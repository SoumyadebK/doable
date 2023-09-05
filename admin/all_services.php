<?php
require_once('../global/config.php');
$title = "All Services";

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
                        <button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='service.php'" ><i class="fa fa-plus-circle"></i> Create New</button>
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
                                        <th>Service Name</th>
                                        <th>Description</th>
                                        <th>Upload Documents</th>
                                        <th>Actions</th>
                                    </tr>
                                    </thead>

                                    <tbody>
                                    <?php
                                    $i=1;
                                    $row = $db_account->Execute("SELECT DISTINCT DOA_SERVICE_MASTER.PK_SERVICE_MASTER, DOA_SERVICE_MASTER.SERVICE_NAME, DOA_SERVICE_MASTER.DESCRIPTION, DOA_SERVICE_MASTER.ACTIVE FROM `DOA_SERVICE_MASTER` JOIN DOA_SERVICE_LOCATION ON DOA_SERVICE_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_LOCATION.PK_SERVICE_MASTER WHERE DOA_SERVICE_LOCATION.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND IS_DELETED=0 AND PK_ACCOUNT_MASTER='$_SESSION[PK_ACCOUNT_MASTER]'");
                                    while (!$row->EOF) { ?>
                                        <tr>
                                            <td onclick="editpage(<?=$row->fields['PK_SERVICE_MASTER']?>);"><?=$i;?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_SERVICE_MASTER']?>);"><?=$row->fields['SERVICE_NAME']?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_SERVICE_MASTER']?>);"><?=$row->fields['DESCRIPTION']?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_SERVICE_MASTER']?>);">
                                                <?php
                                                    $doc_row = $db_account->Execute("SELECT PK_SERVICE_DOCUMENTS FROM `DOA_SERVICE_DOCUMENTS` WHERE PK_SERVICE_MASTER = ".$row->fields['PK_SERVICE_MASTER']);
                                                    $doc_count = $doc_row->RecordCount();
                                                ?>
                                                <i class="fas fa-upload"></i> (<?=$doc_count;?>)
                                            </td>

                                            <td>
                                                <a href="service.php?id=<?=$row->fields['PK_SERVICE_MASTER']?>"><img src="../assets/images/edit.png" title="Edit" style="padding-top:5px"></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                <a href="all_services.php?type=del&id=<?=$row->fields['PK_SERVICE_MASTER']?>" onclick='javascript:ConfirmDelete(<?=$row->fields['PK_SERVICE_MASTER']?>);return false;'><img src="../assets/images/delete.png" title="Delete" style="padding-top:3px"></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
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
    function ConfirmDelete(PK_SERVICE_MASTER)
    {
        var conf = confirm("Are you sure you want to delete?");
        if(conf) {
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                data: {FUNCTION_NAME: 'deleteServiceData', PK_SERVICE_MASTER: PK_SERVICE_MASTER},
                success: function (data) {
                    window.location.href = `all_services.php`;
                }
            });
        }
    }
    function editpage(id){
        window.location.href = "service.php?id="+id;
    }
</script>
</body>
</html>