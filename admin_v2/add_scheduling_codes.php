<?php
require_once('../global/config.php');

if (empty($_GET['id']))
    $title = "Add Scheduling Codes";
else
    $title = "Edit Scheduling Codes";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

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

if (!empty($_POST)) {
    $SCHEDULING_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
    $SCHEDULING_DATA['SCHEDULING_CODE'] = $_POST['SCHEDULING_CODE'];
    $SCHEDULING_DATA['SCHEDULING_NAME'] = $_POST['SCHEDULING_NAME'];
    $SCHEDULING_DATA['PK_LOCATION'] = $_POST['PK_LOCATION'];
    $SCHEDULING_DATA['TO_DOS'] = $_POST['TO_DOS'] ? 1 : 0;
    $SCHEDULING_DATA['COLOR_CODE'] = $_POST['COLOR_CODE'];
    $SCHEDULING_DATA['DURATION'] = $_POST['DURATION'];
    $SCHEDULING_DATA['UNIT'] = $_POST['UNIT'];
    $SCHEDULING_DATA['SORT_ORDER'] = $_POST['SORT_ORDER'];
    if ($_GET['id'] == '') {
        $SCHEDULING_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
        $SCHEDULING_DATA['CREATED_ON'] = date("Y-m-d H:i");
        $SCHEDULING_DATA['ACTIVE'] = 1;
        db_perform_account('DOA_SCHEDULING_CODE', $SCHEDULING_DATA, 'insert');
        $PK_SCHEDULING_CODE = $db_account->insert_ID();
        header("location:all_scheduling_codes.php");
    } else {
        $SCHEDULING_DATA['ACTIVE'] = $_POST['ACTIVE'];
        $SCHEDULING_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
        $SCHEDULING_DATA['EDITED_ON'] = date("Y-m-d H:i");
        db_perform_account('DOA_SCHEDULING_CODE', $SCHEDULING_DATA, 'update', " PK_SCHEDULING_CODE = '$_GET[id]'");
        $PK_SCHEDULING_CODE = $_GET['id'];
        header("location:add_scheduling_codes.php?id=" . $_GET['id']);
    }
}

if (empty($_GET['id'])) {
    $SCHEDULING_CODE        = '';
    $SCHEDULING_NAME        = '';
    $PK_LOCATION            = '';
    $UNIT                   = '';
    $PK_SCHEDULING_EVENT    = '';
    $PK_EVENT_ACTION        = '';
    $TO_DOS                 = '';
    $COLOR_CODE             = '#39B54A';
    $DURATION               = '';
    $SORT_ORDER             = '';
    $ACTIVE                 = '';
} else {
    $res = $db_account->Execute("SELECT * FROM DOA_SCHEDULING_CODE WHERE PK_SCHEDULING_CODE = '$_GET[id]'");
    if ($res->RecordCount() == 0) {
        header("location:all_scheduling_codes.php");
        exit;
    }
    $SCHEDULING_CODE      = $res->fields['SCHEDULING_CODE'];
    $SCHEDULING_NAME      = $res->fields['SCHEDULING_NAME'];
    $PK_LOCATION      = $res->fields['PK_LOCATION'];
    $UNIT                 = $res->fields['UNIT'];
    $PK_SCHEDULING_EVENT  = $res->fields['PK_SCHEDULING_EVENT'];
    $PK_EVENT_ACTION      = $res->fields['PK_EVENT_ACTION'];
    $TO_DOS               = $res->fields['TO_DOS'];
    $COLOR_CODE           = $res->fields['COLOR_CODE'];
    $DURATION             = $res->fields['DURATION'];
    $SORT_ORDER           = $res->fields['SORT_ORDER'];
    $ACTIVE               = $res->fields['ACTIVE'];
}

$help_title = '';
$help_description = '';
$help = $db->Execute("SELECT * FROM DOA_HELP_PAGE WHERE PAGE_LINK = 'add_scheduling_codes'");
if ($help->RecordCount() > 0) {
    $help_title = $help->fields['TITLE'];
    $help_description = $help->fields['DESCRIPTION'];
}
?>

<!DOCTYPE html>
<html lang="en">
<?php include 'layout/header_script.php'; ?>
<?php require_once('../includes/header.php'); ?>
<?php include 'layout/header.php'; ?>
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

    /* Layout */
    .main-grid {
        display: grid;
        grid-template-columns: 1fr 340px;
        gap: 24px;
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

    /* Checkbox */
    .checkbox-group-modern {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 14px;
        color: var(--gray-700);
        cursor: pointer;
        padding: 4px 0;
    }

    .checkbox-group-modern input[type="checkbox"] {
        width: 18px;
        height: 18px;
        accent-color: var(--primary-color);
        cursor: pointer;
        flex-shrink: 0;
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

    /* Color Picker */
    .color-picker-wrapper {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .color-picker-wrapper input[type="color"] {
        width: 50px;
        height: 50px;
        padding: 4px;
        border: 2px solid var(--gray-200);
        border-radius: var(--radius-sm);
        cursor: pointer;
        background: #fff;
        transition: border-color 0.2s;
    }

    .color-picker-wrapper input[type="color"]:hover {
        border-color: var(--gray-300);
    }

    .color-picker-wrapper input[type="color"]:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1);
    }

    .color-picker-wrapper .color-hex {
        font-size: 14px;
        color: var(--gray-600);
        font-family: 'Courier New', monospace;
        background: var(--gray-50);
        padding: 4px 12px;
        border-radius: var(--radius-sm);
        border: 1px solid var(--gray-200);
    }

    .color-preview {
        width: 36px;
        height: 36px;
        border-radius: var(--radius-sm);
        border: 2px solid var(--gray-200);
        transition: background-color 0.3s;
    }

    /* Duration Input */
    .duration-wrapper {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .duration-wrapper .form-control-modern {
        flex: 1;
        max-width: 150px;
    }

    .duration-wrapper .duration-label {
        font-size: 14px;
        color: var(--gray-500);
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

        .color-picker-wrapper {
            flex-wrap: wrap;
        }

        .duration-wrapper {
            flex-wrap: wrap;
        }
    }

    /* Form helper */
    .form-helper {
        font-size: 12px;
        color: var(--gray-400);
        margin-top: 4px;
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

    /* Full width */
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
                                <span>Please select one Location on the top to add or edit Scheduling Codes.</span>
                            </div>
                        </div>
                    <?php } else { ?>
                        <!-- Main Form -->
                        <div class="col-12 col-md-8 col-xl-10">
                            <!-- Main Grid -->
                            <div class="main-grid">
                                <!-- Main Content -->
                                <div class="card-modern">
                                    <div class="card-header">
                                        <h5>
                                            <i class="bi bi-box-arrow-up-right me-2" style="color: #39b54a;"></i>
                                            <?= !empty($_GET['id']) ? 'Edit Scheduling Code' : 'Create New Scheduling Code' ?>
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
                                                <!-- Scheduling Code -->
                                                <div class="form-group-modern">
                                                    <label class="form-label">Scheduling Code <span class="required">*</span></label>
                                                    <input type="text" class="form-control-modern" id="SCHEDULING_CODE" name="SCHEDULING_CODE" placeholder="Enter Scheduling Code" value="<?php echo htmlspecialchars($SCHEDULING_CODE) ?>" required>
                                                    <div class="form-helper">A unique code for this scheduling type</div>
                                                </div>

                                                <!-- Scheduling Name -->
                                                <div class="form-group-modern">
                                                    <label class="form-label">Scheduling Name <span class="required">*</span></label>
                                                    <input type="text" class="form-control-modern" id="SCHEDULING_NAME" name="SCHEDULING_NAME" placeholder="Enter Scheduling Name" value="<?php echo htmlspecialchars($SCHEDULING_NAME) ?>" required>
                                                    <div class="form-helper">A descriptive name for this scheduling code</div>
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
                                                    <div class="form-helper">Select the location for this scheduling code</div>
                                                </div>

                                                <!-- To Dos (Full Width) -->
                                                <div class="form-group-modern full-width">
                                                    <label class="form-label">To Dos</label>
                                                    <label class="checkbox-group-modern">
                                                        <input type="checkbox" id="TO_DOS" name="TO_DOS" <?= ($TO_DOS == 1) ? 'checked' : '' ?>>
                                                        <span>Enable To-Do List for this scheduling code</span>
                                                    </label>
                                                    <div class="form-helper">Check to enable to-do items for this scheduling code</div>
                                                </div>

                                                <!-- Color Code -->
                                                <div class="form-group-modern">
                                                    <label class="form-label">Color Code <span class="required">*</span></label>
                                                    <div class="color-picker-wrapper">
                                                        <input type="color" id="COLOR_CODE" name="COLOR_CODE" value="<?php echo $COLOR_CODE ?: '#39B54A' ?>">
                                                        <!-- <div class="color-preview" id="colorPreview" style="background-color: <?php echo $COLOR_CODE ?: '#39B54A' ?>;"></div> -->
                                                        <span class="color-hex" id="colorHex"><?php echo $COLOR_CODE ?: '#39B54A' ?></span>
                                                    </div>
                                                    <div class="form-helper">Choose a color to identify this scheduling code</div>
                                                </div>

                                                <!-- Unit -->
                                                <div class="form-group-modern">
                                                    <label class="form-label">Unit <span class="required">*</span></label>
                                                    <select class="form-control-modern" name="UNIT" required>
                                                        <option value="">Select Unit</option>
                                                        <option value="0.5" <?= ($UNIT == '0.5') ? 'selected' : '' ?>>0.5</option>
                                                        <option value="1" <?= ($UNIT == '1') ? 'selected' : '' ?>>1</option>
                                                    </select>
                                                    <div class="form-helper">Time unit for scheduling</div>
                                                </div>

                                                <!-- Duration -->
                                                <div class="form-group-modern">
                                                    <label class="form-label">Duration</label>
                                                    <div class="duration-wrapper">
                                                        <input type="text" class="form-control-modern" id="DURATION" name="DURATION" placeholder="Enter duration" value="<?php echo htmlspecialchars($DURATION) ?>">
                                                        <span class="duration-label">Minutes</span>
                                                    </div>
                                                    <div class="form-helper">Duration in minutes</div>
                                                </div>

                                                <!-- Sort Order -->
                                                <div class="form-group-modern">
                                                    <label class="form-label">Sort Order</label>
                                                    <input type="text" class="form-control-modern" id="SORT_ORDER" name="SORT_ORDER" placeholder="Enter sort order" value="<?php echo htmlspecialchars($SORT_ORDER) ?>">
                                                    <div class="form-helper">Numerical order for sorting</div>
                                                </div>

                                                <!-- Active Status -->
                                                <?php if (!empty($_GET['id'])): ?>
                                                    <div class="form-group-modern" style="grid-column: 1 / -1;">
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
                                                        <div class="form-helper">Set the status of this scheduling code</div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Form Actions -->
                                            <div class="form-actions">
                                                <button type="submit" class="btn-modern btn-modern-primary">
                                                    <i class="fas fa-save"></i>
                                                    <?php if (empty($_GET['id'])): ?>
                                                        Create Scheduling Code
                                                    <?php else: ?>
                                                        Update Scheduling Code
                                                    <?php endif; ?>
                                                </button>
                                                <button type="button" class="btn-modern btn-modern-secondary" onclick="window.location.href='all_scheduling_codes.php'">
                                                    <i class="fas fa-times"></i> Cancel
                                                </button>
                                            </div>

                                        </form>
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
</body>

<script>
    // Color picker live preview
    document.addEventListener('DOMContentLoaded', function() {
        const colorInput = document.getElementById('COLOR_CODE');
        const colorPreview = document.getElementById('colorPreview');
        const colorHex = document.getElementById('colorHex');

        if (colorInput) {
            colorInput.addEventListener('input', function() {
                const color = this.value;
                if (colorPreview) {
                    colorPreview.style.backgroundColor = color;
                }
                if (colorHex) {
                    colorHex.textContent = color.toUpperCase();
                }
            });
        }

        // Form validation
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                const schedulingCode = document.getElementById('SCHEDULING_CODE');
                const schedulingName = document.getElementById('SCHEDULING_NAME');
                const location = document.querySelector('.PK_LOCATION');
                const colorCode = document.getElementById('COLOR_CODE');
                const unit = document.querySelector('select[name="UNIT"]');

                let isValid = true;

                if (!schedulingCode.value.trim()) {
                    schedulingCode.classList.add('is-invalid');
                    isValid = false;
                } else {
                    schedulingCode.classList.remove('is-invalid');
                }

                if (!schedulingName.value.trim()) {
                    schedulingName.classList.add('is-invalid');
                    isValid = false;
                } else {
                    schedulingName.classList.remove('is-invalid');
                }

                if (!location.value) {
                    location.classList.add('is-invalid');
                    isValid = false;
                } else {
                    location.classList.remove('is-invalid');
                }

                if (!colorCode.value) {
                    colorCode.classList.add('is-invalid');
                    isValid = false;
                } else {
                    colorCode.classList.remove('is-invalid');
                }

                if (!unit.value) {
                    unit.classList.add('is-invalid');
                    isValid = false;
                } else {
                    unit.classList.remove('is-invalid');
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
</script>

</html>