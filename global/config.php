<?php
ini_set('session.gc_maxlifetime', 36000); // 30 minutes
ini_set('session.cookie_lifetime', 36000); // 30 minutes cookie lifetime
session_start();
ob_start();
require_once('query_factory.php');
require_once('common_functions.php');
require_once('helper_function.php');
$db = new queryFactory();
$master_database = 'DOA_MASTER';
if ($_SERVER['HTTP_HOST'] == 'localhost') {
    $conn = $db->connect('localhost', 'root', '', $master_database);
    $http_path = 'http://localhost/doable/';
} else {
    $conn = $db->connect('localhost', 'root', 'b54eawxj5h8ev', $master_database);
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
$upload_path = '';

if ($env === 'dev') {
    define("client_id", "5c896c023b775026fc5e3352_gcd6pouyir4s8c0cwg04ss0ck8s0gcgkoog4wcw00ko0ssksg");
    define("client_secret", "62d7w06sfssg8k4gcgg88kokocg48gg0gk08c80wgosgks4ok4");
    define("ami_api_url", "https://api.arthurmurrayfranchisee.com");
} else {
    define("client_id", "5c896bf53b775025ca4ebd0f_1nw82cn6cbz4os80w0wc4www88gwgo4wgc48kg88sg884ss48c");
    define("client_secret", "16ovel8dax340o8kko8w0c8g8s80ss00k8w8kw84ocgc8k8w80");
    define("ami_api_url", "https://reporting.arthurmurray.com");
}

if ($db->error_number) {
    die("Master Database Connection Error");
} else {
    if (!empty($_SESSION['PK_ACCOUNT_MASTER'])) {
        $account_data = $db->Execute("SELECT DOA_ACCOUNT_MASTER.BUSINESS_NAME, DOA_BUSINESS_TYPE.AMI_ENABLE FROM DOA_ACCOUNT_MASTER LEFT JOIN DOA_BUSINESS_TYPE ON DOA_ACCOUNT_MASTER.PK_BUSINESS_TYPE = DOA_BUSINESS_TYPE.PK_BUSINESS_TYPE WHERE DOA_ACCOUNT_MASTER.PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
        $AMI_ENABLE = $account_data->fields['AMI_ENABLE'];

        $location_data = $db->Execute("SELECT DOA_LOCATION.SERVICE_PROVIDER_TITLE, DOA_LOCATION.OPERATION_TAB_TITLE, DOA_TIMEZONE.TIMEZONE FROM DOA_LOCATION LEFT JOIN DOA_TIMEZONE ON DOA_LOCATION.PK_TIMEZONE = DOA_TIMEZONE.PK_TIMEZONE WHERE DOA_LOCATION.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ")");
        /* $business_logo = $location_data->fields['BUSINESS_LOGO'];
        $business_name = $location_data->fields['BUSINESS_NAME']; */
        $business_logo = '';
        $business_name = '';

        if ($location_data->RecordCount() == 0 || ($location_data->fields['SERVICE_PROVIDER_TITLE'] == NULL || $location_data->fields['SERVICE_PROVIDER_TITLE'] == ''))
            $service_provider_title = 'Service Provider';
        else
            $service_provider_title = $location_data->fields['SERVICE_PROVIDER_TITLE'];

        if ($location_data->RecordCount() == 0 || ($location_data->fields['OPERATION_TAB_TITLE'] == NULL || $location_data->fields['OPERATION_TAB_TITLE'] == ''))
            $operation_tab_title = 'Operations';
        else
            $operation_tab_title = $location_data->fields['OPERATION_TAB_TITLE'];

        $currency = '$';

        /* if (is_null($location_data->fields['CURRENCY_SYMBOL']))
            $currency = '$';
        else
            $currency = $location_data->fields['CURRENCY_SYMBOL']; */

        if ($location_data->RecordCount() > 0 &&  !is_null($location_data->fields['TIMEZONE'])) {
            date_default_timezone_set($location_data->fields['TIMEZONE']);
            $time_zone = 1;
        } else {
            $time_zone = 0;
        }

        $upload_path = 'uploads/' . $_SESSION['PK_ACCOUNT_MASTER'];

        $PERMISSION_ARRAY = [];
        $permission_data = $db->Execute("SELECT DOA_PERMISSION.PERMISSION_NAME FROM DOA_PERMISSION LEFT JOIN DOA_ROLES_PERMISSION ON DOA_PERMISSION.PK_PERMISSION = DOA_ROLES_PERMISSION.PK_PERMISSION WHERE DOA_ROLES_PERMISSION.PK_ROLES = '$_SESSION[PK_ROLES]'");
        while (!$permission_data->EOF) {
            $PERMISSION_ARRAY[] = $permission_data->fields['PERMISSION_NAME'];
            $permission_data->MoveNext();
        }
    }
}

$results_per_page = 50;
