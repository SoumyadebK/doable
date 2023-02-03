<?php
/** PHPExcel_IOFactory */
include 'Classes/PHPExcel/IOFactory.php';
$inputFileName = 'test.xlsx';
$objPHPExcel = PHPExcel_IOFactory::load($inputFileName);
$sheetData = $objPHPExcel->getActiveSheet()->toArray(null,true,true,true);
print_r($sheetData);
?>
