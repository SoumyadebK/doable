<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];

$title = "Products Coming Soon";

$status_check = empty($_GET['status'])?'active':$_GET['status'];

if ($status_check == 'active'){
    $status = 1;
} elseif ($status_check == 'inactive') {
    $status = 0;
}

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 4 ){
    header("location:../login.php");
    exit;
}

$header_text = '';
$header_data = $db->Execute("SELECT * FROM `DOA_HEADER_TEXT` WHERE ACTIVE = 1 AND HEADER_TITLE = 'Products Page'");
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
        <div class="container-fluid body_content m-0">
            <div class="row page-titles">
                <div class="col-md-4 align-self-center">
                    <h4 class="text-themecolor"><?=$title?></h4>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row" style="text-align: center;">
                                <h5 style="font-weight: bold;"><?=$title?></h5>
                            </div>
                            <!--<div class="table-responsive">
                                <table id="myTable" class="table table-striped border" data-page-length="50">
                                    <thead>
                                    <tr>
                                        <th width="5%">Product ID</th>
                                        <th width="15%">Product Image</th>
                                        <th width="20%">Product Name</th>
                                        <th width="30%">Product Description</th>
                                        <th width="10%">Price</th>
                                        <th width="10%">Shipping Information</th>
                                        <th width="10%">Action</th>
                                    </tr>
                                    </thead>

                                    <tbody>
                                    <?php
/*                                    $i=1;
                                    $row = $db_account->Execute("SELECT * FROM DOA_PRODUCT WHERE IS_DELETED = 0 AND ACTIVE = 1 ORDER BY PRODUCT_NAME ASC");
                                    while (!$row->EOF) { */?>
                                        <tr>
                                            <td onclick="editpage(<?php /*=$row->fields['PK_PRODUCT']*/?>);"><?php /*=$row->fields['PRODUCT_ID']*/?></td>
                                            <td onclick="editpage(<?php /*=$row->fields['PK_PRODUCT']*/?>);"><img src="<?php /*=$row->fields['PRODUCT_IMAGES']*/?>" alt="<?php /*=$row->fields['PRODUCT_NAME']*/?>" style="width: 150px; height: auto;"></td>
                                            <td onclick="editpage(<?php /*=$row->fields['PK_PRODUCT']*/?>);"><?php /*=$row->fields['PRODUCT_NAME']*/?></td>
                                            <td onclick="editpage(<?php /*=$row->fields['PK_PRODUCT']*/?>);"><?php /*=$row->fields['PRODUCT_DESCRIPTION']*/?></td>
                                            <td onclick="editpage(<?php /*=$row->fields['PK_PRODUCT']*/?>);"><?php /*=$row->fields['PRICE']*/?></td>
                                            <td onclick="editpage(<?php /*=$row->fields['PK_PRODUCT']*/?>);"><?php /*=$row->fields['SHIPPING_INFORMATION']*/?></td>
                                            <td>
                                                <a href="javascript:" onclick="addToCart(<?php /*=$row->fields['PK_PRODUCT']*/?>);"><i class="fa fa-cart-plus" title="Add to Cart"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                            </td>
                                        </tr>
                                        <?php /*$row->MoveNext();
                                        $i++; } */?>
                                    </tbody>
                                </table>
                            </div>-->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!--Add To Cart Model-->
<div class="modal fade" id="add_to_cart" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" style="width: 500px;">
        <form id="add_to_cart_form"  method="post">
            <input type="hidden" name="FUNCTION_NAME" value="addToCart">
            <div class="modal-content">
                <div class="modal-header">
                    <h4><b>Add To Cart</b></h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="row" id="item_details">

                    </div>
                    <div class="number">
                        <span class="minus btn btn-info waves-effect waves-light text-white">-</span>
                        <input class="counter_input" inputmode="numeric" oninput="this.value = this.value.replace(/\D+/g, '')" id="PRODUCT_QUANTITY" name="PRODUCT_QUANTITY" value="1"/>
                        <span class="plus btn btn-info waves-effect waves-light text-white">+</span>
                    </div>
                    <!--<div class="form-group m-t-15">
                        <label class="form-label">Enter Quantity</label>
                        <input inputmode="numeric" oninput="this.value = this.value.replace(/\D+/g, '')" id="PRODUCT_QUANTITY" name="PRODUCT_QUANTITY" class="form-control only-number" placeholder="Quantity" required>
                    </div>-->
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" id="card-button" class="btn btn-info waves-effect waves-light m-r-10 text-white" style="float: right;">Add</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require_once('../includes/footer.php');?>
<script>
    $(function () {
        $('#myTable').DataTable();
    });

    function addToCart(PK_PRODUCT) {
        $('#add_to_cart_form')[0].reset();
        $.ajax({
            url: "../admin/ajax/get_product_details.php",
            type: 'GET',
            data: {PK_PRODUCT:PK_PRODUCT},
            success: function (data) {
                $('#item_details').html(data);
            }
        });
        $('#add_to_cart').modal('show');
    }

    $(document).on('submit', '#add_to_cart_form', function (event) {
        event.preventDefault();
        let form_data = new FormData($('#add_to_cart_form')[0]); //$('#document_form').serialize();
        $.ajax({
            url: "../admin/ajax/AjaxFunctionProductPurchase.php",
            type: 'POST',
            data: form_data,
            processData: false,
            contentType: false,
            success:function (data) {
                $('#cart_count').text(data);
                $('#add_to_cart').modal('hide');
            }
        });
    });
</script>
</body>
</html>