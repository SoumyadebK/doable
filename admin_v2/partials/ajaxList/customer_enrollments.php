<?php
require_once('../../../global/config.php');
global $db;
global $db_account;
global $master_database;
global $results_per_page;
global $upload_path;

$PK_USER_MASTER = !empty($_GET['master_id']) ? $_GET['master_id'] : 0;
$PK_USER = !empty($_GET['pk_user']) ? $_GET['pk_user'] : 0;
$type = !empty($_GET['type']) ? $_GET['type'] : 0;
$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

if ($type == 'completed') {
    $enr_title = 'Completed Enrollments';
    $enr_condition = " (DOA_ENROLLMENT_MASTER.STATUS = 'CO' OR DOA_ENROLLMENT_MASTER.STATUS = 'C') ";
} else {
    $enr_title = 'Active Enrollments';
    $enr_condition = " (DOA_ENROLLMENT_MASTER.STATUS = 'CA' OR DOA_ENROLLMENT_MASTER.STATUS = 'A') ";
}
?>


<?php
if ($page == 1) { ?>
    <div class="enrollment-container mb-4" style="position: relative;">
        <h5 class="fw-bold mb-1"><?= $enr_title ?></h5>
        <p class="text-muted mb-4 small">Optional settings section description</p>

        <div class="view-toggle m-r-15" style="position: absolute; top: 24px; right: 24px; height: 37px; display: flex; gap: 10px;">
            <button class="view-btn-icon <?= ($type != 'completed') ? 'active' : '' ?>" onclick="loadEnrollment('normal')">
                Active
            </button>
            <button class="view-btn-icon <?= ($type == 'completed') ? 'active' : '' ?>" onclick="loadEnrollment('completed')">
                Complete
            </button>
        </div>

        <?php
        $misc_balance = 0;
        $credit_balance = 0;
        $wallet_data = $db_account->Execute("SELECT * FROM DOA_CUSTOMER_WALLET WHERE PK_USER_MASTER = '$PK_USER_MASTER' ORDER BY PK_CUSTOMER_WALLET DESC LIMIT 1");

        $enr_service_data = $db_account->Execute("SELECT DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_SERVICE, DOA_ENROLLMENT_SERVICE.PRICE_PER_SESSION, DOA_ENROLLMENT_SERVICE.FINAL_AMOUNT, DOA_ENROLLMENT_SERVICE.TOTAL_AMOUNT_PAID, DOA_SERVICE_MASTER.PK_SERVICE_CLASS FROM DOA_ENROLLMENT_SERVICE LEFT JOIN DOA_SERVICE_MASTER ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE (DOA_ENROLLMENT_MASTER.STATUS = 'CA' || DOA_ENROLLMENT_MASTER.STATUS = 'A') AND DOA_ENROLLMENT_MASTER.PK_USER_MASTER = " . $PK_USER_MASTER);
        while (!$enr_service_data->EOF) {
            if ($enr_service_data->fields['PK_SERVICE_CLASS'] == 5) {
                $misc_balance += ($enr_service_data->fields['FINAL_AMOUNT'] - $enr_service_data->fields['TOTAL_AMOUNT_PAID']);
            } else {
                $credit_balance += ($enr_service_data->fields['FINAL_AMOUNT'] - $enr_service_data->fields['TOTAL_AMOUNT_PAID']);
            }
            $enr_service_data->MoveNext();
        } ?>

        <div class="d-flex align-items-center border-top border-bottom py-4 mb-4">
            <div class="flex-grow-1">
                <div class="stat-label">Total Balance</div>
                <div class="stat-value">$<?= number_format((float)$credit_balance, 2) ?></div>
            </div>
            <div class="stat-divider"></div>
            <div class="flex-grow-1">
                <div class="stat-label">Miscellaneous Balance</div>
                <div class="stat-value">$<?= number_format((float)$misc_balance, 2) ?></div>
            </div>
            <div class="stat-divider"></div>
            <div class="flex-grow-1">
                <div class="stat-label">Wallet Balance</div>
                <div class="stat-value">$<?= number_format((float)($wallet_data->RecordCount() > 0 ? $wallet_data->fields['CURRENT_BALANCE'] : 0.00), 2) ?></div>
            </div>
        </div>

        <?php
        if ($page == 1) {
            if ($_GET['type'] == 'normal') { ?>
                <h6 class="fw-bold mb-3">List of Pending Services</h6>
                <?php require_once('customer_pending_services.php'); ?>
            <?php } else { ?>
                <h6 class="fw-bold mb-3">List of Completed Services</h6>
                <?php require_once('customer_completed_services.php'); ?>
        <?php }
        } ?>
    </div>
<?php } ?>



<?php
$enrollment_data = $db_account->Execute("SELECT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, DOA_ENROLLMENT_MASTER.ENROLLMENT_NAME, DOA_ENROLLMENT_MASTER.MISC_ID, DOA_ENROLLMENT_MASTER.ENROLLMENT_ID, DOA_ENROLLMENT_MASTER.AGREEMENT_PDF_LINK, DOA_ENROLLMENT_MASTER.ACTIVE, DOA_ENROLLMENT_MASTER.STATUS, DOA_ENROLLMENT_MASTER.ENROLLMENT_DATE, DOA_ENROLLMENT_MASTER.CHARGE_TYPE, DOA_ENROLLMENT_MASTER.ACTIVE_AUTO_PAY, DOA_ENROLLMENT_MASTER.PAYMENT_METHOD_ID, DOA_ENROLLMENT_BILLING.PAYMENT_METHOD, DOA_LOCATION.LOCATION_NAME FROM `DOA_ENROLLMENT_MASTER` INNER JOIN DOA_ENROLLMENT_BILLING ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER LEFT JOIN $master_database.DOA_LOCATION AS DOA_LOCATION ON DOA_LOCATION.PK_LOCATION = DOA_ENROLLMENT_MASTER.PK_LOCATION WHERE " . $enr_condition . " AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $DEFAULT_LOCATION_ID . ") AND DOA_ENROLLMENT_MASTER.PK_USER_MASTER = $PK_USER_MASTER ORDER BY DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER DESC LIMIT $limit OFFSET $offset");

$AGREEMENT_PDF_LINK = '';
while (!$enrollment_data->EOF) {
    $name = $enrollment_data->fields['ENROLLMENT_NAME'];
    $AGREEMENT_PDF_LINK = $enrollment_data->fields['AGREEMENT_PDF_LINK'];
    $PK_ENROLLMENT_MASTER = $enrollment_data->fields['PK_ENROLLMENT_MASTER'];
    $ENROLLMENT_ID = $enrollment_data->fields['ENROLLMENT_ID'];
    if (empty($name)) {
        $enrollment_name = '';
    } else {
        $enrollment_name = "$name" . " - ";
    }
    $serviceMasterData = $db_account->Execute("SELECT DOA_SERVICE_MASTER.SERVICE_NAME FROM DOA_SERVICE_MASTER JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER WHERE DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = " . $PK_ENROLLMENT_MASTER);
    $serviceMaster = [];
    while (!$serviceMasterData->EOF) {
        $serviceMaster[] = $serviceMasterData->fields['SERVICE_NAME'];
        $serviceMasterData->MoveNext();
    } ?>

    <div class="enrollment-container enrollment_div mb-4" style="position: relative;">

        <?php
        $amount_to_pay = 0;
        $amount_to_return = 0;
        $enr_total_amount = $db_account->Execute("SELECT SUM(FINAL_AMOUNT) AS TOTAL_AMOUNT FROM DOA_ENROLLMENT_SERVICE WHERE PK_ENROLLMENT_MASTER = " . $PK_ENROLLMENT_MASTER);
        $enr_paid_amount = $db_account->Execute("SELECT SUM(AMOUNT) AS TOTAL_PAID_AMOUNT FROM DOA_ENROLLMENT_PAYMENT WHERE (TYPE = 'Payment' OR TYPE = 'Adjustment') AND IS_REFUNDED = 0 AND PK_ENROLLMENT_MASTER = " . $PK_ENROLLMENT_MASTER);
        $enr_refund_amount = $db_account->Execute("SELECT SUM(AMOUNT) AS TOTAL_REFUND_AMOUNT FROM DOA_ENROLLMENT_PAYMENT WHERE (TYPE = 'Move' OR TYPE = 'Refund') AND PK_ENROLLMENT_MASTER = " . $PK_ENROLLMENT_MASTER);
        if (($enr_total_amount->fields['TOTAL_AMOUNT'] > 0) && ($enr_paid_amount->fields['TOTAL_PAID_AMOUNT'] < $enr_total_amount->fields['TOTAL_AMOUNT'])) {
            $amount_to_pay = $enr_total_amount->fields['TOTAL_AMOUNT'] - $enr_paid_amount->fields['TOTAL_PAID_AMOUNT'];
            $ledger_data = $db_account->Execute("SELECT count(DOA_ENROLLMENT_LEDGER.IS_PAID) AS PAID FROM `DOA_ENROLLMENT_LEDGER` WHERE DOA_ENROLLMENT_LEDGER.IS_PAID = 0 AND PK_ENROLLMENT_MASTER = " . $PK_ENROLLMENT_MASTER);
            $unpaid_count = $ledger_data->RecordCount() > 0 ? $ledger_data->fields['PAID'] : 0;
        } elseif (($enr_total_amount->fields['TOTAL_AMOUNT'] > 0) && (($enr_paid_amount->fields['TOTAL_PAID_AMOUNT'] - $enr_refund_amount->fields['TOTAL_REFUND_AMOUNT']) > $enr_total_amount->fields['TOTAL_AMOUNT'])) {
            $amount_to_return = $enr_paid_amount->fields['TOTAL_PAID_AMOUNT'] - $enr_refund_amount->fields['TOTAL_REFUND_AMOUNT'] - $enr_total_amount->fields['TOTAL_AMOUNT'];
        }
        ?>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0"><?= $enrollment_data->fields['LOCATION_NAME'] ?> | <?= ($enrollment_data->fields['ENROLLMENT_ID'] == null) ? $enrollment_name . $enrollment_data->fields['MISC_ID'] : $enrollment_name . $enrollment_data->fields['ENROLLMENT_ID'] ?> <span class="text-muted fw-normal ms-2"><?= date('m/d/Y', strtotime($enrollment_data->fields['ENROLLMENT_DATE'])) ?></span></h6>
            <?php if ($AGREEMENT_PDF_LINK != '' && $AGREEMENT_PDF_LINK != null) { ?>
                <a href="../<?= $upload_path ?>/enrollment_pdf/<?= $AGREEMENT_PDF_LINK ?>" class="view-schedule text-primary" target="_blank">View Agreement</a><br>
            <?php } ?>
            <a href="javascript:void(0)" class="view-schedule text-primary show_enrollment_details_button" onclick="showEnrollmentDetails(this, <?= $PK_USER ?>, <?= $PK_USER_MASTER ?>, <?= $PK_ENROLLMENT_MASTER ?>, '<?= $enrollment_data->fields['ENROLLMENT_ID'] ?>', '<?= $type ?>', 'billing_details')">View Payment Schedule</a>

            <?php if (($enr_total_amount->fields['TOTAL_AMOUNT'] == 0) || ($enr_paid_amount->fields['TOTAL_PAID_AMOUNT'] >= $enr_total_amount->fields['TOTAL_AMOUNT'])) { ?>
                <span class="checkicon f15 theme-text" style="background-color: #cffce4; color: #39b54a; padding: 4px 8px; border-radius: 50px;">
                    <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Layer_1" x="0px" y="0px" viewBox="0 0 512 512" style="enable-background:new 0 0 512 512; padding-bottom: 2px;" xml:space="preserve" width="15px" height="15px" fill="#39b54a">
                        <path d="M256,0C114.615,0,0,114.615,0,256s114.615,256,256,256s256-114.615,256-256S397.385,0,256,0z M219.429,367.932 L108.606,257.108l38.789-38.789l72.033,72.035L355.463,154.32l38.789,38.789L219.429,367.932z"></path>
                    </svg>
                    <span style="background-color: #cffce4; color: #39b54a;">PAID</span>
                </span>
            <?php } ?>

            <?php if (($enrollment_data->fields['PAYMENT_METHOD'] == 'Payment Plans' || $enrollment_data->fields['PAYMENT_METHOD'] == 'Flexible Payments') && $enrollment_data->fields['STATUS'] == 'A') { ?>
                <div class="d-flex justify-content-end align-items-center">
                    <div class="form-check form-switch d-flex align-items-center">
                        <?php if (!is_null($enrollment_data->fields['PAYMENT_METHOD']) && $enrollment_data->fields['PAYMENT_METHOD_ID'] != '') { ?>
                            <label class="form-check-label autopay-label" onclick="changeEnrollmentAutoPay(<?= $PK_ENROLLMENT_MASTER ?>);"> Auto Pay
                            <?php } else { ?>
                                <label class="form-check-label autopay-label" onclick="addEnrollmentAutoPay(<?= $PK_ENROLLMENT_MASTER ?>);"> Auto Pay
                                <?php } ?>
                                <input class="form-check-input me-2" type="checkbox" role="switch" <?= ($enrollment_data->fields['ACTIVE_AUTO_PAY'] == 1) ? 'checked' : '' ?>>
                                </label>
                    </div>
                </div>
            <?php } ?>
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
                    $serviceCodeData = $db_account->Execute("SELECT DOA_ENROLLMENT_SERVICE.*, DOA_SERVICE_MASTER.PK_SERVICE_CLASS, DOA_SERVICE_CODE.PK_SERVICE_CODE, DOA_SERVICE_CODE.SERVICE_CODE FROM DOA_ENROLLMENT_SERVICE JOIN DOA_SERVICE_MASTER ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER JOIN DOA_SERVICE_CODE ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE WHERE DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = " . $PK_ENROLLMENT_MASTER);
                    $total_amount = 0;
                    $total_paid_amount = 0;
                    $total_used_amount = 0;
                    $total_scheduled_amount = 0;
                    $enrollment_service_array = [];
                    while (!$serviceCodeData->EOF) {
                        if ($enrollment_data->fields['CHARGE_TYPE'] == 'Membership') {
                            $NUMBER_OF_SESSION = getSessionCreatedCount($serviceCodeData->fields['PK_ENROLLMENT_SERVICE']);
                        } else {
                            $NUMBER_OF_SESSION = $serviceCodeData->fields['NUMBER_OF_SESSION'];
                        }

                        $SESSION_SCHEDULED = getSessionScheduledCount($serviceCodeData->fields['PK_ENROLLMENT_SERVICE']);

                        /* if ($type == 'completed') {
                                $SESSION_COMPLETED = $NUMBER_OF_SESSION;
                            } else { */
                        $SESSION_COMPLETED = getSessionCompletedCount($serviceCodeData->fields['PK_ENROLLMENT_SERVICE']);
                        //}

                        $enrollment_service_array[] = $serviceCodeData->fields['PK_ENROLLMENT_SERVICE'];

                        if ($enrollment_data->fields['CHARGE_TYPE'] == 'Membership') {
                            $PRICE_PER_SESSION = ($NUMBER_OF_SESSION > 0) ? number_format($serviceCodeData->fields['TOTAL_AMOUNT_PAID'] / $NUMBER_OF_SESSION, 2) : 0;
                        } else {
                            $PRICE_PER_SESSION = ($serviceCodeData->fields['PRICE_PER_SESSION'] <= 0) ? 0 : $serviceCodeData->fields['PRICE_PER_SESSION'];
                        }

                        if (($type == 'completed') && ($serviceCodeData->fields['PK_SERVICE_CLASS'] == 5)) {
                            $TOTAL_PAID_SESSION = $NUMBER_OF_SESSION;
                            if ($serviceCodeData->fields['STATUS'] == 'C') {
                                $TOTAL_AMOUNT_PAID = is_null($serviceCodeData->fields['TOTAL_AMOUNT_PAID']) ? 0 : $serviceCodeData->fields['TOTAL_AMOUNT_PAID'];
                            } else {
                                $TOTAL_AMOUNT_PAID = $serviceCodeData->fields['FINAL_AMOUNT'];
                            }
                        } else {
                            $TOTAL_PAID_SESSION = ($PRICE_PER_SESSION <= 0) ? $NUMBER_OF_SESSION : number_format($serviceCodeData->fields['TOTAL_AMOUNT_PAID'] / $PRICE_PER_SESSION, 2);
                            $TOTAL_AMOUNT_PAID = $serviceCodeData->fields['TOTAL_AMOUNT_PAID'];
                        }

                        $ENR_BALANCE = $NUMBER_OF_SESSION - $TOTAL_PAID_SESSION;
                        $SERVICE_CREDIT = $TOTAL_PAID_SESSION - $SESSION_COMPLETED;

                        if ($type == 'completed' && $SERVICE_CREDIT > 0) {
                            $SERVICE_CREDIT = 0;
                        }

                        $total_amount += $serviceCodeData->fields['FINAL_AMOUNT'];
                        $total_paid_amount += $TOTAL_AMOUNT_PAID; //$serviceCodeData->fields['TOTAL_AMOUNT_PAID'];
                        $total_used_amount +=  ($PRICE_PER_SESSION * $SESSION_COMPLETED);
                        $total_scheduled_amount += ($PRICE_PER_SESSION * $SESSION_SCHEDULED); ?>
                        <tr>
                            <td style="text-align: center;"><span class="badge-service" style="background-color: <?= getServiceCodeColor($serviceCodeData->fields['SERVICE_CODE']) ?>20; color: <?= getServiceCodeColor($serviceCodeData->fields['SERVICE_CODE']) ?>;"><?= $serviceCodeData->fields['SERVICE_CODE'] ?></span></td>
                            <td style="text-align: center"><?= ($enrollment_data->fields['CHARGE_TYPE'] == 'Membership' && $NUMBER_OF_SESSION <= 0) ? 'XX' : $NUMBER_OF_SESSION ?></td>
                            <td style="text-align: center;"><?= ($enrollment_data->fields['CHARGE_TYPE'] == 'Membership' && $SESSION_COMPLETED <= 0) ? 'XX' : $SESSION_COMPLETED ?></td>
                            <td style="text-align: center;"><?= ($enrollment_data->fields['CHARGE_TYPE'] == 'Membership' && $SESSION_SCHEDULED <= 0) ? 'XX' : $SESSION_SCHEDULED ?></td>
                            <td style="text-align: center; color:<?= ($ENR_BALANCE < 0) ? 'red' : 'black' ?>;"><?= number_format($ENR_BALANCE, 2) ?></td>
                            <td style="text-align: center"><?= number_format($serviceCodeData->fields['TOTAL_AMOUNT_PAID'] / (($PRICE_PER_SESSION == 0) ? 1 : $PRICE_PER_SESSION), 2) ?></td>
                            <td style="text-align: center; color:<?= ($SERVICE_CREDIT < 0) ? 'red' : 'black' ?>;"><?= number_format($SERVICE_CREDIT, 2) ?></td>
                        </tr>
                    <?php $serviceCodeData->MoveNext();
                    } ?>
                </tbody>

                <tfoot class="border-top-0">
                    <tr class="fw-bold">
                        <td style="text-align: center; font-size: 14px;">Amount</td>
                        <td style="text-align: center; font-size: 14px;">$<?= number_format($total_amount, 2) ?></td>
                        <td style="text-align: center; font-size: 14px;">$<?= number_format($total_amount - $total_used_amount < 0.00 ? $total_amount : $total_used_amount, 2) ?></td>
                        <td style="text-align: center; font-size: 14px;">$<?= number_format($total_scheduled_amount, 2) ?></td>
                        <td style="text-align: center; font-size: 14px; color:<?= ($total_amount - $total_paid_amount < -0.05) ? 'red' : 'black' ?>;">$<?= number_format((abs($total_amount - $total_paid_amount <= 0.05) ? 0 : $total_amount - $total_paid_amount), 2) ?></td>
                        <td style="text-align: center; font-size: 14px;">$<?= number_format($total_paid_amount, 2) ?></td>
                        <td style="text-align: center; font-size: 14px; color:<?= ($total_paid_amount - $total_used_amount < -0.99) ? 'red' : 'black' ?>;">$<?= number_format((abs($total_paid_amount - $total_used_amount) <= 0.99) ? 0 : ($total_paid_amount - $total_used_amount), 2) ?></td>
                    </tr>
                </tfoot>
            </table>

            <div class="enrollment_details" style="display: none;">

            </div>
        </div>
    </div>
<?php
    $enrollment_data->MoveNext();
} ?>