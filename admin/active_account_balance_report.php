<?php
require_once('../global/config.php');
$title = "Active Account Balance Report";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

if (!empty($_GET['NAME'])) {
    if ($_GET['NAME'] == 'active_account_balance_report') {
        $type = isset($_GET['view']) ? 'view' : 'generate_excel';
        $generate_excel = isset($_GET['generate_excel']) ? 1 : 0;
        $SELECTED_DATE = $_GET['SELECTED_DATE'];
        $SELECTED_RANGE = $_GET['SELECTED_RANGE'];
        if ($generate_excel === 1) {
            $report_name = 'active_account_balance_report';
            header('location:excel_' . $report_name . '.php?selected_date=' . $SELECTED_DATE . '&selected_range=' . $SELECTED_RANGE . '&report_type=' . $report_name);
        } else {
            header('location:active_account_balance_report_details.php?selected_date=' . $SELECTED_DATE . '&selected_range=' . $SELECTED_RANGE . '&type=' . $type);
        }
    } else if ($_GET['NAME'] == 'nfa_active_customers_report') {
        $type = isset($_GET['view']) ? 'view' : 'generate_excel';
        $generate_excel = isset($_GET['generate_excel']) ? 1 : 0;
        if ($generate_excel === 1) {
            $report_name = 'nfa_active_customers_report';
            header('location:excel_' . $report_name . '.php?report_type=' . $report_name);
        } else {
            header('location:nfa_active_customers_report.php?type=' . $type);
        }
    } else if ($_GET['NAME'] == 'nfa_active_no_enrollments_report') {
        $type = isset($_GET['view']) ? 'view' : 'generate_excel';
        $generate_excel = isset($_GET['generate_excel']) ? 1 : 0;
        if ($generate_excel === 1) {
            $report_name = 'nfa_active_no_enrollments_report';
            header('location:excel_' . $report_name . '.php?report_type=' . $report_name);
        } else {
            header('location:nfa_active_no_enrollments_report.php?type=' . $type);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<?php require_once('../includes/header.php'); ?>
<style>
    .menu-list {
        list-style-type: none;
        margin-left: -30px;
    }

    .menu-list li {
        margin: 10px;
    }
</style>

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">
        <?php require_once('../includes/top_menu.php'); ?>
        <div class="page-wrapper">
            <?php require_once('../includes/top_menu_bar.php') ?>
            <?php require_once('../includes/report_menu.php') ?>
            <div class="container-fluid" style="padding: 10px 20px 0 20px;">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="row" style="padding: 15px 15px 15px 15px;">
                                <form class="form-material form-horizontal" action="" method="get">
                                    <input type="hidden" name="selected_date" id="selected_date">
                                    <div class="row">
                                        <div class="col-3">
                                            <div class="form-group">
                                                <select class="form-control" required name="NAME" id="NAME" onchange="selectReport(this);">
                                                    <option value="">Select Report</option>
                                                    <option value="active_account_balance_report">ACTIVE ACCOUNT BALANCE REPORT</option>
                                                    <option value="nfa_active_customers_report">NFA ACTIVE CUSTOMERS REPORT</option>
                                                    <option value="nfa_active_no_enrollments_report">NFA ACTIVE NO ENROLLMENTS REPORT</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-2 selected_date" style="display: none;">
                                            <div class="form-group">
                                                <input type="text" id="SELECTED_DATE" name="SELECTED_DATE" class="form-control datepicker-normal" placeholder="Select Date" value="<?= !empty($_GET['SELECTED_DATE']) ? $_GET['SELECTED_DATE'] : '' ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-2 selected_range" style="display: none;">
                                            <div class="form-group">
                                                <select class="form-control" name="SELECTED_RANGE" id="SELECTED_RANGE">
                                                    <option value="">Select Range</option>
                                                    <option value="1">1 Month Prior</option>
                                                    <option value="3">3 Months Prior</option>
                                                    <option value="6">6 Months Prior</option>
                                                </select>
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
            </div>
        </div>
    </div>
    <?php require_once('../includes/footer.php'); ?>
</body>

</html>
<script>
    $('.datepicker-normal').datepicker({
        format: 'mm/dd/yyyy',
    });

    function selectReport(param) {
        let selectedReport = $('#NAME').val();

        // Remove required attributes first
        $('#SELECTED_DATE').prop('required', false);
        $('#SELECTED_RANGE').prop('required', false);

        // Hide both fields
        $('.selected_date').addClass('hidden');
        $('.selected_range').addClass('hidden');

        // Show fields only for active_account_balance_report
        if (selectedReport === 'active_account_balance_report') {
            $('.selected_date').removeClass('hidden');
            $('.selected_range').removeClass('hidden');
            $('#SELECTED_DATE').prop('required', true);
            $('#SELECTED_RANGE').prop('required', true);
        }
    }

    // Initialize on page load
    $(document).ready(function() {
        // Hide fields initially
        $('.selected_date').hide();
        $('.selected_range').hide();

        // If there's already a selected value, trigger the change
        if ($('#NAME').val()) {
            selectReport(document.getElementById('NAME'));
        }
    });
</script>