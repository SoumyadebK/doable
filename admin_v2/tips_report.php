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
        header('location:generate_report_pdf.php?start_date=' . $START_DATE . '&end_date=' . $END_DATE . '&report_type=' . $report_name . '&PK_USER=' . (is_array($PK_USER) ? implode(',', $PK_USER) : $PK_USER));
    } elseif ($generate_excel === 1) {
        header('location:excel_' . $report_name . '.php?start_date=' . $START_DATE . '&end_date=' . $END_DATE . '&report_type=' . $report_name . '&PK_USER=' . (is_array($PK_USER) ? implode(',', $PK_USER) : $PK_USER));
    } else {
        header('location:tips_report.php?start_date=' . $START_DATE . '&end_date=' . $END_DATE . '&type=' . $type . '&service_provider_id=' . (is_array($PK_USER) ? implode(',', $PK_USER) : $PK_USER) . '&include_no_provider=' . $include_no_provider);
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

                                    // Build the query without GROUP BY issues
                                    $tips_query = "
                                        SELECT DISTINCT
                                            et.PK_ENROLLMENT_TIP,
                                            et.PK_ENROLLMENT_MASTER,
                                            et.PK_ENROLLMENT_PAYMENT,
                                            et.TIP_PERCENTAGE,
                                            et.TIP_AMOUNT,
                                            et.PK_USER AS PROVIDER_ID,
                                            et.CREATED_ON,
                                            CONCAT(du.FIRST_NAME, ' ', du.LAST_NAME) AS PROVIDER_NAME,
                                            em.PK_ENROLLMENT_TYPE,
                                            em.ENROLLMENT_NAME,
                                            em.ENROLLMENT_DATE,
                                            em.PK_USER_MASTER,
                                            cd.PARTNER_FIRST_NAME,
                                            cd.PARTNER_LAST_NAME,
                                            CONCAT(us.FIRST_NAME, ' ', us.LAST_NAME) AS CUSTOMER_NAME,
                                            ep.AMOUNT AS PAYMENT_AMOUNT,
                                            ep.PK_PAYMENT_TYPE,
                                            ep.PAYMENT_DATE
                                        FROM DOA_ENROLLMENT_TIP et
                                        INNER JOIN DOA_ENROLLMENT_MASTER em ON et.PK_ENROLLMENT_MASTER = em.PK_ENROLLMENT_MASTER
                                        INNER JOIN DOA_ENROLLMENT_PAYMENT ep ON et.PK_ENROLLMENT_PAYMENT = ep.PK_ENROLLMENT_PAYMENT
                                        LEFT JOIN DOA_USERS du ON et.PK_USER = du.PK_USER
                                        LEFT JOIN DOA_CUSTOMER_DETAILS cd ON em.PK_USER_MASTER = cd.PK_USER_MASTER
                                        LEFT JOIN DOA_MASTER.DOA_USER_MASTER dc ON em.PK_USER_MASTER = dc.PK_USER_MASTER
                                        LEFT JOIN DOA_MASTER.DOA_USERS us ON us.PK_USER = dc.PK_USER
                                        WHERE DATE(et.CREATED_ON) BETWEEN '$from_date' AND '$to_date'
                                        GROUP BY et.PK_ENROLLMENT_MASTER
                                        
                                    ";

                                    // Add service provider filter
                                    if (!empty($service_provider_id) && $service_provider_id != '0') {
                                        $tips_query .= " AND et.PK_USER IN ($service_provider_id)";
                                    }

                                    $tips_query .= " ORDER BY et.CREATED_ON DESC";

                                    // Debug - uncomment to see the query
                                    // echo "<pre>$tips_query</pre>";

                                    // Execute query
                                    $tips_result = $db_account->Execute($tips_query);

                                    if (!$tips_result) {
                                        echo "<div class='alert alert-danger'>Query Error: " . $db_account->ErrorMsg() . "</div>";
                                    }

                                    // Initialize summary variables
                                    $total_tips = 0;
                                    $total_tips_count = 0;
                                    $provider_summary = [];
                                    $enrollment_type_summary = [];
                                    $payment_method_summary = [];
                                    $tips_data = [];

                                    if ($tips_result && $tips_result->RecordCount() > 0) {
                                        while (!$tips_result->EOF) {
                                            $tip_amount = floatval($tips_result->fields['TIP_AMOUNT']);
                                            $tip_percentage = floatval($tips_result->fields['TIP_PERCENTAGE']);
                                            $enrollment_type_id = $tips_result->fields['PK_ENROLLMENT_TYPE'];
                                            $enrollment_type = $enrollment_types[$enrollment_type_id] ?? 'Unknown';

                                            // Get appointments for this enrollment separately to avoid GROUP_CONCAT issues
                                            $appointments = [];
                                            $enrollment_id = $tips_result->fields['PK_ENROLLMENT_MASTER'];
                                            $appt_query = "
                                                SELECT DATE(am.DATE) as APPT_DATE, am.START_TIME, am.END_TIME, sm.SERVICE_NAME, CONCAT(du.FIRST_NAME, ' ', du.LAST_NAME) AS PROVIDER_NAME
                                                FROM DOA_APPOINTMENT_MASTER am
                                                LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ap ON am.PK_APPOINTMENT_MASTER = ap.PK_APPOINTMENT_MASTER
                                                LEFT JOIN $master_database.DOA_USERS du ON ap.PK_USER = du.PK_USER
                                                LEFT JOIN DOA_SERVICE_MASTER sm ON am.PK_SERVICE_MASTER = sm.PK_SERVICE_MASTER
                                                WHERE am.PK_ENROLLMENT_MASTER = '$enrollment_id'
                                                
                                                ORDER BY am.DATE
                                            ";
                                            $appt_result = $db_account->Execute($appt_query);
                                            if ($appt_result && $appt_result->RecordCount() > 0) {
                                                while (!$appt_result->EOF) {
                                                    $service_name = !empty($appt_result->fields['SERVICE_NAME']) ? $appt_result->fields['SERVICE_NAME'] : 'Lesson';
                                                    $provider_name = !empty($appt_result->fields['PROVIDER_NAME']) ? $appt_result->fields['PROVIDER_NAME'] : 'Unknown';
                                                    $appointments[] = date('m/d/Y', strtotime($appt_result->fields['APPT_DATE'])) . " - " . date('g:i A', strtotime($appt_result->fields['START_TIME'])) . " to " . date('g:i A', strtotime($appt_result->fields['END_TIME'])) . " - " . $service_name . " (" . $provider_name . ")";
                                                    $appt_result->MoveNext();
                                                }
                                            }

                                            $tip_record = [
                                                'pk_enrollment_tip' => $tips_result->fields['PK_ENROLLMENT_TIP'],
                                                'customer_name' => !empty($tips_result->fields['CUSTOMER_NAME']) ? $tips_result->fields['CUSTOMER_NAME'] : (!empty($tips_result->fields['PARTNER_FIRST_NAME']) ? trim($tips_result->fields['PARTNER_FIRST_NAME'] . ' ' . $tips_result->fields['PARTNER_LAST_NAME']) : 'Unknown'),
                                                'enrollment_name' => $tips_result->fields['ENROLLMENT_NAME'],
                                                'enrollment_type' => $enrollment_type,
                                                'tip_amount' => $tip_amount,
                                                'tip_percentage' => $tip_percentage,
                                                'appointments' => $appointments
                                            ];

                                            $tips_data[] = $tip_record;

                                            // Calculate totals
                                            $total_tips += $tip_amount;
                                            $total_tips_count++;

                                            // Provider summary
                                            // if (!isset($provider_summary[$provider_name])) {
                                            //     $provider_summary[$provider_name] = ['count' => 0, 'total' => 0];
                                            // }
                                            // $provider_summary[$provider_name]['count']++;
                                            // $provider_summary[$provider_name]['total'] += $tip_amount;

                                            // Enrollment type summary
                                            if (!isset($enrollment_type_summary[$enrollment_type])) {
                                                $enrollment_type_summary[$enrollment_type] = ['count' => 0, 'total' => 0];
                                            }
                                            $enrollment_type_summary[$enrollment_type]['count']++;
                                            $enrollment_type_summary[$enrollment_type]['total'] += $tip_amount;



                                            $tips_result->MoveNext();
                                        }
                                    ?>

                                        <!-- Summary Cards -->
                                        <!-- <div class="row mb-4">
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
                                        </div> -->

                                        <!-- Provider Summary Table -->
                                        <!-- <?php if (count($provider_summary) > 0): ?>
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
                                        <?php endif; ?> -->

                                        <!-- Enrollment Type Summary Table -->
                                        <!-- <?php if (count($enrollment_type_summary) > 0): ?>
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
                                        <?php endif; ?> -->

                                        <!-- Detailed Tips Table -->
                                        <div class="table-responsive mt-4">

                                            <table class="table table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th style="text-align: center">Client Name</th>
                                                        <th style="text-align: center">Enrollment Name</th>
                                                        <th style="text-align: center">Enrollment Type</th>
                                                        <th style="text-align: center">Appointments</th>
                                                        <th style="text-align: center">Tip %</th>
                                                        <th style="text-align: center">Tip Amount</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($tips_data as $tip): ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars($tip['customer_name']) ?></td>
                                                            <td style="text-align: center"><?= htmlspecialchars($tip['enrollment_name']) ?></td>
                                                            <td style="text-align: center"><?= htmlspecialchars($tip['enrollment_type']) ?></td>
                                                            <td style="font-size: 11px;">
                                                                <?php
                                                                if (!empty($tip['appointments'])) {
                                                                    echo '<ul style="margin: 0; padding-left: 15px;">';
                                                                    foreach (array_slice($tip['appointments'], 0, 3) as $appt) {
                                                                        echo '<li>' . htmlspecialchars($appt) . '</li>';
                                                                    }
                                                                    if (count($tip['appointments']) > 3) {
                                                                        echo '<li>... and ' . (count($tip['appointments']) - 3) . ' more</li>';
                                                                    }
                                                                    echo '</ul>';
                                                                } else {
                                                                    echo 'No appointments found';
                                                                }
                                                                ?>
                                                            </td>
                                                            <td style="text-align: center"><?= $tip['tip_percentage'] > 0 ? $tip['tip_percentage'] . '%' : 'N/A' ?></td>
                                                            <td style="text-align: right; font-weight: bold; color: green;">$<?= number_format($tip['tip_amount'], 2) ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                                <tfoot style="background-color: #f8f9fa; font-weight: bold;">
                                                    <tr>
                                                        <td colspan="5" style="text-align: right">TOTAL:</td>
                                                        <td style="text-align: right">$<?= number_format($total_tips, 2) ?></td>
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
                                            <?php
                                            // Debug info - remove in production
                                            if (isset($tips_query)) {
                                                echo "<small>Query executed: " . htmlspecialchars($tips_query) . "</small>";
                                            }
                                            ?>
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