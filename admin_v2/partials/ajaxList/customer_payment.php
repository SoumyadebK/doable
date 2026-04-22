<?php
require_once('../../../global/config.php');
global $db;
global $db_account;

$PK_USER_MASTER = !empty($_GET['master_id']) ? $_GET['master_id'] : 0;
?>


<div class="payments-card">
    <div class="d-flex justify-content-between align-items-start">
        <div>
            <h5 class="fw-bold mb-1">Payments</h5>
            <p class="text-muted small mb-0">Optional settings section description</p>
        </div>
        <button class="btn btn-light btn-sm btn-outline-edit border text-muted px-3 py-2" style="border-radius: 8px;">
            <i class="bi bi-plus"></i> New Payment
        </button>
    </div>

    <div class="summary-row d-flex align-items-center">
        <?php
        $total_payments = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_PAYMENT.AMOUNT) AS TOTAL_AMOUNT_PAID FROM DOA_ENROLLMENT_PAYMENT INNER JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_PAYMENT.TYPE = 'Payment' AND DOA_ENROLLMENT_PAYMENT.IS_REFUNDED = 0 AND DOA_ENROLLMENT_PAYMENT.PAYMENT_STATUS = 'Success' AND DOA_ENROLLMENT_PAYMENT.IS_ORIGINAL_RECEIPT = 1 AND DOA_ENROLLMENT_MASTER.PK_USER_MASTER = $PK_USER_MASTER");
        ?>
        <div class="flex-grow-1">
            <div class="stat-label">Total Payments</div>
            <div class="stat-value">$<?= number_format($total_payments->fields['TOTAL_AMOUNT_PAID'], 2) ?></div>
        </div>

        <div class="stat-divider"></div>
        <?php
        $payment_due = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_LEDGER.BILLED_AMOUNT) AS TOTAL_PAYMENT_DUE FROM DOA_ENROLLMENT_LEDGER INNER JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_LEDGER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.STATUS IN ('A') AND DOA_ENROLLMENT_LEDGER.TRANSACTION_TYPE = 'Billing' AND DOA_ENROLLMENT_LEDGER.IS_PAID = 0 AND DOA_ENROLLMENT_MASTER.PK_USER_MASTER = $PK_USER_MASTER");
        ?>
        <div class="flex-grow-1">
            <div class="stat-label">Pending Payments</div>
            <div class="stat-value">$<?= number_format($payment_due->fields['TOTAL_PAYMENT_DUE'], 2) ?></div>
        </div>

        <div class="stat-divider"></div>
        <?php $wallet_data = $db_account->Execute("SELECT * FROM DOA_CUSTOMER_WALLET WHERE PK_USER_MASTER = '$PK_USER_MASTER' ORDER BY PK_CUSTOMER_WALLET DESC LIMIT 1"); ?>
        <div class="flex-grow-1">
            <div class="stat-label">Wallet Balance</div>
            <div class="stat-value">$<?= number_format((float)($wallet_data->RecordCount() > 0 ? $wallet_data->fields['CURRENT_BALANCE'] : 0.00), 2) ?></div>
        </div>
    </div>

    <div class="table-responsive  border-0">
        <table id="paymentRegisterTable" class="table mb-0 border-0">
            <thead>
                <tr>
                    <th style="cursor: pointer;">Receipt</th>
                    <th style="cursor: pointer;">Date</th>
                    <th style="cursor: pointer;">Enrollment</th>
                    <th style="cursor: pointer;">Method</th>
                    <th style="cursor: pointer;">Memo</th>
                    <th style="cursor: pointer;">Paid</th>
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
                        <tr style="color: <?= ($payment_details->fields['TYPE'] == 'Refund') ? 'red' : 'black' ?>;">
                            <td><a onclick="openReceipt(<?= $payment_details->fields['PK_ENROLLMENT_MASTER'] ?>, '<?= $payment_details->fields['RECEIPT_NUMBER'] ?>')" href="javascript:" style="color: #39b54a;"><?= $payment_details->fields['RECEIPT_NUMBER'] ?></a></td>
                            <td><?= date('m/d/Y', strtotime($payment_details->fields['PAYMENT_DATE'])) ?></td>
                            <td><a href="../admin/enrollment.php?id=<?= $payment_details->fields['PK_ENROLLMENT_MASTER'] ?>" target="_blank" style="color: #39b54a;">#<?= $payment_details->fields['ENROLLMENT_ID'] ?></a></td>
                            <td><?= $payment_type ?></td>
                            <td><?= $payment_details->fields['NOTE'] ?></td>
                            <td>$<?= number_format($payment_details->fields['AMOUNT'], 2) ?></td>
                        </tr>
                <?php $payment_details->MoveNext();
                    }
                } ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    function openReceipt(PK_ENROLLMENT_MASTER, RECEIPT_NUMBER) {
        let RECEIPT_NUMBER_ARRAY = RECEIPT_NUMBER.split(',');
        for (let i = 0; i < RECEIPT_NUMBER_ARRAY.length; i++) {
            window.open('generate_receipt_pdf.php?master_id=' + PK_ENROLLMENT_MASTER + '&receipt=' + RECEIPT_NUMBER_ARRAY[i], '_blank');
        }
    }
</script>