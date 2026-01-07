<?php
require_once('../global/config.php');
global $AMI_ENABLE;
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
                            <div class="row" style="padding: 15px 35px 35px 35px;">
                                <div class="col-md-3 col-sm-3 mt-3">
                                    <h4 class="card-title">Electronic Weekly Reports</h4>
                                </div>
                                <form class="form-material form-horizontal" action="" method="get">
                                    <input type="hidden" name="start_date" id="start_date">
                                    <div class="row">
                                        <div class="col-2">
                                            <div class="form-group">
                                                <select class="form-control" required name="NAME" id="NAME" <?= ($AMI_ENABLE == 1) ? 'onchange = "showReportLog(this);"' : '' ?>>
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
                                            <?php if ($AMI_ENABLE == 1) { ?>
                                                <input type="submit" name="export" value="Export" class="btn btn-info" style="background-color: #39B54A !important;">
                                            <?php } ?>
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
        calculateWeek: function(date) {
            return '#' + getBusinessWeek(date).week;
        },

        beforeShowDay: function(date) {
            return [date.getDay() === 0, ''];
        },

        onSelect: function(dateText) {
            let d = new Date(dateText);
            let bw = getBusinessWeek(d);

            let start_date = (d.getMonth() + 1) + '/' + d.getDate() + '/' + d.getFullYear();

            $(this).closest('form').find('#start_date').val(start_date);
            $(this).val("Week Number " + bw.week);
        }
    });


    function getBusinessWeek(date) {
        let d = new Date(date);
        d.setDate(d.getDate() - d.getDay());

        let year = d.getFullYear();

        // Logic for up to 2025
        if (year <= 2025) {
            let yearStart = new Date(year, 0, 1);
            yearStart.setDate(yearStart.getDate() - yearStart.getDay());

            let week = Math.floor((d - yearStart) / (7 * 24 * 60 * 60 * 1000)) + 1;

            return {
                week,
                year
            };
        }

        // Logic for 2026 onward
        let firstSunday = new Date(year, 0, 1);
        if (firstSunday.getDay() !== 0) {
            firstSunday.setDate(1 + (7 - firstSunday.getDay()));
        }

        let diff = d - firstSunday;
        let week = Math.floor(diff / (7 * 24 * 60 * 60 * 1000)) + 1;

        // If date falls before first Sunday, belongs to previous year
        if (week <= 0) {
            return getBusinessWeek(new Date(year - 1, 11, 31));
        }

        return {
            week,
            year
        };
    }


    /* function wk(d) {
        var d = new Date(d);
        d.setDate(d.getDate() - 363);
        return '#' + $.datepicker(d);
    } */

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