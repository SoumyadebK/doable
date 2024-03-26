<?php
require_once('../../global/config.php');
global $db;
global $db_account;
global $master_database;
global $results_per_page;

$PK_USER_MASTER = !empty($_GET['master_id']) ? $_GET['master_id'] : 0;
$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];

if ($_GET['type'] == 'completed') {
    $enr_condition = " (DOA_ENROLLMENT_MASTER.ALL_APPOINTMENT_DONE = 1 OR DOA_ENROLLMENT_MASTER.STATUS = 'C') ";
    $ledger_condition = " ((DOA_ENROLLMENT_LEDGER.STATUS = 'C' OR DOA_ENROLLMENT_LEDGER.STATUS = 'A') AND DOA_ENROLLMENT_LEDGER.IS_PAID = 1) ";
} else {
    $enr_condition = " DOA_ENROLLMENT_MASTER.STATUS != 'C' AND DOA_ENROLLMENT_MASTER.ALL_APPOINTMENT_DONE = 0 ";
    $ledger_condition = " (((DOA_ENROLLMENT_LEDGER.STATUS = 'C' OR DOA_ENROLLMENT_LEDGER.STATUS = 'CA') AND DOA_ENROLLMENT_LEDGER.IS_PAID = 1) OR DOA_ENROLLMENT_LEDGER.STATUS = 'A')";
}

$ALL_GROUP_CLASS_QUERY = "SELECT
                            DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER,
                            DOA_APPOINTMENT_ENROLLMENT.PK_ENROLLMENT_MASTER,
                            DOA_APPOINTMENT_ENROLLMENT.PK_ENROLLMENT_SERVICE,
                            DOA_APPOINTMENT_MASTER.GROUP_NAME,
                            DOA_APPOINTMENT_MASTER.SERIAL_NUMBER,
                            DOA_APPOINTMENT_MASTER.DATE,
                            DOA_APPOINTMENT_MASTER.START_TIME,
                            DOA_APPOINTMENT_MASTER.END_TIME,
                            DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE,
                            DOA_APPOINTMENT_MASTER.IS_PAID,
                            DOA_SERVICE_MASTER.SERVICE_NAME,
                            DOA_SERVICE_CODE.PK_SERVICE_CODE,
                            DOA_SERVICE_CODE.SERVICE_CODE,
                            DOA_APPOINTMENT_MASTER.IS_PAID,
                            DOA_APPOINTMENT_STATUS.STATUS_CODE,
                            DOA_APPOINTMENT_STATUS.APPOINTMENT_STATUS,
                            DOA_APPOINTMENT_STATUS.COLOR_CODE AS APPOINTMENT_COLOR,
                            DOA_SCHEDULING_CODE.COLOR_CODE,
                            GROUP_CONCAT(SERVICE_PROVIDER.PK_USER SEPARATOR ',') AS SERVICE_PROVIDER_ID,
                            GROUP_CONCAT(CONCAT(CUSTOMER.FIRST_NAME, ' ', CUSTOMER.LAST_NAME) SEPARATOR ',') AS CUSTOMER_NAME
                        FROM
                            DOA_APPOINTMENT_MASTER
                        LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER
                        LEFT JOIN $master_database.DOA_USERS AS SERVICE_PROVIDER ON DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER = SERVICE_PROVIDER.PK_USER
                        
                        LEFT JOIN DOA_APPOINTMENT_CUSTOMER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER
                        LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_APPOINTMENT_CUSTOMER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER
                        LEFT JOIN $master_database.DOA_USERS AS CUSTOMER ON DOA_USER_MASTER.PK_USER = CUSTOMER.PK_USER
                        
                        LEFT JOIN DOA_APPOINTMENT_ENROLLMENT ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_ENROLLMENT.PK_APPOINTMENT_MASTER
                        LEFT JOIN DOA_SCHEDULING_CODE ON DOA_APPOINTMENT_MASTER.PK_SCHEDULING_CODE = DOA_SCHEDULING_CODE.PK_SCHEDULING_CODE
                        LEFT JOIN DOA_SERVICE_MASTER ON DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER
                        LEFT JOIN $master_database.DOA_APPOINTMENT_STATUS AS DOA_APPOINTMENT_STATUS ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS = DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS 
                        LEFT JOIN DOA_SERVICE_CODE ON DOA_APPOINTMENT_MASTER.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE
                        %s
                        AND DOA_APPOINTMENT_MASTER.STATUS = 'A'
                        AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE = 'GROUP'
                        GROUP BY DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER
                        ORDER BY DOA_APPOINTMENT_MASTER.PK_SERVICE_CODE, DOA_APPOINTMENT_MASTER.DATE DESC, DOA_APPOINTMENT_MASTER.START_TIME DESC, DOA_APPOINTMENT_MASTER.START_TIME, DOA_APPOINTMENT_MASTER.START_TIME";

$ALL_APPOINTMENT_QUERY = "SELECT
                            DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER,
                            DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_MASTER,
                            DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_SERVICE,
                            DOA_APPOINTMENT_MASTER.GROUP_NAME,
                            DOA_APPOINTMENT_MASTER.SERIAL_NUMBER,
                            DOA_APPOINTMENT_MASTER.DATE,
                            DOA_APPOINTMENT_MASTER.START_TIME,
                            DOA_APPOINTMENT_MASTER.END_TIME,
                            DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE,
                            DOA_APPOINTMENT_MASTER.IS_PAID,
                            DOA_SERVICE_MASTER.SERVICE_NAME,
                            DOA_SERVICE_CODE.PK_SERVICE_CODE,
                            DOA_SERVICE_CODE.SERVICE_CODE,
                            DOA_APPOINTMENT_MASTER.IS_PAID,
                            DOA_APPOINTMENT_STATUS.STATUS_CODE,
                            DOA_APPOINTMENT_STATUS.APPOINTMENT_STATUS
                        FROM
                            DOA_APPOINTMENT_MASTER
                        LEFT JOIN DOA_APPOINTMENT_ENROLLMENT ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_ENROLLMENT.PK_APPOINTMENT_MASTER
                        LEFT JOIN DOA_SERVICE_MASTER ON DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER
                        LEFT JOIN $master_database.DOA_APPOINTMENT_STATUS AS DOA_APPOINTMENT_STATUS ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS = DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS 
                        LEFT JOIN DOA_SERVICE_CODE ON DOA_APPOINTMENT_MASTER.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE
                        %s
                        AND DOA_APPOINTMENT_MASTER.STATUS = 'A'
                        GROUP BY DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER";

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
if ($_GET['type'] == 'normal') { ?>
    <div class="row" style="padding: 35px 35px 0 35px">
        <h5 style="margin-left: 30%;">List of Pending Services</h5>
        <?php require_once('pending_services.php'); ?>
    </div>
    <div class="row" style="padding: 35px 35px 0 35px">
        <?php require_once('orphan_appointment.php'); ?>
    </div>
    <div class="row" style="margin-bottom: -15px; margin-top: 10px;">
        <div class="col-12 d-flex justify-content-end">
            <?php
            $all_row = $db_account->Execute("SELECT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, DOA_ENROLLMENT_MASTER.ENROLLMENT_ID, DOA_ENROLLMENT_MASTER.ACTIVE FROM `DOA_ENROLLMENT_MASTER` WHERE DOA_ENROLLMENT_MASTER.PK_USER_MASTER='$_GET[master_id]' ORDER BY DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER DESC");
            ?>
            <input type="checkbox" id="toggleAll" onclick="toggleAllCheckboxes()"/>
            <a class="btn btn-info d-none d-lg-block m-15 text-white right-aside" href="javascript:;" onclick="payAll(<?=$all_row->fields['PK_ENROLLMENT_MASTER']?>, '<?=$all_row->fields['ENROLLMENT_ID']?>')">Pay All</a>
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
$row = $db_account->Execute("SELECT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, DOA_ENROLLMENT_MASTER.ENROLLMENT_NAME, DOA_ENROLLMENT_MASTER.ENROLLMENT_ID, DOA_ENROLLMENT_MASTER.AGREEMENT_PDF_LINK, DOA_ENROLLMENT_MASTER.ACTIVE, DOA_ENROLLMENT_MASTER.STATUS, DOA_ENROLLMENT_MASTER.CREATED_ON FROM `DOA_ENROLLMENT_MASTER` WHERE ".$enr_condition." AND DOA_ENROLLMENT_MASTER.PK_USER_MASTER = $PK_USER_MASTER ORDER BY DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER DESC");
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
        <div class="row" onclick="$(this).next().slideToggle();$(this).next().next().slideToggle();" style="cursor:pointer; font-size: 15px; *border: 1px solid #ebe5e2; padding: 8px;">
            <div class="col-2" style="text-align: center; margin-top: 1.5%;">
                <a href="enrollment.php?id=<?=$row->fields['PK_ENROLLMENT_MASTER']?>"><?=$enrollment_name.$row->fields['ENROLLMENT_ID']?></a>
                <p><?=implode(' || ', $serviceMaster)?></p>
                <p><?=date('m/d/Y', strtotime($row->fields['CREATED_ON']))?></p>
                <?php if ($AGREEMENT_PDF_LINK != '' && $AGREEMENT_PDF_LINK != null) { ?>
                    <a href="../uploads/enrollment_pdf/<?=$AGREEMENT_PDF_LINK?>" target="_blank">View Agreement</a>
                <?php } ?>
                <button class="btn btn-danger m-l-10 text-white" style="background-color: #f44336; margin-top: 20px">View Payment Schedule</button>
            </div>
            <div class="col-8">
                <table id="myTable" class="table <?php
                $details = $db_account->Execute("SELECT count(DOA_ENROLLMENT_LEDGER.IS_PAID) AS PAID FROM `DOA_ENROLLMENT_LEDGER` WHERE DOA_ENROLLMENT_LEDGER.IS_PAID = 0 AND PK_ENROLLMENT_MASTER = ".$row->fields['PK_ENROLLMENT_MASTER']);
                $paid_count = $details->RecordCount() > 0 ? $details->fields['PAID'] : 0;
                if ($paid_count==0) { echo 'table-success'; }else{echo "table-striped";}?> border">
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
                    $serviceCodeData = $db_account->Execute("SELECT DOA_ENROLLMENT_SERVICE.*, DOA_SERVICE_CODE.PK_SERVICE_CODE, DOA_SERVICE_CODE.SERVICE_CODE FROM DOA_ENROLLMENT_SERVICE JOIN DOA_SERVICE_CODE ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE WHERE DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = ".$row->fields['PK_ENROLLMENT_MASTER']);
                    $total_amount = 0;
                    $total_paid_amount = 0;
                    $total_used_amount = 0;
                    $enrollment_service_array = [];
                    while (!$serviceCodeData->EOF) {
                        $enrollment_service_array[] = $serviceCodeData->fields['PK_ENROLLMENT_SERVICE'];
                        $PRICE_PER_SESSION = ($serviceCodeData->fields['PRICE_PER_SESSION'] <= 0) ? 1 : $serviceCodeData->fields['PRICE_PER_SESSION'];
                        $TOTAL_PAID_SESSION = ($serviceCodeData->fields['PRICE_PER_SESSION'] <= 0) ? $serviceCodeData->fields['NUMBER_OF_SESSION'] : number_format($serviceCodeData->fields['TOTAL_AMOUNT_PAID']/$serviceCodeData->fields['PRICE_PER_SESSION'], 2);
                        $ENR_BALANCE = $TOTAL_PAID_SESSION - $serviceCodeData->fields['SESSION_COMPLETED'];

                        $total_amount += $serviceCodeData->fields['FINAL_AMOUNT'];
                        $total_paid_amount += $serviceCodeData->fields['TOTAL_AMOUNT_PAID'];
                        $total_used_amount +=  ($PRICE_PER_SESSION * $serviceCodeData->fields['SESSION_COMPLETED']); ?>
                        <tr>
                            <td><?=$serviceCodeData->fields['SERVICE_CODE']?></td>
                            <td style="text-align: right"><?=$serviceCodeData->fields['NUMBER_OF_SESSION']?></td>
                            <td style="text-align: right;"><?=$serviceCodeData->fields['SESSION_COMPLETED']?></td>
                            <td style="text-align: right; color:<?=($ENR_BALANCE < 0)?'red':'black'?>;"><?=number_format($ENR_BALANCE, 2)?></td>
                            <td style="text-align: right">$<?=number_format($serviceCodeData->fields['TOTAL_AMOUNT_PAID'], 2)?></td>
                            <td style="text-align: right;"><?=($ENR_BALANCE > 0) ? number_format($ENR_BALANCE, 2) : 0?></td>
                        </tr>
                    <?php $serviceCodeData->MoveNext();
                    } ?>
                    <tr>
                        <td>Amount</td>
                        <td style="text-align: right;"><?=$total_amount?></td>
                        <td style="text-align: right;"><?=$total_used_amount?></td>
                        <td style="text-align: right; color:<?=($total_paid_amount-$total_used_amount<0)?'red':'black'?>;"><?=$total_paid_amount-$total_used_amount?></td>
                        <td style="text-align: right;">$<?=number_format($total_paid_amount, 2)?></td>
                        <td style="text-align: right;"><?=($total_paid_amount-$total_used_amount > 0) ? $total_paid_amount-$total_used_amount : 0?></td>
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
                        <p style="color: red; margin-top: 20%;">Balance Owned</p>
                    <?php } ?>
            <?php } ?>
            </div>
        </div>

        <table id="myTable" class="table table-striped border" style="display: none">
            <thead style="background-color: #f44336">
                <tr>
                    <th>Due Date</th>
                    <th>Transaction Type</th>
                    <th style="text-align: center;">Billed Amount</th>
                    <th style="text-align: center;">Paid Amount</th>
                    <th style="text-align: center;">Payment Type</th>
                    <th style="text-align: center;">Balance</th>
                    <th style="text-align: center;">
                        <?php if ($paid_count > 0) { ?>
                            <input type="checkbox" class="pay_now_check" id="toggleEnrollment_<?=$row->fields['PK_ENROLLMENT_MASTER']?>" onclick="toggleEnrollmentCheckboxes(<?=$row->fields['PK_ENROLLMENT_MASTER']?>)"/><button type="button" class="btn btn-info m-l-10 text-white pay_selected_btn" onclick="paySelected(<?=$row->fields['PK_ENROLLMENT_MASTER']?>, '<?=$row->fields['ENROLLMENT_ID']?>')" disabled> Pay Selected</button>
                        <?php } ?>
                    </th>
                </tr>
            </thead>

            <tbody>
            <?php
            $billed_amount = 0;
            $balance = 0;
            $billing_details = $db_account->Execute("SELECT * FROM DOA_ENROLLMENT_LEDGER WHERE ".$ledger_condition." AND PK_ENROLLMENT_MASTER = ".$row->fields['PK_ENROLLMENT_MASTER']." AND ENROLLMENT_LEDGER_PARENT = 0 ORDER BY DUE_DATE ASC, PK_ENROLLMENT_LEDGER ASC");
            while (!$billing_details->EOF) {
                $billed_amount = $billing_details->fields['BILLED_AMOUNT'];
                $balance = ($billing_details->fields['BILLED_AMOUNT'] + $balance);
                ?>
                <tr>
                    <td><?=date('m/d/Y', strtotime($billing_details->fields['DUE_DATE']))?></td>
                    <td><?=$billing_details->fields['TRANSACTION_TYPE']?></td>
                    <td style="text-align: right;"><?=$billing_details->fields['BILLED_AMOUNT']?></td>
                    <td></td>
                    <td style="text-align: center;"></td>
                    <td style="text-align: right;"><?=number_format((float)$balance, 2, '.', '')?></td>
                    <td style="text-align: right;">
                        <?php if($billing_details->fields['IS_PAID'] == 0 && $billing_details->fields['STATUS']=='A') { ?>
                            <label><input type="checkbox" name="BILLED_AMOUNT[]" class="pay_now_check BILLED_AMOUNT PAYMENT_CHECKBOX_<?=$row->fields['PK_ENROLLMENT_MASTER']?>" data-pk_enrollment_ledger="<?=$billing_details->fields['PK_ENROLLMENT_LEDGER']?>" value="<?=$billing_details->fields['BILLED_AMOUNT']?>"></label>
                            <button id="payNow" class="pay_now_button btn btn-info waves-effect waves-light m-l-10 text-white" onclick="payNow(<?=$row->fields['PK_ENROLLMENT_MASTER']?>, <?=$billing_details->fields['PK_ENROLLMENT_LEDGER']?>, <?=$billing_details->fields['BILLED_AMOUNT']?>, '<?=$row->fields['ENROLLMENT_ID']?>');">Pay Now</button>
                        <?php } ?>
                    </td>
                </tr>
                <?php
                $RECEIPT_PDF_LINK = '';
                $payment_details = $db_account->Execute("SELECT DOA_ENROLLMENT_LEDGER.*, DOA_ENROLLMENT_PAYMENT.RECEIPT_PDF_LINK, DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE, DOA_ENROLLMENT_PAYMENT.NOTE, DOA_ENROLLMENT_PAYMENT.PAYMENT_INFO, DOA_PAYMENT_TYPE.PAYMENT_TYPE FROM `DOA_ENROLLMENT_LEDGER` LEFT JOIN DOA_ENROLLMENT_PAYMENT ON DOA_ENROLLMENT_LEDGER.PK_ENROLLMENT_LEDGER = DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_LEDGER LEFT JOIN $master_database.DOA_PAYMENT_TYPE AS DOA_PAYMENT_TYPE ON DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE = DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE WHERE DOA_ENROLLMENT_LEDGER.ENROLLMENT_LEDGER_PARENT = ".$billing_details->fields['PK_ENROLLMENT_LEDGER']);
                if ($payment_details->RecordCount() > 0){
                    $RECEIPT_PDF_LINK = $payment_details->fields['RECEIPT_PDF_LINK'];
                    $balance = ($billed_amount - $payment_details->fields['PAID_AMOUNT']);
                    if($payment_details->fields['PK_PAYMENT_TYPE']=='2') {
                        $payment_info = json_decode($payment_details->fields['PAYMENT_INFO']);
                        $payment_type = $payment_details->fields['PAYMENT_TYPE']." : ".$payment_info->CHECK_NUMBER;
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
                        <td style="text-align: center;">
                            <?php if ($RECEIPT_PDF_LINK != '' && $RECEIPT_PDF_LINK != null) { ?>
                                <a href="../uploads/enrollment_pdf/<?=$RECEIPT_PDF_LINK?>" target="_blank">Receipt</a>
                            <?php } ?>
                        </td>
                    </tr>
                <?php }
                $billing_details->MoveNext();
            }
            $cancelled_enrollment = $db_account->Execute("SELECT * FROM `DOA_ENROLLMENT_LEDGER` WHERE PK_ENROLLMENT_MASTER = ".$row->fields['PK_ENROLLMENT_MASTER']." AND ENROLLMENT_LEDGER_PARENT = -1 ORDER BY DUE_DATE ASC, PK_ENROLLMENT_LEDGER ASC");
            while (!$cancelled_enrollment->EOF) {
            ?>
                <tr style="color: <?=(($cancelled_enrollment->fields['TRANSACTION_TYPE'] == 'Refund' || $cancelled_enrollment->fields['TRANSACTION_TYPE'] == 'Refund Credit Available') ? 'green' : (($cancelled_enrollment->fields['TRANSACTION_TYPE'] == 'Billing' || $cancelled_enrollment->fields['TRANSACTION_TYPE'] == 'Balance Owned') ? 'red' : ''))?>;">
                    <td><?=date('m/d/Y', strtotime($cancelled_enrollment->fields['DUE_DATE']))?></td>
                    <td><?=$cancelled_enrollment->fields['TRANSACTION_TYPE']?></td>
                    <td style="text-align: right;"><?=$total_used_amount?></td>
                    <td style="text-align: right;"></td>
                    <td style="text-align: center;"><?=$cancelled_enrollment->fields['TRANSACTION_TYPE']?></td>
                    <td style="text-align: right;"><?=number_format((float)$cancelled_enrollment->fields['BALANCE'], 2, '.', '')?></td>
                    <td style="text-align: right;">
                        <?php if($cancelled_enrollment->fields['IS_PAID'] == 0) { ?>
                            <button id="payNow" class="pay_now_button btn btn-info waves-effect waves-light m-l-10 text-white" onclick="payNow(<?=$cancelled_enrollment->fields['PK_ENROLLMENT_MASTER']?>, <?=$cancelled_enrollment->fields['PK_ENROLLMENT_LEDGER']?>, <?=$cancelled_enrollment->fields['BILLED_AMOUNT']?>, '');">Pay Now</button>
                        <?php } ?>
                    </td>
                </tr>
            <?php $cancelled_enrollment->MoveNext();
            } ?>

            </tbody>
        </table>

        <table id="myTable" class="table border" style="display: none">
            <thead style="background-color: #1E90FF">
                <tr>
                    <th style="text-align: left;">Service</th>
                    <th style="text-align: left;">Apt #</th>
                    <th style="text-align: left;">Service Code</th>
                    <th style="text-align: center;">Date</th>
                    <th style="text-align: center;">Time</th>
                    <th style="text-align: left;">Status</th>
                    <th style="text-align: right;">Session Cost</th>
                    <th style="text-align: right;">Amount $</th>
                </tr>
            </thead>

            <!--<tbody>
            <?php
/*            $group_class_data = $db_account->Execute(sprintf($ALL_GROUP_CLASS_QUERY, " WHERE DOA_APPOINTMENT_ENROLLMENT.PK_ENROLLMENT_MASTER = ".$row->fields['PK_ENROLLMENT_MASTER']." "));
            $j=1;
            $amount_used = 0;
            $service_code_array = [];
            $service_credit_array = [];
            $total_amount_paid_array = [];
            while (!$group_class_data->EOF) {
                $per_session_price = $db_account->Execute("SELECT TOTAL_AMOUNT_PAID, PRICE_PER_SESSION, NUMBER_OF_SESSION, SESSION_CREATED, SESSION_COMPLETED FROM `DOA_ENROLLMENT_SERVICE` WHERE `PK_ENROLLMENT_MASTER` = ".$group_class_data->fields['PK_ENROLLMENT_MASTER']." AND `PK_ENROLLMENT_SERVICE` = ".$group_class_data->fields['PK_ENROLLMENT_SERVICE']);
                $PRICE_PER_SESSION = $per_session_price->fields['PRICE_PER_SESSION'];
                $total_amount_needed = $per_session_price->fields['SESSION_CREATED'] * $PRICE_PER_SESSION;

                if (isset($service_code_array[$group_class_data->fields['SERVICE_CODE']])) {
                    $service_code_array[$group_class_data->fields['SERVICE_CODE']] = $service_code_array[$group_class_data->fields['SERVICE_CODE']] - 1;
                    $service_credit_array[$group_class_data->fields['SERVICE_CODE']] = $service_credit_array[$group_class_data->fields['SERVICE_CODE']] - $per_session_price->fields['PRICE_PER_SESSION'];
                } else {
                    $service_code_array[$group_class_data->fields['SERVICE_CODE']] = $per_session_price->fields['SESSION_CREATED'];
                    $service_credit_array[$group_class_data->fields['SERVICE_CODE']] = $total_amount_needed;
                }

                if (!isset($total_amount_paid_array[$group_class_data->fields['SERVICE_CODE']])) {
                    $total_amount_paid_array[$group_class_data->fields['SERVICE_CODE']] = $per_session_price->fields['TOTAL_AMOUNT_PAID'];
                }

                $status_data = $db_account->Execute("SELECT DOA_APPOINTMENT_STATUS.APPOINTMENT_STATUS, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_APPOINTMENT_STATUS_HISTORY.TIME_STAMP FROM DOA_APPOINTMENT_STATUS_HISTORY LEFT JOIN $master_database.DOA_APPOINTMENT_STATUS AS DOA_APPOINTMENT_STATUS ON DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS=DOA_APPOINTMENT_STATUS_HISTORY.PK_APPOINTMENT_STATUS LEFT JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USERS.PK_USER=DOA_APPOINTMENT_STATUS_HISTORY.PK_USER WHERE PK_APPOINTMENT_MASTER = ".$group_class_data->fields['PK_APPOINTMENT_MASTER']);
                $CHANGED_BY = '';
                while (!$status_data->EOF) {
                    $CHANGED_BY .= "(".$status_data->fields['APPOINTMENT_STATUS']." by ".$status_data->fields['NAME']." at ".date('m-d-Y H:i:s A', strtotime($status_data->fields['TIME_STAMP'])).")<br>";
                    $status_data->MoveNext();
                } */?>
                <tr style="background-color: white;">
                    <td style="text-align: left;"><?php /*=$group_class_data->fields['SERVICE_NAME']*/?></td>
                    <td style="text-align: left;"><?php /*=$service_code_array[$group_class_data->fields['SERVICE_CODE']].'/'.$per_session_price->fields['NUMBER_OF_SESSION']*/?></td>
                    <td style="text-align: left;"><?php /*=$group_class_data->fields['SERVICE_CODE']*/?></td>
                    <td style="text-align: center;"><?php /*=date('m/d/Y', strtotime($group_class_data->fields['DATE']))*/?></td>
                    <td style="text-align: center;"><?php /*=date('h:i A', strtotime($group_class_data->fields['START_TIME']))." - ".date('h:i A', strtotime($group_class_data->fields['END_TIME']))*/?></td>
                    <td style="text-align: left;"><?php /*=$group_class_data->fields['APPOINTMENT_STATUS']*/?></td>
                    <td style="text-align: right;"><?php /*=number_format((float)$PRICE_PER_SESSION, 2, '.', ',');*/?></td>
                    <?php /*$service_credit = $total_amount_paid_array[$group_class_data->fields['SERVICE_CODE']] - $service_credit_array[$group_class_data->fields['SERVICE_CODE']]; */?>
                    <td style="color:<?php /*=($service_credit<0)?'red':'black'*/?>; text-align: right;"><?php /*=number_format((float)($service_credit), 2, '.', ',');*/?></td>
                </tr>
                <tr style="display: none">
                    <?php /*if (!empty($group_class_data->fields['COMMENT'])) { */?>
                        <td>Comment : <?php /*=$group_class_data->fields['COMMENT']*/?></td>
                    <?php /*} */?>
                    <?php /*if (!empty($CHANGED_BY)) {*/?>
                        <td><?php /*=$CHANGED_BY*/?></td>
                    <?php /*} */?>
                </tr>
                <?php /*$group_class_data->MoveNext();
                $j++; } */?>
            </tbody>-->

            <?php
            foreach ($enrollment_service_array AS $key => $pk_enrollment_service) { ?>
                <tbody style="background-color: <?=((($key+1)%2)==0)?'#ebeced':'white'?>;">
                <?php
                $appointment_data = $db_account->Execute(sprintf($ALL_APPOINTMENT_QUERY, " WHERE (DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_SERVICE = '$pk_enrollment_service' OR DOA_APPOINTMENT_ENROLLMENT.PK_ENROLLMENT_SERVICE = '$pk_enrollment_service') "));
                $j=1;
                $amount_used = 0;
                $service_code_array = [];
                $service_credit_array = [];
                $total_amount_paid_array = [];
                while (!$appointment_data->EOF) {
                    $per_session_price = $db_account->Execute("SELECT TOTAL_AMOUNT_PAID, PRICE_PER_SESSION, NUMBER_OF_SESSION, SESSION_CREATED, SESSION_COMPLETED FROM `DOA_ENROLLMENT_SERVICE` WHERE `PK_ENROLLMENT_SERVICE` = ".$pk_enrollment_service);
                    $PRICE_PER_SESSION = $per_session_price->fields['PRICE_PER_SESSION'];
                    $total_amount_needed = $per_session_price->fields['SESSION_CREATED'] * $PRICE_PER_SESSION;

                    if (isset($service_code_array[$appointment_data->fields['SERVICE_CODE']])) {
                        $service_code_array[$appointment_data->fields['SERVICE_CODE']] = $service_code_array[$appointment_data->fields['SERVICE_CODE']] - 1;
                        $service_credit_array[$appointment_data->fields['SERVICE_CODE']] = $service_credit_array[$appointment_data->fields['SERVICE_CODE']] - $per_session_price->fields['PRICE_PER_SESSION'];
                    } else {
                        $service_code_array[$appointment_data->fields['SERVICE_CODE']] = $per_session_price->fields['SESSION_CREATED'];
                        $service_credit_array[$appointment_data->fields['SERVICE_CODE']] = $total_amount_needed;
                    }

                    if (!isset($total_amount_paid_array[$appointment_data->fields['SERVICE_CODE']])) {
                        $total_amount_paid_array[$appointment_data->fields['SERVICE_CODE']] = $per_session_price->fields['TOTAL_AMOUNT_PAID'];
                    } ?>
                    <tr>
                        <td style="text-align: left;"><?=$appointment_data->fields['SERVICE_NAME']?></td>
                        <td style="text-align: left;"><?=$service_code_array[$appointment_data->fields['SERVICE_CODE']].'/'.$per_session_price->fields['NUMBER_OF_SESSION']?></td>
                        <td style="text-align: left;"><?=$appointment_data->fields['SERVICE_CODE']?></td>
                        <td style="text-align: center;"><?=date('m/d/Y', strtotime($appointment_data->fields['DATE']))?></td>
                        <td style="text-align: center;"><?=date('h:i A', strtotime($appointment_data->fields['START_TIME']))." - ".date('h:i A', strtotime($appointment_data->fields['END_TIME']))?></td>
                        <td style="text-align: left;"><?=$appointment_data->fields['APPOINTMENT_STATUS']?></td>
                        <td style="text-align: right;"><?=number_format((float)$PRICE_PER_SESSION, 2, '.', ',');?></td>
                        <?php $service_credit = $total_amount_paid_array[$appointment_data->fields['SERVICE_CODE']] - $service_credit_array[$appointment_data->fields['SERVICE_CODE']]; ?>
                        <td style="color:<?=($service_credit<0)?'red':'black'?>; text-align: right;"><?=number_format((float)($service_credit), 2, '.', ',');?></td>
                    </tr>
                    <?php $appointment_data->MoveNext();
                    $j++; } ?>
                </tbody>
            <?php } ?>
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
