<?php
require_once('../global/config.php');
$title = "Billing";

$user_master_data = $account = $db->Execute("SELECT * FROM DOA_USER_MASTER WHERE PK_USER_MASTER = ".$_SESSION['PK_USER_MASTER']);
$PK_USER = $user_master_data->fields['PK_USER'];
$PK_USER_MASTER_ARRAY = [];
while (!$user_master_data->EOF){
    $PK_USER_MASTER_ARRAY[] = $user_master_data->fields['PK_USER_MASTER'];
    $user_master_data->MoveNext();
}
$PK_USER_MASTERS = implode(',', $PK_USER_MASTER_ARRAY);

$results_per_page = 100;

if (isset($_GET['search_text']) && $_GET['search_text'] != '') {
    $search_text = $_GET['search_text'];
    $search = " AND DOA_USERS.FIRST_NAME LIKE '%".$search_text."%' OR DOA_USERS.EMAIL_ID LIKE '%".$search_text."%' OR DOA_USERS.PHONE LIKE '%".$search_text."%'";
} else {
    $search_text = '';
    $search = ' ';
}

$query = $db->Execute("SELECT count($account_database.DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS TOTAL_RECORDS FROM $account_database.`DOA_ENROLLMENT_MASTER` INNER JOIN $master_database.DOA_LOCATION ON $master_database.DOA_LOCATION.PK_LOCATION = $account_database.DOA_ENROLLMENT_MASTER.PK_LOCATION  WHERE $account_database.DOA_ENROLLMENT_MASTER.PK_USER_MASTER IN (".$PK_USER_MASTERS.")".$search);
$number_of_result =  $query->fields['TOTAL_RECORDS'];
$number_of_page = ceil ($number_of_result / $results_per_page);

if (!isset ($_GET['page']) ) {
    $page = 1;
} else {
    $page = $_GET['page'];
}
$page_first_result = ($page-1) * $results_per_page;


if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 4){
    header("location:../login.php");
    exit;
}

$account_data = $db->Execute("SELECT * FROM `DOA_ACCOUNT_MASTER` WHERE `PK_ACCOUNT_MASTER` = '$_SESSION[PK_ACCOUNT_MASTER]'");

$PAYMENT_GATEWAY = $account_data->fields['PAYMENT_GATEWAY_TYPE'];
$SECRET_KEY = $account_data->fields['SECRET_KEY'];
$PUBLISHABLE_KEY = $account_data->fields['PUBLISHABLE_KEY'];

$ACCESS_TOKEN = $account_data->fields['ACCESS_TOKEN'];
$APP_ID = $account_data->fields['APP_ID'];
$LOCATION_ID = $account_data->fields['LOCATION_ID'];

if(!empty($_POST['PK_PAYMENT_TYPE'])){
    $PK_ENROLLMENT_LEDGER = $_POST['PK_ENROLLMENT_LEDGER'];
    unset($_POST['PK_ENROLLMENT_LEDGER']);
    if(empty($_POST['PK_ENROLLMENT_PAYMENT'])){
        if ($_POST['PK_PAYMENT_TYPE'] == 1) {
            if ($_POST['PAYMENT_GATEWAY'] == 'Stripe') {
                require_once("../global/stripe-php-master/init.php");
                \Stripe\Stripe::setApiKey($_POST['SECRET_KEY']);
                $STRIPE_TOKEN = $_POST['token'];
                $AMOUNT = $_POST['AMOUNT'];
                try {
                    $charge = \Stripe\Charge::create([
                        'amount' => ($AMOUNT * 100),
                        'currency' => 'usd',
                        'description' => $_POST['NOTE'],
                        'source' => $STRIPE_TOKEN
                    ]);
                } catch (Exception $e) {

                }
                if($charge->paid == 1){
                    $PAYMENT_INFO = $charge->id;
                }else{
                    $PAYMENT_INFO = 'Payment Unsuccessful.';
                }
            }
        }else{
            $PAYMENT_INFO = 'Payment Done.';
        }

        $PAYMENT_DATA['PK_ENROLLMENT_MASTER'] = $_POST['PK_ENROLLMENT_MASTER'];
        $PAYMENT_DATA['PK_ENROLLMENT_BILLING'] = $_POST['PK_ENROLLMENT_BILLING'];
        $PAYMENT_DATA['PK_PAYMENT_TYPE'] = $_POST['PK_PAYMENT_TYPE'];
        $PAYMENT_DATA['AMOUNT'] = $_POST['AMOUNT'];
        $PAYMENT_DATA['CHECK_NUMBER'] = $_POST['CHECK_NUMBER'];
        $PAYMENT_DATA['CHECK_DATE'] = $_POST['CHECK_DATE'];
        $PAYMENT_DATA['CHECK_DATE'] = $_POST['CHECK_DATE'];
        $PAYMENT_DATA['NOTE'] = $_POST['NOTE'];
        $PAYMENT_DATA['PAYMENT_DATE'] = date('Y-m-d');
        $PAYMENT_DATA['PAYMENT_INFO'] = $PAYMENT_INFO;
        db_perform_account('DOA_ENROLLMENT_PAYMENT', $PAYMENT_DATA, 'insert');

        $enrollment_balance = $db_account->Execute("SELECT * FROM `DOA_ENROLLMENT_BALANCE` WHERE PK_ENROLLMENT_MASTER = '$_POST[PK_ENROLLMENT_MASTER]'");
        if ($enrollment_balance->RecordCount() > 0){
            $ENROLLMENT_BALANCE_DATA['TOTAL_BALANCE_PAID'] = $enrollment_balance->fields['TOTAL_BALANCE_PAID']+$_POST['AMOUNT'];
            $ENROLLMENT_BALANCE_DATA['EDITED_BY']	= $_SESSION['PK_USER'];
            $ENROLLMENT_BALANCE_DATA['EDITED_ON'] = date("Y-m-d H:i");
            db_perform_account('DOA_ENROLLMENT_BALANCE', $ENROLLMENT_BALANCE_DATA, 'update'," PK_ENROLLMENT_MASTER =  '$_POST[PK_ENROLLMENT_MASTER]'");
        }else{
            $ENROLLMENT_BALANCE_DATA['PK_ENROLLMENT_MASTER'] = $_POST['PK_ENROLLMENT_MASTER'];
            $ENROLLMENT_BALANCE_DATA['TOTAL_BALANCE_PAID'] = $_POST['AMOUNT'];
            $ENROLLMENT_BALANCE_DATA['CREATED_BY']  = $_SESSION['PK_USER'];
            $ENROLLMENT_BALANCE_DATA['CREATED_ON']  = date("Y-m-d H:i");
            db_perform_account('DOA_ENROLLMENT_BALANCE', $ENROLLMENT_BALANCE_DATA, 'insert');
        }

        $PK_ENROLLMENT_PAYMENT = $db_account->insert_ID();
        $ledger_record = $db_account->Execute("SELECT * FROM `DOA_ENROLLMENT_LEDGER` WHERE PK_ENROLLMENT_LEDGER =  '$PK_ENROLLMENT_LEDGER'");
        $LEDGER_DATA['TRANSACTION_TYPE'] = 'Payment';
        $LEDGER_DATA['ENROLLMENT_LEDGER_PARENT'] = $PK_ENROLLMENT_LEDGER;
        $LEDGER_DATA['PK_ENROLLMENT_MASTER'] = $_POST['PK_ENROLLMENT_MASTER'];
        $LEDGER_DATA['PK_ENROLLMENT_BILLING'] = $_POST['PK_ENROLLMENT_BILLING'];
        $LEDGER_DATA['DUE_DATE'] = date('Y-m-d');
        $LEDGER_DATA['BILLED_AMOUNT'] = 0.00;
        $LEDGER_DATA['PAID_AMOUNT'] = $ledger_record->fields['BILLED_AMOUNT'];
        $LEDGER_DATA['BALANCE'] = 0.00;
        $LEDGER_DATA['IS_PAID'] = 1;
        $LEDGER_DATA['PK_PAYMENT_TYPE'] = $_POST['PK_PAYMENT_TYPE'];
        $LEDGER_DATA['PK_ENROLLMENT_PAYMENT'] = $PK_ENROLLMENT_PAYMENT;
        db_perform_account('DOA_ENROLLMENT_LEDGER', $LEDGER_DATA, 'insert');
        $LEDGER_UPDATE_DATA['IS_PAID'] = 1;
        db_perform_account('DOA_ENROLLMENT_LEDGER', $LEDGER_UPDATE_DATA, 'update', "PK_ENROLLMENT_LEDGER =  '$PK_ENROLLMENT_LEDGER'");
    }else{
        db_perform_account('DOA_ENROLLMENT_PAYMENT', $_POST, 'update'," PK_ENROLLMENT_PAYMENT =  '$_POST[PK_ENROLLMENT_PAYMENT]'");
        $PK_ENROLLMENT_PAYMENT = $_POST['PK_ENROLLMENT_PAYMENT'];
    }

    header('location:billing.php');
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
                <div class="col-md-4 align-self-center">
                    <ul class="nav nav-tabs" role="tablist" style="width: 61%">
                            <li> <a class="nav-link active" id="enrollment_tab_link" data-bs-toggle="tab" href="#enrollment" onclick="showEnrollmentList(1, 'normal')" role="tab"><span class="hidden-sm-up"><i class="ti-list"></i></span> <span class="hidden-xs-down">Active Enrollments</span></a> </li>
                            <li> <a class="nav-link" id="completed_enrollment_tab_link" data-bs-toggle="tab" href="#enrollment" onclick="showEnrollmentList(1, 'completed')" role="tab"><span class="hidden-sm-up"><i class="ti-view-list"></i></span> <span class="hidden-xs-down">Completed Enrollments</span></a> </li>
                    </ul>
                </div>
            </div>

            <div class="row">
                <div class="card">
                    <div class="card-body">
                        <div class="tab-content tabcontent-border">
                            <!--Enrollment Model-->
                            <div class="tab-pane active" id="enrollment" role="tabpanel">
                                <div id="enrollment_list" class="p-20">

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


                        <div class="card" id="payment_confirmation_form_div" style="display: none;">
                            <div class="card-body">
                                <h4><b>Payment</b></h4>

                                <form id="payment_confirmation_form" role="form" action="" method="post">
                                    <input type="hidden" name="FUNCTION_NAME" value="confirmEnrollmentPayment">
                                    <input type="hidden" name="PK_ENROLLMENT_MASTER" class="PK_ENROLLMENT_MASTER" value="<?=(empty($_GET['id']))?'':$_GET['id']?>">
                                    <input type="hidden" name="PK_ENROLLMENT_BILLING" class="PK_ENROLLMENT_BILLING" value="<?=$PK_ENROLLMENT_BILLING?>">
                                    <input type="hidden" name="PK_ENROLLMENT_LEDGER" class="PK_ENROLLMENT_LEDGER">
                                    <input type="hidden" name="SECRET_KEY" value="<?=$SECRET_KEY?>">
                                    <input type="hidden" name="PAYMENT_GATEWAY" value="<?=$PAYMENT_GATEWAY?>">
                                    <div class="p-20">
                                        <div class="row">
                                            <div class="col-3">
                                                <div class="form-group">
                                                    <label class="form-label">Amount</label>
                                                    <div class="col-md-12">
                                                        <input type="text" name="AMOUNT" id="AMOUNT_TO_PAY" value="<?=$AMOUNT?>" class="form-control" readonly>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-3">
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
                                                </div>
                                            </div>
                                        </div>


                                        <?php if ($PAYMENT_GATEWAY == 'Stripe'){ ?>
                                            <div class="row payment_type_div" id="credit_card_payment" style="display: none;">
                                                <div class="col-6">
                                                    <div class="form-group" id="card_div">

                                                    </div>
                                                </div>
                                            </div>
                                        <?php } elseif ($PAYMENT_GATEWAY == 'Square'){?>
                                            <div class="payment_type_div" id="credit_card_payment" style="display: none;">
                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="form-label">Name (As it appears on your card)</label>
                                                            <div class="col-md-12">
                                                                <input type="text" name="NAME" id="NAME" class="form-control" value="<?=$NAME?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="form-label">Card Number</label>
                                                            <div class="col-md-12">
                                                                <input type="text" name="CARD_NUMBER" id="CARD_NUMBER" class="form-control" value="<?=$CARD_NUMBER?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-3">
                                                        <div class="form-group">
                                                            <label class="form-label">Expiration Date</label>
                                                            <div class="col-md-12">
                                                                <input type="text" name="EXPIRATION_DATE" id="EXPIRATION_DATE" class="form-control" value="<?=$EXPIRATION_DATE?>" placeholder="MM/YYYY">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-3">
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
                                            <div class="col-3">
                                                <div class="form-group">
                                                    <label class="form-label">Check Number</label>
                                                    <div class="col-md-12">
                                                        <input type="text" name="CHECK_NUMBER" class="form-control">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-3">
                                                <div class="form-group">
                                                    <label class="form-label">Check Date</label>
                                                    <div class="col-md-12">
                                                        <input type="text" name="CHECK_DATE" class="form-control datepicker-normal">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>


                                        <div class="row">
                                            <div class="col-6">
                                                <div class="form-group">
                                                    <label class="form-label">Notes</label>
                                                    <div class="col-md-12">
                                                        <textarea class="form-control" name="NOTE" rows="3"></textarea>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white" style="float: right;">Process</button>
                                        </div>
                                    </div>
                                </form>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!--Payment Model-->
<?php include('includes/enrollment_payment.php'); ?>

<?php require_once('../includes/footer.php');?>

</body>

<script src="https://js.stripe.com/v3/"></script>
<script type="text/javascript">
    function stripePaymentFunction() {

        // Create a Stripe client.
        var stripe = Stripe('<?=$PUBLISHABLE_KEY?>');

        // Create an instance of Elements.
        var elements = stripe.elements();

        // Custom styling can be passed to options when creating an Element.
        // (Note that this demo uses a wider set of styles than the guide below.)
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
        var form = document.getElementById('payment_confirmation_form');
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            stripe.createToken(card).then(function (result) {
                if (result.error) {
                    // Inform the user if there was an error.
                    var errorElement = document.getElementById('card-errors');
                    errorElement.textContent = result.error.message;
                } else {
                    // Send the token to your server.
                    stripeTokenHandler(result.token);
                }
            });
        });

        // Submit the form with the token ID.
        function stripeTokenHandler(token) {
            // Insert the token ID into the form so it gets submitted to the server
            var form = document.getElementById('payment_confirmation_form');
            var hiddenInput = document.createElement('input');
            hiddenInput.setAttribute('type', 'hidden');
            hiddenInput.setAttribute('name', 'token');
            hiddenInput.setAttribute('value', token.id);
            form.appendChild(hiddenInput);

            //ACCEPT_HANDLING_ERROR
            // Submit the form
            form.submit();
        }
    }

</script>
<script>
    $('.datepicker-normal').datepicker({
        format: 'mm/dd/yyyy',
    });

    let PK_USER = parseInt(<?=empty($PK_USER)?0:$PK_USER?>);
    let PK_USER_MASTER = parseInt(<?=empty($_SESSION['PK_USER_MASTER'])?0:$_SESSION['PK_USER_MASTER']?>);

    function showEnrollmentList(page, type) {
        let PK_USER_MASTER=$('.PK_USER_MASTER').val();
        $.ajax({
            url: "pagination/enrollment.php",
            type: "GET",
            data: {search_text:'', page:page, type:type, pk_user:PK_USER, master_id:PK_USER_MASTER},
            async: false,
            cache: false,
            success: function (result) {
                $('#enrollment_list').html(result);
            }
        });
        window.scrollTo(0,0);
    }

    function payNow(PK_ENROLLMENT_MASTER, PK_ENROLLMENT_LEDGER, BILLED_AMOUNT) {
        $('.PK_ENROLLMENT_MASTER').val(PK_ENROLLMENT_MASTER);
        $('.PK_ENROLLMENT_LEDGER').val(PK_ENROLLMENT_LEDGER);
        $('#AMOUNT_TO_PAY').val(BILLED_AMOUNT);
        $('#ACTUAL_AMOUNT').val(BILLED_AMOUNT);
        $('#payment_confirmation_form_div').slideDown();
        $('#PK_PAYMENT_TYPE').val('');
        $('.payment_type_div').slideUp();
        $('#wallet_balance_div').slideUp();
        $('#remaining_amount_div').slideUp();
        $('#PK_PAYMENT_TYPE_REMAINING').prop('required', false);
        $('#enrollment_payment_modal').modal('show');
    }

    $(document).on('click', '.credit-card', function () {
        $('.credit-card').css("opacity", "1");
        $(this).css("opacity", "0.6");
    });

    function openReceipt(PK_ENROLLMENT_MASTER, RECEIPT_NUMBER) {
        let RECEIPT_NUMBER_ARRAY = RECEIPT_NUMBER.split(',');
        for (let i=0; i<RECEIPT_NUMBER_ARRAY.length; i++) {
            window.open('generate_receipt_pdf.php?master_id=' + PK_ENROLLMENT_MASTER + '&receipt=' + RECEIPT_NUMBER_ARRAY[i], '_blank');
        }
    }

    function selectPaymentType(param){
        let paymentType = $("#PK_PAYMENT_TYPE option:selected").text();
        $('.payment_type_div').slideUp();
        $('#card-element').remove();
        switch (paymentType) {
            case 'Credit Card':
                $('#card_div').html(`<div id="card-element"></div>`);
                stripePaymentFunction();
                $('#credit_card_payment').slideDown();
                break;

            case 'Check':
                $('#check_payment').slideDown();
                break;

            case 'Cash':
            default:
                $('.payment_type_div').slideUp();
                break;
        }
    }
</script>
</html>