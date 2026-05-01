<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;

$title = "TIPS REPORT";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

$type = $_GET['type'] ?? '';
$include_no_provider = isset($_GET['include_no_provider']) ? $_GET['include_no_provider'] : 0;

$from_date = !empty($_GET['start_date']) ? date('Y-m-d', strtotime($_GET['start_date'])) : date('Y-m-01');
$to_date = !empty($_GET['end_date']) ? date('Y-m-d', strtotime($_GET['end_date'])) : date('Y-m-d');

// Fix for handling multiple service provider IDs
if (isset($_GET['PK_USER']) && is_array($_GET['PK_USER'])) {
    $service_provider_id = implode(',', $_GET['PK_USER']);
} else {
    $service_provider_id = $_GET['service_provider_id'] ?? '';
}

$account_data = $db->Execute("SELECT * FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
$user_data = $db->Execute("SELECT * FROM DOA_USERS WHERE PK_USER = '$_SESSION[PK_USER]'");
$business_name = $account_data->RecordCount() > 0 ? $account_data->fields['BUSINESS_NAME'] : '';
if (preg_match("/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/", $business_name)) {
    $business_name = '';
} else {
    $business_name = '' . $business_name;
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
    $concatenatedResults .= $result;
    if ($key < $totalResults - 1) {
        $concatenatedResults .= ", ";
    }
}

if (!empty($_GET['START_DATE'])) {
    $type = isset($_GET['view']) ? 'view' : 'export';
    $generate_pdf = isset($_GET['generate_pdf']) ? 1 : 0;
    $generate_excel = isset($_GET['generate_excel']) ? 1 : 0;
    $report_name = 'tips_report';
    $START_DATE = date('Y-m-d', strtotime($_GET['START_DATE']));
    $END_DATE = date('Y-m-d', strtotime($_GET['END_DATE']));
    $PK_USER = empty($_GET['PK_USER']) ? 0 : $_GET['PK_USER'];
    $include_no_provider = isset($_GET['include_no_provider']) ? 1 : 0;

    if ($generate_pdf === 1) {
        header('location:generate_report_pdf.php?start_date=' . $START_DATE . '&end_date=' . $END_DATE . '&report_type=' . $report_name . '&PK_USER=' . implode(',', $PK_USER));
    } elseif ($generate_excel === 1) {
        header('location:excel_' . $report_name . '.php?start_date=' . $START_DATE . '&end_date=' . $END_DATE . '&report_type=' . $report_name . '&PK_USER=' . implode(',', $PK_USER));
    } else {
        header('location:tips_report.php?start_date=' . $START_DATE . '&end_date=' . $END_DATE . '&type=' . $type . '&service_provider_id=' . implode(',', $PK_USER) . '&include_no_provider=' . $include_no_provider);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<?php include 'layout/header_script.php'; ?>

<?php require_once('../includes/header.php'); ?>
<?php include 'layout/header.php'; ?>

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">

        <div class="page-wrapper" style="padding-top: 0px !important;">

            <div class="container-fluid body_content" style="margin-top: 0px;">
                <div class="row page-titles">
                    <div class="col-md-5 align-self-center">
                        <h4 class="text-themecolor"><?= $title ?></h4>
                    </div>
                    <div class="col-md-7 align-self-center text-end">
                        <div class="d-flex justify-content-end align-items-center">
                            <ol class="breadcrumb justify-content-end">
                                <li class="breadcrumb-item active"><a href="cash_report.php">Reports</a></li>
                                <li class="breadcrumb-item active"><?= $title ?></a></li>
                            </ol>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body" style="padding-bottom: 0px !important;">
                                <form class="form-material form-horizontal" action="" method="get" id="reportForm">
                                    <input type="hidden" name="start_date" id="start_date">
                                    <input type="hidden" name="end_date" id="end_date">
                                    <div class="row">
                                        <div class="col-3">
                                            <div id="location" style="width: 100%;">
                                                <select class="multi_select_service_provider" multiple id="service_provider_select" name="PK_USER[]">
                                                    <?php
                                                    $selected_provider_ids = [];
                                                    if (!empty($service_provider_id) && $service_provider_id != '0') {
                                                        $selected_provider_ids = explode(',', $service_provider_id);
                                                    }

                                                    $query = "SELECT DISTINCT DU.PK_USER, CONCAT(DU.FIRST_NAME, ' ', DU.LAST_NAME) AS NAME 
                      FROM DOA_USERS DU
                      INNER JOIN DOA_USER_ROLES DUR ON DU.PK_USER = DUR.PK_USER 
                      INNER JOIN DOA_USER_LOCATION DUL ON DU.PK_USER = DUL.PK_USER 
                      WHERE DU.ACTIVE = 1 
                      AND DUR.PK_ROLES = 5 
                      AND DUL.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") 
                      AND DU.PK_ACCOUNT_MASTER = '" . $_SESSION['PK_ACCOUNT_MASTER'] . "'
                      ORDER BY DU.FIRST_NAME, DU.LAST_NAME";

                                                    $row = $db->Execute($query);

                                                    if ($row && $row->RecordCount() > 0) {
                                                        while (!$row->EOF) {
                                                            $user_id = $row->fields['PK_USER'];
                                                            $selected = in_array($user_id, $selected_provider_ids) ? 'selected' : '';
                                                    ?>
                                                            <option value="<?php echo $user_id; ?>" <?= $selected ?>>
                                                                <?= htmlspecialchars($row->fields['NAME']) ?>
                                                            </option>
                                                    <?php
                                                            $row->MoveNext();
                                                        }
                                                    } else {
                                                        echo '<option value="">No Service Providers Found</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-2">
                                            <div class="form-group">
                                                <input type="text" id="START_DATE" name="START_DATE" class="form-control datepicker-normal" placeholder="Start Date" value="<?= !empty($_GET['start_date']) ? date('m/d/Y', strtotime($_GET['start_date'])) : date('m/d/Y') ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-2">
                                            <div class="form-group">
                                                <input type="text" id="END_DATE" name="END_DATE" class="form-control datepicker-normal" placeholder="End Date" value="<?= !empty($_GET['end_date']) ? date('m/d/Y', strtotime($_GET['end_date'])) : date('m/d/Y') ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <?php if (in_array('Reports Create', $PERMISSION_ARRAY)) { ?>
                                                <input type="submit" name="view" value="View Report" class="btn btn-info" style="background-color: #39B54A !important;">
                                                <input type="submit" name="generate_excel" value="Export to Excel" class="btn btn-info" style="background-color: #39B54A !important;">
                                            <?php } ?>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($type === 'export') { ?>
                    <h3>Data export to Arthur Murray API Successfully</h3>
                <?php } else if ($type === 'view' || !empty($_GET['start_date'])) { ?>
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row" style="margin-bottom: 20px;">
                                        <div class="col-md-2 text-left">
                                            <img src="../assets/images/background/doable_logo.png" style="margin-bottom:-35px; height: 60px; width: auto;">
                                        </div>
                                        <div class="col-md-3 text-center">
                                            <h3 class="card-title" style="padding-bottom:15px; text-align: center; font-weight: bold"><?= $title ?></h3>
                                        </div>
                                        <div class="col-md-4 text-center">
                                            <h5 class="card-title" style="padding-bottom:15px; text-align: center; font-weight: bold"><?= $concatenatedResults ?></h5>
                                        </div>
                                        <div class="col-md-3 text-center">
                                            <h6 class="card-title" style="padding-bottom:15px; text-align: center; font-weight: bold">(<?= date('m/d/Y', strtotime($from_date)) ?> - <?= date('m/d/Y', strtotime($to_date)) ?>)</h6>
                                        </div>
                                    </div>

                                    <?php
                                    // Enrollment types mapping
                                    $enrollment_types = [
                                        5 => 'Pre Original',
                                        2 => 'Original',
                                        9 => 'Extension',
                                        13 => 'Renewal'
                                    ];

                                    // Query to get tips data with client and appointment information
                                    $tips_query = "
                                        SELECT 
                                            et.PK_ENROLLMENT_TIP,
                                            et.PK_ENROLLMENT_MASTER,
                                            et.PK_ENROLLMENT_PAYMENT,
                                            et.TIP_PERCENTAGE,
                                            et.TIP_AMOUNT,
                                            et.PK_USER AS PROVIDER_ID,
                                            et.CREATED_ON,
                                            CONCAT(et.CREATED_BY) AS CREATED_BY,
                                            CONCAT(du.FIRST_NAME, ' ', du.LAST_NAME) AS PROVIDER_NAME,
                                            em.PK_ENROLLMENT_TYPE,
                                            em.ENROLLMENT_DATE,
                                            em.PK_USER_MASTER,
                                            cd.PARTNER_FIRST_NAME,
                                            cd.PARTNER_LAST_NAME,
                                            CONCAT(us.FIRST_NAME, ' ', us.LAST_NAME) AS CUSTOMER_NAME,
                                            ep.AMOUNT AS PAYMENT_AMOUNT,
                                            ep.PK_PAYMENT_TYPE,
                                            ep.PAYMENT_DATE,
                                            GROUP_CONCAT(DISTINCT DATE(am.DATE) ORDER BY am.DATE SEPARATOR ', ') AS APPOINTMENT_DATES,
                                            GROUP_CONCAT(DISTINCT CONCAT(DATE(am.DATE), ' - ', sm.SERVICE_NAME) ORDER BY am.DATE SEPARATOR '; ') AS APPOINTMENT_DETAILS
                                        FROM DOA_ENROLLMENT_TIP et
                                        INNER JOIN DOA_ENROLLMENT_MASTER em ON et.PK_ENROLLMENT_MASTER = em.PK_ENROLLMENT_MASTER
                                        INNER JOIN DOA_ENROLLMENT_PAYMENT ep ON et.PK_ENROLLMENT_PAYMENT = ep.PK_ENROLLMENT_PAYMENT
                                        LEFT JOIN DOA_USERS du ON et.PK_USER = du.PK_USER
                                        LEFT JOIN DOA_CUSTOMER_DETAILS cd ON em.PK_USER_MASTER = cd.PK_USER_MASTER
                                        LEFT JOIN DOA_MASTER.DOA_USER_MASTER dc ON em.PK_USER_MASTER = dc.PK_USER_MASTER
                                        LEFT JOIN DOA_MASTER.DOA_USERS us ON us.PK_USER = dc.PK_USER
                                        LEFT JOIN DOA_APPOINTMENT_MASTER am ON em.PK_ENROLLMENT_MASTER = am.PK_ENROLLMENT_MASTER
                                        LEFT JOIN DOA_SERVICE_MASTER sm ON am.PK_SERVICE_MASTER = sm.PK_SERVICE_MASTER
                                        WHERE DATE(et.CREATED_ON) BETWEEN '$from_date' AND '$to_date'
                                        
                                    ";

                                    echo "SELECT 
                                            et.PK_ENROLLMENT_TIP,
                                            et.PK_ENROLLMENT_MASTER,
                                            et.PK_ENROLLMENT_PAYMENT,
                                            et.TIP_PERCENTAGE,
                                            et.TIP_AMOUNT,
                                            et.PK_USER AS PROVIDER_ID,
                                            et.CREATED_ON,
                                            CONCAT(et.CREATED_BY) AS CREATED_BY,
                                            CONCAT(du.FIRST_NAME, ' ', du.LAST_NAME) AS PROVIDER_NAME,
                                            em.PK_ENROLLMENT_TYPE,
                                            em.ENROLLMENT_DATE,
                                            em.PK_USER_MASTER,
                                            cd.PARTNER_FIRST_NAME,
                                            cd.PARTNER_LAST_NAME,
                                            CONCAT(us.FIRST_NAME, ' ', us.LAST_NAME) AS CUSTOMER_NAME,
                                            ep.TOTAL_AMOUNT AS PAYMENT_AMOUNT,
                                            ep.PAYMENT_METHOD,
                                            ep.PAYMENT_DATE,
                                            GROUP_CONCAT(DISTINCT DATE(am.DATE) ORDER BY am.DATE SEPARATOR ', ') AS APPOINTMENT_DATES,
                                            GROUP_CONCAT(DISTINCT CONCAT(DATE(am.DATE), ' - ', sm.SERVICE_NAME) ORDER BY am.DATE SEPARATOR '; ') AS APPOINTMENT_DETAILS
                                        FROM DOA_ENROLLMENT_TIP et
                                        INNER JOIN DOA_ENROLLMENT_MASTER em ON et.PK_ENROLLMENT_MASTER = em.PK_ENROLLMENT_MASTER
                                        INNER JOIN DOA_ENROLLMENT_PAYMENT ep ON et.PK_ENROLLMENT_PAYMENT = ep.PK_ENROLLMENT_PAYMENT
                                        LEFT JOIN DOA_USERS du ON et.PK_USER = du.PK_USER
                                        LEFT JOIN DOA_CUSTOMER_DETAILS cd ON em.PK_USER_MASTER = cd.PK_USER_MASTER
                                        LEFT JOIN DOA_MASTER.DOA_USER_MASTER dc ON em.PK_USER_MASTER = dc.PK_USER_MASTER
                                        LEFT JOIN DOA_MASTER.DOA_USERS us ON us.PK_USER = dc.PK_USER
                                        LEFT JOIN DOA_APPOINTMENT_MASTER am ON em.PK_ENROLLMENT_MASTER = am.PK_ENROLLMENT_MASTER
                                        LEFT JOIN DOA_SERVICE_MASTER sm ON am.PK_SERVICE_MASTER = sm.PK_SERVICE_MASTER
                                        WHERE DATE(et.CREATED_ON) BETWEEN '$from_date' AND '$to_date'
                                        AND em.PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'";

                                    // Add service provider filter
                                    if (!empty($service_provider_id) && $service_provider_id != '0') {
                                        $provider_ids = explode(',', $service_provider_id);
                                        $provider_placeholders = implode(',', array_fill(0, count($provider_ids), '?'));
                                        $tips_query .= " AND et.PK_USER IN ($provider_placeholders)";
                                    }

                                    $tips_query .= " GROUP BY et.PK_ENROLLMENT_TIP, et.PK_ENROLLMENT_MASTER, et.PK_ENROLLMENT_PAYMENT, et.TIP_PERCENTAGE, et.TIP_AMOUNT, et.PK_USER, et.CREATED_ON, PROVIDER_NAME, em.PK_ENROLLMENT_TYPE, em.ENROLLMENT_DATE, em.PK_USER_MASTER, cd.PARTNER_FIRST_NAME, cd.PARTNER_LAST_NAME, CUSTOMER_NAME, ep.TOTAL_AMOUNT, ep.PAYMENT_METHOD, ep.PAYMENT_DATE
                                    ORDER BY et.CREATED_ON DESC";

                                    // Execute query with parameters if needed
                                    if (!empty($service_provider_id) && $service_provider_id != '0') {
                                        $provider_ids = explode(',', $service_provider_id);
                                        $tips_result = $db_account->Execute($tips_query, $provider_ids);
                                    } else {
                                        $tips_result = $db_account->Execute($tips_query);
                                    }

                                    // Initialize summary variables
                                    $total_tips = 0;
                                    $total_tips_count = 0;
                                    $provider_summary = [];
                                    $enrollment_type_summary = [];
                                    $payment_method_summary = [];

                                    if ($tips_result && $tips_result->RecordCount() > 0) {
                                        $tips_data = [];
                                        while (!$tips_result->EOF) {
                                            $tip_amount = floatval($tips_result->fields['TIP_AMOUNT']);
                                            $provider_id = $tips_result->fields['PROVIDER_ID'];
                                            $provider_name = $tips_result->fields['PROVIDER_NAME'] ?: 'Unknown';
                                            $enrollment_type_id = $tips_result->fields['PK_ENROLLMENT_TYPE'];
                                            $enrollment_type = $enrollment_types[$enrollment_type_id] ?? 'Unknown';
                                            $payment_method = $tips_result->fields['PAYMENT_METHOD'] ?: 'Unknown';

                                            $tip_record = [
                                                'pk_enrollment_tip' => $tips_result->fields['PK_ENROLLMENT_TIP'],
                                                'provider_name' => $provider_name,
                                                'customer_name' => $tips_result->fields['CUSTOMER_NAME'] ?: ($tips_result->fields['PARTNER_FIRST_NAME'] ? trim($tips_result->fields['PARTNER_FIRST_NAME'] . ' ' . $tips_result->fields['PARTNER_LAST_NAME']) : 'Unknown'),
                                                'enrollment_type' => $enrollment_type,
                                                'enrollment_date' => $tips_result->fields['ENROLLMENT_DATE'],
                                                'tip_amount' => $tip_amount,
                                                'tip_percentage' => $tips_result->fields['TIP_PERCENTAGE'],
                                                'payment_amount' => $tips_result->fields['PAYMENT_AMOUNT'],
                                                'payment_method' => $payment_method,
                                                'payment_date' => $tips_result->fields['PAYMENT_DATE'],
                                                'created_on' => $tips_result->fields['CREATED_ON'],
                                                'appointment_dates' => $tips_result->fields['APPOINTMENT_DATES'],
                                                'appointment_details' => $tips_result->fields['APPOINTMENT_DETAILS']
                                            ];

                                            $tips_data[] = $tip_record;

                                            // Calculate totals
                                            $total_tips += $tip_amount;
                                            $total_tips_count++;

                                            // Provider summary
                                            if (!isset($provider_summary[$provider_name])) {
                                                $provider_summary[$provider_name] = ['count' => 0, 'total' => 0];
                                            }
                                            $provider_summary[$provider_name]['count']++;
                                            $provider_summary[$provider_name]['total'] += $tip_amount;

                                            // Enrollment type summary
                                            if (!isset($enrollment_type_summary[$enrollment_type])) {
                                                $enrollment_type_summary[$enrollment_type] = ['count' => 0, 'total' => 0];
                                            }
                                            $enrollment_type_summary[$enrollment_type]['count']++;
                                            $enrollment_type_summary[$enrollment_type]['total'] += $tip_amount;

                                            // Payment method summary
                                            if (!isset($payment_method_summary[$payment_method])) {
                                                $payment_method_summary[$payment_method] = ['count' => 0, 'total' => 0];
                                            }
                                            $payment_method_summary[$payment_method]['count']++;
                                            $payment_method_summary[$payment_method]['total'] += $tip_amount;

                                            $tips_result->MoveNext();
                                        }
                                    ?>

                                        <!-- Summary Cards -->
                                        <div class="row mb-4">
                                            <div class="col-md-3">
                                                <div class="card text-white bg-info">
                                                    <div class="card-body">
                                                        <h5 class="card-title">Total Tips</h5>
                                                        <h3 class="card-text">$<?= number_format($total_tips, 2) ?></h3>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="card text-white bg-success">
                                                    <div class="card-body">
                                                        <h5 class="card-title">Number of Tips</h5>
                                                        <h3 class="card-text"><?= $total_tips_count ?></h3>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="card text-white bg-warning">
                                                    <div class="card-body">
                                                        <h5 class="card-title">Average Tip Amount</h5>
                                                        <h3 class="card-text">$<?= $total_tips_count > 0 ? number_format($total_tips / $total_tips_count, 2) : '0.00' ?></h3>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Provider Summary Table -->
                                        <div class="table-responsive mt-4">
                                            <h5>Summary by Service Provider</h5>
                                            <table class="table table-bordered table-sm" style="background-color: #d4edda;">
                                                <thead>
                                                    <tr>
                                                        <th style="text-align: center">Service Provider</th>
                                                        <th style="text-align: center">Number of Tips</th>
                                                        <th style="text-align: center">Total Tip Amount</th>
                                                        <th style="text-align: center">Average Tip</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($provider_summary as $provider => $data): ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars($provider) ?></td>
                                                            <td style="text-align: center"><?= $data['count'] ?></td>
                                                            <td style="text-align: right">$<?= number_format($data['total'], 2) ?></td>
                                                            <td style="text-align: right">$<?= number_format($data['total'] / $data['count'], 2) ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                    <tr style="background-color: #f8f9fa; font-weight: bold;">
                                                        <td>TOTAL</td>
                                                        <td style="text-align: center"><?= $total_tips_count ?></td>
                                                        <td style="text-align: right">$<?= number_format($total_tips, 2) ?></td>
                                                        <td style="text-align: right">$<?= number_format($total_tips / $total_tips_count, 2) ?></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>

                                        <!-- Enrollment Type Summary Table -->
                                        <div class="table-responsive mt-4">
                                            <h5>Summary by Enrollment Type</h5>
                                            <table class="table table-bordered table-sm" style="background-color: #d4edda;">
                                                <thead>
                                                    <tr>
                                                        <th style="text-align: center">Enrollment Type</th>
                                                        <th style="text-align: center">Number of Tips</th>
                                                        <th style="text-align: center">Total Tip Amount</th>
                                                        <th style="text-align: center">Average Tip</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($enrollment_type_summary as $type => $data): ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars($type) ?></td>
                                                            <td style="text-align: center"><?= $data['count'] ?></td>
                                                            <td style="text-align: right">$<?= number_format($data['total'], 2) ?></td>
                                                            <td style="text-align: right">$<?= number_format($data['total'] / $data['count'], 2) ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>

                                        <!-- Detailed Tips Table -->
                                        <div class="table-responsive mt-4">
                                            <h5>Detailed Tips Report</h5>
                                            <table class="table table-bordered table-striped" id="tipsTable">
                                                <thead>
                                                    <tr>
                                                        <th style="text-align: center">Date</th>
                                                        <th style="text-align: center">Service Provider</th>
                                                        <th style="text-align: center">Client Name</th>
                                                        <th style="text-align: center">Enrollment Type</th>
                                                        <th style="text-align: center">Tip Amount</th>
                                                        <th style="text-align: center">Tip %</th>
                                                        <th style="text-align: center">Payment Amount</th>
                                                        <th style="text-align: center">Payment Method</th>
                                                        <th style="text-align: center">Appointments</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($tips_data as $tip): ?>
                                                        <tr>
                                                            <td style="text-align: center"><?= date('m/d/Y', strtotime($tip['created_on'])) ?></td>
                                                            <td><?= htmlspecialchars($tip['provider_name']) ?></td>
                                                            <td><?= htmlspecialchars($tip['customer_name']) ?></td>
                                                            <td style="text-align: center"><?= htmlspecialchars($tip['enrollment_type']) ?></td>
                                                            <td style="text-align: right; font-weight: bold; color: green;">$<?= number_format($tip['tip_amount'], 2) ?></td>
                                                            <td style="text-align: center"><?= $tip['tip_percentage'] ?>%</td>
                                                            <td style="text-align: right">$<?= number_format($tip['payment_amount'], 2) ?></td>
                                                            <td style="text-align: center"><?= htmlspecialchars($tip['payment_method']) ?></td>
                                                            <td style="font-size: 11px;">
                                                                <?php
                                                                $appointments = $tip['appointment_details'];
                                                                if (!empty($appointments)) {
                                                                    $appt_array = explode('; ', $appointments);
                                                                    echo '<ul style="margin: 0; padding-left: 15px;">';
                                                                    foreach (array_slice($appt_array, 0, 3) as $appt) {
                                                                        echo '<li>' . htmlspecialchars($appt) . '</li>';
                                                                    }
                                                                    if (count($appt_array) > 3) {
                                                                        echo '<li>... and ' . (count($appt_array) - 3) . ' more</li>';
                                                                    }
                                                                    echo '</ul>';
                                                                } else {
                                                                    echo 'No appointments found';
                                                                }
                                                                ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                                <tfoot style="background-color: #f8f9fa; font-weight: bold;">
                                                    <tr>
                                                        <td colspan="4" style="text-align: right">TOTAL:</td>
                                                        <td style="text-align: right">$<?= number_format($total_tips, 2) ?></td>
                                                        <td colspan="4"></td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>

                                    <?php
                                    } else {
                                    ?>
                                        <div class="alert alert-info mt-4">
                                            <h5>No tips found for the selected criteria.</h5>
                                            <p>Please try adjusting your date range or service provider selection.</p>
                                        </div>
                                    <?php
                                    }
                                    ?>
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
        $('.multi_select_service_provider').SumoSelect({
            placeholder: 'Select Service Provider',
            selectAll: true,
            triggerChangeCombined: true,
            search: true,
            searchText: 'Search Service Providers...'
        });

        // Initialize datepickers
        $('.datepicker-normal').datepicker({
            format: 'mm/dd/yyyy',
            autoclose: true,
            todayHighlight: true
        });

        // Initialize DataTable for better sorting and searching
        if ($('#tipsTable').length && $('#tipsTable tbody tr').length > 0) {
            $('#tipsTable').DataTable({
                "pageLength": 25,
                "order": [
                    [0, "desc"]
                ],
                "language": {
                    "search": "Search:",
                    "lengthMenu": "Show _MENU_ entries per page",
                    "info": "Showing _START_ to _END_ of _TOTAL_ entries"
                }
            });
        }

        // Form validation
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

            return true;
        });
    });
</script>

<style>
    .table-responsive {
        overflow-x: auto;
    }

    .table-bordered td,
    .table-bordered th {
        border: 1px solid #dee2e6;
        vertical-align: middle;
    }

    .table-striped tbody tr:nth-of-type(odd) {
        background-color: rgba(0, 0, 0, .02);
    }

    .card-text {
        margin-bottom: 0;
    }

    .text-green {
        color: #28a745;
    }
</style>