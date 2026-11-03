<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];

$title = "Concierge Setting";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

$PK_ACCOUNT_MASTER = $_SESSION['PK_ACCOUNT_MASTER'];

$account_data = $db->Execute("SELECT * FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER = '$PK_ACCOUNT_MASTER'");

$WELCOME_MESSAGE = $account_data->fields['WELCOME_MESSAGE'];
$AVATAR_EMOJI = $account_data->fields['AVATAR_EMOJI'];
$PRIMARY_COLOR = $account_data->fields['PRIMARY_COLOR'];
$OFFERING_LABEL = $account_data->fields['OFFERING_LABEL'];
$OFFERING_TYPE = $account_data->fields['OFFERING_TYPE'];

if (isset($_POST['FUNCTION_NAME']) && $_POST['FUNCTION_NAME'] == 'saveConciergeSetting') {
    $ACCOUNT_DATA_UPDATE['WELCOME_MESSAGE'] = $_POST['WELCOME_MESSAGE'];
    $ACCOUNT_DATA_UPDATE['AVATAR_EMOJI'] = $_POST['AVATAR_EMOJI'];
    $ACCOUNT_DATA_UPDATE['PRIMARY_COLOR'] = $_POST['PRIMARY_COLOR'];
    $ACCOUNT_DATA_UPDATE['OFFERING_LABEL'] = $_POST['OFFERING_LABEL'];
    $ACCOUNT_DATA_UPDATE['OFFERING_TYPE'] = $_POST['OFFERING_TYPE'];

    // Update the account master table
    db_perform('DOA_ACCOUNT_MASTER', $ACCOUNT_DATA_UPDATE, 'update', ' PK_ACCOUNT_MASTER = ' . $PK_ACCOUNT_MASTER);

    // Insert new offerings
    if (isset($_POST['OFFERING'])) {
        // Delete existing offerings
        $db->Execute("DELETE FROM DOA_ACCOUNT_OFFERINGS WHERE PK_ACCOUNT_MASTER = '$PK_ACCOUNT_MASTER'");

        $INSERT_DATA['PK_ACCOUNT_MASTER'] = $PK_ACCOUNT_MASTER;
        $offerings = $_POST['OFFERING'];
        $description = $_POST['DESCRIPTION'];
        for ($i = 0; $i < count($offerings); $i++) {
            $INSERT_DATA['OFFERING'] = $offerings[$i];
            $INSERT_DATA['DESCRIPTION'] = $description[$i];
            db_perform('DOA_ACCOUNT_OFFERINGS', $INSERT_DATA, 'insert');
        }
    }

    header("location:concierge_settings.php");
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

    .chatbot-toggle-row {
        display: flex;
        align-items: center;
        gap: 20px;
        flex-wrap: wrap;
    }

    /* Offering Card (keeps theme consistent with other cards) */
    .offering-card {
        background: #ffffff;
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--gray-200);
        padding: 18px;
        margin-top: 24px;
    }

    .offering-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 12px;
    }

    .offering-title {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 15px;
        font-weight: 600;
        color: var(--gray-800);
    }

    .offering-sub {
        font-size: 13px;
        color: var(--gray-500);
    }

    .offering-card .service-table-wrapper {
        background: transparent;
        border: none;
        padding: 0;
    }

    /* Adjust grid specifically for offering rows (two inputs + action) */
    .offering-card .service-table-wrapper .table-header,
    .offering-card .service-table-wrapper .service-row {
        grid-template-columns: 2fr 3fr 0.5fr;
        gap: 12px;
        align-items: center;
    }

    .offering-card .service-table-wrapper .service-row .form-control-modern {
        width: 100%;
        padding: 8px 10px;
        font-size: 14px;
    }

    @media (max-width: 768px) {
        .offering-card .service-table-wrapper .table-header {
            display: none;
        }

        .offering-card .service-table-wrapper .service-row {
            grid-template-columns: 1fr;
            gap: 8px;
            padding: 12px;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-sm);
            background: #fff;
            margin-bottom: 8px;
        }
    }

    /* Offering-specific column widths for a simpler 3-column layout */
    .offering-card .service-table-wrapper .table-header,
    .offering-card .service-table-wrapper .service-row {
        grid-template-columns: 2fr 3fr 60px !important;
        gap: 12px;
        padding: 8px 4px;
    }

    .offering-card .service-table-wrapper .service-row .form-control-modern {
        padding: 8px 10px;
        font-size: 14px;
    }

    @media (max-width: 992px) {

        .offering-card .service-table-wrapper .table-header,
        .offering-card .service-table-wrapper .service-row {
            grid-template-columns: 1fr 1fr 60px !important;
            font-size: 13px;
        }
    }

    @media (max-width: 768px) {
        .offering-card .service-table-wrapper .table-header {
            display: none;
        }

        .offering-card .service-table-wrapper .service-row {
            grid-template-columns: 1fr !important;
            gap: 8px;
            padding: 12px;
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-sm);
            margin-bottom: 8px;
        }

        .offering-card .service-table-wrapper .service-row .form-control-modern {
            width: 100%;
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
                                            <i class="bi bi-gear"></i>
                                            <?= $title ?>
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <form class="form-material form-horizontal" method="post" enctype="multipart/form-data">
                                            <input type="hidden" name="FUNCTION_NAME" value="saveConciergeSetting">

                                            <!-- Welcome Message -->
                                            <div class="form-grid">
                                                <div class="form-group-modern">
                                                    <label class="form-label">Welcome Message <span class="required">*</span></label>
                                                    <input type="text" name="WELCOME_MESSAGE" class="form-control-modern" placeholder="Enter Welcome Message" required value="<?php echo htmlspecialchars($WELCOME_MESSAGE) ?>">
                                                </div>

                                                <div class="form-group-modern">
                                                    <label class="form-label">Avatar Emoji</label>
                                                    <input type="text" name="AVATAR_EMOJI" class="form-control-modern" placeholder="Enter Avatar Emoji" value="<?php echo htmlspecialchars($AVATAR_EMOJI) ?>">
                                                </div>

                                                <div class="form-group-modern">
                                                    <label class="form-label">Primary Color</label>
                                                    <input type="color" name="PRIMARY_COLOR" class="form-control-modern" placeholder="Enter Primary Color" value="<?php echo htmlspecialchars($PRIMARY_COLOR) ?>">
                                                </div>
                                            </div>

                                            <!-- Offering Section -->
                                            <div class="offering-card">
                                                <div class="offering-header">
                                                    <div class="offering-title">
                                                        <i class="bi bi-box-seam" style="color: var(--primary-color); font-size:18px;"></i>
                                                        Offering
                                                    </div>
                                                    <div class="offering-sub">Configure the concierge offerings shown to customers</div>
                                                </div>

                                                <div class="offering-body">
                                                    <div class="form-grid">
                                                        <div class="form-group-modern">
                                                            <label class="form-label">Offering Label</label>
                                                            <input type="text" name="OFFERING_LABEL" class="form-control-modern" placeholder="Enter Offering Name" required value="<?php echo htmlspecialchars($OFFERING_LABEL) ?>">
                                                        </div>

                                                        <div class="form-group-modern">
                                                            <label class="form-label">Offering Type</label>
                                                            <input type="text" name="OFFERING_TYPE" class="form-control-modern" placeholder="Enter Offering Type" value="<?php echo htmlspecialchars($OFFERING_TYPE) ?>">
                                                        </div>
                                                    </div>

                                                    <div class="service-table-wrapper" style="margin-top:12px; padding:12px 16px;">
                                                        <!-- Table Header -->
                                                        <div class="table-header">
                                                            <span>Offerings</span>
                                                            <span>Description</span>
                                                            <span></span>
                                                        </div>

                                                        <!-- Service Rows -->
                                                        <div id="append_service_div">
                                                            <?php
                                                            $offering_data = $db->Execute("SELECT * FROM DOA_ACCOUNT_OFFERINGS WHERE PK_ACCOUNT_MASTER = '$PK_ACCOUNT_MASTER'");
                                                            if ($offering_data->RecordCount() > 0) {
                                                                while (!$offering_data->EOF) { ?>
                                                                    <div class="service-row">
                                                                        <div>
                                                                            <input type="text" class="form-control-modern OFFERING" name="OFFERING[]" value="<?= htmlspecialchars($offering_data->fields['OFFERING']) ?>">
                                                                        </div>
                                                                        <div>
                                                                            <input type="text" class="form-control-modern DESCRIPTION" name="DESCRIPTION[]" value="<?= htmlspecialchars($offering_data->fields['DESCRIPTION']) ?>">
                                                                        </div>
                                                                        <div>
                                                                            <button type="button" class="remove-btn" onclick="removeThis(this);"><i class="fas fa-trash"></i></button>
                                                                        </div>
                                                                    </div>
                                                                <?php $offering_data->MoveNext();
                                                                }
                                                            } else { ?>
                                                                <div class="service-row">
                                                                    <div>
                                                                        <input type="text" class="form-control-modern OFFERING" name="OFFERING[]">
                                                                    </div>
                                                                    <div>
                                                                        <input type="text" class="form-control-modern DESCRIPTION" name="DESCRIPTION[]">
                                                                    </div>
                                                                    <div>
                                                                        <button type="button" class="remove-btn" onclick="removeThis(this);"><i class="fas fa-trash"></i></button>
                                                                    </div>
                                                                </div>
                                                            <?php } ?>
                                                        </div>
                                                    </div>

                                                    <!-- Action Row -->
                                                    <div class="action-row" style="margin-top:12px;">
                                                        <button type="button" class="btn-modern btn-modern-secondary btn-modern-sm" onclick="addMoreOffering();">
                                                            <i class="fas fa-plus"></i> Add More
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>

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
        function addMoreOffering() {
            $('#append_service_div').append(`<div class="service-row">
                                                <div>
                                                    <input type="text" class="form-control-modern OFFERING" name="OFFERING[]">
                                                </div>
                                                <div>
                                                    <input type="text" class="form-control-modern DESCRIPTION" name="DESCRIPTION[]">
                                                </div>
                                                <div>
                                                    <button type="button" class="remove-btn" onclick="removeThis(this);"><i class="fas fa-trash"></i></button>
                                                </div>
                                            </div>`);
        }

        function removeThis(param) {
            $(param).closest('.service-row').remove();
        }
    </script>
</body>

</html>