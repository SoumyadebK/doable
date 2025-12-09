<?php
require_once("/var/www/html/global/config.php");
global $db;

header("Content-Type: text/xml");

$callSid = $_POST['CallSid'] ?? '';
$handleDateUrl = "https://doable.net/voice_agent/twilio_handle_date.php";

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";

$callDetails = $db->Execute("SELECT * FROM DOA_CALL_DETAILS WHERE CALL_SID = '" . $callSid . "' LIMIT 1");
$PK_LEADS = $callDetails->fields['PK_LEADS'] ?? null;

$locationSettings = $db->Execute("SELECT DOA_LOCATION.PK_LOCATION, DOA_LOCATION.PK_ACCOUNT_MASTER, DOA_LOCATION.LOCATION_NAME FROM DOA_LOCATION INNER JOIN DOA_LEADS ON DOA_LOCATION.PK_LOCATION = DOA_LEADS.PK_LOCATION WHERE DOA_LEADS.PK_LEADS = " . $PK_LEADS . " LIMIT 1");
$locationName = $locationSettings->fields['LOCATION_NAME'] ?? 'our location';
?>

<Response>
    <Say voice="Polly.Joanna-Neural">
        Hello, this is Robert from <?= $locationName ?>. Am I speaking with Robert Melgoza?
        <break time="400ms" />
        I'm reaching out because you recently visited our website looking for information about learning to dance. Does that ring a bell?
    </Say>

    <Gather action="<?php echo $handleDateUrl; ?>"
        method="POST"
        input="speech dtmf"
        speechTimeout="auto"
        timeout="6">
        <Say voice="Polly.Joanna-Neural">Please say a date like December twenty.</Say>
    </Gather>

    <Say voice="Polly.Joanna-Neural">We did not receive your response. Goodbye.</Say>
    <Hangup />
</Response>