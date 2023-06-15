<?php
require_once('../global/config.php');

if (empty($_GET['id']))
    $title = "Add Service";
else
    $title = "Edit Service";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 2 ){
    header("location:../login.php");
    exit;
}

if(!empty($_POST)){
    if (isset($_POST['PK_LOCATION'])){
        $res = $db->Execute("DELETE FROM `DOA_SERVICE_DOCUMENTS` WHERE PK_SERVICE_MASTER =  '$_GET[id]'");
        for($i = 0; $i < count($_POST['PK_LOCATION']); $i++){
            $SERVICE_DOCUMENT_DATA['PK_SERVICE_MASTER'] = $_GET['id'];
            $SERVICE_DOCUMENT_DATA['PK_LOCATION'] = $_POST['PK_LOCATION'][$i];
            if(!empty($_FILES['FILE_PATH']['name'][$i])){
                $extn 			= explode(".",$_FILES['FILE_PATH']['name'][$i]);
                $iindex			= count($extn) - 1;
                $rand_string 	= time()."-".rand(100000,999999);
                $file11			= 'service_document_'.$_SESSION['PK_USER'].$rand_string.".".$extn[$iindex];
                $extension   	= strtolower($extn[$iindex]);

                $image_path    = '../uploads/service_document/'.$file11;
                move_uploaded_file($_FILES['FILE_PATH']['tmp_name'][$i], $image_path);
                $SERVICE_DOCUMENT_DATA['FILE_PATH'] = $image_path;
            } else {
                $SERVICE_DOCUMENT_DATA['FILE_PATH'] = $_POST['FILE_PATH_URL'][$i];
            }

            if(empty($_GET['id'])){
                $SERVICE_DOCUMENT_DATA['ACTIVE'] = 1;
                $SERVICE_DOCUMENT_DATA['CREATED_BY']  = $_SESSION['PK_USER'];
                $SERVICE_DOCUMENT_DATA['CREATED_ON']  = date("Y-m-d H:i");
            }else{
                $SERVICE_DOCUMENT_DATA['EDITED_BY']	= $_SESSION['PK_USER'];
                $SERVICE_DOCUMENT_DATA['EDITED_ON'] = date("Y-m-d H:i");
            }
            db_perform('DOA_SERVICE_DOCUMENTS', $SERVICE_DOCUMENT_DATA, 'insert');
        }
    }
    header("location:all_services.php");
}

if(empty($_GET['id'])){
    $SERVICE_NAME = '';
    $PK_SERVICE_CLASS = '';
    $IS_SCHEDULE = 1;
    $DESCRIPTION = '';
    $ACTIVE = '';
    $IS_PACKAGE = '';
} else {
    $res = $db->Execute("SELECT * FROM `DOA_SERVICE_MASTER` WHERE `PK_SERVICE_MASTER` = '$_GET[id]'");

    if($res->RecordCount() == 0){
        header("location:all_services.php");
        exit;
    }

    $SERVICE_NAME = $res->fields['SERVICE_NAME'];
    $PK_SERVICE_CLASS = $res->fields['PK_SERVICE_CLASS'];
    $IS_SCHEDULE = $res->fields['IS_SCHEDULE'];
    $DESCRIPTION = $res->fields['DESCRIPTION'];
    $ACTIVE = $res->fields['ACTIVE'];
    $IS_PACKAGE = $res->fields['IS_PACKAGE'];
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
        <div class="container-fluid">
            <div class="row page-titles">
                <div class="col-md-5 align-self-center">
                    <h4 class="text-themecolor"><?=$title?></h4>
                </div>
                <div class="col-md-7 align-self-center text-end">
                    <div class="d-flex justify-content-end align-items-center">
                        <ol class="breadcrumb justify-content-end">
                            <li class="breadcrumb-item"><a href="setup.php">Setup</a></li>
                            <li class="breadcrumb-item"><a href="all_services.php">All Services</a></li>
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
                                echo $SERVICE_NAME;
                            }
                            ?>
                        </div>
                        <div class="card-body">
                            <!-- Nav tabs -->
                            <ul class="nav nav-tabs" role="tablist">
                                <li class="active"> <a class="nav-link active" data-bs-toggle="tab" href="#service_info" role="tab"><span class="hidden-sm-up"><i class="ti-info"></i></span> <span class="hidden-xs-down">Info</span></a> </li>
                                <li> <a class="nav-link" data-bs-toggle="tab" id="service_codes_link" href="#service_codes" role="tab" ><span class="hidden-sm-up"><i class="ti-receipt"></i></span> <span class="hidden-xs-down">Service Codes</span></a> </li>
                                <li> <a class="nav-link" data-bs-toggle="tab" id="service_document_link" href="#service_document" role="tab" ><span class="hidden-sm-up"><i class="ti-files"></i></span> <span class="hidden-xs-down">Service Document</span></a> </li>
                            </ul>

                            <!-- Tab panes -->
                            <div class="tab-content tabcontent-border">
                                <div class="tab-pane active" id="service_info" role="tabpanel">
                                    <form class="form-material form-horizontal" id="service_info_form">
                                        <input type="hidden" name="FUNCTION_NAME" value="saveServiceInfoData">
                                        <input type="hidden" name="PK_SERVICE_MASTER" class="PK_SERVICE_MASTER" value="<?=(empty($_GET['id']))?'':$_GET['id']?>">
                                        <div class="p-20">
                                            <div class="row">
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="form-label">Service Name<span class="text-danger">*</span></label>
                                                        <input type="text" id="SERVICE_NAME" name="SERVICE_NAME" class="form-control" placeholder="Enter Service name" required value="<?php echo $SERVICE_NAME?>">
                                                    </div>
                                                </div>

                                                <div class="col-2">
                                                    <label class="col-md-12 mt-3"><input type="checkbox" id="IS_PACKAGE" name="IS_PACKAGE" class="form-check-inline" <?=($IS_PACKAGE == 1)?'checked':''?> style="margin-top: 30px;" onchange="isPackage(this);"> Is Package ?</label>
                                                </div>

                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="form-label">Service Class</label>
                                                        <select class="form-control PK_SERVICE_CLASS" name="PK_SERVICE_CLASS" onchange="selectServiceClass(this)">
                                                            <option value="">Select</option>
                                                            <?php
                                                            $row = $db->Execute("SELECT * FROM DOA_SERVICE_CLASS WHERE ACTIVE = 1");
                                                            while (!$row->EOF) { ?>
                                                                <option value="<?php echo $row->fields['PK_SERVICE_CLASS'];?>" <?=($PK_SERVICE_CLASS == $row->fields['PK_SERVICE_CLASS'])?'selected':''?>><?=$row->fields['SERVICE_CLASS']?></option>
                                                            <?php $row->MoveNext(); } ?>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="col-6" id="schedule_div">
                                                    <div class="form-group">
                                                        <label class="form-label">Schedule</label><br>
                                                        <label><input type="radio" class="IS_SCHEDULE" name="IS_SCHEDULE" value="1" <?=($IS_SCHEDULE == 1)?'checked':''?>/>&nbsp;Yes</label>
                                                        <label class="m-l-40"><input type="radio" class="IS_SCHEDULE" name="IS_SCHEDULE" value="0" <?=($IS_SCHEDULE == 0)?'checked':''?>/>&nbsp;No</label>
                                                    </div>
                                                </div>

                                                <div class="col-12">
                                                    <div class="form-group">
                                                        <label class="form-label">Description</label>
                                                        <textarea class="form-control" rows="3" id="DESCRIPTION" name="DESCRIPTION"><?php echo $DESCRIPTION?></textarea>
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

                                <div class="tab-pane" id="service_codes" role="tabpanel">
                                    <form id="service_code_form">
                                        <input type="hidden" name="FUNCTION_NAME" value="saveServiceCodeData">
                                        <input type="hidden" name="PK_SERVICE_MASTER" class="PK_SERVICE_MASTER" value="<?=(empty($_GET['id']))?'':$_GET['id']?>">
                                        <div class="p-20">
                                            <div id="append_service_code">

                                                <div class="row align-items-end">
                                                    <div class="col-1" style="text-align: center;">
                                                        <div class="form-group">
                                                            <label>Service Code</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-1" style="text-align: center;">
                                                        <div class="form-group">
                                                            <label>Description</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-1" style="text-align: center;">
                                                        <div class="form-group">
                                                            <label>Booking Code</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-1" style="text-align: center;">
                                                        <div class="form-group">
                                                            <label id="frequency_duration_label"><?=($PK_SERVICE_CLASS==2)?'Duration':'Frequency'?></label>
                                                        </div>
                                                    </div>
                                                    <div class="col-2" style="text-align: center;">
                                                        <div class="form-group">
                                                            <label>Is Group</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-1" style="text-align: center;">
                                                        <div class="form-group">
                                                            <label>Capacity</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-2" style="text-align: center;">
                                                        <div class="form-group">
                                                            <label>Is Chargeable</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-1" style="text-align: center;">
                                                        <div class="form-group">
                                                            <label>Price</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-1" style="text-align: center;">
                                                        <div class="form-group">
                                                            <label id="number_of_sessions">Number of Sessions</label>
                                                        </div>
                                                    </div>
                                                </div>


                                                <?php
                                                if(!empty($_GET['id'])) { $i = 0;
                                                $row = $db->Execute("SELECT * FROM DOA_SERVICE_CODE WHERE PK_SERVICE_MASTER = '$_GET[id]'");
                                                while (!$row->EOF) { ?>
                                                    <input type="hidden" name="ALL_PK_SERVICE_CODE[]" value="<?=$row->fields['PK_SERVICE_CODE']?>">
                                                    <div class="row align-items-end">
                                                        <input type="hidden" name="PK_SERVICE_CODE[]" value="<?=$row->fields['PK_SERVICE_CODE']?>">
                                                        <div class="col-1">
                                                            <div class="form-group">
                                                                <input type="text" name="SERVICE_CODE[]" class="form-control" placeholder="Service Code" value="<?=$row->fields['SERVICE_CODE']?>">
                                                            </div>
                                                        </div>
                                                        <div class="col-1">
                                                            <div class="form-group">
                                                                <input type="text" name="SERVICE_CODE_DESCRIPTION[]" class="form-control" placeholder="Description" value="<?=$row->fields['DESCRIPTION']?>">
                                                            </div>
                                                        </div>
                                                        <div class="col-1" style=" margin-bottom: auto">
                                                            <div class="form-group multiselect-box">
                                                                <select class="multi_sumo_select PK_BOOKING_CODES" name="PK_BOOKING_CODES[<?=$i?>][]" multiple>
                                                                    <?php
                                                                    $selected_booking_code = [];
                                                                    if(!empty($_GET['id'])) {
                                                                        $selected_booking_code_row = $db->Execute("SELECT `PK_BOOKING_CODES` FROM `DOA_SERVICE_BOOKING_CODE` WHERE `PK_SERVICE_MASTER` = '$_GET[id]'");
                                                                        while (!$selected_booking_code_row->EOF) {
                                                                            $selected_booking_code[] = $selected_booking_code_row->fields['PK_BOOKING_CODES'];
                                                                            $selected_booking_code_row->MoveNext();
                                                                        }
                                                                    }
                                                                    $booking_row = $db->Execute("SELECT PK_BOOKING_CODES, BOOKING_NAME FROM DOA_BOOKING_CODES WHERE ACTIVE = 1 AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
                                                                    while (!$booking_row->EOF) { ?>
                                                                        <option value="<?php echo $booking_row->fields['PK_BOOKING_CODES'];?>" <?=in_array($booking_row->fields['PK_BOOKING_CODES'], $selected_booking_code)?"selected":""?>><?=$booking_row->fields['BOOKING_NAME']?></option>
                                                                        <?php $booking_row->MoveNext(); } ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="col-1 frequency_div" style="display: <?=($PK_SERVICE_CLASS!=1)?'none':''?>;">
                                                            <div class="form-group">
                                                                <select class="form-control PK_FREQUENCY" name="PK_FREQUENCY[]">
                                                                    <option value="">Select</option>
                                                                    <?php
                                                                    $frequencyRow = $db->Execute("SELECT * FROM DOA_FREQUENCY WHERE ACTIVE = 1");
                                                                    while (!$frequencyRow->EOF) { ?>
                                                                        <option value="<?php echo $frequencyRow->fields['PK_FREQUENCY'];?>" <?=($frequencyRow->fields['PK_FREQUENCY'] == $row->fields['PK_FREQUENCY'])?'selected':''?>><?=$frequencyRow->fields['FREQUENCY']?></option>
                                                                    <?php $frequencyRow->MoveNext(); } ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="col-1 duration_div" style="display: <?=($PK_SERVICE_CLASS!=2)?'none':''?>;">
                                                            <div class="form-group">
                                                                <div class="col-md-12">
                                                                    <input type="text" id="DURATION" name="DURATION[]" class="form-control" placeholder="Duration" value="<?=$row->fields['DURATION']?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-2" style="height: 20px;margin-bottom: 35px;padding-left: 5%;">
                                                            <div class="form-group justify-content-center">
                                                                <div class="col-md-12">
                                                                    <label><input type="radio" name="IS_GROUP_<?=$i?>" class="IS_GROUP" value="1" <?=(($row->fields['IS_GROUP'] == 1) ? 'checked' : '')?>/>&nbsp;Yes</label>&nbsp;&nbsp;&nbsp;&nbsp;
                                                                    <label><input type="radio" name="IS_GROUP_<?=$i?>" class="IS_GROUP" value="0"  <?=(($row->fields['IS_GROUP'] == 0) ? 'checked' : '')?>/>&nbsp;No</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-1">
                                                            <div class="form-group capacity_div" style="display: <?=(($row->fields['IS_GROUP'] == 0) ? 'none' : '')?>"">
                                                                <div class="col-md-12" >
                                                                    <input type="number" class="form-control" name="CAPACITY[]" id="CAPACITY" value="<?=$row->fields['CAPACITY']?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-2" style="height: 20px;margin-bottom: 35px;padding-left: 5%;">
                                                            <div class="form-group" >
                                                                <div class="col-md-12" style="margin-bottom: 10px">
                                                                    <label><input type="radio" name="IS_CHARGEABLE_<?=$i?>" class="IS_CHARGEABLE" value="1" <?=(($row->fields['IS_CHARGEABLE'] == 1) ? 'checked' : '')?>/>&nbsp;Yes</label>&nbsp;&nbsp;&nbsp;&nbsp;
                                                                    <label><input type="radio" name="IS_CHARGEABLE_<?=$i?>" class="IS_CHARGEABLE" value="0" <?=(($row->fields['IS_CHARGEABLE'] == 0) ? 'checked' : '')?>/>&nbsp;No</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-1">
                                                            <div class="form-group service_price" style="display: <?=(($row->fields['IS_CHARGEABLE'] == 0) ? 'none' : '')?>">
                                                                <div class="col-md-12" >
                                                                    <div class="input-group">
                                                                        <span class="input-group-text"><?=$currency?></span>
                                                                        <input type="text" id="PRICE" name="PRICE[]" class="form-control" placeholder="Price" value="<?=$row->fields['PRICE']?>">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                <div class="col-1">
                                                    <div class="form-group number_of_sessions_div" style="display: <?=(($IS_PACKAGE == 0) ? 'none' : '')?>">
                                                        <div class="col-md-12" >
                                                            <input type="number" class="form-control" name="NUMBER_OF_SESSIONS[]" id="NUMBER_OF_SESSIONS" value="<?=$row->fields['NUMBER_OF_SESSIONS']?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                        <div class="col-1">
                                                            <div class="form-group">
                                                                <a href="javascript:;" class="btn btn-danger waves-effect waves-light m-r-10 text-white" onclick="removeServiceCode(this);"><i class="ti-trash"></i></a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php $row->MoveNext(); $i++;} ?>
                                                <?php } else { $i = 1;?>
                                                    <div class="row align-items-end">
                                                        <input type="hidden" name="PK_SERVICE_CODE[]" value="0">
                                                        <div class="col-1">
                                                            <div class="form-group">
                                                                <input type="text" name="SERVICE_CODE[]" class="form-control" placeholder="Service Code">
                                                            </div>
                                                        </div>
                                                        <div class="col-1">
                                                            <div class="form-group">
                                                                <input type="text" name="SERVICE_CODE_DESCRIPTION[]" class="form-control" placeholder="Description">
                                                            </div>
                                                        </div>
                                                        <div class="col-1" style="margin-bottom: auto">
                                                            <div class="form-group multiselect-box">
                                                                <select class="multi_sumo_select PK_BOOKING_CODES" name="PK_BOOKING_CODES[0][]" multiple>
                                                                    <?php
                                                                    $booking_code = $db->Execute("SELECT * FROM DOA_BOOKING_CODES WHERE ACTIVE = 1");
                                                                    while (!$booking_code->EOF) { ?>
                                                                        <option value="<?php echo $booking_code->fields['PK_BOOKING_CODES'];?> <?=($booking_code->fields['PK_BOOKING_CODES'] == $row->fields['PK_BOOKING_CODES'])?'selected':''?>"><?=$booking_code->fields['BOOKING_NAME']?></option>
                                                                        <?php $booking_code->MoveNext(); } ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="col-1 frequency_div">
                                                            <div class="form-group">
                                                                <select class="form-control PK_FREQUENCY" name="PK_FREQUENCY[]">
                                                                    <option value="">Select</option>
                                                                    <?php
                                                                    $row = $db->Execute("SELECT * FROM DOA_FREQUENCY WHERE ACTIVE = 1");
                                                                    while (!$row->EOF) { ?>
                                                                        <option value="<?php echo $row->fields['PK_FREQUENCY'];?>"><?=$row->fields['FREQUENCY']?></option>
                                                                        <?php $row->MoveNext(); } ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="col-1 duration_div" style="display: none;">
                                                            <div class="form-group">
                                                                <div class="col-md-12">
                                                                    <input type="text" id="DURATION" name="DURATION[]" class="form-control" placeholder="Duration">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-2" style="height: 20px;margin-bottom: 35px;padding-left: 5%;">
                                                            <div class="form-group">
                                                                <div class="col-md-12">
                                                                    <label><input type="radio" name="IS_GROUP_0" class="IS_GROUP" value="1"/>&nbsp;Yes</label>&nbsp;&nbsp;&nbsp;&nbsp;
                                                                    <label><input type="radio" name="IS_GROUP_0" class="IS_GROUP" value="0" checked/>&nbsp;No</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-1">
                                                            <div class="form-group capacity_div">
                                                                <div class="col-md-12" >
                                                                    <input type="number" class="form-control" name="CAPACITY[]" id="CAPACITY">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-2" style="height: 20px;margin-bottom: 35px;padding-left: 5%;">
                                                            <div class="form-group" >
                                                                <div class="col-md-12" style="height: 20px;margin-bottom: 35px;padding-left: 90px;">
                                                                    <label><input type="radio" name="IS_CHARGEABLE_0" class="IS_CHARGEABLE" value="1" checked/>&nbsp;Yes</label>&nbsp;&nbsp;&nbsp;&nbsp;
                                                                    <label><input type="radio" name="IS_CHARGEABLE_0" class="IS_CHARGEABLE" value="0"/>&nbsp;No</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-1">
                                                            <div class="form-group service_price">
                                                                <div class="col-md-12" >
                                                                    <div class="input-group">
                                                                        <span class="input-group-text"><?=$currency?></span>
                                                                        <input type="text" id="PRICE" name="PRICE[]" class="form-control" placeholder="Price">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-1">
                                                            <div class="form-group number_of_sessions_div" style="display: <?=(($IS_PACKAGE == 0) ? 'none' : '')?>">
                                                                <div class="col-md-12" >
                                                                    <input type="number" class="form-control" name="NUMBER_OF_SESSIONS[]" id="NUMBER_OF_SESSIONS">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-1">
                                                            <div class="form-group">
                                                                <a href="javascript:;" class="btn btn-danger waves-effect waves-light m-r-10 text-white" onclick="removeServiceCode(this);"><i class="ti-trash"></i></a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php } ?>
                                            </div>
                                            <div class="row">
                                                <div class="form-group" style="margin-left: 92%">
                                                    <a href="javascript:;" class="btn btn-info waves-effect waves-light m-r-10 text-white" onclick="addMoreServiceCode();">Add More</a>
                                                </div>
                                            </div>

                                            <!--<div class="row form-group">
                                                <div>
                                                    <label class="form-label">Service Providers</label>
                                                </div>
                                                <div class="col-6">
                                                    <select class="multi_sumo_select" name="PK_USER[]" multiple>
                                                        <?php
/*                                                        $selected_service = [];
                                                        if(!empty($_GET['id'])) {
                                                            $selected_service_row = $db->Execute("SELECT `PK_USER` FROM `DOA_SERVICE_PROVIDER_SERVICE_NEW` WHERE `PK_SERVICE_MASTER` = '$_GET[id]'");
                                                            while (!$selected_service_row->EOF) {
                                                                $selected_service[] = $selected_service_row->fields['PK_USER'];
                                                                $selected_service_row->MoveNext();
                                                            }
                                                        }
                                                        $row = $db->Execute("SELECT PK_USER, CONCAT(FIRST_NAME, ' ', LAST_NAME) AS NAME FROM DOA_USERS WHERE PK_ROLES = 5 AND PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER']);
                                                        while (!$row->EOF) { */?>
                                                            <option value="<?php /*echo $row->fields['PK_USER'];*/?>" <?/*=in_array($row->fields['PK_USER'], $selected_service)?"selected":""*/?> ><?/*=$row->fields['NAME']*/?></option>
                                                            <?php /*$row->MoveNext(); } */?>
                                                    </select>
                                                </div>
                                            </div>-->

                                            <div class="form-group">
                                                <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">Continue</button>
                                                <button type="button" id="cancel_button" class="btn btn-inverse waves-effect waves-light">Cancel</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>

                                <div class="tab-pane" id="service_document" role="tabpanel">
                                    <form method="post" action="" enctype="multipart/form-data">
                                        <div class="p-20">
                                            <div class="card-body" id="append_service_document">
                                                <?php
                                                if(!empty($_GET['id'])) {
                                                    $service_document = $db->Execute("SELECT * FROM DOA_SERVICE_DOCUMENTS WHERE PK_SERVICE_MASTER = '$_GET[id]'");
                                                    while (!$service_document->EOF) { ?>
                                                        <div class="row">
                                                            <div class="col-5">
                                                                <div class="form-group">
                                                                    <label class="form-label">Location</label>
                                                                    <select class="form-control PK_LOCATION" name="PK_LOCATION[]">
                                                                        <option>Select Location</option>
                                                                        <?php
                                                                        $row = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME FROM DOA_LOCATION WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]' AND ACTIVE = 1 ORDER BY LOCATION_NAME");
                                                                        while (!$row->EOF) { ?>
                                                                            <option value="<?php echo $row->fields['PK_LOCATION'];?>" <?=($service_document->fields['PK_LOCATION'] == $row->fields['PK_LOCATION'])?'selected':''?>><?=$row->fields['LOCATION_NAME']?></option>
                                                                            <?php $row->MoveNext(); } ?>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="col-5">
                                                                <div class="form-group">
                                                                    <label class="form-label">Document File</label>
                                                                    <input type="file" name="FILE_PATH[]" class="form-control">
                                                                    <a target="_blank" href="<?=$service_document->fields['FILE_PATH']?>">View</a>
                                                                    <input type="hidden" name="FILE_PATH_URL[]" value="<?=$service_document->fields['FILE_PATH']?>">
                                                                </div>
                                                            </div>
                                                            <div class="col-2">
                                                                <div class="form-group" style="margin-top: 15px;">
                                                                    <a href="javascript:;" class="btn btn-danger waves-effect waves-light m-r-10 text-white" onclick="removeUserDocument(this);"><i class="ti-trash"></i></a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <?php $service_document->MoveNext();} ?>
                                                <?php } else {?>
                                                    <div class="row">
                                                        <div class="col-5">
                                                            <div class="form-group">
                                                                <label class="form-label">Location</label>
                                                                <select class="form-control PK_LOCATION" name="PK_LOCATION[]">
                                                                    <option>Select Location</option>
                                                                    <?php
                                                                    $row = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME FROM DOA_LOCATION WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]' AND ACTIVE = 1 ORDER BY LOCATION_NAME");
                                                                    while (!$row->EOF) { ?>
                                                                        <option value="<?php echo $row->fields['PK_LOCATION'];?>"><?=$row->fields['LOCATION_NAME']?></option>
                                                                        <?php $row->MoveNext(); } ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="col-5">
                                                            <div class="form-group">
                                                                <label class="form-label">Document File</label>
                                                                <input type="file" name="FILE_PATH[]" class="form-control">
                                                            </div>
                                                        </div>
                                                        <div class="col-2">
                                                            <div class="form-group" style="margin-top: 15px;">
                                                                <a href="javascript:;" class="btn btn-danger waves-effect waves-light m-r-10 text-white" onclick="removeServiceDocument(this);"><i class="ti-trash"></i></a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php } ?>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-11">
                                                <div class="form-group" style="float: right;">
                                                    <a href="javascript:;" class="btn btn-info waves-effect waves-light m-r-10 text-white" onclick="addServiceDocument();">Add More</a>
                                                </div>
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white ">Submit</button>
                                        <button type="button" onclick="window.location.href='all_services.php'" class="btn btn-inverse waves-effect waves-light">Cancel</button>
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

    $(document).on('change', '.IS_CHARGEABLE', function () {
        if ($(this).val() == 1){
            $(this).closest('.row').find('.service_price').slideDown();
        }else {
            $(this).closest('.row').find('.service_price').slideUp();
        }
    });

    $(document).on('change', '.IS_GROUP', function () {
        if ($(this).val() == 1){
            $(this).closest('.row').find('.capacity_div').slideDown();
        }else {
            $(this).closest('.row').find('.capacity_div').slideUp();
        }
    });

    let counter = parseInt(<?=$i?>);
    function addMoreServiceCode() {
        let PK_SERVICE_CLASS = ($('.PK_SERVICE_CLASS').val())?parseInt($('.PK_SERVICE_CLASS').val()):1;
        $('#append_service_code').append(`<div class="row align-items-end">
                                            <input type="hidden" name="PK_SERVICE_CODE[]" value="0">
                                            <div class="col-1">
                                                <div class="form-group">
                                                    <input type="text" name="SERVICE_CODE[]" class="form-control" placeholder="Service Code">
                                                </div>
                                            </div>
                                            <div class="col-1">
                                                <div class="form-group">
                                                    <input type="text" name="SERVICE_CODE_DESCRIPTION[]" class="form-control" placeholder="Description">
                                                </div>
                                            </div>
                                            <div class="col-1" style="margin-bottom: auto">
                                                            <div class="form-group multiselect-box">
                                                                <select class="multi_sumo_select PK_BOOKING_CODES" name="PK_BOOKING_CODES[${counter}][]" multiple>
                                                                    <?php
                                                                        $booking_code = $db->Execute("SELECT * FROM DOA_BOOKING_CODES WHERE ACTIVE = 1");
                                                                        while (!$booking_code->EOF) { ?>
                                                                        <option value="<?php echo $booking_code->fields['PK_BOOKING_CODES'];?>"><?=$booking_code->fields['BOOKING_NAME']?></option>
                                                                        <?php $booking_code->MoveNext(); } ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                            <div class="col-1 frequency_div" style="display: ${(PK_SERVICE_CLASS===1)?'':'none'}">
                                                <div class="form-group">
                                                    <select class="form-control PK_FREQUENCY" name="PK_FREQUENCY[]">
                                                        <option value="">Select</option>
                                                        <?php
                                                        $row = $db->Execute("SELECT * FROM DOA_FREQUENCY WHERE ACTIVE = 1");
                                                        while (!$row->EOF) { ?>
                                                            <option value="<?php echo $row->fields['PK_FREQUENCY'];?>"><?=$row->fields['FREQUENCY']?></option>
                                                        <?php $row->MoveNext(); } ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-1 duration_div" style="display: ${(PK_SERVICE_CLASS===2)?'':'none'}">
                                                <div class="form-group">
                                                    <div class="col-md-12">
                                                        <input type="text" id="DURATION" name="DURATION[]" class="form-control" placeholder="Duration">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-2" style="height: 20px;margin-bottom: 35px;padding-left: 5%;">
                                                <div class="form-group">
                                                    <div class="col-md-12">
                                                        <label><input type="radio" name="IS_GROUP_${counter}" class="IS_GROUP" value="1"/>&nbsp;Yes</label>&nbsp;&nbsp;&nbsp;&nbsp;
                                                        <label><input type="radio" name="IS_GROUP_${counter}" class="IS_GROUP" value="0" checked/>&nbsp;No</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-1">
                                                <div class="form-group capacity_div" style="display: none;">
                                                    <div class="col-md-12" >
                                                        <input type="number" class="form-control" name="CAPACITY[]" id="CAPACITY">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-2" style="height: 20px;margin-bottom: 35px;padding-left: 5%;">
                                                <div class="form-group" >
                                                    <div class="col-md-12" style="margin-bottom: 10px">
                                                        <label><input type="radio" name="IS_CHARGEABLE_${counter}" class="IS_CHARGEABLE" value="1" checked/>&nbsp;Yes</label>&nbsp;&nbsp;&nbsp;&nbsp;
                                                        <label><input type="radio" name="IS_CHARGEABLE_${counter}" class="IS_CHARGEABLE" value="0"/>&nbsp;No</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-1">
                                                <div class="form-group service_price">
                                                    <div class="col-md-12" >
                                                        <div class="input-group">
                                                            <span class="input-group-text"><?=$currency?></span>
                                                            <input type="text" id="PRICE" name="PRICE[]" class="form-control" placeholder="Price">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-1">
                                                <div class="form-group number_of_sessions_div" style="display: <?=(($IS_PACKAGE == 0) ? 'none' : '')?>">
                                                    <div class="col-md-12" >
                                                        <input type="number" class="form-control" name="NUMBER_OF_SESSIONS[]" id="NUMBER_OF_SESSIONS">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-1">
                                                <div class="form-group">
                                                    <a href="javascript:;" class="btn btn-danger waves-effect waves-light m-r-10 text-white" onclick="removeServiceCode(this);"><i class="ti-trash"></i></a>
                                                </div>
                                            </div>
                                        </div>`);
        counter++;
        $('.multi_sumo_select').SumoSelect({placeholder: 'Select Services', selectAll: true});
    }

    function removeServiceCode(param) {
        $(param).closest('.row').remove();
        counter--;
    }

    function selectServiceClass(param) {
        let PK_SERVICE_CLASS = parseInt($(param).val());

        if (PK_SERVICE_CLASS === 1){
            $('#frequency_duration_label').text('Frequency');
            $('.duration_div').hide();
            $('.frequency_div').show();
            $('#schedule_div').slideUp();
        }else {
            if (PK_SERVICE_CLASS === 2){
                $('#frequency_duration_label').text('Duration');
                $('.duration_div').show();
                $('.frequency_div').hide();
                $('#schedule_div').slideDown();
            }
        }
    }

    function addServiceDocument() {
        $('#append_service_document').append(`<div class="row">
                                                <div class="col-5">
                                                    <div class="form-group">
                                                        <label class="form-label">Location</label>
                                                        <select class="form-control PK_LOCATION" name="PK_LOCATION[]">
                                                            <option>Select Location</option>
                                                            <?php
                                                            $row = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME FROM DOA_LOCATION WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]' AND ACTIVE = 1 ORDER BY LOCATION_NAME");
                                                            while (!$row->EOF) { ?>
                                                                <option value="<?php echo $row->fields['PK_LOCATION'];?>"><?=$row->fields['LOCATION_NAME']?></option>
                                                            <?php $row->MoveNext(); } ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-5">
                                                    <div class="form-group">
                                                        <label class="form-label">Document File</label>
                                                        <input type="file" name="FILE_PATH[]" class="form-control">
                                                    </div>
                                                </div>
                                                <div class="col-2">
                                                    <div class="form-group" style="margin-top: 15px;">
                                                        <a href="javascript:;" class="btn btn-danger waves-effect waves-light m-r-10 text-white" onclick="removeServiceDocument(this);"><i class="ti-trash"></i></a>
                                                    </div>
                                                </div>
                                            </div>`);
    }

    function removeServiceDocument(param) {
        $(param).closest('.row').remove();
    }

    $(document).on('click', '#cancel_button', function () {
        window.location.href='all_services.php'
    });

    $(document).on('submit', '#service_info_form', function (event) {
        event.preventDefault();
        let form_data = $('#service_info_form').serialize();
        $.ajax({
            url: "ajax/AjaxFunctions.php",
            type: 'POST',
            data: form_data,
            success:function (data) {
                if (PK_SERVICE_MASTER == 0) {
                    $('.PK_SERVICE_MASTER').val(data);
                    $('#service_codes_link')[0].click();
                }else{
                    window.location.href='all_services.php';
                }
            }
        });
    });

    $(document).on('submit', '#service_code_form', function (event) {
        event.preventDefault();
        let form_data = $('#service_code_form').serialize();
        $.ajax({
            url: "ajax/AjaxFunctions.php",
            type: 'POST',
            data: form_data,
            success:function (data) {
                //$('#service_document_link')[0].click();
                window.location.href='all_services.php';
            }
        });
    });

    $('.multi_sumo_select').SumoSelect({placeholder: 'Select Services', selectAll: true});

    function isPackage(param) {
        if ($(param).is(':checked')){
            $('#number_of_sessions').show();
            $('.number_of_sessions_div').show();
        }else {
            $('#number_of_sessions').hide();
            $('.number_of_sessions_div').hide();
        }
    }

</script>
</body>
</html>