<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $upload_path;

$title = "My Profile";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 2 ){
    header("location:../login.php");
    exit;
}

$err_msg = '';
$success_msg = '';
$PK_USER = $_SESSION['PK_USER'];
if(!empty($_POST)){
    if ($_POST['FORM_TYPE'] == 'change_password_form'){
        if ($_POST['NEW_PASSWORD'] == $_POST['CONFIRM_NEW_PASSWORD']){
            $result = $db->Execute("SELECT PASSWORD FROM `DOA_USERS` WHERE PK_USER = '$_SESSION[PK_USER]'");
            if($result->RecordCount() > 0) {
                if (password_verify($_POST['OLD_PASSWORD'], $result->fields['PASSWORD'])) {
                    $USER_DATA['PASSWORD'] = password_hash($_POST['NEW_PASSWORD'], PASSWORD_DEFAULT);
                    db_perform('DOA_USERS', $USER_DATA, 'update', " PK_USER =  '$_SESSION[PK_USER]'");
                    $success_msg = "Password Changed Successfilly.";
                }else{
                    $err_msg = 'Old Password is Wrong.';
                }
            }
        }else{
           $err_msg = 'Password and Confirm Password Not Matched.';
        }
    }else {
        if ($_FILES['USER_IMAGE']['name'] != '') {
            if (!file_exists('../'.$upload_path.'/user_image/')) {
                mkdir('../'.$upload_path.'/user_image/', 0777, true);
            }

            $USER_DATA = [];
            $extn = explode(".", $_FILES['USER_IMAGE']['name']);
            $iindex = count($extn) - 1;
            $rand_string = time() . "-" . rand(100000, 999999);
            $file11 = 'user_image_' . $_SESSION['PK_USER'] . $rand_string . "." . $extn[$iindex];
            $extension = strtolower($extn[$iindex]);

            if ($extension == "gif" || $extension == "jpeg" || $extension == "pjpeg" || $extension == "png" || $extension == "jpg") {
                $image_path = '../'.$upload_path.'/user_image/' . $file11;
                move_uploaded_file($_FILES['USER_IMAGE']['tmp_name'], $image_path);
                $USER_DATA['USER_IMAGE'] = $image_path;
            }
        }
        $USER_DATA['PHONE'] = $_POST['PHONE'];
        $USER_DATA['GENDER'] = $_POST['GENDER'];
        $USER_DATA['DOB'] = date('Y-m-d', strtotime($_POST['DOB']));
        $USER_DATA['ADDRESS'] = $_POST['ADDRESS'];
        $USER_DATA['ADDRESS_1'] = $_POST['ADDRESS_1'];
        $USER_DATA['PK_COUNTRY'] = $_POST['PK_COUNTRY'];
        $USER_DATA['PK_STATES'] = $_POST['PK_STATES'];
        $USER_DATA['CITY'] = $_POST['CITY'];
        $USER_DATA['ZIP'] = $_POST['ZIP'];
        $USER_DATA['NOTES'] = $_POST['NOTES'];
        db_perform('DOA_USERS', $USER_DATA, 'update', " PK_USER = ".$PK_USER);

        $USER_DATA_ACCOUNT['PHONE'] = $_POST['PHONE'];
        db_perform_account('DOA_USERS', $USER_DATA_ACCOUNT, 'update', " PK_USER_MASTER_DB = ".$PK_USER);
    }
}

$res = $db->Execute("SELECT * FROM DOA_USERS WHERE PK_USER = ".$PK_USER);

if($res->RecordCount() == 0){
    header("location:../login.php");
    exit;
}

$selected_roles_row = $db->Execute("SELECT DOA_ROLES.ROLES FROM `DOA_USER_ROLES` LEFT JOIN DOA_ROLES ON DOA_USER_ROLES.PK_ROLES = DOA_ROLES.PK_ROLES WHERE `PK_USER` = '$PK_USER'");
while (!$selected_roles_row->EOF) {
    $selected_roles[] = $selected_roles_row->fields['ROLES'];
    $selected_roles_row->MoveNext();
}

$USER_NAME = $res->fields['USER_NAME'];
$FIRST_NAME = $res->fields['FIRST_NAME'];
$LAST_NAME = $res->fields['LAST_NAME'];
$EMAIL_ID = $res->fields['EMAIL_ID'];
$USER_IMAGE = $res->fields['USER_IMAGE'];
$GENDER = $res->fields['GENDER'];
$DOB = $res->fields['DOB'];
$ADDRESS = $res->fields['ADDRESS'];
$ADDRESS_1 = $res->fields['ADDRESS_1'];
$PK_COUNTRY = $res->fields['PK_COUNTRY'];
$PK_STATES = $res->fields['PK_STATES'];
$CITY = $res->fields['CITY'];
$ZIP = $res->fields['ZIP'];
$PHONE = $res->fields['PHONE'];
$NOTES = $res->fields['NOTES'];
$ACTIVE = $res->fields['ACTIVE'];
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
            </div>

                <div class="row">

                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">

                                <div class="row">
                                    <div class="col-2">
                                        <label class="col-md-12" for="example-text">First Name : </label>
                                    </div>
                                    <div class="col-3">
                                        <label style="color: #ff9800; "><?php echo $FIRST_NAME?></label>
                                    </div>

                                    <div class="col-2">
                                        <label class="col-md-12" for="example-text">Last Name : </label>
                                    </div>
                                    <div class="col-3">
                                        <label style="color: #ff9800; "><?php echo $LAST_NAME?></label>
                                    </div>

                                    <div class="col-2">
                                        <a class="btn btn-info waves-effect waves-light m-r-10 text-white" onclick="$('#change_password_div').slideToggle();">Change Password</a>
                                    </div>
                                </div>
                                </br>
                                <div class="row">
                                    <div class="col-1">
                                        <label class="col-md-12" for="example-text">Role : </label>
                                    </div>
                                    <div class="col-3">
                                        <label style="color: #ff9800; "><?=implode(', ', $selected_roles)?></label>
                                    </div>

                                    <div class="col-1">
                                        <label class="col-md-12" for="example-text">Email Id : </label>
                                    </div>
                                    <div class="col-3">
                                        <label style="color: #ff9800; "><?php echo $EMAIL_ID?></label>
                                    </div>

                                    <div class="col-1">
                                        <label class="col-md-12" for="example-text">User Name : </label>
                                    </div>
                                    <div class="col-3">
                                        <label style="color: #ff9800; "><?php echo $USER_NAME?></label>
                                    </div>
                                </div>
                                </br>
                                <?php if ($success_msg) {?>
                                    <div class="alert alert-success">
                                        <strong><?=$success_msg;?></strong>
                                    </div>
                                <?php } ?>
                                <form class="form-material" action="" method="post">
                                    <input type="hidden" name="FORM_TYPE" value="change_password_form">
                                    <div class="row" id="change_password_div" style="padding: 20px 20px 0px 20px; display: <?=($err_msg)?'':'none'?>; margin-top: 10px;">
                                        <?php if ($err_msg) {?>
                                            <div class="alert alert-danger">
                                                <strong><?=$err_msg;?></strong>
                                            </div>
                                        <?php } ?>
                                        <div class="col-3">
                                            <div class="form-group">
                                                <label class="form-label">Old Password</label>
                                                <input type="password" name="OLD_PASSWORD" class="form-control" required>
                                            </div>
                                        </div>
                                        <div class="col-3">
                                            <div class="form-group">
                                                <label class="form-label">New Password</label>
                                                <input type="password" name="NEW_PASSWORD" class="form-control" required>
                                            </div>
                                        </div>
                                        <div class="col-3">
                                            <div class="form-group">
                                                <label class="form-label">Confirm New Password</label>
                                                <input type="password" name="CONFIRM_NEW_PASSWORD" class="form-control" required>
                                            </div>
                                        </div>
                                        <div class="col-3">
                                            <div class="form-group" style="padding-top: 30px;">
                                                <button type="submit" class="btn btn-info waves-effect waves-light text-white">Change</button>
                                            </div>
                                        </div>
                                    </div>
                                </form>

                                <form class="form-material form-horizontal" action="" method="post" enctype="multipart/form-data" style="margin-top: 20px;">
                                    <input type="hidden" name="FORM_TYPE" value="profile_form">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label">Gender</label>
                                                <select class="form-control" id="GENDER" name="GENDER">
                                                    <option>Select Gender</option>
                                                    <option value="Male" <?php if($GENDER == "Male") echo 'selected = "selected"';?>>Male</option>
                                                    <option value="Female" <?php if($GENDER == "Female") echo 'selected = "selected"';?>>Female</option>
                                                    <option value="Other" <?php if($GENDER == "Other") echo 'selected = "selected"';?>>Other</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label">Date of Birth</label>
                                                <input type="text" class="form-control datepicker-past" id="DOB" name="DOB" value="<?=($DOB)?date('m/d/Y', strtotime($DOB)):''?>">
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
                                                <label class="col-md-12" for="example-text">Country<span class="text-danger">*</span></label>
                                                <div class="col-md-12">
                                                    <select class="form-control" name="PK_COUNTRY" id="PK_COUNTRY" onChange="fetch_state(this.value)">
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
                                                    <input type="text" id="CITY" name="CITY" class="form-control" placeholder="Enter your city" value="<?php echo $CITY?>">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-group">
                                                <label class="col-md-12" for="example-text">Postal / Zip Code</span>
                                                </label>
                                                <div class="col-md-12">
                                                    <input type="text" id="ZIP" name="ZIP" class="form-control" placeholder="Enter Postal / Zip Code" value="<?php echo $ZIP?>">
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
                                                    <input type="file" name="USER_IMAGE" id="USER_IMAGE" class="form-control" onchange="previewFile(this)"> </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <?php if($USER_IMAGE!=''){?>
                                            <div style="width: 120px;height: 120px;margin-top: 25px;">
                                            <a class="fancybox" href="<?php echo $USER_IMAGE;?>" data-fancybox-group="gallery">
                                                <img id="profile-img" src="<?php echo $USER_IMAGE;?>" style="width:120px; height:120px" /></a>
                                            </div><?php } ?>
                                    </div>

                                    <div class="form-group">
                                        <label class="col-md-12">Remarks</label>
                                        <div class="col-md-12">
                                            <textarea class="form-control" rows="3" id="NOTES" name="NOTES"><?php echo $NOTES?></textarea>
                                        </div>
                                    </div>

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
    </script>
</body>
</html>