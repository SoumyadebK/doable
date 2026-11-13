<?php
require_once('../../global/config.php');
global $db;
global $db_account;

$PK_USER_MASTER = !empty($_GET['master_id']) ? $_GET['master_id'] : 0;
?>

<table id="paymentDueTable" class="table table-striped border" data-page-length="50">
    <thead>
        <tr>
            <th style="text-align: center;">Enrollment</th>
            <th style="text-align: center;">Date</th>
            <th style="text-align: center;">Amount</th>
            <th style="text-align: center;">Action</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $payment_due = $db_account->Execute("SELECT DOA_ENROLLMENT_LEDGER.*, DOA_ENROLLMENT_MASTER.ENROLLMENT_ID, DOA_ENROLLMENT_MASTER.ENROLLMENT_NAME FROM DOA_ENROLLMENT_LEDGER INNER JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_LEDGER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_LEDGER.TRANSACTION_TYPE = 'Billing' AND DOA_ENROLLMENT_LEDGER.IS_PAID = 0 AND DOA_ENROLLMENT_MASTER.PK_USER_MASTER = $PK_USER_MASTER ORDER BY DOA_ENROLLMENT_LEDGER.DUE_DATE DESC");
        if ($payment_due->RecordCount() > 0) {
            while (!$payment_due->EOF) {
                $name = $payment_due->fields['ENROLLMENT_NAME'];
                $ENROLLMENT_ID = $payment_due->fields['ENROLLMENT_ID'];
                if (empty($name)) {
                    $enrollment_name = '';
                } else {
                    $enrollment_name = "$name" . " - ";
                } ?>
                <tr style="border-style: hidden;">
                    <td style="text-align: center;"><?= ($enrollment_name . $ENROLLMENT_ID == null) ? $enrollment_name . $payment_due->fields['MISC_ID'] : $enrollment_name . $ENROLLMENT_ID ?></td>
                    <td style="text-align: center;"><?= date('m/d/Y', strtotime($payment_due->fields['DUE_DATE'])) ?></td>
                    <td style="text-align: center;">$<?= number_format($payment_due->fields['BILLED_AMOUNT'], 2) ?></td>
                    <td style="text-align: center;">
                        <button id="payNow" class="pay_now_button btn btn-info waves-effect waves-light m-l-10 text-white" onclick="payNow(<?= $payment_due->fields['PK_ENROLLMENT_MASTER'] ?>, <?= $payment_due->fields['PK_ENROLLMENT_LEDGER'] ?>, <?= $payment_due->fields['BILLED_AMOUNT'] ?>, '<?= $ENROLLMENT_ID ?>');">Pay Now</button>
                        <button class="btn btn-info waves-effect waves-light m-l-10 text-white" onclick="editDueDate(<?= $payment_due->fields['PK_ENROLLMENT_LEDGER'] ?>, '<?= date('m/d/Y', strtotime($payment_due->fields['DUE_DATE'])) ?>', 'billing')">Edit Due Date</button>
                    </td>
                </tr>
        <?php $payment_due->MoveNext();
            }
        } ?>

    </tbody>
</table>

<script>
    function editDueDate(PK_ENROLLMENT_LEDGER, DUE_DATE, TYPE) {
        $('#PK_ENROLLMENT_LEDGER').val(PK_ENROLLMENT_LEDGER);
        $('#old_due_date').val(DUE_DATE);
        $('#due_date').val(DUE_DATE);
        $('#edit_type').val(TYPE);
        $('#billing_due_date_model').modal('show');
    }
</script>