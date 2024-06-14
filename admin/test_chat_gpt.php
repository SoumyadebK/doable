<?php
require_once('../global/config.php');
global $conn;
global $db;
global $db_account;
global $master_database;
global $results_per_page;

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 2 ){
    header("location:../login.php");
    exit;
}

$status = 1;

$query = $db->Execute("SELECT DISTINCT(DOA_USERS.PK_USER), count(DOA_USERS.PK_USER) AS TOTAL_RECORDS FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER LEFT JOIN $account_database.DOA_CUSTOMER_DETAILS AS DOA_CUSTOMER_DETAILS ON DOA_USER_MASTER.PK_USER_MASTER = DOA_CUSTOMER_DETAILS.PK_USER_MASTER LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER LEFT JOIN DOA_USER_LOCATION ON DOA_USER_LOCATION.PK_USER=DOA_USERS.PK_USER WHERE DOA_USER_LOCATION.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_USER_ROLES.PK_ROLES = 4 AND DOA_USERS.IS_DELETED = 0 AND DOA_USERS.ACTIVE = '$status' AND DOA_USERS.IS_DELETED = 0 AND DOA_USER_MASTER.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER']);
$number_of_result =  ($query->RecordCount() > 0) ? $query->fields['TOTAL_RECORDS'] : 1;
$number_of_page = ceil ($number_of_result / $results_per_page);

$page = 1;

$page_first_result = ($page-1) * $results_per_page;

$sql = "SELECT DOA_USERS.FIRST_NAME, DOA_USERS.LAST_NAME, DOA_USERS.USER_ID, DOA_USERS.PHONE, DOA_USERS.EMAIL_ID, DOA_USERS.ADDRESS FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER LEFT JOIN $account_database.DOA_CUSTOMER_DETAILS AS DOA_CUSTOMER_DETAILS ON DOA_USER_MASTER.PK_USER_MASTER = DOA_CUSTOMER_DETAILS.PK_USER_MASTER LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER LEFT JOIN DOA_USER_LOCATION ON DOA_USER_LOCATION.PK_USER=DOA_USERS.PK_USER WHERE DOA_USER_LOCATION.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_USER_ROLES.PK_ROLES = 4 AND DOA_USERS.IS_DELETED = 0 AND DOA_USERS.ACTIVE = '$status' AND DOA_USER_MASTER.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER']." ORDER BY DOA_USERS.PK_USER DESC LIMIT 50";
$result = $conn->query($sql);

$data = [];

if ($result->num_rows > 0) {
    // Output data of each row
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
} else {
    echo "0 results";
}

$chatGptSk = "sk-proj-LJPiqRq8BjnC6owrsgRQT3BlbkFJafJOZAML54fpJKIx2KCW";

function generateReport($data) {
    global $chatGptSk;
    $dataString = json_encode($data);
    $prompt = "Generate a report based on the following data: " . $dataString;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.openai.com/v1/engines/gpt-3.5-turbo-instruct/completions");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'prompt' => $prompt,
        'max_tokens' => 1000
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer $chatGptSk"
    ]);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    }
    curl_close($ch);

    $response = json_decode($response, true);
    return $response['choices'][0]['text'];
}

$report = generateReport($data);

?>

<!DOCTYPE html>
<html>
<head>
    <title>Generated Report</title>
</head>
<body>
<h1>Generated Report</h1>
<pre><?php echo $report; ?></pre>
</body>
</html>

