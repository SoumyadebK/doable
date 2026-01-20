<?php
require_once('../../global/config.php');

$results_per_page = 100;

if (isset($_GET['search_text']) && $_GET['search_text'] != '') {
    $search_text = $_GET['search_text'];
    $search = " AND DOA_USERS.FIRST_NAME LIKE '%".$search_text."%' OR DOA_USERS.EMAIL_ID LIKE '%".$search_text."%' OR DOA_USERS.PHONE LIKE '%".$search_text."%'";
} else {
    $search_text = '';
    $search = ' ';
}

$query = $db_account->Execute("SELECT count(DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS TOTAL_RECORDS FROM `DOA_ENROLLMENT_MASTER` INNER JOIN $master_database.DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER = $master_database.DOA_USER_MASTER.PK_USER_MASTER INNER JOIN $master_database.DOA_USERS ON $master_database.DOA_USERS.PK_USER = $master_database.DOA_USER_MASTER.PK_USER LEFT JOIN $master_database.DOA_LOCATION ON $master_database.DOA_LOCATION.PK_LOCATION = DOA_ENROLLMENT_MASTER.PK_LOCATION LEFT JOIN DOA_ENROLLMENT_BALANCE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_BALANCE.PK_ENROLLMENT_MASTER WHERE $master_database.DOA_USER_MASTER.PRIMARY_LOCATION_ID IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_ENROLLMENT_MASTER.PK_USER_MASTER='$_GET[master_id]' AND DOA_ENROLLMENT_MASTER.PK_ACCOUNT_MASTER=".$_SESSION['PK_ACCOUNT_MASTER'].$search);
$number_of_result =  $query->fields['TOTAL_RECORDS'];
$number_of_page = ceil ($number_of_result / $results_per_page);

if (!isset ($_GET['page']) ) {
    $page = 1;
} else {
    $page = $_GET['page'];
}
$page_first_result = ($page-1) * $results_per_page;
?>