<?php
require_once('../global/config.php');
$title = "Business Profile";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 2){
    header("location:../login.php");
    exit;
}

if(!empty($_POST)){
    if ($_POST['FUNCTION_NAME'] == 'saveProfileData') {
        unset($_POST['FUNCTION_NAME']);
        $ACCOUNT_DATA = $_POST;
        if ($_FILES['BUSINESS_LOGO']['name'] != '') {
            $USER_DATA = [];
            $extn = explode(".", $_FILES['BUSINESS_LOGO']['name']);
            $iindex = count($extn) - 1;
            $rand_string = time() . "-" . rand(100000, 999999);
            $file11 = 'business_logo_' . $_SESSION['PK_USER'] . $rand_string . "." . $extn[$iindex];
            $extension = strtolower($extn[$iindex]);

            if ($extension == "gif" || $extension == "jpeg" || $extension == "pjpeg" || $extension == "png" || $extension == "jpg") {
                $image_path = '../uploads/business_logo/' . $file11;
                move_uploaded_file($_FILES['BUSINESS_LOGO']['tmp_name'], $image_path);
                $ACCOUNT_DATA['BUSINESS_LOGO'] = $image_path;
            }
        }

        $ACCOUNT_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
        $ACCOUNT_DATA['EDITED_ON'] = date("Y-m-d H:i");
        db_perform('DOA_ACCOUNT_MASTER', $ACCOUNT_DATA, 'update', " PK_ACCOUNT_MASTER =  '$_SESSION[PK_ACCOUNT_MASTER]'");
    }

    if ($_POST['FUNCTION_NAME'] == 'saveHolidayData') {
        unset($_POST['FUNCTION_NAME']);
        $db->Execute("DELETE FROM `DOA_HOLIDAY_LIST` WHERE `PK_ACCOUNT_MASTER` = '$_SESSION[PK_ACCOUNT_MASTER]'");
        for ($i=0; $i < count($_POST['HOLIDAY_DATE']); $i++) {
            $HOLIDAY_LIST_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
            $HOLIDAY_LIST_DATA['HOLIDAY_DATE'] = date('Y-m-d', strtotime($_POST['HOLIDAY_DATE'][$i]));
            $HOLIDAY_LIST_DATA['HOLIDAY_NAME'] = $_POST['HOLIDAY_NAME'][$i];
            db_perform('DOA_HOLIDAY_LIST', $HOLIDAY_LIST_DATA, 'insert');
        }
    }

    header("location:business_profile.php");
}

$res = $db->Execute("SELECT * FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
if($res->RecordCount() == 0){
    header("location:login.php");
    exit;
}
$PK_BUSINESS_TYPE   = $res->fields['PK_BUSINESS_TYPE'];
$API_KEY  	        = $res->fields['API_KEY'];
$BUSINESS_NAME 	    = $res->fields['BUSINESS_NAME'];
$BUSINESS_LOGO      = $res->fields['BUSINESS_LOGO'];
$ADDRESS 	        = $res->fields['ADDRESS'];
$ADDRESS_1          = $res->fields['ADDRESS_1'];
$CITY  	            = $res->fields['CITY'];
$PK_STATES 	        = $res->fields['PK_STATES'];
$ZIP 	            = $res->fields['ZIP'];
$PK_COUNTRY  	    = $res->fields['PK_COUNTRY'];
$PHONE 	            = $res->fields['PHONE'];
$FAX 	            = $res->fields['FAX'];
$EMAIL              = $res->fields['EMAIL'];
$WEBSITE  	        = $res->fields['WEBSITE'];
$PK_ACCOUNT_TYPE    = $res->fields['PK_ACCOUNT_TYPE'];
$PK_TIMEZONE        = $res->fields['PK_TIMEZONE'];
$ACTIVE             = $res->fields['ACTIVE'];
$SERVICE_PROVIDER_TITLE = $res->fields['SERVICE_PROVIDER_TITLE'];
$PK_CURRENCY            = $res->fields['PK_CURRENCY'];
$ENROLLMENT_ID_CHAR     = $res->fields['ENROLLMENT_ID_CHAR'];
$ENROLLMENT_ID_NUM      = $res->fields['ENROLLMENT_ID_NUM'];
$PAYMENT_GATEWAY_TYPE   = $res->fields['PAYMENT_GATEWAY_TYPE'];
$SECRET_KEY             = $res->fields['SECRET_KEY'];
$PUBLISHABLE_KEY        = $res->fields['PUBLISHABLE_KEY'];
$ACCESS_TOKEN           = $res->fields['ACCESS_TOKEN'];
$APP_ID                 = $res->fields['APP_ID'];
$LOCATION_ID            = $res->fields['LOCATION_ID'];

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
                            <li class="breadcrumb-item active"><?=$title?></li>
                        </ol>

                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <!-- Nav tabs -->
                            <ul class="nav nav-tabs" role="tablist">
                                <li class="active"> <a class="nav-link active" data-bs-toggle="tab" id="profile_link" href="#profile" role="tab"><span class="hidden-sm-up"><i class="ti-user"></i></span> <span class="hidden-xs-down">Profile</span></a> </li>
                                <li> <a class="nav-link" data-bs-toggle="tab" id="holiday_list_link" href="#holiday_list" role="tab"><span class="hidden-sm-up"><i class="ti-calendar"></i></span> <span class="hidden-xs-down">Holiday List</span></a> </li>
                            </ul>

                            <!-- Tab panes -->
                            <div class="tab-content tabcontent-border">
                                <div class="tab-pane active" id="profile" role="tabpanel">
                                    <form class="form-material form-horizontal" action="" method="post" enctype="multipart/form-data">
                                        <input type="hidden" name="FUNCTION_NAME" value="saveProfileData">
                                        <div class="p-20">
                                            <div class="row">
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12">Business Type<span class="text-danger">*</span>
                                                        </label>
                                                        <div class="col-md-12">
                                                            <select class="form-control" required name="PK_BUSINESS_TYPE" id="PK_BUSINESS_TYPE">
                                                                <option>Select Business Type</option>
                                                                <?php
                                                                $result_dropdown_query = mysqli_query($conn,"SELECT PK_BUSINESS_TYPE,BUSINESS_TYPE FROM DOA_BUSINESS_TYPE WHERE ACTIVE='1' ORDER BY PK_BUSINESS_TYPE");
                                                                while ($result_dropdown=mysqli_fetch_array($result_dropdown_query,MYSQLI_ASSOC)) { ?>
                                                                    <option value="<?php echo $result_dropdown['PK_BUSINESS_TYPE'];?>" <?php if($result_dropdown['PK_BUSINESS_TYPE'] == $PK_BUSINESS_TYPE) echo 'selected = "selected"';?> ><?=$result_dropdown['BUSINESS_TYPE']?></option>
                                                                    <?php
                                                                }
                                                                ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <!--<div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12">Account Type<span class="text-danger">*</span>
                                                        </label>
                                                        <div class="col-md-12">
                                                            <?php /*$i=1;
                                                            $result_dropdown_query = mysqli_query($conn,"select PK_ACCOUNT_TYPE,ACCOUNT_TYPE from DOA_ACCOUNT_TYPE WHERE ACTIVE='1' order by PK_ACCOUNT_TYPE");
                                                            while ($result_dropdown=mysqli_fetch_array($result_dropdown_query,MYSQLI_ASSOC)) { */?>
                                                                <input type="radio" id="PK_ACCOUNT_TYPE_<?php /*echo $i;*/?>" name="PK_ACCOUNT_TYPE" value="<?php /*echo $result_dropdown['PK_ACCOUNT_TYPE'];*/?>" <?php /*if($result_dropdown['PK_ACCOUNT_TYPE'] == $PK_ACCOUNT_TYPE) echo 'checked';*/?>>
                                                                <label for="contactChoice1"><?/*=$result_dropdown['ACCOUNT_TYPE']*/?></label>
                                                                <?php
/*                                                                $i++; }
                                                            */?>
                                                        </div>
                                                    </div>
                                                </div>-->
                                            </div>



                                            <div class="row">
                                                <div class="col-12">
                                                    <div class="form-group">
                                                        <label class="col-md-12">Business Name<span class="text-danger">*</span>
                                                        </label>
                                                        <div class="col-md-12">
                                                            <input type="text" id="BUSINESS_NAME" name="BUSINESS_NAME" class="form-control" placeholder="Enter Business Name" required data-validation-required-message="This field is required" value="<?php echo $BUSINESS_NAME?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>


                                            <div class="row">
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12">Address
                                                        </label>
                                                        <div class="col-md-12">
                                                            <textarea class="form-control" rows="2" id="ADDRESS" name="ADDRESS"><?php echo $ADDRESS?></textarea>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12">Apt/Ste
                                                        </label>
                                                        <div class="col-md-12">
                                                            <textarea class="form-control" rows="2" id="ADDRESS_1" name="ADDRESS_1" ><?php echo $ADDRESS_1?></textarea>
                                                        </div>
                                                    </div>

                                                </div>
                                            </div>


                                            <div class="row">
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12">Country<span class="text-danger">*</span>
                                                        </label>
                                                        <div class="col-md-12">
                                                            <div class="col-sm-12">
                                                                <select class="form-select" required name="PK_COUNTRY" id="PK_COUNTRY" onChange="fetch_state(this.value)">
                                                                    <option value="">Select Country</option>
                                                                    <?php
                                                                    $result_dropdown_query = mysqli_query($conn,"select PK_COUNTRY,COUNTRY_NAME from DOA_COUNTRY WHERE ACTIVE='1' order by PK_COUNTRY");
                                                                    while ($result_dropdown=mysqli_fetch_array($result_dropdown_query,MYSQLI_ASSOC)) { ?>
                                                                        <option value="<?php echo $result_dropdown['PK_COUNTRY'];?>" <?php if($result_dropdown['PK_COUNTRY'] == $PK_COUNTRY) echo 'selected = "selected"';?> ><?=$result_dropdown['COUNTRY_NAME']?></option>
                                                                        <?php
                                                                    }
                                                                    ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12">State<span class="text-danger">*</span>
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
                                                        <label class="col-md-12">City</span>
                                                        </label>
                                                        <div class="col-md-12">
                                                            <input type="text" id="CITY" name="CITY" class="form-control" placeholder="Enter Your City" value="<?php echo $CITY?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12">Zip Code</span>
                                                        </label>
                                                        <div class="col-md-12">
                                                            <input type="text" id="ZIP" name="ZIP" class="form-control" placeholder="Enter Zip Code" value="<?php echo $ZIP?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-6">

                                                    <div class="form-group">
                                                        <label class="col-md-12">Business Phone
                                                        </label>
                                                        <div class="col-md-12">
                                                            <input type="text" id="PHONE" name="PHONE" class="form-control" placeholder="Enter Phone Number" value="<?php echo $PHONE?>">
                                                        </div>
                                                    </div>

                                                </div>
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12">Business Fax
                                                        </label>
                                                        <div class="col-md-12">
                                                            <input type="text" id="FAX" name="FAX" class="form-control" placeholder="Enter Fax" value="<?php echo $FAX;?>">
                                                        </div>
                                                    </div>

                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12">Business Email<span class="text-danger">*</span>
                                                        </label>
                                                        <div class="col-md-12">
                                                            <input type="email" id="EMAIL" name="EMAIL" class="form-control" placeholder="Enter Email" required data-validation-required-message="This field is required" value="<?php echo $EMAIL?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12">Website
                                                        </label>
                                                        <div class="col-md-12">
                                                            <input type="text" id="WEBSITE" name="WEBSITE" class="form-control" placeholder="Enter Website" value="<?php echo $WEBSITE?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row" style="margin-bottom: 15px;">
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12">Business Logo
                                                        </label>
                                                        <div class="col-md-12">
                                                            <input type="file" name="BUSINESS_LOGO" id="BUSINESS_LOGO" class="form-control" >
                                                        </div>
                                                    </div>
                                                    <?php if($BUSINESS_LOGO!=''){?><div style="width: 120px;height: 120px;margin-top: 25px;"><a class="fancybox" href="<?php echo $BUSINESS_LOGO;?>" data-fancybox-group="gallery"><img src = "<?php echo $BUSINESS_LOGO;?>" style="width:auto; height:120px" /></a></div><?php } ?>
                                                </div>

                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12">Timezone<span class="text-danger">*</span></label>
                                                        <div class="col-md-12">
                                                            <select name="PK_TIMEZONE" id="PK_TIMEZONE" class="form-control" required>
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

                                            <div class="row">
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12">Service Provider Title</label>
                                                        <div class="col-md-12">
                                                            <input type="text" id="SERVICE_PROVIDER_TITLE" name="SERVICE_PROVIDER_TITLE" class="form-control" placeholder="Enter Service Provider Title" value="<?php echo $SERVICE_PROVIDER_TITLE?>">
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12">Currency</label>
                                                        <div class="col-md-12">
                                                            <select name="PK_CURRENCY" id="PK_CURRENCY" class="form-control required-entry">
                                                                <? $res_type = $db->Execute("SELECT * FROM `DOA_CURRENCY` WHERE `ACTIVE` = 1");
                                                                while (!$res_type->EOF) { ?>
                                                                    <option value="<?=$res_type->fields['PK_CURRENCY']?>" <?=($res_type->fields['PK_CURRENCY'] == $PK_CURRENCY)?'selected':''?>><?=$res_type->fields['CURRENCY_NAME']." (".$res_type->fields['CURRENCY_SYMBOL'].")"?></option>
                                                                    <?	$res_type->MoveNext();
                                                                } ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12">Enrollment Id Character</label>
                                                        <div class="col-md-12">
                                                            <input type="text" id="ENROLLMENT_ID_CHAR" name="ENROLLMENT_ID_CHAR" class="form-control" placeholder="Enrollment Id Character" value="<?php echo $ENROLLMENT_ID_CHAR?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12">Enrollment Id Number</label>
                                                        <div class="col-md-12">
                                                            <input type="number" id="ENROLLMENT_ID_NUM" name="ENROLLMENT_ID_NUM" class="form-control" placeholder="Enrollment Id Number" value="<?php echo $ENROLLMENT_ID_NUM?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="form-label" style="margin-bottom: 5px;">Payment Gateway</label><br>
                                                        <label style="margin-right: 70px;"><input type="radio" id="PAYMENT_GATEWAY_TYPE" name="PAYMENT_GATEWAY_TYPE" class="form-check-inline" value="Stripe" <?=($PAYMENT_GATEWAY_TYPE=='Stripe')?'checked':''?> onclick="showPaymentGateway(this);">Stripe</label>
                                                        <label><input type="radio" id="PAYMENT_GATEWAY_TYPE" name="PAYMENT_GATEWAY_TYPE" class="form-check-inline" value="Square" <?=($PAYMENT_GATEWAY_TYPE=='Square')?'checked':''?> onclick="showPaymentGateway(this);">Square</label>
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
                                                        <label class="form-label">Access Token</label>
                                                        <input type="text" class="form-control" name="ACCESS_TOKEN" value="<?=$ACCESS_TOKEN?>">
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="form-label">Application ID</label>
                                                        <input type="text" class="form-control" name="APP_ID" value="<?=$APP_ID?>">
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="form-label">Location ID</label>
                                                        <input type="text" class="form-control" name="LOCATION_ID" value="<?=$LOCATION_ID?>">
                                                    </div>
                                                </div>
                                            </div>

                                            <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">Submit</button>
                                            <button type="button" class="btn btn-inverse waves-effect waves-light" onclick="window.location.href='business_profile.php'">Cancel</button>
                                        </div>
                                    </form>
                                </div>

                                <div class="tab-pane" id="holiday_list" role="tabpanel">
                                    <form class="form-material form-horizontal" action="" method="post" enctype="multipart/form-data">
                                        <input type="hidden" name="FUNCTION_NAME" value="saveHolidayData">
                                        <div class="p-20" id="holiday_list_div">
                                            <div class="row">
                                                <div class="col-3">
                                                    <div class="form-group" style="text-align: center;">
                                                        <label class="form-label" style="font-weight: bold;">Holiday Date</label>
                                                    </div>
                                                </div>
                                                <div class="col-3">
                                                    <div class="form-group" style="text-align: center;">
                                                        <label class="form-label" style="font-weight: bold;">Holiday Name</label>
                                                    </div>
                                                </div>
                                                <div class="col-3" style="margin-top: -30px;">
                                                    <a href="javascript:;" class="btn btn-info waves-effect waves-light text-white" style="margin-top: 30px;" onclick="addMoreHoliday();">Add More</a>
                                                </div>
                                            </div>
                                            <?php
                                            $holiday_list = $db->Execute("SELECT * FROM DOA_HOLIDAY_LIST WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
                                            if($holiday_list->RecordCount() > 0) {
                                                while (!$holiday_list->EOF) { ?>
                                                <div class="row">
                                                    <div class="col-3">
                                                        <div class="form-group">
                                                            <div class="col-md-12">
                                                                <input type="text" name="HOLIDAY_DATE[]" class="form-control datepicker-normal" value="<?=date('m/d/Y', strtotime($holiday_list->fields['HOLIDAY_DATE']))?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-3">
                                                        <div class="form-group">
                                                            <div class="col-md-12">
                                                                <input type="text" name="HOLIDAY_NAME[]" class="form-control" value="<?=$holiday_list->fields['HOLIDAY_NAME']?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-3" style="padding-top: 5px;">
                                                        <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                                    </div>
                                                </div>
                                                <?php $holiday_list->MoveNext(); } ?>
                                            <?php } else { ?>
                                                <div class="row">
                                                    <div class="col-3">
                                                        <div class="form-group">
                                                            <div class="col-md-12">
                                                                <input type="text" name="HOLIDAY_DATE[]" class="form-control datepicker-normal">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-3">
                                                        <div class="form-group">
                                                            <div class="col-md-12">
                                                                <input type="text" name="HOLIDAY_NAME[]" class="form-control">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-3" style="padding-top: 5px;">
                                                        <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                                    </div>
                                                </div>
                                            <?php } ?>
                                        </div>
                                        <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">Save</button>
                                        <button type="button" class="btn btn-inverse waves-effect waves-light" onclick="window.location.href='business_profile.php'">Cancel</button>
                                    </form>
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
        $('.datepicker-normal').datepicker({
            format: 'mm/dd/yyyy',
        });

        $(document).ready(function() {
            fetch_state(<?php  echo $PK_COUNTRY; ?>);
        });

        function fetch_state(PK_COUNTRY){
            $(document).ready(function(event) {
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


        function removeThis(param) {
            $(param).closest('.row').remove();
        }

        function addMoreHoliday(){
            $('#holiday_list_div').append(`<div class="row">
                                                <div class="col-3">
                                                    <div class="form-group">
                                                        <div class="col-md-12">
                                                            <input type="text" name="HOLIDAY_DATE[]" class="form-control datepicker-normal">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-3">
                                                    <div class="form-group">
                                                        <div class="col-md-12">
                                                            <input type="text" name="HOLIDAY_NAME[]" class="form-control">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-3" style="padding-top: 5px;">
                                                    <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                                </div>
                                            </div>`);

            $('.datepicker-normal').datepicker({
                format: 'mm/dd/yyyy',
            });
        }

        function showPaymentGateway(param) {
            $('.payment_gateway').slideUp();
            if($(param).val() === 'Stripe'){
                $('#stripe').slideDown();
            }else {
                if($(param).val() === 'Square'){
                    $('#square').slideDown();
                }
            }
        }
    </script>

</body>
</html>