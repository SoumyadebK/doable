<?php
require_once('../../global/config.php');
global $db;
global $db_account;
global $master_database;
global $results_per_page;
global $service_provider_title;

$PK_USER = !empty($_GET['PK_USER']) ? $_GET['PK_USER'] : 0;
$PK_USER_MASTER = !empty($_GET['PK_USER_MASTER']) ? $_GET['PK_USER_MASTER'] : 0;
$PK_ENROLLMENT_MASTER = !empty($_GET['PK_ENROLLMENT_MASTER']) ? $_GET['PK_ENROLLMENT_MASTER'] : 0;
$ENROLLMENT_ID = !empty($_GET['ENROLLMENT_ID']) ? $_GET['ENROLLMENT_ID'] : 0;
$type = !empty($_GET['type']) ? $_GET['type'] : 0;
$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];

if ($type == 'completed') {
    $ledger_condition = " ((DOA_ENROLLMENT_LEDGER.STATUS = 'C' OR DOA_ENROLLMENT_LEDGER.STATUS = 'A') AND DOA_ENROLLMENT_LEDGER.IS_PAID = 1) ";
} else {
    $ledger_condition = " (((DOA_ENROLLMENT_LEDGER.STATUS = 'C' OR DOA_ENROLLMENT_LEDGER.STATUS = 'CA') AND DOA_ENROLLMENT_LEDGER.IS_PAID = 1) OR DOA_ENROLLMENT_LEDGER.STATUS = 'A')";
}

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
                            DOA_SCHEDULING_CODE.UNIT,
                            DOA_SCHEDULING_CODE.SCHEDULING_CODE,
                            DOA_APPOINTMENT_MASTER.IS_PAID,
                            DOA_APPOINTMENT_MASTER.IS_CHARGED,
                            DOA_APPOINTMENT_STATUS.STATUS_CODE,
                            DOA_APPOINTMENT_STATUS.COLOR_CODE AS STATUS_COLOR,
                            DOA_APPOINTMENT_STATUS.APPOINTMENT_STATUS,
                            CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME
                        FROM
                            DOA_APPOINTMENT_MASTER
                        LEFT JOIN DOA_SCHEDULING_CODE ON DOA_APPOINTMENT_MASTER.PK_SCHEDULING_CODE = DOA_SCHEDULING_CODE.PK_SCHEDULING_CODE
                        LEFT JOIN DOA_APPOINTMENT_ENROLLMENT ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_ENROLLMENT.PK_APPOINTMENT_MASTER
                        LEFT JOIN DOA_SERVICE_MASTER ON DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER
                        LEFT JOIN $master_database.DOA_APPOINTMENT_STATUS AS DOA_APPOINTMENT_STATUS ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS = DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS 
                        LEFT JOIN DOA_SERVICE_CODE ON DOA_APPOINTMENT_MASTER.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE
                        LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER
                        LEFT JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USERS.PK_USER = DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER
                        %s
                        AND DOA_APPOINTMENT_MASTER.STATUS = 'A'
                        GROUP BY DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER
                        ORDER BY DOA_APPOINTMENT_MASTER.DATE ASC, DOA_APPOINTMENT_MASTER.START_TIME ASC";

$details = $db_account->Execute("SELECT count(DOA_ENROLLMENT_LEDGER.IS_PAID) AS PAID FROM `DOA_ENROLLMENT_LEDGER` WHERE DOA_ENROLLMENT_LEDGER.IS_PAID = 0 AND PK_ENROLLMENT_MASTER = ".$PK_ENROLLMENT_MASTER);
$paid_count = $details->RecordCount() > 0 ? $details->fields['PAID'] : 0;

$serviceCodeData = $db_account->Execute("SELECT PK_ENROLLMENT_SERVICE FROM DOA_ENROLLMENT_SERVICE WHERE DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = ".$PK_ENROLLMENT_MASTER);
$enrollment_service_array = [];
while (!$serviceCodeData->EOF) {
    $enrollment_service_array[] = $serviceCodeData->fields['PK_ENROLLMENT_SERVICE'];
    $serviceCodeData->MoveNext();
}
?>

<table id="myTable" class="table billing_details">
    <thead style="background-color: #f44336; cursor:pointer;" onclick="$(this).next().slideToggle();">
        <tr>
            <th style="text-align: center;">Due Date</th>
            <th style="text-align: center;">Transaction Type</th>
            <th style="text-align: center;">Billed Amount</th>
            <th style="text-align: center;">Paid Amount</th>
            <th style="text-align: center;">Payment Type</th>
            <th style="text-align: center;">Balance</th>
            <th style="text-align: center;">
                <?php if ($paid_count > 0) { ?>
                    <input type="checkbox" class="pay_now_check" id="toggleEnrollment_<?=$PK_ENROLLMENT_MASTER?>" onclick="toggleEnrollmentCheckboxes(<?=$PK_ENROLLMENT_MASTER?>)"/><button type="button" class="btn btn-info m-l-10 text-white pay_selected_btn" onclick="paySelected(<?=$PK_ENROLLMENT_MASTER?>, '<?=$ENROLLMENT_ID?>')" disabled> Pay Selected</button>
                <?php } ?>
            </th>
        </tr>
    </thead>

    <tbody>
    <?php
    /*$payment_count = $billing_details = $db_account->Execute("SELECT * FROM DOA_ENROLLMENT_LEDGER WHERE PK_ENROLLMENT_MASTER = ".$PK_ENROLLMENT_MASTER." AND TRANSACTION_TYPE = 'Payment' AND IS_PAID = 1");
    $payment_counter = $payment_count->RecordCount();*/
    $b = 0;
    $p = 0;
    $billed_amount = 0;
    $balance = 0;
    $billing_details = $db_account->Execute("SELECT * FROM DOA_ENROLLMENT_LEDGER WHERE ".$ledger_condition." AND PK_ENROLLMENT_MASTER = ".$PK_ENROLLMENT_MASTER." AND ENROLLMENT_LEDGER_PARENT = 0 ORDER BY DUE_DATE ASC, PK_ENROLLMENT_LEDGER ASC");
    while (!$billing_details->EOF) {
        $billed_amount = $billing_details->fields['BILLED_AMOUNT'];
        $balance = ($billing_details->fields['BILLED_AMOUNT'] + $balance);
        ?>
        <tr style="border-style: hidden; background-color: <?=(fmod($b, 2) == 0) ? '#ebeced' : ''?>;">
            <td style="text-align: center;">
                <a href="javascript:" title="Edit Info" onmouseover="getEditHistory(this, <?=$billing_details->fields['PK_ENROLLMENT_LEDGER']?>, 'enrollment_ledger')"><i class="ti-info-alt"></i></a>&nbsp;&nbsp;
                <?=date('m/d/Y', strtotime($billing_details->fields['DUE_DATE']))?>&nbsp;&nbsp;
                <a href="javascript:" title="Edit Due Date" onclick="editBillingDueDate(<?=$billing_details->fields['PK_ENROLLMENT_LEDGER']?>, '<?=date('m/d/Y', strtotime($billing_details->fields['DUE_DATE']))?>', 'billing')"><i class="ti-pencil-alt"></i></a>
            </td>
            <td style="text-align: center;"><?=$billing_details->fields['TRANSACTION_TYPE']?></td>
            <td style="text-align: right;"><?=$billing_details->fields['BILLED_AMOUNT']?></td>
            <td></td>
            <td style="text-align: center;"></td>
            <td style="text-align: right;"><?php /*=($billing_details->fields['AMOUNT_REMAIN'] > 0) ? $billing_details->fields['AMOUNT_REMAIN'] : ''*/?><?php /*=number_format((float)$balance, 2, '.', '')*/?></td>
            <td style="text-align: right;">
                <?php if($billing_details->fields['IS_PAID'] == 0 && $billing_details->fields['STATUS'] == 'A') {
                    if ($billing_details->fields['AMOUNT_REMAIN'] > 0) { ?>
                        <button id="payNow" class="pay_now_button btn btn-info waves-effect waves-light m-l-10 text-white" onclick="payNow(<?=$PK_ENROLLMENT_MASTER?>, <?=$billing_details->fields['PK_ENROLLMENT_LEDGER']?>, <?=$billing_details->fields['AMOUNT_REMAIN']?>, '<?=$ENROLLMENT_ID?>');">Pay Now</button>
                    <?php } else { ?>
                        <label><input type="checkbox" name="PK_ENROLLMENT_LEDGER[]" class="pay_now_check PK_ENROLLMENT_LEDGER PAYMENT_CHECKBOX_<?=$PK_ENROLLMENT_MASTER?>" data-billed_amount="<?=$billing_details->fields['BILLED_AMOUNT']?>" value="<?=$billing_details->fields['PK_ENROLLMENT_LEDGER']?>"></label>
                        <button id="payNow" class="pay_now_button btn btn-info waves-effect waves-light m-l-10 text-white" onclick="payNow(<?=$PK_ENROLLMENT_MASTER?>, <?=$billing_details->fields['PK_ENROLLMENT_LEDGER']?>, <?=$billing_details->fields['BILLED_AMOUNT']?>, '<?=$ENROLLMENT_ID?>');">Pay Now</button>
                    <?php }
                } ?>
            </td>
        </tr>
        <?php
        $payment_details = $db_account->Execute("SELECT DOA_ENROLLMENT_PAYMENT.*, DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE, DOA_PAYMENT_TYPE.PAYMENT_TYPE FROM DOA_ENROLLMENT_PAYMENT LEFT JOIN $master_database.DOA_PAYMENT_TYPE AS DOA_PAYMENT_TYPE ON DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE = DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE WHERE PK_ENROLLMENT_LEDGER = ".$billing_details->fields['PK_ENROLLMENT_LEDGER']);
        if ($payment_details->RecordCount() > 0) {
            $p++;
            $balance = $billed_amount;
            while (!$payment_details->EOF) {
                $PK_ENROLLMENT_MASTER = $payment_details->fields['PK_ENROLLMENT_MASTER'];
                $PK_ENROLLMENT_LEDGER = $payment_details->fields['PK_ENROLLMENT_LEDGER'];

                if ($payment_details->fields['TYPE'] == 'Payment' && $payment_details->fields['IS_REFUNDED'] == 0) {
                    $balance -= $payment_details->fields['AMOUNT'];
                }

                if ($payment_details->fields['TYPE'] == 'Move') {
                    $payment_type = 'Wallet';
                } elseif ($payment_details->fields['PK_PAYMENT_TYPE'] == '2') {
                    $payment_info = json_decode($payment_details->fields['PAYMENT_INFO']);
                    $payment_type = $payment_details->fields['PAYMENT_TYPE']." : ".((isset($payment_info->CHECK_NUMBER)) ? $payment_info->CHECK_NUMBER : '');
                } elseif (in_array($payment_details->fields['PK_PAYMENT_TYPE'], [1, 8, 9, 10, 11, 13, 14])) {
                    $payment_info = json_decode($payment_details->fields['PAYMENT_INFO']);
                    $payment_type = $payment_details->fields['PAYMENT_TYPE']." # ".((isset($payment_info->LAST4)) ? $payment_info->LAST4 : '');
                } elseif ($payment_details->fields['PK_PAYMENT_TYPE'] == '7') {
                    $receipt_number_array = explode(',', $payment_details->fields['RECEIPT_NUMBER']);
                    $payment_type_array = [];
                    foreach ($receipt_number_array as $receipt_number) {
                        $receipt_payment_details = $db_account->Execute("SELECT DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE, DOA_ENROLLMENT_PAYMENT.PAYMENT_INFO, DOA_PAYMENT_TYPE.PAYMENT_TYPE FROM DOA_ENROLLMENT_PAYMENT LEFT JOIN $master_database.DOA_PAYMENT_TYPE AS DOA_PAYMENT_TYPE ON DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE = DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE WHERE DOA_ENROLLMENT_PAYMENT.RECEIPT_NUMBER = '$receipt_number'");
                        if ($receipt_payment_details->fields['PK_PAYMENT_TYPE'] == '2') {
                            $payment_info = json_decode($receipt_payment_details->fields['PAYMENT_INFO']);
                            $payment_type_array[] = $receipt_payment_details->fields['PAYMENT_TYPE']." : ".((isset($payment_info->CHECK_NUMBER)) ? $payment_info->CHECK_NUMBER : '');
                        } else {
                            $payment_type_array[] = $receipt_payment_details->fields['PAYMENT_TYPE'];
                        }
                    }
                    $payment_type = implode(', ', $payment_type_array);
                } else{
                    $payment_type = $payment_details->fields['PAYMENT_TYPE'];
                } ?>
                <tr style="border-style: hidden; color: <?=($payment_details->fields['TYPE'] == 'Refund') ? 'green' : ''?>; background-color: <?=(fmod($b, 2) == 0) ? '#ebeced' : ''?>;">
                    <td style="text-align: center;">
                        <a href="javascript:" title="Edit Info" onmouseover="getEditHistory(this, <?=$payment_details->fields['PK_ENROLLMENT_PAYMENT']?>, 'enrollment_payment')"><i class="ti-info-alt"></i></a>&nbsp;&nbsp;
                        <?=date('m/d/Y', strtotime($payment_details->fields['PAYMENT_DATE']))?>&nbsp;&nbsp;
                        <a href="javascript:" title="Edit Due Date" onclick="editBillingDueDate(<?=$payment_details->fields['PK_ENROLLMENT_PAYMENT']?>, '<?=date('m/d/Y', strtotime($payment_details->fields['PAYMENT_DATE']))?>', 'payment')"><i class="ti-pencil-alt"></i></a>
                    </td>
                    <td style="text-align: center;"><?=$payment_details->fields['TYPE']?></td>
                    <td></td>
                    <td style="text-align: right;"><?=$payment_details->fields['AMOUNT']?></td>
                    <td style="text-align: center;"><?=$payment_type?></td>
                    <td style="text-align: right;"><?=($payment_details->fields['TYPE'] == 'Payment' || $payment_details->fields['TYPE'] == 'Adjustment') ? number_format((float)$balance, 2, '.', '') : ''?></td>
                    <td style="text-align: right;">
                        <?php if (($payment_details->fields['IS_REFUNDED'] == 0) && ($billing_details->fields['STATUS'] == 'A') && ($payment_details->fields['TYPE'] == 'Payment')) { ?>
                            <a class="btn btn-info waves-effect waves-light text-white <?=($payment_details->fields['IS_REFUNDED'] == 1)?'disabled':''?>" href="javascript:" onclick="moveToWallet(this, <?=$payment_details->fields['PK_ENROLLMENT_PAYMENT']?>, <?=$payment_details->fields['PK_ENROLLMENT_MASTER']?>, <?=$payment_details->fields['PK_ENROLLMENT_LEDGER']?>, <?=$PK_USER_MASTER?>, <?=$payment_details->fields['AMOUNT']?>, 'active', 'Move', <?=$p?>)">Move</a>
                            <a class="btn btn-info waves-effect waves-light text-white <?=($payment_details->fields['IS_REFUNDED'] == 1)?'disabled':''?>" href="javascript:" onclick="moveToWallet(this, <?=$payment_details->fields['PK_ENROLLMENT_PAYMENT']?>, <?=$payment_details->fields['PK_ENROLLMENT_MASTER']?>, <?=$payment_details->fields['PK_ENROLLMENT_LEDGER']?>, <?=$PK_USER_MASTER?>, <?=$payment_details->fields['AMOUNT']?>, 'active', 'Refund', <?=$p?>)">Refund</a>
                        <?php } ?>
                        <a class="btn btn-info waves-effect waves-light text-white" onclick="openReceipt(<?=$PK_ENROLLMENT_MASTER?>, '<?=$payment_details->fields['RECEIPT_NUMBER']?>')" href="javascript:">Receipt</a>
                    </td>
                </tr>
                <?php $payment_details->MoveNext();
            }
        }
        $b++;
        $billing_details->MoveNext();
    }
    $cancelled_enrollment_ledger = $db_account->Execute("SELECT * FROM `DOA_ENROLLMENT_LEDGER` WHERE PK_ENROLLMENT_MASTER = ".$PK_ENROLLMENT_MASTER." AND DOA_ENROLLMENT_LEDGER.ENROLLMENT_LEDGER_PARENT = -1");
    while (!$cancelled_enrollment_ledger->EOF) {
        ?>
        <tr style="color: <?=(($cancelled_enrollment_ledger->fields['TRANSACTION_TYPE'] == 'Refund' || $cancelled_enrollment_ledger->fields['TRANSACTION_TYPE'] == 'Refund Credit Available') ? 'green' : (($cancelled_enrollment_ledger->fields['TRANSACTION_TYPE'] == 'Billing' || $cancelled_enrollment_ledger->fields['TRANSACTION_TYPE'] == 'Balance Owed') ? 'red' : ''))?>;">
            <td style="text-align: center;"><?=date('m/d/Y', strtotime($cancelled_enrollment_ledger->fields['DUE_DATE']))?></td>
            <td style="text-align: center;">Canceled<?php /*=$cancelled_enrollment_ledger->fields['TRANSACTION_TYPE']*/?></td>
            <td style="text-align: right;"><?=$cancelled_enrollment_ledger->fields['BILLED_AMOUNT']?></td>
            <td style="text-align: right;"></td>
            <td style="text-align: center;"><?=$cancelled_enrollment_ledger->fields['TRANSACTION_TYPE']?></td>
            <td style="text-align: right;"><?=number_format((float)$cancelled_enrollment_ledger->fields['BALANCE'], 2, '.', '')?></td>
            <td style="text-align: right;">
                <?php if($cancelled_enrollment_ledger->fields['IS_PAID'] == 0) { ?>
                    <button id="payNow" class="pay_now_button btn btn-info waves-effect waves-light m-l-10 text-white" onclick="payNow(<?=$cancelled_enrollment_ledger->fields['PK_ENROLLMENT_MASTER']?>, <?=$cancelled_enrollment_ledger->fields['PK_ENROLLMENT_LEDGER']?>, <?=$cancelled_enrollment_ledger->fields['BILLED_AMOUNT']?>, '');">Pay Now</button>
                <?php } elseif($cancelled_enrollment_ledger->fields['IS_PAID'] == 2) { ?>
                    <button class="btn btn-success waves-effect waves-light m-l-10 text-white" onclick="moveToWallet(this, 0, <?=$cancelled_enrollment_ledger->fields['PK_ENROLLMENT_MASTER']?>, <?=$cancelled_enrollment_ledger->fields['PK_ENROLLMENT_LEDGER']?>, <?=$PK_USER_MASTER?>, <?=$cancelled_enrollment_ledger->fields['BALANCE']?>, 'cancelled', 'Refund', 0)">Refund</button>
                <?php } ?>
            </td>
        </tr>

        <?php
        $cancelled_enrollment_payment_details = $db_account->Execute("SELECT DOA_ENROLLMENT_PAYMENT.*, DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE, DOA_PAYMENT_TYPE.PAYMENT_TYPE FROM DOA_ENROLLMENT_PAYMENT LEFT JOIN $master_database.DOA_PAYMENT_TYPE AS DOA_PAYMENT_TYPE ON DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE = DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE WHERE PK_ENROLLMENT_LEDGER = ".$cancelled_enrollment_ledger->fields['PK_ENROLLMENT_LEDGER']);
        if ($cancelled_enrollment_payment_details->RecordCount() > 0) {
            $p++;
            $balance = $billed_amount;
            while (!$cancelled_enrollment_payment_details->EOF) {
                if ($cancelled_enrollment_payment_details->fields['TYPE'] == 'Move') {
                    $payment_type = 'Wallet';
                } elseif ($cancelled_enrollment_payment_details->fields['PK_PAYMENT_TYPE'] == '2') {
                    $payment_info = json_decode($cancelled_enrollment_payment_details->fields['PAYMENT_INFO']);
                    $payment_type = $cancelled_enrollment_payment_details->fields['PAYMENT_TYPE']." : ".((isset($payment_info->CHECK_NUMBER)) ? $payment_info->CHECK_NUMBER : '');
                } elseif (in_array($cancelled_enrollment_payment_details->fields['PK_PAYMENT_TYPE'], [1, 8, 9, 10, 11, 13, 14])) {
                    $payment_info = json_decode($cancelled_enrollment_payment_details->fields['PAYMENT_INFO']);
                    $payment_type = $cancelled_enrollment_payment_details->fields['PAYMENT_TYPE']." # ".((isset($payment_info->LAST4)) ? $payment_info->LAST4 : '');
                } elseif ($cancelled_enrollment_payment_details->fields['PK_PAYMENT_TYPE'] == '7') {
                    $receipt_number_array = explode(',', $cancelled_enrollment_payment_details->fields['RECEIPT_NUMBER']);
                    $payment_type_array = [];
                    foreach ($receipt_number_array as $receipt_number) {
                        $receipt_payment_details = $db_account->Execute("SELECT DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE, DOA_ENROLLMENT_PAYMENT.PAYMENT_INFO, DOA_PAYMENT_TYPE.PAYMENT_TYPE FROM DOA_ENROLLMENT_PAYMENT LEFT JOIN $master_database.DOA_PAYMENT_TYPE AS DOA_PAYMENT_TYPE ON DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE = DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE WHERE DOA_ENROLLMENT_PAYMENT.RECEIPT_NUMBER = '$receipt_number'");
                        if ($receipt_payment_details->fields['PK_PAYMENT_TYPE'] == '2') {
                            $payment_info = json_decode($receipt_payment_details->fields['PAYMENT_INFO']);
                            $payment_type_array[] = $receipt_payment_details->fields['PAYMENT_TYPE']." : ".((isset($payment_info->CHECK_NUMBER)) ? $payment_info->CHECK_NUMBER : '');
                        } else {
                            $payment_type_array[] = $receipt_payment_details->fields['PAYMENT_TYPE'];
                        }
                    }
                    $payment_type = implode(', ', $payment_type_array);
                } else{
                    $payment_type = $cancelled_enrollment_payment_details->fields['PAYMENT_TYPE'];
                } ?>
            <tr style="border-style: hidden; color: <?=($cancelled_enrollment_payment_details->fields['TYPE'] == 'Refund') ? 'green' : ''?>; background-color: <?=(fmod($b, 2) == 0) ? '#ebeced' : ''?>;">
                <td style="text-align: center;"><?=date('m/d/Y', strtotime($cancelled_enrollment_payment_details->fields['PAYMENT_DATE']))?></td>
                <td style="text-align: center;"><?=$cancelled_enrollment_payment_details->fields['TYPE']?></td>
                <td></td>
                <td style="text-align: right;"><?=$cancelled_enrollment_payment_details->fields['AMOUNT']?></td>
                <td style="text-align: center;"><?=$payment_type?></td>
                <td style="text-align: right;"></td>
                <td style="text-align: right;">
                    <a class="btn btn-info waves-effect waves-light text-white" onclick="openReceipt(<?=$PK_ENROLLMENT_MASTER?>, '<?=$cancelled_enrollment_payment_details->fields['RECEIPT_NUMBER']?>')" href="javascript:">Receipt</a>
                </td>
            </tr>
        <?php $cancelled_enrollment_payment_details->MoveNext();
            }
        }
        $cancelled_enrollment_ledger->MoveNext();
    } ?>

    </tbody>
</table>

<table id="myTable" class="table border appointment_details">
    <thead style="background-color: #1E90FF; cursor:pointer;" onclick="$(this).closest('.appointment_details').find('tbody').slideToggle();">
        <tr>
            <th style="text-align: left;">Service</th>
            <th style="text-align: left;">Apt #</th>
            <th style="text-align: left;">Service Code</th>
            <th style="text-align: center;">Date</th>
            <th style="text-align: center;">Time</th>
            <th style="text-align: left;">Status</th>
            <th style="text-align: left;"><?=$service_provider_title?></th>
            <th style="text-align: right;">Session Cost</th>
            <th style="text-align: right;">Amount $</th>
        </tr>
    </thead>

    <?php
    foreach ($enrollment_service_array AS $key => $pk_enrollment_service) { ?>
        <tbody style="background-color: <?=((($key+1)%2)==0)?'#ebeced':'white'?>;">
        <?php
        $appointment_data = $db_account->Execute(sprintf($ALL_APPOINTMENT_QUERY, " WHERE (DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_SERVICE = '$pk_enrollment_service' OR DOA_APPOINTMENT_ENROLLMENT.PK_ENROLLMENT_SERVICE = '$pk_enrollment_service') "));
        $amount_used = 0;
        $service_code_array = [];
        $service_credit_array = [];
        $total_amount_paid_array = [];
        $appointment_array = [];
        while (!$appointment_data->EOF) {
            //$SESSION_CREATED = getSessionCreatedCount($pk_enrollment_service, $appointment_data->fields['APPOINTMENT_TYPE']);
            $UNIT = $appointment_data->fields['UNIT'];
            //echo $appointment_data->fields['SCHEDULING_CODE']."-".$UNIT."<br>";
            $per_session_price = $db_account->Execute("SELECT TOTAL_AMOUNT_PAID, PRICE_PER_SESSION, NUMBER_OF_SESSION FROM `DOA_ENROLLMENT_SERVICE` WHERE `PK_ENROLLMENT_SERVICE` = " . $pk_enrollment_service);
            $PRICE_PER_SESSION = $per_session_price->fields['PRICE_PER_SESSION'] * $UNIT;
            //$total_amount_needed = $SESSION_CREATED * $per_session_price->fields['PRICE_PER_SESSION'];

            if ($appointment_data->fields['APPOINTMENT_STATUS'] != 'Cancelled' || $appointment_data->fields['IS_CHARGED'] == 1) {
                if (isset($service_code_array[$appointment_data->fields['SERVICE_CODE']])) {
                    $service_code_array[$appointment_data->fields['SERVICE_CODE']] = $service_code_array[$appointment_data->fields['SERVICE_CODE']] + $UNIT;
                    $service_credit_array[$appointment_data->fields['SERVICE_CODE']] = $service_credit_array[$appointment_data->fields['SERVICE_CODE']] + $PRICE_PER_SESSION;
                } else {
                    $service_code_array[$appointment_data->fields['SERVICE_CODE']] = $UNIT;
                    $service_credit_array[$appointment_data->fields['SERVICE_CODE']] = $PRICE_PER_SESSION;
                }
            }

            if ($appointment_data->fields['APPOINTMENT_TYPE'] == 'GROUP') {
                $appointment_enr_data = $db_account->Execute("SELECT * FROM DOA_APPOINTMENT_ENROLLMENT WHERE PK_APPOINTMENT_MASTER = " . $appointment_data->fields['PK_APPOINTMENT_MASTER'] . " AND PK_USER_MASTER = '$PK_USER_MASTER'");
                $IS_CHARGED = $appointment_enr_data->fields['IS_CHARGED'];
            } else {
                $IS_CHARGED = $appointment_data->fields['IS_CHARGED'];
            }

            if (!isset($total_amount_paid_array[$appointment_data->fields['SERVICE_CODE']])) {
                $total_amount_paid_array[$appointment_data->fields['SERVICE_CODE']] = $per_session_price->fields['TOTAL_AMOUNT_PAID'];
            }

            $appointment_array[] = [
                "PK_APPOINTMENT_MASTER" => $appointment_data->fields['PK_APPOINTMENT_MASTER'],
                "SERVICE_NAME" => $appointment_data->fields['SERVICE_NAME'],
                "APPOINTMENT_STATUS" => $appointment_data->fields['APPOINTMENT_STATUS'],
                "STATUS_COLOR" => $appointment_data->fields['STATUS_COLOR'],
                "IS_CHARGED" => $IS_CHARGED,
                "APPOINTMENT_NUMBER" => $service_code_array[$appointment_data->fields['SERVICE_CODE']] . '/' . $per_session_price->fields['NUMBER_OF_SESSION'],
                "SERVICE_CODE" => $appointment_data->fields['SERVICE_CODE'],
                "APPOINTMENT_DATE" => date('m/d/Y', strtotime($appointment_data->fields['DATE'])),
                "APPOINTMENT_TIME" => date('h:i A', strtotime($appointment_data->fields['START_TIME'])) . " - " . date('h:i A', strtotime($appointment_data->fields['END_TIME'])),
                "SERVICE_PROVIDER" => $appointment_data->fields['NAME'],
                "PRICE_PER_SESSION" => $PRICE_PER_SESSION,
                "SERVICE_CREDIT" => $total_amount_paid_array[$appointment_data->fields['SERVICE_CODE']] - $service_credit_array[$appointment_data->fields['SERVICE_CODE']]
            ];
            $appointment_data->MoveNext();
        }

        foreach (array_reverse($appointment_array) AS $key => $appointment_value) { ?>
            <tr>
                <td style="text-align: left;">
                    <a href="javascript:" title="Edit Appointment" onclick="editThisAppointment(<?=$appointment_value['PK_APPOINTMENT_MASTER']?>, <?=$PK_USER?>, <?=$PK_USER_MASTER?>);"><i class="ti-pencil-alt"></i></a>&nbsp;&nbsp;
                    <?=$appointment_value['SERVICE_NAME']?>
                </td>
                <?php if($appointment_value['APPOINTMENT_STATUS'] == 'Cancelled' && $appointment_value['IS_CHARGED'] == 0) {?>
                    <td></td>
                <?php } else { ?>
                    <td style="text-align: left;"><?=$appointment_value['APPOINTMENT_NUMBER']?></td>
                <?php }?>
                <td style="text-align: left;"><?=$appointment_value['SERVICE_CODE']?></td>
                <td style="text-align: center;"><?=$appointment_value['APPOINTMENT_DATE']?></td>
                <td style="text-align: center;"><?=$appointment_value['APPOINTMENT_TIME']?></td>
                <td style="text-align: left; color: <?=$appointment_value['STATUS_COLOR']?>">
                    <?=$appointment_value['APPOINTMENT_STATUS']?>&nbsp;
                    <?php if ($appointment_value['IS_CHARGED'] == 1) { ?>
                        <i class="ti-money"></i>
                    <?php } ?>
                </td>
                <td style="text-align: left;"><?=$appointment_value['SERVICE_PROVIDER']?></td>
                <?php if($appointment_value['APPOINTMENT_STATUS']=='Cancelled' && $appointment_value['IS_CHARGED'] == 0) {?>
                    <td></td>
                <?php } else {?>
                    <td style="text-align: right;"><?=number_format((float)$appointment_value['PRICE_PER_SESSION'], 2, '.', ',');?></td>
                <?php }?>
                <?php if($appointment_value['APPOINTMENT_STATUS']=='Cancelled' && $appointment_value['IS_CHARGED'] == 0) {?>
                    <td></td>
                <?php } else { ?>
                    <td style="color:<?=($appointment_value['SERVICE_CREDIT'] < 0)?'red':'black'?>; text-align: right;"><?=number_format((float)($appointment_value['SERVICE_CREDIT']), 2, '.', ',');?></td>
                <?php }?>
            </tr>
        <?php } ?>
        </tbody>
    <?php } ?>
</table>
