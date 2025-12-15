<?php
require_once("/var/www/html/global/config.php");
require_once("/var/www/html/voice_agent/voice_agent_helper.php");
global $db;

header("Content-Type: text/xml");

$response = new SimpleXMLElement("<Response></Response>");

// Collect input
$callSid = $_POST['CallSid'] ?? '';
$speech = strtolower($_POST['SpeechResult'] ?? '');
$digit  = $_POST['Digits'] ?? '';

$CALL_DETAILS['STEP'] = 'chose_yes_no';
$CALL_DETAILS['SPEECH'] = $speech;
$CALL_DETAILS['DIGITS'] = $digit;
db_perform('DOA_CALL_DETAILS', $CALL_DETAILS, "update", " CALL_SID = '" . $callSid . "'");

// -----------------------------
// DTMF override
// -----------------------------
if ($digit === '1') $speech = 'yes';
if ($digit === '2') $speech = 'no';

// -----------------------------
// YES/NO vocabulary lists
// -----------------------------
$yesWords = [
    'yes',
    'yeah',
    'yep',
    'sure',
    'correct',
    'right',
    'ya',
    'ok',
    'okay',
    'yup',
    'absolutely',
    'definitely',
    'of course',
    'sure thing',
    'sounds good',
    'i guess',
    'i guess so',
    'i think so',
    'why not',
    'please',
    'go ahead'
];

$noWords = [
    'no',
    'nope',
    'nah',
    'not really',
    'negative',
    'wrong person',
    'don\'t think so',
    'i don\'t think so',
    'no thank you',
    'not at all',
    'stop',
    'cancel',
    'wrong number'
];

// -----------------------------
// Fuzzy keyword detection
// -----------------------------
function containsAny($text, $wordList)
{
    foreach ($wordList as $w) {
        if (strpos($text, $w) !== false) return true;
    }
    return false;
}

$isYes = containsAny($speech, $yesWords);
$isNo  = containsAny($speech, $noWords);

// -----------------------------
// Confidence check (Twilio gives 0.0–1.0)
// Accept if speechResult is somewhat clear
// -----------------------------
if (!$isYes && !$isNo && $confidence >= 0.60) {
    // fallback: classify based on strongest keyword match
    if (strpos($speech, 'yes') !== false || strpos($speech, 'sure') !== false)
        $isYes = true;
    if (strpos($speech, 'no') !== false || strpos($speech, 'not') !== false)
        $isNo = true;
}

// -----------------------------
// RESPONSE BUILDER
// -----------------------------
$response = new SimpleXMLElement("<Response></Response>");

if ($isYes) {
    // --- USER SAID YES ---
    $month = date("F");

    // Convert numeric day to spoken words
    $dayNumber = (int)date("j");
    $words = [
        1 => "one",
        2 => "two",
        3 => "three",
        4 => "four",
        5 => "five",
        6 => "six",
        7 => "seven",
        8 => "eight",
        9 => "nine",
        10 => "ten",
        11 => "eleven",
        12 => "twelve",
        13 => "thirteen",
        14 => "fourteen",
        15 => "fifteen",
        16 => "sixteen",
        17 => "seventeen",
        18 => "eighteen",
        19 => "nineteen",
        20 => "twenty",
        21 => "twenty one",
        22 => "twenty two",
        23 => "twenty three",
        24 => "twenty four",
        25 => "twenty five",
        26 => "twenty six",
        27 => "twenty seven",
        28 => "twenty eight",
        29 => "twenty nine",
        30 => "thirty",
        31 => "thirty one"
    ];
    $dayWords = $words[$dayNumber];
    $spokenDate = "$month $dayWords";

    // SAY DATE INSTRUCTION
    $say = $response->addChild("Say", "Great! Let's get you scheduled for your first session. Please say a date like $spokenDate.");
    $say->addAttribute("voice", "Polly.Amy-Neural");

    $handleDateUrl = "https://doable.net/voice_agent/twilio_handle_date.php";
    // NEW DATE GATHER
    $gather = $response->addChild("Gather");
    $gather->addAttribute("action", $handleDateUrl);
    $gather->addAttribute("method", "POST");
    $gather->addAttribute("input", "speech dtmf");
    $gather->addAttribute("timeout", "6");
    $gather->addAttribute("speechTimeout", "auto");
} elseif ($isNo) {
    // --- USER SAID NO ---
    $response->addChild("Say", "Alright, thank you for your time. Have a great day! Goodbye.")
        ->addAttribute("voice", "Polly.Amy-Neural");
    $response->addChild("Hangup");
} else {
    $handleYesNoUrl = "https://doable.net/voice_agent/twilio_handle_yes_no.php";
    // --- NOT CLEAR ---
    $response->addChild("Say", "Sorry, I didn't quite catch that. Please clearly say yes or no.")
        ->addAttribute("voice", "Polly.Amy-Neural");

    // REASK (redirect back to yes/no handler)
    $response->addChild("Redirect", $handleYesNoUrl);
}

echo $response->asXML();
