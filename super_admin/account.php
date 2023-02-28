<?php
require_once('../global/config.php');

if (empty($_GET['id']))
    $title = "Add Account";
else
    $title = "Edit Account";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 1 ){
    header("location:../login.php");
    exit;
}

if (!empty($_GET['cond']) && $_GET['cond'] == 'del'){
    $db->Execute("DELETE FROM `DOA_USERS` WHERE `PK_USER` = ".$_GET['PK_USER']);
    $db->Execute("DELETE FROM `DOA_USER_PROFILE` WHERE `PK_USER` = ".$_GET['PK_USER']);
    header('location:account.php?id='.$_GET['id']);
}

if(!empty($_POST)){
    $ACCOUNT_DATA['PK_BUSINESS_TYPE'] = $_POST['PK_BUSINESS_TYPE'];
    $ACCOUNT_DATA['PK_ACCOUNT_TYPE'] = $_POST['PK_ACCOUNT_TYPE'];
    $ACCOUNT_DATA['BUSINESS_NAME'] = $_POST['BUSINESS_NAME'];
    $ACCOUNT_DATA['ADDRESS'] = $_POST['ACCOUNT_ADDRESS'];
    $ACCOUNT_DATA['ADDRESS_1'] = $_POST['ACCOUNT_ADDRESS_1'];
    $ACCOUNT_DATA['PK_COUNTRY'] = $_POST['ACCOUNT_PK_COUNTRY'];
    $ACCOUNT_DATA['PK_STATES'] = $_POST['PK_STATES'];
    $ACCOUNT_DATA['CITY'] = $_POST['ACCOUNT_CITY'];
    $ACCOUNT_DATA['ZIP'] = $_POST['ACCOUNT_ZIP'];
    $ACCOUNT_DATA['PHONE'] = $_POST['ACCOUNT_PHONE'];
    $ACCOUNT_DATA['FAX'] = $_POST['ACCOUNT_FAX'];
    $ACCOUNT_DATA['EMAIL'] = $_POST['ACCOUNT_EMAIL'];
    $ACCOUNT_DATA['WEBSITE'] = $_POST['ACCOUNT_WEBSITE'];

    $USER_DATA['PK_ROLES'] = $_POST['PK_ROLES'];
    $USER_DATA['USER_ID'] = $_POST['USER_ID'];
    $USER_DATA['FIRST_NAME'] = $_POST['FIRST_NAME'];
    $USER_DATA['LAST_NAME'] = $_POST['LAST_NAME'];
    $USER_DATA['EMAIL_ID'] = $_POST['EMAIL_ID'];
    $USER_DATA['PHONE'] = $_POST['PHONE'];
    if (!empty($_POST['PASSWORD']))
        $USER_DATA['PASSWORD'] = password_hash($_POST['PASSWORD'], PASSWORD_DEFAULT);
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
    $USER_PROFILE_DATA['NOTES'] = $_POST['NOTES'];

    if(empty($_GET['id'])){
        $ACCOUNT_DATA['ACTIVE'] = 1;
        $ACCOUNT_DATA['CREATED_BY']  = $_SESSION['PK_USER'];
        $ACCOUNT_DATA['CREATED_ON']  = date("Y-m-d H:i");
        db_perform('DOA_ACCOUNT_MASTER', $ACCOUNT_DATA, 'insert');
        $PK_ACCOUNT_MASTER = $db->insert_ID();
        $USER_DATA['PK_ACCOUNT_MASTER'] = $PK_ACCOUNT_MASTER;
        $USER_DATA['CREATE_LOGIN'] = 1;
        $USER_DATA['ACTIVE'] = 1;
        $USER_DATA['ABLE_TO_EDIT_PAYMENT_GATEWAY'] = isset($_POST['ABLE_TO_EDIT_PAYMENT_GATEWAY']) ? 1 : 0;
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
        $ACCOUNT_DATA['ACTIVE'] = $_POST['ACTIVE'];
        $ACCOUNT_DATA['EDITED_BY']	= $_SESSION['PK_USER'];
        $ACCOUNT_DATA['EDITED_ON'] = date("Y-m-d H:i");
        db_perform('DOA_ACCOUNT_MASTER', $ACCOUNT_DATA, 'update'," PK_ACCOUNT_MASTER =  '$_GET[id]'");
        if (empty($_POST['PK_USER_EDIT'])){
            $USER_DATA['PK_ACCOUNT_MASTER'] = $_GET[id];
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
        }else {
            $USER_DATA['ACTIVE'] = $_POST['ACTIVE'];
            $USER_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
            $USER_DATA['EDITED_ON'] = date("Y-m-d H:i");
            db_perform('DOA_USERS', $USER_DATA, 'update', " PK_USER =  '$_POST[PK_USER_EDIT]'");
            $USER_PROFILE_DATA['ACTIVE'] = $_POST['ACTIVE'];
            $USER_PROFILE_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
            $USER_PROFILE_DATA['EDITED_ON'] = date("Y-m-d H:i");
            db_perform('DOA_USER_PROFILE', $USER_PROFILE_DATA, 'update', " PK_USER =  '$_POST[PK_USER_EDIT]'");
        }
    }

    header("location:all_accounts.php");
}

$PK_ACCOUNT_MASTER = '';
$PK_BUSINESS_TYPE = '';
$PK_ACCOUNT_TYPE = '';
$BUSINESS_NAME = '';
$ACCOUNT_ADDRESS = '';
$ACCOUNT_ADDRESS_1 = '';
$ACCOUNT_PK_COUNTRY = '';
$ACCOUNT_PK_STATES = '';
$PK_STATE = '';
$ACCOUNT_CITY = '';
$ACCOUNT_ZIP = '';
$ACCOUNT_PHONE = '';
$ACCOUNT_FAX = '';
$ACCOUNT_EMAIL = '';
$ACCOUNT_WEBSITE = '';
$ACTIVE = '';
$ABLE_TO_EDIT_PAYMENT_GATEWAY = '';

$PK_USER_EDIT = '';
$PK_ROLES = '';
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
$NOTES = '';
if(!empty($_GET['id'])) {
    $account_res = $db->Execute("SELECT * FROM `DOA_ACCOUNT_MASTER` WHERE `PK_ACCOUNT_MASTER`  = '$_GET[id]'");
    if($account_res->RecordCount() == 0){
        header("location:all_accounts.php");
        exit;
    }
    $PK_ACCOUNT_MASTER = $_GET['id'];
    $PK_BUSINESS_TYPE = $account_res->fields['PK_BUSINESS_TYPE'];
    $PK_ACCOUNT_TYPE = $account_res->fields['PK_ACCOUNT_TYPE'];
    $BUSINESS_NAME = $account_res->fields['BUSINESS_NAME'];
    $ACCOUNT_ADDRESS = $account_res->fields['ADDRESS'];
    $ACCOUNT_ADDRESS_1 = $account_res->fields['ADDRESS_1'];
    $ACCOUNT_PK_COUNTRY = $account_res->fields['PK_COUNTRY'];
    $ACCOUNT_PK_STATES = $account_res->fields['PK_STATES'];
    $ACCOUNT_CITY = $account_res->fields['CITY'];
    $ACCOUNT_ZIP = $account_res->fields['ZIP'];
    $ACCOUNT_PHONE = $account_res->fields['PHONE'];
    $ACCOUNT_FAX = $account_res->fields['FAX'];
    $ACCOUNT_EMAIL = $account_res->fields['EMAIL'];
    $ACCOUNT_WEBSITE = $account_res->fields['WEBSITE'];
    $ACTIVE = $account_res->fields['ACTIVE'];

    $user_res = $db->Execute("SELECT DOA_USERS.PK_USER AS PK_USER_EDIT, DOA_USERS.PK_ROLES, DOA_USERS.FIRST_NAME, DOA_USERS.LAST_NAME, DOA_USERS.USER_ID, DOA_USERS.EMAIL_ID, DOA_USERS.USER_IMAGE, DOA_USERS.ACTIVE, DOA_USERS.ABLE_TO_EDIT_PAYMENT_GATEWAY, DOA_USER_PROFILE.GENDER, DOA_USER_PROFILE.DOB, DOA_USER_PROFILE.ADDRESS, DOA_USER_PROFILE.ADDRESS_1, DOA_USER_PROFILE.CITY, DOA_USER_PROFILE.PK_STATES, DOA_USER_PROFILE.ZIP, DOA_USER_PROFILE.PK_COUNTRY, DOA_USERS.PHONE, DOA_USER_PROFILE.FAX, DOA_USER_PROFILE.WEBSITE, DOA_USER_PROFILE.NOTES FROM DOA_USERS LEFT JOIN DOA_USER_PROFILE ON DOA_USERS.PK_USER = DOA_USER_PROFILE.PK_USER WHERE DOA_USERS.PK_ACCOUNT_MASTER = '$_GET[id]' AND DOA_USERS.CREATED_BY = '$_SESSION[PK_USER]'");

    if($user_res->RecordCount() > 0) {
        $PK_USER_EDIT = $user_res->fields['PK_USER_EDIT'];
        $PK_ROLES = $user_res->fields['PK_ROLES'];
        $USER_ID = $user_res->fields['USER_ID'];
        $FIRST_NAME = $user_res->fields['FIRST_NAME'];
        $LAST_NAME = $user_res->fields['LAST_NAME'];
        $EMAIL_ID = $user_res->fields['EMAIL_ID'];
        $USER_IMAGE = $user_res->fields['USER_IMAGE'];
        $GENDER = $user_res->fields['GENDER'];
        $DOB = $user_res->fields['DOB'];
        $ADDRESS = $user_res->fields['ADDRESS'];
        $ADDRESS_1 = $user_res->fields['ADDRESS_1'];
        $PK_COUNTRY = $user_res->fields['PK_COUNTRY'];
        $PK_STATES = $user_res->fields['PK_STATES'];
        $CITY = $user_res->fields['CITY'];
        $ZIP = $user_res->fields['ZIP'];
        $PHONE = $user_res->fields['PHONE'];
        $NOTES = $user_res->fields['NOTES'];
        $ABLE_TO_EDIT_PAYMENT_GATEWAY = $user_res->fields['ABLE_TO_EDIT_PAYMENT_GATEWAY'];
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<?php require_once('../includes/header.php');?>
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
                            <li class="breadcrumb-item"><a href="all_accounts.php">All Accounts</a></li>
                            <li class="breadcrumb-item active"><?=$title?></li>
                        </ol>
                    </div>
                </div>
            </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="card wizard-content">
                            <div class="card-body">
                                <!-- Nav tabs -->
                                <ul class="nav nav-tabs" role="tablist">
                                    <li class="active"> <a class="nav-link active" id="home_tab_link" data-bs-toggle="tab" href="#home" role="tab"><span class="hidden-sm-up"><i class="ti-home"></i></span> <span class="hidden-xs-down">Account Info</span></a> </li>
                                    <?php if(empty($_GET['id'])) { ?>
                                        <li> <a class="nav-link" id="profile_tab_link" data-bs-toggle="tab" href="#profile" role="tab"><span class="hidden-sm-up"><i class="ti-folder"></i></span> <span class="hidden-xs-down">User Profile</span></a> </li>
                                    <?php } else { ?>
                                        <li> <a class="nav-link" data-bs-toggle="tab" href="#login" role="tab" id="logintab"><span class="hidden-sm-up"><i class="ti-list"></i></span> <span class="hidden-xs-down">User List</span></a> </li>
                                    <?php } ?>
                                </ul>

                                <!-- Tab panes -->
                                <div class="tab-content tabcontent-border">
                                    <!--Account Info Tab-->
                                    <div class="tab-pane active" id="home" role="tabpanel">

                                        <form class="form-material form-horizontal" id="account_info_form">
                                            <input type="hidden" name="FUNCTION_NAME" value="saveAccountInfoData">
                                            <input type="hidden" class="PK_ACCOUNT_MASTER" name="PK_ACCOUNT_MASTER" value="<?=$PK_ACCOUNT_MASTER?>">
                                            <div class="p-20">
                                                <div class="row align-items-end">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Business Type<span class="text-danger">*</span>
                                                            </label>
                                                            <div class="col-md-12">
                                                                <select class="form-control" required name="PK_BUSINESS_TYPE" id="PK_BUSINESS_TYPE">
                                                                    <option value="">Select Business Type</option>
                                                                    <?php
                                                                    $row = $db->Execute("SELECT PK_BUSINESS_TYPE,BUSINESS_TYPE FROM DOA_BUSINESS_TYPE WHERE ACTIVE='1' ORDER BY PK_BUSINESS_TYPE");
                                                                    while (!$row->EOF) { ?>
                                                                        <option value="<?php echo $row->fields['PK_BUSINESS_TYPE'];?>" <?=($row->fields['PK_BUSINESS_TYPE'] == $PK_BUSINESS_TYPE)?"selected":""?>><?=$row->fields['BUSINESS_TYPE']?></option>
                                                                    <?php $row->MoveNext(); } ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Account Type<span class="text-danger">*</span>
                                                            </label>
                                                            <div class="col-md-12">
                                                                <?php
                                                                $row = $db->Execute("SELECT PK_ACCOUNT_TYPE,ACCOUNT_TYPE FROM DOA_ACCOUNT_TYPE WHERE ACTIVE='1' ORDER BY PK_ACCOUNT_TYPE");
                                                                while (!$row->EOF) { ?>
                                                                    <input type="radio" name="PK_ACCOUNT_TYPE" id="<?=$row->fields['PK_ACCOUNT_TYPE'];?>" value="<?=$row->fields['PK_ACCOUNT_TYPE'];?>" <?php if($row->fields['PK_ACCOUNT_TYPE'] == $PK_ACCOUNT_TYPE) echo 'checked';?> required>
                                                                    <label for="<?=$row->fields['PK_ACCOUNT_TYPE'];?>"><?=$row->fields['ACCOUNT_TYPE']?></label>
                                                                <?php $row->MoveNext(); } ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-12">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Business Name<span class="text-danger">*</span>
                                                            </label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="BUSINESS_NAME" name="BUSINESS_NAME" class="form-control" placeholder="Enter Business Name" required value="<?php echo $BUSINESS_NAME?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Address</label>
                                                            <div class="col-md-12">
                                                                <textarea class="form-control" rows="2" id="ACCOUNT_ADDRESS" name="ACCOUNT_ADDRESS" placeholder="Enter Address"><?php echo $ACCOUNT_ADDRESS?></textarea>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Apt/Ste</label>
                                                            <div class="col-md-12">
                                                                <textarea class="form-control" rows="2" id="ACCOUNT_ADDRESS_1" name="ACCOUNT_ADDRESS_1" placeholder="Enter Street/Apartment"><?php echo $ACCOUNT_ADDRESS_1?></textarea>
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
                                                                    <select class="form-control" name="ACCOUNT_PK_COUNTRY" id="ACCOUNT_PK_COUNTRY" onChange="fetch_Account_State(this.value)">
                                                                        <option>Select Country</option>
                                                                        <?php
                                                                        $row = $db->Execute("SELECT PK_COUNTRY,COUNTRY_NAME FROM DOA_COUNTRY WHERE ACTIVE = 1 ORDER BY PK_COUNTRY");
                                                                        while (!$row->EOF) { ?>
                                                                            <option value="<?php echo $row->fields['PK_COUNTRY'];?>" <?=($row->fields['PK_COUNTRY'] == $ACCOUNT_PK_COUNTRY)?"selected":""?>><?=$row->fields['COUNTRY_NAME']?></option>
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
                                                                    <div id="Account_State_div"></div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">City</span>
                                                            </label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="ACCOUNT_CITY" name="ACCOUNT_CITY" class="form-control" placeholder="Entere City" value="<?php echo $ACCOUNT_CITY?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Zip Code</span>
                                                            </label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="ACCOUNT_ZIP" name="ACCOUNT_ZIP" class="form-control" placeholder="Enter Zip Code" value="<?php echo $ACCOUNT_ZIP?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Business Phone</label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="ACCOUNT_PHONE" name="ACCOUNT_PHONE" class="form-control" placeholder="Enter Business Phone No." value="<?php echo $ACCOUNT_PHONE?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Business Fax</label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="ACCOUNT_FAX" name="ACCOUNT_FAX" class="form-control" placeholder="Enter Business Fax" value="<?php echo $ACCOUNT_FAX;?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Business Email<span class="text-danger">*</span></label>
                                                            <div class="col-md-12">
                                                                <input type="email" id="ACCOUNT_EMAIL" name="ACCOUNT_EMAIL" class="form-control" placeholder="Enter Business Email" required value="<?php echo $ACCOUNT_EMAIL?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Website
                                                            </label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="ACCOUNT_WEBSITE" name="ACCOUNT_WEBSITE" class="form-control" placeholder="Enter Website" value="<?php echo $ACCOUNT_WEBSITE?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <?php if(!empty($_GET['id'])) { ?>
                                                    <div class="row" style="margin-bottom: 15px; margin-top: 15px;">
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


                                    <?php if(empty($_GET['id'])) { ?>
                                    <!--User Profile Info Tab-->
                                    <div class="tab-pane p-20" id="profile" role="tabpanel">
                                        <form class="form-material form-horizontal" id="profile_info_form">
                                            <input type="hidden" name="FUNCTION_NAME" value="saveProfileInfoData">
                                            <input type="hidden" class="PK_ACCOUNT_MASTER" name="PK_ACCOUNT_MASTER" value="<?=$PK_ACCOUNT_MASTER?>">
                                            <input type="hidden" class="PK_USER_EDIT" name="PK_USER_EDIT" value="<?=$PK_USER_EDIT?>">
                                            <div class="p-20">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <label class="form-label mb-0">Roles</label>
                                                        <?php $row = $db->Execute("SELECT PK_ROLES, ROLES FROM DOA_ROLES WHERE ACTIVE='1' AND PK_ROLES = 2"); ?>
                                                        <input type="hidden" name="PK_ROLES" value="<?php echo $row->fields['PK_ROLES'];?>">
                                                        <input type="text" class="form-control" value="<?=$row->fields['ROLES']?>" readonly>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">User Name<span class="text-danger">*</span>
                                                            </label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="USER_ID" name="USER_ID" class="form-control" placeholder="Enter User Name" required data-validation-required-message="This field is required" onkeyup="ValidateUsername()" value="<?=$USER_ID?>">
                                                            </div>
                                                        </div>
                                                        <span id="lblError" style="color: red"></span>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">First Name<span class="text-danger">*</span>
                                                            </label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="FIRST_NAME" name="FIRST_NAME" class="form-control" placeholder="Enter First Name" required value="<?=$FIRST_NAME?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Last Name
                                                            </label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="LAST_NAME" name="LAST_NAME" class="form-control" placeholder="Enter Last Name" value="<?=$LAST_NAME?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Email<span class="text-danger">*</span>
                                                            </label>
                                                            <div class="col-md-12">
                                                                <input type="email" id="EMAIL_ID" name="EMAIL_ID" class="form-control" placeholder="Enter Email Address" required data-validation-required-message="This field is required" value="<?=$EMAIL_ID?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label class="form-label">Gender</label>
                                                            <select class="form-control form-control" id="GENDER" name="GENDER">
                                                                <option value="1" <?php if($GENDER == "1") echo 'selected = "selected"';?>>Male</option>
                                                                <option value="2" <?php if($GENDER == "2") echo 'selected = "selected"';?>>Female</option>
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
                                                            <label class="col-md-12">Address
                                                            </label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="ADDRESS" name="ADDRESS" class="form-control" placeholder="Enter Address" value="<?php echo $ADDRESS?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Apt/Ste
                                                            </label>
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
                                                            <label class="col-md-12">City</span>
                                                            </label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="CITY" name="CITY" class="form-control" placeholder="Enter your city" value="<?php echo $CITY?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Zip Code</span>
                                                            </label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="ZIP" name="ZIP" class="form-control" placeholder="Enter Zip Code" value="<?php echo $ZIP?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Phone
                                                            </label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="PHONE" name="PHONE" class="form-control" placeholder="Enter Phone No." value="<?php echo $PHONE?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Remarks</label>
                                                            <div class="col-md-12">
                                                                <textarea class="form-control" rows="2" id="NOTES" name="NOTES"><?php echo $NOTES?></textarea>
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
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <?php if($USER_IMAGE!=''){?><div style="width: 120px;height: 120px;margin-top: 25px;"><a class="fancybox" href="<?php echo $USER_IMAGE;?>" data-fancybox-group="gallery"><img src = "<?php echo $USER_IMAGE;?>" style="width:120px; height:120px" /></a></div><?php } ?>
                                                </div>

                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Password<span class="text-danger">*</span>
                                                            </label>
                                                            <div class="col-md-12">
                                                                <input type="password" autocomplete="off" class="form-control" placeholder="Password" aria-label="Password" aria-describedby="basic-addon3" name="PASSWORD" id="PASSWORD" onkeyup="isGood(this.value)" required>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Confirm Password<span class="text-danger">*</span>
                                                            </label>
                                                            <div class="col-md-12">
                                                                <input type="password" autocomplete="off" class="form-control" placeholder="Password" aria-label="Password" aria-describedby="basic-addon3" name="CONFIRM_PASSWORD" id="CONFIRM_PASSWORD" onkeyup="isGood(this.value)" required>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-12">
                                                        <span style="color:red">Note  : Password Must contain at least one number and one uppercase and lowercase letter, and at least 8 or more characters</span>
                                                    </div>
                                                </div>
                                                <div class="row" style="margin-bottom: 20px;">
                                                    <div class="col-2">
                                                        Password Strength:
                                                    </div>
                                                    <div class="col-3">
                                                        <small id="password-text"></small>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-6">
                                                        <label class="col-md-12"><input type="checkbox" id="ABLE_TO_EDIT_PAYMENT_GATEWAY" name="ABLE_TO_EDIT_PAYMENT_GATEWAY" class="form-check-inline" <?=($ABLE_TO_EDIT_PAYMENT_GATEWAY == 1)?'checked':''?>> Able to edit payment gateway</label>
                                                    </div>
                                                </div>

                                            </div>
                                            <div class="form-group">
                                                <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white"><?=empty($_GET['id'])?'Continue':'Save'?></button>
                                                <button type="button" id="cancel_button" class="btn btn-inverse waves-effect waves-light">Cancel</button>
                                            </div>
                                        </form>
                                    </div>
                                    <?php } else { ?>
                                    <!--User List Tab-->
                                    <div class="tab-pane p-20" id="login" role="tabpanel">
                                        <table id="myTable" class="table table-striped border">
                                            <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>Name</th>
                                                <th>Username</th>
                                                <th>Roles</th>
                                                <th>Email Id</th>
                                                <th>Actions</th>
                                            </tr>
                                            </thead>

                                            <tbody>
                                            <?php
                                            $i=1;
                                            $row = $db->Execute("SELECT DOA_USERS.PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_ID, DOA_ROLES.ROLES, DOA_USERS.EMAIL_ID, DOA_USERS.ACTIVE FROM DOA_USERS LEFT JOIN DOA_ROLES ON DOA_ROLES.PK_ROLES = DOA_USERS.PK_ROLES WHERE DOA_USERS.PK_ROLES IN(2,3) AND DOA_USERS.PK_ACCOUNT_MASTER='$_GET[id]'");
                                            while (!$row->EOF) { ?>
                                                <tr>
                                                    <td onclick="editpage(<?=$row->fields['PK_USER']?>, <?=$_GET['id']?>);"><?=$i;?></td>
                                                    <td onclick="editpage(<?=$row->fields['PK_USER']?>, <?=$_GET['id']?>);"><?=$row->fields['NAME']?></td>
                                                    <td onclick="editpage(<?=$row->fields['PK_USER']?>, <?=$_GET['id']?>);"><?=$row->fields['USER_ID']?></td>
                                                    <td onclick="editpage(<?=$row->fields['PK_USER']?>, <?=$_GET['id']?>);"><?=$row->fields['ROLES']?></td>
                                                    <td onclick="editpage(<?=$row->fields['PK_USER']?>, <?=$_GET['id']?>);"><?=$row->fields['EMAIL_ID']?></td>
                                                    <td style="padding: 10px 0px 0px 0px;font-size: 20px;">
                                                        <a href="edit_account_user.php?id=<?=$row->fields['PK_USER']?>&ac_id=<?=$_GET['id']?>" title="Reset Password" style="color: #03a9f3;"><i class="ti-lock"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;
                                                        <?php if($row->fields['ACTIVE']==1){ ?>
                                                            <span title="Active" class="active-box-green"></span>
                                                        <?php } else{ ?>
                                                            <span title="Inactive" class="active-box-red"></span>
                                                        <?php } ?>&nbsp;&nbsp;
                                                        <a href="javascript:;" data-href="account.php?id=<?=$_GET['id']?>&PK_USER=<?=$row->fields['PK_USER']?>&cond=del" onclick="confirmDelete(this);" title="Delete" style="color: red;"><i class="ti-trash"></i></a>
                                                    </td>
                                                </tr>
                                                <?php $row->MoveNext();
                                                $i++; } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<style>
    .progress-bar {
        border-radius: 5px;
        height:18px !important;
    }
</style>
<?php require_once('../includes/footer.php');?>
<script>
    $('.datepicker-past').datepicker({
        format: 'mm/dd/yyyy',
        maxDate: 0
    });

    $(document).ready(function() {
        fetch_state(<?php  echo $PK_COUNTRY; ?>);
        fetch_Account_State(<?php  echo $ACCOUNT_PK_COUNTRY; ?>);
    });

    function fetch_state(PK_COUNTRY){

        jQuery(document).ready(function($) {

            var data = "PK_COUNTRY="+PK_COUNTRY+"&PK_STATES=<?=$PK_STATES;?>";

            var value = $.ajax({
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
    function fetch_Account_State(PK_COUNTRY){

        jQuery(document).ready(function($) {

            var data = "PK_COUNTRY="+PK_COUNTRY+"&PK_STATES=<?=$ACCOUNT_PK_STATES;?>";

            var value = $.ajax({
                url: "ajax/state.php",
                type: "POST",
                data: data,
                async: false,
                cache :false,
                success: function (result) {
                    document.getElementById('Account_State_div').innerHTML = result;

                }
            }).responseText;
        });
    }

</script>
<script>
    function isGood(password) {
        //alert(password);
        var password_strength = document.getElementById("password-text");

        //TextBox left blank.
        if (password.length == 0) {
            password_strength.innerHTML = "";
            return;
        }

        //Regular Expressions.
        var regex = new Array();
        regex.push("[A-Z]"); //Uppercase Alphabet.
        regex.push("[a-z]"); //Lowercase Alphabet.
        regex.push("[0-9]"); //Digit.
        regex.push("[$@$!%*#?&]"); //Special Character.

        var passed = 0;

        //Validate for each Regular Expression.
        for (var i = 0; i < regex.length; i++) {
            if (new RegExp(regex[i]).test(password)) {
                passed++;
            }
        }

        //Display status.
        var strength = "";
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
    function ValidateUsername() {
        var username = document.getElementById("User_Id").value;
        var lblError = document.getElementById("lblError");
        lblError.innerHTML = "";
        var expr = /^[a-zA-Z0-9_]{8,20}$/;
        if (!expr.test(username)) {
            lblError.innerHTML = "Only Alphabets, Numbers and Underscore and between 8 to 20 characters.";
        }
        else{
            lblError.innerHTML = "";
        }
    }

    function editpage(PK_USER, AC_ID){
        window.location.href = "edit_account_user.php?id="+PK_USER+"&ac_id="+AC_ID;
    }

    function confirmDelete(anchor)
    {
        let conf = confirm("Are you sure you want to delete?");
        if(conf)
            window.location=$(anchor).data("href");
    }

    $(document).on('click', '#cancel_button', function () {
        window.location.href='all_accounts.php';
    });

    let PK_ACCOUNT_MASTER = parseInt(<?=empty($_GET['id'])?0:$_GET['id']?>);

    $(document).on('submit', '#account_info_form', function (event) {
        event.preventDefault();
        let form_data = $('#account_info_form').serialize();
        $.ajax({
            url: "ajax/AjaxFunctions.php",
            type: 'POST',
            data: form_data,
            dataType: 'JSON',
            success:function (data) {
                $('.PK_ACCOUNT_MASTER').val(data);
                if (PK_ACCOUNT_MASTER == 0) {
                    $('#profile_tab_link')[0].click();
                }else{
                    window.location.href='all_accounts.php';
                }
            }
        });
    });

    $(document).on('submit', '#profile_info_form', function (event) {
        event.preventDefault();
        let form_data = $('#profile_info_form').serialize();
        $.ajax({
            url: "ajax/AjaxFunctions.php",
            type: 'POST',
            data: form_data,
            dataType: 'JSON',
            success:function (data) {
                $('.PK_ACCOUNT_MASTER').val(data);
                window.location.href='all_accounts.php';
            }
        });
    });
</script>




</body>
</html>