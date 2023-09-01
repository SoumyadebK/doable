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

$query = $db_account->Execute("SELECT count(DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS TOTAL_RECORDS FROM `DOA_ENROLLMENT_MASTER` WHERE DOA_ENROLLMENT_MASTER.PK_USER_MASTER='$_GET[master_id]'".$search);
$number_of_result =  $query->fields['TOTAL_RECORDS'];
$number_of_page = ceil ($number_of_result / $results_per_page);

if (!isset ($_GET['page']) ) {
    $page = 1;
} else {
    $page = $_GET['page'];
}
$page_first_result = ($page-1) * $results_per_page;
?>

<?php $wallet_data = $db_account->Execute("SELECT * FROM DOA_CUSTOMER_WALLET WHERE PK_USER_MASTER = '$PK_USER_MASTER' ORDER BY PK_CUSTOMER_WALLET DESC LIMIT 1"); ?>
<h3 class="m-20">Wallet Balance : $<?=($wallet_data->RecordCount() > 0)?$wallet_data->fields['CURRENT_BALANCE']:0.00?></h3>
<?php
$i=$page_first_result+1;
$row = $db_account->Execute("SELECT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, DOA_ENROLLMENT_MASTER.ENROLLMENT_ID, DOA_ENROLLMENT_MASTER.ACTIVE FROM `DOA_ENROLLMENT_MASTER` WHERE DOA_ENROLLMENT_MASTER.PK_USER_MASTER='$_GET[master_id]'".$search."ORDER BY DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER DESC"." LIMIT " . $page_first_result . ',' . $results_per_page);
while (!$row->EOF) {
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
    <div class="row" onclick="$(this).next().slideToggle()" style="cursor:pointer; font-size: 15px; *border: 1px solid #ebe5e2; padding: 8px;">
        <div class="col-1"><span class="hidden-sm-up link" style=""><i class="ti-arrow-circle-right"></i></span></i> <?=$row->fields['ENROLLMENT_ID']?></div>
        <div class="col-2">Total Billed : <?=$total_bill_and_paid->fields['TOTAL_BILL'];?></div>
        <div class="col-2">Total Paid : <?=$total_bill_and_paid->fields['TOTAL_PAID'];?></div>
        <div class="col-2">Balance : <?=$balance?></div>
        <div class="col-2">Used : <?=number_format((float)$total_used, 2, '.', ',');?></div>
        <div class="col-2" style="color:<?=($service_credit<0)?'red':'black'?>;">Service Credit : <?=number_format((float)$service_credit, 2, '.', ',');?></div>
        <div class="col-1">Session : <?=$used_session_count->fields['USED_SESSION_COUNT'].'/'.$total_session_count;?></div>
    </div>
    <table id="myTable" class="table table-striped border" style="display: none">
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
            $total_session_cost += $price_per_session?>
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
    <?php $row->MoveNext();
    $i++; } ?>

<div class="center">
    <div class="pagination outer">
        <ul>
            <?php if ($page > 1) { ?>
                <li><a href="javascript:;" onclick="showLedgerList(1)">&laquo;</a></li>
                <li><a href="javascript:;" onclick="showLedgerList(<?=($page-1)?>)">&lsaquo;</a></li>
            <?php }
            for($page_count = 1; $page_count<=$number_of_page; $page_count++) {
                if ($page_count == $page || $page_count == ($page+1) || $page_count == ($page-1) || $page_count == $number_of_page) {
                    echo '<li><a class="'.(($page_count==$page)?"active":"").'" href="javascript:;" onclick="showLedgerList('.$page_count.')">' . $page_count . ' </a></li>';
                } elseif ($page_count == ($number_of_page-1)){
                    echo '<li><a href="javascript:;" onclick="showHiddenPageNumber(this);" style="border: none; margin: 0; padding: 8px;">...</a></li>';
                } else {
                    echo '<li><a class="hidden" href="javascript:;" onclick="showLedgerList('.$page_count.')">' . $page_count . ' </a></li>';
                }
            }
            if ($page < $number_of_page) { ?>
                <li><a href="javascript:;" onclick="showLedgerList(<?=($page+1)?>)">&rsaquo;</a></li>
                <li><a href="javascript:;" onclick="showLedgerList(<?=$number_of_page?>)">&raquo;</a></li>
            <?php } ?>
        </ul>

        <!--<ul>
            <?php /*if ($page > 1) { */?>
                <li><a href="javascript:;" onclick="showLedgerList(<?php /*=($page-1)*/?>)">&laquo;</a></li>
            <?php /*}
            for($page_count = 1; $page_count<=$number_of_page; $page_count++) {
                echo '<li><a class="'.(($page_count==$page)?"active":"").'" href="javascript:;" onclick="showLedgerList('.$page_count.')">' . $page_count . ' </a></li>';
            }
            if ($page < $number_of_page) { */?>
                <li><a href="javascript:;" onclick="showLedgerList(<?php /*=($page+1)*/?>)">&raquo;</a></li>
            <?php /*} */?>
        </ul>-->
    </div>
</div>

