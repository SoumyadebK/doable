<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
require_once('../global/config.php');
$title = "Data Uploader";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

$PK_ACCOUNT_MASTER = $_SESSION['PK_ACCOUNT_MASTER'];

// Multi-token store: keep the last 5 tokens valid for 10 minutes each.
if (empty($_SESSION['UPLOAD_TOKENS']) || !is_array($_SESSION['UPLOAD_TOKENS'])) {
    $_SESSION['UPLOAD_TOKENS'] = [];
}
foreach ($_SESSION['UPLOAD_TOKENS'] as $t => $data) {
    if ($data['ts'] < time() - 600) {
        unset($_SESSION['UPLOAD_TOKENS'][$t]);
    }
}
$upload_token = bin2hex(random_bytes(16));
$_SESSION['UPLOAD_TOKENS'][$upload_token] = ['ts' => time(), 'used' => false];

if (!empty($_POST)) {

    // ---------- Token guard ----------
    $submitted_token = $_POST['upload_token'] ?? '';
    $token_ok = $submitted_token !== ''
        && isset($_SESSION['UPLOAD_TOKENS'][$submitted_token])
        && $_SESSION['UPLOAD_TOKENS'][$submitted_token]['used'] === false;

    if (!$token_ok) {
        $upload_error = "This upload link has expired or was already used. Please refresh the page and try again.";
    } else {
        $_SESSION['UPLOAD_TOKENS'][$submitted_token]['used'] = true;

        $fileMimes = [
            'text/x-comma-separated-values',
            'text/comma-separated-values',
            'application/octet-stream',
            'application/vnd.ms-excel',
            'application/x-csv',
            'text/x-csv',
            'text/csv',
            'application/csv',
            'application/excel',
            'application/vnd.msexcel',
            'text/plain'
        ];

        if (empty($_FILES['file']['name'])) {
            $upload_error = "No file uploaded.";
        } elseif (!in_array($_FILES['file']['type'], $fileMimes)) {
            $upload_error = "Invalid file type. Please upload a CSV file.";
        } elseif ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $upload_error = "Upload failed with error code " . $_FILES['file']['error'];
        } else {
            $account_data = $db->Execute("SELECT DB_NAME, ENROLLMENT_ID_CHAR, ENROLLMENT_ID_NUM FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER = " . $PK_ACCOUNT_MASTER);
            if (!$account_data || $account_data->RecordCount() == 0) {
                $upload_error = "Account configuration not found.";
            } else {
                $DB_NAME = $account_data->fields['DB_NAME'];

                if ($account_data->fields['ENROLLMENT_ID_CHAR'] != null) {
                    $enrollment_char = $account_data->fields['ENROLLMENT_ID_CHAR'];
                } else {
                    $enrollment_char = 'ENR';
                }

                if (!empty($DB_NAME)) {
                    require_once('../global/common_functions_account.php');
                    $account_database = $DB_NAME;
                    $db_account = new queryFactory();
                    if ($_SERVER['HTTP_HOST'] == 'localhost') {
                        $conn_account = $db_account->connect('localhost', 'root', '', $account_database);
                    } else {
                        $conn_account = $db_account->connect('localhost', 'root', 'b54eawxj5h8ev', $account_database);
                    }

                    if (mysqli_connect_error()) {
                        $upload_error = "Account database connection failed.";
                    } else {
                        $csvFile = fopen($_FILES['file']['tmp_name'], 'r');

                        // Strip UTF-8 BOM
                        $bom = fread($csvFile, 3);
                        if ($bom !== "\xEF\xBB\xBF") {
                            rewind($csvFile);
                        }

                        $lineNumber = 1;
                        $PK_LOCATION = $_POST['PK_LOCATION'];

                        $skipped_rows      = [];
                        $duplicate_rows    = [];
                        $balance_only_rows = [];
                        $misc_rows         = [];

                        // ==========================================================
                        // CSV COLUMN MAP (19 columns)
                        // 0 First Name | 1 Last Name | 2 Email | 3 Phone
                        // 4-7 Completed counts | 8 Completed total
                        // 9-12 Active counts   | 13 Active total
                        // 14 amount owed on remaining services (info only)
                        // 15 $ Amount Paid Ahead or Amount Behind (account balance)
                        // 16-17 Partner
                        // 18 amount paid on remaining services
                        // ==========================================================
                        while (($getData = fgetcsv($csvFile, 10000, ",")) !== FALSE) {
                            if ($lineNumber === 1) {
                                $lineNumber++;
                                continue;
                            }

                            if (count($getData) < 4 || (empty($getData[0]) && empty($getData[2]))) {
                                $lineNumber++;
                                continue;
                            }

                            // Reset per-row state
                            $PK_USER             = 0;
                            $PK_USER_MASTER      = 0;
                            $USER_ID             = 0;
                            $PK_CUSTOMER_DETAILS = 0;

                            // Parse numeric columns
                            $priv_completed  = (float)($getData[4]  ?? 0);
                            $grp_completed   = (float)($getData[5]  ?? 0);
                            $party_completed = (float)($getData[6]  ?? 0);
                            $coach_completed = (float)($getData[7]  ?? 0);
                            $cost_completed  = (float)($getData[8]  ?? 0);

                            $priv_active     = (float)($getData[9]  ?? 0);
                            $grp_active      = (float)($getData[10] ?? 0);
                            $party_active    = (float)($getData[11] ?? 0);
                            $coach_active    = (float)($getData[12] ?? 0);
                            $cost_active     = (float)($getData[13] ?? 0);

                            $account_balance = (float)($getData[15] ?? 0);

                            // Col 18 = explicit "amount paid on remaining services"
                            $amount_paid_active_raw = isset($getData[18]) ? trim($getData[18]) : '';
                            $amount_paid_active = ($amount_paid_active_raw === '') ? null : (float)$amount_paid_active_raw;

                            // Partner columns
                            $partner_first = isset($getData[16]) ? trim($getData[16]) : '';
                            $partner_last  = isset($getData[17]) ? trim($getData[17]) : '';

                            // ---------------- CUSTOMER ----------------
                            $email_escaped = addslashes(trim($getData[2]));
                            $customer_exist = $db->Execute("SELECT DOA_USERS.PK_USER, DOA_USER_MASTER.PK_USER_MASTER, DOA_USERS.USER_ID 
                                                            FROM DOA_USERS 
                                                            INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER 
                                                            WHERE DOA_USERS.EMAIL_ID = '$email_escaped' 
                                                            AND DOA_USER_MASTER.PRIMARY_LOCATION_ID = '$PK_LOCATION' 
                                                            AND DOA_USERS.PK_ACCOUNT_MASTER = " . $PK_ACCOUNT_MASTER);

                            if ($customer_exist->RecordCount() > 0) {
                                $PK_USER        = $customer_exist->fields['PK_USER'];
                                $PK_USER_MASTER = $customer_exist->fields['PK_USER_MASTER'];
                                $USER_ID        = $customer_exist->fields['USER_ID'];
                            } else {
                                $USER_DATA['PK_ACCOUNT_MASTER'] = $PK_ACCOUNT_MASTER;
                                $USER_DATA['FIRST_NAME']        = trim($getData[0]);
                                $USER_DATA['LAST_NAME']         = trim($getData[1]);
                                $USER_DATA['EMAIL_ID']          = $getData[2];
                                $USER_DATA['PHONE']             = $getData[3];
                                $USER_DATA['DOB']               = null;
                                $USER_DATA['ADDRESS']           = '';
                                $USER_DATA['ADDRESS_1']         = null;
                                $USER_DATA['CITY']              = '';
                                $USER_DATA['ZIP']               = '';
                                $USER_DATA['PK_COUNTRY']        = 1;
                                $USER_DATA['PK_STATES']         = 0;
                                $USER_DATA['IS_DELETED']        = 0;
                                $USER_DATA['ACTIVE']            = 1;
                                $USER_DATA['CREATED_BY']        = $_SESSION['PK_USER'];
                                $USER_DATA['CREATED_ON']        = date("Y-m-d H:i");
                                db_perform('DOA_USERS', $USER_DATA, 'insert');
                                $PK_USER = $db->insert_ID();

                                if ($PK_USER) {
                                    $USER_ROLE_DATA['PK_USER']  = $PK_USER;
                                    $USER_ROLE_DATA['PK_ROLES'] = 4;
                                    db_perform('DOA_USER_ROLES', $USER_ROLE_DATA, 'insert');

                                    $USER_LOCATION_DATA['PK_USER']     = $PK_USER;
                                    $USER_LOCATION_DATA['PK_LOCATION'] = $PK_LOCATION;
                                    db_perform('DOA_USER_LOCATION', $USER_LOCATION_DATA, 'insert');

                                    $USER_MASTER_DATA['PK_USER']             = $PK_USER;
                                    $USER_MASTER_DATA['PK_ACCOUNT_MASTER']   = $PK_ACCOUNT_MASTER;
                                    $USER_MASTER_DATA['PRIMARY_LOCATION_ID'] = $PK_LOCATION;
                                    $USER_MASTER_DATA['CREATED_BY']          = $_SESSION['PK_USER'];
                                    $USER_MASTER_DATA['CREATED_ON']          = date("Y-m-d H:i");
                                    db_perform('DOA_USER_MASTER', $USER_MASTER_DATA, 'insert');
                                    $PK_USER_MASTER = $db->insert_ID();
                                }

                                if ($PK_USER_MASTER) {
                                    $USER_DATA_ACCOUNT['PK_USER_MASTER_DB'] = $PK_USER;
                                    $USER_DATA_ACCOUNT['PK_ACCOUNT_MASTER'] = $PK_ACCOUNT_MASTER;
                                    $USER_DATA_ACCOUNT['FIRST_NAME']        = trim($getData[0]);
                                    $USER_DATA_ACCOUNT['LAST_NAME']         = trim($getData[1]);
                                    $USER_DATA_ACCOUNT['EMAIL_ID']          = $getData[2];
                                    $USER_DATA_ACCOUNT['PHONE']             = $getData[3];
                                    $USER_DATA_ACCOUNT['CREATED_BY']        = $_SESSION['PK_USER'];
                                    $USER_DATA_ACCOUNT['CREATED_ON']        = date("Y-m-d H:i");
                                    db_perform_account('DOA_USERS', $USER_DATA_ACCOUNT, 'insert');

                                    $CUSTOMER_DATA['PK_USER_MASTER']     = $PK_USER_MASTER;
                                    $CUSTOMER_DATA['FIRST_NAME']         = trim($getData[0]);
                                    $CUSTOMER_DATA['LAST_NAME']          = trim($getData[1]);
                                    $CUSTOMER_DATA['EMAIL']              = $getData[2];
                                    $CUSTOMER_DATA['PHONE']              = $getData[3];
                                    $CUSTOMER_DATA['ATTENDING_WITH']     = (!empty($partner_first) || !empty($partner_last)) ? 'With a Partner' : 'Solo';
                                    $CUSTOMER_DATA['PARTNER_FIRST_NAME'] = $partner_first;
                                    $CUSTOMER_DATA['PARTNER_LAST_NAME']  = $partner_last;
                                    db_perform_account('DOA_CUSTOMER_DETAILS', $CUSTOMER_DATA, 'insert');
                                    $PK_CUSTOMER_DETAILS = $db_account->insert_ID();
                                }
                            }

                            if (empty($PK_USER_MASTER)) {
                                $skipped_rows[] = "Line $lineNumber — could not resolve customer for email " . htmlspecialchars($getData[2]);
                                $lineNumber++;
                                continue;
                            }

                            // Track whether this row created any enrollment
                            $row_created_enrollment = false;

                            // ==========================================================
                            // COMPLETED ENROLLMENT (cols 4–8)
                            // ==========================================================
                            $TOTAL_LESSONS = $priv_completed + $grp_completed + $party_completed + $coach_completed;
                            if ($TOTAL_LESSONS > 0) {
                                $ENROLLMENT_DATA = [];
                                $dup_check = $db_account->Execute("SELECT PK_ENROLLMENT_MASTER 
                                                                    FROM DOA_ENROLLMENT_MASTER 
                                                                    WHERE PK_USER_MASTER = " . $PK_USER_MASTER . " 
                                                                    AND PK_LOCATION = " . intval($PK_LOCATION) . " 
                                                                    AND STATUS = 'CO' 
                                                                    AND ENROLLMENT_DATE = '" . date('Y-m-d') . "' 
                                                                    LIMIT 1");
                                if ($dup_check && $dup_check->RecordCount() > 0) {
                                    $duplicate_rows[] = "Line $lineNumber — completed enrollment already exists for " . htmlspecialchars($getData[2]);
                                } else {
                                    $enrollment_data = $db_account->Execute("SELECT ENROLLMENT_ID FROM `DOA_ENROLLMENT_MASTER` 
                                                                            WHERE `PK_USER_MASTER` = " . $PK_USER_MASTER . " 
                                                                            ORDER BY PK_ENROLLMENT_MASTER DESC LIMIT 1");
                                    if ($enrollment_data && $enrollment_data->RecordCount() > 0) {
                                        $last_enrollment_id = str_replace($enrollment_char, '', $enrollment_data->fields['ENROLLMENT_ID']);
                                        $ENROLLMENT_DATA['ENROLLMENT_ID'] = $enrollment_char . (intval($last_enrollment_id) + 1);
                                    } else {
                                        $ENROLLMENT_DATA['ENROLLMENT_ID'] = $enrollment_char . $account_data->fields['ENROLLMENT_ID_NUM'];
                                    }

                                    $customer_enrollment_number = $db_account->Execute("SELECT CUSTOMER_ENROLLMENT_NUMBER FROM `DOA_ENROLLMENT_MASTER` 
                                                                                        WHERE PK_USER_MASTER = " . $PK_USER_MASTER . " 
                                                                                        ORDER BY PK_ENROLLMENT_MASTER DESC LIMIT 1");
                                    if ($customer_enrollment_number && $customer_enrollment_number->RecordCount() > 0) {
                                        $ENROLLMENT_DATA['CUSTOMER_ENROLLMENT_NUMBER'] = $customer_enrollment_number->fields['CUSTOMER_ENROLLMENT_NUMBER'] + 1;
                                    } else {
                                        $ENROLLMENT_DATA['CUSTOMER_ENROLLMENT_NUMBER'] = 1;
                                    }

                                    $cen = $ENROLLMENT_DATA['CUSTOMER_ENROLLMENT_NUMBER'];
                                    if ($cen <= 1)        $ENROLLMENT_DATA['PK_ENROLLMENT_TYPE'] = 5;
                                    else if ($cen == 2)   $ENROLLMENT_DATA['PK_ENROLLMENT_TYPE'] = 2;
                                    else if ($cen == 3)   $ENROLLMENT_DATA['PK_ENROLLMENT_TYPE'] = 13;
                                    else                  $ENROLLMENT_DATA['PK_ENROLLMENT_TYPE'] = 9;

                                    $TOTAL_COST        = $cost_completed;
                                    $PRICE_PER_SESSION = ($priv_completed > 0) ? $TOTAL_COST / $priv_completed : 0;

                                    $ENROLLMENT_DATA['PK_USER_MASTER']   = $PK_USER_MASTER;
                                    $ENROLLMENT_DATA['PK_LOCATION']      = $PK_LOCATION;
                                    $ENROLLMENT_DATA['CHARGE_TYPE']      = 'Session';
                                    $ENROLLMENT_DATA['ENROLLMENT_BY_ID'] = $_SESSION['PK_USER'];
                                    $ENROLLMENT_DATA['ACTIVE']           = 1;
                                    $ENROLLMENT_DATA['STATUS']           = "CO";
                                    $ENROLLMENT_DATA['ENROLLMENT_DATE']  = date("Y-m-d");
                                    $ENROLLMENT_DATA['EXPIRY_DATE']      = date("Y-m-d", strtotime("+1 month"));
                                    $ENROLLMENT_DATA['CREATED_BY']       = $_SESSION['PK_USER'];
                                    $ENROLLMENT_DATA['CREATED_ON']       = date("Y-m-d H:i");
                                    db_perform_account('DOA_ENROLLMENT_MASTER', $ENROLLMENT_DATA, 'insert');
                                    $PK_ENROLLMENT_MASTER = $db_account->insert_ID();

                                    $BILLING_DATA = [
                                        'PK_ENROLLMENT_MASTER' => $PK_ENROLLMENT_MASTER,
                                        'BILLING_REF'          => '',
                                        'BILLING_DATE'         => date('Y-m-d'),
                                        'ACTUAL_AMOUNT'        => $TOTAL_COST,
                                        'DISCOUNT'             => 0,
                                        'DOWN_PAYMENT'         => 0,
                                        'BALANCE_PAYABLE'      => 0,
                                        'TOTAL_AMOUNT'         => $TOTAL_COST,
                                        'PAYMENT_METHOD'       => 'One Time',
                                        'PAYMENT_TERM'         => '',
                                        'NUMBER_OF_PAYMENT'    => 0,
                                        'FIRST_DUE_DATE'       => date('Y-m-d'),
                                        'INSTALLMENT_AMOUNT'   => 0,
                                    ];
                                    db_perform_account('DOA_ENROLLMENT_BILLING', $BILLING_DATA, 'insert');
                                    $PK_ENROLLMENT_BILLING = $db_account->insert_ID();

                                    $services = [
                                        ['col' => $priv_completed,  'like' => 'Private', 'price' => $PRICE_PER_SESSION, 'total' => $TOTAL_COST, 'paid' => $TOTAL_COST],
                                        ['col' => $grp_completed,   'like' => 'Group',   'price' => 0,                  'total' => 0,          'paid' => 0],
                                        ['col' => $party_completed, 'like' => 'Party',   'price' => 0,                  'total' => 0,          'paid' => 0],
                                        ['col' => $coach_completed, 'like' => 'Coach',   'price' => 0,                  'total' => 0,          'paid' => 0],
                                    ];
                                    foreach ($services as $svc) {
                                        if ($svc['col'] <= 0) continue;
                                        $service_details = $db_account->Execute("SELECT DOA_SERVICE_MASTER.PK_SERVICE_MASTER, DOA_SERVICE_MASTER.DESCRIPTION, DOA_SERVICE_CODE.PK_SERVICE_CODE 
                                                                                FROM DOA_SERVICE_MASTER 
                                                                                LEFT JOIN DOA_SERVICE_CODE ON DOA_SERVICE_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_CODE.PK_SERVICE_MASTER 
                                                                                WHERE SERVICE_NAME LIKE '%" . $svc['like'] . "%'");
                                        if (!$service_details || $service_details->RecordCount() == 0) continue;

                                        $SERVICE_DATA = [
                                            'PK_ENROLLMENT_MASTER' => $PK_ENROLLMENT_MASTER,
                                            'PK_SERVICE_MASTER'    => $service_details->fields['PK_SERVICE_MASTER'],
                                            'PK_SERVICE_CODE'      => $service_details->fields['PK_SERVICE_CODE'],
                                            'SERVICE_DETAILS'      => $service_details->fields['DESCRIPTION'],
                                            'NUMBER_OF_SESSION'    => $svc['col'],
                                            'PRICE_PER_SESSION'    => $svc['price'],
                                            'TOTAL'                => $svc['total'],
                                            'TOTAL_AMOUNT_PAID'    => $svc['paid'],
                                            'DISCOUNT'             => 0,
                                            'FINAL_AMOUNT'         => $svc['total'],
                                            'STATUS'               => 'CO',
                                        ];
                                        db_perform_account('DOA_ENROLLMENT_SERVICE', $SERVICE_DATA, 'insert');
                                    }

                                    $BILLING_LEDGER_DATA = [
                                        'PK_ENROLLMENT_MASTER'     => $PK_ENROLLMENT_MASTER,
                                        'PK_ENROLLMENT_BILLING '   => $PK_ENROLLMENT_BILLING,
                                        'TRANSACTION_TYPE'         => 'Billing',
                                        'ENROLLMENT_LEDGER_PARENT' => 0,
                                        'DUE_DATE'                 => date('Y-m-d'),
                                        'BILLED_AMOUNT'            => $TOTAL_COST,
                                        'PAID_AMOUNT'              => $TOTAL_COST,
                                        'BALANCE'                  => $TOTAL_COST,
                                        'IS_PAID'                  => 1,
                                        'STATUS'                   => 'CO',
                                        'IS_DOWN_PAYMENT'          => 0,
                                    ];
                                    db_perform_account('DOA_ENROLLMENT_LEDGER', $BILLING_LEDGER_DATA, 'insert');
                                    $PK_ENROLLMENT_LEDGER = $db_account->insert_ID();

                                    $ENROLLMENT_PAYMENT_DATA = [
                                        'PK_ENROLLMENT_MASTER'  => $PK_ENROLLMENT_MASTER,
                                        'PK_ENROLLMENT_BILLING' => $PK_ENROLLMENT_BILLING,
                                        'PK_PAYMENT_TYPE'       => 12,
                                        'PK_ENROLLMENT_LEDGER'  => $PK_ENROLLMENT_LEDGER,
                                        'TYPE'                  => 'Payment',
                                        'AMOUNT'                => $TOTAL_COST,
                                        'NOTE'                  => '',
                                        'PAYMENT_DATE'          => date('Y-m-d'),
                                        'PAYMENT_INFO'          => '',
                                        'PAYMENT_STATUS'        => 'Success',
                                    ];
                                    db_perform_account('DOA_ENROLLMENT_PAYMENT', $ENROLLMENT_PAYMENT_DATA, 'insert');

                                    $row_created_enrollment = true;
                                }
                            }

                            // ==========================================================
                            // ACTIVE ENROLLMENT (cols 9–13)
                            // ==========================================================
                            $TOTAL_LESSONS = $priv_active + $grp_active + $party_active + $coach_active;
                            if ($TOTAL_LESSONS > 0) {
                                $ENROLLMENT_DATA = [];
                                $dup_check = $db_account->Execute("SELECT PK_ENROLLMENT_MASTER 
                                                                    FROM DOA_ENROLLMENT_MASTER 
                                                                    WHERE PK_USER_MASTER = " . $PK_USER_MASTER . " 
                                                                    AND PK_LOCATION = " . intval($PK_LOCATION) . " 
                                                                    AND STATUS = 'A' 
                                                                    AND ENROLLMENT_DATE = '" . date('Y-m-d') . "' 
                                                                    LIMIT 1");
                                if ($dup_check && $dup_check->RecordCount() > 0) {
                                    $duplicate_rows[] = "Line $lineNumber — active enrollment already exists for " . htmlspecialchars($getData[2]);
                                } else {
                                    $enrollment_data = $db_account->Execute("SELECT ENROLLMENT_ID FROM `DOA_ENROLLMENT_MASTER` 
                                                                            WHERE `PK_USER_MASTER` = " . $PK_USER_MASTER . " 
                                                                            ORDER BY PK_ENROLLMENT_MASTER DESC LIMIT 1");
                                    if ($enrollment_data && $enrollment_data->RecordCount() > 0) {
                                        $last_enrollment_id = str_replace($enrollment_char, '', $enrollment_data->fields['ENROLLMENT_ID']);
                                        $ENROLLMENT_DATA['ENROLLMENT_ID'] = $enrollment_char . (intval($last_enrollment_id) + 1);
                                    } else {
                                        $ENROLLMENT_DATA['ENROLLMENT_ID'] = $enrollment_char . $account_data->fields['ENROLLMENT_ID_NUM'];
                                    }

                                    $customer_enrollment_number = $db_account->Execute("SELECT CUSTOMER_ENROLLMENT_NUMBER FROM `DOA_ENROLLMENT_MASTER` 
                                                                                        WHERE PK_USER_MASTER = " . $PK_USER_MASTER . " 
                                                                                        ORDER BY PK_ENROLLMENT_MASTER DESC LIMIT 1");
                                    if ($customer_enrollment_number && $customer_enrollment_number->RecordCount() > 0) {
                                        $ENROLLMENT_DATA['CUSTOMER_ENROLLMENT_NUMBER'] = $customer_enrollment_number->fields['CUSTOMER_ENROLLMENT_NUMBER'] + 1;
                                    } else {
                                        $ENROLLMENT_DATA['CUSTOMER_ENROLLMENT_NUMBER'] = 1;
                                    }

                                    $cen = $ENROLLMENT_DATA['CUSTOMER_ENROLLMENT_NUMBER'];
                                    if ($cen <= 1)        $ENROLLMENT_DATA['PK_ENROLLMENT_TYPE'] = 5;
                                    else if ($cen == 2)   $ENROLLMENT_DATA['PK_ENROLLMENT_TYPE'] = 2;
                                    else if ($cen == 3)   $ENROLLMENT_DATA['PK_ENROLLMENT_TYPE'] = 13;
                                    else                  $ENROLLMENT_DATA['PK_ENROLLMENT_TYPE'] = 9;

                                    $TOTAL_COST        = $cost_active;
                                    $PRICE_PER_SESSION = ($priv_active > 0) ? $TOTAL_COST / $priv_active : 0;

                                    if ($amount_paid_active === null) {
                                        $prepaid_lessons_value = $TOTAL_COST;
                                    } else {
                                        $prepaid_lessons_value = $amount_paid_active;
                                    }
                                    if ($prepaid_lessons_value > $TOTAL_COST) {
                                        $prepaid_lessons_value = $TOTAL_COST;
                                    }

                                    $TOTAL_PAID      = $prepaid_lessons_value;
                                    $TOTAL_REMAINING = max(0, $TOTAL_COST - $prepaid_lessons_value);

                                    $ENROLLMENT_DATA['PK_USER_MASTER']   = $PK_USER_MASTER;
                                    $ENROLLMENT_DATA['PK_LOCATION']      = $PK_LOCATION;
                                    $ENROLLMENT_DATA['CHARGE_TYPE']      = 'Session';
                                    $ENROLLMENT_DATA['ENROLLMENT_BY_ID'] = $_SESSION['PK_USER'];
                                    $ENROLLMENT_DATA['ACTIVE']           = 1;
                                    $ENROLLMENT_DATA['STATUS']           = "A";
                                    $ENROLLMENT_DATA['ENROLLMENT_DATE']  = date("Y-m-d");
                                    $ENROLLMENT_DATA['EXPIRY_DATE']      = date("Y-m-d", strtotime("+1 month"));
                                    $ENROLLMENT_DATA['CREATED_BY']       = $_SESSION['PK_USER'];
                                    $ENROLLMENT_DATA['CREATED_ON']       = date("Y-m-d H:i");
                                    db_perform_account('DOA_ENROLLMENT_MASTER', $ENROLLMENT_DATA, 'insert');
                                    $PK_ENROLLMENT_MASTER = $db_account->insert_ID();

                                    $BILLING_DATA = [
                                        'PK_ENROLLMENT_MASTER' => $PK_ENROLLMENT_MASTER,
                                        'BILLING_REF'          => '',
                                        'BILLING_DATE'         => date('Y-m-d'),
                                        'ACTUAL_AMOUNT'        => $TOTAL_COST,
                                        'DISCOUNT'             => 0,
                                        'DOWN_PAYMENT'         => 0,
                                        'BALANCE_PAYABLE'      => $TOTAL_REMAINING,
                                        'TOTAL_AMOUNT'         => $TOTAL_COST,
                                        'PAYMENT_METHOD'       => 'One Time',
                                        'PAYMENT_TERM'         => '',
                                        'NUMBER_OF_PAYMENT'    => 0,
                                        'FIRST_DUE_DATE'       => date('Y-m-d'),
                                        'INSTALLMENT_AMOUNT'   => 0,
                                    ];
                                    db_perform_account('DOA_ENROLLMENT_BILLING', $BILLING_DATA, 'insert');
                                    $PK_ENROLLMENT_BILLING = $db_account->insert_ID();

                                    $services = [
                                        ['col' => $priv_active,  'like' => 'Private', 'price' => $PRICE_PER_SESSION, 'total' => $TOTAL_COST, 'paid' => $TOTAL_PAID],
                                        ['col' => $grp_active,   'like' => 'Group',   'price' => 0,                  'total' => 0,          'paid' => 0],
                                        ['col' => $party_active, 'like' => 'Party',   'price' => 0,                  'total' => 0,          'paid' => 0],
                                        ['col' => $coach_active, 'like' => 'Coach',   'price' => 0,                  'total' => 0,          'paid' => 0],
                                    ];
                                    foreach ($services as $svc) {
                                        if ($svc['col'] <= 0) continue;
                                        $service_details = $db_account->Execute("SELECT DOA_SERVICE_MASTER.PK_SERVICE_MASTER, DOA_SERVICE_MASTER.DESCRIPTION, DOA_SERVICE_CODE.PK_SERVICE_CODE 
                                                                                FROM DOA_SERVICE_MASTER 
                                                                                LEFT JOIN DOA_SERVICE_CODE ON DOA_SERVICE_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_CODE.PK_SERVICE_MASTER 
                                                                                WHERE SERVICE_NAME LIKE '%" . $svc['like'] . "%'");
                                        if (!$service_details || $service_details->RecordCount() == 0) continue;

                                        $SERVICE_DATA = [
                                            'PK_ENROLLMENT_MASTER' => $PK_ENROLLMENT_MASTER,
                                            'PK_SERVICE_MASTER'    => $service_details->fields['PK_SERVICE_MASTER'],
                                            'PK_SERVICE_CODE'      => $service_details->fields['PK_SERVICE_CODE'],
                                            'SERVICE_DETAILS'      => $service_details->fields['DESCRIPTION'],
                                            'NUMBER_OF_SESSION'    => $svc['col'],
                                            'PRICE_PER_SESSION'    => $svc['price'],
                                            'TOTAL'                => $svc['total'],
                                            'TOTAL_AMOUNT_PAID'    => $svc['paid'],
                                            'DISCOUNT'             => 0,
                                            'FINAL_AMOUNT'         => $svc['total'],
                                            'STATUS'               => 'A',
                                        ];
                                        db_perform_account('DOA_ENROLLMENT_SERVICE', $SERVICE_DATA, 'insert');
                                    }

                                    if ($prepaid_lessons_value > 0) {
                                        $BILLING_LEDGER_DATA = [
                                            'PK_ENROLLMENT_MASTER'     => $PK_ENROLLMENT_MASTER,
                                            'PK_ENROLLMENT_BILLING '   => $PK_ENROLLMENT_BILLING,
                                            'TRANSACTION_TYPE'         => 'Billing',
                                            'ENROLLMENT_LEDGER_PARENT' => 0,
                                            'DUE_DATE'                 => date('Y-m-d'),
                                            'BILLED_AMOUNT'            => $prepaid_lessons_value,
                                            'PAID_AMOUNT'              => 0,
                                            'BALANCE'                  => $prepaid_lessons_value,
                                            'IS_PAID'                  => 1,
                                            'STATUS'                   => 'A',
                                            'IS_DOWN_PAYMENT'          => 0,
                                        ];
                                        db_perform_account('DOA_ENROLLMENT_LEDGER', $BILLING_LEDGER_DATA, 'insert');
                                        $PK_ENROLLMENT_LEDGER = $db_account->insert_ID();

                                        $ENROLLMENT_PAYMENT_DATA = [
                                            'PK_ENROLLMENT_MASTER'  => $PK_ENROLLMENT_MASTER,
                                            'PK_ENROLLMENT_BILLING' => $PK_ENROLLMENT_BILLING,
                                            'PK_PAYMENT_TYPE'       => 12,
                                            'PK_ENROLLMENT_LEDGER'  => $PK_ENROLLMENT_LEDGER,
                                            'TYPE'                  => 'Payment',
                                            'AMOUNT'                => $prepaid_lessons_value,
                                            'NOTE'                  => 'Prepaid lessons imported from Mindbody',
                                            'PAYMENT_DATE'          => date('Y-m-d'),
                                            'PAYMENT_INFO'          => '',
                                            'PAYMENT_STATUS'        => 'Success',
                                        ];
                                        db_perform_account('DOA_ENROLLMENT_PAYMENT', $ENROLLMENT_PAYMENT_DATA, 'insert');
                                    }

                                    if ($TOTAL_REMAINING > 0) {
                                        $BILLING_LEDGER_DATA2 = [
                                            'PK_ENROLLMENT_MASTER'     => $PK_ENROLLMENT_MASTER,
                                            'PK_ENROLLMENT_BILLING '   => $PK_ENROLLMENT_BILLING,
                                            'TRANSACTION_TYPE'         => 'Billing',
                                            'ENROLLMENT_LEDGER_PARENT' => 0,
                                            'DUE_DATE'                 => date('Y-m-d'),
                                            'BILLED_AMOUNT'            => $TOTAL_REMAINING,
                                            'PAID_AMOUNT'              => 0,
                                            'BALANCE'                  => $TOTAL_REMAINING,
                                            'IS_PAID'                  => 0,
                                            'STATUS'                   => 'A',
                                            'IS_DOWN_PAYMENT'          => 0,
                                        ];
                                        db_perform_account('DOA_ENROLLMENT_LEDGER', $BILLING_LEDGER_DATA2, 'insert');
                                    }

                                    $row_created_enrollment = true;
                                }
                            }

                            // ==========================================================
                            // MISC ENROLLMENT for col 15 balance
                            // Rule:
                            //   - If the row created ANY completed or active enrollment
                            //     AND col 15 is non-zero → create a misc enrollment
                            //     for the amount (positive = credit, negative = owed).
                            //   - If the row created no enrollment (no lessons at all)
                            //     AND col 15 is negative → create a misc enrollment
                            //     for the owed amount (existing behavior).
                            //   - Misc enrollments are ALWAYS created as ACTIVE (STATUS='A').
                            // ==========================================================
                            $should_create_misc = false;
                            $misc_amount = 0;

                            if ($row_created_enrollment && $account_balance != 0) {
                                $should_create_misc = true;
                                $misc_amount = abs($account_balance);
                            } elseif (!$row_created_enrollment && $account_balance < 0) {
                                $should_create_misc = true;
                                $misc_amount = abs($account_balance);
                            }

                            // ==========================================================
                            // ENSURE MISCELLANEOUS SERVICE EXISTS
                            // ==========================================================
                            $misc_service_check = $db_account->Execute("SELECT DOA_SERVICE_MASTER.PK_SERVICE_MASTER,
                                                   DOA_SERVICE_MASTER.DESCRIPTION,
                                                   DOA_SERVICE_CODE.PK_SERVICE_CODE
                                            FROM DOA_SERVICE_MASTER
                                            LEFT JOIN DOA_SERVICE_CODE ON DOA_SERVICE_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_CODE.PK_SERVICE_MASTER
                                            WHERE DOA_SERVICE_MASTER.PK_SERVICE_CLASS = 5
                                               OR DOA_SERVICE_MASTER.SERVICE_NAME LIKE '%Misc%'
                                            LIMIT 1");

                            if (!$misc_service_check || $misc_service_check->RecordCount() == 0) {
                                // Create the service master
                                $MISC_SERVICE_MASTER = [
                                    'SERVICE_NAME'     => 'Miscellaneous',
                                    'PK_SERVICE_CLASS' => 5,
                                    'IS_SCHEDULE'      => 0,
                                    'DESCRIPTION'      => 'Miscellaneous charges and adjustments',
                                    'ACTIVE'           => 1,
                                    'IS_DELETED'       => 0,
                                    'CREATED_BY'       => $_SESSION['PK_USER'],
                                    'CREATED_ON'       => date('Y-m-d H:i'),
                                ];
                                db_perform_account('DOA_SERVICE_MASTER', $MISC_SERVICE_MASTER, 'insert');
                                $PK_NEW_SERVICE_MASTER = $db_account->insert_ID();

                                // Create the service code
                                $MISC_SERVICE_CODE = [
                                    'PK_SERVICE_MASTER' => $PK_NEW_SERVICE_MASTER,
                                    'SERVICE_CODE'      => 'MISC',
                                    'DESCRIPTION'       => 'Miscellaneous',
                                    'IS_GROUP'          => 0,
                                    'IS_SUNDRY'         => 0,
                                    'CAPACITY'          => 0,
                                    'IS_CHARGEABLE'     => 1,
                                    'PRICE'             => 0,
                                    'COUNT_ON_CALENDAR' => 0,
                                    'SORT_ORDER'        => 999,
                                ];
                                db_perform_account('DOA_SERVICE_CODE', $MISC_SERVICE_CODE, 'insert');
                            }

                            if ($should_create_misc && $misc_amount > 0) {

                                $dup_check = $db_account->Execute("SELECT PK_ENROLLMENT_MASTER 
                                        FROM DOA_ENROLLMENT_MASTER 
                                        WHERE PK_USER_MASTER = " . $PK_USER_MASTER . " 
                                        AND PK_LOCATION = " . intval($PK_LOCATION) . " 
                                        AND CHARGE_TYPE = 'Miscellaneous' 
                                        AND ENROLLMENT_DATE = '" . date('Y-m-d') . "' 
                                        LIMIT 1");

                                if ($dup_check && $dup_check->RecordCount() > 0) {
                                    $duplicate_rows[] = "Line $lineNumber — miscellaneous enrollment already exists for " . htmlspecialchars($getData[2]);
                                } else {
                                    $ENROLLMENT_DATA = [];

                                    // Enrollment ID
                                    $enrollment_data = $db_account->Execute("SELECT ENROLLMENT_ID FROM `DOA_ENROLLMENT_MASTER` 
                                                WHERE `PK_USER_MASTER` = " . $PK_USER_MASTER . " 
                                                ORDER BY PK_ENROLLMENT_MASTER DESC LIMIT 1");
                                    if ($enrollment_data && $enrollment_data->RecordCount() > 0) {
                                        $last_enrollment_id = str_replace($enrollment_char, '', $enrollment_data->fields['ENROLLMENT_ID']);
                                        $ENROLLMENT_DATA['ENROLLMENT_ID'] = $enrollment_char . (intval($last_enrollment_id) + 1);
                                    } else {
                                        $ENROLLMENT_DATA['ENROLLMENT_ID'] = $enrollment_char . $account_data->fields['ENROLLMENT_ID_NUM'];
                                    }

                                    // customer enrollment number
                                    $cen_data = $db_account->Execute("SELECT CUSTOMER_ENROLLMENT_NUMBER FROM `DOA_ENROLLMENT_MASTER` 
                                         WHERE PK_USER_MASTER = " . $PK_USER_MASTER . " 
                                         ORDER BY PK_ENROLLMENT_MASTER DESC LIMIT 1");
                                    $ENROLLMENT_DATA['CUSTOMER_ENROLLMENT_NUMBER'] = ($cen_data && $cen_data->RecordCount() > 0)
                                        ? $cen_data->fields['CUSTOMER_ENROLLMENT_NUMBER'] + 1 : 1;

                                    $ENROLLMENT_DATA['PK_USER_MASTER']     = $PK_USER_MASTER;
                                    $ENROLLMENT_DATA['PK_LOCATION']        = $PK_LOCATION;
                                    $ENROLLMENT_DATA['CHARGE_TYPE']        = 'Miscellaneous';
                                    $ENROLLMENT_DATA['ENROLLMENT_BY_ID']   = $_SESSION['PK_USER'];
                                    $ENROLLMENT_DATA['ACTIVE']             = 1;
                                    $ENROLLMENT_DATA['STATUS']             = 'A';   // ACTIVE
                                    $ENROLLMENT_DATA['ALL_APPOINTMENT_DONE'] = 0;   // explicit — ensure it lands in Active tab
                                    $ENROLLMENT_DATA['COMPLETED_DATE']     = null;  // explicit — ensure it doesn't leak from reused array
                                    $ENROLLMENT_DATA['ENROLLMENT_DATE']    = date('Y-m-d');
                                    $ENROLLMENT_DATA['EXPIRY_DATE']        = date('Y-m-d', strtotime('+1 month'));
                                    $ENROLLMENT_DATA['CREATED_BY']         = $_SESSION['PK_USER'];
                                    $ENROLLMENT_DATA['CREATED_ON']         = date('Y-m-d H:i');
                                    $ENROLLMENT_DATA['PK_ENROLLMENT_TYPE'] = 5;

                                    db_perform_account('DOA_ENROLLMENT_MASTER', $ENROLLMENT_DATA, 'insert');
                                    $PK_ENROLLMENT_MASTER = $db_account->insert_ID();

                                    // ---- BILLING ----
                                    $BILLING_DATA = [
                                        'PK_ENROLLMENT_MASTER' => $PK_ENROLLMENT_MASTER,
                                        'BILLING_REF'          => '',
                                        'BILLING_DATE'         => date('Y-m-d'),
                                        'ACTUAL_AMOUNT'        => $misc_amount,
                                        'DISCOUNT'             => 0,
                                        'DOWN_PAYMENT'         => 0,
                                        'BALANCE_PAYABLE'      => $misc_amount,
                                        'TOTAL_AMOUNT'         => $misc_amount,
                                        'PAYMENT_METHOD'       => 'One Time',
                                        'PAYMENT_TERM'         => '',
                                        'NUMBER_OF_PAYMENT'    => 0,
                                        'FIRST_DUE_DATE'       => date('Y-m-d'),
                                        'INSTALLMENT_AMOUNT'   => 0,
                                    ];
                                    db_perform_account('DOA_ENROLLMENT_BILLING', $BILLING_DATA, 'insert');
                                    $PK_ENROLLMENT_BILLING = $db_account->insert_ID();

                                    // ---- SERVICE ROW (the missing piece) ----
                                    // Find a "Miscellaneous" service. Falls back to the first service with PK_SERVICE_CLASS = 5
                                    // (miscellaneous class) if a name-based match isn't found.
                                    $misc_service = $db_account->Execute("SELECT DOA_SERVICE_MASTER.PK_SERVICE_MASTER, 
                                                     DOA_SERVICE_MASTER.DESCRIPTION, 
                                                     DOA_SERVICE_CODE.PK_SERVICE_CODE, 
                                                     DOA_SERVICE_CODE.SERVICE_CODE
                                              FROM DOA_SERVICE_MASTER 
                                              LEFT JOIN DOA_SERVICE_CODE ON DOA_SERVICE_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_CODE.PK_SERVICE_MASTER 
                                              WHERE DOA_SERVICE_MASTER.PK_SERVICE_CLASS = 5 
                                              LIMIT 1");

                                    if (!$misc_service || $misc_service->RecordCount() == 0) {
                                        // fallback: try a name match
                                        $misc_service = $db_account->Execute("SELECT DOA_SERVICE_MASTER.PK_SERVICE_MASTER, 
                                                         DOA_SERVICE_MASTER.DESCRIPTION, 
                                                         DOA_SERVICE_CODE.PK_SERVICE_CODE, 
                                                         DOA_SERVICE_CODE.SERVICE_CODE
                                                  FROM DOA_SERVICE_MASTER 
                                                  LEFT JOIN DOA_SERVICE_CODE ON DOA_SERVICE_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_CODE.PK_SERVICE_MASTER 
                                                  WHERE SERVICE_NAME LIKE '%Misc%' 
                                                  LIMIT 1");
                                    }

                                    if ($misc_service && $misc_service->RecordCount() > 0) {
                                        $SERVICE_DATA = [
                                            'PK_ENROLLMENT_MASTER' => $PK_ENROLLMENT_MASTER,
                                            'PK_SERVICE_MASTER'    => $misc_service->fields['PK_SERVICE_MASTER'],
                                            'PK_SERVICE_CODE'      => $misc_service->fields['PK_SERVICE_CODE'],
                                            'SERVICE_DETAILS'      => $misc_service->fields['DESCRIPTION'],
                                            'NUMBER_OF_SESSION'    => 1,
                                            'PRICE_PER_SESSION'    => $misc_amount,
                                            'TOTAL'                => $misc_amount,
                                            'TOTAL_AMOUNT_PAID'    => 0,
                                            'DISCOUNT'             => 0,
                                            'FINAL_AMOUNT'         => $misc_amount,
                                            'STATUS'               => 'A',
                                        ];
                                        db_perform_account('DOA_ENROLLMENT_SERVICE', $SERVICE_DATA, 'insert');
                                    }

                                    // ---- LEDGER (active / unpaid) ----
                                    $BILLING_LEDGER_DATA = [
                                        'PK_ENROLLMENT_MASTER'     => $PK_ENROLLMENT_MASTER,
                                        'PK_ENROLLMENT_BILLING '   => $PK_ENROLLMENT_BILLING,
                                        'TRANSACTION_TYPE'         => 'Billing',
                                        'ENROLLMENT_LEDGER_PARENT' => 0,
                                        'DUE_DATE'                 => date('Y-m-d'),
                                        'BILLED_AMOUNT'            => $misc_amount,
                                        'PAID_AMOUNT'              => 0,
                                        'BALANCE'                  => $misc_amount,
                                        'IS_PAID'                  => 0,
                                        'STATUS'                   => 'A',
                                        'IS_DOWN_PAYMENT'          => 0,
                                    ];
                                    db_perform_account('DOA_ENROLLMENT_LEDGER', $BILLING_LEDGER_DATA, 'insert');

                                    if ($row_created_enrollment) {
                                        $misc_rows[] = "Line $lineNumber — misc enrollment for " . htmlspecialchars($getData[2]) . " ($" . number_format($misc_amount, 2) . " from account balance)";
                                    } else {
                                        $balance_only_rows[] = "Line $lineNumber — balance-only enrollment for " . htmlspecialchars($getData[2]) . " ($" . number_format($misc_amount, 2) . " owed)";
                                    }
                                }
                            }

                            $lineNumber++;
                        }
                        fclose($csvFile);

                        $msg_parts = ["CSV uploaded and processed successfully."];
                        if (!empty($misc_rows)) {
                            $msg_parts[] = "<br><br><b>Miscellaneous enrollments created:</b><br>" . implode('<br>', $misc_rows);
                        }
                        if (!empty($balance_only_rows)) {
                            $msg_parts[] = "<br><br><b>Balance-only enrollments created:</b><br>" . implode('<br>', $balance_only_rows);
                        }
                        if (!empty($duplicate_rows)) {
                            $msg_parts[] = "<br><br><b>Skipped (already imported today):</b><br>" . implode('<br>', $duplicate_rows);
                        }
                        if (!empty($skipped_rows)) {
                            $msg_parts[] = "<br><br><b>Skipped rows:</b><br>" . implode('<br>', $skipped_rows);
                        }

                        $upload_success = implode('', $msg_parts);
                    }
                }
            }
        }
    }
}

$header_text = '';
$header_data = $db->Execute("SELECT * FROM `DOA_HEADER_TEXT` WHERE ACTIVE = 1 AND HEADER_TITLE = 'Data Uploader page'");
if ($header_data && $header_data->RecordCount() > 0) {
    $header_text = $header_data->fields['HEADER_TEXT'];
}
?>

<!DOCTYPE html>
<html lang="en">
<?php include 'layout/header_script.php'; ?>
<?php include 'layout/header.php'; ?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> - Setup Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="assets/css/setup-styles.css" rel="stylesheet">
    <style>
        .main-card form label.form-label {
            font-weight: 500;
            font-size: 0.85rem;
            color: #374151;
            margin-bottom: 6px;
        }

        .main-card form .form-control {
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            padding: 9px 14px;
            font-size: 0.9rem;
        }

        .main-card form .form-control:focus {
            border-color: #39b54a;
            box-shadow: 0 0 0 3px rgba(57, 181, 74, 0.15);
        }

        .main-card form a {
            font-size: 0.8rem;
            color: #39b54a;
            text-decoration: none;
            display: inline-block;
            margin-top: 6px;
        }

        .main-card form a:hover {
            text-decoration: underline;
        }

        .btn-uploader-submit {
            background: #39b54a;
            color: #fff;
            border: none;
            border-radius: 999px;
            padding: 9px 26px;
            font-size: 0.9rem;
            font-weight: 600;
            height: 42px;
            display: inline-flex;
            align-items: center;
        }

        .btn-uploader-submit:hover {
            background: #2f9a3f;
            color: #fff;
        }

        .btn-uploader-submit:disabled {
            opacity: 0.65;
            cursor: not-allowed;
        }

        .uploader-info-note {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 0.85rem;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <div class="container-fluid py-4 px-4 m-auto mx-auto dashboard-container">
        <div class="row g-4">
            <div class="col-12 col-md-4 col-xl-2">
                <?php include 'layout/setup_sidebar.php'; ?>
            </div>

            <div class="col-12 col-md-8 col-xl-10">
                <div class="main-card">
                    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
                        <div>
                            <h2 class="fw-semibold h4 mb-1">
                                <i class="bi bi-database-up me-2" style="color: #39b54a;"></i><?= htmlspecialchars($title) ?>
                            </h2>
                            <p class="text-muted small mb-0">Upload customer and enrollment data via CSV</p>
                            <?php if (!empty($header_text)): ?>
                                <div class="mt-2 alert alert-light py-2 px-3 small bg-light rounded-3">
                                    <i class="bi bi-info-circle-fill me-1 text-info"></i>
                                    <?= htmlspecialchars($header_text) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="uploader-info-note">
                        <i class="bi bi-info-circle me-1"></i>
                        Upload a CSV file to import customers and enrollments. Make sure the file follows the demo sheet format.
                    </div>

                    <?php if (!empty($upload_success)): ?>
                        <div class="alert alert-success" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <?= $upload_success ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($upload_error)): ?>
                        <div class="alert alert-danger d-flex align-items-center" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <?= htmlspecialchars($upload_error) ?>
                        </div>
                    <?php endif; ?>

                    <form action="" method="post" enctype="multipart/form-data" id="uploaderForm">
                        <input type="hidden" name="upload_token" value="<?= htmlspecialchars($upload_token) ?>">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Select Location</label>
                                    <select class="form-control" name="PK_LOCATION" id="PK_LOCATION" required>
                                        <option value="">Select Location</option>
                                        <?php
                                        $row = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME FROM DOA_LOCATION WHERE PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND ACTIVE = 1 AND PK_ACCOUNT_MASTER = " . $_SESSION['PK_ACCOUNT_MASTER']);
                                        while (!$row->EOF) { ?>
                                            <option value="<?php echo $row->fields['PK_LOCATION']; ?>"><?= $row->fields['LOCATION_NAME'] ?></option>
                                        <?php $row->MoveNext();
                                        } ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Select CSV</label>
                                    <input type="file" class="form-control" name="file" accept=".csv" required>
                                    <a href="../uploads/Demo Sheet.csv" target="_blank">
                                        <i class="bi bi-download me-1"></i>Download Demo Sheet
                                    </a>
                                </div>
                            </div>

                            <div class="col-md-4 d-flex align-items-end">
                                <div class="form-group w-100" style="margin-bottom: 27px;">
                                    <button type="submit" class="btn-uploader-submit">
                                        <i class="bi bi-upload me-1"></i> Submit
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <script>
        function viewCsvDownload(param) {
            let table_name = $(param).val();
            $('#view_download_div').html(`<a href="../uploads/csv_upload/${table_name}.csv" target="_blank">View Sample</a>`);
        }

        document.getElementById('uploaderForm').addEventListener('submit', function(e) {
            const btn = this.querySelector('button[type="submit"]');
            if (btn.disabled) {
                e.preventDefault();
                return;
            }
            btn.disabled = true;
            btn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Uploading…';
        });
    </script>
</body>

</html>