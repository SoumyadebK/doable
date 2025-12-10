<?php
if ($_SERVER['HTTP_HOST'] == 'localhost') {
    require_once("../global/config.php");
    require_once("detect_user_slot.php");
    require_once("../global/vendor/twilio/sdk/src/Twilio/autoload.php");
} else {
    require_once("/var/www/html/global/config.php");
    require_once("/var/www/html/voice_agent/detect_user_slot.php");
    require_once("/var/www/html/global/vendor/twilio/sdk/src/Twilio/autoload.php");
}

use Twilio\Rest\Client;

global $db;

$PK_LEADS = $_GET['PK_LEADS'] ?? 37;

if (!empty($_POST) && !empty($_POST['phone_number'])) {
    // Customer number & callback URL (the webhook Twilio will request when call is answered)
    $to = '+1' . preg_replace('/\D/', '', $_POST['phone_number']);
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
        }

        echo "Call started with call ID: " . $call->sid;
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
        exit;
    }
}
?>

<html>

<head>
    <title>AI Call Test</title>
</head>

<body>
    <h1>AI Call Test</h1>
    <form method="POST" action="">
        <label for="phone_number">Enter Phone Number:</label><br><br>
        <input type="text" id="phone_number" name="phone_number" style="width: 250px; height: 30px;" required />
        <br /><br />
        <input type="submit" value="Initiate Call" style="width: 100px; height: 30px;" />
    </form>
</body>

</html>