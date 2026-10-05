<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
require_once('../global/config.php');
$title = "Data Uploader";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

$PK_ACCOUNT_MASTER = $_SESSION['PK_ACCOUNT_MASTER'];

if (!empty($_POST)) {
    // Allowed mime types
    $fileMimes = array(
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
    );

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
                    // Open uploaded CSV
                    $csvFile = fopen($_FILES['file']['tmp_name'], 'r');

                    // Strip UTF-8 BOM (Excel adds it invisibly)
                    $bom = fread($csvFile, 3);
                    if ($bom !== "\xEF\xBB\xBF") {
                        rewind($csvFile);
                    }

                    $lineNumber = 1;
                    $PK_LOCATION = $_POST['PK_LOCATION'];

                    // ==========================================================
                    // CSV COLUMN MAP (16 columns — matches the Demo Sheet)
                    // 0 First Name | 1 Last Name | 2 Email | 3 Phone
                    // 4 Privates Used (completed)  | 5 Groups Used (completed)
                    // 6 Parties Used (completed)   | 7 Coaches Used (completed)
                    // 8 Total cost historical (completed)
                    // 9 Privates Remaining (active)| 10 Groups Remaining (active)
                    // 11 Parties Remaining (active)| 12 Coaches Remaining (active)
                    // 13 Total cost remaining (active)
                    // 14 Amount owed on remaining (active)
                    // 15 $ Paid ahead/behind (active)
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
                        $remaining       = (float)($getData[14] ?? 0);
                        $paid            = (float)($getData[15] ?? 0);

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

                                $CUSTOMER_DATA['PK_USER_MASTER'] = $PK_USER_MASTER;
                                $CUSTOMER_DATA['FIRST_NAME']     = trim($getData[0]);
                                $CUSTOMER_DATA['LAST_NAME']      = trim($getData[1]);
                                $CUSTOMER_DATA['EMAIL_ID']       = $getData[2];
                                $CUSTOMER_DATA['PHONE']          = $getData[3];
                                db_perform_account('DOA_CUSTOMER_DETAILS', $CUSTOMER_DATA, 'insert');
                                $PK_CUSTOMER_DETAILS = $db_account->insert_ID();
                            }
                        }

                        // Skip downstream if customer could not be resolved
                        if (empty($PK_USER_MASTER)) {
                            $lineNumber++;
                            continue;
                        }

                        // ==========================================================
                        // COMPLETED ENROLLMENT (cols 4–8)
                        // ==========================================================
                        $TOTAL_LESSONS = $priv_completed + $grp_completed + $party_completed + $coach_completed;
                        if ($TOTAL_LESSONS > 0) {
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

                            // Billing
                            $BILLING_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
                            $BILLING_DATA['BILLING_REF']          = '';
                            $BILLING_DATA['BILLING_DATE']         = date('Y-m-d');
                            $BILLING_DATA['ACTUAL_AMOUNT']        = $TOTAL_COST;
                            $BILLING_DATA['DISCOUNT']             = 0;
                            $BILLING_DATA['DOWN_PAYMENT']         = 0;
                            $BILLING_DATA['BALANCE_PAYABLE']      = 0;
                            $BILLING_DATA['TOTAL_AMOUNT']         = $TOTAL_COST;
                            $BILLING_DATA['PAYMENT_METHOD']       = 'One Time';
                            $BILLING_DATA['PAYMENT_TERM']         = '';
                            $BILLING_DATA['NUMBER_OF_PAYMENT']    = 0;
                            $BILLING_DATA['FIRST_DUE_DATE']       = date('Y-m-d');
                            $BILLING_DATA['INSTALLMENT_AMOUNT']   = 0;
                            db_perform_account('DOA_ENROLLMENT_BILLING', $BILLING_DATA, 'insert');
                            $PK_ENROLLMENT_BILLING = $db_account->insert_ID();

                            // Services
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

                            // Ledger
                            $BILLING_LEDGER_DATA['PK_ENROLLMENT_MASTER']     = $PK_ENROLLMENT_MASTER;
                            $BILLING_LEDGER_DATA['PK_ENROLLMENT_BILLING ']   = $PK_ENROLLMENT_BILLING;
                            $BILLING_LEDGER_DATA['TRANSACTION_TYPE']         = 'Billing';
                            $BILLING_LEDGER_DATA['ENROLLMENT_LEDGER_PARENT'] = 0;
                            $BILLING_LEDGER_DATA['DUE_DATE']                 = date('Y-m-d');
                            $BILLING_LEDGER_DATA['BILLED_AMOUNT']            = $TOTAL_COST;
                            $BILLING_LEDGER_DATA['PAID_AMOUNT']              = $TOTAL_COST;
                            $BILLING_LEDGER_DATA['BALANCE']                  = $TOTAL_COST;
                            $BILLING_LEDGER_DATA['IS_PAID']                  = 1;
                            $BILLING_LEDGER_DATA['STATUS']                   = 'CO';
                            $BILLING_LEDGER_DATA['IS_DOWN_PAYMENT']          = 0;
                            db_perform_account('DOA_ENROLLMENT_LEDGER', $BILLING_LEDGER_DATA, 'insert');
                            $PK_ENROLLMENT_LEDGER = $db_account->insert_ID();

                            // Payment
                            $ENROLLMENT_PAYMENT_DATA['PK_ENROLLMENT_MASTER']  = $PK_ENROLLMENT_MASTER;
                            $ENROLLMENT_PAYMENT_DATA['PK_ENROLLMENT_BILLING'] = $PK_ENROLLMENT_BILLING;
                            $ENROLLMENT_PAYMENT_DATA['PK_PAYMENT_TYPE']       = 12;
                            $ENROLLMENT_PAYMENT_DATA['PK_ENROLLMENT_LEDGER']  = $PK_ENROLLMENT_LEDGER;
                            $ENROLLMENT_PAYMENT_DATA['TYPE']                  = 'Payment';
                            $ENROLLMENT_PAYMENT_DATA['AMOUNT']                = $TOTAL_COST;
                            $ENROLLMENT_PAYMENT_DATA['NOTE']                  = '';
                            $ENROLLMENT_PAYMENT_DATA['PAYMENT_DATE']          = date('Y-m-d');
                            $ENROLLMENT_PAYMENT_DATA['PAYMENT_INFO']          = '';
                            $ENROLLMENT_PAYMENT_DATA['PAYMENT_STATUS']        = 'Success';
                            db_perform_account('DOA_ENROLLMENT_PAYMENT', $ENROLLMENT_PAYMENT_DATA, 'insert');
                        }

                        // ==========================================================
                        // ACTIVE ENROLLMENT (cols 9–15)
                        // ==========================================================
                        $TOTAL_LESSONS = $priv_active + $grp_active + $party_active + $coach_active;
                        if ($TOTAL_LESSONS > 0) {
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
                            $TOTAL_REMAINING   = $remaining;
                            $TOTAL_PAID        = $paid;
                            $PRICE_PER_SESSION = ($priv_active > 0) ? $TOTAL_COST / $priv_active : 0;

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

                            // Billing
                            $BILLING_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
                            $BILLING_DATA['BILLING_REF']          = '';
                            $BILLING_DATA['BILLING_DATE']         = date('Y-m-d');
                            $BILLING_DATA['ACTUAL_AMOUNT']        = $TOTAL_COST;
                            $BILLING_DATA['DISCOUNT']             = 0;
                            $BILLING_DATA['DOWN_PAYMENT']         = 0;
                            $BILLING_DATA['BALANCE_PAYABLE']      = $TOTAL_COST;
                            $BILLING_DATA['TOTAL_AMOUNT']         = $TOTAL_COST;
                            $BILLING_DATA['PAYMENT_METHOD']       = 'One Time';
                            $BILLING_DATA['PAYMENT_TERM']         = '';
                            $BILLING_DATA['NUMBER_OF_PAYMENT']    = 0;
                            $BILLING_DATA['FIRST_DUE_DATE']       = date('Y-m-d');
                            $BILLING_DATA['INSTALLMENT_AMOUNT']   = 0;
                            db_perform_account('DOA_ENROLLMENT_BILLING', $BILLING_DATA, 'insert');
                            $PK_ENROLLMENT_BILLING = $db_account->insert_ID();

                            // Services
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

                            // Ledger (paid)
                            $BILLING_LEDGER_DATA['PK_ENROLLMENT_MASTER']     = $PK_ENROLLMENT_MASTER;
                            $BILLING_LEDGER_DATA['PK_ENROLLMENT_BILLING ']   = $PK_ENROLLMENT_BILLING;
                            $BILLING_LEDGER_DATA['TRANSACTION_TYPE']         = 'Billing';
                            $BILLING_LEDGER_DATA['ENROLLMENT_LEDGER_PARENT'] = 0;
                            $BILLING_LEDGER_DATA['DUE_DATE']                 = date('Y-m-d');
                            $BILLING_LEDGER_DATA['BILLED_AMOUNT']            = $TOTAL_PAID;
                            $BILLING_LEDGER_DATA['PAID_AMOUNT']              = 0;
                            $BILLING_LEDGER_DATA['BALANCE']                  = $TOTAL_PAID;
                            $BILLING_LEDGER_DATA['IS_PAID']                  = 1;
                            $BILLING_LEDGER_DATA['STATUS']                   = 'A';
                            $BILLING_LEDGER_DATA['IS_DOWN_PAYMENT']          = 0;
                            db_perform_account('DOA_ENROLLMENT_LEDGER', $BILLING_LEDGER_DATA, 'insert');
                            $PK_ENROLLMENT_LEDGER = $db_account->insert_ID();

                            // Payment
                            $ENROLLMENT_PAYMENT_DATA['PK_ENROLLMENT_MASTER']  = $PK_ENROLLMENT_MASTER;
                            $ENROLLMENT_PAYMENT_DATA['PK_ENROLLMENT_BILLING'] = $PK_ENROLLMENT_BILLING;
                            $ENROLLMENT_PAYMENT_DATA['PK_PAYMENT_TYPE']       = 12;
                            $ENROLLMENT_PAYMENT_DATA['PK_ENROLLMENT_LEDGER']  = $PK_ENROLLMENT_LEDGER;
                            $ENROLLMENT_PAYMENT_DATA['TYPE']                  = 'Payment';
                            $ENROLLMENT_PAYMENT_DATA['AMOUNT']                = $TOTAL_PAID;
                            $ENROLLMENT_PAYMENT_DATA['NOTE']                  = '';
                            $ENROLLMENT_PAYMENT_DATA['PAYMENT_DATE']          = date('Y-m-d');
                            $ENROLLMENT_PAYMENT_DATA['PAYMENT_INFO']          = '';
                            $ENROLLMENT_PAYMENT_DATA['PAYMENT_STATUS']        = 'Success';
                            db_perform_account('DOA_ENROLLMENT_PAYMENT', $ENROLLMENT_PAYMENT_DATA, 'insert');

                            // Remaining ledger
                            if ($TOTAL_REMAINING > 0) {
                                $BILLING_LEDGER_DATA['PK_ENROLLMENT_MASTER']     = $PK_ENROLLMENT_MASTER;
                                $BILLING_LEDGER_DATA['PK_ENROLLMENT_BILLING ']   = $PK_ENROLLMENT_BILLING;
                                $BILLING_LEDGER_DATA['TRANSACTION_TYPE']         = 'Billing';
                                $BILLING_LEDGER_DATA['ENROLLMENT_LEDGER_PARENT'] = 0;
                                $BILLING_LEDGER_DATA['DUE_DATE']                 = date('Y-m-d');
                                $BILLING_LEDGER_DATA['BILLED_AMOUNT']            = $TOTAL_REMAINING;
                                $BILLING_LEDGER_DATA['PAID_AMOUNT']              = 0;
                                $BILLING_LEDGER_DATA['BALANCE']                  = $TOTAL_REMAINING;
                                $BILLING_LEDGER_DATA['IS_PAID']                  = 0;
                                $BILLING_LEDGER_DATA['STATUS']                   = 'A';
                                $BILLING_LEDGER_DATA['IS_DOWN_PAYMENT']          = 0;
                                db_perform_account('DOA_ENROLLMENT_LEDGER', $BILLING_LEDGER_DATA, 'insert');
                                $PK_ENROLLMENT_LEDGER = $db_account->insert_ID();
                            }
                        }

                        $lineNumber++;
                    }
                    fclose($csvFile);

                    $upload_success = "CSV uploaded and processed successfully.";
                }
            }
        }
    }
}

/* Optional header note */
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
                        <div class="alert alert-success d-flex align-items-center" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <?= htmlspecialchars($upload_success) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($upload_error)): ?>
                        <div class="alert alert-danger d-flex align-items-center" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <?= htmlspecialchars($upload_error) ?>
                        </div>
                    <?php endif; ?>

                    <form action="" method="post" enctype="multipart/form-data">
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
    </script>
</body>

</html>