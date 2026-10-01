<?php
require_once('../../global/config.php');
?>
<select class="form-control" name="PK_STATES" id="PK_STATES">
    <option>Select State</option>
    <?php
    $PK_COUNTRY = $_POST['PK_COUNTRY'];
    $PK_STATES = $_POST['PK_STATES'];
    $row = $db->Execute("SELECT * FROM DOA_STATES WHERE PK_COUNTRY='$PK_COUNTRY'");
    while (!$row->EOF) { ?>
        <option value="<?php echo $row->fields['PK_STATES'];?>" <?=($row->fields['PK_STATES'] == $PK_STATES)?"selected":""?>><?=$row->fields['STATE_NAME']?></option>
    <?php $row->MoveNext(); } ?>
</select>