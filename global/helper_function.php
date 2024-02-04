<?php
function markAppointmentPaid($PK_ENROLLMENT_SERVICE)
{
    global $db_account;
    $serviceCodeData = $db_account->Execute("SELECT PK_ENROLLMENT_SERVICE, NUMBER_OF_SESSION, TOTAL_AMOUNT_PAID, PRICE_PER_SESSION FROM DOA_ENROLLMENT_SERVICE WHERE PK_ENROLLMENT_SERVICE = ".$PK_ENROLLMENT_SERVICE);
    if ($serviceCodeData->RecordCount() > 0) {
        $paid_session = ($serviceCodeData->fields['PRICE_PER_SESSION'] > 0) ? ceil($serviceCodeData->fields['TOTAL_AMOUNT_PAID'] / $serviceCodeData->fields['PRICE_PER_SESSION']) : 0;
        if ($paid_session >= 1) {
            $db_account->Execute("UPDATE `DOA_APPOINTMENT_MASTER` SET `IS_PAID` = '1' WHERE APPOINTMENT_TYPE = 'NORMAL' AND PK_ENROLLMENT_SERVICE = '$PK_ENROLLMENT_SERVICE' LIMIT $paid_session ORDER BY DATE DESC");
        }
    }
}
