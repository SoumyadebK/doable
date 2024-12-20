<?php
require_once('../../global/config.php');
global $db;
global $db_account;
global $master_database;

$PK_PRODUCT = $_GET['PK_PRODUCT'];

$product_details =  $db_account->Execute("SELECT * FROM DOA_PRODUCT WHERE PK_PRODUCT = '$PK_PRODUCT'");
?>

<input type="hidden" id="PK_PRODUCT" name="PK_PRODUCT" value="<?=$PK_PRODUCT?>">
<input type="hidden" id="PRODUCT_NAME" name="PRODUCT_NAME" value="<?=$product_details->fields['PRODUCT_NAME']?>">
<input type="hidden" id="PRODUCT_IMAGES" name="PRODUCT_IMAGES" value="<?=$product_details->fields['PRODUCT_IMAGES']?>">
<input type="hidden" id="PRODUCT_PRICE" name="PRODUCT_PRICE" value="<?=$product_details->fields['PRICE']?>">

<div class="col-5">
    <img id="profile-img" src="<?=$product_details->fields['PRODUCT_IMAGES']?>" alt="<?=$product_details->fields['PRODUCT_NAME']?>" style="width: 145px; height: auto;">
</div>
<div class="col-7">
    <b style="font-weight: bold;"><?=$product_details->fields['PRODUCT_NAME']?></b>
    <p class="m-t-10 m-b-10" style="font-weight: bold; font-size: 18px;">$<?=$product_details->fields['PRICE']?></p>
    <div class="row m-t-20">
        <?php
        $product_color = $db_account->Execute("SELECT * FROM DOA_PRODUCT_COLOR WHERE PK_PRODUCT = '$PK_PRODUCT'");
        if ($product_color->RecordCount() > 0) { ?>
            <div class="col-6">
                <label class="form-label">Colour
                    <select class="form-control" name="PK_PRODUCT_COLOR" style="min-height: 30px; *width: 85px; line-height: 1; margin-top: 3px;">
                        <option value="">Select Colour</option>
                        <?php while (!$product_color->EOF) { ?>
                            <option value="<?=$product_color->fields['PK_PRODUCT_COLOR']?>"><?=$product_color->fields['COLOR']?></option>
                        <?php $product_color->MoveNext(); } ?>
                    </select>
                </label>
            </div>
        <?php } ?>

        <?php
        $product_size = $db_account->Execute("SELECT * FROM DOA_PRODUCT_SIZE WHERE PK_PRODUCT = '$PK_PRODUCT'");
        if ($product_size->RecordCount() > 0) { ?>
            <div class="col-6">
                <label class="form-label">Size
                    <select class="form-control" name="PK_PRODUCT_SIZE" style="min-height: 30px; *width: 85px; line-height: 1; margin-top: 3px;">
                        <option value="">Select Size</option>
                        <?php while (!$product_size->EOF) { ?>
                            <option value="<?=$product_size->fields['PK_PRODUCT_SIZE']?>"><?=$product_size->fields['SIZE']?></option>
                            <?php $product_size->MoveNext(); } ?>
                    </select>
                </label>
            </div>
        <?php } ?>
    </div>
</div>
