<?php
require_once('../global/config.php');
$title = "Reports";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

if (!empty($_GET['NAME'])) {
    $type = isset($_GET['view']) ? 'view' : 'export';
    $generate_pdf = isset($_GET['generate_pdf']) ? 1 : 0;
    $generate_excel = isset($_GET['generate_excel']) ? 1 : 0;
    $report_name = $_GET['NAME'];
    $WEEK_NUMBER = explode(' ', $_GET['WEEK_NUMBER'])[2];
    $START_DATE = $_GET['start_date'];

    if ($generate_pdf === 1) {
        header('location:generate_report_pdf.php?week_number=' . $WEEK_NUMBER . '&start_date=' . $START_DATE . '&report_type=' . $report_name);
    } elseif ($generate_excel === 1) {
        header('location:excel_' . $report_name . '.php?week_number=' . $WEEK_NUMBER . '&start_date=' . $START_DATE . '&report_type=' . $report_name);
    } else {
        if ($_GET['NAME'] == 'payments_made_report') {
            header('location:payments_made_report.php?week_number=' . $WEEK_NUMBER . '&start_date=' . $START_DATE . '&type=' . $type);
        } elseif ($_GET['NAME'] == 'royalty_service_report') {
            header('location:royalty_service_report.php?week_number=' . $WEEK_NUMBER . '&start_date=' . $START_DATE . '&type=' . $type);
        } elseif ($_GET['NAME'] == 'summary_of_studio_business_report') {
            header('location:summary_of_studio_business_report.php?week_number=' . $WEEK_NUMBER . '&start_date=' . $START_DATE . '&type=' . $type);
        } elseif ($_GET['NAME'] == 'staff_performance_report') {
            header('location:staff_performance_report.php?week_number=' . $WEEK_NUMBER . '&start_date=' . $START_DATE . '&type=' . $type);
        } elseif ($_GET['NAME'] == 'summary_of_staff_member_report') {
            header('location:summary_of_staff_member_report.php?week_number=' . $WEEK_NUMBER . '&start_date=' . $START_DATE . '&type=' . $type);
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
            <div class="container-fluid body_content">
                <div class="row page-titles">
                    <div class="col-md-12 align-self-start">
                        <?php
                        $currentURL = parse_url($_SERVER['REQUEST_URI']);
                        $url = explode("/", $currentURL['path']);
                        if ($_SERVER['HTTP_HOST'] == 'localhost') {
                            $address = $url[3];
                        } else {
                            $address = $url[2];
                        }
                        if ($address == "reports.php" || $address == "business_reports.php" || $address == "service_provider_reports.php" || $address == "electronic_miscellaneous_reports.php" || $address == "enrollment_reports.php" || $address == "student_mailing_list.php" || $address == "total_open_liability.php") { ?>
                            <ul class="nav nav-pills justify-content-left">
                                <li class="nav-item"><a class="nav-link <?= ($address == 'reports.php') ? 'active' : '' ?>" href="../admin/reports.php">Electronic Weekly Reports</a></li>
                                <li class="nav-item"><a class="nav-link <?= ($address == 'business_reports.php') ? 'active' : '' ?>" href="../admin/business_reports.php">Business Reports</a></li>
                                <li class="nav-item"><a class="nav-link <?= ($address == 'service_provider_reports.php') ? 'active' : '' ?>" href="../admin/service_provider_reports.php">Service Provider Reports</a></li>
                                <li class="nav-item"><a class="nav-link <?= ($address == 'electronic_miscellaneous_reports.php') ? 'active' : '' ?>" href="../admin/electronic_miscellaneous_reports.php">Electronic Miscellaneous Reports</a></li>
                                <!-- <li class="nav-item"><a class="nav-link <?= ($address == 'enrollment_reports.php') ? 'active' : '' ?>" href="../admin/enrollment_reports.php">Enrollment Reports</a></li> -->
                                <li class="nav-item"><a class="nav-link <?= ($address == 'customer_summary_report.php') ? 'active' : '' ?>" href="../admin/customer_summary_report.php">Customer Summary Report</a></li>
                                <li class="nav-item"><a class="nav-link <?= ($address == 'student_mailing_list.php') ? 'active' : '' ?>" href="../admin/student_mailing_list.php">Student Mailing List</a></li>
                                <li class="nav-item"><a class="nav-link <?= ($address == 'total_open_liability.php') ? 'active' : '' ?>" href="../admin/total_open_liability.php">Total Open Liability Since Last Activity</a></li>
                            </ul>
                        <?php } ?>
                    </div>
                </div>

                <!-- <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="row" style="padding: 15px 35px 35px 35px;">
                            <div class="col-md-3 col-sm-3 mt-3">
                                <h4 class="card-title">Reports</h4>
                                <div>
                                    <ul class="menu-list">
                                        <li><a href="business_reports.php">Business Reports</a></li>
                                        <li><a href="electronic_weekly_reports.php">Electronic Weekly Reports</a></li>
                                        <li><a href="service_provider_reports.php">Service Provider Reports</a></li>
                                        <li><a href="electronic_miscellaneous_reports.php">Electronic Miscellaneous Reports</a></li>
                                        <li><a href="#">Enrollment Reports</a></li>
                                        <li><a href="customer_summary_report.php">Customer Reports</a></li>
                                        <li><a href="student_mailing_list.php">Student Mailing List</a></li>
                                        <li><a href="total_open_liability.php">Total Open Liability Since Last Activity</a></li>
                                        <li><a href="royalty.php">Royalty / Service Report</a></li>
                                        <li><a href="summary_of_studio_business_report.php">Summary of Studio Business Report</a></li>
                                        <li><a href="staff_performance_report.php">Staff Performance Report</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div> -->

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="row" style="padding: 15px 35px 35px 35px;">
                                <div class="col-md-3 col-sm-3 mt-3">
                                    <h4 class="card-title">Electronic Weekly Reports</h4>
                                </div>
                                <form class="form-material form-horizontal" action="" method="get">
                                    <input type="hidden" name="start_date" id="start_date">
                                    <div class="row">
                                        <div class="col-2">
                                            <div class="form-group">
                                                <select class="form-control" required name="NAME" id="NAME" onchange="showReportLog(this);">
                                                    <option value="">Select Report</option>
                                                    <option value="royalty_service_report">ROYALTY / SERVICE REPORT</option>
                                                    <option value="summary_of_studio_business_report">SUMMARY OF STUDIO BUSINESS REPORT</option>
                                                    <option value="staff_performance_report">STAFF PERFORMANCE REPORT</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-2">
                                            <div class="form-group">
                                                <input type="text" id="WEEK_NUMBER1" name="WEEK_NUMBER" class="form-control datepicker-normal week-picker" placeholder="Start Date" value="<?php /*=!empty($_GET['WEEK_NUMBER'])?$_GET['WEEK_NUMBER']:''*/ ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <?php /*if(in_array('Reports Create', $PERMISSION_ARRAY)){ */ ?>
                                            <input type="submit" name="view" value="View" class="btn btn-info" style="background-color: #39B54A !important;">
                                            <input type="submit" name="export" value="Export" class="btn btn-info" style="background-color: #39B54A !important;">
                                            <input type="submit" name="generate_pdf" value="Generate PDF" class="btn btn-info" style="background-color: #39B54A !important;">
                                            <input type="submit" name="generate_excel" value="Generate Excel" class="btn btn-info" style="background-color: #39B54A !important;">
                                            <?php /*} */ ?>
                                        </div>
                                        <div class="col-4">
                                            <p id="last_export_message" style="color: red; margin-top: 9px;"></p>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-4" id="export_log">

                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!--<div class="card">
                        <div class="row" style="padding: 15px 35px 35px 35px;">
                            <div class="col-md-3 col-sm-3 mt-3">
                                <h4 class="card-title">Business Reports</h4>
                            </div>
                            <form class="form-material form-horizontal" action="" method="get">
                                <input type="hidden" name="start_date" id="start_date">
                                <div class="row">
                                    <div class="col-2">
                                        <div class="form-group">
                                            <select class="form-control" required name="NAME" id="NAME" onchange="showReportLog(this);">
                                                <option value="">Select Report</option>
                                                <option value="payments_made_report">PAYMENTS MADE REPORT</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-2">
                                        <div class="form-group">
                                            <input type="text" id="WEEK_NUMBER2" name="WEEK_NUMBER" class="form-control datepicker-normal week-picker" placeholder="Start Date" value="<?php /*=!empty($_GET['WEEK_NUMBER'])?$_GET['WEEK_NUMBER']:''*/ ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <?php /*if(in_array('Reports Create', $PERMISSION_ARRAY)){ */ ?>
                                            <input type="submit" name="view" value="View" class="btn btn-info">
                                            <input type="submit" name="export" value="Export" class="btn btn-info">
                                            <input type="submit" name="generate_pdf" value="Generate PDF" class="btn btn-info">
                                            <input type="submit" name="generate_excel" value="Generate Excel" class="btn btn-info">
                                        <?php /*} */ ?>
                                    </div>
                                    <div class="col-4">
                                        <p id="last_export_message" style="color: red; margin-top: 9px;"></p>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-4" id="export_log">
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card">
                        <div class="row" style="padding: 15px 35px 35px 35px;">
                            <div class="col-md-3 col-sm-3 mt-3">
                                <h4 class="card-title">Service Provider Reports</h4>
                            </div>
                            <form class="form-material form-horizontal" action="" method="get">
                                <input type="hidden" name="start_date" id="start_date">
                                <div class="row">
                                    <div class="col-2">
                                        <div class="form-group">
                                            <select class="form-control" required name="NAME" id="NAME" onchange="showReportLog(this);">
                                                <option value="">Select Report</option>
                                                <option value="summary_of_staff_member_report">SUMMARY OF STAFF MEMBER REPORT</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-2">
                                        <div class="form-group">
                                            <input type="text" id="WEEK_NUMBER3" name="WEEK_NUMBER" class="form-control datepicker-normal week-picker" placeholder="Start Date" value="<?php /*=!empty($_GET['WEEK_NUMBER'])?$_GET['WEEK_NUMBER']:''*/ ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <?php /*if(in_array('Reports Create', $PERMISSION_ARRAY)){ */ ?>
                                            <input type="submit" name="view" value="View" class="btn btn-info">
                                            <input type="submit" name="export" value="Export" class="btn btn-info">
                                            <input type="submit" name="generate_pdf" value="Generate PDF" class="btn btn-info">
                                            <input type="submit" name="generate_excel" value="Generate Excel" class="btn btn-info">
                                        <?php /*} */ ?>
                                    </div>
                                    <div class="col-4">
                                        <p id="last_export_message" style="color: red; margin-top: 9px;"></p>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-4" id="export_log">
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>-->

                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php require_once('../includes/footer.php'); ?>
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
        beforeShowDay: function(date) {
            if (date.getDay() === 0) {
                return [true, ''];
            }
            return [false, ''];
        },
        onSelect: function(dateText, inst) {
            let d = new Date(dateText);
            let start_date = (d.getMonth() + 1) + '/' + d.getDate() + '/' + d.getFullYear();
            $(this).closest('form').find('#start_date').val(start_date);
            d.setDate(d.getDate() - 363);
            let week_number = $.datepicker.iso8601Week(d);
            let report_type = $(this).closest('form').find('#NAME').val();
            $(this).val("Week Number " + week_number);
            /* $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: "POST",
                data: {
                    FUNCTION_NAME: 'getReportDetails',
                    REPORT_TYPE: report_type,
                    WEEK_NUMBER: week_number,
                    YEAR: (d.getFullYear() + 1)
                },
                async: false,
                cache: false,
                success: function(result) {
                    $('#last_export_message').text(result);
                }
            }); */
        }
    });

    function wk(d) {
        var d = new Date(d);
        d.setDate(d.getDate() - 363);
        return '#' + $.datepicker.iso8601Week(d);
    }

    function showReportLog(param) {
        $('#export_log').html('<p style="font-size: 16px;">Loading Submission Log <i class="fas fa-spinner fa-pulse" style="font-size: 20px;"></i></p>');

        let report_type = $(param).closest('form').find('#NAME').val();
        $.ajax({
            url: "includes/get_report_details.php",
            type: "POST",
            data: {
                REPORT_TYPE: report_type
            },
            success: function(result) {
                $('#export_log').html(result);
            }
        });
    }
</script>