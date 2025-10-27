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

// Fix for handling multiple service provider IDs
if (isset($_GET['PK_USER']) && is_array($_GET['PK_USER'])) {
    $service_provider_id = implode(',', $_GET['PK_USER']);
} else {
    $service_provider_id = $_GET['service_provider_id'] ?? '';
}

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

if (!empty($_GET['START_DATE'])) {
    $type = isset($_GET['view']) ? 'view' : 'export';
    $generate_pdf = isset($_GET['generate_pdf']) ? 1 : 0;
    $generate_excel = isset($_GET['generate_excel']) ? 1 : 0;
    $report_name = 'sales_by_enrollment_report';
    $WEEK_NUMBER = explode(' ', $_GET['WEEK_NUMBER'])[2];
    $START_DATE = date('Y-m-d', strtotime($_GET['START_DATE']));
    $END_DATE = date('Y-m-d', strtotime($_GET['END_DATE']));
    $PK_USER = empty($_GET['PK_USER']) ? 0 : $_GET['PK_USER'];
    $include_no_provider = isset($_GET['include_no_provider']) ? 1 : 0;

    if ($generate_pdf === 1) {
        header('location:generate_report_pdf.php?week_number=' . $WEEK_NUMBER . '&start_date=' . $START_DATE . '&end_date=' . $END_DATE . '&report_type=' . $report_name . '&PK_USER=' . implode(',', $PK_USER));
    } elseif ($generate_excel === 1) {
        header('location:excel_' . $report_name . '.php?week_number=' . $WEEK_NUMBER . '&start_date=' . $START_DATE . '&end_date=' . $END_DATE . '&report_type=' . $report_name . '&PK_USER=' . implode(',', $PK_USER));
    } else {
        header('location:sales_by_enrollment_report.php?week_number=' . $WEEK_NUMBER . '&start_date=' . $START_DATE . '&end_date=' . $END_DATE . '&type=' . $type . '&service_provider_id=' . implode(',', $PK_USER) . '&include_no_provider=' . $include_no_provider);
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

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body" style="padding-bottom: 0px !important;">
                                <form class="form-material form-horizontal" action="" method="get" id="reportForm">
                                    <input type="hidden" name="start_date" id="start_date">
                                    <input type="hidden" name="end_date" id="end_date">
                                    <div class="row">
                                        <div class="col-2">
                                            <div id="location" style="width: 100%;">
                                                <select class="multi_select_service_provider" multiple id="service_provider_select" name="PK_USER[]">
                                                    <?php
                                                    // Convert service_provider_id string to array for selection check
                                                    $selected_provider_ids = [];
                                                    if (!empty($service_provider_id) && $service_provider_id != '0') {
                                                        $selected_provider_ids = explode(',', $service_provider_id);
                                                    }

                                                    // Fixed query with proper session variable handling
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
                                            <div class="no-provider-checkbox" id="no_provider_checkbox">
                                                <input type="checkbox" id="include_no_provider" name="include_no_provider" value="1" <?= isset($_GET['include_no_provider']) && $_GET['include_no_provider'] == 1 ? 'checked' : '' ?>>
                                                <label for="include_no_provider">With No Service Provider</label>
                                            </div>
                                        </div>
                                        <div class="col-2">
                                            <div class="form-group">
                                                <input type="text" id="START_DATE" name="START_DATE" class="form-control datepicker-normal" placeholder="Start Date" value="<?= !empty($_GET['start_date']) ? date('m/d/Y', strtotime($_GET['start_date'])) : '' ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-2">
                                            <div class="form-group">
                                                <input type="text" id="END_DATE" name="END_DATE" class="form-control datepicker-normal" placeholder="End Date" value="<?= !empty($_GET['end_date']) ? date('m/d/Y', strtotime($_GET['end_date'])) : '' ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <?php if (in_array('Reports Create', $PERMISSION_ARRAY)) { ?>
                                                <input type="submit" name="view" value="View" class="btn btn-info" style="background-color: #39B54A !important;">
                                                <!-- <input type="submit" name="export" value="Export" class="btn btn-info" style="background-color: #39B54A !important;"> -->
                                                <!-- <input type="submit" name="generate_pdf" value="Generate PDF" class="btn btn-info" style="background-color: #39B54A !important;"> -->
                                                <input type="submit" name="generate_excel" value="Generate Excel" class="btn btn-info" style="background-color: #39B54A !important;">
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
                                    // Get total sold counts from studio summary report (matching the studio business report logic)
                                    $weekly_pre_original_sold = $db_account->Execute("SELECT COUNT(DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS SOLD FROM `DOA_ENROLLMENT_MASTER` LEFT JOIN DOA_ENROLLMENT_BILLING ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_ENROLLMENT_BILLING.TOTAL_AMOUNT > 0 AND IS_SALE = 'Y' AND PK_ENROLLMENT_TYPE = 5 AND ENROLLMENT_DATE BETWEEN '$from_date' AND '$to_date'");

                                    $weekly_original_sold = $db_account->Execute("SELECT COUNT(DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS SOLD FROM `DOA_ENROLLMENT_MASTER` LEFT JOIN DOA_ENROLLMENT_BILLING ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_ENROLLMENT_BILLING.TOTAL_AMOUNT > 0 AND IS_SALE = 'Y' AND PK_ENROLLMENT_TYPE = 2 AND ENROLLMENT_DATE BETWEEN '$from_date' AND '$to_date'");

                                    $weekly_extension_sold = $db_account->Execute("SELECT COUNT(DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS SOLD FROM `DOA_ENROLLMENT_MASTER` LEFT JOIN DOA_ENROLLMENT_BILLING ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_ENROLLMENT_BILLING.TOTAL_AMOUNT > 0 AND IS_SALE = 'Y' AND PK_ENROLLMENT_TYPE = 9 AND ENROLLMENT_DATE BETWEEN '$from_date' AND '$to_date'");

                                    $weekly_renewal_sold = $db_account->Execute("SELECT COUNT(DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS SOLD FROM `DOA_ENROLLMENT_MASTER` LEFT JOIN DOA_ENROLLMENT_BILLING ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_ENROLLMENT_BILLING.TOTAL_AMOUNT > 0 AND IS_SALE = 'Y' AND PK_ENROLLMENT_TYPE = 13 AND ENROLLMENT_DATE BETWEEN '$from_date' AND '$to_date'");

                                    // Store the total sold counts
                                    $total_pre_original_sold = $weekly_pre_original_sold->fields['SOLD'] > 0 ? $weekly_pre_original_sold->fields['SOLD'] : 0;
                                    $total_original_sold = $weekly_original_sold->fields['SOLD'] > 0 ? $weekly_original_sold->fields['SOLD'] : 0;
                                    $total_extension_sold = $weekly_extension_sold->fields['SOLD'] > 0 ? $weekly_extension_sold->fields['SOLD'] : 0;
                                    $total_renewal_sold = $weekly_renewal_sold->fields['SOLD'] > 0 ? $weekly_renewal_sold->fields['SOLD'] : 0;
                                    $total_all_sold = $total_pre_original_sold + $total_original_sold + $total_extension_sold + $total_renewal_sold;

                                    // Get total units from studio summary report (EXACT MATCH with Studio Business Report)
                                    $weekly_pre_original_units = $db_account->Execute("
    SELECT COALESCE(SUM(es.NUMBER_OF_SESSION), 0) AS TOTAL_UNITS 
    FROM DOA_ENROLLMENT_MASTER em 
    INNER JOIN DOA_ENROLLMENT_BILLING eb ON em.PK_ENROLLMENT_MASTER = eb.PK_ENROLLMENT_MASTER 
    INNER JOIN DOA_ENROLLMENT_SERVICE es ON em.PK_ENROLLMENT_MASTER = es.PK_ENROLLMENT_MASTER 
    LEFT JOIN DOA_SERVICE_CODE sc ON es.PK_SERVICE_CODE = sc.PK_SERVICE_CODE 
    WHERE em.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") 
    AND eb.TOTAL_AMOUNT > 0 
    AND em.PK_ENROLLMENT_TYPE = 5 
    AND (sc.IS_GROUP = 0 OR sc.IS_GROUP IS NULL) 
    AND em.ENROLLMENT_DATE BETWEEN '$from_date' AND '$to_date'
");

                                    $weekly_original_units = $db_account->Execute("
    SELECT COALESCE(SUM(es.NUMBER_OF_SESSION), 0) AS TOTAL_UNITS 
    FROM DOA_ENROLLMENT_MASTER em 
    INNER JOIN DOA_ENROLLMENT_BILLING eb ON em.PK_ENROLLMENT_MASTER = eb.PK_ENROLLMENT_MASTER 
    INNER JOIN DOA_ENROLLMENT_SERVICE es ON em.PK_ENROLLMENT_MASTER = es.PK_ENROLLMENT_MASTER 
    LEFT JOIN DOA_SERVICE_CODE sc ON es.PK_SERVICE_CODE = sc.PK_SERVICE_CODE 
    WHERE em.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") 
    AND eb.TOTAL_AMOUNT > 0 
    AND em.PK_ENROLLMENT_TYPE = 2 
    AND (sc.IS_GROUP = 0 OR sc.IS_GROUP IS NULL) 
    AND em.ENROLLMENT_DATE BETWEEN '$from_date' AND '$to_date'
");

                                    $weekly_extension_units = $db_account->Execute("
    SELECT COALESCE(SUM(es.NUMBER_OF_SESSION), 0) AS TOTAL_UNITS 
    FROM DOA_ENROLLMENT_MASTER em 
    INNER JOIN DOA_ENROLLMENT_BILLING eb ON em.PK_ENROLLMENT_MASTER = eb.PK_ENROLLMENT_MASTER 
    INNER JOIN DOA_ENROLLMENT_SERVICE es ON em.PK_ENROLLMENT_MASTER = es.PK_ENROLLMENT_MASTER 
    LEFT JOIN DOA_SERVICE_CODE sc ON es.PK_SERVICE_CODE = sc.PK_SERVICE_CODE 
    WHERE em.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") 
    AND eb.TOTAL_AMOUNT > 0 
    AND em.PK_ENROLLMENT_TYPE = 9 
    AND (sc.IS_GROUP = 0 OR sc.IS_GROUP IS NULL) 
    AND em.ENROLLMENT_DATE BETWEEN '$from_date' AND '$to_date'
");

                                    $weekly_renewal_units = $db_account->Execute("
    SELECT COALESCE(SUM(es.NUMBER_OF_SESSION), 0) AS TOTAL_UNITS 
    FROM DOA_ENROLLMENT_MASTER em 
    INNER JOIN DOA_ENROLLMENT_BILLING eb ON em.PK_ENROLLMENT_MASTER = eb.PK_ENROLLMENT_MASTER 
    INNER JOIN DOA_ENROLLMENT_SERVICE es ON em.PK_ENROLLMENT_MASTER = es.PK_ENROLLMENT_MASTER 
    LEFT JOIN DOA_SERVICE_CODE sc ON es.PK_SERVICE_CODE = sc.PK_SERVICE_CODE 
    WHERE em.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") 
    AND eb.TOTAL_AMOUNT > 0 
    AND em.PK_ENROLLMENT_TYPE = 13 
    AND (sc.IS_GROUP = 0 OR sc.IS_GROUP IS NULL) 
    AND em.ENROLLMENT_DATE BETWEEN '$from_date' AND '$to_date'
");

                                    // Store the total units
                                    $total_pre_original_units = $weekly_pre_original_units->fields['TOTAL_UNITS'] > 0 ? $weekly_pre_original_units->fields['TOTAL_UNITS'] : 0;
                                    $total_original_units = $weekly_original_units->fields['TOTAL_UNITS'] > 0 ? $weekly_original_units->fields['TOTAL_UNITS'] : 0;
                                    $total_extension_units = $weekly_extension_units->fields['TOTAL_UNITS'] > 0 ? $weekly_extension_units->fields['TOTAL_UNITS'] : 0;
                                    $total_renewal_units = $weekly_renewal_units->fields['TOTAL_UNITS'] > 0 ? $weekly_renewal_units->fields['TOTAL_UNITS'] : 0;
                                    $total_all_units_studio = $total_pre_original_units + $total_original_units + $total_extension_units + $total_renewal_units;

                                    // Get all enrollments (including non-sales) for comparison
                                    $all_enrollments_query = $db_account->Execute("
    SELECT COUNT(DISTINCT em.PK_ENROLLMENT_MASTER) AS TOTAL_ENROLLMENTS
    FROM DOA_ENROLLMENT_MASTER em
    INNER JOIN DOA_ENROLLMENT_BILLING eb ON em.PK_ENROLLMENT_MASTER = eb.PK_ENROLLMENT_MASTER
    WHERE em.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ")
    AND eb.TOTAL_AMOUNT > 0
    AND em.ENROLLMENT_DATE BETWEEN '$from_date' AND '$to_date'
");
                                    $total_all_enrollments = $all_enrollments_query->fields['TOTAL_ENROLLMENTS'] ?? 0;

                                    // Get all selected service providers
                                    $each_service_provider = $db_account->Execute("SELECT DISTINCT DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID 
    FROM DOA_ENROLLMENT_MASTER 
    INNER JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER 
    INNER JOIN DOA_ENROLLMENT_BILLING ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER
    WHERE DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") 
    AND DOA_ENROLLMENT_BILLING.TOTAL_AMOUNT > 0
    AND DOA_ENROLLMENT_MASTER.IS_SALE = 'Y'
    AND DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN ($service_provider_id) 
    GROUP BY SERVICE_PROVIDER_ID");

                                    $all_providers_data = [];
                                    $all_enrollments_with_providers = []; // Track which enrollments have which providers

                                    // Process each service provider (only for sold enrollments)
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
                                            // QUERY 1: Get sold enrollments count for this provider and type
                                            $sold_enrollments_query = $db_account->Execute("
            SELECT COUNT(DISTINCT em.PK_ENROLLMENT_MASTER) AS SOLD_COUNT
            FROM DOA_ENROLLMENT_MASTER em
            INNER JOIN DOA_ENROLLMENT_BILLING eb ON em.PK_ENROLLMENT_MASTER = eb.PK_ENROLLMENT_MASTER
            INNER JOIN DOA_ENROLLMENT_SERVICE_PROVIDER esp ON em.PK_ENROLLMENT_MASTER = esp.PK_ENROLLMENT_MASTER
            WHERE em.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ")
            AND eb.TOTAL_AMOUNT > 0 
            AND em.IS_SALE = 'Y'
            AND esp.SERVICE_PROVIDER_ID = $service_provider_id_per_table
            AND em.PK_ENROLLMENT_TYPE = $type_id
            AND em.ENROLLMENT_DATE BETWEEN '$from_date' AND '$to_date'
        ");

                                            $sold_count = $sold_enrollments_query->fields['SOLD_COUNT'] ?? 0;

                                            // QUERY 2: Get total units for this provider and type (using percentage allocation)
                                            $units_query = $db_account->Execute("
            SELECT 
                em.PK_ENROLLMENT_MASTER,
                esp.SERVICE_PROVIDER_PERCENTAGE,
                COALESCE(SUM(es.NUMBER_OF_SESSION), 0) AS TOTAL_ENROLLMENT_UNITS
            FROM DOA_ENROLLMENT_MASTER em
            INNER JOIN DOA_ENROLLMENT_BILLING eb ON em.PK_ENROLLMENT_MASTER = eb.PK_ENROLLMENT_MASTER
            INNER JOIN DOA_ENROLLMENT_SERVICE_PROVIDER esp ON em.PK_ENROLLMENT_MASTER = esp.PK_ENROLLMENT_MASTER
            LEFT JOIN DOA_ENROLLMENT_SERVICE es ON em.PK_ENROLLMENT_MASTER = es.PK_ENROLLMENT_MASTER
            LEFT JOIN DOA_SERVICE_CODE sc ON es.PK_SERVICE_CODE = sc.PK_SERVICE_CODE
            WHERE em.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ")
            AND eb.TOTAL_AMOUNT > 0 
            AND em.IS_SALE = 'Y'
            AND esp.SERVICE_PROVIDER_ID = $service_provider_id_per_table
            AND em.PK_ENROLLMENT_TYPE = $type_id
            AND (sc.IS_GROUP = 0 OR sc.IS_GROUP IS NULL)
            AND em.ENROLLMENT_DATE BETWEEN '$from_date' AND '$to_date'
            GROUP BY em.PK_ENROLLMENT_MASTER, esp.SERVICE_PROVIDER_PERCENTAGE
        ");

                                            $total_units = 0;
                                            $enrollment_list = [];

                                            while (!$units_query->EOF) {
                                                $enrollment_id = $units_query->fields['PK_ENROLLMENT_MASTER'];
                                                $percentage = $units_query->fields['SERVICE_PROVIDER_PERCENTAGE'];
                                                $enrollment_units = $units_query->fields['TOTAL_ENROLLMENT_UNITS'];

                                                // Calculate provider's share of units based on percentage
                                                $provider_units = $enrollment_units * ($percentage / 100);
                                                $total_units += $provider_units;

                                                $enrollment_list[] = $enrollment_id . " (" . number_format($provider_units, 2) . " units - " . $percentage . "%)";

                                                // Track which providers are associated with each enrollment
                                                if (!isset($all_enrollments_with_providers[$enrollment_id])) {
                                                    $all_enrollments_with_providers[$enrollment_id] = [];
                                                }
                                                $all_enrollments_with_providers[$enrollment_id][] = [
                                                    'provider_name' => $provider_name,
                                                    'percentage' => $percentage,
                                                    'units' => $provider_units,
                                                    'fractional_count' => $percentage / 100
                                                ];

                                                $units_query->MoveNext();
                                            }

                                            // Calculate fractional enrollment count based on percentage
                                            $fractional_enrollment_count = 0;
                                            $enrollments_with_percentage_query = $db_account->Execute("
            SELECT esp.SERVICE_PROVIDER_PERCENTAGE
            FROM DOA_ENROLLMENT_MASTER em
            INNER JOIN DOA_ENROLLMENT_BILLING eb ON em.PK_ENROLLMENT_MASTER = eb.PK_ENROLLMENT_MASTER
            INNER JOIN DOA_ENROLLMENT_SERVICE_PROVIDER esp ON em.PK_ENROLLMENT_MASTER = esp.PK_ENROLLMENT_MASTER
            WHERE em.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ")
            AND eb.TOTAL_AMOUNT > 0 
            AND em.IS_SALE = 'Y'
            AND esp.SERVICE_PROVIDER_ID = $service_provider_id_per_table
            AND em.PK_ENROLLMENT_TYPE = $type_id
            AND em.ENROLLMENT_DATE BETWEEN '$from_date' AND '$to_date'
            GROUP BY em.PK_ENROLLMENT_MASTER, esp.SERVICE_PROVIDER_PERCENTAGE
        ");

                                            while (!$enrollments_with_percentage_query->EOF) {
                                                $percentage = $enrollments_with_percentage_query->fields['SERVICE_PROVIDER_PERCENTAGE'];
                                                $fractional_enrollment_count += ($percentage / 100);
                                                $enrollments_with_percentage_query->MoveNext();
                                            }

                                            $provider_data[$type_name]['sold'] = $fractional_enrollment_count;
                                            $provider_data[$type_name]['units'] = $total_units;
                                            $provider_data[$type_name]['enrollments'] = $enrollment_list;
                                        }

                                        $all_providers_data[] = $provider_data;
                                        $each_service_provider->MoveNext();
                                    }

                                    // Calculate grand totals
                                    $grand_total_enrollments = $total_all_sold;
                                    $grand_total_units = $total_all_units_studio;

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
                                        // First, get all enrollment IDs that have service providers in our selected providers (only sold enrollments)
                                        $enrollments_with_providers_query = $db_account->Execute("
                                                SELECT DISTINCT esp.PK_ENROLLMENT_MASTER
                                                FROM DOA_ENROLLMENT_SERVICE_PROVIDER esp
                                                INNER JOIN DOA_ENROLLMENT_MASTER em ON esp.PK_ENROLLMENT_MASTER = em.PK_ENROLLMENT_MASTER
                                                INNER JOIN DOA_ENROLLMENT_BILLING eb ON em.PK_ENROLLMENT_MASTER = eb.PK_ENROLLMENT_MASTER
                                                WHERE em.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ")
                                                AND eb.TOTAL_AMOUNT > 0 AND em.IS_SALE = 'Y'  -- Match studio summary report logic
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
                                            // Get ALL enrollments of this type in our date range (only sold enrollments)
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
                                                    AND em.IS_SALE = 'Y'  -- Match studio summary report logic
                                                    AND (sc.IS_GROUP = 0 OR sc.IS_GROUP IS NULL)
                                                    AND em.PK_ENROLLMENT_TYPE = $type_id
                                                    AND em.ENROLLMENT_DATE BETWEEN '$from_date' AND '$to_date'
                                                    GROUP BY em.PK_ENROLLMENT_MASTER
                                                ");

                                            $enrollment_count = 0;
                                            $total_units = 0;
                                            $total_all_units = $total_all_units_studio; // Use previously calculated total units
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

                                    <!-- NEW SECTION: Total Sold Counts from Studio Summary Report -->
                                    <!-- <div class="table-responsive mt-4">
                                            <table class="table table-bordered table-sm" style="background-color: #d4edda;">
                                                <thead>
                                                    <tr>
                                                        <th colspan="6" style="text-align: center; font-weight: bold; font-size: 14px;">TOTAL SOLD COUNTS (Studio Summary Report)</th>
                                                    </tr>
                                                    <tr>
                                                        <th style="text-align: center">Enrollment Type</th>
                                                        <th style="text-align: center">Pre Original</th>
                                                        <th style="text-align: center">Original</th>
                                                        <th style="text-align: center">Extension</th>
                                                        <th style="text-align: center">Renewal</th>
                                                        <th style="text-align: center">Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td style="text-align: center; font-weight: bold">Total Sold</td>
                                                        <td style="text-align: center"><?= $total_pre_original_sold ?></td>
                                                        <td style="text-align: center"><?= $total_original_sold ?></td>
                                                        <td style="text-align: center"><?= $total_extension_sold ?></td>
                                                        <td style="text-align: center"><?= $total_renewal_sold ?></td>
                                                        <td style="text-align: center; font-weight: bold"><?= $total_all_sold ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td style="text-align: center; font-weight: bold">Total All Enrollments</td>
                                                        <td style="text-align: center" colspan="4"></td>
                                                        <td style="text-align: center; font-weight: bold"><?= $total_all_enrollments ?></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div> -->

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

                                    <!-- Display each provider's table WITH fractional enrollment counts -->
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
                                                        <th style="width:26%; text-align: center">Enrollment IDs (with units & %)</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <!-- Pre Original -->
                                                    <tr>
                                                        <td style="text-align: center">Pre Original</td>
                                                        <td style="text-align: center"><?= number_format($provider_data['pre_original']['sold'], 2) ?></td>
                                                        <td style="text-align: center"><?= number_format($provider_data['pre_original']['units'], 2) ?></td>
                                                        <td style="text-align: center; font-size: 11px;">
                                                            <?= !empty($provider_data['pre_original']['enrollments']) ? implode(', ', $provider_data['pre_original']['enrollments']) : 'None' ?>
                                                        </td>
                                                    </tr>

                                                    <!-- Original -->
                                                    <tr>
                                                        <td style="text-align: center">Original</td>
                                                        <td style="text-align: center"><?= number_format($provider_data['original']['sold'], 2) ?></td>
                                                        <td style="text-align: center"><?= number_format($provider_data['original']['units'], 2) ?></td>
                                                        <td style="text-align: center; font-size: 11px;">
                                                            <?= !empty($provider_data['original']['enrollments']) ? implode(', ', $provider_data['original']['enrollments']) : 'None' ?>
                                                        </td>
                                                    </tr>

                                                    <!-- Extension -->
                                                    <tr>
                                                        <td style="text-align: center">Extension</td>
                                                        <td style="text-align: center"><?= number_format($provider_data['extension']['sold'], 2) ?></td>
                                                        <td style="text-align: center"><?= number_format($provider_data['extension']['units'], 2) ?></td>
                                                        <td style="text-align: center; font-size: 11px;">
                                                            <?= !empty($provider_data['extension']['enrollments']) ? implode(', ', $provider_data['extension']['enrollments']) : 'None' ?>
                                                        </td>
                                                    </tr>

                                                    <!-- Renewal -->
                                                    <tr>
                                                        <td style="text-align: center">Renewal</td>
                                                        <td style="text-align: center"><?= number_format($provider_data['renewal']['sold'], 2) ?></td>
                                                        <td style="text-align: center"><?= number_format($provider_data['renewal']['units'], 2) ?></td>
                                                        <td style="text-align: center; font-size: 11px;">
                                                            <?= !empty($provider_data['renewal']['enrollments']) ? implode(', ', $provider_data['renewal']['enrollments']) : 'None' ?>
                                                        </td>
                                                    </tr>

                                                    <!-- Provider Totals -->
                                                    <tr style="background-color: #f8f9fa;">
                                                        <td style="text-align: center; font-weight: bold">Provider Total</td>
                                                        <td style="text-align: center; font-weight: bold">
                                                            <?= number_format($provider_data['pre_original']['sold'] + $provider_data['original']['sold'] + $provider_data['extension']['sold'] + $provider_data['renewal']['sold'], 2) ?>
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

                                    <?php
                                    // NEW SECTION: Table for Enrollments Without Service Providers
                                    if ($include_no_provider == 1 && ($no_provider_data['total_units'] > 0 || $total_enrollments_without_providers > 0)): ?>
                                        <div class="table-responsive mt-4">
                                            <table class="table table-bordered" data-page-length='50'>
                                                <thead>
                                                    <tr>
                                                        <th style="width:50%; text-align: center; vertical-align:auto; font-weight: bold" colspan="4">ENROLLMENTS WITHOUT SERVICE PROVIDERS</th>
                                                    </tr>
                                                    <tr>
                                                        <th style="width:8%; text-align: center">Enrollment Type</th>
                                                        <th style="width:8%; text-align: center">Total Enrollments</th>
                                                        <th style="width:8%; text-align: center">Total Units Sold</th>
                                                        <th style="width:26%; text-align: center">Enrollment IDs (with units)</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <!-- Pre Original Without Provider -->
                                                    <tr>
                                                        <td style="text-align: center">Pre Original</td>
                                                        <td style="text-align: center"><?= $no_provider_data['pre_original']['sold'] ?></td>
                                                        <td style="text-align: center"><?= number_format($no_provider_data['pre_original']['units'], 2) ?></td>
                                                        <td style="text-align: center; font-size: 11px;">
                                                            <?= !empty($no_provider_data['pre_original']['enrollments']) ? implode(', ', $no_provider_data['pre_original']['enrollments']) : 'None' ?>
                                                        </td>
                                                    </tr>

                                                    <!-- Original Without Provider -->
                                                    <tr>
                                                        <td style="text-align: center">Original</td>
                                                        <td style="text-align: center"><?= $no_provider_data['original']['sold'] ?></td>
                                                        <td style="text-align: center"><?= number_format($no_provider_data['original']['units'], 2) ?></td>
                                                        <td style="text-align: center; font-size: 11px;">
                                                            <?= !empty($no_provider_data['original']['enrollments']) ? implode(', ', $no_provider_data['original']['enrollments']) : 'None' ?>
                                                        </td>
                                                    </tr>

                                                    <!-- Extension Without Provider -->
                                                    <tr>
                                                        <td style="text-align: center">Extension</td>
                                                        <td style="text-align: center"><?= $no_provider_data['extension']['sold'] ?></td>
                                                        <td style="text-align: center"><?= number_format($no_provider_data['extension']['units'], 2) ?></td>
                                                        <td style="text-align: center; font-size: 11px;">
                                                            <?= !empty($no_provider_data['extension']['enrollments']) ? implode(', ', $no_provider_data['extension']['enrollments']) : 'None' ?>
                                                        </td>
                                                    </tr>

                                                    <!-- Renewal Without Provider -->
                                                    <tr>
                                                        <td style="text-align: center">Renewal</td>
                                                        <td style="text-align: center"><?= $no_provider_data['renewal']['sold'] ?></td>
                                                        <td style="text-align: center"><?= number_format($no_provider_data['renewal']['units'], 2) ?></td>
                                                        <td style="text-align: center; font-size: 11px;">
                                                            <?= !empty($no_provider_data['renewal']['enrollments']) ? implode(', ', $no_provider_data['renewal']['enrollments']) : 'None' ?>
                                                        </td>
                                                    </tr>

                                                    <!-- No Provider Totals -->
                                                    <tr style="background-color: #f8d7da;">
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

                                    <!-- Update Summary Statistics to use consistent unit calculations -->
                                    <div class="table-responsive mt-4">
                                        <table class="table table-bordered table-sm" style="background-color: #d4edda;">
                                            <thead>
                                                <tr>
                                                    <th colspan="6" style="text-align: center; font-weight: bold; font-size: 14px;">TOTAL SOLD COUNTS & UNITS</th>
                                                </tr>
                                                <tr>
                                                    <th style="text-align: center">Enrollment Type</th>
                                                    <th style="text-align: center">Pre Original</th>
                                                    <th style="text-align: center">Original</th>
                                                    <th style="text-align: center">Extension</th>
                                                    <th style="text-align: center">Renewal</th>
                                                    <th style="text-align: center">Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Sold Row -->
                                                <tr>
                                                    <td style="text-align: center; font-weight: bold">Total Sold</td>
                                                    <td style="text-align: center"><?= $total_pre_original_sold ?></td>
                                                    <td style="text-align: center"><?= $total_original_sold ?></td>
                                                    <td style="text-align: center"><?= $total_extension_sold ?></td>
                                                    <td style="text-align: center"><?= $total_renewal_sold ?></td>
                                                    <td style="text-align: center; font-weight: bold"><?= $total_all_sold ?></td>
                                                </tr>
                                                <!-- Units Row -->
                                                <tr>
                                                    <td style="text-align: center; font-weight: bold">Total Units</td>
                                                    <td style="text-align: center"><?= number_format($total_pre_original_units, 2) ?></td>
                                                    <td style="text-align: center"><?= number_format($total_original_units, 2) ?></td>
                                                    <td style="text-align: center"><?= number_format($total_extension_units, 2) ?></td>
                                                    <td style="text-align: center"><?= number_format($total_renewal_units, 2) ?></td>
                                                    <td style="text-align: center; font-weight: bold"><?= number_format($total_all_units_studio, 2) ?></td>
                                                </tr>
                                                <!-- <tr>
                                                        <td style="text-align: center; font-weight: bold">Total All Enrollments</td>
                                                        <td style="text-align: center" colspan="4"></td>
                                                        <td style="text-align: center; font-weight: bold"><?= $total_all_enrollments ?></td>
                                                    </tr> -->
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Update the Summary Statistics to use the studio report totals -->
                                    <!-- <div class="table-responsive mt-4">
                                            <table class="table table-bordered table-sm" style="background-color: #e9ecef;">
                                                <thead>
                                                    <tr>
                                                        <th colspan="2" style="text-align: center; font-weight: bold; font-size: 14px;">SUMMARY STATISTICS</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    // Calculate totals from provider data
                                                    $total_enrollments_with_providers = 0;
                                                    $total_provider_units = 0;
                                                    foreach ($all_providers_data as $provider) {
                                                        $total_enrollments_with_providers += $provider['pre_original']['sold'] + $provider['original']['sold'] + $provider['extension']['sold'] + $provider['renewal']['sold'];
                                                        $total_provider_units += $provider['pre_original']['units'] + $provider['original']['units'] + $provider['extension']['units'] + $provider['renewal']['units'];
                                                    }

                                                    // Use studio report totals for comparison
                                                    $total_units_all_sold = $total_all_units_studio;
                                                    $total_covered_units = $total_provider_units + $total_no_provider_units;
                                                    $coverage_percentage = $total_units_all_sold > 0 ? ($total_covered_units / $total_units_all_sold) * 100 : 0;
                                                    ?>

                                                    // Your existing summary rows... 
                                                    <tr>
                                                        <td style="text-align: right; font-weight: bold">Total Units (Studio Summary):</td>
                                                        <td style="text-align: left"><?= number_format($total_units_all_sold, 2) ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td style="text-align: right; font-weight: bold">Total Units Covered:</td>
                                                        <td style="text-align: left; font-weight: bold">
                                                            <?= number_format($total_covered_units, 2) . " (" . number_format($coverage_percentage, 2) . "%)" ?>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div> -->
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

        // Form validation
        $('#reportForm').on('submit', function(e) {
            var startDate = $('#START_DATE').val();
            var endDate = $('#END_DATE').val();

            // Validate dates are filled
            if (!startDate || !endDate) {
                alert('Please select both start date and end date.');
                e.preventDefault();
                return false;
            }

            // Validate date range
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