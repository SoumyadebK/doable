<?php
session_start();
ob_start();
require_once('query_factory.php');
require_once('common_functions.php');
require_once('helper_function.php');
$db = new queryFactory();
$master_database = 'DOA_MASTER';
if($_SERVER['HTTP_HOST'] == 'localhost' ) {
    $conn = $db->connect('localhost','root','',$master_database);
    $http_path = 'http://localhost/doable/';
} else {
    $conn = $db->connect('localhost','root','b54eawxj5h8ev',$master_database);
    $http_path = 'https://doable.net/';
}

if (!empty($_SESSION['DB_NAME'])) {
    require_once('common_functions_account.php');
    $account_database = $_SESSION['DB_NAME'];
    $db_account = new queryFactory();
    if ($_SERVER['HTTP_HOST'] == 'localhost') {
        $conn_account = $db_account->connect('localhost', 'root', '', $account_database);
    } else {
        $conn_account = $db_account->connect('localhost', 'root', 'b54eawxj5h8ev', $account_database);
    }
    if (mysqli_connect_error()) {
        header("location:../logout.php");
        die("Account Database Connection Error");
    }
}

$env = 'dev';

if ($env === 'dev') {
    define("client_id", "5c896c023b775026fc5e3352_gcd6pouyir4s8c0cwg04ss0ck8s0gcgkoog4wcw00ko0ssksg");
    define("client_secret", "62d7w06sfssg8k4gcgg88kokocg48gg0gk08c80wgosgks4ok4");
    define("ami_api_url", "https://api.arthurmurrayfranchisee.com");
} else {
    define("client_id", "5c896bf53b775025ca4ebd0f_1nw82cn6cbz4os80w0wc4www88gwgo4wgc48kg88sg884ss48c");
    define("client_secret", "16ovel8dax340o8kko8w0c8g8s80ss00k8w8kw84ocgc8k8w80");
    define("ami_api_url", "https://reporting.arthurmurray.com");
}

if ($db->error_number){
    die("Master Database Connection Error");
}else{
    if (!empty($_SESSION['PK_ACCOUNT_MASTER'])){
        $account_data = $db->Execute("SELECT DOA_ACCOUNT_MASTER.SERVICE_PROVIDER_TITLE, DOA_ACCOUNT_MASTER.OPERATION_TAB_TITLE, DOA_ACCOUNT_MASTER.BUSINESS_NAME, DOA_ACCOUNT_MASTER.BUSINESS_LOGO, DOA_TIMEZONE.TIMEZONE, DOA_CURRENCY.CURRENCY_SYMBOL FROM DOA_TIMEZONE RIGHT JOIN DOA_ACCOUNT_MASTER ON DOA_TIMEZONE.PK_TIMEZONE = DOA_ACCOUNT_MASTER.PK_TIMEZONE LEFT JOIN DOA_CURRENCY ON DOA_CURRENCY.PK_CURRENCY = DOA_ACCOUNT_MASTER.PK_CURRENCY WHERE DOA_ACCOUNT_MASTER.PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
        $business_logo = $account_data->fields['BUSINESS_LOGO'];
        $business_name = $account_data->fields['BUSINESS_NAME'];
        if ($account_data->fields['SERVICE_PROVIDER_TITLE'] == NULL || $account_data->fields['SERVICE_PROVIDER_TITLE'] == '')
            $service_provider_title = 'Service Provider';
        else
            $service_provider_title = $account_data->fields['SERVICE_PROVIDER_TITLE'];

        if ($account_data->fields['OPERATION_TAB_TITLE'] == NULL || $account_data->fields['OPERATION_TAB_TITLE'] == '')
            $operation_tab_title = 'Operations';
        else
            $operation_tab_title = $account_data->fields['OPERATION_TAB_TITLE'];

        if (is_null($account_data->fields['CURRENCY_SYMBOL']))
            $currency = '$';
        else
            $currency = $account_data->fields['CURRENCY_SYMBOL'];

        if (!is_null($account_data->fields['TIMEZONE'])) {
            date_default_timezone_set($account_data->fields['TIMEZONE']);
            $time_zone = 1;
        }else{
            $time_zone = 0;
        }

        $PERMISSION_ARRAY = [];
        $permission_data = $db->Execute("SELECT DOA_PERMISSION.PERMISSION_NAME FROM DOA_PERMISSION LEFT JOIN DOA_ROLES_PERMISSION ON DOA_PERMISSION.PK_PERMISSION = DOA_ROLES_PERMISSION.PK_PERMISSION LEFT JOIN DOA_USER_ROLES ON DOA_ROLES_PERMISSION.PK_ROLES = DOA_USER_ROLES.PK_ROLES WHERE DOA_USER_ROLES.PK_USER = '$_SESSION[PK_USER]'");
        while (!$permission_data->EOF) {
            $PERMISSION_ARRAY[] = $permission_data->fields['PERMISSION_NAME'];
            $permission_data->MoveNext();
        }
    }
}

$results_per_page = 50;