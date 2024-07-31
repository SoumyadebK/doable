<?php
require_once('../../global/config.php');
global $db;
global $db_account;
global $master_database;

$STANDING_ID = $_GET['STANDING_ID'];
$PK_SPECIAL_APPOINTMENT = $_GET['PK_SPECIAL_APPOINTMENT'];

$SPECIAL_APPOINTMENT_QUERY = "SELECT
                                    DOA_SPECIAL_APPOINTMENT.*,
                                    DOA_APPOINTMENT_STATUS.APPOINTMENT_STATUS,
                                    DOA_APPOINTMENT_STATUS.STATUS_CODE,
                                    DOA_APPOINTMENT_STATUS.COLOR_CODE AS APPOINTMENT_COLOR,
                                    DOA_SCHEDULING_CODE.COLOR_CODE,
                                    DOA_SCHEDULING_CODE.DURATION,
                                    GROUP_CONCAT(DISTINCT(CONCAT(SERVICE_PROVIDER.FIRST_NAME, ' ', SERVICE_PROVIDER.LAST_NAME)) SEPARATOR ', ') AS SERVICE_PROVIDER_NAME,
                                    GROUP_CONCAT(DISTINCT(CONCAT(CUSTOMER.FIRST_NAME, ' ', CUSTOMER.LAST_NAME)) SEPARATOR ', ') AS CUSTOMER_NAME
                                FROM
                                    `DOA_SPECIAL_APPOINTMENT`
                                LEFT JOIN DOA_SPECIAL_APPOINTMENT_USER ON DOA_SPECIAL_APPOINTMENT.PK_SPECIAL_APPOINTMENT = DOA_SPECIAL_APPOINTMENT_USER.PK_SPECIAL_APPOINTMENT
                                LEFT JOIN $master_database.DOA_USERS AS SERVICE_PROVIDER ON DOA_SPECIAL_APPOINTMENT_USER.PK_USER = SERVICE_PROVIDER.PK_USER
                                        
                                LEFT JOIN DOA_SPECIAL_APPOINTMENT_CUSTOMER ON DOA_SPECIAL_APPOINTMENT.PK_SPECIAL_APPOINTMENT = DOA_SPECIAL_APPOINTMENT_CUSTOMER.PK_SPECIAL_APPOINTMENT
                                LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_SPECIAL_APPOINTMENT_CUSTOMER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER
                                LEFT JOIN $master_database.DOA_USERS AS CUSTOMER ON DOA_USER_MASTER.PK_USER = CUSTOMER.PK_USER
                                        
                                LEFT JOIN DOA_MASTER.DOA_APPOINTMENT_STATUS AS DOA_APPOINTMENT_STATUS ON DOA_SPECIAL_APPOINTMENT.PK_APPOINTMENT_STATUS = DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS
                                LEFT JOIN DOA_SCHEDULING_CODE ON DOA_SCHEDULING_CODE.PK_SCHEDULING_CODE = DOA_SPECIAL_APPOINTMENT.PK_SCHEDULING_CODE
                                WHERE DOA_SPECIAL_APPOINTMENT.STANDING_ID = ".$STANDING_ID." AND DOA_SPECIAL_APPOINTMENT.PK_SPECIAL_APPOINTMENT != $PK_SPECIAL_APPOINTMENT
                                GROUP BY DOA_SPECIAL_APPOINTMENT_USER.PK_SPECIAL_APPOINTMENT
                                ORDER BY DOA_SPECIAL_APPOINTMENT.DATE ASC, DOA_SPECIAL_APPOINTMENT.START_TIME ASC"; ?>

<?php $i = 2;
$special_appointment_data = $db_account->Execute($SPECIAL_APPOINTMENT_QUERY);
while (!$special_appointment_data->EOF) { ?>
    <tr class="added_standing">
        <td style="text-align: end;"></td>
        <td><?=$special_appointment_data->fields['TITLE']?>
            <?php if ($special_appointment_data->fields['STANDING_ID'] > 0) { ?>
                <span style="font-weight: bold; color: #1B72B8">(S)</span>
            <?php } ?>
        </td>
        <td><?=$special_appointment_data->fields['SERVICE_PROVIDER_NAME']?></td>
        <td><?=$special_appointment_data->fields['CUSTOMER_NAME']?></td>
        <td><?=date('l', strtotime($special_appointment_data->fields['DATE']))?></td>
        <td><?=date('m/d/Y', strtotime($special_appointment_data->fields['DATE']))?></td>
        <td><?=date('h:i A', strtotime($special_appointment_data->fields['START_TIME']))." - ".date('h:i A', strtotime($special_appointment_data->fields['END_TIME']))?></td>
        <td style="color: <?=$special_appointment_data->fields['APPOINTMENT_COLOR']?>"><?=$special_appointment_data->fields['APPOINTMENT_STATUS']?></td>
        <td>
            <a href="edit_to_do.php?id=<?=$special_appointment_data->fields['PK_SPECIAL_APPOINTMENT']?>" title="Edit"><i class="ti-pencil" style="font-size: 20px;"></i></a>&nbsp;&nbsp;&nbsp;
            <a href="javascript:" onclick='ConfirmDelete(<?=$special_appointment_data->fields['PK_SPECIAL_APPOINTMENT']?>);' title="Delete"><i class="fa fa-trash" style="font-size: 16px;"></i></a>&nbsp;&nbsp;&nbsp;
        </td>
    </tr>
<?php
$special_appointment_data->MoveNext();
$i++; } ?>
