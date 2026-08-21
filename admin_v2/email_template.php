<?php
require_once('../global/config.php');

if (empty($_GET['id']))
    $title = "Add Email Template";
else
    $title = "Edit Email Template";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

$error_message = '';
$success_message = '';

// Get parameters from URL (for "Set up" button)
$preset_location = isset($_GET['location']) ? $_GET['location'] : '';
$preset_type = isset($_GET['type']) ? $_GET['type'] : '';

if (!empty($_POST)) {
    $EMAIL_ACCOUNT_DATA = $_POST;
    unset($EMAIL_ACCOUNT_DATA['TEMP_CONTENT']);
    $EMAIL_ACCOUNT_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
    if ($_GET['id'] == '') {
        if (!isset($EMAIL_ACCOUNT_DATA['ACTIVE'])) {
            $EMAIL_ACCOUNT_DATA['ACTIVE'] = 1;
        }
        $EMAIL_ACCOUNT_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
        $EMAIL_ACCOUNT_DATA['CREATED_ON'] = date("Y-m-d H:i");
        db_perform_account('DOA_EMAIL_TEMPLATE', $EMAIL_ACCOUNT_DATA, 'insert');
        $success_message = "Template created successfully!";
        header("location:all_email_templates.php?success=1");
        exit;
    } else {
        $EMAIL_ACCOUNT_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
        $EMAIL_ACCOUNT_DATA['EDITED_ON'] = date("Y-m-d H:i");
        db_perform_account('DOA_EMAIL_TEMPLATE', $EMAIL_ACCOUNT_DATA, 'update', " PK_EMAIL_TEMPLATE = '$_GET[id]'");
        $success_message = "Template updated successfully!";
        header("location:all_email_templates.php?success=1");
        exit;
    }
}

// Get template data for editing
if (empty($_GET['id'])) {
    $TEMPLATE_NAME      = $preset_type ? strtoupper(str_replace(' ', '_', $preset_type)) : '';
    $PK_LOCATION        = $preset_location;
    $SUBJECT            = '';
    $PK_TEMPLATE_CATEGORY = '';
    $PK_EMAIL_TRIGGER     = '';
    $PK_EMAIL_ACCOUNT   = '';
    $CONTENT            = '';
    $ACTIVE             = '';
    $template_display_name = '';

    // If preset_type is provided, set display name
    if ($preset_type) {
        $template_display_name = ucwords(str_replace('_', ' ', $preset_type));
    }
} else {
    $res = $db_account->Execute("SELECT * FROM DOA_EMAIL_TEMPLATE WHERE PK_EMAIL_TEMPLATE = '$_GET[id]'");
    if ($res->RecordCount() == 0) {
        header("location:all_email_templates.php");
        exit;
    }
    $TEMPLATE_NAME      = $res->fields['TEMPLATE_NAME'];
    $PK_LOCATION        = $res->fields['PK_LOCATION'];
    $SUBJECT            = $res->fields['SUBJECT'];
    $PK_TEMPLATE_CATEGORY = $res->fields['PK_TEMPLATE_CATEGORY'];
    $PK_EMAIL_TRIGGER     = $res->fields['PK_EMAIL_TRIGGER'];
    $PK_EMAIL_ACCOUNT   = $res->fields['PK_EMAIL_ACCOUNT'];
    $CONTENT            = $res->fields['CONTENT'];
    $ACTIVE             = $res->fields['ACTIVE'];

    // Get display name for the template type
    if ($TEMPLATE_NAME == 'APPOINTMENT_CREATION') {
        $template_display_name = 'Appointment Creation';
    } elseif ($TEMPLATE_NAME == 'ENROLLMENT_CREATION') {
        $template_display_name = 'Enrollment Creation';
    } else {
        $template_display_name = $TEMPLATE_NAME;
    }
}

// Get available locations for the dropdown
$locations = [];
$row = $db->Execute("SELECT * FROM DOA_LOCATION WHERE PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND ACTIVE = 1 AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
while (!$row->EOF) {
    $locations[] = [
        'PK_LOCATION' => $row->fields['PK_LOCATION'],
        'LOCATION_NAME' => $row->fields['LOCATION_NAME']
    ];
    $row->MoveNext();
}
?>

<!DOCTYPE html>
<html lang="en">
<?php include 'layout/header_script.php'; ?>
<?php require_once('../includes/header.php'); ?>
<?php include 'layout/header.php'; ?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="assets/css/setup-styles.css" rel="stylesheet">

<style>
    :root {
        --primary-color: #39B54A;
        --primary-light: #5DCB6E;
        --primary-dark: #2D8F3B;
        --primary-rgb: 57, 181, 74;
        --success-color: #39B54A;
        --warning-color: #F59E0B;
        --danger-color: #EF4444;
        --gray-50: #F9FAFB;
        --gray-100: #F3F4F6;
        --gray-200: #E5E7EB;
        --gray-300: #D1D5DB;
        --gray-400: #9CA3AF;
        --gray-500: #6B7280;
        --gray-600: #4B5563;
        --gray-700: #374151;
        --gray-800: #1F2937;
        --gray-900: #111827;
        --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        --radius: 12px;
        --radius-sm: 8px;
        --radius-lg: 16px;
        --radius-pill: 50px;
    }

    * {
        box-sizing: border-box;
    }

    body {
        background: var(--gray-50);
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }

    .page-wrapper {
        padding-top: 0px !important;
        background: var(--gray-50);
    }

    .card-modern {
        background: #ffffff;
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--gray-200);
        overflow: hidden;
        transition: box-shadow 0.2s ease;
    }

    .card-modern:hover {
        box-shadow: var(--shadow-md);
    }

    .card-modern .card-header {
        padding: 20px 24px;
        background: var(--gray-50);
        border-bottom: 1px solid var(--gray-200);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }

    .card-modern .card-header h5 {
        font-size: 16px;
        font-weight: 600;
        color: var(--gray-800);
        margin: 0;
    }

    .card-modern .card-header h5 i {
        color: var(--primary-color);
        margin-right: 8px;
    }

    .card-modern .card-header .template-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #eef2ff;
        color: #1e40af;
        padding: 6px 16px;
        border-radius: 50px;
        font-size: 13px;
        font-weight: 600;
        border: 1px solid #c7d2fe;
    }

    .card-modern .card-header .template-badge i {
        font-size: 14px;
        color: #1e40af;
    }

    .card-modern .card-body {
        padding: 28px 32px;
    }

    @media (max-width: 768px) {
        .card-modern .card-body {
            padding: 20px;
        }

        .container-fluid {
            padding: 16px !important;
        }

        .card-modern .card-header {
            flex-direction: column;
            align-items: flex-start;
        }
    }

    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px 24px;
    }

    @media (max-width: 768px) {
        .form-grid {
            grid-template-columns: 1fr;
        }
    }

    .form-group-modern {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .form-group-modern .form-label {
        font-size: 13px;
        font-weight: 500;
        color: var(--gray-700);
        letter-spacing: 0.01em;
    }

    .form-group-modern .form-label .required {
        color: var(--danger-color);
        margin-left: 2px;
    }

    .form-group-modern .form-label .helper {
        font-weight: 400;
        color: var(--gray-400);
        font-size: 12px;
    }

    .form-control-modern {
        width: 100%;
        padding: 10px 14px;
        font-size: 14px;
        color: var(--gray-800);
        background: #fff;
        border: 1.5px solid var(--gray-200);
        border-radius: var(--radius-sm);
        transition: all 0.2s ease;
        outline: none;
        font-family: inherit;
    }

    .form-control-modern:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1);
    }

    .form-control-modern:hover {
        border-color: var(--gray-300);
    }

    .form-control-modern::placeholder {
        color: var(--gray-400);
        font-size: 13px;
    }

    .form-control-modern.is-invalid {
        border-color: var(--danger-color);
    }

    .form-control-modern.is-invalid:focus {
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
    }

    .form-control-modern:disabled,
    .form-control-modern[readonly] {
        background: var(--gray-100);
        cursor: not-allowed;
    }

    select.form-control-modern {
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236B7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 12px center;
        padding-right: 36px;
    }

    .radio-group-modern {
        display: flex;
        gap: 24px;
        flex-wrap: wrap;
        padding-top: 4px;
    }

    .radio-group-modern .radio-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        color: var(--gray-700);
        cursor: pointer;
    }

    .radio-group-modern .radio-item input[type="radio"] {
        width: 18px;
        height: 18px;
        cursor: pointer;
        flex-shrink: 0;
    }

    .btn-modern {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 28px;
        font-size: 14px;
        font-weight: 500;
        border: none;
        border-radius: var(--radius-pill);
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        font-family: inherit;
        line-height: 1.5;
    }

    .btn-modern-primary {
        background: var(--primary-color);
        color: #fff;
    }

    .btn-modern-primary:hover {
        background: var(--primary-dark);
        box-shadow: var(--shadow-md);
        transform: translateY(-1px);
        color: #fff;
    }

    .btn-modern-primary:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }

    .btn-modern-secondary {
        background: var(--gray-100);
        color: var(--gray-700);
    }

    .btn-modern-secondary:hover {
        background: var(--gray-200);
        color: var(--gray-800);
    }

    .form-actions {
        display: flex;
        gap: 12px;
        margin-top: 24px;
        padding-top: 20px;
        border-top: 1px solid var(--gray-200);
        flex-wrap: wrap;
    }

    @media (max-width: 640px) {
        .form-actions {
            flex-direction: column;
        }

        .form-actions .btn-modern {
            width: 100%;
            justify-content: center;
        }
    }

    .full-width {
        grid-column: 1 / -1;
    }

    .status-indicator {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 14px;
        border-radius: 50px;
        font-size: 13px;
        font-weight: 500;
    }

    .status-indicator.active {
        background: #D1FAE5;
        color: #065F46;
    }

    .status-indicator.inactive {
        background: #FEE2E2;
        color: #991B1B;
    }

    .status-indicator i {
        font-size: 8px;
    }

    .form-helper {
        font-size: 12px;
        color: var(--gray-400);
        margin-top: 4px;
        transition: all 0.3s ease;
    }

    .form-helper.error {
        color: var(--danger-color) !important;
        font-weight: 500;
    }

    .form-helper.success {
        color: var(--success-color) !important;
        font-weight: 500;
    }

    .alert-duplicate {
        background-color: #FEF2F2;
        border: 1px solid #FECACA;
        color: #991B1B;
        padding: 12px 16px;
        border-radius: var(--radius-sm);
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 14px;
    }

    .alert-duplicate i {
        font-size: 18px;
        color: #DC2626;
    }

    .alert-success-custom {
        background-color: #D1FAE5;
        border: 1px solid #A7F3D0;
        color: #065F46;
        padding: 12px 16px;
        border-radius: var(--radius-sm);
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 14px;
    }

    .alert-success-custom i {
        font-size: 18px;
        color: #059669;
    }

    .variable-badge {
        background-color: #eef2ff;
        border-radius: 20px;
        padding: 0.2rem 0.6rem;
        font-size: 0.75rem;
        font-weight: 500;
        display: inline-block;
        margin: 0 2px;
        color: #1e40af;
        cursor: default;
        border: 1px solid #c7d2fe;
        user-select: none;
    }

    .variable-badge:hover {
        background-color: #e0e7ff;
    }

    .btn-variable-token {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 40px;
        font-size: 0.7rem;
        padding: 0.25rem 0.9rem;
        transition: all 0.2s ease;
        cursor: pointer;
        font-weight: 500;
        color: var(--gray-700);
    }

    .btn-variable-token:hover {
        background: #f1f5f9;
        border-color: var(--primary-color);
        transform: translateY(-1px);
        box-shadow: var(--shadow-sm);
        color: var(--gray-800);
    }

    .btn-variable-token:active {
        transform: translateY(0px);
    }

    .variables-section {
        grid-column: 1 / -1;
        padding: 8px 0 4px 0;
        margin-top: 8px;
    }

    .variables-section .text-muted {
        font-size: 12px;
        font-weight: 600;
        color: var(--gray-500) !important;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 8px;
    }

    .variables-section .d-flex {
        gap: 6px;
        flex-wrap: wrap;
    }

    .content-editable {
        width: 100%;
        min-height: 300px;
        padding: 16px;
        font-size: 14px;
        color: var(--gray-800);
        background: #fff;
        border: 1.5px solid var(--gray-200);
        border-radius: var(--radius-sm);
        transition: all 0.2s ease;
        outline: none;
        font-family: inherit;
        line-height: 1.8;
        overflow-y: auto;
    }

    .content-editable:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1);
    }

    .content-editable:hover {
        border-color: var(--gray-300);
    }

    .content-editable.is-invalid {
        border-color: var(--danger-color);
    }

    .content-editable.is-invalid:focus {
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
    }

    .content-editable .variable-badge {
        background-color: #eef2ff;
        border-radius: 20px;
        padding: 0.15rem 0.6rem;
        font-size: 0.8rem;
        font-weight: 500;
        display: inline-block;
        margin: 0 2px;
        color: #1e40af;
        border: 1px solid #c7d2fe;
        cursor: default;
        user-select: none;
    }

    .content-editable .variable-badge:hover {
        background-color: #e0e7ff;
    }

    .breadcrumb-info {
        background: #EFF6FF;
        border: 1px solid #BFDBFE;
        border-radius: var(--radius-sm);
        padding: 8px 16px;
        font-size: 13px;
        color: #1E40AF;
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 16px;
    }

    .breadcrumb-info i {
        font-size: 16px;
    }

    .template-info-box {
        background: #F0FDF4;
        border: 1px solid #BBF7D0;
        border-radius: var(--radius-sm);
        padding: 10px 16px;
        font-size: 13px;
        color: #166534;
        display: flex;
        align-items: center;
        gap: 10px;
        grid-column: 1 / -1;
    }

    .template-info-box i {
        font-size: 16px;
        color: #22C55E;
    }
</style>

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">
        <?php require_once('../includes/header.php'); ?>

        <div class="page-wrapper" style="padding-top: 0px !important;">
            <div class="container-fluid py-4 px-4 m-auto mx-auto dashboard-container">

                <!-- Main Content -->
                <div class="row g-4">
                    <!-- Sidebar -->
                    <div class="col-12 col-md-4 col-xl-2">
                        <?php include 'layout/setup_sidebar.php'; ?>
                    </div>

                    <!-- Main Form -->
                    <div class="col-12 col-md-8 col-xl-10">
                        <div class="card-modern">
                            <div class="card-header">
                                <h5>
                                    <i class="bi bi-chat-dots"></i>
                                    <?php
                                    // Get the display name properly formatted
                                    if (!empty($_GET['id'])) {
                                        $display_name = $template_display_name;
                                    } elseif (!empty($preset_type)) {
                                        $display_name = ucwords(strtolower(str_replace('_', ' ', $preset_type)));
                                    } else {
                                        $display_name = '';
                                    }
                                    ?>
                                    <?= !empty($_GET['id']) ? 'Edit ' . htmlspecialchars($display_name) . ' Template' : 'Create New ' . htmlspecialchars($display_name) . ' Template' ?>
                                </h5>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <?php if (!empty($_GET['id'])): ?>
                                        <span class="status-indicator <?= ($ACTIVE == 1) ? 'active' : 'inactive' ?>">
                                            <i class="fas fa-circle"></i>
                                            <?= ($ACTIVE == 1) ? 'Active' : 'Inactive' ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-body">
                                <!-- Show breadcrumb info when coming from "Set up" button -->
                                <?php if (!empty($preset_type) && !empty($preset_location) && empty($_GET['id'])): ?>
                                    <div class="breadcrumb-info">
                                        <i class="bi bi-info-circle-fill"></i>
                                        Creating a new <strong><?= htmlspecialchars(ucwords(strtolower(str_replace('_', ' ', $preset_type)))) ?></strong> template for <strong><?php
                                                                                                                                                                                $loc_name = '';
                                                                                                                                                                                foreach ($locations as $loc) {
                                                                                                                                                                                    if ($loc['PK_LOCATION'] == $preset_location) {
                                                                                                                                                                                        $loc_name = $loc['LOCATION_NAME'];
                                                                                                                                                                                        break;
                                                                                                                                                                                    }
                                                                                                                                                                                }
                                                                                                                                                                                echo htmlspecialchars($loc_name);
                                                                                                                                                                                ?></strong>
                                    </div>
                                <?php endif; ?>

                                <!-- Display error message if any -->
                                <?php if (!empty($error_message)): ?>
                                    <div class="alert-duplicate">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        <?= htmlspecialchars($error_message) ?>
                                    </div>
                                <?php endif; ?>

                                <!-- Display success message -->
                                <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
                                    <div class="alert-success-custom">
                                        <i class="fas fa-check-circle"></i>
                                        Template saved successfully!
                                    </div>
                                <?php endif; ?>

                                <form class="form-material form-horizontal" action="" method="post" enctype="multipart/form-data" id="templateForm">

                                    <div class="form-grid">
                                        <!-- Template Name - Always disabled -->
                                        <div class="form-group-modern">
                                            <label class="form-label">Template Type</label>

                                            <select class="form-control-modern" id="TEMPLATE_NAME" name="TEMPLATE_NAME" required disabled>
                                                <option value="">Select template type</option>
                                                <option value="APPOINTMENT_CREATION" <?php echo ($TEMPLATE_NAME == 'APPOINTMENT_CREATION') ? 'selected' : ''; ?>>
                                                    Appointment Creation
                                                </option>
                                                <option value="ENROLLMENT_CREATION" <?php echo ($TEMPLATE_NAME == 'ENROLLMENT_CREATION') ? 'selected' : ''; ?>>
                                                    Enrollment Creation
                                                </option>
                                            </select>
                                            <input type="hidden" name="TEMPLATE_NAME" value="<?= htmlspecialchars($TEMPLATE_NAME) ?>">

                                            <div class="form-helper" id="templateTypeHelper">
                                                Template type is fixed and cannot be changed.
                                            </div>
                                        </div>

                                        <!-- Location - Always disabled -->
                                        <div class="form-group-modern">
                                            <label class="form-label">
                                                Location
                                            </label>
                                            <select class="form-control-modern PK_LOCATION" name="PK_LOCATION" disabled>
                                                <?php foreach ($locations as $loc): ?>
                                                    <option value="<?php echo $loc['PK_LOCATION']; ?>" <?= ($PK_LOCATION == $loc['PK_LOCATION']) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($loc['LOCATION_NAME']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <input type="hidden" name="PK_LOCATION" value="<?= htmlspecialchars($PK_LOCATION) ?>">
                                            <div class="form-helper">Location for this Template to be Active</div>
                                        </div>

                                        <!-- Subject -->
                                        <div class="form-group-modern">
                                            <label class="form-label">Subject <span class="required">*</span></label>
                                            <input type="text" class="form-control-modern" id="SUBJECT" name="SUBJECT" placeholder="Enter email subject" value="<?php echo htmlspecialchars($SUBJECT) ?>" required>
                                            <div class="form-helper">The subject line that will appear in the email</div>
                                        </div>

                                        <!-- Email Account -->
                                        <!-- <div class="form-group-modern">
                                            <label class="form-label">Email Account <span class="required">*</span></label>
                                            <select id="PK_EMAIL_ACCOUNT" name="PK_EMAIL_ACCOUNT" class="form-control-modern">
                                                <option value="">Select Email Account</option>
                                                <?php
                                                $row = $db_account->Execute("SELECT PK_EMAIL_ACCOUNT, USER_NAME FROM DOA_EMAIL_ACCOUNT WHERE PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND ACTIVE = 1");
                                                while (!$row->EOF) {
                                                    $selected = '';
                                                    if ($PK_EMAIL_ACCOUNT != '' && $PK_EMAIL_ACCOUNT == $row->fields['PK_EMAIL_ACCOUNT']) {
                                                        $selected = 'selected';
                                                    }
                                                ?>
                                                    <option value="<?php echo $row->fields['PK_EMAIL_ACCOUNT']; ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($row->fields['USER_NAME']); ?></option>
                                                <?php $row->MoveNext();
                                                } ?>
                                            </select>
                                            <div class="form-helper">The email account used to send this template</div>
                                        </div> -->

                                        <!-- Active Status -->
                                        <?php if (!empty($_GET['id'])): ?>
                                            <div class="form-group-modern">
                                                <label class="form-label">Status</label>
                                                <div class="radio-group-modern">
                                                    <label class="radio-item">
                                                        <input type="radio" id="ACTIVE1" name="ACTIVE" value="1" <?php echo $ACTIVE == '1' ? 'checked' : '' ?>>
                                                        Active
                                                    </label>
                                                    <label class="radio-item">
                                                        <input type="radio" id="ACTIVE2" name="ACTIVE" value="0" <?php echo $ACTIVE == '0' ? 'checked' : '' ?>>
                                                        Inactive
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Info Box -->
                                        <div class="template-info-box">
                                            <i class="fas fa-info-circle"></i>
                                            <span>
                                                <strong>Note:</strong> Template type and location are fixed. You can only have <strong>one</strong> template for
                                                <strong>Appointment Creation</strong> and <strong>one</strong> for
                                                <strong>Enrollment Creation</strong> per location.
                                            </span>
                                        </div>

                                        <!-- Email Content - Full Width with contenteditable -->
                                        <div class="form-group-modern full-width">
                                            <label class="form-label">Email Content <span class="required">*</span></label>

                                            <!-- Content editable div -->
                                            <div class="content-editable" contenteditable="true" id="contentEditable"><?= htmlspecialchars_decode($CONTENT) ?></div>
                                            <input type="hidden" name="CONTENT" id="CONTENT" value="<?= htmlspecialchars($CONTENT) ?>">
                                            <div class="form-helper" id="contentHelper">Click variable buttons below to insert dynamic fields into your email content.</div>
                                        </div>

                                        <!-- Variables Section -->
                                        <div class="variables-section">
                                            <span class="text-muted extra-small d-block mb-1">Insert Variables</span>
                                            <div class="d-flex flex-wrap gap-1">
                                                <button type="button" class="btn btn-variable-token var-btn" data-var="Student Name">Student Name</button>
                                                <button type="button" class="btn btn-variable-token var-btn" data-var="Location">Location</button>
                                                <button type="button" class="btn btn-variable-token var-btn" data-var="Service Provider Name">Service Provider Name</button>
                                                <button type="button" class="btn btn-variable-token var-btn" data-var="Corporation Name">Corporation Name</button>
                                                <button type="button" class="btn btn-variable-token var-btn" data-var="Student ID">Student ID</button>
                                                <button type="button" class="btn btn-variable-token var-btn" data-var="Course Name">Course Name</button>
                                                <button type="button" class="btn btn-variable-token var-btn" data-var="Date">Date</button>
                                                <button type="button" class="btn btn-variable-token var-btn" data-var="Time">Time</button>
                                                <button type="button" class="btn btn-variable-token var-btn" data-var="Instructor Name">Instructor Name</button>
                                                <button type="button" class="btn btn-variable-token var-btn" data-var="Class Name">Class Name</button>
                                            </div>
                                        </div>

                                        <!-- Hidden fields -->
                                        <?php if (!empty($_GET['id'])): ?>
                                            <input type="hidden" name="PK_EMAIL_TEMPLATE" value="<?php echo $_GET['id'] ?>">
                                        <?php endif; ?>
                                    </div>

                                    <!-- Form Actions -->
                                    <div class="form-actions">
                                        <button type="submit" class="btn-modern btn-modern-primary" id="submitBtn">
                                            <i class="fas fa-save"></i>
                                            <?php if (empty($_GET['id'])): ?>
                                                Create Template
                                            <?php else: ?>
                                                Update Template
                                            <?php endif; ?>
                                        </button>
                                        <button type="button" class="btn-modern btn-modern-secondary" onclick="window.location.href='all_email_templates.php'">
                                            <i class="fas fa-times"></i> Cancel
                                        </button>
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

    <script>
        // --- INSERT VARIABLE INTO CONTENTEDITABLE DIV ---
        function insertVariable(varName) {
            const editable = document.getElementById('contentEditable');
            if (!editable) return;

            editable.focus();

            const selection = window.getSelection();
            let range;

            if (selection.rangeCount > 0) {
                range = selection.getRangeAt(0);
            } else {
                range = document.createRange();
                range.setStart(editable, editable.childNodes.length);
                range.collapse(true);
                selection.addRange(range);
            }

            const variableSpan = document.createElement('span');
            variableSpan.className = 'variable-badge';
            variableSpan.setAttribute('contenteditable', 'false');
            variableSpan.textContent = varName;

            range.deleteContents();
            range.insertNode(variableSpan);

            const spaceNode = document.createTextNode('\u00A0');
            range.setStartAfter(variableSpan);
            range.insertNode(spaceNode);

            range.setStartAfter(spaceNode);
            range.collapse(true);
            selection.removeAllRanges();
            selection.addRange(range);

            updateContentInput();
        }

        // --- UPDATE HIDDEN INPUT WITH CONTENT ---
        function updateContentInput() {
            const editable = document.getElementById('contentEditable');
            const hiddenInput = document.getElementById('CONTENT');
            if (editable && hiddenInput) {
                hiddenInput.value = editable.innerHTML;
            }
        }

        // --- VARIABLE BUTTON HANDLERS ---
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.var-btn');
            if (btn) {
                e.preventDefault();
                const varName = btn.getAttribute('data-var');
                insertVariable(varName);
            }
        });

        // Update hidden input when content changes
        document.getElementById('contentEditable').addEventListener('input', updateContentInput);

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const subject = document.getElementById('SUBJECT');
            const editable = document.getElementById('contentEditable');
            const content = editable ? editable.innerHTML.trim() : '';

            let isValid = true;

            // Update hidden input before validation
            updateContentInput();

            // Check if subject is filled
            if (!subject.value.trim()) {
                subject.classList.add('is-invalid');
                isValid = false;
            } else {
                subject.classList.remove('is-invalid');
            }

            // Check if content is filled
            const isEmpty = !content || content === '<p><br></p>' || content === '<br>' || content === '<div><br></div>';
            if (isEmpty) {
                editable.classList.add('is-invalid');
                isValid = false;
                const contentHelper = document.getElementById('contentHelper');
                contentHelper.textContent = '⚠️ Please enter email content';
                contentHelper.className = 'form-helper error';
            } else {
                editable.classList.remove('is-invalid');
                const contentHelper = document.getElementById('contentHelper');
                contentHelper.textContent = 'Click variable buttons below to insert dynamic fields into your email content.';
                contentHelper.className = 'form-helper';
            }

            if (!isValid) {
                e.preventDefault();
                const firstError = document.querySelector('.is-invalid');
                if (firstError) {
                    firstError.focus();
                }
            }
        });

        // Remove invalid class on input
        document.querySelectorAll('.form-control-modern').forEach(input => {
            input.addEventListener('input', function() {
                if (this.value.trim()) {
                    this.classList.remove('is-invalid');
                }
            });
        });

        // Initialize content from hidden input
        document.addEventListener('DOMContentLoaded', function() {
            const editable = document.getElementById('contentEditable');
            const hiddenInput = document.getElementById('CONTENT');
            if (editable && hiddenInput && !editable.innerHTML.trim()) {
                const content = hiddenInput.value;
                if (content) {
                    editable.innerHTML = content;
                }
            }
        });
    </script>

</body>

</html>