<?php
require_once('../global/config.php');
$title = "Electronic Miscellaneous Reports";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5]) ){
    header("location:../login.php");
    exit;
}

if (!empty($_GET['NAME'])) {
    $type = isset($_GET['view']) ? 'view' : 'export';
    $generate_pdf = isset($_GET['generate_pdf']) ? 1 : 0;
    $generate_excel = isset($_GET['generate_excel']) ? 1 : 0;
    $report_name = $_GET['NAME'];
    $PK_PACKAGE = $_GET['NAME'];
    $TRANSPORTATION_CHARGES = $_GET['TRANSPORTATION_CHARGES'];
    $PACKAGE_COSTS = $_GET['PACKAGE_COSTS'];

    if ($generate_pdf === 1) {
        header('location:generate_report_pdf.php?PK_PACKAGE=' . $PK_PACKAGE . '&TRANSPORTATION_CHARGES=' . $TRANSPORTATION_CHARGES . '&PACKAGE_COSTS=' . $PACKAGE_COSTS . '&report_type='.$report_name);
    } elseif ($generate_excel === 1) {
        header('location:excel_miscellaneous_service_summary_report.php?PK_PACKAGE=' . $PK_PACKAGE . '&TRANSPORTATION_CHARGES=' . $TRANSPORTATION_CHARGES . '&PACKAGE_COSTS=' . $PACKAGE_COSTS . '&report_type='.$report_name);
    } else {
        if ($_GET['NAME'] == 'payments_made_report') {
            header('location:payments_made_report.php?week_number=' . $WEEK_NUMBER . '&start_date=' . $START_DATE . '&end_date=' . $END_DATE . '&type=' . $type);
        } elseif ($_GET['NAME'] == 'royalty_service_report') {
            header('location:royalty_service_report.php?week_number=' . $WEEK_NUMBER . '&start_date=' . $START_DATE . '&type=' . $type);
        } elseif ($_GET['NAME'] == 'summary_of_studio_business_report') {
            header('location:summary_of_studio_business_report.php?week_number=' . $WEEK_NUMBER . '&start_date=' . $START_DATE . '&type=' . $type);
        } elseif ($_GET['NAME'] == 'staff_performance_report') {
            header('location:staff_performance_report.php?week_number=' . $WEEK_NUMBER . '&start_date=' . $START_DATE . '&type=' . $type);
        } elseif ($_GET['NAME'] == 'summary_of_staff_member_report') {
            header('location:summary_of_staff_member_report.php?week_number=' . $WEEK_NUMBER . '&start_date=' . $START_DATE . '&type=' . $type);
        } else {
            header('location:miscellaneous_service_summary_report.php?PK_PACKAGE=' . $PK_PACKAGE . '&TRANSPORTATION_CHARGES=' . $TRANSPORTATION_CHARGES . '&PACKAGE_COSTS=' . $PACKAGE_COSTS . '&type=' . $type);
        }
    }
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
<style>
    .form-group {
        display: flex;
        align-items: center;
    }
    .form-group label {
        width: 170px; /* Adjust label width */
    }
    .form-group input {
        flex: 1; /* Takes remaining space */
        padding: 5px;
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
                    if ($address == "reports.php" || $address == "business_reports.php" || $address == "service_provider_reports.php" || $address == "electronic_miscellaneous_reports.php" || $address == "enrollment_reports.php" || $address == "student_mailing_list.php" || $address == "total_open_liability.php") { ?>
                        <ul class="nav nav-pills justify-content-left">
                            <li class="nav-item"><a class="nav-link <?=($address == 'reports.php') ? 'active' : ''?>" href="../admin/reports.php">Electronic Weekly Reports</a></li>
                            <li class="nav-item"><a class="nav-link <?=($address == 'business_reports.php') ? 'active' : ''?>" href="../admin/business_reports.php">Business Reports</a></li>
                            <li class="nav-item"><a class="nav-link <?=($address == 'service_provider_reports.php') ? 'active' : ''?>" href="../admin/service_provider_reports.php">Service Provider Reports</a></li>
                            <li class="nav-item"><a class="nav-link <?=($address == 'electronic_miscellaneous_reports.php') ? 'active' : ''?>" href="../admin/electronic_miscellaneous_reports.php">Electronic Miscellaneous Reports</a></li>
                            <!-- <li class="nav-item"><a class="nav-link <?=($address == 'enrollment_reports.php') ? 'active' : ''?>" href="">Enrollment Reports</a></li> -->
                            <li class="nav-item"><a class="nav-link <?=($address == 'customer_summary_report.php') ? 'active' : ''?>" href="../admin/customer_summary_report.php">Customer Summary Report</a></li>
                            <li class="nav-item"><a class="nav-link <?=($address == 'student_mailing_list.php') ? 'active' : ''?>" href="../admin/student_mailing_list.php">Student Mailing List</a></li>
                            <li class="nav-item"><a class="nav-link <?=($address == 'total_open_liability.php') ? 'active' : ''?>" href="../admin/total_open_liability.php">Total Open Liability Since Last Activity</a></li>
                        </ul>
                    <?php } ?>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="row" style="padding: 15px 35px 35px 35px;">
                            <div class="col-md-12 col-sm-3 mt-3">
                                <h4 class="card-title">Electronic Miscellaneous Report: *Deduction will be automatically created upon submission*</h4>
                            </div>
                            <form class="form-material form-horizontal" action="" method="get">
                                <input type="hidden" name="start_date" id="start_date">
                                <input type="hidden" name="end_date" id="end_date">
                                <div class="row">
                                    <div class="col-2">
                                        <div class="form-group">
                                            <select class="form-control" required name="NAME" id="NAME" onchange="showReportLog(this);">
                                                <option value="">Select a package</option>
                                                <?php
                                                $row = $db_account->Execute("SELECT PK_PACKAGE, PACKAGE_NAME FROM DOA_PACKAGE WHERE ACTIVE = 1");
                                                while (!$row->EOF) {?>
                                                <option value="<?=$row->fields['PK_PACKAGE']?>"><?=$row->fields['PACKAGE_NAME']?></option>
                                                <?php $row->MoveNext();
                                                $i++; } ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-2">
                                        <div class="form-group">
                                            <label for="TRANSPORTATION_CHARGES">Transportation Charges : $</label>
                                            <input type="text" id="TRANSPORTATION_CHARGES" name="TRANSPORTATION_CHARGES" class="form-control" placeholder="" value="<?=!empty($_GET['TRANSPORTATION_CHARGES'])?$_GET['TRANSPORTATION_CHARGES']:''?>" required>
                                        </div>
                                    </div>
                                    <div class="col-2">
                                        <div class="form-group">
                                            <label for="PACKAGE_COSTS">Package Costs : $</label>
                                            <input style="margin-left: -50px" type="text" id="PACKAGE_COSTS" name="PACKAGE_COSTS" class="form-control" placeholder="" value="<?=!empty($_GET['PACKAGE_COSTS'])?$_GET['PACKAGE_COSTS']:''?>" required>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <?php if(in_array('Reports Create', $PERMISSION_ARRAY)){ ?>
                                            <input type="submit" name="view" value="View" class="btn btn-info" style="background-color: #39B54A !important;">
                                            <!--<input type="submit" name="export" value="Export" class="btn btn-info" style="background-color: #39B54A !important;">-->
                                            <input type="submit" name="generate_pdf" value="Generate PDF" class="btn btn-info" style="background-color: #39B54A !important;">
                                            <input type="submit" name="generate_excel" value="Generate Excel" class="btn btn-info" style="background-color: #39B54A !important;">
                                        <?php } ?>
                                    </div>
                                    <!--<div class="col-4">
                                        <p id="last_export_message" style="color: red; margin-top: 9px;"></p>
                                    </div>-->
                                </div>
                                <!--<div class="row">
                                    <div class="col-4" id="export_log">
                                    </div>
                                </div>-->
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
    $(".week-picker").datepicker({
        showWeek: true,
        showOtherMonths: true,
        selectOtherMonths: true,
        changeMonth: true,
        changeYear: true,
        calculateWeek: wk,
        beforeShowDay: function (date) {
            if (date.getDay() === 0) {
                return [true, ''];
            }
            return [false, ''];
        },
        onSelect: function(dateText, inst) {
            let d = new Date(dateText);
            let start_date = (d.getMonth()+1)+'/'+d.getDate()+'/'+d.getFullYear();
            $(this).closest('form').find('#start_date').val(start_date);
            d.setDate(d.getDate() -363);
            let week_number = $.datepicker.iso8601Week(d);
            let report_type = $(this).closest('form').find('#NAME').val();
            $(this).val("Week Number " + week_number);
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: "POST",
                data: {FUNCTION_NAME:'getReportDetails', REPORT_TYPE:report_type, WEEK_NUMBER:week_number, YEAR:(d.getFullYear()+1)},
                async: false,
                cache: false,
                success: function (result) {
                    $('#last_export_message').text(result);
                }
            });
        }
    });

    function wk(d) {
        var d = new Date(d);
        d.setDate(d.getDate() -363);
        return '#' + $.datepicker.iso8601Week(d);
    }

    function showReportLog(param) {
        let report_type = $(param).closest('form').find('#NAME').val();
        $.ajax({
            url: "includes/get_report_details.php",
            type: "POST",
            data: {REPORT_TYPE:report_type},
            async: false,
            cache: false,
            success: function (result) {
                $(param).closest('form').find('#export_log').html(result);
            }
        });
    }
</script>