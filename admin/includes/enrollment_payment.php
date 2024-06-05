<div class="modal fade payment_modal" id="enrollment_payment_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="enrollment_payment_form" action="includes/process_enrollment_payment.php" method="post" enctype="multipart/form-data">
            <div class="modal-content">
                <div class="modal-header">
                    <h4><b>Payment</b></h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="sourceId" id="sourceId">
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
                                    <label class="form-label">Amount</label>
                                    <div class="col-md-12">
                                        <input type="text" name="AMOUNT" id="AMOUNT_TO_PAY" value="<?=($AMOUNT) ?? 0?>" class="form-control" readonly>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label">Payment Type</label>
                                    <div class="col-md-12">
                                        <select class="form-control" required name="PK_PAYMENT_TYPE" id="PK_PAYMENT_TYPE" onchange="selectPaymentType(this)">
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

                        <div class="row" id="remaining_amount_div" style="display: none;">
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
                                        <select class="form-control" name="PK_PAYMENT_TYPE_REMAINING" id="PK_PAYMENT_TYPE_REMAINING" onchange="selectRemainingPaymentType(this)">
                                            <option value="">Select</option>
                                            <?php
                                            $row = $db->Execute("SELECT * FROM DOA_PAYMENT_TYPE WHERE PAYMENT_TYPE != 'Wallet' AND ACTIVE = 1");
                                            while (!$row->EOF) { ?>
                                                <option value="<?php echo $row->fields['PK_PAYMENT_TYPE'];?>"><?=$row->fields['PAYMENT_TYPE']?></option>
                                                <?php $row->MoveNext(); } ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row remaining_payment_type_div" id="remaining_credit_card_payment" style="display: none;">
                            <div class="col-12">
                                <div class="form-group" id="remaining_card_div">

                                </div>
                            </div>
                        </div>

                        <div class="row remaining_payment_type_div" id="remaining_check_payment" style="display: none;">
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label">Check Number</label>
                                    <div class="col-md-12">
                                        <input type="text" name="CHECK_NUMBER_REMAINING" class="form-control">
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label">Check Date</label>
                                    <div class="col-md-12">
                                        <input type="text" name="CHECK_DATE_REMAINING" class="form-control datepicker-normal">
                                    </div>
                                </div>
                            </div>
                        </div>


                        <?php if ($PAYMENT_GATEWAY == 'Stripe'){ ?>
                            <div class="row payment_type_div" id="credit_card_payment" style="display: none;">
                                <div class="row" style="margin: auto;" id="card_list">
                                </div>
                                <div class="col-12">
                                    <div class="form-group" id="card_div">

                                    </div>
                                </div>
                            </div>
                        <?php } elseif ($PAYMENT_GATEWAY == 'Square') { ?>
                            <div class="row payment_type_div" id="credit_card_payment" style="display: none;">
                                <div class="row" style="margin: auto;" id="card_list">
                                </div>
                                <div class="col-12">
                                    <div class="form-group" id="card-container">

                                    </div>
                                </div>
                                <div id="payment-status-container"></div>
                            </div>
                        <?php } elseif ($PAYMENT_GATEWAY == 'Authorized.net'){?>
                            <div class="payment_type_div" id="credit_card_payment" style="display: none;">
                                <div class="row" style="margin: auto;" id="card_list">
                                </div>
                                <div class="row">
                                    <div class="col-12">
                                        <div class="form-group">
                                            <label class="form-label">Name (As it appears on your card)</label>
                                            <div class="col-md-12">
                                                <input type="text" name="NAME" id="NAME" class="form-control" value="<?=$NAME?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12">
                                        <div class="form-group">
                                            <label class="form-label">Email (For receiving payment confirmation mail)</label>
                                            <div class="col-md-12">
                                                <input type="email" name="EMAIL" id="EMAIL" class="form-control">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12">
                                        <div class="form-group">
                                            <label class="form-label">Card Number</label>
                                            <div class="col-md-12">
                                                <input type="text" name="CARD_NUMBER" id="CARD_NUMBER" class="form-control" value="<?=$CARD_NUMBER?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-4">
                                        <div class="form-group">
                                            <label class="form-label">Expiration Month</label>
                                            <div class="col-md-12">
                                                <input type="text" name="EXPIRATION_MONTH" id="EXPIRATION_MONTH" class="form-control" >
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="form-group">
                                            <label class="form-label">Expiration Year</label>
                                            <div class="col-md-12">
                                                <input type="text" name="EXPIRATION_YEAR" id="EXPIRATION_YEAR" class="form-control" >
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="form-group">
                                            <label class="form-label">Security Code</label>
                                            <div class="col-md-12">
                                                <input type="text" name="SECURITY_CODE" id="SECURITY_CODE" class="form-control" value="<?=$SECURITY_CODE?>">
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
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" id="card-button" class="btn btn-info waves-effect waves-light m-r-10 text-white" style="float: right;">Process</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://js.stripe.com/v3/"></script>
<script type="text/javascript">
    function stripePaymentFunction() {
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
        var card = elements.create('card', {style: style});

        // Add an instance of the card Element into the `card-element` <div>.
        if (($('#card-element')).length > 0) {
            card.mount('#card-element');
        }

        // Handle real-time validation errors from the card Element.
        card.addEventListener('change', function (event) {
            var displayError = document.getElementById('card-errors');
            if (event.error) {
                displayError.textContent = event.error.message;
            } else {
                displayError.textContent = '';
            }
        });

        // Handle form submission.
        var form = document.getElementById('enrollment_payment_form');
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
            let form = document.getElementById('enrollment_payment_form');
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
    function getPaymentMethodId(param) {
        $('#PAYMENT_METHOD_ID').val($(param).attr('id'));
    }

    function selectPaymentType(param){
        let paymentType = parseInt($(param).val());
        let PAYMENT_GATEWAY = $('#PAYMENT_GATEWAY').val();
        $(param).closest('.payment_modal').find('.payment_type_div').slideUp();
        $(param).closest('.payment_modal').find('#card-element').remove();
        switch (paymentType) {
            case 1:
                if (PAYMENT_GATEWAY == 'Stripe') {
                    $(param).closest('.payment_modal').find('#card_div').html(`<div id="card-element"></div><p id="card-errors" role="alert"></p>`);
                    stripePaymentFunction();
                }

                getCreditCardList();
                $(param).closest('.payment_modal').find('#credit_card_payment').slideDown();
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

                        let AMOUNT_TO_PAY = parseFloat($('#AMOUNT_TO_PAY').val());
                        let WALLET_BALANCE = parseFloat($('#WALLET_BALANCE').val());

                        if (AMOUNT_TO_PAY > WALLET_BALANCE) {
                            $('#REMAINING_AMOUNT').val(AMOUNT_TO_PAY - WALLET_BALANCE);
                            $('#remaining_amount_div').slideDown();
                            $('#PK_PAYMENT_TYPE_REMAINING').prop('required', true);
                        } else {
                            $('#remaining_amount_div').slideUp();
                            $('#PK_PAYMENT_TYPE_REMAINING').prop('required', false);
                        }
                    }
                });
                break;

            case 3:
            default:
                $(param).closest('.payment_modal').find('.payment_type_div').slideUp();
                $(param).closest('.payment_modal').find('#wallet_balance_div').slideUp();
                $(param).closest('.payment_modal').find('#remaining_amount_div').slideUp();
                $(param).closest('.payment_modal').find('#PK_PAYMENT_TYPE_REMAINING').prop('required', false);
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
                $('#card_list').html(data);
            }
        });
    }

    function selectRemainingPaymentType(param){
        let paymentType = $("#PK_PAYMENT_TYPE_REMAINING option:selected").text();
        let PAYMENT_GATEWAY = $('#PAYMENT_GATEWAY').val();
        $('.remaining_payment_type_div').slideUp();
        $('#card-element').remove();
        switch (paymentType) {
            case 'Credit Card':
                if (PAYMENT_GATEWAY == 'Stripe') {
                    $('#card_div').html(`<div id="card-element"></div>`);
                    stripePaymentFunction();
                }
                $('#remaining_credit_card_payment').slideDown();
                break;

            case 'Check':
                $('#remaining_check_payment').slideDown();
                break;

            case 'Cash':
            default:
                $('.remaining_payment_type_div').slideUp();
                break;
        }
    }
</script>
