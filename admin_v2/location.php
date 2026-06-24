<?php
require_once('../global/config.php');
require_once("../global/stripe-php/init.php");

global $db;
global $db_account;
global $upload_path;
global $AMI_ENABLE;

use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

if (empty($_GET['id']))
    $title = "Add Location";
else
    $title = "Edit Location";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

$PK_ACCOUNT_MASTER = $_SESSION['PK_ACCOUNT_MASTER'];
$PK_USER = $_SESSION['PK_USER'];

$help_title = '';
$help_description = '';
$help = $db->Execute("SELECT * FROM DOA_HELP_PAGE WHERE PAGE_LINK = 'location'");
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
    $res = $db->Execute("SELECT * FROM `DOA_LOCATION` WHERE `PK_LOCATION` = '22'");
    $PK_LOCATION = 0;
    $PK_CORPORATION = $res->fields['PK_CORPORATION'];
    $PK_ACCOUNT_TYPE = $res->fields['PK_ACCOUNT_TYPE'];
    $FRANCHISE = '';
    $LOCATION_NAME = '';
    $LOCATION_CODE = '';
    $ADDRESS = '';
    $ADDRESS_1 = '';
    $PK_COUNTRY = '';
    $PK_STATES = '';
    $CITY = '';
    $ZIP_CODE = '';
    $PHONE = '';
    $EMAIL = '';
    $IMAGE_PATH = '';
    $PK_TIMEZONE = '';
    $TIME_SLOT_INTERVAL     = $res->fields['TIME_SLOT_INTERVAL'];
    $SERVICE_PROVIDER_TITLE = $res->fields['SERVICE_PROVIDER_TITLE'];
    $OPERATION_TAB_TITLE    = $res->fields['OPERATION_TAB_TITLE'];
    $ENROLLMENT_ID_CHAR     = $res->fields['ENROLLMENT_ID_CHAR'];
    $ENROLLMENT_ID_NUM      = $res->fields['ENROLLMENT_ID_NUM'];
    $MISCELLANEOUS_ID_CHAR  = $res->fields['MISCELLANEOUS_ID_CHAR'];
    $MISCELLANEOUS_ID_NUM   = $res->fields['MISCELLANEOUS_ID_NUM'];
    $APPOINTMENT_REMINDER   = $res->fields['APPOINTMENT_REMINDER'];
    $HOUR                   = $res->fields['HOUR'];
    $ROYALTY_PERCENTAGE = $res->fields['ROYALTY_PERCENTAGE'];
    $ACTIVE = $res->fields['ACTIVE'];

    $PAYMENT_GATEWAY_TYPE = '';
    $GATEWAY_MODE = '';
    $SECRET_KEY = '';
    $PUBLISHABLE_KEY = '';
    $ACCESS_TOKEN = '';
    $SQUARE_APP_ID = '';
    $SQUARE_LOCATION_ID = '';
    $LOGIN_ID = '';
    $TRANSACTION_KEY = '';
    $AUTHORIZE_CLIENT_KEY = '';
    $MERCHANT_ID = '';
    $API_KEY = '';
    $PUBLIC_API_KEY = '';


    $AM_USER_NAME = '';
    $AM_PASSWORD = '';
    $AM_REFRESH_TOKEN = '';
    $SALES_TAX              = $res->fields['SALES_TAX'];
    $RECEIPT_CHARACTER      = $res->fields['RECEIPT_CHARACTER'];
    $TEXTING_FEATURE_ENABLED = '';
    $ENABLE_AI_VOICE_AGENT = '';
    $TWILIO_ACCOUNT_TYPE = '';
    $SID = '';
    $TOKEN = '';
    $TWILIO_PHONE_NO = '';
    $FOCUSBIZ_API_KEY = '';
    $USER_INACTIVE_DAYS = '';
    $USERNAME_PREFIX = '';

    $SMTP_HOST = '';
    $SMTP_PORT = '';
    $SMTP_USERNAME = '';
    $SMTP_PASSWORD = '';

    $START_DATE = '';
    $PAYMENT_FROM = '';
    $SUBSCRIPTION_START_DATE = '';
    $NEXT_RENEWAL_DATE = '';
    $SUBSCRIPTION_AMOUNT = '';
} else {
    $res = $db->Execute("SELECT * FROM `DOA_LOCATION` WHERE `PK_LOCATION` = '$_GET[id]'");
    if ($res->RecordCount() == 0) {
        header("location:all_locations.php");
        exit;
    }

    $PK_LOCATION = $_GET['id'];
    $PK_CORPORATION = $res->fields['PK_CORPORATION'];
    $PK_ACCOUNT_TYPE = $res->fields['PK_ACCOUNT_TYPE'];
    $FRANCHISE = $res->fields['FRANCHISE'];
    $LOCATION_NAME = $res->fields['LOCATION_NAME'];
    $LOCATION_CODE = $res->fields['LOCATION_CODE'];
    $ADDRESS = $res->fields['ADDRESS'];
    $ADDRESS_1 = $res->fields['ADDRESS_1'];
    $PK_COUNTRY = $res->fields['PK_COUNTRY'];
    $PK_STATES = $res->fields['PK_STATES'];
    $CITY = $res->fields['CITY'];
    $ZIP_CODE = $res->fields['ZIP_CODE'];
    $PHONE = $res->fields['PHONE'];
    $EMAIL = $res->fields['EMAIL'];
    $IMAGE_PATH = $res->fields['IMAGE_PATH'];
    $PK_TIMEZONE = $res->fields['PK_TIMEZONE'];
    $TIME_SLOT_INTERVAL     = $res->fields['TIME_SLOT_INTERVAL'];
    $SERVICE_PROVIDER_TITLE = $res->fields['SERVICE_PROVIDER_TITLE'];
    $OPERATION_TAB_TITLE    = $res->fields['OPERATION_TAB_TITLE'];
    $ENROLLMENT_ID_CHAR     = $res->fields['ENROLLMENT_ID_CHAR'];
    $ENROLLMENT_ID_NUM      = $res->fields['ENROLLMENT_ID_NUM'];
    $MISCELLANEOUS_ID_CHAR  = $res->fields['MISCELLANEOUS_ID_CHAR'];
    $MISCELLANEOUS_ID_NUM   = $res->fields['MISCELLANEOUS_ID_NUM'];
    $APPOINTMENT_REMINDER   = $res->fields['APPOINTMENT_REMINDER'];
    $HOUR                   = $res->fields['HOUR'];
    $ROYALTY_PERCENTAGE = $res->fields['ROYALTY_PERCENTAGE'];
    $ACTIVE = $res->fields['ACTIVE'];

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

    $AM_USER_NAME           = $res->fields['AM_USER_NAME'];
    $AM_PASSWORD            = $res->fields['AM_PASSWORD'];
    $AM_REFRESH_TOKEN       = $res->fields['AM_REFRESH_TOKEN'];
    $SALES_TAX              = $res->fields['SALES_TAX'];
    $RECEIPT_CHARACTER      = $res->fields['RECEIPT_CHARACTER'];
    $TEXTING_FEATURE_ENABLED = $res->fields['TEXTING_FEATURE_ENABLED'];
    $ENABLE_AI_VOICE_AGENT  = $res->fields['ENABLE_AI_VOICE_AGENT'];
    $TWILIO_ACCOUNT_TYPE    = $res->fields['TWILIO_ACCOUNT_TYPE'];
    $SID                    = $res->fields['SID'];
    $TOKEN                  = $res->fields['TOKEN'];
    $TWILIO_PHONE_NO        = $res->fields['TWILIO_PHONE_NO'];

    $FOCUSBIZ_API_KEY = $res->fields['FOCUSBIZ_API_KEY'];
    $USER_INACTIVE_DAYS = $res->fields['USER_INACTIVE_DAYS'];
    $USERNAME_PREFIX = $res->fields['USERNAME_PREFIX'];

    $SMTP_HOST = $res->fields['SMTP_HOST'];
    $SMTP_PORT = $res->fields['SMTP_PORT'];
    $SMTP_USERNAME = $res->fields['SMTP_USERNAME'];
    $SMTP_PASSWORD = $res->fields['SMTP_PASSWORD'];

    $START_DATE = $res->fields['CREATED_ON'];
    $PAYMENT_FROM = $res->fields['PAYMENT_FROM'];
    $SUBSCRIPTION_START_DATE = $res->fields['SUBSCRIPTION_START_DATE'];
    $NEXT_RENEWAL_DATE = $res->fields['NEXT_RENEWAL_DATE'];
    $SUBSCRIPTION_AMOUNT = $res->fields['SUBSCRIPTION_AMOUNT'];
}

$user_data = $db->Execute("SELECT DOA_USERS.ABLE_TO_EDIT_PAYMENT_GATEWAY FROM DOA_USERS WHERE PK_USER = '$_SESSION[PK_USER]'");
$ABLE_TO_EDIT_PAYMENT_GATEWAY = $user_data->fields['ABLE_TO_EDIT_PAYMENT_GATEWAY'];

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
$AMOUNT = ($FRANCHISE == 1) ? $AM_AMOUNT : $NOT_AM_AMOUNT;

if (!empty($_POST)) {
    if ($_POST['FUNCTION_NAME'] == 'saveHolidayData') {
        unset($_POST['FUNCTION_NAME']);
        $PK_LOCATION = (int)$_POST['PK_LOCATION'];
        $db->Execute("DELETE FROM `DOA_LOCATION_HOLIDAY_LIST` WHERE `PK_LOCATION` = " . $PK_LOCATION);
        for ($i = 0; $i < count($_POST['HOLIDAY_DATE']); $i++) {
            $HOLIDAY_LIST_DATA['PK_LOCATION'] = $PK_LOCATION;
            $HOLIDAY_LIST_DATA['HOLIDAY_DATE'] = date('Y-m-d', strtotime($_POST['HOLIDAY_DATE'][$i]));
            $HOLIDAY_LIST_DATA['HOLIDAY_NAME'] = $_POST['HOLIDAY_NAME'][$i];
            db_perform('DOA_LOCATION_HOLIDAY_LIST', $HOLIDAY_LIST_DATA, 'insert');
        }
    }

    if ($_POST['FUNCTION_NAME'] == 'savePermissionData') {
        $PK_LOCATION = (int)$_POST['PK_LOCATION'];

        $db->Execute("DELETE FROM DOA_CUSTOMER_TAB WHERE PK_LOCATION = " . $PK_LOCATION);

        if (!empty($_POST['TAB_NAME']) && is_array($_POST['TAB_NAME'])) {
            foreach ($_POST['TAB_NAME'] as $i => $tab_name) {
                $permission = isset($_POST['PERMISSION'][$i]) ? 1 : 0;

                $PERMISSION_DATA = [
                    'PK_LOCATION' => $PK_LOCATION,
                    'TAB_NAME' => $tab_name,
                    'PERMISSION' => $permission
                ];

                db_perform('DOA_CUSTOMER_TAB', $PERMISSION_DATA, 'insert');
            }
        }
    }

    header("location:all_locations.php");
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include 'layout/header_script.php'; ?>
    <?php require_once('../includes/header.php'); ?>
    <?php include 'layout/header.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        /* Keep original header styles */
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

        .breadcrumb-wrapper h4 .badge-location {
            font-size: 14px;
            font-weight: 500;
            background: var(--gray-200);
            color: var(--gray-700);
            padding: 4px 14px;
            border-radius: 50px;
            margin-left: 12px;
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
        }

        .card-modern .card-header .location-name {
            font-size: 14px;
            font-weight: 500;
            color: var(--primary-color);
        }

        .card-modern .card-body {
            padding: 24px;
        }

        /* Tabs - Updated without scroll */
        .tabs-modern {
            display: flex;
            gap: 4px;
            border-bottom: 2px solid var(--gray-200);
            padding-bottom: 0;
            margin-bottom: 24px;
            flex-wrap: wrap;
            /* Changed from nowrap to wrap */
        }

        /* Remove scrollbar styles */
        .tabs-modern::-webkit-scrollbar {
            display: none;
        }

        .tabs-modern::-webkit-scrollbar-thumb {
            display: none;
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

        .form-grid.three-col {
            grid-template-columns: 1fr 1fr 1fr;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-grid.three-col {
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

        .form-control-modern:disabled {
            background: var(--gray-100);
            cursor: not-allowed;
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

        .checkbox-group-modern {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: var(--gray-700);
            cursor: pointer;
        }

        .checkbox-group-modern input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: var(--primary-color);
            cursor: pointer;
            flex-shrink: 0;
        }

        /* Toggle Switch */
        .switch-modern {
            position: relative;
            display: inline-block;
            width: 44px;
            height: 24px;
            flex-shrink: 0;
        }

        .switch-modern input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .switch-modern .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: var(--gray-300);
            transition: .3s;
            border-radius: 24px;
        }

        .switch-modern .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .3s;
            border-radius: 50%;
            box-shadow: var(--shadow-sm);
        }

        .switch-modern input:checked+.slider {
            background-color: var(--primary-color);
        }

        .switch-modern input:checked+.slider:before {
            transform: translateX(20px);
        }

        .switch-modern input:focus+.slider {
            box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.2);
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

        .btn-modern-sm {
            padding: 6px 18px;
            font-size: 13px;
        }

        .btn-modern .spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid var(--gray-200);
            flex-wrap: wrap;
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

        /* Section Headers */
        .section-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 18px;
            background: var(--gray-50);
            border-radius: var(--radius-sm);
            margin: 8px 0 20px 0;
            border: 1px solid var(--gray-200);
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

        /* Operational Hours */
        .hours-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr auto;
            gap: 12px 16px;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid var(--gray-100);
        }

        .hours-grid:last-child {
            border-bottom: none;
        }

        .hours-grid .day-label {
            font-weight: 500;
            color: var(--gray-700);
            font-size: 14px;
        }

        .hours-grid .closed-label {
            font-size: 13px;
            color: var(--gray-500);
        }

        @media (max-width: 768px) {
            .hours-grid {
                grid-template-columns: 1fr 1fr;
                gap: 8px;
            }

            .hours-grid .day-label {
                grid-column: 1 / -1;
            }
        }

        /* Payment Register - Updated */
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

        /* DataTable wrapper fixes */
        .dataTables_wrapper {
            width: 100% !important;
            overflow-x: auto;
        }

        .dataTables_wrapper .dataTables_scroll {
            width: 100% !important;
        }

        .dataTables_wrapper .dataTables_scrollBody {
            width: 100% !important;
        }

        .dataTables_wrapper .dataTables_filter input {
            border: 1.5px solid var(--gray-200);
            border-radius: var(--radius-sm);
            padding: 6px 12px;
            margin-left: 8px;
            outline: none;
            transition: border-color 0.2s;
        }

        .dataTables_wrapper .dataTables_filter input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1);
        }

        .dataTables_wrapper .dataTables_length select {
            border: 1.5px solid var(--gray-200);
            border-radius: var(--radius-sm);
            padding: 4px 8px;
            margin: 0 4px;
            outline: none;
        }

        .dataTables_wrapper .dataTables_info {
            color: var(--gray-500);
            font-size: 13px;
            padding-top: 8px;
        }

        /* DataTable responsive for small screens */
        @media (max-width: 768px) {
            .table-modern {
                font-size: 13px;
                min-width: 500px;
            }

            .table-modern thead th,
            .table-modern tbody td {
                padding: 8px 12px;
            }

            .dataTables_wrapper .dataTables_filter input {
                max-width: 150px;
            }
        }

        /* Holiday List */
        .holiday-row {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 16px;
            align-items: center;
            padding: 8px 0;
        }

        .holiday-row .remove-btn {
            color: var(--danger-color);
            cursor: pointer;
            font-size: 18px;
            padding: 4px 8px;
            border: none;
            background: none;
            transition: transform 0.2s;
            border-radius: 50%;
        }

        .holiday-row .remove-btn:hover {
            transform: scale(1.2);
        }

        /* Permission Toggle */
        .permission-row {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 16px;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid var(--gray-100);
        }

        .permission-row:last-child {
            border-bottom: none;
        }

        .permission-row .tab-name {
            font-size: 14px;
            color: var(--gray-700);
        }

        /* Credit Card */
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
            border-color: var(--danger-color);
        }

        /* Responsive adjustments */
        @media (max-width: 640px) {
            .breadcrumb-wrapper {
                flex-direction: column;
                align-items: flex-start;
            }

            .form-actions {
                flex-direction: column;
            }

            .form-actions .btn-modern {
                width: 100%;
                justify-content: center;
            }

            .tabs-modern .tab-item {
                padding: 10px 14px;
                font-size: 13px;
            }

            .card-modern .card-body {
                padding: 16px;
            }
        }

        /* Input group with percentage */
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

        /* Image preview */
        .image-preview {
            width: 100px;
            height: 100px;
            border-radius: var(--radius-sm);
            overflow: hidden;
            border: 2px solid var(--gray-200);
            margin-top: 8px;
        }

        .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .image-preview a {
            display: block;
            width: 100%;
            height: 100%;
        }

        /* Payment status messages */
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

        .alert-modern.warning {
            background: #FEF3C7;
            color: #92400E;
            border: 1px solid #FCD34D;
        }

        /* Card list */
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
    </style>
</head>

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">
        <!-- Keep original header include - DO NOT CHANGE -->
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
                        <div class="main-grid">
                            <!-- Main Content -->
                            <div class="card-modern">
                                <div class="card-header">
                                    <h5>
                                        <i class="bi bi-geo-alt me-2" style="color: #39b54a;"></i>
                                        <?= !empty($_GET['id']) ?  $LOCATION_NAME : 'Create New Location' ?>
                                    </h5>
                                    <?php if (!empty($_GET['id'])): ?>
                                        <span class="location-name">
                                            <i class="fas fa-circle" style="color: <?= ($ACTIVE == 1) ? 'var(--success-color)' : 'var(--gray-400)'; ?>; font-size: 10px; margin-right: 6px;"></i>
                                            <?= ($ACTIVE == 1) ? 'Active' : 'Inactive' ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body">
                                    <!-- Tabs -->
                                    <div class="tabs-modern" role="tablist">
                                        <button class="tab-item active" data-tab="location_div" role="tab">
                                            <i class="fas fa-location-dot"></i> Location
                                        </button>
                                        <button class="tab-item" data-tab="operational_hours" role="tab">
                                            <i class="fas fa-clock"></i> Hours
                                        </button>
                                        <?php if (!empty($_GET['id'])): ?>
                                            <button class="tab-item" data-tab="holiday_list" role="tab">
                                                <i class="fas fa-calendar-days"></i> Holidays
                                            </button>
                                            <button class="tab-item" data-tab="customer_tab_permissions" role="tab">
                                                <i class="fas fa-check-circle"></i> Permissions
                                            </button>
                                            <button class="tab-item" data-tab="payment_register" role="tab">
                                                <i class="fas fa-receipt"></i> Payments
                                            </button>
                                            <button class="tab-item" data-tab="billing" role="tab">
                                                <i class="fas fa-credit-card"></i> Billing
                                            </button>
                                            <button class="tab-item" data-tab="credit_card" role="tab">
                                                <i class="fas fa-credit-card"></i> Card
                                            </button>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Tab Content -->
                                    <div class="tab-content-modern">
                                        <!-- Location Tab -->
                                        <div class="tab-pane-modern active" id="location_div" role="tabpanel">
                                            <form id="location_form" method="post" enctype="multipart/form-data">
                                                <input type="hidden" name="FUNCTION_NAME" value="saveLocationData">
                                                <input type="hidden" name="PK_LOCATION" value="<?= $PK_LOCATION ?>">

                                                <div class="form-grid">
                                                    <!-- Corporation -->
                                                    <div class="form-group-modern">
                                                        <label class="form-label">Corporation <span class="required">*</span></label>
                                                        <select class="form-control-modern" name="PK_CORPORATION" id="PK_CORPORATION" required>
                                                            <option value="">Select Corporation</option>
                                                            <?php
                                                            $row = $db->Execute("SELECT PK_CORPORATION, CORPORATION_NAME FROM DOA_CORPORATION WHERE ACTIVE = 1 AND PK_ACCOUNT_MASTER = " . $_SESSION['PK_ACCOUNT_MASTER'] . " ORDER BY PK_CORPORATION");
                                                            while (!$row->EOF) { ?>
                                                                <option value="<?= $row->fields['PK_CORPORATION']; ?>" <?= ($row->fields['PK_CORPORATION'] == $PK_CORPORATION) ? "selected" : "" ?>><?= htmlspecialchars($row->fields['CORPORATION_NAME']) ?></option>
                                                            <?php $row->MoveNext();
                                                            } ?>
                                                        </select>
                                                    </div>

                                                    <!-- Account Type -->
                                                    <div class="form-group-modern">
                                                        <label class="form-label">Account Type <span class="required">*</span></label>
                                                        <div class="radio-group-modern">
                                                            <?php
                                                            $row = $db->Execute("SELECT PK_ACCOUNT_TYPE,ACCOUNT_TYPE FROM DOA_ACCOUNT_TYPE WHERE ACTIVE='1' ORDER BY PK_ACCOUNT_TYPE");
                                                            while (!$row->EOF) { ?>
                                                                <label class="radio-item">
                                                                    <input type="radio" name="PK_ACCOUNT_TYPE" value="<?= $row->fields['PK_ACCOUNT_TYPE']; ?>" <?php if ($row->fields['PK_ACCOUNT_TYPE'] == $PK_ACCOUNT_TYPE) echo 'checked'; ?> required>
                                                                    <?= htmlspecialchars($row->fields['ACCOUNT_TYPE']) ?>
                                                                </label>
                                                            <?php $row->MoveNext();
                                                            } ?>
                                                        </div>
                                                    </div>

                                                    <!-- Franchise -->
                                                    <?php if ($AMI_ENABLE == 1): ?>
                                                        <div class="form-group-modern">
                                                            <label class="form-label">Arthur Murray Franchise</label>
                                                            <div class="radio-group-modern">
                                                                <label class="radio-item">
                                                                    <input type="radio" name="FRANCHISE" value="1" <?php if ($FRANCHISE == 1) echo 'checked'; ?> onclick="showArthurMurraySetup(this);"> Yes
                                                                </label>
                                                                <label class="radio-item">
                                                                    <input type="radio" name="FRANCHISE" value="0" <?php if ($FRANCHISE == 0) echo 'checked'; ?> onclick="showArthurMurraySetup(this);"> No
                                                                </label>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>

                                                    <!-- Location Name -->
                                                    <div class="form-group-modern">
                                                        <label class="form-label">Location <span class="required">*</span></label>
                                                        <input type="text" class="form-control-modern" name="LOCATION_NAME" placeholder="Enter Location Name" required value="<?= htmlspecialchars($LOCATION_NAME) ?>">
                                                    </div>

                                                    <!-- Location Code -->
                                                    <div class="form-group-modern">
                                                        <label class="form-label">Location Code <span class="required">*</span></label>
                                                        <input type="text" class="form-control-modern" name="LOCATION_CODE" placeholder="Enter Location Code" required value="<?= htmlspecialchars($LOCATION_CODE) ?>">
                                                    </div>

                                                    <!-- Address -->
                                                    <div class="form-group-modern">
                                                        <label class="form-label">Address</label>
                                                        <input type="text" class="form-control-modern" name="ADDRESS" placeholder="Enter Address" value="<?= htmlspecialchars($ADDRESS) ?>">
                                                    </div>

                                                    <!-- Address 1 -->
                                                    <div class="form-group-modern">
                                                        <label class="form-label">Apt/Ste</label>
                                                        <input type="text" class="form-control-modern" name="ADDRESS_1" placeholder="Enter Apartment or Suite" value="<?= htmlspecialchars($ADDRESS_1) ?>">
                                                    </div>

                                                    <!-- Country -->
                                                    <div class="form-group-modern">
                                                        <label class="form-label">Country <span class="required">*</span></label>
                                                        <select class="form-control-modern" name="PK_COUNTRY" id="PK_COUNTRY" onChange="fetch_state(this.value)" required>
                                                            <option value="">Select Country</option>
                                                            <?php
                                                            $row = $db->Execute("SELECT PK_COUNTRY,COUNTRY_NAME FROM DOA_COUNTRY WHERE ACTIVE = 1 ORDER BY PK_COUNTRY");
                                                            while (!$row->EOF) { ?>
                                                                <option value="<?= $row->fields['PK_COUNTRY']; ?>" <?= ($row->fields['PK_COUNTRY'] == $PK_COUNTRY) ? "selected" : "" ?>><?= htmlspecialchars($row->fields['COUNTRY_NAME']) ?></option>
                                                            <?php $row->MoveNext();
                                                            } ?>
                                                        </select>
                                                    </div>

                                                    <!-- State -->
                                                    <div class="form-group-modern">
                                                        <label class="form-label">State <span class="required">*</span></label>
                                                        <div id="State_div"></div>
                                                    </div>

                                                    <!-- City -->
                                                    <div class="form-group-modern">
                                                        <label class="form-label">City</label>
                                                        <input type="text" class="form-control-modern" name="CITY" placeholder="Enter City" value="<?= htmlspecialchars($CITY) ?>">
                                                    </div>

                                                    <!-- ZIP -->
                                                    <div class="form-group-modern">
                                                        <label class="form-label">Postal / Zip Code</label>
                                                        <input type="text" class="form-control-modern" name="ZIP_CODE" placeholder="Enter Postal / Zip Code" value="<?= htmlspecialchars($ZIP_CODE) ?>">
                                                    </div>

                                                    <!-- Phone -->
                                                    <div class="form-group-modern">
                                                        <label class="form-label">Phone</label>
                                                        <input type="text" class="form-control-modern" name="PHONE" placeholder="Enter Phone No." value="<?= htmlspecialchars($PHONE) ?>">
                                                    </div>

                                                    <!-- Email -->
                                                    <div class="form-group-modern">
                                                        <label class="form-label">Email</label>
                                                        <input type="email" class="form-control-modern" name="EMAIL" placeholder="Enter Email Address" value="<?= htmlspecialchars($EMAIL) ?>">
                                                    </div>

                                                    <!-- Image -->
                                                    <div class="form-group-modern">
                                                        <label class="form-label">Location Image</label>
                                                        <input type="file" class="form-control-modern" name="IMAGE_PATH" accept="image/*">
                                                        <?php if ($IMAGE_PATH != ''): ?>
                                                            <div class="image-preview">
                                                                <a class="fancybox" href="<?= $IMAGE_PATH; ?>" data-fancybox-group="gallery">
                                                                    <img src="<?= $IMAGE_PATH; ?>" alt="Location Image">
                                                                </a>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>

                                                    <!-- Timezone -->
                                                    <div class="form-group-modern">
                                                        <label class="form-label">Timezone <span class="required">*</span></label>
                                                        <select class="form-control-modern" name="PK_TIMEZONE" required>
                                                            <option value="">Select</option>
                                                            <?php
                                                            $res_type = $db->Execute("SELECT * FROM DOA_TIMEZONE WHERE ACTIVE = 1 ORDER BY NAME ASC");
                                                            while (!$res_type->EOF) { ?>
                                                                <option value="<?= $res_type->fields['PK_TIMEZONE'] ?>" <?php if ($res_type->fields['PK_TIMEZONE'] == $PK_TIMEZONE) echo 'selected'; ?>><?= htmlspecialchars($res_type->fields['NAME']) ?></option>
                                                            <?php $res_type->MoveNext();
                                                            } ?>
                                                        </select>
                                                    </div>

                                                    <!-- Time Interval -->
                                                    <div class="form-group-modern">
                                                        <label class="form-label">Time Interval for Calendar Rows</label>
                                                        <select class="form-control-modern" name="TIME_SLOT_INTERVAL">
                                                            <option value="">Select</option>
                                                            <?php for ($i = 5; $i <= 60; $i += 5): ?>
                                                                <option value="00:<?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>:00" <?= ($TIME_SLOT_INTERVAL == '00:' . str_pad($i, 2, '0', STR_PAD_LEFT) . ':00') ? 'selected' : '' ?>>
                                                                    00:<?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>:00
                                                                </option>
                                                            <?php endfor; ?>
                                                        </select>
                                                    </div>

                                                    <!-- Service Provider Title -->
                                                    <div class="form-group-modern">
                                                        <label class="form-label">Title for Service Provider</label>
                                                        <input type="text" class="form-control-modern" name="SERVICE_PROVIDER_TITLE" placeholder="Title for Service Provider" value="<?= htmlspecialchars($SERVICE_PROVIDER_TITLE) ?>">
                                                    </div>

                                                    <!-- Operation Tab Title -->
                                                    <div class="form-group-modern">
                                                        <label class="form-label">Name of the Tab for Charging Services</label>
                                                        <input type="text" class="form-control-modern" name="OPERATION_TAB_TITLE" placeholder="Name of the Tab for Charging Services" value="<?= htmlspecialchars($OPERATION_TAB_TITLE) ?>">
                                                    </div>

                                                    <!-- Enrollment Prefix -->
                                                    <div class="form-group-modern">
                                                        <label class="form-label">Enrollment Prefix</label>
                                                        <input type="text" class="form-control-modern" name="ENROLLMENT_ID_CHAR" placeholder="Enrollment Prefix" value="<?= htmlspecialchars($ENROLLMENT_ID_CHAR) ?>">
                                                    </div>

                                                    <!-- Starting Enrollment -->
                                                    <div class="form-group-modern">
                                                        <label class="form-label">Starting Enrollment Number</label>
                                                        <input type="number" class="form-control-modern" name="ENROLLMENT_ID_NUM" placeholder="Starting Enrollment Number" value="<?= htmlspecialchars($ENROLLMENT_ID_NUM) ?>">
                                                    </div>

                                                    <!-- Misc Enrollment Prefix -->
                                                    <div class="form-group-modern">
                                                        <label class="form-label">Misc Enrollment Prefix</label>
                                                        <input type="text" class="form-control-modern" name="MISCELLANEOUS_ID_CHAR" placeholder="Misc Enrollment Prefix" value="<?= htmlspecialchars($MISCELLANEOUS_ID_CHAR) ?>">
                                                    </div>

                                                    <!-- Starting Misc Enrollment -->
                                                    <div class="form-group-modern">
                                                        <label class="form-label">Starting Misc Enrollment Number</label>
                                                        <input type="number" class="form-control-modern" name="MISCELLANEOUS_ID_NUM" placeholder="Starting Misc Enrollment Number" value="<?= htmlspecialchars($MISCELLANEOUS_ID_NUM) ?>">
                                                    </div>

                                                    <!-- Royalty Percentage -->
                                                    <div class="form-group-modern">
                                                        <label class="form-label">Royalty Percentage</label>
                                                        <div class="input-group-modern">
                                                            <input type="text" class="form-control-modern" name="ROYALTY_PERCENTAGE" value="<?= htmlspecialchars($ROYALTY_PERCENTAGE) ?>">
                                                            <span class="input-group-text">%</span>
                                                        </div>
                                                    </div>

                                                    <!-- Sales Tax -->
                                                    <div class="form-group-modern">
                                                        <label class="form-label">Sales Tax</label>
                                                        <div class="input-group-modern">
                                                            <input type="text" class="form-control-modern" name="SALES_TAX" value="<?= htmlspecialchars($SALES_TAX) ?>">
                                                            <span class="input-group-text">%</span>
                                                        </div>
                                                    </div>

                                                    <!-- Receipt Prefix -->
                                                    <div class="form-group-modern">
                                                        <label class="form-label">Receipt Prefix <span class="required">*</span></label>
                                                        <input type="text" class="form-control-modern" name="RECEIPT_CHARACTER" placeholder="Receipt Prefix" required value="<?= htmlspecialchars($RECEIPT_CHARACTER) ?>">
                                                    </div>

                                                    <!-- Focusbiz API Key -->
                                                    <div class="form-group-modern">
                                                        <label class="form-label">Focusbiz API Key</label>
                                                        <input type="hidden" name="FOCUSBIZ_API_KEY_OLD" value="<?= $FOCUSBIZ_API_KEY ? $FOCUSBIZ_API_KEY : '' ?>">
                                                        <input type="text" class="form-control-modern" name="FOCUSBIZ_API_KEY" placeholder="Enter Focusbiz API Key" value="<?= htmlspecialchars($FOCUSBIZ_API_KEY) ?>">
                                                    </div>

                                                    <!-- User Inactive Days -->
                                                    <div class="form-group-modern">
                                                        <label class="form-label">User Inactive Days</label>
                                                        <select class="form-control-modern" name="USER_INACTIVE_DAYS">
                                                            <option value="">Select</option>
                                                            <option value="30" <?= ($USER_INACTIVE_DAYS == '30') ? 'selected' : '' ?>>30 Days</option>
                                                            <option value="60" <?= ($USER_INACTIVE_DAYS == '60') ? 'selected' : '' ?>>60 Days</option>
                                                            <option value="90" <?= ($USER_INACTIVE_DAYS == '90') ? 'selected' : '' ?>>90 Days</option>
                                                        </select>
                                                    </div>

                                                    <!-- Appointment Reminder -->
                                                    <div class="form-group-modern" style="grid-column: 1 / -1;">
                                                        <label class="form-label">Send an Appointment Reminder Text message.</label>
                                                        <div class="radio-group-modern">
                                                            <label class="radio-item">
                                                                <input type="radio" name="APPOINTMENT_REMINDER" value="1" <?= ($APPOINTMENT_REMINDER == '1') ? 'checked' : '' ?> onclick="showHourBox(this);"> Yes
                                                            </label>
                                                            <label class="radio-item">
                                                                <input type="radio" name="APPOINTMENT_REMINDER" value="0" <?= ($APPOINTMENT_REMINDER == '0') ? 'checked' : '' ?> onclick="showHourBox(this);"> No
                                                            </label>
                                                        </div>
                                                        <div id="hour_box" style="display: <?= ($APPOINTMENT_REMINDER == '1') ? 'block' : 'none' ?>; margin-top: 8px;">
                                                            <label class="form-label" style="font-weight: 400;">How many hours before the appointment?</label>
                                                            <input type="text" class="form-control-modern" name="HOUR" value="<?= htmlspecialchars($HOUR) ?>" style="max-width: 200px;">
                                                        </div>
                                                    </div>

                                                    <!-- Texting Feature -->
                                                    <div class="form-group-modern">
                                                        <label class="form-label">Texting Feature Enabled?</label>
                                                        <div class="radio-group-modern">
                                                            <label class="radio-item">
                                                                <input type="radio" name="TEXTING_FEATURE_ENABLED" value="1" <?php if ($TEXTING_FEATURE_ENABLED == 1) echo 'checked'; ?>> Yes
                                                            </label>
                                                            <label class="radio-item">
                                                                <input type="radio" name="TEXTING_FEATURE_ENABLED" value="0" <?php if ($TEXTING_FEATURE_ENABLED == 0) echo 'checked'; ?>> No
                                                            </label>
                                                        </div>
                                                    </div>

                                                    <?php if ($account_data->fields['ENABLE_AI_VOICE_AGENT'] == 1): ?>
                                                        <div class="form-group-modern">
                                                            <label class="form-label">Enable AI Voice Agent?</label>
                                                            <div class="radio-group-modern">
                                                                <label class="radio-item">
                                                                    <input type="radio" name="ENABLE_AI_VOICE_AGENT" value="1" <?php if ($ENABLE_AI_VOICE_AGENT == 1) echo 'checked'; ?>> Yes
                                                                </label>
                                                                <label class="radio-item">
                                                                    <input type="radio" name="ENABLE_AI_VOICE_AGENT" value="0" <?php if ($ENABLE_AI_VOICE_AGENT == 0) echo 'checked'; ?>> No
                                                                </label>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>

                                                    <!-- Twilio Account Type -->
                                                    <div class="form-group-modern" style="grid-column: 1 / -1;">
                                                        <label class="form-label">Which Twilio Account You Want to Use?</label>
                                                        <div class="radio-group-modern">
                                                            <label class="radio-item">
                                                                <input type="radio" name="TWILIO_ACCOUNT_TYPE" value="0" <?php if ($TWILIO_ACCOUNT_TYPE == 0) echo 'checked'; ?> onclick="showTwilioSetting(this);"> Using Doable's Twilio account
                                                            </label>
                                                            <label class="radio-item">
                                                                <input type="radio" name="TWILIO_ACCOUNT_TYPE" value="1" <?php if ($TWILIO_ACCOUNT_TYPE == 1) echo 'checked'; ?> onclick="showTwilioSetting(this);"> Using Your own Twilio Account
                                                            </label>
                                                        </div>
                                                    </div>

                                                    <!-- Twilio Settings -->
                                                    <div id="twilio_setting_div" style="display: <?= ($TWILIO_ACCOUNT_TYPE == 1) ? 'grid' : 'none' ?>; grid-column: 1 / -1; gap: 16px; padding-top: 8px;">
                                                        <div class="section-header">
                                                            <i class="fas fa-phone"></i>
                                                            <span>Twilio Settings</span>
                                                        </div>
                                                        <div class="form-grid">
                                                            <div class="form-group-modern">
                                                                <label class="form-label">SID</label>
                                                                <input type="text" class="form-control-modern" name="SID" placeholder="Enter SID" value="<?= htmlspecialchars($SID) ?>">
                                                            </div>
                                                            <div class="form-group-modern">
                                                                <label class="form-label">Token</label>
                                                                <input type="text" class="form-control-modern" name="TOKEN" placeholder="Enter Token" value="<?= htmlspecialchars($TOKEN) ?>">
                                                            </div>
                                                            <div class="form-group-modern">
                                                                <label class="form-label">Phone No.</label>
                                                                <input type="text" class="form-control-modern" name="TWILIO_PHONE_NO" placeholder="Enter Phone No." value="<?= htmlspecialchars($TWILIO_PHONE_NO) ?>">
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Payment Gateway -->
                                                    <?php if ($ABLE_TO_EDIT_PAYMENT_GATEWAY == 1): ?>
                                                        <div style="grid-column: 1 / -1;">
                                                            <div class="section-header">
                                                                <i class="fas fa-credit-card"></i>
                                                                <span>Electronic Connection to Merchant Service</span>
                                                            </div>
                                                            <div class="form-grid">
                                                                <div class="form-group-modern">
                                                                    <label class="form-label">Payment Gateway</label>
                                                                    <div class="radio-group-modern">
                                                                        <label class="radio-item">
                                                                            <input type="radio" name="PAYMENT_GATEWAY_TYPE" value="Stripe" <?= ($PAYMENT_GATEWAY_TYPE == 'Stripe') ? 'checked' : '' ?> onclick="showPaymentGateway(this);"> Stripe
                                                                        </label>
                                                                        <label class="radio-item">
                                                                            <input type="radio" name="PAYMENT_GATEWAY_TYPE" value="Square" <?= ($PAYMENT_GATEWAY_TYPE == 'Square') ? 'checked' : '' ?> onclick="showPaymentGateway(this);"> Square
                                                                        </label>
                                                                        <label class="radio-item">
                                                                            <input type="radio" name="PAYMENT_GATEWAY_TYPE" value="Authorized.net" <?= ($PAYMENT_GATEWAY_TYPE == 'Authorized.net') ? 'checked' : '' ?> onclick="showPaymentGateway(this);"> Authorized.net
                                                                        </label>
                                                                        <label class="radio-item">
                                                                            <input type="radio" name="PAYMENT_GATEWAY_TYPE" value="Clover" <?= ($PAYMENT_GATEWAY_TYPE == 'Clover') ? 'checked' : '' ?> onclick="showPaymentGateway(this);"> Clover
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                                <div class="form-group-modern">
                                                                    <label class="form-label">Gateway Mode</label>
                                                                    <div class="radio-group-modern">
                                                                        <label class="radio-item">
                                                                            <input type="radio" name="GATEWAY_MODE" value="test" <?= ($GATEWAY_MODE == 'test' || $GATEWAY_MODE == null || $GATEWAY_MODE == '') ? 'checked' : '' ?>> Test
                                                                        </label>
                                                                        <label class="radio-item">
                                                                            <input type="radio" name="GATEWAY_MODE" value="live" <?= ($GATEWAY_MODE == 'live') ? 'checked' : '' ?>> Live
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <!-- Stripe -->
                                                            <div id="stripe" class="form-grid" style="display: <?= ($PAYMENT_GATEWAY_TYPE == 'Stripe') ? 'grid' : 'none' ?>; margin-top: 12px;">
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
                                                            <div id="square" class="form-grid" style="display: <?= ($PAYMENT_GATEWAY_TYPE == 'Square') ? 'grid' : 'none' ?>; margin-top: 12px;">
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
                                                            <div id="authorized" class="form-grid" style="display: <?= ($PAYMENT_GATEWAY_TYPE == 'Authorized.net') ? 'grid' : 'none' ?>; margin-top: 12px;">
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
                                                            <div id="Clover" class="form-grid" style="display: <?= ($PAYMENT_GATEWAY_TYPE == 'Clover') ? 'grid' : 'none' ?>; margin-top: 12px;">
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
                                                    <?php endif; ?>

                                                    <!-- Email Settings -->
                                                    <div style="grid-column: 1 / -1;">
                                                        <div class="section-header">
                                                            <i class="fas fa-envelope"></i>
                                                            <span>Email Connection</span>
                                                        </div>
                                                        <div class="form-grid">
                                                            <div class="form-group-modern">
                                                                <label class="form-label">SMTP Host</label>
                                                                <input type="text" class="form-control-modern" name="SMTP_HOST" value="<?= htmlspecialchars($SMTP_HOST) ?>">
                                                            </div>
                                                            <div class="form-group-modern">
                                                                <label class="form-label">SMTP Port</label>
                                                                <input type="text" class="form-control-modern" name="SMTP_PORT" value="<?= htmlspecialchars($SMTP_PORT) ?>">
                                                            </div>
                                                            <div class="form-group-modern">
                                                                <label class="form-label">SMTP Username</label>
                                                                <input type="text" class="form-control-modern" name="SMTP_USERNAME" value="<?= htmlspecialchars($SMTP_USERNAME) ?>">
                                                            </div>
                                                            <div class="form-group-modern">
                                                                <label class="form-label">SMTP Password</label>
                                                                <input type="text" class="form-control-modern" name="SMTP_PASSWORD" value="<?= htmlspecialchars($SMTP_PASSWORD) ?>">
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Arthur Murray API -->
                                                    <?php if ($AMI_ENABLE == 1): ?>
                                                        <div id="arthur_murray_setup" style="grid-column: 1 / -1; display: <?= ($FRANCHISE == '1') ? 'block' : 'none' ?>;">
                                                            <div class="section-header">
                                                                <i class="fas fa-cog"></i>
                                                                <span>Arthur Murray API Setup</span>
                                                            </div>
                                                            <div class="form-grid">
                                                                <div class="form-group-modern">
                                                                    <label class="form-label">User Name</label>
                                                                    <input type="text" class="form-control-modern" name="AM_USER_NAME" value="<?= htmlspecialchars($AM_USER_NAME) ?>">
                                                                </div>
                                                                <div class="form-group-modern">
                                                                    <label class="form-label">Password</label>
                                                                    <input type="text" class="form-control-modern" name="AM_PASSWORD" value="<?= htmlspecialchars($AM_PASSWORD) ?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>

                                                    <!-- Active Status -->
                                                    <?php if (!empty($_GET['id'])): ?>
                                                        <div class="form-group-modern" style="grid-column: 1 / -1;">
                                                            <label class="form-label">Active</label>
                                                            <div class="radio-group-modern">
                                                                <label class="radio-item">
                                                                    <input type="radio" name="ACTIVE" value="1" <?php if ($ACTIVE == 1) echo 'checked'; ?>> Yes
                                                                </label>
                                                                <label class="radio-item">
                                                                    <input type="radio" name="ACTIVE" value="0" <?php if ($ACTIVE == 0) echo 'checked'; ?>> No
                                                                </label>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>

                                                <div class="form-actions">
                                                    <button type="submit" class="btn-modern btn-modern-primary">
                                                        <i class="fas fa-save"></i> <?= empty($_GET['id']) ? 'Create Location' : 'Update Location' ?>
                                                    </button>
                                                    <button type="button" class="btn-modern btn-modern-secondary" onclick="window.location.href='all_locations.php'">
                                                        <i class="fas fa-times"></i> Cancel
                                                    </button>
                                                </div>
                                            </form>
                                        </div>

                                        <!-- Operational Hours Tab -->
                                        <div class="tab-pane-modern" id="operational_hours" role="tabpanel">
                                            <form id="operational_hours_form" method="post">
                                                <input type="hidden" name="FUNCTION_NAME" value="saveOperationalHours">
                                                <input type="hidden" name="PK_LOCATION" value="<?= $PK_LOCATION ?>">

                                                <div style="margin-bottom: 20px;">
                                                    <label class="checkbox-group-modern">
                                                        <input type="checkbox" name="ALL_DAYS" onclick="applyToAllDays(this)">
                                                        Apply to All Days
                                                    </label>
                                                </div>

                                                <?php
                                                $operational_hours = $db_account->Execute("SELECT * FROM DOA_OPERATIONAL_HOUR WHERE `PK_LOCATION` = '$PK_LOCATION'");
                                                $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                                if ($operational_hours->RecordCount() > 0) {
                                                    $i = 0;
                                                    while (!$operational_hours->EOF) {
                                                        $dayIndex = (int)$operational_hours->fields['DAY_NUMBER'] - 1;
                                                ?>
                                                        <div class="hours-grid">
                                                            <div class="day-label"><?= $days[$dayIndex] ?? 'Day ' . $operational_hours->fields['DAY_NUMBER'] ?></div>
                                                            <div>
                                                                <input type="text" class="form-control-modern time-picker OPEN_TIME" name="OPEN_TIME[]" value="<?= ($operational_hours->fields['OPEN_TIME'] == '00:00:00') ? '' : date('h:i A', strtotime($operational_hours->fields['OPEN_TIME'])) ?>" style="pointer-events: <?= ($operational_hours->fields['CLOSED'] == 1) ? 'none' : '' ?>" readonly>
                                                            </div>
                                                            <div>
                                                                <input type="text" class="form-control-modern time-picker CLOSE_TIME" name="CLOSE_TIME[]" value="<?= ($operational_hours->fields['CLOSE_TIME'] == '00:00:00') ? '' : date('h:i A', strtotime($operational_hours->fields['CLOSE_TIME'])) ?>" style="pointer-events: <?= ($operational_hours->fields['CLOSED'] == 1) ? 'none' : '' ?>" readonly>
                                                            </div>
                                                            <label class="checkbox-group-modern closed-label">
                                                                <input type="checkbox" name="CLOSED_<?= $i ?>" onchange="closeThisDay(this)" <?= ($operational_hours->fields['CLOSED'] == 1) ? 'checked' : '' ?>>
                                                                Closed
                                                            </label>
                                                        </div>
                                                    <?php
                                                        $operational_hours->MoveNext();
                                                        $i++;
                                                    }
                                                } else {
                                                    for ($i = 1; $i <= 7; $i++) {
                                                    ?>
                                                        <div class="hours-grid">
                                                            <div class="day-label"><?= $days[$i - 1] ?></div>
                                                            <div>
                                                                <input type="text" class="form-control-modern time-picker OPEN_TIME" name="OPEN_TIME[]" readonly>
                                                            </div>
                                                            <div>
                                                                <input type="text" class="form-control-modern time-picker CLOSE_TIME" name="CLOSE_TIME[]" readonly>
                                                            </div>
                                                            <label class="checkbox-group-modern closed-label">
                                                                <input type="checkbox" name="CLOSED_<?= $i - 1 ?>" onchange="closeThisDay(this)">
                                                                Closed
                                                            </label>
                                                        </div>
                                                <?php
                                                    }
                                                }
                                                ?>

                                                <div class="form-actions">
                                                    <button type="submit" class="btn-modern btn-modern-primary">
                                                        <i class="fas fa-save"></i> Save Hours
                                                    </button>
                                                    <button type="button" class="btn-modern btn-modern-secondary" onclick="window.location.href='all_locations.php'">
                                                        <i class="fas fa-times"></i> Cancel
                                                    </button>
                                                </div>
                                            </form>
                                        </div>

                                        <!-- Holiday List Tab -->
                                        <div class="tab-pane-modern" id="holiday_list" role="tabpanel">
                                            <form method="post">
                                                <input type="hidden" name="FUNCTION_NAME" value="saveHolidayData">
                                                <input type="hidden" name="PK_LOCATION" value="<?= $PK_LOCATION ?>">

                                                <div id="holiday_list_section">
                                                    <?php
                                                    $holiday_list = $db->Execute("SELECT * FROM DOA_LOCATION_HOLIDAY_LIST WHERE PK_LOCATION = " . $PK_LOCATION);
                                                    if ($holiday_list->RecordCount() > 0) {
                                                        while (!$holiday_list->EOF) {
                                                    ?>
                                                            <div class="holiday-row">
                                                                <div>
                                                                    <input type="text" class="form-control-modern datepicker-normal" name="HOLIDAY_DATE[]" value="<?= date('m/d/Y', strtotime($holiday_list->fields['HOLIDAY_DATE'])) ?>">
                                                                </div>
                                                                <div>
                                                                    <input type="text" class="form-control-modern" name="HOLIDAY_NAME[]" value="<?= htmlspecialchars($holiday_list->fields['HOLIDAY_NAME']) ?>">
                                                                </div>
                                                                <button type="button" class="remove-btn" onclick="removeThis(this);"><i class="fas fa-trash"></i></button>
                                                            </div>
                                                        <?php
                                                            $holiday_list->MoveNext();
                                                        }
                                                    } else {
                                                        ?>
                                                        <div class="holiday-row">
                                                            <div>
                                                                <input type="text" class="form-control-modern datepicker-normal" name="HOLIDAY_DATE[]">
                                                            </div>
                                                            <div>
                                                                <input type="text" class="form-control-modern" name="HOLIDAY_NAME[]">
                                                            </div>
                                                            <button type="button" class="remove-btn" onclick="removeThis(this);"><i class="fas fa-trash"></i></button>
                                                        </div>
                                                    <?php } ?>
                                                </div>

                                                <button type="button" class="btn-modern btn-modern-secondary btn-modern-sm" onclick="addMoreHoliday();" style="margin: 12px 0;">
                                                    <i class="fas fa-plus"></i> Add More
                                                </button>

                                                <div class="form-actions">
                                                    <button type="submit" class="btn-modern btn-modern-primary">
                                                        <i class="fas fa-save"></i> Save Holidays
                                                    </button>
                                                    <button type="button" class="btn-modern btn-modern-secondary" onclick="window.location.href='business_profile.php'">
                                                        <i class="fas fa-times"></i> Cancel
                                                    </button>
                                                </div>
                                            </form>
                                        </div>

                                        <!-- Customer Tab Permissions -->
                                        <div class="tab-pane-modern" id="customer_tab_permissions" role="tabpanel">
                                            <form method="post">
                                                <input type="hidden" name="FUNCTION_NAME" value="savePermissionData">
                                                <input type="hidden" name="PK_LOCATION" value="<?= $PK_LOCATION ?>">

                                                <div style="margin-bottom: 16px;">
                                                    <div class="permission-row" style="grid-template-columns: 1fr auto; font-weight: 600; color: var(--gray-600); font-size: 13px; border-bottom: 2px solid var(--gray-200);">
                                                        <span>Customer Tab</span>
                                                        <span>Visible in Customer Login</span>
                                                    </div>
                                                    <?php
                                                    $tab_options = [
                                                        'Profile' => 'Profile',
                                                        'Family' => 'Family',
                                                        'Documents' => 'Documents',
                                                        'Active Enrollments' => 'Active Enrollments',
                                                        'Completed Enrollments' => 'Completed Enrollments',
                                                        'Payment Register' => 'Payment Register',
                                                        'Appointments' => 'Appointments',
                                                        'For Record Only' => 'For Record Only',
                                                        'Comments' => 'Comments',
                                                        'Credit Card' => 'Credit Card',
                                                        'Wallet' => 'Wallet',
                                                        'Delete' => 'Delete'
                                                    ];

                                                    $customer_tabs = $db->Execute("SELECT * FROM DOA_CUSTOMER_TAB WHERE PK_LOCATION = " . $PK_LOCATION);
                                                    $existing_permissions = [];
                                                    while (!$customer_tabs->EOF) {
                                                        $existing_permissions[$customer_tabs->fields['TAB_NAME']] = $customer_tabs->fields['PERMISSION'];
                                                        $customer_tabs->MoveNext();
                                                    }

                                                    $i = 0;
                                                    foreach ($tab_options as $tab_key => $tab_label) {
                                                        $is_checked = isset($existing_permissions[$tab_key]) ? ($existing_permissions[$tab_key] == 1) : true;
                                                    ?>
                                                        <div class="permission-row">
                                                            <span class="tab-name"><?= htmlspecialchars($tab_label) ?></span>
                                                            <input type="hidden" name="TAB_NAME[]" value="<?= $tab_key ?>">
                                                            <label class="switch-modern">
                                                                <input type="checkbox" name="PERMISSION[<?= $i ?>]" value="1" <?= $is_checked ? 'checked' : '' ?>>
                                                                <span class="slider"></span>
                                                            </label>
                                                        </div>
                                                    <?php
                                                        $i++;
                                                    }
                                                    ?>
                                                </div>

                                                <div class="form-actions">
                                                    <button type="submit" class="btn-modern btn-modern-primary">
                                                        <i class="fas fa-save"></i> Save Permissions
                                                    </button>
                                                    <button type="button" class="btn-modern btn-modern-secondary" onclick="window.location.href='all_locations.php'">
                                                        <i class="fas fa-times"></i> Cancel
                                                    </button>
                                                </div>
                                            </form>
                                        </div>

                                        <!-- Payment Register -->
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
                                                            <th style="width: 20%;">Date</th>
                                                            <th style="width: 15%;">Status</th>
                                                            <th style="width: 15%;">Amount</th>
                                                            <th style="width: 25%;">Info</th>
                                                            <th style="width: 25%;">Details</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php
                                                        $location_payments = $db->Execute("SELECT * FROM DOA_PAYMENT_DETAILS WHERE PK_LOCATION = " . $PK_LOCATION . " ORDER BY DATE_TIME DESC");
                                                        if ($location_payments->RecordCount() > 0) {
                                                            while (!$location_payments->EOF) {
                                                                $payment_info = json_decode($location_payments->fields['PAYMENT_INFO']);
                                                                $payment_type = (isset($payment_info->LAST4)) ? 'Credit Card #' . $payment_info->LAST4 : $location_payments->fields['PAYMENT_INFO'];
                                                                $statusClass = ($location_payments->fields['PAYMENT_STATUS'] == 'Failed') ? 'failed' : (($location_payments->fields['PAYMENT_STATUS'] == 'Pending') ? 'pending' : 'success');
                                                        ?>
                                                                <tr>
                                                                    <td><?= date('m/d/Y h:i A', strtotime($location_payments->fields['DATE_TIME'])) ?></td>
                                                                    <td><span class="status-badge <?= $statusClass ?>"><?= $location_payments->fields['PAYMENT_STATUS'] ?></span></td>
                                                                    <td>$<?= number_format($location_payments->fields['AMOUNT'], 2) ?></td>
                                                                    <td><?= htmlspecialchars($payment_type) ?></td>
                                                                    <td><?= ($location_payments->fields['PAYMENT_FROM'] == 'corporation') ? 'Corporation' : 'Location' ?></td>
                                                                </tr>
                                                            <?php
                                                                $location_payments->MoveNext();
                                                            }
                                                        } else {
                                                            ?>
                                                            <tr>
                                                                <td colspan="5" style="text-align: center; padding: 32px; color: var(--gray-400);">No payment records found.</td>
                                                            </tr>
                                                        <?php } ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                        <!-- Billing Tab -->
                                        <div class="tab-pane-modern" id="billing" role="tabpanel">
                                            <form id="location_payment_form" method="post">
                                                <input type="hidden" class="PK_ACCOUNT_MASTER" name="PK_ACCOUNT_MASTER" value="<?= $PK_ACCOUNT_MASTER ?>">
                                                <input type="hidden" class="PK_LOCATION" name="PK_LOCATION" value="<?= $PK_LOCATION ?>">
                                                <input type="hidden" class="PK_CORPORATION" name="PK_CORPORATION" value="<?= $PK_CORPORATION ?>">
                                                <input type="hidden" name="PAYMENT_METHOD_ID" id="PAYMENT_METHOD_ID" value="">

                                                <div class="form-grid">
                                                    <div class="form-group-modern">
                                                        <label class="form-label">Subscription Start Date</label>
                                                        <p style="padding: 10px 14px; background: var(--gray-50); border-radius: var(--radius-sm); color: var(--gray-700); font-size: 14px; margin: 0;">
                                                            <?= (($SUBSCRIPTION_START_DATE == '0000-00-00') ? (($START_DATE == '') ? '' : date('m/d/Y', strtotime($START_DATE))) : date('m/d/Y', strtotime($SUBSCRIPTION_START_DATE))) ?>
                                                        </p>
                                                    </div>
                                                    <div class="form-group-modern">
                                                        <label class="form-label">Next Renewal Date</label>
                                                        <p style="padding: 10px 14px; background: var(--gray-50); border-radius: var(--radius-sm); color: var(--gray-700); font-size: 14px; margin: 0;">
                                                            <?= (($NEXT_RENEWAL_DATE == '0000-00-00') ? (($START_DATE == '') ? '' : (($RENEWAL_INTERVAL == 'monthly') ? date('m/d/Y', strtotime('+1 month', strtotime($START_DATE))) : date('m/d/Y', strtotime('+1 year', strtotime($START_DATE))))) : date('m/d/Y', strtotime($NEXT_RENEWAL_DATE))) ?>
                                                        </p>
                                                    </div>
                                                    <div class="form-group-modern">
                                                        <label class="form-label">Status</label>
                                                        <p style="padding: 10px 14px; background: var(--gray-50); border-radius: var(--radius-sm); color: var(--gray-700); font-size: 14px; margin: 0;">
                                                            <span style="display: inline-flex; align-items: center; gap: 6px;">
                                                                <i class="fas fa-circle" style="color: <?= ($ACTIVE == 1) ? 'var(--success-color)' : 'var(--gray-400)'; ?>; font-size: 10px;"></i>
                                                                <?= ($ACTIVE == 1) ? 'Active' : 'Inactive' ?>
                                                            </span>
                                                        </p>
                                                    </div>

                                                    <div class="form-group-modern" style="grid-column: 1 / -1;">
                                                        <label class="form-label">Payment From</label>
                                                        <div class="radio-group-modern">
                                                            <label class="radio-item">
                                                                <input type="radio" name="PAYMENT_FROM" class="PAYMENT_FROM" value="location" <?= (trim($PAYMENT_FROM) == 'location') ? 'checked' : '' ?> onclick="changePaymentFrom(this)"> Location
                                                            </label>
                                                            <label class="radio-item">
                                                                <input type="radio" name="PAYMENT_FROM" class="PAYMENT_FROM" value="corporation" <?= (trim($PAYMENT_FROM) == 'corporation') ? 'checked' : '' ?> onclick="changePaymentFrom(this)"> Corporation
                                                            </label>
                                                        </div>
                                                    </div>

                                                    <div class="form-group-modern" style="grid-column: 1 / -1;">
                                                        <label class="form-label">Amount</label>
                                                        <div style="position: relative;">
                                                            <span style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); font-weight: 600; color: var(--gray-500);">$</span>
                                                            <input type="text" class="form-control-modern" style="padding-left: 32px;" value="<?= number_format(($SUBSCRIPTION_AMOUNT == 0) ? $AMOUNT : $SUBSCRIPTION_AMOUNT, 2) ?>" disabled>
                                                            <input type="hidden" name="AMOUNT" id="AMOUNT" value="<?= ($SUBSCRIPTION_AMOUNT == 0) ? $AMOUNT : $SUBSCRIPTION_AMOUNT ?>">
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Payment Details -->
                                                <div id="payment_details_div" style="display: <?= ($PAYMENT_FROM == 'location') ? 'block' : 'none' ?>;">
                                                    <?php if ($SA_PAYMENT_GATEWAY_TYPE == 'Stripe'): ?>
                                                        <input type="hidden" name="stripe_token" id="stripe_token" value="">
                                                        <div class="form-group-modern" style="margin: 16px 0;">
                                                            <label class="form-label">Card Details</label>
                                                            <div id="card_div">
                                                                <div id="card-element"></div>
                                                                <div id="card-errors" style="color: var(--danger-color); font-size: 13px; margin-top: 6px;"></div>
                                                            </div>
                                                        </div>
                                                    <?php elseif ($SA_PAYMENT_GATEWAY_TYPE == 'Square'): ?>
                                                        <input type="hidden" name="square_token" class="square_token" value="">
                                                        <div class="form-group-modern" style="margin: 16px 0;">
                                                            <label class="form-label">Card Details</label>
                                                            <div id="payment-card-container" style="padding: 8px 0;"></div>
                                                            <div id="payment-status-container"></div>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div class="card_list_div"></div>
                                                </div>

                                                <div id="corporation_card_div" style="display: <?= ($PAYMENT_FROM == 'corporation') ? 'block' : 'none' ?>; margin: 16px 0;">
                                                    <div id="corporation_card_list"></div>
                                                </div>

                                                <div id="location_payment_status"></div>

                                                <div class="form-actions">
                                                    <button type="submit" id="location-payment-btn" class="btn-modern btn-modern-success">
                                                        <i class="fas fa-check"></i> Process Payment
                                                    </button>
                                                </div>
                                            </form>
                                        </div>

                                        <!-- Credit Card Tab -->
                                        <div class="tab-pane-modern" id="credit_card" role="tabpanel">
                                            <form id="credit_card_form" method="post">
                                                <input type="hidden" name="PK_LOCATION" id="PK_LOCATION" value="<?= $PK_LOCATION ?>">
                                                <input type="hidden" name="FROM" value="location">

                                                <?php if ($SA_PAYMENT_GATEWAY_TYPE == 'Stripe'): ?>
                                                    <input type="hidden" name="stripe_token" id="stripe_token" value="">
                                                    <div class="form-group-modern" style="margin: 16px 0;">
                                                        <label class="form-label">Card Details</label>
                                                        <div id="card_div">
                                                            <div id="card-element"></div>
                                                            <div id="card-errors" style="color: var(--danger-color); font-size: 13px; margin-top: 6px;"></div>
                                                        </div>
                                                    </div>
                                                <?php elseif ($SA_PAYMENT_GATEWAY_TYPE == 'Square'): ?>
                                                    <input type="hidden" name="square_token" class="square_token" value="">
                                                    <div class="form-group-modern" style="margin: 16px 0;">
                                                        <label class="form-label">Card Details</label>
                                                        <div id="save_card-card-container" style="padding: 8px 0;"></div>
                                                        <div id="save_card-status-container"></div>
                                                    </div>
                                                <?php endif; ?>

                                                <div id="save_card_payment_status"></div>
                                                <div class="card_list_div" style="margin: 16px 0;"></div>

                                                <div class="form-actions">
                                                    <button type="submit" id="save_card-pay-button" class="btn-modern btn-modern-primary">
                                                        <i class="fas fa-save"></i> Save Card
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

    <script>
        // Initialize DataTable with proper settings
        $(document).ready(function() {
            // Check if table has data before initializing
            if ($('#payment_table tbody tr').length > 0) {
                // Use retrieve: true to prevent re-initialization errors
                $('#payment_table').DataTable({
                    retrieve: true,
                    order: [
                        [0, 'desc']
                    ],
                    columnDefs: [{
                        type: 'date',
                        targets: 0
                    }],
                    pageLength: 10,
                    responsive: true,
                    autoWidth: false,
                    scrollX: true,
                    dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                        '<"row"<"col-sm-12"tr>>' +
                        '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                    language: {
                        emptyTable: "No payment records found."
                    }
                });
            }
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

        // Datepicker
        $('.datepicker-normal').datepicker({
            format: 'mm/dd/yyyy',
        });

        // Timepicker
        $('.time-picker').timepicker({
            timeFormat: 'hh:mm p',
            interval: 30,
            dynamic: false,
            dropdown: true,
            scrollbar: true
        });

        function closeThisDay(param) {
            const row = param.closest('.hours-grid');
            const inputs = row.querySelectorAll('.time-picker');
            if (param.checked) {
                inputs.forEach(input => {
                    input.value = '';
                    input.style.pointerEvents = 'none';
                });
            } else {
                inputs.forEach(input => {
                    input.style.pointerEvents = '';
                });
            }
        }

        function applyToAllDays(param) {
            const openTimes = document.querySelectorAll('.OPEN_TIME');
            const closeTimes = document.querySelectorAll('.CLOSE_TIME');
            if (param.checked) {
                const firstOpen = openTimes[0]?.value || '';
                const firstClose = closeTimes[0]?.value || '';
                openTimes.forEach((el, i) => {
                    if (i > 0) el.value = firstOpen;
                });
                closeTimes.forEach((el, i) => {
                    if (i > 0) el.value = firstClose;
                });
            } else {
                openTimes.forEach((el, i) => {
                    if (i > 0) el.value = '';
                });
                closeTimes.forEach((el, i) => {
                    if (i > 0) el.value = '';
                });
            }
        }

        function fetch_state(PK_COUNTRY) {
            const data = "PK_COUNTRY=" + PK_COUNTRY + "&PK_STATES=<?= $PK_STATES; ?>";
            $.ajax({
                url: "ajax/state.php",
                type: "POST",
                data: data,
                success: function(result) {
                    document.getElementById('State_div').innerHTML = result;
                }
            });
        }

        function showPaymentGateway(radio) {
            // Hide all payment gateway divs
            const gatewayDivs = ['stripe', 'square', 'authorized', 'Clover'];
            gatewayDivs.forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.style.display = 'none';
                }
            });

            // Show the selected one based on radio value
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
                const target = document.getElementById(targetId);
                if (target) {
                    target.style.display = 'grid';
                }
            }
        }

        function showTwilioSetting(radio) {
            const div = document.getElementById('twilio_setting_div');
            if (radio.value == '1') {
                div.style.display = 'grid';
            } else {
                div.style.display = 'none';
            }
        }

        function showArthurMurraySetup(radio) {
            const div = document.getElementById('arthur_murray_setup');
            if (radio.value == '1') {
                div.style.display = 'block';
            } else {
                div.style.display = 'none';
            }
        }

        function showHourBox(radio) {
            const box = document.getElementById('hour_box');
            if (radio.value == '1') {
                box.style.display = 'block';
            } else {
                box.style.display = 'none';
            }
        }

        function addMoreHoliday() {
            const section = document.getElementById('holiday_list_section');
            const row = document.createElement('div');
            row.className = 'holiday-row';
            row.innerHTML = `
                <div><input type="text" class="form-control-modern datepicker-normal" name="HOLIDAY_DATE[]"></div>
                <div><input type="text" class="form-control-modern" name="HOLIDAY_NAME[]"></div>
                <button type="button" class="remove-btn" onclick="removeThis(this);"><i class="fas fa-trash"></i></button>
            `;
            section.appendChild(row);
            $('.datepicker-normal').datepicker({
                format: 'mm/dd/yyyy',
            });
        }

        function removeThis(el) {
            el.closest('.holiday-row').remove();
        }

        function changePaymentFrom(param) {
            const paymentDetails = document.getElementById('payment_details_div');
            const corporationCard = document.getElementById('corporation_card_div');
            if (param.value == 'corporation') {
                paymentDetails.style.display = 'none';
                corporationCard.style.display = 'block';
                getCorporationSavedCreditCardList();
            } else {
                paymentDetails.style.display = 'block';
                corporationCard.style.display = 'none';
                getSavedCreditCardList('payment');
            }
        }

        function getSavedCreditCardList(type) {
            const gateway = '<?= $SA_PAYMENT_GATEWAY_TYPE ?>';
            if (gateway == 'Square') {
                squarePaymentFunction(type);
            } else if (gateway == 'Stripe') {
                stripePaymentFunction(type);
            }
            $.ajax({
                url: "ajax/get_credit_card_list_from_master.php",
                type: 'POST',
                data: {
                    PK_VALUE: '<?= $PK_LOCATION ?>',
                    class: 'location'
                },
                success: function(data) {
                    document.querySelectorAll('.card_list_div').forEach(el => {
                        el.style.display = 'block';
                        el.innerHTML = data;
                    });
                }
            });
        }

        function getCorporationSavedCreditCardList() {
            $.ajax({
                url: "ajax/get_credit_card_list_from_master.php",
                type: 'POST',
                data: {
                    PK_VALUE: '<?= $PK_CORPORATION ?>',
                    class: 'corporation'
                },
                success: function(data) {
                    const div = document.getElementById('corporation_card_list');
                    div.style.display = 'block';
                    div.innerHTML = data;
                }
            });
        }

        // Init
        $(document).ready(function() {
            fetch_state(<?= $PK_COUNTRY; ?>);
            const checkedPaymentFrom = document.querySelector('.PAYMENT_FROM:checked');
            if (checkedPaymentFrom) {
                changePaymentFrom(checkedPaymentFrom);
            }
        });
    </script>

    <?php if ($SA_PAYMENT_GATEWAY_TYPE == 'Stripe'): ?>
        <script src="https://js.stripe.com/v3/"></script>
        <script>
            const stripe = Stripe('<?= $SA_PUBLISHABLE_KEY ?>');
            const elements = stripe.elements();

            const style = {
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

            const stripe_card = elements.create('card', {
                style: style
            });

            function stripePaymentFunction(type) {
                if (document.getElementById('card-element')) {
                    stripe_card.mount('#card-element');
                }
                stripe_card.addEventListener('change', function(event) {
                    const displayError = document.getElementById('card-errors');
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
                        const errorElement = document.getElementById('card-errors');
                        errorElement.textContent = result.error.message;
                    } else {
                        document.getElementById('stripe_token').value = result.token.id;
                    }
                });
            }
        </script>
    <?php endif; ?>

    <?php if ($SA_PAYMENT_GATEWAY_TYPE == 'Square'):
        if ($SA_GATEWAY_MODE == 'live')
            $URL = "https://web.squarecdn.com/v1/square.js";
        else
            $URL = "https://sandbox.web.squarecdn.com/v1/square.js";
    ?>
        <script src="<?= $URL ?>"></script>
        <script>
            let square_card;

            async function squarePaymentFunction(type) {
                const square_appId = '<?= $SA_SQUARE_APP_ID ?>';
                const square_locationId = '<?= $SA_SQUARE_LOCATION_ID ?>';
                const payments = Square.payments(square_appId, square_locationId);
                square_card = await payments.card();
                const container = document.getElementById(type + '-card-container');
                if (container) {
                    container.innerHTML = '';
                    await square_card.attach('#' + type + '-card-container');
                }
            }

            async function addSquareTokenOnForm(type) {
                const statusContainer = document.getElementById(type + '-status-container');
                try {
                    const result = await square_card.tokenize();
                    if (result.status === 'OK') {
                        document.querySelectorAll('.square_token').forEach(el => el.value = result.token);
                    } else {
                        let errorMessage = `Tokenization failed with status: ${result.status}`;
                        if (result.errors) {
                            errorMessage += ` and errors: ${JSON.stringify(result.errors)}`;
                        }
                        throw new Error(errorMessage);
                    }
                } catch (e) {
                    console.error(e);
                    if (statusContainer) {
                        statusContainer.innerHTML = `<p class="alert-modern error">Payment Failed: ${e.message}</p>`;
                    }
                }
            }
        </script>
    <?php endif; ?>

    <script>
        function getPaymentMethodId(param) {
            document.querySelectorAll('.credit-card-item').forEach(el => el.classList.remove('selected'));
            param.closest('.credit-card-item')?.classList.add('selected');
            document.getElementById('PAYMENT_METHOD_ID').value = param.getAttribute('id') || '';
        }

        $(document).on('submit', '#location_payment_form', function(event) {
            event.preventDefault();
            const btn = document.getElementById('location-payment-btn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner"></span> Processing...';

            const gateway = '<?= $SA_PAYMENT_GATEWAY_TYPE ?>';
            if (gateway == 'Square') {
                const methodId = document.getElementById('PAYMENT_METHOD_ID').value;
                if (!methodId) {
                    addSquareTokenOnForm('payment');
                    setTimeout(() => submitLocationPaymentForm(), 3000);
                } else {
                    submitLocationPaymentForm();
                }
            } else {
                submitLocationPaymentForm();
            }
        });

        function submitLocationPaymentForm() {
            const form_data = $('#location_payment_form').serialize();
            $.ajax({
                url: "includes/process_location_payment.php",
                type: 'POST',
                data: form_data,
                dataType: 'json',
                success: function(data) {
                    const statusDiv = document.getElementById('location_payment_status');
                    const btn = document.getElementById('location-payment-btn');
                    if (data.STATUS === 'Failed') {
                        statusDiv.innerHTML = `<p class="alert-modern error">${data.PAYMENT_INFO}</p>`;
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-check"></i> Process Payment';
                    } else {
                        statusDiv.innerHTML = `<p class="alert-modern success">Payment Successful, page will refresh automatically.</p>`;
                        setTimeout(() => location.reload(), 3000);
                    }
                }
            });
        }

        $(document).on('submit', '#credit_card_form', function(event) {
            event.preventDefault();
            const btn = document.getElementById('save_card-pay-button');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner"></span> Processing...';

            const gateway = '<?= $SA_PAYMENT_GATEWAY_TYPE ?>';
            if (gateway == 'Square') {
                addSquareTokenOnForm('save_card');
                setTimeout(() => submitCreditCardForm(), 3000);
            } else {
                submitCreditCardForm();
            }
        });

        function submitCreditCardForm() {
            const form_data = $('#credit_card_form').serialize();
            $.ajax({
                url: "includes/save_corporation_credit_card.php",
                type: 'POST',
                data: form_data,
                dataType: 'json',
                success: function(data) {
                    const statusDiv = document.getElementById('save_card_payment_status');
                    const btn = document.getElementById('save_card-pay-button');
                    if (data.STATUS == false) {
                        statusDiv.innerHTML = `<p class="alert-modern error">${data.MESSAGE}</p>`;
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-save"></i> Save Card';
                    } else {
                        statusDiv.innerHTML = `<p class="alert-modern success">Credit Card Successfully Saved.</p>`;
                        setTimeout(() => location.reload(), 3000);
                    }
                }
            });
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
            responsive: true
        });
    </script>

    <!-- Location Form AJAX -->
    <script>
        $(document).on('submit', '#location_form', function(event) {
            event.preventDefault();

            const btn = this.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner"></span> Saving...';

            const form_data = new FormData(this);

            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                data: form_data,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        document.querySelectorAll('input[name="PK_LOCATION"]').forEach(el => el.value = response.PK_LOCATION);
                        if (window.location.href.indexOf('id=') === -1 && response.PK_LOCATION) {
                            document.querySelector('[data-tab="operational_hours"]')?.click();
                        } else {
                            window.location.href = 'location.php?id=' + response.PK_LOCATION;
                        }
                    } else {
                        alert('Error: ' + (response.message || 'Failed to save location'));
                        btn.disabled = false;
                        btn.innerHTML = originalText;
                    }
                },
                error: function() {
                    alert('An error occurred while saving the location.');
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            });
        });
    </script>

    <!-- Operational Hours AJAX -->
    <script>
        $(document).on('submit', '#operational_hours_form', function(event) {
            event.preventDefault();

            let isValid = true;
            let errorMessage = '';

            document.querySelectorAll('.hours-grid').forEach(row => {
                const isClosed = row.querySelector('input[type="checkbox"]')?.checked;
                const openTime = row.querySelector('.OPEN_TIME')?.value;
                const closeTime = row.querySelector('.CLOSE_TIME')?.value;

                if (!isClosed) {
                    if (!openTime || openTime === '') {
                        isValid = false;
                        errorMessage = 'Please select open time for all days (or mark as closed)';
                        row.querySelector('.OPEN_TIME')?.classList.add('is-invalid');
                    } else {
                        row.querySelector('.OPEN_TIME')?.classList.remove('is-invalid');
                    }
                    if (!closeTime || closeTime === '') {
                        isValid = false;
                        errorMessage = 'Please select close time for all days (or mark as closed)';
                        row.querySelector('.CLOSE_TIME')?.classList.add('is-invalid');
                    } else {
                        row.querySelector('.CLOSE_TIME')?.classList.remove('is-invalid');
                    }
                }
            });

            if (!isValid) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Validation Error',
                    text: errorMessage,
                    confirmButtonColor: '#39B54A'
                });
                return;
            }

            const btn = this.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner"></span> Saving...';

            const form_data = $(this).serialize();

            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                data: form_data,
                dataType: 'json',
                success: function(response) {
                    if (response && response.success) {
                        window.location.href = 'all_locations.php';
                    } else {
                        alert('Error: ' + (response.message || 'Failed to save operational hours'));
                        btn.disabled = false;
                        btn.innerHTML = originalText;
                    }
                },
                error: function() {
                    alert('An error occurred while saving operational hours.');
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            });
        });
    </script>
</body>

</html>