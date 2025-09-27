<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;
global $account_database;
error_reporting(0);

include('../global/excel/Classes/PHPExcel/IOFactory.php');

$title = "CASH REPORT";

$query = '';
$selected_range = '';
$selected_date = '';
$type = $_GET['type'];
$from_date = date('Y-m-d', strtotime($_GET['start_date']));
$to_date = date('Y-m-d', strtotime($_GET['end_date']));
$service_provider_id = $_GET['service_provider_id'];

$payment_date = "AND DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (" . $service_provider_id . ") AND DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE BETWEEN '" . date('Y-m-d', strtotime($from_date)) . "' AND '" . date('Y-m-d', strtotime($to_date)) . "' GROUP BY SERVICE_PROVIDER_ID ORDER BY DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE DESC";

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
    // Append the current result to the concatenated string
    $concatenatedResults .= $result;

    // If it's not the last result, append a comma
    if ($key < $totalResults - 1) {
        $concatenatedResults .= ", ";
    }
}

$cell1  = array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z");
define('EOL', (PHP_SAPI == 'cli') ? PHP_EOL : '<br />');

$total_fields = 70;
for ($i = 0; $i <= $total_fields; $i++) {
    if ($i <= 25)
        $cell[] = $cell1[$i];
    else {
        $j = floor($i / 26) - 1;
        $k = ($i % 26);
        //echo $j."--".$k."<br />";
        $cell[] = $cell1[$j] . $cell1[$k];
    }
}

$inputFileType  = 'Excel2007';
$outputFileName = 'ACTIVE_ACCOUNT_BALANCE_REPORT.xlsx';

$objReader      = PHPExcel_IOFactory::createReader($inputFileType);
$objReader->setIncludeCharts(TRUE);
$objPHPExcel     = new PHPExcel();
$objWriter         = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

$objPHPExcel->getActiveSheet()->getColumnDimension("A")->setWidth(12);
$objPHPExcel->getActiveSheet()->getColumnDimension("B")->setWidth(15);
$objPHPExcel->getActiveSheet()->getColumnDimension("C")->setWidth(12);
$objPHPExcel->getActiveSheet()->getColumnDimension("D")->setWidth(20);
$objPHPExcel->getActiveSheet()->getColumnDimension("E")->setWidth(20);
$objPHPExcel->getActiveSheet()->getColumnDimension("F")->setWidth(12);
$objPHPExcel->getActiveSheet()->getColumnDimension("G")->setWidth(25);
$objPHPExcel->getActiveSheet()->getColumnDimension("H")->setWidth(20);
$objPHPExcel->getActiveSheet()->getColumnDimension("I")->setWidth(12);
$objPHPExcel->getActiveSheet()->getColumnDimension("J")->setWidth(12);
$objPHPExcel->getActiveSheet()->getColumnDimension("K")->setWidth(20);

$objPHPExcel->getActiveSheet()->mergeCells('A1:K1');

$styleArray = [
    'borders' => [
        'allborders' => [
            'style' => PHPExcel_Style_Border::BORDER_THIN,
            'color' => ['rgb' => '000000']
        ]
    ]
];

$cell_no = "A1";
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($title);
$objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setSize(18); // Set font size to 16
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getRowDimension(1)->setRowHeight(36);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle('A1:K1')->applyFromArray($styleArray);

$cell_no = "A2";
$objPHPExcel->getActiveSheet()->mergeCells('A2:G2');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($business_name . " (" . $concatenatedResults . ")");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "H2";
$objPHPExcel->getActiveSheet()->mergeCells('H2:K2');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue(date('m/d/Y', strtotime($from_date)) . ' - ' . date('m/d/Y', strtotime($to_date)));
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$objPHPExcel->getActiveSheet()->getStyle('A2:K2')->applyFromArray($styleArray);

$rowNumber = 5; // Start data at row 4
$borderRows = [];
$total_refund = 0; // Reset for each service provider
$total_portion = 0;
$each_service_provider = $db_account->Execute("SELECT distinct DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID, DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_PERCENTAGE, DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, PAYMENT_DATE, AMOUNT, PAYMENT_INFO, PAYMENT_TYPE, RECEIPT_NUMBER, MEMO, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS CLIENT, ENROLLMENT_NAME, ENROLLMENT_ID, ENROLLMENT_TYPE, TOTAL_AMOUNT, ENROLLMENT_BY_ID FROM DOA_ENROLLMENT_PAYMENT INNER JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER INNER JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER INNER JOIN $master_database.DOA_PAYMENT_TYPE AS DOA_PAYMENT_TYPE ON DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE=DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE INNER JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER=DOA_USER_MASTER.PK_USER_MASTER INNER JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USER_MASTER.PK_USER=DOA_USERS.PK_USER INNER JOIN $master_database.DOA_ENROLLMENT_TYPE AS DOA_ENROLLMENT_TYPE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE=DOA_ENROLLMENT_TYPE.PK_ENROLLMENT_TYPE INNER JOIN DOA_ENROLLMENT_BILLING ON DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") " . $payment_date);
while (!$each_service_provider->EOF) {
    $name = $db->Execute("SELECT CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS TEACHER FROM DOA_USERS WHERE DOA_USERS.PK_USER = " . $each_service_provider->fields['SERVICE_PROVIDER_ID']);
    $service_provider_id_per_table = $each_service_provider->fields['SERVICE_PROVIDER_ID'];

    // Initialize totals for EACH service provider
    $total_portion = 0;
    $total_refund = 0;

    // Write service provider name (merged across all columns)
    $objPHPExcel->getActiveSheet()
        ->setCellValue('A' . $rowNumber, $name->fields['TEACHER'])
        ->mergeCells('A' . $rowNumber . ':K' . $rowNumber);

    // Style the service provider name with borders
    $objPHPExcel->getActiveSheet()
        ->getStyle('A' . $rowNumber . ':K' . $rowNumber)
        ->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12
            ],
            'alignment' => [
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER
            ],
            'borders' => [
                'allborders' => [
                    'style' => PHPExcel_Style_Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ]);

    $rowNumber++; // Move to next row for headers
    $borderRows[] = $rowNumber; // Add header row to border array

    // Write headers
    $objPHPExcel->getActiveSheet()
        ->setCellValue('A' . $rowNumber, 'Receipt#')
        ->setCellValue('B' . $rowNumber, 'Payment Date')
        ->setCellValue('C' . $rowNumber, 'Amount')
        ->setCellValue('D' . $rowNumber, 'Student Name')
        ->setCellValue('E' . $rowNumber, 'Type')
        ->setCellValue('F' . $rowNumber, 'ENR ID')
        ->setCellValue('G' . $rowNumber, 'Enrollment')
        ->setCellValue('H' . $rowNumber, 'Enrollment Type')
        ->setCellValue('I' . $rowNumber, 'Units/Total Cost')
        ->setCellValue('J' . $rowNumber, 'Portion')
        ->setCellValue('K' . $rowNumber, '%')
        ->setCellValue('L' . $rowNumber, 'Comment/Remark');

    // Style headers - bold and center-aligned
    $headerStyle = [
        'font' => [
            'bold' => true
        ],
        'alignment' => [
            'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER
        ]
    ];
    $objPHPExcel->getActiveSheet()
        ->getStyle('A' . $rowNumber . ':K' . $rowNumber)
        ->applyFromArray($headerStyle);

    $rowNumber++;

    $row = $db_account->Execute("SELECT DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID, DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_PERCENTAGE, DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, DOA_ENROLLMENT_PAYMENT.TYPE, PAYMENT_DATE, AMOUNT, PAYMENT_INFO, PAYMENT_TYPE, RECEIPT_NUMBER, MEMO, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS CLIENT, ENROLLMENT_NAME, ENROLLMENT_ID, ENROLLMENT_TYPE, TOTAL_AMOUNT, ENROLLMENT_BY_ID FROM DOA_ENROLLMENT_PAYMENT INNER JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER INNER JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER INNER JOIN $master_database.DOA_PAYMENT_TYPE AS DOA_PAYMENT_TYPE ON DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE=DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE INNER JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER=DOA_USER_MASTER.PK_USER_MASTER INNER JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USER_MASTER.PK_USER=DOA_USERS.PK_USER INNER JOIN $master_database.DOA_ENROLLMENT_TYPE AS DOA_ENROLLMENT_TYPE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE=DOA_ENROLLMENT_TYPE.PK_ENROLLMENT_TYPE INNER JOIN DOA_ENROLLMENT_BILLING ON DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (" . $service_provider_id_per_table . ") AND DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE BETWEEN '" . date('Y-m-d', strtotime($from_date)) . "' AND '" . date('Y-m-d', strtotime($to_date)) . "' ORDER BY DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE DESC");
    while (!$row->EOF) {
        $sessions = $db_account->Execute("SELECT NUMBER_OF_SESSION FROM DOA_ENROLLMENT_SERVICE WHERE PK_ENROLLMENT_MASTER = " . $row->fields['PK_ENROLLMENT_MASTER']);
        $units = $sessions->fields['NUMBER_OF_SESSION'] ?? 0;
        $service_provider = $db->Execute("SELECT CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS TEACHER FROM DOA_USERS WHERE DOA_USERS.PK_USER = " . $row->fields['SERVICE_PROVIDER_ID']);
        $portion = $row->fields['AMOUNT'] * ($row->fields['SERVICE_PROVIDER_PERCENTAGE'] / 100);
        $total_portion += $portion; // Add to the sum
        $total_amount += $row->fields['AMOUNT'];

        // Check if it's a refund and add to refund total
        if ($row->fields['TYPE'] == 'Refund') {
            $total_refund += $row->fields['AMOUNT'] * ($row->fields['SERVICE_PROVIDER_PERCENTAGE'] / 100);
            $total_refund_amount += $row->fields['AMOUNT'];
        }

        // Write data row - REGULAR PAYMENT
        $objPHPExcel->getActiveSheet()
            ->setCellValue('A' . $rowNumber, $row->fields['RECEIPT_NUMBER'])
            ->setCellValue('B' . $rowNumber, date('m-d-Y', strtotime($row->fields['PAYMENT_DATE'])))
            ->setCellValue('C' . $rowNumber, '$' . $row->fields['AMOUNT'])
            ->setCellValue('D' . $rowNumber, $row->fields['CLIENT'])
            ->setCellValue('E' . $rowNumber, $row->fields['PAYMENT_TYPE'])
            ->setCellValue('F' . $rowNumber, $row->fields['ENROLLMENT_ID'])
            ->setCellValue('G' . $rowNumber, $row->fields['ENROLLMENT_NAME'])
            ->setCellValue('H' . $rowNumber, $row->fields['ENROLLMENT_TYPE'])
            ->setCellValue('I' . $rowNumber, $units . '/$' . $row->fields['TOTAL_AMOUNT'])
            ->setCellValue('J' . $rowNumber, '$' . number_format($portion, 2))
            ->setCellValue('K' . $rowNumber, number_format($row->fields['SERVICE_PROVIDER_PERCENTAGE'], 0))
            ->setCellValue('L' . $rowNumber, ' ');

        // Apply center alignment
        $objPHPExcel->getActiveSheet()
            ->getStyle('A' . $rowNumber . ':J' . $rowNumber)
            ->getAlignment()
            ->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        // Apply specific right alignment to columns C and I
        $objPHPExcel->getActiveSheet()
            ->getStyle('C' . $rowNumber)
            ->getAlignment()
            ->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

        $objPHPExcel->getActiveSheet()
            ->getStyle('I' . $rowNumber)
            ->getAlignment()
            ->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

        // Add data row to border array
        $borderRows[] = $rowNumber;
        $rowNumber++; // MOVE TO NEXT ROW FOR REFUND DATA

        // If it's a refund, create a SECOND ROW with refund details
        if ($row->fields['TYPE'] == 'Refund') {
            $objPHPExcel->getActiveSheet()
                ->setCellValue('A' . $rowNumber, $row->fields['RECEIPT_NUMBER'])
                ->setCellValue('B' . $rowNumber, date('m-d-Y', strtotime($row->fields['PAYMENT_DATE'])))
                ->setCellValue('C' . $rowNumber, '$' . $row->fields['AMOUNT'])
                ->setCellValue('D' . $rowNumber, $row->fields['CLIENT'])
                ->setCellValue('E' . $rowNumber, '(Refund) ' . $row->fields['PAYMENT_TYPE'])
                ->setCellValue('F' . $rowNumber, $row->fields['ENROLLMENT_ID'])
                ->setCellValue('G' . $rowNumber, $row->fields['ENROLLMENT_NAME'])
                ->setCellValue('H' . $rowNumber, $row->fields['ENROLLMENT_TYPE'])
                ->setCellValue('I' . $rowNumber, $units . '/$' . $row->fields['TOTAL_AMOUNT'])
                ->setCellValue('J' . $rowNumber, '$' . number_format($portion, 2))
                ->setCellValue('K' . $rowNumber, number_format($row->fields['SERVICE_PROVIDER_PERCENTAGE'], 0))
                ->setCellValue('L' . $rowNumber, ' ');

            // Set the entire refund row's font color to red
            $objPHPExcel->getActiveSheet()
                ->getStyle('A' . $rowNumber . ':K' . $rowNumber)
                ->getFont()
                ->setColor(new PHPExcel_Style_Color(PHPExcel_Style_Color::COLOR_RED));

            // Apply center alignment to refund row
            $objPHPExcel->getActiveSheet()
                ->getStyle('A' . $rowNumber . ':J' . $rowNumber)
                ->getAlignment()
                ->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

            // Apply specific right alignment to columns C and I in refund row
            $objPHPExcel->getActiveSheet()
                ->getStyle('C' . $rowNumber)
                ->getAlignment()
                ->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

            $objPHPExcel->getActiveSheet()
                ->getStyle('I' . $rowNumber)
                ->getAlignment()
                ->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

            // Add refund row to border array
            $borderRows[] = $rowNumber;
            $rowNumber++; // MOVE TO NEXT ROW AFTER REFUND
        }

        $row->MoveNext();
    }

    // Add total row
    $objPHPExcel->getActiveSheet()
        ->setCellValue('I' . $rowNumber, '$' . number_format($total_portion - $total_refund, 2));

    // Apply styling to the total portion cell
    $objPHPExcel->getActiveSheet()
        ->getStyle('I' . $rowNumber)
        ->applyFromArray([
            'alignment' => [
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT
            ],
            'font' => [
                'bold' => true
            ],
            'borders' => [
                'allborders' => [
                    'style' => PHPExcel_Style_Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ]);

    $borderRows[] = $rowNumber;

    // Add blank rows between service providers
    $rowNumber += 2;
    $each_service_provider->MoveNext();
}



// Apply borders only to header and data rows
$borderStyle = [
    'borders' => [
        'allborders' => [
            'style' => PHPExcel_Style_Border::BORDER_THIN,
            'color' => ['rgb' => '000000']
        ]
    ]
];

foreach ($borderRows as $row) {
    $objPHPExcel->getActiveSheet()->getStyle('A' . $row . ':K' . $row)->applyFromArray($borderStyle);
}


$objWriter->save($outputFileName);
$objPHPExcel->disconnectWorksheets();
header("location:" . $outputFileName);
