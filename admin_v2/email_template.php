<?php
require_once('../global/config.php');

if (empty($_GET['id']))
    $title = "Add Email Template";
else
    $title = "Edit Email Template";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

if (!empty($_POST)) {
    $EMAIL_ACCOUNT_DATA = $_POST;
    unset($EMAIL_ACCOUNT_DATA['TEMP_CONTENT']);
    $EMAIL_ACCOUNT_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
    if ($_GET['id'] == '') {
        $EMAIL_ACCOUNT_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
        $EMAIL_ACCOUNT_DATA['CREATED_ON'] = date("Y-m-d H:i");
        db_perform_account('DOA_EMAIL_TEMPLATE', $EMAIL_ACCOUNT_DATA, 'insert');
        header("location:all_email_templates.php");
    } else {
        $EMAIL_ACCOUNT_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
        $EMAIL_ACCOUNT_DATA['EDITED_ON'] = date("Y-m-d H:i");
        db_perform_account('DOA_EMAIL_TEMPLATE', $EMAIL_ACCOUNT_DATA, 'update', " PK_EMAIL_TEMPLATE = '$_GET[id]'");
        header("location:all_email_templates.php");
    }
}

if (empty($_GET['id'])) {
    $TEMPLATE_NAME      = '';
    $SUBJECT            = '';
    $PK_TEMPLATE_CATEGORY = '';
    $PK_EMAIL_TRIGGER     = '';
    $PK_EMAIL_ACCOUNT   = '';
    $CONTENT            = '';
    $ACTIVE             = '';
} else {
    $res = $db_account->Execute("SELECT * FROM DOA_EMAIL_TEMPLATE WHERE PK_EMAIL_TEMPLATE = '$_GET[id]'");
    if ($res->RecordCount() == 0) {
        header("location:all_email_templates.php");
        exit;
    }
    $TEMPLATE_NAME      = $res->fields['TEMPLATE_NAME'];
    $SUBJECT            = $res->fields['SUBJECT'];
    $PK_TEMPLATE_CATEGORY = $res->fields['PK_TEMPLATE_CATEGORY'];
    $PK_EMAIL_TRIGGER     = $res->fields['PK_EMAIL_TRIGGER'];
    $PK_EMAIL_ACCOUNT   = $res->fields['PK_EMAIL_ACCOUNT'];
    $CONTENT            = $res->fields['CONTENT'];
    $ACTIVE             = $res->fields['ACTIVE'];
}
?>

<!DOCTYPE html>
<html lang="en">
<?php include 'layout/header_script.php'; ?>
<?php require_once('../includes/header.php'); ?>
<?php include 'layout/header.php'; ?>

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

    /* Quill Editor */
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

    /* Full width for editor */
    .full-width {
        grid-column: 1 / -1;
    }

    /* Status indicator */
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

    /* Help text */
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
                <div class="row">
                    <div class="col-12">
                        <div class="card-modern">
                            <div class="card-header">
                                <h5>
                                    <i class="fas fa-file-alt"></i>
                                    <?= !empty($_GET['id']) ? 'Edit Template' : 'Create New Template' ?>
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
                                        <!-- Template Name -->
                                        <div class="form-group-modern">
                                            <label class="form-label">Template Name <span class="required">*</span></label>
                                            <input type="text" class="form-control-modern" id="TEMPLATE_NAME" name="TEMPLATE_NAME" placeholder="Enter template name" value="<?php echo htmlspecialchars($TEMPLATE_NAME) ?>" required>
                                            <div class="form-helper">A unique name to identify this template</div>
                                        </div>

                                        <!-- Subject -->
                                        <div class="form-group-modern">
                                            <label class="form-label">Subject <span class="required">*</span></label>
                                            <input type="text" class="form-control-modern" id="SUBJECT" name="SUBJECT" placeholder="Enter email subject" value="<?php echo htmlspecialchars($SUBJECT) ?>" required>
                                            <div class="form-helper">The subject line that will appear in the email</div>
                                        </div>

                                        <!-- Template Category -->
                                        <div class="form-group-modern">
                                            <label class="form-label">Template Category <span class="required">*</span></label>
                                            <select id="PK_TEMPLATE_CATEGORY" name="PK_TEMPLATE_CATEGORY" class="form-control-modern" onchange="selectTemplateCategory(this)" required>
                                                <option value="">Select Category</option>
                                                <?php
                                                $row = $db->Execute("SELECT PK_TEMPLATE_CATEGORY, TEMPLATE_CATEGORY FROM DOA_TEMPLATE_CATEGORY WHERE ACTIVE = 1");
                                                while (!$row->EOF) {
                                                    $selected = '';
                                                    if ($PK_TEMPLATE_CATEGORY != '' && $PK_TEMPLATE_CATEGORY == $row->fields['PK_TEMPLATE_CATEGORY']) {
                                                        $selected = 'selected';
                                                    }
                                                ?>
                                                    <option value="<?php echo $row->fields['PK_TEMPLATE_CATEGORY']; ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($row->fields['TEMPLATE_CATEGORY']); ?></option>
                                                <?php $row->MoveNext();
                                                } ?>
                                            </select>
                                        </div>

                                        <!-- Email Trigger -->
                                        <div class="form-group-modern" id="email_event_div" style="display: <?= ($PK_TEMPLATE_CATEGORY == 1) ? 'flex' : 'none' ?>;">
                                            <label class="form-label">Email Trigger</label>
                                            <select id="PK_EMAIL_TRIGGER" name="PK_EMAIL_TRIGGER" class="form-control-modern">
                                                <option value="">Select Trigger Event</option>
                                                <?php
                                                $row = $db->Execute("SELECT PK_EMAIL_TRIGGER, EMAIL_TRIGGER FROM DOA_EMAIL_TRIGGER WHERE ACTIVE = 1");
                                                while (!$row->EOF) {
                                                    $selected = '';
                                                    if ($PK_EMAIL_TRIGGER != '' && $PK_EMAIL_TRIGGER == $row->fields['PK_EMAIL_TRIGGER']) {
                                                        $selected = 'selected';
                                                    }
                                                ?>
                                                    <option value="<?php echo $row->fields['PK_EMAIL_TRIGGER']; ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($row->fields['EMAIL_TRIGGER']); ?></option>
                                                <?php $row->MoveNext();
                                                } ?>
                                            </select>
                                            <div class="form-helper">Select the event that will trigger this email</div>
                                        </div>

                                        <!-- Email Account -->
                                        <div class="form-group-modern">
                                            <label class="form-label">Email Account <span class="required">*</span></label>
                                            <select id="PK_EMAIL_ACCOUNT" name="PK_EMAIL_ACCOUNT" class="form-control-modern" required>
                                                <option value="">Select Email Account</option>
                                                <?php
                                                $row = $db_account->Execute("SELECT PK_EMAIL_ACCOUNT, USER_NAME FROM DOA_EMAIL_ACCOUNT WHERE PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND ACTIVE = 1");
                                                while (!$row->EOF) {
                                                    $selected = '';
                                                    if ($PK_EMAIL_ACCOUNT != '' && $PK_EMAIL_ACCOUNT == $row->fields['PK_EMAIL_ACCOUNT']) {
                                                        $selected = 'selected';
                                                    }
                                                ?>
                                                    <option value="<?php echo $row->fields['PK_EMAIL_ACCOUNT']; ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($row->fields['USER_NAME']); ?></option>
                                                <?php $row->MoveNext();
                                                } ?>
                                            </select>
                                            <div class="form-helper">The email account used to send this template</div>
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

                                        <!-- Email Content - Full Width -->
                                        <div class="form-group-modern full-width">
                                            <label class="form-label">Email Content <span class="required">*</span></label>
                                            <div class="quill-wrapper">
                                                <div id="editor" style="min-height: 300px;"></div>
                                            </div>
                                            <input type="hidden" name="CONTENT" id="CONTENT">
                                            <textarea name="TEMP_CONTENT" id="TEMP_CONTENT" style="display:none;"><?= htmlspecialchars($CONTENT) ?></textarea>
                                            <div class="form-helper">Use the toolbar above to format your email content</div>
                                        </div>

                                        <!-- Hidden fields -->
                                        <?php if (!empty($_GET['id'])): ?>
                                            <input type="hidden" name="PK_EMAIL_TEMPLATE" value="<?php echo $_GET['id'] ?>">
                                        <?php endif; ?>
                                    </div>

                                    <!-- Form Actions -->
                                    <div class="form-actions">
                                        <button type="submit" class="btn-modern btn-modern-primary">
                                            <i class="fas fa-save"></i>
                                            <?php if (empty($_GET['id'])): ?>
                                                Create Template
                                            <?php else: ?>
                                                Update Template
                                            <?php endif; ?>
                                        </button>
                                        <button type="button" class="btn-modern btn-modern-secondary" onclick="window.location.href='all_email_templates.php'">
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
            placeholder: 'Write your email content here...',
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

        // Template Category toggle
        function selectTemplateCategory(param) {
            const emailEventDiv = document.getElementById('email_event_div');
            if ($(param).val() == 1) {
                $(emailEventDiv).slideDown();
            } else {
                $(emailEventDiv).slideUp();
            }
        }

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const templateName = document.getElementById('TEMPLATE_NAME');
            const subject = document.getElementById('SUBJECT');
            const content = quill.root.innerHTML.trim();

            let isValid = true;

            if (!templateName.value.trim()) {
                templateName.classList.add('is-invalid');
                isValid = false;
            } else {
                templateName.classList.remove('is-invalid');
            }

            if (!subject.value.trim()) {
                subject.classList.add('is-invalid');
                isValid = false;
            } else {
                subject.classList.remove('is-invalid');
            }

            if (!content || content === '<p><br></p>' || content === '<p><br class="ql-cursor"></p>') {
                // Show error for empty content
                document.querySelector('.quill-wrapper').style.borderColor = 'var(--danger-color)';
                isValid = false;
                // Add a visual indicator
                const helper = document.querySelector('.form-helper:last-of-type');
                if (helper) {
                    helper.style.color = 'var(--danger-color)';
                    helper.textContent = 'Please enter email content';
                    setTimeout(() => {
                        helper.style.color = 'var(--gray-400)';
                        helper.textContent = 'Use the toolbar above to format your email content';
                    }, 3000);
                }
            } else {
                document.querySelector('.quill-wrapper').style.borderColor = 'var(--gray-200)';
            }

            if (!isValid) {
                e.preventDefault();
                // Scroll to first error
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
    </script>

</body>

</html>