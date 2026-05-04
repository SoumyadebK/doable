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
<div class="profile-card">
    <form id="edit_customer_address_form">
        <input type="hidden" name="PK_USER" value="<?= $PK_USER ?>">
        <input type="hidden" name="PK_USER_MASTER" value="<?= $PK_USER_MASTER ?>">
        <input type="hidden" name="FUNCTION_NAME" value="updateCustomerAddressDetails">

        <div class="d-flex justify-content-between border-bottom">
            <div>
                <div class="section-title">Address Information</div>
                <div class="section-desc">Optional settings section description</div>
            </div>

            <a href="javascript:;" class="btn btn-secondary cancel" style="height: min-content; margin-left: 40%;">Cancel</a>
            <button class="btn btn-secondary" type="submit" style="height: min-content;">Save</button>
        </div>

        <div class="row mt-3">
            <div class="col-6">
                <div class="label">Address</div>
                <div class="value">
                    <input type="text" class="form-control" name="ADDRESS" value="<?= $ADDRESS ?>">
                </div>
            </div>
            <div class="col-6">
                <div class="label">Apt/Ste</div>
                <div class="value">
                    <input type="text" class="form-control" name="ADDRESS_1" value="<?= $ADDRESS_1 ?>">
                </div>
            </div>

            <div class="col-6">
                <div class="label">Country</div>
                <div class="value">
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
            <div class="col-6">
                <div class="label">State</div>
                <div class="value">
                    <select class="form-control" name="PK_STATES" id="PK_STATES" required>
                        <option>Select State</option>
                        <?php
                        $row = $db->Execute("SELECT PK_STATES, STATE_NAME FROM DOA_STATES WHERE PK_COUNTRY = " . $PK_COUNTRY . " AND ACTIVE = 1 ORDER BY PK_STATES");
                        while (!$row->EOF) { ?>
                            <option value="<?php echo $row->fields['PK_STATES']; ?>" <?= ($row->fields['PK_STATES'] == $PK_STATES) ? "selected" : "" ?>><?= $row->fields['STATE_NAME'] ?></option>
                        <?php $row->MoveNext();
                        } ?>
                    </select>
                </div>
            </div>

            <div class="col-6">
                <div class="label">City</div>
                <div class="value">
                    <input type="text" class="form-control" name="CITY" value="<?= $CITY ?>">
                </div>
            </div>
            <div class="col-6">
                <div class="label">Postal / Zip Code</div>
                <div class="value">
                    <input type="text" class="form-control" name="ZIP" value="<?= $ZIP ?>">
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    $(document).on('submit', '#edit_customer_address_form', function() {
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