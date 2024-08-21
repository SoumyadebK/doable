<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;

$title = "STAFF PERFORMANCE REPORT";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 2 ){
    header("location:../login.php");
    exit;
}

$type = $_GET['type'];

if (!empty($_GET['week_number'])){
    $week_number = $_GET['week_number'];
    $YEAR = date('Y');
    $dto = new DateTime();
    $dto->setISODate($YEAR, $week_number+1);
    $from_date = $dto->modify('-1 day')->format('Y-m-d');
    $dto->modify('+6 days');
    $to_date = $dto->format('Y-m-d');

    $date_between = "AND DOA_ENROLLMENT_MASTER.CREATED_ON BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."'";
    $appointment_date = "AND DOA_APPOINTMENT_MASTER.DATE BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."'";
} else {
    $from_date = '';
    $to_date = '';
    $week_number = '';
    $date_between = '';
    $appointment_date = '';
}
$res = $db->Execute("SELECT BUSINESS_NAME FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
$business_name = $res->RecordCount() > 0 ? $res->fields['BUSINESS_NAME'] : '';

if ($type === 'export') {
    $access_token = getAccessToken();
    $authorization = "Authorization: Bearer ".$access_token;
    $line_item = [];

    $row = $db->Execute("SELECT DISTINCT (DOA_USERS.PK_USER), DOA_USERS.FIRST_NAME, DOA_USERS.LAST_NAME, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.ACTIVE FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER LEFT JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER LEFT JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER WHERE DOA_USER_LOCATION.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_USER_ROLES.PK_ROLES = 5 AND DOA_USERS.ACTIVE = 1 AND DOA_USERS.IS_DELETED = 0 AND DOA_USERS.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER']);
    while (!$row->EOF) {
        if (empty($row->fields['LAST_NAME'])) {
            $last_name = '';
        } else {
            $last_name = $row->fields['LAST_NAME'] . ',';
            $private_data = $db_account->Execute("SELECT count(DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER) AS PRIVATE FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER LEFT JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER = DOA_USERS.PK_USER WHERE DOA_USERS.IS_DELETED = 0 AND DOA_APPOINTMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE IN ('NORMAL', 'AD-HOC') AND DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS = 2 " . $appointment_date . " AND DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER = " . $row->fields['PK_USER']);
        }
        $private = $private_data->RecordCount() > 0 ? $private_data->fields['PRIVATE'] : 0;
        $group_data = $db_account->Execute("SELECT count(DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER) AS CLASS FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER LEFT JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER = DOA_USERS.PK_USER WHERE DOA_USERS.IS_DELETED = 0 AND DOA_APPOINTMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE = 'GROUP' AND DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS = 2 " . $appointment_date . " AND DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER = " . $row->fields['PK_USER']);
        $group = $group_data->RecordCount() > 0 ? $group_data->fields['CLASS'] : 0;

        $enrollment_data = $db_account->Execute("SELECT DOA_ENROLLMENT_SERVICE.FINAL_AMOUNT, DOA_ENROLLMENT_SERVICE.TOTAL_AMOUNT_PAID, DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_PERCENTAGE, DOA_ENROLLMENT_SERVICE.STATUS FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID = " . $row->fields['PK_USER'] . " $date_between ORDER BY DOA_ENROLLMENT_MASTER.PK_USER_MASTER ASC");
        $INTERVIEW_TOTAL = 0;
        $RENEWAL_TOTAL = 0;
        $j = 1;
        while (!$enrollment_data->EOF) {
            if ($j <= 3) {
                if ($enrollment_data->fields['STATUS'] == 'A') {
                    $INTERVIEW_TOTAL += (($enrollment_data->fields['FINAL_AMOUNT'] * $enrollment_data->fields['SERVICE_PROVIDER_PERCENTAGE']) / 100);
                } else {
                    $INTERVIEW_TOTAL += (($enrollment_data->fields['TOTAL_AMOUNT_PAID'] * $enrollment_data->fields['SERVICE_PROVIDER_PERCENTAGE']) / 100);
                }
            } else {
                if ($enrollment_data->fields['STATUS'] == 'A') {
                    $RENEWAL_TOTAL += (($enrollment_data->fields['FINAL_AMOUNT'] * $enrollment_data->fields['SERVICE_PROVIDER_PERCENTAGE']) / 100);
                } else {
                    $RENEWAL_TOTAL += (($enrollment_data->fields['TOTAL_AMOUNT_PAID'] * $enrollment_data->fields['SERVICE_PROVIDER_PERCENTAGE']) / 100);
                }
            }
            $j++;
            $enrollment_data->MoveNext();
        }

        $staff_members = [];
        while (!$row->EOF) {
            $staff_members[] = getStaffCode($authorization, $row->fields['FIRST_NAME'], $row->fields['LAST_NAME']);
            $row->MoveNext();
        }

        $line_item[] = array(
            "staff_type" => "INSTRUCTOR",
            "number_guests" => $private,
            "private_lessons" => $group,
            "number_in_class" => '',
            "dor_sanct_competition" => '',
            "showcase_medal_ball" => '',
            "party_time_non_unit" => '',
            "interview_department" => $INTERVIEW_TOTAL,
            "renewal_department" => $RENEWAL_TOTAL,
            "staff_members" => $staff_members,
        );
        $row->MoveNext();
    }

    $data = [
        'type' => 'staff_performance',
        'prepared_by' => $row->fields['FIRST_NAME'].' '.$row->fields['LAST_NAME'],
        'week_number' => $week_number,
        'week_year' => $YEAR,
        'line_items' => $line_item,
    ];

    $url = constant('ami_api_url').'/api/v1/reports';
    $post_data = callArturMurrayApi($url, $data, $authorization);

    //pre_r(json_decode($post_data));
}

$location_name='';
$results = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME FROM DOA_LOCATION WHERE PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND ACTIVE = 1 AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
$resultsArray = [];
while (!$results->EOF) {
    $resultsArray[] = $results->fields['LOCATION_NAME'];
    $results->MoveNext();
}
$totalResults = count($resultsArray);
$concatenatedResults = "";
foreach ($resultsArray as $key => $result) {
    // Append the current result to the concatenated string
    $concatenatedResults .= $result;

    // If it's not the last result, append a comma
    if ($key < $totalResults - 1) {
        $concatenatedResults .= ", ";
    }
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
                                        <th style="width:50%; text-align: center; vertical-align:auto; font-weight: bold" colspan="5">Franchisee: <?=$business_name." (".$concatenatedResults.")"?></th>
                                        <th style="width:50%; text-align: center; font-weight: bold" colspan="4">Week # <?=$week_number?> (<?=date('m-d-Y', strtotime($from_date))?> - <?=date('m-d-Y', strtotime($to_date))?>)</th>
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
                                        if(empty($row->fields['LAST_NAME'])) {
                                            $last_name = '';
                                        } else {
                                            $last_name = $row->fields['LAST_NAME'].',';
                                        }
                                        $private_data = $db_account->Execute("SELECT count(DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER) AS PRIVATE FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER LEFT JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER = DOA_USERS.PK_USER WHERE DOA_USERS.IS_DELETED = 0 AND DOA_APPOINTMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE IN ('NORMAL', 'AD-HOC') AND DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS = 2 ".$appointment_date." AND DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER = ".$row->fields['PK_USER']);
                                        $private = $private_data->RecordCount() > 0 ? $private_data->fields['PRIVATE'] : 0;
                                        $group_data = $db_account->Execute("SELECT count(DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER) AS CLASS FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER LEFT JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER = DOA_USERS.PK_USER WHERE DOA_USERS.IS_DELETED = 0 AND DOA_APPOINTMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE = 'GROUP' AND DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS = 2 ".$appointment_date." AND DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER = ".$row->fields['PK_USER']);
                                        $group = $group_data->RecordCount() > 0 ? $group_data->fields['CLASS'] : 0;

                                        $enrollment_data = $db_account->Execute("SELECT DOA_ENROLLMENT_SERVICE.FINAL_AMOUNT, DOA_ENROLLMENT_SERVICE.TOTAL_AMOUNT_PAID, DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_PERCENTAGE, DOA_ENROLLMENT_SERVICE.STATUS FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER LEFT JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USER_MASTER.PK_USER = DOA_USERS.PK_USER WHERE DOA_USERS.IS_DELETED = 0 AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID = ".$row->fields['PK_USER']." $date_between ORDER BY DOA_ENROLLMENT_MASTER.PK_USER_MASTER ASC");
                                        $INTERVIEW_TOTAL=0;
                                        $RENEWAL_TOTAL=0;
                                        $j=1;
                                        while (!$enrollment_data->EOF) {
                                            if($j <= 3) {
                                                if($enrollment_data->fields['STATUS']=='A'){
                                                    $INTERVIEW_TOTAL += (($enrollment_data->fields['FINAL_AMOUNT'] * $enrollment_data->fields['SERVICE_PROVIDER_PERCENTAGE']) / 100);
                                                } else {
                                                    $INTERVIEW_TOTAL += (($enrollment_data->fields['TOTAL_AMOUNT_PAID'] * $enrollment_data->fields['SERVICE_PROVIDER_PERCENTAGE']) / 100);
                                                }
                                            } else {
                                                if($enrollment_data->fields['STATUS']=='A'){
                                                    $RENEWAL_TOTAL += (($enrollment_data->fields['FINAL_AMOUNT'] * $enrollment_data->fields['SERVICE_PROVIDER_PERCENTAGE']) / 100);
                                                } else {
                                                    $RENEWAL_TOTAL += (($enrollment_data->fields['TOTAL_AMOUNT_PAID'] * $enrollment_data->fields['SERVICE_PROVIDER_PERCENTAGE']) / 100);
                                                }
                                            }
                                            $j++;
                                            $enrollment_data->MoveNext();
                                        }
                                        ?>
                                        <tr>
                                            <td><?=$last_name.' '.$row->fields['FIRST_NAME']?></td>
                                            <td></td>
                                            <td style="text-align: center"><?=$private?></td>
                                            <td style="text-align: center"><?=$group?></td>
                                            <td style="text-align: right"><?=''?></td>
                                            <td style="text-align: right"><?=''?></td>
                                            <td style="text-align: right"><?=''?></td>
                                            <td style="text-align: right"><?=number_format($INTERVIEW_TOTAL , 2)?></td>
                                            <td style="text-align: right"><?=number_format($RENEWAL_TOTAL , 2)?></td>
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
                                    $i=1;
                                    $row = $db->Execute("SELECT DISTINCT (DOA_USERS.PK_USER), DOA_USERS.FIRST_NAME, DOA_USERS.LAST_NAME, DOA_USERS.TYPE, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.ACTIVE, DOA_USER_ROLES.PK_ROLES FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER LEFT JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER LEFT JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER LEFT JOIN DOA_ROLES ON DOA_ROLES.PK_ROLES=DOA_USER_ROLES.PK_ROLES WHERE DOA_USER_LOCATION.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_ROLES.IS_MANAGEMENT=1 AND DOA_USERS.ACTIVE = 1 AND DOA_USERS.IS_DELETED = 0 AND DOA_USERS.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER']." GROUP BY DOA_USERS.PK_USER");
                                    while (!$row->EOF) {
                                        if(empty($row->fields['LAST_NAME'])) {
                                            $last_name = '';
                                        } else {
                                            $last_name = $row->fields['LAST_NAME'].',';
                                        }
                                        $role = $row->fields['PK_ROLES'];
                                        $private_data = $db_account->Execute("SELECT count(DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER) AS PRIVATE FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER LEFT JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER = DOA_USERS.PK_USER WHERE DOA_USERS.IS_DELETED = 0 AND DOA_APPOINTMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS = 2 AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE IN ('NORMAL', 'AD-HOC') AND DOA_APPOINTMENT_MASTER.DATE BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."' AND DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER = ".$row->fields['PK_USER']);
                                        $private = $private_data->RecordCount() > 0 ? $private_data->fields['PRIVATE'] : 0;
                                        $group_data = $db_account->Execute("SELECT count(DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER) AS CLASS FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER LEFT JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER = DOA_USERS.PK_USER WHERE DOA_USERS.IS_DELETED = 0 AND DOA_APPOINTMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE = 'GROUP' AND DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS = 2 AND DOA_APPOINTMENT_MASTER.DATE BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."' AND DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER = ".$row->fields['PK_USER']);
                                        $group = $group_data->RecordCount() > 0 ? $group_data->fields['CLASS'] : 0;

                                        $enrollment_data = $db_account->Execute("SELECT DOA_ENROLLMENT_SERVICE.FINAL_AMOUNT, DOA_ENROLLMENT_SERVICE.TOTAL_AMOUNT_PAID, DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_PERCENTAGE, DOA_ENROLLMENT_SERVICE.STATUS FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER LEFT JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USER_MASTER.PK_USER = DOA_USERS.PK_USER WHERE DOA_USERS.IS_DELETED = 0 AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID = ".$row->fields['PK_USER']." $date_between ORDER BY DOA_ENROLLMENT_MASTER.PK_USER_MASTER ASC");
                                        $INTERVIEW_TOTAL=0;
                                        $RENEWAL_TOTAL=0;
                                        $j=1;
                                        while (!$enrollment_data->EOF) {
                                            if($j <= 3) {
                                                if($enrollment_data->fields['STATUS']=='A'){
                                                    $INTERVIEW_TOTAL += $enrollment_data->fields['FINAL_AMOUNT'] * $enrollment_data->fields['ENROLLMENT_BY_PERCENTAGE'] / 100;
                                                } else {
                                                    $INTERVIEW_TOTAL += $enrollment_data->fields['TOTAL_AMOUNT_PAID'] * $enrollment_data->fields['ENROLLMENT_BY_PERCENTAGE'] / 100;
                                                }
                                            } else {
                                                if($enrollment_data->fields['STATUS']=='A'){
                                                    $RENEWAL_TOTAL += $enrollment_data->fields['FINAL_AMOUNT'] * $enrollment_data->fields['ENROLLMENT_BY_PERCENTAGE'] / 100;
                                                } else {
                                                    $RENEWAL_TOTAL += $enrollment_data->fields['TOTAL_AMOUNT_PAID'] * $enrollment_data->fields['ENROLLMENT_BY_PERCENTAGE'] / 100;
                                                }
                                            }
                                            $j++;
                                            $enrollment_data->MoveNext();
                                        }
                                        ?>
                                        <tr>
                                            <td><?=$last_name.' '.$row->fields['FIRST_NAME']?></td>
                                            <td style="text-align: center"></td>
                                            <td style="text-align: center"><?=$private?></td>
                                            <td style="text-align: center"><?=$group?></td>
                                            <td style="text-align: right"></td>
                                            <td style="text-align: right"></td>
                                            <td style="text-align: right"></td>
                                            <td style="text-align: right"><?=number_format($INTERVIEW_TOTAL, 2)?></td>
                                            <td style="text-align: right"><?=number_format($RENEWAL_TOTAL, 2)?></td>
                                        </tr>
                                        <?php $row->MoveNext();
                                        $i++; } ?>
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
