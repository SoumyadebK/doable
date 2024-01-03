<?php
require_once('../global/config.php');
$title = "WEEKLY ROYALTY / SERVICE REPORT";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 2 ){
    header("location:../login.php");
    exit;
} ?>

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
                                        <th style="width:20%; text-align: center; vertical-align:auto; font-weight: bold" colspan="6">Franchisee: Arthur Murray Thousand Oaks</th>
                                        <th style="width:20%; text-align: center; font-weight: bold" colspan="2">Part 1</th>
                                        <th style="width:20%; text-align: center; font-weight: bold" colspan="5">Week # (12/24/2023 - 12/30/2023)</th>
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
                                            <td style="text-align: right" onclick="editpage(<?=$row->fields['PK_USER']?>, <?=$row->fields['PK_USER_MASTER']?>);"><?=number_format($balance_left, 2)?></td>
                                            <td style="text-align: right" onclick="editpage(<?=$row->fields['PK_USER']?>, <?=$row->fields['PK_USER_MASTER']?>);"><?=number_format($balance_left, 2)?></td>
                                            <td style="text-align: right" onclick="editpage(<?=$row->fields['PK_USER']?>, <?=$row->fields['PK_USER_MASTER']?>);"><?=number_format($balance_left, 2)?></td>
                                            <td style="text-align: right" onclick="editpage(<?=$row->fields['PK_USER']?>, <?=$row->fields['PK_USER_MASTER']?>);"><?=number_format($balance_left, 2)?></td>
                                            <td style="text-align: right" onclick="editpage(<?=$row->fields['PK_USER']?>, <?=$row->fields['PK_USER_MASTER']?>);"><?=number_format($balance_left, 2)?></td>
                                            <td style="text-align: right" onclick="editpage(<?=$row->fields['PK_USER']?>, <?=$row->fields['PK_USER_MASTER']?>);"><?=number_format($balance_left, 2)?></td>
                                            <td style="text-align: right" onclick="editpage(<?=$row->fields['PK_USER']?>, <?=$row->fields['PK_USER_MASTER']?>);"><?=number_format($paid_amount, 2)?></td>
                                        </tr>
                                        <?php $row->MoveNext();
                                        $i++; } ?>
                                    <tr>
                                        <th style="width:10%; text-align: center; font-weight: bold" colspan="7">Daily Totals</th>
                                        <th style="width:10%; text-align: center; font-weight: bold" colspan="1"></th>
                                        <th style="width:10%; text-align: center; font-weight: bold" colspan="1"></th>
                                        <th style="width:10%; text-align: center; font-weight: bold" colspan="1"></th>
                                        <th style="width:10%; text-align: center; font-weight: bold" colspan="1"></th>
                                        <th style="width:10%; text-align: center; font-weight: bold" colspan="1"></th>
                                        <th style="width:10%; text-align: center; font-weight: bold" colspan="1"></th>
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
                                    <thead>
                                    <tr>
                                        <th style="width:20%; text-align: center; vertical-align:auto; font-weight: bold" colspan="6">Franchisee: Arthur Murray Thousand Oaks</th>
                                        <th style="width:20%; text-align: center; font-weight: bold" colspan="2">Part 2</th>
                                        <th style="width:20%; text-align: center; font-weight: bold" colspan="5">Week # (12/24/2023 - 12/30/2023)</th>
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
                                        <th style="width:10%; text-align: center; font-weight: bold">Total receipts</th>
                                        <th style="width:10%; text-align: center; font-weight: bold"></th>
                                        <th style="width:10%; text-align: center; font-weight: bold"></th>
                                        <th style="width:10%; text-align: center; font-weight: bold"></th>
                                        <th style="width:10%; text-align: center; font-weight: bold"></th>
                                        <th style="width:5%; text-align: center; font-weight: bold">=</th>
                                        <th style="width:10%; text-align: center; font-weight: bold"></th>
                                        <th style="width:5%; text-align: center; font-weight: bold">+</th>
                                        <th style="width:10%; text-align: center; font-weight: bold"></th>
                                        <th style="width:5%; text-align: center; font-weight: bold">=</th>
                                        <th style="width:10%; text-align: center; font-weight: bold"></th>
                                    </tr>
                                    <tr>
                                        <th style="width:10%; text-align: center; font-weight: bold">Total refunds/credits</th>
                                        <th style="width:10%; text-align: center; font-weight: bold"></th>
                                        <th style="width:10%; text-align: center; font-weight: bold"></th>
                                        <th style="width:10%; text-align: center; font-weight: bold"></th>
                                        <th style="width:10%; text-align: center; font-weight: bold"></th>
                                        <th style="width:5%; text-align: center; font-weight: bold">=</th>
                                        <th style="width:10%; text-align: center; font-weight: bold"></th>
                                        <th style="width:5%; text-align: center; font-weight: bold">+</th>
                                        <th style="width:10%; text-align: center; font-weight: bold"></th>
                                        <th style="width:5%; text-align: center; font-weight: bold">=</th>
                                        <th style="width:10%; text-align: center; font-weight: bold"></th>
                                    </tr>
                                    <tr>
                                        <th style="width:10%; text-align: center; font-weight: bold" colspan="6">Total subject to r/s fee</th>
                                        <th style="width:10%; text-align: center; font-weight: bold"></th>
                                        <th style="width:10%; text-align: center; font-weight: bold" colspan="2"></th>
                                        <th style="width:10%; text-align: center; font-weight: bold" colspan="2"></th>
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
                                            <td style="text-align: right" onclick="editpage(<?=$row->fields['PK_USER']?>, <?=$row->fields['PK_USER_MASTER']?>);"><?=number_format($balance_left, 2)?></td>
                                            <td style="text-align: right" onclick="editpage(<?=$row->fields['PK_USER']?>, <?=$row->fields['PK_USER_MASTER']?>);"><?=number_format($balance_left, 2)?></td>
                                            <td style="text-align: right" onclick="editpage(<?=$row->fields['PK_USER']?>, <?=$row->fields['PK_USER_MASTER']?>);"><?=number_format($balance_left, 2)?></td>
                                            <td style="text-align: right" onclick="editpage(<?=$row->fields['PK_USER']?>, <?=$row->fields['PK_USER_MASTER']?>);"><?=number_format($balance_left, 2)?></td>
                                            <td style="text-align: right" onclick="editpage(<?=$row->fields['PK_USER']?>, <?=$row->fields['PK_USER_MASTER']?>);"><?=number_format($balance_left, 2)?></td>
                                            <td style="text-align: right" onclick="editpage(<?=$row->fields['PK_USER']?>, <?=$row->fields['PK_USER_MASTER']?>);"><?=number_format($balance_left, 2)?></td>
                                            <td style="text-align: right" onclick="editpage(<?=$row->fields['PK_USER']?>, <?=$row->fields['PK_USER_MASTER']?>);"><?=number_format($paid_amount, 2)?></td>
                                        </tr>
                                        <?php $row->MoveNext();
                                        $i++; } ?>
                                    </tbody>
                                </table>
                                <div>
                                    I have checked this statement and certify that it is correct and complete. I also certify that none of the above listed enrollments or payments resulted in violation of the Arthur Murray International Inc. dollar limitation policy or limitations set by other regulatory agencies.<br>
                                    I further certify that all the above have been enrolled on Arthur Murray International Inc. approved student enrollment agreements.<br><br>
                                    Prepared by : ............................ Title : ........................ Date : ............. Signed franchisee :...............
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
