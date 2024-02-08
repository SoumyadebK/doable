<?php
require_once('../global/config.php');
$title = "SUMMARY OF STUDIO BUSINESS REPORT";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 2 ){
    header("location:../login.php");
    exit;
}

if (!empty($_GET['date'])){
    $from_date = $_GET['date'];
    $to_date = date('m/d/y', strtotime("+7 day", strtotime($from_date)));
    $duedt = explode("/", $from_date);
    $date  = mktime(0, 0, 0, $duedt[0], $duedt[1], $duedt[2]);
    $week_number  = (int)date('W', $date);
}
$res = $db->Execute("SELECT BUSINESS_NAME FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
$business_name = $res->RecordCount() > 0 ? $res->fields['BUSINESS_NAME'] : '';
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
                            <div>
                                <img src="../assets/images/background/doable_logo.png" style="margin-bottom:-35px; height: 60px; width: auto;">
                                <h3 class="card-title" style="padding-bottom:15px; text-align: center; font-weight: bold"><?=$title?></h3>
                            </div>
                            <div class="table-responsive">
                                <table id="myTable" class="table table-bordered" data-page-length='50'>
                                    <thead>
                                    <tr>
                                        <th style="width:40%; text-align: center; vertical-align:auto; font-weight: bold"><?=$business_name?></th>
                                        <th style="width:20%; text-align: center; font-weight: bold"><?=$from_date?> - <?=$to_date?></th>
                                        <th style="width:20%; text-align: center; font-weight: bold">Week # : <?=$week_number?></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>
                            <div class="table-responsive">
                                <label style="width:100%; text-align: center; font-weight: bold">CASH RECEIPTS</label>
                                <table id="myTable" class="table table-bordered" data-page-length='50'>
                                    <thead>
                                    <tr>
                                        <th style="width:20%; text-align: center; vertical-align:auto; font-weight: bold">Period</th>
                                        <th style="width:20%; text-align: center; font-weight: bold">Regular</th>
                                        <th style="width:20%; text-align: center; font-weight: bold">Misc. / NonUnit</th>
                                        <th style="width:30%; text-align: center; font-weight: bold">Total</th>
                                    </tr>
                                    <tr>
                                        <?php
                                        $ledger_data = $db_account->Execute("SELECT SUM(PAID_AMOUNT) AS CASH FROM DOA_ENROLLMENT_LEDGER WHERE PK_PAYMENT_TYPE = 3 AND DUE_DATE BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."'");
                                        $cash = $ledger_data->RecordCount() > 0 ? $ledger_data->fields['CASH'] : '0.00';
                                        ?>
                                        <th style="width:25%; text-align: center; vertical-align:auto; font-weight: bold">Week</th>
                                        <th style="width:25%; text-align: center; font-weight: bold"><?=$cash?></th>
                                        <th style="width:25%; text-align: center; font-weight: bold"></th>
                                        <th style="width:25%; text-align: center; font-weight: bold"></th>
                                    </tr>
                                    <tr>
                                        <th style="width:25%; text-align: center; vertical-align:auto; font-weight: bold">Week Refunds</th>
                                        <th style="width:25%; text-align: center; font-weight: bold">0.00</th>
                                        <th style="width:25%; text-align: center; font-weight: bold"></th>
                                        <th style="width:25%; text-align: center; font-weight: bold"></th>
                                    </tr>
                                    <tr>
                                        <th style="width:25%; text-align: center; vertical-align:auto; font-weight: bold">Transfer out</th>
                                        <th style="width:25%; text-align: center; font-weight: bold">0.00</th>
                                        <th style="width:25%; text-align: center; font-weight: bold"></th>
                                        <th style="width:25%; text-align: center; font-weight: bold"></th>
                                    </tr>
                                    <tr>
                                        <?php
                                        $net_data = $db_account->Execute("SELECT SUM(PAID_AMOUNT) AS CASH FROM DOA_ENROLLMENT_LEDGER WHERE PK_PAYMENT_TYPE = 3 AND YEAR(DUE_DATE) = YEAR(CURDATE())");
                                        $net = $net_data->RecordCount() > 0 ? $net_data->fields['CASH'] : '0.00';
                                        ?>
                                        <th style="width:25%; text-align: center; vertical-align:auto; font-weight: bold">NET Y.T.D.</th>
                                        <th style="width:25%; text-align: center; font-weight: bold"><?=$net?></th>
                                        <th style="width:25%; text-align: center; font-weight: bold"></th>
                                        <th style="width:25%; text-align: center; font-weight: bold"></th>
                                    </tr>
                                    <tr>
                                        <?php
                                        $prev_data = $db_account->Execute("SELECT SUM(PAID_AMOUNT) AS CASH FROM DOA_ENROLLMENT_LEDGER WHERE PK_PAYMENT_TYPE = 3 AND YEAR(DUE_DATE) > DATEADD(year,-1,GETDATE())");
                                        $net = $prev_data->RecordCount() > 0 ? $prev_data->fields['CASH'] : '0.00';
                                        ?>
                                        <th style="width:25%; text-align: center; vertical-align:auto; font-weight: bold">PRV. Y.T.D.</th>
                                        <th style="width:25%; text-align: center; font-weight: bold"><?=$net?></th>
                                        <th style="width:25%; text-align: center; font-weight: bold"></th>
                                        <th style="width:25%; text-align: center; font-weight: bold"></th>
                                    </tr>
                                    </thead>
                                </table>
                            </div>
                            <div class="table-responsive">
                                <table id="myTable" class="table table-bordered" data-page-length='50'>
                                    <thead>
                                    <tr>
                                        <th style="width:20%; text-align: center; vertical-align:auto; font-weight: bold" colspan="4">INQUIRIES</th>
                                        <th style="width:20%; text-align: center; font-weight: bold" colspan="3">LESSONS TAUGHT | Exchange</th>
                                        <th style="width:20%; text-align: center; font-weight: bold" rowspan="2">ACTIVE<br/>
                                            STUDENTS</th>
                                    </tr>
                                    <tr>
                                        <th style="width:10%; text-align: center; vertical-align:auto; font-weight: bold" colspan="2">Contact</th>
                                        <th style="width:10%; text-align: center; font-weight: bold">Booked</th>
                                        <th style="width:10%; text-align: center; font-weight: bold">Showed</th>
                                        <th style="width:10%; text-align: center; font-weight: bold">Pvt Intv(front)</th>
                                        <th style="width:10%; text-align: center; font-weight: bold">Pvt Ren(back)</th>
                                        <th style="width:10%; text-align: center; font-weight: bold"># in class [incl.core]</th>
                                    </tr>
                                    <tr>
                                        <th style="width:10%; text-align: center; vertical-align:auto; font-weight: bold">Week</th>
                                        <th style="width:10%; text-align: center; font-weight: bold"></th>
                                        <th style="width:10%; text-align: center; font-weight: bold"></th>
                                        <th style="width:10%; text-align: center; font-weight: bold"></th>
                                        <th style="width:10%; text-align: center; font-weight: bold"></th>
                                        <th style="width:10%; text-align: center; font-weight: bold"></th>
                                        <th style="width:10%; text-align: center; font-weight: bold"></th>
                                        <th style="width:10%; text-align: center; font-weight: bold">Department</th>
                                    </tr>
                                    <tr>
                                        <th style="width:10%; text-align: center; vertical-align:auto; font-weight: bold">YTD</th>
                                        <th style="width:10%; text-align: center; font-weight: bold"></th>
                                        <th style="width:10%; text-align: center; font-weight: bold"></th>
                                        <th style="width:10%; text-align: center; font-weight: bold"></th>
                                        <th style="width:10%; text-align: center; font-weight: bold"></th>
                                        <th style="width:10%; text-align: center; font-weight: bold"></th>
                                        <th style="width:10%; text-align: center; font-weight: bold"></th>
                                        <th style="width:10%; text-align: center; font-weight: bold">19 Intv(front)</th>
                                    </tr>
                                    <tr>
                                        <th style="width:10%; text-align: center; vertical-align:auto; font-weight: bold">PREV</th>
                                        <th style="width:10%; text-align: center; font-weight: bold"></th>
                                        <th style="width:10%; text-align: center; font-weight: bold"></th>
                                        <th style="width:10%; text-align: center; font-weight: bold"></th>
                                        <th style="width:10%; text-align: center; font-weight: bold"></th>
                                        <th style="width:10%; text-align: center; font-weight: bold"></th>
                                        <th style="width:10%; text-align: center; font-weight: bold"></th>
                                        <th style="width:10%; text-align: center; font-weight: bold">38 Ren(back)</th>
                                    </tr>
                                    </thead>
                                </table>
                            </div>
                            <div class="table-responsive">
                                <label style="width:100%; text-align: center; font-weight: bold">UNIT SALES TRACKING</label>
                                <table id="myTable" class="table table-bordered" data-page-length='50'>
                                    <thead>
                                    <tr>
                                        <th style="width:5%; text-align: center; vertical-align:auto; font-weight: bold"></th>
                                        <th style="width:19%; text-align: center; font-weight: bold" colspan="2">Pre Original</th>
                                        <th style="width:19%; text-align: center; font-weight: bold" colspan="2">Original</th>
                                        <th style="width:19%; text-align: center; font-weight: bold" colspan="2">Extension</th>
                                        <th style="width:19%; text-align: center; font-weight: bold" colspan="2">Renewal</th>
                                        <th style="width:19%; text-align: center; font-weight: bold" colspan="2">Total</th>
                                    </tr>
                                    <tr>
                                        <th style="width:5%; text-align: center; vertical-align:auto; font-weight: bold" rowspan="3">Week</th>
                                        <th style="width:9%; text-align: center; font-weight: bold">T: 4</th>
                                        <th style="width:9%; text-align: center; font-weight: bold">S: 4</th>
                                        <th style="width:9%; text-align: center; font-weight: bold">T: 4</th>
                                        <th style="width:9%; text-align: center; font-weight: bold">S: 4</th>
                                        <th style="width:9%; text-align: center; font-weight: bold">T: 4</th>
                                        <th style="width:9%; text-align: center; font-weight: bold">S: 4</th>
                                        <th style="width:9%; text-align: center; font-weight: bold">T: 4</th>
                                        <th style="width:9%; text-align: center; font-weight: bold">S: 4</th>
                                        <th style="width:9%; text-align: center; font-weight: bold">T: 4</th>
                                        <th style="width:9%; text-align: center; font-weight: bold">S: 4</th>
                                    </tr>
                                    <tr>
                                        <th style="width:18%; text-align: center; vertical-align:auto; font-weight: bold" colspan="2"></th>
                                        <th style="width:18%; text-align: center; font-weight: bold" colspan="2"></th>
                                        <th style="width:18%; text-align: center; font-weight: bold" colspan="2"></th>
                                        <th style="width:18%; text-align: center; font-weight: bold" colspan="2"></th>
                                        <th style="width:18%; text-align: center; font-weight: bold" colspan="2"></th>
                                    </tr>
                                    <tr>
                                        <th style="width:18%; text-align: center; vertical-align:auto; font-weight: bold" colspan="2"></th>
                                        <th style="width:18%; text-align: center; font-weight: bold" colspan="2"></th>
                                        <th style="width:18%; text-align: center; font-weight: bold" colspan="2"></th>
                                        <th style="width:18%; text-align: center; font-weight: bold" colspan="2"></th>
                                        <th style="width:18%; text-align: center; font-weight: bold" colspan="2"></th>
                                    </tr>
                                    <tr>
                                        <th style="width:9%; text-align: center;font-weight: bold">Adjust</th>
                                        <th style="width:18%; text-align: center; font-weight: bold" colspan="2"></th>
                                        <th style="width:18%; text-align: center; font-weight: bold" colspan="2"></th>
                                        <th style="width:18%; text-align: center; font-weight: bold" colspan="2"></th>
                                        <th style="width:18%; text-align: center; font-weight: bold" colspan="2"></th>
                                        <th style="width:18%; text-align: center; font-weight: bold" colspan="2"></th>
                                    </tr>
                                    <tr>
                                        <th style="width:9%; text-align: center; vertical-align:auto; font-weight: bold" rowspan="3">Net<br><br> YTD</th>
                                        <th style="width:9%; text-align: center; font-weight: bold">T: 4</th>
                                        <th style="width:9%; text-align: center; font-weight: bold">S: 4</th>
                                        <th style="width:9%; text-align: center; font-weight: bold">T: 4</th>
                                        <th style="width:9%; text-align: center; font-weight: bold">S: 4</th>
                                        <th style="width:9%; text-align: center; font-weight: bold">T: 4</th>
                                        <th style="width:9%; text-align: center; font-weight: bold">S: 4</th>
                                        <th style="width:9%; text-align: center; font-weight: bold">T: 4</th>
                                        <th style="width:9%; text-align: center; font-weight: bold">S: 4</th>
                                        <th style="width:9%; text-align: center; font-weight: bold">T: 4</th>
                                        <th style="width:9%; text-align: center; font-weight: bold">S: 4</th>
                                    </tr>
                                    <tr>
                                        <th style="width:18%; text-align: center; font-weight: bold" colspan="2"></th>
                                        <th style="width:18%; text-align: center; font-weight: bold" colspan="2"></th>
                                        <th style="width:18%; text-align: center; font-weight: bold" colspan="2"></th>
                                        <th style="width:18%; text-align: center; font-weight: bold" colspan="2"></th>
                                        <th style="width:18%; text-align: center; font-weight: bold" colspan="2"></th>
                                    </tr>
                                    <tr>
                                        <th style="width:18%; text-align: center; font-weight: bold" colspan="2"></th>
                                        <th style="width:18%; text-align: center; font-weight: bold" colspan="2"></th>
                                        <th style="width:18%; text-align: center; font-weight: bold" colspan="2"></th>
                                        <th style="width:18%; text-align: center; font-weight: bold" colspan="2"></th>
                                        <th style="width:18%; text-align: center; font-weight: bold" colspan="2"></th>
                                    </tr>
                                    <tr>
                                        <th style="width:9%; text-align: center; font-weight: bold">Prev.</th>
                                        <th style="width:18%; text-align: center; font-weight: bold" colspan="2"></th>
                                        <th style="width:18%; text-align: center; font-weight: bold" colspan="2"></th>
                                        <th style="width:18%; text-align: center; font-weight: bold" colspan="2"></th>
                                        <th style="width:18%; text-align: center; font-weight: bold" colspan="2"></th>
                                        <th style="width:18%; text-align: center; font-weight: bold" colspan="2"></th>
                                    </tr>
                                    </thead>
                                </table>
                            </div>
                            <div class="table-responsive">
                                <label style="width:100%; text-align: center; font-weight: bold">MISCELLANEOUS / FESTIVAL SALES TRACKING</label>
                                <table id="myTable" class="table table-bordered" data-page-length='50'>
                                    <thead>
                                    <tr>
                                        <th style="width:20%; text-align: center; vertical-align:auto; font-weight: bold" rowspan="2"></th>
                                        <th style="width:20%; text-align: center; vertical-align:auto; font-weight: bold" colspan="2">NON-UNIT SALES</th>
                                        <th style="width:20%; text-align: center; font-weight: bold" rowspan="2">SUNDRY</th>
                                        <th style="width:20%; text-align: center; font-weight: bold" rowspan="2">MISCELLANEOUS</th>
                                    </tr>
                                    <tr>
                                        <th style="width:10%; text-align: center; vertical-align:auto; font-weight: bold">Private/coach</th>
                                        <th style="width:10%; text-align: center; font-weight: bold">Class</th>
                                    </tr>
                                    <tr>
                                        <th style="width:10%; text-align: center; vertical-align:auto; font-weight: bold">Week</th>
                                        <th style="width:10%; text-align: center; font-weight: bold"></th>
                                        <th style="width:10%; text-align: center; font-weight: bold"></th>
                                        <th style="width:10%; text-align: center; font-weight: bold"></th>
                                        <th style="width:10%; text-align: center; font-weight: bold"></th>
                                    </tr>
                                    <tr>
                                        <th style="width:10%; text-align: center; vertical-align:auto; font-weight: bold">YTD</th>
                                        <th style="width:10%; text-align: center; font-weight: bold"></th>
                                        <th style="width:10%; text-align: center; font-weight: bold"></th>
                                        <th style="width:10%; text-align: center; font-weight: bold"></th>
                                        <th style="width:10%; text-align: center; font-weight: bold"></th>
                                    </tr>
                                    <tr>
                                        <th style="width:10%; text-align: center; font-weight: bold">Prev.</th>
                                        <th style="width:10%; text-align: center; font-weight: bold"></th>
                                        <th style="width:10%; text-align: center; font-weight: bold"></th>
                                        <th style="width:10%; text-align: center; font-weight: bold"></th>
                                        <th style="width:10%; text-align: center; font-weight: bold"></th>
                                    </tr>
                                    </thead>
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
                            <div class="table-responsive">
                                <table id="myTable" class="table table-bordered" data-page-length='50'>
                                    <label style="width:100%; text-align: center; font-weight: bold">Active students list (Last 30 days)</label>
                                    <thead>
                                    <tr>
                                        <th style="width:4%; text-align: center; vertical-align:auto; font-weight: bold">#</th>
                                        <th style="width:24%; text-align: center; font-weight: bold">Student</th>
                                        <th style="width:24%; text-align: center; font-weight: bold">11th Appointment Date</th>
                                        <th style="width:24%; text-align: center; font-weight: bold">Last Appearance This Period (Service)</th>
                                        <th style="width:24%; text-align: center; font-weight: bold">Department</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    $i=1;
                                    //$row = $db->Execute("SELECT DOA_USERS.PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.PHONE, DOA_USERS.ACTIVE, DOA_USER_MASTER.PK_USER_MASTER FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER WHERE DOA_USER_ROLES.PK_ROLES = 4 AND DOA_USER_MASTER.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER']);
                                    while (!$row->EOF) {
                                        $balance_data = $db->Execute("SELECT SUM(BALANCE_PAYABLE) AS ENROLLED, SUM(TOTAL_BALANCE_PAID) AS TOTAL_PAID, SUM(TOTAL_BALANCE_USED) AS BALANCE_USED, SUM(AMOUNT) AS AMOUNT, SUM(REMAINING_AMOUNT) AS REMAINING, SUM(PAID_AMOUNT) AS PAID, SUM(BILLED_AMOUNT) AS BILLED FROM `DOA_ENROLLMENT_BALANCE` LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_BALANCE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_PAYMENT ON DOA_ENROLLMENT_BALANCE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_LEDGER ON DOA_ENROLLMENT_BALANCE.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_LEDGER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_BILLING ON DOA_ENROLLMENT_BALANCE.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER LEFT JOIN DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER WHERE DOA_USER_MASTER.PK_USER = ".$row->fields['PK_USER']);
                                        $enrolled = 0.00;
                                        $total_paid = 0.00;
                                        $balance_left = 0.00;
                                        $used = 0.00;
                                        $balance = 0.00;
                                        $amount = 0.00;
                                        $remaining = 0.00;
                                        $paid_amount = 0.00;
                                        if ($balance_data->RecordCount() > 0) {
                                            $enrolled = $balance_data->fields['ENROLLED'];
                                            $total_paid = $balance_data->fields['TOTAL_PAID'];
                                            $balance = $balance_data->fields['TOTAL_PAID'];
                                            $used = $balance_data->fields['BALANCE_USED'];
                                            $amount = $balance_data->fields['AMOUNT'];
                                            $remaining = $balance_data->fields['REMAINING'];
                                            $balance_left = $balance_data->fields['TOTAL_PAID']-$balance_data->fields['BALANCE_USED'];
                                            $paid_amount = $balance_data->fields['PAID'];
                                        } ?>
                                        <tr>
                                            <td onclick="editpage(<?=$row->fields['PK_USER']?>, <?=$row->fields['PK_USER_MASTER']?>);"><?=$row->fields['NAME']?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_USER']?>, <?=$row->fields['PK_USER_MASTER']?>);"><?=$row->fields['USER_NAME']?></td>
                                            <td style="text-align: right" onclick="editpage(<?=$row->fields['PK_USER']?>, <?=$row->fields['PK_USER_MASTER']?>);"><?=number_format($enrolled , 2)?></td>
                                            <td style="text-align: right" onclick="editpage(<?=$row->fields['PK_USER']?>, <?=$row->fields['PK_USER_MASTER']?>);"><?=number_format($used, 2)?></td>
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
