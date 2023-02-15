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
     $card_list .= '<a href="javascript:;">'.$all_payment_methods_data->card->last4.'</a><br>';
}

echo $card_list;