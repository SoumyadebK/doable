<?php
require_once('../../global/config.php');
global $db;
global $db_account;
global $master_database;
global $results_per_page;

$PK_ENROLLMENT_MASTER = $_GET['PK_ENROLLMENT_MASTER'];
$USE_AVAILABLE_CREDIT = $_GET['USE_AVAILABLE_CREDIT'];
$ACTUAL_CREDIT_BALANCE = $_GET['ACTUAL_CREDIT_BALANCE'];
?>

<table id="myTable" class="table table-striped border">
    <thead>
        <tr>
            <th></th>
            <th style="text-align: right;">Enrolled</th>
            <th style="text-align: right;">Paid</th>
            <th style="text-align: right;">Used</th>
            <th style="text-align: right;">Balance</th>
            <th style="text-align: right;">Service Credit</th>
        </tr>
    </thead>

    <tbody>
    <?php
    $serviceCodeData = $db_account->Execute("SELECT DOA_ENROLLMENT_SERVICE.*, DOA_SERVICE_CODE.PK_SERVICE_CODE, DOA_SERVICE_CODE.SERVICE_CODE FROM DOA_ENROLLMENT_SERVICE JOIN DOA_SERVICE_CODE ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE WHERE DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = ".$PK_ENROLLMENT_MASTER);
    $total_amount = 0;
    $total_paid_amount = 0;
    $total_used_amount = 0;
    while (!$serviceCodeData->EOF) {
        $PRICE_PER_SESSION = ($serviceCodeData->fields['PRICE_PER_SESSION'] <= 0) ? 1 : $serviceCodeData->fields['PRICE_PER_SESSION'];
        $TOTAL_PAID_SESSION = ($serviceCodeData->fields['PRICE_PER_SESSION'] <= 0) ? $serviceCodeData->fields['NUMBER_OF_SESSION'] : number_format($serviceCodeData->fields['TOTAL_AMOUNT_PAID']/$serviceCodeData->fields['PRICE_PER_SESSION'], 2);
        if ($USE_AVAILABLE_CREDIT == 1 && $ACTUAL_CREDIT_BALANCE > 0 && ($serviceCodeData->fields['SESSION_COMPLETED'] > $TOTAL_PAID_SESSION)) {
            $total_needed_amount = $serviceCodeData->fields['SESSION_COMPLETED'] * $serviceCodeData->fields['PRICE_PER_SESSION'];
            $adjustable_amount = $total_needed_amount - $serviceCodeData->fields['TOTAL_AMOUNT_PAID'];
            if ($adjustable_amount < $ACTUAL_CREDIT_BALANCE) {
                $ACTUAL_CREDIT_BALANCE -= $adjustable_amount;
                $TOTAL_PAID_SESSION = $serviceCodeData->fields['SESSION_COMPLETED'];
            } else {
                $TOTAL_PAID_SESSION = number_format(($serviceCodeData->fields['TOTAL_AMOUNT_PAID']+$ACTUAL_CREDIT_BALANCE)/$serviceCodeData->fields['PRICE_PER_SESSION'], 2);
                $ACTUAL_CREDIT_BALANCE = 0;
            }
        }
        $ENR_BALANCE = $TOTAL_PAID_SESSION - $serviceCodeData->fields['SESSION_COMPLETED'];

        $total_amount += $serviceCodeData->fields['FINAL_AMOUNT'];
        $total_paid_amount += $serviceCodeData->fields['TOTAL_AMOUNT_PAID'];
        $total_used_amount +=  ($PRICE_PER_SESSION * $serviceCodeData->fields['SESSION_COMPLETED']); ?>
        <tr>
            <td><?=$serviceCodeData->fields['SERVICE_CODE']?></td>
            <td style="text-align: right"><?=$serviceCodeData->fields['NUMBER_OF_SESSION']?></td>
            <td style="text-align: right"><?=number_format($TOTAL_PAID_SESSION, 2)?></td>
            <td style="text-align: right;"><?=$serviceCodeData->fields['SESSION_COMPLETED']?></td>
            <td style="text-align: right; color:<?=($ENR_BALANCE < 0)?'red':'black'?>;"><?=number_format($TOTAL_PAID_SESSION - $serviceCodeData->fields['SESSION_COMPLETED'], 2)?></td>
            <td style="text-align: right;"><?=($ENR_BALANCE > 0) ? number_format($ENR_BALANCE, 2) : 0?></td>
        </tr>
    <?php $serviceCodeData->MoveNext();
    } ?>
    <tr>
        <td>Amount</td>
        <td style="text-align: right;"><?=$total_amount?></td>
        <td style="text-align: right;"><?=$total_paid_amount?></td>
        <td style="text-align: right;"><?=$total_used_amount?></td>
        <td style="text-align: right; color:<?=($total_paid_amount-$total_used_amount<0)?'red':'black'?>;"><?=$total_paid_amount-$total_used_amount?></td>
        <td style="text-align: right;"><?=($total_paid_amount-$total_used_amount > 0) ? $total_paid_amount-$total_used_amount : 0?></td>
    </tr>
    </tbody>
</table>

<input type="text" id="FINAL_CREDIT_BALANCE" value="<?=$ACTUAL_CREDIT_BALANCE?>">