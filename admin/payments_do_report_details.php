<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;

$title = "PAYMENTS DO. REPORT";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5]) ){
    header("location:../login.php");
    exit;
}

$type = $_GET['type'];

$selected_date = date('Y-m-d', strtotime($_GET['selected_date']));
$due_date = "AND DOA_ENROLLMENT_LEDGER.DUE_DATE <= '".date('Y-m-d', strtotime($selected_date))."' ORDER BY DOA_ENROLLMENT_LEDGER.DUE_DATE DESC";

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
                                                <th style="width:50%; text-align: center; vertical-align:auto; font-weight: bold" colspan="3"><?=($account_data->fields['FRANCHISE']==1)?'Franchisee: ':''?><?=$business_name." (".$concatenatedResults.")"?></th>
                                                <th style="width:50%; text-align: center; font-weight: bold" colspan="2">Previous Pending Payments on or before <?=date('m/d/Y', strtotime($selected_date))?></th>
                                            </tr>
                                            <tr>
                                                <th style="width:10%; text-align: center" >Customer Name</th>
                                                <th style="width:10%; text-align: center" >Enrollment Name</th>
                                                <th style="width:10%; text-align: center">Due Date</th>
                                                <th style="width:10%; text-align: center" >Pending Payments</th>
                                                <th style="width:10%; text-align: center" >Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php
                                        $i=1;
                                        $row = $db_account->Execute("SELECT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, DOA_ENROLLMENT_MASTER.PK_USER_MASTER, ENROLLMENT_NAME, DUE_DATE, AMOUNT, PAYMENT_INFO, PAYMENT_TYPE, RECEIPT_NUMBER, MEMO, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS CLIENT, ENROLLMENT_NAME FROM DOA_ENROLLMENT_PAYMENT INNER JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER INNER JOIN $master_database.DOA_PAYMENT_TYPE AS DOA_PAYMENT_TYPE ON DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE=DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE INNER JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER=DOA_USER_MASTER.PK_USER_MASTER INNER JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USER_MASTER.PK_USER=DOA_USERS.PK_USER INNER JOIN DOA_ENROLLMENT_LEDGER ON DOA_ENROLLMENT_LEDGER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_LEDGER.IS_PAID = 0 AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") ".$due_date);
                                        while (!$row->EOF) {
                                            $customer = $db->Execute("SELECT DOA_USERS.PK_USER, DOA_USER_MASTER.PK_USER_MASTER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS CUSTOMER_NAME FROM DOA_USERS LEFT JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER WHERE PK_USER_MASTER = ".$row->fields['PK_USER_MASTER']);
                                            $selected_user_id = $customer->fields['PK_USER'];
                                            $selected_customer_id = $customer->fields['PK_USER_MASTER'];
                                            ?>
                                            <tr>
                                                <td style="text-align: left"><a href="customer.php?id=<?= $selected_user_id ?>&master_id=<?= $selected_customer_id ?>&tab=enrollment" target="_blank" style="color: blue; font-weight: bold"><?= $customer->fields['CUSTOMER_NAME'] ?></a></td>
                                                <td style="text-align: center"><?=$row->fields['ENROLLMENT_NAME']?></td>
                                                <td style="text-align: center"><?=date('m-d-Y', strtotime($row->fields['DUE_DATE']))?></td>
                                                <td style="text-align: right">$<?=$row->fields['AMOUNT']?></td>                                               
                                                <td style="text-align: right"></td> 
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
            <?php } ?>
        </div>
    </div>
</div>
<?php require_once('../includes/footer.php');?>
</body>
</html>
