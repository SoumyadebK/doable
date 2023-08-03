<?php
require_once('../global/config.php');

if (empty($_GET['id']))
    $title = "Add Location";
else
    $title = "Edit Location";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 2 ){
    header("location:../login.php");
    exit;
}

if(!empty($_POST)){
    if ($_POST['FUNCTION_NAME'] == 'saveLocationData') {
        unset($_POST['FUNCTION_NAME']);
        $LOCATION_DATA = $_POST;
        $LOCATION_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];

        if ($_FILES['IMAGE_PATH']['name'] != '') {
            $extn = explode(".", $_FILES['IMAGE_PATH']['name']);
            $iindex = count($extn) - 1;
            $rand_string = time() . "-" . rand(100000, 999999);
            $file11 = 'location_image_' . $_SESSION['PK_USER'] . $rand_string . "." . $extn[$iindex];
            $extension = strtolower($extn[$iindex]);

            if ($extension == "gif" || $extension == "jpeg" || $extension == "pjpeg" || $extension == "png" || $extension == "jpg") {
                $image_path = '../uploads/location_image/' . $file11;
                move_uploaded_file($_FILES['IMAGE_PATH']['tmp_name'], $image_path);
                $LOCATION_DATA['IMAGE_PATH'] = $image_path;
            }
        }

        if (empty($_GET['id'])) {
            $LOCATION_DATA['ACTIVE'] = 1;
            $LOCATION_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
            $LOCATION_DATA['CREATED_ON'] = date("Y-m-d H:i");
            db_perform('DOA_LOCATION', $LOCATION_DATA, 'insert');
        } else {
            $LOCATION_DATA['ACTIVE'] = $_POST['ACTIVE'];
            $LOCATION_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
            $LOCATION_DATA['EDITED_ON'] = date("Y-m-d H:i");
            db_perform('DOA_LOCATION', $LOCATION_DATA, 'update', " PK_LOCATION =  '$_GET[id]'");
        }
    }

    if ($_POST['FUNCTION_NAME'] == 'saveOperationalHours') {
        $ALL_DAYS = isset($_POST['ALL_DAYS'])?1:0;
        $operational_hours = $db_account->Execute("SELECT * FROM DOA_OPERATIONAL_HOUR WHERE `PK_LOCATION` = '$_GET[id]'");
        if($operational_hours->RecordCount() > 0){
            for ($i = 0; $i < count($_POST['OPEN_TIME']); $i++) {
                $PK_LOCATION = (int)$_GET['id'];
                $DAY_NUMBER = (int)($i+1);
                $OPERATIONAL_HOUR_DATA['PK_LOCATION'] = $_GET['id'];
                $OPERATIONAL_HOUR_DATA['DAY_NUMBER'] = $i + 1;
                $OPERATIONAL_HOUR_DATA['OPEN_TIME'] = ($ALL_DAYS == 0) ? (($_POST['OPEN_TIME'][$i])?date('H:i', strtotime($_POST['OPEN_TIME'][$i])):'') : date('H:i', strtotime($_POST['OPEN_TIME'][0]));
                $OPERATIONAL_HOUR_DATA['CLOSE_TIME'] = ($ALL_DAYS == 0) ? (($_POST['CLOSE_TIME'][$i])?date('H:i', strtotime($_POST['CLOSE_TIME'][$i])):'') : date('H:i', strtotime($_POST['CLOSE_TIME'][0]));
                $OPERATIONAL_HOUR_DATA['CLOSED'] = isset($_POST['CLOSED_'.$i])?1:0;
                db_perform_account('DOA_OPERATIONAL_HOUR', $OPERATIONAL_HOUR_DATA, 'update', " PK_LOCATION =  $PK_LOCATION AND DAY_NUMBER = $DAY_NUMBER");
            }
        }else {
            if (count($_POST['OPEN_TIME']) > 0) {
                for ($i = 0; $i < count($_POST['OPEN_TIME']); $i++) {
                    $OPERATIONAL_HOUR_DATA['PK_LOCATION'] = $_GET['id'];
                    $OPERATIONAL_HOUR_DATA['DAY_NUMBER'] = $i + 1;
                    $OPERATIONAL_HOUR_DATA['OPEN_TIME'] = ($ALL_DAYS == 0) ? (($_POST['OPEN_TIME'][$i]) ? date('H:i', strtotime($_POST['OPEN_TIME'][$i])) : '') : date('H:i', strtotime($_POST['OPEN_TIME'][0]));
                    $OPERATIONAL_HOUR_DATA['CLOSE_TIME'] = ($ALL_DAYS == 0) ? (($_POST['CLOSE_TIME'][$i]) ? date('H:i', strtotime($_POST['CLOSE_TIME'][$i])) : '') : date('H:i', strtotime($_POST['CLOSE_TIME'][0]));
                    $OPERATIONAL_HOUR_DATA['CLOSED'] = isset($_POST['CLOSED_' . $i]) ? 1 : 0;
                    db_perform_account('DOA_OPERATIONAL_HOUR', $OPERATIONAL_HOUR_DATA, 'insert');
                }
            }
        }
    }
    header("location:all_locations.php");
}



if(empty($_GET['id'])){
    $PK_LOCATION = 0;
    $LOCATION_NAME = '';
    $LOCATION_CODE = '';
    $ADDRESS = '';
    $ADDRESS_1 = '';
    $PK_COUNTRY = '';
    $PK_STATES = '';
    $CITY = '';
    $ZIP_CODE = '';
    $PHONE = '';
    $EMAIL = '';
    $IMAGE_PATH = '';
    $PK_TIMEZONE = '';
    $ACTIVE = '';
    $PAYMENT_GATEWAY_TYPE = '';
    $SECRET_KEY = '';
    $PUBLISHABLE_KEY = '';
    $ACCESS_TOKEN = '';
    $SQUARE_APP_ID ='';
    $SQUARE_LOCATION_ID = '';
    $LOGIN_ID = '';
    $TRANSACTION_KEY = '';
    $AUTHORIZE_CLIENT_KEY = '';
} else {
    $res = $db->Execute("SELECT * FROM `DOA_LOCATION` WHERE `PK_LOCATION` = '$_GET[id]'");

    if($res->RecordCount() == 0){
        header("location:all_locations.php");
        exit;
    }

    $PK_LOCATION = $_GET['id'];
    $LOCATION_NAME = $res->fields['LOCATION_NAME'];
    $LOCATION_CODE = $res->fields['LOCATION_CODE'];
    $ADDRESS = $res->fields['ADDRESS'];
    $ADDRESS_1 = $res->fields['ADDRESS_1'];
    $PK_COUNTRY = $res->fields['PK_COUNTRY'];
    $PK_STATES = $res->fields['PK_STATES'];
    $CITY = $res->fields['CITY'];
    $ZIP_CODE = $res->fields['ZIP_CODE'];
    $PHONE = $res->fields['PHONE'];
    $EMAIL = $res->fields['EMAIL'];
    $IMAGE_PATH = $res->fields['IMAGE_PATH'];
    $PK_TIMEZONE = $res->fields['PK_TIMEZONE'];
    $ACTIVE = $res->fields['ACTIVE'];
    $PAYMENT_GATEWAY_TYPE   = $res->fields['PAYMENT_GATEWAY_TYPE'];
    $SECRET_KEY             = $res->fields['SECRET_KEY'];
    $PUBLISHABLE_KEY        = $res->fields['PUBLISHABLE_KEY'];
    $ACCESS_TOKEN           = $res->fields['ACCESS_TOKEN'];
    $SQUARE_APP_ID          = $res->fields['APP_ID'];
    $SQUARE_LOCATION_ID     = $res->fields['LOCATION_ID'];
    $LOGIN_ID               = $res->fields['LOGIN_ID'];
    $TRANSACTION_KEY        = $res->fields['TRANSACTION_KEY'];
    $AUTHORIZE_CLIENT_KEY   = $res->fields['AUTHORIZE_CLIENT_KEY'];

}

$user_data = $db->Execute("SELECT DOA_USERS.ABLE_TO_EDIT_PAYMENT_GATEWAY FROM DOA_USERS WHERE PK_USER = '$_SESSION[PK_USER]'");
$ABLE_TO_EDIT_PAYMENT_GATEWAY = $user_data->fields['ABLE_TO_EDIT_PAYMENT_GATEWAY'];

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
        <div class="container-fluid">
            <div class="row page-titles">
                <div class="col-md-5 align-self-center">
                    <h4 class="text-themecolor"><?=$title?></h4>
                </div>
                <div class="col-md-7 align-self-center text-end">
                    <div class="d-flex justify-content-end align-items-center">
                        <ol class="breadcrumb justify-content-end">
                            <li class="breadcrumb-item"><a href="setup.php">Setup</a></li>
                            <li class="breadcrumb-item"><a href="all_locations.php">All Locations</a></li>
                            <li class="breadcrumb-item active"><?=$title?></li>
                        </ol>

                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-title" style="margin-top: 15px; margin-left: 15px;">
                            <?php
                            if(!empty($_GET['id'])) {
                                echo $LOCATION_NAME;
                            }
                            ?>
                        </div>
                        <div class="card-body">
                            <!-- Nav tabs -->
                            <ul class="nav nav-tabs" role="tablist">
                                <li class="active"> <a class="nav-link active" data-bs-toggle="tab" id="location_link" href="#location" role="tab"><span class="hidden-sm-up"><i class="ti-location-pin"></i></span> <span class="hidden-xs-down">Location</span></a> </li>
                                <li> <a class="nav-link" data-bs-toggle="tab" id="operational_hours_link" href="#operational_hours" role="tab"><span class="hidden-sm-up"><i class="ti-time"></i></span> <span class="hidden-xs-down">Operational Hours</span></a> </li>
                            </ul>

                            <!-- Tab panes -->
                            <div class="tab-content tabcontent-border">
                                <div class="tab-pane active" id="location" role="tabpanel">
                                    <form class="form-material form-horizontal" action="" method="post" enctype="multipart/form-data">
                                        <input type="hidden" name="FUNCTION_NAME" value="saveLocationData">
                                        <div class="p-20">
                                            <div class="row">
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12" for="example-text">Location<span class="text-danger">*</span>
                                                        </label>
                                                        <div class="col-md-12">
                                                            <input type="text" id="LOCATION_NAME" name="LOCATION_NAME" class="form-control" placeholder="Enter Location Name" required value="<?php echo $LOCATION_NAME?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12" for="example-text">Location Code<span class="text-danger">*</span>
                                                        </label>
                                                        <div class="col-md-12">
                                                            <input type="text" id="LOCATION_CODE" name="LOCATION_CODE" class="form-control" placeholder="Enter Location Code" required value="<?php echo $LOCATION_CODE?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12" for="example-text">Address
                                                        </label>
                                                        <div class="col-md-12">
                                                            <input type="text" id="ADDRESS" name="ADDRESS" class="form-control" placeholder="Enter Address" value="<?php echo $ADDRESS?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12" for="example-text">Apt/Ste
                                                        </label>
                                                        <div class="col-md-12">
                                                            <input type="text" id="ADDRESS_1" name="ADDRESS_1" class="form-control" placeholder="Enter Apartment OR Street" value="<?php echo $ADDRESS_1?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12" for="example-text">Country<span class="text-danger">*</span>
                                                        </label>
                                                        <div class="col-md-12">
                                                            <div class="col-sm-12">
                                                                <select class="form-control" name="PK_COUNTRY" id="PK_COUNTRY" onChange="fetch_state(this.value)" required>
                                                                    <option value="">Select Country</option>
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
                                                        <label class="col-md-12" for="example-text">State<span class="text-danger">*</span>
                                                        </label>
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
                                                        <label class="col-md-12" for="example-text">City</span>
                                                        </label>
                                                        <div class="col-md-12">
                                                            <input type="text" id="CITY" name="CITY" class="form-control" placeholder="Enter City" value="<?php echo $CITY?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12" for="example-text">Zip Code</span>
                                                        </label>
                                                        <div class="col-md-12">
                                                            <input type="text" id="ZIP_CODE" name="ZIP_CODE" class="form-control" placeholder="Enter Zip Code" value="<?php echo $ZIP_CODE?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12" for="example-text">Phone
                                                        </label>
                                                        <div class="col-md-12">
                                                            <input type="text" id="PHONE" name="PHONE" class="form-control" placeholder="Enter Phone No." value="<?php echo $PHONE?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12" for="example-text">Email</label>
                                                        <div class="col-md-12">
                                                            <input type="email" id="EMAIL" name="EMAIL" class="form-control" placeholder="enter Email Address" value="<?php echo $EMAIL?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12" for="example-text">Location Image</label>
                                                        <div class="col-md-12">
                                                            <input type="file" name="IMAGE_PATH" id="IMAGE_PATH" class="form-control">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12" for="example-text">Timezone<span class="text-danger">*</span></label>
                                                        <div class="col-md-12">
                                                            <select name="PK_TIMEZONE" id="PK_TIMEZONE" class="form-control required-entry" required>
                                                                <option value="">Select</option>
                                                                <? $res_type = $db->Execute("select * from DOA_TIMEZONE order by NAME ASC ");
                                                                while (!$res_type->EOF) { ?>
                                                                    <option value="<?=$res_type->fields['PK_TIMEZONE']?>" <? if($res_type->fields['PK_TIMEZONE'] == $PK_TIMEZONE) echo 'selected="selected"'; ?>><?=$res_type->fields['NAME']?></option>
                                                                    <?	$res_type->MoveNext();
                                                                } ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <?php if($IMAGE_PATH!=''){?><div style="width: 120px;height: 120px;margin-top: 25px;"><a class="fancybox" href="<?php echo $IMAGE_PATH;?>" data-fancybox-group="gallery"><img src = "<?php echo $IMAGE_PATH;?>" style="width:120px; height:120px" /></a></div><?php } ?>
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
                                            <? } ?>

                                            <?php if ($ABLE_TO_EDIT_PAYMENT_GATEWAY == 1) { ?>
                                            <div class="col-6" style="margin-top:50px">
                                                <div class="row">
                                                    <div class="form-group">
                                                        <label class="form-label" style="margin-bottom: 5px;">Payment Gateway</label><br>
                                                        <label style="margin-right: 70px;"><input type="radio" id="PAYMENT_GATEWAY_TYPE" name="PAYMENT_GATEWAY_TYPE" class="form-check-inline" value="Stripe" <?=($PAYMENT_GATEWAY_TYPE=='Stripe')?'checked':''?> onclick="showPaymentGateway(this);">Stripe</label>
                                                        <label style="margin-right: 70px;"><input type="radio" id="PAYMENT_GATEWAY_TYPE" name="PAYMENT_GATEWAY_TYPE" class="form-check-inline" value="Square" <?=($PAYMENT_GATEWAY_TYPE=='Square')?'checked':''?> onclick="showPaymentGateway(this);">Square</label>
                                                        <label style="margin-right: 70px;"><input type="radio" id="PAYMENT_GATEWAY_TYPE" name="PAYMENT_GATEWAY_TYPE" class="form-check-inline" value="Authorized.net" <?=($PAYMENT_GATEWAY_TYPE=='Authorized.net')?'checked':''?> onclick="showPaymentGateway(this);">Authorized.net</label>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row payment_gateway" id="stripe" style="display: <?=($PAYMENT_GATEWAY_TYPE=='Stripe')?'':'none'?>;">
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="form-label">Secret Key</label>
                                                        <input type="text" class="form-control" name="SECRET_KEY" value="<?=$SECRET_KEY?>">
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="form-label">Publishable Key</label>
                                                        <input type="text" class="form-control" name="PUBLISHABLE_KEY" value="<?=$PUBLISHABLE_KEY?>">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row payment_gateway" id="square" style="display: <?=($PAYMENT_GATEWAY_TYPE=='Square')?'':'none'?>">
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="form-label">Application ID</label>
                                                        <input type="text" class="form-control" name="APP_ID" value="<?=$SQUARE_APP_ID?>">
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="form-label">Location ID</label>
                                                        <input type="text" class="form-control" name="LOCATION_ID" value="<?=$SQUARE_LOCATION_ID?>">
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="form-label">Access Token</label>
                                                        <input type="text" class="form-control" name="ACCESS_TOKEN" value="<?=$ACCESS_TOKEN?>">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row payment_gateway" id="authorized" style="display: <?=($PAYMENT_GATEWAY_TYPE=='Authorized.net')?'':'none'?>">
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="form-label">Login ID</label>
                                                        <input type="text" class="form-control" name="LOGIN_ID" value="<?=$LOGIN_ID?>">
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="form-label">Transaction Key</label>
                                                        <input type="text" class="form-control" name="TRANSACTION_KEY" value="<?=$TRANSACTION_KEY?>">
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="form-label">Authorize Client Key</label>
                                                        <input type="text" class="form-control" name="AUTHORIZE_CLIENT_KEY" value="<?=$AUTHORIZE_CLIENT_KEY?>">
                                                    </div>
                                                </div>
                                            </div>
                                            <?php } ?>

                                            <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">Submit</button>
                                            <button type="button" class="btn btn-inverse waves-effect waves-light" onclick="window.location.href='all_locations.php'">Cancel</button>
                                        </div>
                                    </form>
                                </div>

                                <div class="tab-pane" id="operational_hours" role="tabpanel">
                                    <form class="form-material form-horizontal" action="" method="post" enctype="multipart/form-data">
                                        <input type="hidden" name="FUNCTION_NAME" value="saveOperationalHours">
                                        <div class="p-20" id="holiday_list_div">
                                            <div class="row">
                                                <div class="col-3">
                                                    <div class="form-group" style="text-align: center;">
                                                        <label class="form-label" for="example-text" style="font-weight: bold;">Day</label>
                                                    </div>
                                                </div>
                                                <div class="col-3">
                                                    <div class="form-group" style="text-align: center;">
                                                        <label class="form-label" for="example-text" style="font-weight: bold;">Open Time</label>
                                                    </div>
                                                </div>
                                                <div class="col-3">
                                                    <div class="form-group" style="text-align: center;">
                                                        <label class="form-label" for="example-text" style="font-weight: bold;">Close Time</label>
                                                    </div>
                                                </div>
                                                <div class="col-3">
                                                    <div class="form-group">
                                                        <label><input type="checkbox" name="ALL_DAYS" class="form-check-inline"> All Days</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php
                                            $operational_hours = $db_account->Execute("SELECT * FROM DOA_OPERATIONAL_HOUR WHERE `PK_LOCATION` = '$PK_LOCATION'");
                                            if($operational_hours->RecordCount() > 0) {
                                                $i = 0;
                                                while (!$operational_hours->EOF) { ?>
                                                <div class="row">
                                                    <div class="col-3">
                                                        <div class="form-group">
                                                            <div class="col-md-12">
                                                                <select name="DAY_NUMBER[]" class="form-control required-entry" disabled>
                                                                    <option value="1" <?=($operational_hours->fields['DAY_NUMBER']==1)?'selected':''?>>Monday</option>
                                                                    <option value="2" <?=($operational_hours->fields['DAY_NUMBER']==2)?'selected':''?>>Tuesday</option>
                                                                    <option value="3" <?=($operational_hours->fields['DAY_NUMBER']==3)?'selected':''?>>Wednesday</option>
                                                                    <option value="4" <?=($operational_hours->fields['DAY_NUMBER']==4)?'selected':''?>>Thursday</option>
                                                                    <option value="5" <?=($operational_hours->fields['DAY_NUMBER']==5)?'selected':''?>>Friday</option>
                                                                    <option value="6" <?=($operational_hours->fields['DAY_NUMBER']==6)?'selected':''?>>Saturday</option>
                                                                    <option value="7" <?=($operational_hours->fields['DAY_NUMBER']==7)?'selected':''?>>Sunday</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-3">
                                                        <div class="form-group">
                                                            <div class="col-md-12">
                                                                <input type="text" name="OPEN_TIME[]" class="form-control time-input time-picker" value="<?=($operational_hours->fields['OPEN_TIME']=='00:00:00')?'':date('h:i A', strtotime($operational_hours->fields['OPEN_TIME']))?>" style="pointer-events: <?=($operational_hours->fields['CLOSED']==1)?'none':''?>" readonly>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-3">
                                                        <div class="form-group">
                                                            <div class="col-md-12">
                                                                <input type="text" name="CLOSE_TIME[]" class="form-control time-input time-picker" value="<?=($operational_hours->fields['CLOSE_TIME']=='00:00:00')?'':date('h:i A', strtotime($operational_hours->fields['CLOSE_TIME']))?>" style="pointer-events: <?=($operational_hours->fields['CLOSED']==1)?'none':''?>" readonly>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-3">
                                                        <div class="form-group">
                                                            <div class="col-md-12" style="margin-top: 10px;">
                                                                <label><input type="checkbox" name="CLOSED_<?=$i?>" onchange="closeThisDay(this)" <?=($operational_hours->fields['CLOSED']==1)?'checked':''?>> Closed</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php $operational_hours->MoveNext(); $i++;} ?>
                                            <?php } else {
                                                for ($i = 1; $i <= 7; $i++) { ?>
                                                <div class="row">
                                                    <div class="col-3">
                                                        <div class="form-group">
                                                            <div class="col-md-12">
                                                                <select name="DAY_NUMBER[]" class="form-control required-entry" disabled>
                                                                    <option value="1" <?=($i==1)?'selected':''?>>Monday</option>
                                                                    <option value="2" <?=($i==2)?'selected':''?>>Tuesday</option>
                                                                    <option value="3" <?=($i==3)?'selected':''?>>Wednesday</option>
                                                                    <option value="4" <?=($i==4)?'selected':''?>>Thursday</option>
                                                                    <option value="5" <?=($i==5)?'selected':''?>>Friday</option>
                                                                    <option value="6" <?=($i==6)?'selected':''?>>Saturday</option>
                                                                    <option value="7" <?=($i==7)?'selected':''?>>Sunday</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-3">
                                                        <div class="form-group">
                                                            <div class="col-md-12">
                                                                <input type="text" name="OPEN_TIME[]" class="form-control time-input time-picker" readonly>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-3">
                                                        <div class="form-group">
                                                            <div class="col-md-12">
                                                                <input type="text" name="CLOSE_TIME[]" class="form-control time-input time-picker" readonly>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-3">
                                                        <div class="form-group">
                                                            <div class="col-md-12" style="margin-top: 10px;">
                                                                <label><input type="checkbox" name="CLOSED_<?=$i-1?>" onchange="closeThisDay(this)"> Closed</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php }
                                            } ?>
                                        </div>
                                        <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">Save</button>
                                        <button type="button" class="btn btn-inverse waves-effect waves-light" onclick="window.location.href='all_locations.php'">Cancel</button>
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

<script>
    $('.time-picker').timepicker({
        timeFormat: 'hh:mm p',
    });

    function closeThisDay(param){
        if ($(param).is(':checked')){
            $(param).closest('.row').find('.time-input').val('');
            $(param).closest('.row').find('.time-input').css('pointer-events', 'none');
        }else {
            $(param).closest('.row').find('.time-input').css('pointer-events', '');
        }
    }

    $(document).ready(function() {
        fetch_state(<?php  echo $PK_COUNTRY; ?>);
    });

    function fetch_state(PK_COUNTRY){
        jQuery(document).ready(function($) {
            var data = "PK_COUNTRY="+PK_COUNTRY+"&PK_STATES=<?=$PK_STATES;?>";
            var value = $.ajax({
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

    function showPaymentGateway(param) {
        $('.payment_gateway').slideUp();
        if($(param).val() === 'Stripe'){
            $('#stripe').slideDown();
        }else {
            if($(param).val() === 'Square'){
                $('#square').slideDown();
            }else {
                if($(param).val() === 'Authorized.net'){
                    $('#authorized').slideDown();
                }
            }

        }
    }
</script>
</body>
</html>