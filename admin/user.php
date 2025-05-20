<?php
require_once('../global/config.php');
global $db;

$userType = "Users";
$user_role_condition = " AND PK_ROLES IN(2,3,5,6,7,8,9,10)";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

$CREATE_LOGIN = 0;

if (empty($_GET['id']))
    $title = "Add " . $userType;
else
    $title = "Edit " . $userType;

$PK_ACCOUNT_MASTER = $_SESSION['PK_ACCOUNT_MASTER'];

$PK_USER = 0;
$PK_CUSTOMER_DETAILS = '';
$USER_NAME = '';
$FIRST_NAME = '';
$LAST_NAME = '';
//$TYPE = '';
$EMAIL_ID = '';
$DISPLAY_ORDER = '';
$ARTHUR_MURRAY_ID = '';
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
$APPEAR_IN_CALENDAR = 0;

$selected_roles = array();
//end
if (!empty($_GET['id'])) {
    $res = $db->Execute("SELECT * FROM DOA_USERS WHERE PK_USER = '$_GET[id]'");
    if ($res->RecordCount() == 0) {
        header("location:all_users.php");
        exit;
    }
    $PK_USER = $res->fields['PK_USER'];
    $USER_NAME = $res->fields['USER_NAME'];
    $FIRST_NAME = $res->fields['FIRST_NAME'];
    $LAST_NAME = $res->fields['LAST_NAME'];
    //$TYPE = $res->fields['TYPE'];
    $EMAIL_ID = $res->fields['EMAIL_ID'];
    $DISPLAY_ORDER = $res->fields['DISPLAY_ORDER'];
    $ARTHUR_MURRAY_ID = $res->fields['ARTHUR_MURRAY_ID'];
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
    $NOTES = $res->fields['NOTES'];
    $ACTIVE = $res->fields['ACTIVE'];
    $PASSWORD = $res->fields['PASSWORD'];
    $INACTIVE_BY_ADMIN = $res->fields['INACTIVE_BY_ADMIN'];
    $CAN_EDIT_ENROLLMENT = $res->fields['CAN_EDIT_ENROLLMENT'];
    $CREATE_LOGIN = $res->fields['CREATE_LOGIN'];
    $APPEAR_IN_CALENDAR = $res->fields['APPEAR_IN_CALENDAR'];
    $TICKET_SYSTEM_ACCESS = $res->fields['TICKET_SYSTEM_ACCESS'];

    if (!empty($_GET['id'])) {
        $PK_USER = $_GET['id'];
        $selected_roles_row = $db->Execute("SELECT PK_ROLES FROM `DOA_USER_ROLES` WHERE `PK_USER` = '$PK_USER'");
        while (!$selected_roles_row->EOF) {
            $selected_roles[] = $selected_roles_row->fields['PK_ROLES'];
            $selected_roles_row->MoveNext();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<?php require_once('../includes/header.php'); ?>
<style>
    .progress-bar {
        border-radius: 5px;
        height: 18px !important;
    }
</style>
<link href="../assets/sumoselect/sumoselect.min.css" rel="stylesheet" />

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">
        <?php require_once('../includes/top_menu.php'); ?>
        <div class="page-wrapper">
            <?php require_once('../includes/top_menu_bar.php') ?>
            <div class="container-fluid body_content">
                <div class="row page-titles">
                    <div class="col-md-5 align-self-center">
                        <h4 class="text-themecolor">
                            <div class="card-title">
                                <?php
                                if (!empty($_GET['id'])) {
                                    echo "Edit " . $FIRST_NAME . " " . $LAST_NAME;
                                }
                                ?>
                            </div>
                        </h4>
                    </div>
                    <div class="col-md-3 align-self-center" style="position: sticky; z-index: 1;">
                        <?php if (!empty($_GET['id'])) { ?>
                            <select required name="NAME" id="NAME" onchange="editpage(this);">
                                <option value="">Select User</option>
                                <?php
                                $row = $db->Execute("SELECT DISTINCT (DOA_USERS.PK_USER), CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.ACTIVE FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER LEFT JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER LEFT JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER WHERE DOA_USER_LOCATION.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_USER_ROLES.PK_ROLES IN(2,3,5,6,7,8) AND DOA_USERS.ACTIVE = 1 AND DOA_USERS.IS_DELETED = 0 AND DOA_USERS.PK_ACCOUNT_MASTER = " . $_SESSION['PK_ACCOUNT_MASTER']);
                                while (!$row->EOF) { ?>
                                    <option value="<?php echo $row->fields['PK_USER']; ?>" data-id="<?php echo $row->fields['PK_USER']; ?>" <?= ($row->fields['PK_USER'] == $_GET['id']) ? 'selected' : '' ?>><?= $row->fields['NAME'] ?></option>
                                <?php $row->MoveNext();
                                } ?>
                            </select>
                        <?php } ?>
                    </div>

                    <div class="col-md-4 align-self-center text-end">
                        <div class="d-flex justify-content-end align-items-center">
                            <ol class="breadcrumb justify-content-end">
                                <li class="breadcrumb-item"><a href="setup.php">Setup</a></li>
                                <li class="breadcrumb-item"><a href="all_users.php">All Users</a></li>
                                <li class="breadcrumb-item active"><?= $title ?></li>
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
                                                    <li> <a class="nav-link active" data-bs-toggle="tab" id="profile_tab_link" href="#profile" role="tab"><span class="hidden-sm-up"><i class="ti-id-badge"></i></span> <span class="hidden-xs-down">Profile</span></a> </li>
                                                    <li id="login_info_tab" style="display: <?= ($CREATE_LOGIN == 1) ? '' : 'none' ?>"> <a class="nav-link" id="login_info_tab_link" onclick="goToLoginTab()" data-bs-toggle="tab" href="#login" role="tab"><span class="hidden-sm-up"><i class="ti-lock"></i></span> <span class="hidden-xs-down">Login Info</span></a> </li>
                                                    <li id="rates_tab"> <a class="nav-link" id="rates_tab_link" data-bs-toggle="tab" href="#rates" role="tab"><span class="hidden-sm-up"><i class="ti-money"></i></span> <span class="hidden-xs-down">Rates</span></a> </li>
                                                    <li id="service_tab"> <a class="nav-link" id="service_tab_link" onclick="getLocationHours()" data-bs-toggle="tab" href="#service" role="tab"><span class="hidden-sm-up"><i class="ti-server"></i></span> <span class="hidden-xs-down">Service</span></a> </li>
                                                    <li> <a class="nav-link" data-bs-toggle="tab" href="#documents" id="document_tab_link" role="tab"><span class="hidden-sm-up"><i class="ti-files"></i></span> <span class="hidden-xs-down">Documents</span></a> </li>
                                                    <li id="comment_tab"> <a class="nav-link" id="comment_tab_link" data-bs-toggle="tab" href="#comments" role="tab"><span class="hidden-sm-up"><i class="ti-comment"></i></span> <span class="hidden-xs-down">Comments</span></a> </li>
                                                </ul>
                                                <!-- Tab panes -->
                                                <div class="tab-content tabcontent-border">
                                                    <div class="tab-pane active" id="profile" role="tabpanel">
                                                        <form class="form-material form-horizontal" id="profile_form">
                                                            <input type="hidden" name="FUNCTION_NAME" value="saveProfileData">
                                                            <input type="hidden" class="PK_USER" name="PK_USER" value="<?= $PK_USER ?>">
                                                            <input type="hidden" class="TYPE" name="TYPE" value="1">
                                                            <div class="p-20">
                                                                <div class="row">
                                                                    <div class="col-5">
                                                                        <div class="form-group">
                                                                            <label class="form-label">First Name<span class="text-danger">*</span></label>
                                                                            <div class="col-md-12">
                                                                                <input type="text" id="FIRST_NAME" name="FIRST_NAME" class="form-control" placeholder="Enter First Name" required value="<?= $FIRST_NAME ?>">
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-5">
                                                                        <div class="form-group">
                                                                            <label class="form-label">Last Name</label>
                                                                            <div class="col-md-12">
                                                                                <input type="text" id="LAST_NAME" name="LAST_NAME" class="form-control" placeholder="Enter Last Name" value="<?= $LAST_NAME ?>">
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <!--<div class="col-md-2">
                                                                    <div class="form-group">
                                                                        <label class="form-label">Select Type</label>
                                                                        <select class="form-control" name="TYPE">
                                                                            <option value="">Select</option>
                                                                            <option value="C" <?php /*=($TYPE == 'C')?'selected':''*/ ?>>Counsellor</option>
                                                                            <option value="S" <?php /*=($TYPE == 'S')?'selected':''*/ ?>>Supervisor</option>
                                                                        </select>
                                                                    </div>
                                                                </div>-->
                                                                    <div class="col-md-2">
                                                                        <label class="form-label">Roles<span class="text-danger">*</span></label>
                                                                        <div class="col-md-12 multiselect-box">
                                                                            <select class="multi_sumo_select_roles" name="PK_ROLES[]" id="PK_ROLES" onchange="showServiceProviderTabs(this)" required multiple>
                                                                                <?php
                                                                                $row = $db->Execute("SELECT PK_ROLES, ROLES FROM DOA_ROLES WHERE ACTIVE='1' " . $user_role_condition . " ORDER BY SORT_ORDER");
                                                                                while (!$row->EOF) { ?>
                                                                                    <option value="<?php echo $row->fields['PK_ROLES']; ?>" <?= in_array($row->fields['PK_ROLES'], $selected_roles) ? "selected" : "" ?>><?= $row->fields['ROLES'] ?></option>
                                                                                <?php $row->MoveNext();
                                                                                } ?>
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="row">
                                                                    <div class="col-3">
                                                                        <div class="form-group">
                                                                            <label class="form-label">Phone<span class="text-danger">*</span></label>
                                                                            <div class="col-md-12">
                                                                                <input type="text" id="PHONE" name="PHONE" class="form-control" placeholder="Enter Phone Number" value="<?php echo $PHONE ?>" required>
                                                                                <div id="phone_result"></div>
                                                                            </div>
                                                                            <span id="lblError" style="color: red"></span>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-3">
                                                                        <div class="form-group">
                                                                            <label class="form-label">Email<span class="text-danger">*</span></label>
                                                                            <div class="col-md-12">
                                                                                <input type="email" id="EMAIL_ID" name="EMAIL_ID" class="form-control" placeholder="Enter Email Address" value="<?= $EMAIL_ID ?>" required>
                                                                                <div id="email_result"></div>
                                                                            </div>
                                                                            <span id="lblError" style="color: red"></span>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-2">
                                                                        <label class="col-md-12"><input type="checkbox" id="CREATE_LOGIN" name="CREATE_LOGIN" class="form-check-inline" <?= ($CREATE_LOGIN == 1) ? 'checked' : '' ?> style="margin-top: 30px;" onchange="createLogin(this);"> Create Login</label>
                                                                    </div>
                                                                    <div class="col-2">
                                                                        <label class="col-md-12"><input type="checkbox" id="APPEAR_IN_CALENDAR" name="APPEAR_IN_CALENDAR" class="form-check-inline" <?= ($APPEAR_IN_CALENDAR == 1) ? 'checked' : '' ?> style="margin-top: 30px;"> Appear In Calendar</label>
                                                                    </div>
                                                                    <div id="display_order" class="col-2">
                                                                        <div class="form-group">
                                                                            <label class="form-label">Display Order</label>
                                                                            <div class="col-md-12">
                                                                                <input type="text" id="DISPLAY_ORDER" name="DISPLAY_ORDER" class="form-control" placeholder="Enter Display Order" value="<?= $DISPLAY_ORDER ?>">
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="row">
                                                                    <div class="col-md-3">
                                                                        <div class="form-group">
                                                                            <label class="form-label">Arthur Murray ID</label>
                                                                            <div class="col-md-12">
                                                                                <input type="text" id="ARTHUR_MURRAY_ID" name="ARTHUR_MURRAY_ID" class="form-control" placeholder="Arthur Murray ID" value="<?= $ARTHUR_MURRAY_ID ?>">
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-3">
                                                                        <div class="form-group">
                                                                            <label class="form-label">Gender</label>
                                                                            <select class="form-control" id="GENDER" name="GENDER">
                                                                                <option>Select Gender</option>
                                                                                <option value="Male" <?php if ($GENDER == "Male") echo 'selected = "selected"'; ?>>Male</option>
                                                                                <option value="Female" <?php if ($GENDER == "Female") echo 'selected = "selected"'; ?>>Female</option>
                                                                                <option value="Other" <?php if ($GENDER == "Other") echo 'selected = "selected"'; ?>>Other</option>
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <div class="form-group">
                                                                            <label class="form-label">Date of Birth</label>
                                                                            <input type="text" class="form-control datepicker-past" id="DOB" name="DOB" value="<?= ($DOB) ? date('m/d/Y', strtotime($DOB)) : '' ?>">
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="row">
                                                                    <div class="col-6">
                                                                        <div class="form-group">
                                                                            <label class="col-md-12">Address</label>
                                                                            <div class="col-md-12">
                                                                                <input type="text" id="ADDRESS" name="ADDRESS" class="form-control" placeholder="Enter Address" value="<?php echo $ADDRESS ?>">
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-6">
                                                                        <div class="form-group">
                                                                            <label class="col-md-12">Apt/Ste</label>
                                                                            <div class="col-md-12">
                                                                                <input type="text" id="ADDRESS_1" name="ADDRESS_1" class="form-control" placeholder="Enter Address" value="<?php echo $ADDRESS_1 ?>">

                                                                            </div>
                                                                        </div>

                                                                    </div>
                                                                </div>

                                                                <div class="row">
                                                                    <div class="col-6">
                                                                        <div class="form-group">
                                                                            <label class="col-md-12">Country<span class="text-danger">*</span></label>
                                                                            <div class="col-md-12">
                                                                                <div class="col-sm-12">
                                                                                    <select class="form-control" name="PK_COUNTRY" id="PK_COUNTRY" onChange="fetch_state(this.value)" required>
                                                                                        <option>Select Country</option>
                                                                                        <?php
                                                                                        $row = $db->Execute("SELECT PK_COUNTRY,COUNTRY_NAME FROM DOA_COUNTRY WHERE ACTIVE = 1 ORDER BY PK_COUNTRY");
                                                                                        while (!$row->EOF) { ?>
                                                                                            <option value="<?php echo $row->fields['PK_COUNTRY']; ?>" <?= ($row->fields['PK_COUNTRY'] == $PK_COUNTRY) ? "selected" : "" ?>><?= $row->fields['COUNTRY_NAME'] ?></option>
                                                                                        <?php $row->MoveNext();
                                                                                        } ?>
                                                                                    </select>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                    <div class="col-6">
                                                                        <div class="form-group">
                                                                            <label class="col-md-12">State<span class="text-danger">*</span></label>
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
                                                                                <input type="text" id="CITY" name="CITY" class="form-control" placeholder="Enter your city" value="<?php echo $CITY ?>">
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-6">
                                                                        <div class="form-group">
                                                                            <label class="col-md-12">Postal / Zip Code</label>
                                                                            <div class="col-md-12">
                                                                                <input type="text" id="ZIP" name="ZIP" class="form-control" placeholder="Enter Postal / Zip Code" value="<?php echo $ZIP ?>">
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="row">
                                                                    <div class="col-6">
                                                                        <label class="form-label">Location</label>
                                                                        <div class="col-md-12 multiselect-box">
                                                                            <select class="multi_sumo_select_location" name="PK_USER_LOCATION[]" id="PK_LOCATION_MULTIPLE" multiple>
                                                                                <?php
                                                                                $selected_location = [];
                                                                                if (!empty($_GET['id'])) {
                                                                                    $selected_location_row = $db->Execute("SELECT `PK_LOCATION` FROM `DOA_USER_LOCATION` WHERE `PK_USER` = '$_GET[id]'");
                                                                                    while (!$selected_location_row->EOF) {
                                                                                        $selected_location[] = $selected_location_row->fields['PK_LOCATION'];
                                                                                        $selected_location_row->MoveNext();
                                                                                    }
                                                                                }
                                                                                $row = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME FROM DOA_LOCATION WHERE ACTIVE = 1 AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
                                                                                while (!$row->EOF) { ?>
                                                                                    <option value="<?php echo $row->fields['PK_LOCATION']; ?>" <?= in_array($row->fields['PK_LOCATION'], $selected_location) ? "selected" : "" ?>><?= $row->fields['LOCATION_NAME'] ?></option>
                                                                                <?php $row->MoveNext();
                                                                                } ?>
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="row">
                                                                    <div class="col-12">
                                                                        <div class="form-group">
                                                                            <label class="col-md-12">Remarks</label>
                                                                            <div class="col-md-12">
                                                                                <textarea class="form-control" rows="3" id="NOTES" name="NOTES"><?php echo $NOTES ?></textarea>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="row">
                                                                    <div class="col-6">
                                                                        <div class="form-group">
                                                                            <label class="col-md-12">Users / Employees / Service Providers Photo</label>
                                                                            <div class="col-md-12">
                                                                                <input type="file" name="USER_IMAGE" id="USER_IMAGE" class="form-control">
                                                                            </div>
                                                                        </div>
                                                                        <div class="form-group">
                                                                            <?php if ($USER_IMAGE != '') { ?><div style="width: 120px;height: 120px;margin-top: 25px;"><a class="fancybox" href="<?php echo $USER_IMAGE; ?>" data-fancybox-group="gallery"><img src="<?php echo $USER_IMAGE; ?>" style="width:120px; height:120px" /></a></div><?php } ?>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <?php if (!empty($_GET['id'])) { ?>
                                                                    <div class="row <?= ($INACTIVE_BY_ADMIN == 1) ? 'div_inactive' : '' ?>" style="margin-bottom: 15px; margin-top: 15px;">
                                                                        <div class="col-md-1">
                                                                            <label class="form-label">Active : </label>
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <label><input type="radio" name="ACTIVE" id="ACTIVE" value="1" <?php if ($ACTIVE == 1) echo 'checked="checked"'; ?> />&nbsp;Yes</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                                            <label><input type="radio" name="ACTIVE" id="ACTIVE" value="0" <?php if ($ACTIVE == 0) echo 'checked="checked"'; ?> />&nbsp;No</label>
                                                                        </div>
                                                                    </div>
                                                                <?php } ?>
                                                            </div>
                                                            <div class="form-group">
                                                                <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white"><?= empty($_GET['id']) ? 'Continue' : 'Save' ?></button>
                                                                <button type="button" id="cancel_button" class="btn btn-inverse waves-effect waves-light">Cancel</button>
                                                            </div>
                                                        </form>
                                                    </div>

                                                    <div class="tab-pane" id="login" role="tabpanel">
                                                        <form class="form-material form-horizontal" id="login_form">
                                                            <input type="hidden" name="FUNCTION_NAME" value="saveLoginData">
                                                            <input type="hidden" class="PK_USER" name="PK_USER" value="<?= $PK_USER ?>">
                                                            <input type="hidden" class="TYPE" name="TYPE" value="1">
                                                            <div class="p-20">
                                                                <div class="row">
                                                                    <div class="col-6">
                                                                        <div class="form-group">
                                                                            <label class="col-md-12">User Name<span class="text-danger">*</span></label>
                                                                            <div class="col-md-12">
                                                                                <input type="text" id="USER_NAME" name="USER_NAME" class="form-control" placeholder="Enter User Name" onkeyup="ValidateUsername()" value="<?= $USER_NAME ?>" required>
                                                                                <div id="uname_result"></div>
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
                                                                        <label><input type="radio" name="CAN_EDIT_ENROLLMENT" id="CAN_EDIT_ENROLLMENT" value="1" <?php if ($CAN_EDIT_ENROLLMENT == 1) echo 'checked="checked"'; ?> />&nbsp;Yes</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                                        <label><input type="radio" name="CAN_EDIT_ENROLLMENT" id="CAN_EDIT_ENROLLMENT" value="0" <?php if ($CAN_EDIT_ENROLLMENT == 0) echo 'checked="checked"'; ?> />&nbsp;No</label>
                                                                    </div>
                                                                </div>

                                                                <div class="row" style="margin-top: 10px;">
                                                                    <div class="col-md-3">
                                                                        <div class="col-md-12 form-group m-b-40 custom-control custom-checkbox form-group">
                                                                            <input type="checkbox" class="custom-control-input" id="TICKET_SYSTEM_ACCESS" name="TICKET_SYSTEM_ACCESS" value="1" <?php if ($TICKET_SYSTEM_ACCESS == 1) echo "checked"; ?>>
                                                                            <label class="custom-control-label" for="TICKET_SYSTEM_ACCESS">Can Create Support Tickets</label>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <?php if (empty($_GET['id']) || $PASSWORD == '') { ?>
                                                                    <div class="row">
                                                                        <div class="col-6">
                                                                            <div class="form-group">
                                                                                <label class="col-md-12">Password</label>
                                                                                <div class="col-md-12">
                                                                                    <input type="text" class="form-control" placeholder="Password" aria-label="Password" aria-describedby="basic-addon3" name="PASSWORD" id="PASSWORD" onkeyup="isGood(this.value)">
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-6">
                                                                            <div class="form-group">
                                                                                <label class="col-md-12">Confirm Password</label>
                                                                                <div class="col-md-12">
                                                                                    <input type="text" class="form-control" placeholder="Confirm Password" aria-label="Password" aria-describedby="basic-addon3" name="CONFIRM_PASSWORD" id="CONFIRM_PASSWORD" onkeyup="isGood(this.value)">
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <b id="password_error" style="color: red;"></b>
                                                                    <!--<div class="row">
                                                                    <div class="col-12">
                                                                        <span style="color: orange;">Note  : Password Must contain at least one number and one uppercase and lowercase letter, and at least 8 or more characters</span>
                                                                    </div>
                                                                </div>-->
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
                                                                            <!--<div class="col-3">
                                                                            <div class="form-group">
                                                                                <label class="form-label">Old Password</label>
                                                                                <input type="hidden" name="SAVED_OLD_PASSWORD" id="SAVED_OLD_PASSWORD" value="<?php /*=$PASSWORD*/ ?>">
                                                                                <input type="password" name="OLD_PASSWORD" id="OLD_PASSWORD" class="form-control">
                                                                            </div>
                                                                        </div>-->
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
                                                                <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white"><?= empty($_GET['id']) ? 'Continue' : 'Save' ?></button>
                                                                <?php if (!empty($_GET['id']) && $PASSWORD != '') { ?>
                                                                    <a class="btn btn-info waves-effect waves-light m-r-10 text-white" onclick="$('#change_password_div').slideToggle();">Change Password</a>
                                                                <?php } ?>
                                                                <button type="button" id="cancel_button" class="btn btn-inverse waves-effect waves-light">Cancel</button>
                                                            </div>
                                                        </form>
                                                    </div>

                                                    <div class="tab-pane card-body" id="rates" role="tabpanel">
                                                        <h4 class="card-title">Engagement Terms</h4>
                                                        <form id="engagement_form">
                                                            <input type="hidden" name="FUNCTION_NAME" value="saveEngagementData">
                                                            <input type="hidden" class="PK_USER" name="PK_USER" value="<?= $PK_USER ?>">
                                                            <input type="hidden" class="TYPE" name="TYPE" value="3">
                                                            <div class="p-20">
                                                                <div class="row">
                                                                    <?php
                                                                    $i = 0;
                                                                    if (!empty($_GET['id'])) {
                                                                        $row = $db->Execute("SELECT DOA_RATE_TYPE.PK_RATE_TYPE, DOA_RATE_TYPE.RATE_NAME, DOA_RATE_TYPE.PRICE_TYPE, DOA_USER_RATE.RATE, DOA_USER_RATE.ACTIVE FROM DOA_RATE_TYPE LEFT JOIN $account_database.DOA_USER_RATE AS DOA_USER_RATE ON DOA_RATE_TYPE.PK_RATE_TYPE = DOA_USER_RATE.PK_RATE_TYPE WHERE DOA_RATE_TYPE.ACTIVE = 1 AND DOA_USER_RATE.PK_USER = '$_GET[id]' ORDER BY DOA_RATE_TYPE.PK_RATE_TYPE ASC");
                                                                        if ($row->RecordCount() > 0) {
                                                                            while (!$row->EOF) { ?>
                                                                                <div class="col-12">
                                                                                    <div class="row form-group" style="margin-bottom: 10px;">
                                                                                        <div class="col-2" style="margin-top: 10px;">
                                                                                            <label class="form-label" for="<?= $row->fields['PK_RATE_TYPE'] ?>"><?= $row->fields['RATE_NAME'] ?></label>
                                                                                        </div>
                                                                                        <div class="col-1" style="width: 4.5%; margin-top: 7px;">
                                                                                            <input type="hidden" name="PK_RATE_TYPE[]" value="<?= $row->fields['PK_RATE_TYPE'] ?>">
                                                                                            <input type="checkbox" class="engagement_terms" name="PK_RATE_TYPE_ACTIVE[<?= $i ?>]" id="<?= $row->fields['PK_RATE_TYPE'] ?>" value="1" <?= (is_null($row->fields['ACTIVE']) || $row->fields['ACTIVE'] == 0) ? '' : 'checked' ?>>
                                                                                        </div>
                                                                                        <div class="col-4" style="*display: <?= (is_null($row->fields['ACTIVE']) || $row->fields['ACTIVE'] == 0) ? 'none' : '' ?>;">
                                                                                            <div class="col-md-6">
                                                                                                <div class="input-group">
                                                                                                    <?php if ($row->fields['PRICE_TYPE'] == 1) { ?>
                                                                                                        <span class="input-group-text"><?= $currency ?></span>
                                                                                                    <?php } else { ?>
                                                                                                        <span class="input-group-text">%</span>
                                                                                                    <?php } ?>
                                                                                                    <input type="text" class="form-control" oninput="setFormat(this)" name="RATE[]" value="<?= $row->fields['RATE'] ?>" style="text-align: right; width: 25%;">
                                                                                                </div>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            <?php
                                                                                $i++;
                                                                                $row->MoveNext();
                                                                            }
                                                                        } else {
                                                                            $row = $db->Execute("SELECT * FROM DOA_RATE_TYPE WHERE ACTIVE = 1 ORDER BY PK_RATE_TYPE ASC");
                                                                            while (!$row->EOF) { ?>
                                                                                <div class="col-12">
                                                                                    <div class="row form-group" style="margin-bottom: 10px;">
                                                                                        <div class="col-2" style="margin-top: 10px;">
                                                                                            <label class="form-label" for="<?= $row->fields['PK_RATE_TYPE'] ?>"><?= $row->fields['RATE_NAME'] ?></label>
                                                                                        </div>
                                                                                        <div class="col-1" style="width: 4.5%; margin-top: 7px;">
                                                                                            <input type="hidden" name="PK_RATE_TYPE[]" value="<?= $row->fields['PK_RATE_TYPE'] ?>">
                                                                                            <input type="checkbox" class="engagement_terms" oninput="setFormat(this)" name="PK_RATE_TYPE_ACTIVE[<?= $i ?>]" id="<?= $row->fields['PK_RATE_TYPE'] ?>" value="1">
                                                                                        </div>
                                                                                        <div class="col-4" style="display: <?= (is_null($row->fields['ACTIVE']) || $row->fields['ACTIVE'] == 0) ? 'none' : '' ?>;">
                                                                                            <div class="col-md-6">
                                                                                                <div class="input-group">
                                                                                                    <?php if ($row->fields['PRICE_TYPE'] == 1) { ?>
                                                                                                        <span class="input-group-text"><?= $currency ?></span>
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
                                                                        }
                                                                    } else {
                                                                        $row = $db->Execute("SELECT * FROM DOA_RATE_TYPE WHERE ACTIVE = 1 ORDER BY PK_RATE_TYPE ASC");
                                                                        while (!$row->EOF) { ?>
                                                                            <div class="col-12">
                                                                                <div class="row form-group" style="margin-bottom: 10px;">
                                                                                    <div class="col-2" style="margin-top: 10px;">
                                                                                        <label class="form-label" for="<?= $row->fields['PK_RATE_TYPE'] ?>"><?= $row->fields['RATE_NAME'] ?></label>
                                                                                    </div>
                                                                                    <div class="col-1" style="width: 4.5%; margin-top: 7px;">
                                                                                        <input type="hidden" name="PK_RATE_TYPE[]" value="<?= $row->fields['PK_RATE_TYPE'] ?>">
                                                                                        <input type="checkbox" class="engagement_terms" oninput="setFormat(this)" name="PK_RATE_TYPE_ACTIVE[<?= $i ?>]" id="<?= $row->fields['PK_RATE_TYPE'] ?>" value="1">
                                                                                    </div>
                                                                                    <div class="col-4" style="display: <?= (is_null($row->fields['ACTIVE']) || $row->fields['ACTIVE'] == 0) ? 'none' : '' ?>;">
                                                                                        <div class="col-md-6">
                                                                                            <div class="input-group">
                                                                                                <?php if ($row->fields['PRICE_TYPE'] == 1) { ?>
                                                                                                    <span class="input-group-text"><?= $currency ?></span>
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
                                                                if (!empty($_GET['id'])) {
                                                                    $code_commission_data = $db_account->Execute("SELECT * FROM DOA_SERVICE_COMMISSION WHERE PK_USER = " . $_GET['id']);
                                                                    while (!$code_commission_data->EOF) { ?>
                                                                        <div class="row m-t-10">
                                                                            <div class="col-md-3">
                                                                                <select class="form-control" name="PK_SERVICE_MASTER[]">
                                                                                    <option value="">Select Service</option>
                                                                                    <?php
                                                                                    $row = $db_account->Execute("SELECT * FROM DOA_SERVICE_MASTER WHERE ACTIVE = 1 AND IS_DELETED = 0");
                                                                                    while (!$row->EOF) { ?>
                                                                                        <option value="<?php echo $row->fields['PK_SERVICE_MASTER']; ?>" <?= ($code_commission_data->fields['PK_SERVICE_MASTER'] == $row->fields['PK_SERVICE_MASTER']) ? 'selected' : '' ?>><?= $row->fields['SERVICE_NAME'] ?></option>
                                                                                    <?php $row->MoveNext();
                                                                                    } ?>
                                                                                </select>
                                                                            </div>
                                                                            <div class="col-md-3">
                                                                                <input type="text" class="form-control" name="COMMISSION_AMOUNT[]" value="<?= $code_commission_data->fields['COMMISSION_AMOUNT'] ?>">
                                                                            </div>
                                                                            <div class="col-2 m-t-5">
                                                                                <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                                                            </div>
                                                                        </div>
                                                                    <?php $code_commission_data->MoveNext();
                                                                    } ?>
                                                                <?php } else { ?>
                                                                    <div class="row m-t-10">
                                                                        <div class="col-md-3">
                                                                            <select class="form-control" name="PK_SERVICE_MASTER[]">
                                                                                <option value="">Select Service</option>
                                                                                <?php
                                                                                $row = $db_account->Execute("SELECT * FROM DOA_SERVICE_MASTER WHERE ACTIVE = 1 AND IS_DELETED = 0");
                                                                                while (!$row->EOF) { ?>
                                                                                    <option value="<?php echo $row->fields['PK_SERVICE_MASTER']; ?>"><?= $row->fields['SERVICE_NAME'] ?></option>
                                                                                <?php $row->MoveNext();
                                                                                } ?>
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
                                                                <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white"><?= empty($_GET['id']) ? 'Continue' : 'Save' ?></button>
                                                                <button type="button" id="cancel_button" class="btn btn-inverse waves-effect waves-light">Cancel</button>
                                                            </div>
                                                        </form>
                                                    </div>

                                                    <div class="tab-pane card-body" id="service" role="tabpanel">
                                                        <form id="location_hour_form">
                                                            <input type="hidden" name="FUNCTION_NAME" value="saveLocationHourData">
                                                            <input type="hidden" class="PK_USER" name="PK_USER" value="<?= $PK_USER ?>">
                                                            <input type="hidden" class="TYPE" name="TYPE" value="3">
                                                            <div class="p-20" id="location_details_div">

                                                            </div>
                                                            <div class="form-group">
                                                                <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white"><?= empty($_GET['id']) ? 'Continue' : 'Save' ?></button>
                                                                <button type="button" id="cancel_button" class="btn btn-inverse waves-effect waves-light">Cancel</button>
                                                            </div>
                                                        </form>
                                                    </div>

                                                    <div class="tab-pane" id="documents" role="tabpanel">
                                                        <form id="document_form">
                                                            <input type="hidden" name="FUNCTION_NAME" value="saveUserDocumentData">
                                                            <input type="hidden" class="PK_USER" name="PK_USER" value="<?= $PK_USER ?>">
                                                            <input type="hidden" class="PK_CUSTOMER_DETAILS" name="PK_CUSTOMER_DETAILS" value="<?= $PK_CUSTOMER_DETAILS ?>">
                                                            <input type="hidden" class="TYPE" name="TYPE" value="2">
                                                            <div>
                                                                <div class="card-body" id="append_user_document">
                                                                    <?php
                                                                    if (!empty($_GET['id'])) {
                                                                        $user_doc_count = 0;
                                                                        $row = $db_account->Execute("SELECT * FROM DOA_USER_DOCUMENT WHERE PK_USER = '$PK_USER'");
                                                                        while (!$row->EOF) { ?>
                                                                            <div class="row">
                                                                                <div class="col-5">
                                                                                    <div class="form-group">
                                                                                        <label class="form-label">Document Name</label>
                                                                                        <input type="text" name="DOCUMENT_NAME[]" class="form-control" placeholder="Enter Document Name" value="<?= $row->fields['DOCUMENT_NAME'] ?>">
                                                                                    </div>
                                                                                </div>
                                                                                <div class="col-5">
                                                                                    <div class="form-group">
                                                                                        <label class="form-label">Document File</label>
                                                                                        <input type="file" name="FILE_PATH[]" class="form-control">
                                                                                        <a target="_blank" href="<?= $row->fields['FILE_PATH'] ?>">View</a>
                                                                                        <input type="hidden" name="FILE_PATH_URL[]" value="<?= $row->fields['FILE_PATH'] ?>">
                                                                                    </div>
                                                                                </div>
                                                                                <div class="col-2">
                                                                                    <div class="form-group" style="margin-top: 30px;">
                                                                                        <a href="javascript:;" class="btn btn-danger waves-effect waves-light m-r-10 text-white" onclick="removeUserDocument(this);"><i class="ti-trash"></i></a>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        <?php $row->MoveNext();
                                                                            $user_doc_count++;
                                                                        } ?>
                                                                    <?php } else {
                                                                        $user_doc_count = 1; ?>
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
                                                                <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white"><?= empty($_GET['id']) ? 'Continue' : 'Save' ?></button>
                                                                <button type="button" id="cancel_button" class="btn btn-inverse waves-effect waves-light">Cancel</button>
                                                            </div>
                                                        </form>
                                                    </div>

                                                    <div class="tab-pane" id="comments" role="tabpanel">
                                                        <div class="p-20">
                                                            <a class="btn btn-info d-none d-lg-block m-15 text-white" href="javascript:;" onclick="createUserComment();" style="width: 120px; float: right;"><i class="fa fa-plus-circle"></i> Create New</a>
                                                            <table id="myTable" class="table table-striped border">
                                                                <thead>
                                                                    <tr>
                                                                        <th>Commented Date</th>
                                                                        <th>Commented User</th>
                                                                        <th>Comment</th>
                                                                        <th>Actions</th>
                                                                    </tr>
                                                                </thead>

                                                                <tbody>
                                                                    <?php
                                                                    $comment_data = $db_account->Execute("SELECT DOA_COMMENT.PK_COMMENT, DOA_COMMENT.COMMENT, DOA_COMMENT.COMMENT_DATE, DOA_COMMENT.ACTIVE, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS FULL_NAME FROM `DOA_COMMENT` INNER JOIN DOA_USERS ON DOA_COMMENT.BY_PK_USER = DOA_USERS.PK_USER WHERE `FOR_PK_USER` = " . $PK_USER);
                                                                    $i = 1;
                                                                    while (!$comment_data->EOF) { ?>
                                                                        <tr>
                                                                            <td onclick="editComment(<?= $comment_data->fields['PK_COMMENT'] ?>);"><?= date('m/d/Y', strtotime($comment_data->fields['COMMENT_DATE'])) ?></td>
                                                                            <td onclick="editComment(<?= $comment_data->fields['PK_COMMENT'] ?>);"><?= $comment_data->fields['FULL_NAME'] ?></td>
                                                                            <td onclick="editComment(<?= $comment_data->fields['PK_COMMENT'] ?>);"><?= $comment_data->fields['COMMENT'] ?></td>
                                                                            <td>
                                                                                <a href="javascript:;" onclick="editComment(<?= $comment_data->fields['PK_COMMENT'] ?>);"><i class="ti-pencil" style="font-size: 22px;"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                                                <a href="javascript:;" onclick='javascript:deleteComment(<?= $comment_data->fields['PK_COMMENT'] ?>);return false;'><i class="ti-trash" style="font-size: 22px;"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                                                <?php if ($comment_data->fields['ACTIVE'] == 1) { ?>
                                                                                    <span class="active-box-green"></span>
                                                                                <?php } else { ?>
                                                                                    <span class="active-box-red"></span>
                                                                                <?php } ?>
                                                                            </td>
                                                                        </tr>
                                                                    <?php $comment_data->MoveNext();
                                                                        $i++;
                                                                    } ?>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>

                                                    <!--Comment Model-->
                                                    <div id="commentModel" class="modal">
                                                        <!-- Modal content -->
                                                        <div class="modal-content" style="width: 50%;">
                                                            <span class="close close_comment_model" style="margin-left: 96%;">&times;</span>
                                                            <div class="card">
                                                                <div class="card-body">
                                                                    <h4><b id="comment_header">Add Comment</b></h4>
                                                                    <form id="comment_add_edit_form" role="form" action="" method="post">
                                                                        <input type="hidden" name="FUNCTION_NAME" value="saveCommentData">
                                                                        <input type="hidden" class="PK_USER" name="PK_USER" value="<?= $PK_USER ?>">
                                                                        <input type="hidden" name="PK_COMMENT" id="PK_COMMENT" value="0">
                                                                        <div class="p-20">
                                                                            <div class="form-group">
                                                                                <label class="form-label">Comments</label>
                                                                                <textarea class="form-control" rows="10" name="COMMENT" id="COMMENT" required></textarea>
                                                                            </div>

                                                                            <div class="form-group">
                                                                                <label class="form-label">Date</label>
                                                                                <input type="date" class="form-control" name="COMMENT_DATE" id="COMMENT_DATE" required>
                                                                            </div>

                                                                            <div class="form-group" id="comment_active" style="display: none;">
                                                                                <label class="form-label">Active</label>
                                                                                <div>
                                                                                    <label><input type="radio" id="COMMENT_ACTIVE_1" name="ACTIVE" value="1">&nbsp;&nbsp;&nbsp;Yes</label>&nbsp;&nbsp;&nbsp;&nbsp;
                                                                                    <label><input type="radio" id="COMMENT_ACTIVE_0" name="ACTIVE" value="0">&nbsp;&nbsp;&nbsp;No</label>
                                                                                </div>
                                                                            </div>

                                                                            <div class="form-group">
                                                                                <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white" style="float: right;">Submit</button>
                                                                            </div>
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
                </div>
            </div>
        </div>

        <?php require_once('../includes/footer.php'); ?>
        <script src="../assets/sumoselect/jquery.sumoselect.min.js"></script>
        <script>
            $('.datepicker-past').datepicker({
                format: 'mm/dd/yyyy',
                maxDate: 0
            });

            $('#NAME').SumoSelect({
                placeholder: 'Select User',
                search: true,
                searchText: 'Search...'
            });
            $('.multi_sumo_select_location').SumoSelect({
                placeholder: 'Select Location',
                selectAll: true
            });
            $('.multi_sumo_select_roles').SumoSelect({
                placeholder: 'Select Roles',
                selectAll: true
            });
            $('.multi_sumo_select_services').SumoSelect({
                placeholder: 'Select Services',
                selectAll: true
            });

            function editpage(param) {
                var id = $(param).val();
                window.location.href = "user.php?id=" + id;

            }

            $(document).ready(function() {
                fetch_state(<?php echo $PK_COUNTRY; ?>);
            });

            function fetch_state(PK_COUNTRY) {
                jQuery(document).ready(function() {
                    let data = "PK_COUNTRY=" + PK_COUNTRY + "&PK_STATES=<?= $PK_STATES; ?>";
                    let value = $.ajax({
                        url: "ajax/state.php",
                        type: "POST",
                        data: data,
                        async: false,
                        cache: false,
                        success: function(result) {
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

            $(document).on('focus', '.time-picker', function() {
                let minTime = $(this).closest('.form-group').find('.minTime').val();
                let maxTime = $(this).closest('.form-group').find('.maxTime').val();
                $(this).timepicker({
                    timeFormat: 'hh:mm p',
                    interval: 30,
                    dynamic: false,
                    dropdown: true,
                    scrollbar: true,
                    minTime: minTime,
                    maxTime: maxTime
                });
            });

            function closeThisDay(param) {
                if ($(param).is(':checked')) {
                    $(param).closest('.row').find('.time-input').val('');
                    //$(param).closest('.row').find('.time-input').css('pointer-events', 'none');
                    $(param).closest('.row').find('.time-input').each(function() {
                        this.style.cssText = 'background-color: #80808080 !important; pointer-events: none !important;';
                    });
                } else {
                    $(param).closest('.row').find('.time-input').css('pointer-events', '');
                    $(param).closest('.row').find('.time-input').css('background-color', '');
                }
            }
        </script>
        <script>
            let PK_USER = parseInt(<?= empty($_GET['id']) ? 0 : $_GET['id'] ?>);
            let NEW_USER = parseInt(<?= empty($_GET['id']) ? true : false ?>);

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

            $(document).on('click', '#cancel_button', function() {
                window.location.href = 'all_users.php';
            });

            function setFormat(param) {
                if ($(param).val() != "") {
                    $(param).val(parseFloat($(param).val().replace(/,/g, ""))
                        .toString()
                        .replace(/\B(?=(\d{3})+(?!\d))/g, ","));
                }
            }

            function createLogin(param) {
                if ($(param).is(':checked')) {
                    $('#login_info_tab').show();
                    $('#phone_label').text('*');
                    $('#PHONE').prop('required', true);
                    $('#email_label').text('*');
                    $('#EMAIL_ID').prop('required', true);
                    $('#submit_button').hide();
                    $('#next_button_interest').hide();
                    $('#next_button').show();
                } else {
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

            /*function showServiceProviderTabs(param) {
                let pk_role = $(param).val();
                if (pk_role.indexOf('5') !== -1){
                    $('#rates_tab').show();
                    $('#service_tab').show();
                    $('#comment_tab').show();
                    $('#display_order').show();
                }else {
                    $('#rates_tab').hide();
                    $('#service_tab').hide();
                    $('#comment_tab').hide();
                    $('#display_order').hide();
                }
            }*/

            let counter = parseInt(<?= $user_doc_count ?>);

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

            $(document).on('submit', '#profile_form', function(event) {
                event.preventDefault();
                let PK_USER = $('.PK_USER').val();
                const PHONE = $('#PHONE').val().trim();
                const EMAIL_ID = $('#EMAIL_ID').val().trim();
                if (PHONE != '') {
                    $.ajax({
                        url: 'ajax/username_checker.php',
                        type: 'post',
                        data: {
                            PHONE: PHONE
                        },
                        success: function(response) {
                            if (response && PK_USER == 0) {
                                $('#phone_result').html(response);
                            } else {
                                $('#phone_result').html('');
                                if (EMAIL_ID != '') {
                                    $.ajax({
                                        url: 'ajax/username_checker.php',
                                        type: 'post',
                                        data: {
                                            EMAIL_ID: EMAIL_ID
                                        },
                                        success: function(response) {
                                            if (response && PK_USER == 0) {
                                                $('#email_result').html(response);
                                            } else {
                                                $('#email_result').html('');
                                                let form_data = new FormData($('#profile_form')[0]); //$('#profile_form').serialize();
                                                $.ajax({
                                                    url: "ajax/AjaxFunctions.php",
                                                    type: 'POST',
                                                    data: form_data,
                                                    processData: false,
                                                    contentType: false,
                                                    dataType: 'JSON',
                                                    success: function(data) {
                                                        $('.PK_USER').val(data.PK_USER);
                                                        $('.PK_CUSTOMER_DETAILS').val(data.PK_CUSTOMER_DETAILS);
                                                        if (PK_USER == 0 || NEW_USER) {
                                                            if ($('#CREATE_LOGIN').is(':checked')) {
                                                                $('#login_info_tab_link')[0].click();
                                                            } else {
                                                                if ($('#PK_ROLES').val().indexOf('5') !== -1) {
                                                                    $('#rates_tab_link')[0].click();
                                                                } else {
                                                                    $('#document_tab_link')[0].click();
                                                                }
                                                            }
                                                        } else {
                                                            window.location.href = 'all_users.php';
                                                        }
                                                    }
                                                });
                                            }
                                        }
                                    });
                                }
                            }
                        }
                    });
                }
            });

            /*const phone = document.getElementById("PHONE");
            if (phone.value !== "") {
                phone.setAttribute("readonly", "readonly");
            }

            const email_id = document.getElementById("EMAIL_ID");
            if (email_id.value !== "") {
                email_id.setAttribute("readonly", "readonly");
            }*/

            function goToLoginTab() {
                let PK_USER = $('.PK_USER').val();
                if (!PK_USER) {
                    alert('Please fill up the profile and click next.');
                    $('#profile_tab_link')[0].click();
                }
            }

            $(document).on('submit', '#login_form', function(event) {
                event.preventDefault();
                let PK_USER = $('.PK_USER').val();
                let PASSWORD = $('#PASSWORD').val();
                let CONFIRM_PASSWORD = $('#CONFIRM_PASSWORD').val();
                if (PASSWORD === CONFIRM_PASSWORD) {
                    let form_data = $('#login_form').serialize();
                    $.ajax({
                        url: "ajax/AjaxFunctions.php",
                        type: 'POST',
                        data: form_data,
                        success: function(data) {
                            if (PK_USER == 0 || NEW_USER) {
                                if ($('#PK_ROLES').val().indexOf('5') !== -1) {
                                    $('#rates_tab_link')[0].click();
                                } else {
                                    $('#document_tab_link')[0].click();
                                }
                            } else {
                                window.location.href = 'all_users.php';
                            }
                        }
                    });
                } else {
                    $('#password_error').text('Password and Confirm Password not matched');
                }
            });

            function addMoreCodeCommission() {
                $('#add_more_code_commission').append(`<div class="row m-t-15">
                                                        <div class="col-md-3">
                                                            <select class="form-control" name="PK_SERVICE_MASTER[]">
                                                                <option value="">Select Service</option>
                                                                <?php
                                                                $row = $db_account->Execute("SELECT * FROM DOA_SERVICE_MASTER WHERE ACTIVE = 1 AND IS_DELETED = 0");
                                                                while (!$row->EOF) { ?>
                                                                    <option value="<?php echo $row->fields['PK_SERVICE_MASTER']; ?>"><?= $row->fields['SERVICE_NAME'] ?></option>
                                                                <?php $row->MoveNext();
                                                                } ?>
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

            function removeThis(param) {
                $(param).closest('.row').remove();
            }

            $(document).on('submit', '#engagement_form', function(event) {
                event.preventDefault();
                let PK_USER = $('.PK_USER').val();
                let form_data = $('#engagement_form').serialize();
                $.ajax({
                    url: "ajax/AjaxFunctions.php",
                    type: 'POST',
                    data: form_data,
                    success: function(data) {
                        if (PK_USER == 0 || NEW_USER) {
                            if ($('#PK_ROLES').val().indexOf('5') !== -1) {
                                $('#service_tab_link')[0].click();
                            } else {
                                $('#document_tab_link')[0].click();
                            }
                        } else {
                            window.location.href = 'all_users.php';
                        }
                    }
                });
            });

            function getLocationHours() {
                let PK_USER = $('.PK_USER').val();
                $.ajax({
                    url: "ajax/get_location_hours.php",
                    type: 'POST',
                    data: {
                        PK_USER: PK_USER
                    },
                    success: function(data) {
                        $('#location_details_div').html(data);
                    }
                });
            }

            $(document).on('submit', '#location_hour_form', function(event) {
                event.preventDefault();
                let form_data = $('#location_hour_form').serialize();
                $.ajax({
                    url: "ajax/AjaxFunctions.php",
                    type: 'POST',
                    data: form_data,
                    success: function(data) {
                        window.location.href = 'all_users.php';
                        /*if (PK_USER == 0) {
                            if ($('#PK_ROLES').val().indexOf('5') !== -1) {
                                $('#document_tab_link')[0].click();
                            } else {
                                window.location.href='all_users.php';
                            }
                        }*/
                    }
                });
            });

            $(document).on('submit', '#document_form', function(event) {
                event.preventDefault();
                let form_data = new FormData($('#document_form')[0]); //$('#document_form').serialize();
                $.ajax({
                    url: "ajax/AjaxFunctions.php",
                    type: 'POST',
                    data: form_data,
                    processData: false,
                    contentType: false,
                    success: function(data) {
                        window.location.href = 'all_users.php';
                    }
                });
            });
        </script>

        <script>
            let comment_model = document.getElementById("commentModel");
            let comment_span = document.getElementsByClassName("close_comment_model")[0];

            function openCommentModel() {
                comment_model.style.display = "block";
            }
            comment_span.onclick = function() {
                comment_model.style.display = "none";
            }
            window.onclick = function(event) {
                if (event.target == comment_model) {
                    comment_model.style.display = "none";
                }
            }

            function createUserComment() {
                $('#comment_header').text("Add Comment");
                $('#PK_COMMENT').val(0);
                $('#COMMENT').val('');
                $('#COMMENT_DATE').val('');
                $('#comment_active').hide();
                openCommentModel();
            }

            function editComment(PK_COMMENT) {
                $.ajax({
                    url: "ajax/AjaxFunctions.php",
                    type: 'POST',
                    dataType: 'JSON',
                    data: {
                        FUNCTION_NAME: 'getEditCommentData',
                        PK_COMMENT: PK_COMMENT
                    },
                    success: function(data) {
                        $('#comment_header').text("Edit Comment");
                        $('#PK_COMMENT').val(data.fields.PK_COMMENT);
                        $('#COMMENT').val(data.fields.COMMENT);
                        $('#COMMENT_DATE').val(data.fields.COMMENT_DATE);
                        $('#COMMENT_ACTIVE_' + data.fields.ACTIVE).prop('checked', true);
                        $('#comment_active').show();
                        openCommentModel();
                    }
                });
            }

            $(document).on('submit', '#comment_add_edit_form', function(event) {
                event.preventDefault();
                let form_data = new FormData($('#comment_add_edit_form')[0]); //$('#document_form').serialize();
                $.ajax({
                    url: "ajax/AjaxFunctions.php",
                    type: 'POST',
                    data: form_data,
                    processData: false,
                    contentType: false,
                    success: function(data) {
                        window.location.href = `user.php?id=${PK_USER}&on_tab=comments`;
                    }
                });
            });

            function deleteComment(PK_COMMENT) {
                let conf = confirm("Are you sure you want to delete?");
                if (conf) {
                    $.ajax({
                        url: "ajax/AjaxFunctions.php",
                        type: 'POST',
                        data: {
                            FUNCTION_NAME: 'deleteCommentData',
                            PK_COMMENT: PK_COMMENT
                        },
                        success: function(data) {
                            window.location.href = `user.php?id=${PK_USER}&on_tab=comments`;
                        }
                    });
                }
            }
        </script>

        <script>
            $(document).ready(function() {
                $('#USER_NAME').on('blur', function() {
                    const USER_NAME = $(this).val().trim();
                    if (USER_NAME != '') {
                        $.ajax({
                            url: 'ajax/username_checker.php',
                            type: 'post',
                            data: {
                                USER_NAME: USER_NAME
                            },
                            success: function(response) {
                                $('#uname_result').html(response);
                            }
                        });
                    } else {
                        $("#uname_result").html("");
                    }
                });
            });

            $(document).on('change', '.engagement_terms', function() {
                if ($(this).attr('id') == 4 && $(this).is(':checked')) {
                    $('#5').prop('checked', false);
                }
                if ($(this).attr('id') == 5 && $(this).is(':checked')) {
                    $('#4').prop('checked', false);
                }
            });
        </script>
</body>

</html>