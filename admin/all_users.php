<?php
require_once('../global/config.php');
$title = "All Account Users";

$status_check = empty($_GET['status'])?'active':$_GET['status'];

if ($status_check == 'active'){
    $status = 1;
} elseif ($status_check == 'inactive') {
    $status = 0;
}

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
                    <?php if ($status_check=='inactive') { ?>
                        <h4 class="text-themecolor">Not Active Users</h4>
                    <?php } elseif ($status_check=='active') { ?>
                        <h4 class="text-themecolor">Active Users</h4>
                    <?php } ?>
                </div>
                <?php if ($status_check=='inactive') { ?>
                    <div class="col-md-3" >
                        <button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='all_users.php?status=active'"><i class="fa fa-user"></i> Show Active</button>
                    </div>
                <?php } elseif ($status_check=='active') { ?>
                    <div class="col-md-3" >
                        <button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='all_users.php?status=inactive'"><i class="fa fa-user-times"></i> Show Not Active</button>
                    </div>
                <?php } ?>
                <div class="col-md-4 align-self-center text-end">
                    <div class="d-flex justify-content-end align-items-center">
                        <ol class="breadcrumb justify-content-end">
                            <li class="breadcrumb-item"><a href="setup.php">Setup</a></li>
                            <li class="breadcrumb-item active"><?=$title?></li>
                        </ol>
                        <button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='user.php'"><i class="fa fa-plus-circle"></i> Create New</button>
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
                                        <th>Name</th>
                                        <th>Username</th>
                                        <th>Roles</th>
                                        <th>Location</th>
                                        <th>Email Id</th>
                                        <th>Actions</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                        $i=1;
                                        $row = $db->Execute("SELECT DISTINCT (DOA_USERS.PK_USER), CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.ACTIVE FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER LEFT JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER LEFT JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER WHERE DOA_USER_LOCATION.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_USER_ROLES.PK_ROLES IN(2,3,5,6,7,8) AND DOA_USERS.ACTIVE = '$status' AND DOA_USERS.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER']);
                                        while (!$row->EOF) {
                                            $selected_roles = [];
                                            $selected_location = [];
                                            if(!empty($row->fields['PK_USER'])) {
                                                $PK_USER = $row->fields['PK_USER'];
                                                $selected_roles_row = $db->Execute("SELECT DOA_ROLES.ROLES FROM `DOA_USER_ROLES` LEFT JOIN DOA_ROLES ON DOA_USER_ROLES.PK_ROLES = DOA_ROLES.PK_ROLES WHERE `PK_USER` = '$PK_USER'");
                                                while (!$selected_roles_row->EOF) {
                                                    $selected_roles[] = $selected_roles_row->fields['ROLES'];
                                                    $selected_roles_row->MoveNext();
                                                }

                                                $selected_location_row = $db->Execute("SELECT DOA_LOCATION.LOCATION_NAME FROM `DOA_LOCATION` INNER JOIN DOA_USER_LOCATION ON DOA_USER_LOCATION.PK_LOCATION = DOA_LOCATION.PK_LOCATION WHERE DOA_USER_LOCATION.PK_USER = '$PK_USER'");
                                                while (!$selected_location_row->EOF) {
                                                    $selected_location[] = $selected_location_row->fields['LOCATION_NAME'];
                                                    $selected_location_row->MoveNext();
                                                }
                                            } ?>
                                        <tr>
                                            <td onclick="editpage(<?=$row->fields['PK_USER']?>);"><?=$i;?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_USER']?>);"><?=$row->fields['NAME']?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_USER']?>);"><?=$row->fields['USER_NAME']?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_USER']?>);"><?=implode(', ', $selected_roles)?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_USER']?>);"><?=implode(', ', $selected_location)?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_USER']?>);"><?=$row->fields['EMAIL_ID']?></td>
                                            <td>
                                                <a href="user.php?id=<?=$row->fields['PK_USER']?>"><img src="../assets/images/edit.png" title="Edit" style="padding-top:5px"></a>&nbsp;&nbsp;&nbsp;&nbsp;
                                                <?php if($row->fields['ACTIVE']==1){ ?>
                                                    <span class="active-box-green"></span>
                                                <?php } else{ ?>
                                                    <span class="active-box-red"></span>
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

<script>
    $(function () {
        $('#myTable').DataTable();
    });

    function ConfirmDelete(anchor)
    {
        let conf = confirm("Are you sure you want to delete?");
        if(conf)
            window.location=anchor.attr("href");
    }

    function editpage(id){
        window.location.href = "user.php?id="+id;
    }
</script>

</body>
</html>