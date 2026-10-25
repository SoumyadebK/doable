<?php

use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

require_once('../global/config.php');
global $db;
global $db_account;
global $upload_path;

if (empty($_GET['id']))
    $title = "Add Location";
else
    $title = "Edit Location";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

$FRANCHISE = 0;
$franchise_data = $db->Execute("SELECT FRANCHISE FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER = '1018'");
if ($franchise_data->RecordCount() > 0) {
    $FRANCHISE = $franchise_data->fields['FRANCHISE'];
}

if (!empty($_GET['id'])) {
    $account_res = $db->Execute("SELECT * FROM `DOA_ACCOUNT_MASTER` WHERE `PK_ACCOUNT_MASTER`  = '1018'");

    $PK_LOCATION = 0;
    $PK_CORPORATION = '';
    $FRANCHISE = '';
    $PK_ACCOUNT_TYPE = '';
    $LOCATION_NAME = '';
    $LOCATION_CODE = '';
    $ADDRESS = $account_res->fields['ADDRESS'];
    $ADDRESS_1 = $account_res->fields['ADDRESS_1'];
    $PK_COUNTRY = $account_res->fields['PK_COUNTRY'];
    $PK_STATES = $account_res->fields['PK_STATES'];
    $CITY = $account_res->fields['CITY'];
    $ZIP_CODE = $account_res->fields['ZIP'];
    $PHONE = $account_res->fields['PHONE'];
    $EMAIL = $account_res->fields['EMAIL'];
    $IMAGE_PATH = '';
    $PK_TIMEZONE = '';
    $TIME_SLOT_INTERVAL     = '';
    $SERVICE_PROVIDER_TITLE = '';
    $OPERATION_TAB_TITLE    = '';
    $ENROLLMENT_ID_CHAR     = '';
    $ENROLLMENT_ID_NUM      = '';
    $MISCELLANEOUS_ID_CHAR  = '';
    $MISCELLANEOUS_ID_NUM   = '';
    $APPOINTMENT_REMINDER   = '';
    $HOUR                   = '';
    $ROYALTY_PERCENTAGE = '';
    $ACTIVE = '';
    $PAYMENT_GATEWAY_TYPE = '';
    $SECRET_KEY = '';
    $PUBLISHABLE_KEY = '';
    $ACCESS_TOKEN = '';
    $SQUARE_APP_ID = '';
    $SQUARE_LOCATION_ID = '';
    $LOGIN_ID = '';
    $TRANSACTION_KEY = '';
    $AUTHORIZE_CLIENT_KEY = '';
    $AM_USER_NAME = '';
    $AM_PASSWORD = '';
    $AM_REFRESH_TOKEN = '';
    $SALES_TAX = '';
    $RECEIPT_CHARACTER = '';
    $TEXTING_FEATURE_ENABLED = '';
    $TWILIO_ACCOUNT_TYPE = '';
    $SID = '';
    $TOKEN = '';
    $TWILIO_PHONE_NO = '';
    $FOCUSBIZ_API_KEY = '';
    $USERNAME_PREFIX = '';
} else {
    $res = $db->Execute("SELECT * FROM `DOA_LOCATION` WHERE `PK_LOCATION` = '22'");

    if ($res->RecordCount() == 0) {
        header("location:all_locations.php");
        exit;
    }

    $PK_LOCATION = '';
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
    $SALES_TAX              = $res->fields['SALES_TAX'];
    $RECEIPT_CHARACTER      = $res->fields['RECEIPT_CHARACTER'];
    $ROYALTY_PERCENTAGE = $res->fields['ROYALTY_PERCENTAGE'];
    $ACTIVE = $res->fields['ACTIVE'];
    $TEXTING_FEATURE_ENABLED = $res->fields['TEXTING_FEATURE_ENABLED'];
    $PAYMENT_GATEWAY_TYPE = '';
    $SECRET_KEY = '';
    $PUBLISHABLE_KEY = '';
    $ACCESS_TOKEN = '';
    $SQUARE_APP_ID = '';
    $SQUARE_LOCATION_ID = '';
    $LOGIN_ID = '';
    $TRANSACTION_KEY = '';
    $AUTHORIZE_CLIENT_KEY = '';
    $AM_USER_NAME = '';
    $AM_PASSWORD = '';
    $AM_REFRESH_TOKEN = '';
    $TWILIO_ACCOUNT_TYPE = '';
    $SID = '';
    $TOKEN = '';
    $TWILIO_PHONE_NO = '';
    $FOCUSBIZ_API_KEY = '';
    $USERNAME_PREFIX = '';
}

$SMTP_HOST = '';
$SMTP_PORT = '';
$SMTP_USERNAME = '';
$SMTP_PASSWORD = '';
$email = $db_account->Execute("SELECT * FROM DOA_EMAIL_ACCOUNT WHERE PK_LOCATION = " . $PK_LOCATION);
if ($email->RecordCount() > 0) {
    $SMTP_HOST = $email->fields['HOST'];
    $SMTP_PORT = $email->fields['PORT'];
    $SMTP_USERNAME = $email->fields['USER_NAME'];
    $SMTP_PASSWORD = $email->fields['PASSWORD'];
}

$user_data = $db->Execute("SELECT DOA_USERS.ABLE_TO_EDIT_PAYMENT_GATEWAY FROM DOA_USERS WHERE PK_USER = '$_SESSION[PK_USER]'");
$ABLE_TO_EDIT_PAYMENT_GATEWAY = $user_data->fields['ABLE_TO_EDIT_PAYMENT_GATEWAY'];

$payment_gateway_setting = $db->Execute("SELECT * FROM `DOA_PAYMENT_GATEWAY_SETTINGS`");
$STRIPE_SECRET_KEY = $payment_gateway_setting->fields['SECRET_KEY'];
$STRIPE_PUBLISHABLE_KEY = $payment_gateway_setting->fields['PUBLISHABLE_KEY'];

require_once("../global/stripe-php-master/init.php");
$stripe = new StripeClient($STRIPE_SECRET_KEY);
$account_payment_info = $db->Execute("SELECT * FROM DOA_ACCOUNT_PAYMENT_INFO WHERE PK_LOCATION = " . $PK_LOCATION . " AND PAYMENT_TYPE = 'Stripe' AND PK_ACCOUNT_MASTER = " . $_SESSION['PK_ACCOUNT_MASTER']);
if ($account_payment_info->RecordCount() > 0) {
    $customer_id = $account_payment_info->fields['ACCOUNT_PAYMENT_ID'];
    $stripe_customer = $stripe->customers->retrieve($customer_id);
    $card_id = $stripe_customer->default_source;

    $url = "https://api.stripe.com/v1/customers/" . $customer_id . "/cards/" . $card_id;
    $AUTH = "Authorization: Bearer " . $STRIPE_SECRET_KEY;

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            $AUTH
        ),
    ));

    $response = curl_exec($curl);
    $card_details = json_decode($response, true);
}

if (!empty($_POST)) {
    if ($_POST['FUNCTION_NAME'] == 'saveOperationalHours') {
        $ALL_DAYS = isset($_POST['ALL_DAYS']) ? 1 : 0;
        $operational_hours = $db_account->Execute("SELECT * FROM DOA_OPERATIONAL_HOUR WHERE `PK_LOCATION` = '" . (int)$_POST['PK_LOCATION'] . "'");

        if ($operational_hours->RecordCount() > 0) {
            for ($i = 0; $i < count($_POST['OPEN_TIME']); $i++) {
                $PK_LOCATION = (int)$_POST['PK_LOCATION'];
                $DAY_NUMBER = (int)($i + 1);
                $OPERATIONAL_HOUR_DATA['PK_LOCATION'] = $PK_LOCATION;
                $OPERATIONAL_HOUR_DATA['DAY_NUMBER'] = $DAY_NUMBER;

                $open_time = ($ALL_DAYS == 0) ? $_POST['OPEN_TIME'][$i] : $_POST['OPEN_TIME'][0];
                if (strtoupper($open_time) == '12:00 AM' || strtoupper($open_time) == '12:00:00 AM') {
                    $OPERATIONAL_HOUR_DATA['OPEN_TIME'] = '24:00:00';
                } else {
                    $OPERATIONAL_HOUR_DATA['OPEN_TIME'] = !empty($open_time) ? date('H:i:s', strtotime($open_time)) : '00:00:00';
                }

                $close_time = ($ALL_DAYS == 0) ? $_POST['CLOSE_TIME'][$i] : $_POST['CLOSE_TIME'][0];
                if (strtoupper($close_time) == '12:00 AM' || strtoupper($close_time) == '12:00:00 AM') {
                    $OPERATIONAL_HOUR_DATA['CLOSE_TIME'] = '24:00:00';
                } else {
                    $OPERATIONAL_HOUR_DATA['CLOSE_TIME'] = !empty($close_time) ? date('H:i:s', strtotime($close_time)) : '00:00:00';
                }

                $OPERATIONAL_HOUR_DATA['CLOSED'] = isset($_POST['CLOSED_' . $i]) ? 1 : 0;

                db_perform_account('DOA_OPERATIONAL_HOUR', $OPERATIONAL_HOUR_DATA, 'update', " PK_LOCATION = $PK_LOCATION AND DAY_NUMBER = $DAY_NUMBER");
            }
        } else {
            if (count($_POST['OPEN_TIME']) > 0) {
                for ($i = 0; $i < count($_POST['OPEN_TIME']); $i++) {
                    $OPERATIONAL_HOUR_DATA['PK_LOCATION'] = (int)$_POST['PK_LOCATION'];
                    $OPERATIONAL_HOUR_DATA['DAY_NUMBER'] = $i + 1;

                    $open_time = ($ALL_DAYS == 0) ? $_POST['OPEN_TIME'][$i] : $_POST['OPEN_TIME'][0];
                    if (strtoupper($open_time) == '12:00 AM' || strtoupper($open_time) == '12:00:00 AM') {
                        $OPERATIONAL_HOUR_DATA['OPEN_TIME'] = '24:00:00';
                    } else {
                        $OPERATIONAL_HOUR_DATA['OPEN_TIME'] = !empty($open_time) ? date('H:i:s', strtotime($open_time)) : '00:00:00';
                    }

                    $close_time = ($ALL_DAYS == 0) ? $_POST['CLOSE_TIME'][$i] : $_POST['CLOSE_TIME'][0];
                    if (strtoupper($close_time) == '12:00 AM' || strtoupper($close_time) == '12:00:00 AM') {
                        $OPERATIONAL_HOUR_DATA['CLOSE_TIME'] = '24:00:00';
                    } else {
                        $OPERATIONAL_HOUR_DATA['CLOSE_TIME'] = !empty($close_time) ? date('H:i:s', strtotime($close_time)) : '00:00:00';
                    }

                    $OPERATIONAL_HOUR_DATA['CLOSED'] = isset($_POST['CLOSED_' . $i]) ? 1 : 0;

                    db_perform_account('DOA_OPERATIONAL_HOUR', $OPERATIONAL_HOUR_DATA, 'insert');
                }
            }
        }
    }
    header("location:wizard_scheduling_code.php");
}

$help_title = '';
$help_description = '';
$help = $db->Execute("SELECT * FROM DOA_HELP_PAGE WHERE PAGE_LINK = 'location'");
if ($help->RecordCount() > 0) {
    $help_title = $help->fields['TITLE'];
    $help_description = $help->fields['DESCRIPTION'];
}
?>

<!DOCTYPE html>
<html lang="en">
<?php include 'layout/header_script.php'; ?>
<?php require_once('../includes/header.php'); ?>
<?php include 'layout/header.php'; ?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

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
        gap: 8px;
        border-bottom: 1px solid var(--gray-200);
        padding: 0 32px;
        background: #fff;
        overflow-x: auto;
    }

    @media (max-width: 768px) {
        .tabs-modern {
            padding: 0 20px;
        }
    }

    .tabs-modern .tab-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 16px 20px;
        font-size: 14px;
        font-weight: 500;
        color: var(--gray-500);
        text-decoration: none;
        border-bottom: 2px solid transparent;
        margin-bottom: -1px;
        white-space: nowrap;
        transition: all 0.2s ease;
        cursor: pointer;
    }

    .tabs-modern .tab-link i {
        font-size: 16px;
    }

    .tabs-modern .tab-link:hover {
        color: var(--gray-700);
        background: var(--gray-50);
    }

    .tabs-modern .tab-link.active {
        color: var(--primary-color);
        border-bottom-color: var(--primary-color);
        background: transparent;
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

    .form-helper {
        font-size: 12px;
        color: var(--gray-400);
        margin-top: 2px;
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

    select.form-control-modern {
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236B7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 12px center;
        padding-right: 36px;
    }

    /* Input group with suffix */
    .input-group-modern {
        display: flex;
        align-items: stretch;
    }

    .input-group-modern .form-control-modern {
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
        border-right: none;
    }

    .input-group-modern .input-suffix {
        display: flex;
        align-items: center;
        padding: 0 14px;
        background: var(--gray-100);
        border: 1.5px solid var(--gray-200);
        border-left: none;
        border-top-right-radius: var(--radius-sm);
        border-bottom-right-radius: var(--radius-sm);
        font-size: 14px;
        color: var(--gray-600);
    }

    /* Radio */
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

    /* Checkbox */
    .checkbox-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        color: var(--gray-700);
        cursor: pointer;
    }

    .checkbox-item input[type="checkbox"] {
        width: 18px;
        height: 18px;
        accent-color: var(--primary-color);
        cursor: pointer;
        flex-shrink: 0;
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
    }

    /* Section Header */
    .section-header {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px 18px;
        background: var(--gray-50);
        border-radius: var(--radius-sm);
        margin: 24px 0 20px 0;
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

    /* Stripe Element */
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

    /* Alert */
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

    .full-width {
        grid-column: 1 / -1;
    }

    @media (max-width: 768px) {
        .full-width {
            grid-column: 1;
        }
    }

    /* Operational Hours table */
    .hours-header {
        display: grid;
        grid-template-columns: 1.2fr 1fr 1fr auto;
        gap: 16px;
        padding: 0 4px 12px 4px;
        border-bottom: 1px solid var(--gray-200);
        margin-bottom: 4px;
    }

    .hours-header .hours-col {
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--gray-500);
    }

    .hours-row {
        display: grid;
        grid-template-columns: 1.2fr 1fr 1fr auto;
        gap: 16px;
        align-items: center;
        padding: 10px 4px;
        border-bottom: 1px solid var(--gray-100);
    }

    .hours-row:last-child {
        border-bottom: none;
    }

    .hours-row .day-label {
        font-size: 14px;
        font-weight: 500;
        color: var(--gray-700);
    }

    @media (max-width: 640px) {
        .hours-header {
            display: none;
        }

        .hours-row {
            grid-template-columns: 1fr;
            gap: 8px;
            padding: 14px 0;
            border-bottom: 1px solid var(--gray-200);
        }
    }

    /* Credit Card display */
    .credit-card {
        width: 260px;
        height: 160px;
        border-radius: var(--radius);
        padding: 18px;
        color: #fff;
        position: relative;
        box-shadow: var(--shadow-md);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        background: linear-gradient(135deg, #374151 0%, #1F2937 100%);
    }

    .credit-card.visa {
        background: linear-gradient(135deg, #1A1F71 0%, #2A3A9B 100%);
    }

    .credit-card.mastercard {
        background: linear-gradient(135deg, #EB001B 0%, #F79E1B 100%);
    }

    .credit-card.amex {
        background: linear-gradient(135deg, #006FCF 0%, #2E77BC 100%);
    }

    .credit-card.discover {
        background: linear-gradient(135deg, #FF6000 0%, #F76B1C 100%);
    }

    .credit-card-last4 {
        font-size: 20px;
        letter-spacing: 3px;
        font-weight: 600;
    }

    .credit-card-expiry {
        font-size: 13px;
        opacity: 0.85;
    }
</style>

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">
        <?php require_once('../includes/header.php'); ?>

        <div class="page-wrapper" style="padding-top: 0px !important;">
            <div class="container-fluid py-4 px-4 m-auto mx-auto dashboard-container">

                <!-- Main Grid -->
                <div class="main-grid">
                    <!-- Main Content -->
                    <div class="card-modern">
                        <div class="card-header">
                            <h5>
                                <i class="fas fa-map-marker-alt"></i>
                                Location Details
                            </h5>
                            <?php if (!empty($_GET['id'])): ?>
                                <span class="status-indicator <?= ($ACTIVE == 1) ? 'active' : 'inactive' ?>">
                                    <i class="fas fa-circle"></i>
                                    <?= ($ACTIVE == 1) ? 'Active' : 'Inactive' ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Tabs -->
                        <ul class="nav tabs-modern" role="tablist">
                            <li>
                                <a class="tab-link active" data-bs-toggle="tab" id="location_link" href="#location_div" role="tab">
                                    <i class="fas fa-location-dot"></i> Location
                                </a>
                            </li>
                            <li>
                                <a class="tab-link" data-bs-toggle="tab" id="operational_hours_link" href="#operational_hours" role="tab">
                                    <i class="fas fa-clock"></i> Operational Hours
                                </a>
                            </li>
                            <?php if (!empty($_GET['id'])) { ?>
                                <li>
                                    <a class="tab-link" data-bs-toggle="tab" id="credit_card_link" href="#credit_card" role="tab" onclick="stripePaymentFunction();">
                                        <i class="fas fa-credit-card"></i> Credit Card
                                    </a>
                                </li>
                            <?php } ?>
                        </ul>

                        <!-- Tab panes -->
                        <div class="tab-content">
                            <!-- ================= LOCATION TAB ================= -->
                            <div class="tab-pane fade show active" id="location_div" role="tabpanel">
                                <div class="card-body">
                                    <form class="form-material form-horizontal" id="location_form" enctype="multipart/form-data">
                                        <input type="hidden" name="FUNCTION_NAME" value="saveLocationData">
                                        <input type="hidden" class="PK_LOCATION" name="PK_LOCATION" value="<?= $PK_LOCATION ?>">

                                        <!-- Section: Corporation & Account -->
                                        <div class="section-header">
                                            <i class="fas fa-building"></i>
                                            <span>Corporation &amp; Account</span>
                                        </div>

                                        <div class="form-grid">
                                            <div class="form-group-modern">
                                                <label class="form-label">Corporation <span class="required">*</span></label>
                                                <select class="form-control-modern" name="PK_CORPORATION" id="PK_CORPORATION" required>
                                                    <option value="">Select Corporation</option>
                                                    <?php
                                                    $row = $db->Execute("SELECT PK_CORPORATION, CORPORATION_NAME FROM DOA_CORPORATION WHERE ACTIVE = 1 AND PK_ACCOUNT_MASTER = " . $_SESSION['PK_ACCOUNT_MASTER'] . " ORDER BY PK_CORPORATION");
                                                    while (!$row->EOF) { ?>
                                                        <option value="<?php echo $row->fields['PK_CORPORATION']; ?>" <?= ($row->fields['PK_CORPORATION'] == $PK_CORPORATION) ? "selected" : "" ?>><?= htmlspecialchars($row->fields['CORPORATION_NAME']) ?></option>
                                                    <?php $row->MoveNext();
                                                    } ?>
                                                </select>
                                            </div>

                                            <div class="form-group-modern">
                                                <label class="form-label">Account Type <span class="required">*</span></label>
                                                <div class="radio-group-modern">
                                                    <?php
                                                    $row = $db->Execute("SELECT PK_ACCOUNT_TYPE, ACCOUNT_TYPE FROM DOA_ACCOUNT_TYPE WHERE ACTIVE='1' ORDER BY PK_ACCOUNT_TYPE");
                                                    while (!$row->EOF) {
                                                        $checked = '';
                                                        if ($PK_ACCOUNT_TYPE != '') {
                                                            if ($row->fields['PK_ACCOUNT_TYPE'] == $PK_ACCOUNT_TYPE) $checked = 'checked';
                                                        } else {
                                                            if ($row->fields['PK_ACCOUNT_TYPE'] == 1) $checked = 'checked';
                                                        }
                                                    ?>
                                                        <label class="radio-item">
                                                            <input type="radio" name="PK_ACCOUNT_TYPE"
                                                                id="acct_type_<?= $row->fields['PK_ACCOUNT_TYPE']; ?>"
                                                                value="<?= $row->fields['PK_ACCOUNT_TYPE']; ?>"
                                                                <?= $checked; ?> required>
                                                            <?= htmlspecialchars($row->fields['ACCOUNT_TYPE']) ?>
                                                        </label>
                                                    <?php $row->MoveNext();
                                                    } ?>
                                                </div>
                                            </div>

                                            <div class="form-group-modern">
                                                <label class="form-label">Location Name <span class="required">*</span></label>
                                                <input type="text" id="LOCATION_NAME" name="LOCATION_NAME" class="form-control-modern" placeholder="Enter Location Name" required value="<?php echo htmlspecialchars($LOCATION_NAME) ?>">
                                            </div>

                                            <div class="form-group-modern">
                                                <label class="form-label">Location Code <span class="required">*</span></label>
                                                <input type="text" id="LOCATION_CODE" name="LOCATION_CODE" class="form-control-modern" placeholder="Enter Location Code" required value="<?php echo htmlspecialchars($LOCATION_CODE) ?>">
                                            </div>
                                        </div>

                                        <!-- Section: Address -->
                                        <div class="section-header">
                                            <i class="fas fa-map-pin"></i>
                                            <span>Address</span>
                                        </div>

                                        <div class="form-grid">
                                            <div class="form-group-modern">
                                                <label class="form-label">Address</label>
                                                <input type="text" id="ADDRESS" name="ADDRESS" class="form-control-modern" placeholder="Enter Address" value="<?php echo htmlspecialchars($ADDRESS) ?>">
                                            </div>

                                            <div class="form-group-modern">
                                                <label class="form-label">Apt/Ste</label>
                                                <input type="text" id="ADDRESS_1" name="ADDRESS_1" class="form-control-modern" placeholder="Enter Apartment OR Street" value="<?php echo htmlspecialchars($ADDRESS_1) ?>">
                                            </div>

                                            <div class="form-group-modern">
                                                <label class="form-label">Country <span class="required">*</span></label>
                                                <select class="form-control-modern" name="PK_COUNTRY" id="PK_COUNTRY" onChange="fetch_state(this.value)" required>
                                                    <option value="">Select Country</option>
                                                    <?php
                                                    $row = $db->Execute("SELECT PK_COUNTRY, COUNTRY_NAME FROM DOA_COUNTRY WHERE ACTIVE = 1 ORDER BY PK_COUNTRY");
                                                    while (!$row->EOF) { ?>
                                                        <option value="<?php echo $row->fields['PK_COUNTRY']; ?>" <?= ($row->fields['PK_COUNTRY'] == $PK_COUNTRY) ? "selected" : "" ?>><?= htmlspecialchars($row->fields['COUNTRY_NAME']) ?></option>
                                                    <?php $row->MoveNext();
                                                    } ?>
                                                </select>
                                            </div>

                                            <div class="form-group-modern">
                                                <label class="form-label">State <span class="required">*</span></label>
                                                <div id="State_div">
                                                    <!-- populated via AJAX -->
                                                </div>
                                            </div>

                                            <div class="form-group-modern">
                                                <label class="form-label">City</label>
                                                <input type="text" id="CITY" name="CITY" class="form-control-modern" placeholder="Enter City" value="<?php echo htmlspecialchars($CITY) ?>">
                                            </div>

                                            <div class="form-group-modern">
                                                <label class="form-label">Postal / Zip Code</label>
                                                <input type="text" id="ZIP_CODE" name="ZIP_CODE" class="form-control-modern" placeholder="Enter Postal / Zip Code" value="<?php echo htmlspecialchars($ZIP_CODE) ?>">
                                            </div>

                                            <div class="form-group-modern">
                                                <label class="form-label">Phone</label>
                                                <input type="text" id="PHONE" name="PHONE" class="form-control-modern" placeholder="Enter Phone No." value="<?php echo htmlspecialchars($PHONE) ?>">
                                            </div>

                                            <div class="form-group-modern">
                                                <label class="form-label">Email</label>
                                                <input type="email" id="EMAIL" name="EMAIL" class="form-control-modern" placeholder="Enter Email Address" value="<?php echo htmlspecialchars($EMAIL) ?>">
                                            </div>

                                            <div class="form-group-modern">
                                                <label class="form-label">Location Image</label>
                                                <input type="file" name="IMAGE_PATH" id="IMAGE_PATH" class="form-control-modern">
                                            </div>

                                            <div class="form-group-modern">
                                                <label class="form-label">Timezone <span class="required">*</span></label>
                                                <select name="PK_TIMEZONE" id="PK_TIMEZONE" class="form-control-modern" required>
                                                    <option value="">Select Timezone</option>
                                                    <?php $res_type = $db->Execute("SELECT * FROM DOA_TIMEZONE WHERE ACTIVE = 1 ORDER BY NAME ASC");
                                                    while (!$res_type->EOF) { ?>
                                                        <option value="<?= $res_type->fields['PK_TIMEZONE'] ?>" <?= ($res_type->fields['PK_TIMEZONE'] == $PK_TIMEZONE) ? 'selected="selected"' : '' ?>><?= htmlspecialchars($res_type->fields['NAME']) ?></option>
                                                    <?php $res_type->MoveNext();
                                                    } ?>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Section: Operational Settings -->
                                        <div class="section-header">
                                            <i class="fas fa-sliders-h"></i>
                                            <span>Operational Settings</span>
                                        </div>

                                        <div class="form-grid">
                                            <div class="form-group-modern">
                                                <label class="form-label">Time Interval for Calendar Rows <span class="required">*</span></label>
                                                <select name="TIME_SLOT_INTERVAL" id="TIME_SLOT_INTERVAL" class="form-control-modern" required>
                                                    <option value="">Select</option>
                                                    <?php for ($i = 5; $i <= 60; $i += 5) { ?>
                                                        <option value="<?= '00:' . $i . ':00' ?>" <?= ($TIME_SLOT_INTERVAL == '00:' . $i . ':00') ? 'selected' : '' ?>><?= '00:' . $i . ':00' ?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>

                                            <div class="form-group-modern">
                                                <label class="form-label">Title for Service Provider</label>
                                                <input type="text" id="SERVICE_PROVIDER_TITLE" name="SERVICE_PROVIDER_TITLE" class="form-control-modern" placeholder="Enter Service Provider Title" value="<?php echo htmlspecialchars($SERVICE_PROVIDER_TITLE) ?>">
                                            </div>

                                            <div class="form-group-modern">
                                                <label class="form-label">Name of the Tab for Charging Services</label>
                                                <input type="text" id="OPERATION_TAB_TITLE" name="OPERATION_TAB_TITLE" class="form-control-modern" placeholder="Enter Operation Tab Title" value="<?php echo htmlspecialchars($OPERATION_TAB_TITLE) ?>">
                                            </div>

                                            <div class="form-group-modern">
                                                <label class="form-label">Enrollment Prefix</label>
                                                <input type="text" id="ENROLLMENT_ID_CHAR" name="ENROLLMENT_ID_CHAR" class="form-control-modern" placeholder="Enrollment Id Character" value="<?php echo htmlspecialchars($ENROLLMENT_ID_CHAR) ?>">
                                            </div>

                                            <div class="form-group-modern">
                                                <label class="form-label">Starting Enrollment Number</label>
                                                <input type="number" id="ENROLLMENT_ID_NUM" name="ENROLLMENT_ID_NUM" class="form-control-modern" placeholder="Enrollment Id Number" value="<?php echo htmlspecialchars($ENROLLMENT_ID_NUM) ?>">
                                            </div>

                                            <div class="form-group-modern">
                                                <label class="form-label">Misc Enrollment Prefix</label>
                                                <input type="text" id="MISCELLANEOUS_ID_CHAR" name="MISCELLANEOUS_ID_CHAR" class="form-control-modern" placeholder="Miscellaneous Id Character" value="<?php echo htmlspecialchars($MISCELLANEOUS_ID_CHAR) ?>">
                                            </div>

                                            <div class="form-group-modern">
                                                <label class="form-label">Starting Misc Enrollment Number</label>
                                                <input type="number" id="MISCELLANEOUS_ID_NUM" name="MISCELLANEOUS_ID_NUM" class="form-control-modern" placeholder="Miscellaneous Id Number" value="<?php echo htmlspecialchars($MISCELLANEOUS_ID_NUM) ?>">
                                            </div>

                                            <div class="form-group-modern">
                                                <label class="form-label">Royalty Percentage</label>
                                                <div class="input-group-modern">
                                                    <input type="text" name="ROYALTY_PERCENTAGE" id="ROYALTY_PERCENTAGE" class="form-control-modern" value="<?php echo htmlspecialchars($ROYALTY_PERCENTAGE) ?>">
                                                    <span class="input-suffix">%</span>
                                                </div>
                                            </div>

                                            <div class="form-group-modern">
                                                <label class="form-label">Sales Tax</label>
                                                <div class="input-group-modern">
                                                    <input type="text" name="SALES_TAX" id="SALES_TAX" class="form-control-modern" value="<?php echo htmlspecialchars($SALES_TAX) ?>">
                                                    <span class="input-suffix">%</span>
                                                </div>
                                            </div>

                                            <div class="form-group-modern">
                                                <label class="form-label">Receipt Prefix <span class="required">*</span></label>
                                                <input type="text" id="RECEIPT_CHARACTER" name="RECEIPT_CHARACTER" class="form-control-modern" placeholder="Receipt Character" required value="<?= htmlspecialchars($RECEIPT_CHARACTER) ?>">
                                            </div>

                                            <div class="form-group-modern">
                                                <label class="form-label">Focusbiz API Key</label>
                                                <input type="text" id="FOCUSBIZ_API_KEY" name="FOCUSBIZ_API_KEY" class="form-control-modern" placeholder="Enter Focusbiz API Key" value="<?php echo htmlspecialchars($FOCUSBIZ_API_KEY) ?>">
                                            </div>
                                        </div>

                                        <!-- Section: Appointment Reminder -->
                                        <div class="section-header">
                                            <i class="fas fa-bell"></i>
                                            <span>Appointment Reminder</span>
                                        </div>

                                        <div class="form-grid">
                                            <div class="form-group-modern">
                                                <label class="form-label">Send an Appointment Reminder Text message.</label>
                                                <div class="radio-group-modern">
                                                    <label class="radio-item">
                                                        <input type="radio" id="APPOINTMENT_REMINDER_YES" name="APPOINTMENT_REMINDER" value="1" <?= ($APPOINTMENT_REMINDER == '1') ? 'checked' : '' ?> onclick="showHourBox(this);">
                                                        Yes
                                                    </label>
                                                    <label class="radio-item">
                                                        <input type="radio" id="APPOINTMENT_REMINDER_NO" name="APPOINTMENT_REMINDER" value="0" <?= ($APPOINTMENT_REMINDER == '0') ? 'checked' : '' ?> onclick="showHourBox(this);">
                                                        No
                                                    </label>
                                                </div>
                                            </div>

                                            <div class="form-group-modern hour_box" id="yes" style="display: <?= ($APPOINTMENT_REMINDER == '1') ? 'flex' : 'none' ?>;">
                                                <label class="form-label">How many hours before the appointment?</label>
                                                <input type="text" class="form-control-modern" name="HOUR" value="<?= htmlspecialchars($HOUR) ?>">
                                            </div>
                                        </div>

                                        <!-- Section: Texting -->
                                        <div class="section-header">
                                            <i class="fas fa-sms"></i>
                                            <span>Texting Feature</span>
                                        </div>

                                        <div class="form-grid">
                                            <div class="form-group-modern">
                                                <label class="form-label">Texting Feature Enabled?</label>
                                                <div class="radio-group-modern">
                                                    <label class="radio-item">
                                                        <input type="radio" name="TEXTING_FEATURE_ENABLED" id="TEXTING_FEATURE_ENABLED_YES" value="1" <?= ($TEXTING_FEATURE_ENABLED == 1) ? 'checked' : '' ?> onclick="showTwilioAccountSetting(this);">
                                                        Yes
                                                    </label>
                                                    <label class="radio-item">
                                                        <input type="radio" name="TEXTING_FEATURE_ENABLED" id="TEXTING_FEATURE_ENABLED_NO" value="0" <?= ($TEXTING_FEATURE_ENABLED == 0) ? 'checked' : '' ?> onclick="showTwilioAccountSetting(this);">
                                                        No
                                                    </label>
                                                </div>
                                            </div>

                                            <div class="form-group-modern twilio_account_type" id="twilio_account_type" style="display: <?= ($TEXTING_FEATURE_ENABLED == '1') ? 'flex' : 'none' ?>;">
                                                <label class="form-label">Twilio Account Type</label>
                                                <div class="radio-group-modern">
                                                    <label class="radio-item">
                                                        <input type="radio" name="TWILIO_ACCOUNT_TYPE" id="TWILIO_ACCOUNT_TYPE_0" value="0" <?= ($TWILIO_ACCOUNT_TYPE == 0) ? 'checked' : '' ?> onclick="showTwilioSetting(this);">
                                                        Using Doable's Twilio account
                                                    </label>
                                                    <label class="radio-item">
                                                        <input type="radio" name="TWILIO_ACCOUNT_TYPE" id="TWILIO_ACCOUNT_TYPE_1" value="1" <?= ($TWILIO_ACCOUNT_TYPE == 1) ? 'checked' : '' ?> onclick="showTwilioSetting(this);">
                                                        Using Your own Twilio Account
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <div id="twilio_setting_div" class="form-grid" style="display: <?= ($TEXTING_FEATURE_ENABLED == 1 && $TWILIO_ACCOUNT_TYPE == 1) ? 'grid' : 'none' ?>; margin-top: 16px;">
                                            <div class="form-group-modern">
                                                <label class="form-label">SID</label>
                                                <input type="text" id="SID" name="SID" class="form-control-modern" placeholder="Enter SID" value="<?php echo htmlspecialchars($SID) ?>">
                                            </div>
                                            <div class="form-group-modern">
                                                <label class="form-label">Token</label>
                                                <input type="text" id="TOKEN" name="TOKEN" class="form-control-modern" placeholder="Enter TOKEN" value="<?php echo htmlspecialchars($TOKEN) ?>">
                                            </div>
                                            <div class="form-group-modern">
                                                <label class="form-label">Phone No.</label>
                                                <input type="text" id="TWILIO_PHONE_NO" name="TWILIO_PHONE_NO" class="form-control-modern" placeholder="Enter Phone No." value="<?php echo htmlspecialchars($TWILIO_PHONE_NO) ?>">
                                            </div>
                                        </div>

                                        <?php if ($IMAGE_PATH != '') { ?>
                                            <div style="width: 120px; height: 120px; margin-top: 25px;">
                                                <a class="fancybox" href="<?php echo $IMAGE_PATH; ?>" data-fancybox-group="gallery">
                                                    <img src="<?php echo $IMAGE_PATH; ?>" style="width:120px; height:120px; border-radius: var(--radius-sm); object-fit: cover;" />
                                                </a>
                                            </div>
                                        <?php } ?>

                                        <!-- Section: Payment Gateway -->
                                        <?php if ($ABLE_TO_EDIT_PAYMENT_GATEWAY == 1) { ?>
                                            <div class="section-header">
                                                <i class="fas fa-credit-card"></i>
                                                <span>Electronic Connection to Merchant Service</span>
                                            </div>

                                            <div class="form-grid">
                                                <div class="form-group-modern full-width">
                                                    <label class="form-label">Payment Gateway</label>
                                                    <div class="radio-group-modern">
                                                        <label class="radio-item">
                                                            <input type="radio" id="PAYMENT_GATEWAY_TYPE_STRIPE" name="PAYMENT_GATEWAY_TYPE" value="Stripe" <?= ($PAYMENT_GATEWAY_TYPE == 'Stripe') ? 'checked' : '' ?> onclick="showPaymentGateway(this);">
                                                            Stripe
                                                        </label>
                                                        <label class="radio-item">
                                                            <input type="radio" id="PAYMENT_GATEWAY_TYPE_SQUARE" name="PAYMENT_GATEWAY_TYPE" value="Square" <?= ($PAYMENT_GATEWAY_TYPE == 'Square') ? 'checked' : '' ?> onclick="showPaymentGateway(this);">
                                                            Square
                                                        </label>
                                                        <label class="radio-item">
                                                            <input type="radio" id="PAYMENT_GATEWAY_TYPE_AUTH" name="PAYMENT_GATEWAY_TYPE" value="Authorized.net" <?= ($PAYMENT_GATEWAY_TYPE == 'Authorized.net') ? 'checked' : '' ?> onclick="showPaymentGateway(this);">
                                                            Authorized.net
                                                        </label>
                                                    </div>
                                                </div>

                                                <div class="form-group-modern full-width payment_gateway" id="stripe" style="display: <?= ($PAYMENT_GATEWAY_TYPE == 'Stripe') ? 'grid' : 'none' ?>;">
                                                    <div class="form-grid" style="width: 100%;">
                                                        <div class="form-group-modern">
                                                            <label class="form-label">Secret Key</label>
                                                            <input type="text" class="form-control-modern" name="SECRET_KEY" value="<?= htmlspecialchars($SECRET_KEY) ?>">
                                                        </div>
                                                        <div class="form-group-modern">
                                                            <label class="form-label">Publishable Key</label>
                                                            <input type="text" class="form-control-modern" name="PUBLISHABLE_KEY" value="<?= htmlspecialchars($PUBLISHABLE_KEY) ?>">
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="form-group-modern full-width payment_gateway" id="square" style="display: <?= ($PAYMENT_GATEWAY_TYPE == 'Square') ? 'grid' : 'none' ?>;">
                                                    <div class="form-grid" style="width: 100%;">
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
                                                </div>

                                                <div class="form-group-modern full-width payment_gateway" id="authorized" style="display: <?= ($PAYMENT_GATEWAY_TYPE == 'Authorized.net') ? 'grid' : 'none' ?>;">
                                                    <div class="form-grid" style="width: 100%;">
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
                                                </div>
                                            </div>
                                        <?php } ?>

                                        <!-- Section: Email Connection -->
                                        <div class="section-header">
                                            <i class="fas fa-envelope"></i>
                                            <span>Email Connection</span>
                                        </div>

                                        <div class="form-grid">
                                            <div class="form-group-modern">
                                                <label class="form-label">SMTP HOST</label>
                                                <input type="text" class="form-control-modern" name="SMTP_HOST" value="<?= htmlspecialchars($SMTP_HOST) ?>">
                                            </div>
                                            <div class="form-group-modern">
                                                <label class="form-label">SMTP PORT</label>
                                                <input type="text" class="form-control-modern" name="SMTP_PORT" value="<?= htmlspecialchars($SMTP_PORT) ?>">
                                            </div>
                                            <div class="form-group-modern">
                                                <label class="form-label">SMTP USERNAME</label>
                                                <input type="text" class="form-control-modern" name="SMTP_USERNAME" value="<?= htmlspecialchars($SMTP_USERNAME) ?>">
                                            </div>
                                            <div class="form-group-modern">
                                                <label class="form-label">SMTP PASSWORD</label>
                                                <input type="text" class="form-control-modern" name="SMTP_PASSWORD" value="<?= htmlspecialchars($SMTP_PASSWORD) ?>">
                                            </div>
                                        </div>

                                        <?php if (!empty($_GET['id'])) { ?>
                                            <div class="section-header">
                                                <i class="fas fa-toggle-on"></i>
                                                <span>Status</span>
                                            </div>
                                            <div class="form-group-modern">
                                                <label class="form-label">Active</label>
                                                <div class="radio-group-modern">
                                                    <label class="radio-item">
                                                        <input type="radio" name="ACTIVE" id="ACTIVE_YES" value="1" <?= ($ACTIVE == 1) ? 'checked="checked"' : '' ?>>
                                                        Yes
                                                    </label>
                                                    <label class="radio-item">
                                                        <input type="radio" name="ACTIVE" id="ACTIVE_NO" value="0" <?= ($ACTIVE == 0) ? 'checked="checked"' : '' ?>>
                                                        No
                                                    </label>
                                                </div>
                                            </div>
                                        <?php } ?>

                                        <div class="form-actions">
                                            <button type="submit" class="btn-modern btn-modern-primary">
                                                <i class="fas fa-arrow-right"></i> Next
                                            </button>
                                            <button type="button" class="btn-modern btn-modern-secondary" onclick="window.location.href='all_locations.php'">
                                                <i class="fas fa-times"></i> Cancel
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- ================= OPERATIONAL HOURS TAB ================= -->
                            <div class="tab-pane fade" id="operational_hours" role="tabpanel">
                                <div class="card-body">
                                    <form class="form-material form-horizontal" action="" method="post" enctype="multipart/form-data">
                                        <input type="hidden" name="FUNCTION_NAME" value="saveOperationalHours">
                                        <input type="hidden" class="PK_LOCATION" name="PK_LOCATION" value="<?= $PK_LOCATION ?>">

                                        <div class="section-header" style="margin-top: 0;">
                                            <i class="fas fa-clock"></i>
                                            <span>Weekly Schedule</span>
                                        </div>

                                        <div id="holiday_list_div">
                                            <div class="hours-header">
                                                <div class="hours-col">Day</div>
                                                <div class="hours-col">Open Time</div>
                                                <div class="hours-col">Close Time</div>
                                                <div class="hours-col" style="text-align: right;">
                                                    <label class="checkbox-item" style="justify-content: flex-end;">
                                                        <input type="checkbox" name="ALL_DAYS" onclick="applyToAllDays(this)">
                                                        All Days
                                                    </label>
                                                </div>
                                            </div>

                                            <?php
                                            $operational_hours = $db_account->Execute("SELECT * FROM DOA_OPERATIONAL_HOUR WHERE `PK_LOCATION` = '$PK_LOCATION'");
                                            if ($operational_hours->RecordCount() > 0) {
                                                $i = 0;
                                                $day_names = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                                while (!$operational_hours->EOF) { ?>
                                                    <div class="hours-row">
                                                        <div class="day-label"><?= $day_names[$operational_hours->fields['DAY_NUMBER'] - 1] ?></div>
                                                        <div>
                                                            <input type="text" name="OPEN_TIME[]" class="form-control-modern time-input time-picker OPEN_TIME" value="<?= ($operational_hours->fields['OPEN_TIME'] == '00:00:00') ? '' : date('h:i A', strtotime($operational_hours->fields['OPEN_TIME'])) ?>" style="pointer-events: <?= ($operational_hours->fields['CLOSED'] == 1) ? 'none' : '' ?>" readonly>
                                                        </div>
                                                        <div>
                                                            <input type="text" name="CLOSE_TIME[]" class="form-control-modern time-input time-picker CLOSE_TIME" value="<?= ($operational_hours->fields['CLOSE_TIME'] == '00:00:00') ? '' : date('h:i A', strtotime($operational_hours->fields['CLOSE_TIME'])) ?>" style="pointer-events: <?= ($operational_hours->fields['CLOSED'] == 1) ? 'none' : '' ?>" readonly>
                                                        </div>
                                                        <div>
                                                            <label class="checkbox-item">
                                                                <input type="checkbox" name="CLOSED_<?= $i ?>" onchange="closeThisDay(this)" <?= ($operational_hours->fields['CLOSED'] == 1) ? 'checked' : '' ?>>
                                                                Closed
                                                            </label>
                                                        </div>
                                                    </div>
                                                <?php $operational_hours->MoveNext();
                                                    $i++;
                                                }
                                            } else {
                                                $day_names = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                                for ($i = 0; $i < 7; $i++) { ?>
                                                    <div class="hours-row">
                                                        <div class="day-label"><?= $day_names[$i] ?></div>
                                                        <div>
                                                            <input type="text" name="OPEN_TIME[]" class="form-control-modern time-input time-picker OPEN_TIME" readonly>
                                                        </div>
                                                        <div>
                                                            <input type="text" name="CLOSE_TIME[]" class="form-control-modern time-input time-picker CLOSE_TIME" readonly>
                                                        </div>
                                                        <div>
                                                            <label class="checkbox-item">
                                                                <input type="checkbox" name="CLOSED_<?= $i ?>" onchange="closeThisDay(this)">
                                                                Closed
                                                            </label>
                                                        </div>
                                                    </div>
                                            <?php }
                                            } ?>
                                        </div>

                                        <div class="form-actions">
                                            <button type="submit" class="btn-modern btn-modern-primary">
                                                <i class="fas fa-save"></i> Save
                                            </button>
                                            <button type="button" class="btn-modern btn-modern-secondary" onclick="window.location.href='all_locations.php'">
                                                <i class="fas fa-times"></i> Cancel
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- ================= CREDIT CARD TAB ================= -->
                            <div class="tab-pane fade" id="credit_card" role="tabpanel">
                                <div class="card-body">
                                    <form class="form-material form-horizontal" id="creditCardForm" action="" method="post" enctype="multipart/form-data">
                                        <input type="hidden" name="FUNCTION_NAME" value="saveCreditCard">

                                        <div class="section-header" style="margin-top: 0;">
                                            <i class="fas fa-credit-card"></i>
                                            <span>Card Details</span>
                                        </div>

                                        <div id="credit_card_div">
                                            <div class="form-grid">
                                                <div class="form-group-modern full-width">
                                                    <div id="card-element"></div>
                                                    <div id="card-errors" style="color: var(--danger-color); font-size: 13px; margin-top: 6px;"></div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-actions">
                                            <button type="submit" class="btn-modern btn-modern-primary">
                                                <i class="fas fa-save"></i> Save
                                            </button>
                                            <button type="button" class="btn-modern btn-modern-secondary" onclick="window.location.href='all_locations.php'">
                                                <i class="fas fa-times"></i> Cancel
                                            </button>
                                        </div>
                                    </form>

                                    <?php if (isset($card_details['last4'])) {
                                        switch ($card_details['brand']) {
                                            case 'Visa':
                                            case 'Visa (debit)':
                                                $card_type = 'visa';
                                                break;
                                            case 'MasterCard':
                                            case 'Mastercard (2-series)':
                                            case 'Mastercard (debit)':
                                            case 'Mastercard (prepaid)':
                                                $card_type = 'mastercard';
                                                break;
                                            case 'American Express':
                                                $card_type = 'amex';
                                                break;
                                            case 'Discover':
                                            case 'Discover (debit)':
                                                $card_type = 'discover';
                                                break;
                                            default:
                                                $card_type = '';
                                                break;
                                        } ?>
                                        <div class="section-header">
                                            <i class="fas fa-check-circle"></i>
                                            <span>Saved Card</span>
                                        </div>
                                        <div class="credit-card <?= $card_type ?>">
                                            <div class="credit-card-last4">
                                                •••• <?= htmlspecialchars($card_details['last4']) ?>
                                            </div>
                                            <div class="credit-card-expiry">
                                                Expires <?= $card_details['exp_month'] . '/' . $card_details['exp_year'] ?>
                                            </div>
                                        </div>
                                    <?php } ?>
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
                            <h5><?= htmlspecialchars($help_title) ?></h5>
                            <p><?= htmlspecialchars($help_description) ?></p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <?php require_once('../includes/footer.php'); ?>

    <script src="https://js.stripe.com/v3/"></script>
    <script>
        $('.time-picker').timepicker({
            timeFormat: 'hh:mm p',
            interval: 30,
            dynamic: false,
            dropdown: true,
            scrollbar: true
        });

        function closeThisDay(param) {
            if ($(param).is(':checked')) {
                $(param).closest('.hours-row').find('.time-input').val('');
                $(param).closest('.hours-row').find('.time-input').css('pointer-events', 'none');
            } else {
                $(param).closest('.hours-row').find('.time-input').css('pointer-events', '');
            }
        }

        $(document).ready(function() {
            fetch_state(<?php echo $PK_COUNTRY; ?>);
        });

        function fetch_state(PK_COUNTRY) {
            var data = "PK_COUNTRY=" + PK_COUNTRY + "&PK_STATES=<?= $PK_STATES; ?>";
            $.ajax({
                url: "ajax/state.php",
                type: "POST",
                data: data,
                async: false,
                cache: false,
                success: function(result) {
                    document.getElementById('State_div').innerHTML = result;
                }
            });
        }

        function showPaymentGateway(param) {
            $('.payment_gateway').slideUp();
            if ($(param).val() === 'Stripe') {
                $('#stripe').slideDown();
            } else if ($(param).val() === 'Square') {
                $('#square').slideDown();
            } else if ($(param).val() === 'Authorized.net') {
                $('#authorized').slideDown();
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

        function showHourBox(radio) {
            if (radio.value == 1) {
                document.getElementById("yes").style.display = "flex";
            } else {
                document.getElementById("yes").style.display = "none";
            }
        }

        function applyToAllDays(param) {
            if ($(param).is(':checked')) {
                let OPEN_TIME = $(".OPEN_TIME");
                $('.OPEN_TIME').val($(OPEN_TIME[0]).val());

                let CLOSE_TIME = $(".CLOSE_TIME");
                $('.CLOSE_TIME').val($(CLOSE_TIME[0]).val());
            } else {
                let OPEN_TIME = $(".OPEN_TIME");
                for (let i = 1; i < 7; i++) {
                    $(OPEN_TIME[i]).val('');
                }

                let CLOSE_TIME = $(".CLOSE_TIME");
                for (let i = 1; i < 7; i++) {
                    $(CLOSE_TIME[i]).val('');
                }
            }
        }

        function stripePaymentFunction() {
            let stripe = Stripe('<?= $STRIPE_PUBLISHABLE_KEY ?>');
            let elements = stripe.elements();

            let style = {
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

            let card = elements.create('card', {
                style: style
            });

            if (($('#card-element')).length > 0) {
                card.mount('#card-element');
            }

            card.addEventListener('change', function(event) {
                let displayError = document.getElementById('card-errors');
                if (event.error) {
                    displayError.textContent = event.error.message;
                } else {
                    displayError.textContent = '';
                }
            });

            let form = document.getElementById('creditCardForm');
            // avoid duplicate listeners
            if (!form.dataset.stripeBound) {
                form.dataset.stripeBound = '1';
                form.addEventListener('submit', function(event) {
                    event.preventDefault();
                    stripe.createToken(card).then(function(result) {
                        if (result.error) {
                            let errorElement = document.getElementById('card-errors');
                            errorElement.textContent = result.error.message;
                        } else {
                            stripeTokenHandler(result.token);
                        }
                    });
                });
            }

            function stripeTokenHandler(token) {
                let form = document.getElementById('creditCardForm');
                let hiddenInput = document.createElement('input');
                hiddenInput.setAttribute('type', 'hidden');
                hiddenInput.setAttribute('name', 'token');
                hiddenInput.setAttribute('value', token.id);
                form.appendChild(hiddenInput);
                form.submit();
            }
        }

        $(document).on('submit', '#location_form', function(event) {
            event.preventDefault();
            let form_data = new FormData($('#location_form')[0]);
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                data: form_data,
                processData: false,
                contentType: false,
                dataType: 'JSON',
                success: function(data) {
                    console.log(data);
                    if (data.success) {
                        $('.PK_LOCATION').val(data.PK_LOCATION);
                        $('#operational_hours_link')[0].click();
                    } else {
                        alert(data.message);
                    }
                }
            });
        });
    </script>
</body>

</html>