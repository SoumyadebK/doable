<?php
require_once('../../global/config.php');
global $db;
global $db_account;
global $master_database;

$OPEN_TIME = '00:00:00';
$CLOSE_TIME = '23:59:00';
$DAYS = 0;

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];
$LOCATION_ARRAY = explode(',', $DEFAULT_LOCATION_ID);

$SESSION_PK_USER_MASTER = $_SESSION['PK_USER_MASTER'];

$utc_tz =  new DateTimeZone('UTC');
try {
    $start_dt = new DateTime($_POST['START_DATE'], $utc_tz);
    $end_dt = new DateTime($_POST['END_DATE'], $utc_tz);

    $START_DATE = $start_dt->format('Y-m-d');
    $END_DATE = $end_dt->format('Y-m-d');

    $date_difference = date_diff(date_create($START_DATE), date_create($END_DATE));
    $DAYS = $date_difference->days;

    $APPOINTMENT_DATE_CONDITION = " AND DOA_APPOINTMENT_MASTER.DATE BETWEEN ''$START_DATE'' AND ''$END_DATE'' ";
    $SPL_APPOINTMENT_DATE_CONDITION = " AND DOA_SPECIAL_APPOINTMENT.DATE BETWEEN '$START_DATE' AND '$END_DATE' ";
    $EVENT_DATE_CONDITION = " AND DOA_EVENT.START_DATE BETWEEN '$START_DATE' AND '$END_DATE' ";
} catch (Exception $e) {
    $APPOINTMENT_DATE_CONDITION = '';
    $SPL_APPOINTMENT_DATE_CONDITION = '';
    $EVENT_DATE_CONDITION = '';
}

$appointment_status = empty($_POST['STATUS_CODE']) ? '1, 2, 3, 5, 7, 8' : $_POST['STATUS_CODE'];

$appointment_type = '';
$APPOINTMENT_TYPE_QUERY = " AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE IN (''NORMAL'', ''AD-HOC'', ''GROUP'', ''DEMO'') ";
if (isset($_POST['APPOINTMENT_TYPE']) && $_POST['APPOINTMENT_TYPE'] != '') {
    $appointment_type = $_POST['APPOINTMENT_TYPE'];
    $APPOINTMENT_TYPE_QUERY = " AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE = '$appointment_type' ";
}

$SERVICE_PROVIDER_ID = ' ';
$APPOINTMENT_SERVICE_PROVIDER_ID = ' ';
$SPECIAL_APPOINTMENT_SERVICE_PROVIDER_ID = ' ';
if (isset($_POST['SERVICE_PROVIDER_ID']) && $_POST['SERVICE_PROVIDER_ID'] != '') {
    $service_providers = implode(',', $_POST['SERVICE_PROVIDER_ID']);
    $SERVICE_PROVIDER_ID = " AND DOA_USERS.PK_USER IN (" . $service_providers . ") ";
    $SPECIAL_APPOINTMENT_SERVICE_PROVIDER_ID = " AND SERVICE_PROVIDER.PK_USER IN (" . $service_providers . ") ";
    $APPOINTMENT_SERVICE_PROVIDER_ID = " AND DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER IN (" . $service_providers . ") ";
}

$ALL_APPOINTMENT_QUERY = "CALL getCalendarAppointments('$DEFAULT_LOCATION_ID', '$appointment_status', '$APPOINTMENT_DATE_CONDITION', '$APPOINTMENT_TYPE_QUERY', '$APPOINTMENT_SERVICE_PROVIDER_ID')";

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
                                LEFT JOIN $master_database.DOA_USERS AS SERVICE_PROVIDER ON DOA_SPECIAL_APPOINTMENT_USER.PK_USER = SERVICE_PROVIDER.PK_USER
                                LEFT JOIN $master_database.DOA_APPOINTMENT_STATUS AS DOA_APPOINTMENT_STATUS ON DOA_SPECIAL_APPOINTMENT.PK_APPOINTMENT_STATUS = DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS
                                LEFT JOIN DOA_SCHEDULING_CODE ON DOA_SCHEDULING_CODE.PK_SCHEDULING_CODE = DOA_SPECIAL_APPOINTMENT.PK_SCHEDULING_CODE
                                WHERE DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS IN ($appointment_status)
                                AND DOA_SPECIAL_APPOINTMENT.PK_LOCATION IN ($DEFAULT_LOCATION_ID)
                                " . $SPL_APPOINTMENT_DATE_CONDITION . "
                                " . $SPECIAL_APPOINTMENT_SERVICE_PROVIDER_ID . "
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
                " . $EVENT_DATE_CONDITION . "
                ORDER BY DOA_EVENT.START_DATE DESC";

$appointment_array = [];

if ($DAYS === 1 && count($LOCATION_ARRAY) === 1) {
    $i = 0;
    $service_provider_data = $db->Execute("SELECT DISTINCT DOA_USERS.PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME FROM DOA_USERS INNER JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER INNER JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER WHERE DOA_USER_ROLES.PK_ROLES = 5 AND ACTIVE = 1 AND DOA_USER_LOCATION.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") " . $SERVICE_PROVIDER_ID . " AND DOA_USERS.PK_ACCOUNT_MASTER = " . $_SESSION['PK_ACCOUNT_MASTER'] . " ORDER BY DISPLAY_ORDER");
    while (!$service_provider_data->EOF) {
        $LOCATION_OPEN_TIME = '';
        $LOCATION_CLOSE_TIME = '';
        $USER_OPEN_TIME = '';
        $USER_CLOSE_TIME = '';

        $PK_USER = $service_provider_data->fields['PK_USER'];
        $PK_LOCATION = $DEFAULT_LOCATION_ID;

        $dayNumber1 = date('N', strtotime($START_DATE));
        $location_operational_hour = $db_account->Execute("SELECT OPEN_TIME, CLOSE_TIME FROM DOA_OPERATIONAL_HOUR WHERE DAY_NUMBER = '$dayNumber1' AND CLOSED = 0 AND PK_LOCATION = " . $PK_LOCATION);
        if ($location_operational_hour->RecordCount() > 0) {
            $LOCATION_OPEN_TIME = $location_operational_hour->fields['OPEN_TIME'];
            $LOCATION_CLOSE_TIME = $location_operational_hour->fields['CLOSE_TIME'];
        }

        $holiday_date = $db->Execute("SELECT * FROM DOA_LOCATION_HOLIDAY_LIST WHERE HOLIDAY_DATE = '$START_DATE' AND PK_LOCATION = " . $PK_LOCATION);
        if ($holiday_date->RecordCount() > 0) {
            while (!$holiday_date->EOF) {
                $appointment_array[] = [
                    'id' => $i++,
                    'resourceId' => $PK_USER,
                    'title' => 'Holiday (' . $holiday_date->fields['HOLIDAY_NAME'] . ')',
                    'start' => date("Y-m-d", strtotime($START_DATE)) . 'T' . date("H:i:s", strtotime($LOCATION_OPEN_TIME)),
                    'end' => date("Y-m-d", strtotime($START_DATE)) . 'T' . date("H:i:s", strtotime($LOCATION_CLOSE_TIME)),
                    'color' => 'gray',
                    'type' => 'not_available',
                    'comment' => '',
                    'internal_comment' => '',
                    'statusCode' => '',
                    'duration' => '',
                ];
                $holiday_date->MoveNext();
            }
        } else {
            $user_operational_hour = $db_account->Execute("SELECT * FROM `DOA_SERVICE_PROVIDER_LOCATION_HOURS` WHERE PK_USER = '$PK_USER' AND PK_LOCATION = " . $PK_LOCATION);
            if ($user_operational_hour->RecordCount() > 0) {
                switch ((int)$dayNumber1) {
                    case 1:
                        $USER_OPEN_TIME = $user_operational_hour->fields['MON_START_TIME'];
                        $USER_CLOSE_TIME = $user_operational_hour->fields['MON_END_TIME'];
                        break;
                    case 2:
                        $USER_OPEN_TIME = $user_operational_hour->fields['TUE_START_TIME'];
                        $USER_CLOSE_TIME = $user_operational_hour->fields['TUE_END_TIME'];
                        break;
                    case 3:
                        $USER_OPEN_TIME = $user_operational_hour->fields['WED_START_TIME'];
                        $USER_CLOSE_TIME = $user_operational_hour->fields['WED_END_TIME'];
                        break;
                    case 4:
                        $USER_OPEN_TIME = $user_operational_hour->fields['THU_START_TIME'];
                        $USER_CLOSE_TIME = $user_operational_hour->fields['THU_END_TIME'];
                        break;
                    case 5:
                        $USER_OPEN_TIME = $user_operational_hour->fields['FRI_START_TIME'];
                        $USER_CLOSE_TIME = $user_operational_hour->fields['FRI_END_TIME'];
                        break;
                    case 6:
                        $USER_OPEN_TIME = $user_operational_hour->fields['SAT_START_TIME'];
                        $USER_CLOSE_TIME = $user_operational_hour->fields['SAT_END_TIME'];
                        break;
                    case 7:
                        $USER_OPEN_TIME = $user_operational_hour->fields['SUN_START_TIME'];
                        $USER_CLOSE_TIME = $user_operational_hour->fields['SUN_END_TIME'];
                        break;
                }
            }

            if ($USER_OPEN_TIME == '00:00:00' && $USER_CLOSE_TIME == '00:00:00') {
                $appointment_array[] = [
                    'id' => $i++,
                    'resourceId' => $PK_USER,
                    'title' => 'Not Available (Holiday)',
                    'start' => date("Y-m-d", strtotime($START_DATE)) . 'T' . date("H:i:s", strtotime($LOCATION_OPEN_TIME)),
                    'end' => date("Y-m-d", strtotime($START_DATE)) . 'T' . date("H:i:s", strtotime($LOCATION_CLOSE_TIME)),
                    'color' => 'gray',
                    'type' => 'not_available',
                    /*'status' => $special_appointment_data->fields['STATUS_CODE'],
                'statusColor' => $special_appointment_data->fields['APPOINTMENT_COLOR'],*/
                    'comment' => '',
                    'internal_comment' => '',
                    'statusCode' => '',
                    'duration' => '',
                ];
            } else {
                if (($LOCATION_OPEN_TIME < $USER_OPEN_TIME) && ($USER_OPEN_TIME != '00:00:00' && $USER_OPEN_TIME != '')) {
                    $appointment_array[] = [
                        'id' => $i++,
                        'resourceId' => $PK_USER,
                        'title' => 'Not Available',
                        'start' => date("Y-m-d", strtotime($START_DATE)) . 'T' . date("H:i:s", strtotime($LOCATION_OPEN_TIME)),
                        'end' => date("Y-m-d", strtotime($START_DATE)) . 'T' . date("H:i:s", strtotime($USER_OPEN_TIME)),
                        'color' => 'gray',
                        'type' => 'not_available',
                        /*'status' => $special_appointment_data->fields['STATUS_CODE'],
                'statusColor' => $special_appointment_data->fields['APPOINTMENT_COLOR'],*/
                        'comment' => '',
                        'internal_comment' => '',
                        'statusCode' => '',
                        'duration' => '',
                    ];
                }

                if (($LOCATION_CLOSE_TIME > $USER_CLOSE_TIME) && ($USER_CLOSE_TIME != '00:00:00' && $USER_CLOSE_TIME != '')) {
                    $appointment_array[] = [
                        'id' => $i++,
                        'resourceId' => $PK_USER,
                        'title' => 'Not Available',
                        'start' => date("Y-m-d", strtotime($START_DATE)) . 'T' . date("H:i:s", strtotime($USER_CLOSE_TIME)),
                        'end' => date("Y-m-d", strtotime($START_DATE)) . 'T' . date("H:i:s", strtotime($LOCATION_CLOSE_TIME)),
                        'color' => 'gray',
                        'type' => 'not_available',
                        /*'status' => $special_appointment_data->fields['STATUS_CODE'],
                'statusColor' => $special_appointment_data->fields['APPOINTMENT_COLOR'],*/
                        'comment' => '',
                        'internal_comment' => '',
                        'statusCode' => '',
                        'duration' => '',
                    ];
                }
            }
        }

        $service_provider_data->MoveNext();
    }
}

if ($appointment_type == 'TO-DO' || $appointment_type == '') {
    $special_appointment_data = $db_account->Execute($SPECIAL_APPOINTMENT_QUERY);
    while (!$special_appointment_data->EOF) {
        preg_match_all("/\\((.*?)\\)/", $special_appointment_data->fields['TITLE'], $statusCode);
        $appointment_array[] = [
            'id' => $special_appointment_data->fields['PK_SPECIAL_APPOINTMENT'],
            'resourceIds' => explode(',', $special_appointment_data->fields['SERVICE_PROVIDER_ID']),
            'title' => '',
            'start' => date("Y-m-d", strtotime($special_appointment_data->fields['DATE'])) . 'T' . date("H:i:s", strtotime($special_appointment_data->fields['START_TIME'])),
            'end' => date("Y-m-d", strtotime($special_appointment_data->fields['DATE'])) . 'T' . date("H:i:s", strtotime($special_appointment_data->fields['END_TIME'])),
            'color' => 'gray',
            'type' => 'not_available',
            /*'status' => $special_appointment_data->fields['STATUS_CODE'],
            'statusColor' => $special_appointment_data->fields['APPOINTMENT_COLOR'],*/
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
            'title' => '',
            'start' => date("Y-m-d", strtotime($event_data->fields['START_DATE'])) . 'T' . date("H:i:s", strtotime($event_data->fields['START_TIME'])),
            'end' => date("Y-m-d", strtotime($event_data->fields['END_DATE'])) . 'T' . date("H:i:s", strtotime($event_data->fields['END_TIME'])),
            'color' => 'gray',
            'type' => 'not_available',
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

if ($appointment_type == 'NORMAL' || $appointment_type == 'GROUP' || $appointment_type == '') {
    $appointment_data = $db_account->Execute($ALL_APPOINTMENT_QUERY);

    while ($conn_account->more_results() && $conn_account->next_result()) {
        $result = $conn_account->use_result();
        if ($result instanceof mysqli_result) {
            $result->free();
        }
    }

    $paid_session = 0;
    $service_code_array = [];
    while (!$appointment_data->EOF) {
        $PK_ENROLLMENT_SERVICE = '';
        $SERIAL_NUMBER = 0;
        $PK_USER_MASTER = $appointment_data->fields['PK_USER_MASTER'];
        $PK_APPOINTMENT_MASTER = $appointment_data->fields['PK_APPOINTMENT_MASTER'];

        if (($SESSION_PK_USER_MASTER == $PK_USER_MASTER) || ($appointment_data->fields['APPOINTMENT_TYPE'] == 'GROUP')) {
            $customerName = $appointment_data->fields['CUSTOMER_NAME'];
            $partnerName = '';

            if ($appointment_data->fields['APPOINTMENT_TYPE'] != 'GROUP') {
                $customer_details = $db_account->Execute("SELECT * FROM `DOA_CUSTOMER_DETAILS` WHERE `PK_USER_MASTER` = '$PK_USER_MASTER'");
                $ATTENDING_WITH = '';
                $PARTNER_FIRST_NAME = '';
                $PARTNER_LAST_NAME = '';
                if ($customer_details->RecordCount() > 0) {
                    $ATTENDING_WITH = $customer_details->fields['ATTENDING_WITH'];
                    $PARTNER_FIRST_NAME = $customer_details->fields['PARTNER_FIRST_NAME'];
                    $PARTNER_LAST_NAME = $customer_details->fields['PARTNER_LAST_NAME'];
                }
                if ($ATTENDING_WITH == 'With a Partner') {
                    $customerName .= ' & ' . $PARTNER_FIRST_NAME . ' ' . $PARTNER_LAST_NAME;
                }
            }

            $type = "appointment";
            if ($appointment_data->fields['APPOINTMENT_TYPE'] === 'NORMAL') {
                $PK_ENROLLMENT_SERVICE = $appointment_data->fields['PK_ENROLLMENT_SERVICE'];
                $SERIAL_NUMBER = $appointment_data->fields['SERIAL_NUMBER'];
            } elseif ($appointment_data->fields['APPOINTMENT_TYPE'] === 'GROUP') {
                $type = "group_class";
                $customerNameArray = [];
                $partnerNameArray = [];
                $PK_ENROLLMENT_SERVICE = $appointment_data->fields['APT_ENR_SERVICE'];
                $selected_customer = $db_account->Execute("SELECT * FROM DOA_APPOINTMENT_CUSTOMER WHERE PK_APPOINTMENT_MASTER = " . $PK_APPOINTMENT_MASTER);
                while (!$selected_customer->EOF) {
                    if ($selected_customer->fields['IS_PARTNER'] == 0) {
                        $user_data = $db->Execute("SELECT DOA_USERS.PK_USER, DOA_USER_MASTER.PK_USER_MASTER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER WHERE DOA_USER_MASTER.PK_USER_MASTER = " . $selected_customer->fields['PK_USER_MASTER']);
                        $customerNameArray[] = $user_data->fields['NAME'];
                    } elseif ($selected_customer->fields['IS_PARTNER'] == 1) {
                        $partner_data = $db_account->Execute("SELECT * FROM `DOA_CUSTOMER_DETAILS` WHERE `PK_USER_MASTER` = " . $selected_customer->fields['PK_USER_MASTER']);
                        $customerNameArray[] = $partner_data->fields['PARTNER_FIRST_NAME'] . ' ' . $partner_data->fields['PARTNER_LAST_NAME'];
                    }
                    $selected_customer->MoveNext();
                }
                $customerName = implode(', ', $customerNameArray);
            }

            $appointment_number = '';
            $paid_status = '';
            $title = ' || ';

            $appointment_array[] = [
                'id' => $PK_APPOINTMENT_MASTER,
                'resourceIds' => explode(',', $appointment_data->fields['SERVICE_PROVIDER_ID']),
                'customerName' => $customerName,
                'title' => $title,
                'appointment_number' => $appointment_number,
                'paid_status' => $paid_status,
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
        } else {
            $appointment_array[] = [
                'id' => $PK_APPOINTMENT_MASTER,
                'resourceId' => explode(',', $appointment_data->fields['SERVICE_PROVIDER_ID']),
                'title' => '',
                'start' => date("Y-m-d", strtotime($appointment_data->fields['DATE'])) . 'T' . date("H:i:s", strtotime($appointment_data->fields['START_TIME'])),
                'end' => date("Y-m-d", strtotime($appointment_data->fields['DATE'])) . 'T' . date("H:i:s", strtotime($appointment_data->fields['END_TIME'])),
                'color' => 'gray',
                'type' => 'not_available',
                'comment' => '',
                'internal_comment' => '',
                'statusCode' => '',
                'duration' => '',
            ];
        }


        $appointment_data->MoveNext();
    }
}

echo json_encode($appointment_array);
