<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4])) {
    header("location:../login.php");
    exit;
}

if (!isset($_GET['PK_ENROLLMENT_MASTER']) || $_GET['PK_ENROLLMENT_MASTER'] == '') {
    echo "invalid enrollment";
    exit;
}

$PK_ENROLLMENT_MASTER = $_GET['PK_ENROLLMENT_MASTER'];

deleteEnrollment($PK_ENROLLMENT_MASTER);

echo "deleted";
