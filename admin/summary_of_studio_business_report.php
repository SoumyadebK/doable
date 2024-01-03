<?php
require_once('../global/config.php');
$title = "SUMMARY OF STUDIO BUSINESS REPORT";

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
                                        <th style="width:40%; text-align: center; vertical-align:auto; font-weight: bold">Arthur Murray Woodland Hills</th>
                                        <th style="width:20%; text-align: center; font-weight: bold">12/10/2023 - 12/16/2023</th>
                                        <th style="width:20%; text-align: center; font-weight: bold">Week # :50</th>
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
                                        <th style="width:25%; text-align: center; vertical-align:auto; font-weight: bold">Week</th>
                                        <th style="width:25%; text-align: center; font-weight: bold"></th>
                                        <th style="width:25%; text-align: center; font-weight: bold"></th>
                                        <th style="width:25%; text-align: center; font-weight: bold"></th>
                                    </tr>
                                    <tr>
                                        <th style="width:25%; text-align: center; vertical-align:auto; font-weight: bold">Week Refunds</th>
                                        <th style="width:25%; text-align: center; font-weight: bold"></th>
                                        <th style="width:25%; text-align: center; font-weight: bold"></th>
                                        <th style="width:25%; text-align: center; font-weight: bold"></th>
                                    </tr>
                                    <tr>
                                        <th style="width:25%; text-align: center; vertical-align:auto; font-weight: bold">Transfer out</th>
                                        <th style="width:25%; text-align: center; font-weight: bold"></th>
                                        <th style="width:25%; text-align: center; font-weight: bold"></th>
                                        <th style="width:25%; text-align: center; font-weight: bold"></th>
                                    </tr>
                                    <tr>
                                        <th style="width:25%; text-align: center; vertical-align:auto; font-weight: bold">NET Y.T.D.</th>
                                        <th style="width:25%; text-align: center; font-weight: bold"></th>
                                        <th style="width:25%; text-align: center; font-weight: bold"></th>
                                        <th style="width:25%; text-align: center; font-weight: bold"></th>
                                    </tr>
                                    <tr>
                                        <th style="width:25%; text-align: center; vertical-align:auto; font-weight: bold">PRV. Y.T.D.</th>
                                        <th style="width:25%; text-align: center; font-weight: bold"></th>
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
                                        <th style="width:10%; text-align: center; font-weight: bold">S: 4</th>
                                        <th style="width:9%; text-align: center; font-weight: bold">T: 4</th>
                                        <th style="width:10%; text-align: center; font-weight: bold">S: 4</th>
                                        <th style="width:9%; text-align: center; font-weight: bold">T: 4</th>
                                        <th style="width:10%; text-align: center; font-weight: bold">S: 4</th>
                                        <th style="width:9%; text-align: center; font-weight: bold">T: 4</th>
                                        <th style="width:10%; text-align: center; font-weight: bold">S: 4</th>
                                        <th style="width:9%; text-align: center; font-weight: bold">T: 4</th>
                                        <th style="width:10%; text-align: center; font-weight: bold">S: 4</th>
                                    </tr>
                                    <tr>
                                        <th style="width:25%; text-align: center; vertical-align:auto; font-weight: bold">Week Refunds</th>
                                        <th style="width:25%; text-align: center; font-weight: bold"></th>
                                        <th style="width:25%; text-align: center; font-weight: bold"></th>
                                        <th style="width:25%; text-align: center; font-weight: bold"></th>
                                    </tr>
                                    <tr>
                                        <th style="width:25%; text-align: center; vertical-align:auto; font-weight: bold">Transfer out</th>
                                        <th style="width:25%; text-align: center; font-weight: bold"></th>
                                        <th style="width:25%; text-align: center; font-weight: bold"></th>
                                        <th style="width:25%; text-align: center; font-weight: bold"></th>
                                    </tr>
                                    <tr>
                                        <th style="width:25%; text-align: center; vertical-align:auto; font-weight: bold">NET Y.T.D.</th>
                                        <th style="width:25%; text-align: center; font-weight: bold"></th>
                                        <th style="width:25%; text-align: center; font-weight: bold"></th>
                                        <th style="width:25%; text-align: center; font-weight: bold"></th>
                                    </tr>
                                    <tr>
                                        <th style="width:25%; text-align: center; vertical-align:auto; font-weight: bold">PRV. Y.T.D.</th>
                                        <th style="width:25%; text-align: center; font-weight: bold"></th>
                                        <th style="width:25%; text-align: center; font-weight: bold"></th>
                                        <th style="width:25%; text-align: center; font-weight: bold"></th>
                                    </tr>
                                    </thead>
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
