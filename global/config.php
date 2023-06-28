<?php
session_start();
ob_start();
require_once('query_factory.php');
require_once('common_functions.php');
$db = new queryFactory();
if($_SERVER['HTTP_HOST'] == 'localhost' ) {
    $conn = $db->connect('localhost','root','','doable');
    $http_path = 'http://localhost/doable/';
} else {
    $conn = $db->connect('localhost','root','b54eawxj5h8ev','doable');
    $http_path = 'http://allonehub.com/';
}
if ($db->error_number){
    die("Connection Error");
}else{
    if (!empty($_SESSION['PK_ACCOUNT_MASTER'])){
        $user_data = $db->Execute("SELECT PK_LOCATION FROM DOA_USERS WHERE PK_USER = '$_SESSION[PK_USER]'");
        if ($user_data->fields['PK_LOCATION'] != 0){
            $time_zone_data = $db->Execute("SELECT DOA_TIMEZONE.TIMEZONE, DOA_LOCATION.IMAGE_PATH FROM DOA_TIMEZONE RIGHT JOIN DOA_LOCATION ON DOA_TIMEZONE.PK_TIMEZONE = DOA_LOCATION.PK_TIMEZONE WHERE DOA_LOCATION.PK_LOCATION = ".$user_data->fields['PK_LOCATION']."");
            $business_logo = $time_zone_data->fields['IMAGE_PATH'];
            if (!is_null($time_zone_data->fields['TIMEZONE'])) {
                date_default_timezone_set($time_zone_data->fields['TIMEZONE']);
                $time_zone = 1;
            }else{
                $time_zone = 0;
            }
        }else{
            $time_zone_data = $db->Execute("SELECT DOA_TIMEZONE.TIMEZONE, DOA_ACCOUNT_MASTER.BUSINESS_LOGO FROM DOA_TIMEZONE RIGHT JOIN DOA_ACCOUNT_MASTER ON DOA_TIMEZONE.PK_TIMEZONE = DOA_ACCOUNT_MASTER.PK_TIMEZONE WHERE DOA_ACCOUNT_MASTER.PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
            $business_logo = $time_zone_data->fields['BUSINESS_LOGO'];
            if (!is_null($time_zone_data->fields['TIMEZONE'])) {
                date_default_timezone_set($time_zone_data->fields['TIMEZONE']);
                $time_zone = 1;
            }else{
                $time_zone = 0;
            }
        }

        $account_data = $db->Execute("SELECT DOA_ACCOUNT_MASTER.SERVICE_PROVIDER_TITLE, DOA_ACCOUNT_MASTER.BUSINESS_NAME, DOA_CURRENCY.CURRENCY_SYMBOL FROM DOA_ACCOUNT_MASTER LEFT JOIN DOA_CURRENCY ON DOA_CURRENCY.PK_CURRENCY = DOA_ACCOUNT_MASTER.PK_CURRENCY WHERE DOA_ACCOUNT_MASTER.PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
        $business_name = $account_data->fields['BUSINESS_NAME'];
        if ($account_data->fields['SERVICE_PROVIDER_TITLE'] == NULL || $account_data->fields['SERVICE_PROVIDER_TITLE'] == '')
            $service_provider_title = 'Service Provider';
        else
            $service_provider_title = $account_data->fields['SERVICE_PROVIDER_TITLE'];

        if (is_null($account_data->fields['CURRENCY_SYMBOL']))
            $currency = '$';
        else
            $currency = $account_data->fields['CURRENCY_SYMBOL'];

        $DEFAULT_LOCATION_ID = (!empty($_SESSION['DEFAULT_LOCATION_ID']) && $_SESSION['DEFAULT_LOCATION_ID'] > 0)?$_SESSION['DEFAULT_LOCATION_ID']:0;
    }
}