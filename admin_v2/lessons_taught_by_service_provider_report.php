<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;

$title = "LESSONS TAUGHT BY SERVICE PROVIDER REPORT";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

$type = $_GET['type'];
$include_no_provider = isset($_GET['include_no_provider']) ? $_GET['include_no_provider'] : 0;

$from_date = date('Y-m-d', strtotime($_GET['start_date']));
$to_date = date('Y-m-d', strtotime($_GET['end_date']));

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
    $report_name = 'lessons_taught_by_service_provider_report';
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
        header('location:lessons_taught_by_service_provider_report.php?week_number=' . $WEEK_NUMBER . '&start_date=' . $START_DATE . '&end_date=' . $END_DATE . '&type=' . $type . '&service_provider_id=' . implode(',', $PK_USER) . '&include_no_provider=' . $include_no_provider);
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
                                        <div class="col-2">
                                            <div id="location" style="width: 100%;">
                                                <select class="multi_select_service_provider" multiple id="service_provider_select" name="PK_USER[]">
                                                    <?php
                                                    // Convert service_provider_id string to array for selection check
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
                                        <!-- <div class="col-2">
                                            <div class="no-provider-checkbox" id="no_provider_checkbox">
                                                <input type="checkbox" id="include_no_provider" name="include_no_provider" value="1" <?= isset($_GET['include_no_provider']) && $_GET['include_no_provider'] == 1 ? 'checked' : '' ?>>
                                                <label for="include_no_provider">Include Lessons Without Provider</label>
                                            </div>
                                        </div> -->
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
                                            <h5 class="card-title" style="padding-bottom:15px; text-align: center; font-weight: bold"><?= $concatenatedResults ?></h5>
                                        </div>
                                        <div class="col-md-3 text-center">
                                            <h6 class="card-title" style="padding-bottom:15px; text-align: center; font-weight: bold">(<?= date('m/d/Y', strtotime($from_date)) ?> - <?= date('m/d/Y', strtotime($to_date)) ?>)</h6>
                                        </div>
                                    </div>

                                    <?php
                                    // Get enrollment types mapping
                                    $enrollment_types = [
                                        5 => 'Pre Original',
                                        2 => 'Original',
                                        9 => 'Extension',
                                        13 => 'Renewal'
                                    ];

                                    // Get all service providers
                                    $providers_query = $db->Execute("
                                        SELECT DISTINCT DU.PK_USER, CONCAT(DU.FIRST_NAME, ' ', DU.LAST_NAME) AS PROVIDER_NAME
                                        FROM DOA_USERS DU
                                        INNER JOIN DOA_USER_ROLES DUR ON DU.PK_USER = DUR.PK_USER
                                        WHERE DU.ACTIVE = 1 
                                        AND DUR.PK_ROLES = 5
                                        AND DU.PK_ACCOUNT_MASTER = '" . $_SESSION['PK_ACCOUNT_MASTER'] . "'
                                        ORDER BY DU.FIRST_NAME, DU.LAST_NAME
                                    ");

                                    $all_providers_data = [];
                                    $grand_totals = [
                                        'pre_original' => ['lessons' => 0, 'customers' => 0],
                                        'original' => ['lessons' => 0, 'customers' => 0],
                                        'extension' => ['lessons' => 0, 'customers' => 0],
                                        'renewal' => ['lessons' => 0, 'customers' => 0],
                                        'group' => ['lessons' => 0, 'customers' => 0],
                                        'to_do' => ['lessons' => 0, 'customers' => 0],
                                        'total_lessons' => 0,
                                        'total_customers' => 0
                                    ];

                                    while (!$providers_query->EOF) {
                                        $provider_id = $providers_query->fields['PK_USER'];
                                        $provider_name = $providers_query->fields['PROVIDER_NAME'];

                                        // Skip if specific providers are selected and this one isn't in the list
                                        if (!empty($service_provider_id) && $service_provider_id != '0' && !in_array($provider_id, explode(',', $service_provider_id))) {
                                            $providers_query->MoveNext();
                                            continue;
                                        }

                                        // Initialize provider data
                                        $provider_data = [
                                            'name' => $provider_name,
                                            'pre_original' => ['lessons' => 0, 'customers' => [], 'sessions' => []],
                                            'original' => ['lessons' => 0, 'customers' => [], 'sessions' => []],
                                            'extension' => ['lessons' => 0, 'customers' => [], 'sessions' => []],
                                            'renewal' => ['lessons' => 0, 'customers' => [], 'sessions' => []],
                                            'group' => ['lessons' => 0, 'customers' => 0, 'sessions' => []],
                                            'to_do' => ['lessons' => 0, 'customers' => 0, 'sessions' => []]
                                        ];

                                        // Get private lessons for this provider by enrollment type
                                        foreach ($enrollment_types as $type_id => $type_name) {
                                            $type_key = strtolower(str_replace(' ', '_', $type_name));

                                            // Query for lessons taught - CORRECTED
                                            $lessons_query = $db_account->Execute("
                                                SELECT
                                                    am.PK_APPOINTMENT_MASTER,
                                                    am.DATE,
                                                    es.NUMBER_OF_SESSION,
                                                    ac.PK_USER_MASTER,
                                                    CONCAT(cd.PARTNER_FIRST_NAME, ' ', cd.PARTNER_LAST_NAME) AS PARTNER_NAME,
                                                    CONCAT(us.FIRST_NAME, ' ', us.LAST_NAME) AS CUSTOMER_NAME,
                                                    sm.SERVICE_NAME
                                                FROM DOA_APPOINTMENT_MASTER am
                                                INNER JOIN DOA_APPOINTMENT_SERVICE_PROVIDER asp ON am.PK_APPOINTMENT_MASTER = asp.PK_APPOINTMENT_MASTER
                                                LEFT JOIN DOA_APPOINTMENT_CUSTOMER ac ON am.PK_APPOINTMENT_MASTER = ac.PK_APPOINTMENT_MASTER
                                                LEFT JOIN DOA_ENROLLMENT_SERVICE es ON am.PK_ENROLLMENT_SERVICE = es.PK_ENROLLMENT_SERVICE
                                                LEFT JOIN DOA_ENROLLMENT_MASTER em ON am.PK_ENROLLMENT_MASTER = em.PK_ENROLLMENT_MASTER
                                                LEFT JOIN DOA_MASTER.DOA_USER_MASTER dc ON ac.PK_USER_MASTER = dc.PK_USER_MASTER
                                                LEFT JOIN DOA_MASTER.DOA_USERS us ON us.PK_USER = dc.PK_USER
                                                LEFT JOIN DOA_SERVICE_MASTER sm ON sm.PK_SERVICE_MASTER = am.PK_SERVICE_MASTER
                                                LEFT JOIN DOA_SERVICE_CODE sc ON am.PK_SERVICE_CODE = sc.PK_SERVICE_CODE
                                                LEFT JOIN DOA_CUSTOMER_DETAILS cd ON ac.PK_USER_MASTER = cd.PK_USER_MASTER
                                                WHERE DATE(am.DATE) BETWEEN '$from_date' AND '$to_date'
                                                AND am.PK_APPOINTMENT_STATUS = 2
                                                AND asp.PK_USER = $provider_id
                                                AND em.PK_ENROLLMENT_TYPE = $type_id
                                                AND (sc.IS_GROUP = 0 OR sc.IS_GROUP IS NULL)
                                                ORDER BY am.DATE
                                            ");

                                            $lesson_count = 0;
                                            $unique_customers = [];
                                            $session_details = [];
                                            $processed_appointments = [];

                                            while (!$lessons_query->EOF) {
                                                $appointment_id = $lessons_query->fields['PK_APPOINTMENT_MASTER'];
                                                $num_sessions = ($appointment_id) ? 1 : 0; // Assuming each record represents one session, adjust if needed
                                                $customer_id = $lessons_query->fields['PK_USER_MASTER'];
                                                $customer_name = $lessons_query->fields['CUSTOMER_NAME'] ? $lessons_query->fields['CUSTOMER_NAME'] : 'Unknown';
                                                $partner_name = (!empty($lessons_query->fields['PARTNER_NAME']) && trim($lessons_query->fields['PARTNER_NAME']) !== '')
                                                    ? " (Partner: " . $lessons_query->fields['PARTNER_NAME'] . ")"
                                                    : '';
                                                $service_date = $lessons_query->fields['DATE'];

                                                // Count lessons (use NUMBER_OF_SESSION which can be > 1)
                                                $lesson_count += $num_sessions;

                                                // Track unique customers
                                                if ($customer_id > 0 && !in_array($customer_id, $unique_customers)) {
                                                    $unique_customers[] = $customer_id;
                                                }

                                                // Store session details (only once per appointment)
                                                if (!in_array($appointment_id, $processed_appointments)) {
                                                    $session_details[] = date('m/d/Y', strtotime($service_date)) . " - " . $customer_name . $partner_name . " (" . $num_sessions . " lesson" . ($num_sessions > 1 ? 's' : '') . ")";
                                                    $processed_appointments[] = $appointment_id;
                                                }

                                                $lessons_query->MoveNext();
                                            }

                                            $provider_data[$type_key]['lessons'] = $lesson_count;
                                            $provider_data[$type_key]['customers'] = $unique_customers;
                                            $provider_data[$type_key]['sessions'] = $session_details;

                                            // Add to grand totals
                                            $grand_totals[$type_key]['lessons'] += $lesson_count;
                                            $grand_totals[$type_key]['customers'] += count($unique_customers);
                                        }

                                        // Get group lessons for this provider - CORRECTED
                                        $group_query = $db_account->Execute("
                                            SELECT
                                                am.PK_APPOINTMENT_MASTER,
                                                am.DATE,
                                                es.NUMBER_OF_SESSION,
                                                COUNT(DISTINCT ac.PK_USER_MASTER) AS ATTENDEE_COUNT,
                                                sm.SERVICE_NAME
                                            FROM DOA_APPOINTMENT_MASTER am
                                            INNER JOIN DOA_APPOINTMENT_SERVICE_PROVIDER asp ON am.PK_APPOINTMENT_MASTER = asp.PK_APPOINTMENT_MASTER
                                            LEFT JOIN DOA_APPOINTMENT_CUSTOMER ac ON am.PK_APPOINTMENT_MASTER = ac.PK_APPOINTMENT_MASTER
                                            LEFT JOIN DOA_ENROLLMENT_SERVICE es ON am.PK_ENROLLMENT_SERVICE = es.PK_ENROLLMENT_SERVICE
                                            LEFT JOIN DOA_ENROLLMENT_MASTER em ON am.PK_ENROLLMENT_MASTER = em.PK_ENROLLMENT_MASTER
                                            LEFT JOIN DOA_SERVICE_MASTER sm ON sm.PK_SERVICE_MASTER = am.PK_SERVICE_MASTER
                                            LEFT JOIN DOA_SERVICE_CODE sc ON am.PK_SERVICE_CODE = sc.PK_SERVICE_CODE
                                            WHERE DATE(am.DATE) BETWEEN '$from_date' AND '$to_date'
                                            AND am.PK_APPOINTMENT_STATUS = 2
                                            AND asp.PK_USER = $provider_id
                                            AND sc.IS_GROUP = 1
                                            GROUP BY am.PK_APPOINTMENT_MASTER, am.DATE, es.NUMBER_OF_SESSION, sm.SERVICE_NAME
                                            ORDER BY am.DATE
                                        ");

                                        $group_lesson_count = 0;
                                        $group_customer_count = 0;
                                        $group_details = [];

                                        while (!$group_query->EOF) {
                                            $num_sessions = $group_query->fields['NUMBER_OF_SESSION'] ? $group_query->fields['NUMBER_OF_SESSION'] : 1;
                                            $attendee_count = $group_query->fields['ATTENDEE_COUNT'] ? $group_query->fields['ATTENDEE_COUNT'] : 0;
                                            $service_date = $group_query->fields['DATE'];
                                            $service_name = $group_query->fields['SERVICE_NAME'] ? $group_query->fields['SERVICE_NAME'] : 'Group Lesson';

                                            $group_lesson_count += $num_sessions;
                                            $group_customer_count += $attendee_count;

                                            $group_details[] = date('m/d/Y', strtotime($service_date)) . " - " . $service_name . ": " . $num_sessions . " session" . ($num_sessions > 1 ? 's' : '') . " (" . $attendee_count . " attendees)";

                                            $group_query->MoveNext();
                                        }

                                        $provider_data['group']['lessons'] = $group_lesson_count;
                                        $provider_data['group']['customers'] = $group_customer_count;
                                        $provider_data['group']['sessions'] = $group_details;

                                        // Add to grand totals for group
                                        $grand_totals['group']['lessons'] += $group_lesson_count;
                                        $grand_totals['group']['customers'] += $group_customer_count;

                                        // Get to-do lessons for this provider
                                        $to_do_query = $db_account->Execute("
                                            SELECT
                                                am.PK_SPECIAL_APPOINTMENT,
                                                am.TITLE,
                                                COUNT(DISTINCT ac.PK_USER_MASTER) AS ATTENDEE_COUNT,
                                                sc.SCHEDULING_NAME,
                                                sc.SCHEDULING_CODE,
                                                am.DATE
                                            FROM DOA_SPECIAL_APPOINTMENT am
                                            INNER JOIN DOA_SPECIAL_APPOINTMENT_USER asp ON am.PK_SPECIAL_APPOINTMENT = asp.PK_SPECIAL_APPOINTMENT
                                            LEFT JOIN DOA_SPECIAL_APPOINTMENT_CUSTOMER ac ON am.PK_SPECIAL_APPOINTMENT = ac.PK_SPECIAL_APPOINTMENT
                                            LEFT JOIN DOA_SCHEDULING_CODE sc ON am.PK_SCHEDULING_CODE = sc.PK_SCHEDULING_CODE
                                            WHERE DATE(am.DATE) BETWEEN '$from_date' AND '$to_date'
                                            AND am.PK_APPOINTMENT_STATUS = 2
                                            AND asp.PK_USER = $provider_id
                                            GROUP BY am.PK_SPECIAL_APPOINTMENT, am.DATE
                                            ORDER BY am.DATE
                                        ");

                                        $to_do_lesson_count = 0;
                                        $to_do_customer_count = 0;
                                        $to_do_details = [];

                                        while (!$to_do_query->EOF) {
                                            $num_sessions = 1; // Assuming each record represents one session, adjust if needed
                                            $attendee_count = $to_do_query->fields['ATTENDEE_COUNT'] ? $to_do_query->fields['ATTENDEE_COUNT'] : 0;
                                            $service_date = $to_do_query->fields['DATE'];
                                            $service_name = $to_do_query->fields['SCHEDULING_NAME'] ? $to_do_query->fields['SCHEDULING_NAME'] : 'To Do Lesson';
                                            $scheduling_code = $to_do_query->fields['SCHEDULING_CODE'] ? $to_do_query->fields['SCHEDULING_CODE'] : '';

                                            $to_do_lesson_count += $num_sessions;
                                            $to_do_customer_count += $attendee_count;

                                            $to_do_details[] = date('m/d/Y', strtotime($service_date)) . " - " . $service_name . " (". $scheduling_code . "): " . $num_sessions . " session" . ($num_sessions > 1 ? 's' : '') . " (" . $attendee_count . " attendees)";

                                            $to_do_query->MoveNext();
                                        }

                                        $provider_data['to_do']['lessons'] = $to_do_lesson_count;
                                        $provider_data['to_do']['customers'] = $to_do_customer_count;
                                        $provider_data['to_do']['sessions'] = $to_do_details;

                                        // Add to grand totals for to-do
                                        $grand_totals['to_do']['lessons'] += $to_do_lesson_count;
                                        $grand_totals['to_do']['customers'] += $to_do_customer_count;

                                        // Calculate provider totals (convert customer arrays to counts)
                                        $provider_total_lessons = $provider_data['pre_original']['lessons'] +
                                            $provider_data['original']['lessons'] +
                                            $provider_data['extension']['lessons'] +
                                            $provider_data['renewal']['lessons'] +
                                            $group_lesson_count + $to_do_lesson_count;

                                        $provider_total_customers = count($provider_data['pre_original']['customers']) +
                                            count($provider_data['original']['customers']) +
                                            count($provider_data['extension']['customers']) +
                                            count($provider_data['renewal']['customers']) +
                                            $group_customer_count + $to_do_customer_count;

                                        $provider_data['total_lessons'] = $provider_total_lessons;
                                        $provider_data['total_customers'] = $provider_total_customers;

                                        // Convert customer arrays to counts for display
                                        $provider_data['pre_original']['customer_count'] = count($provider_data['pre_original']['customers']);
                                        $provider_data['original']['customer_count'] = count($provider_data['original']['customers']);
                                        $provider_data['extension']['customer_count'] = count($provider_data['extension']['customers']);
                                        $provider_data['renewal']['customer_count'] = count($provider_data['renewal']['customers']);

                                        $all_providers_data[] = $provider_data;

                                        $providers_query->MoveNext();
                                    }

                                    // Calculate grand totals
                                    $grand_totals['total_lessons'] = $grand_totals['pre_original']['lessons'] +
                                        $grand_totals['original']['lessons'] +
                                        $grand_totals['extension']['lessons'] +
                                        $grand_totals['renewal']['lessons'] +
                                        $grand_totals['group']['lessons'] +
                                        $grand_totals['to_do']['lessons'];

                                    $grand_totals['total_customers'] = $grand_totals['pre_original']['customers'] +
                                        $grand_totals['original']['customers'] +
                                        $grand_totals['extension']['customers'] +
                                        $grand_totals['renewal']['customers'] +
                                        $grand_totals['group']['customers'] +
                                        $grand_totals['to_do']['customers'];
                                    ?>

                                    <!-- Summary Totals Table -->
                                    <div class="table-responsive mt-4">
                                        <table class="table table-bordered table-sm" style="background-color: #d4edda;">
                                            <thead>
                                                <tr>
                                                    <th colspan="7" style="text-align: center; font-weight: bold; font-size: 14px;">SUMMARY TOTALS</th>
                                                </tr>
                                                <tr>
                                                    <th style="text-align: center">Enrollment Type</th>
                                                    <th style="text-align: center">Pre Original</th>
                                                    <th style="text-align: center">Original</th>
                                                    <th style="text-align: center">Extension</th>
                                                    <th style="text-align: center">Renewal</th>
                                                    <th style="text-align: center">Group</th>
                                                    <th style="text-align: center">To Do</th>
                                                    <th style="text-align: center">TOTAL</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td style="text-align: center; font-weight: bold">Total Lessons</td>
                                                    <td style="text-align: center"><?= $grand_totals['pre_original']['lessons'] ?></td>
                                                    <td style="text-align: center"><?= $grand_totals['original']['lessons'] ?></td>
                                                    <td style="text-align: center"><?= $grand_totals['extension']['lessons'] ?></td>
                                                    <td style="text-align: center"><?= $grand_totals['renewal']['lessons'] ?></td>
                                                    <td style="text-align: center"><?= $grand_totals['group']['lessons'] ?></td>
                                                    <td style="text-align: center"><?= $grand_totals['to_do']['lessons'] ?></td>
                                                    <td style="text-align: center; font-weight: bold"><?= $grand_totals['total_lessons'] ?></td>
                                                </tr>
                                                <tr>
                                                    <td style="text-align: center; font-weight: bold">Total Customers</td>
                                                    <td style="text-align: center"><?= $grand_totals['pre_original']['customers'] ?></td>
                                                    <td style="text-align: center"><?= $grand_totals['original']['customers'] ?></td>
                                                    <td style="text-align: center"><?= $grand_totals['extension']['customers'] ?></td>
                                                    <td style="text-align: center"><?= $grand_totals['renewal']['customers'] ?></td>
                                                    <td style="text-align: center"><?= $grand_totals['group']['customers'] ?></td>
                                                    <td style="text-align: center"><?= $grand_totals['to_do']['customers'] ?></td>
                                                    <td style="text-align: center; font-weight: bold"><?= $grand_totals['total_customers'] ?></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Display each provider's table -->
                                    <?php foreach ($all_providers_data as $provider_data): ?>
                                        <div class="table-responsive mt-4">
                                            <table class="table table-bordered" data-page-length='50'>
                                                <thead>
                                                    <tr>
                                                        <th style="width:60%; text-align: center; vertical-align:auto; font-weight: bold; background-color: #e9ecef;" colspan="4"><?= $provider_data['name'] ?></th>
                                                    </tr>
                                                    <tr>
                                                        <th style="width:12%; text-align: center">Enrollment Type</th>
                                                        <th style="width:8%; text-align: center">Lessons Taught</th>
                                                        <th style="width:8%; text-align: center">Customers Served</th>
                                                        <th style="width:32%; text-align: center">Session Details</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <!-- Pre Original -->
                                                    <tr>
                                                        <td style="text-align: center">Pre Original</td>
                                                        <td style="text-align: center"><?= $provider_data['pre_original']['lessons'] ?></td>
                                                        <td style="text-align: center"><?= $provider_data['pre_original']['customer_count'] ?></td>
                                                        <td style="text-align: left; font-size: 10px; padding: 2px 5px;">
                                                            <?php
                                                            $sessions = $provider_data['pre_original']['sessions'];
                                                            if (!empty($sessions)) {
                                                                echo '<ul style="margin: 0; padding-left: 15px;">';
                                                                $display_count = 0;
                                                                foreach ($sessions as $session) {
                                                                    if ($display_count < 100) {
                                                                        echo '<li>' . htmlspecialchars($session) . '</li>';
                                                                    } elseif ($display_count == 100) {
                                                                        echo '<li>... and ' . (count($sessions) - 100) . ' more</li>';
                                                                        break;
                                                                    }
                                                                    $display_count++;
                                                                }
                                                                echo '</ul>';
                                                            } else {
                                                                echo 'None';
                                                            }
                                                            ?>
                                                        </td>
                                                    </tr>

                                                    <!-- Original -->
                                                    <tr>
                                                        <td style="text-align: center">Original</td>
                                                        <td style="text-align: center"><?= $provider_data['original']['lessons'] ?></td>
                                                        <td style="text-align: center"><?= $provider_data['original']['customer_count'] ?></td>
                                                        <td style="text-align: left; font-size: 10px; padding: 2px 5px;">
                                                            <?php
                                                            $sessions = $provider_data['original']['sessions'];
                                                            if (!empty($sessions)) {
                                                                echo '<ul style="margin: 0; padding-left: 15px;">';
                                                                $display_count = 0;
                                                                foreach ($sessions as $session) {
                                                                    if ($display_count < 100) {
                                                                        echo '<li>' . htmlspecialchars($session) . '</li>';
                                                                    } elseif ($display_count == 100) {
                                                                        echo '<li>... and ' . (count($sessions) - 100) . ' more</li>';
                                                                        break;
                                                                    }
                                                                    $display_count++;
                                                                }
                                                                echo '</ul>';
                                                            } else {
                                                                echo 'None';
                                                            }
                                                            ?>
                                                        </td>
                                                    </tr>

                                                    <!-- Extension -->
                                                    <tr>
                                                        <td style="text-align: center">Extension</td>
                                                        <td style="text-align: center"><?= $provider_data['extension']['lessons'] ?></td>
                                                        <td style="text-align: center"><?= $provider_data['extension']['customer_count'] ?></td>
                                                        <td style="text-align: left; font-size: 10px; padding: 2px 5px;">
                                                            <?php
                                                            $sessions = $provider_data['extension']['sessions'];
                                                            if (!empty($sessions)) {
                                                                echo '<ul style="margin: 0; padding-left: 15px;">';
                                                                $display_count = 0;
                                                                foreach ($sessions as $session) {
                                                                    if ($display_count < 100) {
                                                                        echo '<li>' . htmlspecialchars($session) . '</li>';
                                                                    } elseif ($display_count == 100) {
                                                                        echo '<li>... and ' . (count($sessions) - 100) . ' more</li>';
                                                                        break;
                                                                    }
                                                                    $display_count++;
                                                                }
                                                                echo '</ul>';
                                                            } else {
                                                                echo 'None';
                                                            }
                                                            ?>
                                                        </td>
                                                    </tr>

                                                    <!-- Renewal -->
                                                    <tr>
                                                        <td style="text-align: center">Renewal</td>
                                                        <td style="text-align: center"><?= $provider_data['renewal']['lessons'] ?></td>
                                                        <td style="text-align: center"><?= $provider_data['renewal']['customer_count'] ?></td>
                                                        <td style="text-align: left; font-size: 10px; padding: 2px 5px;">
                                                            <?php
                                                            $sessions = $provider_data['renewal']['sessions'];
                                                            if (!empty($sessions)) {
                                                                echo '<ul style="margin: 0; padding-left: 15px;">';
                                                                $display_count = 0;
                                                                foreach ($sessions as $session) {
                                                                    if ($display_count < 100) {
                                                                        echo '<li>' . htmlspecialchars($session) . '</li>';
                                                                    } elseif ($display_count == 100) {
                                                                        echo '<li>... and ' . (count($sessions) - 100) . ' more</li>';
                                                                        break;
                                                                    }
                                                                    $display_count++;
                                                                }
                                                                echo '</ul>';
                                                            } else {
                                                                echo 'None';
                                                            }
                                                            ?>
                                                        </td>
                                                    </tr>

                                                    <!-- Group Lessons -->
                                                    <tr style="background-color: #fff3cd;">
                                                        <td style="text-align: center; font-weight: bold">Group Lessons</td>
                                                        <td style="text-align: center; font-weight: bold"><?= $provider_data['group']['lessons'] ?></td>
                                                        <td style="text-align: center; font-weight: bold"><?= $provider_data['group']['customers'] ?></td>
                                                        <td style="text-align: left; font-size: 10px; padding: 2px 5px;">
                                                            <?php
                                                            $group_sessions = $provider_data['group']['sessions'];
                                                            if (!empty($group_sessions)) {
                                                                echo '<ul style="margin: 0; padding-left: 15px;">';
                                                                $display_count = 0;
                                                                foreach ($group_sessions as $session) {
                                                                    if ($display_count < 100) {
                                                                        echo '<li>' . htmlspecialchars($session) . '</li>';
                                                                    } elseif ($display_count == 100) {
                                                                        echo '<li>... and ' . (count($group_sessions) - 100) . ' more</li>';
                                                                        break;
                                                                    }
                                                                    $display_count++;
                                                                }
                                                                echo '</ul>';
                                                            } else {
                                                                echo 'None';
                                                            }
                                                            ?>
                                                        </td>
                                                    </tr>

                                                    <tr style="background-color: #EDFFFE;">
                                                        <td style="text-align: center; font-weight: bold">To Do</td>
                                                        <td style="text-align: center; font-weight: bold"><?= $provider_data['to_do']['lessons'] ?></td>
                                                        <td style="text-align: center; font-weight: bold"><?= $provider_data['to_do']['customers'] ?></td>
                                                        <td style="text-align: left; font-size: 10px; padding: 2px 5px;">
                                                            <?php
                                                            $group_sessions = $provider_data['to_do']['sessions'];
                                                            if (!empty($group_sessions)) {
                                                                echo '<ul style="margin: 0; padding-left: 15px;">';
                                                                $display_count = 0;
                                                                foreach ($group_sessions as $session) {
                                                                    if ($display_count < 100) {
                                                                        echo '<li>' . htmlspecialchars($session) . '</li>';
                                                                    } elseif ($display_count == 100) {
                                                                        echo '<li>... and ' . (count($group_sessions) - 100) . ' more</li>';
                                                                        break;
                                                                    }
                                                                    $display_count++;
                                                                }
                                                                echo '</ul>';
                                                            } else {
                                                                echo 'None';
                                                            }
                                                            ?>
                                                        </td>
                                                    </tr>

                                                    <!-- Provider Totals -->
                                                    <tr style="background-color: #f8f9fa; font-weight: bold;">
                                                        <td style="text-align: center">PROVIDER TOTAL</td>
                                                        <td style="text-align: center"><?= $provider_data['total_lessons'] ?></td>
                                                        <td style="text-align: center"><?= $provider_data['total_customers'] ?></td>
                                                        <td style="text-align: center">Total Lessons: <?= $provider_data['total_lessons'] ?> | Total Customers: <?= $provider_data['total_customers'] ?></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endforeach; ?>

                                    <?php
                                    // Handle no provider lessons if checkbox is checked
                                    if ($include_no_provider == 1) {
                                        // Get lessons with no service provider - CORRECTED
                                        $no_provider_query = $db_account->Execute("
                                            SELECT 
                                                am.PK_APPOINTMENT_MASTER,
                                                am.DATE,
                                                es.NUMBER_OF_SESSION,
                                                em.PK_ENROLLMENT_TYPE,
                                                ac.PK_USER_MASTER,
                                                CONCAT(us.FIRST_NAME, ' ', us.LAST_NAME) AS CUSTOMER_NAME,
                                                sm.SERVICE_NAME,
                                                sc.IS_GROUP,
                                                (SELECT COUNT(DISTINCT ac2.PK_USER_MASTER) 
                                                 FROM DOA_APPOINTMENT_CUSTOMER ac2 
                                                 WHERE ac2.PK_APPOINTMENT_MASTER = am.PK_APPOINTMENT_MASTER) AS ATTENDEE_COUNT
                                            FROM DOA_APPOINTMENT_MASTER am
                                            LEFT JOIN DOA_APPOINTMENT_CUSTOMER ac ON am.PK_APPOINTMENT_MASTER = ac.PK_APPOINTMENT_MASTER
                                            LEFT JOIN DOA_ENROLLMENT_SERVICE es ON am.PK_ENROLLMENT_SERVICE = es.PK_ENROLLMENT_SERVICE
                                            LEFT JOIN DOA_ENROLLMENT_MASTER em ON am.PK_ENROLLMENT_MASTER = em.PK_ENROLLMENT_MASTER
                                            LEFT JOIN DOA_MASTER.DOA_USER_MASTER dc ON ac.PK_USER_MASTER = dc.PK_USER_MASTER
                                            LEFT JOIN DOA_MASTER.DOA_USERS us ON us.PK_USER = dc.PK_USER
                                            LEFT JOIN DOA_SERVICE_MASTER sm ON sm.PK_SERVICE_MASTER = am.PK_SERVICE_MASTER
                                            LEFT JOIN DOA_SERVICE_CODE sc ON am.PK_SERVICE_CODE = sc.PK_SERVICE_CODE
                                            WHERE DATE(am.DATE) BETWEEN '$from_date' AND '$to_date'
                                            AND am.PK_APPOINTMENT_STATUS = 2
                                            AND NOT EXISTS (
                                                SELECT 1 FROM DOA_APPOINTMENT_SERVICE_PROVIDER asp 
                                                WHERE asp.PK_APPOINTMENT_MASTER = am.PK_APPOINTMENT_MASTER
                                            )
                                            ORDER BY am.DATE
                                        ");

                                        if ($no_provider_query && $no_provider_query->RecordCount() > 0) {
                                            $no_provider_private = [
                                                'pre_original' => ['lessons' => 0, 'customers' => [], 'sessions' => []],
                                                'original' => ['lessons' => 0, 'customers' => [], 'sessions' => []],
                                                'extension' => ['lessons' => 0, 'customers' => [], 'sessions' => []],
                                                'renewal' => ['lessons' => 0, 'customers' => [], 'sessions' => []]
                                            ];
                                            $no_provider_group = ['lessons' => 0, 'customers' => 0, 'sessions' => []];
                                            $processed_group_appointments = [];

                                            while (!$no_provider_query->EOF) {
                                                $type_id = $no_provider_query->fields['PK_ENROLLMENT_TYPE'];
                                                $is_group = $no_provider_query->fields['IS_GROUP'];
                                                $num_sessions = $no_provider_query->fields['NUMBER_OF_SESSION'] ? $no_provider_query->fields['NUMBER_OF_SESSION'] : 1;
                                                $attendee_count = $no_provider_query->fields['ATTENDEE_COUNT'] ? $no_provider_query->fields['ATTENDEE_COUNT'] : 0;
                                                $customer_id = $no_provider_query->fields['PK_USER_MASTER'];
                                                $customer_name = $no_provider_query->fields['CUSTOMER_NAME'] ? $no_provider_query->fields['CUSTOMER_NAME'] : 'Unknown';
                                                $service_date = $no_provider_query->fields['DATE'];
                                                $service_name = $no_provider_query->fields['SERVICE_NAME'] ? $no_provider_query->fields['SERVICE_NAME'] : 'Lesson';
                                                $appointment_id = $no_provider_query->fields['PK_APPOINTMENT_MASTER'];

                                                if ($is_group == 1) {
                                                    // For group lessons
                                                    if (!in_array($appointment_id, $processed_group_appointments)) {
                                                        $no_provider_group['lessons'] += $num_sessions;
                                                        $processed_group_appointments[] = $appointment_id;
                                                    }
                                                    $no_provider_group['customers'] += $attendee_count;

                                                    // Store session details (only once per appointment)
                                                    $session_key = $appointment_id . '_group';
                                                    if (!isset($no_provider_group['sessions'][$session_key])) {
                                                        $no_provider_group['sessions'][$session_key] = date('m/d/Y', strtotime($service_date)) . " - " . $service_name . ": " . $num_sessions . " session" . ($num_sessions > 1 ? 's' : '') . " (" . $attendee_count . " attendees)";
                                                    }
                                                } else {
                                                    $type_key = '';
                                                    switch ($type_id) {
                                                        case 5:
                                                            $type_key = 'pre_original';
                                                            break;
                                                        case 2:
                                                            $type_key = 'original';
                                                            break;
                                                        case 9:
                                                            $type_key = 'extension';
                                                            break;
                                                        case 13:
                                                            $type_key = 'renewal';
                                                            break;
                                                        default:
                                                            $no_provider_query->MoveNext();
                                                            continue 2;
                                                    }

                                                    $no_provider_private[$type_key]['lessons'] += $num_sessions;

                                                    // Track unique customers
                                                    if ($customer_id > 0 && !in_array($customer_id, $no_provider_private[$type_key]['customers'])) {
                                                        $no_provider_private[$type_key]['customers'][] = $customer_id;
                                                    }

                                                    // Store session details (only once per appointment)
                                                    $session_key = $appointment_id . '_' . $type_key;
                                                    if (!isset($no_provider_private[$type_key]['sessions'][$session_key])) {
                                                        $no_provider_private[$type_key]['sessions'][$session_key] = date('m/d/Y', strtotime($service_date)) . " - " . $customer_name . " (" . $num_sessions . " lesson" . ($num_sessions > 1 ? 's' : '') . ")";
                                                    }
                                                }

                                                $no_provider_query->MoveNext();
                                            }

                                            // Convert sessions arrays to simple lists
                                            foreach ($no_provider_private as $key => $data) {
                                                $no_provider_private[$key]['sessions'] = array_values($data['sessions']);
                                            }
                                            $no_provider_group['sessions'] = array_values($no_provider_group['sessions']);

                                            // Calculate totals
                                            $no_provider_total_lessons = 0;
                                            $no_provider_total_customers = 0;

                                            $no_provider_private_counts = [];
                                            foreach ($no_provider_private as $key => $data) {
                                                $customer_count = count($data['customers']);
                                                $no_provider_private_counts[$key] = [
                                                    'lessons' => $data['lessons'],
                                                    'customers' => $customer_count,
                                                    'sessions' => $data['sessions']
                                                ];
                                                $no_provider_total_lessons += $data['lessons'];
                                                $no_provider_total_customers += $customer_count;
                                            }

                                            $no_provider_total_lessons += $no_provider_group['lessons'];
                                            $no_provider_total_customers += $no_provider_group['customers'];

                                            // Display No Provider table
                                    ?>
                                            <div class="table-responsive mt-4">
                                                <table class="table table-bordered" data-page-length='50'>
                                                    <thead>
                                                        <tr>
                                                            <th style="width:60%; text-align: center; vertical-align:auto; font-weight: bold; background-color: #f8d7da;" colspan="4">LESSONS WITHOUT SERVICE PROVIDERS</th>
                                                        </tr>
                                                        <tr>
                                                            <th style="width:12%; text-align: center">Enrollment Type</th>
                                                            <th style="width:8%; text-align: center">Lessons Taught</th>
                                                            <th style="width:8%; text-align: center">Customers Served</th>
                                                            <th style="width:32%; text-align: center">Session Details</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php
                                                        $no_provider_types = [
                                                            'pre_original' => 'Pre Original',
                                                            'original' => 'Original',
                                                            'extension' => 'Extension',
                                                            'renewal' => 'Renewal'
                                                        ];

                                                        foreach ($no_provider_types as $key => $label):
                                                            if ($no_provider_private_counts[$key]['lessons'] > 0):
                                                        ?>
                                                                <tr>
                                                                    <td style="text-align: center"><?= $label ?></td>
                                                                    <td style="text-align: center"><?= $no_provider_private_counts[$key]['lessons'] ?></td>
                                                                    <td style="text-align: center"><?= $no_provider_private_counts[$key]['customers'] ?></td>
                                                                    <td style="text-align: left; font-size: 10px; padding: 2px 5px;">
                                                                        <ul style="margin: 0; padding-left: 15px;">
                                                                            <?php
                                                                            $display_count = 0;
                                                                            foreach ($no_provider_private_counts[$key]['sessions'] as $session):
                                                                                if ($display_count < 5):
                                                                            ?>
                                                                                    <li><?= htmlspecialchars($session) ?></li>
                                                                            <?php
                                                                                elseif ($display_count == 5):
                                                                                    echo '<li>... and ' . (count($no_provider_private_counts[$key]['sessions']) - 5) . ' more</li>';
                                                                                    break;
                                                                                endif;
                                                                                $display_count++;
                                                                            endforeach;
                                                                            ?>
                                                                        </ul>
                                                                    </td>
                                                                </tr>
                                                        <?php
                                                            endif;
                                                        endforeach;
                                                        ?>

                                                        <?php if ($no_provider_group['lessons'] > 0): ?>
                                                            <tr style="background-color: #fff3cd;">
                                                                <td style="text-align: center; font-weight: bold">Group Lessons</td>
                                                                <td style="text-align: center; font-weight: bold"><?= $no_provider_group['lessons'] ?></td>
                                                                <td style="text-align: center; font-weight: bold"><?= $no_provider_group['customers'] ?></td>
                                                                <td style="text-align: left; font-size: 10px; padding: 2px 5px;">
                                                                    <ul style="margin: 0; padding-left: 15px;">
                                                                        <?php
                                                                        $display_count = 0;
                                                                        foreach ($no_provider_group['sessions'] as $session):
                                                                            if ($display_count < 5):
                                                                        ?>
                                                                                <li><?= htmlspecialchars($session) ?></li>
                                                                        <?php
                                                                            elseif ($display_count == 5):
                                                                                echo '<li>... and ' . (count($no_provider_group['sessions']) - 5) . ' more</li>';
                                                                                break;
                                                                            endif;
                                                                            $display_count++;
                                                                        endforeach;
                                                                        ?>
                                                                    </ul>
                                                                </td>
                                                            </tr>
                                                        <?php endif; ?>

                                                        <tr style="background-color: #f8d7da; font-weight: bold;">
                                                            <td style="text-align: center">NO PROVIDER TOTAL</td>
                                                            <td style="text-align: center"><?= $no_provider_total_lessons ?></td>
                                                            <td style="text-align: center"><?= $no_provider_total_customers ?></td>
                                                            <td style="text-align: center">Total Lessons: <?= $no_provider_total_lessons ?> | Total Customers: <?= $no_provider_total_customers ?></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                    <?php
                                        }
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