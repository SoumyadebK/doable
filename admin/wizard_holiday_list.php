<?php

use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;

$title = "Create Holiday List";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])){
    header("location:../login.php");
    exit;
}

$res = $db->Execute("SELECT * FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
if($res->RecordCount() == 0){
    header("location:login.php");
    exit;
}
$PK_BUSINESS_TYPE   = $res->fields['PK_BUSINESS_TYPE'];
$BUSINESS_ID   = $res->fields['BUSINESS_ID'];
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
$OPERATION_TAB_TITLE = $res->fields['OPERATION_TAB_TITLE'];
$PK_CURRENCY            = $res->fields['PK_CURRENCY'];
$ENROLLMENT_ID_CHAR     = $res->fields['ENROLLMENT_ID_CHAR'];
$ENROLLMENT_ID_NUM      = $res->fields['ENROLLMENT_ID_NUM'];
$MISCELLANEOUS_ID_CHAR = $res->fields['MISCELLANEOUS_ID_CHAR'];
$MISCELLANEOUS_ID_NUM = $res->fields['MISCELLANEOUS_ID_NUM'];
$PAYMENT_GATEWAY_TYPE   = $res->fields['PAYMENT_GATEWAY_TYPE'];
$SECRET_KEY             = $res->fields['SECRET_KEY'];
$PUBLISHABLE_KEY        = $res->fields['PUBLISHABLE_KEY'];
$ACCESS_TOKEN           = $res->fields['ACCESS_TOKEN'];
$SQUARE_APP_ID          = $res->fields['APP_ID'];
$SQUARE_LOCATION_ID     = $res->fields['LOCATION_ID'];
$LOGIN_ID               = $res->fields['LOGIN_ID'];
$TRANSACTION_KEY        = $res->fields['TRANSACTION_KEY'];
$AUTHORIZE_CLIENT_KEY   = $res->fields['AUTHORIZE_CLIENT_KEY'];
$APPOINTMENT_REMINDER = $res->fields['APPOINTMENT_REMINDER'];
$HOUR = $res->fields['HOUR'];
$USERNAME_PREFIX = $res->fields['USERNAME_PREFIX'];
$TIME_SLOT_INTERVAL = $res->fields['TIME_SLOT_INTERVAL'];

$AM_USER_NAME = $res->fields['AM_USER_NAME'];
$AM_PASSWORD = $res->fields['AM_PASSWORD'];
$AM_REFRESH_TOKEN = $res->fields['AM_REFRESH_TOKEN'];

$SMTP_HOST = '';
$SMTP_PORT = '';
$SMTP_USERNAME = '';
$SMTP_PASSWORD = '';
$email = $db_account->Execute("SELECT * FROM DOA_EMAIL_ACCOUNT WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
if ($email->RecordCount() > 0) {
    $SMTP_HOST = $email->fields['HOST'];
    $SMTP_PORT = $email->fields['PORT'];
    $SMTP_USERNAME = $email->fields['USER_NAME'];
    $SMTP_PASSWORD = $email->fields['PASSWORD'];
}

$user_data = $db->Execute("SELECT DOA_USERS.ABLE_TO_EDIT_PAYMENT_GATEWAY FROM DOA_USERS WHERE PK_USER = '$_SESSION[PK_USER]'");
$ABLE_TO_EDIT_PAYMENT_GATEWAY = $user_data->fields['ABLE_TO_EDIT_PAYMENT_GATEWAY'];

$payment_gateway_setting = $db->Execute( "SELECT * FROM `DOA_PAYMENT_GATEWAY_SETTINGS`");
$STRIPE_SECRET_KEY = $payment_gateway_setting->fields['SECRET_KEY'];
$STRIPE_PUBLISHABLE_KEY = $payment_gateway_setting->fields['PUBLISHABLE_KEY'];

require_once("../global/stripe-php-master/init.php");
$stripe = new StripeClient($STRIPE_SECRET_KEY);
$account_payment_info = $db->Execute("SELECT * FROM DOA_ACCOUNT_PAYMENT_INFO WHERE PK_LOCATION IS NULL AND PAYMENT_TYPE = 'Stripe' AND PK_ACCOUNT_MASTER = " . $_SESSION['PK_ACCOUNT_MASTER']);
if ($account_payment_info->RecordCount() > 0) {
    $customer_id = $account_payment_info->fields['ACCOUNT_PAYMENT_ID'];
    $stripe_customer = $stripe->customers->retrieve($customer_id);
    $card_id = $stripe_customer->default_source;

    $url = "https://api.stripe.com/v1/customers/".$customer_id."/cards/".$card_id;
    $AUTH = "Authorization: Bearer ".$STRIPE_SECRET_KEY;

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            $AUTH
        ),
    ));

    $response = curl_exec($curl);
    $card_details = json_decode($response, true);
    //pre_r($card_details);
}

if(!empty($_POST)){
    if ($_POST['FUNCTION_NAME'] == 'saveHolidayData') {
        unset($_POST['FUNCTION_NAME']);
        $db_account->Execute("DELETE FROM `DOA_HOLIDAY_LIST` WHERE `PK_ACCOUNT_MASTER` = '$_SESSION[PK_ACCOUNT_MASTER]'");
        for ($i=0; $i < count($_POST['HOLIDAY_DATE']); $i++) {
            $HOLIDAY_LIST_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
            $HOLIDAY_LIST_DATA['HOLIDAY_DATE'] = date('Y-m-d', strtotime($_POST['HOLIDAY_DATE'][$i]));
            $HOLIDAY_LIST_DATA['HOLIDAY_NAME'] = $_POST['HOLIDAY_NAME'][$i];
            db_perform_account('DOA_HOLIDAY_LIST', $HOLIDAY_LIST_DATA, 'insert');
        }
    }

    header("location:wizard_location.php");
}

$header_text = '';
$header_data = $db->Execute("SELECT * FROM `DOA_HEADER_TEXT` WHERE ACTIVE = 1 AND HEADER_TITLE = 'Business Profile page'");
if ($header_data->RecordCount() > 0) {
    $header_text = $header_data->fields['HEADER_TEXT'];
}
?>

<!DOCTYPE html>
<html lang="en">
<?php require_once('../includes/header.php');?>
<style>
    #advice-required-entry-ACCEPT_HANDLING{width: 150px;top: 20px;position: absolute;}
    .StripeElement {
        display: block;
        width: 100%;
        height: 34px;
        padding: 6px 12px;
        font-size: 14px;
        line-height: 1.42857143;
        color: #555;
        background-color: #fff;
        background-image: none;
        border: 1px solid #ccc;
        border-radius: 4px;
    }

    .StripeElement--focus {
        box-shadow: 0 1px 3px 0 #cfd7df;
    }

    .StripeElement--invalid {
        border-color: #fa755a;
    }

    .StripeElement--webkit-autofill {
        background-color: #fefde5 !important;
    }
</style>
<body class="skin-default-dark fixed-layout">
<?php require_once('../includes/loader.php');?>
<div id="main-wrapper">
    <?php require_once('../includes/top_menu.php');?>
    <div class="page-wrapper">
        <?php require_once('../includes/top_menu_bar.php') ?>
        <?php require_once('../includes/setup_menu.php') ?>
        <div class="container-fluid body_content m-0">
            <div class="row page-titles">
                <div class="col-md-5 align-self-center">
                    <h4 class="text-themecolor"><?=$title?></h4>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row" style="text-align: center;">
                                <h5 style="font-weight: bold;"><?=$header_text?></h5>
                            </div>
                            <!-- Nav tabs -->
                            <ul class="nav nav-tabs" role="tablist">
                                <li class="active"> <a class="nav-link active" data-bs-toggle="tab" id="holiday_list_link" href="#holiday_list" role="tab"><span class="hidden-sm-up"><i class="ti-calendar"></i></span> <span class="hidden-xs-down">Holiday List</span></a> </li>
                            </ul>

                            <!-- Tab panes -->
                            <div class="tab-content tabcontent-border">
                                <div class="tab-pane active" id="holiday_list" role="tabpanel">
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
                                            $holiday_list = $db_account->Execute("SELECT * FROM DOA_HOLIDAY_LIST WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
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
                                        <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">Next</button>
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
                }else {
                    if($(param).val() === 'Authorized.net'){
                        $('#authorized').slideDown();
                    }
                }

            }
        }

        function showHourBox(param) {
            $('.hour_box').slideUp();
            if ($(param).val() === '1') {
                $('#yes').slideDown();
            }
        }
    </script>
    <script>
        $('.time-picker').timepicker({
            timeFormat: 'HH:mm:ss',
            interval: 5,
            minTime: '00',
            maxTime: '00:60:00',
            //defaultTime: '11',
            startTime: '00:00:00',
            dynamic: false,
            dropdown: true,
            scrollbar: true
        });
    </script>

    <script src="https://js.stripe.com/v3/"></script>
    <script type="text/javascript">
        function stripePaymentFunction() {
            let stripe = Stripe('<?=$STRIPE_PUBLISHABLE_KEY?>');
            let elements = stripe.elements();

            let style = {
                base: {
                    height: '34px',
                    padding: '6px 12px',
                    fontSize: '14px',
                    lineHeight: '1.42857143',
                    color: '#555',
                    backgroundColor: '#fff',
                    border: '1px solid #ccc',
                    borderRadius: '4px',
                    '::placeholder': {
                        color: '#ddd'
                    }
                },
                invalid: {
                    color: '#fa755a',
                    iconColor: '#fa755a'
                }
            };

            // Create an instance of the card Element.
            let card = elements.create('card', {style: style});

            if (($('#card-element')).length > 0) {
                card.mount('#card-element');
            }

            // Handle real-time validation errors from the card Element.
            card.addEventListener('change', function (event) {
                let displayError = document.getElementById('card-errors');
                if (event.error) {
                    displayError.textContent = event.error.message;
                } else {
                    displayError.textContent = '';
                }
            });

            // Handle form submission.
            let form = document.getElementById('creditCardForm');
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                stripe.createToken(card).then(function (result) {
                    if (result.error) {
                        // Inform the user if there was an error.
                        let errorElement = document.getElementById('card-errors');
                        errorElement.textContent = result.error.message;
                    } else {
                        // Send the token to your server.
                        stripeTokenHandler(result.token);
                    }
                });
            });

            // Submit the form with the token ID.
            function stripeTokenHandler(token) {
                // Insert the token ID into the form, so it gets submitted to the server
                let form = document.getElementById('creditCardForm');
                let hiddenInput = document.createElement('input');
                hiddenInput.setAttribute('type', 'hidden');
                hiddenInput.setAttribute('name', 'token');
                hiddenInput.setAttribute('value', token.id);
                form.appendChild(hiddenInput);
                form.submit();
            }
        }
    </script>
    <script>
        $(document).on('submit', '#business_profile_form', function (event) {
            event.preventDefault();
            $('#holiday_list_link')[0].click();
        }
    </script>
</body>
</html>