<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;

$title = "PAYMENTS MADE REPORT";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5]) ){
    header("location:../login.php");
    exit;
}

$week_number = $_SESSION['week_number'];
$YEAR = date('Y', strtotime($_SESSION['start_date']));

$from_date = date('Y-m-d', strtotime($_SESSION['start_date']));
$to_date = date('Y-m-d', strtotime($from_date. ' +6 day'));

$payment_date = "AND DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."'";
$enrollment_date = "AND DOA_ENROLLMENT_MASTER.ENROLLMENT_DATE BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."'";
$appointment_date = "AND DOA_APPOINTMENT_MASTER.DATE BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."'";

$account_data = $db->Execute("SELECT * FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
$user_data = $db->Execute("SELECT * FROM DOA_USERS WHERE PK_USER = '$_SESSION[PK_USER]'");
$business_name = $account_data->RecordCount() > 0 ? $account_data->fields['BUSINESS_NAME'] : '';

$location_name='';
$results = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME FROM DOA_LOCATION WHERE PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND ACTIVE = 1 AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
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

$executive_data = $db_account->Execute("SELECT DISTINCT(ENROLLMENT_BY_ID) AS ENROLLMENT_BY_ID FROM DOA_ENROLLMENT_MASTER WHERE PK_ENROLLMENT_MASTER > 0 $enrollment_date");
$executive_id = [];
while (!$executive_data->EOF) {
    $executive_id[] = $executive_data->fields['ENROLLMENT_BY_ID'];
    $executive_data->MoveNext();
}
?>

<!DOCTYPE html>
<html lang="en">
<?php require_once('../includes/header.php');?>
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
                        <h1 style="margin: 25px;"><?=$title?></h1>
                        <table id="collapseTable" style="width:100%">
                            <thead>
                                <tr>
                                    <th style="width:50%; text-align: center; vertical-align:auto; font-weight: bold" colspan="10"><?=($account_data->fields['FRANCHISE']==1)?'Franchisee: ':''?><?=$business_name." (".$concatenatedResults.")"?></th>
                                    <th style="width:50%; text-align: center; font-weight: bold" colspan="7">Week # <?=$week_number?> (<?=date('m/d/Y', strtotime($from_date))?> - <?=date('m/d/Y', strtotime($to_date))?>)</th>
                                </tr>
                            <tr>
                                <th style="width:10%; text-align: center">Payment Date</th>
                                <th style="width:10%; text-align: center" >Payment Amount</th>
                                <th style="width:10%; text-align: center" >Payment Title</th>
                                <th style="width:12%; text-align: center" >Payment Method</th>
                                <th style="width:10%; text-align: center" >Card Type</th>
                                <th style="width:10%; text-align: center" >Receipt</th>
                                <th style="width:10%; text-align: center" >Memo</th>
                                <th style="width:10%; text-align: center" >Client</th>
                                <th style="width:10%; text-align: center" >Enrollment Name</th>
                                <th style="width:10%; text-align: center" >Enrollment Date</th>
                                <th style="width:10%; text-align: center" >Enrollment Type</th>
                                <th style="width:10%; text-align: center" >Enrollment Cost</th>
                                <th style="width:10%; text-align: center" >Enrollment Balance</th>
                                <th style="width:10%; text-align: center" >Closer</th>
                                <th style="width:10%; text-align: center" >Teacher1</th>
                                <th style="width:10%; text-align: center" >Teacher2</th>
                                <th style="width:10%; text-align: center" >Teacher3</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            $i=1;
                            //$row = $db->Execute("SELECT DISTINCT (DOA_USERS.PK_USER), DOA_USERS.FIRST_NAME, DOA_USERS.LAST_NAME, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.ACTIVE FROM DOA_USERS LEFT JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER WHERE DOA_USER_LOCATION.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_USERS.APPEAR_IN_CALENDAR = 1 AND DOA_USERS.ACTIVE = 1 AND DOA_USERS.IS_DELETED = 0 AND DOA_USERS.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER']." ORDER BY DOA_USERS.DISPLAY_ORDER ASC");
                            $row = $db_account->Execute("SELECT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, PAYMENT_DATE, AMOUNT, PAYMENT_INFO, PAYMENT_TYPE, RECEIPT_NUMBER, MEMO, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS CLIENT, ENROLLMENT_NAME, ENROLLMENT_DATE, ENROLLMENT_TYPE, FINAL_AMOUNT, TOTAL_AMOUNT_PAID, ENROLLMENT_BY_ID FROM DOA_ENROLLMENT_PAYMENT LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN $master_database.DOA_PAYMENT_TYPE AS DOA_PAYMENT_TYPE ON DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE=DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER=DOA_USER_MASTER.PK_USER_MASTER LEFT JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USER_MASTER.PK_USER=DOA_USERS.PK_USER LEFT JOIN $master_database.DOA_ENROLLMENT_TYPE AS DOA_ENROLLMENT_TYPE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE=DOA_ENROLLMENT_TYPE.PK_ENROLLMENT_TYPE LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") ".$enrollment_date);
                            while (!$row->EOF) {
                                $enrollment_by = $db->Execute("SELECT CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS CLOSER FROM DOA_USERS WHERE PK_USER = ".$row->fields['ENROLLMENT_BY_ID']);
                                $service_provider = $db->Execute("SELECT CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS TEACHER FROM $account_database.DOA_ENROLLMENT_MASTER AS DOA_ENROLLMENT_MASTER LEFT JOIN $account_database.DOA_ENROLLMENT_SERVICE_PROVIDER AS DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_USERS ON DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID=DOA_USERS.PK_USER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = ".$row->fields['PK_ENROLLMENT_MASTER']);
                                ?>
                                <tr>
                                    <td><?=$row->fields['PAYMENT_DATE']?></td>
                                    <td style="text-align: right">$<?=$row->fields['AMOUNT']?></td>
                                    <td style="text-align: left"><?=$row->fields['PAYMENT_INFO']?></td>
                                    <td style="text-align: center"><?=$row->fields['PAYMENT_TYPE']?></td>
                                    <?php if($row->fields['PAYMENT_TYPE'] == 'Credit Card' || $row->fields['PAYMENT_TYPE'] == 'Visa' || $row->fields['PAYMENT_TYPE'] == 'Master Card' || $row->fields['PAYMENT_TYPE'] == 'American Express' || $row->fields['PAYMENT_TYPE'] == 'Card' || $row->fields['PAYMENT_TYPE'] == 'Card On File') {?>
                                        <td style="text-align: center"><?=$row->fields['PAYMENT_TYPE']?></td>
                                    <?php } else { ?>
                                        <td style="text-align: center"></td>
                                    <?php } ?>
                                    <td style="text-align: right"><?=$row->fields['RECEIPT_NUMBER']?></td>
                                    <td style="text-align: left"><?=$row->fields['MEMO']?></td>
                                    <td style="text-align: left"><?=$row->fields['CLIENT']?></td>
                                    <td style="text-align: left"><?=$row->fields['ENROLLMENT_NAME']?></td>
                                    <td style="text-align: right"><?=$row->fields['ENROLLMENT_DATE']?></td>
                                    <td style="text-align: right"><?=$row->fields['ENROLLMENT_TYPE']?></td>
                                    <td style="text-align: right">$<?=$row->fields['FINAL_AMOUNT']?></td>
                                    <td style="text-align: right">$<?=number_format($row->fields['FINAL_AMOUNT'] - $row->fields['TOTAL_AMOUNT_PAID'], 2)?></td>
                                    <td style="text-align: left"><?=$enrollment_by->fields['CLOSER']?></td>
                                    <?php if($service_provider->RecordCount() > 0) {
                                        while (!$service_provider->EOF) { ?>
                                            <td style="text-align: left"><?=$service_provider->fields['TEACHER']?></td>
                                            <?php $service_provider->MoveNext();
                                        }
                                    } ?>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <?php $row->MoveNext();
                                $i++; } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
