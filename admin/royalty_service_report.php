<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];

$title = "WEEKLY ROYALTY / SERVICE REPORT";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 2){
    header("location:../login.php");
    exit;
}

$type = $_GET['type'];

$week_number = $_GET['week_number'];
$YEAR = date('Y');
$dto = new DateTime();
$dto->setISODate($YEAR, $week_number+1);
$from_date = $dto->modify('-1 day')->format('Y-m-d');
$dto->modify('+6 days');
$to_date = $dto->format('Y-m-d');


$PAYMENT_QUERY = "SELECT
                        DOA_ENROLLMENT_PAYMENT.AMOUNT,
                        DOA_ENROLLMENT_PAYMENT.RECEIPT_NUMBER,
                        DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE,
                        DOA_PAYMENT_TYPE.PAYMENT_TYPE,
                        CONCAT(CUSTOMER.FIRST_NAME, ' ' ,CUSTOMER.LAST_NAME) AS STUDENT_NAME,
                        CLOSER.FIRST_NAME AS CLOSER_FIRST_NAME,
                        CLOSER.LAST_NAME AS CLOSER_LAST_NAME,
                        DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER,
                        DOA_ENROLLMENT_MASTER.CUSTOMER_ENROLLMENT_NUMBER,
                        DOA_ENROLLMENT_MASTER.PK_LOCATION
                    FROM
                        DOA_ENROLLMENT_PAYMENT
                    LEFT JOIN $master_database.DOA_PAYMENT_TYPE AS DOA_PAYMENT_TYPE ON DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE = DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE
                            
                    LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER
                    LEFT JOIN $master_database.DOA_USERS AS CLOSER ON DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID = CLOSER.PK_USER
                    
                    LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER
                    LEFT JOIN $master_database.DOA_USERS AS CUSTOMER ON CUSTOMER.PK_USER = DOA_USER_MASTER.PK_USER
                    
                    WHERE CUSTOMER.IS_DELETED = 0 AND DOA_ENROLLMENT_PAYMENT.TYPE = 'Payment' AND DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE != 7 AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$DEFAULT_LOCATION_ID.")
                    AND DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."'
                    ORDER BY PAYMENT_DATE ASC, RECEIPT_NUMBER ASC";

$REFUND_QUERY = "SELECT
                        DOA_ENROLLMENT_PAYMENT.AMOUNT,
                        DOA_ENROLLMENT_PAYMENT.RECEIPT_NUMBER,
                        DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE,
                        DOA_PAYMENT_TYPE.PAYMENT_TYPE,
                        CONCAT(CUSTOMER.FIRST_NAME, ' ' ,CUSTOMER.LAST_NAME) AS STUDENT_NAME,
                        CLOSER.FIRST_NAME AS CLOSER_FIRST_NAME,
                        CLOSER.LAST_NAME AS CLOSER_LAST_NAME,
                        DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER,
                        DOA_ENROLLMENT_MASTER.CUSTOMER_ENROLLMENT_NUMBER,
                        DOA_ENROLLMENT_MASTER.PK_LOCATION
                    FROM
                        DOA_ENROLLMENT_PAYMENT
                    LEFT JOIN $master_database.DOA_PAYMENT_TYPE AS DOA_PAYMENT_TYPE ON DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE = DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE
                            
                    LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER
                    LEFT JOIN $master_database.DOA_USERS AS CLOSER ON DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID = CLOSER.PK_USER
                    
                    LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER
                    LEFT JOIN $master_database.DOA_USERS AS CUSTOMER ON CUSTOMER.PK_USER = DOA_USER_MASTER.PK_USER
                    
                    WHERE CUSTOMER.IS_DELETED = 0 AND DOA_ENROLLMENT_PAYMENT.TYPE = 'Refund' AND DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE != 7 AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$DEFAULT_LOCATION_ID.")
                    AND DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."'
                    ORDER BY PAYMENT_DATE ASC, RECEIPT_NUMBER ASC";

$account_data = $db->Execute("SELECT * FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
$user_data = $db->Execute("SELECT * FROM DOA_USERS WHERE PK_USER = '$_SESSION[PK_USER]'");
$business_name = $account_data->RecordCount() > 0 ? $account_data->fields['BUSINESS_NAME'] : '';

if ($type === 'export') {
    $access_token = getAccessToken();
    $authorization = "Authorization: Bearer ".$access_token;
    $line_item = [];

    $payment_data = $db_account->Execute($PAYMENT_QUERY);
    while (!$payment_data->EOF) {
        $TOTAL_UNIT = 0;
        $REGULAR_AMOUNT = 0;
        $SUNDRY_AMOUNT = 0;
        $MISC_AMOUNT = 0;

        $teacher_data = $db_account->Execute("SELECT TEACHER.FIRST_NAME, TEACHER.LAST_NAME FROM DOA_ENROLLMENT_SERVICE_PROVIDER LEFT JOIN $master_database.DOA_USERS AS TEACHER ON DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID = TEACHER.PK_USER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = ".$payment_data->fields['PK_ENROLLMENT_MASTER']);

        $enrollment_service_data = $db_account->Execute("SELECT SUM(`FINAL_AMOUNT`) AS TOTAL_AMOUNT, DOA_SERVICE_MASTER.PK_SERVICE_CLASS FROM `DOA_ENROLLMENT_SERVICE` LEFT JOIN DOA_SERVICE_MASTER ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER WHERE DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = ".$payment_data->fields['PK_ENROLLMENT_MASTER']." GROUP BY PK_ENROLLMENT_MASTER");
        $TOTAL_AMOUNT = $enrollment_service_data->fields['TOTAL_AMOUNT'];
        $SERVICE_CLASS = $enrollment_service_data->fields['PK_SERVICE_CLASS'];

        $AMOUNT_PAID = $payment_data->fields['AMOUNT'];

        if ($SERVICE_CLASS == 5) {
            $MISC_AMOUNT = $AMOUNT_PAID;
        } else {
            $REGULAR_AMOUNT = $AMOUNT_PAID;
        }

        $enrollment_service_code_data = $db_account->Execute("SELECT DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION, DOA_ENROLLMENT_SERVICE.FINAL_AMOUNT, DOA_SERVICE_CODE.IS_SUNDRY, DOA_SERVICE_CODE.IS_GROUP FROM DOA_ENROLLMENT_SERVICE LEFT JOIN DOA_SERVICE_CODE ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE WHERE DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = ".$payment_data->fields['PK_ENROLLMENT_MASTER']);
        while (!$enrollment_service_code_data->EOF) {
            if ($enrollment_service_code_data->fields['IS_GROUP'] == 0) {
                $TOTAL_UNIT += $enrollment_service_code_data->fields['NUMBER_OF_SESSION'];
            }
            if ($SERVICE_CLASS == 5 && $enrollment_service_code_data->fields['IS_SUNDRY'] == 1) {
                $servicePercent = ($enrollment_service_code_data->fields['FINAL_AMOUNT']*100)/$TOTAL_AMOUNT;
                $serviceAmount = ($AMOUNT_PAID*$servicePercent)/100;
                $SUNDRY_AMOUNT += $serviceAmount;
            }
            $enrollment_service_code_data->MoveNext();
        }

        if ($SUNDRY_AMOUNT > 0) {
            $MISC_AMOUNT = $AMOUNT_PAID - $SUNDRY_AMOUNT;
        }

        switch ($payment_data->fields['CUSTOMER_ENROLLMENT_NUMBER']) {
            case 1:
                $sale_code = 'PORI';
                break;
            case 2:
                $sale_code = 'ORI';
                break;
            case 3:
                $sale_code = 'EXT';
                break;

            default:
                $sale_code = 'REN';
                break;
        }

        $executive = getStaffCode($authorization, $payment_data->fields['CLOSER_FIRST_NAME'], $payment_data->fields['CLOSER_LAST_NAME']);
        $staff_members = [];
        while(!$teacher_data->EOF) {
            $staff_members[] =  getStaffCode($authorization, $teacher_data->fields['FIRST_NAME'], $teacher_data->fields['LAST_NAME']);
            $teacher_data->MoveNext();
        }

        $line_item[] = array(
            "receipt_number" => $payment_data->fields['RECEIPT_NUMBER'],
            "date_paid" => date('Y-m-d', strtotime($payment_data->fields['PAYMENT_DATE'])),
            "students_full_name" => $payment_data->fields['STUDENT_NAME'],
            "executive" => $executive,
            "staff_members" => $staff_members,
            "sale_code" => $sale_code,
            "number_of_units" => $TOTAL_UNIT,
            "sale_value" => $TOTAL_AMOUNT,
            "cash" => $AMOUNT_PAID,
            "miscellaneous_services" => $MISC_AMOUNT,
            "sundry" => $SUNDRY_AMOUNT,
        );
        $payment_data->MoveNext();
    }

    $refunds = [];
    $refund_data = $db_account->Execute($REFUND_QUERY);
    while (!$refund_data->EOF) {
        $AMOUNT_REFUND = $refund_data->fields['AMOUNT'];

        $refunds[] = array(
            "refund_type" => 'regular',
            "date_reported" => date('Y-m-d', strtotime($refund_data->fields['PAYMENT_DATE'])),
            "date_refunded" => date('Y-m-d', strtotime($refund_data->fields['PAYMENT_DATE'])),
            "student_name" => $refund_data->fields['STUDENT_NAME'],
            "amount" => $AMOUNT_REFUND,
        );
        $refund_data->MoveNext();
    }

    $data = [
        'type' => 'royalty',
        'prepared_by' => $user_data->fields['FIRST_NAME'].' '.$user_data->fields['LAST_NAME'],
        'week_number' => $week_number,
        'week_year' => $YEAR,
        'line_items' => $line_item,
        'refunds' => $refunds
    ];

    $url = constant('ami_api_url').'/api/v1/reports';
    $post_data = callArturMurrayApi($url, $data, $authorization);

    //pre_r(json_decode($post_data));
}

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
                $data = json_decode($post_data);
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
                }
            } else { ?>
            <div class="row">
                <div class="col-12">
                    <div class="card">

                        <div class="card-body">
                            <div class="row">
                                <div class="col-2" style="padding-bottom: 20px;">
                                    <img src="../assets/images/background/doable_logo.png" style="height: 60px; width: auto;">
                                </div>

                                <div class="col-8" style="padding-top: 10px;">
                                    <h3 class="card-title" style="padding-bottom:15px; text-align: center; font-weight: bold"><?=$title?></h3>
                                </div>
                                <div class="col-2" style="padding-bottom: 20px;">
                                    <img src="../assets/images/background/doable_logo.png" style="float: right; height: 60px; width: auto;">
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table id="myTable" class="table table-bordered" data-page-length='50'>
                                    <thead>
                                    <tr>
                                        <th style="width:20%; text-align: center; vertical-align:auto; font-weight: bold" colspan="6">Franchisee: <?=$business_name." (".$concatenatedResults.")"?></th>
                                        <th style="width:20%; text-align: center; font-weight: bold" colspan="2">Part 1</th>
                                        <th style="width:20%; text-align: center; font-weight: bold" colspan="5">Week # <?=$week_number?> (<?=date('m/d/Y', strtotime($from_date))?> - <?=date('m/d/Y', strtotime($to_date))?>)</th>
                                    </tr>
                                    <tr>
                                        <th style="width:10%; text-align: center; font-weight: bold" rowspan="2">Receipt number</th>
                                        <th style="width:10%; text-align: center; font-weight: bold" rowspan="2">Date</th>
                                        <th style="width:10%; text-align: center; font-weight: bold" rowspan="2">Student name</th>
                                        <th style="width:12%; text-align: center; font-weight: bold" rowspan="2">Type</th>
                                        <th style="width:10%; text-align: center; font-weight: bold" colspan="2">Staff code</th>
                                        <th style="width:10%; text-align: center; font-weight: bold" colspan="2"></th>
                                        <th style="width:10%; text-align: center; font-weight: bold" colspan="3">Studio Receipts</th>
                                        <th style="width:10%; text-align: center; font-weight: bold" rowspan="2">Total<br>
                                            subject<br>
                                            R/S fee</th>
                                        <th style="width:10%; text-align: center; font-weight: bold" rowspan="2">Studio<br>
                                            Grand<br>
                                            Total </th>

                                    </tr>
                                    <tr>
                                        <th style="width:10%; text-align: center; font-weight: bold">Closer</th>
                                        <th style="width:10%; text-align: center; font-weight: bold">Teachers</th>
                                        <th style="width:10%; text-align: center; font-weight: bold">First
                                            payment or
                                            A/C
                                        </th>
                                        <th style="width:10%; text-align: center; font-weight: bold">Units/Total Cost</th>
                                        <th style="width:10%; text-align: center; font-weight: bold">Regular</th>
                                        <th style="width:10%; text-align: center; font-weight: bold">Sundry</th>
                                        <th style="width:10%; text-align: center; font-weight: bold">Misc./NonUnit</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    $payment_data = $db_account->Execute($PAYMENT_QUERY);
                                    $REGULAR_TOTAL = 0;
                                    $SUNDRY_TOTAL = 0;
                                    $MISC_TOTAL = 0;
                                    $TOTAL_RS_FEE = 0;
                                    $TOTAL_AMOUNT_PAID = 0;
                                    $LOCATION_TOTAL = [];

                                    $TOTAL_AMOUNT_PAID_DAILY = 0;
                                    $REGULAR_TOTAL_DAILY = 0;
                                    $SUNDRY_TOTAL_DAILY = 0;
                                    $MISC_TOTAL_DAILY = 0;

                                    $total_record = $payment_data->RecordCount();
                                    $i = 0;
                                    while (!$payment_data->EOF) {
                                        if ($i == 0) {
                                            $last_date = $payment_data->fields['PAYMENT_DATE'];
                                        }
                                        $TOTAL_UNIT = 0;
                                        $REGULAR_AMOUNT = 0;
                                        $SUNDRY_AMOUNT = 0;
                                        $MISC_AMOUNT = 0;
                                        $teacher_data = $db_account->Execute("SELECT GROUP_CONCAT(DISTINCT(CONCAT(TEACHER.FIRST_NAME, ' ', TEACHER.LAST_NAME)) SEPARATOR ', ') AS TEACHER_NAME FROM DOA_ENROLLMENT_SERVICE_PROVIDER LEFT JOIN $master_database.DOA_USERS AS TEACHER ON DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID = TEACHER.PK_USER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = ".$payment_data->fields['PK_ENROLLMENT_MASTER']);
                                        $enrollment_service_data = $db_account->Execute("SELECT SUM(`FINAL_AMOUNT`) AS TOTAL_AMOUNT, DOA_SERVICE_MASTER.PK_SERVICE_CLASS FROM `DOA_ENROLLMENT_SERVICE` LEFT JOIN DOA_SERVICE_MASTER ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER WHERE DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = ".$payment_data->fields['PK_ENROLLMENT_MASTER']." GROUP BY PK_ENROLLMENT_MASTER");
                                        $TOTAL_AMOUNT = $enrollment_service_data->fields['TOTAL_AMOUNT'];
                                        $SERVICE_CLASS = $enrollment_service_data->fields['PK_SERVICE_CLASS'];

                                        $AMOUNT_PAID = $payment_data->fields['AMOUNT'];
                                        $TOTAL_AMOUNT_PAID += $AMOUNT_PAID;
                                        $TOTAL_AMOUNT_PAID_DAILY += $AMOUNT_PAID;

                                        if ($SERVICE_CLASS == 5) {
                                            $MISC_AMOUNT = $AMOUNT_PAID;
                                        } else {
                                            $REGULAR_AMOUNT = $AMOUNT_PAID;
                                        }

                                        $enrollment_service_code_data = $db_account->Execute("SELECT DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION, DOA_ENROLLMENT_SERVICE.PRICE_PER_SESSION, DOA_ENROLLMENT_SERVICE.FINAL_AMOUNT, DOA_SERVICE_CODE.IS_SUNDRY, DOA_SERVICE_CODE.IS_GROUP FROM DOA_ENROLLMENT_SERVICE LEFT JOIN DOA_SERVICE_CODE ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE WHERE DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = ".$payment_data->fields['PK_ENROLLMENT_MASTER']);
                                        while (!$enrollment_service_code_data->EOF) {
                                            if ($enrollment_service_code_data->fields['IS_GROUP'] == 0 && $enrollment_service_code_data->fields['PRICE_PER_SESSION'] > 0) {
                                                $TOTAL_UNIT += $enrollment_service_code_data->fields['NUMBER_OF_SESSION'];
                                            }
                                            if ($SERVICE_CLASS == 5 && $enrollment_service_code_data->fields['IS_SUNDRY'] == 1) {
                                                $servicePercent = ($enrollment_service_code_data->fields['FINAL_AMOUNT']*100)/$TOTAL_AMOUNT;
                                                $serviceAmount = ($AMOUNT_PAID*$servicePercent)/100;
                                                $SUNDRY_AMOUNT += $serviceAmount;
                                            }
                                            $enrollment_service_code_data->MoveNext();
                                        }

                                        if ($SUNDRY_AMOUNT > 0) {
                                            $MISC_AMOUNT = $AMOUNT_PAID - $SUNDRY_AMOUNT;
                                        }

                                        $REGULAR_TOTAL += $REGULAR_AMOUNT;
                                        $SUNDRY_TOTAL += $SUNDRY_AMOUNT;
                                        $MISC_TOTAL += $MISC_AMOUNT;

                                        $REGULAR_TOTAL_DAILY += $REGULAR_AMOUNT;
                                        $SUNDRY_TOTAL_DAILY += $SUNDRY_AMOUNT;
                                        $MISC_TOTAL_DAILY += $MISC_AMOUNT;

                                        $TOTAL_RS_FEE += $REGULAR_AMOUNT;
                                        if (isset($LOCATION_TOTAL[$payment_data->fields['PK_LOCATION']])) {
                                            $LOCATION_TOTAL[$payment_data->fields['PK_LOCATION']] = $LOCATION_TOTAL[$payment_data->fields['PK_LOCATION']] + $REGULAR_AMOUNT;
                                        } else {
                                            $LOCATION_TOTAL[$payment_data->fields['PK_LOCATION']] = $REGULAR_AMOUNT;
                                        } ?>
                                        <tr style="text-align: center;">
                                            <td><?=$payment_data->fields['RECEIPT_NUMBER']?></td>
                                            <td><?=date('m/d/Y', strtotime($payment_data->fields['PAYMENT_DATE']))?></td>
                                            <td><?=$payment_data->fields['STUDENT_NAME']?></td>
                                            <td><?=$payment_data->fields['PAYMENT_TYPE']?></td>
                                            <td><?=$payment_data->fields['CLOSER_FIRST_NAME']." ".$payment_data->fields['CLOSER_LAST_NAME']?></td>
                                            <td><?=$teacher_data->fields['TEACHER_NAME']?></td>
                                            <td>
                                                <?php
                                                if($SERVICE_CLASS == 5) {
                                                    echo $payment_data->fields['CUSTOMER_ENROLLMENT_NUMBER'] . '/MISC';
                                                } else {
                                                    switch ($payment_data->fields['CUSTOMER_ENROLLMENT_NUMBER']) {
                                                        case 1:
                                                            echo '1/PORI';
                                                            break;
                                                        case 2:
                                                            echo '2/ORI';
                                                            break;
                                                        case 3:
                                                            echo '3/EXT';
                                                            break;

                                                        default:
                                                            echo $payment_data->fields['CUSTOMER_ENROLLMENT_NUMBER'] . '/REN';
                                                            break;
                                                    }
                                                }
                                                ?>
                                            </td>
                                            <td><?=$TOTAL_UNIT.' / $'.$TOTAL_AMOUNT?></td>
                                            <td><?=$REGULAR_AMOUNT?></td>
                                            <td><?=$SUNDRY_AMOUNT?></td>
                                            <td><?=$MISC_AMOUNT?></td>
                                            <td><?='$'.$AMOUNT_PAID?></td>
                                            <td><?='$'.number_format($TOTAL_AMOUNT_PAID, 2)?></td>
                                        </tr>
                                        <?php
                                        $payment_data->MoveNext();
                                        $i++;
                                        if (($last_date != $payment_data->fields['PAYMENT_DATE']) || ($i == $total_record)) { ?>
                                            <tr>
                                                <th style="width:10%; text-align: center; font-weight: bold" colspan="7">Daily Totals</th>
                                                <th style="width:10%; text-align: center; font-weight: bold" colspan="1"><?='$'.number_format($TOTAL_AMOUNT_PAID_DAILY, 2)?></th>
                                                <th style="width:10%; text-align: center; font-weight: bold" colspan="1"><?='$'.number_format($REGULAR_TOTAL_DAILY, 2)?></th>
                                                <th style="width:10%; text-align: center; font-weight: bold" colspan="1"><?='$'.number_format($SUNDRY_TOTAL_DAILY, 2)?></th>
                                                <th style="width:10%; text-align: center; font-weight: bold" colspan="1"><?='$'.number_format($MISC_TOTAL_DAILY, 2)?></th>
                                                <th style="width:10%; text-align: center; font-weight: bold" colspan="1"><?='$'.number_format($TOTAL_RS_FEE, 2)?></th>
                                                <th style="width:10%; text-align: center; font-weight: bold" colspan="1"><?='$'.number_format($TOTAL_AMOUNT_PAID, 2)?></th>
                                            </tr>
                                            <?php
                                            if ($i < $total_record) { ?>
                                            <tr>
                                                <th style="width:20%; text-align: center; vertical-align:auto; font-weight: bold" colspan="6">Franchisee: <?=$business_name?></th>
                                                <th style="width:20%; text-align: center; font-weight: bold" colspan="2">Part 1</th>
                                                <th style="width:20%; text-align: center; font-weight: bold" colspan="5">Week # <?=$week_number?> (<?=date('m/d/Y', strtotime($from_date))?> - <?=date('m/d/Y', strtotime($to_date))?>)</th>
                                            </tr>
                                            <?php } ?>
                                        <?php
                                            $TOTAL_AMOUNT_PAID_DAILY = 0;
                                            $REGULAR_TOTAL_DAILY = 0;
                                            $SUNDRY_TOTAL_DAILY = 0;
                                            $MISC_TOTAL_DAILY = 0;
                                        }
                                        $last_date = $payment_data->fields['PAYMENT_DATE'];
                                    }
                                    ?>
                                        <!--<tr>
                                            <th style="width:10%; text-align: center; font-weight: bold" colspan="7">Daily Totals</th>
                                            <th style="width:10%; text-align: center; font-weight: bold" colspan="1"><?php /*='$'.number_format($TOTAL_AMOUNT_PAID, 2)*/?></th>
                                            <th style="width:10%; text-align: center; font-weight: bold" colspan="1"><?php /*='$'.number_format($REGULAR_TOTAL, 2)*/?></th>
                                            <th style="width:10%; text-align: center; font-weight: bold" colspan="1"><?php /*='$'.number_format($SUNDRY_TOTAL, 2)*/?></th>
                                            <th style="width:10%; text-align: center; font-weight: bold" colspan="1"><?php /*='$'.number_format($MISC_TOTAL, 2)*/?></th>
                                            <th style="width:10%; text-align: center; font-weight: bold" colspan="1"><?php /*='$'.number_format($TOTAL_RS_FEE, 2)*/?></th>
                                            <th style="width:10%; text-align: center; font-weight: bold" colspan="1"><?php /*='$'.number_format($TOTAL_AMOUNT_PAID, 2)*/?></th>
                                        </tr>-->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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
                                    <tbody>
                                        <tr>
                                            <th style="width:20%; text-align: center; vertical-align:auto; font-weight: bold" colspan="6">Franchisee: <?=$business_name?></th>
                                            <th style="width:20%; text-align: center; font-weight: bold" colspan="2">Part 2</th>
                                            <th style="width:20%; text-align: center; font-weight: bold" colspan="5">Week # <?=$week_number?> (<?=$from_date?> - <?=$to_date?>)</th>
                                        </tr>
                                        <tr>
                                            <th colspan="13">Refunds or credits below completed tuition refund report & photocopy of front & back of caceled cheks must be attached in order to receive credits.
                                                (Identify bank plan, rewrites & cancellation. Attach detail on authorized D-O-R transportation details.)</th>
                                        </tr>

                                        <tr>
                                            <th style="width:10%; text-align: center; font-weight: bold" rowspan="2">Receipt number</th>
                                            <th style="width:10%; text-align: center; font-weight: bold" rowspan="2">Date</th>
                                            <th style="width:10%; text-align: center; font-weight: bold" rowspan="2" colspan="2">Student name</th>
                                            <th style="width:20%; text-align: center; font-weight: bold" colspan="2">Staff code</th>
                                            <th style="width:20%; text-align: center; font-weight: bold" colspan="2"></th>
                                            <th style="width:20%; text-align: center; font-weight: bold" colspan="3">Studio Refunds Deductions</th>
                                        </tr>
                                        <tr>
                                            <th style="width:10%; text-align: center; font-weight: bold">Closer</th>
                                            <th style="width:10%; text-align: center; font-weight: bold">Teachers</th>
                                            <th style="width:10%; text-align: center; font-weight: bold">Type</th>
                                            <th style="width:10%; text-align: center; font-weight: bold">Units/Total Cost</th>
                                        </tr>
                                        <?php
                                        $TOTAL_AMOUNT_REFUND = 0;
                                        $refund_data = $db_account->Execute($REFUND_QUERY);
                                        while (!$refund_data->EOF) {
                                            $REFUND_TOTAL_UNIT = 0;
                                            $teacher_data = $db_account->Execute("SELECT GROUP_CONCAT(DISTINCT(CONCAT(TEACHER.FIRST_NAME, ' ', TEACHER.LAST_NAME)) SEPARATOR ', ') AS TEACHER_NAME FROM DOA_ENROLLMENT_SERVICE_PROVIDER LEFT JOIN $master_database.DOA_USERS AS TEACHER ON DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID = TEACHER.PK_USER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = ".$refund_data->fields['PK_ENROLLMENT_MASTER']);
                                            $enrollment_service_data = $db_account->Execute("SELECT SUM(`FINAL_AMOUNT`) AS TOTAL_AMOUNT, DOA_SERVICE_MASTER.PK_SERVICE_CLASS FROM `DOA_ENROLLMENT_SERVICE` LEFT JOIN DOA_SERVICE_MASTER ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER WHERE DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = ".$refund_data->fields['PK_ENROLLMENT_MASTER']." GROUP BY PK_ENROLLMENT_MASTER");
                                            $REFUND_TOTAL_AMOUNT = $enrollment_service_data->fields['TOTAL_AMOUNT'];

                                            $enrollment_service_code_data = $db_account->Execute("SELECT DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION, DOA_ENROLLMENT_SERVICE.PRICE_PER_SESSION, DOA_ENROLLMENT_SERVICE.FINAL_AMOUNT, DOA_SERVICE_CODE.IS_SUNDRY, DOA_SERVICE_CODE.IS_GROUP FROM DOA_ENROLLMENT_SERVICE LEFT JOIN DOA_SERVICE_CODE ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE WHERE DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = ".$refund_data->fields['PK_ENROLLMENT_MASTER']);
                                            while (!$enrollment_service_code_data->EOF) {
                                                if ($enrollment_service_code_data->fields['IS_GROUP'] == 0 && $enrollment_service_code_data->fields['PRICE_PER_SESSION'] > 0) {
                                                    $REFUND_TOTAL_UNIT += $enrollment_service_code_data->fields['NUMBER_OF_SESSION'];
                                                }
                                                $enrollment_service_code_data->MoveNext();
                                            }

                                            $AMOUNT_REFUND = $refund_data->fields['AMOUNT'];
                                            $TOTAL_AMOUNT_REFUND += $AMOUNT_REFUND; ?>
                                            <tr style="text-align: center;">
                                                <td><?=$refund_data->fields['RECEIPT_NUMBER']?></td>
                                                <td><?=date('m/d/Y', strtotime($refund_data->fields['PAYMENT_DATE']))?></td>
                                                <td colspan="2"><?=$refund_data->fields['STUDENT_NAME']?></td>
                                                <td><?=$refund_data->fields['CLOSER_FIRST_NAME']." ".$refund_data->fields['CLOSER_LAST_NAME']?></td>
                                                <td><?=$teacher_data->fields['TEACHER_NAME']?></td>
                                                <td><?=$refund_data->fields['PAYMENT_TYPE']?></td>
                                                <td><?=$REFUND_TOTAL_UNIT.' / $'.$REFUND_TOTAL_AMOUNT?></td>
                                                <td colspan="3"><?='-$'.number_format($AMOUNT_REFUND, 2)?></td>
                                            </tr>
                                            <?php
                                            $refund_data->MoveNext();
                                        } ?>

                                        <tr>
                                            <th style="width:70%; text-align: center; font-weight: bold" colspan="8">Refunds Total</th>
                                            <th style="width:30%; text-align: center; font-weight: bold" colspan="3"><?='-$'.number_format($TOTAL_AMOUNT_REFUND, 2)?></th>
                                        </tr>

                                        <tr>
                                            <th style="width:10%; text-align: center; font-weight: bold"></th>
                                            <th style="width:10%; text-align: center; font-weight: bold">Regular Cash +</th>
                                            <th style="width:10%; text-align: center; font-weight: bold">Sundry +</th>
                                            <th style="width:10%; text-align: center; font-weight: bold">Misc./NonUnit -</th>
                                            <th style="width:10%; text-align: center; font-weight: bold">Sundry deduct.</th>
                                            <th style="width:5%; text-align: center; font-weight: bold">=</th>
                                            <th style="width:10%; text-align: center; font-weight: bold">Total sub.rlty</th>
                                            <th style="width:10%; text-align: center; font-weight: bold" colspan="4">Sundry cash Studio total</th>
                                        </tr>
                                        <tr>
                                            <td style="width:10%; text-align: center; font-weight: bold;">Total receipts</td>
                                            <td style="width:10%; text-align: center;"><?='$'.number_format($REGULAR_TOTAL, 2)?></td>
                                            <td style="width:10%; text-align: center;"><?='$'.number_format($SUNDRY_TOTAL, 2)?></td>
                                            <td style="width:10%; text-align: center;"><?='$'.number_format($MISC_TOTAL, 2)?></td>
                                            <td style="width:10%; text-align: center;">$0.00</td>
                                            <td style="width:5%; text-align: center;">=</td>
                                            <td style="width:10%; text-align: center;"><?='$'.number_format($TOTAL_RS_FEE, 2)?></td>
                                            <td style="width:5%; text-align: center;">+</td>
                                            <td style="width:10%; text-align: center;"><?='$'.number_format($SUNDRY_TOTAL, 2)?></td>
                                            <td style="width:5%; text-align: center;">=</td>
                                            <td style="width:10%; text-align: center;"><?='$'.number_format($TOTAL_RS_FEE+$SUNDRY_TOTAL, 2)?></td>
                                        </tr>
                                        <tr>
                                            <td style="width:10%; text-align: center; font-weight: bold">Total refunds/credits</td>
                                            <td style="width:10%; text-align: center;"></td>
                                            <td style="width:10%; text-align: center;"></td>
                                            <td style="width:10%; text-align: center;"></td>
                                            <td style="width:10%; text-align: center;"></td>
                                            <td style="width:5%; text-align: center;">=</td>
                                            <td style="width:10%; text-align: center;"><?='-$'.number_format($TOTAL_AMOUNT_REFUND, 2)?></td>
                                            <td style="width:5%; text-align: center;"></td>
                                            <td style="width:10%; text-align: center;"></td>
                                            <td style="width:5%; text-align: center;">=</td>
                                            <td style="width:10%; text-align: center;"><?='-$'.number_format($TOTAL_AMOUNT_REFUND, 2)?></td>
                                        </tr>
                                        <tr>
                                            <td style="width:10%; text-align: center; font-weight: bold" colspan="6">Total subject to r/s fee
                                                <span style="font-weight: normal; float: right;">
                                                    <?php
                                                    $royalty_percent = '';
                                                    $location_total = '';
                                                    $royalty_percent_array = [];
                                                    foreach ($LOCATION_TOTAL AS $key => $value) {
                                                        $location_name = $db->Execute("SELECT LOCATION_NAME, ROYALTY_PERCENTAGE FROM `DOA_LOCATION` WHERE `PK_LOCATION` = ".$key);
                                                        echo $location_name->fields['LOCATION_NAME']." - "."<br>";
                                                        $royalty_percent .= "X ".$location_name->fields['ROYALTY_PERCENTAGE']." %"."<br>";
                                                        $location_total .= "$".number_format($value - $TOTAL_AMOUNT_REFUND, 2)."<br>";
                                                        $royalty_percent_array[$key]['ROYALTY_PERCENTAGE'] = $location_name->fields['ROYALTY_PERCENTAGE'];
                                                        $royalty_percent_array[$key]['LOCATION_TOTAL'] = $value - $TOTAL_AMOUNT_REFUND;
                                                    }
                                                    ?>
                                                </span>
                                            </td>
                                            <td style="width:10%; text-align: center;">
                                                <?=$location_total?>
                                                <hr>
                                                <?='$'.number_format($TOTAL_RS_FEE - $TOTAL_AMOUNT_REFUND, 2)?>
                                            </td>
                                            <td style="width:10%; text-align: center;" colspan="2"><?=$royalty_percent?></td>
                                            <td style="width:10%; text-align: center;" colspan="2">
                                                <?php
                                                foreach ($royalty_percent_array AS $key => $value) {
                                                    echo "$".number_format(($value['LOCATION_TOTAL']*($value['ROYALTY_PERCENTAGE']/100)), 2)."<br>";
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                                <div>
                                    I have checked this statement and certify that it is correct and complete. I also certify that none of the above listed enrollments or payments resulted in violation of the Arthur Murray International Inc. dollar limitation policy or limitations set by other regulatory agencies.<br>
                                    I further certify that all the above have been enrolled on Arthur Murray International Inc. approved student enrollment agreements.<br><br>
                                    Prepared by : ............................ Title : ............................ Date : ............................ Signed franchisee ............................
                                </div>
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
