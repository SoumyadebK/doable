<?php
require_once('../../global/config.php');
$PK_USER_MASTER='';

$results_per_page = 100;

if (isset($_GET['search_text']) && $_GET['search_text'] != '') {
    $search_text = $_GET['search_text'];
    $search = " AND DOA_USERS.FIRST_NAME LIKE '%".$search_text."%' OR DOA_USERS.EMAIL_ID LIKE '%".$search_text."%' OR DOA_USERS.PHONE LIKE '%".$search_text."%'";
} else {
    $search_text = '';
    $search = ' ';
}

if (!isset ($_GET['page']) ) {
    $page = 1;
} else {
    $page = $_GET['page'];
}
$page_first_result = ($page-1) * $results_per_page;
?>

<?php $wallet_data = $db_account->Execute("SELECT * FROM DOA_CUSTOMER_WALLET WHERE PK_USER_MASTER = '$PK_USER_MASTER' ORDER BY PK_CUSTOMER_WALLET DESC LIMIT 1"); ?>
<?php
$i=$page_first_result+1;
$row = $db_account->Execute("SELECT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, DOA_ENROLLMENT_MASTER.ENROLLMENT_ID, DOA_ENROLLMENT_MASTER.ACTIVE FROM `DOA_ENROLLMENT_MASTER` WHERE DOA_ENROLLMENT_MASTER.PK_USER_MASTER='$_GET[master_id]' ORDER BY DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER DESC");
while (!$row->EOF) {
    $serviceMasterData = $db_account->Execute("SELECT DOA_SERVICE_MASTER.SERVICE_NAME FROM DOA_SERVICE_MASTER JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER WHERE DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = ".$row->fields['PK_ENROLLMENT_MASTER']);
    $serviceMaster = [];
    while (!$serviceMasterData->EOF) {
        $serviceMaster[] = $serviceMasterData->fields['SERVICE_NAME'];
        $serviceMasterData->MoveNext();
    }



    $used_session_count = $db_account->Execute("SELECT COUNT(`PK_ENROLLMENT_MASTER`) AS USED_SESSION_COUNT, PK_SERVICE_MASTER FROM `DOA_APPOINTMENT_MASTER` WHERE `PK_ENROLLMENT_MASTER` = ".$row->fields['PK_ENROLLMENT_MASTER']);
    $PK_SERVICE_MASTER = ($used_session_count->RecordCount() > 0) ? $used_session_count->fields['PK_SERVICE_MASTER'] : 0;
    $total_session = $db_account->Execute("SELECT SUM(`NUMBER_OF_SESSION`) AS TOTAL_SESSION_COUNT FROM `DOA_ENROLLMENT_SERVICE` WHERE  `PK_ENROLLMENT_MASTER` = ".$row->fields['PK_ENROLLMENT_MASTER']." AND `PK_SERVICE_MASTER` = ".$PK_SERVICE_MASTER);
    if ($total_session->RecordCount() <= 0 || $total_session->fields['TOTAL_SESSION_COUNT'] == '') {
        $total_session = $db_account->Execute("SELECT SUM(`NUMBER_OF_SESSION`) AS TOTAL_SESSION_COUNT FROM `DOA_ENROLLMENT_SERVICE` WHERE  `PK_ENROLLMENT_MASTER` = ".$row->fields['PK_ENROLLMENT_MASTER']);
    }
    $total_session_count = ($total_session->RecordCount() > 0) ? $total_session->fields['TOTAL_SESSION_COUNT'] : 0;
    $total_bill_and_paid = $db_account->Execute("SELECT SUM(BILLED_AMOUNT) AS TOTAL_BILL, SUM(PAID_AMOUNT) AS TOTAL_PAID, SUM(BALANCE) AS BALANCE FROM DOA_ENROLLMENT_LEDGER WHERE `PK_ENROLLMENT_MASTER`=".$row->fields['PK_ENROLLMENT_MASTER']);
    $price_per_session = ($total_session_count > 0) ? $total_bill_and_paid->fields['TOTAL_PAID']/$total_session_count : 0.00;
    //$enrollment_balance = $db->Execute("SELECT * FROM `DOA_ENROLLMENT_BALANCE` WHERE `PK_ENROLLMENT_MASTER`=".$row->fields['PK_ENROLLMENT_MASTER']);
    $total_amount = $db_account->Execute("SELECT SUM(TOTAL_AMOUNT) AS TOTAL_AMOUNT FROM `DOA_ENROLLMENT_BILLING` WHERE `PK_ENROLLMENT_MASTER`=".$row->fields['PK_ENROLLMENT_MASTER']);
    $total_paid = $total_bill_and_paid->fields['TOTAL_PAID'];
    $balance = $total_bill_and_paid->fields['TOTAL_BILL'] - $total_bill_and_paid->fields['TOTAL_PAID'];
    $total_used = $used_session_count->fields['USED_SESSION_COUNT']*$price_per_session;
    $service_credit = $total_bill_and_paid->fields['TOTAL_PAID']-$total_used;
    ?>
    <div class="border" style="margin: 10px;">
        <div class="row" onclick="$(this).next().slideToggle();" style="cursor:pointer; font-size: 15px; *border: 1px solid #ebe5e2; padding: 8px;">
            <div class="col-6" style="text-align: center; margin-top: 1.5%;">
                <a href="enrollment.php?id=<?=$row->fields['PK_ENROLLMENT_MASTER']?>"><?=$row->fields['ENROLLMENT_ID']?></a>
                <p><?=implode(' || ', $serviceMaster)?></p>
            </div>
            <div class="col-6" style="text-align: center; margin-top: 1.5%;">
                <p>Wallet Balance : $<?=$balance?></p>
            </div>
            <div class="col-12">
                <table id="myTable" class="table table-striped border">
                    <thead>
                    <tr>
                        <th></th>
                        <th>Enrolled</th>
                        <th>Paid</th>
                        <th>Used</th>
                        <th>Balance</th>
                        <th>Service Credit</th>
                    </tr>
                    </thead>

                    <tbody>
                    <?php
                    $per_session_cost = $total_bill_and_paid->fields['TOTAL_BILL']/(($total_session_count==0)?1:$total_session_count);
                    $total_paid_session_count = ceil($total_bill_and_paid->fields['TOTAL_PAID']/(($per_session_cost==0)?1:$per_session_cost));
                    $serviceCodeData = $db_account->Execute("SELECT DOA_SERVICE_CODE.PK_SERVICE_CODE, DOA_SERVICE_CODE.SERVICE_CODE, DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION FROM DOA_SERVICE_CODE JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE WHERE DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = ".$row->fields['PK_ENROLLMENT_MASTER']);
                    while (!$serviceCodeData->EOF) {
                        $used_session_count = $db_account->Execute("SELECT COUNT(`PK_ENROLLMENT_MASTER`) AS USED_SESSION_COUNT FROM `DOA_APPOINTMENT_MASTER` WHERE `PK_ENROLLMENT_MASTER` = ".$row->fields['PK_ENROLLMENT_MASTER']." AND PK_SERVICE_CODE = ".$serviceCodeData->fields['PK_SERVICE_CODE']); ?>
                        <tr>
                            <td><?=$serviceCodeData->fields['SERVICE_CODE']?></td>
                            <td><?=$serviceCodeData->fields['NUMBER_OF_SESSION']?></td>
                            <td>
                                <?php
                                if ($total_paid_session_count > $serviceCodeData->fields['NUMBER_OF_SESSION']) {
                                    echo $paid_session_count = $serviceCodeData->fields['NUMBER_OF_SESSION'];
                                    $total_paid_session_count -= $serviceCodeData->fields['NUMBER_OF_SESSION'];
                                } else {
                                    echo $paid_session_count = $total_paid_session_count;
                                    $total_paid_session_count = 0;
                                }
                                ?>
                            </td>
                            <td><?=$used_session_count->fields['USED_SESSION_COUNT']?></td>
                            <td><?=$serviceCodeData->fields['NUMBER_OF_SESSION']-$used_session_count->fields['USED_SESSION_COUNT']?></td>
                            <td><?=($total_bill_and_paid->fields['TOTAL_BILL']==0) ? 0 : $serviceCodeData->fields['NUMBER_OF_SESSION']-$used_session_count->fields['USED_SESSION_COUNT']?></td>
                        </tr>
                        <?php $serviceCodeData->MoveNext();
                    } ?>
                    <tr>
                        <td>Amount</td>
                        <td><?=$total_bill_and_paid->fields['TOTAL_BILL']?></td>
                        <td><?=$total_bill_and_paid->fields['TOTAL_PAID']?></td>
                        <td><?=$total_used?></td>
                        <td><?=$balance?></td>
                        <td><?=$service_credit?></td>
                    </tr>
                    </tbody>
                </table>
            </div>

        </div>

        <table id="myTable" class="table table-striped border" style="display: none">
            <thead>
            <tr>
                <th>Due Date</th>
                <th>Transaction Type</th>
                <th>Billed Amount</th>
                <th>Paid Amount</th>
                <th>Payment Type</th>
                <th>Balance</th>
                <?php
                $details = $db_account->Execute("SELECT DOA_ENROLLMENT_LEDGER.*, DOA_PAYMENT_TYPE.PAYMENT_TYPE FROM `DOA_ENROLLMENT_LEDGER` LEFT JOIN $master_database.DOA_PAYMENT_TYPE AS DOA_PAYMENT_TYPE ON DOA_ENROLLMENT_LEDGER.PK_PAYMENT_TYPE = DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE WHERE PK_ENROLLMENT_MASTER = ".$row->fields['PK_ENROLLMENT_MASTER']." AND ENROLLMENT_LEDGER_PARENT = 0 ORDER BY DUE_DATE ASC, PK_ENROLLMENT_LEDGER ASC");
                ?>
                <th><input type="checkbox" onClick="toggle(this)"/><button type="button" class="btn btn-info m-l-10 text-white" onclick="paySelected(<?=$row->fields['PK_ENROLLMENT_MASTER']?>, '<?=$row->fields['ENROLLMENT_ID']?>')"> Pay Selected</button></th>
            </tr>
            </thead>

            <tbody>
            <?php
            $billed_amount = 0;
            $paid_amount = 0;
            $balance = 0;
            $billing_details = $db_account->Execute("SELECT DOA_ENROLLMENT_LEDGER.*, DOA_PAYMENT_TYPE.PAYMENT_TYPE FROM `DOA_ENROLLMENT_LEDGER` LEFT JOIN $master_database.DOA_PAYMENT_TYPE AS DOA_PAYMENT_TYPE ON DOA_ENROLLMENT_LEDGER.PK_PAYMENT_TYPE = DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE WHERE PK_ENROLLMENT_MASTER = ".$row->fields['PK_ENROLLMENT_MASTER']." AND ENROLLMENT_LEDGER_PARENT = 0 ORDER BY DUE_DATE ASC, PK_ENROLLMENT_LEDGER ASC");
            while (!$billing_details->EOF) { $billed_amount = $billing_details->fields['BILLED_AMOUNT']; $balance = ($billing_details->fields['BILLED_AMOUNT'] + $balance); ?>
                <tr>
                    <td><?=date('m/d/Y', strtotime($billing_details->fields['DUE_DATE']))?></td>
                    <td><?=$billing_details->fields['TRANSACTION_TYPE']?></td>
                    <td><?=$billing_details->fields['BILLED_AMOUNT']?></td>
                    <td></td>
                    <td><?=$billing_details->fields['PAYMENT_TYPE']?></td>
                    <td><?=number_format((float)$balance, 2, '.', '')?></td>
                    <td>
                        <?php if($billing_details->fields['IS_PAID']==0 && $billing_details->fields['STATUS']=='A') { ?>
                            <label><input type="checkbox" name="BILLED_AMOUNT[]" class="BILLED_AMOUNT" data-pk_enrollment_ledger="<?=$billing_details->fields['PK_ENROLLMENT_LEDGER']?>" value="<?=$billing_details->fields['BILLED_AMOUNT']?>"</label>
                            <a href="javascript:;" class="btn btn-info waves-effect waves-light m-r-10 text-white" onclick="payNow(<?=$row->fields['PK_ENROLLMENT_MASTER']?>, <?=$billing_details->fields['PK_ENROLLMENT_LEDGER']?>, <?=$billing_details->fields['BILLED_AMOUNT']?>, '<?=$row->fields['ENROLLMENT_ID']?>');">Pay Now</a>
                        <?php } ?>
                    </td>
                </tr>
                <?php
                $payment_details = $db_account->Execute("SELECT DOA_ENROLLMENT_LEDGER.*, DOA_PAYMENT_TYPE.PAYMENT_TYPE FROM `DOA_ENROLLMENT_LEDGER` LEFT JOIN $master_database.DOA_PAYMENT_TYPE AS DOA_PAYMENT_TYPE ON DOA_ENROLLMENT_LEDGER.PK_PAYMENT_TYPE = DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE WHERE ENROLLMENT_LEDGER_PARENT = ".$billing_details->fields['PK_ENROLLMENT_LEDGER']);
                if ($payment_details->RecordCount() > 0){ $balance = ($billed_amount - $payment_details->fields['PAID_AMOUNT']); ?>
                    <tr>
                        <td><?=date('m/d/Y', strtotime($payment_details->fields['DUE_DATE']))?></td>
                        <td><?=$payment_details->fields['TRANSACTION_TYPE']?></td>
                        <td></td>
                        <td><?=$payment_details->fields['PAID_AMOUNT']?></td>
                        <td><?=$payment_details->fields['PAYMENT_TYPE']?></td>
                        <td><?=number_format((float)$balance, 2, '.', '')?></td>
                        <td>
                        </td>
                    </tr>
                <?php } ?>
                <?php $billing_details->MoveNext(); } ?>
            </tbody>

            <thead>
            <tr>
                <th>Service</th>
                <th>Apt #</th>
                <th>Service Code</th>
                <th>Date</th>
                <th>Time</th>
                <th>Session Cost</th>
                <th>Service Credit</th>
            </tr>
            </thead>

            <tbody>
            <?php
            $appointment_data = $db_account->Execute("SELECT DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER, DOA_APPOINTMENT_MASTER.SERIAL_NUMBER, DOA_APPOINTMENT_MASTER.DATE, DOA_APPOINTMENT_MASTER.START_TIME, DOA_APPOINTMENT_MASTER.END_TIME, DOA_SERVICE_MASTER.SERVICE_NAME, DOA_SERVICE_CODE.SERVICE_CODE, DOA_SERVICE_CODE.PRICE AS SESSION_COST, DOA_APPOINTMENT_MASTER.ACTIVE, DOA_APPOINTMENT_STATUS.APPOINTMENT_STATUS, DOA_APPOINTMENT_STATUS.COLOR_CODE FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_SERVICE_MASTER ON DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER LEFT JOIN DOA_SERVICE_CODE ON DOA_APPOINTMENT_MASTER.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE LEFT JOIN $master_database.DOA_APPOINTMENT_STATUS AS DOA_APPOINTMENT_STATUS ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS = DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS  WHERE DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_MASTER = ".$row->fields['PK_ENROLLMENT_MASTER']."");
            $total_session_cost = 0;
            $j=1;
            while (!$appointment_data->EOF) {
                $total_session_cost += $price_per_session; ?>
                <tr>
                    <td><?=$appointment_data->fields['SERVICE_NAME']?></td>
                    <td><?=$j.'/'.$total_session_count?></td>
                    <td><?=$appointment_data->fields['SERVICE_CODE']?></td>
                    <td><?=date('m/d/Y', strtotime($appointment_data->fields['DATE']))?></td>
                    <td><?=date('h:i A', strtotime($appointment_data->fields['START_TIME']))." - ".date('h:i A', strtotime($appointment_data->fields['END_TIME']))?></td>
                    <td><?=number_format((float)$price_per_session, 2, '.', ',');?></td>
                    <td style="color:<?=(($total_paid-$total_session_cost)<0)?'red':'black'?>;"><?=number_format((float)($total_paid-$total_session_cost), 2, '.', ',');?></td>
                </tr>
                <?php $appointment_data->MoveNext();
                $j++; } ?>
            </tbody>
        </table>

    </div>
    <?php
    $row->MoveNext();
    $i++;
} ?>
<?php
