<?php
require_once('../../global/config.php');

use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;
use Stripe\StripeClient;


$RESPONSE_DATA = $_POST;
$FUNCTION_NAME = $RESPONSE_DATA['FUNCTION_NAME'];
unset($RESPONSE_DATA['FUNCTION_NAME']);
$FUNCTION_NAME($RESPONSE_DATA);

/*Saving Data from Service Code Page*/
function saveServiceInfoData($RESPONSE_DATA){
    error_reporting(0);
    global $db_account;
    $RESPONSE_DATA['SERVICE_NAME'] = $_POST['SERVICE_NAME'];
    $RESPONSE_DATA['DESCRIPTION'] = $_POST['DESCRIPTION'];
    $RESPONSE_DATA['ACTIVE'] = $_POST['ACTIVE'];
    $RESPONSE_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
    if(empty($RESPONSE_DATA['PK_SERVICE_MASTER'])){
        $RESPONSE_DATA['ACTIVE'] = 1;
        $RESPONSE_DATA['CREATED_BY']  = $_SESSION['PK_USER'];
        $RESPONSE_DATA['CREATED_ON']  = date("Y-m-d H:i");
        db_perform_account('DOA_SERVICE_MASTER', $RESPONSE_DATA, 'insert');
        $PK_SERVICE_MASTER = $db_account->insert_ID();
    }else{
        $RESPONSE_DATA['ACTIVE'] = $_POST['ACTIVE'];
        $RESPONSE_DATA['EDITED_BY']	= $_SESSION['PK_USER'];
        $RESPONSE_DATA['EDITED_ON'] = date("Y-m-d H:i");
        db_perform_account('DOA_SERVICE_MASTER', $RESPONSE_DATA, 'update'," PK_SERVICE_MASTER =  '$RESPONSE_DATA[PK_SERVICE_MASTER]'");
        $PK_SERVICE_MASTER = $RESPONSE_DATA['PK_SERVICE_MASTER'];
    }
    echo $PK_SERVICE_MASTER;
}

function saveServiceCodeData($RESPONSE_DATA){
    global $db_account;
    if (count($RESPONSE_DATA['SERVICE_CODE']) > 0) {
        $db_account->Execute("DELETE FROM `DOA_SERVICE_CODE` WHERE `PK_SERVICE_MASTER` = '$RESPONSE_DATA[PK_SERVICE_MASTER]'");
        for ($i = 0; $i < count($RESPONSE_DATA['SERVICE_CODE']); $i++) {
            $SERVICE_CODE_DATA['PK_SERVICE_MASTER'] = $RESPONSE_DATA['PK_SERVICE_MASTER'];
            $SERVICE_CODE_DATA['PK_FREQUENCY'] = $RESPONSE_DATA['PK_FREQUENCY'][$i];
            $SERVICE_CODE_DATA['DURATION'] = $RESPONSE_DATA['DURATION'][$i];
            $SERVICE_CODE_DATA['IS_GROUP'] = $RESPONSE_DATA['IS_GROUP_'.$i];
            $SERVICE_CODE_DATA['IS_CHARGEABLE'] = $RESPONSE_DATA['IS_CHARGEABLE_'.$i];
            $SERVICE_CODE_DATA['PRICE'] = $RESPONSE_DATA['PRICE'][$i];
            $SERVICE_CODE_DATA['SERVICE_CODE'] = $RESPONSE_DATA['SERVICE_CODE'][$i];
            $SERVICE_CODE_DATA['DESCRIPTION'] = $RESPONSE_DATA['SERVICE_CODE_DESCRIPTION'][$i];
            db_perform_account('DOA_SERVICE_CODE', $SERVICE_CODE_DATA, 'insert');
        }
    }
}

/*Saving Data from Enrollment Page*/

function saveEnrollmentData($RESPONSE_DATA){
    error_reporting(0);
    global $db;
    $ENROLLMENT_MASTER_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
    $ENROLLMENT_MASTER_DATA['PK_USER'] = $RESPONSE_DATA['PK_USER'];
    $ENROLLMENT_MASTER_DATA['PK_LOCATION'] = $RESPONSE_DATA['PK_LOCATION'];
    $ENROLLMENT_MASTER_DATA['PK_AGREEMENT_TYPE'] = $RESPONSE_DATA['PK_AGREEMENT_TYPE'];
    $ENROLLMENT_MASTER_DATA['PK_DOCUMENT_LIBRARY'] = $RESPONSE_DATA['PK_DOCUMENT_LIBRARY'];
    $ENROLLMENT_MASTER_DATA['ENROLLMENT_BY_ID'] = $RESPONSE_DATA['ENROLLMENT_BY_ID'];

    if(empty($RESPONSE_DATA['PK_ENROLLMENT_MASTER'])){
        $account_data = $db->Execute("SELECT ENROLLMENT_ID_CHAR, ENROLLMENT_ID_NUM FROM `DOA_ACCOUNT_MASTER` WHERE `PK_ACCOUNT_MASTER` = '$_SESSION[PK_ACCOUNT_MASTER]'");
        $enrollment_data = $db->Execute("SELECT ENROLLMENT_ID FROM `DOA_ENROLLMENT_MASTER` WHERE `PK_ACCOUNT_MASTER` = '$_SESSION[PK_ACCOUNT_MASTER]' ORDER BY ENROLLMENT_ID DESC LIMIT 1");
        if ($enrollment_data->RecordCount() > 0){
            $last_enrollment_id = str_replace($account_data->fields['ENROLLMENT_ID_CHAR'], '', $enrollment_data->fields['ENROLLMENT_ID']) ;
            $ENROLLMENT_MASTER_DATA['ENROLLMENT_ID'] = $account_data->fields['ENROLLMENT_ID_CHAR'].($last_enrollment_id+1);
        }else{
            $ENROLLMENT_MASTER_DATA['ENROLLMENT_ID'] = $account_data->fields['ENROLLMENT_ID_CHAR'].$account_data->fields['ENROLLMENT_ID_NUM'];
        }
        $ENROLLMENT_MASTER_DATA['ACTIVE'] = 1;
        $ENROLLMENT_MASTER_DATA['CREATED_BY']  = $_SESSION['PK_USER'];
        $ENROLLMENT_MASTER_DATA['CREATED_ON']  = date("Y-m-d H:i");
        db_perform_account('DOA_ENROLLMENT_MASTER', $ENROLLMENT_MASTER_DATA, 'insert');
        $PK_ENROLLMENT_MASTER = $db->insert_ID();
    }else{
        $ENROLLMENT_MASTER_DATA['ACTIVE'] = $RESPONSE_DATA['ACTIVE'];
        $ENROLLMENT_MASTER_DATA['EDITED_BY']	= $_SESSION['PK_USER'];
        $ENROLLMENT_MASTER_DATA['EDITED_ON'] = date("Y-m-d H:i");
        db_perform_account('DOA_ENROLLMENT_MASTER', $ENROLLMENT_MASTER_DATA, 'update'," PK_ENROLLMENT_MASTER =  '$RESPONSE_DATA[PK_ENROLLMENT_MASTER]'");
        $PK_ENROLLMENT_MASTER = $RESPONSE_DATA['PK_ENROLLMENT_MASTER'];
    }

    $total = 0;
    if (isset($RESPONSE_DATA['PK_SERVICE_MASTER']) && count($RESPONSE_DATA['PK_SERVICE_MASTER']) > 0){
        $res = $db_account->Execute("DELETE FROM `DOA_ENROLLMENT_SERVICE` WHERE `PK_ENROLLMENT_MASTER` = '$PK_ENROLLMENT_MASTER'");
        for ($i = 0; $i < count($RESPONSE_DATA['PK_SERVICE_MASTER']); $i++){
            $ENROLLMENT_SERVICE_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
            $ENROLLMENT_SERVICE_DATA['PK_SERVICE_MASTER'] = $RESPONSE_DATA['PK_SERVICE_MASTER'][$i];
            $ENROLLMENT_SERVICE_DATA['PK_SERVICE_CODE'] = $RESPONSE_DATA['PK_SERVICE_CODE'][$i];
            $ENROLLMENT_SERVICE_DATA['SERVICE_DETAILS'] = $RESPONSE_DATA['SERVICE_DETAILS'][$i];
            $ENROLLMENT_SERVICE_DATA['FREQUENCY'] = $RESPONSE_DATA['FREQUENCY'][$i];
            $ENROLLMENT_SERVICE_DATA['NUMBER_OF_SESSION'] = $RESPONSE_DATA['NUMBER_OF_SESSION'][$i];
            $ENROLLMENT_SERVICE_DATA['PRICE_PER_SESSION'] = $RESPONSE_DATA['PRICE_PER_SESSION'][$i];
            $ENROLLMENT_SERVICE_DATA['TOTAL'] = $RESPONSE_DATA['TOTAL'][$i];
            db_perform_account('DOA_ENROLLMENT_SERVICE', $ENROLLMENT_SERVICE_DATA, 'insert');
            $total += $RESPONSE_DATA['TOTAL'][$i];
        }
    }

    $return_data['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
    $return_data['TOTAL_AMOUNT'] = $total;
    echo json_encode($return_data);
}

function saveEnrollmentBillingData($RESPONSE_DATA){
    error_reporting(0);
    global $db_account;
    $PK_ENROLLMENT_SERVICE = $RESPONSE_DATA['PK_ENROLLMENT_SERVICE'];
    $FLEXIBLE_PAYMENT_DATE = isset($RESPONSE_DATA['FLEXIBLE_PAYMENT_DATE'])?$RESPONSE_DATA['FLEXIBLE_PAYMENT_DATE']:[];
    $FLEXIBLE_PAYMENT_AMOUNT = isset($RESPONSE_DATA['FLEXIBLE_PAYMENT_AMOUNT'])?$RESPONSE_DATA['FLEXIBLE_PAYMENT_AMOUNT']:[];
    unset($RESPONSE_DATA['PK_ENROLLMENT_SERVICE']);
    unset($RESPONSE_DATA['FLEXIBLE_PAYMENT_DATE']);
    unset($RESPONSE_DATA['FLEXIBLE_PAYMENT_AMOUNT']);
    $RESPONSE_DATA['BILLING_DATE'] = date('Y-m-d', strtotime($RESPONSE_DATA['BILLING_DATE']));
    $RESPONSE_DATA['FIRST_DUE_DATE'] = date('Y-m-d', strtotime($RESPONSE_DATA['FIRST_DUE_DATE']));
    $PK_ENROLLMENT_LEDGER = 0;
    if(empty($RESPONSE_DATA['PK_ENROLLMENT_BILLING'])){
        if ($RESPONSE_DATA['PK_SERVICE_CLASS'] == 1){
            $ENROLLMENT_BILLING_DATA['PK_ENROLLMENT_MASTER'] = $RESPONSE_DATA['PK_ENROLLMENT_MASTER'];
            $ENROLLMENT_BILLING_DATA['BILLING_REF'] = $RESPONSE_DATA['BILLING_REF'];
            $ENROLLMENT_BILLING_DATA['BILLING_DATE'] = $RESPONSE_DATA['BILLING_DATE'];
            $ENROLLMENT_BILLING_DATA['DOWN_PAYMENT'] = 0;
            $ENROLLMENT_BILLING_DATA['BALANCE_PAYABLE'] = $RESPONSE_DATA['BALANCE_PAYABLE'];
            $ENROLLMENT_BILLING_DATA['TOTAL_AMOUNT'] = $RESPONSE_DATA['TOTAL_AMOUNT'];
            $ENROLLMENT_BILLING_DATA['PAYMENT_METHOD'] = 0;
            $ENROLLMENT_BILLING_DATA['PAYMENT_TERM'] = '';
            $ENROLLMENT_BILLING_DATA['NUMBER_OF_PAYMENT'] = 0;
            $ENROLLMENT_BILLING_DATA['FIRST_DUE_DATE'] = date('Y-m-d', strtotime($RESPONSE_DATA['MEMBERSHIP_PAYMENT_DATE']));
            $ENROLLMENT_BILLING_DATA['INSTALLMENT_AMOUNT'] = $RESPONSE_DATA['MEMBERSHIP_PAYMENT_AMOUNT'];
            db_perform_account('DOA_ENROLLMENT_BILLING', $ENROLLMENT_BILLING_DATA, 'insert');
            $PK_ENROLLMENT_BILLING = $db->insert_ID();

            $LEDGER_DATA['TRANSACTION_TYPE'] = 'Billing';
            $LEDGER_DATA['ENROLLMENT_LEDGER_PARENT'] = 0;
            $LEDGER_DATA['PK_ENROLLMENT_MASTER'] = $RESPONSE_DATA['PK_ENROLLMENT_MASTER'];
            $LEDGER_DATA['PK_ENROLLMENT_BILLING'] = $PK_ENROLLMENT_BILLING;
            $LEDGER_DATA['PAID_AMOUNT'] = 0.00;
            $LEDGER_DATA['IS_PAID'] = 0;
            $LEDGER_DATA['DUE_DATE'] = date('Y-m-d', strtotime($RESPONSE_DATA['MEMBERSHIP_PAYMENT_DATE']));
            $LEDGER_DATA['BILLED_AMOUNT'] = $RESPONSE_DATA['BALANCE_PAYABLE'];
            $LEDGER_DATA['BALANCE'] = $RESPONSE_DATA['MEMBERSHIP_PAYMENT_AMOUNT'];
            db_perform_account('DOA_ENROLLMENT_LEDGER', $LEDGER_DATA, 'insert');
            $PK_ENROLLMENT_LEDGER = $db->insert_ID();
        }else {
            unset($RESPONSE_DATA['PK_SERVICE_CLASS']);
            unset($RESPONSE_DATA['MEMBERSHIP_PAYMENT_DATE']);
            unset($RESPONSE_DATA['MEMBERSHIP_PAYMENT_AMOUNT']);
            db_perform_account('DOA_ENROLLMENT_BILLING', $RESPONSE_DATA, 'insert');
            $PK_ENROLLMENT_BILLING = $db->insert_ID();
            for ($i = 0; $i < count($PK_ENROLLMENT_SERVICE); $i++) {
                $SESSION_MASTER_DATA['PK_ENROLLMENT_MASTER'] = $RESPONSE_DATA['PK_ENROLLMENT_MASTER'];
                $SESSION_MASTER_DATA['PK_ENROLLMENT_BILLING'] = $PK_ENROLLMENT_BILLING;
                $SESSION_MASTER_DATA['PK_ENROLLMENT_SERVICE'] = $PK_ENROLLMENT_SERVICE[$i];
                $SESSION_MASTER_DATA['SESSION_STATUS'] = 'Purchased';
                db_perform('DOA_SESSION_MASTER', $SESSION_MASTER_DATA, 'insert');
            }

            $LEDGER_DATA['TRANSACTION_TYPE'] = 'Billing';
            $LEDGER_DATA['ENROLLMENT_LEDGER_PARENT'] = 0;
            $LEDGER_DATA['PK_ENROLLMENT_MASTER'] = $RESPONSE_DATA['PK_ENROLLMENT_MASTER'];
            $LEDGER_DATA['PK_ENROLLMENT_BILLING'] = $PK_ENROLLMENT_BILLING;
            $LEDGER_DATA['PAID_AMOUNT'] = 0.00;
            $LEDGER_DATA['IS_PAID'] = 0;

            if ($RESPONSE_DATA['PAYMENT_METHOD'] == 'One Time') {
                $LEDGER_DATA['DUE_DATE'] = date('Y-m-d');
                $LEDGER_DATA['BILLED_AMOUNT'] = $RESPONSE_DATA['BALANCE_PAYABLE'];
                $LEDGER_DATA['BALANCE'] = $RESPONSE_DATA['BALANCE_PAYABLE'];
                db_perform_account('DOA_ENROLLMENT_LEDGER', $LEDGER_DATA, 'insert');
                $PK_ENROLLMENT_LEDGER = $db_account->insert_ID();
            } elseif ($RESPONSE_DATA['PAYMENT_METHOD'] == 'Payment Plans') {
                if ($RESPONSE_DATA['DOWN_PAYMENT'] > 0) {
                    $LEDGER_DATA['DUE_DATE'] = date('Y-m-d');
                    $LEDGER_DATA['BILLED_AMOUNT'] = $RESPONSE_DATA['DOWN_PAYMENT'];
                    $LEDGER_DATA['BALANCE'] = $RESPONSE_DATA['DOWN_PAYMENT'];
                    db_perform_account('DOA_ENROLLMENT_LEDGER', $LEDGER_DATA, 'insert');
                    $PK_ENROLLMENT_LEDGER = $db_account->insert_ID();
                }
                $BALANCE = $RESPONSE_DATA['DOWN_PAYMENT'];
                for ($i = 0; $i < $RESPONSE_DATA['NUMBER_OF_PAYMENT']; $i++) {
                    if ($RESPONSE_DATA['PAYMENT_TERM'] == 'Monthly') {
                        $LEDGER_DATA['DUE_DATE'] = date("Y-m-d", strtotime("+" . $i . " month", strtotime($RESPONSE_DATA['FIRST_DUE_DATE'])));
                    } elseif ($RESPONSE_DATA['PAYMENT_TERM'] == 'Quarterly') {
                        $LEDGER_DATA['DUE_DATE'] = date("Y-m-d", strtotime("+" . $i * 3 . " month", strtotime($RESPONSE_DATA['FIRST_DUE_DATE'])));
                    }
                    $LEDGER_DATA['BILLED_AMOUNT'] = $RESPONSE_DATA['INSTALLMENT_AMOUNT'];
                    $BALANCE = ($BALANCE + $RESPONSE_DATA['INSTALLMENT_AMOUNT']);
                    $LEDGER_DATA['BALANCE'] = $BALANCE;
                    db_perform_account('DOA_ENROLLMENT_LEDGER', $LEDGER_DATA, 'insert');
                    if ($RESPONSE_DATA['DOWN_PAYMENT'] <= 0 && $i == 0) {
                        $PK_ENROLLMENT_LEDGER = $db_account->insert_ID();
                    }
                }
            } elseif ($RESPONSE_DATA['PAYMENT_METHOD'] == 'Flexible Payments') {
                if ($RESPONSE_DATA['DOWN_PAYMENT'] > 0) {
                    $LEDGER_DATA['DUE_DATE'] = date('Y-m-d');
                    $LEDGER_DATA['BILLED_AMOUNT'] = $RESPONSE_DATA['DOWN_PAYMENT'];
                    $LEDGER_DATA['BALANCE'] = $RESPONSE_DATA['DOWN_PAYMENT'];
                    db_perform_account('DOA_ENROLLMENT_LEDGER', $LEDGER_DATA, 'insert');
                    $PK_ENROLLMENT_LEDGER = $db->insert_ID();
                }
                $BALANCE = $RESPONSE_DATA['DOWN_PAYMENT'];
                for ($i = 0; $i < count($FLEXIBLE_PAYMENT_DATE); $i++) {
                    $LEDGER_DATA['DUE_DATE'] = date('Y-m-d', strtotime($FLEXIBLE_PAYMENT_DATE[$i]));
                    $LEDGER_DATA['BILLED_AMOUNT'] = $FLEXIBLE_PAYMENT_AMOUNT[$i];
                    $BALANCE = ($BALANCE + $FLEXIBLE_PAYMENT_AMOUNT[$i]);
                    $LEDGER_DATA['BALANCE'] = $BALANCE;
                    db_perform_account('DOA_ENROLLMENT_LEDGER', $LEDGER_DATA, 'insert');
                    if ($RESPONSE_DATA['DOWN_PAYMENT'] <= 0 && $i == 0) {
                        $PK_ENROLLMENT_LEDGER = $db->insert_ID();
                    }
                }
            }
        }
    }else{
        db_perform_account('DOA_ENROLLMENT_BILLING', $RESPONSE_DATA, 'update'," PK_ENROLLMENT_BILLING =  '$RESPONSE_DATA[PK_ENROLLMENT_BILLING]'");
        $PK_ENROLLMENT_BILLING = $RESPONSE_DATA['PK_ENROLLMENT_BILLING'];
    }
    $return_data['PK_ENROLLMENT_BILLING'] = $PK_ENROLLMENT_BILLING;
    $return_data['PK_ENROLLMENT_LEDGER'] = $PK_ENROLLMENT_LEDGER;
    echo json_encode($return_data);
}

/*function confirmEnrollmentPayment($RESPONSE_DATA){
    global $db;
    $RESPONSE_DATA['PAYMENT_DATE'] = date('Y-m-d');
    $PK_ENROLLMENT_LEDGER = $RESPONSE_DATA['PK_ENROLLMENT_LEDGER'];
    unset($RESPONSE_DATA['PK_ENROLLMENT_LEDGER']);
    if(empty($RESPONSE_DATA['PK_ENROLLMENT_PAYMENT'])){
        if ($RESPONSE_DATA['PK_PAYMENT_TYPE'] == 1) {
            if ($RESPONSE_DATA['PAYMENT_GATEWAY'] == 'Stripe') {
                require_once("../../global/stripe/init.php");
                \Stripe\Stripe::setApiKey($RESPONSE_DATA['SECRET_KEY']);
                $STRIPE_TOKEN = $RESPONSE_DATA['token'];
                $AMOUNT = $RESPONSE_DATA['AMOUNT'];
                try {
                    $charge = \Stripe\Charge::create([
                        'amount' => ($AMOUNT * 100),
                        'currency' => 'usd',
                        'description' => $RESPONSE_DATA['NOTE'],
                        'source' => $STRIPE_TOKEN
                    ]);
                } catch (Exception $e) {
                    echo $e->getMessage();
                }
                pre_r($charge);
            }
        }

        db_perform('DOA_ENROLLMENT_PAYMENT', $RESPONSE_DATA, 'insert');
        $PK_ENROLLMENT_PAYMENT = $db->insert_ID();
        $ledger_record = $db->Execute("SELECT * FROM `DOA_ENROLLMENT_LEDGER` WHERE PK_ENROLLMENT_LEDGER =  '$PK_ENROLLMENT_LEDGER'");
        $LEDGER_DATA['TRANSACTION_TYPE'] = 'Payment';
        $LEDGER_DATA['ENROLLMENT_LEDGER_PARENT'] = $PK_ENROLLMENT_LEDGER;
        $LEDGER_DATA['PK_ENROLLMENT_MASTER'] = $RESPONSE_DATA['PK_ENROLLMENT_MASTER'];
        $LEDGER_DATA['PK_ENROLLMENT_BILLING'] = $RESPONSE_DATA['PK_ENROLLMENT_BILLING'];
        $LEDGER_DATA['DUE_DATE'] = date('Y-m-d');
        $LEDGER_DATA['BILLED_AMOUNT'] = 0.00;
        $LEDGER_DATA['PAID_AMOUNT'] = $ledger_record->fields['BILLED_AMOUNT'];
        $LEDGER_DATA['BALANCE'] = 0.00;
        $LEDGER_DATA['IS_PAID'] = 1;
        $LEDGER_DATA['PK_PAYMENT_TYPE'] = $RESPONSE_DATA['PK_PAYMENT_TYPE'];
        $LEDGER_DATA['PK_ENROLLMENT_PAYMENT'] = $PK_ENROLLMENT_PAYMENT;
        db_perform('DOA_ENROLLMENT_LEDGER', $LEDGER_DATA, 'insert');
        $LEDGER_UPDATE_DATA['IS_PAID'] = 1;
        db_perform('DOA_ENROLLMENT_LEDGER', $LEDGER_UPDATE_DATA, 'update', "PK_ENROLLMENT_LEDGER =  '$PK_ENROLLMENT_LEDGER'");
    }else{
        db_perform('DOA_ENROLLMENT_PAYMENT', $RESPONSE_DATA, 'update'," PK_ENROLLMENT_PAYMENT =  '$RESPONSE_DATA[PK_ENROLLMENT_PAYMENT]'");
        $PK_ENROLLMENT_PAYMENT = $RESPONSE_DATA['PK_ENROLLMENT_PAYMENT'];
    }
    echo $PK_ENROLLMENT_PAYMENT;
}*/

function saveProfileData($RESPONSE_DATA){
    error_reporting(0);
    global $db;
    $USER_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
    $USER_DATA['PK_ROLES'] = $RESPONSE_DATA['PK_ROLES'];
    $USER_DATA['FIRST_NAME'] = $RESPONSE_DATA['FIRST_NAME'];
    $USER_DATA['LAST_NAME'] = $RESPONSE_DATA['LAST_NAME'];
    $USER_DATA['EMAIL_ID'] = $RESPONSE_DATA['EMAIL_ID'];
    $USER_DATA['PK_LOCATION'] = $RESPONSE_DATA['PK_LOCATION'];
    $USER_DATA['USER_TITLE'] = $RESPONSE_DATA['USER_TITLE'];
    $USER_DATA['CREATE_LOGIN'] = isset($RESPONSE_DATA['CREATE_LOGIN'])?1:0;

    if ($USER_DATA['CREATE_LOGIN'] == 1) {
        if (!empty($RESPONSE_DATA['PASSWORD'])) {
            $USER_DATA['USER_NAME'] = $RESPONSE_DATA['USER_NAME'];
            $USER_DATA['PASSWORD'] = password_hash($RESPONSE_DATA['PASSWORD'], PASSWORD_DEFAULT);
        }
    }

    if($_FILES['USER_IMAGE']['name'] != ''){
        $extn 			= explode(".",$_FILES['USER_IMAGE']['name']);
        $iindex			= count($extn) - 1;
        $rand_string 	= time()."-".rand(100000,999999);
        $file11			= 'user_image_'.$_SESSION['PK_USER'].$rand_string.".".$extn[$iindex];
        $extension   	= strtolower($extn[$iindex]);

        if($extension == "gif" || $extension == "jpeg" || $extension == "pjpeg" || $extension == "png" || $extension == "jpg"){
            $image_path    = '../uploads/user_image/'.$file11;
            move_uploaded_file($_FILES['USER_IMAGE']['tmp_name'], $image_path);
            $USER_DATA['USER_IMAGE'] = $image_path;
        }
    }

    $USER_PROFILE_DATA['GENDER'] = $RESPONSE_DATA['GENDER'];
    $USER_PROFILE_DATA['DOB'] = date('Y-m-d', strtotime($RESPONSE_DATA['DOB']));
    $USER_PROFILE_DATA['ADDRESS'] = $RESPONSE_DATA['ADDRESS'];
    $USER_PROFILE_DATA['ADDRESS_1'] = $RESPONSE_DATA['ADDRESS_1'];
    $USER_PROFILE_DATA['PK_COUNTRY'] = $RESPONSE_DATA['PK_COUNTRY'];
    $USER_PROFILE_DATA['PK_STATES'] = $RESPONSE_DATA['PK_STATES'];
    $USER_PROFILE_DATA['CITY'] = $RESPONSE_DATA['CITY'];
    $USER_PROFILE_DATA['ZIP'] = $RESPONSE_DATA['ZIP'];
    $USER_PROFILE_DATA['PHONE'] = $RESPONSE_DATA['PHONE'];
    $USER_PROFILE_DATA['NOTES'] = $RESPONSE_DATA['NOTES'];

    if(empty($RESPONSE_DATA['PK_USER'])){
        $USER_DATA['ACTIVE'] = 1;
        $USER_DATA['CREATED_BY']  = $_SESSION['PK_USER'];
        $USER_DATA['CREATED_ON']  = date("Y-m-d H:i");
        db_perform('DOA_USERS', $USER_DATA, 'insert');
        $PK_USER = $db->insert_ID();
        $USER_PROFILE_DATA['PK_USER'] = $PK_USER;
        $USER_PROFILE_DATA['ACTIVE'] = 1;
        $USER_PROFILE_DATA['CREATED_BY']  = $_SESSION['PK_USER'];
        $USER_PROFILE_DATA['CREATED_ON']  = date("Y-m-d H:i");
        db_perform('DOA_USER_PROFILE', $USER_PROFILE_DATA, 'insert');
    }else{
        $USER_DATA['EDITED_BY']	= $_SESSION['PK_USER'];
        $USER_DATA['EDITED_ON'] = date("Y-m-d H:i");
        db_perform('DOA_USERS', $USER_DATA, 'update'," PK_USER =  '$RESPONSE_DATA[PK_USER]'");
        $USER_PROFILE_DATA['EDITED_BY']	= $_SESSION['PK_USER'];
        $USER_PROFILE_DATA['EDITED_ON'] = date("Y-m-d H:i");
        db_perform('DOA_USER_PROFILE', $USER_PROFILE_DATA, 'update'," PK_USER =  '$RESPONSE_DATA[PK_USER]'");
        $PK_USER = $RESPONSE_DATA['PK_USER'];
    }

    if ($RESPONSE_DATA['TYPE'] == 2) {
        $CUSTOMER_USER_DATA['PK_USER'] = $PK_USER;
        $CUSTOMER_USER_DATA['IS_PRIMARY'] = 1;
        $CUSTOMER_USER_DATA['FIRST_NAME'] = $RESPONSE_DATA['FIRST_NAME'];
        $CUSTOMER_USER_DATA['LAST_NAME'] = $RESPONSE_DATA['LAST_NAME'];
        $CUSTOMER_USER_DATA['PHONE'] = $RESPONSE_DATA['PHONE'];
        $CUSTOMER_USER_DATA['EMAIL'] = $RESPONSE_DATA['EMAIL'];
        $CUSTOMER_USER_DATA['GENDER'] = $RESPONSE_DATA['GENDER'];
        $CUSTOMER_USER_DATA['DOB'] = $RESPONSE_DATA['DOB'];
        $CUSTOMER_USER_DATA['CALL_PREFERENCE'] = $RESPONSE_DATA['CALL_PREFERENCE'];
        $CUSTOMER_USER_DATA['REMINDER_OPTION'] = implode(',', $RESPONSE_DATA['REMINDER_OPTION']);
        $CUSTOMER_USER_DATA['ATTENDING_WITH'] = $RESPONSE_DATA['ATTENDING_WITH'];
        $CUSTOMER_USER_DATA['PARTNER_FIRST_NAME'] = $RESPONSE_DATA['PARTNER_FIRST_NAME'];
        $CUSTOMER_USER_DATA['PARTNER_LAST_NAME'] = $RESPONSE_DATA['PARTNER_LAST_NAME'];
        $CUSTOMER_USER_DATA['PARTNER_GENDER'] = $RESPONSE_DATA['PARTNER_GENDER'];
        $CUSTOMER_USER_DATA['PARTNER_DOB'] = $RESPONSE_DATA['PARTNER_DOB'];

        $check_customer_data = $db->Execute("SELECT * FROM `DOA_CUSTOMER_DETAILS` WHERE `PK_USER` = '$PK_USER'");
        if ($check_customer_data->RecordCount() > 0) {
            db_perform('DOA_CUSTOMER_DETAILS', $CUSTOMER_USER_DATA, 'update', " PK_USER =  '$PK_USER'");
            $PK_CUSTOMER_DETAILS = $RESPONSE_DATA['PK_CUSTOMER_DETAILS'];
        } else {
            db_perform('DOA_CUSTOMER_DETAILS', $CUSTOMER_USER_DATA, 'insert');
            $PK_CUSTOMER_DETAILS = $db->insert_ID();
        }

        if (isset($RESPONSE_DATA['CUSTOMER_PHONE'])) {
            $db->Execute("DELETE FROM `DOA_CUSTOMER_PHONE` WHERE `PK_CUSTOMER_DETAILS` = '$PK_CUSTOMER_DETAILS'");
            for ($i = 0; $i < count($RESPONSE_DATA['CUSTOMER_PHONE']); $i++) {
                $CUSTOMER_PHONE['PK_CUSTOMER_DETAILS'] = $PK_CUSTOMER_DETAILS;
                $CUSTOMER_PHONE['PHONE'] = $RESPONSE_DATA['CUSTOMER_PHONE'][$i];
                db_perform('DOA_CUSTOMER_PHONE', $CUSTOMER_PHONE, 'insert');
            }
        }

        if (isset($RESPONSE_DATA['CUSTOMER_EMAIL'])) {
            $db->Execute("DELETE FROM `DOA_CUSTOMER_EMAIL` WHERE `PK_CUSTOMER_DETAILS` = '$PK_CUSTOMER_DETAILS'");
            for ($i = 0; $i < count($RESPONSE_DATA['CUSTOMER_EMAIL']); $i++) {
                $CUSTOMER_EMAIL['PK_CUSTOMER_DETAILS'] = $PK_CUSTOMER_DETAILS;
                $CUSTOMER_EMAIL['EMAIL'] = $RESPONSE_DATA['CUSTOMER_EMAIL'][$i];
                db_perform('DOA_CUSTOMER_EMAIL', $CUSTOMER_EMAIL, 'insert');
            }
        }

        if (isset($RESPONSE_DATA['CUSTOMER_SPECIAL_DATE'])) {
            $db->Execute("DELETE FROM `DOA_CUSTOMER_SPECIAL_DATE` WHERE `PK_CUSTOMER_DETAILS` = '$PK_CUSTOMER_DETAILS'");
            for ($i = 0; $i < count($RESPONSE_DATA['CUSTOMER_SPECIAL_DATE']); $i++) {
                $CUSTOMER_SPECIAL_DATE['PK_CUSTOMER_DETAILS'] = $PK_CUSTOMER_DETAILS;
                $CUSTOMER_SPECIAL_DATE['SPECIAL_DATE'] = $RESPONSE_DATA['CUSTOMER_SPECIAL_DATE'][$i];
                $CUSTOMER_SPECIAL_DATE['DATE_NAME'] = $RESPONSE_DATA['CUSTOMER_SPECIAL_DATE_NAME'][$i];
                db_perform('DOA_CUSTOMER_SPECIAL_DATE', $CUSTOMER_SPECIAL_DATE, 'insert');
            }
        }
    }

    if($RESPONSE_DATA['TYPE'] == 3 && isset($_POST['PK_USER_LOCATION'])){
        $PK_USER_LOCATION = $_POST['PK_USER_LOCATION'];
        $res = $db->Execute("DELETE FROM `DOA_USER_LOCATION` WHERE `PK_USER` = '$PK_USER'");
        for($i = 0; $i < count($PK_USER_LOCATION); $i++){
            $SERVICE_PROVIDER_LOCATION_DATA['PK_USER'] = $PK_USER;
            $SERVICE_PROVIDER_LOCATION_DATA['PK_LOCATION'] = $PK_USER_LOCATION[$i];
            db_perform('DOA_USER_LOCATION', $SERVICE_PROVIDER_LOCATION_DATA, 'insert');
        }
    }

    $return_data['PK_USER'] = $PK_USER;
    $return_data['PK_CUSTOMER_DETAILS'] = $PK_CUSTOMER_DETAILS;
    echo json_encode($return_data);
}

function saveLoginData($RESPONSE_DATA)
{
    global $db;
    $USER_DATA['USER_NAME'] = $RESPONSE_DATA['USER_NAME'];
    $USER_DATA['CREATE_LOGIN'] = 1;
    $USER_DATA['PASSWORD'] = password_hash($RESPONSE_DATA['PASSWORD'], PASSWORD_DEFAULT);
    $USER_DATA['ACTIVE'] = isset($RESPONSE_DATA['ACTIVE'])?$RESPONSE_DATA['ACTIVE']:1;
    $USER_DATA['EDITED_BY']	= $_SESSION['PK_USER'];
    $USER_DATA['EDITED_ON'] = date("Y-m-d H:i");
    db_perform('DOA_USERS', $USER_DATA, 'update'," PK_USER =  '$RESPONSE_DATA[PK_USER]'");
    $USER_PROFILE_DATA['ACTIVE'] = isset($RESPONSE_DATA['ACTIVE'])?$RESPONSE_DATA['ACTIVE']:1;
    $USER_PROFILE_DATA['EDITED_BY']	= $_SESSION['PK_USER'];
    $USER_PROFILE_DATA['EDITED_ON'] = date("Y-m-d H:i");
    db_perform('DOA_USER_PROFILE', $USER_PROFILE_DATA, 'update'," PK_USER =  '$RESPONSE_DATA[PK_USER]'");
}

function saveFamilyData($RESPONSE_DATA)
{
    global $db;
    if (!empty($RESPONSE_DATA['FAMILY_FIRST_NAME']) && $RESPONSE_DATA['PK_CUSTOMER_DETAILS'] > 0) {
        $db_account->Execute("DELETE FROM `DOA_CUSTOMER_DETAILS` WHERE `PK_CUSTOMER_PRIMARY` = '$RESPONSE_DATA[PK_CUSTOMER_DETAILS]'");
        for ($i = 0; $i < count($RESPONSE_DATA['FAMILY_FIRST_NAME']); $i++) {
            if ($RESPONSE_DATA['FAMILY_FIRST_NAME'][$i] != '') {
                $FAMILY_DATA['IS_PRIMARY'] = 0;
                $FAMILY_DATA['PK_USER'] = $RESPONSE_DATA['PK_USER'];
                $FAMILY_DATA['PK_CUSTOMER_PRIMARY'] = $RESPONSE_DATA['PK_CUSTOMER_DETAILS'];
                $FAMILY_DATA['FIRST_NAME'] = $RESPONSE_DATA['FAMILY_FIRST_NAME'][$i];
                $FAMILY_DATA['LAST_NAME'] = $RESPONSE_DATA['FAMILY_LAST_NAME'][$i];
                $FAMILY_DATA['PK_RELATIONSHIP'] = $RESPONSE_DATA['PK_RELATIONSHIP'][$i];
                $FAMILY_DATA['PHONE'] = $RESPONSE_DATA['FAMILY_PHONE'][$i];
                $FAMILY_DATA['EMAIL'] = $RESPONSE_DATA['FAMILY_EMAIL'][$i];
                $FAMILY_DATA['GENDER'] = $RESPONSE_DATA['FAMILY_GENDER'][$i];
                $FAMILY_DATA['DOB'] = $RESPONSE_DATA['FAMILY_DOB'][$i];
                db_perform_account('DOA_CUSTOMER_DETAILS', $FAMILY_DATA, 'insert');
                $PK_CUSTOMER_DETAILS = $db_account->insert_ID();

                if (isset($RESPONSE_DATA['FAMILY_SPECIAL_DATE'][$i])) {
                    $db->Execute("DELETE FROM `DOA_CUSTOMER_SPECIAL_DATE` WHERE `PK_CUSTOMER_DETAILS` = '$PK_CUSTOMER_DETAILS'");
                    for ($j = 0; $j < count($RESPONSE_DATA['FAMILY_SPECIAL_DATE'][$i]); $j++) {
                        $FAMILY_SPECIAL_DATE['PK_CUSTOMER_DETAILS'] = $PK_CUSTOMER_DETAILS;
                        $FAMILY_SPECIAL_DATE['SPECIAL_DATE'] = $RESPONSE_DATA['FAMILY_SPECIAL_DATE'][$i][$j];
                        $FAMILY_SPECIAL_DATE['DATE_NAME'] = $RESPONSE_DATA['FAMILY_SPECIAL_DATE_NAME'][$i][$j];
                        db_perform_account('DOA_CUSTOMER_SPECIAL_DATE', $FAMILY_SPECIAL_DATE, 'insert');
                    }
                }
            }
        }
    }
}

function saveInterestData($RESPONSE_DATA)
{
    global $db_account;
    if (isset($RESPONSE_DATA['PK_INTERESTS'])){
        $res = $db_account->Execute("DELETE FROM `DOA_CUSTOMER_INTEREST` WHERE `PK_USER` = '$RESPONSE_DATA[PK_USER]'");
        for($i = 0; $i < count($RESPONSE_DATA['PK_INTERESTS']); $i++){
            $USER_INTEREST_DATA['PK_USER'] = $RESPONSE_DATA['PK_USER'];
            $USER_INTEREST_DATA['PK_INTERESTS'] = $RESPONSE_DATA['PK_INTERESTS'][$i];
            db_perform_account('DOA_CUSTOMER_INTEREST', $USER_INTEREST_DATA, 'insert');
        }
    }
    if (isset($RESPONSE_DATA['WHAT_PROMPTED_YOU_TO_INQUIRE']) || isset($RESPONSE_DATA['PK_INQUIRY_METHOD']) || isset($RESPONSE_DATA['INQUIRY_TAKER_ID'])){
        $USER_INTEREST_OTHER_DATA['PK_USER'] = $RESPONSE_DATA['PK_USER'];
        $USER_INTEREST_OTHER_DATA['WHAT_PROMPTED_YOU_TO_INQUIRE'
        ] = $RESPONSE_DATA['WHAT_PROMPTED_YOU_TO_INQUIRE'];
        $USER_INTEREST_OTHER_DATA['PK_SKILL_LEVEL'] = $RESPONSE_DATA['PK_SKILL_LEVEL'];
        $USER_INTEREST_OTHER_DATA['PK_INQUIRY_METHOD'] = $RESPONSE_DATA['PK_INQUIRY_METHOD'];
        $USER_INTEREST_OTHER_DATA['INQUIRY_TAKER_ID'] = $RESPONSE_DATA['INQUIRY_TAKER_ID'];

        $check_interest_other_data = '';
        if ($RESPONSE_DATA['PK_USER']){
            $check_interest_other_data = $db_account->Execute("SELECT * FROM `DOA_CUSTOMER_INTEREST_OTHER_DATA` WHERE `PK_USER` = '$RESPONSE_DATA[PK_USER]'");
        }
        if ($check_interest_other_data != '' && $check_interest_other_data->RecordCount() > 0){
            db_perform_account('DOA_CUSTOMER_INTEREST_OTHER_DATA', $USER_INTEREST_OTHER_DATA, 'update'," PK_USER =  '$RESPONSE_DATA[PK_USER]'");
        }else{
            db_perform_account('DOA_CUSTOMER_INTEREST_OTHER_DATA', $USER_INTEREST_OTHER_DATA, 'insert');
        }
    }
}

function saveDocumentData($RESPONSE_DATA)
{
    global $db_account;
    if (isset($RESPONSE_DATA['DOCUMENT_NAME'])){
        $db_account->Execute("DELETE FROM `DOA_CUSTOMER_DOCUMENT` WHERE `PK_USER` = '$RESPONSE_DATA[PK_USER]'");
        for($i = 0; $i < count($RESPONSE_DATA['DOCUMENT_NAME']); $i++){
            $USER_DOCUMENT_DATA['PK_USER'] = $RESPONSE_DATA['PK_USER'];
            $USER_DOCUMENT_DATA['DOCUMENT_NAME'] = $RESPONSE_DATA['DOCUMENT_NAME'][$i];
            if(!empty($_FILES['FILE_PATH']['name'][$i])){
                $extn 			= explode(".",$_FILES['FILE_PATH']['name'][$i]);
                $iindex			= count($extn) - 1;
                $rand_string 	= time()."-".rand(100000,999999);
                $file11			= 'user_image_'.$_SESSION['PK_USER'].$rand_string.".".$extn[$iindex];
                $extension   	= strtolower($extn[$iindex]);

                $image_path    = '../uploads/user_doc/'.$file11;
                move_uploaded_file($_FILES['FILE_PATH']['tmp_name'][$i], $image_path);
                $USER_DOCUMENT_DATA['FILE_PATH'] = $image_path;
            } else {
                $USER_DOCUMENT_DATA['FILE_PATH'] = $RESPONSE_DATA['FILE_PATH_URL'][$i];
            }
            db_perform_account('DOA_CUSTOMER_DOCUMENT', $USER_DOCUMENT_DATA, 'insert');
        }
    }
}

function saveEngagementData($RESPONSE_DATA){
    global $db_account;
    $USER_RATE_ACTIVE['ACTIVE'] = 0;
    db_perform_account('DOA_USER_RATE', $USER_RATE_ACTIVE, 'update', " PK_USER = '$RESPONSE_DATA[PK_USER]'");
    $PK_RATE_TYPE = $RESPONSE_DATA['PK_RATE_TYPE'];
    $PK_RATE_TYPE_ACTIVE = $RESPONSE_DATA['PK_RATE_TYPE_ACTIVE'];
    $RATE = $RESPONSE_DATA['RATE'];
    for ($i = 0; $i < count($PK_RATE_TYPE); $i++) {
        if (isset($PK_RATE_TYPE[$i])) {
            $USER_RATE_DATA = [];
            $res = $db_account->Execute("SELECT * FROM `DOA_USER_RATE` WHERE PK_RATE_TYPE = '$PK_RATE_TYPE[$i]' AND PK_USER = '$RESPONSE_DATA[PK_USER]'");
            if ($res->RecordCount() == 0) {
                $USER_RATE_DATA['PK_USER'] = $RESPONSE_DATA['PK_USER'];
                $USER_RATE_DATA['PK_RATE_TYPE'] = $PK_RATE_TYPE[$i];
                $USER_RATE_DATA['RATE'] = $RATE[$i];
                $USER_RATE_DATA['ACTIVE'] = isset($PK_RATE_TYPE_ACTIVE[$i])?1:0;
                $USER_RATE_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
                $USER_RATE_DATA['CREATED_ON'] = date("Y-m-d H:i");
                db_perform_account('DOA_USER_RATE', $USER_RATE_DATA, 'insert');
            } else {
                $USER_RATE_DATA['RATE'] = $RATE[$i];
                $USER_RATE_DATA['ACTIVE'] = isset($PK_RATE_TYPE_ACTIVE[$i])?1:0;
                $USER_RATE_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
                $USER_RATE_DATA['EDITED_ON'] = date("Y-m-d H:i");
                db_perform_account('DOA_USER_RATE', $USER_RATE_DATA, 'update', " PK_RATE_TYPE = '$PK_RATE_TYPE[$i]' AND PK_USER = '$RESPONSE_DATA[PK_USER]'");
            }
        }
    }
}

function saveServiceData($RESPONSE_DATA){
    //pre_r($RESPONSE_DATA);
    global $db_account;
    $TYPE = $RESPONSE_DATA['TYPE'];
    unset($RESPONSE_DATA['TYPE']);
    $RESPONSE_DATA['PK_SERVICE_MASTER'] = implode(',', $RESPONSE_DATA['PK_SERVICE_MASTER']);
    $RESPONSE_DATA['MON_START_TIME'] = ($RESPONSE_DATA['MON_START_TIME'])?date('H:i', strtotime($RESPONSE_DATA['MON_START_TIME'])):'';
    $RESPONSE_DATA['MON_END_TIME'] = ($RESPONSE_DATA['MON_END_TIME'])?date('H:i', strtotime($RESPONSE_DATA['MON_END_TIME'])):'';
    $RESPONSE_DATA['TUE_START_TIME'] = ($RESPONSE_DATA['TUE_START_TIME'])?date('H:i', strtotime($RESPONSE_DATA['TUE_START_TIME'])):'';
    $RESPONSE_DATA['TUE_END_TIME'] = ($RESPONSE_DATA['TUE_END_TIME'])?date('H:i', strtotime($RESPONSE_DATA['TUE_END_TIME'])):'';
    $RESPONSE_DATA['WED_START_TIME'] = ($RESPONSE_DATA['WED_START_TIME'])?date('H:i', strtotime($RESPONSE_DATA['WED_START_TIME'])):'';
    $RESPONSE_DATA['WED_END_TIME'] = ($RESPONSE_DATA['WED_END_TIME'])?date('H:i', strtotime($RESPONSE_DATA['WED_END_TIME'])):'';
    $RESPONSE_DATA['THU_START_TIME'] = ($RESPONSE_DATA['THU_START_TIME'])?date('H:i', strtotime($RESPONSE_DATA['THU_START_TIME'])):'';
    $RESPONSE_DATA['THU_END_TIME'] = ($RESPONSE_DATA['THU_END_TIME'])?date('H:i', strtotime($RESPONSE_DATA['THU_END_TIME'])):'';
    $RESPONSE_DATA['FRI_START_TIME'] = ($RESPONSE_DATA['FRI_START_TIME'])?date('H:i', strtotime($RESPONSE_DATA['FRI_START_TIME'])):'';
    $RESPONSE_DATA['FRI_END_TIME'] = ($RESPONSE_DATA['FRI_END_TIME'])?date('H:i', strtotime($RESPONSE_DATA['FRI_END_TIME'])):'';
    $RESPONSE_DATA['SAT_START_TIME'] = ($RESPONSE_DATA['SAT_START_TIME'])?date('H:i', strtotime($RESPONSE_DATA['SAT_START_TIME'])):'';
    $RESPONSE_DATA['SAT_END_TIME'] = ($RESPONSE_DATA['SAT_END_TIME'])?date('H:i', strtotime($RESPONSE_DATA['SAT_END_TIME'])):'';
    $RESPONSE_DATA['SUN_START_TIME'] = ($RESPONSE_DATA['SUN_START_TIME'])?date('H:i', strtotime($RESPONSE_DATA['SUN_START_TIME'])):'';
    $RESPONSE_DATA['SUN_END_TIME'] = ($RESPONSE_DATA['SUN_END_TIME'])?date('H:i', strtotime($RESPONSE_DATA['SUN_END_TIME'])):'';
    $res = $db_account->Execute("SELECT * FROM `DOA_SERVICE_PROVIDER_SERVICES` WHERE PK_USER = '$RESPONSE_DATA[PK_USER]'");
    if ($res->RecordCount() == 0) {
        db_perform_account('DOA_SERVICE_PROVIDER_SERVICES', $RESPONSE_DATA, 'insert');
    }else{
        db_perform_account('DOA_SERVICE_PROVIDER_SERVICES', $RESPONSE_DATA, 'update', " PK_USER = $RESPONSE_DATA[PK_USER]");
    }
}

function saveAppointmentData($RESPONSE_DATA){
    global $db_account;
    unset($RESPONSE_DATA['TIME']);
    if (empty($RESPONSE_DATA['START_TIME']) || empty($RESPONSE_DATA['END_TIME'])){
        unset($RESPONSE_DATA['START_TIME']);
        unset($RESPONSE_DATA['END_TIME']);
    }
    if(empty($RESPONSE_DATA['PK_APPOINTMENT_MASTER'])){
        $RESPONSE_DATA['PK_APPOINTMENT_STATUS'] = 1;
        $RESPONSE_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
        $RESPONSE_DATA['ACTIVE'] = 1;
        $RESPONSE_DATA['CREATED_BY']  = $_SESSION['PK_USER'];
        $RESPONSE_DATA['CREATED_ON']  = date("Y-m-d H:i");
        db_perform_account('DOA_APPOINTMENT_MASTER', $RESPONSE_DATA, 'insert');
    }else{
        //$RESPONSE_DATA['ACTIVE'] = $_POST['ACTIVE'];
        if($_FILES['IMAGE']['name'] != ''){
            $extn 			= explode(".",$_FILES['IMAGE']['name']);
            $iindex			= count($extn) - 1;
            $rand_string 	= time()."-".rand(100000,999999);
            $file11			= 'appointment_image_'.$_SESSION['PK_USER'].$rand_string.".".$extn[$iindex];
            $extension   	= strtolower($extn[$iindex]);

            if($extension == "gif" || $extension == "jpeg" || $extension == "pjpeg" || $extension == "png" || $extension == "jpg"){
                $image_path    = '../uploads/appointment_image/'.$file11;
                move_uploaded_file($_FILES['IMAGE']['tmp_name'], $image_path);
                $RESPONSE_DATA['IMAGE'] = $image_path;
            }
        }
        $RESPONSE_DATA['EDITED_BY']	= $_SESSION['PK_USER'];
        $RESPONSE_DATA['EDITED_ON'] = date("Y-m-d H:i");
        db_perform_account('DOA_APPOINTMENT_MASTER', $RESPONSE_DATA, 'update'," PK_APPOINTMENT_MASTER =  '$RESPONSE_DATA[PK_APPOINTMENT_MASTER]'");
    }
}

function cancelAppointment($RESPONSE_DATA){
    global $db_account;
    $db_account->Execute("DELETE FROM `DOA_APPOINTMENT_MASTER` WHERE `PK_APPOINTMENT_MASTER` = '$RESPONSE_DATA[PK_APPOINTMENT_MASTER]'");
}

function completeAppointment($RESPONSE_DATA){
    global $db_account;
    $RESPONSE_DATA['EDITED_BY']	= $_SESSION['PK_USER'];
    $RESPONSE_DATA['EDITED_ON'] = date("Y-m-d H:i");
    $RESPONSE_DATA['STATUS'] = 'C';
    db_perform_account('DOA_APPOINTMENT_MASTER', $RESPONSE_DATA, 'update'," PK_APPOINTMENT_MASTER =  '$RESPONSE_DATA[PK_APPOINTMENT_MASTER]'");
}

function getServiceProviderCount($RESPONSE_DATA){
    global $db;
    global $db_account;
    global $master_database;
    $DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];
    $date = $RESPONSE_DATA['currentDate'];
    $all_service_provider = implode(',', $RESPONSE_DATA['all_service_provider']);
    $return_data = [];

    $event_count = $db_account->Execute("SELECT COUNT(DOA_EVENT.PK_EVENT) AS APPOINTMENT_COUNT FROM DOA_EVENT
                            LEFT JOIN DOA_EVENT_LOCATION ON DOA_EVENT.PK_EVENT = DOA_EVENT_LOCATION.PK_EVENT
                            WHERE SHARE_WITH_SERVICE_PROVIDERS = 1 AND ALL_DAY = 0 AND DOA_EVENT_LOCATION.PK_LOCATION IN ($DEFAULT_LOCATION_ID) AND `START_DATE` = '$date'");

    $all_service_provider_details = $db->Execute("SELECT PK_USER AS SERVICE_PROVIDER_ID, CONCAT(SERVICE_PROVIDER.FIRST_NAME, ' ', SERVICE_PROVIDER.LAST_NAME) AS SERVICE_PROVIDER_NAME FROM DOA_USERS AS SERVICE_PROVIDER WHERE PK_USER IN (".$all_service_provider.")");
    while (!$all_service_provider_details->EOF){
        $return_data[$all_service_provider_details->fields['SERVICE_PROVIDER_ID']]['APPOINTMENT_COUNT'] = ($event_count->RecordCount() > 0) ? $event_count->fields['APPOINTMENT_COUNT'] : 0; //+$service_provider_special_appointment_count->fields['SPECIAL_APPOINTMENT_COUNT']+$service_provider_group_class_count->fields['GROUP_CLASS_COUNT'];
        $return_data[$all_service_provider_details->fields['SERVICE_PROVIDER_ID']]['SERVICE_PROVIDER_ID'] = $all_service_provider_details->fields['SERVICE_PROVIDER_ID'];
        $return_data[$all_service_provider_details->fields['SERVICE_PROVIDER_ID']]['SERVICE_PROVIDER_NAME'] = $all_service_provider_details->fields['SERVICE_PROVIDER_NAME'];
        $all_service_provider_details->MoveNext();
    }

    $ALL_APPOINTMENT_QUERY = "SELECT COUNT(DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER) AS APPOINTMENT_COUNT, DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER AS SERVICE_PROVIDER_ID FROM DOA_APPOINTMENT_MASTER
                            INNER JOIN DOA_APPOINTMENT_CUSTOMER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER
                            LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER
                            WHERE DOA_APPOINTMENT_MASTER.PK_LOCATION IN ($DEFAULT_LOCATION_ID)
                            AND (EXISTS(SELECT DOA_APPOINTMENT_ENROLLMENT.PK_APPOINTMENT_MASTER FROM  DOA_APPOINTMENT_ENROLLMENT WHERE DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_ENROLLMENT.PK_APPOINTMENT_MASTER AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE = 'GROUP') OR DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE IN ('NORMAL', 'AD-HOC'))
                            AND DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS IN (1, 2, 3, 5, 7)
                            AND DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER IN (".$all_service_provider.") AND `DATE` = '$date' GROUP BY DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER";
    $service_provider_appointment_count = $db_account->Execute($ALL_APPOINTMENT_QUERY);
    while (!$service_provider_appointment_count->EOF){
        $return_data[$service_provider_appointment_count->fields['SERVICE_PROVIDER_ID']]['APPOINTMENT_COUNT'] = $return_data[$service_provider_appointment_count->fields['SERVICE_PROVIDER_ID']]['APPOINTMENT_COUNT']+$service_provider_appointment_count->fields['APPOINTMENT_COUNT'];
        $service_provider_appointment_count->MoveNext();
    }

    /*$ALL_SPECIAL_APPOINTMENT_QUERY = "SELECT COUNT(DOA_SPECIAL_APPOINTMENT.PK_SPECIAL_APPOINTMENT) AS APPOINTMENT_COUNT, DOA_SPECIAL_APPOINTMENT_USER.PK_USER AS SERVICE_PROVIDER_ID FROM DOA_SPECIAL_APPOINTMENT
                            LEFT JOIN DOA_SPECIAL_APPOINTMENT_USER ON DOA_SPECIAL_APPOINTMENT.PK_SPECIAL_APPOINTMENT = DOA_SPECIAL_APPOINTMENT_USER.PK_SPECIAL_APPOINTMENT
                            WHERE DOA_SPECIAL_APPOINTMENT.PK_APPOINTMENT_STATUS IN (1, 2, 3, 5, 7)
                            AND DOA_SPECIAL_APPOINTMENT_USER.PK_USER IN (".$all_service_provider.") AND `DATE` = '$date' GROUP BY DOA_SPECIAL_APPOINTMENT_USER.PK_USER";
    $service_provider_special_appointment_count = $db_account->Execute($ALL_SPECIAL_APPOINTMENT_QUERY);
    while (!$service_provider_special_appointment_count->EOF){
        $return_data[$service_provider_special_appointment_count->fields['SERVICE_PROVIDER_ID']]['APPOINTMENT_COUNT'] = $return_data[$service_provider_special_appointment_count->fields['SERVICE_PROVIDER_ID']]['APPOINTMENT_COUNT']+$service_provider_special_appointment_count->fields['APPOINTMENT_COUNT'];
        $service_provider_special_appointment_count->MoveNext();
    }*/

    echo json_encode(array_values($return_data));
}

function updateBillingDueDate($RESPONSE_DATA)
{
    global $db;
    global $db_account;

    $PK_ENROLLMENT_LEDGER = $RESPONSE_DATA['PK_ENROLLMENT_LEDGER'];
    $old_due_date = $RESPONSE_DATA['old_due_date'];
    $due_date = $RESPONSE_DATA['due_date'];
    $edit_type = $RESPONSE_DATA['edit_type'];

    $PASSWORD = $RESPONSE_DATA['due_date_verify_password'];
    $user_data = $db->Execute("SELECT PASSWORD FROM DOA_USERS LEFT JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER WHERE DOA_USER_MASTER.PK_USER_MASTER = ".$_SESSION['PK_USER_MASTER']);

    if (password_verify($PASSWORD, $user_data->fields['PASSWORD'])) {
        if ($edit_type == 'billing') {
            $LEDGER_DATA['DUE_DATE'] = date('Y-m-d', strtotime($due_date));
            db_perform_account('DOA_ENROLLMENT_LEDGER', $LEDGER_DATA, 'update', " PK_ENROLLMENT_LEDGER =  '$PK_ENROLLMENT_LEDGER'");

            $UPDATE_HISTORY_DATA['CLASS'] = 'enrollment_ledger';
            $UPDATE_HISTORY_DATA['PRIMARY_KEY'] = $PK_ENROLLMENT_LEDGER;
            $UPDATE_HISTORY_DATA['FIELD_NAME'] = 'DUE_DATE';
            $UPDATE_HISTORY_DATA['FROM_VALUE'] = $old_due_date;
            $UPDATE_HISTORY_DATA['TO_VALUE'] = $due_date;
            $UPDATE_HISTORY_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
            $UPDATE_HISTORY_DATA['EDITED_ON'] = date("Y-m-d H:i");
            db_perform_account('DOA_UPDATE_HISTORY', $UPDATE_HISTORY_DATA, 'insert');
        } else {
            $PAYMENT_DATA['PAYMENT_DATE'] = date('Y-m-d', strtotime($due_date));
            db_perform_account('DOA_ENROLLMENT_PAYMENT', $PAYMENT_DATA, 'update', " PK_ENROLLMENT_PAYMENT =  '$PK_ENROLLMENT_LEDGER'");

            $UPDATE_HISTORY_DATA['CLASS'] = 'enrollment_payment';
            $UPDATE_HISTORY_DATA['PRIMARY_KEY'] = $PK_ENROLLMENT_LEDGER;
            $UPDATE_HISTORY_DATA['FIELD_NAME'] = 'DUE_DATE';
            $UPDATE_HISTORY_DATA['FROM_VALUE'] = $old_due_date;
            $UPDATE_HISTORY_DATA['TO_VALUE'] = $due_date;
            $UPDATE_HISTORY_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
            $UPDATE_HISTORY_DATA['EDITED_ON'] = date("Y-m-d H:i");
            db_perform_account('DOA_UPDATE_HISTORY', $UPDATE_HISTORY_DATA, 'insert');
        }
        echo 1;
    } else {
        echo 0;
    }
}

function moveToWallet($RESPONSE_DATA)
{
    require_once("../../global/stripe-php-master/init.php");
    global $db;
    global $db_account;
    global $account_database;

    $PK_ENROLLMENT_PAYMENT = $RESPONSE_DATA['PK_ENROLLMENT_PAYMENT'];
    $PK_ENROLLMENT_MASTER = $RESPONSE_DATA['PK_ENROLLMENT_MASTER'];
    $PK_ENROLLMENT_LEDGER = $RESPONSE_DATA['PK_ENROLLMENT_LEDGER'];
    //$ENROLLMENT_LEDGER_PARENT = $RESPONSE_DATA['ENROLLMENT_LEDGER_PARENT'];
    $PK_USER_MASTER = $RESPONSE_DATA['PK_USER_MASTER'];
    $BALANCE = $RESPONSE_DATA['BALANCE'];
    $REFUND_AMOUNT = $RESPONSE_DATA['REFUND_AMOUNT'];
    $ENROLLMENT_TYPE = $RESPONSE_DATA['ENROLLMENT_TYPE'];
    $TRANSACTION_TYPE = $RESPONSE_DATA['TRANSACTION_TYPE'];
    $PK_PAYMENT_TYPE = ($TRANSACTION_TYPE == 'Move') ? 7 : $RESPONSE_DATA['PK_PAYMENT_TYPE'];
    $IS_ORIGINAL_RECEIPT = 0;

    $enrollment_data = $db_account->Execute("SELECT ENROLLMENT_NAME, ENROLLMENT_ID, MISC_ID, PK_ENROLLMENT_BILLING FROM DOA_ENROLLMENT_MASTER JOIN DOA_ENROLLMENT_BILLING ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = ".$PK_ENROLLMENT_MASTER);
    if(empty($enrollment_data->fields['ENROLLMENT_NAME'])){
        $enrollment_name = '';
    }else {
        $enrollment_name = $enrollment_data->fields['ENROLLMENT_NAME']." - ";
    }

    if(empty($enrollment_data->fields['ENROLLMENT_ID'])) {
        $enrollment_id = $enrollment_data->fields['MISC_ID'];
    } else {
        $enrollment_id = $enrollment_data->fields['ENROLLMENT_ID'];
    }

    $payment_data = $db_account->Execute("SELECT PK_PAYMENT_TYPE, RECEIPT_NUMBER, RECEIPT_PDF_LINK FROM DOA_ENROLLMENT_PAYMENT WHERE PK_ENROLLMENT_LEDGER=".$PK_ENROLLMENT_LEDGER);
    if ($PK_PAYMENT_TYPE == 7) {
        $TYPE = 'Move';

        $wallet_data = $db_account->Execute("SELECT * FROM DOA_CUSTOMER_WALLET WHERE PK_USER_MASTER = '$PK_USER_MASTER' ORDER BY PK_CUSTOMER_WALLET DESC LIMIT 1");
        if ($wallet_data->RecordCount() > 0) {
            $INSERT_DATA['CURRENT_BALANCE'] = $wallet_data->fields['CURRENT_BALANCE'] + $BALANCE;
        } else {
            $INSERT_DATA['CURRENT_BALANCE'] = $BALANCE;
        }
        $INSERT_DATA['PK_USER_MASTER'] = $PK_USER_MASTER;
        $INSERT_DATA['CREDIT'] = $BALANCE;
        $INSERT_DATA['BALANCE_LEFT'] = $BALANCE;
        $INSERT_DATA['DESCRIPTION'] = "Balance credited from enrollment " . $enrollment_name . $enrollment_id;
        $INSERT_DATA['RECEIPT_NUMBER'] = $payment_data->fields['RECEIPT_NUMBER'];
        $INSERT_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
        $INSERT_DATA['CREATED_ON'] = date("Y-m-d H:i");
        db_perform_account('DOA_CUSTOMER_WALLET', $INSERT_DATA, 'insert');

        $PAYMENT_DATA['RECEIPT_NUMBER'] = $payment_data->fields['RECEIPT_NUMBER'];
    } else {
        $BALANCE = $REFUND_AMOUNT;
        $TYPE = 'Refund';
        $IS_ORIGINAL_RECEIPT = 1;

        $receipt = $db_account->Execute("SELECT RECEIPT_NUMBER FROM DOA_ENROLLMENT_PAYMENT WHERE IS_ORIGINAL_RECEIPT = 1 ORDER BY CONVERT(RECEIPT_NUMBER, DECIMAL) DESC LIMIT 1");
        if ($receipt->RecordCount() > 0) {
            $lastSerialNumber = $receipt->fields['RECEIPT_NUMBER'];
            $RECEIPT_NUMBER = $lastSerialNumber + 1;
        } else {
            $RECEIPT_NUMBER = 1;
        }

        $PAYMENT_DATA['RECEIPT_NUMBER'] = $RECEIPT_NUMBER;
    }

    $enrollmentBillingData = $db_account->Execute("SELECT * FROM `DOA_ENROLLMENT_BILLING` WHERE `PK_ENROLLMENT_MASTER` = ".$PK_ENROLLMENT_MASTER);
    /*if ($ENROLLMENT_TYPE == 'active') {
        $LEDGER_DATA['TRANSACTION_TYPE'] = $TYPE;
        $LEDGER_DATA['ENROLLMENT_LEDGER_PARENT'] = $ENROLLMENT_LEDGER_PARENT;
        $LEDGER_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
        $LEDGER_DATA['PK_ENROLLMENT_BILLING'] = $enrollmentBillingData->fields['PK_ENROLLMENT_BILLING'];
        $LEDGER_DATA['PAID_AMOUNT'] = 0.00;
        $LEDGER_DATA['IS_PAID'] = 1;
        $LEDGER_DATA['DUE_DATE'] = date('Y-m-d');
        $LEDGER_DATA['BILLED_AMOUNT'] = 0.00;
        $LEDGER_DATA['BALANCE'] = $BALANCE;
        $LEDGER_DATA['STATUS'] = 'A';
        db_perform_account('DOA_ENROLLMENT_LEDGER', $LEDGER_DATA, 'insert');

        $PK_ENROLLMENT_LEDGER_NEW = $db_account->insert_ID();
    }*/

    if ($PK_ENROLLMENT_PAYMENT == 0) {
        $old_payment_data = $db_account->Execute("SELECT PAYMENT_INFO FROM DOA_ENROLLMENT_PAYMENT WHERE PK_PAYMENT_TYPE = '$PK_PAYMENT_TYPE' AND TYPE = 'Payment' AND IS_REFUNDED = 0 AND PAYMENT_STATUS = 'Success' AND PK_ENROLLMENT_MASTER = '$PK_ENROLLMENT_MASTER' ORDER BY AMOUNT DESC LIMIT 1");
    } else {
        $old_payment_data = $db_account->Execute("SELECT PAYMENT_INFO FROM DOA_ENROLLMENT_PAYMENT WHERE PK_PAYMENT_TYPE = '$PK_PAYMENT_TYPE' AND PK_ENROLLMENT_PAYMENT = '$PK_ENROLLMENT_PAYMENT'");
    }
    $PAYMENT_INFO = ($old_payment_data->RecordCount() > 0) ? $old_payment_data->fields['PAYMENT_INFO'] : $TYPE;;
    if ($PK_PAYMENT_TYPE == 1) {
        $payment_info = json_decode($old_payment_data->fields['PAYMENT_INFO']);
        if (isset($payment_info->CHARGE_ID)) {
            $account_data = $db->Execute("SELECT * FROM `DOA_ACCOUNT_MASTER` WHERE `PK_ACCOUNT_MASTER` = '$_SESSION[PK_ACCOUNT_MASTER]'");
            $SECRET_KEY = $account_data->fields['SECRET_KEY'];

            Stripe::setApiKey($SECRET_KEY);

            $transaction_id = $payment_info->CHARGE_ID;
            try {
                $refund = \Stripe\Refund::create([
                    'charge' => $transaction_id,
                    'amount' => $BALANCE * 100
                ]);
            } catch (Exception $e) {
                echo $e->getMessage(); die();
            }
            $PAYMENT_INFO_ARRAY = ['REFUND_ID' => $refund->id, 'LAST4' => $payment_info->LAST4];
            $PAYMENT_INFO = json_encode($PAYMENT_INFO_ARRAY);
        }
    }

    $PAYMENT_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
    $PAYMENT_DATA['PK_ENROLLMENT_BILLING'] = $enrollmentBillingData->fields['PK_ENROLLMENT_BILLING'];
    $PAYMENT_DATA['PK_PAYMENT_TYPE'] = $PK_PAYMENT_TYPE;
    $PAYMENT_DATA['AMOUNT'] = $BALANCE;
    $PAYMENT_DATA['PK_ENROLLMENT_LEDGER'] = $PK_ENROLLMENT_LEDGER;
    $PAYMENT_DATA['TYPE'] = $TYPE;
    $PAYMENT_DATA['NOTE'] = "Balance credited from enrollment " . $enrollment_name . $enrollment_id;
    $PAYMENT_DATA['PAYMENT_DATE'] = date('Y-m-d');
    $PAYMENT_DATA['PAYMENT_INFO'] = $PAYMENT_INFO;
    $PAYMENT_DATA['PAYMENT_STATUS'] = 'Success';
    $PAYMENT_DATA['IS_ORIGINAL_RECEIPT'] = $IS_ORIGINAL_RECEIPT;
    db_perform_account('DOA_ENROLLMENT_PAYMENT', $PAYMENT_DATA, 'insert');

    if ($ENROLLMENT_TYPE == 'active') {
        $UPDATE_PAYMENT_DATA['IS_REFUNDED'] = 1;
        db_perform_account('DOA_ENROLLMENT_PAYMENT', $UPDATE_PAYMENT_DATA, 'update'," PK_ENROLLMENT_PAYMENT =  '$PK_ENROLLMENT_PAYMENT'");

        $UPDATE_DATA['IS_PAID'] = 2;
        //$UPDATE_DATA['TRANSACTION_TYPE'] = $TRANSACTION_TYPE;
        db_perform_account('DOA_ENROLLMENT_LEDGER', $UPDATE_DATA, 'update'," PK_ENROLLMENT_LEDGER =  '$PK_ENROLLMENT_LEDGER'");

        $enrollment_billing_data = $db_account->Execute("SELECT `BILLED_AMOUNT`, `AMOUNT_REMAIN` FROM `DOA_ENROLLMENT_LEDGER` WHERE `PK_ENROLLMENT_LEDGER` = '$PK_ENROLLMENT_LEDGER'");
        $AMOUNT_REMAIN = $enrollment_billing_data->fields['AMOUNT_REMAIN'] + $BALANCE;
        if ($AMOUNT_REMAIN >= $enrollment_billing_data->fields['BILLED_AMOUNT']) {
            $PARENT_DATA['AMOUNT_REMAIN'] = 0;
            $PARENT_DATA['IS_PAID'] = 0;
        } else {
            $PARENT_DATA['IS_PAID'] = 0;
            $PARENT_DATA['AMOUNT_REMAIN'] = $AMOUNT_REMAIN;
        }
        db_perform_account('DOA_ENROLLMENT_LEDGER', $PARENT_DATA, 'update'," PK_ENROLLMENT_LEDGER =  '$PK_ENROLLMENT_LEDGER'");

        $enrollmentServiceData = $db_account->Execute("SELECT * FROM `DOA_ENROLLMENT_SERVICE` WHERE `PK_ENROLLMENT_MASTER` = ".$PK_ENROLLMENT_MASTER);
        $enrollmentBillingData = $db_account->Execute("SELECT * FROM `DOA_ENROLLMENT_BILLING` WHERE `PK_ENROLLMENT_MASTER` = ".$PK_ENROLLMENT_MASTER);
        $ACTUAL_AMOUNT = $enrollmentBillingData->fields['TOTAL_AMOUNT'];
        while (!$enrollmentServiceData->EOF) {
            $servicePercent = ($enrollmentServiceData->fields['FINAL_AMOUNT']*100)/$ACTUAL_AMOUNT;
            $serviceAmount = ($BALANCE*$servicePercent)/100;
            $ENROLLMENT_SERVICE_UPDATE_DATA['TOTAL_AMOUNT_PAID'] = $enrollmentServiceData->fields['TOTAL_AMOUNT_PAID'] - $serviceAmount;
            db_perform_account('DOA_ENROLLMENT_SERVICE', $ENROLLMENT_SERVICE_UPDATE_DATA, 'update'," PK_ENROLLMENT_SERVICE = ".$enrollmentServiceData->fields['PK_ENROLLMENT_SERVICE']);
            markAppointmentPaid($enrollmentServiceData->fields['PK_ENROLLMENT_SERVICE']);
            $enrollmentServiceData->MoveNext();
        }
    } else {
        $UPDATE_DATA['IS_PAID'] = 1;
        //$UPDATE_DATA['TRANSACTION_TYPE'] = $TYPE;
        db_perform_account('DOA_ENROLLMENT_LEDGER', $UPDATE_DATA, 'update'," PK_ENROLLMENT_LEDGER =  '$PK_ENROLLMENT_LEDGER'");
    }
    markEnrollmentComplete($PK_ENROLLMENT_MASTER);
    echo 1;
}