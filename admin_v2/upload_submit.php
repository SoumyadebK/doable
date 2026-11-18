<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
session_start();
require_once('../global/config.php');

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

$PK_ACCOUNT_MASTER = (int)$_SESSION['PK_ACCOUNT_MASTER'];
$PK_LOCATION       = (int)($_POST['PK_LOCATION'] ?? 0);
$UPLOAD_TYPE       = $_POST['UPLOAD_TYPE'] ?? '';
$submitted_token   = $_POST['upload_token'] ?? '';

$VALID_TYPES = ['customer_enrollments', 'appointments', 'staff'];

function fail_back($msg)
{
    $_SESSION['UPLOAD_FLASH_ERROR'] = $msg;
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'data_uploader.php'));
    exit;
}

// --- token ---
$token_ok = $submitted_token !== ''
    && isset($_SESSION['UPLOAD_TOKENS'][$submitted_token])
    && $_SESSION['UPLOAD_TOKENS'][$submitted_token]['used'] === false;
if (!$token_ok) fail_back("This upload link has expired or was already used. Refresh and retry.");

// --- type / location ---
if (!in_array($UPLOAD_TYPE, $VALID_TYPES, true)) fail_back("Invalid upload type.");
if ($PK_LOCATION <= 0) fail_back("Please select a location.");

// --- file ---
if (empty($_FILES['file']['name']))            fail_back("No file uploaded.");
if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) fail_back("Upload error code " . $_FILES['file']['error']);
$allowed = ['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel', 'application/octet-stream', 'text/x-csv', 'application/x-csv', 'text/comma-separated-values', 'text/x-comma-separated-values', 'application/excel', 'application/vnd.msexcel', 'application/msexcel'];
if (!in_array($_FILES['file']['type'], $allowed)) fail_back("Invalid file type. Upload a CSV.");

// --- LOCATION LOCK: refuse if a job is running for this location ---
$lock = $db->Execute("SELECT PK_IMPORT_JOB FROM DOA_IMPORT_JOBS
                      WHERE PK_LOCATION=" . $PK_LOCATION . "
                        AND STATUS IN ('queued','running') LIMIT 1");
if ($lock && $lock->RecordCount() > 0) {
    fail_back("Another import is already running for this location. Please wait for it to finish.");
}

// --- DUPLICATE FILE GUARD ---
$file_hash = hash_file('sha256', $_FILES['file']['tmp_name']);
$dup = $db->Execute("SELECT PK_IMPORT_JOB FROM DOA_IMPORT_JOBS
                     WHERE FILE_HASH='" . addslashes($file_hash) . "'
                       AND PK_LOCATION=" . $PK_LOCATION . "
                       AND STATUS IN ('queued','running','done') LIMIT 1");
if ($dup && $dup->RecordCount() > 0) {
    fail_back("This exact file has already been uploaded to this location. Not importing again.");
}

// --- count rows quickly ---
$total = 0;
if (($fh = fopen($_FILES['file']['tmp_name'], 'r')) !== false) {
    while (fgets($fh) !== false) $total++;
    fclose($fh);
}
$total = max(0, $total - 1); // minus header

// --- persist the CSV next to the worker ---
$import_dir = __DIR__ . '/imports';
if (!is_dir($import_dir)) @mkdir($import_dir, 0775, true);
$safe_name = preg_replace('/[^A-Za-z0-9._-]/', '_', $_FILES['file']['name']);

// --- insert job row ---
$JOB = [
    'PK_ACCOUNT_MASTER' => $PK_ACCOUNT_MASTER,
    'PK_LOCATION'       => $PK_LOCATION,
    'UPLOAD_TYPE'       => $UPLOAD_TYPE,
    'FILE_NAME'         => $safe_name,
    'FILE_HASH'         => $file_hash,
    'STATUS'            => 'queued',
    'TOTAL_ROWS'        => $total,
    'CREATED_BY'        => (int)$_SESSION['PK_USER'],
    'CREATED_ON'        => date('Y-m-d H:i:s'),
];
db_perform('DOA_IMPORT_JOBS', $JOB, 'insert');
$job_id = (int)$db->insert_ID();
if (!$job_id) fail_back("Could not create import job.");

$target = $import_dir . '/' . $job_id . '.csv';
if (!move_uploaded_file($_FILES['file']['tmp_name'], $target)) {
    $db->Execute("UPDATE DOA_IMPORT_JOBS SET STATUS='failed', ERROR_TEXT='Could not save upload' WHERE PK_IMPORT_JOB=$job_id");
    fail_back("Could not save the uploaded file.");
}

// mark token used now that the job exists
$_SESSION['UPLOAD_TOKENS'][$submitted_token]['used'] = true;

// --- spawn worker (detached; do not wait) ---
$php    = PHP_BINARY ?: '/usr/bin/php';
$worker = escapeshellarg(__DIR__ . '/import_worker.php');
$args   = $job_id . ' ' . $PK_ACCOUNT_MASTER . ' ' . (int)$_SESSION['PK_USER'];
@exec("$php $worker $args > /dev/null 2>&1 &");

// Release session lock, then go to the progress page
session_write_close();
header("Location: uploader.php?job=" . $job_id);
exit;
