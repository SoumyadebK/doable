<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;

$title = "LESSONS TAUGHT BY DEPARTMENT";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

$type = $_GET['type'];
$service_provider_id = $_GET['service_provider_id'];
$from_date = date('Y-m-d', strtotime($_GET['start_date']));
$to_date = date('Y-m-d', strtotime($_GET['end_date']));

// Use the same date format as Studio Business Report
$date_condition = "'$from_date' AND '$to_date'";

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

// Get data for each service provider using UNIT-BASED calculation (matching Studio Business)
$provider_data = [];
$lessons_total = 0;
$interview_total = 0;
$renewal_total = 0;

if ($service_provider_id) {
    $providers = explode(',', $service_provider_id);

    foreach ($providers as $provider_id) {
        // Get provider name
        $provider_info = $db->Execute("SELECT CONCAT(FIRST_NAME, ' ', LAST_NAME) AS NAME FROM DOA_USERS WHERE PK_USER = $provider_id");
        $provider_name = $provider_info->fields['NAME'] ?? 'Unknown';

        // USE UNIT-BASED CALCULATION (Same as Studio Business Report)
        $provider_query = "SELECT 
            CONCAT(
                COALESCE(SUM(CASE WHEN W.DATE <= M.twelfth_date OR M.twelfth_date IS NULL THEN W.total_units ELSE 0 END), 0),
                '/',
                COALESCE(SUM(CASE WHEN W.DATE > M.twelfth_date THEN W.total_units ELSE 0 END), 0)
            ) AS INTERVIEW_RENEWAL_COUNT,
            COALESCE(SUM(W.total_units), 0) as total_units
        FROM (
            SELECT 
                AC.PK_USER_MASTER,
                SUM(SC.UNIT) AS total_lessons,
                SUBSTRING_INDEX(SUBSTRING_INDEX(GROUP_CONCAT(AM.DATE ORDER BY AM.DATE SEPARATOR ','), ',', 12), ',', -1) AS twelfth_date 
            FROM DOA_APPOINTMENT_CUSTOMER AC 
            INNER JOIN DOA_APPOINTMENT_MASTER AM ON AC.PK_APPOINTMENT_MASTER = AM.PK_APPOINTMENT_MASTER 
            INNER JOIN DOA_SCHEDULING_CODE SC ON AM.PK_SCHEDULING_CODE = SC.PK_SCHEDULING_CODE 
            WHERE AM.STATUS = 'A' 
                AND AM.PK_APPOINTMENT_STATUS IN (1,2,3,5,7,8) 
                AND AM.APPOINTMENT_TYPE IN ('NORMAL','AD-HOC')
                AND AM.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ")
            GROUP BY AC.PK_USER_MASTER 
            HAVING total_lessons >= 12
        ) AS M 
        RIGHT JOIN (
            SELECT 
                AC.PK_USER_MASTER,
                AM.DATE,
                SUM(SC.UNIT) AS total_units
            FROM DOA_APPOINTMENT_CUSTOMER AC 
            INNER JOIN DOA_APPOINTMENT_MASTER AM ON AC.PK_APPOINTMENT_MASTER = AM.PK_APPOINTMENT_MASTER 
            INNER JOIN DOA_SCHEDULING_CODE SC ON AM.PK_SCHEDULING_CODE = SC.PK_SCHEDULING_CODE 
            INNER JOIN DOA_SERVICE_CODE SVC ON AM.PK_SERVICE_CODE = SVC.PK_SERVICE_CODE
            INNER JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ASP ON AM.PK_APPOINTMENT_MASTER = ASP.PK_APPOINTMENT_MASTER
            WHERE AM.STATUS = 'A' 
                AND AM.PK_APPOINTMENT_STATUS IN (1,2,3,5,7,8) 
                AND AM.APPOINTMENT_TYPE IN ('NORMAL','AD-HOC') 
                AND SVC.IS_GROUP = 0
                AND AM.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ")
                AND AM.DATE BETWEEN $date_condition
                AND ASP.PK_USER = $provider_id
            GROUP BY AC.PK_USER_MASTER, AM.DATE
        ) AS W USING (PK_USER_MASTER)";

        $provider_result = $db_account->Execute($provider_query);
        $provider_counts = explode('/', $provider_result->fields['INTERVIEW_RENEWAL_COUNT'] ?? '0/0');

        $provider_interview = intval($provider_counts[0] ?? 0);
        $provider_renewal = intval($provider_counts[1] ?? 0);
        $provider_total = $provider_interview + $provider_renewal;
        $provider_units = $provider_result->fields['total_units'] ?? 0;

        $provider_data[] = [
            'name' => $provider_name,
            'total' => $provider_total,
            'interview' => $provider_interview,
            'renewal' => $provider_renewal,
            'units' => $provider_units
        ];

        $lessons_total += $provider_total;
        $interview_total += $provider_interview;
        $renewal_total += $provider_renewal;
    }
}

if (!empty($_GET['START_DATE'])) {
    $type = isset($_GET['view']) ? 'view' : 'export';
    $generate_pdf = isset($_GET['generate_pdf']) ? 1 : 0;
    $generate_excel = isset($_GET['generate_excel']) ? 1 : 0;
    $report_name = 'lessons_taught_by_department_report';
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
        header('location:lessons_taught_by_department_report.php?week_number=' . $WEEK_NUMBER . '&start_date=' . $START_DATE . '&end_date=' . $END_DATE . '&type=' . $type . '&service_provider_id=' . implode(',', $PK_USER) . '&include_no_provider=' . $include_no_provider);
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
                                        <!-- <div class="col-2">
                                            <div class="no-provider-checkbox" id="no_provider_checkbox">
                                                <input type="checkbox" id="include_no_provider" name="include_no_provider" value="1" <?= isset($_GET['include_no_provider']) && $_GET['include_no_provider'] == 1 ? 'checked' : '' ?>>
                                                <label for="include_no_provider">With No Service Provider</label>
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
                                        <div class="col-md-3 text-left">
                                            <img src="../assets/images/background/doable_logo.png" style="margin-bottom:-35px; height: 60px; width: auto;">
                                        </div>
                                        <div class="col-md-6 text-center">
                                            <h3 class="card-title" style="padding-bottom:15px; text-align: center; font-weight: bold"><?= $title ?></h3>
                                        </div>
                                        <div class="col-md-3 text-center">
                                            <h6 class="card-title" style="padding-bottom:15px; text-align: center; font-weight: bold">(<?= date('m/d/Y', strtotime($from_date)) ?> - <?= date('m/d/Y', strtotime($to_date)) ?>)</h6>
                                        </div>
                                    </div>

                                    <div class="table-responsive">
                                        <table id="myTable" class="table table-bordered" data-page-length='50'>
                                            <thead>
                                                <tr>
                                                    <th style="width:50%; text-align: center; vertical-align:auto; font-weight: bold" colspan="5">
                                                        <?= ($account_data->fields['FRANCHISE'] == 1) ? 'Franchisee: ' : '' ?><?= $business_name . " (" . $concatenatedResults . ")" ?>
                                                    </th>
                                                </tr>
                                                <tr>
                                                    <th style="text-align: center;">Service Provider</th>
                                                    <th style="text-align: center;">Total Units</th>
                                                    <th style="text-align: center;">Pvt Intv (Front)</th>
                                                    <th style="text-align: center;">Pvt Ren (Back)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($provider_data as $provider): ?>
                                                    <tr>
                                                        <td style="text-align: center;"><?= $provider['name'] ?></td>
                                                        <td style="text-align: center;"><?= $provider['units'] ?></td>
                                                        <td style="text-align: center;"><?= $provider['interview'] ?></td>
                                                        <td style="text-align: center;"><?= $provider['renewal'] ?></td>
                                                    </tr>
                                                <?php endforeach; ?>

                                                <tr style="background-color: #f8f9fa;">
                                                    <th style="text-align: center;">Total</th>
                                                    <th style="text-align: center;"><?= array_sum(array_column($provider_data, 'units')) ?></th>
                                                    <th style="text-align: center;"><?= $interview_total ?></th>
                                                    <th style="text-align: center;"><?= $renewal_total ?></th>
                                                </tr>
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