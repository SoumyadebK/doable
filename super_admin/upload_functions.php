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
