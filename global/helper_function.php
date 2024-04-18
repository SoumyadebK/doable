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

        $db_account->Execute("UPDATE `DOA_APPOINTMENT_MASTER` LEFT JOIN DOA_APPOINTMENT_CUSTOMER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER SET `PK_ENROLLMENT_MASTER` = '$PK_ENROLLMENT_MASTER',`PK_ENROLLMENT_SERVICE` = '$PK_ENROLLMENT_SERVICE', `APPOINTMENT_TYPE` = 'NORMAL', `SERIAL_NUMBER` = '$SERIAL_NUMBER' WHERE DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER = '$PK_SERVICE_MASTER' AND DOA_APPOINTMENT_MASTER.PK_SERVICE_CODE = '$PK_SERVICE_CODE' AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE = 'AD-HOC' AND DOA_APPOINTMENT_CUSTOMER.PK_USER_MASTER = '$PK_USER_MASTER'");
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
    $serviceCodeData = $db_account->Execute("SELECT DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_SERVICE, DOA_ENROLLMENT_SERVICE.SESSION_COMPLETED, DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, DOA_ENROLLMENT_MASTER.PK_USER_MASTER FROM DOA_ENROLLMENT_SERVICE JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER WHERE PK_ENROLLMENT_SERVICE = ".$PK_ENROLLMENT_SERVICE);
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

function callArturMurrayApi(string $url, array $data, string $method, string $access_token = '')
{
    $params = http_build_query($data);
    pre_r($data);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return response as a string
    curl_setopt($ch, CURLOPT_POST, true); // Use custom request method
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params); // Set request body data
    curl_setopt($ch, CURLOPT_HTTPHEADER, array($access_token));

    $response = curl_exec($ch);
    if(curl_errno($ch)){
        echo 'Curl error: ' . curl_error($ch);
    }
    curl_close($ch);

    return $response;
}
