<?php
require_once('../global/config.php');

global $db;
global $db_account;

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug: Log all POST data
    error_log("POST Data: " . print_r($_POST, true));

    // Insert corporation data
    $corpName = trim($_POST['corp_name'] ?? '');
    $corpCard = trim($_POST['corp_card'] ?? '');

    if (empty($corpName)) {
        $error = "Corporation name is required";
    } else {
        $CORPORATION_DATA = array(
            'PK_ACCOUNT_MASTER' => $_SESSION['PK_ACCOUNT_MASTER'],
            'CORPORATION_NAME' => $corpName,
            'ACTIVE' => 1,
            'CREATED_BY' => $_SESSION['PK_USER'],
            'CREATED_ON' => date("Y-m-d H:i:s")
        );
        //pre_r($CORPORATION_DATA);

        db_perform('DOA_CORPORATION', $CORPORATION_DATA, 'insert');
        $PK_CORPORATION = $db->insert_ID();

        if ($PK_CORPORATION) {
            $success = true;

            // Insert locations
            if (isset($_POST['location']) && is_array($_POST['location'])) {
                foreach ($_POST['location'] as $location) {
                    if (!empty($location['name'])) {
                        $LOCATION_DATA = array(
                            'PK_ACCOUNT_MASTER' => $_SESSION['PK_ACCOUNT_MASTER'],
                            'PK_CORPORATION' => $PK_CORPORATION,
                            'LOCATION_NAME' => trim($location['name'] ?? ''),
                            'CITY' => trim($location['city'] ?? ''),
                            'PK_STATES' => trim($location['state'] ?? ''),
                            'ZIP_CODE' => trim($location['zip'] ?? ''),
                            'EMAIL' => trim($location['email'] ?? ''),
                            'PK_TIMEZONE' => trim($location['timezone'] ?? ''),
                            //'OPERATIONAL_HOURS' => trim($location['hours'] ?? ''),
                            //'CREDIT_CARD' => trim($location['card'] ?? ''),
                            'ACTIVE' => 1,
                            'CREATED_BY' => $_SESSION['PK_USER'],
                            'CREATED_ON' => date("Y-m-d H:i:s")
                        );
                        //pre_r($LOCATION_DATA);
                        db_perform('DOA_LOCATION', $LOCATION_DATA, 'insert');
                        $PK_LOCATION = $db->insert_ID();
                    }
                }
            }

            // Insert users
            if (isset($_POST['user']) && is_array($_POST['user'])) {
                foreach ($_POST['user'] as $user) {
                    if (!empty($user['name'])) {
                        $USER_DATA = array(
                            'PK_ACCOUNT_MASTER' => $_SESSION['PK_ACCOUNT_MASTER'],
                            'FIRST_NAME' => trim($user['name'] ?? ''),
                            'LAST_NAME' => '', // Assuming last name is not collected in this form
                            'EMAIL_ID' => trim($user['email'] ?? ''),
                            'USER_NAME' => trim($user['login'] ?? ''),
                            'CREATE_LOGIN' => 1,

                            //'SERVICE_HOURS' => trim($user['hours'] ?? ''),
                            'APPEAR_IN_CALENDAR' => isset($user['calendar']) ? 1 : 0,
                            'ACTIVE' => 1,
                            'CREATED_BY' => $_SESSION['PK_USER'],
                            'CREATED_ON' => date("Y-m-d H:i:s")
                        );
                        //pre_r($USER_DATA);
                        db_perform('DOA_USERS', $USER_DATA, 'insert');
                        $PK_USER = $db->insert_ID();

                        $USER_LOCATION_DATA = array(
                            'PK_USER' => $PK_USER,
                            'PK_LOCATION' => $PK_LOCATION
                        );
                        //pre_r($USER_LOCATION_DATA);
                        db_perform('DOA_USER_LOCATION', $USER_LOCATION_DATA, 'insert');

                        // Assign role to user
                        if (!empty($user['role']) && $PK_USER) {
                            $ROLE_DATA = array(
                                'PK_USER' => $PK_USER,
                                'PK_ROLES' => trim($user['role'] ?? '')
                            );
                            //pre_r($ROLE_DATA);
                            db_perform('DOA_USER_ROLES', $ROLE_DATA, 'insert');
                        }
                    }
                }
            }

            // Insert services
            if (isset($_POST['service']) && is_array($_POST['service'])) {
                foreach ($_POST['service'] as $service) {
                    if (!empty($service['name'])) {
                        $SERVICE_DATA = array(
                            'PK_LOCATION' => $PK_LOCATION,
                            'SERVICE_NAME' => trim($service['name'] ?? ''),
                            'PK_SERVICE_CLASS' => trim($service['class'] ?? ''),
                            'DESCRIPTION' => trim($service['description'] ?? ''),
                            'ACTIVE' => 1,
                            'CREATED_BY' => $_SESSION['PK_USER'],
                            'CREATED_ON' => date("Y-m-d H:i:s")
                        );
                        //pre_r($SERVICE_DATA);
                        db_perform_account('DOA_SERVICE_MASTER', $SERVICE_DATA, 'insert');
                        $PK_SERVICE_MASTER = $db_account->insert_ID();

                        if ($PK_SERVICE_MASTER) {
                            // Link service to service code
                            $SERVICE_CODE_DATA = array(
                                'PK_SERVICE_MASTER' => $PK_SERVICE_MASTER,
                                'PK_LOCATION' => $PK_LOCATION,
                                'SERVICE_CODE' => trim($service['code'] ?? ''),
                                'DESCRIPTION' => trim($service['description'] ?? ''),
                                'IS_GROUP' => isset($service['group']) ? 1 : 0,
                                'IS_CHARGEABLE' => isset($service['chargeable']) ? 1 : 0,
                                'PRICE' => floatval(number_format($service['price'], 2) ?? 0),
                                'ACTIVE' => 1,
                                'COUNT_ON_CALENDAR' => isset($service['calendar']) ? 1 : 0,
                                'SORT_ORDER' => intval($service['sort'] ?? 0)
                            );
                            //pre_r($SERVICE_CODE_DATA);
                            db_perform_account('DOA_SERVICE_CODE', $SERVICE_CODE_DATA, 'insert');
                            $PK_SERVICE_CODE = $db_account->insert_ID();
                        }
                    }
                }
            }

            // Insert packages
            if (isset($_POST['package']) && is_array($_POST['package'])) {
                foreach ($_POST['package'] as $package) {
                    if (!empty($package['name'])) {
                        $PACKAGE_DATA = array(
                            'PK_LOCATION' => $PK_LOCATION,
                            'PACKAGE_NAME' => trim($package['name'] ?? ''),
                            //'PACKAGE_CODE' => trim($package['code'] ?? ''),
                            //'PRICE' => floatval($package['price'] ?? 0),
                            //'BILLING_CYCLE' => trim($package['billing'] ?? ''),
                            //'DESCRIPTION' => trim($package['description'] ?? ''),
                            //'SERVICES_INCLUDED' => trim($package['services'] ?? ''),
                            //'SESSION_LIMIT' => !empty($package['limit']) ? intval($package['limit']) : null,
                            'EXPIRY_DATE' => !empty($package['expiry']) ? intval($package['expiry']) : null,
                            'SORT_ORDER' => intval($package['sort'] ?? 0),
                            'ACTIVE' => isset($package['active']) ? 1 : 0,
                            //'CHARGEABLE' => isset($package['chargeable']) ? 1 : 0,
                            'ACTIVE' => 1,
                            'CREATED_BY' => $_SESSION['PK_USER'],
                            'CREATED_ON' => date("Y-m-d H:i:s")
                        );
                        //pre_r($PACKAGE_DATA);
                        db_perform_account('DOA_PACKAGE', $PACKAGE_DATA, 'insert');
                        $PK_PACKAGE = $db_account->insert_ID();

                        if ($PK_PACKAGE) {
                            $PACKAGE_LOCATION_DATA = array(
                                'PK_PACKAGE' => $PK_PACKAGE,
                                'PK_LOCATION' => $PK_LOCATION
                            );
                            //pre_r($PACKAGE_LOCATION_DATA);
                            db_perform_account('DOA_PACKAGE_LOCATION', $PACKAGE_LOCATION_DATA, 'insert');

                            // Link package to services

                            $PACKAGE_SERVICE_DATA = array(
                                'PK_PACKAGE' => $PK_PACKAGE,
                                'PK_SERVICE_MASTER' => $PK_SERVICE_MASTER,
                                'PK_SERVICE_CODE' => $PK_SERVICE_CODE,
                                'SERVICE_DETAILS' => trim($package['services'] ?? ''),
                                'FINAL_AMOUNT' => floatval($package['price'] ?? 0),
                                'ACTIVE' => 1
                            );
                            //pre_r($PACKAGE_SERVICE_DATA);
                            db_perform_account('DOA_PACKAGE_SERVICE', $PACKAGE_SERVICE_DATA, 'insert');
                        }
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doable Setup Wizard</title>
    <style>
        /* Your existing CSS remains the same */
        html,
        body {
            height: 100%;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #1a1a1a;
            background: #f9fafb;
        }

        .content {
            flex: 1;
            padding: 20px 0;
        }

        .footer {
            text-align: center;
            padding: 10px 0;
            font-size: 12px;
            color: #828080;
        }

        .wizard {
            max-width: 900px;
            margin: 0 auto;
            display: flex;
            gap: 24px;
            align-items: flex-start;
            padding: 0 20px;
        }

        .sidebar {
            width: 160px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            padding-top: 6px;
            position: sticky;
            top: 2rem;
        }

        .side-step {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            cursor: pointer;
            position: relative;
        }

        .side-line {
            position: absolute;
            left: 15px;
            top: 32px;
            width: 2px;
            background: #d0d7de;
            bottom: 0;
            z-index: 0;
        }

        .side-line.done {
            background: #39B54A;
        }

        .dot {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: 1.5px solid #d0d7de;
            background: #f0f2f5;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 600;
            transition: all .2s;
            color: #666;
            flex-shrink: 0;
            position: relative;
            z-index: 1;
        }

        .dot.active {
            background: #39B54A;
            border-color: #39B54A;
            color: #fff;
        }

        .dot.done {
            background: #39B54A;
            border-color: #39B54A;
            color: #fff;
        }

        .side-label {
            font-size: 12px;
            color: #666;
            line-height: 1.3;
            padding-top: 7px;
            padding-bottom: 28px;
        }

        .side-label.active {
            color: #39B54A;
            font-weight: 600;
        }

        .side-label.done {
            color: #39B54A;
        }

        .main {
            flex: 1;
            min-width: 0;
        }

        .panel {
            background: #fff;
            border: 1px solid #d0d7de;
            border-radius: 12px;
            padding: 1.75rem;
        }

        .panel-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 4px;
            color: #111;
        }

        .panel-sub {
            font-size: 13px;
            color: #666;
            margin-bottom: 1.5rem;
        }

        .record-block {
            border: 1px solid #d0d7de;
            border-radius: 8px;
            margin-bottom: 10px;
            overflow: hidden;
        }

        .record-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.65rem 1rem;
            background: #f5f6f8;
            cursor: pointer;
            user-select: none;
            border-bottom: 1px solid #d0d7de;
        }

        .record-label {
            font-size: 13px;
            font-weight: 600;
            color: #555;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .chevron {
            font-size: 11px;
            color: #888;
            transition: transform .2s;
            display: inline-block;
        }

        .chevron.open {
            transform: rotate(180deg);
        }

        .record-body {
            padding: 1rem;
            background: #fff;
            display: none;
        }

        .record-body.open {
            display: block;
        }

        .remove-btn {
            font-size: 12px;
            color: #a32d2d;
            background: none;
            border: 1px solid #f09595;
            border-radius: 6px;
            padding: 3px 10px;
            cursor: pointer;
        }

        .remove-btn:hover {
            background: #fdecea;
        }

        .field-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .field-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .field-group.full {
            grid-column: 1 / -1;
        }

        label {
            font-size: 12px;
            color: #555;
            font-weight: 600;
            letter-spacing: 0.01em;
        }

        .req {
            color: #e24b4a;
            margin-left: 2px;
        }

        input[type=text],
        input[type=email],
        input[type=tel],
        input[type=number],
        select,
        textarea {
            width: 100%;
            font-size: 14px;
            padding: 8px 11px;
            border: 1.5px solid #b0b8c4;
            border-radius: 7px;
            background: #fff;
            color: #1a1a1a;
            outline: none;
            transition: border-color .15s, box-shadow .15s;
            font-family: inherit;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: #39B54A;
            box-shadow: 0 0 0 3px rgba(57, 181, 74, 0.1);
        }

        input:hover,
        select:hover,
        textarea:hover {
            border-color: #39B54A;
        }

        input::placeholder,
        textarea::placeholder {
            color: #aaa;
            font-size: 13px;
        }

        textarea {
            resize: vertical;
            min-height: 64px;
        }

        select {
            cursor: pointer;
        }

        .add-btn {
            display: flex;
            align-items: center;
            gap: 6px;
            background: none;
            border: 1.5px dashed #39B54A;
            border-radius: 8px;
            padding: 9px 14px;
            font-size: 13px;
            color: #39B54A;
            cursor: pointer;
            width: 100%;
            justify-content: center;
            margin-top: 6px;
            transition: background .15s;
            font-family: inherit;
        }

        .add-btn:hover {
            background: #e8f5e9;
        }

        .nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1.25rem;
        }

        .btn {
            padding: 9px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            border: 1.5px solid #d0d7de;
            background: #f0f2f5;
            color: #333;
            transition: background .15s;
            font-family: inherit;
        }

        .btn:hover {
            background: #e2e6ea;
        }

        .btn.primary {
            background: #39B54A;
            border-color: #39B54A;
            color: #fff;
        }

        .btn.primary:hover {
            background: #2e9a3e;
        }

        .checkrow {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .checkrow input[type=checkbox] {
            width: 16px;
            height: 16px;
            cursor: pointer;
            accent-color: #39B54A;
        }

        .checkrow label {
            font-size: 14px;
            color: #333;
            cursor: pointer;
            font-weight: 400;
        }

        .pkg-divider {
            font-size: 11px;
            font-weight: 700;
            color: #666;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            border-bottom: 1px solid #e0e4ea;
            padding-bottom: 5px;
            margin: 1rem 0 0.25rem;
            grid-column: 1 / -1;
        }

        .success-panel {
            text-align: center;
            padding: 3rem 1.5rem;
        }

        .success-icon {
            font-size: 48px;
            margin-bottom: 1rem;
            color: #39B54A;
        }

        .nav-new {
            margin: 0 auto;
            width: 100%;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            background: #fff;
            z-index: 9;
            padding: 10px 0;
        }

        .container-fluid {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .d-flex {
            display: flex;
        }

        .justify-content-between {
            justify-content: space-between;
        }

        .align-items-center {
            align-items: center;
        }

        .py-3 {
            padding-top: 12px;
            padding-bottom: 12px;
        }

        .px-5 {
            padding-left: 20px;
            padding-right: 20px;
        }

        .fw-bold {
            font-weight: bold;
        }

        .fs-4 {
            font-size: 1.5rem;
        }

        .text-center {
            text-align: center;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        img {
            max-width: 100%;
            height: auto;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 20px;
            text-align: center;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
</head>

<body>
    <nav class="container-fluid d-flex justify-content-between align-items-center py-3 px-10 nav-new">
        <div class="fw-bold fs-4" style="padding: 5px;">
            <a href="https://doable.net/">
                <img width="150" src="../demo1/images/doable_logo.png" alt="Doable Logo" onerror="this.src='https://via.placeholder.com/150x50?text=Doable'">
            </a>
        </div>
    </nav>

    <main class="content">
        <section class="text-center" style="margin-top: 40px;">
            <div class="container">
                <?php if (isset($error) && $error): ?>
                    <div class="error-message">⚠️ <?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <?php if (isset($success) && $success): ?>
                    <div class="success-message">✓ Setup completed successfully!</div>
                    <div><a href="calendar.php">Click here to go to the main page.</a></div>
                <?php endif; ?>

                <?php if (!isset($success) || !$success): ?>
                    <div class="wizard">
                        <div class="sidebar" id="sidebar"></div>
                        <div class="main">
                            <form id="wizardForm" method="POST" action="">
                                <div id="panels"></div>
                                <div class="nav" id="nav">
                                    <button type="button" class="btn" id="backBtn" onclick="navigate(-1)">← Back</button>
                                    <span id="stepCounter" style="font-size:13px;color:#666"></span>
                                    <button type="button" class="btn primary" id="nextBtn" onclick="navigate(1)">Next →</button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <footer class="footer">
        © <?= date('Y'); ?> Doable LLC
    </footer>

    <script>
        // Simple solution - ensure all fields are visible and submitted
        let currentStep = 0;
        const steps = ['corp', 'location', 'users', 'services', 'packages'];

        // Store data in hidden fields to ensure submission
        function updateCorpData() {
            const corpName = document.querySelector('input[name="corp_name"]');
            const corpCard = document.querySelector('input[name="corp_card"]');

            if (corpName) {
                console.log('Corp name value:', corpName.value);
            }
        }

        function showStep(step) {
            // Hide all panels
            document.querySelectorAll('.panel-step').forEach(panel => {
                panel.style.display = 'none';
            });

            // Show current panel
            const currentPanel = document.getElementById('step-' + steps[step]);
            if (currentPanel) {
                currentPanel.style.display = 'block';
            }

            // Update sidebar
            updateSidebar(step);

            // Update buttons
            document.getElementById('backBtn').style.visibility = step === 0 ? 'hidden' : 'visible';
            const nextBtn = document.getElementById('nextBtn');
            nextBtn.textContent = step === steps.length - 1 ? 'Submit ✓' : 'Next →';
            document.getElementById('stepCounter').textContent = `Step ${step + 1} of ${steps.length}`;
        }

        function updateSidebar(activeStep) {
            const sidebar = document.getElementById('sidebar');
            const stepNames = ['Corporation', 'Locations', 'Users', 'Services', 'Packages'];

            sidebar.innerHTML = stepNames.map((name, idx) => {
                let dotClass = '';
                let labelClass = '';
                let lineClass = '';
                let symbol = idx + 1;

                if (idx < activeStep) {
                    dotClass = 'done';
                    labelClass = 'done';
                    lineClass = 'done';
                    symbol = '✓';
                } else if (idx === activeStep) {
                    dotClass = 'active';
                    labelClass = 'active';
                }

                return `
                    <div class="side-step" onclick="goToStep(${idx})">
                        ${idx < steps.length - 1 ? `<div class="side-line ${lineClass}"></div>` : ''}
                        <div class="dot ${dotClass}">${symbol}</div>
                        <span class="side-label ${labelClass}">${name}</span>
                    </div>
                `;
            }).join('');
        }

        function goToStep(step) {
            currentStep = step;
            showStep(currentStep);
        }

        function navigate(direction) {
            if (direction === 1 && currentStep === steps.length - 1) {
                // Before submitting, make sure all data is captured
                updateCorpData();

                if (confirm('Are you sure you want to submit all data?')) {
                    document.getElementById('wizardForm').submit();
                }
                return;
            }

            currentStep += direction;
            if (currentStep < 0) currentStep = 0;
            if (currentStep >= steps.length) currentStep = steps.length - 1;
            showStep(currentStep);
        }

        function addRecord(type) {
            const list = document.getElementById(type + '-list');
            if (!list) return;

            const counter = list.querySelectorAll('.record-block').length + 1;
            const uid = type + '-' + counter;

            let fieldsHtml = '';
            if (type === 'location') {
                fieldsHtml = getLocationFields(counter);
            } else if (type === 'user') {
                fieldsHtml = getUserFields(counter);
            } else if (type === 'service') {
                fieldsHtml = getServiceFields(counter);
            } else if (type === 'package') {
                fieldsHtml = getPackageFields(counter);
            }

            const div = document.createElement('div');
            div.className = 'record-block';
            div.id = uid;
            div.innerHTML = `
                <div class="record-header" onclick="toggleRecord('${uid}')">
                    <span class="record-label">
                        <span class="chevron open" id="chev-${uid}">▼</span>
                        ${type.charAt(0).toUpperCase() + type.slice(1)} #${counter}
                    </span>
                    ${counter > 1 ? `<button type="button" class="remove-btn" onclick="event.stopPropagation();removeRecord('${uid}')">Remove</button>` : ''}
                </div>
                <div class="record-body open" id="body-${uid}">${fieldsHtml}</div>
            `;
            list.appendChild(div);
        }

        function getLocationFields(i) {
            return `<div class="field-grid">
                <div class="field-group full"><label>Location Name / Business Name / DBA <span class="req">*</span></label><input type="text" name="location[${i}][name]" placeholder="e.g. Main Street Branch"></div>
                <div class="field-group"><label>City <span class="req">*</span></label><input type="text" name="location[${i}][city]" placeholder="City"></div>
                <div class="field-group"><label>State <span class="req">*</span></label><input type="text" name="location[${i}][state]" placeholder="State"></div>
                <div class="field-group"><label>ZIP Code <span class="req">*</span></label><input type="text" name="location[${i}][zip]" placeholder="ZIP"></div>
                <div class="field-group"><label>Email <span class="req">*</span></label><input type="email" name="location[${i}][email]" placeholder="location@email.com"></div>
                <div class="field-group"><label>Time Zone <span class="req">*</span></label>
                    <select name="location[${i}][timezone]"><option value="">Select time zone</option><option>Eastern Time (ET)</option><option>Central Time (CT)</option><option>Mountain Time (MT)</option><option>Pacific Time (PT)</option></select>
                </div>
                <div class="field-group"><label>Operational Hours <span class="req">*</span></label><input type="text" name="location[${i}][hours]" placeholder="e.g. Mon–Fri 9am–6pm"></div>
                <div class="field-group full"><label>Credit Card <span class="req">*</span></label><input type="text" name="location[${i}][card]" placeholder="Card on file for this location"></div>
            </div>`;
        }

        function getUserFields(i) {
            return `<div class="field-grid">
                <div class="field-group"><label>Name <span class="req">*</span></label><input type="text" name="user[${i}][name]" placeholder="Full name"></div>
                <div class="field-group"><label>Email <span class="req">*</span></label><input type="email" name="user[${i}][email]" placeholder="user@email.com"></div>
                <div class="field-group"><label>Login <span class="req">*</span></label><input type="text" name="user[${i}][login]" placeholder="Username or login ID"></div>
                <div class="field-group"><label>Role <span class="req">*</span></label>
                    <select name="user[${i}][role]"><option value="">Select role</option><option value="2">Account Admin</option><option value="3">Manager</option><option value="4">Administrative Assistant</option><option value="5">Counsellor</option><option value="6">Supervisor</option><option value="7">Service Provider</option><option value="8">Account User</option><option value="9">Account Accountant</option><option value="10">Customer</option></select>
                </div>
                <div class="field-group"><label>Service Hours <span class="req">*</span></label><input type="text" name="user[${i}][hours]" placeholder="e.g. Mon–Fri 9am–5pm"></div>
                <div class="field-group" style="justify-content:flex-end;padding-top:20px">
                    <div class="checkrow"><input type="checkbox" name="user[${i}][calendar]" id="cal-${i}" value="1"><label for="cal-${i}">Appear in Calendar</label></div>
                </div>
            </div>`;
        }

        function getServiceFields(i) {
            return `<div class="field-grid">
                <div class="field-group"><label>Service Name <span class="req">*</span></label><input type="text" name="service[${i}][name]" placeholder="e.g. Initial Consultation"></div>
                <div class="field-group"><label>Code <span class="req">*</span></label><input type="text" name="service[${i}][code]" placeholder="e.g. SVC-001"></div>
                <div class="field-group"><label>Price <span class="req">*</span></label><input type="text" name="service[${i}][price]" placeholder="$0.00"></div>
                <div class="field-group"><label>Service Class <span class="req">*</span></label>
                    <select name="service[${i}][class]"><option value="">Select class</option><option value="1">Membership</option><option value="2">Sessions</option><option value="5">MISC</option></select>
                </div>
                <div class="field-group full"><label>Description <span class="req">*</span></label><textarea name="service[${i}][description]" placeholder="Brief description of this service"></textarea></div>
                <div class="field-group"><label>Sort Number <span class="req">*</span></label><input type="number" name="service[${i}][sort]" placeholder="1"></div>
                <div class="field-group" style="padding-top:4px">
                    <div style="display:flex;flex-direction:column;gap:8px">
                        <div class="checkrow"><input type="checkbox" name="service[${i}][chargeable]" id="chargeable-${i}" value="1"><label for="chargeable-${i}">Chargeable to client account</label></div>
                        <div class="checkrow"><input type="checkbox" name="service[${i}][group]" id="group-${i}" value="1"><label for="group-${i}">Is this a group service?</label></div>
                        <div class="checkrow"><input type="checkbox" name="service[${i}][calendar]" id="calcount-${i}" value="1"><label for="calcount-${i}">Show in calendar count</label></div>
                    </div>
                </div>
            </div>`;
        }

        function getPackageFields(i) {
            return `<div class="field-grid">
                <div class="pkg-divider">Package Details</div>
                <div class="field-group"><label>Package Name <span class="req">*</span></label><input type="text" name="package[${i}][name]" placeholder="e.g. Starter Pack"></div>
                <div class="field-group"><label>Package Code <span class="req">*</span></label><input type="text" name="package[${i}][code]" placeholder="e.g. PKG-001"></div>
                <div class="field-group"><label>Price <span class="req">*</span></label><input type="text" name="package[${i}][price]" placeholder="$0.00"></div>
                <div class="field-group"><label>Billing Cycle <span class="req">*</span></label>
                    <select name="package[${i}][billing]"><option value="">Select</option><option>One-time</option><option>Weekly</option><option>Monthly</option><option>Annually</option></select>
                </div>
                <div class="field-group full"><label>Description <span class="req">*</span></label><textarea name="package[${i}][description]" placeholder="What's included in this package?"></textarea></div>
                <div class="pkg-divider">Services Included</div>
                <div class="field-group full"><label>Services <span class="req">*</span></label><input type="text" name="package[${i}][services]" placeholder="e.g. SVC-001, SVC-002 (comma-separated codes)"></div>
                <div class="field-group"><label>Session / Visit Limit</label><input type="number" name="package[${i}][limit]" placeholder="Leave blank for unlimited"></div>
                <div class="field-group"><label>Expiry (days)</label><input type="number" name="package[${i}][expiry]" placeholder="Leave blank if none"></div>
                <div class="pkg-divider">Options</div>
                <div class="field-group"><label>Sort Number <span class="req">*</span></label><input type="number" name="package[${i}][sort]" placeholder="1"></div>
                <div class="field-group" style="padding-top:4px">
                    <div style="display:flex;flex-direction:column;gap:8px">
                        <div class="checkrow"><input type="checkbox" name="package[${i}][active]" id="pkg-active-${i}" value="1"><label for="pkg-active-${i}">Active / available for purchase</label></div>
                        <div class="checkrow"><input type="checkbox" name="package[${i}][chargeable]" id="pkg-client-${i}" value="1"><label for="pkg-client-${i}">Chargeable to client account</label></div>
                    </div>
                </div>
            </div>`;
        }

        function toggleRecord(uid) {
            const body = document.getElementById('body-' + uid);
            const chev = document.getElementById('chev-' + uid);
            if (body && chev) {
                const open = body.classList.contains('open');
                body.classList.toggle('open', !open);
                chev.classList.toggle('open', !open);
            }
        }

        function removeRecord(uid) {
            const el = document.getElementById(uid);
            if (el) el.remove();
        }

        // Initialize the wizard
        document.addEventListener('DOMContentLoaded', function() {
            // Create panels container
            const panelsContainer = document.getElementById('panels');

            // Create corporation panel
            const corpPanel = document.createElement('div');
            corpPanel.id = 'step-corp';
            corpPanel.className = 'panel-step';
            corpPanel.innerHTML = `
                <div class="panel">
                    <div class="panel-title">Corporation / Entity</div>
                    <div class="panel-sub">Basic information about your corporation or business entity.</div>
                    <div class="field-grid">
                        <div class="field-group full">
                            <label>Corporation / Entity Name <span class="req">*</span></label>
                            <input type="text" name="corp_name" id="corp_name" placeholder="e.g. Acme Corp — or owner's name if no formal entity">
                        </div>
                        <div class="field-group full">
                            <label>Credit Card <span class="req">*</span></label>
                            <input type="text" name="corp_card" id="corp_card" placeholder="Card on file">
                        </div>
                    </div>
                </div>
            `;

            // Create location panel
            const locationPanel = document.createElement('div');
            locationPanel.id = 'step-location';
            locationPanel.className = 'panel-step';
            locationPanel.style.display = 'none';
            locationPanel.innerHTML = `
                <div class="panel">
                    <div class="panel-title">Locations</div>
                    <div class="panel-sub">Add one or more business locations. Click a header to expand or collapse.</div>
                    <div id="location-list"></div>
                    <button type="button" class="add-btn" onclick="addRecord('location')">+ Add another location</button>
                </div>
            `;

            // Create users panel
            const usersPanel = document.createElement('div');
            usersPanel.id = 'step-users';
            usersPanel.className = 'panel-step';
            usersPanel.style.display = 'none';
            usersPanel.innerHTML = `
                <div class="panel">
                    <div class="panel-title">Users / Employees / Contractors</div>
                    <div class="panel-sub">Add all team members who will use the system.</div>
                    <div id="user-list"></div>
                    <button type="button" class="add-btn" onclick="addRecord('user')">+ Add another user</button>
                </div>
            `;

            // Create services panel
            const servicesPanel = document.createElement('div');
            servicesPanel.id = 'step-services';
            servicesPanel.className = 'panel-step';
            servicesPanel.style.display = 'none';
            servicesPanel.innerHTML = `
                <div class="panel">
                    <div class="panel-title">Services</div>
                    <div class="panel-sub">Define the services your business offers. A default scheduling code is applied automatically.</div>
                    <div id="service-list"></div>
                    <button type="button" class="add-btn" onclick="addRecord('service')">+ Add another service</button>
                </div>
            `;

            // Create packages panel
            const packagesPanel = document.createElement('div');
            packagesPanel.id = 'step-packages';
            packagesPanel.className = 'panel-step';
            packagesPanel.style.display = 'none';
            packagesPanel.innerHTML = `
                <div class="panel">
                    <div class="panel-title">Packages</div>
                    <div class="panel-sub">Bundle services into packages for client purchase or subscription.</div>
                    <div id="package-list"></div>
                    <button type="button" class="add-btn" onclick="addRecord('package')">+ Add another package</button>
                </div>
            `;

            panelsContainer.appendChild(corpPanel);
            panelsContainer.appendChild(locationPanel);
            panelsContainer.appendChild(usersPanel);
            panelsContainer.appendChild(servicesPanel);
            panelsContainer.appendChild(packagesPanel);

            // Add default records
            addRecord('location');
            addRecord('user');
            addRecord('service');
            addRecord('package');

            // Show first step
            showStep(0);
        });

        function showStep(step) {
            currentStep = step;
            // Hide all panels
            document.querySelectorAll('.panel-step').forEach(panel => {
                panel.style.display = 'none';
            });

            // Show current panel
            const currentPanel = document.getElementById('step-' + steps[step]);
            if (currentPanel) {
                currentPanel.style.display = 'block';
            }

            // Update sidebar
            updateSidebar(step);

            // Update buttons
            const backBtn = document.getElementById('backBtn');
            const nextBtn = document.getElementById('nextBtn');
            if (backBtn) backBtn.style.visibility = step === 0 ? 'hidden' : 'visible';
            if (nextBtn) nextBtn.textContent = step === steps.length - 1 ? 'Submit ✓' : 'Next →';
            const stepCounter = document.getElementById('stepCounter');
            if (stepCounter) stepCounter.textContent = `Step ${step + 1} of ${steps.length}`;
        }

        function updateSidebar(activeStep) {
            const sidebar = document.getElementById('sidebar');
            const stepNames = ['Corporation', 'Locations', 'Users', 'Services', 'Packages'];

            sidebar.innerHTML = stepNames.map((name, idx) => {
                let dotClass = '';
                let labelClass = '';
                let lineClass = '';
                let symbol = idx + 1;

                if (idx < activeStep) {
                    dotClass = 'done';
                    labelClass = 'done';
                    lineClass = 'done';
                    symbol = '✓';
                } else if (idx === activeStep) {
                    dotClass = 'active';
                    labelClass = 'active';
                }

                return `
                    <div class="side-step" onclick="goToStep(${idx})">
                        ${idx < steps.length - 1 ? `<div class="side-line ${lineClass}"></div>` : ''}
                        <div class="dot ${dotClass}">${symbol}</div>
                        <span class="side-label ${labelClass}">${name}</span>
                    </div>
                `;
            }).join('');
        }

        function goToStep(step) {
            showStep(step);
        }

        function navigate(direction) {
            if (direction === 1 && currentStep === steps.length - 1) {
                if (confirm('Are you sure you want to submit all data?')) {
                    document.getElementById('wizardForm').submit();
                }
                return;
            }

            const newStep = currentStep + direction;
            if (newStep >= 0 && newStep < steps.length) {
                showStep(newStep);
            }
        }

        // Make functions globally available
        window.addRecord = addRecord;
        window.toggleRecord = toggleRecord;
        window.removeRecord = removeRecord;
        window.navigate = navigate;
        window.goToStep = goToStep;
    </script>
</body>

</html>