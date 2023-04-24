<?php
require_once('../global/config.php');
if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 1 ){
    header("location:../login.php");
    exit;
}

if (empty($_GET['id']))
    $title = "Add User";
else
    $title = "Edit User";

if(!empty($_POST)){
    $USER_DATA['PK_ACCOUNT_MASTER'] = 0;
    $USER_DATA['PK_ROLES'] = $_POST['PK_ROLES'];
    $USER_DATA['USER_ID'] = $_POST['USER_ID'];
    $USER_DATA['FIRST_NAME'] = $_POST['FIRST_NAME'];
    $USER_DATA['LAST_NAME'] = $_POST['LAST_NAME'];
    $USER_DATA['EMAIL_ID'] = $_POST['EMAIL_ID'];
    $USER_DATA['PHONE'] = $_POST['PHONE'];
    if (!empty($_POST['PASSWORD'])) {
        $USER_DATA['CREATE_LOGIN'] = 1;
        $USER_DATA['PASSWORD'] = password_hash($_POST['PASSWORD'], PASSWORD_DEFAULT);
    }

    if($_FILES['USER_IMAGE']['name'] != ''){
        $extn 			= explode(".",$_FILES['USER_IMAGE']['name']);
        $iindex			= count($extn) - 1;
        $rand_string 	= time()."-".rand(100000,999999);
        $file11			= 'user_image_'.$_SESSION['PK_USER'].$rand_string.".".$extn[$iindex];
        $extension   	= strtolower($extn[$iindex]);

        if($extension == "gif" || $extension == "jpeg" || $extension == "pjpeg" || $extension == "png" || $extension == "jpg"){
            $image_path    = '../uploads/user_image/'.$file11;
            move_uploaded_file($_FILES['USER_IMAGE']['tmp_name'], $image_path);
            $USER_DATA['USER_IMAGE'] = $image_path;
        }
    }

    $USER_PROFILE_DATA['GENDER'] = $_POST['GENDER'];
    $USER_PROFILE_DATA['DOB'] = date('Y-m-d', strtotime($_POST['DOB']));
    $USER_PROFILE_DATA['ADDRESS'] = $_POST['ADDRESS'];
    $USER_PROFILE_DATA['ADDRESS_1'] = $_POST['ADDRESS_1'];
    $USER_PROFILE_DATA['PK_COUNTRY'] = $_POST['PK_COUNTRY'];
    $USER_PROFILE_DATA['PK_STATES'] = $_POST['PK_STATES'];
    $USER_PROFILE_DATA['CITY'] = $_POST['CITY'];
    $USER_PROFILE_DATA['ZIP'] = $_POST['ZIP'];
    /*$USER_PROFILE_DATA['FAX'] = $_POST['FAX'];
    $USER_PROFILE_DATA['WEBSITE'] = $_POST['WEBSITE'];*/
    $USER_PROFILE_DATA['NOTES'] = $_POST['NOTES'];

    if(empty($_GET['id'])){
        $USER_DATA['ACTIVE'] = 1;
        $USER_DATA['CREATED_BY']  = $_SESSION['PK_USER'];
        $USER_DATA['CREATED_ON']  = date("Y-m-d H:i");
        db_perform('DOA_USERS', $USER_DATA, 'insert');
        $PK_USER = $db->insert_ID();
        $USER_PROFILE_DATA['PK_USER'] = $PK_USER;
        $USER_PROFILE_DATA['ACTIVE'] = 1;
        $USER_PROFILE_DATA['CREATED_BY']  = $_SESSION['PK_USER'];
        $USER_PROFILE_DATA['CREATED_ON']  = date("Y-m-d H:i");
        db_perform('DOA_USER_PROFILE', $USER_PROFILE_DATA, 'insert');
    }else{
        $USER_DATA['ACTIVE'] = $_POST['ACTIVE'];
        $USER_DATA['EDITED_BY']	= $_SESSION['PK_USER'];
        $USER_DATA['EDITED_ON'] = date("Y-m-d H:i");
        db_perform('DOA_USERS', $USER_DATA, 'update'," PK_USER =  '$_GET[id]'");
        $USER_PROFILE_DATA['ACTIVE'] = $_POST['ACTIVE'];
        $USER_PROFILE_DATA['EDITED_BY']	= $_SESSION['PK_USER'];
        $USER_PROFILE_DATA['EDITED_ON'] = date("Y-m-d H:i");
        db_perform('DOA_USER_PROFILE', $USER_PROFILE_DATA, 'update'," PK_USER =  '$_GET[id]'");
    }
    header("location:all_users.php");
}

if(empty($_GET['id'])){
    $PK_ROLES = '';
    $USER_ID = '';
    $FIRST_NAME = '';
    $LAST_NAME = '';
    $EMAIL_ID = '';
    $USER_IMAGE = '';
    $PASSWORD = '';
    $GENDER = '';
    $DOB = '';
    $ADDRESS = '';
    $ADDRESS_1 = '';
    $PK_COUNTRY = '';
    $PK_STATES = '';
    $CITY = '';
    $ZIP = '';
    $PHONE = '';
    $FAX = '';
    $WEBSITE = '';
    $NOTES = '';
    $ACTIVE = '';
}
else {
    $res = $db->Execute("SELECT DOA_USERS.PK_ROLES, DOA_USERS.FIRST_NAME, DOA_USERS.LAST_NAME, DOA_USERS.USER_ID, DOA_USERS.EMAIL_ID, DOA_USERS.USER_IMAGE, DOA_USERS.PASSWORD, DOA_USERS.ACTIVE, DOA_USER_PROFILE.GENDER, DOA_USER_PROFILE.DOB, DOA_USER_PROFILE.ADDRESS, DOA_USER_PROFILE.ADDRESS_1, DOA_USER_PROFILE.CITY, DOA_USER_PROFILE.PK_STATES, DOA_USER_PROFILE.ZIP, DOA_USER_PROFILE.PK_COUNTRY, DOA_USERS.PHONE, DOA_USER_PROFILE.FAX, DOA_USER_PROFILE.WEBSITE, DOA_USER_PROFILE.NOTES FROM DOA_USERS LEFT JOIN DOA_USER_PROFILE ON DOA_USERS.PK_USER = DOA_USER_PROFILE.PK_USER WHERE DOA_USERS.PK_USER = '$_GET[id]'");

    if($res->RecordCount() == 0){
        header("location:all_users.php");
        exit;
    }

    $PK_ROLES = $res->fields['PK_ROLES'];
    $USER_ID = $res->fields['USER_ID'];
    $FIRST_NAME = $res->fields['FIRST_NAME'];
    $LAST_NAME = $res->fields['LAST_NAME'];
    $EMAIL_ID = $res->fields['EMAIL_ID'];
    $USER_IMAGE = $res->fields['USER_IMAGE'];
    $PASSWORD = $res->fields['PASSWORD'];
    $GENDER = $res->fields['GENDER'];
    $DOB = $res->fields['DOB'];
    $ADDRESS = $res->fields['ADDRESS'];
    $ADDRESS_1 = $res->fields['ADDRESS_1'];
    $PK_COUNTRY = $res->fields['PK_COUNTRY'];
    $PK_STATES = $res->fields['PK_STATES'];
    $CITY = $res->fields['CITY'];
    $ZIP = $res->fields['ZIP'];
    $PHONE = $res->fields['PHONE'];
    $FAX = $res->fields['FAX'];
    $WEBSITE = $res->fields['WEBSITE'];
    $NOTES = $res->fields['NOTES'];
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
        <div class="container-fluid">
            <div class="row page-titles">
                <div class="col-md-5 align-self-center">
                    <h4 class="text-themecolor"><?=$title?></h4>
                </div>
                <div class="col-md-7 align-self-center text-end">
                    <div class="d-flex justify-content-end align-items-center">
                        <ol class="breadcrumb justify-content-end">
                            <li class="breadcrumb-item"><a href="setup.php">Setup</a></li>
                            <li class="breadcrumb-item"><a href="all_users.php">All Users</a></li>
                            <li class="breadcrumb-item active"><?=$title?></li>
                        </ol>

                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <form class="form-material form-horizontal" id="user_form" action="" method="post" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="card">
                                            <div class="card-body">
                                                <!-- Nav tabs -->
                                                <ul class="nav nav-tabs" role="tablist">
                                                    <li class="active"> <a class="nav-link active" data-bs-toggle="tab" href="#login" role="tab"><span class="hidden-sm-up"><i class="ti-home"></i></span> <span class="hidden-xs-down">User Login Info</span></a> </li>
                                                    <li> <a class="nav-link" data-bs-toggle="tab" href="#profile" role="tab" ><span class="hidden-sm-up"><i class="ti-folder"></i></span> <span class="hidden-xs-down">User Profile</span></a> </li>
                                                </ul>
                                                <!-- Tab panes -->
                                                <div class="tab-content tabcontent-border">
                                                    <div class="tab-pane active" id="login" role="tabpanel">
                                                        <div class="p-20">

                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <label class="form-label">Roles</label>
                                                                    <select class="form-control" name="PK_ROLES" id="PK_ROLES">
                                                                        <?php
                                                                        $row = $db->Execute("SELECT PK_ROLES, ROLES FROM DOA_ROLES WHERE ACTIVE=1 AND PK_ROLES=1 ORDER BY PK_ROLES");
                                                                        while (!$row->EOF) { ?>
                                                                            <option value="<?php echo $row->fields['PK_ROLES'];?>"><?=$row->fields['ROLES']?></option>
                                                                            <?php $row->MoveNext(); } ?>
                                                                    </select>
                                                                </div>

                                                                <div class="col-6">
                                                                    <div class="form-group">
                                                                        <label class="col-md-12" for="example-text">User Name<span class="text-danger">*</span>
                                                                        </label>
                                                                        <div class="col-md-12">
                                                                            <input type="text" id="USER_ID" name="USER_ID" class="form-control" placeholder="Enter User Name" required data-validation-required-message="This field is required" onkeyup="ValidateUsername()" value="<?=$USER_ID?>">
                                                                        </div>
                                                                    </div>
                                                                    <span id="lblError" style="color: red"></span>
                                                                </div>
                                                            </div>



                                                            <div class="row">
                                                                <div class="col-6">
                                                                    <div class="form-group">
                                                                        <label class="col-md-12" for="example-text">First Name<span class="text-danger">*</span>
                                                                        </label>
                                                                        <div class="col-md-12">
                                                                            <input type="text" id="FIRST_NAME" name="FIRST_NAME" class="form-control" placeholder="Enter First Name" required data-validation-required-message="This field is required" value="<?=$FIRST_NAME?>">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-6">
                                                                    <div class="form-group">
                                                                        <label class="col-md-12" for="example-text">Last Name
                                                                        </label>
                                                                        <div class="col-md-12">
                                                                            <input type="text" id="LAST_NAME" name="LAST_NAME" class="form-control" placeholder="Enter Last Name" value="<?=$LAST_NAME?>">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="row">
                                                                <div class="col-6">
                                                                    <div class="form-group">
                                                                        <label class="col-md-12" for="example-text">Email<span class="text-danger">*</span>
                                                                        </label>
                                                                        <div class="col-md-12">
                                                                            <input type="email" id="EMAIL_ID" name="EMAIL_ID" class="form-control" placeholder="Enter Email Address" required data-validation-required-message="This field is required" value="<?=$EMAIL_ID?>">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <?php if(empty($_GET['id'])) { ?>
                                                                <div class="row">
                                                                    <div class="col-6">
                                                                        <div class="form-group">
                                                                            <label class="col-md-12">Password</label>
                                                                            <div class="col-md-12">
                                                                                <input type="password" required class="form-control" placeholder="Password" aria-label="Password" aria-describedby="basic-addon3" name="PASSWORD" id="PASSWORD" onkeyup="isGood(this.value)">
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-6">
                                                                        <div class="form-group">
                                                                            <label class="col-md-12">Confirm Password</label>
                                                                            <div class="col-md-12">
                                                                                <input type="password" required class="form-control" placeholder="Confirm Password" aria-label="Password" aria-describedby="basic-addon3" name="CONFIRM_PASSWORD" id="CONFIRM_PASSWORD" onkeyup="isGood(this.value)">
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <b id="password_error" style="color: red;"></b>
                                                                <div class="row">
                                                                    <div class="col-12">
                                                                        <span style="color:red">Note  : Password Must contain at least one number and one uppercase and lowercase letter, and at least 8 or more characters</span>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-2">
                                                                        Password Strength:
                                                                    </div>
                                                                    <div class="col-3">
                                                                        <small id="password-text"></small>
                                                                    </div>
                                                                </div>
                                                            <?php } else { ?>
                                                                <div class="row">
                                                                    <div class="col-2">
                                                                        <a class="btn btn-info waves-effect waves-light m-r-10 text-white" onclick="$('#change_password_div').slideToggle();">Change Password</a>
                                                                    </div>
                                                                    <div class="row" id="change_password_div" style="padding: 20px 20px 0px 20px; display: none;">
                                                                        <div class="col-3">
                                                                            <div class="form-group">
                                                                                <label class="form-label">Old Password</label>
                                                                                <input type="hidden" name="SAVED_OLD_PASSWORD" id="SAVED_OLD_PASSWORD" value="<?=$PASSWORD?>">
                                                                                <input type="password" name="OLD_PASSWORD" id="OLD_PASSWORD" class="form-control">
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-3">
                                                                            <div class="form-group">
                                                                                <label class="form-label">New Password</label>
                                                                                <input type="password" name="PASSWORD" id="PASSWORD" class="form-control">
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-3">
                                                                            <div class="form-group">
                                                                                <label class="form-label">Confirm New Password</label>
                                                                                <input type="password" name="CONFIRM_PASSWORD" id="CONFIRM_PASSWORD" class="form-control">
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <b id="password_error" style="color: red;"></b>
                                                                </div>
                                                            <?php } ?>

                                                            <?php if(!empty($_GET['id'])) { ?>
                                                                <div class="row" style="margin-bottom: 15px; margin-top: 15px;">
                                                                    <div class="col-md-1">
                                                                        <label class="form-label">Active : </label>
                                                                    </div>
                                                                    <div class="col-md-4">
                                                                        <label><input type="radio" name="ACTIVE" id="ACTIVE" value="1" <? if($ACTIVE == 1) echo 'checked="checked"'; ?> />&nbsp;Yes</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                                        <label><input type="radio" name="ACTIVE" id="ACTIVE" value="0" <? if($ACTIVE == 0) echo 'checked="checked"'; ?> />&nbsp;No</label>
                                                                    </div>
                                                                </div>
                                                            <? } ?>
                                                        </div>
                                                    </div>



                                                    <div class="tab-pane  p-20" id="profile" role="tabpanel">
                                                        <div class="p-20">
                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <div class="form-group has-success">
                                                                        <label class="form-label">Gender</label>
                                                                        <select class="form-control form-select" id="GENDER" name="GENDER">
                                                                            <option value="">Select Gender</option>
                                                                            <option value="Male" <?php if($GENDER == "Male") echo 'selected = "selected"';?>>Male</option>
                                                                            <option value="Female" <?php if($GENDER == "Female") echo 'selected = "selected"';?>>Female</option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <div class="form-group">
                                                                        <label class="form-label">Date of Birth</label>
                                                                        <input type="text" class="form-control datepicker-past"  id="DOB" name="DOB" value="<?=($DOB)?date('m/d/Y', strtotime($DOB)):''?>">
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
                                                                            <input type="text" id="ADDRESS_1" name="ADDRESS_1" class="form-control" placeholder="Enter Address" value="<?php echo $ADDRESS_1?>">

                                                                        </div>
                                                                    </div>

                                                                </div>
                                                            </div>


                                                            <div class="row">
                                                                <div class="col-6">
                                                                    <div class="form-group">
                                                                        <label class="col-md-12" for="example-text">Country</label>
                                                                        <div class="col-md-12">
                                                                            <div class="col-sm-12">
                                                                                <select class="form-control" name="PK_COUNTRY" id="PK_COUNTRY" onChange="fetch_state(this.value)">
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
                                                                        <label class="col-md-12" for="example-text">State</label>
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
                                                                            <input type="text" id="CITY" name="CITY" class="form-control" placeholder="Enter your city" value="<?php echo $CITY?>">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-6">
                                                                    <div class="form-group">
                                                                        <label class="col-md-12" for="example-text">Zip Code</span>
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
                                                                        <label class="col-md-12" for="example-text">Phone
                                                                        </label>
                                                                        <div class="col-md-12">
                                                                            <input type="text" id="PHONE" name="PHONE" class="form-control" placeholder="Enter Phone No." value="<?php echo $PHONE?>">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-6">
                                                                    <div class="form-group">
                                                                        <label class="col-md-12">Remarks</label>
                                                                        <div class="col-md-12">
                                                                            <textarea class="form-control" rows="2" id="NOTES" name="NOTES"><?php echo $NOTES?></textarea>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <!--<div class="col-6">
                                                                    <div class="form-group">
                                                                        <label class="col-md-12" for="example-text">Fax
                                                                        </label>
                                                                        <div class="col-md-12">
                                                                            <input type="text" id="FAX" name="FAX" class="form-control" placeholder="Enter Fax" value="<?php /*echo $FAX;*/?>">
                                                                        </div>
                                                                    </div>
                                                                </div>-->
                                                            </div>


                                                            <div class="row">
                                                                <!--<div class="col-6">
                                                                    <div class="form-group">
                                                                        <label class="col-md-12" for="example-text">Website
                                                                        </label>
                                                                        <div class="col-md-12">
                                                                            <input type="text" id="WEBSITE" name="WEBSITE" class="form-control" placeholder="Enter Website" value="<?php /*echo $WEBSITE*/?>">
                                                                        </div>
                                                                    </div>
                                                                </div>-->
                                                                <div class="col-6">
                                                                    <div class="form-group">
                                                                        <label class="col-md-12" for="example-text">Image Upload
                                                                        </label>
                                                                        <div class="col-md-12">
                                                                            <input type="file" name="USER_IMAGE" id="USER_IMAGE" class="form-control">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="form-group">
                                                                <?php if($USER_IMAGE!=''){?><div style="width: 120px;height: 120px;margin-top: 25px;"><a class="fancybox" href="<?php echo $USER_IMAGE;?>" data-fancybox-group="gallery"><img src = "<?php echo $USER_IMAGE;?>" style="width:120px; height:120px" /></a></div><?php } ?>
                                                            </div>

                                                        </div>
                                                    </div>
                                                </div>
                                                <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">Submit</button>
                                                <button type="button" onclick="window.location.href='all_users.php?type=<?=$type?>'" class="btn btn-inverse waves-effect waves-light">Cancel</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
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
        $('.datepicker-past').datepicker({
            format: 'mm/dd/yyyy',
            maxDate: 0
        });

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


    </script>
    <script>
        function isGood(password) {
            //alert(password);
            var password_strength = document.getElementById("password-text");

            //TextBox left blank.
            if (password.length == 0) {
                password_strength.innerHTML = "";
                return;
            }

            //Regular Expressions.
            var regex = new Array();
            regex.push("[A-Z]"); //Uppercase Alphabet.
            regex.push("[a-z]"); //Lowercase Alphabet.
            regex.push("[0-9]"); //Digit.
            regex.push("[$@$!%*#?&]"); //Special Character.

            var passed = 0;

            //Validate for each Regular Expression.
            for (var i = 0; i < regex.length; i++) {
                if (new RegExp(regex[i]).test(password)) {
                    passed++;
                }
            }

            //Display status.
            var strength = "";
            switch (passed) {
                case 0:
                case 1:
                case 2:
                    strength = "<small class='progress-bar bg-danger' style='width: 50%'>Weak</small>";
                    break;
                case 3:
                    strength = "<small class='progress-bar bg-warning' style='width: 60%'>Medium</small>";
                    break;
                case 4:
                    strength = "<small class='progress-bar bg-success' style='width: 100%'>Strong</small>";
                    break;

            }
            // alert(strength);
            password_strength.innerHTML = strength;

        }

        function ValidateUsername() {
            var username = document.getElementById("User_Id").value;
            var lblError = document.getElementById("lblError");
            lblError.innerHTML = "";
            var expr = /^[a-zA-Z0-9_]{8,20}$/;
            if (!expr.test(username)) {
                lblError.innerHTML = "Only Alphabets, Numbers and Underscore and between 8 to 20 characters.";
            }
            else{
                lblError.innerHTML = "";
            }
        }

        $(document).on('submit', '#user_form', function (event) {
            //event.preventDefault();
            let PASSWORD = $('#PASSWORD').val();
            let CONFIRM_PASSWORD = $('#CONFIRM_PASSWORD').val();
            if (PASSWORD === CONFIRM_PASSWORD) {
                let SAVED_OLD_PASSWORD = $('#SAVED_OLD_PASSWORD').val();
                let OLD_PASSWORD = $('#OLD_PASSWORD').val();
                if (SAVED_OLD_PASSWORD)
                {
                    $.ajax({
                        url: "ajax/check_old_password.php",
                        type: 'POST',
                        data: {ENTERED_PASSWORD: OLD_PASSWORD, SAVED_PASSWORD: SAVED_OLD_PASSWORD},
                        success: function (data) {
                            if (data == 0){
                                $('#password_error').text('Old Password not matched');
                                return false;
                            }else{
                                return true;
                            }
                        }
                    });
                }else {
                    return true;
                }
            }else{
                $('#password_error').text('Password and Confirm Password not matched');
                return false;
            }
        });
    </script>




</body>
</html>