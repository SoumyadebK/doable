<?php
require_once('../global/config.php');

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

if (empty($_GET['id'])) {
    $title = "Add Gift Certificate Setup";
} else {
    $title = "Edit Gift Certificate Setup";
}

if (!empty($_POST)) {
    $GIFT_CERTIFICATE_SETUP_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
    if (empty($_GET['id'])) {
        $GIFT_CERTIFICATE_SETUP_DATA['GIFT_CERTIFICATE_CODE'] = $_POST['GIFT_CERTIFICATE_CODE'];
        $GIFT_CERTIFICATE_SETUP_DATA['GIFT_CERTIFICATE_NAME'] = $_POST['GIFT_CERTIFICATE_NAME'];
        $GIFT_CERTIFICATE_SETUP_DATA['MINIMUM_AMOUNT'] = $_POST['MINIMUM_AMOUNT'];
        $GIFT_CERTIFICATE_SETUP_DATA['MAXIMUM_AMOUNT'] = $_POST['MAXIMUM_AMOUNT'];
        $GIFT_CERTIFICATE_SETUP_DATA['EFFECTIVE_DATE'] = date('Y-m-d', strtotime($_POST['EFFECTIVE_DATE']));
        $GIFT_CERTIFICATE_SETUP_DATA['END_DATE'] = date('Y-m-d', strtotime($_POST['END_DATE']));
        $GIFT_CERTIFICATE_SETUP_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
        $GIFT_CERTIFICATE_SETUP_DATA['CREATED_ON'] = date("Y-m-d H:i");
        $GIFT_CERTIFICATE_SETUP_DATA['ACTIVE'] = 1;
        db_perform_account('DOA_GIFT_CERTIFICATE_SETUP', $GIFT_CERTIFICATE_SETUP_DATA, 'insert');
        $PK_GIFT_CERTIFICATE_SETUP = $db_account->insert_ID();
        header("location:all_gift_certificate_setup.php");
    } else {
        $GIFT_CERTIFICATE_SETUP_DATA['GIFT_CERTIFICATE_CODE'] = $_POST['GIFT_CERTIFICATE_CODE'];
        $GIFT_CERTIFICATE_SETUP_DATA['GIFT_CERTIFICATE_NAME'] = $_POST['GIFT_CERTIFICATE_NAME'];
        $GIFT_CERTIFICATE_SETUP_DATA['MINIMUM_AMOUNT'] = $_POST['MINIMUM_AMOUNT'];
        $GIFT_CERTIFICATE_SETUP_DATA['MAXIMUM_AMOUNT'] = $_POST['MAXIMUM_AMOUNT'];
        $GIFT_CERTIFICATE_SETUP_DATA['EFFECTIVE_DATE'] = date('Y-m-d', strtotime($_POST['EFFECTIVE_DATE']));
        $GIFT_CERTIFICATE_SETUP_DATA['END_DATE'] = date('Y-m-d', strtotime($_POST['END_DATE']));
        $GIFT_CERTIFICATE_SETUP_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
        $GIFT_CERTIFICATE_SETUP_DATA['EDITED_ON'] = date("Y-m-d H:i");
        $GIFT_CERTIFICATE_SETUP_DATA['ACTIVE'] = $_POST['ACTIVE'];
        db_perform_account('DOA_GIFT_CERTIFICATE_SETUP', $GIFT_CERTIFICATE_SETUP_DATA, 'update', "PK_GIFT_CERTIFICATE_SETUP = '$_GET[id]'");
        $PK_GIFT_CERTIFICATE_SETUP = $_GET['id'];
        header("location:all_gift_certificate_setup.php");
    }

    $db_account->Execute("DELETE FROM `DOA_GIFT_LOCATION` WHERE `PK_GIFT_CERTIFICATE_SETUP` = '$PK_GIFT_CERTIFICATE_SETUP'");
    if (isset($_POST['PK_LOCATION'])) {
        $PK_LOCATION = $_POST['PK_LOCATION'];
        for ($i = 0; $i < count($PK_LOCATION); $i++) {
            $GIFT_LOCATION_DATA['PK_GIFT_CERTIFICATE_SETUP'] = $PK_GIFT_CERTIFICATE_SETUP;
            $GIFT_LOCATION_DATA['PK_LOCATION'] = $PK_LOCATION[$i];
            db_perform_account('DOA_GIFT_LOCATION', $GIFT_LOCATION_DATA, 'insert');
        }
    }
}

if (empty($_GET['id'])) {
    $PK_USER_MASTER = '';
    $GIFT_CERTIFICATE_CODE = '';
    $GIFT_CERTIFICATE_NAME = '';
    $MINIMUM_AMOUNT = '';
    $MAXIMUM_AMOUNT = '';
    $EFFECTIVE_DATE = '';
    $END_DATE = '';
    $PK_LOCATION = '';
    $ACTIVE = '';
} else {
    $res = $db_account->Execute("SELECT * FROM DOA_GIFT_CERTIFICATE_SETUP WHERE PK_GIFT_CERTIFICATE_SETUP = '$_GET[id]'");
    if ($res->RecordCount() == 0) {
        header("location:all_gift_certificate_setup.php");
        exit;
    }
    $GIFT_CERTIFICATE_CODE = $res->fields['GIFT_CERTIFICATE_CODE'];
    $GIFT_CERTIFICATE_NAME = $res->fields['GIFT_CERTIFICATE_NAME'];
    $MINIMUM_AMOUNT = $res->fields['MINIMUM_AMOUNT'];
    $MAXIMUM_AMOUNT = $res->fields['MAXIMUM_AMOUNT'];
    $EFFECTIVE_DATE = $res->fields['EFFECTIVE_DATE'];
    $END_DATE = $res->fields['END_DATE'];
    $ACTIVE = $res->fields['ACTIVE'];
}

?>

<!DOCTYPE html>
<html lang="en">
<?php include 'layout/header_script.php'; ?>
<?php require_once('../includes/header.php'); ?>
<?php include 'layout/header.php'; ?>
<link href="../assets/sumoselect/sumoselect.min.css" rel="stylesheet" />
<link href="https://fonts.googleapis.com/css2?family=PT+Mono&display=swap" rel="stylesheet">
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

    /* Form Grid */
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

    .form-control-modern:disabled {
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

    /* Form helper */
    .form-helper {
        font-size: 12px;
        color: var(--gray-400);
        margin-top: 4px;
    }

    .form-helper.error {
        color: var(--danger-color);
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
            <div class="container-fluid body_content" style="margin-top: 24px !important;">

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
                                    <i class="bi bi-sliders2"></i>
                                    <?= !empty($_GET['id']) ? 'Edit Gift Certificate Setup' : 'Create New Gift Certificate Setup' ?>
                                </h5>
                                <?php if (!empty($_GET['id'])): ?>
                                    <span class="status-indicator <?= ($ACTIVE == 1) ? 'active' : 'inactive' ?>">
                                        <i class="fas fa-circle"></i>
                                        <?= ($ACTIVE == 1) ? 'Active' : 'Inactive' ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <form id="gift_certificate_form" action="" method="post" enctype="multipart/form-data">

                                    <div class="form-grid">
                                        <!-- Gift Certificate Name -->
                                        <div class="form-group-modern">
                                            <label class="form-label">Gift Certificate Name <span class="required">*</span></label>
                                            <input type="text" id="GIFT_CERTIFICATE_NAME" name="GIFT_CERTIFICATE_NAME" class="form-control-modern" placeholder="Enter Gift Certificate Name" required value="<?php echo htmlspecialchars($GIFT_CERTIFICATE_NAME) ?>">
                                            <div class="form-helper">A descriptive name for this gift certificate</div>
                                        </div>

                                        <!-- Gift Certificate Code -->
                                        <div class="form-group-modern">
                                            <label class="form-label">Gift Certificate Code <span class="required">*</span></label>
                                            <input type="text" id="GIFT_CERTIFICATE_CODE" name="GIFT_CERTIFICATE_CODE" class="form-control-modern" placeholder="Enter Gift Certificate Code" required value="<?php echo htmlspecialchars($GIFT_CERTIFICATE_CODE) ?>">
                                            <div class="form-helper">A unique identifier code for this gift certificate</div>
                                        </div>

                                        <!-- Effective Date -->
                                        <div class="form-group-modern">
                                            <label class="form-label">Effective Date <span class="required">*</span></label>
                                            <input type="text" name="EFFECTIVE_DATE" id="EFFECTIVE_DATE" value="<?= ($EFFECTIVE_DATE == '') ? date('m/d/Y') : date('m/d/Y', strtotime($EFFECTIVE_DATE)) ?>" class="form-control-modern datepicker-normal" required>
                                            <div class="form-helper">Date when this gift certificate becomes active</div>
                                        </div>

                                        <!-- End Date -->
                                        <div class="form-group-modern">
                                            <label class="form-label">End Date <span class="required">*</span></label>
                                            <input type="text" name="END_DATE" id="END_DATE" value="<?= ($END_DATE == '') ? date('m/d/Y') : date('m/d/Y', strtotime($END_DATE)) ?>" class="form-control-modern datepicker-normal" required>
                                            <div class="form-helper">Date when this gift certificate expires</div>
                                        </div>

                                        <!-- Minimum Amount -->
                                        <div class="form-group-modern">
                                            <label class="form-label">Minimum Amount <span class="required">*</span></label>
                                            <div style="position: relative;">
                                                <span style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); font-weight: 600; color: var(--gray-500);">$</span>
                                                <input type="text" id="MINIMUM_AMOUNT" name="MINIMUM_AMOUNT" class="form-control-modern" style="padding-left: 32px;" placeholder="0.00" required value="<?php echo $MINIMUM_AMOUNT ?>">
                                            </div>
                                            <div class="form-helper">Minimum purchase amount required</div>
                                        </div>

                                        <!-- Maximum Amount -->
                                        <div class="form-group-modern">
                                            <label class="form-label">Maximum Amount <span class="required">*</span></label>
                                            <div style="position: relative;">
                                                <span style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); font-weight: 600; color: var(--gray-500);">$</span>
                                                <input type="text" id="MAXIMUM_AMOUNT" name="MAXIMUM_AMOUNT" class="form-control-modern" style="padding-left: 32px;" placeholder="0.00" required value="<?php echo $MAXIMUM_AMOUNT ?>">
                                            </div>
                                            <div class="form-helper">Maximum purchase amount allowed</div>
                                        </div>

                                        <!-- Location (Full Width) -->
                                        <div class="form-group-modern" style="grid-column: 1 / -1;">
                                            <label class="form-label">Assigned Locations <span class="required">*</span></label>
                                            <select class="multi_sumo_select_location" name="PK_LOCATION[]" id="PK_LOCATION" multiple required>
                                                <?php
                                                $selected_location = [];
                                                if (!empty($_GET['id'])) {
                                                    $selected_location_row = $db_account->Execute("SELECT `PK_LOCATION` FROM `DOA_GIFT_LOCATION` WHERE `PK_GIFT_CERTIFICATE_SETUP` = '$_GET[id]'");
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
                                            <div class="form-helper">Select one or more locations where this gift certificate will be available</div>
                                        </div>

                                        <!-- Active Status -->
                                        <?php if (!empty($_GET['id'])): ?>
                                            <div class="form-group-modern" style="grid-column: 1 / -1;">
                                                <label class="form-label">Status</label>
                                                <div class="radio-group-modern">
                                                    <label class="radio-item">
                                                        <input type="radio" name="ACTIVE" id="ACTIVE1" value="1" <?php if ($ACTIVE == 1) echo 'checked="checked"'; ?>>
                                                        Active
                                                    </label>
                                                    <label class="radio-item">
                                                        <input type="radio" name="ACTIVE" id="ACTIVE2" value="0" <?php if ($ACTIVE == 0) echo 'checked="checked"'; ?>>
                                                        Inactive
                                                    </label>
                                                </div>
                                                <div class="form-helper">Set the status of this gift certificate setup</div>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Form Actions -->
                                    <div class="form-actions">
                                        <button type="submit" class="btn-modern btn-modern-primary">
                                            <i class="fas fa-save"></i>
                                            <?php if (empty($_GET['id'])): ?>
                                                Create Setup
                                            <?php else: ?>
                                                Update Setup
                                            <?php endif; ?>
                                        </button>
                                        <button type="button" class="btn-modern btn-modern-secondary" onclick="window.location.href='all_gift_certificate_setup.php'">
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

    <script src="https://js.stripe.com/v3/"></script>

    <script>
        // Initialize SumoSelect
        $('#PK_LOCATION').SumoSelect({
            placeholder: 'Select Locations',
            selectAll: true,
            captionFormat: '{0} locations selected',
            captionFormatAllSelected: 'All locations selected'
        });

        // Date pickers
        $('.datepicker-future').datepicker({
            format: 'mm/dd/yyyy',
            minDate: 0
        });

        $('.datepicker-normal').datepicker({
            format: 'mm/dd/yyyy',
        });

        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('gift_certificate_form');

            form.addEventListener('submit', function(e) {
                const name = document.getElementById('GIFT_CERTIFICATE_NAME');
                const code = document.getElementById('GIFT_CERTIFICATE_CODE');
                const minAmount = document.getElementById('MINIMUM_AMOUNT');
                const maxAmount = document.getElementById('MAXIMUM_AMOUNT');
                const effectiveDate = document.getElementById('EFFECTIVE_DATE');
                const endDate = document.getElementById('END_DATE');

                let isValid = true;

                // Validate Name
                if (!name.value.trim()) {
                    name.classList.add('is-invalid');
                    isValid = false;
                } else {
                    name.classList.remove('is-invalid');
                }

                // Validate Code
                if (!code.value.trim()) {
                    code.classList.add('is-invalid');
                    isValid = false;
                } else {
                    code.classList.remove('is-invalid');
                }

                // Validate Minimum Amount
                if (!minAmount.value || parseFloat(minAmount.value) < 0) {
                    minAmount.classList.add('is-invalid');
                    isValid = false;
                } else {
                    minAmount.classList.remove('is-invalid');
                }

                // Validate Maximum Amount
                if (!maxAmount.value || parseFloat(maxAmount.value) < 0) {
                    maxAmount.classList.add('is-invalid');
                    isValid = false;
                } else {
                    maxAmount.classList.remove('is-invalid');
                }

                // Validate that max >= min
                if (parseFloat(minAmount.value) > parseFloat(maxAmount.value)) {
                    maxAmount.classList.add('is-invalid');
                    isValid = false;
                    alert('Maximum amount must be greater than or equal to minimum amount.');
                }

                // Validate Dates
                if (!effectiveDate.value) {
                    effectiveDate.classList.add('is-invalid');
                    isValid = false;
                } else {
                    effectiveDate.classList.remove('is-invalid');
                }

                if (!endDate.value) {
                    endDate.classList.add('is-invalid');
                    isValid = false;
                } else {
                    endDate.classList.remove('is-invalid');
                }

                // Validate Location selection
                const selectedLocations = $('#PK_LOCATION').val();
                if (!selectedLocations || selectedLocations.length === 0) {
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