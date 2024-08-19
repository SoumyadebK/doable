<?php

use Stripe\Stripe;

require_once("global/stripe-php-master/init.php");

require_once('global/config.php');
global $db;

$payment_gateway_setting = $db->Execute( "SELECT * FROM `DOA_PAYMENT_GATEWAY_SETTINGS`");
$SECRET_KEY = $payment_gateway_setting->fields['SECRET_KEY'];
$PUBLISHABLE_KEY = $payment_gateway_setting->fields['PUBLISHABLE_KEY'];
Stripe::setApiKey($SECRET_KEY);

$account_details = $db->Execute("SELECT DOA_ACCOUNT_MASTER.PK_ACCOUNT_MASTER, DOA_ACCOUNT_MASTER.BUSINESS_NAME, DOA_ACCOUNT_BILLING_DETAILS.NEXT_RENEWAL_DATE, DOA_ACCOUNT_BILLING_DETAILS.TOTAL_AMOUNT, DOA_ACCOUNT_PAYMENT_INFO.ACCOUNT_PAYMENT_ID FROM DOA_ACCOUNT_MASTER INNER JOIN DOA_ACCOUNT_BILLING_DETAILS ON DOA_ACCOUNT_MASTER.PK_ACCOUNT_MASTER = DOA_ACCOUNT_BILLING_DETAILS.PK_ACCOUNT_MASTER INNER JOIN DOA_ACCOUNT_PAYMENT_INFO ON DOA_ACCOUNT_MASTER.PK_ACCOUNT_MASTER = DOA_ACCOUNT_PAYMENT_INFO.PK_ACCOUNT_MASTER WHERE ACTIVE = 1 AND DOA_ACCOUNT_BILLING_DETAILS.NEXT_RENEWAL_DATE <= '".date('Y-m-d')."'");

while (!$account_details->EOF) {
    $PK_ACCOUNT_MASTER = $account_details->fields['PK_ACCOUNT_MASTER'];
    $BUSINESS_NAME = $account_details->fields['BUSINESS_NAME'];
    $ACCOUNT_PAYMENT_ID = $account_details->fields['ACCOUNT_PAYMENT_ID'];
    $AMOUNT = $account_details->fields['TOTAL_AMOUNT'];
    $NEXT_RENEWAL_DATE = $account_details->fields['NEXT_RENEWAL_DATE'];

    $account = \Stripe\Customer::retrieve($ACCOUNT_PAYMENT_ID);
    if($account->id === $ACCOUNT_PAYMENT_ID) {
        try {
            $charge = \Stripe\Charge::create(array(
                "amount" => $AMOUNT * 100,
                "currency" => "usd",
                "description" => $BUSINESS_NAME,
                "customer" => $ACCOUNT_PAYMENT_ID,
                "statement_descriptor" => "Subscription Charge",
            ));

            if($charge->paid == 1){
                $ACCOUNT_PAYMENT_DETAILS['PAYMENT_STATUS'] = 'Success';
                $ACCOUNT_PAYMENT_DETAILS['PAYMENT_INFO'] = $charge->id;

                $ACCOUNT_BILLING_DETAILS['STATUS'] = 'Active';
                $ACCOUNT_BILLING_DETAILS['NEXT_RENEWAL_DATE'] = date("Y-m-d", strtotime('+1 month', strtotime($NEXT_RENEWAL_DATE)));
            } else {
                $ACCOUNT_PAYMENT_DETAILS['PAYMENT_STATUS'] = 'Failed';
                $ACCOUNT_PAYMENT_DETAILS['PAYMENT_INFO'] = $charge->failure_message;

                $ACCOUNT_BILLING_DETAILS['STATUS'] = 'Pending';
            }
        } catch (Exception $e) {
            $ACCOUNT_PAYMENT_DETAILS['PAYMENT_STATUS'] = 'Failed';
            $ACCOUNT_PAYMENT_DETAILS['PAYMENT_INFO'] = $e->getMessage();

            $ACCOUNT_BILLING_DETAILS['STATUS'] = 'Pending';
        }
    } else {
        $ACCOUNT_PAYMENT_DETAILS['PAYMENT_STATUS'] = 'Failed';
        $ACCOUNT_PAYMENT_DETAILS['PAYMENT_INFO'] = "Customer not found";

        $ACCOUNT_BILLING_DETAILS['STATUS'] = 'Pending';
    }

    db_perform('DOA_ACCOUNT_BILLING_DETAILS', $ACCOUNT_BILLING_DETAILS, 'update', " PK_ACCOUNT_MASTER = ".$PK_ACCOUNT_MASTER);

    $ACCOUNT_PAYMENT_DETAILS['PK_ACCOUNT_MASTER'] = $PK_ACCOUNT_MASTER;
    $ACCOUNT_PAYMENT_DETAILS['DATE_TIME'] = date('Y-m-d H:i');
    $ACCOUNT_PAYMENT_DETAILS['AMOUNT'] = $AMOUNT;
    db_perform('DOA_ACCOUNT_PAYMENT_DETAILS', $ACCOUNT_PAYMENT_DETAILS, 'insert');

    $account_details->MoveNext();
}