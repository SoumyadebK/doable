<?php
if ($_SERVER['HTTP_HOST'] == 'localhost') {
    require_once("../global/config.php");
    require_once("voice_agent_helper.php");
    require_once("../global/vendor/twilio/sdk/src/Twilio/autoload.php");
} else {
    require_once("/var/www/html/global/config.php");
    require_once("/var/www/html/voice_agent/voice_agent_helper.php");
    require_once("/var/www/html/global/vendor/twilio/sdk/src/Twilio/autoload.php");
}

use Twilio\Rest\Client;

global $db;

$PK_LEADS = $_GET['PK_LEADS'] ?? 0;

if ($PK_LEADS > 0) {
    $leadsData = $db->Execute("SELECT * FROM DOA_LEADS WHERE PK_LEADS = " . intval($PK_LEADS));
    $PK_LOCATION = $leadsData->fields['PK_LOCATION'];

    [$SID, $TOKEN, $TWILIO_PHONE_NO] = getTwilioSettingData($PK_LOCATION);

    $phone_number = $leadsData->fields['PHONE'];
    $to = '+1' . preg_replace('/\D/', '', $phone_number);
    $answerUrl = 'https://doable.net/voice_agent/twilio_voice_initial.php'; // public HTTPS

    try {
        $client = new Client($SID, $TOKEN);

        $call = $client->calls->create(
            $to,
            $TWILIO_PHONE_NO,
            [
                'url' => $answerUrl // TwiML or webhook that returns TwiML
            ]
        );
        $callSid = $call->sid;

        if ($callSid && $PK_LEADS) {
            $CALL_DETAILS['PK_LEADS'] = $PK_LEADS;
            $CALL_DETAILS['TO_NUMBER'] = $to;
            $CALL_DETAILS['CALL_SID'] = $callSid;
            $CALL_DETAILS['STEP'] = 'initiated';
            $CALL_DETAILS['CREATED_AT'] = date('Y-m-d H:i:s');
            db_perform('DOA_CALL_DETAILS', $CALL_DETAILS);

            $LEADS_UPDATE_DATA = [];
            $leadStatus = $db->Execute("SELECT PK_LEAD_STATUS FROM DOA_LEAD_STATUS WHERE LEAD_STATUS = '1st contact' AND PK_ACCOUNT_MASTER = " . $PK_ACCOUNT_MASTER . " LIMIT 1");
            if ($leadStatus->RecordCount() > 0) {
                $LEADS_UPDATE_DATA['PK_LEAD_STATUS'] = $leadStatus->fields['PK_LEAD_STATUS'];
            }
            $LEADS_UPDATE_DATA['IS_CALLED'] = 1;
            db_perform('DOA_LEADS', $LEADS_UPDATE_DATA, 'update', " PK_LEADS = " . $PK_LEADS);
        }

        echo "success";
        exit;
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
        exit;
    }
} else {
    echo "success";
}
