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

$call_from = (isset($_POST['call_from'])) ? $_POST['call_from'] : '';

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

$AUTHORIZE_LOGIN_ID         = $payment_gateway_data->fields['LOGIN_ID'];
$AUTHORIZE_TRANSACTION_KEY     = $payment_gateway_data->fields['TRANSACTION_KEY'];
$AUTHORIZE_CLIENT_KEY         = $payment_gateway_data->fields['AUTHORIZE_CLIENT_KEY'];

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
            $card_type = getCardTypeDetails($card_details->brand); ?>

            <div style="position: relative; width: 303px; display: inline-block;">
                <!-- Credit Card Box -->
                <?php if ($call_from == 'enrollment_auto_pay') { ?>
                    <div class="credit-card-div" id="<?= $card_details->id; ?>" data-last4="<?= $card_details->last4 ?>" onclick="selectAutoPayCreditCard(this)">
                    <?php } else { ?>
                        <div class="credit-card-div" id="<?= $card_details->id; ?>" onclick="getPaymentMethodId(this)">
                        <?php } ?>
                        <div class="credit-card <?= $card_type ?> selectable">
                            <div class="credit-card-last4">
                                <?= $card_details->last4 ?>
                            </div>
                            <div class="credit-card-expiry">
                                <?= $card_details->exp_month . '/' . $card_details->exp_year ?>
                            </div>
                        </div>
                        </div>
                        <?php if ($call_from == 'customer_credit_card') { ?>
                            <!-- Delete Button in Top-Right Corner -->
                            <a href="javascript:;" onclick="deleteThisCreditCard('<?= $card_details->id ?>');" title="Delete"
                                style="position: absolute; top: 15px; right: 5px; color: red; font-size: 18px; z-index: 10;">
                                <i class="ti-trash"></i>
                            </a>
                        <?php } ?>
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
                $api_response = $client->getCardsApi()->listCards(null, $CUSTOMER_PAYMENT_ID, 'DESC');
                $all_cards = $api_response->getResult()->getCards();
            } catch (Exception $e) {
                echo 'Caught exception: ', $e->getMessage(), "\n";
            }

            foreach ($all_cards as $card_details) {
                $card_type = getCardTypeDetails($card_details->getCardBrand()); ?>

                    <div style="position: relative; width: 303px; display: inline-block;">
                        <!-- Credit Card Box -->
                        <?php if ($call_from == 'enrollment_auto_pay') { ?>
                            <div class="credit-card-div" id="<?= $card_details->getId(); ?>" data-last4="<?= $card_details->getLast4() ?>" onclick="selectAutoPayCreditCard(this)">
                            <?php } else { ?>
                                <div class="credit-card-div" id="<?= $card_details->getId(); ?>" onclick="getPaymentMethodId(this)">
                                <?php } ?>
                                <div class="credit-card <?= $card_type ?> selectable">
                                    <div class="credit-card-last4">
                                        <?= $card_details->getLast4() ?>
                                    </div>
                                    <div class="credit-card-expiry">
                                        <?= $card_details->getExpMonth() . '/' . $card_details->getExpYear() ?>
                                    </div>
                                </div>
                                </div>
                                <?php if ($call_from == 'customer_credit_card') { ?>
                                    <!-- Delete Button in Top-Right Corner -->
                                    <a href="javascript:;" onclick="deleteThisCreditCard('<?= $card_details->getId() ?>');" title="Delete"
                                        style="position: absolute; top: 15px; right: 5px; color: red; font-size: 18px; z-index: 10;">
                                        <i class="ti-trash"></i>
                                    </a>
                                <?php } ?>
                            </div>
                            <?php }
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
                                $card_type = getCardTypeDetails($card->getCardType()); ?>

                                <div style="position: relative; width: 303px; display: inline-block;">
                                    <?php if ($call_from == 'enrollment_auto_pay') { ?>
                                        <div class="credit-card-div" id="<?= $profile->getCustomerPaymentProfileId() ?>" data-last4="<?= $card->getCardNumber() ?>" onclick="selectAutoPayCreditCard(this)">
                                        <?php } else { ?>
                                            <div class="credit-card-div" id="<?= $profile->getCustomerPaymentProfileId() ?>" onclick="getPaymentMethodId(this)">
                                            <?php } ?>
                                            <div class="credit-card <?= $card_type ?> selectable">
                                                <div class="credit-card-last4">
                                                    <?= $card->getCardNumber() ?>
                                                </div>
                                                <div class="credit-card-expiry">
                                                    <?= $card->getExpirationDate() ?>
                                                </div>
                                            </div>
                                            </div>
                                            <?php if ($call_from == 'customer_credit_card') { ?>
                                                <!-- Delete Button in Top-Right Corner -->
                                                <a href="javascript:;" onclick="deleteThisCreditCard('<?= $profile->getCustomerPaymentProfileId() ?>');" title="Delete"
                                                    style="position: absolute; top: 15px; right: 5px; color: red; font-size: 18px; z-index: 10;">
                                                    <i class="ti-trash"></i>
                                                </a>
                                            <?php } ?>
                                        </div>
                                    <?php }
                            } else {
                                echo "Error fetching payment profiles.\n";
                                $errorMessages = $response->getMessages()->getMessage();
                                foreach ($errorMessages as $error) {
                                    echo "Error: " . $error->getCode() . " - " . $error->getText() . "\n";
                                }
                            }
                        }
                    } elseif ($PAYMENT_GATEWAY == 'Clover') {
                        $user_payment_info_data = $db_account->Execute("SELECT DOA_CUSTOMER_PAYMENT_INFO.CUSTOMER_PAYMENT_ID FROM DOA_CUSTOMER_PAYMENT_INFO INNER JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_USER_MASTER.PK_USER = DOA_CUSTOMER_PAYMENT_INFO.PK_USER WHERE PAYMENT_TYPE = 'Clover' AND PK_USER_MASTER = '$_POST[PK_USER_MASTER]'");

                        if ($user_payment_info_data->RecordCount() > 0) {
                            $CLOVER_API_URL = ($GATEWAY_MODE == 'live') ? "https://scl.clover.com" : "https://scl-sandbox.dev.clover.com";
                            $CUSTOMER_PAYMENT_ID = $user_payment_info_data->fields['CUSTOMER_PAYMENT_ID'];

                            $ch = curl_init($CLOVER_API_URL . '/v1/customers/' . urlencode($CUSTOMER_PAYMENT_ID));
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                                "Authorization: Bearer " . $API_KEY,
                                "X-Clover-Merchant-Id: " . $MERCHANT_ID,
                                "Accept: application/json",
                            ]);
                            $response = curl_exec($ch);
                            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                            curl_close($ch);

                            $clover_customer = json_decode($response);
                            $all_cards = $clover_customer->sources->data ?? [];

                            if ($http_code != 200 && $http_code != 201) {
                                echo "Error fetching saved cards.";
                                $all_cards = [];
                            }

                            foreach ($all_cards as $card_details) {
                                $card_id    = $card_details->id ?? '';
                                $card_last4 = $card_details->last4 ?? '';
                                $card_type  = getCardTypeDetails($card_details->brand ?? '');
                                $card_exp   = ($card_details->exp_month ?? '') . '/' . ($card_details->exp_year ?? ''); ?>

                                    <div style="position: relative; width: 303px; display: inline-block;">
                                        <?php if ($call_from == 'enrollment_auto_pay') { ?>
                                            <div class="credit-card-div" id="<?= $card_id ?>" data-last4="<?= $card_last4 ?>" onclick="selectAutoPayCreditCard(this)">
                                            <?php } else { ?>
                                                <div class="credit-card-div" id="<?= $card_id ?>" onclick="getPaymentMethodId(this)">
                                                <?php } ?>
                                                <div class="credit-card <?= $card_type ?> selectable">
                                                    <div class="credit-card-last4">
                                                        <?= $card_last4 ?>
                                                    </div>
                                                    <div class="credit-card-expiry">
                                                        <?= $card_exp ?>
                                                    </div>
                                                </div>
                                                </div>
                                                <?php if ($call_from == 'customer_credit_card') { ?>
                                                    <a href="javascript:;" onclick="deleteThisCreditCard('<?= $card_id ?>');" title="Delete"
                                                        style="position: absolute; top: 15px; right: 5px; color: red; font-size: 18px; z-index: 10;">
                                                        <i class="ti-trash"></i>
                                                    </a>
                                                <?php } ?>
                                            </div>
                                <?php }
                        }
                    } ?>
                                <div id="delete_message"></div>


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