<?php
session_start();
ob_start();
require_once('query_factory.php');
require_once('common_functions.php');
$db = new queryFactory();
if($_SERVER['HTTP_HOST'] == 'localhost' ) {
    $conn = $db->connect('localhost','root','','doable_master');
    $http_path = 'http://localhost/doable/';
} else {
    $conn = $db->connect('localhost','root','b54eawxj5h8ev','doable');
    $http_path = 'http://allonehub.com/';
}

if (!empty($_SESSION['DB_NAME'])) {
    require_once('common_functions_account.php');
    $db_account = new queryFactory();
    if ($_SERVER['HTTP_HOST'] == 'localhost') {
        $conn_account = $db_account->connect('localhost', 'root', '', $_SESSION['DB_NAME']);
    } else {
        $conn_account = $db_account->connect('localhost', 'root', 'b54eawxj5h8ev', 'doable');
    }
    if ($db_account->error_number)
        die("Account Database Connection Error");
}

if ($db->error_number){
    die("Master Database Connection Error");
}else{
    if (!empty($_SESSION['PK_ACCOUNT_MASTER'])){
        $account_data = $db->Execute("SELECT DOA_ACCOUNT_MASTER.SERVICE_PROVIDER_TITLE, DOA_ACCOUNT_MASTER.BUSINESS_NAME, DOA_ACCOUNT_MASTER.BUSINESS_LOGO, DOA_TIMEZONE.TIMEZONE, DOA_CURRENCY.CURRENCY_SYMBOL FROM DOA_TIMEZONE RIGHT JOIN DOA_ACCOUNT_MASTER ON DOA_TIMEZONE.PK_TIMEZONE = DOA_ACCOUNT_MASTER.PK_TIMEZONE LEFT JOIN DOA_CURRENCY ON DOA_CURRENCY.PK_CURRENCY = DOA_ACCOUNT_MASTER.PK_CURRENCY WHERE DOA_ACCOUNT_MASTER.PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
        $business_logo = $account_data->fields['BUSINESS_LOGO'];
        $business_name = $account_data->fields['BUSINESS_NAME'];
        if ($account_data->fields['SERVICE_PROVIDER_TITLE'] == NULL || $account_data->fields['SERVICE_PROVIDER_TITLE'] == '')
            $service_provider_title = 'Service Provider';
        else
            $service_provider_title = $account_data->fields['SERVICE_PROVIDER_TITLE'];

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
    }
}