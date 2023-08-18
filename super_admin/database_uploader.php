<?php
//error_reporting(0);
require_once('../global/config.php');
$title = "Upload CSV";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 1 ){
    header("location:../login.php");
    exit;
}

if(!empty($_POST))
{
    $PK_ACCOUNT_MASTER = $_POST['PK_ACCOUNT_MASTER'];
    $PK_LOCATION = $_POST['PK_LOCATION'];

    $account_data = $db->Execute("SELECT DB_NAME FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER = ".$PK_ACCOUNT_MASTER);
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
    $_SESSION['MIGRATION_DB_NAME'] = $_POST['DATABASE_NAME'];
    require_once('upload_functions.php');

    $standardServicePkId = $db_account->Execute("SELECT PK_SERVICE_CODE, PK_SERVICE_MASTER FROM DOA_SERVICE_CODE WHERE SERVICE_CODE LIKE 'S-1'");
    $PK_SERVICE_CODE_STANDARD = $standardServicePkId->fields['PK_SERVICE_CODE'];
    $PK_SERVICE_MASTER_STANDARD = $standardServicePkId->fields['PK_SERVICE_MASTER'];


    switch ($_POST['TABLE_NAME']) {
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
            while (!$allUsers->EOF) {
                $roleId = $allUsers->fields['role'];
                $getRole = getRole($roleId);
                $doableRoleId = $db->Execute("SELECT PK_ROLES FROM DOA_ROLES WHERE ROLES='$getRole'");
                $USER_DATA['PK_ACCOUNT_MASTER'] = $PK_ACCOUNT_MASTER;
                $USER_DATA['FIRST_NAME'] = trim($getData[3]);
                $USER_DATA['LAST_NAME'] = trim($getData[4]);
                $USER_DATA['USER_ID'] = $getData[19];
                $USER_DATA['EMAIL_ID'] = $getData[14];
                if (!empty($getData[13]) && $getData[13] != null) {
                    $USER_DATA['PHONE'] = $getData[13];
                } elseif (!empty($getData[12]) && $getData[12] != null) {
                    $USER_DATA['PHONE'] = $getData[12];
                }
                $USER_DATA['PASSWORD'] = $getData[20];
                $USER_DATA['GENDER'] = ($getData[5] == 'M') ? 'Male' : 'Female';
                $USER_DATA['DOB'] = date("Y-m-d", strtotime($getData[16]));
                $USER_DATA['ADDRESS'] = $getData[7];
                $USER_DATA['ADDRESS_1'] = $getData[8];
                $USER_DATA['CITY'] = $getData[9];
                $USER_DATA['PK_COUNTRY'] = 1;
                $state_data = $db->Execute("SELECT PK_STATES FROM DOA_STATES WHERE STATE_NAME='$getData[10]' OR STATE_CODE='$getData[10]'");
                $USER_DATA['PK_STATES'] = ($state_data->RecordCount() > 0) ? $state_data->fields['PK_STATES'] : 0;
                $USER_DATA['ZIP'] = $getData[11];
                $USER_DATA['NOTES'] = $getData[18];
                $USER_DATA['ACTIVE'] = $getData[17];
                $USER_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
                $USER_DATA['CREATED_ON'] = date("Y-m-d H:i");
                db_perform('DOA_USERS', $USER_DATA, 'insert');
                $PK_USER = $db->insert_ID();

                if ($PK_USER) {
                    $USER_ROLE_DATA['PK_USER'] = $PK_USER;
                    $USER_ROLE_DATA['PK_ROLES'] = ($doableRoleId->RecordCount() > 0) ? $doableRoleId->fields['PK_ROLES'] : 0;
                    db_perform('DOA_USER_ROLES', $USER_ROLE_DATA, 'insert');

                    $USER_DATA_ACCOUNT['PK_USER_MASTER_DB'] = $PK_USER;
                    $USER_DATA_ACCOUNT['PK_ACCOUNT_MASTER'] = $PK_ACCOUNT_MASTER;
                    $USER_DATA_ACCOUNT['FIRST_NAME'] = trim($getData[3]);
                    $USER_DATA_ACCOUNT['LAST_NAME'] = trim($getData[4]);
                    $USER_DATA_ACCOUNT['USER_ID'] = $getData[19];
                    $USER_DATA_ACCOUNT['EMAIL_ID'] = $getData[14];
                    if (!empty($getData[13]) && $getData[13] != null) {
                        $USER_DATA_ACCOUNT['PHONE'] = $getData[13];
                    } elseif (!empty($getData[12]) && $getData[12] != null) {
                        $USER_DATA_ACCOUNT['PHONE'] = $getData[12];
                    }
                    $USER_DATA_ACCOUNT['CREATED_BY'] = $_SESSION['PK_USER'];
                    $USER_DATA_ACCOUNT['CREATED_ON'] = date("Y-m-d H:i");
                    db_perform_account('DOA_USERS', $USER_DATA_ACCOUNT, 'insert');

                    $USER_LOCATION_DATA['PK_USER'] = $PK_USER;
                    $USER_LOCATION_DATA['PK_LOCATION'] = $PK_LOCATION;
                    db_perform('DOA_USER_LOCATION', $USER_LOCATION_DATA, 'insert');
                }
                $allUsers->MoveNext();
            }
            break;

        case 'DOA_CUSTOMER':
            $USER_DATA['PK_ACCOUNT_MASTER'] = $PK_ACCOUNT_MASTER;
            $USER_DATA['USER_ID'] = $getData[1];
            $USER_DATA['FIRST_NAME'] = trim($getData[2]);
            $USER_DATA['LAST_NAME'] = trim($getData[3]);
            $USER_DATA['EMAIL_ID'] = $getData[25];
            //$USER_DATA['HOME_PHONE'] = $getData[18];
            if (!empty($getData[21]) && $getData[21] != null && $getData[21] != "   -   -    *") {
                $USER_DATA['PHONE'] = $getData[21];
            } elseif (!empty($getData[19]) && $getData[19] != null && $getData[19] != "   -   -    *") {
                $USER_DATA['PHONE'] = $getData[19];
            } elseif (!empty($getData[20]) && $getData[20] != null && $getData[20] != "   -   -    *") {
                $USER_DATA['PHONE'] = $getData[20];
            }
            if ($getData[8] == 0) {
                $USER_DATA['GENDER'] = 'Male';
            } elseif ($getData[8] == 1) {
                $USER_DATA['GENDER'] = 'Female';
            }

            $USER_DATA['DOB'] = date("Y-m-d", strtotime($getData[6]));
            if ($getData[9] == 1) {
                $USER_DATA['MARITAL_STATUS'] = "Married";
            } elseif ($getData[9] == 0) {
                $USER_DATA['MARITAL_STATUS'] = "Unmarried";
            }

            $USER_DATA['ADDRESS'] = $getData[14];
            $USER_DATA['ADDRESS_1'] = $getData[15];
            $USER_DATA['CITY'] = $getData[16];
            $USER_DATA['PK_COUNTRY'] = 1;
            $state_data = $db->Execute("SELECT PK_STATES FROM DOA_STATES WHERE STATE_NAME='$getData[17]' OR STATE_CODE='$getData[17]'");
            $USER_DATA['PK_STATES'] = ($state_data->RecordCount() > 0) ? $state_data->fields['PK_STATES'] : 0;
            $USER_DATA['ZIP'] = $getData[18];
            $USER_DATA['NOTES'] = $getData[43];
            $USER_DATA['ACTIVE'] = ($getData[46] == 'A') ? 1 : 0;
            $USER_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
            $USER_DATA['CREATED_ON'] = date("Y-m-d H:i");
            db_perform('DOA_USERS', $USER_DATA, 'insert');
            $PK_USER = $db->insert_ID();

            if ($PK_USER) {
                $USER_DATA_ACCOUNT['PK_USER_MASTER_DB'] = $PK_USER;
                $USER_DATA_ACCOUNT['PK_ACCOUNT_MASTER'] = $PK_ACCOUNT_MASTER;
                $USER_DATA_ACCOUNT['FIRST_NAME'] = trim($getData[2]);
                $USER_DATA_ACCOUNT['LAST_NAME'] = trim($getData[3]);
                $USER_DATA_ACCOUNT['USER_ID'] = $getData[1];
                $USER_DATA_ACCOUNT['EMAIL_ID'] = $getData[25];
                if (!empty($getData[21]) && $getData[21] != null && $getData[21] != "   -   -    *") {
                    $USER_DATA_ACCOUNT['PHONE'] = $getData[21];
                } elseif (!empty($getData[19]) && $getData[19] != null && $getData[19] != "   -   -    *") {
                    $USER_DATA_ACCOUNT['PHONE'] = $getData[19];
                } elseif (!empty($getData[20]) && $getData[20] != null && $getData[20] != "   -   -    *") {
                    $USER_DATA_ACCOUNT['PHONE'] = $getData[20];
                }
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
                    $CUSTOMER_DATA['FIRST_NAME'] = $getData[2];
                    $CUSTOMER_DATA['LAST_NAME'] = $getData[3];
                    $CUSTOMER_DATA['EMAIL'] = $getData[25];
                    $CUSTOMER_DATA['PHONE'] = $getData[21];
                    $CUSTOMER_DATA['DOB'] = date("Y-m-d", strtotime($getData[6]));
                    $CUSTOMER_DATA['CALL_PREFERENCE'] = $getData[24];
                    //$CUSTOMER_DATA['REMINDER_OPTION'] = $getData[23];
                    $partner_name = explode(" ", $getData[26]);
                    $CUSTOMER_DATA['PARTNER_FIRST_NAME'] = isset($partner_name[0]) ?: '';
                    $CUSTOMER_DATA['PARTNER_LAST_NAME'] = isset($partner_name[1]) ?: '';
                    if ($getData[27] == 0) {
                        $CUSTOMER_DATA['PARTNER_GENDER'] = "Male";
                    } elseif ($getData[27] == 1) {
                        $CUSTOMER_DATA['PARTNER_GENDER'] = "Female";
                    }
                    if (!empty($getData[26])) {
                        $CUSTOMER_DATA['ATTENDING_WITH'] = "With a Partner";
                    } else {
                        $CUSTOMER_DATA['ATTENDING_WITH'] = "Solo";
                    }
                    $CUSTOMER_DATA['PARTNER_DOB'] = date("Y-m-d", strtotime($getData[7]));
                    $CUSTOMER_DATA['IS_PRIMARY'] = 1;
                    db_perform_account('DOA_CUSTOMER_DETAILS', $CUSTOMER_DATA, 'insert');
                    $PK_CUSTOMER_DETAILS = $db_account->insert_ID();

                    if (!empty($getData[19]) && $getData[19] != "   -   -    *") {
                        $PHONE_DATA['PK_CUSTOMER_DETAILS'] = $PK_CUSTOMER_DETAILS;
                        $PHONE_DATA['PHONE'] = $getData[19];
                        db_perform_account('DOA_CUSTOMER_PHONE', $PHONE_DATA, 'insert');
                    }

                    if (!empty($getData[20]) && $getData[20] != "   -   -    *") {
                        $PHONE_DATA['PK_CUSTOMER_DETAILS'] = $PK_CUSTOMER_DETAILS;
                        $PHONE_DATA['PHONE'] = $getData[20];
                        db_perform_account('DOA_CUSTOMER_PHONE', $PHONE_DATA, 'insert');
                    }

                    if ($getData[10] != "0000-00-00 00:00:00" && $getData[10] > 0) {
                        $SPECIAL_DATA['PK_CUSTOMER_DETAILS'] = $PK_CUSTOMER_DETAILS;
                        $SPECIAL_DATA['SPECIAL_DATE'] = date("Y-m-d", strtotime($getData[10]));
                        $SPECIAL_DATA['DATE_NAME'] = $getData[12];
                        db_perform_account('DOA_CUSTOMER_SPECIAL_DATE', $SPECIAL_DATA, 'insert');
                    }
                    if ($getData[11] != "0000-00-00 00:00:00" && $getData[11] > 0) {
                        $SPECIAL_DATA_1['PK_CUSTOMER_DETAILS'] = $PK_CUSTOMER_DETAILS;
                        $SPECIAL_DATA_1['SPECIAL_DATE'] = date("Y-m-d", strtotime($getData[11]));
                        $SPECIAL_DATA_1['DATE_NAME'] = $getData[13];
                        db_perform_account('DOA_CUSTOMER_SPECIAL_DATE', $SPECIAL_DATA_1, 'insert');
                    }

                    $INQUIRY_VALUE['PK_USER_MASTER'] = $PK_USER_MASTER;
                    $INQUIRY_VALUE['WHAT_PROMPTED_YOU_TO_INQUIRE'] = $getData[39];

                    $INQUIRY_VALUE['PK_INQUIRY_METHOD'] = 0;
                    $INQUIRY_VALUE['INQUIRY_TAKER_ID'] = 0;

                    if (!empty($getData[38])) {
                        $inquiryId = $getData[38];
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

                    if (!empty($getData[37])) {
                        $takerId = $getData[37];
                        $getTaker = getTaker($takerId);
                        $doableTakerId = $db->Execute("SELECT PK_USER FROM DOA_USERS WHERE USER_ID='$getTaker'");
                        $INQUIRY_VALUE['INQUIRY_TAKER_ID'] = ($doableTakerId->RecordCount() > 0) ? $doableTakerId->fields['PK_USER'] : 0;
                    }
                    db_perform_account('DOA_CUSTOMER_INTEREST_OTHER_DATA', $INQUIRY_VALUE, 'insert');
                }
            }
            break;

        case 'DOA_SERVICE_MASTER':
            $table_data = $db_account->Execute("SELECT * FROM DOA_SERVICE_MASTER WHERE SERVICE_NAME='$getData[1]' AND PK_ACCOUNT_MASTER='$PK_ACCOUNT_MASTER'");
            if ($table_data->RecordCount() == 0) {
                $SERVICE['PK_ACCOUNT_MASTER'] = $PK_ACCOUNT_MASTER;
                $SERVICE['SERVICE_NAME'] = $getData[1];
                $SERVICE['PK_SERVICE_CLASS'] = 2;
                $SERVICE['IS_SCHEDULE'] = 1;
                $SERVICE['DESCRIPTION'] = $getData[1];
                $SERVICE['ACTIVE'] = 1;
                $SERVICE['CREATED_BY'] = $_SESSION['PK_USER'];
                $SERVICE['CREATED_ON'] = date("Y-m-d H:i");
                db_perform_account('DOA_SERVICE_MASTER', $SERVICE, 'insert');
                $PK_SERVICE_MASTER = $db_account->insert_ID();

                $SERVICE_CODE['PK_SERVICE_MASTER'] = $PK_SERVICE_MASTER;
                $SERVICE_CODE['SERVICE_CODE'] = $getData[0];
                $SERVICE_CODE['PK_FREQUENCY'] = 0;
                $SERVICE_CODE['DESCRIPTION'] = $getData[1];
                $SERVICE_CODE['DURATION'] = 0;
                if (strpos($getData[0], "GRP") !== false) {
                    $SERVICE_CODE['IS_GROUP'] = 1;
                } else {
                    $SERVICE_CODE['IS_GROUP'] = 0;
                }
                $SERVICE_CODE['CAPACITY'] = 0;
                if ($getData[3] == "Y") {
                    $SERVICE_CODE['IS_CHARGEABLE'] = 1;
                } elseif ($getData[3] == "N") {
                    $SERVICE_CODE['IS_CHARGEABLE'] = 0;
                }
                $SERVICE_CODE['ACTIVE'] = 1;
                db_perform_account('DOA_SERVICE_CODE', $SERVICE_CODE, 'insert');
            }
            break;

        case 'DOA_SCHEDULING_CODE':
            $table_data = $db_account->Execute("SELECT * FROM DOA_SCHEDULING_CODE WHERE SCHEDULING_CODE = '$getData[0]' AND PK_ACCOUNT_MASTER='$PK_ACCOUNT_MASTER'");
            if ($table_data->RecordCount() == 0) {
                $SCHEDULING_CODE['PK_ACCOUNT_MASTER'] = $PK_ACCOUNT_MASTER;
                $SCHEDULING_CODE['SCHEDULING_CODE'] = $getData[0];
                $SCHEDULING_CODE['SCHEDULING_NAME'] = $getData[1];
                $SCHEDULING_CODE['PK_SCHEDULING_EVENT'] = 1;
                $SCHEDULING_CODE['PK_EVENT_ACTION'] = 2;
                if ($getData[13] == "Active") {
                    $SCHEDULING_CODE['ACTIVE'] = 1;
                } elseif ($getData[13] == "Not Active") {
                    $SCHEDULING_CODE['ACTIVE'] = 0;
                }
                $SCHEDULING_CODE['CREATED_BY'] = $_SESSION['PK_USER'];
                $SCHEDULING_CODE['CREATED_ON'] = date("Y-m-d H:i");
                db_perform_account('DOA_SCHEDULING_CODE', $SCHEDULING_CODE, 'insert');
                $PK_SCHEDULING_CODE = $db_account->insert_ID();

                $serviceCodeData = $db_account->Execute("SELECT PK_SERVICE_MASTER, PK_SERVICE_CODE FROM DOA_SERVICE_CODE WHERE SERVICE_CODE = '$getData[3]'");
                $SERVICE_SCHEDULING_CODE['PK_SERVICE_MASTER'] = $serviceCodeData->fields['PK_SERVICE_MASTER'];
                $SERVICE_SCHEDULING_CODE['PK_SERVICE_CODE'] = $serviceCodeData->fields['PK_SERVICE_CODE'];
                $SERVICE_SCHEDULING_CODE['PK_SCHEDULING_CODE'] = $PK_SCHEDULING_CODE;
                db_perform_account('DOA_SERVICE_SCHEDULING_CODE', $SERVICE_SCHEDULING_CODE, 'insert');
            }
            break;

        case "DOA_ENROLLMENT_TYPE":
            $table_data = $db_account->Execute("SELECT * FROM DOA_ENROLLMENT_TYPE WHERE ENROLLMENT_TYPE='$getData[1]' AND PK_ACCOUNT_MASTER='$PK_ACCOUNT_MASTER'");
            if ($table_data->RecordCount() == 0) {
                $INSERT_DATA['ENROLLMENT_TYPE'] = $getData[1];
                $INSERT_DATA['CODE'] = $getData[6];
                $INSERT_DATA['ACTIVE'] = 1;
                $INSERT_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
                $INSERT_DATA['CREATED_ON'] = date("Y-m-d H:i");
                db_perform_account('DOA_ENROLLMENT_TYPE', $INSERT_DATA, 'insert');
            }
            break;

        case "DOA_ENROLLMENT_MASTER":
            $ENROLLMENT_DATA['ENROLLMENT_ID'] = $getData[0];
            $ENROLLMENT_DATA['ENROLLMENT_NAME'] = $getData[3];

            [$enrollment_type, $code] = getEnrollmentType($getData[2]);
            $enrollment_type_data = $db_account->Execute("SELECT PK_ENROLLMENT_TYPE FROM `DOA_ENROLLMENT_TYPE` WHERE ENROLLMENT_TYPE = '$enrollment_type'");
            if ($enrollment_type_data->RecordCount() > 0) {
                $ENROLLMENT_DATA['PK_ENROLLMENT_TYPE'] = $enrollment_type_data->fields['PK_ENROLLMENT_TYPE'];
            } else {
                $ENROLLMENT_DATA['PK_ENROLLMENT_TYPE'] = 0;
            }

            $ENROLLMENT_DATA['PK_ACCOUNT_MASTER'] = $PK_ACCOUNT_MASTER;
            $customerId = $getData[4];
            $doableCustomerId = $db->Execute("SELECT DOA_USER_MASTER.PK_USER_MASTER FROM DOA_USER_MASTER INNER JOIN DOA_USERS ON DOA_USER_MASTER.PK_USER=DOA_USERS.PK_USER WHERE DOA_USERS.USER_ID='$customerId' AND DOA_USER_MASTER.PK_ACCOUNT_MASTER = '$PK_ACCOUNT_MASTER'");
            $ENROLLMENT_DATA['PK_USER_MASTER'] = ($doableCustomerId->RecordCount() > 0) ? $doableCustomerId->fields['PK_USER_MASTER'] : 0;
            $ENROLLMENT_DATA['PK_LOCATION'] = $PK_LOCATION;
            $ENROLLMENT_DATA['ENROLLMENT_BY_ID'] = $PK_ACCOUNT_MASTER;
            $ENROLLMENT_DATA['ACTIVE'] = 1;
            $ENROLLMENT_DATA['STATUS'] = "A";
            $ENROLLMENT_DATA['EXPIRY_DATE'] = $getData[22];
            $ENROLLMENT_DATA['CREATED_BY'] = $PK_ACCOUNT_MASTER;
            $ENROLLMENT_DATA['CREATED_ON'] = date("Y-m-d H:i");
            db_perform_account('DOA_ENROLLMENT_MASTER', $ENROLLMENT_DATA, 'insert');
            $PK_ENROLLMENT_MASTER = $db_account->insert_ID();

            if ($code == 'MISC' && date('Y-m-d') > $getData[22]) {
                $APPOINTMENT_DATA['PK_ACCOUNT_MASTER'] = $PK_ACCOUNT_MASTER;
                $APPOINTMENT_DATA['CUSTOMER_ID'] = $ENROLLMENT_DATA['PK_USER_MASTER'];
                $APPOINTMENT_DATA['SERVICE_PROVIDER_ID'] = NULL;
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
            }

            $ACTUAL_AMOUNT = $getData[8];
            $DISCOUNT = $getData[28];
            $TOTAL_AMOUNT = $getData[38];
            $DOWN_PAYMENT = 0;
            $BALANCE_PAYABLE = 0;
            $PAYMENT_METHOD = 'One Time';
            $PAYMENT_TERM = '';
            $NUMBER_OF_PAYMENT = 0;
            $FIRST_DUE_DATE = date('Y-m-d');
            $INSTALLMENT_AMOUNT = 0;
            if (strpos($getData[23], "C") !== false) {
                $info = str_replace('  ', ' ', $getData[23]);
                $paymentInfo = explode(' ', $info);
                $NUMBER_OF_PAYMENT = (is_array($paymentInfo) && isset($paymentInfo[2]) && is_int($paymentInfo[2])) ? $paymentInfo[2] : 0;
                $INSTALLMENT_AMOUNT = (float)$paymentInfo[1];
                $DOWN_PAYMENT = $TOTAL_AMOUNT - ($NUMBER_OF_PAYMENT * $INSTALLMENT_AMOUNT);
                $BALANCE_PAYABLE = ($NUMBER_OF_PAYMENT * $INSTALLMENT_AMOUNT);
                $PAYMENT_METHOD = 'Flexible Payments';
                $PAYMENT_TERM = 'Monthly';
                $FIRST_DUE_DATE = date('Y-m-d', strtotime($getData[7]));
            }

            $BILLING_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
            $BILLING_DATA['BILLING_REF'] = '';
            $BILLING_DATA['BILLING_DATE'] = date('Y-m-d', strtotime($getData[7]));
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
            break;

        case "DOA_ENROLLMENT_SERVICE":
            $enrollmentId = $getData[4];
            $doableEnrollmentId = $db_account->Execute("SELECT PK_ENROLLMENT_MASTER, ENROLLMENT_NAME FROM DOA_ENROLLMENT_MASTER WHERE ENROLLMENT_ID = '$enrollmentId'");
            $PK_ENROLLMENT_MASTER = $doableEnrollmentId->fields['PK_ENROLLMENT_MASTER'];
            $ENROLLMENT_NAME = $doableEnrollmentId->fields['ENROLLMENT_NAME'];

            $doableServiceId = $db_account->Execute("SELECT PK_SERVICE_MASTER, PK_SERVICE_CODE, DESCRIPTION FROM DOA_SERVICE_CODE WHERE SERVICE_CODE ='$getData[2]'");
            $PK_SERVICE_MASTER = $doableServiceId->fields['PK_SERVICE_MASTER'];
            $PK_SERVICE_CODE = $doableServiceId->fields['PK_SERVICE_CODE'];

            preg_match('#\((.*?)\)#', $ENROLLMENT_NAME, $match);
            $serviceCode = (is_array($match) && isset($match[1])) ? $match[1] : '';
            $getServiceCodeId = $db_account->Execute("SELECT PK_SCHEDULING_CODE FROM DOA_SCHEDULING_CODE WHERE SCHEDULING_NAME LIKE '%" . $serviceCode . "%'");
            if ($getServiceCodeId->RecordCount() > 0) {
                $PK_SCHEDULING_CODE = $getServiceCodeId->fields['PK_SCHEDULING_CODE'];
            } else {
                $PK_SCHEDULING_CODE = 0;
            }

            $table_data = $db_account->Execute("SELECT * FROM DOA_ENROLLMENT_SERVICE WHERE  PK_ENROLLMENT_MASTER='$PK_ENROLLMENT_MASTER' AND PK_SERVICE_MASTER='$PK_SERVICE_MASTER'");
            if ($table_data->RecordCount() == 0) {
                $SERVICE_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
                $SERVICE_DATA['PK_SERVICE_MASTER'] = $PK_SERVICE_MASTER;
                $SERVICE_DATA['PK_SERVICE_CODE'] = $PK_SERVICE_CODE;
                $SERVICE_DATA['PK_SCHEDULING_CODE'] = $PK_SCHEDULING_CODE;
                $SERVICE_DATA['FREQUENCY'] = 0;
                $SERVICE_DATA['SERVICE_DETAILS'] = $doableServiceId->fields['DESCRIPTION'];
                $SERVICE_DATA['NUMBER_OF_SESSION'] = $getData[3];
                [$getTotal, $getDiscount, $getFinalAmount] = getEnrollmentDetails($enrollmentId);
                $SERVICE_DATA['PRICE_PER_SESSION'] = $getTotal / $getData[3];
                $SERVICE_DATA['TOTAL'] = $getTotal;
                $SERVICE_DATA['DISCOUNT'] = $getDiscount;
                $SERVICE_DATA['FINAL_AMOUNT'] = $getFinalAmount;
                db_perform_account('DOA_ENROLLMENT_SERVICE', $SERVICE_DATA, 'insert');
            }
            break;

        case "DOA_ENROLLMENT_PAYMENT":
            $enrollmentId = $getData[1];
            $doableEnrollmentId = $db_account->Execute("SELECT PK_ENROLLMENT_MASTER, ENROLLMENT_NAME FROM DOA_ENROLLMENT_MASTER WHERE ENROLLMENT_ID = '$enrollmentId'");
            $PK_ENROLLMENT_MASTER = $doableEnrollmentId->fields['PK_ENROLLMENT_MASTER'];
            $INSERT_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;

            $PK_ENROLLMENT_BILLING = $db_account->Execute("SELECT PK_ENROLLMENT_BILLING, TOTAL_AMOUNT FROM DOA_ENROLLMENT_BILLING WHERE PK_ENROLLMENT_MASTER='$PK_ENROLLMENT_MASTER' ");
            $INSERT_DATA['PK_ENROLLMENT_BILLING'] = $PK_ENROLLMENT_BILLING->fields['PK_ENROLLMENT_BILLING'];
            $TOTAL_AMOUNT = $PK_ENROLLMENT_BILLING->fields['TOTAL_AMOUNT'];

            $total_paid = $db_account->Execute("SELECT SUM(AMOUNT) AS TOTAL_PAID FROM DOA_ENROLLMENT_PAYMENT WHERE `PK_ENROLLMENT_MASTER`=" . $PK_ENROLLMENT_MASTER);
            $TOTAL_PAID = $total_paid->fields['TOTAL_PAID'];

            $PK_PAYMENT_TYPE = $db_account->Execute("SELECT PK_PAYMENT_TYPE FROM DOA_PAYMENT_TYPE WHERE PAYMENT_TYPE='$getData[5]'");
            $INSERT_DATA['PK_PAYMENT_TYPE'] = ($PK_PAYMENT_TYPE->RecordCount() > 0) ? $PK_PAYMENT_TYPE->fields['PK_PAYMENT_TYPE'] : 0;

            $INSERT_DATA['AMOUNT'] = $getData[8];
            $INSERT_DATA['REMAINING_AMOUNT'] = $TOTAL_AMOUNT - $TOTAL_PAID;
            $INSERT_DATA['PK_PAYMENT_TYPE_REMAINING'] = '';
            $INSERT_DATA['NAME'] = '';
            $INSERT_DATA['CARD_NUMBER'] = $getData[16];
            $INSERT_DATA['SECURITY_CODE'] = '';
            $INSERT_DATA['EXPIRATION_DATE'] = '';
            $INSERT_DATA['CHECK_NUMBER'] = $getData[17];
            $INSERT_DATA['CHECK_DATE'] = '';
            $INSERT_DATA['NOTE'] = $getData[19];
            $orgDate = $getData[7];
            $newDate = date("Y-m-d", strtotime($orgDate));
            $INSERT_DATA['PAYMENT_DATE'] = $newDate;
            $INSERT_DATA['PAYMENT_INFO'] = $getData[20];
            db_perform_account('DOA_ENROLLMENT_PAYMENT', $INSERT_DATA, 'insert');
            $PK_ENROLLMENT_PAYMENT = $db_account->insert_ID();

            $LEDGER_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
            $LEDGER_DATA['PK_ENROLLMENT_BILLING '] = $PK_ENROLLMENT_BILLING->fields['PK_ENROLLMENT_BILLING'];
            $LEDGER_DATA['TRANSACTION_TYPE'] = 'Billing';
            $LEDGER_DATA['ENROLLMENT_LEDGER_PARENT'] = 0;
            $LEDGER_DATA['DUE_DATE'] = $newDate;
            $LEDGER_DATA['BILLED_AMOUNT'] = $getData[8];
            $LEDGER_DATA['PAID_AMOUNT'] = 0;
            $LEDGER_DATA['BALANCE'] = $getData[8];
            $LEDGER_DATA['IS_PAID'] = 1;
            $LEDGER_DATA['PK_ENROLLMENT_PAYMENT'] = 0;
            $LEDGER_DATA['PK_PAYMENT_TYPE'] = 0;
            $LEDGER_DATA['STATUS'] = 'A';
            db_perform_account('DOA_ENROLLMENT_LEDGER', $LEDGER_DATA, 'insert');
            $PK_ENROLLMENT_LEDGER = $db_account->insert_ID();

            $LEDGER_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
            $LEDGER_DATA['PK_ENROLLMENT_BILLING '] = $PK_ENROLLMENT_BILLING->fields['PK_ENROLLMENT_BILLING'];
            $LEDGER_DATA['TRANSACTION_TYPE'] = 'Payment';
            $LEDGER_DATA['ENROLLMENT_LEDGER_PARENT'] = $PK_ENROLLMENT_LEDGER;
            $LEDGER_DATA['DUE_DATE'] = $newDate;
            $LEDGER_DATA['BILLED_AMOUNT'] = 0;
            $LEDGER_DATA['PAID_AMOUNT'] = $getData[8];
            $LEDGER_DATA['BALANCE'] = 0;
            $LEDGER_DATA['IS_PAID'] = 1;
            $LEDGER_DATA['PK_ENROLLMENT_PAYMENT'] = $PK_ENROLLMENT_PAYMENT;
            $LEDGER_DATA['PK_PAYMENT_TYPE'] = ($PK_PAYMENT_TYPE->RecordCount() > 0) ? $PK_PAYMENT_TYPE->fields['PK_PAYMENT_TYPE'] : 0;
            $LEDGER_DATA['STATUS'] = 'A';
            db_perform_account('DOA_ENROLLMENT_LEDGER', $LEDGER_DATA, 'insert');

            /*$BALANCE_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
            $BALANCE_DATA['TOTAL_BALANCE_PAID'] = $getData[8];
            $BALANCE_DATA['TOTAL_BALANCE_USED'] = $getData[11];
            $BALANCE_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
            db_perform('DOA_ENROLLMENT_BALANCE', $BALANCE_DATA, 'insert');*/
            break;

        case "DOA_EVENT":
            $table_data = $db_account->Execute("SELECT * FROM DOA_EVENT WHERE PK_LOCATION = '$PK_LOCATION' AND HEADER = '$getData[4]' AND START_DATE = '$getData[5]' AND START_TIME = '$getData[6]'");
            if ($table_data->RecordCount() == 0) {
                $INSERT_DATA['HEADER'] = $getData[4];
                if ($getData[9] == "G") {
                    $pk_event_type = $db_account->Execute("SELECT PK_EVENT_TYPE FROM DOA_EVENT_TYPE WHERE EVENT_TYPE='General' AND PK_ACCOUNT_MASTER='$PK_ACCOUNT_MASTER'");
                    $INSERT_DATA['PK_EVENT_TYPE'] = $pk_event_type->fields['PK_EVENT_TYPE'];
                } else {
                    $INSERT_DATA['PK_EVENT_TYPE'] = 0;
                }
                $INSERT_DATA['PK_ACCOUNT_MASTER'] = $PK_ACCOUNT_MASTER;
                $INSERT_DATA['PK_LOCATION'] = $PK_LOCATION;
                $INSERT_DATA['START_DATE'] = $getData[5];
                $INSERT_DATA['START_TIME'] = $getData[6];
                $endDateTime = strtotime($getData[5] . ' ' . $getData[6]) + $getData[8] * 60;
                $convertedDate = date('Y-m-d', $endDateTime);
                $convertedTime = date('H:i:s', $endDateTime);
                $INSERT_DATA['END_DATE'] = $convertedDate;
                $INSERT_DATA['END_TIME'] = $convertedTime;
                $INSERT_DATA['DESCRIPTION'] = $getData[15];
                $INSERT_DATA['SHARE_WITH_CUSTOMERS'] = 0;
                $INSERT_DATA['SHARE_WITH_SERVICE_PROVIDERS'] = 0;
                $INSERT_DATA['SHARE_WITH_EMPLOYEES'] = 1;
                if ($getData[10] == "A") {
                    $INSERT_DATA['ACTIVE'] = 1;
                } else {
                    $INSERT_DATA['ACTIVE'] = 0;
                }
                $created_by = explode(" ", $getData[2]);
                $firstName = ($created_by[0]) ?: '';
                $lastName = ($created_by[1]) ?: '';
                $doableNameId = $db->Execute("SELECT PK_USER FROM DOA_USERS WHERE FIRST_NAME='$firstName' AND LAST_NAME = '$lastName'");
                $INSERT_DATA['CREATED_BY'] = $doableNameId->fields['PK_USER'];
                $INSERT_DATA['CREATED_ON'] = date("Y-m-d H:i");
                db_perform_account('DOA_EVENT', $INSERT_DATA, 'insert');
            }
            break;

        case "DOA_APPOINTMENT_MASTER":
            $INSERT_DATA['PK_ACCOUNT_MASTER'] = $PK_ACCOUNT_MASTER;
            $studentId = $getData[3];
            $getEmail = getCustomer($studentId);
            $PK_USER_MASTER = 0;
            if ($getEmail !== 0) {
                $doableUserId = $db->Execute("SELECT DOA_USER_MASTER.PK_USER_MASTER FROM DOA_USER_MASTER INNER JOIN DOA_USERS ON DOA_USER_MASTER.PK_USER=DOA_USERS.PK_USER WHERE DOA_USERS.EMAIL_ID='$getEmail' AND DOA_USER_MASTER.PK_ACCOUNT_MASTER = '$PK_ACCOUNT_MASTER'");
                $PK_USER_MASTER = ($doableUserId->RecordCount() > 0) ? $doableUserId->fields['PK_USER_MASTER'] : NULL;
                $INSERT_DATA['CUSTOMER_ID'] = $PK_USER_MASTER;
            } else {
                $INSERT_DATA['CUSTOMER_ID'] = NULL;
            }

            $getServiceProvider = getUser($getData[1]);
            if ($getServiceProvider !== 0) {
                $SERVICE_PROVIDER_ID = $db->Execute("SELECT PK_USER FROM DOA_USERS WHERE EMAIL_ID = '$getServiceProvider'");
                $INSERT_DATA['SERVICE_PROVIDER_ID'] = ($SERVICE_PROVIDER_ID->RecordCount() > 0) ? $SERVICE_PROVIDER_ID->fields['PK_USER'] : '';
            } else {
                $INSERT_DATA['SERVICE_PROVIDER_ID'] = NULL;
            }

            $doableServiceId = $db_account->Execute("SELECT PK_SERVICE_MASTER, PK_SERVICE_CODE, DESCRIPTION FROM DOA_SERVICE_CODE WHERE SERVICE_CODE ='$getData[9]'");
            $PK_SERVICE_MASTER = ($doableServiceId->RecordCount() > 0) ? $doableServiceId->fields['PK_SERVICE_MASTER'] : 0;
            $PK_SERVICE_CODE = ($doableServiceId->RecordCount() > 0) ? $doableServiceId->fields['PK_SERVICE_CODE'] : 0;

            $getServiceCodeId = $db_account->Execute("SELECT PK_SCHEDULING_CODE FROM DOA_SCHEDULING_CODE WHERE SCHEDULING_CODE = '$getData[14]'");
            if ($getServiceCodeId->RecordCount() > 0) {
                $PK_SCHEDULING_CODE = $getServiceCodeId->fields['PK_SCHEDULING_CODE'];
            } else {
                $PK_SCHEDULING_CODE = 0;
            }

            $enrollment_data = $db_account->Execute("SELECT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_SERVICE, DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION FROM DOA_ENROLLMENT_MASTER JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_USER_MASTER = '$PK_USER_MASTER' AND DOA_ENROLLMENT_SERVICE.PK_SERVICE_MASTER = '$PK_SERVICE_MASTER' AND DOA_ENROLLMENT_MASTER.ALL_APPOINTMENT_DONE = 0 ORDER BY PK_ENROLLMENT_MASTER ASC LIMIT 1");
            $PK_ENROLLMENT_MASTER_CHECK = ($enrollment_data->RecordCount() > 0) ? $enrollment_data->fields['PK_ENROLLMENT_MASTER'] : 0;
            $PK_ENROLLMENT_SERVICE_CHECK = ($enrollment_data->RecordCount() > 0) ? $enrollment_data->fields['PK_ENROLLMENT_SERVICE'] : 0;
            $SESSION_COUNT = ($enrollment_data->RecordCount() > 0) ? $enrollment_data->fields['NUMBER_OF_SESSION'] : 0;

            if ($PK_ENROLLMENT_MASTER_CHECK > 0 && $PK_ENROLLMENT_SERVICE_CHECK > 0) {
                [$PK_ENROLLMENT_MASTER, $PK_ENROLLMENT_SERVICE] = checkSessionCount($SESSION_COUNT, $PK_ENROLLMENT_MASTER_CHECK, $PK_ENROLLMENT_SERVICE_CHECK, $PK_USER_MASTER, $PK_SERVICE_MASTER);
            } else {
                $PK_ENROLLMENT_MASTER = 0;
                $PK_ENROLLMENT_SERVICE = 0;
            }

            if ($PK_SERVICE_MASTER == 0 && $PK_SERVICE_CODE == 0 && $PK_ENROLLMENT_MASTER == 0 && $PK_ENROLLMENT_SERVICE == 0) {
                $PK_SERVICE_MASTER = $PK_SERVICE_MASTER_STANDARD;
                $PK_SERVICE_CODE = $PK_SERVICE_CODE_STANDARD;
                $checkEnrollmentExist = $db_account->Execute("SELECT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_SERVICE FROM DOA_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_USER_MASTER = '$PK_USER_MASTER' AND DOA_ENROLLMENT_SERVICE.PK_SERVICE_MASTER = '$PK_SERVICE_MASTER' AND DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE = '$PK_SERVICE_CODE'");
                if ($checkEnrollmentExist->RecordCount() > 0) {
                    $PK_ENROLLMENT_MASTER = $checkEnrollmentExist->fields['PK_ENROLLMENT_MASTER'];
                    $PK_ENROLLMENT_SERVICE = $checkEnrollmentExist->fields['PK_ENROLLMENT_SERVICE'];
                } else {
                    $ENROLLMENT_DATA['ENROLLMENT_ID'] = 0;
                    $ENROLLMENT_DATA['ENROLLMENT_NAME'] = 'Standard (S-1)';
                    $account_data = $db->Execute("SELECT ENROLLMENT_ID_CHAR, ENROLLMENT_ID_NUM FROM `DOA_ACCOUNT_MASTER` WHERE `PK_ACCOUNT_MASTER` = '$PK_ACCOUNT_MASTER'");
                    if ($account_data->RecordCount() > 0) {
                        $enrollment_char = $account_data->fields['ENROLLMENT_ID_CHAR'];
                    } else {
                        $enrollment_char = 'ENR';
                    }
                    $enrollment_data = $db_account->Execute("SELECT ENROLLMENT_ID FROM `DOA_ENROLLMENT_MASTER` WHERE `PK_ACCOUNT_MASTER` = '$PK_ACCOUNT_MASTER' ORDER BY PK_ENROLLMENT_MASTER DESC LIMIT 1");
                    if ($enrollment_data->RecordCount() > 0) {
                        $last_enrollment_id = str_replace($enrollment_char, '', $enrollment_data->fields['ENROLLMENT_ID']);
                        $ENROLLMENT_DATA['ENROLLMENT_ID'] = $enrollment_char . (intval($last_enrollment_id) + 1);
                    } else {
                        $ENROLLMENT_DATA['ENROLLMENT_ID'] = $enrollment_char . $account_data->fields['ENROLLMENT_ID_NUM'];
                    }

                    $ENROLLMENT_DATA['PK_ACCOUNT_MASTER'] = $PK_ACCOUNT_MASTER;
                    $customerId = $getData[4];
                    $doableCustomerId = $db->Execute("SELECT DOA_USER_MASTER.PK_USER_MASTER FROM DOA_USER_MASTER INNER JOIN DOA_USERS ON DOA_USER_MASTER.PK_USER=DOA_USERS.PK_USER WHERE DOA_USERS.USER_ID='$customerId' AND DOA_USER_MASTER.PK_ACCOUNT_MASTER = '$PK_ACCOUNT_MASTER'");
                    $ENROLLMENT_DATA['PK_USER_MASTER'] = $PK_USER_MASTER;
                    $ENROLLMENT_DATA['AGREEMENT_PDF_LINK'] = '';
                    $ENROLLMENT_DATA['ENROLLMENT_BY_ID'] = $PK_ACCOUNT_MASTER;
                    $ENROLLMENT_DATA['ACTIVE'] = 1;
                    $ENROLLMENT_DATA['STATUS'] = "A";
                    $ENROLLMENT_DATA['CREATED_BY'] = $PK_ACCOUNT_MASTER;
                    $ENROLLMENT_DATA['CREATED_ON'] = date("Y-m-d H:i");
                    db_perform_account('DOA_ENROLLMENT_MASTER', $ENROLLMENT_DATA, 'insert');
                    $PK_ENROLLMENT_MASTER = $db_account->insert_ID();

                    $SERVICE_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
                    $SERVICE_DATA['PK_SERVICE_MASTER'] = $PK_SERVICE_MASTER;
                    $SERVICE_DATA['PK_SERVICE_CODE'] = $PK_SERVICE_CODE;
                    $SERVICE_DATA['FREQUENCY'] = 0;
                    $SERVICE_DATA['SERVICE_DETAILS'] = 'Standard Enrollment';
                    $SERVICE_DATA['NUMBER_OF_SESSION'] = 100;
                    $SERVICE_DATA['PRICE_PER_SESSION'] = 0;
                    $SERVICE_DATA['TOTAL'] = 0;
                    $SERVICE_DATA['DISCOUNT'] = 0;
                    $SERVICE_DATA['FINAL_AMOUNT'] = 0;
                    db_perform_account('DOA_ENROLLMENT_SERVICE', $SERVICE_DATA, 'insert');
                    $PK_ENROLLMENT_SERVICE = $db_account->insert_ID();
                }
            }

            $INSERT_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
            $INSERT_DATA['PK_ENROLLMENT_SERVICE'] = $PK_ENROLLMENT_SERVICE;
            $INSERT_DATA['PK_SERVICE_MASTER'] = $PK_SERVICE_MASTER;
            $INSERT_DATA['PK_SERVICE_CODE'] = $PK_SERVICE_CODE;
            $INSERT_DATA['PK_SCHEDULING_CODE'] = $PK_SCHEDULING_CODE;
            $orgDate = $getData[5];
            $newDate = date("Y-m-d", strtotime($orgDate));
            $INSERT_DATA['DATE'] = $newDate;
            $INSERT_DATA['START_TIME'] = $getData[6];
            $endTime = strtotime($getData[6]) + $getData[8] * 60;
            $convertedTime = date('H:i:s', $endTime);
            $INSERT_DATA['END_TIME'] = $convertedTime;

            if ($getData[11] == "A") {
                $INSERT_DATA['PK_APPOINTMENT_STATUS'] = 5;
            } elseif ($getData[11] == "C") {
                $INSERT_DATA['PK_APPOINTMENT_STATUS'] = 6;
            } elseif ($getData[11] == "CM") {
                $INSERT_DATA['PK_APPOINTMENT_STATUS'] = 7;
            } elseif ($getData[11] == "I") {
                $INSERT_DATA['PK_APPOINTMENT_STATUS'] = 6;
            } elseif ($getData[11] == "N") {
                $INSERT_DATA['PK_APPOINTMENT_STATUS'] = 6;
            } elseif ($getData[11] == "NS") {
                $INSERT_DATA['PK_APPOINTMENT_STATUS'] = 4;
            } elseif ($getData[11] == "O") {
                $INSERT_DATA['PK_APPOINTMENT_STATUS'] = 6;
            } elseif ($getData[11] == "S") {
                $INSERT_DATA['PK_APPOINTMENT_STATUS'] = 8;
            }
            $INSERT_DATA['COMMENT'] = $getData[17];
            $INSERT_DATA['ACTIVE'] = 1;
            if ($getData[16] == "V") {
                $INSERT_DATA['IS_PAID'] = 1;
            } elseif ($getData[16] == "U") {
                $INSERT_DATA['IS_PAID'] = 0;
            }
            $INSERT_DATA['CREATED_BY'] = $PK_ACCOUNT_MASTER;
            $INSERT_DATA['CREATED_ON'] = date("Y-m-d H:i");
            db_perform_account('DOA_APPOINTMENT_MASTER', $INSERT_DATA, 'insert');
            break;
        default:
            break;
    }
}

function checkSessionCount($SESSION_COUNT, $PK_ENROLLMENT_MASTER, $PK_ENROLLMENT_SERVICE, $PK_USER_MASTER, $PK_SERVICE_MASTER) {
    global $db;
    global $db_account;
    $SESSION_CREATED = $db_account->Execute("SELECT COUNT(`PK_ENROLLMENT_MASTER`) AS SESSION_COUNT FROM `DOA_APPOINTMENT_MASTER` WHERE `PK_ENROLLMENT_MASTER` = ".$PK_ENROLLMENT_MASTER." AND PK_ENROLLMENT_SERVICE = ".$PK_ENROLLMENT_SERVICE);
    if ($SESSION_CREATED->RecordCount() > 0 && $SESSION_CREATED->fields['SESSION_COUNT'] >= $SESSION_COUNT) {
        $db_account->Execute("UPDATE `DOA_ENROLLMENT_MASTER` SET `ALL_APPOINTMENT_DONE` = '1' WHERE PK_ENROLLMENT_MASTER = '$PK_ENROLLMENT_MASTER'");
        $enrollment_data = $db_account->Execute("SELECT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER, DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_SERVICE, DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION FROM DOA_ENROLLMENT_MASTER JOIN DOA_ENROLLMENT_SERVICE ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_USER_MASTER = '$PK_USER_MASTER' AND DOA_ENROLLMENT_SERVICE.PK_SERVICE_MASTER = '$PK_SERVICE_MASTER' AND DOA_ENROLLMENT_MASTER.ALL_APPOINTMENT_DONE = 0 ORDER BY PK_ENROLLMENT_MASTER ASC LIMIT 1");
        $PK_ENROLLMENT_MASTER_NEW = ($enrollment_data->RecordCount() > 0) ? $enrollment_data->fields['PK_ENROLLMENT_MASTER'] : 0;
        $PK_ENROLLMENT_SERVICE_NEW = ($enrollment_data->RecordCount() > 0) ? $enrollment_data->fields['PK_ENROLLMENT_SERVICE'] : 0;
        $SESSION_COUNT = ($enrollment_data->RecordCount() > 0) ? $enrollment_data->fields['NUMBER_OF_SESSION'] : 0;
        if ($PK_ENROLLMENT_MASTER_NEW > 0 && $PK_ENROLLMENT_SERVICE_NEW > 0) {
            checkSessionCount($SESSION_COUNT, $PK_ENROLLMENT_MASTER_NEW, $PK_ENROLLMENT_SERVICE_NEW, $PK_USER_MASTER, $PK_SERVICE_MASTER);
        } else {
            return [$PK_ENROLLMENT_MASTER_NEW, $PK_ENROLLMENT_SERVICE_NEW];
        }
    } else {
        return [$PK_ENROLLMENT_MASTER, $PK_ENROLLMENT_SERVICE];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<?php require_once('../includes/header.php');?>
<body class="skin-default-dark fixed-layout">
<?php require_once('../includes/loader.php');?>
<div id="main-wrapper">
    <?php require_once('../includes/top_menu.php');?>
    <div class="page-wrapper">
        <?php require_once('../includes/top_menu_bar.php') ?>
        <div class="container-fluid">
            <div class="row page-titles">
                <div class="col-md-5 align-self-center">
                    <h4 class="text-themecolor"><?=$title?></h4>
                </div>
                <div class="col-md-7 align-self-center text-end">
                    <div class="d-flex justify-content-end align-items-center">
                        <ol class="breadcrumb justify-content-end">
                            <li class="breadcrumb-item"><a href="setup.php">Setup</a></li>
                            <li class="breadcrumb-item active"><?=$title?></li>
                        </ol>
                    </div>
                </div>
            </div>
            <form action="" method="post" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label">Business Name</label>
                            <select class="form-control" name="PK_ACCOUNT_MASTER" id="PK_ACCOUNT_MASTER">
                                <option value="">Select Business</option>
                                <?php
                                $row = $db->Execute("SELECT DOA_ACCOUNT_MASTER.*, DOA_BUSINESS_TYPE.BUSINESS_TYPE FROM DOA_ACCOUNT_MASTER LEFT JOIN DOA_BUSINESS_TYPE ON DOA_BUSINESS_TYPE.PK_BUSINESS_TYPE = DOA_ACCOUNT_MASTER.PK_BUSINESS_TYPE ORDER BY CREATED_ON DESC");
                                while (!$row->EOF) { ?>
                                    <option value="<?php echo $row->fields['PK_ACCOUNT_MASTER'];?>" ><?=$row->fields['BUSINESS_NAME']?></option>
                                <?php $row->MoveNext(); } ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label class="form-label">Select Location</label>
                            <select class="form-control" name="PK_LOCATION" id="PK_LOCATION">
                                <option value="">Select Location</option>
                                <?php
                                $row = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME FROM DOA_LOCATION WHERE ACTIVE = 1");
                                while (!$row->EOF) { ?>
                                    <option value="<?php echo $row->fields['PK_LOCATION'];?>"><?=$row->fields['LOCATION_NAME']?></option>
                                <?php $row->MoveNext(); } ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label class="form-label">Select Database Name</label>
                            <select class="form-control" name="DATABASE_NAME" id="DATABASE_NAME">
                                <option value="">Select Database Name</option>
                                <option value="AMTO">AMTO</option>
                                <option value="AMWH">AMWH</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label">Select Table Name</label>
                            <select class="form-control" name="TABLE_NAME" id="TABLE_NAME" onchange="viewCsvDownload(this)">
                                <option value="">Select Table Name</option>
                                <option value="DOA_INQUIRY_METHOD">DOA_INQUIRY_METHOD</option>
                                <!--<option value="DOA_EVENT_TYPE">DOA_EVENT_TYPE</option>
                                <option value="DOA_HOLIDAY_LIST">DOA_HOLIDAY_LIST</option>-->
                                <option value="DOA_USERS">DOA_USERS</option>
                                <option value="DOA_CUSTOMER">DOA_CUSTOMER</option>
                                <option value="DOA_SERVICE_MASTER">DOA_SERVICE_MASTER</option>
                                <option value="DOA_SCHEDULING_CODE">DOA_SCHEDULING_CODE</option>
                                <option value="DOA_ENROLLMENT_TYPE">DOA_ENROLLMENT_TYPE</option>
                                <option value="DOA_ENROLLMENT_MASTER">DOA_ENROLLMENT_MASTER</option>
                                <option value="DOA_ENROLLMENT_SERVICE">DOA_ENROLLMENT_SERVICE</option>
                                <option value="DOA_ENROLLMENT_PAYMENT">DOA_ENROLLMENT_PAYMENT</option>
                                <option value="DOA_EVENT">DOA_EVENT</option>
                                <option value="DOA_APPOINTMENT_MASTER">DOA_APPOINTMENT_MASTER</option>
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
<?php require_once('../includes/footer.php');?>
</body>
<script>
    function viewCsvDownload(param) {
        let table_name = $(param).val();
        $('#view_download_div').html(`<a href="../uploads/csv_upload/${table_name}.csv" target="_blank">View Sample</a>`);
    }
</script>
</html>