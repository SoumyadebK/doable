<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];

$title = "All Products";

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

$header_text = '';
$header_data = $db->Execute("SELECT * FROM `DOA_HEADER_TEXT` WHERE ACTIVE = 1 AND HEADER_TITLE = 'Services page'");
if ($header_data->RecordCount() > 0) {
    $header_text = $header_data->fields['HEADER_TEXT'];
}

?>

<!DOCTYPE html>
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
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
                <div class="col-md-4 align-self-center">
                    <h4 class="text-themecolor"><?=$title?></h4>
                </div>
                <?php if ($status_check=='inactive') { ?>
                    <div class="col-md-3 align-self-center">
                        <button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='all_products.php?status=active'"><i class="fa fa-user"></i> Show Active</button>
                    </div>
                <?php } elseif ($status_check=='active') { ?>
                    <div class="col-md-3 align-self-center">
                        <button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='all_products.php?status=inactive'"><i class="fa fa-user-times"></i> Show Not Active</button>
                    </div>
                <?php } ?>
                <div class="col-md-5 align-self-center text-end">
                    <div class="d-flex justify-content-end align-items-center">
                        <ol class="breadcrumb justify-content-end">
                            <li class="breadcrumb-item"><a href="setup.php">Setup</a></li>
                            <li class="breadcrumb-item active"><?=$title?></li>
                        </ol>
                        <button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='product.php'" ><i class="fa fa-plus-circle"></i> Create New</button>
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
                                        <th>Product ID</th>
                                        <th>Product Name</th>
                                        <th>Product Description</th>
                                        <th>Price</th>
                                        <th>Shipping Information</th>
                                        <th>Action</th>
                                    </tr>
                                    </thead>

                                    <tbody>
                                    <?php
                                    $i=1;
                                    $row = $db_account->Execute("SELECT * FROM DOA_PRODUCT WHERE IS_DELETED = 0 AND ACTIVE = '$status' ORDER BY PRODUCT_NAME ASC");
                                    while (!$row->EOF) { ?>
                                        <tr>
                                            <td onclick="editpage(<?=$row->fields['PK_PRODUCT']?>);"><?=$row->fields['PRODUCT_ID']?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_PRODUCT']?>);"><?=$row->fields['PRODUCT_NAME']?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_PRODUCT']?>);"><?=$row->fields['PRODUCT_DESCRIPTION']?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_PRODUCT']?>);"><?=$row->fields['PRICE']?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_PRODUCT']?>);"><?=$row->fields['SHIPPING_INFORMATION']?></td>
                                            </td>

                                            <td>
                                                <a href="product.php?id=<?=$row->fields['PK_PRODUCT']?>"><i class="fa fa-edit" title="Edit"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                <a href="all_products.php?type=del&id=<?=$row->fields['PK_PRODUCT']?>" onclick='ConfirmDelete(<?=$row->fields['PK_PRODUCT']?>);'><i class="fa fa-trash" title="Delete"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
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
    function ConfirmDelete(PK_PRODUCT)
    {
        var conf = confirm("Are you sure you want to delete?");
        if(conf) {
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                data: {FUNCTION_NAME: 'deleteProductData', PK_PRODUCT: PK_PRODUCT},
                success: function (data) {
                    window.location.href = `all_products.php`;
                }
            });
        }
    }
    function editpage(id){
        window.location.href = "product.php?id="+id;
    }
</script>
</body>
</html>