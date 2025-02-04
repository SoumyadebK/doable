<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $upload_path;

$title = "Product Checkout";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5]) ){
    header("location:../login.php");
    exit;
}

$account_data = $db->Execute("SELECT * FROM `DOA_ACCOUNT_MASTER` WHERE `PK_ACCOUNT_MASTER` = '$_SESSION[PK_ACCOUNT_MASTER]'");

$PAYMENT_GATEWAY = $account_data->fields['PAYMENT_GATEWAY_TYPE'];
$SECRET_KEY = $account_data->fields['SECRET_KEY'];
$PUBLISHABLE_KEY = $account_data->fields['PUBLISHABLE_KEY'];

$SQUARE_ACCESS_TOKEN = $account_data->fields['ACCESS_TOKEN'];
$SQUARE_APP_ID = $account_data->fields['APP_ID'];
$SQUARE_LOCATION_ID = $account_data->fields['LOCATION_ID'];

$SALES_TAX = getSalesTax($_SESSION['DEFAULT_LOCATION_ID']);

if(!empty($_POST)) {
    $ORDER_DATA['ORDER_ID'] = time();
    $ORDER_DATA['PK_USER_MASTER'] = $_POST['PK_USER_MASTER'];
    $ORDER_DATA['ORDER_TYPE'] = $_POST['ORDER_TYPE'];
    $ORDER_DATA['ITEM_TOTAL'] = $_POST['ALL_ITEM_TOTAL'];
    $ORDER_DATA['SALES_TAX'] = $_POST['SALES_TAX_AMOUNT'];
    $ORDER_DATA['SHIPPING_CHARGE'] = $_POST['SHIPPING_CHARGE'];
    $ORDER_DATA['ORDER_TOTAL'] = $_POST['SALES_TAX_AMOUNT']+$_POST['SHIPPING_CHARGE']+$_POST['ALL_ITEM_TOTAL'];
    if ($ORDER_DATA['ORDER_TYPE'] == 'SHIPPING') {
        $ORDER_DATA['ADDRESS'] = $_POST['ADDRESS'];
        $ORDER_DATA['ADDRESS_1'] = $_POST['ADDRESS_1'];
        $ORDER_DATA['PK_COUNTRY'] = $_POST['PK_COUNTRY'];
        $ORDER_DATA['PK_STATES'] = $_POST['PK_STATES'];
        $ORDER_DATA['CITY'] = $_POST['CITY'];
        $ORDER_DATA['ZIP'] = $_POST['ZIP'];
    }
    $ORDER_DATA['PK_PAYMENT_TYPE'] = 3;
    $ORDER_DATA['PAYMENT_DETAILS'] = 'Cash';
    $ORDER_DATA['PAYMENT_STATUS'] = 'Success';
    $ORDER_DATA['PK_ORDER_STATUS'] = 1;
    $ORDER_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
    $ORDER_DATA['CREATED_ON'] = date("Y-m-d H:i");

    db_perform_account('DOA_ORDER', $ORDER_DATA, 'insert');
    $PK_ORDER = $db_account->insert_ID();

    $PK_PRODUCT = $_POST['PK_PRODUCT'];
    $PK_PRODUCT_COLOR = $_POST['PK_PRODUCT_COLOR'];
    $PK_PRODUCT_SIZE = $_POST['PK_PRODUCT_SIZE'];
    $PRODUCT_QUANTITY = $_POST['PRODUCT_QUANTITY'];
    $PRODUCT_PRICE = $_POST['PRODUCT_PRICE'];

    for ($i = 0; $i < count($PK_PRODUCT); $i++) {
        $ORDER_ITEM_DATA['PK_ORDER'] = $PK_ORDER;
        $ORDER_ITEM_DATA['PK_PRODUCT'] = $PK_PRODUCT[$i];
        $ORDER_ITEM_DATA['PK_PRODUCT_COLOR'] = $PK_PRODUCT_COLOR[$i];
        $ORDER_ITEM_DATA['PK_PRODUCT_SIZE'] = $PK_PRODUCT_SIZE[$i];
        $ORDER_ITEM_DATA['PRODUCT_QUANTITY'] = $PRODUCT_QUANTITY[$i];
        $ORDER_ITEM_DATA['PRODUCT_PRICE'] = $PRODUCT_PRICE[$i];
        db_perform_account('DOA_ORDER_ITEM', $ORDER_ITEM_DATA, 'insert');
    }

    $PAYMENT_INFO = 'Cash';
    $PAYMENT_DATA['PK_ENROLLMENT_MASTER'] = 0;
    $PAYMENT_DATA['PK_ENROLLMENT_BILLING'] = 0;
    $PAYMENT_DATA['PK_PAYMENT_TYPE'] = 3;
    $PAYMENT_DATA['AMOUNT'] = $ORDER_DATA['ORDER_TOTAL'];
    $PAYMENT_DATA['PK_ENROLLMENT_LEDGER'] = 0;
    $PAYMENT_DATA['PK_ORDER'] = $PK_ORDER;
    $TYPE = 'Payment';
    if ($_POST['PK_PAYMENT_TYPE'] == 2) {
        $PAYMENT_INFO_ARRAY = ['CHECK_NUMBER' => $_POST['CHECK_NUMBER'], 'CHECK_DATE' => date('Y-m-d', strtotime($_POST['CHECK_DATE']))];
        $PAYMENT_INFO = json_encode($PAYMENT_INFO_ARRAY);
    }
    $PAYMENT_DATA['TYPE'] = $TYPE;
    $PAYMENT_DATA['NOTE'] = 'Product Purchase';
    $PAYMENT_DATA['PAYMENT_DATE'] = date('Y-m-d');
    $PAYMENT_DATA['PAYMENT_INFO'] = $PAYMENT_INFO;
    $PAYMENT_DATA['PAYMENT_STATUS'] = 'Success';

    $receipt = $db_account->Execute("SELECT RECEIPT_NUMBER FROM DOA_ENROLLMENT_PAYMENT WHERE IS_ORIGINAL_RECEIPT = 1 ORDER BY CONVERT(RECEIPT_NUMBER, DECIMAL) DESC LIMIT 1");
    if ($receipt->RecordCount() > 0) {
        $RECEIPT_NUMBER_ORIGINAL = $receipt->fields['RECEIPT_NUMBER'] + 1;
    } else {
        $RECEIPT_NUMBER_ORIGINAL = 1;
    }

    $PAYMENT_DATA['RECEIPT_NUMBER'] = $RECEIPT_NUMBER_ORIGINAL;
    $PAYMENT_DATA['IS_ORIGINAL_RECEIPT'] = 1;
    db_perform_account('DOA_ENROLLMENT_PAYMENT', $PAYMENT_DATA, 'insert');

    unset($_SESSION['CART_DATA']);
    header("location:all_orders.php");
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
            </div>

            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body" style="width: 80%; margin: auto;">
                            <form id="order_details_form" class="form-material form-horizontal" action="" method="post" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-6">
                                        <label for="PK_USER_MASTER">Select Customer</label>
                                        <select required class="form-control" name="PK_USER_MASTER" id="PK_USER_MASTER" onchange="fetchAddress();">
                                            <option value="">Select Customer</option>
                                            <?php
                                            $row = $db->Execute("SELECT DOA_USERS.PK_USER, DOA_USER_MASTER.PK_USER_MASTER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_NAME, DOA_USERS.ADDRESS, DOA_USERS.ADDRESS_1, DOA_USERS.PK_COUNTRY, DOA_USERS.PK_STATES, DOA_USERS.CITY, DOA_USERS.ZIP FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER WHERE DOA_USER_MASTER.PRIMARY_LOCATION_ID IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_USERS.ACTIVE = 1 AND DOA_USERS.IS_DELETED = 0 AND DOA_USER_ROLES.PK_ROLES = 4 AND DOA_USER_MASTER.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER']." ORDER BY DOA_USERS.FIRST_NAME");
                                            while (!$row->EOF) {?>
                                                <option value="<?php echo $row->fields['PK_USER_MASTER'];?>" data-address="<?php echo $row->fields['ADDRESS']?>" data-address_1="<?php echo $row->fields['ADDRESS_1']?>" data-country="<?php echo $row->fields['PK_COUNTRY']?>" data-state="<?php echo $row->fields['PK_STATES']?>" data-city="<?php echo $row->fields['CITY']?>" data-zip="<?php echo $row->fields['ZIP']?>"><?=$row->fields['NAME'].' ('.$row->fields['USER_NAME'].')'?></option>
                                            <?php $row->MoveNext(); } ?>
                                        </select>
                                    </div>
                                    <div class="col-6 m-t-10">
                                        <a href="javascript:" onclick="addNewCustomer()" class="btn btn-info waves-effect waves-light m-r-10 text-white">Add New Customer</a>
                                    </div>
                                </div>

                                <div class="row m-t-20">
                                    <div class="col-6">
                                        <label for="PICK_UP"><input type="radio" id="PICK_UP" name="ORDER_TYPE" class="form-check-inline charge_type" value="PICK_UP" onchange="changeOrderType(this);" checked>Pick Up</label>
                                        <label class="m-l-40" for="SHIPPING"><input type="radio" id="SHIPPING" name="ORDER_TYPE" class="form-check-inline charge_type" value="SHIPPING" onchange="changeOrderType(this);">Shipping</label>
                                    </div>
                                </div>

                                <div class="row m-t-30">
                                    <?php
                                    if (isset($_SESSION['CART_DATA']) && count($_SESSION['CART_DATA']) > 0) {
                                        $all_item_total = 0;
                                        foreach ($_SESSION['CART_DATA'] as $key => $cart_data) {
                                            $item_total = $cart_data['PRODUCT_QUANTITY'] * $cart_data['PRODUCT_PRICE'];
                                            $all_item_total += $item_total; ?>
                                            <div class="row m-b-30">
                                                <div class="col-4">
                                                    <img id="profile-img" src="<?=$cart_data['PRODUCT_IMAGES']?>" alt="<?=$cart_data['PRODUCT_NAME']?>" style="width: 180px; height: auto;">
                                                </div>
                                                <div class="col-6">
                                                    <b style="font-weight: bold;"><?=$cart_data['PRODUCT_NAME']?></b>
                                                    <div class="number" style="margin: 5px 0 5px 0">
                                                        <span class="minus btn btn-info waves-effect waves-light text-white" onclick="increaseDecreaseCounter('<?=$key?>', 'decrease')">-</span>
                                                        <input class="counter_input" inputmode="numeric" oninput="this.value = this.value.replace(/\D+/g, '')" id="product_quantity_<?=$key?>" name="PRODUCT_QUANTITY[]" value="<?=$cart_data['PRODUCT_QUANTITY']?>"/>
                                                        <span class="plus btn btn-info waves-effect waves-light text-white" onclick="increaseDecreaseCounter('<?=$key?>', 'increase')">+</span>
                                                        <span> X $<?=number_format($cart_data['PRODUCT_PRICE'], 2)?></span>
                                                    </div>
                                                    <div class="row m-b-10">
                                                        <div class="col-8">
                                                            <div class="row">
                                                                <?php
                                                                $product_color = $db_account->Execute("SELECT * FROM DOA_PRODUCT_COLOR WHERE PK_PRODUCT_COLOR = ".$cart_data['PK_PRODUCT_COLOR']);
                                                                if ($product_color->RecordCount() > 0) { ?>
                                                                    <div class="col-6">
                                                                        <p><strong style="font-weight: bold;">Colour :</strong> <?=$product_color->fields['COLOR']?></p>
                                                                    </div>
                                                                <?php } ?>

                                                                <?php
                                                                $product_size = $db_account->Execute("SELECT * FROM DOA_PRODUCT_SIZE WHERE PK_PRODUCT_SIZE = ".$cart_data['PK_PRODUCT_SIZE']);
                                                                if ($product_size->RecordCount() > 0) { ?>
                                                                    <div class="col-6">
                                                                        <p><strong style="font-weight: bold;">Size :</strong> <?=$product_size->fields['SIZE']?></p>
                                                                    </div>
                                                                <?php } ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <input type="hidden" id="product_price_<?=$key?>" value="<?=$cart_data['PRODUCT_PRICE']?>">
                                                <input type="hidden" name="PK_PRODUCT[]" value="<?=$cart_data['PK_PRODUCT']?>">
                                                <input type="hidden" name="PK_PRODUCT_COLOR[]" value="<?=$cart_data['PK_PRODUCT_COLOR']?>">
                                                <input type="hidden" name="PK_PRODUCT_SIZE[]" value="<?=$cart_data['PK_PRODUCT_SIZE']?>">
                                                <input type="hidden" name="PRODUCT_PRICE[]" value="<?=$cart_data['PRODUCT_PRICE']?>">
                                                <input class="item_total_price" type="hidden" name="TOTAL_PRODUCT_PRICE[]" id="item_total_price_value_<?=$key?>" value="<?=$item_total?>">
                                                <div class="col-2">
                                                    <a href="product_checkout.php" onclick="removeFromCart('<?=$key?>')" style="color: red; float: right;"><i class="fa fa-trash" title="Delete"></i></a><br>
                                                    <p class="m-t-5" style="float: right;" id="item_total_price_text_<?=$key?>">$<?=number_format($item_total, 2)?></p>
                                                </div>
                                            </div>
                                        <?php }
                                        $SALES_TAX_AMOUNT = ($all_item_total*($SALES_TAX/100)); ?>
                                        <div class="row m-t-5">
                                            <div class="col-8">
                                                <b style="font-weight: bold; float: right;"> Subtotal : </b>
                                            </div>
                                            <div class="col-4">
                                                <input type="hidden" name="ALL_ITEM_TOTAL" id="ALL_ITEM_TOTAL" value="<?=$all_item_total?>">
                                                <b style="font-weight: bold; float: right;" id="all_item_total_text"> $<?=number_format($all_item_total, 2)?> </b>
                                            </div>
                                        </div>
                                        <div class="row m-t-5">
                                            <div class="col-8">
                                                <b style="font-weight: bold; float: right;"> Sales Tax : (<?=number_format($SALES_TAX, 2)?>%)</b>
                                            </div>
                                            <div class="col-4">
                                                <input type="hidden" name="SALES_TAX" id="SALES_TAX" value="<?=$SALES_TAX?>">
                                                <input type="hidden" name="SALES_TAX_AMOUNT" id="SALES_TAX_AMOUNT" value="<?=$SALES_TAX_AMOUNT?>">
                                                <b style="font-weight: bold; float: right;" id="sales_tax_amount_text"> $<?=number_format($SALES_TAX_AMOUNT, 2)?> </b>
                                            </div>
                                        </div>
                                        <div class="row m-t-5 shipping_div" style="display: none;">
                                            <div class="col-8">
                                                <b style="font-weight: bold; float: right; margin-top: 8px;"> Shipping Charge : </b>
                                            </div>
                                            <div class="col-4">
                                                <input type="text" id="SHIPPING_CHARGE" name="SHIPPING_CHARGE" class="form-control" placeholder="Shipping Charge" value="0.00" style="float: right; width: 100px; text-align: right;" onkeyup="calculateOrderTotal()">
                                            </div>
                                        </div>
                                        <div class="row m-t-5">
                                            <div class="col-8">
                                                <b style="font-weight: bold; float: right;"> Order Total : </b>
                                            </div>
                                            <div class="col-4">
                                                <?php $order_total = ($all_item_total + $SALES_TAX_AMOUNT); ?>
                                                <b style="font-weight: bold; float: right;" id="order_total"> $<?=number_format($order_total, 2)?> </b>
                                            </div>
                                        </div>
                                    <?php } else { ?>
                                        <div class="row" style="text-align: center;">
                                            <b style="font-weight: bold; color: indianred;">Your Cart is Empty</b>
                                        </div>
                                    <?php } ?>
                                </div>

                                <div class="row m-t-30 shipping_div" style="display: none;">
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label class="col-md-12" for="example-text" style="font-weight: bold; font-size: 16px">Shipping Information<span class="text-danger">*</span></label>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="form-group">
                                                <label class="col-md-12">Address</label>
                                                <div class="col-md-12">
                                                    <input type="text" id="ADDRESS" name="ADDRESS" class="form-control" placeholder="Enter Address">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-group">
                                                <label class="col-md-12">Apt/Ste</label>
                                                <div class="col-md-12">
                                                    <input type="text" id="ADDRESS_1" name="ADDRESS_1" class="form-control" placeholder="Enter Apt/Ste">
                                                </div>
                                            </div>

                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-6">
                                            <div class="form-group">
                                                <label class="col-md-12">Country<span class="text-danger">*</span></label>
                                                <div class="col-md-12">
                                                    <div class="col-sm-12">
                                                        <select class="form-control" name="PK_COUNTRY" id="PK_COUNTRY" onChange="fetch_state(this.value)">
                                                            <option>Select Country</option>
                                                            <?php
                                                            $row = $db->Execute("SELECT PK_COUNTRY,COUNTRY_NAME FROM DOA_COUNTRY WHERE ACTIVE = 1 ORDER BY PK_COUNTRY");
                                                            while (!$row->EOF) { ?>
                                                                <option value="<?php echo $row->fields['PK_COUNTRY'];?>"><?=$row->fields['COUNTRY_NAME']?></option>
                                                            <?php $row->MoveNext(); } ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-6">
                                            <div class="form-group">
                                                <label class="col-md-12">State<span class="text-danger">*</span></label>
                                                <div class="col-md-12">
                                                    <div class="col-sm-12" id="State_div">
                                                        <select class="form-control" name="PK_STATES" id="PK_STATES">
                                                            <option value="">Select State</option>
                                                            <?php
                                                            $row = $db->Execute("SELECT * FROM DOA_STATES ORDER BY STATE_NAME ASC");
                                                            while (!$row->EOF) { ?>
                                                                <option value="<?php echo $row->fields['PK_STATES'];?>"><?=$row->fields['STATE_NAME']?></option>
                                                            <?php $row->MoveNext(); } ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-6">
                                            <div class="form-group">
                                                <label class="col-md-12">City</label>
                                                <div class="col-md-12">
                                                    <input type="text" id="CITY" name="CITY" class="form-control" placeholder="Enter your city">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-group">
                                                <label class="col-md-12">Postal / Zip Code</label>
                                                <div class="col-md-12">
                                                    <input type="text" id="ZIP" name="ZIP" class="form-control" placeholder="Enter Postal / Zip Code">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row m-t-20">
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white" style="float: right;">Proceed to Checkout</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>



<div class="modal fade customer_model" id="customer_model" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="add_customer_form" action="" method="post" enctype="multipart/form-data">
            <input type="hidden" name="FUNCTION_NAME" value="addNewCustomer">
            <div class="modal-content">
                <div class="modal-header">
                    <h4><b>Add New Customer</b></h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <form class="form-material form-horizontal" id="profile_form">
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label">First Name<span class="text-danger">*</span></label>
                                    <div class="col-md-12">
                                        <input type="text" id="FIRST_NAME" name="FIRST_NAME" class="form-control" placeholder="Enter First Name" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label">Last Name</label>
                                    <div class="col-md-12">
                                        <input type="text" id="LAST_NAME" name="LAST_NAME" class="form-control" placeholder="Enter Last Name">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label">Email<span class="text-danger">*</span></label>
                                    <div class="col-md-12">
                                        <input type="email" id="EMAIL_ID" name="EMAIL_ID" class="form-control" placeholder="Enter Email Address" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label">Phone<span class="text-danger">*</span></label>
                                    <div class="col-md-12">
                                        <input type="text" name="PHONE" class="form-control" placeholder="Enter Phone Number" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="col-md-12">Address</label>
                                    <div class="col-md-12">
                                        <input type="text" id="ADDRESS" name="ADDRESS" class="form-control" placeholder="Enter Address">
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="col-md-12">Apt/Ste</label>
                                    <div class="col-md-12">
                                        <input type="text" id="ADDRESS_1" name="ADDRESS_1" class="form-control" placeholder="Enter Apt/Ste">

                                    </div>
                                </div>

                            </div>
                        </div>

                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="col-md-12">Country<span class="text-danger">*</span></label>
                                    <div class="col-md-12">
                                        <div class="col-sm-12">
                                            <select class="form-control" name="PK_COUNTRY" id="PK_COUNTRY" onChange="fetch_customer_state(this.value)" required>
                                                <option>Select Country</option>
                                                <?php
                                                $row = $db->Execute("SELECT PK_COUNTRY,COUNTRY_NAME FROM DOA_COUNTRY WHERE ACTIVE = 1 ORDER BY PK_COUNTRY");
                                                while (!$row->EOF) { ?>
                                                    <option value="<?php echo $row->fields['PK_COUNTRY'];?>"><?=$row->fields['COUNTRY_NAME']?></option>
                                                <?php $row->MoveNext(); } ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="form-group">
                                    <label class="col-md-12">State<span class="text-danger">*</span></label>
                                    <div class="col-md-12">
                                        <div class="col-sm-12">
                                            <div id="customer_state_div">
                                                <select class="form-control" name="PK_STATE" id="PK_STATE">
                                                    <option>Select State</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="col-md-12">City</label>
                                    <div class="col-md-12">
                                        <input type="text" id="CITY" name="CITY" class="form-control" placeholder="Enter City">
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="col-md-12">Postal / Zip Code</label>
                                    <div class="col-md-12">
                                        <input type="text" id="ZIP" name="ZIP" class="form-control" placeholder="Enter Postal / Zip Code">
                                    </div>
                                </div>
                            </div>
                        </div>

                    </form>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" id="card-button" class="btn btn-info waves-effect waves-light m-r-10 text-white" style="float: right;">Submit</button>
                </div>
            </div>
        </form>
    </div>
</div>



<div class="modal fade payment_modal" id="product_payment_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="product_payment_form" action="" method="post" enctype="multipart/form-data">
            <div class="modal-content">
                <div class="modal-header">
                    <h4><b>Payment</b></h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="PAYMENT_GATEWAY" id="PAYMENT_GATEWAY" value="<?=$PAYMENT_GATEWAY?>">
                    <div class="p-20">
                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label class="form-label">Total Amount</label>
                                    <div class="col-md-12">
                                        <input type="text" name="ORDER_TOTAL_AMOUNT" id="ORDER_TOTAL_AMOUNT" class="form-control" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label class="form-label">Payment Type</label>
                                    <div class="col-md-12">
                                        <select class="form-control PAYMENT_TYPE ENROLLMENT_PAYMENT_TYPE" required name="PK_PAYMENT_TYPE" id="PK_PAYMENT_TYPE" onchange="selectPaymentType(this, 'enrollment')">
                                            <option value="">Select</option>
                                            <?php
                                            $row = $db->Execute("SELECT * FROM DOA_PAYMENT_TYPE WHERE ACTIVE = 1");
                                            while (!$row->EOF) { ?>
                                                <option value="<?php echo $row->fields['PK_PAYMENT_TYPE'];?>"><?=$row->fields['PAYMENT_TYPE']?></option>
                                            <?php $row->MoveNext(); } ?>
                                        </select>
                                    </div>
                                    <div id="wallet_balance_div">

                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if ($PAYMENT_GATEWAY == 'Stripe'){ ?>
                            <div class="row payment_type_div" id="credit_card_payment" style="display: none;">
                                <div class="row" style="margin: auto;" id="card_list">
                                </div>
                                <div class="col-12">
                                    <div class="form-group" id="card_div">

                                    </div>
                                </div>
                            </div>
                        <?php } elseif ($PAYMENT_GATEWAY == 'Square') { ?>
                            <div class="row payment_type_div" id="credit_card_payment" style="display: none;">
                                <div class="row" style="margin: auto;" id="card_list">
                                </div>
                                <div class="col-12">
                                    <div class="form-group" id="card_div">

                                    </div>
                                </div>
                                <div id="payment-status-container"></div>
                            </div>
                        <?php } ?>


                        <div class="row payment_type_div" id="check_payment" style="display: none;">
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label">Check Number</label>
                                    <div class="col-md-12">
                                        <input type="text" name="CHECK_NUMBER" class="form-control">
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label">Check Date</label>
                                    <div class="col-md-12">
                                        <input type="text" name="CHECK_DATE" class="form-control datepicker-normal">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" id="card-button" class="btn btn-info waves-effect waves-light m-r-10 text-white" style="float: right;">Process</button>
                </div>
            </div>
        </form>
    </div>
</div>


<?php require_once('../includes/footer.php');?>
<script>
    function fetch_customer_state(PK_COUNTRY){
        jQuery(document).ready(function() {
            let data = "PK_COUNTRY="+PK_COUNTRY+"&PK_STATES=0";
            let value = $.ajax({
                url: "ajax/state.php",
                type: "POST",
                data: data,
                async: false,
                cache :false,
                success: function (result) {
                    document.getElementById('customer_state_div').innerHTML = result;
                }
            }).responseText;
        });
    }

    $(document).on('submit', '#add_customer_form', function (event) {
        event.preventDefault();
        let form_data = new FormData($('#add_customer_form')[0]); //$('#document_form').serialize();
        $.ajax({
            url: "ajax/AjaxFunctionProductPurchase.php",
            type: 'POST',
            data: form_data,
            processData: false,
            contentType: false,
            success:function (data) {
                window.location.reload();
            }
        });
    });

    function fetch_state(PK_COUNTRY, PK_STATES){
        jQuery(document).ready(function() {
            let data = "PK_COUNTRY="+PK_COUNTRY+"&PK_STATES="+PK_STATES;
            let value = $.ajax({
                url: "ajax/state.php",
                type: "POST",
                data: data,
                async: false,
                cache :false,
                success: function (result) {
                    document.getElementById('State_div').innerHTML = result;
                }
            }).responseText;
        });
    }

    function addNewCustomer() {
        $('#customer_model').modal('show');
    }

    function changeOrderType(param) {
        if ($(param).val() === 'SHIPPING') {
            $('.shipping_div').slideDown();
        } else {
            $('.shipping_div').slideUp();
        }
    }

    function fetchAddress() {
        let address = $('#PK_USER_MASTER').find(':selected').data('address');
        let address_1 = $('#PK_USER_MASTER').find(':selected').data('address_1');
        let PK_COUNTRY = $('#PK_USER_MASTER').find(':selected').data('country');
        let PK_STATE = $('#PK_USER_MASTER').find(':selected').data('state');
        let city = $('#PK_USER_MASTER').find(':selected').data('city');
        let zip = $('#PK_USER_MASTER').find(':selected').data('zip');
        $('#ADDRESS').val(address);
        $('#ADDRESS_1').val(address_1);
        $('#PK_COUNTRY').val(PK_COUNTRY);
        $('#PK_STATES').val(PK_STATE);
        $('#CITY').val(city);
        $('#ZIP').val(zip);
    }

    function increaseDecreaseCounter(array_index, type) {
        $.ajax({
            url: "ajax/AjaxFunctionProductPurchase.php",
            type: 'POST',
            data: {FUNCTION_NAME: 'increaseDecreaseCounter', array_index: array_index, type: type},
            success: function (data) {
                let product_quantity = $('#product_quantity_'+array_index).val();
                let product_price = $('#product_price_'+array_index).val();
                let product_total_price = product_price*product_quantity;
                $('#item_total_price_text_'+array_index).text('$'+numberWithCommas(product_total_price.toFixed(2)));
                $('#item_total_price_value_'+array_index).val(product_total_price);
                let all_product_total = 0;
                $('.item_total_price').each(function () {
                    all_product_total += parseFloat($(this).val());
                });
                $('#ALL_ITEM_TOTAL').val(all_product_total);
                $('#all_item_total_text').text('$'+numberWithCommas(all_product_total.toFixed(2)));
                calculateOrderTotal();
            }
        });
    }

    function calculateOrderTotal() {
        let ALL_ITEM_TOTAL = parseFloat($('#ALL_ITEM_TOTAL').val());
        let SHIPPING_CHARGE = parseFloat($('#SHIPPING_CHARGE').val());
        let SALES_TAX = parseFloat($('#SALES_TAX').val());
        let SALES_TAX_AMOUNT = ALL_ITEM_TOTAL*(SALES_TAX/100);
        $('#SALES_TAX_AMOUNT').val(SALES_TAX_AMOUNT);
        $('#sales_tax_amount_text').text('$'+SALES_TAX_AMOUNT.toFixed(2));

        if (!SHIPPING_CHARGE) {
            $('#SHIPPING_CHARGE').val(0);
            SHIPPING_CHARGE = 0;
        }

        let ORDER_TOTAL = ALL_ITEM_TOTAL+SALES_TAX_AMOUNT+SHIPPING_CHARGE;

        $('#order_total').text('$'+numberWithCommas(ORDER_TOTAL.toFixed(2)));
        $('#ORDER_TOTAL_AMOUNT').val(ORDER_TOTAL.toFixed(2));
    }

    $(document).on('submit', '#order_details_form', function (event) {
        event.preventDefault();
        calculateOrderTotal();
        $('#product_payment_modal').modal('show');
    });

    function selectPaymentType(param, type){
        let paymentType = parseInt($(param).val());
        let PAYMENT_GATEWAY = $('#PAYMENT_GATEWAY').val();
        $(param).closest('.payment_modal').find('.payment_type_div').slideUp();
        let form = document.getElementById('product_payment_form');
        form.removeEventListener('submit', listener);
        $(param).closest('.payment_modal').find('#card-element').remove();
        $(param).closest('.payment_modal').find('#enrollment-card-container').remove();
        switch (paymentType) {
            case 1:
                if (PAYMENT_GATEWAY === 'Stripe') {
                    $(param).closest('.payment_modal').find('#card_div').html(`<div id="card-element"></div><p id="card-errors" role="alert"></p>`);
                    stripePaymentFunction(type);
                }

                if (PAYMENT_GATEWAY === 'Square') {
                    $(param).closest('.payment_modal').find('#card_div').html(`<div id="enrollment-card-container"></div>`);
                    $('#'+type+'-card-container').text('Loading......');
                    squarePaymentFunction(type);
                }

                getCreditCardList();
                $(param).closest('.payment_modal').find('#credit_card_payment').slideDown();
                break;

            case 2:
                $(param).closest('.payment_modal').find('#check_payment').slideDown();
                break;

            case 7:
                let PK_USER_MASTER = $('#PK_USER_MASTER').val();
                $.ajax({
                    url: "ajax/wallet_balance.php",
                    type: 'POST',
                    data: {PK_USER_MASTER: PK_USER_MASTER},
                    success: function (data) {
                        $('#wallet_balance_div').html(data);
                        $('#wallet_balance_div').slideDown();

                        let ACTUAL_AMOUNT = parseFloat($('#ACTUAL_AMOUNT').val());
                        let WALLET_BALANCE = parseFloat($('#WALLET_BALANCE').val());

                        if (ACTUAL_AMOUNT > WALLET_BALANCE) {
                            //$('#PARTIAL_PAYMENT').prop('checked', true);
                            //$('.partial_payment_div').slideDown();

                            $('#AMOUNT_TO_PAY').val(WALLET_BALANCE);
                            $('#PARTIAL_AMOUNT').val(0);
                            $('#REMAINING_AMOUNT').val(ACTUAL_AMOUNT - WALLET_BALANCE);

                            //$('#PK_PAYMENT_TYPE_PARTIAL').prop('required', true);
                        } else {
                            //$('#PARTIAL_PAYMENT').prop('checked', false);
                            let ACTUAL_AMOUNT = $('#ACTUAL_AMOUNT').val();
                            $('#AMOUNT_TO_PAY').val(ACTUAL_AMOUNT);
                            $('#PARTIAL_AMOUNT').val(0);
                            $('#REMAINING_AMOUNT').val(0);
                            //$('.partial_payment_div').slideUp();
                            //$('#PK_PAYMENT_TYPE_PARTIAL').prop('required', false);
                        }
                    }
                });
                break;

            case 3:
            default:
                $(param).closest('.payment_modal').find('.payment_type_div').slideUp();
                $(param).closest('.payment_modal').find('#wallet_balance_div').slideUp();
                $(param).closest('.payment_modal').find('#partial_payment_div').slideUp();
                $(param).closest('.payment_modal').find('#PK_PAYMENT_TYPE_PARTIAL').prop('required', false);
                break;
        }
    }

    $(document).on('submit', '#product_payment_form', function (event) {
        event.preventDefault();
        let form = document.getElementById('order_details_form');
        form.submit();
    });
</script>

<?php
$SQUARE_MODE = 0;
if ($SQUARE_APP_ID != '' && strpos($SQUARE_APP_ID, 'sandbox') !== false) {
    $SQUARE_MODE = 2;
} elseif ($SQUARE_APP_ID != '') {
    $SQUARE_MODE = 1;
}

if ($SQUARE_MODE == 1)
    $SQ_URL = "https://connect.squareup.com";
else if ($SQUARE_MODE == 2)
    $SQ_URL = "https://connect.squareupsandbox.com";

if ($SQUARE_MODE == 1)
    $URL = "https://web.squarecdn.com/v1/square.js";
else if ($SQUARE_MODE == 2)
    $URL = "https://sandbox.web.squarecdn.com/v1/square.js";
?>

<script src="https://js.stripe.com/v3/"></script>
<script type="text/javascript">
    var stripe = Stripe('<?=$PUBLISHABLE_KEY?>');
    var elements = stripe.elements();

    var style = {
        base: {
            height: '34px',
            padding: '6px 12px',
            fontSize: '14px',
            lineHeight: '1.42857143',
            color: '#555',
            backgroundColor: '#fff',
            border: '1px solid #ccc',
            borderRadius: '4px',
            '::placeholder': {
                color: '#ddd'
            }
        },
        invalid: {
            color: '#fa755a',
            iconColor: '#fa755a'
        }
    };

    // Create an instance of the card Element.
    var card = elements.create('card', {style: style});
    var pay_type = '';

    function stripePaymentFunction(type) {
        pay_type = type;
        // Add an instance of the card Element into the `card-element` <div>.
        if (($('#card-element')).length > 0) {
            card.mount('#card-element');
        }
        // Handle real-time validation errors from the card Element.
        card.addEventListener('change', function (event) {
            var displayError = document.getElementById('card-errors');
            if (event.error) {
                displayError.textContent = event.error.message;
            } else {
                displayError.textContent = '';
            }
        });
        // Handle form submission.
        let form = document.getElementById('product_payment_form');
        form.addEventListener('submit', listener);
    }

    const listener = async event => {
        event.preventDefault();
        stripe.createToken(card).then(function (result) {
            if (result.error) {
                // Inform the user if there was an error.
                let errorElement = document.getElementById('card-errors');
                errorElement.textContent = result.error.message;
            } else {
                // Send the token to your server.
                stripeTokenHandler(result.token);
            }
        });
    }

    // Submit the form with the token ID.
    function stripeTokenHandler(token) {
        // Insert the token ID into the form, so it gets submitted to the server
        let form = document.getElementById('order_details_form');
        let hiddenInput = document.createElement('input');
        hiddenInput.setAttribute('type', 'hidden');
        hiddenInput.setAttribute('name', 'token');
        hiddenInput.setAttribute('value', token.id);
        form.appendChild(hiddenInput);
        form.submit();
    }
</script>


<script src="<?=$URL?>"></script>
<script type="text/javascript">
    async function squarePaymentFunction(type) {
        let square_appId = '<?=$SQUARE_APP_ID ?>';
        let square_locationId = '<?=$SQUARE_LOCATION_ID ?>';
        const payments = Square.payments(square_appId, square_locationId);
        const card = await payments.card();
        $('#'+type+'-card-container').text('');
        await card.attach('#'+type+'-card-container');

        let form = document.getElementById('product_payment_form');

        //const cardButton = document.getElementById('card-button');
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const statusContainer = document.getElementById('payment-status-container');

            try {
                const result = await card.tokenize();
                if (result.status === 'OK') {
                    document.getElementById('enrollment_sourceId').value = result.token;
                    console.log(`Payment token is ${result.token}`);
                    //statusContainer.innerHTML = "Payment Successful";
                    form.submit();
                } else {
                    let errorMessage = `Tokenization failed with status: ${result.status}`;
                    if (result.errors) {
                        errorMessage += ` and errors: ${JSON.stringify(
                            result.errors
                        )}`;
                    }
                    if ($('#enrollment-card-container').length > 0) {
                        throw new Error(errorMessage);
                    } else {
                        form.submit();
                    }
                }
            } catch (e) {
                console.error(e);
                statusContainer.innerHTML = "Payment Failed";
            }
        });
    }

    function getCreditCardList() {
        let PK_USER_MASTER = $('#PK_USER_MASTER').val();
        let PAYMENT_GATEWAY = $('#PAYMENT_GATEWAY').val();
        $.ajax({
            url: "ajax/get_credit_card_list.php",
            type: 'POST',
            data: {PK_USER_MASTER: PK_USER_MASTER, PAYMENT_GATEWAY: PAYMENT_GATEWAY},
            success: function (data) {
                $('#card_list').html(data);
            }
        });
    }


</script>

</body>
</html>