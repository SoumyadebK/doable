<?php
require_once('../global/config.php');

global $db;
global $db_account;

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

// Fetch timezones from database
$timezones = [];
$timezoneQuery = "SELECT PK_TIMEZONE, NAME FROM DOA_TIMEZONE WHERE ACTIVE = 1";
$timezoneResult = $db->Execute($timezoneQuery);
if ($timezoneResult && !$timezoneResult->EOF) {
    while (!$timezoneResult->EOF) {
        $timezones[] = array(
            'PK_TIMEZONE' => $timezoneResult->fields['PK_TIMEZONE'],
            'NAME' => $timezoneResult->fields['NAME']
        );
        $timezoneResult->MoveNext();
    }
}

// Convert to JSON for JavaScript
$timezonesJSON = json_encode($timezones);

// Fetch locations (for package location selection)
$locations = [];
$locationQuery = "SELECT PK_LOCATION, LOCATION_NAME FROM DOA_LOCATION WHERE ACTIVE = 1 AND PK_ACCOUNT_MASTER = " . $_SESSION['PK_ACCOUNT_MASTER'];
$locationResult = $db->Execute($locationQuery);
if ($locationResult && !$locationResult->EOF) {
    while (!$locationResult->EOF) {
        $locations[] = array(
            'PK_LOCATION' => $locationResult->fields['PK_LOCATION'],
            'LOCATION_NAME' => $locationResult->fields['LOCATION_NAME']
        );
        $locationResult->MoveNext();
    }
}

$locationsJSON = json_encode($locations);


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
                            'LOCATION_CODE' => trim($location['code'] ?? ''),
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
                        // Hash the password if provided
                        $hashedPassword = '';
                        if (!empty($user['password'])) {
                            $hashedPassword = password_hash($user['password'], PASSWORD_DEFAULT);
                        }

                        $USER_DATA = array(
                            'PK_ACCOUNT_MASTER' => $_SESSION['PK_ACCOUNT_MASTER'],
                            'FIRST_NAME' => trim($user['name'] ?? ''),
                            'LAST_NAME' => '', // Assuming last name is not collected in this form
                            'EMAIL_ID' => trim($user['email'] ?? ''),
                            'USER_NAME' => trim($user['login'] ?? ''),
                            'PASSWORD' => $hashedPassword, // Store encrypted password
                            'CREATE_LOGIN' => 1,
                            'APPEAR_IN_CALENDAR' => isset($user['calendar']) ? 1 : 0,
                            'ACTIVE' => 1,
                            'CREATED_BY' => $_SESSION['PK_USER'],
                            'CREATED_ON' => date("Y-m-d H:i:s")
                        );
                        //pre_r($USER_DATA);
                        db_perform('DOA_USERS', $USER_DATA, 'insert');
                        $PK_USER = $db->insert_ID();

                        if ($PK_USER) {
                            $USER_LOCATION_DATA = array(
                                'PK_USER' => $PK_USER,
                                'PK_LOCATION' => $PK_LOCATION
                            );
                            db_perform('DOA_USER_LOCATION', $USER_LOCATION_DATA, 'insert');

                            // Assign role to user
                            if (!empty($user['role']) && $PK_USER) {
                                $ROLE_DATA = array(
                                    'PK_USER' => $PK_USER,
                                    'PK_ROLES' => trim($user['role'] ?? '')
                                );
                                db_perform('DOA_USER_ROLES', $ROLE_DATA, 'insert');
                            }
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
                // First, create an array to store service master IDs for quick lookup
                $serviceMasterIds = [];

                // Query all service masters for this location to map names to IDs
                if (isset($PK_LOCATION)) {
                    $serviceQuery = "SELECT PK_SERVICE_MASTER, SERVICE_NAME FROM DOA_SERVICE_MASTER WHERE PK_LOCATION = " . $PK_LOCATION . " AND ACTIVE = 1";
                    $serviceResult = $db_account->Execute($serviceQuery);
                    if ($serviceResult && !$serviceResult->EOF) {
                        while (!$serviceResult->EOF) {
                            $serviceMasterIds[trim($serviceResult->fields['SERVICE_NAME'])] = $serviceResult->fields['PK_SERVICE_MASTER'];
                            $serviceResult->MoveNext();
                        }
                    }

                    // Also get service code IDs
                    $serviceCodeIds = [];
                    $codeQuery = "SELECT sc.PK_SERVICE_CODE, sm.SERVICE_NAME 
                      FROM DOA_SERVICE_CODE sc 
                      JOIN DOA_SERVICE_MASTER sm ON sc.PK_SERVICE_MASTER = sm.PK_SERVICE_MASTER 
                      WHERE sc.PK_LOCATION = " . $PK_LOCATION . " AND sc.ACTIVE = 1";
                    $codeResult = $db_account->Execute($codeQuery);
                    if ($codeResult && !$codeResult->EOF) {
                        while (!$codeResult->EOF) {
                            $serviceCodeIds[trim($codeResult->fields['SERVICE_NAME'])] = $codeResult->fields['PK_SERVICE_CODE'];
                            $codeResult->MoveNext();
                        }
                    }
                }

                foreach ($_POST['package'] as $packageIndex => $package) {
                    if (!empty($package['name'])) {
                        $PACKAGE_DATA = array(
                            'PK_LOCATION' => $PK_LOCATION,
                            'PACKAGE_NAME' => trim($package['name'] ?? ''),
                            'EXPIRY_DATE' => !empty($package['expiry']) ? intval($package['expiry']) : null,
                            'SORT_ORDER' => intval($package['sort'] ?? 0),
                            'ACTIVE' => 1,
                            'CREATED_BY' => $_SESSION['PK_USER'],
                            'CREATED_ON' => date("Y-m-d H:i:s")
                        );

                        db_perform_account('DOA_PACKAGE', $PACKAGE_DATA, 'insert');
                        $PK_PACKAGE = $db_account->insert_ID();

                        if ($PK_PACKAGE) {
                            // Link package to location
                            if (isset($package['location']) && !empty($package['location'])) {
                                $PACKAGE_LOCATION_DATA = array(
                                    'PK_PACKAGE' => $PK_PACKAGE,
                                    'PK_LOCATION' => $package['location']
                                );
                                db_perform_account('DOA_PACKAGE_LOCATION', $PACKAGE_LOCATION_DATA, 'insert');
                            }

                            // Insert multiple services for the package
                            if (isset($package['service_item']) && is_array($package['service_item'])) {
                                foreach ($package['service_item'] as $serviceCounter => $serviceItem) {
                                    // Get the service name from the selected option
                                    $serviceName = trim($serviceItem['service_name'] ?? '');

                                    if (!empty($serviceName)) {
                                        // Look up the service master ID and service code ID
                                        $PK_SERVICE_MASTER = isset($serviceMasterIds[$serviceName]) ? $serviceMasterIds[$serviceName] : 0;
                                        $PK_SERVICE_CODE = isset($serviceCodeIds[$serviceName]) ? $serviceCodeIds[$serviceName] : 0;

                                        if ($PK_SERVICE_MASTER > 0 && $PK_SERVICE_CODE > 0) {
                                            $PACKAGE_SERVICE_DATA = array(
                                                'PK_PACKAGE' => $PK_PACKAGE,
                                                'PK_SERVICE_MASTER' => $PK_SERVICE_MASTER,
                                                'PK_SERVICE_CODE' => $PK_SERVICE_CODE,
                                                'SERVICE_DETAILS' => trim($serviceItem['details'] ?? ''),
                                                'NUMBER_OF_SESSION' => !empty($serviceItem['sessions']) ? intval($serviceItem['sessions']) : null,
                                                'PRICE_PER_SESSION' => floatval($serviceItem['price_per_session'] ?? 0),
                                                'TOTAL' => floatval($serviceItem['total_price'] ?? 0),
                                                'DISCOUNT_TYPE' => trim($serviceItem['discount_type'] ?? ''),
                                                'DISCOUNT' => floatval($serviceItem['discount'] ?? 0),
                                                'FINAL_AMOUNT' => floatval($serviceItem['final_amount'] ?? 0),
                                                'ACTIVE' => 1
                                            );

                                            // Debug log
                                            error_log("Package Service Data: " . print_r($PACKAGE_SERVICE_DATA, true));
                                            //pre_r($PACKAGE_SERVICE_DATA);
                                            db_perform_account('DOA_PACKAGE_SERVICE', $PACKAGE_SERVICE_DATA, 'insert');

                                            if ($db_account->insert_ID()) {
                                                error_log("Package service inserted successfully for: " . $serviceName);
                                            } else {
                                                error_log("Failed to insert package service for: " . $serviceName);
                                            }
                                        } else {
                                            error_log("Could not find service IDs for: " . $serviceName);
                                        }
                                    }
                                }
                            }
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
        input[type=password],
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

        .service-item {
            background: #f9fafb;
            border: 1px solid #e0e4ea;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }

        .service-item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 1px solid #e0e4ea;
        }

        .service-item-title {
            font-weight: 600;
            font-size: 14px;
            color: #39B54A;
        }

        .remove-service-btn {
            font-size: 11px;
            color: #a32d2d;
            background: none;
            border: 1px solid #f09595;
            border-radius: 6px;
            padding: 2px 8px;
            cursor: pointer;
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

        /* Password strength indicator styles */
        .password-field {
            position: relative;
        }

        .password-wrapper {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
            font-size: 14px;
            background: none;
            border: none;
            padding: 0;
        }

        .strength-meter {
            margin-top: 5px;
            height: 4px;
            background-color: #e0e0e0;
            border-radius: 2px;
            overflow: hidden;
        }

        .strength-bar {
            height: 100%;
            width: 0%;
            transition: width 0.3s ease, background-color 0.3s ease;
            border-radius: 2px;
        }

        .strength-text {
            font-size: 11px;
            margin-top: 4px;
            color: #666;
        }

        .strength-text.weak {
            color: #e24b4a;
        }

        .strength-text.fair {
            color: #f39c12;
        }

        .strength-text.good {
            color: #3498db;
        }

        .strength-text.strong {
            color: #39B54A;
        }

        .password-requirements {
            font-size: 11px;
            color: #666;
            margin-top: 5px;
            padding-left: 0;
            list-style: none;
        }

        .password-requirements li {
            margin-bottom: 3px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .password-requirements li.valid {
            color: #39B54A;
        }

        .password-requirements li.invalid {
            color: #e24b4a;
        }

        .password-requirements li i {
            font-style: normal;
            font-weight: bold;
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
        // Pass PHP data to JavaScript
        const timezonesData = <?php echo $timezonesJSON; ?>;
        const locationsData = <?php echo $locationsJSON; ?>;

        // Log for debugging
        console.log('Timezones:', timezonesData);
        console.log('Locations:', locationsData);

        // Function to generate timezone options
        function getTimezoneOptions(selectedValue = '') {
            let options = '<option value="">Select time zone</option>';

            if (timezonesData && timezonesData.length > 0) {
                timezonesData.forEach(timezone => {
                    const selected = (selectedValue == timezone.PK_TIMEZONE) ? 'selected' : '';
                    const displayText = timezone.NAME;
                    options += `<option value="${timezone.PK_TIMEZONE}" ${selected}>${(displayText)}</option>`;
                });
            } else {
                // Fallback timezones if no data found
                options += `
                <option value="1">Eastern Time (ET)</option>
                <option value="2">Central Time (CT)</option>
                <option value="3">Mountain Time (MT)</option>
                <option value="4">Pacific Time (PT)</option>
            `;
            }

            return options;
        }

        // Function to save services data from services step
        function saveServicesData() {
            servicesData = [];
            const serviceBlocks = document.querySelectorAll('#service-list .record-block');
            serviceBlocks.forEach((block, index) => {
                const nameInput = block.querySelector('input[name*="[name]"]');
                const codeInput = block.querySelector('input[name*="[code]"]');
                const priceInput = block.querySelector('input[name*="[price]"]');
                const descriptionInput = block.querySelector('textarea[name*="[description]"]');

                if (nameInput && nameInput.value) {
                    servicesData.push({
                        id: index + 1,
                        name: nameInput.value,
                        code: codeInput ? codeInput.value : '',
                        price: priceInput ? priceInput.value : 0,
                        description: descriptionInput ? descriptionInput.value : ''
                    });
                }
            });
            console.log('Services saved:', servicesData);
        }

        // Function to get service options for package dropdown
        function getServiceOptions(selectedValue = '') {
            let options = '<option value="">Select service</option>';

            if (servicesData && servicesData.length > 0) {
                servicesData.forEach(service => {
                    const selected = (selectedValue == service.name) ? 'selected' : '';
                    options += `<option value="${escapeHtml(service.name)}" data-code="${escapeHtml(service.code)}" data-price="${service.price}" data-description="${escapeHtml(service.description)}" ${selected}>${escapeHtml(service.name)} (${escapeHtml(service.code)})</option>`;
                });
            } else {
                options += '<option value="" disabled>No services available. Please add services first.</option>';
            }

            return options;
        }

        // Function to get location options for packages
        function getLocationOptions(selectedValue = '') {
            let options = '<option value="">Select location</option>';

            if (locationsData && locationsData.length > 0) {
                locationsData.forEach(location => {
                    const selected = (selectedValue == location.PK_LOCATION) ? 'selected' : '';
                    options += `<option value="${location.PK_LOCATION}" ${selected}>${escapeHtml(location.LOCATION_NAME)}</option>`;
                });
            } else {
                options += '<option value="" disabled>No locations available. Please add locations first.</option>';
            }

            return options;
        }

        // Function to calculate totals for a service item
        function calculateServiceTotals(packageId, counter) {
            const sessionsInput = document.querySelector(`input[name="package[${packageId}][service_item][${counter}][sessions]"]`);
            const pricePerSessionInput = document.querySelector(`input[name="package[${packageId}][service_item][${counter}][price_per_session]"]`);
            const totalPriceInput = document.querySelector(`input[name="package[${packageId}][service_item][${counter}][total_price]"]`);
            const discountTypeSelect = document.querySelector(`select[name="package[${packageId}][service_item][${counter}][discount_type]"]`);
            const discountInput = document.querySelector(`input[name="package[${packageId}][service_item][${counter}][discount]"]`);
            const finalAmountInput = document.querySelector(`input[name="package[${packageId}][service_item][${counter}][final_amount]"]`);

            if (sessionsInput && pricePerSessionInput && totalPriceInput) {
                const sessions = parseFloat(sessionsInput.value) || 0;
                const pricePerSession = parseFloat(pricePerSessionInput.value) || 0;
                const totalPrice = sessions * pricePerSession;
                totalPriceInput.value = totalPrice.toFixed(2);

                // Calculate final amount based on discount
                if (finalAmountInput && discountInput) {
                    const discountType = discountTypeSelect ? discountTypeSelect.value : '';
                    const discount = parseFloat(discountInput.value) || 0;
                    let finalAmount = totalPrice;

                    if (discountType === '1') {
                        finalAmount = totalPrice - discount;
                        if (finalAmount < 0) finalAmount = 0;
                    } else if (discountType === '2') {
                        finalAmount = totalPrice - (totalPrice * discount / 100);
                        if (finalAmount < 0) finalAmount = 0;
                    }

                    finalAmountInput.value = finalAmount.toFixed(2);
                }
            }
        }

        // Function to handle service selection change
        function onServiceChange(packageId, serviceCounter) {
            const serviceSelect = document.querySelector(`select[name="package[${packageId}][service_item][${serviceCounter}][service_name]"]`);
            if (serviceSelect && serviceSelect.value) {
                const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
                const serviceCode = selectedOption.getAttribute('data-code') || '';
                const servicePrice = selectedOption.getAttribute('data-price') || 0;
                const serviceDescription = selectedOption.getAttribute('data-description') || '';

                // Auto-fill service code
                const codeInput = document.querySelector(`input[name="package[${packageId}][service_item][${serviceCounter}][code]"]`);
                if (codeInput) {
                    codeInput.value = serviceCode;
                }

                // Auto-fill service details
                const detailsInput = document.querySelector(`input[name="package[${packageId}][service_item][${serviceCounter}][details]"]`);
                if (detailsInput) {
                    detailsInput.value = serviceDescription;
                }

                // Optionally auto-fill price per session
                const pricePerSessionInput = document.querySelector(`input[name="package[${packageId}][service_item][${serviceCounter}][price_per_session]"]`);
                if (pricePerSessionInput && !pricePerSessionInput.value) {
                    pricePerSessionInput.value = servicePrice;
                }

                // Recalculate totals
                calculateServiceTotals(packageId, serviceCounter);
            }
        }

        // Helper function to escape HTML
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

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

            // If adding a user, we don't need to do anything extra as the password field is already in the HTML
            // If adding a service, save services data
            if (type === 'service') {
                saveServicesData();
                refreshAllServiceOptions();
            }
        }

        function addServiceItem(packageId) {
            const container = document.getElementById(`services-container-${packageId}`);
            if (!container) return;

            // Get current number of service items
            const serviceCounter = container.querySelectorAll('.service-item').length + 1;

            // Create new service item (not first, so remove button will be shown)
            const serviceHtml = getServiceItemFields(packageId, serviceCounter, false);

            const div = document.createElement('div');
            div.className = 'service-item';
            div.id = `service-item-${packageId}-${serviceCounter}`;
            div.innerHTML = serviceHtml;
            container.appendChild(div);
        }

        function removeServiceItem(counter, packageId) {
            const element = document.getElementById(`service-item-${packageId}-${counter}`);
            if (element) {
                element.remove();

                // Renumber remaining service items
                const container = document.getElementById(`services-container-${packageId}`);
                if (container) {
                    const services = container.querySelectorAll('.service-item');
                    services.forEach((service, index) => {
                        const newNumber = index + 1;
                        const isFirst = newNumber === 1;

                        // Update the service item ID
                        service.id = `service-item-${packageId}-${newNumber}`;

                        // Update the title text
                        const titleSpan = service.querySelector('.service-item-title');
                        if (titleSpan) {
                            titleSpan.textContent = `Service #${newNumber}`;
                        }

                        // Update all input names to reflect new numbering
                        const inputs = service.querySelectorAll('input, select, textarea');
                        inputs.forEach(input => {
                            const name = input.getAttribute('name');
                            if (name) {
                                const updatedName = name.replace(/service_item\]\[\d+\]/, `service_item][${newNumber}]`);
                                input.setAttribute('name', updatedName);

                                // Update onchange/onkeyup attributes
                                if (input.hasAttribute('onchange')) {
                                    const onchange = input.getAttribute('onchange');
                                    const updatedOnchange = onchange.replace(/,\s*\d+\)/g, `, ${newNumber})`);
                                    input.setAttribute('onchange', updatedOnchange);
                                }
                                if (input.hasAttribute('onkeyup')) {
                                    const onkeyup = input.getAttribute('onkeyup');
                                    const updatedOnkeyup = onkeyup.replace(/,\s*\d+\)/g, `, ${newNumber})`);
                                    input.setAttribute('onkeyup', updatedOnkeyup);
                                }
                            }
                        });

                        // Handle remove button visibility
                        const removeBtn = service.querySelector('.remove-service-btn');
                        if (isFirst) {
                            // First item should not have remove button
                            if (removeBtn) {
                                removeBtn.remove();
                            }
                        } else {
                            // Non-first items should have remove button
                            if (!removeBtn) {
                                const headerDiv = service.querySelector('.service-item-header');
                                if (headerDiv) {
                                    const newRemoveBtn = document.createElement('button');
                                    newRemoveBtn.type = 'button';
                                    newRemoveBtn.className = 'remove-service-btn';
                                    newRemoveBtn.setAttribute('onclick', `removeServiceItem(${newNumber}, '${packageId}')`);
                                    newRemoveBtn.textContent = 'Remove';
                                    headerDiv.appendChild(newRemoveBtn);
                                }
                            } else {
                                // Update existing remove button onclick event
                                removeBtn.setAttribute('onclick', `removeServiceItem(${newNumber}, '${packageId}')`);
                            }
                        }

                        // Reattach event listeners for the renumbered service item
                        attachServiceItemEventListeners(packageId, newNumber);

                        // Recalculate totals for the renumbered service item
                        calculateServiceTotals(packageId, newNumber);
                    });
                }
            }
        }

        function getServiceItemFields(packageId, counter, isFirst = false) {
            return `
                    <div class="service-item-header">
                        <span class="service-item-title">Service #${counter}</span>
                        ${!isFirst ? `<button type="button" class="remove-service-btn" onclick="removeServiceItem(${counter}, '${packageId}')">Remove</button>` : ''}
                    </div>
                    <div class="field-grid">
                        <div class="field-group"><label>Service Name <span class="req">*</span></label>
                            <select name="package[${packageId}][service_item][${counter}][service_name]" 
                                    onchange="handleServiceSelection('${packageId}', ${counter})"
                                    class="service-select">
                                ${getServiceOptions()}
                            </select>
                        </div>
                        <div class="field-group"><label>Service Code <span class="req">*</span></label>
                            <input type="text" name="package[${packageId}][service_item][${counter}][code]" 
                                class="service-code" readonly style="background-color: #f5f5f5;">
                        </div>
                        <div class="field-group"><label>Service Details</label>
                            <textarea name="package[${packageId}][service_item][${counter}][details]" 
                                    class="service-details" rows="2" style="resize: vertical;"></textarea>
                        </div>
                        <div class="field-group"><label>Number of Sessions</label>
                            <input type="number" name="package[${packageId}][service_item][${counter}][sessions]" 
                                class="sessions-input" placeholder="Leave blank for unlimited" 
                                onchange="calculateServiceTotals('${packageId}', ${counter})" 
                                onkeyup="calculateServiceTotals('${packageId}', ${counter})">
                        </div>
                        <div class="field-group"><label>Price per Session</label>
                            <input type="number" step="0.01" name="package[${packageId}][service_item][${counter}][price_per_session]" 
                                class="price-per-session" placeholder="$0.00" 
                                onchange="calculateServiceTotals('${packageId}', ${counter})" 
                                onkeyup="calculateServiceTotals('${packageId}', ${counter})">
                        </div>
                        <div class="field-group"><label>Total Price</label>
                            <input type="number" step="0.01" name="package[${packageId}][service_item][${counter}][total_price]" 
                                class="total-price" placeholder="$0.00" readonly style="background-color: #f5f5f5;">
                        </div>
                        <div class="field-group"><label>Discount Type</label>
                            <select name="package[${packageId}][service_item][${counter}][discount_type]" 
                                    class="discount-type" onchange="calculateServiceTotals('${packageId}', ${counter})">
                                <option value="">Select</option>
                                <option value="1">Fixed</option>
                                <option value="2">Percent</option>
                            </select>
                        </div>
                        <div class="field-group"><label>Discount</label>
                            <input type="number" step="0.01" name="package[${packageId}][service_item][${counter}][discount]" 
                                class="discount-input" placeholder="0.00" 
                                onchange="calculateServiceTotals('${packageId}', ${counter})" 
                                onkeyup="calculateServiceTotals('${packageId}', ${counter})">
                        </div>
                        <div class="field-group"><label>Final Amount</label>
                            <input type="number" step="0.01" name="package[${packageId}][service_item][${counter}][final_amount]" 
                                class="final-amount" placeholder="0.00" readonly style="background-color: #f5f5f5;">
                        </div>
                    </div>
                `;
        }


        function getLocationFields(i) {
            return `<div class="field-grid">
        <div class="field-group"><label>Location Name <span class="req">*</span></label>
            <input type="text" name="location[${i}][name]" placeholder="e.g. Main Street Branch">
        </div>
        <div class="field-group">
            <label>Location Code <span class="req">*</span></label>
            <input type="text" 
                   name="location[${i}][code]" 
                   class="location-code-input"
                   data-location-index="${i}"
                   placeholder="e.g. MSB1" 
                   maxlength="4" 
                   onkeypress="return onlyAlphanumeric(event)"
                   onpaste="return false"
                   ondrop="return false"
                   onkeyup="convertToUpperCase(this); checkDuplicateLocationCode(this, ${i})"
                   onblur="convertToUpperCase(this); checkDuplicateLocationCode(this, ${i})"
                   style="text-transform: uppercase;">
            <small style="color: #666; font-size: 11px; display: block; margin-top: 4px;">Only letters and numbers (4 characters max) - Automatically converts to uppercase</small>
            <div id="duplicate-result-${i}" class="duplicate-check-result" style="font-size: 11px; margin-top: 4px;"></div>
        </div>
        <div class="field-group"><label>City <span class="req">*</span></label><input type="text" name="location[${i}][city]" placeholder="City"></div>
        <div class="field-group"><label>State <span class="req">*</span></label><input type="text" name="location[${i}][state]" placeholder="State"></div>
        <div class="field-group"><label>ZIP Code <span class="req">*</span></label><input type="text" name="location[${i}][zip]" placeholder="ZIP"></div>
        <div class="field-group"><label>Email <span class="req">*</span></label><input type="email" name="location[${i}][email]" placeholder="location@email.com"></div>
        <div class="field-group"><label>Time Zone <span class="req">*</span></label>
            <select name="location[${i}][timezone]">${getTimezoneOptions()}</select>
        </div>
    </div>`;
        }

        function getUserFields(i) {
            return `<div class="field-grid">
                        <div class="field-group"><label>Name <span class="req">*</span></label><input type="text" name="user[${i}][name]" placeholder="Full name" onchange="validateUserFields(${i})"></div>
                        <div class="field-group"><label>Email <span class="req">*</span></label><input type="email" name="user[${i}][email]" placeholder="user@email.com" onchange="validateUserFields(${i})"></div>
                        <div class="field-group"><label>Login <span class="req">*</span></label><input type="text" name="user[${i}][login]" placeholder="Username or login ID" onchange="validateUserFields(${i})"></div>
                        <div class="field-group password-field">
                            <label>Password <span class="req">*</span></label>
                            <div class="password-wrapper">
                                <input type="password" name="user[${i}][password]" id="password-${i}" placeholder="Enter password" onkeyup="checkPasswordStrength(${i})">
                                <button type="button" class="toggle-password" onclick="togglePasswordVisibility(${i})"><i class="fas fa-eye"></i></button>
                            </div>
                            <div class="strength-meter">
                                <div class="strength-bar" id="strength-bar-${i}"></div>
                            </div>
                            <div class="strength-text" id="strength-text-${i}"></div>
                            
                        </div>
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
                            <select name="service[${i}][class]"><option value="">Select class</option><option value="1">Membership</option><option value="2">Sessions (Charged per session)</option><option value="5">Miscellaneous</option></select>
                        </div>
                        <div class="field-group full"><label>Description <span class="req">*</span></label><textarea name="service[${i}][description]" placeholder="Brief description of this service"></textarea></div>
                        <div class="field-group"><label>Sort Order <span class="req">*</span></label><input type="number" name="service[${i}][sort]" placeholder="1"></div>
                        <div class="field-group" style="padding-top:4px">
                            <div style="display:flex;flex-direction:column;gap:8px">
                                <div class="checkrow"><input type="checkbox" name="service[${i}][chargeable]" id="chargeable-${i}" value="1"><label for="chargeable-${i}">Chargeable to client account</label></div>
                                <div class="checkrow"><input type="checkbox" name="service[${i}][group]" id="group-${i}" value="1"><label for="group-${i}">Is this a group service?</label></div>
                                <div class="checkrow"><input type="checkbox" name="service[${i}][calendar]" id="calcount-${i}" value="1"><label for="calcount-${i}">Show in calendar count</label></div>
                            </div>
                        </div>
                    </div>`;
        }

        function getPackageFields(packageId) {
            return `
                    <div class="field-grid">
                        <div class="pkg-divider">Package Details</div>
                        <div class="field-group"><label>Package Name <span class="req">*</span></label><input type="text" name="package[${packageId}][name]" placeholder="e.g. Starter Pack"></div>
                        <div class="field-group"><label>Location <span class="req">*</span></label>
                            <select name="package[${packageId}][location]">${getLocationOptions()}</select>
                        </div>
                        <div class="field-group"><label>Sort Order <span class="req">*</span></label><input type="number" name="package[${packageId}][sort]" placeholder="1"></div>
                        <div class="field-group"><label>Expiry (days)</label><input type="number" name="package[${packageId}][expiry]" placeholder="Leave blank if none"></div>
                    </div>
                    
                    <div class="pkg-divider" style="margin-top: 20px;">Services Included</div>
                    <div id="services-container-${packageId}">
                        ${getServiceItemFields(packageId, 1, true)}
                    </div>
                    <button type="button" class="add-btn" onclick="addServiceItem('${packageId}')" style="margin-top: 10px; width: 100%;">+ Add another service</button>
                `;
        }

        function addServiceItem(packageId) {
            const container = document.getElementById(`services-container-${packageId}`);
            if (!container) return;

            // Get current number of service items
            const serviceCounter = container.querySelectorAll('.service-item').length + 1;

            // Create new service item (not first, so remove button will be shown)
            const serviceHtml = getServiceItemFields(packageId, serviceCounter, false);

            const div = document.createElement('div');
            div.className = 'service-item';
            div.id = `service-item-${packageId}-${serviceCounter}`;
            div.innerHTML = serviceHtml;
            container.appendChild(div);

            // Refresh service options for all service items to ensure consistent data
            refreshAllServiceOptions();

            // Attach event listeners to the newly created service item
            attachServiceItemEventListeners(packageId, serviceCounter);
        }

        function attachServiceItemEventListeners(packageId, counter) {
            // Get all input elements for this service item
            const sessionsInput = document.querySelector(`input[name="package[${packageId}][service_item][${counter}][sessions]"]`);
            const pricePerSessionInput = document.querySelector(`input[name="package[${packageId}][service_item][${counter}][price_per_session]"]`);
            const discountInput = document.querySelector(`input[name="package[${packageId}][service_item][${counter}][discount]"]`);
            const discountTypeSelect = document.querySelector(`select[name="package[${packageId}][service_item][${counter}][discount_type]"]`);

            // Add event listeners if they exist
            if (sessionsInput) {
                sessionsInput.addEventListener('input', () => calculateServiceTotals(packageId, counter));
                sessionsInput.addEventListener('change', () => calculateServiceTotals(packageId, counter));
            }

            if (pricePerSessionInput) {
                pricePerSessionInput.addEventListener('input', () => calculateServiceTotals(packageId, counter));
                pricePerSessionInput.addEventListener('change', () => calculateServiceTotals(packageId, counter));
            }

            if (discountInput) {
                discountInput.addEventListener('input', () => calculateServiceTotals(packageId, counter));
                discountInput.addEventListener('change', () => calculateServiceTotals(packageId, counter));
            }

            if (discountTypeSelect) {
                discountTypeSelect.addEventListener('change', () => calculateServiceTotals(packageId, counter));
            }
        }

        function handleServiceSelection(packageId, counter) {
            const serviceSelect = document.querySelector(`select[name="package[${packageId}][service_item][${counter}][service_name]"]`);
            if (serviceSelect && serviceSelect.value) {
                const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
                const serviceCode = selectedOption.getAttribute('data-code') || '';
                const servicePrice = selectedOption.getAttribute('data-price') || 0;
                const serviceDescription = selectedOption.getAttribute('data-description') || '';

                // Auto-fill service code
                const codeInput = document.querySelector(`input[name="package[${packageId}][service_item][${counter}][code]"]`);
                if (codeInput) {
                    codeInput.value = serviceCode;
                }

                // Auto-fill service details
                const detailsInput = document.querySelector(`textarea[name="package[${packageId}][service_item][${counter}][details]"]`);
                if (detailsInput && !detailsInput.value) {
                    detailsInput.value = serviceDescription;
                }

                // Auto-fill price per session if empty
                const pricePerSessionInput = document.querySelector(`input[name="package[${packageId}][service_item][${counter}][price_per_session]"]`);
                if (pricePerSessionInput && (!pricePerSessionInput.value || pricePerSessionInput.value === '0')) {
                    pricePerSessionInput.value = servicePrice;
                }

                // Recalculate totals
                calculateServiceTotals(packageId, counter);
            }
        }

        function refreshAllServiceOptions() {
            const serviceSelects = document.querySelectorAll('select[name*="[service_name]"]');
            const serviceOptions = getServiceOptions();
            serviceSelects.forEach(select => {
                const currentValue = select.value;
                select.innerHTML = serviceOptions;
                if (currentValue) {
                    select.value = currentValue;
                    // Trigger the change event to refill data
                    const packageId = select.getAttribute('name').match(/package\[(\d+)\]/)[1];
                    const counter = select.getAttribute('name').match(/service_item\]\[(\d+)\]/)[1];
                    if (packageId && counter) {
                        handleServiceSelection(packageId, counter);
                    }
                }
            });
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
            if (el) {
                const isServiceRecord = uid.startsWith('service-');
                el.remove();

                // If removing a service, save services data
                if (isServiceRecord) {
                    saveServicesData();
                    refreshAllServiceOptions();
                }
            }
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
                // Validate all location codes before submission
                if (!validateAllLocationCodes()) {
                    return;
                }

                Swal.fire({
                    title: 'Confirm Submission',
                    text: 'Are you sure you want to submit all data?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#39B54A',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, submit it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('wizardForm').submit();
                    }
                });
                return;
            }

            // When moving from services to packages, save services data
            if (direction === 1 && steps[currentStep] === 'services' && steps[currentStep + 1] === 'packages') {
                saveServicesData();
                refreshAllServiceOptions();
            }

            const newStep = currentStep + direction;
            if (newStep >= 0 && newStep < steps.length) {
                showStep(newStep);
            }
        }

        // Update showStep to refresh package dropdowns when showing packages step
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

                // If showing packages step, refresh service and location dropdowns
                if (steps[step] === 'packages') {
                    refreshAllServiceOptions();
                    // Also refresh location dropdowns in packages
                    const locationSelects = document.querySelectorAll('select[name*="[location]"]');
                    const locationOptions = getLocationOptions();
                    locationSelects.forEach(select => {
                        const currentValue = select.value;
                        select.innerHTML = locationOptions;
                        if (currentValue) {
                            select.value = currentValue;
                        }
                    });
                }
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

        // Also save services when navigating away from services step via sidebar
        function goToStep(step) {
            if (currentStep === 2 && step === 4) { // Moving from services (index 2) to packages (index 4)
                saveServicesData();
            }
            showStep(step);
        }

        // Password strength checker function
        function checkPasswordStrength(userIndex) {
            const password = document.getElementById(`password-${userIndex}`).value;
            const strengthBar = document.getElementById(`strength-bar-${userIndex}`);
            const strengthText = document.getElementById(`strength-text-${userIndex}`);

            // Check requirements
            const requirements = {
                length: password.length >= 8,
                uppercase: /[A-Z]/.test(password),
                lowercase: /[a-z]/.test(password),
                number: /[0-9]/.test(password),
                special: /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)
            };

            // Update requirement indicators
            updateRequirementIndicator(userIndex, 'length', requirements.length);
            updateRequirementIndicator(userIndex, 'upper', requirements.uppercase);
            updateRequirementIndicator(userIndex, 'lower', requirements.lowercase);
            updateRequirementIndicator(userIndex, 'number', requirements.number);
            updateRequirementIndicator(userIndex, 'special', requirements.special);

            // Calculate strength score (0-100)
            let score = 0;
            if (requirements.length) score += 20;
            if (requirements.uppercase) score += 20;
            if (requirements.lowercase) score += 20;
            if (requirements.number) score += 20;
            if (requirements.special) score += 20;

            // Determine strength level
            let strength = '';
            let color = '';
            let text = '';

            if (score === 0) {
                strength = '';
                color = '#e0e0e0';
                text = '';
            } else if (score <= 20) {
                strength = 'weak';
                color = '#e24b4a';
                text = 'Very Weak';
            } else if (score <= 40) {
                strength = 'weak';
                color = '#e24b4a';
                text = 'Weak';
            } else if (score <= 60) {
                strength = 'fair';
                color = '#f39c12';
                text = 'Fair';
            } else if (score <= 80) {
                strength = 'good';
                color = '#3498db';
                text = 'Good';
            } else {
                strength = 'strong';
                color = '#39B54A';
                text = 'Strong';
            }

            // Update strength meter
            strengthBar.style.width = score + '%';
            strengthBar.style.backgroundColor = color;

            // Update strength text
            strengthText.textContent = text;
            strengthText.className = `strength-text ${strength}`;

            return score === 100; // Return true if password meets all requirements
        }

        // Update individual requirement indicator
        function updateRequirementIndicator(userIndex, requirement, isValid) {
            const reqElement = document.getElementById(`req-${requirement}-${userIndex}`);
            if (reqElement) {
                reqElement.innerHTML = isValid ? '✓ ' + reqElement.textContent.substring(2) : '✗ ' + reqElement.textContent.substring(2);
                reqElement.className = isValid ? 'valid' : 'invalid';
            }
        }

        // Toggle password visibility with Font Awesome icons
        function togglePasswordVisibility(userIndex) {
            const passwordInput = document.getElementById(`password-${userIndex}`);
            const toggleBtn = event.currentTarget; // Use currentTarget instead of target for reliability
            const icon = toggleBtn.querySelector('i'); // Get the icon element

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                // Change icon to 'visible' state
                icon.className = 'fas fa-eye-slash'; // Font Awesome eye-slash for visible
            } else {
                passwordInput.type = 'password';
                // Change icon to 'hidden' state
                icon.className = 'fas fa-eye'; // Font Awesome eye for hidden
            }
        }

        // Validate user fields (optional)
        function validateUserFields(userIndex) {
            // You can add additional validation here if needed
            const nameInput = document.querySelector(`input[name="user[${userIndex}][name]"]`);
            const emailInput = document.querySelector(`input[name="user[${userIndex}][email]"]`);
            const loginInput = document.querySelector(`input[name="user[${userIndex}][login]"]`);
            const passwordInput = document.getElementById(`password-${userIndex}`);

            let isValid = true;

            if (nameInput && !nameInput.value.trim()) {
                nameInput.style.borderColor = '#e24b4a';
                isValid = false;
            } else if (nameInput) {
                nameInput.style.borderColor = '#b0b8c4';
            }

            if (emailInput && !emailInput.value.trim()) {
                emailInput.style.borderColor = '#e24b4a';
                isValid = false;
            } else if (emailInput) {
                emailInput.style.borderColor = '#b0b8c4';
            }

            if (loginInput && !loginInput.value.trim()) {
                loginInput.style.borderColor = '#e24b4a';
                isValid = false;
            } else if (loginInput) {
                loginInput.style.borderColor = '#b0b8c4';
            }

            if (passwordInput && passwordInput.value) {
                const isStrong = checkPasswordStrength(userIndex);
                if (!isStrong) {
                    passwordInput.style.borderColor = '#e24b4a';
                    isValid = false;
                } else {
                    passwordInput.style.borderColor = '#39B54A';
                }
            } else if (passwordInput && !passwordInput.value) {
                passwordInput.style.borderColor = '#e24b4a';
                isValid = false;
            }

            return isValid;
        }

        // Add this function to validate all users before form submission
        function validateAllUsers() {
            const userBlocks = document.querySelectorAll('#user-list .record-block');
            let allValid = true;

            userBlocks.forEach((block, index) => {
                const userIndex = index + 1;
                if (!validateUserFields(userIndex)) {
                    allValid = false;
                }
            });

            return allValid;
        }

        // Make additional functions globally available
        window.onServiceChange = onServiceChange;
        window.calculateServiceTotals = calculateServiceTotals;
        window.saveServicesData = saveServicesData;
        window.refreshAllServiceOptions = refreshAllServiceOptions;

        // Only allow alphanumeric characters (A-Z, a-z, 0-9)
        function onlyAlphanumeric(event) {
            const charCode = event.which ? event.which : event.keyCode;
            const char = String.fromCharCode(charCode);

            // Allow: backspace, delete, tab, escape, enter, etc.
            if (charCode === 0 || charCode === 8 || charCode === 9 || charCode === 13 || charCode === 27 || charCode === 46) {
                return true;
            }

            // Allow only alphanumeric characters
            const alphanumericRegex = /^[A-Za-z0-9]$/;
            return alphanumericRegex.test(char);
        }

        // Global variable to store checking status
        window.locationCodeValid = {};

        // Function to check for duplicate location codes in real-time
        function checkDuplicateLocationCode(element, index) {
            const locationCode = $(element).val().trim().toUpperCase();
            const resultDiv = $(`#duplicate-result-${index}`);

            // Clear if code is empty or less than 4 characters
            if (locationCode.length === 0) {
                resultDiv.html('');
                resultDiv.removeClass('text-danger text-success');
                $(element).css('border-color', '#b0b8c4');
                window.locationCodeValid[index] = false;
                return;
            }

            // if (locationCode.length < 4) {
            //     resultDiv.html('<span style="color: #f39c12;">⚠️ Must be exactly 4 characters</span>');
            //     $(element).css('border-color', '#f39c12');
            //     window.locationCodeValid[index] = false;
            //     return;
            // }

            // Check for duplicate within the same form (other location blocks)
            let isDuplicateInForm = false;
            let duplicateBlockIndex = null;

            $('.location-code-input').each(function() {
                const otherValue = $(this).val().trim().toUpperCase();
                const otherIndex = $(this).data('location-index');
                if (otherValue === locationCode && parseInt(otherIndex) !== parseInt(index) && otherValue !== '') {
                    isDuplicateInForm = true;
                    duplicateBlockIndex = otherIndex;
                    return false; // break the loop
                }
            });

            if (isDuplicateInForm) {
                resultDiv.html(`<span style="color: #e24b4a;">⚠️ This code is already used in Location #${duplicateBlockIndex}</span>`);
                $(element).css('border-color', '#e24b4a');
                window.locationCodeValid[index] = false;
                return;
            }

            // Show checking status
            resultDiv.html('<span style="color: #666;">Checking availability...</span>');

            // Check in database via AJAX
            $.ajax({
                url: 'ajax/location_code_checker.php',
                type: 'POST',
                data: {
                    LOCATION_CODE: locationCode
                },
                dataType: 'text',
                success: function(response) {
                    if (response && response.trim() !== '') {
                        // Duplicate found in database
                        resultDiv.html(response);
                        $(element).css('border-color', '#e24b4a');
                        window.locationCodeValid[index] = false;
                    } else {
                        // Code is available
                        resultDiv.html('<span style="color: #39B54A;">✓ Available</span>');
                        $(element).css('border-color', '#39B54A');
                        window.locationCodeValid[index] = true;
                    }
                },
                error: function() {
                    resultDiv.html('<span style="color: #f39c12;">⚠️ Error checking availability</span>');
                    $(element).css('border-color', '#f39c12');
                    window.locationCodeValid[index] = false;
                }
            });
        }

        // Validate all location codes before submission
        function validateAllLocationCodes() {
            let allValid = true;
            let errorMessages = [];

            $('.location-code-input').each(function(index) {
                const locationCode = $(this).val().trim().toUpperCase();
                const locationIndex = $(this).data('location-index');

                if (!locationCode) {
                    allValid = false;
                    errorMessages.push(`Location #${locationIndex}: Code is required`);
                    $(this).css('border-color', '#e24b4a');
                } else if (locationCode.length !== 4) {
                    allValid = false;
                    errorMessages.push(`Location #${locationIndex}: Code must be exactly 4 characters (current: ${locationCode.length})`);
                    $(this).css('border-color', '#e24b4a');
                } else if (window.locationCodeValid[locationIndex] !== true) {
                    allValid = false;
                    errorMessages.push(`Location #${locationIndex}: Location Code "${locationCode}" is already exist.`);
                    $(this).css('border-color', '#e24b4a');
                }
            });

            if (!allValid) {
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    html: '<div style="text-align: left;">Please fix the following location code issues:<br><br>' +
                        errorMessages.join('<br>') + '</div>',
                    confirmButtonColor: '#e24b4a',
                    confirmButtonText: 'OK'
                });
                return false;
            }

            return true;
        }

        // Convert input value to uppercase
        function convertToUpperCase(element) {
            if (element.value) {
                element.value = element.value.toUpperCase();
            }
        }
    </script>
</body>

</html>