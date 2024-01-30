<?php
require_once('../../global/config.php');
$PK_USER_MASTER='';
$results_per_page = 100;

/*if (isset($_GET['search_text']) && $_GET['search_text'] != '') {
    $search_text = $_GET['search_text'];
    $search = " AND DOA_USERS.FIRST_NAME LIKE '%".$search_text."%' OR DOA_USERS.EMAIL_ID LIKE '%".$search_text."%' OR DOA_USERS.PHONE LIKE '%".$search_text."%'";
} else {
    $search_text = '';
    $search = ' ';
}*/

/*if (!isset ($_GET['page']) ) {
    $page = 1;
} else {
    $page = $_GET['page'];
}
$page_first_result = ($page-1) * $results_per_page;*/
?>

<?php
$wallet_data = $db_account->Execute("SELECT * FROM DOA_CUSTOMER_WALLET WHERE PK_USER_MASTER = '$PK_USER_MASTER' ORDER BY PK_CUSTOMER_WALLET DESC LIMIT 1");
//$i=$page_first_result+1;
$row = $db_account->Execute("SELECT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, DOA_ENROLLMENT_MASTER.ENROLLMENT_NAME, DOA_ENROLLMENT_MASTER.ENROLLMENT_ID, DOA_ENROLLMENT_MASTER.AGREEMENT_PDF_LINK, DOA_ENROLLMENT_MASTER.ACTIVE, DOA_ENROLLMENT_MASTER.STATUS, DOA_ENROLLMENT_MASTER.CREATED_ON FROM `DOA_ENROLLMENT_MASTER` WHERE DOA_ENROLLMENT_MASTER.PK_USER_MASTER='$_GET[master_id]' ORDER BY DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER DESC");
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
    }

    $used_session = $db_account->Execute("SELECT COUNT(`PK_ENROLLMENT_MASTER`) AS USED_SESSION_COUNT, PK_SERVICE_MASTER FROM `DOA_APPOINTMENT_MASTER` WHERE PK_APPOINTMENT_STATUS = 2 AND `PK_ENROLLMENT_MASTER` = ".$row->fields['PK_ENROLLMENT_MASTER']);

    //$PK_SERVICE_MASTER = ($used_session->RecordCount() > 0) ? $used_session->fields['PK_SERVICE_MASTER'] : 0;
    /*$total_session = $db_account->Execute("SELECT SUM(`NUMBER_OF_SESSION`) AS TOTAL_SESSION_COUNT FROM `DOA_ENROLLMENT_SERVICE` WHERE  `PK_ENROLLMENT_MASTER` = ".$row->fields['PK_ENROLLMENT_MASTER']." AND `PK_SERVICE_MASTER` = ".$PK_SERVICE_MASTER);
    if ($total_session->RecordCount() <= 0 || $total_session->fields['TOTAL_SESSION_COUNT'] == '') {
        $total_session = $db_account->Execute("SELECT SUM(`NUMBER_OF_SESSION`) AS TOTAL_SESSION_COUNT FROM `DOA_ENROLLMENT_SERVICE` WHERE  `PK_ENROLLMENT_MASTER` = ".$row->fields['PK_ENROLLMENT_MASTER']);
    }
    $total_session_count = ($total_session->RecordCount() > 0) ? $total_session->fields['TOTAL_SESSION_COUNT'] : 0;*/

    //$total_bill_and_paid = $db_account->Execute("SELECT SUM(BILLED_AMOUNT) AS TOTAL_BILL, SUM(PAID_AMOUNT) AS TOTAL_PAID, SUM(BALANCE) AS BALANCE FROM DOA_ENROLLMENT_LEDGER WHERE `PK_ENROLLMENT_MASTER`=".$row->fields['PK_ENROLLMENT_MASTER']);
    //$enrollment_balance = $db->Execute("SELECT * FROM `DOA_ENROLLMENT_BALANCE` WHERE `PK_ENROLLMENT_MASTER`=".$row->fields['PK_ENROLLMENT_MASTER']);
    $billing_data = $db_account->Execute("SELECT SUM(TOTAL_AMOUNT) AS TOTAL_AMOUNT FROM `DOA_ENROLLMENT_BILLING` WHERE `PK_ENROLLMENT_MASTER`=".$row->fields['PK_ENROLLMENT_MASTER']);
    $total_amount = ($billing_data->RecordCount() > 0) ? $billing_data->fields['TOTAL_AMOUNT'] : 0;
    //$price_per_session = ($total_session_count > 0) ? $total_amount->fields['TOTAL_AMOUNT']/$total_session_count : 0.00;
    //$total_paid = $total_bill_and_paid->fields['TOTAL_PAID'];
    //$balance = $total_bill_and_paid->fields['TOTAL_BILL'] - $total_bill_and_paid->fields['TOTAL_PAID'];
    //$total_used = $used_session_count->fields['USED_SESSION_COUNT']*$price_per_session;
    //$service_credit = $total_bill_and_paid->fields['TOTAL_PAID']-$total_used;
    ?>
    <div class="border" style="margin: 10px;">
        <div class="row" onclick="$(this).next().slideToggle();" style="cursor:pointer; font-size: 15px; *border: 1px solid #ebe5e2; padding: 8px;">
            <div class="col-2" style="text-align: center; margin-top: 1.5%;">
                <a href="enrollment.php?id=<?=$row->fields['PK_ENROLLMENT_MASTER']?>"><?=$enrollment_name.$row->fields['ENROLLMENT_ID']?></a>
                <p><?=implode(' || ', $serviceMaster)?></p>
                <p><?=date('m/d/Y', strtotime($row->fields['CREATED_ON']))?></p>
                <?php if ($AGREEMENT_PDF_LINK != '' && $AGREEMENT_PDF_LINK != null) { ?>
                    <a href="../uploads/enrollment_pdf/<?=$AGREEMENT_PDF_LINK?>" target="_blank">View Agreement</a>
                <?php } ?>
            </div>
            <div class="col-8">
                <?php
                /*$per_session_cost = $total_amount->fields['TOTAL_AMOUNT']/(($total_session_count==0)?1:$total_session_count);
                $total_paid_session_count = ceil($total_bill_and_paid->fields['TOTAL_PAID']/(($per_session_cost==0)?1:$per_session_cost));
                $serviceCodeData = $db_account->Execute("SELECT DOA_SERVICE_CODE.PK_SERVICE_CODE, DOA_SERVICE_CODE.SERVICE_CODE, DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION FROM DOA_SERVICE_CODE JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE WHERE DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = ".$row->fields['PK_ENROLLMENT_MASTER']);
                if ($serviceCodeData->RecordCount()>0) {
                    $number_of_sessions = $serviceCodeData->fields['NUMBER_OF_SESSION'];
                }else{
                    $number_of_sessions=0;
                }
                if ($total_paid_session_count > $number_of_sessions) {
                    $paid_session_count = $number_of_sessions;
                    $total_paid_session_count -= $number_of_sessions;
                } else {
                    $paid_session_count = $total_paid_session_count;
                    $total_paid_session_count = 0;
                }*/
                ?>
                <table id="myTable" class="table <?php
                $details = $db_account->Execute("SELECT count(DOA_ENROLLMENT_LEDGER.IS_PAID) AS PAID FROM `DOA_ENROLLMENT_LEDGER` WHERE DOA_ENROLLMENT_LEDGER.IS_PAID = 0 AND PK_ENROLLMENT_MASTER = ".$row->fields['PK_ENROLLMENT_MASTER']);
                $paid_count = $details->RecordCount() > 0 ? $details->fields['PAID'] : 0;
                $status = $db_account->Execute("SELECT STATUS FROM `DOA_ENROLLMENT_MASTER` WHERE PK_ENROLLMENT_MASTER = ".$row->fields['PK_ENROLLMENT_MASTER']);
                if ($paid_count==0) { echo 'table-success' ;}else{echo "table-striped";}?> border">
                    <thead>
                        <tr>
                            <th></th>
                            <th style="text-align: right;">Enrolled</th>
                            <th style="text-align: right;">Paid</th>
                            <th style="text-align: right;">Used</th>
                            <th style="text-align: right;">Balance</th>
                            <th style="text-align: right;">Service Credit</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php
                    //$per_session_cost = $total_amount->fields['TOTAL_AMOUNT']/(($total_session_count==0)?1:$total_session_count);
                    //$total_paid_session_count = ceil($total_bill_and_paid->fields['TOTAL_PAID']/(($per_session_cost==0)?1:$per_session_cost));
                    $serviceCodeData = $db_account->Execute("SELECT DOA_SERVICE_CODE.PK_SERVICE_CODE, DOA_SERVICE_CODE.SERVICE_CODE, DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION, DOA_ENROLLMENT_SERVICE.TOTAL_AMOUNT_PAID, DOA_ENROLLMENT_SERVICE.PRICE_PER_SESSION, DOA_ENROLLMENT_SERVICE.FINAL_AMOUNT FROM DOA_SERVICE_CODE JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE WHERE DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = ".$row->fields['PK_ENROLLMENT_MASTER']);
                    $total_paid_amount = 0;
                    $total_used_amount = 0;
                    $total_session_count = 0;
                    while (!$serviceCodeData->EOF) {
                        $PRICE_PER_SESSION = $serviceCodeData->fields['PRICE_PER_SESSION'];
                        $used_session_count = $db_account->Execute("SELECT COUNT(`PK_ENROLLMENT_MASTER`) AS USED_SESSION_COUNT FROM `DOA_APPOINTMENT_MASTER` WHERE PK_APPOINTMENT_STATUS = 2 AND `PK_ENROLLMENT_MASTER` = ".$row->fields['PK_ENROLLMENT_MASTER']." AND PK_SERVICE_CODE = ".$serviceCodeData->fields['PK_SERVICE_CODE']); ?>
                        <tr>
                            <td><?=$serviceCodeData->fields['SERVICE_CODE']?></td>
                            <td style="text-align: right"><?=$serviceCodeData->fields['NUMBER_OF_SESSION']?></td>
                            <td style="text-align: right">
                                <?php
                                echo $paid_session = ($PRICE_PER_SESSION > 0) ? number_format($serviceCodeData->fields['TOTAL_AMOUNT_PAID']/$PRICE_PER_SESSION, 2, '.', '') : 0;
                                $total_paid_amount += $serviceCodeData->fields['TOTAL_AMOUNT_PAID'];
                                $total_used_amount += ($PRICE_PER_SESSION*$used_session_count->fields['USED_SESSION_COUNT']);
                                $total_session_count += $serviceCodeData->fields['NUMBER_OF_SESSION'];
                                /*if ($total_paid_session_count > $serviceCodeData->fields['NUMBER_OF_SESSION']) {
                                    echo $paid_session_count = $serviceCodeData->fields['NUMBER_OF_SESSION'];
                                    $total_paid_session_count -= $serviceCodeData->fields['NUMBER_OF_SESSION'];
                                } else {
                                    echo $paid_session_count = $total_paid_session_count;
                                    $total_paid_session_count = 0;
                                }*/
                                ?>
                            </td>
                            <td style="text-align: right;"><?=$used_session_count->fields['USED_SESSION_COUNT']?></td>
                            <td style="text-align: right;"><?=$serviceCodeData->fields['NUMBER_OF_SESSION'] - $paid_session?></td>
                            <!--<td><?php /*=($total_bill_and_paid->fields['TOTAL_BILL']==0) ? 0 : $serviceCodeData->fields['NUMBER_OF_SESSION']-$used_session_count->fields['USED_SESSION_COUNT']*/?></td>-->
                            <td style="text-align: right;"><?=$paid_session - $used_session_count->fields['USED_SESSION_COUNT']?></td>
                        </tr>
                    <?php $serviceCodeData->MoveNext();
                    } ?>
                    <tr>
                        <td>Amount</td>
                        <td style="text-align: right;"><?=$total_amount?></td>
                        <td style="text-align: right;"><?=$total_paid_amount?></td>
                        <td style="text-align: right;"><?=$total_used_amount?></td>
                        <td style="text-align: right;"><?=$total_amount-$total_paid_amount?></td>
                        <td style="text-align: right;"><?=$total_paid_amount-$total_used_amount ?></td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <div class="col-2" style="font-weight: bold; text-align: center; margin-top: 1.5%;">
            <?php if ($paid_count == 0) { ?>
                    <i class="fa fa-check-circle" style="font-size:21px;color:#35e235;"></i>
            <?php } elseif ($status->fields['STATUS']=='C') { ?>
                    <i class="fa fa-check-circle" style="font-size:21px;color:#ff0000;"></i>
            <?php } else { ?>
                    <button class="btn btn-info m-l-10 text-white">Payments Schedule</button>
            <?php }
                if($row->fields['STATUS'] === 'C') { ?>
                    <p style="color: red; margin-top: 25%;">Cancelled</p>
            <?php } ?>
            </div>
        </div>

        <table id="myTable" class="table table-striped border" style="display: none">
            <thead>
                <tr>
                    <th>Due Date</th>
                    <th>Transaction Type</th>
                    <th style="text-align: center;">Billed Amount</th>
                    <th style="text-align: center;">Paid Amount</th>
                    <th style="text-align: center;">Payment Type</th>
                    <th style="text-align: center;">Balance</th>
                    <th>
                        <?php if ($paid_count > 0) { ?>
                            <input type="checkbox" class="pay_now_check" id="toggleEnrollment_<?=$row->fields['PK_ENROLLMENT_MASTER']?>" onclick="toggleEnrollmentCheckboxes(<?=$row->fields['PK_ENROLLMENT_MASTER']?>)"/><button type="button" class="btn btn-info m-l-10 text-white pay_selected_btn" onclick="paySelected(<?=$row->fields['PK_ENROLLMENT_MASTER']?>, '<?=$row->fields['ENROLLMENT_ID']?>')" disabled> Pay Selected</button>
                        <?php } ?>
                    </th>
                </tr>
            </thead>

            <tbody>
            <?php
            $billed_amount = 0;
            $paid_amount = 0;
            $balance = 0;
            $billing_details = $db_account->Execute("SELECT DOA_ENROLLMENT_LEDGER.*, DOA_PAYMENT_TYPE.PAYMENT_TYPE FROM `DOA_ENROLLMENT_LEDGER` LEFT JOIN $master_database.DOA_PAYMENT_TYPE AS DOA_PAYMENT_TYPE ON DOA_ENROLLMENT_LEDGER.PK_PAYMENT_TYPE = DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE WHERE PK_ENROLLMENT_MASTER = ".$row->fields['PK_ENROLLMENT_MASTER']." AND ENROLLMENT_LEDGER_PARENT = 0 ORDER BY DUE_DATE ASC, PK_ENROLLMENT_LEDGER ASC");
            while (!$billing_details->EOF) {
                $billed_amount = $billing_details->fields['BILLED_AMOUNT'];
                $balance = ($billing_details->fields['BILLED_AMOUNT'] + $balance);
                ?>
                <tr>
                    <td><?=date('m/d/Y', strtotime($billing_details->fields['DUE_DATE']))?></td>
                    <td><?=$billing_details->fields['TRANSACTION_TYPE']?></td>
                    <td style="text-align: right;"><?=$billing_details->fields['BILLED_AMOUNT']?></td>
                    <td></td>
                    <td style="text-align: center;"><?=$billing_details->fields['PAYMENT_TYPE']?></td>
                    <td style="text-align: right;"><?=number_format((float)$balance, 2, '.', '')?></td>
                    <td>
                        <?php if($billing_details->fields['IS_PAID']==0 && $billing_details->fields['STATUS']=='A') { ?>
                            <label><input type="checkbox" name="BILLED_AMOUNT[]" class="pay_now_check BILLED_AMOUNT PAYMENT_CHECKBOX_<?=$row->fields['PK_ENROLLMENT_MASTER']?>" data-pk_enrollment_ledger="<?=$billing_details->fields['PK_ENROLLMENT_LEDGER']?>" value="<?=$billing_details->fields['BILLED_AMOUNT']?>"></label>
                            <button id="payNow" class="pay_now_button btn btn-info waves-effect waves-light m-l-10 text-white" onclick="payNow(<?=$row->fields['PK_ENROLLMENT_MASTER']?>, <?=$billing_details->fields['PK_ENROLLMENT_LEDGER']?>, <?=$billing_details->fields['BILLED_AMOUNT']?>, '<?=$row->fields['ENROLLMENT_ID']?>');">Pay Now</button>
                        <?php } ?>
                    </td>
                    <td>
                    </td>
                </tr>
                <?php
                $RECEIPT_PDF_LINK = '';
                $payment_details = $db_account->Execute("SELECT DOA_ENROLLMENT_LEDGER.*, DOA_PAYMENT_TYPE.PAYMENT_TYPE FROM `DOA_ENROLLMENT_LEDGER` LEFT JOIN $master_database.DOA_PAYMENT_TYPE AS DOA_PAYMENT_TYPE ON DOA_ENROLLMENT_LEDGER.PK_PAYMENT_TYPE = DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE WHERE DOA_ENROLLMENT_LEDGER.ENROLLMENT_LEDGER_PARENT = ".$billing_details->fields['PK_ENROLLMENT_LEDGER']);
                if ($payment_details->RecordCount() > 0){
                    $RECEIPT_PDF_LINK = $payment_details->fields['RECEIPT_PDF_LINK'];
                    $balance = ($billed_amount - $payment_details->fields['PAID_AMOUNT']);
                    if($payment_details->fields['PK_PAYMENT_TYPE']=='2') {
                        $payment_type = $payment_details->fields['PAYMENT_TYPE']." : ".$payment_details->fields['CHECK_NUMBER'];
                    }else{
                        $payment_type = $payment_details->fields['PAYMENT_TYPE'];
                    } ?>
                    <tr>
                        <td><?=date('m/d/Y', strtotime($payment_details->fields['DUE_DATE']))?></td>
                        <td><?=$payment_details->fields['TRANSACTION_TYPE']?></td>
                        <td></td>
                        <td style="text-align: right;"><?=$payment_details->fields['PAID_AMOUNT']?></td>
                        <td style="text-align: center;"><?=$payment_type?></td>
                        <td style="text-align: right;"><?=number_format((float)$balance, 2, '.', '')?></td>
                        <td>
                        </td>
                        <td>
                            <?php if ($RECEIPT_PDF_LINK != '' && $RECEIPT_PDF_LINK != null) { ?>
                                <a href="../uploads/enrollment_pdf/<?=$RECEIPT_PDF_LINK?>" target="_blank">Receipt</a>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
                <?php $billing_details->MoveNext(); } ?>
            </tbody>

            <thead>
                <tr>
                    <th style="text-align: left;">Service</th>
                    <th style="text-align: left;" onclick="$(this).next().slideToggle();">Apt #</th>
                    <th style="text-align: left;" onclick="$(this).next().slideToggle();">Service Code</th>
                    <th style="text-align: center;">Date</th>
                    <th style="text-align: center;">Time</th>
                    <th style="text-align: left;">Status</th>
                    <th style="text-align: right;">Session Cost</th>
                    <th style="text-align: right;">Service Credit</th>
                </tr>
            </thead>

            <tbody>
            <?php
            $appointment_data = $db_account->Execute("SELECT DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER, DOA_APPOINTMENT_MASTER.EDITED_ON, DOA_APPOINTMENT_MASTER.COMMENT, DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS, DOA_APPOINTMENT_MASTER.OLD_PK_APPOINTMENT_STATUS, DOA_APPOINTMENT_MASTER.SERIAL_NUMBER, DOA_APPOINTMENT_MASTER.DATE, DOA_APPOINTMENT_MASTER.START_TIME, DOA_APPOINTMENT_MASTER.END_TIME, DOA_APPOINTMENT_MASTER.IS_PAID, DOA_APPOINTMENT_MASTER.CANCELLED_ON, DOA_APPOINTMENT_MASTER.CANCELLED_BY, DOA_APPOINTMENT_MASTER.CHANGED_ON, DOA_APPOINTMENT_MASTER.CHANGED_BY, DOA_APPOINTMENT_MASTER.CREATED_ON, DOA_SERVICE_MASTER.SERVICE_NAME, DOA_SERVICE_CODE.PK_SERVICE_CODE, DOA_SERVICE_CODE.SERVICE_CODE, DOA_SERVICE_CODE.PRICE AS SESSION_COST, DOA_APPOINTMENT_MASTER.ACTIVE, DOA_APPOINTMENT_STATUS.APPOINTMENT_STATUS, DOA_APPOINTMENT_STATUS.COLOR_CODE, CONCAT($master_database.DOA_USERS.FIRST_NAME, ' ', $master_database.DOA_USERS.LAST_NAME) AS NAME FROM DOA_APPOINTMENT_MASTER LEFT JOIN DOA_SERVICE_MASTER ON DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER LEFT JOIN DOA_SERVICE_CODE ON DOA_APPOINTMENT_MASTER.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE LEFT JOIN $master_database.DOA_APPOINTMENT_STATUS AS DOA_APPOINTMENT_STATUS ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS = DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS LEFT JOIN $master_database.DOA_USERS ON $master_database.DOA_USERS.PK_USER=DOA_APPOINTMENT_MASTER.EDITED_BY WHERE DOA_APPOINTMENT_MASTER.STATUS = 'A' AND DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_MASTER = ".$row->fields['PK_ENROLLMENT_MASTER']."");
            $j=1;
            $service_code_array = [];
            while (!$appointment_data->EOF) {
                if (isset($service_code_array[$appointment_data->fields['SERVICE_CODE']])) {
                    $service_code_array[$appointment_data->fields['SERVICE_CODE']] = $service_code_array[$appointment_data->fields['SERVICE_CODE']] + 1;
                } else {
                    $service_code_array[$appointment_data->fields['SERVICE_CODE']] = 1;
                }
                $per_session_price = $db_account->Execute("SELECT `PRICE_PER_SESSION`, NUMBER_OF_SESSION FROM `DOA_ENROLLMENT_SERVICE` WHERE `PK_ENROLLMENT_MASTER` = ".$row->fields['PK_ENROLLMENT_MASTER']." AND `PK_SERVICE_CODE` = ".$appointment_data->fields['PK_SERVICE_CODE']);
                $status_data = $db_account->Execute("SELECT DOA_APPOINTMENT_STATUS.APPOINTMENT_STATUS, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_APPOINTMENT_STATUS_HISTORY.TIME_STAMP FROM DOA_APPOINTMENT_STATUS_HISTORY LEFT JOIN $master_database.DOA_APPOINTMENT_STATUS AS DOA_APPOINTMENT_STATUS ON DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS=DOA_APPOINTMENT_STATUS_HISTORY.PK_APPOINTMENT_STATUS LEFT JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USERS.PK_USER=DOA_APPOINTMENT_STATUS_HISTORY.PK_USER WHERE PK_APPOINTMENT_MASTER = ".$appointment_data->fields['PK_APPOINTMENT_MASTER']);
                $CHANGED_BY = '';
                while (!$status_data->EOF) {
                    $CHANGED_BY .= "(".$status_data->fields['APPOINTMENT_STATUS']." by ".$status_data->fields['NAME']." at ".date('m-d-Y H:i:s A', strtotime($status_data->fields['TIME_STAMP'])).")<br>";
                    $status_data->MoveNext();
                } ?>
                <tr onclick="$(this).next().slideToggle();">
                    <td style="text-align: left;"><?=$appointment_data->fields['SERVICE_NAME']?></td>
                    <td style="text-align: left;"><?=$service_code_array[$appointment_data->fields['SERVICE_CODE']].'/'.$per_session_price->fields['NUMBER_OF_SESSION']?></td>
                    <td style="text-align: left;"><?=$appointment_data->fields['SERVICE_CODE']?></td>
                    <td style="text-align: center;"><?=date('m/d/Y', strtotime($appointment_data->fields['DATE']))?></td>
                    <td style="text-align: center;"><?=date('h:i A', strtotime($appointment_data->fields['START_TIME']))." - ".date('h:i A', strtotime($appointment_data->fields['END_TIME']))?></td>
                    <td style="text-align: left;"><?=$appointment_data->fields['APPOINTMENT_STATUS']?>
                        <?php if($appointment_data->fields['APPOINTMENT_STATUS']=='Cancelled' && $appointment_data->fields['IS_PAID']==0){ ?>
                            <a href="javascript:;" class="btn btn-info waves-effect waves-light m-l-10 text-white" onclick='ConfirmPosted(<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>);return false;'>Post</a>
                        <?php }else if ($appointment_data->fields['APPOINTMENT_STATUS']=='Cancelled' && $appointment_data->fields['IS_PAID']==1) { ?>
                            <a href="javascript:;" class="btn btn-info waves-effect waves-light m-l-10 text-white" onclick='ConfirmUnposted(<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>);return false;'>Unpost</a>
                        <?php }?>
                    </td>
                    <td style="text-align: right;"><?=number_format((float)$per_session_price->fields['PRICE_PER_SESSION'], 2, '.', ',');?></td>
                    <?php $service_credit = $total_paid_amount -= $per_session_price->fields['PRICE_PER_SESSION']; ?>
                    <td style="color:<?=($service_credit<0)?'red':'black'?>; text-align: right;"><?=number_format((float)($service_credit), 2, '.', ',');?></td>
                </tr>
                <tr style="display: none">
                    <?php if (!empty($appointment_data->fields['COMMENT'])) { ?>
                    <td>Comment : <?=$appointment_data->fields['COMMENT']?></td>
                    <?php } ?>
                    <?php if (!empty($CHANGED_BY)) {?>
                        <td><?=$CHANGED_BY?></td>
                    <?php } ?>
                </tr>
                <?php $appointment_data->MoveNext();
                $j++; } ?>
            </tbody>
        </table>
    </div>
    <?php
        $row->MoveNext();
    } ?>

<script>
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
</script>
