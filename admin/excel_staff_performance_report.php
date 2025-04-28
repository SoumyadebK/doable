<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;
global $account_database;
error_reporting(0);

include ('../global/excel/Classes/PHPExcel/IOFactory.php');

$title = "STAFF PERFORMANCE REPORT";

$week_number = '';
$from_date = '';
$to_date = '';

if (!empty($_GET['week_number'])){
    $week_number = $_GET['week_number'];
    $YEAR = date('Y');

    $from_date = date('Y-m-d', strtotime($_GET['start_date']));
    $to_date = date('Y-m-d', strtotime($from_date. ' +6 day'));

    $enrollment_date = "AND DOA_ENROLLMENT_MASTER.ENROLLMENT_DATE BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."'";
    $appointment_date = "AND DOA_APPOINTMENT_MASTER.DATE BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."'";
}

$account_data = $db->Execute("SELECT BUSINESS_NAME, FRANCHISE FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
$business_name = $account_data->RecordCount() > 0 ? $account_data->fields['BUSINESS_NAME'] : '';


$location_name='';
$results = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME FROM DOA_LOCATION WHERE PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND ACTIVE = 1 AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
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

$executive_data = $db_account->Execute("SELECT DISTINCT(ENROLLMENT_BY_ID) AS ENROLLMENT_BY_ID FROM DOA_ENROLLMENT_MASTER WHERE PK_ENROLLMENT_MASTER > 0 $enrollment_date");
$executive_id = [];
while (!$executive_data->EOF) {
    $executive_id[] = $executive_data->fields['ENROLLMENT_BY_ID'];
    $executive_data->MoveNext();
}


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
$outputFileName = 'STAFF_PERFORMANCE_REPORT.xlsx';

$objReader      = PHPExcel_IOFactory::createReader($inputFileType);
$objReader->setIncludeCharts(TRUE);
$objPHPExcel 	= new PHPExcel();
$objWriter     	= PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

$objPHPExcel->getActiveSheet()->getColumnDimension("A")->setWidth(18);
$objPHPExcel->getActiveSheet()->getColumnDimension("B")->setWidth(18);
$objPHPExcel->getActiveSheet()->getColumnDimension("C")->setWidth(18);
$objPHPExcel->getActiveSheet()->getColumnDimension("D")->setWidth(18);
$objPHPExcel->getActiveSheet()->getColumnDimension("E")->setWidth(18);
$objPHPExcel->getActiveSheet()->getColumnDimension("F")->setWidth(18);
$objPHPExcel->getActiveSheet()->getColumnDimension("G")->setWidth(18);
$objPHPExcel->getActiveSheet()->getColumnDimension("H")->setWidth(18);
$objPHPExcel->getActiveSheet()->getColumnDimension("I")->setWidth(18);

$cell_no = "A1";
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($title);
$objPHPExcel->getActiveSheet()->mergeCells('A1:I1');
$objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setSize(18); // Set font size to 16
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
//$objPHPExcel->getActiveSheet()->getRowDimension(1)->setRowHeight(36);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

$objPHPExcel->getActiveSheet()->getRowDimension(2)->setRowHeight(20);
$cell_no = "A2";
$objPHPExcel->getActiveSheet()->mergeCells('A2:E2');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue((($account_data->fields['FRANCHISE']==1)?'Franchisee: ':' ').$business_name." (".$concatenatedResults.")");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setWrapText(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "F2";
$objPHPExcel->getActiveSheet()->mergeCells('F2:G2');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue('('.date('m/d/Y', strtotime($from_date)).' - '.date('m/d/Y', strtotime($to_date)).')');
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "H2";
$objPHPExcel->getActiveSheet()->mergeCells('H2:I2');
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
$objPHPExcel->getActiveSheet()->getStyle('A2:I2')->applyFromArray($styleArray);

$cell_no = "C3";
$objPHPExcel->getActiveSheet()->mergeCells('C3:D3');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Lessons Taught");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

$cell_no = "E3";
$objPHPExcel->getActiveSheet()->mergeCells('E3:G3');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("$ value of misc. sales");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

$cell_no = "H3";
$objPHPExcel->getActiveSheet()->mergeCells('H3:I3');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("$ val. of lessons sales");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);



$cell_no = "A4";
//$objPHPExcel->getActiveSheet()->mergeCells('A1:A1');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Staff name");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

$cell_no = "B4";
//$objPHPExcel->getActiveSheet()->mergeCells('A1:A1');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Number of Guests");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

$cell_no = "C4";
//$objPHPExcel->getActiveSheet()->mergeCells('A1:A1');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Private");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

$cell_no = "D4";
//$objPHPExcel->getActiveSheet()->mergeCells('A1:A1');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Class");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

$cell_no = "E4";
//$objPHPExcel->getActiveSheet()->mergeCells('A1:A1');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("DOR/Sanct. Competition");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

$cell_no = "F4";
//$objPHPExcel->getActiveSheet()->mergeCells('A1:A1');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Showcase Medal ball");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

$cell_no = "G4";
//$objPHPExcel->getActiveSheet()->mergeCells('A1:A1');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("General Misc. NonUnit");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

$cell_no = "H4";
//$objPHPExcel->getActiveSheet()->mergeCells('A1:A1');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Interview Dept.");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

$cell_no = "I4";
//$objPHPExcel->getActiveSheet()->mergeCells('A1:A1');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Renewal Dept.");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);


$cell_no = "A5";
$objPHPExcel->getActiveSheet()->mergeCells('A5:I5');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("INSTRUCTORS");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);


$i=6;
$row = $db->Execute("SELECT DISTINCT (DOA_USERS.PK_USER), DOA_USERS.FIRST_NAME, DOA_USERS.LAST_NAME, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.ACTIVE FROM DOA_USERS LEFT JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER WHERE DOA_USER_LOCATION.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_USERS.APPEAR_IN_CALENDAR = 1 AND DOA_USERS.ACTIVE = 1 AND DOA_USERS.IS_DELETED = 0 AND DOA_USERS.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER']." ORDER BY DOA_USERS.DISPLAY_ORDER ASC");
while (!$row->EOF) {
    $last_name = empty($row->fields['LAST_NAME']) ? '' : $row->fields['LAST_NAME'].',';

    $private_data = $db_account->Execute("SELECT SUM(DOA_SCHEDULING_CODE.UNIT) AS PRIVATE_COUNT FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_SCHEDULING_CODE ON DOA_APPOINTMENT_MASTER.PK_SCHEDULING_CODE = DOA_SCHEDULING_CODE.PK_SCHEDULING_CODE LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE IN ('NORMAL', 'AD-HOC') AND DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER != 2 AND DOA_APPOINTMENT_MASTER.IS_CHARGED = 1 ".$appointment_date." AND DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER = ".$row->fields['PK_USER']);
    $group_data = $db_account->Execute("SELECT SUM(DOA_SCHEDULING_CODE.UNIT) AS GROUP_COUNT FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_SCHEDULING_CODE ON DOA_APPOINTMENT_MASTER.PK_SCHEDULING_CODE = DOA_SCHEDULING_CODE.PK_SCHEDULING_CODE LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE = 'GROUP' AND DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER != 2 AND DOA_APPOINTMENT_MASTER.IS_CHARGED = 1 ".$appointment_date." AND DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER = ".$row->fields['PK_USER']);

    $dor_misc_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE_PROVIDER.PERCENTAGE_AMOUNT) AS MISC_TOTAL FROM DOA_ENROLLMENT_SERVICE_PROVIDER LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE = 16 AND DOA_ENROLLMENT_MASTER.MISC_TYPE = 'DOR' AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID = ".$row->fields['PK_USER']." $enrollment_date");
    $showcase_misc_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE_PROVIDER.PERCENTAGE_AMOUNT) AS MISC_TOTAL FROM DOA_ENROLLMENT_SERVICE_PROVIDER LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE = 16 AND DOA_ENROLLMENT_MASTER.MISC_TYPE = 'SHOWCASE' AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID = ".$row->fields['PK_USER']." $enrollment_date");
    $general_misc_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE_PROVIDER.PERCENTAGE_AMOUNT) AS MISC_TOTAL FROM DOA_ENROLLMENT_SERVICE_PROVIDER LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE = 16 AND DOA_ENROLLMENT_MASTER.MISC_TYPE = 'GENERAL' AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID = ".$row->fields['PK_USER']." $enrollment_date");

    $interview_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE_PROVIDER.PERCENTAGE_AMOUNT) AS INTERVIEW_TOTAL FROM DOA_ENROLLMENT_SERVICE_PROVIDER LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE NOT IN (13,16) AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID = ".$row->fields['PK_USER']." $enrollment_date");
    $renewal_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE_PROVIDER.PERCENTAGE_AMOUNT) AS RENEWAL_TOTAL FROM DOA_ENROLLMENT_SERVICE_PROVIDER LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE = 13 AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID = ".$row->fields['PK_USER']." $enrollment_date");

    $cell_no = "A".$i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($last_name.' '.$row->fields['FIRST_NAME']);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

    $cell_no = "B".$i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("");
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

    $cell_no = "C".$i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($private_data->fields['PRIVATE_COUNT'] ?? 0);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

    $cell_no = "D".$i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($group_data->fields['GROUP_COUNT'] ?? 0);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

    $cell_no = "E".$i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue(number_format($dor_misc_data->fields['MISC_TOTAL'] , 2));
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

    $cell_no = "F".$i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue(number_format($showcase_misc_data->fields['MISC_TOTAL'] , 2));
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

    $cell_no = "G".$i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue(number_format($general_misc_data->fields['MISC_TOTAL'] , 2));
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

    $cell_no = "H".$i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue(number_format($interview_data->fields['INTERVIEW_TOTAL'] , 2));
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

    $cell_no = "I".$i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue(number_format($renewal_data->fields['RENEWAL_TOTAL'] , 2));
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

    $row->MoveNext();
    $i++;
}


$cell_no = "A".$i;
$objPHPExcel->getActiveSheet()->mergeCells('A'.$i.':I'.$i);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("EXECUTIVES");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$i++;

$row = $db->Execute("SELECT DISTINCT (PK_USER) AS PK_USER, FIRST_NAME, LAST_NAME FROM DOA_USERS WHERE PK_USER IN (".implode(',', $executive_id).")");
while (!$row->EOF) {
    $last_name = empty($row->fields['LAST_NAME']) ? '' : $row->fields['LAST_NAME'].',';

    $executive_dor_misc_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_BILLING.TOTAL_AMOUNT) AS MISC_TOTAL FROM DOA_ENROLLMENT_BILLING JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE = 16 AND DOA_ENROLLMENT_MASTER.MISC_TYPE = 'DOR' AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID = ".$row->fields['PK_USER']." $enrollment_date");
    $executive_showcase_misc_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_BILLING.TOTAL_AMOUNT) AS MISC_TOTAL FROM DOA_ENROLLMENT_BILLING JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE = 16 AND DOA_ENROLLMENT_MASTER.MISC_TYPE = 'SHOWCASE' AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID = ".$row->fields['PK_USER']." $enrollment_date");
    $executive_general_misc_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_BILLING.TOTAL_AMOUNT) AS MISC_TOTAL FROM DOA_ENROLLMENT_BILLING JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE = 16 AND DOA_ENROLLMENT_MASTER.MISC_TYPE = 'GENERAL' AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID = ".$row->fields['PK_USER']." $enrollment_date");

    $executive_interview_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_BILLING.TOTAL_AMOUNT) AS INTERVIEW_TOTAL FROM DOA_ENROLLMENT_BILLING JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE NOT IN (13,16) AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID = ".$row->fields['PK_USER']." $enrollment_date");
    $executive_renewal_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_BILLING.TOTAL_AMOUNT) AS RENEWAL_TOTAL FROM DOA_ENROLLMENT_BILLING JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE = 13 AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_ENROLLMENT_MASTER.ENROLLMENT_BY_ID = ".$row->fields['PK_USER']." $enrollment_date");

    $cell_no = "A".$i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($last_name.' '.$row->fields['FIRST_NAME']);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

    $cell_no = "B".$i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("");
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

    $cell_no = "C".$i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($private_data->fields['PRIVATE_COUNT'] ?? 0);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

    $cell_no = "D".$i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($group_data->fields['GROUP_COUNT'] ?? 0);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

    $cell_no = "E".$i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue(number_format($dor_misc_data->fields['MISC_TOTAL'] , 2));
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

    $cell_no = "F".$i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue(number_format($showcase_misc_data->fields['MISC_TOTAL'] , 2));
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

    $cell_no = "G".$i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue(number_format($general_misc_data->fields['MISC_TOTAL'] , 2));
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

    $cell_no = "H".$i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue(number_format($interview_data->fields['INTERVIEW_TOTAL'] , 2));
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

    $cell_no = "I".$i;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue(number_format($renewal_data->fields['RENEWAL_TOTAL'] , 2));
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

    $row->MoveNext();
    $i++;
}


$objWriter->save($outputFileName);
$objPHPExcel->disconnectWorksheets();
header("location:".$outputFileName);