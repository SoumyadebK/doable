<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;
global $upload_path;
global $http_path;

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4])) {
    header("location:../login.php");
    exit;
}

$PK_ENROLLMENT_MASTER = $_GET['id'] ?: 0;

$enrollment_data = $db_account->Execute("SELECT AGREEMENT_PDF_LINK, IS_SIGNED FROM DOA_ENROLLMENT_MASTER WHERE PK_ENROLLMENT_MASTER = '$PK_ENROLLMENT_MASTER'");

$filePath = "../" . $upload_path . "/enrollment_pdf/" . $enrollment_data->fields['AGREEMENT_PDF_LINK'];

//echo $filePath; die;

// 3. Confirm file exists
if (!file_exists($filePath)) {
    http_response_code(404);
    die('File not found.');
}

// 4. Stream it
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="agreement.pdf"'); // inline = opens in browser
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: private, no-cache, no-store, must-revalidate');
header('Pragma: no-cache');

readfile($filePath);
exit;
