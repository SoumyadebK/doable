<?php
require_once('../global/config.php');
$title = "Total Open Liability Since Last Activity";

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
                    if ($address == "reports.php" || $address == "business_reports.php" || $address == "service_provider_reports.php" || $address == "electronic_miscellaneous_reports.php" || $address == "enrollment_reports.php" || $address == "student_mailing_list.php" || $address == "customer_summary_report.php" || $address == "total_open_liability.php") { ?>
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
                            <div class="row">
                                <div class="col-3">
                                    <img src="../assets/images/background/doable_logo.png" style="margin-top:15px; margin-bottom:15px;  height: 60px; width: auto;">
                                </div>
                                <div class="col-6">
                                    <?php
                                    $name=$db->Execute("SELECT CONCAT(FIRST_NAME, ' ', LAST_NAME) AS NAME, CREATED_ON FROM DOA_USERS WHERE PK_USER = '$_SESSION[PK_USER]'");
                                    $originalDate = $name->fields['CREATED_ON'];
                                    $newDate = date("m/d/Y H:i:s", strtotime($originalDate));
                                    ?>
                                    <h3 class="card-title" style="text-align: center; font-weight: bold"><?= $name->fields['NAME'] ?> </h3>
                                    <h2 class="card-title" style="text-align: center; font-weight: bold"><?=$title?></h2>

                                    <h5 class="card-title" style="text-align: center; font-weight: bold">(<?=$newDate.' - '.$date = date('m/d/Y H:i:s', time());?>)</h5>
                                </div>
                                <div class="btn col-3" style="margin-top:20px">
                                        <button  id="export-to-pdf" class="btn btn-info" onclick="viewSamplePdf()">Export
                                            to PDF</button>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table id="myTable" class="table table-bordered" data-page-length='50'>
                                    <thead>
                                    <tr>
                                        <th style="width:30%; text-align: center; vertical-align:auto; font-weight: bold" >Student</th>
                                        <th style="width:15%; text-align: center; font-weight: bold" >Open Balance</th>
                                        <th style="width:15%; text-align: center; font-weight: bold" >PaidAhead</th>
                                        <th style="width:15%; text-align: center; font-weight: bold" >Last Activity Date</th>
                                        <th style="width:15%; text-align: center; font-weight: bold" >Time Elapsed</th>
                                        <th style="width:15%; text-align: center; font-weight: bold" >Running Total</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    $i=1;
                                    $row = $db->Execute("SELECT DOA_USERS.PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.PHONE, DOA_USERS.ACTIVE, DOA_USER_MASTER.PK_USER_MASTER FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER WHERE DOA_USER_ROLES.PK_ROLES = 4 AND DOA_USER_MASTER.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER']);
                                    while (!$row->EOF) {
                                        $balance_data = $db->Execute("SELECT DOA_ENROLLMENT_BILLING.TOTAL_AMOUNT AS OPEN_BALANCE, SUM(TOTAL_BALANCE_PAID) AS TOTAL_PAID, SUM(TOTAL_BALANCE_USED) AS BALANCE_USED, SUM(AMOUNT) AS AMOUNT, SUM(REMAINING_AMOUNT) AS REMAINING, SUM(PAID_AMOUNT) AS PAID, SUM(BILLED_AMOUNT) AS BILLED FROM `DOA_ENROLLMENT_BALANCE` LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_BALANCE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_PAYMENT ON DOA_ENROLLMENT_BALANCE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_LEDGER ON DOA_ENROLLMENT_BALANCE.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_LEDGER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_BILLING ON DOA_ENROLLMENT_BALANCE.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER LEFT JOIN DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER WHERE DOA_USER_MASTER.PK_USER = ".$row->fields['PK_USER']);
                                        $open_balance = 0.00;
                                        $paid_ahead = 0.00;
                                        $last_activity_date = "";
                                        $time_elapsed = 0.00;
                                        $running_total = 0.00;
                                        if ($balance_data->RecordCount() > 0) {
                                            $open_balance = $balance_data->fields['AMOUNT'];
                                            $paid_ahead =  $balance_data->fields['TOTAL_PAID']-$balance_data->fields['BALANCE_USED'];
                                            $last_activity_date = $balance_data->fields['TOTAL_PAID'];
                                            $time_elapsed = $balance_data->fields['BALANCE_USED'];
                                            $running_total = $balance_data->fields['AMOUNT'];
                                        } ?>
                                        <tr>
                                            <td ><?=$row->fields['NAME']?></td>
                                            <td ><?=$open_balance?></td>
                                            <td style="text-align: right" ><?=number_format($paid_ahead , 2)?></td>
                                            <td ><?=$last_activity_date?></td>
                                            <td ><?=$time_elapsed?></td>
                                            <td ><?=number_format($running_total , 2)?></td>
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

    // function viewSamplePdf() {
    //     let DOCUMENT_TEMPLATE = "asldjal"//$('#myTable').html();
    //     $.ajax({
    //         url: "ajax/AjaxFunctions.php",
    //         type: 'POST',
    //         data: {FUNCTION_NAME: 'viewSamplePdf', DOCUMENT_TEMPLATE: DOCUMENT_TEMPLATE},
    //         success:function (data) {
    //             console.log(data);
    //             window.open(
    //                 data,
    //                 '_blank' // <- This is what makes it open in a new window.
    //             );
    //         },
    //         error: (error) => {
    //             console.log(JSON.stringify(error));
    //         }
    //     });
    // }

    var buttonElement = document.querySelector("#myTable");
    buttonElement.addEventListener('click', function() {
        var pdfContent = document.getElementById("pdf-content").innerHTML;
        var windowObject = window.open();

        windowObject.document.write(pdfContent);

        windowObject.print();
        windowObject.close();
    });

</script>

</body>
</html>
