<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;

$title = "STAFF PERFORMANCE REPORT";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

$week_number = $_SESSION['week_number'];
$YEAR = date('Y', strtotime($_SESSION['start_date']));

$from_date = date('Y-m-d', strtotime($_SESSION['start_date']));
$to_date = date('Y-m-d', strtotime($from_date . ' +6 day'));

$enrollment_date = "AND DOA_ENROLLMENT_MASTER.ENROLLMENT_DATE BETWEEN '" . date('Y-m-d', strtotime($from_date)) . "' AND '" . date('Y-m-d', strtotime($to_date)) . "'";
$appointment_date = "AND DOA_APPOINTMENT_MASTER.DATE BETWEEN '" . date('Y-m-d', strtotime($from_date)) . "' AND '" . date('Y-m-d', strtotime($to_date)) . "'";

$account_data = $db->Execute("SELECT * FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
$user_data = $db->Execute("SELECT * FROM DOA_USERS WHERE PK_USER = '$_SESSION[PK_USER]'");
$business_name = $account_data->RecordCount() > 0 ? $account_data->fields['BUSINESS_NAME'] : '';

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

$executive_data = $db_account->Execute("SELECT DISTINCT(ENROLLMENT_BY_ID) AS ENROLLMENT_BY_ID FROM DOA_ENROLLMENT_MASTER WHERE PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND PK_ENROLLMENT_MASTER > 0 $enrollment_date");
$executive_id = [];
while (!$executive_data->EOF) {
    $executive_id[] = $executive_data->fields['ENROLLMENT_BY_ID'];
    $executive_data->MoveNext();
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
                    <div class="table-responsive">
                        <h1 style="margin: 25px;"><?= $title ?></h1>
                        <table id="collapseTable" style="width:100%">
                            <thead>
                                <tr>
                                    <th style="width:50%; text-align: center; vertical-align:auto; font-weight: bold" colspan="5"><?= ($account_data->fields['FRANCHISE'] == 1) ? 'Franchisee: ' : '' ?><?= " (" . $concatenatedResults . ")" ?></th>
                                    <th style="width:50%; text-align: center; font-weight: bold" colspan="4">Week # <?= $week_number ?> (<?= date('m/d/Y', strtotime($from_date)) ?> - <?= date('m/d/Y', strtotime($to_date)) ?>)</th>
                                </tr>
                                <tr>
                                    <th style="width:10%; text-align: center" rowspan="2">Staff name</th>
                                    <th style="width:10%; text-align: center" rowspan="2">Number of<br>Guests</th>
                                    <th style="width:10%; text-align: center" colspan="2">Lessons taught</th>
                                    <th style="width:12%; text-align: center" colspan="3">$ value of misc. sales </th>
                                    <th style="width:10%; text-align: center" colspan="2">$ val. of lessons sales</th>
                                </tr>
                                <tr>
                                    <th style="width:10%; text-align: center">Private</th>
                                    <th style="width:10%; text-align: center">Class</th>
                                    <?php if ($account_data->fields['FRANCHISE'] == 1) { ?>
                                        <th style="width:10%; text-align: center">DOR/Sanct.<br>Competition</th>
                                    <?php } else { ?>
                                        <th style="width:10%; text-align: center">Sanct.<br>Competition</th>
                                    <?php } ?>
                                    <th style="width:10%; text-align: center">Showcase<br>Medal ball</th>
                                    <th style="width:10%; text-align: center">General Misc.<br>NonUnit</th>
                                    <th style="width:10%; text-align: center">Interview <br>Dept.</th>
                                    <th style="width:10%; text-align: center">Renewal <br>Dept.</th>
                                </tr>
                                <tr>
                                    <th style="width:10%; text-align: center; font-weight: bold; font-style: italic" colspan="9">INSTRUCTORS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $i = 1;
                                $row = $db->Execute("SELECT DISTINCT (DOA_USERS.PK_USER), DOA_USERS.FIRST_NAME, DOA_USERS.LAST_NAME, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.ACTIVE FROM DOA_USERS LEFT JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER WHERE DOA_USER_LOCATION.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_USERS.APPEAR_IN_CALENDAR = 1 AND DOA_USERS.ACTIVE = 1 AND DOA_USERS.IS_DELETED = 0 AND DOA_USERS.PK_ACCOUNT_MASTER = " . $_SESSION['PK_ACCOUNT_MASTER'] . " ORDER BY DOA_USERS.DISPLAY_ORDER ASC");
                                while (!$row->EOF) {
                                    $last_name = empty($row->fields['LAST_NAME']) ? '' : $row->fields['LAST_NAME'] . ',';

                                    $private_data = $db_account->Execute("SELECT SUM(DOA_SCHEDULING_CODE.UNIT) AS PRIVATE_COUNT FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_SCHEDULING_CODE ON DOA_APPOINTMENT_MASTER.PK_SCHEDULING_CODE = DOA_SCHEDULING_CODE.PK_SCHEDULING_CODE LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE IN ('NORMAL', 'AD-HOC') AND DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER != 2 AND DOA_APPOINTMENT_MASTER.IS_CHARGED = 1 " . $appointment_date . " AND DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER = " . $row->fields['PK_USER']);
                                    $group_data = $db_account->Execute("SELECT SUM(DOA_SCHEDULING_CODE.UNIT) AS GROUP_COUNT FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_SCHEDULING_CODE ON DOA_APPOINTMENT_MASTER.PK_SCHEDULING_CODE = DOA_SCHEDULING_CODE.PK_SCHEDULING_CODE LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE = 'GROUP' AND DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER != 2 " . $appointment_date . " AND DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER = " . $row->fields['PK_USER']);

                                    $dor_misc_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE_PROVIDER.PERCENTAGE_AMOUNT) AS MISC_TOTAL FROM DOA_ENROLLMENT_SERVICE_PROVIDER LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE = 16 AND DOA_ENROLLMENT_MASTER.MISC_TYPE = 'DOR' AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID = " . $row->fields['PK_USER'] . " $enrollment_date");
                                    $showcase_misc_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE_PROVIDER.PERCENTAGE_AMOUNT) AS MISC_TOTAL FROM DOA_ENROLLMENT_SERVICE_PROVIDER LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE = 16 AND DOA_ENROLLMENT_MASTER.MISC_TYPE = 'SHOWCASE' AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID = " . $row->fields['PK_USER'] . " $enrollment_date");
                                    $general_misc_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE_PROVIDER.PERCENTAGE_AMOUNT) AS MISC_TOTAL FROM DOA_ENROLLMENT_SERVICE_PROVIDER LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE = 16 AND DOA_ENROLLMENT_MASTER.MISC_TYPE = 'GENERAL' AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID = " . $row->fields['PK_USER'] . " $enrollment_date");

                                    $interview_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE_PROVIDER.PERCENTAGE_AMOUNT) AS INTERVIEW_TOTAL FROM DOA_ENROLLMENT_SERVICE_PROVIDER LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE NOT IN (13,16) AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID = " . $row->fields['PK_USER'] . " $enrollment_date");
                                    $renewal_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE_PROVIDER.PERCENTAGE_AMOUNT) AS RENEWAL_TOTAL FROM DOA_ENROLLMENT_SERVICE_PROVIDER LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE = 13 AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID = " . $row->fields['PK_USER'] . " $enrollment_date");
                                ?>
                                    <tr>
                                        <td><?= $last_name . ' ' . $row->fields['FIRST_NAME'] ?></td>
                                        <td></td>
                                        <td style="text-align: center"><?= $private_data->fields['PRIVATE_COUNT'] ?? 0 ?></td>
                                        <td style="text-align: center"><?= $group_data->fields['GROUP_COUNT'] ?? 0 ?></td>
                                        <td style="text-align: right">$<?= number_format($dor_misc_data->fields['MISC_TOTAL'], 2) ?></td>
                                        <td style="text-align: right">$<?= number_format($showcase_misc_data->fields['MISC_TOTAL'], 2) ?></td>
                                        <td style="text-align: right">$<?= number_format($general_misc_data->fields['MISC_TOTAL'], 2) ?></td>
                                        <td style="text-align: right">$<?= number_format($interview_data->fields['INTERVIEW_TOTAL'], 2) ?></td>
                                        <td style="text-align: right">$<?= number_format($renewal_data->fields['RENEWAL_TOTAL'], 2) ?></td>
                                    </tr>
                                <?php $row->MoveNext();
                                    $i++;
                                } ?>
                            </tbody>

                            <thead>
                                <tr>
                                    <th style="width:10%; text-align: center; font-weight: bold; font-style: italic" colspan="9">EXECUTIVES</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $i = 1;
                                $row = $db->Execute("SELECT DISTINCT (PK_USER) AS PK_USER, FIRST_NAME, LAST_NAME FROM DOA_USERS WHERE PK_USER IN (" . implode(',', $executive_id) . ")");
                                while (!$row->EOF) {
                                    $last_name = empty($row->fields['LAST_NAME']) ? '' : $row->fields['LAST_NAME'] . ',';

                                    $executive_dor_misc_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_BILLING.TOTAL_AMOUNT) AS MISC_TOTAL FROM DOA_ENROLLMENT_BILLING JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE = 16 AND DOA_ENROLLMENT_MASTER.MISC_TYPE = 'DOR' AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID = " . $row->fields['PK_USER'] . " $enrollment_date");
                                    $executive_showcase_misc_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_BILLING.TOTAL_AMOUNT) AS MISC_TOTAL FROM DOA_ENROLLMENT_BILLING JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE = 16 AND DOA_ENROLLMENT_MASTER.MISC_TYPE = 'SHOWCASE' AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID = " . $row->fields['PK_USER'] . " $enrollment_date");
                                    $executive_general_misc_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_BILLING.TOTAL_AMOUNT) AS MISC_TOTAL FROM DOA_ENROLLMENT_BILLING JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE = 16 AND DOA_ENROLLMENT_MASTER.MISC_TYPE = 'GENERAL' AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID = " . $row->fields['PK_USER'] . " $enrollment_date");

                                    $executive_interview_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_BILLING.TOTAL_AMOUNT) AS INTERVIEW_TOTAL FROM DOA_ENROLLMENT_BILLING JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE NOT IN (13,16) AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID = " . $row->fields['PK_USER'] . " $enrollment_date");
                                    $executive_renewal_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_BILLING.TOTAL_AMOUNT) AS RENEWAL_TOTAL FROM DOA_ENROLLMENT_BILLING JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE = 13 AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID = " . $row->fields['PK_USER'] . " $enrollment_date");
                                ?>
                                    <tr>
                                        <td><?= $last_name . ' ' . $row->fields['FIRST_NAME'] ?></td>
                                        <td style="text-align: center">-----</td>
                                        <td style="text-align: center">-----</td>
                                        <td style="text-align: center">-----</td>
                                        <td style="text-align: right">$<?= number_format($executive_dor_misc_data->fields['MISC_TOTAL'], 2) ?></td>
                                        <td style="text-align: right">$<?= number_format($executive_showcase_misc_data->fields['MISC_TOTAL'], 2) ?></td>
                                        <td style="text-align: right">$<?= number_format($executive_general_misc_data->fields['MISC_TOTAL'], 2) ?></td>
                                        <td style="text-align: right">$<?= number_format($executive_interview_data->fields['INTERVIEW_TOTAL'], 2) ?></td>
                                        <td style="text-align: right">$<?= number_format($executive_renewal_data->fields['RENEWAL_TOTAL'], 2) ?></td>
                                    </tr>
                                <?php $row->MoveNext();
                                    $i++;
                                } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>