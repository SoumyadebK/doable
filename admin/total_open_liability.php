<?php
require_once('../global/config.php');
$title = "Total Open Liability Since Last Activity";

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
        <div class="container-fluid">

            <div class="row page-titles">
                <div class="col-md-5 align-self-center">
                    <h4 class="text-themecolor"><?=$title?></h4>
                </div>
                <div class="col-md-7 align-self-center text-end">
                    <div class="d-flex justify-content-end align-items-center">
                        <ol class="breadcrumb justify-content-end">
                            <li class="breadcrumb-item active"><a href="reports.php">Reports</a></li>
                            <li class="breadcrumb-item active"><a href="student_mailing_list.php"><?=$title?></a></li>
                        </ol>

                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-4">
                                    <img src="../assets/images/background/doable_logo.png" style="margin-bottom:15px;  height: 60px; width: auto;">
                                </div>
                                <div class="col-4">
                                    <?php
                                    $name=$db->Execute("SELECT FIRST_NAME, CREATED_ON FROM DOA_USERS WHERE PK_USER = '$_SESSION[PK_USER]'");
                                    ?>
                                    <h2 class="card-title" style="text-align: center; font-weight: bold"><?= $name->fields['FIRST_NAME'] ?> </h2>
                                    <h3 class="card-title" style="text-align: center; font-weight: bold"><?=$title?></h3>
                                    <h4 class="card-title" style="text-align: center; font-weight: bold"><?=$name->fields['CREATED_ON']?></h4>

                                </div>
                                <div class="btn col-4" >
                                    <form action="generate_pdf.php" method="post" >
                                        <button  type="submit" id="export-to-pdf" name="ExportType"
                                                 value="Export to PDF" class="btn btn-info">Export
                                            to PDF</button>
                                    </form>
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
                                    $row = $db->Execute("SELECT DOA_USERS.PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_ID, DOA_USERS.EMAIL_ID, DOA_USERS.PHONE, DOA_USERS.ACTIVE, DOA_USER_MASTER.PK_USER_MASTER FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER WHERE DOA_USERS.PK_ROLES = 4 AND DOA_USER_MASTER.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER']);
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

</script>

</body>
</html>
