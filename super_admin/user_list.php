<?php
require_once('../global/config.php');
$title = "User List";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 1 ){
    header("location:../login.php");
    exit;
}

$PK_LOCATION = $_GET['id'];

if (!empty($_GET['cond']) && $_GET['cond'] == 'del'){
    $db->Execute("DELETE FROM `DOA_USERS` WHERE `PK_USER` = ".$_GET['PK_USER']);
    header('location:user_list.php?id='.$PK_LOCATION);
}

$location_name = $db->Execute("SELECT * FROM `DOA_LOCATION` WHERE PK_LOCATION = '$PK_LOCATION'");

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
        <?php require_once('../includes/setup_menu_super_admin.php') ?>
        <div class="container-fluid body_content m-0">
            <div class="row page-titles">
                <div class="col-md-5 align-self-center">
                    <h4 class="text-themecolor"><?=$title?> for <?=$location_name->fields['LOCATION_NAME']?></h4>
                </div>
                <div class="col-md-7 align-self-center text-end">
                    <div class="d-flex justify-content-end align-items-center">
                        <ol class="breadcrumb justify-content-end">
                            <li class="breadcrumb-item"><a href="all_accounts.php">All Accounts</a></li>
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
                                <table id="myTable" class="table table-striped border">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Name</th>
                                            <th>Username</th>
                                            <th>Roles</th>
                                            <th>Email Id</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                    <?php
                                    $i=1;
                                    $row = $db->Execute("SELECT DISTINCT (DOA_USERS.PK_USER), CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.ACTIVE FROM DOA_USERS LEFT JOIN DOA_USER_LOCATION ON  DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER WHERE DOA_USER_ROLES.PK_ROLES IN(2,3,5,6,7,8) AND DOA_USER_LOCATION.PK_LOCATION = '$PK_LOCATION' ORDER BY DOA_USERS.ACTIVE DESC, DOA_USERS.FIRST_NAME ASC");
                                    while (!$row->EOF) {
                                        $selected_roles = [];
                                        if(!empty($row->fields['PK_USER'])) {
                                            $PK_USER = $row->fields['PK_USER'];
                                            $selected_roles_row = $db->Execute("SELECT DOA_ROLES.ROLES FROM `DOA_USER_ROLES` LEFT JOIN DOA_ROLES ON DOA_USER_ROLES.PK_ROLES = DOA_ROLES.PK_ROLES WHERE `PK_USER` = '$PK_USER'");
                                            while (!$selected_roles_row->EOF) {
                                                $selected_roles[] = $selected_roles_row->fields['ROLES'];
                                                $selected_roles_row->MoveNext();
                                            }
                                        } ?>
                                        <tr>
                                            <td onclick="editpage(<?=$row->fields['PK_USER']?>, <?=$_GET['id']?>);"><?=$i;?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_USER']?>, <?=$_GET['id']?>);"><?=$row->fields['NAME']?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_USER']?>, <?=$_GET['id']?>);"><?=$row->fields['USER_NAME']?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_USER']?>, <?=$_GET['id']?>);"><?=implode(', ', $selected_roles)?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_USER']?>, <?=$_GET['id']?>);"><?=$row->fields['EMAIL_ID']?></td>
                                            <td style="padding: 10px 0px 0px 0px;font-size: 20px;">
                                                <a href="edit_account_user.php?id=<?=$row->fields['PK_USER']?>&ac_id=<?=$_GET['id']?>" title="Reset Password" style="color: #03a9f3;"><i class="ti-lock"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;
                                                <?php if($row->fields['ACTIVE']==1){ ?>
                                                    <span title="Active" class="active-box-green"></span>
                                                <?php } else{ ?>
                                                    <span title="Inactive" class="active-box-red"></span>
                                                <?php } ?>&nbsp;&nbsp;
                                                <a href="javascript:;" data-href="user_list.php?id=<?=$_GET['id']?>&PK_USER=<?=$row->fields['PK_USER']?>&cond=del" onclick="confirmDelete(this);" title="Delete" style="color: red;"><i class="ti-trash"></i></a>
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

    function editpage(PK_USER, AC_ID){
        window.location.href = "edit_account_user.php?id="+PK_USER+"&ac_id="+AC_ID;
    }

    function confirmDelete(anchor)
    {
        let conf = confirm("Are you sure you want to delete?");
        if(conf)
            window.location=$(anchor).data("href");
    }
</script>
</body>
</html>