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
$PK_APPOINTMENT_MASTER = $_POST['PK_APPOINTMENT_MASTER'];
$SERVICE_PROVIDER_ID = $_POST['SERVICE_PROVIDER_ID'];
$PK_LOCATION = $_POST['PK_LOCATION'];
$duration = intval($_POST['duration']);
$date = $_POST['date'];
$day = $_POST['day'];
$START_TIME = empty($_POST['START_TIME']) ? '09:00:00' : $_POST['START_TIME'];
$END_TIME = empty($_POST['END_TIME']) ? '22:00:00' : $_POST['END_TIME'];
$slot_time = empty($_POST['slot_time']) ? '' : $_POST['slot_time'];

$booked_appointment_data = $db_account->Execute("SELECT DOA_APPOINTMENT_MASTER.START_TIME, DOA_APPOINTMENT_MASTER.END_TIME FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS NOT IN (2, 6) AND DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER = " . $SERVICE_PROVIDER_ID . " AND DOA_APPOINTMENT_MASTER.DATE = " . "'" . $date . "'");
$booked_slot_array = [];
$j = 0;
while (!$booked_appointment_data->EOF) {
    $booked_slot_array[$j]['START_TIME'] = $booked_appointment_data->fields['START_TIME'];
    $booked_slot_array[$j]['END_TIME'] = $booked_appointment_data->fields['END_TIME'];
    $booked_appointment_data->MoveNext();
    $j++;
}

$booked_special_appt_data = $db_account->Execute("SELECT DOA_SPECIAL_APPOINTMENT.START_TIME, DOA_SPECIAL_APPOINTMENT.END_TIME FROM DOA_SPECIAL_APPOINTMENT LEFT JOIN DOA_SPECIAL_APPOINTMENT_USER ON DOA_SPECIAL_APPOINTMENT.PK_SPECIAL_APPOINTMENT = DOA_SPECIAL_APPOINTMENT_USER.PK_SPECIAL_APPOINTMENT WHERE DOA_SPECIAL_APPOINTMENT.PK_APPOINTMENT_STATUS NOT IN (2, 6) AND DOA_SPECIAL_APPOINTMENT_USER.PK_USER = " . $SERVICE_PROVIDER_ID . " AND DOA_SPECIAL_APPOINTMENT.DATE = " . "'" . $date . "'");
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
        <div class="col-md-6 form-group">
            <button type="button" data-is_disable="<?= $is_disable ?>" data-is_selected="<?= (($slot_time) ? 0 : (($selected) ? 1 : 0)) ?>" class="btn waves-effect waves-light btn-light slot_btn <?= ($selected) ? 'selected_slot' : '' ?>" id="slot_btn_<?= $key ?>" onclick="set_time(this, <?= $key ?>, '<?= $item['slot_start_time'] ?>', '<?= $item['slot_end_time'] ?>', <?= $PK_APPOINTMENT_MASTER ?>)" style="width:100%; <?= ($selected) ?: $disabled ?>"><?= date('h:i A', strtotime($item['slot_start_time'])) ?> - <?= date('h:i A', strtotime($item['slot_end_time'])) ?></button>
        </div>
    <?php }
} else { ?>
    <div class="col-md-12 form-group">
        <button type="button" data-is_disable="1" class="btn waves-effect waves-light btn-light slot_btn" style="width:100%; pointer-events: none; background-color: gray !important; color: white; font-size: 18px;">No slot available on your selected time</button>
    </div>
<?php } ?>