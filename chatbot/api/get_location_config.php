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
                'label'        => $account_data->fields['OFFERING_LABEL'],
                'type'         => $account_data->fields['OFFERING_TYPE'],
                'is_offerings' => true
            ],
            [
                'label'        => 'Pricing',
                'type'         => 'pricing',
                'is_offerings' => false
            ],
            [
                'label'        => 'Book Appointment',
                'type'         => 'booking',
                'is_offerings' => false
            ],
            [
                'label'        => 'Contact Us',
                'type'         => 'contact',
                'is_offerings' => false
            ]
        ];
        $location_result = $db->Execute("SELECT DOA_LOCATION.*, DOA_CORPORATION.CORPORATION_NAME FROM DOA_LOCATION LEFT JOIN DOA_CORPORATION ON DOA_LOCATION.PK_CORPORATION = DOA_CORPORATION.PK_CORPORATION WHERE DOA_LOCATION.ACTIVE = 1 AND DOA_LOCATION.LOCATION_CODE = '$location_id' AND DOA_LOCATION.PK_ACCOUNT_MASTER = " . $account_id);
        $booking_periods = [];
        while (!$location_result->EOF) {
            $booking_periods = [
                ($location_result->fields['IS_MORNING'] == 1) ? 'M' : '',
                ($location_result->fields['IS_AFTERNOON'] == 1) ? 'A' : '',
                ($location_result->fields['IS_EVENING'] == 1) ? 'E' : '',
                ($location_result->fields['IS_NIGHT'] == 1) ? 'N' : ''
            ];

            $locations[] = [
                'location_id' => $location_result->fields['LOCATION_CODE'],
                'name' => $location_result->fields['LOCATION_NAME'],
                'phone' => $location_result->fields['PHONE'],
                'booking_periods' => array_values(array_filter($booking_periods))
            ];
            $location_result->MoveNext();
        }

        $account_config = [
            "account_id" => $account_data->fields['PK_ACCOUNT_MASTER'],
            "business_name" => $location_result->fields['CORPORATION_NAME'],
            "avatar_emoji" => $account_data->fields['AVATAR_EMOJI'],
            "primary_color" => $account_data->fields['PRIMARY_COLOR'],
            "welcome_message" => $account_data->fields['WELCOME_MESSAGE'],
            "languages" => ['en', 'es'],  // ← hardcoded for now
            "locations" => $locations,
            "menu_options" => $menu_options,
        ];

        $return_data['status'] = 'success';
        $return_data['data'] = $account_config;
        echo json_encode($return_data);
        exit;
    }
}
