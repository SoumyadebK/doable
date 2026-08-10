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

// Function to check for existing templates - NOW CHECKS ALL TEMPLATES, NOT JUST ACTIVE
function checkExistingTemplate($templateName, $accountMaster, $excludeId = null)
{
    global $db_account;

    $sql = "SELECT COUNT(*) as count FROM DOA_EMAIL_TEMPLATE 
            WHERE TEMPLATE_NAME = '$templateName' 
            AND PK_ACCOUNT_MASTER = '$accountMaster'";
    // Removed "AND ACTIVE = 1" - now checks all templates

    if ($excludeId) {
        $sql .= " AND PK_EMAIL_TEMPLATE != '$excludeId'";
    }

    $result = $db_account->Execute($sql);
    return $result->fields['count'] > 0;
}

$error_message = '';
$success_message = '';

if (!empty($_POST)) {
    $templateName = $_POST['TEMPLATE_NAME'];
    $editId = !empty($_GET['id']) ? $_GET['id'] : null;

    // Check for duplicates - now checks all templates
    if (in_array($templateName, ['APPOINTMENT_CREATION', 'ENROLLMENT_CREATION'])) {
        if (checkExistingTemplate($templateName, $_SESSION['PK_ACCOUNT_MASTER'], $editId)) {
            $error_message = "A template for " . str_replace('_', ' ', $templateName) . " already exists. You can only have one template per type.";
        }
    }

    // If no error, proceed with save
    if (empty($error_message)) {
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
}

if (empty($_GET['id'])) {
    $TEMPLATE_NAME      = '';
    $PK_LOCATION        = '';
    $SUBJECT            = '';
    $PK_TEMPLATE_CATEGORY = '';
    $PK_EMAIL_TRIGGER     = '';
    $PK_EMAIL_ACCOUNT   = '';
    $CONTENT            = '';
    $ACTIVE             = '';
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

    /* Breadcrumb */
    .breadcrumb-wrapper {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        flex-wrap: wrap;
        gap: 12px;
    }

    .breadcrumb-wrapper h4 {
        font-size: 24px;
        font-weight: 700;
        color: var(--gray-900);
        margin: 0;
        letter-spacing: -0.025em;
    }

    .breadcrumb-wrapper h4 i {
        color: var(--primary-color);
        margin-right: 10px;
    }

    .breadcrumb-nav {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        color: var(--gray-500);
    }

    .breadcrumb-nav a {
        color: var(--primary-color);
        text-decoration: none;
        font-weight: 500;
        transition: color 0.2s;
    }

    .breadcrumb-nav a:hover {
        color: var(--primary-dark);
    }

    .breadcrumb-nav .separator {
        color: var(--gray-300);
    }

    .breadcrumb-nav .current {
        color: var(--gray-700);
        font-weight: 500;
    }

    /* Card */
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
    }

    /* Form */
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

    select.form-control-modern {
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236B7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 12px center;
        padding-right: 36px;
    }

    /* Radio Group */
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

    /* Buttons - Rounded Pill */
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

    .btn-modern-success {
        background: var(--success-color);
        color: #fff;
    }

    .btn-modern-success:hover {
        background: var(--primary-dark);
        box-shadow: var(--shadow-md);
        transform: translateY(-1px);
        color: #fff;
    }

    .btn-modern-danger {
        background: var(--danger-color);
        color: #fff;
    }

    .btn-modern-danger:hover {
        background: #DC2626;
        color: #fff;
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

        .breadcrumb-wrapper {
            flex-direction: column;
            align-items: flex-start;
        }
    }

    /* Quill Editor */
    .quill-wrapper {
        border: 1.5px solid var(--gray-200);
        border-radius: var(--radius-sm);
        overflow: hidden;
        transition: all 0.2s ease;
    }

    .quill-wrapper:focus-within {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1);
    }

    .quill-wrapper .ql-toolbar {
        border: none;
        border-bottom: 1px solid var(--gray-200);
        background: var(--gray-50);
        padding: 8px 12px;
    }

    .quill-wrapper .ql-container {
        border: none;
        font-size: 14px;
        font-family: inherit;
        min-height: 300px;
    }

    .quill-wrapper .ql-editor {
        min-height: 300px;
        padding: 16px;
        font-size: 14px;
        line-height: 1.6;
    }

    .quill-wrapper .ql-editor p {
        margin-bottom: 8px;
    }

    .quill-wrapper .ql-toolbar .ql-formats {
        margin-right: 8px;
    }

    .quill-wrapper .ql-toolbar button {
        border-radius: 4px;
        transition: background 0.2s;
    }

    .quill-wrapper .ql-toolbar button:hover {
        background: var(--gray-200);
    }

    .quill-wrapper .ql-toolbar .ql-active {
        background: var(--primary-color);
        color: #fff;
    }

    /* Full width for editor */
    .full-width {
        grid-column: 1 / -1;
    }

    /* Status indicator */
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

    /* Help text */
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

    .form-helper.warning {
        color: var(--warning-color) !important;
        font-weight: 500;
    }

    /* Alert message styles */
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

    /* Variable Badge Styles */
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
    }

    .btn-variable-token {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 40px;
        font-size: 0.7rem;
        padding: 0.25rem 0.9rem;
        transition: all 0.2s ease;
        cursor: pointer;
    }

    .btn-variable-token:hover {
        background: #f1f5f9;
        border-color: var(--primary-color);
        transform: translateY(-1px);
        box-shadow: var(--shadow-sm);
    }

    .variables-section {
        grid-column: 1 / -1;
        padding: 8px 0 4px 0;
    }

    .variables-section .text-muted {
        font-size: 12px;
        font-weight: 600;
        color: var(--gray-500) !important;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 8px;
    }

    /* Existing templates info */
    .existing-templates-info {
        grid-column: 1 / -1;
        padding: 8px 0;
    }

    .existing-templates-info .info-box {
        background: #F0FDF4;
        border: 1px solid #BBF7D0;
        border-radius: var(--radius-sm);
        padding: 10px 16px;
        font-size: 13px;
        color: #166534;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .existing-templates-info .info-box i {
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
                                    <i class="bi bi-envelope"></i>
                                    <?= !empty($_GET['id']) ? 'Edit Template' : 'Create New Template' ?>
                                </h5>
                                <?php if (!empty($_GET['id'])): ?>
                                    <span class="status-indicator <?= ($ACTIVE == 1) ? 'active' : 'inactive' ?>">
                                        <i class="fas fa-circle"></i>
                                        <?= ($ACTIVE == 1) ? 'Active' : 'Inactive' ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
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
                                        <!-- Template Name -->
                                        <div class="form-group-modern">
                                            <label class="form-label">Template Type <span class="required">*</span></label>

                                            <select class="form-control-modern" id="TEMPLATE_NAME" name="TEMPLATE_NAME" required>
                                                <option value="">Select template type</option>
                                                <option value="APPOINTMENT_CREATION" <?php echo ($TEMPLATE_NAME == 'APPOINTMENT_CREATION') ? 'selected' : ''; ?>>
                                                    Appointment Creation
                                                </option>
                                                <option value="ENROLLMENT_CREATION" <?php echo ($TEMPLATE_NAME == 'ENROLLMENT_CREATION') ? 'selected' : ''; ?>>
                                                    Enrollment Creation
                                                </option>
                                            </select>

                                            <div class="form-helper" id="templateTypeHelper">Select whether this template is for an appointment or enrollment</div>
                                        </div>

                                        <!-- Location -->
                                        <div class="form-group-modern">
                                            <label class="form-label">
                                                Location
                                            </label>
                                            <select class="form-control-modern PK_LOCATION" name="PK_LOCATION" onchange="selectServiceClass(this)">
                                                <?php
                                                $row = $db->Execute("SELECT * FROM DOA_LOCATION WHERE PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND ACTIVE = 1 AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
                                                while (!$row->EOF) { ?>
                                                    <option value="<?php echo $row->fields['PK_LOCATION']; ?>" <?= ($PK_LOCATION == $row->fields['PK_LOCATION']) ? 'selected' : '' ?>><?= htmlspecialchars($row->fields['LOCATION_NAME']) ?></option>
                                                <?php $row->MoveNext();
                                                } ?>
                                            </select>
                                            <div class="form-helper">Location for this Template to be Active</div>
                                        </div>

                                        <!-- Subject -->
                                        <div class="form-group-modern">
                                            <label class="form-label">Subject <span class="required">*</span></label>
                                            <input type="text" class="form-control-modern" id="SUBJECT" name="SUBJECT" placeholder="Enter email subject" value="<?php echo htmlspecialchars($SUBJECT) ?>" required>
                                            <div class="form-helper">The subject line that will appear in the email</div>
                                        </div>

                                        <!-- Template Category -->
                                        <div class="form-group-modern">
                                            <label class="form-label">Template Category <span class="required">*</span></label>
                                            <select id="PK_TEMPLATE_CATEGORY" name="PK_TEMPLATE_CATEGORY" class="form-control-modern" onchange="selectTemplateCategory(this)" required>
                                                <option value="">Select Category</option>
                                                <?php
                                                $row = $db->Execute("SELECT PK_TEMPLATE_CATEGORY, TEMPLATE_CATEGORY FROM DOA_TEMPLATE_CATEGORY WHERE ACTIVE = 1");
                                                while (!$row->EOF) {
                                                    $selected = '';
                                                    if ($PK_TEMPLATE_CATEGORY != '' && $PK_TEMPLATE_CATEGORY == $row->fields['PK_TEMPLATE_CATEGORY']) {
                                                        $selected = 'selected';
                                                    }
                                                ?>
                                                    <option value="<?php echo $row->fields['PK_TEMPLATE_CATEGORY']; ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($row->fields['TEMPLATE_CATEGORY']); ?></option>
                                                <?php $row->MoveNext();
                                                } ?>
                                            </select>
                                        </div>

                                        <!-- Email Trigger -->
                                        <div class="form-group-modern" id="email_event_div" style="display: <?= ($PK_TEMPLATE_CATEGORY == 1) ? 'flex' : 'none' ?>;">
                                            <label class="form-label">Email Trigger</label>
                                            <select id="PK_EMAIL_TRIGGER" name="PK_EMAIL_TRIGGER" class="form-control-modern">
                                                <option value="">Select Trigger Event</option>
                                                <?php
                                                $row = $db->Execute("SELECT PK_EMAIL_TRIGGER, EMAIL_TRIGGER FROM DOA_EMAIL_TRIGGER WHERE ACTIVE = 1");
                                                while (!$row->EOF) {
                                                    $selected = '';
                                                    if ($PK_EMAIL_TRIGGER != '' && $PK_EMAIL_TRIGGER == $row->fields['PK_EMAIL_TRIGGER']) {
                                                        $selected = 'selected';
                                                    }
                                                ?>
                                                    <option value="<?php echo $row->fields['PK_EMAIL_TRIGGER']; ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($row->fields['EMAIL_TRIGGER']); ?></option>
                                                <?php $row->MoveNext();
                                                } ?>
                                            </select>
                                            <div class="form-helper">Select the event that will trigger this email</div>
                                        </div>

                                        <!-- Email Account -->
                                        <div class="form-group-modern">
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
                                        </div>

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

                                        <!-- Existing Templates Info -->
                                        <div class="existing-templates-info">
                                            <div class="info-box">
                                                <i class="fas fa-info-circle"></i>
                                                <span>
                                                    <strong>Note:</strong> You can only have <strong>one</strong> template for
                                                    <strong>Appointment Creation</strong> and <strong>one</strong> for
                                                    <strong>Enrollment Creation</strong> at a time (including inactive templates).
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Email Content - Full Width -->
                                        <div class="form-group-modern full-width">
                                            <label class="form-label">Email Content <span class="required">*</span></label>
                                            <div class="quill-wrapper">
                                                <div id="editor" style="min-height: 300px;"></div>
                                            </div>
                                            <input type="hidden" name="CONTENT" id="CONTENT">
                                            <textarea name="TEMP_CONTENT" id="TEMP_CONTENT" style="display:none;"><?= htmlspecialchars($CONTENT) ?></textarea>
                                            <div class="form-helper" id="contentHelper">Use the toolbar above to format your email content</div>
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

    <!-- Quill Editor -->
    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">

    <script type="text/javascript">
        // Initialize Quill Editor
        const quill = new Quill('#editor', {
            modules: {
                toolbar: [
                    ['bold', 'italic', 'underline', 'strike'],
                    ['link', 'image'],
                    [{
                        'header': 1
                    }, {
                        'header': 2
                    }],
                    [{
                        'list': 'ordered'
                    }, {
                        'list': 'bullet'
                    }],
                    [{
                        'script': 'sub'
                    }, {
                        'script': 'super'
                    }],
                    [{
                        'indent': '-1'
                    }, {
                        'indent': '+1'
                    }],
                    [{
                        'header': [1, 2, 3, 4, 5, 6, false]
                    }],
                    [{
                        'color': []
                    }, {
                        'background': []
                    }],
                    [{
                        'align': []
                    }],
                    ['clean']
                ],
            },
            theme: 'snow',
            placeholder: 'Write your email content here...',
        });

        // Load existing content
        const resetForm = () => {
            const content = document.getElementById('TEMP_CONTENT').value;
            if (content) {
                quill.root.innerHTML = content;
                document.getElementById('CONTENT').value = content;
            }
        };

        resetForm();

        // Update hidden input on content change
        quill.on('text-change', function() {
            document.getElementById('CONTENT').value = quill.root.innerHTML;
        });

        // --- VARIABLE INSERTION FUNCTION ---
        function insertVariable(varName) {
            quill.focus();
            const range = quill.getSelection();
            const cursorPosition = range ? range.index : quill.getLength();
            const html = `<span class="variable-badge" contenteditable="false">${varName}</span>&nbsp;`;
            quill.clipboard.dangerouslyPasteHTML(cursorPosition, html);
            setTimeout(() => {
                document.getElementById('CONTENT').value = quill.root.innerHTML;
            }, 100);
        }

        // Attach click handlers to variable buttons
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.var-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const varName = this.getAttribute('data-var');
                    insertVariable(varName);
                });
            });
        });

        // Template Category toggle
        function selectTemplateCategory(param) {
            const emailEventDiv = document.getElementById('email_event_div');
            if ($(param).val() == 1) {
                $(emailEventDiv).slideDown();
            } else {
                $(emailEventDiv).slideUp();
            }
        }

        // --- CLIENT-SIDE DUPLICATE CHECK ---
        function checkTemplateExists(templateName, callback) {
            const isEdit = <?= !empty($_GET['id']) ? 'true' : 'false' ?>;
            const currentId = <?= !empty($_GET['id']) ? $_GET['id'] : 'null' ?>;

            const formData = new FormData();
            formData.append('action', 'check_template');
            formData.append('template_name', templateName);
            formData.append('current_id', currentId);
            formData.append('is_edit', isEdit);

            fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    try {
                        const jsonData = JSON.parse(data);
                        callback(jsonData.exists);
                    } catch (e) {
                        callback(false);
                    }
                })
                .catch(() => callback(false));
        }

        // Real-time check when template type changes
        document.getElementById('TEMPLATE_NAME').addEventListener('change', function() {
            const selectedValue = this.value;
            const helper = document.getElementById('templateTypeHelper');
            const submitBtn = document.getElementById('submitBtn');

            if (selectedValue === 'APPOINTMENT_CREATION' || selectedValue === 'ENROLLMENT_CREATION') {
                // Show loading state
                helper.textContent = '⏳ Checking if template exists...';
                helper.className = 'form-helper warning';

                checkTemplateExists(selectedValue, function(exists) {
                    if (exists) {
                        const typeName = selectedValue.replace('_', ' ');
                        helper.textContent = '❌ A template for ' + typeName + ' already exists. You can only have one per type.';
                        helper.className = 'form-helper error';
                        document.getElementById('TEMPLATE_NAME').classList.add('is-invalid');
                        submitBtn.disabled = true;
                        submitBtn.style.opacity = '0.6';
                        submitBtn.style.cursor = 'not-allowed';
                    } else {
                        helper.textContent = '✅ Available - You can create this template type';
                        helper.className = 'form-helper success';
                        document.getElementById('TEMPLATE_NAME').classList.remove('is-invalid');
                        submitBtn.disabled = false;
                        submitBtn.style.opacity = '1';
                        submitBtn.style.cursor = 'pointer';
                    }
                });
            } else {
                helper.textContent = 'Select whether this template is for an appointment or enrollment';
                helper.className = 'form-helper';
                document.getElementById('TEMPLATE_NAME').classList.remove('is-invalid');
                submitBtn.disabled = false;
                submitBtn.style.opacity = '1';
                submitBtn.style.cursor = 'pointer';
            }
        });

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const templateName = document.getElementById('TEMPLATE_NAME');
            const subject = document.getElementById('SUBJECT');
            const content = quill.root.innerHTML.trim();
            const helper = document.getElementById('templateTypeHelper');

            let isValid = true;

            // Check if template type is selected
            if (!templateName.value.trim()) {
                templateName.classList.add('is-invalid');
                helper.textContent = 'Please select a template type';
                helper.className = 'form-helper error';
                isValid = false;
            } else {
                templateName.classList.remove('is-invalid');
            }

            // Check if subject is filled
            if (!subject.value.trim()) {
                subject.classList.add('is-invalid');
                isValid = false;
            } else {
                subject.classList.remove('is-invalid');
            }

            // Check if content is filled
            if (!content || content === '<p><br></p>' || content === '<p><br class="ql-cursor"></p>') {
                document.querySelector('.quill-wrapper').style.borderColor = 'var(--danger-color)';
                const contentHelper = document.getElementById('contentHelper');
                contentHelper.textContent = '⚠️ Please enter email content';
                contentHelper.className = 'form-helper error';
                isValid = false;
            } else {
                document.querySelector('.quill-wrapper').style.borderColor = 'var(--gray-200)';
                const contentHelper = document.getElementById('contentHelper');
                contentHelper.textContent = 'Use the toolbar above to format your email content';
                contentHelper.className = 'form-helper';
            }

            // Check if submit button is disabled (duplicate template)
            const submitBtn = document.getElementById('submitBtn');
            if (submitBtn.disabled) {
                isValid = false;
                helper.textContent = '⚠️ Please select a different template type';
                helper.className = 'form-helper error';
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

        // Trigger change event on page load to check existing template
        document.addEventListener('DOMContentLoaded', function() {
            const templateSelect = document.getElementById('TEMPLATE_NAME');
            if (templateSelect.value) {
                // Trigger the change event to check if template exists
                const event = new Event('change');
                templateSelect.dispatchEvent(event);
            }
        });

        <?php
        // Handle AJAX request for template check within the same page
        if (isset($_POST['action']) && $_POST['action'] == 'check_template') {
            $templateName = isset($_POST['template_name']) ? $_POST['template_name'] : '';
            $currentId = isset($_POST['current_id']) ? $_POST['current_id'] : null;
            $isEdit = isset($_POST['is_edit']) ? $_POST['is_edit'] : false;

            $exists = false;
            if (in_array($templateName, ['APPOINTMENT_CREATION', 'ENROLLMENT_CREATION'])) {
                $sql = "SELECT COUNT(*) as count FROM DOA_EMAIL_TEMPLATE 
                        WHERE TEMPLATE_NAME = '$templateName' 
                        AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'";
                // Removed "AND ACTIVE = 1" - now checks all templates

                if ($isEdit && $currentId) {
                    $sql .= " AND PK_EMAIL_TEMPLATE != '$currentId'";
                }

                $result = $db_account->Execute($sql);
                $exists = $result->fields['count'] > 0;
            }

            header('Content-Type: application/json');
            echo json_encode(['exists' => $exists]);
            exit;
        }
        ?>
    </script>

</body>

</html>