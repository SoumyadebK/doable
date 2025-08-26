<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];

if (empty($_GET['id']))
    $title = "Add Package";
else
    $title = "Edit Package";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5]) ){
    header("location:../login.php");
    exit;
}

if(empty($_GET['id'])){
    $PACKAGE_NAME = '';
    $PK_LOCATION = '';
    $SORT_ORDER = '';
    $EXPIRY_DATE = '';
    $ACTIVE = '';
} else {
    $res = $db_account->Execute("SELECT * FROM `DOA_PACKAGE` WHERE `PK_PACKAGE` = '$_GET[id]'");

    if($res->RecordCount() == 0){
        header("location:all_packages.php");
        exit;
    }

    $PACKAGE_NAME = $res->fields['PACKAGE_NAME'];
    $PK_LOCATION = $res->fields['PK_LOCATION'];
    $SORT_ORDER = $res->fields['SORT_ORDER'];
    $EXPIRY_DATE = $res->fields['EXPIRY_DATE'];
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
                                                <div class="col-4">
                                                    <div class="form-group">
                                                        <label class="form-label">Package Name<span class="text-danger">*</span></label>
                                                        <input type="text" id="PK_PACKAGE" name="PACKAGE_NAME" class="form-control" placeholder="Enter Package name" required value="<?php echo $PACKAGE_NAME?>">
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <label class="form-label">Location</label>
                                                    <div>
                                                        <div class="form-group">
                                                            <select class="form-control PK_LOCATION" name="PK_LOCATION">
                                                                <option value="">Select</option>
                                                                <?php
                                                                $row = $db->Execute("SELECT * FROM DOA_LOCATION WHERE ACTIVE = 1 AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
                                                                while (!$row->EOF) { ?>
                                                                    <option value="<?php echo $row->fields['PK_LOCATION']; ?>" <?= ($PK_LOCATION == $row->fields['PK_LOCATION']) ? 'selected' : '' ?>><?= $row->fields['LOCATION_NAME'] ?></option>
                                                                <?php $row->MoveNext();
                                                                } ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="form-group">
                                                        <label class="form-label">Sort Order</label>
                                                        <input type="text" id="SORT_ORDER" name="SORT_ORDER" class="form-control" placeholder="Enter Sort Order" required value="<?php echo $SORT_ORDER?>">
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
                                                        <div class="col-2">
                                                            <div class="form-group">
                                                                <label class="form-label">Service Details</label>
                                                            </div>
                                                        </div>
                                                        <div class="col-1">
                                                            <div class="form-group">
                                                                <label class="form-label">Number of Sessions</label>
                                                            </div>
                                                        </div>
                                                        <div class="col-1">
                                                            <div class="form-group">
                                                                <label class="form-label">Price Per Sessions</label>
                                                            </div>
                                                        </div>
                                                        <div class="col-1">
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
                                                        <div class="col-2">
                                                            <div class="form-group">
                                                                <label class="form-label">Final Amount</label>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <?php
                                                    if(!empty($_GET['id'])) {
                                                        $package_service_data = $db_account->Execute("SELECT * FROM DOA_PACKAGE_SERVICE WHERE PK_PACKAGE = '$_GET[id]'");
                                                        while (!$package_service_data->EOF) { ?>
                                                            <div class="row">
                                                                <div class="col-2">
                                                                    <div class="form-group">
                                                                        <select class="form-control PK_SERVICE_MASTER" name="PK_SERVICE_MASTER[]" onchange="selectThisService(this)">
                                                                            <option>Select Service</option>
                                                                            <?php
                                                                            $row = $db_account->Execute("SELECT DISTINCT DOA_SERVICE_MASTER.PK_SERVICE_MASTER, DOA_SERVICE_MASTER.SERVICE_NAME, DOA_SERVICE_MASTER.DESCRIPTION, DOA_SERVICE_MASTER.ACTIVE FROM `DOA_SERVICE_MASTER` JOIN DOA_SERVICE_LOCATION ON DOA_SERVICE_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_LOCATION.PK_SERVICE_MASTER WHERE DOA_SERVICE_LOCATION.PK_LOCATION IN (".$DEFAULT_LOCATION_ID.") AND ACTIVE = 1 AND IS_DELETED = 0");
                                                                            while (!$row->EOF) { ?>
                                                                                <option value="<?php echo $row->fields['PK_SERVICE_MASTER'];?>" <?=($row->fields['PK_SERVICE_MASTER'] == $package_service_data->fields['PK_SERVICE_MASTER'])?'selected':''?>><?=$row->fields['SERVICE_NAME']?></option>
                                                                                <?php $row->MoveNext(); } ?>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                                <div class="col-1">
                                                                    <div class="form-group">
                                                                        <select class="form-control PK_SERVICE_CODE" name="PK_SERVICE_CODE[]" onchange="selectThisServiceCode(this)">
                                                                            <?php
                                                                            $row = $db_account->Execute("SELECT * FROM `DOA_SERVICE_CODE` WHERE `PK_SERVICE_MASTER` = ".$package_service_data->fields['PK_SERVICE_MASTER']);
                                                                            while (!$row->EOF) { ?>
                                                                                <option value="<?php echo $row->fields['PK_SERVICE_CODE'];?>" data-details="<?=$row->fields['DESCRIPTION']?>" data-price="<?=$row->fields['PRICE']?>" <?=($row->fields['PK_SERVICE_CODE'] == $package_service_data->fields['PK_SERVICE_CODE'])?'selected':''?>><?=$row->fields['SERVICE_CODE']?></option>
                                                                            <?php $row->MoveNext(); } ?>
                                                                        </select>
                                                                    </div>
                                                                </div>
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
                                                                <div class="col-1">
                                                                    <div class="form-group">
                                                                        <input type="text" class="form-control PRICE_PER_SESSION" name="PRICE_PER_SESSION[]" value="<?=$package_service_data->fields['PRICE_PER_SESSION']?>" onkeyup="calculateServiceTotal(this);">
                                                                    </div>
                                                                </div>
                                                                <div class="col-1">
                                                                    <div class="form-group">
                                                                        <input type="text" class="form-control TOTAL" name="TOTAL[]" value="<?=$package_service_data->fields['TOTAL']?>" readonly>
                                                                    </div>
                                                                </div>
                                                                <div class="col-1">
                                                                    <div class="form-group">
                                                                        <select class="form-control DISCOUNT_TYPE" name="DISCOUNT_TYPE[]" onchange="calculateServiceTotal(this)">
                                                                            <option value="">Select</option>
                                                                            <option value="1" <?=($package_service_data->fields['DISCOUNT_TYPE'] == 1)?'selected':''?>>Fixed</option>
                                                                            <option value="2" <?=($package_service_data->fields['DISCOUNT_TYPE'] == 2)?'selected':''?>>Percent</option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                                <div class="col-1">
                                                                    <div class="form-group">
                                                                        <input type="text" class="form-control DISCOUNT" name="DISCOUNT[]" value="<?=$package_service_data->fields['DISCOUNT']?>" onkeyup="calculateServiceTotal(this)">
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
                                                                <div class="col-2">
                                                                    <div class="form-group">
                                                                        <select class="form-control PK_SERVICE_MASTER" name="PK_SERVICE_MASTER[]" onchange="selectThisService(this)">
                                                                            <option>Select Service</option>
                                                                            <?php
                                                                            $row = $db_account->Execute("SELECT DISTINCT DOA_SERVICE_MASTER.PK_SERVICE_MASTER, DOA_SERVICE_MASTER.SERVICE_NAME, DOA_SERVICE_MASTER.DESCRIPTION, DOA_SERVICE_MASTER.ACTIVE FROM `DOA_SERVICE_MASTER` JOIN DOA_SERVICE_LOCATION ON DOA_SERVICE_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_LOCATION.PK_SERVICE_MASTER WHERE DOA_SERVICE_LOCATION.PK_LOCATION IN (".$DEFAULT_LOCATION_ID.") AND ACTIVE = 1 AND IS_DELETED = 0");
                                                                            while (!$row->EOF) { ?>
                                                                                <option value="<?php echo $row->fields['PK_SERVICE_MASTER'];?>"><?=$row->fields['SERVICE_NAME']?></option>
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
                                                                <div class="col-1">
                                                                    <div class="form-group">
                                                                        <input type="text" class="form-control NUMBER_OF_SESSION" name="NUMBER_OF_SESSION[]" onkeyup="calculateServiceTotal(this)">
                                                                    </div>
                                                                </div>
                                                                <div class="col-1">
                                                                    <div class="form-group">
                                                                        <input type="text" class="form-control PRICE_PER_SESSION" name="PRICE_PER_SESSION[]" onkeyup="calculateServiceTotal(this);">
                                                                    </div>
                                                                </div>
                                                                <div class="col-1">
                                                                    <div class="form-group">
                                                                        <input type="text" class="form-control TOTAL" name="TOTAL[]" readonly>
                                                                    </div>
                                                                </div>
                                                                <div class="col-1">
                                                                    <div class="form-group">
                                                                        <select class="form-control DISCOUNT_TYPE" name="DISCOUNT_TYPE[]" onchange="calculateServiceTotal(this)">
                                                                            <option value="">Select</option>
                                                                            <option value="1">Fixed</option>
                                                                            <option value="2">Percent</option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                                <div class="col-1">
                                                                    <div class="form-group">
                                                                        <input type="text" class="form-control DISCOUNT" name="DISCOUNT[]" onkeyup="calculateServiceTotal(this)">
                                                                    </div>
                                                                </div>
                                                                <div class="col-1">
                                                                    <div class="form-group">
                                                                        <input type="text" class="form-control FINAL_AMOUNT" name="FINAL_AMOUNT[]" readonly>
                                                                    </div>
                                                                </div>
                                                                <div class="col-1">
                                                                    <div class="form-group">
                                                                        <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php } ?>

                                                    <div id="append_service_div" style="margin-top: 0px">

                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-10">
                                                        <div class="form-group" style="float: right;">
                                                            <select class="form-control" name="EXPIRY_DATE" id="EXPIRY_DATE">
                                                                <option value="">Select Expiration Date</option>
                                                                <option value="30" <?=($EXPIRY_DATE == 30)?'selected':''?>>30 days</option>
                                                                <option value="60" <?=($EXPIRY_DATE == 60)?'selected':''?>>60 days</option>
                                                                <option value="90" <?=($EXPIRY_DATE == 90)?'selected':''?>>90 days</option>
                                                                <option value="180" <?=($EXPIRY_DATE == 180)?'selected':''?>>180 days</option>
                                                                <option value="365" <?=($EXPIRY_DATE == 365)?'selected':''?>>365 days</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-2">
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
                                                                <label><input type="radio" name="ACTIVE" id="ACTIVE" value="1" <?php if($ACTIVE == 1) echo 'checked="checked"'; ?> />&nbsp;Yes</label>&nbsp;&nbsp;
                                                                <label><input type="radio" name="ACTIVE" id="ACTIVE" value="0" <?php if($ACTIVE == 0) echo 'checked="checked"'; ?> />&nbsp;No</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php } ?>

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

    $('.multi_sumo_select').SumoSelect({placeholder: 'Select Location', selectAll: true});

    function selectThisService(param) {
        let PK_SERVICE_MASTER = $(param).val();
        $.ajax({
            url: "ajax/get_service_codes.php",
            type: "POST",
            data: {PK_SERVICE_MASTER: PK_SERVICE_MASTER},
            async: false,
            cache: false,
            success: function (result) {
                $(param).closest('.row').find('.PK_SERVICE_CODE').empty();
                $(param).closest('.row').find('.PK_SERVICE_CODE').append(result);
            }
        });
    }

    function selectThisServiceCode(param) {
        let service_details = $(param).find(':selected').data('details');
        let price = $(param).find(':selected').data('price');

        $(param).closest('.row').find('.SERVICE_DETAILS').val(service_details);
        $(param).closest('.row').find('.PRICE_PER_SESSION').val(price);

        calculateServiceTotal(param);
    }

    function calculateServiceTotal(param) {
        let number_of_session = ($(param).closest('.row').find('.NUMBER_OF_SESSION').val() == '') ? 0 : $(param).closest('.row').find('.NUMBER_OF_SESSION').val();
        let service_price = ($(param).closest('.row').find('.PRICE_PER_SESSION').val()) ?? 0;
        let TOTAL = parseFloat(number_of_session) * parseFloat(service_price);

        $(param).closest('.row').find('.TOTAL').val(parseFloat(TOTAL).toFixed(2));

        let DISCOUNT = ($(param).closest('.row').find('.DISCOUNT').val()) ?? 0;
        let DISCOUNT_TYPE = ($(param).closest('.row').find('.DISCOUNT_TYPE').val()) ?? 0;
        let FINAL_AMOUNT = parseFloat(TOTAL);
        if (DISCOUNT_TYPE == 1){
            FINAL_AMOUNT = parseFloat(TOTAL - DISCOUNT);
        } else {
            if (DISCOUNT_TYPE == 2) {
                FINAL_AMOUNT = parseFloat(TOTAL - (TOTAL * (DISCOUNT / 100)));
            }
        }
        $(param).closest('.row').find('.FINAL_AMOUNT').val(FINAL_AMOUNT.toFixed(2));
    }

    function addMoreServices() {
        $('#append_service_div').append(`<div class="row">
                                            <div class="col-2">
                                                <div class="form-group">
                                                    <select class="form-control PK_SERVICE_MASTER" name="PK_SERVICE_MASTER[]" onchange="selectThisService(this)">
                                                        <option>Select Service</option>
                                                        <?php
                                                        $row = $db_account->Execute("SELECT DISTINCT DOA_SERVICE_MASTER.PK_SERVICE_MASTER, DOA_SERVICE_MASTER.SERVICE_NAME, DOA_SERVICE_MASTER.DESCRIPTION, DOA_SERVICE_MASTER.ACTIVE FROM `DOA_SERVICE_MASTER` JOIN DOA_SERVICE_LOCATION ON DOA_SERVICE_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_LOCATION.PK_SERVICE_MASTER WHERE DOA_SERVICE_LOCATION.PK_LOCATION IN (".$DEFAULT_LOCATION_ID.") AND ACTIVE = 1 AND IS_DELETED = 0");
                                                        while (!$row->EOF) { ?>
                                                            <option value="<?php echo $row->fields['PK_SERVICE_MASTER'];?>"><?=$row->fields['SERVICE_NAME']?></option>
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
                                            <div class="col-1">
                                                <div class="form-group">
                                                    <input type="text" class="form-control NUMBER_OF_SESSION" name="NUMBER_OF_SESSION[]" onkeyup="calculateServiceTotal(this)">
                                                </div>
                                            </div>
                                            <div class="col-1">
                                                <div class="form-group">
                                                    <input type="text" class="form-control PRICE_PER_SESSION" name="PRICE_PER_SESSION[]" onkeyup="calculateServiceTotal(this);">
                                                </div>
                                            </div>
                                            <div class="col-1">
                                                <div class="form-group">
                                                    <input type="text" class="form-control TOTAL" name="TOTAL[]" readonly>
                                                </div>
                                            </div>
                                            <div class="col-1">
                                                <div class="form-group">
                                                    <select class="form-control DISCOUNT_TYPE" name="DISCOUNT_TYPE[]" onchange="calculateServiceTotal(this)">
                                                        <option value="">Select</option>
                                                        <option value="1">Fixed</option>
                                                        <option value="2">Percent</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-1">
                                                <div class="form-group">
                                                    <input type="text" class="form-control DISCOUNT" name="DISCOUNT[]" onkeyup="calculateServiceTotal(this)">
                                                </div>
                                            </div>
                                            <div class="col-1">
                                                <div class="form-group">
                                                    <input type="text" class="form-control FINAL_AMOUNT" name="FINAL_AMOUNT[]" readonly>
                                                </div>
                                            </div>
                                            <div class="col-1">
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
                window.location.href = 'all_packages.php';
            }
        });
    });


</script>
</body>
</html>