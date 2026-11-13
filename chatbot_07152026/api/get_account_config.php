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
        $location_result = $db->Execute("SELECT * FROM DOA_LOCATION WHERE ACTIVE = 1 AND PK_ACCOUNT_MASTER = " . $account_id);
        $booking_periods = [];
        while (!$location_result->EOF) {
            $booking_periods = [
                ($location_result->fields['IS_MORNING'] == 1) ? 'M' : '',
                ($location_result->fields['IS_AFTERNOON'] == 1) ? 'A' : '',
                ($location_result->fields['IS_EVENING'] == 1) ? 'E' : '',
                ($location_result->fields['IS_NIGHT'] == 1) ? 'N' : ''
            ];

            $menu_options = [
                [
                    'label'        => $location_result->fields['OFFERING_LABEL'],
                    'type'         => $location_result->fields['OFFERING_TYPE'],
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
                ],
                [
                    'label'        => 'FAQ',
                    'type'         => 'faq',
                    'is_offerings' => false
                ]
            ];

            $faq = [];
            $faq_result = $db->Execute("SELECT * FROM DOA_LOCATION_FAQ WHERE PK_LOCATION = " . $location_result->fields['PK_LOCATION']);
            while (!$faq_result->EOF) {
                $faq[] = [
                    'question' => $faq_result->fields['QUESTION'],
                    'answer' => $faq_result->fields['ANSWER']
                ];
                $faq_result->MoveNext();
            }

            $locations[] = [
                'location_id' => $location_result->fields['LOCATION_CODE'],
                'name' => $location_result->fields['LOCATION_NAME'],
                'phone' => $location_result->fields['PHONE'],
                'booking_periods' => array_values(array_filter($booking_periods)),
                "avatar_emoji" => $location_result->fields['AVATAR_EMOJI'],
                "primary_color" => $location_result->fields['PRIMARY_COLOR'],
                "welcome_message" => $location_result->fields['WELCOME_MESSAGE'],
                "menu_options" => $menu_options,
                "faq" => $faq
            ];
            $location_result->MoveNext();
        }

        $account_config = [
            "account_id" => $account_data->fields['PK_ACCOUNT_MASTER'],
            "business_name" => $account_data->fields['BUSINESS_NAME'],
            "languages" => ['en', 'es'],  // ← hardcoded for now
            "locations" => $locations,
        ];

        $return_data['status'] = 'success';
        $return_data['data'] = $account_config;
        echo json_encode($return_data);
        exit;
    }
}
