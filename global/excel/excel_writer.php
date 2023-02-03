<?php
define('EOL',(PHP_SAPI == 'cli') ? PHP_EOL : '<br />');
include 'Classes/PHPExcel/IOFactory.php';
$inputFileType = 'Excel2007';
$outputFileName = 'test2.xlsx';
$objReader = PHPExcel_IOFactory::createReader($inputFileType);
$objReader->setIncludeCharts(TRUE);
$objPHPExcel = $objReader->load('Template/unit_template.xlsx');
$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
$objPHPExcel->getActiveSheet()->getCell('A2')->setValue("FName");
$objPHPExcel->getActiveSheet()->getCell('B2')->setValue("LastName");
$objWriter->save($outputFileName);
$objPHPExcel->disconnectWorksheets();
unset($objPHPExcel);
?>