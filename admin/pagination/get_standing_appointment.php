<?php
require_once('../../global/config.php');
global $db;
global $db_account;
global $master_database;

$STANDING_ID = $_GET['STANDING_ID'];
$PK_APPOINTMENT_MASTER = $_GET['PK_APPOINTMENT_MASTER'];

$ALL_APPOINTMENT_QUERY = "SELECT
                            DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER,
                            DOA_APPOINTMENT_MASTER.STANDING_ID,
                            DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_SERVICE,
                            DOA_APPOINTMENT_MASTER.GROUP_NAME,
                            DOA_APPOINTMENT_MASTER.SERIAL_NUMBER,
                            DOA_APPOINTMENT_MASTER.DATE,
                            DOA_APPOINTMENT_MASTER.START_TIME,
                            DOA_APPOINTMENT_MASTER.END_TIME,
                            DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE,
                            DOA_APPOINTMENT_MASTER.IS_PAID,
                            DOA_ENROLLMENT_MASTER.ENROLLMENT_NAME,
                            DOA_ENROLLMENT_MASTER.ENROLLMENT_ID,
                            DOA_SERVICE_MASTER.SERVICE_NAME,
                            DOA_SERVICE_CODE.SERVICE_CODE,
                            DOA_APPOINTMENT_MASTER.IS_PAID,
                            DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE,
                            DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS,
                            DOA_APPOINTMENT_STATUS.STATUS_CODE,
                            DOA_APPOINTMENT_STATUS.COLOR_CODE AS APPOINTMENT_COLOR,
                            DOA_SCHEDULING_CODE.COLOR_CODE,
                            GROUP_CONCAT(DISTINCT(CONCAT(SERVICE_PROVIDER.FIRST_NAME, ' ', SERVICE_PROVIDER.LAST_NAME)) SEPARATOR ', ') AS SERVICE_PROVIDER_NAME,
                            GROUP_CONCAT(DISTINCT(CONCAT(CUSTOMER.FIRST_NAME, ' ', CUSTOMER.LAST_NAME)) SEPARATOR ', ') AS CUSTOMER_NAME
                        FROM
                            DOA_APPOINTMENT_MASTER
                        LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER
                        LEFT JOIN $master_database.DOA_USERS AS SERVICE_PROVIDER ON DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER = SERVICE_PROVIDER.PK_USER
                        
                        LEFT JOIN DOA_APPOINTMENT_CUSTOMER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER
                        LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_APPOINTMENT_CUSTOMER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER
                        LEFT JOIN $master_database.DOA_USERS AS CUSTOMER ON DOA_USER_MASTER.PK_USER = CUSTOMER.PK_USER
                                
                        LEFT JOIN DOA_SCHEDULING_CODE ON DOA_APPOINTMENT_MASTER.PK_SCHEDULING_CODE = DOA_SCHEDULING_CODE.PK_SCHEDULING_CODE
                        LEFT JOIN DOA_SERVICE_MASTER ON DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER
                        LEFT JOIN $master_database.DOA_APPOINTMENT_STATUS AS DOA_APPOINTMENT_STATUS ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS = DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS 
                        LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER
                        LEFT JOIN DOA_SERVICE_CODE ON DOA_APPOINTMENT_MASTER.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE
                        WHERE DOA_APPOINTMENT_MASTER.STANDING_ID = ".$STANDING_ID." AND DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER != $PK_APPOINTMENT_MASTER
                        GROUP BY DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER
                        ORDER BY DOA_APPOINTMENT_MASTER.DATE DESC, DOA_APPOINTMENT_MASTER.START_TIME DESC";
$i = 1;
$appointment_data = $db_account->Execute($ALL_APPOINTMENT_QUERY);
while (!$appointment_data->EOF) { ?>
    <tr class="added_standing">
        <td style="text-align: end;"></td>
        <td><?=(($appointment_data->fields['APPOINTMENT_TYPE'] == 'NORMAL') ? 'Private Session' : (($appointment_data->fields['APPOINTMENT_TYPE'] == 'AD-HOC') ? 'Ad-Hoc' : 'Group Class'))?></td>
        <td><?=$appointment_data->fields['CUSTOMER_NAME']?></td>
        <?php if (!empty($appointment_data->fields['ENROLLMENT_ID']) || !empty($appointment_data->fields['ENROLLMENT_NAME'])) { ?>
            <td><?=(($appointment_data->fields['ENROLLMENT_NAME']) ? $appointment_data->fields['ENROLLMENT_NAME'].' - ' : '').$appointment_data->fields['ENROLLMENT_ID']." || ".$appointment_data->fields['SERVICE_NAME']." || ".$appointment_data->fields['SERVICE_CODE']?></td>
        <?php } elseif (empty($appointment_data->fields['SERVICE_NAME']) && empty($appointment_data->fields['SERVICE_CODE'])) { ?>
            <td><?=$appointment_data->fields['SERVICE_NAME']."  ".$appointment_data->fields['SERVICE_CODE']?></td>
        <?php } else { ?>
            <td><?=$appointment_data->fields['SERVICE_NAME']." || ".$appointment_data->fields['SERVICE_CODE']?></td>
        <?php } ?>
        <td><?=$appointment_data->fields['SERVICE_PROVIDER_NAME']?></td>
        <td><?=date('l', strtotime($appointment_data->fields['DATE']))?></td>
        <td><?=date('m/d/Y', strtotime($appointment_data->fields['DATE']))?></td>
        <td><?=date('h:i A', strtotime($appointment_data->fields['START_TIME']))." - ".date('h:i A', strtotime($appointment_data->fields['END_TIME']))?></td>
        <td><?=($appointment_data->fields['IS_PAID'] == 1)?'Paid':'Unpaid'?></td>
        <td style="text-align: center;">
            <?php
            if ($appointment_data->fields['CUSTOMER_NAME']) {
                if ($appointment_data->fields['PK_APPOINTMENT_STATUS'] == 2){ ?>
                    <i class="fa fa-check-circle" style="font-size:25px;color:#35e235;"></i>
                <?php } else { ?>
                    <a href="javascript:" data-id="<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>" onclick='confirmComplete($(this));'><i class="fa fa-check-circle" style="font-size:25px;color:#a9b7a9;"></i></a>
                <?php }
            } ?>
        </td>
        <td>
            <a href="copy_schedule.php?id=<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>"><i class="fa fa-copy"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <a href="all_schedules.php?id=<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>" onclick='ConfirmDelete(<?=$appointment_data->fields['PK_APPOINTMENT_MASTER']?>);'><i class="fa fa-trash"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        </td>
    </tr>
<?php $appointment_data->MoveNext();
    $i++; } ?>