<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;
global $account_database;
error_reporting(0);

include ('../global/excel/Classes/PHPExcel/IOFactory.php');

$title = "ACTIVE ACCOUNT BALANCE REPORT";

$query = '';
$selected_range = '';
$selected_date = '';

if(!empty($_GET['selected_range'])) {
    $selected_range = $_GET['selected_range'];
    $selected_date = date('Y-m-d', strtotime($_GET['selected_date']));
    $query = "SELECT DISTINCT DOA_APPOINTMENT_CUSTOMER.PK_USER_MASTER FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_APPOINTMENT_CUSTOMER ON DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_USER_MASTER.PK_USER_MASTER = DOA_APPOINTMENT_CUSTOMER.PK_USER_MASTER LEFT JOIN DOA_MASTER.DOA_USERS AS DOA_USERS ON DOA_USER_MASTER.PK_USER = DOA_USERS.PK_USER WHERE DOA_USERS.ACTIVE =1 AND DOA_USERS.IS_DELETED = 0 AND DOA_APPOINTMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ")  AND DOA_APPOINTMENT_MASTER.DATE >= DATE_SUB('".$selected_date."', INTERVAL ".$selected_range." MONTH) AND DOA_APPOINTMENT_MASTER.DATE <= '".$selected_date."'";
} else {
    $selected_date = date('Y-m-d', strtotime($_GET['selected_date']));
    $query = "SELECT DISTINCT DOA_ENROLLMENT_MASTER.PK_USER_MASTER FROM DOA_ENROLLMENT_MASTER LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_USER_MASTER.PK_USER_MASTER = DOA_ENROLLMENT_MASTER.PK_USER_MASTER LEFT JOIN DOA_MASTER.DOA_USERS AS DOA_USERS ON DOA_USER_MASTER.PK_USER = DOA_USERS.PK_USER WHERE DOA_USERS.ACTIVE =1 AND DOA_USERS.IS_DELETED = 0 AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_ENROLLMENT_MASTER.ENROLLMENT_DATE = '" . date('Y-m-d', strtotime($selected_date)) . "'";
}

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
$outputFileName = 'ACTIVE_ACCOUNT_BALANCE_REPORT.xlsx';

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

$objPHPExcel->getActiveSheet()->mergeCells('A1:H1');

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
$objPHPExcel->getActiveSheet()->getStyle('A1:H1')->applyFromArray($styleArray);

$cell_no = "A2";
$objPHPExcel->getActiveSheet()->mergeCells('A2:H2');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue(!empty($selected_range) ? "Range ".$selected_range." Month" : "Date ".date('m-d-Y', strtotime($selected_date)));
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$objPHPExcel->getActiveSheet()->getStyle('A2:H2')->applyFromArray($styleArray);

$service_data = $db_account->Execute($query);
$rowNumber = 4; // Start data at row 3
while (!$service_data->EOF) {
    $customer = $db->Execute("SELECT DOA_USERS.PK_USER, DOA_USER_MASTER.PK_USER_MASTER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS CUSTOMER_NAME FROM DOA_USERS LEFT JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER WHERE DOA_USER_MASTER.PK_USER_MASTER = " . $service_data->fields['PK_USER_MASTER']);

    // Write headers
    $objPHPExcel->getActiveSheet()
        ->setCellValue('A'.$rowNumber, 'Customer Name')
        ->setCellValue('B'.$rowNumber, 'Service Code')
        ->setCellValue('C'.$rowNumber, 'Enroll')
        ->setCellValue('D'.$rowNumber, 'Used')
        ->setCellValue('E'.$rowNumber, 'Scheduled')
        ->setCellValue('F'.$rowNumber, 'Remain')
        ->setCellValue('G'.$rowNumber, 'Balance')
        ->setCellValue('H'.$rowNumber, 'Paid');
    
    // Style headers
    $objPHPExcel->getActiveSheet()
        ->getStyle('A'.$rowNumber.':H'.$rowNumber)
        ->getFont()->setBold(true);
    
    $rowNumber++;


    $pending_service_data = $db_account->Execute("SELECT DOA_ENROLLMENT_SERVICE.*, DOA_SERVICE_CODE.SERVICE_CODE, DOA_ENROLLMENT_MASTER.CHARGE_TYPE, DOA_ENROLLMENT_MASTER.PK_USER_MASTER FROM DOA_ENROLLMENT_SERVICE LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER JOIN DOA_SERVICE_CODE ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE WHERE (DOA_ENROLLMENT_MASTER.STATUS = 'CA' || DOA_ENROLLMENT_MASTER.STATUS = 'A') AND DOA_ENROLLMENT_MASTER.PK_USER_MASTER = " . $service_data->fields['PK_USER_MASTER']);
    $pending_service_code_array = [];
    while (!$pending_service_data->EOF) {
        if ($pending_service_data->fields['CHARGE_TYPE'] == 'Membership') {
            $NUMBER_OF_SESSION = getSessionCreatedCount($pending_service_data->fields['PK_ENROLLMENT_SERVICE']);
        } else {
            $NUMBER_OF_SESSION = $pending_service_data->fields['NUMBER_OF_SESSION'];
        }
        $SESSION_SCHEDULED = getSessionScheduledCount($pending_service_data->fields['PK_ENROLLMENT_SERVICE']);
        $SESSION_COMPLETED = getSessionCompletedCount($pending_service_data->fields['PK_ENROLLMENT_SERVICE']);
        $PRICE_PER_SESSION = $pending_service_data->fields['PRICE_PER_SESSION'];
        $paid_session = ($PRICE_PER_SESSION > 0) ? number_format($pending_service_data->fields['TOTAL_AMOUNT_PAID'] / $PRICE_PER_SESSION, 2) : $NUMBER_OF_SESSION;
        $remain_session = $NUMBER_OF_SESSION - ($SESSION_COMPLETED + $SESSION_SCHEDULED);
        $ps_balance = $paid_session - $SESSION_COMPLETED;

        //if ($remain_session > 0) {
        if (isset($pending_service_code_array[$pending_service_data->fields['SERVICE_CODE']])) {
            $pending_service_code_array[$pending_service_data->fields['SERVICE_CODE']]['CODE'] = $pending_service_data->fields['SERVICE_CODE'];
            $pending_service_code_array[$pending_service_data->fields['SERVICE_CODE']]['ENROLL'] += $NUMBER_OF_SESSION;
            $pending_service_code_array[$pending_service_data->fields['SERVICE_CODE']]['REMAIN'] += $remain_session;
            $pending_service_code_array[$pending_service_data->fields['SERVICE_CODE']]['PAID'] += $pending_service_data->fields['TOTAL_AMOUNT_PAID'];
            $pending_service_code_array[$pending_service_data->fields['SERVICE_CODE']]['USED'] += $SESSION_COMPLETED;
            $pending_service_code_array[$pending_service_data->fields['SERVICE_CODE']]['SCHEDULED'] += $SESSION_SCHEDULED;
            $pending_service_code_array[$pending_service_data->fields['SERVICE_CODE']]['BALANCE'] += $ps_balance;
        } else {
            $pending_service_code_array[$pending_service_data->fields['SERVICE_CODE']]['CODE'] = $pending_service_data->fields['SERVICE_CODE'];
            $pending_service_code_array[$pending_service_data->fields['SERVICE_CODE']]['ENROLL'] = $NUMBER_OF_SESSION;
            $pending_service_code_array[$pending_service_data->fields['SERVICE_CODE']]['REMAIN'] = $remain_session;
            $pending_service_code_array[$pending_service_data->fields['SERVICE_CODE']]['PAID'] = $pending_service_data->fields['TOTAL_AMOUNT_PAID'];
            $pending_service_code_array[$pending_service_data->fields['SERVICE_CODE']]['USED'] = $SESSION_COMPLETED;
            $pending_service_code_array[$pending_service_data->fields['SERVICE_CODE']]['SCHEDULED'] = $SESSION_SCHEDULED;
            $pending_service_code_array[$pending_service_data->fields['SERVICE_CODE']]['BALANCE'] = $ps_balance;
        }
        //}
        
        $pending_service_data->MoveNext();
    }
    foreach ($pending_service_code_array as $service_code) {
            // Write data row
            $objPHPExcel->getActiveSheet()
                ->setCellValue('A'.$rowNumber, $customer->fields['CUSTOMER_NAME'])
                ->setCellValue('B'.$rowNumber, $service_code['CODE'])
                ->setCellValue('C'.$rowNumber, $service_code['ENROLL'])
                ->setCellValue('D'.$rowNumber, $service_code['USED'])
                ->setCellValue('E'.$rowNumber, $service_code['SCHEDULED'])
                ->setCellValue('F'.$rowNumber, $service_code['REMAIN'])
                ->setCellValue('G'.$rowNumber, $service_code['BALANCE'])
                ->setCellValue('H'.$rowNumber, '$'.number_format($service_code['PAID'], 2));
            
            $rowNumber++;
        }
$service_data->MoveNext();
}

// Apply borders
$borderStyle = [
    'borders' => [
        'allborders' => [
            'style' => PHPExcel_Style_Border::BORDER_THIN,
            'color' => ['rgb' => '000000']
        ]
    ]
];
$objPHPExcel->getActiveSheet()->getStyle('A3:H'.($rowNumber-1))->applyFromArray($borderStyle);

$objWriter->save($outputFileName);
$objPHPExcel->disconnectWorksheets();
header("location:".$outputFileName);