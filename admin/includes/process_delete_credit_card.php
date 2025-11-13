<?php
require_once('../../global/config.php');
require_once("../../global/stripe-php/init.php");
global $db;
global $db_account;

use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

use Square\Environment;
use Square\Models\CreatePaymentRequest;
use Square\Models\Money;
use Square\SquareClient;

require_once('../../global/authorizenet/autoload.php');

use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

$PK_USER = $_POST['PK_USER'];

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

$message = '';

if ($PAYMENT_GATEWAY == 'Stripe') {
    $stripe = new StripeClient($SECRET_KEY);

    $customer_payment_info = $db_account->Execute("SELECT * FROM DOA_CUSTOMER_PAYMENT_INFO WHERE PAYMENT_TYPE = 'Stripe' AND PK_USER = " . $PK_USER);

    if ($customer_payment_info->RecordCount() > 0) {
        $CUSTOMER_PAYMENT_ID = $customer_payment_info->fields['CUSTOMER_PAYMENT_ID'];
        try {
            // Delete the customer payment method
            $stripe->customers->deleteSource(
                $CUSTOMER_PAYMENT_ID,
                $_POST['card_id']
            );
            $STATUS = true;
            $MESSAGE = 'Credit card deleted successfully.';
        } catch (ApiErrorException $e) {
            $STATUS = false;
            $MESSAGE = 'Error deleting credit card: ' . $e->getMessage();
        }
    } else {
        $STATUS = false;
        $MESSAGE = 'No credit card found for this user.';
    }
    $RETURN_DATA['STATUS'] = $STATUS;
    $RETURN_DATA['MESSAGE'] = $MESSAGE;
    echo json_encode($RETURN_DATA);
} elseif ($PAYMENT_GATEWAY == 'Square') {
    $card_id = $_POST['card_id'];

    echo $url = $GATEWAY_MODE == 'live'
        ? "https://connect.squareup.com/v2/cards/$card_id"
        : "https://connect.squareupsandbox.com/v2/cards/$card_id";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $SQUARE_ACCESS_TOKEN",
        "Content-Type: application/json"
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    if ($httpcode == 200) {
        $STATUS = true;
        $MESSAGE = 'Credit card deleted successfully.';
    } else {
        $STATUS = false;
        $MESSAGE = "Failed to disable card (HTTP $httpcode):<br>$response";
    }
    $RETURN_DATA['STATUS'] = $STATUS;
    $RETURN_DATA['MESSAGE'] = $MESSAGE;
    echo json_encode($RETURN_DATA);

    /* 

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

    $customer_payment_info = $db_account->Execute("SELECT * FROM DOA_CUSTOMER_PAYMENT_INFO WHERE PAYMENT_TYPE = 'Square' AND PK_USER = " . $PK_USER);

    if ($customer_payment_info->RecordCount() > 0) {
        $CUSTOMER_PAYMENT_ID = $customer_payment_info->fields['CUSTOMER_PAYMENT_ID'];
        try {
            // Delete the customer payment method
            $api_response = $client->getCardsApi()->disableCard($_POST['card_id']);
            $STATUS = true;
            $MESSAGE = 'Credit card deleted successfully.';
        } catch (ApiErrorException $e) {
            $STATUS = false;
            $MESSAGE = 'Error deleting credit card: ' . $e->getMessage();
        }
    } else {
        $STATUS = false;
        $MESSAGE = 'No credit card found for this user.';
    }
    $RETURN_DATA['STATUS'] = $STATUS;
    $RETURN_DATA['MESSAGE'] = $MESSAGE;
    echo json_encode($RETURN_DATA); */
} elseif ($PAYMENT_GATEWAY == 'Authorized.net') {
    $customer_payment_info = $db_account->Execute("SELECT * FROM DOA_CUSTOMER_PAYMENT_INFO WHERE PAYMENT_TYPE = 'Authorized.net' AND PK_USER = " . $PK_USER);
    if ($customer_payment_info->RecordCount() > 0) {
        $customerProfileId = $customer_payment_info->fields['CUSTOMER_PAYMENT_ID'];
        $paymentProfileId = $_POST['card_id'];
    } else {
        $RETURN_DATA['STATUS'] = false;
        $RETURN_DATA['MESSAGE'] = 'No credit card found for this user.';
        echo json_encode($RETURN_DATA);
        exit;
    }

    $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
    $merchantAuthentication->setName($AUTHORIZE_LOGIN_ID);
    $merchantAuthentication->setTransactionKey($AUTHORIZE_TRANSACTION_KEY);

    // Create the request
    $request = new AnetAPI\DeleteCustomerPaymentProfileRequest();
    $request->setMerchantAuthentication($merchantAuthentication);
    $request->setCustomerProfileId($customerProfileId);
    $request->setCustomerPaymentProfileId($paymentProfileId);

    // Create the controller and get the response
    $controller = new AnetController\DeleteCustomerPaymentProfileController($request);

    if ($GATEWAY_MODE == 'live') {
        $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::PRODUCTION);
    } else {
        $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::SANDBOX);
    }

    if ($response != null && $response->getMessages()->getResultCode() == "Ok") {
        $STATUS = true;
        $MESSAGE = 'Credit card deleted successfully.';
    } else {
        $STATUS = false;
        $MESSAGE = 'Error deleting credit card: ' . ($response->getMessages() ? $response->getMessages()->getMessage()[0]->getText() : 'Unknown error');
    }
    $RETURN_DATA['STATUS'] = $STATUS;
    $RETURN_DATA['MESSAGE'] = $MESSAGE;
    echo json_encode($RETURN_DATA);
} else {
    $RETURN_DATA['STATUS'] = false;
    $RETURN_DATA['MESSAGE'] = 'Payment gateway not supported for deletion.';
    echo json_encode($RETURN_DATA);
}
