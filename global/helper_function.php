<?php
function markAppointmentPaid($PK_ENROLLMENT_SERVICE)
{
    global $db_account;
    $serviceCodeData = $db_account->Execute("SELECT PK_ENROLLMENT_SERVICE, NUMBER_OF_SESSION, TOTAL_AMOUNT_PAID, PRICE_PER_SESSION FROM DOA_ENROLLMENT_SERVICE WHERE PK_ENROLLMENT_SERVICE = ".$PK_ENROLLMENT_SERVICE);
    if ($serviceCodeData->RecordCount() > 0) {
        $paid_session = ($serviceCodeData->fields['PRICE_PER_SESSION'] > 0) ? ceil($serviceCodeData->fields['TOTAL_AMOUNT_PAID'] / $serviceCodeData->fields['PRICE_PER_SESSION']) : $serviceCodeData->fields['NUMBER_OF_SESSION'];
        if ($paid_session >= 1) {
            $db_account->Execute("UPDATE `DOA_APPOINTMENT_MASTER` SET `IS_PAID` = '1' WHERE APPOINTMENT_TYPE = 'NORMAL' AND PK_ENROLLMENT_SERVICE = '$PK_ENROLLMENT_SERVICE' ORDER BY DATE DESC LIMIT $paid_session");
        }
    }
}

function checkAdhocAppointmentStatus($PK_APPOINTMENT_MASTER, $PK_SERVICE_MASTER, $PK_SERVICE_CODE, $CUSTOMER_ID)
{
    global $db_account;
    $enrollment_data = $db_account->Execute("SELECT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_SERVICE, DOA_SERVICE_MASTER.PK_SERVICE_MASTER, DOA_SERVICE_CODE.PK_SERVICE_CODE, DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION FROM DOA_ENROLLMENT_MASTER RIGHT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER LEFT JOIN DOA_SERVICE_MASTER ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER LEFT JOIN DOA_SERVICE_CODE ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE WHERE DOA_SERVICE_CODE.IS_GROUP != 1 AND DOA_ENROLLMENT_MASTER.STATUS = 'A' AND DOA_ENROLLMENT_SERVICE.SESSION_CREATED < DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION AND DOA_ENROLLMENT_SERVICE.PK_SERVICE_MASTER = $PK_SERVICE_MASTER AND DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE = $PK_SERVICE_CODE AND DOA_ENROLLMENT_MASTER.PK_USER_MASTER = $CUSTOMER_ID LIMIT 1");
    if ($enrollment_data->RecordCount() > 0) {
        $APPOINTMENT_DATA['PK_ENROLLMENT_MASTER'] = $enrollment_data->fields['PK_ENROLLMENT_MASTER'];
        $APPOINTMENT_DATA['PK_ENROLLMENT_SERVICE'] = $enrollment_data->fields['PK_ENROLLMENT_SERVICE'];
        $APPOINTMENT_DATA['APPOINTMENT_TYPE'] = 'NORMAL';
        $APPOINTMENT_DATA['SERIAL_NUMBER'] = getAppointmentSerialNumber($CUSTOMER_ID);
        db_perform_account('DOA_APPOINTMENT_MASTER', $APPOINTMENT_DATA, 'update'," PK_APPOINTMENT_MASTER = ".$PK_APPOINTMENT_MASTER);
        markAppointmentPaid($enrollment_data->fields['PK_ENROLLMENT_SERVICE']);
        updateSessionCreatedCount($enrollment_data->fields['PK_ENROLLMENT_SERVICE']);
    }
}

function markAdhocAppointmentNormal($PK_ENROLLMENT_MASTER)
{
    global $db_account;
    $enrollmentServiceData = $db_account->Execute("SELECT DOA_ENROLLMENT_SERVICE.*, DOA_ENROLLMENT_MASTER.PK_USER_MASTER FROM DOA_ENROLLMENT_SERVICE LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = ".$PK_ENROLLMENT_MASTER);
    while (!$enrollmentServiceData->EOF) {
        $PK_ENROLLMENT_SERVICE = $enrollmentServiceData->fields['PK_ENROLLMENT_SERVICE'];
        $PK_USER_MASTER = $enrollmentServiceData->fields['PK_USER_MASTER'];
        $PK_SERVICE_MASTER = $enrollmentServiceData->fields['PK_SERVICE_MASTER'];
        $PK_SERVICE_CODE = $enrollmentServiceData->fields['PK_SERVICE_CODE'];
        $NUMBER_OF_SESSION = $enrollmentServiceData->fields['NUMBER_OF_SESSION'];
        $SERIAL_NUMBER = getAppointmentSerialNumber($PK_USER_MASTER);

        $db_account->Execute("UPDATE `DOA_APPOINTMENT_MASTER` LEFT JOIN DOA_APPOINTMENT_CUSTOMER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER SET `PK_ENROLLMENT_MASTER` = '$PK_ENROLLMENT_MASTER',`PK_ENROLLMENT_SERVICE` = '$PK_ENROLLMENT_SERVICE', `APPOINTMENT_TYPE` = 'NORMAL', `SERIAL_NUMBER` = '$SERIAL_NUMBER' WHERE DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER = '$PK_SERVICE_MASTER' AND DOA_APPOINTMENT_MASTER.PK_SERVICE_CODE = '$PK_SERVICE_CODE' AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE = 'AD-HOC' AND DOA_APPOINTMENT_CUSTOMER.PK_USER_MASTER = '$PK_USER_MASTER' LIMIT ".$NUMBER_OF_SESSION);
        $affected_row = $db_account->affected_rows();
        for ($i=0; $i<$affected_row; $i++) {
            updateSessionCreatedCount($PK_ENROLLMENT_SERVICE);
        }

        $enrollmentServiceData->MoveNext();
    }

}

function updateSessionCreatedCount($PK_ENROLLMENT_SERVICE)
{
    global $db_account;
    $serviceCodeData = $db_account->Execute("SELECT DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_SERVICE, DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION, DOA_ENROLLMENT_SERVICE.SESSION_CREATED, DOA_ENROLLMENT_MASTER.PK_USER_MASTER FROM DOA_ENROLLMENT_SERVICE JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER WHERE PK_ENROLLMENT_SERVICE = ".$PK_ENROLLMENT_SERVICE);
    if ($serviceCodeData->RecordCount() > 0) {
        if ($serviceCodeData->fields['SESSION_CREATED'] < $serviceCodeData->fields['NUMBER_OF_SESSION']) {
            if ($serviceCodeData->fields['SESSION_CREATED'] > 0) {
                $ENR_SERVICE_DATA['SESSION_CREATED'] = $serviceCodeData->fields['SESSION_CREATED'] + 1;
            } else {
                $ENR_SERVICE_DATA['SESSION_CREATED'] = 1;
            }
            db_perform_account('DOA_ENROLLMENT_SERVICE', $ENR_SERVICE_DATA, 'update', " PK_ENROLLMENT_SERVICE = " . $PK_ENROLLMENT_SERVICE);
        }
    }
}

function updateSessionCompletedCount($PK_APPOINTMENT_MASTER)
{
    global $db_account;
    $appointmentData = $db_account->Execute("SELECT `PK_ENROLLMENT_SERVICE` FROM `DOA_APPOINTMENT_MASTER` WHERE `PK_APPOINTMENT_MASTER` = ".$PK_APPOINTMENT_MASTER);
    $PK_ENROLLMENT_SERVICE = $appointmentData->fields['PK_ENROLLMENT_SERVICE'];
    $serviceCodeData = $db_account->Execute("SELECT DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_SERVICE, DOA_ENROLLMENT_SERVICE.SESSION_COMPLETED, DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION, DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, DOA_ENROLLMENT_MASTER.PK_USER_MASTER FROM DOA_ENROLLMENT_SERVICE JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER WHERE PK_ENROLLMENT_SERVICE = ".$PK_ENROLLMENT_SERVICE);
    if ($serviceCodeData->RecordCount() > 0) {
        $is_count_done = checkCountAdded($PK_APPOINTMENT_MASTER, $serviceCodeData->fields['PK_USER_MASTER'], $serviceCodeData->fields['PK_ENROLLMENT_MASTER'], $PK_ENROLLMENT_SERVICE, 'COMPLETED');
        if ($is_count_done === 0) {
            if ($serviceCodeData->fields['SESSION_COMPLETED'] > 0) {
                $ENR_SERVICE_DATA['SESSION_COMPLETED'] = $serviceCodeData->fields['SESSION_COMPLETED'] + 1;
            } else {
                $ENR_SERVICE_DATA['SESSION_COMPLETED'] = 1;
            }
            db_perform_account('DOA_ENROLLMENT_SERVICE', $ENR_SERVICE_DATA, 'update', " PK_ENROLLMENT_SERVICE = " . $PK_ENROLLMENT_SERVICE);
            markEnrollmentComplete($serviceCodeData->fields['PK_ENROLLMENT_MASTER']);
        }
    }

    if($serviceCodeData->fields['NUMBER_OF_SESSION'] == $ENR_SERVICE_DATA['SESSION_COMPLETED']) {
        copyEnrollment($serviceCodeData->fields['PK_ENROLLMENT_MASTER']);
    }
}

function copyEnrollment($PK_ENROLLMENT_MASTER){
    global $db;
    global $db_account;
    $enrollment_data = $db_account->Execute("SELECT * FROM DOA_ENROLLMENT_MASTER WHERE PK_ENROLLMENT_MASTER=".$PK_ENROLLMENT_MASTER);
    if($enrollment_data->RecordCount() > 0) {
        $ENROLLMENT_MASTER_DATA['PK_ENROLLMENT_TYPE '] = $enrollment_data->fields['PK_ENROLLMENT_TYPE '];
        $ENROLLMENT_MASTER_DATA['ENROLLMENT_NAME'] = $enrollment_data->fields['ENROLLMENT_NAME'];
        $ENROLLMENT_MASTER_DATA['PK_USER_MASTER'] = $enrollment_data->fields['PK_USER_MASTER'];
        $ENROLLMENT_MASTER_DATA['PK_LOCATION'] = $enrollment_data->fields['PK_LOCATION'];
        $ENROLLMENT_MASTER_DATA['PK_PACKAGE'] = $enrollment_data->fields['PK_PACKAGE'];
        $ENROLLMENT_MASTER_DATA['CHARGE_BY_SESSIONS'] = $enrollment_data->fields['CHARGE_BY_SESSIONS'];
        $ENROLLMENT_MASTER_DATA['PK_AGREEMENT_TYPE'] = $enrollment_data->fields['PK_AGREEMENT_TYPE'];
        $ENROLLMENT_MASTER_DATA['PK_DOCUMENT_LIBRARY'] = $enrollment_data->fields['PK_DOCUMENT_LIBRARY'];
        $ENROLLMENT_MASTER_DATA['AGREEMENT_PDF_LINK'] = $enrollment_data->fields['AGREEMENT_PDF_LINK'];
        $ENROLLMENT_MASTER_DATA['ENROLLMENT_BY_ID'] = $enrollment_data->fields['ENROLLMENT_BY_ID'];
        $ENROLLMENT_MASTER_DATA['ENROLLMENT_BY_PERCENTAGE'] = $enrollment_data->fields['ENROLLMENT_BY_PERCENTAGE'];
        $ENROLLMENT_MASTER_DATA['MEMO'] = $enrollment_data->fields['MEMO'];
        $ENROLLMENT_MASTER_DATA['STATUS'] = 'A';
        $service_data = $db_account->Execute("SELECT * FROM DOA_ENROLLMENT_SERVICE WHERE PK_ENROLLMENT_MASTER=".$PK_ENROLLMENT_MASTER);
        $account_data = $db->Execute("SELECT ENROLLMENT_ID_CHAR, ENROLLMENT_ID_NUM, MISCELLANEOUS_ID_CHAR, MISCELLANEOUS_ID_NUM FROM `DOA_ACCOUNT_MASTER` WHERE `PK_ACCOUNT_MASTER` = '$_SESSION[PK_ACCOUNT_MASTER]'");
        $misc_service_data = $db_account->Execute("SELECT * FROM DOA_SERVICE_MASTER WHERE PK_SERVICE_CLASS = 5 AND PK_SERVICE_MASTER = ".$service_data->fields['PK_SERVICE_MASTER']);
        if ($misc_service_data->RecordCount() > 0){
            $id_data = $db_account->Execute("SELECT MISC_ID FROM `DOA_ENROLLMENT_MASTER` WHERE ENROLLMENT_ID IS NULL AND PK_USER_MASTER = ".$enrollment_data->fields['PK_USER_MASTER']." ORDER BY PK_ENROLLMENT_MASTER DESC LIMIT 1");
            if ($id_data->fields['MISC_ID'] != ' '){
                $misc_id = explode("-", $id_data->fields['MISC_ID']);
                $last_misc_id = $misc_id[1];
                $ENROLLMENT_MASTER_DATA['MISC_ID'] = $account_data->fields['MISCELLANEOUS_ID_CHAR']."-".(intval($last_misc_id)+1);
            }else{
                $ENROLLMENT_MASTER_DATA['MISC_ID'] = $account_data->fields['MISCELLANEOUS_ID_CHAR']."-".$account_data->fields['MISCELLANEOUS_ID_NUM'];
            }
        } else {
            $id_data = $db_account->Execute("SELECT ENROLLMENT_ID FROM `DOA_ENROLLMENT_MASTER` WHERE MISC_ID IS NULL AND PK_USER_MASTER = ".$enrollment_data->fields['PK_USER_MASTER']." ORDER BY PK_ENROLLMENT_MASTER DESC LIMIT 1");
            if ($id_data->fields['ENROLLMENT_ID'] != ' '){
                $enrollment_id = explode("-", $id_data->fields['ENROLLMENT_ID']);
                $last_enrollment_id = $enrollment_id[1];
                $ENROLLMENT_MASTER_DATA['ENROLLMENT_ID'] = $account_data->fields['ENROLLMENT_ID_CHAR']."-".(intval($last_enrollment_id)+1);
            }else{
                $ENROLLMENT_MASTER_DATA['ENROLLMENT_ID'] = $account_data->fields['ENROLLMENT_ID_CHAR']."-".$account_data->fields['ENROLLMENT_ID_NUM'];
            }
        }
        if ($id_data->RecordCount() > 0){
            $ENROLLMENT_MASTER_DATA['CUSTOMER_ENROLLMENT_NUMBER'] = $id_data->fields['CUSTOMER_ENROLLMENT_NUMBER'] + 1;
        }else{
            $ENROLLMENT_MASTER_DATA['CUSTOMER_ENROLLMENT_NUMBER'] = 1;
        }
        $ENROLLMENT_MASTER_DATA['ACTIVE'] = 1;
        $ENROLLMENT_MASTER_DATA['CREATED_BY']  = $_SESSION['PK_USER'];
        $ENROLLMENT_MASTER_DATA['CREATED_ON']  = date("Y-m-d H:i");
        db_perform_account('DOA_ENROLLMENT_MASTER', $ENROLLMENT_MASTER_DATA, 'insert');
        $PK_ENROLLMENT_MASTER_NEW = $db_account->insert_ID();

        $ENROLLMENT_SERVICE_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER_NEW;
        $ENROLLMENT_SERVICE_DATA['PK_SERVICE_MASTER'] = $service_data->fields['PK_SERVICE_MASTER'];
        $ENROLLMENT_SERVICE_DATA['PK_SERVICE_CODE'] = $service_data->fields['PK_SERVICE_CODE'];
        $ENROLLMENT_SERVICE_DATA['SERVICE_DETAILS'] = $service_data->fields['SERVICE_DETAILS'];
        $ENROLLMENT_SERVICE_DATA['NUMBER_OF_SESSION'] = $service_data->fields['NUMBER_OF_SESSION'];
        $ENROLLMENT_SERVICE_DATA['PRICE_PER_SESSION'] = $service_data->fields['PRICE_PER_SESSION'];
        $ENROLLMENT_SERVICE_DATA['TOTAL'] = $service_data->fields['TOTAL'];
        $ENROLLMENT_SERVICE_DATA['TOTAL_AMOUNT_PAID'] = $service_data->fields['TOTAL_AMOUNT_PAID'];
        $ENROLLMENT_SERVICE_DATA['DISCOUNT_TYPE'] = $service_data->fields['DISCOUNT_TYPE'];
        $ENROLLMENT_SERVICE_DATA['DISCOUNT'] = $service_data->fields['DISCOUNT'];
        $ENROLLMENT_SERVICE_DATA['FINAL_AMOUNT'] = $service_data->fields['FINAL_AMOUNT'];
        $ENROLLMENT_SERVICE_DATA['STATUS'] = 'A';
        db_perform_account('DOA_ENROLLMENT_SERVICE', $ENROLLMENT_SERVICE_DATA, 'insert');

        $billing_data = $db_account->Execute("SELECT * FROM DOA_ENROLLMENT_BILLING WHERE PK_ENROLLMENT_MASTER=".$PK_ENROLLMENT_MASTER);
        $ENROLLMENT_BILLING_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER_NEW;
        $ENROLLMENT_BILLING_DATA['BILLING_REF'] = $billing_data->fields['BILLING_REF'];
        $ENROLLMENT_BILLING_DATA['BILLING_DATE'] = $billing_data->fields['BILLING_DATE'];
        $ENROLLMENT_BILLING_DATA['DOWN_PAYMENT'] = $billing_data->fields['DOWN_PAYMENT'];
        $ENROLLMENT_BILLING_DATA['BALANCE_PAYABLE'] = $billing_data->fields['BALANCE_PAYABLE'];
        $ENROLLMENT_BILLING_DATA['TOTAL_AMOUNT'] = $billing_data->fields['TOTAL_AMOUNT'];
        $ENROLLMENT_BILLING_DATA['PAYMENT_METHOD'] = $billing_data->fields['PAYMENT_METHOD'];
        $ENROLLMENT_BILLING_DATA['PAYMENT_TERM'] = $billing_data->fields['PAYMENT_TERM'];
        $ENROLLMENT_BILLING_DATA['NUMBER_OF_PAYMENT'] = $billing_data->fields['NUMBER_OF_PAYMENT'];
        $ENROLLMENT_BILLING_DATA['FIRST_DUE_DATE'] = $billing_data->fields['FIRST_DUE_DATE'];
        $ENROLLMENT_BILLING_DATA['INSTALLMENT_AMOUNT'] = $billing_data->fields['INSTALLMENT_AMOUNT'];
        db_perform_account('DOA_ENROLLMENT_BILLING', $ENROLLMENT_BILLING_DATA, 'insert');
        $PK_ENROLLMENT_BILLING_NEW = $db_account->insert_ID();

        $ledger_data = $db_account->Execute("SELECT * FROM DOA_ENROLLMENT_LEDGER WHERE TRANSACTION_TYPE='Billing' AND PK_ENROLLMENT_MASTER=".$PK_ENROLLMENT_MASTER);
        $ENROLLMENT_LEDGER_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER_NEW;
        $ENROLLMENT_LEDGER_DATA['PK_ENROLLMENT_BILLING '] = $PK_ENROLLMENT_BILLING_NEW;
        $ENROLLMENT_LEDGER_DATA['TRANSACTION_TYPE'] = $ledger_data->fields['TRANSACTION_TYPE'];
        $ENROLLMENT_LEDGER_DATA['ENROLLMENT_LEDGER_PARENT '] = $ledger_data->fields['ENROLLMENT_LEDGER_PARENT'];
        $ENROLLMENT_LEDGER_DATA['DUE_DATE'] = $ledger_data->fields['DUE_DATE'];
        $ENROLLMENT_LEDGER_DATA['BILLED_AMOUNT'] = $ledger_data->fields['BILLED_AMOUNT'];
        $ENROLLMENT_LEDGER_DATA['PAID_AMOUNT'] = $ledger_data->fields['PAID_AMOUNT'];
        $ENROLLMENT_LEDGER_DATA['BALANCE'] = $ledger_data->fields['BALANCE'];
        $ENROLLMENT_LEDGER_DATA['IS_PAID'] = $ledger_data->fields['IS_PAID'];
        $ENROLLMENT_LEDGER_DATA['IS_DOWN_PAYMENT'] = $ledger_data->fields['IS_DOWN_PAYMENT'];
        $ENROLLMENT_LEDGER_DATA['STATUS'] = $ledger_data->fields['STATUS'];
        db_perform_account('DOA_ENROLLMENT_LEDGER', $ENROLLMENT_LEDGER_DATA, 'insert');
    }
}

function updateSessionCreatedCountByStatus($PK_APPOINTMENT_MASTER)
{
    global $db_account;
    $appointmentData = $db_account->Execute("SELECT `PK_ENROLLMENT_SERVICE` FROM `DOA_APPOINTMENT_MASTER` WHERE `PK_APPOINTMENT_MASTER` = ".$PK_APPOINTMENT_MASTER);
    $PK_ENROLLMENT_SERVICE = $appointmentData->fields['PK_ENROLLMENT_SERVICE'];
    $serviceCodeData = $db_account->Execute("SELECT PK_ENROLLMENT_SERVICE, SESSION_CREATED FROM DOA_ENROLLMENT_SERVICE WHERE PK_ENROLLMENT_SERVICE = ".$PK_ENROLLMENT_SERVICE);
    if ($serviceCodeData->RecordCount() > 0) {
        $ENR_SERVICE_DATA['SESSION_CREATED'] = $serviceCodeData->fields['SESSION_CREATED'] - 1;
        }
    db_perform_account('DOA_ENROLLMENT_SERVICE', $ENR_SERVICE_DATA, 'update', " PK_ENROLLMENT_SERVICE = " . $PK_ENROLLMENT_SERVICE);
}

function updateSessionCreatedCountGroupClass($PK_APPOINTMENT_MASTER, $PK_USER_MASTER)
{
    global $db_account;
    $appointmentData = $db_account->Execute("SELECT `PK_SERVICE_MASTER`, `PK_SERVICE_CODE` FROM `DOA_APPOINTMENT_MASTER` WHERE `PK_APPOINTMENT_MASTER` = ".$PK_APPOINTMENT_MASTER);
    $PK_SERVICE_MASTER = $appointmentData->fields['PK_SERVICE_MASTER'];
    $PK_SERVICE_CODE = $appointmentData->fields['PK_SERVICE_CODE'];
    $serviceCodeData = $db_account->Execute("SELECT PK_ENROLLMENT_SERVICE, SESSION_CREATED, DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER FROM DOA_ENROLLMENT_SERVICE LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_USER_MASTER = $PK_USER_MASTER AND DOA_ENROLLMENT_SERVICE.PK_SERVICE_MASTER = $PK_SERVICE_MASTER AND DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE = $PK_SERVICE_CODE AND `NUMBER_OF_SESSION` > `SESSION_CREATED` ORDER BY PK_ENROLLMENT_SERVICE ASC LIMIT 1");
    if ($serviceCodeData->RecordCount() > 0) {
        $is_count_done = checkCountAdded($PK_APPOINTMENT_MASTER, $PK_USER_MASTER, $serviceCodeData->fields['PK_ENROLLMENT_MASTER'], $serviceCodeData->fields['PK_ENROLLMENT_SERVICE'], 'CREATED');
        if ($is_count_done === 0) {
            if ($serviceCodeData->fields['SESSION_CREATED'] > 0) {
                $ENR_SERVICE_DATA['SESSION_CREATED'] = $serviceCodeData->fields['SESSION_CREATED'] + 1;
            } else {
                $ENR_SERVICE_DATA['SESSION_CREATED'] = 1;
            }
            db_perform_account('DOA_ENROLLMENT_SERVICE', $ENR_SERVICE_DATA, 'update', " PK_ENROLLMENT_SERVICE = " . $serviceCodeData->fields['PK_ENROLLMENT_SERVICE']);
        }
    }
}

function updateSessionCompletedCountGroupClass($PK_APPOINTMENT_MASTER, $PK_USER_MASTER)
{
    global $db_account;
    $appointmentData = $db_account->Execute("SELECT `PK_SERVICE_MASTER`, `PK_SERVICE_CODE` FROM `DOA_APPOINTMENT_MASTER` WHERE `PK_APPOINTMENT_MASTER` = ".$PK_APPOINTMENT_MASTER);
    $PK_SERVICE_MASTER = $appointmentData->fields['PK_SERVICE_MASTER'];
    $PK_SERVICE_CODE = $appointmentData->fields['PK_SERVICE_CODE'];
    $serviceCodeData = $db_account->Execute("SELECT PK_ENROLLMENT_SERVICE, SESSION_COMPLETED, DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER FROM DOA_ENROLLMENT_SERVICE LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_USER_MASTER = $PK_USER_MASTER AND DOA_ENROLLMENT_SERVICE.PK_SERVICE_MASTER = $PK_SERVICE_MASTER AND DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE = $PK_SERVICE_CODE AND `NUMBER_OF_SESSION` > `SESSION_COMPLETED` ORDER BY PK_ENROLLMENT_SERVICE ASC LIMIT 1");
    if ($serviceCodeData->RecordCount() > 0) {
        $is_count_done = checkCountAdded($PK_APPOINTMENT_MASTER, $PK_USER_MASTER, $serviceCodeData->fields['PK_ENROLLMENT_MASTER'], $serviceCodeData->fields['PK_ENROLLMENT_SERVICE'], 'COMPLETED');
        if ($is_count_done === 0) {
            if ($serviceCodeData->fields['SESSION_COMPLETED'] > 0) {
                $ENR_SERVICE_DATA['SESSION_COMPLETED'] = $serviceCodeData->fields['SESSION_COMPLETED'] + 1;
            } else {
                $ENR_SERVICE_DATA['SESSION_COMPLETED'] = 1;
            }
            db_perform_account('DOA_ENROLLMENT_SERVICE', $ENR_SERVICE_DATA, 'update', " PK_ENROLLMENT_SERVICE = " . $serviceCodeData->fields['PK_ENROLLMENT_SERVICE']);
            markEnrollmentComplete($serviceCodeData->fields['PK_ENROLLMENT_MASTER']);
        }
    }
}

function checkCountAdded($PK_APPOINTMENT_MASTER, $PK_USER_MASTER, $PK_ENROLLMENT_MASTER, $PK_ENROLLMENT_SERVICE, $TYPE): int
{
    global $db_account;
    $count_data = $db_account->Execute("SELECT * FROM DOA_APPOINTMENT_ENROLLMENT WHERE PK_APPOINTMENT_MASTER = $PK_APPOINTMENT_MASTER AND PK_USER_MASTER = $PK_USER_MASTER AND PK_ENROLLMENT_SERVICE = $PK_ENROLLMENT_SERVICE AND TYPE = '$TYPE'");
    if ($count_data->RecordCount() > 0) {
        return 1;
    } else {
        $APPOINTMENT_ENROLLMENT_DATA['PK_APPOINTMENT_MASTER'] = $PK_APPOINTMENT_MASTER;
        $APPOINTMENT_ENROLLMENT_DATA['PK_USER_MASTER'] = $PK_USER_MASTER;
        $APPOINTMENT_ENROLLMENT_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
        $APPOINTMENT_ENROLLMENT_DATA['PK_ENROLLMENT_SERVICE'] = $PK_ENROLLMENT_SERVICE;
        $APPOINTMENT_ENROLLMENT_DATA['TYPE'] = $TYPE;
        db_perform_account('DOA_APPOINTMENT_ENROLLMENT', $APPOINTMENT_ENROLLMENT_DATA, 'insert');
        return 0;
    }
}

function markEnrollmentComplete($PK_ENROLLMENT_MASTER)
{
    global $db_account;
    $enrollment_count_data = $db_account->Execute("SELECT SUM(`NUMBER_OF_SESSION`) AS TOTAL_SESSION, SUM(`SESSION_COMPLETED`) AS COMPLETED_SESSION FROM `DOA_ENROLLMENT_SERVICE` WHERE `PK_ENROLLMENT_MASTER` = '$PK_ENROLLMENT_MASTER'");
    $details = $db_account->Execute("SELECT PK_ENROLLMENT_LEDGER FROM `DOA_ENROLLMENT_LEDGER` WHERE DOA_ENROLLMENT_LEDGER.IS_PAID = 0 AND PK_ENROLLMENT_MASTER = ".$PK_ENROLLMENT_MASTER);
    $paid_count = $details->RecordCount() > 0 ? 1 : 0;
    if (($enrollment_count_data->fields['TOTAL_SESSION'] == $enrollment_count_data->fields['COMPLETED_SESSION']) && ($paid_count === 0)) {
        $ENR_UPDATE_DATA['ALL_APPOINTMENT_DONE'] = 1;
        db_perform_account('DOA_ENROLLMENT_MASTER', $ENR_UPDATE_DATA, 'update', " PK_ENROLLMENT_MASTER = " . $PK_ENROLLMENT_MASTER);
    }
}

function getAppointmentSerialNumber($PK_USER_MASTER){
    global $db_account;
    $appointment_data = $db_account->Execute("SELECT MAX(SERIAL_NUMBER) AS SERIAL_NUMBER FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_APPOINTMENT_CUSTOMER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER  WHERE DOA_APPOINTMENT_CUSTOMER.PK_USER_MASTER = '$PK_USER_MASTER'");
    if ($appointment_data->RecordCount() > 0) {
        return $appointment_data->fields['SERIAL_NUMBER'] + 1;
    } else {
        return 1;
    }
}

function getAccessToken()
{
    global $db;
    $account_data = $db->Execute("SELECT * FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
    $client_id = constant('client_id');
    $client_secret = constant('client_secret');
    $ami_api_url = constant('ami_api_url').'/oauth/v2/token';

    $AM_USER_NAME = $account_data->fields['AM_USER_NAME'];
    $AM_PASSWORD = $account_data->fields['AM_PASSWORD'];
    $AM_REFRESH_TOKEN = $account_data->fields['AM_REFRESH_TOKEN'];

    $user_credential = [
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'grant_type' => 'password',
        'username' => $AM_USER_NAME,
        'password' => $AM_PASSWORD
    ];

    $params = http_build_query($user_credential);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $ami_api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

    $response = curl_exec($ch);
    if(curl_errno($ch)){
        echo 'Curl error: ' . curl_error($ch);
    }
    curl_close($ch);

    return json_decode($response)->access_token;
}

function getStaffCode($access_token, $first_name, $last_name)
{
    $url = constant('ami_api_url').'/api/v1/staff';

    $user_details = [
        'first_name' => $first_name,
        'last_name' => $last_name
    ];
    $url .= '?' . http_build_query($user_details);

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            $access_token
        ),
    ));

    $response = curl_exec($curl);
    $data = json_decode($response, true);

    return $data[0]['id'] ?? '';
}

function callArturMurrayApi(string $url, array $data, string $access_token)
{
    $curl = curl_init();

    $param = http_build_query($data);

    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $param,
        CURLOPT_HTTPHEADER => array(
            $access_token
        ),
    ));

    $response = curl_exec($curl);
    if(curl_errno($curl)){
        echo 'Curl error: ' . curl_error($curl);
    }
    curl_close($curl);

    return $response;
}
