<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];

$title = "WEEKLY ROYALTY / SERVICE REPORT";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

$type = $_GET['type'];

$from_date = date('Y-m-d', strtotime($_GET['start_date']));
$to_date = date('Y-m-d', strtotime($from_date . ' +6 day'));

$week_number = $_GET['week_number'];
$YEAR = date('Y', strtotime($from_date));

$PAYMENT_QUERY = "SELECT 
                    DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_PAYMENT, 
                    DOA_ENROLLMENT_PAYMENT.AMOUNT, 
                    DOA_ENROLLMENT_PAYMENT.RECEIPT_NUMBER, 
                    DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE,
                    DOA_ENROLLMENT_PAYMENT.PK_ORDER,
                    DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE,
                    DOA_PAYMENT_TYPE.PAYMENT_TYPE, 
                    CONCAT(CUSTOMER.FIRST_NAME, ' ' ,CUSTOMER.LAST_NAME) AS STUDENT_NAME, 
                    CLOSER.FIRST_NAME AS CLOSER_FIRST_NAME, 
                    CLOSER.LAST_NAME AS CLOSER_LAST_NAME, 
                    DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER, 
                    DOA_ENROLLMENT_MASTER.CUSTOMER_ENROLLMENT_NUMBER, 
                    DOA_ENROLLMENT_MASTER.PK_LOCATION,
                    DOA_ENROLLMENT_MASTER.PK_PACKAGE
                FROM DOA_ENROLLMENT_PAYMENT 
                LEFT JOIN DOA_MASTER.DOA_PAYMENT_TYPE AS DOA_PAYMENT_TYPE 
                    ON DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE = DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE 
                LEFT JOIN DOA_ENROLLMENT_MASTER 
                    ON DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER 
                LEFT JOIN DOA_MASTER.DOA_USERS AS CLOSER 
                    ON DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID = CLOSER.PK_USER 
                LEFT JOIN DOA_ORDER 
                    ON DOA_ENROLLMENT_PAYMENT.PK_ORDER = DOA_ORDER.PK_ORDER 
                LEFT JOIN DOA_MASTER.DOA_USER_MASTER AS DOA_USER_MASTER 
                    ON (CASE 
                            WHEN DOA_ENROLLMENT_PAYMENT.PK_ORDER IS NULL 
                                THEN DOA_ENROLLMENT_MASTER.PK_USER_MASTER 
                            ELSE DOA_ORDER.PK_USER_MASTER 
                        END) = DOA_USER_MASTER.PK_USER_MASTER 
                LEFT JOIN DOA_MASTER.DOA_USERS AS CUSTOMER 
                    ON CUSTOMER.PK_USER = DOA_USER_MASTER.PK_USER 
                WHERE CUSTOMER.IS_DELETED = 0 AND DOA_ENROLLMENT_PAYMENT.NOT_EXPORT_TO_AMI = 0
                    AND DOA_ENROLLMENT_PAYMENT.IS_REFUNDED = 0 AND (DOA_ENROLLMENT_PAYMENT.TYPE = 'Payment' || DOA_ENROLLMENT_PAYMENT.TYPE = 'Adjustment') AND DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE NOT IN (5) 
                    AND DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE BETWEEN '" . date('Y-m-d', strtotime($from_date)) . "' AND '" . date('Y-m-d', strtotime($to_date)) . "'
                    AND (DOA_ENROLLMENT_PAYMENT.PK_ORDER IS NOT NULL OR DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $DEFAULT_LOCATION_ID . ")) 
                ORDER BY PAYMENT_DATE ASC, RECEIPT_NUMBER ASC";

$REFUND_QUERY = "SELECT
                        DOA_ENROLLMENT_PAYMENT.AMOUNT,
                        DOA_ENROLLMENT_PAYMENT.RECEIPT_NUMBER,
                        DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE,
                        DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE,
                        DOA_PAYMENT_TYPE.PAYMENT_TYPE,
                        CONCAT(CUSTOMER.FIRST_NAME, ' ' ,CUSTOMER.LAST_NAME) AS STUDENT_NAME,
                        CLOSER.FIRST_NAME AS CLOSER_FIRST_NAME,
                        CLOSER.LAST_NAME AS CLOSER_LAST_NAME,
                        DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER,
                        DOA_ENROLLMENT_MASTER.CUSTOMER_ENROLLMENT_NUMBER,
                        DOA_ENROLLMENT_MASTER.PK_LOCATION
                    FROM
                        DOA_ENROLLMENT_PAYMENT
                    LEFT JOIN $master_database.DOA_PAYMENT_TYPE AS DOA_PAYMENT_TYPE ON DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE = DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE
                            
                    LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER
                    LEFT JOIN $master_database.DOA_USERS AS CLOSER ON DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID = CLOSER.PK_USER
                    
                    LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER
                    LEFT JOIN $master_database.DOA_USERS AS CUSTOMER ON CUSTOMER.PK_USER = DOA_USER_MASTER.PK_USER
                    
                    WHERE CUSTOMER.IS_DELETED = 0 AND DOA_ENROLLMENT_PAYMENT.TYPE = 'Refund' AND DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE NOT IN (5) AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $DEFAULT_LOCATION_ID . ")
                    AND DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE BETWEEN '" . date('Y-m-d', strtotime($from_date)) . "' AND '" . date('Y-m-d', strtotime($to_date)) . "'
                    ORDER BY PAYMENT_DATE ASC, RECEIPT_NUMBER ASC";

$account_data = $db->Execute("SELECT * FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
$user_data = $db->Execute("SELECT * FROM DOA_USERS WHERE PK_USER = '$_SESSION[PK_USER]'");
$business_name = $account_data->RecordCount() > 0 ? $account_data->fields['BUSINESS_NAME'] : '';
if (preg_match("/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/", $business_name)) {
    $business_name = '';
} else {
    $business_name = 'Franchisee: ' . $business_name;
}

if ($type === 'export') {
    $PK_ENROLLMENT_PAYMENT_ARRAY = [];
    $location_array = explode(",", $DEFAULT_LOCATION_ID);
    if (count($location_array) > 1) {
        $error_message = "Please select any one location from top to export data.";
    } else {
        $access_token = getAccessToken();
        $authorization = "Authorization: Bearer " . $access_token;
        $line_item = [];
        $payment_data = $db_account->Execute($PAYMENT_QUERY);
        while (!$payment_data->EOF) {
            $TOTAL_UNIT = 0;
            $REGULAR_AMOUNT = 0;
            $SUNDRY_AMOUNT = 0;
            $MISC_AMOUNT = 0;

            $teacher_data = $db_account->Execute("SELECT TEACHER.FIRST_NAME, TEACHER.LAST_NAME FROM DOA_ENROLLMENT_SERVICE_PROVIDER LEFT JOIN $master_database.DOA_USERS AS TEACHER ON DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID = TEACHER.PK_USER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = " . $payment_data->fields['PK_ENROLLMENT_MASTER']);

            $enrollment_service_data = $db_account->Execute("SELECT SUM(`FINAL_AMOUNT`) AS TOTAL_AMOUNT, DOA_SERVICE_MASTER.PK_SERVICE_CLASS FROM `DOA_ENROLLMENT_SERVICE` LEFT JOIN DOA_SERVICE_MASTER ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER WHERE DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = " . $payment_data->fields['PK_ENROLLMENT_MASTER'] . " GROUP BY PK_ENROLLMENT_MASTER");
            $TOTAL_AMOUNT = ($enrollment_service_data->RecordCount() > 0) ? $enrollment_service_data->fields['TOTAL_AMOUNT'] : 0;
            $SERVICE_CLASS = ($enrollment_service_data->RecordCount() > 0) ? $enrollment_service_data->fields['PK_SERVICE_CLASS'] : '';

            $AMOUNT_PAID = $payment_data->fields['AMOUNT'];

            if ($SERVICE_CLASS == 5) {
                $MISC_AMOUNT = $AMOUNT_PAID;
            } else {
                $REGULAR_AMOUNT = $AMOUNT_PAID;
            }

            if ($payment_data->fields['PK_ENROLLMENT_MASTER'] == 0 && $payment_data->fields['PK_ORDER'] != null) {
                $SUNDRY_AMOUNT += $AMOUNT_PAID;
            } else {
                $enrollment_service_code_data = $db_account->Execute("SELECT DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION, DOA_ENROLLMENT_SERVICE.PRICE_PER_SESSION, DOA_ENROLLMENT_SERVICE.FINAL_AMOUNT, DOA_SERVICE_CODE.IS_SUNDRY, DOA_SERVICE_CODE.IS_GROUP FROM DOA_ENROLLMENT_SERVICE LEFT JOIN DOA_SERVICE_CODE ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE WHERE DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = " . $payment_data->fields['PK_ENROLLMENT_MASTER']);
                while (!$enrollment_service_code_data->EOF) {
                    if ($enrollment_service_code_data->fields['IS_GROUP'] == 0 && $enrollment_service_code_data->fields['PRICE_PER_SESSION'] > 0) {
                        $TOTAL_UNIT += $enrollment_service_code_data->fields['NUMBER_OF_SESSION'];
                    }
                    if ($SERVICE_CLASS == 5 && $enrollment_service_code_data->fields['IS_SUNDRY'] == 1) {
                        $servicePercent = ($enrollment_service_code_data->fields['FINAL_AMOUNT'] * 100) / $TOTAL_AMOUNT;
                        $serviceAmount = ($AMOUNT_PAID * $servicePercent) / 100;
                        $SUNDRY_AMOUNT += $serviceAmount;
                    }
                    $enrollment_service_code_data->MoveNext();
                }
            }

            if ($SUNDRY_AMOUNT > 0) {
                $MISC_AMOUNT = $AMOUNT_PAID - $SUNDRY_AMOUNT;
            }

            if ($SERVICE_CLASS == 5) {
                $PK_PACKAGE = $payment_data->fields['PK_PACKAGE'];
                $package_data = $db_account->Execute("SELECT PACKAGE_NAME FROM DOA_PACKAGE WHERE PK_PACKAGE = " . $PK_PACKAGE);
                if ($package_data->RecordCount() > 0) {
                    $package_name = $package_data->fields['PACKAGE_NAME'];
                } else {
                    $package_name = 'Custom Package';
                }
                $sale_code = 'MISC';
            } else {
                $package_name = null;
                switch ($payment_data->fields['CUSTOMER_ENROLLMENT_NUMBER']) {
                    case 1:
                        $sale_code = 'PORI';
                        break;
                    case 2:
                        $sale_code = 'ORI';
                        break;
                    case 3:
                        $sale_code = 'EXT';
                        break;

                    default:
                        $sale_code = 'REN';
                        break;
                }
            }

            $executive = getStaffCode($authorization, $payment_data->fields['CLOSER_FIRST_NAME'], $payment_data->fields['CLOSER_LAST_NAME']);
            $staff_members = [];
            while (!$teacher_data->EOF) {
                $staff_members[] = getStaffCode($authorization, $teacher_data->fields['FIRST_NAME'], $teacher_data->fields['LAST_NAME']);
                $teacher_data->MoveNext();
            }

            $line_item[] = array(
                "receipt_number" => $payment_data->fields['RECEIPT_NUMBER'],
                "date_paid" => date('Y-m-d', strtotime($payment_data->fields['PAYMENT_DATE'])),
                "students_full_name" => $payment_data->fields['STUDENT_NAME'],
                "executive" => $executive,
                "staff_members" => $staff_members,
                "sale_code" => $sale_code,
                "number_of_units" => $TOTAL_UNIT,
                "sale_value" => $TOTAL_AMOUNT,
                "cash" => ($MISC_AMOUNT <= 0) ? $AMOUNT_PAID : 0,
                "custom_package" => $package_name,
                "miscellaneous_services" => $MISC_AMOUNT,
                "sundry" => $SUNDRY_AMOUNT,
            );

            $PK_ENROLLMENT_PAYMENT_ARRAY[] = $payment_data->fields['PK_ENROLLMENT_PAYMENT'];

            $payment_data->MoveNext();
        }

        $refunds = [];
        $refund_data = $db_account->Execute($REFUND_QUERY);
        while (!$refund_data->EOF) {
            $AMOUNT_REFUND = $refund_data->fields['AMOUNT'];

            $refunds[] = array(
                "refund_type" => 'regular',
                "date_reported" => date('Y-m-d', strtotime($refund_data->fields['PAYMENT_DATE'])),
                "date_refunded" => date('Y-m-d', strtotime($refund_data->fields['PAYMENT_DATE'])),
                "student_name" => $refund_data->fields['STUDENT_NAME'],
                "amount" => $AMOUNT_REFUND,
            );
            $refund_data->MoveNext();
        }

        $data = [
            'type' => 'royalty',
            'prepared_by' => $user_data->fields['FIRST_NAME'] . ' ' . $user_data->fields['LAST_NAME'],
            'week_number' => $week_number,
            'week_year' => $YEAR,
            'line_items' => $line_item,
            'refunds' => $refunds
        ];

        $report_details = $db_account->Execute("SELECT * FROM `DOA_REPORT_EXPORT_DETAILS` WHERE PK_LOCATION = $DEFAULT_LOCATION_ID AND `REPORT_TYPE` = 'royalty_service_report' AND `YEAR` = '$YEAR' AND `WEEK_NUMBER` = " . $week_number);
        if ($report_details->RecordCount() > 0) {
            if ($report_details->fields['ID'] != '' && $report_details->fields['ID'] != null) {
                $url = constant('ami_api_url') . '/api/v1/reports/' . $report_details->fields['ID'];
                $post_data = callArturMurrayApi($url, $data, $authorization, 'PUT');
            } else {
                $get_url = constant('ami_api_url') . '/api/v1/reports';
                $get_data = [
                    'type' => 'royalty',
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
                    'type' => 'royalty',
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
                    $REPORT_DATA['REPORT_TYPE'] = 'royalty_service_report';
                    $REPORT_DATA['YEAR'] = $YEAR;
                    $REPORT_DATA['WEEK_NUMBER'] = $week_number;
                    $REPORT_DATA['ID'] = $report_id;
                    $REPORT_DATA['SUBMISSION_DATE'] = date('Y-m-d H:i:s');
                    db_perform_account('DOA_REPORT_EXPORT_DETAILS', $REPORT_DATA, "insert");
                }
            } else {
                $REPORT_DATA['PK_LOCATION'] = $DEFAULT_LOCATION_ID;
                $REPORT_DATA['REPORT_TYPE'] = 'royalty_service_report';
                $REPORT_DATA['YEAR'] = $YEAR;
                $REPORT_DATA['WEEK_NUMBER'] = $week_number;
                $REPORT_DATA['ID'] = $response->id;
                $REPORT_DATA['SUBMISSION_DATE'] = date('Y-m-d H:i:s');
                db_perform_account('DOA_REPORT_EXPORT_DETAILS', $REPORT_DATA, "insert");
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

    $START_DATE = $_GET['start_date'] ?? '';

    if ($generate_pdf === 1) {
        header('location:generate_report_pdf.php?week_number=' . $WEEK_NUMBER . '&start_date=' . $START_DATE . '&report_type=' . $report_name);
    } elseif ($generate_excel === 1) {
        header('location:excel_' . $report_name . '.php?week_number=' . $WEEK_NUMBER . '&start_date=' . $START_DATE . '&report_type=' . $report_name);
    } else {
        header('location:royalty_service_report.php?week_number=' . $WEEK_NUMBER . '&start_date=' . $START_DATE . '&type=' . $type);
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
                                        <input type="hidden" name="start_date" id="weekly_start_date">
                                        <input type="hidden" name="NAME" id="NAME" value="royalty_service_report">
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
                    if (isset($error_message)) {
                        echo '<div class="alert alert-danger alert-dismissible" role="alert">' . $error_message . '</div>';
                    } else {
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
                            foreach ($PK_ENROLLMENT_PAYMENT_ARRAY as $PK_ENROLLMENT_PAYMENT) {
                                $UPDATE_PAYMENT_DATA['IS_EXPORTED_TO_AMI'] = 1;
                                db_perform_account('DOA_ENROLLMENT_PAYMENT', $UPDATE_PAYMENT_DATA, "update", " PK_ENROLLMENT_PAYMENT = " . $PK_ENROLLMENT_PAYMENT);
                            }
                            echo "<h3 style='color: green;'>Data export to Arthur Murray API Successfully</h3>";
                        }
                    }
                } else { ?>
                    <div class="row">
                        <div class="col-12">
                            <div class="card">

                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-2" style="padding-bottom: 20px;">
                                            <img src="../assets/images/background/doable_logo.png" style="height: 60px; width: auto;">
                                        </div>

                                        <div class="col-8" style="padding-top: 10px;">
                                            <h3 class="card-title" style="padding-bottom:15px; text-align: center; font-weight: bold"><?= $title ?></h3>
                                        </div>
                                        <div class="col-2" style="padding-bottom: 20px;">
                                            <img src="../assets/images/background/doable_logo.png" style="float: right; height: 60px; width: auto;">
                                        </div>
                                    </div>

                                    <div class="table-responsive">
                                        <table id="myTable" class="table table-bordered" data-page-length='50'>
                                            <thead>
                                                <tr>
                                                    <th style="width:20%; text-align: center; vertical-align:auto; font-weight: bold" colspan="6"><?= $concatenatedResults ?></th>
                                                    <th style="width:20%; text-align: center; font-weight: bold" colspan="2">Part 1</th>
                                                    <th style="width:20%; text-align: center; font-weight: bold" colspan="5">Week # <?= $week_number ?> (<?= date('m/d/Y', strtotime($from_date)) ?> - <?= date('m/d/Y', strtotime($to_date)) ?>)</th>
                                                </tr>
                                                <tr>
                                                    <th style="width:10%; text-align: center; font-weight: bold" rowspan="2">Receipt number</th>
                                                    <th style="width:10%; text-align: center; font-weight: bold" rowspan="2">Date</th>
                                                    <th style="width:10%; text-align: center; font-weight: bold" rowspan="2">Student name</th>
                                                    <th style="width:12%; text-align: center; font-weight: bold" rowspan="2">Type</th>
                                                    <th style="width:10%; text-align: center; font-weight: bold" colspan="2">Staff code</th>
                                                    <th style="width:10%; text-align: center; font-weight: bold" colspan="2"></th>
                                                    <th style="width:10%; text-align: center; font-weight: bold" colspan="3">Studio Receipts</th>
                                                    <th style="width:10%; text-align: center; font-weight: bold" rowspan="2">Total<br>
                                                        subject<br>
                                                        R/S fee</th>
                                                    <th style="width:10%; text-align: center; font-weight: bold" rowspan="2">Studio<br>
                                                        Grand<br>
                                                        Total </th>

                                                </tr>
                                                <tr>
                                                    <th style="width:10%; text-align: center; font-weight: bold">Closer</th>
                                                    <th style="width:10%; text-align: center; font-weight: bold">Teachers</th>
                                                    <th style="width:10%; text-align: center; font-weight: bold">First
                                                        payment or
                                                        A/C
                                                    </th>
                                                    <th style="width:10%; text-align: center; font-weight: bold">Units/Total Cost</th>
                                                    <th style="width:10%; text-align: center; font-weight: bold">Regular</th>
                                                    <th style="width:10%; text-align: center; font-weight: bold">Sundry</th>
                                                    <th style="width:10%; text-align: center; font-weight: bold">Misc./NonUnit</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $payment_data = $db_account->Execute($PAYMENT_QUERY);
                                                $REGULAR_TOTAL = 0;
                                                $SUNDRY_TOTAL = 0;
                                                $MISC_TOTAL = 0;
                                                $TOTAL_RS_FEE = 0;
                                                $TOTAL_AMOUNT_PAID = 0;
                                                $LOCATION_TOTAL = [];

                                                $TOTAL_AMOUNT_PAID_DAILY = 0;
                                                $REGULAR_TOTAL_DAILY = 0;
                                                $SUNDRY_TOTAL_DAILY = 0;
                                                $MISC_TOTAL_DAILY = 0;

                                                $total_record = $payment_data->RecordCount();
                                                $i = 0;
                                                while (!$payment_data->EOF) {
                                                    if ($i == 0) {
                                                        $last_date = $payment_data->fields['PAYMENT_DATE'];
                                                    }
                                                    $TOTAL_UNIT = 0;
                                                    $REGULAR_AMOUNT = 0;
                                                    $SUNDRY_AMOUNT = 0;
                                                    $MISC_AMOUNT = 0;
                                                    $teacher_data = $db_account->Execute("SELECT GROUP_CONCAT(DISTINCT(CONCAT(TEACHER.FIRST_NAME, ' ', TEACHER.LAST_NAME)) SEPARATOR ', ') AS TEACHER_NAME FROM DOA_ENROLLMENT_SERVICE_PROVIDER LEFT JOIN $master_database.DOA_USERS AS TEACHER ON DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID = TEACHER.PK_USER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = " . $payment_data->fields['PK_ENROLLMENT_MASTER']);
                                                    $enrollment_service_data = $db_account->Execute("SELECT SUM(`FINAL_AMOUNT`) AS TOTAL_AMOUNT, DOA_SERVICE_MASTER.PK_SERVICE_CLASS FROM `DOA_ENROLLMENT_SERVICE` LEFT JOIN DOA_SERVICE_MASTER ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER WHERE DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = " . $payment_data->fields['PK_ENROLLMENT_MASTER'] . " GROUP BY PK_ENROLLMENT_MASTER");
                                                    $TOTAL_AMOUNT = ($enrollment_service_data->RecordCount() > 0) ? $enrollment_service_data->fields['TOTAL_AMOUNT'] : 0;
                                                    $SERVICE_CLASS = ($enrollment_service_data->RecordCount() > 0) ? $enrollment_service_data->fields['PK_SERVICE_CLASS'] : '';

                                                    $AMOUNT_PAID = $payment_data->fields['AMOUNT'];
                                                    $TOTAL_AMOUNT_PAID += $AMOUNT_PAID;
                                                    $TOTAL_AMOUNT_PAID_DAILY += $AMOUNT_PAID;

                                                    if ($SERVICE_CLASS == 5) {
                                                        $MISC_AMOUNT = $AMOUNT_PAID;
                                                    } else {
                                                        $REGULAR_AMOUNT = $AMOUNT_PAID;
                                                    }

                                                    if ($payment_data->fields['PK_ENROLLMENT_MASTER'] == 0 && $payment_data->fields['PK_ORDER'] != null) {
                                                        $SUNDRY_AMOUNT += $AMOUNT_PAID;
                                                    } else {
                                                        $enrollment_service_code_data = $db_account->Execute("SELECT DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION, DOA_ENROLLMENT_SERVICE.PRICE_PER_SESSION, DOA_ENROLLMENT_SERVICE.FINAL_AMOUNT, DOA_SERVICE_CODE.IS_SUNDRY, DOA_SERVICE_CODE.IS_GROUP FROM DOA_ENROLLMENT_SERVICE LEFT JOIN DOA_SERVICE_CODE ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE WHERE DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = " . $payment_data->fields['PK_ENROLLMENT_MASTER']);
                                                        while (!$enrollment_service_code_data->EOF) {
                                                            if ($enrollment_service_code_data->fields['IS_GROUP'] == 0 && $enrollment_service_code_data->fields['PRICE_PER_SESSION'] > 0) {
                                                                $TOTAL_UNIT += $enrollment_service_code_data->fields['NUMBER_OF_SESSION'];
                                                            }
                                                            if ($SERVICE_CLASS == 5 && $enrollment_service_code_data->fields['IS_SUNDRY'] == 1) {
                                                                $servicePercent = ($enrollment_service_code_data->fields['FINAL_AMOUNT'] * 100) / $TOTAL_AMOUNT;
                                                                $serviceAmount = ($AMOUNT_PAID * $servicePercent) / 100;
                                                                $SUNDRY_AMOUNT += $serviceAmount;
                                                            }
                                                            $enrollment_service_code_data->MoveNext();
                                                        }
                                                    }

                                                    if ($payment_data->fields['PK_PAYMENT_TYPE'] == '7') {
                                                        $receipt_number = $payment_data->fields['RECEIPT_NUMBER'];
                                                        $receipt_payment_details = $db_account->Execute("SELECT DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE, DOA_ENROLLMENT_PAYMENT.PAYMENT_INFO, DOA_PAYMENT_TYPE.PAYMENT_TYPE FROM DOA_ENROLLMENT_PAYMENT LEFT JOIN $master_database.DOA_PAYMENT_TYPE AS DOA_PAYMENT_TYPE ON DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE = DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE WHERE IS_ORIGINAL_RECEIPT = 1 AND DOA_ENROLLMENT_PAYMENT.RECEIPT_NUMBER = '$receipt_number'");
                                                        $payment_type = $receipt_payment_details->fields['PAYMENT_TYPE'];
                                                    } else {
                                                        $payment_type = $payment_data->fields['PAYMENT_TYPE'];
                                                    }

                                                    if ($SUNDRY_AMOUNT > 0) {
                                                        $MISC_AMOUNT = $AMOUNT_PAID - $SUNDRY_AMOUNT;
                                                    }

                                                    $REGULAR_TOTAL += $REGULAR_AMOUNT;
                                                    $SUNDRY_TOTAL += $SUNDRY_AMOUNT;
                                                    $MISC_TOTAL += $MISC_AMOUNT;

                                                    $REGULAR_TOTAL_DAILY += $REGULAR_AMOUNT;
                                                    $SUNDRY_TOTAL_DAILY += $SUNDRY_AMOUNT;
                                                    $MISC_TOTAL_DAILY += $MISC_AMOUNT;

                                                    $TOTAL_RS_FEE += $REGULAR_AMOUNT;
                                                    if (isset($LOCATION_TOTAL[$payment_data->fields['PK_LOCATION']])) {
                                                        $LOCATION_TOTAL[$payment_data->fields['PK_LOCATION']] = $LOCATION_TOTAL[$payment_data->fields['PK_LOCATION']] + ($REGULAR_AMOUNT + $MISC_AMOUNT);
                                                    } else {
                                                        $LOCATION_TOTAL[$payment_data->fields['PK_LOCATION']] = ($REGULAR_AMOUNT + $MISC_AMOUNT);
                                                    } ?>
                                                    <tr style="text-align: center;">
                                                        <td><?= $payment_data->fields['RECEIPT_NUMBER'] ?></td>
                                                        <td><?= date('m/d/Y', strtotime($payment_data->fields['PAYMENT_DATE'])) ?></td>
                                                        <td><?= $payment_data->fields['STUDENT_NAME'] ?></td>

                                                        <td><?= $payment_type ?></td>

                                                        <td><?= $payment_data->fields['CLOSER_FIRST_NAME'] . " " . $payment_data->fields['CLOSER_LAST_NAME'] ?></td>
                                                        <td><?= $teacher_data->fields['TEACHER_NAME'] ?></td>
                                                        <td>
                                                            <?php
                                                            if ($SERVICE_CLASS == 5) {
                                                                echo $payment_data->fields['CUSTOMER_ENROLLMENT_NUMBER'] . '/MISC';
                                                            } else {
                                                                switch ($payment_data->fields['CUSTOMER_ENROLLMENT_NUMBER']) {
                                                                    case 1:
                                                                        echo '1/PORI';
                                                                        break;
                                                                    case 2:
                                                                        echo '2/ORI';
                                                                        break;
                                                                    case 3:
                                                                        echo '3/EXT';
                                                                        break;

                                                                    default:
                                                                        echo $payment_data->fields['CUSTOMER_ENROLLMENT_NUMBER'] . '/REN';
                                                                        break;
                                                                }
                                                            }
                                                            ?>
                                                        </td>
                                                        <td><?= $TOTAL_UNIT . ' / $' . $TOTAL_AMOUNT ?></td>
                                                        <td><?= $REGULAR_AMOUNT ?></td>
                                                        <td><?= $SUNDRY_AMOUNT ?></td>
                                                        <td><?= $MISC_AMOUNT ?></td>
                                                        <td><?= '$' . $AMOUNT_PAID ?></td>
                                                        <td><?= '$' . number_format($TOTAL_AMOUNT_PAID, 2) ?></td>
                                                    </tr>
                                                    <?php
                                                    $payment_data->MoveNext();
                                                    $i++;
                                                    if (($last_date != $payment_data->fields['PAYMENT_DATE']) || ($i == $total_record)) { ?>
                                                        <tr>
                                                            <th style="width:10%; text-align: center; font-weight: bold" colspan="7">Daily Totals</th>
                                                            <th style="width:10%; text-align: center; font-weight: bold" colspan="1"><?= '$' . number_format($TOTAL_AMOUNT_PAID_DAILY, 2) ?></th>
                                                            <th style="width:10%; text-align: center; font-weight: bold" colspan="1"><?= '$' . number_format($REGULAR_TOTAL_DAILY, 2) ?></th>
                                                            <th style="width:10%; text-align: center; font-weight: bold" colspan="1"><?= '$' . number_format($SUNDRY_TOTAL_DAILY, 2) ?></th>
                                                            <th style="width:10%; text-align: center; font-weight: bold" colspan="1"><?= '$' . number_format($MISC_TOTAL_DAILY, 2) ?></th>
                                                            <th style="width:10%; text-align: center; font-weight: bold" colspan="1"><?= '$' . number_format($TOTAL_RS_FEE + $MISC_TOTAL, 2) ?></th>
                                                            <th style="width:10%; text-align: center; font-weight: bold" colspan="1"><?= '$' . number_format($TOTAL_AMOUNT_PAID, 2) ?></th>
                                                        </tr>
                                                        <?php
                                                        if ($i < $total_record) { ?>
                                                            <tr>
                                                                <th style="width:20%; text-align: center; vertical-align:auto; font-weight: bold" colspan="6"><?= $business_name ?></th>
                                                                <th style="width:20%; text-align: center; font-weight: bold" colspan="2">Part 1</th>
                                                                <th style="width:20%; text-align: center; font-weight: bold" colspan="5">Week # <?= $week_number ?> (<?= date('m/d/Y', strtotime($from_date)) ?> - <?= date('m/d/Y', strtotime($to_date)) ?>)</th>
                                                            </tr>
                                                        <?php } ?>
                                                <?php
                                                        $TOTAL_AMOUNT_PAID_DAILY = 0;
                                                        $REGULAR_TOTAL_DAILY = 0;
                                                        $SUNDRY_TOTAL_DAILY = 0;
                                                        $MISC_TOTAL_DAILY = 0;
                                                    }
                                                    $last_date = $payment_data->fields['PAYMENT_DATE'];
                                                }
                                                ?>
                                                <!--<tr>
                                            <th style="width:10%; text-align: center; font-weight: bold" colspan="7">Daily Totals</th>
                                            <th style="width:10%; text-align: center; font-weight: bold" colspan="1"><?php /*='$'.number_format($TOTAL_AMOUNT_PAID, 2)*/ ?></th>
                                            <th style="width:10%; text-align: center; font-weight: bold" colspan="1"><?php /*='$'.number_format($REGULAR_TOTAL, 2)*/ ?></th>
                                            <th style="width:10%; text-align: center; font-weight: bold" colspan="1"><?php /*='$'.number_format($SUNDRY_TOTAL, 2)*/ ?></th>
                                            <th style="width:10%; text-align: center; font-weight: bold" colspan="1"><?php /*='$'.number_format($MISC_TOTAL, 2)*/ ?></th>
                                            <th style="width:10%; text-align: center; font-weight: bold" colspan="1"><?php /*='$'.number_format($TOTAL_RS_FEE, 2)*/ ?></th>
                                            <th style="width:10%; text-align: center; font-weight: bold" colspan="1"><?php /*='$'.number_format($TOTAL_AMOUNT_PAID, 2)*/ ?></th>
                                        </tr>-->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
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
                                            <tbody>
                                                <tr>
                                                    <th style="width:20%; text-align: center; vertical-align:auto; font-weight: bold" colspan="6"><?= $business_name ?></th>
                                                    <th style="width:20%; text-align: center; font-weight: bold" colspan="2">Part 2</th>
                                                    <th style="width:20%; text-align: center; font-weight: bold" colspan="5">Week # <?= $week_number ?> (<?= date('m/d/Y', strtotime($from_date)) ?> - <?= date('m/d/Y', strtotime($to_date)) ?>)</th>
                                                </tr>
                                                <tr>
                                                    <th colspan="13">Refunds or credits below completed tuition refund report & photocopy of front & back of caceled cheks must be attached in order to receive credits.
                                                        (Identify bank plan, rewrites & cancellation. Attach detail on authorized D-O-R transportation details.)</th>
                                                </tr>

                                                <tr>
                                                    <th style="width:10%; text-align: center; font-weight: bold" rowspan="2">Receipt number</th>
                                                    <th style="width:10%; text-align: center; font-weight: bold" rowspan="2">Date</th>
                                                    <th style="width:10%; text-align: center; font-weight: bold" rowspan="2" colspan="2">Student name</th>
                                                    <th style="width:20%; text-align: center; font-weight: bold" colspan="2">Staff code</th>
                                                    <th style="width:20%; text-align: center; font-weight: bold" colspan="2"></th>
                                                    <th style="width:20%; text-align: center; font-weight: bold" colspan="3">Studio Refunds Deductions</th>
                                                </tr>
                                                <tr>
                                                    <th style="width:10%; text-align: center; font-weight: bold">Closer</th>
                                                    <th style="width:10%; text-align: center; font-weight: bold">Teachers</th>
                                                    <th style="width:10%; text-align: center; font-weight: bold">Type</th>
                                                    <th style="width:10%; text-align: center; font-weight: bold">Units/Total Cost</th>
                                                </tr>
                                                <?php
                                                $TOTAL_AMOUNT_REFUND = 0;
                                                $refund_data = $db_account->Execute($REFUND_QUERY);
                                                while (!$refund_data->EOF) {
                                                    $REFUND_TOTAL_UNIT = 0;
                                                    $teacher_data = $db_account->Execute("SELECT GROUP_CONCAT(DISTINCT(CONCAT(TEACHER.FIRST_NAME, ' ', TEACHER.LAST_NAME)) SEPARATOR ', ') AS TEACHER_NAME FROM DOA_ENROLLMENT_SERVICE_PROVIDER LEFT JOIN $master_database.DOA_USERS AS TEACHER ON DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID = TEACHER.PK_USER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = " . $refund_data->fields['PK_ENROLLMENT_MASTER']);
                                                    $enrollment_service_data = $db_account->Execute("SELECT SUM(`FINAL_AMOUNT`) AS TOTAL_AMOUNT, DOA_SERVICE_MASTER.PK_SERVICE_CLASS FROM `DOA_ENROLLMENT_SERVICE` LEFT JOIN DOA_SERVICE_MASTER ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER WHERE DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = " . $refund_data->fields['PK_ENROLLMENT_MASTER'] . " GROUP BY PK_ENROLLMENT_MASTER");
                                                    $REFUND_TOTAL_AMOUNT = $enrollment_service_data->fields['TOTAL_AMOUNT'];

                                                    $enrollment_service_code_data = $db_account->Execute("SELECT DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION, DOA_ENROLLMENT_SERVICE.PRICE_PER_SESSION, DOA_ENROLLMENT_SERVICE.FINAL_AMOUNT, DOA_SERVICE_CODE.IS_SUNDRY, DOA_SERVICE_CODE.IS_GROUP FROM DOA_ENROLLMENT_SERVICE LEFT JOIN DOA_SERVICE_CODE ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE WHERE DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = " . $refund_data->fields['PK_ENROLLMENT_MASTER']);
                                                    while (!$enrollment_service_code_data->EOF) {
                                                        if ($enrollment_service_code_data->fields['IS_GROUP'] == 0 && $enrollment_service_code_data->fields['PRICE_PER_SESSION'] > 0) {
                                                            $REFUND_TOTAL_UNIT += $enrollment_service_code_data->fields['NUMBER_OF_SESSION'];
                                                        }
                                                        $enrollment_service_code_data->MoveNext();
                                                    }

                                                    $AMOUNT_REFUND = $refund_data->fields['AMOUNT'];
                                                    $TOTAL_AMOUNT_REFUND += $AMOUNT_REFUND; ?>
                                                    <tr style="text-align: center;">
                                                        <td><?= $refund_data->fields['RECEIPT_NUMBER'] ?></td>
                                                        <td><?= date('m/d/Y', strtotime($refund_data->fields['PAYMENT_DATE'])) ?></td>
                                                        <td colspan="2"><?= $refund_data->fields['STUDENT_NAME'] ?></td>
                                                        <td><?= $refund_data->fields['CLOSER_FIRST_NAME'] . " " . $refund_data->fields['CLOSER_LAST_NAME'] ?></td>
                                                        <td><?= $teacher_data->fields['TEACHER_NAME'] ?></td>
                                                        <td><?= $refund_data->fields['PAYMENT_TYPE'] ?></td>
                                                        <td><?= $REFUND_TOTAL_UNIT . ' / $' . $REFUND_TOTAL_AMOUNT ?></td>
                                                        <td colspan="3"><?= '-$' . number_format($AMOUNT_REFUND, 2) ?></td>
                                                    </tr>
                                                <?php
                                                    $refund_data->MoveNext();
                                                } ?>

                                                <tr>
                                                    <th style="width:70%; text-align: center; font-weight: bold" colspan="8">Refunds Total</th>
                                                    <th style="width:30%; text-align: center; font-weight: bold" colspan="3"><?= '-$' . number_format($TOTAL_AMOUNT_REFUND, 2) ?></th>
                                                </tr>

                                                <tr>
                                                    <th style="width:10%; text-align: center; font-weight: bold"></th>
                                                    <th style="width:10%; text-align: center; font-weight: bold">Regular Cash +</th>
                                                    <th style="width:10%; text-align: center; font-weight: bold">Sundry +</th>
                                                    <th style="width:10%; text-align: center; font-weight: bold">Misc./NonUnit -</th>
                                                    <th style="width:10%; text-align: center; font-weight: bold">Sundry deduct.</th>
                                                    <th style="width:5%; text-align: center; font-weight: bold">=</th>
                                                    <th style="width:10%; text-align: center; font-weight: bold">Total sub.rlty</th>
                                                    <th style="width:10%; text-align: center; font-weight: bold" colspan="4">Sundry cash Studio total</th>
                                                </tr>
                                                <tr>
                                                    <td style="width:10%; text-align: center; font-weight: bold;">Total receipts</td>
                                                    <td style="width:10%; text-align: center;"><?= '$' . number_format($REGULAR_TOTAL, 2) ?></td>
                                                    <td style="width:10%; text-align: center;"><?= '$' . number_format($SUNDRY_TOTAL, 2) ?></td>
                                                    <td style="width:10%; text-align: center;"><?= '$' . number_format($MISC_TOTAL, 2) ?></td>
                                                    <td style="width:10%; text-align: center;">$0.00</td>
                                                    <td style="width:5%; text-align: center;">=</td>
                                                    <td style="width:10%; text-align: center;"><?= '$' . number_format($TOTAL_RS_FEE + $MISC_TOTAL, 2) ?></td>
                                                    <td style="width:5%; text-align: center;">+</td>
                                                    <td style="width:10%; text-align: center;"><?= '$' . number_format($SUNDRY_TOTAL, 2) ?></td>
                                                    <td style="width:5%; text-align: center;">=</td>
                                                    <td style="width:10%; text-align: center;"><?= '$' . number_format($TOTAL_RS_FEE + $MISC_TOTAL + $SUNDRY_TOTAL, 2) ?></td>
                                                </tr>
                                                <tr>
                                                    <td style="width:10%; text-align: center; font-weight: bold">Total refunds/credits</td>
                                                    <td style="width:10%; text-align: center;"></td>
                                                    <td style="width:10%; text-align: center;"></td>
                                                    <td style="width:10%; text-align: center;"></td>
                                                    <td style="width:10%; text-align: center;"></td>
                                                    <td style="width:5%; text-align: center;">=</td>
                                                    <td style="width:10%; text-align: center;"><?= '-$' . number_format($TOTAL_AMOUNT_REFUND, 2) ?></td>
                                                    <td style="width:5%; text-align: center;"></td>
                                                    <td style="width:10%; text-align: center;"></td>
                                                    <td style="width:5%; text-align: center;">=</td>
                                                    <td style="width:10%; text-align: center;"><?= '-$' . number_format($TOTAL_AMOUNT_REFUND, 2) ?></td>
                                                </tr>
                                                <tr>
                                                    <td style="width:10%; text-align: center; font-weight: bold" colspan="6">Total subject to r/s fee
                                                        <span style="font-weight: normal; float: right;">
                                                            <?php
                                                            $royalty_percent = '';
                                                            $location_total = '';
                                                            $royalty_percent_array = [];
                                                            foreach ($LOCATION_TOTAL as $key => $value) {
                                                                if ($key != null) {
                                                                    $location_name = $db->Execute("SELECT LOCATION_NAME, ROYALTY_PERCENTAGE FROM `DOA_LOCATION` WHERE `PK_LOCATION` = " . $key);
                                                                    echo $location_name->fields['LOCATION_NAME'] . " - " . "<br>";
                                                                    $royalty_percent .= "X " . $location_name->fields['ROYALTY_PERCENTAGE'] . " %" . "<br>";
                                                                    $location_total .= "$" . number_format($value - $TOTAL_AMOUNT_REFUND, 2) . "<br>";
                                                                    $royalty_percent_array[$key]['ROYALTY_PERCENTAGE'] = $location_name->fields['ROYALTY_PERCENTAGE'];
                                                                    $royalty_percent_array[$key]['LOCATION_TOTAL'] = $value - $TOTAL_AMOUNT_REFUND;
                                                                }
                                                            }
                                                            ?>
                                                        </span>
                                                    </td>
                                                    <td style="width:10%; text-align: center;">
                                                        <?= $location_total ?>
                                                        <hr>
                                                        <?= '$' . number_format(($TOTAL_RS_FEE + $MISC_TOTAL) - $TOTAL_AMOUNT_REFUND, 2) ?>
                                                    </td>
                                                    <td style="width:10%; text-align: center;" colspan="2"><?= $royalty_percent ?></td>
                                                    <td style="width:10%; text-align: center;" colspan="2">
                                                        <?php
                                                        foreach ($royalty_percent_array as $key => $value) {
                                                            echo "$" . number_format(($value['LOCATION_TOTAL'] * ($value['ROYALTY_PERCENTAGE'] / 100)), 2) . "<br>";
                                                        }
                                                        ?>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <div>
                                            I have checked this statement and certify that it is correct and complete. I also certify that none of the above listed enrollments or payments resulted in violation of the Arthur Murray International Inc. dollar limitation policy or limitations set by other regulatory agencies.<br>
                                            I further certify that all the above have been enrolled on Arthur Murray International Inc. approved student enrollment agreements.<br><br>
                                            Prepared by : ............................ Title : ............................ Date : ............................ Signed franchisee ............................
                                        </div>
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
        $(".week-picker").datepicker({
            showWeek: true,
            showOtherMonths: true,
            selectOtherMonths: true,
            changeMonth: true,
            changeYear: true,
            calculateWeek: function(date) {
                return '#' + getBusinessWeek(date).week;
            },

            beforeShowDay: function(date) {
                return [date.getDay() === 0, ''];
            },

            onSelect: function(dateText) {
                let d = new Date(dateText);
                let bw = getBusinessWeek(d);

                let start_date = (d.getMonth() + 1) + '/' + d.getDate() + '/' + d.getFullYear();

                $('#weekly_start_date').val(start_date);
                $(this).val("Week Number " + bw.week);
            }
        });

        function startOfWeekSunday(d) {
            let s = new Date(d);
            s.setHours(0, 0, 0, 0);
            s.setDate(s.getDate() - s.getDay()); // Sunday = 0
            return s;
        }

        function getBusinessWeek(date) {
            let d = new Date(date);
            d.setHours(0, 0, 0, 0);

            let weekStart = startOfWeekSunday(d);

            // Decide which year this week belongs to (majority rule)
            let yearCounts = {};
            for (let i = 0; i < 7; i++) {
                let wd = new Date(weekStart);
                wd.setDate(weekStart.getDate() + i);
                let y = wd.getFullYear();
                yearCounts[y] = (yearCounts[y] || 0) + 1;
            }

            let weekYear = Object.keys(yearCounts).reduce(function(a, b) {
                return yearCounts[a] >= yearCounts[b] ? a : b;
            });

            weekYear = parseInt(weekYear, 10);

            // Find Week 1 start for that weekYear:
            // First Sunday whose week has >=4 days in weekYear
            let jan1 = new Date(weekYear, 0, 1);
            jan1.setHours(0, 0, 0, 0);

            let w1 = startOfWeekSunday(jan1);

            let daysInWeekYear = 0;
            for (let j = 0; j < 7; j++) {
                let d1 = new Date(w1);
                d1.setDate(w1.getDate() + j);
                if (d1.getFullYear() === weekYear) {
                    daysInWeekYear++;
                }
            }

            if (daysInWeekYear < 4) {
                w1.setDate(w1.getDate() + 7);
            }

            // Count Sundays between w1 and weekStart
            let weeks = 0;
            let cursor = new Date(w1);

            while (cursor <= weekStart) {
                weeks++;
                cursor.setDate(cursor.getDate() + 7);
            }

            return {
                week: weeks,
                year: weekYear
            };
        }

        // Set initial value based on PHP variables
        <?php if (!empty($week_number)): ?>
            $('#WEEK_NUMBER1').val("Week Number <?= $week_number ?>");
        <?php endif; ?>
    });
</script>