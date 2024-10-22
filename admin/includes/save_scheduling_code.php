<?php
require_once('../../global/config.php');

global $db;
global $db_account;

$SCHEDULING_DATA['SCHEDULING_CODE'] = $_POST['code'];
$SCHEDULING_DATA['SCHEDULING_NAME'] = $_POST['name'];
$SCHEDULING_DATA['TO_DOS'] = ($_POST['todos'] == "true") ? 1 : 0;
$SCHEDULING_DATA['COLOR_CODE'] = $_POST['color'];
$SCHEDULING_DATA['DURATION'] = $_POST['duration'];
$SCHEDULING_DATA['UNIT'] = $_POST['unit'];
$SCHEDULING_DATA['SORT_ORDER'] = $_POST['order'];
$SCHEDULING_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
$SCHEDULING_DATA['EDITED_ON'] = date("Y-m-d H:i");
db_perform_account('DOA_SCHEDULING_CODE', $SCHEDULING_DATA, 'update', " PK_SCHEDULING_CODE = '$_POST[id]'");

echo json_encode(["status" => "success", "message" => "Record updated successfully"]);
