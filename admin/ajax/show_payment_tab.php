<?php
require_once('../../global/config.php');
global $db;
global $db_account;
global $master_database;

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];

$PK_ENROLLMENT_MASTER = $_POST['PK_ENROLLMENT_MASTER'];
$PK_SERVICE_CLASS = $_POST['PK_SERVICE_CLASS'];
$enrollment_data = $db_account->Execute("SELECT ENROLLMENT_ID FROM `DOA_ENROLLMENT_MASTER` WHERE `PK_ENROLLMENT_MASTER` = '$PK_ENROLLMENT_MASTER'");
//$enrollment_number = $enrollment_data->fields['ENROLLMENT_ID'];
?>
<div class="col-12">
    <div class="form-group">
        <div class="row">
            <div class="col-md-1">
                <h4><b>Billing</b></h4>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="example-text" style="float: right;margin-top: 10px;">Enrollment Number</label>
            </div>
            <div class="col-md-4">
                <input type="text" class="form-control" value="<?=$enrollment_data->fields['ENROLLMENT_ID']?>" disabled>
            </div>
        </div>
    </div>
</div>

<div class="card-body" id="append_service_div" style="margin-top: -25px;">
    <div class="row">
        <div class="col-2">
            <div class="form-group">
                <label class="form-label">Services</label>
            </div>
        </div>
        <div class="col-1">
            <div class="form-group">
                <label class="form-label">Service Codes</label>
            </div>
        </div>
        <div class="col-2">
            <div class="form-group">
                <label class="form-label">Service Details</label>
            </div>
        </div>
        <div class="col-1">
            <div class="form-group">
                <label class="form-label">Scheduling Code</label>
            </div>
        </div>
        <div class="col-1">
            <div class="form-group">
                <label class="form-label">Number of Sessions</label>
            </div>
        </div>
        <div class="col-1">
            <div class="form-group">
                <label class="form-label">Price Per Sessions</label>
            </div>
        </div>
        <div class="col-1">
            <div class="form-group">
                <label class="form-label">Amount</label>
            </div>
        </div>
        <div class="col-1">
            <div class="form-group">
                <label class="form-label">Discount Type</label>
            </div>
        </div>
        <div class="col-1">
            <div class="form-group">
                <label class="form-label">Discount</label>
            </div>
        </div>
        <div class="col-1">
            <div class="form-group">
                <label class="form-label">Final Amount</label>
            </div>
        </div>
    </div>

    <?php
    $total = 0;
    $enrollment_service_data = $db_account->Execute("SELECT * FROM DOA_ENROLLMENT_SERVICE WHERE PK_ENROLLMENT_MASTER = '$PK_ENROLLMENT_MASTER'");
    while (!$enrollment_service_data->EOF) {
        $total += $enrollment_service_data->fields['FINAL_AMOUNT']; ?>
        <input type="hidden" name="PK_ENROLLMENT_SERVICE[]" value="<?=$enrollment_service_data->fields['PK_ENROLLMENT_SERVICE']?>">
        <div class="row" style="margin-top: -20px;">
            <div class="col-2">
                <div class="form-group">
                    <select class="form-control PK_SERVICE_MASTER" onchange="selectThisService(this)" disabled>
                        <option>Select</option>
                        <?php
                        $row = $db_account->Execute("SELECT PK_SERVICE_MASTER, SERVICE_NAME FROM DOA_SERVICE_MASTER WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]' AND ACTIVE = 1 ORDER BY SERVICE_NAME");
                        while (!$row->EOF) { ?>
                            <option value="<?php echo $row->fields['PK_SERVICE_MASTER'];?>" <?=($row->fields['PK_SERVICE_MASTER'] == $enrollment_service_data->fields['PK_SERVICE_MASTER'])?'selected':''?>><?=$row->fields['SERVICE_NAME']?></option>
                            <?php $row->MoveNext(); } ?>
                    </select>
                </div>
            </div>
            <div class="col-1">
                <div class="form-group">
                    <select class="form-control PK_SERVICE_CODE" onchange="selectThisServiceCode(this)" disabled>
                        <option value="">Select</option>
                        <?php
                        $row = $db_account->Execute("SELECT * FROM DOA_SERVICE_CODE WHERE PK_SERVICE_MASTER = ".$enrollment_service_data->fields['PK_SERVICE_MASTER']);
                        while (!$row->EOF) { ?>
                            <option value="<?php echo $row->fields['PK_SERVICE_CODE'];?>" data-service_details="<?=$row->fields['DESCRIPTION']?>" data-price="<?=$row->fields['PRICE']?>" <?=($row->fields['PK_SERVICE_CODE'] == $enrollment_service_data->fields['PK_SERVICE_CODE'])?'selected':''?>><?=$row->fields['SERVICE_CODE']?></option>
                            <?php $row->MoveNext(); } ?>
                    </select>
                </div>
            </div>
            <div class="col-2">
                <div class="form-group">
                    <input type="text" class="form-control SERVICE_DETAILS" value="<?=$enrollment_service_data->fields['SERVICE_DETAILS']?>" disabled>
                </div>
            </div>
            <div class="col-1">
                <div class="form-group">
                    <select class="form-control PK_SCHEDULING_CODE" disabled>
                        <option>Select</option>
                        <?php
                        $row = $db_account->Execute("SELECT `PK_SCHEDULING_CODE`, `SCHEDULING_CODE`, `SCHEDULING_NAME` FROM `DOA_SCHEDULING_CODE` WHERE `ACTIVE` = 1");
                        while (!$row->EOF) { ?>
                            <option value="<?php echo $row->fields['PK_SCHEDULING_CODE'];?>" <?=($row->fields['PK_SCHEDULING_CODE'] == $enrollment_service_data->fields['PK_SCHEDULING_CODE'])?'selected':''?>><?=$row->fields['SCHEDULING_CODE'].' ('.$row->fields['SCHEDULING_CODE'].')'?></option>
                            <?php $row->MoveNext(); } ?>
                    </select>
                </div>
            </div>
            <div class="col-1">
                <div class="form-group">
                    <input type="text" class="form-control NUMBER_OF_SESSION" value="<?=$enrollment_service_data->fields['NUMBER_OF_SESSION']?>" disabled>
                </div>
            </div>
            <div class="col-1">
                <div class="form-group">
                    <input type="text" class="form-control PRICE_PER_SESSION" value="<?=$enrollment_service_data->fields['PRICE_PER_SESSION']?>" disabled>
                </div>
            </div>
            <div class="col-1">
                <div class="form-group">
                    <input type="text" class="form-control TOTAL" value="<?=$enrollment_service_data->fields['TOTAL']?>" disabled>
                </div>
            </div>
            <div class="col-1">
                <div class="form-group">
                    <select class="form-control DISCOUNT_TYPE" name="DISCOUNT_TYPE[]" onchange="calculateDiscount(this)">
                        <option value="">Select</option>
                        <option value="1" <?=($enrollment_service_data->fields['DISCOUNT_TYPE'] == 1)?'selected':''?>>Fixed</option>
                        <option value="2" <?=($enrollment_service_data->fields['DISCOUNT_TYPE'] == 2)?'selected':''?>>Percent</option>
                    </select>
                </div>
            </div>
            <div class="col-1">
                <div class="form-group">
                    <input type="text" class="form-control DISCOUNT" name="DISCOUNT[]" value="<?=$enrollment_service_data->fields['DISCOUNT']?>" onkeyup="calculateDiscount(this)">
                </div>
            </div>
            <div class="col-1">
                <div class="form-group">
                    <input type="text" class="form-control FINAL_AMOUNT" name="FINAL_AMOUNT[]" value="<?=$enrollment_service_data->fields['FINAL_AMOUNT']?>" readonly>
                </div>
            </div>
        </div>
    <?php $enrollment_service_data->MoveNext(); } ?>

    <div class="col-3" style="margin-left: 75%; margin-top: -15px;">
        <div class="form-group">
            <div class="row">
                <div class="col-md-4">
                    <label class="form-label" style="float: right; margin-top: 10px;">Total</label>
                </div>
                <div class="col-md-8">
                    <input type="text" class="form-control TOTAL_AMOUNT" name="TOTAL_AMOUNT" id="total_bill" value="<?=number_format((float)$total, 2, '.', '');?>" readonly>
                </div>
            </div>
        </div>
    </div>
</div>