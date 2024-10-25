<?php
require_once('../../global/config.php');
global $db;
global $db_account;
global $master_database;
global $results_per_page;

$PK_USER_MASTER = !empty($_SESSION['PK_USER_MASTER']) ? $_SESSION['PK_USER_MASTER'] : 0;
$PK_USER = !empty($_GET['pk_user']) ? $_GET['pk_user'] : 0;
$type = !empty($_GET['type']) ? $_GET['type'] : 0;
$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];

if ($type == 'completed') {
    $enr_condition = " (DOA_ENROLLMENT_MASTER.STATUS = 'CO' || DOA_ENROLLMENT_MASTER.STATUS = 'C') ";
} else {
    $enr_condition = " (DOA_ENROLLMENT_MASTER.STATUS = 'CA' || DOA_ENROLLMENT_MASTER.STATUS = 'A') ";
}
?>

<?php
if ($_GET['type'] == 'normal') { ?>
    <div class="row" style="padding: 35px 35px 0 35px">
        <div class="col-md-9">
            <h5 style="margin-left: 15%;">List of Pending Services</h5>
            <?php require_once('pending_services.php'); ?>
        </div>

        <div class="col-md-3">
            <?php
            $total_paid = 0;
            $misc_paid = 0;
            $total_used = 0;
            $wallet_data = $db_account->Execute("SELECT * FROM DOA_CUSTOMER_WALLET WHERE PK_USER_MASTER = '$PK_USER_MASTER' ORDER BY PK_CUSTOMER_WALLET DESC LIMIT 1");

            $total_paid_data = $db_account->Execute("SELECT DISTINCT DOA_ENROLLMENT_PAYMENT.*, DOA_SERVICE_MASTER.PK_SERVICE_CLASS FROM DOA_ENROLLMENT_PAYMENT LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER LEFT JOIN DOA_SERVICE_MASTER ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER LEFT JOIN DOA_SERVICE_CODE ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE WHERE (DOA_ENROLLMENT_MASTER.STATUS = 'CA' || DOA_ENROLLMENT_MASTER.STATUS = 'A') AND (DOA_ENROLLMENT_PAYMENT.TYPE = 'Payment' || DOA_ENROLLMENT_PAYMENT.TYPE = 'Adjustment') AND DOA_ENROLLMENT_PAYMENT.IS_REFUNDED = 0 AND DOA_ENROLLMENT_MASTER.PK_USER_MASTER = ".$PK_USER_MASTER);
            while (!$total_paid_data->EOF) {
                if ($total_paid_data->fields['PK_SERVICE_CLASS'] == 5) {
                    $misc_paid += $total_paid_data->fields['AMOUNT'];
                } else {
                    $total_paid += $total_paid_data->fields['AMOUNT'];
                }
                $total_paid_data->MoveNext();
            }

            $total_refund_data = $db_account->Execute("SELECT SUM(AMOUNT) AS TOTAL_REFUND FROM DOA_ENROLLMENT_PAYMENT LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_PAYMENT.TYPE = 'Refund' AND DOA_ENROLLMENT_MASTER.PK_USER_MASTER = ".$PK_USER_MASTER);
            $total_refund = ($total_refund_data->RecordCount() > 0) ? $total_refund_data->fields['TOTAL_REFUND'] : 0.00;
            $enr_service_data = $db_account->Execute("SELECT DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_SERVICE, DOA_ENROLLMENT_SERVICE.PRICE_PER_SESSION FROM DOA_ENROLLMENT_SERVICE LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE (DOA_ENROLLMENT_MASTER.STATUS = 'CA' || DOA_ENROLLMENT_MASTER.STATUS = 'A') AND DOA_ENROLLMENT_MASTER.PK_USER_MASTER = ".$PK_USER_MASTER);
            while (!$enr_service_data->EOF) {
                $SESSION_COMPLETED = getSessionCompletedCount($enr_service_data->fields['PK_ENROLLMENT_SERVICE']);
                $total_used += ($SESSION_COMPLETED*$enr_service_data->fields['PRICE_PER_SESSION']);
                $enr_service_data->MoveNext();
            }
            ?>
            <a class="btn btn-info d-none d-lg-block m-15 text-white right-aside" href="javascript:" onclick="$('#export_model').modal('show');" style="width: 120px; "><i class="fa fa-file-export"></i> Export</a>
            <h5 id="wallet_balance_span">Credit Balance : $<?=number_format((float)$total_paid-(float)$total_used, 2)?></h5>
            <h5 id="wallet_balance_span">Miscellaneous Balance : $<?=number_format($misc_paid, 2)?></h5>
            <h5 id="wallet_balance_span">Wallet Balance : $<?=($wallet_data->RecordCount() > 0)?$wallet_data->fields['CURRENT_BALANCE']:0.00?></h5>
        </div>
    </div>
    <div class="row" style="padding: 35px 35px 0 35px">
        <?php require_once('orphan_appointment.php'); ?>
    </div>
    <div class="row" style="margin-bottom: -15px; margin-top: 10px;">
        <div class="col-12 d-flex justify-content-end">
            <?php
            $all_row = $db_account->Execute("SELECT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, DOA_ENROLLMENT_MASTER.ENROLLMENT_ID, DOA_ENROLLMENT_MASTER.ACTIVE FROM `DOA_ENROLLMENT_MASTER` WHERE DOA_ENROLLMENT_MASTER.PK_USER_MASTER='$_SESSION[PK_USER_MASTER]' ORDER BY DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER DESC");
            ?>
            <!--<input type="checkbox" id="toggleAll" onclick="toggleAllCheckboxes()"/>
            <a class="btn btn-info d-none d-lg-block m-15 text-white right-aside" href="javascript:;" onclick="payAll(<?php /*=$all_row->fields['PK_ENROLLMENT_MASTER']*/?>, '<?php /*=$all_row->fields['ENROLLMENT_ID']*/?>')">Pay All</a>-->
            <a class="btn btn-info d-none d-lg-block m-15 text-white right-aside" href="enrollment.php?id_customer=<?=$_GET['pk_user']?>&master_id_customer=<?=$PK_USER_MASTER?>&source=customer" style="width: 120px; "><i class="fa fa-plus-circle"></i> Enrollment</a>
        </div>
    </div>
<?php } else { ?>
    <div class="row" style="padding: 35px 35px 0 35px">
        <h5 style="margin-left: 30%;">List of Completed Services</h5>
        <?php require_once('completed_services.php'); ?>
    </div>
<?php } ?>

<?php
$row = $db_account->Execute("SELECT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, DOA_ENROLLMENT_MASTER.ENROLLMENT_NAME, DOA_ENROLLMENT_MASTER.ENROLLMENT_ID, DOA_ENROLLMENT_MASTER.AGREEMENT_PDF_LINK, DOA_ENROLLMENT_MASTER.ACTIVE, DOA_ENROLLMENT_MASTER.STATUS, DOA_ENROLLMENT_MASTER.ENROLLMENT_DATE, DOA_LOCATION.LOCATION_NAME FROM `DOA_ENROLLMENT_MASTER` LEFT JOIN $master_database.DOA_LOCATION AS DOA_LOCATION ON DOA_LOCATION.PK_LOCATION = DOA_ENROLLMENT_MASTER.PK_LOCATION WHERE ".$enr_condition." AND DOA_ENROLLMENT_MASTER.PK_USER_MASTER = $PK_USER_MASTER ORDER BY DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER DESC");
$AGREEMENT_PDF_LINK = '';
while (!$row->EOF) {
    $name = $row->fields['ENROLLMENT_NAME'];
    $AGREEMENT_PDF_LINK = $row->fields['AGREEMENT_PDF_LINK'];
    if(empty($name)){
        $enrollment_name = '';
    }else {
        $enrollment_name = "$name"." - ";
    }
    $serviceMasterData = $db_account->Execute("SELECT DOA_SERVICE_MASTER.SERVICE_NAME FROM DOA_SERVICE_MASTER JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER WHERE DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = ".$row->fields['PK_ENROLLMENT_MASTER']);
    $serviceMaster = [];
    while (!$serviceMasterData->EOF) {
        $serviceMaster[] = $serviceMasterData->fields['SERVICE_NAME'];
        $serviceMasterData->MoveNext();
    } ?>
    <div class="border" style="margin: 10px;">
        <div class="row enrollment_div" style="font-size: 15px; *border: 1px solid #ebe5e2; padding: 8px;">
            <div class="col-2" style="text-align: center; margin-top: 1.5%;">
                <p><?=$row->fields['LOCATION_NAME']?></p>
                <a href="enrollment.php?id=<?=$row->fields['PK_ENROLLMENT_MASTER']?>"><?=$enrollment_name.$row->fields['ENROLLMENT_ID']?></a>
                <p><?=implode(' || ', $serviceMaster)?></p>
                <p><?=date('m/d/Y', strtotime($row->fields['ENROLLMENT_DATE']))?></p>
                <?php if ($AGREEMENT_PDF_LINK != '' && $AGREEMENT_PDF_LINK != null) { ?>
                    <a href="../uploads/enrollment_pdf/<?=$AGREEMENT_PDF_LINK?>" target="_blank">View Agreement</a>
                <?php } ?>
                <button class="btn btn-danger m-l-10 text-white" onclick="showEnrollmentDetails(this, <?=$PK_USER?>, <?=$PK_USER_MASTER?>, <?=$row->fields['PK_ENROLLMENT_MASTER']?>, '<?=$row->fields['ENROLLMENT_ID']?>', '<?=$type?>', 'billing_details')" style="background-color: #f44336; margin-top: 20px">View Payment Schedule</button>
            </div>
            <div class="col-8" onclick="showEnrollmentDetails(this, <?=$PK_USER?>, <?=$PK_USER_MASTER?>, <?=$row->fields['PK_ENROLLMENT_MASTER']?>, '<?=$row->fields['ENROLLMENT_ID']?>', '<?=$type?>', 'appointment_details')" style="cursor: pointer;">
                <table id="myTable" class="table <?php
                $details = $db_account->Execute("SELECT PK_ENROLLMENT_LEDGER FROM `DOA_ENROLLMENT_LEDGER` WHERE DOA_ENROLLMENT_LEDGER.IS_PAID = 0 AND PK_ENROLLMENT_MASTER = ".$row->fields['PK_ENROLLMENT_MASTER']);
                $paid_count = ($details->RecordCount() > 0) ? $details->RecordCount() : 0;
                if ($paid_count == 0) { echo 'table-success'; }else{ echo "table-striped"; } ?> border">
                    <thead>
                    <tr>
                        <th></th>
                        <th style="text-align: right;">Enrolled</th>
                        <th style="text-align: right;">Used</th>
                        <th style="text-align: right;">Balance</th>
                        <th style="text-align: right;">Paid</th>
                        <th style="text-align: right;">Service Credit</th>
                    </tr>
                    </thead>

                    <tbody>
                    <?php
                    $serviceCodeData = $db_account->Execute("SELECT DOA_ENROLLMENT_SERVICE.*, DOA_SERVICE_MASTER.PK_SERVICE_CLASS, DOA_SERVICE_CODE.PK_SERVICE_CODE, DOA_SERVICE_CODE.SERVICE_CODE FROM DOA_ENROLLMENT_SERVICE JOIN DOA_SERVICE_MASTER ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER JOIN DOA_SERVICE_CODE ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE WHERE DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = ".$row->fields['PK_ENROLLMENT_MASTER']);
                    $total_amount = 0;
                    $total_paid_amount = 0;
                    $total_used_amount = 0;
                    $enrollment_service_array = [];
                    while (!$serviceCodeData->EOF) {
                        if (($type == 'completed') && ($serviceCodeData->fields['PK_SERVICE_CLASS'] == 5)) {
                            $SESSION_COMPLETED = $serviceCodeData->fields['NUMBER_OF_SESSION'];
                        } else {
                            $SESSION_COMPLETED = getSessionCompletedCount($serviceCodeData->fields['PK_ENROLLMENT_SERVICE']);
                        }

                        $enrollment_service_array[] = $serviceCodeData->fields['PK_ENROLLMENT_SERVICE'];
                        $PRICE_PER_SESSION = ($serviceCodeData->fields['PRICE_PER_SESSION'] <= 0) ? 0 : $serviceCodeData->fields['PRICE_PER_SESSION'];

                        if (($type == 'completed') && ($serviceCodeData->fields['PK_SERVICE_CLASS'] == 5)) {
                            $TOTAL_PAID_SESSION = $SESSION_COMPLETED;
                            $TOTAL_AMOUNT_PAID = $serviceCodeData->fields['FINAL_AMOUNT'];
                        } else {
                            $TOTAL_PAID_SESSION = ($serviceCodeData->fields['PRICE_PER_SESSION'] <= 0) ? $serviceCodeData->fields['NUMBER_OF_SESSION'] : number_format($serviceCodeData->fields['TOTAL_AMOUNT_PAID'] / $serviceCodeData->fields['PRICE_PER_SESSION'], 2);
                            $TOTAL_AMOUNT_PAID = $serviceCodeData->fields['TOTAL_AMOUNT_PAID'];
                        }

                        $ENR_BALANCE = $TOTAL_PAID_SESSION - $SESSION_COMPLETED;

                        $total_amount += $serviceCodeData->fields['FINAL_AMOUNT'];
                        $total_paid_amount += $TOTAL_AMOUNT_PAID; //$serviceCodeData->fields['TOTAL_AMOUNT_PAID'];
                        $total_used_amount +=  ($PRICE_PER_SESSION * $SESSION_COMPLETED); ?>
                        <tr>
                            <td><?=$serviceCodeData->fields['SERVICE_CODE']?></td>
                            <td style="text-align: right"><?=$serviceCodeData->fields['NUMBER_OF_SESSION']?></td>
                            <td style="text-align: right;"><?=$SESSION_COMPLETED?></td>
                            <td style="text-align: right; color:<?=($ENR_BALANCE < 0)?'red':'black'?>;"><?=number_format($ENR_BALANCE, 2)?></td>
                            <td style="text-align: right">$<?=number_format($serviceCodeData->fields['TOTAL_AMOUNT_PAID'], 2)?></td>
                            <td style="text-align: right;"><?=($ENR_BALANCE > 0) ? number_format($ENR_BALANCE, 2) : 0?></td>
                        </tr>
                        <?php $serviceCodeData->MoveNext();
                    } ?>
                    <tr>
                        <td>Amount</td>
                        <td style="text-align: right;"><?=number_format($total_amount, 2)?></td>
                        <td style="text-align: right;"><?=number_format($total_amount-$total_used_amount<0.00 ? $total_amount : $total_used_amount, 2)?></td>
                        <td style="text-align: right; color:<?=($total_paid_amount-$total_used_amount<-0.03)?'red':'black'?>;"><?=number_format((($total_paid_amount-$total_used_amount<0.03) ? 0 : $total_paid_amount-$total_used_amount), 2)?></td>
                        <td style="text-align: right;">$<?=number_format($total_paid_amount, 2)?></td>
                        <td style="text-align: right;"><?=($total_paid_amount-$total_used_amount > 0) ? number_format($total_paid_amount-$total_used_amount, 2) : 0?></td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <div class="col-2" style="font-weight: bold; text-align: center; margin-top: 1.5%;">
                <?php if ($paid_count == 0) { ?>
                    <i class="fa fa-check-circle" style="font-size:21px;color:#35e235;"></i>
                <?php } elseif ($row->fields['STATUS'] == 'C') { ?>
                    <i class="fa fa-check-circle" style="font-size:21px;color:#ff0000;"></i>
                <?php } ?>
                <?php
                if($row->fields['STATUS'] === 'C' || $row->fields['STATUS'] === 'CA') { ?>
                    <p style="color: red; margin-top: 25%;">Cancelled</p>
                <?php } ?>
                <?php
                if($row->fields['STATUS'] === 'CA') {
                    if ($total_paid_amount-$total_used_amount > 0) { ?>
                        <p style="color: green; margin-top: 20%;">Refund Credit Available</p>
                    <?php } elseif ($total_paid_amount-$total_used_amount < 0) { ?>
                        <p style="color: red; margin-top: 20%;">Balance Owed</p>
                    <?php } ?>
                <?php } ?>
            </div>
        </div>

        <div id="enrollment_details" style="display: none;">

        </div>
    </div>
    <?php
    $row->MoveNext();
} ?>

<script>
    function showEnrollmentDetails(param, PK_USER, PK_USER_MASTER, PK_ENROLLMENT_MASTER, ENROLLMENT_ID, type, details) {
        $.ajax({
            url: "pagination/get_enrollment_details.php",
            type: "GET",
            data: {PK_USER:PK_USER, PK_USER_MASTER:PK_USER_MASTER, PK_ENROLLMENT_MASTER:PK_ENROLLMENT_MASTER, ENROLLMENT_ID:ENROLLMENT_ID, type:type},
            async: false,
            cache: false,
            success: function (result) {
                $(param).closest('.enrollment_div').next('#enrollment_details').html(result).slideToggle();
            }
        });
    }
    function toggleAllCheckboxes() {
        let toggleCheckbox = document.getElementById('toggleAll');
        let childCheckboxes = document.getElementsByClassName('BILLED_AMOUNT');

        // If the toggle checkbox is checked, uncheck all child checkboxes
        if (toggleCheckbox.checked) {
            for (let i = 0; i < childCheckboxes.length; i++) {
                childCheckboxes[i].checked = true;
            }
        } else {
            for (let i = 0; i < childCheckboxes.length; i++) {
                childCheckboxes[i].checked = false;
            }
        }
    }

    function toggleEnrollmentCheckboxes(PK_ENROLLMENT_MASTER) {
        let toggleCheckbox = document.getElementById('toggleEnrollment_'+PK_ENROLLMENT_MASTER);
        let childCheckboxes = document.getElementsByClassName('PAYMENT_CHECKBOX_'+PK_ENROLLMENT_MASTER);
        let payNow = document.getElementById('payNow');

        // If the toggle checkbox is checked, uncheck all child checkboxes
        if (toggleCheckbox.checked) {
            for (let i = 0; i < childCheckboxes.length; i++) {
                childCheckboxes[i].checked = true;
                payNow.disabled = true;
            }
        } else {
            for (let i = 0; i < childCheckboxes.length; i++) {
                childCheckboxes[i].checked = false;
                payNow.disabled = false;
            }
        }
    }

    $(document).on('change', '.pay_now_check', function (){
        if ($('.pay_now_check').is(':checked')) {
            $('.pay_selected_btn').prop('disabled', false);
            $('.pay_now_button').prop('disabled', true);
        } else {
            $('.pay_selected_btn').prop('disabled', true);
            $('.pay_now_button').prop('disabled', false);
        }
    });

    function moveToWallet(param, PK_ENROLLMENT_PAYMENT, PK_ENROLLMENT_MASTER, PK_ENROLLMENT_LEDGER, PK_USER_MASTER, BALANCE, ENROLLMENT_TYPE, TRANSACTION_TYPE, PAYMENT_COUNTER) {
        let PK_PAYMENT_TYPE = $('#PK_PAYMENT_TYPE_REFUND').val();
        let confirm_move = $('#confirm_move').val();
        if (TRANSACTION_TYPE == 'Refund' && PK_PAYMENT_TYPE == 0) {
            $('.trigger_this').removeClass('trigger_this');
            $(param).addClass('trigger_this');
            $('#REFUND_AMOUNT').val(BALANCE);
            $('#refund_modal').modal('show');
        } else {
            if (TRANSACTION_TYPE == 'Move' && confirm_move == 0) {
                $('.trigger_this').removeClass('trigger_this');
                $(param).addClass('trigger_this');
                $('#move_amount').text(parseFloat(BALANCE).toFixed(2));
                $('#move_to_wallet_model').modal('show');
            } else {
                let REFUND_AMOUNT = $('#REFUND_AMOUNT').val();
                if (REFUND_AMOUNT > BALANCE) {
                    alert("Refund amount can't be grater then balance");
                    $('#REFUND_AMOUNT').val(BALANCE);
                } else {
                    $.ajax({
                        url: "ajax/AjaxFunctions.php",
                        type: 'POST',
                        data: {
                            FUNCTION_NAME: 'moveToWallet',
                            PK_ENROLLMENT_PAYMENT : PK_ENROLLMENT_PAYMENT,
                            PK_ENROLLMENT_MASTER: PK_ENROLLMENT_MASTER,
                            PK_ENROLLMENT_LEDGER: PK_ENROLLMENT_LEDGER,
                            PK_USER_MASTER: PK_USER_MASTER,
                            BALANCE: BALANCE,
                            REFUND_AMOUNT: REFUND_AMOUNT,
                            ENROLLMENT_TYPE: ENROLLMENT_TYPE,
                            TRANSACTION_TYPE: TRANSACTION_TYPE,
                            PK_PAYMENT_TYPE: PK_PAYMENT_TYPE
                        },
                        success: function (data) {
                            if (data == 1) {
                                window.location.reload();
                            } else {
                                alert(data);
                            }
                        }
                    });
                }
            }
        }
    }

    function openReceipt(PK_ENROLLMENT_MASTER, RECEIPT_NUMBER) {
        let RECEIPT_NUMBER_ARRAY = RECEIPT_NUMBER.split(',');
        for (let i=0; i<RECEIPT_NUMBER_ARRAY.length; i++) {
            window.open('generate_receipt_pdf.php?master_id=' + PK_ENROLLMENT_MASTER + '&receipt=' + RECEIPT_NUMBER_ARRAY[i], '_blank');
        }
    }

    function editThisAppointment(PK_APPOINTMENT_MASTER, PK_USER, PK_USER_MASTER) {
        $.ajax({
            url: "includes/edit_appointment_details.php",
            type: 'GET',
            data: {
                PK_APPOINTMENT_MASTER: PK_APPOINTMENT_MASTER,
                PK_USER : PK_USER,
                PK_USER_MASTER : PK_USER_MASTER
            },
            success: function (data) {
                $('#edit_appointment_modal').html(data).modal('show');
            }
        });
    }

    function editBillingDueDate(PK_ENROLLMENT_LEDGER, DUE_DATE, TYPE) {
        $('#PK_ENROLLMENT_LEDGER').val(PK_ENROLLMENT_LEDGER);
        $('#old_due_date').val(DUE_DATE);
        $('#due_date').val(DUE_DATE);
        $('#edit_type').val(TYPE);
        $('#billing_due_date_model').modal('show');
    }

    function getEditHistory(param, PK_ENROLLMENT_LEDGER, type) {
        $.ajax({
            url: "includes/get_update_history.php",
            type: 'GET',
            data: {
                PK_ENROLLMENT_LEDGER: PK_ENROLLMENT_LEDGER,
                CLASS : type,
                FIELD_NAME : 'DUE_DATE'
            },
            success: function (data) {
                $(param).popover({
                    title: 'Due Date Update Details',
                    placement: 'top',
                    trigger: 'hover',
                    content: data,
                    container: 'body',
                    html: true,
                }).popover('show');
            }
        });
    }
</script>
