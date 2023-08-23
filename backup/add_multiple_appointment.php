<?php
require_once('../global/config.php');

if (empty($_GET['id']))
    $title = "Add Appointment";
else
    $title = "Edit Appointment";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 2 ){
    header("location:../login.php");
    exit;
}

if (isset($_POST['FUNCTION_NAME'])){
    $PK_ENROLLMENT_MASTER_ARRAY = explode(',', $_POST['PK_ENROLLMENT_MASTER']);
    $PK_ENROLLMENT_MASTER = $PK_ENROLLMENT_MASTER_ARRAY[0];
    $PK_ENROLLMENT_SERVICE = $PK_ENROLLMENT_MASTER_ARRAY[1];
    $PK_SERVICE_MASTER = $PK_ENROLLMENT_MASTER_ARRAY[2];
    $PK_SERVICE_CODE = $PK_ENROLLMENT_MASTER_ARRAY[3];

    $session_cost = $db->Execute("SELECT * FROM `DOA_ENROLLMENT_SERVICE` WHERE PK_SERVICE_MASTER = '$PK_SERVICE_MASTER' AND PK_SERVICE_CODE = '$PK_SERVICE_CODE'");
    $price_per_session = $session_cost->fields['PRICE_PER_SESSION'];
    if(empty($_POST['PK_APPOINTMENT_MASTER'])){
        $DURATION = $_POST['DURATION'];
        $NUMBER_OF_SESSION = $_POST['NUMBER_OF_SESSION'];
        $STARTING_ON = $_POST['STARTING_ON'];
        $LENGTH = $_POST['LENGTH'];
        $FREQUENCY = $_POST['FREQUENCY'];
        $END_DATE = date('Y-m-d', strtotime('+ ' . $LENGTH . ' ' . $FREQUENCY, strtotime($STARTING_ON)));

        $START_TIME = $_POST['START_TIME'];
        $END_TIME = date("H:i", strtotime($START_TIME)+($DURATION*60));

        $APPOINTMENT_DATE_ARRAY = [];
        if (!empty($_POST['OCCURRENCE'])){
            $APPOINTMENT_DATE = date('Y-m-d', strtotime($STARTING_ON));
            if ($_POST['OCCURRENCE'] == 'WEEKLY'){
                if (isset($_POST['DAYS'])) {
                    $DAYS = $_POST['DAYS'];
                } else {
                    $DAYS[] = strtolower(date('l', strtotime($STARTING_ON)));
                }
                while ($APPOINTMENT_DATE < $END_DATE) {
                    $appointment_day = date('l', strtotime($APPOINTMENT_DATE));
                    if (in_array(strtolower($appointment_day), $DAYS)){
                        $APPOINTMENT_DATE_ARRAY[] = $APPOINTMENT_DATE;
                    }
                    $APPOINTMENT_DATE = date('Y-m-d', strtotime('+1 day ', strtotime($APPOINTMENT_DATE)));
                    /*for ($i = 0; $i < count($DAYS); $i++) {
                        $day = $DAYS[$i];
                        $appointment_day = date('l', strtotime($APPOINTMENT_DATE));
                        echo $appointment_day." - ".$day."<br>";
                        if ($day == strtolower($appointment_day)){
                            $APPOINTMENT_DATE_ARRAY[] = $APPOINTMENT_DATE;
                        }
                        $APPOINTMENT_DATE = date('Y-m-d', strtotime('+1 day ', strtotime($APPOINTMENT_DATE)));
                        echo $APPOINTMENT_DATE . "<br>";
                    }*/
                }
            }else {
                $OCCURRENCE_DAYS = (empty($_POST['OCCURRENCE_DAYS']))?7:$_POST['OCCURRENCE_DAYS'];

                while ($APPOINTMENT_DATE < $END_DATE) {
                    $APPOINTMENT_DATE_ARRAY[] = $APPOINTMENT_DATE;
                    $APPOINTMENT_DATE = date('Y-m-d', strtotime('+ '.$OCCURRENCE_DAYS.' day', strtotime($APPOINTMENT_DATE)));
                    //echo $APPOINTMENT_DATE . "<br>";
                }
            }
        }

        $session_created_data = $db->Execute("SELECT COUNT(PK_APPOINTMENT_MASTER) AS SESSION_COUNT FROM `DOA_APPOINTMENT_MASTER` WHERE `PK_SERVICE_MASTER` = ".$PK_SERVICE_MASTER." AND PK_ENROLLMENT_MASTER = ".$PK_ENROLLMENT_MASTER);
        $SESSION_CREATED = $session_created_data->fields['SESSION_COUNT'];
        $SESSION_LEFT = $NUMBER_OF_SESSION-$SESSION_CREATED;

        if (count($APPOINTMENT_DATE_ARRAY) > 0) {
            $SESSION_WILL_CREATE = (count($APPOINTMENT_DATE_ARRAY) < $SESSION_LEFT) ? count($APPOINTMENT_DATE_ARRAY) : $SESSION_LEFT;
            for ($i = 0; $i < $SESSION_WILL_CREATE; $i++) {
                $APPOINTMENT_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
                $APPOINTMENT_DATA['CUSTOMER_ID'] = $_POST['CUSTOMER_ID'];

                $PK_ENROLLMENT_MASTER_ARRAY = explode(',', $_POST['PK_ENROLLMENT_MASTER']);
                $APPOINTMENT_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
                $APPOINTMENT_DATA['PK_ENROLLMENT_SERVICE'] = $PK_ENROLLMENT_SERVICE;
                $APPOINTMENT_DATA['PK_SERVICE_MASTER'] = $PK_SERVICE_MASTER;
                $APPOINTMENT_DATA['PK_SERVICE_CODE'] = $PK_SERVICE_CODE;

                $APPOINTMENT_DATA['SERVICE_PROVIDER_ID'] = $_POST['SERVICE_PROVIDER_ID'];
                $APPOINTMENT_DATA['DATE'] = $APPOINTMENT_DATE_ARRAY[$i];
                $APPOINTMENT_DATA['START_TIME'] = date('H:i:s', strtotime($START_TIME));
                $APPOINTMENT_DATA['END_TIME'] = date('H:i:s', strtotime($END_TIME));
                $APPOINTMENT_DATA['PK_APPOINTMENT_STATUS'] = 1;
                $APPOINTMENT_DATA['ACTIVE'] = 1;
                $APPOINTMENT_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
                $APPOINTMENT_DATA['CREATED_ON'] = date("Y-m-d H:i");
                db_perform('DOA_APPOINTMENT_MASTER', $APPOINTMENT_DATA, 'insert');
            }
        }
    }else{
        //$_POST['ACTIVE'] = $_POST['ACTIVE'];
        if($_FILES['IMAGE']['name'] != ''){
            $extn 			= explode(".",$_FILES['IMAGE']['name']);
            $iindex			= count($extn) - 1;
            $rand_string 	= time()."-".rand(100000,999999);
            $file11			= 'appointment_image_'.$_SESSION['PK_USER'].$rand_string.".".$extn[$iindex];
            $extension   	= strtolower($extn[$iindex]);

            if($extension == "gif" || $extension == "jpeg" || $extension == "pjpeg" || $extension == "png" || $extension == "jpg"){
                $image_path    = '../uploads/appointment_image/'.$file11;
                move_uploaded_file($_FILES['IMAGE']['tmp_name'], $image_path);
                $_POST['IMAGE'] = $image_path;
            }
        }
        $_POST['EDITED_BY']	= $_SESSION['PK_USER'];
        $_POST['EDITED_ON'] = date("Y-m-d H:i");
        db_perform('DOA_APPOINTMENT_MASTER', $_POST, 'update'," PK_APPOINTMENT_MASTER =  '$_POST[PK_APPOINTMENT_MASTER]'");

        if ($_POST['PK_APPOINTMENT_STATUS'] == 2 || ($_POST['PK_APPOINTMENT_STATUS'] == 4 && $_POST['NO_SHOW'] == 'Charge')) {
            $enrollment_balance = $db->Execute("SELECT * FROM `DOA_ENROLLMENT_BALANCE` WHERE PK_ENROLLMENT_MASTER = '$_POST[PK_ENROLLMENT_MASTER]'");
            if ($enrollment_balance->RecordCount() > 0) {
                $ENROLLMENT_BALANCE_DATA['TOTAL_BALANCE_USED'] = $enrollment_balance->fields['TOTAL_BALANCE_USED'] + $price_per_session;
                $ENROLLMENT_BALANCE_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
                $ENROLLMENT_BALANCE_DATA['EDITED_ON'] = date("Y-m-d H:i");
                db_perform('DOA_ENROLLMENT_BALANCE', $ENROLLMENT_BALANCE_DATA, 'update', " PK_ENROLLMENT_MASTER =  '$_POST[PK_ENROLLMENT_MASTER]'");
            }
        }
    }

    rearrangeSerialNumber($_POST['PK_ENROLLMENT_MASTER'], $price_per_session);

    header("location:all_schedules.php");
}

function rearrangeSerialNumber($PK_ENROLLMENT_MASTER, $price_per_session){
    global $db;
    $appointment_data = $db->Execute("SELECT * FROM `DOA_APPOINTMENT_MASTER` WHERE PK_ENROLLMENT_MASTER = '$PK_ENROLLMENT_MASTER' ORDER BY DATE ASC");
    $total_bill_and_paid = $db->Execute("SELECT SUM(BILLED_AMOUNT) AS TOTAL_BILL, SUM(PAID_AMOUNT) AS TOTAL_PAID FROM DOA_ENROLLMENT_LEDGER WHERE `PK_ENROLLMENT_MASTER`=".$PK_ENROLLMENT_MASTER);
    $total_paid = $total_bill_and_paid->fields['TOTAL_PAID'];
    $total_paid_appointment = intval($total_paid/$price_per_session);
    $i = 1;
    while (!$appointment_data->EOF){
        $UPDATE_DATA['SERIAL_NUMBER'] = $i;
        if ($i <= $total_paid_appointment){
            $UPDATE_DATA['IS_PAID'] = 1;
        } else {
            $UPDATE_DATA['IS_PAID'] = 0;
        }
        db_perform('DOA_APPOINTMENT_MASTER', $UPDATE_DATA, 'update'," PK_APPOINTMENT_MASTER =  ".$appointment_data->fields['PK_APPOINTMENT_MASTER']);
        $appointment_data->MoveNext();
        $i++;
    }
}

if(empty($_GET['id'])){
    $CUSTOMER_ID = '';
    $PK_ENROLLMENT_MASTER = '';
    $PK_SERVICE_MASTER = '';
    $PK_SERVICE_CODE = '';
    $SERVICE_PROVIDER_ID = '';
    $PK_APPOINTMENT_STATUS = '';
    $NO_SHOW = '';
    $COMMENT = '';
    $IMAGE = '';
    $ACTIVE = '';
    $DATE = '';
    $DATE_ARR = [];
    $START_TIME = '';
    $END_TIME = '';
} else {
    $res = $db->Execute("SELECT * FROM `DOA_APPOINTMENT_MASTER` WHERE `PK_APPOINTMENT_MASTER` = '$_GET[id]'");

    if($res->RecordCount() == 0){
        header("location:all_schedules.php");
        exit;
    }

    $CUSTOMER_ID = $res->fields['CUSTOMER_ID'];
    $PK_ENROLLMENT_MASTER = $res->fields['PK_ENROLLMENT_MASTER'];
    $PK_SERVICE_MASTER = $res->fields['PK_SERVICE_MASTER'];
    $PK_SERVICE_CODE = $res->fields['PK_SERVICE_CODE'];
    $SERVICE_PROVIDER_ID = $res->fields['SERVICE_PROVIDER_ID'];
    $ACTIVE = $res->fields['ACTIVE'];
    $PK_APPOINTMENT_STATUS = $res->fields['PK_APPOINTMENT_STATUS'];
    $NO_SHOW = $res->fields['NO_SHOW'];
    $COMMENT = $res->fields['COMMENT'];
    $IMAGE = $res->fields['IMAGE'];
    $DATE = date("m/d/Y",strtotime($res->fields['DATE']));
    $DATE_ARR[0] = date("Y",strtotime($res->fields['DATE']));
    $DATE_ARR[1] = date("m",strtotime($res->fields['DATE'])) -1;
    $DATE_ARR[2] = date("d",strtotime($res->fields['DATE']));
    $START_TIME = $res->fields['START_TIME'];
    $END_TIME = $res->fields['END_TIME'];
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
<link rel="stylesheet" href="../assets/CalendarPicker/CalendarPicker.style.css">
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
                            <li class="breadcrumb-item"><a href="../admin/all_schedules.php">All Appointment</a></li>
                            <li class="breadcrumb-item active"><?=$title?></li>
                        </ol>

                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <form id="multi_appointment_form" method="post" action="">
                                <input type="hidden" name="FUNCTION_NAME" value="saveMultiAppointmentData">
                                <input type="hidden" name="IS_SUBMIT" id="IS_SUBMIT" value="0">
                                <div class="p-40" style="padding-top: 10px;">
                                    <div class="row">
                                        <div class="col-3">
                                            <div class="form-group">
                                                <label class="form-label">Customer<span class="text-danger">*</span></label><br>
                                                <select required name="CUSTOMER_ID" id="CUSTOMER_ID" onchange="selectThisCustomer(this);">
                                                    <option value="">Select Customer</option>
                                                    <?php
                                                    $row = $db->Execute("SELECT DOA_USERS.PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.PHONE, DOA_USERS.PK_LOCATION, DOA_USERS.ACTIVE, DOA_USER_MASTER.PK_USER_MASTER FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER WHERE DOA_USERS.PK_ROLES = 4 AND DOA_USER_MASTER.PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]' AND DOA_USERS.ACTIVE = 1 ORDER BY FIRST_NAME");
                                                    while (!$row->EOF) { ?>
                                                        <option value="<?php echo $row->fields['PK_USER_MASTER'];?>"><?=$row->fields['NAME'].' ('.$row->fields['PHONE'].')'?></option>
                                                    <?php $row->MoveNext(); } ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-5">
                                            <div class="form-group">
                                                <label class="form-label">Enrollment ID<span class="text-danger">*</span></label>
                                                <select class="form-control" required name="PK_ENROLLMENT_MASTER" id="PK_ENROLLMENT_MASTER" onchange="selectThisEnrollment(this);">
                                                    <option value="">Select Enrollment ID</option>

                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-3">
                                            <div class="form-group">
                                                <label class="form-label"><?=$service_provider_title?><span class="text-danger">*</span></label>
                                                <select required name="SERVICE_PROVIDER_ID" id="SERVICE_PROVIDER_ID" <!--onchange="getSlots()"-->>
                                                    <option value="">Select <?=$service_provider_title?></option>

                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <input type="hidden" id="DURATION" name="DURATION">
                                    <input type="hidden" id="NUMBER_OF_SESSION" name="NUMBER_OF_SESSION">
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
    <script src="../assets/CalendarPicker/CalendarPicker.js"></script>
    <script src="../assets/sumoselect/jquery.sumoselect.min.js"></script>

    <script type="text/javascript">
        $('.datepicker-normal').datepicker({
            format: 'mm/dd/yyyy',
        });

        $('.timepicker-normal').timepicker({
            timeFormat: 'hh:mm p',
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

        $('#CUSTOMER_ID').SumoSelect({placeholder: 'Select Customer', search: true, searchText: 'Search...'});
        $('#SERVICE_PROVIDER_ID').SumoSelect({placeholder: 'Select <?=$service_provider_title?>', search: true, searchText: 'Search...'});

        function set_time(id, start_time, end_time){
            $('#START_TIME').val(start_time);
            $('#END_TIME').val(end_time);
            let slot_btn  = $(".slot_btn");
            slot_btn.each(function (index) {
                if ($(this).data('is_disable') == 0) {
                    $(this).css('background-color', 'greenyellow');
                }
            })
            document.getElementById('slot_btn_'+id).style.setProperty('background-color', 'orange', 'important');
        }

        $(document).on('click', '#cancel_button', function () {
            window.location.href='all_schedules.php'
        });

        function selectThisCustomer(param) {
            let PK_USER_MASTER = $(param).val();
            $.ajax({
                url: "ajax/get_enrollments.php",
                type: "POST",
                data: {PK_USER_MASTER: PK_USER_MASTER},
                async: false,
                cache: false,
                success: function (result) {
                    $('#PK_ENROLLMENT_MASTER').empty();
                    $('#PK_ENROLLMENT_MASTER').append(result);
                }
            });
        }

        function selectThisEnrollment(param) {
            let PK_ENROLLMENT_MASTER = $(param).val();
            $.ajax({
                url: "ajax/get_service_provider.php",
                type: "POST",
                data: {PK_ENROLLMENT_MASTER: PK_ENROLLMENT_MASTER},
                async: false,
                cache: false,
                success: function (result) {
                    $('#SERVICE_PROVIDER_ID').empty();
                    $('#SERVICE_PROVIDER_ID').append(result);
                    $('#SERVICE_PROVIDER_ID')[0].sumo.reload();

                    let no_of_session = $('#PK_ENROLLMENT_MASTER').find(':selected').data('no_of_session');
                    $('#NUMBER_OF_SESSION').val(no_of_session);
                    let duration = $('#PK_ENROLLMENT_MASTER').find(':selected').data('duration');
                    $('#DURATION').val(duration);
                }
            });
        }

        $(document).on('submit', '#multi_appointment_form', function (event) {
            event.preventDefault();
            let form_data = $('#multi_appointment_form').serialize();
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                data: form_data,
                success:function (data) {
                    if (data > 0) {
                        let conf = confirm(`According to the number of classes in the enrollment, ${data} appointments were generated.`);
                        if(conf) {
                            submitAppointmentForm();
                        }
                    } else {
                        submitAppointmentForm();
                    }
                    // window.location.href='all_schedules.php';
                }
            });
        });

        function submitAppointmentForm() {
            $('#IS_SUBMIT').val(1);
            let form_data = $('#multi_appointment_form').serialize();
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                data: form_data,
                success:function (data) {
                    window.location.href='all_schedules.php';
                }
            });
        }
    </script>
</body>
</html>