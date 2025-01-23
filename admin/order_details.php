<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $upload_path;
global $master_database;

$title = "Order Details";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5]) ){
    header("location:../login.php");
    exit;
}

if(!empty($_POST)) {
    $ORDER_DATA['ORDER_TYPE'] = $_POST['ORDER_TYPE'];
    $ORDER_DATA['PK_ORDER_STATUS'] = $_POST['PK_ORDER_STATUS'];
    db_perform_account('DOA_ORDER', $ORDER_DATA, 'update', ' PK_ORDER = ' . $_POST['PK_ORDER']);
    header("location:all_orders.php");
}

$PK_ORDER = $_GET['id'];
$order_data = $db_account->Execute("SELECT DOA_ORDER.*, CONCAT(CUSTOMER.FIRST_NAME, ' ', CUSTOMER.LAST_NAME) AS CUSTOMER_NAME, DOA_COUNTRY.COUNTRY_NAME, DOA_STATES.STATE_NAME FROM `DOA_ORDER` LEFT JOIN $master_database.DOA_COUNTRY AS DOA_COUNTRY ON DOA_ORDER.PK_COUNTRY = DOA_COUNTRY.PK_COUNTRY LEFT JOIN $master_database.DOA_STATES AS DOA_STATES ON DOA_ORDER.PK_STATES = DOA_STATES.PK_STATES LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_ORDER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER LEFT JOIN $master_database.DOA_USERS AS CUSTOMER ON DOA_USER_MASTER.PK_USER = CUSTOMER.PK_USER WHERE DOA_ORDER.PK_ORDER = '$PK_ORDER'");

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
                <div class="col-md-4 align-self-center">
                    <h4 class="text-themecolor"><?=$title?></h4>
                </div>
                <div class="col-md-3"></div>
                <div class="col-md-5 align-self-center text-end">
                    <div class="d-flex justify-content-end align-items-center">
                        <ol class="breadcrumb justify-content-end">
                            <li class="breadcrumb-item"><a href="setup.php">Setup</a></li>
                            <li class="breadcrumb-item"><a href="all_orders.php">All Orders</a></li>
                            <li class="breadcrumb-item active"><?=$title?></li>
                        </ol>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body" style="width: 80%; margin: auto;">
                            <div class="row">
                                <div class="col-6">
                                    <h4>Customer : <?=$order_data->fields['CUSTOMER_NAME']?></h4>
                                </div>
                            </div>
                            <form id="order_details_form" class="form-material form-horizontal" action="" method="post" enctype="multipart/form-data">
                                <input type="hidden" name="PK_ORDER" value="<?=$PK_ORDER?>">
                                <div class="row m-t-20">
                                    <div class="col-4">
                                        <div class="form-group">
                                            <label class="col-md-12">Order Type</label>
                                            <div class="col-md-12">
                                                <label for="PICK_UP"><input type="radio" id="PICK_UP" name="ORDER_TYPE" class="form-check-inline charge_type" value="PICK_UP" <?=($order_data->fields['ORDER_TYPE']=='PICK_UP')?'checked':''?> onchange="changeOrderType(this);" checked>Pick Up</label>
                                                <label class="m-l-40" for="SHIPPING"><input type="radio" id="SHIPPING" name="ORDER_TYPE" class="form-check-inline charge_type" value="SHIPPING" <?=($order_data->fields['ORDER_TYPE']=='SHIPPING')?'checked':''?> onchange="changeOrderType(this);">Shipping</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="form-group">
                                            <label class="col-md-12">Order Status</label>
                                            <div class="col-md-12">
                                                <div class="col-sm-12" id="State_div">
                                                    <select class="form-control" name="PK_ORDER_STATUS" id="PK_ORDER_STATUS">
                                                        <?php
                                                        $row = $db->Execute("SELECT * FROM DOA_ORDER_STATUS");
                                                        while (!$row->EOF) { ?>
                                                            <option value="<?php echo $row->fields['PK_ORDER_STATUS'];?>" <?=($order_data->fields['PK_ORDER_STATUS']==$row->fields['PK_ORDER_STATUS'])?'selected':''?>><?=$row->fields['STATUS']?></option>
                                                        <?php $row->MoveNext(); } ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="form-group">
                                            <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white" style="float: right;">Update</button>
                                        </div>
                                    </div>
                                </div>
                            </form>

                            <div class="row m-t-30 shipping_div" style="display: <?=($order_data->fields['ORDER_TYPE']=='PICK_UP')?'none':''?>;">
                                <div class="row">
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label class="col-md-12" for="example-text" style="font-weight: bold; font-size: 16px">Shipping Information</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-4">
                                        <p>Address : <?=$order_data->fields['ADDRESS']?></p>
                                    </div>
                                    <div class="col-4">
                                        <p>Apt/Ste : <?=$order_data->fields['ADDRESS_1']?></p>
                                    </div>
                                    <div class="col-4">
                                        <p>Country : <?=$order_data->fields['COUNTRY_NAME']?></p>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-4">
                                        <p>State : <?=$order_data->fields['STATE_NAME']?></p>
                                    </div>
                                    <div class="col-4">
                                        <p>City : <?=$order_data->fields['CITY']?></p>
                                    </div>
                                    <div class="col-4">
                                        <p>Zip : <?=$order_data->fields['ZIP']?></p>
                                    </div>
                                </div>
                            </div>

                            <div class="row m-t-30">
                                <?php
                                $all_item_total = 0;
                                $product_details = $db_account->Execute("SELECT DOA_ORDER_ITEM.*, DOA_PRODUCT.PRODUCT_NAME, DOA_PRODUCT.PRODUCT_IMAGES, DOA_PRODUCT_SIZE.SIZE, DOA_PRODUCT_COLOR.COLOR FROM `DOA_ORDER_ITEM` LEFT JOIN DOA_PRODUCT ON DOA_ORDER_ITEM.PK_PRODUCT = DOA_PRODUCT.PK_PRODUCT LEFT JOIN DOA_PRODUCT_SIZE ON DOA_ORDER_ITEM.PK_PRODUCT_SIZE = DOA_PRODUCT_SIZE.PK_PRODUCT_SIZE LEFT JOIN DOA_PRODUCT_COLOR ON DOA_ORDER_ITEM.PK_PRODUCT_COLOR = DOA_PRODUCT_COLOR.PK_PRODUCT_COLOR WHERE DOA_ORDER_ITEM.PK_ORDER = ".$PK_ORDER);
                                while (!$product_details->EOF) {
                                    $item_total = $product_details->fields['PRODUCT_QUANTITY'] * $product_details->fields['PRODUCT_PRICE'];
                                    $all_item_total += $item_total; ?>
                                    <div class="row m-b-30">
                                        <div class="col-4">
                                            <img id="profile-img" src="<?=$product_details->fields['PRODUCT_IMAGES']?>" alt="<?=$product_details->fields['PRODUCT_NAME']?>" style="width: 180px; height: auto;">
                                        </div>
                                        <div class="col-6">
                                            <b style="font-weight: bold;"><?=$product_details->fields['PRODUCT_NAME']?></b>
                                            <div class="number" style="margin: 5px 0 5px 0">
                                                <span> <?=$product_details->fields['PRODUCT_QUANTITY']?> X $<?=number_format($product_details->fields['PRODUCT_PRICE'], 2)?></span>
                                            </div>
                                            <div class="row m-b-10">
                                                <div class="col-8">
                                                    <div class="row">
                                                        <div class="col-6">
                                                            <p><strong style="font-weight: bold;">Colour :</strong> <?=$product_details->fields['COLOR']?></p>
                                                        </div>
                                                        <div class="col-6">
                                                            <p><strong style="font-weight: bold;">Size :</strong> <?=$product_details->fields['SIZE']?></p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-2">
                                            <p>$<?=number_format($item_total,2)?></p>
                                        </div>
                                    </div>
                                <?php $product_details->MoveNext();
                                } ?>
                                <div class="row" style="width: 92%;">
                                    <div class="row m-t-5">
                                        <div class="col-8">
                                            <b style="font-weight: bold; float: right;"> Subtotal : </b>
                                        </div>
                                        <div class="col-4">
                                            <b style="font-weight: bold; float: right;" id="all_item_total_text"> $<?=number_format($order_data->fields['ITEM_TOTAL'], 2)?> </b>
                                        </div>
                                    </div>
                                    <div class="row m-t-5">
                                        <div class="col-8">
                                            <b style="font-weight: bold; float: right;"> Sales Tax : </b>
                                        </div>
                                        <div class="col-4">
                                            <b style="font-weight: bold; float: right;" id="all_item_total_text"> <?=number_format($order_data->fields['SALES_TAX'], 2)?>% </b>
                                        </div>
                                    </div>
                                    <div class="row m-t-5 shipping_div" style="display: none;">
                                        <div class="col-8">
                                            <b style="font-weight: bold; float: right; margin-top: 8px;"> Shipping Charge : </b>
                                        </div>
                                        <div class="col-4">
                                            <b style="font-weight: bold; float: right;" id="all_item_total_text"> <?=number_format($order_data->fields['SHIPPING_CHARGE'], 2)?> % </b>
                                        </div>
                                    </div>
                                    <div class="row m-t-5">
                                        <div class="col-8">
                                            <b style="font-weight: bold; float: right;"> Order Total : </b>
                                        </div>
                                        <div class="col-4">
                                            <b style="font-weight: bold; float: right;" id="order_total"> $<?=number_format($order_data->fields['ORDER_TOTAL'], 2)?> </b>
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
</div>


<?php require_once('../includes/footer.php');?>
<script>
    function changeOrderType(param) {
        if ($(param).val() === 'SHIPPING') {
            $('.shipping_div').slideDown();
        } else {
            $('.shipping_div').slideUp();
        }
    }
    </script>

</body>
</html>