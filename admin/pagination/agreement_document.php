<?php
require_once('../../global/config.php');
global $db_account;
global $upload_path;
?>
<a style="font-weight: bold">Client Enrollment Agreements :-</a><br>
<?php
$PK_USER_MASTER = !empty($_GET['master_id']) ? $_GET['master_id'] : 0;
$res = $db_account->Execute("SELECT * FROM `DOA_ENROLLMENT_MASTER` WHERE `PK_USER_MASTER` = ".$PK_USER_MASTER);
while (!$res->EOF) {?>
    <div style="margin-top: 5px">
        <?php if(file_exists('../'.$upload_path.'/enrollment_pdf/'.$res->fields['AGREEMENT_PDF_LINK'])) { ?>
            <?=$res->fields['ENROLLMENT_ID']?> - <a href="../<?=$upload_path?>/enrollment_pdf/<?=$res->fields['AGREEMENT_PDF_LINK']?>" target="_blank">  View Agreement</a><br>
        <?php } else { ?>
            <?=$res->fields['ENROLLMENT_ID']?> - <a href="javascript:">  View Agreement (Not Available)</a><br>
        <?php } ?>
    </div>
    <?php $res->MoveNext();
} ?>