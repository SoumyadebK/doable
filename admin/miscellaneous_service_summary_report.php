<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;

$title = "MISCELLANEOUS SERVICE - SUMMARY REPORT";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5]) ){
    header("location:../login.php");
    exit;
}

$type = $_GET['type'];

$PK_PACKAGE = $_GET['PK_PACKAGE'];
$TRANSPORTATION_CHARGES = $_GET['TRANSPORTATION_CHARGES'];
$PACKAGE_COSTS = $_GET['PACKAGE_COSTS'];

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

if ($type === 'export') {
    $access_token = getAccessToken();
    $authorization = "Authorization: Bearer ".$access_token;

    $line_item = [];

    $row = $db_account->Execute("SELECT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, TOTAL_AMOUNT, BALANCE_PAYABLE, PAYMENT_DATE, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME_OF_PARTICIPANT, DOA_ENROLLMENT_MASTER.PK_USER_MASTER, RECEIPT_NUMBER, AMOUNT, ENROLLMENT_DATE, EXPIRY_DATE FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_BILLING ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_PAYMENT ON DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER=DOA_USER_MASTER.PK_USER_MASTER LEFT JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USER_MASTER.PK_USER=DOA_USERS.PK_USER WHERE DOA_ENROLLMENT_MASTER.PK_PACKAGE = ".$PK_PACKAGE. " AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") ORDER BY PAYMENT_DATE ");
    $package = $db_account->Execute("SELECT PACKAGE_NAME FROM DOA_PACKAGE WHERE PK_PACKAGE = ".$PK_PACKAGE);

        $total =0;
        $unique_id = [];
        while (!$row->EOF) {
            $service_provider = $db->Execute("SELECT CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS TEACHER FROM $account_database.DOA_ENROLLMENT_MASTER AS DOA_ENROLLMENT_MASTER LEFT JOIN $account_database.DOA_ENROLLMENT_SERVICE_PROVIDER AS DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_USERS ON DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID=DOA_USERS.PK_USER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = ".$row->fields['PK_ENROLLMENT_MASTER']);
            $partner = $db_account->Execute("SELECT CONCAT(DOA_CUSTOMER_DETAILS.PARTNER_FIRST_NAME, ' ', DOA_CUSTOMER_DETAILS.PARTNER_LAST_NAME) AS PARTNER_NAME, ATTENDING_WITH FROM DOA_CUSTOMER_DETAILS WHERE PK_USER_MASTER = ".$row->fields['PK_USER_MASTER']);
            if(($partner->fields['ATTENDING_WITH']) == 'With a Partner') {
                $NAME = $row->fields['NAME_OF_PARTICIPANT'].' & '.$partner->fields['PARTNER_NAME'];
            } else {
                $NAME = $row->fields['NAME_OF_PARTICIPANT'];
            }
            $date = $row->fields['PAYMENT_DATE']; // Example date
            $weekNumber = date("W", strtotime($date));
            $weekYear = date("Y", strtotime($date));
            if(!in_array($row->fields['PK_ENROLLMENT_MASTER'], $unique_id)){
                $total += $row->fields['TOTAL_AMOUNT'];
                $unique_id[] = $row->fields['PK_ENROLLMENT_MASTER'];
            }

        $line_item[] = array(
            "receipt_number" => $row->fields['RECEIPT_NUMBER'],
            "date" => date('m-d-Y', strtotime($row->fields['PAYMENT_DATE'])),
            "participant_full_name" => $NAME,
            "teachers" => $service_provider->fields['TEACHER'],
            "total_charges_due" => $row->fields['TOTAL_AMOUNT'],
            "payment_amount" => number_format($row->fields['AMOUNT'], 2),
            "reported_week_number" => $weekNumber,
            "reported_week_year" => $weekYear,
        );

        $row->MoveNext();
    }

    $data = [
        'type' => 'miscellaneous',
        'prepared_by' => $_SESSION['PK_USER'],
        'event' => $package->fields['PACKAGE_NAME'],
        'location' => $concatenatedResults,
        'date_started' => date('m-d-Y', strtotime($row->fields['ENROLLMENT_DATE'])),
        'date_ended' =>date('m-d-Y', strtotime($row->fields['EXPIRY_DATE'])),
        'transportation_costs' => $TRANSPORTATION_CHARGES,
        'package_costs' => $PACKAGE_COSTS,
        'line_items' => $line_item,
    ];

    $url = constant('ami_api_url').'/api/v1/reports';
    $post_data = callArturMurrayApi($url, $data, $authorization);

    //pre_r(json_decode($post_data));
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
                            <li class="breadcrumb-item active"><a href="reports.php">Reports</a></li>
                            <li class="breadcrumb-item active"><a href="customer_summary_report.php"><?=$title?></a></li>
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
                                <div>
                                    <img src="../assets/images/background/doable_logo.png" style="margin-bottom:-35px; height: 60px; width: auto;">
                                    <h3 class="card-title" style="padding-bottom:15px; text-align: center; font-weight: bold"><?=$title?></h3>
                                </div>

                                <div class="table-responsive">
                                    <table id="myTable" class="table table-bordered" data-page-length='50'>
                                        <thead>
                                        <tr>
                                            <th style="width:50%; text-align: center; vertical-align:auto; font-weight: bold" colspan="8"><?=($account_data->fields['FRANCHISE']==1)?'Franchisee: ':''?><?=$business_name." (".$concatenatedResults.")"?></th>
                                        </tr>
                                        <tr>
                                            <th style="width:10%; text-align: center">Receipt No.</th>
                                            <th style="width:10%; text-align: center" >Date</th>
                                            <th style="width:20%; text-align: center" >Name of Participant</th>
                                            <th style="width:20%; text-align: center" >Teacher(s)</th>
                                            <th style="width:10%; text-align: center" >Unique ID</th>
                                            <th style="width:10%; text-align: center" >Total Charges Due</th>
                                            <th style="width:10%; text-align: center" >Amount of Payment</th>
                                            <th style="width:10%; text-align: center" >Reported on Week</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php
                                        $i=1;
                                        $row = $db_account->Execute("SELECT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, TOTAL_AMOUNT, BALANCE_PAYABLE, PAYMENT_DATE, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME_OF_PARTICIPANT, DOA_ENROLLMENT_MASTER.PK_USER_MASTER, RECEIPT_NUMBER, AMOUNT FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_BILLING ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_PAYMENT ON DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER=DOA_USER_MASTER.PK_USER_MASTER LEFT JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USER_MASTER.PK_USER=DOA_USERS.PK_USER WHERE DOA_ENROLLMENT_MASTER.PK_PACKAGE = ".$PK_PACKAGE. " AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") ORDER BY PAYMENT_DATE ");
                                        $total =0;
                                        $unique_id = [];
                                        while (!$row->EOF) {
                                            $service_provider = $db->Execute("SELECT CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS TEACHER FROM $account_database.DOA_ENROLLMENT_MASTER AS DOA_ENROLLMENT_MASTER LEFT JOIN $account_database.DOA_ENROLLMENT_SERVICE_PROVIDER AS DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_USERS ON DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID=DOA_USERS.PK_USER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = ".$row->fields['PK_ENROLLMENT_MASTER']);
                                            $partner = $db_account->Execute("SELECT CONCAT(DOA_CUSTOMER_DETAILS.PARTNER_FIRST_NAME, ' ', DOA_CUSTOMER_DETAILS.PARTNER_LAST_NAME) AS PARTNER_NAME, ATTENDING_WITH FROM DOA_CUSTOMER_DETAILS WHERE PK_USER_MASTER = ".$row->fields['PK_USER_MASTER']);
                                            if(($partner->fields['ATTENDING_WITH']) == 'With a Partner') {
                                                $NAME = $row->fields['NAME_OF_PARTICIPANT'].' & '.$partner->fields['PARTNER_NAME'];
                                            } else {
                                                $NAME = $row->fields['NAME_OF_PARTICIPANT'];
                                            }
                                            $date = $row->fields['PAYMENT_DATE']; // Example date
                                            $weekNumber = date("W", strtotime($date));
                                            if(!in_array($row->fields['PK_ENROLLMENT_MASTER'], $unique_id)){
                                                $total += $row->fields['TOTAL_AMOUNT'];
                                                $unique_id[] = $row->fields['PK_ENROLLMENT_MASTER'];
                                            }
                                            ?>
                                            <tr>
                                                <td style="text-align: center"><?=$row->fields['RECEIPT_NUMBER']?></td>
                                                <td style="text-align: center"><?=date('m-d-Y', strtotime($row->fields['PAYMENT_DATE']))?></td>
                                                <td style="text-align: center"><?=$NAME?></td>
                                                <td style="text-align: center"><?=$service_provider->fields['TEACHER']?></td>
                                                <td style="text-align: center"><?=$row->fields['PK_ENROLLMENT_MASTER']?></td>
                                                <td style="text-align: center">$<?=$row->fields['TOTAL_AMOUNT']?></td>
                                                <td style="text-align: center">$<?=number_format($row->fields['AMOUNT'], 2)?></td>
                                                <td style="text-align: center">#<?=$weekNumber?></td>
                                            </tr>
                                            <?php $row->MoveNext();
                                            $i++; } ?>
                                        </tbody>
                                        <?php
                                        $row = $db_account->Execute("SELECT SUM(AMOUNT) AS TOTAL_PAID_AMOUNT FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_BILLING ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_PAYMENT ON DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER=DOA_USER_MASTER.PK_USER_MASTER LEFT JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USER_MASTER.PK_USER=DOA_USERS.PK_USER WHERE DOA_ENROLLMENT_MASTER.PK_PACKAGE = ".$PK_PACKAGE. " AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].")");
                                        ?>
                                        <tr>
                                            <th style="width:10%; text-align: center"></th>
                                            <th style="width:10%; text-align: center" ></th>
                                            <th style="width:20%; text-align: center" ></th>
                                            <th style="width:20%; text-align: center" ></th>
                                            <th style="width:10%; text-align: center" >Totals :</th>
                                            <th style="width:10%; text-align: center" >$<?=number_format($total, 2)?></th>
                                            <th style="width:10%; text-align: center" >$<?=number_format($row->fields['TOTAL_PAID_AMOUNT'], 2)?></th>
                                            <th style="width:10%; text-align: center" ></th>
                                        </tr>
                                    </table>
                                    <table id="myTable" class="table table-bordered" data-page-length='50'>
                                        <thead>
                                        <tr>
                                            <th style="width:10%; text-align: center">Total Enrollment</th>
                                            <th style="width:10%; text-align: center" >Transportation Charges</th>
                                            <th style="width:10%; text-align: center" >Package Costs</th>
                                            <th style="width:12%; text-align: center" >Total Deduction</th>
                                            <th style="width:10%; text-align: center" >Total Subject to Royalty</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php
                                        $row = $db_account->Execute("SELECT SUM(TOTAL_AMOUNT) AS TOTAL FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_BILLING ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_PACKAGE = ".$PK_PACKAGE. " AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") ");
                                        $TOTAL_DEDUCTION = $PACKAGE_COSTS + $TRANSPORTATION_CHARGES;
                                        $TOTAL_SUBJECT_TO_ROYALTY = $total - $TOTAL_DEDUCTION;
                                            ?>
                                            <tr>
                                                <td style="text-align: center">$<?=number_format($total, 2)?></td>
                                                <td style="text-align: center">$<?=number_format($TRANSPORTATION_CHARGES, 2)?></td>
                                                <td style="text-align: center">$<?=number_format($PACKAGE_COSTS, 2)?></td>
                                                <td style="text-align: center">$<?=number_format($TOTAL_DEDUCTION, 2)?></td>
                                                <td style="text-align: center">$<?=number_format($TOTAL_SUBJECT_TO_ROYALTY, 2)?></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
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
