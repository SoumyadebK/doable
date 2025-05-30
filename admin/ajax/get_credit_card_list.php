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

/* $account_data = $db->Execute("SELECT * FROM `DOA_ACCOUNT_MASTER` WHERE `PK_ACCOUNT_MASTER` = '$_SESSION[PK_ACCOUNT_MASTER]'");
$ACCESS_TOKEN = $account_data->fields['ACCESS_TOKEN'];
$PAYMENT_GATEWAY = $_POST['PAYMENT_GATEWAY']; */

$payment_gateway_data = getPaymentGatewayData();

$PAYMENT_GATEWAY = $payment_gateway_data->fields['PAYMENT_GATEWAY_TYPE'];
$GATEWAY_MODE  = $payment_gateway_data->fields['GATEWAY_MODE'];

$SECRET_KEY = $payment_gateway_data->fields['SECRET_KEY'];
$PUBLISHABLE_KEY = $payment_gateway_data->fields['PUBLISHABLE_KEY'];

$SQUARE_ACCESS_TOKEN = $payment_gateway_data->fields['ACCESS_TOKEN'];
$SQUARE_APP_ID = $payment_gateway_data->fields['APP_ID'];
$SQUARE_LOCATION_ID = $payment_gateway_data->fields['LOCATION_ID'];

$AUTHORIZE_LOGIN_ID         = $payment_gateway_data->fields['LOGIN_ID']; //"4Y5pCy8Qr";
$AUTHORIZE_TRANSACTION_KEY     = $payment_gateway_data->fields['TRANSACTION_KEY']; //"4ke43FW8z3287HV5";
$AUTHORIZE_CLIENT_KEY         = $payment_gateway_data->fields['AUTHORIZE_CLIENT_KEY']; //"8ZkyJnT87uFztUz56B4PfgCe7yffEZA4TR5dv8ALjqk5u9mr6d8Nmt8KHyp8s9Ay";

$MERCHANT_ID            = $payment_gateway_data->fields['MERCHANT_ID'];
$API_KEY                = $payment_gateway_data->fields['API_KEY'];
$PUBLIC_API_KEY         = $payment_gateway_data->fields['PUBLIC_API_KEY'];

if ($PAYMENT_GATEWAY == "Stripe") {
    $customer_payment_info = $db_account->Execute("SELECT DOA_CUSTOMER_PAYMENT_INFO.CUSTOMER_PAYMENT_ID FROM DOA_CUSTOMER_PAYMENT_INFO INNER JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_USER_MASTER.PK_USER = DOA_CUSTOMER_PAYMENT_INFO.PK_USER WHERE PAYMENT_TYPE = 'Stripe' AND PK_USER_MASTER = '$_POST[PK_USER_MASTER]'");
    if ($SECRET_KEY != '' && $customer_payment_info->RecordCount() > 0) {
        $stripe = new \Stripe\StripeClient($SECRET_KEY);
        $CUSTOMER_PAYMENT_ID = $customer_payment_info->fields['CUSTOMER_PAYMENT_ID'];

        $all_cards = $stripe->customers->allSources(
            $CUSTOMER_PAYMENT_ID,
            ['object' => 'card']
        );

        foreach ($all_cards->data as $card_details) {
            $card_type = getCardTypeDetails($card_details->brand);
?>
            <div class="credit-card-div" id="<?php echo $card_details->id; ?>" onclick="getPaymentMethodId(this)" style="width: 303px;">
                <div class="credit-card <?= $card_type ?> selectable">
                    <div class="credit-card-last4">
                        <?= $card_details->last4 ?>
                    </div>
                    <div class="credit-card-expiry">
                        <?= $card_details->exp_month . '/' . $card_details->exp_year ?>
                    </div>
                </div>
            </div>
<?php }
    }
} elseif ($PAYMENT_GATEWAY == "Square") {
    $user_payment_info_data = $db_account->Execute("SELECT DOA_CUSTOMER_PAYMENT_INFO.CUSTOMER_PAYMENT_ID FROM DOA_CUSTOMER_PAYMENT_INFO INNER JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_USER_MASTER.PK_USER = DOA_CUSTOMER_PAYMENT_INFO.PK_USER WHERE PAYMENT_TYPE = 'Square' AND PK_USER_MASTER = '$_POST[PK_USER_MASTER]'");

    if ($user_payment_info_data->RecordCount() > 0) {
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

        $CUSTOMER_PAYMENT_ID = $user_payment_info_data->fields['CUSTOMER_PAYMENT_ID'];
        try {
            $card = new \Square\Models\Card();
            $card->setCustomerId($CUSTOMER_PAYMENT_ID);
            $all_payment_methods = $client->getCardsApi()->listCards();
            $all_payment_methods_array = json_decode($all_payment_methods->getBody());
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }

        $card_list = '';
        foreach ($all_payment_methods_array->cards as $all_payment_methods_data) {
            $card_type = getCardTypeDetails($all_payment_methods_data->card_brand);

            $card_list .= '<div class="credit-card-div" id="' . $all_payment_methods_data->id . '" onclick="getPaymentMethodId(this)" style="width: 303px;">
                            <div class="credit-card ' . $card_type . ' selectable">
                                <div class="credit-card-last4">
                                    ' . $all_payment_methods_data->last_4 . '
                                </div>
                                <div class="credit-card-expiry">
                                    ' . $all_payment_methods_data->exp_month . '/' . $all_payment_methods_data->exp_year . '
                                </div>
                            </div>
                        </div>';
        }
        echo $card_list;
    }
} elseif ($PAYMENT_GATEWAY == 'Authorized.net') {
    $user_payment_info_data = $db_account->Execute("SELECT DOA_CUSTOMER_PAYMENT_INFO.CUSTOMER_PAYMENT_ID FROM DOA_CUSTOMER_PAYMENT_INFO INNER JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_USER_MASTER.PK_USER = DOA_CUSTOMER_PAYMENT_INFO.PK_USER WHERE PAYMENT_TYPE = 'Authorized.net' AND PK_USER_MASTER = '$_POST[PK_USER_MASTER]'");

    if ($user_payment_info_data->RecordCount() > 0) {
        // Your API credentials
        $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
        $merchantAuthentication->setName($AUTHORIZE_LOGIN_ID);
        $merchantAuthentication->setTransactionKey($AUTHORIZE_TRANSACTION_KEY);

        // Customer Profile ID (from your DB or after creating profile)
        $customerProfileId = $user_payment_info_data->fields['CUSTOMER_PAYMENT_ID'];

        // Prepare the request
        $request = new AnetAPI\GetCustomerProfileRequest();
        $request->setMerchantAuthentication($merchantAuthentication);
        $request->setCustomerProfileId($customerProfileId);

        // Execute the API call
        $controller = new AnetController\GetCustomerProfileController($request);

        if ($GATEWAY_MODE == 'live')
            $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::PRODUCTION);
        else
            $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::SANDBOX);

        //$response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::SANDBOX); // or PRODUCTION

        // Handle response
        if (($response != null) && ($response->getMessages()->getResultCode() == "Ok")) {
            $paymentProfiles = $response->getProfile()->getPaymentProfiles();

            $card_list = '';
            foreach ($paymentProfiles as $profile) {
                $card = $profile->getPayment()->getCreditCard();
                $card_type = getCardTypeDetails($card->getCardType());

                $card_list .= '<div class="credit-card-div" id="' . $profile->getCustomerPaymentProfileId() . '" onclick="getPaymentMethodId(this)" style="width: 303px;">
                            <div class="credit-card ' . $card_type . ' selectable">
                                <div class="credit-card-last4">
                                    ' . $card->getCardNumber() . '
                                </div>
                                <div class="credit-card-expiry">
                                    ' . $card->getExpirationDate() . '
                                </div>
                            </div>
                        </div>';
            }
            echo $card_list;
        } else {
            echo "Error fetching payment profiles.\n";
            $errorMessages = $response->getMessages()->getMessage();
            foreach ($errorMessages as $error) {
                echo "Error: " . $error->getCode() . " - " . $error->getText() . "\n";
            }
        }
    }
} ?>

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