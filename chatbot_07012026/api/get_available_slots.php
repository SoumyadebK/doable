<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
//session_start(); // ← start session ourselves before config.php does
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

        $PK_USER_MORNING = explode(',', $location_data->fields['PK_USER_MORNING'] ?? null);
        $PK_USER_AFTERNOON = explode(',', $location_data->fields['PK_USER_AFTERNOON'] ?? null);
        $PK_USER_EVENING = explode(',', $location_data->fields['PK_USER_EVENING'] ?? null);
        $PK_USER_NIGHT = explode(',', $location_data->fields['PK_USER_NIGHT'] ?? null);


        $slot_duration = '00:30:00'; // Default slot duration
        $slot_data = [];
        $seen_slots = [];
        $provider_ids = array_filter(array_merge($PK_USER_MORNING, $PK_USER_AFTERNOON, $PK_USER_EVENING, $PK_USER_NIGHT));

        foreach ($provider_ids as $provider_id) {
            $available_slots = getAvailableSlots($db_account, $provider_id, $PK_LOCATION, $date, $slot_duration);
            foreach ($available_slots as $slot) {
                $slot_key = $slot['slot_start_time'] . '|' . $slot['slot_end_time'];
                if (!isset($seen_slots[$slot_key])) {
                    $seen_slots[$slot_key] = true;
                    $slot_data[] = $slot;
                }
            }
        }

        $return_data['status'] = 'success';
        $return_data['data'] = $slot_data;
        echo json_encode($return_data);
    }
}

function getAvailableSlots($db_account, $provider_id, $location_id, $date, $slot_duration)
{

    $provider_id   = (int)$provider_id;
    $location_id   = (int)$location_id;
    $date          = addslashes($date);
    $slot_duration = addslashes($slot_duration);

    // Get provider working hours for the day
    $day_name = strtoupper(substr(date('l', strtotime($date)), 0, 3));
    $dayNumber = date('N', strtotime($date));

    $sql_hours = "
        SELECT 
            {$day_name}_START_TIME AS WORK_START,
            {$day_name}_END_TIME AS WORK_END
        FROM DOA_SERVICE_PROVIDER_LOCATION_HOURS
        WHERE PK_USER = $provider_id AND PK_LOCATION = $location_id
    ";

    $hours_data = $db_account->Execute($sql_hours);
    $hours = ($hours_data->RecordCount() > 0) ? $hours_data->fields : null;

    if (!$hours || !$hours['WORK_START'] || !$hours['WORK_END']) return [];

    $location_operational_hour = $db_account->Execute("SELECT MIN(DOA_OPERATIONAL_HOUR.OPEN_TIME) AS OPEN_TIME, MAX(DOA_OPERATIONAL_HOUR.CLOSE_TIME) AS CLOSE_TIME FROM DOA_OPERATIONAL_HOUR WHERE DAY_NUMBER = '$dayNumber' AND PK_LOCATION = $location_id");
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
    $duration   = strtotime("1970-01-01 $slot_duration UTC") - strtotime("1970-01-01 00:00:00 UTC");

    // Get all booked slots
    $sql_booked = "
        SELECT START_TIME, END_TIME
        FROM DOA_APPOINTMENT_MASTER a
        LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER sp
            ON a.PK_APPOINTMENT_MASTER = sp.PK_APPOINTMENT_MASTER
        WHERE a.PK_LOCATION = $location_id
            AND a.PK_APPOINTMENT_STATUS NOT IN (2,6)
            AND sp.PK_USER = $provider_id
            AND a.DATE = '$date'
        UNION ALL
        SELECT START_TIME, END_TIME
        FROM DOA_SPECIAL_APPOINTMENT sa
        LEFT JOIN DOA_SPECIAL_APPOINTMENT_USER sau
            ON sa.PK_SPECIAL_APPOINTMENT = sau.PK_SPECIAL_APPOINTMENT
        WHERE sa.PK_LOCATION = $location_id
            AND sa.PK_APPOINTMENT_STATUS NOT IN (2,6)
            AND sau.PK_USER = $provider_id
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
