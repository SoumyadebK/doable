<?php
require_once('../global/config.php');
$title = "Login Log";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 1) {
    header("location:../login.php");
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<?php require_once('../includes/header.php'); ?>

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">
        <?php require_once('../includes/top_menu.php'); ?>
        <div class="page-wrapper">
            <?php require_once('../includes/top_menu_bar.php') ?>
            <?php require_once('../includes/setup_menu_super_admin.php') ?>
            <div class="container-fluid body_content m-0">
                <div class="row page-titles">
                    <div class="col-md-5 align-self-center">
                        <h4 class="text-themecolor"><?= $title ?></h4>
                    </div>
                    <div class="col-md-4 align-self-center text-end">
                        <div class="d-flex justify-content-end align-items-center">
                            <ol class="breadcrumb justify-content-end">
                                <li class="breadcrumb-item"><a href="setup.php">Setup</a></li>
                                <li class="breadcrumb-item active"><?= $title ?></li>
                            </ol>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped border" id="myTable">
                                        <thead>
                                            <tr>
                                                <th>User</th>
                                                <th>IP Address</th>
                                                <th>Login Time</th>
                                                <th>Total Login Attempts</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $row = $db->Execute("SELECT DOA_USERS.*, DOA_USER_AUTH_LOG.IP_ADDRESS, DOA_USER_AUTH_LOG.LOGIN_TIME, DOA_USER_AUTH_LOG.LOGIN_ATTEMPTS FROM DOA_USER_AUTH_LOG INNER JOIN DOA_USERS ON DOA_USERS.PK_USER = DOA_USER_AUTH_LOG.PK_USER WHERE DOA_USER_AUTH_LOG.IS_VERIFIED = 0");
                                            while (!$row->EOF) { ?>
                                                <tr>
                                                    <td><?= $row->fields['FIRST_NAME'] . " " . $row->fields['LAST_NAME'] ?> (<?= formatPhone($row->fields['PHONE']) ?>)</td>
                                                    <td><?= $row->fields['IP_ADDRESS'] ?></td>
                                                    <td><?= date('m/d/Y h:i A', strtotime($row->fields['LOGIN_TIME'])) ?></td>
                                                    <td><?= $row->fields['LOGIN_ATTEMPTS'] ?></td>
                                                </tr>
                                            <?php
                                                $row->MoveNext();
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
        $(function() {
            $('#myTable').DataTable();
        });
    </script>
</body>

</html>