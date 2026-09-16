<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;
global $upload_path;
global $http_path;

require '../vendor/autoload.php';

use setasign\Fpdi\Fpdi;

// Get JSON input
$data = json_decode(file_get_contents("php://input"), true);
$image = $data['image'];
$PK_ENROLLMENT_MASTER = $data['PK_ENROLLMENT_MASTER'];

$enrollment_data = $db_account->Execute("SELECT AGREEMENT_PDF_LINK, IS_SIGNED FROM DOA_ENROLLMENT_MASTER WHERE PK_ENROLLMENT_MASTER = '$PK_ENROLLMENT_MASTER'");

if ($enrollment_data->fields['IS_SIGNED'] == 1) {
    echo json_encode(["status" => "failed"]);
    exit;
}

$update_history_data = $db_account->Execute("SELECT * FROM DOA_UPDATE_HISTORY WHERE CLASS = 'enrollment_signature' AND PRIMARY_KEY = '$PK_ENROLLMENT_MASTER' ORDER BY EDITED_ON ASC LIMIT 1");
if ($update_history_data->RecordCount() > 0) {
    $ORIGINAL_AGREEMENT = $update_history_data->fields['FROM_VALUE'];
} else {
    $ORIGINAL_AGREEMENT = $enrollment_data->fields['AGREEMENT_PDF_LINK'];
}

$last_update_data = $db_account->Execute("SELECT * FROM DOA_UPDATE_HISTORY WHERE CLASS = 'enrollment_signature' AND PRIMARY_KEY = '$PK_ENROLLMENT_MASTER' ORDER BY EDITED_ON DESC LIMIT 1");
if ($last_update_data->RecordCount() > 0) {
    $LAST_UPDATED_AGREEMENT = $last_update_data->fields['TO_VALUE'];
} else {
    $LAST_UPDATED_AGREEMENT = $ORIGINAL_AGREEMENT;
}

// Convert base64 to image
$image = str_replace('data:image/png;base64,', '', $image);
$image = str_replace(' ', '+', $image);
$imageData = base64_decode($image);

$enrollment_location = $db_account->Execute("SELECT DOA_LOCATION.LOCATION_CODE FROM DOA_ENROLLMENT_MASTER LEFT JOIN $master_database.DOA_LOCATION AS DOA_LOCATION ON DOA_LOCATION.PK_LOCATION = DOA_ENROLLMENT_MASTER.PK_LOCATION WHERE PK_ENROLLMENT_MASTER = '$PK_ENROLLMENT_MASTER'");
$LOCATION_CODE = $enrollment_location->fields['LOCATION_CODE'];

$signatureToken = generateSecureToken(16);
$signatureFileName = $signatureToken . '_signature.png';

file_put_contents('../' . $upload_path . '/enrollment_pdf/' . $LOCATION_CODE . '/' . $signatureFileName, $imageData);

// Load existing PDF
$pdf = new FPDI();
$pageCount = $pdf->setSourceFile("../" . $upload_path . "/enrollment_pdf/" . $ORIGINAL_AGREEMENT);

for ($i = 1; $i <= $pageCount; $i++) {
    $template = $pdf->importPage($i);
    $pdf->AddPage();
    $pdf->useTemplate($template);

    // Add signature on first page (adjust position)
    if ($i == 2) {
        $pdf->Image('../' . $upload_path . '/enrollment_pdf/' . $LOCATION_CODE . '/' . $signatureFileName, 85, 70, 60); // X, Y, Width
    }
}

// Save signed PDF
$agreement_file_name = "enrollment_pdf_" . generateSecureToken(16) . ".pdf";
$pdf->Output("F", '../' . $upload_path . '/enrollment_pdf/' . $LOCATION_CODE . '/' . $agreement_file_name);

$updated_file_name = $LOCATION_CODE . '/' . $agreement_file_name;
$db_account->Execute("UPDATE DOA_ENROLLMENT_MASTER SET AGREEMENT_PDF_LINK = '$updated_file_name', IS_SIGNED = 1 WHERE PK_ENROLLMENT_MASTER = '$PK_ENROLLMENT_MASTER'");


$UPDATE_HISTORY_DATA['CLASS'] = 'enrollment_signature';
$UPDATE_HISTORY_DATA['PRIMARY_KEY'] = $PK_ENROLLMENT_MASTER;
$UPDATE_HISTORY_DATA['FIELD_NAME'] = 'AGREEMENT';
$UPDATE_HISTORY_DATA['FROM_VALUE'] = $LAST_UPDATED_AGREEMENT;
$UPDATE_HISTORY_DATA['TO_VALUE'] = $updated_file_name;
$UPDATE_HISTORY_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
$UPDATE_HISTORY_DATA['EDITED_ON'] = date("Y-m-d H:i");
db_perform_account('DOA_UPDATE_HISTORY', $UPDATE_HISTORY_DATA, 'insert');

echo json_encode(["status" => "success"]);
