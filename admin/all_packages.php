<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;

$title = "All Packages";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 2 ){
    header("location:../login.php");
    exit;
}

$header_text = '';
$header_data = $db->Execute("SELECT * FROM `DOA_HEADER_TEXT` WHERE ACTIVE = 1 AND HEADER_TITLE = 'Packages page'");
if ($header_data->RecordCount() > 0) {
    $header_text = $header_data->fields['HEADER_TEXT'];
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
                        <button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='package.php'" ><i class="fa fa-plus-circle"></i> Create New</button>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row" style="text-align: center;">
                                <h5 style="font-weight: bold;"><?=$header_text?></h5>
                            </div>
                            <div class="table-responsive">
                                <table id="myTable" class="table table-striped border" data-page-length="50">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Package Name</th>
                                            <th>Description</th>
                                            <th>Sort Order</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                    <?php
                                    $i=1;
                                    $row = $db_account->Execute("SELECT * FROM DOA_PACKAGE WHERE IS_DELETED = 0 ORDER BY CASE WHEN SORT_ORDER IS NULL THEN 1 ELSE 0 END, SORT_ORDER ASC");
                                    while (!$row->EOF) {
                                        $serviceCodeData = $db_account->Execute("SELECT DOA_SERVICE_CODE.SERVICE_CODE, DOA_PACKAGE_SERVICE.NUMBER_OF_SESSION FROM DOA_SERVICE_CODE JOIN DOA_PACKAGE_SERVICE ON DOA_PACKAGE_SERVICE.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE WHERE DOA_PACKAGE_SERVICE.PK_PACKAGE = ".$row->fields['PK_PACKAGE']);
                                        $serviceCode = [];
                                        while (!$serviceCodeData->EOF) {
                                            $serviceCode[] = $serviceCodeData->fields['SERVICE_CODE'].': '.$serviceCodeData->fields['NUMBER_OF_SESSION'];
                                            $serviceCodeData->MoveNext();
                                        } ?>
                                        <tr>
                                            <td onclick="editpage(<?=$row->fields['PK_PACKAGE']?>);"><?=$i;?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_PACKAGE']?>);"><?=$row->fields['PACKAGE_NAME']?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_PACKAGE']?>);"><?=implode(', ', $serviceCode)?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_PACKAGE']?>);"><?=$row->fields['SORT_ORDER']?></td>
                                            <td>
                                                <a href="package.php?id=<?=$row->fields['PK_PACKAGE']?>"><i class="fa fa-edit" title="Edit"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                <a href="all_packages.php?type=del&id=<?=$row->fields['PK_PACKAGE']?>" onclick='ConfirmDelete(<?=$row->fields['PK_PACKAGE']?>);'><i class="fa fa-trash" title="Delete"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
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