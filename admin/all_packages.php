<?php
require_once('../global/config.php');
$title = "All Packages";

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
        <div class="container-fluid body_content">
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
                        <button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='package.php'" ><i class="fa fa-plus-circle"></i> Create New</button>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="myTable" class="table table-striped border" data-page-length="50">
                                    <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Package Name</th>
                                        <!--<th>Service Name</th>
                                        <th>Service Details</th>
                                        <th>Service Code</th>
                                        <th>Number of Sessions</th>
                                        <th>Price Per Sessions</th>
                                        <th>Total</th>-->
                                        <th>Action</th>
                                    </tr>
                                    </thead>

                                    <tbody>
                                    <?php
                                    $i=1;
                                    $row = $db_account->Execute("SELECT * FROM DOA_PACKAGE WHERE IS_DELETED = 0");
                                    while (!$row->EOF) { ?>
                                        <tr>
                                            <td onclick="editpage(<?=$row->fields['PK_PACKAGE']?>);"><?=$i;?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_PACKAGE']?>);"><?=$row->fields['PACKAGE_NAME']?></td>
                                           <!-- <td onclick="editpage(<?php /*=$row->fields['PK_PACKAGE_SERVICE']*/?>);"><?php /*=$row->fields['SERVICE_NAME']*/?></td>
                                            <td onclick="editpage(<?php /*=$row->fields['PK_PACKAGE_SERVICE']*/?>);"><?php /*=$row->fields['DESCRIPTION']*/?></td>
                                            <td onclick="editpage(<?php /*=$row->fields['PK_PACKAGE_SERVICE']*/?>);"><?php /*=$row->fields['SERVICE_CODE']*/?></td>
                                            <td onclick="editpage(<?php /*=$row->fields['PK_PACKAGE_SERVICE']*/?>);"><?php /*=$row->fields['NUMBER_OF_SESSION']*/?></td>
                                            <td onclick="editpage(<?php /*=$row->fields['PK_PACKAGE_SERVICE']*/?>);"><?php /*=$row->fields['PRICE_PER_SESSION']*/?></td>
                                            <td onclick="editpage(<?php /*=$row->fields['PK_PACKAGE_SERVICE']*/?>);"><?php /*=$row->fields['TOTAL']*/?></td>-->
                                            <td>
                                                <a href="package.php?id=<?=$row->fields['PK_PACKAGE']?>"><img src="../assets/images/edit.png" title="Edit" style="padding-top:5px"></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                <a href="all_packages.php?type=del&id=<?=$row->fields['PK_PACKAGE']?>" onclick='javascript:ConfirmDelete(<?=$row->fields['PK_PACKAGE']?>);return false;'><img src="../assets/images/delete.png" title="Delete" style="padding-top:3px"></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
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
    function ConfirmDelete(PK_PACKAGE)
    {
        var conf = confirm("Are you sure you want to delete?");
        if(conf) {
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                data: {FUNCTION_NAME: 'deletePackageData', PK_PACKAGE: PK_PACKAGE},
                success: function (data) {
                    window.location.href = `all_packages.php`;
                }
            });
        }
    }
    function editpage(id){
        window.location.href = "package.php?id="+id;
    }
</script>
</body>
</html>