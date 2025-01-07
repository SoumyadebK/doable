<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $upload_path;

$title = "Add Product";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5]) ){
    header("location:../login.php");
    exit;
}

if(empty($_GET['id'])){
    $PK_PRODUCT = '';
    $PRODUCT_ID = '';
    $PRODUCT_NAME = '';
    $PRODUCT_DESCRIPTION = '';
    $PRICE = '';
    $SHIPPING_INFORMATION = '';
    $PRODUCT_IMAGES = '';
    $BRAND = '';
    $CATEGORY = '';
    $SIZE = '';
    $COLOR = '';
    $WEIGHT = '';
    $ACTIVE = '';
} else {
    $res = $db_account->Execute("SELECT * FROM `DOA_PRODUCT` WHERE `PK_PRODUCT` = '$_GET[id]'");
    if($res->RecordCount() == 0){
        header("location:all_products.php");
        exit;
    }
    $PK_PRODUCT = $res->fields['PK_PRODUCT'];
    $PRODUCT_ID = $res->fields['PRODUCT_ID'];
    $PRODUCT_NAME = $res->fields['PRODUCT_NAME'];
    $PRODUCT_DESCRIPTION = $res->fields['PRODUCT_DESCRIPTION'];
    $PRICE = $res->fields['PRICE'];
    $SHIPPING_INFORMATION = $res->fields['SHIPPING_INFORMATION'];
    $PRODUCT_IMAGES = $res->fields['PRODUCT_IMAGES'];
    $BRAND = $res->fields['BRAND'];
    $CATEGORY = $res->fields['CATEGORY'];
    $SIZE = $res->fields['SIZE'];
    $COLOR = $res->fields['COLOR'];
    $WEIGHT = $res->fields['WEIGHT'];
    $ACTIVE = $res->fields['ACTIVE'];
}

if(!empty($_POST)){
    $PRODUCT_SIZE = $_POST['PRODUCT_SIZE'];
    unset($_POST['PRODUCT_SIZE']);
    $PRODUCT_COLOR = $_POST['PRODUCT_COLOR'];
    unset($_POST['PRODUCT_COLOR']);
    $PRODUCT_DATA = $_POST;
    if ($_FILES['PRODUCT_IMAGES']['name'] != '') {
        if (!file_exists('../'.$upload_path.'/product_image/')) {
            mkdir('../'.$upload_path.'/product_image/', 0777, true);
        }
        $extn = explode(".", $_FILES['PRODUCT_IMAGES']['name']);
        $iindex = count($extn) - 1;
        $rand_string = time() . "-" . rand(100000, 999999);
        $file11 = 'product_image' . $_SESSION['PK_USER'] . $rand_string . "." . $extn[$iindex];
        $extension = strtolower($extn[$iindex]);

        if ($extension == "gif" || $extension == "jpeg" || $extension == "pjpeg" || $extension == "png" || $extension == "jpg") {
            $image_path = '../'.$upload_path.'/product_image/' . $file11;
            move_uploaded_file($_FILES['PRODUCT_IMAGES']['tmp_name'], $image_path);
            $PRODUCT_DATA['PRODUCT_IMAGES'] = $image_path;
        }
    }

    if (empty($_GET['id'])) {
        $PRODUCT_DATA['IS_DELETED'] = 0;
        $PRODUCT_DATA['ACTIVE'] = 1;
        $PRODUCT_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
        $PRODUCT_DATA['CREATED_ON'] = date("Y-m-d H:i");
        db_perform_account('DOA_PRODUCT', $PRODUCT_DATA, 'insert');
        $PK_PRODUCT = $db_account->insert_ID();
    } else {
        $PRODUCT_DATA['ACTIVE'] = $_POST['ACTIVE'];
        $PRODUCT_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
        $PRODUCT_DATA['EDITED_ON'] = date("Y-m-d H:i");
        db_perform_account('DOA_PRODUCT', $PRODUCT_DATA, 'update', "PK_PRODUCT = '$_GET[id]'");
        $PK_PRODUCT = $_GET['id'];
    }

    $db_account->Execute("DELETE FROM `DOA_PRODUCT_SIZE` WHERE `PK_PRODUCT` = '$PK_PRODUCT'");
    if (count($PRODUCT_SIZE) > 0) {
        for ($i = 0; $i < count($PRODUCT_SIZE); $i++) {
            $PRODUCT_SIZE_DATA['PK_PRODUCT'] = $PK_PRODUCT;
            $PRODUCT_SIZE_DATA['SIZE'] = $PRODUCT_SIZE[$i];
            db_perform_account('DOA_PRODUCT_SIZE', $PRODUCT_SIZE_DATA, 'insert');
        }
    }

    $db_account->Execute("DELETE FROM `DOA_PRODUCT_COLOR` WHERE `PK_PRODUCT` = '$PK_PRODUCT'");
    if (isset($PRODUCT_COLOR)) {
        for ($i = 0; $i < count($PRODUCT_COLOR); $i++) {
            $PRODUCT_COLOR_DATA['PK_PRODUCT'] = $PK_PRODUCT;
            $PRODUCT_COLOR_DATA['COLOR'] = $PRODUCT_COLOR[$i];
            db_perform_account('DOA_PRODUCT_COLOR', $PRODUCT_COLOR_DATA, 'insert');
        }
    }

    header("location:all_products.php");
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
                <div class="col-md-7 align-self-center text-end">
                    <div class="d-flex justify-content-end align-items-center">
                        <ol class="breadcrumb justify-content-end">
                            <li class="breadcrumb-item"><a href="setup.php">Setup</a></li>
                            <li class="breadcrumb-item"><a href="all_products.php">All Products</a></li>
                            <li class="breadcrumb-item active"><?=$title?></li>
                        </ol>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <form class="form-material form-horizontal" action="" method="post" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label class="col-md-12" for="example-text">Product ID/ SKU (Stock Keeping Unit)<span class="text-danger">*</span></label>
                                            <div class="col-md-12">
                                                <input type="text" id="PRODUCT_ID" name="PRODUCT_ID" class="form-control" placeholder="Enter Product ID/SKU" value="<?php echo $PRODUCT_ID?>" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label class="col-md-12" for="example-text">Product Name<span class="text-danger">*</span></label>
                                            <div class="col-md-12">
                                                <input type="text" id="PRODUCT_NAME" name="PRODUCT_NAME" class="form-control" placeholder="Enter Product Name" value="<?php echo $PRODUCT_NAME?>" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label class="col-md-12" for="example-text">Product Description<span class="text-danger">*</span></span>
                                            </label>
                                            <div class="col-md-12">
                                                <input type="text" id="PRODUCT_DESCRIPTION" name="PRODUCT_DESCRIPTION" class="form-control" placeholder="Enter Product Description" value="<?php echo $PRODUCT_DESCRIPTION?>" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label class="col-md-12" for="example-text">Price<span class="text-danger">*</span></span>
                                            </label>
                                            <div class="col-md-12">
                                                <input type="text" id="PRICE" name="PRICE" class="form-control" placeholder="Enter Price" value="<?php echo $PRICE?>" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label class="col-md-12" for="example-text">Shipping Information</label>
                                            <div class="col-md-12">
                                                <input type="text" id="SHIPPING_INFORMATION" name="SHIPPING_INFORMATION" class="form-control" placeholder="Enter Shipping Information" value="<?php echo $SHIPPING_INFORMATION?>" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label class="col-md-12" for="example-text">Product Images<span class="text-danger">*</span></label>
                                            <div class="col-md-12">
                                                <input type="file" name="PRODUCT_IMAGES" id="PRODUCT_IMAGES" class="form-control" onchange="previewFile(this)" <?php echo !empty($PRODUCT_IMAGES) ? '' : 'required'?>>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <?php if($PRODUCT_IMAGES!=''){?>
                                        <div style="width: 120px;height: 120px;margin-top: 25px;">
                                        <a class="fancybox" href="<?php echo $PRODUCT_IMAGES;?>" data-fancybox-group="gallery">
                                            <img id="profile-img" src="<?php echo $PRODUCT_IMAGES;?>" style="width:120px; height:120px" /></a>
                                        </div><?php } ?>
                                </div>

                                <div class="row">
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label class="col-md-12" for="example-text">Brand</label>
                                            <div class="col-md-12">
                                                <input type="text" id="BRAND" name="BRAND" class="form-control" placeholder="Enter Brand" value="<?php echo $BRAND?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label class="col-md-12" for="example-text">Category</label>
                                            <div class="col-md-12">
                                                <input type="text" id="CATEGORY" name="CATEGORY" class="form-control" placeholder="Enter Category" value="<?php echo $CATEGORY?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-5">
                                        <div class="form-group">
                                            <label class="col-md-12" for="example-text">Size/Dimensions</label>
                                            <div class="col-md-12">
                                                <input type="text" id="PRODUCT_SIZE" name="PRODUCT_SIZE[]" class="form-control" placeholder="Enter Size/Dimensions">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-1">
                                        <a href="javascript:;" class="btn btn-info waves-effect waves-light text-white" style="margin-top: 30px;" onclick="addMoreSize();"><i class="ti-plus"></i> Add</a>
                                    </div>
                                    <div class="col-5">
                                        <div class="form-group">
                                            <label class="col-md-12" for="example-text">Color</label>
                                            <div class="col-md-12">
                                                <input type="text" id="PRODUCT_COLOR" name="PRODUCT_COLOR[]" class="form-control" placeholder="Enter Color">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-1">
                                        <a href="javascript:;" class="btn btn-info waves-effect waves-light text-white" style="margin-top: 30px;" onclick="addMoreColor();"><i class="ti-plus"></i> Add</a>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-6" id="add_more_size">
                                        <?php
                                        if(!empty($_GET['id'])) {
                                            $product_size = $db_account->Execute("SELECT * FROM DOA_PRODUCT_SIZE WHERE PK_PRODUCT = '$PK_PRODUCT'");
                                            while (!$product_size->EOF) { ?>
                                                <div class="row">
                                                    <div class="col-10">
                                                        <div class="form-group">
                                                            <label class="form-label">Size/Dimensions<span class="text-danger">*</span></label>
                                                            <div class="col-md-12">
                                                                <input type="text" name="PRODUCT_SIZE[]" class="form-control" placeholder="Enter Size/Dimensions" value="<?=$product_size->fields['SIZE']?>" required>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-2" style="padding-top: 25px;">
                                                        <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                                    </div>
                                                </div>
                                                <?php $product_size->MoveNext(); } ?>
                                        <?php } ?>
                                    </div>
                                    <div class="col-6" id="add_more_color">
                                        <?php
                                        if(!empty($_GET['id'])) {
                                            $product_color = $db_account->Execute("SELECT * FROM DOA_PRODUCT_COLOR WHERE PK_PRODUCT = '$PK_PRODUCT'");
                                            while (!$product_color->EOF) { ?>
                                                <div class="row">
                                                    <div class="col-10">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Color<span class="text-danger">*</span></label>
                                                            <div class="col-md-12">
                                                                <input type="text" name="PRODUCT_COLOR[]" class="form-control" placeholder="Enter Color" value="<?=$product_color->fields['COLOR']?>" required>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-2" style="padding-top: 25px;">
                                                        <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                                    </div>
                                                </div>
                                                <?php $product_color->MoveNext(); } ?>
                                        <?php } ?>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label class="col-md-12" for="example-text">Weight</label>
                                            <div class="col-md-12">
                                                <input type="text" id="WEIGHT" name="WEIGHT" class="form-control" placeholder="Enter Weight" value="<?php echo $WEIGHT?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <?php if(!empty($_GET['id'])) { ?>
                                    <div class="row" style="margin-bottom: 15px;">
                                        <div class="col-6">
                                            <div class="col-md-2">
                                                <label>Active</label>
                                            </div>
                                            <div class="col-md-4">
                                                <label><input type="radio" name="ACTIVE" id="ACTIVE" value="1" <? if($ACTIVE == 1) echo 'checked="checked"'; ?> />&nbsp;Yes</label>&nbsp;&nbsp;
                                                <label><input type="radio" name="ACTIVE" id="ACTIVE" value="0" <? if($ACTIVE == 0) echo 'checked="checked"'; ?> />&nbsp;No</label>
                                            </div>
                                        </div>
                                    </div>
                                <?php } ?>

                                <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">Submit</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <style>

        .progress-bar {
            border-radius: 5px;
            height:18px !important;
        }
    </style>
    <?php require_once('../includes/footer.php');?>
    <script>
        function previewFile(input){
            let file = $("#USER_IMAGE").get(0).files[0];
            if(file){
                let reader = new FileReader();
                reader.onload = function(){
                    $("#profile-img").attr("src", reader.result);
                }
                reader.readAsDataURL(file);
            }
        }
    </script>
    <script>
        function removeThis(param) {
            $(param).closest('.row').remove();
        }

        function addMoreSize(){
            $('#add_more_size').append(`<div class="row">
                                            <div class="col-10">
                                                <div class="form-group">
                                                    <div class="col-md-12">
                                                        <input type="text" name="PRODUCT_SIZE[]" class="form-control" placeholder="Enter Size/Dimensions">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-2" style="padding-top: 25px;">
                                                <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                            </div>
                                        </div>`);
        }
        function addMoreColor(){
            $('#add_more_color').append(`<div class="row">
                                            <div class="col-10">
                                                <div class="form-group">
                                                    <div class="col-md-12">
                                                        <input type="text" name="PRODUCT_COLOR[]" class="form-control" placeholder="Enter Color">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-2" style="padding-top: 25px;">
                                                <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                            </div>
                                        </div>`);
        }
    </script>
</body>
</html>