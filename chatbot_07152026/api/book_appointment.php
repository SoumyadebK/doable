<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
//session_start();
require_once("../../global/config.php");

// Read JSON body
$input = json_decode(file_get_contents('php://input'), true);

$account_id  = $input['account']  ?? null;
$location_id = $input['location'] ?? null;
$date        = $input['date']     ?? null;
$slot        = $input['slot']     ?? null;
$name        = $input['name']     ?? null;
$phone       = $input['phone']    ?? null;
$email       = $input['email']    ?? null;

if (!$account_id) {
    $return_data['status'] = 'error';
    $return_data['message'] = 'Account ID is required.';
    echo json_encode($return_data);
    exit;
} else {
    $account_data = $db->Execute("SELECT * FROM DOA_ACCOUNT_MASTER WHERE ACTIVE = 1 AND PK_ACCOUNT_MASTER = " . $account_id);
    $location_data = $db->Execute("SELECT * FROM DOA_LOCATION WHERE ACTIVE = 1 AND PK_ACCOUNT_MASTER = " . $account_id . " AND LOCATION_CODE = '$location_id'");
    $PK_LOCATION = $location_data->fields['PK_LOCATION'] ?? null;

    $DB_NAME = $account_data->fields['DB_NAME'];
    $db_account = new queryFactory();
    if ($_SERVER['HTTP_HOST'] == 'localhost') {
        $conn1 = $db_account->connect('localhost', 'root', '', $DB_NAME);
        $http_path = 'http://localhost/doable/';
    } else {
        $conn1 = $db_account->connect('localhost', 'root', 'b54eawxj5h8ev', $DB_NAME);
        $http_path = 'https://doable.net/';
    }

    if ($db_account->error_number) {
        die("Connection Error");
    }

    [$PK_USER, $PK_USER_MASTER] = createUser($PK_LOCATION, $name, $phone, $email);
    $PK_APPOINTMENT_MASTER = createAppointment($account_id, $PK_LOCATION, $PK_USER_MASTER, $date, $slot);

    if ($account_data->RecordCount() == 0) {
        $return_data['status'] = 'error';
        $return_data['message'] = 'Account not found.';
        echo json_encode($return_data);
        exit;
    } else {
        $return_data['status'] = 'success';
        $return_data['data'] = 'Appointment booked successfully at ' . $slot . ' on ' . $date . ' for ' . $name . '.';
        echo json_encode($return_data);
    }
}


function createUser($PK_LOCATION, $name, $phone, $email)
{
    global $db;

    $locationData = $db->Execute("SELECT DOA_LOCATION.PK_LOCATION, DOA_LOCATION.LOCATION_NAME, DOA_LOCATION.PK_ACCOUNT_MASTER, DOA_LOCATION.HOUR, DOA_LOCATION.PK_TAG, DOA_ACCOUNT_MASTER.DB_NAME, DOA_TIMEZONE.TIMEZONE FROM DOA_LOCATION LEFT JOIN DOA_TIMEZONE ON DOA_LOCATION.PK_TIMEZONE = DOA_TIMEZONE.PK_TIMEZONE LEFT JOIN DOA_ACCOUNT_MASTER ON DOA_LOCATION.PK_ACCOUNT_MASTER = DOA_ACCOUNT_MASTER.PK_ACCOUNT_MASTER  WHERE DOA_LOCATION.PK_LOCATION = " . $PK_LOCATION . " LIMIT 1");
    $DB_NAME = $locationData->fields['DB_NAME'];
    $db_account = new queryFactory();
    if ($_SERVER['HTTP_HOST'] == 'localhost') {
        $conn1 = $db_account->connect('localhost', 'root', '', $DB_NAME);
        $http_path = 'http://localhost/doable/';
    } else {
        $conn1 = $db_account->connect('localhost', 'root', 'b54eawxj5h8ev', $DB_NAME);
        $http_path = 'https://doable.net/';
    }
    if ($db_account->error_number) {
        die("Connection Error");
    }

    $PK_ACCOUNT_MASTER = $locationData->fields['PK_ACCOUNT_MASTER'] ?? null;
    $PK_TAG = $locationData->fields['PK_TAG'] ?? null;
    $FIRST_NAME = explode(' ', $name)[0] ?? '';
    $LAST_NAME = explode(' ', $name)[1] ?? '';
    $EMAIL_ID = $email;
    $PHONE = $phone;
    $ADDRESS = '';

    $isUserExist = $db->Execute("SELECT PK_USER FROM DOA_USERS WHERE PK_ACCOUNT_MASTER = '$PK_ACCOUNT_MASTER' AND PHONE = '" . addslashes($PHONE) . "' OR EMAIL_ID = '" . addslashes($EMAIL_ID) . "' LIMIT 1");
    if ($isUserExist->RecordCount() > 0) {
        $PK_USER = $isUserExist->fields['PK_USER'];
        $USER_DATA_UPDATE = [];
        $USER_DATA_UPDATE['FIRST_NAME'] = $FIRST_NAME;
        $USER_DATA_UPDATE['LAST_NAME'] = $LAST_NAME;
        $USER_DATA_UPDATE['EMAIL_ID'] = $EMAIL_ID;
        $USER_DATA_UPDATE['PHONE'] = $PHONE;
        $USER_DATA_UPDATE['ADDRESS'] = $ADDRESS;
        $USER_DATA_UPDATE['ACTIVE'] = 1;
        $USER_DATA_UPDATE['IS_DELETED'] = 0;
        db_perform('DOA_USERS', $USER_DATA_UPDATE, 'update', " PK_USER = " . $PK_USER);

        $USER_MASTER_DATA['PK_USER'] = $PK_USER;
        $USER_MASTER_DATA['PK_ACCOUNT_MASTER'] = $PK_ACCOUNT_MASTER;
        $USER_MASTER_DATA['PRIMARY_LOCATION_ID'] = $PK_LOCATION;
        db_perform('DOA_USER_MASTER', $USER_MASTER_DATA, 'update', " PK_USER = " . $PK_USER);

        $CUSTOMER_LOCATION_DATA['PK_USER'] = $PK_USER;
        $CUSTOMER_LOCATION_DATA['PK_LOCATION'] = $PK_LOCATION;
        db_perform('DOA_USER_LOCATION', $CUSTOMER_LOCATION_DATA, 'update', " PK_USER = " . $PK_USER);

        $userMasterData = $db->Execute("SELECT PK_USER_MASTER FROM DOA_USER_MASTER WHERE PK_USER = " . $PK_USER . " LIMIT 1");
        return [$PK_USER, $userMasterData->fields['PK_USER_MASTER']];
    } else {
        $LEADS_DATA['PK_LOCATION'] = $PK_LOCATION;
        $LEADS_DATA['FIRST_NAME'] = $FIRST_NAME;
        $LEADS_DATA['LAST_NAME'] = $LAST_NAME;
        $LEADS_DATA['PHONE'] = $PHONE;
        $LEADS_DATA['EMAIL_ID'] = $EMAIL_ID;
        $LEADS_DATA['PK_LEAD_STATUS'] = getLeadStatusId('Scheduled', $PK_ACCOUNT_MASTER);
        $LEADS_DATA['DESCRIPTION'] = 'Created from AI Chatbot';
        $LEADS_DATA['OPPORTUNITY_SOURCE'] = 'AI Chatbot';
        $LEADS_DATA['REMOTE_ADDRESS'] = $_SERVER['REMOTE_ADDR'];
        $LEADS_DATA['IP_ADDRESS'] = getUserIP();
        $LEADS_DATA['ACTIVE'] = 1;
        $LEADS_DATA['CREATED_BY']  = $_SESSION['PK_USER'];
        $LEADS_DATA['CREATED_ON']  = date("Y-m-d H:i");
        db_perform('DOA_LEADS', $LEADS_DATA, 'insert');


        $USER_DATA['PK_ACCOUNT_MASTER'] = $USER_DATA_ACCOUNT['PK_ACCOUNT_MASTER'] = $PK_ACCOUNT_MASTER;
        $USER_DATA['FIRST_NAME'] = $USER_DATA_ACCOUNT['FIRST_NAME'] = $FIRST_NAME;
        $USER_DATA['LAST_NAME'] = $USER_DATA_ACCOUNT['LAST_NAME'] = $LAST_NAME;
        $USER_DATA['EMAIL_ID'] = $USER_DATA_ACCOUNT['EMAIL_ID'] = $EMAIL_ID;
        $USER_DATA['PHONE'] = $USER_DATA_ACCOUNT['PHONE'] = $PHONE;
        $USER_DATA['ADDRESS'] = $ADDRESS;
        $USER_DATA['CREATE_LOGIN'] = 0;
        $USER_DATA['APPEAR_IN_CALENDAR'] = 0;
        $USER_DATA['IS_DELETED'] = 0;

        $row = $db->Execute("SELECT UNIQUE_ID FROM DOA_USERS ORDER BY UNIQUE_ID DESC LIMIT 1");
        if ($row->RecordCount() > 0 && $row->fields['UNIQUE_ID'] > 0) {
            $USER_DATA['UNIQUE_ID']  =  intval($row->fields['UNIQUE_ID']) + 1;
        }

        $USER_DATA['JOINING_DATE'] = date("Y-m-d H:i");
        $USER_DATA['ACTIVE'] = $USER_DATA_ACCOUNT['ACCOUNT'] = 1;
        $USER_DATA['CREATED_BY']  = $USER_DATA_ACCOUNT['CREATED_BY'] = 0;
        $USER_DATA['CREATED_ON']  = date("Y-m-d H:i");
        db_perform('DOA_USERS', $USER_DATA, 'insert');
        $PK_USER = $db->insert_ID();

        $USER_DATA_ACCOUNT['PK_USER_MASTER_DB'] = $PK_USER;
        db_perform_account_own($db_account, 'DOA_USERS', $USER_DATA_ACCOUNT, 'insert');

        $USER_MASTER_DATA['PK_USER'] = $PK_USER;
        $USER_MASTER_DATA['PK_ACCOUNT_MASTER'] = $PK_ACCOUNT_MASTER;
        $USER_MASTER_DATA['PRIMARY_LOCATION_ID'] = $PK_LOCATION;
        $USER_MASTER_DATA['CREATED_BY'] = 0;
        $USER_MASTER_DATA['CREATED_ON'] = date("Y-m-d H:i");
        db_perform('DOA_USER_MASTER', $USER_MASTER_DATA, 'insert');
        $PK_USER_MASTER = $db->insert_ID();

        $CUSTOMER_USER_DATA['PK_USER_MASTER'] = $PK_USER_MASTER;
        $CUSTOMER_USER_DATA['IS_PRIMARY'] = 1;
        $CUSTOMER_USER_DATA['FIRST_NAME'] = $FIRST_NAME;
        $CUSTOMER_USER_DATA['LAST_NAME'] = $LAST_NAME;
        $CUSTOMER_USER_DATA['PHONE'] = $PHONE;
        $CUSTOMER_USER_DATA['EMAIL'] = $EMAIL_ID;
        db_perform_account_own($db_account, 'DOA_CUSTOMER_DETAILS', $CUSTOMER_USER_DATA, 'insert');
        $PK_CUSTOMER_DETAILS = $db_account->insert_ID();

        $TAG_DATA['PK_USER_MASTER'] = $PK_USER_MASTER;
        $TAG_DATA['PK_TAG'] = $PK_TAG;
        db_perform_account('DOA_USER_TAG', $TAG_DATA, 'insert');

        /* $CUSTOMER_PHONE['PK_CUSTOMER_DETAILS'] = $PK_CUSTOMER_DETAILS;
        $CUSTOMER_PHONE['PHONE'] = $PHONE;
        db_perform_account_own($db_account, 'DOA_CUSTOMER_PHONE', $CUSTOMER_PHONE, 'insert');

        $CUSTOMER_EMAIL['PK_CUSTOMER_DETAILS'] = $PK_CUSTOMER_DETAILS;
        $CUSTOMER_EMAIL['EMAIL'] = $EMAIL_ID;
        db_perform_account_own($db_account, 'DOA_CUSTOMER_EMAIL', $CUSTOMER_EMAIL, 'insert'); */

        $USER_ROLE_DATA['PK_USER'] = $PK_USER;
        $USER_ROLE_DATA['PK_ROLES'] = 4;
        db_perform('DOA_USER_ROLES', $USER_ROLE_DATA, 'insert');

        $CUSTOMER_LOCATION_DATA['PK_USER'] = $PK_USER;
        $CUSTOMER_LOCATION_DATA['PK_LOCATION'] = $PK_LOCATION;
        db_perform('DOA_USER_LOCATION', $CUSTOMER_LOCATION_DATA, 'insert');

        return [$PK_USER, $PK_USER_MASTER];
    }
}

function createAppointment($account_id, $PK_LOCATION, $PK_USER_MASTER, $DATE, $START_TIME): int
{
    global $db;

    $locationData = $db->Execute("SELECT DOA_LOCATION.*, DOA_ACCOUNT_MASTER.DB_NAME, DOA_TIMEZONE.TIMEZONE FROM DOA_LOCATION LEFT JOIN DOA_TIMEZONE ON DOA_LOCATION.PK_TIMEZONE = DOA_TIMEZONE.PK_TIMEZONE LEFT JOIN DOA_ACCOUNT_MASTER ON DOA_LOCATION.PK_ACCOUNT_MASTER = DOA_ACCOUNT_MASTER.PK_ACCOUNT_MASTER  WHERE DOA_LOCATION.PK_LOCATION = " . $PK_LOCATION . " LIMIT 1");
    $DB_NAME = $locationData->fields['DB_NAME'];
    $db_account = new queryFactory();
    if ($_SERVER['HTTP_HOST'] == 'localhost') {
        $conn1 = $db_account->connect('localhost', 'root', '', $DB_NAME);
        $http_path = 'http://localhost/doable/';
    } else {
        $conn1 = $db_account->connect('localhost', 'root', 'b54eawxj5h8ev', $DB_NAME);
        $http_path = 'https://doable.net/';
    }
    if ($db_account->error_number) {
        die("Connection Error");
    }

    $timePeriod = getTimePeriod($START_TIME);
    $PK_USER = 0;
    if ($timePeriod === "Morning") {
        $PK_USER_ARRAY = explode(',', $locationData->fields['PK_USER_MORNING'] ?? null);
        foreach ($PK_USER_ARRAY as $PK_USER_VALUE) {
            if (getAvailableServiceProviders($db_account, $PK_USER_VALUE, $DATE, $START_TIME, $PK_LOCATION) == false) {
                $PK_USER = $PK_USER_VALUE;
                break;
            }
        }
    } elseif ($timePeriod === "Afternoon") {
        $PK_USER_ARRAY = explode(',', $locationData->fields['PK_USER_AFTERNOON'] ?? null);
        foreach ($PK_USER_ARRAY as $PK_USER_VALUE) {
            if (getAvailableServiceProviders($db_account, $PK_USER_VALUE, $DATE, $START_TIME, $PK_LOCATION) == false) {
                $PK_USER = $PK_USER_VALUE;
                break;
            }
        }
    } elseif ($timePeriod === "Evening") {
        $PK_USER_ARRAY = explode(',', $locationData->fields['PK_USER_EVENING'] ?? null);
        foreach ($PK_USER_ARRAY as $PK_USER_VALUE) {
            if (getAvailableServiceProviders($db_account, $PK_USER_VALUE, $DATE, $START_TIME, $PK_LOCATION) == false) {
                $PK_USER = $PK_USER_VALUE;
                break;
            }
        }
    } elseif ($timePeriod === "Night") {
        $PK_USER_ARRAY = explode(',', $locationData->fields['PK_USER_NIGHT'] ?? null);
        foreach ($PK_USER_ARRAY as $PK_USER_VALUE) {
            if (getAvailableServiceProviders($db_account, $PK_USER_VALUE, $DATE, $START_TIME, $PK_LOCATION) == false) {
                $PK_USER = $PK_USER_VALUE;
                break;
            }
        }
    }

    if ($PK_USER == 0) {
        $return_data['status'] = 'error';
        $return_data['data'] = 'No available service provider found.';
        echo json_encode($return_data);
        die();
    }

    $package_services = $db_account->Execute("SELECT DOA_PACKAGE_SERVICE.*, DOA_PACKAGE.* FROM DOA_PACKAGE_SERVICE LEFT JOIN DOA_PACKAGE ON DOA_PACKAGE_SERVICE.PK_PACKAGE = DOA_PACKAGE.PK_PACKAGE WHERE DOA_PACKAGE.ACTIVE = 1 AND DOA_PACKAGE_SERVICE.CHATBOT_ENABLED = 1 AND DOA_PACKAGE.IS_DELETED = 0 ORDER BY DOA_PACKAGE.SORT_ORDER ASC LIMIT 1");
    $PK_SERVICE_MASTER = $package_services->fields['PK_SERVICE_MASTER'] ?? null;

    $callSettingData = $db->Execute("SELECT * FROM DOA_DEFAULT_CALL_SETTING WHERE PK_LOCATION = " . $PK_LOCATION . " LIMIT 1");
    $PK_SCHEDULING_CODE = $callSettingData->fields['PK_SCHEDULING_CODE'] ?? null;

    $schedulingCodeData = $db_account->Execute("SELECT * FROM DOA_SCHEDULING_CODE WHERE PK_SCHEDULING_CODE = " . addslashes($PK_SCHEDULING_CODE) . " LIMIT 1");
    $SLOT_DURATION = $schedulingCodeData->fields['DURATION'] ?? '30';

    $APPOINTMENT_DATA['PK_ENROLLMENT_MASTER'] = 0;
    $APPOINTMENT_DATA['PK_ENROLLMENT_SERVICE'] = 0;
    $APPOINTMENT_DATA['PK_SERVICE_MASTER'] = $PK_SERVICE_MASTER;
    $APPOINTMENT_DATA['PK_SERVICE_CODE'] = $PK_SERVICE_MASTER;
    $APPOINTMENT_DATA['PK_SCHEDULING_CODE'] = $PK_SCHEDULING_CODE;
    $APPOINTMENT_DATA['PK_LOCATION'] = $PK_LOCATION;
    $APPOINTMENT_DATA['DATE'] = $DATE;
    $APPOINTMENT_DATA['PK_APPOINTMENT_STATUS'] = 1;
    $APPOINTMENT_DATA['ACTIVE'] = 1;
    $APPOINTMENT_DATA['APPOINTMENT_TYPE'] = 'AD-HOC';
    $APPOINTMENT_DATA['CREATED_BY'] = $PK_USER;
    $APPOINTMENT_DATA['CREATED_ON'] = date("Y-m-d H:i");
    $APPOINTMENT_DATA['START_TIME'] = date('H:i:s', strtotime($START_TIME));
    $APPOINTMENT_DATA['END_TIME'] = date('H:i:s', strtotime($START_TIME . ' +' . $SLOT_DURATION . ' minutes'));
    $APPOINTMENT_DATA['SERIAL_NUMBER'] = 1;
    $APPOINTMENT_DATA['IS_FROM_AI_CALL'] = 1;
    db_perform_account_own($db_account, 'DOA_APPOINTMENT_MASTER', $APPOINTMENT_DATA, 'insert');
    $PK_APPOINTMENT_MASTER = $db_account->insert_ID();

    $APPOINTMENT_SP_DATA['PK_APPOINTMENT_MASTER'] = $PK_APPOINTMENT_MASTER;
    $APPOINTMENT_SP_DATA['PK_USER'] = $PK_USER;
    db_perform_account_own($db_account, 'DOA_APPOINTMENT_SERVICE_PROVIDER', $APPOINTMENT_SP_DATA, 'insert');

    $APPOINTMENT_CUSTOMER_DATA['PK_APPOINTMENT_MASTER'] = $PK_APPOINTMENT_MASTER;
    $APPOINTMENT_CUSTOMER_DATA['PK_USER_MASTER'] = $PK_USER_MASTER;
    db_perform_account_own($db_account, 'DOA_APPOINTMENT_CUSTOMER', $APPOINTMENT_CUSTOMER_DATA, 'insert');

    return $PK_APPOINTMENT_MASTER;
}

function getAvailableServiceProviders($db_account, $PK_USER, $date, $time, $PK_LOCATION)
{
    $sql = "SELECT DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER, DOA_APPOINTMENT_MASTER.DATE, DOA_APPOINTMENT_MASTER.START_TIME, DOA_APPOINTMENT_MASTER.END_TIME, DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER
            FROM DOA_APPOINTMENT_MASTER
            LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER
            WHERE DOA_APPOINTMENT_MASTER.DATE = '" . addslashes($date) . "'
            AND DOA_APPOINTMENT_MASTER.START_TIME = '" . date('H:i:s', strtotime($time)) . "'
            AND DOA_APPOINTMENT_MASTER.PK_LOCATION = " . (int)$PK_LOCATION . " AND DOA_APPOINTMENT_MASTER.STATUS = 'A' AND DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS = 1
            AND DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER = " . (int)$PK_USER . " AND DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER IS NOT NULL
            ORDER BY DOA_APPOINTMENT_MASTER.DATE ASC, DOA_APPOINTMENT_MASTER.START_TIME ASC";

    $result = $db_account->Execute($sql);
    if ($result->RecordCount() > 0) {
        return true; // Service provider is booked
    } else {
        return false; // Service provider is available
    }
}

function getTimePeriod($time)
{
    $hour = (int) date('H', strtotime($time));

    if ($hour >= 5 && $hour < 12) {
        return "Morning";
    } elseif ($hour >= 12 && $hour < 17) {
        return "Afternoon";
    } elseif ($hour >= 17 && $hour < 21) {
        return "Evening";
    } else {
        return "Night";
    }
}

function db_perform_account_own($db_account, $table, $data, $action = 'insert', $parameters = '')
{
    if (!is_array($data)) return false;
    reset($data);
    $query = '';
    if ($action == 'insert') {
        $query = 'insert into ' . $table . ' (';
        while (list($columns,) = each($data)) {
            $query .= $columns . ', ';
        }
        $query = substr($query, 0, -2) . ') values (';
        reset($data);
        while (list(, $value) = each($data)) {
            switch ((string)$value) {
                case 'now()':
                    $query .= 'now(), ';
                    break;
                case 'null':
                    $query .= 'null, ';
                    break;
                default:
                    $query .= '\'' . db_input($value) . '\', ';
                    break;
            }
        }
        $query = substr($query, 0, -2) . ')';
    } elseif ($action == 'update') {
        $query = 'update ' . $table . ' set ';
        while (list($columns, $value) = each($data)) {
            switch ((string)$value) {
                case 'now()':
                    $query .= $columns . ' = now(), ';
                    break;
                case 'null':
                    $query .= $columns .= ' = null, ';
                    break;
                default:
                    $query .= $columns . ' = \'' . db_input($value) . '\', ';
                    break;
            }
        }
        $query = substr($query, 0, -2) . ' where ' . $parameters;
    }
    // echo $query . "<br>";
    return $db_account->Execute($query);
}
