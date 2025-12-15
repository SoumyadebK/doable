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

function parse_date_from_text($text)
{
    // Try to use strtotime. You may improve with a natural language date parser.
    $time = strtotime($text);
    if ($time === false) return null;
    return date('Y-m-d', $time);
}

if ($date == '0000-00-00') {
    if (!empty($speech)) {
        $date = parse_date_from_text($speech);
    } elseif (!empty($digits)) {
        // Optionally map digits to date if you use a keypad date input scheme.
        $date = null;
    }
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

$slotsData = getLocationSlotDetails($PK_LOCATION, $date);
$slots = [];
foreach ($slotsData as $key => $slot) {
    $slots[$key + 1] = [
        'id' => $key + 1,
        'label' => date('h:i A', strtotime($slot['slot_start_time']))
    ];
}

// If no slots
if (empty($slots)) {
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    $CALL_DETAILS['SELECTED_DATE'] = '0000-00-00';
    db_perform('DOA_CALL_DETAILS', $CALL_DETAILS, "update", " CALL_SID = '" . $callSid . "'");
?>
    <Response>
        <Say voice="Polly.Amy-Neural">
            Sorry,
            <break time="400ms" />
            there are no available slots on <?php echo date('F j, Y', strtotime($date)); ?>. Would you like to try another date?
        </Say>
        <Redirect><?php echo $handleDateUrl; ?></Redirect>
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
    <Say voice="Polly.Amy-Neural">
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
            echo '<Say voice="Polly.Amy-Neural">'
                . 'Option ' . $i . '. '
                . $slot['label']
                . '. <break time="200ms"/>'
                . '</Say>';
            $i++;
            if ($i > 9) break; // DTMF limit
        }
        ?>

        <Say voice="Polly.Amy-Neural">
            Please say the option number or press the corresponding digit on your keypad to select your preferred time slot.
        </Say>
    </Gather>

    <!-- Fallback -->
    <Say voice="Polly.Amy-Neural">
        I did not receive a selection.
        <break time="300ms" />
        Ending the call now. Goodbye.
    </Say>
    <Hangup />
</Response>