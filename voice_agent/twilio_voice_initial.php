<?php
require_once("/var/www/html/global/config.php");
global $db;

header("Content-Type: text/xml");

$callSid = $_POST['CallSid'] ?? '';
$handleYesNoUrl = "https://doable.net/voice_agent/twilio_handle_yes_no.php";

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";

$callDetails = $db->Execute("SELECT * FROM DOA_CALL_DETAILS WHERE CALL_SID = '" . $callSid . "' LIMIT 1");
$PK_LEADS = $callDetails->fields['PK_LEADS'] ?? null;

$locationSettings = $db->Execute("SELECT DOA_LOCATION.PK_LOCATION, DOA_LOCATION.PK_ACCOUNT_MASTER, DOA_LOCATION.LOCATION_NAME FROM DOA_LOCATION INNER JOIN DOA_LEADS ON DOA_LOCATION.PK_LOCATION = DOA_LEADS.PK_LOCATION WHERE DOA_LEADS.PK_LEADS = " . $PK_LEADS . " LIMIT 1");
$PK_LOCATION = $locationSettings->fields['PK_LOCATION'] ?? null;
$locationName = $locationSettings->fields['LOCATION_NAME'] ?? 'our location';

$callSettingData = $db->Execute("SELECT * FROM DOA_DEFAULT_CALL_SETTING WHERE PK_LOCATION = " . $PK_LOCATION . " LIMIT 1");
$script1 = $callSettingData->fields['SCRIPT_1'] ?? '';
$script2 = $callSettingData->fields['SCRIPT_2'] ?? '';

$PK_USER = $callSettingData->fields['PK_USER'] ?? null;
$agentData = $db->Execute("SELECT FIRST_NAME, LAST_NAME FROM DOA_USERS WHERE PK_USER = " . $PK_USER . " LIMIT 1");
$agentFirstName = $agentData->fields['FIRST_NAME'] ?? 'Agent';
$agentLastName = $agentData->fields['LAST_NAME'] ?? '';
$agentFullName = trim($agentFirstName . ' ' . $agentLastName);

$leadsData = $db->Execute("SELECT FIRST_NAME, LAST_NAME FROM DOA_LEADS WHERE PK_LEADS = " . $PK_LEADS . " LIMIT 1");
$leadFirstName = $leadsData->fields['FIRST_NAME'] ?? 'Valued';
$leadLastName = $leadsData->fields['LAST_NAME'] ?? 'Customer';
$leadFullName = trim($leadFirstName . ' ' . $leadLastName);
?>

<?php
function renderTemplate($template, $data)
{
    foreach ($data as $key => $value) {
        $template = str_replace('{{' . $key . '}}', $value, $template);
    }
    return $template;
}

$template = $script1;

$data = [
    'agentName'   => $agentFullName,
    'companyName' => $locationName,
    'firstName'   => $leadFullName
];
?>
<Response>
    <Say voice="Polly.Amy-Neural">
        <?= renderTemplate($template, $data) ?>
        <break time="1000ms" />
        <?= $script2 ?>
    </Say>

    <!-- YES / NO Gather -->
    <Gather
        bargeIn="true"
        action="<?php echo $handleYesNoUrl; ?>"
        input="dtmf speech"
        method="POST"
        numDigits="1"
        timeout="8"
        speechTimeout="2">
        <Say voice="Polly.Amy-Neural">
            Please say yes or no, or press 1 for yes, 2 for no.
        </Say>
    </Gather>

    <Say voice="Polly.Amy-Neural">We did not receive your response. Goodbye.</Say>
    <Hangup />
</Response>