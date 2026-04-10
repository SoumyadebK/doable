<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;

$title = "SERVICES OF LESSONS TAUGHT BY SERVICE PROVIDER REPORT";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

$type = isset($_GET['type']) ? $_GET['type'] : (isset($_GET['view']) ? 'view' : '');
$include_no_provider = isset($_GET['include_no_provider']) ? $_GET['include_no_provider'] : 0;

$from_date = date('Y-m-d', strtotime($_GET['start_date']));
$to_date = date('Y-m-d', strtotime($_GET['end_date']));

// Fix for handling multiple service provider IDs
if (isset($_GET['PK_USER']) && is_array($_GET['PK_USER'])) {
    $service_provider_id = implode(',', array_map('intval', $_GET['PK_USER']));
} elseif (isset($_GET['service_provider_id']) && !empty($_GET['service_provider_id'])) {
    $service_provider_id = $_GET['service_provider_id'];
} else {
    $service_provider_id = '';
}

// Fix for handling multiple service IDs
if (isset($_GET['PK_SERVICE_MASTER']) && is_array($_GET['PK_SERVICE_MASTER'])) {
    $service_master_id = implode(',', array_map('intval', $_GET['PK_SERVICE_MASTER']));
} elseif (isset($_GET['PK_SERVICE_MASTER']) && !empty($_GET['PK_SERVICE_MASTER'])) {
    $service_master_id = $_GET['PK_SERVICE_MASTER'];
} else {
    $service_master_id = '';
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
    $report_name = 'services_of_lessons_taught_by_service_provider_report';
    $WEEK_NUMBER = isset($_GET['WEEK_NUMBER']) ? explode(' ', $_GET['WEEK_NUMBER'])[2] : '';
    $START_DATE = date('Y-m-d', strtotime($_GET['START_DATE']));
    $END_DATE = date('Y-m-d', strtotime($_GET['END_DATE']));

    // Handle PK_USER array
    $PK_USER = isset($_GET['PK_USER']) ? $_GET['PK_USER'] : '';
    $PK_USER_STRING = '';
    if (is_array($PK_USER)) {
        $PK_USER_STRING = implode(',', array_map('intval', $PK_USER));
    } elseif (!empty($PK_USER)) {
        $PK_USER_STRING = $PK_USER;
    }

    // Handle PK_SERVICE_MASTER array
    $PK_SERVICE_MASTER = isset($_GET['PK_SERVICE_MASTER']) ? $_GET['PK_SERVICE_MASTER'] : '';
    $PK_SERVICE_MASTER_STRING = '';
    if (is_array($PK_SERVICE_MASTER)) {
        $PK_SERVICE_MASTER_STRING = implode(',', array_map('intval', $PK_SERVICE_MASTER));
    } elseif (!empty($PK_SERVICE_MASTER)) {
        $PK_SERVICE_MASTER_STRING = $PK_SERVICE_MASTER;
    }

    $include_no_provider = isset($_GET['include_no_provider']) ? 1 : 0;

    if ($generate_pdf === 1) {
        header('location:generate_report_pdf.php?week_number=' . $WEEK_NUMBER . '&start_date=' . $START_DATE . '&end_date=' . $END_DATE . '&report_type=' . $report_name . '&PK_USER=' . $PK_USER_STRING . '&PK_SERVICE_MASTER=' . $PK_SERVICE_MASTER_STRING);
        exit;
    } elseif ($generate_excel === 1) {
        header('location:excel_' . $report_name . '.php?week_number=' . $WEEK_NUMBER . '&start_date=' . $START_DATE . '&end_date=' . $END_DATE . '&report_type=' . $report_name . '&PK_USER=' . $PK_USER_STRING . '&PK_SERVICE_MASTER=' . $PK_SERVICE_MASTER_STRING);
        exit;
    } else {
        header('location:services_of_lessons_taught_by_service_provider_report.php?week_number=' . $WEEK_NUMBER . '&start_date=' . $START_DATE . '&end_date=' . $END_DATE . '&type=' . $type . '&service_provider_id=' . $PK_USER_STRING . '&PK_SERVICE_MASTER=' . $PK_SERVICE_MASTER_STRING . '&include_no_provider=' . $include_no_provider);
        exit;
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
                                        <div class="col-3">
                                            <div id="services" style="width: 100%;">
                                                <select class="multi_select_services" multiple id="service_master_select" name="PK_SERVICE_MASTER[]">
                                                    <?php
                                                    // Convert service_master_id string to array for selection check
                                                    $selected_service_ids = [];
                                                    if (!empty($service_master_id) && $service_master_id != '0') {
                                                        $selected_service_ids = explode(',', $service_master_id);
                                                    }

                                                    $service_query = $db_account->Execute("SELECT DISTINCT PK_SERVICE_MASTER, SERVICE_NAME FROM DOA_SERVICE_MASTER WHERE PK_LOCATION IN ( " . $_SESSION['DEFAULT_LOCATION_ID'] . " ) AND ACTIVE = 1 ORDER BY SERVICE_NAME");
                                                    if ($service_query && $service_query->RecordCount() > 0) {
                                                        while (!$service_query->EOF) {
                                                            $service_id = $service_query->fields['PK_SERVICE_MASTER'];
                                                            $selected = in_array($service_id, $selected_service_ids) ? 'selected' : '';
                                                    ?>
                                                            <option value="<?php echo $service_id; ?>" <?= $selected ?>>
                                                                <?= htmlspecialchars($service_query->fields['SERVICE_NAME']) ?>
                                                            </option>
                                                    <?php
                                                            $service_query->MoveNext();
                                                        }
                                                    } else {
                                                        echo '<option value="">No Services Found</option>';
                                                    }
                                                    ?>
                                                </select>
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
                                        <div class="col-2">
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
                <?php } else if ($type === 'view' && !empty($service_master_id) && $service_master_id != '0') { ?>
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
                                    // Get selected services information
                                    $selected_services = [];
                                    if (!empty($service_master_id) && $service_master_id != '0') {
                                        $service_ids_array = explode(',', $service_master_id);
                                        if (!empty($service_ids_array)) {
                                            $sanitized_ids = array_map('intval', $service_ids_array);
                                            $ids_string = implode(',', $sanitized_ids);
                                            $service_info_query = $db_account->Execute("SELECT PK_SERVICE_MASTER, SERVICE_NAME FROM DOA_SERVICE_MASTER WHERE PK_SERVICE_MASTER IN ($ids_string) ORDER BY SERVICE_NAME");
                                            if ($service_info_query && $service_info_query->RecordCount() > 0) {
                                                while (!$service_info_query->EOF) {
                                                    $selected_services[$service_info_query->fields['PK_SERVICE_MASTER']] = $service_info_query->fields['SERVICE_NAME'];
                                                    $service_info_query->MoveNext();
                                                }
                                            }
                                        }
                                    }

                                    // Get service providers
                                    $provider_filter = "";
                                    if (!empty($service_provider_id) && $service_provider_id != '0') {
                                        $provider_ids_array = explode(',', $service_provider_id);
                                        $sanitized_provider_ids = array_map('intval', $provider_ids_array);
                                        $provider_ids_string = implode(',', $sanitized_provider_ids);
                                        $provider_filter = " AND DU.PK_USER IN ($provider_ids_string)";
                                    }

                                    $providers_query = $db->Execute("
                                        SELECT DISTINCT DU.PK_USER, CONCAT(DU.FIRST_NAME, ' ', DU.LAST_NAME) AS PROVIDER_NAME
                                        FROM DOA_USERS DU
                                        INNER JOIN DOA_USER_ROLES DUR ON DU.PK_USER = DUR.PK_USER
                                        WHERE DU.ACTIVE = 1 
                                        AND DUR.PK_ROLES = 5
                                        AND DU.PK_ACCOUNT_MASTER = '" . $_SESSION['PK_ACCOUNT_MASTER'] . "'
                                        $provider_filter
                                        ORDER BY DU.FIRST_NAME, DU.LAST_NAME
                                    ");

                                    $all_providers_data = [];
                                    $grand_totals = [];

                                    // Initialize grand totals for selected services
                                    foreach ($selected_services as $service_id => $service_name) {
                                        $service_key = 'service_' . $service_id;
                                        $grand_totals[$service_key] = [
                                            'service_name' => $service_name,
                                            'lessons' => 0,
                                            'customers' => 0
                                        ];
                                    }
                                    $grand_totals['total_lessons'] = 0;
                                    $grand_totals['total_customers'] = 0;

                                    if ($providers_query && $providers_query->RecordCount() > 0) {
                                        while (!$providers_query->EOF) {
                                            $provider_id = $providers_query->fields['PK_USER'];
                                            $provider_name = $providers_query->fields['PROVIDER_NAME'];

                                            $provider_data = [
                                                'name' => $provider_name,
                                                'services' => [],
                                                'total_lessons' => 0,
                                                'total_customers' => 0
                                            ];

                                            foreach ($selected_services as $service_id => $service_name) {
                                                $provider_data['services'][$service_id] = [
                                                    'service_name' => $service_name,
                                                    'lessons' => 0,
                                                    'customers' => [],
                                                    'sessions' => []
                                                ];
                                            }

                                            foreach ($selected_services as $service_id => $service_name) {
                                                $sql = "SELECT
                                                            am.PK_APPOINTMENT_MASTER,
                                                            am.DATE,
                                                            ac.PK_USER_MASTER,
                                                            CONCAT(cd.PARTNER_FIRST_NAME, ' ', cd.PARTNER_LAST_NAME) AS PARTNER_NAME,
                                                            CONCAT(us.FIRST_NAME, ' ', us.LAST_NAME) AS CUSTOMER_NAME,
                                                            sm.SERVICE_NAME,
                                                            sc.IS_GROUP
                                                        FROM DOA_APPOINTMENT_MASTER am
                                                        INNER JOIN DOA_APPOINTMENT_SERVICE_PROVIDER asp ON am.PK_APPOINTMENT_MASTER = asp.PK_APPOINTMENT_MASTER
                                                        LEFT JOIN DOA_APPOINTMENT_CUSTOMER ac ON am.PK_APPOINTMENT_MASTER = ac.PK_APPOINTMENT_MASTER
                                                        LEFT JOIN DOA_ENROLLMENT_SERVICE es ON am.PK_ENROLLMENT_SERVICE = es.PK_ENROLLMENT_SERVICE
                                                        LEFT JOIN DOA_MASTER.DOA_USER_MASTER dc ON ac.PK_USER_MASTER = dc.PK_USER_MASTER
                                                        LEFT JOIN DOA_MASTER.DOA_USERS us ON us.PK_USER = dc.PK_USER
                                                        LEFT JOIN DOA_SERVICE_MASTER sm ON sm.PK_SERVICE_MASTER = am.PK_SERVICE_MASTER
                                                        LEFT JOIN DOA_SERVICE_CODE sc ON am.PK_SERVICE_CODE = sc.PK_SERVICE_CODE
                                                        LEFT JOIN DOA_CUSTOMER_DETAILS cd ON ac.PK_USER_MASTER = cd.PK_USER_MASTER
                                                        WHERE DATE(am.DATE) BETWEEN '$from_date' AND '$to_date'
                                                        AND am.PK_APPOINTMENT_STATUS = 2
                                                        AND asp.PK_USER = $provider_id
                                                        AND am.PK_SERVICE_MASTER = $service_id
                                                        ORDER BY am.DATE";

                                                $lessons_query = $db_account->Execute($sql);
                                                $lesson_count = 0;
                                                $unique_customers = [];
                                                $session_details = [];
                                                $processed_appointments = [];

                                                if ($lessons_query && $lessons_query->RecordCount() > 0) {
                                                    while (!$lessons_query->EOF) {
                                                        $appointment_id = $lessons_query->fields['PK_APPOINTMENT_MASTER'];
                                                        $num_sessions = ($appointment_id) ? 1 : 0; // Assuming each record represents one session, adjust if needed
                                                        $customer_id = $lessons_query->fields['PK_USER_MASTER'];
                                                        $customer_name = $lessons_query->fields['CUSTOMER_NAME'] ? $lessons_query->fields['CUSTOMER_NAME'] : 'Unknown';
                                                        $partner_name = (!empty($lessons_query->fields['PARTNER_NAME']) && trim($lessons_query->fields['PARTNER_NAME']) !== '')
                                                            ? " (Partner: " . $lessons_query->fields['PARTNER_NAME'] . ")"
                                                            : '';
                                                        $service_date = $lessons_query->fields['DATE'];

                                                        $lesson_count += $num_sessions;

                                                        if ($customer_id > 0 && !in_array($customer_id, $unique_customers)) {
                                                            $unique_customers[] = $customer_id;
                                                        }

                                                        if (!in_array($appointment_id, $processed_appointments)) {
                                                            $session_details[] = date('m/d/Y', strtotime($service_date)) . " - " . $customer_name . $partner_name . " (" . $num_sessions . " lesson" . ($num_sessions > 1 ? 's' : '') . ")";
                                                            $processed_appointments[] = $appointment_id;
                                                        }

                                                        $lessons_query->MoveNext();
                                                    }
                                                }

                                                $provider_data['services'][$service_id]['lessons'] = $lesson_count;
                                                $provider_data['services'][$service_id]['customers'] = $unique_customers;
                                                $provider_data['services'][$service_id]['sessions'] = $session_details;

                                                $provider_data['total_lessons'] += $lesson_count;
                                                $provider_data['total_customers'] += count($unique_customers);

                                                $service_key = 'service_' . $service_id;
                                                $grand_totals[$service_key]['lessons'] += $lesson_count;
                                                $grand_totals[$service_key]['customers'] += count($unique_customers);
                                                $grand_totals['total_lessons'] += $lesson_count;
                                                $grand_totals['total_customers'] += count($unique_customers);
                                            }

                                            if ($provider_data['total_lessons'] > 0) {
                                                $all_providers_data[] = $provider_data;
                                            }
                                            $providers_query->MoveNext();
                                        }
                                    }
                                    ?>

                                    <?php if (!empty($all_providers_data) && !empty($selected_services)): ?>
                                        <!-- Summary Totals Table -->
                                        <div class="table-responsive mt-4">
                                            <table class="table table-bordered table-sm" style="background-color: #d4edda;">
                                                <thead>
                                                    <tr>
                                                        <th colspan="<?= (count($selected_services) * 2) + 1 ?>" style="text-align: center; font-weight: bold; font-size: 14px;">SUMMARY TOTALS</th>
                                                    </tr>
                                                    <tr>
                                                        <th style="text-align: center">Service Type</th>
                                                        <?php foreach ($selected_services as $service_id => $service_name): ?>
                                                            <th style="text-align: center" colspan="2"><?= htmlspecialchars($service_name) ?></th>
                                                        <?php endforeach; ?>
                                                    </tr>
                                                    <tr>
                                                        <th style="text-align: center">&nbsp;</th>
                                                        <?php foreach ($selected_services as $service_id => $service_name): ?>
                                                            <th style="text-align: center">Lessons</th>
                                                            <th style="text-align: center">Customers</th>
                                                        <?php endforeach; ?>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr style="background-color: #e8f5e9;">
                                                        <td style="text-align: center; font-weight: bold">Total</td>
                                                        <?php foreach ($selected_services as $service_id => $service_name): ?>
                                                            <?php $service_key = 'service_' . $service_id; ?>
                                                            <td style="text-align: center"><?= $grand_totals[$service_key]['lessons'] ?></td>
                                                            <td style="text-align: center"><?= $grand_totals[$service_key]['customers'] ?></td>
                                                        <?php endforeach; ?>
                                                    </tr>
                                                    <tr style="background-color: #c8e6c9; font-weight: bold;">
                                                        <td style="text-align: center; font-weight: bold">GRAND TOTAL</td>
                                                        <td style="text-align: center" colspan="<?= (count($selected_services) * 2) ?>">
                                                            Total Lessons: <?= $grand_totals['total_lessons'] ?> | Total Customers: <?= $grand_totals['total_customers'] ?>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>

                                        <!-- Display each provider's table - FIXED VERSION -->
                                        <?php foreach ($all_providers_data as $provider_data): ?>
                                            <div class="table-responsive mt-4">
                                                <table class="table table-bordered">
                                                    <thead>
                                                        <tr>
                                                            <th style="text-align: center; vertical-align:auto; font-weight: bold; background-color: #e9ecef;" colspan="4"><?= htmlspecialchars($provider_data['name']) ?></th>
                                                        </tr>
                                                        <tr>
                                                            <th style="width:20%; text-align: center">Service Type</th>
                                                            <th style="width:15%; text-align: center">Lessons Taught</th>
                                                            <th style="width:15%; text-align: center">Customers Served</th>
                                                            <th style="width:50%; text-align: center">Session Details</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($selected_services as $service_id => $service_name): ?>
                                                            <tr>
                                                                <td style="text-align: center"><?= htmlspecialchars($service_name) ?></td>
                                                                <td style="text-align: center"><?= $provider_data['services'][$service_id]['lessons'] ?></td>
                                                                <td style="text-align: center"><?= count($provider_data['services'][$service_id]['customers']) ?></td>
                                                                <td style="text-align: left; font-size: 10px; padding: 2px 5px;">
                                                                    <?php
                                                                    $sessions = $provider_data['services'][$service_id]['sessions'];
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
                                                        <?php endforeach; ?>

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
                                    <?php else: ?>
                                        <div class="alert alert-info mt-4">
                                            No data found for the selected criteria. Please try different date range or service selections.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } else if ($type === 'view' && (empty($service_master_id) || $service_master_id == '0')) { ?>
                    <div class="row">
                        <div class="col-12">
                            <div class="alert alert-warning">
                                Please select at least one service to view the report.
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

        $('.multi_select_services').SumoSelect({
            placeholder: 'Select Services',
            selectAll: true,
            triggerChangeCombined: true,
            search: true,
            searchText: 'Search Services...'
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
            var selectedServices = $('#service_master_select').val();

            if (!startDate || !endDate) {
                alert('Please select both start date and end date.');
                e.preventDefault();
                return false;
            }

            if (!selectedServices || selectedServices.length === 0) {
                alert('Please select at least one service.');
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