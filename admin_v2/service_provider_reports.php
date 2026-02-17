<?php
require_once('../global/config.php');
$title = "Service Provider Reports";

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
    $END_DATE = $_GET['end_date'];
    $PK_USER = empty($_GET['PK_USER']) ? 0 : $_GET['PK_USER'];
    $include_no_provider = isset($_GET['include_no_provider']) ? 1 : 0;

    if ($generate_pdf === 1) {
        header('location:generate_report_pdf.php?week_number=' . $WEEK_NUMBER . '&start_date=' . $START_DATE . '&end_date=' . $END_DATE . '&report_type=' . $report_name . '&PK_USER=' . implode(',', $PK_USER));
    } elseif ($generate_excel === 1) {
        header('location:excel_' . $report_name . '.php?week_number=' . $WEEK_NUMBER . '&start_date=' . $START_DATE . '&end_date=' . $END_DATE . '&report_type=' . $report_name . '&PK_USER=' . implode(',', $PK_USER));
    } else {
        if ($_GET['NAME'] == 'summary_of_staff_member_report') {
            header('location:summary_of_staff_member_report.php?week_number=' . $WEEK_NUMBER . '&start_date=' . $START_DATE . '&end_date=' . $END_DATE . '&type=' . $type . '&PK_USER=' . implode(',', $PK_USER));
        } elseif ($_GET['NAME'] == 'lessons_taught_by_department_report') {
            header('location:lessons_taught_by_department_report.php?week_number=' . $WEEK_NUMBER . '&start_date=' . $START_DATE . '&end_date=' . $END_DATE . '&type=' . $type . '&service_provider_id=' . implode(',', $PK_USER));
        } elseif ($_GET['NAME'] == 'sales_by_enrollment_report') {
            header('location:sales_by_enrollment_report.php?week_number=' . $WEEK_NUMBER . '&start_date=' . $START_DATE . '&end_date=' . $END_DATE . '&type=' . $type . '&service_provider_id=' . implode(',', $PK_USER) . '&include_no_provider=' . $include_no_provider);
        } elseif ($_GET['NAME'] == 'enrollment_details_report') {
            header('location:enrollment_details_report.php?start_date=' . $START_DATE . '&end_date=' . $END_DATE . '&view=1');
        }
    }
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

    .export-buttons {
        display: none;
    }

    .no-provider-checkbox {
        margin-top: 10px;
        display: none;
    }

    .no-provider-checkbox label {
        font-weight: normal;
        margin-left: 5px;
    }

    /* Add smooth transition for hiding/showing */
    .service-provider-column {
        transition: all 0.3s ease;
    }

    .service-provider-column.hidden {
        display: none !important;
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
                                <div class="col-md-3 col-sm-3 mt-3">
                                    <h4 class="card-title">Service Provider Reports</h4>
                                </div>
                                <form class="form-material form-horizontal" action="" method="get" id="reportForm">
                                    <input type="hidden" name="start_date" id="start_date">
                                    <input type="hidden" name="end_date" id="end_date">
                                    <div class="row">
                                        <div class="col-2">
                                            <div class="form-group">
                                                <select class="form-control" required name="NAME" id="NAME" onchange="showReportLog(this); toggleExportButtons(this); toggleNoProviderOption(this); toggleServiceProviderVisibility(this);">
                                                    <option value="">Select Report</option>
                                                    <option value="summary_of_staff_member_report">SUMMARY OF STAFF MEMBER REPORT</option>
                                                    <option value="lessons_taught_by_department_report">LESSONS TAUGHT BY DEPARTMENT</option>
                                                    <option value="sales_by_enrollment_report">SALES BY ENROLLMENT REPORT</option>
                                                    <option value="enrollment_details_report">ENROLLMENT TYPE DETAILED REPORT</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-2 service-provider-column" id="service_provider_column">
                                            <div id="location" style="width: 100%;">
                                                <select class="multi_select_service_provider" multiple id="service_provider_select" name="PK_USER[]">
                                                    <?php
                                                    $row = $db->Execute("SELECT DISTINCT DOA_USERS.PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER LEFT JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER WHERE DOA_USERS.ACTIVE = 1 AND DOA_USER_ROLES.PK_ROLES = 5 AND DOA_USER_LOCATION.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
                                                    while (!$row->EOF) { ?>
                                                        <option value="<?php echo $row->fields['PK_USER']; ?>"><?= $row->fields['NAME'] ?></option>
                                                    <?php $row->MoveNext();
                                                    } ?>
                                                </select>
                                            </div>
                                            <div class="no-provider-checkbox" id="no_provider_checkbox">
                                                <input type="checkbox" id="include_no_provider" name="include_no_provider" value="1" <?= isset($_GET['include_no_provider']) && $_GET['include_no_provider'] == 1 ? 'checked' : '' ?>>
                                                <label for="include_no_provider">With No Service Provider</label>
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
                                                <span class="export-buttons" id="exportButtons">
                                                    <input type="submit" name="export" value="Export" class="btn btn-info" style="background-color: #39B54A !important;">
                                                    <input type="submit" name="generate_pdf" value="Generate PDF" class="btn btn-info" style="background-color: #39B54A !important;">
                                                </span>
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
    $('.multi_select_service_provider').SumoSelect({
        placeholder: 'Select Service Provider',
        selectAll: true,
        triggerChangeCombined: true
    });

    // Function to toggle Export and PDF buttons visibility
    function toggleExportButtons(selectElement) {
        var selectedValue = selectElement.value;
        var exportButtons = document.getElementById('exportButtons');

        if (selectedValue === 'summary_of_staff_member_report') {
            exportButtons.style.display = 'inline';
        } else {
            exportButtons.style.display = 'none';
        }
    }

    // Function to toggle "With No Service Provider" checkbox
    function toggleNoProviderOption(selectElement) {
        var selectedValue = selectElement.value;
        var noProviderCheckbox = document.getElementById('no_provider_checkbox');

        if (selectedValue === 'sales_by_enrollment_report') {
            noProviderCheckbox.style.display = 'block';
        } else {
            noProviderCheckbox.style.display = 'none';
            // Uncheck the checkbox when switching to other reports
            document.getElementById('include_no_provider').checked = false;
        }
    }

    // Function to toggle Service Provider selection visibility
    function toggleServiceProviderVisibility(selectElement) {
        var selectedValue = selectElement.value;
        var serviceProviderColumn = document.getElementById('service_provider_column');

        if (selectedValue === 'enrollment_details_report') {
            // Hide the entire service provider column for enrollment details report
            serviceProviderColumn.classList.add('hidden');

            // Adjust other columns to use the space
            adjustColumnLayout(true);
        } else {
            // Show service provider column for other reports
            serviceProviderColumn.classList.remove('hidden');

            // Reset column layout
            adjustColumnLayout(false);
        }
    }

    // Function to adjust column layout when hiding/showing service provider column
    function adjustColumnLayout(hideProviderColumn) {
        var dateColumns = document.querySelectorAll('.col-2');
        var actionColumn = document.querySelector('.col-4:last-child');

        // This is a simplified adjustment - you might want to adjust based on your specific layout needs
        if (hideProviderColumn) {
            // When hiding provider column, you could expand date columns or action column
            // For example, you could add a class to make date columns wider
            dateColumns.forEach(function(col) {
                if (!col.classList.contains('service-provider-column')) {
                    col.classList.add('expanded-col');
                }
            });
        } else {
            // Remove expanded class when showing provider column
            dateColumns.forEach(function(col) {
                col.classList.remove('expanded-col');
            });
        }
    }

    // Initialize visibility on page load
    document.addEventListener('DOMContentLoaded', function() {
        var reportSelect = document.getElementById('NAME');
        toggleExportButtons(reportSelect);
        toggleNoProviderOption(reportSelect);
        toggleServiceProviderVisibility(reportSelect);
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

        // Handle form submission to ensure PK_USER values are properly sent
        $('#reportForm').on('submit', function() {
            var selectedReport = $('#NAME').val();
            var selectedProviders = $('#service_provider_select').val();
            var includeNoProvider = $('#include_no_provider').is(':checked');

            // For enrollment details report, don't require service provider selection
            if (selectedReport === 'enrollment_details_report') {
                // Always allow submission - service provider not required
                return true;
            }

            // For sales by enrollment report, allow submission even if no providers are selected
            // when "Include Enrollments With No Service Provider" is checked
            if (selectedReport === 'sales_by_enrollment_report') {
                if ((!selectedProviders || selectedProviders.length === 0) && !includeNoProvider) {
                    alert('Please select at least one service provider or check "Include Enrollments With No Service Provider".');
                    return false;
                }
            } else {
                // For other reports, require at least one service provider
                if (!selectedProviders || selectedProviders.length === 0) {
                    alert('Please select at least one service provider.');
                    return false;
                }
            }

            return true;
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