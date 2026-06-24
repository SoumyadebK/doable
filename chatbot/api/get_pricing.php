<?php
header('Content-Type: application/json');
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

    if ($account_data->RecordCount() == 0) {
        $return_data['status'] = 'error';
        $return_data['message'] = 'Account not found.';
        echo json_encode($return_data);
        exit;
    } else {
        $pricing = [
            [
                "price_id" => "P001",
                "label" => "3 Classes",
                "price" => "$149",
                "note" => "Great way to get started!"
            ]
        ];

        $return_data['status'] = 'success';
        $return_data['data'] = $pricing;
        echo json_encode($return_data);
    }
}
