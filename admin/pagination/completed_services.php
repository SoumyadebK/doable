<table id="myTable" class="table table-striped border" style="margin: auto; width: 50%;">
    <thead>
        <tr>
            <th style="text-align: center;">Service Code</th>
            <th style="text-align: center;">Enroll</th>
            <th style="text-align: center;">Used</th>
            <th style="text-align: center;">Scheduled</th>
            <th style="text-align: center;">Remain</th>
            <th style="text-align: center;">Balance</th>
            <th style="text-align: center;">Paid</th>
        </tr>
    </thead>

    <tbody>
        <?php
        $completed_service_data = $db_account->Execute("SELECT DOA_ENROLLMENT_SERVICE.*, DOA_SERVICE_CODE.SERVICE_CODE, DOA_ENROLLMENT_MASTER.CHARGE_TYPE FROM DOA_ENROLLMENT_SERVICE LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER JOIN DOA_SERVICE_CODE ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE WHERE (DOA_ENROLLMENT_MASTER.STATUS = 'CO' OR DOA_ENROLLMENT_MASTER.STATUS = 'C') AND DOA_ENROLLMENT_MASTER.PK_USER_MASTER = " . $PK_USER_MASTER);
        $completed_service_code_array = [];
        while (!$completed_service_data->EOF) {
            if ($completed_service_data->fields['CHARGE_TYPE'] == 'Membership') {
                $NUMBER_OF_SESSION = getSessionCreatedCount($completed_service_data->fields['PK_ENROLLMENT_SERVICE']);
            } else {
                $NUMBER_OF_SESSION = ($completed_service_data->fields['NUMBER_OF_SESSION'] > 0) ? $completed_service_data->fields['NUMBER_OF_SESSION'] : 0;
            }
            $SESSION_SCHEDULED = getSessionScheduledCount($completed_service_data->fields['PK_ENROLLMENT_SERVICE']);
            $SESSION_COMPLETED = getSessionCompletedCount($completed_service_data->fields['PK_ENROLLMENT_SERVICE']);
            $PRICE_PER_SESSION = $completed_service_data->fields['PRICE_PER_SESSION'];
            $paid_session = ($PRICE_PER_SESSION > 0) ? number_format($completed_service_data->fields['TOTAL_AMOUNT_PAID'] / $PRICE_PER_SESSION, 2) : $NUMBER_OF_SESSION;
            $remain_session = $NUMBER_OF_SESSION - ($SESSION_COMPLETED + $SESSION_SCHEDULED);
            $ps_balance = $paid_session - $SESSION_COMPLETED;

            //if ($remain_session > 0) {
            if (isset($completed_service_code_array[$completed_service_data->fields['SERVICE_CODE']])) {
                $completed_service_code_array[$completed_service_data->fields['SERVICE_CODE']]['CODE'] = $completed_service_data->fields['SERVICE_CODE'];
                $completed_service_code_array[$completed_service_data->fields['SERVICE_CODE']]['ENROLL'] += $NUMBER_OF_SESSION;
                $completed_service_code_array[$completed_service_data->fields['SERVICE_CODE']]['REMAIN'] += $remain_session;
                $completed_service_code_array[$completed_service_data->fields['SERVICE_CODE']]['PAID'] += $completed_service_data->fields['TOTAL_AMOUNT_PAID'];
                $completed_service_code_array[$completed_service_data->fields['SERVICE_CODE']]['USED'] += $SESSION_COMPLETED;
                $completed_service_code_array[$completed_service_data->fields['SERVICE_CODE']]['SCHEDULED'] += $SESSION_SCHEDULED;
                $completed_service_code_array[$completed_service_data->fields['SERVICE_CODE']]['BALANCE'] += $ps_balance;
            } else {
                $completed_service_code_array[$completed_service_data->fields['SERVICE_CODE']]['CODE'] = $completed_service_data->fields['SERVICE_CODE'];
                $completed_service_code_array[$completed_service_data->fields['SERVICE_CODE']]['ENROLL'] = $NUMBER_OF_SESSION;
                $completed_service_code_array[$completed_service_data->fields['SERVICE_CODE']]['REMAIN'] = $remain_session;
                $completed_service_code_array[$completed_service_data->fields['SERVICE_CODE']]['PAID'] = $completed_service_data->fields['TOTAL_AMOUNT_PAID'];
                $completed_service_code_array[$completed_service_data->fields['SERVICE_CODE']]['USED'] = $SESSION_COMPLETED;
                $completed_service_code_array[$completed_service_data->fields['SERVICE_CODE']]['SCHEDULED'] = $SESSION_SCHEDULED;
                $completed_service_code_array[$completed_service_data->fields['SERVICE_CODE']]['BALANCE'] = $ps_balance;
            }
            //}

            $completed_service_data->MoveNext();
        } ?>
        <?php foreach ($completed_service_code_array as $service_code) { ?>
            <tr>
                <td style="text-align: center;"><?= $service_code['CODE'] ?></td>
                <td style="text-align: center;"><?= $service_code['ENROLL'] ?></td>
                <td style="text-align: center;"><?= $service_code['USED'] ?></td>
                <td style="text-align: center;"><?= $service_code['SCHEDULED'] ?></td>
                <td style="text-align: center;"><?= $service_code['REMAIN'] ?></td>
                <td style="text-align: center; color:<?= ($service_code['BALANCE'] < 0) ? 'red' : 'black' ?>;"><?= $service_code['BALANCE'] ?></td>
                <td style="text-align: center;">$<?= number_format($service_code['PAID'], 2) ?></td>
            </tr>
        <?php } ?>
    </tbody>
</table>