<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;

$title = "PROVIDER CASH REPORT";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

$type = $_GET['type'];

$from_date = date('Y-m-d', strtotime($_GET['start_date']));
$to_date = date('Y-m-d', strtotime($_GET['end_date']));
$service_provider_id = $_GET['service_provider_id'];

$payment_date = "AND DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (" . $service_provider_id . ") AND DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE BETWEEN '" . date('Y-m-d', strtotime($from_date)) . "' AND '" . date('Y-m-d', strtotime($to_date)) . "' GROUP BY SERVICE_PROVIDER_ID ORDER BY DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE DESC";

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
    $report_name = 'cash_report';
    $WEEK_NUMBER = explode(' ', $_GET['WEEK_NUMBER'])[2];
    $START_DATE = date('Y-m-d', strtotime($_GET['START_DATE']));
    $END_DATE = date('Y-m-d', strtotime($_GET['END_DATE']));
    $PK_USER = empty($_GET['PK_USER']) ? 0 : $_GET['PK_USER'];

    if ($generate_pdf === 1) {
        header('location:generate_report_pdf.php?week_number=' . $WEEK_NUMBER . '&start_date=' . $START_DATE . '&end_date=' . $END_DATE . '&report_type=' . $report_name . '&service_provider_id=' . implode(',', $PK_USER));
    } elseif ($generate_excel === 1) {
        header('location:excel_' . $report_name . '.php?week_number=' . $WEEK_NUMBER . '&start_date=' . $START_DATE . '&end_date=' . $END_DATE . '&report_type=' . $report_name . '&service_provider_id=' . implode(',', $PK_USER));
    } else {
        header('location:cash_report_details.php?week_number=' . $WEEK_NUMBER . '&start_date=' . $START_DATE . '&end_date=' . $END_DATE . '&type=' . $type . '&service_provider_id=' . implode(',', $PK_USER));
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
                    echo "<h3>Data export to Arthur Murray API Successfully</h3>";
                    /*$data = json_decode($post_data);
                if (isset($data->error)) {
                    echo '<div class="alert alert-danger alert-dismissible" role="alert">'.$data->error_description.'</div>';
                } elseif (isset($data->errors)) {
                    if (isset($data->errors->errors[0])) {
                        echo '<div class="alert alert-danger alert-dismissible" role="alert">' . $data->errors->errors[0] . '</div>';
                    } else {
                        echo '<div class="alert alert-danger alert-dismissible" role="alert">'.$data->message.'</div>';
                    }
                } else {
                    echo "<h3>Data export to Arthur Murray API Successfully</h3>";
                }*/
                } else { ?>
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
                                    $each_service_provider = $db_account->Execute("SELECT distinct DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID, DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_PERCENTAGE, DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, PAYMENT_DATE, AMOUNT, PAYMENT_INFO, PAYMENT_TYPE, RECEIPT_NUMBER, MEMO, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS CLIENT, ENROLLMENT_NAME, ENROLLMENT_ID, ENROLLMENT_TYPE, TOTAL_AMOUNT, ENROLLMENT_BY_ID FROM DOA_ENROLLMENT_PAYMENT INNER JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER INNER JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER INNER JOIN $master_database.DOA_PAYMENT_TYPE AS DOA_PAYMENT_TYPE ON DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE=DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE INNER JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER=DOA_USER_MASTER.PK_USER_MASTER INNER JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USER_MASTER.PK_USER=DOA_USERS.PK_USER INNER JOIN $master_database.DOA_ENROLLMENT_TYPE AS DOA_ENROLLMENT_TYPE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE=DOA_ENROLLMENT_TYPE.PK_ENROLLMENT_TYPE INNER JOIN DOA_ENROLLMENT_BILLING ON DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") " . $payment_date);
                                    while (!$each_service_provider->EOF) {
                                        $name = $db->Execute("SELECT CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS TEACHER FROM DOA_USERS WHERE DOA_USERS.PK_USER = " . $each_service_provider->fields['SERVICE_PROVIDER_ID']);
                                        $service_provider_id_per_table = $each_service_provider->fields['SERVICE_PROVIDER_ID'];
                                    ?>

                                        <div class="table-responsive">
                                            <table id="myTable" class="table table-bordered" data-page-length='50'>
                                                <thead>
                                                    <tr>

                                                        <th style="width:50%; text-align: center; vertical-align:auto; font-weight: bold" colspan="12"><?= $name->fields['TEACHER'] ?></th>

                                                    </tr>
                                                    <tr>
                                                        <th style="width:8%; text-align: center">Receipt #</th>
                                                        <th style="width:8%; text-align: center">Payment Date</th>
                                                        <th style="width:8%; text-align: center">Amount</th>
                                                        <th style="width:10%; text-align: center">Student Name</th>
                                                        <th style="width:8%; text-align: center">Type</th>
                                                        <th style="width:5%; text-align: center">ENR ID</th>
                                                        <th style="width:10%; text-align: center">Enrollment</th>
                                                        <th style="width:10%; text-align: center">Enrollment Type</th>
                                                        <th style="width:10%; text-align: center">Units/Total Cost</th>
                                                        <th style="width:8%; text-align: center">Portion</th>
                                                        <th style="width:5%; text-align: center">%</th>
                                                        <th style="width:8%; text-align: center">Comment/Remark</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    $total_portion = 0;
                                                    $total_amount = 0;
                                                    $total_refund = 0;
                                                    $total_refund_amount = 0;
                                                    $row = $db_account->Execute("SELECT DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID, DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_PERCENTAGE, DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, DOA_ENROLLMENT_PAYMENT.TYPE, PAYMENT_DATE, AMOUNT, PAYMENT_INFO, PAYMENT_TYPE, RECEIPT_NUMBER, MEMO, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS CLIENT, ENROLLMENT_NAME, ENROLLMENT_ID, MISC_ID, ENROLLMENT_TYPE, TOTAL_AMOUNT, ENROLLMENT_BY_ID FROM DOA_ENROLLMENT_PAYMENT INNER JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER INNER JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER INNER JOIN $master_database.DOA_PAYMENT_TYPE AS DOA_PAYMENT_TYPE ON DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE=DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE INNER JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER=DOA_USER_MASTER.PK_USER_MASTER INNER JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USER_MASTER.PK_USER=DOA_USERS.PK_USER INNER JOIN $master_database.DOA_ENROLLMENT_TYPE AS DOA_ENROLLMENT_TYPE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE=DOA_ENROLLMENT_TYPE.PK_ENROLLMENT_TYPE INNER JOIN DOA_ENROLLMENT_BILLING ON DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE (DOA_ENROLLMENT_PAYMENT.TYPE = 'Payment' || DOA_ENROLLMENT_PAYMENT.TYPE = 'Refund') AND DOA_ENROLLMENT_PAYMENT.NOT_EXPORT_TO_AMI = 0 AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (" . $service_provider_id_per_table . ") AND DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE BETWEEN '" . date('Y-m-d', strtotime($from_date)) . "' AND '" . date('Y-m-d', strtotime($to_date)) . "' ORDER BY DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE DESC");
                                                    while (!$row->EOF) {
                                                        $sessions = $db_account->Execute("SELECT NUMBER_OF_SESSION FROM DOA_ENROLLMENT_SERVICE WHERE PK_ENROLLMENT_MASTER = " . $row->fields['PK_ENROLLMENT_MASTER']);
                                                        $units = $sessions->fields['NUMBER_OF_SESSION'] ?? 0;
                                                        $service_provider = $db->Execute("SELECT CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS TEACHER FROM DOA_USERS WHERE DOA_USERS.PK_USER = " . $row->fields['SERVICE_PROVIDER_ID']);
                                                        $portion = $row->fields['AMOUNT'] * ($row->fields['SERVICE_PROVIDER_PERCENTAGE'] / 100);

                                                        if ($row->fields['TYPE'] == 'Refund') {
                                                            $total_refund += $row->fields['AMOUNT'] * ($row->fields['SERVICE_PROVIDER_PERCENTAGE'] / 100);
                                                            $total_refund_amount += $row->fields['AMOUNT'];
                                                        } else {
                                                            $total_portion += $portion; // Add to the sum
                                                            $total_amount += $row->fields['AMOUNT'];
                                                        }

                                                        $name = $row->fields['ENROLLMENT_NAME'];
                                                        $ENROLLMENT_ID = $row->fields['ENROLLMENT_ID'];
                                                        if (empty($name)) {
                                                            $enrollment_name = '';
                                                        } else {
                                                            $enrollment_name = "$name" . " - ";
                                                        }
                                                    ?>
                                                        <tr style="color: <?= ($row->fields['TYPE'] == 'Refund') ? 'red' : '' ?>;">
                                                            <td style="text-align: center"><?= $row->fields['RECEIPT_NUMBER'] ?></td>
                                                            <td style="text-align: center"><?= date('m-d-Y', strtotime($row->fields['PAYMENT_DATE'])) ?></td>
                                                            <td style="text-align: center">$<?= $row->fields['AMOUNT'] ?></td>
                                                            <td style="text-align: center"><?= $row->fields['CLIENT'] ?></td>
                                                            <td style="text-align: center"><?= $row->fields['PAYMENT_TYPE'] ?></td>
                                                            <td style="text-align: center"><?= $row->fields['ENROLLMENT_ID'] ?></td>
                                                            <td style="text-align: center"><?= ($enrollment_name . $ENROLLMENT_ID == null) ? $enrollment_name . $row->fields['MISC_ID'] : $enrollment_name . $ENROLLMENT_ID ?></td>
                                                            <td style="text-align: center"><?= $row->fields['ENROLLMENT_TYPE'] ?></td>
                                                            <td style="text-align: center"><?= $units . '/$' . $row->fields['TOTAL_AMOUNT'] ?></td>
                                                            <td style="text-align: center">$<?= number_format($row->fields['AMOUNT'] * ($row->fields['SERVICE_PROVIDER_PERCENTAGE'] / 100), 2) ?></td>
                                                            <td style="text-align: center"><?= number_format($row->fields['SERVICE_PROVIDER_PERCENTAGE'], 0) ?></td>
                                                            <td style="text-align: center"></td>
                                                        </tr>
                                                    <?php $row->MoveNext();
                                                    } ?>
                                                    <tr>
                                                        <th style="text-align: center; vertical-align:auto; font-weight: bold" colspan="9"></th>
                                                        <th style="text-align: center; vertical-align:auto; font-weight: bold" colspan="1">$<?= number_format($total_portion - $total_refund, 2) ?></th>
                                                        <th style="text-align: center; vertical-align:auto; font-weight: bold" colspan="2"></th>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php
                                        $each_service_provider->MoveNext();
                                    } ?>
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