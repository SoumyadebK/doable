<?php
require_once('../global/config.php');
$title = "My Profile";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 4){
    header("location:../login.php");
    exit;
}
$err_msg = '';
$success_msg = '';
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
        $USER_DATA['PHONE'] = $_POST['PHONE'];
        if ($_FILES['USER_IMAGE']['name'] != '') {
            $USER_DATA = [];
            $extn = explode(".", $_FILES['USER_IMAGE']['name']);
            $iindex = count($extn) - 1;
            $rand_string = time() . "-" . rand(100000, 999999);
            $file11 = 'user_image_' . $_SESSION['PK_USER'] . $rand_string . "." . $extn[$iindex];
            $extension = strtolower($extn[$iindex]);

            if ($extension == "gif" || $extension == "jpeg" || $extension == "pjpeg" || $extension == "png" || $extension == "jpg") {
                $image_path = '../uploads/user_image/' . $file11;
                move_uploaded_file($_FILES['USER_IMAGE']['tmp_name'], $image_path);
                $USER_DATA['USER_IMAGE'] = $image_path;
            }
        }
        db_perform('DOA_USERS', $USER_DATA, 'update', " PK_USER =  '$_SESSION[PK_USER]'");

        $USER_DATA['GENDER'] = $_POST['GENDER'];
        $USER_DATA['DOB'] = date('Y-m-d', strtotime($_POST['DOB']));
        $USER_DATA['ADDRESS'] = $_POST['ADDRESS'];
        $USER_DATA['ADDRESS_1'] = $_POST['ADDRESS_1'];
        $USER_DATA['PK_COUNTRY'] = $_POST['PK_COUNTRY'];
        $USER_DATA['PK_STATES'] = $_POST['PK_STATES'];
        $USER_DATA['CITY'] = $_POST['CITY'];
        $USER_DATA['ZIP'] = $_POST['ZIP'];

        /*$USER_PROFILE_DATA['FAX'] = $_POST['FAX'];
        $USER_PROFILE_DATA['WEBSITE'] = $_POST['WEBSITE'];*/
        $USER_DATA['NOTES'] = $_POST['NOTES'];

        $res = $db->Execute("SELECT `PK_USER` FROM `DOA_USERS` WHERE PK_USER = '$_SESSION[PK_USER]'");

        if ($res->RecordCount() == 0) {
            $USER_DATA['PK_USER'] = $_SESSION['PK_USER'];
            $USER_DATA['ACTIVE'] = 1;
            $USER_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
            $USER_DATA['CREATED_ON'] = date("Y-m-d H:i");
            db_perform('DOA_USER', $USER_DATA, 'insert');
        } else {
            $USER_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
            $USER_DATA['EDITED_ON'] = date("Y-m-d H:i");
            db_perform('DOA_USER', $USER_DATA, 'update', " PK_USER =  '$_SESSION[PK_USER]'");
        }
    }
}

$res = $db->Execute("SELECT * FROM DOA_USERS WHERE PK_USER = '$_SESSION[PK_USER]'");

if($res->RecordCount() == 0){
    header("location:../login.php");
    exit;
}

$USER_ID = $res->fields['USER_ID'];
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
/*$FAX = $res->fields['FAX'];
$WEBSITE = $res->fields['WEBSITE'];*/
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
                                <ul class="nav nav-tabs" role="tablist">
                                    <li> <a class="nav-link active" data-bs-toggle="tab" href="#profile" role="tab" ><span class="hidden-sm-up"><i class="ti-id-badge"></i></span> <span class="hidden-xs-down">Profile</span></a> </li>
                                </ul>

                                <div class="tab-content tabcontent-border">
                                    <div class="tab-pane active" id="profile" role="tabpanel">
                                        <div class="p-20">
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
                                                <div class="col-3" style="width: 22%;">
                                                    <label style="color: #ff9800; ">Customer</label>
                                                </div>

                                                <div class="col-1">
                                                    <label class="col-md-12" for="example-text">Email Id : </label>
                                                </div>
                                                <div class="col-3">
                                                    <label style="color: #ff9800; "><?php echo $EMAIL_ID?></label>
                                                </div>

                                                <div class="col-1" style="width: 11%;">
                                                    <label class="col-md-12" for="example-text">User Name : </label>
                                                </div>
                                                <div class="col-3">
                                                    <label style="color: #ff9800; "><?php echo $USER_ID?></label>
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
                                                                <option value="1" <?php if($GENDER == "1") echo 'selected = "selected"';?>>Male</option>
                                                                <option value="2" <?php if($GENDER == "2") echo 'selected = "selected"';?>>Female</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label class="form-label">Date of Birth</label>
                                                            <input type="text" class="form-control datepicker-past"  id="DOB" name="DOB" value="<?php echo ($DOB)?date('m/d/Y', strtotime($DOB)):''?>">
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
                                                            <label class="col-md-12" for="example-text">Country<span class="text-danger">*</span>
                                                            </label>
                                                            <div class="col-md-12">
                                                                <div class="col-sm-12">
                                                                    <select class="form-control" required name="PK_COUNTRY" id="PK_COUNTRY" onChange="fetch_state(this.value)">
                                                                        <option>Select Country</option>
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
                                                                <input type="file" name="USER_IMAGE" id="USER_IMAGE" class="form-control" > </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <?php if($USER_IMAGE!=''){?><div style="width: 120px;height: 120px;margin-top: 25px;"><a class="fancybox" href="<?php echo $USER_IMAGE;?>" data-fancybox-group="gallery"><img src = "<?php echo $USER_IMAGE;?>" style="width:120px; height:120px" /></a></div><?php } ?>
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
    </script>
</body>
</html>