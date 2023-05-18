<?php
require_once('../global/config.php');
$title = "Accounts";

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

$query = $db->Execute("SELECT count(DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS TOTAL_RECORDS FROM `DOA_ENROLLMENT_MASTER` INNER JOIN DOA_LOCATION ON DOA_LOCATION.PK_LOCATION = DOA_ENROLLMENT_MASTER.PK_LOCATION  WHERE DOA_ENROLLMENT_MASTER.PK_USER_MASTER IN (".$PK_USER_MASTERS.")".$search);
$number_of_result =  $query->fields['TOTAL_RECORDS'];
$number_of_page = ceil ($number_of_result / $results_per_page);

if (!isset ($_GET['page']) ) {
    $page = 1;
} else {
    $page = $_GET['page'];
}
$page_first_result = ($page-1) * $results_per_page;

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 4){
    header("location:../login.php");
    exit;
}


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
        <div class="container-fluid">
            <div class="row page-titles">
                <div class="col-md-5 align-self-center">
                    <h4 class="text-themecolor"><?=$title?></h4>
                </div>
                <div class="col-md-7 align-self-center text-end">
                    <div class="d-flex justify-content-end align-items-center">
                        <ol class="breadcrumb justify-content-end">
                            <li class="breadcrumb-item active"><?=$title?></li>
                        </ol>
                        <!--<button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='add_schedule.php'" ><i class="fa fa-plus-circle"></i> Create New</button>-->
                    </div>
                </div>
            </div>

            <div class="row">
                <div id="appointment_list_half" class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6">
                                    <h5 class="card-title"><?=$title?></h5>
                                </div>
                            </div>
                            <div class="p-20">
                                <?php
                                $i=$page_first_result+1;
                                $row = $db->Execute("SELECT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, DOA_ENROLLMENT_MASTER.ENROLLMENT_ID, DOA_ENROLLMENT_MASTER.ACTIVE, DOA_LOCATION.LOCATION_NAME FROM `DOA_ENROLLMENT_MASTER` INNER JOIN DOA_LOCATION ON DOA_LOCATION.PK_LOCATION = DOA_ENROLLMENT_MASTER.PK_LOCATION  WHERE DOA_ENROLLMENT_MASTER.PK_USER_MASTER IN (".$PK_USER_MASTERS.")".$search."ORDER BY DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER DESC"." LIMIT " . $page_first_result . ',' . $results_per_page);
                                while (!$row->EOF) {
                                    $total_bill_and_paid = $db->Execute("SELECT SUM(BILLED_AMOUNT) AS TOTAL_BILL, SUM(PAID_AMOUNT) AS TOTAL_PAID FROM DOA_ENROLLMENT_LEDGER WHERE `PK_ENROLLMENT_MASTER`=".$row->fields['PK_ENROLLMENT_MASTER']);
                                    $enrollment_balance = $db->Execute("SELECT * FROM `DOA_ENROLLMENT_BALANCE` WHERE `PK_ENROLLMENT_MASTER`=".$row->fields['PK_ENROLLMENT_MASTER']);
                                    $total_paid = $total_bill_and_paid->fields['TOTAL_PAID'];
                                    ?>
                                    <div class="row" onclick="$(this).next().slideToggle()" style="cursor:pointer; font-size: 15px; *border: 1px solid #ebe5e2; padding: 8px;">
                                        <div class="col-3" style="width: 20%;"><span class="hidden-sm-up" style="margin-right: 20px;"><i class="ti-arrow-circle-right"></i></span></i> <?=$row->fields['ENROLLMENT_ID']?></div>
                                        <div class="col-3" style="width: 20%;"><?=$row->fields['LOCATION_NAME']?></div>
                                        <div class="col-2" style="width: 18%;">Paid : <?=$total_bill_and_paid->fields['TOTAL_PAID'];?></div>
                                        <div class="col-2" style="width: 18%;">Used : <?=($enrollment_balance->RecordCount() > 0)?$enrollment_balance->fields['TOTAL_BALANCE_USED']:'0.00';?></div>
                                        <div class="col-2" style="width: 18%;">Balance : <?=($enrollment_balance->RecordCount() > 0)?($total_bill_and_paid->fields['TOTAL_PAID']-$enrollment_balance->fields['TOTAL_BALANCE_USED']):'0.00';?></div>
                                    </div>
                                    <table id="myTable" class="table table-striped border" style="display: none">
                                        <thead>
                                        <tr>
                                            <th>Service</th>
                                            <th>Service Code</th>
                                            <th>Date</th>
                                            <th>Time</th>
                                            <th>Session Cost</th>
                                            <th>Balance</th>
                                        </tr>
                                        </thead>

                                        <tbody>
                                        <?php
                                        $appointment_data = $db->Execute("SELECT DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER, DOA_APPOINTMENT_MASTER.DATE, DOA_APPOINTMENT_MASTER.START_TIME, DOA_APPOINTMENT_MASTER.END_TIME, DOA_SERVICE_MASTER.SERVICE_NAME, DOA_SERVICE_CODE.SERVICE_CODE, DOA_SERVICE_CODE.PRICE AS SESSION_COST, DOA_APPOINTMENT_MASTER.ACTIVE, DOA_APPOINTMENT_STATUS.APPOINTMENT_STATUS, DOA_APPOINTMENT_STATUS.COLOR_CODE FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_SERVICE_MASTER ON DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER LEFT JOIN DOA_SERVICE_CODE ON DOA_APPOINTMENT_MASTER.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE LEFT JOIN DOA_APPOINTMENT_STATUS ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS = DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS  WHERE DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_MASTER = ".$row->fields['PK_ENROLLMENT_MASTER']." AND DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS = 2");
                                        $total_session_cost = 0;
                                        while (!$appointment_data->EOF) {
                                            $total_session_cost += $appointment_data->fields['SESSION_COST']?>
                                            <tr>
                                                <td><?=$appointment_data->fields['SERVICE_NAME']?></td>
                                                <td><?=$appointment_data->fields['SERVICE_CODE']?></td>
                                                <td><?=date('m/d/Y', strtotime($appointment_data->fields['DATE']))?></td>
                                                <td><?=date('h:i A', strtotime($appointment_data->fields['START_TIME']))." - ".date('h:i A', strtotime($appointment_data->fields['END_TIME']))?></td>
                                                <td><?=$appointment_data->fields['SESSION_COST']?></td>
                                                <td><?=$total_paid-$total_session_cost?></td>
                                            </tr>
                                            <?php $appointment_data->MoveNext();
                                        } ?>
                                        </tbody>
                                    </table>
                                    <?php $row->MoveNext();
                                    $i++; } ?>

                                <div class="center">
                                    <div class="pagination outer">
                                        <ul>
                                            <?php if ($page > 1) { ?>
                                                <li><a href="javascript:;" onclick="showBillingList(<?=($page-1)?>)">&laquo;</a></li>
                                            <?php }
                                            for($page_count = 1; $page_count<=$number_of_page; $page_count++) {
                                                echo '<li><a class="'.(($page_count==$page)?"active":"").'" href="javascript:;" onclick="showBillingList('.$page_count.')">' . $page_count . ' </a></li>';
                                            }
                                            if ($page < $number_of_page) { ?>
                                                <li><a href="javascript:;" onclick="showBillingList(<?=($page+1)?>)">&raquo;</a></li>
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