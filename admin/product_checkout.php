<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $upload_path;

$title = "Product Checkout";

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
            </div>

            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body" style="width: 80%; margin: auto;">
                            <form class="form-material form-horizontal" action="" method="post" enctype="multipart/form-data">
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
                                </div>

                                <div class="row m-t-30">
                                    <?php
                                    if (isset($_SESSION['CART_DATA']) && count($_SESSION['CART_DATA']) > 0) {
                                        $all_item_total = 0;
                                        foreach ($_SESSION['CART_DATA'] as $key => $cart_data) {
                                            $item_total = $cart_data['PRODUCT_QUANTITY'] * $cart_data['PRODUCT_PRICE'];
                                            $all_item_total += $item_total; ?>
                                            <div class="row m-b-15">
                                                <div class="col-4">
                                                    <img id="profile-img" src="<?=$cart_data['PRODUCT_IMAGES']?>" alt="<?=$cart_data['PRODUCT_NAME']?>" style="width: 180px; height: auto;">
                                                </div>
                                                <div class="col-6">
                                                    <b style="font-weight: bold;"><?=$cart_data['PRODUCT_NAME']?></b>
                                                    <p class="m-t-5"><?=$cart_data['PRODUCT_QUANTITY']?> X $<?=number_format($cart_data['PRODUCT_PRICE'], 2)?></p>
                                                </div>
                                                <div class="col-2">
                                                    <a href="product_checkout.php" onclick="removeFromCart(<?=$key?>)" style="color: red; float: right;"><i class="fa fa-trash" title="Delete"></i></a><br>
                                                    <p class="m-t-5" style="float: right;">$<?=number_format($item_total, 2)?></p>
                                                </div>
                                            </div>
                                        <?php } ?>
                                        <div class="row">
                                            <div class="col-8">
                                                <b style="font-weight: bold; float: right;"> Subtotal : </b>
                                            </div>
                                            <div class="col-4">
                                                <input type="hidden" name="ALL_ITEM_TOTAL" id="ALL_ITEM_TOTAL" value="<?=$all_item_total?>">
                                                <b style="font-weight: bold; float: right;"> $<?=number_format($all_item_total, 2)?> </b>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-8">
                                                <b style="font-weight: bold; float: right; margin-top: 8px;"> Shipping Charge : </b>
                                            </div>
                                            <div class="col-4">
                                                <input type="text" id="SHIPPING_CHARGE" name="SHIPPING_CHARGE" class="form-control" placeholder="Shipping Charge" value="0.00" style="float: right; width: 100px; text-align: right;" onkeyup="calculateOrderTotal()">
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-8">
                                                <b style="font-weight: bold; float: right;"> Order Total : </b>
                                            </div>
                                            <div class="col-4">
                                                <b style="font-weight: bold; float: right;" id="order_total"> $<?=number_format($all_item_total, 2)?> </b>
                                            </div>
                                        </div>
                                    <?php } else { ?>
                                        <div class="row" style="text-align: center;">
                                            <b style="font-weight: bold; color: indianred;">Your Cart is Empty</b>
                                        </div>
                                    <?php } ?>
                                </div>

                                <div class="row m-t-30">
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
                                                        <select class="form-control" name="PK_COUNTRY" id="PK_COUNTRY" onChange="fetch_state(this.value)" required>
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
                                                        <select class="form-control" name="PK_STATES" id="PK_STATES" required>
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

                                <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">Proceed</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php require_once('../includes/footer.php');?>
    <script>
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

        function calculateOrderTotal() {
            let ALL_ITEM_TOTAL = $('#ALL_ITEM_TOTAL').val();
            let SHIPPING_CHARGE = $('#SHIPPING_CHARGE').val();
            if (!SHIPPING_CHARGE) {
                $('#SHIPPING_CHARGE').val(0);
                SHIPPING_CHARGE = 0;
            }
            let ORDER_TOTAL = parseFloat(ALL_ITEM_TOTAL)+parseFloat(SHIPPING_CHARGE);
            $('#order_total').text('$'+ORDER_TOTAL.toFixed(2));
        }
    </script>
</body>
</html>