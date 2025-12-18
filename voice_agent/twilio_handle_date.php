<?php
require_once("/var/www/html/global/config.php");
require_once("/var/www/html/voice_agent/voice_agent_helper.php");
global $db;

$handleDateUrl = "https://doable.net/voice_agent/twilio_handle_date.php";

// twilio_handle_date.php
header("Content-Type: text/xml");

$speech = $_POST['SpeechResult'] ?? '';
$digits = $_POST['Digits'] ?? '';
$callSid = $_POST['CallSid'] ?? '';

$call_details = $db->Execute("SELECT * FROM DOA_CALL_DETAILS WHERE CALL_SID = '" . $callSid . "' LIMIT 1");
$date = $call_details->fields['SELECTED_DATE'] ?? null;
$PK_LEADS = $call_details->fields['PK_LEADS'] ?? null;

$locationSettings = $db->Execute("SELECT DOA_LOCATION.PK_LOCATION, DOA_LOCATION.PK_ACCOUNT_MASTER, DOA_LOCATION.LOCATION_NAME FROM DOA_LOCATION INNER JOIN DOA_LEADS ON DOA_LOCATION.PK_LOCATION = DOA_LEADS.PK_LOCATION WHERE DOA_LEADS.PK_LEADS = " . $PK_LEADS . " LIMIT 1");
$PK_LOCATION = $locationSettings->fields['PK_LOCATION'] ?? null;

$callSettingData = $db->Execute("SELECT * FROM DOA_DEFAULT_CALL_SETTING WHERE PK_LOCATION = " . $PK_LOCATION . " LIMIT 1");
$PART_OF_DAY_STR = $callSettingData->fields['PART_OF_DAY'] ?? '';
$PART_OF_DAY = explode(',', $PART_OF_DAY_STR);

function parse_date_from_text($text)
{
    // Try to use strtotime. You may improve with a natural language date parser.
    $time = strtotime($text);
    if ($time === false) return null;
    return date('Y-m-d', $time);
}

if (!empty($speech)) {
    $date = parse_date_from_text($speech);
} elseif (!empty($digits)) {
    // Optionally map digits to date if you use a keypad date input scheme.
    $date = null;
}

$CALL_DETAILS['STEP'] = 'date_received';
$CALL_DETAILS['SELECTED_DATE'] = $date;
$CALL_DETAILS['SPEECH'] = $speech;
$CALL_DETAILS['DIGITS'] = $digits;
db_perform('DOA_CALL_DETAILS', $CALL_DETAILS, "update", " CALL_SID = '" . $callSid . "'");

if (!$date) {
    // ask to repeat/clarify
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
?>
    <Response>
        <Say voice="Polly.Amy-Neural">
            Sorry,
            <break time="400ms" />
            I didn't understand that date. Please say the date again.
        </Say>
        <Redirect><?php echo $handleDateUrl; ?></Redirect>
    </Response>
<?php
    exit;
}

$handlePartOfDay = "https://doable.net/voice_agent/twilio_handle_part_of_day.php";
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
?>

<Response>
    <!-- Intro Line -->
    <Say voice="Polly.Amy-Neural">
        Which part of the day works best for you?
        <break time="300ms" />
    </Say>

    <!-- Part Selection -->
    <Gather
        bargeIn="true"
        action="<?php echo $handlePartOfDay; ?>"
        input="dtmf speech"
        method="POST"
        numDigits="1"
        timeout="8"
        speechTimeout="2">

        <?php foreach ($PART_OF_DAY as $key => $part) { ?>
            <Say voice="Polly.Joanna-Neural">For <?= $part ?>, press <?= $key + 1 ?> or say <?= $part ?>.</Say>
        <?php } ?>

    </Gather>

    <!-- Fallback -->
    <Say voice="Polly.Amy-Neural">
        I did not receive a selection.
        <break time="300ms" />
        Ending the call now. Goodbye.
    </Say>
    <Hangup />
</Response>