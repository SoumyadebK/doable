<?php
require_once('../../global/config.php');
global $db;
global $db_account;
global $master_database;

$PK_ACCOUNT_MASTER = $_GET['PK_ACCOUNT_MASTER'];
?>
<option value="">Select Location</option>
<?php
$row = $db->Execute("SELECT DISTINCT DOA_LOCATION.PK_LOCATION, DOA_LOCATION.LOCATION_NAME, DOA_LOCATION.LOCATION_CODE FROM DOA_LOCATION WHERE PK_ACCOUNT_MASTER = '$PK_ACCOUNT_MASTER' AND ACTIVE = 1 ORDER BY DOA_LOCATION.LOCATION_NAME");
while (!$row->EOF) { ?>
    <option value="<?= $row->fields['PK_LOCATION']; ?>" <?= (!empty($_SESSION['MIGRATION_LOCATION']) && $_SESSION['MIGRATION_LOCATION'] == $row->fields['PK_LOCATION']) ? 'selected' : '' ?>>(<?= $row->fields['LOCATION_CODE'] ?>) <?= $row->fields['LOCATION_NAME'] ?></option>
<?php $row->MoveNext();
} ?>