<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;

$title = "CASH REPORT";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}


$from_date = date('Y-m-d', strtotime($_GET['start_date']));
$to_date = date('Y-m-d', strtotime($_GET['end_date']));
$service_provider_id = $_GET['service_provider_id'];

$payment_date = "AND DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (" . $service_provider_id . ") AND DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE BETWEEN '" . date('Y-m-d', strtotime($from_date)) . "' AND '" . date('Y-m-d', strtotime($to_date)) . "' GROUP BY SERVICE_PROVIDER_ID ORDER BY DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE DESC";

$account_data = $db->Execute("SELECT * FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
$user_data = $db->Execute("SELECT * FROM DOA_USERS WHERE PK_USER = '$_SESSION[PK_USER]'");
$business_name = $account_data->RecordCount() > 0 ? $account_data->fields['BUSINESS_NAME'] : '';
if (preg_match("/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/", $business_name)) {
    $business_name = '';
} else {
    $business_name = '' . $business_name;
}

$location_name = '';
$results = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME FROM DOA_LOCATION WHERE PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND ACTIVE = 1 AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
$resultsArray = [];
while (!$results->EOF) {
    $resultsArray[] = $results->fields['LOCATION_NAME'];
    $results->MoveNext();
}
$totalResults = count($resultsArray);
$concatenatedResults = "";
foreach ($resultsArray as $key => $result) {
    // Append the current result to the concatenated string
    $concatenatedResults .= $result;

    // If it's not the last result, append a comma
    if ($key < $totalResults - 1) {
        $concatenatedResults .= ", ";
    }
}
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

                    <?php
                    $each_service_provider = $db_account->Execute("SELECT distinct DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID, DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_PERCENTAGE, DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, PAYMENT_DATE, AMOUNT, PAYMENT_INFO, PAYMENT_TYPE, RECEIPT_NUMBER, MEMO, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS CLIENT, ENROLLMENT_NAME, ENROLLMENT_ID, ENROLLMENT_TYPE, TOTAL_AMOUNT, ENROLLMENT_BY_ID FROM DOA_ENROLLMENT_PAYMENT INNER JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER INNER JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER INNER JOIN $master_database.DOA_PAYMENT_TYPE AS DOA_PAYMENT_TYPE ON DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE=DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE INNER JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER=DOA_USER_MASTER.PK_USER_MASTER INNER JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USER_MASTER.PK_USER=DOA_USERS.PK_USER INNER JOIN $master_database.DOA_ENROLLMENT_TYPE AS DOA_ENROLLMENT_TYPE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE=DOA_ENROLLMENT_TYPE.PK_ENROLLMENT_TYPE INNER JOIN DOA_ENROLLMENT_BILLING ON DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") " . $payment_date);
                    while (!$each_service_provider->EOF) {
                        $name = $db->Execute("SELECT CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS TEACHER FROM DOA_USERS WHERE DOA_USERS.PK_USER = " . $each_service_provider->fields['SERVICE_PROVIDER_ID']);
                        $service_provider_id_per_table = $each_service_provider->fields['SERVICE_PROVIDER_ID'];
                        $total_portion = 0;
                        $total_refund = 0;
                        $total_amount = 0;
                        $total_refund_amount = 0;

                        // $grand_total_amount = 0;
                        // $grand_total_portion = 0;
                        // $grand_total_refund = 0;
                        // $grand_total_refund_amount = 0;
                    ?>

                        <div class="table-responsive">
                            <table id="collapseTable" style="width:100%">
                                <thead>
                                    <tr>
                                        <th style="width:50%; text-align: center; vertical-align:auto; font-weight: bold" colspan="4"><?= ($account_data->fields['FRANCHISE'] == 1) ? 'Franchisee: ' : '' ?><?= $business_name . " (" . $concatenatedResults . ")" ?></th>
                                        <th style="width:50%; text-align: center; font-weight: bold" colspan="8">(<?= date('m/d/Y', strtotime($from_date)) ?> - <?= date('m/d/Y', strtotime($to_date)) ?>)</th>
                                    </tr>
                                    <tr>
                                        <th style="width:50%; text-align: center; vertical-align:auto; font-weight: bold" colspan="12"><?= $name->fields['TEACHER'] ?></th>
                                    </tr>
                                    <tr>
                                        <th style="width:8%; text-align: center">Receipt #</th>
                                        <th style="width:8%; text-align: center">Payment Date</th>
                                        <th style="width:8%; text-align: center">Amount</th>
                                        <th style="width:10%; text-align: center">Student Name</th>
                                        <th style="width:8%; text-align: center">Type</th>
                                        <th style="width:5%; text-align: center">ENR ID</th>
                                        <th style="width:10%; text-align: center">Enrollment</th>
                                        <th style="width:10%; text-align: center">Enrollment Type</th>
                                        <th style="width:10%; text-align: center">Units/Total Cost</th>
                                        <th style="width:8%; text-align: center">Portion</th>
                                        <th style="width:5%; text-align: center">%</th>
                                        <th style="width:8%; text-align: center">Comment/Remark</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php

                                    $row = $db_account->Execute("SELECT DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID, DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_PERCENTAGE, DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, DOA_ENROLLMENT_PAYMENT.TYPE, PAYMENT_DATE, AMOUNT, PAYMENT_INFO, PAYMENT_TYPE, RECEIPT_NUMBER, MEMO, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS CLIENT, ENROLLMENT_NAME, ENROLLMENT_ID, ENROLLMENT_TYPE, TOTAL_AMOUNT, ENROLLMENT_BY_ID FROM DOA_ENROLLMENT_PAYMENT INNER JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER INNER JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER INNER JOIN $master_database.DOA_PAYMENT_TYPE AS DOA_PAYMENT_TYPE ON DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE=DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE INNER JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER=DOA_USER_MASTER.PK_USER_MASTER INNER JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USER_MASTER.PK_USER=DOA_USERS.PK_USER INNER JOIN $master_database.DOA_ENROLLMENT_TYPE AS DOA_ENROLLMENT_TYPE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE=DOA_ENROLLMENT_TYPE.PK_ENROLLMENT_TYPE INNER JOIN DOA_ENROLLMENT_BILLING ON DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (" . $service_provider_id_per_table . ") AND DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE BETWEEN '" . date('Y-m-d', strtotime($from_date)) . "' AND '" . date('Y-m-d', strtotime($to_date)) . "' ORDER BY DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE DESC");
                                    while (!$row->EOF) {
                                        $sessions = $db_account->Execute("SELECT NUMBER_OF_SESSION FROM DOA_ENROLLMENT_SERVICE WHERE PK_ENROLLMENT_MASTER = " . $row->fields['PK_ENROLLMENT_MASTER']);
                                        $units = $sessions->fields['NUMBER_OF_SESSION'] ?? 0;
                                        $service_provider = $db->Execute("SELECT CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS TEACHER FROM DOA_USERS WHERE DOA_USERS.PK_USER = " . $row->fields['SERVICE_PROVIDER_ID']);
                                        $portion = $row->fields['AMOUNT'] * ($row->fields['SERVICE_PROVIDER_PERCENTAGE'] / 100);
                                        $total_portion += $portion; // Add to the sum
                                        $total_amount += $row->fields['AMOUNT'];
                                    ?>
                                        <tr>
                                            <td style="text-align: center"><?= $row->fields['RECEIPT_NUMBER'] ?></td>
                                            <td style="text-align: center"><?= date('m-d-Y', strtotime($row->fields['PAYMENT_DATE'])) ?></td>
                                            <td style="text-align: center">$<?= $row->fields['AMOUNT'] ?></td>
                                            <td style="text-align: center"><?= $row->fields['CLIENT'] ?></td>
                                            <td style="text-align: center"><?= $row->fields['PAYMENT_TYPE'] ?></td>
                                            <td style="text-align: center"><?= $row->fields['ENROLLMENT_ID'] ?></td>
                                            <td style="text-align: center"><?= $row->fields['ENROLLMENT_NAME'] ?></td>
                                            <td style="text-align: center"><?= $row->fields['ENROLLMENT_TYPE'] ?></td>
                                            <td style="text-align: center"><?= $units . '/$' . $row->fields['TOTAL_AMOUNT'] ?></td>
                                            <td style="text-align: center">$<?= number_format($row->fields['AMOUNT'] * ($row->fields['SERVICE_PROVIDER_PERCENTAGE'] / 100), 2) ?></td>
                                            <td style="text-align: center"><?= number_format($row->fields['SERVICE_PROVIDER_PERCENTAGE'], 0) ?></td>
                                            <td style="text-align: center"></td>
                                        </tr>
                                        <?php if ($row->fields['TYPE'] == 'Refund') {
                                            $total_refund += $row->fields['AMOUNT'] * ($row->fields['SERVICE_PROVIDER_PERCENTAGE'] / 100);
                                            $total_refund_amount += $row->fields['AMOUNT'];
                                        ?>
                                            <tr>
                                                <td style="text-align: center; color: red"><?= $row->fields['RECEIPT_NUMBER'] ?></td>
                                                <td style="text-align: center; color: red"><?= date('m-d-Y', strtotime($row->fields['PAYMENT_DATE'])) ?></td>
                                                <td style="text-align: center; color: red">$<?= $row->fields['AMOUNT'] ?></td>
                                                <td style="text-align: center; color: red"><?= $row->fields['CLIENT'] ?></td>
                                                <td style="text-align: center; color: red"><?= '(Refund) ' . $row->fields['PAYMENT_TYPE'] ?></td>
                                                <td style="text-align: center; color: red"><?= $row->fields['ENROLLMENT_ID'] ?></td>
                                                <td style="text-align: center; color: red"><?= $row->fields['ENROLLMENT_NAME'] ?></td>
                                                <td style="text-align: center; color: red"><?= $row->fields['ENROLLMENT_TYPE'] ?></td>
                                                <td style="text-align: center; color: red"><?= $units . '/$' . $row->fields['TOTAL_AMOUNT'] ?></td>
                                                <td style="text-align: center; color: red">$<?= number_format($row->fields['AMOUNT'] * ($row->fields['SERVICE_PROVIDER_PERCENTAGE'] / 100), 2) ?></td>
                                                <td style="text-align: center; color: red"><?= number_format($row->fields['SERVICE_PROVIDER_PERCENTAGE'], 0) ?></td>
                                                <td style="text-align: center; color: red"></td>
                                            </tr>
                                        <?php } ?>
                                    <?php $row->MoveNext();
                                    }

                                    // Store service provider summary for later use
                                    $service_provider_summaries[] = array(
                                        'name' => $name->fields['TEACHER'],
                                        'total_amount' => $total_amount - $total_refund_amount,
                                        'total_portion' => $total_portion - $total_refund
                                    );

                                    // Add to grand totals
                                    // $grand_total_amount += ($total_amount - $total_refund_amount);
                                    // $grand_total_portion += ($total_portion - $total_refund);
                                    // $grand_total_refund_amount += $total_refund_amount;
                                    // $grand_total_refund += $total_refund;
                                    ?>
                                    <tr>
                                        <th style="text-align: center; vertical-align:auto; font-weight: bold" colspan="8"></th>
                                        <th style="text-align: center; vertical-align:auto; font-weight: bold" colspan="1">$<?= number_format($total_portion - $total_refund, 2) ?></th>
                                        <th style="text-align: center; vertical-align:auto; font-weight: bold" colspan="3"></th>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <br><br><br>
                    <?php
                        $each_service_provider->MoveNext();
                    } ?>
                </div>
            </div>
        </div>
    </div>
    <?php require_once('../includes/footer.php'); ?>
</body>

</html>