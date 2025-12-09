<?php
require_once("/var/www/html/global/config.php");
require_once("/var/www/html/voice_agent/detect_user_slot.php");
global $db;

// twilio_handle_slot.php
header("Content-Type: text/xml");

$callSid = $_POST['CallSid'] ?? '';
$speech = $_POST['SpeechResult'] ?? '';
$digits = $_POST['Digits'] ?? '';

$call_details = $db->Execute("SELECT * FROM DOA_CALL_DETAILS WHERE CALL_SID = '" . $callSid . "' LIMIT 1");
$date = $call_details->fields['SELECTED_DATE'] ?? null;


$CALL_DETAILS['STEP'] = 'start_slot_selection';
$CALL_DETAILS['SPEECH'] = $speech;
$CALL_DETAILS['DIGITS'] = $digits;
db_perform('DOA_CALL_DETAILS', $CALL_DETAILS, "update", " CALL_SID = '" . $callSid . "'");


$state['options'] = [
    1 => ["id" => 1, "label" => "10:00 AM"],
    2 => ["id" => 2, "label" => "11:30 AM"],
    3 => ["id" => 3, "label" => "12:00 PM"],
    4 => ["id" => 4, "label" => "01:30 PM"],
    5 => ["id" => 5, "label" => "02:00 PM"],
    6 => ["id" => 6, "label" => "03:30 PM"],
]; // Example slots

$options = $state['options'];

$choiceIndex = null;
if (!empty($digits)) {
    $choiceIndex = intval($digits);
} elseif (!empty($speech)) {
    $choiceIndex = detectUserChoiceAdvanced($speech, $digits, $options);
}

if (!$choiceIndex || !isset($state['options'][$choiceIndex])) {
    // Ask again
    $retryUrl = 'https://doable.net/voice_agent/twilio_voice_initial.php';
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
?>
    <Response>
        <Say voice="Polly.Joanna-Neural">
            I'm sorry, I didn't quite get that.
            <break time="300ms" />
            Let's try again.
        </Say>
        <Redirect><?php echo $retryUrl; ?></Redirect>
    </Response>
<?php
    exit;
}

$chosen = $state['options'][$choiceIndex];

$CALL_DETAILS['STEP'] = 'end_slot_selection';
$CALL_DETAILS['SELECTED_SLOT_ID'] = $chosen['id'];
$CALL_DETAILS['SELECTED_SLOT_LABEL'] = $chosen['label'];
db_perform('DOA_CALL_DETAILS', $CALL_DETAILS, "update", " CALL_SID = '" . $callSid . "'");

$resp['success'] = true; // Simulate success

// Call your booking API
/* $bookingApi = "https://yourdomain.com/api/book-slot";
$payload = [
    'customer_id' => $state['customer_id'], // ensure you track this
    'slot_id' => $chosen['id'],
    'date' => $date
];

$options = [
    'http' => [
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => json_encode($payload),
        'timeout' => 10
    ],
];

$context  = stream_context_create($options);
$result = file_get_contents($bookingApi, false, $context);
$resp = json_decode($result, true); */

if (!$resp || empty($resp['success'])) {
    // Booking failed
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
?>
    <Response>
        <Say voice="Polly.Joanna-Neural">
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

// Success — respond with confirmation
$confirmText = "Excellent, I appreciate you committing to that. And just to confirm, you're locked in for,"
    . date('F j, Y', strtotime($date))
    . " at " . $chosen['label']
    . ". A confirmation email will be sent to you shortly. We look forward to seeing you soon. Have a great day.";

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
?>
<Response>
    <Say voice="Polly.Joanna-Neural">
        <?php echo $confirmText; ?>
        <break time="300ms" />
        We look forward to seeing you.
    </Say>
    <Hangup />
</Response>