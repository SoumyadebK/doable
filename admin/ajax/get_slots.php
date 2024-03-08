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
    $i=0;
    $time = [];
    while(strtotime($startTime) <= strtotime($endTime)){
        $start = $startTime;
        $end = date('H:i',strtotime('+'.$interval.' minutes',strtotime($startTime)));
        $startTime = date('H:i',strtotime('+'.$interval.' minutes',strtotime($startTime)));
        $i++;
        if(strtotime($startTime) <= strtotime($endTime)){
            $time[$i]['slot_start_time'] = $start;
            $time[$i]['slot_end_time'] = $end;
        }
    }
    return $time;
}

$PK_APPOINTMENT_MASTER = $_POST['PK_APPOINTMENT_MASTER'];
$SERVICE_PROVIDER_ID = $_POST['SERVICE_PROVIDER_ID'];
$duration = intval($_POST['duration']);
$date = $_POST['date'];
$day = $_POST['day'];
$START_TIME = empty($_POST['START_TIME'])?'09:00:00':$_POST['START_TIME'];
$END_TIME = empty($_POST['END_TIME'])?'22:00:00':$_POST['END_TIME'];
$slot_time = empty($_POST['slot_time'])?'':$_POST['slot_time'];

$booked_slot_data = $db_account->Execute("SELECT DOA_APPOINTMENT_MASTER.START_TIME, DOA_APPOINTMENT_MASTER.END_TIME FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS NOT IN (2, 6) AND DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER = ".$SERVICE_PROVIDER_ID." AND DOA_APPOINTMENT_MASTER.DATE = "."'".$date."'");
$booked_slot_array = [];
$j = 0;
while (!$booked_slot_data->EOF) {
    $booked_slot_array[$j]['START_TIME'] = $booked_slot_data->fields['START_TIME'];
    $booked_slot_array[$j]['END_TIME'] = $booked_slot_data->fields['END_TIME'];
    $booked_slot_data->MoveNext();
    $j++;
}

$dayNumber = date('N', strtotime($date));
$location_operational_hour = $db_account->Execute("SELECT MIN(DOA_OPERATIONAL_HOUR.OPEN_TIME) AS OPEN_TIME, MAX(DOA_OPERATIONAL_HOUR.CLOSE_TIME) AS CLOSE_TIME FROM DOA_OPERATIONAL_HOUR WHERE DAY_NUMBER = '$dayNumber' AND CLOSED = 0 AND PK_LOCATION IN (".$_SESSION['DEFAULT_LOCATION_ID'].")");
if ($location_operational_hour->RecordCount() > 0) {
    $SLOT_START = $location_operational_hour->fields['OPEN_TIME'] ?? '00:00:00';
    $SLOT_END = $location_operational_hour->fields['CLOSE_TIME'] ?? '23:00:00';
} else {
    $SLOT_START = '00:00:00';
    $SLOT_END = '23:00:00';
}

$holiday_data = $db_account->Execute("SELECT * FROM DOA_HOLIDAY_LIST WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]' AND HOLIDAY_DATE = "."'".$date."'");
if ($holiday_data->RecordCount() > 0){
    $SLOT_START = '00:00:00';
    $SLOT_END = '23:00:00';
}
//echo $SLOT_START." - ".$SLOT_END;

$time_slot_array = getTimeSlot($duration, $SLOT_START, $SLOT_END, $dayNumber);

foreach ($time_slot_array as $key => $item) {
    $disabled = '';
    $is_disable = 0;
    if ($SLOT_START == '00:00:00' && $SLOT_END == '23:00:00'){
        $disabled = "pointer-events: none; background-color: red !important;";
        $is_disable = 1;
    }elseif((date('H:i',strtotime($item['slot_start_time'])) < date('H:i',strtotime($SLOT_START))) || date('H:i',strtotime($item['slot_end_time'])) > date('H:i',strtotime($SLOT_END))){
        $disabled = "pointer-events: none; background-color: gray !important;";
        $is_disable = 1;
    }else {
        foreach ($booked_slot_array as $booked_slot_array_data) {
            if ((date('H:i', strtotime($item['slot_start_time'])) >= date('H:i', strtotime($booked_slot_array_data['START_TIME']))) && date('H:i', strtotime($item['slot_end_time'])) <= date('H:i', strtotime($booked_slot_array_data['END_TIME']))) {
                $disabled = "pointer-events: none; background-color: blue !important; color: white;";
                $is_disable = 1;
            }
        }
    }
    $selected = "";
    if((date('H:i',strtotime($item['slot_start_time'])) == date('H:i',strtotime($START_TIME))) && date('H:i',strtotime($item['slot_end_time'])) == date('H:i',strtotime($END_TIME)) || (!empty($slot_time) && date('H:i',strtotime($item['slot_start_time'])) == date('H:i',strtotime($slot_time))) || (!empty($slot_time) && date('H:i',strtotime($slot_time)) > date('H:i',strtotime($item['slot_start_time']))) && (date('H:i',strtotime($slot_time)) < date('H:i',strtotime($item['slot_end_time'])))){
        $selected = "background-color: orange !important;";
    } ?>
    <div class="col-md-6 form-group">
        <button type="button" data-is_disable="<?=$disabled?>" data-is_selected="<?=(($slot_time)?0:(($selected)?1:0))?>" class="btn waves-effect waves-light btn-light slot_btn <?=($selected)?'selected_slot':''?>" id="slot_btn_<?=$key?>" onclick="set_time(this, <?=$key?>, '<?=$item['slot_start_time']?>', '<?=$item['slot_end_time']?>', <?=$PK_APPOINTMENT_MASTER?>)" style="width:100%; <?=($selected)?:$disabled?>"><?=date('h:i A', strtotime($item['slot_start_time']))?> - <?=date('h:i A', strtotime($item['slot_end_time']))?></button>
    </div>
<?php } ?>
