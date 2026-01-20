<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;

/* $PK_LOCATION = getPkLocation();
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
} */
if (!empty($_POST)) {
    $location_1 = $_POST['location_1'];
    $location_2 = $_POST['location_2'];

    $service_code_data = $db_account->Execute("SELECT * FROM `DOA_SERVICE_CODE` WHERE `PK_LOCATION` = '$location_1'");
    while (!$service_code_data->EOF) {
        $PK_SERVICE_CODE = $service_code_data->fields['PK_SERVICE_CODE'];
        $PK_SERVICE_MASTER = $service_code_data->fields['PK_SERVICE_MASTER'];

        $service_code = $service_code_data->fields['SERVICE_CODE'];

        $existing_data = $db_account->Execute("SELECT * FROM `DOA_SERVICE_CODE` WHERE `PK_LOCATION` = '$location_2' AND `SERVICE_CODE` LIKE '$service_code'");
        $NEW_PK_SERVICE_CODE = $existing_data->fields['PK_SERVICE_CODE'] ?? 0;
        $NEW_PK_SERVICE_MASTER = $existing_data->fields['PK_SERVICE_MASTER'] ?? 0;

        $db_account->Execute("UPDATE DOA_APPOINTMENT_MASTER SET PK_SERVICE_CODE = $NEW_PK_SERVICE_CODE, PK_SERVICE_MASTER = $NEW_PK_SERVICE_MASTER WHERE (PK_SERVICE_MASTER = $PK_SERVICE_MASTER OR PK_SERVICE_CODE = $PK_SERVICE_CODE) AND PK_LOCATION = '$location_2'");
        $db_account->Execute("UPDATE DOA_ENROLLMENT_SERVICE S
                                JOIN DOA_ENROLLMENT_MASTER M 
                                    ON S.PK_ENROLLMENT_MASTER = M.PK_ENROLLMENT_MASTER
                                SET 
                                    S.PK_SERVICE_MASTER = $NEW_PK_SERVICE_MASTER,
                                    S.PK_SERVICE_CODE   = $NEW_PK_SERVICE_CODE
                                WHERE 
                                    M.PK_LOCATION = $location_2 AND (S.PK_SERVICE_MASTER = $PK_SERVICE_MASTER OR S.PK_SERVICE_CODE = $PK_SERVICE_CODE)");

        $service_code_data->MoveNext();
    }

    $scheduling_code_data = $db_account->Execute("SELECT * FROM `DOA_SCHEDULING_CODE` WHERE `PK_LOCATION` = '$location_1'");
    while (!$scheduling_code_data->EOF) {
        $PK_SCHEDULING_CODE = $scheduling_code_data->fields['PK_SCHEDULING_CODE'];
        $SCHEDULING_CODE = $scheduling_code_data->fields['SCHEDULING_CODE'];

        $existing_data = $db_account->Execute("SELECT * FROM `DOA_SCHEDULING_CODE` WHERE `PK_LOCATION` = '$location_2' AND `SCHEDULING_CODE` LIKE '$SCHEDULING_CODE'");
        $NEW_PK_SCHEDULING_CODE = $existing_data->fields['PK_SCHEDULING_CODE'] ?? 0;

        $db_account->Execute("UPDATE DOA_APPOINTMENT_MASTER SET PK_SCHEDULING_CODE = $NEW_PK_SCHEDULING_CODE WHERE PK_SCHEDULING_CODE = $PK_SCHEDULING_CODE AND PK_LOCATION = '$location_2'");
        $db_account->Execute("UPDATE DOA_SPECIAL_APPOINTMENT SET PK_SCHEDULING_CODE = $NEW_PK_SCHEDULING_CODE WHERE PK_SCHEDULING_CODE = $PK_SCHEDULING_CODE AND PK_LOCATION = '$location_2'");
        $scheduling_code_data->MoveNext();
    }
}
?>


<h3>Enter Location Id's</h3>
<form method="POST" action="">
    <input type="text" name="location_1" placeholder="From which location data come" required>
    <br><br>
    <input type="text" name="location_2" placeholder="Which location have to adjust" required>
    <br><br>
    <input type="submit" value="Submit">
</form>