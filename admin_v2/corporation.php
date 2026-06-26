<?php
require_once('../global/config.php');
global $db;
global $db_account;

if (empty($_GET['id']))
    $title = "Add Corporation";
else
    $title = "Edit Corporation";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

$PK_ACCOUNT_MASTER = $_SESSION['PK_ACCOUNT_MASTER'];
$PK_CORPORATION =  (!empty($_GET['id'])) ? $_GET['id'] : 0;

$help_title = '';
$help_description = '';
$help = $db->Execute("SELECT * FROM DOA_HELP_PAGE WHERE PAGE_LINK = 'corporation'");
if ($help->RecordCount() > 0) {
    $help_title = $help->fields['TITLE'];
    $help_description = $help->fields['DESCRIPTION'];
}

$SA_PAYMENT_GATEWAY_TYPE = '';
$SA_GATEWAY_MODE = '';
$SA_SECRET_KEY = '';
$SA_PUBLISHABLE_KEY = '';
$SA_ACCESS_TOKEN = '';
$SA_SQUARE_APP_ID = '';
$SA_SQUARE_LOCATION_ID = '';

$payment_gateway_setting = $db->Execute("SELECT * FROM `DOA_PAYMENT_GATEWAY_SETTINGS`");
if ($payment_gateway_setting->RecordCount() > 0) {
    $SA_PAYMENT_GATEWAY_TYPE = $payment_gateway_setting->fields['PAYMENT_GATEWAY_TYPE'];
    $SA_GATEWAY_MODE = $payment_gateway_setting->fields['GATEWAY_MODE'];
    $SA_SECRET_KEY = $payment_gateway_setting->fields['SECRET_KEY'];
    $SA_PUBLISHABLE_KEY = $payment_gateway_setting->fields['PUBLISHABLE_KEY'];
    $SA_ACCESS_TOKEN = $payment_gateway_setting->fields['ACCESS_TOKEN'];
    $SA_SQUARE_APP_ID = $payment_gateway_setting->fields['APP_ID'];
    $SA_SQUARE_LOCATION_ID = $payment_gateway_setting->fields['LOCATION_ID'];
}

if (empty($_GET['id'])) {
    $CORPORATION_NAME       = '';
    $PK_TIMEZONE            = '';
    $PK_CURRENCY            = '';
    $USERNAME_PREFIX        = '';
    $TIME_SLOT_INTERVAL     = '';
    $SERVICE_PROVIDER_TITLE = '';
    $OPERATION_TAB_TITLE    = '';
    $ENROLLMENT_ID_CHAR     = '';
    $ENROLLMENT_ID_NUM      = '';
    $MISCELLANEOUS_ID_CHAR  = '';
    $MISCELLANEOUS_ID_NUM   = '';
    $APPOINTMENT_REMINDER   = '';
    $HOUR                   = '';
    $FOCUSBIZ_API_KEY       = '';
    $SALES_TAX              = '';

    $PAYMENT_GATEWAY_TYPE   = '';
    $GATEWAY_MODE           = '';
    $SECRET_KEY             = '';
    $PUBLISHABLE_KEY        = '';
    $ACCESS_TOKEN           = '';
    $SQUARE_APP_ID          = '';
    $SQUARE_LOCATION_ID     = '';
    $LOGIN_ID               = '';
    $TRANSACTION_KEY        = '';
    $AUTHORIZE_CLIENT_KEY   = '';
    $MERCHANT_ID            = '';
    $API_KEY                = '';
    $PUBLIC_API_KEY         = '';

    $FRANCHISE              = '';
    $AM_USER_NAME           = '';
    $AM_PASSWORD            = '';
    $AM_REFRESH_TOKEN       = '';

    $TEXTING_FEATURE_ENABLED = '';
    $TWILIO_ACCOUNT_TYPE     = '';
    $SID                     = '';
    $TOKEN                   = '';
    $TWILIO_PHONE_NO         = '';

    $ACTIVE                  = '';
    $START_DATE              = '';

    $PK_USER_MORNING         = '';
    $PK_USER_AFTERNOON_EVENING = '';
} else {
    $res = $db->Execute("SELECT * FROM `DOA_CORPORATION` WHERE PK_CORPORATION = '$PK_CORPORATION'");
    if ($res->RecordCount() == 0) {
        header("location:all_corporations.php");
        exit;
    }
    $CORPORATION_NAME       = $res->fields['CORPORATION_NAME'];
    $PK_TIMEZONE            = $res->fields['PK_TIMEZONE'];
    $PK_CURRENCY            = $res->fields['PK_CURRENCY'];
    $USERNAME_PREFIX        = $res->fields['USERNAME_PREFIX'];
    $TIME_SLOT_INTERVAL     = $res->fields['TIME_SLOT_INTERVAL'];
    $SERVICE_PROVIDER_TITLE = $res->fields['SERVICE_PROVIDER_TITLE'];
    $OPERATION_TAB_TITLE    = $res->fields['OPERATION_TAB_TITLE'];
    $ENROLLMENT_ID_CHAR     = $res->fields['ENROLLMENT_ID_CHAR'];
    $ENROLLMENT_ID_NUM      = $res->fields['ENROLLMENT_ID_NUM'];
    $MISCELLANEOUS_ID_CHAR  = $res->fields['MISCELLANEOUS_ID_CHAR'];
    $MISCELLANEOUS_ID_NUM   = $res->fields['MISCELLANEOUS_ID_NUM'];
    $APPOINTMENT_REMINDER   = $res->fields['APPOINTMENT_REMINDER'];
    $HOUR                   = $res->fields['HOUR'];
    $FOCUSBIZ_API_KEY       = $res->fields['FOCUSBIZ_API_KEY'];
    $SALES_TAX              = $res->fields['SALES_TAX'];

    $PAYMENT_GATEWAY_TYPE   = $res->fields['PAYMENT_GATEWAY_TYPE'];
    $GATEWAY_MODE           = $res->fields['GATEWAY_MODE'];
    $SECRET_KEY             = $res->fields['SECRET_KEY'];
    $PUBLISHABLE_KEY        = $res->fields['PUBLISHABLE_KEY'];
    $ACCESS_TOKEN           = $res->fields['ACCESS_TOKEN'];
    $SQUARE_APP_ID          = $res->fields['APP_ID'];
    $SQUARE_LOCATION_ID     = $res->fields['LOCATION_ID'];
    $LOGIN_ID               = $res->fields['LOGIN_ID'];
    $TRANSACTION_KEY        = $res->fields['TRANSACTION_KEY'];
    $AUTHORIZE_CLIENT_KEY   = $res->fields['AUTHORIZE_CLIENT_KEY'];
    $MERCHANT_ID            = $res->fields['MERCHANT_ID'];
    $API_KEY                = $res->fields['API_KEY'];
    $PUBLIC_API_KEY         = $res->fields['PUBLIC_API_KEY'];

    $FRANCHISE              = $res->fields['FRANCHISE'];
    $AM_USER_NAME           = $res->fields['AM_USER_NAME'];
    $AM_PASSWORD            = $res->fields['AM_PASSWORD'];
    $AM_REFRESH_TOKEN       = $res->fields['AM_REFRESH_TOKEN'];

    $TEXTING_FEATURE_ENABLED    = $res->fields['TEXTING_FEATURE_ENABLED'];
    $TWILIO_ACCOUNT_TYPE        = $res->fields['TWILIO_ACCOUNT_TYPE'];
    $SID                        = $res->fields['SID'];
    $TOKEN                      = $res->fields['TOKEN'];
    $TWILIO_PHONE_NO            = $res->fields['TWILIO_PHONE_NO'];

    $ACTIVE                 = $res->fields['ACTIVE'];
    $START_DATE             = $res->fields['CREATED_ON'];

    $PK_USER_MORNING         = $res->fields['PK_USER_MORNING'];
    $PK_USER_AFTERNOON_EVENING = $res->fields['PK_USER_AFTERNOON_EVENING'];
}

$user_data = $db->Execute("SELECT DOA_USERS.ABLE_TO_EDIT_PAYMENT_GATEWAY FROM DOA_USERS WHERE PK_USER = '$_SESSION[PK_USER]'");
$ABLE_TO_EDIT_PAYMENT_GATEWAY = $user_data->fields['ABLE_TO_EDIT_PAYMENT_GATEWAY'];

$am_location_data = $db->Execute("SELECT * FROM `DOA_LOCATION` WHERE ACTIVE = 1 AND FRANCHISE = 1 AND PK_CORPORATION = '$PK_CORPORATION' AND `PK_ACCOUNT_MASTER`  = " . $PK_ACCOUNT_MASTER);
$am_location_count = $am_location_data->RecordCount();

$non_am_location_data = $db->Execute("SELECT * FROM `DOA_LOCATION` WHERE ACTIVE = 1 AND FRANCHISE = 0 AND PK_CORPORATION = '$PK_CORPORATION' AND `PK_ACCOUNT_MASTER`  = " . $PK_ACCOUNT_MASTER);
$non_am_location_count = $non_am_location_data->RecordCount();

$account_data = $db->Execute("SELECT * FROM `DOA_ACCOUNT_MASTER` WHERE `PK_ACCOUNT_MASTER`  = " . $PK_ACCOUNT_MASTER);

$RENEWAL_INTERVAL = $account_data->fields['RENEWAL_INTERVAL'];

$AM_AMOUNT = $account_data->fields['AM_AMOUNT'];
$NOT_AM_AMOUNT = $account_data->fields['NOT_AM_AMOUNT'];

if (($AM_AMOUNT == '' || $AM_AMOUNT == 0.00) && ($NOT_AM_AMOUNT == '' || $NOT_AM_AMOUNT == 0.00)) {
    $res = $db->Execute("SELECT * FROM `DOA_OTHER_SETTING`");
    if ($res->RecordCount() > 0) {
        $AM_AMOUNT       = $res->fields['AM_AMOUNT'];
        $NOT_AM_AMOUNT   = $res->fields['NOT_AM_AMOUNT'];
    }
}

if (!empty($_POST)  && $_POST['FUNCTION_NAME'] == 'saveCorporationData') {
    $CORPORATION_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
    $CORPORATION_DATA['CORPORATION_NAME'] = $_POST['CORPORATION_NAME'];
    $CORPORATION_DATA['PK_TIMEZONE'] = $_POST['PK_TIMEZONE'];
    $CORPORATION_DATA['TIME_SLOT_INTERVAL'] = $_POST['TIME_SLOT_INTERVAL'];
    $CORPORATION_DATA['SERVICE_PROVIDER_TITLE'] = $_POST['SERVICE_PROVIDER_TITLE'];
    $CORPORATION_DATA['OPERATION_TAB_TITLE'] = $_POST['OPERATION_TAB_TITLE'];
    $CORPORATION_DATA['PK_CURRENCY'] = $_POST['PK_CURRENCY'];
    $CORPORATION_DATA['ENROLLMENT_ID_CHAR'] = $_POST['ENROLLMENT_ID_CHAR'];
    $CORPORATION_DATA['ENROLLMENT_ID_NUM'] = $_POST['ENROLLMENT_ID_NUM'];
    $CORPORATION_DATA['MISCELLANEOUS_ID_CHAR'] = $_POST['MISCELLANEOUS_ID_CHAR'];
    $CORPORATION_DATA['MISCELLANEOUS_ID_NUM'] = $_POST['MISCELLANEOUS_ID_NUM'];

    $CORPORATION_DATA['APPOINTMENT_REMINDER'] = $_POST['APPOINTMENT_REMINDER'];
    $CORPORATION_DATA['HOUR'] = empty($_POST['HOUR']) ? 0 : $_POST['HOUR'];

    $CORPORATION_DATA['FOCUSBIZ_API_KEY'] = $_POST['FOCUSBIZ_API_KEY'];
    $CORPORATION_DATA['SALES_TAX'] = $_POST['SALES_TAX'];

    $CORPORATION_DATA['TEXTING_FEATURE_ENABLED'] = $_POST['TEXTING_FEATURE_ENABLED'];
    $CORPORATION_DATA['TWILIO_ACCOUNT_TYPE'] = $_POST['TWILIO_ACCOUNT_TYPE'];
    $CORPORATION_DATA['SID'] = $_POST['SID'];
    $CORPORATION_DATA['TOKEN'] = $_POST['TOKEN'];
    $CORPORATION_DATA['TWILIO_PHONE_NO'] = $_POST['TWILIO_PHONE_NO'];

    $CORPORATION_DATA['PAYMENT_GATEWAY_TYPE'] = $_POST['PAYMENT_GATEWAY_TYPE'];
    $CORPORATION_DATA['GATEWAY_MODE'] = $_POST['GATEWAY_MODE'];
    $CORPORATION_DATA['SECRET_KEY'] = $_POST['SECRET_KEY'];
    $CORPORATION_DATA['PUBLISHABLE_KEY'] = $_POST['PUBLISHABLE_KEY'];
    $CORPORATION_DATA['ACCESS_TOKEN'] = $_POST['ACCESS_TOKEN'];
    $CORPORATION_DATA['APP_ID'] = $_POST['APP_ID'];
    $CORPORATION_DATA['LOCATION_ID'] = $_POST['LOCATION_ID'];
    $CORPORATION_DATA['AUTHORIZE_CLIENT_KEY'] = $_POST['AUTHORIZE_CLIENT_KEY'];
    $CORPORATION_DATA['TRANSACTION_KEY'] = $_POST['TRANSACTION_KEY'];
    $CORPORATION_DATA['LOGIN_ID'] = $_POST['LOGIN_ID'];
    $CORPORATION_DATA['MERCHANT_ID'] = $_POST['MERCHANT_ID'];
    $CORPORATION_DATA['API_KEY'] = $_POST['API_KEY'];
    $CORPORATION_DATA['PUBLIC_API_KEY'] = $_POST['PUBLIC_API_KEY'];

    if (empty($_GET['id'])) {
        $CORPORATION_DATA['ACTIVE'] = 1;
        $CORPORATION_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
        $CORPORATION_DATA['CREATED_ON'] = date("Y-m-d H:i");
        db_perform('DOA_CORPORATION', $CORPORATION_DATA, 'insert');
    } else {
        $CORPORATION_DATA['ACTIVE'] = $_POST['ACTIVE'];
        $CORPORATION_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
        $CORPORATION_DATA['EDITED_ON'] = date("Y-m-d H:i");
        db_perform('DOA_CORPORATION', $CORPORATION_DATA, 'update', " PK_CORPORATION =  '$_GET[id]'");
    }

    header("location:all_corporations.php");
}

if (!empty($_POST)  && $_POST['FUNCTION_NAME'] == 'saveChatbotSettings') {
    $PK_CORPORATION = $_POST['PK_CORPORATION'];
    $CORPORATION_DATA['PK_USER_MORNING'] = implode(',', $_POST['PK_USER_MORNING']);
    $CORPORATION_DATA['PK_USER_AFTERNOON_EVENING'] = implode(',', $_POST['PK_USER_AFTERNOON_EVENING']);

    if (!empty($_GET['id'])) {
        db_perform('DOA_CORPORATION', $CORPORATION_DATA, 'update', " PK_CORPORATION =  '$PK_CORPORATION'");
    }

    header("location:all_corporations.php");
}

if (!empty($_POST['FUNCTION_NAME']) && $_POST['FUNCTION_NAME'] == 'savecredit_cardData') {

    header("location:all_corporations.php");
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
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="assets/css/setup-styles.css" rel="stylesheet">

<style>
    :root {
        --primary-color: #39B54A;
        --primary-dark: #2D8F3B;
        --primary-rgb: 57, 181, 74;
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

    /* Layout */
    .main-grid {
        display: grid;
        grid-template-columns: 1fr 340px;
        gap: 24px;
    }

    @media (max-width: 1200px) {
        .main-grid {
            grid-template-columns: 1fr;
        }

        .container-fluid {
            padding: 16px !important;
        }
    }

    /* Card */
    .card-modern {
        background: #ffffff;
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--gray-200);
        overflow: visible;
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
    }

    /* Tabs */
    .tabs-modern {
        display: flex;
        gap: 4px;
        border-bottom: 2px solid var(--gray-200);
        padding-bottom: 0;
        margin-bottom: 24px;
        flex-wrap: wrap;
    }

    .tabs-modern .tab-item {
        padding: 12px 20px;
        font-size: 14px;
        font-weight: 500;
        color: var(--gray-500);
        cursor: pointer;
        border: none;
        background: transparent;
        border-bottom: 2px solid transparent;
        margin-bottom: -2px;
        transition: all 0.2s ease;
        white-space: nowrap;
        display: flex;
        align-items: center;
        gap: 8px;
        border-radius: 0;
    }

    .tabs-modern .tab-item:hover {
        color: var(--gray-700);
        background: var(--gray-50);
    }

    .tabs-modern .tab-item.active {
        color: var(--primary-color);
        border-bottom-color: var(--primary-color);
        font-weight: 600;
    }

    .tabs-modern .tab-item i {
        font-size: 16px;
    }

    .tab-content-modern {
        padding: 4px 0;
    }

    .tab-pane-modern {
        display: none;
        animation: fadeIn 0.3s ease;
    }

    .tab-pane-modern.active {
        display: block;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(8px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
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
        color: #EF4444;
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

    /* Section Header */
    .section-header {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px 18px;
        background: var(--gray-50);
        border-radius: var(--radius-sm);
        margin: 8px 0 20px 0;
        border: 1px solid var(--gray-200);
        grid-column: 1 / -1;
    }

    .section-header i {
        color: var(--primary-color);
        font-size: 18px;
    }

    .section-header span {
        font-size: 14px;
        font-weight: 600;
        color: var(--gray-700);
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

    /* Payment Gateway sections */
    .gateway-section {
        grid-column: 1 / -1;
        display: none;
        gap: 12px 24px;
        padding: 16px 20px;
        background: var(--gray-50);
        border-radius: var(--radius-sm);
        border: 1px solid var(--gray-200);
        margin-top: 8px;
    }

    .gateway-section.active {
        display: grid;
        grid-template-columns: 1fr 1fr;
    }

    @media (max-width: 768px) {
        .gateway-section.active {
            grid-template-columns: 1fr;
        }
    }

    /* Input group */
    .input-group-modern {
        display: flex;
        align-items: center;
        border: 1.5px solid var(--gray-200);
        border-radius: var(--radius-sm);
        overflow: hidden;
        transition: all 0.2s ease;
    }

    .input-group-modern:focus-within {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1);
    }

    .input-group-modern .form-control-modern {
        border: none;
        border-radius: 0;
        flex: 1;
    }

    .input-group-modern .form-control-modern:focus {
        box-shadow: none;
    }

    .input-group-modern .input-group-text {
        padding: 10px 14px;
        background: var(--gray-50);
        color: var(--gray-500);
        font-size: 14px;
        font-weight: 500;
        border-left: 1px solid var(--gray-200);
    }

    /* Payment Register Table */
    .table-modern {
        width: 100% !important;
        border-collapse: collapse;
        font-size: 14px;
        min-width: 600px;
    }

    .table-modern thead th {
        background: var(--gray-50);
        padding: 12px 16px;
        text-align: left;
        font-weight: 600;
        color: var(--gray-600);
        border-bottom: 2px solid var(--gray-200);
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        white-space: nowrap;
    }

    .table-modern tbody td {
        padding: 12px 16px;
        border-bottom: 1px solid var(--gray-100);
        color: var(--gray-700);
        vertical-align: middle;
    }

    .table-modern tbody tr:hover {
        background: var(--gray-50);
    }

    .table-modern .status-badge {
        display: inline-block;
        padding: 4px 14px;
        border-radius: 50px;
        font-size: 12px;
        font-weight: 500;
        white-space: nowrap;
    }

    .table-modern .status-badge.success {
        background: #D1FAE5;
        color: #065F46;
    }

    .table-modern .status-badge.failed {
        background: #FEE2E2;
        color: #991B1B;
    }

    .table-modern .status-badge.pending {
        background: #FEF3C7;
        color: #92400E;
    }

    /* Stripe element */
    #card-element,
    .StripeElement {
        width: 100%;
        padding: 10px 14px;
        border: 1.5px solid var(--gray-200);
        border-radius: var(--radius-sm);
        background: #fff;
        transition: all 0.2s ease;
        min-height: 44px;
    }

    #card-element:focus,
    .StripeElement--focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1);
    }

    .StripeElement--invalid {
        border-color: #EF4444;
    }

    /* Credit card item */
    .credit-card-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 16px;
        border: 1.5px solid var(--gray-200);
        border-radius: var(--radius-sm);
        cursor: pointer;
        transition: all 0.2s ease;
        margin-bottom: 8px;
    }

    .credit-card-item:hover {
        border-color: var(--primary-color);
        background: var(--gray-50);
    }

    .credit-card-item.selected {
        border-color: var(--primary-color);
        background: #F0FDF4;
    }

    .credit-card-item i {
        font-size: 24px;
        color: var(--gray-400);
    }

    .credit-card-item .card-details {
        flex: 1;
        font-size: 14px;
        color: var(--gray-700);
    }

    .credit-card-item .card-details .card-brand {
        font-weight: 600;
    }

    /* Help Card */
    .help-card {
        background: linear-gradient(135deg, #F0FDF4 0%, #ECFDF5 100%);
        border: 1px solid #A7F3D0;
        border-radius: var(--radius-lg);
        padding: 24px;
        position: sticky;
        top: 24px;
    }

    .help-card .help-icon {
        width: 48px;
        height: 48px;
        background: var(--primary-color);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 20px;
        margin-bottom: 16px;
    }

    .help-card h5 {
        font-size: 16px;
        font-weight: 600;
        color: var(--gray-800);
        margin: 0 0 12px 0;
    }

    .help-card p {
        font-size: 14px;
        color: var(--gray-600);
        line-height: 1.6;
        margin: 0;
    }

    .alert-modern {
        padding: 12px 16px;
        border-radius: var(--radius-sm);
        font-size: 14px;
        margin: 12px 0;
    }

    .alert-modern.success {
        background: #D1FAE5;
        color: #065F46;
        border: 1px solid #A7F3D0;
    }

    .alert-modern.error {
        background: #FEE2E2;
        color: #991B1B;
        border: 1px solid #FCA5A5;
    }
</style>

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">
        <?php require_once('../includes/header.php'); ?>

        <div class="page-wrapper" style="padding-top: 0px !important;">
            <div class="container-fluid py-4 px-4 m-auto mx-auto dashboard-container">
                <div class="row g-4">
                    <!-- Sidebar -->
                    <div class="col-12 col-md-4 col-xl-2">
                        <?php include 'layout/setup_sidebar.php'; ?>
                    </div>

                    <!-- Main Form -->
                    <div class="col-12 col-md-8 col-xl-10">
                        <!-- Main Grid -->
                        <div class="main-grid">
                            <!-- Main Content -->
                            <div class="card-modern">
                                <div class="card-header">
                                    <h5>
                                        <i class="bi bi-building"></i>
                                        <?= $title ?>
                                    </h5>
                                    <?php if (!empty($_GET['id'])): ?>
                                        <span class="status-indicator <?= ($ACTIVE == 1) ? 'active' : 'inactive' ?>">
                                            <i class="fas fa-circle"></i>
                                            <?= ($ACTIVE == 1) ? 'Active' : 'Inactive' ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body">
                                    <!-- Tabs -->
                                    <div class="tabs-modern" role="tablist">
                                        <button class="tab-item active" data-tab="corporation" role="tab">
                                            <i class="bi bi-building"></i> Corporation
                                        </button>
                                        <?php if (!empty($_GET['id'])): ?>
                                            <button class="tab-item" data-tab="payment_register" role="tab">
                                                <i class="bi bi-receipt"></i> Payment Register
                                            </button>
                                            <button class="tab-item" data-tab="credit_card" role="tab" onclick="getSavedCreditCardList();">
                                                <i class="bi bi-credit-card"></i> Credit Card
                                            </button>
                                            <button class="tab-item" data-tab="chatbot_setting" role="tab">
                                                <i class="bi bi-robot"></i> Chatbot Setting
                                            </button>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Tab Content -->
                                    <div class="tab-content-modern">
                                        <!-- Corporation Tab -->
                                        <div class="tab-pane-modern active" id="corporation" role="tabpanel">
                                            <form class="form-material form-horizontal" action="" method="post" enctype="multipart/form-data">
                                                <input type="hidden" name="FUNCTION_NAME" value="saveCorporationData">

                                                <div class="form-grid">
                                                    <!-- Corporation Name -->
                                                    <div class="form-group-modern">
                                                        <label class="form-label">Corporation Name<span class="required">*</span></label>
                                                        <input type="text" id="CORPORATION_NAME" name="CORPORATION_NAME" class="form-control-modern" placeholder="Enter Corporation Name" value="<?php echo htmlspecialchars($CORPORATION_NAME) ?>">
                                                    </div>

                                                    <!-- Timezone -->
                                                    <div class="form-group-modern">
                                                        <label class="form-label">Timezone<span class="required">*</span></label>
                                                        <select name="PK_TIMEZONE" id="PK_TIMEZONE" class="form-control-modern" required>
                                                            <option value="">Select</option>
                                                            <?php $res_type = $db->Execute("SELECT * FROM DOA_TIMEZONE WHERE ACTIVE = 1 ORDER BY NAME ASC");
                                                            while (!$res_type->EOF) { ?>
                                                                <option value="<?= $res_type->fields['PK_TIMEZONE'] ?>" <?php if ($res_type->fields['PK_TIMEZONE'] == $PK_TIMEZONE) echo 'selected="selected"'; ?>><?= htmlspecialchars($res_type->fields['NAME']) ?></option>
                                                            <?php $res_type->MoveNext();
                                                            } ?>
                                                        </select>
                                                    </div>

                                                    <!-- Currency -->
                                                    <div class="form-group-modern">
                                                        <label class="form-label">Currency</label>
                                                        <select name="PK_CURRENCY" id="PK_CURRENCY" class="form-control-modern">
                                                            <?php $res_type = $db->Execute("SELECT * FROM `DOA_CURRENCY` WHERE `ACTIVE` = 1");
                                                            while (!$res_type->EOF) { ?>
                                                                <option value="<?= $res_type->fields['PK_CURRENCY'] ?>" <?= ($res_type->fields['PK_CURRENCY'] == $PK_CURRENCY) ? 'selected' : '' ?>><?= htmlspecialchars($res_type->fields['CURRENCY_NAME'] . " (" . $res_type->fields['CURRENCY_SYMBOL'] . ")") ?></option>
                                                            <?php $res_type->MoveNext();
                                                            } ?>
                                                        </select>
                                                    </div>

                                                    <!-- Payment Gateway -->
                                                    <?php if ($ABLE_TO_EDIT_PAYMENT_GATEWAY == 1) { ?>
                                                        <div style="grid-column: 1 / -1;">
                                                            <div class="section-header">
                                                                <i class="fas fa-credit-card"></i>
                                                                <span>Electronic Connection to Merchant Service</span>
                                                            </div>
                                                            <div class="form-grid" style="grid-column: 1 / -1;">
                                                                <div class="form-group-modern">
                                                                    <label class="form-label">Gateway Type</label>
                                                                    <div class="radio-group-modern">
                                                                        <label class="radio-item">
                                                                            <input type="radio" id="PAYMENT_GATEWAY_TYPE_STRIPE" name="PAYMENT_GATEWAY_TYPE" value="Stripe" <?= ($PAYMENT_GATEWAY_TYPE == 'Stripe') ? 'checked' : '' ?> onclick="showPaymentGateway(this);"> Stripe
                                                                        </label>
                                                                        <label class="radio-item">
                                                                            <input type="radio" id="PAYMENT_GATEWAY_TYPE_SQUARE" name="PAYMENT_GATEWAY_TYPE" value="Square" <?= ($PAYMENT_GATEWAY_TYPE == 'Square') ? 'checked' : '' ?> onclick="showPaymentGateway(this);"> Square
                                                                        </label>
                                                                        <label class="radio-item">
                                                                            <input type="radio" id="PAYMENT_GATEWAY_TYPE_AUTHORIZED" name="PAYMENT_GATEWAY_TYPE" value="Authorized.net" <?= ($PAYMENT_GATEWAY_TYPE == 'Authorized.net') ? 'checked' : '' ?> onclick="showPaymentGateway(this);"> Authorized.net
                                                                        </label>
                                                                        <label class="radio-item">
                                                                            <input type="radio" id="PAYMENT_GATEWAY_TYPE_CLOVER" name="PAYMENT_GATEWAY_TYPE" value="Clover" <?= ($PAYMENT_GATEWAY_TYPE == 'Clover') ? 'checked' : '' ?> onclick="showPaymentGateway(this);"> Clover
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                                <div class="form-group-modern">
                                                                    <label class="form-label">Gateway Mode</label>
                                                                    <div class="radio-group-modern">
                                                                        <label class="radio-item">
                                                                            <input type="radio" id="GATEWAY_MODE_TEST" name="GATEWAY_MODE" value="test" <?= ($GATEWAY_MODE == 'test' || $GATEWAY_MODE == null || $GATEWAY_MODE == '') ? 'checked' : '' ?>> Test
                                                                        </label>
                                                                        <label class="radio-item">
                                                                            <input type="radio" id="GATEWAY_MODE_LIVE" name="GATEWAY_MODE" value="live" <?= ($GATEWAY_MODE == 'live') ? 'checked' : '' ?>> Live
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <!-- Stripe -->
                                                            <div id="stripe" class="gateway-section <?= ($PAYMENT_GATEWAY_TYPE == 'Stripe') ? 'active' : '' ?>">
                                                                <div class="form-group-modern">
                                                                    <label class="form-label">Secret Key</label>
                                                                    <input type="text" class="form-control-modern" name="SECRET_KEY" value="<?= htmlspecialchars($SECRET_KEY) ?>">
                                                                </div>
                                                                <div class="form-group-modern">
                                                                    <label class="form-label">Publishable Key</label>
                                                                    <input type="text" class="form-control-modern" name="PUBLISHABLE_KEY" value="<?= htmlspecialchars($PUBLISHABLE_KEY) ?>">
                                                                </div>
                                                            </div>

                                                            <!-- Square -->
                                                            <div id="square" class="gateway-section <?= ($PAYMENT_GATEWAY_TYPE == 'Square') ? 'active' : '' ?>">
                                                                <div class="form-group-modern">
                                                                    <label class="form-label">Application ID</label>
                                                                    <input type="text" class="form-control-modern" name="APP_ID" value="<?= htmlspecialchars($SQUARE_APP_ID) ?>">
                                                                </div>
                                                                <div class="form-group-modern">
                                                                    <label class="form-label">Location ID</label>
                                                                    <input type="text" class="form-control-modern" name="LOCATION_ID" value="<?= htmlspecialchars($SQUARE_LOCATION_ID) ?>">
                                                                </div>
                                                                <div class="form-group-modern">
                                                                    <label class="form-label">Access Token</label>
                                                                    <input type="text" class="form-control-modern" name="ACCESS_TOKEN" value="<?= htmlspecialchars($ACCESS_TOKEN) ?>">
                                                                </div>
                                                            </div>

                                                            <!-- Authorized.net -->
                                                            <div id="authorized" class="gateway-section <?= ($PAYMENT_GATEWAY_TYPE == 'Authorized.net') ? 'active' : '' ?>">
                                                                <div class="form-group-modern">
                                                                    <label class="form-label">Login ID</label>
                                                                    <input type="text" class="form-control-modern" name="LOGIN_ID" value="<?= htmlspecialchars($LOGIN_ID) ?>">
                                                                </div>
                                                                <div class="form-group-modern">
                                                                    <label class="form-label">Transaction Key</label>
                                                                    <input type="text" class="form-control-modern" name="TRANSACTION_KEY" value="<?= htmlspecialchars($TRANSACTION_KEY) ?>">
                                                                </div>
                                                                <div class="form-group-modern">
                                                                    <label class="form-label">Authorize Client Key</label>
                                                                    <input type="text" class="form-control-modern" name="AUTHORIZE_CLIENT_KEY" value="<?= htmlspecialchars($AUTHORIZE_CLIENT_KEY) ?>">
                                                                </div>
                                                            </div>

                                                            <!-- Clover -->
                                                            <div id="Clover" class="gateway-section <?= ($PAYMENT_GATEWAY_TYPE == 'Clover') ? 'active' : '' ?>">
                                                                <div class="form-group-modern">
                                                                    <label class="form-label">Merchant ID</label>
                                                                    <input type="text" class="form-control-modern" name="MERCHANT_ID" value="<?= htmlspecialchars($MERCHANT_ID) ?>">
                                                                </div>
                                                                <div class="form-group-modern">
                                                                    <label class="form-label">Private Token</label>
                                                                    <input type="text" class="form-control-modern" name="API_KEY" value="<?= htmlspecialchars($API_KEY) ?>">
                                                                </div>
                                                                <div class="form-group-modern">
                                                                    <label class="form-label">Public Token</label>
                                                                    <input type="text" class="form-control-modern" name="PUBLIC_API_KEY" value="<?= htmlspecialchars($PUBLIC_API_KEY) ?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php } ?>

                                                    <!-- Active Status -->
                                                    <?php if (!empty($_GET['id'])) { ?>
                                                        <div class="form-group-modern" style="grid-column: 1 / -1;">
                                                            <label class="form-label">Active</label>
                                                            <div class="radio-group-modern">
                                                                <label class="radio-item">
                                                                    <input type="radio" name="ACTIVE" id="ACTIVE_YES" value="1" <?php if ($ACTIVE == 1) echo 'checked="checked"'; ?>> Yes
                                                                </label>
                                                                <label class="radio-item">
                                                                    <input type="radio" name="ACTIVE" id="ACTIVE_NO" value="0" <?php if ($ACTIVE == 0) echo 'checked="checked"'; ?>> No
                                                                </label>
                                                            </div>
                                                        </div>
                                                    <?php } ?>
                                                </div>

                                                <div class="form-actions">
                                                    <button type="submit" class="btn-modern btn-modern-primary">
                                                        <i class="fas fa-save"></i>
                                                        <?php if (empty($_GET['id'])): ?>
                                                            Save
                                                        <?php else: ?>
                                                            Update
                                                        <?php endif; ?>
                                                    </button>
                                                    <button type="button" class="btn-modern btn-modern-secondary" onclick="window.location.href='all_corporations.php'">
                                                        <i class="fas fa-times"></i> Cancel
                                                    </button>
                                                </div>
                                            </form>
                                        </div>

                                        <!-- Payment Register Tab -->
                                        <div class="tab-pane-modern" id="payment_register" role="tabpanel">
                                            <div style="margin-bottom: 16px;">
                                                <h5 style="font-weight: 600; color: var(--gray-800);">
                                                    <i class="fas fa-receipt" style="color: var(--primary-color); margin-right: 8px;"></i>
                                                    Payment History
                                                </h5>
                                            </div>
                                            <div style="overflow-x: auto; width: 100%;">
                                                <table class="table-modern" id="payment_table" style="width: 100% !important;">
                                                    <thead>
                                                        <tr>
                                                            <th>Date</th>
                                                            <th>Status</th>
                                                            <th>Amount</th>
                                                            <th>Info</th>
                                                            <th>For Location</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php
                                                        $corporation_payments = $db->Execute("SELECT DOA_PAYMENT_DETAILS.*, DOA_LOCATION.LOCATION_NAME FROM DOA_PAYMENT_DETAILS INNER JOIN DOA_LOCATION ON DOA_PAYMENT_DETAILS.PK_LOCATION = DOA_LOCATION.PK_LOCATION WHERE DOA_PAYMENT_DETAILS.PAYMENT_FROM = 'corporation' AND DOA_PAYMENT_DETAILS.PK_CORPORATION = " . $PK_CORPORATION . " ORDER BY DOA_PAYMENT_DETAILS.DATE_TIME DESC");
                                                        if ($corporation_payments->RecordCount() > 0) {
                                                            while (!$corporation_payments->EOF) {
                                                                $payment_info = json_decode($corporation_payments->fields['PAYMENT_INFO']);
                                                                $payment_type = (isset($payment_info->LAST4)) ? 'Credit Card #' . $payment_info->LAST4 : $corporation_payments->fields['PAYMENT_INFO'];
                                                                $statusClass = ($corporation_payments->fields['PAYMENT_STATUS'] == 'Failed') ? 'failed' : (($corporation_payments->fields['PAYMENT_STATUS'] == 'Pending') ? 'pending' : 'success');
                                                        ?>
                                                                <tr>
                                                                    <td><?= date('m/d/Y h:i A', strtotime($corporation_payments->fields['DATE_TIME'])) ?></td>
                                                                    <td><span class="status-badge <?= $statusClass ?>"><?= $corporation_payments->fields['PAYMENT_STATUS'] ?></span></td>
                                                                    <td>$<?= number_format($corporation_payments->fields['AMOUNT'], 2) ?></td>
                                                                    <td><?= htmlspecialchars($payment_type) ?></td>
                                                                    <td><?= htmlspecialchars($corporation_payments->fields['LOCATION_NAME']) ?></td>
                                                                </tr>
                                                            <?php $corporation_payments->MoveNext();
                                                            } ?>
                                                        <?php } else { ?>
                                                            <tr>
                                                                <td colspan="5" style="text-align: center; padding: 32px; color: var(--gray-400);">No payment records found.</td>
                                                            </tr>
                                                        <?php } ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                        <!-- Credit Card Tab -->
                                        <div class="tab-pane-modern" id="credit_card" role="tabpanel">
                                            <form class="form-material form-horizontal" id="credit_card_form" method="post" enctype="multipart/form-data">
                                                <input type="hidden" name="PK_CORPORATION" id="PK_CORPORATION" value="<?= $PK_CORPORATION ?>">
                                                <input type="hidden" name="FROM" value="corporation">
                                                <input type="hidden" name="PAYMENT_METHOD_ID" id="PAYMENT_METHOD_ID" value="">

                                                <?php if ($SA_PAYMENT_GATEWAY_TYPE == 'Stripe') { ?>
                                                    <input type="hidden" name="stripe_token" id="stripe_token" value="">
                                                    <div class="form-group-modern" style="margin: 16px 0;">
                                                        <label class="form-label">Card Details</label>
                                                        <div id="card_div">
                                                            <div id="card-element"></div>
                                                            <div id="card-errors" style="color: #EF4444; font-size: 13px; margin-top: 6px;"></div>
                                                        </div>
                                                    </div>
                                                <?php } elseif ($SA_PAYMENT_GATEWAY_TYPE == 'Square') { ?>
                                                    <input type="hidden" name="square_token" id="square_token" value="">
                                                    <div class="form-group-modern" style="margin: 16px 0;">
                                                        <label class="form-label">Card Details</label>
                                                        <div id="payment-card-container"></div>
                                                        <div id="payment-status-container"></div>
                                                    </div>
                                                <?php } ?>

                                                <div id="corporation_payment_status"></div>
                                                <div id="card_list_div" style="margin: 16px 0;"></div>

                                                <div class="form-actions">
                                                    <button type="submit" id="corporation-pay-button" class="btn-modern btn-modern-primary">
                                                        <i class="fas fa-save"></i> Save
                                                    </button>
                                                </div>
                                            </form>
                                        </div>

                                        <!-- Chatbot Settings Tab -->
                                        <div class="tab-pane-modern active" id="chatbot_setting" role="tabpanel">
                                            <form class="form-material form-horizontal" action="" method="post" enctype="multipart/form-data">
                                                <input type="hidden" name="FUNCTION_NAME" value="saveChatbotSettings">
                                                <input type="hidden" name="PK_CORPORATION" value="<?= $PK_CORPORATION ?>">

                                                <div class="form-grid">
                                                    <!-- Morning Service Provider -->
                                                    <div class="form-group-modern">
                                                        <label class="form-label">Morning <?= $service_provider_title ?></label>
                                                        <select class="multi_sumo_select" name="PK_USER_MORNING[]" multiple required>
                                                            <?php
                                                            $row = getServiceProvider();
                                                            while (!$row->EOF) { ?>
                                                                <option value="<?php echo $row->fields['PK_USER']; ?>" <?= (strpos($PK_USER_MORNING, $row->fields['PK_USER']) !== false) ? 'selected' : '' ?>><?= $row->fields['NAME'] ?></option>
                                                            <?php $row->MoveNext();
                                                            } ?>
                                                        </select>
                                                    </div>

                                                    <!-- Afternoon OR Evening Service Provider -->
                                                    <div class="form-group-modern">
                                                        <label class="form-label">Afternoon OR Evening <?= $service_provider_title ?></label>
                                                        <select class="multi_sumo_select" name="PK_USER_AFTERNOON_EVENING[]" multiple required>
                                                            <?php
                                                            $row = getServiceProvider();
                                                            while (!$row->EOF) { ?>
                                                                <option value="<?php echo $row->fields['PK_USER']; ?>" <?= (strpos($PK_USER_AFTERNOON_EVENING, $row->fields['PK_USER']) !== false) ? 'selected' : '' ?>><?= $row->fields['NAME'] ?></option>
                                                            <?php $row->MoveNext();
                                                            } ?>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="form-actions">
                                                    <button type="submit" class="btn-modern btn-modern-primary">
                                                        <i class="fas fa-save"></i> Save
                                                    </button>
                                                    <button type="button" class="btn-modern btn-modern-secondary" onclick="window.location.href='all_corporations.php'">
                                                        <i class="fas fa-times"></i> Cancel
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Help Sidebar -->
                            <div>
                                <div class="help-card">
                                    <div class="help-icon">
                                        <i class="fas fa-question"></i>
                                    </div>
                                    <h5><?= $help_title ?></h5>
                                    <p><?= $help_description ?></p>
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
    $('.multi_sumo_select').SumoSelect({
        "okCancelInMulti": true,
        "search": true,
        "searchText": 'Search here.',
        "placeholder": 'Select',
        "selectAll": true,
        "csvDispCount": 3,
        "captionFormat": '{0} Selected',
        "captionFormatAllSelected": 'All Selected.',
        "locale": ['OK', 'Cancel', 'Select All'],
    });

    // Tab switching
    document.querySelectorAll('.tab-item').forEach(tab => {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.tab-item').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-pane-modern').forEach(p => p.classList.remove('active'));

            this.classList.add('active');
            document.getElementById(this.dataset.tab).classList.add('active');
        });
    });

    $('.datepicker-past').datepicker({
        format: 'mm/dd/yyyy',
        maxDate: 0
    });

    $('.datepicker-normal').datepicker({
        format: 'mm/dd/yyyy',
    });

    $('.time-picker').timepicker({
        timeFormat: 'hh:mm p',
        interval: 30,
        dynamic: false,
        dropdown: true,
        scrollbar: true
    });

    function showHourBox(radio) {
        if (radio.value == 1) {
            document.getElementById("hour_box").style.display = "flex";
        } else {
            document.getElementById("hour_box").style.display = "none";
        }
    }

    function showTwilioAccountSetting(param) {
        if ($(param).val() === '1') {
            $('#twilio_account_type').slideDown();
        } else {
            $('#twilio_account_type').slideUp();
            $('#TWILIO_ACCOUNT_TYPE_0').prop('checked', true);
            $('#twilio_setting_div').slideUp();
        }
    }

    function showTwilioSetting(param) {
        if ($(param).val() === '1') {
            $('#twilio_setting_div').slideDown();
        } else {
            $('#twilio_setting_div').slideUp();
        }
    }

    function showPaymentGateway(radio) {
        document.querySelectorAll('.gateway-section').forEach(el => el.classList.remove('active'));

        const value = radio.value;
        let targetId = '';

        if (value === 'Stripe') {
            targetId = 'stripe';
        } else if (value === 'Square') {
            targetId = 'square';
        } else if (value === 'Authorized.net') {
            targetId = 'authorized';
        } else if (value === 'Clover') {
            targetId = 'Clover';
        }

        if (targetId) {
            const el = document.getElementById(targetId);
            if (el) {
                el.classList.add('active');
            }
        }
    }

    function getSavedCreditCardList() {
        let payment_gateway_type = '<?= $SA_PAYMENT_GATEWAY_TYPE ?>';
        if (payment_gateway_type == 'Square') {
            squarePaymentFunction();
        } else if (payment_gateway_type == 'Stripe') {
            stripePaymentFunction();
        }
        $.ajax({
            url: "ajax/get_credit_card_list_from_master.php",
            type: 'POST',
            data: {
                PK_VALUE: '<?= $PK_CORPORATION  ?>',
                class: 'corporation'
            },
            success: function(data) {
                $('#card_list_div').slideDown().html(data);
            }
        });
    }

    function getPaymentMethodId(param) {
        document.querySelectorAll('.credit-card-item').forEach(el => el.classList.remove('selected'));
        param.closest('.credit-card-item')?.classList.add('selected');
        document.getElementById('PAYMENT_METHOD_ID').value = param.getAttribute('id') || '';
    }

    $('#payment_table').DataTable({
        order: [
            [0, 'desc']
        ],
        columnDefs: [{
            type: 'date',
            targets: 0
        }],
        pageLength: 10,
        responsive: true,
        retrieve: true
    });
</script>

<?php if ($SA_PAYMENT_GATEWAY_TYPE == 'Stripe') { ?>
    <script src="https://js.stripe.com/v3/"></script>
    <script type="text/javascript">
        var stripe = Stripe('<?= $SA_PUBLISHABLE_KEY ?>');
        var elements = stripe.elements();

        var style = {
            base: {
                fontSize: '14px',
                color: '#1F2937',
                fontFamily: 'Inter, -apple-system, BlinkMacSystemFont, sans-serif',
                '::placeholder': {
                    color: '#9CA3AF'
                }
            },
            invalid: {
                color: '#EF4444',
                iconColor: '#EF4444'
            }
        };

        var stripe_card = elements.create('card', {
            style: style
        });

        function stripePaymentFunction() {
            if ($('#card-element').length > 0) {
                stripe_card.mount('#card-element');
            }
            stripe_card.addEventListener('change', function(event) {
                var displayError = document.getElementById('card-errors');
                if (event.error) {
                    displayError.textContent = event.error.message;
                } else {
                    displayError.textContent = '';
                    addStripeTokenOnForm();
                }
            });
        }

        function addStripeTokenOnForm() {
            stripe.createToken(stripe_card).then(function(result) {
                if (result.error) {
                    let errorElement = document.getElementById('card-errors');
                    errorElement.textContent = result.error.message;
                } else {
                    $('#stripe_token').val(result.token.id);
                }
            });
        }
    </script>
<?php } ?>

<?php if ($SA_PAYMENT_GATEWAY_TYPE == 'Square') {
    if ($SA_GATEWAY_MODE == 'live')
        $SQ_URL = "https://connect.squareup.com";
    else
        $SQ_URL = "https://connect.squareupsandbox.com";

    if ($SA_GATEWAY_MODE == 'live')
        $URL = "https://web.squarecdn.com/v1/square.js";
    else
        $URL = "https://sandbox.web.squarecdn.com/v1/square.js";
?>
    <script src="<?= $URL ?>"></script>
    <script type="text/javascript">
        let square_card;

        async function squarePaymentFunction() {
            let square_appId = '<?= $SA_SQUARE_APP_ID ?>';
            let square_locationId = '<?= $SA_SQUARE_LOCATION_ID ?>';
            const payments = Square.payments(square_appId, square_locationId);
            square_card = await payments.card();
            $('#payment-card-container').text('');
            await square_card.attach('#payment-card-container');
        }

        async function addSquareTokenOnForm() {
            const statusContainer = document.getElementById('payment-status-container');

            try {
                const result = await square_card.tokenize();
                if (result.status === 'OK') {
                    $('#square_token').val(result.token);
                } else {
                    let errorMessage = `Tokenization failed with status: ${result.status}`;
                    if (result.errors) {
                        errorMessage += ` and errors: ${JSON.stringify(result.errors)}`;
                    }
                    throw new Error(errorMessage);
                }
            } catch (e) {
                console.error(e);
                statusContainer.innerHTML = `<p class="alert-modern error">Payment Failed: ${e.message}</p>`;
            }
        }
    </script>
<?php } ?>

<script>
    function sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    $(document).on('submit', '#credit_card_form', function(event) {
        $('#corporation-pay-button').prop('disabled', true);
        $('#corporation-pay-button').html(`<span class="spinner-border spinner-border-sm"></span> Processing...`);
        event.preventDefault();
        let PAYMENT_GATEWAY = '<?= $SA_PAYMENT_GATEWAY_TYPE ?>';
        if (PAYMENT_GATEWAY == 'Square') {
            let PAYMENT_METHOD_ID = $('#PAYMENT_METHOD_ID').val();
            if (PAYMENT_METHOD_ID == '') {
                addSquareTokenOnForm();
                sleep(3000).then(() => {
                    submitCreditCardForm();
                });
            } else {
                submitCreditCardForm();
            }
        } else {
            submitCreditCardForm();
        }
    });

    function submitCreditCardForm() {
        let form_data = $('#credit_card_form').serialize();
        $.ajax({
            url: "includes/save_corporation_credit_card.php",
            type: 'POST',
            data: form_data,
            dataType: 'json',
            success: function(data) {
                if (data.STATUS == false) {
                    $('#corporation_payment_status').html(`<p class="alert-modern error">${data.MESSAGE}</p>`);
                    $('#corporation-pay-button').prop('disabled', false);
                    $('#corporation-pay-button').html(`<i class="fas fa-save"></i> Save`);
                } else {
                    $('#corporation_payment_status').html(`<p class="alert-modern success">Credit Card Successfully Saved.</p>`);

                    setTimeout(function() {
                        location.reload();
                    }, 3000);
                }
            }
        });
    }
</script>

</html>