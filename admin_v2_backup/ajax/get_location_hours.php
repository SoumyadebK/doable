<?php
require_once('../../global/config.php');
global $db;
global $db_account;
global $master_database;

$PK_USER = $_POST['PK_USER'];

/*$service_data = $db_account->Execute("SELECT * FROM `DOA_SERVICE_PROVIDER_LOCATION_HOURS` WHERE PK_USER = '$PK_USER'");
if($service_data->RecordCount() > 0) {
    $PK_SERVICE_MASTER = $service_data->fields['PK_SERVICE_MASTER'];
    $MON_START_TIME = $service_data->fields['MON_START_TIME'];
    $MON_END_TIME = $service_data->fields['MON_END_TIME'];
    $TUE_START_TIME = $service_data->fields['TUE_START_TIME'];
    $TUE_END_TIME = $service_data->fields['TUE_END_TIME'];
    $WED_START_TIME = $service_data->fields['WED_START_TIME'];
    $WED_END_TIME = $service_data->fields['WED_END_TIME'];
    $THU_START_TIME = $service_data->fields['THU_START_TIME'];
    $THU_END_TIME = $service_data->fields['THU_END_TIME'];
    $FRI_START_TIME = $service_data->fields['FRI_START_TIME'];
    $FRI_END_TIME = $service_data->fields['FRI_END_TIME'];
    $SAT_START_TIME = $service_data->fields['SAT_START_TIME'];
    $SAT_END_TIME = $service_data->fields['SAT_END_TIME'];
    $SUN_START_TIME = $service_data->fields['SUN_START_TIME'];
    $SUN_END_TIME = $service_data->fields['SUN_END_TIME'];
}*/
/*$operational_hours_res = $db_account->Execute("SELECT DOA_OPERATIONAL_HOUR.DAY_NUMBER, MIN(DOA_OPERATIONAL_HOUR.OPEN_TIME) AS OPEN_TIME, MAX(DOA_OPERATIONAL_HOUR.CLOSE_TIME) AS CLOSE_TIME, DOA_OPERATIONAL_HOUR.CLOSED FROM `DOA_OPERATIONAL_HOUR` LEFT JOIN DOA_USER_LOCATION ON DOA_OPERATIONAL_HOUR.PK_LOCATION = DOA_USER_LOCATION.PK_LOCATION WHERE DOA_USER_LOCATION.PK_USER = '$_GET[id]' GROUP BY DOA_OPERATIONAL_HOUR.DAY_NUMBER");
while (!$operational_hours_res->EOF) {

    switch ($operational_hours_res->fields['DAY_NUMBER']) {
        case 1:
            $MON_MIN_TIME = $operational_hours_res->fields['OPEN_TIME'];
            $MON_MAX_TIME = $operational_hours_res->fields['CLOSE_TIME'];
            $MON_CLOSED = $operational_hours_res->fields['CLOSED'];
            break;
        case 2:
            $TUE_MIN_TIME = $operational_hours_res->fields['OPEN_TIME'];
            $TUE_MAX_TIME = $operational_hours_res->fields['CLOSE_TIME'];
            $TUE_CLOSED = $operational_hours_res->fields['CLOSED'];
            break;
        case 3:
            $WED_MIN_TIME = $operational_hours_res->fields['OPEN_TIME'];
            $WED_MAX_TIME = $operational_hours_res->fields['CLOSE_TIME'];
            $WED_CLOSED = $operational_hours_res->fields['CLOSED'];
            break;
        case 4:
            $THU_MIN_TIME = $operational_hours_res->fields['OPEN_TIME'];
            $THU_MAX_TIME = $operational_hours_res->fields['CLOSE_TIME'];
            $THU_CLOSED = $operational_hours_res->fields['CLOSED'];
            break;
        case 5:
            $FRI_MIN_TIME = $operational_hours_res->fields['OPEN_TIME'];
            $FRI_MAX_TIME = $operational_hours_res->fields['CLOSE_TIME'];
            $FRI_CLOSED = $operational_hours_res->fields['CLOSED'];
            break;
        case 6:
            $SAT_MIN_TIME = $operational_hours_res->fields['OPEN_TIME'];
            $SAT_MAX_TIME = $operational_hours_res->fields['CLOSE_TIME'];
            $SAT_CLOSED = $operational_hours_res->fields['CLOSED'];
            break;
        case 7:
            $SUN_MIN_TIME = $operational_hours_res->fields['OPEN_TIME'];
            $SUN_MAX_TIME = $operational_hours_res->fields['CLOSE_TIME'];
            $SUN_CLOSED = $operational_hours_res->fields['CLOSED'];
            break;
    }
    $operational_hours_res->MoveNext();
}*/

?>

<?php
$user_location = $db->Execute("SELECT DOA_USER_LOCATION.*, DOA_LOCATION.LOCATION_NAME FROM `DOA_USER_LOCATION` LEFT JOIN DOA_LOCATION ON DOA_USER_LOCATION.PK_LOCATION = DOA_LOCATION.PK_LOCATION WHERE PK_USER = '$PK_USER'");
if($user_location->RecordCount() > 0) {
    while(!$user_location->EOF) {
        $MON_START_TIME = '';
        $MON_END_TIME = '';
        $TUE_START_TIME = '';
        $TUE_END_TIME = '';
        $WED_START_TIME = '';
        $WED_END_TIME = '';
        $THU_START_TIME = '';
        $THU_END_TIME = '';
        $FRI_START_TIME = '';
        $FRI_END_TIME = '';
        $SAT_START_TIME = '';
        $SAT_END_TIME = '';
        $SUN_START_TIME = '';
        $SUN_END_TIME = '';

        $location_hour_data = $db_account->Execute("SELECT * FROM `DOA_SERVICE_PROVIDER_LOCATION_HOURS` WHERE PK_USER = '$PK_USER' AND PK_LOCATION = ".$user_location->fields['PK_LOCATION']);
        if ($location_hour_data->RecordCount() > 0) {
            $MON_START_TIME = $location_hour_data->fields['MON_START_TIME'];
            $MON_END_TIME = $location_hour_data->fields['MON_END_TIME'];
            $TUE_START_TIME = $location_hour_data->fields['TUE_START_TIME'];
            $TUE_END_TIME = $location_hour_data->fields['TUE_END_TIME'];
            $WED_START_TIME = $location_hour_data->fields['WED_START_TIME'];
            $WED_END_TIME = $location_hour_data->fields['WED_END_TIME'];
            $THU_START_TIME = $location_hour_data->fields['THU_START_TIME'];
            $THU_END_TIME = $location_hour_data->fields['THU_END_TIME'];
            $FRI_START_TIME = $location_hour_data->fields['FRI_START_TIME'];
            $FRI_END_TIME = $location_hour_data->fields['FRI_END_TIME'];
            $SAT_START_TIME = $location_hour_data->fields['SAT_START_TIME'];
            $SAT_END_TIME = $location_hour_data->fields['SAT_END_TIME'];
            $SUN_START_TIME = $location_hour_data->fields['SUN_START_TIME'];
            $SUN_END_TIME = $location_hour_data->fields['SUN_END_TIME'];
        }

        $location_operational_hour = $db_account->Execute("SELECT * FROM DOA_OPERATIONAL_HOUR WHERE PK_LOCATION = ".$user_location->fields['PK_LOCATION']);
        if ($location_operational_hour->RecordCount() > 0) {
            while(!$location_operational_hour->EOF) {
                switch ($location_operational_hour->fields['DAY_NUMBER']) {
                    case 1:
                        if ($location_operational_hour->fields['CLOSED'] == 1) {
                            $MON_START_TIME = '00:00:00';
                            $MON_END_TIME = '00:00:00';
                        }
                        $monMinTime = $location_operational_hour->fields['OPEN_TIME'];
                        $monMaxTime = $location_operational_hour->fields['CLOSE_TIME'];
                        break;
                    case 2:
                        if ($location_operational_hour->fields['CLOSED'] == 1) {
                            $TUE_START_TIME = '00:00:00';
                            $TUE_END_TIME = '00:00:00';
                        }
                        $tueMinTime = $location_operational_hour->fields['OPEN_TIME'];
                        $tueMaxTime = $location_operational_hour->fields['CLOSE_TIME'];
                        break;
                    case 3:
                        if ($location_operational_hour->fields['CLOSED'] == 1) {
                            $WED_START_TIME = '00:00:00';
                            $WED_END_TIME = '00:00:00';
                        }
                        $wedMinTime = $location_operational_hour->fields['OPEN_TIME'];
                        $wedMaxTime = $location_operational_hour->fields['CLOSE_TIME'];
                        break;
                    case 4:
                        if ($location_operational_hour->fields['CLOSED'] == 1) {
                            $THU_START_TIME = '00:00:00';
                            $THU_END_TIME = '00:00:00';
                        }
                        $thuMinTime = $location_operational_hour->fields['OPEN_TIME'];
                        $thuMaxTime = $location_operational_hour->fields['CLOSE_TIME'];
                        break;
                    case 5:
                        if ($location_operational_hour->fields['CLOSED'] == 1) {
                            $FRI_START_TIME = '00:00:00';
                            $FRI_END_TIME = '00:00:00';
                        }
                        $friMinTime = $location_operational_hour->fields['OPEN_TIME'];
                        $friMaxTime = $location_operational_hour->fields['CLOSE_TIME'];
                        break;
                    case 6: 
                        if ($location_operational_hour->fields['CLOSED'] == 1) {
                            $SAT_START_TIME = '00:00:00';
                            $SAT_END_TIME = '00:00:00';
                        }
                        $satMinTime = $location_operational_hour->fields['OPEN_TIME'];
                        $satMaxTime = $location_operational_hour->fields['CLOSE_TIME'];
                        break;
                    case 7:
                        if ($location_operational_hour->fields['CLOSED'] == 1) {
                            $SUN_START_TIME = '00:00:00';
                            $SUN_END_TIME = '00:00:00';
                        }
                        $sunMinTime = $location_operational_hour->fields['OPEN_TIME'];
                        $sunMaxTime = $location_operational_hour->fields['CLOSE_TIME'];
                        break;
                }
                $location_operational_hour->MoveNext();
            }
        }
    ?>
        <div class="location-hours">
            <div class="row form-group">
                <div class="col-6">
                    <h5><strong>Location Name : </strong><?=$user_location->fields['LOCATION_NAME']?></h5>
                    <input type="hidden" name="PK_LOCATION[]" value="<?=$user_location->fields['PK_LOCATION']?>">
                </div>
            </div>

            <div class="row">
                <div class="col-1">
                </div>
                <div class="col-2">
                    <label class="form-label">Start Time</label>
                </div>
                <div class="col-2">
                    <label class="form-label">End Time</label>
                </div>
            </div>

            <div class="row form-group">
                <input type="hidden" class="minTime" value="<?=$monMinTime?>">
                <input type="hidden" class="maxTime" value="<?=$monMaxTime?>">
                <div class="col-1">
                    <label class="form-label">Monday</label>
                </div>
                <div class="col-2">
                    <input type="text" name="MON_START_TIME[]" class="form-control time-input time-picker" placeholder="Start Time" style="background-color: <?=($MON_START_TIME=='00:00:00'&&$MON_END_TIME=='00:00:00')?'#80808080 !important; pointer-events: none !important;':''?>" value="<?=($MON_START_TIME=='00:00:00' || $MON_START_TIME=='')?'':date('h:i A', strtotime($MON_START_TIME))?>" readonly>
                </div>
                <div class="col-2">
                    <input type="text" name="MON_END_TIME[]" class="form-control time-input time-picker" placeholder="End Time" style="background-color: <?=($MON_START_TIME=='00:00:00'&&$MON_END_TIME=='00:00:00')?'#80808080 !important; pointer-events: none !important;':''?>" value="<?=($MON_END_TIME=='00:00:00' || $MON_END_TIME=='')?'':date('h:i A', strtotime($MON_END_TIME))?>" readonly>
                </div>
                <div class="col-2">
                    <label><input type="checkbox" onchange="closeThisDay(this)" <?=($MON_START_TIME=='00:00:00'&&$MON_END_TIME=='00:00:00')?'checked':''?>> Holiday</label>
                </div>
            </div>

            <div class="row form-group">
                <input type="hidden" class="minTime" value="<?=$tueMinTime?>">
                <input type="hidden" class="maxTime" value="<?=$tueMaxTime?>">
                <div class="col-1">
                    <label class="form-label">Tuesday</label>
                </div>
                <div class="col-2">
                    <input type="text" name="TUE_START_TIME[]" class="form-control time-input time-picker" placeholder="Start Time" style="background-color: <?=($TUE_START_TIME=='00:00:00'&&$TUE_END_TIME=='00:00:00')?'#80808080 !important; pointer-events: none !important;':''?>" value="<?=($TUE_START_TIME=='00:00:00' || $TUE_START_TIME=='')?'':date('h:i A', strtotime($TUE_START_TIME))?>" readonly>
                </div>
                <div class="col-2">
                    <input type="text" name="TUE_END_TIME[]" class="form-control time-input time-picker" placeholder="End Time" style="background-color: <?=($TUE_START_TIME=='00:00:00'&&$TUE_END_TIME=='00:00:00')?'#80808080 !important; pointer-events: none !important;':''?>" value="<?=($TUE_END_TIME=='00:00:00' || $TUE_END_TIME=='')?'':date('h:i A', strtotime($TUE_END_TIME))?>" readonly>
                </div>
                <div class="col-2">
                    <label><input type="checkbox" onchange="closeThisDay(this)" <?=($TUE_START_TIME=='00:00:00'&&$TUE_END_TIME=='00:00:00')?'checked':''?>> Holiday</label>
                </div>
            </div>

            <div class="row form-group">
                <input type="hidden" class="minTime" value="<?=$wedMinTime?>">
                <input type="hidden" class="maxTime" value="<?=$wedMaxTime?>">
                <div class="col-1">
                    <label class="form-label">Wednesday</label>
                </div>
                <div class="col-2">
                    <input type="text" name="WED_START_TIME[]" class="form-control time-input time-picker" placeholder="Start Time" style="background-color: <?=($WED_START_TIME=='00:00:00'&&$WED_END_TIME=='00:00:00')?'#80808080 !important; pointer-events: none !important;':''?>" value="<?=($WED_START_TIME=='00:00:00' || $WED_START_TIME=='')?'':date('h:i A', strtotime($WED_START_TIME))?>" readonly>
                </div>
                <div class="col-2">
                    <input type="text" name="WED_END_TIME[]" class="form-control time-input time-picker" placeholder="End Time" style="background-color: <?=($WED_START_TIME=='00:00:00'&&$WED_END_TIME=='00:00:00')?'#80808080 !important; pointer-events: none !important;':''?>" value="<?=($WED_END_TIME=='00:00:00' || $WED_END_TIME=='')?'':date('h:i A', strtotime($WED_END_TIME))?>" readonly>
                </div>
                <div class="col-2">
                    <label><input type="checkbox" onchange="closeThisDay(this)" <?=($WED_START_TIME=='00:00:00'&&$WED_END_TIME=='00:00:00')?'checked':''?>> Holiday</label>
                </div>
            </div>

            <div class="row form-group">
                <input type="hidden" class="minTime" value="<?=$thuMinTime?>">
                <input type="hidden" class="maxTime" value="<?=$thuMaxTime?>">
                <div class="col-1">
                    <label class="form-label">Thursday</label>
                </div>
                <div class="col-2">
                    <input type="text" name="THU_START_TIME[]" class="form-control time-input time-picker" placeholder="Start Time" style="background-color: <?=($THU_START_TIME=='00:00:00'&&$THU_END_TIME=='00:00:00')?'#80808080 !important; pointer-events: none !important;':''?>" value="<?=($THU_START_TIME=='00:00:00' || $THU_START_TIME=='')?'':date('h:i A', strtotime($THU_START_TIME))?>" readonly>
                </div>
                <div class="col-2">
                    <input type="text" name="THU_END_TIME[]" class="form-control time-input time-picker" placeholder="End Time" style="background-color: <?=($THU_START_TIME=='00:00:00'&&$THU_END_TIME=='00:00:00')?'#80808080 !important; pointer-events: none !important;':''?>" value="<?=($THU_END_TIME=='00:00:00' || $THU_END_TIME=='')?'':date('h:i A', strtotime($THU_END_TIME))?>" readonly>
                </div>
                <div class="col-2">
                    <label><input type="checkbox" onchange="closeThisDay(this)" <?=($THU_START_TIME=='00:00:00'&&$THU_END_TIME=='00:00:00')?'checked':''?>> Holiday</label>
                </div>
            </div>

            <div class="row form-group">
                <input type="hidden" class="minTime" value="<?=$friMinTime?>">
                <input type="hidden" class="maxTime" value="<?=$friMaxTime?>">
                <div class="col-1">
                    <label class="form-label">Friday</label>
                </div>
                <div class="col-2">
                    <input type="text" name="FRI_START_TIME[]" class="form-control time-input time-picker" placeholder="Start Time" style="background-color: <?=($FRI_START_TIME=='00:00:00'&&$FRI_END_TIME=='00:00:00')?'#80808080 !important; pointer-events: none !important;':''?>" value="<?=($FRI_START_TIME=='00:00:00' || $FRI_START_TIME=='')?'':date('h:i A', strtotime($FRI_START_TIME))?>" readonly>
                </div>
                <div class="col-2">
                    <input type="text" name="FRI_END_TIME[]" class="form-control time-input time-picker" placeholder="End Time" style="background-color: <?=($FRI_START_TIME=='00:00:00'&&$FRI_END_TIME=='00:00:00')?'#80808080 !important; pointer-events: none !important;':''?>" value="<?=($FRI_END_TIME=='00:00:00' || $FRI_END_TIME=='')?'':date('h:i A', strtotime($FRI_END_TIME))?>" readonly>
                </div>
                <div class="col-2">
                    <label><input type="checkbox" onchange="closeThisDay(this)" <?=($FRI_START_TIME=='00:00:00'&&$FRI_END_TIME=='00:00:00')?'checked':''?>> Holiday</label>
                </div>
            </div>

            <div class="row form-group">
                <input type="hidden" class="minTime" value="<?=$satMinTime?>">
                <input type="hidden" class="maxTime" value="<?=$satMaxTime?>">
                <div class="col-1">
                    <label class="form-label">Saturday</label>
                </div>
                <div class="col-2">
                    <input type="text" name="SAT_START_TIME[]" class="form-control time-input time-picker" placeholder="Start Time" style="background-color: <?=($SAT_START_TIME=='00:00:00'&&$SAT_END_TIME=='00:00:00')?'#80808080 !important; pointer-events: none !important;':''?>" value="<?=($SAT_START_TIME=='00:00:00' || $SAT_START_TIME=='')?'':date('h:i A', strtotime($SAT_START_TIME))?>" readonly>
                </div>
                <div class="col-2">
                    <input type="text" name="SAT_END_TIME[]" class="form-control time-input time-picker" placeholder="End Time" style="background-color: <?=($SAT_START_TIME=='00:00:00'&&$SAT_END_TIME=='00:00:00')?'#80808080 !important; pointer-events: none !important;':''?>" value="<?=($SAT_END_TIME=='00:00:00' || $SAT_END_TIME=='')?'':date('h:i A', strtotime($SAT_END_TIME))?>" readonly>
                </div>
                <div class="col-2">
                    <label><input type="checkbox" onchange="closeThisDay(this)" <?=($SAT_START_TIME=='00:00:00'&&$SAT_END_TIME=='00:00:00')?'checked':''?>> Holiday</label>
                </div>
            </div>

            <div class="row form-group">
                <input type="hidden" class="minTime" value="<?=$sunMinTime?>">
                <input type="hidden" class="maxTime" value="<?=$sunMaxTime?>">
                <div class="col-1">
                    <label class="form-label">Sunday</label>
                </div>
                <div class="col-2">
                    <input type="text" name="SUN_START_TIME[]" class="form-control time-input time-picker" placeholder="Start Time" style="background-color: <?=($SUN_START_TIME=='00:00:00'&&$SUN_END_TIME=='00:00:00')?'#80808080 !important; pointer-events: none !important;':''?>" value="<?=($SUN_START_TIME=='00:00:00' || $SUN_START_TIME=='')?'':date('h:i A', strtotime($SUN_START_TIME))?>" readonly>
                </div>
                <div class="col-2">
                    <input type="text" name="SUN_END_TIME[]" class="form-control time-input time-picker" placeholder="End Time" style="background-color: <?=($SUN_START_TIME=='00:00:00'&&$SUN_END_TIME=='00:00:00')?'#80808080 !important; pointer-events: none !important;':''?>" value="<?=($SUN_END_TIME=='00:00:00' || $SUN_END_TIME=='')?'':date('h:i A', strtotime($SUN_END_TIME))?>" readonly>
                </div>
                <div class="col-2">
                    <label><input type="checkbox" onchange="closeThisDay(this)" <?=($SUN_START_TIME=='00:00:00'&&$SUN_END_TIME=='00:00:00')?'checked':''?>> Holiday</label>
                </div>
            </div>
            <br><br>
        </div>
    <?php
        $user_location->MoveNext();
    } 
} else { ?>
    <h4 style="color: red;">Please select your location first to set the service hours.</h4>
<?php } ?>