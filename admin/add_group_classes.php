<?php
require_once('../global/config.php');

if (empty($_GET['id']))
    $title = "Add Group Class";
else
    $title = "Edit Group Class";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 2 ){
    header("location:../login.php");
    exit;
}

$PK_LOCATION = $DEFAULT_LOCATION_ID;

if (isset($_POST['FUNCTION_NAME'])){
    $SERVICE_ID = explode(',', $_POST['SERVICE_ID']);
    $DURATION = $SERVICE_ID[0];
    $PK_SERVICE_CODE = $SERVICE_ID[1];
    $PK_SERVICE_MASTER = $SERVICE_ID[2];

    $STARTING_ON = $_POST['STARTING_ON'];
    $LENGTH = $_POST['LENGTH'];
    $FREQUENCY = $_POST['FREQUENCY'];
    $END_DATE = date('Y-m-d', strtotime('+ ' . $LENGTH . ' ' . $FREQUENCY, strtotime($STARTING_ON)));

    $START_TIME = $_POST['START_TIME'];
    $END_TIME = date("H:i", strtotime($START_TIME)+($DURATION*60));

    $GROUP_CLASS_DATE_ARRAY = [];
    if (!empty($_POST['OCCURRENCE'])){
        $SERVICE_DATE = date('Y-m-d', strtotime($STARTING_ON));
        if ($_POST['OCCURRENCE'] == 'WEEKLY'){
            if (isset($_POST['DAYS'])) {
                $DAYS = $_POST['DAYS'];
            } else {
                $DAYS[] = strtolower(date('l', strtotime($STARTING_ON)));
            }
            while ($SERVICE_DATE < $END_DATE) {
                $appointment_day = date('l', strtotime($SERVICE_DATE));
                if (in_array(strtolower($appointment_day), $DAYS)){
                    $GROUP_CLASS_DATE_ARRAY[] = $SERVICE_DATE;
                }
                $SERVICE_DATE = date('Y-m-d', strtotime('+1 day ', strtotime($SERVICE_DATE)));
            }
        }else {
            $OCCURRENCE_DAYS = (empty($_POST['OCCURRENCE_DAYS']))?7:$_POST['OCCURRENCE_DAYS'];

            while ($SERVICE_DATE < $END_DATE) {
                $GROUP_CLASS_DATE_ARRAY[] = $SERVICE_DATE;
                $SERVICE_DATE = date('Y-m-d', strtotime('+ '.$OCCURRENCE_DAYS.' day', strtotime($SERVICE_DATE)));
                //echo $SERVICE_DATE . "<br>";
            }
        }
    }

    if (count($GROUP_CLASS_DATE_ARRAY) > 0) {
        $group_class_data = $db->Execute("SELECT GROUP_CLASS_ID FROM `DOA_GROUP_CLASS` ORDER BY GROUP_CLASS_ID DESC LIMIT 1");
        if ($group_class_data->RecordCount() > 0) {
            $group_class_id = $group_class_data->fields['GROUP_CLASS_ID']+1;
        } else {
            $group_class_id = 1;
        }

        for ($i = 0; $i < count($GROUP_CLASS_DATE_ARRAY); $i++) {
            $GROUP_CLASS_DATA['GROUP_CLASS_ID'] = $group_class_id;
            $GROUP_CLASS_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
            $GROUP_CLASS_DATA['PK_SERVICE_MASTER'] = $PK_SERVICE_MASTER;
            $GROUP_CLASS_DATA['PK_SERVICE_CODE'] = $PK_SERVICE_CODE;
            $GROUP_CLASS_DATA['SERVICE_PROVIDER_ID_1'] = $_POST['SERVICE_PROVIDER_ID_1'];
            $GROUP_CLASS_DATA['SERVICE_PROVIDER_ID_2'] = $_POST['SERVICE_PROVIDER_ID_2'];
            $GROUP_CLASS_DATA['PK_LOCATION'] = $_POST['PK_LOCATION'];
            $GROUP_CLASS_DATA['DATE'] = $GROUP_CLASS_DATE_ARRAY[$i];
            $GROUP_CLASS_DATA['START_TIME'] = date('H:i:s', strtotime($START_TIME));
            $GROUP_CLASS_DATA['END_TIME'] = date('H:i:s', strtotime($END_TIME));
            $GROUP_CLASS_DATA['PK_APPOINTMENT_STATUS'] = 1;
            $GROUP_CLASS_DATA['ACTIVE'] = 1;
            $GROUP_CLASS_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
            $GROUP_CLASS_DATA['CREATED_ON'] = date("Y-m-d H:i");
            db_perform('DOA_GROUP_CLASS', $GROUP_CLASS_DATA, 'insert');
        }
    }

    header("location:all_schedules.php");
}

?>


<!DOCTYPE html>
<html lang="en">
<?php require_once('../includes/header.php');?>
<link href="../assets/sumoselect/sumoselect.min.css" rel="stylesheet"/>
<style>
    .slot_btn{
        background-color: greenyellow;
    }
    .SumoSelect {
        width: 100%;
    }

    .disable-div {
        opacity: 0.5;
        pointer-events: none
    }
</style>
<body class="skin-default-dark fixed-layout">
<?php require_once('../includes/loader.php');?>
<div id="main-wrapper">
    <?php require_once('../includes/top_menu.php');?>
    <div class="page-wrapper">
        <?php require_once('../includes/top_menu_bar.php') ?>
        <div class="container-fluid">
            <div class="row page-titles">
                <div class="col-md-5 align-self-center">
                    <h4 class="text-themecolor"><?=$title?></h4>

                </div>
                <div class="col-md-7 align-self-center text-end">
                    <div class="d-flex justify-content-end align-items-center">
                        <ol class="breadcrumb justify-content-end">
                            <li class="breadcrumb-item"><a href="all_schedules.php">All Appointment</a></li>
                            <li class="breadcrumb-item active"><?=$title?></li>
                        </ol>

                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <form id="appointment_form" action="" method="post" enctype="multipart/form-data">
                                <input type="hidden" name="FUNCTION_NAME" value="saveGroupClassData">
                                <input type="hidden" name="PK_APPOINTMENT_MASTER" class="PK_APPOINTMENT_MASTER" value="<?=(empty($_GET['id']))?'':$_GET['id']?>">
                                <div class="p-40" style="padding-top: 10px;">
                                    <div class="row">
                                        <div class="col-3">
                                            <div class="form-group">
                                                <label class="form-label">Service <span class="text-danger">*</span></label><br>
                                                <select required name="SERVICE_ID" id="SERVICE_ID" onchange="selectThisService(this);">
                                                    <option value="">Select Service</option>
                                                    <?php
                                                    $row = $db->Execute("SELECT DOA_SERVICE_MASTER.PK_SERVICE_MASTER, DOA_SERVICE_CODE.PK_SERVICE_CODE, DOA_SERVICE_MASTER.SERVICE_NAME, DOA_SERVICE_CODE.SERVICE_CODE, DOA_SERVICE_CODE.DURATION, DOA_SERVICE_CODE.CAPACITY FROM DOA_SERVICE_CODE LEFT JOIN DOA_SERVICE_MASTER ON DOA_SERVICE_CODE.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER WHERE DOA_SERVICE_MASTER.ACTIVE = 1 AND DOA_SERVICE_CODE.IS_GROUP = 1 AND DOA_SERVICE_MASTER.PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER']);
                                                    while (!$row->EOF) { ?>
                                                        <option value="<?=$row->fields['DURATION'].','.$row->fields['PK_SERVICE_CODE'].','.$row->fields['PK_SERVICE_MASTER'];?>"><?=$row->fields['SERVICE_NAME'].' || '.$row->fields['SERVICE_CODE'];?></option>
                                                    <?php $row->MoveNext(); } ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-3">
                                            <div class="form-group">
                                                <label class="form-label">Primary <?=$service_provider_title?> <span class="text-danger">*</span></label>
                                                <select name="SERVICE_PROVIDER_ID_1" class="SERVICE_PROVIDER_ID" id="SERVICE_PROVIDER_ID_1" required>
                                                <option value="">Select <?=$service_provider_title?></option>

                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-3">
                                            <div class="form-group">
                                                <label class="form-label">Secondary <?=$service_provider_title?></label>
                                                <select name="SERVICE_PROVIDER_ID_2" class="SERVICE_PROVIDER_ID" id="SERVICE_PROVIDER_ID_2">
                                                <option value="">Select <?=$service_provider_title?></option>

                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-3">
                                            <div class="form-group">
                                                <label class="form-label">Location</label>
                                                <select class="form-control" name="PK_LOCATION" id="PK_LOCATION">
                                                    <option value="">Select Location</option>
                                                    <?php
                                                    $row = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME FROM DOA_LOCATION WHERE ACTIVE = 1 AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
                                                    while (!$row->EOF) { ?>
                                                        <option value="<?php echo $row->fields['PK_LOCATION'];?>" <?=($row->fields['PK_LOCATION']==$PK_LOCATION)?'selected':''?>><?=$row->fields['LOCATION_NAME']?></option>
                                                    <?php $row->MoveNext(); } ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-2">
                                            <div class="form-group">
                                                <label class="form-label">Starting On<span class="text-danger">*</span></label><br>
                                                <input class="form-control datepicker-normal" type="text" name="STARTING_ON" required>
                                            </div>
                                        </div>
                                        <div class="col-2">
                                            <div class="form-group">
                                                <label class="form-label">Time<span class="text-danger">*</span></label><br>
                                                <input class="form-control timepicker-normal" type="text" name="START_TIME" required>
                                            </div>
                                        </div>
                                        <div class="col-2">
                                            <div class="form-group">
                                                <label class="form-label">Select Days</label><br>
                                                <label><input type="checkbox" class="DAYS" name="DAYS[]" value="monday"> Monday</label><br>
                                                <label><input type="checkbox" class="DAYS" name="DAYS[]" value="tuesday"> Tuesday</label><br>
                                                <label><input type="checkbox" class="DAYS" name="DAYS[]" value="wednesday"> Wednesday</label><br>
                                                <label><input type="checkbox" class="DAYS" name="DAYS[]" value="thursday"> Thursday</label><br>
                                                <label><input type="checkbox" class="DAYS" name="DAYS[]" value="friday"> Friday</label><br>
                                                <label><input type="checkbox" class="DAYS" name="DAYS[]" value="saturday"> Saturday</label><br>
                                                <label><input type="checkbox" class="DAYS" name="DAYS[]" value="sunday"> Sunday</label><br>
                                            </div>
                                        </div>
                                        <div class="col-2 occurrence_div">
                                            <div class="form-group">
                                                <label class="form-label">Select Occurrence<span class="text-danger">*</span></label><br>
                                                <label><input type="radio" name="OCCURRENCE" value="WEEKLY" required> Weekly</label><br>
                                                <label><input type="radio" name="OCCURRENCE" value="DAYS" required> Every <input type="text" name="OCCURRENCE_DAYS" style="width: 45px;"> Days</label>
                                            </div>
                                        </div>
                                        <div class="col-2">
                                            <div class="form-group">
                                                <label class="form-label">Length<span class="text-danger">*</span></label><br>
                                                <input type="number" class="form-control" name="LENGTH" style="width: 80px;" required>
                                                <select class="form-control" name="FREQUENCY" style="width: 100px;" required>
                                                    <option value="week">Week(S)</option>
                                                    <option value="month">Month(S)</option>
                                                    <option value="year">Year(S)</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <?php if ($time_zone == 1){ ?>
                                        <div class="form-group" style="margin-top: 25px;">
                                            <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">SAVE</button>
                                            <button type="button" id="cancel_button" class="btn btn-inverse waves-effect waves-light">Cancel</button>
                                        </div>
                                    <?php } else { ?>
                                        <div class="alert alert-danger mt-2">
                                            <strong>Warning!</strong> Please go to your Business Profile or Location Profile to set the timezone first.
                                        </div>
                                    <?php } ?>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>


        </div>
    </div>
    <?php require_once('../includes/footer.php');?>
    <script src="../assets/sumoselect/jquery.sumoselect.min.js"></script>

    <script type="text/javascript">
        $('.datepicker-normal').datepicker({
            format: 'mm/dd/yyyy',
        });

        $('.timepicker-normal').timepicker({
            timeFormat: 'hh:mm p',
            minTime: '01:00 PM',
            maxTime: '09:00 PM'
        });

        $('.DAYS').on('change', function(){
            if ($('.DAYS').is(':checked')){
                $("input[name='OCCURRENCE'][value='WEEKLY']").prop('checked', true);
                $('.occurrence_div').addClass('disable-div');
            } else {
                $("input[name='OCCURRENCE'][value='WEEKLY']").prop('checked', false);
                $('.occurrence_div').removeClass('disable-div');
            }
        });

        $('#SERVICE_ID').SumoSelect({placeholder: 'Select Services', search: true, searchText: 'Search...'});
        $('.SERVICE_PROVIDER_ID').SumoSelect({placeholder: 'Select <?=$service_provider_title?>', search: true, searchText: 'Search...'});

        function selectThisService(param) {
            let SERVICE_ID = $(param).val();
            $.ajax({
                url: "ajax/get_service_provider.php",
                type: "POST",
                data: {PK_ENROLLMENT_MASTER: SERVICE_ID},
                async: false,
                cache: false,
                success: function (result) {
                    $('.SERVICE_PROVIDER_ID').empty();
                    $('.SERVICE_PROVIDER_ID').append(result);
                    $('#SERVICE_PROVIDER_ID_1')[0].sumo.reload();
                    $('#SERVICE_PROVIDER_ID_2')[0].sumo.reload();
                    $('#SERVICE_PROVIDER_ID_2').prop('required', false);
                }
            });
        }
    </script>


</body>
</html>