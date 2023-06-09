<?php
require_once('../../global/config.php');
?>
<?php
$row = $db->Execute("SELECT DOA_SERVICE_CODE.*, DOA_FREQUENCY.FREQUENCY FROM DOA_SERVICE_CODE LEFT JOIN DOA_FREQUENCY ON DOA_SERVICE_CODE.PK_FREQUENCY = DOA_FREQUENCY.PK_FREQUENCY WHERE PK_SERVICE_MASTER = ".$_POST['PK_SERVICE_MASTER']);
while (!$row->EOF) { $i=0; ?>
    <div class="row justify-content-end">
        <input type="hidden" class="form-control IS_PACKAGE" name="IS_PACKAGE" value="1">
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
