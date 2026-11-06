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

// SECURITY: STATUS_CODE, APPOINTMENT_TYPE and SERVICE_PROVIDER_ID came straight
// from $_POST into SQL. Status is cast to int, type is whitelisted, ids are ints.
$appointment_status = empty($_POST['STATUS_CODE']) ? '1, 2, 3, 5, 7, 8' : (string) (int) $_POST['STATUS_CODE'];

$appointment_type = '';
$APPOINTMENT_TYPE_QUERY = " AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE IN (''NORMAL'', ''AD-HOC'', ''GROUP'', ''DEMO'') ";
$ALLOWED_TYPES = ['NORMAL', 'AD-HOC', 'GROUP', 'DEMO', 'TO-DO', 'EVENT'];
if (isset($_POST['APPOINTMENT_TYPE']) && in_array($_POST['APPOINTMENT_TYPE'], $ALLOWED_TYPES, true)) {
    $appointment_type = $_POST['APPOINTMENT_TYPE'];
    $APPOINTMENT_TYPE_QUERY = " AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE = \"$appointment_type\" ";
}

$SERVICE_PROVIDER_ID = ' ';
$APPOINTMENT_SERVICE_PROVIDER_ID = ' ';
$SPECIAL_APPOINTMENT_SERVICE_PROVIDER_ID = ' ';
if (!empty($_POST['SERVICE_PROVIDER_ID']) && is_array($_POST['SERVICE_PROVIDER_ID'])) {
    $service_providers = implode(',', array_filter(array_map('intval', $_POST['SERVICE_PROVIDER_ID'])));
    if ($service_providers !== '') {
        $SERVICE_PROVIDER_ID = " AND DOA_USERS.PK_USER IN (" . $service_providers . ") ";
        $SPECIAL_APPOINTMENT_SERVICE_PROVIDER_ID = " AND SERVICE_PROVIDER.PK_USER IN (" . $service_providers . ") ";
        $APPOINTMENT_SERVICE_PROVIDER_ID = " AND DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER IN (" . $service_providers . ") ";
    }
}

// Helper: turn a list of ids into a safe "1,2,3" string for IN (...)
function calendarIdList(array $ids)
{
    $ids = array_unique(array_filter(array_map('intval', $ids)));
    return implode(',', $ids);
}

//pre_r($APPOINTMENT_SERVICE_PROVIDER_ID);

/* $ALL_APPOINTMENT_QUERY = "SELECT
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
                            DOA_APPOINTMENT_CUSTOMER.PK_USER_MASTER,
                            DOA_ENROLLMENT_MASTER.ENROLLMENT_ID,
                            DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER,
                            DOA_SERVICE_MASTER.SERVICE_NAME,
                            DOA_SERVICE_CODE.SERVICE_CODE,
                            DOA_APPOINTMENT_MASTER.IS_PAID,
                            DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS,
                            DOA_APPOINTMENT_STATUS.STATUS_CODE,
                            DOA_APPOINTMENT_STATUS.APPOINTMENT_STATUS,
                            DOA_APPOINTMENT_STATUS.COLOR_CODE AS APPOINTMENT_COLOR,
                            DOA_SCHEDULING_CODE.COLOR_CODE,
                            DOA_SCHEDULING_CODE.SCHEDULING_CODE,
                            DOA_SCHEDULING_CODE.DURATION,
                            DOA_SCHEDULING_CODE.UNIT,
                            GROUP_CONCAT(DISTINCT(DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER) SEPARATOR ',') AS SERVICE_PROVIDER_ID,
                            GROUP_CONCAT(DISTINCT(CONCAT(CUSTOMER.FIRST_NAME, ' ', CUSTOMER.LAST_NAME)) SEPARATOR ', ') AS CUSTOMER_NAME,
                            DOA_PACKAGE.PACKAGE_NAME
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
                        LEFT JOIN DOA_PACKAGE ON DOA_ENROLLMENT_MASTER.PK_PACKAGE = DOA_PACKAGE.PK_PACKAGE 
                        WHERE (CUSTOMER.IS_DELETED = 0 OR CUSTOMER.IS_DELETED IS null) 
                        AND DOA_APPOINTMENT_MASTER.PK_LOCATION IN ($DEFAULT_LOCATION_ID)
                        AND DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS IN ($appointment_status)
                        " . $APPOINTMENT_DATE_CONDITION . "
                        " . $APPOINTMENT_TYPE_QUERY . " 
                        AND DOA_APPOINTMENT_MASTER.STATUS = 'A' " . $APPOINTMENT_SERVICE_PROVIDER_ID . "
                        GROUP BY DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER
                        ORDER BY DOA_APPOINTMENT_MASTER.DATE DESC, DOA_APPOINTMENT_MASTER.START_TIME DESC"; */

$ALL_APPOINTMENT_QUERY = "CALL getCalendarAppointments('$DEFAULT_LOCATION_ID', '$appointment_status', '$APPOINTMENT_DATE_CONDITION', '$APPOINTMENT_TYPE_QUERY', '$APPOINTMENT_SERVICE_PROVIDER_ID')";

$SPECIAL_APPOINTMENT_QUERY = "SELECT
                                    DOA_SPECIAL_APPOINTMENT.*,
                                    DOA_APPOINTMENT_STATUS.STATUS_CODE,
                                    DOA_APPOINTMENT_STATUS.COLOR_CODE AS APPOINTMENT_COLOR,
                                    DOA_SCHEDULING_CODE.SCHEDULING_NAME,
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
    $PK_LOCATION = (int) $DEFAULT_LOCATION_ID;
    $dayNumber1 = date('N', strtotime($START_DATE));

    // PERF: location hours and holidays don't depend on the provider, so they are
    // now fetched ONCE instead of once per provider inside the loop.
    $LOCATION_OPEN_TIME = '';
    $LOCATION_CLOSE_TIME = '';
    $location_operational_hour = $db_account->Execute("SELECT OPEN_TIME, CLOSE_TIME FROM DOA_OPERATIONAL_HOUR WHERE DAY_NUMBER = '$dayNumber1' AND CLOSED = 0 AND PK_LOCATION = " . $PK_LOCATION);
    if ($location_operational_hour->RecordCount() > 0) {
        $LOCATION_OPEN_TIME = $location_operational_hour->fields['OPEN_TIME'];
        $LOCATION_CLOSE_TIME = $location_operational_hour->fields['CLOSE_TIME'];
    }

    $holiday_rows = [];
    $holiday_date = $db->Execute("SELECT * FROM DOA_LOCATION_HOLIDAY_LIST WHERE HOLIDAY_DATE = '$START_DATE' AND PK_LOCATION = " . $PK_LOCATION);
    while (!$holiday_date->EOF) {
        $holiday_rows[] = $holiday_date->fields;
        $holiday_date->MoveNext();
    }

    $service_provider_rows = [];
    $service_provider_data = $db->Execute("SELECT DISTINCT DOA_USERS.PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME FROM DOA_USERS INNER JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER INNER JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER WHERE DOA_USER_ROLES.PK_ROLES = 5 AND ACTIVE = 1 AND DOA_USER_LOCATION.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") " . $SERVICE_PROVIDER_ID . " AND DOA_USERS.PK_ACCOUNT_MASTER = " . (int) $_SESSION['PK_ACCOUNT_MASTER'] . " ORDER BY DISPLAY_ORDER");
    while (!$service_provider_data->EOF) {
        $service_provider_rows[] = $service_provider_data->fields;
        $service_provider_data->MoveNext();
    }

    // PERF: all providers' working hours in ONE query (was one query per provider).
    $user_hours_by_user = [];
    $provider_id_list = calendarIdList(array_column($service_provider_rows, 'PK_USER'));
    if (empty($holiday_rows) && $provider_id_list !== '') {
        $user_operational_hour = $db_account->Execute("SELECT * FROM `DOA_SERVICE_PROVIDER_LOCATION_HOURS` WHERE PK_LOCATION = " . $PK_LOCATION . " AND PK_USER IN (" . $provider_id_list . ")");
        while (!$user_operational_hour->EOF) {
            $uid = $user_operational_hour->fields['PK_USER'];
            if (!isset($user_hours_by_user[$uid])) { // keep first row, like before
                $user_hours_by_user[$uid] = $user_operational_hour->fields;
            }
            $user_operational_hour->MoveNext();
        }
    }

    foreach ($service_provider_rows as $service_provider_fields) {
        $USER_OPEN_TIME = '';
        $USER_CLOSE_TIME = '';

        $PK_USER = $service_provider_fields['PK_USER'];

        if (!empty($holiday_rows)) {
            foreach ($holiday_rows as $holiday_fields) {
                $appointment_array[] = [
                    'id' => $i++,
                    'resourceId' => $PK_USER,
                    'title' => 'Holiday (' . $holiday_fields['HOLIDAY_NAME'] . ')',
                    'start' => date("Y-m-d", strtotime($START_DATE)) . 'T' . date("H:i:s", strtotime($LOCATION_OPEN_TIME)),
                    'end' => date("Y-m-d", strtotime($START_DATE)) . 'T' . date("H:i:s", strtotime($LOCATION_CLOSE_TIME)),
                    'color' => 'gray',
                    'type' => 'not_available',
                    'comment' => '',
                    'internal_comment' => '',
                    'statusCode' => '',
                    'duration' => '',
                ];
            }
        } else {
            if (isset($user_hours_by_user[$PK_USER])) {
                $uh = $user_hours_by_user[$PK_USER];
                switch ((int)$dayNumber1) {
                    case 1:
                        $USER_OPEN_TIME = $uh['MON_START_TIME'];
                        $USER_CLOSE_TIME = $uh['MON_END_TIME'];
                        break;
                    case 2:
                        $USER_OPEN_TIME = $uh['TUE_START_TIME'];
                        $USER_CLOSE_TIME = $uh['TUE_END_TIME'];
                        break;
                    case 3:
                        $USER_OPEN_TIME = $uh['WED_START_TIME'];
                        $USER_CLOSE_TIME = $uh['WED_END_TIME'];
                        break;
                    case 4:
                        $USER_OPEN_TIME = $uh['THU_START_TIME'];
                        $USER_CLOSE_TIME = $uh['THU_END_TIME'];
                        break;
                    case 5:
                        $USER_OPEN_TIME = $uh['FRI_START_TIME'];
                        $USER_CLOSE_TIME = $uh['FRI_END_TIME'];
                        break;
                    case 6:
                        $USER_OPEN_TIME = $uh['SAT_START_TIME'];
                        $USER_CLOSE_TIME = $uh['SAT_END_TIME'];
                        break;
                    case 7:
                        $USER_OPEN_TIME = $uh['SUN_START_TIME'];
                        $USER_CLOSE_TIME = $uh['SUN_END_TIME'];
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
    }
}

if ($appointment_type == 'TO-DO' || $appointment_type == '') {
    $special_appointment_data = $db_account->Execute($SPECIAL_APPOINTMENT_QUERY);
    while (!$special_appointment_data->EOF) {
        if ($special_appointment_data->fields['ALL_DAY'] == 1) {
            $allDay = true;
            $start = date("Y-m-d", strtotime($special_appointment_data->fields['DATE']));
            $end = date("Y-m-d", strtotime($special_appointment_data->fields['DATE']));
        } else {
            $allDay = false;
            $start = date("Y-m-d", strtotime($special_appointment_data->fields['DATE'])) . 'T' . date("H:i:s", strtotime($special_appointment_data->fields['START_TIME']));
            $end = date("Y-m-d", strtotime($special_appointment_data->fields['DATE'])) . 'T' . date("H:i:s", strtotime($special_appointment_data->fields['END_TIME']));
        }
        preg_match_all("/\\((.*?)\\)/", $special_appointment_data->fields['TITLE'], $statusCode);
        $appointment_array[] = [
            'id' => $special_appointment_data->fields['PK_SPECIAL_APPOINTMENT'],
            'resourceIds' => explode(',', $special_appointment_data->fields['SERVICE_PROVIDER_ID']),
            'title' => ($special_appointment_data->fields['TITLE']) ? preg_replace("/\([^)]+\)/", "", $special_appointment_data->fields['TITLE']) : $special_appointment_data->fields['SCHEDULING_NAME'],
            'start' => $start,
            'end' => $end,
            'color' => $special_appointment_data->fields['COLOR_CODE'],
            'type' => 'special_appointment',
            'allDay' => $allDay,
            /*'status' => $special_appointment_data->fields['STATUS_CODE'],
            'statusColor' => $special_appointment_data->fields['APPOINTMENT_COLOR'],*/
            'comment' => $special_appointment_data->fields['DESCRIPTION'],
            'internal_comment' => '',
            'statusCode' => (isset($statusCode[1][0])) ? $statusCode[1][0] : '',
            'duration' => $special_appointment_data->fields['DURATION'],
        ];
        $special_appointment_data->MoveNext();
    }
}

if ($appointment_type == 'EVENT' || $appointment_type == '') {
    $service_provider_data = $db->Execute("SELECT DISTINCT
                                                DOA_USERS.PK_USER,
                                                CONCAT(
                                                    DOA_USERS.FIRST_NAME,
                                                    ' ',
                                                    DOA_USERS.LAST_NAME
                                                ) AS NAME,
                                                DOA_USERS.DISPLAY_ORDER
                                            FROM
                                                DOA_USERS
                                            INNER JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER
                                            WHERE DOA_USERS.APPEAR_IN_CALENDAR = 1 AND DOA_USERS.ACTIVE = 1 AND (DOA_USERS.IS_DELETED = 0 OR DOA_USERS.IS_DELETED IS NULL) AND DOA_USER_LOCATION.PK_LOCATION IN( " . $DEFAULT_LOCATION_ID . " )
                                            AND DOA_USERS.PK_ACCOUNT_MASTER = " . $_SESSION['PK_ACCOUNT_MASTER'] . "
                                            ORDER BY DOA_USERS.DISPLAY_ORDER ASC");
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
            'id' => (int) $event_data->fields['PK_EVENT'],
            'resourceIds' => $resourceIdArray,
            'title' => $event_data->fields['HEADER'],
            'start' => date("Y-m-d", strtotime($event_data->fields['START_DATE'])),
            'end' => date("Y-m-d", strtotime($event_data->fields['END_DATE'])),
            'color' => $event_data->fields['COLOR_CODE'],
            'type' => 'event',
            'allDay' => (($event_data->fields['ALL_DAY'] == 1) ? true : (($start_end_time_diff >= $open_close_time_diff) ? true : false)),
            'status' => '',
            'statusColor' => '',
            'comment' => '',
            'internal_comment' => '',
            'statusCode' => '',
        ];
        $event_data->MoveNext();
    }
}

if ($appointment_type == 'NORMAL' || $appointment_type == 'AD-HOC' || $appointment_type == 'GROUP' || $appointment_type == 'DEMO' || $appointment_type == '') {
    $appointment_data = $db_account->Execute($ALL_APPOINTMENT_QUERY);

    // PERF: read every appointment row first, so the extra lookups below can be
    // done in a few batched queries instead of several queries PER appointment.
    $appointment_rows = [];
    while (!$appointment_data->EOF) {
        $appointment_rows[] = $appointment_data->fields;
        $appointment_data->MoveNext();
    }

    while ($conn_account->more_results() && $conn_account->next_result()) {
        $result = $conn_account->use_result();
        if ($result instanceof mysqli_result) {
            $result->free();
        }
    }

    // ---- Collect the ids we need ------------------------------------------
    $details_user_masters = [];   // DOA_CUSTOMER_DETAILS lookups (customers + group partners)
    $group_appointment_ids = [];  // group classes -> customer list
    $enrollment_service_ids = []; // NORMAL appointments -> NUMBER_OF_SESSION
    foreach ($appointment_rows as $row) {
        if ($row['APPOINTMENT_TYPE'] == 'GROUP') {
            $group_appointment_ids[] = $row['PK_APPOINTMENT_MASTER'];
        } else {
            $details_user_masters[] = $row['PK_USER_MASTER'];
        }
        if ($row['APPOINTMENT_TYPE'] === 'NORMAL' && !empty($row['PK_ENROLLMENT_SERVICE'])) {
            $enrollment_service_ids[] = $row['PK_ENROLLMENT_SERVICE'];
        }
    }

    // ---- Group class customers: ONE query (was one per group class) -------
    $group_customers = [];          // PK_APPOINTMENT_MASTER => [ [PK_USER_MASTER, IS_PARTNER], ... ]
    $group_customer_user_masters = [];
    $id_list = calendarIdList($group_appointment_ids);
    if ($id_list !== '') {
        $rs = $db_account->Execute("SELECT PK_APPOINTMENT_MASTER, PK_USER_MASTER, IS_PARTNER FROM DOA_APPOINTMENT_CUSTOMER WHERE PK_APPOINTMENT_MASTER IN (" . $id_list . ")");
        while (!$rs->EOF) {
            $group_customers[$rs->fields['PK_APPOINTMENT_MASTER']][] = $rs->fields;
            if ($rs->fields['IS_PARTNER'] == 0) {
                $group_customer_user_masters[] = $rs->fields['PK_USER_MASTER'];
            } elseif ($rs->fields['IS_PARTNER'] == 1) {
                $details_user_masters[] = $rs->fields['PK_USER_MASTER'];
            }
            $rs->MoveNext();
        }
    }

    // ---- Group customer names: ONE query (was one per customer) -----------
    $user_master_names = [];
    $id_list = calendarIdList($group_customer_user_masters);
    if ($id_list !== '') {
        $rs = $db->Execute("SELECT DOA_USER_MASTER.PK_USER_MASTER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER WHERE DOA_USER_MASTER.PK_USER_MASTER IN (" . $id_list . ")");
        while (!$rs->EOF) {
            if (!isset($user_master_names[$rs->fields['PK_USER_MASTER']])) {
                $user_master_names[$rs->fields['PK_USER_MASTER']] = $rs->fields['NAME'];
            }
            $rs->MoveNext();
        }
    }

    // ---- Customer / partner details: ONE query (was one per appointment) --
    $customer_details_by_um = [];
    $id_list = calendarIdList($details_user_masters);
    if ($id_list !== '') {
        $rs = $db_account->Execute("SELECT PK_USER_MASTER, ATTENDING_WITH, PARTNER_FIRST_NAME, PARTNER_LAST_NAME FROM `DOA_CUSTOMER_DETAILS` WHERE `PK_USER_MASTER` IN (" . $id_list . ")");
        while (!$rs->EOF) {
            if (!isset($customer_details_by_um[$rs->fields['PK_USER_MASTER']])) { // first row, like before
                $customer_details_by_um[$rs->fields['PK_USER_MASTER']] = $rs->fields;
            }
            $rs->MoveNext();
        }
    }

    // ---- Sessions per enrollment service: ONE query -----------------------
    $number_of_sessions = [];
    $id_list = calendarIdList($enrollment_service_ids);
    if ($id_list !== '') {
        $rs = $db_account->Execute("SELECT PK_ENROLLMENT_SERVICE, NUMBER_OF_SESSION FROM `DOA_ENROLLMENT_SERVICE` WHERE `PK_ENROLLMENT_SERVICE` IN (" . $id_list . ")");
        while (!$rs->EOF) {
            $number_of_sessions[$rs->fields['PK_ENROLLMENT_SERVICE']] = $rs->fields['NUMBER_OF_SESSION'];
            $rs->MoveNext();
        }
    }

    // PERF: getPaidCount() only depends on the enrollment service, so cache it.
    // (Many appointments in a week share the same enrollment.)
    $paid_count_cache = [];

    foreach ($appointment_rows as $row) {
        $PK_ENROLLMENT_SERVICE = 0;
        $SERIAL_NUMBER = 0;

        $PK_APPOINTMENT_MASTER = $row['PK_APPOINTMENT_MASTER'];
        $PK_USER_MASTER = $row['PK_USER_MASTER'];
        $customerName = $row['CUSTOMER_NAME'];

        if ($row['APPOINTMENT_TYPE'] != 'GROUP') {
            $cd = $customer_details_by_um[$PK_USER_MASTER] ?? null;
            if ($cd && $cd['ATTENDING_WITH'] == 'With a Partner') {
                $customerName .= ' & ' . $cd['PARTNER_FIRST_NAME'] . ' ' . $cd['PARTNER_LAST_NAME'];
            }
        }

        if ($row['APPOINTMENT_TYPE'] === 'NORMAL') {
            $PK_ENROLLMENT_SERVICE = $row['PK_ENROLLMENT_SERVICE'];
            $SERIAL_NUMBER = $row['SERIAL_NUMBER'];
        } elseif ($row['APPOINTMENT_TYPE'] === 'GROUP') {
            $customerNameArray = [];
            $PK_ENROLLMENT_SERVICE = $row['APT_ENR_SERVICE'];
            foreach ($group_customers[$PK_APPOINTMENT_MASTER] ?? [] as $gc) {
                if ($gc['IS_PARTNER'] == 0) {
                    $customerNameArray[] = $user_master_names[$gc['PK_USER_MASTER']] ?? '';
                } elseif ($gc['IS_PARTNER'] == 1) {
                    $pd = $customer_details_by_um[$gc['PK_USER_MASTER']] ?? ['PARTNER_FIRST_NAME' => '', 'PARTNER_LAST_NAME' => ''];
                    $customerNameArray[] = $pd['PARTNER_FIRST_NAME'] . ' ' . $pd['PARTNER_LAST_NAME'];
                }
            }
            $customerName = implode(', ', $customerNameArray);
        }

        $paid_status = '';
        $appointment_number = '';
        // PERF: removed a getPaidCount() call here - its result was never used
        // (it was overwritten below), and for Ad-Hoc it ran with id 0.
        if ($row['APPOINTMENT_TYPE'] === 'NORMAL' || $row['APPOINTMENT_TYPE'] === 'AD-HOC') {
            $title = strtoupper($row['SERVICE_NAME'] . ', ' . $row['SERVICE_CODE'] . (($row['PK_ENROLLMENT_MASTER'] == 0) ? ' (Ad-Hoc)' : ''));
            $type = "appointment";
        } elseif ($row['APPOINTMENT_TYPE'] === 'DEMO') {
            $title = strtoupper($row['SERVICE_NAME'] . ', ' . $row['SERVICE_CODE']);
            $type = "appointment";
        } else {
            $group_name = (!empty($row['GROUP_NAME'])) ? $row['GROUP_NAME'] . ', ' : '';
            $title = strtoupper($group_name . $row['SERVICE_NAME'] . ' - ' . $row['SERVICE_CODE']);
            $type = "group_class";
        }

        if ($PK_ENROLLMENT_SERVICE != 0 && $row['APPOINTMENT_TYPE'] === 'NORMAL' && isset($number_of_sessions[$PK_ENROLLMENT_SERVICE])) {
            $appointment_position = getAppointmentPosition($PK_ENROLLMENT_SERVICE, $PK_APPOINTMENT_MASTER);
            if (!isset($paid_count_cache[$PK_ENROLLMENT_SERVICE])) {
                $paid_count_cache[$PK_ENROLLMENT_SERVICE] = getPaidCount($PK_ENROLLMENT_SERVICE);
            }
            $PAID_COUNT = $paid_count_cache[$PK_ENROLLMENT_SERVICE];
            $paid_status = (($appointment_position <= $PAID_COUNT) ? ' (' . ($PAID_COUNT - $appointment_position) . ' Paid)' : ' (Unpaid)');
            $appointment_number = ($appointment_position > 0) ? '  ' . ($appointment_position) . '/' . $number_of_sessions[$PK_ENROLLMENT_SERVICE] : '';
        }

        $appointment_array[] = [
            'id' => $PK_APPOINTMENT_MASTER,
            'resourceIds' => explode(',', $row['SERVICE_PROVIDER_ID']),
            'customerName' => $customerName,
            'title' => $title,
            'appointment_number' => $appointment_number,
            'paid_status' => $paid_status,
            'start' => date("Y-m-d", strtotime($row['DATE'])) . 'T' . date("H:i:s", strtotime($row['START_TIME'])),
            'end' => date("Y-m-d", strtotime($row['DATE'])) . 'T' . date("H:i:s", strtotime($row['END_TIME'])),
            'color' => $row['COLOR_CODE'],
            'type' => $type,
            'status' => $row['STATUS_CODE'],
            'statusColor' => $row['APPOINTMENT_COLOR'],
            'comment' => $row['COMMENT'],
            'internal_comment' => $row['INTERNAL_COMMENT'],
            'statusCode' => $row['SCHEDULING_CODE'],
            'duration' => $row['DURATION'],
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($appointment_array);
