<?php
require_once('../global/config.php');

$title = "Document Library";

$status_check = empty($_GET['status'])?'active':$_GET['status'];

if ($status_check == 'active'){
    $status = 1;
} elseif ($status_check == 'inactive') {
    $status = 0;
}

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5]) ){
    header("location:../login.php");
    exit;
}

$header_text = '';
$header_data = $db->Execute("SELECT * FROM `DOA_HEADER_TEXT` WHERE ACTIVE = 1 AND HEADER_TITLE = 'Document Library page'");
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
                <div class="col-md-3 align-self-center">
                    <?php if ($status_check=='inactive') { ?>
                        <h4 class="text-themecolor">Not Active Document Library</h4>
                    <?php } elseif ($status_check=='active') { ?>
                        <h4 class="text-themecolor">Active Document Library</h4>
                    <?php } ?>
                </div>

                <?php if ($status_check=='inactive') { ?>
                    <div class="col-md-3 align-self-center">
                        <button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='all_document_library.php?status=active'"><i class="fa fa-user"></i> Show Active</button>
                    </div>
                <?php } elseif ($status_check=='active') { ?>
                    <div class="col-md-3 align-self-center">
                        <button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='all_document_library.php?status=inactive'"><i class="fa fa-user-times"></i> Show Not Active</button>
                    </div>
                <?php } ?>

                <div class="col-md-6 align-self-center text-end">
                    <div class="d-flex justify-content-end align-items-center">
                        <ol class="breadcrumb justify-content-end">
                            <li class="breadcrumb-item"><a href="setup.php">Setup</a></li>
                            <li class="breadcrumb-item active"><?=$title?></li>
                        </ol>
                        <button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='document_library.php'" ><i class="fa fa-plus-circle"></i> Create New</button>
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
                                <table id="myTable" class="table table-striped border" data-page-length='50'>
                                    <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Document Name</th>
                                        <th>Actions</th>
                                    </tr>
                                    </thead>

                                    <tbody>
                                    <?php
                                    $i=1;
                                    $row = $db_account->Execute("SELECT * FROM `DOA_DOCUMENT_LIBRARY` WHERE DOA_DOCUMENT_LIBRARY.ACTIVE = '$status' GROUP BY DOA_DOCUMENT_LIBRARY.DOCUMENT_NAME") ;
                                    while (!$row->EOF) { ?>
                                        <tr>
                                            <td onclick="editpage(<?=$row->fields['PK_DOCUMENT_LIBRARY']?>);"><?=$i;?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_DOCUMENT_LIBRARY']?>);"><?=$row->fields['DOCUMENT_NAME']?></td>
                                            <td>
                                                <a href="document_library.php?id=<?=$row->fields['PK_DOCUMENT_LIBRARY']?>"><img src="../assets/images/edit.png" title="Edit" style="padding-top:5px"></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                <a href="all_document_library.php?type=del&id=<?=$row->fields['PK_DOCUMENT_LIBRARY']?>" onclick='javascript:ConfirmDelete(<?=$row->fields['PK_DOCUMENT_LIBRARY']?>);return false;'><img src="../assets/images/delete.png" title="Delete" style="padding-top:3px"></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                <?php if($row->fields['ACTIVE']==1) { ?>
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
    function ConfirmDelete(PK_DOCUMENT_LIBRARY)
    {
        var conf = confirm("Are you sure you want to delete?");
        if(conf) {
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                data: {FUNCTION_NAME: 'deleteDocumentLibraryData', PK_DOCUMENT_LIBRARY: PK_DOCUMENT_LIBRARY},
                success: function (data) {
                    window.location.href = `all_document_library.php`;
                }
            });
        }
    }
    function editpage(id){
        //alert(i);
        window.location.href = "document_library.php?id="+id;
    }
</script>
</body>
</html>