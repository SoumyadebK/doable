<?php
require_once('../global/config.php');

if (empty($_GET['id']))
    $title = "Add Lead Status";
else
    $title = "Edit Lead Status";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '') {
    header("location:../login.php");
    exit;
}

if (!empty($_POST)) {
    $LEAD_STATUS_DATA = $_POST;
    $LEAD_STATUS_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
    if (empty($_GET['id'])) {
        $LEAD_STATUS_DATA['ACTIVE'] = 1;
        $LEAD_STATUS_DATA['CREATED_BY']  = $_SESSION['PK_USER'];
        $LEAD_STATUS_DATA['CREATED_ON']  = date("Y-m-d H:i");
        db_perform('DOA_LEAD_STATUS', $LEAD_STATUS_DATA, 'insert');
    } else {
        $LEAD_STATUS_DATA['ACTIVE'] = $_POST['ACTIVE'];
        $LEAD_STATUS_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
        $LEAD_STATUS_DATA['EDITED_ON'] = date("Y-m-d H:i");
        db_perform('DOA_LEAD_STATUS', $LEAD_STATUS_DATA, 'update', " PK_LEAD_STATUS =  '$_GET[id]'");
    }
    header("location:all_lead_status.php");
}

if (empty($_GET['id'])) {
    $LEAD_STATUS = '';
    $ACTIVE = '';
    $STATUS_COLOR = '#39B54A';
    $DISPLAY_ORDER = '';
    $TEXT_MESSAGE = '';
    $EMAIL_MESSAGE = '';
    $SEND_AFTER_DAYS = '';
} else {
    $res = $db->Execute("SELECT * FROM `DOA_LEAD_STATUS` WHERE PK_LEAD_STATUS = '$_GET[id]'");
    if ($res->RecordCount() == 0) {
        header("location:all_lead_status.php");
        exit;
    }
    $LEAD_STATUS = $res->fields['LEAD_STATUS'];
    $ACTIVE = $res->fields['ACTIVE'];
    $STATUS_COLOR = $res->fields['STATUS_COLOR'];
    $DISPLAY_ORDER = $res->fields['DISPLAY_ORDER'];
    $TEXT_MESSAGE = $res->fields['TEXT_MESSAGE'];
    $EMAIL_MESSAGE = $res->fields['EMAIL_MESSAGE'];
    $SEND_AFTER_DAYS = $res->fields['SEND_AFTER_DAYS'];
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
        --primary-dark: #2D8F3B;
        --primary-rgb: 57, 181, 74;
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
        --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
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

    .form-control-modern.is-invalid:focus {
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
    }

    textarea.form-control-modern {
        min-height: 100px;
        resize: vertical;
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
        accent-color: var(--primary-color);
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

    .form-helper {
        font-size: 12px;
        color: var(--gray-400);
        margin-top: 4px;
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
                                            <i class="bi bi-dot"></i>
                                            <?= !empty($_GET['id']) ? 'Edit Lead Status' : 'Add Lead Status' ?>
                                        </h5>
                                        <?php if (!empty($_GET['id'])): ?>
                                            <span class="status-indicator <?= ($ACTIVE == 1) ? 'active' : 'inactive' ?>">
                                                <i class="fas fa-circle"></i>
                                                <?= ($ACTIVE == 1) ? 'Active' : 'Inactive' ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-body">
                                        <form class="form-material form-horizontal m-t-30" name="form1" id="form1" action="" method="post" enctype="multipart/form-data">

                                            <div class="form-grid">
                                                <!-- Status Name -->
                                                <div class="form-group-modern">
                                                    <label class="form-label">Status Name <span class="required">*</span></label>
                                                    <input type="text" class="form-control-modern" id="LEAD_STATUS" name="LEAD_STATUS" placeholder="Enter Status Name" value="<?php echo htmlspecialchars($LEAD_STATUS) ?>" <?= ($LEAD_STATUS == 'New') ? 'readonly' : '' ?> required>
                                                    <div class="form-helper">Enter the name of the lead status</div>
                                                </div>

                                                <!-- Status Color -->
                                                <div class="form-group-modern">
                                                    <label class="form-label">Status Color <span class="required">*</span></label>
                                                    <div class="color-picker-wrapper">
                                                        <input type="color" id="STATUS_COLOR" name="STATUS_COLOR" value="<?php echo $STATUS_COLOR ?>">
                                                        <!-- <div class="color-preview" id="colorPreview" style="background-color: <?php echo $STATUS_COLOR ?>;"></div> -->
                                                        <span class="color-hex" id="colorHex"><?php echo $STATUS_COLOR ?></span>
                                                    </div>
                                                    <div class="form-helper">Choose a color to identify this lead status</div>
                                                </div>

                                                <!-- Display Order -->
                                                <div class="form-group-modern">
                                                    <label class="form-label">Display Order <span class="required">*</span></label>
                                                    <input type="text" class="form-control-modern" id="DISPLAY_ORDER" name="DISPLAY_ORDER" placeholder="Enter Display Order" value="<?php echo htmlspecialchars($DISPLAY_ORDER) ?>" required>
                                                    <div class="form-helper">Numerical order for displaying this status</div>
                                                </div>

                                                <!-- Send After Days -->
                                                <div class="form-group-modern">
                                                    <label class="form-label">Send After Days</label>
                                                    <input type="text" class="form-control-modern" id="SEND_AFTER_DAYS" name="SEND_AFTER_DAYS" placeholder="Enter days" value="<?php echo htmlspecialchars($SEND_AFTER_DAYS) ?>">
                                                    <div class="form-helper">Number of days after which to send the message</div>
                                                </div>

                                                <!-- Text Message -->
                                                <div class="form-group-modern">
                                                    <label class="form-label">Text Message</label>
                                                    <textarea class="form-control-modern" id="TEXT_MESSAGE" name="TEXT_MESSAGE" placeholder="Enter Text Message" rows="5"><?php echo htmlspecialchars($TEXT_MESSAGE) ?></textarea>
                                                    <div class="form-helper">SMS message to send when this status is applied</div>
                                                </div>

                                                <!-- Email Message -->
                                                <div class="form-group-modern">
                                                    <label class="form-label">Email Message</label>
                                                    <textarea class="form-control-modern" id="EMAIL_MESSAGE" name="EMAIL_MESSAGE" placeholder="Enter Email Message" rows="5"><?php echo htmlspecialchars($EMAIL_MESSAGE) ?></textarea>
                                                    <div class="form-helper">Email message to send when this status is applied</div>
                                                </div>

                                                <!-- Active Status -->
                                                <?php if (!empty($_GET['id'])): ?>
                                                    <div class="form-group-modern full-width" style="<?php if ($LEAD_STATUS == 'New') echo 'display:none;'; ?>">
                                                        <label class="form-label">Active</label>
                                                        <div class="radio-group-modern">
                                                            <label class="radio-item">
                                                                <input type="radio" name="ACTIVE" id="ACTIVE_YES" value="1" <?php if ($ACTIVE == 1) echo 'checked="checked"'; ?>> Active
                                                            </label>
                                                            <label class="radio-item">
                                                                <input type="radio" name="ACTIVE" id="ACTIVE_NO" value="0" <?php if ($ACTIVE == 0) echo 'checked="checked"'; ?>> Inactive
                                                            </label>
                                                        </div>
                                                        <div class="form-helper">Set the status of this lead status</div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Form Actions -->
                                            <div class="form-actions">
                                                <button type="submit" class="btn-modern btn-modern-primary">
                                                    <i class="fas fa-save"></i>
                                                    <?php if (empty($_GET['id'])): ?>
                                                        Create Lead Status
                                                    <?php else: ?>
                                                        Update Lead Status
                                                    <?php endif; ?>
                                                </button>
                                                <button type="button" class="btn-modern btn-modern-secondary" onclick="window.location.href='all_lead_status.php'">
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
</body>

<script>
    // Color picker live preview
    document.addEventListener('DOMContentLoaded', function() {
        const colorInput = document.getElementById('STATUS_COLOR');
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
        const form = document.getElementById('form1');
        if (form) {
            const leadStatus = document.getElementById('LEAD_STATUS');
            const statusColor = document.getElementById('STATUS_COLOR');
            const displayOrder = document.getElementById('DISPLAY_ORDER');

            form.addEventListener('submit', function(e) {
                let isValid = true;

                if (!leadStatus.value.trim()) {
                    leadStatus.classList.add('is-invalid');
                    isValid = false;
                } else {
                    leadStatus.classList.remove('is-invalid');
                }

                if (!statusColor.value) {
                    statusColor.classList.add('is-invalid');
                    isValid = false;
                } else {
                    statusColor.classList.remove('is-invalid');
                }

                if (!displayOrder.value.trim()) {
                    displayOrder.classList.add('is-invalid');
                    isValid = false;
                } else {
                    displayOrder.classList.remove('is-invalid');
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