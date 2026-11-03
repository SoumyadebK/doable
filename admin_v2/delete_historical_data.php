<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4])) {
    header("location:../login.php");
    exit;
}

$user_list = $db_account->Execute("SELECT DISTINCT(PK_USER_MASTER) AS PK_USER_MASTER FROM `DOA_ENROLLMENT_MASTER` WHERE `ENROLLMENT_DATE` <= '2016-12-31'");

while (!$user_list->EOF) {
    $PK_USER_MASTER = $user_list->fields['PK_USER_MASTER'];
    $user_data = $db->Execute("SELECT PK_USER FROM DOA_USER_MASTER WHERE PK_USER_MASTER = '$PK_USER_MASTER'");
    $PK_USER = $user_data->fields['PK_USER'];

    $db_account->Execute("UPDATE DOA_APPOINTMENT_MASTER am JOIN DOA_APPOINTMENT_CUSTOMER ac ON am.PK_APPOINTMENT_MASTER = ac.PK_APPOINTMENT_MASTER SET am.PK_ENROLLMENT_MASTER = 0, am.PK_ENROLLMENT_SERVICE = 0, am.APPOINTMENT_TYPE = 'AD-HOC' WHERE am.APPOINTMENT_TYPE = 'NORMAL' AND ac.PK_USER_MASTER = '$PK_USER_MASTER'");
    $enrollment_data = $db_account->Execute("SELECT PK_ENROLLMENT_MASTER FROM DOA_ENROLLMENT_MASTER WHERE PK_USER_MASTER = '$PK_USER_MASTER' ORDER BY ENROLLMENT_DATE ASC");
    while (!$enrollment_data->EOF) {
        $PK_ENROLLMENT_MASTER = $enrollment_data->fields['PK_ENROLLMENT_MASTER'];

        markAdhocAppointmentNormal($PK_ENROLLMENT_MASTER);

        $enrollment_data->MoveNext();
    }

    makeExpiryEnrollmentComplete($PK_USER_MASTER);
    makeMiscComplete($PK_USER_MASTER);
    makeDroppedCancelled($PK_USER_MASTER);
    checkAllEnrollmentStatus($PK_USER_MASTER);


    $completed_service_data = $db_account->Execute("SELECT DOA_ENROLLMENT_SERVICE.*, DOA_SERVICE_CODE.SERVICE_CODE, DOA_ENROLLMENT_MASTER.CHARGE_TYPE FROM DOA_ENROLLMENT_SERVICE LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER JOIN DOA_SERVICE_CODE ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE WHERE DOA_ENROLLMENT_MASTER.ENROLLMENT_DATE <= '2016-12-31' AND DOA_ENROLLMENT_MASTER.PK_USER_MASTER = " . $PK_USER_MASTER);
    $completed_service_code_array = [];
    while (!$completed_service_data->EOF) {
        if ($completed_service_data->fields['CHARGE_TYPE'] == 'Membership') {
            $NUMBER_OF_SESSION = getSessionCreatedCount($completed_service_data->fields['PK_ENROLLMENT_SERVICE']);
        } else {
            $NUMBER_OF_SESSION = $completed_service_data->fields['NUMBER_OF_SESSION'];
        }

        if (isset($completed_service_code_array[$completed_service_data->fields['SERVICE_CODE']])) {
            $completed_service_code_array[$completed_service_data->fields['SERVICE_CODE']]['CODE'] = $completed_service_data->fields['SERVICE_CODE'];
            $completed_service_code_array[$completed_service_data->fields['SERVICE_CODE']]['ENROLL'] += $NUMBER_OF_SESSION;
        } else {
            $completed_service_code_array[$completed_service_data->fields['SERVICE_CODE']]['CODE'] = $completed_service_data->fields['SERVICE_CODE'];
            $completed_service_code_array[$completed_service_data->fields['SERVICE_CODE']]['ENROLL'] = $NUMBER_OF_SESSION;
        }

        $completed_service_data->MoveNext();
    }

    foreach ($completed_service_code_array as $service_code) {
        if ($service_code['ENROLL'] > 0) {
            $COMMENT_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
            $COMMENT_DATA['COMMENT'] = "ENROLLMENT PRIOR TO 2017 \n " . $service_code['CODE'] . " - " . $service_code['ENROLL'] . "";
            $COMMENT_DATA['COMMENT_DATE'] = date("Y-m-d");
            $COMMENT_DATA['FOR_PK_USER'] = $PK_USER;
            $COMMENT_DATA['BY_PK_USER']  = $_SESSION['PK_USER'];
            $COMMENT_DATA['ACTIVE'] = 1;
            $COMMENT_DATA['CREATED_ON']  = date("Y-m-d H:i");
            $COMMENT_DATA['CREATED_BY']  = $_SESSION['PK_USER'];
            db_perform_account('DOA_COMMENT', $COMMENT_DATA, 'insert');
        }
    }

    $enrollment_list = $db_account->Execute("SELECT PK_ENROLLMENT_MASTER FROM `DOA_ENROLLMENT_MASTER` WHERE `ENROLLMENT_DATE` <= '2016-12-31' AND PK_USER_MASTER = " . $PK_USER_MASTER);
    while (!$enrollment_list->EOF) {
        $PK_ENROLLMENT_MASTER = $enrollment_list->fields['PK_ENROLLMENT_MASTER'];

        $db_account->Execute("DELETE DOA_APPOINTMENT_CUSTOMER FROM DOA_APPOINTMENT_CUSTOMER LEFT JOIN DOA_APPOINTMENT_MASTER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER=DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_MASTER = " . $PK_ENROLLMENT_MASTER);
        $db_account->Execute("DELETE DOA_APPOINTMENT_SERVICE_PROVIDER FROM DOA_APPOINTMENT_SERVICE_PROVIDER LEFT JOIN DOA_APPOINTMENT_MASTER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER=DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_MASTER = " . $PK_ENROLLMENT_MASTER);
        $db_account->Execute("DELETE DOA_APPOINTMENT_STATUS_HISTORY FROM DOA_APPOINTMENT_STATUS_HISTORY LEFT JOIN DOA_APPOINTMENT_MASTER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER=DOA_APPOINTMENT_STATUS_HISTORY.PK_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_MASTER = " . $PK_ENROLLMENT_MASTER);
        $db_account->Execute("DELETE FROM `DOA_ENROLLMENT_BALANCE` WHERE `PK_ENROLLMENT_MASTER` = " . $PK_ENROLLMENT_MASTER);
        $db_account->Execute("DELETE FROM `DOA_ENROLLMENT_BILLING` WHERE `PK_ENROLLMENT_MASTER` = " . $PK_ENROLLMENT_MASTER);
        $db_account->Execute("DELETE FROM `DOA_ENROLLMENT_LEDGER` WHERE `PK_ENROLLMENT_MASTER` = " . $PK_ENROLLMENT_MASTER);
        $db_account->Execute("DELETE FROM `DOA_ENROLLMENT_MASTER` WHERE `PK_ENROLLMENT_MASTER` = " . $PK_ENROLLMENT_MASTER);
        $db_account->Execute("DELETE FROM `DOA_ENROLLMENT_PAYMENT` WHERE `PK_ENROLLMENT_MASTER` = " . $PK_ENROLLMENT_MASTER);
        $db_account->Execute("DELETE FROM `DOA_ENROLLMENT_SERVICE` WHERE `PK_ENROLLMENT_MASTER` = " . $PK_ENROLLMENT_MASTER);
        $db_account->Execute("DELETE FROM `DOA_ENROLLMENT_SERVICE_PROVIDER` WHERE `PK_ENROLLMENT_MASTER` = " . $PK_ENROLLMENT_MASTER);
        $db_account->Execute("DELETE FROM DOA_APPOINTMENT_ENROLLMENT WHERE PK_ENROLLMENT_MASTER = " . $PK_ENROLLMENT_MASTER);
        $db_account->Execute("DELETE FROM DOA_APPOINTMENT_MASTER WHERE PK_ENROLLMENT_MASTER = " . $PK_ENROLLMENT_MASTER);

        addEnrollmentLogData($PK_ENROLLMENT_MASTER, 'Deleted', 'Enrollment deleted from historical data deletion script');

        $enrollment_list->MoveNext();
    }

    $user_list->MoveNext();
}

echo "Done all historical data deletion.";
