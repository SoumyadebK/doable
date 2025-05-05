<?php
require_once('../global/config.php');
$title = "Customer Summary Report";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5]) ){
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
                <div class="col-md-12 align-self-start">
                    <?php
                    $currentURL = parse_url($_SERVER['REQUEST_URI']);
                    $url = explode("/", $currentURL['path']);
                    if($_SERVER['HTTP_HOST'] == 'localhost' ) {
                        $address = $url[3];
                    } else {
                        $address = $url[2];
                    }
                    if ($address == "reports.php" || $address == "business_reports.php" || $address == "service_provider_reports.php" || $address == "electronic_miscellaneous_reports.php" || $address == "enrollment_reports.php" || $address == "customer_summary_report.php" || $address == "student_mailing_list.php" || $address == "total_open_liability.php") { ?>
                        <ul class="nav nav-pills justify-content-left">
                            <li class="nav-item"><a class="nav-link <?=($address == 'reports.php') ? 'active' : ''?>" href="../admin/reports.php">Electronic Weekly Reports</a></li>
                            <li class="nav-item"><a class="nav-link <?=($address == 'business_reports.php') ? 'active' : ''?>" href="../admin/business_reports.php">Business Reports</a></li>
                            <li class="nav-item"><a class="nav-link <?=($address == 'service_provider_reports.php') ? 'active' : ''?>" href="../admin/service_provider_reports.php">Service Provider Reports</a></li>
                            <li class="nav-item"><a class="nav-link <?=($address == 'electronic_miscellaneous_reports.php') ? 'active' : ''?>" href="../admin/electronic_miscellaneous_reports.php">Electronic Miscellaneous Reports</a></li>
                            <li class="nav-item"><a class="nav-link <?=($address == 'enrollment_reports.php') ? 'active' : ''?>" href="">Enrollment Reports</a></li>
                            <li class="nav-item"><a class="nav-link <?=($address == 'customer_summary_report.php') ? 'active' : ''?>" href="../admin/customer_summary_report.php">Customer Summary Report</a></li>
                            <li class="nav-item"><a class="nav-link <?=($address == 'student_mailing_list.php') ? 'active' : ''?>" href="../admin/student_mailing_list.php">Student Mailing List</a></li>
                            <li class="nav-item"><a class="nav-link <?=($address == 'total_open_liability.php') ? 'active' : ''?>" href="../admin/total_open_liability.php">Total Open Liability Since Last Activity</a></li>
                        </ul>
                    <?php } ?>
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
                                        <th style="width:20%; text-align: center; vertical-align:auto; font-weight: bold" rowspan="2">Student (Active)<br>
                                            Department (All)</th>
                                        <th style="width:20%; text-align: center; font-weight: bold" rowspan="2">Teacher(s) (Split)</th>
                                        <th style="width:20%; text-align: center; font-weight: bold" colspan="3">Overall (PRIs)</th>
                                        <th style="width:30%; text-align: center; font-weight: bold" colspan="4">Open Balance Enrollments</th>
                                        <th style="width:10%; text-align: center; font-weight: bold" colspan="2">PaidAhead</th>
                                    </tr>
                                    <tr>
                                        <th style="width:10%; text-align: center; font-weight: bold">Enrolled</th>
                                        <th style="width:10%; text-align: center; font-weight: bold">Used</th>
                                        <th style="width:10%; text-align: center; font-weight: bold">Remaining</th>
                                        <th style="width:12%; text-align: center; font-weight: bold">Total Cost/PRIs</th>
                                        <th style="width:10%; text-align: center; font-weight: bold">Lesson Avg</th>
                                        <th style="width:10%; text-align: center; font-weight: bold">Balance</th>
                                        <th style="width:10%; text-align: center; font-weight: bold">UnpaidPRI</th>
                                        <th style="width:10%; text-align: center; font-weight: bold">PRIs</th>
                                        <th style="width:10%; text-align: center; font-weight: bold">AMOUNT</th>

                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    $i=1;
                                    $row = $db->Execute("SELECT DOA_USERS.PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.PHONE, DOA_USERS.ACTIVE, DOA_USER_MASTER.PK_USER_MASTER FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER WHERE DOA_USER_ROLES.PK_ROLES = 4 AND DOA_USER_MASTER.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER']);
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
