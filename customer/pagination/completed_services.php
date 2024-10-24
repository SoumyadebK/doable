<table id="myTable" class="table table-striped border" style="margin: auto; width: 40%;">
    <thead>
        <tr>
            <th style="text-align: center;">Service Code</th>
            <th style="text-align: center;">Enroll</th>
            <th style="text-align: center;">Remain</th>
            <th style="text-align: center;">Used</th>
            <th style="text-align: center;">Balance</th>
            <th style="text-align: center;">Paid</th>
        </tr>
    </thead>

    <tbody>
    <?php
    $pending_service_data = $db_account->Execute("SELECT DOA_ENROLLMENT_SERVICE.*, DOA_SERVICE_CODE.SERVICE_CODE FROM DOA_ENROLLMENT_SERVICE LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER JOIN DOA_SERVICE_CODE ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE WHERE (DOA_ENROLLMENT_MASTER.STATUS = 'CO' OR DOA_ENROLLMENT_MASTER.STATUS = 'C') AND DOA_ENROLLMENT_MASTER.PK_USER_MASTER = ".$PK_USER_MASTER);
    $pending_service_code_array = [];
    while (!$pending_service_data->EOF) {
        //$SESSION_COMPLETED = getSessionCompletedCount($pending_service_data->fields['PK_ENROLLMENT_SERVICE']);
        //$PRICE_PER_SESSION = $pending_service_data->fields['PRICE_PER_SESSION'];
        //$used_session_count = $db_account->Execute("SELECT COUNT(`PK_ENROLLMENT_MASTER`) AS USED_SESSION_COUNT FROM `DOA_APPOINTMENT_MASTER` WHERE PK_APPOINTMENT_STATUS = 2 AND `PK_ENROLLMENT_MASTER` = ".$pending_service_data->fields['PK_ENROLLMENT_MASTER']." AND PK_SERVICE_CODE = ".$pending_service_data->fields['PK_SERVICE_CODE']);
        //$paid_session = ($PRICE_PER_SESSION > 0) ? number_format($pending_service_data->fields['TOTAL_AMOUNT_PAID']/$PRICE_PER_SESSION, 2) : $pending_service_data->fields['NUMBER_OF_SESSION'];
        //$remain_session = $pending_service_data->fields['NUMBER_OF_SESSION'] - $SESSION_COMPLETED;
        //$ps_balance = $paid_session - $SESSION_COMPLETED;

        //if ($remain_session <= 0) {
            if (isset($pending_service_code_array[$pending_service_data->fields['SERVICE_CODE']])) {
                $pending_service_code_array[$pending_service_data->fields['SERVICE_CODE']]['CODE'] = $pending_service_data->fields['SERVICE_CODE'];
                $pending_service_code_array[$pending_service_data->fields['SERVICE_CODE']]['ENROLL'] += $pending_service_data->fields['NUMBER_OF_SESSION'];
                //$pending_service_code_array[$pending_service_data->fields['SERVICE_CODE']]['REMAIN'] += $remain_session;
                $pending_service_code_array[$pending_service_data->fields['SERVICE_CODE']]['PAID'] += $pending_service_data->fields['TOTAL_AMOUNT_PAID'];
                //$pending_service_code_array[$pending_service_data->fields['SERVICE_CODE']]['USED'] += $pending_service_data->fields['NUMBER_OF_SESSION']; //$SESSION_COMPLETED;
                //$pending_service_code_array[$pending_service_data->fields['SERVICE_CODE']]['BALANCE'] += $ps_balance;
            } else {
                $pending_service_code_array[$pending_service_data->fields['SERVICE_CODE']]['CODE'] = $pending_service_data->fields['SERVICE_CODE'];
                $pending_service_code_array[$pending_service_data->fields['SERVICE_CODE']]['ENROLL'] = $pending_service_data->fields['NUMBER_OF_SESSION'];
                //$pending_service_code_array[$pending_service_data->fields['SERVICE_CODE']]['REMAIN'] = $remain_session;
                $pending_service_code_array[$pending_service_data->fields['SERVICE_CODE']]['PAID'] = $pending_service_data->fields['TOTAL_AMOUNT_PAID'];
                //$pending_service_code_array[$pending_service_data->fields['SERVICE_CODE']]['USED'] = $SESSION_COMPLETED;
                //$pending_service_code_array[$pending_service_data->fields['SERVICE_CODE']]['BALANCE'] = $ps_balance;
            }
        //}

        $pending_service_data->MoveNext();
    } ?>
    <?php foreach ($pending_service_code_array AS $service_code) { ?>
        <tr>
            <td style="text-align: center;"><?=$service_code['CODE']?></td>
            <td style="text-align: center;"><?=$service_code['ENROLL']?></td>
            <td style="text-align: center;">0</td>
            <td style="text-align: center;"><?=$service_code['ENROLL']?></td>
            <td style="text-align: center;">0</td>
            <td style="text-align: center;">$<?=number_format($service_code['PAID'], 2)?></td>
        </tr>
    <?php } ?>
    </tbody>
</table>