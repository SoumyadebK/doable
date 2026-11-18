<?php
// CLI only
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
set_time_limit(0);
ini_set('max_execution_time', 0);

$JOB_ID            = (int)($argv[1] ?? 0);
$PK_ACCOUNT_MASTER = (int)($argv[2] ?? 0);
$PK_USER_RUNNER    = (int)($argv[3] ?? 0);
if (!$JOB_ID) {
    fwrite(STDERR, "missing job id\n");
    exit(1);
}

// Fake the bits of the request the handlers expect
$_SERVER['HTTP_HOST']      = 'localhost'; // change to your host detection if you need prod passwords
$_SESSION['PK_USER']       = $PK_USER_RUNNER;
$_SESSION['PK_ACCOUNT_MASTER'] = $PK_ACCOUNT_MASTER;

require_once(__DIR__ . '/../global/config.php');
require_once(__DIR__ . '/../global/common_functions_account.php');
require_once(__DIR__ . '/../global/functions_progress.php'); // <- tiny helper below

// Load the job row
$job = $db->Execute("SELECT * FROM DOA_IMPORT_JOBS WHERE PK_IMPORT_JOB = $JOB_ID LIMIT 1");
if (!$job || $job->RecordCount() == 0) {
    exit("job not found\n");
}
$J = $job->fields;

$csv_path = __DIR__ . '/imports/' . $JOB_ID . '.csv';
if (!is_file($csv_path)) {
    $db->Execute("UPDATE DOA_IMPORT_JOBS SET STATUS='failed', ERROR_TEXT='CSV missing', FINISHED_AT=NOW() WHERE PK_IMPORT_JOB=$JOB_ID");
    exit("csv missing\n");
}

// Emulate $_POST / $_FILES for the existing handlers
$_POST['PK_LOCATION'] = (int)$J['PK_LOCATION'];
$_FILES['file'] = [
    'name'     => $J['FILE_NAME'],
    'type'     => 'text/csv',
    'tmp_name' => $csv_path,
    'error'    => UPLOAD_ERR_OK,
    'size'     => filesize($csv_path),
];

// Mark running
$db->Execute("UPDATE DOA_IMPORT_JOBS SET STATUS='running', STARTED_AT=NOW() WHERE PK_IMPORT_JOB=$JOB_ID");

// === Paste your three functions verbatim here ===
function normalizeImportDate($raw)
{ /* ... unchanged from your file ... */
}
function handleStaffUpload()
{ /* ... unchanged ... */
}
function handleCustomerEnrollmentsUpload()
{ /* ... unchanged ... */
}
function handleAppointmentsUpload()
{ /* ... unchanged ... */
}

// Progress hooks — replace the existing counters at end of each handler with a call to
// progress_tick($rows_created, $wallet_credits, $rows_skipped) so the UI updates live.
// (see section 5)

$upload_error = null;
$upload_success = null;

try {
    switch ($J['UPLOAD_TYPE']) {
        case 'staff':
            handleStaffUpload();
            break;
        case 'customer_enrollments':
            handleCustomerEnrollmentsUpload();
            break;
        case 'appointments':
            handleAppointmentsUpload();
            break;
        default:
            throw new Exception("unknown upload type");
    }
} catch (Throwable $e) {
    $upload_error = $e->getMessage();
}

if (!empty($upload_error)) {
    $db->Execute("UPDATE DOA_IMPORT_JOBS SET STATUS='failed',
                   ERROR_TEXT='" . addslashes($upload_error) . "',
                   FINISHED_AT=NOW() WHERE PK_IMPORT_JOB=$JOB_ID");
    exit("failed: $upload_error\n");
}

// Wrap up — store the same HTML report you already build
$db->Execute("UPDATE DOA_IMPORT_JOBS SET
        STATUS='done',
        PROCESSED_ROWS=TOTAL_ROWS,
        REPORT_JSON='" . addslashes(json_encode(['html' => $upload_success])) . "',
        FINISHED_AT=NOW()
    WHERE PK_IMPORT_JOB=$JOB_ID");

@unlink($csv_path); // clean up
exit(0);
