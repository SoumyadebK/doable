<?php
require_once("/var/www/html/global/config.php");
require_once("/var/www/html/voice_agent/voice_agent_helper.php");
global $db;

$handleDateUrl = "https://doable.net/voice_agent/twilio_handle_date.php";
$handlePartOfDay = "https://doable.net/voice_agent/twilio_handle_part_of_day.php";

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

if ($digits == '1' || strpos(strtolower($speech), 'morning') !== false) $part = 'morning';
elseif ($digits == '2' || strpos(strtolower($speech), 'afternoon') !== false) $part = 'afternoon';
elseif ($digits == '3' || strpos(strtolower($speech), 'evening') !== false) $part = 'evening';
else $part = null;

$CALL_DETAILS['STEP'] = 'part_of_day_received';
$CALL_DETAILS['SELECTED_PART'] = $part;
$CALL_DETAILS['SPEECH'] = $speech;
$CALL_DETAILS['DIGITS'] = $digits;
db_perform('DOA_CALL_DETAILS', $CALL_DETAILS, "update", " CALL_SID = '" . $callSid . "'");

if ($part == null) {
    // ask to repeat/clarify
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
?>
    <Response>
        <Say voice="Polly.Joanna-Neural">
            <amazon:domain name="conversational">
                Sorry,
                <break time="400ms" />
                I didn't understand that. Please say the date again.
            </amazon:domain>
        </Say>
        <Redirect><?php echo $handleDateUrl; ?></Redirect>
    </Response>
<?php
    exit;
}

$slotsData = getLocationSlotDetails($PK_LOCATION, $date, $part);
$slots = [];
foreach ($slotsData as $key => $slot) {
    if ($key <= 1) {
        $slots[$key + 1] = [
            'id' => $key + 1,
            'label' => date('h:i A', strtotime($slot['slot_start_time']))
        ];
    }
}

// If no slots
if (empty($slots) && count($slots) == 0) {
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    $CALL_DETAILS['SELECTED_DATE'] = '0000-00-00';
    db_perform('DOA_CALL_DETAILS', $CALL_DETAILS, "update", " CALL_SID = '" . $callSid . "'");
?>
    <Response>
        <Say voice="Polly.Joanna-Neural">
            <amazon:domain name="conversational">
                Sorry,
                <break time="400ms" />
                there are no available slots on <?php echo date('F j, Y', strtotime($date)); ?> at <?= $part ?>. Would you like to try another date?
            </amazon:domain>
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
    <Say voice="Polly.Joanna-Neural">
        <amazon:domain name="conversational">
            Great! Here are the available time slots for
            <?php echo date('F j, Y', strtotime($date)); ?> at <?= $part ?>.
            <break time="300ms" />
        </amazon:domain>
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
            echo '<Say voice="Polly.Joanna-Neural"><amazon:domain name="conversational">'
                . 'Option ' . $i . '. '
                . $slot['label']
                . '. <break time="200ms"/>'
                . '</amazon:domain></Say>';
            $i++;
            if ($i > 9) break; // DTMF limit
        }
        ?>

        <Say voice="Polly.Joanna-Neural">
            <amazon:domain name="conversational">
                Please say the option number or press the corresponding digit on your keypad to select your preferred time slot.
            </amazon:domain>
        </Say>
    </Gather>

    <!-- Fallback -->
    <Say voice="Polly.Joanna-Neural">
        <amazon:domain name="conversational">
            I did not receive a selection.
            <break time="300ms" />
            Ending the call now. Goodbye.
        </amazon:domain>
    </Say>
    <Hangup />
</Response>