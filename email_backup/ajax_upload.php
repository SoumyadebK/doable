<?php
require_once("../global/config.php");
if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == ''/* || $_SESSION['ROLE_ID'] != 1*/) {
	header("location:../index.php");
	exit;
}

if (0 < $_FILES['file']['error']) {
	echo '0||Error: ' . $_FILES['file']['error'] . '<br>';
} else {
	$extn = explode(".", $_FILES['file']['name']);
	$type = $_FILES['file']['type'];
	$ii = count($extn) - 1;
	if (strtolower($extn[$ii]) != 'php' && strtolower($extn[$ii]) != 'js' && strtolower($extn[$ii]) != 'html') {
		$name     	 = $_FILES['file']['name'];
		$rand_string = time() . rand(10000, 99999);
		$name1 = str_replace("." . $extn[$ii], "", $name);
		$file11 = $name1 . '-' . $_SESSION['PK_USER'] . $rand_string . "." . $extn[$ii];
		$newfile1 = '../uploads/email_attachments/' . $file11;

		move_uploaded_file($_FILES['file']['tmp_name'], $newfile1);
		echo "1||" . $name . "||" . $newfile1;
	} else {
		echo "0||You Cant Upload php, js and html Files";
	}
}
