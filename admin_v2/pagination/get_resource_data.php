<?php
require_once('../../global/config.php');
global $db;
global $db_account;
global $master_database;

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];

$SERVICE_PROVIDER_ID = '';
if (!empty($_POST['selected_service_provider']) && is_array($_POST['selected_service_provider'])) {
    // SECURITY: cast to int - these values go straight into SQL.
    $service_providers = implode(',', array_filter(array_map('intval', $_POST['selected_service_provider'])));
    if ($service_providers !== '') {
        $SERVICE_PROVIDER_ID = " AND DOA_USERS.PK_USER IN (" . $service_providers . ") ";
    }
}

$service_provider_data = $db->Execute("SELECT DISTINCT
                                                DOA_USERS.PK_USER,
                                                CONCAT(
                                                    DOA_USERS.FIRST_NAME,
                                                    ' ',
                                                    DOA_USERS.LAST_NAME
                                                ) AS NAME,
                                                DOA_USERS.DISPLAY_ORDER
                                            FROM
                                                DOA_USERS
                                            INNER JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER
                                            WHERE DOA_USERS.APPEAR_IN_CALENDAR = 1 AND DOA_USERS.ACTIVE = 1 AND (DOA_USERS.IS_DELETED = 0 OR DOA_USERS.IS_DELETED IS NULL) AND DOA_USER_LOCATION.PK_LOCATION IN( " . $DEFAULT_LOCATION_ID . " )
                                            " . $SERVICE_PROVIDER_ID . " AND DOA_USERS.PK_ACCOUNT_MASTER = " . (int) $_SESSION['PK_ACCOUNT_MASTER'] . "
                                            ORDER BY DOA_USERS.DISPLAY_ORDER ASC");
$resourceIdArray = [];
while (!$service_provider_data->EOF) {
    $resourceIdArray[] = [
        'id' =>  $service_provider_data->fields['PK_USER'],
        'title' => $service_provider_data->fields['NAME'],
        'sortOrder' => (int) $service_provider_data->fields['DISPLAY_ORDER'],
    ];
    $service_provider_data->MoveNext();
}

header('Content-Type: application/json');
echo json_encode($resourceIdArray);
