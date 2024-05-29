<?php
require_once('../global/config.php');
$PK_ENROLLMENT_MASTER = $_GET['PK_ENROLLMENT_MASTER'] ? $_GET['PK_ENROLLMENT_MASTER'] : 0;
$PK_ENROLLMENT_LEDGER = $_GET['PK_ENROLLMENT_LEDGER'] ? $_GET['PK_ENROLLMENT_LEDGER'] : 0;
$receipt = $db_account->Execute("SELECT RECEIPT_NUMBER FROM DOA_ENROLLMENT_PAYMENT ORDER BY PK_ENROLLMENT_PAYMENT DESC LIMIT 1");
if ($receipt->RecordCount() > 0) {
    $lastSerialNumber = $receipt->fields['RECEIPT_NUMBER'];
    $RECEIPT_NUMBER = $lastSerialNumber + 1;
} else {
    $RECEIPT_NUMBER = 1;
}

$business_details = $db->Execute("SELECT * FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER=".$_SESSION['PK_ACCOUNT_MASTER']);
$user_data = $db->Execute("SELECT DOA_USERS.FIRST_NAME, DOA_USERS.LAST_NAME, DOA_USERS.PHONE, DOA_USERS.ADDRESS, DOA_USERS.CITY, DOA_STATES.STATE_NAME, DOA_USERS.ZIP, DOA_LOCATION.LOCATION_NAME FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER LEFT JOIN DOA_STATES ON DOA_STATES.PK_STATES=DOA_USERS.PK_STATES LEFT JOIN DOA_USER_LOCATION ON DOA_USER_LOCATION.PK_USER=DOA_USERS.PK_USER LEFT JOIN DOA_LOCATION ON DOA_LOCATION.PK_LOCATION=DOA_USER_LOCATION.PK_LOCATION LEFT JOIN $account_database.DOA_ENROLLMENT_MASTER AS DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER=DOA_USER_MASTER.PK_USER_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = ".$PK_ENROLLMENT_MASTER);
$enrollment_billing_data = $db_account->Execute("SELECT * FROM DOA_ENROLLMENT_BILLING WHERE PK_ENROLLMENT_MASTER=".$PK_ENROLLMENT_MASTER);
$enrollment_ledger_data = $db_account->Execute("SELECT DOA_ENROLLMENT_LEDGER.*, DOA_ENROLLMENT_PAYMENT.* FROM DOA_ENROLLMENT_LEDGER LEFT JOIN DOA_ENROLLMENT_PAYMENT ON DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_LEDGER=DOA_ENROLLMENT_LEDGER.PK_ENROLLMENT_LEDGER WHERE DOA_ENROLLMENT_LEDGER.PK_ENROLLMENT_LEDGER=".$PK_ENROLLMENT_LEDGER);
$BALANCE = $enrollment_ledger_data->fields['AMOUNT'];
$enrollment_payment_type = $db->Execute("SELECT PAYMENT_TYPE FROM DOA_PAYMENT_TYPE WHERE PK_PAYMENT_TYPE=".$enrollment_ledger_data->fields['PK_PAYMENT_TYPE']);

$BUSINESS_NAME = $business_details->fields['BUSINESS_NAME'];
$FULL_NAME = $user_data->fields['FIRST_NAME'] . " " . $user_data->fields['LAST_NAME'];
$LOCATION_NAME = $user_data->fields['LOCATION_NAME'];
$STATE_NAME = $user_data->fields['STATE_NAME'];
$ZIP = $user_data->fields['ZIP'];
$PHONE = $user_data->fields['PHONE'];
$BILLING_REF = $enrollment_billing_data->fields['BILLING_REF'];
$RECEIPT_NUMBER = $enrollment_ledger_data->fields['RECEIPT_NUMBER'];
$PAYMENT_METHOD = $enrollment_payment_type->fields['PAYMENT_TYPE'];
$DETAILS = '$' . number_format($BALANCE, 2);
$AMOUNT = '$' . number_format($BALANCE, 2);
$TOTAL = '$' . number_format($BALANCE, 2);
$PAYMENT_DATE = $enrollment_ledger_data->fields['PAYMENT_DATE'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
    /* Optional: You can style your button */
    .print-button {
        background-color: #4CAF50; /* Green */
        border: none;
        color: white;
        padding: 10px 20px;
        text-align: center;
        text-decoration: none;
        display: inline-block;
        font-size: 12px;
        margin: 4px 2px;
        cursor: pointer;
        float: right;
    }
</style>
</head>
<body>
<div>
    <button class="print-button" onclick="printPage()">Print</button>
</div>
<div id="printContent">

<table style="margin-left: auto; margin-right: auto; width:70%;">
    <tbody>
    <tr>
        <td style="text-align:center; font-size: 18px"><strong><?=$BUSINESS_NAME?></strong></td>
    </tr>
    <tr>
        <td>&nbsp;</td>
    </tr>
    <tr>
        <td style="text-align:center"><?=$FULL_NAME?> <?=$LOCATION_NAME?></td>
    </tr>
    <tr>
        <td style="text-align:center"><?=$LOCATION_NAME?> <?=$STATE_NAME?> <?=$ZIP?></td>
    </tr>
    <tr>
        <td style="text-align:center"><?=$PHONE?></td>
    </tr>
    <tr>
        <td style="text-align:center"><strong>Refund Transaction</strong></td>
    </tr>
    <tr>
        <td style="text-align:center"><strong>Billing Ref# <?=$BILLING_REF?></strong></td>
    </tr>
    <tr>
        <td style="text-align:center"><strong>Receipt# <?=$RECEIPT_NUMBER?></strong></td>
    </tr>
    <tr>
        <td style="text-align:center"><?=$PAYMENT_DATE?></td>
    </tr>
    </tbody>
</table>

<table style="margin-left: auto; margin-right: auto; width:70%;">
    <tbody>
    <tr>
        <td>&nbsp;</td>
    </tr>
    <tr>
        <td>Refund Method : </td>
        <td style="text-align:right"><?=$PAYMENT_METHOD?></td>
    </tr>
    <tr>
        <td>Details : </td>
        <td style="text-align:right"><?=$DETAILS?></td>
    </tr>
    <tr>
        <td>Amount(s) : </td>
        <td style="text-align:right"><?=$AMOUNT?></td>
    </tr>
    <tr>
        <td>Total : </td>
        <td style="text-align:right"><?=$TOTAL?></td>
    </tr>
    </tbody>
</table>

<table style="margin-left: auto; margin-right: auto; width:70%;">
    <tbody>
    <tr>
        <td>&nbsp;</td>
    </tr>
    <tr>
        <td style="text-align:center"><?=$FULL_NAME?></td>
    </tr>
    <tr>
        <td style="text-align:center">*Per authorization on <?=$PAYMENT_DATE?> Billing Ref# <?=$BILLING_REF?></td>
    </tr>
    </tbody>
</table>
</div>
</body>
</html>

<script>
    function printPage() {
        window.print();
    }
</script>