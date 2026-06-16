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

// Fetch services for the dropdown
$services = array();
$services_query = $db_account->Execute("SELECT PK_SERVICE_MASTER, SERVICE_NAME FROM DOA_SERVICE_MASTER WHERE PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND ACTIVE = 1 ORDER BY SERVICE_NAME");
if ($services_query && $services_query->RecordCount() > 0) {
    while (!$services_query->EOF) {
        $services[] = array(
            'id' => $services_query->fields['PK_SERVICE_MASTER'],
            'name' => $services_query->fields['SERVICE_NAME']
        );
        $services_query->MoveNext();
    }
}

// Handle AJAX request for services
if (isset($_GET['ajax']) && $_GET['ajax'] == 'get_services') {
    header('Content-Type: application/json');

    if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '') {
        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
        exit;
    }

    $automation_id = isset($_GET['automation_id']) ? intval($_GET['automation_id']) : 0;
    $location_id = isset($_GET['location_id']) ? intval($_GET['location_id']) : $_SESSION['DEFAULT_LOCATION_ID'];
    $PK_ACCOUNT_MASTER = $_SESSION['PK_ACCOUNT_MASTER'];

    $services = array();
    $services_query = $db_account->Execute("SELECT DOA_SERVICE_MASTER.PK_SERVICE_MASTER, DOA_SERVICE_MASTER.SERVICE_NAME, DOA_SERVICE_CODE.SERVICE_CODE FROM DOA_SERVICE_MASTER INNER JOIN DOA_SERVICE_CODE ON DOA_SERVICE_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_CODE.PK_SERVICE_MASTER WHERE DOA_SERVICE_MASTER.PK_LOCATION = '$location_id' AND DOA_SERVICE_MASTER.ACTIVE = 1 ORDER BY DOA_SERVICE_MASTER.SERVICE_NAME");
    if ($services_query && $services_query->RecordCount() > 0) {
        while (!$services_query->EOF) {
            $services[] = array(
                'id' => $services_query->fields['PK_SERVICE_MASTER'],
                'name' => $services_query->fields['SERVICE_NAME'] . " (" . $services_query->fields['SERVICE_CODE'] . ")"
            );
            $services_query->MoveNext();
        }
    }

    $selected_services = array();
    if ($automation_id > 0) {
        $auto_query = $db_account->Execute("SELECT TRIGGER_VALUE FROM DOA_AUTOMATIONS WHERE PK_AUTOMATION_ID = '$automation_id' AND PK_ACCOUNT_MASTER = '$PK_ACCOUNT_MASTER'");
        if ($auto_query && $auto_query->RecordCount() > 0) {
            $trigger_value = $auto_query->fields['TRIGGER_VALUE'];
            if (!empty($trigger_value)) {
                $selected_services = json_decode($trigger_value, true);
                if (!is_array($selected_services)) {
                    $selected_services = array();
                }
            }
        }
    }

    echo json_encode([
        'success' => true,
        'services' => $services,
        'selected_services' => $selected_services
    ]);
    exit;
}

// Handle AJAX request for status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['update_status'])) {
    header('Content-Type: application/json');

    $automation_id = isset($_POST['automation_id']) ? intval($_POST['automation_id']) : 0;
    $is_active = isset($_POST['is_active']) ? intval($_POST['is_active']) : 0;
    $PK_ACCOUNT_MASTER = $_SESSION['PK_ACCOUNT_MASTER'];

    if ($automation_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid automation ID']);
        exit;
    }

    $update_data = array(
        'IS_ACTIVE' => $is_active,
        'EDITED_BY' => $_SESSION['PK_USER'],
        'EDITED_ON' => date("Y-m-d H:i:s")
    );

    $result = db_perform_account('DOA_AUTOMATIONS', $update_data, 'update', " PK_AUTOMATION_ID = '$automation_id' AND PK_ACCOUNT_MASTER = '$PK_ACCOUNT_MASTER'");

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update status']);
    }
    exit;
}

// Handle form submission
if (!empty($_POST)) {
    $custom_reminders_json = isset($_POST['CUSTOM_REMINDERS']) ? $_POST['CUSTOM_REMINDERS'] : '';
    $messages_json = isset($_POST['MESSAGES']) ? $_POST['MESSAGES'] : '';

    unset($_POST['CUSTOM_REMINDERS']);
    unset($_POST['MESSAGES']);

    $AUTOMATION_DATA = $_POST;
    $AUTOMATION_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];

    // Ensure PK_LOCATION is set
    if (!isset($AUTOMATION_DATA['PK_LOCATION']) || empty($AUTOMATION_DATA['PK_LOCATION'])) {
        $AUTOMATION_DATA['PK_LOCATION'] = $_SESSION['DEFAULT_LOCATION_ID'];
    }

    // Handle switch/checkbox values - they don't send value when unchecked
    $AUTOMATION_DATA['NOTIFY_SERVICE_PROVIDER_LAST'] = isset($_POST['NOTIFY_SERVICE_PROVIDER_LAST']) ? 1 : 0;
    $AUTOMATION_DATA['NOTIFY_SERVICE_PROVIDER_ENROLL'] = isset($_POST['NOTIFY_SERVICE_PROVIDER_ENROLL']) ? 1 : 0;
    $AUTOMATION_DATA['NOTIFY_STUDIO_MANAGER'] = isset($_POST['NOTIFY_STUDIO_MANAGER']) ? 1 : 0;

    // Keep the old field for backward compatibility if needed
    $AUTOMATION_DATA['NOTIFY_SERVICE_PROVIDER'] = ($AUTOMATION_DATA['NOTIFY_SERVICE_PROVIDER_LAST'] || $AUTOMATION_DATA['NOTIFY_SERVICE_PROVIDER_ENROLL']) ? 1 : 0;

    if (isset($_POST['TRIGGER_TYPE']) && $_POST['TRIGGER_TYPE'] == 'no_specific_services') {
        if (isset($_POST['TRIGGER_VALUE_SERVICES']) && is_array($_POST['TRIGGER_VALUE_SERVICES'])) {
            $AUTOMATION_DATA['TRIGGER_VALUE'] = json_encode($_POST['TRIGGER_VALUE_SERVICES']);
        } else {
            $AUTOMATION_DATA['TRIGGER_VALUE'] = json_encode(array());
        }
        unset($AUTOMATION_DATA['TRIGGER_VALUE_SERVICES']);
    }

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

        $db_account->Execute("DELETE FROM DOA_AUTOMATION_REMINDERS WHERE PK_AUTOMATION_ID = '$automation_id'");
        $db_account->Execute("DELETE FROM DOA_AUTOMATION_MESSAGES WHERE PK_AUTOMATION_ID = '$automation_id'");
    }

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

    if (!empty($messages_json) && $messages_json != 'null' && $messages_json != '[]') {
        $messages = json_decode($messages_json, true);
        if (is_array($messages) && !empty($messages)) {
            foreach ($messages as $index => $message_content) {
                if (!empty($message_content)) {
                    $clean_content = trim($message_content);
                    $created_on = date("Y-m-d H:i:s");
                    $follow_up_num = $index + 1;

                    $sql = "INSERT INTO DOA_AUTOMATION_MESSAGES (PK_AUTOMATION_ID, FOLLOW_UP_NUMBER, MESSAGE_CONTENT, CREATED_ON, EDITED_ON) 
                            VALUES ('$automation_id', '$follow_up_num', '$clean_content', '$created_on', '$created_on')";
                    $db_account->Execute($sql);
                }
            }
        }
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
    $PK_LOCATION = $AUTOMATION['PK_LOCATION'];
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

    $messages_res = $db_account->Execute("SELECT * FROM `DOA_AUTOMATION_MESSAGES` WHERE PK_AUTOMATION_ID = '$_GET[id]' ORDER BY FOLLOW_UP_NUMBER");
    $MESSAGES = array();
    if ($messages_res && $messages_res->RecordCount() > 0) {
        while (!$messages_res->EOF) {
            $MESSAGES[] = $messages_res->fields['MESSAGE_CONTENT'];
            $messages_res->MoveNext();
        }
    }

    if ($AUTOMATION['TRIGGER_TYPE'] == 'no_specific_services' && !empty($AUTOMATION['TRIGGER_VALUE'])) {
        $AUTOMATION['TRIGGER_VALUE_SERVICES'] = json_decode($AUTOMATION['TRIGGER_VALUE'], true);
        if (!is_array($AUTOMATION['TRIGGER_VALUE_SERVICES'])) {
            $AUTOMATION['TRIGGER_VALUE_SERVICES'] = array();
        }
    } else {
        $AUTOMATION['TRIGGER_VALUE_SERVICES'] = array();
    }
} else {
    $AUTOMATION = array(
        'TITLE' => '',
        'PK_LOCATION' => $_SESSION['DEFAULT_LOCATION_ID'],
        'IS_ACTIVE' => 1,
        'TRIGGER_TYPE' => 'customer_completes_class',
        'TRIGGER_VALUE' => 'trial_class',
        'CONDITION_TYPE' => 'customer_not_purchased_contract',
        'SCHEDULE_TYPE' => 'custom',
        'START_REMINDER_VALUE' => 1,
        'START_REMINDER_UNIT' => 'Days',
        'MAX_REMINDERS' => 5,
        'NOTIFY_SERVICE_PROVIDER_LAST' => 1,
        'NOTIFY_SERVICE_PROVIDER_ENROLL' => 1,
        'NOTIFY_STUDIO_MANAGER' => 1,
        'NOTIFY_SERVICE_PROVIDER' => 1,
        'TRIGGER_VALUE_SERVICES' => array()
    );
    $CUSTOM_REMINDERS = array(
        array('enabled' => true, 'value' => 1, 'unit' => 'Days'),
        array('enabled' => true, 'value' => 3, 'unit' => 'Days'),
        array('enabled' => true, 'value' => 5, 'unit' => 'Days'),
        array('enabled' => true, 'value' => 7, 'unit' => 'Days'),
        array('enabled' => true, 'value' => 10, 'unit' => 'Days')
    );
    $MESSAGES = array();
}
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'layout/header_script.php'; ?>
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
            color: #39b54a !important;
            font-weight: 600;
        }

        .sidebar-card .nav-link.active i {
            color: #39b54a;
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
            font-size: 0.85rem;
            padding: 0.6rem 0.9rem;
            background-color: #fefefe;
            transition: 0.2s;
        }

        .form-control-custom:focus,
        .form-select-custom:focus {
            border-color: #39b54a;
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
            background-color: #39b54a;
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
            background-color: #39b54a;
        }

        .custom-switch .form-check-input:focus,
        .custom-checkbox .form-check-input:focus,
        .custom-radio .form-check-input:focus {
            box-shadow: none;
        }

        /* ========== START: ALL CHECKBOXES GREEN ========== */

        /* General checkbox style - all checkboxes */
        .form-check-input[type="checkbox"] {
            width: 1.2em;
            height: 1.2em;
            margin-top: 0;
            cursor: pointer;
            border: 1.5px solid #cbd5e1;
            background-color: white;
            flex-shrink: 0;
        }

        /* Checked state for all checkboxes with form-check-input class */
        .form-check-input[type="checkbox"]:checked {
            background-color: #39b54a !important;
            border-color: #39b54a !important;
        }

        /* Checked state for all checkboxes (fallback) */
        input[type="checkbox"]:checked {
            accent-color: #39b54a !important;
        }

        /* Custom checkboxes in "Who gets notified" section */
        #NOTIFY_SERVICE_PROVIDER_LAST:checked,
        #NOTIFY_SERVICE_PROVIDER_ENROLL:checked,
        #NOTIFY_STUDIO_MANAGER:checked {
            background-color: #39b54a !important;
            border-color: #39b54a !important;
        }

        /* Service checkboxes */
        .service-checkbox:checked {
            background-color: #39b54a !important;
            border-color: #39b54a !important;
        }

        /* Select all checkbox */
        #selectAllServices:checked {
            background-color: #39b54a !important;
            border-color: #39b54a !important;
        }

        /* Reminder enabled checkboxes */
        .reminder-enabled:checked {
            background-color: #39b54a !important;
            border-color: #39b54a !important;
        }

        /* Custom checkbox class */
        .custom-checkbox .form-check-input:checked {
            background-color: #39b54a !important;
            border-color: #39b54a !important;
        }

        /* Custom radio buttons */
        .custom-radio .form-check-input:checked {
            background-color: #39b54a;
            border-color: #39b54a;
        }

        /* ========== END: ALL CHECKBOXES GREEN ========== */

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

        .d-flex.align-items-center.gap-2 {
            display: flex !important;
            align-items: center !important;
            gap: 0.5rem !important;
        }

        .services-checkbox-container {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 10px;
            max-height: 250px;
            overflow-y: auto;
            background-color: #fefefe;
        }

        .services-checkbox-item {
            padding: 8px 10px;
            border-bottom: 1px solid #f1f5f9;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .services-checkbox-item:hover {
            background-color: #f8fafc;
        }

        .services-checkbox-item label {
            margin-left: 8px;
            cursor: pointer;
            font-size: 0.85rem;
            color: #334155;
        }

        .select-all-item {
            background-color: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
            font-weight: 600;
            padding: 10px;
        }

        .selected-count {
            font-size: 0.7rem;
            color: #39b54a;
            margin-top: 5px;
        }

        /* Tooltip styles */
        .tooltip .tooltip-inner {
            background-color: #39B54A;
            max-width: 280px;
            padding: 6px 12px;
            font-size: 0.8rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        .tooltip.bs-tooltip-top .tooltip-arrow::before {
            border-top-color: #39B54A;
        }

        .tooltip.bs-tooltip-bottom .tooltip-arrow::before {
            border-bottom-color: #39B54A;
        }

        .tooltip.bs-tooltip-start .tooltip-arrow::before {
            border-left-color: #39B54A;
        }

        .tooltip.bs-tooltip-end .tooltip-arrow::before {
            border-right-color: #39B54A;
        }

        /* Make all checkbox labels clickable and aligned */
        .d-flex.align-items-center.gap-2 input[type="checkbox"] {
            flex-shrink: 0;
        }

        .d-flex.align-items-center.gap-2 label {
            cursor: pointer;
            margin-bottom: 0;
        }

        /* Fix for switch alignment */
        .custom-switch .form-check-input {
            width: 2.3em;
            height: 1.25em;
            background-color: #cbd5e1;
            border-color: transparent;
            cursor: pointer;
            flex-shrink: 0;
        }

        .custom-switch .form-check-input:checked {
            background-color: #39b54a;
        }

        .custom-switch {
            display: flex !important;
            align-items: center !important;
            gap: 0.75rem !important;
            padding-left: 0 !important;
        }

        .custom-switch .form-check-label {
            cursor: pointer;
            margin-bottom: 0;
            padding-top: 2px;
        }

        /* Ensure switches show green when checked */
        .custom-switch .form-check-input:checked {
            background-color: #39b54a !important;
            border-color: #39b54a !important;
        }

        .custom-switch .form-check-input:focus {
            box-shadow: none;
        }
    </style>
</head>

<body>
    <div class="container-fluid py-4 px-4 dashboard-container">
        <div class="row g-4">
            <div class="col-12 col-md-4 col-xl-2">
                <?php include 'layout/setup_sidebar.php'; ?>
            </div>

            <div class="col-12 col-md-8 col-lg-10">
                <div class="main-card p-4">
                    <div class="main-header border-bottom pb-3 mb-4 d-flex align-items-center gap-2">
                        <a href="all_follow_ups.php" class="text-dark text-decoration-none"><i class="bi bi-arrow-left fs-5 me-1"></i></a>
                        <h2 class="h5 mb-0 fw-semibold"><?= $title ?></h2>
                    </div>

                    <form id="automationForm" action="" method="post">
                        <!-- Title & toggle -->
                        <div class="form-section row align-items-end mb-4">
                            <div class="col-md-5">
                                <label class="form-label-custom">Title</label>
                                <input type="text" class="form-control form-control-custom bg-light" value="<?= htmlspecialchars($AUTOMATION['TITLE']) ?>" id="TITLE" name="TITLE" required>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label-custom">Location <span class="text-danger">*</span></label>
                                <select class="form-select form-select-custom bg-light" id="PK_LOCATION" name="PK_LOCATION" required>
                                    <option value="">Select Location</option>
                                    <?php
                                    $row = $db->Execute("SELECT * FROM DOA_LOCATION WHERE PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND ACTIVE = 1 AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
                                    while (!$row->EOF) { ?>
                                        <option value="<?php echo $row->fields['PK_LOCATION']; ?>" <?= ($AUTOMATION['PK_LOCATION'] == $row->fields['PK_LOCATION']) ? 'selected' : '' ?>><?= $row->fields['LOCATION_NAME'] ?></option>
                                    <?php $row->MoveNext();
                                    } ?>
                                </select>
                            </div>
                            <div class="col-md-2 ps-0 pb-2">
                                <label class="form-label-custom d-block">&nbsp;</label>
                                <div class="form-check form-switch custom-switch d-flex align-items-center gap-2 m-0 p-0">
                                    <input class="form-check-input m-0" type="checkbox" role="switch" id="IS_ACTIVE" name="IS_ACTIVE" value="1" <?= $AUTOMATION['IS_ACTIVE'] ? 'checked' : '' ?>>
                                    <label class="form-check-label text-dark small fw-medium" for="IS_ACTIVE">Active</label>
                                </div>
                            </div>
                        </div>

                        <!-- Triggers section -->
                        <div class="form-section mb-4">
                            <label class="form-label-custom">When this happens</label>
                            <div class="row g-2 mb-2">
                                <div class="col-12 col-sm-6">
                                    <select class="form-select form-select-custom bg-light" name="TRIGGER_TYPE" id="TRIGGER_TYPE">
                                        <option value="customer_completes_class" <?= $AUTOMATION['TRIGGER_TYPE'] == 'customer_completes_class' ? 'selected' : '' ?>>Customer completes a class</option>
                                        <option value="no_future_appointments" <?= $AUTOMATION['TRIGGER_TYPE'] == 'no_future_appointments' ? 'selected' : '' ?>>No future appointments</option>
                                        <option value="no_active_enrollments" <?= $AUTOMATION['TRIGGER_TYPE'] == 'no_active_enrollments' ? 'selected' : '' ?>>No active enrollments</option>
                                        <option value="no_specific_services" <?= $AUTOMATION['TRIGGER_TYPE'] == 'no_specific_services' ? 'selected' : '' ?>>No specific services</option>
                                    </select>
                                </div>
                                <div class="col-12 col-sm-6" id="triggerValueContainer">
                                    <?php if ($AUTOMATION['TRIGGER_TYPE'] == 'no_specific_services'): ?>
                                        <div class="services-checkbox-container" id="servicesCheckboxContainer">
                                            <div class="services-checkbox-item select-all-item">
                                                <input type="checkbox" id="selectAllServices" class="form-check-input">
                                                <label for="selectAllServices" class="fw-semibold">Select All Services</label>
                                            </div>
                                            <?php foreach ($services as $service): ?>
                                                <div class="services-checkbox-item">
                                                    <input type="checkbox" class="form-check-input service-checkbox" name="TRIGGER_VALUE_SERVICES[]" value="<?= $service['id'] ?>" id="service_<?= $service['id'] ?>" <?= in_array($service['id'], $AUTOMATION['TRIGGER_VALUE_SERVICES']) ? 'checked' : '' ?>>
                                                    <label for="service_<?= $service['id'] ?>"><?= htmlspecialchars($service['name']) ?></label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <div class="selected-count" id="selectedCount"></div>
                                        <small class="text-muted">Check the boxes to select multiple services</small>
                                    <?php else: ?>
                                        <select class="form-select form-select-custom bg-light" name="TRIGGER_VALUE" id="TRIGGER_VALUE">
                                            <option value="PRIVATE_CLASS" <?= $AUTOMATION['TRIGGER_VALUE'] == 'PRIVATE_CLASS' ? 'selected' : '' ?>>Private class</option>
                                            <option value="GROUP_CLASS" <?= $AUTOMATION['TRIGGER_VALUE'] == 'GROUP_CLASS' ? 'selected' : '' ?>>Group class</option>
                                        </select>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Start first reminder - FIXED: default 1, min 1 -->
                        <div class="form-section mb-4">
                            <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                                <span class="text-dark small fw-medium">Start first reminder</span>
                                <input type="number" class="form-control form-control-inline bg-light text-center"
                                    value="<?= $AUTOMATION['START_REMINDER_VALUE'] ?>"
                                    id="START_REMINDER_VALUE"
                                    name="START_REMINDER_VALUE"
                                    min="1"
                                    onchange="if(this.value < 1) this.value = 1;">
                                <select class="form-select form-select-inline bg-light" id="START_REMINDER_UNIT" name="START_REMINDER_UNIT">
                                    <option value="Days" <?= $AUTOMATION['START_REMINDER_UNIT'] == 'Days' ? 'selected' : '' ?>>Days</option>
                                    <option value="Hours" <?= $AUTOMATION['START_REMINDER_UNIT'] == 'Hours' ? 'selected' : '' ?>>Hours</option>
                                </select>
                            </div>
                            <span class="text-muted extra-small">If trigger and conditions are not met, nothing happens</span>
                        </div>

                        <!-- Send up to reminders - FIXED: dynamically updates -->
                        <div class="form-section mb-4">
                            <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                                <span class="text-dark small fw-medium">Send up to</span>
                                <input type="number" class="form-control form-control-inline bg-light text-center"
                                    value="<?= $AUTOMATION['MAX_REMINDERS'] ?>"
                                    id="MAX_REMINDERS"
                                    name="MAX_REMINDERS"
                                    min="1"
                                    max="20"
                                    onchange="if(this.value < 1) this.value = 1; if(this.value > 20) this.value = 20; updateFollowUpsAndReminders();">
                                <span class="text-dark small fw-medium">reminders</span>
                            </div>
                            <span class="text-muted extra-small">Stops immediately once conditions are no longer met</span>
                        </div>

                        <!-- Schedule -->
                        <div class="form-section mb-4">
                            <label class="form-label-custom mb-2">Schedule</label>
                            <div class="d-flex flex-column gap-2 mb-3">
                                <div class="form-check custom-radio d-flex align-items-center gap-2">
                                    <input class="form-check-input" type="radio" name="SCHEDULE_TYPE" id="radioSimple" value="simple" <?= $AUTOMATION['SCHEDULE_TYPE'] == 'simple' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="radioSimple">Simple</label>
                                </div>
                                <div class="form-check custom-radio d-flex align-items-center gap-2">
                                    <input class="form-check-input" type="radio" name="SCHEDULE_TYPE" id="radioCustom" value="custom" <?= $AUTOMATION['SCHEDULE_TYPE'] == 'custom' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="radioCustom">Custom</label>
                                </div>
                            </div>

                            <div id="customScheduleContainer" class="custom-schedule-matrix ms-0 ms-md-3 mt-3" style="display: <?= $AUTOMATION['SCHEDULE_TYPE'] == 'custom' ? 'block' : 'none' ?>;">
                                <div class="row text-muted extra-small fw-semibold mb-2 g-2 align-items-center">
                                    <div class="col-1">Reminder</div>
                                    <div class="col-1 text-center">Send</div>
                                    <div class="col-8">Timing</div>
                                    <div class="col-1"></div>
                                </div>
                                <div id="remindersList"></div>
                            </div>
                        </div>

                        <!-- Who gets notified -->
                        <div class="form-section mb-4">
                            <label class="form-label-custom mb-2">Who gets notified</label>
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <div class="form-check form-switch custom-switch d-flex align-items-center gap-2 m-0 p-0">
                                    <input class="form-check-input m-0" type="checkbox" role="switch" id="NOTIFY_SERVICE_PROVIDER_LAST" name="NOTIFY_SERVICE_PROVIDER_LAST" value="1" <?= isset($AUTOMATION['NOTIFY_SERVICE_PROVIDER_LAST']) && $AUTOMATION['NOTIFY_SERVICE_PROVIDER_LAST'] ? 'checked' : '' ?>>
                                    <label class="form-check-label text-dark small fw-medium" for="NOTIFY_SERVICE_PROVIDER_LAST">
                                        Service provider on last class
                                        <i class="bi bi-info-circle-fill text-muted" style="font-size: 0.8rem; cursor: help; color: #39B54A !important;"
                                            data-bs-toggle="tooltip"
                                            data-bs-placement="right"
                                            title="Follow up will be sent to service provider that taught the class associated in the criteria"></i>
                                    </label>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <div class="form-check form-switch custom-switch d-flex align-items-center gap-2 m-0 p-0">
                                    <input class="form-check-input m-0" type="checkbox" role="switch" id="NOTIFY_SERVICE_PROVIDER_ENROLL" name="NOTIFY_SERVICE_PROVIDER_ENROLL" value="1" <?= isset($AUTOMATION['NOTIFY_SERVICE_PROVIDER_ENROLL']) && $AUTOMATION['NOTIFY_SERVICE_PROVIDER_ENROLL'] ? 'checked' : '' ?>>
                                    <label class="form-check-label text-dark small fw-medium" for="NOTIFY_SERVICE_PROVIDER_ENROLL">
                                        Service provider on enrollment
                                        <i class="bi bi-info-circle-fill text-muted" style="font-size: 0.8rem; cursor: help; color: #39B54A !important;"
                                            data-bs-toggle="tooltip"
                                            data-bs-placement="right"
                                            title="Follow up will be sent to service provider in the enrollment associated in the criteria"></i>
                                    </label>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <div class="form-check form-switch custom-switch d-flex align-items-center gap-2 m-0 p-0">
                                    <input class="form-check-input m-0" type="checkbox" role="switch" id="NOTIFY_STUDIO_MANAGER" name="NOTIFY_STUDIO_MANAGER" value="1" <?= $AUTOMATION['NOTIFY_STUDIO_MANAGER'] ? 'checked' : '' ?>>
                                    <label class="form-check-label text-dark small fw-medium" for="NOTIFY_STUDIO_MANAGER">
                                        Studio manager
                                        <i class="bi bi-info-circle-fill" style="font-size: 0.8rem; cursor: help; color: #39B54A !important;"
                                            data-bs-toggle="tooltip"
                                            data-bs-placement="right"
                                            title="The Studio Manager will receive notifications for this automation"></i>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Message Templates -->
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

    <input type="hidden" name="CUSTOM_REMINDERS" id="CUSTOM_REMINDERS" value='<?= htmlspecialchars(json_encode($CUSTOM_REMINDERS)) ?>'>
    <input type="hidden" name="MESSAGES" id="MESSAGES" value='<?= htmlspecialchars(json_encode($MESSAGES)) ?>'>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Parse existing data
        let reminders = <?= json_encode($CUSTOM_REMINDERS) ?>;
        let existingMessages = <?= json_encode($MESSAGES) ?>;

        // If reminders is empty or not an array, initialize with defaults
        if (!reminders || !Array.isArray(reminders) || reminders.length === 0) {
            const maxReminders = parseInt(document.getElementById('MAX_REMINDERS')?.value) || 5;
            reminders = [];
            for (let i = 0; i < maxReminders; i++) {
                reminders.push({
                    id: Date.now() + i,
                    enabled: true,
                    value: i + 1,
                    unit: "Days"
                });
            }
        } else {
            reminders = reminders.map((rem, idx) => ({
                ...rem,
                id: Date.now() + idx
            }));
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
                            <input class="reminder-enabled form-check-input" type="checkbox" ${rem.enabled ? 'checked' : ''}>
                        </div>
                    </div>
                    <div class="col-2 d-flex align-items-center gap-2 flex-wrap">
                        <input type="number" class="form-control form-control-inline bg-light text-center reminder-value" value="${rem.value}" style="width:75px" min="1">
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
            document.getElementById('CUSTOM_REMINDERS').value = JSON.stringify(remindersData);
        }

        // FIXED: Dynamic update function
        function updateFollowUpsAndReminders() {
            const maxReminders = parseInt(document.getElementById('MAX_REMINDERS').value) || 1;

            // Update reminders count
            while (reminders.length < maxReminders) {
                reminders.push({
                    id: Date.now() + reminders.length,
                    enabled: true,
                    value: reminders.length + 1,
                    unit: "Days"
                });
            }

            while (reminders.length > maxReminders) {
                reminders.pop();
            }

            renderReminders();

            // Update messages accordion - preserve existing messages if they exist
            const currentMessages = [];
            document.querySelectorAll('#messagesAccordion .accordion-item .editable-content-area').forEach(el => {
                currentMessages.push(el.innerHTML);
            });

            // Only update existingMessages if we have new content
            if (currentMessages.length > 0) {
                existingMessages = currentMessages;
            }

            buildAccordionItems(maxReminders);
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
            document.querySelectorAll('#messagesAccordion .accordion-item .editable-content-area').forEach(el => {
                messages.push(el.innerHTML);
            });
            document.getElementById('MESSAGES').value = JSON.stringify(messages);
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

            // Make sure existingMessages is an array
            if (!Array.isArray(existingMessages)) {
                existingMessages = [];
            }

            for (let i = 1; i <= count; i++) {
                const messageContent = (existingMessages[i - 1] && existingMessages[i - 1] !== '') ? existingMessages[i - 1] : sampleTexts[(i - 1) % sampleTexts.length];
                const accordionItem = document.createElement('div');
                accordionItem.className = 'accordion-item mb-2 border rounded-3 overflow-hidden';
                accordionItem.innerHTML = `
            <h2 class="accordion-header">
                <button class="accordion-button ${i === 1 ? '' : 'collapsed'}" type="button" data-bs-toggle="collapse" data-bs-target="#collapse${i}">
                    Follow up ${i}
                </button>
            </h2>
            <div id="collapse${i}" class="accordion-collapse collapse ${i === 1 ? 'show' : ''}" data-bs-parent="#messagesAccordion">
                <div class="accordion-body p-3 pt-1">
                    <div class="textarea-container p-2 border rounded-2 mb-2 bg-white">
                        <div class="editable-content-area" contenteditable="true" data-msg-index="${i}">${messageContent}</div>
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
            document.querySelectorAll('.editable-content-area').forEach(el => {
                el.addEventListener('input', updateMessagesInput);
            });
            updateMessagesInput();
        }

        // Handle trigger type change for services dropdown
        const triggerTypeSelect = document.getElementById('TRIGGER_TYPE');
        const triggerValueContainer = document.getElementById('triggerValueContainer');

        function updateSelectedCount() {
            const checkedBoxes = document.querySelectorAll('.service-checkbox:checked');
            const countSpan = document.getElementById('selectedCount');
            if (countSpan) {
                countSpan.innerHTML = checkedBoxes.length + ' service(s) selected';
            }

            const selectAll = document.getElementById('selectAllServices');
            if (selectAll) {
                const allCheckboxes = document.querySelectorAll('.service-checkbox');
                const allChecked = allCheckboxes.length === checkedBoxes.length && allCheckboxes.length > 0;
                selectAll.checked = allChecked;
                selectAll.indeterminate = checkedBoxes.length > 0 && !allChecked;
            }
        }

        function loadTriggerValueField() {
            const selectedType = triggerTypeSelect.value;
            const automationId = '<?= $_GET['id'] ?? 0 ?>';
            const locationId = document.getElementById('PK_LOCATION')?.value || '<?= $_SESSION['DEFAULT_LOCATION_ID'] ?>';

            if (selectedType === 'no_specific_services') {
                if (!locationId || locationId === '') {
                    triggerValueContainer.innerHTML = '<div class="text-warning">Please select a location first to load services.</div>';
                    return;
                }

                triggerValueContainer.innerHTML = '<div class="text-muted">Loading services...</div>';

                const url = window.location.href.split('?')[0] + '?ajax=get_services&automation_id=' + automationId + '&location_id=' + locationId;

                fetch(url, {
                        credentials: 'same-origin',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.services && data.services.length > 0) {
                            let checkboxesHtml = '';
                            checkboxesHtml += '<div class="services-checkbox-item select-all-item">';
                            checkboxesHtml += '<input type="checkbox" id="selectAllServices" class="form-check-input">';
                            checkboxesHtml += '<label for="selectAllServices" class="fw-semibold">Select All Services</label>';
                            checkboxesHtml += '</div>';

                            data.services.forEach(service => {
                                const isSelected = data.selected_services && data.selected_services.includes(service.id.toString());
                                checkboxesHtml += `
                        <div class="services-checkbox-item">
                            <input type="checkbox" class="form-check-input service-checkbox" name="TRIGGER_VALUE_SERVICES[]" value="${service.id}" id="service_${service.id}" ${isSelected ? 'checked' : ''}>
                            <label for="service_${service.id}">${escapeHtml(service.name)}</label>
                        </div>
                    `;
                            });

                            triggerValueContainer.innerHTML = `
                    <div class="services-checkbox-container" id="servicesCheckboxContainer">
                        ${checkboxesHtml}
                    </div>
                    <div class="selected-count" id="selectedCount"></div>
                    <small class="text-muted">Check the boxes to select multiple services</small>
                `;

                            attachCheckboxEvents();
                            updateSelectedCount();
                        } else {
                            triggerValueContainer.innerHTML = '<div class="text-warning">No services available for this location. Please add services first.</div>';
                        }
                    })
                    .catch(error => {
                        console.error('Error loading services:', error);
                        triggerValueContainer.innerHTML = '<div class="text-danger">Error loading services. Please refresh the page.</div>';
                    });
            } else {
                const currentValue = '<?= addslashes($AUTOMATION['TRIGGER_VALUE']) ?>';
                let optionsHtml = '';

                if (selectedType === 'customer_completes_class') {
                    optionsHtml = `
                                    <option value="PRIVATE_CLASS" ${currentValue === 'PRIVATE_CLASS' ? 'selected' : ''}>Private class</option>
                                    <option value="GROUP_CLASS" ${currentValue === 'GROUP_CLASS' ? 'selected' : ''}>Group class</option>
                                `;
                } else {
                    optionsHtml = `<option value="yes" selected>Yes</option>`;
                }

                triggerValueContainer.innerHTML = `
            <select class="form-select form-select-custom bg-light" name="TRIGGER_VALUE" id="TRIGGER_VALUE">
                ${optionsHtml}
            </select>
        `;
            }
        }

        function attachCheckboxEvents() {
            const selectAllCheckbox = document.getElementById('selectAllServices');
            if (selectAllCheckbox) {
                const newSelectAll = selectAllCheckbox.cloneNode(true);
                selectAllCheckbox.parentNode.replaceChild(newSelectAll, selectAllCheckbox);

                newSelectAll.addEventListener('change', function(e) {
                    const allCheckboxes = document.querySelectorAll('.service-checkbox');
                    allCheckboxes.forEach(checkbox => {
                        checkbox.checked = e.target.checked;
                    });
                    updateSelectedCount();
                });
            }

            document.querySelectorAll('.service-checkbox').forEach(checkbox => {
                const newCheckbox = checkbox.cloneNode(true);
                checkbox.parentNode.replaceChild(newCheckbox, checkbox);

                newCheckbox.addEventListener('change', function() {
                    updateSelectedCount();
                });
            });
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Add event listener for location change
        document.addEventListener('DOMContentLoaded', function() {
            const locationSelect = document.getElementById('PK_LOCATION');
            if (locationSelect) {
                locationSelect.addEventListener('change', function() {
                    // Reload services if the trigger type is 'no_specific_services'
                    if (document.getElementById('TRIGGER_TYPE').value === 'no_specific_services') {
                        loadTriggerValueField();
                    }
                });
            }
        });

        if (triggerTypeSelect) {
            triggerTypeSelect.addEventListener('change', loadTriggerValueField);
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
            const initialCount = (existingMessages.length > 0) ? existingMessages.length : maxRemindersInput.value;
            maxRemindersInput.value = initialCount;
            buildAccordionItems(parseInt(initialCount));
        } else {
            buildAccordionItems(5);
        }

        document.getElementById('automationForm').addEventListener('submit', function(e) {
            updateRemindersInput();
            updateMessagesInput();
        });

        renderReminders();

        // Update the window load event handler
        window.addEventListener('load', function() {
            // First, ensure reminders count matches MAX_REMINDERS
            const maxReminders = parseInt(document.getElementById('MAX_REMINDERS').value) || 5;

            // If reminders array is empty or has different count than maxReminders, adjust it
            if (reminders.length === 0) {
                // If no reminders, create default ones
                for (let i = 0; i < maxReminders; i++) {
                    reminders.push({
                        id: Date.now() + i,
                        enabled: true,
                        value: i + 1,
                        unit: "Days"
                    });
                }
            } else if (reminders.length !== maxReminders) {
                // If count mismatch, adjust
                while (reminders.length < maxReminders) {
                    reminders.push({
                        id: Date.now() + reminders.length,
                        enabled: true,
                        value: reminders.length + 1,
                        unit: "Days"
                    });
                }
                while (reminders.length > maxReminders) {
                    reminders.pop();
                }
            }

            // Now render everything
            renderReminders();
            toggleScheduleDisplay();

            // Load services after a short delay to ensure location is set
            setTimeout(function() {
                loadTriggerValueField();
            }, 100);

            // Ensure start reminder value is at least 1
            const startReminder = document.getElementById('START_REMINDER_VALUE');
            if (startReminder && parseInt(startReminder.value) < 1) {
                startReminder.value = 1;
            }

            // Ensure max reminders value matches reminders count
            const maxRemindersInput = document.getElementById('MAX_REMINDERS');
            if (maxRemindersInput && parseInt(maxRemindersInput.value) !== reminders.length) {
                maxRemindersInput.value = reminders.length;
            }

            // Build accordion with correct count
            const messageCount = existingMessages.length > 0 ? existingMessages.length : reminders.length;
            buildAccordionItems(messageCount);
        });

        // Initialize Bootstrap tooltips
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl, {
                    trigger: 'hover',
                    animation: true,
                    delay: {
                        "show": 300,
                        "hide": 100
                    }
                });
            });
        });
    </script>
</body>

</html>