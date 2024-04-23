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
                        CONCAT(DOA_USERS.FIRST_NAME, ' ' ,DOA_USERS.LAST_NAME) AS STUDENT_NAME,
                        CONCAT(CLOSER.FIRST_NAME, ' ' ,CLOSER.LAST_NAME) AS CLOSER_NAME,
                        DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER,
                        DOA_ENROLLMENT_MASTER.CUSTOMER_ENROLLMENT_NUMBER,
                        DOA_ENROLLMENT_MASTER.PK_LOCATION
                    FROM
                        DOA_ENROLLMENT_PAYMENT
                    LEFT JOIN $master_database.DOA_PAYMENT_TYPE AS DOA_PAYMENT_TYPE ON DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE = DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE
                            
                    LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER
                    LEFT JOIN $master_database.DOA_USERS AS CLOSER ON DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID = CLOSER.PK_USER
                    
                    LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER
                    LEFT JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER
                    
                    WHERE DOA_USER_MASTER.PRIMARY_LOCATION_ID IN (".$DEFAULT_LOCATION_ID.")
                    AND DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."'";

$account_data = $db->Execute("SELECT * FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
$user_data = $db->Execute("SELECT * FROM DOA_USERS WHERE PK_USER = '$_SESSION[PK_USER]'");
$business_name = $account_data->RecordCount() > 0 ? $account_data->fields['BUSINESS_NAME'] : '';


if ($type === 'export') {
    $client_id = constant('client_id');
    $client_secret = constant('client_secret');
    $ami_api_url = constant('ami_api_url').'/oauth/v2/token';

    $AM_USER_NAME = $account_data->fields['AM_USER_NAME'];
    $AM_PASSWORD = $account_data->fields['AM_PASSWORD'];
    $AM_REFRESH_TOKEN = $account_data->fields['AM_REFRESH_TOKEN'];

    $user_credential = [
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'grant_type' => 'password',
        'username' => $AM_USER_NAME,
        'password' => $AM_PASSWORD
    ];

    /*$auth_data = callArturMurrayApi($ami_api_url, $user_credential, 'GET');
    $access_token = json_decode($auth_data)->access_token;*/


    $payment_data = $db_account->Execute($PAYMENT_QUERY);

    while (!$payment_data->EOF) {
        $enrollment_service_data = $db_account->Execute("SELECT SUM(`NUMBER_OF_SESSION`) AS TOTAL_UNIT, SUM(`FINAL_AMOUNT`) AS TOTAL_AMOUNT, SUM(`TOTAL_AMOUNT_PAID`) AS TOTAL_PAID FROM `DOA_ENROLLMENT_SERVICE` WHERE `PK_ENROLLMENT_MASTER` = ".$payment_data->fields['PK_ENROLLMENT_MASTER']);
        $line_item[] = array(
            "receipt_number" => $payment_data->fields['ENROLLMENT_ID'],
            "date_paid" => $payment_data->fields['CREATED_ON'],
            "students_full_name" => $payment_data->fields['FIRST_NAME'].' '.$payment_data->fields['LAST_NAME'],
            //"executive" => $payment_data->fields['ENROLLMENT_ID'],
            //"staff_members" => $payment_data->fields['ENROLLMENT_ID'],
            "sale_code" => 'PRI',
            "custom_package" => 'TEST',
            "number_of_units" => $enrollment_service_data->fields['TOTAL_UNIT'],
            "sale_value" => $enrollment_service_data->fields['TOTAL_AMOUNT'],
            "cash" => $enrollment_service_data->fields['TOTAL_PAID'],
            "miscellaneous_services" => 0,
            "sundry" => 0,
        );
        $payment_data->MoveNext();
    }

    $data = [
        'type' => 'royalty',
        'prepared_by' => $user_data->fields['FIRST_NAME'].' '.$user_data->fields['LAST_NAME'],
        'week_number' => 2,
        'week_year' => 2023,
        'line_items' => $line_item
    ];

    $url = 'https://api.arthurmurrayfranchisee.com/api/v1/reports';
    $authorization = "Authorization: Bearer YTM4YWEyNzU4MWFhZTZkNjhlMWJlYWQ5NWU4ODJkYmE3MzBlYzYxYTZmMWNlMWYyMTJkZGI5N2JiMWYyMjE3ZA";

    $get_data = callArturMurrayApi($url, $data, 'GET', $authorization);

    pre_r($get_data);

    die();
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
                                        <th style="width:20%; text-align: center; vertical-align:auto; font-weight: bold" colspan="6">Franchisee: <?=$business_name?></th>
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
                                    $TOTAL_AMOUNT = 0;
                                    $LOCATION_TOTAL = [];
                                    while (!$payment_data->EOF) {
                                        $teacher_data = $db_account->Execute("SELECT GROUP_CONCAT(DISTINCT(CONCAT(TEACHER.FIRST_NAME, ' ', TEACHER.LAST_NAME)) SEPARATOR ', ') AS TEACHER_NAME FROM DOA_ENROLLMENT_SERVICE_PROVIDER LEFT JOIN $master_database.DOA_USERS AS TEACHER ON DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID = TEACHER.PK_USER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = ".$payment_data->fields['PK_ENROLLMENT_MASTER']);
                                        $AMOUNT = $payment_data->fields['AMOUNT'];
                                        $TOTAL_AMOUNT += $AMOUNT;
                                        $enrollment_service_code_data = $db_account->Execute("SELECT SUM(`NUMBER_OF_SESSION`) AS TOTAL_UNIT FROM `DOA_ENROLLMENT_SERVICE` LEFT JOIN DOA_SERVICE_CODE ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE WHERE IS_GROUP = 0 AND DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = ".$payment_data->fields['PK_ENROLLMENT_MASTER']." GROUP BY PK_ENROLLMENT_MASTER");
                                        $enrollment_service_data = $db_account->Execute("SELECT SUM(`NUMBER_OF_SESSION`) AS TOTAL_UNIT, SUM(`FINAL_AMOUNT`) AS TOTAL_AMOUNT, DOA_SERVICE_MASTER.PK_SERVICE_CLASS, DOA_SERVICE_MASTER.IS_SUNDRY FROM `DOA_ENROLLMENT_SERVICE` LEFT JOIN DOA_SERVICE_MASTER ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER WHERE DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = ".$payment_data->fields['PK_ENROLLMENT_MASTER']." GROUP BY PK_ENROLLMENT_MASTER");
                                        if($enrollment_service_data->fields['PK_SERVICE_CLASS'] == 5 && $enrollment_service_data->fields['IS_SUNDRY'] == 0) {
                                            $MISC_TOTAL += $AMOUNT;
                                        } elseif($enrollment_service_data->fields['PK_SERVICE_CLASS'] == 5 && $enrollment_service_data->fields['IS_SUNDRY'] == 1) {
                                            $SUNDRY_TOTAL += $AMOUNT;
                                        } else {
                                            $REGULAR_TOTAL += $AMOUNT;
                                        }

                                        if($enrollment_service_data->fields['IS_SUNDRY'] == 0) {
                                            $TOTAL_RS_FEE += $AMOUNT;
                                            if (isset($LOCATION_TOTAL[$payment_data->fields['PK_LOCATION']])) {
                                                $LOCATION_TOTAL[$payment_data->fields['PK_LOCATION']] = $LOCATION_TOTAL[$payment_data->fields['PK_LOCATION']]+$AMOUNT;
                                            } else {
                                                $LOCATION_TOTAL[$payment_data->fields['PK_LOCATION']] = $AMOUNT;
                                            }
                                        } ?>
                                        <tr style="text-align: center;">
                                            <td><?=$payment_data->fields['RECEIPT_NUMBER']?></td>
                                            <td><?=date('m/d/Y', strtotime($payment_data->fields['PAYMENT_DATE']))?></td>
                                            <td><?=$payment_data->fields['STUDENT_NAME']?></td>
                                            <td><?=$payment_data->fields['PAYMENT_TYPE']?></td>
                                            <td><?=$payment_data->fields['CLOSER_NAME']?></td>
                                            <td><?=$teacher_data->fields['TEACHER_NAME']?></td>
                                            <td>
                                                <?php
                                                if($enrollment_service_data->fields['PK_SERVICE_CLASS'] == 5) {
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
                                            <td><?=$enrollment_service_code_data->fields['TOTAL_UNIT'].' / $'.$enrollment_service_data->fields['TOTAL_AMOUNT']?></td>
                                            <td><?=($enrollment_service_data->fields['PK_SERVICE_CLASS'] == 5) ? '0.00' : '$'.$AMOUNT?></td>
                                            <td><?=($enrollment_service_data->fields['PK_SERVICE_CLASS'] == 5 && $enrollment_service_data->fields['IS_SUNDRY'] == 1) ? '$'.$AMOUNT : '0.00'?></td>
                                            <td><?=($enrollment_service_data->fields['PK_SERVICE_CLASS'] == 5 && $enrollment_service_data->fields['IS_SUNDRY'] == 0) ? '$'.$AMOUNT : '0.00'?></td>
                                            <td><?='$'.$AMOUNT?></td>
                                            <td><?='$'.number_format($TOTAL_AMOUNT, 2)?></td>
                                        </tr>
                                        <?php
                                        $payment_data->MoveNext();
                                    }
                                    ?>
                                        <tr>
                                            <th style="width:10%; text-align: center; font-weight: bold" colspan="7">Daily Totals</th>
                                            <th style="width:10%; text-align: center; font-weight: bold" colspan="1"><?='$'.number_format($TOTAL_AMOUNT, 2)?></th>
                                            <th style="width:10%; text-align: center; font-weight: bold" colspan="1"><?='$'.number_format($REGULAR_TOTAL, 2)?></th>
                                            <th style="width:10%; text-align: center; font-weight: bold" colspan="1"><?='$'.number_format($SUNDRY_TOTAL, 2)?></th>
                                            <th style="width:10%; text-align: center; font-weight: bold" colspan="1"><?='$'.number_format($MISC_TOTAL, 2)?></th>
                                            <th style="width:10%; text-align: center; font-weight: bold" colspan="1"><?='$'.number_format($TOTAL_RS_FEE, 2)?></th>
                                            <th style="width:10%; text-align: center; font-weight: bold" colspan="1"><?='$'.number_format($TOTAL_AMOUNT, 2)?></th>
                                        </tr>
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
                                        <tr><th colspan="13">Refunds or credits below completed tuition refund report & photocopy of front & back of caceled cheks must be attached in order to receive credits.
                                                (Identify bank plan, rewrites & cancellation. Attach detail on authorized D-O-R transportation details.)</th>
                                        </tr>
                                        <tr>
                                            <th style="width:10%; text-align: center; font-weight: bold" rowspan="2">Receipt number</th>
                                            <th style="width:10%; text-align: center; font-weight: bold" rowspan="2">Date</th>
                                            <th style="width:10%; text-align: center; font-weight: bold" rowspan="2">Student name</th>
                                            <th style="width:10%; text-align: center; font-weight: bold" colspan="2">Staff code</th>
                                            <th style="width:10%; text-align: center; font-weight: bold" colspan="2"></th>
                                            <th style="width:10%; text-align: center; font-weight: bold" rowspan="2" colspan="4">Studio Refunds Deductions</th>
                                        </tr>
                                        <tr>
                                            <th style="width:10%; text-align: center; font-weight: bold">Closer</th>
                                            <th style="width:10%; text-align: center; font-weight: bold">Teachers</th>
                                            <th style="width:10%; text-align: center; font-weight: bold">Type</th>
                                            <th style="width:10%; text-align: center; font-weight: bold">Units/Total Cost</th>
                                        </tr>
                                        <tr>
                                            <th style="width:10%; text-align: center; font-weight: bold" colspan="10">Refunds Total</th>
                                            <th style="width:10%; text-align: center; font-weight: bold"></th>
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
                                            <td style="width:10%; text-align: center;">$0.00</td>
                                            <td style="width:5%; text-align: center;">+</td>
                                            <td style="width:10%; text-align: center;">$0.00</td>
                                            <td style="width:5%; text-align: center;">=</td>
                                            <td style="width:10%; text-align: center;">$0.00</td>
                                        </tr>
                                        <tr>
                                            <td style="width:10%; text-align: center; font-weight: bold" colspan="6">Total subject to r/s fee
                                                <span style="font-weight: normal; float: right;">
                                                    <?php
                                                    foreach ($LOCATION_TOTAL AS $key => $value) {
                                                        $location_name = $db->Execute("SELECT `LOCATION_NAME` FROM `DOA_LOCATION` WHERE `PK_LOCATION` = ".$key);
                                                        echo $location_name->fields['LOCATION_NAME']." - "."<br>";
                                                    }
                                                    ?>
                                                </span>
                                            </td>
                                            <td style="width:10%; text-align: center;">
                                                <?php
                                                foreach ($LOCATION_TOTAL AS $key => $value) {
                                                    echo "$".number_format($value, 2)."<br>";
                                                }
                                                ?>
                                                <hr>
                                                <?='$'.number_format($TOTAL_RS_FEE, 2)?>
                                            </td>
                                            <td style="width:10%; text-align: center;" colspan="2">X 7.00 %</td>
                                            <td style="width:10%; text-align: center;" colspan="2"><?='$'.number_format($TOTAL_RS_FEE*.07, 2)?></td>
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
        </div>
    </div>
</div>

<?php require_once('../includes/footer.php');?>

<script>
    // $(function () {
    //     $('#myTable').DataTable({
    //         "columnDefs": [
    //             { "targets": [0,2,5], "searchable": false }
    //         ]
    //     });
    // });
    function ConfirmDelete(anchor)
    {
        let conf = confirm("Are you sure you want to delete?");
        if(conf)
            window.location=anchor.attr("href");
    }
    // function editpage(id, master_id){
    //     window.location.href = "customer.php?id="+id+"&master_id="+master_id;
    //
    // }

</script>

</body>
</html>
