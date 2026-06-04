<?php
require_once('../global/config.php');
//session_start();

// Check if user is logged in
if (!isset($_SESSION['PK_USER'])) {
    header('Location: ../login.php');
    exit;
}

$PK_ACCOUNT_MASTER = $_SESSION['PK_ACCOUNT_MASTER'] ?? 0;
$user_id = $_SESSION['PK_USER'];
$current_time = date('Y-m-d H:i:s');

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    header('Content-Type: application/json');

    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    // Save automation
    if ($action === 'save') {
        try {
            $conn = db_connect();
            $input = json_decode(file_get_contents('php://input'), true);

            if (!$input) {
                throw new Exception('Invalid input data');
            }

            $automation_id = $input['automation_id'] ?? 0;
            $title = mysqli_real_escape_string($conn, $input['title']);
            $is_active = $input['is_active'] ? 1 : 0;
            $schedule_type = mysqli_real_escape_string($conn, $input['schedule_type']);
            $start_reminder_value = intval($input['start_reminder_value']);
            $start_reminder_unit = mysqli_real_escape_string($conn, $input['start_reminder_unit']);
            $max_reminders = intval($input['max_reminders']);
            $notify_service_provider = $input['notify_service_provider'] ? 1 : 0;
            $notify_studio_manager = $input['notify_studio_manager'] ? 1 : 0;
            $trigger_type = mysqli_real_escape_string($conn, $input['trigger_type'] ?? 'customer_completes_class');
            $trigger_value = mysqli_real_escape_string($conn, $input['trigger_value'] ?? 'trial_class');
            $condition_type = mysqli_real_escape_string($conn, $input['condition_type'] ?? 'customer_not_purchased_contract');
            $condition_value = mysqli_real_escape_string($conn, json_encode($input['condition_value'] ?? []));
            $custom_reminders = $input['custom_reminders'] ?? [];
            $messages = $input['messages'] ?? [];

            mysqli_begin_transaction($conn);

            if ($automation_id > 0) {
                $query = "UPDATE DOA_AUTOMATIONS SET 
                            TITLE = '$title',
                            IS_ACTIVE = $is_active,
                            TRIGGER_TYPE = '$trigger_type',
                            TRIGGER_VALUE = '$trigger_value',
                            CONDITION_TYPE = '$condition_type',
                            CONDITION_VALUE = '$condition_value',
                            SCHEDULE_TYPE = '$schedule_type',
                            START_REMINDER_VALUE = $start_reminder_value,
                            START_REMINDER_UNIT = '$start_reminder_unit',
                            MAX_REMINDERS = $max_reminders,
                            NOTIFY_SERVICE_PROVIDER = $notify_service_provider,
                            NOTIFY_STUDIO_MANAGER = $notify_studio_manager,
                            EDITED_ON = '$current_time',
                            EDITED_BY = $user_id
                          WHERE PK_AUTOMATION_ID = $automation_id 
                          AND PK_ACCOUNT_MASTER = $PK_ACCOUNT_MASTER";

                if (!mysqli_query($conn, $query)) {
                    throw new Exception('Failed to update automation: ' . mysqli_error($conn));
                }

                mysqli_query($conn, "DELETE FROM DOA_AUTOMATION_REMINDERS WHERE PK_AUTOMATION_ID = $automation_id");
                mysqli_query($conn, "DELETE FROM DOA_AUTOMATION_MESSAGES WHERE PK_AUTOMATION_ID = $automation_id");
            } else {
                $query = "INSERT INTO DOA_AUTOMATIONS (
                            PK_ACCOUNT_MASTER, TITLE, IS_ACTIVE, TRIGGER_TYPE, TRIGGER_VALUE,
                            CONDITION_TYPE, CONDITION_VALUE, SCHEDULE_TYPE, START_REMINDER_VALUE,
                            START_REMINDER_UNIT, MAX_REMINDERS, NOTIFY_SERVICE_PROVIDER,
                            NOTIFY_STUDIO_MANAGER, CREATED_ON, CREATED_BY, EDITED_ON, EDITED_BY
                          ) VALUES (
                            $PK_ACCOUNT_MASTER, '$title', $is_active, '$trigger_type', '$trigger_value',
                            '$condition_type', '$condition_value', '$schedule_type', $start_reminder_value,
                            '$start_reminder_unit', $max_reminders, $notify_service_provider,
                            $notify_studio_manager, '$current_time', $user_id, '$current_time', $user_id
                          )";

                if (!mysqli_query($conn, $query)) {
                    throw new Exception('Failed to create automation: ' . mysqli_error($conn));
                }
                $automation_id = mysqli_insert_id($conn);
            }

            if ($schedule_type === 'custom' && !empty($custom_reminders)) {
                foreach ($custom_reminders as $order => $reminder) {
                    $is_enabled = $reminder['enabled'] ? 1 : 0;
                    $value = intval($reminder['value']);
                    $unit = mysqli_real_escape_string($conn, $reminder['unit']);

                    $query = "INSERT INTO DOA_AUTOMATION_REMINDERS (
                                PK_AUTOMATION_ID, REMINDER_ORDER, IS_ENABLED, VALUE, UNIT, 
                                CREATED_ON, EDITED_ON
                              ) VALUES (
                                $automation_id, $order, $is_enabled, $value, '$unit',
                                '$current_time', '$current_time'
                              )";

                    if (!mysqli_query($conn, $query)) {
                        throw new Exception('Failed to save reminder: ' . mysqli_error($conn));
                    }
                }
            }

            foreach ($messages as $index => $message_content) {
                $clean_content = mysqli_real_escape_string($conn, $message_content);
                $follow_up_number = $index + 1;

                $query = "INSERT INTO DOA_AUTOMATION_MESSAGES (
                            PK_AUTOMATION_ID, FOLLOW_UP_NUMBER, MESSAGE_CONTENT, 
                            CREATED_ON, EDITED_ON
                          ) VALUES (
                            $automation_id, $follow_up_number, '$clean_content',
                            '$current_time', '$current_time'
                          )";

                if (!mysqli_query($conn, $query)) {
                    throw new Exception('Failed to save message template: ' . mysqli_error($conn));
                }
            }

            mysqli_commit($conn);

            echo json_encode([
                'success' => true,
                'message' => 'Automation saved successfully',
                'automation_id' => $automation_id
            ]);
        } catch (Exception $e) {
            mysqli_rollback($conn);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        mysqli_close($conn);
        exit;
    }

    // Load automation
    if ($action === 'load') {
        $automation_id = intval($_GET['id'] ?? 0);

        if ($automation_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid automation ID']);
            exit;
        }

        try {
            $conn = db_connect();

            $query = "SELECT * FROM DOA_AUTOMATIONS 
                      WHERE PK_AUTOMATION_ID = $automation_id 
                      AND PK_ACCOUNT_MASTER = $PK_ACCOUNT_MASTER";

            $result = mysqli_query($conn, $query);
            $automation = mysqli_fetch_assoc($result);

            if (!$automation) {
                throw new Exception('Automation not found');
            }

            $reminders = [];
            $query = "SELECT * FROM DOA_AUTOMATION_REMINDERS 
                      WHERE PK_AUTOMATION_ID = $automation_id 
                      ORDER BY REMINDER_ORDER";
            $result = mysqli_query($conn, $query);
            while ($row = mysqli_fetch_assoc($result)) {
                $reminders[] = [
                    'enabled' => (bool)$row['IS_ENABLED'],
                    'value' => intval($row['VALUE']),
                    'unit' => $row['UNIT']
                ];
            }

            $messages = [];
            $query = "SELECT * FROM DOA_AUTOMATION_MESSAGES 
                      WHERE PK_AUTOMATION_ID = $automation_id 
                      ORDER BY FOLLOW_UP_NUMBER";
            $result = mysqli_query($conn, $query);
            while ($row = mysqli_fetch_assoc($result)) {
                $messages[] = $row['MESSAGE_CONTENT'];
            }

            echo json_encode([
                'success' => true,
                'data' => [
                    'automation_id' => $automation['PK_AUTOMATION_ID'],
                    'title' => $automation['TITLE'],
                    'is_active' => (bool)$automation['IS_ACTIVE'],
                    'trigger_type' => $automation['TRIGGER_TYPE'],
                    'trigger_value' => $automation['TRIGGER_VALUE'],
                    'condition_type' => $automation['CONDITION_TYPE'],
                    'condition_value' => json_decode($automation['CONDITION_VALUE'], true),
                    'schedule_type' => $automation['SCHEDULE_TYPE'],
                    'start_reminder_value' => intval($automation['START_REMINDER_VALUE']),
                    'start_reminder_unit' => $automation['START_REMINDER_UNIT'],
                    'max_reminders' => intval($automation['MAX_REMINDERS']),
                    'notify_service_provider' => (bool)$automation['NOTIFY_SERVICE_PROVIDER'],
                    'notify_studio_manager' => (bool)$automation['NOTIFY_STUDIO_MANAGER'],
                    'custom_reminders' => $reminders,
                    'messages' => $messages
                ]
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        mysqli_close($conn);
        exit;
    }

    // Delete automation
    if ($action === 'delete') {
        $automation_id = intval($_POST['automation_id'] ?? 0);

        if ($automation_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid automation ID']);
            exit;
        }

        try {
            $conn = db_connect();

            $query = "DELETE FROM DOA_AUTOMATIONS 
                      WHERE PK_AUTOMATION_ID = $automation_id 
                      AND PK_ACCOUNT_MASTER = $PK_ACCOUNT_MASTER";

            if (mysqli_query($conn, $query)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Automation deleted successfully'
                ]);
            } else {
                throw new Exception('Failed to delete automation');
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        mysqli_close($conn);
        exit;
    }
}

// Get automation ID from URL for editing
$automation_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'layout/header_script.php'; ?>
<?php require_once('../includes/header.php'); ?>
<?php include 'layout/header.php'; ?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Automations - Custom Schedule Builder</title>
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

        .d-flex.align-items-center.gap-2 {
            display: flex !important;
            align-items: center !important;
            gap: 0.5rem !important;
        }

        .loading {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .loading.active {
            display: flex;
        }

        .spinner-border {
            width: 3rem;
            height: 3rem;
        }
    </style>
</head>

<body>
    <div class="loading">
        <div class="spinner-border text-light" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <div class="container-fluid py-4 px-3 dashboard-container">
        <div class="row g-4">
            <div class="col-12 col-md-4 col-xl-3">
                <?php include 'layout/setup_sidebar.php'; ?>
            </div>

            <div class="col-12 col-md-8 col-lg-9">
                <div class="main-card p-4">
                    <div class="main-header border-bottom pb-3 mb-4 d-flex align-items-center gap-2">
                        <a href="automations_list.php" class="text-dark text-decoration-none"><i class="bi bi-arrow-left fs-5 me-1"></i></a>
                        <h2 class="h5 mb-0 fw-semibold">Automations</h2>
                    </div>

                    <form id="automationForm">
                        <div class="form-section row align-items-end mb-4">
                            <div class="col">
                                <label class="form-label-custom">Title</label>
                                <input type="text" class="form-control form-control-custom bg-light" value="Trial Class Follow Up" id="automationTitle">
                            </div>
                            <div class="col-auto ps-0 pb-2">
                                <div class="form-check form-switch custom-switch d-flex align-items-center gap-2 m-0 p-0">
                                    <input class="form-check-input m-0" type="checkbox" role="switch" id="automationToggle" checked>
                                    <label class="form-check-label text-dark small fw-medium" for="automationToggle">On</label>
                                </div>
                            </div>
                        </div>

                        <div class="form-section mb-4">
                            <label class="form-label-custom">When this happens</label>
                            <div class="row g-2 mb-2">
                                <div class="col-12 col-sm-6"><select class="form-select form-select-custom bg-light" id="triggerType">
                                        <option value="customer_completes_class">Customer completes a class</option>
                                    </select></div>
                                <div class="col-12 col-sm-6"><select class="form-select form-select-custom bg-light" id="triggerValue">
                                        <option value="trial_class">Trial class</option>
                                    </select></div>
                            </div>
                            <button type="button" class="btn btn-pill-outline mt-1">Add a trigger</button>
                        </div>

                        <div class="form-section mb-4">
                            <label class="form-label-custom">Only if</label>
                            <div class="row mb-2">
                                <div class="col-12 col-sm-6"><select class="form-select form-select-custom bg-light" id="conditionType">
                                        <option value="customer_not_purchased_contract">Customer has not purchased a contract</option>
                                    </select></div>
                            </div>
                            <button type="button" class="btn btn-pill-outline mt-1">Add a condition</button>
                        </div>

                        <div class="form-section mb-4">
                            <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                                <span class="text-dark small fw-medium">Start first reminder</span>
                                <input type="number" class="form-control form-control-inline bg-light text-center" value="3" id="startReminderValue">
                                <select class="form-select form-select-inline bg-light" id="startReminderUnit">
                                    <option>Days</option>
                                    <option>Hours</option>
                                </select>
                            </div>
                            <span class="text-muted extra-small">If trigger and conditions are not met, nothing happens</span>
                        </div>

                        <div class="form-section mb-4">
                            <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                                <span class="text-dark small fw-medium">Send up to</span>
                                <input type="number" class="form-control form-control-inline bg-light text-center" value="5" id="maxReminders">
                                <span class="text-dark small fw-medium">reminders</span>
                            </div>
                            <span class="text-muted extra-small">Stops immediately once conditions are no longer met</span>
                        </div>

                        <div class="form-section mb-4">
                            <label class="form-label-custom mb-2">Schedule</label>
                            <div class="d-flex flex-column gap-2 mb-3">
                                <div class="form-check custom-radio d-flex align-items-center gap-2">
                                    <input class="form-check-input" type="radio" name="scheduleRadio" id="radioSimple" value="simple">
                                    <label class="form-check-label text-dark small fw-medium" for="radioSimple">Simple</label>
                                </div>
                                <div class="form-check custom-radio d-flex align-items-center gap-2">
                                    <input class="form-check-input" type="radio" name="scheduleRadio" id="radioCustom" value="custom" checked>
                                    <label class="form-check-label text-dark small fw-medium" for="radioCustom">Custom</label>
                                </div>
                            </div>

                            <div id="customScheduleContainer" class="custom-schedule-matrix ms-0 ms-md-3 mt-3">
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

                        <div class="form-section mb-4">
                            <label class="form-label-custom mb-2">Who gets notified</label>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <input type="checkbox" id="checkServiceProvider" checked style="width: 20px; height: 20px;">
                                <label class="text-dark small" for="checkServiceProvider">Service provider</label>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <input type="checkbox" id="checkStudioManager" checked style="width: 20px; height: 20px;">
                                <label class="text-dark small" for="checkStudioManager">Studio manager</label>
                            </div>
                        </div>

                        <div class="form-section mb-3">
                            <label class="form-label-custom mb-1">Message templates</label>
                            <p class="text-muted extra-small mb-2">Optionally provide example language. This will only appear in the To Do list item for the assigned team members.</p>
                        </div>
                        <div class="accordion custom-accordion mb-4" id="messagesAccordion"></div>

                        <div class="mt-4 text-center">
                            <button type="submit" class="btn btn-save-automation w-100 py-2 fw-semibold mb-3">Save Automation</button>
                            <button type="button" id="deleteAutomationBtn" class="btn btn-link text-danger text-decoration-none small d-block mx-auto">Delete Automation</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const automationId = <?php echo $automation_id ?: 'null'; ?>;
        let reminders = [{
                id: Date.now() + 1,
                enabled: true,
                value: 3,
                unit: "Days"
            },
            {
                id: Date.now() + 2,
                enabled: true,
                value: 5,
                unit: "Days"
            },
            {
                id: Date.now() + 3,
                enabled: true,
                value: 3,
                unit: "Days"
            },
            {
                id: Date.now() + 4,
                enabled: true,
                value: 5,
                unit: "Days"
            },
            {
                id: Date.now() + 5,
                enabled: true,
                value: 3,
                unit: "Days"
            }
        ];

        function showLoading() {
            document.querySelector('.loading').classList.add('active');
        }

        function hideLoading() {
            document.querySelector('.loading').classList.remove('active');
        }

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
                });

                const enableChk = rowDiv.querySelector('.reminder-enabled');
                const valueInp = rowDiv.querySelector('.reminder-value');
                const unitSel = rowDiv.querySelector('.reminder-unit');
                enableChk.addEventListener('change', (e) => {
                    rem.enabled = e.target.checked;
                });
                valueInp.addEventListener('change', (e) => {
                    rem.value = parseInt(e.target.value) || 0;
                });
                unitSel.addEventListener('change', (e) => {
                    rem.unit = e.target.value;
                });
                container.appendChild(rowDiv);
            });
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

        const radioSimple = document.getElementById('radioSimple');
        const radioCustom = document.getElementById('radioCustom');
        const customContainerDiv = document.getElementById('customScheduleContainer');

        function toggleScheduleDisplay() {
            if (customContainerDiv) {
                customContainerDiv.style.display = radioCustom.checked ? 'block' : 'none';
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
                }
            }
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
                                    ${sampleTexts[(i-1) % sampleTexts.length]}
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
        }

        const maxRemindersInput = document.getElementById('maxReminders');

        function updateAccordionCount() {
            let maxVal = parseInt(maxRemindersInput.value);
            if (isNaN(maxVal) || maxVal < 1) maxVal = 1;
            if (maxVal > 20) maxVal = 20;
            buildAccordionItems(maxVal);
        }

        if (maxRemindersInput) {
            maxRemindersInput.addEventListener('change', updateAccordionCount);
            updateAccordionCount();
        } else {
            buildAccordionItems(5);
        }

        async function saveAutomation() {
            showLoading();

            const title = document.getElementById('automationTitle')?.value || '';
            const isActive = document.getElementById('automationToggle')?.checked;
            const scheduleType = radioCustom.checked ? 'custom' : 'simple';

            let customRemindersData = [];
            if (scheduleType === 'custom') {
                customRemindersData = reminders.map(r => ({
                    enabled: r.enabled,
                    value: r.value,
                    unit: r.unit
                }));
            }

            const messages = [];
            const accordItems = document.querySelectorAll('#messagesAccordion .accordion-item');
            accordItems.forEach((item) => {
                const editableDiv = item.querySelector('.editable-content-area');
                messages.push(editableDiv ? editableDiv.innerHTML : '');
            });

            const automationData = {
                automation_id: automationId || 0,
                title: title,
                is_active: isActive,
                schedule_type: scheduleType,
                start_reminder_value: document.getElementById('startReminderValue')?.value || 3,
                start_reminder_unit: document.getElementById('startReminderUnit')?.value || 'Days',
                max_reminders: maxRemindersInput?.value || 5,
                notify_service_provider: document.getElementById('checkServiceProvider')?.checked,
                notify_studio_manager: document.getElementById('checkStudioManager')?.checked,
                trigger_type: document.getElementById('triggerType')?.value || 'customer_completes_class',
                trigger_value: document.getElementById('triggerValue')?.value || 'trial_class',
                condition_type: document.getElementById('conditionType')?.value || 'customer_not_purchased_contract',
                condition_value: {},
                custom_reminders: customRemindersData,
                messages: messages
            };

            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(automationData)
                });

                const result = await response.json();

                if (result.success) {
                    alert('Automation saved successfully!');
                    if (!automationId && result.automation_id) {
                        window.location.href = `?id=${result.automation_id}`;
                    }
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                console.error('Error saving automation:', error);
                alert('An error occurred while saving the automation');
            } finally {
                hideLoading();
            }
        }

        async function loadAutomation(id) {
            showLoading();
            try {
                const response = await fetch(`${window.location.href}?action=load&id=${id}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const result = await response.json();

                if (result.success && result.data) {
                    const data = result.data;
                    document.getElementById('automationTitle').value = data.title;
                    document.getElementById('automationToggle').checked = data.is_active;
                    document.getElementById('startReminderValue').value = data.start_reminder_value;
                    document.getElementById('startReminderUnit').value = data.start_reminder_unit;
                    document.getElementById('maxReminders').value = data.max_reminders;
                    document.getElementById('checkServiceProvider').checked = data.notify_service_provider;
                    document.getElementById('checkStudioManager').checked = data.notify_studio_manager;

                    if (data.schedule_type === 'simple') {
                        radioSimple.checked = true;
                        radioCustom.checked = false;
                    } else {
                        radioSimple.checked = false;
                        radioCustom.checked = true;
                    }
                    toggleScheduleDisplay();

                    if (data.custom_reminders && data.custom_reminders.length > 0) {
                        reminders = data.custom_reminders.map((rem, idx) => ({
                            id: Date.now() + idx,
                            enabled: rem.enabled,
                            value: rem.value,
                            unit: rem.unit
                        }));
                        renderReminders();
                    }

                    if (data.messages && data.messages.length > 0) {
                        maxRemindersInput.value = data.messages.length;
                        buildAccordionItems(data.messages.length);
                        setTimeout(() => {
                            const accordItems = document.querySelectorAll('#messagesAccordion .accordion-item');
                            accordItems.forEach((item, idx) => {
                                const editableDiv = item.querySelector('.editable-content-area');
                                if (editableDiv && data.messages[idx]) {
                                    editableDiv.innerHTML = data.messages[idx];
                                }
                            });
                        }, 100);
                    }
                }
            } catch (error) {
                console.error('Error loading automation:', error);
            } finally {
                hideLoading();
            }
        }

        async function deleteAutomation() {
            if (!automationId) {
                alert('No automation to delete');
                return;
            }

            if (!confirm('Are you sure you want to delete this automation? This action cannot be undone.')) {
                return;
            }

            showLoading();
            try {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('automation_id', automationId);

                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const result = await response.json();

                if (result.success) {
                    alert('Automation deleted successfully!');
                    window.location.href = 'automations_list.php';
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                console.error('Error deleting automation:', error);
                alert('An error occurred while deleting the automation');
            } finally {
                hideLoading();
            }
        }

        // Handle form submission
        document.getElementById('automationForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            saveAutomation();
        });

        document.getElementById('deleteAutomationBtn')?.addEventListener('click', (e) => {
            e.preventDefault();
            deleteAutomation();
        });

        if (automationId) {
            loadAutomation(automationId);
        }

        renderReminders();
        window.addEventListener('load', () => {
            renderReminders();
            toggleScheduleDisplay();
        });
    </script>
</body>

</html>