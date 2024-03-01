<?php
use Mpdf\Mpdf;

require_once('../../global/config.php');
global $db;
global $db_account;

$header = '../'.$_POST['header'];

/*$SQUARE_MODE 			= 2;
if ($SQUARE_MODE == 1)
    $SQ_URL = "https://connect.squareup.com";
else if ($SQUARE_MODE == 2)
    $SQ_URL = "https://connect.squareupsandbox.com";

if ($SQUARE_MODE == 1)
    $URL = "https://web.squarecdn.com/v1/square.js";
else if ($SQUARE_MODE == 2)
    $URL = "https://sandbox.web.squarecdn.com/v1/square.js";*/

if(!empty($_POST) && $_POST['FUNCTION_NAME'] == 'confirmEnrollmentPayment') {
    $html_template_receipt = '<table style="width:100%">
        <tbody>
            <tr>
                <td style="text-align:center"><strong>{BUSINESS_NAME}</strong></td>
            </tr>
            <tr>
                <td>&nbsp;</td>
            </tr>
            <tr>
                <td style="text-align:center">{FULL_NAME} {LOCATION_NAME}</td>
            </tr>
            <tr>
                <td style="text-align:center">{LOCATION_NAME}, {STATE} {ZIP}</td>
            </tr>
            <tr>
                <td style="text-align:center">{PHONE}</td>
            </tr>
            <tr>
                <td style="text-align:center"><strong>Sale Transaction</strong></td>
            </tr>
            <tr>
                <td style="text-align:center"><strong>Receipt# {BILLING_REF}</strong></td>
            </tr>
            <tr>
                <td style="text-align:center">{PAYMENT_DATE}</td>
            </tr>
        </tbody>
    </table>
    
    <table style="width:100%">
        <tbody>
            <tr>
                <td>&nbsp;</td>
            </tr>
            <tr>
                <td>Payment Method</td>
                <td style="text-align:right">{PAYMENT_METHOD}</td>
            </tr>
            <tr>
                <td>Card#:</td>
                <td style="text-align:right">{CARD_NUMBER}</td>
            </tr>
            <tr>
                <td>Details</td>
                <td style="text-align:right">{DETAILS}</td>
            </tr>
            <tr>
                <td>Amount(s)</td>
                <td style="text-align:right">{AMOUNT}</td>
            </tr>
            <tr>
                <td>Total:</td>
                <td style="text-align:right">{TOTAL}</td>
            </tr>
        </tbody>
    </table>
    
    <table style="width:100%">
        <tbody>
            <tr>
                <td>&nbsp;</td>
            </tr>
            <tr>
                <td style="text-align:center">{FULL_NAME}</td>
            </tr>
            <tr>
                <td style="text-align:center">I agree to pay above total amount according to card<br />
                issuer agreement (merchant agreement if voucher)</td>
            </tr>
            <tr>
                <td style="text-align:center">*Per authorization on {PAYMENT_DATE} Receipt# {BILLING_REF}</td>
            </tr>
        </tbody>
    </table>';

    $ENROLLMENT_LEDGER_PARENT = $_POST['PK_ENROLLMENT_LEDGER'];
    unset($_POST['PK_ENROLLMENT_LEDGER']);

    $payment_info = '';
    if ($_POST['PK_PAYMENT_TYPE'] == 1) {
        if ($_POST['PAYMENT_GATEWAY'] == 'Stripe') {
            require_once("../global/stripe-php-master/init.php");
            $stripe = new \Stripe\StripeClient($SECRET_KEY);
            $STRIPE_TOKEN = $_POST['token'];

            $user_payment_info_data = $db->Execute("SELECT $account_database.DOA_CUSTOMER_PAYMENT_INFO.CUSTOMER_PAYMENT_ID FROM $account_database.DOA_CUSTOMER_PAYMENT_INFO INNER JOIN $master_database.DOA_USER_MASTER ON $master_database.DOA_USER_MASTER.PK_USER=$account_database.DOA_CUSTOMER_PAYMENT_INFO.PK_USER WHERE DOA_CUSTOMER_PAYMENT_INFO.PAYMENT_TYPE = 'Stripe' AND DOA_USER_MASTER.PK_USER_MASTER = '$_POST[PK_USER_MASTER]'");
            if ($user_payment_info_data->RecordCount() > 0) {
                $CUSTOMER_PAYMENT_ID = $user_payment_info_data->fields['CUSTOMER_PAYMENT_ID'];
            } else {
                $user_master = $db->Execute("SELECT DOA_USERS.PK_USER, DOA_USERS.EMAIL_ID, DOA_USERS.FIRST_NAME, DOA_USERS.LAST_NAME, DOA_USERS.PHONE FROM `DOA_USERS` LEFT JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER=DOA_USER_MASTER.PK_USER WHERE DOA_USER_MASTER.PK_USER_MASTER = '$_POST[PK_USER_MASTER]'");

                try {
                    $customer = $stripe->customers->create([
                        'email' => $user_master->fields['EMAIL_ID'],
                        'name' => $user_master->fields['FIRST_NAME']." ".$user_master->fields['LAST_NAME'],
                        'phone' => $user_master->fields['PHONE'],
                        'description' => $user_master->fields['PK_USER'],
                    ]);
                } catch (\Stripe\Exception\ApiErrorException $e) {
                    pre_r($e->getMessage());
                }

                $CUSTOMER_PAYMENT_ID = $customer->id;
                $STRIPE_DETAILS['PK_USER']  = $user_master->fields['PK_USER'];
                $STRIPE_DETAILS['CUSTOMER_PAYMENT_ID'] = $CUSTOMER_PAYMENT_ID;
                $STRIPE_DETAILS['PAYMENT_TYPE'] = 'Stripe';
                $STRIPE_DETAILS['CREATED_ON'] = date("Y-m-d H:i");
                db_perform_account('DOA_CUSTOMER_PAYMENT_INFO', $STRIPE_DETAILS, 'insert');
            }

            $PAYMENT_METHOD_ID = '';
            if (!empty($_POST['PAYMENT_METHOD_ID'])) {
                $PAYMENT_METHOD_ID = $_POST['PAYMENT_METHOD_ID'];
            } else {
                try {
                    $payment_method = $stripe->paymentMethods->create([
                        'type' => 'card',
                        'card' => [
                            'token' => $STRIPE_TOKEN
                        ],
                    ]);

                    $stripe->paymentMethods->attach(
                        $payment_method->id,
                        ['customer' => $CUSTOMER_PAYMENT_ID]
                    );

                    $PAYMENT_METHOD_ID = $payment_method->id;
                } catch (\Stripe\Exception\ApiErrorException $e) {
                    pre_r($e->getMessage());
                }
            }

            $AMOUNT = $_POST['AMOUNT']*100;
            try {\Stripe\Stripe::setApiKey($SECRET_KEY);
                $payment_intent = \Stripe\PaymentIntent::create([
                    'amount' => $AMOUNT,
                    'currency' => 'USD',
                    'customer' => $CUSTOMER_PAYMENT_ID,
                    'payment_method' => $PAYMENT_METHOD_ID,
                ]);
            } catch (Exception $e) {
                pre_r($e->getMessage());
            }

            $payment_info = '**** **** **** '.$payment_intent->source->last4;

            //pre_r($payment_intent);

        } elseif ($_POST['PAYMENT_GATEWAY'] == 'Square') {

            require_once("../global/vendor/autoload.php");

            $AMOUNT = $_POST['AMOUNT'];

            $client = new SquareClient([
                'accessToken' => $ACCESS_TOKEN,
                'environment' => Environment::SANDBOX,
            ]);

            $user_payment_info_data = $db->Execute("SELECT $account_database.DOA_CUSTOMER_PAYMENT_INFO.CUSTOMER_PAYMENT_ID FROM $account_database.DOA_CUSTOMER_PAYMENT_INFO INNER JOIN $master_database.DOA_USER_MASTER ON $master_database.DOA_USER_MASTER.PK_USER=$account_database.DOA_CUSTOMER_PAYMENT_INFO.PK_USER WHERE PAYMENT_TYPE = 'Square' AND PK_USER_MASTER = '$_POST[PK_USER_MASTER]'");
            if ($user_payment_info_data->RecordCount() > 0) {
                $CUSTOMER_PAYMENT_ID = $user_payment_info_data->fields['CUSTOMER_PAYMENT_ID'];
            } else {
                $user_master = $db->Execute("SELECT DOA_USERS.PK_USER, DOA_USERS.EMAIL_ID, DOA_USERS.FIRST_NAME, DOA_USERS.LAST_NAME, DOA_USERS.PHONE FROM `DOA_USERS` LEFT JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER=DOA_USER_MASTER.PK_USER WHERE PK_USER_MASTER = '$_POST[PK_USER_MASTER]'");

                $address = new \Square\Models\Address();
                $address->setAddressLine1('500 Electric Ave');
                $address->setAddressLine2('Suite 600');
                $address->setLocality('New York');
                $address->setAdministrativeDistrictLevel1('NY');
                $address->setPostalCode('10003');
                $address->setCountry('US');

                $body = new \Square\Models\CreateCustomerRequest();
                $body->setGivenName($user_master->fields['FIRST_NAME'] . " " . $user_master->fields['LAST_NAME']);
                $body->setFamilyName('Earhart');
                $body->setEmailAddress($user_master->fields['EMAIL_ID']);
                $body->setAddress($address);
                $body->setPhoneNumber($user_master->fields['PHONE']);
                $body->setReferenceId('YOUR_REFERENCE_ID');
                $body->setNote('a customer');

                try {
                    $api_response = $client->getCustomersApi()->createCustomer($body);
                } catch (\Square\Exceptions\ApiException $e) {
                    pre_r($e->getMessage());
                }

                $CUSTOMER_PAYMENT_ID = json_decode($api_response->getBody())->customer->id;
                $SQUARE_DETAILS['PK_USER'] = $user_master->fields['PK_USER'];
                $SQUARE_DETAILS['CUSTOMER_PAYMENT_ID'] = $CUSTOMER_PAYMENT_ID;
                $SQUARE_DETAILS['PAYMENT_TYPE'] = 'Square';
                $SQUARE_DETAILS['CREATED_ON'] = date("Y-m-d H:i");
                db_perform_account('DOA_CUSTOMER_PAYMENT_INFO', $SQUARE_DETAILS, 'insert');

            }

            $card = new \Square\Models\Card();
            $card->setCardholderName($user_master->fields['FIRST_NAME'] . " " . $user_master->fields['LAST_NAME']);
            //$card->setBillingAddress($billing_address);
            $card->setCustomerId($CUSTOMER_PAYMENT_ID);
            //$card->setReferenceId('user-id-1');

            $body = new \Square\Models\CreateCardRequest(
                uniqid(),
                $_POST['sourceId'],
                $card
            );

            $api_response = $client->getCardsApi()->createCard($body);

            if ($api_response->isSuccess()) {
                $result = $api_response->getResult();
            } else {
                $errors = $api_response->getErrors();
            }

        } elseif ($_POST['PAYMENT_GATEWAY'] == 'Authorized.net') {

            require_once('../global/authorizenet/vendor/autoload.php');

            $LOGIN_ID = $account_data->fields['LOGIN_ID'];
            $TRANSACTION_KEY = $account_data->fields['TRANSACTION_KEY'];

            // Product Details
            $itemName = $_POST['PK_ENROLLMENT_MASTER'];
            $itemNumber = $_POST['PK_ENROLLMENT_BILLING'];
            $itemPrice = $_POST['AMOUNT'];
            $currency = "USD";

            // Retrieve card and user info from the submitted form data
            $name = $_POST['NAME'];
            $email = $_POST['EMAIL'];
            $card_number = preg_replace('/\s+/', '', $_POST['CARD_NUMBER']);
            $card_exp_month = $_POST['EXPIRATION_MONTH'];
            $card_exp_year = $_POST['EXPIRATION_YEAR'];
            $card_exp_year_month = $card_exp_year . '-' . $card_exp_month;
            $card_cvc = $_POST['SECURITY_CODE'];

            $refID = 'ref' . time();

            $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
            $merchantAuthentication->setName($LOGIN_ID);
            $merchantAuthentication->setTransactionKey($TRANSACTION_KEY);

            // Create the payment data for a credit card
            $creditCard = new AnetAPI\CreditCardType();
            $creditCard->setCardNumber($card_number);
            $creditCard->setExpirationDate($card_exp_year_month);
            $creditCard->setCardCode($card_cvc);

            // Add the payment data to a paymentType object
            $paymentOne = new AnetAPI\PaymentType();
            $paymentOne->setCreditCard($creditCard);

            // Create order information
            $order = new AnetAPI\OrderType();
            $order->setDescription($itemName);

            // Set the customer's identifying information
            $customerData = new AnetAPI\CustomerDataType();
            $customerData->setType("individual");
            $customerData->setEmail($email);

            $ANET_ENV = 'SANDBOX';

            // Create a transaction
            $transactionRequestType = new AnetAPI\TransactionRequestType();
            $transactionRequestType->setTransactionType("authCaptureTransaction");
            $transactionRequestType->setAmount($itemPrice);
            $transactionRequestType->setOrder($order);
            $transactionRequestType->setPayment($paymentOne);
            $transactionRequestType->setCustomer($customerData);
            $request = new AnetAPI\CreateTransactionRequest();
            $request->setMerchantAuthentication($merchantAuthentication);
            $request->setRefId($refID);
            $request->setTransactionRequest($transactionRequestType);
            $controller = new AnetController\CreateTransactionController($request);
            $response = $controller->executeWithApiResponse(constant("\\net\authorize\api\constants\ANetEnvironment::$ANET_ENV"));

            if ($response != null && $response->getMessages()->getResultCode() == "Ok") {
                $tresponse = $response->getTransactionResponse();
                $PAYMENT_INFO = $tresponse->getTransId();
            } else {
                $PAYMENT_INFO = 'Payment Unsuccessful.';
            }
            pre_r($response);
        }
    }
    elseif ($_POST['PK_PAYMENT_TYPE'] == 7) {
        $AMOUNT = $_POST['AMOUNT'];
        $REMAINING_AMOUNT = $_POST['REMAINING_AMOUNT'];
        $WALLET_BALANCE = $_POST['WALLET_BALANCE'];

        if ($_POST['PK_PAYMENT_TYPE_REMAINING'] == 1) {
            require_once("../global/stripe-php-master/init.php");
            \Stripe\Stripe::setApiKey($_POST['SECRET_KEY']);
            $STRIPE_TOKEN = $_POST['token'];
            $REMAINING_AMOUNT = $_POST['REMAINING_AMOUNT'];
            try {
                $charge = \Stripe\Charge::create([
                    'amount' => ($REMAINING_AMOUNT * 100),
                    'currency' => 'usd',
                    'description' => $_POST['NOTE'],
                    'source' => $STRIPE_TOKEN
                ]);
            } catch (Exception $e) {

            }
            if($charge->paid == 1){
                $PAYMENT_INFO = $charge->id;
            }else{
                $PAYMENT_INFO = 'Payment Unsuccessful.';
            }
        }

        $PK_USER_MASTER = $_POST['PK_USER_MASTER'];
        $enrollment_data = $db_account->Execute("SELECT ENROLLMENT_ID FROM `DOA_ENROLLMENT_MASTER` WHERE `PK_ENROLLMENT_MASTER` = ".$_POST['PK_ENROLLMENT_MASTER']);

        $wallet_data = $db_account->Execute("SELECT * FROM DOA_CUSTOMER_WALLET WHERE PK_USER_MASTER = '$PK_USER_MASTER' ORDER BY PK_CUSTOMER_WALLET DESC LIMIT 1");
        $DEBIT_AMOUNT = ($WALLET_BALANCE>$AMOUNT)?$AMOUNT:$WALLET_BALANCE;
        if ($wallet_data->RecordCount() > 0) {
            $INSERT_DATA['CURRENT_BALANCE'] = $wallet_data->fields['CURRENT_BALANCE'] - $DEBIT_AMOUNT;
        }
        $INSERT_DATA['PK_USER_MASTER'] = $PK_USER_MASTER;
        $INSERT_DATA['DEBIT'] = $DEBIT_AMOUNT;
        $INSERT_DATA['DESCRIPTION'] = "Balance debited for payment of enrollment ".$enrollment_data->fields['ENROLLMENT_ID'];
        $INSERT_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
        $INSERT_DATA['CREATED_ON'] = date("Y-m-d H:i");
        db_perform_account('DOA_CUSTOMER_WALLET', $INSERT_DATA, 'insert');

    }else{
        $PAYMENT_INFO = 'Payment Done.';
    }
    $TRANSACTION_TYPE = 'Payment';
    $BILLED_AMOUNT = 0.00;

    /*if($_POST['IS_ONE_TIME_PAY'] == 1) {
        $ENROLLMENT_BILLING_DATA['PK_ENROLLMENT_MASTER'] = $_POST['PK_ENROLLMENT_MASTER'];
        $ENROLLMENT_BILLING_DATA['BILLING_REF'] = $_POST['BILLING_REF'];
        $ENROLLMENT_BILLING_DATA['BILLING_DATE'] = date('Y-m-d', strtotime($_POST['BILLING_DATE']));
        $ENROLLMENT_BILLING_DATA['DOWN_PAYMENT'] = 0;
        $ENROLLMENT_BILLING_DATA['BALANCE_PAYABLE'] = 0;
        $ENROLLMENT_BILLING_DATA['ACTUAL_AMOUNT'] = $ENROLLMENT_BILLING_DATA['TOTAL_AMOUNT'] = $_POST['AMOUNT'];
        $ENROLLMENT_BILLING_DATA['PAYMENT_METHOD'] = 'One Time';
        $ENROLLMENT_BILLING_DATA['NUMBER_OF_PAYMENT'] = 0;
        $ENROLLMENT_BILLING_DATA['FIRST_DUE_DATE'] = date('Y-m-d', strtotime($_POST['BILLING_DATE']));
        $ENROLLMENT_BILLING_DATA['INSTALLMENT_AMOUNT'] = 0;

        db_perform_account('DOA_ENROLLMENT_BILLING', $ENROLLMENT_BILLING_DATA, 'insert');
        $PK_ENROLLMENT_BILLING = $db_account->insert_ID();
        $_POST['PK_ENROLLMENT_BILLING'] = $PK_ENROLLMENT_BILLING;

        $LEDGER_DATA['TRANSACTION_TYPE'] = 'Billing';
        $LEDGER_DATA['ENROLLMENT_LEDGER_PARENT'] = 0;
        $LEDGER_DATA['PK_ENROLLMENT_MASTER'] = $_POST['PK_ENROLLMENT_MASTER'];
        $LEDGER_DATA['PK_ENROLLMENT_BILLING'] = $_POST['PK_ENROLLMENT_BILLING'];
        $LEDGER_DATA['DUE_DATE'] = date('Y-m-d');
        $LEDGER_DATA['BILLED_AMOUNT'] = $_POST['AMOUNT'];
        $LEDGER_DATA['PAID_AMOUNT'] = 0.00;
        $LEDGER_DATA['BALANCE'] = $_POST['AMOUNT'];
        $LEDGER_DATA['IS_PAID'] = 1;
        $LEDGER_DATA['PK_PAYMENT_TYPE'] = $_POST['PK_PAYMENT_TYPE'];
        $LEDGER_DATA['PK_ENROLLMENT_PAYMENT'] = $PK_ENROLLMENT_PAYMENT;
        db_perform_account('DOA_ENROLLMENT_LEDGER', $LEDGER_DATA, 'insert');
        $PK_ENROLLMENT_LEDGER = $db_account->insert_ID();

        $document_library_data = $db_account->Execute("SELECT DOA_DOCUMENT_LIBRARY.DOCUMENT_TEMPLATE FROM `DOA_DOCUMENT_LIBRARY` LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_MASTER.PK_DOCUMENT_LIBRARY=DOA_DOCUMENT_LIBRARY.PK_DOCUMENT_LIBRARY WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = '$_POST[PK_ENROLLMENT_MASTER]'");
        $user_data = $db->Execute("SELECT DOA_USERS.FIRST_NAME, DOA_USERS.LAST_NAME, DOA_USERS.PHONE, DOA_USERS.ADDRESS, DOA_USERS.CITY, DOA_STATES.STATE_NAME, DOA_USERS.ZIP FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER LEFT JOIN DOA_STATES ON DOA_STATES.PK_STATES=DOA_USERS.PK_STATES LEFT JOIN $account_database.DOA_ENROLLMENT_MASTER AS DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER=DOA_USER_MASTER.PK_USER_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = ".$_POST['PK_ENROLLMENT_MASTER']);
        $enrollment_details = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION) AS NUMBER_OF_SESSIONS, SUM(DOA_ENROLLMENT_SERVICE.TOTAL) AS TOTAL, SUM(DOA_ENROLLMENT_SERVICE.DISCOUNT) AS DISCOUNT, SUM(DOA_ENROLLMENT_SERVICE.FINAL_AMOUNT) AS FINAL_AMOUNT, DOA_ENROLLMENT_BILLING.FIRST_DUE_DATE, DOA_ENROLLMENT_BILLING.PAYMENT_TERM, DOA_ENROLLMENT_BILLING.NUMBER_OF_PAYMENT, DOA_ENROLLMENT_BILLING.INSTALLMENT_AMOUNT FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_BILLING ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = ".$_POST['PK_ENROLLMENT_MASTER']);
        $html_template = $document_library_data->fields['DOCUMENT_TEMPLATE'];
        $html_template = str_replace('{FULL_NAME}', $user_data->fields['FIRST_NAME']." ".$user_data->fields['LAST_NAME'], $html_template);
        $html_template = str_replace('{STREET_ADD}', $user_data->fields['ADDRESS'], $html_template);
        $html_template = str_replace('{CITY}', $user_data->fields['CITY'], $html_template);
        $html_template = str_replace('{STATE}', $user_data->fields['STATE_NAME'], $html_template);
        $html_template = str_replace('{ZIP}', $user_data->fields['ZIP'], $html_template);
        $html_template = str_replace('{CELL_PHONE}', $user_data->fields['PHONE'], $html_template);
        $TYPE_OF_ENROLLMENT='';
        $SERVICE_DETAILS='';
        $PVT_LESSONS='';
        $TUITION='';
        $DISCOUNT='';
        $BAL_DUE='';
        $enrollment_service_data = $db_account->Execute("SELECT * FROM DOA_ENROLLMENT_SERVICE LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = '$_POST[PK_ENROLLMENT_MASTER]'");
        $enrollment_count = $db_account->Execute("SELECT COUNT(PK_USER_MASTER) AS ENROLLMENT_COUNT FROM DOA_ENROLLMENT_MASTER WHERE PK_USER_MASTER=".$enrollment_service_data->fields['PK_USER_MASTER']);
        $number = $enrollment_count->RecordCount() > 0 ? $enrollment_count->fields['ENROLLMENT_COUNT'] : '';
        $ends = array('th','st','nd','rd','th','th','th','th','th','th');
        $abbreviation = ($number % 100) >= 11 && ($number % 100) <= 13 ? $number . 'th' : $number . $ends[$number % 10];
        if(empty($enrollment_service_data->fields['ENROLLMENT_NAME'])){
            $enrollment_name = $abbreviation;
        }else{
            $enrollment_name = $enrollment_service_data->fields['ENROLLMENT_NAME']." - ".$abbreviation;
        }
        while (!$enrollment_service_data->EOF) {
            $TYPE_OF_ENROLLMENT = $enrollment_name;
            $SERVICE_DETAILS .= $enrollment_service_data->fields['SERVICE_DETAILS']."<br>";
            $PVT_LESSONS .= $enrollment_service_data->fields['NUMBER_OF_SESSION']."<br>";
            $TUITION .= $enrollment_service_data->fields['TOTAL']."<br>";
            $DISCOUNT .= $enrollment_service_data->fields['DISCOUNT']."<br>";
            $BAL_DUE .= $enrollment_service_data->fields['FINAL_AMOUNT']."<br>";
            $enrollment_service_data->MoveNext();
        }
        $html_template = str_replace('{TYPE_OF_ENROLLMENT}', $TYPE_OF_ENROLLMENT, $html_template);
        $html_template = str_replace('{SERVICE_DETAILS}', $SERVICE_DETAILS, $html_template);
        $html_template = str_replace('{PVT_LESSONS}', $PVT_LESSONS, $html_template);
        $html_template = str_replace('{TUITION}', $TUITION, $html_template);
        $html_template = str_replace('{DISCOUNT}', $DISCOUNT, $html_template);
        $html_template = str_replace('{BAL_DUE}', $BAL_DUE, $html_template);
        $html_template = str_replace('{MISC_SERVICES}', '0', $html_template);
        $html_template = str_replace('{TUITION_COST}', '0', $html_template);
        $html_template = str_replace('{TOTAL}', $enrollment_details->fields['TOTAL'], $html_template);
        $html_template = str_replace('{CASH_PRICE}', $enrollment_details->fields['FINAL_AMOUNT'], $html_template);
        $html_template = str_replace('{FIRST_DATE}', date('m-d-Y', strtotime($_POST['BILLING_DATE'])), $html_template);
        $html_template = str_replace('{DOWN_PAYMENTS}',  $enrollment_details->fields['FINAL_AMOUNT'], $html_template);
        $html_template = str_replace('{SCHEDULE_AMOUNT}', $_POST['BALANCE_PAYABLE'], $html_template);
        $html_template = str_replace('{PAYMENT_NAME}', $_POST['PAYMENT_TERM'], $html_template);
        $html_template = str_replace('{NO_AMT_PAYMENT}', $_POST['NUMBER_OF_PAYMENT'], $html_template);
        //$html_template = str_replace('{STARTING_DATE}', date('m-d-Y', strtotime($_POST['BILLING_DATE'])), $html_template);
        $html_template = str_replace('{STARTING_DATE}', '-', $html_template);
        $ENROLLMENT_MASTER_DATA['AGREEMENT_PDF_LINK'] = generatePdf($html_template);
        db_perform_account('DOA_ENROLLMENT_MASTER', $ENROLLMENT_MASTER_DATA, 'update'," PK_ENROLLMENT_MASTER =  '$_POST[PK_ENROLLMENT_MASTER]'");
    }*/


    $enrollmentServiceData = $db_account->Execute("SELECT * FROM `DOA_ENROLLMENT_SERVICE` WHERE `PK_ENROLLMENT_MASTER` = ".$_POST['PK_ENROLLMENT_MASTER']);
    $enrollmentBillingData = $db_account->Execute("SELECT * FROM `DOA_ENROLLMENT_BILLING` WHERE `PK_ENROLLMENT_MASTER` = ".$_POST['PK_ENROLLMENT_MASTER']);
    $ACTUAL_AMOUNT = $enrollmentBillingData->fields['TOTAL_AMOUNT'];
    while (!$enrollmentServiceData->EOF) {
        $servicePercent = ($enrollmentServiceData->fields['FINAL_AMOUNT']*100)/$ACTUAL_AMOUNT;
        $serviceAmount = ($_POST['AMOUNT']*$servicePercent)/100;

        $ENROLLMENT_SERVICE_UPDATE_DATA['TOTAL_AMOUNT_PAID'] = $enrollmentServiceData->fields['TOTAL_AMOUNT_PAID']+$serviceAmount;
        db_perform_account('DOA_ENROLLMENT_SERVICE', $ENROLLMENT_SERVICE_UPDATE_DATA, 'update'," PK_ENROLLMENT_SERVICE = ".$enrollmentServiceData->fields['PK_ENROLLMENT_SERVICE']);

        markAppointmentPaid($enrollmentServiceData->fields['PK_ENROLLMENT_SERVICE']);

        $enrollmentServiceData->MoveNext();
    }

    savePercentageData($_POST['PK_ENROLLMENT_MASTER'], $_POST['AMOUNT']);

    /*$enrollment_balance = $db_account->Execute("SELECT * FROM `DOA_ENROLLMENT_BALANCE` WHERE PK_ENROLLMENT_MASTER = '$_POST[PK_ENROLLMENT_MASTER]'");
    if ($enrollment_balance->RecordCount() > 0){
        $ENROLLMENT_BALANCE_DATA['TOTAL_BALANCE_PAID'] = $enrollment_balance->fields['TOTAL_BALANCE_PAID']+$_POST['AMOUNT'];
        $ENROLLMENT_BALANCE_DATA['EDITED_BY']	= $_SESSION['PK_USER'];
        $ENROLLMENT_BALANCE_DATA['EDITED_ON'] = date("Y-m-d H:i");
        db_perform_account('DOA_ENROLLMENT_BALANCE', $ENROLLMENT_BALANCE_DATA, 'update'," PK_ENROLLMENT_MASTER =  '$_POST[PK_ENROLLMENT_MASTER]'");
    }else{
        $ENROLLMENT_BALANCE_DATA['PK_ENROLLMENT_MASTER'] = $_POST['PK_ENROLLMENT_MASTER'];
        $ENROLLMENT_BALANCE_DATA['TOTAL_BALANCE_PAID'] = $_POST['AMOUNT'];
        $ENROLLMENT_BALANCE_DATA['CREATED_BY']  = $_SESSION['PK_USER'];
        $ENROLLMENT_BALANCE_DATA['CREATED_ON']  = date("Y-m-d H:i");
        db_perform_account('DOA_ENROLLMENT_BALANCE', $ENROLLMENT_BALANCE_DATA, 'insert');
    }*/

    $business_details = $db->Execute("SELECT * FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER=".$_SESSION['PK_ACCOUNT_MASTER']);
    $user_data = $db->Execute("SELECT DOA_USERS.FIRST_NAME, DOA_USERS.LAST_NAME, DOA_USERS.PHONE, DOA_USERS.ADDRESS, DOA_USERS.CITY, DOA_STATES.STATE_NAME, DOA_USERS.ZIP, DOA_LOCATION.LOCATION_NAME FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER LEFT JOIN DOA_STATES ON DOA_STATES.PK_STATES=DOA_USERS.PK_STATES LEFT JOIN DOA_USER_LOCATION ON DOA_USER_LOCATION.PK_USER=DOA_USERS.PK_USER LEFT JOIN DOA_LOCATION ON DOA_LOCATION.PK_LOCATION=DOA_USER_LOCATION.PK_LOCATION LEFT JOIN $account_database.DOA_ENROLLMENT_MASTER AS DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER=DOA_USER_MASTER.PK_USER_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = ".$_POST['PK_ENROLLMENT_MASTER']);
    $enrollment_billing_data = $db_account->Execute("SELECT * FROM DOA_ENROLLMENT_BILLING WHERE PK_ENROLLMENT_MASTER=".$_POST['PK_ENROLLMENT_MASTER']);
    $enrollment_ledger_data = $db_account->Execute("SELECT DOA_ENROLLMENT_LEDGER.* , DOA_PAYMENT_TYPE.PAYMENT_TYPE FROM DOA_ENROLLMENT_LEDGER LEFT JOIN $master_database.DOA_PAYMENT_TYPE AS DOA_PAYMENT_TYPE ON DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE=DOA_ENROLLMENT_LEDGER.PK_PAYMENT_TYPE WHERE PK_ENROLLMENT_LEDGER=".$ENROLLMENT_LEDGER_PARENT);
    $enrollment_payment_data = $db_account->Execute("SELECT * FROM DOA_ENROLLMENT_PAYMENT WHERE PK_ENROLLMENT_MASTER=".$_POST['PK_ENROLLMENT_MASTER']);
    $enrollment_payment_type = $db->Execute("SELECT PAYMENT_TYPE FROM DOA_PAYMENT_TYPE WHERE PK_PAYMENT_TYPE=".$_POST['PK_PAYMENT_TYPE']);
    if($_POST['PK_PAYMENT_TYPE']==2){
        $PAYMENT_TYPE = $enrollment_payment_type->fields['PAYMENT_TYPE'].' : '.$_POST['CHECK_NUMBER'];
    }else{
        $PAYMENT_TYPE = $enrollment_payment_type->fields['PAYMENT_TYPE'];
    }
    $html_template_receipt = str_replace('{BUSINESS_NAME}', $business_details->fields['BUSINESS_NAME'], $html_template_receipt);
    $html_template_receipt = str_replace('{FULL_NAME}', $user_data->fields['FIRST_NAME']." ".$user_data->fields['LAST_NAME'], $html_template_receipt);
    $html_template_receipt = str_replace('{LOCATION_NAME}', $user_data->fields['LOCATION_NAME'], $html_template_receipt);
    $html_template_receipt = str_replace('{STATE}', $user_data->fields['STATE_NAME'], $html_template_receipt);
    $html_template_receipt = str_replace('{ZIP}', $user_data->fields['ZIP'], $html_template_receipt);
    $html_template_receipt = str_replace('{PHONE}', $user_data->fields['PHONE'], $html_template_receipt);
    $html_template_receipt = str_replace('{BILLING_REF}', $enrollment_billing_data->fields['BILLING_REF'], $html_template_receipt);
    $html_template_receipt = str_replace('{PAYMENT_METHOD}', $PAYMENT_TYPE, $html_template_receipt);
    $html_template_receipt = str_replace('{CARD_NUMBER}', $payment_info, $html_template_receipt);
    $html_template_receipt = str_replace('{DETAILS}', $_POST['AMOUNT'], $html_template_receipt);
    $html_template_receipt = str_replace('{AMOUNT}', $enrollment_ledger_data->fields['BILLED_AMOUNT'], $html_template_receipt);
    $html_template_receipt = str_replace('{TOTAL}', $_POST['AMOUNT'], $html_template_receipt);
    $html_template_receipt = str_replace('{PAYMENT_DATE}', date('Y-m-d'), $html_template_receipt);

    $LEDGER_DATA['TRANSACTION_TYPE'] = 'Payment';
    $LEDGER_DATA['ENROLLMENT_LEDGER_PARENT'] = $ENROLLMENT_LEDGER_PARENT;
    $LEDGER_DATA['PK_ENROLLMENT_MASTER'] = $_POST['PK_ENROLLMENT_MASTER'];
    $LEDGER_DATA['PK_ENROLLMENT_BILLING'] = $_POST['PK_ENROLLMENT_BILLING'];
    $LEDGER_DATA['DUE_DATE'] = date('Y-m-d');
    $LEDGER_DATA['BILLED_AMOUNT'] = 0.00;
    $LEDGER_DATA['PAID_AMOUNT'] = $_POST['AMOUNT'];
    $LEDGER_DATA['BALANCE'] = 0.00;
    $LEDGER_DATA['IS_PAID'] = 1;
    $LEDGER_DATA['STATUS'] = 'A';
    db_perform_account('DOA_ENROLLMENT_LEDGER', $LEDGER_DATA, 'insert');
    $PK_ENROLLMENT_LEDGER = $db_account->insert_ID();

    $PAYMENT_DATA['PK_ENROLLMENT_MASTER'] = $_POST['PK_ENROLLMENT_MASTER'];
    $PAYMENT_DATA['PK_ENROLLMENT_BILLING'] = $_POST['PK_ENROLLMENT_BILLING'];
    $PAYMENT_DATA['PK_PAYMENT_TYPE'] = $_POST['PK_PAYMENT_TYPE'];
    $PAYMENT_DATA['AMOUNT'] = $_POST['AMOUNT'];
    $PAYMENT_DATA['PK_ENROLLMENT_LEDGER'] = $PK_ENROLLMENT_LEDGER;
    $TYPE = 'Payment';
    if ($_POST['PK_PAYMENT_TYPE'] == 7) {
        $TYPE = 'Remaining';
        //$PAYMENT_DATA['REMAINING_AMOUNT'] = $_POST['REMAINING_AMOUNT'];
        /*$PAYMENT_DATA['CHECK_NUMBER'] = $_POST['CHECK_NUMBER_REMAINING'];
        $PAYMENT_DATA['CHECK_DATE'] = date('Y-m-d', strtotime($_POST['CHECK_DATE_REMAINING']));*/
    } elseif($_POST['PK_PAYMENT_TYPE'] == 2) {
        //$PAYMENT_DATA['REMAINING_AMOUNT'] = 0.00;
        $PAYMENT_INFO_ARRAY = ['CHECK_NUMBER' => $_POST['CHECK_NUMBER'], 'CHECK_DATE' => date('Y-m-d', strtotime($_POST['CHECK_DATE']))];
        $PAYMENT_INFO = json_encode($PAYMENT_INFO_ARRAY);
        /*$PAYMENT_DATA['CHECK_NUMBER'] = $_POST['CHECK_NUMBER'];
        $PAYMENT_DATA['CHECK_DATE'] = date('Y-m-d', strtotime($_POST['CHECK_DATE']));*/
    }
    $PAYMENT_DATA['TYPE'] = $TYPE;
    $PAYMENT_DATA['NOTE'] = $_POST['NOTE'];
    $PAYMENT_DATA['PAYMENT_DATE'] = date('Y-m-d');
    $PAYMENT_DATA['PAYMENT_INFO'] = $PAYMENT_INFO;
    $PAYMENT_DATA['PAYMENT_STATUS'] = 'Success';
    $PAYMENT_DATA['RECEIPT_PDF_LINK'] = generateReceiptPdf($html_template_receipt);

    /*if($_POST['PK_PAYMENT_TYPE'] == 1 && $_POST['PAYMENT_GATEWAY'] == 'Authorized.net') {
        $PAYMENT_DATA['NAME'] = $_POST['NAME'];
        $PAYMENT_DATA['CARD_NUMBER'] = $_POST['CARD_NUMBER'];
        $PAYMENT_DATA['EXPIRATION_DATE'] = $_POST['EXPIRATION_MONTH'] . "/" . $_POST['EXPIRATION_YEAR'];
        $PAYMENT_DATA['SECURITY_CODE'] = $_POST['SECURITY_CODE'];
    }*/
    db_perform_account('DOA_ENROLLMENT_PAYMENT', $PAYMENT_DATA, 'insert');

    $LEDGER_UPDATE_DATA['IS_PAID'] = 1;
    db_perform_account('DOA_ENROLLMENT_LEDGER', $LEDGER_UPDATE_DATA, 'update', " PK_ENROLLMENT_LEDGER =  '$ENROLLMENT_LEDGER_PARENT'");

    markAdhocAppointmentNormal($_POST['PK_ENROLLMENT_MASTER']);

    header('location:'.$header);
}

function savePercentageData($PK_ENROLLMENT_MASTER, $AMOUNT){
    global $db_account;
    $row = $db_account->Execute("SELECT SERVICE_PROVIDER_ID, SERVICE_PROVIDER_PERCENTAGE FROM DOA_ENROLLMENT_SERVICE_PROVIDER WHERE PK_ENROLLMENT_MASTER=".$PK_ENROLLMENT_MASTER);
    while (!$row->EOF) {
        $PERCENTAGE_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
        $PERCENTAGE_DATA['SERVICE_PROVIDER_ID'] = $row->fields['SERVICE_PROVIDER_ID'];
        $PERCENTAGE_DATA['PERCENTAGE_AMOUNT'] = ($AMOUNT * $row->fields['SERVICE_PROVIDER_PERCENTAGE']) / 100;
        db_perform_account('DOA_SERVICE_PROVIDER_AMOUNT', $PERCENTAGE_DATA, 'insert');
        $row->MoveNext();
    }
}

function generateReceiptPdf($html){
    require_once('../../global/vendor/autoload.php');

    $mpdf = new Mpdf();
    $mpdf->WriteHTML($html);

    $file_name = "enrollment_receipt_pdf_".time().".pdf";
    $mpdf->Output("../../uploads/enrollment_pdf/".$file_name, 'F');

    return $file_name;
}
function generatePdf($html){
    require_once('../../global/vendor/autoload.php');

    $mpdf = new Mpdf();
    $mpdf->WriteHTML($html);
    $mpdf->keep_table_proportions = true;
    $mpdf->AddPage();

    $file_name = "enrollment_pdf_".time().".pdf";
    $mpdf->Output("../../uploads/enrollment_pdf/".$file_name, 'F');

    return $file_name;
}