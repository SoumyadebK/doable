<?php
require_once('../global/config.php');
$title="Accounts";
$user_master_data = $account = $db->Execute("SELECT * FROM DOA_USER_MASTER WHERE PK_USER = ".$_SESSION['PK_USER']);
$PK_USER_MASTER_ARRAY = [];
while (!$user_master_data->EOF){
    $PK_USER_MASTER_ARRAY[] = $user_master_data->fields['PK_USER_MASTER'];
    $user_master_data->MoveNext();
}
$PK_USER_MASTERS = implode(',', $PK_USER_MASTER_ARRAY);

$results_per_page = 100;

if (isset($_GET['search_text']) && $_GET['search_text'] != '') {
    $search_text = $_GET['search_text'];
    $search = " AND DOA_USERS.FIRST_NAME LIKE '%".$search_text."%' OR DOA_USERS.EMAIL_ID LIKE '%".$search_text."%' OR DOA_USERS.PHONE LIKE '%".$search_text."%'";
} else {
    $search_text = '';
    $search = ' ';
}

$query = $db_account->Execute("SELECT count(DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS TOTAL_RECORDS FROM `DOA_ENROLLMENT_MASTER` WHERE DOA_ENROLLMENT_MASTER.PK_USER_MASTER IN (".$PK_USER_MASTERS.")".$search);
$number_of_result =  $query->fields['TOTAL_RECORDS'];
$number_of_page = ceil ($number_of_result / $results_per_page);

if (!isset ($_GET['page']) ) {
    $page = 1;
} else {
    $page = $_GET['page'];
}
$page_first_result = ($page-1) * $results_per_page;
?>


<!DOCTYPE html>
<html lang="en">
<?php require_once('../includes/header.php');?>

<body class="skin-default-dark fixed-layout">
<?php require_once('../includes/loader.php');?>
<div id="main-wrapper">
    <?php require_once('../includes/top_menu.php');?>
    <div class="page-wrapper">
        <?php require_once('../includes/top_menu_bar.php') ?>
        <div class="container-fluid body_content">
            <div class="row page-titles">
                <div class="col-md-5 align-self-center">
                    <h4 class="text-themecolor"><?=$title?></h4>
                </div>
            </div>

            <div class="row">
                <div id="appointment_list_half" class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="p-10">
                                <?php $wallet_data = $db_account->Execute("SELECT * FROM DOA_CUSTOMER_WALLET WHERE PK_USER_MASTER  IN (".$PK_USER_MASTERS.")".$search." ORDER BY PK_CUSTOMER_WALLET DESC LIMIT 1"); ?>
                                <h3 class="m-20">Wallet Balance : $<?=($wallet_data->RecordCount() > 0)?$wallet_data->fields['CURRENT_BALANCE']:0.00?></h3>
                                <?php
                                $i=$page_first_result+1;
                                $row = $db_account->Execute("SELECT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, DOA_ENROLLMENT_MASTER.ENROLLMENT_ID, DOA_ENROLLMENT_MASTER.ACTIVE FROM `DOA_ENROLLMENT_MASTER` WHERE DOA_ENROLLMENT_MASTER.PK_USER_MASTER IN (".$PK_USER_MASTERS.")".$search."ORDER BY DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER DESC"." LIMIT " . $page_first_result . ',' . $results_per_page);
                                while (!$row->EOF) {
                                    $used_session_count = $db_account->Execute("SELECT COUNT(`PK_ENROLLMENT_MASTER`) AS USED_SESSION_COUNT, PK_SERVICE_MASTER FROM `DOA_APPOINTMENT_MASTER` WHERE `PK_ENROLLMENT_MASTER` = ".$row->fields['PK_ENROLLMENT_MASTER']);
                                    $PK_SERVICE_MASTER = ($used_session_count->RecordCount() > 0) ? $used_session_count->fields['PK_SERVICE_MASTER'] : 0;
                                    $total_session = $db_account->Execute("SELECT SUM(`NUMBER_OF_SESSION`) AS TOTAL_SESSION_COUNT FROM `DOA_ENROLLMENT_SERVICE` WHERE  `PK_ENROLLMENT_MASTER` = ".$row->fields['PK_ENROLLMENT_MASTER']." AND `PK_SERVICE_MASTER` = ".$PK_SERVICE_MASTER);
                                    if ($total_session->RecordCount() <= 0 || $total_session->fields['TOTAL_SESSION_COUNT'] == '') {
                                        $total_session = $db_account->Execute("SELECT SUM(`NUMBER_OF_SESSION`) AS TOTAL_SESSION_COUNT FROM `DOA_ENROLLMENT_SERVICE` WHERE  `PK_ENROLLMENT_MASTER` = ".$row->fields['PK_ENROLLMENT_MASTER']);
                                    }
                                    $total_session_count = ($total_session->RecordCount() > 0) ? $total_session->fields['TOTAL_SESSION_COUNT'] : 0;
                                    $total_bill_and_paid = $db_account->Execute("SELECT SUM(BILLED_AMOUNT) AS TOTAL_BILL, SUM(PAID_AMOUNT) AS TOTAL_PAID FROM DOA_ENROLLMENT_LEDGER WHERE `PK_ENROLLMENT_MASTER`=".$row->fields['PK_ENROLLMENT_MASTER']);
                                    $price_per_session = ($total_session_count > 0) ? $total_bill_and_paid->fields['TOTAL_PAID']/$total_session_count : 0.00;
                                    //$enrollment_balance = $db->Execute("SELECT * FROM `DOA_ENROLLMENT_BALANCE` WHERE `PK_ENROLLMENT_MASTER`=".$row->fields['PK_ENROLLMENT_MASTER']);
                                    $total_paid = $total_bill_and_paid->fields['TOTAL_PAID'];
                                    $total_used = $used_session_count->fields['USED_SESSION_COUNT']*$price_per_session;
                                    $service_credit = $total_bill_and_paid->fields['TOTAL_PAID']-$total_used;
                                    $serviceCodeData = $db_account->Execute("SELECT DOA_SERVICE_CODE.SERVICE_CODE FROM DOA_SERVICE_CODE JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE WHERE DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = ".$row->fields['PK_ENROLLMENT_MASTER']);
                                    $serviceCode = [];
                                    while (!$serviceCodeData->EOF) {
                                        $serviceCode[] = $serviceCodeData->fields['SERVICE_CODE'];
                                        $serviceCodeData->MoveNext();
                                    }
                                    ?>
                                    <div class="row" onclick="$(this).next().slideToggle()" style="cursor:pointer; font-size: 15px; *border: 1px solid #ebe5e2; padding: 8px;">
                                        <div class="col-4"><span class="hidden-sm-up" style="margin-right: 20px;"><i class="ti-arrow-circle-right"></i></span></i> <?=$row->fields['ENROLLMENT_ID']." || ".implode(', ', $serviceCode)?></div>
                                        <div class="col-2">Paid : <?=$total_bill_and_paid->fields['TOTAL_PAID'];?></div>
                                        <div class="col-2">Used : <?=number_format((float)$total_used, 2, '.', ',');?></div>
                                        <div class="col-2" style="color:<?=($service_credit<0)?'red':'black'?>;">Service Credit : <?=number_format((float)$service_credit, 2, '.', ',');?></div>
                                        <div class="col-2">Session : <?=$used_session_count->fields['USED_SESSION_COUNT'].'/'.$total_session_count;?></div>
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
                                        $appointment_data = $db_account->Execute("SELECT DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER, DOA_APPOINTMENT_MASTER.SERIAL_NUMBER, DOA_APPOINTMENT_MASTER.DATE, DOA_APPOINTMENT_MASTER.START_TIME, DOA_APPOINTMENT_MASTER.END_TIME, DOA_SERVICE_MASTER.SERVICE_NAME, DOA_SERVICE_CODE.SERVICE_CODE, DOA_SERVICE_CODE.PRICE AS SESSION_COST, DOA_APPOINTMENT_MASTER.ACTIVE, DOA_APPOINTMENT_STATUS.APPOINTMENT_STATUS, DOA_APPOINTMENT_STATUS.COLOR_CODE FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_SERVICE_MASTER ON DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER LEFT JOIN DOA_SERVICE_CODE ON DOA_APPOINTMENT_MASTER.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE LEFT JOIN $master_database.DOA_APPOINTMENT_STATUS AS DOA_APPOINTMENT_STATUS ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS = $master_database.DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS  WHERE DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_MASTER = ".$row->fields['PK_ENROLLMENT_MASTER']."");
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
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once('../includes/footer.php');?>

</body>
</html>
