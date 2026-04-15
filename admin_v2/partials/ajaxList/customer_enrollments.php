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
        <h4 class="fw-bold mb-1"><?= $enr_title ?></h4>
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


<div class="enrollment-container mb-4" style="position: relative;">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0">Demo 2 | -3 PRI || GRP || PTY <span class="text-muted fw-normal ms-2">11/14/2025</span></h6>
        <a href="#" class="view-schedule text-primary">View Payment Schedule</a>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead class="table-light">
                <tr>
                    <th>Service Code</th>
                    <th>Enrolled</th>
                    <th>Used</th>
                    <th>Scheduled</th>
                    <th>Balance</th>
                    <th>Paid</th>
                    <th>Service Credit</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="badge-service bg-pri">PRI</span></td>
                    <td>52</td>
                    <td>0</td>
                    <td>6</td>
                    <td>19</td>
                    <td>17.00</td>
                    <td>17.00</td>
                </tr>
                <tr>
                    <td><span class="badge-service bg-grp">GRP</span></td>
                    <td>52</td>
                    <td>0</td>
                    <td>6</td>
                    <td>19</td>
                    <td>0</td>
                    <td>25.00</td>
                </tr>
                <tr>
                    <td><span class="badge-service bg-pty">PTY</span></td>
                    <td>52</td>
                    <td>0</td>
                    <td>6</td>
                    <td>19</td>
                    <td>0</td>
                    <td>15.00</td>
                </tr>
            </tbody>
            <tfoot class="border-top-0">
                <tr class="fw-bold">
                    <td>Amount</td>
                    <td>4,250.00</td>
                    <td>0.00</td>
                    <td>340.00</td>
                    <td>2,750.00</td>
                    <td>$1,500.00</td>
                    <td>1,500.00</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="d-flex justify-content-end align-items-center mt-3">
        <div class="form-check form-switch d-flex align-items-center">
            <input class="form-check-input me-2" type="checkbox" role="switch" id="autoPaySwitch">
            <label class="form-check-label autopay-label" for="autoPaySwitch">AutoPay</label>
        </div>
    </div>
</div>