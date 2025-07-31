<?php
require_once('../../global/config.php');

global $db;
global $db_account;

$LEDGER_DATA['DUE_DATE'] = date('Y-m-d', strtotime($_POST['date']));
db_perform_account('DOA_ENROLLMENT_LEDGER', $LEDGER_DATA, 'update', " PK_ENROLLMENT_LEDGER = '$_POST[id]'");

echo json_encode(["status" => "success", "message" => "Record updated successfully"]);
