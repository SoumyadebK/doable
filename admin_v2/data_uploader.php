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

    $submitted_token = $_POST['upload_token'] ?? '';
    $token_ok = $submitted_token !== ''
        && isset($_SESSION['UPLOAD_TOKENS'][$submitted_token])
        && $_SESSION['UPLOAD_TOKENS'][$submitted_token]['used'] === false;

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
            if ($UPLOAD_TYPE === 'customer_enrollments') {
                handleCustomerEnrollmentsUpload();
            } elseif ($UPLOAD_TYPE === 'appointments') {
                handleAppointmentsUpload();
            }
        }
    }
}

/**
 * Shared helper: normalize the date to Y-m-d.
 */
function normalizeImportDate($raw)
{
    $raw = trim($raw);
    if ($raw === '') return '';
    if (preg_match('/^\d{4}-\d{2}-\d{2}/', $raw)) {
        return date('Y-m-d', strtotime($raw));
    }
    if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $raw, $m)) {
        return sprintf('%04d-%02d-%02d', $m[3], $m[1], $m[2]);
    }
    $ts = strtotime($raw);
    return $ts ? date('Y-m-d', $ts) : '';
}

/**
 * ================================================================
 * HANDLER: Customer & Enrollments
 * ================================================================
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
    // Service lookup helper — no junction table.
    // Prefers a row scoped to the target location, then any matching code.
    // =====================================================
    $findServiceInLocation = function ($db_account, $PK_LOCATION, $service_code, $prefix = false) {
        $code_safe = addslashes($service_code);
        $loc_safe  = intval($PK_LOCATION);
        $op        = $prefix ? 'LIKE' : '=';

        return $db_account->Execute("
            SELECT SM.PK_SERVICE_MASTER, SM.SERVICE_NAME,
                   SC.PK_SERVICE_CODE, SC.SERVICE_CODE, SC.IS_GROUP
            FROM DOA_SERVICE_MASTER SM
            INNER JOIN DOA_SERVICE_CODE SC ON SC.PK_SERVICE_MASTER = SM.PK_SERVICE_MASTER
            WHERE SC.SERVICE_CODE $op '$code_safe" . ($prefix ? "%" : "") . "'
              AND SM.ACTIVE = 1 AND SM.IS_DELETED = 0
            ORDER BY (SC.PK_LOCATION = $loc_safe) DESC,
                     (SM.PK_LOCATION = $loc_safe) DESC,
                     SC.PK_SERVICE_CODE DESC
            LIMIT 1
        ");
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
    // Service create-or-reuse helper — no junction table.
    // =====================================================
    $ensureServiceInLocation = function ($db_account, $PK_LOCATION, $code, $label, $is_group, &$created_rows) use ($ensureSchedulingCode) {
        $code_safe = addslashes($code);

        $existing = $db_account->Execute("
            SELECT SM.PK_SERVICE_MASTER, SC.PK_SERVICE_CODE
            FROM DOA_SERVICE_MASTER SM
            INNER JOIN DOA_SERVICE_CODE SC ON SC.PK_SERVICE_MASTER = SM.PK_SERVICE_MASTER
            WHERE SC.SERVICE_CODE = '{$code_safe}'
              AND SM.ACTIVE = 1 AND SM.IS_DELETED = 0
            ORDER BY (SC.PK_LOCATION = " . intval($PK_LOCATION) . ") DESC,
                     (SM.PK_LOCATION = " . intval($PK_LOCATION) . ") DESC,
                     SC.PK_SERVICE_CODE DESC
            LIMIT 1
        ");

        if ($existing && $existing->RecordCount() > 0) {
            $PK_SERVICE_MASTER = $existing->fields['PK_SERVICE_MASTER'];
            $PK_SERVICE_CODE   = $existing->fields['PK_SERVICE_CODE'];
            $created_rows[] = "• {$label} ({$code}) already exists — reusing";
        } else {
            $SERVICE_MASTER = [
                'PK_LOCATION'      => $PK_LOCATION,
                'SERVICE_NAME'     => $label,
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
                'PK_LOCATION'       => $PK_LOCATION,
                'SERVICE_CODE'      => $code,
                'DESCRIPTION'       => $label,
                'IS_GROUP'          => $is_group ? 1 : 0,
                'IS_SUNDRY'         => 0,
                'CAPACITY'          => 0,
                'IS_CHARGEABLE'     => 1,
                'PRICE'             => 0,
                'COUNT_ON_CALENDAR' => 1,
                'SORT_ORDER'        => 100,
            ];
            db_perform_account('DOA_SERVICE_CODE', $SERVICE_CODE, 'insert');
            $PK_SERVICE_CODE = $db_account->insert_ID();

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
        $ensureServiceInLocation($db_account, $PK_LOCATION, $r['code'], $r['label'], $r['is_group'], $created_rows);
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
    //
    // Column layout (0-based) with MINDBODY_CLIENT_ID prepended:
    //   0  MINDBODY_CLIENT_ID
    //   1  First Name
    //   2  Last Name
    //   3  Email
    //   4  Phone No
    //   5  Privates Used
    //   6  Groups Used
    //   7  Parties Used
    //   8  Coaches Used
    //   9  total cost of historical services
    //   10 Privates Remaining
    //   11 Groups Remaining
    //   12 Parties Remaining
    //   13 Coaches Remaining
    //   14 total cost of remaining services
    //   15 amount owed on remaining services
    //   16 $ Amount Paid Ahead or Amount Behind
    //   17 Partner First Name
    //   18 Partner Last Name
    // ==========================================================
    while (($getData = fgetcsv($csvFile, 10000, ",")) !== FALSE) {
        if ($lineNumber === 1) {
            $lineNumber++;
            continue;
        }

        if (count($getData) < 5 || (empty($getData[1]) && empty($getData[3]))) {
            $lineNumber++;
            continue;
        }

        $PK_USER             = 0;
        $PK_USER_MASTER      = 0;
        $USER_ID             = 0;
        $PK_CUSTOMER_DETAILS = 0;

        $MINDBODY_CLIENT_ID = trim($getData[0] ?? '');
        $MINDBODY_CLIENT_ID_INT = ($MINDBODY_CLIENT_ID === '') ? 0 : (int)$MINDBODY_CLIENT_ID;

        $first_name = trim($getData[1] ?? '');
        $last_name  = trim($getData[2] ?? '');
        $email      = trim($getData[3] ?? '');
        $phone      = trim($getData[4] ?? '');

        $priv_completed  = (string)($getData[5]  ?? '0');
        $grp_completed   = (string)($getData[6]  ?? '0');
        $party_completed = (string)($getData[7]  ?? '0');
        $coach_completed = (string)($getData[8]  ?? '0');
        $cost_completed  = (float)($getData[9]  ?? 0);

        $priv_active     = (string)($getData[10] ?? '0');
        $grp_active      = (string)($getData[11] ?? '0');
        $party_active    = (string)($getData[12] ?? '0');
        $coach_active    = (string)($getData[13] ?? '0');
        $cost_active     = (float)($getData[14] ?? 0);
        $amount_owed_remaining = (float)($getData[15] ?? 0);

        $account_balance = (float)($getData[16] ?? 0);

        // No separate "amount paid ahead" column in this layout; default null.
        $amount_paid_active = null;

        $partner_first = isset($getData[17]) ? trim($getData[17]) : '';
        $partner_last  = isset($getData[18]) ? trim($getData[18]) : '';

        // =====================================================
        // CUSTOMER: split lookup (UNIQUE_ID first, then EMAIL) — no OR scan
        // =====================================================
        if ($MINDBODY_CLIENT_ID_INT > 0) {
            $r = $db->Execute("
                SELECT U.PK_USER, UM.PK_USER_MASTER, U.USER_ID, U.UNIQUE_ID
                FROM DOA_USERS U
                INNER JOIN DOA_USER_MASTER UM ON U.PK_USER = UM.PK_USER
                WHERE U.UNIQUE_ID = " . $MINDBODY_CLIENT_ID_INT . "
                  AND UM.PRIMARY_LOCATION_ID = " . intval($PK_LOCATION) . "
                  AND U.PK_ACCOUNT_MASTER = " . intval($PK_ACCOUNT_MASTER) . "
                LIMIT 1
            ");
            if ($r && $r->RecordCount() > 0) {
                $PK_USER        = $r->fields['PK_USER'];
                $PK_USER_MASTER = $r->fields['PK_USER_MASTER'];
                $USER_ID        = $r->fields['USER_ID'];
            }
        }

        if ($PK_USER == 0 && $email !== '') {
            $email_escaped = addslashes($email);
            $r = $db->Execute("
                SELECT U.PK_USER, UM.PK_USER_MASTER, U.USER_ID, U.UNIQUE_ID
                FROM DOA_USERS U
                INNER JOIN DOA_USER_MASTER UM ON U.PK_USER = UM.PK_USER
                WHERE U.EMAIL_ID = '$email_escaped'
                  AND UM.PRIMARY_LOCATION_ID = " . intval($PK_LOCATION) . "
                  AND U.PK_ACCOUNT_MASTER = " . intval($PK_ACCOUNT_MASTER) . "
                ORDER BY U.PK_USER DESC
                LIMIT 1
            ");
            if ($r && $r->RecordCount() > 0) {
                $PK_USER        = $r->fields['PK_USER'];
                $PK_USER_MASTER = $r->fields['PK_USER_MASTER'];
                $USER_ID        = $r->fields['USER_ID'];
                // Back-fill UNIQUE_ID if empty
                if ($MINDBODY_CLIENT_ID_INT > 0 && (empty($r->fields['UNIQUE_ID']) || $r->fields['UNIQUE_ID'] == 0)) {
                    $db->Execute("UPDATE DOA_USERS SET UNIQUE_ID = " . $MINDBODY_CLIENT_ID_INT . " WHERE PK_USER = " . intval($PK_USER));
                }
            }
        }

        // Not found → create new customer
        if ($PK_USER == 0) {
            $USER_DATA['PK_ACCOUNT_MASTER'] = $PK_ACCOUNT_MASTER;
            $USER_DATA['FIRST_NAME']        = $first_name;
            $USER_DATA['LAST_NAME']         = $last_name;
            $USER_DATA['EMAIL_ID']          = $email;
            $USER_DATA['PHONE']             = $phone;
            $USER_DATA['DOB']               = null;
            $USER_DATA['ADDRESS']           = '';
            $USER_DATA['ADDRESS_1']         = null;
            $USER_DATA['CITY']              = '';
            $USER_DATA['ZIP']               = '';
            $USER_DATA['PK_COUNTRY']        = 1;
            $USER_DATA['PK_STATES']         = 0;
            $USER_DATA['IS_DELETED']        = 0;
            $USER_DATA['ACTIVE']            = 1;
            if ($MINDBODY_CLIENT_ID_INT > 0) {
                $USER_DATA['UNIQUE_ID'] = $MINDBODY_CLIENT_ID_INT;
            }
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
                $USER_DATA_ACCOUNT['FIRST_NAME']        = $first_name;
                $USER_DATA_ACCOUNT['LAST_NAME']         = $last_name;
                $USER_DATA_ACCOUNT['EMAIL_ID']          = $email;
                $USER_DATA_ACCOUNT['PHONE']             = $phone;
                $USER_DATA_ACCOUNT['CREATED_BY']        = $_SESSION['PK_USER'];
                $USER_DATA_ACCOUNT['CREATED_ON']        = date("Y-m-d H:i");
                db_perform_account('DOA_USERS', $USER_DATA_ACCOUNT, 'insert');

                $CUSTOMER_DATA['PK_USER_MASTER']     = $PK_USER_MASTER;
                $CUSTOMER_DATA['FIRST_NAME']         = $first_name;
                $CUSTOMER_DATA['LAST_NAME']          = $last_name;
                $CUSTOMER_DATA['EMAIL']              = $email;
                $CUSTOMER_DATA['PHONE']              = $phone;
                $CUSTOMER_DATA['ATTENDING_WITH']     = (!empty($partner_first) || !empty($partner_last)) ? 'With a Partner' : 'Solo';
                $CUSTOMER_DATA['PARTNER_FIRST_NAME'] = $partner_first;
                $CUSTOMER_DATA['PARTNER_LAST_NAME']  = $partner_last;
                db_perform_account('DOA_CUSTOMER_DETAILS', $CUSTOMER_DATA, 'insert');
                $PK_CUSTOMER_DETAILS = $db_account->insert_ID();
            }
        }

        if (empty($PK_USER_MASTER)) {
            $skipped_rows[] = "Line $lineNumber — could not resolve customer for email " . htmlspecialchars($email) . " (client ID " . htmlspecialchars($MINDBODY_CLIENT_ID) . ")";
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
                $duplicate_rows[] = "Line $lineNumber — completed enrollment already exists for " . htmlspecialchars($email);
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

                $ENROLLMENT_DATA['PK_ENROLLMENT_TYPE'] = 35;

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
                    $service_details = $findServiceInLocation($db_account, $PK_LOCATION, $svc['code'], false);
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

                // Completed appointments for "Used" count (record-only history)
                // if ($PK_SCHEDULING_CODE_DEFAULT > 0) {

                //     $used_buckets = [
                //         ['count' => $priv_completed,  'code' => 'PRI'],
                //         ['count' => $grp_completed,   'code' => 'GRP'],
                //         ['count' => $party_completed, 'code' => 'Pty'],
                //         ['count' => $coach_completed, 'code' => 'CCH'],
                //     ];

                //     $history_date = $ENROLLMENT_DATA['ENROLLMENT_DATE'];

                //     foreach ($used_buckets as $bucket) {
                //         if ($bucket['count'] <= 0) continue;

                //         $svc_info = $findServiceInLocation($db_account, $PK_LOCATION, $bucket['code'], false);
                //         if (!$svc_info || $svc_info->RecordCount() == 0) continue;

                //         $PK_SVC_MASTER = $svc_info->fields['PK_SERVICE_MASTER'];
                //         $PK_SVC_CODE   = $svc_info->fields['PK_SERVICE_CODE'];
                //         $IS_GROUP_SVC  = (int)$svc_info->fields['IS_GROUP'];

                //         $enr_svc_row = $db_account->Execute("SELECT PK_ENROLLMENT_SERVICE
                //                              FROM DOA_ENROLLMENT_SERVICE
                //                              WHERE PK_ENROLLMENT_MASTER = " . intval($PK_ENROLLMENT_MASTER) . "
                //                                AND PK_SERVICE_MASTER = " . intval($PK_SVC_MASTER) . "
                //                              LIMIT 1");
                //         if (!$enr_svc_row || $enr_svc_row->RecordCount() == 0) continue;
                //         $ENR_SVC_PK = $enr_svc_row->fields['PK_ENROLLMENT_SERVICE'];

                //         for ($u = 0; $u < (int)$bucket['count']; $u++) {

                //             $APPT = [
                //                 'PK_ENROLLMENT_MASTER'  => $PK_ENROLLMENT_MASTER,
                //                 'PK_ENROLLMENT_SERVICE' => $ENR_SVC_PK,
                //                 'PK_SERVICE_MASTER'     => $PK_SVC_MASTER,
                //                 'PK_SERVICE_CODE'       => $PK_SVC_CODE,
                //                 'PK_SCHEDULING_CODE'    => $PK_SCHEDULING_CODE_DEFAULT,
                //                 'PK_LOCATION'           => $PK_LOCATION,
                //                 'APPOINTMENT_TYPE'      => 'RECORD_ONLY',
                //                 'COMMENT'               => 'Imported from Mindbody, lesson history',
                //                 'INTERNAL_COMMENT'      => 'Imported from Mindbody, lesson history',
                //                 'DATE'                  => $history_date,
                //                 'START_TIME'            => '09:00:00',
                //                 'END_TIME'              => '10:00:00',
                //                 'PK_APPOINTMENT_STATUS' => 2,
                //                 'ACTIVE'                => 1,
                //                 'STATUS'                => 'A',
                //                 'IS_CHARGED'            => 1,
                //                 'IS_PAID'               => 1,
                //                 'SERIAL_NUMBER'         => 0,
                //                 'STANDING_ID'           => 0,
                //                 'GROUP_NAME'            => null,
                //                 'NO_SHOW'               => null,
                //                 'CREATED_BY'            => $_SESSION['PK_USER'],
                //                 'CREATED_ON'            => date('Y-m-d H:i'),
                //             ];

                //             db_perform_account('DOA_APPOINTMENT_MASTER', $APPT, 'insert');
                //             $PK_APPT = $db_account->insert_ID();

                //             if ($PK_APPT) {
                //                 $APPT_CUST = [
                //                     'PK_APPOINTMENT_MASTER' => $PK_APPT,
                //                     'PK_USER_MASTER'        => $PK_USER_MASTER,
                //                     'IS_PARTNER'            => 0,
                //                 ];
                //                 db_perform_account('DOA_APPOINTMENT_CUSTOMER', $APPT_CUST, 'insert');

                //                 if ($IS_GROUP_SVC == 1) {
                //                     $APPT_ENR = [
                //                         'PK_APPOINTMENT_MASTER' => $PK_APPT,
                //                         'PK_USER_MASTER'        => $PK_USER_MASTER,
                //                         'PK_ENROLLMENT_MASTER'  => $PK_ENROLLMENT_MASTER,
                //                         'PK_ENROLLMENT_SERVICE' => $ENR_SVC_PK,
                //                         'IS_CHARGED'            => 1,
                //                     ];
                //                     db_perform_account('DOA_APPOINTMENT_ENROLLMENT', $APPT_ENR, 'insert');
                //                 }
                //             }
                //         }
                //     }
                // }

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
                $duplicate_rows[] = "Line $lineNumber — active enrollment already exists for " . htmlspecialchars($email);
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

                // $cen = $ENROLLMENT_DATA['CUSTOMER_ENROLLMENT_NUMBER'];
                // if ($cen <= 1)        $ENROLLMENT_DATA['PK_ENROLLMENT_TYPE'] = 5;
                // else if ($cen == 2)   $ENROLLMENT_DATA['PK_ENROLLMENT_TYPE'] = 2;
                // else if ($cen == 3)   $ENROLLMENT_DATA['PK_ENROLLMENT_TYPE'] = 13;
                // else                  $ENROLLMENT_DATA['PK_ENROLLMENT_TYPE'] = 9;

                $ENROLLMENT_DATA['PK_ENROLLMENT_TYPE'] = 35;

                $TOTAL_COST        = $cost_active;
                $PRICE_PER_SESSION = ($priv_active > 0) ? $TOTAL_COST / $priv_active : 0;

                // Initialize variables to track the extra debt
                $amount_owed_for_misc = 0;

                if ($amount_paid_active === null) {
                    // If we have a specific "Amount Owed" from the CSV
                    if ($amount_owed_remaining > 0) {
                        if ($amount_owed_remaining > $TOTAL_COST) {
                            // If they owe MORE than the lessons cost, 
                            // the lessons are 100% unpaid, and the rest goes to MISC
                            $prepaid_lessons_value = 0;
                            $amount_owed_for_misc = $amount_owed_remaining - $TOTAL_COST;
                        } else {
                            // They owe less than or equal to the lessons cost
                            $prepaid_lessons_value = $TOTAL_COST - $amount_owed_remaining;
                            $amount_owed_for_misc = 0;
                        }
                    } else {
                        // Fallback: If no amount owed is specified, assume it's fully paid
                        $prepaid_lessons_value = $TOTAL_COST;
                        $amount_owed_for_misc = 0;
                    }
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
                    $service_details = $findServiceInLocation($db_account, $PK_LOCATION, $svc['code'], false);
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
        // MISC ENROLLMENT / WALLET CREDIT
        // MISC ENROLLMENT / WALLET CREDIT
        $should_create_misc = false;
        $should_add_to_wallet = false;
        $misc_amount = 0;

        // Rule 1 & 2: Check the sign of the balance
        if ($account_balance > 0) {
            // Positive value = Credit (Add to wallet)
            $should_add_to_wallet = true;
            $misc_amount = $account_balance;
        } elseif ($account_balance < 0) {
            // Negative value = Debt. 
            // If they have no active lessons, OR if they owe more than the lessons cost, create a MISC.
            if ($TOTAL_LESSONS <= 0 || $amount_owed_for_misc > 0) {
                $should_create_misc = true;

                // If we already calculated a leftover debt from the active enrollment, use that.
                // Otherwise (no lessons), use the absolute account balance.
                if ($amount_owed_for_misc > 0) {
                    $misc_amount = $amount_owed_for_misc;
                } else {
                    $misc_amount = abs($account_balance);
                }
            }
        }

        // --- HANDLE WALLET CREDIT (Rule 1) ---
        if ($should_add_to_wallet && $misc_amount > 0) {
            $dup_check = $db_account->Execute("SELECT PK_CUSTOMER_WALLET 
                    FROM DOA_CUSTOMER_WALLET 
                    WHERE PK_USER_MASTER = " . $PK_USER_MASTER . " 
                    AND NOTE LIKE '%Imported from Mindbody%'
                    AND DATE(CREATED_ON) = '" . date('Y-m-d') . "' 
                    LIMIT 1");

            if ($dup_check && $dup_check->RecordCount() > 0) {
                $duplicate_rows[] = "Line $lineNumber — wallet credit already imported today for " . htmlspecialchars($email);
            } else {
                // 1. Get the current running balance of ALL wallet rows for this user
                $current_sum_data = $db_account->Execute("SELECT SUM(BALANCE_LEFT) AS TOTAL_BALANCE 
                                                          FROM DOA_CUSTOMER_WALLET 
                                                          WHERE PK_USER_MASTER = " . $PK_USER_MASTER . " 
                                                          AND IS_DELETED = 0");
                $current_total = ($current_sum_data && $current_sum_data->RecordCount() > 0) ? (float)$current_sum_data->fields['TOTAL_BALANCE'] : 0;

                $new_total_balance = $current_total + $misc_amount;

                // 2. Generate a Receipt Number
                $receipt_lookup = $db_account->Execute("SELECT RECEIPT_NUMBER FROM DOA_CUSTOMER_WALLET WHERE RECEIPT_NUMBER IS NOT NULL AND RECEIPT_NUMBER != '' ORDER BY PK_CUSTOMER_WALLET DESC LIMIT 1");
                if ($receipt_lookup && $receipt_lookup->RecordCount() > 0) {
                    $last_receipt = $receipt_lookup->fields['RECEIPT_NUMBER'];
                    $last_num = (int)str_replace('PMT-', '', $last_receipt);
                    $new_receipt = 'PMT-' . ($last_num + 1);
                } else {
                    $new_receipt = 'PMT-1';
                }

                // 3. Insert the Transaction as a parent row (CUSTOMER_WALLET_PARENT = 0) to match the UI
                $WALLET_DATA = [
                    'CUSTOMER_WALLET_PARENT' => 0, // The UI queries this
                    'PK_USER_MASTER'         => $PK_USER_MASTER,
                    'CURRENT_BALANCE'        => $misc_amount,
                    'DEBIT'                  => 0,
                    'CREDIT'                 => $misc_amount,
                    'BALANCE_LEFT'           => $new_total_balance, // Set the running total
                    'DESCRIPTION'            => 'Mindbody Import Credit',
                    'PK_PAYMENT_TYPE'        => 12,
                    'RECEIPT_NUMBER'         => $new_receipt,
                    'NOTE'                   => 'Imported from Mindbody (Amount Paid Ahead)',
                    'IS_DELETED'             => 0,
                    'CREATED_BY'             => $_SESSION['PK_USER'],
                    'CREATED_ON'             => date('Y-m-d H:i:s'),
                ];
                db_perform_account('DOA_CUSTOMER_WALLET', $WALLET_DATA, 'insert');

                $misc_rows[] = "Line $lineNumber — Wallet credit added for " . htmlspecialchars($email) . " ($" . number_format($misc_amount, 2) . " credit, Receipt: $new_receipt)";
            }
        }

        // --- HANDLE MISC DEBT (Rule 2) ---
        $misc_service = $findServiceInLocation($db_account, $PK_LOCATION, 'MISC', false);
        if (!$misc_service || $misc_service->RecordCount() == 0) {
            $misc_service = $findServiceInLocation($db_account, $PK_LOCATION, 'MIS', false);
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
                $duplicate_rows[] = "Line $lineNumber — miscellaneous enrollment already exists for " . htmlspecialchars($email);
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
                    $misc_rows[] = "Line $lineNumber — misc enrollment for " . htmlspecialchars($email) . " ($" . number_format($misc_amount, 2) . " from account balance)";
                } else {
                    $balance_only_rows[] = "Line $lineNumber — balance-only enrollment for " . htmlspecialchars($email) . " ($" . number_format($misc_amount, 2) . " owed)";
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
        $msg_parts[] = "<br><br><b>Wallet Credits Added:</b><br>" . implode('<br>', $misc_rows);
    }
    if (!empty($balance_only_rows)) {
        $msg_parts[] = "<br><br><b>Miscellaneous Debts Created:</b><br>" . implode('<br>', $balance_only_rows);
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
 * ================================================================
 * HANDLER: Appointments
 * ================================================================
 */
/**
 * ================================================================
 * HANDLER: Appointments
 * ================================================================
 */
/**
 * ================================================================
 * HANDLER: Appointments
 * ================================================================
 */
function handleAppointmentsUpload()
{
    global $db, $PK_ACCOUNT_MASTER;
    global $upload_error, $upload_success;

    $account_data = $db->Execute("SELECT DB_NAME FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER = " . $PK_ACCOUNT_MASTER);
    if (!$account_data || $account_data->RecordCount() == 0) {
        $upload_error = "Account configuration not found.";
        return;
    }
    $DB_NAME = $account_data->fields['DB_NAME'];
    if (empty($DB_NAME)) {
        $upload_error = "Account database name missing.";
        return;
    }

    require_once('../global/common_functions_account.php');
    $account_database = $DB_NAME;
    $db_account = new queryFactory();
    if ($_SERVER['HTTP_HOST'] == 'localhost') {
        $db_account->connect('localhost', 'root', '', $account_database);
    } else {
        $db_account->connect('localhost', 'root', 'b54eawxj5h8ev', $account_database);
    }
    if (mysqli_connect_error()) {
        $upload_error = "Account DB connection failed.";
        return;
    }

    $csvFile = fopen($_FILES['file']['tmp_name'], 'r');
    if (!$csvFile) {
        $upload_error = "Could not open uploaded file.";
        return;
    }

    $bom = fread($csvFile, 3);
    if ($bom !== "\xEF\xBB\xBF") {
        rewind($csvFile);
    }

    $PK_LOCATION = $_POST['PK_LOCATION'];

    $IDX_MINDBODY_CLIENT_ID  = 0;
    $IDX_GROUP_NAME          = 6;
    $IDX_PK_SCHEDULING_CODE  = 9;
    $IDX_DATE                = 11;
    $IDX_START_TIME          = 12;
    $IDX_END_TIME            = 13;
    $IDX_PK_APPOINTMENT_ST   = 14;
    $IDX_NO_SHOW             = 15;
    $IDX_COMMENT             = 16;
    $IDX_INTERNAL_COMMENT    = 17;
    $IDX_IS_PAID             = 24;
    $IDX_IS_CHARGED          = 25;
    $IDX_APPOINTMENT_TYPE    = 26;
    $IDX_CUSTOMER_EMAIL      = 33;
    $IDX_CUSTOMER_NAME       = 34;
    $IDX_SERVICE_PROVIDER    = 36;
    $IDX_SP_EMAIL            = 37;
    $IDX_SERVICE_CODE        = 38;
    $IDX_SCHEDULING_CODE     = 39;

    $skipped_rows = [];
    $warn_rows    = [];
    $dup_rows     = [];
    $imported     = ['NORMAL' => 0, 'AD-HOC' => 0, 'DEMO' => 0, 'GROUP' => 0];

    $lineNumber = 1;

    // =====================================================
    // SERVICE LOOKUP (for AD-HOC service resolution only)
    // Try exact code, then prefix-stripped code.
    // =====================================================
    $findServiceByCode = function ($db_account, $PK_LOCATION, $service_code) {
        $code_safe = addslashes($service_code);
        $loc_safe  = intval($PK_LOCATION);
        return $db_account->Execute("
            SELECT SM.PK_SERVICE_MASTER, SM.SERVICE_NAME,
                   SC.PK_SERVICE_CODE, SC.SERVICE_CODE, SC.IS_GROUP
            FROM DOA_SERVICE_MASTER SM
            INNER JOIN DOA_SERVICE_CODE SC ON SC.PK_SERVICE_MASTER = SM.PK_SERVICE_MASTER
            WHERE SC.SERVICE_CODE = '$code_safe'
              AND SM.ACTIVE = 1 AND SM.IS_DELETED = 0
            ORDER BY (SC.PK_LOCATION = $loc_safe) DESC,
                     (SM.PK_LOCATION = $loc_safe) DESC,
                     SC.PK_SERVICE_CODE DESC
            LIMIT 1
        ");
    };

    $findSchedulingCodeByName = function ($db_account, $PK_LOCATION, $sched_code) {
        $code_safe = addslashes($sched_code);
        return $db_account->Execute("
            SELECT PK_SCHEDULING_CODE
            FROM DOA_SCHEDULING_CODE
            WHERE SCHEDULING_CODE = '$code_safe'
              AND (PK_LOCATION = " . intval($PK_LOCATION) . " OR PK_LOCATION IS NULL)
            ORDER BY PK_LOCATION DESC
            LIMIT 1
        ");
    };

    $group_cache = [];

    while (($getData = fgetcsv($csvFile, 10000, ",")) !== FALSE) {
        if ($lineNumber === 1) {
            $lineNumber++;
            continue;
        }

        if (count($getData) < 5) {
            $lineNumber++;
            continue;
        }

        // =====================================================
        // CUSTOMER — MINDBODY_CLIENT_ID → UNIQUE_ID only.
        // =====================================================
        $mb_id_raw = trim($getData[$IDX_MINDBODY_CLIENT_ID] ?? '');
        $mb_id_int = ($mb_id_raw === '') ? 0 : (int)$mb_id_raw;

        if ($mb_id_int <= 0) {
            $skipped_rows[] = "Line $lineNumber — missing MINDBODY_CLIENT_ID";
            $lineNumber++;
            continue;
        }

        $cust = $db->Execute("
            SELECT U.PK_USER, UM.PK_USER_MASTER
            FROM DOA_USERS U
            INNER JOIN DOA_USER_MASTER UM ON U.PK_USER = UM.PK_USER
            WHERE U.UNIQUE_ID = " . $mb_id_int . "
              AND UM.PRIMARY_LOCATION_ID = " . intval($PK_LOCATION) . "
              AND U.PK_ACCOUNT_MASTER = " . intval($PK_ACCOUNT_MASTER) . "
            LIMIT 1
        ");
        if (!$cust || $cust->RecordCount() == 0) {
            $skipped_rows[] = "Line $lineNumber — no customer with MINDBODY_CLIENT_ID {$mb_id_int} in location {$PK_LOCATION}";
            $lineNumber++;
            continue;
        }
        $PK_USER        = $cust->fields['PK_USER'];
        $PK_USER_MASTER = $cust->fields['PK_USER_MASTER'];

        // =====================================================
        // KIND from CSV service-code prefix
        // =====================================================
        $csv_svc = strtoupper(trim($getData[$IDX_SERVICE_CODE] ?? ''));
        if ($csv_svc === '') $csv_svc = 'PRI'; // default to private

        if (strpos($csv_svc, 'PRI') === 0) {
            $kind = 'PRI';
            $kind_like = 'PRI';
            $kind_is_group = 0;
        } elseif (strpos($csv_svc, 'GRP') === 0) {
            $kind = 'GRP';
            $kind_like = 'GRP';
            $kind_is_group = 1;
        } elseif (strpos($csv_svc, 'PTY') === 0) {
            $kind = 'Pty';
            $kind_like = 'Pty';
            $kind_is_group = 1;
        } elseif (strpos($csv_svc, 'CCH') === 0) {
            $kind = 'CCH';
            $kind_like = 'CCH';
            $kind_is_group = 0;
        } else {
            $kind = 'PRI';
            $kind_like = 'PRI';
            $kind_is_group = 0;
        }

        // =====================================================
        // SCHEDULING CODE — from CSV (PRI45, PRI30, ...)
        // =====================================================
        $sched_label  = trim($getData[$IDX_SCHEDULING_CODE] ?? '');
        $sched_pk_csv = (int)($getData[$IDX_PK_SCHEDULING_CODE] ?? 0);
        $PK_SCHEDULING_CODE = 0;
        if ($sched_label !== '') {
            $sched_row = $findSchedulingCodeByName($db_account, $PK_LOCATION, $sched_label);
            if ($sched_row && $sched_row->RecordCount() > 0) {
                $PK_SCHEDULING_CODE = $sched_row->fields['PK_SCHEDULING_CODE'];
            }
        }
        if ($PK_SCHEDULING_CODE == 0 && $sched_pk_csv > 0) {
            $chk = $db_account->Execute("SELECT PK_SCHEDULING_CODE FROM DOA_SCHEDULING_CODE WHERE PK_SCHEDULING_CODE = " . intval($sched_pk_csv));
            if ($chk && $chk->RecordCount() > 0) $PK_SCHEDULING_CODE = $chk->fields['PK_SCHEDULING_CODE'];
        }
        if ($PK_SCHEDULING_CODE == 0) {
            $skipped_rows[] = "Line $lineNumber — scheduling code '{$sched_label}' not found in location {$PK_LOCATION}";
            $lineNumber++;
            continue;
        }

        // =====================================================
        // SERVICE PROVIDER
        // =====================================================
        $sp_email = trim($getData[$IDX_SP_EMAIL] ?? '');
        $sp_name  = trim($getData[$IDX_SERVICE_PROVIDER] ?? '');
        $PK_SERVICE_PROVIDER = 0;
        if ($sp_email !== '') {
            $sp_email_esc = addslashes($sp_email);
            $sp = $db->Execute("
                SELECT DOA_USERS.PK_USER
                FROM DOA_USERS
                INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER
                INNER JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER
                WHERE DOA_USERS.EMAIL_ID = '$sp_email_esc'
                  AND DOA_USER_LOCATION.PK_LOCATION = " . intval($PK_LOCATION) . "
                  AND DOA_USERS.PK_ACCOUNT_MASTER = " . intval($PK_ACCOUNT_MASTER) . "
                LIMIT 1
            ");
            if ($sp && $sp->RecordCount() > 0) {
                $PK_SERVICE_PROVIDER = $sp->fields['PK_USER'];
            }
        }
        if ($PK_SERVICE_PROVIDER == 0 && $sp_name !== '') {
            $parts = preg_split('/\s+/', $sp_name, 2);
            $fn = addslashes($parts[0]);
            $ln = isset($parts[1]) ? addslashes($parts[1]) : '';
            $sp = $db->Execute("
                SELECT DOA_USERS.PK_USER
                FROM DOA_USERS
                INNER JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER
                WHERE DOA_USERS.FIRST_NAME = '$fn' AND DOA_USERS.LAST_NAME = '$ln'
                  AND DOA_USER_LOCATION.PK_LOCATION = " . intval($PK_LOCATION) . "
                  AND DOA_USERS.PK_ACCOUNT_MASTER = " . intval($PK_ACCOUNT_MASTER) . "
                LIMIT 1
            ");
            if ($sp && $sp->RecordCount() > 0) {
                $PK_SERVICE_PROVIDER = $sp->fields['PK_USER'];
            }
        }
        if ($PK_SERVICE_PROVIDER == 0) {
            $skipped_rows[] = "Line $lineNumber — service provider not found for '{$sp_name}' / '{$sp_email}'";
            $lineNumber++;
            continue;
        }

        // =====================================================
        // DATE / TIME
        // =====================================================
        $date       = normalizeImportDate($getData[$IDX_DATE] ?? '');
        $start_time = trim($getData[$IDX_START_TIME] ?? '');
        $end_time   = trim($getData[$IDX_END_TIME] ?? '');
        if ($date === '' || $start_time === '' || $end_time === '') {
            $skipped_rows[] = "Line $lineNumber — missing date/time (date='{$getData[$IDX_DATE]}', start='{$start_time}')";
            $lineNumber++;
            continue;
        }

        // 1. Check if the CSV explicitly provided a PK_ENROLLMENT_MASTER
        $PK_ENROLLMENT_MASTER = 0;
        $PK_ENROLLMENT_SERVICE = 0;
        $PK_SERVICE_MASTER = 0;
        $PK_SERVICE_CODE = 0;
        $derived_type = 'AD-HOC';

        if ($sched_pk_csv > 0) {
            $specific_enr = $db_account->Execute("
                SELECT EM.PK_ENROLLMENT_MASTER,
                       ES.PK_ENROLLMENT_SERVICE,
                       ES.PK_SERVICE_MASTER,
                       ES.PK_SERVICE_CODE,
                       ES.NUMBER_OF_SESSION,
                       SC.SERVICE_CODE,
                       (SELECT COUNT(*) FROM DOA_APPOINTMENT_MASTER AM
                         WHERE AM.PK_ENROLLMENT_SERVICE = ES.PK_ENROLLMENT_SERVICE
                           AND AM.PK_APPOINTMENT_STATUS = 2) AS USED
                FROM DOA_ENROLLMENT_MASTER EM
                INNER JOIN DOA_ENROLLMENT_SERVICE ES ON ES.PK_ENROLLMENT_MASTER = EM.PK_ENROLLMENT_MASTER
                INNER JOIN DOA_SERVICE_CODE SC ON SC.PK_SERVICE_CODE = ES.PK_SERVICE_CODE
                WHERE EM.PK_ENROLLMENT_MASTER = " . intval($sched_pk_csv) . "
                  AND EM.PK_USER_MASTER = " . intval($PK_USER_MASTER) . "
                  AND EM.STATUS = 'A'
                ORDER BY ES.PK_ENROLLMENT_SERVICE DESC
                LIMIT 1
            ");

            if ($specific_enr && $specific_enr->RecordCount() > 0) {
                $derived_type          = 'NORMAL';
                $PK_ENROLLMENT_MASTER  = $specific_enr->fields['PK_ENROLLMENT_MASTER'];
                $PK_ENROLLMENT_SERVICE = $specific_enr->fields['PK_ENROLLMENT_SERVICE'];
                $PK_SERVICE_MASTER     = $specific_enr->fields['PK_SERVICE_MASTER'];
                $PK_SERVICE_CODE       = $specific_enr->fields['PK_SERVICE_CODE'];
            }
        }

        // 2. If no specific ID was provided (or it failed), run the original fallback search prioritizing PRI over MISC
        if ($PK_ENROLLMENT_MASTER == 0) {
            $group_kind_sql = '';
            if ($kind_is_group === 1) {
                $group_kind_sql = " OR SC.IS_GROUP = 1 ";
            } elseif ($kind_is_group === 0) {
                $group_kind_sql = " OR SC.IS_GROUP = 0 ";
            }

            $enr = $db_account->Execute("
                SELECT EM.PK_ENROLLMENT_MASTER,
                       ES.PK_ENROLLMENT_SERVICE,
                       ES.PK_SERVICE_MASTER,
                       ES.PK_SERVICE_CODE,
                       ES.NUMBER_OF_SESSION,
                       SC.SERVICE_CODE,
                       (SELECT COUNT(*) FROM DOA_APPOINTMENT_MASTER AM
                         WHERE AM.PK_ENROLLMENT_SERVICE = ES.PK_ENROLLMENT_SERVICE
                           AND AM.PK_APPOINTMENT_STATUS = 2) AS USED
                FROM DOA_ENROLLMENT_MASTER EM
                INNER JOIN DOA_ENROLLMENT_SERVICE ES ON ES.PK_ENROLLMENT_MASTER = EM.PK_ENROLLMENT_MASTER
                INNER JOIN DOA_SERVICE_CODE SC ON SC.PK_SERVICE_CODE = ES.PK_SERVICE_CODE
                WHERE EM.PK_USER_MASTER = " . intval($PK_USER_MASTER) . "
                  AND EM.PK_LOCATION    = " . intval($PK_LOCATION) . "
                  AND EM.STATUS         = 'A'
                  AND ( SC.SERVICE_CODE LIKE '" . addslashes($kind_like) . "%' " . $group_kind_sql . " )
                ORDER BY 
                    (SC.SERVICE_CODE = 'MISC') ASC, 
                    (SC.SERVICE_CODE = 'MIS') ASC, 
                    EM.PK_ENROLLMENT_MASTER DESC, 
                    ES.PK_ENROLLMENT_SERVICE DESC
                LIMIT 1
            ");

            if ($enr && $enr->RecordCount() > 0) {
                $remaining = (float)$enr->fields['NUMBER_OF_SESSION'] - (int)$enr->fields['USED'];
                if ($remaining > 0) {
                    $derived_type          = 'NORMAL';
                    $PK_ENROLLMENT_MASTER  = $enr->fields['PK_ENROLLMENT_MASTER'];
                    $PK_ENROLLMENT_SERVICE = $enr->fields['PK_ENROLLMENT_SERVICE'];
                    $PK_SERVICE_MASTER     = $enr->fields['PK_SERVICE_MASTER'];
                    $PK_SERVICE_CODE       = $enr->fields['PK_SERVICE_CODE'];
                }
            }
        }

        // AD-HOC: resolve service from CSV code (try exact, then prefix)
        if ($derived_type === 'AD-HOC') {
            $svc = $findServiceByCode($db_account, $PK_LOCATION, $csv_svc);
            if (!$svc || $svc->RecordCount() == 0) {
                $svc = $findServiceByCode($db_account, $PK_LOCATION, $kind_like);
            }
            if (!$svc || $svc->RecordCount() == 0) {
                $skipped_rows[] = "Line $lineNumber — SERVICE_CODE '{$csv_svc}' (or '{$kind_like}') not found in location {$PK_LOCATION}";
                $lineNumber++;
                continue;
            }
            $PK_SERVICE_MASTER = $svc->fields['PK_SERVICE_MASTER'];
            $PK_SERVICE_CODE   = $svc->fields['PK_SERVICE_CODE'];
        }

        // =====================================================
        // STATUS / FLAGS
        // =====================================================
        $status_pk = (int)($getData[$IDX_PK_APPOINTMENT_ST] ?? 0);
        if ($status_pk <= 0) $status_pk = 1;

        $no_show    = (int)($getData[$IDX_NO_SHOW] ?? 0);
        $is_paid    = (int)($getData[$IDX_IS_PAID] ?? 1);
        $is_charged = (int)($getData[$IDX_IS_CHARGED] ?? 0);
        $comment    = trim($getData[$IDX_COMMENT] ?? '');
        $internal   = trim($getData[$IDX_INTERNAL_COMMENT] ?? '');
        $group_name = trim($getData[$IDX_GROUP_NAME] ?? '');

        $is_group = ($kind_is_group === 1) || ($group_name !== '');

        if (!$is_group) {
            $dup = $db_account->Execute("
                SELECT AM.PK_APPOINTMENT_MASTER
                FROM DOA_APPOINTMENT_MASTER AM
                INNER JOIN DOA_APPOINTMENT_CUSTOMER AC ON AC.PK_APPOINTMENT_MASTER = AM.PK_APPOINTMENT_MASTER
                WHERE AC.PK_USER_MASTER = " . intval($PK_USER_MASTER) . "
                  AND AM.DATE = '" . addslashes($date) . "'
                  AND AM.START_TIME = '" . addslashes($start_time) . "'
                  AND AM.PK_LOCATION = " . intval($PK_LOCATION) . "
                LIMIT 1
            ");
            if ($dup && $dup->RecordCount() > 0) {
                $dup_rows[] = "Line $lineNumber — duplicate appointment for client ID {$mb_id_int} on {$date} {$start_time}";
                $lineNumber++;
                continue;
            }
        }

        // =====================================================
        // INSERT MASTER
        // =====================================================
        $PK_APPOINTMENT_MASTER = 0;

        if ($is_group) {
            $gkey = strtolower($group_name) . '|' . $date . '|' . $start_time;
            if (isset($group_cache[$gkey])) {
                $PK_APPOINTMENT_MASTER = $group_cache[$gkey];
            }
        }

        if ($PK_APPOINTMENT_MASTER == 0) {
            $APPT = [
                'PK_ENROLLMENT_MASTER'   => $PK_ENROLLMENT_MASTER,
                'PK_ENROLLMENT_SERVICE'  => $PK_ENROLLMENT_SERVICE,
                'SERIAL_NUMBER'          => 0,
                'STANDING_ID'            => 0,
                'GROUP_NAME'             => $group_name !== '' ? $group_name : null,
                'PK_SERVICE_MASTER'      => $PK_SERVICE_MASTER,
                'PK_SERVICE_CODE'        => $PK_SERVICE_CODE,
                'PK_SCHEDULING_CODE'     => $PK_SCHEDULING_CODE,
                'PK_LOCATION'            => $PK_LOCATION,
                'DATE'                   => $date,
                'START_TIME'             => $start_time,
                'END_TIME'               => $end_time,
                'PK_APPOINTMENT_STATUS'  => $status_pk,
                'NO_SHOW'                => $no_show,
                'COMMENT'                => $comment,
                'INTERNAL_COMMENT'       => $internal,
                'ACTIVE'                 => 1,
                'STATUS'                 => 'A',
                'IS_PAID'                => $is_paid,
                'IS_CHARGED'             => $is_charged,
                'APPOINTMENT_TYPE'       => ($is_group ? 'GROUP' : $derived_type),
                'IS_REMINDER_SEND'       => 0,
                'IS_FROM_AI_CALL'        => 0,
                'CREATED_BY'             => $_SESSION['PK_USER'],
                'CREATED_ON'             => date('Y-m-d H:i'),
            ];

            $insert_ok = db_perform_account('DOA_APPOINTMENT_MASTER', $APPT, 'insert');

            if (!$insert_ok) {
                $conn = null;
                if (isset($db_account->connection)) $conn = $db_account->connection;
                elseif (isset($db_account->conn))    $conn = $db_account->conn;
                elseif (isset($db_account->link))    $conn = $db_account->link;
                $mysql_err = $conn ? mysqli_error($conn) : '(no connection handle)';

                error_log("APPT INSERT FAILED line $lineNumber: $mysql_err");
                $warn_rows[] = "Line $lineNumber — could not create appointment master: " . htmlspecialchars($mysql_err);
                $lineNumber++;
                continue;
            }

            // db_perform_account doesn't return an ID; look the row back up
            // by its unique fingerprint (location + service + schedule + date + time).
            $row_back = $db_account->Execute("
    SELECT PK_APPOINTMENT_MASTER
    FROM DOA_APPOINTMENT_MASTER
    WHERE PK_LOCATION         = " . intval($PK_LOCATION) . "
      AND PK_SERVICE_MASTER   = " . intval($PK_SERVICE_MASTER) . "
      AND PK_SCHEDULING_CODE  = " . intval($PK_SCHEDULING_CODE) . "
      AND DATE                = '" . addslashes($date) . "'
      AND START_TIME          = '" . addslashes($start_time) . "'
    ORDER BY PK_APPOINTMENT_MASTER DESC
    LIMIT 1
");

            if (!$row_back || $row_back->RecordCount() == 0) {
                $warn_rows[] = "Line $lineNumber — inserted appointment but could not re-fetch its PK";
                $lineNumber++;
                continue;
            }

            $PK_APPOINTMENT_MASTER = (int)$row_back->fields['PK_APPOINTMENT_MASTER'];

            if ($is_group) {
                $group_cache[$gkey] = $PK_APPOINTMENT_MASTER;
                $imported['GROUP']++;
            } else {
                $imported[$derived_type]++;
            }
        }

        // =====================================================
        // ATTACH CUSTOMER
        // =====================================================
        $already = $db_account->Execute("
            SELECT PK_APPOINTMENT_CUSTOMER
            FROM DOA_APPOINTMENT_CUSTOMER
            WHERE PK_APPOINTMENT_MASTER = " . intval($PK_APPOINTMENT_MASTER) . "
              AND PK_USER_MASTER = " . intval($PK_USER_MASTER) . "
            LIMIT 1
        ");
        if (!$already || $already->RecordCount() == 0) {
            $APPT_CUST = [
                'PK_APPOINTMENT_MASTER' => $PK_APPOINTMENT_MASTER,
                'PK_USER_MASTER'        => $PK_USER_MASTER,
                'IS_PARTNER'            => 0,
            ];
            db_perform_account('DOA_APPOINTMENT_CUSTOMER', $APPT_CUST, 'insert');
        }

        // =====================================================
        // ATTACH SERVICE PROVIDER
        // =====================================================
        $already_sp = $db_account->Execute("
            SELECT PK_APPOINTMENT_MASTER
            FROM DOA_APPOINTMENT_SERVICE_PROVIDER
            WHERE PK_APPOINTMENT_MASTER = " . intval($PK_APPOINTMENT_MASTER) . "
              AND PK_USER = " . intval($PK_SERVICE_PROVIDER) . "
            LIMIT 1
        ");
        if (!$already_sp || $already_sp->RecordCount() == 0) {
            $APPT_SP = [
                'PK_APPOINTMENT_MASTER' => $PK_APPOINTMENT_MASTER,
                'PK_USER'               => $PK_SERVICE_PROVIDER,
            ];
            db_perform_account('DOA_APPOINTMENT_SERVICE_PROVIDER', $APPT_SP, 'insert');
        }

        // =====================================================
        // ENROLLMENT LINK — only when NORMAL
        // =====================================================
        if ($derived_type === 'NORMAL' && $PK_ENROLLMENT_SERVICE > 0) {
            $already_enr = $db_account->Execute("
                SELECT PK_APPOINTMENT_MASTER
                FROM DOA_APPOINTMENT_ENROLLMENT
                WHERE PK_APPOINTMENT_MASTER = " . intval($PK_APPOINTMENT_MASTER) . "
                  AND PK_USER_MASTER = " . intval($PK_USER_MASTER) . "
                LIMIT 1
            ");
            if (!$already_enr || $already_enr->RecordCount() == 0) {
                $APPT_ENR = [
                    'PK_APPOINTMENT_MASTER' => $PK_APPOINTMENT_MASTER,
                    'PK_USER_MASTER'        => $PK_USER_MASTER,
                    'PK_ENROLLMENT_MASTER'  => $PK_ENROLLMENT_MASTER,
                    'PK_ENROLLMENT_SERVICE' => $PK_ENROLLMENT_SERVICE,
                    'IS_CHARGED'            => $is_charged,
                ];
                db_perform_account('DOA_APPOINTMENT_ENROLLMENT', $APPT_ENR, 'insert');
            }
        }

        $lineNumber++;
    }

    fclose($csvFile);

    // =================================================================
    // REPORT
    // =================================================================
    $total_imported = $imported['NORMAL'] + $imported['AD-HOC'] + $imported['DEMO'] + $imported['GROUP'];
    $total_skipped  = count($skipped_rows);
    $total_dup      = count($dup_rows);
    $total_warn     = count($warn_rows);

    $skipped_by_reason = [];
    foreach ($skipped_rows as $row) {
        if (preg_match('/^Line \d+ — (.+)$/u', $row, $m)) $reason = $m[1];
        else                                           $reason = $row;

        if (preg_match('/^no customer with MINDBODY_CLIENT_ID (\d+) in location (\d+)$/', $reason, $m)) {
            $cat = "Customer not found by MINDBODY_CLIENT_ID in location " . $m[2];
        } elseif (preg_match("/^SERVICE_CODE '(.+)' \(or '(.+)'\) not found in location (\d+)$/", $reason, $m)) {
            $cat = "Service code not found in location " . $m[3];
        } elseif (preg_match("/^scheduling code '(.+)' not found in location (\d+)$/", $reason, $m)) {
            $cat = "Scheduling code not found in location " . $m[2];
        } elseif (preg_match("/^service provider not found for '(.+)' \/ '(.+)'$/", $reason, $m)) {
            $cat = "Service provider not found";
        } elseif (strpos($reason, 'missing MINDBODY_CLIENT_ID') !== false) {
            $cat = "Missing MINDBODY_CLIENT_ID";
        } elseif (strpos($reason, 'missing date/time') !== false) {
            $cat = "Missing date/time";
        } else {
            $cat = "Other";
        }
        $skipped_by_reason[$cat][] = $row;
    }

    $report  = '<div style="line-height:1.6;">';
    $report .= '<h5 style="margin-top:0;color:#166534;">✅ Appointments CSV processed</h5>';

    $report .= '<table style="border-collapse:collapse;margin-bottom:16px;font-size:0.9rem;">';
    $report .= '<tr><td style="padding:4px 14px 4px 0;font-weight:600;">Total rows imported</td>'
        . '<td style="padding:4px 0;"><b style="color:#39b54a;">' . $total_imported . '</b></td></tr>';
    $report .= '<tr><td style="padding:4px 14px 4px 0;">&nbsp;&nbsp;• NORMAL (booked against enrollment)</td>'
        . '<td style="padding:4px 0;">' . $imported['NORMAL'] . '</td></tr>';
    $report .= '<tr><td style="padding:4px 14px 4px 0;">&nbsp;&nbsp;• AD-HOC (no lessons left / no enrollment)</td>'
        . '<td style="padding:4px 0;">' . $imported['AD-HOC'] . '</td></tr>';
    $report .= '<tr><td style="padding:4px 14px 4px 0;">&nbsp;&nbsp;• DEMO (historical record only)</td>'
        . '<td style="padding:4px 0;">' . $imported['DEMO'] . '</td></tr>';
    $report .= '<tr><td style="padding:4px 14px 4px 0;">&nbsp;&nbsp;• GROUP (group class with attendees)</td>'
        . '<td style="padding:4px 0;">' . $imported['GROUP'] . '</td></tr>';
    $report .= '<tr><td style="padding:4px 14px 4px 0;font-weight:600;color:#b91c1c;">Total rows skipped</td>'
        . '<td style="padding:4px 0;"><b style="color:#b91c1c;">' . $total_skipped . '</b></td></tr>';
    $report .= '<tr><td style="padding:4px 14px 4px 0;color:#a16207;">Rows skipped as duplicates</td>'
        . '<td style="padding:4px 0;color:#a16207;">' . $total_dup . '</td></tr>';
    if ($total_warn > 0) {
        $report .= '<tr><td style="padding:4px 14px 4px 0;color:#a16207;">Warnings</td>'
            . '<td style="padding:4px 0;color:#a16207;">' . $total_warn . '</td></tr>';
    }
    $report .= '</table>';

    if (!empty($skipped_by_reason)) {
        $report .= '<h6 style="margin-top:18px;margin-bottom:8px;color:#b91c1c;">Why rows were skipped</h6>';
        foreach ($skipped_by_reason as $reason => $rows) {
            $count = count($rows);
            $report .= '<details style="margin-bottom:8px;border:1px solid #fca5a5;border-radius:8px;padding:8px 12px;background:#fef2f2;">';
            $report .= '<summary style="cursor:pointer;font-weight:600;color:#b91c1c;">'
                . htmlspecialchars($reason)
                . ' <span style="background:#fca5a5;color:#7f1d1d;border-radius:999px;padding:1px 8px;font-size:0.75rem;margin-left:6px;">' . $count . '</span>'
                . '</summary>';
            $report .= '<ul style="margin:8px 0 0 0;padding-left:20px;font-size:0.85rem;color:#7f1d1d;max-height:280px;overflow-y:auto;">';
            foreach ($rows as $r) $report .= '<li>' . htmlspecialchars($r) . '</li>';
            $report .= '</ul></details>';
        }
    }

    if (!empty($dup_rows)) {
        $report .= '<details style="margin-top:12px;border:1px solid #fde68a;border-radius:8px;padding:8px 12px;background:#fffbeb;">';
        $report .= '<summary style="cursor:pointer;font-weight:600;color:#a16207;">'
            . 'Duplicates already present in the system'
            . ' <span style="background:#fde68a;color:#78350f;border-radius:999px;padding:1px 8px;font-size:0.75rem;margin-left:6px;">' . count($dup_rows) . '</span>'
            . '</summary>';
        $report .= '<ul style="margin:8px 0 0 0;padding-left:20px;font-size:0.85rem;color:#78350f;max-height:280px;overflow-y:auto;">';
        foreach ($dup_rows as $r) $report .= '<li>' . htmlspecialchars($r) . '</li>';
        $report .= '</ul></details>';
    }

    if (!empty($warn_rows)) {
        $report .= '<details style="margin-top:12px;border:1px solid #93c5fd;border-radius:8px;padding:8px 12px;background:#eff6ff;" open>';
        $report .= '<summary style="cursor:pointer;font-weight:600;color:#1d4ed8;">'
            . 'Warnings'
            . ' <span style="background:#93c5fd;color:#1e3a8a;border-radius:999px;padding:1px 8px;font-size:0.75rem;margin-left:6px;">' . count($warn_rows) . '</span>'
            . '</summary>';
        $report .= '<ul style="margin:8px 0 0 0;padding-left:20px;font-size:0.85rem;color:#1e3a8a;max-height:280px;overflow-y:auto;">';
        foreach ($warn_rows as $r) $report .= '<li>' . $r . '</li>';
        $report .= '</ul></details>';
    }

    $report .= '</div>';

    $upload_success = $report;
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
                            <p class="text-muted small mb-0">Upload customer, enrollment, or appointment data via CSV</p>
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
                        Choose an uploader type, then select a location and CSV file.
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