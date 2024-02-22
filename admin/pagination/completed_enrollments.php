<?php
require_once('../../global/config.php');
global $db;
global $db_account;
global $master_database;
global $results_per_page;

$PK_USER_MASTER = !empty($_GET['master_id']) ? $_GET['master_id'] : 0;
$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];

?>
<table id="myTable" class="table table-striped border" style="margin: auto; width: 40%;">
    <thead>
        <tr>
            <th style="text-align: center;">Service Code</th>
            <th style="text-align: center;">Enroll</th>
            <th style="text-align: center;">Remain</th>
            <th style="text-align: center;">Paid</th>
        </tr>
    </thead>

    <tbody>
    <?php
    $completed_service_data = $db_account->Execute("SELECT DOA_ENROLLMENT_SERVICE.*, DOA_SERVICE_CODE.SERVICE_CODE FROM DOA_ENROLLMENT_SERVICE LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER JOIN DOA_SERVICE_CODE ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE WHERE DOA_ENROLLMENT_MASTER.PK_USER_MASTER = ".$PK_USER_MASTER);
    $complete_service_code_array = [];
    while (!$completed_service_data->EOF) {
        $PRICE_PER_SESSION = $completed_service_data->fields['PRICE_PER_SESSION'];
        $used_session_count = $db_account->Execute("SELECT COUNT(`PK_ENROLLMENT_MASTER`) AS USED_SESSION_COUNT FROM `DOA_APPOINTMENT_MASTER` WHERE PK_APPOINTMENT_STATUS = 2 AND `PK_ENROLLMENT_MASTER` = ".$completed_service_data->fields['PK_ENROLLMENT_MASTER']." AND PK_SERVICE_CODE = ".$completed_service_data->fields['PK_SERVICE_CODE']);
        $paid_session = ($PRICE_PER_SESSION > 0) ? number_format($completed_service_data->fields['TOTAL_AMOUNT_PAID']/$PRICE_PER_SESSION, 2) : 0;
        $remain_session = $completed_service_data->fields['NUMBER_OF_SESSION'] - $used_session_count->fields['USED_SESSION_COUNT'];

        if ($remain_session <= 0) {
            if (isset($complete_service_code_array[$completed_service_data->fields['SERVICE_CODE']])) {
                $complete_service_code_array[$completed_service_data->fields['SERVICE_CODE']]['CODE'] = $completed_service_data->fields['SERVICE_CODE'];
                $complete_service_code_array[$completed_service_data->fields['SERVICE_CODE']]['ENROLL'] += $completed_service_data->fields['NUMBER_OF_SESSION'];
                $complete_service_code_array[$completed_service_data->fields['SERVICE_CODE']]['REMAIN'] += $remain_session;
                $complete_service_code_array[$completed_service_data->fields['SERVICE_CODE']]['PAID'] += $paid_session;
            } else {
                $complete_service_code_array[$completed_service_data->fields['SERVICE_CODE']]['CODE'] = $completed_service_data->fields['SERVICE_CODE'];
                $complete_service_code_array[$completed_service_data->fields['SERVICE_CODE']]['ENROLL'] = $completed_service_data->fields['NUMBER_OF_SESSION'];
                $complete_service_code_array[$completed_service_data->fields['SERVICE_CODE']]['REMAIN'] = $remain_session;
                $complete_service_code_array[$completed_service_data->fields['SERVICE_CODE']]['PAID'] = $paid_session;
            }
        }

        $completed_service_data->MoveNext();
    } ?>
    <?php foreach ($complete_service_code_array AS $service_code) { ?>
        <tr>
            <td style="text-align: center;"><?=$service_code['CODE']?></td>
            <td style="text-align: center;"><?=$service_code['ENROLL']?></td>
            <td style="text-align: center;"><?=$service_code['REMAIN']?></td>
            <td style="text-align: center;"><?=$service_code['PAID']?></td>
        </tr>
    <?php } ?>
    </tbody>
</table>