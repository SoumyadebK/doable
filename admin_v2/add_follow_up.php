<?php
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 1);
require_once('../global/config.php');

if (empty($_GET['id']))
    $title = "Add Automation";
else
    $title = "Edit Automation";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '') {
    header("location:../login.php");
    exit;
}

// Handle form submission
if (!empty($_POST)) {
    // Debug log
    file_put_contents('automation_debug.log', "=== FORM SUBMISSION ===\n", FILE_APPEND);
    file_put_contents('automation_debug.log', "POST keys: " . print_r(array_keys($_POST), true) . "\n", FILE_APPEND);

    // Extract the JSON data from POST
    $custom_reminders_json = isset($_POST['CUSTOM_REMINDERS']) ? $_POST['CUSTOM_REMINDERS'] : '';
    $messages_json = isset($_POST['MESSAGES']) ? $_POST['MESSAGES'] : '';

    file_put_contents('automation_debug.log', "CUSTOM_REMINDERS: $custom_reminders_json\n", FILE_APPEND);
    file_put_contents('automation_debug.log', "MESSAGES: " . substr($messages_json, 0, 200) . "\n", FILE_APPEND);

    // Remove them from the main POST data
    unset($_POST['CUSTOM_REMINDERS']);
    unset($_POST['MESSAGES']);

    $AUTOMATION_DATA = $_POST;
    $AUTOMATION_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];

    if (empty($_GET['id'])) {
        $AUTOMATION_DATA['IS_ACTIVE'] = isset($_POST['IS_ACTIVE']) ? 1 : 0;
        $AUTOMATION_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
        $AUTOMATION_DATA['CREATED_ON'] = date("Y-m-d H:i:s");
        $AUTOMATION_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
        $AUTOMATION_DATA['EDITED_ON'] = date("Y-m-d H:i:s");
        db_perform_account('DOA_AUTOMATIONS', $AUTOMATION_DATA, 'insert');
        $automation_id = $db_account->Insert_ID();
    } else {
        $AUTOMATION_DATA['IS_ACTIVE'] = isset($_POST['IS_ACTIVE']) ? 1 : 0;
        $AUTOMATION_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
        $AUTOMATION_DATA['EDITED_ON'] = date("Y-m-d H:i:s");
        db_perform_account('DOA_AUTOMATIONS', $AUTOMATION_DATA, 'update', " PK_AUTOMATION_ID = '$_GET[id]'");
        $automation_id = $_GET['id'];

        // Delete existing reminders and messages for update
        $db_account->Execute("DELETE FROM DOA_AUTOMATION_REMINDERS WHERE PK_AUTOMATION_ID = '$automation_id'");
        $db_account->Execute("DELETE FROM DOA_AUTOMATION_MESSAGES WHERE PK_AUTOMATION_ID = '$automation_id'");
    }

    // Insert custom reminders using direct SQL
    if (!empty($custom_reminders_json) && $custom_reminders_json != 'null' && $custom_reminders_json != '[]') {
        $custom_reminders = json_decode($custom_reminders_json, true);

        if (is_array($custom_reminders) && !empty($custom_reminders)) {
            foreach ($custom_reminders as $order => $reminder) {
                if (is_array($reminder) && isset($reminder['value'])) {
                    $is_enabled = isset($reminder['enabled']) && $reminder['enabled'] ? 1 : 0;
                    $value = intval($reminder['value']);
                    $unit = isset($reminder['unit']) ? $reminder['unit'] : 'Days';
                    $created_on = date("Y-m-d H:i:s");

                    $sql = "INSERT INTO DOA_AUTOMATION_REMINDERS (PK_AUTOMATION_ID, REMINDER_ORDER, IS_ENABLED, VALUE, UNIT, CREATED_ON, EDITED_ON) 
                            VALUES ('$automation_id', '$order', '$is_enabled', '$value', '$unit', '$created_on', '$created_on')";

                    $db_account->Execute($sql);
                }
            }
        }
    }

    // Insert messages using direct SQL
    if (!empty($messages_json) && $messages_json != 'null' && $messages_json != '[]') {
        $messages = json_decode($messages_json, true);

        if (is_array($messages) && !empty($messages)) {
            foreach ($messages as $index => $message_content) {
                if (!empty($message_content)) {
                    // Clean the content - remove extra whitespace and newlines
                    $clean_content = trim($message_content);
                    //$clean_content = mysqli_real_escape_string($db_account->connection, $clean_content);
                    $created_on = date("Y-m-d H:i:s");
                    $follow_up_num = $index + 1;

                    $sql = "INSERT INTO DOA_AUTOMATION_MESSAGES (PK_AUTOMATION_ID, FOLLOW_UP_NUMBER, MESSAGE_CONTENT, CREATED_ON, EDITED_ON) 
                            VALUES ('$automation_id', '$follow_up_num', '$clean_content', '$created_on', '$created_on')";

                    $db_account->Execute($sql);
                }
            }
        }
    }

    // Debug output
    if (isset($_GET['debug'])) {
        echo "<pre>";
        echo "Automation ID: $automation_id<br>";
        echo "Reminders JSON received: " . htmlspecialchars($custom_reminders_json) . "<br><br>";
        echo "Messages JSON received: " . htmlspecialchars(substr($messages_json, 0, 500)) . "<br><br>";

        // Verify the inserts
        $check_reminders = $db_account->Execute("SELECT COUNT(*) as count FROM DOA_AUTOMATION_REMINDERS WHERE PK_AUTOMATION_ID = '$automation_id'");
        $check_messages = $db_account->Execute("SELECT COUNT(*) as count FROM DOA_AUTOMATION_MESSAGES WHERE PK_AUTOMATION_ID = '$automation_id'");

        echo "<strong>Results:</strong><br>";
        echo "Reminders count in DB: " . ($check_reminders ? $check_reminders->fields['count'] : '0') . "<br>";
        echo "Messages count in DB: " . ($check_messages ? $check_messages->fields['count'] : '0') . "<br>";
        echo "</pre>";
        exit;
    }

    header("location:all_follow_ups.php");
    exit;
}

// Load automation data for editing
if (!empty($_GET['id'])) {
    $res = $db_account->Execute("SELECT * FROM `DOA_AUTOMATIONS` WHERE PK_AUTOMATION_ID = '$_GET[id]' AND PK_ACCOUNT_MASTER = '{$_SESSION['PK_ACCOUNT_MASTER']}'");
    if ($res->RecordCount() == 0) {
        header("location:all_follow_ups.php");
        exit;
    }
    $AUTOMATION = $res->fields;

    // Load reminders
    $reminders_res = $db_account->Execute("SELECT * FROM `DOA_AUTOMATION_REMINDERS` WHERE PK_AUTOMATION_ID = '$_GET[id]' ORDER BY REMINDER_ORDER");
    $CUSTOM_REMINDERS = array();
    if ($reminders_res && $reminders_res->RecordCount() > 0) {
        while (!$reminders_res->EOF) {
            $CUSTOM_REMINDERS[] = array(
                'enabled' => (bool)$reminders_res->fields['IS_ENABLED'],
                'value' => (int)$reminders_res->fields['VALUE'],
                'unit' => $reminders_res->fields['UNIT']
            );
            $reminders_res->MoveNext();
        }
    }

    // Load messages
    $messages_res = $db_account->Execute("SELECT * FROM `DOA_AUTOMATION_MESSAGES` WHERE PK_AUTOMATION_ID = '$_GET[id]' ORDER BY FOLLOW_UP_NUMBER");
    $MESSAGES = array();
    if ($messages_res && $messages_res->RecordCount() > 0) {
        while (!$messages_res->EOF) {
            $MESSAGES[] = $messages_res->fields['MESSAGE_CONTENT'];
            $messages_res->MoveNext();
        }
    }
} else {
    $AUTOMATION = array(
        'TITLE' => 'Trial Class Follow Up',
        'IS_ACTIVE' => 1,
        'TRIGGER_TYPE' => 'customer_completes_class',
        'TRIGGER_VALUE' => 'trial_class',
        'CONDITION_TYPE' => 'customer_not_purchased_contract',
        'SCHEDULE_TYPE' => 'custom',
        'START_REMINDER_VALUE' => 3,
        'START_REMINDER_UNIT' => 'Days',
        'MAX_REMINDERS' => 5,
        'NOTIFY_SERVICE_PROVIDER' => 1,
        'NOTIFY_STUDIO_MANAGER' => 1
    );
    $CUSTOM_REMINDERS = array(
        array('enabled' => true, 'value' => 3, 'unit' => 'Days'),
        array('enabled' => true, 'value' => 5, 'unit' => 'Days'),
        array('enabled' => true, 'value' => 3, 'unit' => 'Days'),
        array('enabled' => true, 'value' => 5, 'unit' => 'Days'),
        array('enabled' => true, 'value' => 3, 'unit' => 'Days')
    );
    $MESSAGES = array();
}
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'layout/header_script.php'; ?>
<?php require_once('../includes/header.php'); ?>
<?php include 'layout/header.php'; ?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/setup-styles.css" rel="stylesheet">
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: #f8f9fa;
            color: #1e293b;
        }

        /* Sidebar styling */
        .sidebar-card {
            background: #ffffff;
            border-radius: 20px;
            border: 1px solid #e9eef3;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
        }

        .sidebar-section {
            margin-bottom: 1.5rem;
        }

        .section-title {
            font-size: 0.7rem;
            font-weight: 700;
            color: #94a3b8;
            letter-spacing: 0.05em;
            margin-bottom: 0.75rem;
            padding-left: 0.5rem;
        }

        .sidebar-card .nav-link {
            color: #334155;
            font-size: 0.85rem;
            font-weight: 500;
            padding: 0.5rem 0.75rem;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.2s;
        }

        .sidebar-card .nav-link i,
        .sidebar-card .nav-link .dot-icon {
            font-size: 1.1rem;
            color: #5b6e8c;
        }

        .sidebar-card .nav-link.active {
            background-color: #ecfdf5;
            color: #10b981 !important;
            font-weight: 600;
        }

        .sidebar-card .nav-link.active i {
            color: #10b981;
        }

        /* main panel */
        .main-card {
            background: #ffffff;
            border-radius: 24px;
            border: 1px solid #e9eef3;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.02);
        }

        .form-label-custom {
            font-size: 0.85rem;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 0.4rem;
        }

        .form-control-custom,
        .form-select-custom {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            font-size: 0.9rem;
            padding: 0.6rem 0.9rem;
            background-color: #fefefe;
            transition: 0.2s;
        }

        .form-control-custom:focus,
        .form-select-custom:focus {
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
            background-color: #fff;
        }

        .form-control-inline,
        .form-select-inline {
            display: inline-block;
            width: auto;
            border-radius: 30px;
            border: 1px solid #e2e8f0;
            font-size: 0.85rem;
            padding: 0.3rem 0.9rem;
            background: #f9fafb;
        }

        input.form-control-inline[type="number"] {
            width: 75px;
        }

        .btn-pill-outline {
            background: transparent;
            border: 1px solid #cbd5e1;
            color: #1e293b;
            border-radius: 40px;
            font-size: 0.8rem;
            font-weight: 500;
            padding: 0.4rem 1.2rem;
            transition: all 0.2s;
        }

        .btn-pill-outline:hover {
            background-color: #f1f5f9;
            border-color: #94a3b8;
        }

        .btn-save-automation {
            background-color: #10b981;
            border: none;
            color: white;
            border-radius: 40px;
            font-weight: 600;
            padding: 0.7rem;
        }

        .btn-save-automation:hover {
            background-color: #059669;
        }

        .custom-switch .form-check-input {
            width: 2.3em;
            height: 1.25em;
            background-color: #cbd5e1;
            border-color: transparent;
            cursor: pointer;
        }

        .custom-switch .form-check-input:checked {
            background-color: #10b981;
        }

        .custom-switch .form-check-input:focus,
        .custom-checkbox .form-check-input:focus,
        .custom-radio .form-check-input:focus {
            box-shadow: none;
        }

        .custom-checkbox .form-check-input:checked {
            background-color: #10b981;
            border-color: #10b981;
        }

        .custom-radio .form-check-input:checked {
            background-color: #10b981;
            border-color: #10b981;
        }

        .variable-badge {
            background-color: #eef2ff;
            border-radius: 20px;
            padding: 0.2rem 0.6rem;
            font-size: 0.75rem;
            font-weight: 500;
            display: inline-block;
            margin: 0 2px;
            color: #1e40af;
        }

        .btn-variable-token {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 40px;
            font-size: 0.7rem;
            padding: 0.25rem 0.9rem;
        }

        .btn-variable-token:hover {
            background: #f1f5f9;
        }

        .custom-schedule-matrix {
            background: #fefefe;
            border-radius: 20px;
            padding: 0.5rem 0.25rem;
        }

        .reminder-row {
            transition: 0.1s;
        }

        .delete-reminder-btn {
            color: #94a3b8;
            font-size: 1rem;
        }

        .delete-reminder-btn:hover {
            color: #dc2626;
        }

        .extra-small {
            font-size: 0.7rem;
        }

        .accordion-button:focus {
            box-shadow: none;
            border-color: #e2e8f0;
        }

        .accordion-button:not(.collapsed) {
            background-color: #fafcff;
            color: #0f172a;
        }

        .editable-content-area {
            min-height: 85px;
            outline: none;
        }

        @media (max-width: 768px) {
            .custom-schedule-matrix .row {
                flex-wrap: wrap;
                margin-bottom: 12px;
            }
        }

        /* Make checkboxes visible */
        .form-check-input[type="checkbox"] {
            width: 1.2em;
            height: 1.2em;
            margin-top: 0;
            cursor: pointer;
            border: 1.5px solid #cbd5e1;
            background-color: white;
        }

        .form-check-input[type="checkbox"]:checked {
            background-color: #10b981;
            border-color: #10b981;
        }

        .form-check-input[type="checkbox"]:focus {
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
            border-color: #10b981;
        }

        /* Ensure the flex layout doesn't hide the checkboxes */
        .d-flex.align-items-center.gap-2 {
            display: flex !important;
            align-items: center !important;
            gap: 0.5rem !important;
        }
    </style>
</head>

<body>

    <div class="container-fluid py-4 px-4 dashboard-container">
        <div class="row g-4">
            <!-- Left Sidebar (similar style) -->
            <div class="col-12 col-md-4 col-xl-2">
                <?php include 'layout/setup_sidebar.php'; ?>
            </div>

            <!-- Main content -->
            <div class="col-12 col-md-8 col-lg-10">
                <div class="main-card p-4">
                    <div class="main-header border-bottom pb-3 mb-4 d-flex align-items-center gap-2">
                        <a href="all_follow_ups.php" class="text-dark text-decoration-none"><i class="bi bi-arrow-left fs-5 me-1"></i></a>
                        <h2 class="h5 mb-0 fw-semibold"><?= $title ?></h2>
                    </div>

                    <form id="automationForm" action="" method="post">
                        <input type="hidden" name="CUSTOM_REMINDERS" id="CUSTOM_REMINDERS" value='<?= htmlspecialchars(json_encode($CUSTOM_REMINDERS)) ?>'>
                        <input type="hidden" name="MESSAGES" id="MESSAGES" value='<?= htmlspecialchars(json_encode($MESSAGES)) ?>'>

                        <!-- Title & toggle -->
                        <div class="form-section row align-items-end mb-4">
                            <div class="col">
                                <label class="form-label-custom">Title</label>
                                <input type="text" class="form-control form-control-custom bg-light" value="<?= htmlspecialchars($AUTOMATION['TITLE']) ?>" id="TITLE" name="TITLE">
                            </div>
                            <div class="col-auto ps-0 pb-2">
                                <div class="form-check form-switch custom-switch d-flex align-items-center gap-2 m-0 p-0">
                                    <input class="form-check-input m-0" type="checkbox" role="switch" id="IS_ACTIVE" name="IS_ACTIVE" value="1" <?= $AUTOMATION['IS_ACTIVE'] ? 'checked' : '' ?>>
                                    <label class="form-check-label text-dark small fw-medium" for="IS_ACTIVE">On</label>
                                </div>
                            </div>
                        </div>

                        <!-- Triggers and conditions (static demo) -->
                        <div class="form-section mb-4">
                            <label class="form-label-custom">When this happens</label>
                            <div class="row g-2 mb-2">
                                <div class="col-12 col-sm-6">
                                    <select class="form-select form-select-custom bg-light" name="TRIGGER_TYPE" id="TRIGGER_TYPE">
                                        <option value="customer_completes_class" <?= $AUTOMATION['TRIGGER_TYPE'] == 'customer_completes_class' ? 'selected' : '' ?>>Customer completes a class</option>
                                    </select>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <select class="form-select form-select-custom bg-light" name="TRIGGER_VALUE" id="TRIGGER_VALUE">
                                        <option value="trial_class" <?= $AUTOMATION['TRIGGER_VALUE'] == 'trial_class' ? 'selected' : '' ?>>Trial class</option>
                                    </select>
                                </div>
                            </div>
                            <button type="button" class="btn btn-pill-outline mt-1">Add a trigger</button>
                        </div>
                        <div class="form-section mb-4">
                            <label class="form-label-custom">Only if</label>
                            <div class="row mb-2">
                                <div class="col-12 col-sm-6">
                                    <select class="form-select form-select-custom bg-light" name="CONDITION_TYPE" id="CONDITION_TYPE">
                                        <option value="customer_not_purchased_contract" <?= $AUTOMATION['CONDITION_TYPE'] == 'customer_not_purchased_contract' ? 'selected' : '' ?>>Customer has not purchased a contract</option>
                                    </select>
                                </div>
                            </div>
                            <button type="button" class="btn btn-pill-outline mt-1">Add a condition</button>
                        </div>
                        <div class="form-section mb-4">
                            <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                                <span class="text-dark small fw-medium">Start first reminder</span>
                                <input type="number" class="form-control form-control-inline bg-light text-center" value="<?= $AUTOMATION['START_REMINDER_VALUE'] ?>" id="START_REMINDER_VALUE" name="START_REMINDER_VALUE">
                                <select class="form-select form-select-inline bg-light" id="START_REMINDER_UNIT" name="START_REMINDER_UNIT">
                                    <option value="Days" <?= $AUTOMATION['START_REMINDER_UNIT'] == 'Days' ? 'selected' : '' ?>>Days</option>
                                    <option value="Hours" <?= $AUTOMATION['START_REMINDER_UNIT'] == 'Hours' ? 'selected' : '' ?>>Hours</option>
                                </select>
                            </div>
                            <span class="text-muted extra-small">If trigger and conditions are not met, nothing happens</span>
                        </div>
                        <div class="form-section mb-4">
                            <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                                <span class="text-dark small fw-medium">Send up to</span>
                                <input type="number" class="form-control form-control-inline bg-light text-center" value="<?= $AUTOMATION['MAX_REMINDERS'] ?>" id="MAX_REMINDERS" name="MAX_REMINDERS">
                                <span class="text-dark small fw-medium">reminders</span>
                            </div>
                            <span class="text-muted extra-small">Stops immediately once conditions are no longer met</span>
                        </div>

                        <!-- Schedule radio buttons + custom matrix area -->
                        <div class="form-section mb-4">
                            <label class="form-label-custom mb-2">Schedule</label>
                            <div class="d-flex flex-column gap-2 mb-3">
                                <div class="form-check custom-radio d-flex align-items-center gap-2">
                                    <input class="form-check-input" type="radio" name="SCHEDULE_TYPE" id="radioSimple" value="simple" <?= $AUTOMATION['SCHEDULE_TYPE'] == 'simple' ? 'checked' : '' ?>>
                                    <label class="form-check-label text-dark small fw-medium" for="radioSimple">Simple</label>
                                </div>
                                <div class="form-check custom-radio d-flex align-items-center gap-2">
                                    <input class="form-check-input" type="radio" name="SCHEDULE_TYPE" id="radioCustom" value="custom" <?= $AUTOMATION['SCHEDULE_TYPE'] == 'custom' ? 'checked' : '' ?>>
                                    <label class="form-check-label text-dark small fw-medium" for="radioCustom">Custom</label>
                                </div>
                            </div>

                            <!-- CUSTOM SCHEDULE MATRIX -->
                            <div id="customScheduleContainer" class="custom-schedule-matrix ms-0 ms-md-3 mt-3" style="display: <?= $AUTOMATION['SCHEDULE_TYPE'] == 'custom' ? 'block' : 'none' ?>;">
                                <div class="row text-muted extra-small fw-semibold mb-2 g-2 align-items-center">
                                    <div class="col-1">Reminder</div>
                                    <div class="col-1 text-center">Send</div>
                                    <div class="col-8">Timing</div>
                                    <div class="col-1"></div>
                                </div>
                                <div id="remindersList"></div>
                                <button type="button" id="addReminderBtn" class="btn btn-pill-outline mt-3">+ Add another reminder</button>
                            </div>
                        </div>

                        <!-- Who gets notified -->
                        <div class="form-section mb-4">
                            <label class="form-label-custom mb-2">Who gets notified</label>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <input type="checkbox" id="NOTIFY_SERVICE_PROVIDER" name="NOTIFY_SERVICE_PROVIDER" value="1" <?= $AUTOMATION['NOTIFY_SERVICE_PROVIDER'] ? 'checked' : '' ?> style="width: 20px; height: 20px;">
                                <label class="text-dark small" for="NOTIFY_SERVICE_PROVIDER">Service provider</label>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <input type="checkbox" id="NOTIFY_STUDIO_MANAGER" name="NOTIFY_STUDIO_MANAGER" value="1" <?= $AUTOMATION['NOTIFY_STUDIO_MANAGER'] ? 'checked' : '' ?> style="width: 20px; height: 20px;">
                                <label class="text-dark small" for="NOTIFY_STUDIO_MANAGER">Studio manager</label>
                            </div>
                        </div>

                        <!-- Message Templates Accordion -->
                        <div class="form-section mb-3">
                            <label class="form-label-custom mb-1">Message templates</label>
                            <p class="text-muted extra-small mb-2">Optionally provide example language. This will only appear in the To Do list item for the assigned team members.</p>
                        </div>
                        <div class="accordion custom-accordion mb-4" id="messagesAccordion"></div>

                        <div class="mt-4 text-center">
                            <button type="submit" class="btn btn-save-automation w-100 py-2 fw-semibold mb-3">Save Automation</button>
                            <?php if (!empty($_GET['id'])): ?>
                                <button type="button" id="deleteAutomationBtn" class="btn btn-link text-danger text-decoration-none small d-block mx-auto" onclick="if(confirm('Are you sure you want to delete this automation?')) window.location.href='delete_automation.php?id=<?= $_GET['id'] ?>'">Delete Automation</button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden inputs to store JSON data -->
    <input type="hidden" name="CUSTOM_REMINDERS" id="CUSTOM_REMINDERS" value='<?= htmlspecialchars(json_encode($CUSTOM_REMINDERS)) ?>'>
    <input type="hidden" name="MESSAGES" id="MESSAGES" value='<?= htmlspecialchars(json_encode($MESSAGES)) ?>'>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Parse existing data
        let reminders = <?= json_encode($CUSTOM_REMINDERS) ?>;
        let existingMessages = <?= json_encode($MESSAGES) ?>;

        // Add IDs to reminders for tracking (only for frontend)
        reminders = reminders.map((rem, idx) => ({
            ...rem,
            id: Date.now() + idx
        }));

        function renderReminders() {
            const container = document.getElementById('remindersList');
            if (!container) return;
            container.innerHTML = '';
            reminders.forEach((rem, idx) => {
                const rowDiv = document.createElement('div');
                rowDiv.className = 'row align-items-center g-2 mb-3 reminder-row';
                rowDiv.setAttribute('data-id', rem.id);
                rowDiv.innerHTML = `
                    <div class="col-1 text-dark small fw-medium ps-2">${idx+1}</div>
                    <div class="col-1 d-flex justify-content-center">
                        <div class="m-0 p-0">
                            <input class="reminder-enabled" type="checkbox" ${rem.enabled ? 'checked' : ''}>
                        </div>
                    </div>
                    <div class="col-2 d-flex align-items-center gap-2 flex-wrap">
                        <input type="number" class="form-control form-control-inline bg-light text-center reminder-value" value="${rem.value}" style="width:75px">
                        <select class="form-select form-select-inline bg-light reminder-unit" style="width:80px">
                            <option value="Days" ${rem.unit === 'Days' ? 'selected' : ''}>Days</option>
                            <option value="Hours" ${rem.unit === 'Hours' ? 'selected' : ''}>Hours</option>
                            <option value="Weeks" ${rem.unit === 'Weeks' ? 'selected' : ''}>Weeks</option>
                        </select>
                    </div>
                    <div class="col-1 text-center">
                        <button type="button" class="btn btn-link p-0 text-muted delete-reminder-btn"><i class="bi bi-trash3"></i></button>
                    </div>
                `;

                const delBtn = rowDiv.querySelector('.delete-reminder-btn');
                delBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    if (reminders.length <= 1) {
                        alert("At least one reminder is required.");
                        return;
                    }
                    reminders = reminders.filter(r => r.id !== rem.id);
                    renderReminders();
                    updateRemindersInput();
                });

                const enableChk = rowDiv.querySelector('.reminder-enabled');
                const valueInp = rowDiv.querySelector('.reminder-value');
                const unitSel = rowDiv.querySelector('.reminder-unit');
                enableChk.addEventListener('change', (e) => {
                    rem.enabled = e.target.checked;
                    updateRemindersInput();
                });
                valueInp.addEventListener('change', (e) => {
                    rem.value = parseInt(e.target.value) || 0;
                    updateRemindersInput();
                });
                unitSel.addEventListener('change', (e) => {
                    rem.unit = e.target.value;
                    updateRemindersInput();
                });
                container.appendChild(rowDiv);
            });
            updateRemindersInput();
        }

        function updateRemindersInput() {
            const remindersData = reminders.map(r => ({
                enabled: r.enabled,
                value: r.value,
                unit: r.unit
            }));
            const remindersJson = JSON.stringify(remindersData);
            document.getElementById('CUSTOM_REMINDERS').value = remindersJson;
            console.log('Reminders JSON updated:', remindersJson);
            return remindersJson;
        }

        function addNewReminder() {
            reminders.push({
                id: Date.now(),
                enabled: true,
                value: 1,
                unit: "Days"
            });
            renderReminders();
        }

        // handle schedule radio toggle
        const radioSimple = document.getElementById('radioSimple');
        const radioCustom = document.getElementById('radioCustom');
        const customContainerDiv = document.getElementById('customScheduleContainer');

        function toggleScheduleDisplay() {
            if (radioCustom.checked) {
                if (customContainerDiv) customContainerDiv.style.display = 'block';
            } else {
                if (customContainerDiv) customContainerDiv.style.display = 'none';
            }
        }

        if (radioSimple && radioCustom) {
            radioSimple.addEventListener('change', toggleScheduleDisplay);
            radioCustom.addEventListener('change', toggleScheduleDisplay);
            toggleScheduleDisplay();
        }

        document.getElementById('addReminderBtn')?.addEventListener('click', (e) => {
            e.preventDefault();
            addNewReminder();
        });

        function attachVariableButtons() {
            document.querySelectorAll('.var-btn').forEach(btn => {
                btn.removeEventListener('click', handleVariableInsert);
                btn.addEventListener('click', handleVariableInsert);
            });
        }

        function handleVariableInsert(e) {
            e.preventDefault();
            const varName = this.getAttribute('data-var');
            const accordBody = this.closest('.accordion-body');
            if (accordBody) {
                const editableDiv = accordBody.querySelector('.editable-content-area');
                if (editableDiv) {
                    const variableSpan = document.createElement('span');
                    variableSpan.className = 'variable-badge';
                    variableSpan.setAttribute('contenteditable', 'false');
                    variableSpan.innerText = varName;
                    editableDiv.focus();
                    const selection = window.getSelection();
                    const range = selection.getRangeAt(0);
                    range.deleteContents();
                    range.insertNode(variableSpan);
                    range.collapse(false);
                    const spaceNode = document.createTextNode('\u00A0');
                    range.insertNode(spaceNode);
                    range.collapse(false);
                    selection.removeAllRanges();
                    selection.addRange(range);
                    editableDiv.dispatchEvent(new Event('input'));
                    updateMessagesInput();
                }
            }
        }

        function updateMessagesInput() {
            const messages = [];
            const accordItems = document.querySelectorAll('#messagesAccordion .accordion-item');
            accordItems.forEach((item) => {
                const editableDiv = item.querySelector('.editable-content-area');
                messages.push(editableDiv ? editableDiv.innerHTML : '');
            });
            const messagesJson = JSON.stringify(messages);
            document.getElementById('MESSAGES').value = messagesJson;
            console.log('Messages JSON updated:', messagesJson);
            return messagesJson;
        }

        function buildAccordionItems(count) {
            const accordionContainer = document.getElementById('messagesAccordion');
            if (!accordionContainer) return;
            accordionContainer.innerHTML = '';
            const sampleTexts = [
                'Hi <span class="variable-badge" contenteditable="false">Student Name</span> this is <span class="variable-badge" contenteditable="false">Service Provider Name</span> at <span class="variable-badge" contenteditable="false">Location</span>. How are you? I was wondering if you\'d be interested in signing up for our winter class.',
                'Just following up again! <span class="variable-badge" contenteditable="false">Student Name</span>, we have limited spots. Let me know if you have any questions.',
                'Hello <span class="variable-badge" contenteditable="false">Student Name</span>, hope you enjoyed the trial! Feel free to reply to <span class="variable-badge" contenteditable="false">Service Provider Name</span>.',
                'Reminder: special offer ends soon at <span class="variable-badge" contenteditable="false">Location</span>. Don\'t miss out!',
                'Last call! <span class="variable-badge" contenteditable="false">Student Name</span>, we\'d love to see you in our upcoming sessions.'
            ];
            for (let i = 1; i <= count; i++) {
                const accordionItem = document.createElement('div');
                accordionItem.className = 'accordion-item mb-2 border rounded-3 overflow-hidden';
                const headerId = `headingFollow${i}`;
                const collapseId = `collapseFollow${i}`;
                const expanded = (i === 1);
                const messageContent = (existingMessages[i - 1] && existingMessages[i - 1] !== '') ? existingMessages[i - 1] : sampleTexts[(i - 1) % sampleTexts.length];

                accordionItem.innerHTML = `
                    <h2 class="accordion-header" id="${headerId}">
                        <button class="accordion-button ${expanded ? '' : 'collapsed'} fs-6 text-dark fw-medium py-2 px-3" type="button" data-bs-toggle="collapse" data-bs-target="#${collapseId}" aria-expanded="${expanded}" aria-controls="${collapseId}">
                            Follow up ${i}
                        </button>
                    </h2>
                    <div id="${collapseId}" class="accordion-collapse collapse ${expanded ? 'show' : ''}" aria-labelledby="${headerId}" data-bs-parent="#messagesAccordion">
                        <div class="accordion-body p-3 pt-1">
                            <div class="textarea-container p-2 border rounded-2 mb-2 bg-white">
                                <div class="editable-content-area" contenteditable="true" data-msg-index="${i}">
                                    ${messageContent}
                                </div>
                            </div>
                            <div class="variables-section">
                                <span class="text-muted extra-small d-block mb-1">Insert Variables</span>
                                <div class="d-flex flex-wrap gap-1">
                                    <button type="button" class="btn btn-variable-token var-btn" data-var="Student Name">Student Name</button>
                                    <button type="button" class="btn btn-variable-token var-btn" data-var="Location">Location</button>
                                    <button type="button" class="btn btn-variable-token var-btn" data-var="Service Provider Name">Service Provider Name</button>
                                    <button type="button" class="btn btn-variable-token var-btn" data-var="Corporation Name">Corporation Name</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                accordionContainer.appendChild(accordionItem);
            }
            attachVariableButtons();

            // Add input event listeners to update messages
            document.querySelectorAll('.editable-content-area').forEach(el => {
                el.addEventListener('input', updateMessagesInput);
            });
            updateMessagesInput();
        }

        const maxRemindersInput = document.getElementById('MAX_REMINDERS');

        function updateAccordionCount() {
            let maxVal = parseInt(maxRemindersInput.value);
            if (isNaN(maxVal) || maxVal < 1) maxVal = 1;
            if (maxVal > 20) maxVal = 20;
            buildAccordionItems(maxVal);
        }

        if (maxRemindersInput) {
            maxRemindersInput.addEventListener('change', updateAccordionCount);
            // Set initial value from existing messages or max_reminders
            const initialCount = (existingMessages.length > 0) ? existingMessages.length : maxRemindersInput.value;
            maxRemindersInput.value = initialCount;
            buildAccordionItems(parseInt(initialCount));
        } else {
            buildAccordionItems(5);
        }

        // Update hidden inputs before form submit
        document.getElementById('automationForm').addEventListener('submit', function(e) {
            console.log('Form submitting - updating hidden inputs...');

            // Update both hidden inputs
            const remindersJson = updateRemindersInput();
            const messagesJson = updateMessagesInput();

            // Verify they have values
            if (!remindersJson || remindersJson === '[]') {
                console.warn('No reminders data!');
            }
            if (!messagesJson || messagesJson === '[]') {
                console.warn('No messages data!');
            }

            // Log the values being submitted
            console.log('CUSTOM_REMINDERS value:', document.getElementById('CUSTOM_REMINDERS').value);
            console.log('MESSAGES value:', document.getElementById('MESSAGES').value);

            // Allow the form to submit normally
            return true;
        });

        // Initialize
        renderReminders();
        window.addEventListener('load', () => {
            renderReminders();
            toggleScheduleDisplay();
        });
    </script>
</body>

</html>