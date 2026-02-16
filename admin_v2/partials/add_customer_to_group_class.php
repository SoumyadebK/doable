<?php
require_once('../../global/config.php');
global $db;
global $db_account;
global $master_database;

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];
$LOCATION_ARRAY = explode(',', $DEFAULT_LOCATION_ID);
?>
<form class="mb-0" id="add_customer_to_group_class_form" onsubmit="addCustomerToGroupClass(this)">
    <input type="hidden" name="FUNCTION_NAME" value="addCustomerToGroupClass">
    <input type="hidden" name="PK_APPOINTMENT_MASTER" id="PK_APPOINTMENT_MASTER" value="<?= $_POST['PK_APPOINTMENT_MASTER'] ?>">
    <input type="hidden" name="PK_SERVICE_CODE" id="PK_SERVICE_CODE" value="<?= $_POST['PK_SERVICE_CODE'] ?>">
    <h6 class="mb-4">Add New Customer</h6>
    <div class="row mb-3 align-items-center mt-3">
        <div class="col-4 col-md-4">
            <div class="d-flex gap-2 align-items-center">
                <svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 32 32" viewBox="0 0 32 32" width="24px" height="24px" fill="#ccc">
                    <path d="m14.545 16.872c3.665 0 6.647-2.982 6.647-6.647s-2.982-6.647-6.647-6.647-6.647 2.982-6.647 6.647 2.982 6.647 6.647 6.647zm0-11.294c2.563 0 4.647 2.084 4.647 4.647s-2.084 4.647-4.647 4.647-4.647-2.084-4.647-4.647 2.085-4.647 4.647-4.647z" />
                    <path d="m3.15 28.387c.089.024.178.036.266.036.439 0 .841-.292.964-.735 1.253-4.555 5.434-7.736 10.166-7.736 2.11 0 4.146.623 5.888 1.8.458.308 1.079.189 1.389-.269.309-.458.189-1.079-.269-1.389-2.074-1.402-4.497-2.143-7.008-2.143-5.629 0-10.602 3.785-12.094 9.205-.147.533.166 1.084.698 1.231z" />
                    <path d="m22.766 25.513h1.909v1.909c0 .552.448 1 1 1s1-.448 1-1v-1.909h1.909c.552 0 1-.448 1-1s-.448-1-1-1h-1.909v-1.909c0-.552-.448-1-1-1s-1 .448-1 1v1.909h-1.909c-.552 0-1 .448-1 1s.448 1 1 1z" />
                </svg>
                <label class="mb-0">Customer</label>
            </div>
        </div>
        <div class="col-8 col-md-8">
            <div class="form-group">
                <select class="form-control customer_select" name="SELECTED_CUSTOMER_ID" id="SELECTED_CUSTOMER_ID" onchange="selectThisCustomerForGroupClass(this)" required>
                    <option value="">Select Customer</option>
                    <?php
                    $row = $db_account->Execute("SELECT DISTINCT (DOA_USERS.PK_USER), CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.PHONE, DOA_USERS.ACTIVE, DOA_USER_MASTER.PK_USER_MASTER, DOA_USER_MASTER.PRIMARY_LOCATION_ID FROM $master_database.DOA_USERS AS DOA_USERS INNER JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER LEFT JOIN $master_database.DOA_USER_LOCATION AS DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER INNER JOIN DOA_ENROLLMENT_MASTER ON DOA_USER_MASTER.PK_USER_MASTER = DOA_ENROLLMENT_MASTER.PK_USER_MASTER LEFT JOIN $master_database.DOA_USER_ROLES AS DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER WHERE (DOA_USER_MASTER.PRIMARY_LOCATION_ID IN (" . $DEFAULT_LOCATION_ID . ") OR DOA_USER_LOCATION.PK_LOCATION IN (" . $DEFAULT_LOCATION_ID . ")) AND DOA_USER_ROLES.PK_ROLES = 4 AND DOA_USER_MASTER.PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]' AND DOA_ENROLLMENT_MASTER.ALL_APPOINTMENT_DONE = 0 AND DOA_USERS.ACTIVE = 1 AND DOA_USERS.IS_DELETED = 0 ORDER BY DOA_USERS.FIRST_NAME");
                    while (!$row->EOF) { ?>
                        <option value="<?= $row->fields['PK_USER_MASTER']; ?>" data-location_id=<?= $row->fields['PRIMARY_LOCATION_ID']; ?>><?= $row->fields['NAME'] . ' (' . $row->fields['USER_NAME'] . ')' . ' (' . $row->fields['PHONE'] . ')' ?></option>
                    <?php $row->MoveNext();
                    } ?>
                </select>
            </div>
        </div>
    </div>

    <div class="row mb-3 enrollment_area mt-3 d-none">
        <div class="col-4 col-md-4">
            <div class="d-flex gap-2 align-items-center">
                <label class="mb-0" style="margin-left: 33px;">Enrollment ID</label>
            </div>
        </div>
        <div class="col-8 col-md-8">
            <div class="form-group" id="enrollment_div">

            </div>
        </div>
    </div>

    <hr class="mb-3">
    <!-- <div class="row mt-2">
        <div class="col-4 col-md-4">
            <div class="d-flex gap-2 align-items-center">
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Capa_1" x="0px" y="0px" viewBox="0 0 512 512" style="enable-background:new 0 0 512 512;" xml:space="preserve" width="24px" height="18px" fill="#ccc">
                    <path d="M487.104,24.954c-33.274-33.269-87.129-33.273-120.407,0L51.948,339.665c-2.098,2.097-3.834,4.825-4.831,7.817 L1.057,485.647c-5.2,15.598,9.679,30.503,25.298,25.296l138.182-46.055c2.922-0.974,5.665-2.678,7.819-4.831l314.748-314.711 C520.299,112.154,520.299,58.146,487.104,24.954z M51.654,460.352l23.177-69.525l46.356,46.35L51.654,460.352z M158.214,417.634 l-63.837-63.829l267.272-267.24l63.837,63.83L158.214,417.634z M458.818,117.065l-5.049,5.049l-63.837-63.83l5.049-5.048 c17.602-17.597,46.239-17.597,63.837,0C476.419,70.833,476.419,99.467,458.818,117.065z" />
                </svg>
                <label class="mb-0">Internal Note</label>
            </div>
        </div>
        <div class="col-8 col-md-8">
            <div class="form-group">
                <textarea class="form-control" name="INTERNAL_COMMENT"></textarea>
            </div>
        </div>
    </div> -->

    <div class="modal-footer flex-nowrap border-top">
        <button type="button" class="btn-secondary w-100 m-1" onclick="cancelAddCustomer()">Cancel</button>
        <button type="submit" class="btn-primary w-100 m-1">Save</button>
    </div>
</form>

<script>
    function cancelAddCustomer() {
        $('#sideDrawer7, .overlay7').removeClass('active');
    }

    function selectThisCustomerForGroupClass(param) {
        let PK_USER_MASTER = $(param).val();
        $('#add_customer_to_group_class .enrollment_area').removeClass('d-none');
        let PK_SERVICE_CODE = $('#add_customer_to_group_class_form #PK_SERVICE_CODE').val();
        $.ajax({
            url: "ajax/get_group_class_enrollment.php",
            type: "POST",
            data: {
                PK_USER_MASTER: PK_USER_MASTER,
                PK_SERVICE_CODE: PK_SERVICE_CODE
            },
            async: false,
            cache: false,
            success: function(result) {
                $('#add_customer_to_group_class #enrollment_div').html(result);
            }
        });
    }

    function addCustomerToGroupClass(param) {
        event.preventDefault();
        let PK_APPOINTMENT_MASTER = $('#add_customer_to_group_class_form #PK_APPOINTMENT_MASTER').val();
        let form_data = $('#add_customer_to_group_class_form').serialize();
        $.ajax({
            url: "ajax/AjaxFunctions.php",
            type: "POST",
            data: form_data,
            dataType: 'json',
            success: function(result) {
                if (result.success) {
                    $('#sideDrawer7, .overlay7').removeClass('active');
                    loadViewAppointmentModal(PK_APPOINTMENT_MASTER, 'group_class');
                } else {
                    alert(result.message);
                }
            }
        });
    }
</script>