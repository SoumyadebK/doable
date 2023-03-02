<?php
require_once('../global/config.php');

use Twilio\Exceptions\ConfigurationException;
use Twilio\Rest\Client;

if (empty($_GET['id']))
    $title = "Add Appointment";
else
    $title = "Edit Appointment";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 2 ){
    header("location:../login.php");
    exit;
}

if (isset($_POST['FUNCTION_NAME'])){
    unset($_POST['TIME']);
    unset($_POST['FUNCTION_NAME']);
    if (empty($_POST['START_TIME']) || empty($_POST['END_TIME'])){
        unset($_POST['START_TIME']);
        unset($_POST['END_TIME']);
    }
    $session_cost = $db->Execute("SELECT * FROM `DOA_ENROLLMENT_SERVICE` WHERE PK_SERVICE_MASTER = '$_POST[PK_SERVICE_MASTER]' AND PK_SERVICE_CODE = '$_POST[PK_SERVICE_CODE]'");
    $price_per_session = $session_cost->fields['PRICE_PER_SESSION'];
    if(empty($_POST['PK_APPOINTMENT_MASTER'])){
        $START_TIME_ARRAY = explode(',', $_POST['START_TIME']);
        $END_TIME_ARRAY = explode(',', $_POST['END_TIME']);
        for ($i=0; $i<count($START_TIME_ARRAY); $i++) {
            $APPOINTMENT_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
            $APPOINTMENT_DATA['CUSTOMER_ID'] = $_POST['CUSTOMER_ID'];

            $PK_ENROLLMENT_MASTER_ARRAY = explode(',', $_POST['PK_ENROLLMENT_MASTER']);
            $APPOINTMENT_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER_ARRAY[0];
            $APPOINTMENT_DATA['PK_ENROLLMENT_SERVICE'] = $PK_ENROLLMENT_MASTER_ARRAY[1];
            $APPOINTMENT_DATA['PK_SERVICE_MASTER'] = $PK_ENROLLMENT_MASTER_ARRAY[2];
            $APPOINTMENT_DATA['PK_SERVICE_CODE'] = $PK_ENROLLMENT_MASTER_ARRAY[3];

            $APPOINTMENT_DATA['SERVICE_PROVIDER_ID'] = $_POST['SERVICE_PROVIDER_ID'];
            $APPOINTMENT_DATA['DATE'] = $_POST['DATE'];
            $APPOINTMENT_DATA['START_TIME'] = $START_TIME_ARRAY[$i];
            $APPOINTMENT_DATA['END_TIME'] = $END_TIME_ARRAY[$i];
            $APPOINTMENT_DATA['PK_APPOINTMENT_STATUS'] = 1;
            $APPOINTMENT_DATA['ACTIVE'] = 1;
            $APPOINTMENT_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
            $APPOINTMENT_DATA['CREATED_ON'] = date("Y-m-d H:i");
            db_perform('DOA_APPOINTMENT_MASTER', $APPOINTMENT_DATA, 'insert');

            require_once("../global/vendor/twilio/sdk/src/Twilio/autoload.php");
            $text_setting = $db->Execute( "SELECT * FROM `DOA_TEXT_SETTINGS`");
            $customer_phone_number = $db->Execute("SELECT DOA_USERS.PHONE FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USER_MASTER.PK_USER=DOA_USERS.PK_USER WHERE DOA_USER_MASTER.PK_USER_MASTER = '$_POST[CUSTOMER_ID]'");

            $sid = $text_setting->fields['SID'];
            $token = $TOKEN = $text_setting->fields['TOKEN'];
            try {
                $client = new Client($sid, $token);
                $client->messages->create(
                    // the number you'd like to send the message to
                    $customer_phone_number->fields['PHONE'],
                    [
                        // A Twilio phone number you purchased at twilio.com/console
                        'from' => $text_setting->fields['FROM_NO'],
                        // the body of the text message you'd like to send
                        'body' => "An appointment is created for you."
                    ]
                );
            } catch (\Twilio\Exceptions\TwilioException $e) {

            }
        }
    }else{
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

    header("location:all_schedules.php?view=list");
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
    $SERIAL_NUMBER = '';
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
        header("location:all_schedules.php?view=list");
        exit;
    }

    $CUSTOMER_ID = $res->fields['CUSTOMER_ID'];
    $PK_ENROLLMENT_MASTER = $res->fields['PK_ENROLLMENT_MASTER'];
    $SERIAL_NUMBER = $res->fields['SERIAL_NUMBER'];
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
                                <input type="hidden" name="FUNCTION_NAME" value="saveAppointmentData">
                                <input type="hidden" name="PK_APPOINTMENT_MASTER" class="PK_APPOINTMENT_MASTER" value="<?=(empty($_GET['id']))?'':$_GET['id']?>">
                                <div class="p-40" style="padding-top: 10px;">
                                    <?php if(empty($_GET['id'])) { ?>
                                        <div class="row">
                                            <div class="col-3">
                                                <div class="form-group">
                                                    <label class="form-label">Customer<span class="text-danger">*</span></label><br>
                                                    <select required name="CUSTOMER_ID" id="CUSTOMER_ID" onchange="selectThisCustomer(this);">
                                                        <option value="">Select Customer</option>
                                                        <?php
                                                        $row = $db->Execute("SELECT DOA_USERS.PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_ID, DOA_USERS.EMAIL_ID, DOA_USERS.PHONE, DOA_USERS.PK_LOCATION, DOA_USERS.ACTIVE, DOA_USER_MASTER.PK_USER_MASTER FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER WHERE DOA_USERS.PK_ROLES = 4 AND DOA_USER_MASTER.PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]' AND DOA_USERS.ACTIVE = 1 ORDER BY FIRST_NAME");
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
                                            <!--<div class="col-2">
                                                <div class="form-group">
                                                    <label class="form-label">Service<span class="text-danger">*</span></label>
                                                    <select class="form-control" required name="PK_SERVICE_MASTER" id="PK_SERVICE_MASTER" onchange="selectThisService(this);">
                                                        <option value="">Select Service</option>

                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-2">
                                                <div class="form-group">
                                                    <label class="form-label">Service Code<span class="text-danger">*</span></label>
                                                    <select class="form-control" required name="PK_SERVICE_CODE" id="PK_SERVICE_CODE" onchange="getSlots()">
                                                        <option value="">Select Service Code</option>

                                                    </select>
                                                </div>
                                            </div>-->
                                            <div class="col-3">
                                                <div class="form-group">
                                                    <label class="form-label"><?=$service_provider_title?><span class="text-danger">*</span></label>
                                                    <select required name="SERVICE_PROVIDER_ID" id="SERVICE_PROVIDER_ID" onchange="getSlots()">
                                                        <option value="">Select <?=$service_provider_title?></option>

                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    <?php } else { ?>
                                        <div class="row">
                                            <div class="col-2">
                                                <div class="form-group">
                                                    <label class="form-label">Name: </label>
                                                    <div id="customer_select" style="display: none;">
                                                        <select name="CUSTOMER_ID" id="CUSTOMER_ID" onchange="selectThisCustomer(this);">
                                                            <option value="">Select Customer</option>
                                                            <?php
                                                            $selected_customer = '';
                                                            $selected_customer_id = '';
                                                            $selected_user_id = '';
                                                            $row = $db->Execute("SELECT DOA_USERS.PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_ID, DOA_USERS.EMAIL_ID, DOA_USERS.PHONE, DOA_USERS.PK_LOCATION, DOA_USERS.ACTIVE, DOA_USER_MASTER.PK_USER_MASTER FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER WHERE DOA_USERS.PK_ROLES = 4 AND DOA_USER_MASTER.PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]' AND DOA_USERS.ACTIVE = 1 ORDER BY FIRST_NAME");
                                                            while (!$row->EOF) { if (($CUSTOMER_ID==$row->fields['PK_USER_MASTER'])){$selected_customer = $row->fields['NAME']; $customer_phone = $row->fields['PHONE']; $customer_email = $row->fields['EMAIL_ID']; $selected_customer_id = $row->fields['PK_USER_MASTER']; $selected_user_id = $row->fields['PK_USER'];} ?>
                                                                <option value="<?php echo $row->fields['PK_USER_MASTER'];?>" <?=($CUSTOMER_ID==$row->fields['PK_USER_MASTER'])?'selected':''?>><?=$row->fields['NAME'].' ('.$row->fields['PHONE'].')'?></option>
                                                            <?php $row->MoveNext(); } ?>
                                                        </select>
                                                    </div>
                                                    <p><a href="customer.php?id=<?=$selected_customer_id?>&master_id=<?=$selected_customer_id?>&tab=profile" target="_blank"><?=$selected_customer?></a></p>
                                                </div>
                                            </div>
                                            <div class="col-2">
                                                <div class="form-group">
                                                    <label class="form-label">Phone: </label>
                                                    <p><?=$customer_phone?></p>
                                                </div>
                                            </div>
                                            <div class="col-2">
                                                <div class="form-group">
                                                    <label class="form-label">Email: </label>
                                                    <p><?=$customer_email?></p>
                                                </div>
                                            </div>
                                            <div class="col-2">
                                                <div class="form-group">
                                                    <label class="form-label">Enrollment ID : </label>
                                                    <select class="form-control" name="PK_ENROLLMENT_MASTER" id="PK_ENROLLMENT_MASTER" style="display: none;" onchange="selectThisEnrollment(this);">
                                                        <option value="">Select Enrollment ID</option>
                                                        <?php
                                                        $selected_enrollment = '';
                                                        $row = $db->Execute("SELECT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_SERVICE, DOA_SERVICE_MASTER.PK_SERVICE_MASTER, DOA_SERVICE_CODE.PK_SERVICE_CODE, DOA_ENROLLMENT_MASTER.ENROLLMENT_ID, DOA_SERVICE_MASTER.SERVICE_NAME, DOA_SERVICE_CODE.SERVICE_CODE, DOA_SERVICE_CODE.DURATION, DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION FROM DOA_ENROLLMENT_MASTER RIGHT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER LEFT JOIN DOA_SERVICE_MASTER ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER LEFT JOIN DOA_SERVICE_CODE ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE WHERE DOA_ENROLLMENT_MASTER.STATUS = 'A' AND DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = ".$PK_ENROLLMENT_MASTER);

                                                        //$row = $db->Execute("SELECT PK_ENROLLMENT_MASTER, ENROLLMENT_ID FROM DOA_ENROLLMENT_MASTER WHERE PK_ENROLLMENT_MASTER = ".$PK_ENROLLMENT_MASTER);
                                                        while (!$row->EOF) { if($PK_ENROLLMENT_MASTER==$row->fields['PK_ENROLLMENT_MASTER']){$selected_enrollment = $row->fields['ENROLLMENT_ID'];} ?>
                                                            <option value="<?php echo $row->fields['PK_ENROLLMENT_MASTER'].','.$row->fields['PK_ENROLLMENT_SERVICE'].','.$row->fields['PK_SERVICE_MASTER'].','.$row->fields['PK_SERVICE_CODE'];?>" data-duration="<?=$row->fields['DURATION']?>" <?=($PK_ENROLLMENT_MASTER==$row->fields['PK_ENROLLMENT_MASTER'])?'selected':''?>><?=$row->fields['ENROLLMENT_ID']?></option>
                                                        <?php $row->MoveNext(); } ?>
                                                    </select>
                                                    <p><?=$selected_enrollment?></p>
                                                </div>
                                            </div>
                                            <div class="col-2">
                                                <div class="form-group">
                                                    <label class="form-label">Apt #: </label>
                                                    <p><?=$SERIAL_NUMBER?></p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-2">
                                                <div class="form-group">
                                                    <label class="form-label">Service : </label>
                                                    <select class="form-control" name="PK_SERVICE_MASTER" id="PK_SERVICE_MASTER" style="display: none;" onchange="selectThisService(this);">
                                                        <option value="">Select Service</option>
                                                        <?php
                                                        $selected_service = '';
                                                        $row = $db->Execute("SELECT DISTINCT(DOA_SERVICE_MASTER.PK_SERVICE_MASTER), DOA_SERVICE_MASTER.SERVICE_NAME FROM DOA_SERVICE_MASTER JOIN DOA_ENROLLMENT_SERVICE ON DOA_SERVICE_MASTER.PK_SERVICE_MASTER = DOA_ENROLLMENT_SERVICE.PK_SERVICE_MASTER LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_SERVICE_MASTER.PK_SERVICE_CLASS = 2 AND DOA_ENROLLMENT_MASTER.PK_USER_MASTER = ".$CUSTOMER_ID);
                                                        while (!$row->EOF) { if($PK_SERVICE_MASTER==$row->fields['PK_SERVICE_MASTER']){$selected_service = $row->fields['SERVICE_NAME'];} ?>
                                                            <option value="<?php echo $row->fields['PK_SERVICE_MASTER'];?>" <?=($PK_SERVICE_MASTER==$row->fields['PK_SERVICE_MASTER'])?'selected':''?>><?=$row->fields['SERVICE_NAME']?></option>
                                                        <?php $row->MoveNext(); } ?>
                                                    </select>
                                                    <p><?=$selected_service?></p>
                                                </div>
                                            </div>
                                            <div class="col-2">
                                                <div class="form-group">
                                                    <label class="form-label">Service Code : </label>
                                                    <select class="form-control" name="PK_SERVICE_CODE" id="PK_SERVICE_CODE" style="display: none;" onchange="getSlots()">
                                                        <option value="">Select Service Code</option>
                                                        <?php
                                                        $selected_service_code = '';
                                                        $row = $db->Execute("SELECT * FROM DOA_SERVICE_CODE WHERE PK_SERVICE_MASTER = ".$PK_SERVICE_MASTER);
                                                        while (!$row->EOF) { if($PK_SERVICE_CODE==$row->fields['PK_SERVICE_CODE']){$selected_service_code = $row->fields['SERVICE_CODE'];} ?>
                                                            <option value="<?php echo $row->fields['PK_SERVICE_CODE'];?>" data-duration="<?=$row->fields['DURATION']?>" <?=($PK_SERVICE_CODE==$row->fields['PK_SERVICE_CODE'])?'selected':''?>><?=$row->fields['SERVICE_CODE']?></option>
                                                        <?php $row->MoveNext(); } ?>
                                                    </select>
                                                    <p><?=$selected_service_code?></p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-3">
                                                <div class="form-group">
                                                    <label class="form-label"><?=$service_provider_title?> : <span id="change_service_provider" style="margin-left: 30px;"><a href="javascript:;" onclick="changeServiceProvider()">Change</a></span>
                                                        <span id="cancel_change_service_provider" style="margin-left: 30px; display: none;"><a href="javascript:;" onclick="cancelChangeServiceProvider()">Cancel</a></span></label>
                                                    <div id="service_provider_select" style="display: none;">
                                                        <select name="SERVICE_PROVIDER_ID" id="SERVICE_PROVIDER_ID" onchange="getSlots()">
                                                            <option value="">Select <?=$service_provider_title?></option>
                                                            <?php
                                                            $selected_service_provider = '';
                                                            $row = $db->Execute("SELECT DOA_USERS.PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME FROM DOA_USERS JOIN DOA_SERVICE_PROVIDER_SERVICES ON DOA_USERS.PK_USER = DOA_SERVICE_PROVIDER_SERVICES.PK_USER WHERE DOA_SERVICE_PROVIDER_SERVICES.PK_SERVICE_MASTER LIKE '".$PK_SERVICE_MASTER."' OR DOA_SERVICE_PROVIDER_SERVICES.PK_SERVICE_MASTER LIKE '%,".$PK_SERVICE_MASTER.",%' OR DOA_SERVICE_PROVIDER_SERVICES.PK_SERVICE_MASTER LIKE '".$PK_SERVICE_MASTER.",%' OR DOA_SERVICE_PROVIDER_SERVICES.PK_SERVICE_MASTER LIKE '%,".$PK_SERVICE_MASTER."'");
                                                            while (!$row->EOF) { if($SERVICE_PROVIDER_ID==$row->fields['PK_USER']){$selected_service_provider = $row->fields['NAME'];} ?>
                                                                <option value="<?php echo $row->fields['PK_USER'];?>" <?=($SERVICE_PROVIDER_ID==$row->fields['PK_USER'])?'selected':''?>><?=$row->fields['NAME']?></option>
                                                                <?php $row->MoveNext(); } ?>
                                                        </select>
                                                    </div>
                                                    <p id="service_provider_name"><?=$selected_service_provider?></p>
                                                </div>
                                            </div>
                                            <div class="col-3">
                                                <div class="form-group">
                                                    <label class="form-label">Date : </label>
                                                    <p><?=$DATE?></p>
                                                </div>
                                            </div>
                                            <div class="col-3">
                                                <div class="form-group">
                                                    <label class="form-label">Time : <span id="reschedule" style="margin-left: 80px;"><a href="javascript:;" onclick="reschedule()">Reschedule</a></span></label>
                                                    <p><?=date('h:i A', strtotime($START_TIME)).' - '.date('h:i A', strtotime($END_TIME))?></p>
                                                </div>
                                            </div>
                                            <div class="col-3">
                                                <div class="form-group">
                                                    <span><a href="javascript:;" onclick="cancelAppointment()">Cancel Appointments</a></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div>
                                            <span id="cancel_reschedule" style="display: none;"><a href="javascript:;" onclick="cancelReschedule()">Cancel</a></span>
                                            <div id="date_time_div"></div>
                                        </div>
                                    <?php } ?>

                                    <input type="hidden" name="DATE" id="DATE">
                                    <input type="hidden" name="START_TIME" id="START_TIME">
                                    <input type="hidden" name="END_TIME" id="END_TIME">

                                    <div class="row" id="schedule_div" style="display: <?=empty($_GET['id'])?'':'none'?>;">
                                        <div class="col-7">
                                            <div id="showcase-wrapper">
                                                <div id="myCalendarWrapper">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-5" style="background-color: #add1b1;max-height:470px;overflow-y:scroll;" >
                                            <br />
                                            <div class="row" id="slot_div" >

                                            </div>
                                        </div>
                                    </div>

                                    <?php if(!empty($_GET['id'])) { ?>
                                        <div class="row m-t-25">
                                            <div class="col-3">
                                                <div class="form-group">
                                                    <label class="form-label">Status : <span id="change_status" style="margin-left: 30px;"><a href="javascript:;" onclick="changeStatus()">Change</a></span>
                                                        <span id="cancel_change_status" style="margin-left: 30px; display: none;"><a href="javascript:;" onclick="cancelChangeStatus()">Cancel</a></span></label><br>
                                                    <select class="form-control" name="PK_APPOINTMENT_STATUS" id="PK_APPOINTMENT_STATUS" style="display: none;" onchange="changeAppointmentStatus(this)">
                                                        <option value="">Select Status</option>
                                                        <?php
                                                        $selected_status = '';
                                                        $row = $db->Execute("SELECT * FROM `DOA_APPOINTMENT_STATUS` WHERE `ACTIVE` = 1");
                                                        while (!$row->EOF) { if($PK_APPOINTMENT_STATUS==$row->fields['PK_APPOINTMENT_STATUS']){$selected_status=$row->fields['APPOINTMENT_STATUS'];}?>
                                                            <option value="<?php echo $row->fields['PK_APPOINTMENT_STATUS'];?>" <?=($PK_APPOINTMENT_STATUS==$row->fields['PK_APPOINTMENT_STATUS'])?'selected':''?>><?=$row->fields['APPOINTMENT_STATUS']?></option>
                                                        <?php $row->MoveNext(); } ?>
                                                    </select>
                                                    <p id="appointment_status"><?=$selected_status?></p>
                                                </div>
                                            </div>
                                            <!-- <div class="col-3" style="width: 18%;">
                                                <div class="form-group m-t-30">
                                                    <a class="btn btn-info waves-effect waves-light m-r-10 text-white" onclick="cancelAppointment()">Cancel Appointments</a>
                                                </div>
                                            </div> -->
                                            <div class="col-2">
                                                <div class="form-group m-t-30">
                                                    <a class="btn btn-info waves-effect waves-light m-r-10 text-white" onclick="$('#add_info_div').slideToggle();">Add Info</a>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row" id="no_show_div" style="display: <?=($PK_APPOINTMENT_STATUS==4)?'':'none'?>;">
                                            <div class="col-3">
                                                <div class="form-group">
                                                    <label class="form-label">No Show</label>
                                                    <select name="NO_SHOW" id="NO_SHOW" class="form-control">
                                                        <option value="">Select</option>
                                                        <option value="Charge" <?=($NO_SHOW=='Charge')?'selected':''?>>Charge</option>
                                                        <option value="No Charge" <?=($NO_SHOW=='No Charge')?'selected':''?>>No Charge</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row" id="add_info_div" style="display: <?=($COMMENT)?'':'none'?>;">
                                            <div class="col-6">
                                                <div class="form-group">
                                                    <label class="form-label">Comment</label>
                                                    <textarea class="form-control" name="COMMENT" rows="6"><?=$COMMENT?></textarea>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="form-group">
                                                    <label class="form-label">Upload Image</label>
                                                    <input type="file" class="form-control" name="IMAGE" id="IMAGE">
                                                    <a href="<?=$IMAGE?>" target="_blank">
                                                        <img src="<?=$IMAGE?>" style="margin-top: 15px; width: 150px; height: auto;">
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php } ?>

                                    <?php if ($time_zone == 1){ ?>
                                        <div class="form-group" style="margin-top: 25px;">
                                            <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">SAVE</button>
                                            <button type="button" id="cancel_button" class="btn btn-inverse waves-effect waves-light">Cancel</button>
                                            <?php if(!empty($_GET['id'])) { ?>
                                                <a href="enrollment.php?customer_id=<?=$selected_customer_id;?>" target="_blank" class="btn btn-info waves-effect waves-light m-r-10 text-white">Enroll</a>
                                                <a href="customer.php?id=<?=$selected_user_id?>&master_id=<?=$selected_customer_id?>&tab=billing" target="_blank" class="btn btn-info waves-effect waves-light m-r-10 text-white">Pay</a>
                                                <a href="customer.php?id=<?=$selected_user_id?>&master_id=<?=$selected_customer_id?>&tab=appointment" target="_blank" class="btn btn-info waves-effect waves-light m-r-10 text-white">View Appointment</a>
                                            <?php } ?>

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
        let PK_APPOINTMENT_MASTER = <?=(empty($_GET['id']))?0:$_GET['id']?>;
        $('#CUSTOMER_ID').SumoSelect({placeholder: 'Select Customer', search: true, searchText: 'Search...'});
        $('#SERVICE_PROVIDER_ID').SumoSelect({placeholder: 'Select <?=$service_provider_title?>', search: true, searchText: 'Search...'});

        $(document).ready(function () {
            getSlots();
        });
        const nextYear 	= new Date().getFullYear() + 2;
        const month 	= new Date().getMonth();
        var def_date 	= new Date();
        <? if(!empty($DATE_ARR)) { ?>
            def_date = new Date(<?=$DATE_ARR[0]?>,<?=$DATE_ARR[1]?>,<?=$DATE_ARR[2]?>);
        <? } ?>
        const myCalender = new CalendarPicker('#myCalendarWrapper', {
            // If max < min or min > max then the only available day will be today.
            default_date: def_date,
            //min: new Date(),
            max: new Date(nextYear, month), // NOTE: new Date(nextYear, 10) is "Sun Nov 01 <nextYear>"
            disabled_days: []
        });

        $('#previous-month').on('click', function (event) {
            event.preventDefault();
        });

        $('#next-month').on('click', function (event) {
            event.preventDefault();
        });

        myCalender.onValueChange((currentValue) => {
           getSlots();
        });

        let start_time_array = [];
        let end_time_array = [];
        function set_time(id, start_time, end_time, PK_APPOINTMENT_MASTER){
            start_time_array.push(start_time);
            end_time_array.push(end_time);
            $('#START_TIME').val(start_time_array);
            $('#END_TIME').val(end_time_array);
            if (PK_APPOINTMENT_MASTER > 0) {
                let slot_btn = $(".slot_btn");
                slot_btn.each(function (index) {
                    if ($(this).data('is_disable') == 0) {
                        $(this).css('background-color', 'greenyellow');
                    }
                })
            }
            document.getElementById('slot_btn_'+id).style.setProperty('background-color', 'orange', 'important');
        }

        $(document).on('click', '#cancel_button', function () {
            window.location.href='all_schedules.php?view=list'
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
                }
            });
        }

        /*function selectThisEnrollment(param) {
            let PK_ENROLLMENT_MASTER = $(param).val();
            $.ajax({
                url: "ajax/get_services.php",
                type: "POST",
                data: {PK_ENROLLMENT_MASTER: PK_ENROLLMENT_MASTER},
                async: false,
                cache: false,
                success: function (result) {
                    $('#PK_SERVICE_MASTER').empty();
                    $('#PK_SERVICE_MASTER').append(result);
                    selectThisService($('#PK_SERVICE_MASTER'));
                }
            });
        }

        function selectThisService(param) {
            let PK_SERVICE_MASTER = $(param).val();
            $.ajax({
                url: "ajax/get_service_provider.php",
                type: "POST",
                data: {PK_SERVICE_MASTER: PK_SERVICE_MASTER},
                async: false,
                cache: false,
                success: function (result) {
                    $('#SERVICE_PROVIDER_ID').empty();
                    $('#SERVICE_PROVIDER_ID').append(result);
                    $('#SERVICE_PROVIDER_ID')[0].sumo.reload();
                }
            });

            let PK_ENROLLMENT_MASTER = $('#PK_ENROLLMENT_MASTER').val();
            $.ajax({
                url: "ajax/get_service_code_appointment.php",
                type: "POST",
                data: {PK_ENROLLMENT_MASTER: PK_ENROLLMENT_MASTER, PK_SERVICE_MASTER: PK_SERVICE_MASTER},
                async: false,
                cache: false,
                success: function (result) {
                    $('#PK_SERVICE_CODE').empty();
                    $('#PK_SERVICE_CODE').append(result);
                    $('#SERVICE_PROVIDER_ID')[0].sumo.reload();
                }
            });

            getSlots();
        }*/

        function getSlots(){
            let PK_ENROLLMENT_MASTER = $('#PK_ENROLLMENT_MASTER').val();
            /*let PK_SERVICE_MASTER = $('#PK_SERVICE_MASTER').val();
            let PK_SERVICE_CODE = $('#PK_SERVICE_CODE').val();*/
            let SERVICE_PROVIDER_ID = $('#SERVICE_PROVIDER_ID').val();
            let duration = $('#PK_ENROLLMENT_MASTER').find(':selected').data('duration');
            let selected_date  = myCalender.value.toDateString();
            let day = (selected_date.toString().split(' ')[0]).toUpperCase();
            let month = selected_date.toString().split(' ')[1];
            if(month == 'Jan')
                month = '01'
            else if(month == 'Feb')
                month = '02'
            else if(month == 'Mar')
                month = '03'
            else if(month == 'Apr')
                month = '04'
            else if(month == 'May')
                month = '05'
            else if(month == 'Jun')
                month = '06'
            else if(month == 'Jul')
                month = '07'
            else if(month == 'Aug')
                month = '08'
            else if(month == 'Sep')
                month = '09'
            else if(month == 'Oct')
                month = '10'
            else if(month == 'Nov')
                month = '11'
            else if(month == 'Dec')
                month = '12'
            let date = selected_date.toString().split(' ')[3]+'-'+month+'-'+selected_date.toString().split(' ')[2];
            let START_TIME = '<?=$START_TIME?>';
            let END_TIME = '<?=$END_TIME?>';

            console.log(SERVICE_PROVIDER_ID,duration,day);

            if (SERVICE_PROVIDER_ID > 0 && duration > 0) {
                $.ajax({
                    url: "ajax/get_slots.php",
                    type: "POST",
                    data: {
                        PK_APPOINTMENT_MASTER: PK_APPOINTMENT_MASTER,
                        PK_ENROLLMENT_MASTER: PK_ENROLLMENT_MASTER,
                        /*PK_SERVICE_MASTER: PK_SERVICE_MASTER,
                        PK_SERVICE_CODE: PK_SERVICE_CODE,*/
                        SERVICE_PROVIDER_ID: SERVICE_PROVIDER_ID,
                        duration: duration,
                        day: day,
                        date: date,
                        START_TIME: START_TIME,
                        END_TIME: END_TIME
                    },
                    async: false,
                    cache: false,
                    success: function (result) {
                        $('#slot_div').html(result);
                    }
                });
            }else {
                $('#slot_div').html('');
            }
        }

        $(document).on('submit', '#appointment_form', function (event) {
            //event.preventDefault();
            let selected_date  = myCalender.value.toDateString()
            let month = selected_date.toString().split(' ')[1];
            if(month == 'Jan')
                month = '01'
            else if(month == 'Feb')
                month = '02'
            else if(month == 'Mar')
                month = '03'
            else if(month == 'Apr')
                month = '04'
            else if(month == 'May')
                month = '05'
            else if(month == 'Jun')
                month = '06'
            else if(month == 'Jul')
                month = '07'
            else if(month == 'Aug')
                month = '08'
            else if(month == 'Sep')
                month = '09'
            else if(month == 'Oct')
                month = '10'
            else if(month == 'Nov')
                month = '11'
            else if(month == 'Dec')
                month = '12'
            let date = selected_date.toString().split(' ')[3]+'-'+month+'-'+selected_date.toString().split(' ')[2];
            $('#DATE').val(date);

            /*let form_data = $('#appointment_form').serialize();
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                data: form_data,
                async: false,
                cache: false,
                success:function (data) {
                    window.location.href='all_schedules.php';
                }
            });*/
        });

        function changeServiceProvider(){
            $('#change_service_provider').hide();
            $('#cancel_change_service_provider').show();
            $('#service_provider_select').slideDown();
            $('#service_provider_name').slideUp();
            $('#date_time_div').slideUp();
            $('#schedule_div').slideDown();
        }

        function cancelChangeServiceProvider() {
            $('#change_service_provider').show();
            $('#cancel_change_service_provider').hide();
            $('#service_provider_select').slideUp();
            $('#service_provider_name').slideDown();
            $('#date_time_div').slideDown();
            $('#schedule_div').slideUp();
        }

        function reschedule() {
            $('#cancel_reschedule').show();
            $('#date_time_div').slideUp();
            $('#schedule_div').slideDown();
        }

        function cancelReschedule() {
            $('#cancel_reschedule').hide();
            $('#date_time_div').slideDown();
            $('#schedule_div').slideUp();
        }

        function changeStatus(){
            $('#cancel_change_status').show();
            $('#change_status').hide();
            $('#PK_APPOINTMENT_STATUS').slideDown();
            $('#appointment_status').slideUp();
        }

        function cancelChangeStatus() {
            $('#cancel_change_status').hide();
            $('#change_status').show();
            $('#PK_APPOINTMENT_STATUS').slideUp();
            $('#appointment_status').slideDown();
        }

        function changeAppointmentStatus(param) {
            if ($(param).val() == 4){
                $('#no_show_div').slideDown();
                $('#NO_SHOW').attr('required', true);
            }else {
                $('#no_show_div').slideUp();
                $('#NO_SHOW').attr('required', false);
            }
        }

        function cancelAppointment() {
            let text = "Did you really want to confirm Appointment?";
            if (confirm(text) == true) {
                let PK_APPOINTMENT_MASTER = $('.PK_APPOINTMENT_MASTER').val();
                $.ajax({
                    url: "ajax/AjaxFunctions.php",
                    type: "POST",
                    data: {FUNCTION_NAME: 'cancelAppointment', PK_APPOINTMENT_MASTER: PK_APPOINTMENT_MASTER},
                    async: false,
                    cache: false,
                    success: function (result) {
                        window.location.href='all_schedules.php?view=list';
                    }
                });
            } 
        }
    </script>


</body>
</html>