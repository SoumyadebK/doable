<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
set_time_limit(0);
ini_set('max_execution_time', 0);
require_once('../global/config.php');
$title = "Data Uploader";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

$PK_ACCOUNT_MASTER = $_SESSION['PK_ACCOUNT_MASTER'];

// Valid upload types
$UPLOAD_TYPES = [
    'customer_enrollments' => 'Customer & Enrollments',
    'appointments'         => 'Appointments',
];

// Multi-token store
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

    // Token guard
    $submitted_token = $_POST['upload_token'] ?? '';
    $token_ok = $submitted_token !== ''
        && isset($_SESSION['UPLOAD_TOKENS'][$submitted_token])
        && $_SESSION['UPLOAD_TOKENS'][$submitted_token]['used'] === false;

    // Validate upload type
    $UPLOAD_TYPE = $_POST['UPLOAD_TYPE'] ?? '';
    if (!array_key_exists($UPLOAD_TYPE, $UPLOAD_TYPES)) {
        $upload_error = "Please select a valid upload type.";
    } elseif (!$token_ok) {
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

            // ============================================================
            // Route to the correct handler based on UPLOAD_TYPE
            // ============================================================
            if ($UPLOAD_TYPE === 'customer_enrollments') {
                handleCustomerEnrollmentsUpload();
            } elseif ($UPLOAD_TYPE === 'appointments') {
                handleAppointmentsUpload();
            }
        }
    }
}

/**
 * ----------------------------------------------------------------
 * HANDLER: Customer & Enrollments
 * (Existing logic, unchanged)
 * ----------------------------------------------------------------
 */
function handleCustomerEnrollmentsUpload()
{
    global $db, $db_account, $PK_ACCOUNT_MASTER;
    global $upload_error, $upload_success;

    $account_data = $db->Execute("SELECT DB_NAME, ENROLLMENT_ID_CHAR, ENROLLMENT_ID_NUM FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER = " . $PK_ACCOUNT_MASTER);
    if (!$account_data || $account_data->RecordCount() == 0) {
        $upload_error = "Account configuration not found.";
        return;
    }

    $DB_NAME = $account_data->fields['DB_NAME'];

    if ($account_data->fields['ENROLLMENT_ID_CHAR'] != null) {
        $enrollment_char = $account_data->fields['ENROLLMENT_ID_CHAR'];
    } else {
        $enrollment_char = 'ENR';
    }

    if (empty($DB_NAME)) {
        $upload_error = "Account database name missing.";
        return;
    }

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
        return;
    }

    $csvFile = fopen($_FILES['file']['tmp_name'], 'r');
    if (!$csvFile) {
        $upload_error = "Could not open uploaded file.";
        return;
    }

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
    $created_rows      = [];
    $rows_created      = 0;

    // =====================================================
    // Service code → location lookup helper
    // =====================================================
    $service_location_strategy = null;
    $probe = $db_account->Execute("SHOW TABLES LIKE 'DOA_SERVICE_LOCATION'");
    if ($probe && $probe->RecordCount() > 0) {
        $service_location_strategy = 'junction';
    } else {
        $probe = $db_account->Execute("SHOW COLUMNS FROM DOA_SERVICE_MASTER LIKE 'PK_LOCATION'");
        if ($probe && $probe->RecordCount() > 0) {
            $service_location_strategy = 'direct';
        } else {
            $service_location_strategy = 'none';
        }
    }

    $findServiceInLocation = function ($db_account, $PK_LOCATION, $service_code_like, $strategy) {
        $code_safe = addslashes($service_code_like);
        $loc_safe  = intval($PK_LOCATION);

        if ($strategy === 'junction') {
            $sql = "SELECT SM.PK_SERVICE_MASTER, SM.SERVICE_NAME,
                           SC.PK_SERVICE_CODE, SC.SERVICE_CODE, SC.IS_GROUP
                    FROM DOA_SERVICE_MASTER SM
                    INNER JOIN DOA_SERVICE_CODE SC ON SC.PK_SERVICE_MASTER = SM.PK_SERVICE_MASTER
                    INNER JOIN DOA_SERVICE_LOCATION SL ON SL.PK_SERVICE_MASTER = SM.PK_SERVICE_MASTER
                    WHERE SL.PK_LOCATION = " . $loc_safe . "
                      AND SC.SERVICE_CODE LIKE '" . $code_safe . "%'
                      AND SM.ACTIVE = 1
                      AND SM.IS_DELETED = 0
                    LIMIT 1";
        } elseif ($strategy === 'direct') {
            $sql = "SELECT SM.PK_SERVICE_MASTER, SM.SERVICE_NAME,
                           SC.PK_SERVICE_CODE, SC.SERVICE_CODE, SC.IS_GROUP
                    FROM DOA_SERVICE_MASTER SM
                    INNER JOIN DOA_SERVICE_CODE SC ON SC.PK_SERVICE_MASTER = SM.PK_SERVICE_MASTER
                    WHERE SM.PK_LOCATION = " . $loc_safe . "
                      AND SC.SERVICE_CODE LIKE '" . $code_safe . "%'
                      AND SM.ACTIVE = 1
                      AND SM.IS_DELETED = 0
                    LIMIT 1";
        } else {
            return $db_account->Execute("SELECT 1 FROM DOA_SERVICE_MASTER WHERE 1=0");
        }
        return $db_account->Execute($sql);
    };

    // =====================================================
    // Scheduling code helper (link table DOA_SCHEDULING_SERVICE)
    // =====================================================
    $ensureSchedulingCode = function ($db_account, $PK_LOCATION, $PK_SERVICE_MASTER, $PK_SERVICE_CODE, $code, $duration, &$created_rows, $ctx) {
        $existing = $db_account->Execute("SELECT PK_SCHEDULING_CODE, PK_LOCATION 
                                          FROM DOA_SCHEDULING_CODE 
                                          WHERE SCHEDULING_CODE = '" . addslashes($code) . "' 
                                            AND (PK_LOCATION = " . intval($PK_LOCATION) . " OR PK_LOCATION IS NULL)
                                          ORDER BY PK_LOCATION DESC 
                                          LIMIT 1");

        if ($existing && $existing->RecordCount() > 0) {
            $PK_SCHED = $existing->fields['PK_SCHEDULING_CODE'];
            $status = 'reused';
        } else {
            $SCH = [
                'PK_LOCATION'       => $PK_LOCATION,
                'PK_ACCOUNT_MASTER' => $_SESSION['PK_ACCOUNT_MASTER'],
                'SCHEDULING_CODE'   => $code,
                'SCHEDULING_NAME'   => $code,
                'DURATION'          => $duration,
                'UNIT'              => 1,
                'SORT_ORDER'        => 0,
                'ACTIVE'            => 1,
                'CREATED_ON'        => date('Y-m-d H:i'),
                'CREATED_BY'        => $_SESSION['PK_USER'],
            ];
            db_perform_account('DOA_SCHEDULING_CODE', $SCH, 'insert');
            $PK_SCHED = $db_account->insert_ID();
            $status = 'created';
        }

        if (!$PK_SCHED) return null;

        $link = $db_account->Execute("SELECT PK_SCHEDULING_SERVICE 
                                      FROM DOA_SCHEDULING_SERVICE 
                                      WHERE PK_SERVICE_CODE = " . intval($PK_SERVICE_CODE) . " 
                                        AND PK_SCHEDULING_CODE = " . intval($PK_SCHED) . " 
                                      LIMIT 1");

        $link_status = 'already linked';
        if (!$link || $link->RecordCount() == 0) {
            $LINK = [
                'PK_SCHEDULING_CODE' => $PK_SCHED,
                'PK_SERVICE_MASTER'  => $PK_SERVICE_MASTER,
                'PK_SERVICE_CODE'    => $PK_SERVICE_CODE,
            ];
            db_perform_account('DOA_SCHEDULING_SERVICE', $LINK, 'insert');
            $link_status = 'linked';
        }

        $created_rows[] = "• {$ctx['label']} ({$ctx['code']}) → scheduling code {$code} ({$duration} min): {$status}, {$link_status}";
        return $PK_SCHED;
    };

    // =====================================================
    // Service create-or-reuse helper
    // =====================================================
    $ensureServiceInLocation = function ($db_account, $PK_LOCATION, $code, $label, $is_group, $strategy, &$created_rows) use ($ensureSchedulingCode) {
        $existing = null;
        $code_safe = addslashes($code);

        if ($strategy === 'junction') {
            $existing = $db_account->Execute("SELECT SM.PK_SERVICE_MASTER, SC.PK_SERVICE_CODE
                                              FROM DOA_SERVICE_MASTER SM
                                              INNER JOIN DOA_SERVICE_CODE SC ON SC.PK_SERVICE_MASTER = SM.PK_SERVICE_MASTER
                                              INNER JOIN DOA_SERVICE_LOCATION SL ON SL.PK_SERVICE_MASTER = SM.PK_SERVICE_MASTER
                                              WHERE SL.PK_LOCATION = " . intval($PK_LOCATION) . "
                                                AND SC.SERVICE_CODE LIKE '" . $code_safe . "%'
                                                AND SM.ACTIVE = 1 AND SM.IS_DELETED = 0
                                              LIMIT 1");
        } elseif ($strategy === 'direct') {
            $existing = $db_account->Execute("SELECT SM.PK_SERVICE_MASTER, SC.PK_SERVICE_CODE
                                              FROM DOA_SERVICE_MASTER SM
                                              INNER JOIN DOA_SERVICE_CODE SC ON SC.PK_SERVICE_MASTER = SM.PK_SERVICE_MASTER
                                              WHERE SM.PK_LOCATION = " . intval($PK_LOCATION) . "
                                                AND SC.SERVICE_CODE LIKE '" . $code_safe . "%'
                                                AND SM.ACTIVE = 1 AND SM.IS_DELETED = 0
                                              LIMIT 1");
        } else {
            return false;
        }

        if ($existing && $existing->RecordCount() > 0) {
            $PK_SERVICE_MASTER = $existing->fields['PK_SERVICE_MASTER'];
            $PK_SERVICE_CODE   = $existing->fields['PK_SERVICE_CODE'];
            $created_rows[] = "• {$label} ({$code}) already exists at location {$PK_LOCATION} — checked scheduling codes";
        } else {
            $SERVICE_MASTER = [
                'PK_LOCATION'      => $PK_LOCATION,
                'SERVICE_NAME'     => $label . ' || ' . $code . '1',
                'PK_SERVICE_CLASS' => 1,
                'IS_SCHEDULE'      => 1,
                'IS_SUNDRY'        => 0,
                'DESCRIPTION'      => $label . ' imported from uploader',
                'ACTIVE'           => 1,
                'IS_DELETED'       => 0,
                'CREATED_BY'       => $_SESSION['PK_USER'],
                'CREATED_ON'       => date('Y-m-d H:i'),
            ];
            db_perform_account('DOA_SERVICE_MASTER', $SERVICE_MASTER, 'insert');
            $PK_SERVICE_MASTER = $db_account->insert_ID();
            if (!$PK_SERVICE_MASTER) return false;

            $SERVICE_CODE = [
                'PK_SERVICE_MASTER' => $PK_SERVICE_MASTER,
                'SERVICE_CODE'      => $code . '1',
                'DESCRIPTION'       => $label,
                'IS_GROUP'          => $is_group ? 1 : 0,
                'IS_SUNDRY'         => 0,
                'CAPACITY'          => 0,
                'IS_CHARGEABLE'     => 1,
                'PRICE'             => 0,
                'COUNT_ON_CALENDAR' => 0,
                'SORT_ORDER'        => 100,
            ];
            db_perform_account('DOA_SERVICE_CODE', $SERVICE_CODE, 'insert');
            $PK_SERVICE_CODE = $db_account->insert_ID();

            if ($strategy === 'junction') {
                $LINK = [
                    'PK_SERVICE_MASTER' => $PK_SERVICE_MASTER,
                    'PK_LOCATION'       => $PK_LOCATION,
                ];
                db_perform_account('DOA_SERVICE_LOCATION', $LINK, 'insert');
            }

            $created_rows[] = "• {$label} ({$code}) created at location {$PK_LOCATION}";
        }

        $ctx = ['code' => $code, 'label' => $label];
        $ensureSchedulingCode($db_account, $PK_LOCATION, $PK_SERVICE_MASTER, $PK_SERVICE_CODE, $code . '30', 30, $created_rows, $ctx);
        $ensureSchedulingCode($db_account, $PK_LOCATION, $PK_SERVICE_MASTER, $PK_SERVICE_CODE, $code . '45', 45, $created_rows, $ctx);

        return true;
    };

    // Ensure PRI / GRP / Pty / CCH / MISC exist for the target location
    $required = [
        ['code' => 'PRI',  'label' => 'Private',       'is_group' => 0],
        ['code' => 'GRP',  'label' => 'Group',         'is_group' => 1],
        ['code' => 'Pty',  'label' => 'Party',         'is_group' => 1],
        ['code' => 'CCH',  'label' => 'Coach',         'is_group' => 0],
        ['code' => 'MISC', 'label' => 'Miscellaneous', 'is_group' => 0],
    ];
    foreach ($required as $r) {
        $ensureServiceInLocation($db_account, $PK_LOCATION, $r['code'], $r['label'], $r['is_group'], $service_location_strategy, $created_rows);
    }

    // Default scheduling code for completed appointments
    $sched_code_lookup = $db_account->Execute("SELECT PK_SCHEDULING_CODE FROM DOA_SCHEDULING_CODE 
                                               WHERE UNIT = 1 
                                                 AND (PK_LOCATION = " . intval($PK_LOCATION) . " OR PK_LOCATION IS NULL)
                                               ORDER BY PK_LOCATION DESC 
                                               LIMIT 1");
    if (!$sched_code_lookup || $sched_code_lookup->RecordCount() == 0) {
        $sched_code_lookup = $db_account->Execute("SELECT PK_SCHEDULING_CODE FROM DOA_SCHEDULING_CODE LIMIT 1");
    }
    $PK_SCHEDULING_CODE_DEFAULT = ($sched_code_lookup && $sched_code_lookup->RecordCount() > 0)
        ? $sched_code_lookup->fields['PK_SCHEDULING_CODE'] : 0;

    // ==========================================================
    // CSV LOOP
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

        $PK_USER             = 0;
        $PK_USER_MASTER      = 0;
        $USER_ID             = 0;
        $PK_CUSTOMER_DETAILS = 0;

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

        $amount_paid_active_raw = isset($getData[18]) ? trim($getData[18]) : '';
        $amount_paid_active = ($amount_paid_active_raw === '') ? null : (float)$amount_paid_active_raw;

        $partner_first = isset($getData[16]) ? trim($getData[16]) : '';
        $partner_last  = isset($getData[17]) ? trim($getData[17]) : '';

        // CUSTOMER
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

        $row_created_enrollment = false;

        // COMPLETED ENROLLMENT
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
                    ['col' => $priv_completed,  'code' => 'PRI',  'price' => $PRICE_PER_SESSION, 'total' => $TOTAL_COST, 'paid' => $TOTAL_COST],
                    ['col' => $grp_completed,   'code' => 'GRP',  'price' => 0,                  'total' => 0,          'paid' => 0],
                    ['col' => $party_completed, 'code' => 'Pty',  'price' => 0,                  'total' => 0,          'paid' => 0],
                    ['col' => $coach_completed, 'code' => 'CCH',  'price' => 0,                  'total' => 0,          'paid' => 0],
                ];
                foreach ($services as $svc) {
                    if ($svc['col'] <= 0) continue;
                    $service_details = $findServiceInLocation($db_account, $PK_LOCATION, $svc['code'], $service_location_strategy);
                    if (!$service_details || $service_details->RecordCount() == 0) {
                        $skipped_rows[] = "Line $lineNumber — no service with code '{$svc['code']}' in location $PK_LOCATION";
                        continue;
                    }

                    $SERVICE_DATA = [
                        'PK_ENROLLMENT_MASTER' => $PK_ENROLLMENT_MASTER,
                        'PK_SERVICE_MASTER'    => $service_details->fields['PK_SERVICE_MASTER'],
                        'PK_SERVICE_CODE'      => $service_details->fields['PK_SERVICE_CODE'],
                        'SERVICE_DETAILS'      => $service_details->fields['SERVICE_NAME'],
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

                // Completed appointments for Used count
                if ($PK_SCHEDULING_CODE_DEFAULT > 0) {
                    $used_buckets = [
                        ['count' => $priv_completed,  'code' => 'PRI'],
                        ['count' => $grp_completed,   'code' => 'GRP'],
                        ['count' => $party_completed, 'code' => 'Pty'],
                        ['count' => $coach_completed, 'code' => 'CCH'],
                    ];

                    foreach ($used_buckets as $bucket) {
                        if ($bucket['count'] <= 0) continue;

                        $svc_info = $findServiceInLocation($db_account, $PK_LOCATION, $bucket['code'], $service_location_strategy);
                        if (!$svc_info || $svc_info->RecordCount() == 0) continue;

                        $PK_SVC_MASTER = $svc_info->fields['PK_SERVICE_MASTER'];
                        $PK_SVC_CODE   = $svc_info->fields['PK_SERVICE_CODE'];
                        $IS_GROUP_SVC  = (int)$svc_info->fields['IS_GROUP'];

                        $enr_svc_row = $db_account->Execute("SELECT PK_ENROLLMENT_SERVICE 
                                                            FROM DOA_ENROLLMENT_SERVICE 
                                                            WHERE PK_ENROLLMENT_MASTER = " . $PK_ENROLLMENT_MASTER . " 
                                                            AND PK_SERVICE_MASTER = " . $PK_SVC_MASTER . " 
                                                            LIMIT 1");
                        if (!$enr_svc_row || $enr_svc_row->RecordCount() == 0) continue;
                        $PK_ENROLLMENT_SERVICE = $enr_svc_row->fields['PK_ENROLLMENT_SERVICE'];

                        for ($u = 0; $u < (int)$bucket['count']; $u++) {
                            $APPT = [
                                'PK_ENROLLMENT_MASTER'  => $PK_ENROLLMENT_MASTER,
                                'PK_ENROLLMENT_SERVICE' => $PK_ENROLLMENT_SERVICE,
                                'PK_SERVICE_MASTER'     => $PK_SVC_MASTER,
                                'PK_SERVICE_CODE'       => $PK_SVC_CODE,
                                'PK_SCHEDULING_CODE'    => $PK_SCHEDULING_CODE_DEFAULT,
                                'PK_LOCATION'           => $PK_LOCATION,
                                'PK_APPOINTMENT_STATUS' => 2,
                                'APPOINTMENT_TYPE'      => ($IS_GROUP_SVC == 1) ? 'GROUP' : 'NORMAL',
                                'DATE'                  => date('Y-m-d', strtotime('-' . ($u + 1) . ' days')),
                                'START_TIME'            => '09:00:00',
                                'END_TIME'              => '10:00:00',
                                'ACTIVE'                => 1,
                                'STATUS'                => 'A',
                                'IS_CHARGED'            => 1,
                                'IS_PAID'               => 1,
                                'SERIAL_NUMBER'         => 0,
                                'STANDING_ID'           => 0,
                                'CREATED_BY'            => $_SESSION['PK_USER'],
                                'CREATED_ON'            => date('Y-m-d H:i'),
                            ];
                            db_perform_account('DOA_APPOINTMENT_MASTER', $APPT, 'insert');
                            $PK_APPT = $db_account->insert_ID();

                            if ($PK_APPT) {
                                $APPT_CUST = [
                                    'PK_APPOINTMENT_MASTER' => $PK_APPT,
                                    'PK_USER_MASTER'        => $PK_USER_MASTER,
                                    'IS_PARTNER'            => 0,
                                ];
                                db_perform_account('DOA_APPOINTMENT_CUSTOMER', $APPT_CUST, 'insert');

                                if ($IS_GROUP_SVC == 1) {
                                    $APPT_ENR = [
                                        'PK_APPOINTMENT_MASTER' => $PK_APPT,
                                        'PK_USER_MASTER'        => $PK_USER_MASTER,
                                        'PK_ENROLLMENT_MASTER'  => $PK_ENROLLMENT_MASTER,
                                        'PK_ENROLLMENT_SERVICE' => $PK_ENROLLMENT_SERVICE,
                                        'IS_CHARGED'            => 1,
                                    ];
                                    db_perform_account('DOA_APPOINTMENT_ENROLLMENT', $APPT_ENR, 'insert');
                                }
                            }
                        }
                    }
                }

                $row_created_enrollment = true;
                $rows_created++;
            }
        }

        // ACTIVE ENROLLMENT
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
                    ['col' => $priv_active,  'code' => 'PRI',  'price' => $PRICE_PER_SESSION, 'total' => $TOTAL_COST, 'paid' => $TOTAL_PAID],
                    ['col' => $grp_active,   'code' => 'GRP',  'price' => 0,                  'total' => 0,          'paid' => 0],
                    ['col' => $party_active, 'code' => 'Pty',  'price' => 0,                  'total' => 0,          'paid' => 0],
                    ['col' => $coach_active, 'code' => 'CCH',  'price' => 0,                  'total' => 0,          'paid' => 0],
                ];
                foreach ($services as $svc) {
                    if ($svc['col'] <= 0) continue;
                    $service_details = $findServiceInLocation($db_account, $PK_LOCATION, $svc['code'], $service_location_strategy);
                    if (!$service_details || $service_details->RecordCount() == 0) {
                        $skipped_rows[] = "Line $lineNumber — no service with code '{$svc['code']}' in location $PK_LOCATION";
                        continue;
                    }

                    $SERVICE_DATA = [
                        'PK_ENROLLMENT_MASTER' => $PK_ENROLLMENT_MASTER,
                        'PK_SERVICE_MASTER'    => $service_details->fields['PK_SERVICE_MASTER'],
                        'PK_SERVICE_CODE'      => $service_details->fields['PK_SERVICE_CODE'],
                        'SERVICE_DETAILS'      => $service_details->fields['SERVICE_NAME'],
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
                $rows_created++;
            }
        }

        // MISC ENROLLMENT
        $should_create_misc = false;
        $misc_amount = 0;

        if ($row_created_enrollment && $account_balance != 0) {
            $should_create_misc = true;
            $misc_amount = abs($account_balance);
        } elseif (!$row_created_enrollment && $account_balance < 0) {
            $should_create_misc = true;
            $misc_amount = abs($account_balance);
        }

        $misc_service = $findServiceInLocation($db_account, $PK_LOCATION, 'MISC', $service_location_strategy);
        if (!$misc_service || $misc_service->RecordCount() == 0) {
            $misc_service = $findServiceInLocation($db_account, $PK_LOCATION, 'MIS', $service_location_strategy);
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

                $enrollment_data = $db_account->Execute("SELECT ENROLLMENT_ID FROM `DOA_ENROLLMENT_MASTER` 
                            WHERE `PK_USER_MASTER` = " . $PK_USER_MASTER . " 
                            ORDER BY PK_ENROLLMENT_MASTER DESC LIMIT 1");
                if ($enrollment_data && $enrollment_data->RecordCount() > 0) {
                    $last_enrollment_id = str_replace($enrollment_char, '', $enrollment_data->fields['ENROLLMENT_ID']);
                    $ENROLLMENT_DATA['ENROLLMENT_ID'] = $enrollment_char . (intval($last_enrollment_id) + 1);
                } else {
                    $ENROLLMENT_DATA['ENROLLMENT_ID'] = $enrollment_char . $account_data->fields['ENROLLMENT_ID_NUM'];
                }

                $cen_data = $db_account->Execute("SELECT CUSTOMER_ENROLLMENT_NUMBER FROM `DOA_ENROLLMENT_MASTER` 
                     WHERE PK_USER_MASTER = " . $PK_USER_MASTER . " 
                     ORDER BY PK_ENROLLMENT_MASTER DESC LIMIT 1");
                $ENROLLMENT_DATA['CUSTOMER_ENROLLMENT_NUMBER'] = ($cen_data && $cen_data->RecordCount() > 0)
                    ? $cen_data->fields['CUSTOMER_ENROLLMENT_NUMBER'] + 1 : 1;

                $ENROLLMENT_DATA['PK_USER_MASTER']       = $PK_USER_MASTER;
                $ENROLLMENT_DATA['PK_LOCATION']          = $PK_LOCATION;
                $ENROLLMENT_DATA['CHARGE_TYPE']          = 'Miscellaneous';
                $ENROLLMENT_DATA['ENROLLMENT_BY_ID']     = $_SESSION['PK_USER'];
                $ENROLLMENT_DATA['ACTIVE']               = 1;
                $ENROLLMENT_DATA['STATUS']               = 'A';
                $ENROLLMENT_DATA['ALL_APPOINTMENT_DONE'] = 0;
                $ENROLLMENT_DATA['COMPLETED_DATE']       = null;
                $ENROLLMENT_DATA['ENROLLMENT_DATE']      = date('Y-m-d');
                $ENROLLMENT_DATA['EXPIRY_DATE']          = date('Y-m-d', strtotime('+1 month'));
                $ENROLLMENT_DATA['CREATED_BY']           = $_SESSION['PK_USER'];
                $ENROLLMENT_DATA['CREATED_ON']           = date('Y-m-d H:i');
                $ENROLLMENT_DATA['PK_ENROLLMENT_TYPE']   = 5;

                db_perform_account('DOA_ENROLLMENT_MASTER', $ENROLLMENT_DATA, 'insert');
                $PK_ENROLLMENT_MASTER = $db_account->insert_ID();

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

                if ($misc_service && $misc_service->RecordCount() > 0) {
                    $SERVICE_DATA = [
                        'PK_ENROLLMENT_MASTER' => $PK_ENROLLMENT_MASTER,
                        'PK_SERVICE_MASTER'    => $misc_service->fields['PK_SERVICE_MASTER'],
                        'PK_SERVICE_CODE'      => $misc_service->fields['PK_SERVICE_CODE'],
                        'SERVICE_DETAILS'      => $misc_service->fields['SERVICE_NAME'],
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
                $rows_created++;
            }
        }

        $lineNumber++;
    }
    fclose($csvFile);

    $msg_parts = ["CSV processed successfully. Enrollments created: " . $rows_created . ". Rows processed: " . ($lineNumber - 1) . "."];

    if (!empty($created_rows)) {
        $msg_parts[] = "<br><br><b>Services / scheduling codes checked for location " . intval($PK_LOCATION) . ":</b><br>" . implode('<br>', $created_rows);
    }
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
        $msg_parts[] = "<br><br><b>Warnings / Skipped:</b><br>" . implode('<br>', $skipped_rows);
    }

    $upload_success = implode('', $msg_parts);
}

/**
 * ----------------------------------------------------------------
 * HANDLER: Appointments
 * (Stub — replace with real logic when the appointments sheet is ready)
 * ----------------------------------------------------------------
 */
function handleAppointmentsUpload()
{
    global $upload_error;
    $upload_error = "The Appointments upload is coming soon. Please use 'Customer & Enrollments' for now.";
}


$header_text = '';
$header_data = $db->Execute("SELECT * FROM `DOA_HEADER_TEXT` WHERE ACTIVE = 1 AND HEADER_TITLE = 'Data Uploader page'");
if ($header_data && $header_data->RecordCount() > 0) {
    $header_text = $header_data->fields['HEADER_TEXT'];
}

$selected_upload_type = $_POST['UPLOAD_TYPE'] ?? 'customer_enrollments';
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

        .main-card form .form-control,
        .main-card form .form-select {
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            padding: 9px 14px;
            font-size: 0.9rem;
        }

        .main-card form .form-control:focus,
        .main-card form .form-select:focus {
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
                        Choose an uploader type, then select a location and CSV file. Missing services and scheduling codes for the selected location will be created automatically.
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

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="form-label">Select Location</label>
                                    <select class="form-select" name="PK_LOCATION" id="PK_LOCATION" required>
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

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="form-label">Select Uploader</label>
                                    <select class="form-select" name="UPLOAD_TYPE" id="UPLOAD_TYPE" required>
                                        <?php foreach ($UPLOAD_TYPES as $key => $label): ?>
                                            <option value="<?= htmlspecialchars($key) ?>" <?= ($selected_upload_type === $key) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($label) ?>
                                            </option>
                                        <?php endforeach; ?>
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

                            <div class="col-md-2 d-flex align-items-end">
                                <div class="form-group w-100" style="margin-bottom: 27px;">
                                    <button type="submit" class="btn-uploader-submit w-100 justify-content-center">
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