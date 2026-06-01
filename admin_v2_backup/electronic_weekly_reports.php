<?php
require_once('../global/config.php');
$title = "Electronic Weekly Reports";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5]) ){
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
        header('location:generate_report_pdf.php?week_number='.$WEEK_NUMBER.'&start_date='.$START_DATE.'&report_type='.$report_name);
    } elseif ($generate_excel === 1) {
        header('location:excel_'.$report_name.'.php?week_number='.$WEEK_NUMBER.'&start_date='.$START_DATE.'&report_type='.$report_name);
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
                <div class="col-md-5 align-self-center">
                    <h4 class="text-themecolor"><?=$title?></h4>
                </div>
                <div class="col-md-7 align-self-center text-end">
                    <div class="d-flex justify-content-end align-items-center">
                        <ol class="breadcrumb justify-content-end">
                            <li class="breadcrumb-item"><a href="reports.php">All Reports</a></li>
                            <li class="breadcrumb-item active"><?=$title?></li>
                        </ol>
                    </div>
                </div>
            </div>
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
                                            <input type="text" id="WEEK_NUMBER1" name="WEEK_NUMBER" class="form-control datepicker-normal week-picker" placeholder="Start Date" value="<?=!empty($_GET['WEEK_NUMBER'])?$_GET['WEEK_NUMBER']:''?>" required>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <?php if(in_array('Reports Create', $PERMISSION_ARRAY)){ ?>
                                            <input type="submit" name="view" value="View" class="btn btn-info" style="background-color: #39B54A !important;">
                                            <input type="submit" name="export" value="Export" class="btn btn-info" style="background-color: #39B54A !important;">
                                            <input type="submit" name="generate_pdf" value="Generate PDF" class="btn btn-info" style="background-color: #39B54A !important;">
                                            <input type="submit" name="generate_excel" value="Generate Excel" class="btn btn-info" style="background-color: #39B54A !important;">
                                        <?php } ?>
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