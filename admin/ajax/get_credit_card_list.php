<?php
require_once('../../global/config.php');
require_once("../../global/stripe-php-master/init.php");

use Square\Models\Address;
use Square\SquareClient;
use Square\Environment;

$account_data = $db->Execute("SELECT * FROM `DOA_ACCOUNT_MASTER` WHERE `PK_ACCOUNT_MASTER` = '$_SESSION[PK_ACCOUNT_MASTER]'");
$ACCESS_TOKEN = $account_data->fields['ACCESS_TOKEN'];

$PAYMENT_GATEWAY = $_POST['PAYMENT_GATEWAY'];

if($PAYMENT_GATEWAY == "Stripe") {

    $account_data = $db->Execute("SELECT * FROM `DOA_ACCOUNT_MASTER` WHERE `PK_ACCOUNT_MASTER` = '$_SESSION[PK_ACCOUNT_MASTER]'");

    $SECRET_KEY = $account_data->fields['SECRET_KEY'];

    $stripe = new \Stripe\StripeClient($SECRET_KEY);

    $user_payment_info_data = $db->Execute("SELECT DOA_CUSTOMER_PAYMENT_INFO.CUSTOMER_PAYMENT_ID FROM DOA_CUSTOMER_PAYMENT_INFO INNER JOIN DOA_USER_MASTER ON DOA_USER_MASTER.PK_USER=DOA_CUSTOMER_PAYMENT_INFO.PK_USER WHERE PK_USER_MASTER = '$_POST[PK_USER_MASTER]'");

    $CUSTOMER_PAYMENT_ID = $user_payment_info_data->fields['CUSTOMER_PAYMENT_ID'];

    $all_payment_methods = $stripe->customers->allPaymentMethods(
        $CUSTOMER_PAYMENT_ID,
        ['type' => 'card']
    );

    $card_list = '';
    foreach ($all_payment_methods as $all_payment_methods_data) {
        $card_list .= '
<div class="col-4" > 
    <div class="credit-card  selectable" id="plate" onclick="myFunction()">
        <div class="credit-card-last4" style="font-size: 20px;">
           '.$all_payment_methods_data->card->last4.'
        </div>
        <div class="credit-card-expiry" style="font-size: 11px; font-weight: bold;">
            '.$all_payment_methods_data->card->exp_month.'/'.$all_payment_methods_data->card->exp_year.'
        </div>
    </div>
</div>';
    }

    echo $card_list;

} elseif ($PAYMENT_GATEWAY == "Square") {

    require_once("../../global/vendor/autoload.php");

    $client = new SquareClient([
        'accessToken' => $ACCESS_TOKEN,
        'environment' => Environment::SANDBOX,
    ]);

    $user_payment_info_data = $db->Execute("SELECT DOA_CUSTOMER_PAYMENT_INFO.CUSTOMER_PAYMENT_ID FROM DOA_CUSTOMER_PAYMENT_INFO INNER JOIN DOA_USER_MASTER ON DOA_USER_MASTER.PK_USER=DOA_CUSTOMER_PAYMENT_INFO.PK_USER WHERE PK_USER_MASTER = '$_POST[PK_USER_MASTER]'");

    $CUSTOMER_PAYMENT_ID = $user_payment_info_data->fields['CUSTOMER_PAYMENT_ID'];

    $card = new \Square\Models\Card();
    //$card->setCardholderName($user_master->fields['FIRST_NAME'] . " " . $user_master->fields['LAST_NAME']);
    //$card->setBillingAddress($billing_address);
    $card->setCustomerId($CUSTOMER_PAYMENT_ID);
    //$card->setReferenceId('user-id-1');

    $all_payment_methods = $client->getCardsApi()->listCards();

    $all_payment_methods_array = json_decode($all_payment_methods->getBody());

    //pre_r(json_decode($all_payment_methods->getBody()));

    $card_list = '';
    foreach ($all_payment_methods_array->cards as $all_payment_methods_data) {
        $card_list .= '
<div class="col-4" > 
    <div class="credit-card  selectable" id="plate" onclick="myFunction()">
        <div class="credit-card-last4" style="font-size: 20px;">
           '.$all_payment_methods_data->last_4.'
        </div>
        <div class="credit-card-expiry" style="font-size: 11px; font-weight: bold;">
            '.$all_payment_methods_data->exp_month.'/'.$all_payment_methods_data->exp_year.'
        </div>
    </div>
</div>';
    }
    echo $card_list;
} /*elseif ($PAYMENT_GATEWAY == "Authorized.net") {

    $user_payment_info_data = $db->Execute("SELECT DOA_CUSTOMER_PAYMENT_INFO.CUSTOMER_PAYMENT_ID FROM DOA_CUSTOMER_PAYMENT_INFO INNER JOIN DOA_USER_MASTER ON DOA_USER_MASTER.PK_USER=DOA_CUSTOMER_PAYMENT_INFO.PK_USER WHERE PK_USER_MASTER = '$_POST[PK_USER_MASTER]'");

    $CUSTOMER_PAYMENT_ID = $user_payment_info_data->fields['CUSTOMER_PAYMENT_ID'];

    $all_payment_methods_array = json_decode($all_payment_methods->getBody());

    //pre_r(json_decode($all_payment_methods->getBody()));

    $card_list = '';
    foreach ($all_payment_methods_array->cards as $all_payment_methods_data) {
        $card_list .= '
<div class="col-4" > 
    <div class="credit-card  selectable" id="plate" onclick="myFunction()">
        <div class="credit-card-last4" style="font-size: 20px;">
           '.$all_payment_methods_data->last_4.'
        </div>
        <div class="credit-card-expiry" style="font-size: 11px; font-weight: bold;">
            '.$all_payment_methods_data->exp_month.'/'.$all_payment_methods_data->exp_year.'
        </div>
    </div>
</div>';
    }
    echo $card_list;
}*/