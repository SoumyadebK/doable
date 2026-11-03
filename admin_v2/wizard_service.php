<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;

if (empty($_GET['id']))
    $title = "Add Service / Service Code";
else
    $title = "Edit Service / Service Code";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

if (!empty($_POST)) {
    if (isset($_POST['PK_LOCATION'])) {
        $res = $db_account->Execute("DELETE FROM `DOA_SERVICE_DOCUMENTS` WHERE PK_SERVICE_MASTER =  '$_GET[id]'");
        for ($i = 0; $i < count($_POST['PK_LOCATION']); $i++) {
            $SERVICE_DOCUMENT_DATA['PK_SERVICE_MASTER'] = $_GET['id'];
            $SERVICE_DOCUMENT_DATA['PK_LOCATION'] = $_POST['PK_LOCATION'][$i];
            if (!empty($_FILES['FILE_PATH']['name'][$i])) {
                $extn             = explode(".", $_FILES['FILE_PATH']['name'][$i]);
                $iindex            = count($extn) - 1;
                $rand_string     = time() . "-" . rand(100000, 999999);
                $file11            = 'service_document_' . $_SESSION['PK_USER'] . $rand_string . "." . $extn[$iindex];
                $extension       = strtolower($extn[$iindex]);

                $image_path    = '../uploads/service_document/' . $file11;
                move_uploaded_file($_FILES['FILE_PATH']['tmp_name'][$i], $image_path);
                $SERVICE_DOCUMENT_DATA['FILE_PATH'] = $image_path;
            } else {
                $SERVICE_DOCUMENT_DATA['FILE_PATH'] = $_POST['FILE_PATH_URL'][$i];
            }

            if (empty($_GET['id'])) {
                $SERVICE_DOCUMENT_DATA['ACTIVE'] = 1;
                $SERVICE_DOCUMENT_DATA['CREATED_BY']  = $_SESSION['PK_USER'];
                $SERVICE_DOCUMENT_DATA['CREATED_ON']  = date("Y-m-d H:i");
            } else {
                $SERVICE_DOCUMENT_DATA['EDITED_BY']    = $_SESSION['PK_USER'];
                $SERVICE_DOCUMENT_DATA['EDITED_ON'] = date("Y-m-d H:i");
            }
            db_perform_account('DOA_SERVICE_DOCUMENTS', $SERVICE_DOCUMENT_DATA, 'insert');
        }
    }
    header("location:wizard_service_provider.php");
}

if (empty($_GET['id'])) {
    $SERVICE_NAME = '';
    $PK_SERVICE_CLASS = '';
    $IS_SCHEDULE = 1;
    $PK_LOCATION = '';
    $DESCRIPTION = '';
    $ACTIVE = '';

    $PK_SERVICE_CODE = '';
    $SERVICE_CODE = '';
    $PRICE = '';
    $IS_GROUP = 0;
    $IS_SUNDRY = 0;
    $CAPACITY = '';
    $IS_CHARGEABLE = 0;
    $COUNT_ON_CALENDAR = 1;
    $SORT_ORDER = 0;
} else {
    $res = $db_account->Execute("SELECT * FROM `DOA_SERVICE_MASTER` WHERE `PK_SERVICE_MASTER` = '$_GET[id]'");
    if ($res->RecordCount() == 0) {
        header("location:all_service_codes.php");
        exit;
    }
    $SERVICE_NAME = $res->fields['SERVICE_NAME'];
    $PK_SERVICE_CLASS = $res->fields['PK_SERVICE_CLASS'];
    $MISC_TYPE = $res->fields['MISC_TYPE'];
    $IS_SCHEDULE = $res->fields['IS_SCHEDULE'];
    $PK_LOCATION = $res->fields['PK_LOCATION'];
    $DESCRIPTION = $res->fields['DESCRIPTION'];
    $ACTIVE = $res->fields['ACTIVE'];

    $service_code = $db_account->Execute("SELECT * FROM DOA_SERVICE_CODE WHERE PK_SERVICE_MASTER = '$_GET[id]'");
    $PK_SERVICE_CODE = $service_code->fields['PK_SERVICE_CODE'];
    $SERVICE_CODE = $service_code->fields['SERVICE_CODE'];
    $PRICE =  $service_code->fields['PRICE'];
    $IS_GROUP = $service_code->fields['IS_GROUP'];
    $IS_SUNDRY = $service_code->fields['IS_SUNDRY'];
    $CAPACITY = $service_code->fields['CAPACITY'];
    $IS_CHARGEABLE = $service_code->fields['IS_CHARGEABLE'];
    $COUNT_ON_CALENDAR = $service_code->fields['COUNT_ON_CALENDAR'];
    $SORT_ORDER = $service_code->fields['SORT_ORDER'];
}

$help_title = '';
$help_description = '';
$help = $db->Execute("SELECT * FROM DOA_HELP_PAGE WHERE PAGE_LINK = 'service_codes'");
if ($help->RecordCount() > 0) {
    $help_title = $help->fields['TITLE'];
    $help_description = $help->fields['DESCRIPTION'];
}
?>

<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/html">
<?php include 'layout/header_script.php'; ?>
<?php require_once('../includes/header.php'); ?>
<?php include 'layout/header.php'; ?>
<link href="../assets/sumoselect/sumoselect.min.css" rel="stylesheet" />
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

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

    /* Layout */
    .main-grid {
        display: grid;
        grid-template-columns: 1fr 340px;
        gap: 24px;
    }

    @media (max-width: 1200px) {
        .main-grid {
            grid-template-columns: 1fr;
        }

        .container-fluid {
            padding: 16px !important;
        }
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
        flex-wrap: wrap;
        gap: 12px;
    }

    .card-modern .card-header h5 {
        font-size: 16px;
        font-weight: 600;
        color: var(--gray-800);
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .card-modern .card-header h5 i {
        color: var(--primary-color);
    }

    .card-modern .card-header .status-indicator {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 14px;
        border-radius: 50px;
        font-size: 13px;
        font-weight: 500;
    }

    .card-modern .card-header .status-indicator.active {
        background: #D1FAE5;
        color: #065F46;
    }

    .card-modern .card-header .status-indicator.inactive {
        background: #FEE2E2;
        color: #991B1B;
    }

    .card-modern .card-header .status-indicator i {
        font-size: 8px;
        margin-right: 0;
    }

    .card-modern .card-body {
        padding: 28px 32px;
    }

    @media (max-width: 768px) {
        .card-modern .card-body {
            padding: 20px;
        }
    }

    /* Tabs */
    .tabs-modern {
        display: flex;
        gap: 8px;
        border-bottom: 1px solid var(--gray-200);
        padding: 0 32px;
        background: #fff;
        overflow-x: auto;
    }

    @media (max-width: 768px) {
        .tabs-modern {
            padding: 0 20px;
        }
    }

    .tabs-modern .tab-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 16px 20px;
        font-size: 14px;
        font-weight: 500;
        color: var(--gray-500);
        text-decoration: none;
        border-bottom: 2px solid transparent;
        margin-bottom: -1px;
        white-space: nowrap;
        transition: all 0.2s ease;
        cursor: pointer;
    }

    .tabs-modern .tab-link i {
        font-size: 16px;
    }

    .tabs-modern .tab-link:hover {
        color: var(--gray-700);
        background: var(--gray-50);
    }

    .tabs-modern .tab-link.active {
        color: var(--primary-color);
        border-bottom-color: var(--primary-color);
        background: transparent;
    }

    .tabs-modern .tab-link.disabled {
        opacity: 0.4;
        cursor: not-allowed;
        pointer-events: none;
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

    .form-helper {
        font-size: 12px;
        color: var(--gray-400);
        margin-top: 2px;
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

    textarea.form-control-modern {
        resize: vertical;
        min-height: 88px;
    }

    select.form-control-modern {
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236B7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 12px center;
        padding-right: 36px;
    }

    /* Input group with prefix */
    .input-group-modern {
        display: flex;
        align-items: stretch;
    }

    .input-group-modern .input-prefix {
        display: flex;
        align-items: center;
        padding: 0 14px;
        background: var(--gray-100);
        border: 1.5px solid var(--gray-200);
        border-right: none;
        border-top-left-radius: var(--radius-sm);
        border-bottom-left-radius: var(--radius-sm);
        font-size: 14px;
        color: var(--gray-600);
    }

    .input-group-modern .form-control-modern {
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
    }

    /* Radio */
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

    /* Buttons */
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

    .btn-modern-danger {
        background: #FEE2E2;
        color: #991B1B;
    }

    .btn-modern-danger:hover {
        background: #FCA5A5;
        color: #7F1D1D;
    }

    .form-actions {
        display: flex;
        gap: 12px;
        margin-top: 28px;
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

    /* Section Header */
    .section-header {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px 18px;
        background: var(--gray-50);
        border-radius: var(--radius-sm);
        margin: 24px 0 20px 0;
        border: 1px solid var(--gray-200);
        grid-column: 1 / -1;
    }

    .section-header:first-child {
        margin-top: 0;
    }

    .section-header i {
        color: var(--primary-color);
        font-size: 18px;
    }

    .section-header span {
        font-size: 14px;
        font-weight: 600;
        color: var(--gray-700);
    }

    /* Help Card */
    .help-card {
        background: linear-gradient(135deg, #F0FDF4 0%, #ECFDF5 100%);
        border: 1px solid #A7F3D0;
        border-radius: var(--radius-lg);
        padding: 24px;
        position: sticky;
        top: 24px;
    }

    .help-card .help-icon {
        width: 48px;
        height: 48px;
        background: var(--primary-color);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 20px;
        margin-bottom: 16px;
    }

    .help-card h5 {
        font-size: 16px;
        font-weight: 600;
        color: var(--gray-800);
        margin: 0 0 12px 0;
    }

    .help-card p {
        font-size: 14px;
        color: var(--gray-600);
        line-height: 1.6;
        margin: 0;
    }

    /* Document Row */
    .document-row {
        display: grid;
        grid-template-columns: 1fr 1fr auto;
        gap: 16px;
        align-items: end;
        padding: 16px;
        border: 1px solid var(--gray-200);
        border-radius: var(--radius-sm);
        margin-bottom: 12px;
        background: var(--gray-50);
    }

    @media (max-width: 768px) {
        .document-row {
            grid-template-columns: 1fr;
        }
    }

    /* SumoSelect overrides to match design */
    .SumoSelect>.CaptionCont {
        border: 1.5px solid var(--gray-200) !important;
        border-radius: var(--radius-sm) !important;
        padding: 10px 14px !important;
        background: #fff !important;
        font-size: 14px !important;
        font-family: inherit !important;
        color: var(--gray-800) !important;
        min-height: 44px;
        box-shadow: none !important;
    }

    .SumoSelect>.CaptionCont>span.placeholder {
        color: var(--gray-400) !important;
        font-style: normal !important;
    }

    .SumoSelect:focus>.CaptionCont,
    .SumoSelect.open>.CaptionCont {
        border-color: var(--primary-color) !important;
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1) !important;
    }

    .SumoSelect>.optWrapper {
        border: 1.5px solid var(--gray-200) !important;
        border-radius: var(--radius-sm) !important;
        margin-top: 4px !important;
        box-shadow: var(--shadow-md) !important;
        font-family: inherit !important;
    }

    .SumoSelect>.optWrapper>.options>li {
        font-size: 14px !important;
        padding: 10px 14px !important;
        color: var(--gray-700) !important;
    }

    .SumoSelect>.optWrapper>.options>li:hover {
        background: var(--gray-50) !important;
    }

    .SumoSelect>.optWrapper>.options>li.selected {
        background: #F0FDF4 !important;
        color: var(--primary-dark) !important;
    }

    .SumoSelect>.optWrapper>.options>li.opt:hover {
        background: var(--gray-50) !important;
    }

    .SumoSelect>.optWrapper>.options>li label {
        color: inherit !important;
    }

    /* Tooltip */
    .tooltip-bubble {
        position: relative;
        display: inline-flex;
        align-items: center;
        margin-left: 4px;
        color: var(--gray-400);
        cursor: help;
    }

    .tooltip-bubble .tooltip-text {
        visibility: hidden;
        opacity: 0;
        width: 220px;
        background: var(--gray-800);
        color: #fff;
        text-align: left;
        border-radius: var(--radius-sm);
        padding: 10px 12px;
        position: absolute;
        z-index: 10;
        bottom: 130%;
        left: 50%;
        transform: translateX(-50%);
        font-size: 12px;
        font-weight: 400;
        line-height: 1.5;
        transition: all 0.2s ease;
        box-shadow: var(--shadow-md);
    }

    .tooltip-bubble:hover .tooltip-text {
        visibility: visible;
        opacity: 1;
    }

    .full-width {
        grid-column: 1 / -1;
    }

    @media (max-width: 768px) {
        .full-width {
            grid-column: 1;
        }
    }
</style>

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">
        <?php require_once('../includes/header.php'); ?>

        <div class="page-wrapper" style="padding-top: 0px !important;">
            <div class="container-fluid py-4 px-4 m-auto mx-auto dashboard-container">

                <!-- Main Grid -->
                <div class="main-grid">
                    <!-- Main Content -->
                    <div class="card-modern">
                        <div class="card-header">
                            <h5>
                                <i class="fas fa-concierge-bell"></i>
                                <?= !empty($_GET['id']) ? htmlspecialchars($SERVICE_NAME) : 'New Service' ?>
                            </h5>
                            <?php if (!empty($_GET['id'])): ?>
                                <span class="status-indicator <?= ($ACTIVE == 1) ? 'active' : 'inactive' ?>">
                                    <i class="fas fa-circle"></i>
                                    <?= ($ACTIVE == 1) ? 'Active' : 'Inactive' ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Tabs -->
                        <ul class="nav tabs-modern" role="tablist">
                            <li>
                                <a class="tab-link active" data-bs-toggle="tab" href="#service_info" role="tab">
                                    <i class="fas fa-info-circle"></i> Info
                                </a>
                            </li>
                            <li>
                                <a class="tab-link <?= (!empty($_GET['id'])) ? '' : 'disabled' ?>"
                                    data-bs-toggle="tab" id="service_document_link" href="#service_document"
                                    role="tab" <?= (!empty($_GET['id'])) ? '' : 'aria-disabled="true"' ?>>
                                    <i class="fas fa-file-alt"></i> Service Document
                                </a>
                            </li>
                        </ul>

                        <!-- Tab panes -->
                        <div class="tab-content">
                            <!-- ================= SERVICE INFO TAB ================= -->
                            <div class="tab-pane fade show active" id="service_info" role="tabpanel">
                                <div class="card-body">
                                    <form class="form-material form-horizontal" id="service_info_form">
                                        <input type="hidden" name="FUNCTION_NAME" value="saveServiceData">
                                        <input type="hidden" name="PK_SERVICE_MASTER" class="PK_SERVICE_MASTER" value="<?= (empty($_GET['id'])) ? '' : $_GET['id'] ?>">
                                        <input type="hidden" name="PK_SERVICE_CODE" class="PK_SERVICE_CODE" value="<?= (empty($PK_SERVICE_CODE)) ? '' : $PK_SERVICE_CODE ?>">

                                        <!-- Section: Basic Information -->
                                        <div class="section-header">
                                            <i class="fas fa-info-circle"></i>
                                            <span>Basic Information</span>
                                        </div>

                                        <div class="form-grid">
                                            <div class="form-group-modern">
                                                <label class="form-label">Service Name <span class="required">*</span></label>
                                                <input type="text" id="SERVICE_NAME" name="SERVICE_NAME" class="form-control-modern" placeholder="Enter Service Name" required value="<?php echo htmlspecialchars($SERVICE_NAME) ?>">
                                            </div>

                                            <div class="form-group-modern">
                                                <label class="form-label">Service Code <span class="required">*</span></label>
                                                <input type="text" id="SERVICE_CODE" name="SERVICE_CODE" class="form-control-modern" placeholder="Enter Service Code" required value="<?php echo htmlspecialchars($SERVICE_CODE) ?>">
                                            </div>

                                            <div class="form-group-modern full-width">
                                                <label class="form-label">Location</label>
                                                <select class="form-control-modern PK_LOCATION" name="PK_LOCATION" onchange="selectServiceClass(this)">
                                                    <?php
                                                    $row = $db->Execute("SELECT * FROM DOA_LOCATION WHERE PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND ACTIVE = 1 AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
                                                    while (!$row->EOF) { ?>
                                                        <option value="<?php echo $row->fields['PK_LOCATION']; ?>" <?= ($PK_LOCATION == $row->fields['PK_LOCATION']) ? 'selected' : '' ?>><?= htmlspecialchars($row->fields['LOCATION_NAME']) ?></option>
                                                    <?php $row->MoveNext();
                                                    } ?>
                                                </select>
                                            </div>

                                            <div class="form-group-modern full-width">
                                                <label class="form-label">Description</label>
                                                <textarea class="form-control-modern" rows="3" id="DESCRIPTION" name="DESCRIPTION"><?php echo htmlspecialchars($DESCRIPTION) ?></textarea>
                                            </div>
                                        </div>

                                        <!-- Section: Pricing -->
                                        <div class="section-header">
                                            <i class="fas fa-dollar-sign"></i>
                                            <span>Pricing</span>
                                        </div>

                                        <div class="form-grid">
                                            <div class="form-group-modern">
                                                <label class="form-label">Is Chargeable?</label>
                                                <div class="radio-group-modern">
                                                    <label class="radio-item">
                                                        <input type="radio" name="IS_CHARGEABLE" class="IS_CHARGEABLE" value="1" <?= (($IS_CHARGEABLE == 1) ? 'checked' : '') ?>>
                                                        Yes
                                                    </label>
                                                    <label class="radio-item">
                                                        <input type="radio" name="IS_CHARGEABLE" class="IS_CHARGEABLE" value="0" <?= (($IS_CHARGEABLE == 0) ? 'checked' : '') ?>>
                                                        No
                                                    </label>
                                                </div>
                                            </div>

                                            <div class="form-group-modern service_price" style="display: <?= ($IS_CHARGEABLE == 0) ? 'none' : 'flex' ?>;">
                                                <label class="form-label">Price</label>
                                                <div class="input-group-modern">
                                                    <span class="input-prefix"><?= $currency ?></span>
                                                    <input type="text" id="PRICE" name="PRICE" class="form-control-modern" placeholder="Price" value="<?= htmlspecialchars($PRICE) ?>">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Section: Options -->
                                        <div class="section-header">
                                            <i class="fas fa-sliders-h"></i>
                                            <span>Options</span>
                                        </div>

                                        <div class="form-grid">
                                            <div class="form-group-modern">
                                                <label class="form-label">Service Class</label>
                                                <select class="form-control-modern PK_SERVICE_CLASS" name="PK_SERVICE_CLASS" onchange="selectServiceClass(this)">
                                                    <option value="">Select</option>
                                                    <?php
                                                    $row = $db->Execute("SELECT * FROM DOA_SERVICE_CLASS WHERE ACTIVE = 1");
                                                    while (!$row->EOF) { ?>
                                                        <option value="<?php echo $row->fields['PK_SERVICE_CLASS']; ?>" <?= ($PK_SERVICE_CLASS == $row->fields['PK_SERVICE_CLASS']) ? 'selected' : '' ?>><?= htmlspecialchars($row->fields['SERVICE_CLASS']) ?></option>
                                                    <?php $row->MoveNext();
                                                    } ?>
                                                </select>
                                            </div>

                                            <div class="form-group-modern service_class_type" id="misc_type_div" style="display: <?= ($PK_SERVICE_CLASS == 5) ? 'flex' : 'none' ?>;">
                                                <label class="form-label">Misc. Type</label>
                                                <select class="form-control-modern MISC_TYPE" name="MISC_TYPE">
                                                    <option value="">Select</option>
                                                    <option value="GENERAL" <?= ($MISC_TYPE == 'GENERAL') ? 'selected' : '' ?>>General</option>
                                                    <option value="DOR" <?= ($MISC_TYPE == 'DOR') ? 'selected' : '' ?>>DOR</option>
                                                    <option value="SHOWCASE" <?= ($MISC_TYPE == 'SHOWCASE') ? 'selected' : '' ?>>Showcase</option>
                                                </select>
                                            </div>

                                            <div class="form-group-modern full-width">
                                                <label class="form-label">Scheduling Code</label>
                                                <div class="multiselect-box">
                                                    <select class="multi_select" id="PK_SCHEDULING_CODE" name="PK_SCHEDULING_CODE[]" multiple>
                                                        <?php
                                                        $selected_scheduling_code  = [];
                                                        if (!empty($_GET['id'])) {
                                                            $selected_scheduling_code_row = $db_account->Execute("SELECT `PK_SCHEDULING_CODE` FROM `DOA_SCHEDULING_SERVICE` WHERE `PK_SERVICE_MASTER` = '$_GET[id]'");
                                                            while (!$selected_scheduling_code_row->EOF) {
                                                                $selected_scheduling_code[] = $selected_scheduling_code_row->fields['PK_SCHEDULING_CODE'];
                                                                $selected_scheduling_code_row->MoveNext();
                                                            }
                                                        }
                                                        $scheduling_code = $db_account->Execute("SELECT * FROM `DOA_SCHEDULING_CODE` WHERE `ACTIVE` = 1 AND PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
                                                        while (!$scheduling_code->EOF) { ?>
                                                            <option value="<?= $scheduling_code->fields['PK_SCHEDULING_CODE'] ?>" <?= in_array($scheduling_code->fields['PK_SCHEDULING_CODE'], $selected_scheduling_code) ? "selected" : "" ?>><?= htmlspecialchars($scheduling_code->fields['SCHEDULING_NAME'] . ' (' . $scheduling_code->fields['SCHEDULING_CODE'] . ')') ?></option>
                                                        <?php $scheduling_code->MoveNext();
                                                        } ?>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="form-group-modern">
                                                <label class="form-label">Is Group?</label>
                                                <div class="radio-group-modern">
                                                    <label class="radio-item">
                                                        <input type="radio" name="IS_GROUP" class="IS_GROUP" value="1" <?= (($IS_GROUP == 1) ? 'checked' : '') ?>>
                                                        Yes
                                                    </label>
                                                    <label class="radio-item">
                                                        <input type="radio" name="IS_GROUP" class="IS_GROUP" value="0" <?= (($IS_GROUP == 0) ? 'checked' : '') ?>>
                                                        No
                                                    </label>
                                                </div>
                                            </div>

                                            <div class="form-group-modern capacity_div" style="display: <?= (($IS_GROUP == 1) ? 'flex' : 'none') ?>;">
                                                <label class="form-label">Capacity</label>
                                                <input type="number" class="form-control-modern" name="CAPACITY" id="CAPACITY" value="<?= htmlspecialchars($CAPACITY) ?>">
                                            </div>

                                            <div class="form-group-modern">
                                                <label class="form-label">Is Sundry?</label>
                                                <div class="radio-group-modern">
                                                    <label class="radio-item">
                                                        <input type="radio" name="IS_SUNDRY" class="IS_SUNDRY" value="1" <?= (($IS_SUNDRY == 1) ? 'checked' : '') ?>>
                                                        Yes
                                                    </label>
                                                    <label class="radio-item">
                                                        <input type="radio" name="IS_SUNDRY" class="IS_SUNDRY" value="0" <?= (($IS_SUNDRY == 0) ? 'checked' : '') ?>>
                                                        No
                                                    </label>
                                                </div>
                                            </div>

                                            <div class="form-group-modern">
                                                <label class="form-label">
                                                    Count on Calendar?
                                                    <span class="tooltip-bubble" tabindex="0">
                                                        <i class="fas fa-question-circle" aria-hidden="true"></i>
                                                        <span class="tooltip-text">Is this Service to be counted on Calendar Counter?</span>
                                                    </span>
                                                </label>
                                                <div class="radio-group-modern">
                                                    <label class="radio-item">
                                                        <input type="radio" name="COUNT_ON_CALENDAR" class="COUNT_ON_CALENDAR" value="1" <?= (($COUNT_ON_CALENDAR == 1) ? 'checked' : '') ?>>
                                                        Yes
                                                    </label>
                                                    <label class="radio-item">
                                                        <input type="radio" name="COUNT_ON_CALENDAR" class="COUNT_ON_CALENDAR" value="0" <?= (($COUNT_ON_CALENDAR == 0) ? 'checked' : '') ?>>
                                                        No
                                                    </label>
                                                </div>
                                            </div>

                                            <div class="form-group-modern">
                                                <label class="form-label">Sort Order</label>
                                                <input type="text" id="SORT_ORDER" name="SORT_ORDER" class="form-control-modern" placeholder="Enter Sort Order" value="<?php echo htmlspecialchars($SORT_ORDER) ?>">
                                                <div class="form-helper">Controls display order in lists</div>
                                            </div>
                                        </div>

                                        <!-- Section: Status -->
                                        <?php if (!empty($_GET['id'])) { ?>
                                            <div class="section-header">
                                                <i class="fas fa-toggle-on"></i>
                                                <span>Status</span>
                                            </div>

                                            <div class="form-group-modern">
                                                <label class="form-label">Active</label>
                                                <div class="radio-group-modern">
                                                    <label class="radio-item">
                                                        <input type="radio" name="ACTIVE" id="ACTIVE_YES" value="1" <?php if ($ACTIVE == 1) echo 'checked="checked"'; ?>>
                                                        Yes
                                                    </label>
                                                    <label class="radio-item">
                                                        <input type="radio" name="ACTIVE" id="ACTIVE_NO" value="0" <?php if ($ACTIVE == 0) echo 'checked="checked"'; ?>>
                                                        No
                                                    </label>
                                                </div>
                                                <div class="form-helper">Set the status of this service</div>
                                            </div>
                                        <?php } ?>

                                        <!-- Form Actions -->
                                        <div class="form-actions">
                                            <button type="submit" class="btn-modern btn-modern-primary">
                                                <i class="fas fa-arrow-right"></i> Continue
                                            </button>
                                            <button type="button" id="cancel_button" class="btn-modern btn-modern-secondary">
                                                <i class="fas fa-times"></i> Cancel
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- ================= SERVICE DOCUMENT TAB ================= -->
                            <div class="tab-pane fade" id="service_document" role="tabpanel">
                                <div class="card-body">
                                    <form method="post" action="" enctype="multipart/form-data">
                                        <div class="section-header" style="margin-top: 0;">
                                            <i class="fas fa-file-alt"></i>
                                            <span>Documents</span>
                                        </div>

                                        <div id="append_service_document">
                                            <?php
                                            if (!empty($_GET['id'])) {
                                                $service_document = $db_account->Execute("SELECT * FROM DOA_SERVICE_DOCUMENTS WHERE PK_SERVICE_MASTER = '$_GET[id]'");
                                                while (!$service_document->EOF) { ?>
                                                    <div class="document-row">
                                                        <div class="form-group-modern">
                                                            <label class="form-label">Location</label>
                                                            <select class="form-control-modern PK_LOCATION" name="PK_LOCATION[]">
                                                                <option>Select Location</option>
                                                                <?php
                                                                $row = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME FROM DOA_LOCATION WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]' AND ACTIVE = 1 ORDER BY LOCATION_NAME");
                                                                while (!$row->EOF) { ?>
                                                                    <option value="<?php echo $row->fields['PK_LOCATION']; ?>" <?= ($service_document->fields['PK_LOCATION'] == $row->fields['PK_LOCATION']) ? 'selected' : '' ?>><?= htmlspecialchars($row->fields['LOCATION_NAME']) ?></option>
                                                                <?php $row->MoveNext();
                                                                } ?>
                                                            </select>
                                                        </div>
                                                        <div class="form-group-modern">
                                                            <label class="form-label">Document File</label>
                                                            <input type="file" name="FILE_PATH[]" class="form-control-modern">
                                                            <a target="_blank" href="<?= $service_document->fields['FILE_PATH'] ?>" style="font-size: 12px; color: var(--primary-color); margin-top: 4px;">
                                                                <i class="fas fa-external-link-alt"></i> View current file
                                                            </a>
                                                            <input type="hidden" name="FILE_PATH_URL[]" value="<?= $service_document->fields['FILE_PATH'] ?>">
                                                        </div>
                                                        <div>
                                                            <button type="button" class="btn-modern btn-modern-danger" onclick="removeServiceDocument(this);">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                <?php $service_document->MoveNext();
                                                } ?>
                                            <?php } else { ?>
                                                <div class="document-row">
                                                    <div class="form-group-modern">
                                                        <label class="form-label">Location</label>
                                                        <select class="form-control-modern PK_LOCATION" name="PK_LOCATION[]">
                                                            <option>Select Location</option>
                                                            <?php
                                                            $row = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME FROM DOA_LOCATION WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]' AND ACTIVE = 1 ORDER BY LOCATION_NAME");
                                                            while (!$row->EOF) { ?>
                                                                <option value="<?php echo $row->fields['PK_LOCATION']; ?>"><?= htmlspecialchars($row->fields['LOCATION_NAME']) ?></option>
                                                            <?php $row->MoveNext();
                                                            } ?>
                                                        </select>
                                                    </div>
                                                    <div class="form-group-modern">
                                                        <label class="form-label">Document File</label>
                                                        <input type="file" name="FILE_PATH[]" class="form-control-modern">
                                                    </div>
                                                    <div>
                                                        <button type="button" class="btn-modern btn-modern-danger" onclick="removeServiceDocument(this);">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            <?php } ?>
                                        </div>

                                        <div style="text-align: right; margin-top: 12px;">
                                            <button type="button" class="btn-modern btn-modern-secondary" onclick="addServiceDocument();">
                                                <i class="fas fa-plus"></i> Add More
                                            </button>
                                        </div>

                                        <div class="form-actions">
                                            <button type="submit" class="btn-modern btn-modern-primary">
                                                <i class="fas fa-save"></i> Submit
                                            </button>
                                            <button type="button" onclick="window.location.href='all_services.php'" class="btn-modern btn-modern-secondary">
                                                <i class="fas fa-times"></i> Cancel
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Help Sidebar -->
                    <div>
                        <div class="help-card">
                            <div class="help-icon">
                                <i class="fas fa-question"></i>
                            </div>
                            <h5><?= htmlspecialchars($help_title) ?></h5>
                            <p><?= htmlspecialchars($help_description) ?></p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <?php require_once('../includes/footer.php'); ?>

    <script src="../assets/sumoselect/jquery.sumoselect.min.js"></script>
    <script>
        let PK_SERVICE_MASTER = parseInt(<?= empty($_GET['id']) ? 0 : $_GET['id'] ?>);

        $('.multi_select').SumoSelect({
            search: true,
            placeholder: 'Select Scheduling Code',
            searchText: 'Search...',
            selectAll: true
        });

        $('.multi_sumo_select_location').SumoSelect({
            placeholder: 'Select Location',
            selectAll: true
        });

        $(document).on('change', '.IS_CHARGEABLE', function() {
            if ($(this).val() == 1) {
                $('.service_price').slideDown();
                $('#PRICE').removeAttr('required');
            } else {
                $('.service_price').slideUp();
                $("#PRICE").attr("required", "required");
            }
        });

        $(document).on('change', '.IS_GROUP', function() {
            if ($(this).val() == 1) {
                $('.capacity_div').slideDown();
            } else {
                $('.capacity_div').slideUp();
            }
        });

        function selectServiceClass(param) {
            let PK_SERVICE_CLASS = parseInt($(param).val());
            $('.service_class_type').slideUp();

            if (PK_SERVICE_CLASS === 5) {
                $('#misc_type_div').slideDown();
            }
        }

        function addServiceDocument() {
            $('#append_service_document').append(`<div class="document-row">
                                                <div class="form-group-modern">
                                                    <label class="form-label">Location</label>
                                                    <select class="form-control-modern PK_LOCATION" name="PK_LOCATION[]">
                                                        <option>Select Location</option>
                                                        <?php
                                                        $row = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME FROM DOA_LOCATION WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]' AND ACTIVE = 1 ORDER BY LOCATION_NAME");
                                                        while (!$row->EOF) { ?>
                                                            <option value="<?php echo $row->fields['PK_LOCATION']; ?>"><?= htmlspecialchars($row->fields['LOCATION_NAME']) ?></option>
                                                        <?php $row->MoveNext();
                                                        } ?>
                                                    </select>
                                                </div>
                                                <div class="form-group-modern">
                                                    <label class="form-label">Document File</label>
                                                    <input type="file" name="FILE_PATH[]" class="form-control-modern">
                                                </div>
                                                <div>
                                                    <button type="button" class="btn-modern btn-modern-danger" onclick="removeServiceDocument(this);">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>`);
        }

        function removeServiceDocument(param) {
            $(param).closest('.document-row').remove();
        }

        $(document).on('click', '#cancel_button', function() {
            window.location.href = 'all_services.php'
        });

        $(document).on('submit', '#service_info_form', function(event) {
            event.preventDefault();
            let form_data = $('#service_info_form').serialize();
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                data: form_data,
                success: function(data) {
                    if (PK_SERVICE_MASTER == 0) {
                        $('.disabled').attr('disabled', false).removeClass('disabled');
                        $('.PK_SERVICE_MASTER').val(data);
                        window.location.href = 'wizard_service_provider.php';
                    } else {
                        window.location.href = 'wizard_service_provider.php';
                    }
                }
            });
        });

        $(document).on('submit', '#service_code_form', function(event) {
            event.preventDefault();
            let form_data = $('#service_code_form').serialize();
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                data: form_data,
                success: function(data) {
                    window.location.href = 'wizard_service_provider.php';
                }
            });
        });
    </script>
</body>

</html>