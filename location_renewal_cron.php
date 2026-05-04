<?php
if ($_SERVER['HTTP_HOST'] == 'localhost') {
    require_once("global/config.php");
    require_once("global/vendor/autoload.php");
} else {
    require_once("/var/www/html/global/config.php");
    require_once("/var/www/html/global/vendor/autoload.php");
}

use Square\Environment;
use Square\Models\CreatePaymentRequest;
use Square\Models\Money;
use Square\SquareClient;


mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$message_string = '';
global $db;
$location_date = $db->Execute("SELECT * FROM DOA_LOCATION WHERE ACTIVE = 1 AND NEXT_RENEWAL_DATE = '" . date('Y-m-d') . "'");
while (!$location_date->EOF) {
    $message_string .= "Processing Location Billing: " . $location_date->fields['LOCATION_NAME'] . "<br>";
    $message_string .= "Date: " . date('Y-m-d H:i:s') . "<br>";
    try {
        $payment_gateway_data = $db->Execute("SELECT * FROM `DOA_PAYMENT_GATEWAY_SETTINGS`");

        $PAYMENT_GATEWAY = $payment_gateway_data->fields['PAYMENT_GATEWAY_TYPE'];
        $GATEWAY_MODE  = $payment_gateway_data->fields['GATEWAY_MODE'];

        $SECRET_KEY = $payment_gateway_data->fields['SECRET_KEY'];
        $PUBLISHABLE_KEY = $payment_gateway_data->fields['PUBLISHABLE_KEY'];

        $SQUARE_ACCESS_TOKEN = $payment_gateway_data->fields['ACCESS_TOKEN'];
        $SQUARE_APP_ID = $payment_gateway_data->fields['APP_ID'];
        $SQUARE_LOCATION_ID = $payment_gateway_data->fields['LOCATION_ID'];

        $SUBSCRIPTION_AMOUNT = $location_date->fields['SUBSCRIPTION_AMOUNT'];
        $PAYMENT_FROM = $location_date->fields['PAYMENT_FROM'];
        if ($PAYMENT_FROM == 'corporation') {
            $PK_VALUE = $location_date->fields['PK_CORPORATION'];
        } else {
            $PK_VALUE = $location_date->fields['PK_LOCATION'];
        }

        $payment_info = $db->Execute("SELECT * FROM `DOA_PAYMENT_INFO` WHERE PAYMENT_TYPE = 'Square' AND CLASS = '$PAYMENT_FROM' AND PK_VALUE = '$PK_VALUE'");

        if ($payment_info->RecordCount() == 0) {
            $message_string .= "No Square payment profile found for customer $PAYMENT_FROM/$PK_VALUE.<br>";
            $location_date->MoveNext();
            continue;
        }

        $LOCATION_PAYMENT_ID = $payment_info->fields['PAYMENT_ID'];

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

        // Find the default saved card for this Square customer
        $PAYMENT_METHOD_ID = '';
        try {
            $cardsResponse = $client->getCardsApi()->listCards(null, $LOCATION_PAYMENT_ID, 'DESC');
            $cards = $cardsResponse->getResult()->getCards();
            if (!empty($cards) && is_array($cards)) {
                $PAYMENT_METHOD_ID = $cards[0]->getId();
            }
        } catch (Exception $e) {
            $PAYMENT_METHOD_ID = '';
        }

        if (empty($PAYMENT_METHOD_ID)) {
            $message_string .= "No saved card found for Square customer $LOCATION_PAYMENT_ID.<br>";
            $location_date->MoveNext();
            continue;
        }

        // Create money object
        $money = new Money();
        $money->setAmount($SUBSCRIPTION_AMOUNT * 100);
        $money->setCurrency('USD');

        // Create payment request
        $paymentRequest = new CreatePaymentRequest($PAYMENT_METHOD_ID, uniqid(), $money);
        $paymentRequest->setCustomerId($LOCATION_PAYMENT_ID);

        // Create payment using the Square API
        $paymentsApi = $client->getPaymentsApi();
        try {
            $response = $paymentsApi->createPayment($paymentRequest);
            if ($response->isSuccess()) {
                $paymentId = $response->getResult()->getPayment()->getId();
                $last4Digits = $response->getResult()->getPayment()->getCardDetails()->getCard()->getLast4();

                $PAYMENT_STATUS = 'Success';
                $PAYMENT_INFO_ARRAY = ['CHARGE_ID' => $paymentId, 'LAST4' => $last4Digits];
                $PAYMENT_INFO_JSON = json_encode($PAYMENT_INFO_ARRAY);

                $LOCATION_DATA_UPDATE['NEXT_RENEWAL_DATE'] = date('Y-m-d', strtotime('+1 month', strtotime($location_date->fields['NEXT_RENEWAL_DATE'])));
            } else {
                $PAYMENT_STATUS = 'Failed';
                $PAYMENT_INFO = $response->getErrors()[0]->getDetail();

                $RETURN_DATA['STATUS'] = $PAYMENT_STATUS;
                $RETURN_DATA['PAYMENT_INFO'] = $PAYMENT_INFO;

                //$LOCATION_DATA_UPDATE['ACTIVE'] = 0;
                goto FINALIZE_PAYMENT;
            }
        } catch (\Square\Exceptions\ApiException $e) {
            $PAYMENT_STATUS = 'Failed';
            $PAYMENT_INFO = $e->getMessage();

            $RETURN_DATA['STATUS'] = $PAYMENT_STATUS;
            $RETURN_DATA['PAYMENT_INFO'] = $PAYMENT_INFO;

            //$LOCATION_DATA_UPDATE['ACTIVE'] = 0;
            goto FINALIZE_PAYMENT;
        }
    } catch (mysqli_sql_exception $e) {
        $message_string .= "Connection failed: " . $e->getMessage();
    }

    FINALIZE_PAYMENT:
    db_perform('DOA_LOCATION', $LOCATION_DATA_UPDATE, 'update', "PK_LOCATION = '" . $location_date->fields['PK_LOCATION'] . "'");
    $PAYMENT_DETAILS['PK_LOCATION'] = $location_date->fields['PK_LOCATION'];
    $PAYMENT_DETAILS['PK_CORPORATION'] = $location_date->fields['PK_CORPORATION'];
    $PAYMENT_DETAILS['PAYMENT_FROM'] = $PAYMENT_FROM;
    $PAYMENT_DETAILS['AMOUNT'] = $SUBSCRIPTION_AMOUNT;
    $PAYMENT_DETAILS['PAYMENT_STATUS'] = $PAYMENT_STATUS;
    $PAYMENT_DETAILS['PAYMENT_INFO'] = ($PAYMENT_STATUS == 'Success') ? $PAYMENT_INFO_JSON : $PAYMENT_INFO;
    $PAYMENT_DETAILS['DATE_TIME'] = date('Y-m-d H:i');
    db_perform('DOA_PAYMENT_DETAILS', $PAYMENT_DETAILS, 'insert');

    $RETURN_DATA['STATUS'] = $PAYMENT_STATUS;
    $RETURN_DATA['PAYMENT_INFO'] = ($PAYMENT_STATUS == 'Success') ? $PAYMENT_INFO_JSON : $PAYMENT_INFO;
    $message_string .= json_encode($RETURN_DATA);

    $message_string .= "<br>---------------------------------<br>";

    $info_log['info'] = $message_string;
    $info_log['created_at'] = date('Y-m-d H:i:s');
    db_perform('cron_running_log', $info_log, 'insert');


    $location_date->MoveNext();
}
