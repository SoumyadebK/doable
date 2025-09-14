<?php
require_once('../global/config.php');
$title = "Student Mailing List";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
} ?>

<!DOCTYPE html>
<html lang="en">
<?php require_once('../includes/header.php'); ?>

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">
        <?php require_once('../includes/top_menu.php'); ?>
        <div class="page-wrapper">
            <?php require_once('../includes/top_menu_bar.php') ?>
            <?php require_once('../includes/report_menu.php') ?>
            <div class="container-fluid" style="padding: 10px 20px 0 20px;">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-4">
                                        <img src="../assets/images/background/doable_logo.png" style="margin-bottom:15px;  height: 60px; width: auto;">
                                    </div>
                                    <div class="col-4">
                                        <h3 class="card-title" style="padding-top:15px; text-align: center; font-weight: bold"><?= $title ?></h3>
                                    </div>
                                    <div class="btn col-4">
                                        <form action="generate_excel.php" method="post">
                                            <button type="submit" id="export-to-excel" name="ExportType"
                                                value="Export to Excel" class="btn btn-info">Export
                                                to Excel</button>
                                        </form>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table id="myTable" class="table table-bordered" data-page-length='50'>
                                        <thead>
                                            <tr>
                                                <th style="width:10%; text-align: center; vertical-align:auto; font-weight: bold">Last Name</th>
                                                <th style="width:10%; text-align: center; font-weight: bold">First Name</th>
                                                <th style="width:15%; text-align: center; font-weight: bold">Partner Name</th>
                                                <th style="width:25%; text-align: center; font-weight: bold">Address</th>
                                                <th style="width:15%; text-align: center; font-weight: bold">City</th>
                                                <th style="width:5%; text-align: center; font-weight: bold">State</th>
                                                <th style="width:5%; text-align: center; font-weight: bold">Zip</th>
                                                <th style="width:15%; text-align: center; font-weight: bold">Email Address</th>
                                                <th style="width:5%; text-align: center; font-weight: bold">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $i = 1;
                                            $row = $db->Execute("SELECT DOA_USERS.LAST_NAME, DOA_USERS.FIRST_NAME, CONCAT(DOA_CUSTOMER_DETAILS.PARTNER_FIRST_NAME, ' ', DOA_CUSTOMER_DETAILS.PARTNER_LAST_NAME) AS PARTNER_NAME, DOA_USER_PROFILE.ADDRESS, DOA_USER_PROFILE.CITY, DOA_STATES.STATE_NAME, DOA_USER_PROFILE.ZIP, DOA_CUSTOMER_DETAILS.EMAIL, DOA_USER_PROFILE.ACTIVE FROM DOA_USERS INNER JOIN DOA_USER_PROFILE ON DOA_USERS.PK_USER=DOA_USER_PROFILE.PK_USER INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER=DOA_USER_MASTER.PK_USER LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER INNER JOIN DOA_CUSTOMER_DETAILS ON DOA_CUSTOMER_DETAILS.PK_USER_MASTER=DOA_USER_MASTER.PK_USER_MASTER INNER JOIN DOA_STATES ON DOA_USER_PROFILE.PK_STATES=DOA_STATES.PK_STATES WHERE DOA_USER_ROLES.PK_ROLES = 4");
                                            if ($row->fields['ACTIVE'] == 1) {
                                                $STATUS = "Active";
                                            }
                                            while (!$row->EOF) {
                                            ?>
                                                <tr>
                                                    <td><?= $row->fields['LAST_NAME'] ?></td>
                                                    <td><?= $row->fields['FIRST_NAME'] ?></td>
                                                    <td><?= $row->fields['PARTNER_NAME'] ?></td>
                                                    <td><?= $row->fields['ADDRESS'] ?></td>
                                                    <td><?= $row->fields['CITY'] ?></td>
                                                    <td style="text-align: center"><?= $row->fields['STATE_NAME'] ?></td>
                                                    <td style="text-align: center"><?= $row->fields['ZIP'] ?></td>
                                                    <td><?= $row->fields['EMAIL'] ?></td>
                                                    <td style="text-align: center"><?= $STATUS ?></td>
                                                </tr>
                                            <?php $row->MoveNext();
                                                $i++;
                                            } ?>
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

    <?php require_once('../includes/footer.php'); ?>

    <script>
        // $(function () {
        //     $('#myTable').DataTable({
        //         "columnDefs": [
        //             { "targets": [0,2,5], "searchable": false }
        //         ]
        //     });
        // });
        function ConfirmDelete(anchor) {
            let conf = confirm("Are you sure you want to delete?");
            if (conf)
                window.location = anchor.attr("href");
        }
        // function editpage(id, master_id){
        //     window.location.href = "customer.php?id="+id+"&master_id="+master_id;
        //
        // }
    </script>

</body>

</html>