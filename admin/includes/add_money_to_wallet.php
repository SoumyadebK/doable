<h4><b>Add Money</b></h4>

<form id="payment_confirmation_form" role="form" action="" method="post">
    <input type="hidden" name="FUNCTION_NAME" value="addMoneyToWallet">
    <input type="hidden" name="PAYMENT_GATEWAY" id="PAYMENT_GATEWAY" value="<?=$PAYMENT_GATEWAY?>">
    <input type="hidden" name="PK_USER_MASTER" class="CUSTOMER_ID" id="PK_USER_MASTER" value="<?=$PK_USER_MASTER?>">

    <div class="p-20">
        <div class="row">
            <div class="col-6">
                <div class="form-group">
                    <label class="form-label">Amount</label>
                    <div class="col-md-12">
                        <input type="text" name="AMOUNT" value="" class="form-control" required>
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
                            $row = $db->Execute("SELECT * FROM DOA_PAYMENT_TYPE WHERE PAYMENT_TYPE != 'Wallet' AND ACTIVE = 1");
                            while (!$row->EOF) { ?>
                                <option value="<?php echo $row->fields['PK_PAYMENT_TYPE'];?>"><?=$row->fields['PAYMENT_TYPE']?></option>
                            <?php $row->MoveNext(); } ?>
                        </select>
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
        <div class="form-group">
            <button type="submit" id="card-button" class="btn btn-info waves-effect waves-light m-r-10 text-white" style="float: right;">Process</button>
        </div>
    </div>
</form>


<script>
    function getPaymentMethodId(param) {
        $('#PAYMENT_METHOD_ID').val($(param).attr('id'));
    }

    function selectPaymentType(param){
        let paymentType = $("#PK_PAYMENT_TYPE option:selected").text();
        let PAYMENT_GATEWAY = $('#PAYMENT_GATEWAY').val();
        $('.payment_type_div').slideUp();
        $('#card-element').remove();
        switch (paymentType) {
            case 'Credit Card':
                if (PAYMENT_GATEWAY == 'Stripe') {
                    $('#card_div').html(`<div id="card-element"></div>`);
                    stripePaymentFunction();
                }

                getCreditCardList();
                $('#credit_card_payment').slideDown();
                break;

            case 'Check':
                $('#check_payment').slideDown();
                break;

            case 'Cash':
            default:
                $('.payment_type_div').slideUp();
                $('#wallet_balance_div').slideUp();
                $('#remaining_amount_div').slideUp();
                $('#PK_PAYMENT_TYPE_REMAINING').prop('required', false);
                break;
        }
    }
</script>