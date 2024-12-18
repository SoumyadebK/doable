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

$PRODUCT_DATA = $_POST;
if(!empty($_POST)){
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
    header("location:all_products.php");
}

if(!empty($_GET['id'])){
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
    $res = $db_account->Execute("SELECT * FROM `DOA_PRODUCT` WHERE `PK_PRODUCT` = '15'");
    if($res->RecordCount() == 0){
        header("location:all_products.php");
        exit;
    }
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
                <div class="col-md-2 align-self-end">
                    <?php if(empty($_GET['id'])) { ?>
                        <select required class="form-control" name="NAME" id="NAME" onchange="fetchAddress(this);">
                            <option value="">Select Customer</option>
                            <?php
                            $row = $db->Execute("SELECT DOA_USERS.PK_USER, DOA_USER_MASTER.PK_USER_MASTER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_NAME, DOA_USERS.ADDRESS, DOA_USERS.ADDRESS_1, DOA_USERS.PK_COUNTRY, DOA_USERS.PK_STATES, DOA_USERS.CITY, DOA_USERS.ZIP FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER WHERE DOA_USER_MASTER.PRIMARY_LOCATION_ID IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_USERS.ACTIVE = 1 AND DOA_USERS.IS_DELETED = 0 AND DOA_USER_ROLES.PK_ROLES = 4 AND DOA_USER_MASTER.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER']." ORDER BY DOA_USERS.FIRST_NAME");
                            $country = $db->Execute("SELECT COUNTRY_NAME FROM DOA_COUNTRY WHERE PK_COUNTRY = ".$row->fields['PK_COUNTRY']);
                            $state = $db->Execute("SELECT STATE_NAME FROM DOA_STATES WHERE PK_STATES = ".$row->fields['PK_STATES']);
                            while (!$row->EOF) {?>
                                <option value="<?php echo $row->fields['PK_USER'];?>" data-master_id="<?php echo $row->fields['PK_USER_MASTER'];?>" data-address="<?php echo $row->fields['ADDRESS']?>" data-address_1="<?php echo $row->fields['ADDRESS_1']?>" data-country="<?php echo $country->fields['COUNTRY_NAME']?>" data-state="<?php echo $row->fields['STATE_NAME']?>" data-city="<?php echo $row->fields['CITY']?>" data-zip="<?php echo $row->fields['ZIP']?>"><?=$row->fields['NAME'].' ('.$row->fields['USER_NAME'].')'?></option>
                                <?php $row->MoveNext(); } ?>
                        </select>
                    <?php } ?>
                </div>
                <!--<div class="col-md-7 align-self-center text-end">
                    <div class="d-flex justify-content-end align-items-center">
                        <ol class="breadcrumb justify-content-end">
                            <li class="breadcrumb-item"><a href="setup.php">Setup</a></li>
                            <li class="breadcrumb-item"><a href="all_products.php">All Products</a></li>
                            <li class="breadcrumb-item active"><?php /*=$title*/?></li>
                        </ol>
                    </div>
                </div>-->
            </div>

            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <form class="form-material form-horizontal" action="" method="post" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-2">
                                        <div class="form-group">
                                            <?php if($PRODUCT_IMAGES!=''){?>
                                                <div style="width: 180px;height: 180px;">
                                                <a class="fancybox" href="<?php echo $PRODUCT_IMAGES;?>" data-fancybox-group="gallery">
                                                    <img id="profile-img" src="<?php echo $PRODUCT_IMAGES;?>" style="width:180px; height:180px" /></a>
                                                </div><?php } ?>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="form-group">
                                            <div class="col-md-12" style="font-weight: bold; font-size: 20px">
                                                <?php echo $PRODUCT_ID." - ".$PRODUCT_NAME.", ".$PRODUCT_DESCRIPTION.", ".$BRAND.", ".$CATEGORY.", ".$SIZE.", ".$COLOR.", ".$WEIGHT?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-2">
                                        <div class="form-group">
                                            <label class="col-md-12" for="example-text" style="font-weight: bold;">Quantity<span class="text-danger">*</span></span>
                                            </label>
                                            <div class="col-md-12">
                                                <input type="text" id="QUANTITY" name="QUANTITY" class="form-control QUANTITY" onkeyup="calculateTotal(this)" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-2">
                                        <div class="form-group">
                                            <label class="col-md-12" for="example-text" style="font-weight: bold;">Price<span class="text-danger">*</span></span>
                                            </label>
                                            <div class="col-md-12">
                                                <input type="text" id="PRICE" name="PRICE" class="form-control PRICE" placeholder="Enter Price" value="<?php echo $PRICE?>" onkeyup="calculateTotal(this)" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-2">
                                        <div class="form-group">
                                            <label class="col-md-12" for="example-text" style="font-weight: bold;">Total</span>
                                            </label>
                                            <div class="col-md-12">
                                                <input type="text" id="TOTAL" name="TOTAL" class="form-control TOTAL" onkeyup="calculateTotal(this)" readonly>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
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
                                                    <input type="text" id="ADDRESS_1" name="ADDRESS_1" class="form-control" placeholder="Enter Address">

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
                                                                <option value="<?php echo $row->fields['PK_COUNTRY'];?>" <?=($row->fields['PK_COUNTRY'] == $PK_COUNTRY)?"selected":""?>><?=$row->fields['COUNTRY_NAME']?></option>
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
                                                        <div id="State_div"></div>
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

        function fetch_state(PK_COUNTRY){
            jQuery(document).ready(function() {
                let data = "PK_COUNTRY="+PK_COUNTRY+"&PK_STATES=<?=$PK_STATES;?>";
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
            let address = $('#NAME').find(':selected').data('address');
            let address_1 = $('#NAME').find(':selected').data('address_1');
            let city = $('#NAME').find(':selected').data('city');
            let zip = $('#NAME').find(':selected').data('zip');
            alert(address)
            $('#ADDRESS').val(address);
            $('#ADDRESS_1').val(address_1);
            $('#CITY').val(city);
            $('#ZIP').val(zip);
        }

        function calculateTotal(param) {
            let TOTAL = 0;
            let quantity = ($(param).closest('.row').find('.QUANTITY').val() == '') ? 0 : $(param).closest('.row').find('.QUANTITY').val();
            let price = ($(param).closest('.row').find('.PRICE').val()) ?? 0;
            TOTAL = parseFloat(quantity) * parseFloat(price);
            $(param).closest('.row').find('.TOTAL').val(parseFloat(TOTAL).toFixed(2));
        }
    </script>
</body>
</html>