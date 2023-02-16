<?php
require_once('../../global/config.php');
require_once("../../global/stripe-php-master/init.php");

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

    <!--
INFO: The selectable class adds a pointer and shadow animations on hover.
-->
    <!-- Cards -->  

    <!-- Visa - selectable -->
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