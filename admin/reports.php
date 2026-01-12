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
    $START_DATE = $_GET['START_DATE'] ?? '';
    $END_DATE   = $_GET['END_DATE'] ?? '';

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
        } elseif ($_GET['NAME'] == 'semi_annual_student_inventory_report') {
            header('location:semi_annual_student_inventory_report.php?week_number=' . $WEEK_NUMBER . '&start_date=' . $START_DATE . '&end_date=' . $END_DATE . '&type=' . $type);
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
                        <!-- Electronic Weekly Reports -->
                        <div class="card mb-4">
                            <div class="row" style="padding: 15px 35px 35px 35px;">
                                <div class="col-md-3 col-sm-3 mt-3">
                                    <h4 class="card-title">Electronic Weekly Reports</h4>
                                </div>
                                <form class="form-material form-horizontal" action="" method="get" id="weeklyForm">
                                    <input type="hidden" name="START_DATE" id="weekly_start_date">
                                    <div class="row">
                                        <div class="col-2">
                                            <div class="form-group">
                                                <select class="form-control" required name="NAME" id="weekly_NAME" <?= ($AMI_ENABLE == 1) ? 'onchange = "showReportLog(this, \'weekly_export_log\');"' : '' ?>>
                                                    <option value="">Select Report</option>
                                                    <option value="royalty_service_report">ROYALTY / SERVICE REPORT</option>
                                                    <option value="summary_of_studio_business_report">SUMMARY OF STUDIO BUSINESS REPORT</option>
                                                    <option value="staff_performance_report">STAFF PERFORMANCE REPORT</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-2">
                                            <div class="form-group">
                                                <input type="text" id="weekly_WEEK_NUMBER" name="WEEK_NUMBER" class="form-control datepicker-normal week-picker-weekly" placeholder="Start Date" required>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <input type="submit" name="view" value="View" class="btn btn-info" style="background-color: #39B54A !important;">
                                            <?php if ($AMI_ENABLE == 1) { ?>
                                                <input type="submit" name="export" value="Export" class="btn btn-info" style="background-color: #39B54A !important;">
                                            <?php } ?>
                                            <input type="submit" name="generate_pdf" value="Generate PDF" class="btn btn-info" style="background-color: #39B54A !important;">
                                            <input type="submit" name="generate_excel" value="Generate Excel" class="btn btn-info" style="background-color: #39B54A !important;">
                                        </div>
                                        <div class="col-4">
                                            <p id="weekly_last_export_message" style="color: red; margin-top: 9px;"></p>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-4" id="weekly_export_log"></div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Extra Reports -->
                        <div class="card">
                            <div class="row" style="padding: 15px 35px 35px 35px;">
                                <div class="col-md-3 col-sm-3 mt-3">
                                    <h4 class="card-title">Extra Reports</h4>
                                </div>
                                <form class="form-material form-horizontal" action="" method="get" id="extraForm">
                                    <!-- <input type="hidden" name="start_date" id="extra_start_date"> -->
                                    <div class="row">
                                        <div class="col-2">
                                            <div class="form-group">
                                                <select class="form-control" required name="NAME">
                                                    <option value="">Select Report</option>
                                                    <option value="semi_annual_student_inventory_report">SEMI ANNUAL STUDENT INVENTORY REPORT</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-2">
                                            <div class="form-group">
                                                <input type="text" id="START_DATE" name="START_DATE" class="form-control" placeholder="Start Date" value="<?= !empty($_GET['START_DATE']) ? $_GET['START_DATE'] : '' ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-2">
                                            <div class="form-group">
                                                <input type="text" id="END_DATE" name="END_DATE" class="form-control" placeholder="End Date" value="<?= !empty($_GET['END_DATE']) ? $_GET['END_DATE'] : '' ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <input type="submit" name="view" value="View" class="btn btn-info" style="background-color: #39B54A !important;">
                                            <!-- <?php if ($AMI_ENABLE == 1) { ?>
                                                <input type="submit" name="export" value="Export" class="btn btn-info" style="background-color: #39B54A !important;">
                                            <?php } ?> -->
                                            <!-- <input type="submit" name="generate_pdf" value="Generate PDF" class="btn btn-info" style="background-color: #39B54A !important;">
                                            <input type="submit" name="generate_excel" value="Generate Excel" class="btn btn-info" style="background-color: #39B54A !important;"> -->
                                        </div>
                                        <div class="col-4">
                                            <p id="extra_last_export_message" style="color: red; margin-top: 9px;"></p>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-4" id="extra_export_log"></div>
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
    $(function() {
        $(".datepicker-normal").datepicker({
            dateFormat: "mm/dd/yy",
            changeMonth: true,
            changeYear: true,
            yearRange: "2000:2035"
        });
    });
</script>

<script>
    $(".week-picker-weekly").datepicker({
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

            $('#weekly_start_date').val(start_date);
            $(this).val("Week Number " + bw.week);
        }
    });

    function startOfWeekSunday(d) {
        let s = new Date(d);
        s.setHours(0, 0, 0, 0);
        s.setDate(s.getDate() - s.getDay()); // Sunday = 0
        return s;
    }

    function getBusinessWeek(date) {
        let d = new Date(date);
        d.setHours(0, 0, 0, 0);

        let weekStart = startOfWeekSunday(d);

        // Decide which year this week belongs to (majority rule)
        let yearCounts = {};
        for (let i = 0; i < 7; i++) {
            let wd = new Date(weekStart);
            wd.setDate(weekStart.getDate() + i);
            let y = wd.getFullYear();
            yearCounts[y] = (yearCounts[y] || 0) + 1;
        }

        let weekYear = Object.keys(yearCounts).reduce(function(a, b) {
            return yearCounts[a] >= yearCounts[b] ? a : b;
        });

        weekYear = parseInt(weekYear, 10);

        // Find Week 1 start for that weekYear:
        // First Sunday whose week has >=4 days in weekYear
        let jan1 = new Date(weekYear, 0, 1);
        jan1.setHours(0, 0, 0, 0);

        let w1 = startOfWeekSunday(jan1);

        let daysInWeekYear = 0;
        for (let j = 0; j < 7; j++) {
            let d1 = new Date(w1);
            d1.setDate(w1.getDate() + j);
            if (d1.getFullYear() === weekYear) {
                daysInWeekYear++;
            }
        }

        if (daysInWeekYear < 4) {
            w1.setDate(w1.getDate() + 7);
        }

        // Count Sundays between w1 and weekStart
        let weeks = 0;
        let cursor = new Date(w1);

        while (cursor <= weekStart) {
            weeks++;
            cursor.setDate(cursor.getDate() + 7);
        }

        return {
            week: weeks,
            year: weekYear
        };
    }


    $(document).ready(function() {
        $("#START_DATE").datepicker({
            numberOfMonths: 1,
            onSelect: function(selected) {
                $("#END_DATE").datepicker("option", "minDate", selected);
                $("#START_DATE, #END_DATE").trigger("change");
            }
        });
        $("#END_DATE").datepicker({
            numberOfMonths: 1,
            onSelect: function(selected) {
                $("#START_DATE").datepicker("option", "maxDate", selected)
            }
        });
    });

    function showReportLog(param, logDivId) {
        $('#' + logDivId).html('<p style="font-size: 16px;">Loading Submission Log <i class="fas fa-spinner fa-pulse" style="font-size: 20px;"></i></p>');

        let report_type = $(param).closest('form').find('select[name="NAME"]').val();
        $.ajax({
            url: "includes/get_report_details.php",
            type: "POST",
            data: {
                REPORT_TYPE: report_type
            },
            success: function(result) {
                $('#' + logDivId).html(result);
            }
        });
    }
</script>