<?php
require_once('../../global/config.php');
$PK_USER_MASTER = $_POST['PK_USER_MASTER'];
?>

<?php $wallet_data = $db->Execute("SELECT * FROM DOA_CUSTOMER_WALLET WHERE PK_USER_MASTER = '$PK_USER_MASTER' ORDER BY PK_CUSTOMER_WALLET DESC LIMIT 1"); ?>
<span id="wallet_balance_span" style="font-size: 10px;color: green;">Wallet Balance : $<?=($wallet_data->RecordCount() > 0)?$wallet_data->fields['CURRENT_BALANCE']:0.00?></span>
<input type="hidden" id="WALLET_BALANCE" name="WALLET_BALANCE" value="<?=($wallet_data->RecordCount() > 0)?$wallet_data->fields['CURRENT_BALANCE']:0.00?>">
<input type="hidden" name="PK_USER_MASTER" value="<?=$PK_USER_MASTER?>">
