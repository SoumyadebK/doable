<?php
$service_master = $db_account->Execute("SELECT DOA_SERVICE_CODE.PK_SERVICE_CODE, DOA_SERVICE_CODE.PK_SERVICE_MASTER FROM DOA_SERVICE_CODE WHERE PK_LOCATION IN (" . $DEFAULT_LOCATION_ID . ") AND SERVICE_CODE = 'COMM'");
if ($service_master->RecordCount() == 0) {
    $PK_SERVICE_MASTER = 0;
    $PK_SERVICE_CODE = 0;
} else {
    $PK_SERVICE_MASTER = $service_master->fields['PK_SERVICE_MASTER'];
    $PK_SERVICE_CODE = $service_master->fields['PK_SERVICE_CODE'];
}
?>
<style>
    .slot_div {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        /* 2 slots per row */
        gap: 10px;
    }

    .slot_div span {
        padding: 8px;
        text-align: center;
        border: 1px solid #ccc;
        border-radius: 20px;
        background: #f8f9fa;
        font-size: 12px;
        font-weight: 500;
        cursor: pointer;
    }

    .slot_div {
        display: grid;
        grid-template-columns: 1fr;
        gap: 10px;
    }

    @media (min-width: 768px) {
        .slot_div {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    .optWrapper {
        max-width: 295px;
    }

    .form-group .SumoSelect {
        width: 100%;
    }

    .multi_sumo_select {
        font-size: 12px;
    }
</style>
<!-- Individual Appointment -->
<div class="overlay"></div>
<div class="side-drawer" id="sideDrawer">
    <div class="drawer-header text-end border-bottom px-3">
        <span class="close-btn" id="closeDrawer">&times;</span>
    </div>

    <!-- Tabs Nav -->
    <ul class="nav nav-tabs align-items-center" id="myTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#Appointment" type="button" onclick="$('#FORM_NAME').val('create_appointment_form');">Appointment</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#Group" type="button" onclick="$('#FORM_NAME').val('create_group_class_form')">Group Class</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#To-Do" type="button" onclick="$('#FORM_NAME').val('create_to_do_form')">To Do</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#Record" type="button" onclick="$('#FORM_NAME').val('create_record_only_form')">Record Only</button>
        </li>
    </ul>
    <div class="modal-body p-3" style="overflow-y: auto; height: calc(100% - 130px);">
        <input type="hidden" id="FORM_NAME" value="create_appointment_form">

        <!-- Tabs Content -->
        <div class="tab-content" id="myTabContent">
            <div class="tab-pane fade show active" id="Appointment" role="tabpanel">
                <h6 class="mb-4">Individual Appointment</h6>
                <form class="mb-0" id="create_appointment_form" action="partials/store/add_appointment_data.php" method="POST">
                    <input type="hidden" name="START_TIME" id="START_TIME">
                    <input type="hidden" name="END_TIME" id="END_TIME">
                    <input type="hidden" name="PK_LOCATION" id="PK_LOCATION">

                    <div class="row mb-2 align-items-center">
                        <div class="col-4 col-md-4">
                            <div class="d-flex gap-2 align-items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 32 32" viewBox="0 0 32 32" width="25px" height="25px" fill="#ccc">
                                    <path d="m14.545 16.872c3.665 0 6.647-2.982 6.647-6.647s-2.982-6.647-6.647-6.647-6.647 2.982-6.647 6.647 2.982 6.647 6.647 6.647zm0-11.294c2.563 0 4.647 2.084 4.647 4.647s-2.084 4.647-4.647 4.647-4.647-2.084-4.647-4.647 2.085-4.647 4.647-4.647z" />
                                    <path d="m3.15 28.387c.089.024.178.036.266.036.439 0 .841-.292.964-.735 1.253-4.555 5.434-7.736 10.166-7.736 2.11 0 4.146.623 5.888 1.8.458.308 1.079.189 1.389-.269.309-.458.189-1.079-.269-1.389-2.074-1.402-4.497-2.143-7.008-2.143-5.629 0-10.602 3.785-12.094 9.205-.147.533.166 1.084.698 1.231z" />
                                    <path d="m22.766 25.513h1.909v1.909c0 .552.448 1 1 1s1-.448 1-1v-1.909h1.909c.552 0 1-.448 1-1s-.448-1-1-1h-1.909v-1.909c0-.552-.448-1-1-1s-1 .448-1 1v1.909h-1.909c-.552 0-1 .448-1 1s.448 1 1 1z" />
                                </svg>
                                <label class="mb-0"><?= $service_provider_title ?></label>
                            </div>
                        </div>
                        <div class="col-8 col-md-8">
                            <div class="form-group serviceprovider">
                                <select class="form-control" name="PK_SERVICE_PROVIDER" id="PK_SERVICE_PROVIDER" onchange="getSlots(this)" required>
                                    <option value="">Select <?= $service_provider_title ?></option>
                                    <?php
                                    $row = $db->Execute("SELECT DISTINCT (DOA_USERS.PK_USER), CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.ACTIVE FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER LEFT JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER LEFT JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER WHERE DOA_USER_LOCATION.PK_LOCATION IN (" . $DEFAULT_LOCATION_ID . ") AND DOA_USERS.APPEAR_IN_CALENDAR = 1 AND DOA_USERS.ACTIVE = 1 AND DOA_USERS.IS_DELETED = 0 AND DOA_USERS.PK_ACCOUNT_MASTER = " . $_SESSION['PK_ACCOUNT_MASTER'] . " ORDER BY NAME");
                                    while (!$row->EOF) { ?>
                                        <option value="<?php echo $row->fields['PK_USER']; ?>"><?= $row->fields['NAME'] ?></option>
                                    <?php $row->MoveNext();
                                    } ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3 align-items-center">
                        <div class="col-4 col-md-4">
                            <div class="d-flex gap-2 align-items-center">
                                <label class="mb-0" style="margin-left: 33px;">Customer</label>
                            </div>
                        </div>
                        <div class="col-8 col-md-8">
                            <div class="form-group">
                                <select class="form-control customer_select" name="SELECTED_CUSTOMER_ID" id="SELECTED_CUSTOMER_ID" onchange="selectThisCustomerForAppointmentCreation(this);" required>
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
                    <div class="row mb-3 enrollment_area d-none">
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

                    <div class="row mb-3 align-items-center service_area d-none">
                        <div class="col-4 col-md-4">
                            <div class="d-flex gap-2 align-items-center">
                                <label class="mb-0" style="margin-left: 33px;">Service</label>
                            </div>
                        </div>
                        <div class="col-8 col-md-8">
                            <div class="form-group">
                                <select class="form-control" name="PK_SERVICE_MASTER" onchange="selectThisServiceForSchedulingCode(this)">
                                    <option value="">Select Service</option>
                                    <?php
                                    $row = $db_account->Execute("SELECT DISTINCT DOA_SERVICE_CODE.PK_SERVICE_CODE, DOA_SERVICE_CODE.SERVICE_CODE, DOA_SERVICE_MASTER.PK_SERVICE_MASTER, DOA_SERVICE_MASTER.SERVICE_NAME FROM DOA_SERVICE_CODE LEFT JOIN DOA_SERVICE_MASTER ON DOA_SERVICE_CODE.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER WHERE DOA_SERVICE_CODE.IS_GROUP = 0 AND DOA_SERVICE_MASTER.PK_SERVICE_CLASS != 5 AND DOA_SERVICE_MASTER.PK_LOCATION IN (" . $DEFAULT_LOCATION_ID . ") AND DOA_SERVICE_MASTER.IS_DELETED = 0 ORDER BY CASE WHEN SORT_ORDER IS NULL THEN 1 ELSE 0 END, SORT_ORDER ASC");
                                    while (!$row->EOF) { ?>
                                        <option value="<?= $row->fields['PK_SERVICE_MASTER'] . ',' . $row->fields['PK_SERVICE_CODE']; ?>"><?= $row->fields['SERVICE_NAME'] . ' || ' . $row->fields['SERVICE_CODE'] ?></option>
                                    <?php $row->MoveNext();
                                    } ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3 align-items-center schedule_code_area d-none">
                        <div class="col-4 col-md-4">
                            <div class="d-flex gap-2 align-items-center">
                                <label class="mb-0" style="margin-left: 33px;">Scheduling Code</label>
                            </div>
                        </div>
                        <div class="col-8 col-md-8">
                            <div class="form-group">
                                <select class="form-control" id="PK_SCHEDULING_CODE" name="PK_SCHEDULING_CODE" onchange="getSlots(this)" required>
                                    <option value="">Select Scheduling Code</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <hr class="mb-3">
                    <div class="row mb-3">
                        <div class="col-4 col-md-4">
                            <div class="d-flex gap-2 align-items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Capa_1" x="0px" y="0px" viewBox="0 0 55.668 55.668" xml:space="preserve" width="25px" height="22px" fill="#ccc">
                                    <path d="M27.833,0C12.487,0,0,12.486,0,27.834s12.487,27.834,27.833,27.834 c15.349,0,27.834-12.486,27.834-27.834S43.182,0,27.833,0z M27.833,51.957c-13.301,0-24.122-10.821-24.122-24.123 S14.533,3.711,27.833,3.711c13.303,0,24.123,10.821,24.123,24.123S41.137,51.957,27.833,51.957z" />
                                    <path d="M41.618,25.819H29.689V10.046c0-1.025-0.831-1.856-1.855-1.856c-1.023,0-1.854,0.832-1.854,1.856 v19.483h15.638c1.024,0,1.855-0.83,1.854-1.855C43.472,26.65,42.64,25.819,41.618,25.819z" />
                                </svg>
                                <label class="mb-0">Date & Time</label>
                            </div>
                        </div>
                        <div class="col-8 col-md-8">
                            <div class="form-group d-flex gap-3" id="datetime">
                                <input type="text" class="form-control datepicker-normal" name="APPOINTMENT_DATE" id="APPOINTMENT_DATE" style="min-width: 110px;" placeholder="MM/DD/YYYY" required>
                                <!-- <input type="time" class="form-control"> -->
                            </div>
                            <button type="button" class="btn-available fw-semibold f12 bg-transparent p-0 border-0 d-flex align-items-center gap-2 ms-auto mt-2">
                                <span>Show Availability</span>
                                <svg xmlns="http://www.w3.org/2000/svg" id="Layer_1" enable-background="new 0 0 512 512" viewBox="0 0 512 512" width="13px" height="13px" fill="#000">
                                    <path d="m256 374.3c-3 0-6-1.1-8.2-3.4l-213.4-213.3c-4.6-4.6-4.6-11.9 0-16.5s11.9-4.6 16.5 0l205.1 205.1 205.1-205.1c4.6-4.6 11.9-4.6 16.5 0s4.6 11.9 0 16.5l-213.4 213.3c-2.2 2.3-5.2 3.4-8.2 3.4z" />
                                </svg>
                            </button>
                            <div class="slot_div mt-2">

                            </div>

                        </div>
                    </div>
                    <hr class="mb-3">
                    <div class="row mb-3">
                        <div class="col-4 col-md-4">
                            <div class="d-flex gap-2 align-items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Capa_1" x="0px" y="0px" viewBox="0 0 512 512" style="enable-background:new 0 0 512 512;" xml:space="preserve" width="25px" height="20px" fill="#ccc">
                                    <path d="M487.104,24.954c-33.274-33.269-87.129-33.273-120.407,0L51.948,339.665c-2.098,2.097-3.834,4.825-4.831,7.817 L1.057,485.647c-5.2,15.598,9.679,30.503,25.298,25.296l138.182-46.055c2.922-0.974,5.665-2.678,7.819-4.831l314.748-314.711 C520.299,112.154,520.299,58.146,487.104,24.954z M51.654,460.352l23.177-69.525l46.356,46.35L51.654,460.352z M158.214,417.634 l-63.837-63.829l267.272-267.24l63.837,63.83L158.214,417.634z M458.818,117.065l-5.049,5.049l-63.837-63.83l5.049-5.048 c17.602-17.597,46.239-17.597,63.837,0C476.419,70.833,476.419,99.467,458.818,117.065z" />
                                </svg>
                                <label class="mb-0">Public Note</label>
                            </div>
                        </div>
                        <div class="col-8 col-md-8">
                            <div class="form-group">
                                <textarea class="form-control" name="COMMENT"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-4 col-md-4">
                            <div class="d-flex gap-2 align-items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Capa_1" x="0px" y="0px" viewBox="0 0 512 512" style="enable-background:new 0 0 512 512;" xml:space="preserve" width="24px" height="19px" fill="transparent">
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
                    </div>
                </form>
            </div>

            <div class="tab-pane fade" id="Group" role="tabpanel">
                <h6 class="mb-4">Group Class</h6>
                <form class="mb-0" id="create_group_class_form" action="partials/store/add_group_class_data.php" method="POST">
                    <div class="row mb-2 align-items-center">
                        <div class="col-4 col-md-4">
                            <div class="d-flex gap-2 align-items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" id="Line_copy" viewBox="0 0 256 256" width="24px" height="24px" fill="#ccc">
                                    <path d="m195.6347 214.5626c-11.3591.7708-14.3591-7.7292-16.4248-11.7246-1.3408-2.8574-40.3667-96.8164-60.8149-146.1a3 3 0 0 0 -2.771-1.8506h-11.1817a3.0007 3.0007 0 0 0 -2.7339 1.7646l-66.5484 147.238c-1.8423 3.2354-3.3423 9.11-9.5278 9.3135-1.9761.2344-8.44 1.3594-8.44 1.3594a3 3 0 0 0 -3 3v8.9658a3 3 0 0 0 3 3h50.24a3 3 0 0 0 3-3v-9.832a2.9989 2.9989 0 0 0 -2.6455-2.9785c-1.8218-.2168-3.625-.44-5.3882-.6807-3.2568-.4375-3.9121-1.5664-4.0752-2.42 1.1416-3.0869 14.4219-34.1689 15.1626-35.9229h61.8613l13.373 32.2891c.0259.0635 1.5146 3.1858.556 4.2666-1.63 1.8375-6.1216 2.4016-7.7449 2.6611a50.6 50.6 0 0 1 -5.1528.541 3 3 0 0 0 -2.8271 2.9951v9.0811a3 3 0 0 0 3 3h59.7734a3 3 0 0 0 2.9976-2.8848l.3442-8.9658c.109-2.178-1.9691-3.3248-4.0319-3.1154zm-2.1978 8.9658h-53.8862v-3.31c4.0583-.7187 15.3916-1.3854 15.9463-8.793a11.7331 11.7331 0 0 0 -1.2715-6.8281l-14.103-34.0508a3 3 0 0 0 -2.7715-1.8525h-65.8691a3.0006 3.0006 0 0 0 -2.8091 1.9473c-.2061.55-16.4009 37.2471-16.4009 39.6777.0454 2.8057 1.0454 8.3057 12.16 9.0332v4.1758h-44.24v-3.3613c6.001-1.292 12.376-.542 16.4717-6.668a40.798 40.798 0 0 0 3.9546-7.1172l65.7601-145.4937h7.2422c7.24 17.4482 58.5117 140.9912 60.1567 144.4971 3.665 7.4485 7.3317 14.4485 19.7759 15.1182z" />
                                    <path d="m107.7045 91.5695a3 3 0 0 0 -2.7573-1.85 2.9415 2.9415 0 0 0 -2.7734 1.8252l-28.6245 67.25a3 3 0 0 0 2.76 4.1748h56.5581a3 3 0 0 0 2.7705-4.15zm-26.8574 65.4 24.0529-56.5104 23.473 56.5109z" />
                                    <path d="m233.0087 223.2169h-9.8213v-162.3291h9.8213a3 3 0 0 0 0-6h-25.6426a3 3 0 1 0 0 6h9.8213v162.3291h-9.8213a3 3 0 0 0 0 6h25.6426a3 3 0 0 0 0-6z" />
                                    <path d="m17.1913 55.23a3 3 0 0 0 3-3v-9.8217h173.1358v9.8217a3 3 0 1 0 6 0v-25.642a3 3 0 0 0 -6 0v9.82h-173.1358v-9.82a3 3 0 1 0 -6 0v25.642a3 3 0 0 0 3 3z" />
                                </svg>
                                <label class="mb-0">Group Name</label>
                            </div>
                        </div>
                        <div class="col-8 col-md-8">
                            <div class="form-group">
                                <input type="text" name="GROUP_NAME" class="form-control" placeholder="Enter Group Name" required />
                            </div>
                        </div>
                    </div>
                    <hr class="mb-3">
                    <div class="row mb-2 align-items-center">
                        <div class="col-4 col-md-4">
                            <div class="d-flex gap-2 align-items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 32 32" viewBox="0 0 32 32" width="25px" height="25px" fill="#ccc">
                                    <path d="m14.545 16.872c3.665 0 6.647-2.982 6.647-6.647s-2.982-6.647-6.647-6.647-6.647 2.982-6.647 6.647 2.982 6.647 6.647 6.647zm0-11.294c2.563 0 4.647 2.084 4.647 4.647s-2.084 4.647-4.647 4.647-4.647-2.084-4.647-4.647 2.085-4.647 4.647-4.647z" />
                                    <path d="m3.15 28.387c.089.024.178.036.266.036.439 0 .841-.292.964-.735 1.253-4.555 5.434-7.736 10.166-7.736 2.11 0 4.146.623 5.888 1.8.458.308 1.079.189 1.389-.269.309-.458.189-1.079-.269-1.389-2.074-1.402-4.497-2.143-7.008-2.143-5.629 0-10.602 3.785-12.094 9.205-.147.533.166 1.084.698 1.231z" />
                                    <path d="m22.766 25.513h1.909v1.909c0 .552.448 1 1 1s1-.448 1-1v-1.909h1.909c.552 0 1-.448 1-1s-.448-1-1-1h-1.909v-1.909c0-.552-.448-1-1-1s-1 .448-1 1v1.909h-1.909c-.552 0-1 .448-1 1s.448 1 1 1z" />
                                </svg>
                                <label class="mb-0"><?= $service_provider_title ?></label>
                            </div>
                        </div>
                        <div class="col-8 col-md-8">
                            <div class="form-group">
                                <select class="form-control" name="PK_SERVICE_PROVIDER" id="PK_SERVICE_PROVIDER" required>
                                    <option value="">Select <?= $service_provider_title ?></option>
                                    <?php
                                    $row = $db->Execute("SELECT DISTINCT (DOA_USERS.PK_USER), CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.ACTIVE FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER LEFT JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER LEFT JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER WHERE DOA_USER_LOCATION.PK_LOCATION IN (" . $DEFAULT_LOCATION_ID . ") AND DOA_USERS.APPEAR_IN_CALENDAR = 1 AND DOA_USERS.ACTIVE = 1 AND DOA_USERS.IS_DELETED = 0 AND DOA_USERS.PK_ACCOUNT_MASTER = " . $_SESSION['PK_ACCOUNT_MASTER'] . " ORDER BY NAME");
                                    while (!$row->EOF) { ?>
                                        <option value="<?php echo $row->fields['PK_USER']; ?>"><?= $row->fields['NAME'] ?></option>
                                    <?php $row->MoveNext();
                                    } ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3 align-items-center">
                        <div class="col-4 col-md-4">
                            <div class="d-flex gap-2 align-items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 32 32" viewBox="0 0 32 32" width="24px" height="24px" fill="transparent">
                                    <path d="m14.545 16.872c3.665 0 6.647-2.982 6.647-6.647s-2.982-6.647-6.647-6.647-6.647 2.982-6.647 6.647 2.982 6.647 6.647 6.647zm0-11.294c2.563 0 4.647 2.084 4.647 4.647s-2.084 4.647-4.647 4.647-4.647-2.084-4.647-4.647 2.085-4.647 4.647-4.647z" />
                                    <path d="m3.15 28.387c.089.024.178.036.266.036.439 0 .841-.292.964-.735 1.253-4.555 5.434-7.736 10.166-7.736 2.11 0 4.146.623 5.888 1.8.458.308 1.079.189 1.389-.269.309-.458.189-1.079-.269-1.389-2.074-1.402-4.497-2.143-7.008-2.143-5.629 0-10.602 3.785-12.094 9.205-.147.533.166 1.084.698 1.231z" />
                                    <path d="m22.766 25.513h1.909v1.909c0 .552.448 1 1 1s1-.448 1-1v-1.909h1.909c.552 0 1-.448 1-1s-.448-1-1-1h-1.909v-1.909c0-.552-.448-1-1-1s-1 .448-1 1v1.909h-1.909c-.552 0-1 .448-1 1s.448 1 1 1z" />
                                </svg>
                                <label class="mb-0">Service</label>
                            </div>
                        </div>
                        <div class="col-8 col-md-8">
                            <div class="form-group">
                                <select class="form-control" name="SERVICE_ID" onchange="selectThisServiceForSchedulingCode(this)" required>
                                    <option value="">Select Service</option>
                                    <?php
                                    $row = $db_account->Execute("SELECT DISTINCT DOA_SERVICE_CODE.PK_SERVICE_CODE, DOA_SERVICE_CODE.SERVICE_CODE, DOA_SERVICE_MASTER.PK_SERVICE_MASTER, DOA_SERVICE_MASTER.SERVICE_NAME FROM DOA_SERVICE_CODE LEFT JOIN DOA_SERVICE_MASTER ON DOA_SERVICE_CODE.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER WHERE DOA_SERVICE_CODE.IS_GROUP = 1 AND DOA_SERVICE_MASTER.PK_LOCATION IN (" . $DEFAULT_LOCATION_ID . ") AND DOA_SERVICE_MASTER.IS_DELETED = 0 ORDER BY CASE WHEN SORT_ORDER IS NULL THEN 1 ELSE 0 END, SORT_ORDER ASC");
                                    while (!$row->EOF) { ?>
                                        <option value="<?= $row->fields['PK_SERVICE_MASTER'] . ',' . $row->fields['PK_SERVICE_CODE']; ?>"><?= $row->fields['SERVICE_NAME'] . ' || ' . $row->fields['SERVICE_CODE'] ?></option>
                                    <?php $row->MoveNext();
                                    } ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3 align-items-center schedulecode">
                        <div class="col-4 col-md-4">
                            <div class="d-flex gap-2 align-items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 32 32" viewBox="0 0 32 32" width="24px" height="24px" fill="transparent">
                                    <path d="m14.545 16.872c3.665 0 6.647-2.982 6.647-6.647s-2.982-6.647-6.647-6.647-6.647 2.982-6.647 6.647 2.982 6.647 6.647 6.647zm0-11.294c2.563 0 4.647 2.084 4.647 4.647s-2.084 4.647-4.647 4.647-4.647-2.084-4.647-4.647 2.085-4.647 4.647-4.647z" />
                                    <path d="m3.15 28.387c.089.024.178.036.266.036.439 0 .841-.292.964-.735 1.253-4.555 5.434-7.736 10.166-7.736 2.11 0 4.146.623 5.888 1.8.458.308 1.079.189 1.389-.269.309-.458.189-1.079-.269-1.389-2.074-1.402-4.497-2.143-7.008-2.143-5.629 0-10.602 3.785-12.094 9.205-.147.533.166 1.084.698 1.231z" />
                                    <path d="m22.766 25.513h1.909v1.909c0 .552.448 1 1 1s1-.448 1-1v-1.909h1.909c.552 0 1-.448 1-1s-.448-1-1-1h-1.909v-1.909c0-.552-.448-1-1-1s-1 .448-1 1v1.909h-1.909c-.552 0-1 .448-1 1s.448 1 1 1z" />
                                </svg>
                                <label class="mb-0">Scheduling Code</label>
                            </div>
                        </div>
                        <div class="col-8 col-md-8">
                            <div class="form-group">
                                <select class="form-control" id="PK_SCHEDULING_CODE" name="SCHEDULING_CODE" required>
                                    <option value="">Select Scheduling Code</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <hr class="mb-3">
                    <div class="row mb-3">
                        <div class="col-4 col-md-4">
                            <div class="d-flex gap-2 align-items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Capa_1" x="0px" y="0px" viewBox="0 0 55.668 55.668" xml:space="preserve" width="24px" height="19px" fill="#ccc">
                                    <path d="M27.833,0C12.487,0,0,12.486,0,27.834s12.487,27.834,27.833,27.834 c15.349,0,27.834-12.486,27.834-27.834S43.182,0,27.833,0z M27.833,51.957c-13.301,0-24.122-10.821-24.122-24.123 S14.533,3.711,27.833,3.711c13.303,0,24.123,10.821,24.123,24.123S41.137,51.957,27.833,51.957z" />
                                    <path d="M41.618,25.819H29.689V10.046c0-1.025-0.831-1.856-1.855-1.856c-1.023,0-1.854,0.832-1.854,1.856 v19.483h15.638c1.024,0,1.855-0.83,1.854-1.855C43.472,26.65,42.64,25.819,41.618,25.819z" />
                                </svg>
                                <label class="mb-0">Date & Time</label>
                            </div>
                        </div>
                        <div class="col-8 col-md-8 custom-date-time">

                            <div class="datetime-area mt-2 d-none">

                            </div>

                            <div class="form-group d-flex gap-2 align-items-center custom-date-time-at" id="datetime">
                                <input type="text" class="form-control datepicker-normal" id="STARTING_ON" name="GROUP_CLASS_START_DATE" style="min-width: 110px;" placeholder="MM/DD/YYYY">
                                <span class="f14">at</span>
                                <input type="text" class="form-control timepicker-normal" id="GROUP_CLASS_START_TIME" name="GROUP_CLASS_START_TIME">
                            </div>

                            <div class="form-group mt-2 custom-date-time-repeat">
                                <select class="form-control" id="REPEAT" name="REPEAT" onchange="repeatSchedule(this)">
                                    <option value="">-- Select --</option>
                                    <option value="Daily">Daily</option>
                                    <option value="Weekly on Thursday">Weekly on Thursday</option>
                                    <option value="Monday on the first Thursday">Monday on the first Thursday</option>
                                    <option value="NOT_REPEAT" selected>Does not repeat</option>
                                    <option value="Custom">Custom...</option>
                                </select>
                            </div>



                            <div class="custom-date-time-format d-none mt-2">
                                <div class="repeat-box">
                                    <div class="row gx-1">
                                        <div class="col-12 col-lg-6 mb-2">
                                            <label class="theme-text-light">Repeat every:</label>
                                            <div class="d-flex gap-2">
                                                <input type="number" id="LENGTH" class="form-control p-1" style="max-width: 45px; height: 25px;" value="1" min="1">
                                                <select class="form-control form-select p-1" id="FREQUENCY" style="max-width: 85px; height: 25px;">
                                                    <option value="week">Week(S)</option>
                                                    <option value="month">Month(S)</option>
                                                    <option value="year">Year(S)</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-12 col-lg-6 mb-2">
                                            <label class="theme-text-light">Repeat on:</label>
                                            <div class="weekday-radio mt-1">
                                                <label>
                                                    <input type="checkbox" name="sunday">
                                                    <span>S</span>
                                                </label>
                                                <label>
                                                    <input type="checkbox" name="monday">
                                                    <span>M</span>
                                                </label>
                                                <label>
                                                    <input type="checkbox" name="tuesday">
                                                    <span>T</span>
                                                </label>
                                                <label>
                                                    <input type="checkbox" name="wednesday">
                                                    <span>W</span>
                                                </label>
                                                <label>
                                                    <input type="checkbox" name="thursday">
                                                    <span>T</span>
                                                </label>
                                                <label>
                                                    <input type="checkbox" name="friday">
                                                    <span>F</span>
                                                </label>
                                                <label>
                                                    <input type="checkbox" name="saturday">
                                                    <span>S</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-12 col-lg-12">
                                            <div class="ends">
                                                <div>
                                                    <label class="theme-text-light">Ends</label>
                                                </div>
                                                <div class="mb-1">
                                                    <label class="radio active">
                                                        <input type="radio" name="end" checked>
                                                        <span></span>
                                                        Never
                                                    </label>
                                                </div>

                                                <div class="mb-1 d-flex gap-2">
                                                    <label class="radio">
                                                        <input type="radio" name="end">
                                                        <span></span>
                                                        On
                                                    </label>
                                                    <div class="ms-auto">
                                                        <input type="text" id="END_ON_DATE" name="END_ON_DATE" class="form-control datepicker-normal" placeholder="Select Date" disabled>
                                                    </div>
                                                </div>

                                                <div class="mb-1 d-flex gap-2">
                                                    <label class="radio">
                                                        <input type="radio" name="end">
                                                        <span></span>
                                                        After
                                                    </label>
                                                    <div class="ms-auto">
                                                        <input type="number" id="OCCURRENCE_AFTER" name="OCCURRENCE_AFTER" class="form-control" placeholder="0 Occurrences" disabled>
                                                    </div>
                                                </div>
                                            </div>
                                            <a href="javascript:;" class="save-custom-date-selection btn-secondary f12 rounded-2 px-3" onclick="saveCustomDateSelection(this)">Save</a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <button type="button" class="btn-secondary w-100 f12 mt-2 mb-2 d-none add-another-day" onclick="addAnotherDay(this)">Add Another Day & Time</button>
                        </div>
                    </div>
                    <hr class="mb-3">
                    <div class="row mb-3">
                        <div class="col-4 col-md-4">
                            <div class="d-flex gap-2 align-items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Capa_1" x="0px" y="0px" viewBox="0 0 512 512" style="enable-background:new 0 0 512 512;" xml:space="preserve" width="24px" height="18px" fill="#ccc">
                                    <path d="M487.104,24.954c-33.274-33.269-87.129-33.273-120.407,0L51.948,339.665c-2.098,2.097-3.834,4.825-4.831,7.817 L1.057,485.647c-5.2,15.598,9.679,30.503,25.298,25.296l138.182-46.055c2.922-0.974,5.665-2.678,7.819-4.831l314.748-314.711 C520.299,112.154,520.299,58.146,487.104,24.954z M51.654,460.352l23.177-69.525l46.356,46.35L51.654,460.352z M158.214,417.634 l-63.837-63.829l267.272-267.24l63.837,63.83L158.214,417.634z M458.818,117.065l-5.049,5.049l-63.837-63.83l5.049-5.048 c17.602-17.597,46.239-17.597,63.837,0C476.419,70.833,476.419,99.467,458.818,117.065z" />
                                </svg>
                                <label class="mb-0">Public Note</label>
                            </div>
                        </div>
                        <div class="col-8 col-md-8">
                            <div class="form-group">
                                <textarea class="form-control" name="COMMENT"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-4 col-md-4">
                            <div class="d-flex gap-2 align-items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Capa_1" x="0px" y="0px" viewBox="0 0 512 512" style="enable-background:new 0 0 512 512;" xml:space="preserve" width="24px" height="19px" fill="transparent">
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
                    </div>
                </form>
            </div>

            <div class="tab-pane fade" id="To-Do" role="tabpanel">
                <h6 class="mb-4">To Do</h6>
                <form class="mb-0" id="create_to_do_form" action="partials/store/add_to_do_data.php" method="POST">
                    <div class="row mb-2 align-items-center">
                        <div class="col-4 col-md-4">
                            <div class="d-flex gap-2 align-items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" id="Line_copy" viewBox="0 0 256 256" width="24px" height="24px" fill="#ccc">
                                    <path d="m195.6347 214.5626c-11.3591.7708-14.3591-7.7292-16.4248-11.7246-1.3408-2.8574-40.3667-96.8164-60.8149-146.1a3 3 0 0 0 -2.771-1.8506h-11.1817a3.0007 3.0007 0 0 0 -2.7339 1.7646l-66.5484 147.238c-1.8423 3.2354-3.3423 9.11-9.5278 9.3135-1.9761.2344-8.44 1.3594-8.44 1.3594a3 3 0 0 0 -3 3v8.9658a3 3 0 0 0 3 3h50.24a3 3 0 0 0 3-3v-9.832a2.9989 2.9989 0 0 0 -2.6455-2.9785c-1.8218-.2168-3.625-.44-5.3882-.6807-3.2568-.4375-3.9121-1.5664-4.0752-2.42 1.1416-3.0869 14.4219-34.1689 15.1626-35.9229h61.8613l13.373 32.2891c.0259.0635 1.5146 3.1858.556 4.2666-1.63 1.8375-6.1216 2.4016-7.7449 2.6611a50.6 50.6 0 0 1 -5.1528.541 3 3 0 0 0 -2.8271 2.9951v9.0811a3 3 0 0 0 3 3h59.7734a3 3 0 0 0 2.9976-2.8848l.3442-8.9658c.109-2.178-1.9691-3.3248-4.0319-3.1154zm-2.1978 8.9658h-53.8862v-3.31c4.0583-.7187 15.3916-1.3854 15.9463-8.793a11.7331 11.7331 0 0 0 -1.2715-6.8281l-14.103-34.0508a3 3 0 0 0 -2.7715-1.8525h-65.8691a3.0006 3.0006 0 0 0 -2.8091 1.9473c-.2061.55-16.4009 37.2471-16.4009 39.6777.0454 2.8057 1.0454 8.3057 12.16 9.0332v4.1758h-44.24v-3.3613c6.001-1.292 12.376-.542 16.4717-6.668a40.798 40.798 0 0 0 3.9546-7.1172l65.7601-145.4937h7.2422c7.24 17.4482 58.5117 140.9912 60.1567 144.4971 3.665 7.4485 7.3317 14.4485 19.7759 15.1182z" />
                                    <path d="m107.7045 91.5695a3 3 0 0 0 -2.7573-1.85 2.9415 2.9415 0 0 0 -2.7734 1.8252l-28.6245 67.25a3 3 0 0 0 2.76 4.1748h56.5581a3 3 0 0 0 2.7705-4.15zm-26.8574 65.4 24.0529-56.5104 23.473 56.5109z" />
                                    <path d="m233.0087 223.2169h-9.8213v-162.3291h9.8213a3 3 0 0 0 0-6h-25.6426a3 3 0 1 0 0 6h9.8213v162.3291h-9.8213a3 3 0 0 0 0 6h25.6426a3 3 0 0 0 0-6z" />
                                    <path d="m17.1913 55.23a3 3 0 0 0 3-3v-9.8217h173.1358v9.8217a3 3 0 1 0 6 0v-25.642a3 3 0 0 0 -6 0v9.82h-173.1358v-9.82a3 3 0 1 0 -6 0v25.642a3 3 0 0 0 3 3z" />
                                </svg>
                                <label class="mb-0">Title</label>
                            </div>
                        </div>
                        <div class="col-8 col-md-8">
                            <div class="form-group">
                                <input type="text" name="TITLE" class="form-control" placeholder="Enter Title" required />
                            </div>
                        </div>
                    </div>
                    <hr class="mb-3">
                    <div class="row mb-2 align-items-center">
                        <div class="col-4 col-md-4">
                            <div class="d-flex gap-2 align-items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 32 32" viewBox="0 0 32 32" width="25px" height="25px" fill="#ccc">
                                    <path d="m14.545 16.872c3.665 0 6.647-2.982 6.647-6.647s-2.982-6.647-6.647-6.647-6.647 2.982-6.647 6.647 2.982 6.647 6.647 6.647zm0-11.294c2.563 0 4.647 2.084 4.647 4.647s-2.084 4.647-4.647 4.647-4.647-2.084-4.647-4.647 2.085-4.647 4.647-4.647z" />
                                    <path d="m3.15 28.387c.089.024.178.036.266.036.439 0 .841-.292.964-.735 1.253-4.555 5.434-7.736 10.166-7.736 2.11 0 4.146.623 5.888 1.8.458.308 1.079.189 1.389-.269.309-.458.189-1.079-.269-1.389-2.074-1.402-4.497-2.143-7.008-2.143-5.629 0-10.602 3.785-12.094 9.205-.147.533.166 1.084.698 1.231z" />
                                    <path d="m22.766 25.513h1.909v1.909c0 .552.448 1 1 1s1-.448 1-1v-1.909h1.909c.552 0 1-.448 1-1s-.448-1-1-1h-1.909v-1.909c0-.552-.448-1-1-1s-1 .448-1 1v1.909h-1.909c-.552 0-1 .448-1 1s.448 1 1 1z" />
                                </svg>
                                <label class="mb-0"><?= $service_provider_title ?></label>
                            </div>
                        </div>
                        <div class="col-8 col-md-8">
                            <div class="form-group">
                                <select class="multi_sumo_select" name="PK_USER[]" multiple required>
                                    <?php
                                    $row = $db->Execute("SELECT DISTINCT (DOA_USERS.PK_USER), CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.ACTIVE FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER LEFT JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER LEFT JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER WHERE DOA_USER_LOCATION.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_USERS.APPEAR_IN_CALENDAR = 1 AND DOA_USERS.ACTIVE = 1 AND (DOA_USERS.IS_DELETED = 0 OR DOA_USERS.IS_DELETED IS NULL) AND DOA_USERS.PK_ACCOUNT_MASTER = " . $_SESSION['PK_ACCOUNT_MASTER'] . " ORDER BY DOA_USERS.DISPLAY_ORDER ASC");
                                    while (!$row->EOF) { ?>
                                        <option value="<?php echo $row->fields['PK_USER']; ?>"><?= $row->fields['NAME'] ?></option>
                                    <?php $row->MoveNext();
                                    } ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-2 align-items-center schedulecode">
                        <div class="col-4 col-md-4">
                            <div class="d-flex gap-2 align-items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 32 32" viewBox="0 0 32 32" width="24px" height="24px" fill="transparent">
                                    <path d="m14.545 16.872c3.665 0 6.647-2.982 6.647-6.647s-2.982-6.647-6.647-6.647-6.647 2.982-6.647 6.647 2.982 6.647 6.647 6.647zm0-11.294c2.563 0 4.647 2.084 4.647 4.647s-2.084 4.647-4.647 4.647-4.647-2.084-4.647-4.647 2.085-4.647 4.647-4.647z" />
                                    <path d="m3.15 28.387c.089.024.178.036.266.036.439 0 .841-.292.964-.735 1.253-4.555 5.434-7.736 10.166-7.736 2.11 0 4.146.623 5.888 1.8.458.308 1.079.189 1.389-.269.309-.458.189-1.079-.269-1.389-2.074-1.402-4.497-2.143-7.008-2.143-5.629 0-10.602 3.785-12.094 9.205-.147.533.166 1.084.698 1.231z" />
                                    <path d="m22.766 25.513h1.909v1.909c0 .552.448 1 1 1s1-.448 1-1v-1.909h1.909c.552 0 1-.448 1-1s-.448-1-1-1h-1.909v-1.909c0-.552-.448-1-1-1s-1 .448-1 1v1.909h-1.909c-.552 0-1 .448-1 1s.448 1 1 1z" />
                                </svg>
                                <label class="mb-0">Scheduling Code</label>
                            </div>
                        </div>
                        <div class="col-8 col-md-8">
                            <div class="form-group">
                                <select class="form-control form-select" name="PK_SCHEDULING_CODE" id="PK_SCHEDULING_CODE" onchange="calculateEndTime(this)" required>
                                    <option disabled selected>Select Scheduling Code</option>
                                    <?php
                                    $booking_row = $db_account->Execute("SELECT DOA_SCHEDULING_CODE.`PK_SCHEDULING_CODE`, DOA_SCHEDULING_CODE.`SCHEDULING_CODE`, DOA_SCHEDULING_CODE.`SCHEDULING_NAME`, DOA_SCHEDULING_CODE.`DURATION` FROM `DOA_SCHEDULING_CODE` WHERE PK_LOCATION IN (" . $DEFAULT_LOCATION_ID . ") AND DOA_SCHEDULING_CODE.TO_DOS = 1 AND DOA_SCHEDULING_CODE.`ACTIVE` = 1");
                                    while (!$booking_row->EOF) { ?>
                                        <option value="<?php echo $booking_row->fields['PK_SCHEDULING_CODE']; ?>" data-duration="<?php echo $booking_row->fields['DURATION']; ?>" data-scheduling_name="<?php echo $booking_row->fields['SCHEDULING_NAME'] ?>" data-is_default="<?php echo $booking_row->fields['IS_DEFAULT'] ?>"><?= $booking_row->fields['SCHEDULING_NAME'] . ' (' . $booking_row->fields['SCHEDULING_CODE'] . ')' ?></option>
                                    <?php $booking_row->MoveNext();
                                    } ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-2 align-items-center schedulecode">
                        <div class="col-4 col-md-4">
                            <div class="d-flex gap-2 align-items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 32 32" viewBox="0 0 32 32" width="24px" height="24px" fill="transparent">
                                    <path d="m14.545 16.872c3.665 0 6.647-2.982 6.647-6.647s-2.982-6.647-6.647-6.647-6.647 2.982-6.647 6.647 2.982 6.647 6.647 6.647zm0-11.294c2.563 0 4.647 2.084 4.647 4.647s-2.084 4.647-4.647 4.647-4.647-2.084-4.647-4.647 2.085-4.647 4.647-4.647z" />
                                    <path d="m3.15 28.387c.089.024.178.036.266.036.439 0 .841-.292.964-.735 1.253-4.555 5.434-7.736 10.166-7.736 2.11 0 4.146.623 5.888 1.8.458.308 1.079.189 1.389-.269.309-.458.189-1.079-.269-1.389-2.074-1.402-4.497-2.143-7.008-2.143-5.629 0-10.602 3.785-12.094 9.205-.147.533.166 1.084.698 1.231z" />
                                    <path d="m22.766 25.513h1.909v1.909c0 .552.448 1 1 1s1-.448 1-1v-1.909h1.909c.552 0 1-.448 1-1s-.448-1-1-1h-1.909v-1.909c0-.552-.448-1-1-1s-1 .448-1 1v1.909h-1.909c-.552 0-1 .448-1 1s.448 1 1 1z" />
                                </svg>
                                <label class="mb-0">Status</label>
                            </div>
                        </div>
                        <div class="col-8 col-md-8">
                            <div class="form-group">
                                <select class="form-control form-select" required>
                                    <option value="Daily">Not Started</option>
                                    <option value="Weekly on Thursday">In Progress</option>
                                    <option value="Monday on the first Thursday">Complete</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <hr class="mb-3">
                    <div class="row mb-3">
                        <div class="col-4 col-md-4">
                            <div class="d-flex gap-2 align-items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Capa_1" x="0px" y="0px" viewBox="0 0 55.668 55.668" xml:space="preserve" width="24px" height="19px" fill="#ccc">
                                    <path d="M27.833,0C12.487,0,0,12.486,0,27.834s12.487,27.834,27.833,27.834 c15.349,0,27.834-12.486,27.834-27.834S43.182,0,27.833,0z M27.833,51.957c-13.301,0-24.122-10.821-24.122-24.123 S14.533,3.711,27.833,3.711c13.303,0,24.123,10.821,24.123,24.123S41.137,51.957,27.833,51.957z" />
                                    <path d="M41.618,25.819H29.689V10.046c0-1.025-0.831-1.856-1.855-1.856c-1.023,0-1.854,0.832-1.854,1.856 v19.483h15.638c1.024,0,1.855-0.83,1.854-1.855C43.472,26.65,42.64,25.819,41.618,25.819z" />
                                </svg>
                                <label class="mb-0">Date & Time</label>
                            </div>
                        </div>
                        <div class="col-8 col-md-8">
                            <div class="form-group d-flex gap-2 align-items-center" id="datetime">
                                <input type="text" name="DATE" class="form-control datepicker-normal" style="min-width: 110px;" required>
                                <span class="f14">at</span>
                                <input type="text" id="TO_DO_START_TIME" name="START_TIME" class="form-control time-picker" onchange="calculateEndTime(this)" required>
                                <span class="f14">to</span>
                                <input type="text" id="TO_DO_END_TIME" name="END_TIME" class="form-control time-picker" required>
                            </div>
                            <label class="custom-checkbox float-start mt-2 mb-2">
                                <input type="checkbox">
                                <span class="checkmark"></span>
                                All Day
                            </label>
                            <!-- <button type="button" class="btn-available fw-semibold f12 bg-transparent p-0 border-0 d-flex align-items-center gap-2 ms-auto mt-2">
                                <span>Show Availability</span>
                                <svg xmlns="http://www.w3.org/2000/svg" id="Layer_1" enable-background="new 0 0 512 512" viewBox="0 0 512 512" width="13px" height="13px" fill="#000">
                                    <path d="m256 374.3c-3 0-6-1.1-8.2-3.4l-213.4-213.3c-4.6-4.6-4.6-11.9 0-16.5s11.9-4.6 16.5 0l205.1 205.1 205.1-205.1c4.6-4.6 11.9-4.6 16.5 0s4.6 11.9 0 16.5l-213.4 213.3c-2.2 2.3-5.2 3.4-8.2 3.4z" />
                                </svg>
                            </button>
                            <div class="Availabilityarea mt-2" style="display: none;">
                                <span>08:00 AM - 09:00 AM</span>
                                <span>09:00 AM - 10:00 AM</span>
                                <span>10:00 AM - 11:00 AM</span>
                                <span>04:00 PM - 05:00 PM</span>
                                <span>05:00 PM - 06:00 PM</span>
                                <span>06:00 PM - 07:00 PM</span>
                            </div> -->
                            <div class="form-group mt-2">
                                <select class="form-control form-select">
                                    <option value="" selected disabled>-- Select --</option>
                                    <option value="Daily">Daily</option>
                                    <option value="Weekly on Thursday">Weekly on Thursday</option>
                                    <option value="Monday on the first Thursday">Monday on the first Thursday</option>
                                    <option value="Does not repeat">Does not repeat</option>
                                    <option value="Custom">Custom</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <hr class="mb-3">
                    <div class="row mb-3">
                        <div class="col-4 col-md-4">
                            <div class="d-flex gap-2 align-items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Capa_1" x="0px" y="0px" viewBox="0 0 512 512" style="enable-background:new 0 0 512 512;" xml:space="preserve" width="24px" height="18px" fill="#ccc">
                                    <path d="M487.104,24.954c-33.274-33.269-87.129-33.273-120.407,0L51.948,339.665c-2.098,2.097-3.834,4.825-4.831,7.817 L1.057,485.647c-5.2,15.598,9.679,30.503,25.298,25.296l138.182-46.055c2.922-0.974,5.665-2.678,7.819-4.831l314.748-314.711 C520.299,112.154,520.299,58.146,487.104,24.954z M51.654,460.352l23.177-69.525l46.356,46.35L51.654,460.352z M158.214,417.634 l-63.837-63.829l267.272-267.24l63.837,63.83L158.214,417.634z M458.818,117.065l-5.049,5.049l-63.837-63.83l5.049-5.048 c17.602-17.597,46.239-17.597,63.837,0C476.419,70.833,476.419,99.467,458.818,117.065z" />
                                </svg>
                                <label class="mb-0">Description</label>
                            </div>
                        </div>
                        <div class="col-8 col-md-8">
                            <div class="form-group">
                                <textarea class="form-control" name="DESCRIPTION"></textarea>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="tab-pane fade" id="Record" role="tabpanel">
                <h6 class="mb-4">Record Only</h6>
                <form class="mb-0" id="create_record_only_form" action="partials/store/add_record_only_data.php" method="POST">
                    <input type="hidden" name="START_TIME" id="START_TIME">
                    <input type="hidden" name="END_TIME" id="END_TIME">
                    <input type="hidden" name="PK_LOCATION" id="PK_LOCATION">

                    <div class="row mb-2 align-items-center">
                        <div class="col-4 col-md-4">
                            <div class="d-flex gap-2 align-items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 32 32" viewBox="0 0 32 32" width="25px" height="25px" fill="#ccc">
                                    <path d="m14.545 16.872c3.665 0 6.647-2.982 6.647-6.647s-2.982-6.647-6.647-6.647-6.647 2.982-6.647 6.647 2.982 6.647 6.647 6.647zm0-11.294c2.563 0 4.647 2.084 4.647 4.647s-2.084 4.647-4.647 4.647-4.647-2.084-4.647-4.647 2.085-4.647 4.647-4.647z" />
                                    <path d="m3.15 28.387c.089.024.178.036.266.036.439 0 .841-.292.964-.735 1.253-4.555 5.434-7.736 10.166-7.736 2.11 0 4.146.623 5.888 1.8.458.308 1.079.189 1.389-.269.309-.458.189-1.079-.269-1.389-2.074-1.402-4.497-2.143-7.008-2.143-5.629 0-10.602 3.785-12.094 9.205-.147.533.166 1.084.698 1.231z" />
                                    <path d="m22.766 25.513h1.909v1.909c0 .552.448 1 1 1s1-.448 1-1v-1.909h1.909c.552 0 1-.448 1-1s-.448-1-1-1h-1.909v-1.909c0-.552-.448-1-1-1s-1 .448-1 1v1.909h-1.909c-.552 0-1 .448-1 1s.448 1 1 1z" />
                                </svg>
                                <label class="mb-0"><?= $service_provider_title ?></label>
                            </div>
                        </div>
                        <div class="col-8 col-md-8">
                            <div class="form-group serviceprovider">
                                <select class="form-control" name="PK_SERVICE_PROVIDER" id="PK_SERVICE_PROVIDER" onchange="getSlots(this)" required>
                                    <option value="">Select <?= $service_provider_title ?></option>
                                    <?php
                                    $row = $db->Execute("SELECT DISTINCT (DOA_USERS.PK_USER), CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.ACTIVE FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER LEFT JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER LEFT JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER WHERE DOA_USER_LOCATION.PK_LOCATION IN (" . $DEFAULT_LOCATION_ID . ") AND DOA_USERS.APPEAR_IN_CALENDAR = 1 AND DOA_USERS.ACTIVE = 1 AND DOA_USERS.IS_DELETED = 0 AND DOA_USERS.PK_ACCOUNT_MASTER = " . $_SESSION['PK_ACCOUNT_MASTER'] . " ORDER BY NAME");
                                    while (!$row->EOF) { ?>
                                        <option value="<?php echo $row->fields['PK_USER']; ?>"><?= $row->fields['NAME'] ?></option>
                                    <?php $row->MoveNext();
                                    } ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3 align-items-center">
                        <div class="col-4 col-md-4">
                            <div class="d-flex gap-2 align-items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 32 32" viewBox="0 0 32 32" width="24px" height="24px" fill="transparent">
                                    <path d="m14.545 16.872c3.665 0 6.647-2.982 6.647-6.647s-2.982-6.647-6.647-6.647-6.647 2.982-6.647 6.647 2.982 6.647 6.647 6.647zm0-11.294c2.563 0 4.647 2.084 4.647 4.647s-2.084 4.647-4.647 4.647-4.647-2.084-4.647-4.647 2.085-4.647 4.647-4.647z" />
                                    <path d="m3.15 28.387c.089.024.178.036.266.036.439 0 .841-.292.964-.735 1.253-4.555 5.434-7.736 10.166-7.736 2.11 0 4.146.623 5.888 1.8.458.308 1.079.189 1.389-.269.309-.458.189-1.079-.269-1.389-2.074-1.402-4.497-2.143-7.008-2.143-5.629 0-10.602 3.785-12.094 9.205-.147.533.166 1.084.698 1.231z" />
                                    <path d="m22.766 25.513h1.909v1.909c0 .552.448 1 1 1s1-.448 1-1v-1.909h1.909c.552 0 1-.448 1-1s-.448-1-1-1h-1.909v-1.909c0-.552-.448-1-1-1s-1 .448-1 1v1.909h-1.909c-.552 0-1 .448-1 1s.448 1 1 1z" />
                                </svg>
                                <label class="mb-0">Customer</label>
                            </div>
                        </div>
                        <div class="col-8 col-md-8">
                            <div class="form-group">
                                <select class="form-control customer_select" name="SELECTED_CUSTOMER_ID" id="SELECTED_CUSTOMER_ID" required>
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

                    <input type="hidden" name="PK_SERVICE_MASTER" value="<?= $PK_SERVICE_MASTER ?>">
                    <input type="hidden" name="PK_SERVICE_CODE" value="<?= $PK_SERVICE_CODE ?>">

                    <div class="row mb-3 align-items-center">
                        <div class="col-4 col-md-4">
                            <div class="d-flex gap-2 align-items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 32 32" viewBox="0 0 32 32" width="24px" height="24px" fill="transparent">
                                    <path d="m14.545 16.872c3.665 0 6.647-2.982 6.647-6.647s-2.982-6.647-6.647-6.647-6.647 2.982-6.647 6.647 2.982 6.647 6.647 6.647zm0-11.294c2.563 0 4.647 2.084 4.647 4.647s-2.084 4.647-4.647 4.647-4.647-2.084-4.647-4.647 2.085-4.647 4.647-4.647z" />
                                    <path d="m3.15 28.387c.089.024.178.036.266.036.439 0 .841-.292.964-.735 1.253-4.555 5.434-7.736 10.166-7.736 2.11 0 4.146.623 5.888 1.8.458.308 1.079.189 1.389-.269.309-.458.189-1.079-.269-1.389-2.074-1.402-4.497-2.143-7.008-2.143-5.629 0-10.602 3.785-12.094 9.205-.147.533.166 1.084.698 1.231z" />
                                    <path d="m22.766 25.513h1.909v1.909c0 .552.448 1 1 1s1-.448 1-1v-1.909h1.909c.552 0 1-.448 1-1s-.448-1-1-1h-1.909v-1.909c0-.552-.448-1-1-1s-1 .448-1 1v1.909h-1.909c-.552 0-1 .448-1 1s.448 1 1 1z" />
                                </svg>
                                <label class="mb-0">Scheduling Code</label>
                            </div>
                        </div>
                        <div class="col-8 col-md-8">
                            <div class="form-group">
                                <select class="form-control" id="PK_SCHEDULING_CODE" name="PK_SCHEDULING_CODE" onchange="getSlots(this)" required>
                                    <option value="">Select Scheduling Code</option>
                                    <?php
                                    $row = $db_account->Execute("SELECT DOA_SCHEDULING_CODE.`PK_SCHEDULING_CODE`, DOA_SCHEDULING_CODE.`SCHEDULING_CODE`, DOA_SCHEDULING_CODE.`SCHEDULING_NAME`, DOA_SCHEDULING_CODE.`DURATION` FROM `DOA_SCHEDULING_CODE` LEFT JOIN DOA_SCHEDULING_SERVICE ON DOA_SCHEDULING_CODE.PK_SCHEDULING_CODE = DOA_SCHEDULING_SERVICE.PK_SCHEDULING_CODE WHERE DOA_SCHEDULING_CODE.PK_LOCATION IN (" . $DEFAULT_LOCATION_ID . ") AND DOA_SCHEDULING_CODE.`ACTIVE` = 1 AND DOA_SCHEDULING_SERVICE.PK_SERVICE_MASTER=" . $PK_SERVICE_MASTER . " ORDER BY CASE WHEN DOA_SCHEDULING_CODE.SORT_ORDER IS NULL THEN 1 ELSE 0 END, DOA_SCHEDULING_CODE.SORT_ORDER");
                                    while (!$row->EOF) { ?>
                                        <option data-duration="<?= $row->fields['DURATION']; ?>" value="<?= $row->fields['PK_SCHEDULING_CODE'] . ',' . $row->fields['DURATION'] ?>"><?= $row->fields['SCHEDULING_NAME'] . ' (' . $row->fields['SCHEDULING_CODE'] . ')' ?></option>
                                    <?php $row->MoveNext();
                                    } ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <hr class="mb-3">
                    <div class="row mb-3">
                        <div class="col-4 col-md-4">
                            <div class="d-flex gap-2 align-items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Capa_1" x="0px" y="0px" viewBox="0 0 55.668 55.668" xml:space="preserve" width="25px" height="22px" fill="#ccc">
                                    <path d="M27.833,0C12.487,0,0,12.486,0,27.834s12.487,27.834,27.833,27.834 c15.349,0,27.834-12.486,27.834-27.834S43.182,0,27.833,0z M27.833,51.957c-13.301,0-24.122-10.821-24.122-24.123 S14.533,3.711,27.833,3.711c13.303,0,24.123,10.821,24.123,24.123S41.137,51.957,27.833,51.957z" />
                                    <path d="M41.618,25.819H29.689V10.046c0-1.025-0.831-1.856-1.855-1.856c-1.023,0-1.854,0.832-1.854,1.856 v19.483h15.638c1.024,0,1.855-0.83,1.854-1.855C43.472,26.65,42.64,25.819,41.618,25.819z" />
                                </svg>
                                <label class="mb-0">Date & Time</label>
                            </div>
                        </div>
                        <div class="col-8 col-md-8">
                            <div class="form-group d-flex gap-3" id="datetime">
                                <input type="text" class="form-control datepicker-normal" name="APPOINTMENT_DATE" id="TO_DO_APPOINTMENT_DATE" style="min-width: 110px;" placeholder="MM/DD/YYYY" required>
                                <!-- <input type="time" class="form-control"> -->
                            </div>
                            <button type="button" class="btn-available fw-semibold f12 bg-transparent p-0 border-0 d-flex align-items-center gap-2 ms-auto mt-2">
                                <span>Show Availability</span>
                                <svg xmlns="http://www.w3.org/2000/svg" id="Layer_1" enable-background="new 0 0 512 512" viewBox="0 0 512 512" width="13px" height="13px" fill="#000">
                                    <path d="m256 374.3c-3 0-6-1.1-8.2-3.4l-213.4-213.3c-4.6-4.6-4.6-11.9 0-16.5s11.9-4.6 16.5 0l205.1 205.1 205.1-205.1c4.6-4.6 11.9-4.6 16.5 0s4.6 11.9 0 16.5l-213.4 213.3c-2.2 2.3-5.2 3.4-8.2 3.4z" />
                                </svg>
                            </button>
                            <div class="slot_div mt-2">

                            </div>
                        </div>
                    </div>
                    <hr class="mb-3">
                    <div class="row mb-3">
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
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal-footer flex-nowrap border-top">
        <button type="button" class="btn-secondary w-100 m-1" id="closeDrawer">Cancel</button>
        <button type="button" class="btn-primary w-100 m-1" onclick="submitAppointmentForm()">Save</button>
    </div>
</div>


<!-- End Individual Appointment -->
<script>
    function submitAppointmentForm() {
        let form_name = $('#FORM_NAME').val();
        let form = $('#' + form_name);

        if (!form[0].checkValidity()) {
            form[0].reportValidity();
            return false;
        }

        form.submit();
    }

    let start_time_array = [];
    let end_time_array = [];

    function selectThisCustomerForAppointmentCreation(param) {
        let PK_USER_MASTER = $(param).val();
        $('#create_appointment_form .enrollment_area').removeClass('d-none');
        $('#create_appointment_form .schedule_code_area').removeClass('d-none');
        $.ajax({
            url: "ajax/get_enrollments.php",
            type: "POST",
            data: {
                PK_USER_MASTER: PK_USER_MASTER
            },
            async: false,
            cache: false,
            success: function(result) {
                $('#create_appointment_form #enrollment_div').html(result);
            }
        });
    }

    function selectThisEnrollment(param) {
        if ($(param).val() == 'AD-HOC') {
            $('.service_area').removeClass('d-none');
            $('#PK_SERVICE_MASTER').val('');
            $('#PK_SCHEDULING_CODE').html('<option value="">Select Scheduling Code</option>');
        } else {
            $('.service_area').addClass('d-none');
            let PK_ENROLLMENT_MASTER = $(param).val();
            let no_of_session = $(param).data('no_of_session');
            let used_session = $(param).data('used_session');

            $.ajax({
                url: "ajax/get_scheduling_codes.php",
                type: "POST",
                data: {
                    PK_ENROLLMENT_MASTER: PK_ENROLLMENT_MASTER,
                    no_of_session: no_of_session,
                    used_session: used_session
                },
                async: false,
                cache: false,
                success: function(result) {
                    $('#PK_SCHEDULING_CODE').html(result);
                }
            });
        }
    }

    function selectThisServiceForSchedulingCode(param) {
        let PK_SERVICE_MASTER = $(param).val();
        $.ajax({
            url: "ajax/get_scheduling_codes.php",
            type: "POST",
            data: {
                PK_SERVICE_MASTER: PK_SERVICE_MASTER
            },
            async: false,
            cache: false,
            success: function(result) {
                $(param).closest('.tab-pane').find('#PK_SCHEDULING_CODE').html(result);
            }
        });
    }

    function getSlots(param) {
        let PK_SERVICE_PROVIDER = $(param).closest('.tab-pane').find('#PK_SERVICE_PROVIDER').val();
        let PK_LOCATION = $(param).closest('.tab-pane').find('#SELECTED_CUSTOMER_ID').find(':selected').data('location_id');
        $(param).closest('.tab-pane').find('#PK_LOCATION').val(PK_LOCATION);
        let duration = $(param).closest('.tab-pane').find('#PK_SCHEDULING_CODE').find(':selected').data('duration');

        let form_name = $('#FORM_NAME').val();
        let selected_date = '';
        if (form_name == 'create_appointment_form') {
            selected_date = $('#APPOINTMENT_DATE').val();
        } else {
            selected_date = $('#TO_DO_APPOINTMENT_DATE').val();
        }

        let dateObj = new Date(selected_date);
        let day = String(dateObj.getDate()).padStart(2, '0');
        let month = String(dateObj.getMonth() + 1).padStart(2, '0');
        let year = dateObj.getFullYear();
        let date = `${year}-${month}-${day}`;

        let START_TIME = '';
        let END_TIME = '';

        if (parseInt(PK_SERVICE_PROVIDER) > 0 && parseInt(duration) > 0 && day > 0) {
            start_time_array = [];
            end_time_array = [];
            $.ajax({
                url: "ajax/get_slots.php",
                type: "POST",
                data: {
                    SERVICE_PROVIDER_ID: PK_SERVICE_PROVIDER,
                    PK_LOCATION: PK_LOCATION,
                    duration: duration,
                    day: day,
                    date: date,
                    START_TIME: START_TIME,
                    END_TIME: END_TIME,
                    slot_time: ''
                },
                async: false,
                cache: false,
                success: function(result) {
                    $(param).closest('.tab-pane').find('.slot_div').html(result);
                }
            });
        } else {
            $(param).closest('.tab-pane').find('.slot_div').html('');
        }
    }

    function selectSlot(param, id, start_time, end_time) {
        if ($(param).data('is_selected') == 0) {
            start_time_array.push(start_time);
            end_time_array.push(end_time);
            $(param).closest('.tab-pane').find('#START_TIME').val(start_time_array.sort());
            $(param).closest('.tab-pane').find('#END_TIME').val(end_time_array.sort());
            $(param).closest('.tab-pane').find('#slot_btn_' + id).data('is_selected', 1);
            document.getElementById('slot_btn_' + id).style.setProperty('background-color', '#39b54a', 'important');
            document.getElementById('slot_btn_' + id).style.setProperty('color', '#fff', 'important');
        } else {
            const start_time_index = start_time_array.indexOf(start_time);
            if (start_time_index > -1) {
                start_time_array.splice(start_time_index, 1);
            }

            const end_time_index = end_time_array.indexOf(end_time);
            if (end_time_index > -1) {
                end_time_array.splice(end_time_index, 1);
            }

            $(param).closest('.tab-pane').find('#START_TIME').val(start_time_array.sort());
            $(param).closest('.tab-pane').find('#END_TIME').val(end_time_array.sort());
            $(param).closest('.tab-pane').find('#slot_btn_' + id).data('is_selected', 0);
            document.getElementById('slot_btn_' + id).style.setProperty('background-color', '#f8f9fa', 'important');
            document.getElementById('slot_btn_' + id).style.setProperty('color', '#000', 'important');
        }

        console.log(start_time_array.sort(), end_time_array.sort());
    }



    function repeatSchedule(param) {
        if ($(param).val() == 'Custom') {
            $('.custom-date-time-format').removeClass("d-none");
        } else {
            $('.custom-date-time-format').addClass('d-none');
        }
    }

    function addAnotherDay(param) {
        $('.add-another-day').addClass('d-none');
        $('.custom-date-time-at').removeClass('d-none');
        $('.custom-date-time-format').removeClass("d-none");
    }


    function formatDate(dateStr) {
        const date = new Date(dateStr);
        return date.toLocaleDateString('en-US', {
            weekday: 'short',
            month: 'short',
            day: 'numeric'
        });
    }

    function formatTime(timeStr) {
        if (!timeStr) return '';

        if (timeStr.toLowerCase().includes('am') || timeStr.toLowerCase().includes('pm')) {
            return timeStr;
        }

        const [hours, minutes] = timeStr.split(':');
        const date = new Date();
        date.setHours(hours, minutes);

        return date.toLocaleTimeString('en-US', {
            hour: 'numeric',
            minute: '2-digit'
        });
    }

    function getSelectedDays() {
        const map = {
            sunday: 'Sunday',
            monday: 'Monday',
            tuesday: 'Tuesday',
            wednesday: 'Wednesday',
            thursday: 'Thursday',
            friday: 'Friday',
            saturday: 'Saturday'
        };

        let days = [];
        $('.weekday-radio input:checked').each(function() {
            days.push(map[this.name]);
        });

        return days;
    }

    function saveCustomDateSelection(param) {

        $('.datetime-area').removeClass('d-none');
        $('.add-another-day').removeClass('d-none');
        $('.custom-date-time-at').addClass('d-none');
        $('.custom-date-time-repeat').addClass('d-none');
        $('.custom-date-time-format').addClass('d-none');


        const startDate = $('#STARTING_ON').val(); // MM/DD/YYYY
        const startTime = $('#GROUP_CLASS_START_TIME').val(); // HH:mm
        const repeatEvery = $('.repeat-box input[type="number"]').first().val();
        const repeatType = $('.repeat-box select').val(); // week / month / year

        const LENGTH = $('#LENGTH').val();
        const FREQUENCY = $('#FREQUENCY').val();


        const days = getSelectedDays();

        // End condition
        let endText = 'Never Ends';
        const endType = $('input[name="end"]:checked').closest('.radio').text().trim();

        if (endType === 'On') {
            let END_ON_DATE = $('#END_ON_DATE').val();
            endText = `Ends on ${END_ON_DATE}`;
        } else if (endType === 'After') {
            let occurrences = $('#OCCURRENCE_AFTER').val();
            endText = `Ends after ${occurrences} occurrences`;
        }

        // Build text
        const dateText = formatDate(startDate);
        const timeText = formatTime(startTime);

        const frequency =
            repeatType === 'week' ? 'Weekly' :
            repeatType === 'month' ? 'Monthly' : 'Yearly';

        const dayText = days.length ? days.join(', ') : 'selected days';

        const html = `<div class="datetime-item f12 bg-light p-2 border rounded-2 d-flex mb-2">
                            <input type="hidden" name="STARTING_ON[]" value="${startDate}">
                            <input type="hidden" name="LENGTH[]" value="${LENGTH}">
                            <input type="hidden" name="FREQUENCY[]" value="${FREQUENCY}">
                            <input type="hidden" name="START_TIME[]" value="${startTime}">

                            <input type="hidden" name="DAYS[]" value="${dayText}">
                            <div>
                                <p class="text-dark fw-semibold mb-0">
                                    ${dateText}, ${timeText}
                                </p>
                                <span class="text-muted f10">
                                    ${frequency} on ${dayText} - ${endText}
                                </span>
                            </div>
                            <div class="d-flex gap-2 ms-auto align-items-start">
                                <a title="Delete" href="javascript:" class="delete-btn" onclick="this.closest('.datetime-item').remove()">
                                    <i class="fa fa-trash" aria-hidden="true"></i>
                                </a>
                            </div>
                        </div>`;

        // Append or replace wherever you want
        $('.datetime-area').append(html);
    }







    function calculateEndTime() {
        let start_time = $('#TO_DO_START_TIME').val();
        let duration = $('#PK_SCHEDULING_CODE').find(':selected').data('duration');
        let scheduling_name = $('#PK_SCHEDULING_CODE').find(':selected').data('scheduling_name');
        let is_default = $('#PK_SCHEDULING_CODE').find(':selected').data('is_default');
        $('#TITLE').val(scheduling_name);
        duration = (duration) ? duration : 0;

        if (start_time && duration) {
            start_time = moment(start_time, ["h:mm A"]).format("HH:mm");
            let end_time = addMinutes(start_time, duration);
            end_time = moment(end_time, ["HH:mm"]).format("h:mm A");
            $('#TO_DO_END_TIME').val(end_time);
        }

        if (is_default === 1) {
            $('.customer_div').show();
        } else {
            $('.customer_div').hide();
        }
    }
</script>