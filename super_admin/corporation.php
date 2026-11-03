<?php
require_once('../global/config.php');
global $db;
global $db_account;

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 1) {
    header("location:../login.php");
    exit;
}

$PK_CORPORATION =  (!empty($_GET['id'])) ? $_GET['id'] : 0;

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

$corporation_data = $db->Execute("SELECT * FROM DOA_CORPORATION WHERE PK_CORPORATION = " . $PK_CORPORATION);
if ($corporation_data->RecordCount() > 0) {
    $title = $corporation_data->fields['CORPORATION_NAME'];
} else {
    $title = "Corporation";
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
                                    <li> <a class="nav-link" data-bs-toggle="tab" href="#credit_card" role="tab" id="credit_card_tab" onclick="getSavedCreditCardList();"><span class="hidden-sm-up"><i class="ti-credit-card"></i></span> <span class="hidden-xs-down">Credit Card</span></a> </li>
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
                                                    <th style="text-align: center;">For Location</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $corporation_payments = $db->Execute("SELECT DOA_PAYMENT_DETAILS.*, DOA_LOCATION.LOCATION_NAME FROM DOA_PAYMENT_DETAILS INNER JOIN DOA_LOCATION ON DOA_PAYMENT_DETAILS.PK_LOCATION = DOA_LOCATION.PK_LOCATION WHERE DOA_PAYMENT_DETAILS.PAYMENT_FROM = 'corporation' AND DOA_PAYMENT_DETAILS.PK_CORPORATION = " . $PK_CORPORATION . " ORDER BY DOA_PAYMENT_DETAILS.DATE_TIME DESC");
                                                if ($corporation_payments->RecordCount() > 0) {
                                                    while (!$corporation_payments->EOF) {
                                                        $payment_info = json_decode($corporation_payments->fields['PAYMENT_INFO']);
                                                        $payment_type = (isset($payment_info->LAST4)) ? 'Credit Card' . " # " . $payment_info->LAST4 : $corporation_payments->fields['PAYMENT_INFO']; ?>
                                                        <tr style="color : <?= ($corporation_payments->fields['PAYMENT_STATUS'] == 'Failed') ? 'red' : 'black' ?>">
                                                            <td style="text-align: center;"><?= date('m/d/Y h:i A', strtotime($corporation_payments->fields['DATE_TIME'])) ?></td>
                                                            <td style="text-align: center;"><?= $corporation_payments->fields['PAYMENT_STATUS'] ?></td>
                                                            <td style="text-align: center;">$<?= number_format($corporation_payments->fields['AMOUNT'], 2) ?></td>
                                                            <td style="text-align: center;"><?= $payment_type ?></td>
                                                            <td style="text-align: center;"><?= $corporation_payments->fields['LOCATION_NAME'] ?></td>
                                                        </tr>
                                                    <?php $corporation_payments->MoveNext();
                                                    } ?>
                                                <?php } else { ?>
                                                    <tr>
                                                        <td colspan="5" style="text-align: center;">No payment records found.</td>
                                                    </tr>
                                                <?php } ?>
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="tab-pane p-20" id="credit_card" role="tabpanel">
                                        <form class="form-material form-horizontal" id="credit_card_form" method="post" enctype="multipart/form-data">
                                            <input type="hidden" name="PK_CORPORATION" id="PK_CORPORATION" value="<?= $PK_CORPORATION ?>">
                                            <div class="p-20">
                                                <div class="row">
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
                                                <div class="row" id="corporation_payment_status"></div>

                                                <div class="form-group">
                                                    <button type="submit" id="corporation-pay-button" class="btn btn-info waves-effect waves-light m-r-10 text-white">Process</button>
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

</html>

<script>
    $('.datepicker-past').datepicker({
        format: 'mm/dd/yyyy',
        maxDate: 0
    });

    $('.datepicker-normal').datepicker({
        format: 'mm/dd/yyyy',
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
        $.ajax({
            url: "ajax/get_credit_card_list_from_master.php",
            type: 'POST',
            data: {
                PK_VALUE: '<?= $PK_CORPORATION  ?>',
                class: 'corporation'
            },
            success: function(data) {
                $('#card_list_div').slideDown().html(data);
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

<script>
    function sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    $(document).on('submit', '#credit_card_form', function(event) {
        $('#corporation-pay-button').prop('disabled', true);
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
        let form_data = $('#credit_card_form').serialize();
        $.ajax({
            url: "includes/save_corporation_credit_card.php",
            type: 'POST',
            data: form_data,
            dataType: 'json',
            success: function(data) {
                if (data.STATUS == false) {
                    $('#corporation_payment_status').html(`<p class="alert alert-danger">${data.PAYMENT_INFO}</p>`);
                    $('#corporation-pay-button').prop('disabled', false);
                } else {
                    $('#corporation_payment_status').html(`<p class="alert alert-success">Credit Card Successfully Saved.</p>`);

                    setTimeout(function() {
                        location.reload();
                    }, 3000);
                }
            }
        });
    }
</script>