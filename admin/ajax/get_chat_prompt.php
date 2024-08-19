<?php
require_once('../../global/config.php');
global $db;

$PK_AI_CHAT_SECTION = $_GET['PK_AI_CHAT_SECTION'];
?>
<?php
$row = $db->Execute("SELECT * FROM `DOA_AI_CHAT_PROMPT` WHERE `PK_AI_CHAT_SECTION` = ".$PK_AI_CHAT_SECTION);
while (!$row->EOF) { ?>
    <button type="button" class="btn btn-light opt_btn" style="border: 1px solid gray; border-radius: 20px; margin-right: 10px;" onclick="selectThisOption(this, <?=$row->fields['PK_AI_CHAT_PROMPT']?>)"><?=$row->fields['PROMPT']?></button>
<?php $row->MoveNext(); } ?>

