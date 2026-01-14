<?php
require_once('../../global/config.php');
global $db;
global $db_account;
global $master_database;
global $results_per_page;

$PK_ENROLLMENT_MASTER = $_GET['PK_ENROLLMENT_MASTER'];
$USE_AVAILABLE_CREDIT = $_GET['USE_AVAILABLE_CREDIT'];
$CANCEL_FUTURE_APPOINTMENT = $_GET['CANCEL_FUTURE_APPOINTMENT'];
?>

<table id="myTable" class="table table-striped border">
    <thead>
        <tr>
            <th></th>
            <th style="text-align: right;">Enrolled</th>
            <th style="text-align: right;">Used</th>
            <th style="text-align: right;">Balance</th>
            <th style="text-align: right;">Paid</th>
            <th style="text-align: right;">Service Credit</th>
        </tr>
    </thead>

    <tbody>
        <?php
        $serviceCodeData = $db_account->Execute("SELECT DOA_ENROLLMENT_SERVICE.*, DOA_SERVICE_CODE.PK_SERVICE_CODE, DOA_SERVICE_CODE.SERVICE_CODE FROM DOA_ENROLLMENT_SERVICE JOIN DOA_SERVICE_CODE ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE WHERE DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = " . $PK_ENROLLMENT_MASTER);
        $total_amount = 0;
        $total_paid_amount = 0;
        $total_used_amount = 0;
        $service_code_array = [];
        while (!$serviceCodeData->EOF) {
            $SESSION_CREATED = getSessionCreatedCount($serviceCodeData->fields['PK_ENROLLMENT_SERVICE']);
            $SESSION_COMPLETED = getSessionCompletedCount($serviceCodeData->fields['PK_ENROLLMENT_SERVICE']);
            if ($CANCEL_FUTURE_APPOINTMENT == 1 || $CANCEL_FUTURE_APPOINTMENT == 3) {
                $used_session_amount = $SESSION_COMPLETED * $serviceCodeData->fields['PRICE_PER_SESSION'];
            } else {
                $session_created_amount = $SESSION_CREATED * $serviceCodeData->fields['PRICE_PER_SESSION'];
                $session_completed_amount = $SESSION_COMPLETED * $serviceCodeData->fields['PRICE_PER_SESSION'];
                $used_session_amount = (($session_completed_amount > $serviceCodeData->fields['TOTAL_AMOUNT_PAID']) ? $session_completed_amount : (($session_created_amount > $serviceCodeData->fields['TOTAL_AMOUNT_PAID']) ? $serviceCodeData->fields['TOTAL_AMOUNT_PAID'] : $session_created_amount));
            }
            $service_code_array[$serviceCodeData->fields['PK_ENROLLMENT_SERVICE']]['SERVICE_CODE'] = $serviceCodeData->fields['SERVICE_CODE'];
            $service_code_array[$serviceCodeData->fields['PK_ENROLLMENT_SERVICE']]['NUMBER_OF_SESSION'] = $serviceCodeData->fields['NUMBER_OF_SESSION'];
            $service_code_array[$serviceCodeData->fields['PK_ENROLLMENT_SERVICE']]['PRICE_PER_SESSION'] = $serviceCodeData->fields['PRICE_PER_SESSION'];
            $service_code_array[$serviceCodeData->fields['PK_ENROLLMENT_SERVICE']]['TOTAL_AMOUNT_PAID'] = $serviceCodeData->fields['TOTAL_AMOUNT_PAID'];
            $service_code_array[$serviceCodeData->fields['PK_ENROLLMENT_SERVICE']]['SESSION_COMPLETED'] = $SESSION_COMPLETED;
            $service_code_array[$serviceCodeData->fields['PK_ENROLLMENT_SERVICE']]['USED_AMOUNT'] = $used_session_amount;
            $service_code_array[$serviceCodeData->fields['PK_ENROLLMENT_SERVICE']]['BALANCE'] = $serviceCodeData->fields['TOTAL_AMOUNT_PAID'] - $used_session_amount;
            $service_code_array[$serviceCodeData->fields['PK_ENROLLMENT_SERVICE']]['FINAL_AMOUNT'] = $serviceCodeData->fields['FINAL_AMOUNT'];

            $serviceCodeData->MoveNext();
        }

        $total_positive_balance = 0;
        $total_negative_balance = 0;
        foreach ($service_code_array as $key => $value) {
            if ($value['BALANCE'] < 0) {
                $total_negative_balance += $value['BALANCE'];

                if ($USE_AVAILABLE_CREDIT == 1) {
                    [$index, $balance] = checkForAdjustableService($service_code_array);
                    if ($index > 0) {
                        if ($balance > abs($value['BALANCE'])) {
                            $adjustable_amount = abs($value['BALANCE']);
                            $leftover_amount = $balance - abs($value['BALANCE']);
                        } else {
                            $adjustable_amount = $balance;
                            $leftover_amount = 0;
                        }
                        $service_code_array[$key]['BALANCE'] = $value['BALANCE'] + $adjustable_amount;
                        $service_code_array[$key]['ADJUSTABLE_AMOUNT'] = $adjustable_amount;

                        $service_code_array[$index]['BALANCE'] = $leftover_amount;
                        $service_code_array[$index]['ADJUSTABLE_AMOUNT'] = -$adjustable_amount;
                    }
                }
            } else {
                $total_positive_balance += $value['BALANCE'];
            }
        }

        function checkForAdjustableService($service_code_array): array
        {
            foreach ($service_code_array as $key => $value) {
                if ($value['BALANCE'] > 0) {
                    return [$key, $value['BALANCE']];
                }
            }
            return [0, 0];
        }

        /*echo $total_positive_balance." ".$total_negative_balance;
    pre_r($service_code_array);*/

        foreach ($service_code_array as $key => $value) {
            $PRICE_PER_SESSION = ($value['PRICE_PER_SESSION'] <= 0) ? 1 : $value['PRICE_PER_SESSION'];
            $TOTAL_PAID_SESSION = ($value['PRICE_PER_SESSION'] <= 0) ? $value['NUMBER_OF_SESSION'] : number_format($value['TOTAL_AMOUNT_PAID'] / $value['PRICE_PER_SESSION'], 2);
            $ENR_BALANCE = $value['BALANCE'];

            $total_amount += $value['FINAL_AMOUNT'];
            $total_paid_amount += $value['TOTAL_AMOUNT_PAID'];
            $total_used_amount +=  ($PRICE_PER_SESSION * $value['SESSION_COMPLETED']);
            if (isset($value['ADJUSTABLE_AMOUNT'])) {
                $adjusted_amount = "<span style='margin-left: 8px; padding: 5px; background-color: " . (($value['ADJUSTABLE_AMOUNT'] > 0) ? 'green' : 'red') . "; border-radius: 5px; color: white;'>" . $value['ADJUSTABLE_AMOUNT'] . "</span>"; ?>
                <input type="hidden" name="TOTAL_AMOUNT_PAID[]" value="<?= $value['TOTAL_AMOUNT_PAID'] + $value['ADJUSTABLE_AMOUNT'] ?>">
            <?php } else {
                $adjusted_amount = '';
            }
            ?>
            <input type="hidden" name="PK_ENROLLMENT_SERVICE[]" value="<?= $key ?>">
            <tr>
                <td><?= $value['SERVICE_CODE'] ?></td>
                <td style="text-align: right"><?= $value['NUMBER_OF_SESSION'] ?></td>
                <td style="text-align: right;"><?= $value['SESSION_COMPLETED'] ?></td>
                <td style="text-align: right; color:<?= ($ENR_BALANCE < 0) ? 'red' : 'black' ?>;"><?= number_format($ENR_BALANCE, 2) . $adjusted_amount ?></td>
                <td style="text-align: right">$<?= number_format($value['TOTAL_AMOUNT_PAID'], 2) ?></td>
                <td style="text-align: right;"><?= ($ENR_BALANCE > 0) ? number_format($ENR_BALANCE, 2) : 0 ?></td>
            </tr>
        <?php
        } ?>
        <tr>
            <td>Amount</td>
            <td style="text-align: right;"><?= number_format($total_amount, 2) ?></td>
            <td style="text-align: right;"><?= number_format($total_used_amount, 2) ?></td>
            <td style="text-align: right; color:<?= ($total_paid_amount - $total_used_amount < 0) ? 'red' : 'black' ?>;"><?= number_format($total_paid_amount - $total_used_amount, 2) ?></td>
            <td style="text-align: right;"><?= number_format($total_paid_amount, 2) ?></td>
            <td style="text-align: right;"><?= ($total_paid_amount - $total_used_amount > 0) ? number_format($total_paid_amount - $total_used_amount, 2) : 0 ?></td>
        </tr>
    </tbody>
</table>

<input type="hidden" id="TOTAL_POSITIVE_BALANCE" name="TOTAL_POSITIVE_BALANCE" value="<?= $total_positive_balance ?>">
<input type="hidden" id="TOTAL_NEGATIVE_BALANCE" name="TOTAL_NEGATIVE_BALANCE" value="<?= $total_negative_balance ?>">