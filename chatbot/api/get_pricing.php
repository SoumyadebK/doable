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

        $package = [];
        $package_data = $db_account->Execute("SELECT * FROM DOA_PACKAGE WHERE ACTIVE = 1 AND CHATBOT_ENABLED = 1 AND IS_DELETED = 0 ORDER BY SORT_ORDER ASC");
        while (!$package_data->EOF) {

            $package_services = $db_account->Execute("SELECT DOA_PACKAGE_SERVICE.*, DOA_SERVICE_MASTER.* FROM DOA_PACKAGE_SERVICE LEFT JOIN DOA_SERVICE_MASTER ON DOA_PACKAGE_SERVICE.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER WHERE DOA_PACKAGE_SERVICE.ACTIVE = 1 AND DOA_PACKAGE_SERVICE.PK_PACKAGE = " . $package_data->fields['PK_PACKAGE']);
            $services = [];
            while (!$package_services->EOF) {
                $services[] = [
                    'service_id' => $package_services->fields['PK_SERVICE_MASTER'],
                    'name' => $package_services->fields['SERVICE_NAME'],
                    'description' => $package_services->fields['DESCRIPTION']
                ];
                $package_services->MoveNext();
            }

            $package_price = $db_account->Execute("SELECT DOA_PACKAGE_SERVICE.*, DOA_PACKAGE.* FROM DOA_PACKAGE_SERVICE LEFT JOIN DOA_PACKAGE ON DOA_PACKAGE_SERVICE.PK_PACKAGE = DOA_PACKAGE.PK_PACKAGE WHERE DOA_PACKAGE_SERVICE.PK_PACKAGE = " . $package_data->fields['PK_PACKAGE'] . " AND DOA_PACKAGE.ACTIVE = 1 AND DOA_PACKAGE_SERVICE.CHATBOT_ENABLED = 1 AND DOA_PACKAGE.IS_DELETED = 0 ORDER BY DOA_PACKAGE.SORT_ORDER ASC LIMIT 1");
            $num = $package_price->fields['NUMBER_OF_SESSION'];
            $package[] = [
                'package_id' => $package_data->fields['PK_PACKAGE'],
                'name' => $package_data->fields['PACKAGE_NAME'],
                'description' => $package_data->fields['PACKAGE_DESCRIPTION'],
                'label'    => $num . ($num == 1 ? ' Class' : ' Classes'),
                'price'    => '$' . number_format($package_price->fields['FINAL_AMOUNT'], 2),
                'note'     => $package_price->fields['SERVICE_DETAILS'],
                'services' => $services
            ];
            $package_data->MoveNext();
        }

        $return_data['status'] = 'success';
        $return_data['data'] = $package;
        echo json_encode($return_data);
    }
}
