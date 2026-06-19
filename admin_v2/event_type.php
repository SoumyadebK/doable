<?php
require_once('../global/config.php');

if (empty($_GET['id']))
    $title = "Add Event Type";
else
    $title = "Edit Event Type";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

if (!empty($_POST)) {
    $EVENT_TYPE_DATA = $_POST;
    $EVENT_TYPE_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
    if (empty($_GET['id'])) {
        $EVENT_TYPE_DATA['ACTIVE'] = 1;
        $EVENT_TYPE_DATA['CREATED_BY']  = $_SESSION['PK_USER'];
        $EVENT_TYPE_DATA['CREATED_ON']  = date("Y-m-d H:i");
        db_perform_account('DOA_EVENT_TYPE', $EVENT_TYPE_DATA, 'insert');
    } else {
        $EVENT_TYPE_DATA['ACTIVE'] = $_POST['ACTIVE'];
        $EVENT_TYPE_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
        $EVENT_TYPE_DATA['EDITED_ON'] = date("Y-m-d H:i");
        db_perform_account('DOA_EVENT_TYPE', $EVENT_TYPE_DATA, 'update', " PK_EVENT_TYPE =  '$_GET[id]'");
    }
    header("location:all_event_types.php");
}

if (empty($_GET['id'])) {
    $EVENT_TYPE = '';
    $COLOR_CODE = '#39B54A';
    $ACTIVE = '';
} else {
    $res = $db_account->Execute("SELECT * FROM `DOA_EVENT_TYPE` WHERE PK_EVENT_TYPE = '$_GET[id]'");
    if ($res->RecordCount() == 0) {
        header("location:all_event_types.php");
        exit;
    }
    $EVENT_TYPE = $res->fields['EVENT_TYPE'];
    $COLOR_CODE = $res->fields['COLOR_CODE'];
    $ACTIVE = $res->fields['ACTIVE'];
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
        gap: 24px 32px;
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

    /* Color Picker */
    .color-picker-wrapper {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .color-picker-wrapper input[type="color"] {
        width: 75px;
        height: 44px;
        padding: 0px;
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
        width: 10px;
        height: 10px;
        border-radius: var(--radius-sm);
        border: 0px solid var(--gray-200);
        transition: background-color 0.3s;
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
    }

    /* Form helper */
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
                                    <i class="fas fa-tag"></i>
                                    <?= !empty($_GET['id']) ? 'Edit Event Type' : 'Create New Event Type' ?>
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
                                        <!-- Event Type -->
                                        <div class="form-group-modern">
                                            <label class="form-label">Event Type <span class="required">*</span></label>
                                            <input type="text" id="EVENT_TYPE" name="EVENT_TYPE" class="form-control-modern" placeholder="Enter Event Type" value="<?php echo htmlspecialchars($EVENT_TYPE) ?>" required>
                                            <div class="form-helper">A unique name for this event type</div>
                                        </div>

                                        <!-- Color Code -->
                                        <div class="form-group-modern">
                                            <label class="form-label">Color Code <span class="required">*</span></label>
                                            <div class="color-picker-wrapper">
                                                <input type="color" id="COLOR_CODE" name="COLOR_CODE" value="<?php echo $COLOR_CODE ?: '#39B54A' ?>">
                                                <div class="color-preview" id="colorPreview" style="background-color: <?php echo $COLOR_CODE ?: '#39B54A' ?>;"></div>
                                                <span class="color-hex" id="colorHex"><?php echo $COLOR_CODE ?: '#39B54A' ?></span>
                                            </div>
                                            <div class="form-helper">Choose a color to identify this event type</div>
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
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Form Actions -->
                                    <div class="form-actions">
                                        <button type="submit" class="btn-modern btn-modern-primary">
                                            <i class="fas fa-save"></i>
                                            <?php if (empty($_GET['id'])): ?>
                                                Create Event Type
                                            <?php else: ?>
                                                Update Event Type
                                            <?php endif; ?>
                                        </button>
                                        <button type="button" class="btn-modern btn-modern-secondary" onclick="window.location.href='all_event_types.php'">
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
                    const eventType = document.getElementById('EVENT_TYPE');
                    const colorCode = document.getElementById('COLOR_CODE');

                    let isValid = true;

                    if (!eventType.value.trim()) {
                        eventType.classList.add('is-invalid');
                        isValid = false;
                    } else {
                        eventType.classList.remove('is-invalid');
                    }

                    if (!colorCode.value) {
                        colorCode.classList.add('is-invalid');
                        isValid = false;
                    } else {
                        colorCode.classList.remove('is-invalid');
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

</body>

</html>