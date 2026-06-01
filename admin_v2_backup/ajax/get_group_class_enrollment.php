<?php
require_once('../../global/config.php');
global $db;
global $db_account;
global $master_database;

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];

$PK_SERVICE_CODE = $_POST['PK_SERVICE_CODE'];
$PK_USER_MASTER = $_POST['PK_USER_MASTER'];
?>
<?php
$enrollment_data = $db_account->Execute("SELECT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, DOA_PACKAGE.PACKAGE_NAME, DOA_ENROLLMENT_MASTER.ENROLLMENT_NAME, DOA_ENROLLMENT_MASTER.PK_LOCATION, DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_SERVICE, DOA_SERVICE_MASTER.PK_SERVICE_MASTER, DOA_SERVICE_CODE.PK_SERVICE_CODE, DOA_ENROLLMENT_MASTER.ENROLLMENT_ID, DOA_ENROLLMENT_MASTER.CHARGE_TYPE, DOA_SERVICE_MASTER.SERVICE_NAME, DOA_SERVICE_CODE.SERVICE_CODE, DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION, DOA_ENROLLMENT_SERVICE.PRICE_PER_SESSION, DOA_ENROLLMENT_SERVICE.TOTAL_AMOUNT_PAID, DOA_ENROLLMENT_SERVICE.FINAL_AMOUNT FROM DOA_ENROLLMENT_MASTER RIGHT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER LEFT JOIN DOA_SERVICE_MASTER ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER LEFT JOIN DOA_SERVICE_CODE ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE LEFT JOIN DOA_PACKAGE ON DOA_ENROLLMENT_MASTER.PK_PACKAGE = DOA_PACKAGE.PK_PACKAGE WHERE DOA_SERVICE_MASTER.PK_SERVICE_CLASS != 5 AND DOA_SERVICE_CODE.IS_GROUP = 1 AND DOA_ENROLLMENT_MASTER.STATUS = 'A' AND DOA_ENROLLMENT_MASTER.ALL_APPOINTMENT_DONE = 0 AND DOA_SERVICE_CODE.PK_SERVICE_CODE = $PK_SERVICE_CODE AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $DEFAULT_LOCATION_ID . ") AND DOA_ENROLLMENT_MASTER.PK_USER_MASTER = " . $PK_USER_MASTER . " ORDER BY DOA_SERVICE_CODE.SORT_ORDER"); ?>

<div class="row mb-3 mt-3">
    <div class="col-4 col-md-4">
        <div class="d-flex gap-2 align-items-center">
            <label class="mb-0" style="margin-left: 33px;">Partner Details</label>
        </div>
    </div>
    <div class="col-8 col-md-8">
        <div style="font-size: 13px;">
            <?php
            $partner_data = $db_account->Execute("SELECT * FROM `DOA_CUSTOMER_DETAILS` WHERE `PK_USER_MASTER` = " . $PK_USER_MASTER);
            if ($partner_data->RecordCount() > 0 && $partner_data->fields['ATTENDING_WITH'] == 'With a Partner') {
                echo "<p>" . $partner_data->fields['PARTNER_FIRST_NAME'] . ' ' . $partner_data->fields['PARTNER_LAST_NAME'] . "</p>"; ?>
                <label style="font-weight: normal;"><input type="radio" name="WITH_PARTNER" value="0" checked> Without a Partner</label> &nbsp;&nbsp;
                <label style="font-weight: normal;"><input type="radio" name="WITH_PARTNER" value="1"> With a Partner</label>
            <?php
            } else {
                echo "<p>" . 'No partner details found.' . "</p>";
            }
            ?>
        </div>
    </div>
</div>


<div class="row mb-3 mt-3">
    <div class="col-4 col-md-4">
        <div class="d-flex gap-2 align-items-center">
            <label class="mb-0" style="margin-left: 33px;">Enrollment ID</label>
        </div>
    </div>
    <div class="col-8 col-md-8">
        <div class="form-group">
            <?php
            if ($enrollment_data->RecordCount() > 0) {
                while (!$enrollment_data->EOF) {
                    $PK_ENROLLMENT_MASTER = $enrollment_data->fields['PK_ENROLLMENT_MASTER'];
                    $name = $enrollment_data->fields['ENROLLMENT_NAME'];
                    if (empty($name)) {
                        $enrollment_name = ' ';
                    } else {
                        $enrollment_name = "$name" . " || ";
                    }

                    $PACKAGE_NAME = $enrollment_data->fields['PACKAGE_NAME'];
                    if (empty($PACKAGE_NAME)) {
                        $PACKAGE = ' ';
                    } else {
                        $PACKAGE = " || " . "$PACKAGE_NAME";
                    }

                    if ($enrollment_data->fields['CHARGE_TYPE'] == 'Membership') {
                        $NUMBER_OF_SESSION = 99; //getAllSessionCreatedCount($enrollment_data->fields['PK_ENROLLMENT_SERVICE'], 'NORMAL');
                    } else {
                        $NUMBER_OF_SESSION = $enrollment_data->fields['NUMBER_OF_SESSION'];
                    }

                    $PRICE_PER_SESSION = $enrollment_data->fields['PRICE_PER_SESSION'];
                    $TOTAL_AMOUNT_PAID = ($enrollment_data->fields['TOTAL_AMOUNT_PAID'] != null) ? $enrollment_data->fields['TOTAL_AMOUNT_PAID'] : 0;
                    $USED_SESSION_COUNT = getAllSessionCreatedCount($enrollment_data->fields['PK_ENROLLMENT_SERVICE'], 'GROUP');
                    $paid_session = ($PRICE_PER_SESSION > 0) ? number_format(($TOTAL_AMOUNT_PAID / $PRICE_PER_SESSION), 2) : $NUMBER_OF_SESSION;

                    if ((($NUMBER_OF_SESSION - $USED_SESSION_COUNT) > 0) || ($enrollment_data->fields['CHARGE_TYPE'] == 'Membership')) { ?>
                        <div class="form-check border rounded-2 p-2 mb-2">
                            <label class="form-check-label">
                                <input class="form-check-input ms-0 me-1" type="radio" name="PK_ENROLLMENT_MASTER" data-location_id="<?= $enrollment_data->fields['PK_LOCATION'] ?>" data-no_of_session="<?= $NUMBER_OF_SESSION ?>" data-used_session="<?= $USED_SESSION_COUNT ?>" value="<?php echo $enrollment_data->fields['PK_ENROLLMENT_MASTER'] . ',' . $enrollment_data->fields['PK_ENROLLMENT_SERVICE'] . ',' . $enrollment_data->fields['PK_SERVICE_MASTER'] . ',' . $enrollment_data->fields['PK_SERVICE_CODE']; ?>" <?= (($NUMBER_OF_SESSION - $USED_SESSION_COUNT) <= 0) ? 'disabled' : '' ?>><?= $enrollment_name . $enrollment_data->fields['ENROLLMENT_ID'] . $PACKAGE ?>
                            </label>
                            <?php if ($TOTAL_AMOUNT_PAID >= $enrollment_data->fields['FINAL_AMOUNT']) { ?>
                                <span class="checkicon float-end">
                                    <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Layer_1" x="0px" y="0px" viewBox="0 0 512 512" style="enable-background:new 0 0 512 512;" xml:space="preserve" width="12px" height="12px" fill="#1FC16B">
                                        <path d="M256,0C114.615,0,0,114.615,0,256s114.615,256,256,256s256-114.615,256-256S397.385,0,256,0z M219.429,367.932 L108.606,257.108l38.789-38.789l72.033,72.035L355.463,154.32l38.789,38.789L219.429,367.932z" />
                                    </svg>
                                </span>
                            <?php } ?>
                            <div class="statusarea mt-1">
                                <span><?= $enrollment_data->fields['SERVICE_NAME'] . ' (' . $enrollment_data->fields['SERVICE_CODE'] . ')' ?> : <?= $USED_SESSION_COUNT ?>/<?= $NUMBER_OF_SESSION ?></span>
                            </div>
                        </div>
                <?php }
                    $enrollment_data->MoveNext();
                }
            } else { ?>
                <div class="form-check border rounded-2 p-2 mb-2">
                    <label class="form-check-label">
                        <input class="form-check-input ms-0 me-1" type="radio" name="PK_ENROLLMENT_MASTER" value="AD-HOC">Ad-Hoc
                    </label>
                </div>
            <?php } ?>

        </div>
    </div>
</div>