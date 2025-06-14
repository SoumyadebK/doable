<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;
global $account_database;

$PK_ENROLLMENT_MASTER = $_GET['master_id'] ?: 0;
$RECEIPT_NUMBER = $_GET['receipt'] ?: 0;
$BILLING_REF = '';

//$RECEIPT_NUMBER_ARRAY = explode(',', $RECEIPT_NUMBER);
$business_details = $db->Execute("SELECT * FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER=" . $_SESSION['PK_ACCOUNT_MASTER']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* Optional: You can style your button */
        .print-button {
            background-color: #4CAF50;
            /* Green */
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
        <?php
        if ($PK_ENROLLMENT_MASTER == 0) {
            $wallet_data = $db_account->Execute("SELECT * FROM DOA_CUSTOMER_WALLET WHERE RECEIPT_NUMBER = '$RECEIPT_NUMBER' ORDER BY PK_CUSTOMER_WALLET DESC LIMIT 1");
            $user_data = $db->Execute("SELECT DOA_USERS.FIRST_NAME, DOA_USERS.LAST_NAME, DOA_USERS.ADDRESS, DOA_USERS.CITY, DOA_STATES.STATE_NAME, DOA_USERS.ZIP, DOA_LOCATION.LOCATION_NAME, DOA_LOCATION.ADDRESS, DOA_LOCATION.PHONE, DOA_LOCATION.ZIP_CODE, DOA_CORPORATION.CORPORATION_NAME FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER LEFT JOIN DOA_STATES ON DOA_STATES.PK_STATES=DOA_USERS.PK_STATES LEFT JOIN DOA_USER_LOCATION ON DOA_USER_LOCATION.PK_USER=DOA_USERS.PK_USER LEFT JOIN DOA_LOCATION ON DOA_LOCATION.PK_LOCATION=DOA_USER_LOCATION.PK_LOCATION LEFT JOIN DOA_CORPORATION ON DOA_LOCATION.PK_CORPORATION=DOA_CORPORATION.PK_CORPORATION WHERE DOA_USER_MASTER.PK_USER_MASTER = " . $wallet_data->fields['PK_USER_MASTER']);
        } else {
            $enrollment_billing_data = $db_account->Execute("SELECT DOA_ENROLLMENT_BILLING.BILLING_REF, DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER FROM DOA_ENROLLMENT_BILLING INNER JOIN DOA_ENROLLMENT_PAYMENT ON DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_BILLING = DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_BILLING WHERE DOA_ENROLLMENT_PAYMENT.IS_ORIGINAL_RECEIPT = 1 AND DOA_ENROLLMENT_PAYMENT.RECEIPT_NUMBER = '$RECEIPT_NUMBER' LIMIT 1");
            $BILLING_REF = $enrollment_billing_data->fields['BILLING_REF'];

            $user_data = $db->Execute("SELECT DOA_USERS.FIRST_NAME, DOA_USERS.LAST_NAME, DOA_USERS.ADDRESS, DOA_USERS.CITY, DOA_STATES.STATE_NAME, DOA_USERS.ZIP, DOA_LOCATION.LOCATION_NAME, DOA_LOCATION.ADDRESS, DOA_LOCATION.PHONE, DOA_LOCATION.ZIP_CODE, DOA_CORPORATION.CORPORATION_NAME FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER LEFT JOIN DOA_STATES ON DOA_STATES.PK_STATES=DOA_USERS.PK_STATES LEFT JOIN DOA_USER_LOCATION ON DOA_USER_LOCATION.PK_USER=DOA_USERS.PK_USER LEFT JOIN DOA_LOCATION ON DOA_LOCATION.PK_LOCATION=DOA_USER_LOCATION.PK_LOCATION LEFT JOIN DOA_CORPORATION ON DOA_LOCATION.PK_CORPORATION=DOA_CORPORATION.PK_CORPORATION LEFT JOIN $account_database.DOA_ENROLLMENT_MASTER AS DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = " . $PK_ENROLLMENT_MASTER);
        }

        $enrollment_payment = $db_account->Execute("SELECT DOA_ENROLLMENT_PAYMENT.*, DOA_PAYMENT_TYPE.PAYMENT_TYPE FROM DOA_ENROLLMENT_PAYMENT LEFT JOIN $master_database.DOA_PAYMENT_TYPE AS DOA_PAYMENT_TYPE ON DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE = DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE WHERE DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER = '$PK_ENROLLMENT_MASTER' AND DOA_ENROLLMENT_PAYMENT.IS_ORIGINAL_RECEIPT = 1 AND RECEIPT_NUMBER = '$RECEIPT_NUMBER'");

        $DETAILS_AMOUNT = '';
        $PAYMENT_DETAILS = '';
        $TOTAL_AMOUNT = 0;
        $PAYMENT_METHOD = '';
        $TYPE = '';
        while (!$enrollment_payment->EOF) {
            $TYPE = $enrollment_payment->fields['TYPE'];
            if ($enrollment_payment->fields['TYPE'] == 'Payment' || $enrollment_payment->fields['TYPE'] == 'Wallet') {
                if ($enrollment_payment->fields['PK_PAYMENT_TYPE'] == 2) {
                    $PAYMENT_INFO = json_decode($enrollment_payment->fields['PAYMENT_INFO']);
                    $PAYMENT_DETAILS = "<tr>
                                <td>" . $enrollment_payment->fields['PAYMENT_TYPE'] . "# : </td>
                                <td style='text-align:right'>" . $PAYMENT_INFO->CHECK_NUMBER . "</td>
                            </tr>";
                } elseif (in_array($enrollment_payment->fields['PK_PAYMENT_TYPE'], [1, 8, 9, 10, 11, 13, 14])) {
                    $PAYMENT_INFO = json_decode($enrollment_payment->fields['PAYMENT_INFO']);
                    $PAYMENT_DETAILS = "<tr>
                                <td>" . $enrollment_payment->fields['PAYMENT_TYPE'] . "# : </td>
                                <td style='text-align:right'>**** " . $PAYMENT_INFO->LAST4 . "</td>
                            </tr>";
                }
            }

            $PAYMENT_METHOD = $enrollment_payment->fields['PAYMENT_TYPE'];
            $DETAILS_AMOUNT .= '$' . number_format($enrollment_payment->fields['AMOUNT'], 2) . '<br>';
            $TOTAL_AMOUNT += $enrollment_payment->fields['AMOUNT'];
            $enrollment_payment->MoveNext();
        }

        $BUSINESS_NAME = $business_details->fields['BUSINESS_NAME'];
        $CORPORATION_NAME = $user_data->fields['CORPORATION_NAME'];
        $FULL_NAME = $user_data->fields['FIRST_NAME'] . " " . $user_data->fields['LAST_NAME'];
        $LOCATION_NAME = $user_data->fields['LOCATION_NAME'];
        $ADDRESS = $user_data->fields['ADDRESS'];
        $STATE_NAME = $user_data->fields['STATE_NAME'];
        $ZIP = $user_data->fields['ZIP'];
        $ZIP_CODE = $user_data->fields['ZIP_CODE'];
        $PHONE = $user_data->fields['PHONE'];
        $AMOUNT = '$' . number_format($TOTAL_AMOUNT, 2);
        $TOTAL = '$' . number_format($TOTAL_AMOUNT, 2);
        $PAYMENT_DATE = date('m-d-Y', strtotime($enrollment_payment->fields['PAYMENT_DATE']));
        ?>

        <table style="margin-left: auto; margin-right: auto; width:70%;">
            <tbody>
                <tr>
                    <td style="text-align:center; font-size: 18px"><strong><?= $CORPORATION_NAME ?></strong></td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td style="text-align:center"><?= $LOCATION_NAME ?></td>
                </tr>
                <tr>
                    <td style="text-align:center"><?= $ADDRESS ?></td>
                </tr>
                <tr>
                    <td style="text-align:center"><?= $PHONE ?></td>
                </tr>
                <tr>
                    <td style="text-align:center"><strong><?= $TYPE ?> Transaction</strong></td>
                </tr>
                <?php if ($BILLING_REF) { ?>
                    <tr>
                        <td style="text-align:center"><strong>Billing Ref# <?= $BILLING_REF ?></strong></td>
                    </tr>
                <?php } ?>
                <tr>
                    <td style="text-align:center"><strong>Receipt# <?= $RECEIPT_NUMBER ?></strong></td>
                </tr>
                <tr>
                    <td style="text-align:center"><?= $PAYMENT_DATE ?></td>
                </tr>
            </tbody>
        </table>

        <table style="margin-left: auto; margin-right: auto; width:70%;">
            <tbody>
                <tr>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td><?= $TYPE ?> Method : </td>
                    <td style="text-align:right"><?= $PAYMENT_METHOD ?></td>
                </tr>
                <?= $PAYMENT_DETAILS ?>
                <tr>
                    <td>Details : </td>
                    <td style="text-align:right"><?= $DETAILS_AMOUNT ?></td>
                </tr>
                <tr>
                    <td>Amount(s) : </td>
                    <td style="text-align:right"><?= $AMOUNT ?></td>
                </tr>
                <tr>
                    <td>Total : </td>
                    <td style="text-align:right"><?= $TOTAL ?></td>
                </tr>
            </tbody>
        </table>

        <table style="margin-left: auto; margin-right: auto; width:70%; margin-bottom: 20%;">
            <tbody>
                <tr>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td style="text-align:center"><?= $FULL_NAME ?></td>
                </tr>
                <tr>
                    <td style="text-align:center">*Per authorization on <?= $PAYMENT_DATE ?> Receipt# <?= $RECEIPT_NUMBER ?></td>
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