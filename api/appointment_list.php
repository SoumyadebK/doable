<?php
require_once("../global/config.php");

$CUSTOMER_ID = $_POST['CUSTOMER_ID'];

$user_master_data = $account = $db->Execute("SELECT * FROM DOA_USER_MASTER WHERE PK_USER = ".$CUSTOMER_ID);
$PK_USER_MASTER_ARRAY = [];
$PK_ACCOUNT_MASTER_ARRAY = [];
while (!$user_master_data->EOF){
    $PK_USER_MASTER_ARRAY[] = $user_master_data->fields['PK_USER_MASTER'];
    $PK_ACCOUNT_MASTER_ARRAY[] = $user_master_data->fields['PK_ACCOUNT_MASTER'];
    $user_master_data->MoveNext();
}
$PK_USER_MASTERS = implode(',', $PK_USER_MASTER_ARRAY);

$account_data = $db->Execute("SELECT * FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER = ".$user_master_data->fields['PK_ACCOUNT_MASTER']);
require_once('../global/common_functions_account.php');
$account_database = $account_data->fields['DB_NAME'];
$db_account = new queryFactory();
if ($_SERVER['HTTP_HOST'] == 'localhost') {
    $conn_account = $db_account->connect('localhost', 'root', '', $account_database);
} else {
    $conn_account = $db_account->connect('localhost', 'root', 'b54eawxj5h8ev', $account_database);
}

$appointment_data = $db_account->Execute("SELECT DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER, DOA_APPOINTMENT_MASTER.DATE, DOA_APPOINTMENT_MASTER.START_TIME, DOA_APPOINTMENT_MASTER.END_TIME, DOA_APPOINTMENT_MASTER.IS_PAID, DOA_ENROLLMENT_MASTER.ENROLLMENT_ID, DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS, CONCAT(CUSTOMER.FIRST_NAME, ' ', CUSTOMER.LAST_NAME) AS CUSTOMER_NAME, CONCAT(SERVICE_PROVIDER.FIRST_NAME, ' ', SERVICE_PROVIDER.LAST_NAME) AS SERVICE_PROVIDER_NAME, DOA_SERVICE_MASTER.SERVICE_NAME, DOA_SERVICE_CODE.SERVICE_CODE, DOA_APPOINTMENT_MASTER.ACTIVE FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_SERVICE_MASTER ON DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_USER_MASTER.PK_USER_MASTER = DOA_APPOINTMENT_MASTER.CUSTOMER_ID INNER JOIN $master_database.DOA_USERS AS CUSTOMER ON DOA_USER_MASTER.PK_USER = CUSTOMER.PK_USER LEFT JOIN $master_database.DOA_USERS AS SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.SERVICE_PROVIDER_ID = SERVICE_PROVIDER.PK_USER LEFT JOIN DOA_SERVICE_CODE ON DOA_APPOINTMENT_MASTER.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_APPOINTMENT_MASTER.STATUS = 'A' AND DOA_APPOINTMENT_MASTER.CUSTOMER_ID IN (".$PK_USER_MASTERS.") ORDER BY DOA_APPOINTMENT_MASTER.DATE DESC");

if($appointment_data->RecordCount() == 0){
    $return_data['success'] = 0;
    $return_data['message'] = 'No record found.';
    echo json_encode($return_data); exit;
} else {
    $i = 0;
    $data = [];
    while (!$appointment_data->EOF) {
        $data[$i] = $appointment_data->fields;
        $i++;
        $appointment_data->MoveNext();
    }
    $return_data['success'] = 1;
    $return_data['message'] = 'Success';
    $return_data['data'] = $data;
    echo json_encode($return_data); exit;
}