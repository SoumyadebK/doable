<?php
require_once('../../global/config.php');
global $db;
global $db_account;
global $master_database;

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];

$SERVICE_PROVIDER_ID = '';
if(isset($_POST['selected_service_provider']) && $_POST['selected_service_provider'] != ''){
    $service_providers = implode(',', $_POST['selected_service_provider']);
    $SERVICE_PROVIDER_ID = " AND DOA_USERS.PK_USER IN (".$service_providers.") ";
}

$service_provider_data = $db->Execute("SELECT DISTINCT
                                                DOA_USERS.PK_USER,
                                                CONCAT(
                                                    DOA_USERS.FIRST_NAME,
                                                    ' ',
                                                    DOA_USERS.LAST_NAME
                                                ) AS NAME
                                            FROM
                                                DOA_USERS
                                            INNER JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER
                                            INNER JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER
                                            WHERE DOA_USER_ROLES.PK_ROLES = 5 AND ACTIVE = 1 AND DOA_USER_LOCATION.PK_LOCATION IN( ".$DEFAULT_LOCATION_ID." ) 
                                            ".$SERVICE_PROVIDER_ID." AND DOA_USERS.PK_ACCOUNT_MASTER = " . $_SESSION['PK_ACCOUNT_MASTER']. "
                                            ORDER BY DISPLAY_ORDER");
$resourceIdArray = [];
while (!$service_provider_data->EOF) {
    $resourceIdArray [] = [
        'id' =>  $service_provider_data->fields['PK_USER'],
        'title' => $service_provider_data->fields['NAME'].' - 0'
    ];
    $service_provider_data->MoveNext();
}

echo json_encode($resourceIdArray);