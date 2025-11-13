<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;

$PK_USER = $_GET['PK_USER'] ?? '';
$PK_USER_MASTER = $_GET['PK_USER_MASTER'] ?? '';

$db_account->Execute("UPDATE DOA_APPOINTMENT_MASTER am JOIN DOA_APPOINTMENT_CUSTOMER ac ON am.PK_APPOINTMENT_MASTER = ac.PK_APPOINTMENT_MASTER SET am.PK_ENROLLMENT_MASTER = 0, am.PK_ENROLLMENT_SERVICE = 0, am.APPOINTMENT_TYPE = 'AD-HOC' WHERE am.APPOINTMENT_TYPE = 'NORMAL' AND ac.PK_USER_MASTER = '$PK_USER_MASTER'");
$enrollment_data = $db_account->Execute("SELECT PK_ENROLLMENT_MASTER FROM DOA_ENROLLMENT_MASTER WHERE PK_USER_MASTER = '$PK_USER_MASTER' ORDER BY ENROLLMENT_DATE ASC");
while (!$enrollment_data->EOF) {
    $PK_ENROLLMENT_MASTER = $enrollment_data->fields['PK_ENROLLMENT_MASTER'];

    /* $ENR_UPDATE_DATA['ALL_APPOINTMENT_DONE'] = 0;
    $ENR_UPDATE_DATA['STATUS'] = 'A';
    db_perform_account('DOA_ENROLLMENT_MASTER', $ENR_UPDATE_DATA, 'update', " PK_ENROLLMENT_MASTER = " . $PK_ENROLLMENT_MASTER);

    db_perform_account('DOA_ENROLLMENT_SERVICE', ['STATUS' => $ENR_UPDATE_DATA['STATUS']], 'update', " PK_ENROLLMENT_MASTER = " . $PK_ENROLLMENT_MASTER);
    db_perform_account('DOA_ENROLLMENT_LEDGER', ['STATUS' => $ENR_UPDATE_DATA['STATUS']], 'update', " PK_ENROLLMENT_MASTER = " . $PK_ENROLLMENT_MASTER); */

    /* $enrollment_service_count = $db_account->Execute("SELECT COUNT(PK_ENROLLMENT_SERVICE) AS TOTAL_SERVICE FROM DOA_ENROLLMENT_SERVICE WHERE PK_ENROLLMENT_MASTER = '$PK_ENROLLMENT_MASTER'");
    $TOTAL_SERVICE = $enrollment_service_count->fields['TOTAL_SERVICE'];
    if ($TOTAL_SERVICE > 1) {
        $max_session_service = $db_account->Execute("SELECT es.NUMBER_OF_SESSION, sc.IS_GROUP FROM DOA_ENROLLMENT_SERVICE es INNER JOIN DOA_SERVICE_CODE sc ON es.PK_SERVICE_CODE = sc.PK_SERVICE_CODE WHERE es.PK_ENROLLMENT_MASTER = '$PK_ENROLLMENT_MASTER' ORDER BY es.NUMBER_OF_SESSION DESC LIMIT 1");
        if ($max_session_service->fields['IS_GROUP'] == 0) {
            $enrollment_service_data = $db_account->Execute("SELECT * FROM DOA_ENROLLMENT_SERVICE WHERE PK_ENROLLMENT_MASTER = '$PK_ENROLLMENT_MASTER' AND NUMBER_OF_SESSION < (SELECT MAX(NUMBER_OF_SESSION) FROM DOA_ENROLLMENT_SERVICE WHERE PK_ENROLLMENT_MASTER = '$PK_ENROLLMENT_MASTER') ORDER BY NUMBER_OF_SESSION DESC");
            while (!$enrollment_service_data->EOF) {
                $PK_ENROLLMENT_SERVICE = $enrollment_service_data->fields['PK_ENROLLMENT_SERVICE'];
                $ENR_UPDATE_SERVICE_DATA['PRICE_PER_SESSION'] = 0;
                $ENR_UPDATE_SERVICE_DATA['TOTAL'] = 0.00;
                $ENR_UPDATE_SERVICE_DATA['TOTAL_AMOUNT_PAID'] = 0.00;
                $ENR_UPDATE_SERVICE_DATA['DISCOUNT'] = 0.00;
                $ENR_UPDATE_SERVICE_DATA['DISCOUNT_TYPE'] = 0;
                $ENR_UPDATE_SERVICE_DATA['FINAL_AMOUNT'] = 0.00;
                db_perform_account('DOA_ENROLLMENT_SERVICE', $ENR_UPDATE_SERVICE_DATA, 'update', " PK_ENROLLMENT_SERVICE = " . $PK_ENROLLMENT_SERVICE);
                $enrollment_service_data->MoveNext();
            }
        }
    } */

    //echo $PK_ENROLLMENT_MASTER . "<br>";

    markAdhocAppointmentNormal($PK_ENROLLMENT_MASTER);

    $enrollment_data->MoveNext();
}

header("location: customer.php?id=$PK_USER&master_id=$PK_USER_MASTER");
exit;
