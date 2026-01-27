<?php
require_once('../../global/config.php');
global $db;
global $db_account;
global $master_database;

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];
/**
 * @throws Exception
 */
function getTimeSlot($interval, $start_time, $end_time): array
{
    $start = new DateTime($start_time);
    $end = new DateTime($end_time);
    $startTime = $start->format('H:i');
    $endTime = $end->format('H:i');
    $i = 0;
    $time = [];
    while ((strtotime($startTime) <= strtotime($endTime)) && ((strtotime($endTime) - strtotime($startTime) >= ($interval * 60)))) {
        $start = $startTime;
        $end = date('H:i', strtotime('+' . $interval . ' minutes', strtotime($startTime)));
        $startTime = date('H:i', strtotime('+' . $interval . ' minutes', strtotime($startTime)));
        $i++;
        if (strtotime($startTime) <= strtotime($endTime)) {
            $time[$i]['slot_start_time'] = $start;
            $time[$i]['slot_end_time'] = $end;
        }
    }
    return $time;
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
    $hours = $hours_data->fields;

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


$SERVICE_PROVIDER_ID = $_POST['SERVICE_PROVIDER_ID'];
$PK_LOCATION = $_POST['PK_LOCATION'];
$duration = intval($_POST['duration']);
$date = $_POST['date'];
$day = $_POST['day'];
$START_TIME = empty($_POST['START_TIME']) ? '09:00:00' : $_POST['START_TIME'];
$END_TIME = empty($_POST['END_TIME']) ? '22:00:00' : $_POST['END_TIME'];
$slot_time = empty($_POST['slot_time']) ? '' : $_POST['slot_time'];

if (empty($_POST['slot_time'])) {
    $is_disable = 0;
    $selected = "";
    $available_slots = getAvailableSlots($db_account, $SERVICE_PROVIDER_ID, $PK_LOCATION, $date, '00:' . $duration . ':00');
    foreach ($available_slots as $key => $item) { ?>
        <span data-is_disable="<?= $is_disable ?>" data-is_selected="<?= (($slot_time) ? 0 : (($selected) ? 1 : 0)) ?>" class="slot_btn <?= ($selected) ? 'selected_slot' : '' ?>" id="slot_btn_<?= $key ?>" onclick="set_time(this, <?= $key ?>, '<?= $item['slot_start_time'] ?>', '<?= $item['slot_end_time'] ?>')" style="<?= ($selected) ?: $disabled ?>"><?= date('h:i A', strtotime($item['slot_start_time'])) ?> - <?= date('h:i A', strtotime($item['slot_end_time'])) ?></span>
        <?php }
} else {
    $booked_appointment_data = $db_account->Execute("SELECT DOA_APPOINTMENT_MASTER.START_TIME, DOA_APPOINTMENT_MASTER.END_TIME FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_MASTER.PK_LOCATION = '$PK_LOCATION' AND DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS NOT IN (2, 6) AND DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER = " . $SERVICE_PROVIDER_ID . " AND DOA_APPOINTMENT_MASTER.DATE = " . "'" . $date . "'");
    $booked_slot_array = [];
    $j = 0;
    while (!$booked_appointment_data->EOF) {
        $booked_slot_array[$j]['START_TIME'] = $booked_appointment_data->fields['START_TIME'];
        $booked_slot_array[$j]['END_TIME'] = $booked_appointment_data->fields['END_TIME'];
        $booked_appointment_data->MoveNext();
        $j++;
    }

    $booked_special_appt_data = $db_account->Execute("SELECT DOA_SPECIAL_APPOINTMENT.START_TIME, DOA_SPECIAL_APPOINTMENT.END_TIME FROM DOA_SPECIAL_APPOINTMENT LEFT JOIN DOA_SPECIAL_APPOINTMENT_USER ON DOA_SPECIAL_APPOINTMENT.PK_SPECIAL_APPOINTMENT = DOA_SPECIAL_APPOINTMENT_USER.PK_SPECIAL_APPOINTMENT WHERE DOA_SPECIAL_APPOINTMENT.PK_LOCATION = '$PK_LOCATION' AND DOA_SPECIAL_APPOINTMENT.PK_APPOINTMENT_STATUS NOT IN (2, 6) AND DOA_SPECIAL_APPOINTMENT_USER.PK_USER = " . $SERVICE_PROVIDER_ID . " AND DOA_SPECIAL_APPOINTMENT.DATE = " . "'" . $date . "'");
    while (!$booked_special_appt_data->EOF) {
        $booked_slot_array[$j]['START_TIME'] = $booked_special_appt_data->fields['START_TIME'];
        $booked_slot_array[$j]['END_TIME'] = $booked_special_appt_data->fields['END_TIME'];
        $booked_special_appt_data->MoveNext();
        $j++;
    }

    if ($SERVICE_PROVIDER_ID > 0 && $PK_LOCATION > 0) {
        $dayNumber = date('N', strtotime($date));
        $location_operational_hour = $db_account->Execute("SELECT MIN(DOA_OPERATIONAL_HOUR.OPEN_TIME) AS OPEN_TIME, MAX(DOA_OPERATIONAL_HOUR.CLOSE_TIME) AS CLOSE_TIME FROM DOA_OPERATIONAL_HOUR WHERE DAY_NUMBER = '$dayNumber' AND CLOSED = 0 AND PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ")");
        $user_operational_hours = $db_account->Execute("SELECT * FROM `DOA_SERVICE_PROVIDER_LOCATION_HOURS` WHERE PK_USER = '$SERVICE_PROVIDER_ID' AND PK_LOCATION = " . $PK_LOCATION);
        if ($location_operational_hour->RecordCount() > 0 && $user_operational_hours->RecordCount() > 0) {
            $LOCATION_SLOT_START = $location_operational_hour->fields['OPEN_TIME'] ?? '00:00:00';
            $LOCATION_SLOT_END = $location_operational_hour->fields['CLOSE_TIME'] ?? '23:00:00';

            $USER_SLOT_START = ($user_operational_hours->fields[$day . '_START_TIME'] != '00:00:00') ? $user_operational_hours->fields[$day . '_START_TIME'] : '00:00:00';
            $USER_SLOT_END = ($user_operational_hours->fields[$day . '_END_TIME'] != '00:00:00') ? $user_operational_hours->fields[$day . '_END_TIME'] : '23:00:00';

            if ($LOCATION_SLOT_START > $USER_SLOT_START) {
                $SLOT_START = $LOCATION_SLOT_START;
            } else {
                $SLOT_START = $USER_SLOT_START;
            }

            if ($LOCATION_SLOT_END > $USER_SLOT_END) {
                $SLOT_END = $USER_SLOT_END;
            } else {
                $SLOT_END = $LOCATION_SLOT_END;
            }
        } elseif ($user_operational_hours->RecordCount() > 0) {
            $SLOT_START = ($user_operational_hours->fields[$day . '_START_TIME'] != '00:00:00') ? $user_operational_hours->fields[$day . '_START_TIME'] : '00:00:00';
            $SLOT_END = ($user_operational_hours->fields[$day . '_END_TIME'] != '00:00:00') ? $user_operational_hours->fields[$day . '_END_TIME'] : '23:00:00';
        } elseif ($location_operational_hour->RecordCount() > 0) {
            $SLOT_START = $location_operational_hour->fields['OPEN_TIME'] ?? '00:00:00';
            $SLOT_END = $location_operational_hour->fields['CLOSE_TIME'] ?? '23:00:00';
        } else {
            $SLOT_START = '00:00:00';
            $SLOT_END = '23:00:00';
        }
    } else {
        $SLOT_START = '00:00:00';
        $SLOT_END = '23:00:00';
    }

    $holiday_data = $db_account->Execute("SELECT * FROM DOA_HOLIDAY_LIST WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]' AND HOLIDAY_DATE = " . "'" . $date . "'");
    if ($holiday_data->RecordCount() > 0) {
        $SLOT_START = '00:00:00';
        $SLOT_END = '23:00:00';
    }

    $SLOT_START = ($slot_time) ? $slot_time : $SLOT_START;
    //echo $SLOT_START." - ".$SLOT_END; die();
    $time_slot_array = [];
    try {
        $time_slot_array = getTimeSlot($duration, $SLOT_START, $SLOT_END);
    } catch (Exception $e) {
        echo $e->getMessage();
    }

    if (count($time_slot_array) > 0) {
        foreach ($time_slot_array as $key => $item) {
            $disabled = '';
            $is_disable = 0;
            if ($SLOT_START == '00:00:00' && $SLOT_END == '23:00:00') {
                $disabled = "pointer-events: none; background-color: red !important;";
                $is_disable = 1;
            } elseif ((date('H:i', strtotime($item['slot_start_time'])) < date('H:i', strtotime($SLOT_START))) || date('H:i', strtotime($item['slot_end_time'])) > date('H:i', strtotime($SLOT_END))) {
                $disabled = "pointer-events: none; background-color: gray !important;";
                $is_disable = 1;
            } else {
                foreach ($booked_slot_array as $booked_slot_array_data) {
                    if (((date('H:i', strtotime($item['slot_start_time'])) >= date('H:i', strtotime($booked_slot_array_data['START_TIME']))) && date('H:i', strtotime($item['slot_start_time'])) < date('H:i', strtotime($booked_slot_array_data['END_TIME']))) || ((date('H:i', strtotime($item['slot_end_time'])) > date('H:i', strtotime($booked_slot_array_data['START_TIME']))) && date('H:i', strtotime($item['slot_end_time'])) <= date('H:i', strtotime($booked_slot_array_data['END_TIME'])))) {
                        $disabled = "pointer-events: none; background-color: blue !important; color: white;";
                        $is_disable = 1;
                    }
                }
            }
            $selected = "";
            if ($is_disable === 0) {
                if ((date('H:i', strtotime($item['slot_start_time'])) == date('H:i', strtotime($START_TIME))) && date('H:i', strtotime($item['slot_end_time'])) == date('H:i', strtotime($END_TIME)) || (!empty($slot_time) && date('H:i', strtotime($item['slot_start_time'])) == date('H:i', strtotime($slot_time))) || (!empty($slot_time) && date('H:i', strtotime($slot_time)) > date('H:i', strtotime($item['slot_start_time']))) && (date('H:i', strtotime($slot_time)) < date('H:i', strtotime($item['slot_end_time'])))) {
                    $selected = "background-color: orange !important;";
                }
            } ?>
            <span data-is_disable="<?= $is_disable ?>" data-is_selected="<?= (($slot_time) ? 0 : (($selected) ? 1 : 0)) ?>" class="slot_btn <?= ($selected) ? 'selected_slot' : '' ?>" id="slot_btn_<?= $key ?>" onclick="set_time(this, <?= $key ?>, '<?= $item['slot_start_time'] ?>', '<?= $item['slot_end_time'] ?>')" style="<?= ($selected) ?: $disabled ?>"><?= date('h:i A', strtotime($item['slot_start_time'])) ?> - <?= date('h:i A', strtotime($item['slot_end_time'])) ?></span>
        <?php }
    } else { ?>
        <span data-is_disable="1" class="slot_btn">No slot available on your selected time</span>
<?php }
} ?>