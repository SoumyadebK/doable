<?php
require_once('../global/config.php');

if (empty($_GET['id']))
    $title = "Add Event";
else
    $title = "Edit Event";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 2 ){
    header("location:../login.php");
    exit;
}

if(!empty($_POST)){
    $EVENT_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
    $EVENT_DATA['HEADER'] = $_POST['HEADER'];
    $EVENT_DATA['PK_EVENT_TYPE'] = $_POST['PK_EVENT_TYPE'];
    $EVENT_DATA['DESCRIPTION'] = $_POST['DESCRIPTION'];
    $EVENT_DATA['SHARE_WITH_CUSTOMERS'] = isset($_POST['SHARE_WITH_CUSTOMERS'])?1:0;
    $EVENT_DATA['SHARE_WITH_SERVICE_PROVIDERS'] = isset($_POST['SHARE_WITH_SERVICE_PROVIDERS'])?1:0;
    $EVENT_DATA['SHARE_WITH_EMPLOYEES'] = isset($_POST['SHARE_WITH_EMPLOYEES'])?1:0;
    $EVENT_DATA['START_DATE'] = date('Y-m-d', strtotime($_POST['START_DATE']));
    $EVENT_DATA['START_TIME'] = date('H:i:s', strtotime($_POST['START_TIME']));
    $EVENT_DATA['END_DATE'] = !empty($_POST['END_DATE'])?date('Y-m-d', strtotime($_POST['END_DATE'])):NULL;
    $EVENT_DATA['END_TIME'] = !empty($_POST['END_TIME'])?date('H:i:s', strtotime($_POST['END_TIME'])):NULL;

    if(empty($_GET['id'])){
        $EVENT_DATA['ACTIVE'] = 1;
        $EVENT_DATA['CREATED_BY']  = $_SESSION['PK_USER'];
        $EVENT_DATA['CREATED_ON']  = date("Y-m-d H:i");
        db_perform_account('DOA_EVENT', $EVENT_DATA, 'insert');
        $PK_EVENT = $db_account->insert_ID();
    }else{
        $EVENT_DATA['ACTIVE'] = $_POST['ACTIVE'];
        $EVENT_DATA['EDITED_BY']	= $_SESSION['PK_USER'];
        $EVENT_DATA['EDITED_ON'] = date("Y-m-d H:i");
        db_perform_account('DOA_EVENT', $EVENT_DATA, 'update'," PK_EVENT =  '$_GET[id]'");
        $PK_EVENT = $_GET['id'];
    }

    $db_account->Execute("DELETE FROM `DOA_EVENT_LOCATION` WHERE `PK_EVENT` = '$PK_EVENT'");
    if(isset($_POST['PK_LOCATION'])){
        $PK_LOCATION = $_POST['PK_LOCATION'];
        for($i = 0; $i < count($PK_LOCATION); $i++){
            $EVENT_LOCATION_DATA['PK_EVENT'] = $PK_EVENT;
            $EVENT_LOCATION_DATA['PK_LOCATION'] = $PK_LOCATION[$i];
            db_perform_account('DOA_EVENT_LOCATION', $EVENT_LOCATION_DATA, 'insert');
        }
    }

    if (isset($_FILES['IMAGE']['name'])){
        $db_account->Execute("DELETE FROM `DOA_EVENT_IMAGE` WHERE `PK_EVENT` = '$PK_EVENT'");
        for($i = 0; $i < count($_FILES['IMAGE']['name']); $i++){
            $EVENT_IMAGE_DATA['PK_EVENT'] = $PK_EVENT;
            if(!empty($_FILES['IMAGE']['name'][$i])){
                $extn 			= explode(".",$_FILES['IMAGE']['name'][$i]);
                $iindex			= count($extn) - 1;
                $rand_string 	= time()."-".rand(100000,999999);
                $file11			= 'event_image_'.$PK_EVENT.'_'.$rand_string.".".$extn[$iindex];
                $extension   	= strtolower($extn[$iindex]);

                $image_path    = '../uploads/event_image/'.$file11;
                move_uploaded_file($_FILES['IMAGE']['tmp_name'][$i], $image_path);
                $EVENT_IMAGE_DATA['IMAGE'] = $image_path;
            } else {
                $EVENT_IMAGE_DATA['IMAGE'] = $_POST['IMAGE_PATH'][$i];
            }
            $EVENT_IMAGE_DATA['CREATED_BY']  = $_SESSION['PK_USER'];
            $EVENT_IMAGE_DATA['CREATED_ON']  = date("Y-m-d H:i");
            db_perform_account('DOA_EVENT_IMAGE', $EVENT_IMAGE_DATA, 'insert');
        }
    }

    header("location:all_events.php");
}



if(empty($_GET['id'])){
    $HEADER = '';
    $PK_EVENT_TYPE = '';
    $START_DATE = '';
    $START_TIME = '';
    $END_DATE = '0000-00-00';
    $END_TIME = '00:00:00';
    $DESCRIPTION = '';
    $PK_LOCATION = '';
    $SHARE_WITH_CUSTOMERS = '';
    $SHARE_WITH_SERVICE_PROVIDERS = '';
    $SHARE_WITH_EMPLOYEES = '';
    $ACTIVE = '';
} else {
    $res = $db_account->Execute("SELECT * FROM `DOA_EVENT` WHERE `PK_EVENT` = '$_GET[id]'");

    if($res->RecordCount() == 0){
        header("location:all_events.php");
        exit;
    }

    $HEADER = $res->fields['HEADER'];
    $PK_EVENT_TYPE = $res->fields['PK_EVENT_TYPE'];
    $START_DATE = $res->fields['START_DATE'];
    $END_DATE = $res->fields['END_DATE'];
    $START_TIME = $res->fields['START_TIME'];
    $END_TIME = $res->fields['END_TIME'];
    $DESCRIPTION = $res->fields['DESCRIPTION'];
    //$PK_LOCATION = $res->fields['PK_LOCATION'];
    $SHARE_WITH_CUSTOMERS = $res->fields['SHARE_WITH_CUSTOMERS'];
    $SHARE_WITH_SERVICE_PROVIDERS = $res->fields['SHARE_WITH_SERVICE_PROVIDERS'];
    $SHARE_WITH_EMPLOYEES = $res->fields['SHARE_WITH_EMPLOYEES'];
    $ACTIVE = $res->fields['ACTIVE'];
}

?>

<!DOCTYPE html>
<html lang="en">
<?php require_once('../includes/header.php');?>
<style>
    .ck-editor__editable_inline {
        min-height: 300px;
    }
    .SumoSelect {
        width: 100%;
    }
</style>
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
                            <li class="breadcrumb-item"><a href="all_events.php">All Events</a></li>
                            <li class="breadcrumb-item active"><?=$title?></li>
                        </ol>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card p-20">
                        <div class="card-body">
                            <form class="form-material form-horizontal" action="" method="post" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-9">
                                        <div class="row">
                                            <div class="col-6">
                                                <div class="form-group">
                                                    <label class="form-label">Header</label>
                                                    <input type="text" id="HEADER" name="HEADER" class="form-control" required value="<?php echo $HEADER?>">
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="form-group">
                                                    <label class="form-label">Event Type</label>
                                                    <select class="form-control" name="PK_EVENT_TYPE" id="PK_EVENT_TYPE">
                                                        <option value="">Select Event Type</option>
                                                        <?php
                                                        $row = $db_account->Execute("SELECT * FROM `DOA_EVENT_TYPE` WHERE PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER']." AND `ACTIVE` = 1");
                                                        while (!$row->EOF) {?>
                                                            <option value="<?php echo $row->fields['PK_EVENT_TYPE'];?>" <?=($PK_EVENT_TYPE==$row->fields['PK_EVENT_TYPE'])?'selected':''?>><?=$row->fields['EVENT_TYPE']?></option>
                                                        <?php $row->MoveNext(); } ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-6">
                                                <div class="form-group">
                                                    <label class="form-label">Start Date</label>
                                                    <input type="text" id="START_DATE" name="START_DATE" class="form-control datepicker-normal" required value="<?php echo ($START_DATE)?date('m/d/Y', strtotime($START_DATE)):''?>">
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="form-group">
                                                    <label class="form-label">End Date</label>
                                                    <input type="text" id="END_DATE" name="END_DATE" class="form-control datepicker-normal" required value="<?php echo ($END_DATE == '0000-00-00')?'':date('m/d/Y', strtotime($END_DATE))?>">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-6">
                                                <div class="form-group">
                                                    <label class="form-label">Start Time</label>
                                                    <input type="text" id="START_TIME" name="START_TIME" class="form-control time-picker" required value="<?php echo ($START_TIME)?date('h:i A', strtotime($START_TIME)):''?>">
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="form-group">
                                                    <label class="form-label">End Time</label>
                                                    <input type="text" id="END_TIME" name="END_TIME" class="form-control time-picker" required value="<?php echo ($END_TIME == '00:00:00')?'':date('h:i A', strtotime($END_TIME))?>">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-6">
                                                <label class="form-label">Location</label>
                                                <div class="col-md-12 multiselect-box">
                                                    <select class="multi_sumo_select" name="PK_LOCATION[]" id="PK_LOCATION" multiple>
                                                        <?php
                                                        $selected_location = [];
                                                        if(!empty($_GET['id'])) {
                                                            $selected_location_row = $db_account->Execute("SELECT `PK_LOCATION` FROM `DOA_EVENT_LOCATION` WHERE `PK_EVENT` = '$_GET[id]'");
                                                            while (!$selected_location_row->EOF) {
                                                                $selected_location[] = $selected_location_row->fields['PK_LOCATION'];
                                                                $selected_location_row->MoveNext();
                                                            }
                                                        }
                                                        $row = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME FROM DOA_LOCATION WHERE ACTIVE = 1 AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
                                                        while (!$row->EOF) { ?>
                                                            <option value="<?php echo $row->fields['PK_LOCATION'];?>" <?=in_array($row->fields['PK_LOCATION'], $selected_location)?"selected":""?>><?=$row->fields['LOCATION_NAME']?></option>
                                                            <?php $row->MoveNext(); } ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <?php if(!empty($_GET['id'])) { ?>
                                                <div class="col-6">
                                                    <div class="col-md-2">
                                                        <label>Active</label>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label><input type="radio" name="ACTIVE" id="ACTIVE" value="1" <? if($ACTIVE == 1) echo 'checked="checked"'; ?> />&nbsp;Yes</label>&nbsp;&nbsp;
                                                        <label><input type="radio" name="ACTIVE" id="ACTIVE" value="0" <? if($ACTIVE == 0) echo 'checked="checked"'; ?> />&nbsp;No</label>
                                                    </div>
                                                </div>
                                            <? } ?>
                                        </div>

                                        <div class="row">
                                            <div class="col-12">
                                                <div class="form-group">
                                                    <label class="form-label">Description</label>
                                                    <textarea class="ckeditor" id="DESCRIPTION" name="DESCRIPTION"><?=$DESCRIPTION?></textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <div id="add_more_image">
                                            <?php
                                            if(!empty($_GET['id'])) {
                                            $row = $db_account->Execute("SELECT * FROM DOA_EVENT_IMAGE WHERE PK_EVENT = ".$_GET['id']);
                                            if ($row->RecordCount() > 0) {
                                                while (!$row->EOF) { ?>
                                                <div class="row">
                                                    <div class="col-4">
                                                        <div class="form-group">
                                                            <label class="form-label">Images</label>
                                                            <input class="form-control-file" type="file" name="IMAGE[]">
                                                            <img src="<?=$row->fields['IMAGE']?>" style="margin-top: 15px; width: 100px; height: auto;">
                                                            <input type="hidden" name="IMAGE_PATH[]" value="<?=$row->fields['IMAGE']?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-2">
                                                        <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                                    </div>
                                                </div>
                                                <?php
                                                $row->MoveNext(); } ?>
                                                <div class="row m-15">
                                                    <div class="col-2" style="margin-left: 26%;">
                                                        <a href="javascript:;" onclick="addMoreImages()"><i class="ti-plus"></i> Add More</a>
                                                    </div>
                                                </div>
                                            <?php } else { ?>
                                                <div class="row">
                                                    <div class="col-4">
                                                        <div class="form-group">
                                                            <label class="form-label">Images</label>
                                                            <input class="form-control-file" type="file" name="IMAGE[]">
                                                        </div>
                                                    </div>
                                                    <div class="col-2">
                                                        <a href="javascript:;" onclick="addMoreImages()"><i class="ti-plus"></i> Add More</a>
                                                    </div>
                                                </div>
                                            <?php }
                                            } else { ?>
                                            <div class="row">
                                                <div class="col-4">
                                                    <div class="form-group">
                                                        <label class="form-label">Images</label>
                                                        <input class="form-control-file" type="file" name="IMAGE[]">
                                                    </div>
                                                </div>
                                                <div class="col-2">
                                                    <a href="javascript:;" onclick="addMoreImages()"><i class="ti-plus"></i> Add More</a>
                                                </div>
                                            </div>
                                            <?php } ?>
                                        </div>
                                    </div>

                                    <div class="col-3">
                                        <h4 class="card-title">Share With</h4>
                                        <div class="m-l-20">
                                            <div class="form-group">
                                                <label class="form-label">
                                                   <input type="checkbox" class="form-check-inline share_with" name="" onchange="checkAll(this)" <?=($SHARE_WITH_CUSTOMERS == 1 && $SHARE_WITH_SERVICE_PROVIDERS == 1 && $SHARE_WITH_EMPLOYEES == 1) ? 'checked' : ''?>> All
                                                </label>
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label">
                                                    <input type="checkbox" class="form-check-inline share_with" name="SHARE_WITH_CUSTOMERS" value="1" <?=($SHARE_WITH_CUSTOMERS == 1) ? 'checked' : ''?>> Customers
                                                </label>
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label">
                                                    <input type="checkbox" class="form-check-inline share_with" name="SHARE_WITH_SERVICE_PROVIDERS" value="1" <?=($SHARE_WITH_SERVICE_PROVIDERS == 1) ? 'checked' : ''?>> Service Providers
                                                </label>
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label">
                                                    <input type="checkbox" class="form-check-inline share_with" name="SHARE_WITH_EMPLOYEES" value="1" <?=($SHARE_WITH_EMPLOYEES == 1) ? 'checked' : ''?>> Employees
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">Submit</button>
                                <button type="button" class="btn btn-inverse waves-effect waves-light" onclick="window.location.href='all_events.php'">Cancel</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once('../includes/footer.php');?>
<script src="../assets/sumoselect/jquery.sumoselect.min.js"></script>
<script src="https://cdn.ckeditor.com/ckeditor5/34.2.0/classic/ckeditor.js"></script>

</body>
<script>

    $(document).ready(function(){
        $("#START_DATE").datepicker({
            numberOfMonths: 1,
            onSelect: function(selected) {
                $("#END_DATE").datepicker("option","minDate", selected)
            }
        });
        $("#END_DATE").datepicker({
            numberOfMonths: 1,
            onSelect: function(selected) {
                $("#START_DATE").datepicker("option","maxDate", selected)
            }
        });
    });


    /*$('.datepicker-normal').datepicker({
        format: 'mm/dd/yyyy',
    });*/

    $('.time-picker').timepicker({
        timeFormat: 'hh:mm p',
    });

    $('.multi_sumo_select').SumoSelect({placeholder: 'Select Location', selectAll: true});

    function checkAll(ele) {
        var checkboxes = $('.share_with');
        if (ele.checked) {
            for (var i = 0; i < checkboxes.length; i++) {
                if (checkboxes[i].type == 'checkbox') {
                    checkboxes[i].checked = true;
                }
            }
        } else {
            for (var i = 0; i < checkboxes.length; i++) {
                console.log(i)
                if (checkboxes[i].type == 'checkbox') {
                    checkboxes[i].checked = false;
                }
            }
        }
    }

    ClassicEditor
        .create( document.querySelector( '#DESCRIPTION' ) )
        .catch( error => {
            console.error( error );
        } );

    function addMoreImages() {
        $('#add_more_image').append(`<div class="row">
                                        <div class="col-4">
                                            <div class="form-group">
                                                <label class="form-label">Images</label>
                                                <input class="form-control-file" type="file" name="IMAGE[]">
                                            </div>
                                        </div>
                                        <div class="col-2">
                                            <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                        </div>
                                    </div>`);
    }

    function removeThis(param) {
        $(param).closest('.row').remove();
    }
</script>
</html>