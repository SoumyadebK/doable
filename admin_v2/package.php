<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];

if (empty($_GET['id']))
    $title = "Create New Package";
else
    $title = "Edit Package";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

if (empty($_GET['id'])) {
    $PACKAGE_NAME = '';
    $PACKAGE_DESCRIPTION = '';
    $PK_LOCATION = '';
    $SORT_ORDER = '';
    $EXPIRY_DATE = '';
    $ACTIVE = '';
    $CHATBOT_ENABLED = 0;
} else {
    $res = $db_account->Execute("SELECT * FROM `DOA_PACKAGE` WHERE `PK_PACKAGE` = '$_GET[id]'");

    if ($res->RecordCount() == 0) {
        header("location:all_packages.php");
        exit;
    }

    $PACKAGE_NAME = $res->fields['PACKAGE_NAME'];
    $PACKAGE_DESCRIPTION = $res->fields['PACKAGE_DESCRIPTION'];
    $PK_LOCATION = $res->fields['PK_LOCATION'];
    $SORT_ORDER = $res->fields['SORT_ORDER'];
    $EXPIRY_DATE = $res->fields['EXPIRY_DATE'];
    $ACTIVE = $res->fields['ACTIVE'];
    $CHATBOT_ENABLED = isset($res->fields['CHATBOT_ENABLED']) ? $res->fields['CHATBOT_ENABLED'] : 0;
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

        .container-fluid {
            padding: 16px !important;
        }
    }

    /* Form */
    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 20px 24px;
    }

    @media (max-width: 992px) {
        .form-grid {
            grid-template-columns: 1fr 1fr;
        }
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

    .form-control-modern:read-only {
        background: var(--gray-50);
        cursor: default;
    }

    select.form-control-modern {
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236B7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 12px center;
        padding-right: 36px;
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
        width: 18px;
        height: 18px;
        cursor: pointer;
        flex-shrink: 0;
    }

    /* Toggle Switch Styles */
    .toggle-switch {
        position: relative;
        display: inline-block;
        width: 50px;
        height: 26px;
        flex-shrink: 0;
    }

    .toggle-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .toggle-slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        transition: .3s;
        border-radius: 26px;
    }

    .toggle-slider:before {
        position: absolute;
        content: "";
        height: 20px;
        width: 20px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: .3s;
        border-radius: 50%;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    .toggle-switch input:checked+.toggle-slider {
        background-color: var(--primary-color);
    }

    .toggle-switch input:checked+.toggle-slider:before {
        transform: translateX(24px);
    }

    .toggle-switch input:disabled+.toggle-slider {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .toggle-wrapper {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 4px 0;
    }

    .toggle-wrapper .toggle-label {
        font-size: 13px;
        color: var(--gray-600);
        font-weight: 500;
        min-width: 70px;
    }

    .toggle-wrapper .toggle-status {
        font-size: 12px;
        color: var(--gray-500);
        min-width: 50px;
        font-weight: 500;
    }

    .toggle-wrapper .toggle-status.active {
        color: var(--primary-color);
    }

    .toggle-wrapper .toggle-status.inactive {
        color: var(--gray-400);
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

    /* Service Table */
    .service-table-wrapper {
        overflow-x: auto;
        margin-top: 8px;
        border: 1px solid var(--gray-200);
        border-radius: var(--radius-sm);
        padding: 16px 16px 8px 16px;
        background: var(--gray-50);
    }

    .service-table-wrapper .table-header {
        display: grid;
        grid-template-columns: 1.8fr 1fr 1.5fr 1fr 1fr 1fr 1fr 1fr 1fr 1fr 0.5fr;
        gap: 8px;
        padding: 8px 4px 12px 4px;
        font-weight: 600;
        font-size: 12px;
        color: var(--gray-600);
        text-transform: uppercase;
        letter-spacing: 0.03em;
        border-bottom: 2px solid var(--gray-200);
        align-items: center;
    }

    .service-table-wrapper .service-row {
        display: grid;
        grid-template-columns: 1.8fr 1fr 1.5fr 1fr 1fr 1fr 1fr 1fr 1fr 1fr 0.5fr;
        gap: 8px;
        padding: 8px 4px;
        border-bottom: 1px solid var(--gray-100);
        align-items: center;
    }

    .service-table-wrapper .service-row:last-child {
        border-bottom: none;
    }

    .service-table-wrapper .service-row .form-control-modern {
        padding: 6px 10px;
        font-size: 13px;
    }

    .service-table-wrapper .service-row .remove-btn {
        color: var(--danger-color);
        font-size: 18px;
        cursor: pointer;
        padding: 4px 8px;
        border: none;
        background: none;
        transition: transform 0.2s;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .service-table-wrapper .service-row .remove-btn:hover {
        transform: scale(1.2);
    }

    .service-table-wrapper .service-row .toggle-switch {
        width: 40px;
        height: 22px;
    }

    .service-table-wrapper .service-row .toggle-slider:before {
        height: 16px;
        width: 16px;
        left: 3px;
        bottom: 3px;
    }

    .service-table-wrapper .service-row .toggle-switch input:checked+.toggle-slider:before {
        transform: translateX(18px);
    }

    @media (max-width: 1200px) {

        .service-table-wrapper .table-header,
        .service-table-wrapper .service-row {
            grid-template-columns: 1.5fr 1fr 1.5fr 1fr 0.8fr 0.8fr 0.8fr 0.8fr 0.8fr 0.8fr 0.5fr;
            font-size: 11px;
        }
    }

    @media (max-width: 992px) {

        .service-table-wrapper .table-header,
        .service-table-wrapper .service-row {
            grid-template-columns: 1.5fr 1fr 1.5fr 1fr 1fr 1fr 1fr 1fr 1fr 1fr 0.5fr;
            font-size: 12px;
        }
    }

    @media (max-width: 768px) {
        .service-table-wrapper {
            padding: 8px;
        }

        .service-table-wrapper .table-header {
            display: none;
        }

        .service-table-wrapper .service-row {
            grid-template-columns: 1fr;
            gap: 6px;
            padding: 12px;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-sm);
            margin-bottom: 8px;
            background: #fff;
        }

        .service-table-wrapper .service-row .form-label-sm {
            display: block;
            font-size: 11px;
            font-weight: 600;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.03em;
            margin-bottom: 2px;
        }

        .service-table-wrapper .service-row .remove-btn {
            justify-self: end;
            margin-top: 4px;
        }

        .service-table-wrapper .service-row .toggle-wrapper {
            justify-content: flex-start;
        }
    }

    .form-label-sm {
        display: none;
    }

    @media (max-width: 768px) {
        .form-label-sm {
            display: block;
        }
    }

    /* Expiry & Add More Row */
    .action-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 16px;
        padding-top: 16px;
        border-top: 1px solid var(--gray-200);
    }

    .action-row .expiry-wrapper {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .action-row .expiry-wrapper .form-label {
        font-size: 13px;
        font-weight: 500;
        color: var(--gray-700);
        margin: 0;
    }

    .action-row .expiry-wrapper select {
        min-width: 150px;
    }

    @media (max-width: 768px) {
        .action-row {
            flex-direction: column;
            align-items: stretch;
        }

        .action-row .expiry-wrapper {
            flex-direction: column;
            align-items: stretch;
        }

        .action-row .expiry-wrapper select {
            width: 100%;
        }
    }

    .form-helper {
        font-size: 12px;
        color: var(--gray-400);
        margin-top: 4px;
    }

    .chatbot-section {
        background: var(--gray-50);
        border-radius: var(--radius-sm);
        padding: 16px 20px;
        margin: 16px 0 8px 0;
        border: 1px solid var(--gray-200);
    }

    .chatbot-section .section-title {
        font-size: 13px;
        font-weight: 600;
        color: var(--gray-700);
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .chatbot-section .section-title i {
        color: var(--primary-color);
    }

    /* Right alignment for toggle */
    .chatbot-toggle-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        width: 100%;
    }

    .chatbot-toggle-row .left-content {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .chatbot-toggle-row .right-content {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-left: auto;
        flex-wrap: wrap;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .chatbot-toggle-row {
            flex-direction: column;
            align-items: flex-start;
        }

        .chatbot-toggle-row .right-content {
            margin-left: 0;
            width: 100%;
            flex-wrap: wrap;
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
                <div class="row g-4">
                    <!-- Sidebar -->
                    <div class="col-12 col-md-4 col-xl-2">
                        <?php include 'layout/setup_sidebar.php'; ?>
                    </div>

                    <!-- Main Form -->
                    <div class="col-12 col-md-8 col-xl-10">
                        <!-- Main Content -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card-modern">
                                    <div class="card-header">
                                        <h5>
                                            <i class="bi bi-box"></i>
                                            <?= !empty($_GET['id']) ? 'Create New Package' : 'Add Package' ?>
                                        </h5>
                                        <?php if (!empty($_GET['id'])): ?>
                                            <span class="status-indicator <?= ($ACTIVE == 1) ? 'active' : 'inactive' ?>">
                                                <i class="fas fa-circle"></i>
                                                <?= ($ACTIVE == 1) ? 'Active' : 'Inactive' ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-body">
                                        <form class="form-material form-horizontal" id="package_info_form">
                                            <input type="hidden" name="FUNCTION_NAME" value="savePackageInfoData">
                                            <input type="hidden" name="PK_PACKAGE" class="PK_PACKAGE" value="<?= (empty($_GET['id'])) ? '' : $_GET['id'] ?>">

                                            <!-- Package Details -->
                                            <div class="form-grid">
                                                <div class="form-group-modern">
                                                    <label class="form-label">Package Name <span class="required">*</span></label>
                                                    <input type="text" id="PK_PACKAGE" name="PACKAGE_NAME" class="form-control-modern" placeholder="Enter Package name" required value="<?php echo htmlspecialchars($PACKAGE_NAME) ?>">
                                                    <div class="form-helper">A unique name for this package</div>
                                                </div>

                                                <div class="form-group-modern">
                                                    <label class="form-label">Location</label>
                                                    <select class="form-control-modern PK_LOCATION" name="PK_LOCATION">
                                                        <?php
                                                        $row = $db->Execute("SELECT * FROM DOA_LOCATION WHERE PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND ACTIVE = 1 AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
                                                        while (!$row->EOF) { ?>
                                                            <option value="<?php echo $row->fields['PK_LOCATION']; ?>" <?= ($PK_LOCATION == $row->fields['PK_LOCATION']) ? 'selected' : '' ?>><?= htmlspecialchars($row->fields['LOCATION_NAME']) ?></option>
                                                        <?php $row->MoveNext();
                                                        } ?>
                                                    </select>
                                                    <div class="form-helper">Select the location for this package</div>
                                                </div>

                                                <div class="form-group-modern">
                                                    <label class="form-label">Sort Order</label>
                                                    <input type="text" id="SORT_ORDER" name="SORT_ORDER" class="form-control-modern" placeholder="Enter Sort Order" value="<?php echo htmlspecialchars($SORT_ORDER) ?>">
                                                    <div class="form-helper">Numerical order for sorting</div>
                                                </div>
                                            </div>

                                            <div class="form-grid-modern mt-3">
                                                <div class="form-group-modern">
                                                    <label class="form-label">Package Description</label>
                                                    <textarea id="PACKAGE_DESCRIPTION" name="PACKAGE_DESCRIPTION" class="form-control-modern" placeholder="Enter Package Description"><?php echo htmlspecialchars($PACKAGE_DESCRIPTION) ?></textarea>
                                                    <div class="form-helper">A brief description of this package</div>
                                                </div>
                                            </div>

                                            <!-- Chatbot Toggle for Package -->
                                            <!-- <div class="chatbot-section">
                                                <div class="section-title">
                                                    <i class="bi bi-robot"></i> Chatbot Configuration
                                                </div>
                                                <div class="chatbot-toggle-row">
                                                    <div class="toggle-wrapper">
                                                        <span class="toggle-label">Package Chatbot</span>
                                                        <label class="toggle-switch">
                                                            <input type="checkbox" name="PACKAGE_CHATBOT_ENABLED" id="PACKAGE_CHATBOT_ENABLED" value="1" <?= ($CHATBOT_ENABLED == 1) ? 'checked' : '' ?> onchange="updateToggleStatus(this, 'package-status')">
                                                            <span class="toggle-slider"></span>
                                                        </label>
                                                        <span class="toggle-status <?= ($CHATBOT_ENABLED == 1) ? 'active' : 'inactive' ?>" id="package-status">
                                                            <?= ($CHATBOT_ENABLED == 1) ? 'Enabled' : 'Disabled' ?>
                                                        </span>
                                                    </div>
                                                    <span class="form-helper" style="margin:0;">Enable AI chatbot for this package</span>
                                                </div>
                                            </div> -->

                                            <!-- Services Table -->
                                            <div style="margin-top: 24px;">
                                                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; width: 100%;">
                                                    <label class="form-label" style="font-size: 14px; font-weight: 600; color: var(--gray-700); margin:0;">Package Services</label>

                                                    <div style="display: flex; align-items: center; gap: 12px; margin-left: auto;">
                                                        <div class="toggle-wrapper" style="padding:0;">
                                                            <span class="toggle-label">AI-Concierge</span>
                                                            <label class="toggle-switch">
                                                                <input type="checkbox" name="PACKAGE_CHATBOT_ENABLED" id="PACKAGE_CHATBOT_ENABLED" value="1" <?= ($CHATBOT_ENABLED == 1) ? 'checked' : '' ?> onchange="updateToggleStatus(this, 'package-status')">
                                                                <span class="toggle-slider"></span>
                                                            </label>
                                                            <span class="toggle-status <?= ($CHATBOT_ENABLED == 1) ? 'active' : 'inactive' ?>" id="package-status">
                                                                <?= ($CHATBOT_ENABLED == 1) ? 'On' : 'Off' ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="service-table-wrapper">
                                                    <!-- Table Header -->
                                                    <div class="table-header">
                                                        <span>Services</span>
                                                        <span>Service Codes</span>
                                                        <span>Service Details</span>
                                                        <span>Sessions</span>
                                                        <span>Price/Session</span>
                                                        <span>Total</span>
                                                        <span>Discount Type</span>
                                                        <span>Discount</span>
                                                        <span>Final Amount</span>
                                                        <span>Chatbot</span>
                                                        <span></span>
                                                    </div>

                                                    <!-- Service Rows -->
                                                    <div id="append_service_div">
                                                        <?php
                                                        if (!empty($_GET['id'])) {
                                                            $package_service_data = $db_account->Execute("SELECT * FROM DOA_PACKAGE_SERVICE WHERE PK_PACKAGE = '$_GET[id]'");
                                                            while (!$package_service_data->EOF) {
                                                                $service_chatbot = isset($package_service_data->fields['CHATBOT_ENABLED']) ? $package_service_data->fields['CHATBOT_ENABLED'] : 0;
                                                        ?>
                                                                <div class="service-row">
                                                                    <div>
                                                                        <span class="form-label-sm">Services</span>
                                                                        <select class="form-control-modern PK_SERVICE_MASTER" name="PK_SERVICE_MASTER[]" onchange="selectThisService(this)">
                                                                            <option value="">Select Service</option>
                                                                            <?php
                                                                            $row = $db_account->Execute("SELECT * FROM `DOA_SERVICE_MASTER` WHERE PK_LOCATION IN (" . $DEFAULT_LOCATION_ID . ") AND ACTIVE = 1 AND IS_DELETED = 0");
                                                                            while (!$row->EOF) { ?>
                                                                                <option value="<?php echo $row->fields['PK_SERVICE_MASTER']; ?>" <?= ($row->fields['PK_SERVICE_MASTER'] == $package_service_data->fields['PK_SERVICE_MASTER']) ? 'selected' : '' ?>><?= htmlspecialchars($row->fields['SERVICE_NAME']) ?></option>
                                                                            <?php $row->MoveNext();
                                                                            } ?>
                                                                        </select>
                                                                    </div>
                                                                    <div>
                                                                        <span class="form-label-sm">Service Codes</span>
                                                                        <select class="form-control-modern PK_SERVICE_CODE" name="PK_SERVICE_CODE[]" onchange="selectThisServiceCode(this)">
                                                                            <?php
                                                                            $row = $db_account->Execute("SELECT * FROM `DOA_SERVICE_CODE` WHERE `PK_SERVICE_MASTER` = " . $package_service_data->fields['PK_SERVICE_MASTER']);
                                                                            while (!$row->EOF) { ?>
                                                                                <option value="<?php echo $row->fields['PK_SERVICE_CODE']; ?>" data-details="<?= htmlspecialchars($row->fields['DESCRIPTION']) ?>" data-price="<?= $row->fields['PRICE'] ?>" <?= ($row->fields['PK_SERVICE_CODE'] == $package_service_data->fields['PK_SERVICE_CODE']) ? 'selected' : '' ?>><?= htmlspecialchars($row->fields['SERVICE_CODE']) ?></option>
                                                                            <?php $row->MoveNext();
                                                                            } ?>
                                                                        </select>
                                                                    </div>
                                                                    <div>
                                                                        <span class="form-label-sm">Service Details</span>
                                                                        <input type="text" class="form-control-modern SERVICE_DETAILS" name="SERVICE_DETAILS[]" value="<?= htmlspecialchars($package_service_data->fields['SERVICE_DETAILS']) ?>">
                                                                    </div>
                                                                    <div>
                                                                        <span class="form-label-sm">Sessions</span>
                                                                        <input type="text" class="form-control-modern NUMBER_OF_SESSION" name="NUMBER_OF_SESSION[]" value="<?= htmlspecialchars($package_service_data->fields['NUMBER_OF_SESSION']) ?>" onkeyup="calculateServiceTotal(this)">
                                                                    </div>
                                                                    <div>
                                                                        <span class="form-label-sm">Price/Session</span>
                                                                        <input type="text" class="form-control-modern PRICE_PER_SESSION" name="PRICE_PER_SESSION[]" value="<?= htmlspecialchars($package_service_data->fields['PRICE_PER_SESSION']) ?>" onkeyup="calculateServiceTotal(this);">
                                                                    </div>
                                                                    <div>
                                                                        <span class="form-label-sm">Total</span>
                                                                        <input type="text" class="form-control-modern TOTAL" name="TOTAL[]" value="<?= htmlspecialchars($package_service_data->fields['TOTAL']) ?>" readonly>
                                                                    </div>
                                                                    <div>
                                                                        <span class="form-label-sm">Discount Type</span>
                                                                        <select class="form-control-modern DISCOUNT_TYPE" name="DISCOUNT_TYPE[]" onchange="calculateServiceTotal(this)">
                                                                            <option value="">Select</option>
                                                                            <option value="1" <?= ($package_service_data->fields['DISCOUNT_TYPE'] == 1) ? 'selected' : '' ?>>Fixed</option>
                                                                            <option value="2" <?= ($package_service_data->fields['DISCOUNT_TYPE'] == 2) ? 'selected' : '' ?>>Percent</option>
                                                                        </select>
                                                                    </div>
                                                                    <div>
                                                                        <span class="form-label-sm">Discount</span>
                                                                        <input type="text" class="form-control-modern DISCOUNT" name="DISCOUNT[]" value="<?= htmlspecialchars($package_service_data->fields['DISCOUNT']) ?>" onkeyup="calculateServiceTotal(this)">
                                                                    </div>
                                                                    <div>
                                                                        <span class="form-label-sm">Final Amount</span>
                                                                        <input type="text" class="form-control-modern FINAL_AMOUNT" name="FINAL_AMOUNT[]" value="<?= htmlspecialchars($package_service_data->fields['FINAL_AMOUNT']) ?>" readonly>
                                                                    </div>
                                                                    <div>
                                                                        <span class="form-label-sm">Chatbot</span>
                                                                        <div class="toggle-wrapper" style="padding:0;">
                                                                            <label class="toggle-switch">
                                                                                <input type="checkbox" class="service-chatbot" name="SERVICE_CHATBOT_ENABLED[]" value="1" <?= ($service_chatbot == 1) ? 'checked' : '' ?> onchange="updateToggleStatus(this, 'service-status-' + this.dataset.rowIndex)">
                                                                                <span class="toggle-slider"></span>
                                                                            </label>
                                                                            <span class="toggle-status <?= ($service_chatbot == 1) ? 'active' : 'inactive' ?>" id="service-status-<?= $package_service_data->fields['PK_PACKAGE_SERVICE'] ?>">
                                                                                <?= ($service_chatbot == 1) ? 'On' : 'Off' ?>
                                                                            </span>
                                                                        </div>
                                                                        <input type="hidden" class="service-row-index" value="<?= $package_service_data->fields['PK_PACKAGE_SERVICE'] ?>">
                                                                    </div>
                                                                    <div>
                                                                        <button type="button" class="remove-btn" onclick="removeThis(this);"><i class="fas fa-trash"></i></button>
                                                                    </div>
                                                                </div>
                                                            <?php $package_service_data->MoveNext();
                                                            } ?>
                                                        <?php } else { ?>
                                                            <div class="service-row">
                                                                <div>
                                                                    <span class="form-label-sm">Services</span>
                                                                    <select class="form-control-modern PK_SERVICE_MASTER" name="PK_SERVICE_MASTER[]" onchange="selectThisService(this)">
                                                                        <option value="">Select Service</option>
                                                                        <?php
                                                                        $row = $db_account->Execute("SELECT * FROM `DOA_SERVICE_MASTER` WHERE PK_LOCATION IN (" . $DEFAULT_LOCATION_ID . ") AND ACTIVE = 1 AND IS_DELETED = 0");
                                                                        while (!$row->EOF) { ?>
                                                                            <option value="<?php echo $row->fields['PK_SERVICE_MASTER']; ?>"><?= htmlspecialchars($row->fields['SERVICE_NAME']) ?></option>
                                                                        <?php $row->MoveNext();
                                                                        } ?>
                                                                    </select>
                                                                </div>
                                                                <div>
                                                                    <span class="form-label-sm">Service Codes</span>
                                                                    <select class="form-control-modern PK_SERVICE_CODE" name="PK_SERVICE_CODE[]" onchange="selectThisServiceCode(this)">
                                                                        <option value="">Select</option>
                                                                    </select>
                                                                </div>
                                                                <div>
                                                                    <span class="form-label-sm">Service Details</span>
                                                                    <input type="text" class="form-control-modern SERVICE_DETAILS" name="SERVICE_DETAILS[]">
                                                                </div>
                                                                <div>
                                                                    <span class="form-label-sm">Sessions</span>
                                                                    <input type="text" class="form-control-modern NUMBER_OF_SESSION" name="NUMBER_OF_SESSION[]" onkeyup="calculateServiceTotal(this)">
                                                                </div>
                                                                <div>
                                                                    <span class="form-label-sm">Price/Session</span>
                                                                    <input type="text" class="form-control-modern PRICE_PER_SESSION" name="PRICE_PER_SESSION[]" onkeyup="calculateServiceTotal(this);">
                                                                </div>
                                                                <div>
                                                                    <span class="form-label-sm">Total</span>
                                                                    <input type="text" class="form-control-modern TOTAL" name="TOTAL[]" readonly>
                                                                </div>
                                                                <div>
                                                                    <span class="form-label-sm">Discount Type</span>
                                                                    <select class="form-control-modern DISCOUNT_TYPE" name="DISCOUNT_TYPE[]" onchange="calculateServiceTotal(this)">
                                                                        <option value="">Select</option>
                                                                        <option value="1">Fixed</option>
                                                                        <option value="2">Percent</option>
                                                                    </select>
                                                                </div>
                                                                <div>
                                                                    <span class="form-label-sm">Discount</span>
                                                                    <input type="text" class="form-control-modern DISCOUNT" name="DISCOUNT[]" onkeyup="calculateServiceTotal(this)">
                                                                </div>
                                                                <div>
                                                                    <span class="form-label-sm">Final Amount</span>
                                                                    <input type="text" class="form-control-modern FINAL_AMOUNT" name="FINAL_AMOUNT[]" readonly>
                                                                </div>
                                                                <div>
                                                                    <span class="form-label-sm">Chatbot</span>
                                                                    <div class="toggle-wrapper" style="padding:0;">
                                                                        <label class="toggle-switch">
                                                                            <input type="checkbox" class="service-chatbot" name="SERVICE_CHATBOT_ENABLED[]" value="1" onchange="updateToggleStatus(this, 'service-status-' + this.dataset.rowIndex)">
                                                                            <span class="toggle-slider"></span>
                                                                        </label>
                                                                        <span class="toggle-status inactive" id="service-status-0">Off</span>
                                                                    </div>
                                                                    <input type="hidden" class="service-row-index" value="0">
                                                                </div>
                                                                <div>
                                                                    <button type="button" class="remove-btn" onclick="removeThis(this);"><i class="fas fa-trash"></i></button>
                                                                </div>
                                                            </div>
                                                        <?php } ?>
                                                    </div>
                                                </div>

                                                <!-- Action Row -->
                                                <div class="action-row">
                                                    <div class="expiry-wrapper">
                                                        <label class="form-label">Expiration Date</label>
                                                        <select class="form-control-modern" name="EXPIRY_DATE" id="EXPIRY_DATE">
                                                            <option value="">Select Expiration Date</option>
                                                            <option value="30" <?= ($EXPIRY_DATE == 30) ? 'selected' : '' ?>>30 days</option>
                                                            <option value="60" <?= ($EXPIRY_DATE == 60) ? 'selected' : '' ?>>60 days</option>
                                                            <option value="90" <?= ($EXPIRY_DATE == 90) ? 'selected' : '' ?>>90 days</option>
                                                            <option value="180" <?= ($EXPIRY_DATE == 180) ? 'selected' : '' ?>>180 days</option>
                                                            <option value="365" <?= ($EXPIRY_DATE == 365) ? 'selected' : '' ?>>365 days</option>
                                                        </select>
                                                    </div>
                                                    <button type="button" class="btn-modern btn-modern-secondary btn-modern-sm" onclick="addMoreServices();">
                                                        <i class="fas fa-plus"></i> Add More
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Active Status -->
                                            <?php if (!empty($_GET['id'])) { ?>
                                                <div class="form-group-modern" style="margin-top: 24px;">
                                                    <label class="form-label">Active</label>
                                                    <div class="radio-group-modern">
                                                        <label class="radio-item">
                                                            <input type="radio" name="ACTIVE" id="ACTIVE_YES" value="1" <?php if ($ACTIVE == 1) echo 'checked="checked"'; ?>> Active
                                                        </label>
                                                        <label class="radio-item">
                                                            <input type="radio" name="ACTIVE" id="ACTIVE_NO" value="0" <?php if ($ACTIVE == 0) echo 'checked="checked"'; ?>> Inactive
                                                        </label>
                                                    </div>
                                                    <div class="form-helper">Set the status of this package</div>
                                                </div>
                                            <?php } ?>

                                            <!-- Form Actions -->
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
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php require_once('../includes/footer.php'); ?>
    <script src="../assets/sumoselect/jquery.sumoselect.min.js"></script>

    <script>
        let rowCounter = <?= !empty($_GET['id']) ? $package_service_data->RecordCount() : 0 ?>;

        function updateToggleStatus(toggle, statusId) {
            let statusSpan = document.getElementById(statusId);
            if (toggle.checked) {
                statusSpan.textContent = toggle.closest('.toggle-wrapper') ? 'On' : 'Enabled';
                statusSpan.className = 'toggle-status active';
            } else {
                statusSpan.textContent = toggle.closest('.toggle-wrapper') ? 'Off' : 'Disabled';
                statusSpan.className = 'toggle-status inactive';
            }
        }

        $('.multi_sumo_select').SumoSelect({
            placeholder: 'Select Location',
            selectAll: true
        });

        function selectThisService(param) {
            let PK_SERVICE_MASTER = $(param).val();
            $.ajax({
                url: "ajax/get_service_codes.php",
                type: "POST",
                data: {
                    PK_SERVICE_MASTER: PK_SERVICE_MASTER
                },
                async: false,
                cache: false,
                success: function(result) {
                    $(param).closest('.service-row').find('.PK_SERVICE_CODE').empty();
                    $(param).closest('.service-row').find('.PK_SERVICE_CODE').append(result);
                }
            });
        }

        function selectThisServiceCode(param) {
            let service_details = $(param).find(':selected').data('details');
            let price = $(param).find(':selected').data('price');

            $(param).closest('.service-row').find('.SERVICE_DETAILS').val(service_details);
            $(param).closest('.service-row').find('.PRICE_PER_SESSION').val(price);

            calculateServiceTotal(param);
        }

        function calculateServiceTotal(param) {
            let number_of_session = ($(param).closest('.service-row').find('.NUMBER_OF_SESSION').val() == '') ? 0 : $(param).closest('.service-row').find('.NUMBER_OF_SESSION').val();
            let service_price = ($(param).closest('.service-row').find('.PRICE_PER_SESSION').val()) ?? 0;
            let TOTAL = parseFloat(number_of_session) * parseFloat(service_price);

            $(param).closest('.service-row').find('.TOTAL').val(parseFloat(TOTAL).toFixed(2));

            let DISCOUNT = ($(param).closest('.service-row').find('.DISCOUNT').val()) ?? 0;
            let DISCOUNT_TYPE = ($(param).closest('.service-row').find('.DISCOUNT_TYPE').val()) ?? 0;
            let FINAL_AMOUNT = parseFloat(TOTAL);
            if (DISCOUNT_TYPE == 1) {
                FINAL_AMOUNT = parseFloat(TOTAL - DISCOUNT);
            } else {
                if (DISCOUNT_TYPE == 2) {
                    FINAL_AMOUNT = parseFloat(TOTAL - (TOTAL * (DISCOUNT / 100)));
                }
            }
            $(param).closest('.service-row').find('.FINAL_AMOUNT').val(FINAL_AMOUNT.toFixed(2));
        }

        function addMoreServices() {
            rowCounter++;
            $('#append_service_div').append(`<div class="service-row">
                <div>
                    <span class="form-label-sm">Services</span>
                    <select class="form-control-modern PK_SERVICE_MASTER" name="PK_SERVICE_MASTER[]" onchange="selectThisService(this)">
                        <option value="">Select Service</option>
                        <?php
                        $row = $db_account->Execute("SELECT * FROM `DOA_SERVICE_MASTER` WHERE PK_LOCATION IN (" . $DEFAULT_LOCATION_ID . ") AND ACTIVE = 1 AND IS_DELETED = 0");
                        while (!$row->EOF) { ?>
                            <option value="<?php echo $row->fields['PK_SERVICE_MASTER']; ?>"><?= htmlspecialchars($row->fields['SERVICE_NAME']) ?></option>
                        <?php $row->MoveNext();
                        } ?>
                    </select>
                </div>
                <div>
                    <span class="form-label-sm">Service Codes</span>
                    <select class="form-control-modern PK_SERVICE_CODE" name="PK_SERVICE_CODE[]" onchange="selectThisServiceCode(this)">
                        <option value="">Select</option>
                    </select>
                </div>
                <div>
                    <span class="form-label-sm">Service Details</span>
                    <input type="text" class="form-control-modern SERVICE_DETAILS" name="SERVICE_DETAILS[]">
                </div>
                <div>
                    <span class="form-label-sm">Sessions</span>
                    <input type="text" class="form-control-modern NUMBER_OF_SESSION" name="NUMBER_OF_SESSION[]" onkeyup="calculateServiceTotal(this)">
                </div>
                <div>
                    <span class="form-label-sm">Price/Session</span>
                    <input type="text" class="form-control-modern PRICE_PER_SESSION" name="PRICE_PER_SESSION[]" onkeyup="calculateServiceTotal(this);">
                </div>
                <div>
                    <span class="form-label-sm">Total</span>
                    <input type="text" class="form-control-modern TOTAL" name="TOTAL[]" readonly>
                </div>
                <div>
                    <span class="form-label-sm">Discount Type</span>
                    <select class="form-control-modern DISCOUNT_TYPE" name="DISCOUNT_TYPE[]" onchange="calculateServiceTotal(this)">
                        <option value="">Select</option>
                        <option value="1">Fixed</option>
                        <option value="2">Percent</option>
                    </select>
                </div>
                <div>
                    <span class="form-label-sm">Discount</span>
                    <input type="text" class="form-control-modern DISCOUNT" name="DISCOUNT[]" onkeyup="calculateServiceTotal(this)">
                </div>
                <div>
                    <span class="form-label-sm">Final Amount</span>
                    <input type="text" class="form-control-modern FINAL_AMOUNT" name="FINAL_AMOUNT[]" readonly>
                </div>
                <div>
                    <span class="form-label-sm">Chatbot</span>
                    <div class="toggle-wrapper" style="padding:0;">
                        <label class="toggle-switch">
                            <input type="checkbox" class="service-chatbot" name="SERVICE_CHATBOT_ENABLED[]" value="1" data-row-index="` + rowCounter + `" onchange="updateToggleStatus(this, 'service-status-' + this.dataset.rowIndex)">
                            <span class="toggle-slider"></span>
                        </label>
                        <span class="toggle-status inactive" id="service-status-` + rowCounter + `">Off</span>
                    </div>
                    <input type="hidden" class="service-row-index" value="` + rowCounter + `">
                </div>
                <div>
                    <button type="button" class="remove-btn" onclick="removeThis(this);"><i class="fas fa-trash"></i></button>
                </div>
            </div>`);
        }

        function removeThis(param) {
            $(param).closest('.service-row').remove();
        }

        $(document).on('click', '#cancel_button', function() {
            window.location.href = 'all_packages.php'
        });

        $(document).on('submit', '#package_info_form', function(event) {
            event.preventDefault();
            let form_data = $('#package_info_form').serialize();

            // Handle checkbox values properly (they only send value when checked)
            // We need to ensure unchecked checkboxes are sent as 0
            if (!$('#PACKAGE_CHATBOT_ENABLED').is(':checked')) {
                form_data += '&PACKAGE_CHATBOT_ENABLED=0';
            }

            // Ensure all service chatbots are properly serialized
            $('.service-row').each(function(index) {
                let chatbotCheckbox = $(this).find('.service-chatbot');
                if (!chatbotCheckbox.is(':checked')) {
                    // If not checked, we need to ensure it's sent as 0
                    let name = chatbotCheckbox.attr('name');
                    // Remove any existing value for this checkbox
                    form_data = form_data.replace(new RegExp(name + '=[^&]*&?', 'g'), '');
                    form_data += name + '=0&';
                }
            });

            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                data: form_data,
                success: function(data) {
                    window.location.href = 'all_packages.php';
                },
                error: function(xhr, status, error) {
                    console.error('Error saving package:', error);
                    alert('An error occurred while saving. Please try again.');
                }
            });
        });
    </script>
</body>

</html>