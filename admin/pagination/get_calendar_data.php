<?php
require_once('../../global/config.php');
global $db;
global $db_account;
global $master_database;

$OPEN_TIME = '00:00:00';
$CLOSE_TIME = '23:59:00';

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];

$utc_tz =  new DateTimeZone('UTC');
try {
    $start_dt = new DateTime($_POST['START_DATE'], $utc_tz);
    $end_dt = new DateTime($_POST['END_DATE'], $utc_tz);

    $START_DATE = $start_dt->format('Y-m-d');
    $END_DATE = $end_dt->format('Y-m-d');

    $APPOINTMENT_DATE_CONDITION = " AND DOA_APPOINTMENT_MASTER.DATE BETWEEN '$START_DATE' AND '$END_DATE' ";
    $SPL_APPOINTMENT_DATE_CONDITION = " AND DOA_SPECIAL_APPOINTMENT.DATE BETWEEN '$START_DATE' AND '$END_DATE' ";
    $EVENT_DATE_CONDITION = " AND DOA_EVENT.START_DATE BETWEEN '$START_DATE' AND '$END_DATE' ";
} catch (Exception $e) {
    $APPOINTMENT_DATE_CONDITION = '';
    $SPL_APPOINTMENT_DATE_CONDITION = '';
    $EVENT_DATE_CONDITION = '';
}

$appointment_status = empty($_POST['STATUS_CODE']) ? '1, 2, 3, 5, 7, 8' : $_POST['STATUS_CODE'];

$appointment_type = '';
$APPOINTMENT_TYPE_QUERY = " AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE IN ('NORMAL', 'AD-HOC', 'GROUP') ";
if (isset($_POST['APPOINTMENT_TYPE']) && $_POST['APPOINTMENT_TYPE'] != '') {
    $appointment_type = $_POST['APPOINTMENT_TYPE'];
    $APPOINTMENT_TYPE_QUERY = " AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE = '$appointment_type' ";
}

$SERVICE_PROVIDER_ID = ' ';
$APPOINTMENT_SERVICE_PROVIDER_ID = ' ';
if(isset($_POST['SERVICE_PROVIDER_ID']) && $_POST['SERVICE_PROVIDER_ID'] != ''){
    $service_providers = implode(',', $_POST['SERVICE_PROVIDER_ID']);
    $SERVICE_PROVIDER_ID = " AND DOA_USERS.PK_USER IN (".$service_providers.") ";
    $APPOINTMENT_SERVICE_PROVIDER_ID = " AND DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER IN (".$service_providers.") ";
}

$ALL_APPOINTMENT_QUERY = "SELECT
                            DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER,
                            DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_SERVICE,
                            DOA_APPOINTMENT_ENROLLMENT.PK_ENROLLMENT_SERVICE AS APT_ENR_SERVICE,
                            DOA_APPOINTMENT_MASTER.GROUP_NAME,
                            DOA_APPOINTMENT_MASTER.SERIAL_NUMBER,
                            DOA_APPOINTMENT_MASTER.DATE,
                            DOA_APPOINTMENT_MASTER.START_TIME,
                            DOA_APPOINTMENT_MASTER.END_TIME,
                            DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE,
                            DOA_APPOINTMENT_MASTER.IS_PAID,
                            DOA_APPOINTMENT_MASTER.COMMENT,
                            DOA_APPOINTMENT_MASTER.INTERNAL_COMMENT,
                            DOA_ENROLLMENT_MASTER.ENROLLMENT_ID,
                            DOA_SERVICE_MASTER.SERVICE_NAME,
                            DOA_SERVICE_CODE.SERVICE_CODE,
                            DOA_APPOINTMENT_MASTER.IS_PAID,
                            DOA_APPOINTMENT_STATUS.STATUS_CODE,
                            DOA_APPOINTMENT_STATUS.APPOINTMENT_STATUS,
                            DOA_APPOINTMENT_STATUS.COLOR_CODE AS APPOINTMENT_COLOR,
                            DOA_SCHEDULING_CODE.COLOR_CODE,
                            DOA_SCHEDULING_CODE.SCHEDULING_CODE,
                            DOA_SCHEDULING_CODE.DURATION,
                            GROUP_CONCAT(DISTINCT(DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER) SEPARATOR ',') AS SERVICE_PROVIDER_ID,
                            GROUP_CONCAT(CONCAT(CUSTOMER.FIRST_NAME, ' ', CUSTOMER.LAST_NAME) SEPARATOR ',') AS CUSTOMER_NAME
                        FROM
                            DOA_APPOINTMENT_MASTER
                        LEFT JOIN DOA_APPOINTMENT_CUSTOMER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER
                        LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_APPOINTMENT_CUSTOMER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER
                        LEFT JOIN $master_database.DOA_USERS AS CUSTOMER ON DOA_USER_MASTER.PK_USER = CUSTOMER.PK_USER
                                
                        LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER
                                
                        LEFT JOIN DOA_APPOINTMENT_ENROLLMENT ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_ENROLLMENT.PK_APPOINTMENT_MASTER AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE = 'GROUP'
                        LEFT JOIN DOA_ENROLLMENT_MASTER AS APT_ENR ON DOA_APPOINTMENT_ENROLLMENT.PK_ENROLLMENT_MASTER = APT_ENR.PK_ENROLLMENT_MASTER AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE = 'GROUP'
                        
                        LEFT JOIN DOA_SCHEDULING_CODE ON DOA_APPOINTMENT_MASTER.PK_SCHEDULING_CODE = DOA_SCHEDULING_CODE.PK_SCHEDULING_CODE
                        LEFT JOIN DOA_SERVICE_MASTER ON DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER
                        LEFT JOIN $master_database.DOA_APPOINTMENT_STATUS AS DOA_APPOINTMENT_STATUS ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS = DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS 
                        LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER
                        LEFT JOIN DOA_SERVICE_CODE ON DOA_APPOINTMENT_MASTER.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE
                        WHERE DOA_APPOINTMENT_MASTER.PK_LOCATION IN ($DEFAULT_LOCATION_ID)
                        AND DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS IN ($appointment_status)
                        ".$APPOINTMENT_DATE_CONDITION."
                        ".$APPOINTMENT_TYPE_QUERY." 
                        AND DOA_APPOINTMENT_MASTER.STATUS = 'A' ".$APPOINTMENT_SERVICE_PROVIDER_ID."
                        GROUP BY DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER
                        ORDER BY DOA_APPOINTMENT_MASTER.DATE DESC, DOA_APPOINTMENT_MASTER.START_TIME DESC";

$SPECIAL_APPOINTMENT_QUERY = "SELECT
                                    DOA_SPECIAL_APPOINTMENT.*,
                                    DOA_APPOINTMENT_STATUS.STATUS_CODE,
                                    DOA_APPOINTMENT_STATUS.COLOR_CODE AS APPOINTMENT_COLOR,
                                    DOA_SCHEDULING_CODE.COLOR_CODE,
                                    DOA_SCHEDULING_CODE.DURATION,
                                    GROUP_CONCAT(SERVICE_PROVIDER.PK_USER SEPARATOR ',') AS SERVICE_PROVIDER_ID
                                FROM
                                    `DOA_SPECIAL_APPOINTMENT`
                                LEFT JOIN DOA_SPECIAL_APPOINTMENT_USER ON DOA_SPECIAL_APPOINTMENT.PK_SPECIAL_APPOINTMENT = DOA_SPECIAL_APPOINTMENT_USER.PK_SPECIAL_APPOINTMENT
                                LEFT JOIN DOA_MASTER.DOA_USERS AS SERVICE_PROVIDER ON DOA_SPECIAL_APPOINTMENT_USER.PK_USER = SERVICE_PROVIDER.PK_USER
                                LEFT JOIN DOA_MASTER.DOA_APPOINTMENT_STATUS AS DOA_APPOINTMENT_STATUS ON DOA_SPECIAL_APPOINTMENT.PK_APPOINTMENT_STATUS = DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS
                                LEFT JOIN DOA_SCHEDULING_CODE ON DOA_SCHEDULING_CODE.PK_SCHEDULING_CODE = DOA_SPECIAL_APPOINTMENT.PK_SCHEDULING_CODE
                                WHERE DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS IN ($appointment_status)
                                AND DOA_SPECIAL_APPOINTMENT.PK_LOCATION IN ($DEFAULT_LOCATION_ID)
                                ".$SPL_APPOINTMENT_DATE_CONDITION."
                                GROUP BY DOA_SPECIAL_APPOINTMENT_USER.PK_SPECIAL_APPOINTMENT";

$EVENT_QUERY = "SELECT DISTINCT
                    DOA_EVENT.*,
                    DOA_EVENT_TYPE.EVENT_TYPE,
                    DOA_EVENT_TYPE.COLOR_CODE
                FROM
                    DOA_EVENT
                INNER JOIN DOA_EVENT_LOCATION ON DOA_EVENT.PK_EVENT = DOA_EVENT_LOCATION.PK_EVENT
                LEFT JOIN DOA_EVENT_TYPE ON DOA_EVENT.PK_EVENT_TYPE = DOA_EVENT_TYPE.PK_EVENT_TYPE
                WHERE DOA_EVENT.ACTIVE = 1 
                AND DOA_EVENT_LOCATION.PK_LOCATION IN ($DEFAULT_LOCATION_ID)
                ".$EVENT_DATE_CONDITION."
                ORDER BY DOA_EVENT.START_DATE DESC";

$appointment_array = [];
if ($appointment_type == 'NORMAL' || $appointment_type == 'GROUP' || $appointment_type == '') {
    $appointment_data = $db_account->Execute($ALL_APPOINTMENT_QUERY);
    $paid_session = 0;
    while (!$appointment_data->EOF) {
        $PK_ENROLLMENT_SERVICE = '';
        if ($appointment_data->fields['APPOINTMENT_TYPE'] === 'NORMAL' || $appointment_data->fields['APPOINTMENT_TYPE'] === 'AD-HOC') {
            $title = $appointment_data->fields['CUSTOMER_NAME'] . ' (' . $appointment_data->fields['SERVICE_NAME'] . '-' . $appointment_data->fields['SERVICE_CODE'] . ') ' . (($appointment_data->fields['ENROLLMENT_ID'] === 0) ? '(Ad-Hoc)' : $appointment_data->fields['ENROLLMENT_ID']) . ' - ' . $appointment_data->fields['SERIAL_NUMBER'] . (($appointment_data->fields['IS_PAID'] == 1) ? ' (Paid)' : ' (Unpaid)');
            $type = "appointment";
        } else {
            $title = (($appointment_data->fields['CUSTOMER_NAME'] !== null) ? (count(explode(',', $appointment_data->fields['CUSTOMER_NAME']))) : '0') . ' - ' . $appointment_data->fields['GROUP_NAME'] . ' - ' . $appointment_data->fields['SERVICE_NAME'] . ' - ' . $appointment_data->fields['SERVICE_CODE'];
            $type = "group_class";
        }
        if ($appointment_data->fields['APPOINTMENT_TYPE'] === 'NORMAL') {
            $PK_ENROLLMENT_SERVICE = $appointment_data->fields['PK_ENROLLMENT_SERVICE'];
        } elseif ($appointment_data->fields['APPOINTMENT_TYPE'] === 'GROUP') {
            $PK_ENROLLMENT_SERVICE = $appointment_data->fields['APT_ENR_SERVICE'];
        }
        $enr_service_data = $db_account->Execute("SELECT NUMBER_OF_SESSION, SESSION_CREATED, SESSION_COMPLETED FROM `DOA_ENROLLMENT_SERVICE` WHERE `PK_ENROLLMENT_SERVICE` = ".$PK_ENROLLMENT_SERVICE);
        if ($enr_service_data->RecordCount() > 0) {
            if (isset($service_code_array[$PK_ENROLLMENT_SERVICE])) {
                $service_code_array[$PK_ENROLLMENT_SERVICE] = $service_code_array[$PK_ENROLLMENT_SERVICE] - 1;
            } else {
                $service_code_array[$PK_ENROLLMENT_SERVICE] = $enr_service_data->fields['SESSION_CREATED'];
            }
        }
        $title .= ((isset($service_code_array[$PK_ENROLLMENT_SERVICE])) ? ' '.$service_code_array[$PK_ENROLLMENT_SERVICE].'/'.$enr_service_data->fields['NUMBER_OF_SESSION'] : '');
        $appointment_array[] = [
            'id' => $appointment_data->fields['PK_APPOINTMENT_MASTER'],
            'resourceIds' => explode(',', $appointment_data->fields['SERVICE_PROVIDER_ID']),
            'title' => $title,
            'start' => date("Y-m-d", strtotime($appointment_data->fields['DATE'])) . 'T' . date("H:i:s", strtotime($appointment_data->fields['START_TIME'])),
            'end' => date("Y-m-d", strtotime($appointment_data->fields['DATE'])) . 'T' . date("H:i:s", strtotime($appointment_data->fields['END_TIME'])),
            'color' => $appointment_data->fields['COLOR_CODE'],
            'type' => $type,
            'status' => $appointment_data->fields['STATUS_CODE'],
            'statusColor' => $appointment_data->fields['APPOINTMENT_COLOR'],
            'comment' => $appointment_data->fields['COMMENT'],
            'internal_comment' => $appointment_data->fields['INTERNAL_COMMENT'],
            'statusCode' => $appointment_data->fields['SCHEDULING_CODE'],
            'duration' => $appointment_data->fields['DURATION'],
        ];
        $appointment_data->MoveNext();
    }
}

if ($appointment_type == 'TO-DO' || $appointment_type == '') {
    $special_appointment_data = $db_account->Execute($SPECIAL_APPOINTMENT_QUERY);
    while (!$special_appointment_data->EOF) {
        $appointment_array[] = [
            'id' => $special_appointment_data->fields['PK_SPECIAL_APPOINTMENT'],
            'resourceIds' => explode(',', $special_appointment_data->fields['SERVICE_PROVIDER_ID']),
            'title' => $special_appointment_data->fields['TITLE'],
            'start' => date("Y-m-d", strtotime($special_appointment_data->fields['DATE'])) . 'T' . date("H:i:s", strtotime($special_appointment_data->fields['START_TIME'])),
            'end' => date("Y-m-d", strtotime($special_appointment_data->fields['DATE'])) . 'T' . date("H:i:s", strtotime($special_appointment_data->fields['END_TIME'])),
            'color' => $special_appointment_data->fields['COLOR_CODE'],
            'type' => 'special_appointment',
            'status' => $special_appointment_data->fields['STATUS_CODE'],
            'statusColor' => $special_appointment_data->fields['APPOINTMENT_COLOR'],
            'comment' => '',
            'internal_comment' => '',
            'statusCode' => '',
            'duration' => $special_appointment_data->fields['DURATION'],
        ];
        $special_appointment_data->MoveNext();
    }
}

if ($appointment_type == 'EVENT' || $appointment_type == '') {
    $service_provider_data = $db->Execute("SELECT DISTINCT DOA_USERS.PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME FROM DOA_USERS INNER JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER INNER JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER WHERE DOA_USER_ROLES.PK_ROLES = 5 AND ACTIVE = 1 AND DOA_USER_LOCATION.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") " . $SERVICE_PROVIDER_ID . " AND DOA_USERS.PK_ACCOUNT_MASTER = " . $_SESSION['PK_ACCOUNT_MASTER'] . " ORDER BY DISPLAY_ORDER");
    $resourceIdArray = [];
    while (!$service_provider_data->EOF) {
        $resourceIdArray[] = $service_provider_data->fields['PK_USER'];
        $service_provider_data->MoveNext();
    }

    $event_data = $db_account->Execute($EVENT_QUERY);
    while (!$event_data->EOF) {
        if (isset($event_data->fields['END_DATE']) && $event_data->fields['ALL_DAY'] == 1) {
            $END_DATE = date('Y-m-d', strtotime($event_data->fields['END_DATE'] . '+1 day'));
        } else {
            $END_DATE = ($event_data->fields['END_DATE'] == '0000-00-00') ? $event_data->fields['START_DATE'] : $event_data->fields['END_DATE'];
        }
        $END_TIME = ($event_data->fields['END_TIME'] == '00:00:00') ? $event_data->fields['START_TIME'] : $event_data->fields['END_TIME'];
        $open_close_time_diff = (strtotime($CLOSE_TIME) - strtotime($OPEN_TIME));
        $start_end_time_diff = strtotime($END_DATE . ' ' . $END_TIME) - strtotime($event_data->fields['START_DATE'] . ' ' . $event_data->fields['START_TIME']);

        $appointment_array[] = [
            'id' => $event_data->fields['PK_EVENT'],
            'resourceIds' => $resourceIdArray,
            'title' => $event_data->fields['HEADER'],
            'start' => date("Y-m-d", strtotime($event_data->fields['START_DATE'])) . 'T' . date("H:i:s", strtotime($event_data->fields['START_TIME'])),
            'end' => date("Y-m-d", strtotime($event_data->fields['END_DATE'])) . 'T' . date("H:i:s", strtotime($event_data->fields['END_TIME'])),
            'color' => $event_data->fields['COLOR_CODE'],
            'type' => 'event',
            'allDay' => (($event_data->fields['ALL_DAY'] == 1) ? 1 : (($start_end_time_diff >= $open_close_time_diff) ? 1 : 0)),
            'status' => '',
            'statusColor' => '',
            'comment' => '',
            'internal_comment' => '',
            'statusCode' => '',
        ];
        $event_data->MoveNext();
    }
}

echo json_encode($appointment_array);

