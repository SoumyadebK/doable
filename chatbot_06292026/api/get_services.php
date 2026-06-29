<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
//session_start(); // ← start session ourselves before config.php does
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
        $DB_NAME = $account_data->fields['DB_NAME'];
        $db_account = new queryFactory();
        if ($_SERVER['HTTP_HOST'] == 'localhost') {
            $conn1 = $db_account->connect('localhost', 'root', '', $DB_NAME);
            $http_path = 'http://localhost/doable/';
        } else {
            $conn1 = $db_account->connect('localhost', 'root', 'b54eawxj5h8ev', $DB_NAME);
            $http_path = 'https://doable.net/';
        }

        if ($db_account->error_number) {
            die("Connection Error");
        }

        $package_data = $db_account->Execute("SELECT * FROM DOA_PACKAGE WHERE ACTIVE = 1 AND CHATBOT_ENABLED = 1 AND IS_DELETED = 0 ORDER BY SORT_ORDER ASC LIMIT 1");
        if ($package_data->RecordCount() == 0) {
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
        } else {
            $package_services = $db_account->Execute("SELECT DOA_PACKAGE_SERVICE.*, DOA_SERVICE_MASTER.* FROM DOA_PACKAGE_SERVICE LEFT JOIN DOA_SERVICE_MASTER ON DOA_PACKAGE_SERVICE.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER WHERE DOA_PACKAGE_SERVICE.ACTIVE = 1 AND DOA_PACKAGE_SERVICE.PK_PACKAGE = " . $package_data->fields['PK_PACKAGE']);

            $services = [];
            while (!$package_services->EOF) {
                $services[] = [
                    'service_id' => $package_services->fields['PK_SERVICE_MASTER'],
                    'name' => $package_services->fields['SERVICE_NAME'],
                    'description' => $package_services->fields['DESCRIPTION'],
                    'number_of_session' => $package_services->fields['NUMBER_OF_SESSION'],
                    'price_per_session' => $package_services->fields['PRICE_PER_SESSION']
                ];
                $package_services->MoveNext();
            }
        }

        $return_data['status'] = 'success';
        $return_data['data'] = $services;
        echo json_encode($return_data);
    }
}
