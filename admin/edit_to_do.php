<?php
require_once('../global/config.php');
global $db;
global $db_account;

$title = "Edit To-Do";
$LOCATION_ARRAY = explode(',', $_SESSION['DEFAULT_LOCATION_ID']);

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

$standing = empty($_GET['standing']) ? 0 : 1;

if (!empty($_POST)) {
    $SPECIAL_APPOINTMENT_DATA['TITLE'] = $_POST['TITLE'];
    $SPECIAL_APPOINTMENT_DATA['START_TIME'] = date('H:i:s', strtotime($_POST['START_TIME']));
    $SPECIAL_APPOINTMENT_DATA['END_TIME'] = date('H:i:s', strtotime($_POST['END_TIME']));
    $SPECIAL_APPOINTMENT_DATA['PK_SCHEDULING_CODE'] = $_POST['PK_SCHEDULING_CODE'];
    $SPECIAL_APPOINTMENT_DATA['DESCRIPTION'] = $_POST['DESCRIPTION'];
    $PK_SPECIAL_APPOINTMENT = $_POST['PK_SPECIAL_APPOINTMENT'];

    $SPECIAL_APPOINTMENT_DATA['PK_APPOINTMENT_STATUS'] = $_POST['PK_APPOINTMENT_STATUS'];
    $SPECIAL_APPOINTMENT_DATA['EDITED_BY']    = $_SESSION['PK_USER'];
    $SPECIAL_APPOINTMENT_DATA['EDITED_ON'] = date("Y-m-d H:i");

    if ($_POST['IS_STANDING'] == 0) {
        if (count($_POST['PK_USER']) == 1) {
            $SPECIAL_APPOINTMENT_USER['PK_USER'] = $_POST['PK_USER'][0];
            db_perform_account('DOA_SPECIAL_APPOINTMENT_USER', $SPECIAL_APPOINTMENT_USER, 'update', " PK_SPECIAL_APPOINTMENT =  '$PK_SPECIAL_APPOINTMENT'");
        } elseif (count($_POST['PK_USER']) >= 1) {
            for ($j = 0; $j < count($_POST['PK_USER']); $j++) {
                if ($j == 0) {
                    $SPECIAL_APPOINTMENT_USER['PK_USER'] = $_POST['PK_USER'][$j];
                    db_perform_account('DOA_SPECIAL_APPOINTMENT_USER', $SPECIAL_APPOINTMENT_USER, 'update', " PK_SPECIAL_APPOINTMENT =  '$PK_SPECIAL_APPOINTMENT'");
                } else {
                    $NEW_SPECIAL_APPOINTMENT_DATA['STANDING_ID'] = $_POST['SELECTED_STANDING_ID'];
                    $NEW_SPECIAL_APPOINTMENT_DATA['PK_LOCATION'] = $LOCATION_ARRAY[0];
                    $NEW_SPECIAL_APPOINTMENT_DATA['TITLE'] = $_POST['TITLE'];
                    $NEW_SPECIAL_APPOINTMENT_DATA['DATE'] = date('Y-m-d', strtotime($_POST['DATE']));
                    $NEW_SPECIAL_APPOINTMENT_DATA['START_TIME'] = date('H:i:s', strtotime($_POST['START_TIME']));
                    $NEW_SPECIAL_APPOINTMENT_DATA['END_TIME'] = date('H:i:s', strtotime($_POST['END_TIME']));
                    $NEW_SPECIAL_APPOINTMENT_DATA['PK_SCHEDULING_CODE'] = $_POST['PK_SCHEDULING_CODE'];
                    $NEW_SPECIAL_APPOINTMENT_DATA['DESCRIPTION'] = $_POST['DESCRIPTION'];
                    $NEW_SPECIAL_APPOINTMENT_DATA['ACTIVE'] = 1;
                    $NEW_SPECIAL_APPOINTMENT_DATA['PK_APPOINTMENT_STATUS'] = $_POST['PK_APPOINTMENT_STATUS'];;
                    $NEW_SPECIAL_APPOINTMENT_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
                    $NEW_SPECIAL_APPOINTMENT_DATA['CREATED_ON'] = date("Y-m-d H:i");
                    db_perform_account('DOA_SPECIAL_APPOINTMENT', $NEW_SPECIAL_APPOINTMENT_DATA, 'insert');
                    $PK_SPECIAL_APPOINTMENT_NEW = $db_account->insert_ID();

                    $SPECIAL_APPOINTMENT_USER['PK_SPECIAL_APPOINTMENT'] = $PK_SPECIAL_APPOINTMENT_NEW;
                    $SPECIAL_APPOINTMENT_USER['PK_USER'] = $_POST['PK_USER'][$j];
                    db_perform_account('DOA_SPECIAL_APPOINTMENT_USER', $SPECIAL_APPOINTMENT_USER, 'insert');
                }
            }
        }
    } else {
        $STANDING_ID = $_POST['SELECTED_STANDING_ID'];

        $SAVED_SERVICE_PROVIDER = explode(',', $_POST['SAVED_SERVICE_PROVIDER']);
        $SELECTED_SERVICE_PROVIDER = $_POST['PK_USER'];

        $ADDED_SP = array_values(array_diff($SELECTED_SERVICE_PROVIDER, $SAVED_SERVICE_PROVIDER));
        $REMOVED_SP = array_values(array_diff($SAVED_SERVICE_PROVIDER, $SELECTED_SERVICE_PROVIDER));

        if (count($REMOVED_SP) > 0) {
            $distinct_data = $db_account->Execute("SELECT DISTINCT (DATE) FROM DOA_SPECIAL_APPOINTMENT WHERE STANDING_ID = '$STANDING_ID'");
            for ($j = 0; $j < count($REMOVED_SP); $j++) {
                while (!$distinct_data->EOF) {
                    $DATE = $distinct_data->fields['DATE'];
                    $special_appt = $db_account->Execute("SELECT DOA_SPECIAL_APPOINTMENT.PK_SPECIAL_APPOINTMENT FROM `DOA_SPECIAL_APPOINTMENT` INNER JOIN DOA_SPECIAL_APPOINTMENT_USER ON DOA_SPECIAL_APPOINTMENT.PK_SPECIAL_APPOINTMENT = DOA_SPECIAL_APPOINTMENT_USER.PK_SPECIAL_APPOINTMENT WHERE DOA_SPECIAL_APPOINTMENT.STANDING_ID = '$STANDING_ID' AND DOA_SPECIAL_APPOINTMENT.DATE = '$DATE' AND DOA_SPECIAL_APPOINTMENT_USER.PK_USER = " . $REMOVED_SP[$j]);
                    $db_account->Execute("DELETE FROM `DOA_SPECIAL_APPOINTMENT` WHERE PK_SPECIAL_APPOINTMENT = " . $special_appt->fields['PK_SPECIAL_APPOINTMENT']);
                    $db_account->Execute("DELETE FROM `DOA_SPECIAL_APPOINTMENT_USER` WHERE PK_SPECIAL_APPOINTMENT = " . $special_appt->fields['PK_SPECIAL_APPOINTMENT']);
                    $distinct_data->MoveNext();
                }
            }
        }

        if (count($ADDED_SP) > 0) {
            $distinct_data = $db_account->Execute("SELECT DISTINCT (DATE) FROM DOA_SPECIAL_APPOINTMENT WHERE STANDING_ID = '$STANDING_ID'");
            for ($i = 0; $i < count($ADDED_SP); $i++) {
                while (!$distinct_data->EOF) {
                    $NEW_SPECIAL_APPOINTMENT_DATA['STANDING_ID'] = $STANDING_ID;
                    $NEW_SPECIAL_APPOINTMENT_DATA['PK_LOCATION'] = $LOCATION_ARRAY[0];
                    $NEW_SPECIAL_APPOINTMENT_DATA['TITLE'] = $_POST['TITLE'];
                    $NEW_SPECIAL_APPOINTMENT_DATA['DATE'] = $distinct_data->fields['DATE'];
                    $NEW_SPECIAL_APPOINTMENT_DATA['START_TIME'] = date('H:i:s', strtotime($_POST['START_TIME']));
                    $NEW_SPECIAL_APPOINTMENT_DATA['END_TIME'] = date('H:i:s', strtotime($_POST['END_TIME']));
                    $NEW_SPECIAL_APPOINTMENT_DATA['PK_SCHEDULING_CODE'] = $_POST['PK_SCHEDULING_CODE'];
                    $NEW_SPECIAL_APPOINTMENT_DATA['DESCRIPTION'] = $_POST['DESCRIPTION'];
                    $NEW_SPECIAL_APPOINTMENT_DATA['ACTIVE'] = 1;
                    $NEW_SPECIAL_APPOINTMENT_DATA['PK_APPOINTMENT_STATUS'] = $_POST['PK_APPOINTMENT_STATUS'];;
                    $NEW_SPECIAL_APPOINTMENT_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
                    $NEW_SPECIAL_APPOINTMENT_DATA['CREATED_ON'] = date("Y-m-d H:i");
                    db_perform_account('DOA_SPECIAL_APPOINTMENT', $NEW_SPECIAL_APPOINTMENT_DATA, 'insert');
                    $PK_SPECIAL_APPOINTMENT_NEW = $db_account->insert_ID();

                    $SPECIAL_APPOINTMENT_USER['PK_SPECIAL_APPOINTMENT'] = $PK_SPECIAL_APPOINTMENT_NEW;
                    $SPECIAL_APPOINTMENT_USER['PK_USER'] = $ADDED_SP[$i];
                    db_perform_account('DOA_SPECIAL_APPOINTMENT_USER', $SPECIAL_APPOINTMENT_USER, 'insert');
                    $distinct_data->MoveNext();
                }
            }
        }
    }

    if (isset($_POST['STANDING_ID'])) {
        db_perform_account('DOA_SPECIAL_APPOINTMENT', $SPECIAL_APPOINTMENT_DATA, 'update', " STANDING_ID =  '$_POST[STANDING_ID]'");
    } else {
        $SPECIAL_APPOINTMENT_DATA['DATE'] = date('Y-m-d', strtotime($_POST['DATE']));
        db_perform_account('DOA_SPECIAL_APPOINTMENT', $SPECIAL_APPOINTMENT_DATA, 'update', " PK_SPECIAL_APPOINTMENT =  '$PK_SPECIAL_APPOINTMENT'");
    }

    header("location:to_do_list.php");
}



$res = $db_account->Execute("SELECT DOA_SPECIAL_APPOINTMENT.*  FROM DOA_SPECIAL_APPOINTMENT WHERE PK_SPECIAL_APPOINTMENT = " . $_GET['id']);
if ($res->RecordCount() == 0) {
    header("location:to_do_list.php");
    exit;
} else {
    $PK_SPECIAL_APPOINTMENT = $res->fields['PK_SPECIAL_APPOINTMENT'];
    $STANDING_ID = $res->fields['STANDING_ID'];
    $TITLE = $res->fields['TITLE'];
    $DATE = $res->fields['DATE'];
    $START_TIME = $res->fields['START_TIME'];
    $END_TIME = $res->fields['END_TIME'];
    $DESCRIPTION = $res->fields['DESCRIPTION'];
    $PK_SCHEDULING_CODE = $res->fields['PK_SCHEDULING_CODE'];
    $PK_APPOINTMENT_STATUS = $res->fields['PK_APPOINTMENT_STATUS'];
    $ACTIVE = $res->fields['ACTIVE'];
}
?>

<!DOCTYPE html>
<html lang="en">
<?php require_once('../includes/header.php'); ?>
<style>
    .ck-editor__editable_inline {
        min-height: 300px;
    }

    .SumoSelect {
        width: 100%;
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
                        <h4 class="text-themecolor"><?= $title ?></h4>
                    </div>
                    <div class="col-md-7 align-self-center text-end">
                        <div class="d-flex justify-content-end align-items-center">
                            <ol class="breadcrumb justify-content-end">
                                <li class="breadcrumb-item"><a href="to_do_list.php">To-Do List</a></li>
                                <li class="breadcrumb-item active"><?= $title ?></li>
                            </ol>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card p-20">
                            <div class="card-body">
                                <form class="form-material form-horizontal" action="" method="post" enctype="multipart/form-data">
                                    <input type="hidden" name="FUNCTION_NAME" value="saveSpecialAppointmentData">
                                    <input type="hidden" name="PK_SPECIAL_APPOINTMENT" class="PK_SPECIAL_APPOINTMENT" value="<?= $PK_SPECIAL_APPOINTMENT ?>">
                                    <input type="hidden" name="SELECTED_STANDING_ID" value="<?= $STANDING_ID ?>">
                                    <input type="hidden" name="IS_STANDING" value="<?= $standing ?>">
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="row">
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="form-label">Title</label>
                                                        <input type="text" id="TITLE" name="TITLE" class="form-control" required value="<?php echo $TITLE ?>">
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="form-label">Date</label>
                                                        <?php if ($standing == 1) {
                                                            $standing_date = $db_account->Execute("SELECT MIN(DOA_SPECIAL_APPOINTMENT.DATE) AS BEGINNING_DATE, MAX(DOA_SPECIAL_APPOINTMENT.DATE) AS END_DATE FROM `DOA_SPECIAL_APPOINTMENT` WHERE STANDING_ID = '$STANDING_ID'"); ?>
                                                            <input type="text" class="form-control" disabled value="<?= date('m/d/Y', strtotime($standing_date->fields['BEGINNING_DATE'])) ?> - <?= date('m/d/Y', strtotime($standing_date->fields['END_DATE'])) ?>">
                                                        <?php } else { ?>
                                                            <input type="text" id="DATE" name="DATE" class="form-control datepicker-normal" required value="<?php echo ($DATE) ? date('m/d/Y', strtotime($DATE)) : '' ?>">
                                                        <?php } ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="form-label">Start Time</label>
                                                        <input type="text" id="START_TIME" name="START_TIME" class="form-control time-picker" required value="<?php echo ($START_TIME) ? date('h:i A', strtotime($START_TIME)) : '' ?>">
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="form-label">End Time</label>
                                                        <input type="text" id="END_TIME" name="END_TIME" class="form-control time-picker" required value="<?php echo ($END_TIME) ? date('h:i A', strtotime($END_TIME)) : '' ?>">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="form-label">Description</label>
                                                        <textarea class="form-control" id="DESCRIPTION" name="DESCRIPTION" rows="2"><?= $DESCRIPTION ?></textarea>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label">Scheduling Code</label>
                                                    <div class="col-md-12" style="margin-top: 16px;">
                                                        <select class="form-control" name="PK_SCHEDULING_CODE" onchange="calculateEndTime(this)">
                                                            <?php
                                                            $booking_row = $db_account->Execute("SELECT DOA_SCHEDULING_CODE.`PK_SCHEDULING_CODE`, DOA_SCHEDULING_CODE.`SCHEDULING_CODE`, DOA_SCHEDULING_CODE.`SCHEDULING_NAME`, DOA_SCHEDULING_CODE.`DURATION` FROM `DOA_SCHEDULING_CODE` WHERE DOA_SCHEDULING_CODE.TO_DOS = 1 AND DOA_SCHEDULING_CODE.`ACTIVE` = 1");
                                                            while (!$booking_row->EOF) { ?>
                                                                <option value="<?php echo $booking_row->fields['PK_SCHEDULING_CODE']; ?>" data-duration="<?php echo $booking_row->fields['DURATION']; ?>" data-scheduling_name="<?php echo $booking_row->fields['SCHEDULING_NAME'] ?>" data-is_default="<?php echo $booking_row->fields['IS_DEFAULT'] ?>" <?= ($PK_SCHEDULING_CODE == $booking_row->fields['PK_SCHEDULING_CODE']) ? "selected" : "" ?>><?= $booking_row->fields['SCHEDULING_NAME'] . ' (' . $booking_row->fields['SCHEDULING_CODE'] . ')' ?></option>
                                                            <?php $booking_row->MoveNext();
                                                            } ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <!--<div class="col-6">
                                                <label class="form-label">Customer</label>
                                                <div style="margin-bottom: 15px; margin-top: 10px; width: 480px;">
                                                    <select class="multi_sumo_select" name="PK_USER_MASTER[]" multiple>
                                                        <?php
                                                        /*                                                        $selected_customer = [];
                                                        $selected_customer_row = $db_account->Execute("SELECT DOA_SPECIAL_APPOINTMENT_CUSTOMER.PK_USER_MASTER FROM DOA_SPECIAL_APPOINTMENT_CUSTOMER LEFT JOIN $master_database.DOA_USER_MASTER ON DOA_SPECIAL_APPOINTMENT_CUSTOMER.PK_USER_MASTER = $master_database.DOA_USER_MASTER.PK_USER_MASTER WHERE DOA_SPECIAL_APPOINTMENT_CUSTOMER.PK_SPECIAL_APPOINTMENT = '$PK_SPECIAL_APPOINTMENT'");
                                                        while (!$selected_customer_row->EOF) {
                                                            $selected_customer[] = $selected_customer_row->fields['PK_USER_MASTER'];
                                                            $selected_customer_row->MoveNext();
                                                        }

                                                        $row = $db->Execute("SELECT DOA_USERS.PK_USER, DOA_USER_MASTER.PK_USER_MASTER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER WHERE DOA_USER_MASTER.PRIMARY_LOCATION_ID IN (".$_SESSION['DEFAULT_LOCATION_ID'].") AND DOA_USER_ROLES.PK_ROLES = 4 AND DOA_USERS.ACTIVE = 1 AND DOA_USERS.IS_DELETED = 0 AND DOA_USER_MASTER.PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]' ORDER BY DOA_USERS.FIRST_NAME ASC");
                                                        while (!$row->EOF) { */ ?>
                                                            <option value="<?php /*echo $row->fields['PK_USER_MASTER'];*/ ?>" <?php /*=in_array($row->fields['PK_USER_MASTER'], $selected_customer)?"selected":""*/ ?>><?php /*=$row->fields['NAME']*/ ?></option>
                                                            <?php /*$row->MoveNext(); } */ ?>
                                                    </select>
                                                </div>
                                            </div>-->
                                            </div>
                                            <div class="row">
                                                <div class="col-6">
                                                    <?php
                                                    $selected_service_provider = [];
                                                    if ($standing == 1) {
                                                        $selected_service_provider_row = $db_account->Execute("SELECT DISTINCT (`PK_USER`) FROM `DOA_SPECIAL_APPOINTMENT_USER` LEFT JOIN DOA_SPECIAL_APPOINTMENT ON DOA_SPECIAL_APPOINTMENT_USER.PK_SPECIAL_APPOINTMENT = DOA_SPECIAL_APPOINTMENT.PK_SPECIAL_APPOINTMENT WHERE DOA_SPECIAL_APPOINTMENT.STANDING_ID = '$STANDING_ID'");
                                                    } else {
                                                        $selected_service_provider_row = $db_account->Execute("SELECT DISTINCT (`PK_USER`) FROM `DOA_SPECIAL_APPOINTMENT_USER` WHERE `PK_SPECIAL_APPOINTMENT` = '$PK_SPECIAL_APPOINTMENT'");
                                                    }
                                                    while (!$selected_service_provider_row->EOF) {
                                                        $selected_service_provider[] = $selected_service_provider_row->fields['PK_USER'];
                                                        $selected_service_provider_row->MoveNext();
                                                    } ?>
                                                    <input type="hidden" name="SAVED_SERVICE_PROVIDER" value="<?= implode(',', $selected_service_provider) ?>">
                                                    <label class="form-label"><?= $service_provider_title ?></label>
                                                    <div class="col-md-12" style="margin-bottom: 15px; margin-top: 10px;">
                                                        <select class="multi_sumo_select" name="PK_USER[]" multiple required>
                                                            <?php
                                                            $row = $db->Execute("SELECT DISTINCT (DOA_USERS.PK_USER), CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.ACTIVE FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER LEFT JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER LEFT JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER WHERE DOA_USER_LOCATION.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND DOA_USER_ROLES.PK_ROLES IN(5) AND DOA_USERS.ACTIVE = 1 AND DOA_USERS.IS_DELETED = 0 AND DOA_USERS.PK_ACCOUNT_MASTER = " . $_SESSION['PK_ACCOUNT_MASTER'] . " ORDER BY DOA_USERS.FIRST_NAME ASC");
                                                            while (!$row->EOF) { ?>
                                                                <option value="<?php echo $row->fields['PK_USER']; ?>" <?= in_array($row->fields['PK_USER'], $selected_service_provider) ? "selected" : "" ?>><?= $row->fields['NAME'] ?></option>
                                                            <?php $row->MoveNext();
                                                            } ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="form-label">Status:</label>
                                                        <select class="form-control" name="PK_APPOINTMENT_STATUS" id="PK_APPOINTMENT_STATUS">
                                                            <option value="">Select Status</option>
                                                            <?php
                                                            $selected_status = '';
                                                            $row = $db->Execute("SELECT * FROM `DOA_APPOINTMENT_STATUS` WHERE `ACTIVE` = 1");
                                                            while (!$row->EOF) { ?>
                                                                <option value="<?php echo $row->fields['PK_APPOINTMENT_STATUS']; ?>" <?= ($PK_APPOINTMENT_STATUS == $row->fields['PK_APPOINTMENT_STATUS']) ? 'selected' : '' ?>><?= $row->fields['APPOINTMENT_STATUS'] ?></option>
                                                            <?php $row->MoveNext();
                                                            } ?>
                                                        </select>
                                                        <p id="appointment_status"><?= $selected_status ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php if ($standing == 1) { ?>
                                        <div class="form-group">
                                            <label><input type="checkbox" name="STANDING_ID" value="<?= $STANDING_ID ?>" <?= ($standing == 1) ? 'checked' : '' ?>> All Standing Session Details Will Be Changed</label>
                                        </div>
                                    <?php } ?>

                                    <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">Submit</button>
                                    <a onclick="closeEditAppointment()" class="btn btn-inverse waves-effect waves-light">Cancel</a>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php require_once('../includes/footer.php'); ?>

</body>

<script>
    $('.multi_sumo_select').SumoSelect({
        placeholder: 'Select Customer',
        selectAll: true,
        search: true,
        searchText: 'Search...'
    });
</script>


<script type="text/javascript">
    $('#PK_USER_MASTER').SumoSelect({
        placeholder: 'Select Customer',
        selectAll: true,
        search: true,
        searchText: 'Search...'
    });

    $('.datepicker-normal').datepicker({
        format: 'mm/dd/yyyy',
    });

    $('#START_TIME').timepicker({
        timeFormat: 'hh:mm p',
        change: function() {
            calculateEndTime();
        },
    });

    $('#END_TIME').timepicker({
        timeFormat: 'hh:mm p',
    });

    $('.DAYS').on('change', function() {
        if ($('.DAYS').is(':checked')) {
            $("input[name='OCCURRENCE'][value='WEEKLY']").prop('checked', true);
            $('.occurrence_div').addClass('disable-div');
        } else {
            $("input[name='OCCURRENCE'][value='WEEKLY']").prop('checked', false);
            $('.occurrence_div').removeClass('disable-div');
        }
    });

    $('.multi_sumo_select').SumoSelect({
        placeholder: 'Select <?= $service_provider_title ?>',
        selectAll: true
    });
    $('.PK_SCHEDULING_CODE').SumoSelect({
        placeholder: 'Select Scheduling Code',
        selectAll: true
    });

    function calculateEndTime() {
        let start_time = $('#START_TIME').val();
        let duration = $('#PK_SCHEDULING_CODE').find(':selected').data('duration');
        let scheduling_name = $('#PK_SCHEDULING_CODE').find(':selected').data('scheduling_name');
        let is_default = $('#PK_SCHEDULING_CODE').find(':selected').data('is_default');
        $('#TITLE').val(scheduling_name);
        duration = (duration) ? duration : 0;

        if (start_time && duration) {
            start_time = moment(start_time, ["h:mm A"]).format("HH:mm");
            let end_time = addMinutes(start_time, duration);
            end_time = moment(end_time, ["HH:mm"]).format("h:mm A");
            $('#END_TIME').val(end_time);
        }

        if (is_default === 1) {
            $('.customer_div').show();
        } else {
            $('.customer_div').hide();
        }
    }

    function addMinutes(time, minsToAdd) {
        function D(J) {
            return (J < 10 ? '0' : '') + J;
        };
        var piece = time.split(':');
        var mins = piece[0] * 60 + +piece[1] + +minsToAdd;

        return D(mins % (24 * 60) / 60 | 0) + ':' + D(mins % 60);
    }

    $('#IS_STANDING').on('change', function() {
        if ($(this).is(':checked')) {
            $('.is_required').prop('required', true);
            $(this).closest('.to_dos_class_setting').find('.standing').show();
            $(this).closest('.to_dos_class_setting').find('.END_TIME').hide();
        } else {
            $('.is_required').prop('required', false);
            $(this).closest('.to_dos_class_setting').find('.standing').hide();
            $(this).closest('.to_dos_class_setting').find('.END_TIME').show();
        }
    });
</script>

</html>