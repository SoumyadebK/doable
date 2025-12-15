<?php
require_once("/var/www/html/global/config.php");
require_once("/var/www/html/voice_agent/voice_agent_helper.php");
global $db;

// twilio_handle_slot.php
header("Content-Type: text/xml");

$callSid = $_POST['CallSid'] ?? '';
$speech = $_POST['SpeechResult'] ?? '';
$digits = $_POST['Digits'] ?? '';

$callDetails = $db->Execute("SELECT * FROM DOA_CALL_DETAILS WHERE CALL_SID = '" . $callSid . "' LIMIT 1");
$date = $callDetails->fields['SELECTED_DATE'] ?? null;
$PK_LEADS = $callDetails->fields['PK_LEADS'] ?? null;

$locationSettings = $db->Execute("SELECT PK_LOCATION FROM DOA_LEADS WHERE PK_LEADS = " . $PK_LEADS . " LIMIT 1");
$PK_LOCATION = $locationSettings->fields['PK_LOCATION'] ?? null;

$CALL_DETAILS['STEP'] = 'start_slot_selection';
$CALL_DETAILS['SPEECH'] = $speech;
$CALL_DETAILS['DIGITS'] = $digits;
db_perform('DOA_CALL_DETAILS', $CALL_DETAILS, "update", " CALL_SID = '" . $callSid . "'");

$slotsData = getLocationSlotDetails($PK_LOCATION, $date);
$slots = [];
foreach ($slotsData as $key => $slot) {
    $slots[$key + 1] = [
        'id' => $key + 1,
        'label' => date('h:i A', strtotime($slot['slot_start_time']))
    ];
}

$choiceIndex = null;
if (!empty($digits)) {
    $choiceIndex = intval($digits);
} elseif (!empty($speech)) {
    $choiceIndex = detectUserChoiceAdvanced($speech, $digits, $slots);
}

if (!$choiceIndex || !isset($slots[$choiceIndex])) {
    // Ask again
    $retryUrl = 'https://doable.net/voice_agent/twilio_handle_date.php';
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
?>
    <Response>
        <Say voice="Polly.Amy-Neural">
            I'm sorry, I didn't quite get that.
            <break time="300ms" />
            Let's try again.
        </Say>
        <Redirect><?php echo $retryUrl; ?></Redirect>
    </Response>
<?php
    exit;
}

$chosen = $slots[$choiceIndex];

$CALL_DETAILS['STEP'] = 'end_slot_selection';
$CALL_DETAILS['SELECTED_SLOT_ID'] = $chosen['id'];
$CALL_DETAILS['SELECTED_SLOT_LABEL'] = $chosen['label'];
db_perform('DOA_CALL_DETAILS', $CALL_DETAILS, "update", " CALL_SID = '" . $callSid . "'");

[$PK_USER, $PK_USER_MASTER] = createUserFromLeads($PK_LEADS);
$PK_APPOINTMENT_MASTER = createAppointment($PK_LEADS, $PK_USER_MASTER, $data, $chosen['label']);

if ($PK_APPOINTMENT_MASTER <= 0) {
    // Booking failed
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
?>
    <Response>
        <Say voice="Polly.Amy-Neural">
            I'm sorry, we couldn't complete your booking at this moment.
            <break time="300ms" />
            Please try again later, or contact our support team for help.
            <break time="300ms" />
            Goodbye.
        </Say>
        <Hangup />
    </Response>
<?php
    exit;
}

$callSettingData = $db->Execute("SELECT * FROM DOA_DEFAULT_CALL_SETTING WHERE PK_LOCATION = " . $PK_LOCATION . " LIMIT 1");
$endScript = $callSettingData->fields['END_SCRIPT'] ?? '';

function renderTemplate($template, $data)
{
    foreach ($data as $key => $value) {
        $template = str_replace('{{' . $key . '}}', $value, $template);
    }
    return $template;
}

$template = $endScript;

$data = [
    'dateTime' => date('F j, Y', strtotime($date)) . " at " . $chosen['label']
];

// Success — respond with confirmation
$confirmText = renderTemplate($template, $data);

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
?>
<Response>
    <Say voice="Polly.Amy-Neural">
        <?php echo $confirmText; ?>
    </Say>
    <Hangup />
</Response>