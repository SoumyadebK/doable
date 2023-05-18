<?php
require_once('../global/config.php');
$title = "Billing";

$user_master_data = $account = $db->Execute("SELECT * FROM DOA_USER_MASTER WHERE PK_USER = ".$_SESSION['PK_USER']);
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

$query = $db->Execute("SELECT count(DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS TOTAL_RECORDS FROM `DOA_ENROLLMENT_MASTER` INNER JOIN DOA_LOCATION ON DOA_LOCATION.PK_LOCATION = DOA_ENROLLMENT_MASTER.PK_LOCATION  WHERE DOA_ENROLLMENT_MASTER.PK_USER_MASTER IN (".$PK_USER_MASTERS.")".$search);
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
                require_once("../global/stripe/init.php");
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
        db_perform('DOA_ENROLLMENT_PAYMENT', $PAYMENT_DATA, 'insert');

        $enrollment_balance = $db->Execute("SELECT * FROM `DOA_ENROLLMENT_BALANCE` WHERE PK_ENROLLMENT_MASTER = '$_POST[PK_ENROLLMENT_MASTER]'");
        if ($enrollment_balance->RecordCount() > 0){
            $ENROLLMENT_BALANCE_DATA['TOTAL_BALANCE_PAID'] = $enrollment_balance->fields['TOTAL_BALANCE_PAID']+$_POST['AMOUNT'];
            $ENROLLMENT_BALANCE_DATA['EDITED_BY']	= $_SESSION['PK_USER'];
            $ENROLLMENT_BALANCE_DATA['EDITED_ON'] = date("Y-m-d H:i");
            db_perform('DOA_ENROLLMENT_BALANCE', $ENROLLMENT_BALANCE_DATA, 'update'," PK_ENROLLMENT_MASTER =  '$_POST[PK_ENROLLMENT_MASTER]'");
        }else{
            $ENROLLMENT_BALANCE_DATA['PK_ENROLLMENT_MASTER'] = $_POST['PK_ENROLLMENT_MASTER'];
            $ENROLLMENT_BALANCE_DATA['TOTAL_BALANCE_PAID'] = $_POST['AMOUNT'];
            $ENROLLMENT_BALANCE_DATA['CREATED_BY']  = $_SESSION['PK_USER'];
            $ENROLLMENT_BALANCE_DATA['CREATED_ON']  = date("Y-m-d H:i");
            db_perform('DOA_ENROLLMENT_BALANCE', $ENROLLMENT_BALANCE_DATA, 'insert');
        }

        $PK_ENROLLMENT_PAYMENT = $db->insert_ID();
        $ledger_record = $db->Execute("SELECT * FROM `DOA_ENROLLMENT_LEDGER` WHERE PK_ENROLLMENT_LEDGER =  '$PK_ENROLLMENT_LEDGER'");
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
        db_perform('DOA_ENROLLMENT_LEDGER', $LEDGER_DATA, 'insert');
        $LEDGER_UPDATE_DATA['IS_PAID'] = 1;
        db_perform('DOA_ENROLLMENT_LEDGER', $LEDGER_UPDATE_DATA, 'update', "PK_ENROLLMENT_LEDGER =  '$PK_ENROLLMENT_LEDGER'");
    }else{
        db_perform('DOA_ENROLLMENT_PAYMENT', $_POST, 'update'," PK_ENROLLMENT_PAYMENT =  '$_POST[PK_ENROLLMENT_PAYMENT]'");
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
        <div class="container-fluid">
            <div class="row page-titles">
                <div class="col-md-5 align-self-center">
                    <h4 class="text-themecolor"><?=$title?></h4>
                </div>
                <div class="col-md-7 align-self-center text-end">
                    <div class="d-flex justify-content-end align-items-center">
                        <ol class="breadcrumb justify-content-end">
                            <li class="breadcrumb-item active"><?=$title?></li>
                        </ol>
                        <!--<button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='add_schedule.php'" ><i class="fa fa-plus-circle"></i> Create New</button>-->
                    </div>
                </div>
            </div>

            <div class="row">
                <div id="appointment_list_half" class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6">
                                    <h5 class="card-title"><?=$title?></h5>
                                </div>
                            </div>
                            <div class="p-20">
                                <?php
                                $i=$page_first_result+1;
                                $row = $db->Execute("SELECT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, DOA_ENROLLMENT_MASTER.ENROLLMENT_ID, DOA_ENROLLMENT_MASTER.ACTIVE, DOA_LOCATION.LOCATION_NAME FROM `DOA_ENROLLMENT_MASTER` INNER JOIN DOA_LOCATION ON DOA_LOCATION.PK_LOCATION = DOA_ENROLLMENT_MASTER.PK_LOCATION  WHERE DOA_ENROLLMENT_MASTER.PK_USER_MASTER IN (".$PK_USER_MASTERS.")".$search."ORDER BY DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER DESC"." LIMIT " . $page_first_result . ',' . $results_per_page);
                                while (!$row->EOF) {
                                    $total_bill_and_paid = $db->Execute("SELECT SUM(BILLED_AMOUNT) AS TOTAL_BILL, SUM(PAID_AMOUNT) AS TOTAL_PAID FROM DOA_ENROLLMENT_LEDGER WHERE `PK_ENROLLMENT_MASTER`=".$row->fields['PK_ENROLLMENT_MASTER']);
                                    ?>
                                    <div class="row" onclick="$(this).next().slideToggle()" style="cursor:pointer; font-size: 15px; *border: 1px solid #ebe5e2; padding: 8px;">
                                        <div class="col-3" style="width: 18%"><span class="hidden-sm-up" style="margin-right: 20px;"><i class="ti-arrow-circle-right"></i></span></i> <?=$row->fields['ENROLLMENT_ID']?></div>
                                        <div class="col-3" style="width: 18%"><?=$row->fields['LOCATION_NAME']?></div>
                                        <div class="col-2" style="width: 20%">Total Billed : <?=$total_bill_and_paid->fields['TOTAL_BILL'];?></div>
                                        <div class="col-2" style="width: 20%">Total Paid : <?=$total_bill_and_paid->fields['TOTAL_PAID'];?></div>
                                        <div class="col-2" style="width: 20%">Balance : <?=$total_bill_and_paid->fields['TOTAL_BILL']-$total_bill_and_paid->fields['TOTAL_PAID'];?></div>
                                    </div>
                                    <table id="myTable" class="table table-striped border" style="display: none">
                                        <thead>
                                        <tr>
                                            <th>Due Date</th>
                                            <th>Transaction Type</th>
                                            <th>Billed Amount</th>
                                            <th>Paid Amount</th>
                                            <th>Payment Type</th>
                                            <th>Description</th>
                                            <th>Balance</th>
                                            <th>Paid</th>
                                            <th>Actions</th>
                                        </tr>
                                        </thead>

                                        <tbody>
                                        <?php
                                        $billed_amount = 0;
                                        $paid_amount = 0;
                                        $balance = 0;
                                        $billing_details = $db->Execute("SELECT DOA_ENROLLMENT_LEDGER.*, DOA_PAYMENT_TYPE.PAYMENT_TYPE FROM `DOA_ENROLLMENT_LEDGER` LEFT JOIN DOA_PAYMENT_TYPE ON DOA_ENROLLMENT_LEDGER.PK_PAYMENT_TYPE = DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE WHERE PK_ENROLLMENT_MASTER = ".$row->fields['PK_ENROLLMENT_MASTER']." AND ENROLLMENT_LEDGER_PARENT = 0 ORDER BY DUE_DATE ASC, PK_ENROLLMENT_LEDGER ASC");
                                        while (!$billing_details->EOF) { $billed_amount = $billing_details->fields['BILLED_AMOUNT']; $balance = ($billing_details->fields['BILLED_AMOUNT'] + $balance); ?>
                                            <tr>
                                                <td><?=date('m/d/Y', strtotime($billing_details->fields['DUE_DATE']))?></td>
                                                <td><?=$billing_details->fields['TRANSACTION_TYPE']?></td>
                                                <td><?=$billing_details->fields['BILLED_AMOUNT']?></td>
                                                <td></td>
                                                <td><?=$billing_details->fields['PAYMENT_TYPE']?></td>
                                                <td></td>
                                                <td><?=number_format((float)$balance, 2, '.', '')?></td>
                                                <td><?=(($billing_details->fields['TRANSACTION_TYPE']=='Billing')?(($billing_details->fields['IS_PAID']==1)?'YES':'NO'):'')?></td>
                                                <td>
                                                    <?php if($billing_details->fields['IS_PAID']==0) { ?>
                                                        <a href="javascript:;" class="btn btn-info waves-effect waves-light m-r-10 text-white" onclick="payNow(<?=$row->fields['PK_ENROLLMENT_MASTER']?>, <?=$billing_details->fields['PK_ENROLLMENT_LEDGER']?>, <?=$billing_details->fields['BILLED_AMOUNT']?>);">Pay Now</a>
                                                    <?php } ?>
                                                </td>
                                            </tr>
                                            <?php
                                            $payment_details = $db->Execute("SELECT DOA_ENROLLMENT_LEDGER.*, DOA_PAYMENT_TYPE.PAYMENT_TYPE FROM `DOA_ENROLLMENT_LEDGER` LEFT JOIN DOA_PAYMENT_TYPE ON DOA_ENROLLMENT_LEDGER.PK_PAYMENT_TYPE = DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE WHERE ENROLLMENT_LEDGER_PARENT = ".$billing_details->fields['PK_ENROLLMENT_LEDGER']);
                                            if ($payment_details->RecordCount() > 0){ $balance = ($billed_amount - $payment_details->fields['PAID_AMOUNT']); ?>
                                                <tr>
                                                    <td><?=date('m/d/Y', strtotime($payment_details->fields['DUE_DATE']))?></td>
                                                    <td><?=$payment_details->fields['TRANSACTION_TYPE']?></td>
                                                    <td></td>
                                                    <td><?=$payment_details->fields['PAID_AMOUNT']?></td>
                                                    <td><?=$payment_details->fields['PAYMENT_TYPE']?></td>
                                                    <td></td>
                                                    <td><?=number_format((float)$balance, 2, '.', '')?></td>
                                                    <td><?=(($payment_details->fields['TRANSACTION_TYPE']=='Billing')?(($payment_details->fields['IS_PAID']==1)?'YES':'NO'):'')?></td>
                                                    <td>
                                                    </td>
                                                </tr>
                                            <? } ?>
                                            <?php $billing_details->MoveNext(); } ?>
                                        </tbody>
                                    </table>
                                    <?php $row->MoveNext();
                                    $i++; } ?>

                                <div class="center">
                                    <div class="pagination outer">
                                        <ul>
                                            <?php if ($page > 1) { ?>
                                                <li><a href="javascript:;" onclick="showBillingList(<?=($page-1)?>)">&laquo;</a></li>
                                            <?php }
                                            for($page_count = 1; $page_count<=$number_of_page; $page_count++) {
                                                echo '<li><a class="'.(($page_count==$page)?"active":"").'" href="javascript:;" onclick="showBillingList('.$page_count.')">' . $page_count . ' </a></li>';
                                            }
                                            if ($page < $number_of_page) { ?>
                                                <li><a href="javascript:;" onclick="showBillingList(<?=($page+1)?>)">&raquo;</a></li>
                                            <?php } ?>
                                        </ul>
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

    function payNow(PK_ENROLLMENT_MASTER, PK_ENROLLMENT_LEDGER, BILLED_AMOUNT) {
        $('.PK_ENROLLMENT_MASTER').val(PK_ENROLLMENT_MASTER);
        $('.PK_ENROLLMENT_LEDGER').val(PK_ENROLLMENT_LEDGER);
        $('#AMOUNT_TO_PAY').val(BILLED_AMOUNT);
        $('#payment_confirmation_form_div').slideDown();
        window.scrollTo(0, document.body.scrollHeight);
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