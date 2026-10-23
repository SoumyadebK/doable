<?php

use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

require_once('../global/config.php');

if (empty($_GET['id']))
    $title = "Add Corporation";
else
    $title = "Edit Corporation";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
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

    $FRANCHISE              = '';
    $AM_USER_NAME           = '';
    $AM_PASSWORD            = '';
    $AM_REFRESH_TOKEN       = '';

    $TEXTING_FEATURE_ENABLED    = '';
    $TWILIO_ACCOUNT_TYPE        = '';

    $ACTIVE                 = '';
} else {
    $res = $db->Execute("SELECT * FROM `DOA_CORPORATION` WHERE PK_CORPORATION = '$_GET[id]'");
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

    $FRANCHISE              = $res->fields['FRANCHISE'];
    $AM_USER_NAME           = $res->fields['AM_USER_NAME'];
    $AM_PASSWORD            = $res->fields['AM_PASSWORD'];
    $AM_REFRESH_TOKEN       = $res->fields['AM_REFRESH_TOKEN'];

    $TEXTING_FEATURE_ENABLED    = $res->fields['TEXTING_FEATURE_ENABLED'];
    $TWILIO_ACCOUNT_TYPE        = $res->fields['TWILIO_ACCOUNT_TYPE'];

    $ACTIVE                 = $res->fields['ACTIVE'];
}

$user_data = $db->Execute("SELECT DOA_USERS.ABLE_TO_EDIT_PAYMENT_GATEWAY FROM DOA_USERS WHERE PK_USER = '$_SESSION[PK_USER]'");
$ABLE_TO_EDIT_PAYMENT_GATEWAY = $user_data->fields['ABLE_TO_EDIT_PAYMENT_GATEWAY'];

$help_title = '';
$help_description = '';
$help = $db->Execute("SELECT * FROM DOA_HELP_PAGE WHERE PAGE_LINK = 'corporation'");
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

    /* Credit Card List */
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
                                <i class="fas fa-building"></i>
                                <?= !empty($_GET['id']) ? 'Edit Corporation' : 'Create New Corporation' ?>
                            </h5>
                            <?php if (!empty($_GET['id'])): ?>
                                <span class="status-indicator <?= ($ACTIVE == 1) ? 'active' : 'inactive' ?>">
                                    <i class="fas fa-circle"></i>
                                    <?= ($ACTIVE == 1) ? 'Active' : 'Inactive' ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <form class="form-material form-horizontal" id="save_corporation_form" method="post" enctype="multipart/form-data">
                                <input type="hidden" name="FUNCTION_NAME" value="saveCorporationData">

                                <div class="form-grid">
                                    <!-- Corporation Name -->
                                    <div class="form-group-modern full-width">
                                        <label class="form-label">Corporation Name <span class="required">*</span></label>
                                        <input type="text" id="CORPORATION_NAME" name="CORPORATION_NAME" class="form-control-modern" placeholder="Enter Corporation Name" value="<?php echo htmlspecialchars($CORPORATION_NAME) ?>" required>
                                        <div class="form-helper">The official name of the corporation</div>
                                    </div>

                                    <!-- Timezone -->
                                    <div class="form-group-modern">
                                        <label class="form-label">Timezone <span class="required">*</span></label>
                                        <select name="PK_TIMEZONE" id="PK_TIMEZONE" class="form-control-modern" required>
                                            <option value="">Select Timezone</option>
                                            <?php $res_type = $db->Execute("SELECT * FROM DOA_TIMEZONE WHERE ACTIVE = 1 ORDER BY NAME ASC");
                                            while (!$res_type->EOF) { ?>
                                                <option value="<?= $res_type->fields['PK_TIMEZONE'] ?>" <?php if ($res_type->fields['PK_TIMEZONE'] == $PK_TIMEZONE) echo 'selected="selected"'; ?>><?= htmlspecialchars($res_type->fields['NAME']) ?></option>
                                            <?php $res_type->MoveNext();
                                            } ?>
                                        </select>
                                        <div class="form-helper">Primary timezone for this corporation</div>
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
                                        <div class="form-helper">Default currency for financial transactions</div>
                                    </div>
                                </div>

                                <!-- Credit Card Section -->
                                <div class="section-header">
                                    <i class="fas fa-credit-card"></i>
                                    <span>Add Credit Card</span>
                                </div>

                                <div class="form-grid">
                                    <div class="form-group-modern full-width">
                                        <input type="hidden" name="PAYMENT_METHOD_ID" id="PAYMENT_METHOD_ID" value="">
                                        <?php if ($SA_PAYMENT_GATEWAY_TYPE == 'Stripe') { ?>
                                            <input type="hidden" name="stripe_token" id="stripe_token" value="">
                                            <label class="form-label">Card Details</label>
                                            <div id="card_div">
                                                <div id="card-element"></div>
                                                <div id="card-errors" style="color: var(--danger-color); font-size: 13px; margin-top: 6px;"></div>
                                            </div>
                                            <div id="card_list_div"></div>
                                        <?php } elseif ($SA_PAYMENT_GATEWAY_TYPE == 'Square') { ?>
                                            <input type="hidden" name="square_token" id="square_token" value="">
                                            <label class="form-label">Card Details</label>
                                            <div id="payment-card-container"></div>
                                            <div id="payment-status-container"></div>
                                            <div id="card_list_div"></div>
                                        <?php } ?>
                                    </div>
                                </div>

                                <div id="corporation_payment_status"></div>

                                <!-- Active Status -->
                                <?php if (!empty($_GET['id'])): ?>
                                    <div class="form-group-modern" style="margin-top: 16px;">
                                        <label class="form-label">Active</label>
                                        <div class="radio-group-modern">
                                            <label class="radio-item">
                                                <input type="radio" name="ACTIVE" id="ACTIVE_YES" value="1" <?php if ($ACTIVE == 1) echo 'checked="checked"'; ?>>
                                                Active
                                            </label>
                                            <label class="radio-item">
                                                <input type="radio" name="ACTIVE" id="ACTIVE_NO" value="0" <?php if ($ACTIVE == 0) echo 'checked="checked"'; ?>>
                                                Inactive
                                            </label>
                                        </div>
                                        <div class="form-helper">Set the status of this corporation</div>
                                    </div>
                                <?php endif; ?>

                                <!-- Form Actions -->
                                <div class="form-actions">
                                    <button type="submit" id="corporation-pay-button" class="btn-modern btn-modern-primary">
                                        <i class="fas fa-save"></i> <?= empty($_GET['id']) ? 'Create Corporation' : 'Update Corporation' ?>
                                    </button>
                                    <button type="button" class="btn-modern btn-modern-secondary" onclick="window.location.href='all_corporations.php'">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                </div>
                            </form>
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
    <?php require_once('../includes/footer.php'); ?>
</body>

<script>
    $(document).ready(function() {
        getSavedCreditCardList();
    });

    function showHourBox(radio) {
        if (radio.value == 1) {
            document.getElementById("yes").style.display = "block";
        } else {
            document.getElementById("yes").style.display = "none";
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
        $('.payment_gateway').slideUp();
        if (radio.value == 'Stripe') {
            document.getElementById('stripe').style.display = 'block';
        } else if (radio.value == 'Square') {
            document.getElementById('square').style.display = 'block';
        } else if (radio.value == 'Authorized.net') {
            document.getElementById('authorized').style.display = 'block';
        } else if (radio.value == 'Clover') {
            document.getElementById('Clover').style.display = 'block';
        }
    }

    function getSavedCreditCardList() {
        let payment_gateway_type = '<?= $SA_PAYMENT_GATEWAY_TYPE ?>';
        if (payment_gateway_type == 'Square') {
            squarePaymentFunction();
        } else if (payment_gateway_type == 'Stripe') {
            stripePaymentFunction();
        }
    }
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

        // Create an instance of the card Element.
        var stripe_card = elements.create('card', {
            style: style
        });
        var pay_type = '';

        function stripePaymentFunction() {
            if (($('#card-element')).length > 0) {
                stripe_card.mount('#card-element');
            }
            // Handle real-time validation errors from the card Element.
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
            //event.preventDefault();
            stripe.createToken(stripe_card).then(function(result) {
                if (result.error) {
                    // Inform the user if there was an error.
                    let errorElement = document.getElementById('card-errors');
                    errorElement.textContent = result.error.message;
                } else {
                    // Send the token to your server.
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
                // Tokenize the card details
                const result = await square_card.tokenize();
                if (result.status === 'OK') {
                    // Add the token to the hidden input field
                    $('#square_token').val(result.token);
                } else {
                    // Handle tokenization errors
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

    $(document).on('submit', '#save_corporation_form', function(event) {
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
        let form_data = $('#save_corporation_form').serialize();
        $.ajax({
            url: "includes/save_wizard_corporation.php",
            type: 'POST',
            data: form_data,
            dataType: 'json',
            success: function(data) {
                if (data.STATUS == false) {
                    $('#corporation_payment_status').html(`<p class="alert-modern error">${data.MESSAGE}</p>`);
                    $('#corporation-pay-button').prop('disabled', false);
                    $('#corporation-pay-button').html(`<i class="fas fa-save"></i> Process`);
                } else {
                    $('#corporation_payment_status').html(`<p class="alert-modern success">Credit Card Successfully Saved.</p>`);

                    setTimeout(function() {
                        window.location.href = 'wizard_location.php';
                    }, 3000);
                }
            }
        });
    }
</script>

</html>