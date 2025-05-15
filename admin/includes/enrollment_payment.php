<div class="modal fade payment_modal" id="enrollment_payment_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="enrollment_payment_form">
            <div class="modal-content">
                <div class="modal-header">
                    <h4><b>Payment</b></h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="$('#enrollment_payment_modal').modal('hide');"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="sourceId" id="enrollment_sourceId">
                    <input type="hidden" name="token" id="token">
                    <input type="hidden" name="FUNCTION_NAME" value="confirmEnrollmentPayment">
                    <!--<input type="hidden" name="IS_ONE_TIME_PAY" id="IS_ONE_TIME_PAY" value="0">-->
                    <input type="hidden" name="PK_ENROLLMENT_MASTER" class="PK_ENROLLMENT_MASTER" value="<?=(empty($_GET['id']))?'':$_GET['id']?>">
                    <input type="hidden" name="PK_ENROLLMENT_BILLING" class="PK_ENROLLMENT_BILLING" value="<?=($PK_ENROLLMENT_BILLING) ?? 0?>">
                    <input type="hidden" name="PK_ENROLLMENT_LEDGER" class="PK_ENROLLMENT_LEDGER">
                    <input type="hidden" name="PAYMENT_GATEWAY" id="PAYMENT_GATEWAY" value="<?=$PAYMENT_GATEWAY?>">
                    <input type="hidden" name="PK_USER_MASTER" class="CUSTOMER_ID" id="PK_USER_MASTER" value="<?=$PK_USER_MASTER?>">
                    <input type="hidden" name="PAYMENT_METHOD_ID" id="PAYMENT_METHOD_ID">
                    <input type="hidden" name="BILLING_REF" id="PAYMENT_BILLING_REF">
                    <input type="hidden" name="BILLING_DATE" id="PAYMENT_BILLING_DATE">
                    <input type="hidden" name="header" value="<?=$header?>">

                    <div class="p-20">
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label">Total Amount</label>
                                    <div class="col-md-12">
                                        <input type="text" name="ACTUAL_AMOUNT" id="ACTUAL_AMOUNT" value="<?=($AMOUNT) ?? 0?>" class="form-control" readonly>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label">Remaining Amount</label>
                                    <div class="col-md-12">
                                        <input type="text" name="REMAINING_AMOUNT" id="REMAINING_AMOUNT" class="form-control" value="0" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label">Amount to Pay</label>
                                    <div class="col-md-12">
                                        <input type="text" name="AMOUNT_TO_PAY" id="AMOUNT_TO_PAY" value="<?=($AMOUNT) ?? 0?>" class="form-control" onkeyup="calculatePartialPayment('actual')">
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label">Payment Type</label>
                                    <div class="col-md-12">
                                        <select class="form-control PAYMENT_TYPE ENROLLMENT_PAYMENT_TYPE" required name="PK_PAYMENT_TYPE" id="PK_PAYMENT_TYPE" onchange="selectPaymentType(this, 'enrollment')">
                                            <option value="">Select</option>
                                            <?php
                                            $row = $db->Execute("SELECT * FROM DOA_PAYMENT_TYPE WHERE ACTIVE = 1");
                                            while (!$row->EOF) { ?>
                                                <option value="<?php echo $row->fields['PK_PAYMENT_TYPE'];?>"><?=$row->fields['PAYMENT_TYPE']?></option>
                                            <?php $row->MoveNext(); } ?>
                                        </select>
                                    </div>
                                    <div id="wallet_balance_div">

                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if ($PAYMENT_GATEWAY == 'Stripe'){ ?>
                            <div class="row" id="card_list">
                            </div>
                            <div class="row payment_type_div" id="credit_card_payment" style="display: none;">
                                <div class="col-12">
                                    <div class="form-group" id="card_div">

                                    </div>
                                </div>
                            </div>
                        <?php } elseif ($PAYMENT_GATEWAY == 'Square') { ?>
                            <div class="row payment_type_div" id="credit_card_payment" style="display: none;">
                                <div class="row" id="card_list">
                                </div>
                                <div class="col-12">
                                    <div class="form-group" id="card_div">

                                    </div>
                                </div>
                                <div id="payment-status-container"></div>
                            </div>
                        <?php } elseif ($PAYMENT_GATEWAY == 'Authorized.net') {
                            $customer_data = $db->Execute("SELECT CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.EMAIL_ID FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER WHERE DOA_USER_MASTER.PK_USER_MASTER = '$PK_USER_MASTER'");
                            ?>
                            <div class="payment_type_div" id="credit_card_payment" style="display: none;">
                                <div class="row" id="card_list">
                                </div>

                                <div class="row">
                                    <div class="col-12">
                                        <div class="form-group">
                                            <label class="form-label">Name (As it appears on your card)</label>
                                            <div class="col-md-12">
                                                <input type="text" name="NAME" id="NAME" class="form-control" value="<?=$customer_data->fields['NAME']?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12">
                                        <div class="form-group">
                                            <label class="form-label">Email (For receiving payment confirmation mail)</label>
                                            <div class="col-md-12">
                                                <input type="email" name="EMAIL" id="EMAIL" class="form-control" value="<?=$customer_data->fields['EMAIL_ID']?>" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12">
                                        <div class="form-group">
                                            <label class="form-label">Card Number</label>
                                            <div class="col-md-12">
                                                <input type="text" name="CARD_NUMBER" id="CARD_NUMBER" placeholder="Card Number" class="form-control format-card" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-4">
                                        <div class="form-group">
                                            <label class="form-label">Expiration Month</label>
                                            <div class="col-md-12">
                                                <select name="EXPIRATION_MONTH" id="EXPIRATION_MONTH" class="form-control" required>
                                                    <?php
                                                    for ($i = 1; $i <= 12; $i++) { ?>
                                                        <option value="<?=$i?>"><?=$i?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="form-group">
                                            <label class="form-label">Expiration Year</label>
                                            <div class="col-md-12">
                                                <select name="EXPIRATION_YEAR" id="EXPIRATION_YEAR" class="form-control" required>
                                                    <?php
                                                    $year = (int)date('Y');
                                                    for ($i = $year; $i <= $year+25; $i++) { ?>
                                                        <option value="<?=$i?>"><?=$i?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="form-group">
                                            <label class="form-label">Security Code</label>
                                            <div class="col-md-12">
                                                <input type="text" name="SECURITY_CODE" id="SECURITY_CODE" class="form-control" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                            </div>
                        <?php } ?>


                        <div class="row payment_type_div" id="check_payment" style="display: none;">
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label">Check Number</label>
                                    <div class="col-md-12">
                                        <input type="text" name="CHECK_NUMBER" class="form-control">
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label">Check Date</label>
                                    <div class="col-md-12">
                                        <input type="text" name="CHECK_DATE" class="form-control datepicker-normal">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!--<div class="row" id="remaining_amount_div" style="display: none;">
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label">Remaining Amount</label>
                                    <div class="col-md-12">
                                        <input type="text" name="REMAINING_AMOUNT" id="REMAINING_AMOUNT" class="form-control" readonly>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label">Payment Type</label>
                                    <div class="col-md-12">
                                        <select class="form-control" name="PK_PAYMENT_TYPE_PARTIAL" id="PK_PAYMENT_TYPE_PARTIAL" onchange="selectPartialPaymentType(this)">
                                            <option value="">Select</option>
                                            <?php
/*                                            $row = $db->Execute("SELECT * FROM DOA_PAYMENT_TYPE WHERE PAYMENT_TYPE != 'Wallet' AND ACTIVE = 1");
                                            while (!$row->EOF) { */?>
                                                <option value="<?php /*echo $row->fields['PK_PAYMENT_TYPE'];*/?>"><?php /*=$row->fields['PAYMENT_TYPE']*/?></option>
                                            <?php /*$row->MoveNext(); } */?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>-->

                        <div class="row">
                            <div class="col-12 partial_payment">
                                <div class="form-group">
                                    <label class="col-md-12 mt-3"><input type="checkbox" id="PARTIAL_PAYMENT" name="PARTIAL_PAYMENT" class="form-check-inline" onchange="showPartialPaymentDiv(this)"> Multiple Payment Type</label>
                                </div>
                            </div>
                        </div>

                        <div class="row" id="save_card_on_file_div" style="display: none;">
                            <div class="col-12">
                                <div class="form-group">
                                    <label class="col-md-12 mt-3"><input type="checkbox" id="SAVE_CARD_ON_FILE" name="SAVE_CARD_ON_FILE" class="form-check-inline" checked disabled> Save Card on File</label>
                                </div>
                            </div>
                        </div>

                        <div class="row form-group partial_payment_div" style="display: none;">
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label">Partial Payment</label>
                                    <div class="col-md-12">
                                        <input type="text" name="PARTIAL_AMOUNT" id="PARTIAL_AMOUNT" value="<?=($AMOUNT) ?? 0?>" class="form-control" onkeyup="calculatePartialPayment('partial')">
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label">Partial Payment Type</label>
                                    <div class="col-md-12">
                                        <select class="form-control" name="PK_PAYMENT_TYPE_PARTIAL" id="PK_PAYMENT_TYPE_PARTIAL" onchange="selectPartialPaymentType(this)">
                                            <option value="">Select</option>
                                            <?php
                                            $row = $db->Execute("SELECT * FROM DOA_PAYMENT_TYPE WHERE PK_PAYMENT_TYPE NOT IN (1, 7, 8, 9, 10, 11, 13, 14) AND ACTIVE = 1");
                                            while (!$row->EOF) { ?>
                                                <option value="<?php echo $row->fields['PK_PAYMENT_TYPE'];?>"><?=$row->fields['PAYMENT_TYPE']?></option>
                                            <?php $row->MoveNext(); } ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row partial_payment_type_div" id="partial_credit_card_payment" style="display: none;">
                            <div class="col-12">
                                <div class="form-group" id="remaining_card_div">

                                </div>
                            </div>
                        </div>

                        <div class="row partial_payment_type_div" id="partial_check_payment" style="display: none;">
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label">Check Number</label>
                                    <div class="col-md-12">
                                        <input type="text" name="CHECK_NUMBER_PARTIAL" class="form-control">
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label">Check Date</label>
                                    <div class="col-md-12">
                                        <input type="text" name="CHECK_DATE_PARTIAL" class="form-control datepicker-normal">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label class="form-label">Notes</label>
                                    <div class="col-md-12">
                                        <textarea class="form-control" name="NOTE" rows="3"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12" id="payment_status">

                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="$('#enrollment_payment_modal').modal('hide');">Close</button>
                    <button type="submit" id="enr-payment-btn" class="btn btn-info waves-effect waves-light m-r-10 text-white" style="float: right;">Process</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php
if ($GATEWAY_MODE == 'live')
    $SQ_URL = "https://connect.squareup.com";
else
    $SQ_URL = "https://connect.squareupsandbox.com";

if ($GATEWAY_MODE == 'live')
    $URL = "https://web.squarecdn.com/v1/square.js";
else
    $URL = "https://sandbox.web.squarecdn.com/v1/square.js";
?>

<script src="https://js.stripe.com/v3/"></script>
<script type="text/javascript">
    var stripe = Stripe('<?=$PUBLISHABLE_KEY?>');
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
    var stripe_card = elements.create('card', {style: style});
    var pay_type = '';

    function stripePaymentFunction(type) {
        pay_type = type;
        // Add an instance of the card Element into the `card-element` <div>.
        if (($('#card-element')).length > 0) {
            stripe_card.mount('#card-element');
        }
        // Handle real-time validation errors from the card Element.
        stripe_card.addEventListener('change', function (event) {
            var displayError = document.getElementById('card-errors');
            if (event.error) {
                displayError.textContent = event.error.message;
            } else {
                displayError.textContent = '';
                addStripeTokenOnForm();
            }
        });
        // Handle form submission.
        /*let form = document.getElementById(type+'_payment_form');
        form.addEventListener('submit', listener);*/
    }

    function addStripeTokenOnForm(){
        //event.preventDefault();
        stripe.createToken(stripe_card).then(function (result) {
            if (result.error) {
                // Inform the user if there was an error.
                let errorElement = document.getElementById('card-errors');
                errorElement.textContent = result.error.message;
            } else {
                // Send the token to your server.
                $('#token').val(result.token.id);
                //stripeTokenHandler(result.token);
            }
        });
    }

    // Submit the form with the token ID.
    function stripeTokenHandler(token) {
        $('#token').val(token.id);
        /*alert(token);
        // Insert the token ID into the form, so it gets submitted to the server
        let form = document.getElementById(pay_type+'_payment_form');
        let hiddenInput = document.createElement('input');
        hiddenInput.setAttribute('type', 'hidden');
        hiddenInput.setAttribute('name', 'token');
        hiddenInput.setAttribute('value', token.id);
        form.appendChild(hiddenInput);
        //form.submit();*/
    }
</script>


<script src="<?=$URL?>"></script>
<script type="text/javascript">
let square_card;

async function squarePaymentFunction(type) {
    let square_appId = '<?=$SQUARE_APP_ID ?>';
    let square_locationId = '<?=$SQUARE_LOCATION_ID ?>';
    const payments = Square.payments(square_appId, square_locationId);
    square_card = await payments.card();
    $('#'+type+'-card-container').text('');
    await square_card.attach('#'+type+'-card-container');
}

async function addSquareTokenOnForm() {
    const statusContainer = document.getElementById('payment-status-container');

    try {
        // Tokenize the card details
        const result = await square_card.tokenize();
        if (result.status === 'OK') {
            // Add the token to the hidden input field
            $('#enrollment_sourceId').val(result.token);
            console.log(`Payment token is ${result.token}`);

            // Submit the form after adding the token
            //form.submit();
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


<script>
    function sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    $(document).on('submit', '#enrollment_payment_form', function (event) {
        $('#enr-payment-btn').prop('disabled', true);
        event.preventDefault();

        let PAYMENT_GATEWAY = $('#PAYMENT_GATEWAY').val();
        if (PAYMENT_GATEWAY == 'Square') {
            let PAYMENT_METHOD_ID = $('#PAYMENT_METHOD_ID').val();
            if (PAYMENT_METHOD_ID == '') {    
                addSquareTokenOnForm();
                sleep(3000).then(() => {
                    submitEnrollmentPaymentForm();
                });
            } else {
                submitEnrollmentPaymentForm();
            }
        } else {
            submitEnrollmentPaymentForm();
        }
    });

    function submitEnrollmentPaymentForm() {
        let form_data = $('#enrollment_payment_form').serialize();
        $.ajax({
            url: "includes/process_enrollment_payment.php",
            type: 'POST',
            data: form_data,
            dataType: 'json',
            success:function (data) {
                if (data.STATUS === 'Failed') {
                    $('#payment_status').html(`<p class="alert alert-danger">${data.PAYMENT_INFO}</p>`);
                    $('#enr-payment-btn').prop('disabled', false);
                } else {
                    $('#payment_status').html(`<p class="alert alert-success">Payment Successful, Page will refresh automatically.</p>`);

                    setTimeout(function() {
                        let header = '<?=$header?>';
                        if (header) {
                            window.location.href = header;
                        } else {
                            let PK_USER = $('#PK_USER_MASTER').find(':selected').data('pk_user');
                            let PK_USER_MASTER = $('#PK_USER_MASTER').find(':selected').data('customer_id');
                            window.location.href = 'customer.php?id=' + PK_USER + '&master_id=' + PK_USER_MASTER + '&tab=enrollment';
                        }
                        //location.reload();
                    }, 3000);
                }
                console.log(data);
            }
        });
    }

    function getPaymentMethodId(param) {
        $('.credit-card-div').css("opacity", "1");
        $('#PAYMENT_METHOD_ID').val($(param).attr('id'));
        $(param).css("opacity", "0.6");
        /*let form = document.getElementById('enrollment_payment_form');
        form.removeEventListener('submit', listener);*/
        $(param).closest('.payment_modal').find('#card-element').remove();
        $(param).closest('.payment_modal').find('#enrollment-card-container').remove();
    }

    $(document).on('click', '.credit-card', function () {
        $('.credit-card').css("opacity", "1");
        $(this).css("opacity", "0.6");
    });

    function selectPaymentType(param, type){
        let paymentType = parseInt($(param).val());
        let PAYMENT_GATEWAY = $('#PAYMENT_GATEWAY').val();
        $(param).closest('.payment_modal').find('.payment_type_div').slideUp();
        $('#PAYMENT_METHOD_ID').val('');
        $('#card_list').slideUp();
        /*let form = document.getElementById(type+'_payment_form');
        form.removeEventListener('submit', listener);*/
        $(param).closest('.payment_modal').find('#card-element').remove();
        $(param).closest('.payment_modal').find('#enrollment-card-container').remove();
        switch (paymentType) {
            case 1:
                $(param).closest('.payment_modal').find('#credit_card_payment').slideDown();
                if (PAYMENT_GATEWAY == 'Stripe') {
                    $(param).closest('.payment_modal').find('#card_div').html(`<div id="card-element"></div><p id="card-errors" role="alert"></p>`);
                    stripePaymentFunction(type);
                }

                if (PAYMENT_GATEWAY == 'Square') {
                    $(param).closest('.payment_modal').find('#card_div').html(`<div id="enrollment-card-container"></div>`);
                    $('#'+type+'-card-container').text('Loading......');
                    squarePaymentFunction(type);
                }

                if (PAYMENT_GATEWAY == 'Authorized.net') {
                    $(".format-card").inputmask({
                        mask: "9999 9999 9999 9999",
                        placeholder: ""
                    });
                }
                getCreditCardList();
                
                break;

            case 14:
                getCreditCardList();
                break;

            case 2:
                $(param).closest('.payment_modal').find('#check_payment').slideDown();
                break;

            case 7:
                let PK_USER_MASTER = $('#PK_USER_MASTER').val();
                $.ajax({
                    url: "ajax/wallet_balance.php",
                    type: 'POST',
                    data: {PK_USER_MASTER: PK_USER_MASTER},
                    success: function (data) {
                        $('#wallet_balance_div').html(data);
                        $('#wallet_balance_div').slideDown();

                        let ACTUAL_AMOUNT = parseFloat($('#ACTUAL_AMOUNT').val());
                        let WALLET_BALANCE = parseFloat($('#WALLET_BALANCE').val());

                        if (ACTUAL_AMOUNT > WALLET_BALANCE) {
                            //$('#PARTIAL_PAYMENT').prop('checked', true);
                            //$('.partial_payment_div').slideDown();

                            $('#AMOUNT_TO_PAY').val(WALLET_BALANCE);
                            $('#PARTIAL_AMOUNT').val(0);
                            $('#REMAINING_AMOUNT').val(ACTUAL_AMOUNT - WALLET_BALANCE);

                            //$('#PK_PAYMENT_TYPE_PARTIAL').prop('required', true);
                        } else {
                            //$('#PARTIAL_PAYMENT').prop('checked', false);
                            let ACTUAL_AMOUNT = $('#ACTUAL_AMOUNT').val();
                            $('#AMOUNT_TO_PAY').val(ACTUAL_AMOUNT);
                            $('#PARTIAL_AMOUNT').val(0);
                            $('#REMAINING_AMOUNT').val(0);
                            //$('.partial_payment_div').slideUp();
                            //$('#PK_PAYMENT_TYPE_PARTIAL').prop('required', false);
                        }
                    }
                });
                break;

            case 3:
            default:
                $(param).closest('.payment_modal').find('.payment_type_div').slideUp();
                $(param).closest('.payment_modal').find('#wallet_balance_div').slideUp();
                $(param).closest('.payment_modal').find('#partial_payment_div').slideUp();
                $(param).closest('.payment_modal').find('#PK_PAYMENT_TYPE_PARTIAL').prop('required', false);
                break;
        }
    }

    function getCreditCardList() {
        let PK_USER_MASTER = $('#PK_USER_MASTER').val();
        let PAYMENT_GATEWAY = $('#PAYMENT_GATEWAY').val();
        $.ajax({
            url: "ajax/get_credit_card_list.php",
            type: 'POST',
            data: {PK_USER_MASTER: PK_USER_MASTER, PAYMENT_GATEWAY: PAYMENT_GATEWAY},
            success: function (data) {
                $('#card_list').slideDown().html(data);
            }
        });
    }

    function selectPartialPaymentType(param){
        let paymentType = $("#PK_PAYMENT_TYPE_PARTIAL option:selected").text();
        let PAYMENT_GATEWAY = $('#PAYMENT_GATEWAY').val();
        $('.partial_payment_type_div').slideUp();
        $('#card-element').remove();
        switch (paymentType) {
            case 'Credit Card':
                if (PAYMENT_GATEWAY == 'Stripe') {
                    $('#card_div').html(`<div id="card-element"></div>`);
                    stripePaymentFunction('enrollment');
                }
                $('#partial_credit_card_payment').slideDown();
                break;

            case 'Check':
                $('#partial_check_payment').slideDown();
                break;

            case 'Cash':
            default:
                $('.partial_payment_type_div').slideUp();
                break;
        }
    }

    function showPartialPaymentDiv(param) {
        if ($(param).is(':checked')) {
            $('.partial_payment_div').slideDown();
            let ACTUAL_AMOUNT = parseFloat($('#ACTUAL_AMOUNT').val());
            let AMOUNT_TO_PAY = parseFloat($('#AMOUNT_TO_PAY').val());
            $('#PARTIAL_AMOUNT').val(ACTUAL_AMOUNT - AMOUNT_TO_PAY);
            $('#REMAINING_AMOUNT').val(0);
        } else {
            let ACTUAL_AMOUNT = $('#ACTUAL_AMOUNT').val();
            $('#AMOUNT_TO_PAY').val(ACTUAL_AMOUNT);
            $('#PARTIAL_AMOUNT').val(0);
            $('#REMAINING_AMOUNT').val(0);
            $('.partial_payment_div').slideUp();
        }
    }

    function calculatePartialPayment(type) {
        let ACTUAL_AMOUNT = parseFloat($('#ACTUAL_AMOUNT').val());
        let AMOUNT_TO_PAY = parseFloat($('#AMOUNT_TO_PAY').val());
        let PARTIAL_AMOUNT = (type == 'partial') ? parseFloat($('#PARTIAL_AMOUNT').val()) : 0;

        if (!$('#AMOUNT_TO_PAY').val()) {
            $('#REMAINING_AMOUNT').val(ACTUAL_AMOUNT);
            $('#PARTIAL_AMOUNT').val(0);
            return false;
        }

        if (isNaN($('#AMOUNT_TO_PAY').val())) {
            $('#AMOUNT_TO_PAY').val(ACTUAL_AMOUNT);
            $('#PARTIAL_AMOUNT').val(0);
            $('#REMAINING_AMOUNT').val(0);
            return false;
        }

        if ($('#PARTIAL_PAYMENT').is(':checked')) {
            if (PARTIAL_AMOUNT == 0) {

            } else {
                if ((!$('#PARTIAL_AMOUNT').val() || isNaN($('#PARTIAL_AMOUNT').val())) && type == 'partial') {
                    $('#PARTIAL_AMOUNT').val(ACTUAL_AMOUNT - AMOUNT_TO_PAY);
                    $('#REMAINING_AMOUNT').val(0);
                    return false;
                }
            }

            if ((ACTUAL_AMOUNT - (AMOUNT_TO_PAY + PARTIAL_AMOUNT)) <= 0) {
                $('#AMOUNT_TO_PAY').val(ACTUAL_AMOUNT);
                $('#PARTIAL_AMOUNT').val(0);
                $('#REMAINING_AMOUNT').val(0);
            } else {
                if (type == 'partial') {
                    $('#REMAINING_AMOUNT').val(ACTUAL_AMOUNT - (AMOUNT_TO_PAY + PARTIAL_AMOUNT));
                } else {
                    $('#PARTIAL_AMOUNT').val(ACTUAL_AMOUNT - AMOUNT_TO_PAY);
                    $('#REMAINING_AMOUNT').val(0);
                }
            }
        } else {
            if ((ACTUAL_AMOUNT - AMOUNT_TO_PAY) < 0) {
                $('#AMOUNT_TO_PAY').val(ACTUAL_AMOUNT);
                $('#REMAINING_AMOUNT').val(0);
            } else {
                $('#REMAINING_AMOUNT').val(ACTUAL_AMOUNT - AMOUNT_TO_PAY);
            }
        }
    }

</script>
