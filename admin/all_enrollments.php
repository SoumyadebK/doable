<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;
global $results_per_page;

$title = "All Enrollments";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 2 ){
    header("location:../login.php");
    exit;
}

$PK_ENROLLMENT_MASTER_ARRAY = [];
$not_billed_enrollment = $db_account->Execute("SELECT PK_ENROLLMENT_MASTER FROM DOA_ENROLLMENT_MASTER WHERE NOT EXISTS(SELECT PK_ENROLLMENT_MASTER FROM DOA_ENROLLMENT_BILLING WHERE DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER )");
if ($not_billed_enrollment->RecordCount() > 0) {
    while (!$not_billed_enrollment->EOF) {
        $PK_ENROLLMENT_MASTER_ARRAY[] = $not_billed_enrollment->fields['PK_ENROLLMENT_MASTER'];
        $not_billed_enrollment->MoveNext();
    }

    $db_account->Execute("DELETE FROM `DOA_ENROLLMENT_MASTER` WHERE `PK_ENROLLMENT_MASTER` IN (".implode(',', $PK_ENROLLMENT_MASTER_ARRAY).")");
    $db_account->Execute("DELETE FROM `DOA_ENROLLMENT_SERVICE` WHERE `PK_ENROLLMENT_MASTER` IN (".implode(',', $PK_ENROLLMENT_MASTER_ARRAY).")");
}

if (isset($_GET['search_text']) || isset($_GET['FROM_DATE']) || isset($_GET['END_DATE'])) {
    $FROM_DATE = date('Y-m-d', strtotime($_GET['FROM_DATE']));
    $END_DATE = date('Y-m-d', strtotime($_GET['END_DATE']));
    $search_text = $_GET['search_text'];
    $search = " AND (DOA_USERS.FIRST_NAME LIKE '%".$search_text."%' OR DOA_USERS.LAST_NAME LIKE '%".$search_text."%'OR DOA_USERS.EMAIL_ID LIKE '%".$search_text."%' OR DOA_USERS.PHONE LIKE '%".$search_text."%')". " AND DOA_ENROLLMENT_MASTER.CREATED_ON BETWEEN '$FROM_DATE' AND '$END_DATE'";
} else {
    $FROM_DATE='';
    $END_DATE='';
    $search_text = '';
    $search = ' ';
}

$query = $db_account->Execute("SELECT count(DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS TOTAL_RECORDS FROM  DOA_ENROLLMENT_MASTER INNER JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER INNER JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER LEFT JOIN $master_database.DOA_LOCATION AS DOA_LOCATION ON DOA_LOCATION.PK_LOCATION = DOA_ENROLLMENT_MASTER.PK_LOCATION LEFT JOIN DOA_ENROLLMENT_BALANCE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_BALANCE.PK_ENROLLMENT_MASTER WHERE DOA_USERS.IS_DELETED = 0 AND DOA_USER_MASTER.PRIMARY_LOCATION_ID IN (".$_SESSION['DEFAULT_LOCATION_ID'].")".$search);

$number_of_result = ($query->RecordCount() > 0) ? $query->fields['TOTAL_RECORDS'] : 1;
$number_of_page = ceil ($number_of_result / $results_per_page);

if (!isset ($_GET['page']) ) {
    $page = 1;
} else {
    $page = $_GET['page'];
}

$page_first_result = ($page-1) * $results_per_page;

if (isset($_POST['SUBMIT'])){
    $PK_ENROLLMENT_MASTER = $_POST['PK_ENROLLMENT_MASTER'];
    $PK_PAYMENT_TYPE_REFUND = ($_POST['PK_PAYMENT_TYPE_REFUND']) ?? 0;
    $enrollment_data = $db_account->Execute("SELECT ENROLLMENT_NAME, ENROLLMENT_ID, PK_ENROLLMENT_BILLING FROM DOA_ENROLLMENT_MASTER JOIN DOA_ENROLLMENT_BILLING ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = ".$PK_ENROLLMENT_MASTER);
    if(empty($enrollment_data->fields['ENROLLMENT_NAME'])){
        $enrollment_name = '';
    }else {
        $enrollment_name = $enrollment_data->fields['ENROLLMENT_NAME']." - ";
    }
    if(empty($enrollment_data->fields['ENROLLMENT_ID'])) {
        $enrollment_id = $enrollment_data->fields['MISC_ID'];
    } else {
        $enrollment_id = $enrollment_data->fields['ENROLLMENT_ID'];
    }
    $TOTAL_POSITIVE_BALANCE = $_POST['TOTAL_POSITIVE_BALANCE'];
    $TOTAL_NEGATIVE_BALANCE = $_POST['TOTAL_NEGATIVE_BALANCE'];

    if ($TOTAL_POSITIVE_BALANCE == 0 && $TOTAL_NEGATIVE_BALANCE == 0) {
        $UPDATE_DATA['STATUS'] = 'C';
    } else {
        $UPDATE_DATA['STATUS'] = 'CA';
    }

    if ($_POST['CANCEL_FUTURE_APPOINTMENT'] == 1){
        $db_account->Execute("DELETE FROM `DOA_APPOINTMENT_ENROLLMENT` WHERE `PK_ENROLLMENT_MASTER` = '$PK_ENROLLMENT_MASTER' AND TYPE = 'CREATED'");
        $CONDITION = " PK_ENROLLMENT_MASTER =  '$PK_ENROLLMENT_MASTER' AND PK_APPOINTMENT_STATUS != 2";
    } else {
        $CONDITION = " PK_ENROLLMENT_MASTER =  '$PK_ENROLLMENT_MASTER' AND PK_APPOINTMENT_STATUS != 2 AND IS_PAID = 0";
    }

    $BALANCE = $TOTAL_POSITIVE_BALANCE + $TOTAL_NEGATIVE_BALANCE;
    if ($_POST['USE_AVAILABLE_CREDIT'] == 1) {
        $TOTAL_POSITIVE_BALANCE += $TOTAL_NEGATIVE_BALANCE;
        $TOTAL_NEGATIVE_BALANCE = $TOTAL_POSITIVE_BALANCE;
        for ($i = 0; $i < count($_POST['PK_ENROLLMENT_SERVICE']); $i++) {
            $ENR_SERVICE_UPDATE['TOTAL_AMOUNT_PAID'] = $_POST['TOTAL_AMOUNT_PAID'][$i];
            db_perform_account('DOA_ENROLLMENT_SERVICE', $ENR_SERVICE_UPDATE, 'update'," PK_ENROLLMENT_SERVICE = ".$_POST['PK_ENROLLMENT_SERVICE'][$i]);
        }
    }

    db_perform_account('DOA_APPOINTMENT_MASTER', $UPDATE_DATA, 'update', $CONDITION);

    db_perform_account('DOA_ENROLLMENT_MASTER', $UPDATE_DATA, 'update'," PK_ENROLLMENT_MASTER =  '$PK_ENROLLMENT_MASTER'");
    db_perform_account('DOA_ENROLLMENT_SERVICE', $UPDATE_DATA, 'update'," PK_ENROLLMENT_MASTER =  '$PK_ENROLLMENT_MASTER'");
    db_perform_account('DOA_ENROLLMENT_LEDGER', $UPDATE_DATA, 'update'," PK_ENROLLMENT_MASTER =  '$PK_ENROLLMENT_MASTER'");

    $LEDGER_DATA['TRANSACTION_TYPE'] = 'Canceled';
    $LEDGER_DATA['ENROLLMENT_LEDGER_PARENT'] = -1;
    $LEDGER_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
    $LEDGER_DATA['PK_ENROLLMENT_BILLING'] = $enrollment_data->fields['PK_ENROLLMENT_BILLING'];
    $LEDGER_DATA['PAID_AMOUNT'] = 0.00;
    $LEDGER_DATA['IS_PAID'] = 1;
    $LEDGER_DATA['DUE_DATE'] = date('Y-m-d');
    $LEDGER_DATA['BILLED_AMOUNT'] = 0.00;
    $LEDGER_DATA['BALANCE'] = $BALANCE;
    $LEDGER_DATA['STATUS'] = $UPDATE_DATA['STATUS'];
    db_perform_account('DOA_ENROLLMENT_LEDGER', $LEDGER_DATA, 'insert');

    $PK_USER_MASTER = $_POST['PK_USER_MASTER'];
    if ($TOTAL_POSITIVE_BALANCE > 0) {
        /*$wallet_data = $db_account->Execute("SELECT * FROM DOA_CUSTOMER_WALLET WHERE PK_USER_MASTER = '$PK_USER_MASTER' ORDER BY PK_CUSTOMER_WALLET DESC LIMIT 1");
        if ($wallet_data->RecordCount() > 0) {
            $INSERT_DATA['CURRENT_BALANCE'] = $wallet_data->fields['CURRENT_BALANCE'] + $BALANCE;
        } else {
            $INSERT_DATA['CURRENT_BALANCE'] = $TOTAL_POSITIVE_BALANCE;
        }
        $INSERT_DATA['PK_USER_MASTER'] = $PK_USER_MASTER;
        $INSERT_DATA['CREDIT'] = $TOTAL_POSITIVE_BALANCE;
        $INSERT_DATA['DESCRIPTION'] = "Balance credited for cancellation of enrollment ".$enrollment_name.$enrollment_data->fields['ENROLLMENT_ID'];
        $INSERT_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
        $INSERT_DATA['CREATED_ON'] = date("Y-m-d H:i");
        db_perform_account('DOA_CUSTOMER_WALLET', $INSERT_DATA, 'insert');*/

        $LEDGER_DATA_REFUND['TRANSACTION_TYPE'] = ($_POST['SUBMIT'] == 'Cancel and Store Info only') ? 'Refund Credit Available' : 'Refund';
        $LEDGER_DATA_REFUND['ENROLLMENT_LEDGER_PARENT'] = -1;
        $LEDGER_DATA_REFUND['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
        $LEDGER_DATA_REFUND['PK_ENROLLMENT_BILLING'] = $enrollment_data->fields['PK_ENROLLMENT_BILLING'];
        $LEDGER_DATA_REFUND['PAID_AMOUNT'] = 0.00;
        $LEDGER_DATA_REFUND['IS_PAID'] = 2;
        $LEDGER_DATA_REFUND['DUE_DATE'] = date('Y-m-d');
        $LEDGER_DATA_REFUND['BILLED_AMOUNT'] = 0.00;
        $LEDGER_DATA_REFUND['BALANCE'] = $TOTAL_POSITIVE_BALANCE;
        $LEDGER_DATA_REFUND['STATUS'] = $UPDATE_DATA['STATUS'];
        db_perform_account('DOA_ENROLLMENT_LEDGER', $LEDGER_DATA_REFUND, 'insert');
        $PK_ENROLLMENT_LEDGER = $db_account->insert_ID();

        if ($LEDGER_DATA_REFUND['TRANSACTION_TYPE'] == 'Refund') {
            $receipt = $db_account->Execute("SELECT RECEIPT_NUMBER FROM DOA_ENROLLMENT_PAYMENT WHERE IS_ORIGINAL_RECEIPT = 1 ORDER BY CONVERT(RECEIPT_NUMBER, DECIMAL) DESC LIMIT 1");
            if ($receipt->RecordCount() > 0) {
                $lastSerialNumber = $receipt->fields['RECEIPT_NUMBER'];
                $RECEIPT_NUMBER = $lastSerialNumber + 1;
            } else {
                $RECEIPT_NUMBER = 1;
            }

            $PAYMENT_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
            $PAYMENT_DATA['PK_ENROLLMENT_BILLING'] = $enrollment_data->fields['PK_ENROLLMENT_BILLING'];
            $PAYMENT_DATA['PK_PAYMENT_TYPE'] = $PK_PAYMENT_TYPE_REFUND;
            $PAYMENT_DATA['AMOUNT'] = $TOTAL_POSITIVE_BALANCE;
            $PAYMENT_DATA['PK_ENROLLMENT_LEDGER'] = $PK_ENROLLMENT_LEDGER;
            $PAYMENT_DATA['TYPE'] = 'Refund';
            $PAYMENT_DATA['NOTE'] = "Balance credited from enrollment " . $enrollment_name . $enrollment_id;
            $PAYMENT_DATA['PAYMENT_DATE'] = date('Y-m-d');
            $PAYMENT_DATA['PAYMENT_INFO'] = 'Refund';
            $PAYMENT_DATA['PAYMENT_STATUS'] = 'Success';
            $PAYMENT_DATA['RECEIPT_NUMBER'] = $RECEIPT_NUMBER;
            $PAYMENT_DATA['IS_ORIGINAL_RECEIPT'] = 1;
            db_perform_account('DOA_ENROLLMENT_PAYMENT', $PAYMENT_DATA, 'insert');
        }
    }
    if ($TOTAL_NEGATIVE_BALANCE < 0) {
        $LEDGER_DATA_BILLING['TRANSACTION_TYPE'] = ($_POST['SUBMIT'] == 'Cancel and Store Info only') ? 'Balance Owed' : 'Billing';
        $LEDGER_DATA_BILLING['ENROLLMENT_LEDGER_PARENT'] = -1;
        $LEDGER_DATA_BILLING['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
        $LEDGER_DATA_BILLING['PK_ENROLLMENT_BILLING'] = $enrollment_data->fields['PK_ENROLLMENT_BILLING'];
        $LEDGER_DATA_BILLING['PAID_AMOUNT'] = 0.00;
        $LEDGER_DATA_BILLING['IS_PAID'] = 0;
        $LEDGER_DATA_BILLING['STATUS'] = 'A';
        $LEDGER_DATA_BILLING['DUE_DATE'] = date('Y-m-d');
        $LEDGER_DATA_BILLING['BILLED_AMOUNT'] = abs($TOTAL_NEGATIVE_BALANCE);
        $LEDGER_DATA_BILLING['BALANCE'] = abs($TOTAL_NEGATIVE_BALANCE);
        db_perform_account('DOA_ENROLLMENT_LEDGER', $LEDGER_DATA_BILLING, 'insert');
    }
    header('location:all_enrollments.php');
}

/*if(!empty($_GET['id']) && !empty($_GET['status'])) {
    if ($_GET['status'] == 'active') {
        $PK_ENROLLMENT_MASTER = $_GET['id'];
        $UPDATE_DATA['STATUS'] = 'A';
        db_perform_account('DOA_APPOINTMENT_MASTER', $UPDATE_DATA, 'update'," PK_ENROLLMENT_MASTER =  '$PK_ENROLLMENT_MASTER'");
        db_perform_account('DOA_ENROLLMENT_MASTER', $UPDATE_DATA, 'update'," PK_ENROLLMENT_MASTER =  '$PK_ENROLLMENT_MASTER'");
        db_perform_account('DOA_ENROLLMENT_LEDGER', $UPDATE_DATA, 'update'," PK_ENROLLMENT_MASTER =  '$PK_ENROLLMENT_MASTER'");
        header('location:all_enrollments.php');
    }
}*/

?>

<!DOCTYPE html>
<link href="//maxcdn.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.min.css" rel="stylesheet">
<style>
    /* Table sort indicators */

    th.sortable {
        position: relative;
        cursor: pointer;
    }

    th.sortable::after {
        font-family: FontAwesome;
        content: "\f0dc";
        position: absolute;
        right: 8px;
        color: #999;
    }

    th.sortable.asc::after {
        content: "\f0d8";
    }

    th.sortable.desc::after {
        content: "\f0d7";
    }

    th.sortable:hover::after {
        color: #333;
    }

</style>
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
                <div class="col-md-2 align-self-center">
                    <h4 class="text-themecolor"><?=$title?></h4>
                </div>
                <div class="col-md-8 align-self-center">
                    <form class="form-material form-horizontal" action="" method="get">
                        <div class="input-group" style="width: 80%; margin: auto;">
                            <div style="margin-right: 5px">
                                <input type="text" id="FROM_DATE" name="FROM_DATE" placeholder="From Date" class="form-control datepicker-past" value="<?=($FROM_DATE == '' || $FROM_DATE == '0000-00-00')?'':date('m/d/Y', strtotime($FROM_DATE))?>">
                            </div>
                            <div style="margin-right: 5px">
                                <input type="text" id="END_DATE" name="END_DATE" placeholder="To Date" class="form-control datepicker-normal" value="<?=($END_DATE == '' || $END_DATE == '0000-00-00')?'':date('m/d/Y', strtotime($END_DATE))?>">
                            </div>
                            <div style="margin-right: 5px">
                                <input class="form-control" type="text" name="search_text" placeholder="Search.." value="<?=$search_text?>">
                            </div>
                            <div>
                                <button class="btn btn-info waves-effect waves-light m-r-10 text-white input-group-btn m-b-1" type="submit"><i class="fa fa-search"></i></button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="col-md-2 align-self-center text-end">
                    <div class="d-flex justify-content-end align-items-center">
                        <button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='enrollment.php'" ><i class="fa fa-plus-circle"></i> Create New</button>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table  class="table table-striped border" data-page-length='50'>
                                    <thead>
                                    <tr>
                                        <th data-type="number" class="sortable" style="cursor: pointer">No</th>
                                        <th data-type="string" class="sortable" style="cursor: pointer">Customer</th>
                                        <th data-type="string" class="sortable" style="cursor: pointer">Enrollment Id</th>
                                        <th data-type="number" class="sortable" style="cursor: pointer">Total Amount</th>
                                        <th data-type="number" class="sortable" style="cursor: pointer">Date</th>
                                        <th data-type="string" class="sortable" style="cursor: pointer">Email ID</th>
                                        <th data-type="string" class="sortable" style="cursor: pointer">Phone</th>
                                        <th data-type="string" class="sortable" style="cursor: pointer">Location</th>
                                        <th>Actions</th>
                                        <th>Status</th>
                                        <th>Cancel</th>
                                    </tr>
                                    </thead>

                                    <tbody>
                                    <?php
                                    $i=$page_first_result+1;
                                    $row = $db_account->Execute("SELECT DISTINCT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, DOA_ENROLLMENT_MASTER.ENROLLMENT_NAME, DOA_ENROLLMENT_MASTER.ENROLLMENT_DATE, DOA_ENROLLMENT_MASTER.ENROLLMENT_ID, DOA_ENROLLMENT_MASTER.MISC_ID, DOA_ENROLLMENT_MASTER.ACTIVE, DOA_ENROLLMENT_MASTER.STATUS, DOA_ENROLLMENT_MASTER.PK_USER_MASTER, DOA_USERS.FIRST_NAME, DOA_USERS.LAST_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.PHONE, DOA_LOCATION.LOCATION_NAME, DOA_ENROLLMENT_BALANCE.TOTAL_BALANCE_PAID, DOA_ENROLLMENT_BALANCE.TOTAL_BALANCE_USED, DOA_USER_MASTER.PK_USER_MASTER, DOA_ENROLLMENT_BILLING.TOTAL_AMOUNT FROM DOA_ENROLLMENT_MASTER INNER JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER INNER JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER LEFT JOIN $master_database.DOA_LOCATION AS DOA_LOCATION ON DOA_LOCATION.PK_LOCATION = DOA_ENROLLMENT_MASTER.PK_LOCATION LEFT JOIN DOA_ENROLLMENT_BALANCE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_BALANCE.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_BILLING ON DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_USERS.IS_DELETED = 0 AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_USERS.ACTIVE = 1 AND DOA_USERS.IS_DELETED = 0 ".$search." ORDER BY DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER DESC LIMIT " . $page_first_result . ',' . $results_per_page);
                                    while (!$row->EOF) {
                                        $name = $row->fields['ENROLLMENT_NAME'];
                                        if (is_null($row->fields['MISC_ID'])) {
                                            $id = $row->fields['ENROLLMENT_ID'];
                                        } else {
                                            $id = $row->fields['MISC_ID'];
                                        }
                                        if(empty($name)){
                                            $enrollment_name = ' ';
                                        }else {
                                            $enrollment_name = "$name"." - ";
                                        }
                                        $serviceCodeData = $db_account->Execute("SELECT DOA_SERVICE_CODE.PK_SERVICE_CODE, DOA_SERVICE_CODE.SERVICE_CODE, DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION, DOA_ENROLLMENT_SERVICE.PRICE_PER_SESSION, DOA_ENROLLMENT_SERVICE.TOTAL_AMOUNT_PAID, DOA_ENROLLMENT_SERVICE.SESSION_CREATED, DOA_ENROLLMENT_SERVICE.SESSION_COMPLETED FROM DOA_SERVICE_CODE JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE WHERE DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = ".$row->fields['PK_ENROLLMENT_MASTER']);
                                        $serviceCode = [];
                                        while (!$serviceCodeData->EOF) {
                                            $serviceCode[] = $serviceCodeData->fields['SERVICE_CODE'].': '.$serviceCodeData->fields['NUMBER_OF_SESSION'];
                                            $serviceCodeData->MoveNext();
                                        } ?>
                                        <tr>
                                            <td onclick="editpage(<?=$row->fields['PK_ENROLLMENT_MASTER']?>);"><?=$i;?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_ENROLLMENT_MASTER']?>);"><?=$row->fields['FIRST_NAME']." ".$row->fields['LAST_NAME']?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_ENROLLMENT_MASTER']?>);"><?=$enrollment_name.$id." || ".implode(', ', $serviceCode)?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_ENROLLMENT_MASTER']?>);"><?=$row->fields['TOTAL_AMOUNT']?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_ENROLLMENT_MASTER']?>);"><?=date('m-d-Y', strtotime($row->fields['ENROLLMENT_DATE']))?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_ENROLLMENT_MASTER']?>);"><?=$row->fields['EMAIL_ID']?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_ENROLLMENT_MASTER']?>);"><?=$row->fields['PHONE']?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_ENROLLMENT_MASTER']?>);"><?=$row->fields['LOCATION_NAME']?></td>
                                            <td>
                                                <a href="enrollment.php?id=<?=$row->fields['PK_ENROLLMENT_MASTER']?>" title="Edit" style="font-size:18px"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

                                                <?php if($row->fields['ACTIVE']==1){ ?>
                                                    <span class="active-box-green"></span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                <?php } else{ ?>
                                                    <span class="active-box-red"></span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                <?php } ?>

                                                <a href="enrollment.php?customer_id=<?=$row->fields['PK_USER_MASTER']?>" title="Add Enrollment" style="font-size:18px"><i class="fa fa-plus-circle"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                <?php
                                                $res = $db_account->Execute("SELECT SUM(TOTAL_AMOUNT_PAID) AS TOTAL_PAID, SUM(SESSION_COMPLETED) AS COMPLETED FROM DOA_ENROLLMENT_SERVICE WHERE PK_ENROLLMENT_MASTER = ".$row->fields['PK_ENROLLMENT_MASTER']);
                                                if($res->fields['TOTAL_PAID']==0 && $res->fields['COMPLETED']==0){
                                                ?>
                                                <a href="all_enrollments.php?type=del&id=<?=$row->fields['PK_ENROLLMENT_MASTER']?>" onclick='javascript:ConfirmDelete(<?=$row->fields['PK_ENROLLMENT_MASTER']?>);return false;' title="Delete" style="font-size:18px"><i class="fa fa-trash"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                <?php } ?>
                                            </td>
                                            <td onclick="editpage(<?=$row->fields['PK_ENROLLMENT_MASTER']?>);">
                                                <?php if ($row->fields['STATUS']=='A') { ?>
                                                    <i class="fa fa-check-circle" style="font-size:21px;color:#35e235;"></i>
                                                <?php } else { ?>
                                                    <span class="fa fa-check-circle" style="font-size:21px;color:#ff0000;"></span>
                                                <?php } ?>
                                            </td>
                                            <td>
                                                <?php if ($row->fields['STATUS']=='A') { ?>
                                                    <a href="javascript:;" onclick="cancelEnrollment(<?=$row->fields['PK_ENROLLMENT_MASTER']?>, <?=$row->fields['PK_USER_MASTER']?>)"><img src="../assets/images/noun-cancel-button.png" alt="LOGO" style="height: 21px; width: 21px;"></a>
                                                <?php } else { ?>
                                                        <p style="color: red;">Cancelled</p>
                                                    <!--<a href="all_enrollments.php?id=<?php /*=$row->fields['PK_ENROLLMENT_MASTER']*/?>&status=active">Active Enrollment</a>-->
                                                <?php } ?>
                                            </td>
                                        </tr>
                                        <?php $row->MoveNext();
                                        $i++; } ?>
                                    </tbody>
                                </table>
                                <div class="center">
                                    <div class="pagination outer">
                                        <ul>
                                            <?php if ($page > 1) { ?>
                                                <li><a href="all_enrollments.php?page=1">&laquo;</a></li>
                                                <li><a href="all_enrollments.php?page=<?=($page-1)?>">&lsaquo;</a></li>
                                            <?php }
                                            for($page_count = 1; $page_count<=$number_of_page; $page_count++) {
                                                if ($page_count == $page || $page_count == ($page+1) || $page_count == ($page-1) || $page_count == $number_of_page) {
                                                    echo '<li><a class="' . (($page_count == $page) ? "active" : "") . '" href="all_enrollments.php?page=' . $page_count . (($search_text == '') ? '' : '&search_text=' . $search_text) . '">' . $page_count . ' </a></li>';
                                                } elseif ($page_count == ($number_of_page-1)){
                                                    echo '<li><a href="javascript:;" onclick="showHiddenPageNumber(this);" style="border: none; margin: 0; padding: 8px;">...</a></li>';
                                                } else {
                                                    echo '<li><a class="hidden" href="all_enrollments.php?page=' . $page_count . (($search_text == '') ? '' : '&search_text=' . $search_text) . '">' . $page_count . ' </a></li>';
                                                }
                                            }
                                            if ($page < $number_of_page) { ?>
                                                <li><a href="all_enrollments.php?page=<?=($page+1)?>">&rsaquo;</a></li>
                                                <li><a href="all_enrollments.php?page=<?=$number_of_page?>">&raquo;</a></li>
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

<div class="modal fade" id="enrollment_cancel_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form class="p-20" action="" method="post">

            <div class="modal-content">
                <div class="modal-header">
                    <h4><b>Cancel Enrollment</b></h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="card">
                        <div class="card-body">

                            <div id="step_1">
                                <input type="hidden" name="PK_ENROLLMENT_MASTER" class="PK_ENROLLMENT_MASTER">
                                <input type="hidden" name="PK_USER_MASTER" class="PK_USER_MASTER">
                                <div class="form-group">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <label>Cancel All Future Appointments? <input type="radio" name="CANCEL_FUTURE_APPOINTMENT" value="1" checked/></label>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <label>Cancel Only Unpaid Future Appointments? <input type="radio" name="CANCEL_FUTURE_APPOINTMENT" value="2"/></label>
                                        </div>
                                    </div>
                                </div>
                                <a href="javascript:" class="btn btn-info waves-effect waves-light text-white next" style="float: right;" onclick="$('#step_1').hide();$('#step_2').show();">Continue</a>
                            </div>

                            <div id="step_2" style="display: none;">
                                <div class="form-group">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label>Use available credits to pay pending balances?</label>
                                        </div>
                                        <div class="col-md-2">
                                            <label><input type="radio" name="USE_AVAILABLE_CREDIT" value="1" checked/>&nbsp;Yes</label>&nbsp;&nbsp;
                                            <!--<label><input type="radio" name="USE_AVAILABLE_CREDIT" value="0"/>&nbsp;No</label>-->
                                        </div>
                                    </div>
                                </div>
                                <a href="javascript:" class="btn btn-info waves-effect waves-light m-l-10 text-white next" style="float: right;" onclick="$('#step_2').hide();$('#step_3').show();showEnrollmentServiceDetails();">Continue</a>
                                <a href="javascript:" class="btn btn-info waves-effect waves-light text-white prev" style="float: right;" onclick="$('#step_2').hide();$('#step_1').show();">Go Back</a>
                            </div>

                            <div id="step_3" style="display: none;">
                                <div id="enrollment_service_details">

                                </div>
                                <div class="form-group negative_balance_div" style="display: none;">
                                    <label class="form-label">How you want to your pay?</label>
                                    <div class="col-md-8">
                                        <select class="form-control" name="PK_PAYMENT_TYPE" id="PK_PAYMENT_TYPE">
                                            <option value="">Select</option>
                                            <?php
                                            $row = $db->Execute("SELECT * FROM DOA_PAYMENT_TYPE WHERE ACTIVE = 1");
                                            while (!$row->EOF) { ?>
                                                <option value="<?php echo $row->fields['PK_PAYMENT_TYPE'];?>"><?=$row->fields['PAYMENT_TYPE']?></option>
                                            <?php $row->MoveNext(); } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group negative_balance_div" style="display: none;">
                                    <div class="row">
                                        <b>Note: Please pay $<span id="total_negative_balance"></span> to cancel your enrollment.</b>
                                    </div>
                                </div>

                                <div class="form-group credit_balance_div" style="display: none;">
                                    <label class="form-label">Refund Method?</label>
                                    <div class="col-md-8">
                                        <select class="form-control" name="PK_PAYMENT_TYPE_REFUND" id="PK_PAYMENT_TYPE_REFUND">
                                            <option value="">Select</option>
                                            <?php
                                            $row = $db->Execute("SELECT * FROM DOA_PAYMENT_TYPE WHERE ACTIVE = 1");
                                            while (!$row->EOF) { ?>
                                                <option value="<?php echo $row->fields['PK_PAYMENT_TYPE'];?>"><?=$row->fields['PAYMENT_TYPE']?></option>
                                            <?php $row->MoveNext(); } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group credit_balance_div" style="display: none;">
                                    <div class="row">
                                        <b>Note: Credit balance $<span id="total_credit_balance"></span> will be moved  to Wallet.</b>
                                    </div>
                                </div>

                                <input type="submit" name="SUBMIT" value="Cancel and Store Info only" style="float: right; margin-left: 8px; background-color: #39B54A; border-color: #39B54A; padding: 5px 10px; color: white; font-size: 15px; border-radius: 5px;"/>
                                <input type="submit" name="SUBMIT" value="Submit" style="float: right; margin-left: 8px; background-color: #39B54A; border-color: #39B54A; padding: 5px 10px; color: white; font-size: 15px; border-radius: 5px;"/>
                                <a href="javascript:" style="float: right; background-color: #39B54A; border-color: #39B54A; padding: 7px 10px; color: white; font-size: 15px; border-radius: 5px;" onclick="$('#step_3').hide();$('#step_2').show();">Go Back</a>
                            </div>

                        </div>
                    </div>
                </div>
                <!--<div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white" style="float: right;">Submit</button>
                </div>-->
            </div>
        </form>
    </div>
</div>

<?php require_once('../includes/footer.php');?>

<script>
    function ConfirmDelete(PK_ENROLLMENT_MASTER)
    {
        var conf = confirm("Are you sure you want to delete?");
        if(conf) {
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                data: {FUNCTION_NAME: 'deleteEnrollmentData', PK_ENROLLMENT_MASTER: PK_ENROLLMENT_MASTER},
                success: function (data) {
                    window.location.href = `all_enrollments.php`;
                }
            });
        }
    }

    $(document).ready(function(){
        $("#FROM_DATE").datepicker({
            numberOfMonths: 1,
            onSelect: function(selected) {
                $("#END_DATE").datepicker("option","minDate", selected);
                $("#FROM_DATE, #END_DATE").trigger("change");
            }
        });
        $("#END_DATE").datepicker({
            numberOfMonths: 1,
            onSelect: function(selected) {
                $("#FROM_DATE").datepicker("option","maxDate", selected)
            }
        });
    });

    function editpage(id){
        //alert(i);
        window.location.href = "enrollment.php?id="+id;
    }

    function cancelEnrollment(PK_ENROLLMENT_MASTER, PK_USER_MASTER) {
        $('.PK_ENROLLMENT_MASTER').val(PK_ENROLLMENT_MASTER);
        $('.PK_USER_MASTER').val(PK_USER_MASTER);
        $('#enrollment_cancel_modal').modal('show');
    }

    function showEnrollmentServiceDetails() {
        let PK_ENROLLMENT_MASTER = $('.PK_ENROLLMENT_MASTER').val();
        let USE_AVAILABLE_CREDIT = $('input[name="USE_AVAILABLE_CREDIT"]:checked').val();
        let CANCEL_FUTURE_APPOINTMENT = $('input[name="CANCEL_FUTURE_APPOINTMENT"]:checked').val();
        $.ajax({
            url: "includes/enrollment_service_details.php",
            type: 'GET',
            data: {PK_ENROLLMENT_MASTER: PK_ENROLLMENT_MASTER, USE_AVAILABLE_CREDIT:USE_AVAILABLE_CREDIT, CANCEL_FUTURE_APPOINTMENT:CANCEL_FUTURE_APPOINTMENT},
            success: function (data) {
                $('#enrollment_service_details').html(data);
                $('.negative_balance_div').slideUp();
                $('.credit_balance_div').slideUp();

                let TOTAL_POSITIVE_BALANCE = parseFloat($('#TOTAL_POSITIVE_BALANCE').val());
                let TOTAL_NEGATIVE_BALANCE = parseFloat($('#TOTAL_NEGATIVE_BALANCE').val());

                if (USE_AVAILABLE_CREDIT == 1) {
                    TOTAL_POSITIVE_BALANCE += TOTAL_NEGATIVE_BALANCE;
                    TOTAL_NEGATIVE_BALANCE = TOTAL_POSITIVE_BALANCE;
                }

                if (TOTAL_POSITIVE_BALANCE > 0) {
                    $('.credit_balance_div').slideDown();
                    $('#total_credit_balance').text(parseFloat(TOTAL_POSITIVE_BALANCE).toFixed(2));
                }
                if (TOTAL_NEGATIVE_BALANCE < 0) {
                    $('.negative_balance_div').slideDown();
                    $('#total_negative_balance').text(Math.abs(parseFloat(TOTAL_NEGATIVE_BALANCE).toFixed(2)));
                }
            }
        });
    }

    /*function checkCancelStatus(){
        let CANCEL_FUTURE_APPOINTMENT = $('input[name="CANCEL_FUTURE_APPOINTMENT"]:checked').val();
        let total_credit_balance = $('.CREDIT_BALANCE').val();
        let total_credit_balance_session_created = $('.CREDIT_BALANCE_SESSION_CREATED').val();

        if (CANCEL_FUTURE_APPOINTMENT == 1) {
            if (total_credit_balance == 0) {
                $('.credit_balance_div').slideUp();
                $('.negative_balance_div').slideUp();
            } else {
                if (total_credit_balance > 0) {
                    $('.credit_balance_div').slideDown();
                    $('.negative_balance_div').slideUp();
                    $('.ACTUAL_CREDIT_BALANCE').val(total_credit_balance);
                    $('#total_credit_balance').text(parseFloat(total_credit_balance).toFixed(2));
                } else {
                    $('.credit_balance_div').slideUp();
                    $('.negative_balance_div').slideDown();
                    $('#total_negative_balance').text(Math.abs(parseFloat(total_credit_balance).toFixed(2)));
                }
            }
        } else {
            if (total_credit_balance_session_created == 0) {
                $('.credit_balance_div').slideUp();
                $('.negative_balance_div').slideUp();
            } else {
                if (total_credit_balance_session_created > 0) {
                    $('.credit_balance_div').slideDown();
                    $('.negative_balance_div').slideUp();
                    $('.ACTUAL_CREDIT_BALANCE').val(total_credit_balance_session_created);
                    $('#total_credit_balance').text(parseFloat(total_credit_balance_session_created).toFixed(2));
                } else {
                    $('.credit_balance_div').slideUp();
                    $('.negative_balance_div').slideDown();
                    $('#total_negative_balance').text(Math.abs(parseFloat(total_credit_balance_session_created).toFixed(2)));
                }
            }
        }
    }*/

</script>

<script>
    $(function() {
        const ths = $("th");
        let sortOrder = 1;

        ths.on("click", function() {
            const rows = sortRows(this);
            rebuildTbody(rows);
            //updateClassName(this);
            sortOrder *= -1; //反転
        })

        function sortRows(th) {
            const rows = $.makeArray($('tbody > tr'));
            const col = th.cellIndex;
            const type = th.dataset.type;
            rows.sort(function(a, b) {
                return compare(a, b, col, type) * sortOrder;
            });
            return rows;
        }

        function compare(a, b, col, type) {
            let _a = a.children[col].textContent;
            let _b = b.children[col].textContent;
            if (type === "number") {
                _a *= 1;
                _b *= 1;
            } else if (type === "string") {
                //全て小文字に揃えている。toLowerCase()
                _a = _a.toLowerCase();
                _b = _b.toLowerCase();
            }

            if (_a < _b) {
                return -1;
            }
            if (_a > _b) {
                return 1;
            }
            return 0;
        }

        function rebuildTbody(rows) {
            const tbody = $("tbody");
            while (tbody.firstChild) {
                tbody.remove(tbody.firstChild);
            }

            let j;
            for (j=0; j<rows.length; j++) {
                tbody.append(rows[j]);
            }
        }

  /*      function updateClassName(th) {
            let k;
            for (k=0; k<ths.length; k++) {
                ths[k].className = "";
            }
            th.className = sortOrder === 1 ? "asc" : "desc";
        }*/

    });
</script>
<script>
    function Checktrim(str) {
        str = str.replace(/^\s+/, '');
        for (var i = str.length - 1; i >= 0; i--) {
            if (/\S/.test(str.charAt(i))) {
                str = str.substring(0, i + 1);
                break;
            }
        }
        return str;
    }
    function stringMonth(month) {

        if(month=="jan" || month=="Jan"){month=01;}
        else if(month=="feb" || month=="Feb"){month=02;}
        else if(month=="mar" || month=="Mar"){month=03;}
        else if(month=="apr" || month=="Apr"){month=04;}
        else if(month=="may" || month=="May"){month=05;}
        else if(month=="jun" || month=="Jun"){month=06;}
        else if(month=="jul" || month=="Jul"){month=07;}
        else if(month=="aug" || month=="Aug"){month=08;}
        else if(month=="sep" || month=="Sep"){month=09;}
        else if(month=="oct" || month=="Oct"){month=10;}
        else if(month=="nov" || month=="Nov"){month=11;}
        else{month=12;}


        return month;
    }

    function dateHeight(dateStr){


        if (Checktrim(dateStr) != ''  && Checktrim(dateStr) != '(none)' && (Checktrim(dateStr)).indexOf(',') > -1 ) {

            var frDateParts = Checktrim(dateStr).split(',');

            var day = frDateParts[0].substring(3) * 60 * 24;
            var strMonth=frDateParts[0].substring(0,3);
            var month = stringMonth(strMonth) * 60 * 24 * 31;
            var year = (frDateParts[1].trim()).substring(0,4) * 60 * 24 * 366;

            var x = day+month+year;


        } else {
            var x =0; //highest value posible
        }

        return x;
    }

    jQuery.fn.dataTableExt.oSort['data-date-asc'] = function(a, b) {
        var x = dateHeight(a) === 0 ? dateHeight(b)+1 : dateHeight(a) ;
        var y = dateHeight(b)=== 0 ? dateHeight(a)+1 : dateHeight(b);
        var z = ((x < y) ? -1 : ((x > y) ? 1 : 0));
        return z;
    };

    jQuery.fn.dataTableExt.oSort['data-date-desc'] = function(a, b) {
        var x = dateHeight(a);
        var y = dateHeight(b);
        var z = ((x < y) ? 1 : ((x > y) ? -1 : 0));
        return z;
    };




    var aoColumns = [];

    var $tableTh = $(".data-table th , .dataTable th");
    if($tableTh.length) {
        $tableTh.each(function(index,elem) {
            if($(elem).hasClass('sortable-false')) {
                aoColumns.push({"bSortable": false });
            } else if($(elem).attr('data-date') !== undefined) {
                aoColumns.push({"sType": "data-date" });
            }else{
                aoColumns.push(null);
            }
        });


    };



    if(aoColumns.length > 0) {

        var indexProperty=0;
        var valueProperty='asc';
        $('.data-table').find('th').each(function(index){


            if($(this).attr('data-order')!== undefined){
                indexProperty=index;
                valueProperty = $(this).attr('data-order') !== undefined? $(this).attr('data-order') : valueProperty;
            }});



        $('.data-table').dataTable({
            "aoColumns": aoColumns,
            "order":[[indexProperty,valueProperty]],
            "oLanguage": {
                "sSearch": "Keyword Search"
            },
            "dom": '<"top"<"row"<"component-4"<"dataTableAction">><"component-4"<"dataTableLength"l<"clear">>> <"component-4"<"dataTableFilter"f<"clear">>>>>rt<"bottom"ip<"clear">>',
            "fnDrawCallback": function(){DataTableTruncate.initTrigger();}
        });
    }


</script>
<script>
    var sortable = $('.sortable');

    sortable.on('click', function(){

        var sort = $(this);
        var asc = sort.hasClass('asc');
        var desc = sort.hasClass('desc');
        sortable.removeClass('asc').removeClass('desc');
        if (desc || (!asc && !desc)) {
            sort.addClass('asc');
        } else {
            sort.addClass('desc');
        }

    });
</script>

</body>
</html>
