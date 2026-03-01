<?php
// pdf_generation.php
require_once('../../global/config.php');
function getEnrollmentHTML($PK_ENROLLMENT_MASTER)
{
    global $account_database;
    global $master_database;
    global $db_account;
    global $db;

    $background_path = '../../assets/images/dwm_background.jpg'; // Default background image path

    // Fetch document template
    $document_library_data = $db_account->Execute("
        SELECT DOA_DOCUMENT_LIBRARY.DOCUMENT_TEMPLATE 
        FROM `DOA_DOCUMENT_LIBRARY` 
        LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_MASTER.PK_DOCUMENT_LIBRARY = DOA_DOCUMENT_LIBRARY.PK_DOCUMENT_LIBRARY 
        WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = '$PK_ENROLLMENT_MASTER'
    ");

    // Fetch user data
    $user_data = $db->Execute(
        "
        SELECT DOA_USERS.FIRST_NAME, DOA_USERS.LAST_NAME, DOA_USERS.PHONE, DOA_USERS.ADDRESS, 
               DOA_USERS.CITY, DOA_STATES.STATE_NAME, DOA_USERS.ZIP, DOA_USERS.DOB, DOA_USERS.EMAIL_ID 
        FROM DOA_USERS 
        INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER 
        LEFT JOIN DOA_STATES ON DOA_STATES.PK_STATES = DOA_USERS.PK_STATES 
        LEFT JOIN $account_database.DOA_ENROLLMENT_MASTER AS DOA_ENROLLMENT_MASTER 
            ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER 
        WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = " . $PK_ENROLLMENT_MASTER
    );

    // Fetch enrollment details with billing info
    $enrollment_details = $db_account->Execute("
        SELECT 
            SUM(DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION) AS NUMBER_OF_SESSIONS, 
            SUM(DOA_ENROLLMENT_SERVICE.TOTAL) AS TOTAL, 
            SUM(DOA_ENROLLMENT_SERVICE.DISCOUNT) AS DISCOUNT, 
            SUM(DOA_ENROLLMENT_SERVICE.FINAL_AMOUNT) AS FINAL_AMOUNT, 
            DOA_ENROLLMENT_MASTER.PK_LOCATION, 
            DOA_ENROLLMENT_MASTER.ENROLLMENT_NAME,
            DOA_ENROLLMENT_MASTER.ENROLLMENT_DATE,
            DOA_ENROLLMENT_MASTER.EXPIRY_DATE,
            DOA_ENROLLMENT_MASTER.CREATED_ON,
            DOA_ENROLLMENT_MASTER.PK_DOCUMENT_LIBRARY,
            DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE,
            DOA_ENROLLMENT_BILLING.FIRST_DUE_DATE, 
            DOA_ENROLLMENT_BILLING.PAYMENT_TERM, 
            DOA_ENROLLMENT_BILLING.NUMBER_OF_PAYMENT, 
            DOA_ENROLLMENT_BILLING.INSTALLMENT_AMOUNT,
            DOA_ENROLLMENT_BILLING.DOWN_PAYMENT,
            DOA_ENROLLMENT_BILLING.BALANCE_PAYABLE,
            DOA_ENROLLMENT_BILLING.PAYMENT_METHOD
        FROM DOA_ENROLLMENT_MASTER 
        LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER 
        LEFT JOIN DOA_ENROLLMENT_BILLING ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER 
        WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = " . $PK_ENROLLMENT_MASTER . " 
        GROUP BY DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER
    ");

    // Fetch enrollment services
    $enrollment_service_data = $db_account->Execute("
        SELECT DOA_ENROLLMENT_SERVICE.*, DOA_SERVICE_MASTER.SERVICE_NAME
        FROM DOA_ENROLLMENT_SERVICE 
        LEFT JOIN DOA_SERVICE_MASTER ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER
        WHERE DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = '$PK_ENROLLMENT_MASTER'
    ");

    // Fetch miscellaneous services
    $misc_service_data = $db_account->Execute("
        SELECT DOA_ENROLLMENT_SERVICE.* 
        FROM DOA_ENROLLMENT_SERVICE 
        LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER 
        LEFT JOIN DOA_SERVICE_MASTER ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER 
        WHERE DOA_SERVICE_MASTER.PK_SERVICE_CLASS = 5 
        AND DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = '$PK_ENROLLMENT_MASTER'
    ");

    // Fetch enrollment count for abbreviation
    $enrollment_count = $db_account->Execute("
        SELECT COUNT(PK_USER_MASTER) AS ENROLLMENT_COUNT 
        FROM DOA_ENROLLMENT_MASTER 
        WHERE PK_USER_MASTER = (
            SELECT PK_USER_MASTER FROM DOA_ENROLLMENT_MASTER WHERE PK_ENROLLMENT_MASTER = " . $PK_ENROLLMENT_MASTER . "
        )
    ");

    // Fetch business/location data
    $business_data = $db->Execute(
        "
        SELECT DOA_LOCATION.LOCATION_NAME, DOA_LOCATION.ADDRESS, DOA_LOCATION.ZIP_CODE, 
               DOA_LOCATION.CITY, DOA_STATES.STATE_NAME, DOA_COUNTRY.COUNTRY_NAME, 
               DOA_LOCATION.PHONE, DOA_LOCATION.LOCATION_CODE
        FROM DOA_LOCATION 
        INNER JOIN DOA_STATES ON DOA_STATES.PK_STATES = DOA_LOCATION.PK_STATES 
        INNER JOIN DOA_COUNTRY ON DOA_COUNTRY.PK_COUNTRY = DOA_LOCATION.PK_COUNTRY 
        WHERE DOA_LOCATION.PK_LOCATION = " . $enrollment_details->fields['PK_LOCATION']
    );

    // Fetch ledger/schedule data
    $ledger_data = $db_account->Execute("
        SELECT DUE_DATE, BILLED_AMOUNT 
        FROM DOA_ENROLLMENT_LEDGER 
        WHERE TRANSACTION_TYPE = 'Billing' 
        AND IS_DOWN_PAYMENT = '0' 
        AND PK_ENROLLMENT_MASTER = '$PK_ENROLLMENT_MASTER'
    ");

    // Initialize variables
    $SERVICE_DETAILS = '';
    $PVT_LESSONS = '';
    $TUITION = '';
    $DISCOUNT = '';
    $BAL_DUE = '';
    $MISC_SERVICES = '';
    $TUITION_COST = '';
    $SERVICE_PRICE = [];
    $SERVICE_SESSION = [];
    $service_data = [];
    $TOTAL_NUMBER_OF_SESSION = 0;
    $TOTAL_TUITION = 0;
    $TOTAL_DISCOUNT = 0;
    $SUBTOTAL = 0;
    $DUE_DATE = '';
    $BILLED_AMOUNT = '';
    $SCHEDULING_AMOUNT = 0;

    // Process services
    if ($enrollment_service_data && $enrollment_service_data->RecordCount() > 0) {
        while (!$enrollment_service_data->EOF) {
            $SERVICE_DETAILS .= $enrollment_service_data->fields['SERVICE_DETAILS'] . "<br>";
            $PVT_LESSONS .= $enrollment_service_data->fields['NUMBER_OF_SESSION'] . "<br>";
            $TUITION .= $enrollment_service_data->fields['TOTAL'] . "<br>";
            $DISCOUNT .= $enrollment_service_data->fields['DISCOUNT'] . "<br>";
            $BAL_DUE .= $enrollment_service_data->fields['FINAL_AMOUNT'] . "<br>";

            $SERVICE_PRICE[] = $enrollment_service_data->fields['SERVICE_DETAILS'] . " $" .
                number_format($enrollment_service_data->fields['PRICE_PER_SESSION'], 2) . " per lesson";
            $SERVICE_SESSION[] = $enrollment_service_data->fields['NUMBER_OF_SESSION'] . " " .
                $enrollment_service_data->fields['SERVICE_DETAILS'];

            $TOTAL_NUMBER_OF_SESSION += $enrollment_service_data->fields['NUMBER_OF_SESSION'];
            $TOTAL_TUITION += $enrollment_service_data->fields['TOTAL'];
            $TOTAL_DISCOUNT += $enrollment_service_data->fields['DISCOUNT'];
            $SUBTOTAL += $enrollment_service_data->fields['FINAL_AMOUNT'];

            // Build service data array for letter mapping
            $service_data[] = array(
                'service_details' => $enrollment_service_data->fields['SERVICE_DETAILS'] ?? '',
                'number_of_sessions' => (int)($enrollment_service_data->fields['NUMBER_OF_SESSION'] ?? 0),
                'total' => (float)($enrollment_service_data->fields['TOTAL'] ?? 0),
                'discount' => (float)($enrollment_service_data->fields['DISCOUNT'] ?? 0),
                'final_amount' => (float)($enrollment_service_data->fields['FINAL_AMOUNT'] ?? 0),
                'price_per_session' => (float)($enrollment_service_data->fields['PRICE_PER_SESSION'] ?? 0),
                'service_name' => $enrollment_service_data->fields['SERVICE_NAME'] ?? $enrollment_service_data->fields['SERVICE_DETAILS']
            );

            $enrollment_service_data->MoveNext();
        }
    }

    // Process miscellaneous services
    if ($misc_service_data && $misc_service_data->RecordCount() > 0) {
        while (!$misc_service_data->EOF) {
            $MISC_SERVICES .= $misc_service_data->fields['SERVICE_DETAILS'] . "<br>";
            $TUITION_COST .= $misc_service_data->fields['FINAL_AMOUNT'] . "<br>";
            $misc_service_data->MoveNext();
        }
    }

    // Process ledger data for schedule
    if ($ledger_data && $ledger_data->RecordCount() > 0) {
        while (!$ledger_data->EOF) {
            $DUE_DATE .= date('m-d-Y', strtotime($ledger_data->fields['DUE_DATE'])) . "<br>";
            $BILLED_AMOUNT .= $ledger_data->fields['BILLED_AMOUNT'] . "<br>";
            $SCHEDULING_AMOUNT += $ledger_data->fields['BILLED_AMOUNT'];
            $ledger_data->MoveNext();
        }
    }

    // Format service price text
    $price_count = count($SERVICE_PRICE);
    if ($price_count === 1) {
        $SERVICE_PRICE_TEXT = $SERVICE_PRICE[0] . '.';
    } elseif ($price_count === 2) {
        $SERVICE_PRICE_TEXT = $SERVICE_PRICE[0] . ' and ' . $SERVICE_PRICE[1] . '.';
    } else {
        $SERVICE_PRICE_TEXT = implode(', ', array_slice($SERVICE_PRICE, 0, -1))
            . ' and ' . end($SERVICE_PRICE) . '.';
    }

    // Format service session text
    $session_count = count($SERVICE_SESSION);
    if ($session_count === 1) {
        $SERVICE_SESSION_TEXT = $SERVICE_SESSION[0] . '.';
    } elseif ($session_count === 2) {
        $SERVICE_SESSION_TEXT = $SERVICE_SESSION[0] . ' and ' . $SERVICE_SESSION[1] . '.';
    } else {
        $SERVICE_SESSION_TEXT = implode(', ', array_slice($SERVICE_SESSION, 0, -1))
            . ' and ' . end($SERVICE_SESSION) . '.';
    }

    // Calculate enrollment abbreviation
    $number = $enrollment_count->RecordCount() > 0 ? $enrollment_count->fields['ENROLLMENT_COUNT'] : '';
    $ends = array('th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th');
    $abbreviation = ($number % 100) >= 11 && ($number % 100) <= 13 ? $number . 'th' : $number . $ends[$number % 10];

    if (empty($enrollment_details->fields['ENROLLMENT_NAME'])) {
        $enrollment_name = $abbreviation;
    } else {
        $enrollment_name = $enrollment_details->fields['ENROLLMENT_NAME'] . " - " . $abbreviation;
    }

    // Calculate months difference
    $EXPIRY_DATE = new DateTime($enrollment_details->fields['EXPIRY_DATE']);
    $CREATED_ON = new DateTime($enrollment_details->fields['CREATED_ON']);
    $interval = $EXPIRY_DATE->diff($CREATED_ON);
    $months = intval($interval->days / 30) . " month" . (intval($interval->days / 30) > 1 ? "s" : "");

    // Format DOB
    $dob = $user_data->fields['DOB'];
    $formatted_dob = '';
    if (!empty($dob)) {
        $date = DateTime::createFromFormat('Y-m-d', $dob);
        if ($date) {
            $formatted_dob = $date->format('m/d/Y');
        }
    }

    // Get student name
    $student_name = $user_data->fields['FIRST_NAME'] . ' ' . $user_data->fields['LAST_NAME'];

    // Format dates
    $enrollment_date = !empty($enrollment_details->fields['ENROLLMENT_DATE']) ?
        date('m/d/Y', strtotime($enrollment_details->fields['ENROLLMENT_DATE'])) : '';
    $expiry_date = !empty($enrollment_details->fields['EXPIRY_DATE']) ?
        date('m/d/Y', strtotime($enrollment_details->fields['EXPIRY_DATE'])) : '';
    $billing_date = date('m-d-Y', strtotime($enrollment_details->fields['ENROLLMENT_DATE']));

    // Company/Business info
    $company_name = $business_data->fields['LOCATION_NAME'] ?? 'DWM Dance Studio Houston, LLC.';
    $company_address = $business_data->fields['ADDRESS'] ?? '600 N SHEPHERD DR SUITE 405';
    $company_city = $business_data->fields['CITY'] ?? 'HOUSTON';
    $company_state = $business_data->fields['STATE_NAME'] ?? 'TX';
    $company_zip = $business_data->fields['ZIP_CODE'] ?? '77007';
    $company_phone = !empty($business_data->fields['PHONE']) ? 'Tel. ' . $business_data->fields['PHONE'] : '713.360.3262';
    $company_email = 'HOUSTON@DANCEWITHMEUSA.COM'; // Default or fetch from somewhere
    $company_logo = ''; // Add logo path if available
    $LOCATION_CODE = $business_data->fields['LOCATION_CODE'] ?? 'DEFAULT';

    // Full company address
    $company_full_address = $company_address . ' | ' . $company_city . ', ' . $company_state . ' ' . $company_zip;

    // Handle logo
    $logo_html = '';
    if (!empty($company_logo) && file_exists('../../' . $company_logo)) {
        $logo_html = '<img src="../../' . $company_logo . '" class="logo" />';
    } else {
        $logo_html = '<img src="../../assets/images/dwm_logo.png" style="max-width: 180px;">';
    }

    // Map services to letters A-F
    $letter_map = ['A', 'B', 'C', 'D', 'E', 'F'];
    $service_blocks = [];

    for ($i = 0; $i < 6; $i++) {
        if (isset($service_data[$i])) {
            $service = $service_data[$i];
            $unit_price = ($service['number_of_sessions'] > 0) ?
                ($service['total'] / $service['number_of_sessions']) : 0;

            $service_blocks[$letter_map[$i]] = [
                'title' => '(' . $letter_map[$i] . ') ' . $service['service_name'],
                'units' => $service['number_of_sessions'],
                'unit_price' => $unit_price,
                'total_price' => $service['total']
            ];
        } else {
            $service_blocks[$letter_map[$i]] = [
                'title' => '(' . $letter_map[$i] . ') ',
                'units' => '',
                'unit_price' => '',
                'total_price' => ''
            ];
        }
    }

    if ($enrollment_details->fields['PK_DOCUMENT_LIBRARY'] == 1) {
        if ($enrollment_details->fields['PK_ENROLLMENT_TYPE'] == 5) {
            return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>DWM Enrollment Agreement</title>
        <style>
            body { 
                font-family: Helvetica, Arial, sans-serif; 
                color: #000; 
                font-size: 13px; 
                line-height: 18px; 
                margin: 0;
                padding: 0;
            }
            .font-bold { font-weight: bold; }
            .font-black { font-weight: 900; }
            p { margin: 0 0 12px; }
            .text-center { text-align: center; }
            .text-end { text-align: right; }
            .logo { max-width: 180px; }
            .line { 
                border-bottom: 1px solid #000; 
                min-height: 20px; 
                width: 100%;
                margin-top: 5px;
                display: inline-block;
            }
            .mb-0 { margin-bottom: 0; }
            .service-block {
                width: 30%;
                padding: 10px 0px;
                vertical-align: top;
            }
            .footer { margin-top: 10px; text-align: center; }
            ol { margin-left: 20px; }
            li { margin-bottom: 8px; }
            .watermark {
                position: fixed;
                top: 40%;
                left: 50%;
                transform: translate(-50%, -50%);
                font-size: 50px;
                color: rgba(0, 0, 0, 0.05);
                white-space: nowrap;
                z-index: -1;
            }
            table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            .page-break { page-break-before: always; }
            hr { border: 1px solid #ddd; }
        </style>
    </head>
    <body>       
        <!-- Welcome Content -->
        <tbody>
            <tr>
                <td colspan="3" width="100%">
                    <div class="text-center">
                        ' . $logo_html . '
                    </div>
                </td>
            </tr>
            <tr>
                <td colspan="3" width="100%">
                    <p>
                        <b>Welcome to the DWM Family!!</b> We are thrilled to have you join our community of dancers. As
                        you begin your program, we want to provide you with tips and information to help you make the most
                        out of your dance lessons.
                    </p>
                    <p>
                        <b>Utilizing Your Lessons:</b> Your program consists of a total of ' . $TOTAL_NUMBER_OF_SESSION . '
                         sessions, including ' . $SERVICE_SESSION_TEXT . ' 
                        We encourage you to utilize the privates and groups within 1 month. This condensed schedule is 
                        designed to immerse you fully in the dance experience and accelerate your progress. Consistency is 
                        key when learning anything new, so we encourage you to make it a priority and we guarantee we will 
                        make it worth it! The aim of your current plan is to explore various dances and patterns, setting 
                        you on the path to becoming a confident dancer. To enhance your learning experience we highly 
                        encourage you to share your feedback throughout the lessons. Let us know which songs inspire you 
                        to dance, which dance styles pique your curiosity, and how you prefer to learn.<i class="fas fa-bullseye"></i>
                    </p>
                    <p>
                        <b>Group Class Schedule:</b> You will find the schedule for our group classes on our website. We
                        recommend attending sessions regularly as they provide valuable opportunities for socializing,
                        practicing with different partners, and refining your techniques under the guidance of our
                        experienced instructors. Anyone working in the studio will be able to give you advice on which groups
                        will be best for your level and desire.
                    </p>
                    <p>
                        <b>Attire:</b> For your comfort and ease of movement, we suggest wearing clothing that allows
                        you to move freely. Ballroom dancing is a king’s sport so dressing up is encouraged :) However, if
                        you are coming from work or the gym, that of course will work as well. Additionally, it’s important
                        to wear appropriate dance shoes to support your feet and enhance your performance. If you are able
                        to purchase ballroom dance shoes, that is ideal but it is not essential to learning. A comfortable
                        sport shoe or a shoe with a small heel will also do as long as it won’t slip off. For more
                        recommendations, please speak to your instructor.
                    </p>
                </td>
            </tr>
            <tr>
                <td colspan="3" width="100%">
                    <p><b>Additional Tips:</b></p>
                    <ul>
                        <li>
                            If possible, consider scheduling a private lesson right before a group class (especially if
                            you are busy). This back-to-back format provides you with 90 minutes of dance practice, blending
                            focused personal instruction with the fun and energy of group dynamics.
                        </li>
                        <li>
                            Arrive a few minutes early to each class to warm up. Warming up could be reviewing the steps
                            you’ve learned, physical stretching or even just giving yourself time to sit and watch the other
                            lessons going on.
                        </li>
                        <li>
                            Let your instructor know what you are enjoying most. The more open you are with your
                            experience the better your instructor will be able to custom tailor it for you.
                        </li>
                        <li>If possible, practice outside of class to reinforce what you’ve learned.</li>
                        <li>
                            Your teacher will keep track of your progress in your program which he will show you on your
                            first lesson. You are more than welcome to access it whenever you are at the studio.
                        </li>
                    </ul>
                    <p>
                        We’re here to support you every step of your dance journey. If you have any questions or need
                        guidance, just give us a call. Let’s make your dance experience as exciting and enriching as possible!
                    </p>
                    <p>Sincerely,</p>
                    <p class="font-bold">Dance With Me Houston</p>
                    <div class="text-center font-bold">
                        <h2 class="font-black mb-0"><b>' . strtoupper($company_name) . '</b></h2>
                        <p>
                            ' . $company_full_address . ' <br />
                            <a href="mailto:' . $company_email . '">' . $company_email . '</a> |
                            <a href="tel:' . preg_replace('/[^0-9]/', '', $company_phone) . '">' . $company_phone . '</a>
                        </p>
                    </div>
                </td>
            </tr>
        </tbody>
        
        <!-- Enrollment Agreement -->
        <div class="page-break"></div>
        <table>
            <tr><td class="font-bold" colspan="3">' . strtoupper($company_name) . '</td></tr>
            <tr>
                <td colspan="2">STUDENT ENROLLMENT AGREEMENT</td>
                <td class="text-end">Date: <span class="line" style="width:150px;">' . $enrollment_date . '</span></td>
            </tr>
            <tr><td colspan="3">Name: <span class="line" style="width:80%;">' . $student_name . '</span></td></tr>
            <tr><td colspan="3" style="padding-top:10px;">Address: <span class="line" style="width:80%;">' . ($user_data->fields['ADDRESS'] ?? '') . '</span></td></tr>
            <tr><td colspan="3" style="padding-top:10px;">City: <span class="line" style="width:85%;">' . ($user_data->fields['CITY'] ?? '') . '</span></td></tr>
            <tr><td colspan="3" style="padding-top:10px;">Phone: <span class="line" style="width:85%;">' . ($user_data->fields['PHONE'] ?? '') . '</span></td></tr>
            <tr>
                <td style="width:30%; padding-top:10px;">Phone 2: <span class="line"></span></td>
                <td style="width:30%; padding-top:10px;">DOB: <span class="line">' . $formatted_dob . '</span></td>
                <td style="width:30%; padding-top:10px;">Email: <span class="line">' . ($user_data->fields['EMAIL_ID'] ?? '') . '</span></td>
            </tr>
            <tr><td colspan="3" style="padding-top:10px;">Student agrees to purchase and ' . $company_name . ' (the “studio”) agrees to provide the following described course of dance instruction and/or miscellaneous studio service(s) on the following items of tuition.</td></tr>
            
            <!-- Service Blocks -->
            <tr>
                <td class="service-block"><h3><em><strong>' . $service_blocks['A']['title'] . '</strong></em></h3>Units: ' . ($service_blocks['A']['units'] ?: '') . '<br>Unit Price: <b>$' . ($service_blocks['A']['unit_price'] ? number_format($service_blocks['A']['unit_price'], 2) : '') . '</b><br>Total: <b>$' . ($service_blocks['A']['total_price'] ? number_format($service_blocks['A']['total_price'], 2) : '') . '</b></td>
                <td class="service-block"><h3><em><strong>' . $service_blocks['B']['title'] . '</strong></em></h3>Units: ' . ($service_blocks['B']['units'] ?: '') . '<br>Unit Price: <b>$' . ($service_blocks['B']['unit_price'] ? number_format($service_blocks['B']['unit_price'], 2) : '') . '</b><br>Total: <b>$' . ($service_blocks['B']['total_price'] ? number_format($service_blocks['B']['total_price'], 2) : '') . '</b></td>
                <td class="service-block"><h3><em><strong>' . $service_blocks['C']['title'] . '</strong></em></h3>Units: ' . ($service_blocks['C']['units'] ?: '') . '<br>Unit Price: <b>$' . ($service_blocks['C']['unit_price'] ? number_format($service_blocks['C']['unit_price'], 2) : '') . '</b><br>Total: <b>$' . ($service_blocks['C']['total_price'] ? number_format($service_blocks['C']['total_price'], 2) : '') . '</b></td>
            </tr>
            <tr>
                <td class="service-block"><h3><em><strong>' . $service_blocks['D']['title'] . '</strong></em></h3>Units: ' . ($service_blocks['D']['units'] ?: '') . '<br>Unit Price: <b>$' . ($service_blocks['D']['unit_price'] ? number_format($service_blocks['D']['unit_price'], 2) : '') . '</b><br>Total: <b>$' . ($service_blocks['D']['total_price'] ? number_format($service_blocks['D']['total_price'], 2) : '') . '</b></td>
                <td class="service-block"><h3><em><strong>' . $service_blocks['E']['title'] . '</strong></em></h3>Units: ' . ($service_blocks['E']['units'] ?: '') . '<br>Unit Price: <b>$' . ($service_blocks['E']['unit_price'] ? number_format($service_blocks['E']['unit_price'], 2) : '') . '</b><br>Total: <b>$' . ($service_blocks['E']['total_price'] ? number_format($service_blocks['E']['total_price'], 2) : '') . '</b></td>
                <td class="service-block"><h3><em><strong>' . $service_blocks['F']['title'] . '</strong></em></h3>Units: ' . ($service_blocks['F']['units'] ?: '') . '<br>Unit Price: <b>$' . ($service_blocks['F']['unit_price'] ? number_format($service_blocks['F']['unit_price'], 2) : '') . '</b><br>Total: <b>$' . ($service_blocks['F']['total_price'] ? number_format($service_blocks['F']['total_price'], 2) : '') . '</b></td>
            </tr>
            
            <tr><td colspan="3" class="text-center"><h2 style="margin:10px 0;"><span style="font-size:13px;">Course Description:</span> ' . $enrollment_name . '</h2></td></tr>
            <tr>
                <td width="100%" colspan="3" class="text-center"><em><strong>
                    <p class="mb-0 font-black line">(Parties and Group classes are complimentary)</p>
                    <p class="font-black line" style="padding-top: 5px;">($' . number_format($TOTAL_TUITION, 2) . ' VALUE)</p></strong></em>
                </td>
            </tr>
            
            <!-- Totals -->
            <tr>
                <td style="padding:10px 0px 0px;"><em><strong><b>TOTAL TUITION</b><br><span style="font-size:24px;">$' . number_format($TOTAL_TUITION, 2) . '</span></strong></em></td>
                <td style="padding:10px 0px 0px; text-align:center;"><em><strong><b>DISCOUNT / CREDIT</b><br><span style="font-size:24px;">$' . number_format($TOTAL_DISCOUNT, 2) . '</span></strong></em></td>
                <td style="padding:10px 0px 0px; text-align:right;"><em><strong><b>GRAND TOTAL</b><br><span style="font-size:24px;">$' . number_format($SUBTOTAL, 2) . '</span></strong></em></td>
            </tr>
            
            <tr><td colspan="3" style="padding:20px 0;"><hr></td></tr>
            
            <!-- Signatures -->
            <tr>
                <td style="width:30%; padding-right:30px;"><div class="line" style="height:30px;"></div><div class="text-center">Manager / Supervisor</div></td>
                <td style="width:30%;"><div class="line" style="height:30px;"></div><div class="text-center">Student Name</div></td>
                <td style="width:30%;"><div class="line" style="height:30px;"></div><div class="text-center">Date: </div></td>
            </tr>
        </table>
    
        
        <!-- Terms and Conditions (New Page) -->
        <div class="page-break"></div>
        <table>
            <tr><td colspan="3" class="text-center" style="font-size:14px; padding:20px 0;"><b>TERMS AND CONDITIONS</b></td></tr>
            <tr><td colspan="3">
                <ol>
                    <li><b>Term:</b> The Student agrees to prearrange and complete all lessons and/or services provided in this Agreement within one (1) year of the date of the Student Enrollment Agreement Form (Expiration Date: ' . $expiry_date . '). DWM Dance Studio GALLERIA, L.L.C. (hereinafter “Studio”) shall not be obligated to transfer any unused or expired dance lessons and/or services from prior Agreements.</li>
                    <li><b>Credit Card Authorization:</b> Student authorizes DWM Houston to charge the credit card on file for any scheduled lessons or unpaid session fees not paid at the time of booking. Student is responsible for updating the studio with any changes to your payment method.</li>
                    <li><b>Payments:</b> Student agrees to make all payments required under the Agreement in a timely manner; otherwise the Agreement shall terminate immediately upon a default.</li>
                    <li><b>Rescheduling/Cancellation:</b> All cancellations and/or changes to a scheduled lesson must be done twenty four (24) hours prior to the scheduled time of the lesson or the lesson shall be forfeited.</li>
                    <li><b>Rescheduling/Cancellation by the Studio:</b> The Studio may cancel or reschedule any individual or group lesson in its sole discretion at any time for any reason.</li>
                    <li><b>Instructors:</b> The Studio does not guarantee the services of any instructor nor does the Studio guarantee that a request for a particular instructor will be accommodated.</li>
                    <li><b>Refunds:</b> There shall be no refunds for any reason whatsoever except as set forth in paragraph 8.</li>
                    <li><b>Termination by the Studio:</b> The Studio may terminate an Agreement with a Student for good cause. Upon termination the Student shall not be entitled to a refund for any unused lessons.</li>
                    <li><b>Termination by the Student:</b> The student may terminate this agreement within sixty days (60) of the date of the Agreement. Upon termination under this provision, the Student shall receive a refund for only unused lessons paid for.</li>
                    <li><b>Lost Items:</b> The Studio is not responsible for or liable to the Student for any lost or stolen items.</li>
                    <li><b>Use of Image and Likeness:</b> The Student grants permission for videos and photographs to be taken of the Student while in the Studio and during the course of the dance lessons, showcases, competitions, company events, etc.</li>
                    <li><b>Non-Solicitation:</b> The Student hereby agrees not to solicit, induce, encourage, or allow an Employee or former employee of the Studio to engage in dance related activities with the Student outside of the Studio regardless of the Employee’s current employment status.</li>
                    <li><b>Gratuity:</b> The Student shall not give or loan anything of value to the Employee except for a tip that would be customary in the industry.</li>
                    <li><b>Liability/Waiver Release:</b> The Student assumes any and all risks involving or arising from his/her participation in the services offered by the Studio, including without limitation, the risk of death, bodily injury or property damage.</li>
                    <li><b>Non-Transferable:</b> This Agreement is not transferable or assignable to any other individual and/or entity.</li>
                    <li><b>Legal Construction:</b> In case any one or more of the provisions contained in this Agreement shall for any reason, be held invalid, illegal, or unenforceable in any respect, the invalidity, illegality, or unenforceability shall not affect any other provision of this agreement.</li>
                    <li><b>Entire Agreement:</b> This Agreement supersedes any prior understandings or written or oral Agreements between the Studio and the Student</li>
                    <li><b>Actions:</b> In the event of any controversy or claim arising out of this Agreement or the Students relationship with the Studio, the Student shall be required to submit written notice to the Studio of its intent to bring a claim.</li>
                    <li><b>Waiver:</b> In the event the Studio relaxes any rules stated herein, the relaxation of same shall not be deemed a waiver of any rights the Studio may otherwise have at a later time.</li>
                    <li><b>Statute of Limitations modifications:</b> Any and all claims brought against the Studio must be brought within twelve months of the accrual of the claim or within six months of the expiration of the one year term of the Agreement, whichever occurs first.</li>
                </ol>
            </td></tr>
            
            <!-- Final Signatures -->
            <tr>
                <td style="width:50%; padding-top:30px; text-align: center;">Student Name</td>
                <td style="width:50%; padding-top:30px; text-align: center;">Date</td>               
            </tr>
        </table>
        
        <!-- Footer -->
        <div class="footer">
            <p><b>Dance With Me Houston</b></p>
            <p><b>' . $company_name . '</b><br>' . $company_full_address . '<br>' . $company_email . ' | ' . $company_phone . '</p>
        </div>
    </body>
    </html>';
        } else {
            return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>DWM Enrollment Agreement</title>
        <style>
            body { 
                font-family: Helvetica, Arial, sans-serif; 
                color: #000; 
                font-size: 13px; 
                line-height: 18px; 
                margin: 0;
                padding: 0;
            }
            .font-bold { font-weight: bold; }
            .font-black { font-weight: 900; }
            p { margin: 0 0 12px; }
            .text-center { text-align: center; }
            .text-end { text-align: right; }
            .logo { max-width: 180px; }
            .line { 
                border-bottom: 1px solid #000; 
                min-height: 20px; 
                width: 100%;
                margin-top: 5px;
                display: inline-block;
            }
            .mb-0 { margin-bottom: 0; }
            .service-block {
                width: 30%;
                padding: 10px 0px;
                vertical-align: top;
            }
            .footer { margin-top: 10px; text-align: center; }
            ol { margin-left: 20px; }
            li { margin-bottom: 8px; }
            .watermark {
                position: fixed;
                top: 40%;
                left: 50%;
                transform: translate(-50%, -50%);
                font-size: 50px;
                color: rgba(0, 0, 0, 0.05);
                white-space: nowrap;
                z-index: -1;
            }
            table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            .page-break { page-break-before: always; }
            hr { border: 1px solid #ddd; }
        </style>
    </head>
    <body>       
        <!-- Enrollment Agreement -->
        <table>
            <tr><td class="font-bold" colspan="3">' . strtoupper($company_name) . '</td></tr>
            <tr>
                <td colspan="2">STUDENT ENROLLMENT AGREEMENT</td>
                <td class="text-end">Date: <span class="line" style="width:150px;">' . $enrollment_date . '</span></td>
            </tr>
            <tr><td colspan="3">Name: <span class="line" style="width:80%;">' . $student_name . '</span></td></tr>
            <tr><td colspan="3" style="padding-top:10px;">Address: <span class="line" style="width:80%;">' . ($user_data->fields['ADDRESS'] ?? '') . '</span></td></tr>
            <tr><td colspan="3" style="padding-top:10px;">City: <span class="line" style="width:85%;">' . ($user_data->fields['CITY'] ?? '') . '</span></td></tr>
            <tr><td colspan="3" style="padding-top:10px;">Phone: <span class="line" style="width:85%;">' . ($user_data->fields['PHONE'] ?? '') . '</span></td></tr>
            <tr>
                <td style="width:30%; padding-top:10px;">Phone 2: <span class="line"></span></td>
                <td style="width:30%; padding-top:10px;">DOB: <span class="line">' . $formatted_dob . '</span></td>
                <td style="width:30%; padding-top:10px;">Email: <span class="line">' . ($user_data->fields['EMAIL_ID'] ?? '') . '</span></td>
            </tr>
            <tr><td colspan="3" style="padding-top:10px;">Student agrees to purchase and ' . $company_name . ' (the “studio”) agrees to provide the following described course of dance instruction and/or miscellaneous studio service(s) on the following items of tuition.</td></tr>
            
            <!-- Service Blocks -->
            <tr>
                <td class="service-block"><h3><em><strong>' . $service_blocks['A']['title'] . '</strong></em></h3>Units: ' . ($service_blocks['A']['units'] ?: '') . '<br>Unit Price: <b>$' . ($service_blocks['A']['unit_price'] ? number_format($service_blocks['A']['unit_price'], 2) : '') . '</b><br>Total: <b>$' . ($service_blocks['A']['total_price'] ? number_format($service_blocks['A']['total_price'], 2) : '') . '</b></td>
                <td class="service-block"><h3><em><strong>' . $service_blocks['B']['title'] . '</strong></em></h3>Units: ' . ($service_blocks['B']['units'] ?: '') . '<br>Unit Price: <b>$' . ($service_blocks['B']['unit_price'] ? number_format($service_blocks['B']['unit_price'], 2) : '') . '</b><br>Total: <b>$' . ($service_blocks['B']['total_price'] ? number_format($service_blocks['B']['total_price'], 2) : '') . '</b></td>
                <td class="service-block"><h3><em><strong>' . $service_blocks['C']['title'] . '</strong></em></h3>Units: ' . ($service_blocks['C']['units'] ?: '') . '<br>Unit Price: <b>$' . ($service_blocks['C']['unit_price'] ? number_format($service_blocks['C']['unit_price'], 2) : '') . '</b><br>Total: <b>$' . ($service_blocks['C']['total_price'] ? number_format($service_blocks['C']['total_price'], 2) : '') . '</b></td>
            </tr>
            <tr>
                <td class="service-block"><h3><em><strong>' . $service_blocks['D']['title'] . '</strong></em></h3>Units: ' . ($service_blocks['D']['units'] ?: '') . '<br>Unit Price: <b>$' . ($service_blocks['D']['unit_price'] ? number_format($service_blocks['D']['unit_price'], 2) : '') . '</b><br>Total: <b>$' . ($service_blocks['D']['total_price'] ? number_format($service_blocks['D']['total_price'], 2) : '') . '</b></td>
                <td class="service-block"><h3><em><strong>' . $service_blocks['E']['title'] . '</strong></em></h3>Units: ' . ($service_blocks['E']['units'] ?: '') . '<br>Unit Price: <b>$' . ($service_blocks['E']['unit_price'] ? number_format($service_blocks['E']['unit_price'], 2) : '') . '</b><br>Total: <b>$' . ($service_blocks['E']['total_price'] ? number_format($service_blocks['E']['total_price'], 2) : '') . '</b></td>
                <td class="service-block"><h3><em><strong>' . $service_blocks['F']['title'] . '</strong></em></h3>Units: ' . ($service_blocks['F']['units'] ?: '') . '<br>Unit Price: <b>$' . ($service_blocks['F']['unit_price'] ? number_format($service_blocks['F']['unit_price'], 2) : '') . '</b><br>Total: <b>$' . ($service_blocks['F']['total_price'] ? number_format($service_blocks['F']['total_price'], 2) : '') . '</b></td>
            </tr>
            
            <tr><td colspan="3" class="text-center"><h2 style="margin:10px 0;"><span style="font-size:13px;">Course Description:</span> ' . $enrollment_name . '</h2></td></tr>
            <tr>
                <td width="100%" colspan="3" class="text-center"><em><strong>
                    <p class="mb-0 font-black line">(Parties and Group classes are complimentary)</p>
                    <p class="font-black line" style="padding-top: 5px;">($' . number_format($TOTAL_TUITION, 2) . ' VALUE)</p></strong></em>
                </td>
            </tr>
            
            <!-- Totals -->
            <tr>
                <td style="padding:10px 0px 0px;"><em><strong><b>TOTAL TUITION</b><br><span style="font-size:24px;">$' . number_format($TOTAL_TUITION, 2) . '</span></strong></em></td>
                <td style="padding:10px 0px 0px; text-align:center;"><em><strong><b>DISCOUNT / CREDIT</b><br><span style="font-size:24px;">$' . number_format($TOTAL_DISCOUNT, 2) . '</span></strong></em></td>
                <td style="padding:10px 0px 0px; text-align:right;"><em><strong><b>GRAND TOTAL</b><br><span style="font-size:24px;">$' . number_format($SUBTOTAL, 2) . '</span></strong></em></td>
            </tr>
            
            <tr><td colspan="3" style="padding:20px 0;"><hr></td></tr>
            
            <!-- Signatures -->
            <tr>
                <td style="width:30%; padding-right:30px;"><div class="line" style="height:30px;"></div><div class="text-center">Manager / Supervisor</div></td>
                <td style="width:30%;"><div class="line" style="height:30px;"></div><div class="text-center">Student Name</div></td>
                <td style="width:30%;"><div class="line" style="height:30px;"></div><div class="text-center">Date: </div></td>
            </tr>
        </table>
    
        
        <!-- Terms and Conditions (New Page) -->
        <div class="page-break"></div>
        <table>
            <tr><td colspan="3" class="text-center" style="font-size:14px; padding:20px 0;"><b>TERMS AND CONDITIONS</b></td></tr>
            <tr><td colspan="3">
                <ol>
                    <li><b>Term:</b> The Student agrees to prearrange and complete all lessons and/or services provided in this Agreement within one (1) year of the date of the Student Enrollment Agreement Form (Expiration Date: ' . $expiry_date . '). DWM Dance Studio GALLERIA, L.L.C. (hereinafter “Studio”) shall not be obligated to transfer any unused or expired dance lessons and/or services from prior Agreements.</li>
                    <li><b>Credit Card Authorization:</b> Student authorizes DWM Houston to charge the credit card on file for any scheduled lessons or unpaid session fees not paid at the time of booking. Student is responsible for updating the studio with any changes to your payment method.</li>
                    <li><b>Payments:</b> Student agrees to make all payments required under the Agreement in a timely manner; otherwise the Agreement shall terminate immediately upon a default.</li>
                    <li><b>Rescheduling/Cancellation:</b> All cancellations and/or changes to a scheduled lesson must be done twenty four (24) hours prior to the scheduled time of the lesson or the lesson shall be forfeited.</li>
                    <li><b>Rescheduling/Cancellation by the Studio:</b> The Studio may cancel or reschedule any individual or group lesson in its sole discretion at any time for any reason.</li>
                    <li><b>Instructors:</b> The Studio does not guarantee the services of any instructor nor does the Studio guarantee that a request for a particular instructor will be accommodated.</li>
                    <li><b>Refunds:</b> There shall be no refunds for any reason whatsoever except as set forth in paragraph 8.</li>
                    <li><b>Termination by the Studio:</b> The Studio may terminate an Agreement with a Student for good cause. Upon termination the Student shall not be entitled to a refund for any unused lessons.</li>
                    <li><b>Termination by the Student:</b> The student may terminate this agreement within sixty days (60) of the date of the Agreement. Upon termination under this provision, the Student shall receive a refund for only unused lessons paid for.</li>
                    <li><b>Lost Items:</b> The Studio is not responsible for or liable to the Student for any lost or stolen items.</li>
                    <li><b>Use of Image and Likeness:</b> The Student grants permission for videos and photographs to be taken of the Student while in the Studio and during the course of the dance lessons, showcases, competitions, company events, etc.</li>
                    <li><b>Non-Solicitation:</b> The Student hereby agrees not to solicit, induce, encourage, or allow an Employee or former employee of the Studio to engage in dance related activities with the Student outside of the Studio regardless of the Employee’s current employment status.</li>
                    <li><b>Gratuity:</b> The Student shall not give or loan anything of value to the Employee except for a tip that would be customary in the industry.</li>
                    <li><b>Liability/Waiver Release:</b> The Student assumes any and all risks involving or arising from his/her participation in the services offered by the Studio, including without limitation, the risk of death, bodily injury or property damage.</li>
                    <li><b>Non-Transferable:</b> This Agreement is not transferable or assignable to any other individual and/or entity.</li>
                    <li><b>Legal Construction:</b> In case any one or more of the provisions contained in this Agreement shall for any reason, be held invalid, illegal, or unenforceable in any respect, the invalidity, illegality, or unenforceability shall not affect any other provision of this agreement.</li>
                    <li><b>Entire Agreement:</b> This Agreement supersedes any prior understandings or written or oral Agreements between the Studio and the Student</li>
                    <li><b>Actions:</b> In the event of any controversy or claim arising out of this Agreement or the Students relationship with the Studio, the Student shall be required to submit written notice to the Studio of its intent to bring a claim.</li>
                    <li><b>Waiver:</b> In the event the Studio relaxes any rules stated herein, the relaxation of same shall not be deemed a waiver of any rights the Studio may otherwise have at a later time.</li>
                    <li><b>Statute of Limitations modifications:</b> Any and all claims brought against the Studio must be brought within twelve months of the accrual of the claim or within six months of the expiration of the one year term of the Agreement, whichever occurs first.</li>
                </ol>
            </td></tr>
            
            <!-- Final Signatures -->
            <tr>
                <td style="width:50%; padding-top:30px; text-align: center;">Student Name</td>
                <td style="width:50%; padding-top:30px; text-align: center;">Date</td>               
            </tr>
        </table>
        
        <!-- Footer -->
        <div class="footer">
            <p><b>Dance With Me Houston</b></p>
            <p><b>' . $company_name . '</b><br>' . $company_full_address . '<br>' . $company_email . ' | ' . $company_phone . '</p>
        </div>
    </body>
    </html>';
        }
    } else {
        return '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>DWM Miscellaneous Agreement</title>
        <style>
            body { 
                font-family: Helvetica, Arial, sans-serif; 
                color: #000; 
                font-size: 12px; 
                line-height: 18px; 
                margin: 0;
                padding: 0;
            }
            .font-bold { font-weight: bold; }
            .font-black { font-weight: 900; }
            p { margin: 0 0 12px; }
            .text-center { text-align: center; }
            .text-end { text-align: right; }
            .logo { max-width: 180px; }
            .line { 
                border-bottom: 1px solid #000; 
                min-height: 20px; 
                width: 100%;
                margin-top: 5px;
                display: inline-block;
            }
            .mb-0 { margin-bottom: 0; }
            .service-block {
                width: 30%;
                padding: 10px 0px;
                vertical-align: top;
            }
            .footer { margin-top: 30px; text-align: center; }
            ol { margin-left: 20px; }
            li { margin-bottom: 8px; }
            .watermark {
                position: fixed;
                top: 40%;
                left: 50%;
                transform: translate(-50%, -50%);
                font-size: 50px;
                color: rgba(0, 0, 0, 0.05);
                white-space: nowrap;
                z-index: -1;
            }
            table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            .page-break { page-break-before: always; }
            hr { border: 1px solid #ddd; }
        </style>
    </head>
    <body>
        <table cellspacing="0" style="width:100%">
            <tbody>
                <tr>
                    <td colspan="3" width="100%">
                    <div style="text-align:center;">
                        ' . $logo_html . '
                    </div>
                </td>
                </tr>
            </tbody>
        </table>
        <!-- MAIN AGREEMENT TABLE (copied structure, no extra colspan complexity) -->

        <table cellspacing="0" style="width:100%">
            <tbody>
                <tr>
                    <td colspan="3" style="font-size:15px;"><em><strong>DANCE WITH ME HOUSTON</strong></em></td>
                </tr>
                <tr>
                    <td colspan="3" style="padding-top:10px; font-size:15px;">COMPETITION AGREEMENT</td>
                </tr>
                <tr>
                    <td colspan="2" style="padding-top:10px; font-size:15px;"><strong><em>NAME:</em>&nbsp;' . $student_name . '</strong></td>
                    <td colspan="1" style="padding-top:10px; font-size:15px; text-align:right;"><strong><em>DATE:</em>&nbsp;' . $enrollment_date . '</strong></td>
                </tr>
                <tr>
                    <td colspan="3" style="padding-top:10px;">Student agrees to purchase and DWM Dance Studio Galleria, LLC. (the &ldquo;studio&rdquo;) agrees to provide the following described course of dance instruction and/or miscellaneous studio service(s) on the following items of tuition. This agreement includes and incorporates by reference the conditions and definitions attached to this form</td>
                </tr>
                <!-- service lines (placeholders) -->
                <tr>
                    <td colspan="3" style="padding-top:10px; font-size:15px;"><strong><em>' . $service_blocks['A']['title'] . '</em></strong></h3>Units: ' . ($service_blocks['A']['units'] ?: '') . '<br>Unit Price: <b><em>$' . ($service_blocks['A']['unit_price'] ? number_format($service_blocks['A']['unit_price'], 2) : '') . '</em></b><br>Total: <b>$' . ($service_blocks['A']['total_price'] ? number_format($service_blocks['A']['total_price'], 2) : '') . '</td>
                </tr>
                <tr>
                    <td colspan="3" style="padding-top:10px; font-size:15px;"><strong><em>' . $service_blocks['B']['title'] . '</em></strong></h3>Units: ' . ($service_blocks['B']['units'] ?: '') . '<br>Unit Price: <b><em>$' . ($service_blocks['B']['unit_price'] ? number_format($service_blocks['B']['unit_price'], 2) : '') . '</em></b><br>Total: <b>$' . ($service_blocks['B']['total_price'] ? number_format($service_blocks['B']['total_price'], 2) : '') . '</td>
                </tr>
                <tr>
                    <td colspan="3" style="padding-top:10px; font-size:15px;"><strong><em>' . $service_blocks['C']['title'] . '</em></strong></h3>Units: ' . ($service_blocks['C']['units'] ?: '') . '<br>Unit Price: <b><em>$' . ($service_blocks['C']['unit_price'] ? number_format($service_blocks['C']['unit_price'], 2) : '') . '</em></b><br>Total: <b>$' . ($service_blocks['C']['total_price'] ? number_format($service_blocks['C']['total_price'], 2) : '') . '</td>
                </tr>
                <tr>
                    <td colspan="2" style="padding-top:10px; font-size:15px;"><strong><em>' . $service_blocks['D']['title'] . '</em></strong></h3>Units: ' . ($service_blocks['D']['units'] ?: '') . '<br>Unit Price: <b><em>$' . ($service_blocks['D']['unit_price'] ? number_format($service_blocks['D']['unit_price'], 2) : '') . '</em></b><br>Total: <b>$' . ($service_blocks['D']['total_price'] ? number_format($service_blocks['D']['total_price'], 2) : '') . '</td>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td colspan="2" style="padding-top:10px; font-size:15px;"><strong><em>' . $service_blocks['E']['title'] . '</em></strong></h3>Units: ' . ($service_blocks['E']['units'] ?: '') . '<br>Unit Price: <b><em>$' . ($service_blocks['E']['unit_price'] ? number_format($service_blocks['E']['unit_price'], 2) : '') . '</em></b><br>Total: <b>$' . ($service_blocks['E']['total_price'] ? number_format($service_blocks['E']['total_price'], 2) : '') . '</td>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td colspan="3" style="padding-top:10px; font-size:15px;"><strong><em>' . $service_blocks['F']['title'] . '</em></strong></h3>Units: ' . ($service_blocks['F']['units'] ?: '') . '<br>Unit Price: <b><em>$' . ($service_blocks['F']['unit_price'] ? number_format($service_blocks['F']['unit_price'], 2) : '') . '</em></b><br>Total: <b>$' . ($service_blocks['F']['total_price'] ? number_format($service_blocks['F']['total_price'], 2) : '') . '</td>
                </tr>
                <!-- financial row: TOTAL | DISCOUNT | SERVICE CHARGE -->
                <tr>
                    <td colspan="1" style="padding-top:10px; font-size:15px;">
                    <p><em><strong>TOTAL</strong></em></p>

                    <p><em><strong>$' . number_format($TOTAL_TUITION, 2) . '</strong></em></p>
                    </td>
                    <td colspan="1" style="text-align:center; font-size:15px; padding-top:10px;">
                    <p><em><strong>DISCOUNT | CREDIT</strong></em></p>

                    <p><em><strong>$' . number_format($TOTAL_DISCOUNT, 2) . '</strong></em></p>
                    </td>
                    <td colspan="1" style="width:30%; padding-top:10px; font-size:15px; text-align:right;">
                    <p><em><strong>SERVICE CHARGE </strong></em></p>
                    <p><em><strong>7%</strong></em></p>

                    <p>&nbsp;</p>
                    </td>
                </tr>
                <!-- second row: GRAND TOTAL | PAID TODAY | BALANCE DUE -->
                <tr>
                    <td colspan="1" style="padding-top:10px; font-size:15px;">
                    <p><em><strong>GRAND TOTAL</strong></em></p>

                    <p><em><strong>$' . number_format($SUBTOTAL, 2) . '</strong></em></p>
                    </td>
                    <td colspan="1" style="text-align:center; font-size:15px; padding-top:10px;">
                    <p><em><strong>PAID TODAY</strong></em></p>

                    <p><em><strong>$' . number_format($enrollment_details->fields['DOWN_PAYMENT'], 2) . '</strong></em></p>
                    </td>
                    <td colspan="1" style="width:30%; padding-top:10px; font-size:15px; text-align:right;">
                    <p><em><strong>BALANCE DUE</strong></em></p>

                    <p><em><strong>$' . number_format($SUBTOTAL - $enrollment_details->fields['DOWN_PAYMENT'], 2) . '</strong></em></p>
                    <!-- placeholder --></td>
                </tr>
                <!-- manager / student signature line (first page signature line, but keep original) -->
                <tr>
                    <td colspan="1" style="text-align:center; width:30%; padding-top:50px;">
                    <p>Manager / Supervisor</p>

                    <p>&nbsp;</p>
                    </td>
                    <td colspan="1" style="text-align:center; width:30%; padding-top:50px;">
                    <p>Student Name</p>

                    <p>&nbsp;</p>
                    </td>
                    <td colspan="1" style="width:30%; padding-top:50px;">&nbsp;</td>
                </tr>
            </tbody>
        </table>

    
        
        <!-- Terms and Conditions (New Page) -->
        <div class="page-break"></div>
        <table>
            <tr><td colspan="3" class="text-center" style="font-size:14px; padding:20px 0;"><b>TERMS AND CONDITIONS</b></td></tr>
            <tr><td colspan="3">
                <ol>
                    <li><b>Term:</b> The Student agrees to prearrange and complete all lessons and/or services provided in this Agreement within one (1) year of the date of the Student Enrollment Agreement Form (Expiration Date: ' . $expiry_date . '). DWM Dance Studio GALLERIA, L.L.C. (hereinafter “Studio”) shall not be obligated to transfer any unused or expired dance lessons and/or services from prior Agreements.</li>
                    <li><b>Credit Card Authorization:</b> Student authorizes DWM Houston to charge the credit card on file for any scheduled lessons or unpaid session fees not paid at the time of booking. Student is responsible for updating the studio with any changes to your payment method.</li>
                    <li><b>Payments:</b> Student agrees to make all payments required under the Agreement in a timely manner; otherwise the Agreement shall terminate immediately upon a default.</li>
                    <li><b>Rescheduling/Cancellation:</b> All cancellations and/or changes to a scheduled lesson must be done twenty four (24) hours prior to the scheduled time of the lesson or the lesson shall be forfeited.</li>
                    <li><b>Rescheduling/Cancellation by the Studio:</b> The Studio may cancel or reschedule any individual or group lesson in its sole discretion at any time for any reason.</li>
                    <li><b>Instructors:</b> The Studio does not guarantee the services of any instructor nor does the Studio guarantee that a request for a particular instructor will be accommodated.</li>
                    <li><b>Refunds:</b> There shall be no refunds for any reason whatsoever except as set forth in paragraph 8.</li>
                    <li><b>Termination by the Studio:</b> The Studio may terminate an Agreement with a Student for good cause. Upon termination the Student shall not be entitled to a refund for any unused lessons.</li>
                    <li><b>Termination by the Student:</b> The student may terminate this agreement within sixty days (60) of the date of the Agreement. Upon termination under this provision, the Student shall receive a refund for only unused lessons paid for.</li>
                    <li><b>Lost Items:</b> The Studio is not responsible for or liable to the Student for any lost or stolen items.</li>
                    <li><b>Use of Image and Likeness:</b> The Student grants permission for videos and photographs to be taken of the Student while in the Studio and during the course of the dance lessons, showcases, competitions, company events, etc.</li>
                    <li><b>Non-Solicitation:</b> The Student hereby agrees not to solicit, induce, encourage, or allow an Employee or former employee of the Studio to engage in dance related activities with the Student outside of the Studio regardless of the Employee’s current employment status.</li>
                    <li><b>Gratuity:</b> The Student shall not give or loan anything of value to the Employee except for a tip that would be customary in the industry.</li>
                    <li><b>Liability/Waiver Release:</b> The Student assumes any and all risks involving or arising from his/her participation in the services offered by the Studio, including without limitation, the risk of death, bodily injury or property damage.</li>
                    <li><b>Non-Transferable:</b> This Agreement is not transferable or assignable to any other individual and/or entity.</li>
                    <li><b>Legal Construction:</b> In case any one or more of the provisions contained in this Agreement shall for any reason, be held invalid, illegal, or unenforceable in any respect, the invalidity, illegality, or unenforceability shall not affect any other provision of this agreement.</li>
                    <li><b>Entire Agreement:</b> This Agreement supersedes any prior understandings or written or oral Agreements between the Studio and the Student</li>
                    <li><b>Actions:</b> In the event of any controversy or claim arising out of this Agreement or the Students relationship with the Studio, the Student shall be required to submit written notice to the Studio of its intent to bring a claim.</li>
                    <li><b>Waiver:</b> In the event the Studio relaxes any rules stated herein, the relaxation of same shall not be deemed a waiver of any rights the Studio may otherwise have at a later time.</li>
                    <li><b>Statute of Limitations modifications:</b> Any and all claims brought against the Studio must be brought within twelve months of the accrual of the claim or within six months of the expiration of the one year term of the Agreement, whichever occurs first.</li>
                </ol>
            </td></tr>
            
            <!-- Final Signatures -->
            <tr>
                <td style="width:50%; padding-top:50px; text-align:center;">Student Name</td>
                <td style="width:50%; padding-top:50px; text-align:center;">Date</td>
            </tr>
        </table>
        
        <!-- Footer -->
        <div class="footer">
            <p><b><em><strong>Dance With Me Houston</strong></em></b></p>
            <p><em><strong><b>' . $company_name . '</b><br>' . $company_full_address . '<br>' . $company_email . ' | ' . $company_phone . '</em></p>
        </div>
    </body>
    </html>';
    }
}
