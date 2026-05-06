<?php
require_once('../../global/config.php');
global $db;
global $db_account;
global $master_database;

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];

$PK_USER = $_POST['PK_USER'] ?? '';
$PK_USER_MASTER = $_POST['PK_USER_MASTER'] ?? '';

$customer_details = $db->Execute("SELECT * FROM DOA_USERS WHERE IS_DELETED = 0 AND DOA_USERS.PK_USER = " . $PK_USER);


$USER_NAME = $customer_details->fields['USER_NAME'];
$FIRST_NAME = $customer_details->fields['FIRST_NAME'];
$LAST_NAME = $customer_details->fields['LAST_NAME'];
$CUSTOMER_ID = $customer_details->fields['USER_ID'];
$UNIQUE_ID = $customer_details->fields['UNIQUE_ID'];
$EMAIL_ID = $customer_details->fields['EMAIL_ID'];
$USER_IMAGE = $customer_details->fields['USER_IMAGE'];
$GENDER = $customer_details->fields['GENDER'];
$DOB = $customer_details->fields['DOB'];
$ADDRESS = $customer_details->fields['ADDRESS'];
$ADDRESS_1 = $customer_details->fields['ADDRESS_1'];
$PK_COUNTRY = $customer_details->fields['PK_COUNTRY'];
$PK_STATES = $customer_details->fields['PK_STATES'];
$CITY = $customer_details->fields['CITY'];
$ZIP = $customer_details->fields['ZIP'];
$PHONE = $customer_details->fields['PHONE'];
$NOTES = $customer_details->fields['NOTES'];
$ACTIVE = $customer_details->fields['ACTIVE'];
$PASSWORD = $customer_details->fields['PASSWORD'];
$INACTIVE_BY_ADMIN = $customer_details->fields['INACTIVE_BY_ADMIN'];
$CREATE_LOGIN = $customer_details->fields['CREATE_LOGIN'];
$CREATED_ON = $customer_details->fields['CREATED_ON'];

$customer_data = $db_account->Execute("SELECT * FROM `DOA_CUSTOMER_DETAILS` WHERE `PK_USER_MASTER` = " . $PK_USER_MASTER);
if ($customer_data->RecordCount() > 0) {
    $PK_CUSTOMER_DETAILS = $customer_data->fields['PK_CUSTOMER_DETAILS'];
    $CALL_PREFERENCE = $customer_data->fields['CALL_PREFERENCE'];
    $REMINDER_OPTION = explode(',', $customer_data->fields['REMINDER_OPTION']);
    $ATTENDING_WITH = $customer_data->fields['ATTENDING_WITH'];
    $PARTNER_FIRST_NAME = $customer_data->fields['PARTNER_FIRST_NAME'];
    $PARTNER_LAST_NAME = $customer_data->fields['PARTNER_LAST_NAME'];
    $PARTNER_PHONE = $customer_data->fields['PARTNER_PHONE'];
    $PARTNER_EMAIL = $customer_data->fields['PARTNER_EMAIL'];
    $PARTNER_GENDER = $customer_data->fields['PARTNER_GENDER'];
    $PARTNER_DOB = $customer_data->fields['PARTNER_DOB'];
}

$selected_primary_location = $db->Execute("SELECT DOA_USER_MASTER.PRIMARY_LOCATION_ID, DOA_LOCATION.LOCATION_NAME FROM DOA_USER_MASTER LEFT JOIN DOA_LOCATION ON DOA_LOCATION.PK_LOCATION = DOA_USER_MASTER.PRIMARY_LOCATION_ID WHERE DOA_USER_MASTER.PK_USER_MASTER = " . $PK_USER_MASTER);
if ($selected_primary_location->RecordCount() > 0) {
    $primary_location = $selected_primary_location->fields['PRIMARY_LOCATION_ID'];
    $PRIMARY_LOCATION_NAME = $selected_primary_location->fields['LOCATION_NAME'];
}

?>
<style>
    .value {
        margin-top: 5px;
    }

    #reminder_email:checked+label,
    #reminder_text:checked+label,
    #reminder_call:checked+label {
        background-color: #39b54a !important;
        color: white !important;
        border-color: #39b54a !important;
    }

    .col-6 .add-btn {
        display: flex;
        align-items: center;
        height: 38px;
    }
</style>
<div class="profile-card">
    <form id="edit_customer_form">
        <input type="hidden" name="PK_USER" value="<?= $PK_USER ?>">
        <input type="hidden" name="PK_USER_MASTER" value="<?= $PK_USER_MASTER ?>">
        <input type="hidden" name="PK_CUSTOMER_DETAILS" value="<?= $PK_CUSTOMER_DETAILS ?>">
        <input type="hidden" name="FUNCTION_NAME" value="updateCustomerProfileDetails">

        <div class="d-flex justify-content-between border-bottom align-items-center">
            <div>
                <div class="section-title">Personal Information</div>
                <div class="section-desc">Optional settings section description</div>
            </div>

            <div class="d-flex gap-2 align-items-center">
                <a href="javascript:;" class="btn btn-secondary cancel">Cancel</a>
                <button class="btn btn-secondary" type="submit">Save</button>
            </div>
        </div>

        <div class="row mt-3 align-items-center">
            <div class="col-auto">
                <div class="avatar-placeholder">
                    <?php if ($USER_IMAGE != '') { ?>
                        <a class="fancybox" href="<?php echo $USER_IMAGE; ?>" data-fancybox-group="gallery">
                            <img src="<?php echo $USER_IMAGE; ?>" style="width:100px; height:100px; border: 2px solid #ccc; border-radius: 50%; " />
                        </a><?php } else { ?>
                        <i class="bi bi-person-fill text-white fs-1"></i>
                    <?php } ?>
                </div>
            </div>
            <div class="col-5">
                <label class="form-label">Upload New Profile Image</label>
                <input type="file" name="USER_IMAGE" class="form-control" accept="image/*" />
                <small class="form-text text-muted">Choose a JPG, PNG, or GIF file. Max file size 2MB.</small>
            </div>
        </div>

        <div class="row">
            <div class="col-6">
                <div class="label">First Name</div>
                <div class="value">
                    <input type="text" name="FIRST_NAME" class="form-control" value="<?= $FIRST_NAME ?>" />
                </div>
            </div>
            <div class="col-6">
                <div class="label">Last Name</div>
                <div class="value">
                    <input type="text" name="LAST_NAME" class="form-control" value="<?= $LAST_NAME ?>" />
                </div>
            </div>

            <div class="col-6">
                <div class="label">Customer ID</div>
                <div class="value">
                    <input type="text" name="CUSTOMER_ID" class="form-control" value="<?= $CUSTOMER_ID ?>" />
                </div>
            </div>
            <div class="col-6">
                <div class="label">Created On</div>
                <div class="value">
                    <input type="text" name="CREATED_ON" class="form-control datepicker-normal" value="<?= date('m/d/Y', strtotime($CREATED_ON)) ?>" />
                </div>
            </div>

            <div class="col-6">
                <div class="label">Primary Location</div>
                <div class="value">
                    <select class="form-control" name="PRIMARY_LOCATION_ID" id="PK_LOCATION_SINGLE" onchange="selectThisPrimaryLocation(this)" required>
                        <option value="">Select Primary Location</option>
                        <?php
                        $row = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME FROM DOA_LOCATION WHERE ACTIVE = 1 AND PK_LOCATION IN (" . $DEFAULT_LOCATION_ID . ") AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
                        while (!$row->EOF) { ?>
                            <option value="<?php echo $row->fields['PK_LOCATION']; ?>" <?= ($primary_location == $row->fields['PK_LOCATION']) ? "selected" : "" ?>><?= $row->fields['LOCATION_NAME'] ?></option>
                        <?php $row->MoveNext();
                        } ?>
                    </select>
                </div>
            </div>
            <div class="col-6">
                <div class="label">Preferred Location</div>
                <div class="value">
                    <?php
                    $selected_location = [];
                    $selected_location_row = $db->Execute("SELECT `PK_LOCATION` FROM `DOA_USER_LOCATION` WHERE `PK_USER` = " . $PK_USER);
                    while (!$selected_location_row->EOF) {
                        $selected_location[] = $selected_location_row->fields['PK_LOCATION'];
                        $selected_location_row->MoveNext();
                    }

                    ?>
                    <input type="hidden" id="selected_location" value="<?= implode(',', $selected_location); ?>">
                    <select class="multi_sumo_select" name="PK_USER_LOCATION[]" id="PK_LOCATION_MULTIPLE" multiple>
                        <?php
                        $row = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME FROM DOA_LOCATION WHERE PK_LOCATION != '$primary_location' AND ACTIVE = 1 AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
                        while (!$row->EOF) { ?>
                            <option value="<?php echo $row->fields['PK_LOCATION']; ?>" <?= in_array($row->fields['PK_LOCATION'], $selected_location) ? "selected" : "" ?>><?= $row->fields['LOCATION_NAME'] ?></option>
                        <?php $row->MoveNext();
                        } ?>
                    </select>
                </div>
            </div>

            <div class="col-6">
                <div class="label">Phone</div>
                <div class="value">
                    <input type="text" name="PHONE" class="form-control format_phone_number" placeholder="Phone NUmber" value="<?= $PHONE ?>" />
                </div>
            </div>
            <div class="col-6">
                <a href="javaScript:void(0)" class="add-btn" style="margin-top: 25px;" onclick="addMorePhone();"><i class="bi bi-plus"></i> Add New</a>
            </div>

            <div id="add_more_phone">
                <?php
                $customer_phone = $db_account->Execute("SELECT * FROM DOA_CUSTOMER_PHONE WHERE PK_CUSTOMER_DETAILS = '$PK_CUSTOMER_DETAILS'");
                while (!$customer_phone->EOF) { ?>
                    <div class="row">
                        <div class="col-6">
                            <div class="value">
                                <input type="text" name="CUSTOMER_PHONE[]" class="form-control format_phone_number" placeholder="Phone Number" value="<?= formatPhone($customer_phone->fields['PHONE']) ?>" />
                            </div>
                        </div>
                        <div class="col-6">
                            <a href="javaScript:void(0)" style="margin-top: 25px;" onclick="deleteThisRow(this);"><i class="fa fa-trash" style="margin-top: 14px; font-size: 20px;"></i></a>
                        </div>
                    </div>
                <?php $customer_phone->MoveNext();
                } ?>
            </div>

            <div class="col-6">
                <div class="label">Email</div>
                <div class="value">
                    <input type="email" name="EMAIL_ID" class="form-control" placeholder="Email" value="<?= $EMAIL_ID ?>" />
                </div>
            </div>
            <div class="col-6">
                <a href="javaScript:void(0)" class="add-btn" style="margin-top: 25px;" onclick="addMoreEmail();"><i class="bi bi-plus"></i> Add New</a>
            </div>

            <div id="add_more_email">
                <?php
                $customer_email = $db_account->Execute("SELECT * FROM DOA_CUSTOMER_EMAIL WHERE PK_CUSTOMER_DETAILS = '$PK_CUSTOMER_DETAILS'");
                while (!$customer_email->EOF) { ?>
                    <div class="row">
                        <div class="col-6">
                            <div class="value">
                                <input type="email" name="CUSTOMER_EMAIL[]" class="form-control" placeholder="Email" value="<?= $customer_email->fields['EMAIL'] ?>" />
                            </div>
                        </div>
                        <div class="col-6">
                            <a href="javaScript:void(0)" style="margin-top: 25px;" onclick="deleteThisRow(this);"><i class="fa fa-trash" style="margin-top: 14px; font-size: 20px;"></i></a>
                        </div>
                    </div>
                <?php $customer_email->MoveNext();
                } ?>
            </div>

            <div class="col-6">
                <div class="label">Reminder Options</div>
                <div class="value">
                    <div class="btn-group" role="group" aria-label="Reminder Options" style="gap: 10px; flex-wrap: wrap;">
                        <input type="checkbox" class="btn-check" name="REMINDER_OPTION[]" id="reminder_email" value="Email" <?= (is_array($REMINDER_OPTION) && in_array('Email', $REMINDER_OPTION)) ? "checked" : "" ?> />
                        <label class="btn btn-outline" for="reminder_email" style="border-radius: 50px; border-color: #39b54a; color: #39b54a; font-size: 14px; margin-top: 7px;">Email</label>

                        <input type="checkbox" class="btn-check" name="REMINDER_OPTION[]" id="reminder_text" value="Text Message" <?= (is_array($REMINDER_OPTION) && in_array('Text Message', $REMINDER_OPTION)) ? "checked" : "" ?> />
                        <label class="btn btn-outline" for="reminder_text" style="border-radius: 50px; border-color: #39b54a; color: #39b54a; font-size: 14px; margin-top: 7px;">Text Message</label>

                        <input type="checkbox" class="btn-check" name="REMINDER_OPTION[]" id="reminder_call" value="Phone Call" <?= (is_array($REMINDER_OPTION) && in_array('Phone Call', $REMINDER_OPTION)) ? "checked" : "" ?> />
                        <label class="btn btn-outline" for="reminder_call" style="border-radius: 50px; border-color: #39b54a; color: #39b54a; font-size: 14px; margin-top: 7px;">Phone Call</label>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="label">Tag</div>
                <div class="value">
                    <?php
                    $selected_tag = [];
                    $selected_tag_row = $db_account->Execute("SELECT `PK_TAG` FROM `DOA_USER_TAG` WHERE `PK_USER_MASTER` = " . $PK_USER_MASTER);
                    while (!$selected_tag_row->EOF) {
                        $selected_tag[] = $selected_tag_row->fields['PK_TAG'];
                        $selected_tag_row->MoveNext();
                    }
                    ?>
                    <input type="hidden" id="selected_tag" value="<?= implode(',', $selected_tag); ?>">
                    <select class="multi_sumo_select" name="PK_USER_TAG[]" id="PK_TAG_MULTIPLE" multiple>
                        <?php
                        $row = $db_account->Execute("SELECT PK_TAG, TAG_NAME FROM DOA_TAG WHERE ACTIVE = 1 ORDER BY TAG_NAME");
                        while (!$row->EOF) { ?>
                            <option value="<?php echo $row->fields['PK_TAG']; ?>" <?= in_array($row->fields['PK_TAG'], $selected_tag) ? "selected" : "" ?>><?= $row->fields['TAG_NAME'] ?></option>
                        <?php $row->MoveNext();
                        } ?>
                    </select>
                </div>
            </div>

            <div class="col-6">
                <div class="label">Gender</div>
                <div class="value">
                    <select class="form-control" name="GENDER" id="GENDER">
                        <option value="">Select Gender</option>
                        <option value="Male" <?= ($GENDER == 'Male') ? "selected" : "" ?>>Male</option>
                        <option value="Female" <?= ($GENDER == 'Female') ? "selected" : "" ?>>Female</option>
                        <option value="Other" <?= ($GENDER == 'Other') ? "selected" : "" ?>>Other</option>
                    </select>
                </div>
            </div>
            <div class="col-6">
                <div class="label">Date of Birth</div>
                <div class="value">
                    <input type="text" class="form-control datepicker-past" id="DOB" name="DOB" value="<?= ($DOB == '' || $DOB == '0000-00-00' || $DOB == '1969-12-31') ? '' : date('m/d/Y', strtotime($DOB)) ?>">
                </div>
            </div>

            <div class="col-6">
                <div class="label">Status</div>
                <div class="value">
                    <select class="form-control" name="ACTIVE" id="ACTIVE" onchange="changeCustomerStatus(this, <?= $PK_USER ?>)">
                        <option value="1" <?= ($ACTIVE == 1) ? "selected" : "" ?>>Active</option>
                        <option value="0" <?= ($ACTIVE == 0) ? "selected" : "" ?>>Inactive</option>
                    </select>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    $('.datepicker-normal').datepicker({
        format: 'mm/dd/yyyy',
    });

    $('.datepicker-past').datepicker({
        format: 'mm/dd/yyyy',
        maxDate: 0,
        changeMonth: true,
        changeYear: true,
        yearRange: '1900:' + new Date().getFullYear(),
    });

    $('.multi_sumo_select').SumoSelect({
        placeholder: 'Select Location',
        selectAll: true
    });

    function formatPhoneNumber(input) {
        let digits = input.value.replace(/\D/g, '');
        if (digits.length > 10) {
            digits = digits.slice(0, 10);
        }
        let formatted = digits;

        if (digits.length <= 3) {
            formatted = digits;
        } else if (digits.length <= 6) {
            formatted = `(${digits.slice(0, 3)}) ${digits.slice(3)}`;
        } else {
            formatted = `(${digits.slice(0, 3)}) ${digits.slice(3, 6)}-${digits.slice(6)}`;
        }

        input.value = formatted;
    }

    $(document).on('input', '.format_phone_number', function() {
        formatPhoneNumber(this);
    });

    function addMorePhone() {
        $('#add_more_phone').append(`<div class="row">
                                        <div class="col-6">
                                            <div class="value">
                                                <input type="text" name="CUSTOMER_PHONE[]" class="form-control format_phone_number" placeholder="Phone Number" />
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <a href="javaScript:void(0)" style="margin-top: 25px;" onclick="deleteThisRow(this);"><i class="fa fa-trash" style="margin-top: 14px; font-size: 20px;"></i></a>
                                        </div>
                                    </div>`);
    }

    function addMoreEmail() {
        $('#add_more_email').append(`<div class="row">
                                        <div class="col-6">
                                            <div class="value">
                                                <input type="email" name="CUSTOMER_EMAIL[]" class="form-control" placeholder="Email" />
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <a href="javaScript:void(0)" style="margin-top: 25px;" onclick="deleteThisRow(this);"><i class="fa fa-trash" style="margin-top: 14px; font-size: 20px;"></i></a>
                                        </div>
                                    </div>`);
    }

    function deleteThisRow(param) {
        $(param).closest('.row').remove();
    }

    $(document).on('submit', '#edit_customer_form', function() {
        event.preventDefault();
        var formData = new FormData(this);
        $.ajax({
            type: 'POST',
            url: 'ajax/AjaxFunctions.php',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response == 1) {
                    location.reload();
                } else {
                    alert('Error updating customer details. Please try again.');
                }
            }
        });
    });
</script>