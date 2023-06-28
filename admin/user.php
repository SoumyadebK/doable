<?php
require_once('../global/config.php');
$userType = "Users";
$user_role_condition = " AND PK_ROLES IN(2,3,5,6,7,8)";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 2 ){
    header("location:../login.php");
    exit;
}

$CREATE_LOGIN = 0;

if (empty($_GET['id']))
    $title = "Add ".$userType;
else
    $title = "Edit ".$userType;

$PK_ACCOUNT_MASTER = $_SESSION['PK_ACCOUNT_MASTER'];

/*if(!empty($_POST)){
    $USER_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
    $USER_DATA['PK_ROLES'] = $_POST['PK_ROLES'];
    $USER_DATA['FIRST_NAME'] = $_POST['FIRST_NAME'];
    $USER_DATA['LAST_NAME'] = $_POST['LAST_NAME'];
    $USER_DATA['EMAIL_ID'] = $_POST['EMAIL_ID'];
    $USER_DATA['PK_LOCATION'] = $_POST['PK_LOCATION'];
    $USER_DATA['USER_TITLE'] = $_POST['USER_TITLE'];
    $USER_DATA['CREATE_LOGIN'] = isset($_POST['CREATE_LOGIN'])?1:0;

    if ($USER_DATA['CREATE_LOGIN'] == 1) {
        if (!empty($_POST['PASSWORD'])) {
            $USER_DATA['USER_ID'] = $_POST['USER_ID'];
            $USER_DATA['PASSWORD'] = password_hash($_POST['PASSWORD'], PASSWORD_DEFAULT);
        }
    }

    if($_FILES['USER_IMAGE']['name'] != ''){
        $extn 			= explode(".",$_FILES['USER_IMAGE']['name']);
        $iindex			= count($extn) - 1;
        $rand_string 	= time()."-".rand(100000,999999);
        $file11			= 'user_image_'.$_SESSION['PK_USER'].$rand_string.".".$extn[$iindex];
        $extension   	= strtolower($extn[$iindex]);

        if($extension == "gif" || $extension == "jpeg" || $extension == "pjpeg" || $extension == "png" || $extension == "jpg"){
            $image_path    = '../uploads/user_image/'.$file11;
            move_uploaded_file($_FILES['USER_IMAGE']['tmp_name'], $image_path);
            $USER_DATA['USER_IMAGE'] = $image_path;
        }
    }

    $USER_PROFILE_DATA['GENDER'] = $_POST['GENDER'];
    $USER_PROFILE_DATA['DOB'] = $_POST['DOB'];
    $USER_PROFILE_DATA['ADDRESS'] = $_POST['ADDRESS'];
    $USER_PROFILE_DATA['ADDRESS_1'] = $_POST['ADDRESS_1'];
    $USER_PROFILE_DATA['PK_COUNTRY'] = $_POST['PK_COUNTRY'];
    $USER_PROFILE_DATA['PK_STATES'] = $_POST['PK_STATES'];
    $USER_PROFILE_DATA['CITY'] = $_POST['CITY'];
    $USER_PROFILE_DATA['ZIP'] = $_POST['ZIP'];
    $USER_PROFILE_DATA['PHONE'] = $_POST['PHONE'];
    $USER_PROFILE_DATA['NOTES'] = $_POST['NOTES'];

    if(empty($_GET['id'])){
        $USER_DATA['ACTIVE'] = 1;
        $USER_DATA['CREATED_BY']  = $_SESSION['PK_USER'];
        $USER_DATA['CREATED_ON']  = date("Y-m-d H:i");
        db_perform('DOA_USERS', $USER_DATA, 'insert');
        $PK_USER = $db->insert_ID();
        $USER_PROFILE_DATA['PK_USER'] = $PK_USER;
        $USER_PROFILE_DATA['ACTIVE'] = 1;
        $USER_PROFILE_DATA['CREATED_BY']  = $_SESSION['PK_USER'];
        $USER_PROFILE_DATA['CREATED_ON']  = date("Y-m-d H:i");
        db_perform('DOA_USER_PROFILE', $USER_PROFILE_DATA, 'insert');
    }else{
        $USER_DATA['ACTIVE'] = $_POST['ACTIVE'];
        $USER_DATA['EDITED_BY']	= $_SESSION['PK_USER'];
        $USER_DATA['EDITED_ON'] = date("Y-m-d H:i");
        db_perform('DOA_USERS', $USER_DATA, 'update'," PK_USER =  '$_GET[id]'");
        $USER_PROFILE_DATA['ACTIVE'] = $_POST['ACTIVE'];
        $USER_PROFILE_DATA['EDITED_BY']	= $_SESSION['PK_USER'];
        $USER_PROFILE_DATA['EDITED_ON'] = date("Y-m-d H:i");
        db_perform('DOA_USER_PROFILE', $USER_PROFILE_DATA, 'update'," PK_USER =  '$_GET[id]'");
        $PK_USER = $_GET['id'];
    }

    if($type == 2){
        $CUSTOMER_USER_DATA['PK_USER'] = $PK_USER;
        $CUSTOMER_USER_DATA['CALL_PREFERENCE'] = $_POST['CALL_PREFERENCE'];
        $CUSTOMER_USER_DATA['REMINDER_OPTION'] = implode(',', $_POST['REMINDER_OPTION']);
        $CUSTOMER_USER_DATA['SPECIAL_DATE_1'] = $_POST['SPECIAL_DATE_1'];
        $CUSTOMER_USER_DATA['DATE_NAME_1'] = $_POST['DATE_NAME_1'];
        $CUSTOMER_USER_DATA['SPECIAL_DATE_2'] = $_POST['SPECIAL_DATE_2'];
        $CUSTOMER_USER_DATA['DATE_NAME_2'] = $_POST['DATE_NAME_2'];
        $CUSTOMER_USER_DATA['ATTENDING_WITH'] = $_POST['ATTENDING_WITH'];
        $CUSTOMER_USER_DATA['PARTNER_FIRST_NAME'] = $_POST['PARTNER_FIRST_NAME'];
        $CUSTOMER_USER_DATA['PARTNER_LAST_NAME'] = $_POST['PARTNER_LAST_NAME'];
        $CUSTOMER_USER_DATA['PARTNER_GENDER'] = $_POST['PARTNER_GENDER'];
        $CUSTOMER_USER_DATA['PARTNER_DOB'] = $_POST['PARTNER_DOB'];

        $check_customer_data = '';
        if (!empty($_GET['id'])){
            $check_customer_data = $db->Execute("SELECT * FROM `DOA_CUSTOMER_DETAILS` WHERE `PK_USER` = '$_GET[id]'");
        }
        if ($check_customer_data == ''){
            db_perform('DOA_CUSTOMER_DETAILS', $CUSTOMER_USER_DATA, 'insert');
            $PK_CUSTOMER_DETAILS = $db->insert_ID();
        }else{
            db_perform('DOA_CUSTOMER_DETAILS', $CUSTOMER_USER_DATA, 'update'," PK_USER =  '$_GET[id]'");
            $PK_CUSTOMER_DETAILS = $_POST['PK_CUSTOMER_DETAILS'];
        }

        if (isset($_POST['CUSTOMER_PHONE'])){
            $res = $db->Execute("DELETE FROM `DOA_CUSTOMER_PHONE_EMAIL` WHERE `PK_CUSTOMER_DETAILS` = '$PK_CUSTOMER_DETAILS'");
            for($i = 0; $i < count($_POST['CUSTOMER_PHONE']); $i++){
                $CUSTOMER_EMAIL_PHONE['PK_CUSTOMER_DETAILS'] = $PK_CUSTOMER_DETAILS;
                $CUSTOMER_EMAIL_PHONE['PHONE'] = $_POST['CUSTOMER_PHONE'][$i];
                $CUSTOMER_EMAIL_PHONE['EMAIL'] = $_POST['CUSTOMER_EMAIL'][$i];
                db_perform('DOA_CUSTOMER_PHONE_EMAIL', $CUSTOMER_EMAIL_PHONE, 'insert');
            }
        }

        if (isset($_POST['CUSTOMER_SPECIAL_DATE'])){
            $res = $db->Execute("DELETE FROM `DOA_SPECIAL_DATE` WHERE `PK_CUSTOMER_DETAILS` = '$PK_CUSTOMER_DETAILS'");
            for($i = 0; $i < count($_POST['CUSTOMER_SPECIAL_DATE']); $i++){
                $CUSTOMER_SPECIAL_DATE['PK_CUSTOMER_DETAILS'] = $PK_CUSTOMER_DETAILS;
                $CUSTOMER_SPECIAL_DATE['SPECIAL_DATE'] = $_POST['CUSTOMER_SPECIAL_DATE'][$i];
                $CUSTOMER_SPECIAL_DATE['DATE_NAME'] = $_POST['CUSTOMER_SPECIAL_DATE_NAME'][$i];
                db_perform('DOA_SPECIAL_DATE', $CUSTOMER_SPECIAL_DATE, 'insert');
            }
        }

        if (isset($_POST['DOCUMENT_NAME'])){
            $res = $db->Execute("DELETE FROM `DOA_CUSTOMER_DOCUMENT` WHERE `PK_USER` = '$PK_USER'");
            for($i = 0; $i < count($_POST['DOCUMENT_NAME']); $i++){
                $USER_DOCUMENT_DATA['PK_USER'] = $PK_USER;
                $USER_DOCUMENT_DATA['DOCUMENT_NAME'] = $_POST['DOCUMENT_NAME'][$i];
                if(!empty($_FILES['FILE_PATH']['name'][$i])){
                    $extn 			= explode(".",$_FILES['FILE_PATH']['name'][$i]);
                    $iindex			= count($extn) - 1;
                    $rand_string 	= time()."-".rand(100000,999999);
                    $file11			= 'user_image_'.$_SESSION['PK_USER'].$rand_string.".".$extn[$iindex];
                    $extension   	= strtolower($extn[$iindex]);

                    $image_path    = '../uploads/user_doc/'.$file11;
                    move_uploaded_file($_FILES['FILE_PATH']['tmp_name'][$i], $image_path);
                    $USER_DOCUMENT_DATA['FILE_PATH'] = $image_path;
                } else {
                    $USER_DOCUMENT_DATA['FILE_PATH'] = $_POST['FILE_PATH_URL'][$i];
                }
                db_perform('DOA_CUSTOMER_DOCUMENT', $USER_DOCUMENT_DATA, 'insert');
            }
        }
        if (isset($_POST['PK_INTERESTS'])){
            $res = $db->Execute("DELETE FROM `DOA_USER_INTEREST` WHERE `PK_USER` = '$PK_USER'");
            for($i = 0; $i < count($_POST['PK_INTERESTS']); $i++){
                $USER_INTEREST_DATA['PK_USER'] = $PK_USER;
                $USER_INTEREST_DATA['PK_INTERESTS'] = $_POST['PK_INTERESTS'][$i];
                db_perform('DOA_USER_INTEREST', $USER_INTEREST_DATA, 'insert');
            }
        }
        if (isset($_POST['WHAT_PROMPTED_YOU_TO_INQUIRE']) || isset($_POST['PK_INQUIRY_METHOD']) || isset($_POST['INQUIRY_TAKER_ID'])){
            $USER_INTEREST_OTHER_DATA['PK_USER'] = $PK_USER;
            $USER_INTEREST_OTHER_DATA['WHAT_PROMPTED_YOU_TO_INQUIRE'
             ] = $_POST['WHAT_PROMPTED_YOU_TO_INQUIRE'];
            $USER_INTEREST_OTHER_DATA['PK_SKILL_LEVEL'] = $_POST['PK_SKILL_LEVEL'];
            $USER_INTEREST_OTHER_DATA['PK_INQUIRY_METHOD'] = $_POST['PK_INQUIRY_METHOD'];
            $USER_INTEREST_OTHER_DATA['INQUIRY_TAKER_ID'] = $_POST['INQUIRY_TAKER_ID'];

            $check_interest_other_data = '';
            if ($_GET['id']){
                $check_interest_other_data = $db->Execute("SELECT * FROM `DOA_USER_INTEREST_OTHER_DATA` WHERE `PK_USER` = '$_GET[id]'");
            }
            if ($check_interest_other_data != '' && $check_interest_other_data->RecordCount() > 0){
                db_perform('DOA_USER_INTEREST_OTHER_DATA', $USER_INTEREST_OTHER_DATA, 'update'," PK_USER =  '$_GET[id]'");
            }else{
                db_perform('DOA_USER_INTEREST_OTHER_DATA', $USER_INTEREST_OTHER_DATA, 'insert');
            }
        }
    }

    if($type == 3 && isset($_POST['PK_USER_LOCATION'])){
        $PK_USER_LOCATION = $_POST['PK_USER_LOCATION'];
        $res = $db->Execute("DELETE FROM `DOA_USER_LOCATION` WHERE `PK_USER` = '$PK_USER'");
        for($i = 0; $i < count($PK_USER_LOCATION); $i++){
            $SERVICE_PROVIDER_LOCATION_DATA['PK_USER'] = $PK_USER;
            $SERVICE_PROVIDER_LOCATION_DATA['PK_LOCATION'] = $PK_USER_LOCATION[$i];
            db_perform('DOA_USER_LOCATION', $SERVICE_PROVIDER_LOCATION_DATA, 'insert');
        }
    }

    if (isset($_POST['PK_RATE_TYPE'])) {
        $USER_RATE_ACTIVE['ACTIVE'] = 0;
        db_perform('DOA_USER_RATE', $USER_RATE_ACTIVE, 'update', " PK_USER = '$PK_USER'");
        $PK_RATE_TYPE = $_POST['PK_RATE_TYPE'];
        $RATE = $_POST['RATE'];
        for ($i = 0; $i < count($RATE); $i++) {
            if (isset($PK_RATE_TYPE[$i])) {
                $USER_RATE_DATA = [];
                $res = $db->Execute("SELECT * FROM `DOA_USER_RATE` WHERE PK_RATE_TYPE = '$PK_RATE_TYPE[$i]' AND PK_USER = '$PK_USER'");
                if ($res->RecordCount() == 0) {
                    $USER_RATE_DATA['PK_USER'] = $PK_USER;
                    $USER_RATE_DATA['PK_RATE_TYPE'] = $PK_RATE_TYPE[$i];
                    $USER_RATE_DATA['RATE'] = $RATE[$i];
                    $USER_RATE_DATA['ACTIVE'] = 1;
                    $USER_RATE_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
                    $USER_RATE_DATA['CREATED_ON'] = date("Y-m-d H:i");
                    db_perform('DOA_USER_RATE', $USER_RATE_DATA, 'insert');
                } else {
                    $USER_RATE_DATA['RATE'] = $RATE[$i];
                    $USER_RATE_DATA['ACTIVE'] = 1;
                    $USER_RATE_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
                    $USER_RATE_DATA['EDITED_ON'] = date("Y-m-d H:i");
                    db_perform('DOA_USER_RATE', $USER_RATE_DATA, 'update', " PK_RATE_TYPE = '$PK_RATE_TYPE[$i]' AND PK_USER = '$PK_USER'");
                }
            }
        }
    }
    header("location:all_users.php?type=".$type);
}*/

$PK_USER = '';
$PK_CUSTOMER_DETAILS = '';
$USER_ID = '';
$FIRST_NAME = '';
$LAST_NAME = '';
$EMAIL_ID = '';
$USER_IMAGE = '';
$GENDER = '';
$DOB = '';
$ADDRESS = '';
$ADDRESS_1 = '';
$PK_COUNTRY = '';
$PK_STATES = '';
$CITY = '';
$ZIP = '';
$PHONE = '';
$PK_LOCATION = '';
$USER_TITLE = '';
$NOTES = '';
$PASSWORD = '';
$ACTIVE = '';
$INACTIVE_BY_ADMIN = '';
$CAN_EDIT_ENROLLMENT = '';
$TICKET_SYSTEM_ACCESS = '';
if(!empty($_GET['id'])) {
    $res = $db->Execute("SELECT DOA_USERS.PK_USER, DOA_USERS.FIRST_NAME, DOA_USERS.LAST_NAME, DOA_USERS.USER_ID, DOA_USERS.EMAIL_ID, DOA_USERS.USER_IMAGE, DOA_USERS.ACTIVE, DOA_USERS.INACTIVE_BY_ADMIN, DOA_USERS.CAN_EDIT_ENROLLMENT, DOA_USERS.PK_LOCATION, DOA_USERS.USER_TITLE, DOA_USERS.CREATE_LOGIN, DOA_USERS.PASSWORD, DOA_USERS.TICKET_SYSTEM_ACCESS, DOA_USER_PROFILE.GENDER, DOA_USER_PROFILE.DOB, DOA_USER_PROFILE.ADDRESS, DOA_USER_PROFILE.ADDRESS_1, DOA_USER_PROFILE.CITY, DOA_USER_PROFILE.PK_STATES, DOA_USER_PROFILE.ZIP, DOA_USER_PROFILE.PK_COUNTRY, DOA_USERS.PHONE, DOA_USER_PROFILE.FAX, DOA_USER_PROFILE.WEBSITE, DOA_USER_PROFILE.NOTES FROM DOA_USERS LEFT JOIN DOA_USER_PROFILE ON DOA_USERS.PK_USER = DOA_USER_PROFILE.PK_USER WHERE DOA_USERS.PK_USER = '$_GET[id]'");
    if($res->RecordCount() == 0){
        header("location:all_users.php");
        exit;
    }
    $PK_USER = $res->fields['PK_USER'];
    $USER_ID = $res->fields['USER_ID'];
    $FIRST_NAME = $res->fields['FIRST_NAME'];
    $LAST_NAME = $res->fields['LAST_NAME'];
    $EMAIL_ID = $res->fields['EMAIL_ID'];
    $USER_IMAGE = $res->fields['USER_IMAGE'];
    $GENDER = $res->fields['GENDER'];
    $DOB = $res->fields['DOB'];
    $ADDRESS = $res->fields['ADDRESS'];
    $ADDRESS_1 = $res->fields['ADDRESS_1'];
    $PK_COUNTRY = $res->fields['PK_COUNTRY'];
    $PK_STATES = $res->fields['PK_STATES'];
    $CITY = $res->fields['CITY'];
    $ZIP = $res->fields['ZIP'];
    $PHONE = $res->fields['PHONE'];
    $PK_LOCATION = $res->fields['PK_LOCATION'];
    $USER_TITLE = $res->fields['USER_TITLE'];
    $NOTES = $res->fields['NOTES'];
    $NOTES = $res->fields['NOTES'];
    $ACTIVE = $res->fields['ACTIVE'];
    $PASSWORD = $res->fields['PASSWORD'];
    $INACTIVE_BY_ADMIN = $res->fields['INACTIVE_BY_ADMIN'];
    $CAN_EDIT_ENROLLMENT = $res->fields['CAN_EDIT_ENROLLMENT'];
    $CREATE_LOGIN = $res->fields['CREATE_LOGIN'];
    $TICKET_SYSTEM_ACCESS = $res->fields['TICKET_SYSTEM_ACCESS'];
}

?>
<!DOCTYPE html>
<html lang="en">
<?php require_once('../includes/header.php');?>
<style>
    .progress-bar {
        border-radius: 5px;
        height:18px !important;
    }
</style>
<link href="../assets/sumoselect/sumoselect.min.css" rel="stylesheet"/>
<body class="skin-default-dark fixed-layout">
<?php require_once('../includes/loader.php');?>
<div id="main-wrapper">
    <?php require_once('../includes/top_menu.php');?>
    <div class="page-wrapper">
        <?php require_once('../includes/top_menu_bar.php') ?>
        <div class="container-fluid">
            <div class="row page-titles">
                <div class="col-md-5 align-self-center">
                    <h4 class="text-themecolor"><div class="card-title">
                            <?php
                            if(!empty($_GET['id'])) {
                                echo "Edit ".$FIRST_NAME." ".$LAST_NAME;
                            }
                            ?>
                        </div></h4>
                </div>
                <div class="col-md-7 align-self-center text-end">
                    <div class="d-flex justify-content-end align-items-center">
                        <ol class="breadcrumb justify-content-end">
                            <li class="breadcrumb-item"><a href="setup.php">Setup</a></li>
                            <li class="breadcrumb-item"><a href="all_users.php">All Users</a></li>
                            <li class="breadcrumb-item active"><?=$title?></li>
                        </ol>

                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="card">
                                        <div class="card-body">
                                            <!-- Nav tabs -->
                                            <ul class="nav nav-tabs" role="tablist">
                                                <li> <a class="nav-link active" data-bs-toggle="tab" href="#profile" role="tab" ><span class="hidden-sm-up"><i class="ti-id-badge"></i></span> <span class="hidden-xs-down">Profile</span></a> </li>
                                                <li id="login_info_tab" style="display: <?=($CREATE_LOGIN == 1)?'':'none'?>"> <a class="nav-link" id="login_info_tab_link" data-bs-toggle="tab" href="#login" role="tab"><span class="hidden-sm-up"><i class="ti-lock"></i></span> <span class="hidden-xs-down">Login Info</span></a> </li>
                                                <li> <a class="nav-link" id="rates_tab_link" data-bs-toggle="tab" href="#rates" role="tab" ><span class="hidden-sm-up"><i class="ti-money"></i></span> <span class="hidden-xs-down">Rates</span></a> </li>
                                                <li> <a class="nav-link" data-bs-toggle="tab" href="#documents" id="document_tab_link" role="tab" ><span class="hidden-sm-up"><i class="ti-files"></i></span> <span class="hidden-xs-down">Documents</span></a> </li>
                                            </ul>
                                            <!-- Tab panes -->
                                            <div class="tab-content tabcontent-border">
                                                <div class="tab-pane active" id="profile" role="tabpanel">
                                                    <form class="form-material form-horizontal" id="profile_form">
                                                        <input type="hidden" name="FUNCTION_NAME" value="saveProfileData">
                                                        <input type="hidden" class="PK_USER" name="PK_USER" value="<?=$PK_USER?>">
                                                        <input type="hidden" class="TYPE" name="TYPE" value="1">
                                                        <div class="p-20">
                                                            <div class="row">
                                                                <div class="col-5">
                                                                    <div class="form-group">
                                                                        <label class="form-label">First Name<span class="text-danger">*</span></label>
                                                                        <div class="col-md-12">
                                                                            <input type="text" id="FIRST_NAME" name="FIRST_NAME" class="form-control" placeholder="Enter First Name" required value="<?=$FIRST_NAME?>">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-5">
                                                                    <div class="form-group">
                                                                        <label class="form-label">Last Name</label>
                                                                        <div class="col-md-12">
                                                                            <input type="text" id="LAST_NAME" name="LAST_NAME" class="form-control" placeholder="Enter Last Name" value="<?=$LAST_NAME?>">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-2">
                                                                    <label class="form-label">Roles<span class="text-danger">*</span></label>
                                                                    <div class="col-md-12 multiselect-box">
                                                                        <select class="multi_sumo_select" name="PK_ROLES[]" id="PK_ROLES" required multiple>
                                                                            <?php
                                                                            $row = $db->Execute("SELECT PK_ROLES, ROLES FROM DOA_ROLES WHERE ACTIVE='1' ".$user_role_condition." ORDER BY PK_ROLES");
                                                                            $selected_roles = [];
                                                                            if(!empty($_GET['id'])) {
                                                                                $PK_USER = $_GET['id'];
                                                                                $selected_roles_row = $db->Execute("SELECT PK_ROLES FROM `DOA_USER_ROLES` WHERE `PK_USER` = '$PK_USER'");
                                                                                while (!$selected_roles_row->EOF) {
                                                                                    $selected_roles[] = $selected_roles_row->fields['PK_ROLES'];
                                                                                    $selected_roles_row->MoveNext();
                                                                                }
                                                                            }
                                                                            while (!$row->EOF) { ?>
                                                                                <option value="<?php echo $row->fields['PK_ROLES'];?>" <?=in_array($row->fields['PK_ROLES'], $selected_roles)?"selected":""?>><?=$row->fields['ROLES']?></option>
                                                                            <?php $row->MoveNext(); } ?>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="row">
                                                                <div class="col-3">
                                                                    <div class="form-group">
                                                                        <label class="form-label">Phone<span class="text-danger" id="phone_label"><?=($CREATE_LOGIN == 1)?'*':''?></span></label>
                                                                        <div class="col-md-12">
                                                                            <input type="text" id="PHONE" name="PHONE" class="form-control" placeholder="Enter Phone Number" value="<?php echo $PHONE?>" <?=($CREATE_LOGIN == 1)?'required':''?>>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-3">
                                                                    <div class="form-group">
                                                                        <label class="form-label">Email<span class="text-danger" id="email_label"><?=($CREATE_LOGIN == 1)?'*':''?></span></label>
                                                                        <div class="col-md-12">
                                                                            <input type="email" id="EMAIL_ID" name="EMAIL_ID" class="form-control" placeholder="Enter Email Address" value="<?=$EMAIL_ID?>" <?=($CREATE_LOGIN == 1)?'required':''?>>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-2">
                                                                    <label class="col-md-12"><input type="checkbox" id="CREATE_LOGIN" name="CREATE_LOGIN" class="form-check-inline" <?=($CREATE_LOGIN == 1)?'checked':''?> style="margin-top: 30px;" onchange="createLogin(this);"> Create Login</label>
                                                                </div>
                                                            </div>

                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <div class="form-group">
                                                                        <label class="form-label">Gender</label>
                                                                        <select class="form-control" id="GENDER" name="GENDER">
                                                                            <option>Select Gender</option>
                                                                            <option value="Male" <?php if($GENDER == "Male") echo 'selected = "selected"';?>>Male</option>
                                                                            <option value="Female" <?php if($GENDER == "Female") echo 'selected = "selected"';?>>Female</option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <div class="form-group">
                                                                        <label class="form-label">Date of Birth</label>
                                                                        <input type="text" class="form-control datepicker-past"  id="DOB" name="DOB" value="<?=($DOB)?date('m/d/Y', strtotime($DOB)):''?>">
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="row">
                                                                <div class="col-6">
                                                                    <div class="form-group">
                                                                        <label class="col-md-12">Address</label>
                                                                        <div class="col-md-12">
                                                                            <input type="text" id="ADDRESS" name="ADDRESS" class="form-control" placeholder="Enter Address" value="<?php echo $ADDRESS?>">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-6">
                                                                    <div class="form-group">
                                                                        <label class="col-md-12">Apt/Ste</label>
                                                                        <div class="col-md-12">
                                                                            <input type="text" id="ADDRESS_1" name="ADDRESS_1" class="form-control" placeholder="Enter Address" value="<?php echo $ADDRESS_1?>">

                                                                        </div>
                                                                    </div>

                                                                </div>
                                                            </div>

                                                            <div class="row">
                                                                <div class="col-6">
                                                                    <div class="form-group">
                                                                        <label class="col-md-12">Country</label>
                                                                        <div class="col-md-12">
                                                                            <div class="col-sm-12">
                                                                                <select class="form-control" name="PK_COUNTRY" id="PK_COUNTRY" onChange="fetch_state(this.value)">
                                                                                    <option>Select Country</option>
                                                                                    <?php
                                                                                    $row = $db->Execute("SELECT PK_COUNTRY,COUNTRY_NAME FROM DOA_COUNTRY WHERE ACTIVE = 1 ORDER BY PK_COUNTRY");
                                                                                    while (!$row->EOF) { ?>
                                                                                        <option value="<?php echo $row->fields['PK_COUNTRY'];?>" <?=($row->fields['PK_COUNTRY'] == $PK_COUNTRY)?"selected":""?>><?=$row->fields['COUNTRY_NAME']?></option>
                                                                                    <?php $row->MoveNext(); } ?>
                                                                                </select>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="col-6">
                                                                    <div class="form-group">
                                                                        <label class="col-md-12">State</label>
                                                                        <div class="col-md-12">
                                                                            <div class="col-sm-12">
                                                                                <div id="State_div"></div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="row">
                                                                <div class="col-6">
                                                                    <div class="form-group">
                                                                        <label class="col-md-12">City</label>
                                                                        <div class="col-md-12">
                                                                            <input type="text" id="CITY" name="CITY" class="form-control" placeholder="Enter your city" value="<?php echo $CITY?>">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-6">
                                                                    <div class="form-group">
                                                                        <label class="col-md-12">Zip Code</label>
                                                                        <div class="col-md-12">
                                                                            <input type="text" id="ZIP" name="ZIP" class="form-control" placeholder="Enter Zip Code" value="<?php echo $ZIP?>">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="row">
                                                                <div class="col-6">
                                                                    <label class="form-label">Location</label>
                                                                    <div class="col-md-12 multiselect-box">
                                                                        <select class="multi_sumo_select" name="PK_USER_LOCATION[]" id="PK_LOCATION_MULTIPLE" multiple>
                                                                            <?php
                                                                            $selected_location = [];
                                                                            if(!empty($_GET['id'])) {
                                                                                $selected_location_row = $db->Execute("SELECT `PK_LOCATION` FROM `DOA_USER_LOCATION` WHERE `PK_USER` = '$_GET[id]'");
                                                                                while (!$selected_location_row->EOF) {
                                                                                    $selected_location[] = $selected_location_row->fields['PK_LOCATION'];
                                                                                    $selected_location_row->MoveNext();
                                                                                }
                                                                            }
                                                                            $row = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME FROM DOA_LOCATION WHERE ACTIVE = 1 AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
                                                                            while (!$row->EOF) { ?>
                                                                                <option value="<?php echo $row->fields['PK_LOCATION'];?>" <?=in_array($row->fields['PK_LOCATION'], $selected_location)?"selected":""?>><?=$row->fields['LOCATION_NAME']?></option>
                                                                            <?php $row->MoveNext(); } ?>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="row">
                                                                <div class="col-12">
                                                                    <div class="form-group">
                                                                        <label class="col-md-12">Remarks</label>
                                                                        <div class="col-md-12">
                                                                            <textarea class="form-control" rows="3" id="NOTES" name="NOTES"><?php echo $NOTES?></textarea>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="row">
                                                                <div class="col-6">
                                                                    <div class="form-group">
                                                                        <label class="col-md-12">Image Upload</label>
                                                                        <div class="col-md-12">
                                                                            <input type="file" name="USER_IMAGE" id="USER_IMAGE" class="form-control">
                                                                        </div>
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <?php if($USER_IMAGE!=''){?><div style="width: 120px;height: 120px;margin-top: 25px;"><a class="fancybox" href="<?php echo $USER_IMAGE;?>" data-fancybox-group="gallery"><img src = "<?php echo $USER_IMAGE;?>" style="width:120px; height:120px" /></a></div><?php } ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <?php if(!empty($_GET['id'])) { ?>
                                                                <div class="row <?=($INACTIVE_BY_ADMIN == 1)?'div_inactive':''?>" style="margin-bottom: 15px; margin-top: 15px;">
                                                                    <div class="col-md-1">
                                                                        <label class="form-label">Active : </label>
                                                                    </div>
                                                                    <div class="col-md-4">
                                                                        <label><input type="radio" name="ACTIVE" id="ACTIVE" value="1" <? if($ACTIVE == 1) echo 'checked="checked"'; ?> />&nbsp;Yes</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                                        <label><input type="radio" name="ACTIVE" id="ACTIVE" value="0" <? if($ACTIVE == 0) echo 'checked="checked"'; ?> />&nbsp;No</label>
                                                                    </div>
                                                                </div>
                                                            <? } ?>
                                                        </div>
                                                        <div class="form-group">
                                                            <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white"><?=empty($_GET['id'])?'Continue':'Save'?></button>
                                                            <button type="button" id="cancel_button" class="btn btn-inverse waves-effect waves-light">Cancel</button>
                                                        </div>
                                                    </form>
                                                </div>

                                                <div class="tab-pane" id="login" role="tabpanel">
                                                    <form class="form-material form-horizontal" id="login_form">
                                                        <input type="hidden" name="FUNCTION_NAME" value="saveLoginData">
                                                        <input type="hidden" class="PK_USER" name="PK_USER" value="<?=$PK_USER?>">
                                                        <input type="hidden" class="TYPE" name="TYPE" value="1">
                                                        <div class="p-20">
                                                            <div class="row">
                                                                <div class="col-6">
                                                                    <div class="form-group">
                                                                        <label class="col-md-12">User Name</label>
                                                                        <div class="col-md-12">
                                                                            <input type="text" id="USER_ID" name="USER_ID" class="form-control" placeholder="Enter User Name" onkeyup="ValidateUsername()" value="<?=$USER_ID?>">
                                                                        </div>
                                                                    </div>
                                                                    <span id="lblError" style="color: red"></span>
                                                                </div>
                                                            </div>

                                                            <div class="row">
                                                                <div class="col-md-2">
                                                                    <label class="form-label">Can Edit Enrollment : </label>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <label><input type="radio" name="CAN_EDIT_ENROLLMENT" id="CAN_EDIT_ENROLLMENT" value="1" <? if($CAN_EDIT_ENROLLMENT == 1) echo 'checked="checked"'; ?> />&nbsp;Yes</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                                    <label><input type="radio" name="CAN_EDIT_ENROLLMENT" id="CAN_EDIT_ENROLLMENT" value="0" <? if($CAN_EDIT_ENROLLMENT == 0) echo 'checked="checked"'; ?> />&nbsp;No</label>
                                                                </div>
                                                            </div>

                                                            <div class="row" style="margin-top: 10px;">
                                                                <div class="col-md-3">
                                                                    <div class="col-md-12 form-group m-b-40 custom-control custom-checkbox form-group">
                                                                        <input type="checkbox" class="custom-control-input" id="TICKET_SYSTEM_ACCESS" name="TICKET_SYSTEM_ACCESS" value="1" <? if($TICKET_SYSTEM_ACCESS == 1) echo "checked"; ?> >
                                                                        <label class="custom-control-label" for="TICKET_SYSTEM_ACCESS">Can Create Support Tickets</label>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <?php if(empty($_GET['id']) || $PASSWORD == '') { ?>
                                                                <div class="row">
                                                                    <div class="col-6">
                                                                        <div class="form-group">
                                                                            <label class="col-md-12">Password</label>
                                                                            <div class="col-md-12">
                                                                                <input type="password" class="form-control" placeholder="Password" aria-label="Password" aria-describedby="basic-addon3" name="PASSWORD" id="PASSWORD" onkeyup="isGood(this.value)">
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-6">
                                                                        <div class="form-group">
                                                                            <label class="col-md-12">Confirm Password</label>
                                                                            <div class="col-md-12">
                                                                                <input type="password" class="form-control" placeholder="Confirm Password" aria-label="Password" aria-describedby="basic-addon3" name="CONFIRM_PASSWORD" id="CONFIRM_PASSWORD" onkeyup="isGood(this.value)">
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <b id="password_error" style="color: red;"></b>
                                                                <div class="row">
                                                                    <div class="col-12">
                                                                        <span style="color: orange;">Note  : Password Must contain at least one number and one uppercase and lowercase letter, and at least 8 or more characters</span>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-2">
                                                                        Password Strength:
                                                                    </div>
                                                                    <div class="col-3">
                                                                        <small id="password-text"></small>
                                                                    </div>
                                                                </div>
                                                            <?php } else { ?>
                                                                <div class="row">
                                                                    <div class="row" id="change_password_div" style="padding: 20px 20px 0px 20px; display: none;">
                                                                        <div class="col-3">
                                                                            <div class="form-group">
                                                                                <label class="form-label">Old Password</label>
                                                                                <input type="hidden" name="SAVED_OLD_PASSWORD" id="SAVED_OLD_PASSWORD" value="<?=$PASSWORD?>">
                                                                                <input type="password" name="OLD_PASSWORD" id="OLD_PASSWORD" class="form-control">
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-3">
                                                                            <div class="form-group">
                                                                                <label class="form-label">New Password</label>
                                                                                <input type="password" name="PASSWORD" class="form-control" id="PASSWORD">
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-3">
                                                                            <div class="form-group">
                                                                                <label class="form-label">Confirm New Password</label>
                                                                                <input type="password" name="CONFIRM_PASSWORD" class="form-control" id="CONFIRM_PASSWORD">
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <b id="password_error" style="color: red;"></b>
                                                                </div>
                                                            <?php } ?>
                                                        </div>
                                                        <div class="form-group">
                                                            <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white"><?=empty($_GET['id'])?'Continue':'Save'?></button>
                                                            <a class="btn btn-info waves-effect waves-light m-r-10 text-white" onclick="$('#change_password_div').slideToggle();">Change Password</a>
                                                            <button type="button" id="cancel_button" class="btn btn-inverse waves-effect waves-light">Cancel</button>
                                                        </div>
                                                    </form>
                                                </div>

                                                <div class="tab-pane card-body" id="rates" role="tabpanel">
                                                    <h4 class="card-title">Engagement Terms</h4>
                                                    <form id="engagement_form">
                                                        <input type="hidden" name="FUNCTION_NAME" value="saveEngagementData">
                                                        <input type="hidden" class="PK_USER" name="PK_USER" value="<?=$PK_USER?>">
                                                        <input type="hidden" class="TYPE" name="TYPE" value="3">
                                                        <div class="p-20">
                                                            <div class="row">
                                                                <?php
                                                                $i = 0;
                                                                if(!empty($_GET['id'])) {
                                                                    $row = $db->Execute("SELECT DOA_RATE_TYPE.PK_RATE_TYPE, DOA_RATE_TYPE.RATE_NAME, DOA_RATE_TYPE.PRICE_TYPE, DOA_USER_RATE.RATE, DOA_USER_RATE.ACTIVE FROM DOA_RATE_TYPE LEFT JOIN DOA_USER_RATE ON DOA_RATE_TYPE.PK_RATE_TYPE = DOA_USER_RATE.PK_RATE_TYPE WHERE DOA_RATE_TYPE.ACTIVE = 1 AND DOA_USER_RATE.PK_USER = '$_GET[id]' ORDER BY DOA_RATE_TYPE.PK_RATE_TYPE ASC");
                                                                    while (!$row->EOF) { ?>
                                                                        <div class="col-12">
                                                                            <div class="row form-group" style="margin-bottom: 10px;">
                                                                                <div class="col-2" style="margin-top: 10px;">
                                                                                    <label class="form-label" for="<?=$row->fields['PK_RATE_TYPE']?>"><?=$row->fields['RATE_NAME']?></label>
                                                                                </div>
                                                                                <div class="col-1" style="width: 4.5%; margin-top: 7px;">
                                                                                    <input type="hidden" name="PK_RATE_TYPE[]" value="<?=$row->fields['PK_RATE_TYPE']?>">
                                                                                    <input type="checkbox" class="form-check-input engagement_terms" name="PK_RATE_TYPE_ACTIVE[<?=$i?>]" id="<?=$row->fields['PK_RATE_TYPE']?>" value="1" <?=(is_null($row->fields['ACTIVE']) || $row->fields['ACTIVE'] == 0)?'':'checked'?>>
                                                                                </div>
                                                                                <div class="col-4" style="*display: <?=(is_null($row->fields['ACTIVE']) || $row->fields['ACTIVE'] == 0)?'none':''?>;">
                                                                                    <div class="col-md-6">
                                                                                        <div class="input-group">
                                                                                            <?php if ($row->fields['PRICE_TYPE'] == 1){ ?>
                                                                                                <span class="input-group-text"><?=$currency?></span>
                                                                                            <?php } else { ?>
                                                                                                <span class="input-group-text">%</span>
                                                                                            <?php } ?>
                                                                                            <input type="text" class="form-control" oninput="setFormat(this)" name="RATE[]" value="<?=$row->fields['RATE']?>" style="text-align: right; width: 25%;">
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <?php
                                                                        $i++;
                                                                        $row->MoveNext();
                                                                    }
                                                                }else {
                                                                    $row = $db->Execute("SELECT DOA_RATE_TYPE.PK_RATE_TYPE, DOA_RATE_TYPE.RATE_NAME, DOA_RATE_TYPE.PRICE_TYPE FROM DOA_RATE_TYPE WHERE DOA_RATE_TYPE.ACTIVE = 1 ORDER BY DOA_RATE_TYPE.PK_RATE_TYPE ASC");
                                                                    while (!$row->EOF) { ?>
                                                                        <div class="col-12">
                                                                            <div class="row form-group" style="margin-bottom: 10px;">
                                                                                <div class="col-2" style="margin-top: 10px;">
                                                                                    <label class="form-label" for="<?=$row->fields['PK_RATE_TYPE']?>"><?=$row->fields['RATE_NAME']?></label>
                                                                                </div>
                                                                                <div class="col-1" style="width: 4.5%; margin-top: 7px;">
                                                                                    <input type="hidden" name="PK_RATE_TYPE[]" value="<?=$row->fields['PK_RATE_TYPE']?>">
                                                                                    <input type="checkbox" class="form-check-input engagement_terms" oninput="setFormat(this)" name="PK_RATE_TYPE_ACTIVE[<?=$i?>]" id="<?=$row->fields['PK_RATE_TYPE']?>" value="1">
                                                                                </div>
                                                                                <div class="col-4" style="display: <?=(is_null($row->fields['ACTIVE']) || $row->fields['ACTIVE'] == 0)?'none':''?>;">
                                                                                    <div class="col-md-6">
                                                                                        <div class="input-group">
                                                                                            <?php if ($row->fields['PRICE_TYPE'] == 1){ ?>
                                                                                                <span class="input-group-text"><?=$currency?></span>
                                                                                            <?php } else { ?>
                                                                                                <span class="input-group-text">%</span>
                                                                                            <?php } ?>
                                                                                            <input type="text" class="form-control" name="RATE[]" style="text-align: right; width: 25%;">
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <?php
                                                                        $i++;
                                                                        $row->MoveNext();
                                                                    }
                                                                } ?>
                                                            </div>
                                                        </div>

                                                        <h4 class="card-title">Service Based Terms</h4>
                                                        <div class="p-20">
                                                            <div class="row">
                                                                <div class="col-md-3">
                                                                    <label class="form-label">Service</label>
                                                                </div>
                                                                <div class="col-md-3">
                                                                    <label class="form-label">Amount</label>
                                                                </div>
                                                                <div class="col-md-3">
                                                                    <a href="javascript:;" class="btn btn-info waves-effect waves-light text-white" onclick="addMoreCodeCommission()"><i class="ti-plus"></i> New</a>
                                                                </div>
                                                            </div>

                                                            <?php
                                                            if(!empty($_GET['id'])) {
                                                                $code_commission_data = $db->Execute("SELECT * FROM DOA_SERVICE_COMMISSION WHERE PK_USER = ".$_GET['id']);
                                                                while (!$code_commission_data->EOF) { ?>
                                                                    <div class="row m-t-10">
                                                                        <div class="col-md-3">
                                                                            <select class="form-control" name="PK_SERVICE_MASTER[]">
                                                                                <option value="">Select Service</option>
                                                                                <?php
                                                                                $row = $db->Execute("SELECT * FROM DOA_SERVICE_MASTER WHERE PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER']);
                                                                                while (!$row->EOF) { ?>
                                                                                    <option value="<?php echo $row->fields['PK_SERVICE_MASTER'];?>" <?=($code_commission_data->fields['PK_SERVICE_MASTER']==$row->fields['PK_SERVICE_MASTER'])?'selected':''?>><?=$row->fields['SERVICE_NAME']?></option>
                                                                                    <?php $row->MoveNext(); } ?>
                                                                            </select>
                                                                        </div>
                                                                        <div class="col-md-3">
                                                                            <input type="text" class="form-control" name="COMMISSION_AMOUNT[]" value="<?=$code_commission_data->fields['COMMISSION_AMOUNT']?>">
                                                                        </div>
                                                                        <div class="col-2 m-t-5">
                                                                            <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                                                        </div>
                                                                    </div>
                                                                    <?php $code_commission_data->MoveNext(); } ?>
                                                            <?php } else { ?>
                                                                <div class="row m-t-10">
                                                                    <div class="col-md-3">
                                                                        <select class="form-control" name="PK_SERVICE_MASTER[]">
                                                                            <option value="">Select Service</option>
                                                                            <?php
                                                                            $row = $db->Execute("SELECT * FROM DOA_SERVICE_MASTER WHERE PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER']);
                                                                            while (!$row->EOF) { ?>
                                                                                <option value="<?php echo $row->fields['PK_SERVICE_MASTER'];?>"><?=$row->fields['SERVICE_NAME']?></option>
                                                                                <?php $row->MoveNext(); } ?>
                                                                        </select>
                                                                    </div>
                                                                    <div class="col-md-3">
                                                                        <input type="text" class="form-control" name="COMMISSION_AMOUNT[]">
                                                                    </div>
                                                                    <div class="col-2 m-t-5">
                                                                        <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                                                    </div>
                                                                </div>
                                                            <?php } ?>

                                                            <div id="add_more_code_commission"></div>
                                                        </div>


                                                        <div class="form-group">
                                                            <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white"><?=empty($_GET['id'])?'Continue':'Save'?></button>
                                                            <button type="button" id="cancel_button" class="btn btn-inverse waves-effect waves-light">Cancel</button>
                                                        </div>
                                                    </form>
                                                </div>

                                                <div class="tab-pane" id="documents" role="tabpanel">
                                                    <form id="document_form">
                                                        <input type="hidden" name="FUNCTION_NAME" value="saveUserDocumentData">
                                                        <input type="hidden" class="PK_USER" name="PK_USER" value="<?=$PK_USER?>">
                                                        <input type="hidden" class="PK_CUSTOMER_DETAILS" name="PK_CUSTOMER_DETAILS" value="<?=$PK_CUSTOMER_DETAILS?>">
                                                        <input type="hidden" class="TYPE" name="TYPE" value="2">
                                                        <div>
                                                            <div class="card-body" id="append_user_document">
                                                                <?php
                                                                if(!empty($_GET['id'])) { $user_doc_count = 0;
                                                                    $row = $db->Execute("SELECT * FROM DOA_USER_DOCUMENT WHERE PK_USER = '$PK_USER'");
                                                                    while (!$row->EOF) { ?>
                                                                        <div class="row">
                                                                            <div class="col-5">
                                                                                <div class="form-group">
                                                                                    <label class="form-label">Document Name</label>
                                                                                    <input type="text" name="DOCUMENT_NAME[]" class="form-control" placeholder="Enter Document Name" value="<?=$row->fields['DOCUMENT_NAME']?>">
                                                                                </div>
                                                                            </div>
                                                                            <div class="col-5">
                                                                                <div class="form-group">
                                                                                    <label class="form-label">Document File</label>
                                                                                    <input type="file" name="FILE_PATH[]" class="form-control">
                                                                                    <a target="_blank" href="<?=$row->fields['FILE_PATH']?>">View</a>
                                                                                    <input type="hidden" name="FILE_PATH_URL[]" value="<?=$row->fields['FILE_PATH']?>">
                                                                                </div>
                                                                            </div>
                                                                            <div class="col-2">
                                                                                <div class="form-group" style="margin-top: 30px;">
                                                                                    <a href="javascript:;" class="btn btn-danger waves-effect waves-light m-r-10 text-white" onclick="removeUserDocument(this);"><i class="ti-trash"></i></a>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <?php $row->MoveNext(); $user_doc_count++;} ?>
                                                                <?php } else { $user_doc_count = 1;?>
                                                                    <div class="row">
                                                                        <div class="col-5">
                                                                            <div class="form-group">
                                                                                <label class="form-label">Document Name</label>
                                                                                <input type="text" name="DOCUMENT_NAME[]" class="form-control" placeholder="Enter Document Name">
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-5">
                                                                            <div class="form-group">
                                                                                <label class="form-label">Document File</label>
                                                                                <input type="file" name="FILE_PATH[]" class="form-control">
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-2">
                                                                            <div class="form-group" style="margin-top: 30px;">
                                                                                <a href="javascript:;" class="btn btn-danger waves-effect waves-light m-r-10 text-white" onclick="removeUserDocument(this);"><i class="ti-trash"></i></a>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                <?php } ?>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-11">
                                                                <div class="form-group">
                                                                    <a href="javascript:;" class="btn btn-info waves-effect waves-light m-r-10 text-white" onclick="addMoreUserDocument();"><i class="ti-plus"></i> New</a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="form-group">
                                                            <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white"><?=empty($_GET['id'])?'Continue':'Save'?></button>
                                                            <button type="button" id="cancel_button" class="btn btn-inverse waves-effect waves-light">Cancel</button>
                                                        </div>
                                                    </form>
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php require_once('../includes/footer.php');?>
    <script src="../assets/sumoselect/jquery.sumoselect.min.js"></script>
    <script>
        $('.datepicker-past').datepicker({
            format: 'mm/dd/yyyy',
            maxDate: 0
        });

        $('.multi_sumo_select').SumoSelect({placeholder: 'Select Location', selectAll: true});

        $(document).ready(function() {
            fetch_state(<?php  echo $PK_COUNTRY; ?>);
        });

        function fetch_state(PK_COUNTRY){
            jQuery(document).ready(function() {
                let data = "PK_COUNTRY="+PK_COUNTRY+"&PK_STATES=<?=$PK_STATES;?>";
                let value = $.ajax({
                    url: "ajax/state.php",
                    type: "POST",
                    data: data,
                    async: false,
                    cache :false,
                    success: function (result) {
                        document.getElementById('State_div').innerHTML = result;
                    }
                }).responseText;
            });
        }

        function setFormat(param) {
            if ($(param).val() != "") {
                $(param).val(parseFloat($(param).val().replace(/,/g, ""))
                    .toString()
                    .replace(/\B(?=(\d{3})+(?!\d))/g, ","));
            }
        }
    </script>
    <script>
        let PK_USER = parseInt(<?=empty($_GET['id'])?0:$_GET['id']?>);

        function isGood(password) {
            let password_strength = document.getElementById("password-text");

            if (password.length == 0) {
                password_strength.innerHTML = "";
                return;
            }
            //Regular Expressions.
            let regex = new Array();
            regex.push("[A-Z]"); //Uppercase Alphabet.
            regex.push("[a-z]"); //Lowercase Alphabet.
            regex.push("[0-9]"); //Digit.
            regex.push("[$@$!%*#?&]"); //Special Character.
            let passed = 0;
            //Validate for each Regular Expression.
            for (let i = 0; i < regex.length; i++) {
                if (new RegExp(regex[i]).test(password)) {
                    passed++;
                }
            }
            //Display status.
            let strength = "";
            switch (passed) {
                case 0:
                case 1:
                case 2:
                    strength = "<small class='progress-bar bg-danger' style='width: 50%'>Weak</small>";
                    break;
                case 3:
                    strength = "<small class='progress-bar bg-warning' style='width: 60%'>Medium</small>";
                    break;
                case 4:
                    strength = "<small class='progress-bar bg-success' style='width: 100%'>Strong</small>";
                    break;

            }
            // alert(strength);
            password_strength.innerHTML = strength;
        }

        $(document).on('click', '#cancel_button', function () {
            window.location.href='all_users.php';
        });

        function setFormat(param) {
            if ($(param).val() != "") {
                $(param).val(parseFloat($(param).val().replace(/,/g, ""))
                    .toString()
                    .replace(/\B(?=(\d{3})+(?!\d))/g, ","));
            }
        }

        function createLogin(param) {
            if ($(param).is(':checked')){
                $('#login_info_tab').show();
                $('#phone_label').text('*');
                $('#PHONE').prop('required', true);
                $('#email_label').text('*');
                $('#EMAIL_ID').prop('required', true);
                $('#submit_button').hide();
                $('#next_button_interest').hide();
                $('#next_button').show();
            }else {
                $('#login_info_tab').hide();
                $('#phone_label').text('');
                $('#PHONE').prop('required', false);
                $('#email_label').text('');
                $('#EMAIL_ID').prop('required', false);
                $('#submit_button').show();
                $('#next_button_interest').show();
                $('#next_button').hide();
            }
        }

        let counter = parseInt(<?=$user_doc_count?>);
        function addMoreUserDocument() {
            $('#append_user_document').append(`<div class="row">
                                                <div class="col-5">
                                                    <div class="form-group">
                                                        <label class="form-label">Document Name</label>
                                                        <input type="text" name="DOCUMENT_NAME[]" class="form-control" placeholder="Enter Document Name">
                                                    </div>
                                                </div>
                                                <div class="col-5">
                                                    <div class="form-group">
                                                        <label class="form-label">Document File</label>
                                                        <input type="file" name="FILE_PATH[]" class="form-control">
                                                    </div>
                                                </div>
                                                <div class="col-2">
                                                    <div class="form-group" style="margin-top: 30px;">
                                                        <a href="javascript:;" class="btn btn-danger waves-effect waves-light m-r-10 text-white" onclick="removeUserDocument(this);"><i class="ti-trash"></i></a>
                                                    </div>
                                                </div>
                                              </div>`);
            counter++;
        }

        function removeUserDocument(param) {
            $(param).closest('.row').remove();
            counter--;
        }

        $(document).on('submit', '#profile_form', function (event) {
            event.preventDefault();
            let form_data = new FormData($('#profile_form')[0]); //$('#profile_form').serialize();
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                data: form_data,
                processData: false,
                contentType: false,
                dataType: 'JSON',
                success:function (data) {
                    $('.PK_USER').val(data.PK_USER);
                    $('.PK_CUSTOMER_DETAILS').val(data.PK_CUSTOMER_DETAILS);
                    if (PK_USER == 0) {
                        if ($('#CREATE_LOGIN').is(':checked')) {
                            $('#login_info_tab_link')[0].click();
                        }else {
                            $('#rates_tab_link')[0].click();
                        }
                    }else{
                        window.location.href='all_users.php';
                    }
                }
            });
        });

        $(document).on('submit', '#login_form', function (event) {
            event.preventDefault();
            let PASSWORD = $('#PASSWORD').val();
            let CONFIRM_PASSWORD = $('#CONFIRM_PASSWORD').val();
            if (PASSWORD === CONFIRM_PASSWORD) {
                let SAVED_OLD_PASSWORD = $('#SAVED_OLD_PASSWORD').val();
                let OLD_PASSWORD = $('#OLD_PASSWORD').val();
                if (SAVED_OLD_PASSWORD && OLD_PASSWORD)
                {
                    $.ajax({
                        url: "ajax/check_old_password.php",
                        type: 'POST',
                        data: {ENTERED_PASSWORD: OLD_PASSWORD, SAVED_PASSWORD: SAVED_OLD_PASSWORD},
                        success: function (data) {
                            if (data == 0){
                                $('#password_error').text('Old Password not matched');
                            }else{
                                let form_data = $('#login_form').serialize();
                                $.ajax({
                                    url: "ajax/AjaxFunctions.php",
                                    type: 'POST',
                                    data: form_data,
                                    success: function (data) {
                                        window.location.href = 'all_users.php';
                                    }
                                });
                            }
                        }
                    });
                }else {
                    let form_data = $('#login_form').serialize();
                    $.ajax({
                        url: "ajax/AjaxFunctions.php",
                        type: 'POST',
                        data: form_data,
                        success: function (data) {
                            window.location.href = 'all_users.php';
                        }
                    });
                }
            }else{
                $('#password_error').text('Password and Confirm Password not matched');
            }
        });

        function addMoreCodeCommission(){
            $('#add_more_code_commission').append(`<div class="row m-t-15">
                                                        <div class="col-md-3">
                                                            <select class="form-control" name="PK_SERVICE_MASTER[]">
                                                                <option value="">Select Service</option>
                                                                <?php
            $row = $db->Execute("SELECT * FROM DOA_SERVICE_MASTER WHERE PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER']);
            while (!$row->EOF) { ?>
                                                                    <option value="<?php echo $row->fields['PK_SERVICE_MASTER'];?>"><?=$row->fields['SERVICE_NAME']?></option>
                                                                <?php $row->MoveNext(); } ?>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <input type="text" class="form-control" name="COMMISSION_AMOUNT[]">
                                                        </div>
                                                        <div class="col-2 m-t-5">
                                                            <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                                        </div>
                                                    </div>`);
        }

        $(document).on('submit', '#engagement_form', function (event) {
            event.preventDefault();
            let form_data = $('#engagement_form').serialize();
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                data: form_data,
                success:function (data) {
                    if (PK_USER == 0) {
                        $('#document_tab_link')[0].click();
                    }else{
                        window.location.href='all_users.php';
                    }
                }
            });
        });

        $(document).on('submit', '#document_form', function (event) {
            event.preventDefault();
            let form_data = new FormData($('#document_form')[0]); //$('#document_form').serialize();
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                data: form_data,
                processData: false,
                contentType: false,
                success:function (data) {
                    window.location.href='all_users.php';
                }
            });
        });
    </script>
</body>
</html>