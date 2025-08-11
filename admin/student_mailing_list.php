<?php
require_once('../global/config.php');
$title = "Student Mailing List";

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
                    if ($address == "reports.php" || $address == "business_reports.php" || $address == "service_provider_reports.php" || $address == "electronic_miscellaneous_reports.php" || $address == "enrollment_reports.php" || $address == "student_mailing_list.php" || $address == "total_open_liability.php" || $address == "active_account_balance_report.php" || $address == "cash_report.php") { ?>
                        <ul class="nav nav-pills justify-content-left">
                            <li class="nav-item"><a class="nav-link <?=($address == 'reports.php') ? 'active' : ''?>" href="../admin/reports.php">Electronic Weekly Reports</a></li>
                            <li class="nav-item"><a class="nav-link <?=($address == 'business_reports.php') ? 'active' : ''?>" href="../admin/business_reports.php">Business Reports</a></li>
                            <li class="nav-item"><a class="nav-link <?=($address == 'service_provider_reports.php') ? 'active' : ''?>" href="../admin/service_provider_reports.php">Service Provider Reports</a></li>
                            <li class="nav-item"><a class="nav-link <?=($address == 'electronic_miscellaneous_reports.php') ? 'active' : ''?>" href="../admin/electronic_miscellaneous_reports.php">Electronic Miscellaneous Reports</a></li>
                            <!-- <li class="nav-item"><a class="nav-link <?=($address == 'enrollment_reports.php') ? 'active' : ''?>" href="../admin/enrollment_reports.php">Enrollment Reports</a></li> -->
                            <li class="nav-item"><a class="nav-link <?=($address == 'customer_summary_report.php') ? 'active' : ''?>" href="../admin/customer_summary_report.php">Customer Reports</a></li>
                            <li class="nav-item"><a class="nav-link <?=($address == 'student_mailing_list.php') ? 'active' : ''?>" href="../admin/student_mailing_list.php">Student Mailing List</a></li>
                            <li class="nav-item"><a class="nav-link <?=($address == 'total_open_liability.php') ? 'active' : ''?>" href="../admin/total_open_liability.php">Total Open Liability Since Last Activity</a></li>
                            <li class="nav-item"><a class="nav-link <?= ($address == 'active_account_balance_report.php') ? 'active' : '' ?>" href="../admin/active_account_balance_report.php">Active Account Balance Report</a></li>
                            <li class="nav-item"><a class="nav-link <?= ($address == 'cash_report.php') ? 'active' : '' ?>" href="../admin/cash_report.php">Cash Report</a></li>
                        </ul>
                    <?php } ?>
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
                                    <h3 class="card-title" style="padding-top:15px; text-align: center; font-weight: bold"><?=$title?></h3>
                                </div>
                                <div class="btn col-4" >
                                    <form action="generate_excel.php" method="post" >
                                        <button  type="submit" id="export-to-excel" name="ExportType"
                                                value="Export to Excel" class="btn btn-info">Export
                                            to Excel</button>
                                    </form>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table id="myTable" class="table table-bordered" data-page-length='50'>
                                    <thead>
                                    <tr>
                                        <th style="width:10%; text-align: center; vertical-align:auto; font-weight: bold" >Last Name</th>
                                        <th style="width:10%; text-align: center; font-weight: bold" >First Name</th>
                                        <th style="width:15%; text-align: center; font-weight: bold" >Partner Name</th>
                                        <th style="width:25%; text-align: center; font-weight: bold" >Address</th>
                                        <th style="width:15%; text-align: center; font-weight: bold" >City</th>
                                        <th style="width:5%; text-align: center; font-weight: bold" >State</th>
                                        <th style="width:5%; text-align: center; font-weight: bold" >Zip</th>
                                        <th style="width:15%; text-align: center; font-weight: bold" >Email Address</th>
                                        <th style="width:5%; text-align: center; font-weight: bold" >Status</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    $i=1;
                                    $row = $db->Execute("SELECT DOA_USERS.LAST_NAME, DOA_USERS.FIRST_NAME, CONCAT(DOA_CUSTOMER_DETAILS.PARTNER_FIRST_NAME, ' ', DOA_CUSTOMER_DETAILS.PARTNER_LAST_NAME) AS PARTNER_NAME, DOA_USER_PROFILE.ADDRESS, DOA_USER_PROFILE.CITY, DOA_STATES.STATE_NAME, DOA_USER_PROFILE.ZIP, DOA_CUSTOMER_DETAILS.EMAIL, DOA_USER_PROFILE.ACTIVE FROM DOA_USERS INNER JOIN DOA_USER_PROFILE ON DOA_USERS.PK_USER=DOA_USER_PROFILE.PK_USER INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER=DOA_USER_MASTER.PK_USER LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER INNER JOIN DOA_CUSTOMER_DETAILS ON DOA_CUSTOMER_DETAILS.PK_USER_MASTER=DOA_USER_MASTER.PK_USER_MASTER INNER JOIN DOA_STATES ON DOA_USER_PROFILE.PK_STATES=DOA_STATES.PK_STATES WHERE DOA_USER_ROLES.PK_ROLES = 4");
                                    if ($row->fields['ACTIVE']==1) {
                                        $STATUS = "Active";
                                    }
                                    while (!$row->EOF) {
                                        ?>
                                        <tr>
                                            <td ><?=$row->fields['LAST_NAME']?></td>
                                            <td ><?=$row->fields['FIRST_NAME']?></td>
                                            <td ><?=$row->fields['PARTNER_NAME']?></td>
                                            <td ><?=$row->fields['ADDRESS']?></td>
                                            <td ><?=$row->fields['CITY']?></td>
                                            <td style="text-align: center"><?=$row->fields['STATE_NAME']?></td>
                                            <td style="text-align: center"><?=$row->fields['ZIP']?></td>
                                            <td ><?=$row->fields['EMAIL']?></td>
                                            <td style="text-align: center"><?=$STATUS?></td>
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
