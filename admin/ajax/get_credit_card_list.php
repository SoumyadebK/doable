<?php
require_once('../../global/config.php');
require_once("../../global/stripe-php-master/init.php");
global $db;
global $db_account;
global $master_database;
use Square\Models\Address;
use Square\SquareClient;
use Square\Environment;

$account_data = $db->Execute("SELECT * FROM `DOA_ACCOUNT_MASTER` WHERE `PK_ACCOUNT_MASTER` = '$_SESSION[PK_ACCOUNT_MASTER]'");
$ACCESS_TOKEN = $account_data->fields['ACCESS_TOKEN'];
$PAYMENT_GATEWAY = $_POST['PAYMENT_GATEWAY'];

if($PAYMENT_GATEWAY == "Stripe") {
    $customer_payment_info = $db_account->Execute("SELECT * FROM DOA_CUSTOMER_PAYMENT_INFO INNER JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_USER_MASTER.PK_USER = DOA_CUSTOMER_PAYMENT_INFO.PK_USER WHERE PAYMENT_TYPE = 'Stripe' AND PK_USER_MASTER = '$_POST[PK_USER_MASTER]'");
    $SECRET_KEY = $account_data->fields['SECRET_KEY'];
    if ($SECRET_KEY != '') {
        $stripe = new \Stripe\StripeClient($SECRET_KEY);
        $message = '';

        if ($customer_payment_info->RecordCount() > 0) {
            $customer_id = $customer_payment_info->fields['CUSTOMER_PAYMENT_ID'];
            $stripe_customer = $stripe->customers->retrieve($customer_id);
            $card_id = $stripe_customer->default_source;

            $url = "https://api.stripe.com/v1/customers/" . $customer_id . "/cards/" . $card_id;
            $AUTH = "Authorization: Bearer " . $SECRET_KEY;

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => array(
                    $AUTH
                ),
            ));

            $response = curl_exec($curl);
            $card_details = json_decode($response, true);
        }
    }
} elseif ($PAYMENT_GATEWAY == "Square") {
    $user_payment_info_data = $db->Execute("SELECT DOA_CUSTOMER_PAYMENT_INFO.CUSTOMER_PAYMENT_ID FROM DOA_CUSTOMER_PAYMENT_INFO INNER JOIN DOA_USER_MASTER ON DOA_USER_MASTER.PK_USER=DOA_CUSTOMER_PAYMENT_INFO.PK_USER WHERE PK_USER_MASTER = '$_POST[PK_USER_MASTER]'");

    if ($user_payment_info_data->RecordCount() > 0) {
        require_once("../../global/vendor/autoload.php");
        $client = new SquareClient([
            'accessToken' => $ACCESS_TOKEN,
            'environment' => Environment::SANDBOX,
        ]);

        $CUSTOMER_PAYMENT_ID = $user_payment_info_data->fields['CUSTOMER_PAYMENT_ID'];
        $card = new \Square\Models\Card();
        $card->setCustomerId($CUSTOMER_PAYMENT_ID);
        $all_payment_methods = $client->getCardsApi()->listCards();
        $all_payment_methods_array = json_decode($all_payment_methods->getBody());

        $card_list = '';
        foreach ($all_payment_methods_array->cards as $all_payment_methods_data) {
            $card_list .= '<div class="col-4"> 
                            <div class="credit-card selectable">
                                <div class="credit-card-last4" style="font-size: 20px;">
                                   ' . $all_payment_methods_data->last_4 . '
                                </div>
                                <div class="credit-card-expiry" style="font-size: 11px; font-weight: bold;">
                                    ' . $all_payment_methods_data->exp_month . '/' . $all_payment_methods_data->exp_year . '
                                </div>
                            </div>
                        </div>';
        }
        echo $card_list;
    }
} ?>

<?php if (isset($card_details['last4'])) {
    switch ($card_details['brand']) {
        case 'Visa':
        case 'Visa (debit)':
            $card_type = 'visa';
            break;
        case 'MasterCard':
        case 'Mastercard (2-series)':
        case 'Mastercard (debit)':
        case 'Mastercard (prepaid)':
            $card_type = 'mastercard';
            break;
        case 'American Express':
            $card_type = 'amex';
            break;
        case 'Discover':
        case 'Discover (debit)':
            $card_type = 'discover';
            break;
        case 'Diners Club':
        case 'Diners Club (14-digit card)':
            $card_type = 'diners';
            break;
        case 'JCB':
            $card_type = 'jcb';
            break;
        case 'UnionPay':
        case 'UnionPay (debit)':
        case 'UnionPay (19-digit card)':
            $card_type = 'unionpay';
            break;
        default:
            $card_type = '';
            break;

    } ?>
    <div class="p-20" id="<?=$card_details['id']?>" onclick="getPaymentMethodId(this)">
        <h5>Saved Card Details</h5>
        <div class="credit-card <?=$card_type?> selectable" style="margin-right: 80%;">
            <div class="credit-card-last4">
                <?=$card_details['last4']?>
            </div>
            <div class="credit-card-expiry">
                <?=$card_details['exp_month'].'/'.$card_details['exp_year']?>
            </div>
        </div>
    </div>
<?php } ?>