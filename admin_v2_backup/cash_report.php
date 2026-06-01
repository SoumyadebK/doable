<?php
require_once('../global/config.php');
$title = "Cash Report";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

if (!empty($_GET['NAME'])) {
    $type = isset($_GET['view']) ? 'view' : 'generate_excel';
    $generate_pdf = isset($_GET['generate_pdf']) ? 1 : 0;
    $generate_excel = isset($_GET['generate_excel']) ? 1 : 0;
    $report_name = $_GET['NAME'];
    $START_DATE = $_GET['START_DATE'];
    $END_DATE = $_GET['END_DATE'];
    $selectedProviders = $_GET['SERVICE_PROVIDER_ID'];
    $SERVICE_PROVIDER_ID = implode(', ', $selectedProviders);

    if ($_GET['NAME'] == 'cash_report') {
        if ($generate_pdf === 1) {
            header('location:generate_report_pdf.php?service_provider_id=' . $SERVICE_PROVIDER_ID . '&start_date=' . $START_DATE . '&end_date=' . $END_DATE . '&report_type=' . $report_name);
        } elseif ($generate_excel === 1) {
            header('location:excel_' . $report_name . '.php?service_provider_id=' . $SERVICE_PROVIDER_ID . '&start_date=' . $START_DATE . '&end_date=' . $END_DATE . '&type=' . $type);
        } else {
            header('location:cash_report_details.php?service_provider_id=' . $SERVICE_PROVIDER_ID . '&start_date=' . $START_DATE . '&end_date=' . $END_DATE . '&type=' . $type);
        }
    } else if ($_GET['NAME'] == 'lessons_taught_by_department_report') {
        if ($generate_pdf === 1) {
            header('location:generate_report_pdf.php?service_provider_id=' . $SERVICE_PROVIDER_ID . '&start_date=' . $START_DATE . '&end_date=' . $END_DATE . '&report_type=' . $report_name);
        } elseif ($generate_excel === 1) {
            header('location:excel_' . $report_name . '.php?service_provider_id=' . $SERVICE_PROVIDER_ID . '&start_date=' . $START_DATE . '&end_date=' . $END_DATE . '&report_type=' . $report_name);
        } else {
            header('location:lessons_taught_by_department_report.php?service_provider_id=' . $SERVICE_PROVIDER_ID . '&start_date=' . $START_DATE . '&end_date=' . $END_DATE . '&type=' . $type);
        }
    } else if ($_GET['NAME'] == 'sales_by_enrollment_report') {
        if ($generate_pdf === 1) {
            header('location:generate_report_pdf.php?service_provider_id=' . $SERVICE_PROVIDER_ID . '&start_date=' . $START_DATE . '&end_date=' . $END_DATE . '&report_type=' . $report_name);
        } elseif ($generate_excel === 1) {
            header('location:excel_' . $report_name . '.php?service_provider_id=' . $SERVICE_PROVIDER_ID . '&start_date=' . $START_DATE . '&end_date=' . $END_DATE . '&report_type=' . $report_name);
        } else {
            header('location:sales_by_enrollment_report.php?service_provider_id=' . $SERVICE_PROVIDER_ID . '&start_date=' . $START_DATE . '&end_date=' . $END_DATE . '&type=' . $type);
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
<?php include 'layout/header_script.php'; ?>
<?php require_once('../includes/header.php'); ?>
<?php include 'layout/header.php'; ?>

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

        <div class="page-wrapper" style="padding-top: 0px !important;">

            <?php require_once('layout/report_menu.php') ?>
            <div class="container-fluid" style="padding: 10px 20px 0 20px; margin-top: 0px;">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="row" style="padding: 15px 35px 35px 35px;">
                                <!-- <div class="col-md-3 col-sm-3 mt-3">
                                    <h4 class="card-title">Cash Report</h4>
                                </div> -->

                                <form class="form-material form-horizontal" action="" method="get">
                                    <div class="row">
                                        <div class="col-2">
                                            <div class="form-group">
                                                <select class="form-control" required name="NAME" id="NAME">
                                                    <option value="">Select Report</option>
                                                    <option value="cash_report">CASH REPORT</option>
                                                    <!-- <option value="lessons_taught_by_department_report">LESSONS TAUGHT BY DEPARTMENT</option>
                                                    <option value="sales_by_enrollment_report">SALES BY ENROLLMENT REPORT</option> -->
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-2">
                                            <div>
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
                                                <!-- <input type="submit" name="export" value="Export" class="btn btn-info" style="background-color: #39B54A !important;"> -->
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
    $('.datepicker-normal').datepicker({
        format: 'mm/dd/yyyy',
    });

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


    // function selectReport(param) {
    //     let selectedReport = $(param).val();

    //     // Hide all fields first
    //     $('.service_provider_div').hide();
    //     $('.start_date').hide();
    //     $('.end_date').hide();
    //     $('.selected_date').hide();
    //     $('.selected_range').hide();

    //     // Remove required attributes first
    //     $('#START_DATE').prop('required', false);
    //     $('#END_DATE').prop('required', false);
    //     $('#SELECTED_DATE').prop('required', false);
    //     $('#SELECTED_RANGE').prop('required', false);

    //     // Show fields based on selected report
    //     if (selectedReport === 'cash_report') {
    //         // For CASH REPORT: show service provider, start date, end date
    //         $('.service_provider_div').show();
    //         $('.start_date').show();
    //         $('.end_date').show();
    //         $('#START_DATE').prop('required', true);
    //         $('#END_DATE').prop('required', true);
    //     } else if (selectedReport === 'lessons_taught_by_department_report' || selectedReport === 'sales_by_enrollment_report') {
    //         // For other two reports: show service provider, selected date, range
    //         $('.service_provider_div').show();
    //         $('.selected_date').show();
    //         $('.selected_range').show();
    //         $('#SELECTED_DATE').prop('required', true);
    //         $('#SELECTED_RANGE').prop('required', true);
    //     }
    // }

    // // Initialize on page load
    // $(document).ready(function() {
    //     // Hide all fields initially
    //     $('.service_provider_div').hide();
    //     $('.start_date').hide();
    //     $('.end_date').hide();
    //     $('.selected_date').hide();
    //     $('.selected_range').hide();

    //     // If there's already a selected value, trigger the change
    //     if ($('#NAME').val()) {
    //         selectReport(document.getElementById('NAME'));
    //     }

    //     // Also bind the change event properly
    //     $('#NAME').on('change', function() {
    //         selectReport(this);
    //     });
    // });
</script>