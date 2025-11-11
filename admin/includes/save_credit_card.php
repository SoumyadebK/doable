<?php
require_once('../../global/config.php');
require_once("../../global/stripe-php/init.php");
global $db;
global $db_account;

$PK_USER = $_POST['PK_USER'];
$PK_USER_MASTER = $_POST['PK_USER_MASTER'];

$payment_gateway_data = getPaymentGatewayData();

$call_from = (isset($_POST['call_from'])) ? $_POST['call_from'] : '';

$PAYMENT_GATEWAY = $payment_gateway_data->fields['PAYMENT_GATEWAY_TYPE'];
$GATEWAY_MODE  = $payment_gateway_data->fields['GATEWAY_MODE'];

$SECRET_KEY = $payment_gateway_data->fields['SECRET_KEY'];
$PUBLISHABLE_KEY = $payment_gateway_data->fields['PUBLISHABLE_KEY'];

$SQUARE_ACCESS_TOKEN = $payment_gateway_data->fields['ACCESS_TOKEN'];
$SQUARE_APP_ID = $payment_gateway_data->fields['APP_ID'];
$SQUARE_LOCATION_ID = $payment_gateway_data->fields['LOCATION_ID'];

$AUTHORIZE_LOGIN_ID         = $payment_gateway_data->fields['LOGIN_ID']; //"4Y5pCy8Qr";
$AUTHORIZE_TRANSACTION_KEY     = $payment_gateway_data->fields['TRANSACTION_KEY']; //"4ke43FW8z3287HV5";
$AUTHORIZE_CLIENT_KEY         = $payment_gateway_data->fields['AUTHORIZE_CLIENT_KEY']; //"8ZkyJnT87uFztUz56B4PfgCe7yffEZA4TR5dv8ALjqk5u9mr6d8Nmt8KHyp8s9Ay";

$MERCHANT_ID            = $payment_gateway_data->fields['MERCHANT_ID'];
$API_KEY                = $payment_gateway_data->fields['API_KEY'];
$PUBLIC_API_KEY         = $payment_gateway_data->fields['PUBLIC_API_KEY'];

?>

<?php if ($PAYMENT_GATEWAY == 'Stripe') { ?>
    <?php if ($call_from != 'enrollment_auto_pay') { ?>
        <div class="row m-b-20">
            <div class="col-md-8">
                <a class="btn btn-info d-none d-lg-block text-white" href="javascript:" onclick="addCreditCard()" style="float: right; margin-bottom: 10px;"><i class="fa fa-plus-circle"></i> Add Credit Card</a>
            </div>
        </div>
    <?php } ?>

    <form class="form-material form-horizontal" id="save_credit_card_form" action="" method="post" enctype="multipart/form-data" style="display: none;">
        <input type="hidden" name="FUNCTION_NAME" value="saveCreditCard">
        <input type="hidden" name="stripe_token" id="stripe_token">
        <input type="hidden" name="PK_USER" id="PK_USER" value="<?= $PK_USER ?>">
        <input type="hidden" name="PK_USER_MASTER" id="PK_USER_MASTER" value="<?= $PK_USER_MASTER ?>">
        <div class="row">
            <div class="col-8">
                <div class="form-group" id="card_div">
                    <div id="save-card-element"></div>
                    <p id="save-card-errors" role="alert"></p>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-8" id="save_message">

            </div>
        </div>
        <div class="row">
            <div class="col-8">
                <button type="submit" id="save-card-btn" class="btn btn-info waves-effect waves-light m-r-10 text-white" style="float: right;">Save</button>
            </div>
        </div>
    </form>

    <script src="https://js.stripe.com/v3/"></script>
    <script type="text/javascript">
        var stripe_save_card = Stripe('<?= $PUBLISHABLE_KEY ?>');
        var save_card_elements = stripe_save_card.elements();

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
        var save_stripe_card = save_card_elements.create('card', {
            style: style
        });

        function addCreditCard() {
            $('#save_credit_card_form').slideDown();
            // Add an instance of the card Element into the `save-card-element` <div>.
            if (($('#save-card-element')).length > 0) {
                save_stripe_card.mount('#save-card-element');
            }
            // Handle real-time validation errors from the card Element.
            save_stripe_card.addEventListener('change', function(event) {
                var displayError = document.getElementById('save-card-errors');
                if (event.error) {
                    displayError.textContent = event.error.message;
                } else {
                    displayError.textContent = '';
                    addStripeTokenOnSaveCardForm();
                }
            });
            // Handle form submission.
            /*let form = document.getElementById(type+'_payment_form');
            form.addEventListener('submit', listener);*/
        }

        function addStripeTokenOnSaveCardForm() {
            //event.preventDefault();
            stripe_save_card.createToken(save_stripe_card).then(function(result) {
                if (result.error) {
                    // Inform the user if there was an error.
                    let errorElement = document.getElementById('save-card-errors');
                    errorElement.textContent = result.error.message;
                } else {
                    // Send the token to your server.
                    $('#stripe_token').val(result.token.id);
                    //stripeTokenHandler(result.token);
                }
            });
        }
    </script>
<?php } elseif ($PAYMENT_GATEWAY == 'Authorized.net') { ?>
    <?php if ($call_from != 'enrollment_auto_pay') { ?>
        <div class="row m-b-20">
            <div class="col-md-8">
                <a class="btn btn-info d-none d-lg-block text-white" href="javascript:" onclick="addCreditCard()" style="float: right; margin-bottom: 10px;"><i class="fa fa-plus-circle"></i> Add Credit Card</a>
            </div>
        </div>
    <?php } ?>
    <form class="form-material form-horizontal" id="save_credit_card_form" action="" method="post" enctype="multipart/form-data" style="display: none;">
        <input type="hidden" name="FUNCTION_NAME" value="saveCreditCard">
        <input type="hidden" name="PK_USER" id="PK_USER" value="<?= $PK_USER ?>">
        <input type="hidden" name="PK_USER_MASTER" id="PK_USER_MASTER" value="<?= $PK_USER_MASTER ?>">
        <div class="payment_type_div">
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">Card Number</label>
                        <div class="col-md-12">
                            <input type="text" name="SAVE_CARD_NUMBER" id="SAVE_CARD_NUMBER" placeholder="Card Number" class="form-control format-card">
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-2">
                    <div class="form-group">
                        <label class="form-label">Expiration Month</label>
                        <div class="col-md-12">
                            <select name="SAVE_EXPIRATION_MONTH" id="SAVE_EXPIRATION_MONTH" class="form-control">
                                <?php
                                for ($i = 1; $i <= 12; $i++) { ?>
                                    <option value="<?= $i ?>"><?= $i ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-2">
                    <div class="form-group">
                        <label class="form-label">Expiration Year</label>
                        <div class="col-md-12">
                            <select name="SAVE_EXPIRATION_YEAR" id="SAVE_EXPIRATION_YEAR" class="form-control">
                                <?php
                                $year = (int)date('Y');
                                for ($i = $year; $i <= $year + 25; $i++) { ?>
                                    <option value="<?= $i ?>"><?= $i ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-2">
                    <div class="form-group">
                        <label class="form-label">Security Code</label>
                        <div class="col-md-12">
                            <input type="text" name="SAVE_SECURITY_CODE" id="SAVE_SECURITY_CODE" class="form-control">
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-8" id="save_message">

                </div>
            </div>
            <div class="row">
                <div class="col-8">
                    <button type="submit" id="save-card-btn" class="btn btn-info waves-effect waves-light m-r-10 text-white" style="float: right;">Save</button>
                </div>
            </div>
        </div>
    </form>

    <script>
        function addCreditCard() {
            $('#save_credit_card_form').slideDown();
            $(".format-card").inputmask({
                mask: "9999 9999 9999 9999",
                placeholder: ""
            });
        }
    </script>
<?php } ?>


<script>
    $(document).on('submit', '#save_credit_card_form', function(event) {
        $('#save-card-btn').prop('disabled', true);
        event.preventDefault();
        let call_from = '<?= $call_from ?>';
        let form_data = $('#save_credit_card_form').serialize();
        $.ajax({
            url: "includes/process_save_credit_card.php",
            type: 'POST',
            data: form_data,
            dataType: 'json',
            success: function(data) {
                if (data.STATUS) {
                    if (call_from == 'enrollment_auto_pay') {
                        getSavedCreditCardList();
                    } else {
                        $('#save_message').html(`<p class="alert alert-success">Credit Card Added, Page will refresh automatically.</p>`);
                        setTimeout(function() {
                            let PK_USER = $('#PK_USER').val();
                            let PK_USER_MASTER = $('#PK_USER_MASTER').val();
                            window.location.href = 'customer.php?id=' + PK_USER + '&master_id=' + PK_USER_MASTER + '&tab=credit_card';
                        }, 5000);
                    }
                } else {
                    $('#save-card-btn').prop('disabled', false);
                    $('#save_message').html(`<p class="alert alert-danger">` + data.MESSAGE + `</p>`);
                }
            }
        });
    });
</script>