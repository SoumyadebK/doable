<?php
require_once('../global/config.php');
$title = "Active Account Balance Report";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5]) ){
    header("location:../login.php");
    exit;
}

if (!empty($_GET['SELECTED_DATE'])) {
    $type = isset($_GET['view']) ? 'view' : 'export';
    $SELECTED_DATE = $_GET['SELECTED_DATE'];
    $SELECTED_RANGE = $_GET['SELECTED_RANGE'];
    header('location:active_account_balance_report_details.php?selected_date=' . $SELECTED_DATE . '&selected_range=' . $SELECTED_RANGE . '&type=' . $type);
}
?>

<!DOCTYPE html>
<html lang="en">
<?php require_once('../includes/header.php');?>
<style>
    .menu-list{
        list-style-type: none;
        margin-left: -30px;
    }

    .menu-list li{
        margin: 10px;
    }
</style>
<body class="skin-default-dark fixed-layout">
<?php require_once('../includes/loader.php');?>
<div id="main-wrapper">
    <?php require_once('../includes/top_menu.php');?>
    <div class="page-wrapper">
        <?php require_once('../includes/top_menu_bar.php') ?>
        <div class="container-fluid body_content">
            <div class="row page-titles">
                <div class="col-md-12 align-self-start">
                    <?php
                    $currentURL = parse_url($_SERVER['REQUEST_URI']);
                    $url = explode("/", $currentURL['path']);
                    if($_SERVER['HTTP_HOST'] == 'localhost' ) {
                        $address = $url[3];
                    } else {
                        $address = $url[2];
                    }
                    if ($address == "reports.php" || $address == "business_reports.php" || $address == "service_provider_reports.php" || $address == "electronic_miscellaneous_reports.php" || $address == "enrollment_reports.php" || $address == "student_mailing_list.php" || $address == "total_open_liability.php" || $address == "active_account_balance_report.php"  || $address == "cash_report.php") { ?>
                        <ul class="nav nav-pills justify-content-left">
                            <li class="nav-item"><a class="nav-link <?=($address == 'reports.php') ? 'active' : ''?>" href="../admin/reports.php">Electronic Weekly Reports</a></li>
                            <li class="nav-item"><a class="nav-link <?=($address == 'business_reports.php') ? 'active' : ''?>" href="../admin/business_reports.php">Business Reports</a></li>
                            <li class="nav-item"><a class="nav-link <?=($address == 'service_provider_reports.php') ? 'active' : ''?>" href="../admin/service_provider_reports.php">Service Provider Reports</a></li>
                            <li class="nav-item"><a class="nav-link <?=($address == 'electronic_miscellaneous_reports.php') ? 'active' : ''?>" href="../admin/electronic_miscellaneous_reports.php">Electronic Miscellaneous Reports</a></li>
                            <!-- <li class="nav-item"><a class="nav-link <?=($address == 'enrollment_reports.php') ? 'active' : ''?>" href="">Enrollment Reports</a></li> -->
                            <li class="nav-item"><a class="nav-link <?=($address == 'customer_summary_report.php') ? 'active' : ''?>" href="../admin/customer_summary_report.php">Customer Summary Report</a></li>
                            <li class="nav-item"><a class="nav-link <?=($address == 'student_mailing_list.php') ? 'active' : ''?>" href="../admin/student_mailing_list.php">Student Mailing List</a></li>
                            <li class="nav-item"><a class="nav-link <?=($address == 'total_open_liability.php') ? 'active' : ''?>" href="../admin/total_open_liability.php">Total Open Liability Since Last Activity</a></li>
                            <li class="nav-item"><a class="nav-link <?= ($address == 'active_account_balance_report.php') ? 'active' : '' ?>" href="../admin/active_account_balance_report.php">Active Account Balance Report</a></li>
                            <li class="nav-item"><a class="nav-link <?= ($address == 'cash_report.php') ? 'active' : '' ?>" href="../admin/cash_report.php">Cash Report</a></li>
                        </ul>
                    <?php } ?>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="row" style="padding: 15px 15px 15px 15px;">
                            <form class="form-material form-horizontal" action="" method="get">
                                <input type="hidden" name="selected_date" id="selected_date">
                                <div class="row">
                                    <div class="col-2">
                                        <div class="form-group">
                                            <input type="text" id="SELECTED_DATE" name="SELECTED_DATE" class="form-control datepicker-normal" placeholder="Select Date" value="<?= !empty($_GET['SELECTED_DATE']) ? $_GET['SELECTED_DATE'] : '' ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-2">
                                        <div class="form-group">
                                            <select class="form-control" name="SELECTED_RANGE" id="SELECTED_RANGE">
                                                <option value="">Select Range</option>
                                                <option value="1">1 Month</option>
                                                <option value="3">3 Months</option>
                                                <option value="6">6 Months</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <?php if(in_array('Reports Create', $PERMISSION_ARRAY)){ ?>
                                            <input type="submit" name="view" value="View" class="btn btn-info" style="background-color: #39B54A !important;">
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
<?php require_once('../includes/footer.php');?>
</body>
</html>
<script>
    $('.datepicker-normal').datepicker({
    format: 'mm/dd/yyyy',
    });
</script>