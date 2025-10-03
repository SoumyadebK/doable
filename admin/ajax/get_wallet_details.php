<?php
require_once('../../global/config.php');
require_once("../../global/stripe-php-master/init.php");
global $db;
global $db_account;
global $master_database;

$PK_USER_MASTER = $_POST['PK_USER_MASTER'];
?>

<style>
    .nested-table {
        margin: 8px 0 12px 32px;
        /* indent child table */
        background: #f8f9fa;
        width: 95%;
        font-size: 13px;
    }

    .accordion-toggle {
        cursor: pointer;
    }

    .icon {
        transition: transform 0.3s ease, margin-left 0.3s ease, color 0.3s ease;
        margin-right: 6px;
        color: #6c757d;
    }

    .icon.rotate {
        transform: rotate(90deg);
        margin-left: 4px;
        color: #39b54a;
    }
</style>


<div class="table-responsive">
    <table class="table table-bordered align-middle">
        <thead class="table-light">
            <tr>
                <th style="width: 5%"></th>
                <th>Date</th>
                <th>Receipt Number</th>
                <th>Transaction Details</th>
                <th>Credit</th>
                <th>Balance</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php
            $walletTransaction = $db_account->Execute("SELECT * FROM DOA_CUSTOMER_WALLET WHERE CUSTOMER_WALLET_PARENT = 0 AND PK_USER_MASTER = '$PK_USER_MASTER' ORDER BY PK_CUSTOMER_WALLET ASC");
            $i = 1;
            while (!$walletTransaction->EOF) {
                $RECEIPT_NUMBER = $walletTransaction->fields['RECEIPT_NUMBER'];
                $paymentData = $db_account->Execute("SELECT * FROM `DOA_ENROLLMENT_PAYMENT` WHERE PK_CUSTOMER_WALLET = " . $walletTransaction->fields['PK_CUSTOMER_WALLET']);

                $payment_details = '';
                if ($paymentData->RecordCount() > 0) {
                    if ($paymentData->fields['PK_PAYMENT_TYPE'] == '2') {
                        $payment_info = json_decode($paymentData->fields['PAYMENT_INFO']);
                        $payment_details = $paymentData->fields['PAYMENT_TYPE'] . " : " . ((isset($payment_info->CHECK_NUMBER)) ? $payment_info->CHECK_NUMBER : '');
                    } elseif (in_array($paymentData->fields['PK_PAYMENT_TYPE'], [1, 8, 9, 10, 11, 13, 14])) {
                        $payment_info = json_decode($paymentData->fields['PAYMENT_INFO']);
                        $payment_details = $paymentData->fields['PAYMENT_TYPE'] . " # " . ((isset($payment_info->LAST4)) ? $payment_info->LAST4 : '');
                    }
                }
            ?>
                <tr class="accordion-toggle" data-bs-toggle="collapse" data-bs-target="#row<?= $i ?>-details">
                    <td class="text-center">
                        <i class="fa fa-chevron-right icon"></i>
                    </td>
                    <td><?= date('m/d/Y h:i A', strtotime($walletTransaction->fields['CREATED_ON'])) ?></td>
                    <td><?= $walletTransaction->fields['RECEIPT_NUMBER'] ?></td>
                    <td><?= $walletTransaction->fields['DESCRIPTION'] . ' ' . $payment_details ?></td>
                    <td><?= $walletTransaction->fields['CREDIT'] ?></td>
                    <td><?= $walletTransaction->fields['BALANCE_LEFT'] ?></td>
                    <td>
                        <?php if ($RECEIPT_NUMBER != '') { ?>
                            <a class="btn btn-info waves-effect waves-light text-white btn-receipt" href="generate_receipt_pdf.php?master_id=<?= $paymentData->fields['PK_ENROLLMENT_MASTER'] ?>&ledger_id=<?= $paymentData->fields['PK_ENROLLMENT_LEDGER'] ?>&receipt=<?= $walletTransaction->fields['RECEIPT_NUMBER'] ?>" target="_blank">Receipt</a>
                        <?php }
                        if (($walletTransaction->fields['CREDIT'] == $walletTransaction->fields['BALANCE_LEFT']) && $walletTransaction->fields['PK_PAYMENT_TYPE'] == 12 && $walletTransaction->fields['IS_DELETED'] == 0) { ?>
                            <a href="javascript:;" class="btn btn-danger waves-effect waves-light text-white" onclick="deleteWalletPayment(<?= $walletTransaction->fields['PK_CUSTOMER_WALLET'] ?>)"><i class="ti-trash"></i></a>
                        <?php } ?>
                    </td>
                </tr>

                <tr>
                    <td colspan="7" class="p-0">
                        <div class="collapse" id="row<?= $i ?>-details">
                            <table class="table table-sm nested-table table-bordered">
                                <thead class="table-secondary">
                                    <tr>
                                        <th>Date</th>
                                        <th>Transaction Details</th>
                                        <th>Debit</th>
                                        <th>Credit</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $walletTransactionDetails = $db_account->Execute("SELECT * FROM DOA_CUSTOMER_WALLET WHERE CUSTOMER_WALLET_PARENT = " . $walletTransaction->fields['PK_CUSTOMER_WALLET'] . " ORDER BY PK_CUSTOMER_WALLET ASC");
                                    if ($walletTransactionDetails->RecordCount() == 0) { ?>
                                        <tr>
                                            <td colspan="4" class="text-center">No transaction details available.</td>
                                        </tr>
                                        <?php } else {
                                        while (!$walletTransactionDetails->EOF) { ?>
                                            <tr>
                                                <td><?= date('m/d/Y h:i A', strtotime($walletTransactionDetails->fields['CREATED_ON'])) ?></td>
                                                <td><?= $walletTransactionDetails->fields['DESCRIPTION'] ?></td>
                                                <td><?= $walletTransactionDetails->fields['DEBIT'] ?></td>
                                                <td><?= $walletTransactionDetails->fields['CREDIT'] ?></td>
                                            </tr>
                                    <?php $walletTransactionDetails->MoveNext();
                                        }
                                    } ?>
                                </tbody>
                            </table>
                        </div>
                    </td>
                </tr>
            <?php $walletTransaction->MoveNext();
                $i++;
            } ?>

        </tbody>
    </table>
</div>

<script>
    // Prevent collapse toggle when clicking inside "Receipt" button
    document.querySelectorAll('.btn-receipt').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation(); // stop row toggle
            let href = $(this).attr('href'); // get the href value
            console.log("Receipt link:", href);
            window.open(href, '_blank');
        });
    });

    // Sync icon rotation with collapse events
    document.querySelectorAll('.collapse').forEach(collapseEl => {
        collapseEl.addEventListener('show.bs.collapse', function() {
            const row = collapseEl.closest('tr').previousElementSibling;
            const icon = row.querySelector('.icon');
            icon.classList.add('rotate');
        });
        collapseEl.addEventListener('hide.bs.collapse', function() {
            const row = collapseEl.closest('tr').previousElementSibling;
            const icon = row.querySelector('.icon');
            icon.classList.remove('rotate');
        });
    });
</script>