<?php
require_once('../../global/config.php');
global $db;
global $db_account;
global $master_database;

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];

?>
<?php
$package_service_data = $db_account->Execute("SELECT * FROM DOA_PACKAGE_SERVICE WHERE PK_PACKAGE = " . $_POST['PK_PACKAGE']);
while (!$package_service_data->EOF) { ?>
    <div class="row package_div">
        <div class="col-2">
            <div class="form-group">
                <select class="form-control PK_SERVICE_MASTER" name="PK_SERVICE_MASTER[]" onchange="selectThisService(this)" required>
                    <option>Select Service</option>
                    <?php
                    $row = $db_account->Execute("SELECT DISTINCT DOA_SERVICE_MASTER.PK_SERVICE_MASTER, DOA_SERVICE_MASTER.SERVICE_NAME, DOA_SERVICE_MASTER.DESCRIPTION, DOA_SERVICE_MASTER.ACTIVE FROM `DOA_SERVICE_MASTER` WHERE DOA_SERVICE_MASTER.PK_LOCATION IN (" . $DEFAULT_LOCATION_ID . ") AND ACTIVE = 1 AND IS_DELETED = 0");
                    while (!$row->EOF) { ?>
                        <option value="<?php echo $row->fields['PK_SERVICE_MASTER']; ?>" <?= ($row->fields['PK_SERVICE_MASTER'] == $package_service_data->fields['PK_SERVICE_MASTER']) ? 'selected' : '' ?>><?= $row->fields['SERVICE_NAME'] ?></option>
                    <?php $row->MoveNext();
                    } ?>
                </select>
            </div>
        </div>
        <div class="col-1">
            <div class="form-group">
                <select class="form-control PK_SERVICE_CODE" name="PK_SERVICE_CODE[]" onchange="selectThisServiceCode(this)" required>
                    <?php
                    $row = $db_account->Execute("SELECT * FROM `DOA_SERVICE_CODE` WHERE `PK_SERVICE_MASTER` = " . $package_service_data->fields['PK_SERVICE_MASTER']);
                    while (!$row->EOF) { ?>
                        <option value="<?php echo $row->fields['PK_SERVICE_CODE']; ?>" data-details="<?= $row->fields['DESCRIPTION'] ?>" data-price="<?= $row->fields['PRICE'] ?>" <?= ($row->fields['PK_SERVICE_CODE'] == $package_service_data->fields['PK_SERVICE_CODE']) ? 'selected' : '' ?>><?= $row->fields['SERVICE_CODE'] ?></option>
                    <?php $row->MoveNext();
                    } ?>
                </select>
            </div>
        </div>
        <div class="col-2">
            <div class="form-group">
                <input type="text" class="form-control SERVICE_DETAILS" name="SERVICE_DETAILS[]" value="<?= $package_service_data->fields['SERVICE_DETAILS'] ?>">
            </div>
        </div>
        <div class="col-1">
            <div class="form-group">
                <input type="text" class="form-control NUMBER_OF_SESSION" name="NUMBER_OF_SESSION[]" value="<?= $package_service_data->fields['NUMBER_OF_SESSION'] ?>" onkeyup="calculateServiceTotal(this)" required>
            </div>
        </div>
        <div class="col-1">
            <div class="form-group">
                <input type="text" class="form-control PRICE_PER_SESSION" name="PRICE_PER_SESSION[]" value="<?= $package_service_data->fields['PRICE_PER_SESSION'] ?>" onkeyup="calculateServiceTotal(this);" required>
            </div>
        </div>
        <div class="col-1">
            <div class="form-group">
                <input type="text" class="form-control TOTAL" name="TOTAL[]" value="<?= $package_service_data->fields['TOTAL'] ?>" readonly>
            </div>
        </div>
        <div class="col-1">
            <div class="form-group">
                <select class="form-control DISCOUNT_TYPE" name="DISCOUNT_TYPE[]" onchange="calculateServiceTotal(this)">
                    <option value="">Select</option>
                    <option value="1" <?= ($package_service_data->fields['DISCOUNT_TYPE'] == 1) ? 'selected' : '' ?>>Fixed</option>
                    <option value="2" <?= ($package_service_data->fields['DISCOUNT_TYPE'] == 2) ? 'selected' : '' ?>>Percent</option>
                </select>
            </div>
        </div>
        <div class="col-1">
            <div class="form-group">
                <input type="text" class="form-control DISCOUNT" name="DISCOUNT[]" value="<?= $package_service_data->fields['DISCOUNT'] ?>" onkeyup="calculateServiceTotal(this)">
            </div>
        </div>
        <div class="col-1">
            <div class="form-group">
                <input type="text" class="form-control FINAL_AMOUNT" name="FINAL_AMOUNT[]" value="<?= $package_service_data->fields['FINAL_AMOUNT'] ?>" readonly>
            </div>
        </div>
        <div class="col-1" style="width: 5%;">
            <div class="form-group">
                <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
            </div>
        </div>
    </div>
<?php $package_service_data->MoveNext();
} ?>