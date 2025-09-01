<?php
require_once('../../global/config.php');
global $db;
global $db_account;
global $master_database;

$LOCATION_ID = $_POST['LOCATION_ID'];
?>
<!-- <?php
        $row = $db->Execute("SELECT DISTINCT DOA_LOCATION.PK_LOCATION, DOA_LOCATION.LOCATION_NAME FROM DOA_LOCATION LEFT JOIN DOA_USER_LOCATION ON DOA_USER_LOCATION.PK_LOCATION=DOA_LOCATION.PK_LOCATION WHERE DOA_USER_LOCATION.PK_USER = " . $_POST['PK_USER'] . " AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]' AND ACTIVE = 1 ORDER BY DOA_LOCATION.LOCATION_NAME");
        while (!$row->EOF) { ?>
    <option value="<?= $row->fields['PK_LOCATION']; ?>" data-location_id="<?= $row->fields['PK_LOCATION'] ?>" <?= ($LOCATION_ID == $row->fields['PK_LOCATION']) ? 'selected' : '' ?>><?= $row->fields['LOCATION_NAME'] ?></option>
<?php $row->MoveNext();
        } ?>
 -->
<?php
$row = $db->Execute("SELECT DISTINCT DOA_LOCATION.PK_LOCATION, DOA_LOCATION.LOCATION_NAME FROM DOA_LOCATION LEFT JOIN DOA_USER_MASTER ON DOA_USER_MASTER.PRIMARY_LOCATION_ID=DOA_LOCATION.PK_LOCATION WHERE DOA_USER_MASTER.PK_USER = " . $_POST['PK_USER'] . " AND DOA_USER_MASTER.PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]' AND ACTIVE = 1 ORDER BY DOA_LOCATION.LOCATION_NAME");
while (!$row->EOF) { ?>
    <option value="<?= $row->fields['PK_LOCATION']; ?>" data-location_id="<?= $row->fields['PK_LOCATION'] ?>" <?= ($LOCATION_ID == $row->fields['PK_LOCATION']) ? 'selected' : '' ?>><?= $row->fields['LOCATION_NAME'] ?></option>
<?php $row->MoveNext();
} ?>