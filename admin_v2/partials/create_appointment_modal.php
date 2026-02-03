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
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#Appointment" type="button">Appointment</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#Group" type="button">Group Class</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#DO" type="button">TO DO</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#Record" type="button">Record Only</button>
        </li>
    </ul>
    <div class="modal-body p-3" style="overflow-y: auto; height: calc(100% - 130px);">

        <!-- Tabs Content -->
        <div class="tab-content" id="myTabContent">
            <div class="tab-pane fade show active" id="Appointment" role="tabpanel">
                <h6 class="mb-4">Individual Appointment</h6>
                <form class="mb-0" id="create_appointment_form" action="partials/store/add_appointment_data.php" method="POST">
                    <input type="hidden" name="START_TIME" id="START_TIME">
                    <input type="hidden" name="END_TIME" id="END_TIME">

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
                                <select class="form-control" name="PK_SERVICE_PROVIDER" id="PK_SERVICE_PROVIDER" onchange="getSlots()" required>
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
                                <select class="form-control customer_select" name="SELECTED_CUSTOMER_ID" id="SELECTED_CUSTOMER_ID" onchange="selectThisCustomer(this);" required>
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
                                <select class="form-control" required name="PK_SERVICE_MASTER" onchange="selectThisService(this)">
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
                                <select class="form-control" id="PK_SCHEDULING_CODE" name="PK_SCHEDULING_CODE" onchange="getSlots()" required>
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
                                <input type="text" class="form-control datepicker-normal" name="APPOINTMENT_DATE" id="APPOINTMENT_DATE" style="min-width: 110px;" required>
                                <input type="time" class="form-control">
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

                    <div class="row" style="margin-top: 42%;">
                        <div class="col-6 col-md-6">
                            <button type="submit" class="btn-secondary w-100 m-1">Cancel</button>
                        </div>
                        <div class="col-6 col-md-6">
                            <button type="submit" class="btn-primary w-100 m-1">Save</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="tab-pane fade" id="Group" role="tabpanel">
                <h6 class="mb-4">Group Class</h6>
                <form class="mb-0" id="create_appointment_form" action="partials/store/add_appointment_data.php" method="POST">
                    <input type="hidden" name="START_TIME" id="START_TIME">
                    <input type="hidden" name="END_TIME" id="END_TIME">

                    <div class="row mb-3 align-items-center">
                        <div class="col-4 col-md-4">
                            <div class="d-flex gap-2 align-items-center">
                                <label class="mb-0" style="margin-left: 33px;">Group Name</label>
                            </div>
                        </div>
                        <div class="col-8 col-md-8">
                            <div class="form-group">
                                <input type="text" class="form-control" name="GROUP_NAME" id="GROUP_NAME" required>
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
                            <div class="form-group serviceprovider">
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
                                <label class="mb-0" style="margin-left: 33px;">Service</label>
                            </div>
                        </div>
                        <div class="col-8 col-md-8">
                            <div class="form-group">
                                <select class="form-control" class="multi_select" required name="SERVICE_ID" onchange="selectThisService(this)">
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
                    <div class="row mb-3 align-items-center schedule_code_area d-none">
                        <div class="col-4 col-md-4">
                            <div class="d-flex gap-2 align-items-center">
                                <label class="mb-0" style="margin-left: 33px;">Scheduling Code</label>
                            </div>
                        </div>
                        <div class="col-8 col-md-8">
                            <div class="form-group">
                                <select class="form-control" id="PK_SCHEDULING_CODE" name="PK_SCHEDULING_CODE" onchange="getSlots()" required>
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
                                <input type="text" class="form-control datepicker-normal" name="APPOINTMENT_DATE" id="APPOINTMENT_DATE" style="min-width: 110px;" required>
                                <label class="mt-2">at</label><input type="time" class="form-control">
                            </div>
                            <button type="button" class="btn-available fw-semibold f12 bg-transparent p-0 border-0 d-flex align-items-center gap-2 ms-auto mt-2">
                                <span>Show Availability</span>
                                <svg xmlns="http://www.w3.org/2000/svg" id="Layer_1" enable-background="new 0 0 512 512" viewBox="0 0 512 512" width="13px" height="13px" fill="#000">
                                    <path d="m256 374.3c-3 0-6-1.1-8.2-3.4l-213.4-213.3c-4.6-4.6-4.6-11.9 0-16.5s11.9-4.6 16.5 0l205.1 205.1 205.1-205.1c4.6-4.6 11.9-4.6 16.5 0s4.6 11.9 0 16.5l-213.4 213.3c-2.2 2.3-5.2 3.4-8.2 3.4z" />
                                </svg>
                            </button>
                            <div class="slot_div mt-2">

                            </div>
                            <div>
                                <select class="form-control" id="" name="">
                                    <option value="" selected disabled hidden>Does not repeat</option>
                                    <option value="">Daily</option>
                                    <option value="">Weekly on Thursday</option>
                                    <option value="">Monthly on the first Thursday</option>
                                    <option value="">Custom...</option>
                                </select>
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

                    <div class="row" style="margin-top: 42%;">
                        <div class="col-6 col-md-6">
                            <button type="submit" class="btn-secondary w-100 m-1">Cancel</button>
                        </div>
                        <div class="col-6 col-md-6">
                            <button type="submit" class="btn-primary w-100 m-1">Save</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="tab-pane fade" id="DO" role="tabpanel">
                <div class="nodata text-center p-3">
                    <svg xmlns="http://www.w3.org/2000/svg" id="Layer_1" enable-background="new 0 0 64 64" viewBox="0 0 64 64" width="50px" height="50px" fill="#ccc">
                        <path d="m64 38.139c0-.063-.058-.1-.066-.16-.013-.178-.035-.355-.066-.53l-8.536-34.14c-.485-1.948-2.237-3.314-4.245-3.309h-38.174c-2.008-.005-3.76 1.361-4.245 3.309l-8.536 34.146c-.031.173-.052.348-.065.523-.009.06-.067.1-.067.161 0 .033.033.053.036.087-.007.1-.036.193-.036.29v21.108c.005 2.415 1.961 4.371 4.376 4.376h55.248c2.415-.005 4.371-1.961 4.376-4.376v-21.108c0-.1-.029-.194-.036-.291.003-.033.036-.053.036-.086zm-52.8-34.194c.197-.786.905-1.335 1.715-1.331h38.172c.809-.003 1.516.546 1.713 1.331l8.225 32.887h-16.748c-.721.002-1.305.586-1.307 1.307v4.831h-21.94v-4.831c-.002-.721-.586-1.305-1.307-1.307h-16.748zm48.424 57.441h-55.248c-.972-.002-1.76-.79-1.762-1.762v-20.179h15.8v4.832c.001.721.586 1.306 1.307 1.307h24.556c.721-.001 1.306-.586 1.307-1.307v-4.832h15.8v20.179c-.002.972-.788 1.759-1.76 1.762z" />
                    </svg>
                    <span class="d-block pt-2 f12 text-muted">No Data</span>
                </div>
            </div>
            <div class="tab-pane fade" id="Record" role="tabpanel">
                <div class="nodata text-center p-3">
                    <svg xmlns="http://www.w3.org/2000/svg" id="Layer_1" enable-background="new 0 0 64 64" viewBox="0 0 64 64" width="50px" height="50px" fill="#ccc">
                        <path d="m64 38.139c0-.063-.058-.1-.066-.16-.013-.178-.035-.355-.066-.53l-8.536-34.14c-.485-1.948-2.237-3.314-4.245-3.309h-38.174c-2.008-.005-3.76 1.361-4.245 3.309l-8.536 34.146c-.031.173-.052.348-.065.523-.009.06-.067.1-.067.161 0 .033.033.053.036.087-.007.1-.036.193-.036.29v21.108c.005 2.415 1.961 4.371 4.376 4.376h55.248c2.415-.005 4.371-1.961 4.376-4.376v-21.108c0-.1-.029-.194-.036-.291.003-.033.036-.053.036-.086zm-52.8-34.194c.197-.786.905-1.335 1.715-1.331h38.172c.809-.003 1.516.546 1.713 1.331l8.225 32.887h-16.748c-.721.002-1.305.586-1.307 1.307v4.831h-21.94v-4.831c-.002-.721-.586-1.305-1.307-1.307h-16.748zm48.424 57.441h-55.248c-.972-.002-1.76-.79-1.762-1.762v-20.179h15.8v4.832c.001.721.586 1.306 1.307 1.307h24.556c.721-.001 1.306-.586 1.307-1.307v-4.832h15.8v20.179c-.002.972-.788 1.759-1.76 1.762z" />
                    </svg>
                    <span class="d-block pt-2 f12 text-muted">No Data</span>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- End Individual Appointment -->

<script>
    let start_time_array = [];
    let end_time_array = [];

    function selectThisCustomer(param) {
        let PK_USER_MASTER = $(param).val();
        $('.enrollment_area, .schedule_code_area').removeClass('d-none');
        $.ajax({
            url: "ajax/get_enrollments.php",
            type: "POST",
            data: {
                PK_USER_MASTER: PK_USER_MASTER
            },
            async: false,
            cache: false,
            success: function(result) {
                $('#enrollment_div').html(result);
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

    function selectThisService(param) {
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
                $('#PK_SCHEDULING_CODE').html(result);
            }
        });
    }

    function getSlots() {
        let PK_SERVICE_PROVIDER = $('#PK_SERVICE_PROVIDER').val();
        let PK_LOCATION = $('#SELECTED_CUSTOMER_ID').find(':selected').data('location_id');
        let duration = $('#PK_SCHEDULING_CODE').find(':selected').data('duration');

        let selected_date = $('#APPOINTMENT_DATE').val();
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
                    $('.slot_div').html(result);
                }
            });
        } else {
            $('.slot_div').html('');
        }
    }

    function set_time(param, id, start_time, end_time) {
        if ($(param).data('is_selected') == 0) {
            start_time_array.push(start_time);
            end_time_array.push(end_time);
            $('#START_TIME').val(start_time_array.sort());
            $('#END_TIME').val(end_time_array.sort());
            $('#slot_btn_' + id).data('is_selected', 1);
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

            $('#START_TIME').val(start_time_array.sort());
            $('#END_TIME').val(end_time_array.sort());
            $('#slot_btn_' + id).data('is_selected', 0);
            document.getElementById('slot_btn_' + id).style.setProperty('background-color', '#f8f9fa', 'important');
            document.getElementById('slot_btn_' + id).style.setProperty('color', '#000', 'important');
        }

        console.log(start_time_array.sort(), end_time_array.sort());
    }
</script>