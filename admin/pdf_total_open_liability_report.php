<?php
require_once('../global/config.php');
$title = "Total Open Liability Report";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

$type = $_GET['type'];

$week_number = $_GET['week_number'];
$YEAR = date('Y');

$from_date = date('Y-m-d', strtotime($_GET['start_date']));
$to_date = date('Y-m-d', strtotime($_GET['end_date']));

?>

<!DOCTYPE html>
<html lang="en">
<?php require_once('../includes/header.php'); ?>
<style>
    table,
    td,
    th {
        border: 1px solid black;
        padding: 10px;
    }

    #collapseTable {
        border-collapse: collapse;
    }

    body {
        font-size: 12px;
    }
</style>

<body class="skin-default-dark fixed-layout">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div>
                        <h3 class="card-title" style="text-align: center; font-size:medium; font-weight: bold"><?= $title ?></h3>
                    </div>

                    <div class="table-responsive">
                        <table id="collapseTable" style="width:100%">
                            <thead>
                                <tr>
                                    <?php
                                    $i = 1;
                                    $row = $db_account->Execute("SELECT DISTINCT PK_ENROLLMENT_MASTER FROM DOA_APPOINTMENT_MASTER WHERE PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND PK_ENROLLMENT_MASTER != 0 AND DATE BETWEEN '" . date('Y-m-d', strtotime($from_date)) . "' AND '" . date('Y-m-d', strtotime($to_date)) . "' ORDER BY DATE ASC");
                                    $sum_of_amount_ahead = 0;
                                    while (!$row->EOF) {
                                        $appointment = $db_account->Execute("SELECT DATE FROM DOA_APPOINTMENT_MASTER WHERE DATE BETWEEN '" . date('Y-m-d', strtotime($from_date)) . "' AND '" . date('Y-m-d', strtotime($to_date)) . "' AND PK_ENROLLMENT_MASTER = " . $row->fields['PK_ENROLLMENT_MASTER']);
                                        $customer = $db->Execute("SELECT CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER LEFT JOIN $account_database.DOA_ENROLLMENT_MASTER AS DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER=DOA_USER_MASTER.PK_USER_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = " . $row->fields['PK_ENROLLMENT_MASTER']);
                                        $enrollment = $db_account->Execute("SELECT DOA_ENROLLMENT_MASTER.ENROLLMENT_ID FROM `DOA_ENROLLMENT_MASTER` WHERE PK_ENROLLMENT_MASTER = " . $row->fields['PK_ENROLLMENT_MASTER']);
                                        $used_session_count = $db_account->Execute("SELECT COUNT(`PK_ENROLLMENT_MASTER`) AS USED_SESSION_COUNT, PK_SERVICE_MASTER FROM `DOA_APPOINTMENT_MASTER` WHERE `PK_ENROLLMENT_MASTER` = " . $row->fields['PK_ENROLLMENT_MASTER']);
                                        $PK_SERVICE_MASTER = ($used_session_count->RecordCount() > 0) ? $used_session_count->fields['PK_SERVICE_MASTER'] : 0;
                                        $total_session = $db_account->Execute("SELECT SUM(`NUMBER_OF_SESSION`) AS TOTAL_SESSION_COUNT FROM `DOA_ENROLLMENT_SERVICE` WHERE  `PK_ENROLLMENT_MASTER` = " . $row->fields['PK_ENROLLMENT_MASTER'] . " AND `PK_SERVICE_MASTER` = " . $PK_SERVICE_MASTER);
                                        if ($total_session->RecordCount() <= 0 || $total_session->fields['TOTAL_SESSION_COUNT'] == '') {
                                            $total_session = $db_account->Execute("SELECT SUM(`NUMBER_OF_SESSION`) AS TOTAL_SESSION_COUNT FROM `DOA_ENROLLMENT_SERVICE` WHERE  `PK_ENROLLMENT_MASTER` = " . $row->fields['PK_ENROLLMENT_MASTER']);
                                        }
                                        $total_session_count = ($total_session->RecordCount() > 0) ? $total_session->fields['TOTAL_SESSION_COUNT'] : 0;
                                        $total_bill_and_paid = $db_account->Execute("SELECT SUM(BILLED_AMOUNT) AS TOTAL_BILL, SUM(PAID_AMOUNT) AS TOTAL_PAID, SUM(BALANCE) AS BALANCE FROM DOA_ENROLLMENT_LEDGER WHERE `PK_ENROLLMENT_MASTER`=" . $row->fields['PK_ENROLLMENT_MASTER']);
                                        $total_amount = $db_account->Execute("SELECT SUM(TOTAL_AMOUNT) AS TOTAL_AMOUNT FROM `DOA_ENROLLMENT_BILLING` WHERE `PK_ENROLLMENT_MASTER`=" . $row->fields['PK_ENROLLMENT_MASTER']);
                                        $price_per_session = ($total_session_count > 0) ? $total_amount->fields['TOTAL_AMOUNT'] / $total_session_count : 0.00;
                                        $total_paid = $total_bill_and_paid->fields['TOTAL_PAID'];
                                        $total_used = $used_session_count->fields['USED_SESSION_COUNT'] * $price_per_session;
                                        $paid_ahead = $total_amount->fields['TOTAL_AMOUNT'] - $total_used;
                                        if ($paid_ahead > 0) {
                                            $sum_of_amount_ahead += $paid_ahead;
                                        }
                                        $row->MoveNext();
                                        $i++;
                                    }
                                    ?>
                                    <th style="width:30%; text-align: center; vertical-align:auto; font-weight: bold">Client</th>
                                    <th style="width:15%; text-align: center; font-weight: bold">Enrollment Id</th>
                                    <th style="width:15%; text-align: center; font-weight: bold">Amount Ahead (<?= number_format($sum_of_amount_ahead, 2) ?>)</th>
                                    <th style="width:15%; text-align: center; font-weight: bold">Date of Last Service</th>
                                    <th style="width:15%; text-align: center; font-weight: bold">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $i = 1;
                                $row = $db_account->Execute("SELECT DISTINCT PK_ENROLLMENT_MASTER FROM DOA_APPOINTMENT_MASTER WHERE PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND PK_ENROLLMENT_MASTER != 0 AND DATE BETWEEN '" . date('Y-m-d', strtotime($from_date)) . "' AND '" . date('Y-m-d', strtotime($to_date)) . "' ORDER BY DATE ASC");
                                while (!$row->EOF) {
                                    $appointment = $db_account->Execute("SELECT DATE FROM DOA_APPOINTMENT_MASTER WHERE DATE BETWEEN '" . date('Y-m-d', strtotime($from_date)) . "' AND '" . date('Y-m-d', strtotime($to_date)) . "' AND PK_ENROLLMENT_MASTER = " . $row->fields['PK_ENROLLMENT_MASTER']);
                                    $customer = $db->Execute("SELECT CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER LEFT JOIN $account_database.DOA_ENROLLMENT_MASTER AS DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER=DOA_USER_MASTER.PK_USER_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = " . $row->fields['PK_ENROLLMENT_MASTER']);
                                    $enrollment = $db_account->Execute("SELECT DOA_ENROLLMENT_MASTER.ENROLLMENT_ID FROM `DOA_ENROLLMENT_MASTER` WHERE PK_ENROLLMENT_MASTER = " . $row->fields['PK_ENROLLMENT_MASTER']);
                                    $used_session_count = $db_account->Execute("SELECT COUNT(`PK_ENROLLMENT_MASTER`) AS USED_SESSION_COUNT, PK_SERVICE_MASTER FROM `DOA_APPOINTMENT_MASTER` WHERE `PK_ENROLLMENT_MASTER` = " . $row->fields['PK_ENROLLMENT_MASTER']);
                                    $PK_SERVICE_MASTER = ($used_session_count->RecordCount() > 0) ? $used_session_count->fields['PK_SERVICE_MASTER'] : 0;
                                    $total_session = $db_account->Execute("SELECT SUM(`NUMBER_OF_SESSION`) AS TOTAL_SESSION_COUNT FROM `DOA_ENROLLMENT_SERVICE` WHERE  `PK_ENROLLMENT_MASTER` = " . $row->fields['PK_ENROLLMENT_MASTER'] . " AND `PK_SERVICE_MASTER` = " . $PK_SERVICE_MASTER);
                                    if ($total_session->RecordCount() <= 0 || $total_session->fields['TOTAL_SESSION_COUNT'] == '') {
                                        $total_session = $db_account->Execute("SELECT SUM(`NUMBER_OF_SESSION`) AS TOTAL_SESSION_COUNT FROM `DOA_ENROLLMENT_SERVICE` WHERE  `PK_ENROLLMENT_MASTER` = " . $row->fields['PK_ENROLLMENT_MASTER']);
                                    }
                                    $total_session_count = ($total_session->RecordCount() > 0) ? $total_session->fields['TOTAL_SESSION_COUNT'] : 0;
                                    $total_bill_and_paid = $db_account->Execute("SELECT SUM(BILLED_AMOUNT) AS TOTAL_BILL, SUM(PAID_AMOUNT) AS TOTAL_PAID, SUM(BALANCE) AS BALANCE FROM DOA_ENROLLMENT_LEDGER WHERE `PK_ENROLLMENT_MASTER`=" . $row->fields['PK_ENROLLMENT_MASTER']);
                                    $total_amount = $db_account->Execute("SELECT SUM(TOTAL_AMOUNT) AS TOTAL_AMOUNT FROM `DOA_ENROLLMENT_BILLING` WHERE `PK_ENROLLMENT_MASTER`=" . $row->fields['PK_ENROLLMENT_MASTER']);
                                    $price_per_session = ($total_session_count > 0) ? $total_amount->fields['TOTAL_AMOUNT'] / $total_session_count : 0.00;
                                    $total_paid = $total_bill_and_paid->fields['TOTAL_PAID'];
                                    $total_used = $used_session_count->fields['USED_SESSION_COUNT'] * $price_per_session;
                                    $paid_ahead = $total_amount->fields['TOTAL_AMOUNT'] - $total_used;
                                    if ($paid_ahead > 0) {
                                        $sum_of_amount_ahead += $paid_ahead;
                                ?>
                                        <tr>
                                            <td><?= $customer->fields['NAME'] ?></td>
                                            <td style="text-align: center"><?= $enrollment->fields['ENROLLMENT_ID'] ?></td>
                                            <td style="text-align: right"><?= number_format($paid_ahead, 2) ?></td>
                                            <td style="text-align: center"><?= date('m-d-Y', strtotime($appointment->fields['DATE'])) ?></td>
                                            <td style="text-align: right"><?= number_format($total_amount->fields['TOTAL_AMOUNT'], 2) ?></td>
                                        </tr>
                                <?php }
                                    $row->MoveNext();
                                    $i++;
                                } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php require_once('../includes/footer.php'); ?>
</body>

</html>