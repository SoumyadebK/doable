<?php
require_once('../global/config.php');
require_once("../global/stripe-php-master/init.php");

global $db;
global $db_account;
global $master_database;

use Square\Models\Address;
use Square\SquareClient;
use Square\Environment;

use Dompdf\Dompdf;
use Mpdf\Mpdf;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

$title = "Enrollments";

// === FIX: Safe field accessor for ADOdb result objects ===
if (!function_exists('safe_field')) {
    function safe_field($result, $column, $default = null)
    {
        if (!$result || !is_object($result)) return $default;
        if (!isset($result->fields) || !is_array($result->fields)) return $default;
        return array_key_exists($column, $result->fields) ? $result->fields[$column] : $default;
    }
}

// === FIX: Fallback for $account_database / $master_database ===
if (!isset($account_database) || $account_database === '') {
    $account_database = $_SESSION['ACCOUNT_DATABASE'] ?? 'doable_account';
}
if (!isset($master_database) || $master_database === '') {
    $master_database = $_SESSION['MASTER_DATABASE'] ?? 'doable_master';
}

// === FIX: Access control FIRST, before any queries ===
if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 4) {
    header("location:../login.php");
    exit;
}

$PK_USER_MASTER = !empty($_GET['master_id']) ? intval($_GET['master_id']) : 0;
$PK_USER        = !empty($_GET['id'])        ? intval($_GET['id'])        : 0;

echo "<input type='hidden' class='PK_USER_MASTER' value='" . $PK_USER_MASTER . "'>";
echo "<input type='hidden' class='PK_USER' value='" . $PK_USER . "'>";

$PK_ACCOUNT_MASTER = $_SESSION['PK_ACCOUNT_MASTER'] ?? 0;

// === FIX: Guard the DOA_ACCOUNT_MASTER query ===
$account_data = $db->Execute("SELECT * FROM `DOA_ACCOUNT_MASTER` WHERE `PK_ACCOUNT_MASTER` = " . intval($PK_ACCOUNT_MASTER));
if (!$account_data || $account_data->RecordCount() === 0) {
    error_log('billing.php: DOA_ACCOUNT_MASTER lookup failed for PK_ACCOUNT_MASTER=' . $PK_ACCOUNT_MASTER);
    $PAYMENT_GATEWAY = '';
    $SECRET_KEY      = '';
    $PUBLISHABLE_KEY = '';
    $ACCESS_TOKEN    = '';
    $APP_ID          = '';
    $LOCATION_ID     = '';
} else {
    $PAYMENT_GATEWAY = safe_field($account_data, 'PAYMENT_GATEWAY_TYPE', '');
    $SECRET_KEY      = safe_field($account_data, 'SECRET_KEY', '');
    $PUBLISHABLE_KEY = safe_field($account_data, 'PUBLISHABLE_KEY', '');
    $ACCESS_TOKEN    = safe_field($account_data, 'ACCESS_TOKEN', '');
    $APP_ID          = safe_field($account_data, 'APP_ID', '');
    $LOCATION_ID     = safe_field($account_data, 'LOCATION_ID', '');
}

$card_details = '';

if ($SECRET_KEY != '' && $PK_USER > 0) {
    try {
        $stripe = new StripeClient($SECRET_KEY);

        // === FIX: Guard the customer payment info query ===
        $customer_payment_info = $db_account->Execute("SELECT * FROM DOA_CUSTOMER_PAYMENT_INFO WHERE PAYMENT_TYPE = 'Stripe' AND PK_USER = " . intval($PK_USER));

        if ($customer_payment_info && $customer_payment_info->RecordCount() > 0) {
            $customer_id = safe_field($customer_payment_info, 'CUSTOMER_PAYMENT_ID', '');
            if ($customer_id !== '') {
                $stripe_customer = $stripe->customers->retrieve($customer_id);
                $card_id = $stripe_customer->default_source;

                $url  = "https://api.stripe.com/v1/customers/" . $customer_id . "/cards/" . $card_id;
                $AUTH = "Authorization: Bearer " . $SECRET_KEY;

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
                    CURLOPT_HTTPHEADER => array($AUTH),
                ));

                $response = curl_exec($curl);
                $card_details = json_decode($response, true);
            }
        }
    } catch (Exception $e) {
        error_log('billing.php: Stripe customer retrieve failed: ' . $e->getMessage());
    }
}

$results_per_page = 100;

if (isset($_GET['search_text']) && $_GET['search_text'] != '') {
    $search_text = $_GET['search_text'];
    $search = " AND (DOA_USERS.FIRST_NAME LIKE '%" . $search_text . "%' OR DOA_USERS.EMAIL_ID LIKE '%" . $search_text . "%' OR DOA_USERS.PHONE LIKE '%" . $search_text . "%')";
} else {
    $search_text = '';
    $search = ' ';
}

// === FIX: Guard the count query and fall back to 0 if it fails ===
$query = $db->Execute("SELECT count($account_database.DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS TOTAL_RECORDS
    FROM $account_database.`DOA_ENROLLMENT_MASTER`
    INNER JOIN $master_database.DOA_LOCATION ON $master_database.DOA_LOCATION.PK_LOCATION = $account_database.DOA_ENROLLMENT_MASTER.PK_LOCATION
    WHERE $account_database.DOA_ENROLLMENT_MASTER.PK_USER_MASTER IN (" . $PK_USER_MASTER . ")" . $search);

$number_of_result = safe_field($query, 'TOTAL_RECORDS', 0);
$number_of_page   = ceil($number_of_result / $results_per_page);

if (!isset($_GET['page'])) {
    $page = 1;
} else {
    $page = intval($_GET['page']);
}
$page_first_result = ($page - 1) * $results_per_page;

// === FIX: Payment POST handler — guard all ->fields accesses ===
if (!empty($_POST['PK_PAYMENT_TYPE'])) {
    $PK_ENROLLMENT_LEDGER = $_POST['PK_ENROLLMENT_LEDGER'] ?? 0;
    unset($_POST['PK_ENROLLMENT_LEDGER']);

    if (empty($_POST['PK_ENROLLMENT_PAYMENT'])) {
        $PAYMENT_INFO = 'Payment Done.';

        if ($_POST['PK_PAYMENT_TYPE'] == 1) {
            if (($_POST['PAYMENT_GATEWAY'] ?? '') == 'Stripe') {
                require_once("../global/stripe-php-master/init.php");
                \Stripe\Stripe::setApiKey($_POST['SECRET_KEY'] ?? '');
                $STRIPE_TOKEN = $_POST['token'] ?? '';
                $AMOUNT       = $_POST['AMOUNT'] ?? 0;
                try {
                    $charge = \Stripe\Charge::create([
                        'amount'      => ($AMOUNT * 100),
                        'currency'    => 'usd',
                        'description' => $_POST['NOTE'] ?? '',
                        'source'      => $STRIPE_TOKEN
                    ]);
                    if (!empty($charge->paid) && $charge->paid == 1) {
                        $PAYMENT_INFO = $charge->id;
                    } else {
                        $PAYMENT_INFO = 'Payment Unsuccessful.';
                    }
                } catch (Exception $e) {
                    error_log('billing.php: Stripe charge failed: ' . $e->getMessage());
                    $PAYMENT_INFO = 'Payment Unsuccessful.';
                }
            }
        }

        $PAYMENT_DATA['PK_ENROLLMENT_MASTER']  = $_POST['PK_ENROLLMENT_MASTER'] ?? 0;
        $PAYMENT_DATA['PK_ENROLLMENT_BILLING'] = $_POST['PK_ENROLLMENT_BILLING'] ?? 0;
        $PAYMENT_DATA['PK_PAYMENT_TYPE']       = $_POST['PK_PAYMENT_TYPE'];
        $PAYMENT_DATA['AMOUNT']                = $_POST['AMOUNT'] ?? 0;
        $PAYMENT_DATA['CHECK_NUMBER']          = $_POST['CHECK_NUMBER'] ?? '';
        $PAYMENT_DATA['CHECK_DATE']            = $_POST['CHECK_DATE'] ?? '';
        $PAYMENT_DATA['NOTE']                  = $_POST['NOTE'] ?? '';
        $PAYMENT_DATA['PAYMENT_DATE']          = date('Y-m-d');
        $PAYMENT_DATA['PAYMENT_INFO']          = $PAYMENT_INFO;
        db_perform_account('DOA_ENROLLMENT_PAYMENT', $PAYMENT_DATA, 'insert');

        $enrollment_balance = $db_account->Execute("SELECT * FROM `DOA_ENROLLMENT_BALANCE` WHERE PK_ENROLLMENT_MASTER = '" . intval($_POST['PK_ENROLLMENT_MASTER'] ?? 0) . "'");
        if ($enrollment_balance && $enrollment_balance->RecordCount() > 0) {
            $ENROLLMENT_BALANCE_DATA['TOTAL_BALANCE_PAID'] = safe_field($enrollment_balance, 'TOTAL_BALANCE_PAID', 0) + ($_POST['AMOUNT'] ?? 0);
            $ENROLLMENT_BALANCE_DATA['EDITED_BY']          = $_SESSION['PK_USER'];
            $ENROLLMENT_BALANCE_DATA['EDITED_ON']          = date("Y-m-d H:i");
            db_perform_account('DOA_ENROLLMENT_BALANCE', $ENROLLMENT_BALANCE_DATA, 'update', " PK_ENROLLMENT_MASTER =  '" . intval($_POST['PK_ENROLLMENT_MASTER'] ?? 0) . "'");
        } else {
            $ENROLLMENT_BALANCE_DATA['PK_ENROLLMENT_MASTER'] = $_POST['PK_ENROLLMENT_MASTER'] ?? 0;
            $ENROLLMENT_BALANCE_DATA['TOTAL_BALANCE_PAID']   = $_POST['AMOUNT'] ?? 0;
            $ENROLLMENT_BALANCE_DATA['CREATED_BY']           = $_SESSION['PK_USER'];
            $ENROLLMENT_BALANCE_DATA['CREATED_ON']           = date("Y-m-d H:i");
            db_perform_account('DOA_ENROLLMENT_BALANCE', $ENROLLMENT_BALANCE_DATA, 'insert');
        }

        $PK_ENROLLMENT_PAYMENT = $db_account->insert_ID();
        $ledger_record = $db_account->Execute("SELECT * FROM `DOA_ENROLLMENT_LEDGER` WHERE PK_ENROLLMENT_LEDGER =  '" . intval($PK_ENROLLMENT_LEDGER) . "'");

        $LEDGER_DATA['TRANSACTION_TYPE']        = 'Payment';
        $LEDGER_DATA['ENROLLMENT_LEDGER_PARENT'] = $PK_ENROLLMENT_LEDGER;
        $LEDGER_DATA['PK_ENROLLMENT_MASTER']    = $_POST['PK_ENROLLMENT_MASTER'] ?? 0;
        $LEDGER_DATA['PK_ENROLLMENT_BILLING']   = $_POST['PK_ENROLLMENT_BILLING'] ?? 0;
        $LEDGER_DATA['DUE_DATE']                = date('Y-m-d');
        $LEDGER_DATA['BILLED_AMOUNT']           = 0.00;
        $LEDGER_DATA['PAID_AMOUNT']             = safe_field($ledger_record, 'BILLED_AMOUNT', 0);
        $LEDGER_DATA['BALANCE']                 = 0.00;
        $LEDGER_DATA['IS_PAID']                 = 1;
        $LEDGER_DATA['PK_PAYMENT_TYPE']         = $_POST['PK_PAYMENT_TYPE'];
        $LEDGER_DATA['PK_ENROLLMENT_PAYMENT']   = $PK_ENROLLMENT_PAYMENT;
        db_perform_account('DOA_ENROLLMENT_LEDGER', $LEDGER_DATA, 'insert');

        $LEDGER_UPDATE_DATA['IS_PAID'] = 1;
        db_perform_account('DOA_ENROLLMENT_LEDGER', $LEDGER_UPDATE_DATA, 'update', "PK_ENROLLMENT_LEDGER =  '" . intval($PK_ENROLLMENT_LEDGER) . "'");
    } else {
        db_perform_account('DOA_ENROLLMENT_PAYMENT', $_POST, 'update', " PK_ENROLLMENT_PAYMENT =  '" . intval($_POST['PK_ENROLLMENT_PAYMENT']) . "'");
        $PK_ENROLLMENT_PAYMENT = $_POST['PK_ENROLLMENT_PAYMENT'];
    }

    header('location:billing.php');
    exit;
}

// === FIX: Reassign PK_USER_MASTER AFTER access check, matching original behavior ===
$PK_USER_MASTER = $PK_USER;
if ($PK_USER_MASTER > 0) {
    makeExpiryEnrollmentComplete($PK_USER_MASTER);
    makeMiscComplete($PK_USER_MASTER);
    makeDroppedCancelled($PK_USER_MASTER);
    checkAllEnrollmentStatus($PK_USER_MASTER);
}

// === FIX: Payment type query guarded (used in refund modal below) ===
$payment_types = $db->Execute("SELECT * FROM DOA_PAYMENT_TYPE WHERE PAYMENT_TYPE = 'Credit Card' AND ACTIVE = 1");

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

    .container-fluid {
        padding: 24px 32px !important;
        max-width: 1600px;
        margin: 0 auto;
    }

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

    .breadcrumb-nav .current {
        color: var(--gray-700);
        font-weight: 500;
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

    .card-modern .card-body {
        padding: 24px 28px;
    }

    @media (max-width: 768px) {
        .card-modern .card-body {
            padding: 16px;
        }

        .container-fluid {
            padding: 16px !important;
        }
    }

    .tabs-modern {
        display: flex;
        gap: 4px;
        border-bottom: 2px solid var(--gray-200);
        padding-bottom: 0;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .tabs-modern .tab-item {
        padding: 10px 18px;
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

    .enrollment-list-wrapper {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .loading-indicator {
        text-align: center;
        padding: 20px;
        color: var(--gray-500);
        font-size: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .loading-indicator .spinner {
        display: inline-block;
        width: 18px;
        height: 18px;
        border: 2px solid var(--gray-200);
        border-top-color: var(--primary-color);
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }

    .btn-modern {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 24px;
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

    .modal-modern .modal-content {
        border-radius: var(--radius-lg);
        border: none;
        box-shadow: var(--shadow-lg);
    }

    .modal-modern .modal-header {
        border-bottom: 1px solid var(--gray-200);
        padding: 16px 24px;
    }

    .modal-modern .modal-header h4 {
        font-weight: 600;
        color: var(--gray-800);
        font-size: 16px;
        margin: 0;
    }

    .modal-modern .modal-body {
        padding: 20px 24px;
    }

    .modal-modern .modal-footer {
        border-top: 1px solid var(--gray-200);
        padding: 12px 24px;
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

    .form-label-modern {
        font-size: 13px;
        font-weight: 500;
        color: var(--gray-700);
        margin-bottom: 6px;
        display: block;
    }
</style>

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">
        <?php require_once('../includes/header.php'); ?>

        <div class="page-wrapper" style="padding-top: 0px !important;">
            <div class="container-fluid body_content" style="margin-top: 0px !important;">

                <div class="breadcrumb-wrapper">
                    <h4>
                        <i class="fas fa-file-signature"></i>
                        <?= $title ?>
                    </h4>
                    <nav class="breadcrumb-nav">
                        <span class="current">Enrollments</span>
                    </nav>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card-modern">
                            <div class="card-header">
                                <h5>
                                    <i class="fas fa-list"></i>
                                    Enrollments
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="tabs-modern" role="tablist">
                                    <button class="tab-item active" id="enrollment_tab_link" data-tab="enrollment" role="tab" onclick="enrollmentLoadMore('normal')">
                                        <i class="fas fa-list"></i> Active Enrollments
                                    </button>
                                </div>

                                <div class="tab-content-modern">
                                    <div class="tab-pane-modern active" id="enrollment" role="tabpanel">
                                        <div id="enrollment_list" class="enrollment-list-wrapper">
                                            <div id="load-marker" class="loading-indicator">
                                                Loading <span class="spinner"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!--Edit Billing Due Date Model-->
    <div class="modal fade modal-modern" id="billing_due_date_model" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form id="edit_due_date_form" method="post">
                <input type="hidden" name="PK_ENROLLMENT_LEDGER" id="PK_ENROLLMENT_LEDGER">
                <input type="hidden" name="old_due_date" id="old_due_date">
                <input type="hidden" name="edit_type" id="edit_type">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4><i class="fas fa-calendar-edit" style="color: var(--primary-color); margin-right: 8px;"></i> Edit Due Date</h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="form-group-modern" style="margin-bottom: 16px;">
                            <label class="form-label-modern">Due Date</label>
                            <input type="text" id="due_date" name="due_date" class="form-control-modern datepicker-normal" placeholder="Due Date" required>
                        </div>

                        <div class="form-group-modern">
                            <label class="form-label-modern">Enter your profile password</label>
                            <input type="password" id="due_date_verify_password" name="due_date_verify_password" class="form-control-modern" placeholder="Password" required>
                            <p id="due_date_verify_password_error" style="color: var(--danger-color); font-size: 12px; margin-top: 4px; display: none;"></p>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn-modern btn-modern-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn-modern btn-modern-primary">
                            <i class="fas fa-check"></i> Process
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!--Refund Modal-->
    <div class="modal fade modal-modern" id="refund_modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog" style="max-width: 450px;">
            <div class="modal-content">
                <div class="modal-header">
                    <h4><i class="fas fa-undo" style="color: var(--primary-color); margin-right: 8px;"></i> Refund</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group-modern" style="margin-bottom: 16px;">
                        <label class="form-label-modern">How you want your money back?</label>
                        <select class="form-control-modern" required name="PK_PAYMENT_TYPE_REFUND" id="PK_PAYMENT_TYPE_REFUND">
                            <option value="">Select</option>
                            <?php
                            // === FIX: Guard the payment type loop ===
                            if ($payment_types && $payment_types->RecordCount() > 0) {
                                while (!$payment_types->EOF) {
                                    $pt_id   = safe_field($payment_types, 'PK_PAYMENT_TYPE', 0);
                                    $pt_name = safe_field($payment_types, 'PAYMENT_TYPE', '');
                            ?>
                                    <option value="<?= intval($pt_id) ?>"><?= htmlspecialchars($pt_name) ?></option>
                            <?php
                                    $payment_types->MoveNext();
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group-modern">
                        <label class="form-label-modern">How much refund you want?</label>
                        <input class="form-control-modern" name="REFUND_AMOUNT" id="REFUND_AMOUNT" value="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-modern btn-modern-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn-modern btn-modern-primary" onclick="$('.trigger_this').trigger('click');">
                        <i class="fas fa-check"></i> Process
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!--Move to Wallet Modal-->
    <div class="modal fade modal-modern" id="move_to_wallet_model" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog" style="max-width: 450px;">
            <div class="modal-content">
                <div class="modal-header">
                    <h4><i class="fas fa-wallet" style="color: var(--primary-color); margin-right: 8px;"></i> Move to Wallet</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group-modern">
                        <h5 style="font-size: 16px; font-weight: 500; color: var(--gray-700); margin: 0;">Are you sure you want to move <strong style="color: var(--primary-color);">$<span id="move_amount">0.00</span></strong> to wallet?</h5>
                        <input type="hidden" id="confirm_move" value="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-modern btn-modern-secondary" onclick="$('#confirm_move').val(0);$('#move_to_wallet_model').modal('hide');">No</button>
                    <button type="button" class="btn-modern btn-modern-primary" onclick="$('#confirm_move').val(1);$('.trigger_this').trigger('click');">
                        <i class="fas fa-check"></i> Yes
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!--Payment Model-->
    <?php include('includes/enrollment_payment.php'); ?>

    <?php require_once('../includes/footer.php'); ?>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        window.onload = function() {
            document.getElementById("enrollment_tab_link").click();
        };

        $('.datepicker-normal').datepicker({
            format: 'mm/dd/yyyy',
        });

        let PK_USER = parseInt(<?= empty($PK_USER) ? 0 : $PK_USER ?>);
        let PK_USER_MASTER = parseInt(<?= empty($PK_USER_MASTER) ? 0 : $PK_USER_MASTER ?>);

        var enr_tab_type = '';
        var page_count = 1;
        var loading = false;
        var hasMore = true;
        var observer;

        function showEnrollmentList(page, type) {
            enr_tab_type = type;
            let PK_USER_MASTER = $('.PK_USER_MASTER').val();
            let PK_USER = $('.PK_USER').val();

            loading = true;
            $("#load-marker").html('Loading <span class="spinner"></span>');

            $.ajax({
                url: "pagination/enrollment.php",
                type: "GET",
                data: {
                    search_text: '',
                    page: page,
                    type: type,
                    pk_user: PK_USER,
                    master_id: PK_USER_MASTER
                },
                cache: false,
                success: function(result) {
                    if (result && result.trim() !== "") {
                        $('#load-marker').before(result);
                        loading = false;
                    } else {
                        hasMore = false;
                        $("#load-marker").html('No more data');
                        if (observer) observer.disconnect();
                    }
                },
                error: function() {
                    loading = false;
                    $("#load-marker").html('Error loading data');
                }
            });
        }

        function enrollmentLoadMore(type) {
            enr_tab_type = type;
            page_count = 1;
            hasMore = true;
            loading = false;
            $("#enrollment_list").html('<div id="load-marker" class="loading-indicator">Loading <span class="spinner"></span></div>');

            showEnrollmentList(page_count, enr_tab_type);

            if (observer) observer.disconnect();

            observer = new IntersectionObserver(entries => {
                if (entries[0].isIntersecting && !loading && hasMore) {
                    page_count++;
                    showEnrollmentList(page_count, enr_tab_type);
                }
            }, {
                rootMargin: "300px",
                threshold: 0.1
            });

            observer.observe(document.querySelector("#load-marker"));
        }

        $(window).on("scroll", function() {
            if (!loading && hasMore && enr_tab_type != '') {
                if ($(window).scrollTop() + $(window).height() >= $(document).height() - 200) {
                    page_count++;
                    showEnrollmentList(page_count, enr_tab_type);
                }
            }
        });

        function payNow(PK_ENROLLMENT_MASTER, PK_ENROLLMENT_LEDGER, BILLED_AMOUNT) {
            $('.PK_ENROLLMENT_MASTER').val(PK_ENROLLMENT_MASTER);
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

        function moveToWallet(param, PK_ENROLLMENT_PAYMENT, PK_ENROLLMENT_MASTER, PK_ENROLLMENT_LEDGER, PK_USER_MASTER, BALANCE, ENROLLMENT_TYPE, TRANSACTION_TYPE, PAYMENT_COUNTER) {
            let PK_PAYMENT_TYPE = $('#PK_PAYMENT_TYPE_REFUND').val();
            let confirm_move = $('#confirm_move').val();
            if (TRANSACTION_TYPE == 'Refund' && PK_PAYMENT_TYPE == 0) {
                $('.trigger_this').removeClass('trigger_this');
                $(param).addClass('trigger_this');
                $('#REFUND_AMOUNT').val(BALANCE);
                $('#refund_modal').modal('show');
            } else {
                if (TRANSACTION_TYPE == 'Move' && confirm_move == 0) {
                    $('.trigger_this').removeClass('trigger_this');
                    $(param).addClass('trigger_this');
                    $('#move_amount').text(parseFloat(BALANCE).toFixed(2));
                    $('#move_to_wallet_model').modal('show');
                } else {
                    let REFUND_AMOUNT = $('#REFUND_AMOUNT').val();
                    if (REFUND_AMOUNT > BALANCE) {
                        alert("Refund amount can't be grater then balance");
                        $('#REFUND_AMOUNT').val(BALANCE);
                    } else {
                        $.ajax({
                            url: "ajax/AjaxFunctions.php",
                            type: 'POST',
                            data: {
                                FUNCTION_NAME: 'moveToWallet',
                                PK_ENROLLMENT_PAYMENT: PK_ENROLLMENT_PAYMENT,
                                PK_ENROLLMENT_MASTER: PK_ENROLLMENT_MASTER,
                                PK_ENROLLMENT_LEDGER: PK_ENROLLMENT_LEDGER,
                                PK_USER_MASTER: PK_USER_MASTER,
                                BALANCE: BALANCE,
                                REFUND_AMOUNT: REFUND_AMOUNT,
                                ENROLLMENT_TYPE: ENROLLMENT_TYPE,
                                TRANSACTION_TYPE: TRANSACTION_TYPE,
                                PK_PAYMENT_TYPE: PK_PAYMENT_TYPE
                            },
                            success: function(data) {
                                if (data == 1) {
                                    window.location.reload();
                                } else {
                                    alert(data);
                                }
                            }
                        });
                    }
                }
            }
        }

        function openReceipt(PK_ENROLLMENT_MASTER, RECEIPT_NUMBER) {
            let RECEIPT_NUMBER_ARRAY = RECEIPT_NUMBER.split(',');
            for (let i = 0; i < RECEIPT_NUMBER_ARRAY.length; i++) {
                window.open('generate_receipt_pdf.php?master_id=' + PK_ENROLLMENT_MASTER + '&receipt=' + RECEIPT_NUMBER_ARRAY[i], '_blank');
            }
        }

        $('#edit_due_date_form').on('submit', function(event) {
            event.preventDefault();

            let PK_ENROLLMENT_LEDGER = $('#PK_ENROLLMENT_LEDGER').val();
            let old_due_date = $('#old_due_date').val();
            let due_date = $('#due_date').val();
            let edit_type = $('#edit_type').val();
            let due_date_verify_password = $('#due_date_verify_password').val();

            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                data: {
                    FUNCTION_NAME: 'updateBillingDueDate',
                    PK_ENROLLMENT_LEDGER: PK_ENROLLMENT_LEDGER,
                    old_due_date: old_due_date,
                    due_date: due_date,
                    edit_type: edit_type,
                    due_date_verify_password: due_date_verify_password
                },
                success: function(data) {
                    $('#due_date_verify_password_error').slideUp();
                    if (data == 1) {
                        Swal.fire({
                            title: "Updated!",
                            text: "Due Date is Updated.",
                            icon: "success",
                            timer: 3000,
                        }).then((result) => {
                            $('#billing_due_date_model').modal('hide');
                            showEnrollmentList(1, 'normal');
                        });
                    } else {
                        $('#due_date_verify_password_error').text("Incorrect Password").slideDown();
                    }
                }
            });
        });
    </script>

</body>

</html>