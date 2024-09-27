<?php
global $db;
global $db_account;

require_once('../global/config.php');
$title = "Deleted Customers";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 2 ){
    header("location:../login.php");
    exit;
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
        <?php require_once('../includes/setup_menu.php') ?>
        <div class="container-fluid body_content m-0">
            <div class="row page-titles">
                <div class="col-md-5 align-self-center">
                    <h4 class="text-themecolor"><?=$title?></h4>
                </div>
                <div class="col-md-7 align-self-center text-end">
                    <div class="d-flex justify-content-end align-items-center">
                        <ol class="breadcrumb justify-content-end">
                            <li class="breadcrumb-item"><a href="setup.php">Setup</a></li>
                            <li class="breadcrumb-item active"><?=$title?></li>
                        </ol>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="myTable" class="table table-striped border" data-page-length='50'>
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Customer ID</th>
                                            <th>Customer Name</th>
                                            <th>Deleted By</th>
                                            <th>Deleted On</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    $i=1;
                                    $row = $db->Execute("SELECT DISTINCT (DOA_USERS.PK_USER) AS PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_NAME, DOA_USERS.UNIQUE_ID, DOA_USERS.DELETED_ON, DOA_USERS.DELETED_BY FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER LEFT JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER LEFT JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER WHERE DOA_USER_LOCATION.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_USER_ROLES.PK_ROLES = 4 AND DOA_USERS.IS_DELETED = 1 AND DOA_USERS.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER']." ORDER BY DOA_USERS.FIRST_NAME ASC");
                                    while (!$row->EOF) {
                                        $deleted_by = $db->Execute("SELECT DISTINCT (PK_USER), CONCAT(FIRST_NAME, ' ', LAST_NAME) AS NAME FROM DOA_USERS WHERE PK_USER = ".$row->fields['DELETED_BY']);
                                        ?>
                                        <tr>
                                            <td><?=$i;?></td>
                                            <td><?=$row->fields['UNIQUE_ID']?></td>
                                            <td><?=$row->fields['NAME']?></td>
                                            <td><?=($deleted_by->RecordCount() > 0) ? $deleted_by->fields['NAME'] : ''?></td>
                                            <td>
                                                <?=($row->fields['DELETED_ON'] != null) ? date('m/d/Y - h:i a', strtotime($row->fields['DELETED_ON'])) : ''?>&nbsp;&nbsp;&nbsp;
                                                <?php if (date('Y-m-d', strtotime("+6 months", strtotime($row->fields['DELETED_ON']))) > date('Y-m-d')) { ?>
                                                    <a class="waves-dark" href="javascript:" onclick="reactiveThisCustomer(<?=$row->fields['PK_USER']?>)" aria-haspopup="true" aria-expanded="false" title="Retrieve"><i class="ti-reload"></i></a>
                                                <?php } ?>
                                            </td>
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

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11">
    import Swal from 'sweetalert2';
    const Swal = require('sweetalert2');
</script>

<script>
    $(function () {
        $('#myTable').DataTable();
    });

    function reactiveThisCustomer(PK_USER) {
        Swal.fire({
            title: "Are you sure?",
            text: "Re-active this profile will retrieve all data related to this person. Even previous numbers, reports, appointments and enrollments.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, retrieve it!"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "ajax/AjaxFunctions.php",
                    type: 'POST',
                    data: {FUNCTION_NAME: 'reactiveCustomer', PK_USER:PK_USER},
                    success: function (data) {
                        Swal.fire({
                            title: "Re-activated!",
                            text: "Your profile has been re-active.",
                            icon: "success",
                            //timer: 3000,
                        }).then((result) => {
                            window.location.reload();
                        });
                    }
                });
            } else {
                Swal.fire({
                    title: "Cancelled",
                    text: "Account is not active :)",
                    icon: "error"
                });
            }
        });
    }
</script>
</body>
</html>
