<?php
require_once('../../global/config.php');
?>
<select class="form-control" name="PK_STATES" id="PK_STATES">
    <option value="">Select State</option>
    <?php
    $PK_COUNTRY = $_POST['PK_COUNTRY'];
    $PK_STATES = $_POST['PK_STATES'];

    $result_dropdown_query = mysqli_query($conn,"SELECT * FROM DOA_STATES where PK_COUNTRY='$PK_COUNTRY'");
    while ($result_dropdown=mysqli_fetch_array($result_dropdown_query,MYSQLI_ASSOC)) { ?>
        <option value="<?php echo $result_dropdown['PK_STATES'];?>" <?php if($result_dropdown['PK_STATES'] == $PK_STATES) echo 'selected = "selected"';?> ><?=$result_dropdown['STATE_NAME']?></option>
        <?php
    }
    ?>
</select>