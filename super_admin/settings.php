<?php
require_once('../global/config.php');
global $db;
$title = "Settings";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 1 ){
    header("location:../login.php");
    exit;
}

$err_msg = '';
$success_msg = '';
if(!empty($_POST)){
    $TEXT_DATA['SID'] = $_POST['SID'];
    $TEXT_DATA['TOKEN'] = $_POST['TOKEN'];
    $TEXT_DATA['FROM_NO'] = $_POST['PHONE_NO'];
    if ($_POST['PK_TEXT_SETTINGS'] == 0) {
        db_perform('DOA_PAYMENT_GATEWAY_SETTINGS', $TEXT_DATA, 'insert');
    } else {
        db_perform('DOA_TEXT_SETTINGS', $TEXT_DATA, 'update', " PK_TEXT_SETTINGS = 1 ");
    }

    $PAYMENT_GATEWAY_DATA['PAYMENT_GATEWAY_TYPE'] = $_POST['PAYMENT_GATEWAY_TYPE'];
    $PAYMENT_GATEWAY_DATA['SECRET_KEY'] = $_POST['SECRET_KEY'];
    $PAYMENT_GATEWAY_DATA['PUBLISHABLE_KEY'] = $_POST['PUBLISHABLE_KEY'];
    $PAYMENT_GATEWAY_DATA['ACCESS_TOKEN'] = $_POST['ACCESS_TOKEN'];
    $PAYMENT_GATEWAY_DATA['APP_ID'] = $_POST['APP_ID'];
    $PAYMENT_GATEWAY_DATA['LOCATION_ID'] = $_POST['LOCATION_ID'];
    $PAYMENT_GATEWAY_DATA['LOGIN_ID'] = $_POST['LOGIN_ID'];
    $PAYMENT_GATEWAY_DATA['TRANSACTION_KEY'] = $_POST['TRANSACTION_KEY'];
    $PAYMENT_GATEWAY_DATA['AUTHORIZE_CLIENT_KEY'] = $_POST['AUTHORIZE_CLIENT_KEY'];
    if ($_POST['PK_PAYMENT_GATEWAY_SETTINGS'] == 0) {
        db_perform('DOA_PAYMENT_GATEWAY_SETTINGS', $PAYMENT_GATEWAY_DATA, 'insert');
    } else {
        db_perform('DOA_PAYMENT_GATEWAY_SETTINGS', $PAYMENT_GATEWAY_DATA, 'update', " PK_PAYMENT_GATEWAY_SETTINGS = 1 ");
    }

    $OTHER_SETTING_DATA['PK_OTHER_SETTING'] = $_POST['PK_OTHER_SETTING'];
    $OTHER_SETTING_DATA['PAYMENT_REMINDER_BEFORE_DAYS'] = $_POST['PAYMENT_REMINDER_BEFORE_DAYS'];
    $OTHER_SETTING_DATA['PAYMENT_FAILED_REMINDER_AFTER_DAYS'] = $_POST['PAYMENT_FAILED_REMINDER_AFTER_DAYS'];
    if ($_POST['PK_OTHER_SETTING'] == 0) {
        db_perform('DOA_OTHER_SETTING', $OTHER_SETTING_DATA, 'insert');
    } else {
        db_perform('DOA_OTHER_SETTING', $OTHER_SETTING_DATA, 'update', " PK_OTHER_SETTING = 1 ");
    }
}

$PK_TEXT_SETTINGS = 0;
$SID = '';
$TOKEN = '';
$PHONE_NO = '';
$text = $db->Execute( "SELECT * FROM `DOA_TEXT_SETTINGS`");
if ($text->RecordCount() > 0) {
    $PK_TEXT_SETTINGS = $text->fields['PK_TEXT_SETTINGS'];
    $SID = $text->fields['SID'];
    $TOKEN = $text->fields['TOKEN'];
    $PHONE_NO = $text->fields['FROM_NO'];
}

$PK_PAYMENT_GATEWAY_SETTINGS = 0;
$PAYMENT_GATEWAY_TYPE = '';
$SECRET_KEY = '';
$PUBLISHABLE_KEY = '';
$ACCESS_TOKEN = '';
$SQUARE_APP_ID ='';
$SQUARE_LOCATION_ID = '';
$LOGIN_ID = '';
$TRANSACTION_KEY = '';
$AUTHORIZE_CLIENT_KEY = '';
$payment_gateway_setting = $db->Execute( "SELECT * FROM `DOA_PAYMENT_GATEWAY_SETTINGS`");
if ($payment_gateway_setting->RecordCount() > 0) {
    $PK_PAYMENT_GATEWAY_SETTINGS = $payment_gateway_setting->fields['PK_PAYMENT_GATEWAY_SETTINGS'];
    $PAYMENT_GATEWAY_TYPE = $payment_gateway_setting->fields['PAYMENT_GATEWAY_TYPE'];
    $SECRET_KEY = $payment_gateway_setting->fields['SECRET_KEY'];
    $PUBLISHABLE_KEY = $payment_gateway_setting->fields['PUBLISHABLE_KEY'];
    $ACCESS_TOKEN = $payment_gateway_setting->fields['ACCESS_TOKEN'];
    $SQUARE_APP_ID = $payment_gateway_setting->fields['APP_ID'];
    $SQUARE_LOCATION_ID = $payment_gateway_setting->fields['LOCATION_ID'];
    $LOGIN_ID = $payment_gateway_setting->fields['LOGIN_ID'];
    $TRANSACTION_KEY = $payment_gateway_setting->fields['TRANSACTION_KEY'];
    $AUTHORIZE_CLIENT_KEY = $payment_gateway_setting->fields['AUTHORIZE_CLIENT_KEY'];
}

$PK_OTHER_SETTING = 0;
$PAYMENT_REMINDER_BEFORE_DAYS = '';
$PAYMENT_FAILED_REMINDER_AFTER_DAYS = '';
$other_setting = $db->Execute( "SELECT * FROM `DOA_OTHER_SETTING`");
if ($other_setting->RecordCount() > 0) {
    $PK_OTHER_SETTING = $other_setting->fields['PK_OTHER_SETTING'];
    $PAYMENT_REMINDER_BEFORE_DAYS = $other_setting->fields['PAYMENT_REMINDER_BEFORE_DAYS'];
    $PAYMENT_FAILED_REMINDER_AFTER_DAYS = $other_setting->fields['PAYMENT_FAILED_REMINDER_AFTER_DAYS'];
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
                    <h4 class="text-themecolor"><?=$title?></h4>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <form class="form-material" action="" method="post">
                                <div class="row">
                                    <b class="btn btn-light" style="margin-bottom: 20px;">Twilio Setting</b>
                                    <input type="hidden" name="PK_TEXT_SETTINGS" value="<?=$PK_TEXT_SETTINGS?>">
                                    <div class="col-4">
                                        <div class="form-group">
                                            <label class="col-md-12" for="example-text">SID</label>
                                            <div class="col-md-12">
                                                <input type="text" id="SID" name="SID" class="form-control" placeholder="Enter SID" value="<?php echo $SID?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="form-group">
                                            <label class="col-md-12" for="example-text">Token</label>
                                            <div class="col-md-12">
                                                <input type="text" id="TOKEN" name="TOKEN" class="form-control" placeholder="Enter TOKEN" value="<?php echo $TOKEN?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="form-group">
                                            <label class="col-md-12" for="example-text">Phone No.</label>
                                            <div class="col-md-12">
                                                <input type="text" id="PHONE_NO" name="PHONE_NO" class="form-control" placeholder="Enter Phone No." value="<?php echo $PHONE_NO?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row" style="margin-top: 50px;">
                                    <b class="btn btn-light" style="margin-bottom: 20px;">Payment Gateway Setting</b>
                                    <input type="hidden" name="PK_PAYMENT_GATEWAY_SETTINGS" value="<?=$PK_PAYMENT_GATEWAY_SETTINGS?>">
                                    <div class="col-6">
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
                                </div>

                                <div class="row" style="margin-top: 50px;">
                                    <b class="btn btn-light" style="margin-bottom: 20px;">Subscription & Payment Setting</b>
                                    <input type="hidden" name="PK_OTHER_SETTING" value="<?=$PK_OTHER_SETTING?>">
                                    <div class="col-4">
                                        <div class="form-group">
                                            <label class="col-md-12" for="example-text">Payment reminder send before days</label>
                                            <div class="col-md-12">
                                                <input type="text" id="PAYMENT_REMINDER_BEFORE_DAYS" name="PAYMENT_REMINDER_BEFORE_DAYS" class="form-control" placeholder="Payment reminder send before days" value="<?=$PAYMENT_REMINDER_BEFORE_DAYS?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="form-group">
                                            <label class="col-md-12" for="example-text">Payment failed reminder after days</label>
                                            <div class="col-md-12">
                                                <input type="text" id="PAYMENT_FAILED_REMINDER_AFTER_DAYS" name="PAYMENT_FAILED_REMINDER_AFTER_DAYS" class="form-control" placeholder="Payment failed reminder after days" value="<?=$PAYMENT_FAILED_REMINDER_AFTER_DAYS?>">
                                            </div>
                                        </div>
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