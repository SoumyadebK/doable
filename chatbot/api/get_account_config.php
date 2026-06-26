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
    $account_data = $db->Execute("SELECT * FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER = " . $account_id);

    if ($account_data->RecordCount() == 0) {
        $return_data['status'] = 'error';
        $return_data['message'] = 'Account not found.';
        echo json_encode($return_data);
        exit;
    } else {
        $locations = [];
        $menu_options = [
            [
                "label" => "Types of Dances",
                "type" => "dances"
            ],
            [
                "label" => "Pricing",
                "type" => "pricing"
            ],
            [
                "label" => "Book Appointment",
                "type" => "booking"
            ],
            [
                "label" => "Contact Us",
                "type" => "contact"
            ]
        ];
        $location_result = $db->Execute("SELECT * FROM DOA_LOCATION WHERE ACTIVE = 1 AND PK_ACCOUNT_MASTER = " . $account_id);
        while (!$location_result->EOF) {
            $locations[] = [
                'location_id' => $location_result->fields['LOCATION_CODE'],
                'name' => $location_result->fields['LOCATION_NAME'],
                'phone' => $location_result->fields['PHONE'],
            ];
            $location_result->MoveNext();
        }

        $account_config = [
            'account_id' => $account_data->fields['PK_ACCOUNT_MASTER'],
            'business_name' => $account_data->fields['BUSINESS_NAME'],
            "avatar_emoji" => "",
            "primary_color" => "#39b54a",
            "welcome_message" => "Welcome to Arthur Murray Dance Studios...",
            "locations" => $locations,
            "menu_options" => $menu_options,
        ];

        $return_data['status'] = 'success';
        $return_data['data'] = $account_config;
        echo json_encode($return_data);
        exit;
    }
}
