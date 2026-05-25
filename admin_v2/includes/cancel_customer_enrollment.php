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


if (isset($_POST['SUBMIT'])) {
    $PK_ENROLLMENT_MASTER = $_POST['PK_ENROLLMENT_MASTER'];
    $PK_ENROLLMENT_PAYMENT = isset($_POST['PK_ENROLLMENT_PAYMENT']) ? $_POST['PK_ENROLLMENT_PAYMENT'] : 0;
    $PK_PAYMENT_TYPE_REFUND = ($_POST['PK_PAYMENT_TYPE_REFUND']) ?? 0;
    $SOURCE = isset($_POST['SOURCE']) ? $_POST['SOURCE'] : '';
    $enrollment_data = $db_account->Execute("SELECT ENROLLMENT_NAME, ENROLLMENT_ID, PK_ENROLLMENT_BILLING FROM DOA_ENROLLMENT_MASTER JOIN DOA_ENROLLMENT_BILLING ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = " . $PK_ENROLLMENT_MASTER);
    if (empty($enrollment_data->fields['ENROLLMENT_NAME'])) {
        $enrollment_name = '';
    } else {
        $enrollment_name = $enrollment_data->fields['ENROLLMENT_NAME'] . " - ";
    }
    if (empty($enrollment_data->fields['ENROLLMENT_ID'])) {
        $enrollment_id = $enrollment_data->fields['MISC_ID'];
    } else {
        $enrollment_id = $enrollment_data->fields['ENROLLMENT_ID'];
    }
    $TOTAL_POSITIVE_BALANCE = $_POST['TOTAL_POSITIVE_BALANCE'];
    $TOTAL_NEGATIVE_BALANCE = $_POST['TOTAL_NEGATIVE_BALANCE'];

    $BALANCE = $TOTAL_POSITIVE_BALANCE + $TOTAL_NEGATIVE_BALANCE;

    if ($SOURCE == 'CANCEL_MODAL') {
        if ($TOTAL_POSITIVE_BALANCE == 0 && $TOTAL_NEGATIVE_BALANCE == 0) {
            $UPDATE_DATA['STATUS'] = 'C';
        } else {
            $UPDATE_DATA['STATUS'] = 'CA';
        }

        if ($_POST['CANCEL_FUTURE_APPOINTMENT'] == 1) {
            $APPOINTMENT_UPDATE_DATA['PK_APPOINTMENT_STATUS'] = 6;
            $APPOINTMENT_UPDATE_DATA['STATUS'] = 'C';
            $CONDITION = " PK_ENROLLMENT_MASTER = '$PK_ENROLLMENT_MASTER' AND IS_CHARGED = 0";
            db_perform_account('DOA_APPOINTMENT_MASTER', $APPOINTMENT_UPDATE_DATA, 'update', $CONDITION);

            $db_account->Execute("UPDATE DOA_APPOINTMENT_MASTER AM
                                    INNER JOIN DOA_APPOINTMENT_ENROLLMENT AE 
                                        ON AE.PK_APPOINTMENT_MASTER = AM.PK_APPOINTMENT_MASTER
                                    SET 
                                        AM.STATUS = 'C',
                                        AM.PK_APPOINTMENT_STATUS = 6,
                                        AE.IS_CHARGED = 0
                                    WHERE 
                                        AE.PK_ENROLLMENT_MASTER = $PK_ENROLLMENT_MASTER
                                        AND AE.IS_CHARGED = 0");
        } elseif ($_POST['CANCEL_FUTURE_APPOINTMENT'] == 2) {
            $APPOINTMENT_UPDATE_DATA['PK_APPOINTMENT_STATUS'] = 6;
            $APPOINTMENT_UPDATE_DATA['STATUS'] = 'C';
            $CONDITION = " PK_ENROLLMENT_MASTER = '$PK_ENROLLMENT_MASTER' AND IS_CHARGED = 0 AND IS_PAID = 0";
            db_perform_account('DOA_APPOINTMENT_MASTER', $APPOINTMENT_UPDATE_DATA, 'update', $CONDITION);

            $db_account->Execute("UPDATE DOA_APPOINTMENT_MASTER AM
                                    INNER JOIN DOA_APPOINTMENT_ENROLLMENT AE 
                                        ON AE.PK_APPOINTMENT_MASTER = AM.PK_APPOINTMENT_MASTER
                                    SET 
                                        AM.STATUS = 'C',
                                        AM.PK_APPOINTMENT_STATUS = 6,
                                        AE.IS_CHARGED = 0
                                    WHERE 
                                        AE.PK_ENROLLMENT_MASTER = $PK_ENROLLMENT_MASTER
                                        AND AE.IS_CHARGED = 0");
        } elseif ($_POST['CANCEL_FUTURE_APPOINTMENT'] == 3) {
            $APPOINTMENT_UPDATE_DATA['PK_ENROLLMENT_MASTER'] = 0;
            $APPOINTMENT_UPDATE_DATA['PK_ENROLLMENT_SERVICE'] = 0;
            $APPOINTMENT_UPDATE_DATA['APPOINTMENT_TYPE'] = 'AD-HOC';
            $APPOINTMENT_UPDATE_DATA['IS_PAID'] = 0;
            $CONDITION = " PK_ENROLLMENT_MASTER = '$PK_ENROLLMENT_MASTER' AND IS_CHARGED = 0";
            db_perform_account('DOA_APPOINTMENT_MASTER', $APPOINTMENT_UPDATE_DATA, 'update', $CONDITION);

            $db_account->Execute("DELETE FROM `DOA_APPOINTMENT_ENROLLMENT` WHERE `PK_ENROLLMENT_MASTER` = '$PK_ENROLLMENT_MASTER' AND IS_CHARGED = 0");
        }

        $TOTAL_ACTUAL_AMOUNT = 0;
        for ($i = 0; $i < count($_POST['PK_ENROLLMENT_SERVICE']); $i++) {
            $enr_service_data = $db_account->Execute("SELECT PRICE_PER_SESSION, TOTAL_AMOUNT_PAID, FINAL_AMOUNT FROM DOA_ENROLLMENT_SERVICE WHERE PK_ENROLLMENT_SERVICE = " . $_POST['PK_ENROLLMENT_SERVICE'][$i]);
            if ($_POST['CANCEL_FUTURE_APPOINTMENT'] == 1 || $_POST['CANCEL_FUTURE_APPOINTMENT'] == 3) {
                $ENR_SERVICE_UPDATE['NUMBER_OF_SESSION'] = getSessionCompletedCount($_POST['PK_ENROLLMENT_SERVICE'][$i]);
            } elseif ($_POST['CANCEL_FUTURE_APPOINTMENT'] == 2) {
                $ENR_SERVICE_UPDATE['NUMBER_OF_SESSION'] = getPaidSessionCount($_POST['PK_ENROLLMENT_SERVICE'][$i]);
            }

            $TOTAL_PAID_AMOUNT = $ENR_SERVICE_UPDATE['NUMBER_OF_SESSION'] * $enr_service_data->fields['PRICE_PER_SESSION'];
            if ($TOTAL_POSITIVE_BALANCE >= 0) {
                $ENR_SERVICE_UPDATE['TOTAL_AMOUNT_PAID'] = ($enr_service_data->fields['TOTAL_AMOUNT_PAID'] < $TOTAL_PAID_AMOUNT) ? $enr_service_data->fields['TOTAL_AMOUNT_PAID'] : $TOTAL_PAID_AMOUNT;
            }

            $ENR_SERVICE_UPDATE['FINAL_AMOUNT'] = $TOTAL_PAID_AMOUNT;
            db_perform_account('DOA_ENROLLMENT_SERVICE', $ENR_SERVICE_UPDATE, 'update', " PK_ENROLLMENT_SERVICE = " . $_POST['PK_ENROLLMENT_SERVICE'][$i]);

            $CANCEL_ENROLLMENT_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
            $CANCEL_ENROLLMENT_DATA['PK_ENROLLMENT_SERVICE'] = $_POST['PK_ENROLLMENT_SERVICE'][$i];
            $CANCEL_ENROLLMENT_DATA['ACTUAL_AMOUNT'] = $enr_service_data->fields['FINAL_AMOUNT'];
            $CANCEL_ENROLLMENT_DATA['CANCEL_AMOUNT'] = $enr_service_data->fields['FINAL_AMOUNT'] - $ENR_SERVICE_UPDATE['FINAL_AMOUNT'];
            $CANCEL_ENROLLMENT_DATA['CANCEL_DATE'] = date('Y-m-d H:i:s');
            db_perform_account('DOA_ENROLLMENT_CANCEL', $CANCEL_ENROLLMENT_DATA, 'insert');

            $TOTAL_ACTUAL_AMOUNT += $ENR_SERVICE_UPDATE['FINAL_AMOUNT'];
        }
        $ENR_BILLING_UPDATE['TOTAL_AMOUNT'] = $ENR_BILLING_UPDATE['BALANCE_PAYABLE'] = $TOTAL_ACTUAL_AMOUNT;
        db_perform_account('DOA_ENROLLMENT_BILLING', $ENR_BILLING_UPDATE, 'update', " PK_ENROLLMENT_MASTER = '$PK_ENROLLMENT_MASTER'");

        db_perform_account('DOA_ENROLLMENT_MASTER', $UPDATE_DATA, 'update', " PK_ENROLLMENT_MASTER = '$PK_ENROLLMENT_MASTER'");
        db_perform_account('DOA_ENROLLMENT_SERVICE', $UPDATE_DATA, 'update', " PK_ENROLLMENT_MASTER = '$PK_ENROLLMENT_MASTER'");
        db_perform_account('DOA_ENROLLMENT_LEDGER', $UPDATE_DATA, 'update', " PK_ENROLLMENT_MASTER = '$PK_ENROLLMENT_MASTER'");

        if ($TOTAL_NEGATIVE_BALANCE < 0) {
            $LEDGER_DATA_BILLING['TRANSACTION_TYPE'] = ($_POST['SUBMIT'] == 'Cancel and Store Info only') ? 'Balance Owed' : 'Billing';
            $LEDGER_DATA_BILLING['ENROLLMENT_LEDGER_PARENT'] = -1;
            $LEDGER_DATA_BILLING['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
            $LEDGER_DATA_BILLING['PK_ENROLLMENT_BILLING'] = $enrollment_data->fields['PK_ENROLLMENT_BILLING'];
            $LEDGER_DATA_BILLING['PAID_AMOUNT'] = 0.00;
            $LEDGER_DATA_BILLING['IS_PAID'] = 0;
            $LEDGER_DATA_BILLING['STATUS'] = 'A';
            $LEDGER_DATA_BILLING['DUE_DATE'] = date('Y-m-d');
            $LEDGER_DATA_BILLING['BILLED_AMOUNT'] = abs($TOTAL_NEGATIVE_BALANCE);
            $LEDGER_DATA_BILLING['BALANCE'] = abs($TOTAL_NEGATIVE_BALANCE);
            db_perform_account('DOA_ENROLLMENT_LEDGER', $LEDGER_DATA_BILLING, 'insert');
            $PK_ENROLLMENT_LEDGER = $db_account->insert_ID();

            $return_data['STATUS'] = $LEDGER_DATA_BILLING['TRANSACTION_TYPE'];
            $return_data['PK_ENROLLMENT_LEDGER'] = $PK_ENROLLMENT_LEDGER;
            $return_data['BILLED_AMOUNT'] = number_format(abs($TOTAL_NEGATIVE_BALANCE), 2, '.', '');
            $return_data['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
            echo json_encode($return_data);
            die();
        } elseif ($TOTAL_POSITIVE_BALANCE >= 0) {
            $LEDGER_DATA['TRANSACTION_TYPE'] = (($TOTAL_POSITIVE_BALANCE == 0) ? 'Cancelled' : (($_POST['SUBMIT'] == 'Cancel and Store Info only') ? 'Refund Credit Available' : 'Refund'));
            $LEDGER_DATA['ENROLLMENT_LEDGER_PARENT'] = -1;
            $LEDGER_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
            $LEDGER_DATA['PK_ENROLLMENT_BILLING'] = $enrollment_data->fields['PK_ENROLLMENT_BILLING'];
            $LEDGER_DATA['PAID_AMOUNT'] = 0.00;
            $LEDGER_DATA['IS_PAID'] = ($_POST['SUBMIT'] === 'Submit') ? 1 : 2;
            $LEDGER_DATA['DUE_DATE'] = date('Y-m-d');
            $LEDGER_DATA['BILLED_AMOUNT'] = 0.00;
            $LEDGER_DATA['BALANCE'] = $BALANCE;
            $LEDGER_DATA['STATUS'] = $UPDATE_DATA['STATUS'];
            db_perform_account('DOA_ENROLLMENT_LEDGER', $LEDGER_DATA, 'insert');
            $PK_ENROLLMENT_LEDGER = $db_account->insert_ID();
        }
    } else {
        $PK_ENROLLMENT_LEDGER = $_POST['PK_ENROLLMENT_LEDGER'];

        $UPDATE_DATA['IS_PAID'] = 1;
        db_perform_account('DOA_ENROLLMENT_LEDGER', $UPDATE_DATA, 'update', " PK_ENROLLMENT_LEDGER = '$PK_ENROLLMENT_LEDGER'");

        if ($PK_ENROLLMENT_PAYMENT > 0) {
            $UPDATE_PAYMENT_DATA['IS_REFUNDED'] = 1;
            db_perform_account('DOA_ENROLLMENT_PAYMENT', $UPDATE_PAYMENT_DATA, 'update', " PK_ENROLLMENT_PAYMENT = '$PK_ENROLLMENT_PAYMENT'");

            $enrollment_billing_data = $db_account->Execute("SELECT `BILLED_AMOUNT`, `AMOUNT_REMAIN` FROM `DOA_ENROLLMENT_LEDGER` WHERE `PK_ENROLLMENT_LEDGER` = '$PK_ENROLLMENT_LEDGER'");
            $AMOUNT_REMAIN = $enrollment_billing_data->fields['AMOUNT_REMAIN'] + $BALANCE;
            if ($AMOUNT_REMAIN >= $enrollment_billing_data->fields['BILLED_AMOUNT']) {
                $PARENT_DATA['AMOUNT_REMAIN'] = 0;
                $PARENT_DATA['IS_PAID'] = 0;
            } else {
                $PARENT_DATA['IS_PAID'] = 0;
                $PARENT_DATA['AMOUNT_REMAIN'] = $AMOUNT_REMAIN;
            }
            db_perform_account('DOA_ENROLLMENT_LEDGER', $PARENT_DATA, 'update', " PK_ENROLLMENT_LEDGER = '$PK_ENROLLMENT_LEDGER'");

            $enrollmentServiceData = $db_account->Execute("SELECT * FROM `DOA_ENROLLMENT_SERVICE` WHERE `PK_ENROLLMENT_MASTER` = " . $PK_ENROLLMENT_MASTER);
            $enrollmentBillingData = $db_account->Execute("SELECT * FROM `DOA_ENROLLMENT_BILLING` WHERE `PK_ENROLLMENT_MASTER` = " . $PK_ENROLLMENT_MASTER);
            $ACTUAL_AMOUNT = $enrollmentBillingData->fields['TOTAL_AMOUNT'];
            while (!$enrollmentServiceData->EOF) {
                $servicePercent = ($enrollmentServiceData->fields['FINAL_AMOUNT'] * 100) / $ACTUAL_AMOUNT;
                $serviceAmount = ($BALANCE * $servicePercent) / 100;
                $ENROLLMENT_SERVICE_UPDATE_DATA['TOTAL_AMOUNT_PAID'] = $enrollmentServiceData->fields['TOTAL_AMOUNT_PAID'] - $serviceAmount;
                db_perform_account('DOA_ENROLLMENT_SERVICE', $ENROLLMENT_SERVICE_UPDATE_DATA, 'update', " PK_ENROLLMENT_SERVICE = " . $enrollmentServiceData->fields['PK_ENROLLMENT_SERVICE']);
                markAppointmentPaid($enrollmentServiceData->fields['PK_ENROLLMENT_SERVICE']);
                $enrollmentServiceData->MoveNext();
            }
        }
    }

    $PK_USER_MASTER = $_POST['PK_USER_MASTER'];
    if ($TOTAL_POSITIVE_BALANCE >= 0) {
        if ($_POST['SUBMIT'] === 'Submit') {
            $RECEIPT_NUMBER = generateReceiptNumber($PK_ENROLLMENT_MASTER);

            if ($PK_ENROLLMENT_PAYMENT > 0) {
                $old_payment_data = $db_account->Execute("SELECT PAYMENT_INFO, RECEIPT_NUMBER FROM DOA_ENROLLMENT_PAYMENT WHERE PK_PAYMENT_TYPE = '$PK_PAYMENT_TYPE_REFUND' AND PK_ENROLLMENT_PAYMENT = '$PK_ENROLLMENT_PAYMENT'");
            } else {
                $old_payment_data = $db_account->Execute("SELECT PAYMENT_INFO FROM DOA_ENROLLMENT_PAYMENT WHERE PK_PAYMENT_TYPE = '$PK_PAYMENT_TYPE_REFUND' AND TYPE = 'Payment' AND IS_REFUNDED = 0 AND PAYMENT_STATUS = 'Success' AND PK_ENROLLMENT_MASTER = '$PK_ENROLLMENT_MASTER' ORDER BY AMOUNT DESC LIMIT 1");
            }

            $PAYMENT_INFO = ($old_payment_data->RecordCount() > 0) ? $old_payment_data->fields['PAYMENT_INFO'] : 'Refund';;
            if ($PK_PAYMENT_TYPE_REFUND == 1 || $PK_PAYMENT_TYPE_REFUND == 14) {

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
                                'amount' => $BALANCE * 100
                            ]);
                        } catch (Exception $e) {
                            echo $e->getMessage();
                            die();
                        }
                        $PAYMENT_INFO_ARRAY = ['REFUND_ID' => $refund->id, 'LAST4' => $transaction_info->LAST4];
                        $PAYMENT_INFO = json_encode($PAYMENT_INFO_ARRAY);
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
                    $refundAmount = $BALANCE;

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
                        } else {
                            echo "Refund ERROR: " . $tresponse->getErrors()[0]->getErrorText();
                            die;
                        }
                    } else {
                        echo "No response returned.";
                        die;
                    }
                }
            } elseif ($PK_PAYMENT_TYPE_REFUND == 7) {
                $old_receipts = getRefundReceipts($PK_ENROLLMENT_MASTER, $BALANCE);

                foreach ($old_receipts as $key => $old_receipt_data) {
                    $return_amount = ($BALANCE > $old_receipt_data['AMOUNT']) ? $old_receipt_data['AMOUNT'] : $BALANCE;

                    $wallet_data = $db_account->Execute("SELECT * FROM DOA_CUSTOMER_WALLET WHERE PK_USER_MASTER = '$PK_USER_MASTER' ORDER BY PK_CUSTOMER_WALLET DESC LIMIT 1");
                    if ($wallet_data->RecordCount() > 0) {
                        $INSERT_DATA['CURRENT_BALANCE'] = $wallet_data->fields['CURRENT_BALANCE'] + $return_amount;
                    } else {
                        $INSERT_DATA['CURRENT_BALANCE'] = $return_amount;
                    }

                    $INSERT_DATA['PK_USER_MASTER'] = $PK_USER_MASTER;
                    $INSERT_DATA['DEBIT'] = 0;
                    $INSERT_DATA['CREDIT'] = $return_amount;
                    $INSERT_DATA['BALANCE_LEFT'] = $return_amount;
                    $INSERT_DATA['DESCRIPTION'] = "Balance credited from enrollment " . $enrollment_name . $enrollment_id;
                    $INSERT_DATA['PK_PAYMENT_TYPE'] = 0;
                    $INSERT_DATA['RECEIPT_NUMBER'] = $old_receipt_data['RECEIPT_NUMBER'];
                    $INSERT_DATA['NOTE'] = "Balance credited from enrollment " . $enrollment_name . $enrollment_id;
                    $INSERT_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
                    $INSERT_DATA['CREATED_ON'] = date("Y-m-d H:i");
                    db_perform_account('DOA_CUSTOMER_WALLET', $INSERT_DATA, 'insert');
                    $PK_CUSTOMER_WALLET = $db_account->Insert_ID();

                    $WALLET_PAYMENT_DATA['PK_ENROLLMENT_MASTER'] = 0;
                    $WALLET_PAYMENT_DATA['PK_ENROLLMENT_BILLING'] = 0;
                    $WALLET_PAYMENT_DATA['PK_PAYMENT_TYPE'] = 7;
                    $WALLET_PAYMENT_DATA['AMOUNT'] = $return_amount;
                    $WALLET_PAYMENT_DATA['PK_ENROLLMENT_LEDGER'] = 0;
                    $WALLET_PAYMENT_DATA['PK_CUSTOMER_WALLET'] = $PK_CUSTOMER_WALLET;
                    $WALLET_PAYMENT_DATA['PK_LOCATION'] = getPkLocation();
                    $WALLET_PAYMENT_DATA['TYPE'] = 'Wallet';
                    $WALLET_PAYMENT_DATA['NOTE'] = "Balance credited from enrollment " . $enrollment_name . $enrollment_id;
                    $WALLET_PAYMENT_DATA['PAYMENT_DATE'] = date('Y-m-d');
                    $WALLET_PAYMENT_DATA['PAYMENT_INFO'] = '';
                    $WALLET_PAYMENT_DATA['PAYMENT_STATUS'] = 'Success';
                    $WALLET_PAYMENT_DATA['RECEIPT_NUMBER'] = $old_receipt_data['RECEIPT_NUMBER'];
                    $WALLET_PAYMENT_DATA['IS_ORIGINAL_RECEIPT'] = 0;
                    db_perform_account('DOA_ENROLLMENT_PAYMENT', $WALLET_PAYMENT_DATA, 'insert');

                    $PAYMENT_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
                    $PAYMENT_DATA['PK_ENROLLMENT_BILLING'] = $enrollment_data->fields['PK_ENROLLMENT_BILLING'];
                    $PAYMENT_DATA['PK_PAYMENT_TYPE'] = 7;
                    $PAYMENT_DATA['AMOUNT'] = $return_amount;
                    $PAYMENT_DATA['PK_ENROLLMENT_LEDGER'] = $PK_ENROLLMENT_LEDGER;
                    $PAYMENT_DATA['TYPE'] = 'Move';
                    $PAYMENT_DATA['NOTE'] = "Balance credited from enrollment " . $enrollment_name . $enrollment_id;
                    $PAYMENT_DATA['PAYMENT_DATE'] = date('Y-m-d');
                    $PAYMENT_DATA['PAYMENT_INFO'] = '';
                    $PAYMENT_DATA['PAYMENT_STATUS'] = 'Success';
                    $PAYMENT_DATA['RECEIPT_NUMBER'] = $old_receipt_data['RECEIPT_NUMBER'];
                    $PAYMENT_DATA['IS_ORIGINAL_RECEIPT'] = 0;
                    db_perform_account('DOA_ENROLLMENT_PAYMENT', $PAYMENT_DATA, 'insert');

                    $BALANCE -= $return_amount;
                }
            } elseif ($PK_PAYMENT_TYPE_REFUND == 2) {
                $PAYMENT_INFO_ARRAY = ['CHECK_NUMBER' => $_POST['REFUND_CHECK_NUMBER'], 'CHECK_DATE' => date('Y-m-d', strtotime($_POST['REFUND_CHECK_DATE']))];
                $PAYMENT_INFO = json_encode($PAYMENT_INFO_ARRAY);
            }

            if ($TOTAL_POSITIVE_BALANCE > 0 && $PK_PAYMENT_TYPE_REFUND != 7) {
                $PAYMENT_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
                $PAYMENT_DATA['PK_ENROLLMENT_BILLING'] = $enrollment_data->fields['PK_ENROLLMENT_BILLING'];
                $PAYMENT_DATA['PK_PAYMENT_TYPE'] = $PK_PAYMENT_TYPE_REFUND;
                $PAYMENT_DATA['AMOUNT'] = $TOTAL_POSITIVE_BALANCE;
                $PAYMENT_DATA['PK_ENROLLMENT_LEDGER'] = $PK_ENROLLMENT_LEDGER;
                $PAYMENT_DATA['TYPE'] = 'Refund';
                $PAYMENT_DATA['NOTE'] = "Balance credited from enrollment " . $enrollment_name . $enrollment_id;
                $PAYMENT_DATA['PAYMENT_DATE'] = date('Y-m-d');
                $PAYMENT_DATA['PAYMENT_INFO'] = $PAYMENT_INFO;
                $PAYMENT_DATA['PAYMENT_STATUS'] = 'Success';
                $PAYMENT_DATA['RECEIPT_NUMBER'] = $RECEIPT_NUMBER;
                $PAYMENT_DATA['IS_ORIGINAL_RECEIPT'] = 1;
                db_perform_account('DOA_ENROLLMENT_PAYMENT', $PAYMENT_DATA, 'insert');
            }
        }
    }

    $db_account->Execute("UPDATE DOA_APPOINTMENT_MASTER SET PK_ENROLLMENT_MASTER = 0, PK_ENROLLMENT_SERVICE = 0, APPOINTMENT_TYPE = 'AD-HOC' WHERE APPOINTMENT_TYPE = 'NORMAL' AND PK_ENROLLMENT_MASTER = '$PK_ENROLLMENT_MASTER'");
    markAdhocAppointmentNormal($PK_ENROLLMENT_MASTER);

    markEnrollmentComplete($PK_ENROLLMENT_MASTER);

    $return_data['STATUS'] = 'SUCCESS';
    echo json_encode($return_data);
    die();
}
