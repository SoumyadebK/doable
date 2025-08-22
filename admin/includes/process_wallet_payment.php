<?php

use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;
use Stripe\StripeClient;

require_once('../../global/config.php');
require_once("../../global/stripe-php-master/init.php");
global $db;
global $db_account;

$header = '../' . $_POST['header'];

$account_data = $db->Execute("SELECT * FROM `DOA_ACCOUNT_MASTER` WHERE `PK_ACCOUNT_MASTER` = '$_SESSION[PK_ACCOUNT_MASTER]'");
$PAYMENT_GATEWAY = $account_data->fields['PAYMENT_GATEWAY_TYPE'];
$SECRET_KEY = $account_data->fields['SECRET_KEY'];
$PUBLISHABLE_KEY = $account_data->fields['PUBLISHABLE_KEY'];

/*$SQUARE_MODE 			= 2;
if ($SQUARE_MODE == 1)
    $SQ_URL = "https://connect.squareup.com";
else if ($SQUARE_MODE == 2)
    $SQ_URL = "https://connect.squareupsandbox.com";

if ($SQUARE_MODE == 1)
    $URL = "https://web.squarecdn.com/v1/square.js";
else if ($SQUARE_MODE == 2)
    $URL = "https://sandbox.web.squarecdn.com/v1/square.js";*/

if (!empty($_POST) && $_POST['FUNCTION_NAME'] == 'processWalletPayment') {
    $RECEIPT_NUMBER = generateReceiptNumber(0);

    $PAYMENT_INFO = 'Payment Done';
    $PAYMENT_STATUS = 'Success';
    $AMOUNT = $_POST['AMOUNT'];

    if ($_POST['PK_PAYMENT_TYPE'] == 1) {

        if ($_POST['PAYMENT_GATEWAY'] == 'Stripe') {
            $stripe = new StripeClient($SECRET_KEY);
            Stripe::setApiKey($SECRET_KEY);

            $user_master = $db->Execute("SELECT DOA_USERS.PK_USER, DOA_USERS.EMAIL_ID, DOA_USERS.FIRST_NAME, DOA_USERS.LAST_NAME, DOA_USERS.PHONE FROM `DOA_USERS` LEFT JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER=DOA_USER_MASTER.PK_USER WHERE DOA_USER_MASTER.PK_USER_MASTER = '$_POST[PK_USER_MASTER]'");
            $customer_payment_info = $db_account->Execute("SELECT DOA_CUSTOMER_PAYMENT_INFO.CUSTOMER_PAYMENT_ID FROM DOA_CUSTOMER_PAYMENT_INFO INNER JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_USER_MASTER.PK_USER = DOA_CUSTOMER_PAYMENT_INFO.PK_USER WHERE DOA_CUSTOMER_PAYMENT_INFO.PAYMENT_TYPE = 'Stripe' AND DOA_USER_MASTER.PK_USER_MASTER = '$_POST[PK_USER_MASTER]'");

            $STRIPE_TOKEN = $_POST['token'];
            $CUSTOMER_PAYMENT_ID = '';
            if ($customer_payment_info->RecordCount() > 0) {
                $CUSTOMER_PAYMENT_ID = $customer_payment_info->fields['CUSTOMER_PAYMENT_ID'];
            } else {
                try {
                    $customer = $stripe->customers->create([
                        'email' => $user_master->fields['EMAIL_ID'],
                        'name' => $user_master->fields['FIRST_NAME'] . " " . $user_master->fields['LAST_NAME'],
                        'phone' => $user_master->fields['PHONE'],
                        'description' => $user_master->fields['FIRST_NAME'] . " " . $user_master->fields['LAST_NAME'],
                    ]);
                    $CUSTOMER_PAYMENT_ID = $customer->id;
                } catch (ApiErrorException $e) {
                    pre_r($e->getMessage());
                }

                $STRIPE_DETAILS['PK_USER']  = $user_master->fields['PK_USER'];
                $STRIPE_DETAILS['CUSTOMER_PAYMENT_ID'] = $CUSTOMER_PAYMENT_ID;
                $STRIPE_DETAILS['PAYMENT_TYPE'] = 'Stripe';
                $STRIPE_DETAILS['CREATED_ON'] = date("Y-m-d H:i");
                db_perform_account('DOA_CUSTOMER_PAYMENT_INFO', $STRIPE_DETAILS, 'insert');
            }
            $card = $stripe->customers->createSource($CUSTOMER_PAYMENT_ID, ['source' => $STRIPE_TOKEN]);
            $stripe->customers->update($CUSTOMER_PAYMENT_ID, ['default_source' => $card->id]);

            $account = \Stripe\Customer::retrieve($CUSTOMER_PAYMENT_ID);
            try {
                $charge = \Stripe\Charge::create(array(
                    "amount" => $AMOUNT * 100,
                    "currency" => "usd",
                    "description" => "Receipt# " . $RECEIPT_NUMBER,
                    "customer" => $CUSTOMER_PAYMENT_ID,
                    "statement_descriptor" => "Receipt# " . $RECEIPT_NUMBER,
                ));

                $LAST4 = $charge->payment_method_details->card->last4;

                if ($charge->paid == 1) {
                    $PAYMENT_STATUS = 'Success';
                    $PAYMENT_INFO_ARRAY = ['CHARGE_ID' => $charge->id, 'LAST4' => $LAST4];
                    $PAYMENT_INFO = json_encode($PAYMENT_INFO_ARRAY);
                } else {
                    $PAYMENT_STATUS = 'Failed';
                    $PAYMENT_INFO = $charge->failure_message;
                }
            } catch (Exception $e) {
                $PAYMENT_STATUS = 'Failed';
                $PAYMENT_INFO = $e->getMessage();
            }
        }
    }

    if ($_POST['PK_PAYMENT_TYPE'] >= 1) {
        $payment_type = $db->Execute("SELECT * FROM DOA_PAYMENT_TYPE WHERE PK_PAYMENT_TYPE = " . $_POST['PK_PAYMENT_TYPE']);
        $PK_USER_MASTER = $_POST['PK_USER_MASTER'];
        $wallet_data = $db_account->Execute("SELECT * FROM DOA_CUSTOMER_WALLET WHERE PK_USER_MASTER = '$PK_USER_MASTER' ORDER BY PK_CUSTOMER_WALLET DESC LIMIT 1");
        if ($wallet_data->RecordCount() > 0) {
            $INSERT_DATA['CURRENT_BALANCE'] = $wallet_data->fields['CURRENT_BALANCE'] + $AMOUNT;
        } else {
            $INSERT_DATA['CURRENT_BALANCE'] = $AMOUNT;
        }
        $INSERT_DATA['PK_USER_MASTER'] = $PK_USER_MASTER;
        $INSERT_DATA['DEBIT'] = 0;
        $INSERT_DATA['CREDIT'] = $AMOUNT;
        $INSERT_DATA['BALANCE_LEFT'] = $AMOUNT;
        $INSERT_DATA['DESCRIPTION'] = "Amount Credited to Your Wallet using " . $payment_type->fields['PAYMENT_TYPE'];
        $INSERT_DATA['PK_PAYMENT_TYPE'] = $_POST['PK_PAYMENT_TYPE'];
        $INSERT_DATA['RECEIPT_NUMBER'] = $RECEIPT_NUMBER;
        $INSERT_DATA['NOTE'] = $_POST['NOTE'];
        $INSERT_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
        $INSERT_DATA['CREATED_ON'] = date("Y-m-d H:i");
        db_perform_account('DOA_CUSTOMER_WALLET', $INSERT_DATA, 'insert');
        $PK_CUSTOMER_WALLET = $db_account->Insert_ID();

        $PAYMENT_DATA['PK_ENROLLMENT_MASTER'] = 0;
        $PAYMENT_DATA['PK_ENROLLMENT_BILLING'] = 0;
        $PAYMENT_DATA['PK_PAYMENT_TYPE'] = $_POST['PK_PAYMENT_TYPE'];
        $PAYMENT_DATA['AMOUNT'] = $AMOUNT;
        $PAYMENT_DATA['PK_ENROLLMENT_LEDGER'] = 0;
        $TYPE = 'Wallet';
        if ($_POST['PK_PAYMENT_TYPE'] == 2) {
            $PAYMENT_INFO_ARRAY = ['CHECK_NUMBER' => $_POST['CHECK_NUMBER'], 'CHECK_DATE' => date('Y-m-d', strtotime($_POST['CHECK_DATE']))];
            $PAYMENT_INFO = json_encode($PAYMENT_INFO_ARRAY);
        }
        $PAYMENT_DATA['PK_CUSTOMER_WALLET'] = $PK_CUSTOMER_WALLET;
        $PAYMENT_DATA['PK_LOCATION'] = getPkLocation();
        $PAYMENT_DATA['TYPE'] = $TYPE;
        $PAYMENT_DATA['NOTE'] = $_POST['NOTE'];
        $PAYMENT_DATA['PAYMENT_DATE'] = date('Y-m-d');
        $PAYMENT_DATA['PAYMENT_INFO'] = $PAYMENT_INFO;
        $PAYMENT_DATA['PAYMENT_STATUS'] = $PAYMENT_STATUS;
        $PAYMENT_DATA['RECEIPT_NUMBER'] = $RECEIPT_NUMBER;
        $PAYMENT_DATA['IS_ORIGINAL_RECEIPT'] = 1;

        db_perform_account('DOA_ENROLLMENT_PAYMENT', $PAYMENT_DATA, 'insert');
    }
    header('location:' . $header);
}
