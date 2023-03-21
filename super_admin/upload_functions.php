<?php
require_once('../global/query_factory.php');
require_once('../global/common_functions.php');

$db1 = new queryFactory();
if($_SERVER['HTTP_HOST'] == 'localhost' ) {
    $conn1 = $db1->connect('localhost','root','','amto');
    $http_path = 'http://localhost/doable/';
} else {
    $conn1 = $db1->connect('localhost','root','b54eawxj5h8ev','amto');
    $http_path = 'https://doable.net/';
}
if ($db1->error_number){
    die("Connection Error");
}

function getRole($role_id){
    global $db1;
    $role = $db1->Execute("SELECT name FROM roles where id = '$role_id'");
    if ($role->fields['name']=="Supervisor" || $role->fields['name']=="Counselor") {
        return "Account User";
    } else {
        return $role->fields['name'];
    }
}

function getInquiry($inquiry_id) {
    global $db1;
    $inquiry = $db1->Execute("SELECT inquiry_type FROM inquiry_type where inquiry_id = '$inquiry_id'");
    return $inquiry->fields['inquiry_type'];
}

function getTaker($taker_id) {
    global $db1;
    $inquiry_taker = $db1->Execute("SELECT user_name FROM users where user_id = '$taker_id'");
    return $inquiry_taker->fields['user_name'];
}

function getService($service_id) {
    global $db1;
    $service_taker = $db1->Execute("SELECT service_name, chargeable FROM service_codes where service_id = '$service_id'");
    return [$service_taker->fields['service_name'], $service_taker->fields['chargeable']];
}