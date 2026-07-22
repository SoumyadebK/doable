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

$enrollment_data = $db_account->Execute("SELECT AGREEMENT_PDF_LINK FROM DOA_ENROLLMENT_MASTER WHERE PK_ENROLLMENT_MASTER = '$PK_ENROLLMENT_MASTER'");
$AGREEMENT_PDF_LINK = $enrollment_data->fields['AGREEMENT_PDF_LINK'];

// Convert base64 to image
$image = str_replace('data:image/png;base64,', '', $image);
$image = str_replace(' ', '+', $image);
$imageData = base64_decode($image);

$enrollment_location = $db_account->Execute("SELECT DOA_LOCATION.LOCATION_CODE FROM DOA_ENROLLMENT_MASTER LEFT JOIN $master_database.DOA_LOCATION AS DOA_LOCATION ON DOA_LOCATION.PK_LOCATION = DOA_ENROLLMENT_MASTER.PK_LOCATION WHERE PK_ENROLLMENT_MASTER = '$PK_ENROLLMENT_MASTER'");
$LOCATION_CODE = $enrollment_location->fields['LOCATION_CODE'];

file_put_contents('../' . $upload_path . '/enrollment_pdf/' . $LOCATION_CODE . '/' . $PK_ENROLLMENT_MASTER . '_signature.png', $imageData);

// Load existing PDF
$pdf = new FPDI();
$pageCount = $pdf->setSourceFile("../" . $upload_path . "/enrollment_pdf/" . $AGREEMENT_PDF_LINK);

for ($i = 1; $i <= $pageCount; $i++) {
    $template = $pdf->importPage($i);
    $pdf->AddPage();
    $pdf->useTemplate($template);

    // Add signature on first page (adjust position)
    if ($i == 1) {
        $pdf->Image($http_path . $upload_path . '/enrollment_pdf/' . $LOCATION_CODE . '/' . $PK_ENROLLMENT_MASTER . '_signature.png', 90, 215, 60); // X, Y, Width
    }
}

// Save signed PDF
$file_name = "enrollment_pdf_" . time() . ".pdf";
$pdf->Output("F", '../' . $upload_path . '/enrollment_pdf/' . $LOCATION_CODE . '/' . $file_name);

$updated_file_name = $LOCATION_CODE . '/' . $file_name;
$db_account->Execute("UPDATE DOA_ENROLLMENT_MASTER SET AGREEMENT_PDF_LINK = '$updated_file_name' WHERE PK_ENROLLMENT_MASTER = '$PK_ENROLLMENT_MASTER'");

echo json_encode(["status" => "success"]);
