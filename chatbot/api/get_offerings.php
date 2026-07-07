<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
//session_start();
require_once("../../global/config.php");

$account_id = $_GET['account'] ?? null;
$location_id = $_GET['location'] ?? null;

if (!$account_id) {
    $return_data['status'] = 'error';
    $return_data['message'] = 'Account ID is required.';
    echo json_encode($return_data);
    exit;
} else {
    $account_data = $db->Execute("SELECT * FROM DOA_ACCOUNT_MASTER WHERE ACTIVE = 1 AND PK_ACCOUNT_MASTER = " . $account_id);
    $location_data = $db->Execute("SELECT * FROM DOA_LOCATION WHERE ACTIVE = 1 AND PK_ACCOUNT_MASTER = " . $account_id . " AND LOCATION_CODE = '$location_id'");
    $PK_LOCATION = $location_data->fields['PK_LOCATION'] ?? null;

    if ($account_data->RecordCount() == 0) {
        $return_data['status'] = 'error';
        $return_data['message'] = 'Account not found.';
        echo json_encode($return_data);
        exit;
    } else {
        $offerings_data = $db->Execute("SELECT * FROM DOA_LOCATION_OFFERINGS WHERE PK_LOCATION = " . $PK_LOCATION);

        $offerings = [];
        while (!$offerings_data->EOF) {
            $offerings[] = [
                'name'        => $offerings_data->fields['OFFERING'],
                'description' => $offerings_data->fields['DESCRIPTION'],
            ];
            $offerings_data->MoveNext();
        }

        $return_data['status'] = 'success';
        $return_data['data'] = $offerings;
        echo json_encode($return_data);
    }
}
