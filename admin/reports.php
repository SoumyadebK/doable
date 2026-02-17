<?php
require_once('../global/config.php');
global $AMI_ENABLE;
$title = "Reports";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];
$LOCATION_ARRAY = explode(',', $DEFAULT_LOCATION_ID);

$location_data = $db->Execute("SELECT * FROM `DOA_LOCATION` WHERE `PK_LOCATION` = " . $LOCATION_ARRAY[0]);
$AMI_ENABLE = $location_data->fields['FRANCHISE'];

if (!empty($_GET['NAME'])) {
    $type = isset($_GET['view']) ? 'view' : 'export';
    $generate_pdf = isset($_GET['generate_pdf']) ? 1 : 0;
    $generate_excel = isset($_GET['generate_excel']) ? 1 : 0;
    $report_name = $_GET['NAME'];

    // Check if it's a package report (Electronic Miscellaneous Report)
    if (is_numeric($report_name)) {
        // This is a package report from Electronic Miscellaneous Report section
        $package_id = $report_name;
        $transportation_charges = $_GET['TRANSPORTATION_CHARGES'] ?? 0;
        $package_costs = $_GET['PACKAGE_COSTS'] ?? 0;

        if ($generate_excel === 1) {
            header('location:excel_miscellaneous_service_summary_report.php?PK_PACKAGE=' . $package_id . '&TRANSPORTATION_CHARGES=' . $transportation_charges . '&PACKAGE_COSTS=' . $package_costs . '&type=' . $type);
        } else {
            header('location:miscellaneous_service_summary_report.php?PK_PACKAGE=' . $package_id . '&TRANSPORTATION_CHARGES=' . $transportation_charges . '&PACKAGE_COSTS=' . $package_costs . '&type=' . $type);
        }
        exit;
    }

    // Existing code for other reports
    $WEEK_NUMBER = explode(' ', $_GET['WEEK_NUMBER'])[2];
    $START_DATE = $_GET['START_DATE'] ?? '';
    $END_DATE   = $_GET['END_DATE'] ?? '';

    if ($generate_pdf === 1) {
        header('location:generate_report_pdf.php?week_number=' . $WEEK_NUMBER . '&start_date=' . $START_DATE . '&end_date=' . $END_DATE . '&report_type=' . $report_name);
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
<style>
    .form-group {
        display: flex;
        align-items: center;
    }

    .form-group label {
        width: 170px;
        /* Adjust label width */
    }

    .form-group input {
        flex: 1;
        /* Takes remaining space */
        padding: 5px;
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
                                            <input type="submit" name="generate_pdf" value="Generate PDF" class="btn btn-info" style="background-color: #39B54A !important;">
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

                        <!-- Electronic Miscellaneous Reports -->
                        <div class="card">
                            <div class="row" style="padding: 15px 35px 35px 35px;">
                                <div class="col-md-12 col-sm-3 mt-3">
                                    <h4 class="card-title">Electronic Miscellaneous Report: *Deduction will be automatically created upon submission*</h4>
                                </div>
                                <form class="form-material form-horizontal" action="" method="get" id="miscForm">
                                    <input type="hidden" name="start_date" id="misc_start_date">
                                    <input type="hidden" name="end_date" id="misc_end_date">
                                    <div class="row">
                                        <div class="col-2">
                                            <div class="form-group">
                                                <select class="form-control" required name="NAME" id="misc_NAME" onchange="showMiscReportLog(this);">
                                                    <option value="">Select a package</option>
                                                    <?php
                                                    $row = $db_account->Execute("SELECT DOA_PACKAGE.PK_PACKAGE, DOA_PACKAGE.PACKAGE_NAME FROM DOA_PACKAGE LEFT JOIN DOA_PACKAGE_SERVICE ON DOA_PACKAGE.PK_PACKAGE = DOA_PACKAGE_SERVICE.PK_PACKAGE LEFT JOIN DOA_SERVICE_MASTER ON DOA_SERVICE_MASTER.PK_SERVICE_MASTER = DOA_PACKAGE_SERVICE.PK_SERVICE_MASTER WHERE DOA_SERVICE_MASTER.PK_SERVICE_CLASS = 5 AND DOA_PACKAGE.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_PACKAGE.ACTIVE = 1");
                                                    while (!$row->EOF) { ?>
                                                        <option value="<?= $row->fields['PK_PACKAGE'] ?>"><?= $row->fields['PACKAGE_NAME'] ?></option>
                                                    <?php $row->MoveNext();
                                                        $i++;
                                                    } ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-2">
                                            <div class="form-group">
                                                <label for="TRANSPORTATION_CHARGES">Transportation Charges : $</label>
                                                <input type="text" id="TRANSPORTATION_CHARGES" name="TRANSPORTATION_CHARGES" class="form-control" placeholder="" value="<?= !empty($_GET['TRANSPORTATION_CHARGES']) ? $_GET['TRANSPORTATION_CHARGES'] : '' ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-2">
                                            <div class="form-group">
                                                <label for="PACKAGE_COSTS">Package Costs : $</label>
                                                <input style="margin-left: -50px" type="text" id="PACKAGE_COSTS" name="PACKAGE_COSTS" class="form-control" placeholder="" value="<?= !empty($_GET['PACKAGE_COSTS']) ? $_GET['PACKAGE_COSTS'] : '' ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <?php if (in_array('Reports Create', $PERMISSION_ARRAY)) { ?>
                                                <input type="submit" name="view" value="View" class="btn btn-info" style="background-color: #39B54A !important;">
                                                <?php if ($AMI_ENABLE == 1) { ?>
                                                    <input type="submit" name="export" value="Export" class="btn btn-info" style="background-color: #39B54A !important;">
                                                <?php } ?>
                                                <input type="submit" name="generate_excel" value="Generate Excel" class="btn btn-info" style="background-color: #39B54A !important;">
                                            <?php } ?>
                                        </div>
                                        <div class="col-4">
                                            <p id="misc_last_export_message" style="color: red; margin-top: 9px;"></p>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-4" id="misc_export_log">
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

        // Add datepicker for misc form dates if needed
        $("#miscForm input[name='start_date'], #miscForm input[name='end_date']").datepicker({
            dateFormat: "mm/dd/yy",
            changeMonth: true,
            changeYear: true
        });
    });

    function showReportLog(param, logDivId) {
        let form = $(param).closest('form');
        let report_type = form.find('select[name="NAME"]').val();
        let logDiv = logDivId ? '#' + logDivId : '#misc_export_log';

        $(logDiv).html('<p style="font-size: 16px;">Loading Submission Log <i class="fas fa-spinner fa-pulse" style="font-size: 20px;"></i></p>');

        $.ajax({
            url: "includes/get_report_details.php",
            type: "POST",
            data: {
                REPORT_TYPE: report_type
            },
            success: function(result) {
                $(logDiv).html(result);
            },
            error: function() {
                $(logDiv).html('<p style="color: red;">Error loading submission log</p>');
            }
        });
    }

    function showMiscReportLog(param) {
        let form = $(param).closest('form');
        let package_id = form.find('select[name="NAME"]').val();
        let logDiv = '#misc_export_log';

        // For package reports, show a simple message
        if (!package_id || package_id === '') {
            $(logDiv).html('');
            return;
        }

        $(logDiv).html('<p style="font-size: 16px;">Loading submission log for package report... <i class="fas fa-spinner fa-pulse" style="font-size: 20px;"></i></p>');

        // If you want to show logs for package reports too, you might need to create a separate endpoint
        // For now, let's just show a generic message
        $.ajax({
            url: "includes/get_report_details.php",
            type: "POST",
            data: {
                REPORT_TYPE: 'miscellaneous_service_summary_report',
                PACKAGE_ID: package_id
            },
            success: function(result) {
                $(logDiv).html(result);
            },
            error: function() {
                $(logDiv).html('<p style="color: #666;">No submission history available for this package.</p>');
            }
        });
    }
</script>