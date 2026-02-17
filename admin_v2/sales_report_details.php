<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;

$title = "SALES REPORT";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

$type = $_GET['type'];

$from_date = date('Y-m-d', strtotime($_GET['start_date']));
$to_date = date('Y-m-d', strtotime($_GET['end_date']));
$service_provider_id = $_GET['service_provider_id'];

$selected_service_provider = [];
$selected_service_provider_name = [];
$selected_service_provider_row = $db->Execute("SELECT DISTINCT DOA_USERS.`PK_USER`, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME FROM `DOA_USERS` LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER WHERE DOA_USERS.PK_USER IN (" . $service_provider_id . ") AND DOA_USER_ROLES.`PK_ROLES` = 5");
while (!$selected_service_provider_row->EOF) {
    $selected_service_provider[] = $selected_service_provider_row->fields['PK_USER'];
    $selected_service_provider_name[] = $selected_service_provider_row->fields['NAME'];
    $selected_service_provider_row->MoveNext();
}

$row = $db->Execute("SELECT PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME FROM DOA_USERS WHERE ACTIVE = 1 AND PK_USER IN (" . implode(',', $selected_service_provider) . ")");
$totalResults = count($selected_service_provider_name);
$concatenatedServiceProviders = "";
foreach ($selected_service_provider_name as $key => $result) {
    // Append the current result to the concatenated string
    $concatenatedServiceProviders .= $result;

    // If it's not the last result, append a comma
    if ($key < $totalResults - 1) {
        $concatenatedServiceProviders .= ", ";
    }
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
    // Append the current result to the concatenated string
    $concatenatedResults .= $result;

    // If it's not the last result, append a comma
    if ($key < $totalResults - 1) {
        $concatenatedResults .= ", ";
    }
}

if (!empty($_GET['START_DATE'])) {
    $type = isset($_GET['view']) ? 'view' : 'export';
    $generate_pdf = isset($_GET['generate_pdf']) ? 1 : 0;
    $generate_excel = isset($_GET['generate_excel']) ? 1 : 0;
    $report_name = 'sales_report';
    $WEEK_NUMBER = explode(' ', $_GET['WEEK_NUMBER'])[2];
    $START_DATE = date('Y-m-d', strtotime($_GET['START_DATE']));
    $END_DATE = date('Y-m-d', strtotime($_GET['END_DATE']));
    $PK_USER = empty($_GET['PK_USER']) ? 0 : $_GET['PK_USER'];

    if ($generate_pdf === 1) {
        header('location:generate_report_pdf.php?week_number=' . $WEEK_NUMBER . '&start_date=' . $START_DATE . '&end_date=' . $END_DATE . '&report_type=' . $report_name . '&service_provider_id=' . implode(',', $PK_USER));
    } elseif ($generate_excel === 1) {
        header('location:excel_' . $report_name . '.php?week_number=' . $WEEK_NUMBER . '&start_date=' . $START_DATE . '&end_date=' . $END_DATE . '&report_type=' . $report_name . '&service_provider_id=' . implode(',', $PK_USER));
    } else {
        header('location:sales_report_details.php?week_number=' . $WEEK_NUMBER . '&start_date=' . $START_DATE . '&end_date=' . $END_DATE . '&type=' . $type . '&service_provider_id=' . implode(',', $PK_USER));
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
                                <li class="breadcrumb-item active"><a href="reports.php">Reports</a></li>
                                <li class="breadcrumb-item active"><a href="customer_summary_report.php"><?= $title ?></a></li>
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
                        echo "<h3>Data export to Arthur Murray API Successfully</h3>";
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
                                                    <th style="width:50%; text-align: center; vertical-align:auto; font-weight: bold" colspan="6"><?= ($account_data->fields['FRANCHISE'] == 1) ? 'Franchisee: ' : '' ?><?= " (" . $concatenatedResults . ")" . " (" . $concatenatedServiceProviders . ")" ?></th>
                                                    <th style="width:50%; text-align: center; font-weight: bold" colspan="3">(<?= date('m/d/Y', strtotime($from_date)) ?> - <?= date('m/d/Y', strtotime($to_date)) ?>)</th>
                                                </tr>
                                                <tr>
                                                    <th style="width:10%; text-align: center">Date</th>
                                                    <th style="width:10%; text-align: center">Student</th>
                                                    <th style="width:10%; text-align: center">Amount of Sale</th>
                                                    <th style="width:10%; text-align: center">Enrollment Name</th>
                                                    <th style="width:10%; text-align: center">Services</th>
                                                    <th style="width:10%; text-align: center">Executive</th>
                                                    <th style="width:12%; text-align: center">Teacher 1</th>
                                                    <th style="width:12%; text-align: center">Teacher 2</th>
                                                    <th style="width:12%; text-align: center">Teacher 3</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $i = 1;
                                                $total_amount = 0;

                                                // Build the service provider filter condition
                                                $service_provider_filter = "";
                                                if (!empty($service_provider_id)) {
                                                    $service_provider_filter = "AND DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER IN (
                                                        SELECT DISTINCT PK_ENROLLMENT_MASTER 
                                                        FROM DOA_ENROLLMENT_SERVICE_PROVIDER 
                                                        WHERE SERVICE_PROVIDER_ID IN (" . $service_provider_id . ")
                                                    )";
                                                }

                                                $row = $db_account->Execute("
                                                    SELECT 
                                                        DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER,
                                                        DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID,
                                                        DOA_ENROLLMENT_MASTER.ENROLLMENT_DATE AS DATE,
                                                        DOA_ENROLLMENT_MASTER.ENROLLMENT_NAME,
                                                        DOA_ENROLLMENT_MASTER.ENROLLMENT_ID,
                                                        DOA_ENROLLMENT_MASTER.MISC_ID,
                                                        CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS CLIENT,
                                                        DOA_ENROLLMENT_BILLING.TOTAL_AMOUNT AS TOTAL_AMOUNT,
                                                        'PAID' AS STATUS
                                                    FROM DOA_ENROLLMENT_MASTER
                                                    INNER JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER 
                                                        ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER
                                                    INNER JOIN $master_database.DOA_USERS AS DOA_USERS 
                                                        ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER
                                                    LEFT JOIN $master_database.DOA_LOCATION AS DOA_LOCATION 
                                                        ON DOA_LOCATION.PK_LOCATION = DOA_ENROLLMENT_MASTER.PK_LOCATION
                                                    LEFT JOIN DOA_ENROLLMENT_BILLING 
                                                        ON DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER
                                                    WHERE DOA_USERS.IS_DELETED = 0 
                                                    AND DOA_USERS.ACTIVE = 1
                                                    AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ")
                                                    AND DOA_ENROLLMENT_MASTER.ENROLLMENT_DATE BETWEEN '" . date('Y-m-d', strtotime($from_date)) . "' 
                                                        AND '" . date('Y-m-d', strtotime($to_date)) . "'
                                                    $service_provider_filter

                                                    UNION ALL

                                                    SELECT 
                                                        DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER,
                                                        DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID,
                                                        MAX(DOA_ENROLLMENT_CANCEL.CANCEL_DATE) AS DATE,
                                                        DOA_ENROLLMENT_MASTER.ENROLLMENT_NAME,
                                                        DOA_ENROLLMENT_MASTER.ENROLLMENT_ID,
                                                        DOA_ENROLLMENT_MASTER.MISC_ID,
                                                        CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS CLIENT,
                                                        SUM(DOA_ENROLLMENT_CANCEL.CANCEL_AMOUNT) AS TOTAL_AMOUNT,
                                                        'CANCELLED' AS STATUS
                                                    FROM DOA_ENROLLMENT_CANCEL
                                                    INNER JOIN DOA_ENROLLMENT_MASTER 
                                                        ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_CANCEL.PK_ENROLLMENT_MASTER
                                                    INNER JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER 
                                                        ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER
                                                    INNER JOIN $master_database.DOA_USERS AS DOA_USERS 
                                                        ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER
                                                    LEFT JOIN $master_database.DOA_LOCATION AS DOA_LOCATION 
                                                        ON DOA_LOCATION.PK_LOCATION = DOA_ENROLLMENT_MASTER.PK_LOCATION
                                                    WHERE DOA_USERS.IS_DELETED = 0 
                                                    AND DOA_USERS.ACTIVE = 1
                                                    AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ")
                                                    AND DOA_ENROLLMENT_CANCEL.CANCEL_DATE BETWEEN '" . date('Y-m-d', strtotime($from_date)) . "' 
                                                        AND '" . date('Y-m-d', strtotime($to_date)) . "'
                                                    $service_provider_filter
                                                    GROUP BY 
                                                        DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER,
                                                        DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID,
                                                        DOA_ENROLLMENT_MASTER.ENROLLMENT_NAME,
                                                        DOA_ENROLLMENT_MASTER.ENROLLMENT_ID,
                                                        DOA_ENROLLMENT_MASTER.MISC_ID,
                                                        DOA_USERS.FIRST_NAME, 
                                                        DOA_USERS.LAST_NAME

                                                    ORDER BY DATE DESC
                                                ");

                                                while (!$row->EOF) {
                                                    $enr_status = $row->fields['STATUS'];
                                                    $name = $row->fields['ENROLLMENT_NAME'];
                                                    $ENROLLMENT_ID = $row->fields['ENROLLMENT_ID'];
                                                    if (empty($name)) {
                                                        $enrollment_name = '';
                                                    } else {
                                                        $enrollment_name = "$name" . " - ";
                                                    }

                                                    $serviceCode = [];
                                                    if ($enr_status == 'CANCELLED') {
                                                        $total_amount -= $row->fields['TOTAL_AMOUNT'];
                                                        $serviceCodeData = $db_account->Execute("SELECT DOA_SERVICE_CODE.PK_SERVICE_CODE, DOA_SERVICE_CODE.SERVICE_CODE, DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION, DOA_ENROLLMENT_SERVICE.PRICE_PER_SESSION, DOA_ENROLLMENT_SERVICE.TOTAL_AMOUNT_PAID, DOA_ENROLLMENT_SERVICE.SESSION_CREATED, DOA_ENROLLMENT_SERVICE.SESSION_COMPLETED, DOA_ENROLLMENT_CANCEL.ACTUAL_AMOUNT, DOA_ENROLLMENT_CANCEL.CANCEL_AMOUNT FROM DOA_SERVICE_CODE JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE JOIN DOA_ENROLLMENT_CANCEL ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_SERVICE = DOA_ENROLLMENT_CANCEL.PK_ENROLLMENT_SERVICE WHERE DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = " . $row->fields['PK_ENROLLMENT_MASTER']);
                                                        $serviceCode = [];
                                                        while (!$serviceCodeData->EOF) {
                                                            $total_session = ($serviceCodeData->fields['PRICE_PER_SESSION'] > 0) ? ($serviceCodeData->fields['ACTUAL_AMOUNT'] / $serviceCodeData->fields['PRICE_PER_SESSION']) : 0;
                                                            $serviceCode[] = $serviceCodeData->fields['SERVICE_CODE'] . ': ' . ($total_session - $serviceCodeData->fields['NUMBER_OF_SESSION']);
                                                            $serviceCodeData->MoveNext();
                                                        }
                                                    } else {
                                                        $total_amount += $row->fields['TOTAL_AMOUNT'];
                                                        $serviceCodeData = $db_account->Execute("SELECT DOA_SERVICE_CODE.PK_SERVICE_CODE, DOA_SERVICE_CODE.SERVICE_CODE, DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION, DOA_ENROLLMENT_SERVICE.PRICE_PER_SESSION, DOA_ENROLLMENT_SERVICE.TOTAL_AMOUNT_PAID, DOA_ENROLLMENT_SERVICE.SESSION_CREATED, DOA_ENROLLMENT_SERVICE.SESSION_COMPLETED, DOA_ENROLLMENT_CANCEL.ACTUAL_AMOUNT, DOA_ENROLLMENT_CANCEL.CANCEL_AMOUNT FROM DOA_SERVICE_CODE JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE LEFT JOIN DOA_ENROLLMENT_CANCEL ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_SERVICE = DOA_ENROLLMENT_CANCEL.PK_ENROLLMENT_SERVICE WHERE DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = " . $row->fields['PK_ENROLLMENT_MASTER']);
                                                        while (!$serviceCodeData->EOF) {
                                                            if ($serviceCodeData->fields['ACTUAL_AMOUNT'] > 0) {
                                                                $total_session = ($serviceCodeData->fields['PRICE_PER_SESSION'] > 0) ? ($serviceCodeData->fields['ACTUAL_AMOUNT'] / $serviceCodeData->fields['PRICE_PER_SESSION']) : $serviceCodeData->fields['NUMBER_OF_SESSION'];
                                                            } else {
                                                                $total_session = $serviceCodeData->fields['NUMBER_OF_SESSION'];
                                                            }
                                                            $serviceCode[] = $serviceCodeData->fields['SERVICE_CODE'] . ': ' . $total_session;
                                                            $serviceCodeData->MoveNext();
                                                        }
                                                    }

                                                    $executive = $db->Execute("SELECT CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS EXECUTIVE FROM DOA_USERS WHERE PK_USER = " . $row->fields['ENROLLMENT_BY_ID']);

                                                    $results = $db_account->Execute("SELECT CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS SERVICE_PROVIDER, SERVICE_PROVIDER_PERCENTAGE FROM DOA_ENROLLMENT_SERVICE_PROVIDER LEFT JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USERS.PK_USER = DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = " . $row->fields['PK_ENROLLMENT_MASTER']);
                                                    $resultsArray = [];
                                                    while (!$results->EOF) {
                                                        $resultsArray[] = $results->fields['SERVICE_PROVIDER'] . ' (' . number_format($results->fields['SERVICE_PROVIDER_PERCENTAGE']) . '%)';
                                                        $results->MoveNext();
                                                    }
                                                ?>
                                                    <tr <?php if ($enr_status == 'CANCELLED') {
                                                            echo 'style="color: #f83e4dff;"';
                                                        } ?>>
                                                        <td style="text-align: center"><?= date('m/d/Y', strtotime($row->fields['DATE'])) ?></td>
                                                        <td style="text-align: center"><?= $row->fields['CLIENT'] ?></td>
                                                        <td style="text-align: right">$<?= number_format($row->fields['TOTAL_AMOUNT'], 2) ?></td>
                                                        <td style="text-align: center"><?= ($enrollment_name . $ENROLLMENT_ID == null) ? $enrollment_name . $row->fields['MISC_ID'] : $enrollment_name . $ENROLLMENT_ID ?></td>
                                                        <td style="text-align: center"><?= implode(', ', $serviceCode) ?></td>
                                                        <td style="text-align: center"><?= empty($executive->fields['EXECUTIVE']) ? '' : $executive->fields['EXECUTIVE'] ?></td>
                                                        <td style="text-align: center"><?= (isset($resultsArray[0]) && $resultsArray[0]) ? $resultsArray[0] : '' ?></td>
                                                        <td style="text-align: center"><?= (isset($resultsArray[1]) && $resultsArray[1]) ? $resultsArray[1] : '' ?></td>
                                                        <td style="text-align: center"><?= (isset($resultsArray[2]) && $resultsArray[2]) ? $resultsArray[2] : '' ?></td>
                                                    </tr>
                                                <?php $row->MoveNext();
                                                    $i++;
                                                } ?>
                                                <tr>
                                                    <th style="text-align: center; vertical-align:auto; font-weight: bold" colspan="2"></th>
                                                    <th style="text-align: right; vertical-align:auto; font-weight: bold" colspan="1">Total: $<?= number_format($total_amount, 2) ?></th>
                                                    <th style="text-align: center; vertical-align:auto; font-weight: bold" colspan="6"></th>
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