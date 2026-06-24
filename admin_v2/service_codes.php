<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];

// Simple fix - convert to array if it's a string
if (!is_array($DEFAULT_LOCATION_ID)) {
    // If it's a comma-separated string like "13, 27"
    if (strpos($DEFAULT_LOCATION_ID, ',') !== false) {
        $location_count = array_map('trim', explode(',', $DEFAULT_LOCATION_ID));
    } else {
        // If it's a single value
        $location_count = !empty($DEFAULT_LOCATION_ID) ? [$DEFAULT_LOCATION_ID] : [];
    }
}

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
    header("location:all_service_codes.php");
}

if (empty($_GET['id'])) {
    $SERVICE_NAME = '';
    $PK_SERVICE_CLASS = '';
    $MISC_TYPE = '';
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
    $DESCRIPTION = $res->fields['DESCRIPTION'];
    $PK_LOCATION = $res->fields['PK_LOCATION'];
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
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
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

    /* Alert */
    .alert-modern {
        padding: 16px 20px;
        border-radius: var(--radius-sm);
        border: 1px solid #FCD34D;
        background: #FEF3C7;
        color: #92400E;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 24px;
    }

    .alert-modern i {
        font-size: 20px;
        flex-shrink: 0;
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
        border-bottom: 2px solid var(--gray-200);
        padding-bottom: 0;
        margin-bottom: 24px;
        flex-wrap: wrap;
    }

    .tabs-modern .tab-item {
        padding: 12px 20px;
        font-size: 14px;
        font-weight: 500;
        color: var(--gray-500);
        cursor: pointer;
        border: none;
        background: transparent;
        border-bottom: 2px solid transparent;
        margin-bottom: -2px;
        transition: all 0.2s ease;
        white-space: nowrap;
        display: flex;
        align-items: center;
        gap: 8px;
        border-radius: 0;
    }

    .tabs-modern .tab-item:hover {
        color: var(--gray-700);
        background: var(--gray-50);
    }

    .tabs-modern .tab-item.active {
        color: var(--primary-color);
        border-bottom-color: var(--primary-color);
        font-weight: 600;
    }

    .tabs-modern .tab-item i {
        font-size: 16px;
    }

    .tab-content-modern {
        padding: 4px 0;
    }

    .tab-pane-modern {
        display: none;
        animation: fadeIn 0.3s ease;
    }

    .tab-pane-modern.active {
        display: block;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(8px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
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
        padding: 8px 0;
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

    .form-group-modern .form-label .tooltip-bubble {
        position: relative;
        display: inline-block;
        cursor: help;
        color: var(--primary-color);
        margin-left: 4px;
    }

    .form-group-modern .form-label .tooltip-bubble .tooltip-text {
        position: absolute;
        bottom: 125%;
        left: 50%;
        transform: translateX(-50%) translateY(0);
        min-width: 160px;
        max-width: 260px;
        background: var(--primary-color);
        color: #fff;
        padding: 8px 10px;
        border-radius: 6px;
        font-size: 13px;
        line-height: 1.2;
        text-align: center;
        white-space: normal;
        opacity: 0;
        visibility: hidden;
        transition: opacity .18s ease, transform .18s ease;
        box-shadow: var(--shadow-md);
        z-index: 9999;
        pointer-events: none;
    }

    .form-group-modern .form-label .tooltip-bubble .tooltip-text::after {
        content: "";
        position: absolute;
        top: 100%;
        left: 50%;
        transform: translateX(-50%);
        border-width: 6px;
        border-style: solid;
        border-color: var(--primary-color) transparent transparent transparent;
    }

    .form-group-modern .form-label .tooltip-bubble:hover .tooltip-text,
    .form-group-modern .form-label .tooltip-bubble:focus-within .tooltip-text {
        opacity: 1;
        visibility: visible;
        transform: translateX(-50%) translateY(-4px);
        pointer-events: auto;
    }

    .form-group-modern .form-label .tooltip-bubble .ti-help-alt {
        outline: none;
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

    select.form-control-modern {
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236B7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 12px center;
        padding-right: 36px;
    }

    textarea.form-control-modern {
        min-height: 80px;
        resize: vertical;
    }

    /* Radio & Checkbox */
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
        background: var(--danger-color);
        color: #fff;
    }

    .btn-modern-danger:hover {
        background: #DC2626;
        color: #fff;
    }

    .btn-modern-sm {
        padding: 6px 18px;
        font-size: 13px;
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

    /* Input group */
    .input-group-modern {
        display: flex;
        align-items: center;
        border: 1.5px solid var(--gray-200);
        border-radius: var(--radius-sm);
        overflow: hidden;
        transition: all 0.2s ease;
    }

    .input-group-modern:focus-within {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1);
    }

    .input-group-modern .form-control-modern {
        border: none;
        border-radius: 0;
        flex: 1;
    }

    .input-group-modern .form-control-modern:focus {
        box-shadow: none;
    }

    .input-group-modern .input-group-text {
        padding: 10px 14px;
        background: var(--gray-50);
        color: var(--gray-500);
        font-size: 14px;
        font-weight: 500;
        border-left: 1px solid var(--gray-200);
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

    /* SumoSelect override */
    .SumoSelect {
        width: 100% !important;
    }

    .SumoSelect>.CaptionCont {
        border: 1.5px solid var(--gray-200) !important;
        border-radius: var(--radius-sm) !important;
        padding: 8px 12px !important;
        min-height: 44px;
        transition: all 0.2s ease;
    }

    .SumoSelect>.CaptionCont:hover {
        border-color: var(--gray-300) !important;
    }

    .SumoSelect.open>.CaptionCont {
        border-color: var(--primary-color) !important;
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1) !important;
    }

    .SumoSelect>.CaptionCont>span {
        color: var(--gray-700) !important;
        font-size: 14px !important;
    }

    .SumoSelect>.CaptionCont>label {
        margin: 0 !important;
        padding: 0 !important;
    }

    .SumoSelect>.CaptionCont>label>i {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236B7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E") !important;
        background-position: center !important;
        background-repeat: no-repeat !important;
        width: 12px !important;
        height: 12px !important;
    }

    .SumoSelect .optWrapper {
        border: 1.5px solid var(--gray-200) !important;
        border-radius: var(--radius-sm) !important;
        box-shadow: var(--shadow-md) !important;
    }

    .SumoSelect .optWrapper .options li.opt {
        padding: 8px 14px !important;
        font-size: 14px !important;
        color: var(--gray-700) !important;
        transition: background 0.2s;
    }

    .SumoSelect .optWrapper .options li.opt:hover {
        background: var(--gray-50) !important;
    }

    .SumoSelect .optWrapper .options li.opt.selected {
        background: #F0FDF4 !important;
        color: var(--primary-color) !important;
    }

    .SumoSelect .optWrapper .options li.opt.selected::before {
        content: "✓ ";
        color: var(--primary-color);
    }

    .SumoSelect .select-all {
        padding: 8px 14px !important;
        font-size: 14px !important;
        color: var(--gray-700) !important;
        border-bottom: 1px solid var(--gray-200) !important;
    }

    .SumoSelect .select-all label {
        display: flex !important;
        align-items: center !important;
        gap: 8px !important;
        cursor: pointer !important;
    }

    .SumoSelect .select-all input[type="checkbox"] {
        accent-color: var(--primary-color) !important;
        width: 16px !important;
        height: 16px !important;
        cursor: pointer !important;
    }

    .SumoSelect .search input {
        border: 1px solid var(--gray-200) !important;
        border-radius: var(--radius-sm) !important;
        padding: 6px 10px !important;
        font-size: 14px !important;
    }

    .SumoSelect .search input:focus {
        border-color: var(--primary-color) !important;
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1) !important;
    }

    /* Document row */
    .document-row {
        display: grid;
        grid-template-columns: 1fr 1fr auto;
        gap: 16px;
        align-items: end;
        padding: 12px 0;
        border-bottom: 1px solid var(--gray-100);
    }

    .document-row:last-child {
        border-bottom: none;
    }

    .document-row .remove-btn {
        padding: 8px 12px;
        margin-bottom: 2px;
    }

    @media (max-width: 768px) {
        .document-row {
            grid-template-columns: 1fr;
            gap: 12px;
        }
    }

    /* Options section */
    .options-section {
        padding-left: 20px;
        border-left: 2px solid var(--gray-200);
    }

    .options-section .options-title {
        font-size: 16px;
        font-weight: 600;
        color: var(--gray-700);
        margin-bottom: 16px;
        padding-left: 4px;
    }

    @media (max-width: 768px) {
        .options-section {
            padding-left: 0;
            border-left: none;
            margin-top: 16px;
            padding-top: 16px;
            border-top: 2px solid var(--gray-200);
        }
    }

    .form-helper {
        font-size: 12px;
        color: var(--gray-400);
        margin-top: 4px;
    }
</style>

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">
        <?php require_once('../includes/header.php'); ?>

        <div class="page-wrapper" style="padding-top: 0px !important;">
            <div class="container-fluid py-4 px-4 m-auto mx-auto dashboard-container">
                <div class="row g-4">
                    <!-- Sidebar -->
                    <div class="col-12 col-md-4 col-xl-2">
                        <?php include 'layout/setup_sidebar.php'; ?>
                    </div>
                    <?php if (count($location_count) > 1) { ?>
                        <div class="col-12 col-md-8 col-xl-10">
                            <div class="alert-modern">
                                <i class="fas fa-exclamation-triangle"></i>
                                <span>Please select one Location on the top to add or edit Service / Service Code.</span>
                            </div>
                        </div>
                    <?php } else { ?>

                        <!-- Main Grid -->
                        <div class="col-12 col-md-8 col-xl-10">
                            <div class="main-grid">
                                <!-- Main Content -->
                                <div class="card-modern">
                                    <div class="card-header">
                                        <h5>
                                            <i class="bi bi-handbag"></i>
                                            <?= !empty($_GET['id']) ? 'Edit Service / Service Code' : 'Add Service / Service Code' ?>
                                        </h5>
                                        <?php if (!empty($_GET['id'])): ?>
                                            <span class="status-indicator <?= ($ACTIVE == 1) ? 'active' : 'inactive' ?>">
                                                <i class="fas fa-circle"></i>
                                                <?= ($ACTIVE == 1) ? 'Active' : 'Inactive' ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-body">
                                        <!-- Tabs -->
                                        <div class="tabs-modern" role="tablist">
                                            <button class="tab-item active" data-tab="service_info" role="tab">
                                                <i class="fas fa-info-circle"></i> Info
                                            </button>
                                            <button class="tab-item <?= (!empty($_GET['id'])) ? '' : 'disabled' ?>" data-tab="service_document" role="tab" <?= (!empty($_GET['id'])) ? '' : 'disabled' ?>>
                                                <i class="fas fa-file"></i> Service Document
                                            </button>
                                        </div>

                                        <!-- Tab Content -->
                                        <div class="tab-content-modern">
                                            <!-- Service Info Tab -->
                                            <div class="tab-pane-modern active" id="service_info" role="tabpanel">
                                                <form class="form-material form-horizontal" id="service_info_form">
                                                    <input type="hidden" name="FUNCTION_NAME" value="saveServiceData">
                                                    <input type="hidden" name="PK_SERVICE_MASTER" class="PK_SERVICE_MASTER" value="<?= (empty($_GET['id'])) ? '' : $_GET['id'] ?>">
                                                    <input type="hidden" name="PK_SERVICE_CODE" class="PK_SERVICE_CODE" value="<?= (empty($PK_SERVICE_CODE)) ? '' : $PK_SERVICE_CODE ?>">

                                                    <div class="form-grid">
                                                        <!-- Left Column -->
                                                        <div>
                                                            <!-- Service Name -->
                                                            <div class="form-group-modern">
                                                                <label class="form-label">Service Name <span class="required">*</span></label>
                                                                <input type="text" id="SERVICE_NAME" name="SERVICE_NAME" class="form-control-modern" placeholder="Enter Service Name" required value="<?php echo htmlspecialchars($SERVICE_NAME) ?>">
                                                            </div>

                                                            <!-- Service Code -->
                                                            <div class="form-group-modern">
                                                                <label class="form-label">
                                                                    Service Code <span class="required">*</span>
                                                                    <span class="tooltip-bubble" tabindex="0">
                                                                        <i class="ti-help-alt" aria-hidden="true"></i>
                                                                        <span class="tooltip-text">Short Code to show on Calendar</span>
                                                                    </span>
                                                                </label>
                                                                <input type="text" id="SERVICE_CODE" name="SERVICE_CODE" class="form-control-modern" placeholder="Enter Service Code" required value="<?php echo htmlspecialchars($SERVICE_CODE) ?>">
                                                            </div>

                                                            <!-- Is Chargeable -->
                                                            <?php if (!empty($_GET['id'])) { ?>
                                                                <div class="form-group-modern">
                                                                    <label class="form-label">
                                                                        Is Chargeable?
                                                                        <span class="tooltip-bubble" tabindex="0">
                                                                            <i class="ti-help-alt" aria-hidden="true"></i>
                                                                            <span class="tooltip-text">Is this Service used to create an enrollment and deduct from account upon Service delivery?</span>
                                                                        </span>
                                                                    </label>
                                                                    <div class="radio-group-modern">
                                                                        <label class="radio-item">
                                                                            <input type="radio" name="IS_CHARGEABLE" class="IS_CHARGEABLE" value="1" <?= (($IS_CHARGEABLE == 1) ? 'checked' : '') ?>> Yes
                                                                        </label>
                                                                        <label class="radio-item">
                                                                            <input type="radio" name="IS_CHARGEABLE" class="IS_CHARGEABLE" value="0" <?= (($IS_CHARGEABLE == 0) ? 'checked' : '') ?>> No
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                            <?php } else { ?>
                                                                <div class="form-group-modern">
                                                                    <label class="form-label">
                                                                        Is Chargeable?
                                                                        <span class="tooltip-bubble" tabindex="0">
                                                                            <i class="ti-help-alt" aria-hidden="true"></i>
                                                                            <span class="tooltip-text">Is this Service used to create an enrollment and deduct from account upon Service delivery?</span>
                                                                        </span>
                                                                    </label>
                                                                    <div class="radio-group-modern">
                                                                        <label class="radio-item">
                                                                            <input type="radio" name="IS_CHARGEABLE" class="IS_CHARGEABLE" value="1"> Yes
                                                                        </label>
                                                                        <label class="radio-item">
                                                                            <input type="radio" name="IS_CHARGEABLE" class="IS_CHARGEABLE" value="0" checked> No
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                            <?php } ?>

                                                            <!-- Price -->
                                                            <div class="form-group-modern service_price" style="display: <?= ($IS_CHARGEABLE == 0) ? 'none' : '' ?>">
                                                                <label class="form-label">
                                                                    Price
                                                                    <span class="tooltip-bubble" tabindex="0">
                                                                        <i class="ti-help-alt" aria-hidden="true"></i>
                                                                        <span class="tooltip-text">Amount to charge for this Service</span>
                                                                    </span>
                                                                </label>
                                                                <div class="input-group-modern">
                                                                    <span class="input-group-text"><?= $currency ?></span>
                                                                    <input type="text" id="PRICE" name="PRICE" class="form-control-modern" placeholder="Price" value="<?= htmlspecialchars($PRICE) ?>" required>
                                                                </div>
                                                            </div>

                                                            <!-- Location -->
                                                            <div class="form-group-modern">
                                                                <label class="form-label">
                                                                    Location
                                                                    <span class="tooltip-bubble" tabindex="0">
                                                                        <i class="ti-help-alt" aria-hidden="true"></i>
                                                                        <span class="tooltip-text">Location for this Code to be Active</span>
                                                                    </span>
                                                                </label>
                                                                <select class="form-control-modern PK_LOCATION" name="PK_LOCATION" onchange="selectServiceClass(this)">
                                                                    <?php
                                                                    $row = $db->Execute("SELECT * FROM DOA_LOCATION WHERE PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND ACTIVE = 1 AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
                                                                    while (!$row->EOF) { ?>
                                                                        <option value="<?php echo $row->fields['PK_LOCATION']; ?>" <?= ($PK_LOCATION == $row->fields['PK_LOCATION']) ? 'selected' : '' ?>><?= htmlspecialchars($row->fields['LOCATION_NAME']) ?></option>
                                                                    <?php $row->MoveNext();
                                                                    } ?>
                                                                </select>
                                                            </div>

                                                            <!-- Description -->
                                                            <div class="form-group-modern">
                                                                <label class="form-label">Description</label>
                                                                <textarea class="form-control-modern" rows="3" id="DESCRIPTION" name="DESCRIPTION" placeholder="Enter description"><?php echo htmlspecialchars($DESCRIPTION) ?></textarea>
                                                            </div>

                                                            <!-- Active Status -->
                                                            <?php if (!empty($_GET['id'])) { ?>
                                                                <div class="form-group-modern">
                                                                    <label class="form-label">Active</label>
                                                                    <div class="radio-group-modern">
                                                                        <label class="radio-item">
                                                                            <input type="radio" name="ACTIVE" id="ACTIVE_YES" value="1" <?php if ($ACTIVE == 1) echo 'checked="checked"'; ?>> Yes
                                                                        </label>
                                                                        <label class="radio-item">
                                                                            <input type="radio" name="ACTIVE" id="ACTIVE_NO" value="0" <?php if ($ACTIVE == 0) echo 'checked="checked"'; ?>> No
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                            <?php } ?>
                                                        </div>

                                                        <!-- Right Column - Options -->
                                                        <div class="options-section">
                                                            <div class="options-title">Options</div>

                                                            <!-- Service Class -->
                                                            <div class="form-group-modern">
                                                                <label class="form-label">
                                                                    Service Class
                                                                    <span class="tooltip-bubble" tabindex="0">
                                                                        <i class="ti-help-alt" aria-hidden="true"></i>
                                                                        <span class="tooltip-text">How is this Service to be enrolled</span>
                                                                    </span>
                                                                </label>
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

                                                            <!-- Misc Type -->
                                                            <div class="form-group-modern service_class_type" id="misc_type_div" style="display: <?= ($PK_SERVICE_CLASS == 5) ? '' : 'none' ?>">
                                                                <label class="form-label">Misc. Type</label>
                                                                <select class="form-control-modern MISC_TYPE" name="MISC_TYPE">
                                                                    <option value="">Select</option>
                                                                    <option value="GENERAL" <?= ($MISC_TYPE == 'GENERAL') ? 'selected' : '' ?>>General</option>
                                                                    <option value="DOR" <?= ($MISC_TYPE == 'DOR') ? 'selected' : '' ?>>DOR</option>
                                                                    <option value="SHOWCASE" <?= ($MISC_TYPE == 'SHOWCASE') ? 'selected' : '' ?>>Showcase</option>
                                                                </select>
                                                            </div>

                                                            <!-- Scheduling Code -->
                                                            <div class="form-group-modern">
                                                                <label class="form-label">
                                                                    Scheduling Code
                                                                    <span class="tooltip-bubble" tabindex="0">
                                                                        <i class="ti-help-alt" aria-hidden="true"></i>
                                                                        <span class="tooltip-text">Scheduling Codes to show and to be allowed to book for this</span>
                                                                    </span>
                                                                </label>
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
                                                                    $scheduling_code = $db_account->Execute("SELECT * FROM `DOA_SCHEDULING_CODE` WHERE PK_LOCATION IN (" . $DEFAULT_LOCATION_ID . ") AND `ACTIVE` = 1");
                                                                    while (!$scheduling_code->EOF) { ?>
                                                                        <option value="<?= $scheduling_code->fields['PK_SCHEDULING_CODE'] ?>" <?= in_array($scheduling_code->fields['PK_SCHEDULING_CODE'], $selected_scheduling_code) ? "selected" : "" ?>><?= htmlspecialchars($scheduling_code->fields['SCHEDULING_NAME'] . ' (' . $scheduling_code->fields['SCHEDULING_CODE'] . ')') ?></option>
                                                                    <?php $scheduling_code->MoveNext();
                                                                    } ?>
                                                                </select>
                                                            </div>

                                                            <!-- Is Group -->
                                                            <div class="form-group-modern">
                                                                <label class="form-label">
                                                                    Is Group?
                                                                    <span class="tooltip-bubble" tabindex="0">
                                                                        <i class="ti-help-alt" aria-hidden="true"></i>
                                                                        <span class="tooltip-text">Is this Code a Group Class and allow Multiple Clients in it?</span>
                                                                    </span>
                                                                </label>
                                                                <div class="radio-group-modern">
                                                                    <label class="radio-item">
                                                                        <input type="radio" name="IS_GROUP" class="IS_GROUP" value="1" <?= (($IS_GROUP == 1) ? 'checked' : '') ?>> Yes
                                                                    </label>
                                                                    <label class="radio-item">
                                                                        <input type="radio" name="IS_GROUP" class="IS_GROUP" value="0" <?= (($IS_GROUP == 0) ? 'checked' : '') ?>> No
                                                                    </label>
                                                                </div>
                                                            </div>

                                                            <!-- Capacity -->
                                                            <div class="form-group-modern capacity_div" style="display: <?= (($IS_GROUP == 1) ? '' : 'none') ?>">
                                                                <label class="form-label">Capacity</label>
                                                                <input type="number" class="form-control-modern" name="CAPACITY" id="CAPACITY" placeholder="Enter capacity" value="<?= htmlspecialchars($CAPACITY) ?>">
                                                            </div>

                                                            <!-- Is Sundry -->
                                                            <div class="form-group-modern">
                                                                <label class="form-label">
                                                                    Is Sundry?
                                                                    <span class="tooltip-bubble" tabindex="0">
                                                                        <i class="ti-help-alt" aria-hidden="true"></i>
                                                                        <span class="tooltip-text">Is this a Product not a Service?</span>
                                                                    </span>
                                                                </label>
                                                                <div class="radio-group-modern">
                                                                    <label class="radio-item">
                                                                        <input type="radio" name="IS_SUNDRY" class="IS_SUNDRY" value="1" <?= (($IS_SUNDRY == 1) ? 'checked' : '') ?>> Yes
                                                                    </label>
                                                                    <label class="radio-item">
                                                                        <input type="radio" name="IS_SUNDRY" class="IS_SUNDRY" value="0" <?= (($IS_SUNDRY == 0) ? 'checked' : '') ?>> No
                                                                    </label>
                                                                </div>
                                                            </div>

                                                            <!-- Count on Calendar -->
                                                            <div class="form-group-modern">
                                                                <label class="form-label">
                                                                    Count on Calendar?
                                                                    <span class="tooltip-bubble" tabindex="0">
                                                                        <i class="ti-help-alt" aria-hidden="true"></i>
                                                                        <span class="tooltip-text">Is this Service to be counted on Calendar Counter?</span>
                                                                    </span>
                                                                </label>
                                                                <div class="radio-group-modern">
                                                                    <label class="radio-item">
                                                                        <input type="radio" name="COUNT_ON_CALENDAR" class="COUNT_ON_CALENDAR" value="1" <?= (($COUNT_ON_CALENDAR == 1) ? 'checked' : '') ?>> Yes
                                                                    </label>
                                                                    <label class="radio-item">
                                                                        <input type="radio" name="COUNT_ON_CALENDAR" class="COUNT_ON_CALENDAR" value="0" <?= (($COUNT_ON_CALENDAR == 0) ? 'checked' : '') ?>> No
                                                                    </label>
                                                                </div>
                                                            </div>

                                                            <!-- Sort Order -->
                                                            <div class="form-group-modern">
                                                                <label class="form-label">
                                                                    Sort Order
                                                                    <span class="tooltip-bubble" tabindex="0">
                                                                        <i class="ti-help-alt" aria-hidden="true"></i>
                                                                        <span class="tooltip-text">What order is this Schedule to appear in Drop Down Menus</span>
                                                                    </span>
                                                                </label>
                                                                <input type="text" id="SORT_ORDER" name="SORT_ORDER" class="form-control-modern" placeholder="Enter Sort Order" value="<?php echo htmlspecialchars($SORT_ORDER) ?>">
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="form-actions">
                                                        <button type="submit" class="btn-modern btn-modern-primary">
                                                            <i class="fas fa-save"></i> Continue
                                                        </button>
                                                        <button type="button" id="cancel_button" class="btn-modern btn-modern-secondary">
                                                            <i class="fas fa-times"></i> Cancel
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>

                                            <!-- Service Document Tab -->
                                            <div class="tab-pane-modern" id="service_document" role="tabpanel">
                                                <form method="post" action="" enctype="multipart/form-data">
                                                    <div id="append_service_document">
                                                        <?php
                                                        if (!empty($_GET['id'])) {
                                                            $service_document = $db_account->Execute("SELECT * FROM DOA_SERVICE_DOCUMENTS WHERE PK_SERVICE_MASTER = '$_GET[id]'");
                                                            while (!$service_document->EOF) { ?>
                                                                <div class="document-row">
                                                                    <div class="form-group-modern">
                                                                        <label class="form-label">Location</label>
                                                                        <select class="form-control-modern PK_LOCATION" name="PK_LOCATION[]">
                                                                            <option value="">Select Location</option>
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
                                                                        <?php if (!empty($service_document->fields['FILE_PATH'])): ?>
                                                                            <a target="_blank" href="<?= htmlspecialchars($service_document->fields['FILE_PATH']) ?>" style="color: var(--primary-color); font-size: 13px; margin-top: 4px; display: inline-block;">View Current File</a>
                                                                        <?php endif; ?>
                                                                        <input type="hidden" name="FILE_PATH_URL[]" value="<?= htmlspecialchars($service_document->fields['FILE_PATH']) ?>">
                                                                    </div>
                                                                    <button type="button" class="btn-modern btn-modern-danger btn-modern-sm remove-btn" onclick="removeServiceDocument(this);">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                </div>
                                                            <?php $service_document->MoveNext();
                                                            } ?>
                                                        <?php } else { ?>
                                                            <div class="document-row">
                                                                <div class="form-group-modern">
                                                                    <label class="form-label">Location</label>
                                                                    <select class="form-control-modern PK_LOCATION" name="PK_LOCATION[]">
                                                                        <option value="">Select Location</option>
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
                                                                <button type="button" class="btn-modern btn-modern-danger btn-modern-sm remove-btn" onclick="removeServiceDocument(this);">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </div>
                                                        <?php } ?>
                                                    </div>

                                                    <div style="margin: 16px 0;">
                                                        <button type="button" class="btn-modern btn-modern-secondary btn-modern-sm" onclick="addServiceDocument();">
                                                            <i class="fas fa-plus"></i> Add More
                                                        </button>
                                                    </div>

                                                    <div class="form-actions">
                                                        <button type="submit" class="btn-modern btn-modern-primary">
                                                            <i class="fas fa-save"></i> Submit
                                                        </button>
                                                        <button type="button" onclick="window.location.href='all_service_codes.php'" class="btn-modern btn-modern-secondary">
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
                                        <h5><?= $help_title ?></h5>
                                        <p><?= $help_description ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                </div>
            <?php } ?>
            </div>
        </div>
    </div>
    <?php require_once('../includes/footer.php'); ?>
    <script src="../assets/sumoselect/jquery.sumoselect.min.js"></script>
    <script>
        // Tab switching
        document.querySelectorAll('.tab-item').forEach(tab => {
            tab.addEventListener('click', function() {
                if (this.classList.contains('disabled')) return;
                document.querySelectorAll('.tab-item').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-pane-modern').forEach(p => p.classList.remove('active'));

                this.classList.add('active');
                document.getElementById(this.dataset.tab).classList.add('active');
            });
        });

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
                $("#PRICE").attr("required", "required");
            } else {
                $('.service_price').slideUp();
                $('#PRICE').removeAttr('required');
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
                        <option value="">Select Location</option>
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
                <button type="button" class="btn-modern btn-modern-danger btn-modern-sm remove-btn" onclick="removeServiceDocument(this);">
                    <i class="fas fa-trash"></i>
                </button>
            </div>`);
        }

        function removeServiceDocument(param) {
            $(param).closest('.document-row').remove();
        }

        $(document).on('click', '#cancel_button', function() {
            window.location.href = 'all_service_codes.php'
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
                        window.location.href = 'all_service_codes.php';
                    } else {
                        window.location.href = 'all_service_codes.php';
                    }
                }
            });
        });
    </script>
</body>

</html>