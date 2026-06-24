<?php
header('Content-Type: application/json');
require_once("../../global/config.php");

$account_id = $_POST['account'] ?? null;
$location_id = $_POST['location'] ?? null;
$date = $_POST['date'] ?? null;
$slot = $_POST['slot'] ?? null;
$name = $_POST['name'] ?? null;
$phone = $_POST['phone'] ?? null;
$address = $_POST['address'] ?? null;

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
        $return_data['status'] = 'success';
        $return_data['data'] = 'Appointment booked successfully at ' . $slot . ' on ' . $date . ' for ' . $name . '.';
        echo json_encode($return_data);
    }
}
