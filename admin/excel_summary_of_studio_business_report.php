<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;
error_reporting(0);

include ('../global/excel/Classes/PHPExcel/IOFactory.php');

$title = "SUMMARY OF STUDIO BUSINESS REPORT";

$week_number = '';
$from_date = '';
$to_date = '';

if (!empty($_GET['week_number'])){
    $week_number = $_GET['week_number'];
    $YEAR = date('Y');

    $from_date = date('Y-m-d', strtotime($_GET['start_date']));
    $to_date = date('Y-m-d', strtotime($from_date. ' +6 day'));

    $weekly_date_condition = "'".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."'";
    $net_year_date_condition = "'".date('Y', strtotime($to_date))."-01-01' AND '".date('Y-m-d', strtotime($to_date))."'";
    $prev_year_date_condition = "'".(date('Y', strtotime($to_date))-1)."-01-01' AND '".(date('Y', strtotime($to_date))-1).date('-m-d', strtotime($to_date))."'";

    $appointment_date = "AND DOA_APPOINTMENT_MASTER.DATE BETWEEN '".date('Y-m-d', strtotime($from_date))."' AND '".date('Y-m-d', strtotime($to_date))."'";
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
$outputFileName = 'STUDIO_BUSINESS_REPORT.xlsx';

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

$objPHPExcel->getActiveSheet()->mergeCells('A1:k1');

$cell_no = "A1";
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($title);
$objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setSize(18); // Set font size to 16
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getRowDimension(1)->setRowHeight(36);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

$objPHPExcel->getActiveSheet()->getRowDimension(2)->setRowHeight(20);
$cell_no = "A2";
$objPHPExcel->getActiveSheet()->mergeCells('A2:F2');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($business_name);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setWrapText(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "G2";
$objPHPExcel->getActiveSheet()->mergeCells('G2:I2');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue('('.date('m/d/Y', strtotime($from_date)) - date('m/d/Y', strtotime($to_date)).')');
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "J2";
$objPHPExcel->getActiveSheet()->mergeCells('J2:K2');
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

$cell_no = "A4";
$objPHPExcel->getActiveSheet()->mergeCells('A4:k4');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("CASH RECEIPTS");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

$cell_no = "A5";
$objPHPExcel->getActiveSheet()->mergeCells('A5:C5');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Period");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getRowDimension(5)->setRowHeight(20);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "D5";
$objPHPExcel->getActiveSheet()->mergeCells('D5:E5');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Regular");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "F5";
$objPHPExcel->getActiveSheet()->mergeCells('F5:H5');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Misc. / NonUnit");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "I5";
$objPHPExcel->getActiveSheet()->mergeCells('I5:K5');
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Total");
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
$objPHPExcel->getActiveSheet()->getStyle('A5:K5')->applyFromArray($styleArray);

$PERIOD = array();
$PERIOD[] = "Week";
$PERIOD[] = "Week Refunds";
$PERIOD[] = "Transfer out";
$PERIOD[] = "NET Y.T.D.";
$PERIOD[] = "PRV. Y.T.D.";

$regular_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_PAYMENT.AMOUNT) AS REGULAR_TOTAL FROM DOA_ENROLLMENT_PAYMENT LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER LEFT JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USER_MASTER.PK_USER = DOA_USERS.PK_USER WHERE (DOA_ENROLLMENT_MASTER.MISC_TYPE = NULL || DOA_ENROLLMENT_MASTER.MISC_TYPE = '') AND DOA_USERS.IS_DELETED = 0 AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_ENROLLMENT_PAYMENT.TYPE = 'Payment' AND DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE != 7 AND DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE BETWEEN $weekly_date_condition");
$other_payment_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_PAYMENT.AMOUNT) AS OTHER_TOTAL FROM DOA_ENROLLMENT_PAYMENT WHERE PK_ENROLLMENT_MASTER = 0 AND DOA_ENROLLMENT_PAYMENT.TYPE = 'Payment' AND DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE BETWEEN $weekly_date_condition");
$misc_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_PAYMENT.AMOUNT) AS MISC_TOTAL FROM DOA_ENROLLMENT_PAYMENT LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER LEFT JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USER_MASTER.PK_USER = DOA_USERS.PK_USER WHERE DOA_ENROLLMENT_MASTER.MISC_TYPE != '' AND DOA_USERS.IS_DELETED = 0 AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_ENROLLMENT_PAYMENT.TYPE = 'Payment' AND DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE != 7 AND DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE BETWEEN $weekly_date_condition");

$regular_refund_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_PAYMENT.AMOUNT) AS REGULAR_REFUND FROM DOA_ENROLLMENT_PAYMENT LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER LEFT JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USER_MASTER.PK_USER = DOA_USERS.PK_USER WHERE (DOA_ENROLLMENT_MASTER.MISC_TYPE = NULL || DOA_ENROLLMENT_MASTER.MISC_TYPE = '') AND DOA_USERS.IS_DELETED = 0 AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_ENROLLMENT_PAYMENT.TYPE = 'Refund' AND DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE BETWEEN $weekly_date_condition");
$misc_refund_data = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_PAYMENT.AMOUNT) AS MISC_REFUND FROM DOA_ENROLLMENT_PAYMENT LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER LEFT JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USER_MASTER.PK_USER = DOA_USERS.PK_USER WHERE DOA_ENROLLMENT_MASTER.MISC_TYPE != '' AND DOA_USERS.IS_DELETED = 0 AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_ENROLLMENT_PAYMENT.TYPE = 'Refund' AND DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE BETWEEN $weekly_date_condition");

$regular_data_yearly = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_PAYMENT.AMOUNT) AS REGULAR_TOTAL FROM DOA_ENROLLMENT_PAYMENT LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER LEFT JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USER_MASTER.PK_USER = DOA_USERS.PK_USER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE NOT IN (16,17,18) AND DOA_USERS.IS_DELETED = 0 AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_ENROLLMENT_PAYMENT.TYPE = 'Payment' AND DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE BETWEEN $net_year_date_condition");
$other_payment_data_yearly = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_PAYMENT.AMOUNT) AS OTHER_TOTAL FROM DOA_ENROLLMENT_PAYMENT WHERE PK_ENROLLMENT_MASTER = 0 AND DOA_ENROLLMENT_PAYMENT.TYPE = 'Payment' AND DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE BETWEEN $net_year_date_condition");
$misc_data_yearly = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_PAYMENT.AMOUNT) AS MISC_TOTAL FROM DOA_ENROLLMENT_PAYMENT LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER LEFT JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USER_MASTER.PK_USER = DOA_USERS.PK_USER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE IN (16,17) AND DOA_USERS.IS_DELETED = 0 AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_ENROLLMENT_PAYMENT.TYPE = 'Payment' AND DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE BETWEEN $net_year_date_condition");

$regular_data_prev_year = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_PAYMENT.AMOUNT) AS REGULAR_TOTAL FROM DOA_ENROLLMENT_PAYMENT LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER LEFT JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USER_MASTER.PK_USER = DOA_USERS.PK_USER WHERE (DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE IS NULL OR DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE = '' OR DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE NOT IN (16,17,18)) AND DOA_USERS.IS_DELETED = 0 AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_ENROLLMENT_PAYMENT.TYPE = 'Payment' AND DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE BETWEEN $prev_year_date_condition");
$other_payment_data_prev_year = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_PAYMENT.AMOUNT) AS OTHER_TOTAL FROM DOA_ENROLLMENT_PAYMENT WHERE PK_ENROLLMENT_MASTER = 0 AND DOA_ENROLLMENT_PAYMENT.TYPE = 'Payment' AND DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE BETWEEN $prev_year_date_condition");
$misc_data_prev_year = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_PAYMENT.AMOUNT) AS MISC_TOTAL FROM DOA_ENROLLMENT_PAYMENT LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER LEFT JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USER_MASTER.PK_USER = DOA_USERS.PK_USER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_TYPE IN (16,17) AND DOA_USERS.IS_DELETED = 0 AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_ENROLLMENT_PAYMENT.TYPE = 'Payment' AND DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE BETWEEN $prev_year_date_condition");

$REGULAR = array();
$REGULAR[] = number_format($regular_data->fields['REGULAR_TOTAL']+$other_payment_data->fields['OTHER_TOTAL'],2);
$REGULAR[] = ($regular_refund_data->fields['REGULAR_REFUND'] > 0) ? '-' : ' '.number_format($regular_refund_data->fields['REGULAR_REFUND'],2);
$REGULAR[] = '0.00';
$REGULAR[] = number_format($regular_data_yearly->fields['REGULAR_TOTAL']+$other_payment_data_yearly->fields['OTHER_TOTAL'],2);
$REGULAR[] = number_format($regular_data_prev_year->fields['REGULAR_TOTAL']+$other_payment_data_prev_year->fields['OTHER_TOTAL'],2);

$MISC = array();
$MISC[] = number_format($misc_data->fields['MISC_TOTAL'],2);
$MISC[] = ($regular_refund_data->fields['REGULAR_REFUND'] > 0) ? '-' : ' '.number_format($misc_refund_data->fields['MISC_REFUND'],2);
$MISC[] = '0.00';
$MISC[] = number_format($misc_data_yearly->fields['MISC_TOTAL'],2);
$MISC[] = number_format($misc_data_prev_year->fields['MISC_TOTAL'],2);

$TOTAL = array();
$TOTAL[] = number_format($regular_data->fields['REGULAR_TOTAL']+$other_payment_data->fields['OTHER_TOTAL']+$misc_data->fields['MISC_TOTAL'], 2);
$TOTAL[] = ($regular_refund_data->fields['REGULAR_REFUND'] > 0) ? '-' : ' '.number_format($regular_refund_data->fields['REGULAR_REFUND']+$misc_refund_data->fields['MISC_REFUND'], 2);
$TOTAL[] = '0.00';
$TOTAL[] = number_format($regular_data_yearly->fields['REGULAR_TOTAL']+$other_payment_data_yearly->fields['OTHER_TOTAL']+$misc_data_yearly->fields['MISC_TOTAL'], 2);
$TOTAL[] = number_format($regular_data_prev_year->fields['REGULAR_TOTAL']+$other_payment_data_prev_year->fields['OTHER_TOTAL']+$misc_data_prev_year->fields['MISC_TOTAL'], 2);

$line = 5;
foreach($PERIOD as $key => $val){
    $line++;

    $objPHPExcel->getActiveSheet()->getRowDimension($line)->setRowHeight(20);

    $objPHPExcel->getActiveSheet()->mergeCells('A'.$line.':C'.$line);

    $cell_no = 'A'.$line;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($val);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $objPHPExcel->getActiveSheet()->mergeCells('D'.$line.':E'.$line);
    $cell_no = 'D'.$line;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($REGULAR[$key]);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getNumberFormat()->setFormatCode('_("$"* #,##0.00_);_("$"* (#,##0.00);_("$"* "-"??_);_(@_)');

    $objPHPExcel->getActiveSheet()->mergeCells('F'.$line.':H'.$line);
    $cell_no = 'F'.$line;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($MISC[$key]);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getNumberFormat()->setFormatCode('_("$"* #,##0.00_);_("$"* (#,##0.00);_("$"* "-"??_);_(@_)');

    $objPHPExcel->getActiveSheet()->mergeCells('I'.$line.':K'.$line);
    $cell_no = 'I'.$line;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($TOTAL[$key]);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getNumberFormat()->setFormatCode('_("$"* #,##0.00_);_("$"* (#,##0.00);_("$"* "-"??_);_(@_)');

    $styleArray = [
        'borders' => [
            'allborders' => [
                'style' => PHPExcel_Style_Border::BORDER_THIN,
                'color' => ['rgb' => '000000']
            ]
        ]
    ];
    $objPHPExcel->getActiveSheet()->getStyle('A'.$line.':K'.$line)->applyFromArray($styleArray);
}

$line += 2;
$cell_no = "A".$line;
$objPHPExcel->getActiveSheet()->mergeCells('A'.$line.':D'.$line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("INQUIRIES");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getRowDimension($line)->setRowHeight(20);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "E".$line;
$objPHPExcel->getActiveSheet()->mergeCells('E'.$line.':G'.$line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("LESSONS TAUGHT | Exchange");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getRowDimension($line)->setRowHeight(20);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "H".$line;
$line1 = $line + 1;
$objPHPExcel->getActiveSheet()->mergeCells('H'.$line.':K'.$line1);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("ACTIVE STUDENTS");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getRowDimension($line)->setRowHeight(20);
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
$objPHPExcel->getActiveSheet()->getStyle('A'.$line.':K'.$line)->applyFromArray($styleArray);

$line++;
$cell_no = "A".$line;
$objPHPExcel->getActiveSheet()->mergeCells('A'.$line.':B'.$line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Contact");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setWrapText(true);
$objPHPExcel->getActiveSheet()->getRowDimension($line)->setRowHeight(35);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "C".$line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Booked");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setWrapText(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "D".$line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Showed");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setWrapText(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "E".$line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Pvt Intv(front)");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setWrapText(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "F".$line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Pvt Ren(back)");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setWrapText(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "G".$line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("# in class [incl.core]");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setWrapText(true);
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
$objPHPExcel->getActiveSheet()->getStyle('A'.$line.':K'.$line)->applyFromArray($styleArray);

$PERIOD = array();
$PERIOD[] = "Week";
$PERIOD[] = "Y.T.D.";
$PERIOD[] = "PREV";

$CONTACT = array();
$CONTACT[] = "6";
$CONTACT[] = "27";
$CONTACT[] = "21";

$BOOKED = array();
$BOOKED[] = "1";
$BOOKED[] = "16";
$BOOKED[] = "21";

$SHOWED = array();
$SHOWED[] = "2";
$SHOWED[] = "14";
$SHOWED[] = "17";

$INTC = array();
$INTC[] = "12";
$INTC[] = "52";
$INTC[] = "54";

$REN = array();
$REN[] = "39";
$REN[] = "197.5";
$REN[] = "205";

$IN_CLASS = array();
$IN_CLASS[] = "30 [30]";
$IN_CLASS[] = "287 [287]";
$IN_CLASS[] = "147 [147]";

$STUD = array();
$STUD[] = "Department";
$STUD[] = "25 Intv(front)";
$STUD[] = "31 Ren(back)";

foreach($PERIOD as $key => $val){
    $line++;

    $objPHPExcel->getActiveSheet()->getRowDimension($line)->setRowHeight(20);

    $cell_no = 'A'.$line;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($val);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $cell_no = 'B'.$line;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($CONTACT[$key]);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $cell_no = 'C'.$line;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($BOOKED[$key]);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $cell_no = 'D'.$line;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($SHOWED[$key]);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $cell_no = 'E'.$line;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($INTC[$key]);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $cell_no = 'F'.$line;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($REN[$key]);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $cell_no = 'G'.$line;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($IN_CLASS[$key]);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $cell_no = 'H'.$line;
    $objPHPExcel->getActiveSheet()->mergeCells('H'.$line.':K'.$line);
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($STUD[$key]);
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
    $objPHPExcel->getActiveSheet()->getStyle('A'.$line.':K'.$line)->applyFromArray($styleArray);
}

$line++;
$line++;
$cell_no = "A".$line;
$objPHPExcel->getActiveSheet()->mergeCells('A'.$line.':k'.$line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("UNIT SALES TRACKING");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getRowDimension(4)->setRowHeight(20);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);


$line++;
$cell_no = "A".$line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("");
$objPHPExcel->getActiveSheet()->getRowDimension($line)->setRowHeight(35);

$cell_no = "B".$line;
$objPHPExcel->getActiveSheet()->mergeCells('B'.$line.':C'.$line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Pre Original");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setWrapText(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "D".$line;
$objPHPExcel->getActiveSheet()->mergeCells('D'.$line.':E'.$line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Original");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setWrapText(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "F".$line;
$objPHPExcel->getActiveSheet()->mergeCells('F'.$line.':G'.$line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Extension");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setWrapText(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "H".$line;
$objPHPExcel->getActiveSheet()->mergeCells('H'.$line.':I'.$line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Renewal");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setWrapText(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "J".$line;
$objPHPExcel->getActiveSheet()->mergeCells('J'.$line.':K'.$line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Total");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setWrapText(true);
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
$objPHPExcel->getActiveSheet()->getStyle('A'.$line.':K'.$line)->applyFromArray($styleArray);

$line++;
$cell_no = "A".$line;
$line_1 = $line + 2;
$objPHPExcel->getActiveSheet()->mergeCells('A'.$line.':A'.$line_1);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Week");
$objPHPExcel->getActiveSheet()->getRowDimension($line)->setRowHeight(20);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setWrapText(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'B'.$line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("T : 3");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'C'.$line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("S : 3");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'D'.$line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("T : 5");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'E'.$line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("S : 4");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'F'.$line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("T : 0");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'G'.$line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("S : 0");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'H'.$line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("T : 0");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'I'.$line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("S : 0");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'J'.$line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("T : 8");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'K'.$line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("S : 7");
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
$objPHPExcel->getActiveSheet()->getStyle('A'.$line.':K'.$line)->applyFromArray($styleArray);

$line++;

$objPHPExcel->getActiveSheet()->getRowDimension($line)->setRowHeight(20);

$cell_no = 'B'.$line;
$objPHPExcel->getActiveSheet()->mergeCells('B'.$line.':C'.$line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Units: 7.00");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'D'.$line;
$objPHPExcel->getActiveSheet()->mergeCells('D'.$line.':E'.$line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Units: 16.00");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'F'.$line;
$objPHPExcel->getActiveSheet()->mergeCells('F'.$line.':G'.$line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Units: 0.00");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'H'.$line;
$objPHPExcel->getActiveSheet()->mergeCells('H'.$line.':I'.$line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Units: 0.00");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'J'.$line;
$objPHPExcel->getActiveSheet()->mergeCells('J'.$line.':K'.$line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Units: 23.00");
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
$objPHPExcel->getActiveSheet()->getStyle('A'.$line.':K'.$line)->applyFromArray($styleArray);

$line++;

$objPHPExcel->getActiveSheet()->getRowDimension($line)->setRowHeight(20);

$cell_no = 'B'.$line;
$objPHPExcel->getActiveSheet()->mergeCells('B'.$line.':C'.$line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("805.00");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getNumberFormat()->setFormatCode('_("$"* #,##0.00_);_("$"* (#,##0.00);_("$"* "-"??_);_(@_)');

$cell_no = 'D'.$line;
$objPHPExcel->getActiveSheet()->mergeCells('D'.$line.':E'.$line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("2872.80");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getNumberFormat()->setFormatCode('_("$"* #,##0.00_);_("$"* (#,##0.00);_("$"* "-"??_);_(@_)');

$cell_no = 'F'.$line;
$objPHPExcel->getActiveSheet()->mergeCells('F'.$line.':G'.$line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("0");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getNumberFormat()->setFormatCode('_("$"* #,##0.00_);_("$"* (#,##0.00);_("$"* "-"??_);_(@_)');

$cell_no = 'H'.$line;
$objPHPExcel->getActiveSheet()->mergeCells('H'.$line.':I'.$line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("0");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getNumberFormat()->setFormatCode('_("$"* #,##0.00_);_("$"* (#,##0.00);_("$"* "-"??_);_(@_)');

$cell_no = 'J'.$line;
$objPHPExcel->getActiveSheet()->mergeCells('J'.$line.':K'.$line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("3677.80");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getNumberFormat()->setFormatCode('_("$"* #,##0.00_);_("$"* (#,##0.00);_("$"* "-"??_);_(@_)');

$styleArray = [
    'borders' => [
        'allborders' => [
            'style' => PHPExcel_Style_Border::BORDER_THIN,
            'color' => ['rgb' => '000000']
        ]
    ]
];
$objPHPExcel->getActiveSheet()->getStyle('A'.$line.':K'.$line)->applyFromArray($styleArray);

$line++;

$objPHPExcel->getActiveSheet()->getRowDimension($line)->setRowHeight(20);

$cell_no = 'A'.$line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Adjust");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setWrapText(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'B'.$line;
$objPHPExcel->getActiveSheet()->mergeCells('B'.$line.':C'.$line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("0 / 0.00/ $0.00");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'D'.$line;
$objPHPExcel->getActiveSheet()->mergeCells('D'.$line.':E'.$line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("0 / 0.00/ $0.00");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'F'.$line;
$objPHPExcel->getActiveSheet()->mergeCells('F'.$line.':G'.$line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("0 / 0.00/ $0.00");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'H'.$line;
$objPHPExcel->getActiveSheet()->mergeCells('H'.$line.':I'.$line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("0 / 0.00/ $0.00");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'J'.$line;
$objPHPExcel->getActiveSheet()->mergeCells('J'.$line.':K'.$line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("0 / 0.00/ $0.00");
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
$objPHPExcel->getActiveSheet()->getStyle('A'.$line.':K'.$line)->applyFromArray($styleArray);

$line++;

$objPHPExcel->getActiveSheet()->getRowDimension($line)->setRowHeight(20);

$cell_no = "A".$line;
$line_1 = $line + 2;
$objPHPExcel->getActiveSheet()->mergeCells('A'.$line.':A'.$line_1);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Net YTD");
$objPHPExcel->getActiveSheet()->getRowDimension($line)->setRowHeight(20);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setWrapText(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'B'.$line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("T : 3");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'C'.$line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("S : 3");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'D'.$line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("T : 5");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'E'.$line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("S : 4");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'F'.$line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("T : 0");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'G'.$line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("S : 0");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'H'.$line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("T : 0");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'I'.$line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("S : 0");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'J'.$line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("T : 8");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'K'.$line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("S : 7");
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
$objPHPExcel->getActiveSheet()->getStyle('A'.$line.':K'.$line)->applyFromArray($styleArray);

$line++;

$objPHPExcel->getActiveSheet()->getRowDimension($line)->setRowHeight(20);

$cell_no = 'B'.$line;
$objPHPExcel->getActiveSheet()->mergeCells('B'.$line.':C'.$line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Units: 7.00");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'D'.$line;
$objPHPExcel->getActiveSheet()->mergeCells('D'.$line.':E'.$line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Units: 16.00");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'F'.$line;
$objPHPExcel->getActiveSheet()->mergeCells('F'.$line.':G'.$line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Units: 0.00");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'H'.$line;
$objPHPExcel->getActiveSheet()->mergeCells('H'.$line.':I'.$line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Units: 0.00");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'J'.$line;
$objPHPExcel->getActiveSheet()->mergeCells('J'.$line.':K'.$line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Units: 23.00");
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
$objPHPExcel->getActiveSheet()->getStyle('A'.$line.':K'.$line)->applyFromArray($styleArray);

$line++;

$objPHPExcel->getActiveSheet()->getRowDimension($line)->setRowHeight(20);

$cell_no = 'B'.$line;
$objPHPExcel->getActiveSheet()->mergeCells('B'.$line.':C'.$line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("805.00");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getNumberFormat()->setFormatCode('_("$"* #,##0.00_);_("$"* (#,##0.00);_("$"* "-"??_);_(@_)');

$cell_no = 'D'.$line;
$objPHPExcel->getActiveSheet()->mergeCells('D'.$line.':E'.$line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("2872.80");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getNumberFormat()->setFormatCode('_("$"* #,##0.00_);_("$"* (#,##0.00);_("$"* "-"??_);_(@_)');

$cell_no = 'F'.$line;
$objPHPExcel->getActiveSheet()->mergeCells('F'.$line.':G'.$line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("0");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getNumberFormat()->setFormatCode('_("$"* #,##0.00_);_("$"* (#,##0.00);_("$"* "-"??_);_(@_)');

$cell_no = 'H'.$line;
$objPHPExcel->getActiveSheet()->mergeCells('H'.$line.':I'.$line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("0");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getNumberFormat()->setFormatCode('_("$"* #,##0.00_);_("$"* (#,##0.00);_("$"* "-"??_);_(@_)');

$cell_no = 'J'.$line;
$objPHPExcel->getActiveSheet()->mergeCells('J'.$line.':K'.$line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("3677.80");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getNumberFormat()->setFormatCode('_("$"* #,##0.00_);_("$"* (#,##0.00);_("$"* "-"??_);_(@_)');

$styleArray = [
    'borders' => [
        'allborders' => [
            'style' => PHPExcel_Style_Border::BORDER_THIN,
            'color' => ['rgb' => '000000']
        ]
    ]
];
$objPHPExcel->getActiveSheet()->getStyle('A'.$line.':K'.$line)->applyFromArray($styleArray);

$line++;

$objPHPExcel->getActiveSheet()->getRowDimension($line)->setRowHeight(20);

$cell_no = 'A'.$line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Prev");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setWrapText(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'B'.$line;
$objPHPExcel->getActiveSheet()->mergeCells('B'.$line.':C'.$line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("0 / 0.00/ $0.00");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'D'.$line;
$objPHPExcel->getActiveSheet()->mergeCells('D'.$line.':E'.$line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("0 / 0.00/ $0.00");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'F'.$line;
$objPHPExcel->getActiveSheet()->mergeCells('F'.$line.':G'.$line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("0 / 0.00/ $0.00");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'H'.$line;
$objPHPExcel->getActiveSheet()->mergeCells('H'.$line.':I'.$line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("0 / 0.00/ $0.00");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = 'J'.$line;
$objPHPExcel->getActiveSheet()->mergeCells('J'.$line.':K'.$line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("0 / 0.00/ $0.00");
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
$objPHPExcel->getActiveSheet()->getStyle('A'.$line.':K'.$line)->applyFromArray($styleArray);

$line++;
$line++;
$cell_no = "A".$line;
$objPHPExcel->getActiveSheet()->mergeCells('A'.$line.':k'.$line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("MISCELLANEOUS / FESTIVAL SALES TRACKING");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);


$line++;
$objPHPExcel->getActiveSheet()->getRowDimension($line)->setRowHeight(20);

$cell_no = "A".$line;
$line1 = $line + 1;
$objPHPExcel->getActiveSheet()->mergeCells('A'.$line.':C'.$line1);

$cell_no = "D".$line;
$objPHPExcel->getActiveSheet()->mergeCells('D'.$line.':E'.$line);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("NON-UNIT SALES");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "F".$line;
$line1 = $line + 1;
$objPHPExcel->getActiveSheet()->mergeCells('F'.$line.':H'.$line1);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("SUNDRY");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "I".$line;
$line1 = $line + 1;
$objPHPExcel->getActiveSheet()->mergeCells('I'.$line.':K'.$line1);
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("MISCELLANEOUS");
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
$objPHPExcel->getActiveSheet()->getStyle('A'.$line.':K'.$line)->applyFromArray($styleArray);

$line++;
$objPHPExcel->getActiveSheet()->getRowDimension($line)->setRowHeight(20);

$cell_no = "D".$line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Private/coach");
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$cell_no = "E".$line;
$objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue("Class");
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
$objPHPExcel->getActiveSheet()->getStyle('A'.$line.':K'.$line)->applyFromArray($styleArray);

$PERIOD = array();
$PERIOD[] = "Prev.";
$PERIOD[] = "Y.T.D.";
$PERIOD[] = "Prev.";

$PRIVATE = array();
$PRIVATE[] = "";
$PRIVATE[] = "";
$PRIVATE[] = "";

$CLASS = array();
$CLASS[] = "";
$CLASS[] = "";
$CLASS[] = "";

$SUNDRY = array();
$SUNDRY[] = "0";
$SUNDRY[] = "0";
$SUNDRY[] = "0";

$MISCELLANEOUS = array();
$MISCELLANEOUS[] = "32470.00";
$MISCELLANEOUS[] = "35110.00";
$MISCELLANEOUS[] = "2493.00";

foreach($PERIOD as $key => $val){
    $line++;

    $objPHPExcel->getActiveSheet()->getRowDimension($line)->setRowHeight(20);

    $cell_no = 'A'.$line;
    $objPHPExcel->getActiveSheet()->mergeCells('A'.$line.':C'.$line);
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($val);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $cell_no = 'D'.$line;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($PRIVATE[$key]);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $cell_no = 'E'.$line;
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($CLASS[$key]);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $cell_no = 'F'.$line;
    $objPHPExcel->getActiveSheet()->mergeCells('F'.$line.':H'.$line);
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($SUNDRY[$key]);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getNumberFormat()->setFormatCode('_("$"* #,##0.00_);_("$"* (#,##0.00);_("$"* "-"??_);_(@_)');

    $cell_no = 'I'.$line;
    $objPHPExcel->getActiveSheet()->mergeCells('I'.$line.':K'.$line);
    $objPHPExcel->getActiveSheet()->getCell($cell_no)->setValue($MISCELLANEOUS[$key]);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell_no)->getNumberFormat()->setFormatCode('_("$"* #,##0.00_);_("$"* (#,##0.00);_("$"* "-"??_);_(@_)');

    $styleArray = [
        'borders' => [
            'allborders' => [
                'style' => PHPExcel_Style_Border::BORDER_THIN,
                'color' => ['rgb' => '000000']
            ]
        ]
    ];
    $objPHPExcel->getActiveSheet()->getStyle('A'.$line.':K'.$line)->applyFromArray($styleArray);
}


$objWriter->save($outputFileName);
$objPHPExcel->disconnectWorksheets();
header("location:".$outputFileName);