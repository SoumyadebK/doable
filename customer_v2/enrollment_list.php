<?php
require_once('../global/config.php');
require_once("../global/stripe-php-master/init.php");

global $db;
global $db_account;
global $master_database;

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

// === Access control ===
if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 4) {
    header("location:../login.php");
    exit;
}

$PK_USER_MASTER = !empty($_GET['master_id']) ? intval($_GET['master_id']) : 0;
$PK_USER        = !empty($_GET['id'])        ? intval($_GET['id'])        : 0;

echo "<input type='hidden' class='PK_USER_MASTER' value='" . $PK_USER_MASTER . "'>";
echo "<input type='hidden' class='PK_USER' value='" . $PK_USER . "'>";

$PK_ACCOUNT_MASTER = $_SESSION['PK_ACCOUNT_MASTER'] ?? 0;

$account_data = $db->Execute("SELECT * FROM `DOA_ACCOUNT_MASTER` WHERE `PK_ACCOUNT_MASTER` = " . intval($PK_ACCOUNT_MASTER));
if (!$account_data || $account_data->RecordCount() === 0) {
    $PAYMENT_GATEWAY = '';
    $SECRET_KEY      = '';
    $PUBLISHABLE_KEY = '';
} else {
    $PAYMENT_GATEWAY = safe_field($account_data, 'PAYMENT_GATEWAY_TYPE', '');
    $SECRET_KEY      = safe_field($account_data, 'SECRET_KEY', '');
    $PUBLISHABLE_KEY = safe_field($account_data, 'PUBLISHABLE_KEY', '');
}

$results_per_page = 100;

if (isset($_GET['search_text']) && $_GET['search_text'] != '') {
    $search_text = $_GET['search_text'];
    $search = " AND (DOA_USERS.FIRST_NAME LIKE '%" . $search_text . "%' OR DOA_USERS.EMAIL_ID LIKE '%" . $search_text . "%' OR DOA_USERS.PHONE LIKE '%" . $search_text . "%')";
} else {
    $search_text = '';
    $search = ' ';
}

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

// === Payment POST handler ===
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

$PK_USER_MASTER = $PK_USER;
if ($PK_USER_MASTER > 0) {
    makeExpiryEnrollmentComplete($PK_USER_MASTER);
    makeMiscComplete($PK_USER_MASTER);
    makeDroppedCancelled($PK_USER_MASTER);
    checkAllEnrollmentStatus($PK_USER_MASTER);
}

$payment_types = $db->Execute("SELECT * FROM DOA_PAYMENT_TYPE WHERE PAYMENT_TYPE = 'Credit Card' AND ACTIVE = 1");

// For refund / cancel enrollment modals
$all_payment_types = $db->Execute("SELECT * FROM DOA_PAYMENT_TYPE WHERE ACTIVE = 1");
?>

<!DOCTYPE html>
<html lang="en">
<?php include 'layout/header_script.php'; ?>

<?php include 'layout/header.php'; ?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

<style>
    /* ==================== ROOT VARIABLES ==================== */
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

    /* ==================== ENROLLMENT CONTAINER ==================== */
    .enrollment-container {
        background: #fff;
        border: 1px solid #e0e0e0;
        border-radius: 12px;
        padding: 15px 30px;
        margin: auto;
    }

    /* ==================== BALANCE STATS ==================== */
    .stat-label {
        font-size: 0.85rem;
        color: #6c757d;
        margin-bottom: 5px;
    }

    .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1a1a1a;
        line-height: 25px;
    }

    .stat-divider {
        border-left: 1px solid #eee;
        height: 50px;
        margin: 0 40px;
    }

    /* ==================== VIEW TOGGLE ==================== */
    .view-toggle {
        display: flex;
        gap: 8px;
    }

    .view-btn-icon {
        border: 1px solid #dee2e6;
        background: #fff;
        color: #495057;
        font-size: 0.85rem;
        font-weight: 500;
        padding: 6px 16px;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .view-btn-icon:hover {
        background-color: #39b54a !important;
        color: #fff !important;
    }

    .view-btn-icon.active {
        background-color: #39b54a !important;
        color: #fff !important;
    }

    /* ==================== TABLES ==================== */
    .table {
        border: 1px solid #eee;
        border-radius: 8px;
        overflow: hidden;
        border-collapse: separate;
        border-spacing: 0;
    }

    .table thead th {
        background-color: #f8f9fa;
        color: #6c757d;
        font-weight: 500;
        font-size: 0.85rem;
        border-bottom: 1px solid #eee;
        padding: 12px 15px;
    }

    .table tbody td {
        vertical-align: middle;
        padding: 15px;
        border-bottom: 1px solid #f1f1f1;
        font-size: 0.85rem;
        color: #333;
    }

    .table tfoot td {
        padding: 12px 15px;
    }

    .table-responsive {
        border: none;
    }

    /* ==================== AUTO-PAY TOGGLE ==================== */
    .form-switch .form-check-input {
        width: 2.5em;
        height: 1.25em;
        cursor: pointer;
    }

    .autopay-label {
        font-size: 0.85rem;
        color: #444;
        font-weight: 500;
    }

    /* ==================== VIEW SCHEDULE LINK ==================== */
    .view-schedule {
        font-size: 0.85rem;
        color: #6c757d;
        text-decoration: none;
    }

    .view-schedule:hover {
        text-decoration: underline;
    }

    /* ==================== PAID BADGE ==================== */
    .checkicon {
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    /* ==================== BUTTONS ==================== */
    .btn-outline-edit {
        border: 1px solid #e0e0e0;
        color: #333;
        font-size: 0.85rem;
        padding: 5px 15px;
        border-radius: 20px;
        transition: all 0.3s ease;
        background: #fff;
    }

    .btn-outline-edit:hover {
        background-color: #2e9e3d !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(57, 181, 74, 0.35);
        cursor: pointer;
        color: #fff !important;
    }

    /* ==================== LOADER MARKER ==================== */
    #load-marker {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        width: 100%;
        min-height: 120px;
        margin: 24px auto;
        color: #344054;
    }

    #load-marker .loader-ring {
        width: 28px;
        height: 28px;
        border: 4px solid rgba(57, 181, 74, 0.24);
        border-top-color: #39b54a;
        border-radius: 50%;
        animation: loader-spin 0.85s linear infinite;
    }

    #load-marker .loader-text {
        font-size: 0.95rem;
        font-weight: 600;
        letter-spacing: 0.01em;
        color: #252f3f;
    }

    @keyframes loader-spin {
        to {
            transform: rotate(360deg);
        }
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

    /* ==================== MODAL MODERN ==================== */
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

    /* ==================== FORM CONTROLS ==================== */
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

    .form-group-modern {
        margin-bottom: 16px;
    }

    /* ==================== MODERN BUTTONS ==================== */
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

    /* ==================== SERVICE CODE BADGE ==================== */
    .badge-service {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    /* ==================== BUTTON SECONDARY OVERRIDE ==================== */
    .btn.btn-secondary {
        padding: 5px 15px;
        font-size: 12px;
        background-color: #39b54a;
        border-color: #39b54a;
        color: #fff;
    }

    .btn.btn-secondary:hover {
        background-color: #2e9e3d;
        border-color: #2e9e3d;
    }

    /* ==================== FORM SWITCH ==================== */
    .form-check.form-switch {
        padding-left: 2.5em;
    }

    /* ==================== CARD MODERN ==================== */
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

    /* ==================== TABS MODERN ==================== */
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

    /* ==================== ENROLLMENT LIST WRAPPER ==================== */
    .enrollment-list-wrapper {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    /* ==================== RESPONSIVE ==================== */
    @media (max-width: 768px) {
        .card-modern .card-body {
            padding: 16px;
        }

        .container-fluid {
            padding: 16px !important;
        }

        .enrollment-container {
            padding: 15px 15px;
        }

        .stat-divider {
            margin: 0 15px;
            height: 40px;
        }

        .stat-value {
            font-size: 1.2rem;
        }
    }

    /* ==================== CANCEL ENROLLMENT MODAL ==================== */
    #enrollment_cancel_modal .modal-body .card {
        border: none;
        box-shadow: none;
    }

    #enrollment_cancel_modal .form-group label {
        font-size: 0.9rem;
        color: #333;
    }

    #enrollment_cancel_modal .btn-secondary {
        padding: 6px 18px;
        font-size: 13px;
    }

    /* ==================== REFUND / MOVE MODAL ==================== */
    #refund_modal .form-group-modern,
    #move_to_wallet_model .form-group-modern {
        margin-bottom: 16px;
    }

    /* ==================== CREDIT CARD AUTO PAY ==================== */
    #saved_credit_card_list_auto_pay .credit-card-div {
        border: 1px solid #e0e0e0;
        border-radius: 10px;
        padding: 12px 16px;
        margin-bottom: 10px;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    #saved_credit_card_list_auto_pay .credit-card-div:hover {
        border-color: #39b54a;
        box-shadow: 0 2px 8px rgba(57, 181, 74, 0.15);
    }

    /* ==================== DELETE ENROLLMENT MODAL ==================== */
    #delete_enrollment_model .modal-body label {
        font-size: 0.9rem;
        color: #333;
    }


    /* ==================== PAGINATION / DATATABLE ==================== */
    .pagination .page-item.active .page-link {
        background-color: #39b54a;
        border-color: #39b54a;
    }

    .page-link {
        color: #39b54a;
        font-size: 13px;
    }

    .page-link:hover {
        color: #1a1d23;
    }

    /* ==================== SPINNER IN BUTTONS ==================== */
    .spinner-border-sm {
        width: 1rem;
        height: 1rem;
        border-width: 0.15em;
    }

    /* ==================== UTILITY CLASSES ==================== */
    .gap-2 {
        gap: 0.5rem !important;
    }

    .fw-bold {
        font-weight: 700 !important;
    }

    .text-muted {
        color: #6c757d !important;
    }

    .text-primary {
        color: #39b54a !important;
    }

    .text-success {
        color: #39b54a !important;
    }

    .text-danger {
        color: #EF4444 !important;
    }

    .mb-0 {
        margin-bottom: 0 !important;
    }

    .mb-1 {
        margin-bottom: 0.25rem !important;
    }

    .mb-2 {
        margin-bottom: 0.5rem !important;
    }

    .mb-3 {
        margin-bottom: 1rem !important;
    }

    .mb-4 {
        margin-bottom: 1.5rem !important;
    }

    .mt-2 {
        margin-top: 0.5rem !important;
    }

    .mt-3 {
        margin-top: 1rem !important;
    }

    .mt-4 {
        margin-top: 1.5rem !important;
    }

    .p-20 {
        padding: 20px !important;
    }

    .ms-2 {
        margin-left: 0.5rem !important;
    }

    .me-2 {
        margin-right: 0.5rem !important;
    }

    .ms-auto {
        margin-left: auto !important;
    }

    .text-center {
        text-align: center !important;
    }

    .text-end {
        text-align: right !important;
    }

    .d-flex {
        display: flex !important;
    }

    .d-none {
        display: none !important;
    }

    .align-items-center {
        align-items: center !important;
    }

    .justify-content-between {
        justify-content: space-between !important;
    }

    .justify-content-end {
        justify-content: flex-end !important;
    }

    .flex-nowrap {
        flex-wrap: nowrap !important;
    }

    .w-100 {
        width: 100% !important;
    }

    /* ==================== FULL WIDTH FIX ==================== */
    html,
    body {
        width: 100%;
        max-width: 100%;
        overflow-x: hidden;
    }

    #main-wrapper {
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .page-wrapper {
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 !important;
    }

    .dashboard-container {
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 !important;
        padding: 24px 24px !important;
    }

    /* Make sure cards inside stretch */
    .dashboard-container .row,
    .dashboard-container .col-12,
    .dashboard-container .card-modern {
        width: 100% !important;
        max-width: 100% !important;
    }

    /* Enrollment cards themselves should stretch */
    .enrollment-container {
        width: 100% !important;
        max-width: 100% !important;
    }

    /* Table should fill its wrapper */
    .enrollment-container .table-responsive {
        width: 100% !important;
    }

    .enrollment-container .table {
        width: 100% !important;
    }

    /* Fix profile name vertical alignment */
    .nav-user .topbar-link {
        display: flex !important;
        align-items: center !important;
        height: 100%;
        margin-top: 0 !important;
    }

    .nav-user .topbar-link img {
        display: block !important;
        flex-shrink: 0;
    }

    .nav-user .topbar-link>div {
        display: flex !important;
        align-items: center !important;
    }

    .nav-user .pro-username {
        margin: 0 !important;
        line-height: 1 !important;
    }
</style>

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">
        <?php require_once('../includes/header.php'); ?>
        <div class="page-wrapper" style="padding-top: 0px !important;">
            <div class="container-fluid py-4 px-4 m-auto mx-auto dashboard-container">

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

    <!-- ==================== MODALS ==================== -->

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
                        <button type="submit" class="btn-modern btn-modern-primary"><i class="fas fa-check"></i> Process</button>
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
                            if ($all_payment_types && $all_payment_types->RecordCount() > 0) {
                                while (!$all_payment_types->EOF) {
                                    $pt_id   = safe_field($all_payment_types, 'PK_PAYMENT_TYPE', 0);
                                    $pt_name = safe_field($all_payment_types, 'PAYMENT_TYPE', '');
                            ?>
                                    <option value="<?= intval($pt_id) ?>"><?= htmlspecialchars($pt_name) ?></option>
                            <?php
                                    $all_payment_types->MoveNext();
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

    <!--Auto-pay Credit Card Modal-->
    <div class="modal fade modal-modern" id="credit_card_modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4><i class="fas fa-credit-card" style="color: var(--primary-color); margin-right: 8px;"></i> Select Credit Card for Auto-Pay</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if ($PAYMENT_GATEWAY == null || $PAYMENT_GATEWAY == '') { ?>
                        <div class="alert alert-danger">Payment Gateway is Not set Yet</div>
                    <?php } else { ?>
                        <div class="row">
                            <div class="col-md-12">
                                <div id="add_credit_card_div_auto_pay" style="display: none;"></div>
                            </div>
                        </div>
                        <div class="row" id="saved_credit_card_list_auto_pay" style="display: none;"></div>
                    <?php } ?>
                    <input type="hidden" name="AUTO_PAY_ENROLLMENT_ID" id="AUTO_PAY_ENROLLMENT_ID">
                    <input type="hidden" name="AUTO_PAY_PAYMENT_METHOD_ID" id="AUTO_PAY_PAYMENT_METHOD_ID">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-modern btn-modern-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn-modern btn-modern-primary" onclick="addEnrollmentAutoPayCreditCard()">Process</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Cancel enrollment modal -->
    <div class="modal fade" id="enrollment_cancel_modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog" style="max-width: 700px !important;">
            <form class="p-20" id="cancel_enrollment_form">
                <input type="hidden" name="SOURCE" value="CANCEL_MODAL">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4><b>Cancel Enrollment</b></h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="card">
                            <div class="card-body">
                                <div id="step_1">
                                    <input type="hidden" name="PK_ENROLLMENT_MASTER" class="PK_ENROLLMENT_MASTER">
                                    <input type="hidden" name="PK_USER_MASTER" class="PK_USER_MASTER">
                                    <div class="form-group mb-4">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <label>Cancel All Future Appointments for <span class="enrollment_title"></span>? <input type="radio" name="CANCEL_FUTURE_APPOINTMENT" id="CANCEL_FUTURE_APPOINTMENT_1" value="1" checked /></label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group mb-4">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <label>Cancel Only Unpaid Future Appointments for <span class="enrollment_title"></span>? <input type="radio" name="CANCEL_FUTURE_APPOINTMENT" id="CANCEL_FUTURE_APPOINTMENT_2" value="2" /></label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group mb-4">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <label>Move Future Appointments As Ad-Hoc for <span class="enrollment_title"></span>? <input type="radio" name="CANCEL_FUTURE_APPOINTMENT" id="CANCEL_FUTURE_APPOINTMENT_3" value="3" /></label>
                                            </div>
                                        </div>
                                    </div>
                                    <a href="javascript:" class="btn btn-secondary" style="float: right;" onclick="$('#step_1').hide();$('#step_2').show();">Continue</a>
                                </div>

                                <div id="step_2" style="display: none;">
                                    <div class="form-group mb-4">
                                        <div class="row">
                                            <div class="col-md-10"><label>Use available credits to pay pending balances?</label></div>
                                            <div class="col-md-2"><label><input type="radio" name="USE_AVAILABLE_CREDIT" value="1" checked />&nbsp;Yes</label>&nbsp;&nbsp;</div>
                                        </div>
                                    </div>
                                    <a href="javascript:" class="btn btn-secondary next" style="float: right;" onclick="$('#step_2').hide();$('#step_3').show();showEnrollmentServiceDetails();">Continue</a>
                                    <a href="javascript:" class="btn btn-secondary cancel prev" onclick="$('#step_2').hide();$('#step_1').show();">Go Back</a>
                                </div>

                                <div id="step_3" style="display: none;">
                                    <div id="enrollment_service_details"></div>
                                    <div class="form-group mb-4 negative_balance_div" style="display: none;">
                                        <div class="row"><b>Note: Please pay $<span id="total_negative_balance"></span> to cancel your enrollment.</b></div>
                                    </div>
                                    <div class="form-group mb-2 credit_balance_div" style="display: none;">
                                        <label class="form-label">Refund Method?</label>
                                        <div class="col-md-8">
                                            <select class="form-control" name="PK_PAYMENT_TYPE_REFUND" id="PK_PAYMENT_TYPE_REFUND" onchange="selectRefundType(this)">
                                                <option value="">Select</option>
                                                <?php
                                                $row_pt = $db->Execute("SELECT * FROM DOA_PAYMENT_TYPE WHERE ACTIVE = 1");
                                                while (!$row_pt->EOF) { ?>
                                                    <option value="<?php echo $row_pt->fields['PK_PAYMENT_TYPE']; ?>"><?= $row_pt->fields['PAYMENT_TYPE'] ?></option>
                                                <?php $row_pt->MoveNext();
                                                } ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group mb-4 credit_balance_div" style="display: none;">
                                        <div class="row"><b>Note: Credit balance $<span id="total_credit_balance"></span> will be moved to Wallet.</b></div>
                                    </div>
                                    <div class="row mb-4 check_payment" style="display: none;">
                                        <div class="col-6">
                                            <div class="form-group"><label class="form-label">Check Number</label><input type="text" name="REFUND_CHECK_NUMBER" id="REFUND_CHECK_NUMBER" class="form-control"></div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-group"><label class="form-label">Check Date</label><input type="text" name="REFUND_CHECK_DATE" id="REFUND_CHECK_DATE" class="form-control datepicker-normal"></div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="SUBMIT" id="SUBMIT">
                                    <button type="submit" class="btn btn-secondary" id="cancel_and_store_btn" onclick="$('#SUBMIT').val('Cancel and Store Info only');" style="float: right;">Cancel and Store Info only <span><i class="fa fa-info-circle"></i></span></button>
                                    <button type="submit" class="btn btn-secondary" onclick="$('#SUBMIT').val('Submit');" style="float: right; margin-right: 5px;">Submit</button>
                                    <a href="javascript:" class="btn btn-secondary" onclick="$('#step_3').hide();$('#step_2').show();">Go Back</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Enrollment Modal -->
    <div class="modal fade" id="delete_enrollment_model" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form id="delete_enrollment_form" method="post">
                <input type="hidden" name="FUNCTION_NAME" value="deleteActiveEnrollmentData">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4><b>Delete Enrollment</b></h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <input type="hidden" name="PK_ENROLLMENT_MASTER" id="DELETE_ENROLLMENT_ID">
                    <div class="modal-body">
                        <div class="row p-20">
                            <div>
                                <label><input type="radio" id="delete_type_1" name="delete_type" value="1" checked>&nbsp;&nbsp;&nbsp;Delete All Appointment</label><br><br>
                                <label><input type="radio" id="delete_type_0" name="delete_type" value="0">&nbsp;&nbsp;&nbsp;Move Appointment to Ad-Hoc</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-secondary" style="float: right;">Process</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php require_once('../includes/footer.php'); ?>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        window.onload = function() {
            document.getElementById("enrollment_tab_link").click();
        };

        $('.datepicker-normal').datepicker({
            format: 'mm/dd/yyyy'
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
            let masterId = $('.PK_USER_MASTER').val();
            let userId = $('.PK_USER').val();
            loading = true;
            $("#load-marker").html('Loading <span class="spinner"></span>');
            $.ajax({
                url: "pagination/enrollment.php",
                type: "GET",
                data: {
                    search_text: '',
                    page: page,
                    type: type,
                    pk_user: userId,
                    master_id: masterId
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

        // ==================== PORTED FUNCTIONS FROM ADMIN CUSTOMER PAGE ====================

        function showEnrollmentDetails(param, PK_USER, PK_USER_MASTER, PK_ENROLLMENT_MASTER, ENROLLMENT_ID, type, details) {
            let enrollmentDetails = $(param).closest('.enrollment_div').find('.enrollment_details');
            if (enrollmentDetails.find('#myTable').length > 0) {
                enrollmentDetails.slideToggle();
                return;
            }
            $(param).html(`<span class="d-flex align-items-center gap-2">View Payment Schedule <div class="spinner-border spinner-border-sm text-success" role="status"></div></span>`);
            $.ajax({
                url: "partials/ajaxList/customer_enrollment_details.php",
                type: "GET",
                data: {
                    PK_USER: PK_USER,
                    PK_USER_MASTER: PK_USER_MASTER,
                    PK_ENROLLMENT_MASTER: PK_ENROLLMENT_MASTER,
                    ENROLLMENT_ID: ENROLLMENT_ID,
                    type: type
                },
                cache: false,
                success: function(result) {
                    enrollmentDetails.html(result).slideDown();
                    $(param).html(`View Payment Schedule`);
                }
            });
        }

        function openReceipt(PK_ENROLLMENT_MASTER, RECEIPT_NUMBER) {
            let RECEIPT_NUMBER_ARRAY = RECEIPT_NUMBER.split(',');
            for (let i = 0; i < RECEIPT_NUMBER_ARRAY.length; i++) {
                window.open('generate_receipt_pdf.php?master_id=' + PK_ENROLLMENT_MASTER + '&receipt=' + RECEIPT_NUMBER_ARRAY[i], '_blank');
            }
        }

        function changeEnrollmentAutoPay(PK_ENROLLMENT_MASTER) {
            var checkbox = event.target;
            var isRecipient = checkbox.checked ? 1 : 0;
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                data: {
                    FUNCTION_NAME: 'changeEnrollmentAutoPay',
                    PK_ENROLLMENT_MASTER: PK_ENROLLMENT_MASTER,
                    ACTIVE_AUTO_PAY: isRecipient
                },
                success: function(data) {}
            });
        }

        function addEnrollmentAutoPay(PK_ENROLLMENT_MASTER) {
            $('#AUTO_PAY_ENROLLMENT_ID').val(PK_ENROLLMENT_MASTER);
            getSavedCreditCardListAutoPay();
        }

        function getSavedCreditCardListAutoPay() {
            let masterId = <?= $PK_USER_MASTER ?>;
            $('#credit_card_modal').modal('show');
            $.ajax({
                url: "ajax/get_credit_card_list.php",
                type: 'POST',
                data: {
                    PK_USER_MASTER: masterId,
                    call_from: 'enrollment_auto_pay'
                },
                success: function(data) {
                    $('#saved_credit_card_list_auto_pay').slideDown().html(data);
                    addCreditCardAutoPay();
                }
            });
        }

        function selectAutoPayCreditCard(param) {
            let payment_id = $(param).attr('id');
            $('.credit-card-div').css("opacity", "1");
            $(param).css("opacity", "0.6");
            $('#AUTO_PAY_PAYMENT_METHOD_ID').val(payment_id);
        }

        function addCreditCardAutoPay() {
            let userId = <?= $PK_USER ?>;
            let masterId = <?= $PK_USER_MASTER ?>;
            $.ajax({
                url: "includes/save_credit_card.php",
                type: 'POST',
                data: {
                    PK_USER: userId,
                    PK_USER_MASTER: masterId,
                    call_from: 'enrollment_auto_pay'
                },
                success: function(data) {
                    $('#add_credit_card_div_auto_pay').slideDown().html(data);
                }
            });
        }

        function addEnrollmentAutoPayCreditCard() {
            let PK_ENROLLMENT_MASTER = $('#AUTO_PAY_ENROLLMENT_ID').val();
            let PAYMENT_METHOD_ID = $('#AUTO_PAY_PAYMENT_METHOD_ID').val();
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                data: {
                    FUNCTION_NAME: 'addEnrollmentAutoPay',
                    PK_ENROLLMENT_MASTER: PK_ENROLLMENT_MASTER,
                    PAYMENT_METHOD_ID: PAYMENT_METHOD_ID
                },
                success: function(data) {
                    if (data == 1) {
                        Swal.fire({
                                title: "Success!",
                                text: "Auto Pay Added for this Enrollment.",
                                icon: "success",
                                timer: 2000
                            })
                            .then(() => window.location.reload());
                    } else {
                        Swal.fire({
                            title: "Error!",
                            text: "Something went wrong, please try again.",
                            icon: "error",
                            timer: 3000
                        });
                    }
                }
            });
        }

        function payNow(PK_ENROLLMENT_MASTER, PK_ENROLLMENT_LEDGER, BILLED_AMOUNT, ENROLLMENT_ID) {
            $('.partial_payment').show();
            $('#PARTIAL_PAYMENT').prop('checked', false);
            $('.partial_payment_div').slideUp();
            $('.PAYMENT_TYPE').val('');
            $('#remaining_amount_div').slideUp();
            $('#enrollment_number').text(ENROLLMENT_ID);
            $('.PK_ENROLLMENT_MASTER').val(PK_ENROLLMENT_MASTER);
            $('.PK_ENROLLMENT_LEDGER').val(PK_ENROLLMENT_LEDGER);
            $('#ACTUAL_AMOUNT').val(BILLED_AMOUNT);
            $('#AMOUNT_TO_PAY').val(BILLED_AMOUNT);
            $('#enrollment_payment_modal').modal('show');
        }

        function paySelected(PK_ENROLLMENT_MASTER, ENROLLMENT_ID) {
            $('.partial_payment').hide();
            $('#PARTIAL_PAYMENT').prop('checked', false);
            $('.partial_payment_div').slideUp();
            $('.PAYMENT_TYPE').val('');
            $('#remaining_amount_div').slideUp();
            let BILLED_AMOUNT = [];
            let PK_ENROLLMENT_LEDGER = [];
            $(".PAYMENT_CHECKBOX_" + PK_ENROLLMENT_MASTER + ":checked").each(function() {
                BILLED_AMOUNT.push(parseFloat($(this).data('billed_amount')));
                PK_ENROLLMENT_LEDGER.push($(this).val());
            });
            let TOTAL = BILLED_AMOUNT.reduce((t, n) => t + n, 0);
            $('#enrollment_number').text(ENROLLMENT_ID);
            $('.PK_ENROLLMENT_MASTER').val(PK_ENROLLMENT_MASTER);
            $('.PK_ENROLLMENT_LEDGER').val(PK_ENROLLMENT_LEDGER);
            $('#ACTUAL_AMOUNT').val(parseFloat(TOTAL).toFixed(2));
            $('#AMOUNT_TO_PAY').val(parseFloat(TOTAL).toFixed(2));
            $('#enrollment_payment_modal').modal('show');
        }

        function moveToWallet(param, PK_ENROLLMENT_PAYMENT, PK_ENROLLMENT_MASTER, PK_ENROLLMENT_LEDGER, PK_USER_MASTER, BALANCE, ENROLLMENT_TYPE, TRANSACTION_TYPE, PAYMENT_COUNTER) {
            let PK_PAYMENT_TYPE = $('#refund_modal #PK_PAYMENT_TYPE_REFUND').val();
            let confirm_move = $('#confirm_move').val();
            if (TRANSACTION_TYPE == 'Refund' && PK_PAYMENT_TYPE == 0) {
                $('.trigger_this').removeClass('trigger_this');
                $(param).addClass('trigger_this');
                $('#refund_modal').modal('show');
                $('#refund_modal #REFUND_AMOUNT').val(BALANCE);
            } else {
                if (TRANSACTION_TYPE == 'Move' && confirm_move == 0) {
                    $('.trigger_this').removeClass('trigger_this');
                    $(param).addClass('trigger_this');
                    $('#move_amount').text(parseFloat(BALANCE).toFixed(2));
                    $('#move_to_wallet_model').modal('show');
                } else {
                    let REFUND_AMOUNT = $('#REFUND_AMOUNT').val();
                    if (REFUND_AMOUNT > BALANCE) {
                        alert("Refund amount can't be greater than balance");
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

        function editBillingDueDate(param, PK_ENROLLMENT_LEDGER, DUE_DATE, TYPE) {
            $('#PK_ENROLLMENT_LEDGER').val(PK_ENROLLMENT_LEDGER);
            $('#old_due_date').val(DUE_DATE);
            $('#due_date').val(DUE_DATE);
            $('#edit_type').val(TYPE);
            $('.trigger_this_enr_details').removeClass('trigger_this_enr_details');
            $(param).closest('.enrollment_div').find('.enrollment_details').html('');
            $(param).closest('.enrollment-container').find('.show_enrollment_details_button').addClass('trigger_this_enr_details');
            $('#billing_due_date_model').modal('show');
        }

        function getEditHistory(param, PK_ENROLLMENT_LEDGER, type) {
            $.ajax({
                url: "includes/get_update_history.php",
                type: 'GET',
                data: {
                    PK_ENROLLMENT_LEDGER: PK_ENROLLMENT_LEDGER,
                    CLASS: type,
                    FIELD_NAME: 'DUE_DATE'
                },
                success: function(data) {
                    $(param).popover({
                        title: 'Due Date Update Details',
                        placement: 'top',
                        trigger: 'hover',
                        content: data,
                        container: 'body',
                        html: true
                    }).popover('show');
                }
            });
        }

        function deletePayment(PK_ENROLLMENT_PAYMENT, PK_ENROLLMENT_MASTER, PK_ENROLLMENT_LEDGER, BALANCE) {
            Swal.fire({
                title: "Are you sure you want to delete this payment?",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, delete it!"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "ajax/AjaxFunctions.php",
                        type: 'POST',
                        data: {
                            FUNCTION_NAME: 'deletePayment',
                            PK_ENROLLMENT_PAYMENT: PK_ENROLLMENT_PAYMENT,
                            PK_ENROLLMENT_MASTER: PK_ENROLLMENT_MASTER,
                            PK_ENROLLMENT_LEDGER: PK_ENROLLMENT_LEDGER,
                            BALANCE: BALANCE
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
            });
        }

        function markMiscComplete(PK_ENROLLMENT_MASTER) {
            Swal.fire({
                title: "Are you sure you want to mark this enrollment as complete?",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, mark it complete!"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "ajax/AjaxFunctions.php",
                        type: 'POST',
                        data: {
                            FUNCTION_NAME: 'markMiscComplete',
                            PK_ENROLLMENT_MASTER: PK_ENROLLMENT_MASTER
                        },
                        success: function(data) {
                            if (data == 1) {
                                Swal.fire({
                                        title: "Success!",
                                        text: "Enrollment marked as complete.",
                                        icon: "success",
                                        timer: 2000
                                    })
                                    .then(() => window.location.reload());
                            } else {
                                alert(data);
                            }
                        }
                    });
                }
            });
        }

        function toggleEnrollmentCheckboxes(PK_ENROLLMENT_MASTER) {
            let toggleCheckbox = document.getElementById('toggleEnrollment_' + PK_ENROLLMENT_MASTER);
            let childCheckboxes = document.getElementsByClassName('PAYMENT_CHECKBOX_' + PK_ENROLLMENT_MASTER);
            let payNow = document.getElementById('payNow');
            if (toggleCheckbox.checked) {
                for (let i = 0; i < childCheckboxes.length; i++) {
                    childCheckboxes[i].checked = true;
                    payNow.disabled = true;
                }
            } else {
                for (let i = 0; i < childCheckboxes.length; i++) {
                    childCheckboxes[i].checked = false;
                    payNow.disabled = false;
                }
            }
        }

        $(document).on('change', '.pay_now_check', function() {
            if ($('.pay_now_check').is(':checked')) {
                $('.pay_selected_btn').prop('disabled', false);
                $('.pay_now_button').prop('disabled', true);
            } else {
                $('.pay_selected_btn').prop('disabled', true);
                $('.pay_now_button').prop('disabled', false);
            }
        });

        function mailAgreementToCustomer(enrollment_id) {
            Swal.fire({
                title: "Mail to Customer",
                text: "Are you sure you want to mail the agreement to the customer?",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, mail it!",
                cancelButtonText: "Cancel"
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: "Sending...",
                        text: "Please wait",
                        icon: "info",
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    $.ajax({
                        url: "ajax/AjaxFunctions.php",
                        type: 'POST',
                        data: {
                            FUNCTION_NAME: 'mailAgreementToCustomer',
                            enrollment_id: enrollment_id
                        },
                        dataType: 'json',
                        success: function(data) {
                            if (data.success) {
                                Swal.fire({
                                    title: "Success!",
                                    text: "The agreement has been mailed to the customer.",
                                    icon: "success",
                                    timer: 3000
                                });
                            } else {
                                Swal.fire({
                                    title: "Error!",
                                    text: "Something went wrong, please try again.",
                                    icon: "error",
                                    timer: 3000
                                });
                            }
                        }
                    });
                }
            });
        }

        function mailReceiptToCustomer(PK_ENROLLMENT_MASTER, RECEIPT_NUMBER) {
            Swal.fire({
                title: "Mail to Customer",
                text: "Are you sure you want to mail the receipt to the customer?",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, mail it!",
                cancelButtonText: "Cancel"
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: "Sending...",
                        text: "Please wait",
                        icon: "info",
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    $.ajax({
                        url: "ajax/AjaxFunctions.php",
                        type: 'POST',
                        data: {
                            FUNCTION_NAME: 'mailReceiptToCustomer',
                            PK_ENROLLMENT_MASTER: PK_ENROLLMENT_MASTER,
                            RECEIPT_NUMBER: RECEIPT_NUMBER
                        },
                        dataType: 'json',
                        success: function(data) {
                            if (data.success) {
                                Swal.fire({
                                    title: "Success!",
                                    text: "The receipt has been mailed to the customer.",
                                    icon: "success",
                                    timer: 3000
                                });
                            } else {
                                Swal.fire({
                                    title: "Error!",
                                    text: "Something went wrong, please try again.",
                                    icon: "error",
                                    timer: 3000
                                });
                            }
                        }
                    });
                }
            });
        }

        // ==================== CANCEL ENROLLMENT FUNCTIONS ====================

        function cancelEnrollment(PK_ENROLLMENT_MASTER, PK_USER_MASTER, enrollment_title) {
            $('.PK_ENROLLMENT_MASTER').val(PK_ENROLLMENT_MASTER);
            $('.PK_USER_MASTER').val(PK_USER_MASTER);
            $('.enrollment_title').text(enrollment_title);
            $('#CANCEL_FUTURE_APPOINTMENT_3').prop('checked', false);
            $('#CANCEL_FUTURE_APPOINTMENT_2').prop('checked', false);
            $('#CANCEL_FUTURE_APPOINTMENT_1').prop('checked', true);
            $('#step_3').hide();
            $('#step_2').hide();
            $('#step_1').show();
            $('#enrollment_cancel_modal').modal('show');
        }

        function selectRefundType(param) {
            let paymentType = parseInt($(param).val());
            if (paymentType === 2) {
                $(param).closest('.modal-body').find('.check_payment').slideDown();
            } else {
                $(param).closest('.modal-body').find('.check_payment').slideUp();
            }
        }

        function showEnrollmentServiceDetails() {
            let PK_ENROLLMENT_MASTER = $('.PK_ENROLLMENT_MASTER').val();
            let USE_AVAILABLE_CREDIT = $('input[name="USE_AVAILABLE_CREDIT"]:checked').val();
            let CANCEL_FUTURE_APPOINTMENT = $('input[name="CANCEL_FUTURE_APPOINTMENT"]:checked').val();
            $.ajax({
                url: "includes/enrollment_service_details.php",
                type: 'GET',
                data: {
                    PK_ENROLLMENT_MASTER: PK_ENROLLMENT_MASTER,
                    USE_AVAILABLE_CREDIT: USE_AVAILABLE_CREDIT,
                    CANCEL_FUTURE_APPOINTMENT: CANCEL_FUTURE_APPOINTMENT
                },
                success: function(data) {
                    $('#enrollment_service_details').html(data);
                    $('.negative_balance_div').slideUp();
                    $('.credit_balance_div').slideUp();
                    let TOTAL_POSITIVE_BALANCE = parseFloat($('#TOTAL_POSITIVE_BALANCE').val());
                    let TOTAL_NEGATIVE_BALANCE = parseFloat($('#TOTAL_NEGATIVE_BALANCE').val());
                    if (USE_AVAILABLE_CREDIT == 1) {
                        TOTAL_POSITIVE_BALANCE += TOTAL_NEGATIVE_BALANCE;
                        TOTAL_NEGATIVE_BALANCE = TOTAL_POSITIVE_BALANCE;
                    }
                    if (TOTAL_POSITIVE_BALANCE > 0) {
                        $('.credit_balance_div').slideDown();
                        $('#total_credit_balance').text(parseFloat(TOTAL_POSITIVE_BALANCE).toFixed(2));
                        $('#cancel_and_store_btn').attr('title', 'Cancels the enrollment but keeps it in the Active tab so the remaining credit can be refunded or moved to the wallet later.');
                    }
                    if (TOTAL_NEGATIVE_BALANCE < 0) {
                        $('.negative_balance_div').slideDown();
                        $('#total_negative_balance').text(Math.abs(parseFloat(TOTAL_NEGATIVE_BALANCE).toFixed(2)));
                        $('#cancel_and_store_btn').attr('title', 'Cancels the enrollment but keeps it in the Active tab so the outstanding balance can be collected later.');
                    }
                }
            });
        }

        $(document).on('submit', '#cancel_enrollment_form', function(event) {
            event.preventDefault();
            let form_data = new FormData($('#cancel_enrollment_form')[0]);
            $.ajax({
                url: "includes/cancel_customer_enrollment.php",
                type: 'POST',
                data: form_data,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(result) {
                    let response = result;
                    if (response.STATUS == 'Billing') {
                        $('#enrollment_cancel_modal').modal('hide');
                        let PK_ENROLLMENT_LEDGER = response.PK_ENROLLMENT_LEDGER;
                        let BILLED_AMOUNT = response.BILLED_AMOUNT;
                        let PK_ENROLLMENT_MASTER = response.PK_ENROLLMENT_MASTER;
                        payNow(PK_ENROLLMENT_MASTER, PK_ENROLLMENT_LEDGER, BILLED_AMOUNT, '');
                    } else {
                        Swal.fire({
                                title: "Enrollment Cancelled!",
                                text: "The enrollment has been cancelled successfully.",
                                icon: "success",
                                timer: 3000
                            })
                            .then(() => window.location.reload());
                    }
                }
            });
        });

        $(document).on('submit', '#refund_form', function(event) {
            event.preventDefault();
            let form_data = new FormData($('#refund_form')[0]);
            $.ajax({
                url: "includes/cancel_customer_enrollment.php",
                type: 'POST',
                data: form_data,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(data) {
                    window.location.reload();
                }
            });
        });

        // ==================== DELETE ENROLLMENT ====================

        function openDeleteEnrollmentModal(PK_ENROLLMENT_MASTER) {
            Swal.fire({
                title: "Are you sure you want to delete this enrollment?",
                text: "This action cannot be undone.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, delete it!"
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#delete_enrollment_model').modal('show');
                    $('#DELETE_ENROLLMENT_ID').val(PK_ENROLLMENT_MASTER);
                }
            });
        }

        $(document).on('submit', '#delete_enrollment_form', function(event) {
            event.preventDefault();
            let form_data = new FormData($('#delete_enrollment_form')[0]);
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                data: form_data,
                processData: false,
                contentType: false,
                success: function(data) {
                    window.location.reload();
                }
            });
        });

        // ==================== EDIT DUE DATE FORM ====================

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
                                timer: 3000
                            })
                            .then(() => {
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