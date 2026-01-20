<?php
require_once('../../global/config.php');
global $db;
global $db_account;
global $master_database;

$LOCATION_ID = $_POST['LOCATION_ID'];
?>
<option value="">Select Enrollment By</option>
<?php
$row = $db->Execute("SELECT DISTINCT(DOA_USERS.PK_USER), CONCAT(FIRST_NAME, ' ', LAST_NAME) AS NAME FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER LEFT JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER WHERE DOA_USER_ROLES.PK_ROLES IN (2, 3, 9, 10) AND DOA_USER_LOCATION.PK_LOCATION IN (".$LOCATION_ID.") AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]' AND DOA_USERS.ACTIVE = 1 ORDER BY FIRST_NAME");
while (!$row->EOF) { ?>
    <option value="<?php echo $row->fields['PK_USER'];?>"><?=$row->fields['NAME']?></option>
<?php $row->MoveNext(); } ?>