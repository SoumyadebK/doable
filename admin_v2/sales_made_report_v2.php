<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;

$title = "SALES MADE REPORT (4+ and MISC)";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

// Get the current parameters
$type = isset($_GET['type']) ? $_GET['type'] : '';

// Handle redirects from business_reports.php - ONLY if coming from there
if (isset($_GET['WEEK_NUMBER']) && empty($type)) {
    $generate_pdf = isset($_GET['generate_pdf']) ? 1 : 0;
    $generate_excel = isset($_GET['generate_excel']) ? 1 : 0;
    $view = isset($_GET['view']) ? 1 : 0;
    $report_name = 'sales_made_report_v2';

    $week_parts = explode(' ', $_GET['WEEK_NUMBER']);
    $WEEK_NUMBER = end($week_parts);
    $START_DATE = isset($_GET['start_date']) ? $_GET['start_date'] : '';
    $END_DATE = isset($_GET['end_date']) ? $_GET['end_date'] : '';

    if ($generate_pdf === 1) {
        header('location:generate_report_pdf.php?week_number=' . $WEEK_NUMBER . '&start_date=' . $START_DATE . '&end_date=' . $END_DATE . '&report_type=' . $report_name);
        exit;
    } elseif ($generate_excel === 1) {
        header('location:excel_' . $report_name . '.php?week_number=' . $WEEK_NUMBER . '&start_date=' . $START_DATE . '&end_date=' . $END_DATE . '&report_type=' . $report_name);
        exit;
    } elseif ($view === 1) {
        header('location:sales_made_report_v2.php?week_number=' . $WEEK_NUMBER . '&start_date=' . $START_DATE . '&end_date=' . $END_DATE . '&type=view');
        exit;
    }
}

// Get dates
if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
    $from_date = date('Y-m-d', strtotime($_GET['start_date']));
    $to_date = date('Y-m-d', strtotime($_GET['end_date']));
    $week_number = isset($_GET['week_number']) ? $_GET['week_number'] : date('W', strtotime($from_date));
} else {
    $from_date = date('Y-m-d', strtotime('last sunday'));
    $to_date = date('Y-m-d', strtotime('next saturday'));
    $week_number = date('W');
}

$YEAR = date('Y', strtotime($from_date));

// Date conditions
$weekly_date_condition = "'" . date('Y-m-d', strtotime($from_date)) . "' AND '" . date('Y-m-d', strtotime($to_date)) . "'";
$enrollment_date_condition = " AND em.ENROLLMENT_DATE BETWEEN '" . date('Y-m-d', strtotime($from_date)) . "' AND '" . date('Y-m-d', strtotime($to_date)) . "'";
$payment_date_condition = " AND DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE BETWEEN '" . date('Y-m-d', strtotime($from_date)) . "' AND '" . date('Y-m-d', strtotime($to_date)) . "'";

// Get business and location info
$res = $db->Execute("SELECT BUSINESS_NAME, FRANCHISE FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
$business_name = $res->RecordCount() > 0 ? $res->fields['BUSINESS_NAME'] : '';
if (preg_match("/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/", $business_name)) {
    $business_name = '';
} else {
    $business_name = 'Franchisee: ' . $business_name;
}

// Get location name
$results = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME FROM DOA_LOCATION WHERE PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND ACTIVE = 1 AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
$resultsArray = [];
while (!$results->EOF) {
    $resultsArray[] = $results->fields['LOCATION_NAME'];
    $results->MoveNext();
}
$totalResults = count($resultsArray);
$concatenatedResults = "";
foreach ($resultsArray as $key => $result) {
    $concatenatedResults .= $result;
    if ($key < $totalResults - 1) {
        $concatenatedResults .= ", ";
    }
}

// Function to get enrollment payments
function getEnrollmentPayments($db_account, $enrollment_id, $payment_date_condition)
{
    $payments = [];
    $payment_data = $db_account->Execute("
        SELECT 
            DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_PAYMENT,
            DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE,
            DOA_ENROLLMENT_PAYMENT.AMOUNT,
            DOA_ENROLLMENT_PAYMENT.RECEIPT_NUMBER,
            DOA_ENROLLMENT_PAYMENT.PAYMENT_INFO,
            DOA_ENROLLMENT_PAYMENT.TYPE,
            DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE,
            DOA_ENROLLMENT_PAYMENT.IS_REFUNDED,
            DOA_PAYMENT_TYPE.PAYMENT_TYPE AS PAYMENT_TYPE_NAME,
            DOA_ENROLLMENT_TIP.TIP_AMOUNT
        FROM DOA_ENROLLMENT_PAYMENT
        LEFT JOIN " . $GLOBALS['master_database'] . ".DOA_PAYMENT_TYPE AS DOA_PAYMENT_TYPE 
            ON DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE = DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE
        LEFT JOIN DOA_ENROLLMENT_TIP 
            ON DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_PAYMENT = DOA_ENROLLMENT_TIP.PK_ENROLLMENT_PAYMENT
        WHERE DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER = " . $enrollment_id . "
            AND DOA_ENROLLMENT_PAYMENT.IS_REFUNDED = 0
            AND DOA_ENROLLMENT_PAYMENT.TYPE != 'Refund'
            " . $payment_date_condition . "
        ORDER BY DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE ASC
    ");

    while (!$payment_data->EOF) {
        $tip_amount = $payment_data->fields['TIP_AMOUNT'] ?? 0;
        $payments[] = [
            'date' => $payment_data->fields['PAYMENT_DATE'],
            'amount' => $payment_data->fields['AMOUNT'],
            'total_amount' => $payment_data->fields['AMOUNT'] + $tip_amount,
            'receipt_number' => $payment_data->fields['RECEIPT_NUMBER'],
            'payment_type' => $payment_data->fields['PAYMENT_TYPE_NAME'],
            'type' => $payment_data->fields['TYPE'],
            'tip' => $tip_amount
        ];
        $payment_data->MoveNext();
    }
    return $payments;
}

// Function to get enrollments by type - EXACTLY matching Summary Report's sales query
function getEnrollmentsByType($db_account, $type_id, $date_condition, $location_id, $payment_date_condition, $is_misc = false)
{
    $enrollments = [];

    // Build the query based on whether it's MISC or regular (4+)
    if ($is_misc) {
        // MISC query - matches Summary Report's misc sales query
        $query = "
            SELECT 
                em.PK_ENROLLMENT_MASTER,
                em.ENROLLMENT_ID,
                em.ENROLLMENT_NAME,
                em.ENROLLMENT_DATE,
                em.ENROLLMENT_BY_ID,
                em.PK_USER_MASTER,
                em.MISC_ID,
                SUM(es.FINAL_AMOUNT) AS TOTAL_AMOUNT,
                CONCAT(u.FIRST_NAME, ' ', u.LAST_NAME) AS CLIENT_NAME,
                CONCAT(ub.FIRST_NAME, ' ', ub.LAST_NAME) AS CLOSER_NAME
            FROM DOA_ENROLLMENT_SERVICE es
            LEFT JOIN DOA_SERVICE_CODE sc ON es.PK_SERVICE_CODE = sc.PK_SERVICE_CODE
            LEFT JOIN DOA_ENROLLMENT_MASTER em ON es.PK_ENROLLMENT_MASTER = em.PK_ENROLLMENT_MASTER
            LEFT JOIN " . $GLOBALS['master_database'] . ".DOA_USER_MASTER um ON em.PK_USER_MASTER = um.PK_USER_MASTER
            LEFT JOIN " . $GLOBALS['master_database'] . ".DOA_USERS u ON um.PK_USER = u.PK_USER
            LEFT JOIN " . $GLOBALS['master_database'] . ".DOA_USERS ub ON em.ENROLLMENT_BY_ID = ub.PK_USER
            WHERE em.PK_LOCATION IN (" . $location_id . ")
                AND em.MISC_ID LIKE '%MISC%'
                " . $date_condition . "
            GROUP BY em.PK_ENROLLMENT_MASTER
            ORDER BY em.ENROLLMENT_DATE DESC
        ";
    } else {
        // 4+ Enrollment (Type 13) - matches Summary Report's renewal sales query
        $query = "
            SELECT 
                em.PK_ENROLLMENT_MASTER,
                em.ENROLLMENT_ID,
                em.ENROLLMENT_NAME,
                em.ENROLLMENT_DATE,
                em.ENROLLMENT_BY_ID,
                em.PK_USER_MASTER,
                SUM(es.FINAL_AMOUNT) AS TOTAL_AMOUNT,
                CONCAT(u.FIRST_NAME, ' ', u.LAST_NAME) AS CLIENT_NAME,
                CONCAT(ub.FIRST_NAME, ' ', ub.LAST_NAME) AS CLOSER_NAME
            FROM DOA_ENROLLMENT_SERVICE es
            LEFT JOIN DOA_SERVICE_CODE sc ON es.PK_SERVICE_CODE = sc.PK_SERVICE_CODE
            LEFT JOIN DOA_ENROLLMENT_MASTER em ON es.PK_ENROLLMENT_MASTER = em.PK_ENROLLMENT_MASTER
            LEFT JOIN " . $GLOBALS['master_database'] . ".DOA_USER_MASTER um ON em.PK_USER_MASTER = um.PK_USER_MASTER
            LEFT JOIN " . $GLOBALS['master_database'] . ".DOA_USERS u ON um.PK_USER = u.PK_USER
            LEFT JOIN " . $GLOBALS['master_database'] . ".DOA_USERS ub ON em.ENROLLMENT_BY_ID = ub.PK_USER
            WHERE em.PK_LOCATION IN (" . $location_id . ")
                AND em.PK_ENROLLMENT_TYPE = " . $type_id . "
               AND em.ENROLLMENT_ID NOT LIKE '%MISC%'
                AND sc.IS_GROUP = 0
                " . $date_condition . "
            GROUP BY em.PK_ENROLLMENT_MASTER
            ORDER BY em.ENROLLMENT_DATE DESC
        ";
    }

    $result = $db_account->Execute($query);

    while (!$result->EOF) {
        $enrollment_id = $result->fields['PK_ENROLLMENT_MASTER'];
        $payments = getEnrollmentPayments($db_account, $enrollment_id, $payment_date_condition);

        // Build enrollment display name
        $display_name = $result->fields['ENROLLMENT_NAME'];
        if ($is_misc) {
            $display_name = $result->fields['ENROLLMENT_NAME'] . ' (' . $result->fields['MISC_ID'] . ')';
        }

        $enrollments[] = [
            'id' => $result->fields['PK_ENROLLMENT_MASTER'],
            'enrollment_id' => $result->fields['ENROLLMENT_ID'],
            'name' => $display_name,
            'date' => $result->fields['ENROLLMENT_DATE'],
            'client' => $result->fields['CLIENT_NAME'],
            'closer' => $result->fields['CLOSER_NAME'],
            'total_amount' => $result->fields['TOTAL_AMOUNT'] ?? 0,
            'misc_id' => $result->fields['MISC_ID'],
            'payments' => $payments
        ];
        $result->MoveNext();
    }

    return $enrollments;
}

// Export to API functionality
if ($type === 'export') {
    $location_array = explode(",", $_SESSION['DEFAULT_LOCATION_ID']);
    if (count($location_array) > 1) {
        $error_message = "Please select any one location from top to export data.";
    } else {
        $access_token = getAccessToken();
        $authorization = "Authorization: Bearer " . $access_token;

        $user_data = $db->Execute("SELECT DISTINCT (DOA_USERS.PK_USER), DOA_USERS.FIRST_NAME, DOA_USERS.LAST_NAME FROM DOA_USERS 
            LEFT JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER 
            WHERE DOA_USER_LOCATION.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") 
            AND DOA_USERS.PK_USER = '$_SESSION[PK_USER]'");

        $enrollment_types = [
            13 => ['label' => '4+ Enrollment', 'is_misc' => false],
            16 => ['label' => 'MISC', 'is_misc' => true]
        ];

        $export_data = [
            'type' => 'enrollment_payment_details',
            'prepared_by' => $user_data->fields['FIRST_NAME'] . ' ' . $user_data->fields['LAST_NAME'],
            'week_number' => $week_number,
            'week_year' => $YEAR,
            'from_date' => $from_date,
            'to_date' => $to_date,
            'enrollments' => []
        ];

        foreach ($enrollment_types as $type_id => $type_info) {
            $enrollments = getEnrollmentsByType(
                $db_account,
                $type_id,
                $enrollment_date_condition,
                $_SESSION['DEFAULT_LOCATION_ID'],
                $payment_date_condition,
                $type_info['is_misc']
            );
            $export_data['enrollments'][$type_info['label']] = $enrollments;
        }

        $url = constant('ami_api_url') . '/api/v1/reports/enrollment-payment-details';
        $post_data = callArturMurrayApi($url, $export_data, $authorization, 'POST');
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
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<?php include 'layout/header_script.php'; ?>
<?php require_once('../includes/header.php'); ?>
<?php include 'layout/header.php'; ?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<link href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css" rel="stylesheet">

<style>
    a {
        color: #690C24;
        text-decoration: none;
        font-size: 14px;
    }

    .btn {
        border: 0;
        color: #fff;
        border-radius: 50rem;
        padding-left: 1.5rem;
        padding-right: 1.5rem;
    }

    input.form-control,
    select.form-control,
    textarea.form-control {
        border-radius: 0.375rem !important;
    }

    .section-header {
        background-color: #f8f9fa;
        padding: 15px;
        margin-top: 20px;
        margin-bottom: 10px;
        border-left: 4px solid #690C24;
        border-radius: 4px;
    }

    .section-header h5 {
        margin: 0;
        font-weight: bold;
        color: #690C24;
    }

    .enrollment-row {
        background-color: #f5f5f5;
    }

    .payment-row {
        background-color: #ffffff;
    }

    .payment-row td {
        padding: 5px 10px !important;
        font-size: 13px;
    }

    .table td,
    .table th {
        vertical-align: middle;
        padding: 8px;
    }

    .sub-total {
        background-color: #d1ecf1;
        font-weight: bold;
    }

    .grand-total {
        background-color: #cce5ff;
        font-weight: bold;
        font-size: 16px;
    }

    .text-muted-sm {
        font-size: 12px;
        color: #6c757d;
    }

    .badge-payment {
        background-color: #28a745;
        color: white;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 11px;
    }

    .misc-badge {
        background-color: #6f42c1;
        color: white;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 10px;
        margin-left: 5px;
    }

    .renewal-badge {
        background-color: #c62828;
        color: white;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 10px;
        margin-left: 5px;
    }
</style>

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">
        <div class="page-wrapper" style="padding-top: 1px !important;">
            <div class="container-fluid" style="padding: 10px 20px 0 20px; margin-top: 0px;">

                <div class="row page-titles">
                    <div class="col-md-5 align-self-center">
                        <h4 class="text-themecolor"><?= $title ?></h4>
                    </div>
                    <div class="col-md-7 align-self-center text-end">
                        <div class="d-flex justify-content-end align-items-center">
                            <ol class="breadcrumb justify-content-end">
                                <li class="breadcrumb-item active"><a href="reports.php">Reports</a></li>
                                <li class="breadcrumb-item active"><a href="business_reports.php">Business Reports</a></li>
                                <li class="breadcrumb-item active"><?= $title ?></li>
                            </ol>
                        </div>
                    </div>
                </div>

                <?php if ($type != 'export') { ?>
                    <div class="row">
                        <div class="col-12 align-self-center">
                            <div class="card">
                                <div class="card-body" style="padding-bottom: 0px !important;">
                                    <form class="form-material form-horizontal" action="" method="get" id="reportForm">
                                        <input type="hidden" name="start_date" id="start_date" value="<?= !empty($from_date) ? $from_date : '' ?>">
                                        <input type="hidden" name="end_date" id="end_date" value="<?= !empty($to_date) ? $to_date : '' ?>">
                                        <input type="hidden" name="week_number" id="week_number" value="<?= !empty($week_number) ? $week_number : '' ?>">
                                        <div class="row justify-content-start">
                                            <div class="col-2">
                                                <div class="form-group">
                                                    <input type="text" id="START_DATE" name="START_DATE" class="form-control datepicker-normal" placeholder="Start Date" value="<?= !empty($_GET['start_date']) ? date('m/d/Y', strtotime($_GET['start_date'])) : (!empty($from_date) ? date('m/d/Y', strtotime($from_date)) : '') ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-2">
                                                <div class="form-group">
                                                    <input type="text" id="END_DATE" name="END_DATE" class="form-control datepicker-normal" placeholder="End Date" value="<?= !empty($_GET['end_date']) ? date('m/d/Y', strtotime($_GET['end_date'])) : (!empty($to_date) ? date('m/d/Y', strtotime($to_date)) : '') ?>" required>
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
                    }
                } else {
                    $enrollment_types = [
                        13 => ['label' => '4+ Enrollment', 'color' => '#fce4ec', 'border' => '#c62828', 'bg' => '#f8bbd0', 'is_misc' => false],
                        16 => ['label' => 'MISC', 'color' => '#f3e5f5', 'border' => '#6a1b9a', 'bg' => '#e1bee7', 'is_misc' => true]
                    ];

                    $all_enrollments = [];
                    $grand_total_amount = 0;
                    $grand_payment_total = 0;
                    $grand_payment_count = 0;

                    foreach ($enrollment_types as $type_id => $type_info) {
                        $enrollments = getEnrollmentsByType(
                            $db_account,
                            $type_id,
                            $enrollment_date_condition,
                            $_SESSION['DEFAULT_LOCATION_ID'],
                            $payment_date_condition,
                            $type_info['is_misc']
                        );
                        $all_enrollments[$type_id] = $enrollments;
                    }

                    $has_data = false;
                    foreach ($all_enrollments as $enrollments) {
                        if (!empty($enrollments)) {
                            $has_data = true;
                            break;
                        }
                    }
                ?>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <div>
                                        <img src="../assets/images/background/doable_logo.png" style="margin-bottom:-35px; height: 60px; width: auto;">
                                        <h3 class="card-title" style="padding-bottom:15px; text-align: center; font-weight: bold"><?= $title ?></h3>
                                    </div>

                                    <div style="text-align: center; margin-bottom: 20px;">
                                        <strong><?= $concatenatedResults ?></strong><br>
                                        <span style="color: #666;">Period: <?= date('m/d/Y', strtotime($from_date)) ?> - <?= date('m/d/Y', strtotime($to_date)) ?></span>
                                        <?php if (!empty($week_number)) { ?>
                                            <span style="color: #666; margin-left: 15px;">| Week #<?= $week_number ?></span>
                                        <?php } ?>
                                    </div>

                                    <?php if (!$has_data) { ?>
                                        <div class="alert alert-info text-center" style="padding: 30px;">
                                            <i class="bi bi-info-circle" style="font-size: 24px;"></i>
                                            <p style="margin-top: 10px; font-size: 16px;">No 4+ enrollments or MISC found for the selected date range.</p>
                                        </div>
                                    <?php } else { ?>

                                        <?php foreach ($enrollment_types as $type_id => $type_info) {
                                            $enrollments = $all_enrollments[$type_id];
                                            if (empty($enrollments)) {
                                                continue;
                                            }
                                            $section_total_amount = 0;
                                            $section_payment_total = 0;
                                            $section_payment_count = 0;

                                            foreach ($enrollments as $enrollment) {
                                                $section_total_amount += $enrollment['total_amount'];
                                                $section_payment_count += count($enrollment['payments']);
                                                foreach ($enrollment['payments'] as $payment) {
                                                    $section_payment_total += $payment['total_amount'];
                                                }
                                            }
                                        ?>

                                            <div class="section-header" style="background-color: <?= $type_info['color'] ?>; border-left-color: <?= $type_info['border'] ?>;">
                                                <h5>
                                                    <?= $type_info['label'] ?>
                                                    <span style="font-weight: normal; font-size: 14px; color: #555;">
                                                        (<?= count($enrollments) ?> enrollments |
                                                        <?= $section_payment_count ?> payments |
                                                        Sales: $<?= number_format($section_total_amount, 2) ?> |
                                                        Payments: $<?= number_format($section_payment_total, 2) ?>)
                                                    </span>
                                                </h5>
                                            </div>

                                            <div class="table-responsive">
                                                <table class="table table-bordered" style="margin-bottom: 15px;">
                                                    <thead style="background-color: <?= $type_info['bg'] ?>;">
                                                        <tr>
                                                            <th style="width: 15%; text-align: center;">Enrollment ID</th>
                                                            <th style="width: 22%; text-align: center;">Enrollment Name</th>
                                                            <th style="width: 10%; text-align: center;">Date</th>
                                                            <th style="width: 15%; text-align: center;">Client</th>
                                                            <th style="width: 12%; text-align: center;">Closer</th>
                                                            <th style="width: 13%; text-align: center;">Total Amount</th>
                                                            <th style="width: 13%; text-align: center;">Payment Details</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($enrollments as $enrollment) {
                                                            $enrollment_total = $enrollment['total_amount'] ?? 0;
                                                            $payment_count = count($enrollment['payments']);
                                                            $enrollment_payment_total = 0;
                                                            foreach ($enrollment['payments'] as $payment) {
                                                                $enrollment_payment_total += $payment['total_amount'];
                                                            }
                                                        ?>
                                                            <tr class="enrollment-row" style="background-color: <?= $type_info['color'] ?>;">
                                                                <td style="text-align: center; font-weight: bold;">
                                                                    #<?= $enrollment['enrollment_id'] ?>
                                                                    <?php if (!empty($enrollment['misc_id']) && strpos($enrollment['misc_id'], 'MISC') !== false) { ?>
                                                                        <span class="misc-badge" style="font-size: 9px;">MISC</span>
                                                                    <?php } ?>
                                                                </td>
                                                                <td style="text-align: left;">
                                                                    <?= htmlspecialchars($enrollment['name'] ?? 'N/A') ?>
                                                                </td>
                                                                <td style="text-align: center;">
                                                                    <?= date('m/d/Y', strtotime($enrollment['date'])) ?>
                                                                </td>
                                                                <td style="text-align: center;">
                                                                    <?= htmlspecialchars($enrollment['client'] ?? '-') ?>
                                                                </td>
                                                                <td style="text-align: center;">
                                                                    <?= htmlspecialchars($enrollment['closer'] ?? '-') ?>
                                                                </td>
                                                                <td style="text-align: right; font-weight: bold;">
                                                                    $<?= number_format($enrollment_total, 2) ?>
                                                                </td>
                                                                <td style="text-align: center;">
                                                                    <span class="badge-payment">
                                                                        <?= $payment_count ?> payments
                                                                    </span>
                                                                    <span style="font-size: 12px; color: #555; display: block;">
                                                                        $<?= number_format($enrollment_payment_total, 2) ?>
                                                                    </span>
                                                                </td>
                                                            </tr>

                                                            <?php if (!empty($enrollment['payments'])) { ?>
                                                                <?php foreach ($enrollment['payments'] as $payment) { ?>
                                                                    <tr class="payment-row">
                                                                        <td style="text-align: center; padding-left: 30px !important; color: #6c757d; font-size: 12px;">
                                                                            <i class="bi bi-arrow-right-short"></i>
                                                                        </td>
                                                                        <td style="text-align: left; color: #6c757d; font-size: 13px;">
                                                                            <i class="bi bi-credit-card payment-icon"></i> Payment
                                                                        </td>
                                                                        <td style="text-align: center; font-size: 13px;">
                                                                            <?= date('m/d/Y', strtotime($payment['date'])) ?>
                                                                        </td>
                                                                        <td style="text-align: center; font-size: 13px;">
                                                                            <?= $payment['receipt_number'] ? '#' . $payment['receipt_number'] : '-' ?>
                                                                        </td>
                                                                        <td style="text-align: center; font-size: 12px; color: #555;">
                                                                            <?= $payment['payment_type'] ?? $payment['type'] ?? 'Unknown' ?>
                                                                        </td>
                                                                        <td style="text-align: right; font-size: 13px; font-weight: 500;">
                                                                            $<?= number_format($payment['total_amount'], 2) ?>
                                                                            <?php if ($payment['tip'] > 0) { ?>
                                                                                <span class="text-muted-sm" style="display: block; font-weight: normal;">
                                                                                    (Tip: $<?= number_format($payment['tip'], 2) ?>)
                                                                                </span>
                                                                            <?php } ?>
                                                                        </td>
                                                                        <td style="text-align: center;"></td>
                                                                    </tr>
                                                                <?php } ?>
                                                            <?php } else { ?>
                                                                <tr class="payment-row">
                                                                    <td colspan="7" style="text-align: center; color: #999; font-style: italic; padding: 8px;">
                                                                        <i class="bi bi-info-circle"></i> No payments recorded for this enrollment
                                                                    </td>
                                                                </tr>
                                                            <?php } ?>
                                                        <?php } ?>

                                                        <tr class="sub-total">
                                                            <td colspan="5" style="text-align: right; font-weight: bold;">
                                                                <?= $type_info['label'] ?> Sub-total:
                                                            </td>
                                                            <td style="text-align: right; font-weight: bold;">
                                                                $<?= number_format($section_total_amount, 2) ?>
                                                            </td>
                                                            <td style="text-align: center; font-weight: bold;">
                                                                <?= $section_payment_count ?> payments
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php
                                            $grand_total_amount += $section_total_amount;
                                            $grand_payment_total += $section_payment_total;
                                            $grand_payment_count += $section_payment_count;
                                        } ?>

                                        <!-- Grand Total -->
                                        <div style="margin-top: 25px; padding: 15px 20px; background-color: #cce5ff; border: 2px solid #004085; border-radius: 5px;">
                                            <table class="table table-bordered" style="margin: 0; background: transparent;">
                                                <tr class="grand-total" style="background: transparent;">
                                                    <td style="width: 30%; text-align: right; border: none; font-size: 18px; font-weight: bold;">
                                                        GRAND TOTALS:
                                                    </td>
                                                    <td style="width: 23%; text-align: center; border: none; font-size: 15px;">
                                                        <div style="font-weight: bold; color: #333;">Total Payments</div>
                                                        <div style="font-size: 20px; color: #004085; font-weight: bold;"><?= $grand_payment_count ?></div>
                                                    </td>
                                                    <td style="width: 23%; text-align: center; border: none; font-size: 15px;">
                                                        <div style="font-weight: bold; color: #333;">Total Sales Amount</div>
                                                        <div style="font-size: 20px; color: #004085; font-weight: bold;">$<?= number_format($grand_total_amount, 2) ?></div>
                                                    </td>
                                                    <td style="width: 24%; text-align: center; border: none; font-size: 15px;">
                                                        <div style="font-weight: bold; color: #333;">Total Payments Amount</div>
                                                        <div style="font-size: 20px; color: #004085; font-weight: bold;">$<?= number_format($grand_payment_total, 2) ?></div>
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>

                                    <?php } ?>
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
        $('.datepicker-normal').datepicker({
            format: 'mm/dd/yyyy',
            autoclose: true,
            todayHighlight: true
        });

        $('#reportForm').on('submit', function(e) {
            var startDate = $('#START_DATE').val();
            var endDate = $('#END_DATE').val();

            if (!startDate || !endDate) {
                alert('Please select both start date and end date.');
                e.preventDefault();
                return false;
            }

            var start = new Date(startDate);
            var end = new Date(endDate);

            if (start > end) {
                alert('Start date cannot be after end date.');
                e.preventDefault();
                return false;
            }

            function formatDate(dateStr) {
                var parts = dateStr.split('/');
                return parts[2] + '-' + ('0' + parts[0]).slice(-2) + '-' + ('0' + parts[1]).slice(-2);
            }

            $('#start_date').val(formatDate(startDate));
            $('#end_date').val(formatDate(endDate));

            var start = new Date(startDate);
            var weekNumber = $.datepicker.iso8601Week(start);
            $('#week_number').val(weekNumber);

            return true;
        });

        $('input[name="view"], input[name="generate_pdf"], input[name="generate_excel"]').on('click', function() {
            var startDate = $('#START_DATE').val();
            var endDate = $('#END_DATE').val();

            function formatDate(dateStr) {
                var parts = dateStr.split('/');
                return parts[2] + '-' + ('0' + parts[0]).slice(-2) + '-' + ('0' + parts[1]).slice(-2);
            }

            $('#start_date').val(formatDate(startDate));
            $('#end_date').val(formatDate(endDate));

            var start = new Date(startDate);
            var weekNumber = $.datepicker.iso8601Week(start);
            $('#week_number').val(weekNumber);
        });
    });
</script>