<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;

$title = "SALES BY ENROLLMENT REPORT";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

$type = $_GET['type'];
$include_no_provider = isset($_GET['include_no_provider']) ? $_GET['include_no_provider'] : 0;

$from_date = date('Y-m-d', strtotime($_GET['start_date']));
$to_date = date('Y-m-d', strtotime($_GET['end_date']));
$date_condition = "ENROLLMENT_DATE BETWEEN '" . $from_date . "' AND '" . $to_date . "'";

$service_provider_id = $_GET['service_provider_id'];

// Rest of your existing code for business name, location, etc...
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
                                <li class="breadcrumb-item active"><a href="cash_report.php">Reports</a></li>
                                <li class="breadcrumb-item active"><?= $title ?></a></li>
                            </ol>
                        </div>
                    </div>
                </div>

                <?php if ($type === 'export') { ?>
                    <h3>Data export to Arthur Murray API Successfully</h3>
                <?php } else { ?>
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row" style="margin-bottom: 20px;">
                                        <div class="col-md-2 text-left">
                                            <img src="../assets/images/background/doable_logo.png" style="margin-bottom:-35px; height: 60px; width: auto;">
                                        </div>
                                        <div class="col-md-2 text-center">
                                            <h3 class="card-title" style="padding-bottom:15px; text-align: center; font-weight: bold"><?= $title ?></h3>
                                        </div>
                                        <div class="col-md-5 text-center">
                                            <h5 class="card-title" style="padding-bottom:15px; text-align: center; font-weight: bold"><?= $business_name . " (" . $concatenatedResults . ")" ?></h5>
                                        </div>
                                        <div class="col-md-3 text-center">
                                            <h6 class="card-title" style="padding-bottom:15px; text-align: center; font-weight: bold">(<?= date('m/d/Y', strtotime($from_date)) ?> - <?= date('m/d/Y', strtotime($to_date)) ?>)</h6>
                                        </div>
                                    </div>

                                    <?php
                                    // Get all selected service providers
                                    $each_service_provider = $db_account->Execute("SELECT DISTINCT DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID 
    FROM DOA_ENROLLMENT_MASTER 
    INNER JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER 
    WHERE DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") 
    AND DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN ($service_provider_id) 
    GROUP BY SERVICE_PROVIDER_ID");

                                    $all_providers_data = [];
                                    $all_enrollments_with_providers = []; // Track which enrollments have which providers
                                    $enrollment_units_data = []; // Store total units per enrollment

                                    // First, get total units for each enrollment
                                    $enrollment_units_query = $db_account->Execute("
                                        SELECT 
                                            em.PK_ENROLLMENT_MASTER,
                                            COALESCE(SUM(es.NUMBER_OF_SESSION), 0) AS TOTAL_UNITS
                                        FROM DOA_ENROLLMENT_MASTER em
                                        LEFT JOIN DOA_ENROLLMENT_SERVICE es ON em.PK_ENROLLMENT_MASTER = es.PK_ENROLLMENT_MASTER
                                        LEFT JOIN DOA_SERVICE_CODE sc ON es.PK_SERVICE_CODE = sc.PK_SERVICE_CODE
                                        WHERE em.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ")
                                        AND (sc.IS_GROUP = 0 OR sc.IS_GROUP IS NULL)
                                        AND em.ENROLLMENT_DATE BETWEEN '$from_date' AND '$to_date'
                                        GROUP BY em.PK_ENROLLMENT_MASTER
                                    ");

                                    while (!$enrollment_units_query->EOF) {
                                        $enrollment_id = $enrollment_units_query->fields['PK_ENROLLMENT_MASTER'];
                                        $total_units = $enrollment_units_query->fields['TOTAL_UNITS'];
                                        $enrollment_units_data[$enrollment_id] = $total_units;
                                        $enrollment_units_query->MoveNext();
                                    }

                                    // Calculate total units across all enrollments for percentage calculations
                                    $total_all_units = array_sum($enrollment_units_data);

                                    // Process each service provider
                                    while (!$each_service_provider->EOF) {
                                        $service_provider_id_per_table = $each_service_provider->fields['SERVICE_PROVIDER_ID'];
                                        $name = $db->Execute("SELECT CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS TEACHER FROM DOA_USERS WHERE DOA_USERS.PK_USER = " . $service_provider_id_per_table);
                                        $provider_name = $name->fields['TEACHER'];

                                        $provider_data = [
                                            'name' => $provider_name,
                                            'pre_original' => ['sold' => 0, 'units' => 0, 'enrollments' => []],
                                            'original' => ['sold' => 0, 'units' => 0, 'enrollments' => []],
                                            'extension' => ['sold' => 0, 'units' => 0, 'enrollments' => []],
                                            'renewal' => ['sold' => 0, 'units' => 0, 'enrollments' => []]
                                        ];

                                        $enrollment_types = [
                                            5 => 'pre_original',
                                            2 => 'original',
                                            9 => 'extension',
                                            13 => 'renewal'
                                        ];

                                        foreach ($enrollment_types as $type_id => $type_name) {
                                            // Get enrollments with service provider percentage
                                            $enrollments_query = $db_account->Execute("
                                                SELECT 
                                                    em.PK_ENROLLMENT_MASTER,
                                                    em.ENROLLMENT_DATE,
                                                    em.PK_ENROLLMENT_TYPE,
                                                    esp.SERVICE_PROVIDER_PERCENTAGE
                                                FROM DOA_ENROLLMENT_MASTER em
                                                INNER JOIN DOA_ENROLLMENT_BILLING eb ON em.PK_ENROLLMENT_MASTER = eb.PK_ENROLLMENT_MASTER
                                                INNER JOIN DOA_ENROLLMENT_SERVICE_PROVIDER esp ON em.PK_ENROLLMENT_MASTER = esp.PK_ENROLLMENT_MASTER
                                                WHERE em.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ")
                                                AND eb.TOTAL_AMOUNT > 0
                                                AND esp.SERVICE_PROVIDER_ID = $service_provider_id_per_table
                                                AND em.PK_ENROLLMENT_TYPE = $type_id
                                                AND em.ENROLLMENT_DATE BETWEEN '$from_date' AND '$to_date'
                                                GROUP BY em.PK_ENROLLMENT_MASTER
                                            ");

                                            $enrollment_count = 0;
                                            $total_units = 0;
                                            $enrollment_list = [];

                                            while (!$enrollments_query->EOF) {
                                                $enrollment_id = $enrollments_query->fields['PK_ENROLLMENT_MASTER'];
                                                $percentage = $enrollments_query->fields['SERVICE_PROVIDER_PERCENTAGE'];

                                                // Calculate units based on percentage
                                                $total_enrollment_units = isset($enrollment_units_data[$enrollment_id]) ? $enrollment_units_data[$enrollment_id] : 0;
                                                $provider_units = $total_enrollment_units * ($percentage / 100);

                                                $enrollment_count++;
                                                $total_units += $provider_units;
                                                $enrollment_list[] = $enrollment_id . " (" . number_format($provider_units, 2) . " units)";

                                                // Track which providers are associated with each enrollment
                                                if (!isset($all_enrollments_with_providers[$enrollment_id])) {
                                                    $all_enrollments_with_providers[$enrollment_id] = [];
                                                }
                                                $all_enrollments_with_providers[$enrollment_id][] = [
                                                    'provider_name' => $provider_name,
                                                    'percentage' => $percentage,
                                                    'units' => $provider_units
                                                ];

                                                $enrollments_query->MoveNext();
                                            }

                                            $provider_data[$type_name]['sold'] = $enrollment_count;
                                            $provider_data[$type_name]['units'] = $total_units;
                                            $provider_data[$type_name]['enrollments'] = $enrollment_list;
                                        }

                                        $all_providers_data[] = $provider_data;
                                        $each_service_provider->MoveNext();
                                    }

                                    // Calculate grand totals
                                    $grand_total_enrollments = count($enrollment_units_data);
                                    $grand_total_units = array_sum($enrollment_units_data);

                                    // Identify enrollments with multiple providers
                                    $multi_provider_enrollments = [];
                                    foreach ($all_enrollments_with_providers as $enrollment_id => $providers) {
                                        if (count($providers) > 1) {
                                            $multi_provider_enrollments[$enrollment_id] = $providers;
                                        }
                                    }

                                    // NEW SECTION: Process enrollments with no service provider - CORRECTED
                                    $no_provider_data = [
                                        'pre_original' => ['sold' => 0, 'units' => 0, 'percentage' => 0, 'enrollments' => []],
                                        'original' => ['sold' => 0, 'units' => 0, 'percentage' => 0, 'enrollments' => []],
                                        'extension' => ['sold' => 0, 'units' => 0, 'percentage' => 0, 'enrollments' => []],
                                        'renewal' => ['sold' => 0, 'units' => 0, 'percentage' => 0, 'enrollments' => []],
                                        'total_units' => 0,
                                        'total_percentage' => 0
                                    ];

                                    if ($include_no_provider == 1) {
                                        // First, get all enrollment IDs that have service providers in our selected providers
                                        $enrollments_with_providers_query = $db_account->Execute("
        SELECT DISTINCT esp.PK_ENROLLMENT_MASTER
        FROM DOA_ENROLLMENT_SERVICE_PROVIDER esp
        INNER JOIN DOA_ENROLLMENT_MASTER em ON esp.PK_ENROLLMENT_MASTER = em.PK_ENROLLMENT_MASTER
        WHERE em.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ")
        AND esp.SERVICE_PROVIDER_ID IN ($service_provider_id)
        AND em.ENROLLMENT_DATE BETWEEN '$from_date' AND '$to_date'
    ");

                                        $enrollments_with_providers = [];
                                        while (!$enrollments_with_providers_query->EOF) {
                                            $enrollments_with_providers[] = $enrollments_with_providers_query->fields['PK_ENROLLMENT_MASTER'];
                                            $enrollments_with_providers_query->MoveNext();
                                        }

                                        $enrollment_types = [
                                            5 => 'pre_original',
                                            2 => 'original',
                                            9 => 'extension',
                                            13 => 'renewal'
                                        ];

                                        foreach ($enrollment_types as $type_id => $type_name) {
                                            // Get ALL enrollments of this type in our date range
                                            $all_enrollments_query = $db_account->Execute("
            SELECT 
                em.PK_ENROLLMENT_MASTER,
                em.ENROLLMENT_DATE,
                em.PK_ENROLLMENT_TYPE,
                COALESCE(SUM(es.NUMBER_OF_SESSION), 0) AS UNITS
            FROM DOA_ENROLLMENT_MASTER em
            INNER JOIN DOA_ENROLLMENT_BILLING eb ON em.PK_ENROLLMENT_MASTER = eb.PK_ENROLLMENT_MASTER
            LEFT JOIN DOA_ENROLLMENT_SERVICE es ON em.PK_ENROLLMENT_MASTER = es.PK_ENROLLMENT_MASTER
            LEFT JOIN DOA_SERVICE_CODE sc ON es.PK_SERVICE_CODE = sc.PK_SERVICE_CODE
            WHERE em.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ")
            AND eb.TOTAL_AMOUNT > 0
            AND (sc.IS_GROUP = 0 OR sc.IS_GROUP IS NULL)
            AND em.PK_ENROLLMENT_TYPE = $type_id
            AND em.ENROLLMENT_DATE BETWEEN '$from_date' AND '$to_date'
            GROUP BY em.PK_ENROLLMENT_MASTER
        ");

                                            $enrollment_count = 0;
                                            $total_units = 0;
                                            $enrollment_list = [];

                                            while (!$all_enrollments_query->EOF) {
                                                $enrollment_id = $all_enrollments_query->fields['PK_ENROLLMENT_MASTER'];
                                                $units = $all_enrollments_query->fields['UNITS'];

                                                // Check if this enrollment does NOT have ANY of the selected service providers
                                                if (!in_array($enrollment_id, $enrollments_with_providers)) {
                                                    $enrollment_count++;
                                                    $total_units += $units;
                                                    $enrollment_list[] = $enrollment_id . " (" . number_format($units, 2) . " units)";
                                                }

                                                $all_enrollments_query->MoveNext();
                                            }

                                            $no_provider_data[$type_name]['sold'] = $enrollment_count;
                                            $no_provider_data[$type_name]['units'] = $total_units;
                                            $no_provider_data[$type_name]['percentage'] = $total_all_units > 0 ? ($total_units / $total_all_units) * 100 : 0;
                                            $no_provider_data[$type_name]['enrollments'] = $enrollment_list;
                                        }

                                        // Calculate no provider totals
                                        $no_provider_total_units = $no_provider_data['pre_original']['units'] + $no_provider_data['original']['units'] + $no_provider_data['extension']['units'] + $no_provider_data['renewal']['units'];
                                        $no_provider_data['total_units'] = $no_provider_total_units;
                                        $no_provider_data['total_percentage'] = $total_all_units > 0 ? ($no_provider_total_units / $total_all_units) * 100 : 0;
                                    }
                                    ?>

                                    <!-- Debug Section: Show enrollments with multiple providers -->
                                    <div class="table-responsive mt-4">
                                        <table class="table table-bordered table-sm" style="background-color: #fff3cd;">
                                            <thead>
                                                <tr>
                                                    <th colspan="4" style="text-align: center; font-weight: bold; font-size: 14px;">ENROLLMENTS WITH MULTIPLE PROVIDERS</th>
                                                </tr>
                                                <tr>
                                                    <th style="text-align: center">Enrollment ID</th>
                                                    <th style="text-align: center">Total Units</th>
                                                    <th style="text-align: center">Number of Providers</th>
                                                    <th style="text-align: center">Provider Distribution</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (!empty($multi_provider_enrollments)): ?>
                                                    <?php foreach ($multi_provider_enrollments as $enrollment_id => $providers): ?>
                                                        <?php
                                                        $total_enrollment_units = isset($enrollment_units_data[$enrollment_id]) ? $enrollment_units_data[$enrollment_id] : 0;
                                                        $provider_details = [];
                                                        foreach ($providers as $provider) {
                                                            $provider_details[] = $provider['provider_name'] . " (" . $provider['percentage'] . "% = " . number_format($provider['units'], 2) . " units)";
                                                        }
                                                        ?>
                                                        <tr>
                                                            <td style="text-align: center"><?= $enrollment_id ?></td>
                                                            <td style="text-align: center"><?= number_format($total_enrollment_units, 2) ?></td>
                                                            <td style="text-align: center"><?= count($providers) ?></td>
                                                            <td style="text-align: center"><?= implode(', ', $provider_details) ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="4" style="text-align: center">No enrollments found with multiple providers</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Display each provider's table WITH enrollment IDs and calculated units -->
                                    <?php foreach ($all_providers_data as $provider_data): ?>
                                        <div class="table-responsive mt-4">
                                            <table class="table table-bordered" data-page-length='50'>
                                                <thead>
                                                    <tr>
                                                        <th style="width:50%; text-align: center; vertical-align:auto; font-weight: bold" colspan="4"><?= $provider_data['name'] ?></th>
                                                    </tr>
                                                    <tr>
                                                        <th style="width:8%; text-align: center">Enrollment Type</th>
                                                        <th style="width:8%; text-align: center">Total Enrollments</th>
                                                        <th style="width:8%; text-align: center">Total Units Sold</th>
                                                        <th style="width:26%; text-align: center">Enrollment IDs (with units)</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <!-- Pre Original -->
                                                    <tr>
                                                        <td style="text-align: center">Pre Original</td>
                                                        <td style="text-align: center"><?= $provider_data['pre_original']['sold'] ?></td>
                                                        <td style="text-align: center"><?= number_format($provider_data['pre_original']['units'], 2) ?></td>
                                                        <td style="text-align: center; font-size: 11px;">
                                                            <?= !empty($provider_data['pre_original']['enrollments']) ? implode(', ', $provider_data['pre_original']['enrollments']) : 'None' ?>
                                                        </td>
                                                    </tr>

                                                    <!-- Original -->
                                                    <tr>
                                                        <td style="text-align: center">Original</td>
                                                        <td style="text-align: center"><?= $provider_data['original']['sold'] ?></td>
                                                        <td style="text-align: center"><?= number_format($provider_data['original']['units'], 2) ?></td>
                                                        <td style="text-align: center; font-size: 11px;">
                                                            <?= !empty($provider_data['original']['enrollments']) ? implode(', ', $provider_data['original']['enrollments']) : 'None' ?>
                                                        </td>
                                                    </tr>

                                                    <!-- Extension -->
                                                    <tr>
                                                        <td style="text-align: center">Extension</td>
                                                        <td style="text-align: center"><?= $provider_data['extension']['sold'] ?></td>
                                                        <td style="text-align: center"><?= number_format($provider_data['extension']['units'], 2) ?></td>
                                                        <td style="text-align: center; font-size: 11px;">
                                                            <?= !empty($provider_data['extension']['enrollments']) ? implode(', ', $provider_data['extension']['enrollments']) : 'None' ?>
                                                        </td>
                                                    </tr>

                                                    <!-- Renewal -->
                                                    <tr>
                                                        <td style="text-align: center">Renewal</td>
                                                        <td style="text-align: center"><?= $provider_data['renewal']['sold'] ?></td>
                                                        <td style="text-align: center"><?= number_format($provider_data['renewal']['units'], 2) ?></td>
                                                        <td style="text-align: center; font-size: 11px;">
                                                            <?= !empty($provider_data['renewal']['enrollments']) ? implode(', ', $provider_data['renewal']['enrollments']) : 'None' ?>
                                                        </td>
                                                    </tr>

                                                    <!-- Provider Totals -->
                                                    <tr style="background-color: #f8f9fa;">
                                                        <td style="text-align: center; font-weight: bold">Provider Total</td>
                                                        <td style="text-align: center; font-weight: bold">
                                                            <?= $provider_data['pre_original']['sold'] + $provider_data['original']['sold'] + $provider_data['extension']['sold'] + $provider_data['renewal']['sold'] ?>
                                                        </td>
                                                        <td style="text-align: center; font-weight: bold">
                                                            <?= number_format($provider_data['pre_original']['units'] + $provider_data['original']['units'] + $provider_data['extension']['units'] + $provider_data['renewal']['units'], 2) ?>
                                                        </td>
                                                        <td style="text-align: center; font-weight: bold">
                                                            Total Unique: <?= count(array_unique(array_merge(
                                                                                array_map(function ($e) {
                                                                                    return explode(' ', $e)[0];
                                                                                }, $provider_data['pre_original']['enrollments']),
                                                                                array_map(function ($e) {
                                                                                    return explode(' ', $e)[0];
                                                                                }, $provider_data['original']['enrollments']),
                                                                                array_map(function ($e) {
                                                                                    return explode(' ', $e)[0];
                                                                                }, $provider_data['extension']['enrollments']),
                                                                                array_map(function ($e) {
                                                                                    return explode(' ', $e)[0];
                                                                                }, $provider_data['renewal']['enrollments'])
                                                                            ))) ?>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endforeach; ?>

                                    <!-- NEW SECTION: Display enrollments with no service provider -->
                                    <?php if ($include_no_provider == 1): ?>
                                        <div class="table-responsive mt-4">
                                            <table class="table table-bordered" style="background-color: #f8d7da;">
                                                <thead>
                                                    <tr>
                                                        <th style="width:50%; text-align: center; vertical-align:auto; font-weight: bold" colspan="4">ENROLLMENTS WITH NO SERVICE PROVIDER</th>
                                                    </tr>
                                                    <tr>
                                                        <th style="width:8%; text-align: center">Enrollment Type</th>
                                                        <th style="width:8%; text-align: center">Total Enrollments</th>
                                                        <th style="width:8%; text-align: center">Total Units Sold</th>
                                                        <th style="width:26%; text-align: center">Enrollment IDs (with units)</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <!-- Pre Original -->
                                                    <tr>
                                                        <td style="text-align: center">Pre Original</td>
                                                        <td style="text-align: center"><?= $no_provider_data['pre_original']['sold'] ?></td>
                                                        <td style="text-align: center"><?= number_format($no_provider_data['pre_original']['units'], 2) ?></td>
                                                        <td style="text-align: center; font-size: 11px;">
                                                            <?= !empty($no_provider_data['pre_original']['enrollments']) ? implode(', ', $no_provider_data['pre_original']['enrollments']) : 'None' ?>
                                                        </td>
                                                    </tr>

                                                    <!-- Original -->
                                                    <tr>
                                                        <td style="text-align: center">Original</td>
                                                        <td style="text-align: center"><?= $no_provider_data['original']['sold'] ?></td>
                                                        <td style="text-align: center"><?= number_format($no_provider_data['original']['units'], 2) ?></td>
                                                        <td style="text-align: center; font-size: 11px;">
                                                            <?= !empty($no_provider_data['original']['enrollments']) ? implode(', ', $no_provider_data['original']['enrollments']) : 'None' ?>
                                                        </td>
                                                    </tr>

                                                    <!-- Extension -->
                                                    <tr>
                                                        <td style="text-align: center">Extension</td>
                                                        <td style="text-align: center"><?= $no_provider_data['extension']['sold'] ?></td>
                                                        <td style="text-align: center"><?= number_format($no_provider_data['extension']['units'], 2) ?></td>
                                                        <td style="text-align: center; font-size: 11px;">
                                                            <?= !empty($no_provider_data['extension']['enrollments']) ? implode(', ', $no_provider_data['extension']['enrollments']) : 'None' ?>
                                                        </td>
                                                    </tr>

                                                    <!-- Renewal -->
                                                    <tr>
                                                        <td style="text-align: center">Renewal</td>
                                                        <td style="text-align: center"><?= $no_provider_data['renewal']['sold'] ?></td>
                                                        <td style="text-align: center"><?= number_format($no_provider_data['renewal']['units'], 2) ?></td>
                                                        <td style="text-align: center; font-size: 11px;">
                                                            <?= !empty($no_provider_data['renewal']['enrollments']) ? implode(', ', $no_provider_data['renewal']['enrollments']) : 'None' ?>
                                                        </td>
                                                    </tr>

                                                    <!-- No Provider Totals -->
                                                    <tr style="background-color: #f1b0b7;">
                                                        <td style="text-align: center; font-weight: bold">No Provider Total</td>
                                                        <td style="text-align: center; font-weight: bold">
                                                            <?= $no_provider_data['pre_original']['sold'] + $no_provider_data['original']['sold'] + $no_provider_data['extension']['sold'] + $no_provider_data['renewal']['sold'] ?>
                                                        </td>
                                                        <td style="text-align: center; font-weight: bold">
                                                            <?= number_format($no_provider_data['total_units'], 2) ?>
                                                        </td>
                                                        <td style="text-align: center; font-weight: bold">
                                                            Total Unique: <?= count(array_unique(array_merge(
                                                                                array_map(function ($e) {
                                                                                    return explode(' ', $e)[0];
                                                                                }, $no_provider_data['pre_original']['enrollments']),
                                                                                array_map(function ($e) {
                                                                                    return explode(' ', $e)[0];
                                                                                }, $no_provider_data['original']['enrollments']),
                                                                                array_map(function ($e) {
                                                                                    return explode(' ', $e)[0];
                                                                                }, $no_provider_data['extension']['enrollments']),
                                                                                array_map(function ($e) {
                                                                                    return explode(' ', $e)[0];
                                                                                }, $no_provider_data['renewal']['enrollments'])
                                                                            ))) ?>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Summary Statistics -->
                                    <div class="table-responsive mt-4">
                                        <table class="table table-bordered table-sm" style="background-color: #e9ecef;">
                                            <thead>
                                                <tr>
                                                    <th colspan="2" style="text-align: center; font-weight: bold; font-size: 14px;">SUMMARY STATISTICS</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                // Calculate total enrollments with providers
                                                $total_enrollments_with_providers = 0;
                                                foreach ($all_providers_data as $provider) {
                                                    $total_enrollments_with_providers += $provider['pre_original']['sold'] + $provider['original']['sold'] + $provider['extension']['sold'] + $provider['renewal']['sold'];
                                                }

                                                // Calculate total enrollments without providers
                                                $total_enrollments_without_providers = 0;
                                                if ($include_no_provider == 1) {
                                                    $total_enrollments_without_providers = $no_provider_data['pre_original']['sold'] + $no_provider_data['original']['sold'] + $no_provider_data['extension']['sold'] + $no_provider_data['renewal']['sold'];
                                                }

                                                // Total ALL enrollments (with providers + without providers)
                                                $total_all_enrollments = $total_enrollments_with_providers + $total_enrollments_without_providers;
                                                ?>

                                                <tr>
                                                    <td style="text-align: right; font-weight: bold">Total Enrollments with Service Providers:</td>
                                                    <td style="text-align: left"><?= $total_enrollments_with_providers ?></td>
                                                </tr>
                                                <?php if ($include_no_provider == 1): ?>
                                                    <tr>
                                                        <td style="text-align: right; font-weight: bold">Total Enrollments without Service Providers:</td>
                                                        <td style="text-align: left"><?= $total_enrollments_without_providers ?></td>
                                                    </tr>
                                                <?php endif; ?>
                                                <tr>
                                                    <td style="text-align: right; font-weight: bold">Total ALL Enrollments:</td>
                                                    <td style="text-align: left"><?= $total_all_enrollments ?></td>
                                                </tr>
                                                <tr>
                                                    <td style="text-align: right; font-weight: bold">Enrollments with Multiple Service Providers:</td>
                                                    <td style="text-align: left"><?= count($multi_provider_enrollments) ?></td>
                                                </tr>
                                                <tr>
                                                    <td style="text-align: right; font-weight: bold">Total Unique Enrollments:</td>
                                                    <td style="text-align: left; font-weight: bold"><?= $total_all_enrollments - count($multi_provider_enrollments) ?></td>
                                                </tr>
                                                <tr>
                                                    <td style="text-align: right; font-weight: bold">Total Service Provider Units (by %):</td>
                                                    <td style="text-align: left">
                                                        <?php
                                                        $total_allocated_units = 0;
                                                        foreach ($all_providers_data as $provider) {
                                                            $total_allocated_units += $provider['pre_original']['units'] + $provider['original']['units'] + $provider['extension']['units'] + $provider['renewal']['units'];
                                                        }
                                                        echo number_format($total_allocated_units, 2);
                                                        ?>
                                                    </td>
                                                </tr>
                                                <?php if ($include_no_provider == 1): ?>
                                                    <tr>
                                                        <td style="text-align: right; font-weight: bold">Total No Service Provider Units:</td>
                                                        <td style="text-align: left"><?= number_format($no_provider_data['total_units'], 2) ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td style="text-align: right; font-weight: bold">Total Units:</td>
                                                        <td style="text-align: left; font-weight: bold">
                                                            <?php
                                                            $total_coverage_units = $total_allocated_units + $no_provider_data['total_units'];
                                                            $coverage_percentage = $grand_total_units > 0 ? ($total_coverage_units / $grand_total_units) * 100 : 0;
                                                            echo number_format($total_coverage_units, 2) . " (" . number_format($coverage_percentage, 2) . "%)";
                                                            ?>
                                                        </td>
                                                    </tr>
                                                <?php endif; ?>
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