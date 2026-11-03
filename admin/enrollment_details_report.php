<?php
require_once('../global/config.php');
global $db, $db_account, $master_database;

$title = "ENROLLMENT TYPE DETAILED REPORT";

// Authentication check
if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

// Get date range from GET parameters
if (isset($_GET['START_DATE']) && isset($_GET['END_DATE'])) {
    // Coming from form with START_DATE/END_DATE parameters
    $from_date = date('Y-m-d', strtotime($_GET['START_DATE']));
    $to_date = date('Y-m-d', strtotime($_GET['END_DATE']));
} elseif (isset($_GET['start_date']) && isset($_GET['end_date'])) {
    // Coming from redirect with start_date/end_date parameters
    $from_date = date('Y-m-d', strtotime($_GET['start_date']));
    $to_date = date('Y-m-d', strtotime($_GET['end_date']));
} else {
    // Default to current date
    $from_date = date('Y-m-d');
    $to_date = date('Y-m-d');
}

// Date condition for SQL
$date_condition = "'" . $from_date . "' AND '" . $to_date . "'";

// Get location IDs from session
$location_ids = $_SESSION['DEFAULT_LOCATION_ID'] ?? '';

// Define enrollment types
$enrollment_types = [
    '5' => ['name' => 'Pre-Original', 'label' => 'PRE-ORIGINAL'],
    '2' => ['name' => 'Original', 'label' => 'ORIGINAL'],
    '9' => ['name' => 'Extension', 'label' => 'EXTENSION'],
    '13' => ['name' => 'Renewal', 'label' => 'RENEWAL']
];
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
                                <li class="breadcrumb-item active"><a href="reports.php">Reports</a></li>
                                <li class="breadcrumb-item active"><a href="#"><?= $title ?></a></li>
                            </ol>
                        </div>
                    </div>
                </div>

                <!-- Date Range Form -->
                <div class="row">
                    <div class="col-12 align-self-center">
                        <div class="card">
                            <div class="card-body" style="padding-bottom: 0px !important;">
                                <form class="form-material form-horizontal" action="" method="get" id="reportForm">
                                    <input type="hidden" name="start_date" id="start_date" value="<?= $from_date ?>">
                                    <input type="hidden" name="end_date" id="end_date" value="<?= $to_date ?>">
                                    <div class="row justify-content-start">
                                        <div class="col-2">
                                            <div class="form-group">
                                                <input type="text" id="START_DATE" name="START_DATE" class="form-control datepicker" placeholder="Start Date" value="<?= date('m/d/Y', strtotime($from_date)) ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-2">
                                            <div class="form-group">
                                                <input type="text" id="END_DATE" name="END_DATE" class="form-control datepicker" placeholder="End Date" value="<?= date('m/d/Y', strtotime($to_date)) ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-3 ">
                                            <?php if (in_array('Reports Create', $PERMISSION_ARRAY)) { ?>
                                                <input type="submit" name="view" value="View" class="btn btn-info" style="background-color: #39B54A !important;">
                                            <?php } ?>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Report Display -->
                <?php if (isset($_GET['view']) || isset($_GET['START_DATE'])): ?>
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title" style="text-align: center; font-weight: bold"><?= $title ?></h4>
                                    <p style="text-align: center;">Date Range: <?= date('m/d/Y', strtotime($from_date)) ?> to <?= date('m/d/Y', strtotime($to_date)) ?></p>

                                    <!-- Loop through each enrollment type -->
                                    <?php
                                    $total_all_enrollments = 0;
                                    $total_all_amount = 0;

                                    foreach ($enrollment_types as $type_id => $type_info):
                                        // Get enrollment details for this type
                                        $query = "
                                            SELECT 
                                                em.PK_ENROLLMENT_MASTER,
                                                em.ENROLLMENT_NAME,
                                                em.ENROLLMENT_ID,
                                                DATE_FORMAT(em.ENROLLMENT_DATE, '%m/%d/%Y') as enrollment_date,
                                                CONCAT(us.FIRST_NAME, ' ', us.LAST_NAME) as CUSTOMER_NAME,
                                                em.STATUS as ENROLLMENT_STATUS,
                                                SUM(es.FINAL_AMOUNT) as amount
                                            FROM DOA_ENROLLMENT_MASTER em
                                            LEFT JOIN DOA_ENROLLMENT_SERVICE es ON em.PK_ENROLLMENT_MASTER = es.PK_ENROLLMENT_MASTER
                                            LEFT JOIN DOA_ENROLLMENT_BILLING eb ON em.PK_ENROLLMENT_MASTER = eb.PK_ENROLLMENT_MASTER
                                            LEFT JOIN $master_database.DOA_USER_MASTER um ON em.PK_USER_MASTER = um.PK_USER_MASTER
                                            LEFT JOIN $master_database.DOA_USERS us ON um.PK_USER = us.PK_USER
                                            WHERE em.PK_LOCATION IN ($location_ids)
                                            AND em.PK_ENROLLMENT_TYPE = $type_id
                                            AND em.ENROLLMENT_DATE BETWEEN $date_condition
                                            AND eb.TOTAL_AMOUNT > 0
                                            GROUP BY em.PK_ENROLLMENT_MASTER
                                            ORDER BY em.ENROLLMENT_DATE DESC
                                        ";

                                        $result = $db_account->Execute($query);
                                        $count = $result->RecordCount();
                                        $total_amount = 0;
                                    ?>

                                        <div class="enrollment-type-section mb-4">
                                            <!-- Enrollment Type Header -->
                                            <div class="card-header bg-dark text-white mb-2">
                                                <h5 class="mb-0">
                                                    <?= $type_info['label'] ?> -
                                                    <span class="badge bg-light text-dark"><?= $count ?> Enrollment<?= $count != 1 ? 's' : '' ?></span>
                                                </h5>
                                            </div>

                                            <?php if ($count > 0): ?>
                                                <!-- Enrollment Details Table -->
                                                <div class="table-responsive">
                                                    <table class="table table-bordered table-striped">
                                                        <thead>
                                                            <tr style="background-color: #f8f9fa;">
                                                                <th style="width:15%; text-align: center; font-weight: bold">Enrollment Name</th>
                                                                <th style="width:15%; text-align: center; font-weight: bold">Enrollment ID</th>
                                                                <th style="width:15%; text-align: center; font-weight: bold">Date</th>
                                                                <th style="width:25%; text-align: center; font-weight: bold">Customer Name</th>
                                                                <th style="width:15%; text-align: center; font-weight: bold">Amount</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php while (!$result->EOF):
                                                                $amount = $result->fields['amount'] ?? 0;
                                                                $total_amount += $amount;
                                                            ?>
                                                                <tr>
                                                                    <td style="text-align: center;">
                                                                        <?= $result->fields['ENROLLMENT_NAME'] ?>
                                                                    </td>
                                                                    <td style="text-align: center;">
                                                                        <?= $result->fields['ENROLLMENT_ID'] ?>
                                                                    </td>
                                                                    <td style="text-align: center;">
                                                                        <?= date('m/d/Y', strtotime($result->fields['enrollment_date'])) ?>
                                                                    </td>
                                                                    <td style="text-align: center;">
                                                                        <?= htmlspecialchars($result->fields['CUSTOMER_NAME']) ?>
                                                                    </td>
                                                                    <td style="text-align: center; font-weight: bold;">
                                                                        $<?= number_format($amount, 2) ?>
                                                                    </td>
                                                                </tr>
                                                            <?php
                                                                $result->MoveNext();
                                                            endwhile;
                                                            ?>
                                                        </tbody>
                                                        <!-- <tfoot>
                                                            <tr style="background-color: #e9ecef; font-weight: bold;">
                                                                <td colspan="4" style="text-align: right;">Total for <?= $type_info['label'] ?>:</td>
                                                                <td style="text-align: center; font-size: 16px;">
                                                                    $<?= number_format($total_amount, 2) ?>
                                                                </td>
                                                                <td></td>
                                                            </tr>
                                                            <tr style="background-color: #f8f9fa; font-weight: bold;">
                                                                <td colspan="4" style="text-align: right;">Average per enrollment:</td>
                                                                <td style="text-align: center;">
                                                                    $<?= $count > 0 ? number_format($total_amount / $count, 2) : '0.00' ?>
                                                                </td>
                                                                <td></td>
                                                            </tr>
                                                        </tfoot> -->
                                                    </table>
                                                </div>
                                            <?php else: ?>
                                                <!-- No enrollments message -->
                                                <div class="alert alert-info mb-0">
                                                    <i class="fa fa-info-circle"></i> No <?= $type_info['label'] ?> enrollments found in this date range.
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                    <?php
                                        $total_all_enrollments += $count;
                                        $total_all_amount += $total_amount;
                                    endforeach;
                                    ?>

                                    <!-- Summary Section -->
                                    <!-- <div class="card mt-4">
                                        <div class="card-header bg-success text-white">
                                            <h5 class="mb-0">SUMMARY</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="alert alert-success">
                                                        <h6><i class="fa fa-chart-bar"></i> TOTAL ENROLLMENTS</h6>
                                                        <h3 class="text-center" style="font-weight: bold;"><?= $total_all_enrollments ?></h3>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="alert alert-primary">
                                                        <h6><i class="fa fa-money-bill-wave"></i> TOTAL AMOUNT</h6>
                                                        <h3 class="text-center" style="font-weight: bold;">$<?= number_format($total_all_amount, 2) ?></h3>
                                                    </div>
                                                </div>
                                            </div>

                                            
                                            <div class="table-responsive">
                                                <table class="table table-bordered">
                                                    <thead>
                                                        <tr style="background-color: #f8f9fa;">
                                                            <th>Enrollment Type</th>
                                                            <th class="text-center">Count</th>
                                                            <th class="text-center">Percentage</th>
                                                            <th class="text-center">Total Amount</th>
                                                            <th class="text-center">Average Amount</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php
                                                        // Re-query to get fresh data for summary
                                                        foreach ($enrollment_types as $type_id => $type_info):
                                                            $summary_query = "
                                                                SELECT 
                                                                    COUNT(DISTINCT em.PK_ENROLLMENT_MASTER) as count,
                                                                    SUM(es.FINAL_AMOUNT) as total_amount
                                                                FROM DOA_ENROLLMENT_MASTER em
                                                                LEFT JOIN DOA_ENROLLMENT_SERVICE es ON em.PK_ENROLLMENT_MASTER = es.PK_ENROLLMENT_MASTER
                                                                WHERE em.PK_LOCATION IN ($location_ids)
                                                                AND em.PK_ENROLLMENT_TYPE = $type_id
                                                                AND em.ENROLLMENT_DATE BETWEEN $date_condition
                                                                AND em.STATUS != 'D'
                                                            ";

                                                            $summary_result = $db_account->Execute($summary_query);
                                                            $type_count = $summary_result->fields['count'] ?? 0;
                                                            $type_total = $summary_result->fields['total_amount'] ?? 0;
                                                            $percentage = $total_all_enrollments > 0 ? ($type_count / $total_all_enrollments) * 100 : 0;
                                                        ?>
                                                            <tr>
                                                                <td><strong><?= $type_info['label'] ?></strong></td>
                                                                <td class="text-center"><?= $type_count ?></td>
                                                                <td class="text-center"><?= number_format($percentage, 1) ?>%</td>
                                                                <td class="text-center">$<?= number_format($type_total, 2) ?></td>
                                                                <td class="text-center">$<?= $type_count > 0 ? number_format($type_total / $type_count, 2) : '0.00' ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                        <tr style="background-color: #e9ecef; font-weight: bold;">
                                                            <td>TOTAL</td>
                                                            <td class="text-center"><?= $total_all_enrollments ?></td>
                                                            <td class="text-center">100%</td>
                                                            <td class="text-center">$<?= number_format($total_all_amount, 2) ?></td>
                                                            <td class="text-center">$<?= $total_all_enrollments > 0 ? number_format($total_all_amount / $total_all_enrollments, 2) : '0.00' ?></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div> -->
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php require_once('../includes/footer.php'); ?>
</body>

</html>

<script>
    $(document).ready(function() {
        // Initialize datepickers
        $(".datepicker").datepicker({
            dateFormat: 'mm/dd/yy',
            changeMonth: true,
            changeYear: true
        });

        // On form submit, ensure hidden inputs have correct format
        $('#reportForm').on('submit', function() {
            var startDate = $('#START_DATE').val();
            var endDate = $('#END_DATE').val();

            // Convert mm/dd/yyyy to yyyy-mm-dd for hidden inputs
            function toIsoFormat(dateStr) {
                if (!dateStr) return '';
                var parts = dateStr.split('/');
                if (parts.length === 3) {
                    return parts[2] + '-' + ('0' + parts[0]).slice(-2) + '-' + ('0' + parts[1]).slice(-2);
                }
                return dateStr;
            }

            $('#start_date').val(toIsoFormat(startDate));
            $('#end_date').val(toIsoFormat(endDate));
        });
    });
</script>