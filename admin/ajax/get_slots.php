<?php
require_once('../../global/config.php');

function getTimeSlot($interval, $start_time, $end_time)
{
    global $db;
    /*$start = new DateTime($start_time);
    $end = new DateTime($end_time);*/
    $location_operational_hour = $db->Execute("SELECT DOA_OPERATIONAL_HOUR.OPEN_TIME, DOA_OPERATIONAL_HOUR.CLOSE_TIME FROM DOA_OPERATIONAL_HOUR LEFT JOIN DOA_LOCATION ON DOA_OPERATIONAL_HOUR.PK_LOCATION = DOA_LOCATION.PK_LOCATION WHERE DOA_LOCATION.PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]' AND DOA_OPERATIONAL_HOUR.CLOSED = 0 ORDER BY DOA_LOCATION.PK_LOCATION LIMIT 1");

    $start = new DateTime($location_operational_hour->fields['OPEN_TIME']);
    $end = new DateTime($location_operational_hour->fields['CLOSE_TIME']);

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

$PK_ENROLLMENT_MASTER_ARRAY = explode(',', $_POST['PK_ENROLLMENT_MASTER']);
$PK_SERVICE_MASTER = $PK_ENROLLMENT_MASTER_ARRAY[2];
$PK_SERVICE_CODE = $PK_ENROLLMENT_MASTER_ARRAY[3];

$PK_APPOINTMENT_MASTER = $_POST['PK_APPOINTMENT_MASTER'];
$SERVICE_PROVIDER_ID = $_POST['SERVICE_PROVIDER_ID'];
$duration = intval($_POST['duration']);
$date = $_POST['date'];
$day = $_POST['day'];
$START_TIME = $_POST['START_TIME'];
$END_TIME = $_POST['END_TIME'];

$booked_slot_data = $db->Execute("SELECT DOA_APPOINTMENT_MASTER.START_TIME, DOA_APPOINTMENT_MASTER.END_TIME FROM DOA_APPOINTMENT_MASTER INNER JOIN DOA_SERVICE_CODE ON DOA_APPOINTMENT_MASTER.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE WHERE DOA_SERVICE_CODE.IS_GROUP = 0 AND DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER = ".$PK_SERVICE_MASTER." AND DOA_APPOINTMENT_MASTER.PK_SERVICE_CODE = ".$PK_SERVICE_CODE." AND DOA_APPOINTMENT_MASTER.SERVICE_PROVIDER_ID = ".$SERVICE_PROVIDER_ID." AND DOA_APPOINTMENT_MASTER.DATE = "."'".$date."'");
$booked_slot_array = [];
$j = 0;
while (!$booked_slot_data->EOF) {
    $booked_slot_array[$j]['START_TIME'] = $booked_slot_data->fields['START_TIME'];
    $booked_slot_array[$j]['END_TIME'] = $booked_slot_data->fields['END_TIME'];
    $booked_slot_data->MoveNext();
    $j++;
}

$slot_data = $db->Execute("SELECT * FROM DOA_SERVICE_PROVIDER_SERVICES WHERE PK_USER = ".$SERVICE_PROVIDER_ID);

$SLOT_START = $slot_data->fields[$day.'_START_TIME'];
$SLOT_END = $slot_data->fields[$day.'_END_TIME'];

$holiday_data = $db->Execute("SELECT * FROM DOA_HOLIDAY_LIST WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]' AND HOLIDAY_DATE = "."'".$date."'");
if ($holiday_data->RecordCount() > 0){
    $SLOT_START = '00:00:00';
    $SLOT_END = '00:00:00';
}
//echo $SLOT_START." - ".$SLOT_END;

$time_slot_array = getTimeSlot($duration, $SLOT_START, $SLOT_END);

foreach ($time_slot_array as $key => $item) {
    $disabled = '';
    $is_disable = 0;
    if ($SLOT_START == '00:00:00' && $SLOT_END == '00:00:00'){
        $disabled = "pointer-events: none; background-color: red !important;";
        $is_disable = 1;
    }elseif((date('H:i',strtotime($item['slot_start_time'])) < date('H:i',strtotime($SLOT_START))) || date('H:i',strtotime($item['slot_end_time'])) > date('H:i',strtotime($SLOT_END))){
        $disabled = "pointer-events: none; background-color: gray !important;";
        $is_disable = 1;
    }else {
        foreach ($booked_slot_array as $booked_slot_array_data) {
            if ((date('H:i', strtotime($item['slot_start_time'])) == date('H:i', strtotime($booked_slot_array_data['START_TIME']))) && date('H:i', strtotime($item['slot_end_time'])) == date('H:i', strtotime($booked_slot_array_data['END_TIME']))) {
                $disabled = "pointer-events: none; background-color: blue !important;";
                $is_disable = 1;
            }
        }
    }
    $selected = "";
    if((date('H:i',strtotime($item['slot_start_time'])) == date('H:i',strtotime($START_TIME))) && date('H:i',strtotime($item['slot_end_time'])) == date('H:i',strtotime($END_TIME))){
        $selected = "background-color: orange !important;";
    } ?>
    <div class="col-md-6 form-group">
        <button type="button" data-is_disable="<?=$disabled?>" class="btn waves-effect waves-light btn-light slot_btn" id="slot_btn_<?=$key?>" onclick="set_time(<?=$key?>, '<?=$item['slot_start_time']?>', '<?=$item['slot_end_time']?>', <?=$PK_APPOINTMENT_MASTER?>)" style="width:100%; <?=($selected)?$selected:$disabled?>"><?=date('h:i A', strtotime($item['slot_start_time']))?> - <?=date('h:i A', strtotime($item['slot_end_time']))?></button>
    </div>
<?php } ?>