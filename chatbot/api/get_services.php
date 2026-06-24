<?php
header('Content-Type: application/json');
require_once("../../global/config.php");

$account_id = $_GET['account'] ?? null;

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
        $services = [
            [
                "service_id" => "S001",
                "name" => "Salsa",
                "description" => "High-energy Latin dance..."
            ],
            [
                "service_id" => "S002",
                "name" => "Ballroom",
                "description" => "Elegant partner dance..."
            ],
            [
                "service_id" => "S003",
                "name" => "Tango",
                "description" => "Passionate Argentine style..."
            ],
            [
                "service_id" => "S004",
                "name" => "Swing",
                "description" => "Fun upbeat social dance..."
            ]
        ];

        $return_data['status'] = 'success';
        $return_data['data'] = $services;
        echo json_encode($return_data);
    }
}
