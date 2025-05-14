<?php
require_once('../global/config.php');
if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 1 ){
    header("location:../login.php");
    exit;
}

if (empty($_GET['id']))
    $title = "Add Account User";
else
    $title = "Edit Account User";

if(!empty($_POST)){
    if (!empty($_POST['PASSWORD']))
        $USER_DATA['PASSWORD'] = password_hash($_POST['PASSWORD'], PASSWORD_DEFAULT);

    if(!empty($_GET['id'])){
        $USER_DATA['ACTIVE'] = $_POST['ACTIVE'];
        $USER_DATA['ABLE_TO_EDIT_PAYMENT_GATEWAY'] = isset($_POST['ABLE_TO_EDIT_PAYMENT_GATEWAY']) ? 1 : 0;
        $USER_DATA['INACTIVE_BY_ADMIN'] = ($_POST['ACTIVE'] == 0) ? 1 : 0;
        $USER_DATA['EDITED_BY']	= $_SESSION['PK_USER'];
        $USER_DATA['EDITED_ON'] = date("Y-m-d H:i");
        db_perform('DOA_USERS', $USER_DATA, 'update'," PK_USER =  '$_GET[id]'");
    }
    header("location:user_list.php?id=".$_GET['ac_id']);
}

if(empty($_GET['id'])){
    $ACTIVE = '';
}
else {
    $res = $db->Execute("SELECT DOA_USERS.FIRST_NAME, DOA_USERS.LAST_NAME, DOA_USERS.USER_NAME, DOA_USERS.ACTIVE, DOA_USERS.ABLE_TO_EDIT_PAYMENT_GATEWAY FROM DOA_USERS WHERE DOA_USERS.PK_USER = '$_GET[id]'");

    if($res->RecordCount() == 0){
        header("location:account.php?id=".$_GET['ac_id']);
        exit;
    }

    $FIRST_NAME = $res->fields['FIRST_NAME'];
    $LAST_NAME = $res->fields['LAST_NAME'];
    $USER_NAME = $res->fields['USER_NAME'];
    $ACTIVE = $res->fields['ACTIVE'];
    $ABLE_TO_EDIT_PAYMENT_GATEWAY = $res->fields['ABLE_TO_EDIT_PAYMENT_GATEWAY'];
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
                    <h4 class="text-themecolor"><?=$FIRST_NAME." ".$LAST_NAME?></h4>
                </div>
                <div class="col-md-7 align-self-center text-end">
                    <div class="d-flex justify-content-end align-items-center">
                        <ol class="breadcrumb justify-content-end">
                            <li class="breadcrumb-item active"><?=$FIRST_NAME." ".$LAST_NAME?></li>
                        </ol>

                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <form class="form-material form-horizontal" id="account_edit" action="" method="post" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="card">
                                            <div class="card-body">
                                                <h4><span><b>User ID : </b></span><?=$USER_NAME?></h4>
                                                <div class="p-20">
                                                    <h5><b>Change Password</b></h5>
                                                    <div class="row">
                                                        <div class="col-6">
                                                            <div class="form-group">
                                                                <label class="col-md-12">Password</label>
                                                                <div class="col-md-12">
                                                                    <input type="password" class="form-control p-0" placeholder="Password" aria-label="Password" aria-describedby="basic-addon3" name="PASSWORD" id="PASSWORD" onkeyup="isGood(this.value)">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-6">
                                                            <div class="form-group">
                                                                <label class="col-md-12">Confirm Password</label>
                                                                <div class="col-md-12">
                                                                    <input type="password" class="form-control p-0" placeholder="Confirm Password" aria-label="Password" aria-describedby="basic-addon3" name="CONFIRM_PASSWORD" id="CONFIRM_PASSWORD" onkeyup="isGood(this.value)">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <b id="password_error" style="color: red;"></b>
                                                        <div class="col-12">
                                                            <span style="color:orange;">Note  : Password Must contain at least one number and one uppercase and lowercase letter, and at least 8 or more characters</span>
                                                        </div>
                                                        <div class="col-2">
                                                            Password Strength:
                                                        </div>
                                                        <div class="col-3">
                                                            <small id="password-text"></small>
                                                        </div>
                                                    </div>

                                                    <?php if(!empty($_GET['id'])) { ?>
                                                        <div class="row" style="margin-bottom: 15px; margin-top: 25px;">
                                                            <div class="col-md-1">
                                                                <label class="form-label">Active : </label>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <label><input type="radio" name="ACTIVE" id="ACTIVE" value="1" <? if($ACTIVE == 1) echo 'checked="checked"'; ?> />&nbsp;Yes</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                                <label><input type="radio" name="ACTIVE" id="ACTIVE" value="0" <? if($ACTIVE == 0) echo 'checked="checked"'; ?> />&nbsp;No</label>
                                                            </div>
                                                        </div>
                                                    <? } ?>

                                                    <div class="row">
                                                        <div class="col-6">
                                                            <label class="col-md-12"><input type="checkbox" id="ABLE_TO_EDIT_PAYMENT_GATEWAY" name="ABLE_TO_EDIT_PAYMENT_GATEWAY" class="form-check-inline" <?=($ABLE_TO_EDIT_PAYMENT_GATEWAY == 1)?'checked':''?>> Able to edit payment gateway</label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">Submit</button>
                                                <button type="button" onclick="window.location.href='user_list.php?id=<?=$_GET['ac_id']?>'" class="btn btn-inverse waves-effect waves-light">Cancel</button>
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
        $(document).ready(function() {
            fetch_state(<?php  echo $PK_COUNTRY; ?>);

        });

        function fetch_state(PK_COUNTRY){

            jQuery(document).ready(function($) {

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


    </script>
    <script>
        function isGood(password) {
            //alert(password);
            let password_strength = document.getElementById("password-text");

            //TextBox left blank.
            if (password.length == 0) {
                password_strength.innerHTML = "";
                return;
            }

            //Regular Expressions.
            let regex = new Array();
            regex.push("[A-Z]"); //Uppercase Alphabet.
            regex.push("[a-z]"); //Lowercase Alphabet.
            regex.push("[0-9]"); //Digit.
            regex.push("[$@$!%*#?&]"); //Special Character.

            let passed = 0;

            //Validate for each Regular Expression.
            for (let i = 0; i < regex.length; i++) {
                if (new RegExp(regex[i]).test(password)) {
                    passed++;
                }
            }

            //Display status.
            let strength = "";
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
            let username = document.getElementById("User_Id").value;
            let lblError = document.getElementById("lblError");
            lblError.innerHTML = "";
            let expr = /^[a-zA-Z0-9_]{8,20}$/;
            if (!expr.test(username)) {
                lblError.innerHTML = "Only Alphabets, Numbers and Underscore and between 8 to 20 characters.";
            }
            else{
                lblError.innerHTML = "";
            }
        }

        $(document).on('submit', '#account_edit', function (event) {
            //event.preventDefault();
            let PASSWORD = $('#PASSWORD').val();
            let CONFIRM_PASSWORD = $('#CONFIRM_PASSWORD').val();
            if (PASSWORD === CONFIRM_PASSWORD) {
                return true;
            }else{
                $('#password_error').text('Password and Confirm Password not matched');
                return false;
            }
        });
    </script>




</body>
</html>