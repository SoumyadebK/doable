<?php
require_once('../global/config.php');
require_once("../global/stripe-php/init.php");

global $db;
global $db_account;
global $upload_path;
global $AMI_ENABLE;

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 1) {
    header("location:../login.php");
    exit;
}

$PK_LOCATION = $_GET['id'];

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

$location_data = $db->Execute("SELECT * FROM DOA_LOCATION WHERE PK_LOCATION = " . $PK_LOCATION);
if ($location_data->RecordCount() > 0) {
    $title = $location_data->fields['LOCATION_NAME'];
    $PK_ACCOUNT_MASTER = $location_data->fields['PK_ACCOUNT_MASTER'];
    $PK_CORPORATION = $location_data->fields['PK_CORPORATION'];
    $START_DATE = $location_data->fields['CREATED_ON'];
    $ACTIVE = $location_data->fields['ACTIVE'];
    $PAYMENT_FROM = $location_data->fields['PAYMENT_FROM'];
    $FRANCHISE = $location_data->fields['FRANCHISE'];

    $account_data = $db->Execute("SELECT * FROM `DOA_ACCOUNT_MASTER` WHERE `PK_ACCOUNT_MASTER`  = " . $PK_ACCOUNT_MASTER);

    $RENEWAL_INTERVAL = $account_data->fields['RENEWAL_INTERVAL'];

    $AM_AMOUNT = $account_data->fields['AM_AMOUNT'];
    $NOT_AM_AMOUNT = $account_data->fields['NOT_AM_AMOUNT'];

    if (($AM_AMOUNT == '' || $AM_AMOUNT == 0.00) && ($NOT_AM_AMOUNT == '' || $NOT_AM_AMOUNT == 0.00)) {
        $res = $db->Execute("SELECT * FROM `DOA_OTHER_SETTING`");
        if ($res->RecordCount() > 0) {
            $AM_AMOUNT       = $res->fields['AM_AMOUNT'];
            $NOT_AM_AMOUNT   = $res->fields['NOT_AM_AMOUNT'];
        }
    }
    $AMOUNT = ($FRANCHISE == 1) ? $AM_AMOUNT : $NOT_AM_AMOUNT;
} else {
    $title = "Location";
}
?>

<!DOCTYPE html>
<html lang="en">
<?php require_once('../includes/header.php'); ?>
<style>
    #advice-required-entry-ACCEPT_HANDLING {
        width: 150px;
        top: 20px;
        position: absolute;
    }

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
<style>
    /* Compact toggle switch for font-size 14px */
    .switch {
        position: relative;
        display: inline-block;
        width: 42px;
        /* Reduced from 60px */
        height: 22px;
        /* Reduced from 30px */
        vertical-align: middle;
        /* Better alignment with text */
        margin: 0 5px;
        /* Add some spacing */
    }

    .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }


    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        transition: .4s;
        border-radius: 22px;
        /* Adjusted to match new height */
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 18px;
        /* Reduced from 26px */
        width: 18px;
        /* Reduced from 26px */
        left: 2px;
        /* Adjusted positioning */
        bottom: 2px;
        /* Adjusted positioning */
        background-color: white;
        transition: .4s;
        border-radius: 50%;
    }

    input:checked+.slider {
        background-color: #39B54A;
    }

    input:checked+.slider:before {
        transform: translateX(20px);
        /* Adjusted for new width */
    }

    /* Focus state for accessibility */
    input:focus+.slider {
        box-shadow: 0 0 0 2px rgba(57, 181, 74, 0.3);
    }

    /* Optional: Add transition for smooth toggle */
    .switch * {
        transition: all 0.3s ease;
    }
</style>

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
                                <li class="breadcrumb-item"><a href="all_accounts.php">All Accounts</a></li>
                                <!-- <li class="breadcrumb-item"><a href="all_corporations.php">All Corporations</a></li> -->
                                <li class="breadcrumb-item active"><?= $title ?></li>
                            </ol>

                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <!-- Nav tabs -->
                                <ul class="nav nav-tabs" role="tablist">
                                    <li class="active"> <a class="nav-link active" id="payment_register_tab_link" data-bs-toggle="tab" href="#payment_register" role="tab"><span class="hidden-sm-up"><i class="ti-receipt"></i></span> <span class="hidden-xs-down">Payment Register</span></a> </li>
                                    <li> <a class="nav-link" data-bs-toggle="tab" href="#billing" role="tab" id="billing_tab"><span class="hidden-sm-up"><i class="ti-credit-card"></i></span> <span class="hidden-xs-down">Billing</span></a> </li>
                                </ul>

                                <!-- Tab panes -->
                                <div class="tab-content tabcontent-border">
                                    <div class="tab-pane p-20 active" id="payment_register" role="tabpanel" style="margin-top: 15px;">
                                        <h4 style="text-align: center; margin-bottom: 20px;">Payment History</h4>
                                        <table id="payment_table" class="table table-striped border">
                                            <thead>
                                                <tr>
                                                    <th style="text-align: center;">Date</th>
                                                    <th style="text-align: center;">Status</th>
                                                    <th style="text-align: center;">Amount</th>
                                                    <th style="text-align: center;">Info</th>
                                                    <th style="text-align: center;">Details</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $location_payments = $db->Execute("SELECT * FROM DOA_PAYMENT_DETAILS WHERE PK_LOCATION = " . $PK_LOCATION . " ORDER BY DATE_TIME DESC");
                                                if ($location_payments->RecordCount() > 0) {
                                                    while (!$location_payments->EOF) {
                                                        $payment_info = json_decode($location_payments->fields['PAYMENT_INFO']);
                                                        $payment_type = (isset($payment_info->LAST4)) ? 'Credit Card' . " # " . $payment_info->LAST4 : $location_payments->fields['PAYMENT_INFO']; ?>
                                                        <tr style="color : <?= ($location_payments->fields['PAYMENT_STATUS'] == 'Failed') ? 'red' : 'black' ?>">
                                                            <td style="text-align: center;"><?= date('m/d/Y h:i A', strtotime($location_payments->fields['DATE_TIME'])) ?></td>
                                                            <td style="text-align: center;"><?= $location_payments->fields['PAYMENT_STATUS'] ?></td>
                                                            <td style="text-align: center;">$<?= number_format($location_payments->fields['AMOUNT'], 2) ?></td>
                                                            <td style="text-align: center;"><?= $payment_type ?></td>
                                                            <td style="text-align: center;">
                                                                <?php if ($location_payments->fields['PAYMENT_FROM'] == 'corporation') {
                                                                    echo 'Payment from Corporation';
                                                                } else {
                                                                    echo 'Payment from Location';
                                                                } ?>
                                                            </td>
                                                        </tr>
                                                    <?php $location_payments->MoveNext();
                                                    } ?>
                                                <?php } else { ?>
                                                    <tr>
                                                        <td colspan="5" style="text-align: center;">No payment records found.</td>
                                                    </tr>
                                                <?php } ?>
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="tab-pane" id="billing" role="tabpanel" style="margin-top: 20px;">
                                        <div class="row">
                                            <form class="form-material form-horizontal" id="location_payment_form" method="post" enctype="multipart/form-data">
                                                <input type="hidden" class="PK_ACCOUNT_MASTER" name="PK_ACCOUNT_MASTER" value="<?= $PK_ACCOUNT_MASTER ?>">
                                                <input type="hidden" class="PK_LOCATION" name="PK_LOCATION" value="<?= $PK_LOCATION ?>">
                                                <input type="hidden" class="PK_CORPORATION" name="PK_CORPORATION" value="<?= $PK_CORPORATION ?>">
                                                <div class="p-20">
                                                    <div class="row">
                                                        <div class="col-5">
                                                            <div class="form-group">
                                                                <label class="col-md-12">Subscription Start Date</label>
                                                                <div class="col-md-12">
                                                                    <input type="text" class="form-control datepicker-normal" name="START_DATE" id="START_DATE" value="<?= ($START_DATE == '') ? '' : date('m/d/Y', strtotime($START_DATE)) ?>" disabled>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-5">
                                                            <div class="form-group">
                                                                <label class="col-md-12">Next Renewal Date</label>
                                                                <div class="col-md-12">
                                                                    <input type="text" class="form-control datepicker-normal" name="NEXT_RENEWAL_DATE" id="NEXT_RENEWAL_DATE" value="<?= ($START_DATE == '') ? '' : (($RENEWAL_INTERVAL == 'monthly') ? date('m/d/Y', strtotime('+1 month', strtotime($START_DATE))) : date('m/d/Y', strtotime('+1 year', strtotime($START_DATE)))) ?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-2">
                                                            <div class="form-group">
                                                                <label class="col-md-12">Status</label>
                                                                <div class="col-md-12">
                                                                    <select class="form-control" name="ACTIVE" id="ACTIVE">
                                                                        <option value="1" <?= ($ACTIVE == 1) ? 'selected' : '' ?>>Active</option>
                                                                        <option value="0" <?= ($ACTIVE == 0) ? 'selected' : '' ?>>Inactive</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row" style="margin-bottom: 15px;">
                                                        <div class="col-12">
                                                            <div class="form-group">
                                                                <label class="form-label" style="margin-bottom: 10px;">Payment From</label><br>
                                                                <label style="margin-right: 30px;"><input type="radio" name="PAYMENT_FROM" class="PAYMENT_FROM" value="location" <?= ($PAYMENT_FROM == 'location') ? 'checked' : '' ?> onclick="changePaymentFrom(this)" />&nbsp;Location</label>&nbsp;&nbsp;
                                                                <label style="margin-right: 30px;"><input type="radio" name="PAYMENT_FROM" class="PAYMENT_FROM" value="corporation" <?= ($PAYMENT_FROM == 'corporation') ? 'checked' : '' ?> onclick="changePaymentFrom(this)" />&nbsp;Corporation</label>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-12">
                                                            <div class="form-group">
                                                                <label class="col-md-12">Amount</label>
                                                                <div class="col-md-12">
                                                                    <input type="text" class="form-control" name="AMOUNT" value="<?= $AMOUNT ?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <input type="hidden" name="PAYMENT_METHOD_ID" id="PAYMENT_METHOD_ID" value="">
                                                    <div id="payment_details_div" style="display: <?= ($PAYMENT_FROM == 'location') ? '' : 'none' ?>;">
                                                        <div class="row">
                                                            <div class="col-12">
                                                                <?php if ($SA_PAYMENT_GATEWAY_TYPE == 'Stripe') { ?>
                                                                    <input type="hidden" name="stripe_token" id="stripe_token" value="">
                                                                    <div class="row">
                                                                        <div class="col-12">
                                                                            <div class="form-group" id="card_div">

                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row" id="card_list_div">
                                                                    </div>
                                                                <?php } elseif ($SA_PAYMENT_GATEWAY_TYPE == 'Square') { ?>
                                                                    <input type="hidden" name="square_token" id="square_token" value="">
                                                                    <div class="row">
                                                                        <div class="col-12">
                                                                            <div id="payment-card-container"></div>
                                                                            <div id="payment-status-container"></div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row" id="card_list_div">
                                                                    </div>
                                                                <?php } ?>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div id="corporation_card_div" style="display: <?= ($PAYMENT_FROM == 'corporation') ? '' : 'none' ?>;">
                                                        <div class="row">
                                                            <div class="col-12">
                                                                <div class="row" id="corporation_card_list">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row" id="location_payment_status"></div>

                                                    <div class="form-group">
                                                        <button type="submit" id="location-payment-btn" class="btn btn-info waves-effect waves-light m-r-10 text-white">Process</button>
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
        </div>
    </div>

    <?php require_once('../includes/footer.php'); ?>
</body>

<script>
    $(document).ready(function() {
        $('.PAYMENT_FROM').trigger('click');
    });

    $('.datepicker-normal').datepicker({
        format: 'mm/dd/yyyy',
    });


    function changePaymentFrom(param) {
        if ($(param).val() == 'corporation') {
            $('#payment_details_div').slideUp();
            $('#corporation_card_div').slideDown();
            getCorporationSavedCreditCardList();
        } else {
            $('#corporation_card_div').slideUp();
            $('#payment_details_div').slideDown();
            getSavedCreditCardList();
        }
    }

    function getSavedCreditCardList() {
        let payment_gateway_type = '<?= $SA_PAYMENT_GATEWAY_TYPE ?>';
        if (payment_gateway_type == 'Square') {
            squarePaymentFunction();
        } else if (payment_gateway_type == 'Stripe') {
            stripePaymentFunction();
        }
        $.ajax({
            url: "ajax/get_credit_card_list_from_master.php",
            type: 'POST',
            data: {
                PK_VALUE: '<?= $PK_LOCATION ?>',
                class: 'location'
            },
            success: function(data) {
                $('#card_list_div').slideDown().html(data);
            }
        });
    }

    function getCorporationSavedCreditCardList() {
        $.ajax({
            url: "ajax/get_credit_card_list_from_master.php",
            type: 'POST',
            data: {
                PK_VALUE: '<?= $PK_CORPORATION ?>',
                class: 'corporation'
            },
            success: function(data) {
                $('#corporation_card_list').slideDown().html(data);
            }
        });
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


<script type="text/javascript">
    function sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    function getPaymentMethodId(param) {
        $('.credit-card-div').css("opacity", "1");
        $('#PAYMENT_METHOD_ID').val($(param).attr('id'));
        $(param).css("opacity", "0.6");
    }

    $(document).on('submit', '#location_payment_form', function(event) {
        event.preventDefault();
        $('#location-payment-btn').prop('disabled', true);
        let PAYMENT_GATEWAY = '<?= $SA_PAYMENT_GATEWAY_TYPE ?>';
        if (PAYMENT_GATEWAY == 'Square') {
            let PAYMENT_METHOD_ID = $('#PAYMENT_METHOD_ID').val();
            if (PAYMENT_METHOD_ID == '') {
                addSquareTokenOnForm();
                sleep(3000).then(() => {
                    submitLocationPaymentForm();
                });
            } else {
                submitLocationPaymentForm();
            }
        } else {
            submitLocationPaymentForm();
        }
    });

    function submitLocationPaymentForm() {
        let form_data = $('#location_payment_form').serialize();
        $.ajax({
            url: "includes/process_location_payment.php",
            type: 'POST',
            data: form_data,
            dataType: 'json',
            success: function(data) {
                if (data.STATUS === 'Failed') {
                    $('#location_payment_status').html(`<p class="alert alert-danger">${data.PAYMENT_INFO}</p>`);
                    $('#location-payment-btn').prop('disabled', false);
                } else {
                    $('#location_payment_status').html(`<p class="alert alert-success">Payment Successful, Page will refresh automatically.</p>`);

                    setTimeout(function() {
                        location.reload();
                    }, 3000);
                }
            }
        });
    }

    $('#payment_table').DataTable({
        order: [
            [0, 'desc']
        ],
        columnDefs: [{
            type: 'date',
            targets: 0
        }],
    });
</script>

</html>