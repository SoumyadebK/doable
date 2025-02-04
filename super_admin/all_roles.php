<?php
require_once('../global/config.php');
$title = "All Roles";

$status_check = empty($_GET['status'])?'active':$_GET['status'];

if ($status_check == 'active'){
    $status = 1;
} elseif ($status_check == 'inactive') {
    $status = 0;
}

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 1 ){
    header("location:../login.php");
    exit;
}

$results_per_page = 100;

if (isset($_GET['search_text'])) {
    $search_text = $_GET['search_text'];
    $search = " AND (DOA_ROLES.ROLES LIKE '%".$search_text."%')";
} else {
    $search_text = '';
    $search = ' ';
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
        <?php require_once('../includes/setup_menu_super_admin.php') ?>
        <div class="container-fluid body_content m-0">
            <div class="row page-titles">
                <div class="col-md-5 align-self-center">
                    <h4 class="text-themecolor"><?=$title?></h4>
                </div>
                <div class="col-md-3 align-self-center text-end">
                    <form class="form-material form-horizontal" action="" method="get">
                        <input type="hidden" name="status" value="<?=$status_check?>" >
                        <div class="input-group">
                            <input class="form-control" type="text" name="search_text" placeholder="Search.." value="<?=$search_text?>">
                            <button class="btn btn-info waves-effect waves-light m-r-10 text-white input-group-btn m-b-1" type="submit"><i class="fa fa-search"></i></button>
                        </div>
                    </form>
                </div>
                <div class="col-md-4 align-self-center text-end">
                    <div class="d-flex justify-content-end align-items-center">
                        <ol class="breadcrumb justify-content-end">
                            <li class="breadcrumb-item"><a href="setup.php">Setup</a></li>
                            <li class="breadcrumb-item active"><?=$title?></li>
                        </ol>
                        <button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='add_roles.php'" ><i class="fa fa-plus-circle"></i> Add New Role</button>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped border">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Roles</th>
                                            <th>Permission</th>
                                            <th>Priority</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                    <?php
                                    $i=1;
                                    $row = $db->Execute("SELECT * FROM `DOA_ROLES` WHERE PK_ROLES > 0 ".$search);
                                    while (!$row->EOF) { ?>
                                        <tr>
                                            <td onclick="editpage(<?=$row->fields['PK_ROLES']?>);"><?=$i;?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_ROLES']?>);"><?=$row->fields['ROLES']?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_ROLES']?>);">
                                                <?php
                                                $permission_data = $db->Execute("SELECT DOA_PERMISSION.PERMISSION_NAME FROM DOA_ROLES_PERMISSION LEFT JOIN DOA_PERMISSION ON DOA_ROLES_PERMISSION.PK_PERMISSION = DOA_PERMISSION.PK_PERMISSION WHERE DOA_ROLES_PERMISSION.PK_ROLES = ".$row->fields['PK_ROLES']);
                                                while (!$permission_data->EOF) {
                                                    echo $permission_data->fields['PERMISSION_NAME']."<br>";
                                                    $permission_data->MoveNext();
                                                }
                                                ?>
                                            </td>
                                            <td onclick="editpage(<?=$row->fields['PK_ROLES']?>);"><?=$row->fields['SORT_ORDER']?></td>
                                            <td>
                                                <a href="add_roles.php?id=<?=$row->fields['PK_ROLES']?>"><img src="../assets/images/edit.png" title="Edit" style="padding-top:5px"></a>&nbsp;&nbsp;&nbsp;&nbsp;
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
    function editpage(id){
        window.location.href = "add_roles.php?id="+id;
    }
</script>
</body>
</html>