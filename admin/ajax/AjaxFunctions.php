<?php

use Dompdf\Dompdf;
use Mpdf\Mpdf;

require_once('../../global/config.php');

$RESPONSE_DATA = $_POST;
$FUNCTION_NAME = $RESPONSE_DATA['FUNCTION_NAME'];
unset($RESPONSE_DATA['FUNCTION_NAME']);
$FUNCTION_NAME($RESPONSE_DATA);

/*Saving Data from Service Code Page*/
function saveServiceInfoData($RESPONSE_DATA){
    error_reporting(0);
    global $db;
    $RESPONSE_DATA['SERVICE_NAME'] = $_POST['SERVICE_NAME'];
    $RESPONSE_DATA['IS_PACKAGE'] = isset($RESPONSE_DATA['IS_PACKAGE'])?1:0;
    $RESPONSE_DATA['DESCRIPTION'] = $_POST['DESCRIPTION'];
    $RESPONSE_DATA['ACTIVE'] = $_POST['ACTIVE'];
    $RESPONSE_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
    if(empty($RESPONSE_DATA['PK_SERVICE_MASTER'])){
        $RESPONSE_DATA['ACTIVE'] = 1;
        $RESPONSE_DATA['CREATED_BY']  = $_SESSION['PK_USER'];
        $RESPONSE_DATA['CREATED_ON']  = date("Y-m-d H:i");
        db_perform('DOA_SERVICE_MASTER', $RESPONSE_DATA, 'insert');
        $PK_SERVICE_MASTER = $db->insert_ID();
    }else{
        $RESPONSE_DATA['ACTIVE'] = $_POST['ACTIVE'];
        $RESPONSE_DATA['EDITED_BY']	= $_SESSION['PK_USER'];
        $RESPONSE_DATA['EDITED_ON'] = date("Y-m-d H:i");
        db_perform('DOA_SERVICE_MASTER', $RESPONSE_DATA, 'update'," PK_SERVICE_MASTER =  '$RESPONSE_DATA[PK_SERVICE_MASTER]'");
        $PK_SERVICE_MASTER = $RESPONSE_DATA['PK_SERVICE_MASTER'];
    }
    echo $PK_SERVICE_MASTER;
}

function saveServiceCodeData($RESPONSE_DATA){
    global $db;
    if (count($RESPONSE_DATA['SERVICE_CODE']) > 0) {
        $ALL_PK_SERVICE_CODE = $RESPONSE_DATA['ALL_PK_SERVICE_CODE'];
        $PK_SERVICE_CODE = $RESPONSE_DATA['PK_SERVICE_CODE'];
        $DELETED_CODE = array_diff($ALL_PK_SERVICE_CODE, $PK_SERVICE_CODE);
        $db->Execute("DELETE FROM `DOA_SERVICE_CODE` WHERE `PK_SERVICE_CODE` IN (".implode(',', $DELETED_CODE).")");
        for ($i = 0; $i < count($RESPONSE_DATA['SERVICE_CODE']); $i++) {
            $SERVICE_CODE_DATA['PK_SERVICE_MASTER'] = $RESPONSE_DATA['PK_SERVICE_MASTER'];
            $SERVICE_CODE_DATA['PK_FREQUENCY'] = $RESPONSE_DATA['PK_FREQUENCY'][$i];
            $SERVICE_CODE_DATA['PK_SCHEDULING_CODE'] = $RESPONSE_DATA['PK_SCHEDULING_CODE'][$i];
            $SERVICE_CODE_DATA['DURATION'] = $RESPONSE_DATA['DURATION'][$i];
            $SERVICE_CODE_DATA['IS_GROUP'] = $RESPONSE_DATA['IS_GROUP_'.$i];
            $SERVICE_CODE_DATA['CAPACITY'] = $RESPONSE_DATA['CAPACITY'][$i];
            $SERVICE_CODE_DATA['IS_CHARGEABLE'] = $RESPONSE_DATA['IS_CHARGEABLE_'.$i];
            $SERVICE_CODE_DATA['PRICE'] = $RESPONSE_DATA['PRICE'][$i];
            $SERVICE_CODE_DATA['NUMBER_OF_SESSIONS'] = $RESPONSE_DATA['NUMBER_OF_SESSIONS'][$i];
            $SERVICE_CODE_DATA['SERVICE_CODE'] = $RESPONSE_DATA['SERVICE_CODE'][$i];
            $SERVICE_CODE_DATA['DESCRIPTION'] = $RESPONSE_DATA['SERVICE_CODE_DESCRIPTION'][$i];
            if ($RESPONSE_DATA['PK_SERVICE_CODE'][$i] > 0){
                db_perform('DOA_SERVICE_CODE', $SERVICE_CODE_DATA, 'update', "PK_SERVICE_CODE = ".$RESPONSE_DATA['PK_SERVICE_CODE'][$i]);
                $PK_SERVICE_CODE = $RESPONSE_DATA['PK_SERVICE_CODE'][$i];
            } else {
                db_perform('DOA_SERVICE_CODE', $SERVICE_CODE_DATA, 'insert');
                $PK_SERVICE_CODE = $db->insert_ID();
            }

            $db->Execute("DELETE FROM `DOA_SERVICE_SCHEDULING_CODE` WHERE `PK_SERVICE_CODE` = '$PK_SERVICE_CODE'");
            for ($j = 0; $j < count($RESPONSE_DATA['PK_SCHEDULING_CODE'][$i]); $j++) {
                $SERVICE_BOOKING_CODE_DATA['PK_SERVICE_MASTER'] = $RESPONSE_DATA['PK_SERVICE_MASTER'];
                $SERVICE_BOOKING_CODE_DATA['PK_SERVICE_CODE'] = $PK_SERVICE_CODE;
                $SERVICE_BOOKING_CODE_DATA['PK_SCHEDULING_CODE'] = $RESPONSE_DATA['PK_SCHEDULING_CODE'][$i][$j];
                db_perform('DOA_SERVICE_SCHEDULING_CODE', $SERVICE_BOOKING_CODE_DATA, 'insert');
            }
        }
    }

    if (isset($RESPONSE_DATA['PK_USER']) && count($RESPONSE_DATA['PK_USER']) > 0) {
        $db->Execute("DELETE FROM `DOA_SERVICE_PROVIDER_SERVICE_NEW` WHERE `PK_SERVICE_MASTER` = '$RESPONSE_DATA[PK_SERVICE_MASTER]'");
        for ($i = 0; $i < count($RESPONSE_DATA['PK_USER']); $i++) {
            $SERVICE_PROVIDER_DATA['PK_USER'] = $RESPONSE_DATA['PK_USER'][$i];
            $SERVICE_PROVIDER_DATA['PK_SERVICE_MASTER'] = $RESPONSE_DATA['PK_SERVICE_MASTER'];
            db_perform('DOA_SERVICE_PROVIDER_SERVICE_NEW', $SERVICE_PROVIDER_DATA, 'insert');
        }
    }
}

/*Saving Data from Enrollment Page*/

function saveEnrollmentData($RESPONSE_DATA){
    error_reporting(0);
    global $db;
    $ENROLLMENT_MASTER_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
    $ENROLLMENT_MASTER_DATA['PK_USER_MASTER'] = $RESPONSE_DATA['PK_USER_MASTER'];
    $ENROLLMENT_MASTER_DATA['PK_LOCATION'] = $RESPONSE_DATA['PK_LOCATION'];
    $ENROLLMENT_MASTER_DATA['PK_AGREEMENT_TYPE'] = $RESPONSE_DATA['PK_AGREEMENT_TYPE'];
    $ENROLLMENT_MASTER_DATA['PK_DOCUMENT_LIBRARY'] = $RESPONSE_DATA['PK_DOCUMENT_LIBRARY'];
    $ENROLLMENT_MASTER_DATA['ENROLLMENT_BY_ID'] = $RESPONSE_DATA['ENROLLMENT_BY_ID'];

    $document_library_data = $db->Execute("SELECT * FROM `DOA_DOCUMENT_LIBRARY` WHERE `PK_DOCUMENT_LIBRARY` = '$RESPONSE_DATA[PK_DOCUMENT_LIBRARY]'");
    $user_data = $db->Execute("SELECT DOA_USERS.FIRST_NAME, DOA_USERS.LAST_NAME, DOA_USERS.PHONE, DOA_USER_PROFILE.ADDRESS, DOA_USER_PROFILE.CITY, DOA_USER_PROFILE.ZIP FROM DOA_USERS INNER JOIN DOA_USER_PROFILE ON DOA_USERS.PK_USER = DOA_USER_PROFILE.PK_USER INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER WHERE DOA_USER_MASTER.PK_USER_MASTER = ".$RESPONSE_DATA['PK_USER_MASTER']);
    $html_template = $document_library_data->fields['DOCUMENT_TEMPLATE'];
    $html_template = str_replace('{FULL_NAME}', $user_data->fields['FIRST_NAME']." ".$user_data->fields['LAST_NAME'], $html_template);
    $html_template = str_replace('{STREET_ADD}', $user_data->fields['ADDRESS'], $html_template);
    $html_template = str_replace('{CITY}', $user_data->fields['CITY'], $html_template);
    $html_template = str_replace('{ZIP}', $user_data->fields['ZIP'], $html_template);
    $html_template = str_replace('{CELL_PHONE}', $user_data->fields['PHONE'], $html_template);
    $ENROLLMENT_MASTER_DATA['AGREEMENT_PDF_LINK'] = generatePdf($html_template);

    if(empty($RESPONSE_DATA['PK_ENROLLMENT_MASTER'])){
        $account_data = $db->Execute("SELECT ENROLLMENT_ID_CHAR, ENROLLMENT_ID_NUM FROM `DOA_ACCOUNT_MASTER` WHERE `PK_ACCOUNT_MASTER` = '$_SESSION[PK_ACCOUNT_MASTER]'");
        $enrollment_data = $db->Execute("SELECT ENROLLMENT_ID FROM `DOA_ENROLLMENT_MASTER` WHERE `PK_ACCOUNT_MASTER` = '$_SESSION[PK_ACCOUNT_MASTER]' ORDER BY PK_ENROLLMENT_MASTER DESC LIMIT 1");
        if ($enrollment_data->RecordCount() > 0){
            $last_enrollment_id = str_replace($account_data->fields['ENROLLMENT_ID_CHAR'], '', $enrollment_data->fields['ENROLLMENT_ID']) ;
            $ENROLLMENT_MASTER_DATA['ENROLLMENT_ID'] = $account_data->fields['ENROLLMENT_ID_CHAR'].(intval($last_enrollment_id)+1);
        }else{
            $ENROLLMENT_MASTER_DATA['ENROLLMENT_ID'] = $account_data->fields['ENROLLMENT_ID_CHAR'].$account_data->fields['ENROLLMENT_ID_NUM'];
        }
        $ENROLLMENT_MASTER_DATA['ACTIVE'] = 1;
        $ENROLLMENT_MASTER_DATA['CREATED_BY']  = $_SESSION['PK_USER'];
        $ENROLLMENT_MASTER_DATA['CREATED_ON']  = date("Y-m-d H:i");
        db_perform('DOA_ENROLLMENT_MASTER', $ENROLLMENT_MASTER_DATA, 'insert');
        $PK_ENROLLMENT_MASTER = $db->insert_ID();
        createUpdateHistory('enrollment', $PK_ENROLLMENT_MASTER,'DOA_ENROLLMENT_MASTER', 'PK_ENROLLMENT_MASTER', $PK_ENROLLMENT_MASTER, $ENROLLMENT_MASTER_DATA, 'insert');
    }else{
        $ENROLLMENT_MASTER_DATA['ACTIVE'] = $RESPONSE_DATA['ACTIVE'] ?? 0;
        $ENROLLMENT_MASTER_DATA['EDITED_BY']	= $_SESSION['PK_USER'];
        $ENROLLMENT_MASTER_DATA['EDITED_ON'] = date("Y-m-d H:i");
        createUpdateHistory('enrollment', $RESPONSE_DATA['PK_ENROLLMENT_MASTER'],'DOA_ENROLLMENT_MASTER', 'PK_ENROLLMENT_MASTER', $RESPONSE_DATA['PK_ENROLLMENT_MASTER'], $ENROLLMENT_MASTER_DATA, 'update');
        db_perform('DOA_ENROLLMENT_MASTER', $ENROLLMENT_MASTER_DATA, 'update'," PK_ENROLLMENT_MASTER =  '$RESPONSE_DATA[PK_ENROLLMENT_MASTER]'");
        $PK_ENROLLMENT_MASTER = $RESPONSE_DATA['PK_ENROLLMENT_MASTER'];
    }

    $total = 0;
    if (isset($RESPONSE_DATA['PK_SERVICE_MASTER']) && count($RESPONSE_DATA['PK_SERVICE_MASTER']) > 0){
        $db->Execute("DELETE FROM `DOA_ENROLLMENT_SERVICE` WHERE `PK_ENROLLMENT_MASTER` = '$PK_ENROLLMENT_MASTER'");
        /*if ($RESPONSE_DATA['IS_PACKAGE'] == 1 ) {
            for ($i = 0; $i < count($RESPONSE_DATA['PK_SERVICE_CODE']); $i++) {
                if (!empty($RESPONSE_DATA['PK_SERVICE_CODE'][$i])) {
                    $ENROLLMENT_SERVICE_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
                    $ENROLLMENT_SERVICE_DATA['PK_SERVICE_MASTER'] = $RESPONSE_DATA['PK_SERVICE_MASTER'][0];
                    $ENROLLMENT_SERVICE_DATA['PK_SERVICE_CODE'] = $RESPONSE_DATA['PK_SERVICE_CODE'][$i];
                    $ENROLLMENT_SERVICE_DATA['SERVICE_DETAILS'] = $RESPONSE_DATA['SERVICE_DETAILS'][$i];
                    $ENROLLMENT_SERVICE_DATA['FREQUENCY'] = $RESPONSE_DATA['FREQUENCY'][$i];
                    $ENROLLMENT_SERVICE_DATA['NUMBER_OF_SESSION'] = $RESPONSE_DATA['NUMBER_OF_SESSION'][$i];
                    $ENROLLMENT_SERVICE_DATA['PRICE_PER_SESSION'] = $RESPONSE_DATA['PRICE_PER_SESSION'][$i];
                    $ENROLLMENT_SERVICE_DATA['TOTAL'] = $RESPONSE_DATA['TOTAL'][$i];
                    //pre_r($ENROLLMENT_SERVICE_DATA);
                    db_perform('DOA_ENROLLMENT_SERVICE', $ENROLLMENT_SERVICE_DATA, 'insert');
                    $PK_ENROLLMENT_SERVICE = $db->insert_ID();
                    createUpdateHistory('enrollment', $PK_ENROLLMENT_MASTER, 'DOA_ENROLLMENT_SERVICE', 'PK_ENROLLMENT_SERVICE', $PK_ENROLLMENT_SERVICE, $ENROLLMENT_SERVICE_DATA, 'insert');
                    $total += $RESPONSE_DATA['TOTAL'][$i];
                }
            }
        } else {*/
            for ($i = 0; $i < count($RESPONSE_DATA['PK_SERVICE_MASTER']); $i++) {
                $ENROLLMENT_SERVICE_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
                $ENROLLMENT_SERVICE_DATA['PK_SERVICE_MASTER'] = $RESPONSE_DATA['PK_SERVICE_MASTER'][$i];
                $ENROLLMENT_SERVICE_DATA['PK_SERVICE_CODE'] = $RESPONSE_DATA['PK_SERVICE_CODE'][$i];
                $ENROLLMENT_SERVICE_DATA['SERVICE_DETAILS'] = $RESPONSE_DATA['SERVICE_DETAILS'][$i];
                $ENROLLMENT_SERVICE_DATA['FREQUENCY'] = $RESPONSE_DATA['FREQUENCY'][$i];
                $ENROLLMENT_SERVICE_DATA['NUMBER_OF_SESSION'] = $RESPONSE_DATA['NUMBER_OF_SESSION'][$i];
                $ENROLLMENT_SERVICE_DATA['PRICE_PER_SESSION'] = $RESPONSE_DATA['PRICE_PER_SESSION'][$i];
                $ENROLLMENT_SERVICE_DATA['TOTAL'] = $RESPONSE_DATA['TOTAL'][$i];
                db_perform('DOA_ENROLLMENT_SERVICE', $ENROLLMENT_SERVICE_DATA, 'insert');
                $PK_ENROLLMENT_SERVICE = $db->insert_ID();
                createUpdateHistory('enrollment', $PK_ENROLLMENT_MASTER, 'DOA_ENROLLMENT_SERVICE', 'PK_ENROLLMENT_SERVICE', $PK_ENROLLMENT_SERVICE, $ENROLLMENT_SERVICE_DATA, 'insert');
                $total += $RESPONSE_DATA['TOTAL'][$i];
            }
        //}
    }

    $return_data['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
    $return_data['TOTAL_AMOUNT'] = $total;
    echo json_encode($return_data);
}

function generatePdf($html){
    require_once('../../global/vendor/autoload.php');

    $mpdf = new Mpdf();
    $mpdf->WriteHTML($html);

    $file_name = "enrollment_pdf_".time().".pdf";
    $mpdf->Output("../../uploads/enrollment_pdf/".$file_name, 'F');

    return $file_name;
}


function saveEnrollmentBillingData($RESPONSE_DATA){
    //error_reporting(0);
    global $db;
    $PK_ENROLLMENT_SERVICE = $RESPONSE_DATA['PK_ENROLLMENT_SERVICE'];
    $FLEXIBLE_PAYMENT_DATE = isset($RESPONSE_DATA['FLEXIBLE_PAYMENT_DATE'])?$RESPONSE_DATA['FLEXIBLE_PAYMENT_DATE']:[];
    $FLEXIBLE_PAYMENT_AMOUNT = isset($RESPONSE_DATA['FLEXIBLE_PAYMENT_AMOUNT'])?$RESPONSE_DATA['FLEXIBLE_PAYMENT_AMOUNT']:[];
    unset($RESPONSE_DATA['PK_ENROLLMENT_SERVICE']);
    unset($RESPONSE_DATA['FLEXIBLE_PAYMENT_DATE']);
    unset($RESPONSE_DATA['FLEXIBLE_PAYMENT_AMOUNT']);
    $RESPONSE_DATA['BILLING_DATE'] = date('Y-m-d', strtotime($RESPONSE_DATA['BILLING_DATE']));
    $RESPONSE_DATA['FIRST_DUE_DATE'] = date('Y-m-d', strtotime($RESPONSE_DATA['FIRST_DUE_DATE']));
    $PK_ENROLLMENT_LEDGER = 0;
    for ($i = 0; $i < count($PK_ENROLLMENT_SERVICE); $i++) {
        $ENROLLMENT_SERVICE_DATA['DISCOUNT'] = $RESPONSE_DATA['DISCOUNT'][$i];
        $ENROLLMENT_SERVICE_DATA['DISCOUNT_TYPE'] = $RESPONSE_DATA['DISCOUNT_TYPE'][$i];
        $ENROLLMENT_SERVICE_DATA['FINAL_AMOUNT'] = $RESPONSE_DATA['FINAL_AMOUNT'][$i];
        db_perform('DOA_ENROLLMENT_SERVICE', $ENROLLMENT_SERVICE_DATA, 'update', " PK_ENROLLMENT_SERVICE =  '$PK_ENROLLMENT_SERVICE[$i]'");
    }
    if(empty($RESPONSE_DATA['PK_ENROLLMENT_BILLING'])){
        $ENROLLMENT_BILLING_DATA['PK_ENROLLMENT_MASTER'] = $RESPONSE_DATA['PK_ENROLLMENT_MASTER'];
        $ENROLLMENT_BILLING_DATA['BILLING_REF'] = $RESPONSE_DATA['BILLING_REF'];
        $ENROLLMENT_BILLING_DATA['BILLING_DATE'] = $RESPONSE_DATA['BILLING_DATE'];
        $ENROLLMENT_BILLING_DATA['DOWN_PAYMENT'] = $RESPONSE_DATA['DOWN_PAYMENT'];
        $ENROLLMENT_BILLING_DATA['BALANCE_PAYABLE'] = $RESPONSE_DATA['BALANCE_PAYABLE'];
        $ENROLLMENT_BILLING_DATA['TOTAL_AMOUNT'] = $RESPONSE_DATA['TOTAL_AMOUNT'];
        $ENROLLMENT_BILLING_DATA['PAYMENT_METHOD'] = $RESPONSE_DATA['PAYMENT_METHOD'];
        $ENROLLMENT_BILLING_DATA['PAYMENT_TERM'] = $RESPONSE_DATA['PAYMENT_TERM'];
        $ENROLLMENT_BILLING_DATA['NUMBER_OF_PAYMENT'] = $RESPONSE_DATA['NUMBER_OF_PAYMENT'];
        if ($RESPONSE_DATA['PK_SERVICE_CLASS'] == 1) {
            $ENROLLMENT_BILLING_DATA['FIRST_DUE_DATE'] = date('Y-m-d', strtotime($RESPONSE_DATA['MEMBERSHIP_PAYMENT_DATE']));
            $ENROLLMENT_BILLING_DATA['INSTALLMENT_AMOUNT'] = $RESPONSE_DATA['MEMBERSHIP_PAYMENT_AMOUNT'];
        } else {
            $ENROLLMENT_BILLING_DATA['FIRST_DUE_DATE'] = $RESPONSE_DATA['FIRST_DUE_DATE'];
            $ENROLLMENT_BILLING_DATA['INSTALLMENT_AMOUNT'] = $RESPONSE_DATA['INSTALLMENT_AMOUNT'];
        }

        db_perform('DOA_ENROLLMENT_BILLING', $ENROLLMENT_BILLING_DATA, 'insert');
        $PK_ENROLLMENT_BILLING = $db->insert_ID();
        if ($RESPONSE_DATA['PK_SERVICE_CLASS'] == 1){
            $LEDGER_DATA['TRANSACTION_TYPE'] = 'Billing';
            $LEDGER_DATA['ENROLLMENT_LEDGER_PARENT'] = 0;
            $LEDGER_DATA['PK_ENROLLMENT_MASTER'] = $RESPONSE_DATA['PK_ENROLLMENT_MASTER'];
            $LEDGER_DATA['PK_ENROLLMENT_BILLING'] = $PK_ENROLLMENT_BILLING;
            $LEDGER_DATA['PAID_AMOUNT'] = 0.00;
            $LEDGER_DATA['IS_PAID'] = 0;
            $LEDGER_DATA['DUE_DATE'] = date('Y-m-d', strtotime($RESPONSE_DATA['MEMBERSHIP_PAYMENT_DATE']));
            $LEDGER_DATA['BILLED_AMOUNT'] = $RESPONSE_DATA['BALANCE_PAYABLE'];
            $LEDGER_DATA['BALANCE'] = $RESPONSE_DATA['MEMBERSHIP_PAYMENT_AMOUNT'];
            db_perform('DOA_ENROLLMENT_LEDGER', $LEDGER_DATA, 'insert');
            $PK_ENROLLMENT_LEDGER = $db->insert_ID();
        }else {
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
                db_perform('DOA_ENROLLMENT_LEDGER', $LEDGER_DATA, 'insert');
                $PK_ENROLLMENT_LEDGER = $db->insert_ID();
            } elseif ($RESPONSE_DATA['PAYMENT_METHOD'] == 'Payment Plans') {
                if ($RESPONSE_DATA['DOWN_PAYMENT'] > 0) {
                    $LEDGER_DATA['DUE_DATE'] = date('Y-m-d');
                    $LEDGER_DATA['BILLED_AMOUNT'] = $RESPONSE_DATA['DOWN_PAYMENT'];
                    $LEDGER_DATA['BALANCE'] = $RESPONSE_DATA['DOWN_PAYMENT'];
                    db_perform('DOA_ENROLLMENT_LEDGER', $LEDGER_DATA, 'insert');
                    $PK_ENROLLMENT_LEDGER = $db->insert_ID();
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
                    db_perform('DOA_ENROLLMENT_LEDGER', $LEDGER_DATA, 'insert');
                    if ($RESPONSE_DATA['DOWN_PAYMENT'] <= 0 && $i == 0) {
                        $PK_ENROLLMENT_LEDGER = $db->insert_ID();
                    }
                }
            } elseif ($RESPONSE_DATA['PAYMENT_METHOD'] == 'Flexible Payments') {
                if ($RESPONSE_DATA['DOWN_PAYMENT'] > 0) {
                    $LEDGER_DATA['DUE_DATE'] = date('Y-m-d');
                    $LEDGER_DATA['BILLED_AMOUNT'] = $RESPONSE_DATA['DOWN_PAYMENT'];
                    $LEDGER_DATA['BALANCE'] = $RESPONSE_DATA['DOWN_PAYMENT'];
                    db_perform('DOA_ENROLLMENT_LEDGER', $LEDGER_DATA, 'insert');
                    $PK_ENROLLMENT_LEDGER = $db->insert_ID();
                }
                $BALANCE = $RESPONSE_DATA['DOWN_PAYMENT'];
                for ($i = 0; $i < count($FLEXIBLE_PAYMENT_DATE); $i++) {
                    $LEDGER_DATA['DUE_DATE'] = date('Y-m-d', strtotime($FLEXIBLE_PAYMENT_DATE[$i]));
                    $LEDGER_DATA['BILLED_AMOUNT'] = $FLEXIBLE_PAYMENT_AMOUNT[$i];
                    $BALANCE = ($BALANCE + $FLEXIBLE_PAYMENT_AMOUNT[$i]);
                    $LEDGER_DATA['BALANCE'] = $BALANCE;
                    db_perform('DOA_ENROLLMENT_LEDGER', $LEDGER_DATA, 'insert');
                    if ($RESPONSE_DATA['DOWN_PAYMENT'] <= 0 && $i == 0) {
                        $PK_ENROLLMENT_LEDGER = $db->insert_ID();
                    }
                }
            }
        }
    }else{
        db_perform('DOA_ENROLLMENT_BILLING', $RESPONSE_DATA, 'update'," PK_ENROLLMENT_BILLING =  '$RESPONSE_DATA[PK_ENROLLMENT_BILLING]'");
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

    if (in_array(4, $RESPONSE_DATA['PK_ROLES'])) {
        $USER_DATA['PK_ACCOUNT_MASTER'] = 0;
    }else{
        $USER_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
    }

    //$USER_DATA['PK_ROLES'] = $RESPONSE_DATA['PK_ROLES'];
    $USER_DATA['FIRST_NAME'] = $RESPONSE_DATA['FIRST_NAME'];
    $USER_DATA['LAST_NAME'] = $RESPONSE_DATA['LAST_NAME'];
    if (isset($RESPONSE_DATA['CUSTOMER_ID'])) {
        $USER_DATA['USER_ID'] = $RESPONSE_DATA['CUSTOMER_ID'];
    }
    $USER_DATA['IS_COUNSELLOR'] = isset($RESPONSE_DATA['IS_COUNSELLOR'])?1:0;
    $USER_DATA['EMAIL_ID'] = $RESPONSE_DATA['EMAIL_ID'];
    $USER_DATA['PHONE'] = $RESPONSE_DATA['PHONE'];
    $USER_DATA['PK_LOCATION'] = $RESPONSE_DATA['PK_LOCATION'];
    $USER_DATA['USER_TITLE'] = $RESPONSE_DATA['USER_TITLE'];
    $USER_DATA['CREATE_LOGIN'] = isset($RESPONSE_DATA['CREATE_LOGIN'])?1:0;

    if ($USER_DATA['CREATE_LOGIN'] == 1) {
        if (!empty($RESPONSE_DATA['PASSWORD'])) {
            $USER_DATA['USER_ID'] = $RESPONSE_DATA['USER_ID'];
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
            $upload_path   = '../../uploads/user_image/'.$file11;
            $image_path    = '../uploads/user_image/'.$file11;
            move_uploaded_file($_FILES['USER_IMAGE']['tmp_name'], $upload_path);
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
    $USER_PROFILE_DATA['NOTES'] = $RESPONSE_DATA['NOTES'];

    $PK_USER_MASTER = $RESPONSE_DATA['PK_USER_MASTER'];;
    $PK_CUSTOMER_DETAILS = 0;
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
        if (in_array(4, $RESPONSE_DATA['PK_ROLES'])) {
            $USER_MASTER_DATE['PK_USER'] = $PK_USER;
            $USER_MASTER_DATE['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
            $USER_MASTER_DATE['CREATED_BY'] = $_SESSION['PK_USER'];
            $USER_MASTER_DATE['CREATED_ON'] = date("Y-m-d H:i");
            db_perform('DOA_USER_MASTER', $USER_MASTER_DATE, 'insert');
            $PK_USER_MASTER = $db->insert_ID();
        }
    }else{
        $USER_DATA['ACTIVE']	= $RESPONSE_DATA['ACTIVE'];
        $USER_DATA['EDITED_BY']	= $_SESSION['PK_USER'];
        $USER_DATA['EDITED_ON'] = date("Y-m-d H:i");
        db_perform('DOA_USERS', $USER_DATA, 'update'," PK_USER =  '$RESPONSE_DATA[PK_USER]'");
        $USER_PROFILE_DATA['ACTIVE']	= $RESPONSE_DATA['ACTIVE'];
        $USER_PROFILE_DATA['EDITED_BY']	= $_SESSION['PK_USER'];
        $USER_PROFILE_DATA['EDITED_ON'] = date("Y-m-d H:i");
        db_perform('DOA_USER_PROFILE', $USER_PROFILE_DATA, 'update'," PK_USER =  '$RESPONSE_DATA[PK_USER]'");
        $PK_USER = $RESPONSE_DATA['PK_USER'];
        if (in_array(4, $RESPONSE_DATA['PK_ROLES'])) {
            $USER_MASTER_DATE['PRIMARY_LOCATION_ID'] = $RESPONSE_DATA['PRIMARY_LOCATION_ID'];
            db_perform('DOA_USER_MASTER', $USER_MASTER_DATE, 'update', " PK_USER_MASTER = '$RESPONSE_DATA[PK_USER_MASTER]'");

        }
    }

    if ($RESPONSE_DATA['TYPE'] == 2) {
        $CUSTOMER_USER_DATA['PK_USER_MASTER'] = $PK_USER_MASTER;
        $CUSTOMER_USER_DATA['IS_PRIMARY'] = 1;
        $CUSTOMER_USER_DATA['FIRST_NAME'] = $RESPONSE_DATA['FIRST_NAME'];
        $CUSTOMER_USER_DATA['LAST_NAME'] = $RESPONSE_DATA['LAST_NAME'];
        $CUSTOMER_USER_DATA['PHONE'] = $RESPONSE_DATA['PHONE'];
        $CUSTOMER_USER_DATA['EMAIL'] = $RESPONSE_DATA['EMAIL'];
        $CUSTOMER_USER_DATA['GENDER'] = $RESPONSE_DATA['GENDER'];
        $CUSTOMER_USER_DATA['DOB'] = date('Y-m-d', strtotime($RESPONSE_DATA['DOB']));
        $CUSTOMER_USER_DATA['CALL_PREFERENCE'] = $RESPONSE_DATA['CALL_PREFERENCE'];
        $CUSTOMER_USER_DATA['REMINDER_OPTION'] = isset($RESPONSE_DATA['REMINDER_OPTION'])?implode(',', $RESPONSE_DATA['REMINDER_OPTION']):'';
        $CUSTOMER_USER_DATA['ATTENDING_WITH'] = $RESPONSE_DATA['ATTENDING_WITH'];
        $CUSTOMER_USER_DATA['PARTNER_FIRST_NAME'] = $RESPONSE_DATA['PARTNER_FIRST_NAME'];
        $CUSTOMER_USER_DATA['PARTNER_LAST_NAME'] = $RESPONSE_DATA['PARTNER_LAST_NAME'];
        $CUSTOMER_USER_DATA['PARTNER_GENDER'] = $RESPONSE_DATA['PARTNER_GENDER'];
        $CUSTOMER_USER_DATA['PARTNER_DOB'] = date('Y-m-d', strtotime($RESPONSE_DATA['PARTNER_DOB']));

        $check_customer_data = $db->Execute("SELECT * FROM `DOA_CUSTOMER_DETAILS` WHERE `PK_USER_MASTER` = '$PK_USER_MASTER'");
        if ($check_customer_data->RecordCount() > 0) {
            db_perform('DOA_CUSTOMER_DETAILS', $CUSTOMER_USER_DATA, 'update', " PK_USER_MASTER =  '$PK_USER_MASTER'");
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
            $db->Execute("DELETE FROM `DOA_SPECIAL_DATE` WHERE `PK_CUSTOMER_DETAILS` = '$PK_CUSTOMER_DETAILS'");
            for ($i = 0; $i < count($RESPONSE_DATA['CUSTOMER_SPECIAL_DATE']); $i++) {
                $CUSTOMER_SPECIAL_DATE['PK_CUSTOMER_DETAILS'] = $PK_CUSTOMER_DETAILS;
                $CUSTOMER_SPECIAL_DATE['SPECIAL_DATE'] = $RESPONSE_DATA['CUSTOMER_SPECIAL_DATE'][$i];
                $CUSTOMER_SPECIAL_DATE['DATE_NAME'] = $RESPONSE_DATA['CUSTOMER_SPECIAL_DATE_NAME'][$i];
                db_perform('DOA_SPECIAL_DATE', $CUSTOMER_SPECIAL_DATE, 'insert');
            }
        }
    }

    if (isset($RESPONSE_DATA['PK_ROLES'])) {
        $db->Execute("DELETE FROM `DOA_USER_ROLES` WHERE `PK_USER` = '$PK_USER'");
        $PK_ROLE = $RESPONSE_DATA['PK_ROLES'];
        for($i = 0; $i < count($PK_ROLE); $i++){
            $USER_ROLE_DATA['PK_USER'] = $PK_USER;
            $USER_ROLE_DATA['PK_ROLES'] = $PK_ROLE[$i];
            db_perform('DOA_USER_ROLES', $USER_ROLE_DATA, 'insert');
        }
    }

    $db->Execute("DELETE FROM `DOA_USER_LOCATION` WHERE `PK_USER` = '$PK_USER'");
    if(isset($RESPONSE_DATA['PK_USER_LOCATION'])){
        $PK_USER_LOCATION = $RESPONSE_DATA['PK_USER_LOCATION'];
        for($i = 0; $i < count($PK_USER_LOCATION); $i++){
            $CUSTOMER_LOCATION_DATA['PK_USER'] = $PK_USER;
            $CUSTOMER_LOCATION_DATA['PK_LOCATION'] = $PK_USER_LOCATION[$i];
            db_perform('DOA_USER_LOCATION', $CUSTOMER_LOCATION_DATA, 'insert');
        }
    }

    $return_data['PK_USER'] = $PK_USER;
    $return_data['PK_USER_MASTER'] = $PK_USER_MASTER;
    $return_data['PK_CUSTOMER_DETAILS'] = $PK_CUSTOMER_DETAILS;
    echo json_encode($return_data);

}




/*function saveLoginData($RESPONSE_DATA)
{
    global $db;
    $USER_DATA['USER_ID'] = $RESPONSE_DATA['USER_ID'];
    $USER_DATA['CREATE_LOGIN'] = 1;
    $USER_DATA['PASSWORD'] = password_hash($RESPONSE_DATA['PASSWORD'], PASSWORD_DEFAULT);
    $USER_DATA['CAN_EDIT_ENROLLMENT'] = isset($RESPONSE_DATA['CAN_EDIT_ENROLLMENT'])?$RESPONSE_DATA['CAN_EDIT_ENROLLMENT']:0;
    $USER_DATA['ACTIVE'] = isset($RESPONSE_DATA['ACTIVE'])?$RESPONSE_DATA['ACTIVE']:1;
    $USER_DATA['EDITED_BY']	= $_SESSION['PK_USER'];
    $USER_DATA['EDITED_ON'] = date("Y-m-d H:i");
    db_perform('DOA_USERS', $USER_DATA, 'update'," PK_USER =  '$RESPONSE_DATA[PK_USER]'");
    $USER_PROFILE_DATA['ACTIVE'] = isset($RESPONSE_DATA['ACTIVE'])?$RESPONSE_DATA['ACTIVE']:1;
    $USER_PROFILE_DATA['EDITED_BY']	= $_SESSION['PK_USER'];
    $USER_PROFILE_DATA['EDITED_ON'] = date("Y-m-d H:i");
    db_perform('DOA_USER_PROFILE', $USER_PROFILE_DATA, 'update'," PK_USER =  '$RESPONSE_DATA[PK_USER]'");

    echo $RESPONSE_DATA['PK_USER'];
}*/


function saveLoginData($RESPONSE_DATA)
{

    global $db;
    $USER_DATA['USER_ID'] = $RESPONSE_DATA['USER_ID'];
    $USER_DATA['CREATE_LOGIN'] = 1;

    if ((!empty($RESPONSE_DATA['PASSWORD']) && !empty($RESPONSE_DATA['CONFIRM_PASSWORD'])) && ($RESPONSE_DATA['PASSWORD'] == $RESPONSE_DATA['CONFIRM_PASSWORD'])) {
        $USER_DATA['PASSWORD'] = password_hash($RESPONSE_DATA['PASSWORD'], PASSWORD_DEFAULT);
    }

    $USER_DATA['CAN_EDIT_ENROLLMENT'] = isset($RESPONSE_DATA['CAN_EDIT_ENROLLMENT'])?$RESPONSE_DATA['CAN_EDIT_ENROLLMENT']:0;
    $USER_DATA['ACTIVE'] = isset($RESPONSE_DATA['ACTIVE'])?$RESPONSE_DATA['ACTIVE']:1;
    $USER_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
    $USER_DATA['EDITED_ON'] = date("Y-m-d H:i");


    //$user_info = $db->Execute("SELECT * FROM `DOA_USERS` WHERE `PK_USER` = '$RESPONSE_DATA[PK_USER]'");
    // focusbiz code
    if(isset($RESPONSE_DATA['TICKET_SYSTEM_ACCESS']) && $RESPONSE_DATA['TICKET_SYSTEM_ACCESS'] == 1) {
        $USER_DATA['TICKET_SYSTEM_ACCESS'] = $RESPONSE_DATA['TICKET_SYSTEM_ACCESS'];

        $res = $db->Execute("SELECT * FROM DOA_USERS WHERE PK_USER = '$RESPONSE_DATA[PK_USER]' ");

        //$hash      = $res->fields['PASSWORD'];
        //$PASSWORD  = crypt($res->fields['PASSWORD'], $hash);
        $PASSWORD  = $RESPONSE_DATA['PASSWORD'];

        $user = array();
        $user['FIRST_NAME'] = $res->fields['FIRST_NAME'];
        $user['LAST_NAME']  = $res->fields['LAST_NAME'];
        $user['EMAIL_ID']   = $res->fields['EMAIL_ID'];
        $user['ACTIVE']     = $res->fields['ACTIVE'];

        if($res->fields['ACCESS_TOKEN'] == "") {
            $user['USER_ID']    = $res->fields['USER_ID'];
            $user['PASSWORD']   = $PASSWORD;
        } else {
            $user['ACCESS_TOKEN']   = $res->fields['ACCESS_TOKEN'];
        }

        if($_SERVER['HTTP_HOST'] == 'localhost' ) {
            /*$URL    = "http://localhost/focusbiz/API/V1/user";
            $APIKEY = "7QXJtdkZEHcR4bgOxPkona3PnJ02O8";*/
            $URL    = "https://focusbiz.com/API/V1/user";
            $APIKEY = "JJmQm6AvehQzjP0nVRgEfWgCarxWYo";
        } else {
            $URL    = "https://focusbiz.com/API/V1/user";
            $APIKEY = "JJmQm6AvehQzjP0nVRgEfWgCarxWYo";
        }

        $json = json_encode($user);
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_SSL_VERIFYHOST => '0',
            CURLOPT_SSL_VERIFYPEER => '0',
            CURLOPT_URL => $URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_HTTPHEADER => array(
                "APIKEY: ".$APIKEY
            ),
        ));

        $response   = curl_exec($curl);
        $err        = curl_error($curl);

        curl_close($curl);

        if($err) {
            echo "cURL Error #:" . $err;exit;
        } else {
            $response1 = json_decode($response);

            $USER = array();
            $USER['ACCESS_TOKEN'] = $response1->ACCESS_TOKEN;
            db_perform('DOA_USERS', $USER, 'update', " PK_USER = '$RESPONSE_DATA[PK_USER]' ");
        }
    } else {
        $USER_DATA['TICKET_SYSTEM_ACCESS'] = 0;

        $res = $db->Execute("SELECT * FROM DOA_USERS WHERE PK_USER = '$RESPONSE_DATA[PK_USER]' ");
        if($res->fields['ACCESS_TOKEN'] != "") {
            $user = array();
            $user['FIRST_NAME']     = $res->fields['FIRST_NAME'];
            $user['LAST_NAME']      = $res->fields['LAST_NAME'];
            $user['EMAIL_ID']       = $res->fields['EMAIL'];
            $user['ACTIVE']         = 0;
            $user['ACCESS_TOKEN']   = $res->fields['ACCESS_TOKEN'];

            if($_SERVER['HTTP_HOST'] == 'localhost' ) {
                /*$URL    = "http://localhost/focusbiz/API/V1/user";
                $APIKEY = "7QXJtdkZEHcR4bgOxPkona3PnJ02O8";*/
                $URL    = "https://focusbiz.com/API/V1/user";
                $APIKEY = "JJmQm6AvehQzjP0nVRgEfWgCarxWYo";
            } else {
                $URL    = "https://focusbiz.com/API/V1/user";
                $APIKEY = "JJmQm6AvehQzjP0nVRgEfWgCarxWYo";
            }

            $json = json_encode($user);
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_SSL_VERIFYHOST => '0',
                CURLOPT_SSL_VERIFYPEER => '0',
                CURLOPT_URL => $URL,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_POST => 1,
                CURLOPT_POSTFIELDS => $json,
                CURLOPT_HTTPHEADER => array(
                    "APIKEY: ".$APIKEY
                ),
            ));

            $response   = curl_exec($curl);
            $err        = curl_error($curl);

            curl_close($curl);

            if($err) {
                echo "cURL Error #:" . $err;
            } else {
                $response1 = json_decode($response);
            }
        }
    }
    // focusbiz code end


    db_perform('DOA_USERS', $USER_DATA, 'update'," PK_USER =  '$RESPONSE_DATA[PK_USER]'");
    $USER_PROFILE_DATA['ACTIVE'] = isset($RESPONSE_DATA['ACTIVE'])?$RESPONSE_DATA['ACTIVE']:1;
    $USER_PROFILE_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
    $USER_PROFILE_DATA['EDITED_ON'] = date("Y-m-d H:i");
    db_perform('DOA_USER_PROFILE', $USER_PROFILE_DATA, 'update'," PK_USER =  '$RESPONSE_DATA[PK_USER]'");

    echo $RESPONSE_DATA['PK_USER'];
}


function saveFamilyData($RESPONSE_DATA)
{
    global $db;
    if (!empty($RESPONSE_DATA['FAMILY_FIRST_NAME']) && $RESPONSE_DATA['PK_CUSTOMER_DETAILS'] > 0) {
        $db->Execute("DELETE FROM `DOA_CUSTOMER_DETAILS` WHERE `PK_CUSTOMER_PRIMARY` = '$RESPONSE_DATA[PK_CUSTOMER_DETAILS]'");
        for ($i = 0; $i < count($RESPONSE_DATA['FAMILY_FIRST_NAME']); $i++) {
            if ($RESPONSE_DATA['FAMILY_FIRST_NAME'][$i] != '') {
                $FAMILY_DATA['IS_PRIMARY'] = 0;
                //$FAMILY_DATA['PK_USER'] = $RESPONSE_DATA['PK_USER'];
                $FAMILY_DATA['PK_USER_MASTER'] = $RESPONSE_DATA['PK_USER_MASTER'];
                $FAMILY_DATA['PK_CUSTOMER_PRIMARY'] = $RESPONSE_DATA['PK_CUSTOMER_DETAILS'];
                $FAMILY_DATA['FIRST_NAME'] = $RESPONSE_DATA['FAMILY_FIRST_NAME'][$i];
                $FAMILY_DATA['LAST_NAME'] = $RESPONSE_DATA['FAMILY_LAST_NAME'][$i];
                $FAMILY_DATA['PK_RELATIONSHIP'] = $RESPONSE_DATA['PK_RELATIONSHIP'][$i];
                $FAMILY_DATA['PHONE'] = $RESPONSE_DATA['FAMILY_PHONE'][$i];
                $FAMILY_DATA['EMAIL'] = $RESPONSE_DATA['FAMILY_EMAIL'][$i];
                $FAMILY_DATA['GENDER'] = $RESPONSE_DATA['FAMILY_GENDER'][$i];
                $FAMILY_DATA['DOB'] = date('Y-m-d', strtotime($RESPONSE_DATA['FAMILY_DOB'][$i]));
                db_perform('DOA_CUSTOMER_DETAILS', $FAMILY_DATA, 'insert');
                $PK_CUSTOMER_DETAILS = $db->insert_ID();

                if (isset($RESPONSE_DATA['FAMILY_SPECIAL_DATE'][$i])) {
                    $db->Execute("DELETE FROM `DOA_SPECIAL_DATE` WHERE `PK_CUSTOMER_DETAILS` = '$PK_CUSTOMER_DETAILS'");
                    for ($j = 0; $j < count($RESPONSE_DATA['FAMILY_SPECIAL_DATE'][$i]); $j++) {
                        $FAMILY_SPECIAL_DATE['PK_CUSTOMER_DETAILS'] = $PK_CUSTOMER_DETAILS;
                        $FAMILY_SPECIAL_DATE['SPECIAL_DATE'] = $RESPONSE_DATA['FAMILY_SPECIAL_DATE'][$i][$j];
                        $FAMILY_SPECIAL_DATE['DATE_NAME'] = $RESPONSE_DATA['FAMILY_SPECIAL_DATE_NAME'][$i][$j];
                        db_perform('DOA_SPECIAL_DATE', $FAMILY_SPECIAL_DATE, 'insert');
                    }
                }
            }
        }
    }
}

function saveInterestData($RESPONSE_DATA)
{
    global $db;
    if (isset($RESPONSE_DATA['PK_INTERESTS'])){
        $res = $db->Execute("DELETE FROM `DOA_USER_INTEREST` WHERE `PK_USER` = '$RESPONSE_DATA[PK_USER]'");
        for($i = 0; $i < count($RESPONSE_DATA['PK_INTERESTS']); $i++){
            //$USER_INTEREST_DATA['PK_USER'] = $RESPONSE_DATA['PK_USER'];
            $USER_INTEREST_DATA['PK_USER_MASTER'] = $RESPONSE_DATA['PK_USER_MASTER'];
            $USER_INTEREST_DATA['PK_INTERESTS'] = $RESPONSE_DATA['PK_INTERESTS'][$i];
            db_perform('DOA_USER_INTEREST', $USER_INTEREST_DATA, 'insert');
        }
    }
    if (isset($RESPONSE_DATA['WHAT_PROMPTED_YOU_TO_INQUIRE']) || isset($RESPONSE_DATA['PK_INQUIRY_METHOD']) || isset($RESPONSE_DATA['INQUIRY_TAKER_ID'])){
        //$USER_INTEREST_OTHER_DATA['PK_USER'] = $RESPONSE_DATA['PK_USER'];
        $USER_INTEREST_OTHER_DATA['PK_USER_MASTER'] = $RESPONSE_DATA['PK_USER_MASTER'];
        $USER_INTEREST_OTHER_DATA['WHAT_PROMPTED_YOU_TO_INQUIRE'] = $RESPONSE_DATA['WHAT_PROMPTED_YOU_TO_INQUIRE'];
        $USER_INTEREST_OTHER_DATA['PK_SKILL_LEVEL'] = $RESPONSE_DATA['PK_SKILL_LEVEL'];
        $USER_INTEREST_OTHER_DATA['PK_INQUIRY_METHOD'] = $RESPONSE_DATA['PK_INQUIRY_METHOD'];
        $USER_INTEREST_OTHER_DATA['INQUIRY_TAKER_ID'] = $RESPONSE_DATA['INQUIRY_TAKER_ID'];

        $check_interest_other_data = '';
        if ($RESPONSE_DATA['PK_USER_MASTER']){
            $check_interest_other_data = $db->Execute("SELECT * FROM `DOA_USER_INTEREST_OTHER_DATA` WHERE `PK_USER_MASTER` = '$RESPONSE_DATA[PK_USER_MASTER]'");
        }
        if ($check_interest_other_data != '' && $check_interest_other_data->RecordCount() > 0){
            db_perform('DOA_USER_INTEREST_OTHER_DATA', $USER_INTEREST_OTHER_DATA, 'update'," PK_USER_MASTER =  '$RESPONSE_DATA[PK_USER_MASTER]'");
        }else{
            db_perform('DOA_USER_INTEREST_OTHER_DATA', $USER_INTEREST_OTHER_DATA, 'insert');
        }
    }
}

function saveDocumentData($RESPONSE_DATA)
{
    global $db;
    if (isset($RESPONSE_DATA['DOCUMENT_NAME'])){
        $db->Execute("DELETE FROM `DOA_CUSTOMER_DOCUMENT` WHERE `PK_USER_MASTER` = '$RESPONSE_DATA[PK_USER_MASTER]'");
        for($i = 0; $i < count($RESPONSE_DATA['DOCUMENT_NAME']); $i++){
            $USER_DOCUMENT_DATA['PK_USER_MASTER'] = $RESPONSE_DATA['PK_USER_MASTER'];
            $USER_DOCUMENT_DATA['DOCUMENT_NAME'] = $RESPONSE_DATA['DOCUMENT_NAME'][$i];
            if(!empty($_FILES['FILE_PATH']['name'][$i])){
                $extn 			= explode(".",$_FILES['FILE_PATH']['name'][$i]);
                $iindex			= count($extn) - 1;
                $rand_string 	= time()."-".rand(100000,999999);
                $file11			= 'user_image_'.$_SESSION['PK_USER'].$rand_string.".".$extn[$iindex];
                $extension   	= strtolower($extn[$iindex]);

                $upload_path    = '../../uploads/user_doc/'.$file11;
                $image_path    = '../uploads/user_doc/'.$file11;
                move_uploaded_file($_FILES['FILE_PATH']['tmp_name'][$i], $upload_path);
                $USER_DOCUMENT_DATA['FILE_PATH'] = $image_path;
            } else {
                $USER_DOCUMENT_DATA['FILE_PATH'] = $RESPONSE_DATA['FILE_PATH_URL'][$i];
            }
            db_perform('DOA_CUSTOMER_DOCUMENT', $USER_DOCUMENT_DATA, 'insert');
        }
    }
}

function saveUserDocumentData($RESPONSE_DATA)
{
    global $db;
    if (isset($RESPONSE_DATA['DOCUMENT_NAME'])){
        $db->Execute("DELETE FROM `DOA_USER_DOCUMENT` WHERE `PK_USER` = '$RESPONSE_DATA[PK_USER]'");
        for($i = 0; $i < count($RESPONSE_DATA['DOCUMENT_NAME']); $i++){
            $USER_DOCUMENT_DATA['PK_USER'] = $RESPONSE_DATA['PK_USER'];
            $USER_DOCUMENT_DATA['DOCUMENT_NAME'] = $RESPONSE_DATA['DOCUMENT_NAME'][$i];
            if(!empty($_FILES['FILE_PATH']['name'][$i])){
                $extn 			= explode(".",$_FILES['FILE_PATH']['name'][$i]);
                $iindex			= count($extn) - 1;
                $rand_string 	= time()."-".rand(100000,999999);
                $file11			= 'user_image_'.$_SESSION['PK_USER'].$rand_string.".".$extn[$iindex];
                $extension   	= strtolower($extn[$iindex]);

                $upload_path    = '../../uploads/user_doc/'.$file11;
                $image_path    = '../uploads/user_doc/'.$file11;
                move_uploaded_file($_FILES['FILE_PATH']['tmp_name'][$i], $upload_path);
                $USER_DOCUMENT_DATA['FILE_PATH'] = $image_path;
            } else {
                $USER_DOCUMENT_DATA['FILE_PATH'] = $RESPONSE_DATA['FILE_PATH_URL'][$i];
            }
            db_perform('DOA_USER_DOCUMENT', $USER_DOCUMENT_DATA, 'insert');
        }
    }
}



function saveEngagementData($RESPONSE_DATA){
    global $db;
    $USER_RATE_ACTIVE['ACTIVE'] = 0;
    db_perform('DOA_USER_RATE', $USER_RATE_ACTIVE, 'update', " PK_USER = '$RESPONSE_DATA[PK_USER]'");
    $PK_RATE_TYPE = $RESPONSE_DATA['PK_RATE_TYPE'];
    $PK_RATE_TYPE_ACTIVE = $RESPONSE_DATA['PK_RATE_TYPE_ACTIVE'];
    $RATE = $RESPONSE_DATA['RATE'];
    for ($i = 0; $i < count($PK_RATE_TYPE); $i++) {
        if (isset($PK_RATE_TYPE[$i])) {
            $USER_RATE_DATA = [];
            $res = $db->Execute("SELECT * FROM `DOA_USER_RATE` WHERE PK_RATE_TYPE = '$PK_RATE_TYPE[$i]' AND PK_USER = '$RESPONSE_DATA[PK_USER]'");
            if ($res->RecordCount() == 0) {
                $USER_RATE_DATA['PK_USER'] = $RESPONSE_DATA['PK_USER'];
                $USER_RATE_DATA['PK_RATE_TYPE'] = $PK_RATE_TYPE[$i];
                $USER_RATE_DATA['RATE'] = $RATE[$i];
                $USER_RATE_DATA['ACTIVE'] = isset($PK_RATE_TYPE_ACTIVE[$i])?1:0;
                $USER_RATE_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
                $USER_RATE_DATA['CREATED_ON'] = date("Y-m-d H:i");
                db_perform('DOA_USER_RATE', $USER_RATE_DATA, 'insert');
            } else {
                $USER_RATE_DATA['RATE'] = $RATE[$i];
                $USER_RATE_DATA['ACTIVE'] = isset($PK_RATE_TYPE_ACTIVE[$i])?1:0;
                $USER_RATE_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
                $USER_RATE_DATA['EDITED_ON'] = date("Y-m-d H:i");
                db_perform('DOA_USER_RATE', $USER_RATE_DATA, 'update', " PK_RATE_TYPE = '$PK_RATE_TYPE[$i]' AND PK_USER = '$RESPONSE_DATA[PK_USER]'");
            }
        }
    }

    $PK_SERVICE_MASTER = $RESPONSE_DATA['PK_SERVICE_MASTER'];
    $COMMISSION_AMOUNT = $RESPONSE_DATA['COMMISSION_AMOUNT'];
    if (count($PK_SERVICE_MASTER) > 0){
        $db->Execute("DELETE FROM `DOA_SERVICE_COMMISSION` WHERE `PK_USER` = '$RESPONSE_DATA[PK_USER]'");
        for ($i = 0; $i < count($PK_SERVICE_MASTER); $i++){
            $CODE_COMMISSION_DATA['PK_USER'] = $RESPONSE_DATA['PK_USER'];
            $CODE_COMMISSION_DATA['PK_SERVICE_MASTER'] = $PK_SERVICE_MASTER[$i];
            $CODE_COMMISSION_DATA['COMMISSION_AMOUNT'] = $COMMISSION_AMOUNT[$i];
            $CODE_COMMISSION_DATA['ACTIVE'] = 1;
            $CODE_COMMISSION_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
            $CODE_COMMISSION_DATA['CREATED_ON'] = date("Y-m-d H:i");
            db_perform('DOA_SERVICE_COMMISSION', $CODE_COMMISSION_DATA, 'insert');
        }
    }
}

function saveServiceData($RESPONSE_DATA){
    //pre_r($RESPONSE_DATA);
    global $db;
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
    $res = $db->Execute("SELECT * FROM `DOA_SERVICE_PROVIDER_SERVICES` WHERE PK_USER = '$RESPONSE_DATA[PK_USER]'");
    if ($res->RecordCount() == 0) {
        db_perform('DOA_SERVICE_PROVIDER_SERVICES', $RESPONSE_DATA, 'insert');
    }else{
        db_perform('DOA_SERVICE_PROVIDER_SERVICES', $RESPONSE_DATA, 'update', " PK_USER = $RESPONSE_DATA[PK_USER]");
    }
}

function saveAppointmentData($RESPONSE_DATA){
    global $db;
    unset($RESPONSE_DATA['TIME']);
    if (empty($RESPONSE_DATA['START_TIME']) || empty($RESPONSE_DATA['END_TIME'])){
        unset($RESPONSE_DATA['START_TIME']);
        unset($RESPONSE_DATA['END_TIME']);
    }
    $session_cost = $db->Execute("SELECT * FROM `DOA_ENROLLMENT_SERVICE` WHERE PK_SERVICE_MASTER = '$_POST[PK_SERVICE_MASTER]' AND PK_SERVICE_CODE = '$_POST[PK_SERVICE_CODE]'");
    $price_per_session = $session_cost->fields['PRICE_PER_SESSION'];
    if(empty($RESPONSE_DATA['PK_APPOINTMENT_MASTER'])){
        $RESPONSE_DATA['PK_APPOINTMENT_STATUS'] = 1;
        $RESPONSE_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
        $RESPONSE_DATA['ACTIVE'] = 1;
        $RESPONSE_DATA['CREATED_BY']  = $_SESSION['PK_USER'];
        $RESPONSE_DATA['CREATED_ON']  = date("Y-m-d H:i");
        db_perform('DOA_APPOINTMENT_MASTER', $RESPONSE_DATA, 'insert');
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
        db_perform('DOA_APPOINTMENT_MASTER', $RESPONSE_DATA, 'update'," PK_APPOINTMENT_MASTER =  '$RESPONSE_DATA[PK_APPOINTMENT_MASTER]'");
    }

    rearrangeSerialNumber($_POST['PK_ENROLLMENT_MASTER'], $price_per_session);
}

function rearrangeSerialNumber($PK_ENROLLMENT_MASTER, $price_per_session){
    global $db;
    $appointment_data = $db->Execute("SELECT * FROM `DOA_APPOINTMENT_MASTER` WHERE PK_ENROLLMENT_MASTER = '$PK_ENROLLMENT_MASTER' ORDER BY DATE ASC");
    $total_bill_and_paid = $db->Execute("SELECT SUM(BILLED_AMOUNT) AS TOTAL_BILL, SUM(PAID_AMOUNT) AS TOTAL_PAID FROM DOA_ENROLLMENT_LEDGER WHERE `PK_ENROLLMENT_MASTER`=".$PK_ENROLLMENT_MASTER);
    $total_paid = $total_bill_and_paid->fields['TOTAL_PAID'];
    $total_paid_appointment = intval($total_paid/$price_per_session);
    $i = 1;
    while (!$appointment_data->EOF){
        $UPDATE_DATA['SERIAL_NUMBER'] = $i;
        if ($i <= $total_paid_appointment){
            $UPDATE_DATA['IS_PAID'] = 1;
        } else {
            $UPDATE_DATA['IS_PAID'] = 0;
        }
        db_perform('DOA_APPOINTMENT_MASTER', $UPDATE_DATA, 'update'," PK_APPOINTMENT_MASTER =  ".$appointment_data->fields['PK_APPOINTMENT_MASTER']);
        $appointment_data->MoveNext();
        $i++;
    }
}

function cancelAppointment($RESPONSE_DATA){
    global $db;
    $db->Execute("DELETE FROM `DOA_APPOINTMENT_MASTER` WHERE `PK_APPOINTMENT_MASTER` = '$RESPONSE_DATA[PK_APPOINTMENT_MASTER]'");
}

function completeAppointment($RESPONSE_DATA){
    global $db;
    $RESPONSE_DATA['EDITED_BY']	= $_SESSION['PK_USER'];
    $RESPONSE_DATA['EDITED_ON'] = date("Y-m-d H:i");
    $RESPONSE_DATA['STATUS'] = 'C';
    db_perform('DOA_APPOINTMENT_MASTER', $RESPONSE_DATA, 'update'," PK_APPOINTMENT_MASTER =  '$RESPONSE_DATA[PK_APPOINTMENT_MASTER]'");
}

function getServiceProviderCount($RESPONSE_DATA){
    global $db;
    $date = date('Y-m-d', strtotime($RESPONSE_DATA['currentDate']));
    $all_service_provider = implode(',', $RESPONSE_DATA['all_service_provider']);
    $service_provider_appointment_count = $db->Execute("SELECT COUNT(`PK_APPOINTMENT_MASTER`) AS APPOINTMENT_COUNT, SERVICE_PROVIDER_ID, CONCAT(SERVICE_PROVIDER.FIRST_NAME, ' ', SERVICE_PROVIDER.LAST_NAME) AS SERVICE_PROVIDER_NAME FROM `DOA_APPOINTMENT_MASTER` LEFT JOIN DOA_USERS AS SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.SERVICE_PROVIDER_ID = SERVICE_PROVIDER.PK_USER WHERE SERVICE_PROVIDER_ID IN (".$all_service_provider.") AND `DATE` = '$date' GROUP BY SERVICE_PROVIDER_ID");
    $return_data = [];
    $i = 0;
    while (!$service_provider_appointment_count->EOF){
        $return_data[$i]['APPOINTMENT_COUNT'] = $service_provider_appointment_count->fields['APPOINTMENT_COUNT'];
        $return_data[$i]['SERVICE_PROVIDER_ID'] = $service_provider_appointment_count->fields['SERVICE_PROVIDER_ID'];
        $return_data[$i]['SERVICE_PROVIDER_NAME'] = $service_provider_appointment_count->fields['SERVICE_PROVIDER_NAME'];
        $service_provider_appointment_count->MoveNext();
        $i++;
    }
    echo json_encode($return_data);
}

function selectDefaultLocation($RESPONSE_DATA){
    $_SESSION['DEFAULT_LOCATION_ID'] = $RESPONSE_DATA['DEFAULT_LOCATION_ID'];
}

function createUpdateHistory($class, $update_table_primary_key, $table_name, $primary_key_name, $primary_key, $data, $type) {
    global $db;
    $SKIP_PARAM_ARRAY = ['PK_ACCOUNT_MASTER', 'PK_ENROLLMENT_MASTER', 'ACTIVE', 'CREATED_BY', 'CREATED_ON', 'EDITED_BY', 'EDITED_ON'];
    if ($type == 'insert') {
        foreach ($data as $key => $data_value){
            if (!in_array($key, $SKIP_PARAM_ARRAY) && !empty($data_value)) {
                //echo $key." - ".$data_value."<br>";
                [$FIELD_NAME, $OLD_VALUE, $NEW_VALUE] = checkParameterProperty($key, null, $data_value);
                $UPDATE_HISTORY_DATA['CLASS'] = $class;
                $UPDATE_HISTORY_DATA['PRIMARY_KEY'] = $update_table_primary_key;
                $UPDATE_HISTORY_DATA['FIELD_NAME'] = $FIELD_NAME;
                $UPDATE_HISTORY_DATA['FROM_VALUE'] = $OLD_VALUE;
                $UPDATE_HISTORY_DATA['TO_VALUE'] = $NEW_VALUE;
                $UPDATE_HISTORY_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
                $UPDATE_HISTORY_DATA['EDITED_ON'] = date("Y-m-d H:i");
                db_perform('DOA_UPDATE_HISTORY', $UPDATE_HISTORY_DATA, 'insert');

            }
        }
    } elseif ($type == 'update') {
        foreach ($data as $key => $data_value){
            if (!in_array($key, $SKIP_PARAM_ARRAY)  && !empty($data_value)) {
                $old_record = $db->Execute("SELECT * FROM ".$table_name." WHERE ".$primary_key_name." = ".$primary_key);
                if ($old_record->fields[$key] != $data_value) {
                    //echo $key." - ".$old_record->fields[$key]." - ".$data_value."<br>";
                    [$FIELD_NAME, $OLD_VALUE, $NEW_VALUE] = checkParameterProperty($key, $old_record->fields[$key], $data_value);
                    $UPDATE_HISTORY_DATA['CLASS'] = $class;
                    $UPDATE_HISTORY_DATA['PRIMARY_KEY'] = $update_table_primary_key;
                    $UPDATE_HISTORY_DATA['FIELD_NAME'] = $FIELD_NAME;
                    $UPDATE_HISTORY_DATA['FROM_VALUE'] = $OLD_VALUE;
                    $UPDATE_HISTORY_DATA['TO_VALUE'] = $NEW_VALUE;
                    $UPDATE_HISTORY_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
                    $UPDATE_HISTORY_DATA['EDITED_ON'] = date("Y-m-d H:i");
                    db_perform('DOA_UPDATE_HISTORY', $UPDATE_HISTORY_DATA, 'insert');
                }
            }
        }
    }
}

function checkParameterProperty($key, $old_value, $new_value) {
    switch ($key){
        case 'PK_USER_MASTER':
            $field_name = 'Customer';
            $pk_user_data_old = getPrimaryKeyData('DOA_USER_MASTER', 'PK_USER_MASTER', $old_value);
            $pk_user_old = ($pk_user_data_old == 0) ? NULL : $pk_user_data_old->fields['PK_USER'];
            $data_old = getPrimaryKeyData('DOA_USERS', 'PK_USER', $pk_user_old);
            $return_old_value = ($data_old == 0) ? NULL : $data_old->fields['FIRST_NAME']." ".$data_old->fields['LAST_NAME'];
            $pk_user_data_new = getPrimaryKeyData('DOA_USER_MASTER', 'PK_USER_MASTER', $new_value);
            $pk_user_new = ($pk_user_data_new == 0) ? NULL : $pk_user_data_new->fields['PK_USER'];
            $data_new = getPrimaryKeyData('DOA_USERS', 'PK_USER', $pk_user_new);
            $return_new_value = ($data_new == 0) ? NULL : $data_new->fields['FIRST_NAME']." ".$data_new->fields['LAST_NAME'];
            break;

        case 'PK_LOCATION':
            $field_name = 'Location';
            $data_old = getPrimaryKeyData('DOA_LOCATION', 'PK_LOCATION', $old_value);
            $return_old_value = ($data_old == 0) ? NULL : $data_old->fields['LOCATION_NAME'];
            $data_new = getPrimaryKeyData('DOA_LOCATION', 'PK_LOCATION', $new_value);
            $return_new_value = ($data_new == 0) ? NULL : $data_new->fields['LOCATION_NAME'];
            break;

        case 'PK_AGREEMENT_TYPE':
            $field_name = 'Agreement Type';
            $data_old = getPrimaryKeyData('DOA_AGREEMENT_TYPE', 'PK_AGREEMENT_TYPE', $old_value);
            $return_old_value = ($data_old == 0) ? NULL : $data_old->fields['AGREEMENT_TYPE'];
            $data_new = getPrimaryKeyData('DOA_AGREEMENT_TYPE', 'PK_AGREEMENT_TYPE ', $new_value);
            $return_new_value = ($data_new == 0) ? NULL : $data_new->fields['AGREEMENT_TYPE'];
            break;

        case 'PK_DOCUMENT_LIBRARY':
            $field_name = 'Agreement Template';
            $data_old = getPrimaryKeyData('DOA_DOCUMENT_LIBRARY', 'PK_DOCUMENT_LIBRARY', $old_value);
            $return_old_value = ($data_old == 0) ? NULL : $data_old->fields['DOCUMENT_NAME'];
            $data_new = getPrimaryKeyData('DOA_DOCUMENT_LIBRARY', 'PK_DOCUMENT_LIBRARY ', $new_value);
            $return_new_value = ($data_new == 0) ? NULL : $data_new->fields['DOCUMENT_NAME'];
            break;

        case 'ENROLLMENT_BY_ID':
            $field_name = 'Enrollment By';
            $data_old = getPrimaryKeyData('DOA_USERS', 'PK_USER', $old_value);
            $return_old_value = ($data_old == 0) ? NULL : $data_old->fields['FIRST_NAME']." ".$data_old->fields['LAST_NAME'];
            $data_new = getPrimaryKeyData('DOA_USERS', 'PK_USER', $new_value);
            $return_new_value = ($data_new == 0) ? NULL : $data_new->fields['FIRST_NAME']." ".$data_new->fields['LAST_NAME'];
            break;

        case 'PK_SERVICE_MASTER':
            $field_name = 'Service';
            $data_old = getPrimaryKeyData('DOA_SERVICE_MASTER', 'PK_SERVICE_MASTER', $old_value);
            $return_old_value = ($data_old == 0) ? NULL : $data_old->fields['SERVICE_NAME'];
            $data_new = getPrimaryKeyData('DOA_SERVICE_MASTER', 'PK_SERVICE_MASTER ', $new_value);
            $return_new_value = ($data_new == 0) ? NULL : $data_new->fields['SERVICE_NAME'];
            break;

        case 'PK_SERVICE_CODE':
            $field_name = 'Service Code';
            $data_old = getPrimaryKeyData('DOA_SERVICE_CODE', 'PK_SERVICE_CODE', $old_value);
            $return_old_value = ($data_old == 0) ? NULL : $data_old->fields['SERVICE_CODE'];
            $data_new = getPrimaryKeyData('DOA_SERVICE_CODE', 'PK_SERVICE_CODE ', $new_value);
            $return_new_value = ($data_new == 0) ? NULL : $data_new->fields['SERVICE_CODE'];
            break;

        default:
            $field_name = ucwords(strtolower(str_replace('_', ' ', $key)));
            $return_old_value = $old_value;
            $return_new_value = $new_value;
            break;
    }

    return [$field_name, $return_old_value, $return_new_value];
}

function getPrimaryKeyData($table_name, $primary_key_name, $primary_key) {
    global $db;
    $result = $db->Execute("SELECT * FROM ".$table_name." WHERE ".$primary_key_name." = ".$primary_key);
    if ($result->RecordCount() > 0){
        return $result;
    } else {
        return 0;
    }
}

function markAppointmentCompleted($RESPONSE_DATA) {
    global $db;
    $PK_APPOINTMENT_MASTER = $RESPONSE_DATA['PK_APPOINTMENT_MASTER'];
    $db->Execute("UPDATE DOA_APPOINTMENT_MASTER SET PK_APPOINTMENT_STATUS = 2 WHERE PK_APPOINTMENT_MASTER = ".$PK_APPOINTMENT_MASTER);
    echo 1;
}

function markAllAppointmentCompleted($RESPONSE_DATA) {
    global $db;
    $PK_APPOINTMENT_MASTER = $RESPONSE_DATA['PK_APPOINTMENT_MASTER'];
    for($i=0; $i < count($PK_APPOINTMENT_MASTER); $i++){
        $db->Execute("UPDATE DOA_APPOINTMENT_MASTER SET PK_APPOINTMENT_STATUS = 2 WHERE PK_APPOINTMENT_MASTER = ".$PK_APPOINTMENT_MASTER[$i]);
    }
    echo 1;
}

function viewSamplePdf($RESPONSE_DATA) {
    $files = glob('../../uploads/sample_enrollment_pdf/*'); // get all file names
    foreach($files as $file){ // iterate files
        if(is_file($file)) {
            unlink($file); // delete file
        }
    }

    global $http_path;
    require_once('../../global/vendor/autoload.php');
    $html = $RESPONSE_DATA['DOCUMENT_TEMPLATE'];

    try {
        $mpdf = new Mpdf();
        $mpdf->WriteHTML($html);
        $file_name = "sample_pdf_".time().".pdf";
        $mpdf->Output("../../uploads/sample_enrollment_pdf/".$file_name, 'F');
    } catch (Exception $e) {
        echo $e->getMessage(); die;
    }

    echo $http_path."uploads/sample_enrollment_pdf/".$file_name;
}

/*function viewGiftCertificatePdf($RESPONSE_DATA) {
    $files = glob('../../uploads/sample_enrollment_pdf/*'); // get all file names
    foreach($files as $file){ // iterate files
        if(is_file($file)) {
            unlink($file); // delete file
        }
    }

    global $http_path;
    require_once('../../global/vendor/autoload.php');
    $html = $RESPONSE_DATA['DOCUMENT_TEMPLATE'];

    try {
        $mpdf = new Mpdf();
        $mpdf->WriteHTML($html);
        $file_name = "sample_pdf_".time().".pdf";
        $mpdf->Output("../../uploads/sample_enrollment_pdf/".$file_name, 'F');
    } catch (Exception $e) {
        echo $e->getMessage(); die;
    }

    echo $http_path."uploads/sample_enrollment_pdf/".$file_name;
}*/

function viewGiftCertificatePdf($RESPONSE_DATA) {
    error_reporting(0);
    try {
        global $http_path;
        require_once('../../global/vendor/autoload.php');
        try {
            $mpdf = new Mpdf();
            $html = file_get_contents($http_path . 'admin/gift_certificate_pdf.php?id=' . $RESPONSE_DATA['PK_GIFT_CERTIFICATE_MASTER']);
            $mpdf->SetFont('calibri');
            $mpdf->WriteHTML($html);
            $file_name = "gift_certificate_" . $RESPONSE_DATA['PK_GIFT_CERTIFICATE_MASTER'] . ".pdf";
            $mpdf->Output('../../uploads/gift_certificate_pdf/'.$file_name, 'F');
        } catch (Exception $e) {
            echo $e->getMessage(); die;
        }
        echo $http_path."uploads/gift_certificate_pdf/".$file_name;
    } catch (Exception $exception) {
        echo $exception->getMessage();
    }
}

function saveMultiAppointmentData($RESPONSE_DATA){
    global $db;
    $PK_ENROLLMENT_MASTER_ARRAY = explode(',', $RESPONSE_DATA['PK_ENROLLMENT_MASTER']);
    $PK_ENROLLMENT_MASTER = $PK_ENROLLMENT_MASTER_ARRAY[0];
    $PK_ENROLLMENT_SERVICE = $PK_ENROLLMENT_MASTER_ARRAY[1];
    $PK_SERVICE_MASTER = $PK_ENROLLMENT_MASTER_ARRAY[2];
    $PK_SERVICE_CODE = $PK_ENROLLMENT_MASTER_ARRAY[3];

    $DURATION = $RESPONSE_DATA['DURATION'];
    $NUMBER_OF_SESSION = $RESPONSE_DATA['NUMBER_OF_SESSION'];
    $STARTING_ON = $RESPONSE_DATA['STARTING_ON'];
    $LENGTH = $RESPONSE_DATA['LENGTH'];
    $FREQUENCY = $RESPONSE_DATA['FREQUENCY'];
    $END_DATE = date('Y-m-d', strtotime('+ ' . $LENGTH . ' ' . $FREQUENCY, strtotime($STARTING_ON)));

    $START_TIME = $RESPONSE_DATA['START_TIME'];
    $END_TIME = date("H:i", strtotime($START_TIME)+($DURATION*60));

    $APPOINTMENT_DATE_ARRAY = [];
    if (!empty($RESPONSE_DATA['OCCURRENCE'])){
        $APPOINTMENT_DATE = date('Y-m-d', strtotime($STARTING_ON));
        if ($RESPONSE_DATA['OCCURRENCE'] == 'WEEKLY'){
            if (isset($RESPONSE_DATA['DAYS'])) {
                $DAYS = $RESPONSE_DATA['DAYS'];
            } else {
                $DAYS[] = strtolower(date('l', strtotime($STARTING_ON)));
            }
            while ($APPOINTMENT_DATE < $END_DATE) {
                $appointment_day = date('l', strtotime($APPOINTMENT_DATE));
                if (in_array(strtolower($appointment_day), $DAYS)){
                    $APPOINTMENT_DATE_ARRAY[] = $APPOINTMENT_DATE;
                }
                $APPOINTMENT_DATE = date('Y-m-d', strtotime('+1 day ', strtotime($APPOINTMENT_DATE)));
            }
        }else {
            $OCCURRENCE_DAYS = (empty($RESPONSE_DATA['OCCURRENCE_DAYS']))?7:$RESPONSE_DATA['OCCURRENCE_DAYS'];

            while ($APPOINTMENT_DATE < $END_DATE) {
                $APPOINTMENT_DATE_ARRAY[] = $APPOINTMENT_DATE;
                $APPOINTMENT_DATE = date('Y-m-d', strtotime('+ '.$OCCURRENCE_DAYS.' day', strtotime($APPOINTMENT_DATE)));
                //echo $APPOINTMENT_DATE . "<br>";
            }
        }
    }

    $session_created_data = $db->Execute("SELECT COUNT(`PK_ENROLLMENT_SERVICE`) AS USED_SESSION_COUNT FROM `DOA_APPOINTMENT_MASTER` WHERE `PK_ENROLLMENT_SERVICE` = ".$PK_ENROLLMENT_SERVICE);
    $SESSION_CREATED = $session_created_data->fields['USED_SESSION_COUNT'];
    $SESSION_LEFT = $NUMBER_OF_SESSION-$SESSION_CREATED;

    if ($RESPONSE_DATA['IS_SUBMIT'] == 1) {
        if (count($APPOINTMENT_DATE_ARRAY) > 0) {
            $SESSION_WILL_CREATE = (count($APPOINTMENT_DATE_ARRAY) < $SESSION_LEFT) ? count($APPOINTMENT_DATE_ARRAY) : $SESSION_LEFT;
            for ($i = 0; $i < $SESSION_WILL_CREATE; $i++) {
                $APPOINTMENT_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
                $APPOINTMENT_DATA['CUSTOMER_ID'] = $_POST['CUSTOMER_ID'];
                $APPOINTMENT_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
                $APPOINTMENT_DATA['PK_ENROLLMENT_SERVICE'] = $PK_ENROLLMENT_SERVICE;
                $APPOINTMENT_DATA['PK_SERVICE_MASTER'] = $PK_SERVICE_MASTER;
                $APPOINTMENT_DATA['PK_SERVICE_CODE'] = $PK_SERVICE_CODE;
                $APPOINTMENT_DATA['SERVICE_PROVIDER_ID'] = $_POST['SERVICE_PROVIDER_ID'];
                $APPOINTMENT_DATA['DATE'] = $APPOINTMENT_DATE_ARRAY[$i];
                $APPOINTMENT_DATA['START_TIME'] = date('H:i:s', strtotime($START_TIME));
                $APPOINTMENT_DATA['END_TIME'] = date('H:i:s', strtotime($END_TIME));
                $APPOINTMENT_DATA['PK_APPOINTMENT_STATUS'] = 1;
                $APPOINTMENT_DATA['ACTIVE'] = 1;
                $APPOINTMENT_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
                $APPOINTMENT_DATA['CREATED_ON'] = date("Y-m-d H:i");
                db_perform('DOA_APPOINTMENT_MASTER', $APPOINTMENT_DATA, 'insert');
            }
            $session_cost = $db->Execute("SELECT * FROM `DOA_ENROLLMENT_SERVICE` WHERE PK_SERVICE_MASTER = '$PK_SERVICE_MASTER' AND PK_SERVICE_CODE = '$PK_SERVICE_CODE'");
            $price_per_session = $session_cost->fields['PRICE_PER_SESSION'];
            rearrangeSerialNumber($_POST['PK_ENROLLMENT_MASTER'], $price_per_session);
        }
    } else {
        if (count($APPOINTMENT_DATE_ARRAY) > $SESSION_LEFT) {
            echo $SESSION_LEFT;
        } else {
            echo 0;
        }
    }
}

function getEditCommentData($RESPONSE_DATA) {
    global $db;
    $PK_COMMENT = $RESPONSE_DATA['PK_COMMENT'];
    $comment_data = $db->Execute("SELECT * FROM `DOA_COMMENT` WHERE `PK_COMMENT` = ".$PK_COMMENT);
    echo json_encode($comment_data);
}

function saveCommentData($RESPONSE_DATA){
    global $db;
    $COMMENT_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
    $COMMENT_DATA['COMMENT'] = $RESPONSE_DATA['COMMENT'];
    $COMMENT_DATA['COMMENT_DATE'] = date("Y-m-d");
    $COMMENT_DATA['FOR_PK_USER'] = $RESPONSE_DATA['PK_USER'];
    $COMMENT_DATA['BY_PK_USER']  = $_SESSION['PK_USER'];

    if ($RESPONSE_DATA['PK_COMMENT'] == 0) {
        $COMMENT_DATA['ACTIVE'] = 1;
        $COMMENT_DATA['CREATED_ON']  = date("Y-m-d H:i");
        $COMMENT_DATA['CREATED_BY']  = $_SESSION['PK_USER'];
        db_perform('DOA_COMMENT', $COMMENT_DATA, 'insert');
    } else {
        $COMMENT_DATA['ACTIVE'] = $RESPONSE_DATA['ACTIVE'];
        $COMMENT_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
        $COMMENT_DATA['EDITED_ON'] = date("Y-m-d H:i");
        db_perform('DOA_COMMENT', $COMMENT_DATA, 'update', " PK_COMMENT = ".$RESPONSE_DATA['PK_COMMENT']);
    }
    echo 1;
}

function deleteCommentData($RESPONSE_DATA) {
    global $db;
    $PK_COMMENT = $RESPONSE_DATA['PK_COMMENT'];
    $comment_data = $db->Execute("DELETE FROM `DOA_COMMENT` WHERE `PK_COMMENT` = ".$PK_COMMENT);
    echo 1;
}

function deleteDocumentLibraryData($RESPONSE_DATA) {
    global $db;
    $PK_DOCUMENT_LIBRARY = $RESPONSE_DATA['PK_DOCUMENT_LIBRARY'];
    $document_library_data = $db->Execute("DELETE FROM `DOA_DOCUMENT_LIBRARY` WHERE `PK_DOCUMENT_LIBRARY` = ".$PK_DOCUMENT_LIBRARY);
    echo 1;
}

function deleteServiceData($RESPONSE_DATA) {
    global $db;
    $PK_SERVICE_MASTER = $RESPONSE_DATA['PK_SERVICE_MASTER'];
    $service_data = $db->Execute("DELETE FROM `DOA_SERVICE_MASTER` WHERE `PK_SERVICE_MASTER` = ".$PK_SERVICE_MASTER);
    echo 1;
}

function deleteLocationData($RESPONSE_DATA) {
    global $db;
    $PK_LOCATION = $RESPONSE_DATA['PK_LOCATION'];
    $location_data = $db->Execute("DELETE FROM `DOA_LOCATION` WHERE `PK_LOCATION` = ".$PK_LOCATION);
    echo 1;
}