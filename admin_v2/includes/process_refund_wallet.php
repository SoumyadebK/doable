<?php

use Mpdf\Mpdf;
use Square\Environment;
use Square\Models\CreatePaymentRequest;
use Square\Models\Money;
use Square\SquareClient;
use Stripe\Stripe;
use Stripe\StripeClient;

require_once('../../global/authorizenet/autoload.php');

use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

require_once('../../global/config.php');
require_once("../../global/stripe-php/init.php");
global $db;
global $db_account;

$PK_CUSTOMER_WALLET = $_POST['PK_CUSTOMER_WALLET'];
$WALLET_REFUND_AMOUNT = $_POST['WALLET_REFUND_AMOUNT'];
$PK_PAYMENT_TYPE_WALLET_REFUND = $_POST['PK_PAYMENT_TYPE_WALLET_REFUND'];

$wallet_data = $db_account->Execute("SELECT * FROM DOA_CUSTOMER_WALLET WHERE PK_CUSTOMER_WALLET = '$PK_CUSTOMER_WALLET'");

if ($WALLET_REFUND_AMOUNT <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Refund amount must be greater than zero.']);
    exit;
} elseif ($wallet_data->fields['BALANCE_LEFT'] >= $WALLET_REFUND_AMOUNT) {
    echo json_encode(['status' => 'error', 'message' => 'Refund amount cannot be greater than wallet balance.']);
    exit;
}

if ($PK_PAYMENT_TYPE_WALLET_REFUND == 2) {
    $REFUND_TYPE = 'Check : ' . $_POST['REFUND_CHECK_NUMBER'];
    $PAYMENT_INFO_ARRAY = ['CHECK_NUMBER' => $_POST['REFUND_CHECK_NUMBER'], 'CHECK_DATE' => date('Y-m-d', strtotime($_POST['REFUND_CHECK_DATE']))];
    $PAYMENT_INFO = json_encode($PAYMENT_INFO_ARRAY);
} elseif ($PK_PAYMENT_TYPE_WALLET_REFUND == 1 || $PK_PAYMENT_TYPE_WALLET_REFUND == 14) {
    $REFUND_TYPE = 'Card on file';
    $old_payment_data = $db_account->Execute("SELECT PAYMENT_INFO, RECEIPT_NUMBER FROM DOA_ENROLLMENT_PAYMENT WHERE PK_CUSTOMER_WALLET = '$PK_CUSTOMER_WALLET' AND (PK_PAYMENT_TYPE = 1 OR PK_PAYMENT_TYPE = 14) AND TYPE = 'Wallet' AND IS_REFUNDED = 0 AND PAYMENT_STATUS = 'Success'");

    if ($old_payment_data->RecordCount() == 0) {
        echo json_encode(['status' => 'error', 'message' => 'Original payment data not found for card refund.']);
        exit;
    }

    $PAYMENT_INFO = ($old_payment_data->RecordCount() > 0) ? $old_payment_data->fields['PAYMENT_INFO'] : 'Refund';

    $payment_gateway_data = getPaymentGatewayData();

    $PAYMENT_GATEWAY = $payment_gateway_data->fields['PAYMENT_GATEWAY_TYPE'];
    $GATEWAY_MODE = $payment_gateway_data->fields['GATEWAY_MODE'];

    $SECRET_KEY = $payment_gateway_data->fields['SECRET_KEY'];
    $PUBLISHABLE_KEY = $payment_gateway_data->fields['PUBLISHABLE_KEY'];

    $SQUARE_ACCESS_TOKEN = $payment_gateway_data->fields['ACCESS_TOKEN'];
    $SQUARE_APP_ID = $payment_gateway_data->fields['APP_ID'];
    $SQUARE_LOCATION_ID = $payment_gateway_data->fields['LOCATION_ID'];

    $AUTHORIZE_LOGIN_ID = $payment_gateway_data->fields['LOGIN_ID']; //"4Y5pCy8Qr";
    $AUTHORIZE_TRANSACTION_KEY = $payment_gateway_data->fields['TRANSACTION_KEY']; //"4ke43FW8z3287HV5";
    $AUTHORIZE_CLIENT_KEY = $payment_gateway_data->fields['AUTHORIZE_CLIENT_KEY']; //"8ZkyJnT87uFztUz56B4PfgCe7yffEZA4TR5dv8ALjqk5u9mr6d8Nmt8KHyp8s9Ay";

    $MERCHANT_ID = $payment_gateway_data->fields['MERCHANT_ID'];
    $API_KEY = $payment_gateway_data->fields['API_KEY'];
    $PUBLIC_API_KEY = $payment_gateway_data->fields['PUBLIC_API_KEY'];

    $transaction_info = json_decode($old_payment_data->fields['PAYMENT_INFO']);
    if ($PAYMENT_GATEWAY == 'Stripe') {
        if (isset($transaction_info->CHARGE_ID)) {
            Stripe::setApiKey($SECRET_KEY);

            $transaction_id = $transaction_info->CHARGE_ID;
            try {
                $refund = \Stripe\Refund::create([
                    'charge' => $transaction_id,
                    'amount' => $WALLET_REFUND_AMOUNT * 100
                ]);
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => 'Stripe Refund Error: ' . $e->getMessage()]);
                exit;
            }
            $PAYMENT_INFO_ARRAY = ['REFUND_ID' => $refund->id, 'LAST4' => $transaction_info->LAST4];
            $PAYMENT_INFO = json_encode($PAYMENT_INFO_ARRAY);
            $REFUND_TYPE = 'Card #' . $transaction_info->LAST4;
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Charge ID not found in original payment info for Stripe refund.']);
            exit;
        }
    } elseif ($PAYMENT_GATEWAY = 'Authorized.net') {
        $transaction_id = $transaction_info->CHARGE_ID;
        $accountNumber = $transaction_info->LAST4;

        // Merchant Authentication
        $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
        $merchantAuthentication->setName($AUTHORIZE_LOGIN_ID);
        $merchantAuthentication->setTransactionKey($AUTHORIZE_TRANSACTION_KEY);

        $originalTransactionId = $transaction_id;
        $last4 = substr($accountNumber, -4);
        $refundAmount = $WALLET_REFUND_AMOUNT;

        // Refund Request
        $transactionRequest = new AnetAPI\TransactionRequestType();
        $transactionRequest->setTransactionType("refundTransaction");
        $transactionRequest->setRefTransId($originalTransactionId);
        $transactionRequest->setAmount($refundAmount);

        // Payment object with last 4 digits + dummy expiry
        $creditCard = new AnetAPI\CreditCardType();
        $creditCard->setCardNumber($last4); // only last 4 digits
        $creditCard->setExpirationDate("1230"); // or "1225"
        $payment = new AnetAPI\PaymentType();
        $payment->setCreditCard($creditCard);

        $transactionRequest->setPayment($payment);

        // Build request
        $request = new AnetAPI\CreateTransactionRequest();
        $request->setMerchantAuthentication($merchantAuthentication);
        $request->setTransactionRequest($transactionRequest);

        $controller = new AnetController\CreateTransactionController($request);

        if ($GATEWAY_MODE == 'test') {
            $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::SANDBOX);
        } else {
            $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::PRODUCTION);
        }

        if ($response != null) {
            $tresponse = $response->getTransactionResponse();
            if ($tresponse != null && $tresponse->getResponseCode() == "1") {
                $PAYMENT_INFO_ARRAY = ['REFUND_ID' => $tresponse->getTransId(), 'LAST4' => $accountNumber];
                $PAYMENT_INFO = json_encode($PAYMENT_INFO_ARRAY);
                $REFUND_TYPE = 'Card #' . $accountNumber;
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Authorized.net Refund Error: ' . $tresponse->getErrors()[0]->getErrorText()]);
                exit;
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No response returned from Authorized.net.']);
            exit;
        }
    }
} else {
    $refund_type_data = $db->Execute("SELECT PAYMENT_TYPE FROM DOA_PAYMENT_TYPE WHERE PK_PAYMENT_TYPE = '$PK_PAYMENT_TYPE_WALLET_REFUND'");
    $REFUND_TYPE = $refund_type_data->fields['PAYMENT_TYPE'];
    $PAYMENT_INFO_ARRAY = ['REFUND_TYPE' => $REFUND_TYPE];
    $PAYMENT_INFO = json_encode($PAYMENT_INFO_ARRAY);
}

if ($wallet_data->RecordCount() > 0 && $wallet_data->fields['BALANCE_LEFT'] >= $WALLET_REFUND_AMOUNT) {
    $UPDATE_DATA['BALANCE_LEFT'] = $wallet_data->fields['BALANCE_LEFT'] - $WALLET_REFUND_AMOUNT;
    db_perform_account('DOA_CUSTOMER_WALLET', $UPDATE_DATA, 'update', "PK_CUSTOMER_WALLET = '$PK_CUSTOMER_WALLET'");
} else {
    echo json_encode(['status' => 'error', 'message' => 'Customer wallet not found or insufficient balance.']);
    exit;
}

$RECEIPT_NUMBER = generateReceiptNumber($PK_CUSTOMER_WALLET);

$INSERT_DATA['CUSTOMER_WALLET_PARENT'] = $PK_CUSTOMER_WALLET;
$INSERT_DATA['PK_USER_MASTER'] = $wallet_data->fields['PK_USER_MASTER'];
$INSERT_DATA['DEBIT'] = $WALLET_REFUND_AMOUNT;
$INSERT_DATA['CREDIT'] = 0;
$INSERT_DATA['BALANCE_LEFT'] = 0;
$INSERT_DATA['DESCRIPTION'] = "Refund from wallet by " . $REFUND_TYPE;
$INSERT_DATA['PK_PAYMENT_TYPE'] = $PK_PAYMENT_TYPE_WALLET_REFUND;
$INSERT_DATA['RECEIPT_NUMBER'] = $RECEIPT_NUMBER;
$INSERT_DATA['NOTE'] = "Refund from wallet by " . $REFUND_TYPE;
$INSERT_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
$INSERT_DATA['CREATED_ON'] = date("Y-m-d H:i");
db_perform_account('DOA_CUSTOMER_WALLET', $INSERT_DATA, 'insert');
$PK_CUSTOMER_WALLET_NEW = $db_account->Insert_ID();

$WALLET_PAYMENT_DATA['PK_ENROLLMENT_MASTER'] = 0;
$WALLET_PAYMENT_DATA['PK_ENROLLMENT_BILLING'] = 0;
$WALLET_PAYMENT_DATA['PK_ENROLLMENT_LEDGER'] = 0;
$WALLET_PAYMENT_DATA['PK_PAYMENT_TYPE'] = 7;
$WALLET_PAYMENT_DATA['AMOUNT'] = $WALLET_REFUND_AMOUNT;
$WALLET_PAYMENT_DATA['PK_CUSTOMER_WALLET'] = $PK_CUSTOMER_WALLET_NEW;
$WALLET_PAYMENT_DATA['PK_LOCATION'] = getPkLocation();
$WALLET_PAYMENT_DATA['TYPE'] = 'Wallet Refund';
$WALLET_PAYMENT_DATA['NOTE'] = "Refund from wallet by " . $REFUND_TYPE;
$WALLET_PAYMENT_DATA['PAYMENT_DATE'] = date('Y-m-d');
$WALLET_PAYMENT_DATA['PAYMENT_INFO'] = $PAYMENT_INFO;
$WALLET_PAYMENT_DATA['PAYMENT_STATUS'] = 'Success';
$WALLET_PAYMENT_DATA['RECEIPT_NUMBER'] = $RECEIPT_NUMBER;
$WALLET_PAYMENT_DATA['IS_ORIGINAL_RECEIPT'] = 1;
db_perform_account('DOA_ENROLLMENT_PAYMENT', $WALLET_PAYMENT_DATA, 'insert');

echo json_encode(['status' => 'success', 'message' => 'Refund processed successfully.']);
