<?php
require_once('../global/config.php');

if (empty($_GET['id']))
    $title = "Add Package";
else
    $title = "Edit Package";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 2 ){
    header("location:../login.php");
    exit;
}

if(empty($_GET['id'])){
    $PACKAGE_NAME = '';
    $PK_SERVICE_CLASS = '';
    $IS_SCHEDULE = 1;
    $DESCRIPTION = '';
    $ACTIVE = '';
    $IS_PACKAGE = '';
    $TOTAL = '';
    $DISCOUNT_TYPE = '';
    $DISCOUNT = '';
    $FINAL_AMOUNT = '';
} else {
    $res = $db_account->Execute("SELECT * FROM `DOA_PACKAGE_SERVICE` JOIN DOA_PACKAGE ON DOA_PACKAGE_SERVICE.PK_PACKAGE=DOA_PACKAGE.PK_PACKAGE WHERE DOA_PACKAGE_SERVICE.`PK_PACKAGE` = '$_GET[id]'");

    if($res->RecordCount() == 0){
        header("location:all_packages.php");
        exit;
    }

    $PACKAGE_NAME = $res->fields['PACKAGE_NAME'];
    $PK_SERVICE_MASTER = $res->fields['PK_SERVICE_MASTER'];
    $PK_SERVICE_CODE = $res->fields['PK_SERVICE_CODE'];
    $SERVICE_DETAILS = $res->fields['SERVICE_DETAILS'];
    $NUMBER_OF_SESSION = $res->fields['NUMBER_OF_SESSION'];
    $PRICE_PER_SESSION = $res->fields['PRICE_PER_SESSION'];
    $TOTAL = $res->fields['TOTAL'];
    $DISCOUNT_TYPE = $res->fields['DISCOUNT_TYPE'];
    $DISCOUNT = $res->fields['DISCOUNT'];
    $FINAL_AMOUNT = $res->fields['FINAL_AMOUNT'];
    $ACTIVE = $res->fields['ACTIVE'];
}

?>

<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/html">
<?php require_once('../includes/header.php');?>
<link href="../assets/sumoselect/sumoselect.min.css" rel="stylesheet"/>
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
                            <li class="breadcrumb-item"><a href="all_packages.php">All Packages</a></li>
                            <li class="breadcrumb-item active"><?=$title?></li>
                        </ol>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-title" style="margin-top: 15px; margin-left: 15px;">
                            <?php
                            if(!empty($_GET['id'])) {
                                echo $PACKAGE_NAME;
                            }
                            ?>
                        </div>
                        <div class="card-body">
                            <!-- Tab panes -->
                            <div class="tab-content tabcontent-border">
                                <div class="tab-pane active" id="service_info" role="tabpanel">
                                    <form class="form-material form-horizontal" id="package_info_form">
                                        <input type="hidden" name="FUNCTION_NAME" value="savePackageInfoData">
                                        <input type="hidden" name="PK_PACKAGE" class="PK_PACKAGE" value="<?=(empty($_GET['id']))?'':$_GET['id']?>">
                                        <div class="p-20">
                                            <div class="row">
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="form-label">Package Name<span class="text-danger">*</span></label>
                                                        <input type="text" id="PK_PACKAGE" name="PACKAGE_NAME" class="form-control" placeholder="Enter Package name" required value="<?php echo $PACKAGE_NAME?>">
                                                    </div>
                                                </div>

                                                <div class="card-body">
                                                    <div class="row">
                                                        <div class="col-2">
                                                            <div class="form-group">
                                                                <label class="form-label">Services</label>
                                                            </div>
                                                        </div>
                                                        <div class="col-1">
                                                            <div class="form-group">
                                                                <label class="form-label">Service Codes</label>
                                                            </div>
                                                        </div>
                                                        <div class="col-2" style="text-align: center;">
                                                            <div class="form-group">
                                                                <label class="form-label">Service Details</label>
                                                            </div>
                                                        </div>
                                                        <div class="col-1 session_div">
                                                            <div class="form-group">
                                                                <label class="form-label">Number of Sessions</label>
                                                            </div>
                                                        </div>
                                                        <div class="col-1 session_div">
                                                            <div class="form-group">
                                                                <label class="form-label">Price Per Sessions</label>
                                                            </div>
                                                        </div>
                                                        <div class="col-1 frequency_div" style="display: none; text-align: center;">
                                                            <div class="form-group">
                                                                <label class="form-label">Frequency</label>
                                                            </div>
                                                        </div>
                                                        <div class="col-1" style="width: 11%; text-align: center;">
                                                            <div class="form-group">
                                                                <label class="form-label">Total</label>
                                                            </div>
                                                        </div>
                                                        <div class="col-1">
                                                            <div class="form-group">
                                                                <label class="form-label">Discount Type</label>
                                                            </div>
                                                        </div>
                                                        <div class="col-1">
                                                            <div class="form-group">
                                                                <label class="form-label">Discount</label>
                                                            </div>
                                                        </div>
                                                        <div class="col-1">
                                                            <div class="form-group">
                                                                <label class="form-label">Final Amount</label>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <?php
                                                    $PK_SERVICE_CLASS = 0;
                                                    if(!empty($_GET['id'])) {
                                                        $package_service_data = $db_account->Execute("SELECT * FROM DOA_PACKAGE_SERVICE WHERE PK_PACKAGE = '$_GET[id]'");

                                                        while (!$package_service_data->EOF) {
                                                            $service_class = $db_account->Execute("SELECT PK_SERVICE_CLASS FROM DOA_SERVICE_MASTER WHERE PK_SERVICE_MASTER = ".$package_service_data->fields['PK_SERVICE_MASTER']);
                                                            $PK_SERVICE_CLASS = $service_class->fields['PK_SERVICE_CLASS'];
                                                            ?>
                                                            <div class="row">
                                                                <div class="col-2">
                                                                    <div class="form-group">
                                                                        <select class="form-control PK_SERVICE_MASTER" name="PK_SERVICE_MASTER[]" onchange="selectThisService(this)">
                                                                            <option>Select</option>
                                                                            <?php
                                                                            $row = $db_account->Execute("SELECT DOA_SERVICE_MASTER.PK_SERVICE_MASTER, DOA_SERVICE_MASTER.SERVICE_NAME, DOA_SERVICE_MASTER.PK_SERVICE_CLASS FROM DOA_SERVICE_MASTER JOIN DOA_PACKAGE_SERVICE ON DOA_SERVICE_MASTER.PK_SERVICE_MASTER=DOA_PACKAGE_SERVICE.PK_SERVICE_MASTER ");
                                                                            while (!$row->EOF) { ?>
                                                                                <option value="<?php echo $row->fields['PK_SERVICE_MASTER'];?>" data-service_class="<?=$row->fields['PK_SERVICE_CLASS']?>" data-service_code="<?=$package_service_data->fields['PK_SERVICE_CODE']?>" data-is_package="<?=$row->fields['IS_PACKAGE']?>" <?=($row->fields['PK_SERVICE_MASTER'] == $package_service_data->fields['PK_SERVICE_MASTER'])?'selected':''?>><?=$row->fields['SERVICE_NAME']?></option>
                                                                                <?php $row->MoveNext(); } ?>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                                <div class="col-1">
                                                                    <div class="form-group">
                                                                        <select class="form-control PK_SERVICE_CODE" name="PK_SERVICE_CODE[]" onchange="selectThisServiceCode(this)">
                                                                            <option value="">Select</option>
                                                                            <?php
                                                                            $row = $db->Execute("SELECT $account_database.DOA_SERVICE_CODE.*, DOA_FREQUENCY.FREQUENCY FROM $account_database.DOA_SERVICE_CODE LEFT JOIN DOA_FREQUENCY ON $account_database.DOA_SERVICE_CODE.PK_FREQUENCY = DOA_FREQUENCY.PK_FREQUENCY WHERE PK_SERVICE_MASTER = ".$package_service_data->fields['PK_SERVICE_MASTER']);
                                                                            while (!$row->EOF) { ?>
                                                                                <option value="<?php echo $row->fields['PK_SERVICE_CODE'];?>" data-service_details="<?=$row->fields['DESCRIPTION']?>" data-frequency="<?=$row->fields['FREQUENCY']?>" data-price="<?=$row->fields['PRICE']?>" <?=($row->fields['PK_SERVICE_CODE'] == $package_service_data->fields['PK_SERVICE_CODE'])?'selected':''?>><?=$row->fields['SERVICE_CODE']?></option>
                                                                                <?php $row->MoveNext(); } ?>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                                <?php if($PK_SERVICE_CLASS == 1){ ?>
                                                                    <div class="col-1">
                                                                        <div class="form-group">
                                                                            <input type="text" class="form-control FREQUENCY" name="FREQUENCY[]" value="<?=$package_service_data->fields['FREQUENCY']?>" readonly>
                                                                        </div>
                                                                    </div>
                                                                <?php }elseif($PK_SERVICE_CLASS == 2){ ?>
                                                                    <div class="col-2">
                                                                        <div class="form-group">
                                                                            <input type="text" class="form-control SERVICE_DETAILS" name="SERVICE_DETAILS[]" value="<?=$package_service_data->fields['SERVICE_DETAILS']?>">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-1">
                                                                        <div class="form-group">
                                                                            <input type="text" class="form-control NUMBER_OF_SESSION" name="NUMBER_OF_SESSION[]" value="<?=$package_service_data->fields['NUMBER_OF_SESSION']?>" onkeyup="calculateServiceTotal(this)">
                                                                        </div>
                                                                    </div>
                                                                <?php }elseif($PK_SERVICE_CLASS == 0){ ?>
                                                                    <div class="col-1">
                                                                        <div class="form-group">
                                                                            <input type="text" class="form-control SERVICE_DETAILS" name="SERVICE_DETAILS[]" value="<?=$package_service_data->fields['SERVICE_DETAILS']?>">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-1">
                                                                        <div class="form-group">
                                                                            <input type="text" class="form-control NUMBER_OF_SESSION" name="NUMBER_OF_SESSION[]" value="<?=$package_service_data->fields['NUMBER_OF_SESSION']?>" onkeyup="calculateServiceTotal(this)">
                                                                        </div>
                                                                    </div>
                                                                <?php } ?>

                                                                <div class="col-1">
                                                                    <div class="form-group">
                                                                        <input type="text" class="form-control PRICE_PER_SESSION" name="PRICE_PER_SESSION[]" value="<?=$package_service_data->fields['PRICE_PER_SESSION']?>" onkeyup="calculateServiceTotal(this);">
                                                                    </div>
                                                                </div>
                                                                <div class="col-1" style="width: 11%;">
                                                                    <div class="form-group">
                                                                        <input type="text" class="form-control TOTAL" value="<?=$package_service_data->fields['TOTAL']?>" name="TOTAL[]">
                                                                    </div>
                                                                </div>
                                                                <div class="col-1">
                                                                    <div class="form-group">
                                                                        <select class="form-control DISCOUNT_TYPE" name="DISCOUNT_TYPE[]" onchange="calculateDiscount(this)">
                                                                            <option value="">Select</option>
                                                                            <option value="1" <?=($package_service_data->fields['DISCOUNT_TYPE'] == 1)?'selected':''?>>Fixed</option>
                                                                            <option value="2" <?=($package_service_data->fields['DISCOUNT_TYPE'] == 2)?'selected':''?>>Percent</option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                                <div class="col-1">
                                                                    <div class="form-group">
                                                                        <input type="text" class="form-control DISCOUNT" name="DISCOUNT[]" value="<?=$package_service_data->fields['DISCOUNT']?>" onkeyup="calculateDiscount(this)">
                                                                    </div>
                                                                </div>
                                                                <div class="col-1">
                                                                    <div class="form-group">
                                                                        <input type="text" class="form-control FINAL_AMOUNT" name="FINAL_AMOUNT[]" value="<?=$package_service_data->fields['FINAL_AMOUNT']?>" readonly>
                                                                    </div>
                                                                </div>
                                                                <div class="col-1" style="width: 5%;">
                                                                    <div class="form-group">
                                                                        <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <?php $package_service_data->MoveNext(); } ?>
                                                    <?php } else { ?>
                                                        <div class="row">
                                                            <div class="col-2 service_name">
                                                                <div class="form-group">
                                                                    <select class="form-control PK_SERVICE_MASTER" name="PK_SERVICE_MASTER[]" onchange="selectThisService(this)">
                                                                        <option>Select</option>
                                                                        <?php
                                                                        $row = $db_account->Execute("SELECT DISTINCT DOA_SERVICE_MASTER.PK_SERVICE_MASTER, DOA_SERVICE_MASTER.SERVICE_NAME, DOA_SERVICE_MASTER.PK_SERVICE_CLASS, DOA_SERVICE_MASTER.IS_PACKAGE FROM DOA_SERVICE_MASTER JOIN DOA_SERVICE_LOCATION ON DOA_SERVICE_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_LOCATION.PK_SERVICE_MASTER WHERE DOA_SERVICE_LOCATION.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]' AND ACTIVE = 1 ORDER BY SERVICE_NAME");
                                                                        while (!$row->EOF) { ?>
                                                                            <option value="<?php echo $row->fields['PK_SERVICE_MASTER'];?>" data-service_class="<?=$row->fields['PK_SERVICE_CLASS']?>" data-is_package="<?=$row->fields['IS_PACKAGE']?>"><?=$row->fields['SERVICE_NAME']?></option>
                                                                            <?php $row->MoveNext(); } ?>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="col-1 service_div">
                                                                <div class="form-group">
                                                                    <select class="form-control PK_SERVICE_CODE" name="PK_SERVICE_CODE[]" onchange="selectThisServiceCode(this)">
                                                                        <option value="">Select</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="col-2 service_div">
                                                                <div class="form-group">
                                                                    <input type="text" class="form-control SERVICE_DETAILS" name="SERVICE_DETAILS[]" >
                                                                </div>
                                                            </div>
                                                            <div class="col-1 frequency_div " style="display: none;">
                                                                <div class="form-group">
                                                                    <input type="text" class="form-control FREQUENCY" name="FREQUENCY[]" readonly>
                                                                </div>
                                                            </div>
                                                            <div class="col-1 session_div service_div">
                                                                <div class="form-group">
                                                                    <input type="text" class="form-control NUMBER_OF_SESSION" name="NUMBER_OF_SESSION[]" onkeyup="calculateServiceTotal(this)">
                                                                </div>
                                                            </div>
                                                            <div class="col-1 session_div service_div">
                                                                <div class="form-group">
                                                                    <input type="text" class="form-control PRICE_PER_SESSION" name="PRICE_PER_SESSION[]" onkeyup="calculateServiceTotal(this);">
                                                                </div>
                                                            </div>
                                                            <div class="col-1 service_div" style="width: 11%;">
                                                                <div class="form-group">
                                                                    <input type="text" class="form-control TOTAL" name="TOTAL[]">
                                                                </div>
                                                            </div>
                                                            <div class="col-1">
                                                                <div class="form-group">
                                                                    <select class="form-control DISCOUNT_TYPE" name="DISCOUNT_TYPE[]" onchange="calculateDiscount(this)">
                                                                        <option value="">Select</option>
                                                                        <option value="1" <?=($DISCOUNT_TYPE == 1)?'selected':''?>>Fixed</option>
                                                                        <option value="2" <?=($DISCOUNT_TYPE == 2)?'selected':''?>>Percent</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="col-1">
                                                                <div class="form-group">
                                                                    <input type="text" class="form-control DISCOUNT" name="DISCOUNT[]" value="<?=$DISCOUNT?>" onkeyup="calculateDiscount(this)">
                                                                </div>
                                                            </div>
                                                            <div class="col-1">
                                                                <div class="form-group">
                                                                    <input type="text" class="form-control FINAL_AMOUNT" name="FINAL_AMOUNT[]" value="<?=$FINAL_AMOUNT?>" readonly>
                                                                </div>
                                                            </div>
                                                            <div class="col-1 service_div" style="width: 5%;">
                                                                <div class="form-group">
                                                                    <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php } ?>

                                                    <div id="append_service_div" style="margin-top: 0px">

                                                    </div>
                                                </div>

                                                <div class="row" id="add_more">
                                                    <div class="col-12">
                                                        <div class="form-group" style="float: right;">
                                                            <a href="javascript:;" class="btn btn-info waves-effect waves-light m-r-10 text-white" onclick="addMoreServices();">Add More</a>
                                                        </div>
                                                    </div>
                                                </div>

                                            </div>

                                            <?php if(!empty($_GET['id'])) { ?>
                                                <div class="row" style="margin-bottom: 15px;">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Active</label>
                                                            <div class="col-md-12" style="padding: 8px;">
                                                                <label><input type="radio" name="ACTIVE" id="ACTIVE" value="1" <? if($ACTIVE == 1) echo 'checked="checked"'; ?> />&nbsp;Yes</label>&nbsp;&nbsp;
                                                                <label><input type="radio" name="ACTIVE" id="ACTIVE" value="0" <? if($ACTIVE == 0) echo 'checked="checked"'; ?> />&nbsp;No</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <? } ?>

                                            <div class="form-group">
                                                <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">Continue</button>
                                                <button type="button" id="cancel_button" class="btn btn-inverse waves-effect waves-light">Cancel</button>
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
<?php require_once('../includes/footer.php');?>
<script src="../assets/sumoselect/jquery.sumoselect.min.js"></script>

<script>
    let PK_SERVICE_MASTER = parseInt(<?=empty($_GET['id'])?0:$_GET['id']?>);

    function selectThisService(param) {
        let PK_SERVICE_MASTER = $(param).val();
        let SERVICE_CODE_RESULT = '';
        PK_SERVICE_CLASS = $(param).find(':selected').data('service_class');
        let SERVICE_CODE = ($(param).find(':selected').data('service_code'))?$(param).find(':selected').data('service_code'):0;
        $('.PK_SERVICE_CLASS').val(PK_SERVICE_CLASS);
        if (PK_SERVICE_CLASS === 1){
            $('.session_div').hide();
            $('.frequency_div').show();
        }else {
            if (PK_SERVICE_CLASS === 2){
                $('.session_div').show();
                $('.frequency_div').hide();
            }
        }

        if (SERVICE_CODE === 0) {
            $('#add_more').show();
            $.ajax({
                url: "ajax/get_service_codes.php",
                type: "POST",
                data: {PK_SERVICE_MASTER: PK_SERVICE_MASTER, SERVICE_CODE: SERVICE_CODE},
                async: false,
                cache: false,
                success: function (result) {
                    SERVICE_CODE_RESULT = result;
                    $(param).closest('.row').find('.PK_SERVICE_CODE').empty();
                    $(param).closest('.row').find('.PK_SERVICE_CODE').append(result);
                }
            });
        }
    }

    function selectThisServiceCode(param) {
        let service_details = $(param).find(':selected').data('service_details');
        let price = $(param).find(':selected').data('price');
        let frequency = $(param).find(':selected').data('frequency');
        $(param).closest('.row').find('.SERVICE_DETAILS').val(service_details);
        $(param).closest('.row').find('.PRICE_PER_SESSION').val(price);
        $(param).closest('.row').find('.FREQUENCY').val(frequency);
        PK_SERVICE_CLASS = $(param).closest('.row').find('.PK_SERVICE_MASTER').find(':selected').data('service_class');
        if (PK_SERVICE_CLASS === 1) {
            $(param).closest('.row').find('.NUMBER_OF_SESSION').val(1);
        }
        calculateServiceTotal(param);
    }

    function calculateServiceTotal(param) {
        let number_of_session = $(param).closest('.row').find('.NUMBER_OF_SESSION').val();
        number_of_session = (number_of_session)?number_of_session:0;
        let service_price = $(param).closest('.row').find('.PRICE_PER_SESSION').val();
        service_price = (service_price)?service_price:0;
        $(param).closest('.row').find('.TOTAL').val(parseFloat(parseFloat(service_price)* parseFloat(number_of_session)).toFixed(2));
    }

    function addMoreServices() {
        $('#append_service_div').append(`<div class="row">
                                            <div class="col-2">
                                                <div class="form-group">
                                                    <select class="form-control PK_SERVICE_MASTER" name="PK_SERVICE_MASTER[]" onchange="selectThisService(this)">
                                                        <option>Select</option>
                                                        <?php
        $row = $db_account->Execute("SELECT PK_SERVICE_MASTER, SERVICE_NAME, PK_SERVICE_CLASS, IS_PACKAGE FROM DOA_SERVICE_MASTER WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]' AND ACTIVE = 1 ORDER BY SERVICE_NAME");
        while (!$row->EOF) { ?>
                                                            <option value="<?php echo $row->fields['PK_SERVICE_MASTER'];?>" data-service_class="<?=$row->fields['PK_SERVICE_CLASS']?>" data-is_package="<?=$row->fields['IS_PACKAGE']?>"><?=$row->fields['SERVICE_NAME']?></option>
                                                        <?php $row->MoveNext(); } ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-1">
                                                <div class="form-group">
                                                    <select class="form-control PK_SERVICE_CODE" name="PK_SERVICE_CODE[]" onchange="selectThisServiceCode(this)">
                                                        <option value="">Select</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-2">
                                                <div class="form-group">
                                                    <input type="text" class="form-control SERVICE_DETAILS" name="SERVICE_DETAILS[]" >
                                                </div>
                                            </div>
                                            <div class="col-1 frequency_div" style="display: none;">
                                                <div class="form-group">
                                                    <input type="text" class="form-control FREQUENCY" name="FREQUENCY[]" readonly>
                                                </div>
                                            </div>
                                            <div class="col-1 session_div">
                                                <div class="form-group">
                                                    <input type="text" class="form-control NUMBER_OF_SESSION" name="NUMBER_OF_SESSION[]" onkeyup="calculateServiceTotal(this)">
                                                </div>
                                            </div>
                                            <div class="col-1 session_div">
                                                <div class="form-group">
                                                    <input type="text" class="form-control PRICE_PER_SESSION" name="PRICE_PER_SESSION[]" onkeyup="calculateServiceTotal(this);">
                                                </div>
                                            </div>
                                            <div class="col-1" style="width: 11%;">
                                                <div class="form-group">
                                                    <input type="text" class="form-control TOTAL" name="TOTAL[]">
                                                </div>
                                            </div>
                                            <div class="col-1">
                                            <div class="form-group">
                                                                    <select class="form-control DISCOUNT_TYPE" name="DISCOUNT_TYPE[]" onchange="calculateDiscount(this)">
                                                                        <option value="">Select</option>
                                                                        <option value="1">Fixed</option>
                                                                        <option value="2">Percent</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="col-1">
                                                                <div class="form-group">
                                                                    <input type="text" class="form-control DISCOUNT" name="DISCOUNT[]" onkeyup="calculateDiscount(this)">
                                                                </div>
                                                            </div>
                                                            <div class="col-1">
                                                                <div class="form-group">
                                                                    <input type="text" class="form-control FINAL_AMOUNT" name="FINAL_AMOUNT[]" readonly>
                                                                </div>
                                                            </div>
                                            <div class="col-1" style="width: 5%;">
                                                <div class="form-group">
                                                    <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                                </div>
                                            </div>
                                        </div>`);
    }

    function removeThis(param) {
        $(param).closest('.row').remove();
    }

    $(document).on('click', '#cancel_button', function () {
        window.location.href='all_packages.php'
    });


    $(document).on('submit', '#package_info_form', function (event) {
        event.preventDefault();
        let form_data = $('#package_info_form').serialize();
        $.ajax({
            url: "ajax/AjaxFunctions.php",
            type: 'POST',
            data: form_data,
            success:function (data) {
                if (PK_SERVICE_MASTER == 0) {
                    $('.PK_SERVICE_MASTER').val(data);
                    window.location.href = 'all_packages.php';
                } else {
                    window.location.href = 'all_packages.php';
                }
            }
        });
    });

    function calculateDiscount(param) {
        let DISCOUNT = $(param).closest('.row').find('.DISCOUNT').val();
        let DISCOUNT_TYPE = $(param).closest('.row').find('.DISCOUNT_TYPE').val();
        let TOTAL = $(param).closest('.row').find('.TOTAL').val();

        if (DISCOUNT_TYPE == 1){
            let FINAL_AMOUNT = parseFloat(TOTAL-DISCOUNT);
            $(param).closest('.row').find('.FINAL_AMOUNT').val(FINAL_AMOUNT.toFixed(2));
        } else {
            if (DISCOUNT_TYPE == 2) {
                let FINAL_AMOUNT = parseFloat(TOTAL - (TOTAL * (DISCOUNT / 100)));
                $(param).closest('.row').find('.FINAL_AMOUNT').val(FINAL_AMOUNT.toFixed(2));
            }
        }
        let TOTAL_AMOUNT = 0;
        $('.FINAL_AMOUNT').each(function () {
            TOTAL_AMOUNT += parseFloat($(this).val());
        });
        $('#total_bill').val(parseFloat(TOTAL_AMOUNT).toFixed(2));
        $('#BALANCE_PAYABLE').val(parseFloat(TOTAL_AMOUNT).toFixed(2));
    }
</script>
</body>
</html>