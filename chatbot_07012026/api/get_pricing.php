<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
//session_start(); // ← start session ourselves before config.php does
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

        $package_services = $db_account->Execute("SELECT DOA_PACKAGE_SERVICE.*, DOA_PACKAGE.* FROM DOA_PACKAGE_SERVICE LEFT JOIN DOA_PACKAGE ON DOA_PACKAGE_SERVICE.PK_PACKAGE = DOA_PACKAGE.PK_PACKAGE WHERE DOA_PACKAGE.ACTIVE = 1 AND DOA_PACKAGE_SERVICE.CHATBOT_ENABLED = 1 AND DOA_PACKAGE.IS_DELETED = 0 ORDER BY DOA_PACKAGE.SORT_ORDER ASC LIMIT 1");

        if ($package_services->RecordCount() == 0) {
            $pricing = [
                [
                    "price_id" => "P001",
                    "label" => "3 Classes",
                    "price" => "$149",
                    "note" => "Great way to get started!"
                ]
            ];
        } else {
            $pricing = [];
            while (!$package_services->EOF) {
                $pricing[] = [
                    'price_id' => $package_services->fields['PK_PACKAGE'],
                    'label' => $package_services->fields['NUMBER_OF_SESSION'] . ' Classes',
                    'price' => '$' . number_format($package_services->fields['PRICE_PER_SESSION'], 2),
                    'note' => $package_services->fields['SERVICE_DETAILS']
                ];
                $package_services->MoveNext();
            }
        }

        $return_data['status'] = 'success';
        $return_data['data'] = $pricing;
        echo json_encode($return_data);
    }
}
