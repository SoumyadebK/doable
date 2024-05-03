<?php
require_once('../global/config.php');
$title = "All Scheduling Codes";

$status_check = empty($_GET['status'])?'active':$_GET['status'];

if ($status_check == 'active'){
    $status = 1;
} elseif ($status_check == 'inactive') {
    $status = 0;
}

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 2 ){
    header("location:../login.php");
    exit;
}

$FUNCTION_NAME = isset($_POST['FUNCTION_NAME']) ? $_POST['FUNCTION_NAME'] : '';
if(!empty($_POST) && $FUNCTION_NAME == 'saveSortOrder'){
    $db_account->Execute("UPDATE DOA_SCHEDULING_CODE SET SORT_ORDER=".$_POST['ORDER_NUMBER']." WHERE PK_SCHEDULING_CODE=".$_POST['PK_SCHEDULING_CODE']);
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
                    <?php if ($status_check=='inactive') { ?>
                        <h4 class="text-themecolor">Not Active Scheduling Codes</h4>
                    <?php } elseif ($status_check=='active') { ?>
                        <h4 class="text-themecolor">Active Scheduling Codes</h4>
                    <?php } ?>
                </div>
                <?php if ($status_check=='inactive') { ?>
                    <div class="col-md-3" >
                        <button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='all_scheduling_codes.php?status=active'"><i class="fa fa-user"></i> Show Active</button>
                    </div>
                <?php } elseif ($status_check=='active') { ?>
                    <div class="col-md-3" >
                        <button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='all_scheduling_codes.php?status=inactive'"><i class="fa fa-user-times"></i> Show Not Active</button>
                    </div>
                <?php } ?>
                <div class="col-md-4 align-self-center text-end">
                    <div class="d-flex justify-content-end align-items-center">
                        <ol class="breadcrumb justify-content-end">
                            <li class="breadcrumb-item"><a href="setup.php">Setup</a></li>
                            <li class="breadcrumb-item active"><?=$title?></li>
                        </ol>
                        <button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='add_scheduling_codes.php'" ><i class="fa fa-plus-circle"></i> Create New</button>
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
                                        <th>Scheduling code</th>
                                        <th>Scheduling Name</th>
                                        <th>Color</th>
                                        <th>Sort Order</th>
                                        <th>Action</th>
                                    </tr>
                                    </thead>

                                    <tbody>
                                    <?php
                                    $i=1;
                                    $row = $db_account->Execute("SELECT DISTINCT DOA_SCHEDULING_CODE.PK_SCHEDULING_CODE, DOA_SCHEDULING_CODE.SCHEDULING_CODE, DOA_SCHEDULING_CODE.SCHEDULING_NAME, DOA_SCHEDULING_CODE.COLOR_CODE, DOA_SCHEDULING_CODE.SORT_ORDER, DOA_SCHEDULING_CODE.ACTIVE FROM `DOA_SCHEDULING_CODE` LEFT JOIN DOA_SCHEDULING_SERVICE ON DOA_SCHEDULING_CODE.PK_SCHEDULING_CODE=DOA_SCHEDULING_SERVICE.PK_SCHEDULING_CODE WHERE DOA_SCHEDULING_CODE.ACTIVE =".$status." AND DOA_SCHEDULING_CODE.PK_ACCOUNT_MASTER=".$_SESSION['PK_ACCOUNT_MASTER']. " ORDER BY CASE WHEN DOA_SCHEDULING_CODE.SORT_ORDER IS NULL THEN 1 ELSE 0 END, DOA_SCHEDULING_CODE.SORT_ORDER");
                                    while (!$row->EOF) { ?>
                                        <tr>
                                            <td onclick="editpage(<?=$row->fields['PK_SCHEDULING_CODE']?>);"><?=$row->fields['SCHEDULING_CODE']?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_SCHEDULING_CODE']?>);"><?=$row->fields['SCHEDULING_NAME']?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_SCHEDULING_CODE']?>);"><span style="display: block; width: 44px; height: 22px; background-color: <?=$row->fields['COLOR_CODE']?>"></span></td>
                                            <td><?=$row->fields['SORT_ORDER']?>
                                                <a href="javascript:" class="btn btn-info waves-effect waves-light m-r-10 text-white myBtn" onclick="editSortOrder(<?=$row->fields['PK_SCHEDULING_CODE']?>, <?=$row->fields['SORT_ORDER']?>);" style="float: right">Set Order</a>
                                            </td>
                                            <td>
                                                <a href="add_scheduling_codes.php?id=<?=$row->fields['PK_SCHEDULING_CODE']?>"><img src="../assets/images/edit.png" title="Edit" style="padding-top:5px"></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
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

                                <div class="modal fade" id="sort_order_modal" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <form id="sort_order_form" role="form" action="all_scheduling_codes.php" method="post">
                                            <div class="modal-content" style="width: 50%; margin: 15% auto;">
                                                <div class="modal-header">
                                                    <h4><b>Sort Order</b></h4>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="FUNCTION_NAME" value="saveSortOrder">
                                                    <input type="hidden" name="PK_SCHEDULING_CODE" id="PK_SCHEDULING_CODE" class="PK_SCHEDULING_CODE">
                                                    <div class="p-20">
                                                        <div class="row">
                                                            <div class="col-6">
                                                                <div class="form-group">
                                                                    <label class="form-label">Set Order</label>
                                                                    <div class="col-md-12">
                                                                        <input type="text" name="ORDER_NUMBER" id="ORDER_NUMBER" class="form-control">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    <button type="submit" id="card-button" class="btn btn-info waves-effect waves-light m-r-10 text-white" style="float: right;">Save</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>

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
    $(document).ready(function() {
        $('#myTable').DataTable({
            "order": false
        });
    });

    function ConfirmDelete(anchor)
    {
        var conf = confirm("Are you sure you want to delete?");
        if(conf)
            window.location=anchor.attr("href");
    }

    function editpage(id){
        //alert(i);
        window.location.href = "add_scheduling_codes.php?id="+id;
    }

    function editSortOrder(PK_SCHEDULING_CODE, SORT_ORDER) {
        $('#PK_SCHEDULING_CODE').val(PK_SCHEDULING_CODE);
        $('#ORDER_NUMBER').val(SORT_ORDER);
        $('#sort_order_modal').modal('show');
    }
</script>
</body>
</html>