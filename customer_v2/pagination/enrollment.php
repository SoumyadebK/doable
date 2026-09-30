<?php
require_once('../../global/config.php');
global $db;
global $db_account;
global $master_database;
global $results_per_page;
global $upload_path;

/* ============================================================
   SAFE HELPERS
   ============================================================ */
if (!function_exists('safe_field')) {
    function safe_field($result, $column, $default = null)
    {
        if (!$result || !is_object($result)) return $default;
        if (!isset($result->fields) || !is_array($result->fields)) return $default;
        return array_key_exists($column, $result->fields) ? $result->fields[$column] : $default;
    }
}

/* ============================================================
   GUARANTEES — prevent undefined-variable notices
   ============================================================ */
if (!isset($PERMISSION_ARRAY) || !is_array($PERMISSION_ARRAY)) {
    $PERMISSION_ARRAY = [];
}
if (!isset($master_database) || $master_database === '') {
    $master_database = $_SESSION['MASTER_DATABASE'] ?? 'doable_master';
}

/* ============================================================
   INPUT PARAMETERS
   ============================================================ */
$PK_USER_MASTER = !empty($_GET['master_id']) ? intval($_GET['master_id']) : 0;
$PK_USER        = !empty($_GET['pk_user'])   ? intval($_GET['pk_user'])   : 0;
$type           = !empty($_GET['type'])      ? $_GET['type']              : 'normal';

$page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit  = 10;
$offset = ($page - 1) * $limit;

/* ============================================================
   LOCATION — fallback and safe filter
   ============================================================ */
$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'] ?? '';
if (empty($DEFAULT_LOCATION_ID)) {
    $loc = $db->Execute("SELECT PK_LOCATION FROM DOA_USER_LOCATION WHERE PK_USER = " . intval($_SESSION['PK_USER'] ?? 0) . " LIMIT 1");
    $DEFAULT_LOCATION_ID = safe_field($loc, 'PK_LOCATION', '');
}

$location_filter = '';
if (!empty($DEFAULT_LOCATION_ID)) {
    $loc_ids = array_filter(array_map('intval', explode(',', $DEFAULT_LOCATION_ID)));
    if (!empty($loc_ids)) {
        $location_filter = " AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . implode(',', $loc_ids) . ") ";
    }
}

/* ============================================================
   STATUS CONDITION
   ============================================================ */
if ($type == 'completed') {
    $enr_title     = 'Completed Enrollments';
    $enr_condition = " (DOA_ENROLLMENT_MASTER.STATUS = 'CO' OR DOA_ENROLLMENT_MASTER.STATUS = 'C') ";
} else {
    $enr_title     = 'Active Enrollments';
    $enr_condition = " (DOA_ENROLLMENT_MASTER.STATUS = 'CA' OR DOA_ENROLLMENT_MASTER.STATUS = 'A') ";
}

/* ============================================================
   DIAGNOSTIC LOGGING — remove once stable
   ============================================================ */
error_log('pagination/enrollment.php: master_id=' . $PK_USER_MASTER .
    ' pk_user=' . $PK_USER .
    ' type=' . $type .
    ' page=' . $page .
    ' location=' . $DEFAULT_LOCATION_ID);
?>

<?php
if ($page == 1) { ?>
    <div class="enrollment-container mb-4">

        <div class="d-flex justify-content-between align-items-start mb-1 row">
            <div class="col-5">
                <h5 class="fw-bold mb-1"><?= $enr_title ?></h5>
                <p class="text-muted mb-2 small">Optional settings section description</p>
            </div>

            <div class="col-3 d-flex justify-content-end align-items-center">
                <div class="view-toggle m-r-15" style="height: 37px;">
                    <button class="view-btn-icon <?= ($type != 'completed') ? 'active' : '' ?>" onclick="loadEnrollment('normal')">
                        Active
                    </button>
                    <button class="view-btn-icon <?= ($type == 'completed') ? 'active' : '' ?>" onclick="loadEnrollment('completed')">
                        Complete
                    </button>
                </div>
            </div>

            <div class="col-2 text-end">
                <a class="btn btn-light rounded-pill btn-outline-edit btn-sm border-0 text-white px-3 py-2"
                    style="background-color: #39b54a !important; width: max-content; height: 36px;"
                    href="adjust_customer_enrollment_and_appointment.php?PK_USER=<?= $PK_USER ?>&PK_USER_MASTER=<?= $PK_USER_MASTER ?>">
                    <i class="bi bi-repeat"></i> Adjust Everything
                </a>
            </div>

            <div class="col-2 text-end">
                <button class="btn btn-light rounded-pill btn-outline-edit btn-sm border-0 text-white px-3 py-2"
                    style="background-color: #39b54a !important; width: max-content; height: 36px;"
                    onclick="createCustomerEnrollment()">
                    <i class="bi bi-plus"></i> Create New Enrollment
                </button>
            </div>
        </div>

        <?php
        $misc_balance   = 0;
        $credit_balance = 0;

        // === FIX: intval on master id + guarded read ===
        $wallet_data = $db_account->Execute("SELECT SUM(BALANCE_LEFT) AS CURRENT_BALANCE FROM DOA_CUSTOMER_WALLET WHERE PK_USER_MASTER = " . intval($PK_USER_MASTER));
        $CURRENT_WALLET_BALANCE = safe_field($wallet_data, 'CURRENT_BALANCE', 0);

        if ($type == 'completed') {
            $enr_service_data = $db_account->Execute("SELECT DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_SERVICE, DOA_ENROLLMENT_SERVICE.PRICE_PER_SESSION, DOA_ENROLLMENT_SERVICE.FINAL_AMOUNT, DOA_ENROLLMENT_SERVICE.TOTAL_AMOUNT_PAID, DOA_SERVICE_MASTER.PK_SERVICE_CLASS FROM DOA_ENROLLMENT_SERVICE LEFT JOIN DOA_SERVICE_MASTER ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE (DOA_ENROLLMENT_MASTER.STATUS = 'CO' || DOA_ENROLLMENT_MASTER.STATUS = 'C') AND DOA_ENROLLMENT_MASTER.PK_USER_MASTER = " . intval($PK_USER_MASTER));
        } else {
            $enr_service_data = $db_account->Execute("SELECT DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_SERVICE, DOA_ENROLLMENT_SERVICE.PRICE_PER_SESSION, DOA_ENROLLMENT_SERVICE.FINAL_AMOUNT, DOA_ENROLLMENT_SERVICE.TOTAL_AMOUNT_PAID, DOA_SERVICE_MASTER.PK_SERVICE_CLASS FROM DOA_ENROLLMENT_SERVICE LEFT JOIN DOA_SERVICE_MASTER ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE (DOA_ENROLLMENT_MASTER.STATUS = 'CA' || DOA_ENROLLMENT_MASTER.STATUS = 'A') AND DOA_ENROLLMENT_MASTER.PK_USER_MASTER = " . intval($PK_USER_MASTER));
        }

        // === FIX: guard the loop ===
        if ($enr_service_data) {
            while (!$enr_service_data->EOF) {
                $PK_SERVICE_CLASS = safe_field($enr_service_data, 'PK_SERVICE_CLASS', 0);
                $FINAL_AMOUNT     = safe_field($enr_service_data, 'FINAL_AMOUNT', 0);

                if ($PK_SERVICE_CLASS == 5) {
                    $misc_balance += $FINAL_AMOUNT;
                } else {
                    $credit_balance += $FINAL_AMOUNT;
                }
                $enr_service_data->MoveNext();
            }
        } ?>

        <div class="d-flex align-items-center border-top border-bottom py-2 mb-3">
            <div class="flex-grow-1">
                <div class="stat-label">Total Amount Enrolled</div>
                <div class="stat-value">$<?= number_format((float)$credit_balance, 2) ?></div>
            </div>
            <div class="stat-divider"></div>
            <div class="flex-grow-1">
                <div class="stat-label">Miscellaneous Amount</div>
                <div class="stat-value">$<?= number_format((float)$misc_balance, 2) ?></div>
            </div>
            <div class="stat-divider"></div>
            <div class="flex-grow-1">
                <div class="stat-label">Wallet Balance</div>
                <div class="stat-value">$<?= number_format((float)$CURRENT_WALLET_BALANCE, 2) ?></div>
            </div>
        </div>

        <?php
        if ($page == 1) {
            if ($_GET['type'] == 'normal') {
                if (file_exists('customer_pending_services.php')) {
                    require_once('customer_pending_services.php');
                }
            } else {
                if (file_exists('customer_completed_services.php')) {
                    require_once('customer_completed_services.php');
                }
            }
        } ?>
    </div>
<?php } ?>

<?php
if ($page == 1) {
    if ($_GET['type'] == 'normal') {
        if (file_exists('customer_ad_hoc_appointment.php')) {
            require_once('customer_ad_hoc_appointment.php');
        }
    }
} ?>

<?php
/* ============================================================
   MAIN ENROLLMENT QUERY
   ============================================================ */
$enrollment_query = "SELECT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, DOA_ENROLLMENT_MASTER.PK_USER_MASTER, DOA_ENROLLMENT_MASTER.ENROLLMENT_NAME, DOA_ENROLLMENT_MASTER.MISC_TYPE, DOA_ENROLLMENT_MASTER.MISC_ID, DOA_ENROLLMENT_MASTER.ENROLLMENT_ID, DOA_ENROLLMENT_MASTER.AGREEMENT_PDF_LINK, DOA_ENROLLMENT_MASTER.ACTIVE, DOA_ENROLLMENT_MASTER.STATUS, DOA_ENROLLMENT_MASTER.ENROLLMENT_DATE, DOA_ENROLLMENT_MASTER.CHARGE_TYPE, DOA_ENROLLMENT_MASTER.ACTIVE_AUTO_PAY, DOA_ENROLLMENT_MASTER.PAYMENT_METHOD_ID, DOA_ENROLLMENT_BILLING.PAYMENT_METHOD, DOA_LOCATION.LOCATION_NAME
    FROM `DOA_ENROLLMENT_MASTER`
    INNER JOIN DOA_ENROLLMENT_BILLING ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER
    LEFT JOIN " . $master_database . ".DOA_LOCATION AS DOA_LOCATION ON DOA_LOCATION.PK_LOCATION = DOA_ENROLLMENT_MASTER.PK_LOCATION
    WHERE " . $enr_condition
    . $location_filter
    . " AND DOA_ENROLLMENT_MASTER.PK_USER_MASTER = " . intval($PK_USER_MASTER) . "
    ORDER BY DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER DESC
    LIMIT $limit OFFSET $offset";

$enrollment_data = $db_account->Execute($enrollment_query);

if (!$enrollment_data) {
    error_log('pagination/enrollment.php: enrollment query FAILED: ' . $db_account->ErrorMsg());
    error_log('pagination/enrollment.php: SQL = ' . $enrollment_query);
}
error_log('pagination/enrollment.php: enrollment rows = ' . ($enrollment_data ? $enrollment_data->RecordCount() : 'FALSE'));

$AGREEMENT_PDF_LINK = '';

if ($enrollment_data) {
    while (!$enrollment_data->EOF) {
        $name                 = safe_field($enrollment_data, 'ENROLLMENT_NAME', '');
        $AGREEMENT_PDF_LINK   = safe_field($enrollment_data, 'AGREEMENT_PDF_LINK', '');
        $PK_ENROLLMENT_MASTER = safe_field($enrollment_data, 'PK_ENROLLMENT_MASTER', 0);
        $ENROLLMENT_ID        = safe_field($enrollment_data, 'ENROLLMENT_ID', '');

        $enrollment_name = empty($name) ? '' : ($name . " - ");

        $serviceMasterData = $db_account->Execute("SELECT DOA_SERVICE_MASTER.SERVICE_NAME FROM DOA_SERVICE_MASTER JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER WHERE DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = " . intval($PK_ENROLLMENT_MASTER));
        $serviceMaster = [];
        if ($serviceMasterData) {
            while (!$serviceMasterData->EOF) {
                $serviceMaster[] = safe_field($serviceMasterData, 'SERVICE_NAME', '');
                $serviceMasterData->MoveNext();
            }
        }

        $enrollment_title = ($ENROLLMENT_ID == null)
            ? $enrollment_name . safe_field($enrollment_data, 'MISC_ID', '')
            : $enrollment_name . $ENROLLMENT_ID;
?>

        <div class="enrollment-container enrollment_div mb-3" style="position: relative;">

            <?php
            $unpaid_count     = 0;
            $amount_to_pay    = 0;
            $amount_to_return = 0;

            $enr_total_amount  = $db_account->Execute("SELECT SUM(FINAL_AMOUNT) AS TOTAL_AMOUNT FROM DOA_ENROLLMENT_SERVICE WHERE PK_ENROLLMENT_MASTER = " . intval($PK_ENROLLMENT_MASTER));
            $enr_paid_amount   = $db_account->Execute("SELECT SUM(AMOUNT) AS TOTAL_PAID_AMOUNT FROM DOA_ENROLLMENT_PAYMENT WHERE (TYPE = 'Payment' OR TYPE = 'Adjustment') AND IS_REFUNDED = 0 AND PK_ENROLLMENT_MASTER = " . intval($PK_ENROLLMENT_MASTER));
            $enr_refund_amount = $db_account->Execute("SELECT SUM(AMOUNT) AS TOTAL_REFUND_AMOUNT FROM DOA_ENROLLMENT_PAYMENT WHERE (TYPE = 'Move' OR TYPE = 'Refund') AND PK_ENROLLMENT_MASTER = " . intval($PK_ENROLLMENT_MASTER));

            $TOTAL_AMOUNT        = (float) safe_field($enr_total_amount, 'TOTAL_AMOUNT', 0);
            $TOTAL_PAID_AMOUNT   = (float) safe_field($enr_paid_amount, 'TOTAL_PAID_AMOUNT', 0);
            $TOTAL_REFUND_AMOUNT = (float) safe_field($enr_refund_amount, 'TOTAL_REFUND_AMOUNT', 0);

            if (($TOTAL_AMOUNT > 0) && ($TOTAL_PAID_AMOUNT < $TOTAL_AMOUNT)) {
                $amount_to_pay = $TOTAL_AMOUNT - $TOTAL_PAID_AMOUNT;
                $ledger_data = $db_account->Execute("SELECT count(DOA_ENROLLMENT_LEDGER.IS_PAID) AS PAID FROM `DOA_ENROLLMENT_LEDGER` WHERE DOA_ENROLLMENT_LEDGER.IS_PAID = 0 AND PK_ENROLLMENT_MASTER = " . intval($PK_ENROLLMENT_MASTER));
                $unpaid_count = $ledger_data && $ledger_data->RecordCount() > 0 ? (int) safe_field($ledger_data, 'PAID', 0) : 0;
            } elseif (($TOTAL_AMOUNT > 0) && (($TOTAL_PAID_AMOUNT - $TOTAL_REFUND_AMOUNT) > $TOTAL_AMOUNT)) {
                $amount_to_return = $TOTAL_PAID_AMOUNT - $TOTAL_REFUND_AMOUNT - $TOTAL_AMOUNT;
            }
            ?>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="row align-items-center flex-nowrap gx-2" style="width:100%;">
                    <div class="col-3">
                        <a href="../admin_v2/enrollment.php?id=<?= $PK_ENROLLMENT_MASTER ?>" target="_blank">
                            <h6 class="fw-bold mb-0">
                                <?= $enrollment_title ?>
                                <span class="text-muted fw-normal ms-2"><?= date('m/d/Y', strtotime(safe_field($enrollment_data, 'ENROLLMENT_DATE', 'now'))) ?></span>
                            </h6>
                        </a>
                    </div>
                    <div class="col-2">
                        <?php if ($AGREEMENT_PDF_LINK != '' && $AGREEMENT_PDF_LINK != null) { ?>
                            <a href="enrollment_agreement.php?id=<?= $PK_ENROLLMENT_MASTER ?>" class="view-schedule text-primary" target="_blank">View Agreement</a>
                            <i class="bi bi-envelope-fill" title="Mail to Customer" style="font-size: 18px; color: #39b54a; margin-left: 10px; cursor: pointer;" onclick="mailAgreementToCustomer(<?= $PK_ENROLLMENT_MASTER ?>)"></i>
                        <?php } ?>
                    </div>
                    <div class="col-2">
                        <a href="javascript:void(0)" class="view-schedule text-primary show_enrollment_details_button" onclick="showEnrollmentDetails(this, <?= $PK_USER ?>, <?= $PK_USER_MASTER ?>, <?= $PK_ENROLLMENT_MASTER ?>, '<?= htmlspecialchars($ENROLLMENT_ID, ENT_QUOTES) ?>', '<?= htmlspecialchars($type, ENT_QUOTES) ?>', 'billing_details')">View Payment Schedule</a>
                    </div>
                    <div class="col-auto">
                        <?php if (($TOTAL_AMOUNT == 0) || ($TOTAL_PAID_AMOUNT >= $TOTAL_AMOUNT)) { ?>
                            <?php if ((safe_field($enrollment_data, 'MISC_TYPE', null) != null || safe_field($enrollment_data, 'MISC_ID', null) != null) && safe_field($enrollment_data, 'STATUS', '') == 'A') { ?>
                                <button class="btn btn-secondary" onclick="markMiscComplete(<?= $PK_ENROLLMENT_MASTER ?>)" style="margin-top:-3px; margin-right:15px;">Mark Complete</button>
                            <?php } ?>
                            <span class="checkicon f15 theme-text" style="background-color: #cffce4; color: #39b54a; padding: 4px 8px; border-radius: 50px; float: right;">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="15px" height="15px" fill="#39b54a" style="padding-bottom: 2px;">
                                    <path d="M256,0C114.615,0,0,114.615,0,256s114.615,256,256,256s256-114.615,256-256S397.385,0,256,0z M219.429,367.932 L108.606,257.108l38.789-38.789l72.033,72.035L355.463,154.32l38.789,38.789L219.429,367.932z"></path>
                                </svg>
                                <span style="background-color: #cffce4; color: #39b54a;">PAID</span>
                            </span>
                        <?php } ?>
                    </div>
                    <div class="col-auto ms-auto">
                        <?php if ((safe_field($enrollment_data, 'PAYMENT_METHOD', '') == 'Payment Plans' || safe_field($enrollment_data, 'PAYMENT_METHOD', '') == 'Flexible Payments') && safe_field($enrollment_data, 'STATUS', '') == 'A') { ?>
                            <div class="d-flex justify-content-end align-items-center">
                                <div class="form-check form-switch d-flex align-items-center">
                                    <?php if (!is_null(safe_field($enrollment_data, 'PAYMENT_METHOD', null)) && safe_field($enrollment_data, 'PAYMENT_METHOD_ID', '') != '') { ?>
                                        <label class="form-check-label autopay-label" onclick="changeEnrollmentAutoPay(<?= $PK_ENROLLMENT_MASTER ?>);"> Auto Pay
                                        <?php } else { ?>
                                            <label class="form-check-label autopay-label" onclick="addEnrollmentAutoPay(<?= $PK_ENROLLMENT_MASTER ?>);"> Auto Pay
                                            <?php } ?>
                                            <input class="form-check-input me-2" type="checkbox" role="switch" <?= (safe_field($enrollment_data, 'ACTIVE_AUTO_PAY', 0) == 1 && trim((string)safe_field($enrollment_data, 'PAYMENT_METHOD_ID', ''))) ? 'checked' : '' ?>>
                                            </label>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                    <div class="col-auto d-flex justify-content-end align-items-center text-end">
                        <?php
                        $payment_data     = $db_account->Execute("SELECT PK_ENROLLMENT_PAYMENT FROM `DOA_ENROLLMENT_PAYMENT` WHERE PK_PAYMENT_TYPE != 12 AND PK_ENROLLMENT_MASTER = " . intval($PK_ENROLLMENT_MASTER));
                        $balance_owed     = $db_account->Execute("SELECT SUM(BILLED_AMOUNT) AS TOTAL_BALANCE_OWED FROM DOA_ENROLLMENT_LEDGER WHERE (TRANSACTION_TYPE = 'Balance Owed' OR TRANSACTION_TYPE = 'Billing') AND STATUS = 'CA' AND IS_PAID = 0 AND PK_ENROLLMENT_MASTER = " . intval($PK_ENROLLMENT_MASTER));
                        $refund_available = $db_account->Execute("SELECT SUM(BALANCE) AS TOTAL_REFUND_AVAILABLE FROM DOA_ENROLLMENT_LEDGER WHERE TRANSACTION_TYPE = 'Refund Credit Available' AND STATUS = 'CA' AND IS_PAID = 2 AND PK_ENROLLMENT_MASTER = " . intval($PK_ENROLLMENT_MASTER));

                        $PAYMENT_COUNT         = $payment_data ? $payment_data->RecordCount() : 0;
                        $TOTAL_BALANCE_OWED    = (float) safe_field($balance_owed, 'TOTAL_BALANCE_OWED', 0);
                        $TOTAL_REFUND_AVAILABLE = (float) safe_field($refund_available, 'TOTAL_REFUND_AVAILABLE', 0);

                        if ($PAYMENT_COUNT == 0 && $TOTAL_BALANCE_OWED <= 0) {
                        ?>
                            <?php if (in_array('Enrollments Delete', $PERMISSION_ARRAY)) { ?>
                                <a href="javascript:;" onclick="openDeleteEnrollmentModal(<?= $PK_ENROLLMENT_MASTER ?>);" title="Delete" style="color: red; font-size: 21px;">
                                    <i class="bi bi-trash"></i>
                                </a>
                            <?php } ?>
                        <?php } ?>
                        <?php if ($_SESSION['PK_ROLES'] != 5) { ?>
                            <?php if (safe_field($enrollment_data, 'STATUS', '') == 'A') { ?>
                                <a href="javascript:;" onclick="cancelEnrollment(<?= $PK_ENROLLMENT_MASTER ?>, <?= safe_field($enrollment_data, 'PK_USER_MASTER', 0) ?>, '<?= htmlspecialchars($enrollment_title, ENT_QUOTES) ?>')" title="Cancel" style="color: red; font-size: 21px; margin-left: 10px;">
                                    <i class="bi bi-ban"></i>
                                </a>
                            <?php } elseif (safe_field($enrollment_data, 'STATUS', '') == 'C' || safe_field($enrollment_data, 'STATUS', '') == 'CA') { ?>
                                <div class="d-flex flex-column align-items-end">
                                    <p style="color: red; margin: 0;">Cancelled</p>
                                    <?php if (safe_field($enrollment_data, 'STATUS', '') === 'CA') { ?>
                                        <?php if ($TOTAL_REFUND_AVAILABLE > 0) { ?>
                                            <p style="color: green; margin: 0; font-size: 12px; font-weight: bold;">(Refund Credit Available)</p>
                                        <?php } elseif ($TOTAL_BALANCE_OWED > 0) { ?>
                                            <p style="color: red; margin: 0; font-size: 12px; font-weight: bold;">(Balance Owed)</p>
                                        <?php } ?>
                                    <?php } ?>
                                </div>
                            <?php } ?>
                        <?php }
                        if (($amount_to_pay > 0 && $unpaid_count <= 0) && safe_field($enrollment_data, 'STATUS', '') != 'C') { ?>
                            <p style="color:red; margin: 0; margin-right: 10px;">$<?= number_format($amount_to_pay, 2) ?></p>
                            <button id="payNow" class="btn btn-secondary" onclick="payNow(<?= $PK_ENROLLMENT_MASTER ?>, 0, <?= $amount_to_pay ?>, '<?= htmlspecialchars($ENROLLMENT_ID, ENT_QUOTES) ?>');">Adjust</button><br><br>
                        <?php } elseif ($amount_to_return > 0) { ?>
                            <p style="color:green; margin: 0; margin-right: 10px;">$<?= number_format($amount_to_return, 2) ?></p>
                            <button class="btn btn-secondary" onclick="moveToWallet(this, 0, <?= $PK_ENROLLMENT_MASTER ?>, 0, <?= $PK_USER_MASTER ?>, <?= $amount_to_return ?>, 'completed', 'Move', 0)">Move to Wallet</button><br><br>
                        <?php } ?>
                    </div>
                </div>
            </div>

            <div class="table-responsive" style="border: none;">
                <table class="table">
                    <thead class="table-light">
                        <tr>
                            <th style="text-align: center;">Service Code</th>
                            <th style="text-align: center;">Enrolled</th>
                            <th style="text-align: center;">Used</th>
                            <th style="text-align: center;">Scheduled</th>
                            <th style="text-align: center;">Balance</th>
                            <th style="text-align: center;">Paid</th>
                            <th style="text-align: center;">Service Credit</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php
                        $serviceCodeData = $db_account->Execute("SELECT DOA_ENROLLMENT_SERVICE.*, DOA_SERVICE_MASTER.PK_SERVICE_CLASS, DOA_SERVICE_CODE.PK_SERVICE_CODE, DOA_SERVICE_CODE.SERVICE_CODE FROM DOA_ENROLLMENT_SERVICE JOIN DOA_SERVICE_MASTER ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER JOIN DOA_SERVICE_CODE ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE WHERE DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = " . intval($PK_ENROLLMENT_MASTER));
                        $total_amount           = 0;
                        $total_paid_amount      = 0;
                        $total_used_amount      = 0;
                        $total_scheduled_amount = 0;
                        $enrollment_service_array = [];

                        if ($serviceCodeData) {
                            while (!$serviceCodeData->EOF) {
                                $CHARGE_TYPE = safe_field($enrollment_data, 'CHARGE_TYPE', '');

                                $PK_ENROLLMENT_SERVICE = safe_field($serviceCodeData, 'PK_ENROLLMENT_SERVICE', 0);
                                $SERVICE_CODE          = safe_field($serviceCodeData, 'SERVICE_CODE', '');
                                $PK_SERVICE_CLASS      = safe_field($serviceCodeData, 'PK_SERVICE_CLASS', 0);
                                $FINAL_AMOUNT          = (float) safe_field($serviceCodeData, 'FINAL_AMOUNT', 0);
                                $TOTAL_AMOUNT_PAID_SVC = (float) safe_field($serviceCodeData, 'TOTAL_AMOUNT_PAID', 0);
                                $PRICE_PER_SESSION_RAW = (float) safe_field($serviceCodeData, 'PRICE_PER_SESSION', 0);

                                if ($CHARGE_TYPE == 'Membership') {
                                    $NUMBER_OF_SESSION = getSessionCreatedCount($PK_ENROLLMENT_SERVICE);
                                } else {
                                    $NUMBER_OF_SESSION = safe_field($serviceCodeData, 'NUMBER_OF_SESSION', 0);
                                }

                                $SESSION_SCHEDULED = getSessionScheduledCount($PK_ENROLLMENT_SERVICE);
                                $SESSION_COMPLETED = getSessionCompletedCount($PK_ENROLLMENT_SERVICE);

                                $enrollment_service_array[] = $PK_ENROLLMENT_SERVICE;

                                if ($CHARGE_TYPE == 'Membership') {
                                    $PRICE_PER_SESSION = ($NUMBER_OF_SESSION > 0) ? number_format($TOTAL_AMOUNT_PAID_SVC / $NUMBER_OF_SESSION, 2) : 0;
                                } else {
                                    $PRICE_PER_SESSION = ($PRICE_PER_SESSION_RAW <= 0) ? 0 : $PRICE_PER_SESSION_RAW;
                                }

                                if (($type == 'completed') && ($PK_SERVICE_CLASS == 5)) {
                                    $TOTAL_PAID_SESSION = $NUMBER_OF_SESSION;
                                    if (safe_field($serviceCodeData, 'STATUS', '') == 'C') {
                                        $TOTAL_AMOUNT_PAID = is_null(safe_field($serviceCodeData, 'TOTAL_AMOUNT_PAID', null)) ? 0 : $TOTAL_AMOUNT_PAID_SVC;
                                    } else {
                                        $TOTAL_AMOUNT_PAID = $FINAL_AMOUNT;
                                    }
                                } else {
                                    $TOTAL_PAID_SESSION = ($PRICE_PER_SESSION <= 0) ? $NUMBER_OF_SESSION : number_format($TOTAL_AMOUNT_PAID_SVC / $PRICE_PER_SESSION, 2);
                                    $TOTAL_AMOUNT_PAID  = $TOTAL_AMOUNT_PAID_SVC;
                                }

                                $ENR_BALANCE    = $NUMBER_OF_SESSION - $TOTAL_PAID_SESSION;
                                $SERVICE_CREDIT = $TOTAL_PAID_SESSION - $SESSION_COMPLETED;

                                if ($type == 'completed' && $SERVICE_CREDIT > 0) {
                                    $SERVICE_CREDIT = 0;
                                }

                                $total_amount           += $FINAL_AMOUNT;
                                $total_paid_amount      += $TOTAL_AMOUNT_PAID;
                                $total_used_amount      += ($PRICE_PER_SESSION * $SESSION_COMPLETED);
                                $total_scheduled_amount += ($PRICE_PER_SESSION * $SESSION_SCHEDULED);
                        ?>
                                <tr>
                                    <td style="text-align: center;">
                                        <span class="badge-service" style="background-color: <?= getServiceCodeColor($SERVICE_CODE) ?>20; color: <?= getServiceCodeColor($SERVICE_CODE) ?>;">
                                            <?= htmlspecialchars($SERVICE_CODE) ?>
                                        </span>
                                    </td>
                                    <td style="text-align: center"><?= ($CHARGE_TYPE == 'Membership' && $NUMBER_OF_SESSION <= 0) ? 'XX' : $NUMBER_OF_SESSION ?></td>
                                    <td style="text-align: center;"><?= ($CHARGE_TYPE == 'Membership' && $SESSION_COMPLETED <= 0) ? 'XX' : $SESSION_COMPLETED ?></td>
                                    <td style="text-align: center;"><?= ($CHARGE_TYPE == 'Membership' && $SESSION_SCHEDULED <= 0) ? 'XX' : $SESSION_SCHEDULED ?></td>
                                    <td style="text-align: center; color:<?= ($ENR_BALANCE < 0) ? 'red' : 'black' ?>;"><?= number_format($ENR_BALANCE, 2) ?></td>
                                    <td style="text-align: center"><?= number_format($TOTAL_AMOUNT_PAID_SVC / (($PRICE_PER_SESSION == 0) ? 1 : $PRICE_PER_SESSION), 2) ?></td>
                                    <td style="text-align: center; color:<?= ($SERVICE_CREDIT < 0) ? 'red' : 'black' ?>;"><?= number_format($SERVICE_CREDIT, 2) ?></td>
                                </tr>
                        <?php $serviceCodeData->MoveNext();
                            }
                        } ?>
                    </tbody>

                    <tfoot class="border-top-0">
                        <tr class="fw-bold">
                            <td style="text-align: center; font-size: 14px;">Amount</td>
                            <td style="text-align: center; font-size: 14px;">$<?= number_format($total_amount, 2) ?></td>
                            <td style="text-align: center; font-size: 14px;">$<?= number_format($total_amount - $total_used_amount < 0.00 ? $total_amount : $total_used_amount, 2) ?></td>
                            <td style="text-align: center; font-size: 14px;">$<?= number_format($total_scheduled_amount, 2) ?></td>
                            <td style="text-align: center; font-size: 14px; color:<?= ($total_amount - $total_paid_amount < -0.05) ? 'red' : 'black' ?>;">$<?= number_format((abs($total_amount - $total_paid_amount) <= 0.05) ? 0 : $total_amount - $total_paid_amount, 2) ?></td>
                            <td style="text-align: center; font-size: 14px;">$<?= number_format($total_paid_amount, 2) ?></td>
                            <td style="text-align: center; font-size: 14px; color:<?= ($total_paid_amount - $total_used_amount < -0.99) ? 'red' : 'black' ?>;">$<?= number_format((abs($total_paid_amount - $total_used_amount) <= 0.99) ? 0 : ($total_paid_amount - $total_used_amount), 2) ?></td>
                        </tr>
                    </tfoot>
                </table>

                <div class="enrollment_details" style="display: none;"></div>
            </div>
        </div>
<?php
        $enrollment_data->MoveNext();
    }
} ?>

<style>
    .btn-outline-edit {
        transition: all 0.3s ease;
    }

    .btn-outline-edit:hover {
        background-color: #2e9e3d !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(57, 181, 74, 0.35);
        cursor: pointer;
    }
</style>