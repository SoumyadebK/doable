<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;

$title = "CASH REPORT";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5]) ){
    header("location:../login.php");
    exit;
}

$type = $_GET['type'];

$from_date = date('Y-m-d', strtotime($_GET['start_date']));
$to_date = date('Y-m-d', strtotime($_GET['end_date']));
$service_provider_id = $_GET['service_provider_id'];

$payment_date = "AND DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (".$service_provider_id.") AND DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."' GROUP BY SERVICE_PROVIDER_ID ORDER BY DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE DESC";

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
?>

<!DOCTYPE html>
<html lang="en">
<?php require_once('../includes/header.php');?>

<body class="skin-default-dark fixed-layout">
<?php require_once('../includes/loader.php');?>
<div id="main-wrapper">
    <?php require_once('../includes/top_menu.php');?>
    <div class="page-wrapper">
        <?php require_once('../includes/top_menu_bar.php') ?>
        <div class="container-fluid body_content">
            <div class="row page-titles">
                <div class="col-md-5 align-self-center">
                    <h4 class="text-themecolor"><?=$title?></h4>
                </div>
                <div class="col-md-7 align-self-center text-end">
                    <div class="d-flex justify-content-end align-items-center">
                        <ol class="breadcrumb justify-content-end">
                            <li class="breadcrumb-item active"><a href="cash_report.php">Reports</a></li>
                            <li class="breadcrumb-item active"><?=$title?></a></li>
                        </ol>
                    </div>
                </div>
            </div>

            <?php
            if ($type === 'export') {
                echo "<h3>Data export to Arthur Murray API Successfully</h3>";
                /*$data = json_decode($post_data);
                if (isset($data->error)) {
                    echo '<div class="alert alert-danger alert-dismissible" role="alert">'.$data->error_description.'</div>';
                } elseif (isset($data->errors)) {
                    if (isset($data->errors->errors[0])) {
                        echo '<div class="alert alert-danger alert-dismissible" role="alert">' . $data->errors->errors[0] . '</div>';
                    } else {
                        echo '<div class="alert alert-danger alert-dismissible" role="alert">'.$data->message.'</div>';
                    }
                } else {
                    echo "<h3>Data export to Arthur Murray API Successfully</h3>";
                }*/
            } else { ?>
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row" style="margin-bottom: 20px;">
                                    <div class="col-md-2 text-left">
                                        <img src="../assets/images/background/doable_logo.png" style="margin-bottom:-35px; height: 60px; width: auto;">
                                    </div>
                                    <div class="col-md-2 text-center">
                                        <h3 class="card-title" style="padding-bottom:15px; text-align: center; font-weight: bold"><?=$title?></h3>
                                    </div>
                                    <div class="col-md-5 text-center">
                                        <h5 class="card-title" style="padding-bottom:15px; text-align: center; font-weight: bold"><?=$business_name." (".$concatenatedResults.")"?></h5>
                                    </div>
                                    <div class="col-md-3 text-center">
                                        <h6 class="card-title" style="padding-bottom:15px; text-align: center; font-weight: bold">(<?=date('m/d/Y', strtotime($from_date))?> - <?=date('m/d/Y', strtotime($to_date))?>)</h6>
                                    </div>
                                </div>

                                <?php
                                $each_service_provider = $db_account->Execute("SELECT distinct DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID, DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_PERCENTAGE, DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, PAYMENT_DATE, AMOUNT, PAYMENT_INFO, PAYMENT_TYPE, RECEIPT_NUMBER, MEMO, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS CLIENT, ENROLLMENT_NAME, ENROLLMENT_ID, ENROLLMENT_TYPE, TOTAL_AMOUNT, ENROLLMENT_BY_ID FROM DOA_ENROLLMENT_PAYMENT INNER JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER INNER JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER INNER JOIN $master_database.DOA_PAYMENT_TYPE AS DOA_PAYMENT_TYPE ON DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE=DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE INNER JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER=DOA_USER_MASTER.PK_USER_MASTER INNER JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USER_MASTER.PK_USER=DOA_USERS.PK_USER INNER JOIN $master_database.DOA_ENROLLMENT_TYPE AS DOA_ENROLLMENT_TYPE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE=DOA_ENROLLMENT_TYPE.PK_ENROLLMENT_TYPE INNER JOIN DOA_ENROLLMENT_BILLING ON DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") ".$payment_date);
                                while (!$each_service_provider->EOF) {
                                    $name = $db->Execute("SELECT CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS TEACHER FROM DOA_USERS WHERE DOA_USERS.PK_USER = ".$each_service_provider->fields['SERVICE_PROVIDER_ID']);
                                    $service_provider_id_per_table = $each_service_provider->fields['SERVICE_PROVIDER_ID'];
                                    $total_portion = 0; // Initialize sum variable here
                                    ?>

                                <div class="table-responsive">
                                    <table id="myTable" class="table table-bordered" data-page-length='50'>
                                        <thead>
                                            <tr>
                                                
                                                <th style="width:50%; text-align: center; vertical-align:auto; font-weight: bold" colspan="11"><?= $name->fields['TEACHER'] ?></th>
                                                
                                            </tr>
                                            <tr>
                                                <th style="width:8%; text-align: center">Receipt #</th>
                                                <th style="width:8%; text-align: center" >Payment Date</th>
                                                <th style="width:8%; text-align: center" >Amount</th>
                                                <th style="width:10%; text-align: center" >Student Name</th>
                                                <th style="width:8%; text-align: center" >Type</th>
                                                <th style="width:5%; text-align: center" >ENR ID</th>
                                                <th style="width:10%; text-align: center" >Enrollment</th>
                                                <th style="width:10%; text-align: center" >Units/Total Cost</th>
                                                <th style="width:8%; text-align: center" >Portion</th>
                                                <th style="width:5%; text-align: center" >%</th>
                                                <th style="width:8%; text-align: center" >Comment/Remark</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php
                                        
                                        $row = $db_account->Execute("SELECT DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID, DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_PERCENTAGE, DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, PAYMENT_DATE, AMOUNT, PAYMENT_INFO, PAYMENT_TYPE, RECEIPT_NUMBER, MEMO, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS CLIENT, ENROLLMENT_NAME, ENROLLMENT_ID, ENROLLMENT_TYPE, TOTAL_AMOUNT, ENROLLMENT_BY_ID FROM DOA_ENROLLMENT_PAYMENT INNER JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER INNER JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER INNER JOIN $master_database.DOA_PAYMENT_TYPE AS DOA_PAYMENT_TYPE ON DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE=DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE INNER JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER=DOA_USER_MASTER.PK_USER_MASTER INNER JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USER_MASTER.PK_USER=DOA_USERS.PK_USER INNER JOIN $master_database.DOA_ENROLLMENT_TYPE AS DOA_ENROLLMENT_TYPE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE=DOA_ENROLLMENT_TYPE.PK_ENROLLMENT_TYPE INNER JOIN DOA_ENROLLMENT_BILLING ON DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (".$service_provider_id_per_table.") AND DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."' ORDER BY DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE DESC");
                                        while (!$row->EOF) {
                                            $sessions = $db_account->Execute("SELECT NUMBER_OF_SESSION FROM DOA_ENROLLMENT_SERVICE WHERE PK_ENROLLMENT_MASTER = ".$row->fields['PK_ENROLLMENT_MASTER']);
                                            $units = $sessions->fields['NUMBER_OF_SESSION'] ?? 0;
                                            $service_provider = $db->Execute("SELECT CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS TEACHER FROM DOA_USERS WHERE DOA_USERS.PK_USER = ".$row->fields['SERVICE_PROVIDER_ID']);
                                            $portion = $row->fields['AMOUNT'] * ($row->fields['SERVICE_PROVIDER_PERCENTAGE'] / 100);
                                            $total_portion += $portion; // Add to the sum
                                            ?>
                                            <tr>
                                                <td style="text-align: center"><?=$row->fields['RECEIPT_NUMBER']?></td>
                                                <td style="text-align: center"><?=date('m-d-Y', strtotime($row->fields['PAYMENT_DATE']))?></td>
                                                <td style="text-align: center">$<?=$row->fields['AMOUNT']?></td>
                                                <td style="text-align: center"><?=$row->fields['CLIENT']?></td>
                                                <td style="text-align: center"><?=$row->fields['PAYMENT_TYPE']?></td>
                                                <td style="text-align: center"><?=$row->fields['ENROLLMENT_ID']?></td>
                                                <td style="text-align: center"><?=$row->fields['ENROLLMENT_NAME']?></td>
                                                <td style="text-align: center"><?=$units.'/$'.$row->fields['TOTAL_AMOUNT']?></td>
                                                <td style="text-align: center">$<?= number_format($row->fields['AMOUNT'] * ($row->fields['SERVICE_PROVIDER_PERCENTAGE'] / 100), 2) ?></td>
                                                <td style="text-align: center"><?=number_format($row->fields['SERVICE_PROVIDER_PERCENTAGE'], 0)?></td>
                                                <td style="text-align: center"></td>
                                            </tr>
                                            <?php $row->MoveNext();
                                            } ?>
                                            <tr>
                                                <th style="text-align: center; vertical-align:auto; font-weight: bold" colspan="8"></th>
                                                <th style="text-align: center; vertical-align:auto; font-weight: bold" colspan="1">$<?= number_format($total_portion, 2) ?></th>
                                                <th style="text-align: center; vertical-align:auto; font-weight: bold" colspan="2"></th>                                                
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <?php
                                    $each_service_provider->MoveNext();
                                } ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
</div>
<?php require_once('../includes/footer.php');?>
</body>
</html>
