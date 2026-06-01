<?php
require_once('../../global/config.php');
require_once("../../global/stripe-php-master/init.php");
global $db;
global $db_account;

$PK_ACCOUNT_MASTER = $_SESSION['PK_ACCOUNT_MASTER'];

$account_data = $db->Execute("SELECT * FROM `DOA_ACCOUNT_MASTER` WHERE `PK_ACCOUNT_MASTER` = ".$PK_ACCOUNT_MASTER);

$PAYMENT_GATEWAY = $account_data->fields['PAYMENT_GATEWAY_TYPE'];
$SECRET_KEY = $account_data->fields['SECRET_KEY'];
$PUBLISHABLE_KEY = $account_data->fields['PUBLISHABLE_KEY'];
?>
<div class="modal-dialog">
    <form class="form-material form-horizontal" id="creditCardForm" action="" method="post" enctype="multipart/form-data">
        <input type="hidden" name="FUNCTION_NAME" value="saveCreditCard">
        <div class="p-20" id="credit_card_div">
            <div class="row">
                <div class="col-6">
                    <div id="card-element"></div>
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">Save</button>
        <button type="button" class="btn btn-inverse waves-effect waves-light" onclick="window.location.href='business_profile.php'">Cancel</button>
    </form>
</div>

<script src="https://js.stripe.com/v3/"></script>
<script type="text/javascript">
    function stripePaymentFunction() {
        let stripe = Stripe('<?=$PUBLISHABLE_KEY?>');
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