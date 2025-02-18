<?php
require_once('../global/config.php');
require_once '../global/dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$_SESSION['week_number'] = $_GET['week_number'];
$_SESSION['start_date'] = $_GET['start_date'];
$report_type = $_GET['report_type'];

if ($report_type == "royalty_service_report" || $report_type == "payments_made_report") {
    $orientation = "landscape";
} else {
    $orientation = "portrait";
}

if ($report_type == "payments_made_report") {
    $paper_size = "A3";
} else {
    $paper_size = "A4";
}

$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('isPhpEnabled', true);
$dompdf = new Dompdf($options);

// Fetch the HTML content of the PHP page
ob_start(); // Start output buffering
include "pdf_".$report_type.".php"; // Replace with the path to your PHP page
$html = ob_get_clean(); // Get the buffered content and clean the buffer

// Load HTML content into Dompdf
$dompdf->loadHtml($html);

// (Optional) Set paper size and orientation
$dompdf->setPaper($paper_size, $orientation);

// Render the HTML as PDF
$dompdf->render();

// Output the generated PDF to the browser
$dompdf->stream($report_type.'_'.$_GET['week_number'].'.pdf', ['Attachment' => true]); // Set 'Attachment' to true to force download
?>