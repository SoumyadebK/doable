<?php
require_once('../../global/config.php');
global $db;
global $db_account;
global $master_database;

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];
?>
<option value="">Select Enrollment ID</option>
<?php
$row = $db_account->Execute("SELECT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, DOA_PACKAGE.PACKAGE_NAME, DOA_ENROLLMENT_MASTER.ENROLLMENT_NAME, DOA_ENROLLMENT_MASTER.PK_LOCATION, DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_SERVICE, DOA_SERVICE_MASTER.PK_SERVICE_MASTER, DOA_SERVICE_CODE.PK_SERVICE_CODE, DOA_ENROLLMENT_MASTER.ENROLLMENT_ID, DOA_ENROLLMENT_MASTER.CHARGE_TYPE, DOA_SERVICE_MASTER.SERVICE_NAME, DOA_SERVICE_CODE.SERVICE_CODE, DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION, DOA_ENROLLMENT_SERVICE.PRICE_PER_SESSION, DOA_ENROLLMENT_SERVICE.TOTAL_AMOUNT_PAID FROM DOA_ENROLLMENT_MASTER RIGHT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER LEFT JOIN DOA_SERVICE_MASTER ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER LEFT JOIN DOA_SERVICE_CODE ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE LEFT JOIN DOA_PACKAGE ON DOA_ENROLLMENT_MASTER.PK_PACKAGE = DOA_PACKAGE.PK_PACKAGE WHERE DOA_SERVICE_MASTER.PK_SERVICE_CLASS != 5 AND DOA_SERVICE_CODE.IS_GROUP != 1 AND DOA_ENROLLMENT_MASTER.STATUS = 'A' AND DOA_ENROLLMENT_MASTER.ALL_APPOINTMENT_DONE = 0 AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $DEFAULT_LOCATION_ID . ") AND DOA_ENROLLMENT_MASTER.PK_USER_MASTER = " . $_POST['PK_USER_MASTER'] . " ORDER BY DOA_SERVICE_CODE.SORT_ORDER");
while (!$row->EOF) {
    $PK_ENROLLMENT_MASTER = $row->fields['PK_ENROLLMENT_MASTER'];
    $name = $row->fields['ENROLLMENT_NAME'];
    if (empty($name)) {
        $enrollment_name = ' ';
    } else {
        $enrollment_name = "$name" . " || ";
    }

    $PACKAGE_NAME = $row->fields['PACKAGE_NAME'];
    if (empty($PACKAGE_NAME)) {
        $PACKAGE = ' ';
    } else {
        $PACKAGE = " || " . "$PACKAGE_NAME";
    }

    if ($row->fields['CHARGE_TYPE'] == 'Membership') {
        $NUMBER_OF_SESSION = 99; //getAllSessionCreatedCount($row->fields['PK_ENROLLMENT_SERVICE'], 'NORMAL');
    } else {
        $NUMBER_OF_SESSION = $row->fields['NUMBER_OF_SESSION'];
    }

    $PRICE_PER_SESSION = $row->fields['PRICE_PER_SESSION'];
    $TOTAL_AMOUNT_PAID = ($row->fields['TOTAL_AMOUNT_PAID'] != null) ? $row->fields['TOTAL_AMOUNT_PAID'] : 0;
    $USED_SESSION_COUNT = getAllSessionCreatedCount($row->fields['PK_ENROLLMENT_SERVICE'], 'NORMAL');
    $paid_session = ($PRICE_PER_SESSION > 0) ? number_format(($TOTAL_AMOUNT_PAID / $PRICE_PER_SESSION), 2) : $NUMBER_OF_SESSION;

    if ((($NUMBER_OF_SESSION - $USED_SESSION_COUNT) > 0) || ($row->fields['CHARGE_TYPE'] == 'Membership')) { ?>
        <!-- <option value="<?php echo $row->fields['PK_ENROLLMENT_MASTER'] . ',' . $row->fields['PK_ENROLLMENT_SERVICE'] . ',' . $row->fields['PK_SERVICE_MASTER'] . ',' . $row->fields['PK_SERVICE_CODE']; ?>" data-location_id="<?= $row->fields['PK_LOCATION'] ?>" data-no_of_session="<?= $NUMBER_OF_SESSION ?>" data-used_session="<?= $USED_SESSION_COUNT ?>" <?= (($NUMBER_OF_SESSION - $USED_SESSION_COUNT) <= 0) ? 'disabled' : '' ?>><?= $enrollment_name . $row->fields['ENROLLMENT_ID'] . ' || ' . $PACKAGE . $row->fields['SERVICE_NAME'] . ' || ' . $row->fields['SERVICE_CODE'] . ' || ' . $USED_SESSION_COUNT . '/' . $NUMBER_OF_SESSION . ' || Paid : ' . $paid_session; ?></option> -->



        <div class="form-check border rounded-2 p-2 mb-2">
            <label class="form-check-label">
                <input class="form-check-input ms-0 me-1" type="radio" name="PK_ENROLLMENT_MASTER" data-location_id="<?= $row->fields['PK_LOCATION'] ?>" data-no_of_session="<?= $NUMBER_OF_SESSION ?>" data-used_session="<?= $USED_SESSION_COUNT ?>" value="<?php echo $row->fields['PK_ENROLLMENT_MASTER'] . ',' . $row->fields['PK_ENROLLMENT_SERVICE'] . ',' . $row->fields['PK_SERVICE_MASTER'] . ',' . $row->fields['PK_SERVICE_CODE']; ?>" onclick="selectThisEnrollment(this)"><?= $enrollment_name . $row->fields['ENROLLMENT_ID'] . $PACKAGE ?>
            </label>
            <!-- <span class="checkicon float-end">
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Layer_1" x="0px" y="0px" viewBox="0 0 512 512" style="enable-background:new 0 0 512 512;" xml:space="preserve" width="12px" height="12px" fill="#1FC16B">
                    <path d="M256,0C114.615,0,0,114.615,0,256s114.615,256,256,256s256-114.615,256-256S397.385,0,256,0z M219.429,367.932 L108.606,257.108l38.789-38.789l72.033,72.035L355.463,154.32l38.789,38.789L219.429,367.932z" />
                </svg>
            </span> -->
            <div class="statusarea mt-1">
                <span><?= $row->fields['SERVICE_NAME'] ?> : <?= $USED_SESSION_COUNT ?>/<?= $NUMBER_OF_SESSION ?></span>
            </div>
        </div>


<?php }
    $row->MoveNext();
} ?>

<div class="form-check border rounded-2 p-2 mb-2">
    <input class="form-check-input ms-0 me-1" type="radio" name="Enrollment" id="female">
    <label class="form-check-label" for="female">AD-hoc</label>
</div>