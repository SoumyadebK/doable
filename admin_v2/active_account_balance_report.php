<?php
require_once('../global/config.php');
$title = "Active Account Balance Report";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

// Sanitize and validate inputs
function sanitizeInput($input)
{
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validateDate($date, $format = 'm/d/Y')
{
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

if (!empty($_GET['NAME'])) {
    $reportName = sanitizeInput($_GET['NAME']);
    $type = isset($_GET['view']) ? 'view' : 'generate_excel';
    $generate_excel = isset($_GET['generate_excel']) ? 1 : 0;

    // Handle active_account_balance_report
    if ($reportName == 'active_account_balance_report') {
        $SELECTED_DATE = isset($_GET['SELECTED_DATE']) ? sanitizeInput($_GET['SELECTED_DATE']) : '';
        $SELECTED_RANGE = isset($_GET['SELECTED_RANGE']) ? (int)$_GET['SELECTED_RANGE'] : '';

        // Validate inputs
        if (empty($SELECTED_DATE) || empty($SELECTED_RANGE)) {
            $_SESSION['error_message'] = 'Please select both date and range.';
            header('location: ' . $_SERVER['PHP_SELF']);
            exit;
        }

        if ($generate_excel === 1) {
            header('location:excel_' . $reportName . '.php?selected_date=' . urlencode($SELECTED_DATE) . '&selected_range=' . $SELECTED_RANGE . '&report_type=' . $reportName);
        } else {
            header('location:active_account_balance_report_details.php?selected_date=' . urlencode($SELECTED_DATE) . '&selected_range=' . $SELECTED_RANGE . '&type=' . $type);
        }
        exit;
    }
    // Handle nfa_active_customers_report
    else if ($reportName == 'nfa_active_customers_report') {
        $FROM_DATE = isset($_GET['FROM_DATE']) ? sanitizeInput($_GET['FROM_DATE']) : '';
        $TO_DATE = isset($_GET['TO_DATE']) ? sanitizeInput($_GET['TO_DATE']) : '';

        // Validate inputs
        if (empty($FROM_DATE) || empty($TO_DATE)) {
            $_SESSION['error_message'] = 'Please select both from and to dates.';
            header('location: ' . $_SERVER['PHP_SELF']);
            exit;
        }

        // Validate date range
        if (!validateDate($FROM_DATE) || !validateDate($TO_DATE)) {
            $_SESSION['error_message'] = 'Invalid date format. Please use MM/DD/YYYY.';
            header('location: ' . $_SERVER['PHP_SELF']);
            exit;
        }

        $from = DateTime::createFromFormat('m/d/Y', $FROM_DATE);
        $to = DateTime::createFromFormat('m/d/Y', $TO_DATE);
        if ($from > $to) {
            $_SESSION['error_message'] = 'From Date must be before To Date.';
            header('location: ' . $_SERVER['PHP_SELF']);
            exit;
        }

        if ($generate_excel === 1) {
            header('location:excel_' . $reportName . '.php?from_date=' . urlencode($FROM_DATE) . '&to_date=' . urlencode($TO_DATE) . '&report_type=' . $reportName);
        } else {
            header('location:nfa_active_customers_report.php?from_date=' . urlencode($FROM_DATE) . '&to_date=' . urlencode($TO_DATE) . '&type=' . $type);
        }
        exit;
    }
    // Handle nfa_active_no_enrollments_report
    else if ($reportName == 'nfa_active_no_enrollments_report') {
        $APPOINTMENT_TYPE = isset($_GET['APPOINTMENT_TYPE']) ? sanitizeInput($_GET['APPOINTMENT_TYPE']) : 'all';

        if ($generate_excel === 1) {
            header('location:excel_' . $reportName . '.php?appointment_type=' . urlencode($APPOINTMENT_TYPE) . '&report_type=' . $reportName);
        } else {
            header('location:nfa_active_no_enrollments_report.php?appointment_type=' . urlencode($APPOINTMENT_TYPE) . '&type=' . $type);
        }
        exit;
    }
    // Handle customer_summary_report
    else if ($reportName == 'customer_summary_report') {
        if ($generate_excel === 1) {
            header('location:excel_' . $reportName . '.php?report_type=' . $reportName);
        } else {
            header('location:customer_summary_report.php?type=' . $type);
        }
        exit;
    }
}

// Display error message if exists
if (isset($_SESSION['error_message'])) {
    $errorMessage = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}
?>

<!DOCTYPE html>
<html lang="en">
<?php include 'layout/header_script.php'; ?>
<?php require_once('../includes/header.php'); ?>
<?php include 'layout/header.php'; ?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<link href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css" rel="stylesheet">

<style>
    .btn {
        border: 0;
        color: #fff;
        border-radius: 50rem;
        padding-left: 1.5rem;
        padding-right: 1.5rem;
    }

    input.form-control,
    select.form-control,
    textarea.form-control {
        border-radius: 0.375rem !important;
    }

    .alert-custom {
        margin: 10px 20px 0 20px;
    }

    .required-star {
        color: red;
        margin-left: 3px;
    }

    .field-label {
        font-weight: 500;
        margin-bottom: 5px;
        font-size: 14px;
    }
</style>

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">
        <div class="page-wrapper" style="padding-top: 1px !important;">

            <?php require_once('layout/report_menu.php') ?>
            <div class="container-fluid" style="padding: 10px 20px 0 20px; margin-top: 0px;">

                <?php if (isset($errorMessage)): ?>
                    <div class="row">
                        <div class="col-12">
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <?= htmlspecialchars($errorMessage) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="row" style="padding: 15px 15px 15px 35px;">
                                <div class="col-md-3 col-sm-3 mt-3">
                                    <h4 class="card-title">Customer Reports</h4>
                                </div>
                                <form class="form-material form-horizontal" action="" method="get" id="reportForm">
                                    <input type="hidden" name="selected_date" id="selected_date">
                                    <div class="row">
                                        <div class="col-3">
                                            <div class="form-group">
                                                <label class="field-label">Select Report <span class="required-star">*</span></label>
                                                <select class="form-control" required name="NAME" id="NAME">
                                                    <option value="">Select Report</option>
                                                    <option value="active_account_balance_report" <?= (isset($_GET['NAME']) && $_GET['NAME'] == 'active_account_balance_report') ? 'selected' : '' ?>>ACTIVE ACCOUNT BALANCE REPORT</option>
                                                    <option value="nfa_active_customers_report" <?= (isset($_GET['NAME']) && $_GET['NAME'] == 'nfa_active_customers_report') ? 'selected' : '' ?>>NFA ACTIVE CUSTOMERS REPORT</option>
                                                    <option value="nfa_active_no_enrollments_report" <?= (isset($_GET['NAME']) && $_GET['NAME'] == 'nfa_active_no_enrollments_report') ? 'selected' : '' ?>>NFA ACTIVE NO ENROLLMENTS REPORT</option>
                                                    <option value="customer_summary_report" <?= (isset($_GET['NAME']) && $_GET['NAME'] == 'customer_summary_report') ? 'selected' : '' ?>>CUSTOMER SUMMARY REPORT</option>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Fields for Active Account Balance Report -->
                                        <div class="col-2 selected_date" style="display: none;">
                                            <div class="form-group">
                                                <label class="field-label">Select Date <span class="required-star">*</span></label>
                                                <input type="text" id="SELECTED_DATE" name="SELECTED_DATE" class="form-control datepicker-normal" placeholder="Select Date" value="<?= !empty($_GET['SELECTED_DATE']) ? htmlspecialchars($_GET['SELECTED_DATE']) : '' ?>">
                                            </div>
                                        </div>
                                        <div class="col-2 selected_range" style="display: none;">
                                            <div class="form-group">
                                                <label class="field-label">Select Range <span class="required-star">*</span></label>
                                                <select class="form-control" name="SELECTED_RANGE" id="SELECTED_RANGE">
                                                    <option value="">Select Range</option>
                                                    <option value="1" <?= (isset($_GET['SELECTED_RANGE']) && $_GET['SELECTED_RANGE'] == '1') ? 'selected' : '' ?>>1 Month Prior</option>
                                                    <option value="3" <?= (isset($_GET['SELECTED_RANGE']) && $_GET['SELECTED_RANGE'] == '3') ? 'selected' : '' ?>>3 Months Prior</option>
                                                    <option value="6" <?= (isset($_GET['SELECTED_RANGE']) && $_GET['SELECTED_RANGE'] == '6') ? 'selected' : '' ?>>6 Months Prior</option>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Fields for NFA Active Customers Report -->
                                        <div class="col-2 from_date" style="display: none;">
                                            <div class="form-group">
                                                <label class="field-label">From Date <span class="required-star">*</span></label>
                                                <input type="text" id="FROM_DATE" name="FROM_DATE" class="form-control datepicker-normal" placeholder="From Date" value="<?= !empty($_GET['FROM_DATE']) ? htmlspecialchars($_GET['FROM_DATE']) : '' ?>">
                                            </div>
                                        </div>
                                        <div class="col-2 to_date" style="display: none;">
                                            <div class="form-group">
                                                <label class="field-label">To Date <span class="required-star">*</span></label>
                                                <input type="text" id="TO_DATE" name="TO_DATE" class="form-control datepicker-normal" placeholder="To Date" value="<?= !empty($_GET['TO_DATE']) ? htmlspecialchars($_GET['TO_DATE']) : '' ?>">
                                            </div>
                                        </div>

                                        <!-- Fields for NFA Active No Enrollments Report -->
                                        <div class="col-2 appointment_type" style="display: none;">
                                            <div class="form-group">
                                                <label class="field-label">Appointment Type <span class="required-star">*</span></label>
                                                <select class="form-control" name="APPOINTMENT_TYPE" id="APPOINTMENT_TYPE">
                                                    <option value="all" <?= (isset($_GET['APPOINTMENT_TYPE']) && $_GET['APPOINTMENT_TYPE'] == 'all') ? 'selected' : '' ?>>All</option>
                                                    <option value="with_previous" <?= (isset($_GET['APPOINTMENT_TYPE']) && $_GET['APPOINTMENT_TYPE'] == 'with_previous') ? 'selected' : '' ?>>With Previous Appointments</option>
                                                    <option value="without_previous" <?= (isset($_GET['APPOINTMENT_TYPE']) && $_GET['APPOINTMENT_TYPE'] == 'without_previous') ? 'selected' : '' ?>>Without Previous Appointments</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-4" style="padding-top: 28px;">
                                            <?php if (in_array('Reports Create', $PERMISSION_ARRAY)) { ?>
                                                <input type="submit" name="view" value="View" class="btn btn-info" style="background-color: #39B54A !important;">
                                                <input type="submit" name="generate_excel" value="Generate Excel" class="btn btn-info" style="background-color: #39B54A !important;">
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
    <?php require_once('../includes/footer.php'); ?>
</body>

</html>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    $(document).ready(function() {
        // Initialize datepicker
        $('.datepicker-normal').datepicker({
            format: 'mm/dd/yyyy',
            autoclose: true,
            todayHighlight: true
        });

        // Function to show/hide fields based on selected report
        function selectReport(selectedReport) {
            // Remove required attributes first
            $('#SELECTED_DATE').prop('required', false);
            $('#SELECTED_RANGE').prop('required', false);
            $('#APPOINTMENT_TYPE').prop('required', false);
            $('#FROM_DATE').prop('required', false);
            $('#TO_DATE').prop('required', false);

            // Hide all conditional fields
            $('.selected_date').hide();
            $('.selected_range').hide();
            $('.appointment_type').hide();
            $('.from_date').hide();
            $('.to_date').hide();

            // Show fields based on selected report
            if (selectedReport === 'active_account_balance_report') {
                $('.selected_date').show();
                $('.selected_range').show();
                $('#SELECTED_DATE').prop('required', true);
                $('#SELECTED_RANGE').prop('required', true);
            } else if (selectedReport === 'nfa_active_no_enrollments_report') {
                $('.appointment_type').show();
                $('#APPOINTMENT_TYPE').prop('required', true);
            } else if (selectedReport === 'nfa_active_customers_report') {
                $('.from_date').show();
                $('.to_date').show();
                $('#FROM_DATE').prop('required', true);
                $('#TO_DATE').prop('required', true);
            }
        }

        // Handle report selection change
        $('#NAME').on('change', function() {
            selectReport($(this).val());
        });

        // Initialize on page load
        if ($('#NAME').val()) {
            selectReport($('#NAME').val());
        }

        // Add date validation
        $('#reportForm').submit(function(e) {
            let selectedReport = $('#NAME').val();
            let isValid = true;
            let errorMessage = '';

            if (selectedReport === 'active_account_balance_report') {
                let selectedDate = $('#SELECTED_DATE').val();
                let selectedRange = $('#SELECTED_RANGE').val();

                if (!selectedDate) {
                    errorMessage = 'Please select a date.';
                    isValid = false;
                } else if (!selectedRange) {
                    errorMessage = 'Please select a range.';
                    isValid = false;
                }
            } else if (selectedReport === 'nfa_active_customers_report') {
                let fromDate = $('#FROM_DATE').val();
                let toDate = $('#TO_DATE').val();

                if (!fromDate || !toDate) {
                    errorMessage = 'Please select both From and To dates.';
                    isValid = false;
                } else {
                    // Parse dates
                    let from = new Date(fromDate);
                    let to = new Date(toDate);

                    if (isNaN(from.getTime()) || isNaN(to.getTime())) {
                        errorMessage = 'Invalid date format. Please use MM/DD/YYYY.';
                        isValid = false;
                    } else if (from > to) {
                        errorMessage = 'From Date must be before To Date.';
                        isValid = false;
                    }
                }
            } else if (selectedReport === 'nfa_active_no_enrollments_report') {
                let appointmentType = $('#APPOINTMENT_TYPE').val();
                if (!appointmentType) {
                    errorMessage = 'Please select an appointment type.';
                    isValid = false;
                }
            } else if (!selectedReport) {
                errorMessage = 'Please select a report.';
                isValid = false;
            }

            if (!isValid) {
                e.preventDefault();
                // Show error using Bootstrap alert or custom alert
                showError(errorMessage);
                return false;
            }

            return true;
        });

        // Function to show error messages
        function showError(message) {
            // Remove any existing alerts
            $('.alert-custom').remove();

            // Create and show alert
            let alertHtml = `
            <div class="row alert-custom">
                <div class="col-12">
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
            </div>
        `;

            // Insert alert before the card
            $('.container-fluid > .row:first').before(alertHtml);

            // Auto dismiss after 5 seconds
            setTimeout(function() {
                $('.alert-custom .alert').fadeOut('slow', function() {
                    $(this).parent().remove();
                });
            }, 5000);
        }

        // Function to show success message (optional)
        function showSuccess(message) {
            let alertHtml = `
            <div class="row alert-custom">
                <div class="col-12">
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
            </div>
        `;

            $('.container-fluid > .row:first').before(alertHtml);

            setTimeout(function() {
                $('.alert-custom .alert').fadeOut('slow', function() {
                    $(this).parent().remove();
                });
            }, 5000);
        }
    });
</script>