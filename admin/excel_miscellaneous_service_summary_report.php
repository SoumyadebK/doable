<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;
global $account_database;
error_reporting(0);

include ('../global/excel/Classes/PHPExcel/IOFactory.php');

$title = "MISCELLANEOUS SERVICE - SUMMARY REPORT";

$PK_PACKAGE = $_GET['PK_PACKAGE'];
$TRANSPORTATION_CHARGES = $_GET['TRANSPORTATION_CHARGES'];
$PACKAGE_COSTS = $_GET['PACKAGE_COSTS'];

$account_data = $db->Execute("SELECT BUSINESS_NAME, FRANCHISE FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
$business_name = $account_data->RecordCount() > 0 ? $account_data->fields['BUSINESS_NAME'] : '';

$cell1  = array("A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z");
define('EOL',(PHP_SAPI == 'cli') ? PHP_EOL : '<br />');

$total_fields = 70;
for($i = 0 ; $i <= $total_fields ; $i++){
    if($i <= 25)
        $cell[] = $cell1[$i];
    else {
        $j = floor($i / 26) - 1;
        $k = ($i % 26);
        //echo $j."--".$k."<br />";
        $cell[] = $cell1[$j].$cell1[$k];
    }
}

$inputFileType  = 'Excel2007';
$outputFileName = 'MISCELLANEOUS_SERVICE_SUMMARY_REPORT.xlsx';

$objReader      = PHPExcel_IOFactory::createReader($inputFileType);
$objReader->setIncludeCharts(TRUE);
$objPHPExcel 	= new PHPExcel();
$objWriter     	= PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

$objPHPExcel->getActiveSheet()->getColumnDimension("A")->setWidth(12);
$objPHPExcel->getActiveSheet()->getColumnDimension("B")->setWidth(12);
$objPHPExcel->getActiveSheet()->getColumnDimension("C")->setWidth(12);
$objPHPExcel->getActiveSheet()->getColumnDimension("D")->setWidth(12);
$objPHPExcel->getActiveSheet()->getColumnDimension("E")->setWidth(12);
$objPHPExcel->getActiveSheet()->getColumnDimension("F")->setWidth(12);
$objPHPExcel->getActiveSheet()->getColumnDimension("G")->setWidth(12);
$objPHPExcel->getActiveSheet()->getColumnDimension("H")->setWidth(12);
$objPHPExcel->getActiveSheet()->getColumnDimension("I")->setWidth(12);
$objPHPExcel->getActiveSheet()->getColumnDimension("J")->setWidth(12);
$objPHPExcel->getActiveSheet()->getColumnDimension("K")->setWidth(12);

$objPHPExcel->getActiveSheet()->mergeCells('A1:Q1');

$cell_no = "A1";
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($title);
$objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setSize(18); // Set font size to 16
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getRowDimension(1)->setRowHeight(36);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

$objPHPExcel->getActiveSheet()->getRowDimension(2)->setRowHeight(20);
$cell_no = "A2";
$objPHPExcel->getActiveSheet()->mergeCells('A2:H2');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($business_name);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setWrapText(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "I2";
$objPHPExcel->getActiveSheet()->mergeCells('I2:M2');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue('('.date('m/d/Y', strtotime($from_date)).' - '.date('m/d/Y', strtotime($to_date)).')');
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "N2";
$objPHPExcel->getActiveSheet()->mergeCells('N2:Q2');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Week # ".$week_number);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$styleArray = [
    'borders' => [
        'allborders' => [
            'style' => PHPExcel_Style_Border::BORDER_THIN,
            'color' => ['rgb' => '000000']
        ]
    ]
];
$objPHPExcel->getActiveSheet()->getStyle('A2:K2')->applyFromArray($styleArray);

$cell_no = "A3";
//$objPHPExcel->getActiveSheet()->mergeCells('A1:A1');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Receipt No.");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

$cell_no = "B3";
//$objPHPExcel->getActiveSheet()->mergeCells('A1:A1');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Date");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

$cell_no = "C3";
//$objPHPExcel->getActiveSheet()->mergeCells('A1:A1');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Name of Participant");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

$cell_no = "D3";
//$objPHPExcel->getActiveSheet()->mergeCells('A1:A1');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Teacher(s)");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

$cell_no = "E3";
//$objPHPExcel->getActiveSheet()->mergeCells('A1:A1');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Unique ID");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

$cell_no = "F3";
//$objPHPExcel->getActiveSheet()->mergeCells('A1:A1');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Total Charges Due");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

$cell_no = "G3";
//$objPHPExcel->getActiveSheet()->mergeCells('A1:A1');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Amount of Payment");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

$cell_no = "H3";
//$objPHPExcel->getActiveSheet()->mergeCells('A1:A1');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Reported on Week");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

$cell_no = "I3";
//$objPHPExcel->getActiveSheet()->mergeCells('A1:A1');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Enrollment Name");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

$cell_no = "J3";
//$objPHPExcel->getActiveSheet()->mergeCells('A1:A1');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Enrollment Date");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

$cell_no = "K3";
//$objPHPExcel->getActiveSheet()->mergeCells('A1:A1');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Enrollment Type");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

$cell_no = "L3";
//$objPHPExcel->getActiveSheet()->mergeCells('A1:A1');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Enrollment Cost");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

$cell_no = "M3";
//$objPHPExcel->getActiveSheet()->mergeCells('A1:A1');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Enrollment Balance");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

$cell_no = "N3";
//$objPHPExcel->getActiveSheet()->mergeCells('A1:A1');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Closer");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

$cell_no = "O3";
//$objPHPExcel->getActiveSheet()->mergeCells('A1:A1');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Teacher1");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

$cell_no = "P3";
//$objPHPExcel->getActiveSheet()->mergeCells('A1:A1');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Teacher2");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

$cell_no = "Q3";
//$objPHPExcel->getActiveSheet()->mergeCells('A1:A1');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Teacher3");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);


$i=4;
$row = $db_account->Execute("SELECT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, PAYMENT_DATE, AMOUNT, PAYMENT_INFO, PAYMENT_TYPE, RECEIPT_NUMBER, MEMO, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS CLIENT, ENROLLMENT_NAME, ENROLLMENT_DATE, ENROLLMENT_TYPE, FINAL_AMOUNT, TOTAL_AMOUNT_PAID, ENROLLMENT_BY_ID FROM DOA_ENROLLMENT_PAYMENT LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN $master_database.DOA_PAYMENT_TYPE AS DOA_PAYMENT_TYPE ON DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE=DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER=DOA_USER_MASTER.PK_USER_MASTER LEFT JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USER_MASTER.PK_USER=DOA_USERS.PK_USER LEFT JOIN $master_database.DOA_ENROLLMENT_TYPE AS DOA_ENROLLMENT_TYPE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE=DOA_ENROLLMENT_TYPE.PK_ENROLLMENT_TYPE LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") ".$enrollment_date);
while (!$row->EOF) {
    $enrollment_by = $db->Execute("SELECT CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS CLOSER FROM DOA_USERS WHERE PK_USER = ".$row->fields['ENROLLMENT_BY_ID']);
    $service_provider = $db->Execute("SELECT CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS TEACHER FROM $account_database.DOA_ENROLLMENT_MASTER AS DOA_ENROLLMENT_MASTER LEFT JOIN $account_database.DOA_ENROLLMENT_SERVICE_PROVIDER AS DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_USERS ON DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID=DOA_USERS.PK_USER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = ".$row->fields['PK_ENROLLMENT_MASTER']);

    $cell_no = "A".$i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($row->fields['PAYMENT_DATE']);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

    $cell_no = "B".$i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($row->fields['AMOUNT']);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

    $cell_no = "C".$i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($row->fields['PAYMENT_INFO']);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

    $cell_no = "D".$i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($row->fields['PAYMENT_TYPE']);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

    $cell_no = "E".$i;
    $card_type = ($row->fields['PAYMENT_TYPE'] == 'Credit Card' || $row->fields['PAYMENT_TYPE'] == 'Visa' || $row->fields['PAYMENT_TYPE'] == 'Master Card' || $row->fields['PAYMENT_TYPE'] == 'American Express' || $row->fields['PAYMENT_TYPE'] == 'Card' || $row->fields['PAYMENT_TYPE'] == 'Card On File') ? $row->fields['PAYMENT_TYPE'] : '';
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($card_type);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

    $cell_no = "F".$i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($row->fields['RECEIPT_NUMBER']);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

    $cell_no = "G".$i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($row->fields['MEMO']);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

    $cell_no = "H".$i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($row->fields['CLIENT']);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

    $cell_no = "I".$i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($row->fields['ENROLLMENT_NAME']);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

    $cell_no = "J".$i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($row->fields['ENROLLMENT_DATE']);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

    $cell_no = "K".$i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($row->fields['ENROLLMENT_TYPE']);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

    $cell_no = "L".$i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($row->fields['FINAL_AMOUNT']);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

    $cell_no = "M".$i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue(number_format($row->fields['FINAL_AMOUNT'] - $row->fields['TOTAL_AMOUNT_PAID'], 2));
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

    $cell_no = "N".$i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($enrollment_by->fields['CLOSER']);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

    if($service_provider->RecordCount() > 0) {
        $j = 1;
        while (!$service_provider->EOF) {
            if ($j == 1) {
                $cell_no = "O".$i;
                $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($service_provider->fields['TEACHER']);
                $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            }
            if ($j == 2) {
                $cell_no = "P".$i;
                $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($service_provider->fields['TEACHER']);
                $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            }
            if ($j == 3) {
                $cell_no = "Q".$i;
                $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($service_provider->fields['TEACHER']);
                $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            }
            $service_provider->MoveNext();
            $j++;
        }
    }
    $row->MoveNext();
    $i++;
}

$objWriter->save($outputFileName);
$objPHPExcel->disconnectWorksheets();
header("location:".$outputFileName);