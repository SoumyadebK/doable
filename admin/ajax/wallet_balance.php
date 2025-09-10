<?php
require_once('../../global/config.php');
$PK_USER_MASTER = $_POST['PK_USER_MASTER'];
?>

<select class="form-control" required name="PK_CUSTOMER_WALLET" id="PK_CUSTOMER_WALLET" style="margin-top:10px;" onchange="selectThisReceipt(this);">
    <option value="">Select Receipt</option>
    <?php
    $wallet_data = $db_account->Execute("SELECT * FROM DOA_CUSTOMER_WALLET WHERE CUSTOMER_WALLET_PARENT = 0 AND BALANCE_LEFT > 0 AND PK_USER_MASTER = '$PK_USER_MASTER' ORDER BY BALANCE_LEFT ASC");
    while (!$wallet_data->EOF) { ?>
        <option value="<?= $wallet_data->fields['PK_CUSTOMER_WALLET'] ?>" data-balance_left="<?= $wallet_data->fields['BALANCE_LEFT'] ?>"><?= $wallet_data->fields['RECEIPT_NUMBER'] ?> ($<?= $wallet_data->fields['BALANCE_LEFT'] ?>)</option>
    <?php $wallet_data->MoveNext();
    } ?>
</select>

<script>
    function selectThisReceipt(param) {
        let ACTUAL_AMOUNT = parseFloat($('#ACTUAL_AMOUNT').val());
        let WALLET_BALANCE = $(param).find(':selected').data('balance_left');

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
</script>