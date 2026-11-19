<?php
session_start();
require_once('../global/config.php');
if (empty($_SESSION['PK_USER'])) {
    http_response_code(401);
    exit;
}

$job_id = (int)($_GET['job'] ?? 0);
$r = $db->Execute("SELECT * FROM DOA_IMPORT_JOBS
                   WHERE PK_IMPORT_JOB=$job_id
                     AND PK_ACCOUNT_MASTER=" . (int)$_SESSION['PK_ACCOUNT_MASTER'] . " LIMIT 1");
if (!$r || $r->RecordCount() == 0) {
    http_response_code(404);
    exit;
}

$j = $r->fields;
header('Content-Type: application/json');
echo json_encode([
    'id'            => (int)$j['PK_IMPORT_JOB'],
    'status'        => $j['STATUS'],
    'file_name'     => $j['FILE_NAME'],
    'upload_type'   => $j['UPLOAD_TYPE'],
    'total'         => (int)$j['TOTAL_ROWS'],
    'processed'     => (int)$j['PROCESSED_ROWS'],
    'rows_created'  => (int)$j['ROWS_CREATED'],
    'wallet'        => (int)$j['WALLET_CREDITS'],
    'rows_skipped'  => (int)$j['ROWS_SKIPPED'],
    'started_at'    => $j['STARTED_AT'],
    'finished_at'   => $j['FINISHED_AT'],
    'report'        => $j['REPORT_JSON'] ? json_decode($j['REPORT_JSON'], true) : null,
    'error'         => $j['ERROR_TEXT'],
]);
