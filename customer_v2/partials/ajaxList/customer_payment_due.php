<?php
require_once('../../../global/config.php');
global $db;
global $db_account;

$PK_USER_MASTER = !empty($_GET['master_id']) ? $_GET['master_id'] : 0;
?>
<table id="paymentDueTable" class="table">
    <thead>
        <tr>
            <th style="text-align: left;" width="25%">Enrollment</th>
            <th style="text-align: center;" width="20%">Due</th>
            <th style="text-align: center;" width="20%">Amount</th>
            <th style="text-align: center;" width="35%">Action</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $payment_due = $db_account->Execute("SELECT DOA_ENROLLMENT_LEDGER.*, DOA_ENROLLMENT_MASTER.ENROLLMENT_ID, DOA_ENROLLMENT_MASTER.MISC_ID, DOA_ENROLLMENT_MASTER.ENROLLMENT_NAME FROM DOA_ENROLLMENT_LEDGER INNER JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_LEDGER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.STATUS IN ('A') AND DOA_ENROLLMENT_LEDGER.TRANSACTION_TYPE = 'Billing' AND DOA_ENROLLMENT_LEDGER.IS_PAID = 0 AND DOA_ENROLLMENT_MASTER.PK_USER_MASTER = $PK_USER_MASTER ORDER BY DOA_ENROLLMENT_LEDGER.DUE_DATE");
        if ($payment_due->RecordCount() > 0) {
            while (!$payment_due->EOF) {
                $name = $payment_due->fields['ENROLLMENT_NAME'];
                $ENROLLMENT_ID = $payment_due->fields['ENROLLMENT_ID'];
                if (empty($name)) {
                    $enrollment_name = '';
                } else {
                    $enrollment_name = "$name" . " - ";
                }
                if ($payment_due->fields['AMOUNT_REMAIN'] > 0) {
                    $BILLED_AMOUNT = $payment_due->fields['AMOUNT_REMAIN'];
                } else {
                    $BILLED_AMOUNT = $payment_due->fields['BILLED_AMOUNT'];
                }
                $due_date = strtotime($payment_due->fields['DUE_DATE']);
                $is_past_due = $due_date && $due_date < strtotime(date('Y-m-d'));
        ?>
                <tr>
                    <td style="color: <?= $is_past_due ? 'red' : 'black' ?>; text-align: left;"><?= ($enrollment_name . $ENROLLMENT_ID == null) ? $enrollment_name . $payment_due->fields['MISC_ID'] : $enrollment_name . $ENROLLMENT_ID ?></td>
                    <td style="color: <?= $is_past_due ? 'red' : 'black' ?>; text-align: center;"><?= date('m/d/Y', strtotime($payment_due->fields['DUE_DATE'])) ?></td>
                    <td style="color: <?= $is_past_due ? 'red' : 'black' ?>; text-align: center;">$<?= number_format($BILLED_AMOUNT, 2) ?></td>
                    <td style="color: <?= $is_past_due ? 'red' : 'black' ?>; text-align: center;">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="payNow(<?= $payment_due->fields['PK_ENROLLMENT_MASTER'] ?>, <?= $payment_due->fields['PK_ENROLLMENT_LEDGER'] ?>, <?= $BILLED_AMOUNT ?>, '<?= $ENROLLMENT_ID ?>');" style="margin-bottom: 5px;">Pay Now</button>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="editDueDate(<?= $PK_USER_MASTER ?>, <?= $payment_due->fields['PK_ENROLLMENT_LEDGER'] ?>, '<?= date('m/d/Y', strtotime($payment_due->fields['DUE_DATE'])) ?>', 'billing')">Edit Due Date</button>
                    </td>
                </tr>
        <?php $payment_due->MoveNext();
            }
        } ?>
    </tbody>
</table>