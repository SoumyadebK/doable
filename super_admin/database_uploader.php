<?php
//error_reporting(0);
mysqli_report(MYSQLI_REPORT_OFF);
set_time_limit(0);
require_once('../global/config.php');
global $db;
global $db_account;

$title = "Upload CSV";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 1) {
    header("location:../login.php");
    exit;
}

$_SESSION['MIGRATION_DB_NAME'] = empty($_SESSION['MIGRATION_DB_NAME']) ? '' : $_SESSION['MIGRATION_DB_NAME'];
$_SESSION['TABLE_NAME'] = empty($_SESSION['TABLE_NAME']) ? '' : $_SESSION['TABLE_NAME'];

if (!empty($_POST)) {
    $PK_ACCOUNT_MASTER = $_POST['PK_ACCOUNT_MASTER'];
    $PK_LOCATION = $_POST['PK_LOCATION'];

    $account_data = $db->Execute("SELECT DB_NAME FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER = " . $PK_ACCOUNT_MASTER);
    $DB_NAME = $account_data->fields['DB_NAME'];
    if (!empty($DB_NAME)) {
        require_once('../global/common_functions_account.php');
        $account_database = $DB_NAME;
        $db_account = new queryFactory();
        if ($_SERVER['HTTP_HOST'] == 'localhost') {
            $conn_account = $db_account->connect('localhost', 'root', '', $account_database);
        } else {
            $conn_account = $db_account->connect('localhost', 'root', 'b54eawxj5h8ev', $account_database);
        }
        if (mysqli_connect_error()) {
            die("Account Database Connection Error");
        }
    }
    $_SESSION['MIGRATION_ACCOUNT_MASTER'] = $PK_ACCOUNT_MASTER;
    $_SESSION['MIGRATION_LOCATION'] = $PK_LOCATION;
    $_SESSION['MIGRATION_DB_NAME'] = $_POST['DATABASE_NAME'];
    $_SESSION['TABLE_NAME'] = $_POST['TABLE_NAME'];
    require_once('upload_functions.php');

    switch ($_POST['TABLE_NAME']) {
        case 'DOA_OPERATIONAL_HOUR':
            $startTime = getStartTime();
            $endTime = getEndTime();
            for ($i = 1; $i <= 5; $i++) {
                $OPERATIONAL_HOUR_DATA['PK_LOCATION'] = $PK_LOCATION;
                $OPERATIONAL_HOUR_DATA['DAY_NUMBER'] = $i;
                $OPERATIONAL_HOUR_DATA['OPEN_TIME'] = date('H:i', strtotime($startTime->fields['value']));
                $OPERATIONAL_HOUR_DATA['CLOSE_TIME'] = date('H:i', strtotime($endTime->fields['value']));
                $OPERATIONAL_HOUR_DATA['CLOSED'] = 0;
                db_perform_account('DOA_OPERATIONAL_HOUR', $OPERATIONAL_HOUR_DATA, 'insert');
            }

            for ($i = 6; $i <= 7; $i++) {
                $OPERATIONAL_HOUR_DATA['PK_LOCATION'] = $PK_LOCATION;
                $OPERATIONAL_HOUR_DATA['DAY_NUMBER'] = $i;
                $OPERATIONAL_HOUR_DATA['OPEN_TIME'] = '00:00:00';
                $OPERATIONAL_HOUR_DATA['CLOSE_TIME'] = '00:00:00';
                $OPERATIONAL_HOUR_DATA['CLOSED'] = 1;
                db_perform_account('DOA_OPERATIONAL_HOUR', $OPERATIONAL_HOUR_DATA, 'insert');
            }
            break;

        case 'DOA_INQUIRY_METHOD':
            $allInquiryMethod = getAllInquiryMethod();
            while (!$allInquiryMethod->EOF) {
                $INQUIRY_METHOD = $allInquiryMethod->fields['inquiry_type'];
                $table_data = $db_account->Execute("SELECT * FROM DOA_INQUIRY_METHOD WHERE INQUIRY_METHOD='$INQUIRY_METHOD' AND PK_ACCOUNT_MASTER='$PK_ACCOUNT_MASTER'");
                if ($table_data->RecordCount() == 0) {
                    $INSERT_DATA['PK_ACCOUNT_MASTER'] = $PK_ACCOUNT_MASTER;
                    $INSERT_DATA['INQUIRY_METHOD'] = $INQUIRY_METHOD;
                    $INSERT_DATA['ACTIVE'] = 1;
                    $INSERT_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
                    $INSERT_DATA['CREATED_ON'] = date("Y-m-d H:i");
                    db_perform_account('DOA_INQUIRY_METHOD', $INSERT_DATA, 'insert');
                }
                $allInquiryMethod->MoveNext();
            }
            break;

        case 'DOA_USERS':
            $allUsers = getAllUsers();
            /* $account_data = $db->Execute("SELECT USERNAME_PREFIX FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER = " . $PK_ACCOUNT_MASTER);
            $USERNAME_PREFIX = ($account_data->RecordCount() > 0) ? $account_data->fields['USERNAME_PREFIX'] : ''; */
            while (!$allUsers->EOF) {
                $user_id = $allUsers->fields['user_id'];
                $user_exist = $db->Execute("SELECT PK_USER, USER_ID FROM `DOA_USERS` WHERE `USER_ID` LIKE '$user_id' AND PK_ACCOUNT_MASTER = " . $PK_ACCOUNT_MASTER);
                if ($user_exist->RecordCount() == 0) {
                    $USER_DATA['PK_ACCOUNT_MASTER'] = $PK_ACCOUNT_MASTER;
                    $USER_DATA['FIRST_NAME'] = trim($allUsers->fields['first_name']);
                    $USER_DATA['LAST_NAME'] = trim($allUsers->fields['last_name']);
                    $USER_DATA['USER_NAME'] = $allUsers->fields['user_name']; //$USERNAME_PREFIX . '.' . $allUsers->fields['user_name'];
                    $USER_DATA['USER_ID'] = $allUsers->fields['user_id'];
                    $USER_DATA['EMAIL_ID'] = $allUsers->fields['email'];
                    if (!empty($allUsers->fields['cell_phone']) && $allUsers->fields['cell_phone'] != null) {
                        $USER_DATA['PHONE'] = $allUsers->fields['cell_phone'];
                    } elseif (!empty($allUsers->fields['home_phone']) && $allUsers->fields['home_phone'] != null) {
                        $USER_DATA['PHONE'] = $allUsers->fields['home_phone'];
                    }
                    $USER_DATA['PASSWORD'] = password_hash($allUsers->fields['user_pass'], PASSWORD_DEFAULT);
                    $USER_DATA['CREATE_LOGIN'] = ($USER_DATA['USER_NAME'] && $USER_DATA['PASSWORD']) ? 1 : 0;
                    $USER_DATA['GENDER'] = ($allUsers->fields['gender'] == 'M') ? 'Male' : 'Female';
                    $USER_DATA['DOB'] = date("Y-m-d", strtotime($allUsers->fields['birth_date']));
                    $USER_DATA['ADDRESS'] = $allUsers->fields['address1'];
                    $USER_DATA['ADDRESS_1'] = $allUsers->fields['address2'];
                    $USER_DATA['CITY'] = $allUsers->fields['city'];
                    $USER_DATA['PK_COUNTRY'] = 1;
                    $state = $allUsers->fields['state'];
                    $state_data = $db->Execute("SELECT PK_STATES FROM DOA_STATES WHERE STATE_NAME='$state' OR STATE_CODE='$state'");
                    $USER_DATA['PK_STATES'] = ($state_data->RecordCount() > 0) ? $state_data->fields['PK_STATES'] : 0;
                    $USER_DATA['ZIP'] = $allUsers->fields['zip'];
                    $USER_DATA['NOTES'] = $allUsers->fields['remarks'];
                    $USER_DATA['ACTIVE'] = $allUsers->fields['is_active'];
                    $USER_DATA['JOINING_DATE'] = date("Y-m-d", strtotime($allUsers->fields['date_added']));
                    $USER_DATA['APPEAR_IN_CALENDAR'] = $allUsers->fields['appear_in_calendar'];
                    $USER_DATA['IS_DELETED'] = 0;
                    $USER_DATA['DISPLAY_ORDER'] = $allUsers->fields['position'];
                    $USER_DATA['ARTHUR_MURRAY_ID'] = $allUsers->fields['tax_id'];
                    $USER_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
                    $USER_DATA['CREATED_ON'] = date("Y-m-d H:i");
                    db_perform('DOA_USERS', $USER_DATA, 'insert');
                    $PK_USER = $db->insert_ID();

                    if ($PK_USER) {
                        $USER_DATA_ACCOUNT['PK_USER_MASTER_DB'] = $PK_USER;
                        $USER_DATA_ACCOUNT['PK_ACCOUNT_MASTER'] = $PK_ACCOUNT_MASTER;
                        $USER_DATA_ACCOUNT['FIRST_NAME'] = trim($allUsers->fields['first_name']);
                        $USER_DATA_ACCOUNT['LAST_NAME'] = trim($allUsers->fields['last_name']);
                        $USER_DATA_ACCOUNT['USER_NAME'] = $allUsers->fields['user_name'];
                        $USER_DATA_ACCOUNT['EMAIL_ID'] = $allUsers->fields['email'];
                        if (!empty($allUsers->fields['cell_phone']) && $allUsers->fields['cell_phone'] != null) {
                            $USER_DATA_ACCOUNT['PHONE'] = $allUsers->fields['cell_phone'];
                        } elseif (!empty($allUsers->fields['home_phone']) && $allUsers->fields['home_phone'] != null) {
                            $USER_DATA_ACCOUNT['PHONE'] = $allUsers->fields['home_phone'];
                        }
                        $USER_DATA_ACCOUNT['CREATED_BY'] = $_SESSION['PK_USER'];
                        $USER_DATA_ACCOUNT['CREATED_ON'] = date("Y-m-d H:i");
                        db_perform_account('DOA_USERS', $USER_DATA_ACCOUNT, 'insert');
                    }
                } else {
                    $PK_USER = $user_exist->fields['PK_USER'];

                    $USER_DATA['FIRST_NAME'] = trim($allUsers->fields['first_name']);
                    $USER_DATA['LAST_NAME'] = trim($allUsers->fields['last_name']);
                    $USER_DATA['USER_NAME'] = $allUsers->fields['user_name']; //$USERNAME_PREFIX . '.' . $allUsers->fields['user_name'];
                    $USER_DATA['USER_ID'] = $allUsers->fields['user_id'];
                    $USER_DATA['EMAIL_ID'] = $allUsers->fields['email'];
                    if (!empty($allUsers->fields['cell_phone']) && $allUsers->fields['cell_phone'] != null) {
                        $USER_DATA['PHONE'] = $allUsers->fields['cell_phone'];
                    } elseif (!empty($allUsers->fields['home_phone']) && $allUsers->fields['home_phone'] != null) {
                        $USER_DATA['PHONE'] = $allUsers->fields['home_phone'];
                    }
                    $USER_DATA['PASSWORD'] = password_hash($allUsers->fields['user_pass'], PASSWORD_DEFAULT);
                    $USER_DATA['CREATE_LOGIN'] = ($USER_DATA['USER_NAME'] && $USER_DATA['PASSWORD']) ? 1 : 0;
                    $USER_DATA['GENDER'] = ($allUsers->fields['gender'] == 'M') ? 'Male' : 'Female';
                    $USER_DATA['DOB'] = date("Y-m-d", strtotime($allUsers->fields['birth_date']));
                    $USER_DATA['ADDRESS'] = $allUsers->fields['address1'];
                    $USER_DATA['ADDRESS_1'] = $allUsers->fields['address2'];
                    $USER_DATA['CITY'] = $allUsers->fields['city'];
                    $USER_DATA['PK_COUNTRY'] = 1;
                    $state = $allUsers->fields['state'];
                    $state_data = $db->Execute("SELECT PK_STATES FROM DOA_STATES WHERE STATE_NAME='$state' OR STATE_CODE='$state'");
                    $USER_DATA['PK_STATES'] = ($state_data->RecordCount() > 0) ? $state_data->fields['PK_STATES'] : 0;
                    $USER_DATA['ZIP'] = $allUsers->fields['zip'];
                    $USER_DATA['NOTES'] = $allUsers->fields['remarks'];
                    $USER_DATA['ACTIVE'] = $allUsers->fields['is_active'];
                    $USER_DATA['JOINING_DATE'] = date("Y-m-d", strtotime($allUsers->fields['date_added']));
                    $USER_DATA['APPEAR_IN_CALENDAR'] = $allUsers->fields['appear_in_calendar'];
                    $USER_DATA['IS_DELETED'] = 0;
                    $USER_DATA['DISPLAY_ORDER'] = $allUsers->fields['position'];
                    $USER_DATA['ARTHUR_MURRAY_ID'] = $allUsers->fields['tax_id'];
                    db_perform('DOA_USERS', $USER_DATA, 'update', " PK_USER = $PK_USER");
                }

                $roleId = $allUsers->fields['role'];
                $getRole = getRole($roleId);
                $doableRoleId = $db->Execute("SELECT PK_ROLES FROM DOA_ROLES WHERE ROLES='$getRole'");
                $PK_ROLE = ($doableRoleId->RecordCount() > 0) ? $doableRoleId->fields['PK_ROLES'] : 0;

                $user_role_exist = $db->Execute("SELECT PK_USER FROM DOA_USER_ROLES WHERE PK_USER = $PK_USER AND PK_ROLES = " . $PK_ROLE);
                if ($user_role_exist->RecordCount() == 0) {
                    $USER_ROLE_DATA['PK_USER'] = $PK_USER;
                    $USER_ROLE_DATA['PK_ROLES'] = $PK_ROLE;
                    db_perform('DOA_USER_ROLES', $USER_ROLE_DATA, 'insert');
                }

                $user_location_exist = $db->Execute("SELECT PK_USER FROM DOA_USER_LOCATION WHERE PK_USER = $PK_USER AND PK_LOCATION = " . $PK_LOCATION);
                if ($user_location_exist->RecordCount() == 0) {
                    $USER_LOCATION_DATA['PK_USER'] = $PK_USER;
                    $USER_LOCATION_DATA['PK_LOCATION'] = $PK_LOCATION;
                    db_perform('DOA_USER_LOCATION', $USER_LOCATION_DATA, 'insert');
                }


                if ($PK_ROLE == 5) {
                    $operational_hour_exist = $db_account->Execute("SELECT PK_USER FROM DOA_SERVICE_PROVIDER_LOCATION_HOURS WHERE PK_USER = $PK_USER AND PK_LOCATION = " . $PK_LOCATION);
                    if ($operational_hour_exist->RecordCount() == 0) {
                        $startTime = getStartTime();
                        $endTime = getEndTime();

                        $SERVICE_PROVIDER_HOURS['PK_USER'] = $PK_USER;
                        $SERVICE_PROVIDER_HOURS['PK_LOCATION'] = $PK_LOCATION;
                        $SERVICE_PROVIDER_HOURS['MON_START_TIME'] = date('H:i', strtotime($startTime->fields['value']));
                        $SERVICE_PROVIDER_HOURS['MON_END_TIME'] = date('H:i', strtotime($endTime->fields['value']));
                        $SERVICE_PROVIDER_HOURS['TUE_START_TIME'] = date('H:i', strtotime($startTime->fields['value']));
                        $SERVICE_PROVIDER_HOURS['TUE_END_TIME'] = date('H:i', strtotime($endTime->fields['value']));
                        $SERVICE_PROVIDER_HOURS['WED_START_TIME'] = date('H:i', strtotime($startTime->fields['value']));
                        $SERVICE_PROVIDER_HOURS['WED_END_TIME'] = date('H:i', strtotime($endTime->fields['value']));
                        $SERVICE_PROVIDER_HOURS['THU_START_TIME'] = date('H:i', strtotime($startTime->fields['value']));
                        $SERVICE_PROVIDER_HOURS['THU_END_TIME'] = date('H:i', strtotime($endTime->fields['value']));
                        $SERVICE_PROVIDER_HOURS['FRI_START_TIME'] = date('H:i', strtotime($startTime->fields['value']));
                        $SERVICE_PROVIDER_HOURS['FRI_END_TIME'] = date('H:i', strtotime($endTime->fields['value']));
                        $SERVICE_PROVIDER_HOURS['SAT_START_TIME'] = '00:00:00';
                        $SERVICE_PROVIDER_HOURS['SAT_END_TIME'] = '00:00:00';
                        $SERVICE_PROVIDER_HOURS['SUN_START_TIME'] = '00:00:00';
                        $SERVICE_PROVIDER_HOURS['SUN_END_TIME'] = '00:00:00';
                        db_perform_account('DOA_SERVICE_PROVIDER_LOCATION_HOURS', $SERVICE_PROVIDER_HOURS, 'insert');
                    }
                }
                $allUsers->MoveNext();
            }
            break;

        case 'DOA_CUSTOMER':
            $allCustomers = getAllCustomers();
            /* $account_data = $db->Execute("SELECT USERNAME_PREFIX FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER = " . $PK_ACCOUNT_MASTER);
            $USERNAME_PREFIX = ($account_data->RecordCount() > 0) ? $account_data->fields['USERNAME_PREFIX'] : ''; */
            while (!$allCustomers->EOF) {
                $customer_id = $allCustomers->fields['customer_id'];
                $customer_exist = $db->Execute("SELECT DOA_USERS.PK_USER, DOA_USERS.USER_ID FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER WHERE DOA_USERS.USER_ID LIKE '$customer_id' AND DOA_USER_MASTER.PRIMARY_LOCATION_ID = '$PK_LOCATION' AND DOA_USERS.PK_ACCOUNT_MASTER = " . $PK_ACCOUNT_MASTER);
                if ($customer_exist->RecordCount() == 0) {
                    try {
                        $USER_DATA['PK_ACCOUNT_MASTER'] = $PK_ACCOUNT_MASTER;
                        $USER_DATA['USER_NAME'] = $allCustomers->fields['customer_id']; //$USERNAME_PREFIX . '.' . $allCustomers->fields['customer_id'];
                        $USER_DATA['USER_ID'] = $allCustomers->fields['customer_id'];
                        $USER_DATA['FIRST_NAME'] = trim($allCustomers->fields['first_name']);
                        $USER_DATA['LAST_NAME'] = trim($allCustomers->fields['last_name']);
                        $USER_DATA['EMAIL_ID'] = $allCustomers->fields['email'];
                        if (!empty($allCustomers->fields['cell_phone']) && $allCustomers->fields['cell_phone'] != null && $allCustomers->fields['cell_phone'] != "   -   -    *") {
                            $USER_DATA['PHONE'] = $allCustomers->fields['cell_phone'];
                        } elseif (!empty($allCustomers->fields['home_phone']) && $allCustomers->fields['home_phone'] != null && $allCustomers->fields['home_phone'] != "   -   -    *") {
                            $USER_DATA['PHONE'] = $allCustomers->fields['home_phone'];
                        } elseif (!empty($allCustomers->fields['business_phone']) && $allCustomers->fields['business_phone'] != null && $allCustomers->fields['business_phone'] != "   -   -    *") {
                            $USER_DATA['PHONE'] = $allCustomers->fields['business_phone'];
                        }
                        if ($allCustomers->fields['gender'] == 0) {
                            $USER_DATA['GENDER'] = 'Male';
                        } elseif ($allCustomers->fields['gender'] == 1) {
                            $USER_DATA['GENDER'] = 'Female';
                        }

                        $USER_DATA['DOB'] = date("Y-m-d", strtotime($allCustomers->fields['birth_date']));
                        if ($allCustomers->fields['merital_status'] == 1) {
                            $USER_DATA['MARITAL_STATUS'] = "Married";
                        } elseif ($allCustomers->fields['merital_status'] == 0) {
                            $USER_DATA['MARITAL_STATUS'] = "Unmarried";
                        }

                        $USER_DATA['ADDRESS'] = $allCustomers->fields['address1'];
                        $USER_DATA['ADDRESS_1'] = $allCustomers->fields['address2'];
                        $USER_DATA['CITY'] = $allCustomers->fields['city'];
                        $USER_DATA['PK_COUNTRY'] = 1;
                        $state = $allCustomers->fields['state'];
                        $state_data = $db->Execute("SELECT PK_STATES FROM DOA_STATES WHERE STATE_NAME='$state' OR STATE_CODE='$state'");
                        $USER_DATA['PK_STATES'] = ($state_data->RecordCount() > 0) ? $state_data->fields['PK_STATES'] : 0;
                        $USER_DATA['ZIP'] = $allCustomers->fields['zip'];
                        $USER_DATA['NOTES'] = $allCustomers->fields['quote'];
                        $USER_DATA['ACTIVE'] = ($allCustomers->fields['student_status'] == 'A') ? 1 : 0;
                        $USER_DATA['JOINING_DATE'] = date("Y-m-d", strtotime($allCustomers->fields['inquiry_date']));
                        $USER_DATA['IS_DELETED'] = 0;
                        $USER_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
                        $USER_DATA['CREATED_ON'] = date("Y-m-d H:i");
                        db_perform('DOA_USERS', $USER_DATA, 'insert');
                        $PK_USER = $db->insert_ID();

                        if ($PK_USER) {
                            $USER_DATA_ACCOUNT['PK_USER_MASTER_DB'] = $PK_USER;
                            $USER_DATA_ACCOUNT['PK_ACCOUNT_MASTER'] = $PK_ACCOUNT_MASTER;
                            $USER_DATA_ACCOUNT['FIRST_NAME'] = trim($allCustomers->fields['first_name']);
                            $USER_DATA_ACCOUNT['LAST_NAME'] = trim($allCustomers->fields['last_name']);
                            $USER_DATA_ACCOUNT['USER_NAME'] = $allCustomers->fields['customer_id'];
                            $USER_DATA_ACCOUNT['EMAIL_ID'] = $allCustomers->fields['email'];
                            $USER_DATA_ACCOUNT['PHONE'] = $USER_DATA['PHONE'];
                            $USER_DATA_ACCOUNT['CREATED_BY'] = $_SESSION['PK_USER'];
                            $USER_DATA_ACCOUNT['CREATED_ON'] = date("Y-m-d H:i");
                            db_perform_account('DOA_USERS', $USER_DATA_ACCOUNT, 'insert');

                            $USER_ROLE_DATA['PK_USER'] = $PK_USER;
                            $USER_ROLE_DATA['PK_ROLES'] = 4;
                            db_perform('DOA_USER_ROLES', $USER_ROLE_DATA, 'insert');

                            $USER_LOCATION_DATA['PK_USER'] = $PK_USER;
                            $USER_LOCATION_DATA['PK_LOCATION'] = $PK_LOCATION;
                            db_perform('DOA_USER_LOCATION', $USER_LOCATION_DATA, 'insert');

                            $USER_MASTER_DATA['PK_USER'] = $PK_USER;
                            $USER_MASTER_DATA['PK_ACCOUNT_MASTER'] = $PK_ACCOUNT_MASTER;
                            $USER_MASTER_DATA['PRIMARY_LOCATION_ID'] = $PK_LOCATION;
                            db_perform('DOA_USER_MASTER', $USER_MASTER_DATA, 'insert');
                            $PK_USER_MASTER = $db->insert_ID();

                            if ($PK_USER_MASTER) {
                                $CUSTOMER_DATA['PK_USER_MASTER'] = $PK_USER_MASTER;
                                $CUSTOMER_DATA['FIRST_NAME'] = $allCustomers->fields['first_name'];
                                $CUSTOMER_DATA['LAST_NAME'] = $allCustomers->fields['last_name'];
                                $CUSTOMER_DATA['EMAIL'] = $allCustomers->fields['email'];
                                $CUSTOMER_DATA['PHONE'] = $allCustomers->fields['cell_phone'];
                                $CUSTOMER_DATA['DOB'] = date("Y-m-d", strtotime($allCustomers->fields['birth_date']));
                                $CUSTOMER_DATA['CALL_PREFERENCE'] = $allCustomers->fields['confirmation_pref'];
                                //$CUSTOMER_DATA['REMINDER_OPTION'] = $getData[23];
                                $partner_name = explode(" ", $allCustomers->fields['partner_name']);
                                $CUSTOMER_DATA['PARTNER_FIRST_NAME'] = isset($partner_name[0]) ? ($partner_name[0]) : '';
                                $CUSTOMER_DATA['PARTNER_LAST_NAME'] = isset($partner_name[1]) ? ($partner_name[1]) : '';
                                if ($allCustomers->fields['partner_gender'] == 0) {
                                    $CUSTOMER_DATA['PARTNER_GENDER'] = "Male";
                                } elseif ($allCustomers->fields['partner_gender'] == 1) {
                                    $CUSTOMER_DATA['PARTNER_GENDER'] = "Female";
                                }
                                if (!empty(trim($allCustomers->fields['partner_name']))) {
                                    $CUSTOMER_DATA['ATTENDING_WITH'] = "With a Partner";
                                } else {
                                    $CUSTOMER_DATA['ATTENDING_WITH'] = "Solo";
                                }
                                $CUSTOMER_DATA['PARTNER_DOB'] = date("Y-m-d", strtotime($allCustomers->fields['partner_birth_date']));
                                $CUSTOMER_DATA['IS_PRIMARY'] = 1;
                                db_perform_account('DOA_CUSTOMER_DETAILS', $CUSTOMER_DATA, 'insert');
                                $PK_CUSTOMER_DETAILS = $db_account->insert_ID();

                                if (!empty($allCustomers->fields['home_phone']) && $allCustomers->fields['home_phone'] != "   -   -    *") {
                                    $PHONE_DATA['PK_CUSTOMER_DETAILS'] = $PK_CUSTOMER_DETAILS;
                                    $PHONE_DATA['PHONE'] = $allCustomers->fields['home_phone'];
                                    db_perform_account('DOA_CUSTOMER_PHONE', $PHONE_DATA, 'insert');
                                }

                                if (!empty($allCustomers->fields['business_phone']) && $allCustomers->fields['business_phone'] != "   -   -    *") {
                                    $PHONE_DATA['PK_CUSTOMER_DETAILS'] = $PK_CUSTOMER_DETAILS;
                                    $PHONE_DATA['PHONE'] = $allCustomers->fields['business_phone'];
                                    db_perform_account('DOA_CUSTOMER_PHONE', $PHONE_DATA, 'insert');
                                }

                                if ($allCustomers->fields['special_date1'] != "0000-00-00 00:00:00" && $allCustomers->fields['special_date1'] > 0) {
                                    $SPECIAL_DATA['PK_CUSTOMER_DETAILS'] = $PK_CUSTOMER_DETAILS;
                                    $SPECIAL_DATA['SPECIAL_DATE'] = date("Y-m-d", strtotime($allCustomers->fields['special_date1']));
                                    $SPECIAL_DATA['DATE_NAME'] = $allCustomers->fields['datename1'];
                                    db_perform_account('DOA_CUSTOMER_SPECIAL_DATE', $SPECIAL_DATA, 'insert');
                                }
                                if ($allCustomers->fields['special_date2'] != "0000-00-00 00:00:00" && $allCustomers->fields['special_date2'] > 0) {
                                    $SPECIAL_DATA_1['PK_CUSTOMER_DETAILS'] = $PK_CUSTOMER_DETAILS;
                                    $SPECIAL_DATA_1['SPECIAL_DATE'] = date("Y-m-d", strtotime($allCustomers->fields['special_date2']));
                                    $SPECIAL_DATA_1['DATE_NAME'] = $allCustomers->fields['datename2'];
                                    db_perform_account('DOA_CUSTOMER_SPECIAL_DATE', $SPECIAL_DATA_1, 'insert');
                                }

                                $INQUIRY_VALUE['PK_USER_MASTER'] = $PK_USER_MASTER;
                                $INQUIRY_VALUE['WHAT_PROMPTED_YOU_TO_INQUIRE'] = $allCustomers->fields['inquiry_source'];

                                $INQUIRY_VALUE['PK_INQUIRY_METHOD'] = 0;
                                $INQUIRY_VALUE['INQUIRY_TAKER_ID'] = 0;

                                if (!empty(trim($allCustomers->fields['inquiry_type']))) {
                                    $inquiryId = $allCustomers->fields['inquiry_type'];
                                    if ($inquiryId == "TEL") {
                                        $getInquiry = "Telephone";
                                    } elseif ($inquiryId == "WIN") {
                                        $getInquiry = "Walk In";
                                    } elseif ($inquiryId == "GIFT") {
                                        $getInquiry = "Gift";
                                    } elseif ($inquiryId == "EML") {
                                        $getInquiry = "Email Message";
                                    } else {
                                        $getInquiry = getInquiry($inquiryId);
                                    }
                                    $doableInquiryId = $db_account->Execute("SELECT PK_INQUIRY_METHOD FROM DOA_INQUIRY_METHOD WHERE INQUIRY_METHOD='$getInquiry'");
                                    $INQUIRY_VALUE['PK_INQUIRY_METHOD'] = ($doableInquiryId->RecordCount() > 0) ? $doableInquiryId->fields['PK_INQUIRY_METHOD'] : 0;
                                }

                                if (!empty(trim($allCustomers->fields['area_of_interest']))) {
                                    $allInterest = explode('|', $allCustomers->fields['area_of_interest']);
                                    foreach ($allInterest as $interest) {
                                        $interestData = $db->Execute("SELECT PK_INTERESTS FROM DOA_INTERESTS WHERE INTERESTS LIKE '%" . $interest . "%'");
                                        if ($interestData->RecordCount() > 0) {
                                            $INTEREST_DATA['PK_USER_MASTER'] = $PK_USER_MASTER;
                                            $INTEREST_DATA['PK_INTERESTS'] = $interestData->fields['PK_INTERESTS'];
                                            db_perform_account('DOA_CUSTOMER_INTEREST', $INTEREST_DATA, 'insert');
                                        }
                                    }
                                }

                                if (!empty(trim($allCustomers->fields['inquiry_taker']))) {
                                    $takerId = $allCustomers->fields['inquiry_taker'];
                                    $getTaker = getTaker($takerId);
                                    $doableTakerId = $db->Execute("SELECT PK_USER FROM DOA_USERS WHERE USER_NAME='$getTaker'");
                                    $INQUIRY_VALUE['INQUIRY_TAKER_ID'] = ($doableTakerId->RecordCount() > 0) ? $doableTakerId->fields['PK_USER'] : 0;
                                }
                                db_perform_account('DOA_CUSTOMER_INTEREST_OTHER_DATA', $INQUIRY_VALUE, 'insert');
                            }
                        }
                    } catch (mysqli_sql_exception $ex) {
                        echo $ex->getMessage() . "<br>";
                        die();
                    }
                } else {
                    $PK_USER = $customer_exist->fields['PK_USER'];

                    $USER_DATA['USER_NAME'] = $allCustomers->fields['customer_id'];
                    $USER_DATA['USER_ID'] = $allCustomers->fields['customer_id'];
                    $USER_DATA['FIRST_NAME'] = trim($allCustomers->fields['first_name']);
                    $USER_DATA['LAST_NAME'] = trim($allCustomers->fields['last_name']);
                    $USER_DATA['EMAIL_ID'] = $allCustomers->fields['email'];
                    if (!empty($allCustomers->fields['cell_phone']) && $allCustomers->fields['cell_phone'] != null && $allCustomers->fields['cell_phone'] != "   -   -    *") {
                        $USER_DATA['PHONE'] = $allCustomers->fields['cell_phone'];
                    } elseif (!empty($allCustomers->fields['home_phone']) && $allCustomers->fields['home_phone'] != null && $allCustomers->fields['home_phone'] != "   -   -    *") {
                        $USER_DATA['PHONE'] = $allCustomers->fields['home_phone'];
                    } elseif (!empty($allCustomers->fields['business_phone']) && $allCustomers->fields['business_phone'] != null && $allCustomers->fields['business_phone'] != "   -   -    *") {
                        $USER_DATA['PHONE'] = $allCustomers->fields['business_phone'];
                    }
                    if ($allCustomers->fields['gender'] == 0) {
                        $USER_DATA['GENDER'] = 'Male';
                    } elseif ($allCustomers->fields['gender'] == 1) {
                        $USER_DATA['GENDER'] = 'Female';
                    }

                    $USER_DATA['DOB'] = date("Y-m-d", strtotime($allCustomers->fields['birth_date']));
                    if ($allCustomers->fields['merital_status'] == 1) {
                        $USER_DATA['MARITAL_STATUS'] = "Married";
                    } elseif ($allCustomers->fields['merital_status'] == 0) {
                        $USER_DATA['MARITAL_STATUS'] = "Unmarried";
                    }

                    $USER_DATA['ADDRESS'] = $allCustomers->fields['address1'];
                    $USER_DATA['ADDRESS_1'] = $allCustomers->fields['address2'];
                    $USER_DATA['CITY'] = $allCustomers->fields['city'];
                    $USER_DATA['PK_COUNTRY'] = 1;
                    $state = $allCustomers->fields['state'];
                    $state_data = $db->Execute("SELECT PK_STATES FROM DOA_STATES WHERE STATE_NAME='$state' OR STATE_CODE='$state'");
                    $USER_DATA['PK_STATES'] = ($state_data->RecordCount() > 0) ? $state_data->fields['PK_STATES'] : 0;
                    $USER_DATA['ZIP'] = $allCustomers->fields['zip'];
                    $USER_DATA['NOTES'] = $allCustomers->fields['quote'];
                    $USER_DATA['ACTIVE'] = ($allCustomers->fields['student_status'] == 'A') ? 1 : 0;
                    db_perform('DOA_USERS', $USER_DATA, 'update', " PK_USER = $PK_USER");
                }
                $allCustomers->MoveNext();
            }
            break;

        case 'DOA_SERVICE_MASTER':
            $allServices = getAllServices();
            while (!$allServices->EOF) {
                $service_name = $allServices->fields['service_name'];
                $table_data = $db_account->Execute("SELECT * FROM DOA_SERVICE_MASTER WHERE SERVICE_NAME='$service_name'");
                if ($table_data->RecordCount() == 0) {
                    $SERVICE['SERVICE_NAME'] = $allServices->fields['service_name'];
                    if (strpos($SERVICE['SERVICE_NAME'], 'Miscellaneous') !== false) {
                        $SERVICE['PK_SERVICE_CLASS'] = 5;
                    } else {
                        $SERVICE['PK_SERVICE_CLASS'] = 2;
                    }
                    $SERVICE['IS_SCHEDULE'] = 0;
                    $SERVICE['IS_SUNDRY'] = 0;
                    $SERVICE['DESCRIPTION'] = $allServices->fields['service_name'];
                    $SERVICE['ACTIVE'] = 1;
                    $SERVICE['IS_DELETED'] = 0;
                    $SERVICE['CREATED_BY'] = $_SESSION['PK_USER'];
                    $SERVICE['CREATED_ON'] = date("Y-m-d H:i");
                    db_perform_account('DOA_SERVICE_MASTER', $SERVICE, 'insert');
                    $PK_SERVICE_MASTER = $db_account->insert_ID();

                    $SERVICE_CODE['PK_SERVICE_MASTER'] = $PK_SERVICE_MASTER;
                    $SERVICE_CODE['SERVICE_CODE'] = $allServices->fields['service_id'];
                    //$SERVICE_CODE['PK_FREQUENCY'] = 0;
                    $SERVICE_CODE['DESCRIPTION'] = $allServices->fields['service_name'];
                    //$SERVICE_CODE['DURATION'] = 0;

                    if (strpos($allServices->fields['service_id'], "GRP") !== false || strpos($allServices->fields['service_id'], "Group") !== false) {
                        $SERVICE_CODE['IS_GROUP'] = 1;
                        $SERVICE_CODE['CAPACITY'] = 20;
                    } else {
                        $SERVICE_CODE['IS_GROUP'] = 0;
                        $SERVICE_CODE['CAPACITY'] = 0;
                    }

                    if ($allServices->fields['chargeable'] == "Y") {
                        $SERVICE_CODE['IS_CHARGEABLE'] = 1;
                    } elseif ($allServices->fields['chargeable'] == "N") {
                        $SERVICE_CODE['IS_CHARGEABLE'] = 0;
                    }
                    $SERVICE_CODE['ACTIVE'] = 1;
                    db_perform_account('DOA_SERVICE_CODE', $SERVICE_CODE, 'insert');
                } else {
                    $PK_SERVICE_MASTER = $table_data->fields['PK_SERVICE_MASTER'];
                }

                $service_location_data = $db_account->Execute("SELECT * FROM DOA_SERVICE_LOCATION WHERE PK_SERVICE_MASTER = '$PK_SERVICE_MASTER' AND PK_LOCATION = '$PK_LOCATION'");
                if ($service_location_data->RecordCount() == 0) {
                    $SERVICE_LOCATION_DATA['PK_SERVICE_MASTER'] = $PK_SERVICE_MASTER;
                    $SERVICE_LOCATION_DATA['PK_LOCATION'] = $PK_LOCATION;
                    db_perform_account('DOA_SERVICE_LOCATION', $SERVICE_LOCATION_DATA, 'insert');
                }

                $allServices->MoveNext();
            }
            break;

        case 'DOA_SCHEDULING_CODE':
            $allSchedulingCodes = getAllSchedulingCodes();
            while (!$allSchedulingCodes->EOF) {
                $scheduling_code = $allSchedulingCodes->fields['booking_code'];
                $table_data = $db_account->Execute("SELECT * FROM DOA_SCHEDULING_CODE WHERE SCHEDULING_CODE = '$scheduling_code' AND PK_ACCOUNT_MASTER='$PK_ACCOUNT_MASTER'");
                if ($table_data->RecordCount() == 0) {
                    $SCHEDULING_CODE['PK_ACCOUNT_MASTER'] = $PK_ACCOUNT_MASTER;
                    $SCHEDULING_CODE['SCHEDULING_CODE'] = $scheduling_code;
                    $SCHEDULING_CODE['SCHEDULING_NAME'] = $allSchedulingCodes->fields['booking_name'];
                    $SCHEDULING_CODE['PK_SCHEDULING_EVENT'] = 1;
                    $SCHEDULING_CODE['PK_EVENT_ACTION'] = 2;
                    if ($allSchedulingCodes->fields['status'] == "Active") {
                        $SCHEDULING_CODE['ACTIVE'] = 1;
                    } elseif ($allSchedulingCodes->fields['status'] == "Not Active") {
                        $SCHEDULING_CODE['ACTIVE'] = 0;
                    }
                    $SCHEDULING_CODE['DURATION'] = $allSchedulingCodes->fields['duration'];
                    $SCHEDULING_CODE['SORT_ORDER'] = $allSchedulingCodes->fields['sort_order'];
                    $SCHEDULING_CODE['COLOR_CODE'] = $allSchedulingCodes->fields['color_hexcode'];;
                    $SCHEDULING_CODE['TO_DOS'] = ($allSchedulingCodes->fields['service_id'] == 'GEN') ? 1 : 0;
                    $SCHEDULING_CODE['CREATED_BY'] = $_SESSION['PK_USER'];
                    $SCHEDULING_CODE['CREATED_ON'] = date("Y-m-d H:i");
                    db_perform_account('DOA_SCHEDULING_CODE', $SCHEDULING_CODE, 'insert');
                    $PK_SCHEDULING_CODE = $db_account->insert_ID();

                    $service_code = $allSchedulingCodes->fields['service_id'];
                    $serviceCodeData = $db_account->Execute("SELECT PK_SERVICE_MASTER, PK_SERVICE_CODE FROM DOA_SERVICE_CODE WHERE SERVICE_CODE = '$service_code'");

                    /* $SERVICE_SCHEDULING_CODE['PK_SERVICE_MASTER'] = $serviceCodeData->fields['PK_SERVICE_MASTER'];
                    $SERVICE_SCHEDULING_CODE['PK_SERVICE_CODE'] = $serviceCodeData->fields['PK_SERVICE_CODE'];
                    $SERVICE_SCHEDULING_CODE['PK_SCHEDULING_CODE'] = $PK_SCHEDULING_CODE;
                    db_perform_account('DOA_SERVICE_SCHEDULING_CODE', $SERVICE_SCHEDULING_CODE, 'insert'); */

                    $SCHEDULING_SERVICE['PK_SCHEDULING_CODE'] = $PK_SCHEDULING_CODE;
                    $SCHEDULING_SERVICE['PK_SERVICE_MASTER'] = $serviceCodeData->fields['PK_SERVICE_MASTER'];
                    $SCHEDULING_SERVICE['PK_SERVICE_CODE'] = $serviceCodeData->fields['PK_SERVICE_CODE'];
                    db_perform_account('DOA_SCHEDULING_SERVICE', $SCHEDULING_SERVICE, 'insert');
                }
                $allSchedulingCodes->MoveNext();
            }
            break;

        case "DOA_ENROLLMENT_TYPE":
            $allEnrollmentTypes = getAllEnrollmentTypes();
            while (!$allEnrollmentTypes->EOF) {
                $CODE = $allEnrollmentTypes->fields['code'];
                $table_data = $db->Execute("SELECT * FROM DOA_ENROLLMENT_TYPE WHERE CODE = '$CODE'");
                if ($table_data->RecordCount() == 0) {
                    $INSERT_DATA['ENROLLMENT_TYPE'] = $allEnrollmentTypes->fields['enrollment_type'];
                    $INSERT_DATA['CODE'] = $CODE;
                    $INSERT_DATA['ACTIVE'] = 1;
                    $INSERT_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
                    $INSERT_DATA['CREATED_ON'] = date("Y-m-d H:i");
                    db_perform('DOA_ENROLLMENT_TYPE', $INSERT_DATA, 'insert');
                }
                $allEnrollmentTypes->MoveNext();
            }
            break;

        case "DOA_PACKAGE":
            $allPackages = getAllPackages();
            while (!$allPackages->EOF) {
                $package_name = $allPackages->fields['package_name'];
                $table_data = $db_account->Execute("SELECT PK_PACKAGE FROM `DOA_PACKAGE` WHERE PACKAGE_NAME = '$package_name'");
                if ($table_data->RecordCount() == 0) {
                    $PACKAGE_DATA['PACKAGE_NAME'] = $allPackages->fields['package_name'];
                    $PACKAGE_DATA['SORT_ORDER'] = $allPackages->fields['sorder'];
                    $PACKAGE_DATA['EXPIRY_DATE'] = 30;
                    $PACKAGE_DATA['ACTIVE'] = ($allPackages->fields['closed'] == 1) ? 0 : 1;
                    $PACKAGE_DATA['IS_DELETED'] = 0;
                    $PACKAGE_DATA['CREATED_BY']  = $_SESSION['PK_USER'];
                    $PACKAGE_DATA['CREATED_ON']  = date("Y-m-d H:i");
                    db_perform_account('DOA_PACKAGE', $PACKAGE_DATA, 'insert');
                    $PK_PACKAGE = $db_account->insert_ID();

                    $PACKAGE_LOCATION_DATA['PK_PACKAGE'] = $PK_PACKAGE;
                    $PACKAGE_LOCATION_DATA['PK_LOCATION'] = $PK_LOCATION;
                    db_perform_account('DOA_PACKAGE_LOCATION', $PACKAGE_LOCATION_DATA, 'insert');

                    $packageServiceData = getPackageServices($allPackages->fields['package_id']);
                    while (!$packageServiceData->EOF) {
                        $service_code = $packageServiceData->fields['service_id'];
                        $doableServiceId = $db_account->Execute("SELECT PK_SERVICE_MASTER, PK_SERVICE_CODE, DESCRIPTION FROM DOA_SERVICE_CODE WHERE SERVICE_CODE ='$service_code'");
                        $PK_SERVICE_MASTER = $doableServiceId->fields['PK_SERVICE_MASTER'];
                        $PK_SERVICE_CODE = $doableServiceId->fields['PK_SERVICE_CODE'];
                        $DESCRIPTION = $doableServiceId->fields['DESCRIPTION'];
                        $PACKAGE_SERVICE_DATA['PK_PACKAGE'] = $PK_PACKAGE;
                        $PACKAGE_SERVICE_DATA['PK_SERVICE_MASTER'] = $PK_SERVICE_MASTER;
                        $PACKAGE_SERVICE_DATA['PK_SERVICE_CODE'] = $PK_SERVICE_CODE;
                        $PACKAGE_SERVICE_DATA['SERVICE_DETAILS'] = $DESCRIPTION;
                        $PACKAGE_SERVICE_DATA['NUMBER_OF_SESSION'] = $packageServiceData->fields['quantity'];
                        $PACKAGE_SERVICE_DATA['PRICE_PER_SESSION'] = $packageServiceData->fields['cost'];
                        $PACKAGE_SERVICE_DATA['TOTAL'] = $packageServiceData->fields['quantity'] * $packageServiceData->fields['cost'];
                        $PACKAGE_SERVICE_DATA['DISCOUNT_TYPE'] = 0;
                        $PACKAGE_SERVICE_DATA['DISCOUNT'] = 0;
                        $PACKAGE_SERVICE_DATA['FINAL_AMOUNT'] = $PACKAGE_SERVICE_DATA['TOTAL'];
                        $PACKAGE_SERVICE_DATA['ACTIVE'] = 1;
                        db_perform_account('DOA_PACKAGE_SERVICE', $PACKAGE_SERVICE_DATA, 'insert');

                        $packageServiceData->MoveNext();
                    }

                    /*$package_name = $allPackages->fields['package_name'];
                    $package_data = $db_account->Execute("SELECT PK_PACKAGE FROM DOA_PACKAGE WHERE PACKAGE_NAME = '$package_name'");
                    if ($package_data->RecordCount() > 0) {
                        $PACKAGE_DATA['ACTIVE'] = ($allPackages->fields['closed'] == 1) ? 0 : 1;
                        db_perform_account('DOA_PACKAGE', $PACKAGE_DATA, 'update', ' PK_PACKAGE = '.$package_data->fields['PK_PACKAGE']);
                    }*/
                }
                $allPackages->MoveNext();
            }
            break;

        case "DOA_ENROLLMENT":
            $upload_path = 'uploads/' . $PK_ACCOUNT_MASTER;

            if (!file_exists('../' . $upload_path . '/enrollment_pdf/')) {
                mkdir('../' . $upload_path . '/enrollment_pdf/', 0777, true);
                chmod('../' . $upload_path . '/enrollment_pdf/', 0777);
            }

            $location_data = $db->Execute("SELECT LOCATION_CODE FROM DOA_LOCATION WHERE PK_LOCATION = '$PK_LOCATION'");
            $LOCATION_CODE = $location_data->fields['LOCATION_CODE'];
            if (!file_exists('../' . $upload_path . '/enrollment_pdf/' . $LOCATION_CODE . '/')) {
                mkdir('../' . $upload_path . '/enrollment_pdf/' . $LOCATION_CODE . '/', 0777, true);
                chmod('../' . $upload_path . '/enrollment_pdf/' . $LOCATION_CODE . '/', 0777);
            }

            $lastUploadedId = getLastUploadedID($PK_ACCOUNT_MASTER, $PK_LOCATION, $_SESSION['MIGRATION_DB_NAME'], $_POST['TABLE_NAME']);

            $lastUploadedData = getLastEnrollmentId();
            $lastId = $lastUploadedData->fields['last_id'];
            insertLastUploadedID($PK_ACCOUNT_MASTER, $PK_LOCATION, $_SESSION['MIGRATION_DB_NAME'], $_POST['TABLE_NAME'], $lastId);

            $allEnrollments = getAllEnrollments($lastUploadedId);
            while (!$allEnrollments->EOF) {
                $enrollment_id = $allEnrollments->fields['enrollment_id'];
                [$enrollment_type, $code] = getEnrollmentType($allEnrollments->fields['enrollment_type']);
                if ($code == 'MISC') {
                    $ENROLLMENT_DATA['MISC_ID'] = $enrollment_id;
                }
                $ENROLLMENT_DATA['ENROLLMENT_ID'] = $enrollment_id;
                $ENROLLMENT_DATA['ENROLLMENT_NAME'] = $allEnrollments->fields['enrollmentname'];

                $enrollment_type_data = $db->Execute("SELECT PK_ENROLLMENT_TYPE FROM `DOA_ENROLLMENT_TYPE` WHERE CODE = '$code'");
                if ($enrollment_type_data->RecordCount() > 0) {
                    $ENROLLMENT_DATA['PK_ENROLLMENT_TYPE'] = $enrollment_type_data->fields['PK_ENROLLMENT_TYPE'];
                } else {
                    $ENROLLMENT_DATA['PK_ENROLLMENT_TYPE'] = 0;
                }

                $ENROLLMENT_DATA['MISC_TYPE'] = NULL;
                if ($allEnrollments->fields['miscserv'] == 'G') {
                    $ENROLLMENT_DATA['MISC_TYPE'] = 'GENERAL';
                } elseif ($allEnrollments->fields['miscserv'] == 'S') {
                    $ENROLLMENT_DATA['MISC_TYPE'] = 'SHOWCASE';
                } elseif ($allEnrollments->fields['miscserv'] == 'D') {
                    $ENROLLMENT_DATA['MISC_TYPE'] = 'DOR';
                }

                $customerId = $allEnrollments->fields['customer_id'];
                $doableCustomerId = $db->Execute("SELECT DOA_USER_MASTER.PK_USER_MASTER FROM DOA_USER_MASTER INNER JOIN DOA_USERS ON DOA_USER_MASTER.PK_USER = DOA_USERS.PK_USER WHERE DOA_USERS.USER_ID = '$customerId' AND DOA_USER_MASTER.PRIMARY_LOCATION_ID = '$PK_LOCATION' AND DOA_USER_MASTER.PK_ACCOUNT_MASTER = '$PK_ACCOUNT_MASTER'");
                $ENROLLMENT_DATA['PK_USER_MASTER'] = ($doableCustomerId->RecordCount() > 0) ? $doableCustomerId->fields['PK_USER_MASTER'] : 0;

                $customer_enrollment_number = $db_account->Execute("SELECT CUSTOMER_ENROLLMENT_NUMBER FROM `DOA_ENROLLMENT_MASTER` WHERE PK_USER_MASTER = " . $ENROLLMENT_DATA['PK_USER_MASTER'] . " ORDER BY PK_ENROLLMENT_MASTER DESC LIMIT 1");
                if ($customer_enrollment_number->RecordCount() > 0) {
                    $ENROLLMENT_DATA['CUSTOMER_ENROLLMENT_NUMBER'] = $customer_enrollment_number->fields['CUSTOMER_ENROLLMENT_NUMBER'] + 1;
                } else {
                    $ENROLLMENT_DATA['CUSTOMER_ENROLLMENT_NUMBER'] = 1;
                }

                $ENROLLMENT_DATA['PK_LOCATION'] = $PK_LOCATION;

                $package_name = getPackageCode($allEnrollments->fields['package_code']);
                $enrollment_package = $db_account->Execute("SELECT PK_PACKAGE FROM `DOA_PACKAGE` WHERE PACKAGE_NAME = '$package_name'");
                if ($enrollment_package->RecordCount() > 0) {
                    $ENROLLMENT_DATA['PK_PACKAGE'] = $enrollment_package->fields['PK_PACKAGE'];
                } else {
                    $ENROLLMENT_DATA['PK_PACKAGE'] = 0;
                }

                $user_id = $allEnrollments->fields['closer'];
                $doableUserId = $db->Execute("SELECT PK_USER FROM DOA_USERS WHERE PK_ACCOUNT_MASTER = '$PK_ACCOUNT_MASTER' AND USER_ID = '$user_id'");
                $ENROLLMENT_DATA['ENROLLMENT_BY_ID'] = ($doableUserId->RecordCount() > 0) ? $doableUserId->fields['PK_USER'] : 0;

                $ENROLLMENT_BY_PERCENTAGE = 100;
                $ENROLLMENT_DATA['ENROLLMENT_BY_PERCENTAGE'] = number_format($ENROLLMENT_BY_PERCENTAGE, 2);
                $ENROLLMENT_DATA['ACTIVE'] = 1;
                $ENROLLMENT_DATA['IS_SALE'] = $allEnrollments->fields['is_sale'];
                $ENROLLMENT_DATA['STATUS'] = "A";
                $ENROLLMENT_DATA['ENROLLMENT_DATE'] = $allEnrollments->fields['enrollment_date'];

                $location_data = $db->Execute("SELECT LOCATION_CODE FROM DOA_LOCATION WHERE PK_LOCATION = '$PK_LOCATION'");
                $LOCATION_CODE = $location_data->fields['LOCATION_CODE'];

                $ENROLLMENT_DATA['AGREEMENT_PDF_LINK'] = ($allEnrollments->fields['enroll_pdf_file']) ? $LOCATION_CODE . '/' . $allEnrollments->fields['enroll_pdf_file'] . '.pdf' : NULL;
                $ENROLLMENT_DATA['CHARGE_TYPE'] = 0;
                $ENROLLMENT_DATA['EXPIRY_DATE'] = $allEnrollments->fields['expdate'];
                $ENROLLMENT_DATA['CREATED_BY'] = $PK_ACCOUNT_MASTER;
                $ENROLLMENT_DATA['CREATED_ON'] = date("Y-m-d H:i");
                db_perform_account('DOA_ENROLLMENT_MASTER', $ENROLLMENT_DATA, 'insert');
                $PK_ENROLLMENT_MASTER = $db_account->insert_ID();

                if ($allEnrollments->fields['teacher1'] != NULL && $allEnrollments->fields['teacher1'] != '' && $allEnrollments->fields['percentage1'] > 0) {
                    $ENROLLMENT_SERVICE_PROVIDER_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
                    $user_id = $allEnrollments->fields['teacher1'];
                    $doableCustomerId = $db->Execute("SELECT DOA_USERS.PK_USER FROM DOA_USERS INNER JOIN DOA_USER_LOCATION ON DOA_USER_LOCATION.PK_USER = DOA_USERS.PK_USER WHERE DOA_USERS.USER_ID = '$user_id' AND DOA_USER_LOCATION.PK_LOCATION = '$PK_LOCATION' AND DOA_USERS.PK_ACCOUNT_MASTER = '$PK_ACCOUNT_MASTER'");
                    $ENROLLMENT_SERVICE_PROVIDER_DATA['SERVICE_PROVIDER_ID'] = ($doableCustomerId->RecordCount() > 0) ? $doableCustomerId->fields['PK_USER'] : 0;
                    $ENROLLMENT_SERVICE_PROVIDER_DATA['SERVICE_PROVIDER_PERCENTAGE'] = $allEnrollments->fields['percentage1'];
                    $ENROLLMENT_SERVICE_PROVIDER_DATA['PERCENTAGE_AMOUNT'] = ($allEnrollments->fields['total_cost'] * $allEnrollments->fields['percentage1']) / 100;
                    db_perform_account('DOA_ENROLLMENT_SERVICE_PROVIDER', $ENROLLMENT_SERVICE_PROVIDER_DATA, 'insert');
                }
                if ($allEnrollments->fields['teacher2'] != NULL && $allEnrollments->fields['teacher2'] != '' && $allEnrollments->fields['percentage2'] > 0) {
                    $ENROLLMENT_SERVICE_PROVIDER_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
                    $user_id = $allEnrollments->fields['teacher2'];
                    $doableCustomerId = $db->Execute("SELECT DOA_USERS.PK_USER FROM DOA_USERS INNER JOIN DOA_USER_LOCATION ON DOA_USER_LOCATION.PK_USER = DOA_USERS.PK_USER WHERE DOA_USERS.USER_ID = '$user_id' AND DOA_USER_LOCATION.PK_LOCATION = '$PK_LOCATION' AND DOA_USERS.PK_ACCOUNT_MASTER = '$PK_ACCOUNT_MASTER'");
                    $ENROLLMENT_SERVICE_PROVIDER_DATA['SERVICE_PROVIDER_ID'] = ($doableCustomerId->RecordCount() > 0) ? $doableCustomerId->fields['PK_USER'] : 0;
                    $ENROLLMENT_SERVICE_PROVIDER_DATA['SERVICE_PROVIDER_PERCENTAGE'] = $allEnrollments->fields['percentage2'];
                    $ENROLLMENT_SERVICE_PROVIDER_DATA['PERCENTAGE_AMOUNT'] = ($allEnrollments->fields['total_cost'] * $allEnrollments->fields['percentage2']) / 100;
                    db_perform_account('DOA_ENROLLMENT_SERVICE_PROVIDER', $ENROLLMENT_SERVICE_PROVIDER_DATA, 'insert');
                }
                if ($allEnrollments->fields['teacher3'] != NULL && $allEnrollments->fields['teacher3'] != '' && $allEnrollments->fields['percentage3'] > 0) {
                    $ENROLLMENT_SERVICE_PROVIDER_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
                    $user_id = $allEnrollments->fields['teacher3'];
                    $doableCustomerId = $db->Execute("SELECT DOA_USERS.PK_USER FROM DOA_USERS INNER JOIN DOA_USER_LOCATION ON DOA_USER_LOCATION.PK_USER = DOA_USERS.PK_USER WHERE DOA_USERS.USER_ID = '$user_id' AND DOA_USER_LOCATION.PK_LOCATION = '$PK_LOCATION' AND DOA_USERS.PK_ACCOUNT_MASTER = '$PK_ACCOUNT_MASTER'");
                    $ENROLLMENT_SERVICE_PROVIDER_DATA['SERVICE_PROVIDER_ID'] = ($doableCustomerId->RecordCount() > 0) ? $doableCustomerId->fields['PK_USER'] : 0;
                    $ENROLLMENT_SERVICE_PROVIDER_DATA['SERVICE_PROVIDER_PERCENTAGE'] = $allEnrollments->fields['percentage3'];
                    $ENROLLMENT_SERVICE_PROVIDER_DATA['PERCENTAGE_AMOUNT'] = ($allEnrollments->fields['total_cost'] * $allEnrollments->fields['percentage3']) / 100;
                    db_perform_account('DOA_ENROLLMENT_SERVICE_PROVIDER', $ENROLLMENT_SERVICE_PROVIDER_DATA, 'insert');
                }

                /*if ($code == 'MISC' && date('Y-m-d') > $allEnrollments->fields['expdate']) {
                    $APPOINTMENT_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
                    $APPOINTMENT_DATA['PK_ENROLLMENT_SERVICE'] = 0;
                    $APPOINTMENT_DATA['PK_SERVICE_MASTER'] = 0;
                    $APPOINTMENT_DATA['PK_SERVICE_CODE'] = 0;
                    $APPOINTMENT_DATA['DATE'] = date("Y-m-d");
                    $APPOINTMENT_DATA['START_TIME'] = date('H:i:s');
                    $APPOINTMENT_DATA['END_TIME'] = date('H:i:s');
                    $APPOINTMENT_DATA['PK_APPOINTMENT_STATUS'] = 2;
                    $APPOINTMENT_DATA['COMMENT'] = 'Miscellaneous type enrollment';
                    $APPOINTMENT_DATA['ACTIVE'] = 1;
                    $APPOINTMENT_DATA['IS_PAID'] = 1;
                    $APPOINTMENT_DATA['CREATED_BY'] = $PK_ACCOUNT_MASTER;
                    $APPOINTMENT_DATA['CREATED_ON'] = date("Y-m-d H:i");
                    db_perform_account('DOA_APPOINTMENT_MASTER', $APPOINTMENT_DATA, 'insert');
                }*/

                $ACTUAL_AMOUNT = $allEnrollments->fields['sale_value'];
                $DISCOUNT = $allEnrollments->fields['discount'];
                $TOTAL_AMOUNT = $allEnrollments->fields['total_cost'];
                $DOWN_PAYMENT = 0;
                $BALANCE_PAYABLE = 0;
                $PAYMENT_METHOD = 'One Time';
                $PAYMENT_TERM = '';
                $NUMBER_OF_PAYMENT = 0;
                $FIRST_DUE_DATE = date('Y-m-d');
                $INSTALLMENT_AMOUNT = 0;
                if (strpos($allEnrollments->fields['program1'], "C") !== false) {
                    $info = str_replace('  ', ' ', $allEnrollments->fields['program1']);
                    $paymentInfo = explode(' ', $info);
                    $NUMBER_OF_PAYMENT = (is_array($paymentInfo) && isset($paymentInfo[2]) && is_int($paymentInfo[2])) ? $paymentInfo[2] : 0;
                    $INSTALLMENT_AMOUNT = (float)$paymentInfo[1];
                    $DOWN_PAYMENT = $TOTAL_AMOUNT - ($NUMBER_OF_PAYMENT * $INSTALLMENT_AMOUNT);
                    $BALANCE_PAYABLE = ($NUMBER_OF_PAYMENT * $INSTALLMENT_AMOUNT);
                    $PAYMENT_METHOD = 'Payment Plans';
                    $PAYMENT_TERM = 'Monthly';
                    $FIRST_DUE_DATE = date('Y-m-d', strtotime($allEnrollments->fields['sale_date']));
                }

                $BILLING_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
                $BILLING_DATA['BILLING_REF'] = '';
                $BILLING_DATA['BILLING_DATE'] = date('Y-m-d', strtotime($allEnrollments->fields['sale_date']));
                $BILLING_DATA['ACTUAL_AMOUNT'] = $ACTUAL_AMOUNT;
                $BILLING_DATA['DISCOUNT'] = $DISCOUNT;
                $BILLING_DATA['DOWN_PAYMENT'] = $DOWN_PAYMENT;
                $BILLING_DATA['BALANCE_PAYABLE'] = $BALANCE_PAYABLE;
                $BILLING_DATA['TOTAL_AMOUNT'] = $TOTAL_AMOUNT;
                $BILLING_DATA['PAYMENT_METHOD'] = $PAYMENT_METHOD;
                $BILLING_DATA['PAYMENT_TERM'] = $PAYMENT_TERM;
                $BILLING_DATA['NUMBER_OF_PAYMENT'] = $NUMBER_OF_PAYMENT;
                $BILLING_DATA['FIRST_DUE_DATE'] = $FIRST_DUE_DATE;
                $BILLING_DATA['INSTALLMENT_AMOUNT'] = $INSTALLMENT_AMOUNT;
                db_perform_account('DOA_ENROLLMENT_BILLING', $BILLING_DATA, 'insert');
                $PK_ENROLLMENT_BILLING = $db_account->insert_ID();

                if ($code == 'MISC') {
                    /*$enrollmentId = $enrollment_id;
                    $doableEnrollmentId = $db_account->Execute("SELECT PK_ENROLLMENT_MASTER FROM DOA_ENROLLMENT_MASTER WHERE ENROLLMENT_ID = '$enrollmentId' AND PK_LOCATION = '$PK_LOCATION'");
                    $PK_ENROLLMENT_MASTER = $doableEnrollmentId->fields['PK_ENROLLMENT_MASTER'];*/

                    $service_code = 'NONE';
                    $doableServiceId = $db_account->Execute("SELECT PK_SERVICE_MASTER, PK_SERVICE_CODE, DESCRIPTION FROM DOA_SERVICE_CODE WHERE SERVICE_CODE ='$service_code'");
                    $PK_SERVICE_MASTER = $doableServiceId->fields['PK_SERVICE_MASTER'];
                    $PK_SERVICE_CODE = $doableServiceId->fields['PK_SERVICE_CODE'];
                    $SERVICE_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
                    $SERVICE_DATA['PK_SERVICE_MASTER'] = $PK_SERVICE_MASTER;
                    $SERVICE_DATA['PK_SERVICE_CODE'] = $PK_SERVICE_CODE;
                    $SERVICE_DATA['SERVICE_DETAILS'] = $doableServiceId->fields['DESCRIPTION'];
                    $SERVICE_DATA['NUMBER_OF_SESSION'] = $ENROLLMENT_SERVICE_DATA['ORIGINAL_SESSION_COUNT'] = 1;
                    $SERVICE_DATA['PRICE_PER_SESSION'] = $TOTAL_AMOUNT;
                    $SERVICE_DATA['TOTAL'] = $ACTUAL_AMOUNT;
                    $SERVICE_DATA['DISCOUNT'] = $DISCOUNT;
                    $SERVICE_DATA['DISCOUNT_TYPE'] = ($DISCOUNT > 0) ? 1 : 0;
                    $SERVICE_DATA['FINAL_AMOUNT'] = $ENROLLMENT_SERVICE_DATA['ORIGINAL_AMOUNT'] = $TOTAL_AMOUNT;
                    $SERVICE_DATA['STATUS'] = 'A';
                    db_perform_account('DOA_ENROLLMENT_SERVICE', $SERVICE_DATA, 'insert');
                } else {
                    $allEnrollmentServices = getAllEnrollmentServicesById($enrollment_id);
                    while (!$allEnrollmentServices->EOF) {
                        $service_code = $allEnrollmentServices->fields['service_id'];
                        $quantity = $allEnrollmentServices->fields['quantity'];
                        $doableServiceId = $db_account->Execute("SELECT PK_SERVICE_MASTER, PK_SERVICE_CODE, DESCRIPTION FROM DOA_SERVICE_CODE WHERE SERVICE_CODE ='$service_code'");
                        $PK_SERVICE_MASTER = $doableServiceId->fields['PK_SERVICE_MASTER'];
                        $PK_SERVICE_CODE = $doableServiceId->fields['PK_SERVICE_CODE'];
                        $SERVICE_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
                        $SERVICE_DATA['PK_SERVICE_MASTER'] = $PK_SERVICE_MASTER;
                        $SERVICE_DATA['PK_SERVICE_CODE'] = $PK_SERVICE_CODE;
                        $SERVICE_DATA['SERVICE_DETAILS'] = $doableServiceId->fields['DESCRIPTION'];
                        $SERVICE_DATA['NUMBER_OF_SESSION'] = $ENROLLMENT_SERVICE_DATA['ORIGINAL_SESSION_COUNT'] = $quantity;

                        if ((strpos($service_code, 'PRI')  !== false) && ($service_code != 'NPRI')) {
                            $SERVICE_DATA['PRICE_PER_SESSION'] = ($quantity > 0) ? $TOTAL_AMOUNT / $quantity : 0;
                            $SERVICE_DATA['TOTAL'] = $ACTUAL_AMOUNT;
                            $SERVICE_DATA['DISCOUNT'] = $DISCOUNT;
                            $SERVICE_DATA['DISCOUNT_TYPE'] = ($DISCOUNT > 0) ? 1 : 0;
                            $SERVICE_DATA['FINAL_AMOUNT'] = $ENROLLMENT_SERVICE_DATA['ORIGINAL_AMOUNT'] = $TOTAL_AMOUNT;
                        } else {
                            $SERVICE_DATA['PRICE_PER_SESSION'] = 0;
                            $SERVICE_DATA['TOTAL'] = 0;
                            $SERVICE_DATA['DISCOUNT'] = 0;
                            $SERVICE_DATA['DISCOUNT_TYPE'] = 0;
                            $SERVICE_DATA['FINAL_AMOUNT'] = $ENROLLMENT_SERVICE_DATA['ORIGINAL_AMOUNT'] = 0;
                        }

                        db_perform_account('DOA_ENROLLMENT_SERVICE', $SERVICE_DATA, 'insert');
                        $allEnrollmentServices->MoveNext();
                    }
                }

                $BALANCE = 0;
                $allEnrollmentCharges = getAllEnrollmentChargesById($enrollment_id);
                while (!$allEnrollmentCharges->EOF) {
                    $BILLED_AMOUNT = $allEnrollmentCharges->fields['amount_due'];
                    $IS_DOWN_PAYMENT = (strpos($allEnrollmentCharges->fields['title'], 'down payment')  !== false) ? 1 : 0;
                    $BALANCE += $BILLED_AMOUNT;
                    if ($BILLED_AMOUNT == 0 && $IS_DOWN_PAYMENT == 1) {
                        $BILLED_AMOUNT = getDownPaymentBilledAmount($allEnrollmentCharges->fields['id']);
                    }
                    $BILLING_LEDGER_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
                    $BILLING_LEDGER_DATA['PK_ENROLLMENT_BILLING '] = $PK_ENROLLMENT_BILLING;
                    $BILLING_LEDGER_DATA['TRANSACTION_TYPE'] = 'Billing';
                    $BILLING_LEDGER_DATA['ENROLLMENT_LEDGER_PARENT'] = 0;
                    $BILLING_LEDGER_DATA['DUE_DATE'] = $allEnrollmentCharges->fields['date_due'];
                    $BILLING_LEDGER_DATA['BILLED_AMOUNT'] = $BILLED_AMOUNT;
                    $BILLING_LEDGER_DATA['PAID_AMOUNT'] = 0;
                    $BILLING_LEDGER_DATA['BALANCE'] = $BALANCE;
                    $BILLING_LEDGER_DATA['IS_PAID'] = ($allEnrollmentCharges->fields['status'] == 'Paid') ? 1 : 0;
                    $BILLING_LEDGER_DATA['STATUS'] = 'A';
                    $BILLING_LEDGER_DATA['IS_DOWN_PAYMENT'] = $IS_DOWN_PAYMENT;
                    db_perform_account('DOA_ENROLLMENT_LEDGER', $BILLING_LEDGER_DATA, 'insert');
                    $PK_ENROLLMENT_LEDGER = $db_account->insert_ID();

                    $enrollment_payment = getAllEnrollmentPaymentByChargeId($allEnrollmentCharges->fields['id'], 0);
                    if ($enrollment_payment->RecordCount() > 0) {
                        $TOTAL_PAID_AMOUNT = 0;
                        while (!$enrollment_payment->EOF) {
                            $orgDate = $enrollment_payment->fields['date_paid'];
                            $newDate = date("Y-m-d", strtotime($orgDate));

                            /*$PAYMENT_LEDGER_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
                            $PAYMENT_LEDGER_DATA['PK_ENROLLMENT_BILLING '] = $PK_ENROLLMENT_BILLING;
                            $PAYMENT_LEDGER_DATA['TRANSACTION_TYPE'] = $enrollment_payment->fields['record_type'];
                            $PAYMENT_LEDGER_DATA['ENROLLMENT_LEDGER_PARENT'] = $ENROLLMENT_LEDGER_PARENT;
                            $PAYMENT_LEDGER_DATA['DUE_DATE'] = $newDate;
                            $PAYMENT_LEDGER_DATA['BILLED_AMOUNT'] = 0;
                            $PAYMENT_LEDGER_DATA['PAID_AMOUNT'] = abs($enrollment_payment->fields['amount_paid']);
                            $PAYMENT_LEDGER_DATA['BALANCE'] = 0;
                            $PAYMENT_LEDGER_DATA['IS_PAID'] = ($enrollment_payment->fields['record_type'] === 'Payment') ? 1 : 2;
                            $PAYMENT_LEDGER_DATA['STATUS'] = 'A';
                            db_perform_account('DOA_ENROLLMENT_LEDGER', $PAYMENT_LEDGER_DATA, 'insert');
                            $PK_ENROLLMENT_LEDGER = $db_account->insert_ID();*/

                            if ($enrollment_payment->fields['payment_method'] == 'Save Card' || $enrollment_payment->fields['payment_method'] == 'Charge') {
                                $payment_type = $enrollment_payment->fields['card_type'];
                            } else {
                                $payment_type = $enrollment_payment->fields['payment_method'];
                            }
                            $PK_PAYMENT_TYPE = $db->Execute("SELECT PK_PAYMENT_TYPE FROM DOA_PAYMENT_TYPE WHERE PAYMENT_TYPE = '$payment_type'");

                            $ENROLLMENT_PAYMENT_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
                            $ENROLLMENT_PAYMENT_DATA['PK_ENROLLMENT_BILLING'] = $PK_ENROLLMENT_BILLING;
                            $ENROLLMENT_PAYMENT_DATA['PK_PAYMENT_TYPE'] = ($PK_PAYMENT_TYPE->RecordCount() > 0) ? $PK_PAYMENT_TYPE->fields['PK_PAYMENT_TYPE'] : 0;
                            $ENROLLMENT_PAYMENT_DATA['PK_ENROLLMENT_LEDGER'] = $PK_ENROLLMENT_LEDGER;
                            $ENROLLMENT_PAYMENT_DATA['TYPE'] = $enrollment_payment->fields['record_type'];
                            $ENROLLMENT_PAYMENT_DATA['AMOUNT'] = abs($enrollment_payment->fields['amount_paid']);
                            $ENROLLMENT_PAYMENT_DATA['NOTE'] = $enrollment_payment->fields['title'];
                            $ENROLLMENT_PAYMENT_DATA['PAYMENT_DATE'] = $newDate;
                            $PAYMENT_INFO = null;
                            if ($enrollment_payment->fields['card_number'] > 0) {
                                $PAYMENT_INFO_ARRAY = ['CHARGE_ID' => $enrollment_payment->fields['transaction_id'], 'LAST4' => $enrollment_payment->fields['card_number']];
                                $PAYMENT_INFO = json_encode($PAYMENT_INFO_ARRAY);
                            } elseif ($enrollment_payment->fields['check_number'] > 0) {
                                $PAYMENT_INFO_ARRAY = ['CHECK_NUMBER' => $enrollment_payment->fields['check_number'], 'CHECK_DATE' => $newDate];
                                $PAYMENT_INFO = json_encode($PAYMENT_INFO_ARRAY);
                            }
                            $ENROLLMENT_PAYMENT_DATA['PAYMENT_INFO'] = $PAYMENT_INFO;;
                            $ENROLLMENT_PAYMENT_DATA['PAYMENT_STATUS'] = 'Success';
                            $ENROLLMENT_PAYMENT_DATA['RECEIPT_NUMBER'] = $enrollment_payment->fields['receipt'];

                            $enrollmentServiceData = $db_account->Execute("SELECT FINAL_AMOUNT, TOTAL_AMOUNT_PAID, PK_ENROLLMENT_SERVICE FROM `DOA_ENROLLMENT_SERVICE` WHERE `PK_ENROLLMENT_MASTER` = " . $PK_ENROLLMENT_MASTER);
                            while (!$enrollmentServiceData->EOF) {
                                if ($enrollmentServiceData->fields['FINAL_AMOUNT'] > 0 && $TOTAL_AMOUNT > 0) {
                                    $servicePercent = ($enrollmentServiceData->fields['FINAL_AMOUNT'] * 100) / $TOTAL_AMOUNT;
                                    $serviceAmount = ($ENROLLMENT_PAYMENT_DATA['AMOUNT'] * $servicePercent) / 100;

                                    if ($enrollment_payment->fields['record_type'] === 'Payment' || $enrollment_payment->fields['record_type'] === 'Adjustment') {
                                        $ENROLLMENT_SERVICE_UPDATE_DATA['TOTAL_AMOUNT_PAID'] = $enrollmentServiceData->fields['TOTAL_AMOUNT_PAID'] + $serviceAmount;
                                    } elseif ($enrollment_payment->fields['record_type'] === 'Refund') {
                                        $ENROLLMENT_SERVICE_UPDATE_DATA['TOTAL_AMOUNT_PAID'] = $enrollmentServiceData->fields['TOTAL_AMOUNT_PAID'] - $serviceAmount;
                                    } else {
                                        $ENROLLMENT_SERVICE_UPDATE_DATA['TOTAL_AMOUNT_PAID'] = $enrollmentServiceData->fields['TOTAL_AMOUNT_PAID'];
                                    }
                                    db_perform_account('DOA_ENROLLMENT_SERVICE', $ENROLLMENT_SERVICE_UPDATE_DATA, 'update', " PK_ENROLLMENT_SERVICE = " . $enrollmentServiceData->fields['PK_ENROLLMENT_SERVICE']);
                                }

                                $enrollmentServiceData->MoveNext();
                            }
                            db_perform_account('DOA_ENROLLMENT_PAYMENT', $ENROLLMENT_PAYMENT_DATA, 'insert');

                            if ($ENROLLMENT_PAYMENT_DATA['TYPE'] == 'Payment' || $ENROLLMENT_PAYMENT_DATA['TYPE'] == 'Adjustment') {
                                $TOTAL_PAID_AMOUNT += $ENROLLMENT_PAYMENT_DATA['AMOUNT'];
                                if ($TOTAL_PAID_AMOUNT < $BILLED_AMOUNT) {
                                    $LEDGER_UPDATE_DATA['AMOUNT_REMAIN'] = $BILLED_AMOUNT - $TOTAL_PAID_AMOUNT;
                                    $LEDGER_UPDATE_DATA['IS_PAID'] = 0;
                                } elseif ($TOTAL_PAID_AMOUNT == $BILLED_AMOUNT) {
                                    $LEDGER_UPDATE_DATA['AMOUNT_REMAIN'] = 0;
                                    $LEDGER_UPDATE_DATA['IS_PAID'] = 1;
                                }
                                db_perform_account('DOA_ENROLLMENT_LEDGER', $LEDGER_UPDATE_DATA, 'update', ' PK_ENROLLMENT_LEDGER = ' . $PK_ENROLLMENT_LEDGER);
                            }
                            $enrollment_payment->MoveNext();
                        }
                    }
                    $allEnrollmentCharges->MoveNext();
                }
                $allEnrollments->MoveNext();
            }
            break;

        case "DOA_SPECIAL_APPOINTMENT":
            $lastUploadedId = getLastUploadedID($PK_ACCOUNT_MASTER, $PK_LOCATION, $_SESSION['MIGRATION_DB_NAME'], $_POST['TABLE_NAME']);

            $lastUploadedData = getLastGeneralAppt();
            $lastId = $lastUploadedData->fields['last_id'];
            insertLastUploadedID($PK_ACCOUNT_MASTER, $PK_LOCATION, $_SESSION['MIGRATION_DB_NAME'], $_POST['TABLE_NAME'], $lastId);

            $allSpecialAppointment = getAllGeneralAppt($lastUploadedId);
            while (!$allSpecialAppointment->EOF) {
                $header = $allSpecialAppointment->fields['appt_name'];
                $start_date = $allSpecialAppointment->fields['appt_date'];
                $start_time = $allSpecialAppointment->fields['appt_time'];
                /*$table_data = $db_account->Execute("SELECT PK_SPECIAL_APPOINTMENT FROM DOA_SPECIAL_APPOINTMENT WHERE TITLE = '$header' AND DATE = '$start_date' AND START_TIME = '$start_time'");
                if ($table_data->RecordCount() == 0) {*/

                $INSERT_DATA['PK_LOCATION'] = $PK_LOCATION;
                $INSERT_DATA['TITLE'] = $header;
                $INSERT_DATA['DATE'] = $start_date;
                $INSERT_DATA['START_TIME'] = $start_time;
                $duration = $allSpecialAppointment->fields['duration'];
                $endDateTime = strtotime($start_date . ' ' . $start_time) + ($duration * 60);
                $convertedDate = date('Y-m-d', $endDateTime);
                $convertedTime = date('H:i:s', $endDateTime);
                $INSERT_DATA['END_TIME'] = $convertedTime;
                $INSERT_DATA['DESCRIPTION'] = $allSpecialAppointment->fields['appts_comment'];

                $booking_code = $allSpecialAppointment->fields['booking_code'];
                $getServiceCodeId = $db_account->Execute("SELECT PK_SCHEDULING_CODE FROM DOA_SCHEDULING_CODE WHERE SCHEDULING_CODE = '$booking_code'");
                if ($getServiceCodeId->RecordCount() > 0) {
                    $PK_SCHEDULING_CODE = $getServiceCodeId->fields['PK_SCHEDULING_CODE'];
                } else {
                    $PK_SCHEDULING_CODE = 0;
                }
                $INSERT_DATA['PK_SCHEDULING_CODE'] = $PK_SCHEDULING_CODE;

                if ($start_date > date('Y-m-d')) {
                    $INSERT_DATA['PK_APPOINTMENT_STATUS'] = 1;
                } else {
                    $INSERT_DATA['PK_APPOINTMENT_STATUS'] = 2;
                }

                if ($allSpecialAppointment->fields['appt_status'] == "A") {
                    $INSERT_DATA['ACTIVE'] = 1;
                } else {
                    $INSERT_DATA['ACTIVE'] = 0;
                }

                $created_by = explode(" ", $allSpecialAppointment->fields['created_by']);
                $firstName = ($created_by[0]) ?: '';
                $lastName = ($created_by[1]) ?: '';
                $doableNameId = $db->Execute("SELECT PK_USER FROM DOA_USERS WHERE PK_ACCOUNT_MASTER = '$PK_ACCOUNT_MASTER' AND FIRST_NAME='$firstName' AND LAST_NAME = '$lastName'");
                $INSERT_DATA['CREATED_BY'] = $doableNameId->fields['PK_USER'];
                $INSERT_DATA['CREATED_ON'] = date("Y-m-d H:i");
                db_perform_account('DOA_SPECIAL_APPOINTMENT', $INSERT_DATA, 'insert');
                $PK_SPECIAL_APPOINTMENT = $db_account->insert_ID();

                /*} else {
                    $PK_SPECIAL_APPOINTMENT = $table_data->fields['PK_SPECIAL_APPOINTMENT'];
                }*/

                $user_id = $allSpecialAppointment->fields['user_id'];
                $doableUserId = $db->Execute("SELECT PK_USER FROM DOA_USERS WHERE PK_ACCOUNT_MASTER = '$PK_ACCOUNT_MASTER' AND USER_ID = '$user_id'");
                $SPECIAL_APPOINTMENT_USER['PK_SPECIAL_APPOINTMENT'] = $PK_SPECIAL_APPOINTMENT;
                $SPECIAL_APPOINTMENT_USER['PK_USER'] = ($doableUserId->RecordCount() > 0) ? $doableUserId->fields['PK_USER'] : 0;
                db_perform_account('DOA_SPECIAL_APPOINTMENT_USER', $SPECIAL_APPOINTMENT_USER, 'insert');

                $allSpecialAppointment->Movenext();
            }
            break;

        case "DOA_APPOINTMENT_MASTER":
            $lastUploadedId = getLastUploadedID($PK_ACCOUNT_MASTER, $PK_LOCATION, $_SESSION['MIGRATION_DB_NAME'], $_POST['TABLE_NAME']);

            $lastUploadedData = getLastAppointment();
            $lastId = $lastUploadedData->fields['last_id'];
            insertLastUploadedID($PK_ACCOUNT_MASTER, $PK_LOCATION, $_SESSION['MIGRATION_DB_NAME'], $_POST['TABLE_NAME'], $lastId);

            $allCustomers = getAllCustomersID();
            while (!$allCustomers->EOF) {
                $customer_id = $allCustomers->fields['customer_id'];
                $allPrivateAppointments = getAllPrivateAppointmentsByCustomerId($customer_id, $lastUploadedId);
                while (!$allPrivateAppointments->EOF) {
                    $studentId = $allPrivateAppointments->fields['student_id'];
                    $doableCustomerId = $db->Execute("SELECT DOA_USER_MASTER.PK_USER_MASTER FROM DOA_USER_MASTER INNER JOIN DOA_USERS ON DOA_USER_MASTER.PK_USER = DOA_USERS.PK_USER WHERE DOA_USERS.USER_ID = '$studentId' AND DOA_USER_MASTER.PRIMARY_LOCATION_ID = '$PK_LOCATION' AND DOA_USER_MASTER.PK_ACCOUNT_MASTER = '$PK_ACCOUNT_MASTER'");
                    $PK_USER_MASTER = ($doableCustomerId->RecordCount() > 0) ? $doableCustomerId->fields['PK_USER_MASTER'] : 0;

                    $service_id = $allPrivateAppointments->fields['service_id'];
                    $doableServiceId = $db_account->Execute("SELECT PK_SERVICE_MASTER, PK_SERVICE_CODE, DESCRIPTION FROM DOA_SERVICE_CODE WHERE SERVICE_CODE ='$service_id'");
                    $PK_SERVICE_MASTER = ($doableServiceId->RecordCount() > 0) ? $doableServiceId->fields['PK_SERVICE_MASTER'] : 0;
                    $PK_SERVICE_CODE = ($doableServiceId->RecordCount() > 0) ? $doableServiceId->fields['PK_SERVICE_CODE'] : 0;

                    $booking_code = $allPrivateAppointments->fields['booking_code'];
                    $getServiceCodeId = $db_account->Execute("SELECT PK_SCHEDULING_CODE FROM DOA_SCHEDULING_CODE WHERE SCHEDULING_CODE = '$booking_code'");
                    $PK_SCHEDULING_CODE = ($getServiceCodeId->RecordCount() > 0) ? $getServiceCodeId->fields['PK_SCHEDULING_CODE'] : 0;

                    $enrollment_data = $db_account->Execute("SELECT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_SERVICE, DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION FROM DOA_ENROLLMENT_MASTER INNER JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER WHERE (DOA_ENROLLMENT_MASTER.PK_USER_MASTER = '$PK_USER_MASTER') AND DOA_ENROLLMENT_SERVICE.PK_SERVICE_MASTER = '$PK_SERVICE_MASTER' AND DOA_ENROLLMENT_MASTER.PK_LOCATION = '$PK_LOCATION' AND DOA_ENROLLMENT_MASTER.ALL_APPOINTMENT_DONE = 0 ORDER BY DOA_ENROLLMENT_MASTER.ENROLLMENT_DATE ASC LIMIT 1");
                    $PK_ENROLLMENT_MASTER_CHECK = ($enrollment_data->RecordCount() > 0) ? $enrollment_data->fields['PK_ENROLLMENT_MASTER'] : 0;
                    $PK_ENROLLMENT_SERVICE_CHECK = ($enrollment_data->RecordCount() > 0) ? $enrollment_data->fields['PK_ENROLLMENT_SERVICE'] : 0;
                    $SESSION_COUNT = ($enrollment_data->RecordCount() > 0) ? $enrollment_data->fields['NUMBER_OF_SESSION'] : 0;

                    if ($PK_ENROLLMENT_MASTER_CHECK > 0 && $PK_ENROLLMENT_SERVICE_CHECK > 0) {
                        $SESSION_CREATED = getSessionCountForMigration($PK_ENROLLMENT_SERVICE_CHECK, 'NORMAL');
                        if (($SESSION_CREATED > 0) && ($SESSION_CREATED >= $SESSION_COUNT)) {
                            $db_account->Execute("UPDATE `DOA_ENROLLMENT_MASTER` SET `ALL_APPOINTMENT_DONE` = '1' WHERE PK_ENROLLMENT_MASTER = '$PK_ENROLLMENT_MASTER_CHECK'");
                            $new_enrollment_data = $db_account->Execute("SELECT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_SERVICE, DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION FROM DOA_ENROLLMENT_MASTER INNER JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER WHERE (DOA_ENROLLMENT_MASTER.PK_USER_MASTER = '$PK_USER_MASTER') AND DOA_ENROLLMENT_SERVICE.PK_SERVICE_MASTER = '$PK_SERVICE_MASTER' AND DOA_ENROLLMENT_MASTER.PK_LOCATION = '$PK_LOCATION' AND DOA_ENROLLMENT_MASTER.ALL_APPOINTMENT_DONE = 0 ORDER BY DOA_ENROLLMENT_MASTER.ENROLLMENT_DATE ASC LIMIT 1");
                            $PK_ENROLLMENT_MASTER_NEW = ($new_enrollment_data->RecordCount() > 0) ? $new_enrollment_data->fields['PK_ENROLLMENT_MASTER'] : 0;
                            $PK_ENROLLMENT_SERVICE_NEW = ($new_enrollment_data->RecordCount() > 0) ? $new_enrollment_data->fields['PK_ENROLLMENT_SERVICE'] : 0;
                            $PK_ENROLLMENT_MASTER = $PK_ENROLLMENT_MASTER_NEW;
                            $PK_ENROLLMENT_SERVICE = $PK_ENROLLMENT_SERVICE_NEW;
                        } else {
                            $PK_ENROLLMENT_MASTER = $PK_ENROLLMENT_MASTER_CHECK;
                            $PK_ENROLLMENT_SERVICE = $PK_ENROLLMENT_SERVICE_CHECK;
                        }

                        //[$PK_ENROLLMENT_MASTER, $PK_ENROLLMENT_SERVICE] = checkSessionCount($PK_LOCATION, $SESSION_COUNT, $PK_ENROLLMENT_MASTER_CHECK, $PK_ENROLLMENT_SERVICE_CHECK, $PK_USER_MASTER, $PK_SERVICE_MASTER, 'NORMAL');
                    } else {
                        $PK_ENROLLMENT_MASTER = 0;
                        $PK_ENROLLMENT_SERVICE = 0;
                    }

                    $APPOINTMENT_MASTER_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
                    $APPOINTMENT_MASTER_DATA['PK_ENROLLMENT_SERVICE'] = $PK_ENROLLMENT_SERVICE;
                    $APPOINTMENT_MASTER_DATA['PK_SERVICE_MASTER'] = $PK_SERVICE_MASTER;
                    $APPOINTMENT_MASTER_DATA['PK_SERVICE_CODE'] = $PK_SERVICE_CODE;
                    $APPOINTMENT_MASTER_DATA['PK_SCHEDULING_CODE'] = $PK_SCHEDULING_CODE;
                    $APPOINTMENT_MASTER_DATA['PK_LOCATION'] = $PK_LOCATION;
                    $APPOINTMENT_MASTER_DATA['DATE'] = $allPrivateAppointments->fields['appt_date'];
                    $APPOINTMENT_MASTER_DATA['START_TIME'] = $allPrivateAppointments->fields['appt_time'];
                    $endTime = strtotime($allPrivateAppointments->fields['appt_time']) + $allPrivateAppointments->fields['duration'] * 60;
                    $convertedTime = date('H:i:s', $endTime);
                    $APPOINTMENT_MASTER_DATA['END_TIME'] = $convertedTime;
                    $APPOINTMENT_MASTER_DATA['IS_CHARGED'] = 0;

                    $appt_status = $allPrivateAppointments->fields['appt_status'];
                    if ($appt_status == "A") {
                        $APPOINTMENT_MASTER_DATA['PK_APPOINTMENT_STATUS'] = 1;
                    } elseif ($appt_status == "C") {
                        $APPOINTMENT_MASTER_DATA['PK_APPOINTMENT_STATUS'] = 6;
                    } elseif ($appt_status == "CM") {
                        $APPOINTMENT_MASTER_DATA['PK_APPOINTMENT_STATUS'] = 7;
                    } elseif ($appt_status == "I") {
                        $APPOINTMENT_MASTER_DATA['PK_APPOINTMENT_STATUS'] = 6;
                    } elseif ($appt_status == "N") {
                        $APPOINTMENT_MASTER_DATA['PK_APPOINTMENT_STATUS'] = 1;
                    } elseif ($appt_status == "NS") {
                        $APPOINTMENT_MASTER_DATA['PK_APPOINTMENT_STATUS'] = 4;
                    } elseif ($appt_status == "O") {
                        $APPOINTMENT_MASTER_DATA['PK_APPOINTMENT_STATUS'] = 6;
                    } elseif ($appt_status == "S") {
                        $APPOINTMENT_MASTER_DATA['PK_APPOINTMENT_STATUS'] = 2;
                        $APPOINTMENT_MASTER_DATA['IS_CHARGED'] = 1;
                    }
                    $APPOINTMENT_MASTER_DATA['INTERNAL_COMMENT'] = $allPrivateAppointments->fields['appts_comment'];
                    $APPOINTMENT_MASTER_DATA['GROUP_NAME'] = null;

                    if ($PK_ENROLLMENT_MASTER == 0 || $PK_ENROLLMENT_SERVICE == 0) {
                        $APPOINTMENT_MASTER_DATA['APPOINTMENT_TYPE'] = 'AD-HOC';
                    } else {
                        $APPOINTMENT_MASTER_DATA['APPOINTMENT_TYPE'] = 'NORMAL';
                    }

                    $APPOINTMENT_MASTER_DATA['SERIAL_NUMBER'] = getAppointmentSerialNumber($PK_USER_MASTER);
                    $APPOINTMENT_MASTER_DATA['ACTIVE'] = 1;
                    $APPOINTMENT_MASTER_DATA['IS_PAID'] = 0;

                    $APPOINTMENT_MASTER_DATA['CREATED_BY'] = $PK_ACCOUNT_MASTER;
                    $APPOINTMENT_MASTER_DATA['CREATED_ON'] = date("Y-m-d H:i");
                    db_perform_account('DOA_APPOINTMENT_MASTER', $APPOINTMENT_MASTER_DATA, 'insert');
                    $PK_APPOINTMENT_MASTER = $db_account->insert_ID();

                    $INSERT_DATA_CUSTOMER['PK_APPOINTMENT_MASTER'] = $PK_APPOINTMENT_MASTER;
                    $INSERT_DATA_CUSTOMER['PK_USER_MASTER'] = $PK_USER_MASTER;
                    db_perform_account('DOA_APPOINTMENT_CUSTOMER', $INSERT_DATA_CUSTOMER, 'insert');


                    $user_id = $allPrivateAppointments->fields['user_id'];
                    $doableServiceProviderId = $db->Execute("SELECT DOA_USERS.PK_USER FROM DOA_USERS INNER JOIN DOA_USER_LOCATION ON DOA_USER_LOCATION.PK_USER = DOA_USERS.PK_USER WHERE DOA_USERS.USER_ID = '$user_id' AND DOA_USER_LOCATION.PK_LOCATION = '$PK_LOCATION' AND DOA_USERS.PK_ACCOUNT_MASTER = '$PK_ACCOUNT_MASTER'");
                    $SERVICE_PROVIDER_ID = ($doableServiceProviderId->RecordCount() > 0) ? $doableServiceProviderId->fields['PK_USER'] : 0;
                    $INSERT_DATA_SERVICE_PROVIDER['PK_APPOINTMENT_MASTER'] = $PK_APPOINTMENT_MASTER;
                    $INSERT_DATA_SERVICE_PROVIDER['PK_USER'] = $SERVICE_PROVIDER_ID;
                    db_perform_account('DOA_APPOINTMENT_SERVICE_PROVIDER', $INSERT_DATA_SERVICE_PROVIDER, 'insert');

                    $allPrivateAppointments->Movenext();
                }
                $allCustomers->MoveNext();
            }

            $allDemoAppointments = getDemoAppointments($lastUploadedId);
            while (!$allDemoAppointments->EOF) {
                $studentId = $allDemoAppointments->fields['student_id'];
                $doableCustomerId = $db->Execute("SELECT DOA_USER_MASTER.PK_USER_MASTER FROM DOA_USER_MASTER INNER JOIN DOA_USERS ON DOA_USER_MASTER.PK_USER = DOA_USERS.PK_USER WHERE DOA_USERS.USER_ID = '$studentId' AND DOA_USER_MASTER.PRIMARY_LOCATION_ID = '$PK_LOCATION' AND DOA_USER_MASTER.PK_ACCOUNT_MASTER = '$PK_ACCOUNT_MASTER'");
                $PK_USER_MASTER = ($doableCustomerId->RecordCount() > 0) ? $doableCustomerId->fields['PK_USER_MASTER'] : 0;

                $service_id = $allDemoAppointments->fields['service_id'];
                $doableServiceId = $db_account->Execute("SELECT PK_SERVICE_MASTER, PK_SERVICE_CODE, DESCRIPTION FROM DOA_SERVICE_CODE WHERE SERVICE_CODE ='$service_id'");
                $PK_SERVICE_MASTER = ($doableServiceId->RecordCount() > 0) ? $doableServiceId->fields['PK_SERVICE_MASTER'] : 0;
                $PK_SERVICE_CODE = ($doableServiceId->RecordCount() > 0) ? $doableServiceId->fields['PK_SERVICE_CODE'] : 0;

                $booking_code = $allDemoAppointments->fields['booking_code'];
                $getServiceCodeId = $db_account->Execute("SELECT PK_SCHEDULING_CODE FROM DOA_SCHEDULING_CODE WHERE SCHEDULING_CODE = '$booking_code'");
                $PK_SCHEDULING_CODE = ($getServiceCodeId->RecordCount() > 0) ? $getServiceCodeId->fields['PK_SCHEDULING_CODE'] : 0;

                $DEMO_CLASS_DATA['PK_ENROLLMENT_MASTER'] = 0;
                $DEMO_CLASS_DATA['PK_ENROLLMENT_SERVICE'] = 0;
                $DEMO_CLASS_DATA['PK_SERVICE_MASTER'] = $PK_SERVICE_MASTER;
                $DEMO_CLASS_DATA['PK_SERVICE_CODE'] = $PK_SERVICE_CODE;
                $DEMO_CLASS_DATA['PK_SCHEDULING_CODE'] = $PK_SCHEDULING_CODE;
                $DEMO_CLASS_DATA['PK_LOCATION'] = $PK_LOCATION;
                $DEMO_CLASS_DATA['DATE'] = $allDemoAppointments->fields['appt_date'];
                $DEMO_CLASS_DATA['START_TIME'] = $allDemoAppointments->fields['appt_time'];
                $endTime = strtotime($allDemoAppointments->fields['appt_time']) + $allDemoAppointments->fields['duration'] * 60;
                $convertedTime = date('H:i:s', $endTime);
                $DEMO_CLASS_DATA['END_TIME'] = $convertedTime;
                $DEMO_CLASS_DATA['IS_CHARGED'] = 0;

                $appt_status = $allDemoAppointments->fields['appt_status'];
                if ($appt_status == "A") {
                    $DEMO_CLASS_DATA['PK_APPOINTMENT_STATUS'] = 1;
                } elseif ($appt_status == "C") {
                    $DEMO_CLASS_DATA['PK_APPOINTMENT_STATUS'] = 6;
                } elseif ($appt_status == "CM") {
                    $DEMO_CLASS_DATA['PK_APPOINTMENT_STATUS'] = 7;
                } elseif ($appt_status == "I") {
                    $DEMO_CLASS_DATA['PK_APPOINTMENT_STATUS'] = 6;
                } elseif ($appt_status == "N") {
                    $DEMO_CLASS_DATA['PK_APPOINTMENT_STATUS'] = 1;
                } elseif ($appt_status == "NS") {
                    $DEMO_CLASS_DATA['PK_APPOINTMENT_STATUS'] = 4;
                } elseif ($appt_status == "O") {
                    $DEMO_CLASS_DATA['PK_APPOINTMENT_STATUS'] = 6;
                } elseif ($appt_status == "S") {
                    $DEMO_CLASS_DATA['PK_APPOINTMENT_STATUS'] = 2;
                }
                $DEMO_CLASS_DATA['INTERNAL_COMMENT'] = $allDemoAppointments->fields['appts_comment'];
                $DEMO_CLASS_DATA['GROUP_NAME'] = null;
                $DEMO_CLASS_DATA['APPOINTMENT_TYPE'] = 'DEMO';

                $DEMO_CLASS_DATA['SERIAL_NUMBER'] = getAppointmentSerialNumber($PK_USER_MASTER);
                $DEMO_CLASS_DATA['ACTIVE'] = 1;
                if ($allDemoAppointments->fields['payment_status'] == "V") {
                    $DEMO_CLASS_DATA['IS_PAID'] = 1;
                } elseif ($allDemoAppointments->fields['payment_status'] == "U") {
                    $DEMO_CLASS_DATA['IS_PAID'] = 0;
                }
                $DEMO_CLASS_DATA['CREATED_BY'] = $PK_ACCOUNT_MASTER;
                $DEMO_CLASS_DATA['CREATED_ON'] = date("Y-m-d H:i");
                db_perform_account('DOA_APPOINTMENT_MASTER', $DEMO_CLASS_DATA, 'insert');
                $PK_APPOINTMENT_MASTER = $db_account->insert_ID();

                $INSERT_DATA_CUSTOMER_DEMO['PK_APPOINTMENT_MASTER'] = $PK_APPOINTMENT_MASTER;
                $INSERT_DATA_CUSTOMER_DEMO['PK_USER_MASTER'] = $PK_USER_MASTER;
                db_perform_account('DOA_APPOINTMENT_CUSTOMER', $INSERT_DATA_CUSTOMER_DEMO, 'insert');

                $user_id = $allDemoAppointments->fields['user_id'];
                $doableServiceProviderId = $db->Execute("SELECT DOA_USERS.PK_USER FROM DOA_USERS INNER JOIN DOA_USER_LOCATION ON DOA_USER_LOCATION.PK_USER = DOA_USERS.PK_USER WHERE DOA_USERS.USER_ID = '$user_id' AND DOA_USER_LOCATION.PK_LOCATION = '$PK_LOCATION' AND DOA_USERS.PK_ACCOUNT_MASTER = '$PK_ACCOUNT_MASTER'");
                $SERVICE_PROVIDER_ID = ($doableServiceProviderId->RecordCount() > 0) ? $doableServiceProviderId->fields['PK_USER'] : 0;
                $INSERT_DATA_SERVICE_PROVIDER['PK_APPOINTMENT_MASTER'] = $PK_APPOINTMENT_MASTER;
                $INSERT_DATA_SERVICE_PROVIDER['PK_USER'] = $SERVICE_PROVIDER_ID;
                db_perform_account('DOA_APPOINTMENT_SERVICE_PROVIDER', $INSERT_DATA_SERVICE_PROVIDER, 'insert');

                $allDemoAppointments->Movenext();
            }

            $allGroupAppointments = getAllGroupAppointments($lastUploadedId);
            while (!$allGroupAppointments->EOF) {
                $studentId = $allGroupAppointments->fields['student_id'];
                $doableCustomerId = $db->Execute("SELECT DOA_USER_MASTER.PK_USER_MASTER FROM DOA_USER_MASTER INNER JOIN DOA_USERS ON DOA_USER_MASTER.PK_USER = DOA_USERS.PK_USER WHERE DOA_USERS.USER_ID = '$studentId' AND DOA_USER_MASTER.PRIMARY_LOCATION_ID = '$PK_LOCATION' AND DOA_USER_MASTER.PK_ACCOUNT_MASTER = '$PK_ACCOUNT_MASTER'");
                $PK_USER_MASTER = ($doableCustomerId->RecordCount() > 0) ? $doableCustomerId->fields['PK_USER_MASTER'] : 0;

                $service_id = $allGroupAppointments->fields['service_id'];
                $doableServiceId = $db_account->Execute("SELECT PK_SERVICE_MASTER, PK_SERVICE_CODE, DESCRIPTION FROM DOA_SERVICE_CODE WHERE SERVICE_CODE ='$service_id'");
                $PK_SERVICE_MASTER = ($doableServiceId->RecordCount() > 0) ? $doableServiceId->fields['PK_SERVICE_MASTER'] : 0;
                $PK_SERVICE_CODE = ($doableServiceId->RecordCount() > 0) ? $doableServiceId->fields['PK_SERVICE_CODE'] : 0;

                $booking_code = $allGroupAppointments->fields['booking_code'];
                $getServiceCodeId = $db_account->Execute("SELECT PK_SCHEDULING_CODE FROM DOA_SCHEDULING_CODE WHERE SCHEDULING_CODE = '$booking_code'");
                $PK_SCHEDULING_CODE = ($getServiceCodeId->RecordCount() > 0) ? $getServiceCodeId->fields['PK_SCHEDULING_CODE'] : 0;

                $enrollment_data = $db_account->Execute("SELECT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_SERVICE, DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION FROM DOA_ENROLLMENT_MASTER INNER JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_USER_MASTER = '$PK_USER_MASTER' AND DOA_ENROLLMENT_SERVICE.PK_SERVICE_MASTER = '$PK_SERVICE_MASTER' AND DOA_ENROLLMENT_MASTER.PK_LOCATION = '$PK_LOCATION' AND DOA_ENROLLMENT_MASTER.ALL_APPOINTMENT_DONE = 0 ORDER BY DOA_ENROLLMENT_MASTER.ENROLLMENT_DATE ASC LIMIT 1");
                $PK_ENROLLMENT_MASTER_CHECK = ($enrollment_data->RecordCount() > 0) ? $enrollment_data->fields['PK_ENROLLMENT_MASTER'] : 0;
                $PK_ENROLLMENT_SERVICE_CHECK = ($enrollment_data->RecordCount() > 0) ? $enrollment_data->fields['PK_ENROLLMENT_SERVICE'] : 0;
                $SESSION_COUNT = ($enrollment_data->RecordCount() > 0) ? $enrollment_data->fields['NUMBER_OF_SESSION'] : 0;

                if ($PK_ENROLLMENT_MASTER_CHECK > 0 && $PK_ENROLLMENT_SERVICE_CHECK > 0) {
                    $SESSION_CREATED = getSessionCountForMigration($PK_ENROLLMENT_SERVICE_CHECK, 'GROUP');
                    if (($SESSION_CREATED > 0) && ($SESSION_CREATED >= $SESSION_COUNT)) {
                        $db_account->Execute("UPDATE `DOA_ENROLLMENT_MASTER` SET `ALL_APPOINTMENT_DONE` = '1' WHERE PK_ENROLLMENT_MASTER = '$PK_ENROLLMENT_MASTER_CHECK'");
                        $new_enrollment_data = $db_account->Execute("SELECT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_SERVICE, DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION FROM DOA_ENROLLMENT_MASTER INNER JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_USER_MASTER = '$PK_USER_MASTER' AND DOA_ENROLLMENT_SERVICE.PK_SERVICE_MASTER = '$PK_SERVICE_MASTER' AND DOA_ENROLLMENT_MASTER.PK_LOCATION = '$PK_LOCATION' AND DOA_ENROLLMENT_MASTER.ALL_APPOINTMENT_DONE = 0 ORDER BY DOA_ENROLLMENT_MASTER.ENROLLMENT_DATE ASC LIMIT 1");
                        $PK_ENROLLMENT_MASTER_NEW = ($new_enrollment_data->RecordCount() > 0) ? $new_enrollment_data->fields['PK_ENROLLMENT_MASTER'] : 0;
                        $PK_ENROLLMENT_SERVICE_NEW = ($new_enrollment_data->RecordCount() > 0) ? $new_enrollment_data->fields['PK_ENROLLMENT_SERVICE'] : 0;
                        $PK_ENROLLMENT_MASTER = $PK_ENROLLMENT_MASTER_NEW;
                        $PK_ENROLLMENT_SERVICE = $PK_ENROLLMENT_SERVICE_NEW;
                    } else {
                        $PK_ENROLLMENT_MASTER = $PK_ENROLLMENT_MASTER_CHECK;
                        $PK_ENROLLMENT_SERVICE = $PK_ENROLLMENT_SERVICE_CHECK;
                    }

                    //[$PK_ENROLLMENT_MASTER, $PK_ENROLLMENT_SERVICE] = checkSessionCount($PK_LOCATION, $SESSION_COUNT, $PK_ENROLLMENT_MASTER_CHECK, $PK_ENROLLMENT_SERVICE_CHECK, $PK_USER_MASTER, $PK_SERVICE_MASTER, 'GROUP');
                } else {
                    $PK_ENROLLMENT_MASTER = 0;
                    $PK_ENROLLMENT_SERVICE = 0;
                }

                $GROUP_CLASS_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
                $GROUP_CLASS_DATA['PK_ENROLLMENT_SERVICE'] = $PK_ENROLLMENT_SERVICE;
                $GROUP_CLASS_DATA['PK_SERVICE_MASTER'] = $PK_SERVICE_MASTER;
                $GROUP_CLASS_DATA['PK_SERVICE_CODE'] = $PK_SERVICE_CODE;
                $GROUP_CLASS_DATA['PK_SCHEDULING_CODE'] = $PK_SCHEDULING_CODE;
                $GROUP_CLASS_DATA['PK_LOCATION'] = $PK_LOCATION;

                $GROUP_CLASS_DATA['DATE'] = $allGroupAppointments->fields['appt_date'];
                $GROUP_CLASS_DATA['START_TIME'] = $allGroupAppointments->fields['appt_time'];
                $endTime = strtotime($allGroupAppointments->fields['appt_time']) + $allGroupAppointments->fields['duration'] * 60;
                $convertedTime = date('H:i:s', $endTime);
                $GROUP_CLASS_DATA['END_TIME'] = $convertedTime;
                $GROUP_CLASS_DATA['IS_CHARGED'] = 0;

                $appt_status = $allGroupAppointments->fields['appt_status'];
                if ($appt_status == "A") {
                    $GROUP_CLASS_DATA['PK_APPOINTMENT_STATUS'] = 1;
                } elseif ($appt_status == "C") {
                    $GROUP_CLASS_DATA['PK_APPOINTMENT_STATUS'] = 6;
                } elseif ($appt_status == "CM") {
                    $GROUP_CLASS_DATA['PK_APPOINTMENT_STATUS'] = 7;
                } elseif ($appt_status == "I") {
                    $GROUP_CLASS_DATA['PK_APPOINTMENT_STATUS'] = 6;
                } elseif ($appt_status == "N") {
                    $GROUP_CLASS_DATA['PK_APPOINTMENT_STATUS'] = 1;
                } elseif ($appt_status == "NS") {
                    $GROUP_CLASS_DATA['PK_APPOINTMENT_STATUS'] = 4;
                } elseif ($appt_status == "O") {
                    $GROUP_CLASS_DATA['PK_APPOINTMENT_STATUS'] = 6;
                } elseif ($appt_status == "S") {
                    $GROUP_CLASS_DATA['PK_APPOINTMENT_STATUS'] = 2;
                    $GROUP_CLASS_DATA['IS_CHARGED'] = 1;
                }
                $GROUP_CLASS_DATA['INTERNAL_COMMENT'] = $allGroupAppointments->fields['appts_comment'];
                $GROUP_CLASS_DATA['GROUP_NAME'] = null;
                if ($service_id == 'PRT' || $service_id == 'GRP1' ||  $service_id == 'GRP2' || $service_id == 'GRP3') {
                    $GROUP_CLASS_DATA['GROUP_NAME'] = $allGroupAppointments->fields['appt_name'];
                    $GROUP_CLASS_DATA['APPOINTMENT_TYPE'] = 'GROUP';
                } else {
                    $GROUP_CLASS_DATA['APPOINTMENT_TYPE'] = 'AD-HOC';
                }

                $GROUP_CLASS_DATA['ACTIVE'] = 1;
                if ($allGroupAppointments->fields['payment_status'] == "V") {
                    $GROUP_CLASS_DATA['IS_PAID'] = 1;
                } elseif ($allGroupAppointments->fields['payment_status'] == "U") {
                    $GROUP_CLASS_DATA['IS_PAID'] = 0;
                }
                $GROUP_CLASS_DATA['CREATED_BY'] = $PK_ACCOUNT_MASTER;
                $GROUP_CLASS_DATA['CREATED_ON'] = date("Y-m-d H:i");
                db_perform_account('DOA_APPOINTMENT_MASTER', $GROUP_CLASS_DATA, 'insert');
                $PK_APPOINTMENT_MASTER = $db_account->insert_ID();

                if ($service_id == 'PRT' || $service_id == 'GRP1' ||  $service_id == 'GRP2' || $service_id == 'GRP3') {
                    $service_appt_id = $allGroupAppointments->fields['service_appt_id'];
                    $groupStudentIds = getAllStudentIds($service_appt_id);
                    while (!$groupStudentIds->EOF) {
                        $groupStudentId = $groupStudentIds->fields['student_id'];
                        $doableCustomerId = $db->Execute("SELECT DOA_USER_MASTER.PK_USER_MASTER FROM DOA_USER_MASTER INNER JOIN DOA_USERS ON DOA_USER_MASTER.PK_USER = DOA_USERS.PK_USER WHERE DOA_USERS.USER_ID = '$groupStudentId' AND DOA_USER_MASTER.PRIMARY_LOCATION_ID = '$PK_LOCATION' AND DOA_USER_MASTER.PK_ACCOUNT_MASTER = '$PK_ACCOUNT_MASTER'");
                        $PK_USER_MASTER_GROUP = ($doableCustomerId->RecordCount() > 0) ? $doableCustomerId->fields['PK_USER_MASTER'] : 0;
                        $INSERT_DATA_CUSTOMER['PK_APPOINTMENT_MASTER'] = $PK_APPOINTMENT_MASTER;
                        $INSERT_DATA_CUSTOMER['PK_USER_MASTER'] = $PK_USER_MASTER_GROUP;
                        db_perform_account('DOA_APPOINTMENT_CUSTOMER', $INSERT_DATA_CUSTOMER, 'insert');

                        $APPOINTMENT_ENROLLMENT_DATA['PK_APPOINTMENT_MASTER'] = $PK_APPOINTMENT_MASTER;
                        $APPOINTMENT_ENROLLMENT_DATA['PK_USER_MASTER'] = $PK_USER_MASTER_GROUP;
                        $APPOINTMENT_ENROLLMENT_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
                        $APPOINTMENT_ENROLLMENT_DATA['PK_ENROLLMENT_SERVICE'] = $PK_ENROLLMENT_SERVICE;

                        if ($groupStudentIds->fields['payment_status'] == "V") {
                            $APPOINTMENT_ENROLLMENT_DATA['IS_CHARGED'] = 1;
                        } elseif ($groupStudentIds->fields['payment_status'] == "U") {
                            $APPOINTMENT_ENROLLMENT_DATA['IS_CHARGED'] = 0;
                        }
                        db_perform_account('DOA_APPOINTMENT_ENROLLMENT', $APPOINTMENT_ENROLLMENT_DATA, 'insert');

                        /*if ($appt_status != "C") {
                            updateSessionCreatedCountGroupClass($PK_APPOINTMENT_MASTER, $PK_USER_MASTER_GROUP);
                        }
                        if($appt_status == "S") {
                            updateSessionCompletedCountGroupClass($PK_APPOINTMENT_MASTER, $PK_USER_MASTER_GROUP);
                        }*/

                        $groupStudentIds->Movenext();
                    }
                }

                $user_id = $allGroupAppointments->fields['user_id'];
                $doableServiceProviderId = $db->Execute("SELECT DOA_USERS.PK_USER FROM DOA_USERS INNER JOIN DOA_USER_LOCATION ON DOA_USER_LOCATION.PK_USER = DOA_USERS.PK_USER WHERE DOA_USERS.USER_ID = '$user_id' AND DOA_USER_LOCATION.PK_LOCATION = '$PK_LOCATION' AND DOA_USERS.PK_ACCOUNT_MASTER = '$PK_ACCOUNT_MASTER'");
                $SERVICE_PROVIDER_ID = ($doableServiceProviderId->RecordCount() > 0) ? $doableServiceProviderId->fields['PK_USER'] : 0;
                $INSERT_DATA_SERVICE_PROVIDER['PK_APPOINTMENT_MASTER'] = $PK_APPOINTMENT_MASTER;
                $INSERT_DATA_SERVICE_PROVIDER['PK_USER'] = $SERVICE_PROVIDER_ID;
                db_perform_account('DOA_APPOINTMENT_SERVICE_PROVIDER', $INSERT_DATA_SERVICE_PROVIDER, 'insert');

                $allGroupAppointments->Movenext();
            }
            break;

        case 'SP_TIME':
            $startTime = getStartTime();
            $endTime = getEndTime();

            $row = $db->Execute("SELECT DISTINCT (DOA_USERS.PK_USER) FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER LEFT JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER WHERE DOA_USER_LOCATION.PK_LOCATION IN (" . $PK_LOCATION . ") AND DOA_USER_ROLES.PK_ROLES = 5 AND DOA_USERS.ACTIVE = 1 AND DOA_USERS.IS_DELETED = 0 AND DOA_USERS.PK_ACCOUNT_MASTER = " . $PK_ACCOUNT_MASTER);
            while (!$row->EOF) {

                $SERVICE_PROVIDER_HOURS['PK_USER'] = $row->fields['PK_USER'];
                $SERVICE_PROVIDER_HOURS['PK_LOCATION'] = $PK_LOCATION;
                $SERVICE_PROVIDER_HOURS['MON_START_TIME'] = date('H:i', strtotime($startTime->fields['value']));
                $SERVICE_PROVIDER_HOURS['MON_END_TIME'] = date('H:i', strtotime($endTime->fields['value']));
                $SERVICE_PROVIDER_HOURS['TUE_START_TIME'] = date('H:i', strtotime($startTime->fields['value']));
                $SERVICE_PROVIDER_HOURS['TUE_END_TIME'] = date('H:i', strtotime($endTime->fields['value']));
                $SERVICE_PROVIDER_HOURS['WED_START_TIME'] = date('H:i', strtotime($startTime->fields['value']));
                $SERVICE_PROVIDER_HOURS['WED_END_TIME'] = date('H:i', strtotime($endTime->fields['value']));
                $SERVICE_PROVIDER_HOURS['THU_START_TIME'] = date('H:i', strtotime($startTime->fields['value']));
                $SERVICE_PROVIDER_HOURS['THU_END_TIME'] = date('H:i', strtotime($endTime->fields['value']));
                $SERVICE_PROVIDER_HOURS['FRI_START_TIME'] = date('H:i', strtotime($startTime->fields['value']));
                $SERVICE_PROVIDER_HOURS['FRI_END_TIME'] = date('H:i', strtotime($endTime->fields['value']));
                $SERVICE_PROVIDER_HOURS['SAT_START_TIME'] = '00:00:00';
                $SERVICE_PROVIDER_HOURS['SAT_END_TIME'] = '00:00:00';
                $SERVICE_PROVIDER_HOURS['SUN_START_TIME'] = '00:00:00';
                $SERVICE_PROVIDER_HOURS['SUN_END_TIME'] = '00:00:00';

                $location_data = $db->Execute("SELECT PK_SERVICE_PROVIDER_LOCATION_HOURS FROM DOA_SERVICE_PROVIDER_LOCATION_HOURS WHERE PK_USER = " . $row->fields['PK_USER']);
                if ($location_data->RecordCount() == 0) {
                    db_perform_account('DOA_SERVICE_PROVIDER_LOCATION_HOURS', $SERVICE_PROVIDER_HOURS, 'insert');
                }

                $row->MoveNext();
            }
            break;

        case 'SYNC_ENROLLMENT_PAYMENT':
            $lastUploadedId = getLastUploadedID($PK_ACCOUNT_MASTER, $PK_LOCATION, $_SESSION['MIGRATION_DB_NAME'], 'OTHER_PAYMENT');

            $enrollment_payment = getAllEnrollmentPayment($lastUploadedId);
            if ($enrollment_payment->RecordCount() > 0) {
                while (!$enrollment_payment->EOF) {
                    $enroll_id = $enrollment_payment->fields['enroll_id'];
                    $doableEnrollmentId = $db_account->Execute("SELECT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_BILLING, DOA_ENROLLMENT_BILLING.TOTAL_AMOUNT FROM DOA_ENROLLMENT_MASTER 
                        INNER JOIN DOA_ENROLLMENT_BILLING ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_MASTER 
                        WHERE DOA_ENROLLMENT_MASTER.ENROLLMENT_ID = '$enroll_id'");
                    $PK_ENROLLMENT_MASTER = ($doableEnrollmentId->RecordCount() > 0) ? $doableEnrollmentId->fields['PK_ENROLLMENT_MASTER'] : 0;
                    $PK_ENROLLMENT_BILLING = ($doableEnrollmentId->RecordCount() > 0) ? $doableEnrollmentId->fields['PK_ENROLLMENT_BILLING'] : 0;
                    $TOTAL_AMOUNT = ($doableEnrollmentId->RecordCount() > 0) ? $doableEnrollmentId->fields['TOTAL_AMOUNT'] : 0;

                    $enrollment_ledger = $db_account->Execute("SELECT PK_ENROLLMENT_LEDGER FROM DOA_ENROLLMENT_LEDGER WHERE PK_ENROLLMENT_MASTER = " . $PK_ENROLLMENT_MASTER . " AND PK_ENROLLMENT_BILLING = " . $PK_ENROLLMENT_BILLING . " AND IS_PAID = 0 ORDER BY PK_ENROLLMENT_LEDGER ASC LIMIT 1");
                    $PK_ENROLLMENT_LEDGER = ($enrollment_ledger->RecordCount() > 0) ? $enrollment_ledger->fields['PK_ENROLLMENT_LEDGER'] : 0;

                    if ($PK_ENROLLMENT_LEDGER > 0) {
                        $orgDate = $enrollment_payment->fields['date_paid'];
                        $newDate = date("Y-m-d", strtotime($orgDate));

                        if ($enrollment_payment->fields['payment_method'] == 'Save Card' || $enrollment_payment->fields['payment_method'] == 'Charge') {
                            $payment_type = $enrollment_payment->fields['card_type'];
                        } else {
                            $payment_type = $enrollment_payment->fields['payment_method'];
                        }
                        $PK_PAYMENT_TYPE = $db->Execute("SELECT PK_PAYMENT_TYPE FROM DOA_PAYMENT_TYPE WHERE PAYMENT_TYPE = '$payment_type'");

                        $ENROLLMENT_PAYMENT_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
                        $ENROLLMENT_PAYMENT_DATA['PK_ENROLLMENT_BILLING'] = $PK_ENROLLMENT_BILLING;
                        $ENROLLMENT_PAYMENT_DATA['PK_PAYMENT_TYPE'] = ($PK_PAYMENT_TYPE->RecordCount() > 0) ? $PK_PAYMENT_TYPE->fields['PK_PAYMENT_TYPE'] : 0;
                        $ENROLLMENT_PAYMENT_DATA['PK_ENROLLMENT_LEDGER'] = $PK_ENROLLMENT_LEDGER;
                        $ENROLLMENT_PAYMENT_DATA['TYPE'] = $enrollment_payment->fields['record_type'];
                        $ENROLLMENT_PAYMENT_DATA['AMOUNT'] = abs($enrollment_payment->fields['amount_paid']);
                        $ENROLLMENT_PAYMENT_DATA['NOTE'] = $enrollment_payment->fields['title'];
                        $ENROLLMENT_PAYMENT_DATA['PAYMENT_DATE'] = $newDate;
                        $PAYMENT_INFO = null;
                        if ($enrollment_payment->fields['card_number'] > 0) {
                            $PAYMENT_INFO_ARRAY = ['CHARGE_ID' => $enrollment_payment->fields['transaction_id'], 'LAST4' => $enrollment_payment->fields['card_number']];
                            $PAYMENT_INFO = json_encode($PAYMENT_INFO_ARRAY);
                        } elseif ($enrollment_payment->fields['check_number'] > 0) {
                            $PAYMENT_INFO_ARRAY = ['CHECK_NUMBER' => $enrollment_payment->fields['check_number'], 'CHECK_DATE' => $newDate];
                            $PAYMENT_INFO = json_encode($PAYMENT_INFO_ARRAY);
                        }
                        $ENROLLMENT_PAYMENT_DATA['PAYMENT_INFO'] = $PAYMENT_INFO;;
                        $ENROLLMENT_PAYMENT_DATA['PAYMENT_STATUS'] = 'Success';
                        $ENROLLMENT_PAYMENT_DATA['RECEIPT_NUMBER'] = $enrollment_payment->fields['receipt'];

                        $enrollmentServiceData = $db_account->Execute("SELECT FINAL_AMOUNT, TOTAL_AMOUNT_PAID, PK_ENROLLMENT_SERVICE FROM `DOA_ENROLLMENT_SERVICE` WHERE `PK_ENROLLMENT_MASTER` = " . $PK_ENROLLMENT_MASTER);
                        while (!$enrollmentServiceData->EOF) {
                            if ($enrollmentServiceData->fields['FINAL_AMOUNT'] > 0 && $TOTAL_AMOUNT > 0) {
                                $servicePercent = ($enrollmentServiceData->fields['FINAL_AMOUNT'] * 100) / $TOTAL_AMOUNT;
                                $serviceAmount = ($ENROLLMENT_PAYMENT_DATA['AMOUNT'] * $servicePercent) / 100;

                                if ($enrollment_payment->fields['record_type'] === 'Payment' || $enrollment_payment->fields['record_type'] === 'Adjustment') {
                                    $ENROLLMENT_SERVICE_UPDATE_DATA['TOTAL_AMOUNT_PAID'] = $enrollmentServiceData->fields['TOTAL_AMOUNT_PAID'] + $serviceAmount;
                                } elseif ($enrollment_payment->fields['record_type'] === 'Refund') {
                                    $ENROLLMENT_SERVICE_UPDATE_DATA['TOTAL_AMOUNT_PAID'] = $enrollmentServiceData->fields['TOTAL_AMOUNT_PAID'] - $serviceAmount;
                                } else {
                                    $ENROLLMENT_SERVICE_UPDATE_DATA['TOTAL_AMOUNT_PAID'] = $enrollmentServiceData->fields['TOTAL_AMOUNT_PAID'];
                                }
                                db_perform_account('DOA_ENROLLMENT_SERVICE', $ENROLLMENT_SERVICE_UPDATE_DATA, 'update', " PK_ENROLLMENT_SERVICE = " . $enrollmentServiceData->fields['PK_ENROLLMENT_SERVICE']);
                            }

                            $enrollmentServiceData->MoveNext();
                        }
                        db_perform_account('DOA_ENROLLMENT_PAYMENT', $ENROLLMENT_PAYMENT_DATA, 'insert');

                        if ($ENROLLMENT_PAYMENT_DATA['TYPE'] == 'Payment' || $ENROLLMENT_PAYMENT_DATA['TYPE'] == 'Adjustment') {
                            $LEDGER_UPDATE_DATA['AMOUNT_REMAIN'] = 0;
                            $LEDGER_UPDATE_DATA['IS_PAID'] = 1;
                            db_perform_account('DOA_ENROLLMENT_LEDGER', $LEDGER_UPDATE_DATA, 'update', ' PK_ENROLLMENT_LEDGER = ' . $PK_ENROLLMENT_LEDGER);
                        }
                    }
                    $enrollment_payment->MoveNext();
                }
            }
            break;

        case 'OTHER_PAYMENT':
            $lastUploadedId = getLastUploadedID($PK_ACCOUNT_MASTER, $PK_LOCATION, $_SESSION['MIGRATION_DB_NAME'], $_POST['TABLE_NAME']);

            $lastUploadedData = getLastPaymentId();
            $lastId = $lastUploadedData->fields['last_id'];
            insertLastUploadedID($PK_ACCOUNT_MASTER, $PK_LOCATION, $_SESSION['MIGRATION_DB_NAME'], $_POST['TABLE_NAME'], $lastId);

            $enrollment_payment = getAllEnrollmentPaymentByChargeId(0, $lastUploadedId);
            if ($enrollment_payment->RecordCount() > 0) {
                while (!$enrollment_payment->EOF) {
                    $orgDate = $enrollment_payment->fields['date_paid'];
                    $newDate = date("Y-m-d", strtotime($orgDate));

                    if ($enrollment_payment->fields['payment_method'] == 'Save Card' || $enrollment_payment->fields['payment_method'] == 'Charge') {
                        $payment_type = $enrollment_payment->fields['card_type'];
                    } else {
                        $payment_type = $enrollment_payment->fields['payment_method'];
                    }
                    $PK_PAYMENT_TYPE = $db->Execute("SELECT PK_PAYMENT_TYPE FROM DOA_PAYMENT_TYPE WHERE PAYMENT_TYPE = '$payment_type'");

                    $ENROLLMENT_PAYMENT_DATA['PK_ENROLLMENT_MASTER'] = 0;
                    $ENROLLMENT_PAYMENT_DATA['PK_ENROLLMENT_BILLING'] = 0;
                    $ENROLLMENT_PAYMENT_DATA['PK_PAYMENT_TYPE'] = ($PK_PAYMENT_TYPE->RecordCount() > 0) ? $PK_PAYMENT_TYPE->fields['PK_PAYMENT_TYPE'] : 0;
                    $ENROLLMENT_PAYMENT_DATA['PK_ENROLLMENT_LEDGER'] = 0;
                    $ENROLLMENT_PAYMENT_DATA['TYPE'] = $enrollment_payment->fields['record_type'];
                    $ENROLLMENT_PAYMENT_DATA['AMOUNT'] = abs($enrollment_payment->fields['amount_paid']);
                    $ENROLLMENT_PAYMENT_DATA['NOTE'] = $enrollment_payment->fields['title'];
                    $ENROLLMENT_PAYMENT_DATA['PAYMENT_DATE'] = $newDate;
                    $PAYMENT_INFO = null;
                    if ($enrollment_payment->fields['card_number'] > 0) {
                        $PAYMENT_INFO_ARRAY = ['CHARGE_ID' => $enrollment_payment->fields['transaction_id'], 'LAST4' => $enrollment_payment->fields['card_number']];
                        $PAYMENT_INFO = json_encode($PAYMENT_INFO_ARRAY);
                    } elseif ($enrollment_payment->fields['check_number'] > 0) {
                        $PAYMENT_INFO_ARRAY = ['CHECK_NUMBER' => $enrollment_payment->fields['check_number'], 'CHECK_DATE' => $newDate];
                        $PAYMENT_INFO = json_encode($PAYMENT_INFO_ARRAY);
                    }
                    $ENROLLMENT_PAYMENT_DATA['PAYMENT_INFO'] = $PAYMENT_INFO;;
                    $ENROLLMENT_PAYMENT_DATA['PAYMENT_STATUS'] = 'Success';
                    $ENROLLMENT_PAYMENT_DATA['RECEIPT_NUMBER'] = $enrollment_payment->fields['receipt'];
                    db_perform_account('DOA_ENROLLMENT_PAYMENT', $ENROLLMENT_PAYMENT_DATA, 'insert');
                    $enrollment_payment->MoveNext();
                }
            }
            break;

        case 'USER_JOINING_DATE':
            $allCustomers = getAllCustomers();
            while (!$allCustomers->EOF) {
                $user_id = $allCustomers->fields['customer_id'];
                $USER_DATA['JOINING_DATE'] = date("Y-m-d", strtotime($allCustomers->fields['inquiry_date']));
                db_perform('DOA_USERS', $USER_DATA, 'update', " USER_ID = '$user_id'");
                $allCustomers->MoveNext();
            }
            break;

        case 'ENR_IS_SALE':
            $allEnrollments = getAllEnrollments(0);
            while (!$allEnrollments->EOF) {
                $ENROLLMENT_DATA['IS_SALE'] = $allEnrollments->fields['is_sale'];
                $enrollment_id = $allEnrollments->fields['enrollment_id'];
                db_perform_account('DOA_ENROLLMENT_MASTER', $ENROLLMENT_DATA, 'update', " ENROLLMENT_ID = '$enrollment_id'");
                $allEnrollments->MoveNext();
            }
            break;

        case 'MARK_APPOINTMENT_PAID':
            $allEnrollmentService = $db_account->Execute("SELECT `PK_ENROLLMENT_SERVICE` FROM `DOA_ENROLLMENT_SERVICE`");
            while (!$allEnrollmentService->EOF) {
                markAppointmentPaid($allEnrollmentService->fields['PK_ENROLLMENT_SERVICE']);
                $allEnrollmentService->MoveNext();
            }
            break;

        case 'ENR_PDF':
            $upload_path = 'uploads/' . $PK_ACCOUNT_MASTER;

            if (!file_exists('../' . $upload_path . '/enrollment_pdf/')) {
                mkdir('../' . $upload_path . '/enrollment_pdf/', 0777, true);
                chmod('../' . $upload_path . '/enrollment_pdf/', 0777);
            }

            $location_data = $db->Execute("SELECT LOCATION_CODE FROM DOA_LOCATION WHERE PK_LOCATION = '$PK_LOCATION'");
            $LOCATION_CODE = $location_data->fields['LOCATION_CODE'];
            if (!file_exists('../' . $upload_path . '/enrollment_pdf/' . $LOCATION_CODE . '/')) {
                mkdir('../' . $upload_path . '/enrollment_pdf/' . $LOCATION_CODE . '/', 0777, true);
                chmod('../' . $upload_path . '/enrollment_pdf/' . $LOCATION_CODE . '/', 0777);
            }

            $allEnrollments = getAllEnrollments(0);
            while (!$allEnrollments->EOF) {
                $ENROLLMENT_DATA['AGREEMENT_PDF_LINK'] = ($allEnrollments->fields['enroll_pdf_file']) ? $LOCATION_CODE . '/' . $allEnrollments->fields['enroll_pdf_file'] . '.pdf' : NULL;
                $enrollment_id = $allEnrollments->fields['enrollment_id'];
                db_perform_account('DOA_ENROLLMENT_MASTER', $ENROLLMENT_DATA, 'update', " ENROLLMENT_ID = '$enrollment_id' AND PK_LOCATION = '$PK_LOCATION'");
                $allEnrollments->MoveNext();
            }
            break;

        case 'UPDATE_TAX_ID':
            $allUsers = getAllUsers();
            while (!$allUsers->EOF) {
                $user_id = $allUsers->fields['user_id'];
                $USER_DATA['ARTHUR_MURRAY_ID'] = $allUsers->fields['tax_id'];
                db_perform('DOA_USERS', $USER_DATA, 'update', " USER_ID = '$user_id' AND PK_ACCOUNT_MASTER = '$PK_ACCOUNT_MASTER'");
                $allUsers->MoveNext();
            }

        default:
            break;
    }
    header("Location: database_uploader.php");
}

function getLastUploadedID($PK_ACCOUNT_MASTER, $PK_LOCATION, $DATABASE_NAME, $TABLE_NAME)
{
    global $db;
    $last_uploaded = $db->Execute("SELECT MAX(LAST_UPLOAD_ID) AS ID FROM data_migration_log WHERE PK_ACCOUNT_MASTER = $PK_ACCOUNT_MASTER AND PK_LOCATION = $PK_LOCATION AND DATABASE_NAME = '$DATABASE_NAME' AND TABLE_NAME = '$TABLE_NAME'");
    return ($last_uploaded->RecordCount() > 0 && $last_uploaded->fields['ID'] != null && $last_uploaded->fields['ID'] != '') ? $last_uploaded->fields['ID'] : 0;
}

function insertLastUploadedID($PK_ACCOUNT_MASTER, $PK_LOCATION, $DATABASE_NAME, $TABLE_NAME, $LAST_UPLOAD_ID)
{
    global $db;
    $date = date('Y-m-d H:i:s');
    $db->Execute("INSERT INTO data_migration_log (PK_ACCOUNT_MASTER, PK_LOCATION, DATABASE_NAME, TABLE_NAME, LAST_UPLOAD_ID, LAST_UPLOAD_DATE) VALUES ($PK_ACCOUNT_MASTER, $PK_LOCATION, '$DATABASE_NAME', '$TABLE_NAME', $LAST_UPLOAD_ID, '$date')");
}

function checkSessionCount($PK_LOCATION, $SESSION_COUNT, $PK_ENROLLMENT_MASTER, $PK_ENROLLMENT_SERVICE, $PK_USER_MASTER, $PK_SERVICE_MASTER, $TYPE): array
{
    global $db;
    global $db_account;
    $SESSION_CREATED = getAllSessionCreatedCount($PK_ENROLLMENT_SERVICE, $TYPE); //$db_account->Execute("SELECT SESSION_CREATED FROM `DOA_ENROLLMENT_SERVICE` WHERE PK_ENROLLMENT_SERVICE = ".$PK_ENROLLMENT_SERVICE);
    if ($SESSION_CREATED > 0 && $SESSION_CREATED >= $SESSION_COUNT) {
        $db_account->Execute("UPDATE `DOA_ENROLLMENT_MASTER` SET `ALL_APPOINTMENT_DONE` = '1' WHERE PK_ENROLLMENT_MASTER = '$PK_ENROLLMENT_MASTER'");
        $enrollment_data = $db_account->Execute("SELECT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_SERVICE, DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION FROM DOA_ENROLLMENT_MASTER JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_USER_MASTER = '$PK_USER_MASTER' AND DOA_ENROLLMENT_SERVICE.PK_SERVICE_MASTER = '$PK_SERVICE_MASTER' AND DOA_ENROLLMENT_MASTER.PK_LOCATION = '$PK_LOCATION' AND DOA_ENROLLMENT_MASTER.ALL_APPOINTMENT_DONE = 0 ORDER BY DOA_ENROLLMENT_MASTER.ENROLLMENT_DATE ASC LIMIT 1");
        $PK_ENROLLMENT_MASTER_NEW = ($enrollment_data->RecordCount() > 0) ? $enrollment_data->fields['PK_ENROLLMENT_MASTER'] : 0;
        $PK_ENROLLMENT_SERVICE_NEW = ($enrollment_data->RecordCount() > 0) ? $enrollment_data->fields['PK_ENROLLMENT_SERVICE'] : 0;
        //$SESSION_COUNT = ($enrollment_data->RecordCount() > 0) ? $enrollment_data->fields['NUMBER_OF_SESSION'] : 0;
        /*if ($PK_ENROLLMENT_MASTER_NEW > 0 && $PK_ENROLLMENT_SERVICE_NEW > 0) {
            checkSessionCount($PK_LOCATION, $SESSION_COUNT, $PK_ENROLLMENT_MASTER_NEW, $PK_ENROLLMENT_SERVICE_NEW, $PK_USER_MASTER, $PK_SERVICE_MASTER);
        } else {
            return [$PK_ENROLLMENT_MASTER_NEW, $PK_ENROLLMENT_SERVICE_NEW];
        }*/
        return [$PK_ENROLLMENT_MASTER_NEW, $PK_ENROLLMENT_SERVICE_NEW];
    } else {
        return [$PK_ENROLLMENT_MASTER, $PK_ENROLLMENT_SERVICE];
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<?php require_once('../includes/header.php'); ?>

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">
        <?php require_once('../includes/top_menu.php'); ?>
        <div class="page-wrapper">
            <?php require_once('../includes/top_menu_bar.php') ?>
            <div class="container-fluid">
                <div class="row page-titles">
                    <div class="col-md-5 align-self-center">
                        <h4 class="text-themecolor"><?= $title ?></h4>
                    </div>
                    <div class="col-md-7 align-self-center text-end">
                        <div class="d-flex justify-content-end align-items-center">
                            <ol class="breadcrumb justify-content-end">
                                <li class="breadcrumb-item"><a href="setup.php">Setup</a></li>
                                <li class="breadcrumb-item active"><?= $title ?></li>
                            </ol>
                        </div>
                    </div>
                </div>
                <form action="" method="post" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label">Business Name</label>
                                <select class="form-control" name="PK_ACCOUNT_MASTER" id="PK_ACCOUNT_MASTER" onchange="getLocations(this)">
                                    <option value="">Select Business</option>
                                    <?php
                                    $row = $db->Execute("SELECT * FROM DOA_ACCOUNT_MASTER WHERE ACTIVE = 1 ORDER BY PK_ACCOUNT_MASTER DESC");
                                    while (!$row->EOF) { ?>
                                        <option value="<?php echo $row->fields['PK_ACCOUNT_MASTER']; ?>">(<?php echo $row->fields['PK_ACCOUNT_MASTER']; ?>) <?= $row->fields['BUSINESS_NAME'] ?></option>
                                    <?php $row->MoveNext();
                                    } ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label class="form-label">Select Location</label>
                                <select class="form-control" name="PK_LOCATION" id="PK_LOCATION">
                                    <option value="">Select Location</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label class="form-label">Select Database Name</label>
                                <select class="form-control" name="DATABASE_NAME" id="DATABASE_NAME">
                                    <option value="">Select Database Name</option>
                                    <option value="AMTO" <?= ($_SESSION['MIGRATION_DB_NAME'] == 'AMTO') ? 'selected' : '' ?>>AMTO</option>
                                    <option value="AMWH" <?= ($_SESSION['MIGRATION_DB_NAME'] == 'AMWH') ? 'selected' : '' ?>>AMWH</option>
                                    <option value="AMLS" <?= ($_SESSION['MIGRATION_DB_NAME'] == 'AMLS') ? 'selected' : '' ?>>AMLS</option>
                                    <option value="AMWB" <?= ($_SESSION['MIGRATION_DB_NAME'] == 'AMWB') ? 'selected' : '' ?>>AMWB</option>
                                    <option value="AMLV" <?= ($_SESSION['MIGRATION_DB_NAME'] == 'AMLV') ? 'selected' : '' ?>>AMLV</option>
                                    <option value="JTLV" <?= ($_SESSION['MIGRATION_DB_NAME'] == 'JTLV') ? 'selected' : '' ?>>JTLV</option>

                                    <option value="AMMB" <?= ($_SESSION['MIGRATION_DB_NAME'] == 'AMMB') ? 'selected' : '' ?>>AMMB</option>
                                    <option value="AMMO" <?= ($_SESSION['MIGRATION_DB_NAME'] == 'AMMO') ? 'selected' : '' ?>>AMMO</option>
                                    <option value="AMNP" <?= ($_SESSION['MIGRATION_DB_NAME'] == 'AMNP') ? 'selected' : '' ?>>AMNP</option>
                                    <option value="AMSR" <?= ($_SESSION['MIGRATION_DB_NAME'] == 'AMSR') ? 'selected' : '' ?>>AMSR</option>
                                    <option value="AMPT" <?= ($_SESSION['MIGRATION_DB_NAME'] == 'AMPT') ? 'selected' : '' ?>>AMPT</option>

                                    <option value="AMLG" <?= ($_SESSION['MIGRATION_DB_NAME'] == 'AMLG') ? 'selected' : '' ?>>AMLG</option>
                                    <option value="AMSJ" <?= ($_SESSION['MIGRATION_DB_NAME'] == 'AMSJ') ? 'selected' : '' ?>>AMSJ</option>

                                    <option value="AMTC" <?= ($_SESSION['MIGRATION_DB_NAME'] == 'AMTC') ? 'selected' : '' ?>>AMTC</option>

                                    <option value="AMSI" <?= ($_SESSION['MIGRATION_DB_NAME'] == 'AMSI') ? 'selected' : '' ?>>AMSI</option>
                                    <option value="AMBV" <?= ($_SESSION['MIGRATION_DB_NAME'] == 'AMBV') ? 'selected' : '' ?>>AMBV</option>
                                    <option value="AMFW" <?= ($_SESSION['MIGRATION_DB_NAME'] == 'AMFW') ? 'selected' : '' ?>>AMFW</option>
                                    <option value="AMSE" <?= ($_SESSION['MIGRATION_DB_NAME'] == 'AMSE') ? 'selected' : '' ?>>AMSE</option>

                                    <option value="AMEV" <?= ($_SESSION['MIGRATION_DB_NAME'] == 'AMEV') ? 'selected' : '' ?>>AMEV</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label">Select Table Name</label>
                                <select class="form-control" name="TABLE_NAME" id="TABLE_NAME" onchange="viewCsvDownload(this)">
                                    <option value="">Select Table Name</option>
                                    <!-- <option value="DOA_OPERATIONAL_HOUR" <?= ($_SESSION['TABLE_NAME'] == 'DOA_OPERATIONAL_HOUR') ? 'selected' : '' ?>>DOA_OPERATIONAL_HOUR</option> -->
                                    <option value="DOA_INQUIRY_METHOD" <?= ($_SESSION['TABLE_NAME'] == 'DOA_INQUIRY_METHOD') ? 'selected' : '' ?>>DOA_INQUIRY_METHOD</option>
                                    <!--<option value="DOA_EVENT_TYPE">DOA_EVENT_TYPE</option>
                                <option value="DOA_HOLIDAY_LIST">DOA_HOLIDAY_LIST</option>-->
                                    <option value="DOA_USERS" <?= ($_SESSION['TABLE_NAME'] == 'DOA_USERS') ? 'selected' : '' ?>>DOA_USERS</option>
                                    <option value="DOA_CUSTOMER" <?= ($_SESSION['TABLE_NAME'] == 'DOA_CUSTOMER') ? 'selected' : '' ?>>DOA_CUSTOMER</option>
                                    <option value="DOA_SERVICE_MASTER" <?= ($_SESSION['TABLE_NAME'] == 'DOA_SERVICE_MASTER') ? 'selected' : '' ?>>DOA_SERVICE_MASTER</option>
                                    <option value="DOA_SCHEDULING_CODE" <?= ($_SESSION['TABLE_NAME'] == 'DOA_SCHEDULING_CODE') ? 'selected' : '' ?>>DOA_SCHEDULING_CODE</option>
                                    <option value="DOA_ENROLLMENT_TYPE" <?= ($_SESSION['TABLE_NAME'] == 'DOA_ENROLLMENT_TYPE') ? 'selected' : '' ?>>DOA_ENROLLMENT_TYPE</option>
                                    <option value="DOA_PACKAGE" <?= ($_SESSION['TABLE_NAME'] == 'DOA_PACKAGE') ? 'selected' : '' ?>>DOA_PACKAGE</option>
                                    <option value="DOA_ENROLLMENT" <?= ($_SESSION['TABLE_NAME'] == 'DOA_ENROLLMENT') ? 'selected' : '' ?>>DOA_ENROLLMENT</option>
                                    <option value="OTHER_PAYMENT" <?= ($_SESSION['TABLE_NAME'] == 'OTHER_PAYMENT') ? 'selected' : '' ?>>OTHER_PAYMENT</option>
                                    <!--<option value="DOA_ENROLLMENT_SERVICE">DOA_ENROLLMENT_SERVICE</option>
                                <option value="DOA_ENROLLMENT_PAYMENT">DOA_ENROLLMENT_PAYMENT</option>-->
                                    <option value="DOA_APPOINTMENT_MASTER" <?= ($_SESSION['TABLE_NAME'] == 'DOA_APPOINTMENT_MASTER') ? 'selected' : '' ?>>DOA_APPOINTMENT_MASTER</option>
                                    <option value="DOA_SPECIAL_APPOINTMENT" <?= ($_SESSION['TABLE_NAME'] == 'DOA_SPECIAL_APPOINTMENT') ? 'selected' : '' ?>>DOA_SPECIAL_APPOINTMENT</option>

                                    <!-- <option value="ENR_PDF">ENR_PDF</option> -->




                                    <!--<option value="SP_TIME">Service Provider Time</option>

                                <option value="USER_JOINING_DATE">USER_JOINING_DATE</option>

                                <option value="ENR_IS_SALE">ENR_IS_SALE</option>-->

                                    <option value="MARK_APPOINTMENT_PAID" <?= ($_SESSION['TABLE_NAME'] == 'MARK_APPOINTMENT_PAID') ? 'selected' : '' ?>>MARK_APPOINTMENT_PAID</option>
                                    <option value="UPDATE_TAX_ID" <?= ($_SESSION['TABLE_NAME'] == 'UPDATE_TAX_ID') ? 'selected' : '' ?>>UPDATE_TAX_ID</option>
                                    <option value="SYNC_ENROLLMENT_PAYMENT" <?= ($_SESSION['TABLE_NAME'] == 'SYNC_ENROLLMENT_PAYMENT') ? 'selected' : '' ?>>SYNC_ENROLLMENT_PAYMENT</option>
                                </select>
                                <div id="view_download_div" class="m-10"></div>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">Submit</button>
                </form>
            </div>
        </div>
    </div>
    <?php require_once('../includes/footer.php'); ?>
</body>
<script>
    function viewCsvDownload(param) {
        let table_name = $(param).val();
        $('#view_download_div').html(`<a href="../uploads/csv_upload/${table_name}.csv" target="_blank">View Sample</a>`);
    }

    function getLocations(param) {
        let PK_ACCOUNT_MASTER = $(param).val();
        $.ajax({
            url: "ajax/get_location.php",
            type: 'GET',
            data: {
                PK_ACCOUNT_MASTER: PK_ACCOUNT_MASTER
            },
            success: function(data) {
                $('#PK_LOCATION').empty().append(data);
            }
        });
    }
</script>

</html>