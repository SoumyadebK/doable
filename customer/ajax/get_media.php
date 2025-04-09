<?php
require_once('../../global/config.php');
global $db;
global $db_account;
global $master_database;

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];

$media_data = $db_account->Execute("SELECT IMAGE, VIDEO FROM DOA_APPOINTMENT_MASTER WHERE PK_APPOINTMENT_MASTER = ".$_POST['PK_APPOINTMENT_MASTER']);
    $IMAGE_LINK = $media_data->fields['IMAGE'];
    $VIDEO_LINK = $media_data->fields['VIDEO'];
    ?>
    <div class="row">
        <div class="col-6">
            <div class="form-group">
                    <img src="<?=$IMAGE_LINK?>" onclick="showPopup('image', '<?=$IMAGE_LINK?>')" style="cursor: pointer; margin-top: 15px; width: 150px; height: auto;">
            </div>
        </div>
        <div class="col-6">
            <div class="form-group">
                <?php if($VIDEO_LINK != '') {?>
                    <video width="240" height="135" controls onclick="showPopup('video', '<?=$VIDEO_LINK?>')" style="cursor: pointer;">
                        <source src="<?=$VIDEO_LINK?>" type="video/mp4">
                    </video>
                <?php }?>
            </div>
        </div>
    </div>



