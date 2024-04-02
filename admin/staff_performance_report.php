<?php
require_once('../global/config.php');
$title = "STAFF PERFORMANCE REPORT";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 2 ){
    header("location:../login.php");
    exit;
}

if (!empty($_GET['date'])){
    $from_date = $_GET['date'];
    $to_date = date('m/d/y', strtotime("+7 day", strtotime($from_date)));
    $duedt = explode("/", $from_date);
    $date  = mktime(0, 0, 0, $duedt[0], $duedt[1], $duedt[2]);
    $week_number  = (int)date('W', $date);
    $date_between = "AND DOA_ENROLLMENT_MASTER.CREATED_ON BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."'";
} else {
    $from_date = '';
    $to_date = '';
    $week_number = '';
    $date_between = '';
}
$res = $db->Execute("SELECT BUSINESS_NAME FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
$business_name = $res->RecordCount() > 0 ? $res->fields['BUSINESS_NAME'] : '';
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
                <div class="col-md-7 align-self-center text-end">
                    <div class="d-flex justify-content-end align-items-center">
                        <ol class="breadcrumb justify-content-end">
                            <li class="breadcrumb-item active"><a href="reports.php">Reports</a></li>
                            <li class="breadcrumb-item active"><a href="customer_summary_report.php"><?=$title?></a></li>
                        </ol>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div>
                                <img src="../assets/images/background/doable_logo.png" style="margin-bottom:-35px; height: 60px; width: auto;">
                                <h3 class="card-title" style="padding-bottom:15px; text-align: center; font-weight: bold"><?=$title?></h3>
                            </div>

                            <div class="table-responsive">
                                <table id="myTable" class="table table-bordered" data-page-length='50'>
                                    <thead>
                                    <tr>
                                        <th style="width:50%; text-align: center; vertical-align:auto; font-weight: bold" colspan="5">Franchisee: <?=$business_name?></th>
                                        <th style="width:50%; text-align: center; font-weight: bold" colspan="4">Week # <?=$week_number?> (<?=$from_date?> - <?=$to_date?>)</th>
                                    </tr>
                                    <tr>
                                        <th style="width:10%; text-align: center" rowspan="2">Staff name</th>
                                        <th style="width:10%; text-align: center" rowspan="2">Number of<br>Guests</th>
                                        <th style="width:10%; text-align: center" colspan="2">Lessons taught</th>
                                        <th style="width:12%; text-align: center" colspan="3">$ value of misc. sales </th>
                                        <th style="width:10%; text-align: center" colspan="2">$ val. of lessons sales</th>
                                    </tr>
                                    <tr>
                                        <th style="width:10%; text-align: center">Private</th>
                                        <th style="width:10%; text-align: center">Class</th>
                                        <th style="width:10%; text-align: center">DOR/sanct.<br>Competition</th>
                                        <th style="width:10%; text-align: center">Showcase<br>Medal ball</th>
                                        <th style="width:10%; text-align: center">General Misc.<br>NonUnit</th>
                                        <th style="width:10%; text-align: center">Interview <br>Dept.</th>
                                        <th style="width:10%; text-align: center">Renewal <br>Dept.</th>
                                    </tr>
                                    <tr>
                                        <th style="width:10%; text-align: center; font-weight: bold; font-style: italic" colspan="9">INSTRUCTORS</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    $i=1;
                                    $row = $db->Execute("SELECT DISTINCT (DOA_USERS.PK_USER), DOA_USERS.FIRST_NAME, DOA_USERS.LAST_NAME, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.ACTIVE FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER LEFT JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER LEFT JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER WHERE DOA_USER_LOCATION.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_USER_ROLES.PK_ROLES = 5 AND DOA_USERS.ACTIVE = 1 AND DOA_USERS.IS_DELETED = 0 AND DOA_USERS.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER']);
                                    while (!$row->EOF) {
                                        $private_data = $db_account->Execute("SELECT count(DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER) AS PRIVATE FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE = 'NORMAL' AND DOA_APPOINTMENT_MASTER.DATE BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."' AND DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER = ".$row->fields['PK_USER']);
                                        $private = $private_data->RecordCount() > 0 ? $private_data->fields['PRIVATE'] : 0;
                                        $group_data = $db_account->Execute("SELECT count(DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER) AS CLASS FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE = 'GROUP' AND DOA_APPOINTMENT_MASTER.DATE BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."' AND DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER = ".$row->fields['PK_USER']);
                                        $group = $group_data->RecordCount() > 0 ? $group_data->fields['CLASS'] : 0;
                                        $enrollment_data = $db_account->Execute("SELECT SUM(PERCENTAGE_AMOUNT) AS TOTAL_PERCENTAGE_AMOUNT FROM ( SELECT PERCENTAGE_AMOUNT FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID = ".$row->fields['PK_USER']." $date_between ORDER BY DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER ASC LIMIT 3) AS TOTAL_PERCENTAGE_AMOUNT");
                                        $enrollment_data_1 = $db_account->Execute("SELECT SUM(PERCENTAGE_AMOUNT) AS TOTAL_PERCENTAGE_AMOUNT FROM ( SELECT PERCENTAGE_AMOUNT FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID = ".$row->fields['PK_USER']." $date_between ORDER BY DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER ASC LIMIT 3,18446744073709551615) AS TOTAL_PERCENTAGE_AMOUNT");
                                        $interview = $enrollment_data->RecordCount() > 0 ? $enrollment_data->fields['TOTAL_PERCENTAGE_AMOUNT'] : 0;
                                        $renewal = $enrollment_data_1->RecordCount() > 0 ? $enrollment_data_1->fields['TOTAL_PERCENTAGE_AMOUNT'] : 0;
                                        ?>
                                        <tr>
                                            <td><?=$row->fields['LAST_NAME'].', '.$row->fields['FIRST_NAME']?></td>
                                            <td></td>
                                            <td style="text-align: center"><?=$private?></td>
                                            <td style="text-align: center"><?=$group?></td>
                                            <td style="text-align: right"><?=''?></td>
                                            <td style="text-align: right"><?=''?></td>
                                            <td style="text-align: right"><?=''?></td>
                                            <td style="text-align: right"><?=number_format($interview , 2)?></td>
                                            <td style="text-align: right"><?=number_format($renewal , 2)?></td>
                                        </tr>
                                        <?php $row->MoveNext();
                                        $i++; } ?>
                                    </tbody>
                                    <thead>
                                    <tr>
                                        <th style="width:10%; text-align: center; font-weight: bold; font-style: italic" colspan="9">EXECUTIVES</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    $j=1;
                                    $row = $db->Execute("SELECT DISTINCT (DOA_USERS.PK_USER), DOA_USERS.FIRST_NAME, DOA_USERS.LAST_NAME, DOA_USERS.TYPE, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.ACTIVE, DOA_USER_ROLES.PK_ROLES FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER LEFT JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER LEFT JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER LEFT JOIN DOA_ROLES ON DOA_ROLES.PK_ROLES=DOA_USER_ROLES.PK_ROLES WHERE DOA_USER_LOCATION.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_ROLES.IS_MANAGEMENT=1 AND DOA_USERS.ACTIVE = 1 AND DOA_USERS.IS_DELETED = 0 AND DOA_USERS.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER']);
                                    while (!$row->EOF) {
                                        $role = $row->fields['PK_ROLES'];
                                        $private_data = $db_account->Execute("SELECT count(DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER) AS PRIVATE FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE = 'NORMAL' AND DOA_APPOINTMENT_MASTER.DATE BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."' AND DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER = ".$row->fields['PK_USER']);
                                        $private = $private_data->RecordCount() > 0 ? $private_data->fields['PRIVATE'] : 0;
                                        $group_data = $db_account->Execute("SELECT count(DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER) AS CLASS FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE = 'GROUP' AND DOA_APPOINTMENT_MASTER.DATE BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."' AND DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER = ".$row->fields['PK_USER']);
                                        $group = $group_data->RecordCount() > 0 ? $group_data->fields['CLASS'] : 0;
                                        $enrollment_data = $db_account->Execute("SELECT SUM(PERCENTAGE_AMOUNT) AS TOTAL_PERCENTAGE FROM (SELECT (DOA_ENROLLMENT_BILLING.TOTAL_AMOUNT * DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_PERCENTAGE) / 100 AS PERCENTAGE_AMOUNT FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_BILLING ON DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER= DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID = ".$row->fields['PK_USER']." $date_between ORDER BY PK_ENROLLMENT_BILLING ASC LIMIT 3) AS TOTAL_PERCENTAGE");
                                        $result = $enrollment_data->fields['TOTAL_PERCENTAGE'];
                                        $enrollment_data_1 = $db_account->Execute("SELECT SUM(PERCENTAGE_AMOUNT) AS TOTAL_PERCENTAGE FROM (SELECT (DOA_ENROLLMENT_BILLING.TOTAL_AMOUNT * DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_PERCENTAGE) / 100 AS PERCENTAGE_AMOUNT FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_BILLING ON DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER= DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID = ".$row->fields['PK_USER']." $date_between ORDER BY PK_ENROLLMENT_BILLING ASC LIMIT 3,18446744073709551615) AS TOTAL_PERCENTAGE");
                                        $result_1 = $enrollment_data_1->fields['TOTAL_PERCENTAGE'];
                                        /*if ($role==9){
                                            $interview = $enrollment_data->fields['TOTAL_PERCENTAGE'] > 0 ? $result : 0;
                                        } else{
                                            $interview = 0;
                                        }
                                        if ($role==10){
                                            $renewal = $enrollment_data_1->fields['TOTAL_PERCENTAGE'] > 0 ? $result_1 : 0;
                                        } else{
                                            $renewal = 0;
                                        }*/
                                        ?>
                                        <tr>
                                            <td><?=$row->fields['LAST_NAME'].', '.$row->fields['FIRST_NAME']?></td>
                                            <td style="text-align: center">------</td>
                                            <td style="text-align: center">------</td>
                                            <td style="text-align: center">------</td>
                                            <td style="text-align: right"></td>
                                            <td style="text-align: right"></td>
                                            <td style="text-align: right"></td>
                                            <td style="text-align: right"><?=number_format($result, 2)?></td>
                                            <td style="text-align: right"><?=number_format($result_1, 2)?></td>
                                        </tr>
                                        <?php $row->MoveNext();
                                        $j++; } ?>
                                    </tbody>
                                </table>
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
