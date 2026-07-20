<?php
require_once('../global/config.php');
$title = "All Follow Ups";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

// Handle AJAX request for status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    // Get the raw POST data
    $automation_id = isset($_POST['automation_id']) ? intval($_POST['automation_id']) : 0;
    $is_active = isset($_POST['is_active']) ? intval($_POST['is_active']) : 0;
    $PK_ACCOUNT_MASTER = $_SESSION['PK_ACCOUNT_MASTER'];

    if ($automation_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid automation ID']);
        exit;
    }

    // Update the automation status
    $update_data = array(
        'IS_ACTIVE' => $is_active,
        'EDITED_BY' => $_SESSION['PK_USER'],
        'EDITED_ON' => date("Y-m-d H:i:s")
    );

    $result = db_perform_account('DOA_AUTOMATIONS', $update_data, 'update', " PK_AUTOMATION_ID = '$automation_id' AND PK_ACCOUNT_MASTER = '$PK_ACCOUNT_MASTER'");

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update status in database']);
    }
    exit;
}

// Fetch all automations/follow ups
$query = "SELECT * FROM DOA_AUTOMATIONS WHERE PK_ACCOUNT_MASTER = " . intval($_SESSION['PK_ACCOUNT_MASTER']) . " ORDER BY PK_AUTOMATION_ID DESC";
$automations = $db_account->Execute($query);

// Function to format time ago
function time_ago($datetime)
{
    if (empty($datetime)) return "never";
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;

    if ($diff < 60) {
        return "just now";
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . " minute" . ($mins > 1 ? "s" : "") . " ago";
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . " hour" . ($hours > 1 ? "s" : "") . " ago";
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . " day" . ($days > 1 ? "s" : "") . " ago";
    } else {
        return date("M j, Y", $time);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'layout/header_script.php'; ?>
<?php //require_once('../includes/header.php'); 
?>
<?php include 'layout/header.php'; ?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> - Setup Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="assets/css/setup-styles.css" rel="stylesheet">
</head>
<style>
    body {
        background-color: #f8f9fa;
        font-family: 'Inter', sans-serif;
        color: #333;
    }

    .sidebar-card {
        background: #ffffff;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        min-height: 100%;
    }

    .sidebar-section {
        margin-bottom: 1.5rem;
    }

    .sidebar-section:last-child {
        margin-bottom: 0;
    }

    .section-title {
        font-size: 0.7rem;
        font-weight: 700;
        color: #a0aec0;
        letter-spacing: 0.05em;
        margin-bottom: 0.5rem;
        padding-left: 0.5rem;
    }

    .sidebar-card .nav-link {
        color: #4a5568;
        font-size: 0.85rem;
        font-weight: 500;
        padding: 0.5rem 0.75rem;
        border-radius: 6px;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        transition: all 0.2s ease;
    }

    .sidebar-card .nav-link i {
        font-size: 1rem;
        color: #718096;
    }

    .sidebar-card .nav-link .dot-icon {
        font-size: 1.2rem;
        line-height: 1;
        color: #718096;
        margin-left: 2px;
        margin-right: 2px;
    }

    .sidebar-card .nav-link:hover {
        background-color: #f8fafc;
        color: #1a202c;
    }

    .sidebar-card .nav-link.active {
        background-color: #f1f5f9;
        color: #10b981 !important;
        font-weight: 600;
    }

    .sidebar-card .nav-link.active i {
        color: #10b981;
    }

    .main-card {
        background: #ffffff;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    }

    .extra-small {
        font-size: 0.65rem;
        letter-spacing: 0.03em;
        font-weight: 600;
    }

    .automation-card {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        background: #ffffff;
        transition: all 0.2s ease;
    }

    .automation-card:hover {
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

    .icon-wrapper {
        width: 40px;
        height: 40px;
        background-color: #f1f5f9;
        border-radius: 8px;
        flex-shrink: 0;
    }

    .icon-wrapper i {
        font-size: 1.1rem;
    }

    .custom-switch {
        position: relative;
    }

    .custom-switch .form-check-input {
        width: 2.5em;
        height: 1.35em;
        background-color: #39b54a;
        border-color: transparent;
        cursor: pointer;
    }

    .custom-switch .form-check-input:checked {
        background-color: #39b54a;
        border-color: transparent;
    }

    .custom-switch .form-check-input:focus {
        box-shadow: none;
        border-color: transparent;
    }

    .btn-add-followup {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        color: #4a5568;
        font-size: 0.9rem;
        transition: all 0.2s ease;
    }

    .btn-add-followup:hover {
        background-color: #f8fafc;
        border-color: #cbd5e1;
        color: #1a202c;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
    }

    .empty-state i {
        font-size: 4rem;
        color: #cbd5e1;
        margin-bottom: 1rem;
    }

    .empty-state h4 {
        color: #4a5568;
        margin-bottom: 0.5rem;
    }

    .empty-state p {
        color: #a0aec0;
        margin-bottom: 1.5rem;
    }

    .toggle-loading {
        opacity: 0.5;
        pointer-events: none;
    }

    /* Toast notifications */
    .toast-container {
        z-index: 9999;
    }
</style>

<body>

    <div class="container-fluid py-4 px-4 m-auto mx-auto dashboard-container">
        <div class="row g-4">
            <div class="col-12 col-md-4 col-xl-2">
                <?php include 'layout/setup_sidebar.php'; ?>
            </div>

            <div class="col-12 col-md-8 col-xl-10">
                <div class="col-12 col-md-8 col-lg-12">
                    <div class="main-card">
                        <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
                            <div>
                                <h2 class="fw-semibold h4 mb-1"><i class="bi bi-journal-text me-2" style="color: #39b54a;"></i>Automations</h2>
                                <p class="text-muted small mb-0">Enable automatic to-do's</p>
                            </div>
                            <button class="btn btn-success-custom rounded-pill d-flex align-items-center gap-2" onclick="window.location.href='add_follow_up.php'">
                                <i class="bi bi-plus-lg"></i> Add Follow Up
                            </button>
                        </div>


                        <div id="automationsList">
                            <?php if ($automations && $automations->RecordCount() > 0): ?>
                                <?php while (!$automations->EOF):
                                    $automation = $automations->fields;
                                    $trigger_text = "When a customer completes a class";
                                    $condition_text = !empty($automation['CONDITION_TYPE']) ? "and has not purchased a contract" : "";
                                    $edited_time = !empty($automation['EDITED_ON']) ? $automation['EDITED_ON'] : $automation['CREATED_ON'];
                                ?>
                                    <div class="automation-card p-3 mb-3 d-flex align-items-start justify-content-between" data-automation-id="<?= $automation['PK_AUTOMATION_ID'] ?>">
                                        <div class="d-flex align-items-start gap-3">
                                            <div class="icon-wrapper d-flex align-items-center justify-content-center">
                                                <i class="bi bi-lightning-charge-fill text-secondary"></i>
                                            </div>
                                            <div>
                                                <h3 class="h6 mb-1 fw-semibold text-dark edit-automation" data-id="<?= $automation['PK_AUTOMATION_ID'] ?>" style="cursor: pointer;"><?= htmlspecialchars($automation['TITLE']) ?></h3>
                                                <p class="text-muted small mb-1"><?= $trigger_text ?> <?= $condition_text ?></p>
                                                <span class="text-uppercase text-muted extra-small edited-time">EDITED <?= strtoupper(time_ago($edited_time)) ?></span>
                                            </div>
                                        </div>

                                        <div class="d-flex align-items-center gap-3 pt-1">
                                            <div class="form-check form-switch custom-switch d-flex align-items-center gap-2 m-0 p-0">
                                                <input class="form-check-input m-0 toggle-automation" type="checkbox" role="switch"
                                                    data-id="<?= $automation['PK_AUTOMATION_ID'] ?>"
                                                    <?= $automation['IS_ACTIVE'] ? 'checked' : '' ?>>
                                                <label class="form-check-label text-dark small fw-medium toggle-label">
                                                    <?= $automation['IS_ACTIVE'] ? 'On' : 'Off' ?>
                                                </label>
                                            </div>
                                            <button class="btn btn-link text-muted p-0 border-0 edit-automation"
                                                data-id="<?= $automation['PK_AUTOMATION_ID'] ?>">
                                                <i class="bi bi-chevron-right fs-5"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php
                                    $automations->MoveNext();
                                endwhile;
                                ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="bi bi-envelope-paper"></i>
                                    <h4>No automations yet</h4>
                                    <p>Create your first automation to get started</p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <button class="btn btn-add-followup w-100 py-2.5 fw-medium" onclick="window.location='add_follow_up.php'">
                            Add Follow Up
                        </button>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <script>
        $(document).ready(function() {
            // Handle toggle switch change
            $('.toggle-automation').on('change', function() {
                const $toggle = $(this);
                const $automationCard = $toggle.closest('.automation-card');
                const automationId = $toggle.data('id');
                const isActive = $toggle.is(':checked') ? 1 : 0;
                const $label = $toggle.closest('.custom-switch').find('.toggle-label');
                const originalState = !$toggle.is(':checked');
                const originalLabelText = $label.text();

                // Disable toggle during AJAX request
                $toggle.prop('disabled', true);
                $automationCard.addClass('toggle-loading');

                // Show loading state
                $label.html('<span class="spinner-border spinner-border-sm me-1" style="width: 0.8rem; height: 0.8rem;"></span> Saving...');

                // Send AJAX request
                $.ajax({
                    url: window.location.href,
                    type: 'POST',
                    data: {
                        automation_id: automationId,
                        is_active: isActive
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Update label text
                            $label.text(isActive ? 'On' : 'Off');

                            // Update the edited time
                            $automationCard.find('.edited-time').text('EDITED JUST NOW');

                            // Show success message
                            showToast('Status updated successfully', 'success');
                        } else {
                            // Revert toggle if failed
                            $toggle.prop('checked', originalState);
                            $label.text(originalLabelText);
                            showToast('Error: ' + response.message, 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', error);
                        console.log('Response:', xhr.responseText);
                        // Revert toggle
                        $toggle.prop('checked', originalState);
                        $label.text(originalLabelText);
                        showToast('Error updating status. Please try again.', 'error');
                    },
                    complete: function() {
                        // Re-enable toggle
                        $toggle.prop('disabled', false);
                        $automationCard.removeClass('toggle-loading');
                    }
                });
            });

            // Handle edit button click
            $('.edit-automation').on('click', function() {
                const automationId = $(this).data('id');
                window.location.href = 'add_follow_up.php?id=' + automationId;
            });
        });

        // Toast notification function
        function showToast(message, type = 'success') {
            // Create toast container if it doesn't exist
            let toastContainer = document.querySelector('.toast-container');
            if (!toastContainer) {
                toastContainer = document.createElement('div');
                toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
                document.body.appendChild(toastContainer);
            }

            // Create toast element
            const toastId = 'toast-' + Date.now();
            const bgColor = type === 'success' ? 'bg-success' : 'bg-danger';

            const toastHtml = `
            <div id="${toastId}" class="toast" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="3000">
                <div class="toast-header ${bgColor} text-white">
                    <i class="bi ${type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'} me-2"></i>
                    <strong class="me-auto">${type === 'success' ? 'Success' : 'Error'}</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">
                    ${message}
                </div>
            </div>
        `;

            toastContainer.insertAdjacentHTML('beforeend', toastHtml);
            const toastElement = document.getElementById(toastId);
            const toast = new bootstrap.Toast(toastElement);
            toast.show();

            toastElement.addEventListener('hidden.bs.toast', function() {
                toastElement.remove();
            });
        }
    </script>

</body>

</html>