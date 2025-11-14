<?php
require_once('../../global/config.php');
require_once("../../global/stripe-php-master/init.php");
global $db;
global $db_account;
global $master_database;

use Square\Models\Address;
use Square\SquareClient;
use Square\Environment;

require_once('../../global/authorizenet/autoload.php');

use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

$PK_LOCATION = $_POST['PK_LOCATION'];

$payment_gateway_data = $db->Execute("SELECT * FROM `DOA_PAYMENT_GATEWAY_SETTINGS`");

$PAYMENT_GATEWAY = $payment_gateway_data->fields['PAYMENT_GATEWAY_TYPE'];
$GATEWAY_MODE  = $payment_gateway_data->fields['GATEWAY_MODE'];

$SECRET_KEY = $payment_gateway_data->fields['SECRET_KEY'];
$PUBLISHABLE_KEY = $payment_gateway_data->fields['PUBLISHABLE_KEY'];

$SQUARE_ACCESS_TOKEN = $payment_gateway_data->fields['ACCESS_TOKEN'];
$SQUARE_APP_ID = $payment_gateway_data->fields['APP_ID'];
$SQUARE_LOCATION_ID = $payment_gateway_data->fields['LOCATION_ID'];

if ($PAYMENT_GATEWAY == "Stripe") {
    $location_payment_info = $db->Execute("SELECT * FROM `DOA_PAYMENT_INFO` WHERE PAYMENT_TYPE = 'Stripe' AND CLASS = 'location' AND PK_VALUE = '$PK_LOCATION'");
    if ($SECRET_KEY != '' && $location_payment_info->RecordCount() > 0) {
        $stripe = new \Stripe\StripeClient($SECRET_KEY);
        $CUSTOMER_PAYMENT_ID = $location_payment_info->fields['PAYMENT_ID'];

        $all_cards = $stripe->customers->allSources(
            $CUSTOMER_PAYMENT_ID,
            ['object' => 'card']
        );

        foreach ($all_cards->data as $card_details) {
            $card_type = getCardTypeDetails($card_details->brand); ?>

            <div style="position: relative; display: inline-block;">
                <!-- Credit Card Box -->
                <div class="credit-card-div" id="<?= $card_details->id; ?>" onclick="getPaymentMethodId(this)">
                    <div class="credit-card <?= $card_type ?> selectable">
                        <div class="credit-card-last4">
                            <?= $card_details->last4 ?>
                        </div>
                        <div class="credit-card-expiry">
                            <?= $card_details->exp_month . '/' . $card_details->exp_year ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php }
    }
} elseif ($PAYMENT_GATEWAY == "Square") {
    $location_payment_info = $db->Execute("SELECT * FROM `DOA_PAYMENT_INFO` WHERE PAYMENT_TYPE = 'Square' AND CLASS = 'location' AND PK_VALUE = '$PK_LOCATION'");
    if ($location_payment_info->RecordCount() > 0) {
        require_once("../../global/vendor/autoload.php");

        if ($GATEWAY_MODE == 'live') {
            $client = new SquareClient([
                'accessToken' => $SQUARE_ACCESS_TOKEN,
                'environment' => Environment::PRODUCTION,
            ]);
        } else {
            $client = new SquareClient([
                'accessToken' => $SQUARE_ACCESS_TOKEN,
                'environment' => Environment::SANDBOX,
            ]);
        }

        $CUSTOMER_PAYMENT_ID = $location_payment_info->fields['PAYMENT_ID'];
        try {
            $api_response = $client->getCardsApi()->listCards(null, $CUSTOMER_PAYMENT_ID, 'DESC');
            $all_cards = $api_response->getResult()->getCards();
        } catch (Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }

        foreach ($all_cards as $card_details) {
            $card_type = getCardTypeDetails($card_details->getCardBrand()); ?>

            <div style="position: relative; display: inline-block;">
                <!-- Credit Card Box -->
                <div class="credit-card-div" id="<?= $card_details->getId(); ?>" onclick="getPaymentMethodId(this)">
                    <div class="credit-card <?= $card_type ?> selectable">
                        <div class="credit-card-last4">
                            <?= $card_details->getLast4() ?>
                        </div>
                        <div class="credit-card-expiry">
                            <?= $card_details->getExpMonth() . '/' . $card_details->getExpYear() ?>
                        </div>
                    </div>
                </div>
            </div>
<?php   }
    }
}  ?>
<?php
function getCardTypeDetails($brand)
{
    $card_type = '';
    $brand = strtolower($brand);
    switch ($brand) {
        case 'visa':
        case 'visa (debit)':
            $card_type = 'visa';
            break;
        case 'mastercard':
        case 'mastercard (2-series)':
        case 'mastercard (debit)':
        case 'mastercard (prepaid)':
            $card_type = 'mastercard';
            break;
        case 'american express':
            $card_type = 'amex';
            break;
        case 'discover':
        case 'discover (debit)':
            $card_type = 'discover';
            break;
        case 'diners club':
        case 'diners club (14-digit card)':
            $card_type = 'diners';
            break;
        case 'jcb':
            $card_type = 'jcb';
            break;
        case 'unionpay':
        case 'unionpay (debit)':
        case 'unionpay (19-digit card)':
            $card_type = 'unionpay';
            break;
        default:
            $card_type = '';
            break;
    }
    return $card_type;
}
?>