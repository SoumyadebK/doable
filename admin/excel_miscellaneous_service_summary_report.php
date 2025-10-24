<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;
global $account_database;
error_reporting(0);

include('../global/excel/Classes/PHPExcel/IOFactory.php');

$title = "MISCELLANEOUS SERVICE - SUMMARY REPORT";

$type = $_GET['type'] ?? '';
$PK_PACKAGE = $_GET['PK_PACKAGE'] ?? '';
$TRANSPORTATION_CHARGES = $_GET['TRANSPORTATION_CHARGES'] ?? 0;
$PACKAGE_COSTS = $_GET['PACKAGE_COSTS'] ?? 0;

$account_data = $db->Execute("SELECT BUSINESS_NAME, FRANCHISE FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
$business_name = $account_data->RecordCount() > 0 ? $account_data->fields['BUSINESS_NAME'] : '';

$location_name = '';
$results = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME FROM DOA_LOCATION WHERE PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND ACTIVE = 1 AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
$resultsArray = [];
while (!$results->EOF) {
    $resultsArray[] = $results->fields['LOCATION_NAME'];
    $results->MoveNext();
}
$totalResults = count($resultsArray);
$concatenatedResults = "";
foreach ($resultsArray as $key => $result) {
    $concatenatedResults .= $result;
    if ($key < $totalResults - 1) {
        $concatenatedResults .= ", ";
    }
}

// Get package name
$package = $db_account->Execute("SELECT PACKAGE_NAME FROM DOA_PACKAGE WHERE PK_PACKAGE = " . $PK_PACKAGE);
$package_name = $package->fields['PACKAGE_NAME'] ?? '';

// Create new PHPExcel object
$objPHPExcel = new PHPExcel();
$objPHPExcel->setActiveSheetIndex(0);
$sheet = $objPHPExcel->getActiveSheet();

// Set column widths
$sheet->getColumnDimension("A")->setWidth(12); // Receipt No
$sheet->getColumnDimension("B")->setWidth(12); // Date
$sheet->getColumnDimension("C")->setWidth(25); // Name of Participant
$sheet->getColumnDimension("D")->setWidth(20); // Teacher(s)
$sheet->getColumnDimension("E")->setWidth(12); // Unique ID
$sheet->getColumnDimension("F")->setWidth(15); // Total Charges Due
$sheet->getColumnDimension("G")->setWidth(15); // Amount of Payment
$sheet->getColumnDimension("H")->setWidth(15); // Reported on Week
$sheet->getColumnDimension("I")->setWidth(10); // Status

$styleArray = [
    'borders' => [
        'allborders' => [
            'style' => PHPExcel_Style_Border::BORDER_THIN,
            'color' => ['rgb' => '000000']
        ]
    ]
];

$rowNumber = 1;

// Title
$sheet->mergeCells('A1:I1');
$sheet->setCellValue('A1', $title);
$sheet->getStyle('A1')->getFont()->setSize(18);
$sheet->getStyle('A1')->getFont()->setBold(true);
$sheet->getRowDimension(1)->setRowHeight(36);
$sheet->getStyle('A1')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A1:I1')->applyFromArray($styleArray);

// Package Name
$rowNumber = 2;
$sheet->mergeCells('A2:I2');
$sheet->setCellValue('A2', $package_name);
$sheet->getStyle('A2')->getFont()->setBold(true);
$sheet->getStyle('A2')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$sheet->getStyle('A2')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A2:I2')->applyFromArray($styleArray);

// Business Name & Location
$rowNumber = 3;
$sheet->mergeCells('A3:I3');
$sheet->setCellValue('A3', ($account_data->fields['FRANCHISE'] == 1 ? 'Franchisee: ' : '') . $business_name . " (" . $concatenatedResults . ")");
$sheet->getStyle('A3')->getFont()->setBold(true);
$sheet->getStyle('A3')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$sheet->getStyle('A3')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A3:I3')->applyFromArray($styleArray);

$rowNumber = 5;

// Headers for main table
$headers = ['Receipt No.', 'Date', 'Name of Participant', 'Teacher(s)', 'Unique ID', 'Total Charges Due', 'Amount of Payment', 'Reported on Week', 'Status'];
$col = 0;
foreach ($headers as $header) {
    $sheet->setCellValueByColumnAndRow($col, $rowNumber, $header);
    $sheet->getStyleByColumnAndRow($col, $rowNumber)->getFont()->setBold(true);
    $sheet->getStyleByColumnAndRow($col, $rowNumber)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $sheet->getStyleByColumnAndRow($col, $rowNumber)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('E8F4FF');
    $col++;
}
$sheet->getStyle('A' . $rowNumber . ':I' . $rowNumber)->applyFromArray($styleArray);
$rowNumber++;

// Get data for the main table
$total = 0;
$unique_id = [];
$row = $db_account->Execute("SELECT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, TOTAL_AMOUNT, BALANCE_PAYABLE, PAYMENT_DATE, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME_OF_PARTICIPANT, DOA_ENROLLMENT_MASTER.PK_USER_MASTER, RECEIPT_NUMBER, AMOUNT, DOA_ENROLLMENT_MASTER.STATUS FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_BILLING ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_PAYMENT ON DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER=DOA_USER_MASTER.PK_USER_MASTER LEFT JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USER_MASTER.PK_USER=DOA_USERS.PK_USER WHERE DOA_ENROLLMENT_MASTER.STATUS NOT IN ('C', 'CA') AND DOA_ENROLLMENT_PAYMENT.IS_REFUNDED = 0 AND DOA_ENROLLMENT_PAYMENT.TYPE IN ('Payment', 'Adjustment') AND DOA_ENROLLMENT_MASTER.PK_PACKAGE = " . $PK_PACKAGE . " AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") ORDER BY PAYMENT_DATE ");

while (!$row->EOF) {
    $service_provider = $db->Execute("SELECT CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS TEACHER FROM $account_database.DOA_ENROLLMENT_MASTER AS DOA_ENROLLMENT_MASTER LEFT JOIN $account_database.DOA_ENROLLMENT_SERVICE_PROVIDER AS DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_USERS ON DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID=DOA_USERS.PK_USER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = " . $row->fields['PK_ENROLLMENT_MASTER']);

    $partner = $db_account->Execute("SELECT CONCAT(DOA_CUSTOMER_DETAILS.PARTNER_FIRST_NAME, ' ', DOA_CUSTOMER_DETAILS.PARTNER_LAST_NAME) AS PARTNER_NAME, ATTENDING_WITH FROM DOA_CUSTOMER_DETAILS WHERE PK_USER_MASTER = " . $row->fields['PK_USER_MASTER']);

    if (($partner->fields['ATTENDING_WITH']) == 'With a Partner') {
        $NAME = $row->fields['NAME_OF_PARTICIPANT'] . ' & ' . $partner->fields['PARTNER_NAME'];
    } else {
        $NAME = $row->fields['NAME_OF_PARTICIPANT'];
    }

    $date = $row->fields['PAYMENT_DATE'];
    $weekNumber = date("W", strtotime($date));

    if (!in_array($row->fields['PK_ENROLLMENT_MASTER'], $unique_id)) {
        $total += $row->fields['TOTAL_AMOUNT'];
        $unique_id[] = $row->fields['PK_ENROLLMENT_MASTER'];
    }

    // Add row data
    $sheet->setCellValue('A' . $rowNumber, $row->fields['RECEIPT_NUMBER']);
    $sheet->setCellValue('B' . $rowNumber, date('m-d-Y', strtotime($row->fields['PAYMENT_DATE'])));
    $sheet->setCellValue('C' . $rowNumber, $NAME);
    $sheet->setCellValue('D' . $rowNumber, $service_provider->fields['TEACHER']);
    $sheet->setCellValue('E' . $rowNumber, $row->fields['PK_ENROLLMENT_MASTER']);
    $sheet->setCellValue('F' . $rowNumber, '$' . $row->fields['TOTAL_AMOUNT']);
    $sheet->setCellValue('G' . $rowNumber, '$' . number_format($row->fields['AMOUNT'], 2));
    $sheet->setCellValue('H' . $rowNumber, '#' . $weekNumber);
    $sheet->setCellValue('I' . $rowNumber, $row->fields['STATUS']);

    $sheet->getStyle('A' . $rowNumber . ':I' . $rowNumber)->applyFromArray($styleArray);
    $rowNumber++;

    $row->MoveNext();
}

// Get total paid amount
$row = $db_account->Execute("SELECT SUM(AMOUNT) AS TOTAL_PAID_AMOUNT FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_BILLING ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_PAYMENT ON DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER=DOA_USER_MASTER.PK_USER_MASTER LEFT JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USER_MASTER.PK_USER=DOA_USERS.PK_USER WHERE DOA_ENROLLMENT_MASTER.PK_PACKAGE = " . $PK_PACKAGE . " AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ")");
$total_paid_amount = $row->fields['TOTAL_PAID_AMOUNT'] ?? 0;

// Add totals row
$sheet->setCellValue('A' . $rowNumber, '');
$sheet->setCellValue('B' . $rowNumber, '');
$sheet->setCellValue('C' . $rowNumber, '');
$sheet->setCellValue('D' . $rowNumber, '');
$sheet->setCellValue('E' . $rowNumber, 'Totals:');
$sheet->setCellValue('F' . $rowNumber, '$' . number_format($total, 2));
$sheet->setCellValue('G' . $rowNumber, '$' . number_format($total_paid_amount, 2));
$sheet->setCellValue('H' . $rowNumber, '');
$sheet->setCellValue('I' . $rowNumber, '');

$sheet->getStyle('A' . $rowNumber . ':I' . $rowNumber)->getFont()->setBold(true);
$sheet->getStyle('A' . $rowNumber . ':I' . $rowNumber)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('E8F4FF');
$sheet->getStyle('A' . $rowNumber . ':I' . $rowNumber)->applyFromArray($styleArray);

$rowNumber += 2;

// Add summary table headers
$summary_headers = ['Total Enrollment', 'Transportation Charges', 'Package Costs', 'Total Deduction', 'Total Subject to Royalty'];
$sheet->setCellValue('A' . $rowNumber, $summary_headers[0]);
$sheet->setCellValue('B' . $rowNumber, $summary_headers[1]);
$sheet->setCellValue('C' . $rowNumber, $summary_headers[2]);
$sheet->setCellValue('D' . $rowNumber, $summary_headers[3]);
$sheet->setCellValue('E' . $rowNumber, $summary_headers[4]);

for ($col = 0; $col < 5; $col++) {
    $sheet->getStyleByColumnAndRow($col, $rowNumber)->getFont()->setBold(true);
    $sheet->getStyleByColumnAndRow($col, $rowNumber)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $sheet->getStyleByColumnAndRow($col, $rowNumber)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('D4EDDA');
}
$sheet->getStyle('A' . $rowNumber . ':E' . $rowNumber)->applyFromArray($styleArray);
$rowNumber++;

// Calculate summary values
$TOTAL_DEDUCTION = $PACKAGE_COSTS + $TRANSPORTATION_CHARGES;
$TOTAL_SUBJECT_TO_ROYALTY = $total - $TOTAL_DEDUCTION;

// Add summary data
$sheet->setCellValue('A' . $rowNumber, '$' . number_format($total, 2));
$sheet->setCellValue('B' . $rowNumber, '$' . number_format($TRANSPORTATION_CHARGES, 2));
$sheet->setCellValue('C' . $rowNumber, '$' . number_format($PACKAGE_COSTS, 2));
$sheet->setCellValue('D' . $rowNumber, '$' . number_format($TOTAL_DEDUCTION, 2));
$sheet->setCellValue('E' . $rowNumber, '$' . number_format($TOTAL_SUBJECT_TO_ROYALTY, 2));

$sheet->getStyle('A' . $rowNumber . ':E' . $rowNumber)->getFont()->setBold(true);
$sheet->getStyle('A' . $rowNumber . ':E' . $rowNumber)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A' . $rowNumber . ':E' . $rowNumber)->applyFromArray($styleArray);

// Save the file
$outputFileName = 'MISCELLANEOUS_SERVICE_REPORT_' . date('Y-m-d') . '.xlsx';
$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

// Clear any previous output
if (ob_get_length()) {
    ob_clean();
}

// Set headers for download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $outputFileName . '"');
header('Cache-Control: max-age=0');
header('Cache-Control: max-age=1');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: cache, must-revalidate');
header('Pragma: public');

$objWriter->save('php://output');
exit;
