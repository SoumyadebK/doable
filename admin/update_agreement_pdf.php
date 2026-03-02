<?php
require_once('../global/config.php');

$PK_ENROLLMENT_MASTER = $_GET['PK_ENROLLMENT_MASTER'];

$document_library_data = $db_account->Execute("SELECT DOA_DOCUMENT_LIBRARY.DOCUMENT_TEMPLATE FROM `DOA_DOCUMENT_LIBRARY` LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_MASTER.PK_DOCUMENT_LIBRARY=DOA_DOCUMENT_LIBRARY.PK_DOCUMENT_LIBRARY WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = '$PK_ENROLLMENT_MASTER'");
$user_data = $db->Execute("SELECT DOA_USERS.FIRST_NAME, DOA_USERS.LAST_NAME, DOA_USERS.PHONE, DOA_USERS.ADDRESS, DOA_USERS.CITY, DOA_STATES.STATE_NAME, DOA_USERS.ZIP, DOA_USERS.DOB, DOA_USERS.EMAIL_ID FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER LEFT JOIN DOA_STATES ON DOA_STATES.PK_STATES=DOA_USERS.PK_STATES LEFT JOIN $account_database.DOA_ENROLLMENT_MASTER AS DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER=DOA_USER_MASTER.PK_USER_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = " . $PK_ENROLLMENT_MASTER);
$enrollment_details = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION) AS NUMBER_OF_SESSIONS, SUM(DOA_ENROLLMENT_SERVICE.TOTAL) AS TOTAL, SUM(DOA_ENROLLMENT_SERVICE.DISCOUNT) AS DISCOUNT, SUM(DOA_ENROLLMENT_SERVICE.FINAL_AMOUNT) AS FINAL_AMOUNT, DOA_ENROLLMENT_MASTER.PK_LOCATION, DOA_ENROLLMENT_BILLING.FIRST_DUE_DATE, DOA_ENROLLMENT_BILLING.PAYMENT_TERM, DOA_ENROLLMENT_BILLING.NUMBER_OF_PAYMENT, DOA_ENROLLMENT_BILLING.INSTALLMENT_AMOUNT FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_BILLING ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = " . $PK_ENROLLMENT_MASTER . " GROUP BY DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER");
$html_template = $document_library_data->fields['DOCUMENT_TEMPLATE'];
$html_template = str_replace('{FULL_NAME}', $user_data->fields['FIRST_NAME'] . " " . $user_data->fields['LAST_NAME'], $html_template);
$html_template = str_replace('{STREET_ADD}', $user_data->fields['ADDRESS'], $html_template);
$html_template = str_replace('{CITY}', $user_data->fields['CITY'], $html_template);
$html_template = str_replace('{STATE}', $user_data->fields['STATE_NAME'], $html_template);
$html_template = str_replace('{ZIP}', $user_data->fields['ZIP'], $html_template);
$html_template = str_replace('{CELL_PHONE}', !empty($user_data->fields['PHONE']) ? $user_data->fields['PHONE'] : '', $html_template);
$html_template = str_replace('{EMAIL}', !empty($user_data->fields['EMAIL_ID']) ? $user_data->fields['EMAIL_ID'] : '', $html_template);

$dob = $user_data->fields['DOB'];
$formatted_dob = '';
if (!empty($dob)) {
    $date = DateTime::createFromFormat('Y-m-d', $dob);
    $errors = DateTime::getLastErrors();

    if ($date && $errors['warning_count'] == 0 && $errors['error_count'] == 0) {
        $formatted_dob = $date->format('m/d/Y');
    }
}
$html_template = str_replace('{DOB}', $formatted_dob, $html_template);

$TYPE_OF_ENROLLMENT = '';
$SERVICE_DETAILS = '';
$PVT_LESSONS = '';
$TUITION = '';
$DISCOUNT = '';
$BAL_DUE = '';
$MISC_SERVICES = '';
$TUITION_COST = '';
$DUE_DATE = '';
$BILLED_AMOUNT = '';


$enrollment_service_data = $db_account->Execute("SELECT DOA_ENROLLMENT_SERVICE.*, DOA_ENROLLMENT_MASTER.ENROLLMENT_NAME, DOA_ENROLLMENT_MASTER.EXPIRY_DATE, DOA_ENROLLMENT_MASTER.PK_USER_MASTER FROM DOA_ENROLLMENT_SERVICE LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = '$PK_ENROLLMENT_MASTER'");
$enrollment_count = $db_account->Execute("SELECT COUNT(PK_USER_MASTER) AS ENROLLMENT_COUNT FROM DOA_ENROLLMENT_MASTER WHERE PK_USER_MASTER=" . $enrollment_service_data->fields['PK_USER_MASTER']);
$number = $enrollment_count->RecordCount() > 0 ? $enrollment_count->fields['ENROLLMENT_COUNT'] : '';
$ends = array('th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th');
$abbreviation = ($number % 100) >= 11 && ($number % 100) <= 13 ? $number . 'th' : $number . $ends[$number % 10];
if (empty($enrollment_service_data->fields['ENROLLMENT_NAME'])) {
    $enrollment_name = $abbreviation;
} else {
    $enrollment_name = $enrollment_service_data->fields['ENROLLMENT_NAME'] . " - " . $abbreviation;
}

$enrollment_data = $db_account->Execute("SELECT * FROM DOA_ENROLLMENT_MASTER WHERE PK_ENROLLMENT_MASTER = '$PK_ENROLLMENT_MASTER'");

$EXPIRY_DATE = new DateTime($enrollment_data->fields['EXPIRY_DATE']);
$CREATED_ON = new DateTime($enrollment_data->fields['CREATED_ON']);
$interval = $EXPIRY_DATE->diff($CREATED_ON);
$months = intval($interval->days / 30) . " months";
$months = $months . " month" . ($months > 1 ? "s" : "");

$SERVICE_PRICE = [];
$SERVICE_SESSION = [];

$TOTAL_NUMBER_OF_SESSION = 0;
$TOTAL_TUITION = 0;
$TOTAL_DISCOUNT = 0;
$SUBTOTAL = 0;

while (!$enrollment_service_data->EOF) {
    $TYPE_OF_ENROLLMENT = $enrollment_name;
    $SERVICE_DETAILS .= $enrollment_service_data->fields['SERVICE_DETAILS'] . "<br>";
    $PVT_LESSONS .= $enrollment_service_data->fields['NUMBER_OF_SESSION'] . "<br>";
    $TUITION .= $enrollment_service_data->fields['TOTAL'] . "<br>";
    $DISCOUNT .= $enrollment_service_data->fields['DISCOUNT'] . "<br>";
    $BAL_DUE .= $enrollment_service_data->fields['FINAL_AMOUNT'] . "<br>";
    $SERVICE_PRICE[] = $enrollment_service_data->fields['SERVICE_DETAILS'] . " $" . $enrollment_service_data->fields['PRICE_PER_SESSION'] . " per lesson";
    $SERVICE_SESSION[] = $enrollment_service_data->fields['NUMBER_OF_SESSION'] . " " . $enrollment_service_data->fields['SERVICE_DETAILS'];
    $TOTAL_NUMBER_OF_SESSION += $enrollment_service_data->fields['NUMBER_OF_SESSION'];
    $TOTAL_TUITION += $enrollment_service_data->fields['TOTAL'];
    $TOTAL_DISCOUNT += $enrollment_service_data->fields['DISCOUNT'];
    $SUBTOTAL += $enrollment_service_data->fields['FINAL_AMOUNT'];
    $enrollment_service_data->MoveNext();
}

$service_data = [];

// Re-execute the query to reset the recordset pointer
$enrollment_service_data = $db_account->Execute("SELECT DOA_ENROLLMENT_SERVICE.*, DOA_ENROLLMENT_MASTER.ENROLLMENT_NAME, DOA_ENROLLMENT_MASTER.EXPIRY_DATE, DOA_ENROLLMENT_MASTER.PK_USER_MASTER, DOA_ENROLLMENT_MASTER.ENROLLMENT_DATE FROM DOA_ENROLLMENT_SERVICE LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = '$PK_ENROLLMENT_MASTER '");

if ($enrollment_service_data && $enrollment_service_data->RecordCount() > 0) {
    while (!$enrollment_service_data->EOF) {
        $service_data[] = array(
            'service_details' => $enrollment_service_data->fields['SERVICE_DETAILS'] ?? '',
            'number_of_sessions' => (int)($enrollment_service_data->fields['NUMBER_OF_SESSION'] ?? 0),
            'total' => (float)($enrollment_service_data->fields['TOTAL'] ?? 0),
            'discount' => (float)($enrollment_service_data->fields['DISCOUNT'] ?? 0),
            'final_amount' => (float)($enrollment_service_data->fields['FINAL_AMOUNT'] ?? 0),
            'price_per_session' => (float)($enrollment_service_data->fields['PRICE_PER_SESSION'] ?? 0),
            'service_name' => $enrollment_service_data->fields['SERVICE_NAME'] ?? 'Service'
        );
        $enrollment_service_data->MoveNext();
    }
}

$price_count = count($SERVICE_PRICE);
if ($price_count === 1) {
    $SERVICE_PRICE = $SERVICE_PRICE[0] . '.';
} elseif ($price_count === 2) {
    $SERVICE_PRICE = $SERVICE_PRICE[0] . ' and ' . $SERVICE_PRICE[1] . '.';
} else {
    $SERVICE_PRICE = implode(', ', array_slice($SERVICE_PRICE, 0, -1))
        . ' and ' . end($SERVICE_PRICE) . '.';
}

$session_count = count($SERVICE_SESSION);
if ($session_count === 1) {
    $SERVICE_SESSION = $SERVICE_SESSION[0] . '.';
} elseif ($session_count === 2) {
    $SERVICE_SESSION = $SERVICE_SESSION[0] . ' and ' . $SERVICE_SESSION[1] . '.';
} else {
    $SERVICE_SESSION = implode(', ', array_slice($SERVICE_SESSION, 0, -1))
        . ' and ' . end($SERVICE_SESSION) . '.';
}

$misc_service_data = $db_account->Execute("SELECT DOA_ENROLLMENT_SERVICE.* FROM DOA_ENROLLMENT_SERVICE LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER LEFT JOIN DOA_SERVICE_MASTER ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_MASTER=DOA_SERVICE_MASTER.PK_SERVICE_MASTER WHERE DOA_SERVICE_MASTER.PK_SERVICE_CLASS = 5 AND DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = '$PK_ENROLLMENT_MASTER '");
while (!$misc_service_data->EOF) {
    $MISC_SERVICES .= $misc_service_data->fields['SERVICE_DETAILS'] . "<br>";
    $TUITION_COST .= $misc_service_data->fields['FINAL_AMOUNT'] . "<br>";
    $misc_service_data->MoveNext();
}
$enrollment_billing_data = $db_account->Execute("SELECT * FROM DOA_ENROLLMENT_BILLING WHERE PK_ENROLLMENT_MASTER = '$PK_ENROLLMENT_MASTER'");

$html_template = str_replace('{TYPE_OF_ENROLLMENT}', $TYPE_OF_ENROLLMENT, $html_template);
$html_template = str_replace('{SERVICE_DETAILS}', $SERVICE_DETAILS, $html_template);
$html_template = str_replace('{PVT_LESSONS}', $PVT_LESSONS, $html_template);
$html_template = str_replace('{TUITION}', $TUITION, $html_template);
$html_template = str_replace('{DISCOUNT}', $DISCOUNT, $html_template);
$html_template = str_replace('{BAL_DUE}', $BAL_DUE, $html_template);
$html_template = str_replace('{MISC_SERVICES}', $MISC_SERVICES, $html_template);
$html_template = str_replace('{TUITION_COST}', $TUITION_COST, $html_template);
$html_template = str_replace('{TOTAL}', $enrollment_details->fields['TOTAL'], $html_template);
$html_template = str_replace('{TOTAL_TUITION}', $TOTAL_TUITION, $html_template);
$html_template = str_replace('{TOTAL_DISCOUNT}', ($TOTAL_TUITION - $SUBTOTAL), $html_template);
$html_template = str_replace('{SUBTOTAL}', $SUBTOTAL, $html_template);
$html_template = str_replace('{CASH_PRICE}', $enrollment_details->fields['FINAL_AMOUNT'], $html_template);
$html_template = str_replace('{BILLING_DATE}', date('m-d-Y', strtotime($enrollment_billing_data->fields['BILLING_DATE'])), $html_template);
$html_template = str_replace('{EXPIRATION_DATE}', date('m-d-Y', strtotime($enrollment_service_data->fields['EXPIRY_DATE'])), $html_template);
$html_template = str_replace('{SERVICE_PRICE}', $SERVICE_PRICE, $html_template);
$html_template = str_replace('{SERVICE_SESSION}', $SERVICE_SESSION, $html_template);
$html_template = str_replace('{TOTAL_NUMBER_OF_SESSION}', $TOTAL_NUMBER_OF_SESSION . 'sessions', $html_template);
$html_template = str_replace('{MONTHS}', $months, $html_template);
$html_template = str_replace('{ENROLLMENT_NAME}', $enrollment_service_data->fields['ENROLLMENT_NAME'], $html_template);
$html_template = str_replace('{ENROLLMENT_DATE}', !empty($enrollment_service_data->fields['ENROLLMENT_DATE']) ? date('m/d/Y', strtotime($enrollment_service_data->fields['ENROLLMENT_DATE'])) : '', $html_template);

// Create a mapping of numbers to letters
$letter_map = ['A', 'B', 'C', 'D', 'E', 'F'];

// Loop through all 6 possible service positions
for ($i = 1; $i <= 6; $i++) {
    $index = $i - 1; // Convert to zero-based index for array access

    // Check if service exists at this index
    if (isset($service_data[$index]) && isset($service_data[$index]['service_details']) && $service_data[$index]['service_details'] !== '') {
        $service = $service_data[$index];
        $unit_price = ($service['number_of_sessions'] > 0) ? ($service['total'] / $service['number_of_sessions']) : 0;

        // Use the letter from the map instead of the number
        $replacement = "(" . $letter_map[$index] . ") " . $service['service_details'] . "<br> Units: " . $service['number_of_sessions'] . "<br> Unit Price: $" . number_format((float)$unit_price, 2, '.', '') . "<br> Total Price: $" . number_format((float)$service['total'], 2, '.', '') . "<br>";
    } else {
        // No service data for this position, show blank/empty
        $replacement = ''; // or you can use an empty string ''
    }

    // Replace the placeholder
    $html_template = str_replace('{SERVICE_' . $i . '}', $replacement, $html_template);
}

$PAYMENT_METHOD = $enrollment_billing_data->fields['PAYMENT_METHOD'];

if ($PAYMENT_METHOD == 'Flexible Payments') {
    for ($i = 0; $i < count($FLEXIBLE_PAYMENT_DATE); $i++) {
        $html_template = str_replace('{FIRST_DATE}', date('m-d-Y', strtotime($FLEXIBLE_PAYMENT_DATE[$i])), $html_template);
    }
} elseif ($PAYMENT_METHOD == 'Payment Plans') {
    $html_template = str_replace('{FIRST_DATE}', date('m-d-Y', strtotime($enrollment_billing_data->fields['FIRST_DUE_DATE'])), $html_template);
} else {
    $html_template = str_replace('{FIRST_DATE}', date('m-d-Y', strtotime($enrollment_billing_data->fields['BILLING_DATE'])), $html_template);
}

if ($PAYMENT_METHOD == 'Flexible Payments') {
    $PAYMENT_METHOD = '';
    $PAYMENT_AMOUNT = '';
    $STARTING_DATE = '';
    for ($i = 0; $i < count($FLEXIBLE_PAYMENT_DATE); $i++) {
        $PAYMENT_METHOD = $PAYMENT_METHOD;
        $PAYMENT_AMOUNT = count($FLEXIBLE_PAYMENT_DATE) . ' x ' . number_format((float)$FLEXIBLE_PAYMENT_AMOUNT[0], 2, '.', '');
        $STARTING_DATE = date('m-d-Y', strtotime($FLEXIBLE_PAYMENT_DATE[0]));
    }
} elseif ($PAYMENT_METHOD == 'Payment Plans') {
    $PAYMENT_METHOD = $enrollment_billing_data->fields['PAYMENT_TERM'];
    $PAYMENT_AMOUNT = $enrollment_billing_data->fields['NUMBER_OF_PAYMENT'];
    $STARTING_DATE = date('m-d-Y', strtotime($enrollment_billing_data->fields['FIRST_DUE_DATE']));
    $html_template = str_replace('{SCHEDULE_AMOUNT}', $enrollment_billing_data->fields['BALANCE_PAYABLE'], $html_template);
} else {
    $PAYMENT_AMOUNT = 1;
    $STARTING_DATE = date('m-d-Y', strtotime($enrollment_billing_data->fields['BILLING_DATE']));
    $html_template = str_replace('{SCHEDULE_AMOUNT}', $enrollment_billing_data->fields['BALANCE_PAYABLE'], $html_template);
}

$html_template = str_replace('{DOWN_PAYMENTS}', number_format((float)$enrollment_billing_data->fields['DOWN_PAYMENT'], 2, '.', ''), $html_template);

$html_template = str_replace('{REMAINING_BALANCE}', $enrollment_details->fields['FINAL_AMOUNT'] - $enrollment_billing_data->fields['DOWN_PAYMENT'], $html_template);
$html_template = str_replace('{PAYMENT_NAME}', $PAYMENT_METHOD, $html_template);
$html_template = str_replace('{NO_AMT_PAYMENT}', $PAYMENT_AMOUNT, $html_template);
$html_template = str_replace('{INSTALLMENT_AMOUNT}', number_format((float)$enrollment_billing_data->fields['INSTALLMENT_AMOUNT'], 2, '.', ''), $html_template);
$html_template = str_replace('{STARTING_DATE}', $STARTING_DATE, $html_template);

$business_data = $db->Execute("SELECT DOA_LOCATION.LOCATION_NAME, DOA_LOCATION.ADDRESS, DOA_LOCATION.ZIP_CODE, DOA_LOCATION.CITY, DOA_STATES.STATE_NAME, DOA_COUNTRY.COUNTRY_NAME, DOA_LOCATION.PHONE FROM DOA_LOCATION INNER JOIN DOA_STATES ON DOA_STATES.PK_STATES = DOA_LOCATION.PK_STATES INNER JOIN DOA_COUNTRY ON DOA_COUNTRY.PK_COUNTRY = DOA_LOCATION.PK_COUNTRY WHERE DOA_LOCATION.PK_LOCATION = " . $enrollment_details->fields['PK_LOCATION']);
$business_phone = !empty($business_data->fields['PHONE']) ? 'Tel. ' . $business_data->fields['PHONE'] : '';
$html_template = str_replace('{BUSINESS_NAME}', $business_data->fields['LOCATION_NAME'], $html_template);
$html_template = str_replace('{BUSINESS_ADD}', $business_data->fields['ADDRESS'], $html_template);
$html_template = str_replace('{BUSINESS_CITY}', $business_data->fields['CITY'], $html_template);
$html_template = str_replace('{BUSINESS_STATE}', $business_data->fields['STATE_NAME'], $html_template);
$html_template = str_replace('{BUSINESS_COUNTRY}', $business_data->fields['COUNTRY_NAME'], $html_template);
$html_template = str_replace('{BUSINESS_ZIP}', $business_data->fields['ZIP_CODE'], $html_template);
$html_template = str_replace('{BUSINESS_PHONE}', $business_phone, $html_template);

$SCHEDULING_AMOUNT = 0;
$date_amount = $db_account->Execute("SELECT DUE_DATE, BILLED_AMOUNT FROM DOA_ENROLLMENT_LEDGER WHERE TRANSACTION_TYPE = 'Billing' AND IS_DOWN_PAYMENT = '0' AND PK_ENROLLMENT_MASTER = '$PK_ENROLLMENT_MASTER '");
while (!$date_amount->EOF) {
    $DUE_DATE .= date('m-d-Y', strtotime($date_amount->fields['DUE_DATE'])) . "<br>";
    $BILLED_AMOUNT .= $date_amount->fields['BILLED_AMOUNT'] . "<br>";
    $SCHEDULING_AMOUNT += $date_amount->fields['BILLED_AMOUNT'];
    $date_amount->MoveNext();
}
$html_template = str_replace('{SCHEDULE_AMOUNT}', $SCHEDULING_AMOUNT, $html_template);
$html_template = str_replace('{DUE_DATE}', $DUE_DATE, $html_template);
$html_template = str_replace('{BILLED_AMOUNT}', $BILLED_AMOUNT, $html_template);

if ($_SESSION['PK_ACCOUNT_MASTER'] == 1042) {
    $ENROLLMENT_MASTER_DATA['AGREEMENT_PDF_LINK'] = generateEnrollmentPDF($PK_ENROLLMENT_MASTER);
} else {
    $ENROLLMENT_MASTER_DATA['AGREEMENT_PDF_LINK'] = generatePdf($html_template, $PK_ENROLLMENT_MASTER);
}

db_perform_account('DOA_ENROLLMENT_MASTER', $ENROLLMENT_MASTER_DATA, 'update', " PK_ENROLLMENT_MASTER =  '$PK_ENROLLMENT_MASTER '");

echo "New link : " . $ENROLLMENT_MASTER_DATA['AGREEMENT_PDF_LINK'];




function generateEnrollmentPDF($PK_ENROLLMENT_MASTER)
{
    require_once('includes/pdf_generation.php');
    global $upload_path;

    // Generate the HTML using only the PK_ENROLLMENT_MASTER
    $html = getEnrollmentHTML($PK_ENROLLMENT_MASTER);

    // Use your existing mPDF function
    require_once('../global/vendor/autoload.php');

    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'margin_left' => 15,
        'margin_right' => 15,
        'margin_top' => 15,
        'margin_bottom' => 15,
        'margin_header' => 5,
        'margin_footer' => 5
    ]);

    // Set document properties
    $mpdf->SetTitle('Enrollment Agreement #' . $PK_ENROLLMENT_MASTER);
    $mpdf->SetAuthor('Dance With Me');
    $mpdf->SetCreator('DWM System');

    // Write HTML
    $mpdf->WriteHTML($html);
    $mpdf->keep_table_proportions = true;

    // Get location code from database
    global $master_database;
    global $db_account;

    $enrollment_location = $db_account->Execute("
        SELECT DOA_LOCATION.LOCATION_CODE 
        FROM DOA_ENROLLMENT_MASTER 
        LEFT JOIN $master_database.DOA_LOCATION AS DOA_LOCATION 
            ON DOA_LOCATION.PK_LOCATION = DOA_ENROLLMENT_MASTER.PK_LOCATION 
        WHERE PK_ENROLLMENT_MASTER = '$PK_ENROLLMENT_MASTER'
    ");

    $LOCATION_CODE = $enrollment_location->fields['LOCATION_CODE'] ?? 'DEFAULT';

    // Create directory structure if not exists
    $pdf_dir = '../../' . $upload_path . '/enrollment_pdf/' . $LOCATION_CODE . '/';

    if (!file_exists($pdf_dir)) {
        mkdir($pdf_dir, 0777, true);
        chmod($pdf_dir, 0777);
    }

    // Generate filename
    $file_name = "enrollment_" . $PK_ENROLLMENT_MASTER . "_" . date('Ymd_His') . ".pdf";
    $full_path = $pdf_dir . $file_name;

    // Save PDF
    $mpdf->Output($full_path, 'F');

    return $LOCATION_CODE . '/' . $file_name;
}



function generatePdf($html, $PK_ENROLLMENT_MASTER): string
{
    global $upload_path;
    global $master_database;
    global $db_account;
    require_once('../global/vendor/autoload.php');

    $mpdf = $mpdf = new \Mpdf\Mpdf();
    $mpdf->WriteHTML($html);
    $mpdf->keep_table_proportions = true;
    $mpdf->AddPage();

    if (!file_exists('../' . $upload_path . '/enrollment_pdf/')) {
        mkdir('../' . $upload_path . '/enrollment_pdf/', 0777, true);
        chmod('../' . $upload_path . '/enrollment_pdf/', 0777);
    }

    $enrollment_location = $db_account->Execute("SELECT DOA_LOCATION.LOCATION_CODE FROM DOA_ENROLLMENT_MASTER LEFT JOIN $master_database.DOA_LOCATION AS DOA_LOCATION ON DOA_LOCATION.PK_LOCATION = DOA_ENROLLMENT_MASTER.PK_LOCATION WHERE PK_ENROLLMENT_MASTER = '$PK_ENROLLMENT_MASTER'");
    $LOCATION_CODE = $enrollment_location->fields['LOCATION_CODE'];

    if (!file_exists('../' . $upload_path . '/enrollment_pdf/' . $LOCATION_CODE . '/')) {
        mkdir('../' . $upload_path . '/enrollment_pdf/' . $LOCATION_CODE . '/', 0777, true);
        chmod('../' . $upload_path . '/enrollment_pdf/' . $LOCATION_CODE . '/', 0777);
    }

    $file_name = "enrollment_pdf_" . time() . ".pdf";
    $mpdf->Output('../' . $upload_path . '/enrollment_pdf/' . $LOCATION_CODE . '/' . $file_name, 'F');

    return $LOCATION_CODE . '/' . $file_name;
}
