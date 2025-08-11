<?php
require_once('../global/config.php');
$title = "Cas Report";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

if (!empty($_GET['NAME'])) {
    $type = isset($_GET['view']) ? 'view' : 'export';
    $generate_pdf = isset($_GET['generate_pdf']) ? 1 : 0;
    $generate_excel = isset($_GET['generate_excel']) ? 1 : 0;
    $report_name = $_GET['NAME'];
    $START_DATE = $_GET['start_date'];
    $END_DATE = $_GET['end_date'];
    $selectedProviders = $_GET['SERVICE_PROVIDER_ID'];
    $SERVICE_PROVIDER_ID = implode(', ', $selectedProviders);

    if ($generate_pdf === 1) {
        header('location:generate_report_pdf.php?week_number=' . $WEEK_NUMBER . '&start_date=' . $START_DATE . '&report_type=' . $report_name);
    } elseif ($generate_excel === 1) {
        header('location:excel_' . $report_name . '.php?week_number=' . $WEEK_NUMBER . '&start_date=' . $START_DATE . '&report_type=' . $report_name);
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
            header('location:summary_of_staff_member_report.php?start_date=' . $START_DATE . '&type=' . $type);
        } elseif ($_GET['NAME'] == 'cash_report') {
            header('location:cash_report_details.php?service_provider_id=' . $SERVICE_PROVIDER_ID . '&start_date=' . $START_DATE . '&end_date=' . $END_DATE . '&type=' . $type);
        }
    }
}

$mail_url = parse_url($_SERVER['REQUEST_URI']);
$url_array = explode("/", $mail_url['path']);
if ($_SERVER['HTTP_HOST'] == 'localhost') {
    $current_address = $url_array[3];
} else {
    $current_address = $url_array[2];
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
                        if ($address == "business_reports.php" || $address == "business_reports.php" || $address == "service_provider_reports.php" || $address == "electronic_miscellaneous_reports.php" || $address == "all_locations.php" || $address == "all_users.php" || $address == "total_open_liability.php" || $address == "active_account_balance_report.php" || $address == "cash_report.php") { ?>
                            <ul class="nav nav-pills justify-content-left">
                                <li class="nav-item"><a class="nav-link <?= ($address == 'reports.php') ? 'active' : '' ?>" href="../admin/reports.php">Electronic Weekly Reports</a></li>
                                <li class="nav-item"><a class="nav-link <?= ($address == 'business_reports.php') ? 'active' : '' ?>" href="../admin/business_reports.php">Business Reports</a></li>
                                <li class="nav-item"><a class="nav-link <?= ($address == 'service_provider_reports.php') ? 'active' : '' ?>" href="../admin/service_provider_reports.php">Service Provider Reports</a></li>
                                <li class="nav-item"><a class="nav-link <?= ($address == 'electronic_miscellaneous_reports.php') ? 'active' : '' ?>" href="../admin/electronic_miscellaneous_reports.php">Electronic Miscellaneous Reports</a></li>
                                <!-- <li class="nav-item"><a class="nav-link <?= ($address == 'enrollment_reports.php') ? 'active' : '' ?>" href="../admin/enrollment_reports.php">Enrollment Reports</a></li> -->
                                <li class="nav-item"><a class="nav-link <?= ($address == 'customer_reports.php') ? 'active' : '' ?>" href="../admin/customer_reports.php">Customer Summary Report</a></li>
                                <li class="nav-item"><a class="nav-link <?= ($address == 'student_mailing_list.php') ? 'active' : '' ?>" href="../admin/student_mailing_list.php">Student Mailing List</a></li>
                                <li class="nav-item"><a class="nav-link <?= ($address == 'total_open_liability.php') ? 'active' : '' ?>" href="../admin/total_open_liability.php">Total Open Liability Since Last Activity</a></li>
                                <li class="nav-item"><a class="nav-link <?= ($address == 'active_account_balance_report.php') ? 'active' : '' ?>" href="../admin/active_account_balance_report.php">Active Account Balance Report</a></li>
                                <li class="nav-item"><a class="nav-link <?= ($address == 'cash_report.php') ? 'active' : '' ?>" href="../admin/cash_report.php">Cash Report</a></li>
                            </ul>
                        <?php } ?>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="row" style="padding: 15px 35px 35px 35px;">
                                <div class="col-md-3 col-sm-3 mt-3">
                                    <h4 class="card-title">Cash Report</h4>
                                </div>
                            
                                <form class="form-material form-horizontal" action="" method="get">
                                    <input type="hidden" name="start_date" id="start_date">
                                    <input type="hidden" name="end_date" id="end_date">
                                    <div class="row">
                                        <div class="col-2">
                                            <div class="form-group">
                                                <select class="form-control" required name="NAME" id="NAME" onchange="showReportLog(this);">
                                                    <option value="">Select Report</option>
                                                    <option value="cash_report">CASH REPORT</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-2">
                                            <div style=" width: 315px;">
                                                <select name="SERVICE_PROVIDER_ID[]" class="SERVICE_PROVIDER_ID multi_sumo_select" id="SERVICE_PROVIDER_ID" multiple>
                                                    <?php
                                                    $row = $db->Execute("SELECT DISTINCT (DOA_USERS.PK_USER), CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER LEFT JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER WHERE DOA_USER_LOCATION.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_USER_ROLES.PK_ROLES = 5 AND DOA_USERS.ACTIVE = 1 AND DOA_USERS.IS_DELETED = 0 AND DOA_USERS.PK_ACCOUNT_MASTER = " . $_SESSION['PK_ACCOUNT_MASTER'] . " ORDER BY DOA_USERS.FIRST_NAME, DOA_USERS.LAST_NAME");
                                                    while (!$row->EOF) { ?>
                                                        <option value="<?php echo $row->fields['PK_USER']; ?>"><?= $row->fields['NAME'] ?></option>
                                                    <?php $row->MoveNext();
                                                    } ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-2">
                                            <div class="form-group">
                                                <input type="text" id="START_DATE" name="START_DATE" class="form-control datepicker-normal" placeholder="Start Date" value="<?= !empty($_GET['START_DATE']) ? $_GET['START_DATE'] : '' ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-2">
                                            <div class="form-group">
                                                <input type="text" id="END_DATE" name="END_DATE" class="form-control datepicker-normal" placeholder="End Date" value="<?= !empty($_GET['END_DATE']) ? $_GET['END_DATE'] : '' ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <?php if (in_array('Reports Create', $PERMISSION_ARRAY)) { ?>
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
    <?php require_once('../includes/footer.php'); ?>
</body>

</html>
<script>
     $('.multi_sumo_select').SumoSelect({
        placeholder: 'Select Service Provider',
        selectAll: true,
        search: true,
        searchText: 'Search...'
    });

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
            $.ajax({
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
            });
        }
    });

    $(document).ready(function() {
        $("#START_DATE").datepicker({
            numberOfMonths: 1,
            onSelect: function(dateText, inst) {
                let d = new Date(dateText);
                let start_date = (d.getMonth() + 1) + '/' + d.getDate() + '/' + d.getFullYear();
                $(this).closest('form').find('#start_date').val(start_date);
                $("#END_DATE").datepicker("option", "minDate", dateText);
                $("#START_DATE, #END_DATE").trigger("change");
            }
        });
        $("#END_DATE").datepicker({
            numberOfMonths: 1,
            onSelect: function(dateText, inst) {
                let d = new Date(dateText);
                let end_date = (d.getMonth() + 1) + '/' + d.getDate() + '/' + d.getFullYear();
                $(this).closest('form').find('#end_date').val(end_date);
                $("#START_DATE").datepicker("option", "maxDate", dateText)
            }
        });
    });

    function wk(d) {
        var d = new Date(d);
        d.setDate(d.getDate() - 363);
        return '#' + $.datepicker.iso8601Week(d);
    }

    function showReportLog(param) {
        let report_type = $(param).closest('form').find('#NAME').val();
        $.ajax({
            url: "includes/get_report_details.php",
            type: "POST",
            data: {
                REPORT_TYPE: report_type
            },
            async: false,
            cache: false,
            success: function(result) {
                $(param).closest('form').find('#export_log').html(result);
            }
        });
    }
</script>