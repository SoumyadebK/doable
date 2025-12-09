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


<?php
$today = new DateTime();

// Month name (e.g., December)
$month = $today->format('F');

// Day in words (e.g., 20 → twenty)
$dayNumber = (int)$today->format('j');
$dayWords = convert_number_to_words($dayNumber);

// Converts 1–31 into spoken words
function convert_number_to_words($number)
{
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
    return $words[$number];
}
?>

<?php
function renderTemplate($template, $data)
{
    foreach ($data as $key => $value) {
        $template = str_replace('{{' . $key . '}}', $value, $template);
    }
    return $template;
}

$template = "Hello, this is {{agentName}} from {{companyName}}. Am I speaking with {{firstName}}?";

$data = [
    'agentName'   => 'Robert',
    'companyName' => $locationName,
    'firstName'   => 'Robert Melgoza'
];
?>
<Response>
    <Say voice="Polly.Joanna-Neural">
        <?= renderTemplate($template, $data) ?>
        <break time="400ms" />
        I'm reaching out because you recently visited our website looking for information about learning to dance. Does that ring a bell?
    </Say>

    <Gather action="<?php echo $handleDateUrl; ?>"
        method="POST"
        input="speech dtmf"
        speechTimeout="auto"
        timeout="6">
        <Say voice="Polly.Joanna-Neural">Please say a date like <?= $month . ' ' . $dayWords ?>.</Say>
    </Gather>

    <Say voice="Polly.Joanna-Neural">We did not receive your response. Goodbye.</Say>
    <Hangup />
</Response>