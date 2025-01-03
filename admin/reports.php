<?php
require_once('../global/config.php');
$title = "Reports";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5]) ){
    header("location:../login.php");
    exit;
}

if (!empty($_GET['NAME'])) {
    $type = isset($_GET['view']) ? 'view' : 'export';
    $WEEK_NUMBER = explode(' ', $_GET['WEEK_NUMBER'])[2];
    $START_DATE = $_GET['start_date'];

    if ($_GET['NAME'] == 'royalty_service_report') {
        header('location:royalty_service_report.php?week_number='.$WEEK_NUMBER.'&start_date='.$START_DATE.'&type='.$type);
    } elseif ($_GET['NAME'] == 'summary_of_studio_business_report'){
        header('location:summary_of_studio_business_report.php?week_number='.$WEEK_NUMBER.'&start_date='.$START_DATE.'&type='.$type);
    } elseif ($_GET['NAME'] == 'staff_performance_report'){
        header('location:staff_performance_report.php?week_number='.$WEEK_NUMBER.'&start_date='.$START_DATE.'&type='.$type);
    } elseif ($_GET['NAME'] == 'summary_of_staff_member_report'){
        header('location:summary_of_staff_member_report.php?week_number='.$WEEK_NUMBER.'&start_date='.$START_DATE.'&type='.$type);
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
                                <h4 class="card-title">Reports</h4>
                                <div>
                                    <ul class="menu-list">
                                        <li><a href="#">Business Reports</a></li>
                                        <li><a href="#">Enrollment Reports</a></li>
                                        <li><a href="#">Service Provider Reports</a></li>
                                        <li><a href="customer_summary_report.php">Customer Reports</a></li>
                                        <li><a href="student_mailing_list.php">Student Mailing List</a></li>
                                        <li><a href="total_open_liability.php">Total Open Liability Since Last Activity</a></li>
                                        <!--<li><a href="royalty_service_report.php">Royalty / Service Report</a></li>
                                        <li><a href="summary_of_studio_business_report.php">Summary of Studio Business Report</a></li>
                                        <li><a href="staff_performance_report.php">Staff Performance Report</a></li>-->
                                    </ul>
                                </div>
                            </div>
                        </div>
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
                                    <div class="col-4">
                                        <div class="form-group">
                                            <select class="form-control" required name="NAME" id="NAME">
                                                <option value="">Select Report</option>
                                                <option value="royalty_service_report">ROYALTY / SERVICE REPORT</option>
                                                <option value="summary_of_studio_business_report">SUMMARY OF STUDIO BUSINESS REPORT</option>
                                                <option value="staff_performance_report">STAFF PERFORMANCE REPORT</option>
                                                <option value="summary_of_staff_member_report">SUMMARY OF STAFF MEMBER REPORT</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-3">
                                        <div class="form-group">
                                            <input type="text" id="WEEK_NUMBER" name="WEEK_NUMBER" class="form-control datepicker-normal week-picker" placeholder="Start Date" value="<?=!empty($_GET['WEEK_NUMBER'])?$_GET['WEEK_NUMBER']:''?>" required>
                                        </div>
                                    </div>
                                    <div class="col-2">
                                        <?php if(in_array('Reports Create', $PERMISSION_ARRAY)){ ?>
                                        <input type="submit" name="view" id="submit"  value="View" style="background-color: #39B54A; border-color: #39B54A; padding: 5px 10px; color: white; font-size: 15px; border-radius: 5px;">
                                        <input type="submit" name="export" id="submit" style="background-color: #39B54A; border-color: #39B54A; padding: 5px 10px; color: white; font-size: 15px; border-radius: 5px;" value="Export">
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
            $('#start_date').val((d.getMonth()+1)+'/'+d.getDate()+'/'+d.getFullYear());
            d.setDate(d.getDate() -363);
            $(this).val("Week Number " + $.datepicker.iso8601Week(d));
        }
    });

    function wk(d) {
        var d = new Date(d);
        d.setDate(d.getDate() -363);
        return '#' + $.datepicker.iso8601Week(d);
    }

    function generateReport(){
        let week_number = $('#WEEK_NUMBER').val();
        $.ajax({
            url: "ajax/AjaxFunctions.php",
            type: "POST",
            data: {FUNCTION_NAME:'generateAmReport', week_number:week_number},
            async: false,
            cache: false,
            success: function (result) {
                console.log(result);
            }
        });
    }
</script>