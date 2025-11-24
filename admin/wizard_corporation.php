<?php

use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

require_once('../global/config.php');

if (empty($_GET['id']))
    $title = "Add Corporation";
else
    $title = "Edit Corporation";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

$SA_PAYMENT_GATEWAY_TYPE = '';
$SA_GATEWAY_MODE = '';
$SA_SECRET_KEY = '';
$SA_PUBLISHABLE_KEY = '';
$SA_ACCESS_TOKEN = '';
$SA_SQUARE_APP_ID = '';
$SA_SQUARE_LOCATION_ID = '';

$payment_gateway_setting = $db->Execute("SELECT * FROM `DOA_PAYMENT_GATEWAY_SETTINGS`");
if ($payment_gateway_setting->RecordCount() > 0) {
    $SA_PAYMENT_GATEWAY_TYPE = $payment_gateway_setting->fields['PAYMENT_GATEWAY_TYPE'];
    $SA_GATEWAY_MODE = $payment_gateway_setting->fields['GATEWAY_MODE'];
    $SA_SECRET_KEY = $payment_gateway_setting->fields['SECRET_KEY'];
    $SA_PUBLISHABLE_KEY = $payment_gateway_setting->fields['PUBLISHABLE_KEY'];
    $SA_ACCESS_TOKEN = $payment_gateway_setting->fields['ACCESS_TOKEN'];
    $SA_SQUARE_APP_ID = $payment_gateway_setting->fields['APP_ID'];
    $SA_SQUARE_LOCATION_ID = $payment_gateway_setting->fields['LOCATION_ID'];
}

if (empty($_GET['id'])) {
    $CORPORATION_NAME       = '';
    $PK_TIMEZONE            = '';
    $PK_CURRENCY            = '';
    $USERNAME_PREFIX        = '';
    $TIME_SLOT_INTERVAL     = '';
    $SERVICE_PROVIDER_TITLE = '';
    $OPERATION_TAB_TITLE    = '';
    $ENROLLMENT_ID_CHAR     = '';
    $ENROLLMENT_ID_NUM      = '';
    $MISCELLANEOUS_ID_CHAR  = '';
    $MISCELLANEOUS_ID_NUM   = '';
    $APPOINTMENT_REMINDER   = '';
    $HOUR                   = '';
    $FOCUSBIZ_API_KEY       = '';
    $SALES_TAX              = '';

    $PAYMENT_GATEWAY_TYPE   = '';
    $GATEWAY_MODE           = '';
    $SECRET_KEY             = '';
    $PUBLISHABLE_KEY        = '';
    $ACCESS_TOKEN           = '';
    $SQUARE_APP_ID          = '';
    $SQUARE_LOCATION_ID     = '';
    $LOGIN_ID               = '';
    $TRANSACTION_KEY        = '';
    $AUTHORIZE_CLIENT_KEY   = '';

    $FRANCHISE              = '';
    $AM_USER_NAME           = '';
    $AM_PASSWORD            = '';
    $AM_REFRESH_TOKEN       = '';

    $TEXTING_FEATURE_ENABLED    = '';
    $TWILIO_ACCOUNT_TYPE        = '';

    $ACTIVE                 = '';
} else {
    $res = $db->Execute("SELECT * FROM `DOA_CORPORATION` WHERE PK_CORPORATION = '$_GET[id]'");
    if ($res->RecordCount() == 0) {
        header("location:all_corporations.php");
        exit;
    }
    $CORPORATION_NAME       = $res->fields['CORPORATION_NAME'];
    $PK_TIMEZONE            = $res->fields['PK_TIMEZONE'];
    $PK_CURRENCY            = $res->fields['PK_CURRENCY'];
    $USERNAME_PREFIX        = $res->fields['USERNAME_PREFIX'];
    $TIME_SLOT_INTERVAL     = $res->fields['TIME_SLOT_INTERVAL'];
    $SERVICE_PROVIDER_TITLE = $res->fields['SERVICE_PROVIDER_TITLE'];
    $OPERATION_TAB_TITLE    = $res->fields['OPERATION_TAB_TITLE'];
    $ENROLLMENT_ID_CHAR     = $res->fields['ENROLLMENT_ID_CHAR'];
    $ENROLLMENT_ID_NUM      = $res->fields['ENROLLMENT_ID_NUM'];
    $MISCELLANEOUS_ID_CHAR  = $res->fields['MISCELLANEOUS_ID_CHAR'];
    $MISCELLANEOUS_ID_NUM   = $res->fields['MISCELLANEOUS_ID_NUM'];
    $APPOINTMENT_REMINDER   = $res->fields['APPOINTMENT_REMINDER'];
    $HOUR                   = $res->fields['HOUR'];
    $FOCUSBIZ_API_KEY       = $res->fields['FOCUSBIZ_API_KEY'];
    $SALES_TAX              = $res->fields['SALES_TAX'];

    $PAYMENT_GATEWAY_TYPE   = $res->fields['PAYMENT_GATEWAY_TYPE'];
    $GATEWAY_MODE           = $res->fields['GATEWAY_MODE'];
    $SECRET_KEY             = $res->fields['SECRET_KEY'];
    $PUBLISHABLE_KEY        = $res->fields['PUBLISHABLE_KEY'];
    $ACCESS_TOKEN           = $res->fields['ACCESS_TOKEN'];
    $SQUARE_APP_ID          = $res->fields['APP_ID'];
    $SQUARE_LOCATION_ID     = $res->fields['LOCATION_ID'];
    $LOGIN_ID               = $res->fields['LOGIN_ID'];
    $TRANSACTION_KEY        = $res->fields['TRANSACTION_KEY'];
    $AUTHORIZE_CLIENT_KEY   = $res->fields['AUTHORIZE_CLIENT_KEY'];

    $FRANCHISE              = $res->fields['FRANCHISE'];
    $AM_USER_NAME           = $res->fields['AM_USER_NAME'];
    $AM_PASSWORD            = $res->fields['AM_PASSWORD'];
    $AM_REFRESH_TOKEN       = $res->fields['AM_REFRESH_TOKEN'];

    $TEXTING_FEATURE_ENABLED    = $res->fields['TEXTING_FEATURE_ENABLED'];
    $TWILIO_ACCOUNT_TYPE        = $res->fields['TWILIO_ACCOUNT_TYPE'];

    $ACTIVE                 = $res->fields['ACTIVE'];
}

$user_data = $db->Execute("SELECT DOA_USERS.ABLE_TO_EDIT_PAYMENT_GATEWAY FROM DOA_USERS WHERE PK_USER = '$_SESSION[PK_USER]'");
$ABLE_TO_EDIT_PAYMENT_GATEWAY = $user_data->fields['ABLE_TO_EDIT_PAYMENT_GATEWAY'];

$help_title = '';
$help_description = '';
$help = $db->Execute("SELECT * FROM DOA_HELP_PAGE WHERE PAGE_LINK = 'corporation'");
if ($help->RecordCount() > 0) {
    $help_title = $help->fields['TITLE'];
    $help_description = $help->fields['DESCRIPTION'];
}
?>

<!DOCTYPE html>
<html lang="en">
<?php require_once('../includes/header.php'); ?>

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">
        <?php require_once('../includes/top_menu.php'); ?>
        <div class="page-wrapper">
            <?php require_once('../includes/top_menu_bar.php') ?>
            <div class="container-fluid body_content">
                <div class="row page-titles">
                    <div class="col-md-5 align-self-center">
                        <h4 class="text-themecolor"><?= $title ?></h4>
                    </div>
                    <div class="col-md-7 align-self-center text-end">
                        <div class="d-flex justify-content-end align-items-center">
                            <ol class="breadcrumb justify-content-end">
                                <li class="breadcrumb-item active"><?= $title ?></li>
                            </ol>

                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-8">
                        <div class="card">
                            <div class="card-body">
                                <form class="form-material form-horizontal" id="save_corporation_form" method="post" enctype="multipart/form-data">
                                    <div class="form-group">
                                        <label class="col-md-12" for="example-text">Corporation Name<span class="text-danger">*</span>
                                        </label>
                                        <div class="col-md-12">
                                            <input type="text" id="CORPORATION_NAME" name="CORPORATION_NAME" class="form-control" placeholder="Enter Corporation Name" value="<?php echo $CORPORATION_NAME ?>" required>
                                        </div>
                                    </div>
                                    <div class="row" style="margin-bottom: 15px;">
                                        <div class="col-6">
                                            <div class="form-group">
                                                <label class="col-md-12">Timezone<span class="text-danger">*</span></label>
                                                <div class="col-md-12">
                                                    <select name="PK_TIMEZONE" id="PK_TIMEZONE" class="form-control" required>
                                                        <option value="">Select</option>
                                                        <?php $res_type = $db->Execute("SELECT * FROM DOA_TIMEZONE WHERE ACTIVE = 1 ORDER BY NAME ASC");
                                                        while (!$res_type->EOF) { ?>
                                                            <option value="<?= $res_type->fields['PK_TIMEZONE'] ?>" <?php if ($res_type->fields['PK_TIMEZONE'] == $PK_TIMEZONE) echo 'selected="selected"'; ?>><?= $res_type->fields['NAME'] ?></option>
                                                        <?php $res_type->MoveNext();
                                                        } ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-group">
                                                <label class="col-md-12">Currency</label>
                                                <div class="col-md-12">
                                                    <select name="PK_CURRENCY" id="PK_CURRENCY" class="form-control required-entry">
                                                        <?php $res_type = $db->Execute("SELECT * FROM `DOA_CURRENCY` WHERE `ACTIVE` = 1");
                                                        while (!$res_type->EOF) { ?>
                                                            <option value="<?= $res_type->fields['PK_CURRENCY'] ?>" <?= ($res_type->fields['PK_CURRENCY'] == $PK_CURRENCY) ? 'selected' : '' ?>><?= $res_type->fields['CURRENCY_NAME'] . " (" . $res_type->fields['CURRENCY_SYMBOL'] . ")" ?></option>
                                                        <?php $res_type->MoveNext();
                                                        } ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row" style="margin-top: 30px;">
                                        <b class="btn btn-light" style="margin-bottom: 20px;">Add Credit Card</b>
                                        <div class="col-12">
                                            <input type="hidden" name="PAYMENT_METHOD_ID" id="PAYMENT_METHOD_ID" value="">
                                            <?php if ($SA_PAYMENT_GATEWAY_TYPE == 'Stripe') { ?>
                                                <input type="hidden" name="stripe_token" id="stripe_token" value="">
                                                <div class="row">
                                                    <div class="col-12">
                                                        <div class="form-group" id="card_div">

                                                        </div>
                                                    </div>
                                                </div>
                                                <div id="card_list_div">
                                                </div>
                                            <?php } elseif ($SA_PAYMENT_GATEWAY_TYPE == 'Square') { ?>
                                                <input type="hidden" name="square_token" id="square_token" value="">
                                                <div class="row">
                                                    <div class="col-12">
                                                        <div id="payment-card-container"></div>
                                                        <div id="payment-status-container"></div>
                                                    </div>
                                                </div>
                                                <div id="card_list_div">
                                                </div>
                                            <?php } ?>
                                        </div>
                                    </div>
                                    <div class="row" id="corporation_payment_status"></div>



                                    <?php if (!empty($_GET['id'])) { ?>
                                        <div class="row" style="margin-bottom: 15px;">
                                            <div class="col-6">
                                                <div class="col-md-2">
                                                    <label>Active</label>
                                                </div>
                                                <div class="col-md-4">
                                                    <label><input type="radio" name="ACTIVE" id="ACTIVE" value="1" <? if ($ACTIVE == 1) echo 'checked="checked"'; ?> />&nbsp;Yes</label>&nbsp;&nbsp;
                                                    <label><input type="radio" name="ACTIVE" id="ACTIVE" value="0" <? if ($ACTIVE == 0) echo 'checked="checked"'; ?> />&nbsp;No</label>
                                                </div>
                                            </div>
                                        </div>
                                    <? } ?>
                                    <button type="submit" id="corporation-pay-button" class="btn btn-info waves-effect waves-light m-r-10 text-white">Submit</button>
                                    <button type="button" class="btn btn-inverse waves-effect waves-light" onclick="window.location.href='all_corporations.php'">Cancel</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-4">
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <h4 class="col-md-12" STYLE="text-align: center">
                                        <?= $help_title ?>
                                    </h4>
                                    <div class="col-md-12">
                                        <text class="required-entry rich" id="DESCRIPTION"><?= $help_description ?></text>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php require_once('../includes/footer.php'); ?>
</body>

</html>

<script>
    $(document).ready(function() {
        getSavedCreditCardList();
    });

    function showHourBox(radio) {
        if (radio.value == 1) {
            document.getElementById("yes").style.display = "block";
        } else {
            document.getElementById("yes").style.display = "none";
        }
    }

    function showTwilioAccountSetting(param) {
        if ($(param).val() === '1') {
            $('#twilio_account_type').slideDown();
        } else {
            $('#twilio_account_type').slideUp();
            $('#TWILIO_ACCOUNT_TYPE_0').prop('checked', true);
            $('#twilio_setting_div').slideUp();
        }
    }

    function showTwilioSetting(param) {
        if ($(param).val() === '1') {
            $('#twilio_setting_div').slideDown();
        } else {
            $('#twilio_setting_div').slideUp();
        }
    }

    function showPaymentGateway(radio) {
        $('.payment_gateway').slideUp();
        if (radio.value == 'Stripe') {
            document.getElementById('stripe').style.display = 'block';
        } else if (radio.value == 'Square') {
            document.getElementById('square').style.display = 'block';
        } else if (radio.value == 'Authorized.net') {
            document.getElementById('authorized').style.display = 'block';
        } else if (radio.value == 'Clover') {
            document.getElementById('Clover').style.display = 'block';
        }
    }

    function getSavedCreditCardList() {
        let payment_gateway_type = '<?= $SA_PAYMENT_GATEWAY_TYPE ?>';
        if (payment_gateway_type == 'Square') {
            squarePaymentFunction();
        } else if (payment_gateway_type == 'Stripe') {
            stripePaymentFunction();
        }
    }
</script>




<?php if ($SA_PAYMENT_GATEWAY_TYPE == 'Stripe') { ?>
    <script src="https://js.stripe.com/v3/"></script>
    <script type="text/javascript">
        var stripe = Stripe('<?= $SA_PUBLISHABLE_KEY ?>');
        var elements = stripe.elements();

        var style = {
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
        var stripe_card = elements.create('card', {
            style: style
        });
        var pay_type = '';

        function stripePaymentFunction() {
            if (($('#card-element')).length > 0) {
                stripe_card.mount('#card-element');
            }
            // Handle real-time validation errors from the card Element.
            stripe_card.addEventListener('change', function(event) {
                var displayError = document.getElementById('card-errors');
                if (event.error) {
                    displayError.textContent = event.error.message;
                } else {
                    displayError.textContent = '';
                    addStripeTokenOnForm();
                }
            });
        }

        function addStripeTokenOnForm() {
            //event.preventDefault();
            stripe.createToken(stripe_card).then(function(result) {
                if (result.error) {
                    // Inform the user if there was an error.
                    let errorElement = document.getElementById('card-errors');
                    errorElement.textContent = result.error.message;
                } else {
                    // Send the token to your server.
                    $('#stripe_token').val(result.token.id);
                }
            });
        }
    </script>
<?php } ?>

<?php if ($SA_PAYMENT_GATEWAY_TYPE == 'Square') {
    if ($SA_GATEWAY_MODE == 'live')
        $SQ_URL = "https://connect.squareup.com";
    else
        $SQ_URL = "https://connect.squareupsandbox.com";

    if ($SA_GATEWAY_MODE == 'live')
        $URL = "https://web.squarecdn.com/v1/square.js";
    else
        $URL = "https://sandbox.web.squarecdn.com/v1/square.js";
?>
    <script src="<?= $URL ?>"></script>
    <script type="text/javascript">
        let square_card;

        async function squarePaymentFunction() {
            let square_appId = '<?= $SA_SQUARE_APP_ID ?>';
            let square_locationId = '<?= $SA_SQUARE_LOCATION_ID ?>';
            const payments = Square.payments(square_appId, square_locationId);
            square_card = await payments.card();
            $('#payment-card-container').text('');
            await square_card.attach('#payment-card-container');
        }

        async function addSquareTokenOnForm() {
            const statusContainer = document.getElementById('payment-status-container');

            try {
                // Tokenize the card details
                const result = await square_card.tokenize();
                if (result.status === 'OK') {
                    // Add the token to the hidden input field
                    $('#square_token').val(result.token);
                } else {
                    // Handle tokenization errors
                    let errorMessage = `Tokenization failed with status: ${result.status}`;
                    if (result.errors) {
                        errorMessage += ` and errors: ${JSON.stringify(result.errors)}`;
                    }
                    throw new Error(errorMessage);
                }
            } catch (e) {
                console.error(e);
                statusContainer.innerHTML = `<p class="alert alert-danger">Payment Failed: ${e.message}</p>`;
            }
        }
    </script>
<?php } ?>


<script>
    function sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    $(document).on('submit', '#save_corporation_form', function(event) {
        $('#corporation-pay-button').prop('disabled', true);
        $('#corporation-pay-button').html(`<span class="spinner-border spinner-border-sm"></span> Processing...`);
        event.preventDefault();
        let PAYMENT_GATEWAY = '<?= $SA_PAYMENT_GATEWAY_TYPE ?>';
        if (PAYMENT_GATEWAY == 'Square') {
            let PAYMENT_METHOD_ID = $('#PAYMENT_METHOD_ID').val();
            if (PAYMENT_METHOD_ID == '') {
                addSquareTokenOnForm();
                sleep(3000).then(() => {
                    submitCreditCardForm();
                });
            } else {
                submitCreditCardForm();
            }
        } else {
            submitCreditCardForm();
        }
    });

    function submitCreditCardForm() {
        let form_data = $('#save_corporation_form').serialize();
        $.ajax({
            url: "includes/save_wizard_corporation.php",
            type: 'POST',
            data: form_data,
            dataType: 'json',
            success: function(data) {
                if (data.STATUS == false) {
                    $('#corporation_payment_status').html(`<p class="alert alert-danger">${data.MESSAGE}</p>`);
                    $('#corporation-pay-button').prop('disabled', false);
                    $('#corporation-pay-button').html(`Process`);
                } else {
                    $('#corporation_payment_status').html(`<p class="alert alert-success">Credit Card Successfully Saved.</p>`);

                    setTimeout(function() {
                        window.location.href = 'wizard_location.php';
                    }, 3000);
                }
            }
        });
    }
</script>