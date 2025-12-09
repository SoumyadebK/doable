<?php
require_once("/var/www/html/global/config.php");
global $db;

// twilio_handle_date.php
header("Content-Type: text/xml");

$speech = $_POST['SpeechResult'] ?? '';
$digits = $_POST['Digits'] ?? '';
$callSid = $_POST['CallSid'] ?? '';

function parse_date_from_text($text)
{
    // Try to use strtotime. You may improve with a natural language date parser.
    $time = strtotime($text);
    if ($time === false) return null;
    return date('Y-m-d', $time);
}

$date = null;
if (!empty($speech)) {
    $date = parse_date_from_text($speech);
} elseif (!empty($digits)) {
    // Optionally map digits to date if you use a keypad date input scheme.
    $date = null;
}

if (!$date) {
    // ask to repeat/clarify
    $retryUrl = "https://doable.net/voice_agent/twilio_voice_initial.php";
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
?>
    <Response>
        <Say voice="Polly.Joanna-Neural">
            Sorry,
            <break time="400ms" />
            I didn't understand that date. Please say the date again.
        </Say>
        <Redirect><?php echo $retryUrl; ?></Redirect>
    </Response>
<?php
    exit;
}

// Save date to your conversation store (DB) keyed by CallSid
// e.g. saveConversationState($callSid, ['date'=>$date]);


// Call your app API to get available slots
/* $slotsApi = "https://yourdomain.com/api/available-slots?date={$date}";
$slotsJson = file_get_contents($slotsApi);
$slots = json_decode($slotsJson, true);
 */

$CALL_DETAILS['STEP'] = 'date_received';
$CALL_DETAILS['SELECTED_DATE'] = $date;
db_perform('DOA_CALL_DETAILS', $CALL_DETAILS, "update", " CALL_SID = '" . $callSid . "'");

$slots = [
    ["id" => 1, "label" => "10:00 AM"],
    ["id" => 2, "label" => "11:30 AM"],
    ["id" => 3, "label" => "12:00 PM"],
    ["id" => 4, "label" => "01:30 PM"],
    ["id" => 5, "label" => "02:00 PM"],
    ["id" => 6, "label" => "03:30 PM"],
]; // Example slots

// If no slots
if (empty($slots)) {
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
?>
    <Response>
        <Say voice="Polly.Joanna-Neural">
            Sorry,
            <break time="400ms" />
            there are no available slots on <?php echo date('F j, Y', strtotime($date)); ?>. Would you like to try another date?
        </Say>
        <Redirect>https://doable.net/voice_agent/twilio_voice_initial.php</Redirect>
    </Response>
<?php
    exit;
}

// Build a TwiML that lists N options and gathers selection
$handleSlotUrl = "https://doable.net/voice_agent/twilio_handle_slot.php";
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
?>

<Response>
    <!-- Intro Line -->
    <Say voice="Polly.Joanna-Neural">
        Here are the available time slots for
        <?php echo date('F j, Y', strtotime($date)); ?>.
        <break time="300ms" />
    </Say>

    <!-- Slot Selection -->
    <Gather
        bargeIn="true"
        action="<?php echo $handleSlotUrl; ?>"
        input="dtmf speech"
        method="POST"
        numDigits="1"
        timeout="8"
        speechTimeout="2">
        <?php
        $i = 1;
        foreach ($slots as $slot) {
            echo '<Say voice="Polly.Joanna-Neural">'
                . 'Option ' . $i . '. '
                . $slot['label']
                . '. <break time="200ms"/>'
                . '</Say>';
            $i++;
            if ($i > 9) break; // DTMF limit
        }
        ?>

        <Say voice="Polly.Joanna-Neural">
            Please say the option number or press the corresponding digit on your keypad to select your preferred time slot.
        </Say>
    </Gather>

    <!-- Fallback -->
    <Say voice="Polly.Joanna-Neural">
        I did not receive a selection.
        <break time="300ms" />
        Ending the call now. Goodbye.
    </Say>
    <Hangup />
</Response>