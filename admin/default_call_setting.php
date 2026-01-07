<?php
require_once('../global/config.php');

$title = "Default Call Setting";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

if (!empty($_POST)) {

    foreach ($_POST['PK_USER'] as $loc_id => $pk_user_val) {

        // Check if row exists for this location
        $check = $db->Execute("
            SELECT PK_LOCATION 
            FROM DOA_DEFAULT_CALL_SETTING 
            WHERE PK_LOCATION = $loc_id
        ");

        $PART_OF_DAY = isset($_POST['PART_OF_DAY'][$loc_id]) ? $_POST['PART_OF_DAY'][$loc_id] : [];
        $PART_OF_DAY_STR = implode(',', $PART_OF_DAY);

        // Prepare data
        $data = [];
        $data['PK_LOCATION']        = $loc_id;
        $data['PK_USER']            = $_POST['PK_USER'][$loc_id];
        $data['PK_SERVICE_MASTER']  = $_POST['PK_SERVICE_MASTER'][$loc_id];
        $data['PK_SCHEDULING_CODE'] = $_POST['PK_SCHEDULING_CODE'][$loc_id];
        $data['PART_OF_DAY']        = $PART_OF_DAY_STR;
        $data['SCRIPT_1']           = $_POST['SCRIPT_1'][$loc_id];
        $data['SCRIPT_2']           = $_POST['SCRIPT_2'][$loc_id];
        $data['END_SCRIPT']         = $_POST['END_SCRIPT'][$loc_id];
        $data['ACTIVE']             = 1;

        if ($check->RecordCount() > 0) {
            // UPDATE
            $data['EDITED_BY'] = $_SESSION['PK_USER'];
            $data['EDITED_ON'] = date("Y-m-d H:i");

            db_perform('DOA_DEFAULT_CALL_SETTING', $data, 'update', " PK_LOCATION = $loc_id ");
        } else {
            // INSERT
            $data['CREATED_BY'] = $_SESSION['PK_USER'];
            $data['CREATED_ON'] = date("Y-m-d H:i");

            db_perform('DOA_DEFAULT_CALL_SETTING', $data, 'insert');
        }
    }

    header("location:default_call_setting.php");
}



$DEFAULT_CALL_SETTING = [];

$res = $db->Execute("SELECT * FROM DOA_DEFAULT_CALL_SETTING");

while (!$res->EOF) {

    $loc = $res->fields['PK_LOCATION']; // location ID

    $DEFAULT_CALL_SETTING[$loc] = [
        'PK_USER'            => $res->fields['PK_USER'],
        'PK_SERVICE_MASTER'  => $res->fields['PK_SERVICE_MASTER'],
        'PK_SCHEDULING_CODE' => $res->fields['PK_SCHEDULING_CODE'],
        'PART_OF_DAY'        => $res->fields['PART_OF_DAY'],
        'SCRIPT_1'           => $res->fields['SCRIPT_1'],
        'SCRIPT_2'           => $res->fields['SCRIPT_2'],
        'END_SCRIPT'         => $res->fields['END_SCRIPT'],
        'ACTIVE'             => $res->fields['ACTIVE'],
    ];

    $res->MoveNext();
}


?>

<!DOCTYPE html>
<html lang="en">
<?php require_once('../includes/header.php'); ?>

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
                                <li class="breadcrumb-item"><a href="setup.php">Setup</a></li>
                                <li class="breadcrumb-item active"><?= $title ?></li>
                            </ol>
                        </div>
                    </div>
                </div>

                <?php
                // Fetch all locations for this account
                $locations = $db->Execute("
                                            SELECT PK_LOCATION, LOCATION_NAME 
                                            FROM DOA_LOCATION 
                                            WHERE PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND PK_ACCOUNT_MASTER = " . $_SESSION['PK_ACCOUNT_MASTER'] . " 
                                            AND ACTIVE = 1
                                        "); ?>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">

                                <form class="form-material form-horizontal" action="" method="post" enctype="multipart/form-data">

                                    <?php while (!$locations->EOF) {
                                        $loc_id   = $locations->fields['PK_LOCATION'];
                                        $loc_name = $locations->fields['LOCATION_NAME'];
                                    ?>

                                        <!-- ============ LOCATION HEADER ============ -->
                                        <h4 class="bg-light p-2 mb-3 mt-3">
                                            <b><?= $loc_name ?></b>
                                        </h4>

                                        <div class="row">
                                            <!-- SERVICE PROVIDER -->
                                            <div class="col-3">
                                                <div class="form-group">
                                                    <label class="form-label">Service Provider</label>
                                                    <select name="PK_USER[<?= $loc_id ?>]" class="form-control" required>
                                                        <option value="">Select Service Provider</option>

                                                        <?php
                                                        $sp = $db->Execute("
                                                                            SELECT DISTINCT U.PK_USER, CONCAT(U.FIRST_NAME, ' ', U.LAST_NAME) AS NAME
                                                                            FROM DOA_USERS U
                                                                            LEFT JOIN DOA_USER_ROLES R ON U.PK_USER = R.PK_USER
                                                                            LEFT JOIN DOA_USER_LOCATION UL ON U.PK_USER = UL.PK_USER
                                                                            WHERE UL.PK_LOCATION = $loc_id
                                                                            AND R.PK_ROLES = 5
                                                                            AND U.ACTIVE = 1
                                                                            AND U.IS_DELETED = 0
                                                                            AND U.PK_ACCOUNT_MASTER = " . $_SESSION['PK_ACCOUNT_MASTER']);

                                                        while (!$sp->EOF) { ?>
                                                            <option value="<?= $sp->fields['PK_USER'] ?>"
                                                                <?= isset($DEFAULT_CALL_SETTING[$loc_id]) && $DEFAULT_CALL_SETTING[$loc_id]['PK_USER'] == $sp->fields['PK_USER'] ? "selected" : "" ?>>
                                                                <?= $sp->fields['NAME'] ?>
                                                            </option>

                                                        <?php $sp->MoveNext();
                                                        } ?>
                                                    </select>
                                                </div>
                                            </div>

                                            <!-- SERVICE -->
                                            <div class="col-3">
                                                <div class="form-group">
                                                    <label class="form-label">Service</label>
                                                    <select name="PK_SERVICE_MASTER[<?= $loc_id ?>]" class="form-control" required>
                                                        <option value="">Select Service</option>

                                                        <?php
                                                        $svc = $db_account->Execute("
                                                                                    SELECT PK_SERVICE_MASTER, SERVICE_NAME 
                                                                                    FROM DOA_SERVICE_MASTER 
                                                                                    WHERE PK_LOCATION = $loc_id 
                                                                                    AND ACTIVE = 1 
                                                                                    AND PK_SERVICE_CLASS = 2
                                                                                    ORDER BY SERVICE_NAME
                                                                                ");

                                                        while (!$svc->EOF) { ?>
                                                            <option value="<?= $svc->fields['PK_SERVICE_MASTER'] ?>"
                                                                <?= isset($DEFAULT_CALL_SETTING[$loc_id]) && $DEFAULT_CALL_SETTING[$loc_id]['PK_SERVICE_MASTER'] == $svc->fields['PK_SERVICE_MASTER'] ? "selected" : "" ?>>
                                                                <?= $svc->fields['SERVICE_NAME'] ?>
                                                            </option>

                                                        <?php $svc->MoveNext();
                                                        } ?>
                                                    </select>
                                                </div>
                                            </div>

                                            <!-- SCHEDULING CODE -->
                                            <div class="col-3">
                                                <div class="form-group">
                                                    <label class="form-label">Scheduling Code</label>
                                                    <select name="PK_SCHEDULING_CODE[<?= $loc_id ?>]" class="form-control" required>
                                                        <option value="">Select Scheduling Code</option>

                                                        <?php
                                                        $sch = $db_account->Execute("
                                                                                    SELECT PK_SCHEDULING_CODE, SCHEDULING_CODE, SCHEDULING_NAME, DURATION 
                                                                                    FROM DOA_SCHEDULING_CODE 
                                                                                    WHERE PK_LOCATION = $loc_id 
                                                                                    AND ACTIVE = 1
                                                                                    ORDER BY 
                                                                                    CASE WHEN SORT_ORDER IS NULL THEN 1 ELSE 0 END, 
                                                                                    SORT_ORDER
                                                                                ");

                                                        $saved_sched = isset($DEFAULT_CALL_SETTING[$loc_id]['PK_SCHEDULING_CODE'])
                                                            ? $DEFAULT_CALL_SETTING[$loc_id]['PK_SCHEDULING_CODE']
                                                            : '';

                                                        while (!$sch->EOF) { ?>
                                                            <option value="<?= $sch->fields['PK_SCHEDULING_CODE'] . ',' . $sch->fields['DURATION'] ?>"
                                                                <?= $saved_sched == $sch->fields['PK_SCHEDULING_CODE'] ? "selected" : "" ?>>
                                                                <?= $sch->fields['SCHEDULING_CODE'] . ' - ' . $sch->fields['SCHEDULING_NAME'] . ' (' . $sch->fields['DURATION'] . ' mins)' ?>
                                                            </option>

                                                        <?php $sch->MoveNext();
                                                        } ?>
                                                    </select>
                                                </div>
                                            </div>

                                            <!-- PART OF DAY -->
                                            <div class="col-3">
                                                <label class="form-label">Parts of Day for Slot</label>
                                                <div class="multiselect-box" style="width: 100%;">
                                                    <select name="PART_OF_DAY[<?= $loc_id ?>][]" class="multi_sumo_select" required multiple>
                                                        <option value="MORNING" <?= isset($DEFAULT_CALL_SETTING[$loc_id]['PART_OF_DAY']) && in_array('MORNING', explode(',', $DEFAULT_CALL_SETTING[$loc_id]['PART_OF_DAY'])) ? "selected" : "" ?>>Morning</option>
                                                        <option value="AFTERNOON" <?= isset($DEFAULT_CALL_SETTING[$loc_id]['PART_OF_DAY']) && in_array('AFTERNOON', explode(',', $DEFAULT_CALL_SETTING[$loc_id]['PART_OF_DAY'])) ? "selected" : "" ?>>Afternoon</option>
                                                        <option value="EVENING" <?= isset($DEFAULT_CALL_SETTING[$loc_id]['PART_OF_DAY']) && in_array('EVENING', explode(',', $DEFAULT_CALL_SETTING[$loc_id]['PART_OF_DAY'])) ? "selected" : "" ?>>Evening</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- SCRIPTS -->
                                        <div class="row">
                                            <div class="col-4">
                                                <div class="form-group">
                                                    <label>Script 1 for New</label>
                                                    <textarea class="form-control" name="SCRIPT_1[<?= $loc_id ?>]" rows="5">
                                                    <?= isset($DEFAULT_CALL_SETTING[$loc_id]['SCRIPT_1']) ? $DEFAULT_CALL_SETTING[$loc_id]['SCRIPT_1'] : '' ?>
                                                    </textarea>
                                                </div>
                                            </div>

                                            <div class="col-4">
                                                <div class="form-group">
                                                    <label>Script 2 for New</label>
                                                    <textarea class="form-control" name="SCRIPT_2[<?= $loc_id ?>]" rows="5">
                                                    <?= isset($DEFAULT_CALL_SETTING[$loc_id]['SCRIPT_2']) ? $DEFAULT_CALL_SETTING[$loc_id]['SCRIPT_2'] : '' ?>
                                                    </textarea>
                                                </div>
                                            </div>

                                            <div class="col-4">
                                                <div class="form-group">
                                                    <label>End Script for New</label>
                                                    <textarea class="form-control" name="END_SCRIPT[<?= $loc_id ?>]" rows="5">
                                                    <?= isset($DEFAULT_CALL_SETTING[$loc_id]['END_SCRIPT']) ? $DEFAULT_CALL_SETTING[$loc_id]['END_SCRIPT'] : '' ?>
                                                    </textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-4">
                                                <div class="form-group">
                                                    <label>Script 1 for 1st Contact</label>
                                                    <textarea class="form-control" name="SCRIPT_1[<?= $loc_id ?>]" rows="5">

                                                    </textarea>
                                                </div>
                                            </div>

                                            <div class="col-4">
                                                <div class="form-group">
                                                    <label>Script 2 for 1st Contact</label>
                                                    <textarea class="form-control" name="SCRIPT_2[<?= $loc_id ?>]" rows="5">

                                                    </textarea>
                                                </div>
                                            </div>

                                            <div class="col-4">
                                                <div class="form-group">
                                                    <label>End Script for 1st Contact</label>
                                                    <textarea class="form-control" name="END_SCRIPT[<?= $loc_id ?>]" rows="5">

                                                    </textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-4">
                                                <div class="form-group">
                                                    <label>Script 1 for 2nd Contact</label>
                                                    <textarea class="form-control" name="SCRIPT_1[<?= $loc_id ?>]" rows="5">

                                                    </textarea>
                                                </div>
                                            </div>

                                            <div class="col-4">
                                                <div class="form-group">
                                                    <label>Script 2 for 2nd Contact</label>
                                                    <textarea class="form-control" name="SCRIPT_2[<?= $loc_id ?>]" rows="5">

                                                    </textarea>
                                                </div>
                                            </div>

                                            <div class="col-4">
                                                <div class="form-group">
                                                    <label>End Script for 2nd Contact</label>
                                                    <textarea class="form-control" name="END_SCRIPT[<?= $loc_id ?>]" rows="5">

                                                    </textarea>
                                                </div>
                                            </div>
                                        </div>







                                        <div class="row">
                                            <div class="col-4">
                                                <div class="form-group">
                                                    <label>Script for Yes/No</label>
                                                    <textarea class="form-control" name="SCRIPT_1[<?= $loc_id ?>]" rows="5">

                                                    </textarea>
                                                </div>
                                            </div>

                                            <div class="col-4">
                                                <div class="form-group">
                                                    <label>Script if Yes</label>
                                                    <textarea class="form-control" name="SCRIPT_2[<?= $loc_id ?>]" rows="5">

                                                    </textarea>
                                                </div>
                                            </div>

                                            <div class="col-4">
                                                <div class="form-group">
                                                    <label>Script if No</label>
                                                    <textarea class="form-control" name="END_SCRIPT[<?= $loc_id ?>]" rows="5">

                                                    </textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-4">
                                                <div class="form-group">
                                                    <label>Script if No Response is received</label>
                                                    <textarea class="form-control" name="SCRIPT_1[<?= $loc_id ?>]" rows="5">

                                                    </textarea>
                                                </div>
                                            </div>
                                        </div>

                                    <?php
                                        $locations->MoveNext();
                                    } ?>

                                    <button type="submit" class="btn btn-info text-white">Submit</button>
                                    <button type="button" class="btn btn-inverse" onclick="window.location.href='setup.php'">Cancel</button>

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
        placeholder: 'Select Part of Day',
        selectAll: true
    });
</script>

</html>