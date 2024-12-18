<?php
require_once('../../global/config.php');

if (isset($_SESSION['CART_DATA']) && count($_SESSION['CART_DATA']) > 0) {
    $all_item_total = 0;
    foreach ($_SESSION['CART_DATA'] as $key => $cart_data) {
        $item_total = $cart_data['PRODUCT_QUANTITY'] * $cart_data['PRODUCT_PRICE'];
        $all_item_total += $item_total; ?>
        <div class="row m-b-15">
            <div class="col-4">
                <img id="profile-img" src="<?=$cart_data['PRODUCT_IMAGES']?>" alt="<?=$cart_data['PRODUCT_NAME']?>" style="width: 110px; height: auto;">
            </div>
            <div class="col-6">
                <b style="font-weight: bold;"><?=$cart_data['PRODUCT_NAME']?></b>
                <p class="m-t-5"><?=$cart_data['PRODUCT_QUANTITY']?> X $<?=number_format($cart_data['PRODUCT_PRICE'], 2)?></p>
            </div>
            <div class="col-2">
                <a href="javascript:" onclick="removeFromCart(<?=$key?>)" style="color: red; float: right;"><i class="fa fa-trash" title="Delete"></i></a>
                <p class="m-t-5" style="float: right;">$<?=number_format($item_total, 2)?></p>
            </div>
        </div>
    <?php } ?>
    <div class="row">
        <div class="col-8">
            <b style="font-weight: bold; float: right;"> Total : </b>
        </div>
        <div class="col-4">
            <b style="font-weight: bold; float: right;"> $<?=number_format($all_item_total, 2)?> </b>
        </div>
    </div>
<?php } else { ?>
    <div class="row" style="text-align: center;">
        <b style="font-weight: bold; color: indianred;">Your Cart is Empty</b>
    </div>
<?php } ?>