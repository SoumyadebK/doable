<?php
require_once('../global/config.php');

if (empty($_GET['id']))
    $title = "Add Document Library";
else
    $title = "Edit Document Library";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

if (!empty($_POST)) {
    $ONBOARDING_DOCUMENT = $_POST;
    $ONBOARDING_DOCUMENT['PK_LOCATION'] = implode(',', $_POST['PK_LOCATION']);
    $ONBOARDING_DOCUMENT['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];

    if (empty($_GET['id'])) {
        $ONBOARDING_DOCUMENT['ACTIVE'] = 1;
        $ONBOARDING_DOCUMENT['CREATED_BY']  = $_SESSION['PK_USER'];
        $ONBOARDING_DOCUMENT['CREATED_ON']  = date("Y-m-d H:i");
        db_perform_account('DOA_DOCUMENT_LIBRARY', $ONBOARDING_DOCUMENT, 'insert');
        $PK_DOCUMENT_LIBRARY = $db_account->insert_ID();
    } else {
        $ONBOARDING_DOCUMENT['ACTIVE'] = $_POST['ACTIVE'];
        $ONBOARDING_DOCUMENT['EDITED_BY']    = $_SESSION['PK_USER'];
        $ONBOARDING_DOCUMENT['EDITED_ON'] = date("Y-m-d H:i");
        db_perform_account('DOA_DOCUMENT_LIBRARY', $ONBOARDING_DOCUMENT, 'update', " PK_DOCUMENT_LIBRARY =  '$_GET[id]'");
        $PK_DOCUMENT_LIBRARY = $_GET['id'];
    }

    $db_account->Execute("DELETE FROM `DOA_DOCUMENT_LOCATION` WHERE `PK_DOCUMENT_LIBRARY` = '$PK_DOCUMENT_LIBRARY'");
    if (isset($_POST['PK_LOCATION'])) {
        $PK_LOCATION = $_POST['PK_LOCATION'];
        for ($i = 0; $i < count($PK_LOCATION); $i++) {
            $DOCUMENT_LOCATION_DATA['PK_DOCUMENT_LIBRARY'] = $PK_DOCUMENT_LIBRARY;
            $DOCUMENT_LOCATION_DATA['PK_LOCATION'] = $PK_LOCATION[$i];
            db_perform_account('DOA_DOCUMENT_LOCATION', $DOCUMENT_LOCATION_DATA, 'insert');
        }
    }

    header("location:all_document_library.php");
}

if (empty($_GET['id'])) {
    $DOCUMENT_NAME = '';
    $PK_DOCUMENT_TYPE = '';
    $PK_LOCATION = '';
    $DOCUMENT_TEMPLATE = '';
    $ACTIVE = '';
} else {
    $res = $db_account->Execute("SELECT * FROM `DOA_DOCUMENT_LIBRARY` WHERE `PK_DOCUMENT_LIBRARY` = '$_GET[id]'");

    if ($res->RecordCount() == 0) {
        header("location:all_document_library.php");
        exit;
    }

    $DOCUMENT_NAME = $res->fields['DOCUMENT_NAME'];
    $PK_DOCUMENT_TYPE = $res->fields['PK_DOCUMENT_TYPE'];
    $PK_LOCATION = $res->fields['PK_LOCATION'];
    $DOCUMENT_TEMPLATE = $res->fields['DOCUMENT_TEMPLATE'];
    $ACTIVE = $res->fields['ACTIVE'];
}

?>

<!DOCTYPE html>
<html lang="en">
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

    /* Tag Cloud */
    .tag-cloud {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        padding: 8px 0;
    }

    .tag-cloud .tag-item {
        display: inline-block;
        padding: 4px 12px;
        background: var(--gray-100);
        border: 1px solid var(--gray-200);
        border-radius: var(--radius-pill);
        font-size: 12px;
        color: var(--gray-700);
        cursor: pointer;
        transition: all 0.2s ease;
        font-family: 'Courier New', monospace;
        text-decoration: none;
        font-weight: 400;
    }

    .tag-cloud .tag-item:hover {
        background: var(--primary-color);
        color: #fff;
        border-color: var(--primary-color);
        transform: scale(1.05);
    }

    /* CKEditor wrapper */
    .ckeditor-wrapper {
        border: 1.5px solid var(--gray-200);
        border-radius: var(--radius-sm);
        overflow: hidden;
        transition: all 0.2s ease;
    }

    .ckeditor-wrapper:focus-within {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1);
    }

    .ckeditor-wrapper .cke {
        border: none !important;
        box-shadow: none !important;
    }

    .ckeditor-wrapper .cke_top {
        background: var(--gray-50) !important;
        border-bottom: 1px solid var(--gray-200) !important;
        padding: 8px 12px !important;
    }

    .ckeditor-wrapper .cke_contents {
        min-height: 400px;
    }

    /* View PDF link */
    .view-pdf-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: var(--primary-color);
        text-decoration: none;
        font-weight: 500;
        font-size: 14px;
        transition: color 0.2s;
        cursor: pointer;
        float: right;
        margin-top: 8px;
    }

    .view-pdf-link:hover {
        color: var(--primary-dark);
        text-decoration: underline;
    }

    .view-pdf-link i {
        font-size: 16px;
    }

    /* Full width */
    .full-width {
        grid-column: 1 / -1;
    }

    @media (max-width: 768px) {
        .full-width {
            grid-column: 1;
        }
    }

    .form-helper {
        font-size: 12px;
        color: var(--gray-400);
        margin-top: 4px;
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
                            <div class="col-12">
                                <div class="card-modern">
                                    <div class="card-header">
                                        <h5>
                                            <i class="bi bi-file-earmark-text"></i>
                                            <?= !empty($_GET['id']) ? 'Edit Document Library' : 'Add Document Library' ?>
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
                                                <!-- Document Name -->
                                                <div class="form-group-modern">
                                                    <label class="form-label">Document Name <span class="required">*</span></label>
                                                    <input type="text" id="DOCUMENT_NAME" name="DOCUMENT_NAME" class="form-control-modern" placeholder="Enter Document Name" required value="<?php echo htmlspecialchars($DOCUMENT_NAME) ?>">
                                                    <div class="form-helper">A unique name for this document</div>
                                                </div>

                                                <!-- Document Type -->
                                                <div class="form-group-modern">
                                                    <label class="form-label">Document Type</label>
                                                    <select class="form-control-modern" name="PK_DOCUMENT_TYPE" id="PK_DOCUMENT_TYPE">
                                                        <option value="">Select Document Type</option>
                                                        <?php
                                                        $row = $db->Execute("SELECT * FROM DOA_DOCUMENT_TYPE WHERE ACTIVE = 1");
                                                        while (!$row->EOF) { ?>
                                                            <option value="<?php echo $row->fields['PK_DOCUMENT_TYPE']; ?>" <?= ($row->fields['PK_DOCUMENT_TYPE'] == $PK_DOCUMENT_TYPE) ? "selected" : "" ?>><?= htmlspecialchars($row->fields['DOCUMENT_TYPE']) ?></option>
                                                        <?php $row->MoveNext();
                                                        } ?>
                                                    </select>
                                                    <div class="form-helper">Select the type of document</div>
                                                </div>

                                                <!-- Location -->
                                                <div class="form-group-modern full-width">
                                                    <label class="form-label">Location</label>
                                                    <select class="multi_sumo_select_location" name="PK_LOCATION[]" id="PK_LOCATION" multiple>
                                                        <?php
                                                        $selected_location = [];
                                                        if (!empty($_GET['id'])) {
                                                            $selected_location_row = $db_account->Execute("SELECT `PK_LOCATION` FROM `DOA_DOCUMENT_LOCATION` WHERE `PK_DOCUMENT_LIBRARY` = '$_GET[id]'");
                                                            while (!$selected_location_row->EOF) {
                                                                $selected_location[] = $selected_location_row->fields['PK_LOCATION'];
                                                                $selected_location_row->MoveNext();
                                                            }
                                                        }
                                                        $row = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME FROM DOA_LOCATION WHERE ACTIVE = 1 AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
                                                        while (!$row->EOF) { ?>
                                                            <option value="<?php echo $row->fields['PK_LOCATION']; ?>" <?= in_array($row->fields['PK_LOCATION'], $selected_location) ? "selected" : "" ?>><?= htmlspecialchars($row->fields['LOCATION_NAME']) ?></option>
                                                        <?php $row->MoveNext();
                                                        } ?>
                                                    </select>
                                                    <div class="form-helper">Select one or more locations where this document will be available</div>
                                                </div>

                                                <!-- Tag Name -->
                                                <div class="form-group-modern full-width">
                                                    <label class="form-label">Tag Name</label>
                                                    <div class="tag-cloud">
                                                        <span class="tag-item" data-tag="{FULL_NAME}">{FULL_NAME}</span>
                                                        <span class="tag-item" data-tag="{STREET_ADD}">{STREET_ADD}</span>
                                                        <span class="tag-item" data-tag="{CITY}">{CITY}</span>
                                                        <span class="tag-item" data-tag="{STATE}">{STATE}</span>
                                                        <span class="tag-item" data-tag="{ZIP}">{ZIP}</span>
                                                        <span class="tag-item" data-tag="{RES_PHONE}">{RES_PHONE}</span>
                                                        <span class="tag-item" data-tag="{CELL_PHONE}">{CELL_PHONE}</span>
                                                        <span class="tag-item" data-tag="{SERVICE_DETAILS}">{SERVICE_DETAILS}</span>
                                                        <span class="tag-item" data-tag="{PVT_LESSONS}">{PVT_LESSONS}</span>
                                                        <span class="tag-item" data-tag="{TUITION}">{TUITION}</span>
                                                        <span class="tag-item" data-tag="{DISCOUNT}">{DISCOUNT}</span>
                                                        <span class="tag-item" data-tag="{BAL_DUE}">{BAL_DUE}</span>
                                                        <span class="tag-item" data-tag="{TOTAL}">{TOTAL}</span>
                                                        <span class="tag-item" data-tag="{FIRST_DATE}">{FIRST_DATE}</span>
                                                        <span class="tag-item" data-tag="{TYPE_OF_ENROLLMENT}">{TYPE_OF_ENROLLMENT}</span>
                                                        <span class="tag-item" data-tag="{MISC_SERVICES}">{MISC_SERVICES}</span>
                                                        <span class="tag-item" data-tag="{TUITION_COST}">{TUITION_COST}</span>
                                                        <span class="tag-item" data-tag="{CASH_PRICE}">{CASH_PRICE}</span>
                                                        <span class="tag-item" data-tag="{OUTS_BAL_PRE_AGREE}">{OUTS_BAL_PRE_AGREE}</span>
                                                        <span class="tag-item" data-tag="{UNEARNED_CHARGE}">{UNEARNED_CHARGE}</span>
                                                        <span class="tag-item" data-tag="{PREV_BAL_RESCHEDULE}">{PREV_BAL_RESCHEDULE}</span>
                                                        <span class="tag-item" data-tag="{CONSOLIDATED_PRICE}">{CONSOLIDATED_PRICE}</span>
                                                        <span class="tag-item" data-tag="{DOWN_PAYMENTS}">{DOWN_PAYMENTS}</span>
                                                        <span class="tag-item" data-tag="{REMAINING_BALANCE}">{REMAINING_BALANCE}</span>
                                                        <span class="tag-item" data-tag="{PAYMENT_NAME}">{PAYMENT_NAME}</span>
                                                        <span class="tag-item" data-tag="{NO_AMT_PAYMENT}">{NO_AMT_PAYMENT}</span>
                                                        <span class="tag-item" data-tag="{INSTALLMENT_AMOUNT}">{INSTALLMENT_AMOUNT}</span>
                                                        <span class="tag-item" data-tag="{SERVICE_PRICE}">{SERVICE_PRICE}</span>
                                                        <span class="tag-item" data-tag="{STARTING_DATE}">{STARTING_DATE}</span>
                                                        <span class="tag-item" data-tag="{BILLING_DATE}">{BILLING_DATE}</span>
                                                        <span class="tag-item" data-tag="{SCHEDULE_AMOUNT}">{SCHEDULE_AMOUNT}</span>
                                                        <span class="tag-item" data-tag="{SERVICE_CHARGE}">{SERVICE_CHARGE}</span>
                                                        <span class="tag-item" data-tag="{TOTAL_PAYMENTS}">{TOTAL_PAYMENTS}</span>
                                                        <span class="tag-item" data-tag="{TOTAL_SELL_PRICE}">{TOTAL_SELL_PRICE}</span>
                                                        <span class="tag-item" data-tag="{PERCENTAGE_RATE}">{PERCENTAGE_RATE}</span>
                                                        <span class="tag-item" data-tag="{DUE_DATE}">{DUE_DATE}</span>
                                                        <span class="tag-item" data-tag="{BILLED_AMOUNT}">{BILLED_AMOUNT}</span>
                                                        <span class="tag-item" data-tag="{BUSINESS_NAME}">{BUSINESS_NAME}</span>
                                                        <span class="tag-item" data-tag="{BUSINESS_ADD}">{BUSINESS_ADD}</span>
                                                        <span class="tag-item" data-tag="{BUSINESS_CITY}">{BUSINESS_CITY}</span>
                                                        <span class="tag-item" data-tag="{BUSINESS_STATE}">{BUSINESS_STATE}</span>
                                                        <span class="tag-item" data-tag="{BUSINESS_COUNTRY}">{BUSINESS_COUNTRY}</span>
                                                        <span class="tag-item" data-tag="{BUSINESS_ZIP}">{BUSINESS_ZIP}</span>
                                                        <span class="tag-item" data-tag="{BUSINESS_PHONE}">{BUSINESS_PHONE}</span>
                                                        <span class="tag-item" data-tag="{CLIENTS_SIGNATURE}">{CLIENTS_SIGNATURE}</span>
                                                        <span class="tag-item" data-tag="{STUDIO_REPRESENTATIVE}">{STUDIO_REPRESENTATIVE}</span>
                                                        <span class="tag-item" data-tag="{CO_CLIENT_GUARDIAN}">{CO_CLIENT_GUARDIAN}</span>
                                                        <span class="tag-item" data-tag="{VERIFIED_BY}">{VERIFIED_BY}</span>
                                                        <span class="tag-item" data-tag="{EXPIRATION_DATE}">{EXPIRATION_DATE}</span>
                                                    </div>
                                                    <div class="form-helper">Click on any tag to insert it into the template</div>
                                                </div>

                                                <!-- Template -->
                                                <div class="form-group-modern full-width">
                                                    <label class="form-label">Template</label>
                                                    <div class="ckeditor-wrapper">
                                                        <textarea id="ck_editor" rows="20" name="DOCUMENT_TEMPLATE"><?= htmlspecialchars($DOCUMENT_TEMPLATE) ?></textarea>
                                                    </div>
                                                    <div class="form-helper">Use the toolbar above to format your document template</div>
                                                </div>

                                                <!-- View PDF -->
                                                <div class="form-group-modern full-width" style="margin-top: 4px;">
                                                    <a href="javascript:;" onclick="viewSamplePdf()" class="view-pdf-link">
                                                        <i class="fas fa-file-pdf"></i> View PDF Preview
                                                    </a>
                                                </div>

                                                <!-- Active Status -->
                                                <?php if (!empty($_GET['id'])): ?>
                                                    <div class="form-group-modern full-width">
                                                        <label class="form-label">Active</label>
                                                        <div class="radio-group-modern">
                                                            <label class="radio-item">
                                                                <input type="radio" name="ACTIVE" id="ACTIVE_YES" value="1" <?php if ($ACTIVE == 1) echo 'checked="checked"'; ?>> Active
                                                            </label>
                                                            <label class="radio-item">
                                                                <input type="radio" name="ACTIVE" id="ACTIVE_NO" value="0" <?php if ($ACTIVE == 0) echo 'checked="checked"'; ?>> Inactive
                                                            </label>
                                                        </div>
                                                        <div class="form-helper">Set the status of this document</div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Form Actions -->
                                            <div class="form-actions">
                                                <button type="submit" class="btn-modern btn-modern-primary">
                                                    <i class="fas fa-save"></i>
                                                    <?php if (empty($_GET['id'])): ?>
                                                        Create Document
                                                    <?php else: ?>
                                                        Update Document
                                                    <?php endif; ?>
                                                </button>
                                                <button type="button" class="btn-modern btn-modern-secondary" onclick="window.location.href='all_document_library.php'">
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
    <script src="../assets/ckeditor/ckeditor.js"></script>

    <script>
        // Initialize SumoSelect
        $('#PK_LOCATION').SumoSelect({
            placeholder: 'Select Location',
            selectAll: true,
            captionFormat: '{0} locations selected',
            captionFormatAllSelected: 'All locations selected'
        });

        // Initialize CKEditor
        const editor = CKEDITOR.replace('ck_editor', {
            versionCheck: false,
            height: 400,
            toolbar: [{
                    name: 'document',
                    items: ['Source', '-', 'Save', 'NewPage', 'Preview', 'Print', '-', 'Templates']
                },
                {
                    name: 'clipboard',
                    items: ['Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo']
                },
                {
                    name: 'editing',
                    items: ['Find', 'Replace', '-', 'SelectAll', '-', 'Scayt']
                },
                {
                    name: 'forms',
                    items: ['Form', 'Checkbox', 'Radio', 'TextField', 'Textarea', 'Select', 'Button', 'ImageButton', 'HiddenField']
                },
                '/',
                {
                    name: 'basicstyles',
                    items: ['Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-', 'RemoveFormat']
                },
                {
                    name: 'paragraph',
                    items: ['NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote', 'CreateDiv', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock', '-', 'BidiLtr', 'BidiRtl', 'Language']
                },
                {
                    name: 'links',
                    items: ['Link', 'Unlink', 'Anchor']
                },
                {
                    name: 'insert',
                    items: ['Image', 'Flash', 'Table', 'HorizontalRule', 'Smiley', 'SpecialChar', 'PageBreak', 'Iframe']
                },
                '/',
                {
                    name: 'styles',
                    items: ['Styles', 'Format', 'Font', 'FontSize']
                },
                {
                    name: 'colors',
                    items: ['TextColor', 'BGColor']
                },
                {
                    name: 'tools',
                    items: ['Maximize', 'ShowBlocks']
                },
                {
                    name: 'about',
                    items: ['About']
                }
            ]
        });

        // Tag click handler - insert tag into CKEditor
        $(document).on('click', '.tag-item', function() {
            let tag_name = $(this).data('tag');
            if (editor) {
                editor.insertText(tag_name);
            }
        });

        // View PDF function
        function viewSamplePdf() {
            let DOCUMENT_TEMPLATE = $('#ck_editor').val();
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                data: {
                    FUNCTION_NAME: 'viewSamplePdf',
                    DOCUMENT_TEMPLATE: DOCUMENT_TEMPLATE
                },
                success: function(data) {
                    console.log(data);
                    window.open(
                        data,
                        '_blank'
                    );
                },
                error: (error) => {
                    console.log(JSON.stringify(error));
                }
            });
        }

        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const documentName = document.getElementById('DOCUMENT_NAME');
                    const documentType = document.getElementById('PK_DOCUMENT_TYPE');
                    const locations = $('#PK_LOCATION').val();

                    let isValid = true;

                    if (!documentName.value.trim()) {
                        documentName.classList.add('is-invalid');
                        isValid = false;
                    } else {
                        documentName.classList.remove('is-invalid');
                    }

                    if (!documentType.value) {
                        documentType.classList.add('is-invalid');
                        isValid = false;
                    } else {
                        documentType.classList.remove('is-invalid');
                    }

                    if (!locations || locations.length === 0) {
                        $('.SumoSelect').addClass('is-invalid');
                        isValid = false;
                    } else {
                        $('.SumoSelect').removeClass('is-invalid');
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
            }
        });

        // Clear validation on SumoSelect change
        $(document).on('change', '#PK_LOCATION', function() {
            if ($(this).val() && $(this).val().length > 0) {
                $('.SumoSelect').removeClass('is-invalid');
            }
        });
    </script>
</body>

</html>