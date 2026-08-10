<?php
require_once('../global/config.php');

if (empty($_GET['id']))
    $title = "Add Campaign";
else
    $title = "Edit Campaign";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

// Get selected tag values for edit mode
$selected_tag = array();
if (!empty($_GET['id'])) {
    $tag_res = $db_account->Execute("SELECT TAGS FROM DOA_MARKET_CAMPAIGN WHERE PK_CAMPAIGN_ID = '$_GET[id]'");
    if ($tag_res->RecordCount() > 0) {
        $tags_str = $tag_res->fields['TAGS'];
        if (!empty($tags_str)) {
            $selected_tag = explode(',', $tags_str);
        }
    }
}

// Get all tags for display
$all_tags = array();
$tag_res = $db_account->Execute("SELECT PK_TAG, TAG_NAME FROM DOA_TAG WHERE ACTIVE = 1 ORDER BY TAG_NAME");
while (!$tag_res->EOF) {
    $all_tags[] = array(
        'id' => $tag_res->fields['PK_TAG'],
        'name' => $tag_res->fields['TAG_NAME']
    );
    $tag_res->MoveNext();
}

// Get all lead statuses for display
$all_lead_statuses = array();
$status_res = $db->Execute("SELECT PK_LEAD_STATUS, LEAD_STATUS FROM DOA_LEAD_STATUS WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]' AND ACTIVE = 1 ORDER BY LEAD_STATUS");
while (!$status_res->EOF) {
    $all_lead_statuses[] = array(
        'id' => $status_res->fields['PK_LEAD_STATUS'],
        'name' => $status_res->fields['LEAD_STATUS']
    );
    $status_res->MoveNext();
}

if (!empty($_POST)) {
    $CAMPAIGN_DATA = array();
    $CAMPAIGN_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
    $CAMPAIGN_DATA['PK_LOCATION'] = $_POST['PK_LOCATION'];
    $CAMPAIGN_DATA['CAMPAIGN_NAME'] = $_POST['TEMPLATE_NAME'];
    $CAMPAIGN_DATA['SUBJECT'] = $_POST['SUBJECT'];
    $CAMPAIGN_DATA['OPERATION'] = $_POST['OPERATION'];
    $CAMPAIGN_DATA['REMINDER_TYPE'] = implode(',', $_POST['REMINDER_TYPE']); // Store as comma-separated
    // Always save content in CONTENT field
    $CAMPAIGN_DATA['CONTENT'] = $_POST['CONTENT'];

    // Handle TAGS - store as comma-separated values
    if (!empty($_POST['PK_USER_TAG']) && is_array($_POST['PK_USER_TAG'])) {
        $CAMPAIGN_DATA['TAGS'] = implode(',', $_POST['PK_USER_TAG']);
    } else {
        $CAMPAIGN_DATA['TAGS'] = '';
    }

    // Handle LEADS - store as comma-separated values
    if (!empty($_POST['PK_LEAD_STATUS']) && is_array($_POST['PK_LEAD_STATUS'])) {
        $CAMPAIGN_DATA['LEADS'] = implode(',', $_POST['PK_LEAD_STATUS']);
    } else {
        $CAMPAIGN_DATA['LEADS'] = '';
    }

    // Handle ACTIVE status
    if (isset($_POST['ACTIVE'])) {
        $CAMPAIGN_DATA['ACTIVE'] = $_POST['ACTIVE'];
    } else {
        $CAMPAIGN_DATA['ACTIVE'] = 1;
    }

    if ($_GET['id'] == '') {
        // Insert new campaign
        $CAMPAIGN_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
        $CAMPAIGN_DATA['CREATED_ON'] = date("Y-m-d H:i:s");
        $CAMPAIGN_DATA['EDITED_BY'] = 0;
        $CAMPAIGN_DATA['EDITED_ON'] = '0000-00-00 00:00:00';

        db_perform_account('DOA_MARKET_CAMPAIGN', $CAMPAIGN_DATA, 'insert');
        header("location:all_marketings.php");
        exit;
    } else {
        // Update existing campaign
        $CAMPAIGN_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
        $CAMPAIGN_DATA['EDITED_ON'] = date("Y-m-d H:i:s");

        db_perform_account('DOA_MARKET_CAMPAIGN', $CAMPAIGN_DATA, 'update', " PK_MARKET_CAMPAIGN = '$_GET[id]'");
        header("location:all_marketings.php");
        exit;
    }
}

if (empty($_GET['id'])) {
    $TEMPLATE_NAME = '';
    $PK_LOCATION = '';
    $SUBJECT = '';
    $OPERATION = '';
    $CONTENT = '';
    $ACTIVE = '';
    $REMINDER_TYPE = array('email'); // Default to email
    $selected_lead_statuses = array();
} else {
    $res = $db_account->Execute("SELECT * FROM DOA_MARKET_CAMPAIGN WHERE PK_MARKET_CAMPAIGN = '$_GET[id]'");
    if ($res->RecordCount() == 0) {
        header("location:all_marketings.php");
        exit;
    }
    $TEMPLATE_NAME = $res->fields['CAMPAIGN_NAME'];
    $PK_LOCATION = $res->fields['PK_LOCATION'];
    $SUBJECT = $res->fields['SUBJECT'];
    $OPERATION = $res->fields['OPERATION'];
    $REMINDER_TYPE = !empty($res->fields['REMINDER_TYPE']) ? explode(',', $res->fields['REMINDER_TYPE']) : array('email');
    $CONTENT = $res->fields['CONTENT']; // Always get content from CONTENT field
    $ACTIVE = $res->fields['ACTIVE'];

    // Convert comma-separated tags to array
    $selected_tag = !empty($res->fields['TAGS']) ? explode(',', $res->fields['TAGS']) : array();

    // Convert comma-separated lead statuses to array
    $selected_lead_statuses = !empty($res->fields['LEADS']) ? explode(',', $res->fields['LEADS']) : array();
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

    .checkbox-group-modern {
        display: flex;
        gap: 24px;
        flex-wrap: wrap;
        padding-top: 4px;
    }

    .checkbox-group-modern .checkbox-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        color: var(--gray-700);
        cursor: pointer;
    }

    .checkbox-group-modern .checkbox-item input[type="checkbox"] {
        width: 18px;
        height: 18px;
        cursor: pointer;
        flex-shrink: 0;
        accent-color: var(--primary-color);
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

    .conditional-field {
        display: none !important;
    }

    .conditional-field.visible {
        display: block !important;
    }

    .checkbox-container {
        border: 1.5px solid var(--gray-200);
        border-radius: var(--radius-sm);
        padding: 12px 16px;
        max-height: 200px;
        overflow-y: auto;
        background: #fff;
        transition: border-color 0.2s ease;
    }

    .checkbox-container:hover {
        border-color: var(--gray-300);
    }

    .checkbox-container:focus-within {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1);
    }

    .checkbox-container .checkbox-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 6px 4px;
        border-radius: 4px;
        transition: background 0.15s ease;
        cursor: pointer;
    }

    .checkbox-container .checkbox-item:hover {
        background: var(--gray-50);
    }

    .checkbox-container .checkbox-item .form-check-input {
        width: 18px;
        height: 18px;
        cursor: pointer;
        flex-shrink: 0;
        margin: 0;
        accent-color: var(--primary-color);
    }

    .checkbox-container .checkbox-item label {
        font-size: 14px;
        color: var(--gray-700);
        cursor: pointer;
        margin: 0;
        user-select: none;
    }

    .checkbox-container .checkbox-item.select-all-item {
        border-bottom: 1px solid var(--gray-200);
        padding-bottom: 10px;
        margin-bottom: 6px;
    }

    .checkbox-container .checkbox-item.select-all-item label {
        font-weight: 600;
        color: var(--gray-800);
    }

    .selected-count {
        font-size: 13px;
        color: var(--gray-500);
        margin-top: 6px;
        font-weight: 500;
    }

    .selected-count span {
        color: var(--primary-color);
        font-weight: 600;
    }

    .checkbox-container::-webkit-scrollbar {
        width: 6px;
    }

    .checkbox-container::-webkit-scrollbar-track {
        background: var(--gray-100);
        border-radius: 3px;
    }

    .checkbox-container::-webkit-scrollbar-thumb {
        background: var(--gray-300);
        border-radius: 3px;
    }

    .checkbox-container::-webkit-scrollbar-thumb:hover {
        background: var(--gray-400);
    }

    .subject-optional {
        font-size: 12px;
        color: var(--gray-400);
        font-weight: 400;
    }

    .subject-optional.required-text {
        color: var(--danger-color);
    }
</style>

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">
        <?php require_once('../includes/header.php'); ?>

        <div class="page-wrapper" style="padding-top: 0px !important;">
            <div class="container-fluid py-4 px-4 m-auto mx-auto dashboard-container">

                <div class="row g-4">
                    <div class="col-12 col-md-4 col-xl-2">
                        <?php include 'layout/setup_sidebar.php'; ?>
                    </div>

                    <div class="col-12 col-md-8 col-xl-10">
                        <div class="card-modern">
                            <div class="card-header">
                                <h5>
                                    <i class="bi bi-megaphone"></i>
                                    <?= !empty($_GET['id']) ? 'Edit Campaign' : 'Create New Campaign' ?>
                                </h5>
                                <?php if (!empty($_GET['id'])): ?>
                                    <span class="status-indicator <?= ($ACTIVE == 1) ? 'active' : 'inactive' ?>">
                                        <i class="fas fa-circle"></i>
                                        <?= ($ACTIVE == 1) ? 'Active' : 'Inactive' ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <form class="form-material form-horizontal" action="" method="post" enctype="multipart/form-data">

                                    <div class="form-grid">
                                        <!-- Campaign Name -->
                                        <div class="form-group-modern">
                                            <label class="form-label">Campaign Name <span class="required">*</span></label>
                                            <input type="text" class="form-control-modern" id="TEMPLATE_NAME" name="TEMPLATE_NAME" placeholder="Enter campaign name" value="<?php echo htmlspecialchars($TEMPLATE_NAME) ?>" required>
                                            <div class="form-helper">A unique name to identify this campaign</div>
                                        </div>

                                        <!-- Location -->
                                        <div class="form-group-modern">
                                            <label class="form-label">Location <span class="required">*</span></label>
                                            <select class="form-control-modern PK_LOCATION" name="PK_LOCATION" required>
                                                <option value="">Select Location</option>
                                                <?php
                                                $row = $db->Execute("SELECT * FROM DOA_LOCATION WHERE PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND ACTIVE = 1 AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
                                                while (!$row->EOF) { ?>
                                                    <option value="<?php echo $row->fields['PK_LOCATION']; ?>" <?= ($PK_LOCATION == $row->fields['PK_LOCATION']) ? 'selected' : '' ?>><?= htmlspecialchars($row->fields['LOCATION_NAME']) ?></option>
                                                <?php $row->MoveNext();
                                                } ?>
                                            </select>
                                            <div class="form-helper">Location for this Campaign to be Active</div>
                                        </div>

                                        <!-- Reminder Type - Checkboxes for multiple selection -->
                                        <div class="form-group-modern full-width">
                                            <label class="form-label">Reminder Type <span class="required">*</span></label>
                                            <div class="checkbox-group-modern">
                                                <label class="checkbox-item">
                                                    <input type="checkbox" name="REMINDER_TYPE[]" value="email" <?= in_array('email', $REMINDER_TYPE) ? 'checked' : '' ?>>
                                                    Email
                                                </label>
                                                <label class="checkbox-item">
                                                    <input type="checkbox" name="REMINDER_TYPE[]" value="text" <?= in_array('text', $REMINDER_TYPE) ? 'checked' : '' ?>>
                                                    Text Message
                                                </label>
                                            </div>
                                            <div class="form-helper">Select one or both reminder types</div>
                                        </div>

                                        <!-- Subject - Made optional with conditional requirement -->
                                        <div class="form-group-modern">
                                            <label class="form-label">
                                                Subject
                                                <span class="subject-optional" id="subjectRequiredLabel">(Required for Email)</span>
                                            </label>
                                            <input type="text" class="form-control-modern" id="SUBJECT" name="SUBJECT" placeholder="Enter subject" value="<?php echo htmlspecialchars($SUBJECT) ?>">
                                            <div class="form-helper" id="subjectHelper">The subject line that will appear in the <?= in_array('email', $REMINDER_TYPE) ? 'email' : 'text message' ?></div>
                                        </div>

                                        <!-- Operation / Category -->
                                        <div class="form-group-modern">
                                            <label class="form-label">Target Audience <span class="required">*</span></label>
                                            <select id="OPERATION" name="OPERATION" class="form-control-modern" required>
                                                <option value="inactive_customers" <?= ($OPERATION == 'inactive_customers') ? 'selected' : '' ?>>All Inactive Customers</option>
                                                <option value="active_customers" <?= ($OPERATION == 'active_customers') ? 'selected' : '' ?>>All Active Customers</option>
                                                <option value="tags" <?= ($OPERATION == 'tags') ? 'selected' : '' ?>>By Tags</option>
                                                <option value="leads" <?= ($OPERATION == 'leads') ? 'selected' : '' ?>>Leads</option>
                                            </select>
                                            <div class="form-helper">Select the target audience for this campaign</div>
                                        </div>

                                        <!-- Tags - Conditional Field with Checkboxes -->
                                        <div class="form-group-modern conditional-field <?= ($OPERATION == 'tags') ? 'visible' : '' ?>" id="tags_field">
                                            <label class="form-label">Select Tags <span class="required">*</span></label>
                                            <div class="checkbox-container" id="tagsCheckboxContainer">
                                                <div class="checkbox-item select-all-item">
                                                    <input type="checkbox" id="selectAllTags" class="form-check-input">
                                                    <label for="selectAllTags" class="fw-semibold">Select All Tags</label>
                                                </div>
                                                <?php foreach ($all_tags as $tag): ?>
                                                    <div class="checkbox-item">
                                                        <input type="checkbox" class="form-check-input tag-checkbox" name="PK_USER_TAG[]" value="<?= $tag['id'] ?>" id="tag_<?= $tag['id'] ?>" <?= in_array($tag['id'], $selected_tag) ? 'checked' : '' ?>>
                                                        <label for="tag_<?= $tag['id'] ?>"><?= htmlspecialchars($tag['name']) ?></label>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <div class="selected-count" id="tagsSelectedCount">Selected: <span id="tagsCount"><?= count($selected_tag) ?></span></div>
                                            <small class="text-muted">Check the boxes to select multiple tags</small>
                                        </div>

                                        <!-- Lead Status - Conditional Field with Checkboxes -->
                                        <div class="form-group-modern conditional-field <?= ($OPERATION == 'leads') ? 'visible' : '' ?>" id="lead_status_field">
                                            <label class="form-label">Select Lead Statuses <span class="required">*</span></label>
                                            <div class="checkbox-container" id="leadStatusCheckboxContainer">
                                                <div class="checkbox-item select-all-item">
                                                    <input type="checkbox" id="selectAllLeadStatuses" class="form-check-input">
                                                    <label for="selectAllLeadStatuses" class="fw-semibold">Select All Lead Statuses</label>
                                                </div>
                                                <?php foreach ($all_lead_statuses as $status): ?>
                                                    <div class="checkbox-item">
                                                        <input type="checkbox" class="form-check-input lead-status-checkbox" name="PK_LEAD_STATUS[]" value="<?= $status['id'] ?>" id="lead_status_<?= $status['id'] ?>" <?= in_array($status['id'], $selected_lead_statuses) ? 'checked' : '' ?>>
                                                        <label for="lead_status_<?= $status['id'] ?>"><?= htmlspecialchars($status['name']) ?></label>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <div class="selected-count" id="leadStatusSelectedCount">Selected: <span id="leadStatusCount"><?= count($selected_lead_statuses) ?></span></div>
                                            <small class="text-muted">Check the boxes to select multiple lead statuses</small>
                                        </div>

                                        <!-- Content Section - Single Editor for Both -->
                                        <div class="form-group-modern full-width">
                                            <label class="form-label" id="contentLabel">Content <span class="required">*</span></label>
                                            <div class="quill-wrapper" id="quillWrapper">
                                                <div id="editor" style="min-height: 300px;"></div>
                                            </div>
                                            <input type="hidden" name="CONTENT" id="CONTENT">
                                            <textarea name="TEMP_CONTENT" id="TEMP_CONTENT" style="display:none;"><?= htmlspecialchars($CONTENT) ?></textarea>
                                            <div class="form-helper" id="contentHelper">Use the toolbar above to format your content</div>

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

                                        <!-- Hidden fields -->
                                        <?php if (!empty($_GET['id'])): ?>
                                            <input type="hidden" name="PK_CAMPAIGN_ID" value="<?php echo $_GET['id'] ?>">
                                        <?php endif; ?>
                                    </div>

                                    <!-- Form Actions -->
                                    <div class="form-actions">
                                        <button type="submit" class="btn-modern btn-modern-primary">
                                            <i class="fas fa-save"></i>
                                            <?php if (empty($_GET['id'])): ?>
                                                Create Campaign
                                            <?php else: ?>
                                                Update Campaign
                                            <?php endif; ?>
                                        </button>
                                        <button type="button" class="btn-modern btn-modern-secondary" onclick="window.location.href='all_marketings.php'">
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
            placeholder: 'Write your content here...',
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

        // --- UPDATE LABELS AND VALIDATION BASED ON REMINDER TYPE ---
        function updateContentLabels() {
            const emailChecked = document.querySelector('input[name="REMINDER_TYPE[]"][value="email"]').checked;
            const textChecked = document.querySelector('input[name="REMINDER_TYPE[]"][value="text"]').checked;
            const contentLabel = document.getElementById('contentLabel');
            const contentHelper = document.getElementById('contentHelper');
            const subjectHelper = document.getElementById('subjectHelper');
            const subjectRequiredLabel = document.getElementById('subjectRequiredLabel');
            const subjectInput = document.getElementById('SUBJECT');

            // Update content label
            let labelText = 'Content';
            if (emailChecked && textChecked) {
                labelText = 'Content (Email & Text Message)';
                contentHelper.textContent = 'Use the toolbar above to format your content for both email and text message';
                subjectHelper.textContent = 'Subject is required for email (optional for text message)';
                subjectRequiredLabel.textContent = '(Required for Email, Optional for Text)';
            } else if (emailChecked) {
                labelText = 'Email Content';
                contentHelper.textContent = 'Use the toolbar above to format your email content';
                subjectHelper.textContent = 'The subject line that will appear in the email';
                subjectRequiredLabel.textContent = '(Required for Email)';
            } else if (textChecked) {
                labelText = 'Text Message Content';
                contentHelper.textContent = 'Use the toolbar above to format your text message content';
                subjectHelper.textContent = 'Subject is optional for text message';
                subjectRequiredLabel.textContent = '(Optional for Text Message)';
            }

            contentLabel.innerHTML = labelText + ' <span class="required">*</span>';

            // Update subject required status
            if (emailChecked) {
                subjectInput.setAttribute('required', 'required');
                subjectInput.classList.remove('optional');
            } else {
                subjectInput.removeAttribute('required');
                subjectInput.classList.add('optional');
            }
        }

        // --- TOGGLE CONDITIONAL FIELDS (Tags/Leads) ---
        function toggleConditionalFields() {
            const selectedValue = document.getElementById('OPERATION').value;
            const tagsField = document.getElementById('tags_field');
            const leadStatusField = document.getElementById('lead_status_field');

            tagsField.classList.remove('visible');
            leadStatusField.classList.remove('visible');

            if (selectedValue === 'tags') {
                tagsField.classList.add('visible');
                updateTagsCount();
            } else if (selectedValue === 'leads') {
                leadStatusField.classList.add('visible');
                updateLeadStatusCount();
            }
        }

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

        // --- UPDATE SELECTED COUNT FOR TAGS ---
        function updateTagsCount() {
            const checked = document.querySelectorAll('.tag-checkbox:checked').length;
            document.getElementById('tagsCount').textContent = checked;
        }

        // --- UPDATE SELECTED COUNT FOR LEAD STATUSES ---
        function updateLeadStatusCount() {
            const checked = document.querySelectorAll('.lead-status-checkbox:checked').length;
            document.getElementById('leadStatusCount').textContent = checked;
        }

        // --- SELECT ALL TAGS ---
        document.getElementById('selectAllTags').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.tag-checkbox');
            checkboxes.forEach(cb => cb.checked = this.checked);
            updateTagsCount();
        });

        // --- SELECT ALL LEAD STATUSES ---
        document.getElementById('selectAllLeadStatuses').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.lead-status-checkbox');
            checkboxes.forEach(cb => cb.checked = this.checked);
            updateLeadStatusCount();
        });

        // --- EVENT LISTENERS ---
        // Attach change event to target audience dropdown
        document.getElementById('OPERATION').addEventListener('change', function() {
            toggleConditionalFields();
            document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        });

        // Attach change event to reminder type checkboxes
        document.querySelectorAll('input[name="REMINDER_TYPE[]"]').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                updateContentLabels();
                // At least one must be checked
                const checked = document.querySelectorAll('input[name="REMINDER_TYPE[]"]:checked');
                if (checked.length === 0) {
                    this.checked = true; // Prevent unchecking the last one
                    alert('Please select at least one reminder type');
                }
                document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            });
        });

        // Attach click handlers to variable buttons
        document.querySelectorAll('.var-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const varName = this.getAttribute('data-var');
                insertVariable(varName);
            });
        });

        // Individual checkbox events
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('tag-checkbox')) {
                updateTagsCount();
                const allTags = document.querySelectorAll('.tag-checkbox');
                const checkedTags = document.querySelectorAll('.tag-checkbox:checked');
                document.getElementById('selectAllTags').checked = allTags.length === checkedTags.length;
            }
            if (e.target.classList.contains('lead-status-checkbox')) {
                updateLeadStatusCount();
                const allStatuses = document.querySelectorAll('.lead-status-checkbox');
                const checkedStatuses = document.querySelectorAll('.lead-status-checkbox:checked');
                document.getElementById('selectAllLeadStatuses').checked = allStatuses.length === checkedStatuses.length;
            }
        });

        // --- INITIALIZATION ---
        document.addEventListener('DOMContentLoaded', function() {
            updateContentLabels();
            toggleConditionalFields();
            updateTagsCount();
            updateLeadStatusCount();
        });

        // --- FORM VALIDATION ---
        document.querySelector('form').addEventListener('submit', function(e) {
            const templateName = document.getElementById('TEMPLATE_NAME');
            const subject = document.getElementById('SUBJECT');
            const location = document.querySelector('.PK_LOCATION');
            const category = document.getElementById('OPERATION').value;
            const tagsChecked = document.querySelectorAll('.tag-checkbox:checked');
            const leadStatusesChecked = document.querySelectorAll('.lead-status-checkbox:checked');
            const content = quill.root.innerHTML.trim();
            const reminderTypes = document.querySelectorAll('input[name="REMINDER_TYPE[]"]:checked');
            const emailChecked = document.querySelector('input[name="REMINDER_TYPE[]"][value="email"]').checked;

            let isValid = true;

            // Validate at least one reminder type is selected
            if (reminderTypes.length === 0) {
                document.querySelector('.checkbox-group-modern').style.borderColor = 'var(--danger-color)';
                document.querySelector('.checkbox-group-modern').style.border = '1.5px solid var(--danger-color)';
                document.querySelector('.checkbox-group-modern').style.borderRadius = 'var(--radius-sm)';
                document.querySelector('.checkbox-group-modern').style.padding = '8px';
                isValid = false;
                const helper = document.querySelector('.checkbox-group-modern').closest('.form-group-modern').querySelector('.form-helper');
                if (helper) {
                    helper.style.color = 'var(--danger-color)';
                    helper.textContent = 'Please select at least one reminder type';
                    setTimeout(() => {
                        helper.style.color = 'var(--gray-400)';
                        helper.textContent = 'Select one or both reminder types';
                    }, 3000);
                }
            } else {
                document.querySelector('.checkbox-group-modern').style.border = 'none';
                document.querySelector('.checkbox-group-modern').style.padding = '0';
            }

            if (!templateName.value.trim()) {
                templateName.classList.add('is-invalid');
                isValid = false;
            } else {
                templateName.classList.remove('is-invalid');
            }

            // Subject is required only if email is selected
            if (emailChecked && !subject.value.trim()) {
                subject.classList.add('is-invalid');
                isValid = false;
            } else {
                subject.classList.remove('is-invalid');
            }

            if (!location.value) {
                location.classList.add('is-invalid');
                isValid = false;
            } else {
                location.classList.remove('is-invalid');
            }

            if (!content || content === '<p><br></p>' || content === '<p><br class="ql-cursor"></p>') {
                document.getElementById('quillWrapper').style.borderColor = 'var(--danger-color)';
                isValid = false;
                const helper = document.getElementById('contentHelper');
                if (helper) {
                    helper.style.color = 'var(--danger-color)';
                    helper.textContent = 'Please enter content';
                    setTimeout(() => {
                        helper.style.color = 'var(--gray-400)';
                        helper.textContent = 'Use the toolbar above to format your content';
                    }, 3000);
                }
            } else {
                document.getElementById('quillWrapper').style.borderColor = 'var(--gray-200)';
            }

            if (category === 'tags' && tagsChecked.length === 0) {
                document.getElementById('tagsCheckboxContainer').style.borderColor = 'var(--danger-color)';
                isValid = false;
                const helper = document.querySelector('#tags_field .form-helper');
                if (helper) {
                    helper.style.color = 'var(--danger-color)';
                    helper.textContent = 'Please select at least one tag';
                    setTimeout(() => {
                        helper.style.color = 'var(--gray-400)';
                        helper.textContent = 'Check the boxes to select multiple tags';
                    }, 3000);
                }
            }

            if (category === 'leads' && leadStatusesChecked.length === 0) {
                document.getElementById('leadStatusCheckboxContainer').style.borderColor = 'var(--danger-color)';
                isValid = false;
                const helper = document.querySelector('#lead_status_field .form-helper');
                if (helper) {
                    helper.style.color = 'var(--danger-color)';
                    helper.textContent = 'Please select at least one lead status';
                    setTimeout(() => {
                        helper.style.color = 'var(--gray-400)';
                        helper.textContent = 'Check the boxes to select multiple lead statuses';
                    }, 3000);
                }
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
                    this.style.borderColor = 'var(--gray-200)';
                }
            });
            input.addEventListener('change', function() {
                if (this.value) {
                    this.classList.remove('is-invalid');
                    this.style.borderColor = 'var(--gray-200)';
                }
            });
        });
    </script>

</body>

</html>