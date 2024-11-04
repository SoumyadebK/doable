<?php
require_once('../../global/config.php');
error_reporting(0);
$RESPONSE_DATA = $_POST;
$FUNCTION_NAME = $RESPONSE_DATA['FUNCTION_NAME'];
unset($RESPONSE_DATA['FUNCTION_NAME']);
$FUNCTION_NAME($RESPONSE_DATA);

/*Saving Data from Service Code Page*/
function saveAccountInfoData($RESPONSE_DATA){
    global $db;
    global $conn;
    $ACCOUNT_DATA['PK_BUSINESS_TYPE'] = $RESPONSE_DATA['PK_BUSINESS_TYPE'];
    $ACCOUNT_DATA['PK_ACCOUNT_TYPE'] = $RESPONSE_DATA['PK_ACCOUNT_TYPE'];
    $ACCOUNT_DATA['FRANCHISE'] = $RESPONSE_DATA['FRANCHISE'] ?? 0;
    $ACCOUNT_DATA['BUSINESS_NAME'] = $RESPONSE_DATA['BUSINESS_NAME'];
    $ACCOUNT_DATA['ADDRESS'] = $RESPONSE_DATA['ACCOUNT_ADDRESS'];
    $ACCOUNT_DATA['ADDRESS_1'] = $RESPONSE_DATA['ACCOUNT_ADDRESS_1'];
    $ACCOUNT_DATA['PK_COUNTRY'] = $RESPONSE_DATA['ACCOUNT_PK_COUNTRY'];
    $ACCOUNT_DATA['PK_STATES'] = $RESPONSE_DATA['PK_STATES'];
    $ACCOUNT_DATA['CITY'] = $RESPONSE_DATA['ACCOUNT_CITY'];
    $ACCOUNT_DATA['ZIP'] = $RESPONSE_DATA['ACCOUNT_ZIP'];
    $ACCOUNT_DATA['PHONE'] = $RESPONSE_DATA['ACCOUNT_PHONE'];
    $ACCOUNT_DATA['FAX'] = $RESPONSE_DATA['ACCOUNT_FAX'];
    $ACCOUNT_DATA['EMAIL'] = $RESPONSE_DATA['ACCOUNT_EMAIL'];
    $ACCOUNT_DATA['WEBSITE'] = $RESPONSE_DATA['ACCOUNT_WEBSITE'];
    $ACCOUNT_DATA['TEXTING_FEATURE_ENABLED'] = $RESPONSE_DATA['TEXTING_FEATURE_ENABLED'] ?? 0;
    $ACCOUNT_DATA['TWILIO_ACCOUNT_TYPE'] = $RESPONSE_DATA['TWILIO_ACCOUNT_TYPE'];
    $ACCOUNT_DATA['USERNAME_PREFIX'] = $RESPONSE_DATA['USERNAME_PREFIX'];

    if(empty($RESPONSE_DATA['PK_ACCOUNT_MASTER'])) {
        $ACCOUNT_DATA['ACTIVE'] = 1;
        $ACCOUNT_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
        $ACCOUNT_DATA['CREATED_ON'] = date("Y-m-d H:i");
        db_perform('DOA_ACCOUNT_MASTER', $ACCOUNT_DATA, 'insert');
        $PK_ACCOUNT_MASTER = $db->insert_ID();

        $databaseName = 'DOA_'.$PK_ACCOUNT_MASTER;
        $sqlCreateDatabase = "CREATE DATABASE IF NOT EXISTS $databaseName";
        if ($conn->query($sqlCreateDatabase) === FALSE) {
            echo "Error creating database: " . $conn->error . "\n";
        }

        if ($_SERVER['HTTP_HOST'] == 'localhost') {
            $conn_account_db = new mysqli('localhost', 'root', '', $databaseName);
        } else {
            $conn_account_db = new mysqli('localhost', 'root', 'b54eawxj5h8ev', $databaseName);
        }

        if ($conn_account_db->connect_error) {
            die("Connection failed: " . $conn_account_db->connect_error);
        }
        include ('../includes/create_database.php');
        $sqlCreateTable = $create_database;
        if ($conn_account_db->multi_query($sqlCreateTable) === FALSE) {
            echo "Error creating table: " . $conn_account_db->error . "\n";
        }
        $conn_account_db->close();

        $ACCOUNT_DATA_UPDATE['DB_NAME'] = $databaseName;
        db_perform('DOA_ACCOUNT_MASTER', $ACCOUNT_DATA_UPDATE, 'update'," PK_ACCOUNT_MASTER = ".$PK_ACCOUNT_MASTER);
    }else{
        $ACCOUNT_DATA['ACTIVE'] = $RESPONSE_DATA['ACTIVE'];
        $ACCOUNT_DATA['EDITED_BY']	= $_SESSION['PK_USER'];
        $ACCOUNT_DATA['EDITED_ON'] = date("Y-m-d H:i");
        db_perform('DOA_ACCOUNT_MASTER', $ACCOUNT_DATA, 'update'," PK_ACCOUNT_MASTER =  '$RESPONSE_DATA[PK_ACCOUNT_MASTER]'");
        $PK_ACCOUNT_MASTER = $RESPONSE_DATA['PK_ACCOUNT_MASTER'];
    }
    echo $PK_ACCOUNT_MASTER;
}

function saveProfileInfoData($RESPONSE_DATA)
{
    global $db;
    $PK_ACCOUNT_MASTER = $RESPONSE_DATA['PK_ACCOUNT_MASTER'];
    $account_data = $db->Execute("SELECT USERNAME_PREFIX FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER = ".$PK_ACCOUNT_MASTER);
    $USERNAME_PREFIX = ($account_data->RecordCount() > 0) ? $account_data->fields['USERNAME_PREFIX'] : '';
    if (strpos($RESPONSE_DATA['USER_NAME'], $USERNAME_PREFIX.'.') !== false) {
        $USER_DATA['USER_NAME'] = $RESPONSE_DATA['USER_NAME'];
    } else {
        $USER_DATA['USER_NAME'] = $USERNAME_PREFIX . '.' . $RESPONSE_DATA['USER_NAME'];
    }
    $USER_DATA['FIRST_NAME'] = $RESPONSE_DATA['FIRST_NAME'];
    $USER_DATA['LAST_NAME'] = $RESPONSE_DATA['LAST_NAME'];
    $USER_DATA['EMAIL_ID'] = $RESPONSE_DATA['EMAIL_ID'];
    if (!empty($RESPONSE_DATA['PASSWORD']))
        $USER_DATA['PASSWORD'] = password_hash($RESPONSE_DATA['PASSWORD'], PASSWORD_DEFAULT);

    $USER_DATA['GENDER'] = $RESPONSE_DATA['GENDER'];
    $USER_DATA['DOB'] = date('Y-m-d', strtotime($RESPONSE_DATA['DOB']));
    $USER_DATA['ADDRESS'] = $RESPONSE_DATA['ADDRESS'];
    $USER_DATA['ADDRESS_1'] = $RESPONSE_DATA['ADDRESS_1'];
    $USER_DATA['PK_COUNTRY'] = $RESPONSE_DATA['PK_COUNTRY'];
    $USER_DATA['PK_STATES'] = $RESPONSE_DATA['PK_STATES'];
    $USER_DATA['CITY'] = $RESPONSE_DATA['CITY'];
    $USER_DATA['ZIP'] = $RESPONSE_DATA['ZIP'];
    $USER_DATA['PHONE'] = $RESPONSE_DATA['PHONE'];
    $USER_DATA['NOTES'] = $RESPONSE_DATA['NOTES'];


    if(empty($RESPONSE_DATA['PK_USER_EDIT'])){
        $USER_DATA['PK_ACCOUNT_MASTER'] = $PK_ACCOUNT_MASTER;
        $USER_DATA['CREATE_LOGIN'] = 1;
        $USER_DATA['ACTIVE'] = 1;
        $USER_DATA['CREATED_BY']  = $_SESSION['PK_USER'];
        $USER_DATA['CREATED_ON']  = date("Y-m-d H:i");
        db_perform('DOA_USERS', $USER_DATA, 'insert');
        $PK_USER = $db->insert_ID();
        $USER_ROLE_DATA['PK_USER'] = $PK_USER;
        $USER_ROLE_DATA['PK_ROLES'] = 2;
        db_perform('DOA_USER_ROLES', $USER_ROLE_DATA, 'insert');
    }else{
        if (empty($RESPONSE_DATA['PK_USER_EDIT'])){
            $USER_DATA['PK_ACCOUNT_MASTER'] = $PK_ACCOUNT_MASTER;
            $USER_DATA['ACTIVE'] = 1;
            $USER_DATA['CREATED_BY']  = $_SESSION['PK_USER'];
            $USER_DATA['CREATED_ON']  = date("Y-m-d H:i");
            db_perform('DOA_USERS', $USER_DATA, 'insert');
        }else {
            $USER_DATA['ACTIVE'] = $RESPONSE_DATA['ACTIVE'];
            $USER_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
            $USER_DATA['EDITED_ON'] = date("Y-m-d H:i");
            db_perform('DOA_USERS', $USER_DATA, 'update', " PK_USER = ".$RESPONSE_DATA['PK_USER_EDIT']);
        }
    }

    $return_data['PK_USER'] = $RESPONSE_DATA['PK_USER_EDIT'];
    $return_data['PK_ACCOUNT_MASTER'] = $PK_ACCOUNT_MASTER;
    echo json_encode($return_data);
}
