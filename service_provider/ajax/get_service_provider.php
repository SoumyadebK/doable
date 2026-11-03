<?php
require_once('../../global/config.php');

$location_data = $db->Execute("SELECT * FROM DOA_USER_LOCATION WHERE PK_USER = '$_SESSION[PK_USER]'");
$LOCATION_ARRAY = [];
if ($location_data->RecordCount() > 0) {
    while (!$location_data->EOF) {
        $LOCATION_ARRAY[] = $location_data->fields['PK_LOCATION'];
        $location_data->MoveNext();
    }
}

$SERVICE_PROVIDER_ARRAY[] = $_SESSION['PK_USER'];
$location_data = $db->Execute("SELECT DISTINCT(DOA_USERS.PK_USER) FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER INNER JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER WHERE DOA_USER_ROLES.PK_ROLES = 5 AND DOA_USER_LOCATION.PK_LOCATION IN (".implode(',', $LOCATION_ARRAY).")");
if ($location_data->RecordCount() > 0) {
    $SERVICE_PROVIDER_ARRAY = [];
    while (!$location_data->EOF) {
        $SERVICE_PROVIDER_ARRAY[] = $location_data->fields['PK_USER'];
        $location_data->MoveNext();
    }
}

$PK_ENROLLMENT_MASTER_ARRAY = explode(',', $_POST['PK_ENROLLMENT_MASTER']);
$PK_SERVICE_MASTER = $PK_ENROLLMENT_MASTER_ARRAY[2];
?>
<option value="">Select <?=$service_provider_title?></option>
<?php
$row = $db->Execute("SELECT DOA_USERS.PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME FROM DOA_USERS JOIN DOA_SERVICE_PROVIDER_SERVICES ON DOA_USERS.PK_USER = DOA_SERVICE_PROVIDER_SERVICES.PK_USER WHERE DOA_SERVICE_PROVIDER_SERVICES.PK_SERVICE_MASTER LIKE ".$PK_SERVICE_MASTER." OR DOA_SERVICE_PROVIDER_SERVICES.PK_SERVICE_MASTER LIKE '%,".$PK_SERVICE_MASTER.",%' OR DOA_SERVICE_PROVIDER_SERVICES.PK_SERVICE_MASTER LIKE '".$PK_SERVICE_MASTER.",%' OR DOA_SERVICE_PROVIDER_SERVICES.PK_SERVICE_MASTER LIKE '%,".$PK_SERVICE_MASTER."' AND DOA_USERS.PK_USER IN (".implode(',', $SERVICE_PROVIDER_ARRAY).")");
while (!$row->EOF) { ?>
    <option value="<?php echo $row->fields['PK_USER'];?>"><?=$row->fields['NAME']?></option>
<?php $row->MoveNext(); } ?>
