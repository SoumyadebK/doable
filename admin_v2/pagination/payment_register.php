<?php
require_once('../../global/config.php');
global $db;
global $db_account;

$PK_USER_MASTER = !empty($_GET['master_id']) ? $_GET['master_id'] : 0;
?>

<table id="paymentRegisterTable" class="table table-striped border" data-page-length="50">
    <thead>
        <tr>
            <th style="text-align: center;">Date</th>
            <th style="text-align: center;">Paid</th>
            <th style="text-align: center;">Receipt</th>
            <th style="text-align: center;">Method</th>
            <th style="text-align: center;">Enrollment</th>
            <th style="text-align: center;" width="20%">Memo</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $payment_details = $db_account->Execute("SELECT DOA_ENROLLMENT_PAYMENT.*, DOA_ENROLLMENT_MASTER.ENROLLMENT_ID, DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE, DOA_PAYMENT_TYPE.PAYMENT_TYPE FROM DOA_ENROLLMENT_PAYMENT INNER JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN $master_database.DOA_PAYMENT_TYPE AS DOA_PAYMENT_TYPE ON DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE = DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE WHERE (DOA_ENROLLMENT_PAYMENT.TYPE = 'Payment' OR DOA_ENROLLMENT_PAYMENT.TYPE = 'Refund') AND IS_ORIGINAL_RECEIPT = 1 AND DOA_ENROLLMENT_MASTER.PK_USER_MASTER = $PK_USER_MASTER ORDER BY DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE DESC");
        if ($payment_details->RecordCount() > 0) {
            while (!$payment_details->EOF) {
                if ($payment_details->fields['TYPE'] == 'Move') {
                    $payment_type = 'Wallet';
                } elseif ($payment_details->fields['PK_PAYMENT_TYPE'] == '2') {
                    $payment_info = json_decode($payment_details->fields['PAYMENT_INFO']);
                    $payment_type = $payment_details->fields['PAYMENT_TYPE'] . " : " . ((isset($payment_info->CHECK_NUMBER)) ? $payment_info->CHECK_NUMBER : '');
                } elseif (in_array($payment_details->fields['PK_PAYMENT_TYPE'], [1, 8, 9, 10, 11, 13, 14])) {
                    $payment_info = json_decode($payment_details->fields['PAYMENT_INFO']);
                    $payment_type = $payment_details->fields['PAYMENT_TYPE'] . " # " . ((isset($payment_info->LAST4)) ? $payment_info->LAST4 : '');
                } elseif ($payment_details->fields['PK_PAYMENT_TYPE'] == '7') {
                    $receipt_number_array = explode(',', $payment_details->fields['RECEIPT_NUMBER']);
                    $payment_type_array = [];
                    foreach ($receipt_number_array as $receipt_number) {
                        $receipt_payment_details = $db_account->Execute("SELECT DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE, DOA_ENROLLMENT_PAYMENT.PAYMENT_INFO, DOA_PAYMENT_TYPE.PAYMENT_TYPE FROM DOA_ENROLLMENT_PAYMENT LEFT JOIN $master_database.DOA_PAYMENT_TYPE AS DOA_PAYMENT_TYPE ON DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE = DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE WHERE IS_ORIGINAL_RECEIPT = 1 AND PK_ENROLLMENT_MASTER = 0 AND DOA_ENROLLMENT_PAYMENT.RECEIPT_NUMBER = '$receipt_number'");
                        if ($receipt_payment_details->RecordCount() > 0) {
                            if ($receipt_payment_details->fields['PK_PAYMENT_TYPE'] == '2') {
                                $payment_info = json_decode($receipt_payment_details->fields['PAYMENT_INFO']);
                                $payment_type_array[] = $receipt_payment_details->fields['PAYMENT_TYPE'] . " : " . ((isset($payment_info->CHECK_NUMBER)) ? $payment_info->CHECK_NUMBER : '');
                            } elseif (in_array($receipt_payment_details->fields['PK_PAYMENT_TYPE'], [1, 8, 9, 10, 11, 13, 14])) {
                                $payment_info = json_decode($receipt_payment_details->fields['PAYMENT_INFO']);
                                $payment_type_array[] = $receipt_payment_details->fields['PAYMENT_TYPE'] . " # " . ((isset($payment_info->LAST4)) ? $payment_info->LAST4 : '');
                            } else {
                                $payment_type_array[] = $receipt_payment_details->fields['PAYMENT_TYPE'];
                            }
                        }
                    }
                    $payment_type = implode(', ', $payment_type_array);
                } else {
                    $payment_type = $payment_details->fields['PAYMENT_TYPE'];
                } ?>
                <tr style="border-style: hidden; color: <?= ($payment_details->fields['TYPE'] == 'Refund') ? 'red' : 'black' ?>;">
                    <td style="text-align: center;"><?= date('m/d/Y', strtotime($payment_details->fields['PAYMENT_DATE'])) ?></td>
                    <td style="text-align: center;">$<?= number_format($payment_details->fields['AMOUNT'], 2) ?></td>
                    <td style="text-align: center;">
                        <a onclick="openReceipt(<?= $payment_details->fields['PK_ENROLLMENT_MASTER'] ?>, '<?= $payment_details->fields['RECEIPT_NUMBER'] ?>')" href="javascript:"><?= $payment_details->fields['RECEIPT_NUMBER'] ?></a>
                    </td>
                    <td style="text-align: center;"><?= $payment_type ?></td>
                    <td style="text-align: center;">#<a href="enrollment.php?id=<?= $payment_details->fields['PK_ENROLLMENT_MASTER'] ?>" target="_blank"><?= $payment_details->fields['ENROLLMENT_ID'] ?></a></td>
                    <td style="text-align: left;"><?= $payment_details->fields['NOTE'] ?></td>
                </tr>
        <?php $payment_details->MoveNext();
            }
        } ?>

    </tbody>
</table>

<script>

</script>