<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];

$title = "STAFF PERFORMANCE REPORT";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

$type = $_GET['type'];

$week_number = $_GET['week_number'];
$YEAR = date('Y');

$from_date = date('Y-m-d', strtotime($_GET['start_date']));
$to_date = date('Y-m-d', strtotime($from_date . ' +6 day'));

$enrollment_date = "AND DOA_ENROLLMENT_MASTER.ENROLLMENT_DATE BETWEEN '" . date('Y-m-d', strtotime($from_date)) . "' AND '" . date('Y-m-d', strtotime($to_date)) . "'";
$appointment_date = "AND DOA_APPOINTMENT_MASTER.DATE BETWEEN '" . date('Y-m-d', strtotime($from_date)) . "' AND '" . date('Y-m-d', strtotime($to_date)) . "'";

$account_data = $db->Execute("SELECT * FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
$user_data = $db->Execute("SELECT * FROM DOA_USERS WHERE PK_USER = '$_SESSION[PK_USER]'");
$business_name = $account_data->RecordCount() > 0 ? $account_data->fields['BUSINESS_NAME'] : '';

if ($type === 'export') {
    $location_array = explode(",", $DEFAULT_LOCATION_ID);
    if (count($location_array) > 1) {
        $error_message = "Please select any one location from top to export data.";
    } else {
        $access_token = getAccessToken();
        $authorization = "Authorization: Bearer " . $access_token;

        $line_item = [];

        $staff_data = $db->Execute("SELECT DISTINCT (DOA_USERS.PK_USER), DOA_USERS.FIRST_NAME, DOA_USERS.LAST_NAME, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.ACTIVE FROM DOA_USERS LEFT JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER WHERE DOA_USER_LOCATION.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_USERS.APPEAR_IN_CALENDAR = 1 AND DOA_USERS.ACTIVE = 1 AND DOA_USERS.IS_DELETED = 0 AND DOA_USERS.PK_ACCOUNT_MASTER = " . $_SESSION['PK_ACCOUNT_MASTER'] . " ORDER BY DOA_USERS.DISPLAY_ORDER ASC");
        while (!$staff_data->EOF) {
            $staff_member = getStaffCode($authorization, $staff_data->fields['FIRST_NAME'], $staff_data->fields['LAST_NAME']);
            $staff_type = 'INSTRUCTOR';
            $number_guests = 0;

            $private_data = $db_account->Execute("SELECT SUM(DOA_SCHEDULING_CODE.UNIT) AS PRIVATE_COUNT FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_SCHEDULING_CODE ON DOA_APPOINTMENT_MASTER.PK_SCHEDULING_CODE = DOA_SCHEDULING_CODE.PK_SCHEDULING_CODE LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE IN ('NORMAL', 'AD-HOC') AND DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER != 2 AND DOA_APPOINTMENT_MASTER.IS_CHARGED = 1 " . $appointment_date . " AND DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER = " . $staff_data->fields['PK_USER']);
            $private_lessons = $private_data->fields['PRIVATE_COUNT'] ?? 0;

            $group_data = $db_account->Execute("SELECT SUM(DOA_SCHEDULING_CODE.UNIT) AS GROUP_COUNT FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_SCHEDULING_CODE ON DOA_APPOINTMENT_MASTER.PK_SCHEDULING_CODE = DOA_SCHEDULING_CODE.PK_SCHEDULING_CODE LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE = 'GROUP' AND DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER != 2 AND DOA_APPOINTMENT_MASTER.IS_CHARGED = 1 " . $appointment_date . " AND DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER = " . $staff_data->fields['PK_USER']);
            $number_in_class = $group_data->fields['GROUP_COUNT'] ?? 0;

            $dor_misc_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE_PROVIDER.PERCENTAGE_AMOUNT) AS MISC_TOTAL FROM DOA_ENROLLMENT_SERVICE_PROVIDER LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE = 16 AND DOA_ENROLLMENT_MASTER.MISC_TYPE = 'DOR' AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID = " . $staff_data->fields['PK_USER'] . " $enrollment_date");
            $dor_sanct_competition = $dor_misc_data->fields['MISC_TOTAL'] ?? 0;

            $showcase_misc_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE_PROVIDER.PERCENTAGE_AMOUNT) AS MISC_TOTAL FROM DOA_ENROLLMENT_SERVICE_PROVIDER LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE = 16 AND DOA_ENROLLMENT_MASTER.MISC_TYPE = 'SHOWCASE' AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID = " . $staff_data->fields['PK_USER'] . " $enrollment_date");
            $showcase_medal_ball = $showcase_misc_data->fields['MISC_TOTAL'] ?? 0;

            $general_misc_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE_PROVIDER.PERCENTAGE_AMOUNT) AS MISC_TOTAL FROM DOA_ENROLLMENT_SERVICE_PROVIDER LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE = 16 AND DOA_ENROLLMENT_MASTER.MISC_TYPE = 'GENERAL' AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID = " . $staff_data->fields['PK_USER'] . " $enrollment_date");
            $party_time_non_unit = $general_misc_data->fields['MISC_TOTAL'] ?? 0;

            $interview_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE_PROVIDER.PERCENTAGE_AMOUNT) AS INTERVIEW_TOTAL FROM DOA_ENROLLMENT_SERVICE_PROVIDER LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE NOT IN (13,16) AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID = " . $staff_data->fields['PK_USER'] . " $enrollment_date");
            $interview_department = $interview_data->fields['INTERVIEW_TOTAL'] ?? 0;

            $renewal_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE_PROVIDER.PERCENTAGE_AMOUNT) AS RENEWAL_TOTAL FROM DOA_ENROLLMENT_SERVICE_PROVIDER LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE = 13 AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID = " . $staff_data->fields['PK_USER'] . " $enrollment_date");
            $renewal_department = $renewal_data->fields['RENEWAL_TOTAL'] ?? 0;

            $line_item[] = array(
                "staff_member" => ($staff_member == '') ? '628c15c18f29984a7d5f30e7' : $staff_member,
                "staff_type" => $staff_type,
                "number_guests" => $number_guests,
                "private_lessons" => $private_lessons,
                "number_in_class" => $number_in_class,
                "dor_sanct_competition" => $dor_sanct_competition,
                "showcase_medal_ball" => $showcase_medal_ball,
                "party_time_non_unit" => $party_time_non_unit,
                "interview_department" => $interview_department,
                "renewal_department" => $renewal_department,
            );

            $staff_data->MoveNext();
        }

        $data = [
            'type' => 'staff_performance',
            'prepared_by' => $user_data->fields['FIRST_NAME'] . ' ' . $user_data->fields['LAST_NAME'],
            'week_number' => $week_number,
            'week_year' => $YEAR,
            'line_items' => $line_item,
        ];

        $report_details = $db_account->Execute("SELECT * FROM `DOA_REPORT_EXPORT_DETAILS` WHERE PK_LOCATION = $DEFAULT_LOCATION_ID AND `REPORT_TYPE` = 'staff_performance_report' AND `YEAR` = '$YEAR' AND `WEEK_NUMBER` = " . $week_number);
        if ($report_details->RecordCount() > 0) {
            if ($report_details->fields['ID'] != '' && $report_details->fields['ID'] != null) {
                $url = constant('ami_api_url') . '/api/v1/reports/' . $report_details->fields['ID'];
                $post_data = callArturMurrayApi($url, $data, $authorization, 'PUT');
            } else {
                $get_url = constant('ami_api_url') . '/api/v1/reports';
                $get_data = [
                    'type' => 'staff_performance',
                    'week_number' => $week_number,
                    'week_year' => $YEAR
                ];
                $post_get_data = callArturMurrayApiGet($get_url, $get_data, $authorization);
                $return_data_get = json_decode($post_get_data, true);

                if (!empty($return_data_get) && isset($return_data_get[0]['id'])) {
                    $report_id = $return_data_get[0]['id'];

                    $url = constant('ami_api_url') . '/api/v1/reports/' . $report_id;
                    $post_data = callArturMurrayApi($url, $data, $authorization, 'PUT');
                } else {
                    $url = constant('ami_api_url') . '/api/v1/reports';
                    $post_data = callArturMurrayApi($url, $data, $authorization);

                    $response = json_decode($post_data);
                    $report_id = isset($response->id) ? $response->id : '';
                }

                $REPORT_DATA['ID'] = $report_id;
                $REPORT_DATA['SUBMISSION_DATE'] = date('Y-m-d H:i:s');
                db_perform_account('DOA_REPORT_EXPORT_DETAILS', $REPORT_DATA, "update", " PK_REPORT_EXPORT_DETAILS = " . $report_details->fields['PK_REPORT_EXPORT_DETAILS']);
            }

            $response = json_decode($post_data);
        } else {
            $url = constant('ami_api_url') . '/api/v1/reports';
            $post_data = callArturMurrayApi($url, $data, $authorization);

            $response = json_decode($post_data);

            if (isset($response->error) || isset($response->errors)) {
                $get_url = constant('ami_api_url') . '/api/v1/reports';
                $get_data = [
                    'type' => 'staff_performance',
                    'week_number' => $week_number,
                    'week_year' => $YEAR
                ];
                $post_get_data = callArturMurrayApiGet($get_url, $get_data, $authorization);
                $return_data_get = json_decode($post_get_data, true);

                if (!empty($return_data_get) && isset($return_data_get[0]['id'])) {
                    $report_id = $return_data_get[0]['id'];

                    $url = constant('ami_api_url') . '/api/v1/reports/' . $report_id;
                    $post_data = callArturMurrayApi($url, $data, $authorization, 'PUT');

                    $REPORT_DATA['PK_LOCATION'] = $DEFAULT_LOCATION_ID;
                    $REPORT_DATA['REPORT_TYPE'] = 'staff_performance_report';
                    $REPORT_DATA['YEAR'] = $YEAR;
                    $REPORT_DATA['WEEK_NUMBER'] = $week_number;
                    $REPORT_DATA['ID'] = $report_id;
                    $REPORT_DATA['SUBMISSION_DATE'] = date('Y-m-d H:i:s');
                    db_perform_account('DOA_REPORT_EXPORT_DETAILS', $REPORT_DATA, "insert");
                }
            } else {
                $REPORT_DATA['REPORT_TYPE'] = 'staff_performance_report';
                $REPORT_DATA['PK_LOCATION'] = $DEFAULT_LOCATION_ID;
                $REPORT_DATA['ID'] = isset($response->id) ? $response->id : '';
                $REPORT_DATA['WEEK_NUMBER'] = $week_number;
                $REPORT_DATA['YEAR'] = $YEAR;
                $REPORT_DATA['SUBMISSION_DATE'] = date('Y-m-d H:i:s');
                db_perform_account('DOA_REPORT_EXPORT_DETAILS', $REPORT_DATA);
            }
        }
    }
}

$location_name = '';
$results = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME FROM DOA_LOCATION WHERE PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND ACTIVE = 1 AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
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

$executive_data = $db_account->Execute("SELECT DISTINCT(ENROLLMENT_BY_ID) AS ENROLLMENT_BY_ID FROM DOA_ENROLLMENT_MASTER WHERE PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND PK_ENROLLMENT_MASTER > 0 $enrollment_date");
$executive_id = [];
while (!$executive_data->EOF) {
    $executive_id[] = $executive_data->fields['ENROLLMENT_BY_ID'];
    $executive_data->MoveNext();
}

if (!empty($_GET['WEEK_NUMBER'])) {
    $type = isset($_GET['view']) ? 'view' : 'export';
    $generate_pdf = isset($_GET['generate_pdf']) ? 1 : 0;
    $generate_excel = isset($_GET['generate_excel']) ? 1 : 0;
    $report_name = $_GET['NAME'];

    // Extract week number from "Week Number X" format
    $week_parts = explode(' ', $_GET['WEEK_NUMBER']);
    $WEEK_NUMBER = end($week_parts);

    // Calculate start date from week number
    $year = date('Y');
    $date = new DateTime();
    $date->setISODate($year, $WEEK_NUMBER);
    $date->modify('-1 day'); // Get Sunday instead of Monday

    $START_DATE = $date->format('Y-m-d');

    if ($generate_pdf === 1) {
        header('location:generate_report_pdf.php?week_number=' . $WEEK_NUMBER . '&start_date=' . $START_DATE . '&report_type=' . $report_name);
    } elseif ($generate_excel === 1) {
        header('location:excel_' . $report_name . '.php?week_number=' . $WEEK_NUMBER . '&start_date=' . $START_DATE . '&report_type=' . $report_name);
    } else {
        header('location:staff_performance_report.php?week_number=' . $WEEK_NUMBER . '&start_date=' . $START_DATE . '&type=' . $type);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<?php require_once('../includes/header.php'); ?>

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">
        <?php require_once('../includes/top_menu.php'); ?>
        <div class="page-wrapper">
            <?php require_once('../includes/top_menu_bar.php') ?>
            <div class="container-fluid body_content">
                <div class="row page-titles">
                    <div class="col-md-5 align-self-center">
                        <h4 class="text-themecolor"><?= $title ?></h4>
                    </div>
                    <div class="col-md-7 align-self-center text-end">
                        <div class="d-flex justify-content-end align-items-center">
                            <ol class="breadcrumb justify-content-end">
                                <li class="breadcrumb-item active"><a href="reports.php">Reports</a></li>
                                <li class="breadcrumb-item active"><a href="customer_summary_report.php"><?= $title ?></a></li>
                            </ol>
                        </div>
                    </div>
                </div>

                <?php
                if ($type != 'export') { ?>
                    <div class="row">
                        <div class="col-12 align-self-center">
                            <div class="card">
                                <div class="card-body" style="padding-bottom: 0px !important;">
                                    <form class="form-material form-horizontal" action="" method="get" id="reportForm">
                                        <input type="hidden" name="start_date" id="start_date">
                                        <input type="hidden" name="NAME" id="NAME" value="staff_performance_report">
                                        <div class="row justify-content-start">
                                            <div class="col-2">
                                                <div class="form-group">
                                                    <input type="text" id="WEEK_NUMBER1" name="WEEK_NUMBER" class="form-control week-picker" placeholder="Select Week" value="<?= !empty($_GET['WEEK_NUMBER']) ? htmlspecialchars($_GET['WEEK_NUMBER']) : (!empty($week_number) ? 'Week Number ' . $week_number : '') ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-3">
                                                <?php if (in_array('Reports Create', $PERMISSION_ARRAY)) { ?>
                                                    <input type="submit" name="view" value="View" class="btn btn-info" style="background-color: #39B54A !important;">
                                                    <input type="submit" name="generate_pdf" value="Generate PDF" class="btn btn-info" style="background-color: #39B54A !important;">
                                                    <input type="submit" name="generate_excel" value="Generate Excel" class="btn btn-info" style="background-color: #39B54A !important;">
                                                <?php } ?>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>

                <?php
                if ($type === 'export') {
                    $response = json_decode($post_data);
                    if (isset($response->error)) {
                        echo '<div class="alert alert-danger alert-dismissible" role="alert">' . $response->error_description . '</div>';
                    } elseif (isset($response->errors)) {
                        if (isset($response->errors->errors[0])) {
                            echo '<div class="alert alert-danger alert-dismissible" role="alert">' . $response->errors->errors[0] . '</div>';
                        } else {
                            echo '<div class="alert alert-danger alert-dismissible" role="alert">' . $response->message . '</div>';
                        }
                    } else {
                        echo "<h3 style='color: green;'>Data export to Arthur Murray API Successfully</h3>";
                    }
                } else { ?>
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <div>
                                        <img src="../assets/images/background/doable_logo.png" style="margin-bottom:-35px; height: 60px; width: auto;">
                                        <h3 class="card-title" style="padding-bottom:15px; text-align: center; font-weight: bold"><?= $title ?></h3>
                                    </div>

                                    <div class="table-responsive">
                                        <table id="myTable" class="table table-bordered" data-page-length='50'>
                                            <thead>
                                                <tr>
                                                    <th style="width:50%; text-align: center; vertical-align:auto; font-weight: bold" colspan="5"><?= ($account_data->fields['FRANCHISE'] == 1) ? 'Franchisee: ' : '' ?><?= $concatenatedResults ?></th>
                                                    <th style="width:50%; text-align: center; font-weight: bold" colspan="4">Week # <?= $week_number ?> (<?= date('m/d/Y', strtotime($from_date)) ?> - <?= date('m/d/Y', strtotime($to_date)) ?>)</th>
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
                                                    <?php if ($account_data->fields['FRANCHISE'] == 1) { ?>
                                                        <th style="width:10%; text-align: center">DOR/Sanct.<br>Competition</th>
                                                    <?php } else { ?>
                                                        <th style="width:10%; text-align: center">Sanct.<br>Competition</th>
                                                    <?php } ?>
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
                                                $i = 1;
                                                $row = $db->Execute("SELECT DISTINCT (DOA_USERS.PK_USER), DOA_USERS.FIRST_NAME, DOA_USERS.LAST_NAME, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.ACTIVE FROM DOA_USERS LEFT JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER WHERE DOA_USER_LOCATION.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_USERS.APPEAR_IN_CALENDAR = 1 AND DOA_USERS.ACTIVE = 1 AND DOA_USERS.IS_DELETED = 0 AND DOA_USERS.PK_ACCOUNT_MASTER = " . $_SESSION['PK_ACCOUNT_MASTER'] . " ORDER BY DOA_USERS.DISPLAY_ORDER ASC");
                                                while (!$row->EOF) {
                                                    $last_name = empty($row->fields['LAST_NAME']) ? '' : $row->fields['LAST_NAME'] . ',';

                                                    $private_data = $db_account->Execute("SELECT SUM(DOA_SCHEDULING_CODE.UNIT) AS PRIVATE_COUNT FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_SCHEDULING_CODE ON DOA_APPOINTMENT_MASTER.PK_SCHEDULING_CODE = DOA_SCHEDULING_CODE.PK_SCHEDULING_CODE LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE IN ('NORMAL', 'AD-HOC') AND DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER != 2 AND DOA_APPOINTMENT_MASTER.IS_CHARGED = 1 " . $appointment_date . " AND DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER = " . $row->fields['PK_USER']);
                                                    $group_data = $db_account->Execute("SELECT SUM(DOA_SCHEDULING_CODE.UNIT) AS GROUP_COUNT FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_SCHEDULING_CODE ON DOA_APPOINTMENT_MASTER.PK_SCHEDULING_CODE = DOA_SCHEDULING_CODE.PK_SCHEDULING_CODE LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE = 'GROUP' AND DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER != 2 " . $appointment_date . " AND DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER = " . $row->fields['PK_USER']);

                                                    $dor_misc_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE_PROVIDER.PERCENTAGE_AMOUNT) AS MISC_TOTAL FROM DOA_ENROLLMENT_SERVICE_PROVIDER LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE = 16 AND DOA_ENROLLMENT_MASTER.MISC_TYPE = 'DOR' AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID = " . $row->fields['PK_USER'] . " $enrollment_date");
                                                    $showcase_misc_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE_PROVIDER.PERCENTAGE_AMOUNT) AS MISC_TOTAL FROM DOA_ENROLLMENT_SERVICE_PROVIDER LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE = 16 AND DOA_ENROLLMENT_MASTER.MISC_TYPE = 'SHOWCASE' AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID = " . $row->fields['PK_USER'] . " $enrollment_date");
                                                    $general_misc_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE_PROVIDER.PERCENTAGE_AMOUNT) AS MISC_TOTAL FROM DOA_ENROLLMENT_SERVICE_PROVIDER LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE = 16 AND DOA_ENROLLMENT_MASTER.MISC_TYPE = 'GENERAL' AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID = " . $row->fields['PK_USER'] . " $enrollment_date");

                                                    $interview_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE_PROVIDER.PERCENTAGE_AMOUNT) AS INTERVIEW_TOTAL FROM DOA_ENROLLMENT_SERVICE_PROVIDER LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE NOT IN (13,16) AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID = " . $row->fields['PK_USER'] . " $enrollment_date");
                                                    $renewal_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE_PROVIDER.PERCENTAGE_AMOUNT) AS RENEWAL_TOTAL FROM DOA_ENROLLMENT_SERVICE_PROVIDER LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE = 13 AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID = " . $row->fields['PK_USER'] . " $enrollment_date");
                                                ?>
                                                    <tr>
                                                        <td><?= $last_name . ' ' . $row->fields['FIRST_NAME'] ?></td>
                                                        <td></td>
                                                        <td style="text-align: center"><?= $private_data->fields['PRIVATE_COUNT'] ?? 0 ?></td>
                                                        <td style="text-align: center"><?= $group_data->fields['GROUP_COUNT'] ?? 0 ?></td>
                                                        <td style="text-align: right">$<?= number_format($dor_misc_data->fields['MISC_TOTAL'], 2) ?></td>
                                                        <td style="text-align: right">$<?= number_format($showcase_misc_data->fields['MISC_TOTAL'], 2) ?></td>
                                                        <td style="text-align: right">$<?= number_format($general_misc_data->fields['MISC_TOTAL'], 2) ?></td>
                                                        <td style="text-align: right">$<?= number_format($interview_data->fields['INTERVIEW_TOTAL'], 2) ?></td>
                                                        <td style="text-align: right">$<?= number_format($renewal_data->fields['RENEWAL_TOTAL'], 2) ?></td>
                                                    </tr>
                                                <?php $row->MoveNext();
                                                    $i++;
                                                } ?>
                                            </tbody>

                                            <thead>
                                                <tr>
                                                    <th style="width:10%; text-align: center; font-weight: bold; font-style: italic" colspan="9">EXECUTIVES</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $i = 1;
                                                $row = $db->Execute("SELECT DISTINCT (PK_USER) AS PK_USER, FIRST_NAME, LAST_NAME FROM DOA_USERS WHERE PK_USER IN (" . implode(',', $executive_id) . ")");
                                                while (!$row->EOF) {
                                                    $last_name = empty($row->fields['LAST_NAME']) ? '' : $row->fields['LAST_NAME'] . ',';

                                                    $executive_dor_misc_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_BILLING.TOTAL_AMOUNT) AS MISC_TOTAL FROM DOA_ENROLLMENT_BILLING JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE = 16 AND DOA_ENROLLMENT_MASTER.MISC_TYPE = 'DOR' AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID = " . $row->fields['PK_USER'] . " $enrollment_date");
                                                    $executive_showcase_misc_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_BILLING.TOTAL_AMOUNT) AS MISC_TOTAL FROM DOA_ENROLLMENT_BILLING JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE = 16 AND DOA_ENROLLMENT_MASTER.MISC_TYPE = 'SHOWCASE' AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID = " . $row->fields['PK_USER'] . " $enrollment_date");
                                                    $executive_general_misc_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_BILLING.TOTAL_AMOUNT) AS MISC_TOTAL FROM DOA_ENROLLMENT_BILLING JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE = 16 AND DOA_ENROLLMENT_MASTER.MISC_TYPE = 'GENERAL' AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID = " . $row->fields['PK_USER'] . " $enrollment_date");

                                                    $executive_interview_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_BILLING.TOTAL_AMOUNT) AS INTERVIEW_TOTAL FROM DOA_ENROLLMENT_BILLING JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE NOT IN (13,16) AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID = " . $row->fields['PK_USER'] . " $enrollment_date");
                                                    $executive_renewal_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_BILLING.TOTAL_AMOUNT) AS RENEWAL_TOTAL FROM DOA_ENROLLMENT_BILLING JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE = 13 AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID = " . $row->fields['PK_USER'] . " $enrollment_date");
                                                ?>
                                                    <tr>
                                                        <td><?= $last_name . ' ' . $row->fields['FIRST_NAME'] ?></td>
                                                        <td style="text-align: center">-----</td>
                                                        <td style="text-align: center">-----</td>
                                                        <td style="text-align: center">-----</td>
                                                        <td style="text-align: right">$<?= number_format($executive_dor_misc_data->fields['MISC_TOTAL'], 2) ?></td>
                                                        <td style="text-align: right">$<?= number_format($executive_showcase_misc_data->fields['MISC_TOTAL'], 2) ?></td>
                                                        <td style="text-align: right">$<?= number_format($executive_general_misc_data->fields['MISC_TOTAL'], 2) ?></td>
                                                        <td style="text-align: right">$<?= number_format($executive_interview_data->fields['INTERVIEW_TOTAL'], 2) ?></td>
                                                        <td style="text-align: right">$<?= number_format($executive_renewal_data->fields['RENEWAL_TOTAL'], 2) ?></td>
                                                    </tr>
                                                <?php $row->MoveNext();
                                                    $i++;
                                                } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
    <?php require_once('../includes/footer.php'); ?>
</body>

</html>

<script>
    $(document).ready(function() {
        // Function to calculate week number (simplified)
        function getWeekNumber(date) {
            var d = new Date(date);
            // Copy date so don't modify original
            d = new Date(Date.UTC(d.getFullYear(), d.getMonth(), d.getDate()));
            // Set to nearest Thursday: current date + 4 - current day of week
            // Make Sunday's day number 7
            d.setUTCDate(d.getUTCDate() + 4 - (d.getUTCDay() || 7));
            // Get first day of year
            var yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
            // Calculate full weeks to nearest Thursday
            var weekNo = Math.ceil((((d - yearStart) / 86400000) + 1) / 7);
            return weekNo;
        }

        // Initialize week picker
        $(".week-picker").datepicker({
            showWeek: true,
            firstDay: 0, // Start week on Sunday
            showOtherMonths: true,
            selectOtherMonths: true,
            changeMonth: true,
            changeYear: true,
            beforeShowDay: function(date) {
                // Only allow selection of Sundays
                if (date.getDay() === 0) {
                    return [true, 'ui-state-sunday', ''];
                }
                return [false, '', ''];
            },
            onSelect: function(dateText, inst) {
                let selectedDate = new Date(dateText);

                // Set the start date (selected Sunday)
                let start_date = selectedDate.getFullYear() + '-' +
                    String(selectedDate.getMonth() + 1).padStart(2, '0') + '-' +
                    String(selectedDate.getDate()).padStart(2, '0');

                // Calculate week number
                let week_number = getWeekNumber(selectedDate);
                let year = selectedDate.getFullYear();

                // Set hidden field values
                $('#start_date').val(start_date);

                // Update display value
                $(this).val("Week Number " + week_number);

                console.log('Selected:', {
                    dateText: dateText,
                    start_date: start_date,
                    week_number: week_number,
                    year: year
                });
            }
        });

        // Set initial value based on PHP variables
        <?php if (!empty($week_number)): ?>
            $('#WEEK_NUMBER1').val("Week Number <?= $week_number ?>");
            $('#start_date').val("<?= $from_date ?>");
        <?php endif; ?>

        // Form submission handler for better debugging
        $('#reportForm').on('submit', function(e) {
            let startDate = $('#start_date').val();
            let weekInput = $('#WEEK_NUMBER1').val();

            console.log('Form submission:', {
                start_date: startDate,
                week_input: weekInput
            });

            if (!startDate) {
                alert('Please select a valid week first');
                e.preventDefault();
                return false;
            }
        });
    });
</script>