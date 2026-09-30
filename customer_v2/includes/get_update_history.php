<?php
require_once('../../global/config.php');

global $db;
global $db_account;
global $account_database;
global $master_database;

$PRIMARY_KEY = $_GET['PK_ENROLLMENT_LEDGER'];
$CLASS = $_GET['CLASS'];
$FIELD_NAME = $_GET['FIELD_NAME'];

$update_history_data = $db_account->Execute("SELECT DOA_UPDATE_HISTORY.*, DOA_USERS.FIRST_NAME, DOA_USERS.LAST_NAME FROM DOA_UPDATE_HISTORY INNER JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_UPDATE_HISTORY.EDITED_BY = DOA_USERS.PK_USER WHERE DOA_UPDATE_HISTORY.CLASS = '$CLASS' AND DOA_UPDATE_HISTORY.PRIMARY_KEY = '$PRIMARY_KEY' AND DOA_UPDATE_HISTORY.FIELD_NAME = '$FIELD_NAME' ORDER BY DOA_UPDATE_HISTORY.PK_UPDATE_HISTORY ASC");
if ($update_history_data->RecordCount() > 0) {
    while (!$update_history_data->EOF) { ?>
        <p>Change from <b><?=$update_history_data->fields['FROM_VALUE']?></b> to <b><?=$update_history_data->fields['TO_VALUE']?></b> On <b>(<?=date('m/d/Y - h:i a', strtotime($update_history_data->fields['EDITED_ON']))?>)</b> By <b><?=$update_history_data->fields['FIRST_NAME']." ".$update_history_data->fields['LAST_NAME']?></b></p>
    <?php $update_history_data->MoveNext(); }
} else { ?>
    <p>No data available</p>
<?php } ?>




