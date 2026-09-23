<?php

use Twilio\Rest\Client;

if ($_SERVER['HTTP_HOST'] == 'localhost') {
    require_once("global/config.php");
    require_once('global/common_functions_account.php');
    require_once("global/vendor/twilio/sdk/src/Twilio/autoload.php");
    require_once('global/phpmailer/class.phpmailer.php');
} else {
    require_once("/var/www/html/global/config.php");
    require_once('/var/www/html/global/common_functions_account.php');
    require_once("/var/www/html/global/vendor/twilio/sdk/src/Twilio/autoload.php");
    require_once('/var/www/html/global/phpmailer/class.phpmailer.php');
}

global $db;
$all_location = $db->Execute("SELECT DOA_LOCATION.PK_LOCATION, DOA_LOCATION.LOCATION_NAME, DOA_LOCATION.PK_ACCOUNT_MASTER, DOA_LOCATION.HOUR, DOA_ACCOUNT_MASTER.DB_NAME, DOA_TIMEZONE.TIMEZONE FROM DOA_LOCATION LEFT JOIN DOA_TIMEZONE ON DOA_LOCATION.PK_TIMEZONE = DOA_TIMEZONE.PK_TIMEZONE LEFT JOIN DOA_ACCOUNT_MASTER ON DOA_LOCATION.PK_ACCOUNT_MASTER = DOA_ACCOUNT_MASTER.PK_ACCOUNT_MASTER WHERE DOA_ACCOUNT_MASTER.ACTIVE = 1 AND DOA_LOCATION.ACTIVE = 1");
while (!$all_location->EOF) {
    date_default_timezone_set($all_location->fields['TIMEZONE']);

    $DB_NAME = $all_location->fields['DB_NAME'];
    $db_account = new queryFactory();
    if ($_SERVER['HTTP_HOST'] == 'localhost') {
        $conn1 = $db_account->connect('localhost', 'root', '', $DB_NAME);
        $http_path = 'http://localhost/doable/';
    } else {
        $conn1 = $db_account->connect('localhost', 'root', 'b54eawxj5h8ev', $DB_NAME);
        $http_path = 'https://doable.net/';
    }
    if ($db_account->error_number) {
        die("Connection Error");
    }

    $PK_LOCATION = $all_location->fields['PK_LOCATION'];
    $PK_ACCOUNT_MASTER = $all_location->fields['PK_ACCOUNT_MASTER'];
    $date = date("Y-m-d H:i:0");

    $all_campaign = $db_account->Execute("SELECT * FROM `DOA_MARKET_CAMPAIGN` WHERE PK_LOCATION = '$PK_LOCATION' AND SCHEDULE_DATETIME = '$date' AND ACTIVE = 1");
    while (!$all_campaign->EOF) {
        $PK_MARKET_CAMPAIGN = $all_campaign->fields['PK_MARKET_CAMPAIGN'];
        triggerCampaign($PK_MARKET_CAMPAIGN);
        $all_campaign->MoveNext();
    }

    $all_location->MoveNext();
}

function triggerCampaign($PK_MARKET_CAMPAIGN)
{
    global $db;
    global $db_account;
    global $account_database;
    $campaign_res = $db_account->Execute("SELECT * FROM DOA_MARKET_CAMPAIGN WHERE PK_MARKET_CAMPAIGN = " . intval($PK_MARKET_CAMPAIGN));
    if ($campaign_res->RecordCount() > 0) {
        $PK_LOCATION = $campaign_res->fields['PK_LOCATION'];
        $CAMPAIGN_NAME = $campaign_res->fields['CAMPAIGN_NAME'];
        $SUBJECT = $campaign_res->fields['SUBJECT'];
        $CONTENT = $campaign_res->fields['CONTENT'];

        $REMINDER_TYPE = $campaign_res->fields['REMINDER_TYPE'];
        $REMINDER_TYPES = explode(',', $REMINDER_TYPE);

        $OPERATION = $campaign_res->fields['OPERATION'];
        $OPERATIONS = explode(',', $OPERATION);

        if (in_array('inactive_customers', $OPERATIONS) || in_array('active_customers', $OPERATIONS)) {
            $STATUS_CONDITION = ' ';
            if (in_array('inactive_customers', $OPERATIONS) && !in_array('active_customers', $OPERATIONS)) {
                $STATUS_CONDITION = ' AND DOA_USERS.ACTIVE = 0';
            } elseif (!in_array('inactive_customers', $OPERATIONS) && in_array('active_customers', $OPERATIONS)) {
                $STATUS_CONDITION = ' AND DOA_USERS.ACTIVE = 1';
            }
            $all_active_inactive_customers = $db->Execute("SELECT DISTINCT DOA_USERS.PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS CUSTOMER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.PHONE, DOA_LOCATION.LOCATION_NAME FROM `DOA_USERS` INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER=DOA_USER_MASTER.PK_USER LEFT JOIN DOA_LOCATION ON DOA_LOCATION.PK_LOCATION = DOA_USER_MASTER.PRIMARY_LOCATION_ID WHERE (DOA_USERS.IS_DELETED = 0 || DOA_USERS.IS_DELETED IS NULL) $STATUS_CONDITION AND DOA_USER_MASTER.PRIMARY_LOCATION_ID = $PK_LOCATION");
            while (!$all_active_inactive_customers->EOF) {
                $CUSTOMER_NAME = $all_active_inactive_customers->fields['CUSTOMER_NAME'];
                $EMAIL_ID = $all_active_inactive_customers->fields['EMAIL_ID'];
                $PHONE = $all_active_inactive_customers->fields['PHONE'];
                $LOCATION_NAME = $all_active_inactive_customers->fields['LOCATION_NAME'];

                $saved_message = $CONTENT;

                $replacements = [
                    '<span class="variable-badge" contenteditable="false">Student Name</span>' => $CUSTOMER_NAME,
                    '<span class="variable-badge" contenteditable="false">Campaign Name</span>' => $CAMPAIGN_NAME,
                    '<span class="variable-badge" contenteditable="false">Location</span>' => $LOCATION_NAME,
                ];

                $MESSAGE = str_replace(array_keys($replacements), array_values($replacements), $saved_message);

                if (in_array('email', $REMINDER_TYPES)) {
                    sendEmail($PK_LOCATION, $MESSAGE, $EMAIL_ID, $LOCATION_NAME, $SUBJECT);
                }
                if (in_array('text', $REMINDER_TYPES)) {
                    sendTextMessage($PK_LOCATION, $MESSAGE, $PHONE);
                }

                $all_active_inactive_customers->MoveNext();
            }
        }
        if (in_array('tags', $OPERATIONS)) {
            $TAGS = $campaign_res->fields['TAGS'];
            $all_tags_customer = $db->Execute("SELECT DISTINCT DOA_USERS.PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS CUSTOMER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.PHONE, DOA_LOCATION.LOCATION_NAME FROM `DOA_USERS` INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER=DOA_USER_MASTER.PK_USER LEFT JOIN $account_database.DOA_USER_TAG AS DOA_USER_TAG ON DOA_USER_MASTER.PK_USER_MASTER = DOA_USER_TAG.PK_USER_MASTER LEFT JOIN DOA_LOCATION ON DOA_LOCATION.PK_LOCATION = DOA_USER_MASTER.PRIMARY_LOCATION_ID WHERE (DOA_USERS.IS_DELETED = 0 || DOA_USERS.IS_DELETED IS NULL) AND DOA_USER_TAG.PK_TAG IN ($TAGS) AND DOA_USER_MASTER.PRIMARY_LOCATION_ID = $PK_LOCATION");
            while (!$all_tags_customer->EOF) {
                $CUSTOMER_NAME = $all_tags_customer->fields['CUSTOMER_NAME'];
                $EMAIL_ID = $all_tags_customer->fields['EMAIL_ID'];
                $PHONE = $all_tags_customer->fields['PHONE'];
                $LOCATION_NAME = $all_tags_customer->fields['LOCATION_NAME'];

                $saved_message = $CONTENT;

                $replacements = [
                    '<span class="variable-badge" contenteditable="false">Student Name</span>' => $CUSTOMER_NAME,
                    '<span class="variable-badge" contenteditable="false">Campaign Name</span>' => $CAMPAIGN_NAME,
                    '<span class="variable-badge" contenteditable="false">Location</span>' => $LOCATION_NAME,
                ];

                $MESSAGE = str_replace(array_keys($replacements), array_values($replacements), $saved_message);

                if (in_array('email', $REMINDER_TYPES)) {
                    sendEmail($PK_LOCATION, $MESSAGE, $EMAIL_ID, $LOCATION_NAME, $SUBJECT);
                }
                if (in_array('text', $REMINDER_TYPES)) {
                    sendTextMessage($PK_LOCATION, $MESSAGE, $PHONE);
                }

                $all_tags_customer->MoveNext();
            }
        }
        if (in_array('leads', $OPERATIONS)) {
            $PK_LEAD_STATUS = $campaign_res->fields['LEADS'];
            $all_leads = $db->Execute("SELECT DISTINCT 
                                        DOA_LEADS.PK_LEADS, 
                                        CONCAT(DOA_LEADS.FIRST_NAME, ' ', DOA_LEADS.LAST_NAME) AS CUSTOMER_NAME, 
                                        DOA_LEADS.PHONE, 
                                        DOA_LEADS.EMAIL_ID, 
                                        DOA_LOCATION.LOCATION_NAME
                                    FROM `DOA_LEADS` 
                                    INNER JOIN DOA_LOCATION 
                                        ON DOA_LOCATION.PK_LOCATION = DOA_LEADS.PK_LOCATION 
                                    LEFT JOIN DOA_LEAD_STATUS AS LS 
                                        ON DOA_LEADS.PK_LEAD_STATUS = LS.PK_LEAD_STATUS 
                                    WHERE DOA_LEADS.PK_LEAD_STATUS IN ($PK_LEAD_STATUS)
                                        AND DOA_LEADS.PK_LOCATION = $PK_LOCATION
                                        AND DOA_LEADS.ACTIVE = 1");
            while (!$all_leads->EOF) {
                $CUSTOMER_NAME = $all_leads->fields['CUSTOMER_NAME'];
                $EMAIL_ID = $all_leads->fields['EMAIL_ID'];
                $PHONE = $all_leads->fields['PHONE'];
                $LOCATION_NAME = $all_leads->fields['LOCATION_NAME'];

                $saved_message = $CONTENT;

                $replacements = [
                    '<span class="variable-badge" contenteditable="false">Student Name</span>' => $CUSTOMER_NAME,
                    '<span class="variable-badge" contenteditable="false">Campaign Name</span>' => $CAMPAIGN_NAME,
                    '<span class="variable-badge" contenteditable="false">Location</span>' => $LOCATION_NAME,
                ];

                $MESSAGE = str_replace(array_keys($replacements), array_values($replacements), $saved_message);

                if (in_array('email', $REMINDER_TYPES)) {
                    sendEmail($PK_LOCATION, $MESSAGE, $EMAIL_ID, $LOCATION_NAME, $SUBJECT);
                }
                if (in_array('text', $REMINDER_TYPES)) {
                    sendTextMessage($PK_LOCATION, $MESSAGE, $PHONE);
                }

                $all_leads->MoveNext();
            }
        }
    }
}

function sendEmail($PK_LOCATION, $MESSAGE, $EMAIL_ID, $LOCATION_NAME, $SUBJECT)
{
    $locationSmtpSetting = getLocationSmtpSetting($PK_LOCATION);

    $hostname   = $locationSmtpSetting['SMTP_HOST'];
    $port       = $locationSmtpSetting['SMTP_PORT'];
    $userName   = $locationSmtpSetting['SMTP_USERNAME'];
    $SendingPwd = $locationSmtpSetting['SMTP_PASSWORD'];

    $To = $EMAIL_ID; //'deb.soumya93@gmail.com'; // TODO: switch back to $EMAIL_ID after testing

    // Clean the subject: decode HTML entities (e.g. &mdash; &amp;), strip tags/newlines
    $SUBJECT = html_entity_decode(strip_tags((string)$SUBJECT), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $SUBJECT = trim(preg_replace('/\s+/', ' ', $SUBJECT));
    if ($SUBJECT === '') {
        error_log("sendEmail: empty subject for location $PK_LOCATION");
        $SUBJECT = '(No subject)'; // or return false if you'd rather block it
    }

    // Make sure the HTML body declares UTF-8
    if (stripos($MESSAGE, '<html') === false) {
        $MESSAGE = '<!DOCTYPE html><html><head><meta charset="UTF-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '</head><body>' . $MESSAGE . '</body></html>';
    }

    $mail = new PHPMailer(true); // true = throw exceptions

    try {
        $mail->IsSMTP();
        $mail->SMTPDebug  = 0;          // set to 2 while debugging
        $mail->Debugoutput = 'html';
        $mail->Host       = $hostname;
        $mail->Port       = $port;
        $mail->SMTPSecure = ($port == 465) ? 'ssl' : 'tls';
        $mail->SMTPAuth   = true;
        $mail->Username   = $userName;
        $mail->Password   = $SendingPwd;

        // Encoding: fixes emoji / special characters and non-ASCII subject
        $mail->CharSet  = 'UTF-8';
        $mail->Encoding = 'base64';

        $mail->setFrom($userName, $LOCATION_NAME);
        $mail->addAddress($To, $LOCATION_NAME);

        $mail->Subject = encodeSubject($SUBJECT);

        $mail->IsHTML(true);
        $mail->Body    = $MESSAGE;
        $mail->AltBody = htmlToPlainText($MESSAGE); // proper plain-text version

        $mail->send();
        echo 'Email sent successfully';
        return true;
    } catch (Exception $e) {   // use phpmailerException if you're on PHPMailer 5.x
        echo 'Mailer Error: ' . $mail->ErrorInfo;
        return false;
    }
}

function sendTextMessage($PK_LOCATION, $MESSAGE, $PHONE)
{
    [$SID, $TOKEN, $TWILIO_PHONE_NO] = getTwilioSettingData($PK_LOCATION);

    try {
        $client = new Client($SID, $TOKEN);
        $response = $client->messages->create(
            '+1' . $PHONE,
            [
                'from' => $TWILIO_PHONE_NO,
                'body' => $MESSAGE //$msg->fields['CONTENT']
            ]
        );
    } catch (\Twilio\Exceptions\TwilioException $e) {
        echo 'Error : ' . $e->getMessage() . "<br>";
    }
}
