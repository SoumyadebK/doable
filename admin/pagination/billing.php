<?php
require_once('../../global/config.php');

$results_per_page = 100;

if (isset($_GET['search_text']) && $_GET['search_text'] != '') {
    $search_text = $_GET['search_text'];
    $search = " AND DOA_USERS.FIRST_NAME LIKE '%".$search_text."%' OR DOA_USERS.EMAIL_ID LIKE '%".$search_text."%' OR DOA_USERS.PHONE LIKE '%".$search_text."%'";
} else {
    $search_text = '';
    $search = ' ';
}

$query = $db->Execute("SELECT count($account_database.DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS TOTAL_RECORDS FROM $account_database.`DOA_ENROLLMENT_MASTER` INNER JOIN $master_database.DOA_USER_MASTER ON $account_database.DOA_ENROLLMENT_MASTER.PK_USER_MASTER = $master_database.DOA_USER_MASTER.PK_USER_MASTER INNER JOIN $master_database.DOA_USERS ON $master_database.DOA_USERS.PK_USER = $master_database.DOA_USER_MASTER.PK_USER INNER JOIN $master_database.DOA_LOCATION ON $master_database.DOA_LOCATION.PK_LOCATION = $account_database.DOA_ENROLLMENT_MASTER.PK_LOCATION  WHERE $account_database.DOA_ENROLLMENT_MASTER.PK_USER_MASTER = '$_GET[master_id]'".$search);
$number_of_result =  $query->fields['TOTAL_RECORDS'];
$number_of_page = ceil ($number_of_result / $results_per_page);

if (!isset ($_GET['page']) ) {
    $page = 1;
} else {
    $page = $_GET['page'];
}
$page_first_result = ($page-1) * $results_per_page;
?>

<?php
$i=$page_first_result+1;
$row = $db->Execute("SELECT $account_database.DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, $account_database.DOA_ENROLLMENT_MASTER.ENROLLMENT_ID, $account_database.DOA_ENROLLMENT_MASTER.ACTIVE, $master_database.DOA_USERS.FIRST_NAME, $master_database.DOA_USERS.LAST_NAME, $master_database.DOA_LOCATION.LOCATION_NAME FROM $account_database.`DOA_ENROLLMENT_MASTER` INNER JOIN $master_database.DOA_USER_MASTER ON $account_database.DOA_ENROLLMENT_MASTER.PK_USER_MASTER = $master_database.DOA_USER_MASTER.PK_USER_MASTER INNER JOIN $master_database.DOA_USERS ON $master_database.DOA_USERS.PK_USER = $master_database.DOA_USER_MASTER.PK_USER INNER JOIN $master_database.DOA_LOCATION ON $master_database.DOA_LOCATION.PK_LOCATION = $account_database.DOA_ENROLLMENT_MASTER.PK_LOCATION  WHERE $account_database.DOA_ENROLLMENT_MASTER.PK_USER_MASTER = '$_GET[master_id]'".$search."ORDER BY $account_database.DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER DESC"." LIMIT " . $page_first_result . ',' . $results_per_page);
while (!$row->EOF) {
    $used_session_count = $db_account->Execute("SELECT COUNT(`PK_ENROLLMENT_MASTER`) AS USED_SESSION_COUNT, PK_SERVICE_MASTER FROM `DOA_APPOINTMENT_MASTER` WHERE `PK_ENROLLMENT_MASTER` = ".$row->fields['PK_ENROLLMENT_MASTER']);
    $PK_SERVICE_MASTER = ($used_session_count->RecordCount() > 0) ? $used_session_count->fields['PK_SERVICE_MASTER'] : 0;
    $total_session = $db_account->Execute("SELECT SUM(`NUMBER_OF_SESSION`) AS TOTAL_SESSION_COUNT FROM `DOA_ENROLLMENT_SERVICE` WHERE  `PK_ENROLLMENT_MASTER` = ".$row->fields['PK_ENROLLMENT_MASTER']." AND `PK_SERVICE_MASTER` = ".$PK_SERVICE_MASTER);
    if ($total_session->RecordCount() <= 0 || $total_session->fields['TOTAL_SESSION_COUNT'] == '') {
        $total_session = $db_account->Execute("SELECT SUM(`NUMBER_OF_SESSION`) AS TOTAL_SESSION_COUNT FROM `DOA_ENROLLMENT_SERVICE` WHERE  `PK_ENROLLMENT_MASTER` = ".$row->fields['PK_ENROLLMENT_MASTER']);
    }
    $total_session_count = ($total_session->RecordCount() > 0) ? $total_session->fields['TOTAL_SESSION_COUNT'] : 0;
    $total_bill_data = $db_account->Execute("SELECT TOTAL_AMOUNT AS TOTAL_BILL FROM DOA_ENROLLMENT_BILLING WHERE `PK_ENROLLMENT_MASTER`=".$row->fields['PK_ENROLLMENT_MASTER']);
    $total_bill = ($total_bill_data->RecordCount()>0)?$total_bill_data->fields['TOTAL_BILL']:0;
    $total_paid = $db_account->Execute("SELECT SUM(AMOUNT) AS TOTAL_PAID FROM DOA_ENROLLMENT_PAYMENT WHERE `PK_ENROLLMENT_MASTER`=".$row->fields['PK_ENROLLMENT_MASTER']);
    $enrollment_balance = $db->Execute("SELECT * FROM `DOA_ENROLLMENT_BALANCE` WHERE `PK_ENROLLMENT_MASTER`=".$row->fields['PK_ENROLLMENT_MASTER']);
    ?>
    <div class="row" onclick="$(this).next().slideToggle();" style="cursor:pointer; font-size: 15px; *border: 1px solid #ebe5e2; padding: 8px;">
        <div class="col-2"><span class="hidden-sm-up" style="margin-right: 20px;"><i class="ti-arrow-circle-right"></i></span></i> <?=$row->fields['ENROLLMENT_ID']?></div>
        <div class="col-2">Total Billed : <?=$total_bill;?></div>
        <div class="col-2">Total Paid : <?=$total_paid->fields['TOTAL_PAID'];?></div>
        <div class="col-2">Balance : <?=$total_bill-$total_paid->fields['TOTAL_PAID'];?></div>
        <div class="col-2">Session : <?=$used_session_count->fields['USED_SESSION_COUNT'].'/'.$total_session_count;?></div>
    </div>
    <table id="myTable" class="table table-striped border" style="display: none">
        <thead>
        <tr>
            <th>Due Date</th>
            <th>Transaction Type</th>
            <th>Billed Amount</th>
            <th>Paid Amount</th>
            <th>Payment Type</th>
            <th>Description</th>
            <th>Paid</th>
            <th>Balance</th>
            <th>Actions</th>
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
                <td></td>
                <td><?=(($billing_details->fields['TRANSACTION_TYPE']=='Billing')?(($billing_details->fields['IS_PAID']==1)?'YES':'NO'):'')?></td>
                <td><?=number_format((float)$balance, 2, '.', '')?></td>
                <td>
                    <?php if($billing_details->fields['IS_PAID']==0 && $billing_details->fields['STATUS']=='A') { ?>
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
                    <td></td>
                    <td><?=(($payment_details->fields['TRANSACTION_TYPE']=='Billing')?(($payment_details->fields['IS_PAID']==1)?'YES':'NO'):'')?></td>
                    <td><?=number_format((float)$balance, 2, '.', '')?></td>
                    <td>
                    </td>
                </tr>
            <? } ?>
            <?php $billing_details->MoveNext(); } ?>
        </tbody>
    </table>
    <?php $row->MoveNext();
    $i++; } ?>

<div class="center">
    <div class="pagination outer">
        <ul>
            <?php if ($page > 1) { ?>
                <li><a href="javascript:;" onclick="showBillingList(1)">&laquo;</a></li>
                <li><a href="javascript:;" onclick="showBillingList(<?=($page-1)?>)">&lsaquo;</a></li>
            <?php }
            for($page_count = 1; $page_count<=$number_of_page; $page_count++) {
                if ($page_count == $page || $page_count == ($page+1) || $page_count == ($page-1) || $page_count == $number_of_page) {
                    echo '<li><a class="'.(($page_count==$page)?"active":"").'" href="javascript:;" onclick="showBillingList('.$page_count.')">' . $page_count . ' </a></li>';
                } elseif ($page_count == ($number_of_page-1)){
                    echo '<li><a href="javascript:;" onclick="showHiddenPageNumber(this);" style="border: none; margin: 0; padding: 8px;">...</a></li>';
                } else {
                    echo '<li><a class="hidden" href="javascript:;" onclick="showBillingList('.$page_count.')">' . $page_count . ' </a></li>';
                }
            }
            if ($page < $number_of_page) { ?>
                <li><a href="javascript:;" onclick="showBillingList(<?=($page+1)?>)">&rsaquo;</a></li>
                <li><a href="javascript:;" onclick="showBillingList(<?=$number_of_page?>)">&raquo;</a></li>
            <?php } ?>
        </ul>
    </div>
</div>

