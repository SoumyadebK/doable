<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;

$PK_LOCATION = getPkLocation();
$customer_wallet_data = $db_account->Execute("SELECT DOA_CUSTOMER_WALLET.* FROM `DOA_CUSTOMER_WALLET` LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_CUSTOMER_WALLET.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER WHERE DOA_USER_MASTER.PRIMARY_LOCATION_ID = $PK_LOCATION AND `PK_PAYMENT_TYPE` IS NOT NULL ORDER BY `DOA_CUSTOMER_WALLET`.`CREATED_ON` ASC");
while (!$customer_wallet_data->EOF) {
    $PK_CUSTOMER_WALLET = $customer_wallet_data->fields['PK_CUSTOMER_WALLET'];
    $PK_PAYMENT_TYPE = $customer_wallet_data->fields['PK_PAYMENT_TYPE'];
    $AMOUNT = $customer_wallet_data->fields['CREDIT'];
    $NOTE = $customer_wallet_data->fields['NOTE'];
    $CREATED_ON = ($customer_wallet_data->fields['CREATED_ON'] != '') ? date('Y-m-d', strtotime($customer_wallet_data->fields['CREATED_ON'])) : date('Y-m-d');

    $payment_data = $db_account->Execute("SELECT * FROM `DOA_ENROLLMENT_PAYMENT` WHERE `TYPE` LIKE 'Wallet' AND PK_CUSTOMER_WALLET = 0 AND PK_PAYMENT_TYPE = $PK_PAYMENT_TYPE AND AMOUNT = $AMOUNT AND NOTE LIKE '%$NOTE%' AND PAYMENT_DATE = '$CREATED_ON' LIMIT 1");
    if ($payment_data->RecordCount() > 0) {
        $PK_ENROLLMENT_PAYMENT = $payment_data->fields['PK_ENROLLMENT_PAYMENT'] ?? 0;
        db_perform_account('DOA_ENROLLMENT_PAYMENT', ['PK_CUSTOMER_WALLET' => $PK_CUSTOMER_WALLET, 'PK_LOCATION' => $PK_LOCATION], 'update', "PK_ENROLLMENT_PAYMENT = $PK_ENROLLMENT_PAYMENT");

        $RECEIPT_NUMBER = generateReceiptNumber(0);
        db_perform_account('DOA_ENROLLMENT_PAYMENT', ['RECEIPT_NUMBER' => $RECEIPT_NUMBER], 'update', "PK_ENROLLMENT_PAYMENT = $PK_ENROLLMENT_PAYMENT");
        db_perform_account('DOA_CUSTOMER_WALLET', ['RECEIPT_NUMBER' => $RECEIPT_NUMBER], 'update', "PK_CUSTOMER_WALLET = $PK_CUSTOMER_WALLET");

        echo "Updated RECEIPT_NUMBER: $RECEIPT_NUMBER<br>";
    }

    $customer_wallet_data->MoveNext();
}
