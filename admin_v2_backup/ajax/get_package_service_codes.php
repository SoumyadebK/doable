<?php
require_once('../../global/config.php');
?>
<?php
$row = $db_account->Execute("SELECT DOA_SERVICE_CODE.*, DOA_FREQUENCY.FREQUENCY FROM DOA_SERVICE_CODE LEFT JOIN $master_database.DOA_FREQUENCY ON DOA_SERVICE_CODE.PK_FREQUENCY = $master_database.DOA_FREQUENCY.PK_FREQUENCY WHERE PK_SERVICE_MASTER = ".$_POST['PK_SERVICE_MASTER']);
while (!$row->EOF) { $i=0; ?>
    <div class="row justify-content-end">
        <input type="hidden" class="form-control IS_PACKAGE" name="IS_PACKAGE" value="1">
        <div class="col-2">
            <div class="form-group">
                <select class="form-control PK_SERVICE_MASTER" name="PK_SERVICE_MASTER[]" onchange="selectThisService(this)">
                    <option>Select</option>
                    <?php
                    $service_row = $db_account->Execute("SELECT PK_SERVICE_MASTER, SERVICE_NAME, PK_SERVICE_CLASS, IS_PACKAGE FROM DOA_SERVICE_MASTER WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]' AND ACTIVE = 1 ORDER BY SERVICE_NAME");
                    while (!$service_row->EOF) { ?>
                        <option value="<?php echo $service_row->fields['PK_SERVICE_MASTER'];?>" data-service_class="<?=$service_row->fields['PK_SERVICE_CLASS']?>" data-is_package="<?=$service_row->fields['IS_PACKAGE']?>" <?=($row->fields['PK_SERVICE_MASTER'] == $service_row->fields['PK_SERVICE_MASTER'])?'selected':''?>><?=$service_row->fields['SERVICE_NAME']?></option>
                        <?php $service_row->MoveNext(); } ?>
                </select>
            </div>
        </div>
        <div class="col-2">
            <div class="form-group">
                <input type="hidden" class="form-control PK_SERVICE_CODE" name="PK_SERVICE_CODE[]" value="<?=$row->fields['PK_SERVICE_CODE']?>">
                <input type="text" class="form-control PK_SERVICE_CODE" value="<?=$row->fields['SERVICE_CODE']?>">
            </div>
        </div>
        <div class="col-2">
            <div class="form-group">
                <input type="text" class="form-control SERVICE_DETAILS" name="SERVICE_DETAILS[]" value="<?=$row->fields['DESCRIPTION']?>">
            </div>
        </div>
        <div class="col-2">
            <div class="form-group">
                <input type="text" class="form-control NUMBER_OF_SESSION" name="NUMBER_OF_SESSION[]" value="<?=$row->fields['NUMBER_OF_SESSIONS']?>" onkeyup="calculateServiceTotal(this)">
            </div>
        </div>
        <div class="col-2">
            <div class="form-group">
                <input type="text" class="form-control PRICE_PER_SESSION" name="PRICE_PER_SESSION[]" value="<?=$row->fields['PRICE']?>" onkeyup="calculateServiceTotal(this)">
            </div>
        </div>
        <div class="col-1" style="width: 11%;">
            <div class="form-group">
                <input type="text" class="form-control TOTAL" value="<?=$row->fields['NUMBER_OF_SESSIONS']*$row->fields['PRICE']?>" name="TOTAL[]">
            </div>
        </div>
        <div class="col-1" style="width: 5%;">
            <div class="form-group">
                <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
            </div>
        </div>
    </div>
<?php $row->MoveNext(); $i++;} ?>

<script>
    function calculateServiceTotal(param) {
        let number_of_session = $(param).closest('.row').find('.NUMBER_OF_SESSION').val();
        number_of_session = (number_of_session)?number_of_session:0;
        let service_price = $(param).closest('.row').find('.PRICE_PER_SESSION').val();
        service_price = (service_price)?service_price:0;
        $(param).closest('.row').find('.TOTAL').val(parseFloat(parseFloat(service_price)*parseFloat(number_of_session)).toFixed(2));
    }
</script>
