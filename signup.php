<?php
require_once('global/config.php');
global $db;
global $db_account;

$PK_ACCOUNT_MASTER = '';
$PK_BUSINESS_TYPE = '';
$PK_ACCOUNT_TYPE = '';
$FRANCHISE = '';
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
$USERNAME_PREFIX = '';

$PK_USER_EDIT = '';
$USER_NAME = '';
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

$START_DATE = '';
$BILLING_TYPE = '';
$AMOUNT = '';
$TOTAL_AMOUNT = '';
$NEXT_RENEWAL_DATE = '';
$STATUS = '';

$PK_PAYMENT_GATEWAY_SETTINGS = 0;
$PAYMENT_GATEWAY_TYPE = '';
$SECRET_KEY = '';
$PUBLISHABLE_KEY = '';
$ACCESS_TOKEN = '';
$SQUARE_APP_ID = '';
$SQUARE_LOCATION_ID = '';
$LOGIN_ID = '';
$TRANSACTION_KEY = '';
$AUTHORIZE_CLIENT_KEY = '';

$PK_OTHER_SETTING = 0;
$PAYMENT_REMINDER_BEFORE_DAYS = '';
$PAYMENT_FAILED_REMINDER_AFTER_DAYS = '';
?>

<!DOCTYPE html>
<html lang="en">
<?php require_once('includes/header.php'); ?>
<style>
    .SumoSelect {
        width: 100%;
    }
</style>

<body class="skin-default-dark fixed-layout">
    <div class="container-fluid">
        <div class="row page-titles">
            <div class="col-md-6 align-self-center">
                <img src="assets/images/doable_logo.png" alt="LOGO" style="height: 50px; width: auto;">
            </div>
            <div class="col-md-6" style="text-align: right">
                <h3 class="text-themecolor">Sign Up</h3>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card wizard-content">
                    <div class="card-body">
                        <!-- Nav tabs -->
                        <ul class="nav nav-tabs" role="tablist">
                            <li class="active"> <a class="nav-link active" id="home_tab_link" data-bs-toggle="tab" href="#business_profile" role="tab"><span class="hidden-sm-up"><i class="ti-home"></i></span> <span class="hidden-xs-down">Business Profile</span></a> </li>
                            <li> <a class="nav-link" id="profile_tab_link" data-bs-toggle="tab" href="#settings" role="tab"><span class="hidden-sm-up"><i class="ti-settings"></i></span> <span class="hidden-xs-down">Settings</span></a> </li>
                            <li> <a class="nav-link" id="profile_tab_link" data-bs-toggle="tab" href="#locations" role="tab"><span class="hidden-sm-up"><i class="ti-location-pin"></i></span> <span class="hidden-xs-down">Locations</span></a> </li>
                            <li> <a class="nav-link" id="profile_tab_link" data-bs-toggle="tab" href="#hours_of_operations" role="tab"><span class="hidden-sm-up"><i class="ti-time"></i></span> <span class="hidden-xs-down">Hours of Operations</span></a> </li>
                            <li> <a class="nav-link" id="profile_tab_link" data-bs-toggle="tab" href="#users" role="tab"><span class="hidden-sm-up"><i class="ti-user"></i></span> <span class="hidden-xs-down">Users</span></a> </li>
                        </ul>

                        <!-- Tab panes -->
                        <div class="tab-content tabcontent-border">
                            <!--Account Info Tab-->
                            <div class="tab-pane active" id="business_profile" role="tabpanel">

                                <form class="form-material form-horizontal" id="account_info_form">
                                    <input type="hidden" name="FUNCTION_NAME" value="saveAccountInfoData">
                                    <input type="hidden" class="PK_ACCOUNT_MASTER" name="PK_ACCOUNT_MASTER" value="<?= $PK_ACCOUNT_MASTER ?>">
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
                                                                <option value="<?php echo $row->fields['PK_BUSINESS_TYPE']; ?>" <?= ($row->fields['PK_BUSINESS_TYPE'] == $PK_BUSINESS_TYPE) ? "selected" : "" ?>><?= $row->fields['BUSINESS_TYPE'] ?></option>
                                                            <?php $row->MoveNext();
                                                            } ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-3">
                                                <div class="form-group">
                                                    <label class="col-md-12">Account Type<span class="text-danger">*</span>
                                                    </label>
                                                    <div class="col-md-12">
                                                        <?php
                                                        $row = $db->Execute("SELECT PK_ACCOUNT_TYPE,ACCOUNT_TYPE FROM DOA_ACCOUNT_TYPE WHERE ACTIVE='1' ORDER BY PK_ACCOUNT_TYPE");
                                                        while (!$row->EOF) { ?>
                                                            <input type="radio" name="PK_ACCOUNT_TYPE" id="<?= $row->fields['PK_ACCOUNT_TYPE']; ?>" value="<?= $row->fields['PK_ACCOUNT_TYPE']; ?>" <?php if ($row->fields['PK_ACCOUNT_TYPE'] == $PK_ACCOUNT_TYPE) echo 'checked'; ?> required>
                                                            <label for="<?= $row->fields['PK_ACCOUNT_TYPE']; ?>"><?= $row->fields['ACCOUNT_TYPE'] ?></label>
                                                        <?php $row->MoveNext();
                                                        } ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-3">
                                                <div class="form-group">
                                                    <label class="col-md-12">Arthur Murray Franchise ?</label>
                                                    <div class="col-md-12">
                                                        <label><input type="radio" name="FRANCHISE" id="FRANCHISE" value="1" <?php if ($FRANCHISE == 1) echo 'checked="checked"'; ?> />&nbsp;Yes</label>&nbsp;&nbsp;
                                                        <label><input type="radio" name="FRANCHISE" id="FRANCHISE" value="0" <?php if ($FRANCHISE == 0) echo 'checked="checked"'; ?> />&nbsp;No</label>
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
                                                        <input type="text" id="BUSINESS_NAME" name="BUSINESS_NAME" class="form-control" placeholder="Enter Business Name" required value="<?php echo $BUSINESS_NAME ?>">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-6">
                                                <div class="form-group">
                                                    <label class="col-md-12">Address</label>
                                                    <div class="col-md-12">
                                                        <textarea class="form-control" rows="2" id="ACCOUNT_ADDRESS" name="ACCOUNT_ADDRESS" placeholder="Enter Address"><?php echo $ACCOUNT_ADDRESS ?></textarea>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="form-group">
                                                    <label class="col-md-12">Apt/Ste</label>
                                                    <div class="col-md-12">
                                                        <textarea class="form-control" rows="2" id="ACCOUNT_ADDRESS_1" name="ACCOUNT_ADDRESS_1" placeholder="Enter Street/Apartment"><?php echo $ACCOUNT_ADDRESS_1 ?></textarea>
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
                                                                    <option value="<?php echo $row->fields['PK_COUNTRY']; ?>" <?= ($row->fields['PK_COUNTRY'] == $ACCOUNT_PK_COUNTRY) ? "selected" : "" ?>><?= $row->fields['COUNTRY_NAME'] ?></option>
                                                                <?php $row->MoveNext();
                                                                } ?>
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
                                                        <input type="text" id="ACCOUNT_CITY" name="ACCOUNT_CITY" class="form-control" placeholder="Enter City" value="<?php echo $ACCOUNT_CITY ?>">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="form-group">
                                                    <label class="col-md-12">Postal / Zip Code</span>
                                                    </label>
                                                    <div class="col-md-12">
                                                        <input type="text" id="ACCOUNT_ZIP" name="ACCOUNT_ZIP" class="form-control" placeholder="Enter Postal / Zip Code" value="<?php echo $ACCOUNT_ZIP ?>">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-6">
                                                <div class="form-group">
                                                    <label class="col-md-12">Business Phone</label>
                                                    <div class="col-md-12">
                                                        <input type="text" id="ACCOUNT_PHONE" name="ACCOUNT_PHONE" class="form-control" placeholder="Enter Business Phone No." value="<?php echo $ACCOUNT_PHONE ?>">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="form-group">
                                                    <label class="col-md-12">Business Fax</label>
                                                    <div class="col-md-12">
                                                        <input type="text" id="ACCOUNT_FAX" name="ACCOUNT_FAX" class="form-control" placeholder="Enter Business Fax" value="<?php echo $ACCOUNT_FAX; ?>">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-6">
                                                <div class="form-group">
                                                    <label class="col-md-12">Business Email<span class="text-danger">*</span></label>
                                                    <div class="col-md-12">
                                                        <input type="email" id="ACCOUNT_EMAIL" name="ACCOUNT_EMAIL" class="form-control" placeholder="Enter Business Email" required value="<?php echo $ACCOUNT_EMAIL ?>">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="form-group">
                                                    <label class="col-md-12">Website
                                                    </label>
                                                    <div class="col-md-12">
                                                        <input type="text" id="ACCOUNT_WEBSITE" name="ACCOUNT_WEBSITE" class="form-control" placeholder="Enter Website" value="<?php echo $ACCOUNT_WEBSITE ?>">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <?php if (!empty($_GET['id'])) { ?>
                                            <div class="row" style="margin-bottom: 15px; margin-top: 15px;">
                                                <div class="col-md-1">
                                                    <label class="form-label">Active : </label>
                                                </div>
                                                <div class="col-md-4">
                                                    <label><input type="radio" name="ACTIVE" id="ACTIVE" value="1" <? if ($ACTIVE == 1) echo 'checked="checked"'; ?> />&nbsp;Yes</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                    <label><input type="radio" name="ACTIVE" id="ACTIVE" value="0" <? if ($ACTIVE == 0) echo 'checked="checked"'; ?> />&nbsp;No</label>
                                                </div>
                                            </div>
                                        <? } ?>

                                        <div class="row">
                                            <div class="col-6">
                                                <div class="form-group">
                                                    <label class="col-md-12">Username Prefix
                                                    </label>
                                                    <div class="col-md-12">
                                                        <input type="text" id="USERNAME_PREFIX" name="USERNAME_PREFIX" class="form-control" placeholder="Enter Username Prefix">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">Continue</button>
                                        <button type="button" id="cancel_button" class="btn btn-inverse waves-effect waves-light">Cancel</button>
                                    </div>
                                </form>
                            </div>

                            <div class="tab-pane" id="settings" role="tabpanel">
                                <div class="row" style="margin-top: 50px;">
                                    <b class="btn btn-light" style="margin-bottom: 20px;">Twilio Setting</b>
                                    <div class="col-4">
                                        <div class="form-group">
                                            <label class="col-md-12" for="example-text">SID</label>
                                            <div class="col-md-12">
                                                <input type="text" id="SID" name="SID" class="form-control" placeholder="Enter SID">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="form-group">
                                            <label class="col-md-12" for="example-text">Token</label>
                                            <div class="col-md-12">
                                                <input type="text" id="TOKEN" name="TOKEN" class="form-control" placeholder="Enter TOKEN">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="form-group">
                                            <label class="col-md-12" for="example-text">Phone No.</label>
                                            <div class="col-md-12">
                                                <input type="text" id="PHONE_NO" name="PHONE_NO" class="form-control" placeholder="Enter Phone No.">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row" style="margin-top: 50px;">
                                    <b class="btn btn-light" style="margin-bottom: 20px;">Payment Gateway Setting</b>
                                    <input type="hidden" name="PK_PAYMENT_GATEWAY_SETTINGS">
                                    <div class="col-6">
                                        <div class="row">
                                            <div class="form-group">
                                                <label class="form-label" style="margin-bottom: 5px;">Payment Gateway</label><br>
                                                <label style="margin-right: 70px;"><input type="radio" id="PAYMENT_GATEWAY_TYPE" name="PAYMENT_GATEWAY_TYPE" class="form-check-inline" value="Stripe" onclick="showPaymentGateway(this);">Stripe</label>
                                                <label style="margin-right: 70px;"><input type="radio" id="PAYMENT_GATEWAY_TYPE" name="PAYMENT_GATEWAY_TYPE" class="form-check-inline" value="Square" onclick="showPaymentGateway(this);">Square</label>
                                                <label style="margin-right: 70px;"><input type="radio" id="PAYMENT_GATEWAY_TYPE" name="PAYMENT_GATEWAY_TYPE" class="form-check-inline" value="Authorized.net" onclick="showPaymentGateway(this);">Authorized.net</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row payment_gateway" id="stripe" style="display: <?= ($PAYMENT_GATEWAY_TYPE == 'Stripe') ? '' : 'none' ?>;">
                                        <div class="col-6">
                                            <div class="form-group">
                                                <label class="form-label">Secret Key</label>
                                                <input type="text" class="form-control" name="SECRET_KEY">
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label">Publishable Key</label>
                                                <input type="text" class="form-control" name="PUBLISHABLE_KEY">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row payment_gateway" id="square" style="display: <?= ($PAYMENT_GATEWAY_TYPE == 'Square') ? '' : 'none' ?>">
                                        <div class="col-6">
                                            <div class="form-group">
                                                <label class="form-label">Application ID</label>
                                                <input type="text" class="form-control" name="APP_ID">
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label">Location ID</label>
                                                <input type="text" class="form-control" name="LOCATION_ID">
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label">Access Token</label>
                                                <input type="text" class="form-control" name="ACCESS_TOKEN">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row payment_gateway" id="authorized" style="display: <?= ($PAYMENT_GATEWAY_TYPE == 'Authorized.net') ? '' : 'none' ?>">
                                        <div class="col-6">
                                            <div class="form-group">
                                                <label class="form-label">Login ID</label>
                                                <input type="text" class="form-control" name="LOGIN_ID">
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label">Transaction Key</label>
                                                <input type="text" class="form-control" name="TRANSACTION_KEY">
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label">Authorize Client Key</label>
                                                <input type="text" class="form-control" name="AUTHORIZE_CLIENT_KEY">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row" style="margin-top: 50px;">
                                    <b class="btn btn-light" style="margin-bottom: 20px;">Subscription & Payment Setting</b>
                                    <input type="hidden" name="PK_OTHER_SETTING">
                                    <div class="col-4">
                                        <div class="form-group">
                                            <label class="col-md-12" for="example-text">Payment reminder send before days</label>
                                            <div class="col-md-12">
                                                <input type="text" id="PAYMENT_REMINDER_BEFORE_DAYS" name="PAYMENT_REMINDER_BEFORE_DAYS" class="form-control" placeholder="Payment reminder send before days">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="form-group">
                                            <label class="col-md-12" for="example-text">Payment failed reminder after days</label>
                                            <div class="col-md-12">
                                                <input type="text" id="PAYMENT_FAILED_REMINDER_AFTER_DAYS" name="PAYMENT_FAILED_REMINDER_AFTER_DAYS" class="form-control" placeholder="Payment failed reminder after days">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">Continue</button>
                            </div>

                            <div class="tab-pane p-20" id="locations" role="tabpanel">
                                <form class="form-material form-horizontal" action="" method="post" enctype="multipart/form-data">
                                    <input type="hidden" name="FUNCTION_NAME" value="saveLocationData">
                                    <div class="p-20">
                                        <div class="row">
                                            <div class="col-6">
                                                <div class="form-group">
                                                    <label class="col-md-12" for="example-text">Location<span class="text-danger">*</span>
                                                    </label>
                                                    <div class="col-md-12">
                                                        <input type="text" id="LOCATION_NAME" name="LOCATION_NAME" class="form-control" placeholder="Enter Location Name" required>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="form-group">
                                                    <label class="col-md-12" for="example-text">Location Code<span class="text-danger">*</span>
                                                    </label>
                                                    <div class="col-md-12">
                                                        <input type="text" id="LOCATION_CODE" name="LOCATION_CODE" class="form-control" placeholder="Enter Location Code" required>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-6">
                                                <div class="form-group">
                                                    <label class="col-md-12" for="example-text">Address</label>
                                                    <div class="col-md-12">
                                                        <input type="text" id="ADDRESS" name="ADDRESS" class="form-control" placeholder="Enter Address">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="form-group">
                                                    <label class="col-md-12" for="example-text">Apt/Ste</label>
                                                    <div class="col-md-12">
                                                        <input type="text" id="ADDRESS_1" name="ADDRESS_1" class="form-control" placeholder="Enter Apartment OR Street">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-6">
                                                <div class="form-group">
                                                    <label class="col-md-12" for="example-text">Country<span class="text-danger">*</span>
                                                    </label>
                                                    <div class="col-md-12">
                                                        <div class="col-sm-12">
                                                            <select class="form-control" name="PK_COUNTRY" id="PK_COUNTRY" onChange="fetch_state(this.value)" required>
                                                                <option value="">Select Country</option>
                                                                <?php
                                                                $row = $db->Execute("SELECT PK_COUNTRY,COUNTRY_NAME FROM DOA_COUNTRY WHERE ACTIVE = 1 ORDER BY PK_COUNTRY");
                                                                while (!$row->EOF) { ?>
                                                                    <option value="<?php echo $row->fields['PK_COUNTRY']; ?>"><?= $row->fields['COUNTRY_NAME'] ?></option>
                                                                <?php $row->MoveNext();
                                                                } ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="form-group">
                                                    <label class="col-md-12" for="example-text">State<span class="text-danger">*</span>
                                                    </label>
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
                                                    <label class="col-md-12" for="example-text">City</span>
                                                    </label>
                                                    <div class="col-md-12">
                                                        <input type="text" id="CITY" name="CITY" class="form-control" placeholder="Enter City">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="form-group">
                                                    <label class="col-md-12" for="example-text">Postal / Zip Code</span>
                                                    </label>
                                                    <div class="col-md-12">
                                                        <input type="text" id="ZIP_CODE" name="ZIP_CODE" class="form-control" placeholder="Enter Postal / Zip Code">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-6">
                                                <div class="form-group">
                                                    <label class="col-md-12" for="example-text">Phone</label>
                                                    <div class="col-md-12">
                                                        <input type="text" id="PHONE" name="PHONE" class="form-control" placeholder="Enter Phone No.">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="form-group">
                                                    <label class="col-md-12" for="example-text">Email</label>
                                                    <div class="col-md-12">
                                                        <input type="email" id="EMAIL" name="EMAIL" class="form-control" placeholder="enter Email Address">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-6">
                                                <div class="form-group">
                                                    <label class="col-md-12" for="example-text">Location Image</label>
                                                    <div class="col-md-12">
                                                        <input type="file" name="IMAGE_PATH" id="IMAGE_PATH" class="form-control">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="form-group">
                                                    <label class="col-md-12" for="example-text">Timezone<span class="text-danger">*</span></label>
                                                    <div class="col-md-12">
                                                        <select name="PK_TIMEZONE" id="PK_TIMEZONE" class="form-control required-entry" required>
                                                            <option value="">Select</option>
                                                            <? $res_type = $db->Execute("SELECT * FROM DOA_TIMEZONE WHERE ACTIVE = 1 ORDER BY NAME ASC");
                                                            while (!$res_type->EOF) { ?>
                                                                <option value="<?= $res_type->fields['PK_TIMEZONE'] ?>"><?= $res_type->fields['NAME'] ?></option>
                                                            <? $res_type->MoveNext();
                                                            } ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <!--<div class="row">
                                                <div class="col-3">
                                                    <div class="form-group">
                                                        <label class="col-md-12" for="example-text">Royalty Percentage</label>
                                                        <div class="input-group">
                                                            <input type="text" name="ROYALTY_PERCENTAGE" id="ROYALTY_PERCENTAGE" class="form-control">
                                                            <span class="form-control input-group-text">%</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>-->

                                        <div class="row smtp" id="smtp">
                                            <div class="form-group">
                                                <label class="form-label">SMTP Setup</label>
                                            </div>
                                            <div class="col-3">
                                                <div class="form-group">
                                                    <label class="form-label">SMTP HOST</label>
                                                    <input type="text" class="form-control" name="SMTP_HOST">
                                                </div>
                                            </div>
                                            <div class="col-3">
                                                <div class="form-group">
                                                    <label class="form-label">SMTP PORT</label>
                                                    <input type="text" class="form-control" name="SMTP_PORT">
                                                </div>
                                            </div>
                                            <div class="col-3">
                                                <div class="form-group">
                                                    <label class="form-label">SMTP USERNAME</label>
                                                    <input type="text" class="form-control" name="SMTP_USERNAME">
                                                </div>
                                            </div>
                                            <div class="col-3">
                                                <div class="form-group">
                                                    <label class="form-label">SMTP PASSWORD</label>
                                                    <input type="text" class="form-control" name="SMTP_PASSWORD">
                                                </div>
                                            </div>
                                        </div>

                                        <?php if ($FRANCHISE == 1) { ?>
                                            <div class="row smtp" id="smtp">
                                                <div class="form-group">
                                                    <label class="form-label">Arthur Murray API Setup</label>
                                                </div>
                                                <div class="col-4">
                                                    <div class="form-group">
                                                        <label class="form-label">User Name</label>
                                                        <input type="text" class="form-control" name="AM_USER_NAME">
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="form-group">
                                                        <label class="form-label">Password</label>
                                                        <input type="text" class="form-control" name="AM_PASSWORD">
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="form-group">
                                                        <label class="form-label">Refresh Token</label>
                                                        <input type="text" class="form-control" name="AM_REFRESH_TOKEN">
                                                    </div>
                                                </div>
                                            </div>
                                        <?php } ?>

                                        <?php if ($ABLE_TO_EDIT_PAYMENT_GATEWAY == 1) { ?>
                                            <div class="col-6" style="margin-top:50px">
                                                <div class="row">
                                                    <div class="form-group">
                                                        <label class="form-label" style="margin-bottom: 5px;">Payment Gateway</label><br>
                                                        <label style="margin-right: 70px;"><input type="radio" id="PAYMENT_GATEWAY_TYPE" name="PAYMENT_GATEWAY_TYPE" class="form-check-inline" value="Stripe" onclick="showPaymentGateway(this);">Stripe</label>
                                                        <label style="margin-right: 70px;"><input type="radio" id="PAYMENT_GATEWAY_TYPE" name="PAYMENT_GATEWAY_TYPE" class="form-check-inline" value="Square" onclick="showPaymentGateway(this);">Square</label>
                                                        <label style="margin-right: 70px;"><input type="radio" id="PAYMENT_GATEWAY_TYPE" name="PAYMENT_GATEWAY_TYPE" class="form-check-inline" value="Authorized.net" onclick="showPaymentGateway(this);">Authorized.net</label>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row payment_gateway" id="stripe" style="display: <?= ($PAYMENT_GATEWAY_TYPE == 'Stripe') ? '' : 'none' ?>;">
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="form-label">Secret Key</label>
                                                        <input type="text" class="form-control" name="SECRET_KEY">
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="form-label">Publishable Key</label>
                                                        <input type="text" class="form-control" name="PUBLISHABLE_KEY">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row payment_gateway" id="square" style="display: <?= ($PAYMENT_GATEWAY_TYPE == 'Square') ? '' : 'none' ?>">
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="form-label">Application ID</label>
                                                        <input type="text" class="form-control" name="APP_ID">
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="form-label">Location ID</label>
                                                        <input type="text" class="form-control" name="LOCATION_ID">
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="form-label">Access Token</label>
                                                        <input type="text" class="form-control" name="ACCESS_TOKEN">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row payment_gateway" id="authorized" style="display: <?= ($PAYMENT_GATEWAY_TYPE == 'Authorized.net') ? '' : 'none' ?>">
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="form-label">Login ID</label>
                                                        <input type="text" class="form-control" name="LOGIN_ID">
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="form-label">Transaction Key</label>
                                                        <input type="text" class="form-control" name="TRANSACTION_KEY">
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="form-label">Authorize Client Key</label>
                                                        <input type="text" class="form-control" name="AUTHORIZE_CLIENT_KEY">
                                                    </div>
                                                </div>
                                            </div>
                                        <?php } ?>

                                        <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">Continue</button>
                                    </div>
                                </form>
                            </div>

                            <div class="tab-pane p-20" id="hours_of_operations" role="tabpanel">
                                <form class="form-material form-horizontal" action="" method="post" enctype="multipart/form-data">
                                    <input type="hidden" name="FUNCTION_NAME" value="saveOperationalHours">
                                    <div class="p-20" id="holiday_list_div">
                                        <div class="row">
                                            <div class="col-3">
                                                <div class="form-group" style="text-align: center;">
                                                    <label class="form-label" for="example-text" style="font-weight: bold;">Day</label>
                                                </div>
                                            </div>
                                            <div class="col-3">
                                                <div class="form-group" style="text-align: center;">
                                                    <label class="form-label" for="example-text" style="font-weight: bold;">Open Time</label>
                                                </div>
                                            </div>
                                            <div class="col-3">
                                                <div class="form-group" style="text-align: center;">
                                                    <label class="form-label" for="example-text" style="font-weight: bold;">Close Time</label>
                                                </div>
                                            </div>
                                            <div class="col-3">
                                                <div class="form-group">
                                                    <label><input type="checkbox" name="ALL_DAYS" class="form-check-inline" onclick="applyToAllDays(this)"> All Days</label>
                                                </div>
                                            </div>
                                        </div>
                                        <?php
                                        for ($i = 1; $i <= 7; $i++) { ?>
                                            <div class="row">
                                                <div class="col-3">
                                                    <div class="form-group">
                                                        <div class="col-md-12">
                                                            <select name="DAY_NUMBER[]" class="form-control required-entry" disabled>
                                                                <option value="1" <?= ($i == 1) ? 'selected' : '' ?>>Monday</option>
                                                                <option value="2" <?= ($i == 2) ? 'selected' : '' ?>>Tuesday</option>
                                                                <option value="3" <?= ($i == 3) ? 'selected' : '' ?>>Wednesday</option>
                                                                <option value="4" <?= ($i == 4) ? 'selected' : '' ?>>Thursday</option>
                                                                <option value="5" <?= ($i == 5) ? 'selected' : '' ?>>Friday</option>
                                                                <option value="6" <?= ($i == 6) ? 'selected' : '' ?>>Saturday</option>
                                                                <option value="7" <?= ($i == 7) ? 'selected' : '' ?>>Sunday</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-3">
                                                    <div class="form-group">
                                                        <div class="col-md-12">
                                                            <input type="text" name="OPEN_TIME[]" class="form-control time-input time-picker OPEN_TIME" readonly>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-3">
                                                    <div class="form-group">
                                                        <div class="col-md-12">
                                                            <input type="text" name="CLOSE_TIME[]" class="form-control time-input time-picker CLOSE_TIME" readonly>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-3">
                                                    <div class="form-group">
                                                        <div class="col-md-12" style="margin-top: 10px;">
                                                            <label><input type="checkbox" name="CLOSED_<?= $i - 1 ?>" onchange="closeThisDay(this)"> Closed</label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php } ?>
                                    </div>
                                    <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">Continue</button>
                                </form>
                            </div>

                            <!--User Profile Info Tab-->
                            <div class="tab-pane p-20" id="users" role="tabpanel">
                                <form class="form-material form-horizontal" id="profile_info_form">
                                    <input type="hidden" name="FUNCTION_NAME" value="saveProfileInfoData">
                                    <div class="p-20">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label class="form-label mb-0">Roles</label>
                                                <input type="hidden" name="PK_ROLES" value="2">
                                                <input type="text" class="form-control" value="Account Admin" readonly>
                                            </div>
                                            <div class="col-6">
                                                <div class="form-group">
                                                    <label class="col-md-12">User Name<span class="text-danger">*</span>
                                                    </label>
                                                    <div class="col-md-12">
                                                        <input type="text" id="USER_NAME" name="USER_NAME" class="form-control" placeholder="Enter User Name" required data-validation-required-message="This field is required" onkeyup="ValidateUsername()">
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
                                                        <input type="text" id="FIRST_NAME" name="FIRST_NAME" class="form-control" placeholder="Enter First Name" required>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="form-group">
                                                    <label class="col-md-12">Last Name
                                                    </label>
                                                    <div class="col-md-12">
                                                        <input type="text" id="LAST_NAME" name="LAST_NAME" class="form-control" placeholder="Enter Last Name">
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
                                                        <input type="email" id="EMAIL_ID" name="EMAIL_ID" class="form-control" placeholder="Enter Email Address" required data-validation-required-message="This field is required">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="form-label">Gender</label>
                                                    <select class="form-control form-control" id="GENDER" name="GENDER">
                                                        <option value="1">Male</option>
                                                        <option value="2">Female</option>
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
                                                    <label class="col-md-12">Address
                                                    </label>
                                                    <div class="col-md-12">
                                                        <input type="text" id="ADDRESS" name="ADDRESS" class="form-control" placeholder="Enter Address">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="form-group">
                                                    <label class="col-md-12">Apt/Ste
                                                    </label>
                                                    <div class="col-md-12">
                                                        <input type="text" id="ADDRESS_1" name="ADDRESS_1" class="form-control" placeholder="Enter Address">
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
                                                        <input type="text" id="CITY" name="CITY" class="form-control" placeholder="Enter your city">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="form-group">
                                                    <label class="col-md-12">Postal / Zip Code</span>
                                                    </label>
                                                    <div class="col-md-12">
                                                        <input type="text" id="ZIP" name="ZIP" class="form-control" placeholder="Enter Postal / Zip Code">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-6">
                                                <div class="form-group">
                                                    <label class="col-md-12">Phone</label>
                                                    <div class="col-md-12">
                                                        <input type="text" id="PHONE" name="PHONE" class="form-control" placeholder="Enter Phone No.">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="form-group">
                                                    <label class="col-md-12">Remarks</label>
                                                    <div class="col-md-12">
                                                        <textarea class="form-control" rows="2" id="NOTES" name="NOTES"></textarea>
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
                                                <span style="color:red">Note : Password Must contain at least one number and one uppercase and lowercase letter, and at least 8 or more characters</span>
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
                                                <label class="col-md-12"><input type="checkbox" id="ABLE_TO_EDIT_PAYMENT_GATEWAY" name="ABLE_TO_EDIT_PAYMENT_GATEWAY" class="form-check-inline" <?= ($ABLE_TO_EDIT_PAYMENT_GATEWAY == 1) ? 'checked' : '' ?>> Able to edit payment gateway</label>
                                            </div>
                                        </div>

                                    </div>
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">Save</button>
                                        <button type="button" id="cancel_button" class="btn btn-inverse waves-effect waves-light">Cancel</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </form>
    </div>
    <style>
        .progress-bar {
            border-radius: 5px;
            height: 18px !important;
        }
    </style>
    <?php require_once('includes/footer.php'); ?>

    <script src="http://igorescobar.github.io/jQuery-Mask-Plugin/js/jquery.mask.min.js"></script>


    <script>
        $('.datepicker-past').datepicker({
            format: 'mm/dd/yyyy',
            maxDate: 0,
            changeMonth: true,
            changeYear: true,
            yearRange: '1900:' + new Date().getFullYear(),
        });

        $('.datepicker-normal').datepicker({
            format: 'mm/dd/yyyy',
        });

        $(document).ready(function() {
            fetch_state(<?php echo $PK_COUNTRY; ?>);
            fetch_Account_State(<?php echo $ACCOUNT_PK_COUNTRY; ?>);
        });

        function fetch_state(PK_COUNTRY) {
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
        }

        function fetch_Account_State(PK_COUNTRY) {
            let data = "PK_COUNTRY=" + PK_COUNTRY + "&PK_STATES=<?= $ACCOUNT_PK_STATES; ?>";
            let value = $.ajax({
                url: "ajax/state.php",
                type: "POST",
                data: data,
                async: false,
                cache: false,
                success: function(result) {
                    document.getElementById('Account_State_div').innerHTML = result;

                }
            }).responseText;
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
            } else {
                lblError.innerHTML = "";
            }
        }

        function editpage(PK_USER, AC_ID) {
            window.location.href = "edit_account_user.php?id=" + PK_USER + "&ac_id=" + AC_ID;
        }

        function confirmDelete(anchor) {
            let conf = confirm("Are you sure you want to delete?");
            if (conf)
                window.location = $(anchor).data("href");
        }

        $(document).on('click', '#cancel_button', function() {
            window.location.href = 'all_accounts.php';
        });

        let PK_ACCOUNT_MASTER = parseInt(<?= empty($_GET['id']) ? 0 : $_GET['id'] ?>);

        $(document).on('submit', '#account_info_form', function(event) {
            event.preventDefault();
            let form_data = $('#account_info_form').serialize();
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                data: form_data,
                dataType: 'JSON',
                success: function(data) {
                    $('.PK_ACCOUNT_MASTER').val(data);
                    if (PK_ACCOUNT_MASTER == 0) {
                        $('#profile_tab_link')[0].click();
                    } else {
                        window.location.href = 'all_accounts.php';
                    }
                }
            });
        });

        $(document).on('submit', '#profile_info_form', function(event) {
            event.preventDefault();
            let form_data = $('#profile_info_form').serialize();
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                data: form_data,
                dataType: 'JSON',
                success: function(data) {
                    $('.PK_ACCOUNT_MASTER').val(data);
                    window.location.href = 'all_accounts.php';
                }
            });
        });
    </script>

    <!--Newly Added-->
    <script>
        function showPaymentGateway(param) {
            $('.payment_gateway').slideUp();
            if ($(param).val() === 'Stripe') {
                $('#stripe').slideDown();
            } else {
                if ($(param).val() === 'Square') {
                    $('#square').slideDown();
                } else {
                    if ($(param).val() === 'Authorized.net') {
                        $('#authorized').slideDown();
                    }
                }

            }
        }

        $('.time-picker').timepicker({
            timeFormat: 'hh:mm p',
        });

        function closeThisDay(param) {
            if ($(param).is(':checked')) {
                $(param).closest('.row').find('.time-input').val('');
                $(param).closest('.row').find('.time-input').css('pointer-events', 'none');
            } else {
                $(param).closest('.row').find('.time-input').css('pointer-events', '');
            }
        }

        function applyToAllDays(param) {
            if ($(param).is(':checked')) {
                let OPEN_TIME = $(".OPEN_TIME");
                $('.OPEN_TIME').val($(OPEN_TIME[0]).val());

                let CLOSE_TIME = $(".CLOSE_TIME");
                $('.CLOSE_TIME').val($(CLOSE_TIME[0]).val());
            } else {
                let OPEN_TIME = $(".OPEN_TIME");
                for (let i = 1; i < 7; i++) {
                    $(OPEN_TIME[i]).val('');
                }

                let CLOSE_TIME = $(".CLOSE_TIME");
                for (let i = 1; i < 7; i++) {
                    $(CLOSE_TIME[i]).val('');
                }
            }
        }
    </script>


</body>

</html>