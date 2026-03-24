<?php
require_once('global/config.php');

global $db;
global $db_account;

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
            'CREDIT_CARD' => $corpCard,
            'ACTIVE' => 1,
            'CREATED_BY' => $_SESSION['PK_USER'],
            'CREATED_ON' => date("Y-m-d H:i:s")
        );

        db_perform('DOA_CORPORATION', $CORPORATION_DATA, 'insert');
        $PK_CORPORATION = $db->insert_ID();

        if ($PK_CORPORATION) {
            $success = true;

            // Insert locations
            if (isset($_POST['location']) && is_array($_POST['location'])) {
                foreach ($_POST['location'] as $location) {
                    if (!empty($location['name'])) {
                        $LOCATION_DATA = array(
                            'PK_CORPORATION' => $PK_CORPORATION,
                            'LOCATION_NAME' => trim($location['name'] ?? ''),
                            'CITY' => trim($location['city'] ?? ''),
                            'STATE' => trim($location['state'] ?? ''),
                            'ZIP_CODE' => trim($location['zip'] ?? ''),
                            'EMAIL' => trim($location['email'] ?? ''),
                            'TIMEZONE' => trim($location['timezone'] ?? ''),
                            'OPERATIONAL_HOURS' => trim($location['hours'] ?? ''),
                            'CREDIT_CARD' => trim($location['card'] ?? ''),
                            'ACTIVE' => 1,
                            'CREATED_BY' => $_SESSION['PK_USER'],
                            'CREATED_ON' => date("Y-m-d H:i:s")
                        );
                        db_perform('DOA_LOCATION', $LOCATION_DATA, 'insert');
                    }
                }
            }

            // Insert users
            if (isset($_POST['user']) && is_array($_POST['user'])) {
                foreach ($_POST['user'] as $user) {
                    if (!empty($user['name'])) {
                        $USER_DATA = array(
                            'PK_CORPORATION' => $PK_CORPORATION,
                            'USER_NAME' => trim($user['name'] ?? ''),
                            'EMAIL' => trim($user['email'] ?? ''),
                            'LOGIN' => trim($user['login'] ?? ''),
                            'ROLE' => trim($user['role'] ?? ''),
                            'SERVICE_HOURS' => trim($user['hours'] ?? ''),
                            'APPEAR_IN_CALENDAR' => isset($user['calendar']) ? 1 : 0,
                            'ACTIVE' => 1,
                            'CREATED_BY' => $_SESSION['PK_USER'],
                            'CREATED_ON' => date("Y-m-d H:i:s")
                        );
                        db_perform('DOA_USER', $USER_DATA, 'insert');
                    }
                }
            }

            // Insert services
            if (isset($_POST['service']) && is_array($_POST['service'])) {
                foreach ($_POST['service'] as $service) {
                    if (!empty($service['name'])) {
                        $SERVICE_DATA = array(
                            'PK_CORPORATION' => $PK_CORPORATION,
                            'SERVICE_NAME' => trim($service['name'] ?? ''),
                            'SERVICE_CODE' => trim($service['code'] ?? ''),
                            'PRICE' => floatval($service['price'] ?? 0),
                            'SERVICE_CLASS' => trim($service['class'] ?? ''),
                            'DESCRIPTION' => trim($service['description'] ?? ''),
                            'SORT_NUMBER' => intval($service['sort'] ?? 0),
                            'CHARGEABLE' => isset($service['chargeable']) ? 1 : 0,
                            'IS_GROUP_SERVICE' => isset($service['group']) ? 1 : 0,
                            'SHOW_IN_CALENDAR' => isset($service['calendar']) ? 1 : 0,
                            'ACTIVE' => 1,
                            'CREATED_BY' => $_SESSION['PK_USER'],
                            'CREATED_ON' => date("Y-m-d H:i:s")
                        );
                        db_perform('DOA_SERVICE', $SERVICE_DATA, 'insert');
                    }
                }
            }

            // Insert packages
            if (isset($_POST['package']) && is_array($_POST['package'])) {
                foreach ($_POST['package'] as $package) {
                    if (!empty($package['name'])) {
                        $PACKAGE_DATA = array(
                            'PK_CORPORATION' => $PK_CORPORATION,
                            'PACKAGE_NAME' => trim($package['name'] ?? ''),
                            'PACKAGE_CODE' => trim($package['code'] ?? ''),
                            'PRICE' => floatval($package['price'] ?? 0),
                            'BILLING_CYCLE' => trim($package['billing'] ?? ''),
                            'DESCRIPTION' => trim($package['description'] ?? ''),
                            'SERVICES_INCLUDED' => trim($package['services'] ?? ''),
                            'SESSION_LIMIT' => !empty($package['limit']) ? intval($package['limit']) : null,
                            'EXPIRY_DAYS' => !empty($package['expiry']) ? intval($package['expiry']) : null,
                            'SORT_NUMBER' => intval($package['sort'] ?? 0),
                            'IS_ACTIVE' => isset($package['active']) ? 1 : 0,
                            'CHARGEABLE' => isset($package['chargeable']) ? 1 : 0,
                            'ACTIVE' => 1,
                            'CREATED_BY' => $_SESSION['PK_USER'],
                            'CREATED_ON' => date("Y-m-d H:i:s")
                        );
                        db_perform('DOA_PACKAGE', $PACKAGE_DATA, 'insert');
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

        .btn.primary:hover {
            background: #2d8e3a;
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
    <nav class="container-fluid d-flex justify-content-between align-items-center py-3 px-5 nav-new">
        <div class="fw-bold fs-4" style="padding: 5px;">
            <a href="index.php">
                <img width="150" src="demo1/images/doable_logo.png" alt="Doable Logo" onerror="this.src='https://via.placeholder.com/150x50?text=Doable'">
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
                    <div class="success-message">✓ Setup completed successfully! Corporation ID: <?= $PK_CORPORATION ?></div>
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
        const steps = [{
                id: 'corp',
                label: 'Corporation'
            },
            {
                id: 'location',
                label: 'Locations'
            },
            {
                id: 'users',
                label: 'Users'
            },
            {
                id: 'services',
                label: 'Services'
            },
            {
                id: 'packages',
                label: 'Packages'
            }
        ];

        let current = 0;
        const counters = {
            location: 0,
            user: 0,
            service: 0,
            package: 0
        };

        // Store all form data
        let formData = {
            corp_name: '',
            corp_card: '',
            location: {},
            user: {},
            service: {},
            package: {}
        };

        function toggleRecord(uid) {
            const body = document.getElementById('body-' + uid);
            const chev = document.getElementById('chev-' + uid);
            if (body && chev) {
                const open = body.classList.contains('open');
                body.classList.toggle('open', !open);
                chev.classList.toggle('open', !open);
            }
        }

        function saveCurrentPanelData() {
            const currentId = steps[current].id;

            if (currentId === 'corp') {
                const corpName = document.querySelector('input[name="corp_name"]');
                const corpCard = document.querySelector('input[name="corp_card"]');
                if (corpName) formData.corp_name = corpName.value;
                if (corpCard) formData.corp_card = corpCard.value;
            } else if (currentId === 'location') {
                // Save location data
                const locations = document.querySelectorAll('[id^="location-"]');
                locations.forEach((location, idx) => {
                    const i = idx + 1;
                    formData.location[i] = {
                        name: document.querySelector(`input[name="location[${i}][name]"]`)?.value || '',
                        city: document.querySelector(`input[name="location[${i}][city]"]`)?.value || '',
                        state: document.querySelector(`input[name="location[${i}][state]"]`)?.value || '',
                        zip: document.querySelector(`input[name="location[${i}][zip]"]`)?.value || '',
                        email: document.querySelector(`input[name="location[${i}][email]"]`)?.value || '',
                        timezone: document.querySelector(`select[name="location[${i}][timezone]"]`)?.value || '',
                        hours: document.querySelector(`input[name="location[${i}][hours]"]`)?.value || '',
                        card: document.querySelector(`input[name="location[${i}][card]"]`)?.value || ''
                    };
                });
            } else if (currentId === 'users') {
                // Save user data
                const users = document.querySelectorAll('[id^="user-"]');
                users.forEach((user, idx) => {
                    const i = idx + 1;
                    formData.user[i] = {
                        name: document.querySelector(`input[name="user[${i}][name]"]`)?.value || '',
                        email: document.querySelector(`input[name="user[${i}][email]"]`)?.value || '',
                        login: document.querySelector(`input[name="user[${i}][login]"]`)?.value || '',
                        role: document.querySelector(`select[name="user[${i}][role]"]`)?.value || '',
                        hours: document.querySelector(`input[name="user[${i}][hours]"]`)?.value || '',
                        calendar: document.querySelector(`input[name="user[${i}][calendar]"]`)?.checked || false
                    };
                });
            } else if (currentId === 'services') {
                // Save service data
                const services = document.querySelectorAll('[id^="service-"]');
                services.forEach((service, idx) => {
                    const i = idx + 1;
                    formData.service[i] = {
                        name: document.querySelector(`input[name="service[${i}][name]"]`)?.value || '',
                        code: document.querySelector(`input[name="service[${i}][code]"]`)?.value || '',
                        price: document.querySelector(`input[name="service[${i}][price]"]`)?.value || '',
                        class: document.querySelector(`input[name="service[${i}][class]"]`)?.value || '',
                        description: document.querySelector(`textarea[name="service[${i}][description]"]`)?.value || '',
                        sort: document.querySelector(`input[name="service[${i}][sort]"]`)?.value || '',
                        chargeable: document.querySelector(`input[name="service[${i}][chargeable]"]`)?.checked || false,
                        group: document.querySelector(`input[name="service[${i}][group]"]`)?.checked || false,
                        calendar: document.querySelector(`input[name="service[${i}][calendar]"]`)?.checked || false
                    };
                });
            } else if (currentId === 'packages') {
                // Save package data
                const packages = document.querySelectorAll('[id^="package-"]');
                packages.forEach((pkg, idx) => {
                    const i = idx + 1;
                    formData.package[i] = {
                        name: document.querySelector(`input[name="package[${i}][name]"]`)?.value || '',
                        code: document.querySelector(`input[name="package[${i}][code]"]`)?.value || '',
                        price: document.querySelector(`input[name="package[${i}][price]"]`)?.value || '',
                        billing: document.querySelector(`select[name="package[${i}][billing]"]`)?.value || '',
                        description: document.querySelector(`textarea[name="package[${i}][description]"]`)?.value || '',
                        services: document.querySelector(`input[name="package[${i}][services]"]`)?.value || '',
                        limit: document.querySelector(`input[name="package[${i}][limit]"]`)?.value || '',
                        expiry: document.querySelector(`input[name="package[${i}][expiry]"]`)?.value || '',
                        sort: document.querySelector(`input[name="package[${i}][sort]"]`)?.value || '',
                        active: document.querySelector(`input[name="package[${i}][active]"]`)?.checked || false,
                        chargeable: document.querySelector(`input[name="package[${i}][chargeable]"]`)?.checked || false
                    };
                });
            }
        }

        function loadPanelData() {
            const currentId = steps[current].id;

            if (currentId === 'corp') {
                const corpName = document.querySelector('input[name="corp_name"]');
                const corpCard = document.querySelector('input[name="corp_card"]');
                if (corpName && formData.corp_name) corpName.value = formData.corp_name;
                if (corpCard && formData.corp_card) corpCard.value = formData.corp_card;
            }
        }

        function rebuildFieldsFromSavedData(type) {
            // Clear existing fields
            const list = document.getElementById(type + '-list');
            list.innerHTML = '';

            // Get saved data for this type
            const savedData = formData[type];

            if (type === 'location') {
                // Rebuild location fields
                const locationKeys = Object.keys(savedData);
                if (locationKeys.length === 0) {
                    // Add default empty location
                    counters[type] = 0;
                    addRecord(type, true);
                } else {
                    counters[type] = 0;
                    locationKeys.forEach(key => {
                        counters[type]++;
                        const i = counters[type];
                        const uid = type + '-' + i;
                        const div = document.createElement('div');
                        div.className = 'record-block';
                        div.id = uid;
                        div.innerHTML = `
                        <div class="record-header" onclick="toggleRecord('${uid}')">
                            <span class="record-label">
                                <span class="chevron open" id="chev-${uid}">▼</span>
                                ${type === 'location' ? 'Location' : type === 'user' ? 'User / Employee' : type === 'service' ? 'Service' : 'Package'} #${i}
                            </span>
                            ${i > 1 ? `<button type="button" class="remove-btn" onclick="event.stopPropagation();removeRecord('${uid}')">Remove</button>` : ''}
                        </div>
                        <div class="record-body open" id="body-${uid}">${getFieldsHtml(type, i)}</div>`;
                        list.appendChild(div);
                    });
                }
            } else if (type === 'user') {
                const userKeys = Object.keys(savedData);
                if (userKeys.length === 0) {
                    counters[type] = 0;
                    addRecord(type, true);
                } else {
                    counters[type] = 0;
                    userKeys.forEach(key => {
                        counters[type]++;
                        const i = counters[type];
                        const uid = type + '-' + i;
                        const div = document.createElement('div');
                        div.className = 'record-block';
                        div.id = uid;
                        div.innerHTML = `
                        <div class="record-header" onclick="toggleRecord('${uid}')">
                            <span class="record-label">
                                <span class="chevron open" id="chev-${uid}">▼</span>
                                ${type === 'location' ? 'Location' : type === 'user' ? 'User / Employee' : type === 'service' ? 'Service' : 'Package'} #${i}
                            </span>
                            ${i > 1 ? `<button type="button" class="remove-btn" onclick="event.stopPropagation();removeRecord('${uid}')">Remove</button>` : ''}
                        </div>
                        <div class="record-body open" id="body-${uid}">${getFieldsHtml(type, i)}</div>`;
                        list.appendChild(div);
                    });
                }
            } else if (type === 'service') {
                const serviceKeys = Object.keys(savedData);
                if (serviceKeys.length === 0) {
                    counters[type] = 0;
                    addRecord(type, true);
                } else {
                    counters[type] = 0;
                    serviceKeys.forEach(key => {
                        counters[type]++;
                        const i = counters[type];
                        const uid = type + '-' + i;
                        const div = document.createElement('div');
                        div.className = 'record-block';
                        div.id = uid;
                        div.innerHTML = `
                        <div class="record-header" onclick="toggleRecord('${uid}')">
                            <span class="record-label">
                                <span class="chevron open" id="chev-${uid}">▼</span>
                                ${type === 'location' ? 'Location' : type === 'user' ? 'User / Employee' : type === 'service' ? 'Service' : 'Package'} #${i}
                            </span>
                            ${i > 1 ? `<button type="button" class="remove-btn" onclick="event.stopPropagation();removeRecord('${uid}')">Remove</button>` : ''}
                        </div>
                        <div class="record-body open" id="body-${uid}">${getFieldsHtml(type, i)}</div>`;
                        list.appendChild(div);
                    });
                }
            } else if (type === 'package') {
                const packageKeys = Object.keys(savedData);
                if (packageKeys.length === 0) {
                    counters[type] = 0;
                    addRecord(type, true);
                } else {
                    counters[type] = 0;
                    packageKeys.forEach(key => {
                        counters[type]++;
                        const i = counters[type];
                        const uid = type + '-' + i;
                        const div = document.createElement('div');
                        div.className = 'record-block';
                        div.id = uid;
                        div.innerHTML = `
                        <div class="record-header" onclick="toggleRecord('${uid}')">
                            <span class="record-label">
                                <span class="chevron open" id="chev-${uid}">▼</span>
                                ${type === 'location' ? 'Location' : type === 'user' ? 'User / Employee' : type === 'service' ? 'Service' : 'Package'} #${i}
                            </span>
                            ${i > 1 ? `<button type="button" class="remove-btn" onclick="event.stopPropagation();removeRecord('${uid}')">Remove</button>` : ''}
                        </div>
                        <div class="record-body open" id="body-${uid}">${getFieldsHtml(type, i)}</div>`;
                        list.appendChild(div);
                    });
                }
            }
        }

        function getFieldsHtml(type, i) {
            if (type === 'location') {
                return `<div class="field-grid">
                <div class="field-group full"><label>Location Name / Business Name / DBA <span class="req">*</span></label><input type="text" name="location[${i}][name]" placeholder="e.g. Main Street Branch" value="${formData.location[i]?.name || ''}"></div>
                <div class="field-group"><label>City <span class="req">*</span></label><input type="text" name="location[${i}][city]" placeholder="City" value="${formData.location[i]?.city || ''}"></div>
                <div class="field-group"><label>State <span class="req">*</span></label><input type="text" name="location[${i}][state]" placeholder="State" value="${formData.location[i]?.state || ''}"></div>
                <div class="field-group"><label>ZIP Code <span class="req">*</span></label><input type="text" name="location[${i}][zip]" placeholder="ZIP" value="${formData.location[i]?.zip || ''}"></div>
                <div class="field-group"><label>Email <span class="req">*</span></label><input type="email" name="location[${i}][email]" placeholder="location@email.com" value="${formData.location[i]?.email || ''}"></div>
                <div class="field-group"><label>Time Zone <span class="req">*</span></label>
                    <select name="location[${i}][timezone]"><option value="">Select time zone</option><option ${formData.location[i]?.timezone === 'Eastern Time (ET)' ? 'selected' : ''}>Eastern Time (ET)</option><option ${formData.location[i]?.timezone === 'Central Time (CT)' ? 'selected' : ''}>Central Time (CT)</option><option ${formData.location[i]?.timezone === 'Mountain Time (MT)' ? 'selected' : ''}>Mountain Time (MT)</option><option ${formData.location[i]?.timezone === 'Pacific Time (PT)' ? 'selected' : ''}>Pacific Time (PT)</option></select>
                </div>
                <div class="field-group"><label>Operational Hours <span class="req">*</span></label><input type="text" name="location[${i}][hours]" placeholder="e.g. Mon–Fri 9am–6pm" value="${formData.location[i]?.hours || ''}"></div>
                <div class="field-group full"><label>Credit Card <span class="req">*</span></label><input type="text" name="location[${i}][card]" placeholder="Card on file for this location" value="${formData.location[i]?.card || ''}"></div>
            </div>`;
            } else if (type === 'user') {
                return `<div class="field-grid">
                <div class="field-group"><label>Name <span class="req">*</span></label><input type="text" name="user[${i}][name]" placeholder="Full name" value="${formData.user[i]?.name || ''}"></div>
                <div class="field-group"><label>Email <span class="req">*</span></label><input type="email" name="user[${i}][email]" placeholder="user@email.com" value="${formData.user[i]?.email || ''}"></div>
                <div class="field-group"><label>Login <span class="req">*</span></label><input type="text" name="user[${i}][login]" placeholder="Username or login ID" value="${formData.user[i]?.login || ''}"></div>
                <div class="field-group"><label>Role <span class="req">*</span></label>
                    <select name="user[${i}][role]"><option value="">Select role</option><option ${formData.user[i]?.role === 'Admin' ? 'selected' : ''}>Admin</option><option ${formData.user[i]?.role === 'Employee' ? 'selected' : ''}>Employee</option><option ${formData.user[i]?.role === 'Contractor' ? 'selected' : ''}>Contractor</option><option ${formData.user[i]?.role === 'Manager' ? 'selected' : ''}>Manager</option></select>
                </div>
                <div class="field-group"><label>Service Hours <span class="req">*</span></label><input type="text" name="user[${i}][hours]" placeholder="e.g. Mon–Fri 9am–5pm" value="${formData.user[i]?.hours || ''}"></div>
                <div class="field-group" style="justify-content:flex-end;padding-top:20px">
                    <div class="checkrow"><input type="checkbox" name="user[${i}][calendar]" id="cal-${i}" value="1" ${formData.user[i]?.calendar ? 'checked' : ''}><label for="cal-${i}">Appear in Calendar</label></div>
                </div>
            </div>`;
            } else if (type === 'service') {
                return `<div class="field-grid">
                <div class="field-group"><label>Service Name <span class="req">*</span></label><input type="text" name="service[${i}][name]" placeholder="e.g. Initial Consultation" value="${formData.service[i]?.name || ''}"></div>
                <div class="field-group"><label>Code <span class="req">*</span></label><input type="text" name="service[${i}][code]" placeholder="e.g. SVC-001" value="${formData.service[i]?.code || ''}"></div>
                <div class="field-group"><label>Price <span class="req">*</span></label><input type="text" name="service[${i}][price]" placeholder="$0.00" value="${formData.service[i]?.price || ''}"></div>
                <div class="field-group"><label>Service Class <span class="req">*</span></label><input type="text" name="service[${i}][class]" placeholder="Class" value="${formData.service[i]?.class || ''}"></div>
                <div class="field-group full"><label>Description <span class="req">*</span></label><textarea name="service[${i}][description]" placeholder="Brief description of this service">${formData.service[i]?.description || ''}</textarea></div>
                <div class="field-group"><label>Sort Number <span class="req">*</span></label><input type="number" name="service[${i}][sort]" placeholder="1" value="${formData.service[i]?.sort || ''}"></div>
                <div class="field-group" style="padding-top:4px">
                    <div style="display:flex;flex-direction:column;gap:8px">
                        <div class="checkrow"><input type="checkbox" name="service[${i}][chargeable]" id="chargeable-${i}" value="1" ${formData.service[i]?.chargeable ? 'checked' : ''}><label for="chargeable-${i}">Chargeable to client account</label></div>
                        <div class="checkrow"><input type="checkbox" name="service[${i}][group]" id="group-${i}" value="1" ${formData.service[i]?.group ? 'checked' : ''}><label for="group-${i}">Is this a group service?</label></div>
                        <div class="checkrow"><input type="checkbox" name="service[${i}][calendar]" id="calcount-${i}" value="1" ${formData.service[i]?.calendar ? 'checked' : ''}><label for="calcount-${i}">Show in calendar count</label></div>
                    </div>
                </div>
            </div>`;
            } else if (type === 'package') {
                return `<div class="field-grid">
                <div class="pkg-divider">Package Details</div>
                <div class="field-group"><label>Package Name <span class="req">*</span></label><input type="text" name="package[${i}][name]" placeholder="e.g. Starter Pack" value="${formData.package[i]?.name || ''}"></div>
                <div class="field-group"><label>Package Code <span class="req">*</span></label><input type="text" name="package[${i}][code]" placeholder="e.g. PKG-001" value="${formData.package[i]?.code || ''}"></div>
                <div class="field-group"><label>Price <span class="req">*</span></label><input type="text" name="package[${i}][price]" placeholder="$0.00" value="${formData.package[i]?.price || ''}"></div>
                <div class="field-group"><label>Billing Cycle <span class="req">*</span></label>
                    <select name="package[${i}][billing]"><option value="">Select</option><option ${formData.package[i]?.billing === 'One-time' ? 'selected' : ''}>One-time</option><option ${formData.package[i]?.billing === 'Weekly' ? 'selected' : ''}>Weekly</option><option ${formData.package[i]?.billing === 'Monthly' ? 'selected' : ''}>Monthly</option><option ${formData.package[i]?.billing === 'Annually' ? 'selected' : ''}>Annually</option></select>
                </div>
                <div class="field-group full"><label>Description <span class="req">*</span></label><textarea name="package[${i}][description]" placeholder="What's included in this package?">${formData.package[i]?.description || ''}</textarea></div>
                <div class="pkg-divider">Services Included</div>
                <div class="field-group full"><label>Services <span class="req">*</span></label><input type="text" name="package[${i}][services]" placeholder="e.g. SVC-001, SVC-002 (comma-separated codes)" value="${formData.package[i]?.services || ''}"></div>
                <div class="field-group"><label>Session / Visit Limit</label><input type="number" name="package[${i}][limit]" placeholder="Leave blank for unlimited" value="${formData.package[i]?.limit || ''}"></div>
                <div class="field-group"><label>Expiry (days)</label><input type="number" name="package[${i}][expiry]" placeholder="Leave blank if none" value="${formData.package[i]?.expiry || ''}"></div>
                <div class="pkg-divider">Options</div>
                <div class="field-group"><label>Sort Number <span class="req">*</span></label><input type="number" name="package[${i}][sort]" placeholder="1" value="${formData.package[i]?.sort || ''}"></div>
                <div class="field-group" style="padding-top:4px">
                    <div style="display:flex;flex-direction:column;gap:8px">
                        <div class="checkrow"><input type="checkbox" name="package[${i}][active]" id="pkg-active-${i}" value="1" ${formData.package[i]?.active ? 'checked' : ''}><label for="pkg-active-${i}">Active / available for purchase</label></div>
                        <div class="checkrow"><input type="checkbox" name="package[${i}][chargeable]" id="pkg-client-${i}" value="1" ${formData.package[i]?.chargeable ? 'checked' : ''}><label for="pkg-client-${i}">Chargeable to client account</label></div>
                    </div>
                </div>
            </div>`;
            }
        }

        function addRecord(type, openBody) {
            counters[type]++;
            const i = counters[type];
            const list = document.getElementById(type + '-list');
            const labels = {
                location: 'Location',
                user: 'User / Employee',
                service: 'Service',
                package: 'Package'
            };
            const uid = type + '-' + i;
            const div = document.createElement('div');
            div.className = 'record-block';
            div.id = uid;
            div.innerHTML = `
            <div class="record-header" onclick="toggleRecord('${uid}')">
                <span class="record-label">
                    <span class="chevron ${openBody ? 'open' : ''}" id="chev-${uid}">▼</span>
                    ${labels[type]} #${i}
                </span>
                ${i > 1 ? `<button type="button" class="remove-btn" onclick="event.stopPropagation();removeRecord('${uid}')">Remove</button>` : ''}
            </div>
            <div class="record-body ${openBody ? 'open' : ''}" id="body-${uid}">${getFieldsHtml(type, i)}</div>`;
            list.appendChild(div);
        }

        function removeRecord(uid) {
            const el = document.getElementById(uid);
            if (el) el.remove();
        }

        const panelHTML = {
            corp: `<div class="panel-title">Corporation / Entity</div>
            <div class="panel-sub">Basic information about your corporation or business entity.</div>
            <div class="field-grid">
                <div class="field-group full"><label>Corporation / Entity Name <span class="req">*</span></label><input type="text" name="corp_name" placeholder="e.g. Acme Corp — or owner's name if no formal entity" value="${formData.corp_name}"></div>
                <div class="field-group full"><label>Credit Card <span class="req">*</span></label><input type="text" name="corp_card" placeholder="Card on file" value="${formData.corp_card}"></div>
            </div>`,
            location: `<div class="panel-title">Locations</div>
            <div class="panel-sub">Add one or more business locations. Click a header to expand or collapse.</div>
            <div id="location-list"></div>
            <button type="button" class="add-btn" onclick="addRecord('location', true)">+ Add another location</button>`,
            users: `<div class="panel-title">Users / Employees / Contractors</div>
            <div class="panel-sub">Add all team members who will use the system.</div>
            <div id="user-list"></div>
            <button type="button" class="add-btn" onclick="addRecord('user', true)">+ Add another user</button>`,
            services: `<div class="panel-title">Services</div>
            <div class="panel-sub">Define the services your business offers. A default scheduling code is applied automatically.</div>
            <div id="service-list"></div>
            <button type="button" class="add-btn" onclick="addRecord('service', true)">+ Add another service</button>`,
            packages: `<div class="panel-title">Packages</div>
            <div class="panel-sub">Bundle services into packages for client purchase or subscription.</div>
            <div id="package-list"></div>
            <button type="button" class="add-btn" onclick="addRecord('package', true)">+ Add another package</button>`
        };

        function renderSidebar() {
            document.getElementById('sidebar').innerHTML = steps.map((s, idx) => {
                const dotCls = idx < current ? 'done' : idx === current ? 'active' : '';
                const labelCls = idx < current ? 'done' : idx === current ? 'active' : '';
                const sym = idx < current ? '✓' : idx + 1;
                const lineDone = idx < current ? 'done' : '';
                return `<div class="side-step" onclick="goTo(${idx})">
                ${idx < steps.length - 1 ? `<div class="side-line ${lineDone}"></div>` : ''}
                <div class="dot ${dotCls}">${sym}</div>
                <span class="side-label ${labelCls}">${s.label}</span>
            </div>`;
            }).join('');
        }

        function renderPanel() {
            saveCurrentPanelData();
            const id = steps[current].id;
            document.getElementById('panels').innerHTML = `<div class="panel">${panelHTML[id]}</div>`;

            if (id === 'location') {
                if (counters.location === 0) {
                    rebuildFieldsFromSavedData('location');
                }
            }
            if (id === 'users') {
                if (counters.user === 0) {
                    rebuildFieldsFromSavedData('user');
                }
            }
            if (id === 'services') {
                if (counters.service === 0) {
                    rebuildFieldsFromSavedData('service');
                }
            }
            if (id === 'packages') {
                if (counters.package === 0) {
                    rebuildFieldsFromSavedData('package');
                }
            }

            loadPanelData();
            document.getElementById('backBtn').style.visibility = current === 0 ? 'hidden' : 'visible';
            document.getElementById('nextBtn').textContent = current === steps.length - 1 ? 'Submit ✓' : 'Next →';
            document.getElementById('stepCounter').textContent = `Step ${current + 1} of ${steps.length}`;
        }

        function navigate(dir) {
            if (dir === 1 && current === steps.length - 1) {
                saveCurrentPanelData();
                if (confirm('Are you sure you want to submit all data?')) {
                    document.getElementById('wizardForm').submit();
                }
                return;
            }
            current = Math.max(0, Math.min(steps.length - 1, current + dir));
            renderSidebar();
            renderPanel();
        }

        function goTo(idx) {
            saveCurrentPanelData();
            current = idx;
            renderSidebar();
            renderPanel();
        }

        // Add form submission handler to ensure all data is captured
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('wizardForm');
            form.addEventListener('submit', function(e) {
                saveCurrentPanelData();
                // The form will submit with all fields properly named
            });
        });

        // Initialize
        renderSidebar();
        renderPanel();
    </script>
</body>

</html>