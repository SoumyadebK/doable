<?php
header('Content-Type: application/json');
require_once("../../global/config.php");

$account_id = $_GET['account'] ?? null;
$PK_LOCATION = $_GET['location'] ?? null;
$date = $_GET['date'] ?? null;

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
        $location_data = $db->Execute("SELECT * FROM DOA_LOCATION WHERE ACTIVE = 1 AND PK_ACCOUNT_MASTER = " . $account_id . " AND LOCATION_CODE = '$PK_LOCATION'");
        $PK_LOCATION = $location_data->fields['PK_LOCATION'] ?? null;

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

        $slot_data = getAvailableSlots($db_account, $PK_LOCATION, 30, $date);

        $return_data['status'] = 'success';
        $return_data['data'] = $slot_data;
        echo json_encode($return_data);
    }
}

function getAvailableSlots($db_account, $PK_LOCATION, $slot_duration, $date = null)
{

    $PK_LOCATION   = (int)$PK_LOCATION;
    $date          = addslashes($date ?? date('Y-m-d'));
    $slot_duration = addslashes($slot_duration ?? 30);

    // Get provider working hours for the day
    $day_name = strtoupper(substr(date('l', strtotime($date)), 0, 3));
    $dayNumber = date('N', strtotime($date));

    $sql_hours = "
        SELECT 
            {$day_name}_START_TIME AS WORK_START,
            {$day_name}_END_TIME AS WORK_END
        FROM DOA_SERVICE_PROVIDER_LOCATION_HOURS
        WHERE PK_LOCATION = $PK_LOCATION
    ";

    $hours_data = $db_account->Execute($sql_hours);
    $hours = $hours_data->fields;

    if (!$hours || !$hours['WORK_START'] || !$hours['WORK_END']) return [];

    $location_operational_hour = $db_account->Execute("SELECT MIN(DOA_OPERATIONAL_HOUR.OPEN_TIME) AS OPEN_TIME, MAX(DOA_OPERATIONAL_HOUR.CLOSE_TIME) AS CLOSE_TIME FROM DOA_OPERATIONAL_HOUR WHERE DAY_NUMBER = '$dayNumber' AND PK_LOCATION = $PK_LOCATION");
    $LOCATION_SLOT_START = $location_operational_hour->fields['OPEN_TIME'] ?? '00:00:00';
    $LOCATION_SLOT_END = $location_operational_hour->fields['CLOSE_TIME'] ?? '23:00:00';

    if ($LOCATION_SLOT_START > $hours['WORK_START']) {
        $hours['WORK_START'] = $LOCATION_SLOT_START;
    } else {
        $hours['WORK_START'] = $hours['WORK_START'];
    }

    if ($LOCATION_SLOT_END > $hours['WORK_END']) {
        $hours['WORK_END'] = $hours['WORK_END'];
    } else {
        $hours['WORK_END'] = $LOCATION_SLOT_END;
    }

    $work_start = strtotime("$date " . $hours['WORK_START']);
    $work_end   = strtotime("$date " . $hours['WORK_END']);
    $duration   = $slot_duration * 60; // convert minutes to seconds

    // Get all booked slots
    $sql_booked = "
        SELECT START_TIME, END_TIME
        FROM DOA_APPOINTMENT_MASTER a
        WHERE a.PK_LOCATION = $PK_LOCATION
            AND a.PK_APPOINTMENT_STATUS NOT IN (2,6)
            AND a.DATE = '$date'
        UNION ALL
        SELECT START_TIME, END_TIME
        FROM DOA_SPECIAL_APPOINTMENT sa
        WHERE sa.PK_LOCATION = $PK_LOCATION
            AND sa.PK_APPOINTMENT_STATUS NOT IN (2,6)
            AND sa.DATE = '$date'
    ";

    $booked_data = $db_account->Execute($sql_booked);
    $booked_slots = [];
    while (!$booked_data->EOF) {
        $booked_slots[] = [
            'start' => strtotime("$date " . $booked_data->fields['START_TIME']),
            'end'   => strtotime("$date " . $booked_data->fields['END_TIME'])
        ];
        $booked_data->MoveNext();
    }

    // Sort booked slots by start time
    usort($booked_slots, function ($a, $b) {
        return $a['start'] - $b['start'];
    });

    $available_slots = [];
    $current = $work_start;

    foreach ($booked_slots as $b) {
        // If there is free time before this booked slot
        if ($current + $duration <= $b['start']) {
            $slot_start = $current;
            while ($slot_start + $duration <= $b['start']) {
                $available_slots[] = [
                    'slot_start_time' => date('H:i:s', $slot_start),
                    'slot_end_time'   => date('H:i:s', $slot_start + $duration)
                ];
                $slot_start += $duration;
            }
        }
        // Move current time after booked slot ends
        if ($current < $b['end']) {
            $current = $b['end'];
        }
    }

    // Check for any free slots after last booked slot until work_end
    while ($current + $duration <= $work_end) {
        $available_slots[] = [
            'slot_start_time' => date('H:i:s', $current),
            'slot_end_time'   => date('H:i:s', $current + $duration)
        ];
        $current += $duration;
    }

    return $available_slots;
}
