<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;
global $upload_path;

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4])) {
    header("location:../login.php");
    exit;
}

if (empty($_GET['id']))
    $title = "Add Enrollment";
else
    $title = "Edit Enrollment";

if (!empty($_GET['source']) && $_GET['source'] === 'customer') {
    $header = 'customer.php?id=' . $_GET['id_customer'] . '&master_id=' . $_GET['master_id_customer'] . '&tab=enrollment';
} else {
    $header = '';
}

$PK_ENROLLMENT_MASTER = 0;
$ENROLLMENT_NAME = '';
$ENROLLMENT_DATE = date('m/d/Y');
$PK_ENROLLMENT_TYPE = '';
$PK_LOCATION = '';
$PK_PACKAGE = '';
$TOTAL = '';
$FINAL_AMOUNT = '';
$PK_AGREEMENT_TYPE = '';
$PK_DOCUMENT_LIBRARY = 1;
$AGREEMENT_PDF_LINK = '';
$ENROLLMENT_BY_ID = $_SESSION['PK_USER'];
$ENROLLMENT_BY_PERCENTAGE = '';
$MEMO = '';
$ACTIVE = '';
$ACTIVE_AUTO_PAY = 0;

$PK_ENROLLMENT_BILLING = '';
$BILLING_REF = '';
$BILLING_DATE = '';
$DOWN_PAYMENT = 0.00;
$BALANCE_PAYABLE = 0.00;
$PAYMENT_METHOD = '';
$PAYMENT_TERM = '';
$NUMBER_OF_PAYMENT = '';
$FIRST_DUE_DATE = '';
$INSTALLMENT_AMOUNT = '';

$PK_ENROLLMENT_PAYMENT = '';
$PK_PAYMENT_TYPE = '';
$AMOUNT = '';
$NAME = '';
$CARD_NUMBER = '';
$SECURITY_CODE = '';
$EXPIRY_DATE = '';
$CHECK_NUMBER = '';
$CHECK_DATE = '';
$NOTE = '';
$CHARGE_TYPE = '';

$PK_USER_MASTER = '';
if (!empty($_GET['master_id_customer'])) {
    $PK_USER_MASTER = $_GET['master_id_customer'];
    $user_location = $db->Execute("SELECT `PK_LOCATION` FROM `DOA_USER_LOCATION` INNER JOIN DOA_USER_MASTER ON DOA_USER_MASTER.PK_USER = DOA_USER_LOCATION.PK_USER WHERE DOA_USER_MASTER.PK_USER_MASTER = " . $PK_USER_MASTER);
    if ($user_location->RecordCount() > 0) {
        $PK_LOCATION = $user_location->fields['PK_LOCATION'];
    } else {
        $PK_LOCATION = 0;
    }
}

$months = '';
$day = '';
if (!empty($_GET['id'])) {
    $res = $db_account->Execute("SELECT * FROM `DOA_ENROLLMENT_MASTER` WHERE `PK_ENROLLMENT_MASTER` = '$_GET[id]'");
    if ($res->RecordCount() == 0) {
        header("location:all_enrollments.php");
        exit;
    }
    $PK_ENROLLMENT_MASTER = $_GET['id'];
    $PK_USER_MASTER = $res->fields['PK_USER_MASTER'];
    $ENROLLMENT_NAME = $res->fields['ENROLLMENT_NAME'];
    $ENROLLMENT_DATE = date('m/d/Y', strtotime($res->fields['ENROLLMENT_DATE']));
    $PK_ENROLLMENT_TYPE = $res->fields['PK_ENROLLMENT_TYPE'];
    $PK_LOCATION = $res->fields['PK_LOCATION'];
    $PK_PACKAGE = $res->fields['PK_PACKAGE'];
    $CHARGE_TYPE = $res->fields['CHARGE_TYPE'];
    $EXPIRY_DATE = new DateTime($res->fields['EXPIRY_DATE']);
    $PK_AGREEMENT_TYPE = $res->fields['PK_AGREEMENT_TYPE'];
    $PK_DOCUMENT_LIBRARY = is_null($res->fields['PK_DOCUMENT_LIBRARY']) ? 1 : $res->fields['PK_DOCUMENT_LIBRARY'];
    $AGREEMENT_PDF_LINK = $res->fields['AGREEMENT_PDF_LINK'];
    $ENROLLMENT_BY_ID = $res->fields['ENROLLMENT_BY_ID'];
    $ENROLLMENT_BY_PERCENTAGE = $res->fields['ENROLLMENT_BY_PERCENTAGE'];
    $MEMO = $res->fields['MEMO'];
    $ACTIVE = $res->fields['ACTIVE'];
    $ACTIVE_AUTO_PAY = $res->fields['ACTIVE_AUTO_PAY'];

    $CREATED_ON = new DateTime($res->fields['CREATED_ON']);
    $interval = $EXPIRY_DATE->diff($CREATED_ON);
    $months = intval($interval->days / 30);

    $day = $EXPIRY_DATE->format('d');

    $billing_data = $db_account->Execute("SELECT * FROM `DOA_ENROLLMENT_BILLING` WHERE `PK_ENROLLMENT_MASTER` = '$_GET[id]'");
    if ($billing_data->RecordCount() > 0) {
        $PK_ENROLLMENT_BILLING = $billing_data->fields['PK_ENROLLMENT_BILLING'];
        $BILLING_REF = $billing_data->fields['BILLING_REF'];
        $BILLING_DATE = $billing_data->fields['BILLING_DATE'];
        $DOWN_PAYMENT = $billing_data->fields['DOWN_PAYMENT'];
        $BALANCE_PAYABLE = $billing_data->fields['BALANCE_PAYABLE'];
        $PAYMENT_METHOD = $billing_data->fields['PAYMENT_METHOD'];
        $PAYMENT_TERM = $billing_data->fields['PAYMENT_TERM'];
        $NUMBER_OF_PAYMENT = $billing_data->fields['NUMBER_OF_PAYMENT'];
        $FIRST_DUE_DATE = $billing_data->fields['FIRST_DUE_DATE'];
        $INSTALLMENT_AMOUNT = $billing_data->fields['INSTALLMENT_AMOUNT'];
    }

    $payment_data = $db_account->Execute("SELECT * FROM `DOA_ENROLLMENT_PAYMENT` WHERE `PK_ENROLLMENT_MASTER` = '$_GET[id]'");
    if ($payment_data->RecordCount() > 0) {
        $PK_ENROLLMENT_PAYMENT = $payment_data->fields['PK_ENROLLMENT_PAYMENT'];
        $PK_PAYMENT_TYPE = $payment_data->fields['PK_PAYMENT_TYPE'];
        $AMOUNT = $payment_data->fields['AMOUNT'];
        $NOTE = $payment_data->fields['NOTE'];
    }
}

$user_payment_gateway = $db->Execute("SELECT DOA_USER_MASTER.PK_USER_MASTER, DOA_LOCATION.PAYMENT_GATEWAY_TYPE, DOA_LOCATION.SECRET_KEY, DOA_LOCATION.PUBLISHABLE_KEY, DOA_LOCATION.ACCESS_TOKEN, DOA_LOCATION.APP_ID, DOA_LOCATION.LOCATION_ID, DOA_LOCATION.LOGIN_ID, DOA_LOCATION.TRANSACTION_KEY, DOA_LOCATION.AUTHORIZE_CLIENT_KEY FROM DOA_LOCATION INNER JOIN DOA_USER_MASTER ON DOA_LOCATION.PK_LOCATION = DOA_USER_MASTER.PRIMARY_LOCATION_ID WHERE DOA_USER_MASTER.PK_USER_MASTER = '$PK_USER_MASTER'");
if ($user_payment_gateway->RecordCount() > 0) {
    $PAYMENT_GATEWAY = $user_payment_gateway->fields['PAYMENT_GATEWAY_TYPE'];
    $SQUARE_APP_ID = $user_payment_gateway->fields['APP_ID'];
    $SQUARE_LOCATION_ID = $user_payment_gateway->fields['LOCATION_ID'];
    $ACCESS_TOKEN = $user_payment_gateway->fields['ACCESS_TOKEN'];
    $PUBLISHABLE_KEY = $user_payment_gateway->fields['PUBLISHABLE_KEY'];
    $SECRET_KEY = $user_payment_gateway->fields['SECRET_KEY'];
    $LOGIN_ID = $user_payment_gateway->fields['LOGIN_ID'];
    $TRANSACTION_KEY = $user_payment_gateway->fields['TRANSACTION_KEY'];
    $AUTHORIZE_CLIENT_KEY = $user_payment_gateway->fields['AUTHORIZE_CLIENT_KEY'];
} else {
    $account_data = $db->Execute("SELECT * FROM `DOA_ACCOUNT_MASTER` WHERE `PK_ACCOUNT_MASTER` = '$_SESSION[PK_ACCOUNT_MASTER]'");
    $PAYMENT_GATEWAY = $account_data->fields['PAYMENT_GATEWAY_TYPE'];
    $SQUARE_APP_ID             = $account_data->fields['APP_ID'];
    $SQUARE_LOCATION_ID     = $account_data->fields['LOCATION_ID'];
    $ACCESS_TOKEN             = $account_data->fields['ACCESS_TOKEN'];
    $PUBLISHABLE_KEY = $account_data->fields['PUBLISHABLE_KEY'];
    $SECRET_KEY = $account_data->fields['SECRET_KEY'];
    $LOGIN_ID = $account_data->fields['LOGIN_ID'];
    $TRANSACTION_KEY = $account_data->fields['TRANSACTION_KEY'];
    $AUTHORIZE_CLIENT_KEY = $account_data->fields['AUTHORIZE_CLIENT_KEY'];
}

$payment_gateway_data = getPaymentGatewayData();

$PAYMENT_GATEWAY = $payment_gateway_data->fields['PAYMENT_GATEWAY_TYPE'];
$GATEWAY_MODE  = $payment_gateway_data->fields['GATEWAY_MODE'];

$SECRET_KEY = $payment_gateway_data->fields['SECRET_KEY'];
$PUBLISHABLE_KEY = $payment_gateway_data->fields['PUBLISHABLE_KEY'];

$SQUARE_ACCESS_TOKEN = $payment_gateway_data->fields['ACCESS_TOKEN'];
$SQUARE_APP_ID = $payment_gateway_data->fields['APP_ID'];
$SQUARE_LOCATION_ID = $payment_gateway_data->fields['LOCATION_ID'];

$AUTHORIZE_LOGIN_ID         = $payment_gateway_data->fields['LOGIN_ID'];
$AUTHORIZE_TRANSACTION_KEY     = $payment_gateway_data->fields['TRANSACTION_KEY'];
$AUTHORIZE_CLIENT_KEY         = $payment_gateway_data->fields['AUTHORIZE_CLIENT_KEY'];

$MERCHANT_ID            = $payment_gateway_data->fields['MERCHANT_ID'];
$API_KEY                = $payment_gateway_data->fields['API_KEY'];
$PUBLIC_API_KEY         = $payment_gateway_data->fields['PUBLIC_API_KEY'];

$service_provider_title = 'Instructor';
$enrollment_type = 'Enrollment';
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'layout/header_script.php'; ?>
<?php include 'layout/header.php'; ?>

<link href="https://fonts.googleapis.com/css2?family=PT+Mono&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/themify-icons/1.0.1/css/themify-icons.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<link href="../assets/sumoselect/sumoselect.min.css" rel="stylesheet" />

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

    /* Breadcrumb - exactly like customer page */
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

    /* Card - matching customer page exactly */
    .card-box {
        border: 1px solid #e0e0e0;
        border-radius: 12px;
        background: #fff;
        margin-bottom: 24px;
        overflow: hidden;
        box-shadow: var(--shadow-sm);
    }

    .card-box .row {
        display: flex;
        margin: 0;
        min-height: 100%;
    }

    .card-header-custom {
        padding: 16px 24px;
        border-bottom: 1px solid var(--gray-200);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
    }

    .card-header-custom h5 {
        font-size: 16px;
        font-weight: 600;
        color: var(--gray-800);
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .card-header-custom h5 i {
        color: var(--primary-color);
    }

    .card-header-custom .status-indicator {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 14px;
        border-radius: 50px;
        font-size: 13px;
        font-weight: 500;
    }

    .card-header-custom .status-indicator.active {
        background: #D1FAE5;
        color: #065F46;
    }

    .card-header-custom .status-indicator.inactive {
        background: #FEE2E2;
        color: #991B1B;
    }

    .card-header-custom .status-indicator i {
        font-size: 8px;
        margin-right: 0;
    }

    /* Sidebar - EXACTLY matching customer page */
    .left-tabs {
        min-width: 200px;
        border-right: 1px solid var(--gray-200);
        border-radius: 12px 0 0 12px;
        min-height: 100%;
        height: 100%;
    }

    .sidebar-link {
        color: #6c757d;
        text-decoration: none;
        padding: 10px 20px;
        display: block;
        border-left: 3px solid transparent;
        font-size: 16px;
        transition: all 0.2s ease;
        cursor: pointer;
        background: transparent;
        border-right: none;
        width: 100%;
        text-align: left;
    }

    .sidebar-link i {
        margin-right: 10px;
        width: 18px;
        text-align: center;
    }

    .sidebar-link.active {
        background-color: #f0f4f8;
        color: #39b54a;
        border-left-color: #39b54a;
        font-weight: 600;
    }

    .sidebar-link:hover {
        background-color: #f8f9fa;
        color: #39b54a;
    }

    .right-panel {
        background: #fff;
        border: 1px solid var(--gray-200);
        border-radius: 12px 12px 12px 12px;
        padding: 24px 28px;
        flex: 1;
    }

    @media (max-width: 768px) {
        .left-tabs {
            min-width: auto;
            border-right: none;
            border-bottom: 1px solid var(--gray-200);
            border-radius: 12px 12px 0 0;
            display: flex;
            flex-wrap: wrap;
            padding: 0px 0px;
            gap: 0px;
        }

        .sidebar-link {
            width: auto;
            padding: 8px 16px;
            border-left: none;
            border-bottom: 3px solid transparent;
            border-radius: var(--radius-sm);
            font-size: 13px;
        }

        .sidebar-link.active {
            border-left: none;
            border-bottom-color: #39b54a;
        }

        .right-panel {
            padding: 16px;
            border-radius: 0 0 12px 12px;
        }
    }

    /* Form Styles - matching customer page */
    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 16px 20px;
    }

    @media (max-width: 992px) {
        .form-grid {
            grid-template-columns: 1fr 1fr;
        }
    }

    @media (max-width: 768px) {
        .form-grid {
            grid-template-columns: 1fr;
        }
    }

    .form-group-modern {
        display: flex;
        flex-direction: column;
        gap: 4px;
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

    .form-control-modern {
        width: 100%;
        padding: 8px 12px;
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

    .form-control-modern:read-only {
        background: var(--gray-50);
        cursor: default;
    }

    select.form-control-modern {
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236B7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 12px center;
        padding-right: 36px;
    }

    textarea.form-control-modern {
        min-height: 60px;
        resize: vertical;
    }

    .radio-group-modern {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
        padding-top: 4px;
    }

    .radio-group-modern .radio-item {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 14px;
        color: var(--gray-700);
        cursor: pointer;
    }

    .radio-group-modern .radio-item input[type="radio"] {
        width: 17px;
        height: 17px;
        cursor: pointer;
        flex-shrink: 0;
    }

    .section-title {
        font-size: 15px;
        font-weight: 600;
        color: var(--gray-700);
        margin: 12px 0 8px 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .section-title i {
        color: var(--primary-color);
    }

    .btn-modern {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 22px;
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

    .btn-modern-sm {
        padding: 5px 14px;
        font-size: 13px;
    }

    .form-actions {
        display: flex;
        gap: 10px;
        margin-top: 20px;
        padding-top: 16px;
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
    }

    /* Service Table - matching customer page style */
    .service-table-wrapper {
        overflow-x: auto;
        margin-top: 4px;
        border: 1px solid var(--gray-200);
        border-radius: var(--radius-sm);
        padding: 12px 12px 4px 12px;
        background: var(--gray-50);
    }

    .service-table-wrapper .table-header {
        display: grid;
        grid-template-columns: 2fr 1fr 2fr 1fr 1fr 1fr 1fr 1fr 1fr 0.5fr;
        gap: 6px;
        padding: 6px 4px 10px 4px;
        font-weight: 600;
        font-size: 11px;
        color: var(--gray-500);
        text-transform: uppercase;
        letter-spacing: 0.03em;
        border-bottom: 2px solid var(--gray-200);
    }

    .service-table-wrapper .service-row {
        display: grid;
        grid-template-columns: 2fr 1fr 2fr 1fr 1fr 1fr 1fr 1fr 1fr 0.5fr;
        gap: 6px;
        padding: 6px 4px;
        border-bottom: 1px solid var(--gray-100);
        align-items: center;
    }

    .service-table-wrapper .service-row:last-child {
        border-bottom: none;
    }

    .service-table-wrapper .service-row .form-control-modern {
        padding: 5px 8px;
        font-size: 13px;
    }

    .service-table-wrapper .service-row .remove-btn {
        color: var(--danger-color);
        font-size: 18px;
        cursor: pointer;
        padding: 4px 8px;
        border: none;
        background: none;
        transition: transform 0.2s;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .service-table-wrapper .service-row .remove-btn:hover {
        transform: scale(1.2);
    }

    @media (max-width: 768px) {
        .service-table-wrapper .table-header {
            display: none;
        }

        .service-table-wrapper .service-row {
            grid-template-columns: 1fr;
            gap: 4px;
            padding: 10px;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-sm);
            margin-bottom: 8px;
            background: #fff;
        }

        .service-table-wrapper .service-row .form-label-sm {
            display: block;
            font-size: 11px;
            font-weight: 600;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.03em;
            margin-bottom: 2px;
        }
    }

    .form-label-sm {
        display: none;
    }

    @media (max-width: 768px) {
        .form-label-sm {
            display: block;
        }
    }

    .total-amount-display {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 12px;
        padding: 6px 0;
    }

    .total-amount-display label {
        font-weight: 600;
        color: var(--gray-700);
        font-size: 14px;
        margin: 0;
    }

    .total-amount-display input {
        width: 150px;
        text-align: right;
        font-weight: 600;
        font-size: 16px;
        color: var(--gray-800);
        background: var(--gray-50);
    }

    .provider-grid {
        display: grid;
        grid-template-columns: 5fr 5fr 1fr;
        gap: 12px;
        align-items: center;
        padding: 6px 0;
    }

    .provider-grid .form-control-modern {
        padding: 6px 10px;
        font-size: 13px;
    }

    .provider-grid .remove-btn {
        color: var(--danger-color);
        font-size: 18px;
        cursor: pointer;
        padding: 4px 8px;
        border: none;
        background: none;
        transition: transform 0.2s;
    }

    .provider-grid .remove-btn:hover {
        transform: scale(1.2);
    }

    @media (max-width: 768px) {
        .provider-grid {
            grid-template-columns: 1fr;
            gap: 6px;
            padding: 10px;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-sm);
            margin-bottom: 6px;
            background: #fff;
        }

        .provider-grid .form-label-sm {
            display: block;
            font-size: 11px;
            font-weight: 600;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.03em;
            margin-bottom: 2px;
        }
    }

    .flexible-payment-row {
        display: grid;
        grid-template-columns: 1fr 1fr auto;
        gap: 12px;
        align-items: center;
        padding: 6px 0;
    }

    @media (max-width: 768px) {
        .flexible-payment-row {
            grid-template-columns: 1fr;
            gap: 6px;
        }
    }

    .table-modern {
        width: 100% !important;
        border-collapse: collapse;
        font-size: 14px;
    }

    .table-modern thead th {
        background: var(--gray-50);
        padding: 10px 14px;
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
        padding: 10px 14px;
        border-bottom: 1px solid var(--gray-100);
        color: var(--gray-700);
        vertical-align: middle;
    }

    .table-modern tbody tr:hover {
        background: var(--gray-50);
    }

    .full-width {
        grid-column: 1 / -1;
    }

    @media (max-width: 768px) {
        .full-width {
            grid-column: 1;
        }
    }

    .disabled_div {
        opacity: 0.6;
        pointer-events: none;
    }

    /* SumoSelect Override */
    .SumoSelect {
        width: 100% !important;
    }

    .SumoSelect>.CaptionCont {
        border: 1.5px solid var(--gray-200) !important;
        border-radius: var(--radius-sm) !important;
        padding: 6px 12px !important;
        min-height: 40px;
        transition: all 0.2s ease;
        background: #fff !important;
    }

    .SumoSelect>.CaptionCont:hover {
        border-color: var(--gray-300) !important;
    }

    .SumoSelect.open>.CaptionCont {
        border-color: var(--primary-color) !important;
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1) !important;
    }

    .SumoSelect>.CaptionCont>span {
        color: var(--gray-700) !important;
        font-size: 14px !important;
    }

    .SumoSelect>.CaptionCont>label>i {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236B7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E") !important;
        background-position: center !important;
        background-repeat: no-repeat !important;
        width: 12px !important;
        height: 12px !important;
    }

    .SumoSelect .optWrapper {
        border: 1.5px solid var(--gray-200) !important;
        border-radius: var(--radius-sm) !important;
        box-shadow: var(--shadow-md) !important;
        z-index: 1000 !important;
    }

    .SumoSelect .optWrapper .options li.opt {
        padding: 6px 12px !important;
        font-size: 14px !important;
        color: var(--gray-700) !important;
        transition: background 0.2s;
    }

    .SumoSelect .optWrapper .options li.opt.selected {
        background: #F0FDF4 !important;
        color: var(--primary-color) !important;
    }

    .SumoSelect .optWrapper .options li.opt.selected::before {
        content: "✓ ";
        color: var(--primary-color);
    }

    .SumoSelect .search input {
        border: 1px solid var(--gray-200) !important;
        border-radius: var(--radius-sm) !important;
        padding: 6px 10px !important;
        font-size: 14px !important;
        outline: none !important;
    }

    .SumoSelect .search input:focus {
        border-color: var(--primary-color) !important;
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1) !important;
    }

    #signature-pad {
        border: 2px solid var(--gray-200);
        border-radius: var(--radius-sm);
        width: 100%;
        height: 200px;
        touch-action: none;
    }

    .tab-content {
        display: none;
    }

    .tab-content.active {
        display: block;
    }

    .tab-pane {
        display: none;
        animation: fadeIn 0.3s ease;
    }

    .tab-pane.active {
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

    .back-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: var(--gray-600);
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        transition: color 0.2s;
        margin-bottom: 16px;
    }

    .back-link:hover {
        color: var(--primary-color);
    }

    .back-link i {
        font-size: 16px;
    }

    /* Form helper */
    .form-helper {
        font-size: 12px;
        color: var(--gray-400);
        margin-top: 4px;
    }

    .form-helper.error {
        color: var(--danger-color);
    }
</style>

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">

        <div class="page-wrapper" style="padding-top: 0px !important;">
            <div class="container-fluid mt-4">

                <!-- Main Card - using customer page structure -->
                <div class="card-box" style="margin-top: 20px;">
                    <a href="all_enrollments.php" class="d-flex mb-2 px-3">
                        <i class="bi bi-chevron-left font-12"></i>
                        <h6 style="margin-top: 2px; margin-left: 10px;">Enrollments</h6>
                    </a>
                    <div class="card-header-custom d-flex justify-content-between align-items-center mb-0 pb-2 border-bottom px-3">
                        <div class="d-flex align-items-center">
                            <h3><i class="bi bi-file-earmark-text me-3" style="color: var(--primary-color);"></i><?= $ENROLLMENT_NAME ?></h3>
                        </div>
                        <?php if (!empty($_GET['id'])): ?>
                            <span class="status-indicator <?= ($ACTIVE == 1) ? 'active' : 'inactive' ?>">
                                <i class="bi bi-circle-fill"></i>
                                <?= ($ACTIVE == 1) ? 'Active' : 'Inactive' ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="row" style="margin: 0;">
                        <!-- Left Sidebar - EXACTLY like customer page -->
                        <div class="col-md-2 border-right-light pt-2" style="padding: 0;">
                            <nav class="flex-column left-tabs">
                                <a class="sidebar-link active" data-toggle-target=".tab-content-1" href="#">
                                    <i class="bi bi-pencil"></i> Enrollment
                                </a>
                                <a class="sidebar-link" data-toggle-target=".tab-content-2" href="javascript:void(0);" onclick="goToPaymentTab()">
                                    <i class="bi bi-receipt"></i> Billing
                                </a>
                                <a class="sidebar-link" data-toggle-target=".tab-content-3" href="javascript:void(0);" onclick="goToLedgerTab()">
                                    <i class="bi bi-book"></i> Ledger
                                </a>
                                <?php if (!empty($_GET['id'])): ?>
                                    <a class="sidebar-link" data-toggle-target=".tab-content-4" href="javascript:void(0);">
                                        <i class="bi bi-clock-history"></i> History
                                    </a>
                                    <?php if ($AGREEMENT_PDF_LINK != '' && $AGREEMENT_PDF_LINK != null): ?>
                                        <a class="sidebar-link" data-toggle-target=".tab-content-5" href="javascript:void(0);">
                                            <i class="bi bi-file-pdf"></i> PDF Agreement
                                        </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </nav>
                        </div>

                        <!-- Right Panel - EXACTLY like customer page -->
                        <div class="col-md-10 right-panel" style="margin: 24px 0px 0px 12px;">

                            <!-- ===== ENROLLMENT TAB ===== -->
                            <div class="tab-content tab-content-1 active">
                                <form class="form-material form-horizontal" id="enrollment_form">
                                    <input type="hidden" name="FUNCTION_NAME" value="saveEnrollmentData">
                                    <input type="hidden" name="PK_ENROLLMENT_MASTER" class="PK_ENROLLMENT_MASTER" value="<?= (empty($_GET['id'])) ? '' : $_GET['id'] ?>">

                                    <div class="form-grid">
                                        <!-- Customer -->
                                        <div class="form-group-modern">
                                            <label class="form-label">Customer <span class="required">*</span></label>
                                            <select class="form-control-modern" required name="PK_USER_MASTER" id="PK_USER_MASTER" onchange="selectThisCustomer(this);">
                                                <option value="">Select Customer</option>
                                                <?php
                                                $row = $db->Execute("SELECT DISTINCT DOA_USERS.PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.PHONE, DOA_USERS.ACTIVE, DOA_USER_MASTER.PK_USER_MASTER, DOA_USER_MASTER.PRIMARY_LOCATION_ID FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER LEFT JOIN DOA_USER_LOCATION ON DOA_USER_LOCATION.PK_USER = DOA_USERS.PK_USER WHERE (DOA_USER_LOCATION.PK_LOCATION IN (" . $DEFAULT_LOCATION_ID . ") OR DOA_USER_MASTER.PRIMARY_LOCATION_ID IN (" . $DEFAULT_LOCATION_ID . ")) AND DOA_USER_ROLES.PK_ROLES = 4 AND DOA_USERS.ACTIVE = 1 AND DOA_USERS.IS_DELETED = 0 AND DOA_USER_MASTER.PK_ACCOUNT_MASTER = " . $_SESSION['PK_ACCOUNT_MASTER'] . " ORDER BY DOA_USERS.FIRST_NAME ASC");
                                                while (!$row->EOF) { ?>
                                                    <option value="<?php echo $row->fields['PK_USER_MASTER']; ?>" data-customer_id="<?= $row->fields['PK_USER_MASTER'] ?>" data-pk_user="<?= $row->fields['PK_USER'] ?>" data-location_id="<?= ((empty($_GET['id'])) ? $row->fields['PRIMARY_LOCATION_ID'] : $PK_LOCATION) ?>" data-customer_name="<?= $row->fields['NAME'] ?>" <?= ($PK_USER_MASTER == $row->fields['PK_USER_MASTER']) ? 'selected' : '' ?>><?= htmlspecialchars($row->fields['NAME'] . ' (' . $row->fields['USER_NAME'] . ')' . ' (' . $row->fields['PHONE'] . ')' . ' (' . $row->fields['EMAIL_ID'] . ')') ?></option>
                                                <?php $row->MoveNext();
                                                } ?>
                                            </select>
                                            <div class="form-helper">Select the customer for this enrollment</div>
                                        </div>

                                        <!-- Location -->
                                        <div class="form-group-modern">
                                            <label class="form-label">Location <span class="required">*</span></label>
                                            <select class="form-control-modern" required name="PK_LOCATION" id="PK_LOCATION" onchange="showEnrollmentInstructor();">
                                                <option value="">Select Location</option>
                                            </select>
                                            <div class="form-helper">Select the location for this enrollment</div>
                                        </div>

                                        <!-- Enrollment Name -->
                                        <div class="form-group-modern">
                                            <label class="form-label">Enrollment Name</label>
                                            <input type="text" id="ENROLLMENT_NAME" name="ENROLLMENT_NAME" class="form-control-modern" placeholder="Enter Enrollment Name" value="<?= htmlspecialchars($ENROLLMENT_NAME) ?>">
                                            <div class="form-helper">Optional name for this enrollment</div>
                                        </div>

                                        <!-- Enrollment Date -->
                                        <div class="form-group-modern">
                                            <label class="form-label">Enrollment Date</label>
                                            <input type="text" id="ENROLLMENT_DATE" name="ENROLLMENT_DATE" class="form-control-modern datepicker-normal" placeholder="Enter Enrollment Date" value="<?= $ENROLLMENT_DATE ?>" required>
                                        </div>

                                        <!-- Enrollment Type -->
                                        <div class="form-group-modern">
                                            <label class="form-label">Enrollment Type</label>
                                            <select class="form-control-modern" name="PK_ENROLLMENT_TYPE" id="PK_ENROLLMENT_TYPE">
                                                <option value="">Select Enrollment Type</option>
                                                <option value="5" <?= ($PK_ENROLLMENT_TYPE == 5) ? 'selected' : '' ?>>1st enrollment</option>
                                                <option value="2" <?= ($PK_ENROLLMENT_TYPE == 2) ? 'selected' : '' ?>>2nd enrollment</option>
                                                <option value="9" <?= ($PK_ENROLLMENT_TYPE == 9) ? 'selected' : '' ?>>3rd enrollment</option>
                                                <option value="13" <?= ($PK_ENROLLMENT_TYPE == 13) ? 'selected' : '' ?>>4+ enrollment</option>
                                            </select>
                                        </div>

                                        <!-- Packages -->
                                        <div class="form-group-modern <?= ($PK_ENROLLMENT_MASTER > 0) ? 'disabled_div' : '' ?>">
                                            <label class="form-label">Packages</label>
                                            <select class="form-control-modern PK_PACKAGE" name="PK_PACKAGE" id="PK_PACKAGE" onchange="selectThisPackage(this)">
                                                <option value="">Select Package</option>
                                                <?php
                                                $row = $db_account->Execute("SELECT * FROM DOA_PACKAGE WHERE PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND ACTIVE = 1 AND IS_DELETED = 0 ORDER BY SORT_ORDER ASC");
                                                while (!$row->EOF) { ?>
                                                    <option value="<?php echo $row->fields['PK_PACKAGE']; ?>" data-expiry_date="<?= $row->fields['EXPIRY_DATE'] ?>" <?= ($row->fields['PK_PACKAGE'] == $PK_PACKAGE) ? 'selected' : '' ?>><?= htmlspecialchars($row->fields['PACKAGE_NAME']) ?></option>
                                                <?php $row->MoveNext();
                                                } ?>
                                            </select>
                                            <div class="form-helper">Select a package for this enrollment</div>
                                        </div>

                                        <!-- Charge Type -->
                                        <?php
                                        $payment_gateway_type = $db->Execute("SELECT PAYMENT_GATEWAY_TYPE FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER=" . $_SESSION['PK_ACCOUNT_MASTER']);
                                        if ($payment_gateway_type->RecordCount() > 0) { ?>
                                            <div class="form-group-modern <?= ($PK_ENROLLMENT_MASTER > 0) ? 'disabled_div' : '' ?>">
                                                <label class="form-label">Charge Type</label>
                                                <div class="radio-group-modern">
                                                    <label class="radio-item">
                                                        <input type="checkbox" id="Session" name="CHARGE_TYPE" class="form-check-inline charge_type" value="Session" <?= ($CHARGE_TYPE == 'Session') ? 'checked' : '' ?> onchange="chargeBySessions(this);">
                                                        Charge by sessions
                                                    </label>
                                                    <label class="radio-item">
                                                        <input type="checkbox" id="Membership" name="CHARGE_TYPE" class="form-check-inline charge_type" value="Membership" <?= ($CHARGE_TYPE == 'Membership') ? 'checked' : '' ?> onchange="chargeBySessions(this);">
                                                        Membership
                                                    </label>
                                                </div>
                                                <div class="form-helper">Select how this enrollment will be charged</div>
                                            </div>
                                        <?php } ?>

                                        <!-- Expiration Date -->
                                        <div class="form-group-modern session_base" style="display: <?php echo ($CHARGE_TYPE != 'Membership') ? 'flex' : 'none' ?>">
                                            <label class="form-label">Expiration Date</label>
                                            <?php if (empty($_GET['id'])) { ?>
                                                <select class="form-control-modern" name="EXPIRY_DATE" id="EXPIRY_DATE" required>
                                                    <option value="">Select Expiration Date</option>
                                                    <option value="1" data-expiry_date="30" <?= ($months == 1) ? 'selected' : '' ?>>30 days</option>
                                                    <option value="2" data-expiry_date="60" <?= ($months == 2) ? 'selected' : '' ?>>60 days</option>
                                                    <option value="3" data-expiry_date="90" <?= ($months == 3) ? 'selected' : '' ?>>90 days</option>
                                                    <option value="6" data-expiry_date="180" <?= ($months == 6) ? 'selected' : '' ?>>180 days</option>
                                                    <option value="12" data-expiry_date="365" <?= ($months == 12) ? 'selected' : '' ?>>365 days</option>
                                                </select>
                                            <?php } else { ?>
                                                <input type="text" class="form-control-modern datepicker-future" name="EXPIRATION_DATE" id="EXPIRATION_DATE" value="<?= date('m/d/Y', strtotime($res->fields['EXPIRY_DATE'])) ?>">
                                            <?php } ?>
                                            <div class="form-helper">When this enrollment expires</div>
                                        </div>

                                        <!-- Auto Renewal -->
                                        <div class="form-group-modern member_base" style="display: <?php echo ($CHARGE_TYPE == 'Membership') ? 'flex' : 'none' ?>">
                                            <label class="form-label">Auto Renewal</label>
                                            <select class="form-control-modern" name="AUTO_RENEWAL" id="AUTO_RENEWAL">
                                                <option value="">Select Auto Renewal</option>
                                                <option value="1" <?= ($day == 1) ? 'selected' : '' ?>>1st of every month</option>
                                                <option value="15" <?= ($day == 15) ? 'selected' : '' ?>>15th of every month</option>
                                                <option value="0" <?= ($day != 1 && $day != 15) ? 'selected' : '' ?>>Same as created date</option>
                                            </select>
                                            <div class="form-helper">Auto renewal setting for membership</div>
                                        </div>
                                    </div>

                                    <!-- Services Section -->
                                    <div class="section-title" style="margin-top: 16px;">
                                        <i class="bi bi-grid-3x3-gap-fill"></i> Services
                                    </div>

                                    <div class="service-table-wrapper">
                                        <div class="table-header">
                                            <span>Services</span>
                                            <span>Service Codes</span>
                                            <span>Service Details</span>
                                            <span>Sessions</span>
                                            <span>Price/Session</span>
                                            <span>Total</span>
                                            <span>Discount Type</span>
                                            <span>Discount</span>
                                            <span>Final Amount</span>
                                            <span></span>
                                        </div>

                                        <div id="append_service_div">
                                            <?php
                                            $total = 0;
                                            if (!empty($_GET['id'])) {
                                                $enrollment_service_data = $db_account->Execute("SELECT * FROM DOA_ENROLLMENT_SERVICE WHERE PK_ENROLLMENT_MASTER = '$_GET[id]'");
                                                while (!$enrollment_service_data->EOF) {
                                                    $total += $enrollment_service_data->fields['FINAL_AMOUNT']; ?>
                                                    <div class="service-row <?= ($PK_ENROLLMENT_MASTER > 0) ? 'disabled_div' : '' ?>">
                                                        <div>
                                                            <span class="form-label-sm">Services</span>
                                                            <select class="form-control-modern PK_SERVICE_MASTER" onchange="selectThisService(this)" required>
                                                                <option value="">Select Service</option>
                                                                <?php
                                                                $row = $db_account->Execute("SELECT DISTINCT DOA_SERVICE_MASTER.PK_SERVICE_MASTER, DOA_SERVICE_MASTER.SERVICE_NAME, DOA_SERVICE_MASTER.DESCRIPTION, DOA_SERVICE_MASTER.ACTIVE FROM `DOA_SERVICE_MASTER` WHERE DOA_SERVICE_MASTER.PK_LOCATION IN (" . $DEFAULT_LOCATION_ID . ") AND ACTIVE = 1 AND IS_DELETED = 0 ORDER BY DOA_SERVICE_MASTER.SERVICE_NAME ASC");
                                                                while (!$row->EOF) { ?>
                                                                    <option value="<?php echo $row->fields['PK_SERVICE_MASTER']; ?>" <?= ($row->fields['PK_SERVICE_MASTER'] == $enrollment_service_data->fields['PK_SERVICE_MASTER']) ? 'selected' : '' ?>><?= htmlspecialchars($row->fields['SERVICE_NAME']) ?></option>
                                                                <?php $row->MoveNext();
                                                                } ?>
                                                            </select>
                                                        </div>
                                                        <div>
                                                            <span class="form-label-sm">Service Codes</span>
                                                            <select class="form-control-modern PK_SERVICE_CODE" onchange="selectThisServiceCode(this)" required>
                                                                <?php
                                                                $row = $db_account->Execute("SELECT * FROM `DOA_SERVICE_CODE` WHERE `PK_SERVICE_MASTER` = " . $enrollment_service_data->fields['PK_SERVICE_MASTER']);
                                                                while (!$row->EOF) { ?>
                                                                    <option value="<?php echo $row->fields['PK_SERVICE_CODE']; ?>" data-details="<?= htmlspecialchars($row->fields['DESCRIPTION']) ?>" data-price="<?= $row->fields['PRICE'] ?>" <?= ($row->fields['PK_SERVICE_CODE'] == $enrollment_service_data->fields['PK_SERVICE_CODE']) ? 'selected' : '' ?>><?= htmlspecialchars($row->fields['SERVICE_CODE']) ?></option>
                                                                <?php $row->MoveNext();
                                                                } ?>
                                                            </select>
                                                        </div>
                                                        <div>
                                                            <span class="form-label-sm">Service Details</span>
                                                            <input type="text" class="form-control-modern SERVICE_DETAILS" value="<?= htmlspecialchars($enrollment_service_data->fields['SERVICE_DETAILS']) ?>">
                                                        </div>
                                                        <div>
                                                            <span class="form-label-sm">Sessions</span>
                                                            <input type="text" class="form-control-modern NUMBER_OF_SESSION" value="<?= $enrollment_service_data->fields['NUMBER_OF_SESSION'] ?>" onkeyup="calculateServiceTotal(this)" required>
                                                        </div>
                                                        <div>
                                                            <span class="form-label-sm">Price/Session</span>
                                                            <input type="text" class="form-control-modern PRICE_PER_SESSION" value="<?= ($enrollment_service_data->fields['TOTAL'] / $enrollment_service_data->fields['NUMBER_OF_SESSION']) ?>" onkeyup="calculateServiceTotal(this)" required>
                                                        </div>
                                                        <div>
                                                            <span class="form-label-sm">Total</span>
                                                            <input type="text" class="form-control-modern TOTAL" value="<?= $enrollment_service_data->fields['TOTAL'] ?>" readonly>
                                                        </div>
                                                        <div>
                                                            <span class="form-label-sm">Discount Type</span>
                                                            <select class="form-control-modern DISCOUNT_TYPE" onchange="calculateServiceTotal(this)">
                                                                <option value="">Select</option>
                                                                <option value="1" <?= ($enrollment_service_data->fields['DISCOUNT_TYPE'] == 1) ? 'selected' : '' ?>>Fixed</option>
                                                                <option value="2" <?= ($enrollment_service_data->fields['DISCOUNT_TYPE'] == 2) ? 'selected' : '' ?>>Percent</option>
                                                            </select>
                                                        </div>
                                                        <div>
                                                            <span class="form-label-sm">Discount</span>
                                                            <input type="text" class="form-control-modern DISCOUNT" value="<?= $enrollment_service_data->fields['DISCOUNT'] ?>" onkeyup="calculateServiceTotal(this)">
                                                        </div>
                                                        <div>
                                                            <span class="form-label-sm">Final Amount</span>
                                                            <input type="text" class="form-control-modern FINAL_AMOUNT" value="<?= $enrollment_service_data->fields['FINAL_AMOUNT'] ?>" readonly>
                                                        </div>
                                                        <div>
                                                            <button type="button" class="remove-btn" onclick="removeThis(this);"><i class="bi bi-trash"></i></button>
                                                        </div>
                                                    </div>
                                                <?php $enrollment_service_data->MoveNext();
                                                } ?>
                                            <?php } else { ?>
                                                <div class="service-row individual_service_div">
                                                    <div>
                                                        <span class="form-label-sm">Services</span>
                                                        <select class="form-control-modern PK_SERVICE_MASTER" name="PK_SERVICE_MASTER[]" onchange="selectThisService(this)" required>
                                                            <option value="">Select</option>
                                                            <?php
                                                            $row = $db_account->Execute("SELECT DISTINCT DOA_SERVICE_MASTER.PK_SERVICE_MASTER, DOA_SERVICE_MASTER.SERVICE_NAME, DOA_SERVICE_MASTER.DESCRIPTION, DOA_SERVICE_MASTER.ACTIVE FROM `DOA_SERVICE_MASTER` WHERE DOA_SERVICE_MASTER.PK_LOCATION IN (" . $DEFAULT_LOCATION_ID . ") AND IS_DELETED = 0 ORDER BY DOA_SERVICE_MASTER.SERVICE_NAME ASC");
                                                            while (!$row->EOF) { ?>
                                                                <option value="<?php echo $row->fields['PK_SERVICE_MASTER']; ?>"><?= htmlspecialchars($row->fields['SERVICE_NAME']) ?></option>
                                                            <?php $row->MoveNext();
                                                            } ?>
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <span class="form-label-sm">Service Codes</span>
                                                        <select class="form-control-modern PK_SERVICE_CODE" name="PK_SERVICE_CODE[]" onchange="selectThisServiceCode(this)" required>
                                                            <option value="">Select</option>
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <span class="form-label-sm">Service Details</span>
                                                        <input type="text" class="form-control-modern SERVICE_DETAILS" name="SERVICE_DETAILS[]">
                                                    </div>
                                                    <div>
                                                        <span class="form-label-sm">Sessions</span>
                                                        <input type="text" class="form-control-modern NUMBER_OF_SESSION" name="NUMBER_OF_SESSION[]" onkeyup="calculateServiceTotal(this)" required>
                                                    </div>
                                                    <div>
                                                        <span class="form-label-sm">Price/Session</span>
                                                        <input type="text" class="form-control-modern PRICE_PER_SESSION" name="PRICE_PER_SESSION[]" onkeyup="calculateServiceTotal(this);" required>
                                                    </div>
                                                    <div>
                                                        <span class="form-label-sm">Total</span>
                                                        <input type="text" class="form-control-modern TOTAL" name="TOTAL[]" readonly>
                                                    </div>
                                                    <div>
                                                        <span class="form-label-sm">Discount Type</span>
                                                        <select class="form-control-modern DISCOUNT_TYPE" name="DISCOUNT_TYPE[]" onchange="calculateServiceTotal(this)">
                                                            <option value="">Select</option>
                                                            <option value="1">Fixed</option>
                                                            <option value="2">Percent</option>
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <span class="form-label-sm">Discount</span>
                                                        <input type="text" class="form-control-modern DISCOUNT" name="DISCOUNT[]" onkeyup="calculateServiceTotal(this)">
                                                    </div>
                                                    <div>
                                                        <span class="form-label-sm">Final Amount</span>
                                                        <input type="text" class="form-control-modern FINAL_AMOUNT" name="FINAL_AMOUNT[]" readonly>
                                                    </div>
                                                    <div>
                                                        <button type="button" class="remove-btn" onclick="removeThis(this);"><i class="bi bi-trash"></i></button>
                                                    </div>
                                                </div>
                                            <?php } ?>
                                        </div>
                                    </div>

                                    <div class="total-amount-display">
                                        <label>Total</label>
                                        <input type="text" class="form-control-modern TOTAL_AMOUNT" value="<?= number_format((float)$total, 2, '.', ''); ?>" readonly style="width: 150px; text-align: right; font-weight: 600; font-size: 16px;">
                                    </div>

                                    <div class="add_more <?= ($PK_ENROLLMENT_MASTER > 0) ? 'disabled_div' : '' ?>" style="margin-top: 6px; display: <?= $CHARGE_TYPE == 'Session' ? 'none' : '' ?>">
                                        <button type="button" class="btn-modern btn-modern-secondary btn-modern-sm" onclick="addMoreServices();">
                                            <i class="bi bi-plus"></i> Add More
                                        </button>
                                    </div>

                                    <!-- Agreement & Enrollment By -->
                                    <div class="form-grid" style="margin-top: 12px;">
                                        <div class="form-group-modern">
                                            <label class="form-label">Agreement Template <span class="required">*</span></label>
                                            <select class="form-control-modern" required name="PK_DOCUMENT_LIBRARY" id="PK_DOCUMENT_LIBRARY">
                                                <option value="">Select Agreement Template</option>
                                                <?php
                                                $row = $db_account->Execute("SELECT PK_DOCUMENT_LIBRARY, DOCUMENT_NAME FROM DOA_DOCUMENT_LIBRARY WHERE ACTIVE = 1 ORDER BY PK_DOCUMENT_LIBRARY");
                                                while (!$row->EOF) { ?>
                                                    <option value="<?php echo $row->fields['PK_DOCUMENT_LIBRARY']; ?>" <?= ($PK_DOCUMENT_LIBRARY == $row->fields['PK_DOCUMENT_LIBRARY']) ? 'selected' : '' ?>><?= htmlspecialchars($row->fields['DOCUMENT_NAME']) ?></option>
                                                <?php $row->MoveNext();
                                                } ?>
                                            </select>
                                            <div class="form-helper">Select the agreement template for this enrollment</div>
                                        </div>

                                        <div class="form-group-modern">
                                            <label class="form-label">Enrollment By <span class="required">*</span></label>
                                            <select class="form-control-modern" required name="ENROLLMENT_BY_ID" id="ENROLLMENT_BY_ID">
                                                <option value="">Select</option>
                                            </select>
                                            <div class="form-helper">Who is enrolling this customer</div>
                                        </div>

                                        <div class="form-group-modern">
                                            <label class="form-label">Percentage <span class="required">*</span></label>
                                            <div style="display: flex; align-items: center; border: 1.5px solid var(--gray-200); border-radius: var(--radius-sm); overflow: hidden;">
                                                <input type="text" class="form-control-modern ENROLLMENT_BY_PERCENTAGE" name="ENROLLMENT_BY_PERCENTAGE" value="<?= $ENROLLMENT_BY_PERCENTAGE ?>" style="border: none; flex: 1; padding: 8px 12px;">
                                                <span style="padding: 8px 12px; background: var(--gray-50); color: var(--gray-500); font-weight: 500; border-left: 1px solid var(--gray-200);">%</span>
                                            </div>
                                            <div class="form-helper">Percentage of enrollment value</div>
                                        </div>
                                    </div>

                                    <!-- Service Providers -->
                                    <div class="section-title" style="margin-top: 12px;">
                                        <i class="bi bi-people"></i> <?= $service_provider_title ?>
                                    </div>

                                    <div style="display: grid; grid-template-columns: 5fr 5fr 1fr; gap: 12px; align-items: center; font-weight: 600; font-size: 12px; color: var(--gray-500); text-transform: uppercase; letter-spacing: 0.03em; padding: 4px 0 8px 0; border-bottom: 2px solid var(--gray-200);">
                                        <span><?= $service_provider_title ?></span>
                                        <span>Percentage</span>
                                        <span></span>
                                    </div>

                                    <div id="append_service_provider_div">
                                        <?php
                                        if (!empty($_GET['id'])) {
                                            $enrollment_service_provider_data = $db_account->Execute("SELECT * FROM DOA_ENROLLMENT_SERVICE_PROVIDER WHERE PK_ENROLLMENT_MASTER = '$_GET[id]'");
                                            if ($enrollment_service_provider_data->RecordCount() > 0) {
                                                while (!$enrollment_service_provider_data->EOF) { ?>
                                                    <div class="provider-grid individual_service_provider_div">
                                                        <div>
                                                            <span class="form-label-sm"><?= $service_provider_title ?></span>
                                                            <select class="form-control-modern" name="SERVICE_PROVIDER_ID[]">
                                                                <option value="">Select</option>
                                                                <?php
                                                                $row = $db->Execute("SELECT DISTINCT(DOA_USERS.PK_USER), CONCAT(FIRST_NAME, ' ', LAST_NAME) AS NAME FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER LEFT JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER WHERE DOA_USER_ROLES.PK_ROLES = 5 AND DOA_USER_LOCATION.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]' AND ACTIVE = 1 ORDER BY FIRST_NAME");
                                                                while (!$row->EOF) { ?>
                                                                    <option value="<?php echo $row->fields['PK_USER']; ?>" <?= ($row->fields['PK_USER'] == $enrollment_service_provider_data->fields['SERVICE_PROVIDER_ID']) ? 'selected' : '' ?>><?= htmlspecialchars($row->fields['NAME']) ?></option>
                                                                <?php $row->MoveNext();
                                                                } ?>
                                                            </select>
                                                        </div>
                                                        <div>
                                                            <span class="form-label-sm">Percentage</span>
                                                            <div style="display: flex; align-items: center; border: 1.5px solid var(--gray-200); border-radius: var(--radius-sm); overflow: hidden;">
                                                                <input type="text" class="form-control-modern" name="SERVICE_PROVIDER_PERCENTAGE[]" value="<?= number_format((float)$enrollment_service_provider_data->fields['SERVICE_PROVIDER_PERCENTAGE'], 2, '.', '') ?>" style="border: none; flex: 1; padding: 6px 10px;">
                                                                <span style="padding: 6px 10px; background: var(--gray-50); color: var(--gray-500); font-weight: 500; border-left: 1px solid var(--gray-200);">%</span>
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <button type="button" class="remove-btn" onclick="removeThisServiceProvider(this);"><i class="bi bi-trash"></i></button>
                                                        </div>
                                                    </div>
                                                <?php $enrollment_service_provider_data->MoveNext();
                                                }
                                            } else { ?>
                                                <div class="provider-grid individual_service_provider_div">
                                                    <div>
                                                        <span class="form-label-sm"><?= $service_provider_title ?></span>
                                                        <select class="form-control-modern" name="SERVICE_PROVIDER_ID[]">
                                                            <option value="">Select</option>
                                                            <?php
                                                            $row = $db->Execute("SELECT DISTINCT(DOA_USERS.PK_USER), CONCAT(FIRST_NAME, ' ', LAST_NAME) AS NAME FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER LEFT JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER WHERE DOA_USER_ROLES.PK_ROLES = 5 AND DOA_USER_LOCATION.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]' AND ACTIVE = 1 ORDER BY FIRST_NAME");
                                                            while (!$row->EOF) { ?>
                                                                <option value="<?php echo $row->fields['PK_USER']; ?>"><?= htmlspecialchars($row->fields['NAME']) ?></option>
                                                            <?php $row->MoveNext();
                                                            } ?>
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <span class="form-label-sm">Percentage</span>
                                                        <div style="display: flex; align-items: center; border: 1.5px solid var(--gray-200); border-radius: var(--radius-sm); overflow: hidden;">
                                                            <input type="text" class="form-control-modern" name="SERVICE_PROVIDER_PERCENTAGE[]" style="border: none; flex: 1; padding: 6px 10px;">
                                                            <span style="padding: 6px 10px; background: var(--gray-50); color: var(--gray-500); font-weight: 500; border-left: 1px solid var(--gray-200);">%</span>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <button type="button" class="remove-btn" onclick="removeThisServiceProvider(this);"><i class="bi bi-trash"></i></button>
                                                    </div>
                                                </div>
                                            <?php } ?>
                                        <?php } else { ?>
                                            <div class="provider-grid individual_service_provider_div">
                                                <div>
                                                    <span class="form-label-sm"><?= $service_provider_title ?></span>
                                                    <select class="form-control-modern" name="SERVICE_PROVIDER_ID[]">
                                                        <option value="">Select</option>
                                                        <?php
                                                        $row = $db->Execute("SELECT DISTINCT(DOA_USERS.PK_USER), CONCAT(FIRST_NAME, ' ', LAST_NAME) AS NAME FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER LEFT JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER WHERE DOA_USER_ROLES.PK_ROLES = 5 AND DOA_USER_LOCATION.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]' AND ACTIVE = 1 ORDER BY FIRST_NAME");
                                                        while (!$row->EOF) { ?>
                                                            <option value="<?php echo $row->fields['PK_USER']; ?>"><?= htmlspecialchars($row->fields['NAME']) ?></option>
                                                        <?php $row->MoveNext();
                                                        } ?>
                                                    </select>
                                                </div>
                                                <div>
                                                    <span class="form-label-sm">Percentage</span>
                                                    <div style="display: flex; align-items: center; border: 1.5px solid var(--gray-200); border-radius: var(--radius-sm); overflow: hidden;">
                                                        <input type="text" class="form-control-modern" name="SERVICE_PROVIDER_PERCENTAGE[]" style="border: none; flex: 1; padding: 6px 10px;">
                                                        <span style="padding: 6px 10px; background: var(--gray-50); color: var(--gray-500); font-weight: 500; border-left: 1px solid var(--gray-200);">%</span>
                                                    </div>
                                                </div>
                                                <div>
                                                    <button type="button" class="remove-btn" onclick="removeThisServiceProvider(this);"><i class="bi bi-trash"></i></button>
                                                </div>
                                            </div>
                                        <?php } ?>
                                    </div>

                                    <div style="margin-top: 6px;">
                                        <button type="button" class="btn-modern btn-modern-secondary btn-modern-sm" onclick="addMoreServiceProviders();">
                                            <i class="bi bi-plus"></i> Add More
                                        </button>
                                    </div>

                                    <!-- Memo -->
                                    <div class="form-grid" style="margin-top: 12px;">
                                        <div class="form-group-modern full-width">
                                            <label class="form-label">Memo</label>
                                            <textarea class="form-control-modern" name="MEMO" rows="2" placeholder="Add any notes about this enrollment"><?= htmlspecialchars($MEMO) ?></textarea>
                                            <div class="form-helper">Additional notes about this enrollment</div>
                                        </div>
                                    </div>

                                    <!-- Active Status -->
                                    <?php if (!empty($_GET['id'])): ?>
                                        <div class="form-group-modern" style="margin-top: 8px;">
                                            <label class="form-label">Active</label>
                                            <div class="radio-group-modern">
                                                <label class="radio-item">
                                                    <input type="radio" name="ACTIVE" id="ACTIVE_YES" value="1" <?php if ($ACTIVE == 1) echo 'checked="checked"'; ?>> Active
                                                </label>
                                                <label class="radio-item">
                                                    <input type="radio" name="ACTIVE" id="ACTIVE_NO" value="0" <?php if ($ACTIVE == 0) echo 'checked="checked"'; ?>> Inactive
                                                </label>
                                            </div>
                                            <div class="form-helper">Set the status of this enrollment</div>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Form Actions -->
                                    <?php if ($_SESSION['PK_ROLES'] != 5): ?>
                                        <div class="form-actions">
                                            <button type="submit" class="btn-modern btn-modern-primary">
                                                <i class="bi bi-save"></i>
                                                <?= ($PK_ENROLLMENT_MASTER > 0) ? 'Save' : 'Continue' ?>
                                            </button>
                                            <button type="button" id="cancel_button" class="btn-modern btn-modern-secondary">
                                                <i class="bi bi-x"></i> Cancel
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </form>
                            </div>

                            <!-- ===== BILLING TAB ===== -->
                            <div class="tab-content tab-content-2 <?= ($PK_ENROLLMENT_BILLING > 0) ? 'disabled_div' : '' ?>">
                                <div style="border: none; box-shadow: none; padding: 0;">
                                    <form class="form-material form-horizontal" id="billing_form">
                                        <input type="hidden" name="FUNCTION_NAME" value="saveEnrollmentBillingData">
                                        <input type="hidden" name="PK_ENROLLMENT_MASTER" class="PK_ENROLLMENT_MASTER" value="<?= (empty($_GET['id'])) ? '' : $_GET['id'] ?>">
                                        <input type="hidden" name="PK_ENROLLMENT_BILLING" class="PK_ENROLLMENT_BILLING" value="<?= $PK_ENROLLMENT_BILLING ?>">

                                        <div id="payment_tab_div"></div>

                                        <div class="section-title">
                                            <i class="bi bi-credit-card"></i> Payment Plans
                                        </div>

                                        <div class="form-grid">
                                            <div class="form-group-modern">
                                                <label class="form-label">Billing Ref #</label>
                                                <input type="text" name="BILLING_REF" id="BILLING_REF" class="form-control-modern" value="<?= htmlspecialchars($BILLING_REF) ?>" placeholder="Enter billing reference">
                                                <div class="form-helper">Reference number for billing</div>
                                            </div>

                                            <div class="form-group-modern">
                                                <label class="form-label">Payment Method <span class="required">*</span></label>
                                                <div class="radio-group-modern">
                                                    <label class="radio-item one_time">
                                                        <input type="radio" class="PAYMENT_METHOD" name="PAYMENT_METHOD" value="One Time" <?= ($PAYMENT_METHOD == 'One Time') ? 'checked' : '' ?> required> One Time
                                                    </label>
                                                    <label class="radio-item payment_plans">
                                                        <input type="radio" class="PAYMENT_METHOD" name="PAYMENT_METHOD" value="Payment Plans" <?= ($PAYMENT_METHOD == 'Payment Plans') ? 'checked' : '' ?> required> Payment Plans
                                                    </label>
                                                    <label class="radio-item flexible_payments">
                                                        <input type="radio" class="PAYMENT_METHOD" name="PAYMENT_METHOD" value="Flexible Payments" <?= ($PAYMENT_METHOD == 'Flexible Payments') ? 'checked' : '' ?> required> Flexible Payments
                                                    </label>
                                                </div>
                                                <div class="form-helper">Choose how payments will be collected</div>
                                            </div>

                                            <div class="form-group-modern" id="auto-pay-div" style="display: <?= ($PAYMENT_METHOD == 'Payment Plans' || $PAYMENT_METHOD == 'Flexible Payments') ? 'flex' : 'none' ?>; grid-column: 1 / -1;">
                                                <label class="form-label">Active auto-pay for this enrollment</label>
                                                <div class="radio-group-modern">
                                                    <label class="radio-item">
                                                        <input type="radio" class="ACTIVE_AUTO_PAY" name="ACTIVE_AUTO_PAY" id="ACTIVE_AUTO_PAY_YES" value="1" <?= ($ACTIVE_AUTO_PAY == '1') ? 'checked' : '' ?>> Yes
                                                    </label>
                                                    <label class="radio-item">
                                                        <input type="radio" class="ACTIVE_AUTO_PAY" name="ACTIVE_AUTO_PAY" id="ACTIVE_AUTO_PAY_NO" value="0" <?= ($ACTIVE_AUTO_PAY == '0') ? 'checked' : '' ?>> No
                                                    </label>
                                                </div>
                                                <div id="selected_card_span" style="color: var(--primary-color); font-size: 13px;"></div>
                                                <input type="hidden" name="AUTO_PAY_PAYMENT_METHOD_ID" id="AUTO_PAY_PAYMENT_METHOD_ID" value="">
                                                <div class="form-helper">Enable automatic payments for this enrollment</div>
                                            </div>

                                            <div class="form-group-modern">
                                                <label class="form-label">Billing Date</label>
                                                <input type="text" name="BILLING_DATE" id="BILLING_DATE" value="<?= ($BILLING_DATE == '') ? date('m/d/Y') : date('m/d/Y', strtotime($BILLING_DATE)) ?>" class="form-control-modern datepicker-normal">
                                                <div class="form-helper">When billing starts</div>
                                            </div>

                                            <div class="form-group-modern" id="down_payment_div" style="display: <?= ($PAYMENT_METHOD == 'One Time') ? 'none' : 'flex' ?>">
                                                <label class="form-label">Down Payment</label>
                                                <input type="text" name="DOWN_PAYMENT" id="DOWN_PAYMENT" value="<?= $DOWN_PAYMENT ?>" class="form-control-modern" onkeyup="calculatePayment()" placeholder="0.00">
                                                <div class="form-helper">Initial payment amount</div>
                                            </div>

                                            <div class="form-group-modern">
                                                <label class="form-label">Balance Payable</label>
                                                <input type="text" name="BALANCE_PAYABLE" id="BALANCE_PAYABLE" value="<?= $BALANCE_PAYABLE ?>" class="form-control-modern" readonly>
                                                <div class="form-helper">Remaining balance after down payment</div>
                                            </div>
                                        </div>

                                        <!-- Payment Plans -->
                                        <div class="payment_method_div" id="payment_plans_div" style="display: <?= ($PAYMENT_METHOD == 'Payment Plans') ? 'block' : 'none' ?>;">
                                            <div class="form-grid">
                                                <div class="form-group-modern">
                                                    <label class="form-label">Payment Term <span class="required">*</span></label>
                                                    <select class="form-control-modern installment-input" name="PAYMENT_TERM" id="PAYMENT_TERM" <?= ($PAYMENT_METHOD == 'Payment Plans') ? 'required' : '' ?>>
                                                        <option value="">Select</option>
                                                        <option value="Weekly" <?= ($PAYMENT_TERM == 'Weekly') ? 'selected' : '' ?>>Weekly</option>
                                                        <option value="Monthly" <?= ($PAYMENT_TERM == 'Monthly') ? 'selected' : '' ?>>Monthly</option>
                                                        <option value="Quarterly" <?= ($PAYMENT_TERM == 'Quarterly') ? 'selected' : '' ?>>Quarterly</option>
                                                    </select>
                                                    <div class="form-helper">How often payments are made</div>
                                                </div>

                                                <div class="form-group-modern">
                                                    <label class="form-label">Number of Payments <span class="required">*</span></label>
                                                    <input type="text" name="NUMBER_OF_PAYMENT" id="NUMBER_OF_PAYMENT" value="<?= $NUMBER_OF_PAYMENT ?>" class="form-control-modern installment-input" onkeyup="calculatePaymentPlans();" <?= ($PAYMENT_METHOD == 'Payment Plans') ? 'required' : '' ?>>
                                                    <div id="number_of_payment_error" style="color: var(--danger-color); display: none; font-size: 12px;">This value should be a whole number.</div>
                                                    <div class="form-helper">Total number of payments</div>
                                                </div>

                                                <div class="form-group-modern">
                                                    <label class="form-label">First Scheduled Payment Date <span class="required">*</span></label>
                                                    <input type="text" name="FIRST_DUE_DATE" id="FIRST_DUE_DATE" value="<?= ($FIRST_DUE_DATE) ? date('m/d/Y', strtotime($FIRST_DUE_DATE)) : '' ?>" class="form-control-modern datepicker-future installment-input" <?= ($PAYMENT_METHOD == 'Payment Plans') ? 'required' : '' ?>>
                                                    <div class="form-helper">Date of the first payment</div>
                                                </div>

                                                <div class="form-group-modern">
                                                    <label class="form-label">Installment Amount</label>
                                                    <input type="text" name="INSTALLMENT_AMOUNT" id="INSTALLMENT_AMOUNT" value="<?= $INSTALLMENT_AMOUNT ?>" class="form-control-modern installment-input" onkeyup="calculateNumberOfPayment(this)" <?= ($PAYMENT_METHOD == 'Payment Plans') ? 'required' : '' ?>>
                                                    <div class="form-helper">Amount per installment</div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Flexible Payments -->
                                        <div class="payment_method_div" id="flexible_plans_div" style="display: <?= ($PAYMENT_METHOD == 'Flexible Payments') ? 'block' : 'none' ?>;">
                                            <div class="flexible-payments-wrapper">
                                                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; margin-bottom: 8px;">
                                                    <div style="display: flex; gap: 20px;">
                                                        <span style="font-weight: 500; font-size: 14px; color: var(--gray-600);">Next Payment Dates</span>
                                                        <span style="font-weight: 500; font-size: 14px; color: var(--gray-600);">Amount</span>
                                                    </div>
                                                    <button type="button" class="btn-modern btn-modern-secondary btn-modern-sm" onclick="addMorePayments();">
                                                        <i class="bi bi-plus"></i> Add More
                                                    </button>
                                                </div>

                                                <div id="flexible_payments_container">
                                                    <?php
                                                    if (!empty($_GET['id'])) {
                                                        $i = 0;
                                                        $flexible_payment_data = $db_account->Execute("SELECT * FROM DOA_ENROLLMENT_LEDGER WHERE TRANSACTION_TYPE = 'Billing' AND PK_ENROLLMENT_MASTER = '$_GET[id]'");
                                                        while (!$flexible_payment_data->EOF) {
                                                            if ($DOWN_PAYMENT > 0 && $i > 0) { ?>
                                                                <div class="flexible-payment-row">
                                                                    <input type="text" name="FLEXIBLE_PAYMENT_DATE[]" class="form-control-modern datepicker-future" value="<?= ($flexible_payment_data->fields['DUE_DATE']) ? date('m/d/Y', strtotime($flexible_payment_data->fields['DUE_DATE'])) : '' ?>" required>
                                                                    <input type="text" name="FLEXIBLE_PAYMENT_AMOUNT[]" class="form-control-modern FLEXIBLE_PAYMENT_AMOUNT" value="<?= $flexible_payment_data->fields['BILLED_AMOUNT'] ?>" required>
                                                                    <button type="button" class="remove-btn" onclick="removeThisAmount(this);"><i class="bi bi-trash"></i></button>
                                                                </div>
                                                        <?php }
                                                            $i++;
                                                            $flexible_payment_data->MoveNext();
                                                        } ?>
                                                    <?php } else { ?>
                                                        <div class="flexible-payment-row">
                                                            <input type="text" name="FLEXIBLE_PAYMENT_DATE[]" class="form-control-modern datepicker-future">
                                                            <input type="text" name="FLEXIBLE_PAYMENT_AMOUNT[]" class="form-control-modern FLEXIBLE_PAYMENT_AMOUNT" onkeyup="calculateBalancePayable(this);">
                                                            <button type="button" class="remove-btn" onclick="removeThisAmount(this);"><i class="bi bi-trash"></i></button>
                                                        </div>
                                                    <?php } ?>
                                                </div>
                                                <div class="form-helper">Define custom payment dates and amounts</div>
                                            </div>
                                        </div>

                                        <?php if ($PK_ENROLLMENT_BILLING == '') { ?>
                                            <div class="form-actions">
                                                <button type="button" class="btn-modern btn-modern-secondary" onclick="switchTab('.tab-content-1')">
                                                    <i class="bi bi-arrow-left"></i> Back
                                                </button>
                                                <button type="submit" class="btn-modern btn-modern-primary">
                                                    <i class="bi bi-save"></i> Save & Continue
                                                </button>
                                            </div>
                                        <?php } ?>
                                    </form>
                                </div>
                            </div>

                            <!-- ===== LEDGER TAB ===== -->
                            <div class="tab-content tab-content-3">
                                <div class="section-title">
                                    <i class="bi bi-book"></i> Billing Details
                                </div>

                                <div class="table-responsive" style="overflow-x: auto;">
                                    <table class="table-modern" id="ledger_table">
                                        <thead>
                                            <tr>
                                                <th>Due Date</th>
                                                <th>Transaction Type</th>
                                                <th>Billed Amount</th>
                                                <th>Paid Amount</th>
                                                <th>Balance</th>
                                                <th>Payment Type</th>
                                                <th>Memo</th>
                                                <th>Paid</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $billed_amount = 0;
                                            $balance = 0;
                                            $billing_details = $db_account->Execute("SELECT * FROM DOA_ENROLLMENT_LEDGER WHERE PK_ENROLLMENT_MASTER = " . $_GET['id'] . " AND ENROLLMENT_LEDGER_PARENT = 0 ORDER BY DUE_DATE ASC, PK_ENROLLMENT_LEDGER ASC");
                                            while (!$billing_details->EOF) {
                                                $billed_amount = $billing_details->fields['BILLED_AMOUNT'];
                                                $balance = ($billing_details->fields['BILLED_AMOUNT'] + $balance);
                                            ?>
                                                <tr>
                                                    <td><?= date('m/d/Y', strtotime($billing_details->fields['DUE_DATE'])) ?></td>
                                                    <td><?= $billing_details->fields['TRANSACTION_TYPE'] ?></td>
                                                    <td>$<?= number_format($billing_details->fields['BILLED_AMOUNT'], 2) ?></td>
                                                    <td></td>
                                                    <td>$<?= number_format($balance, 2) ?></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td><?= (($billing_details->fields['TRANSACTION_TYPE'] == 'Billing') ? (($billing_details->fields['IS_PAID'] == 1) ? 'YES' : 'NO') : '') ?></td>
                                                    <td>
                                                        <?php if ($billing_details->fields['IS_PAID'] == 0 && $billing_details->fields['STATUS'] == 'A') { ?>
                                                            <button class="btn-modern btn-modern-primary btn-modern-sm" onclick="payNow(<?= $billing_details->fields['PK_ENROLLMENT_LEDGER'] ?>, <?= $billing_details->fields['BILLED_AMOUNT'] ?>);">
                                                                <i class="bi bi-credit-card"></i> Pay Now
                                                            </button>
                                                        <?php } ?>
                                                    </td>
                                                </tr>
                                                <?php
                                                $payment_details = $db_account->Execute("SELECT DOA_ENROLLMENT_PAYMENT.*, DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE, DOA_PAYMENT_TYPE.PAYMENT_TYPE FROM DOA_ENROLLMENT_PAYMENT LEFT JOIN $master_database.DOA_PAYMENT_TYPE AS DOA_PAYMENT_TYPE ON DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE = DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE WHERE PK_ENROLLMENT_LEDGER = " . $billing_details->fields['PK_ENROLLMENT_LEDGER']);
                                                if ($payment_details->RecordCount() > 0) {
                                                    while (!$payment_details->EOF) {
                                                        $PK_ENROLLMENT_MASTER = $payment_details->fields['PK_ENROLLMENT_MASTER'];
                                                        $PK_ENROLLMENT_LEDGER = $payment_details->fields['PK_ENROLLMENT_LEDGER'];
                                                        if ($payment_details->fields['TYPE'] == 'Payment' && $payment_details->fields['IS_REFUNDED'] == 0) {
                                                            $balance -= $payment_details->fields['AMOUNT'];
                                                        }

                                                        if ($payment_details->fields['TYPE'] == 'Move') {
                                                            $payment_type = 'Wallet';
                                                        } elseif ($payment_details->fields['PK_PAYMENT_TYPE'] == '2') {
                                                            $payment_info = json_decode($payment_details->fields['PAYMENT_INFO']);
                                                            $payment_type = $payment_details->fields['PAYMENT_TYPE'] . " : " . ((isset($payment_info->CHECK_NUMBER)) ? $payment_info->CHECK_NUMBER : '');
                                                        } elseif (in_array($payment_details->fields['PK_PAYMENT_TYPE'], [1, 8, 9, 10, 11, 13, 14])) {
                                                            $payment_info = json_decode($payment_details->fields['PAYMENT_INFO']);
                                                            $payment_type = $payment_details->fields['PAYMENT_TYPE'] . " # " . ((isset($payment_info->LAST4)) ? $payment_info->LAST4 : '');
                                                        } elseif ($payment_details->fields['PK_PAYMENT_TYPE'] == '7') {
                                                            $receipt_number_array = explode(',', $payment_details->fields['RECEIPT_NUMBER']);
                                                            $payment_type_array = [];
                                                            foreach ($receipt_number_array as $receipt_number) {
                                                                $receipt_payment_details = $db_account->Execute("SELECT DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE, DOA_ENROLLMENT_PAYMENT.PAYMENT_INFO, DOA_PAYMENT_TYPE.PAYMENT_TYPE FROM DOA_ENROLLMENT_PAYMENT LEFT JOIN $master_database.DOA_PAYMENT_TYPE AS DOA_PAYMENT_TYPE ON DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE = DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE WHERE DOA_ENROLLMENT_PAYMENT.RECEIPT_NUMBER = '$receipt_number'");
                                                                if ($receipt_payment_details->fields['PK_PAYMENT_TYPE'] == '2') {
                                                                    $payment_info = json_decode($receipt_payment_details->fields['PAYMENT_INFO']);
                                                                    $payment_type_array[] = $receipt_payment_details->fields['PAYMENT_TYPE'] . " : " . ((isset($payment_info->CHECK_NUMBER)) ? $payment_info->CHECK_NUMBER : '');
                                                                } else {
                                                                    $payment_type_array[] = $receipt_payment_details->fields['PAYMENT_TYPE'];
                                                                }
                                                            }
                                                            $payment_type = implode(', ', $payment_type_array);
                                                        } else {
                                                            $payment_type = $payment_details->fields['PAYMENT_TYPE'];
                                                        } ?>
                                                        <tr style="color: <?= ($payment_details->fields['IS_PAID'] == 2) ? 'var(--primary-color)' : '' ?>">
                                                            <td><?= date('m/d/Y', strtotime($payment_details->fields['PAYMENT_DATE'])) ?></td>
                                                            <td><?= $payment_details->fields['TYPE'] ?></td>
                                                            <td></td>
                                                            <td style="text-align: right;">$<?= number_format($payment_details->fields['AMOUNT'], 2) ?></td>
                                                            <td></td>
                                                            <td style="text-align: center;"><?= $payment_type ?></td>
                                                            <td style="text-align: center;"><?= htmlspecialchars($payment_details->fields['NOTE']) ?></td>
                                                            <td><?= (($payment_details->fields['TYPE'] == 'Billing') ? (($payment_details->fields['IS_PAID'] == 1) ? 'YES' : 'NO') : '') ?></td>
                                                            <td>
                                                                <a onclick="openReceipt(<?= $PK_ENROLLMENT_MASTER ?>, '<?= $payment_details->fields['RECEIPT_NUMBER'] ?>')" href="javascript:;" style="color: var(--primary-color);">
                                                                    <i class="bi bi-receipt"></i> Receipt
                                                                </a>
                                                            </td>
                                                        </tr>
                                            <?php $payment_details->MoveNext();
                                                    }
                                                }
                                                $billing_details->MoveNext();
                                            } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- ===== HISTORY TAB ===== -->
                            <?php if (!empty($_GET['id'])) { ?>
                                <div class="tab-content tab-content-4">
                                    <div class="section-title">
                                        <i class="bi bi-clock-history"></i> Change History
                                    </div>
                                    <div class="table-responsive" style="overflow-x: auto;">
                                        <table class="table-modern">
                                            <thead>
                                                <tr>
                                                    <th>Field Name</th>
                                                    <th>From</th>
                                                    <th>To</th>
                                                    <th>Update By</th>
                                                    <th>Time</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $row = $db->Execute("SELECT $account_database.DOA_UPDATE_HISTORY.*, $master_database.DOA_USERS.FIRST_NAME, $master_database.DOA_USERS.LAST_NAME FROM $account_database.DOA_UPDATE_HISTORY INNER JOIN $master_database.DOA_USERS ON $account_database.DOA_UPDATE_HISTORY.EDITED_BY = $master_database.DOA_USERS.PK_USER WHERE $account_database.DOA_UPDATE_HISTORY.CLASS = 'enrollment' AND $account_database.DOA_UPDATE_HISTORY.PRIMARY_KEY = " . $_GET['id'] . " ORDER BY $account_database.DOA_UPDATE_HISTORY.PK_UPDATE_HISTORY DESC");
                                                while (!$row->EOF) { ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($row->fields['FIELD_NAME']) ?></td>
                                                        <td><?= htmlspecialchars($row->fields['FROM_VALUE']) ?></td>
                                                        <td><?= htmlspecialchars($row->fields['TO_VALUE']) ?></td>
                                                        <td><?= htmlspecialchars($row->fields['FIRST_NAME'] . " " . $row->fields['LAST_NAME']) ?></td>
                                                        <td><?= $row->fields['EDITED_ON'] ?></td>
                                                    </tr>
                                                <?php $row->MoveNext();
                                                } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            <?php } ?>

                            <!-- ===== AGREEMENT TAB ===== -->
                            <?php if (!empty($_GET['id']) && $AGREEMENT_PDF_LINK != '' && $AGREEMENT_PDF_LINK != null) { ?>
                                <div class="tab-content tab-content-5">
                                    <div style="margin-bottom: 12px; display: flex; justify-content: flex-end;">
                                        <button id="openSign" class="btn-modern btn-modern-primary" onclick="$('#signature_modal').modal('show');">
                                            <i class="bi bi-pencil"></i> Sign Agreement
                                        </button>
                                    </div>
                                    <iframe src="../<?= $upload_path ?>/enrollment_pdf/<?= $AGREEMENT_PDF_LINK ?>" width="100%" height="600px" style="border: 1px solid var(--gray-200); border-radius: var(--radius-sm);"></iframe>
                                </div>
                            <?php } ?>

                        </div><!-- /right-panel -->
                    </div><!-- /row -->
                </div><!-- /card-box -->

            </div><!-- /container-fluid -->
        </div><!-- /page-wrapper -->
    </div><!-- /main-wrapper -->

    <!-- Confirm Modal -->
    <div class="modal fade" id="confirm_modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Confirm</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="is_confirm" value="0">
                    <p>Are you sure you want to proceed without selecting <?= $service_provider_title ?> ?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-modern btn-modern-secondary" data-bs-dismiss="modal">No</button>
                    <button type="button" class="btn-modern btn-modern-primary" onclick="$('#is_confirm').val(1); $('#enrollment_form').submit();">Yes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Signature Modal -->
    <div class="modal fade" id="signature_modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title"><i class="bi bi-pencil"></i> Add Signature</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <canvas id="signature-pad" width="710" height="200"></canvas>
                    <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px;">
                        <button id="clear" class="btn-modern btn-modern-secondary btn-modern-sm">Clear</button>
                        <button id="save" class="btn-modern btn-modern-primary btn-modern-sm">Sign Agreement</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Credit Card Modal -->
    <div class="modal fade" id="credit_card_modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title"><i class="bi bi-credit-card"></i> Select Credit Card for Auto-Pay</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="add_credit_card_div" style="display: none;"></div>
                    <div id="saved_credit_card_list" style="display: none; padding: 0;"></div>
                </div>
                <input type="hidden" id="TEMP_PAYMENT_METHOD_ID">
                <input type="hidden" id="TEMP_LAST4">
                <div class="modal-footer">
                    <button type="button" class="btn-modern btn-modern-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn-modern btn-modern-primary" onclick="addAutoPayCardDetails()">Process</button>
                </div>
            </div>
        </div>
    </div>

    <?php require_once('../includes/footer.php'); ?>
    <?php include('includes/enrollment_payment.php'); ?>

    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="../assets/sumoselect/jquery.sumoselect.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>

    <script>
        // Tab switching - EXACTLY like customer page
        $('.sidebar-link').on('click', function(evt) {
            evt.preventDefault();

            $('.sidebar-link').removeClass('active');
            $(this).addClass('active');

            var sel = $(this).data('toggle-target');
            $('.tab-content').removeClass('active');
            $(sel).addClass('active');
        });

        function switchTab(selector) {
            $('.sidebar-link').removeClass('active');
            $('.sidebar-link[data-toggle-target="' + selector + '"]').addClass('active');
            $('.tab-content').removeClass('active');
            $(selector).addClass('active');
        }

        let PK_ENROLLMENT_MASTER = parseInt(<?= empty($_GET['id']) ? 0 : $_GET['id'] ?>);
        let ENROLLMENT_BY_ID = parseInt(<?= $ENROLLMENT_BY_ID ?>);

        // Date pickers
        $('.datepicker-normal').datepicker({
            dateFormat: 'mm/dd/yy'
        });

        $('.datepicker-future').datepicker({
            dateFormat: 'mm/dd/yy',
            beforeShow: function(input, inst) {
                var selectedDate = $('#BILLING_DATE').datepicker('getDate');
                if (selectedDate) {
                    var nextDay = new Date(selectedDate.getTime());
                    nextDay.setDate(nextDay.getDate() + 1);
                    $(this).datepicker('option', 'minDate', nextDay);
                } else {
                    $(this).datepicker('option', 'minDate', 0);
                }
            }
        });

        // Initialize SumoSelect
        $('#PK_USER_MASTER').SumoSelect({
            placeholder: 'Select Customer',
            search: true,
            searchText: 'Search...'
        });

        // ========== ALL FUNCTIONS ==========

        function selectThisCustomer(param) {
            let location_id = $(param).find(':selected').data('location_id');
            let PK_USER = $(param).find(':selected').data('pk_user');
            $('#PK_LOCATION').val(location_id);
            $.ajax({
                url: "ajax/get_locations.php",
                type: "POST",
                data: {
                    PK_USER: PK_USER,
                    LOCATION_ID: location_id
                },
                async: false,
                cache: false,
                success: function(result) {
                    $('#PK_LOCATION').empty().append(result);
                    if (PK_ENROLLMENT_MASTER == 0) {
                        showEnrollmentInstructor();
                    }
                    showEnrollmentBy();
                    getEnrollmentCount();
                }
            });
        }

        function showEnrollmentBy() {
            let location_id = $('#PK_LOCATION').val();
            $.ajax({
                url: "ajax/get_enrollment_by.php",
                type: "POST",
                data: {
                    LOCATION_ID: location_id
                },
                async: false,
                cache: false,
                success: function(result) {
                    $('#ENROLLMENT_BY_ID').empty().append(result);
                    if (PK_ENROLLMENT_MASTER > 0) {
                        $('#ENROLLMENT_BY_ID').val(ENROLLMENT_BY_ID);
                    }
                }
            });
        }

        function showEnrollmentInstructor() {
            let location_id = $('#PK_LOCATION').val();
            $.ajax({
                url: "ajax/get_instructor.php",
                type: "POST",
                data: {
                    LOCATION_ID: location_id
                },
                async: false,
                cache: false,
                success: function(result) {
                    if ($('.SERVICE_PROVIDER_ID').val() == null) {
                        $('.SERVICE_PROVIDER_ID').empty().append(result);
                    }
                }
            });
        }

        function getEnrollmentCount() {
            let PK_ENROLLMENT_MASTER = parseInt(<?= empty($_GET['id']) ? 0 : $_GET['id'] ?>);
            let PK_USER_MASTER = $('#PK_USER_MASTER').val();
            let PK_LOCATION = $('#PK_LOCATION').val();
            if (PK_USER_MASTER > 0 && PK_LOCATION > 0 && PK_ENROLLMENT_MASTER == 0) {
                $.ajax({
                    url: "ajax/AjaxFunctions.php",
                    type: "POST",
                    data: {
                        PK_USER_MASTER: PK_USER_MASTER,
                        PK_LOCATION: PK_LOCATION,
                        FUNCTION_NAME: 'getEnrollmentCount'
                    },
                    async: false,
                    cache: false,
                    success: function(result) {
                        switch (parseInt(result)) {
                            case 0:
                                $('#PK_ENROLLMENT_TYPE').val(5);
                                break;
                            case 1:
                                $('#PK_ENROLLMENT_TYPE').val(2);
                                break;
                            case 2:
                                $('#PK_ENROLLMENT_TYPE').val(9);
                                break;
                            default:
                                $('#PK_ENROLLMENT_TYPE').val(13);
                                break;
                        }
                    }
                });
            }
        }

        function addMoreServices() {
            let charge_type = $('.charge_type:checked').val();
            if (charge_type === 'Membership') {
                var value = "XX";
                var type = "readonly";
                var total = "";
            } else {
                var value = "";
                var type = "";
                var total = "readonly";
            }

            $('#append_service_div').append(`<div class="service-row individual_service_div">
                <div>
                    <span class="form-label-sm">Services</span>
                    <select class="form-control-modern PK_SERVICE_MASTER" name="PK_SERVICE_MASTER[]" onchange="selectThisService(this)" required>
                        <option value="">Select</option>
                        <?php
                        $row = $db_account->Execute("SELECT DISTINCT DOA_SERVICE_MASTER.PK_SERVICE_MASTER, DOA_SERVICE_MASTER.SERVICE_NAME, DOA_SERVICE_MASTER.DESCRIPTION, DOA_SERVICE_MASTER.ACTIVE FROM `DOA_SERVICE_MASTER` WHERE DOA_SERVICE_MASTER.PK_LOCATION IN (" . $DEFAULT_LOCATION_ID . ") AND IS_DELETED = 0");
                        while (!$row->EOF) { ?>
                            <option value="<?php echo $row->fields['PK_SERVICE_MASTER']; ?>"><?= htmlspecialchars($row->fields['SERVICE_NAME']) ?></option>
                        <?php $row->MoveNext();
                        } ?>
                    </select>
                </div>
                <div>
                    <span class="form-label-sm">Service Codes</span>
                    <select class="form-control-modern PK_SERVICE_CODE" name="PK_SERVICE_CODE[]" onchange="selectThisServiceCode(this)" required>
                        <option value="">Select</option>
                    </select>
                </div>
                <div>
                    <span class="form-label-sm">Service Details</span>
                    <input type="text" class="form-control-modern SERVICE_DETAILS" name="SERVICE_DETAILS[]">
                </div>
                <div>
                    <span class="form-label-sm">Sessions</span>
                    <input type="text" class="form-control-modern NUMBER_OF_SESSION" value="${value}" name="NUMBER_OF_SESSION[]" onkeyup="calculateServiceTotal(this)" ${type} required>
                </div>
                <div>
                    <span class="form-label-sm">Price/Session</span>
                    <input type="text" class="form-control-modern PRICE_PER_SESSION" value="${value}" name="PRICE_PER_SESSION[]" onkeyup="calculateServiceTotal(this);" ${type} required>
                </div>
                <div>
                    <span class="form-label-sm">Total</span>
                    <input type="text" class="form-control-modern TOTAL" name="TOTAL[]" ${total}>
                </div>
                <div>
                    <span class="form-label-sm">Discount Type</span>
                    <select class="form-control-modern DISCOUNT_TYPE" name="DISCOUNT_TYPE[]" onchange="calculateServiceTotal(this)">
                        <option value="">Select</option>
                        <option value="1">Fixed</option>
                        <option value="2">Percent</option>
                    </select>
                </div>
                <div>
                    <span class="form-label-sm">Discount</span>
                    <input type="text" class="form-control-modern DISCOUNT" name="DISCOUNT[]" onkeyup="calculateServiceTotal(this)">
                </div>
                <div>
                    <span class="form-label-sm">Final Amount</span>
                    <input type="text" class="form-control-modern FINAL_AMOUNT" name="FINAL_AMOUNT[]" readonly>
                </div>
                <div>
                    <button type="button" class="remove-btn" onclick="removeThis(this);"><i class="bi bi-trash"></i></button>
                </div>
            </div>`);
            updateServiceAvailability();
        }

        function addMoreServiceProviders() {
            $('#append_service_provider_div').append(`
        <div class="provider-grid individual_service_provider_div">
            <div>
                <span class="form-label-sm"><?= $service_provider_title ?></span>
                <select class="form-control-modern" name="SERVICE_PROVIDER_ID[]">
                    <option value="">Select</option>
                    <?php
                    $row = $db->Execute("SELECT DISTINCT(DOA_USERS.PK_USER), CONCAT(FIRST_NAME, ' ', LAST_NAME) AS NAME FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER LEFT JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER WHERE DOA_USER_ROLES.PK_ROLES = 5 AND DOA_USER_LOCATION.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]' AND ACTIVE = 1 ORDER BY FIRST_NAME");
                    while (!$row->EOF) { ?>
                        <option value="<?php echo $row->fields['PK_USER']; ?>"><?= htmlspecialchars($row->fields['NAME']) ?></option>
                    <?php $row->MoveNext();
                    } ?>
                </select>
            </div>
            <div>
                <span class="form-label-sm">Percentage</span>
                <div style="display: flex; align-items: center; border: 1.5px solid var(--gray-200); border-radius: var(--radius-sm); overflow: hidden;">
                    <input type="text" class="form-control-modern" name="SERVICE_PROVIDER_PERCENTAGE[]" style="border: none; flex: 1; padding: 6px 10px;">
                    <span style="padding: 6px 10px; background: var(--gray-50); color: var(--gray-500); font-weight: 500; border-left: 1px solid var(--gray-200);">%</span>
                </div>
            </div>
            <div>
                <button type="button" class="remove-btn" onclick="removeThisServiceProvider(this);"><i class="bi bi-trash"></i></button>
            </div>
        </div>
    `);
            showEnrollmentInstructor();
        }

        function removeThisServiceProvider(param) {
            $(param).closest('.provider-grid').remove();
        }

        function removeThis(param) {
            if ($(param).closest('.service-row').length > 0) {
                $(param).closest('.service-row').remove();
                let TOTAL_AMOUNT = 0;
                $('.FINAL_AMOUNT').each(function() {
                    if ($(this).val() != '') {
                        TOTAL_AMOUNT += parseFloat($(this).val());
                    }
                });
                $('.TOTAL_AMOUNT').val(TOTAL_AMOUNT.toFixed(2));
                return;
            } else if ($(param).closest('.provider-grid').length > 0) {
                $(param).closest('.provider-grid').remove();
                return;
            } else if ($(param).closest('.flexible-payment-row').length > 0) {
                $(param).closest('.flexible-payment-row').remove();
                let total_bill = parseFloat(($('#total_bill').val()) ? $('#total_bill').val() : 0);
                let total_flexible_payment = 0;
                $('.FLEXIBLE_PAYMENT_AMOUNT').each(function() {
                    total_flexible_payment += parseFloat($(this).val());
                });
                total_flexible_payment = isNaN(total_flexible_payment) ? 0 : total_flexible_payment;
                $('#BALANCE_PAYABLE').val(parseFloat(total_bill - total_flexible_payment).toFixed(2));
                return;
            } else {
                $(param).closest('.row').remove();
            }
        }

        function removeThisAmount(param) {
            $(param).closest('.flexible-payment-row').remove();
            let total_bill = parseFloat(($('#total_bill').val()) ? $('#total_bill').val() : 0);
            let total_flexible_payment = 0;
            $('.FLEXIBLE_PAYMENT_AMOUNT').each(function() {
                total_flexible_payment += parseFloat($(this).val());
            });
            total_flexible_payment = isNaN(total_flexible_payment) ? 0 : total_flexible_payment;
            $('#BALANCE_PAYABLE').val(parseFloat(total_bill - total_flexible_payment).toFixed(2));
        }

        function updateServiceAvailability() {
            let selectedServices = [];
            $('.PK_SERVICE_MASTER').each(function() {
                let val = $(this).val();
                if (val && val !== 'Select' && val !== '') {
                    selectedServices.push(val);
                }
            });

            $('.PK_SERVICE_MASTER').each(function() {
                let currentSelect = $(this);
                let currentValue = currentSelect.val();

                currentSelect.find('option').prop('disabled', false);

                selectedServices.forEach(function(serviceId) {
                    if (serviceId !== currentValue) {
                        currentSelect.find('option[value="' + serviceId + '"]').prop('disabled', true);
                    }
                });
            });
        }

        function selectThisServiceCode(param) {
            let service_details = $(param).find(':selected').data('details');
            let price = $(param).find(':selected').data('price');

            let charge_type = $('.charge_type:checked').val();
            if (charge_type === 'Membership') {
                $(param).closest('.service-row').find('.SERVICE_DETAILS').val(service_details);
                $(param).closest('.service-row').find('.PRICE_PER_SESSION').val("XX");
            } else {
                $(param).closest('.service-row').find('.SERVICE_DETAILS').val(service_details);
                $(param).closest('.service-row').find('.PRICE_PER_SESSION').val(price);
            }

            calculateServiceTotal(param);
        }

        function selectThisService(param) {
            let PK_SERVICE_MASTER = $(param).val();
            $.ajax({
                url: "ajax/get_service_codes.php",
                type: "POST",
                data: {
                    PK_SERVICE_MASTER: PK_SERVICE_MASTER
                },
                async: false,
                cache: false,
                success: function(result) {
                    $(param).closest('.service-row').find('.PK_SERVICE_CODE').empty();
                    $(param).closest('.service-row').find('.PK_SERVICE_CODE').append(result);
                }
            });

            updateServiceAvailability();
        }

        function selectThisPackage(param) {
            let PK_PACKAGE = $(param).val();
            let EXPIRY_DATE = $(param).find(':selected').data('expiry_date');
            if (PK_PACKAGE) {
                $.ajax({
                    url: "ajax/get_packages.php",
                    type: "POST",
                    data: {
                        PK_PACKAGE: PK_PACKAGE
                    },
                    async: false,
                    cache: false,
                    success: function(result) {
                        $('.individual_service_div').remove();
                        $('#append_service_div').html(result);

                        let TOTAL_AMOUNT = 0;
                        $(param).closest('#enrollment_form').find('.FINAL_AMOUNT').each(function() {
                            TOTAL_AMOUNT += parseFloat($(this).val());
                        });
                        $('.TOTAL_AMOUNT').val(TOTAL_AMOUNT.toFixed(2));
                        $('#EXPIRY_DATE option').each(function() {
                            if ($(this).data('expiry_date') == EXPIRY_DATE) {
                                $(this).prop('selected', true);
                            }
                        });
                    }
                });
            } else {
                $('.package_div').remove();
                addMoreServices();
            }
        }

        function chargeBySessions(param) {
            $('.NUMBER_OF_SESSION').prop('readonly', false);
            $('.NUMBER_OF_SESSION').val('').trigger('change');
            $('.PRICE_PER_SESSION').prop('readonly', false);
            $('.PRICE_PER_SESSION').val('').trigger('change');
            if ($(param).is(':checked') && ($(param).val() === 'Session' || $(param).val() === 'Membership')) {
                if ($(param).val() === 'Session') {
                    $('#Membership').prop('checked', false);
                    $('.TOTAL').prop('readonly', true);
                    $('.add_more').hide();
                    $('.session_base').show();
                    $('#EXPIRY_DATE').prop('required', true);
                    $('.member_base').hide();
                } else {
                    $('#Session').prop('checked', false);
                    $('.NUMBER_OF_SESSION').prop('readonly', true);
                    $('.NUMBER_OF_SESSION').val('XX').trigger('change');
                    $('.PRICE_PER_SESSION').prop('readonly', true);
                    $('.PRICE_PER_SESSION').val('XX').trigger('change');
                    $('.TOTAL').prop('readonly', false);
                    $('.add_more').show();
                    $('.session_base').hide();
                    $('#EXPIRY_DATE').prop('required', false);
                    $('.member_base').show();
                }
                $('#BILLING_DATE').prop('readonly', true).css("pointer-events", "none");
                $('.one_time').show();
                $('.payment_plans').hide();
                $('.flexible_payments').hide();
                document.querySelector("input[name='PAYMENT_METHOD'][value='One Time']").checked = true;
                $('#down_payment_div').slideUp();
                $('#AMOUNT_TO_PAY').prop('readonly', true);
                $('.partial_payment').hide();
                $('.ENROLLMENT_PAYMENT_TYPE').val(1).trigger('change');
                $('#save_card_on_file_div').show();
            } else {
                $('.session_base').show();
                $('#EXPIRY_DATE').prop('required', true);
                $('.member_base').hide();

                $('.add_more').show();
                $('#BILLING_DATE').prop('readonly', false).css("pointer-events", "auto");
                $('.one_time').show();
                $('.payment_plans').show();
                $('.flexible_payments').show();
                document.querySelector("input[name='PAYMENT_METHOD'][value='One Time']").checked = false;
                $('#down_payment_div').slideDown();
                $('#AMOUNT_TO_PAY').prop('readonly', false);
                $('.ENROLLMENT_PAYMENT_TYPE').css('pointer-events', 'auto');
                $('.partial_payment').show();
                $('#save_card_on_file_div').hide();
            }
        }

        function calculateServiceTotal(param) {
            let charge_type = $('.charge_type:checked').val();
            let TOTAL = 0;

            if (charge_type === 'Membership') {
                TOTAL = ($(param).closest('.service-row').find('.TOTAL').val() == '') ? 0 : $(param).closest('.service-row').find('.TOTAL').val();
            } else {
                let number_of_session = ($(param).closest('.service-row').find('.NUMBER_OF_SESSION').val() == '') ? 0 : $(param).closest('.service-row').find('.NUMBER_OF_SESSION').val();
                let service_price = ($(param).closest('.service-row').find('.PRICE_PER_SESSION').val()) ?? 0;
                TOTAL = parseFloat(number_of_session) * parseFloat(service_price);
                $(param).closest('.service-row').find('.TOTAL').val(parseFloat(TOTAL).toFixed(2));
            }

            let DISCOUNT = ($(param).closest('.service-row').find('.DISCOUNT').val()) ?? 0;
            let DISCOUNT_TYPE = ($(param).closest('.service-row').find('.DISCOUNT_TYPE').val()) ?? 0;
            let FINAL_AMOUNT = parseFloat(TOTAL);
            if (DISCOUNT_TYPE == 1) {
                FINAL_AMOUNT = parseFloat(TOTAL - DISCOUNT);
            } else {
                if (DISCOUNT_TYPE == 2) {
                    FINAL_AMOUNT = parseFloat(TOTAL - (TOTAL * (DISCOUNT / 100)));
                }
            }
            $(param).closest('.service-row').find('.FINAL_AMOUNT').val(FINAL_AMOUNT.toFixed(2));

            let TOTAL_AMOUNT = 0;
            $(param).closest('#enrollment_form').find('.FINAL_AMOUNT').each(function() {
                TOTAL_AMOUNT += parseFloat($(this).val());
            });
            $('.TOTAL_AMOUNT').val(TOTAL_AMOUNT.toFixed(2));
        }

        $(document).on('click', '#cancel_button', function() {
            window.location.href = 'all_enrollments.php'
        });

        function addMorePayments() {
            let total_bill = parseFloat(($('#total_bill').val()) ? $('#total_bill').val() : 0);
            let down_payment = parseFloat(($('#DOWN_PAYMENT').val()) ? $('#DOWN_PAYMENT').val() : 0);
            let total_flexible_payment = 0;
            $('.FLEXIBLE_PAYMENT_AMOUNT').each(function() {
                total_flexible_payment += parseFloat($(this).val());
            });
            if ((total_flexible_payment + down_payment) < total_bill) {
                $('#flexible_payments_container').append(`<div class="flexible-payment-row">
                    <input type="text" name="FLEXIBLE_PAYMENT_DATE[]" class="form-control-modern datepicker-future" required>
                    <input type="text" name="FLEXIBLE_PAYMENT_AMOUNT[]" class="form-control-modern FLEXIBLE_PAYMENT_AMOUNT" onkeyup="calculateBalancePayable(this)" required>
                    <button type="button" class="remove-btn" onclick="removeThisAmount(this);"><i class="bi bi-trash"></i></button>
                </div>`);
                $('.datepicker-future').datepicker({
                    dateFormat: 'mm/dd/yy',
                    beforeShow: function(input, inst) {
                        var selectedDate = $('#BILLING_DATE').datepicker('getDate');
                        if (selectedDate) {
                            var nextDay = new Date(selectedDate.getTime());
                            nextDay.setDate(nextDay.getDate() + 1);
                            $(this).datepicker('option', 'minDate', nextDay);
                        } else {
                            $(this).datepicker('option', 'minDate', 0);
                        }
                    }
                });
            } else {
                alert('Total Bill Amount Exceed');
            }
        }

        $(document).on('submit', '#enrollment_form', function(event) {
            event.preventDefault();
            let service_provider = $('#SERVICE_PROVIDER_ID').val();
            let is_confirm = $('#is_confirm').val();
            if (service_provider == '' && is_confirm == 0) {
                $('#confirm_modal').modal('show');
            } else {
                $('#confirm_modal').modal('hide');
                let form_data = $('#enrollment_form').serialize();
                $.ajax({
                    url: "ajax/AjaxFunctions.php",
                    type: 'POST',
                    data: form_data,
                    dataType: 'json',
                    success: function(data) {
                        if (PK_ENROLLMENT_MASTER > 0) {
                            window.location.reload();
                        } else {
                            $('.PK_ENROLLMENT_MASTER').val(data.PK_ENROLLMENT_MASTER);
                            switchTab('.tab-content-2');
                        }
                    }
                });
            }
        });

        function goToPaymentTab() {
            let PK_ENROLLMENT_MASTER = $('.PK_ENROLLMENT_MASTER').val();
            if (PK_ENROLLMENT_MASTER) {
                switchTab('.tab-content-2');
                $.ajax({
                    url: "ajax/show_payment_tab.php",
                    type: 'POST',
                    data: {
                        PK_ENROLLMENT_MASTER: PK_ENROLLMENT_MASTER
                    },
                    success: function(data) {
                        $('#payment_tab_div').html(data);
                        $('#AMOUNT_SHOW').val($('.TOTAL_AMOUNT').val());
                        calculatePayment();
                    }
                });
            } else {
                alert('Please fill up the enrollment form first');
                switchTab('.tab-content-1');
            }
        }

        function goToLedgerTab() {
            let PK_ENROLLMENT_MASTER = $('.PK_ENROLLMENT_MASTER').val();
            if (!PK_ENROLLMENT_MASTER) {
                alert('Please fill up the enrollment form first');
                switchTab('.tab-content-1');
            } else {
                switchTab('.tab-content-3');
            }
        }

        function calculatePayment() {
            let total_bill = parseFloat(($('#total_bill').val()) ? $('#total_bill').val() : 0);
            let down_payment = parseFloat(($('#DOWN_PAYMENT').val()) ? $('#DOWN_PAYMENT').val() : 0);
            $('#BALANCE_PAYABLE').val(parseFloat(total_bill - down_payment).toFixed(2));
            calculateBalancePayable();
            calculatePaymentPlans();
        }

        $(document).on('change', '.PAYMENT_METHOD', function() {
            $('.payment_method_div').slideUp();
            $('#down_payment_div').slideDown();
            $('#FIRST_DUE_DATE').prop('required', false);
            $('#auto-pay-div').slideUp();
            if ($(this).val() == 'One Time') {
                let total_bill = parseFloat(($('#total_bill').val()) ? $('#total_bill').val() : 0);
                $('#DOWN_PAYMENT').val(0.00);
                $('#BALANCE_PAYABLE').val(total_bill.toFixed(2));
                $('#down_payment_div').slideUp();
                $('#ACTUAL_AMOUNT').val(total_bill.toFixed(2));
                $('#AMOUNT_TO_PAY').val(total_bill.toFixed(2));
                $('#PAYMENT_BILLING_REF').val($('#BILLING_REF').val());
                $('#PAYMENT_BILLING_DATE').val($('#BILLING_DATE').val());
            }
            if ($(this).val() == 'Payment Plans') {
                $('#FIRST_DUE_DATE').prop('required', true);
                $('#payment_plans_div').slideDown();
                $('#auto-pay-div').slideDown();
            }
            if ($(this).val() == 'Flexible Payments') {
                $('#flexible_plans_div').slideDown();
                let total_bill = parseFloat(($('#total_bill').val()) ? $('#total_bill').val() : 0);
                $('#DOWN_PAYMENT').val(0.00);
                $('#BALANCE_PAYABLE').val(total_bill.toFixed(2));
                $('#down_payment_div').slideDown();
                $('#ACTUAL_AMOUNT').val(total_bill.toFixed(2));
                $('#AMOUNT_TO_PAY').val(total_bill.toFixed(2));
                $('#auto-pay-div').slideDown();
            }
        });

        $(document).on('click', '.ACTIVE_AUTO_PAY', function() {
            if ($(this).val() == '1') {
                $('#credit_card_modal').modal('show');
                getSavedCreditCardListAutoPay();
            } else {
                $('#TEMP_PAYMENT_METHOD_ID').val('');
                $('#TEMP_LAST4').val('');
                $('#AUTO_PAY_PAYMENT_METHOD_ID').val('');
                $('#selected_card_span').css('color', 'red').text('Auto Pay is not active');
            }
        });

        function getSavedCreditCardListAutoPay() {
            let PK_USER_MASTER = $('#PK_USER_MASTER').find(':selected').data('customer_id');
            $.ajax({
                url: "ajax/get_credit_card_list.php",
                type: 'POST',
                data: {
                    PK_USER_MASTER: PK_USER_MASTER,
                    call_from: 'enrollment_auto_pay'
                },
                success: function(data) {
                    $('#saved_credit_card_list').slideDown().html(data);
                    addCreditCardAutoPay();
                }
            });
        }

        function selectAutoPayCreditCard(param) {
            let payment_id = $(param).attr('id');
            let last4 = $(param).data('last4');

            $('.credit-card-div').css("opacity", "1");
            $(param).css("opacity", "0.6");

            $('#TEMP_PAYMENT_METHOD_ID').val(payment_id);
            $('#TEMP_LAST4').val(last4);
        }

        function addAutoPayCardDetails() {
            let payment_id = $('#TEMP_PAYMENT_METHOD_ID').val();
            let last4 = $('#TEMP_LAST4').val();

            $('#AUTO_PAY_PAYMENT_METHOD_ID').val(payment_id);
            $('#selected_card_span').css('color', 'green').html('Card ending in <b>' + last4 + '</b> selected for Auto Pay');
            $('#credit_card_modal').modal('hide');
        }

        function addCreditCardAutoPay() {
            let PK_USER = $('#PK_USER_MASTER').find(':selected').data('pk_user');
            let PK_USER_MASTER = $('#PK_USER_MASTER').find(':selected').data('customer_id');
            $.ajax({
                url: "includes/save_credit_card.php",
                type: 'POST',
                data: {
                    PK_USER: PK_USER,
                    PK_USER_MASTER: PK_USER_MASTER,
                    call_from: 'enrollment_auto_pay'
                },
                success: function(data) {
                    $('#add_credit_card_div').slideDown().html(data);
                    addCreditCard();
                }
            });
        }

        function calculateBalancePayable() {
            let total_bill = parseFloat(($('#total_bill').val()) ? $('#total_bill').val() : 0);
            let total_flexible_payment = parseFloat(($('#DOWN_PAYMENT').val()) ? $('#DOWN_PAYMENT').val() : 0);
            $('.FLEXIBLE_PAYMENT_AMOUNT').each(function() {
                total_flexible_payment += parseFloat(($(this).val()) ? $(this).val() : 0);
            });
            total_flexible_payment = isNaN(total_flexible_payment) ? 0 : total_flexible_payment;
            $('#BALANCE_PAYABLE').val(parseFloat(total_bill - total_flexible_payment).toFixed(2));
        }

        function calculatePaymentPlans() {
            let balance_payable = parseFloat(($('#BALANCE_PAYABLE').val()) ? $('#BALANCE_PAYABLE').val() : 0);
            let NUMBER_OF_PAYMENT = parseInt(($('#NUMBER_OF_PAYMENT').val()) ? $('#NUMBER_OF_PAYMENT').val() : 1);
            $('#INSTALLMENT_AMOUNT').val(parseFloat(balance_payable / NUMBER_OF_PAYMENT).toFixed(2));
        }

        function calculateNumberOfPayment(param) {
            let balance_payable = parseFloat(($('#BALANCE_PAYABLE').val()) ? $('#BALANCE_PAYABLE').val() : 0);
            let entered_amount = $(param).val();
            if (entered_amount > 0) {
                let number_of_payment = balance_payable / entered_amount;
                $('#NUMBER_OF_PAYMENT').val(number_of_payment);
                if (Number.isInteger(number_of_payment)) {
                    $('#number_of_payment_error').hide();
                } else {
                    $('#number_of_payment_error').show();
                }
            }
        }

        $(document).on('submit', '#billing_form', function(event) {
            event.preventDefault();
            calculateBalancePayable();
            calculatePaymentPlans();
            let total_bill = parseFloat(($('#total_bill').val()) ? $('#total_bill').val() : 0);
            let down_payment = parseFloat(($('#DOWN_PAYMENT').val()) ? $('#DOWN_PAYMENT').val() : 0);
            let total_flexible_payment = 0;
            $('.FLEXIBLE_PAYMENT_AMOUNT').each(function() {
                total_flexible_payment += parseFloat($(this).val());
            });
            total_flexible_payment = isNaN(total_flexible_payment) ? 0 : total_flexible_payment;
            if ((total_flexible_payment + down_payment) <= total_bill) {
                let balance_payable = parseFloat(($('#BALANCE_PAYABLE').val()) ? $('#BALANCE_PAYABLE').val() : 0);
                let payment_method = $('.PAYMENT_METHOD:checked').val();
                if (payment_method == 'Flexible Payments' && balance_payable > 0) {
                    swal("Balance Payable!", "Remaining Balance Payable must be fully allocated between Next Payment Dates.", "error");
                } else {
                    let number_of_payment = $('#NUMBER_OF_PAYMENT').val();
                    if (Number.isInteger(Number(number_of_payment))) {
                        if ((payment_method === 'One Time') && (balance_payable <= 0)) {
                            Swal.fire({
                                title: "Are you sure?",
                                text: "The user want to create a $0.00 enrollment?",
                                icon: "warning",
                                showCancelButton: true,
                                confirmButtonColor: "#3085d6",
                                cancelButtonColor: "#d33",
                                confirmButtonText: "Yes, create it!"
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    submitBillingForm();
                                }
                            });
                        } else {
                            if (payment_method == 'Flexible Payments' || payment_method == 'Payment Plans') {
                                let ACTIVE_AUTO_PAY = $('.ACTIVE_AUTO_PAY:checked').val();
                                let AUTO_PAY_PAYMENT_METHOD_ID = $('#AUTO_PAY_PAYMENT_METHOD_ID').val();
                                if (ACTIVE_AUTO_PAY == '1' && AUTO_PAY_PAYMENT_METHOD_ID == '') {
                                    Swal.fire({
                                        title: "Are you sure?",
                                        text: "You selected to active Auto Pay but no credit card selected. Do you want to proceed without Auto Pay?",
                                        icon: "warning",
                                        showCancelButton: true,
                                        confirmButtonColor: "#3085d6",
                                        cancelButtonColor: "#d33",
                                        confirmButtonText: "Yes, proceed!"
                                    }).then((result) => {
                                        if (result.isConfirmed) {
                                            $('#ACTIVE_AUTO_PAY_NO').prop('checked', true);
                                            submitBillingForm();
                                        }
                                    });
                                } else {
                                    submitBillingForm();
                                }
                            } else {
                                submitBillingForm();
                            }
                        }
                    } else {
                        $('#number_of_payment_error').slideUp();
                        $('#number_of_payment_error').slideDown();
                    }
                }
            } else {
                alert('Total Bill Amount Exceed');
            }
        });

        function submitBillingForm() {
            let form_data = $('#billing_form').serialize();
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                data: form_data,
                dataType: 'json',
                success: function(data) {
                    $('.PK_ENROLLMENT_BILLING').val(data.PK_ENROLLMENT_BILLING);
                    $('.PK_ENROLLMENT_LEDGER').val(data.PK_ENROLLMENT_LEDGER);
                    let payment_method = $('.PAYMENT_METHOD:checked').val();
                    let down_payment = parseFloat($('#DOWN_PAYMENT').val());
                    let today = new Date().getTime();
                    let firstPaymentDate = new Date($('#FIRST_DUE_DATE').val()).getTime();
                    let billingDate = new Date($('#BILLING_DATE').val()).getTime();
                    let balance_payable = parseFloat(($('#BALANCE_PAYABLE').val()) ? $('#BALANCE_PAYABLE').val() : 0);

                    if (((down_payment > 0) && (today >= billingDate)) || ((payment_method === 'One Time') && (today >= billingDate) && (balance_payable > 0)) || ((payment_method === 'Payment Plans') && (today >= firstPaymentDate))) {
                        if (payment_method === 'One Time') {
                            $('#AMOUNT_TO_PAY').val(balance_payable.toFixed(2));
                            $('#ACTUAL_AMOUNT').val(balance_payable.toFixed(2));
                        } else {
                            if (down_payment > 0) {
                                $('#AMOUNT_TO_PAY').val(down_payment.toFixed(2));
                                $('#ACTUAL_AMOUNT').val(down_payment.toFixed(2));
                            } else {
                                if ((payment_method === 'Payment Plans') && (today >= firstPaymentDate)) {
                                    let installment_amount = parseFloat(($('#INSTALLMENT_AMOUNT').val()) ? $('#INSTALLMENT_AMOUNT').val() : 0);
                                    $('#AMOUNT_TO_PAY').val(installment_amount.toFixed(2));
                                    $('#ACTUAL_AMOUNT').val(installment_amount.toFixed(2));
                                }
                            }
                        }
                        $('#enrollment_payment_modal').modal('show');
                    } else {
                        let header = '<?= $header ?>';
                        if (header) {
                            window.location.href = header;
                        } else {
                            let PK_USER = $('#PK_USER_MASTER').find(':selected').data('pk_user');
                            let PK_USER_MASTER = $('#PK_USER_MASTER').find(':selected').data('customer_id');
                            window.location.href = 'customer.php?id=' + PK_USER + '&master_id=' + PK_USER_MASTER + '&tab=enrollment';
                        }
                    }
                }
            });
        }

        function payNow(PK_ENROLLMENT_LEDGER, BILLED_AMOUNT) {
            $('.PK_ENROLLMENT_LEDGER').val(PK_ENROLLMENT_LEDGER);
            $('#AMOUNT_TO_PAY').val(BILLED_AMOUNT);
            $('#ACTUAL_AMOUNT').val(BILLED_AMOUNT);
            $('#payment_confirmation_form_div').slideDown();
            $('#PK_PAYMENT_TYPE').val('');
            $('.payment_type_div').slideUp();
            $('#wallet_balance_div').slideUp();
            $('#remaining_amount_div').slideUp();
            $('#PK_PAYMENT_TYPE_REMAINING').prop('required', false);
            $('#enrollment_payment_modal').modal('show');
        }

        function openReceipt(PK_ENROLLMENT_MASTER, RECEIPT_NUMBER) {
            let RECEIPT_NUMBER_ARRAY = RECEIPT_NUMBER.split(',');
            for (let i = 0; i < RECEIPT_NUMBER_ARRAY.length; i++) {
                window.open('generate_receipt_pdf.php?master_id=' + PK_ENROLLMENT_MASTER + '&receipt=' + RECEIPT_NUMBER_ARRAY[i], '_blank');
            }
        }

        // ========== SIGNATURE PAD ==========
        const canvas = document.getElementById('signature-pad');
        const signaturePad = new SignaturePad(canvas);

        document.getElementById('clear').addEventListener('click', () => {
            signaturePad.clear();
        });

        document.getElementById('save').addEventListener('click', () => {
            if (signaturePad.isEmpty()) {
                alert("Please provide signature");
                return;
            }

            const dataURL = signaturePad.toDataURL();

            fetch('save_signature.php', {
                    method: 'POST',
                    body: JSON.stringify({
                        PK_ENROLLMENT_MASTER: <?= empty($_GET['id']) ? "''" : $_GET['id'] ?>,
                        image: dataURL
                    }),
                    headers: {
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        window.location.href = window.location.pathname + '?id=' + <?= empty($_GET['id']) ? "''" : $_GET['id'] ?> +
                            '&tab=agreement';
                    } else {
                        alert('Failed to sign PDF');
                    }
                })
                .catch(error => {
                    console.error(error);
                    alert('Something went wrong');
                });
        });

        // ========== DOCUMENT READY ==========
        $(document).ready(function() {
            $('#PK_USER_MASTER').trigger("change");

            let tab_link = <?= empty($_GET['tab']) ? 0 : json_encode($_GET['tab']) ?>;
            if (tab_link != 0) {
                let selector = '';
                switch (tab_link) {
                    case 'enrollment':
                        selector = '.tab-content-1';
                        break;
                    case 'billing':
                        selector = '.tab-content-2';
                        break;
                    case 'ledger':
                        selector = '.tab-content-3';
                        break;
                    case 'history':
                        selector = '.tab-content-4';
                        break;
                    case 'agreement':
                        selector = '.tab-content-5';
                        break;
                }
                if (selector) {
                    $('.sidebar-link').removeClass('active');
                    $('.sidebar-link[data-toggle-target="' + selector + '"]').addClass('active');
                    $('.tab-content').removeClass('active');
                    $(selector).addClass('active');
                }
            }

            // Initialize engagement terms visibility
            $('.engagement_terms').each(function() {
                let row = $(this).closest('.rate-row');
                let inputDiv = row.find('div:last-child');
                if ($(this).is(':checked')) {
                    inputDiv.show();
                } else {
                    inputDiv.hide();
                }
            });
        });
    </script>
</body>

</html>