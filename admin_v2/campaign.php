<?php
require_once('../global/config.php');
$title = "Campaign";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

// Sanitize and validate inputs
function sanitizeInput($input)
{
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

if (!empty($_GET['NAME'])) {
    $reportName = sanitizeInput($_GET['NAME']);
    $type = isset($_GET['view']) ? 'view' : 'generate_excel';
    $generate_excel = isset($_GET['generate_excel']) ? 1 : 0;

    // Handle the two reports
    if ($reportName == 'student_mailing_list.php' || $reportName == 'leads_report.php') {
        if ($generate_excel === 1) {
            header('location:excel_' . $reportName . '?report_type=' . $reportName);
        } else {
            header('location:' . $reportName . '?type=' . $type);
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
<link href="../assets/sumoselect/sumoselect.min.css" rel="stylesheet" />
<link href="https://fonts.googleapis.com/css2?family=PT+Mono&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="assets/css/setup-styles.css" rel="stylesheet">

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
                                    <h4 class="card-title">Campaign</h4>
                                </div>
                                <form class="form-material form-horizontal" action="" method="get" id="reportForm">
                                    <div class="row">
                                        <div class="col-3">
                                            <div class="form-group">
                                                <label class="field-label">Select Report <span class="required-star">*</span></label>
                                                <select class="form-control" required name="NAME" id="NAME">
                                                    <option value="">Select Report</option>
                                                    <option value="student_mailing_list.php" <?= (isset($_GET['NAME']) && $_GET['NAME'] == 'student_mailing_list.php') ? 'selected' : '' ?>>Student Mailing List</option>
                                                    <option value="leads_report.php" <?= (isset($_GET['NAME']) && $_GET['NAME'] == 'leads_report.php') ? 'selected' : '' ?>>Leads Report</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-4" style="padding-top: 28px;">
                                            <?php if (in_array('Reports Create', $PERMISSION_ARRAY)) { ?>
                                                <input type="submit" name="view" value="View" class="btn btn-info" style="background-color: #39B54A !important;">
                                                <!-- <input type="submit" name="generate_excel" value="Generate Excel" class="btn btn-info" style="background-color: #39B54A !important;"> -->
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

<script>
    $(document).ready(function() {
        // Add date validation
        $('#reportForm').submit(function(e) {
            let selectedReport = $('#NAME').val();

            if (!selectedReport) {
                e.preventDefault();
                showError('Please select a report.');
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
    });
</script>