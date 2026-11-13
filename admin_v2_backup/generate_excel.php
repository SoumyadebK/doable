<?php
require_once('../global/config.php');

$row = $db->Execute("SELECT DOA_USERS.LAST_NAME, DOA_USERS.FIRST_NAME, CONCAT(DOA_CUSTOMER_DETAILS.PARTNER_FIRST_NAME, ' ', DOA_CUSTOMER_DETAILS.PARTNER_LAST_NAME) AS PARTNER_NAME, DOA_USER_PROFILE.ADDRESS, DOA_USER_PROFILE.CITY, DOA_STATES.STATE_NAME, DOA_USER_PROFILE.ZIP, DOA_CUSTOMER_DETAILS.EMAIL, DOA_USER_PROFILE.ACTIVE FROM DOA_USERS INNER JOIN DOA_USER_PROFILE ON DOA_USERS.PK_USER=DOA_USER_PROFILE.PK_USER INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER=DOA_USER_MASTER.PK_USER LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER INNER JOIN DOA_CUSTOMER_DETAILS ON DOA_CUSTOMER_DETAILS.PK_USER_MASTER=DOA_USER_MASTER.PK_USER_MASTER INNER JOIN DOA_STATES ON DOA_USER_PROFILE.PK_STATES=DOA_STATES.PK_STATES WHERE DOA_USER_ROLES.PK_ROLES=4");
if ($row->fields['ACTIVE']==1) {
    $STATUS = "Active";
}
while (!$row->EOF) {
    $tasks[] =[
        'LAST_NAME'=>$row->fields['LAST_NAME'],
        'FIRST_NAME'=>$row->fields['FIRST_NAME'],
        'PARTNER_NAME'=>$row->fields['PARTNER_NAME'],
        'ADDRESS'=>$row->fields['ADDRESS'],
        'CITY'=>$row->fields['CITY'],
        'STATE_NAME'=>$row->fields['STATE_NAME'],
        'ZIP'=>$row->fields['ZIP'],
        'EMAIL'=>$row->fields['EMAIL'],
        'STATUS'=>$STATUS
    ];
    $row->MoveNext();
}
//pre_r($tasks);

if(isset($_POST["ExportType"]))
{
    $filename = "Student Mailing List ".date('Y-m-d') . ".xls";
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    ExportFile($tasks);
}
function ExportFile($records) {
    $heading = false;
    if(!empty($records))
        foreach($records as $row) {
            if(!$heading) {
                // display field/column names as a first row
                echo implode("\t", array_keys($row)) . "\n";
                $heading = true;
            }
            echo implode("\t", array_values($row)) . "\n";
        }
    exit;
}
?>