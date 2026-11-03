<?php
error_reporting(0);
require_once('../global/config.php');
$title = "Upload CSV";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 1) {
    header("location:../login.php");
    exit;
}

if (!empty($_POST)) {
    // Allowed mime types
    $fileMimes = array(
        'text/x-comma-separated-values',
        'text/comma-separated-values',
        'application/octet-stream',
        'application/vnd.ms-excel',
        'application/x-csv',
        'text/x-csv',
        'text/csv',
        'application/csv',
        'application/excel',
        'application/vnd.msexcel',
        'text/plain'
    );

    // Validate whether selected file is a CSV file
    if (!empty($_FILES['file']['name']) && in_array($_FILES['file']['type'], $fileMimes)) {
        $account_data = $db->Execute("SELECT DB_NAME FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER = " . $_POST['PK_ACCOUNT_MASTER']);
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

        // Open uploaded CSV file with read-only mode
        $csvFile = fopen($_FILES['file']['tmp_name'], 'r');
        $lineNumber = 1;

        $standardServicePkId = $db_account->Execute("SELECT PK_SERVICE_CODE, PK_SERVICE_MASTER FROM DOA_SERVICE_CODE WHERE SERVICE_CODE LIKE 'S-1'");
        $PK_SERVICE_CODE_STANDARD = $standardServicePkId->fields['PK_SERVICE_CODE'];
        $PK_SERVICE_MASTER_STANDARD = $standardServicePkId->fields['PK_SERVICE_MASTER'];
        $PK_LOCATION = $_POST['PK_LOCATION'];

        // Parse data from CSV file line by line
        while (($getData = fgetcsv($csvFile, 10000, ",")) !== FALSE) {
            if ($lineNumber === 1) {
                $lineNumber++;
                continue;
            }
            switch ($_POST['TABLE_NAME']) {
                case 'DOA_INQUIRY_METHOD':
                    $INQUIRY_METHOD = $getData[1];
                    $table_data = $db_account->Execute("SELECT * FROM DOA_INQUIRY_METHOD WHERE INQUIRY_METHOD='$INQUIRY_METHOD' AND PK_ACCOUNT_MASTER='$_POST[PK_ACCOUNT_MASTER]'");
                    if ($table_data->RecordCount() == 0) {
                        $INSERT_DATA['PK_ACCOUNT_MASTER'] = $_POST['PK_ACCOUNT_MASTER'];
                        $INSERT_DATA['INQUIRY_METHOD'] = $INQUIRY_METHOD;
                        $INSERT_DATA['ACTIVE'] = 1;
                        $INSERT_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
                        $INSERT_DATA['CREATED_ON'] = date("Y-m-d H:i");
                        db_perform_account('DOA_INQUIRY_METHOD', $INSERT_DATA, 'insert');
                    }
                    break;

                case 'DOA_EVENT_TYPE':
                    $table_data = $db_account->Execute("SELECT * FROM DOA_EVENT_TYPE WHERE EVENT_TYPE='$getData[0]' AND PK_ACCOUNT_MASTER='$_POST[PK_ACCOUNT_MASTER]'");
                    if ($table_data->RecordCount() == 0) {
                        $INSERT_DATA['PK_ACCOUNT_MASTER'] = $_POST['PK_ACCOUNT_MASTER'];
                        $INSERT_DATA['EVENT_TYPE'] = $getData[0];
                        $INSERT_DATA['COLOR_CODE'] = $getData[1];
                        $INSERT_DATA['ACTIVE'] = 1;
                        $INSERT_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
                        $INSERT_DATA['CREATED_ON'] = date("Y-m-d H:i");
                        db_perform_account('DOA_EVENT_TYPE', $INSERT_DATA, 'insert');
                    }
                    break;

                case 'DOA_HOLIDAY_LIST':
                    $table_data = $db_account->Execute("SELECT * FROM DOA_HOLIDAY_LIST WHERE HOLIDAY_NAME='$getData[1]' AND PK_ACCOUNT_MASTER='$_POST[PK_ACCOUNT_MASTER]'");
                    if ($table_data->RecordCount() == 0) {
                        $INSERT_DATA['PK_ACCOUNT_MASTER'] = $_POST['PK_ACCOUNT_MASTER'];
                        $INSERT_DATA['HOLIDAY_DATE'] = date("Y-m-d", strtotime($getData[0]));
                        $INSERT_DATA['HOLIDAY_NAME'] = $getData[1];
                        db_perform_account('DOA_HOLIDAY_LIST', $INSERT_DATA, 'insert');
                    }
                    break;

                case 'DOA_USERS':
                    $roleId = $getData[1];
                    $getRole = getRole($roleId);
                    $doableRoleId = $db->Execute("SELECT PK_ROLES FROM DOA_ROLES WHERE ROLES='$getRole'");
                    $USER_DATA['PK_ACCOUNT_MASTER'] = $_POST['PK_ACCOUNT_MASTER'];
                    $USER_DATA['FIRST_NAME'] = trim($getData[3]);
                    $USER_DATA['LAST_NAME'] = trim($getData[4]);
                    $USER_DATA['USER_NAME'] = $getData[19];
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
                        $USER_DATA_ACCOUNT['PK_ACCOUNT_MASTER'] = $_POST['PK_ACCOUNT_MASTER'];
                        $USER_DATA_ACCOUNT['FIRST_NAME'] = trim($getData[3]);
                        $USER_DATA_ACCOUNT['LAST_NAME'] = trim($getData[4]);
                        $USER_DATA_ACCOUNT['USER_NAME'] = $getData[19];
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
                    break;

                case 'DOA_STAFF':
                    $USER_DATA['PK_ACCOUNT_MASTER'] = $_POST['PK_ACCOUNT_MASTER'];
                    $staff_name = explode(" ", $getData[0]);
                    $USER_DATA['FIRST_NAME'] = isset($staff_name[0]) ? $staff_name[0] : '';
                    $USER_DATA['LAST_NAME'] = isset($staff_name[1]) ? $staff_name[1] : '';
                    $USER_DATA['USER_ID'] = $getData[1];
                    $USER_DATA['EMAIL_ID'] = $getData[14];
                    if (!empty($getData[13]) && $getData[13] != null) {
                        $USER_DATA['PHONE'] = $getData[13];
                    } elseif (!empty($getData[12]) && $getData[12] != null) {
                        $USER_DATA['PHONE'] = $getData[12];
                    }
                    $USER_DATA['PASSWORD'] = $getData[20];
                    $USER_DATA['GENDER'] = '';
                    $USER_DATA['DOB'] = '';
                    $USER_DATA['ADDRESS'] = $getData[7];
                    $USER_DATA['ADDRESS_1'] = $getData[8];
                    $USER_DATA['CITY'] = $getData[9];
                    $USER_DATA['PK_COUNTRY'] = 1;
                    $state_data = $db->Execute("SELECT PK_STATES FROM DOA_STATES WHERE STATE_NAME='$getData[10]' OR STATE_CODE='$getData[10]'");
                    $USER_DATA['PK_STATES'] = ($state_data->RecordCount() > 0) ? $state_data->fields['PK_STATES'] : 0;
                    $USER_DATA['ZIP'] = $getData[11];
                    $USER_DATA['NOTES'] = $getData[18];
                    $USER_DATA['ACTIVE'] = 1;
                    $USER_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
                    $USER_DATA['CREATED_ON'] = date("Y-m-d H:i");
                    db_perform('DOA_USERS', $USER_DATA, 'insert');
                    $PK_USER = $db->insert_ID();

                    if ($PK_USER) {
                        $USER_ROLE_DATA['PK_USER'] = $PK_USER;
                        $USER_ROLE_DATA['PK_ROLES'] = 5;
                        db_perform('DOA_USER_ROLES', $USER_ROLE_DATA, 'insert');

                        $USER_DATA_ACCOUNT['PK_USER_MASTER_DB'] = $PK_USER;
                        $USER_DATA_ACCOUNT['PK_ACCOUNT_MASTER'] = $_POST['PK_ACCOUNT_MASTER'];
                        $USER_DATA_ACCOUNT['FIRST_NAME'] = isset($staff_name[0]) ? $staff_name[0] : '';
                        $USER_DATA_ACCOUNT['LAST_NAME'] = isset($staff_name[1]) ? $staff_name[1] : '';
                        $USER_DATA_ACCOUNT['USER_NAME'] = $getData[1];
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
                    break;

                case 'DOA_CUSTOMER':
                    $USER_DATA['PK_ACCOUNT_MASTER'] = $_POST['PK_ACCOUNT_MASTER'];
                    $USER_DATA['USER_NAME'] = $getData[0];
                    $USER_DATA['USER_ID'] = $getData[0];
                    $customer_name = preg_split("/\s*[,&]|\s+and\s+\s*/", $getData[1]);
                    $USER_DATA['FIRST_NAME'] = isset($customer_name[1]) ? $customer_name[1] : '';
                    $USER_DATA['LAST_NAME'] = isset($customer_name[0]) ? $customer_name[0] : '';
                    $USER_DATA['EMAIL_ID'] = $getData[18];
                    //$USER_DATA['HOME_PHONE'] = $getData[18];
                    if (!empty($getData[14]) && $getData[14] != null && $getData[14] != "   -   -    *") {
                        $USER_DATA['PHONE'] = $getData[14];
                    } elseif (!empty($getData[15]) && $getData[15] != null && $getData[15] != "   -   -    *") {
                        $USER_DATA['PHONE'] = $getData[15];
                    } elseif (!empty($getData[16]) && $getData[16] != null && $getData[16] != "   -   -    *") {
                        $USER_DATA['PHONE'] = $getData[16];
                    }
                    if ($getData[6] == 'M') {
                        $USER_DATA['GENDER'] = 'Male';
                    } elseif ($getData[6] == 'F') {
                        $USER_DATA['GENDER'] = 'Female';
                    } elseif ($getData[6] == 'C') {
                        $USER_DATA['GENDER'] = 'Common';
                    }

                    $USER_DATA['DOB'] = date("Y-m-d", strtotime($getData[4]));
                    if ($getData[9] == 1) {
                        $USER_DATA['MARITAL_STATUS'] = "Married";
                    } elseif ($getData[9] == 0) {
                        $USER_DATA['MARITAL_STATUS'] = "Unmarried";
                    }

                    $USER_DATA['ADDRESS'] = $getData[9];
                    $USER_DATA['ADDRESS_1'] = $getData[10];
                    $USER_DATA['CITY'] = $getData[11];
                    $USER_DATA['PK_COUNTRY'] = 1;
                    $state_data = $db->Execute("SELECT PK_STATES FROM DOA_STATES WHERE STATE_NAME='$getData[12]' OR STATE_CODE='$getData[12]'");
                    $USER_DATA['PK_STATES'] = ($state_data->RecordCount() > 0) ? $state_data->fields['PK_STATES'] : 0;
                    $USER_DATA['ZIP'] = $getData[13];
                    $USER_DATA['NOTES'] = $getData[44];
                    $USER_DATA['ACTIVE'] = ($getData[33] == 'C') ? 1 : 0;
                    $USER_DATA['JOINING_DATE'] = date("Y-m-d", strtotime($getData[8]));
                    $USER_DATA['IS_DELETED'] = 0;
                    $USER_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
                    $USER_DATA['CREATED_ON'] = date("Y-m-d H:i");
                    db_perform('DOA_USERS', $USER_DATA, 'insert');
                    $PK_USER = $db->insert_ID();

                    if ($PK_USER) {
                        $USER_DATA_ACCOUNT['PK_USER_MASTER_DB'] = $PK_USER;
                        $USER_DATA_ACCOUNT['PK_ACCOUNT_MASTER'] = $_POST['PK_ACCOUNT_MASTER'];
                        $customer_name = preg_split("/\s*[,&]|\s+and\s+\s*/", $getData[1]);
                        $USER_DATA_ACCOUNT['FIRST_NAME'] = isset($customer_name[1]) ? $customer_name[1] : '';
                        $USER_DATA_ACCOUNT['LAST_NAME'] = isset($customer_name[0]) ? $customer_name[0] : '';
                        $USER_DATA_ACCOUNT['USER_NAME'] = $getData[0];
                        $USER_DATA_ACCOUNT['EMAIL_ID'] = $getData[18];
                        if (!empty($getData[14]) && $getData[14] != null && $getData[14] != "   -   -    *") {
                            $USER_DATA_ACCOUNT['PHONE'] = $getData[14];
                        } elseif (!empty($getData[15]) && $getData[15] != null && $getData[15] != "   -   -    *") {
                            $USER_DATA_ACCOUNT['PHONE'] = $getData[15];
                        } elseif (!empty($getData[16]) && $getData[16] != null && $getData[16] != "   -   -    *") {
                            $USER_DATA_ACCOUNT['PHONE'] = $getData[16];
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
                        $USER_MASTER_DATA['PK_ACCOUNT_MASTER'] = $_POST['PK_ACCOUNT_MASTER'];
                        $USER_MASTER_DATA['PRIMARY_LOCATION_ID'] = $PK_LOCATION;
                        db_perform('DOA_USER_MASTER', $USER_MASTER_DATA, 'insert');
                        $PK_USER_MASTER = $db->insert_ID();

                        if ($PK_USER_MASTER) {
                            $CUSTOMER_DATA['PK_USER_MASTER'] = $PK_USER_MASTER;
                            $customer_name = preg_split("/\s*[,&]|\s+and\s+\s*/", $getData[1]);
                            $CUSTOMER_DATA['FIRST_NAME'] = isset($customer_name[1]) ? $customer_name[1] : '';
                            $CUSTOMER_DATA['LAST_NAME'] = isset($customer_name[0]) ? $customer_name[0] : '';
                            $CUSTOMER_DATA['EMAIL'] = $getData[18];
                            if (!empty($getData[14]) && $getData[14] != null && $getData[14] != "   -   -    *") {
                                $CUSTOMER_DATA['PHONE'] = $getData[14];
                            } elseif (!empty($getData[15]) && $getData[15] != null && $getData[15] != "   -   -    *") {
                                $CUSTOMER_DATA['PHONE'] = $getData[15];
                            } elseif (!empty($getData[16]) && $getData[16] != null && $getData[16] != "   -   -    *") {
                                $CUSTOMER_DATA['PHONE'] = $getData[16];
                            }
                            $CUSTOMER_DATA['DOB'] = date("Y-m-d", strtotime($getData[4]));
                            $CUSTOMER_DATA['CALL_PREFERENCE'] = $getData[17];
                            //$CUSTOMER_DATA['REMINDER_OPTION'] = $getData[23];
                            $partner_name = preg_split("/\s*[,&]|\s+and\s+\s*/", $getData[1]);
                            $CUSTOMER_DATA['PARTNER_FIRST_NAME'] = isset($partner_name[3]) ? $partner_name[3] : '';
                            $CUSTOMER_DATA['PARTNER_LAST_NAME'] = isset($partner_name[4]) ? $partner_name[4] : '';
                            // if ($getData[27] == 0) {
                            //     $CUSTOMER_DATA['PARTNER_GENDER'] = "Male";
                            // } elseif ($getData[27] == 1) {
                            //     $CUSTOMER_DATA['PARTNER_GENDER'] = "Female";
                            // }
                            $CUSTOMER_DATA['PARTNER_GENDER'] = "";
                            if (!empty(isset($partner_name[3]))) {
                                $CUSTOMER_DATA['ATTENDING_WITH'] = "With a Partner";
                            } else {
                                $CUSTOMER_DATA['ATTENDING_WITH'] = "Solo";
                            }
                            $CUSTOMER_DATA['PARTNER_DOB'] = date("Y-m-d", strtotime($getData[5]));
                            $CUSTOMER_DATA['IS_PRIMARY'] = 1;
                            db_perform_account('DOA_CUSTOMER_DETAILS', $CUSTOMER_DATA, 'insert');
                            $PK_CUSTOMER_DETAILS = $db_account->insert_ID();

                            if (!empty($getData[14]) && $getData[14] != "   -   -    *") {
                                $PHONE_DATA['PK_CUSTOMER_DETAILS'] = $PK_CUSTOMER_DETAILS;
                                $PHONE_DATA['PHONE'] = $getData[14];
                                db_perform_account('DOA_CUSTOMER_PHONE', $PHONE_DATA, 'insert');
                            }

                            if (!empty($getData[15]) && $getData[15] != "   -   -    *") {
                                $PHONE_DATA['PK_CUSTOMER_DETAILS'] = $PK_CUSTOMER_DETAILS;
                                $PHONE_DATA['PHONE'] = $getData[15];
                                db_perform_account('DOA_CUSTOMER_PHONE', $PHONE_DATA, 'insert');
                            }

                            if (!empty($getData[16]) && $getData[16] != "   -   -    *") {
                                $PHONE_DATA['PK_CUSTOMER_DETAILS'] = $PK_CUSTOMER_DETAILS;
                                $PHONE_DATA['PHONE'] = $getData[16];
                                db_perform_account('DOA_CUSTOMER_PHONE', $PHONE_DATA, 'insert');
                            }

                            if ($getData[7] != "0000-00-00 00:00:00" && $getData[7] > 0) {
                                $SPECIAL_DATA['PK_CUSTOMER_DETAILS'] = $PK_CUSTOMER_DETAILS;
                                $SPECIAL_DATA['SPECIAL_DATE'] = date("Y-m-d", strtotime($getData[7]));
                                $SPECIAL_DATA['DATE_NAME'] = $getData[7];
                                db_perform_account('DOA_CUSTOMER_SPECIAL_DATE', $SPECIAL_DATA, 'insert');
                            }
                            if ($getData[8] != "0000-00-00 00:00:00" && $getData[8] > 0) {
                                $SPECIAL_DATA_1['PK_CUSTOMER_DETAILS'] = $PK_CUSTOMER_DETAILS;
                                $SPECIAL_DATA_1['SPECIAL_DATE'] = date("Y-m-d", strtotime($getData[8]));
                                $SPECIAL_DATA_1['DATE_NAME'] = $getData[8];
                                db_perform_account('DOA_CUSTOMER_SPECIAL_DATE', $SPECIAL_DATA_1, 'insert');
                            }

                            $INQUIRY_VALUE['PK_USER_MASTER'] = $PK_USER_MASTER;
                            $INQUIRY_VALUE['WHAT_PROMPTED_YOU_TO_INQUIRE'] = $getData[27];

                            $INQUIRY_VALUE['PK_INQUIRY_METHOD'] = 0;
                            $INQUIRY_VALUE['INQUIRY_TAKER_ID'] = 0;

                            if (!empty($getData[26])) {
                                $inquiryId = $getData[26];
                                if ($inquiryId == "TEL") {
                                    $getInquiry = "Telephone";
                                } elseif ($inquiryId == "WIN") {
                                    $getInquiry = "Walk In";
                                } elseif ($inquiryId == "GIFT") {
                                    $getInquiry = "Gift";
                                } elseif ($inquiryId == "EML") {
                                    $getInquiry = "Email Message";
                                } else {
                                    $getInquiry = '';
                                }
                                $doableInquiryId = $db_account->Execute("SELECT PK_INQUIRY_METHOD FROM DOA_INQUIRY_METHOD WHERE INQUIRY_METHOD='$getInquiry'");
                                $INQUIRY_VALUE['PK_INQUIRY_METHOD'] = ($doableInquiryId->RecordCount() > 0) ? $doableInquiryId->fields['PK_INQUIRY_METHOD'] : 0;
                            }

                            if (!empty($getData[25])) {
                                $takerId = $getData[25];
                                //$getTaker = getTaker($takerId);
                                $doableTakerId = $db->Execute("SELECT PK_USER FROM DOA_USERS WHERE USER_ID = '" . $takerId . "'");
                                $INQUIRY_VALUE['INQUIRY_TAKER_ID'] = ($doableTakerId->RecordCount() > 0) ? $doableTakerId->fields['PK_USER'] : 0;
                            }
                            db_perform_account('DOA_CUSTOMER_INTEREST_OTHER_DATA', $INQUIRY_VALUE, 'insert');
                        }
                    }
                    break;

                case 'DOA_SERVICE_MASTER':
                    $service_code = trim($getData[0]);
                    $service_name = trim($getData[1]);

                    // Fallback: if no service name, use service code
                    if ($service_name == '') {
                        $service_name = $service_code;
                    }

                    // Skip if still no code (completely empty row)
                    if ($service_code == '') {
                        continue 2;
                    }

                    // Clean up spaces
                    $service_name_clean = preg_replace('/\s+/', ' ', $service_name);


                    // 1. Ensure MASTER
                    $table_data = $db_account->Execute("SELECT * FROM DOA_SERVICE_MASTER WHERE TRIM(SERVICE_NAME) = '$service_name_clean'AND (PK_LOCATION = '$PK_LOCATION' OR PK_LOCATION IS NULL)");

                    if ($table_data->RecordCount() == 0) {
                        $SERVICE = [
                            'PK_LOCATION'      => $PK_LOCATION,
                            'SERVICE_NAME'     => $service_name_clean,
                            'PK_SERVICE_CLASS' => (stripos($service_name_clean, 'Miscellaneous') !== false ? 5 : 2),
                            'IS_SCHEDULE'      => 1,
                            'IS_SUNDRY'        => 0,
                            'DESCRIPTION'      => $service_name_clean,
                            'ACTIVE'           => 1,
                            'IS_DELETED'       => 0,
                            'CREATED_BY'       => $_SESSION['PK_USER'],
                            'CREATED_ON'       => date("Y-m-d H:i")
                        ];
                        db_perform_account('DOA_SERVICE_MASTER', $SERVICE, 'insert');
                        $PK_SERVICE_MASTER = $db_account->insert_ID();
                    } else {
                        $PK_SERVICE_MASTER = $table_data->fields['PK_SERVICE_MASTER'];
                    }

                    // 2. Ensure CODE
                    $code_data = $db_account->Execute("SELECT * FROM DOA_SERVICE_CODE WHERE PK_SERVICE_MASTER = '$PK_SERVICE_MASTER' AND PK_LOCATION = '$PK_LOCATION'");

                    $SERVICE_CODE = [
                        'PK_SERVICE_MASTER' => $PK_SERVICE_MASTER,
                        'PK_LOCATION'       => $PK_LOCATION,
                        'SERVICE_CODE'      => $service_code,
                        'DESCRIPTION'       => $service_name_clean,
                        'IS_GROUP'          => (stripos($service_code, 'GRP') !== false ? 1 : 0),
                        'CAPACITY'          => (stripos($service_code, 'GRP') !== false ? 20 : 0),
                        'IS_CHARGEABLE'     => ($getData[2] == "Y" ? 1 : 0),
                        'SORT_ORDER'        => isset($getData[10]) ? intval($getData[10]) : 0,
                        'ACTIVE'            => 1
                    ];

                    if ($code_data->RecordCount() == 0) {
                        db_perform_account('DOA_SERVICE_CODE', $SERVICE_CODE, 'insert');
                    } else {
                        db_perform_account('DOA_SERVICE_CODE', $SERVICE_CODE, 'update', " PK_SERVICE_MASTER = $PK_SERVICE_MASTER AND PK_LOCATION = '$PK_LOCATION'");
                    }

                    // 3. Ensure LOCATION
                    $loc_data = $db_account->Execute("SELECT * FROM DOA_SERVICE_LOCATION WHERE PK_SERVICE_MASTER = '$PK_SERVICE_MASTER' AND PK_LOCATION = '$PK_LOCATION'");

                    if ($loc_data->RecordCount() == 0) {
                        db_perform_account('DOA_SERVICE_LOCATION', [
                            'PK_SERVICE_MASTER' => $PK_SERVICE_MASTER,
                            'PK_LOCATION'       => $PK_LOCATION
                        ], 'insert');
                    }

                    break;

                case 'DOA_SCHEDULING_CODE':
                    $table_data = $db_account->Execute("SELECT * FROM DOA_SCHEDULING_CODE WHERE SCHEDULING_CODE = '$getData[0]' AND PK_ACCOUNT_MASTER='$_POST[PK_ACCOUNT_MASTER]'");
                    if ($table_data->RecordCount() == 0) {
                        $SCHEDULING_CODE['PK_ACCOUNT_MASTER'] = $_POST['PK_ACCOUNT_MASTER'];
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
                    }
                    break;

                case "DOA_ENROLLMENT_TYPE":
                    $table_data = $db_account->Execute("SELECT * FROM DOA_ENROLLMENT_TYPE WHERE ENROLLMENT_TYPE='$getData[1]' AND PK_ACCOUNT_MASTER='$_POST[PK_ACCOUNT_MASTER]'");
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
                    $ENROLLMENT_DATA['ENROLLMENT_NAME'] = $getData[20];
                    $customerId = $getData[1];
                    $doableCustomerId = $db->Execute("SELECT DOA_USER_MASTER.PK_USER_MASTER FROM DOA_USER_MASTER INNER JOIN DOA_USERS ON DOA_USER_MASTER.PK_USER=DOA_USERS.PK_USER WHERE DOA_USERS.USER_NAME='" . $customerId . "' AND DOA_USER_MASTER.PK_ACCOUNT_MASTER = '$_POST[PK_ACCOUNT_MASTER]'");
                    $ENROLLMENT_DATA['PK_USER_MASTER'] = ($doableCustomerId->RecordCount() > 0) ? $doableCustomerId->fields['PK_USER_MASTER'] : 0;
                    $ENROLLMENT_DATA['PK_LOCATION'] = $PK_LOCATION;
                    $package = $db_account->Execute("SELECT PK_PACKAGE, PACKAGE_NAME FROM DOA_PACKAGE WHERE PK_LOCATION = '$PK_LOCATION' AND PACKAGE_NAME = '" . $getData[2] . "'");
                    if ($package->RecordCount() > 0) {
                        $PK_PACKAGE = $package->fields['PK_PACKAGE'];
                    } else {
                        $PACKAGE_DATA['PK_LOCATION'] = $PK_LOCATION;
                        $PACKAGE_DATA['PACKAGE_NAME'] = $getData[2];
                        $PACKAGE_DATA['SORT_ORDER'] = '';
                        $PACKAGE_DATA['EXPIRY_DATE'] = '';
                        $PACKAGE_DATA['ACTIVE'] = 1;
                        $PACKAGE_DATA['IS_DELETED'] = 0;
                        $PACKAGE_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
                        $PACKAGE_DATA['CREATED_ON'] = date("Y-m-d H:i");
                        db_perform_account('DOA_PACKAGE', $PACKAGE_DATA, 'insert');
                        $PK_PACKAGE = $db_account->insert_ID();

                        if ($getData[9] > 0) {
                            $service_code = 'PRI1';
                            $quantity = $getData[9];
                            $doableServiceId = $db_account->Execute("SELECT PK_SERVICE_MASTER, PK_SERVICE_CODE, DESCRIPTION FROM DOA_SERVICE_CODE WHERE SERVICE_CODE ='$service_code' AND PK_LOCATION = '$PK_LOCATION'");
                            $PK_SERVICE_MASTER = $doableServiceId->fields['PK_SERVICE_MASTER'];
                            $PK_SERVICE_CODE = $doableServiceId->fields['PK_SERVICE_CODE'];
                            $PACKAGE_SERVICE_DATA['PK_PACKAGE'] = $PK_PACKAGE;
                            $PACKAGE_SERVICE_DATA['PK_SERVICE_MASTER'] = $PK_SERVICE_MASTER;
                            $PACKAGE_SERVICE_DATA['PK_SERVICE_CODE'] = $PK_SERVICE_CODE;
                            $PACKAGE_SERVICE_DATA['SERVICE_DETAILS'] = $doableServiceId->fields['DESCRIPTION'];
                            $PACKAGE_SERVICE_DATA['NUMBER_OF_SESSION'] = $quantity;
                            $PACKAGE_SERVICE_DATA['PRICE_PER_SESSION'] = ($quantity > 0) ? $getData[54] / $quantity : 0;
                            $PACKAGE_SERVICE_DATA['TOTAL'] = '';
                            $PACKAGE_SERVICE_DATA['DISCOUNT'] = '';
                            $PACKAGE_SERVICE_DATA['DISCOUNT_TYPE'] = '';
                            $PACKAGE_SERVICE_DATA['FINAL_AMOUNT'] = '';
                            $PACKAGE_SERVICE_DATA['ACTIVE'] = 1;
                            db_perform_account('DOA_PACKAGE_SERVICE', $PACKAGE_SERVICE_DATA, 'insert');
                        }

                        if ($getData[10] > 0) {
                            $service_code = 'PRI2';
                            $quantity = $getData[10];
                            $doableServiceId = $db_account->Execute("SELECT PK_SERVICE_MASTER, PK_SERVICE_CODE, DESCRIPTION FROM DOA_SERVICE_CODE WHERE SERVICE_CODE ='$service_code' AND PK_LOCATION = '$PK_LOCATION'");
                            $PK_SERVICE_MASTER = $doableServiceId->fields['PK_SERVICE_MASTER'];
                            $PK_SERVICE_CODE = $doableServiceId->fields['PK_SERVICE_CODE'];
                            $PACKAGE_SERVICE_DATA['PK_PACKAGE'] = $PK_PACKAGE;
                            $PACKAGE_SERVICE_DATA['PK_SERVICE_MASTER'] = $PK_SERVICE_MASTER;
                            $PACKAGE_SERVICE_DATA['PK_SERVICE_CODE'] = $PK_SERVICE_CODE;
                            $PACKAGE_SERVICE_DATA['SERVICE_DETAILS'] = $doableServiceId->fields['DESCRIPTION'];
                            $PACKAGE_SERVICE_DATA['NUMBER_OF_SESSION'] = $quantity;
                            $PACKAGE_SERVICE_DATA['PRICE_PER_SESSION'] = ($quantity > 0) ? $getData[54] / $quantity : 0;
                            $PACKAGE_SERVICE_DATA['TOTAL'] = '';
                            $PACKAGE_SERVICE_DATA['DISCOUNT'] = '';
                            $PACKAGE_SERVICE_DATA['DISCOUNT_TYPE'] = '';
                            $PACKAGE_SERVICE_DATA['FINAL_AMOUNT'] = '';
                            $PACKAGE_SERVICE_DATA['ACTIVE'] = 1;
                            db_perform_account('DOA_PACKAGE_SERVICE', $PACKAGE_SERVICE_DATA, 'insert');
                        }

                        if ($getData[11] > 0) {
                            $service_code = 'PRI3';
                            $quantity = $getData[11];
                            $doableServiceId = $db_account->Execute("SELECT PK_SERVICE_MASTER, PK_SERVICE_CODE, DESCRIPTION FROM DOA_SERVICE_CODE WHERE SERVICE_CODE ='$service_code' AND PK_LOCATION = '$PK_LOCATION'");
                            $PK_SERVICE_MASTER = $doableServiceId->fields['PK_SERVICE_MASTER'];
                            $PK_SERVICE_CODE = $doableServiceId->fields['PK_SERVICE_CODE'];
                            $PACKAGE_SERVICE_DATA['PK_PACKAGE'] = $PK_PACKAGE;
                            $PACKAGE_SERVICE_DATA['PK_SERVICE_MASTER'] = $PK_SERVICE_MASTER;
                            $PACKAGE_SERVICE_DATA['PK_SERVICE_CODE'] = $PK_SERVICE_CODE;
                            $PACKAGE_SERVICE_DATA['SERVICE_DETAILS'] = $doableServiceId->fields['DESCRIPTION'];
                            $PACKAGE_SERVICE_DATA['NUMBER_OF_SESSION'] = $quantity;
                            $PACKAGE_SERVICE_DATA['PRICE_PER_SESSION'] = ($quantity > 0) ? $getData[54] / $quantity : 0;
                            $PACKAGE_SERVICE_DATA['TOTAL'] = '';
                            $PACKAGE_SERVICE_DATA['DISCOUNT'] = '';
                            $PACKAGE_SERVICE_DATA['DISCOUNT_TYPE'] = '';
                            $PACKAGE_SERVICE_DATA['FINAL_AMOUNT'] = '';
                            $PACKAGE_SERVICE_DATA['ACTIVE'] = 1;
                            db_perform_account('DOA_PACKAGE_SERVICE', $PACKAGE_SERVICE_DATA, 'insert');
                        }

                        if ($getData[12] > 0) {
                            $service_code = 'PRI4';
                            $quantity = $getData[12];
                            $doableServiceId = $db_account->Execute("SELECT PK_SERVICE_MASTER, PK_SERVICE_CODE, DESCRIPTION FROM DOA_SERVICE_CODE WHERE SERVICE_CODE ='$service_code' AND PK_LOCATION = '$PK_LOCATION'");
                            $PK_SERVICE_MASTER = $doableServiceId->fields['PK_SERVICE_MASTER'];
                            $PK_SERVICE_CODE = $doableServiceId->fields['PK_SERVICE_CODE'];
                            $PACKAGE_SERVICE_DATA['PK_PACKAGE'] = $PK_PACKAGE;
                            $PACKAGE_SERVICE_DATA['PK_SERVICE_MASTER'] = $PK_SERVICE_MASTER;
                            $PACKAGE_SERVICE_DATA['PK_SERVICE_CODE'] = $PK_SERVICE_CODE;
                            $PACKAGE_SERVICE_DATA['SERVICE_DETAILS'] = $doableServiceId->fields['DESCRIPTION'];
                            $PACKAGE_SERVICE_DATA['NUMBER_OF_SESSION'] = $quantity;
                            $PACKAGE_SERVICE_DATA['PRICE_PER_SESSION'] = ($quantity > 0) ? $getData[54] / $quantity : 0;
                            $PACKAGE_SERVICE_DATA['TOTAL'] = '';
                            $PACKAGE_SERVICE_DATA['DISCOUNT'] = '';
                            $PACKAGE_SERVICE_DATA['DISCOUNT_TYPE'] = '';
                            $PACKAGE_SERVICE_DATA['FINAL_AMOUNT'] = '';
                            $PACKAGE_SERVICE_DATA['ACTIVE'] = 1;
                            db_perform_account('DOA_PACKAGE_SERVICE', $PACKAGE_SERVICE_DATA, 'insert');
                        }

                        if ($getData[14] > 0) {
                            $service_code = 'CMP';
                            $quantity = $getData[14];
                            $doableServiceId = $db_account->Execute("SELECT PK_SERVICE_MASTER, PK_SERVICE_CODE, DESCRIPTION FROM DOA_SERVICE_CODE WHERE SERVICE_CODE ='$service_code' AND PK_LOCATION = '$PK_LOCATION'");
                            $PK_SERVICE_MASTER = $doableServiceId->fields['PK_SERVICE_MASTER'];
                            $PK_SERVICE_CODE = $doableServiceId->fields['PK_SERVICE_CODE'];
                            $PACKAGE_SERVICE_DATA['PK_PACKAGE'] = $PK_PACKAGE;
                            $PACKAGE_SERVICE_DATA['PK_SERVICE_MASTER'] = $PK_SERVICE_MASTER;
                            $PACKAGE_SERVICE_DATA['PK_SERVICE_CODE'] = $PK_SERVICE_CODE;
                            $PACKAGE_SERVICE_DATA['SERVICE_DETAILS'] = $doableServiceId->fields['DESCRIPTION'];
                            $PACKAGE_SERVICE_DATA['NUMBER_OF_SESSION'] = $quantity;
                            $PACKAGE_SERVICE_DATA['PRICE_PER_SESSION'] = ($quantity > 0) ? $getData[54] / $quantity : 0;
                            $PACKAGE_SERVICE_DATA['TOTAL'] = '';
                            $PACKAGE_SERVICE_DATA['DISCOUNT'] = '';
                            $PACKAGE_SERVICE_DATA['DISCOUNT_TYPE'] = '';
                            $PACKAGE_SERVICE_DATA['FINAL_AMOUNT'] = '';
                            $PACKAGE_SERVICE_DATA['ACTIVE'] = 1;
                            db_perform_account('DOA_PACKAGE_SERVICE', $PACKAGE_SERVICE_DATA, 'insert');
                        }

                        if ($getData[15] > 0) {
                            $service_code = 'GRP1';
                            $quantity = $getData[15];
                            $doableServiceId = $db_account->Execute("SELECT PK_SERVICE_MASTER, PK_SERVICE_CODE, DESCRIPTION FROM DOA_SERVICE_CODE WHERE SERVICE_CODE ='$service_code' AND PK_LOCATION = '$PK_LOCATION'");
                            $PK_SERVICE_MASTER = $doableServiceId->fields['PK_SERVICE_MASTER'];
                            $PK_SERVICE_CODE = $doableServiceId->fields['PK_SERVICE_CODE'];
                            $PACKAGE_SERVICE_DATA['PK_PACKAGE'] = $PK_PACKAGE;
                            $PACKAGE_SERVICE_DATA['PK_SERVICE_MASTER'] = $PK_SERVICE_MASTER;
                            $PACKAGE_SERVICE_DATA['PK_SERVICE_CODE'] = $PK_SERVICE_CODE;
                            $PACKAGE_SERVICE_DATA['SERVICE_DETAILS'] = $doableServiceId->fields['DESCRIPTION'];
                            $PACKAGE_SERVICE_DATA['NUMBER_OF_SESSION'] = $quantity;
                            $PACKAGE_SERVICE_DATA['PRICE_PER_SESSION'] = ($quantity > 0) ? $getData[54] / $quantity : 0;
                            $PACKAGE_SERVICE_DATA['TOTAL'] = '';
                            $PACKAGE_SERVICE_DATA['DISCOUNT'] = '';
                            $PACKAGE_SERVICE_DATA['DISCOUNT_TYPE'] = '';
                            $PACKAGE_SERVICE_DATA['FINAL_AMOUNT'] = '';
                            $PACKAGE_SERVICE_DATA['ACTIVE'] = 1;
                            db_perform_account('DOA_PACKAGE_SERVICE', $PACKAGE_SERVICE_DATA, 'insert');
                        }

                        if ($getData[16] > 0) {
                            $service_code = 'GRP2';
                            $quantity = $getData[16];
                            $doableServiceId = $db_account->Execute("SELECT PK_SERVICE_MASTER, PK_SERVICE_CODE, DESCRIPTION FROM DOA_SERVICE_CODE WHERE SERVICE_CODE ='$service_code' AND PK_LOCATION = '$PK_LOCATION'");
                            $PK_SERVICE_MASTER = $doableServiceId->fields['PK_SERVICE_MASTER'];
                            $PK_SERVICE_CODE = $doableServiceId->fields['PK_SERVICE_CODE'];
                            $PACKAGE_SERVICE_DATA['PK_PACKAGE'] = $PK_PACKAGE;
                            $PACKAGE_SERVICE_DATA['PK_SERVICE_MASTER'] = $PK_SERVICE_MASTER;
                            $PACKAGE_SERVICE_DATA['PK_SERVICE_CODE'] = $PK_SERVICE_CODE;
                            $PACKAGE_SERVICE_DATA['SERVICE_DETAILS'] = $doableServiceId->fields['DESCRIPTION'];
                            $PACKAGE_SERVICE_DATA['NUMBER_OF_SESSION'] = $quantity;
                            $PACKAGE_SERVICE_DATA['PRICE_PER_SESSION'] = ($quantity > 0) ? $getData[54] / $quantity : 0;
                            $PACKAGE_SERVICE_DATA['TOTAL'] = '';
                            $PACKAGE_SERVICE_DATA['DISCOUNT'] = '';
                            $PACKAGE_SERVICE_DATA['DISCOUNT_TYPE'] = '';
                            $PACKAGE_SERVICE_DATA['FINAL_AMOUNT'] = '';
                            $PACKAGE_SERVICE_DATA['ACTIVE'] = 1;
                            db_perform_account('DOA_PACKAGE_SERVICE', $PACKAGE_SERVICE_DATA, 'insert');
                        }

                        if ($getData[17] > 0) {
                            $service_code = 'PRT';
                            $quantity = $getData[17];
                            $doableServiceId = $db_account->Execute("SELECT PK_SERVICE_MASTER, PK_SERVICE_CODE, DESCRIPTION FROM DOA_SERVICE_CODE WHERE SERVICE_CODE ='$service_code' AND PK_LOCATION = '$PK_LOCATION'");
                            $PK_SERVICE_MASTER = $doableServiceId->fields['PK_SERVICE_MASTER'];
                            $PK_SERVICE_CODE = $doableServiceId->fields['PK_SERVICE_CODE'];
                            $PACKAGE_SERVICE_DATA['PK_PACKAGE'] = $PK_PACKAGE;
                            $PACKAGE_SERVICE_DATA['PK_SERVICE_MASTER'] = $PK_SERVICE_MASTER;
                            $PACKAGE_SERVICE_DATA['PK_SERVICE_CODE'] = $PK_SERVICE_CODE;
                            $PACKAGE_SERVICE_DATA['SERVICE_DETAILS'] = $doableServiceId->fields['DESCRIPTION'];
                            $PACKAGE_SERVICE_DATA['NUMBER_OF_SESSION'] = $quantity;
                            $PACKAGE_SERVICE_DATA['PRICE_PER_SESSION'] = ($quantity > 0) ? $getData[54] / $quantity : 0;
                            $PACKAGE_SERVICE_DATA['TOTAL'] = '';
                            $PACKAGE_SERVICE_DATA['DISCOUNT'] = '';
                            $PACKAGE_SERVICE_DATA['DISCOUNT_TYPE'] = '';
                            $PACKAGE_SERVICE_DATA['FINAL_AMOUNT'] = '';
                            $PACKAGE_SERVICE_DATA['ACTIVE'] = 1;
                            db_perform_account('DOA_PACKAGE_SERVICE', $PACKAGE_SERVICE_DATA, 'insert');
                        }

                        if ($getData[18] > 0) {
                            $service_code = 'NPRI';
                            $quantity = $getData[18];
                            $doableServiceId = $db_account->Execute("SELECT PK_SERVICE_MASTER, PK_SERVICE_CODE, DESCRIPTION FROM DOA_SERVICE_CODE WHERE SERVICE_CODE ='$service_code' AND PK_LOCATION = '$PK_LOCATION'");
                            $PK_SERVICE_MASTER = $doableServiceId->fields['PK_SERVICE_MASTER'];
                            $PK_SERVICE_CODE = $doableServiceId->fields['PK_SERVICE_CODE'];
                            $PACKAGE_SERVICE_DATA['PK_PACKAGE'] = $PK_PACKAGE;
                            $PACKAGE_SERVICE_DATA['PK_SERVICE_MASTER'] = $PK_SERVICE_MASTER;
                            $PACKAGE_SERVICE_DATA['PK_SERVICE_CODE'] = $PK_SERVICE_CODE;
                            $PACKAGE_SERVICE_DATA['SERVICE_DETAILS'] = $doableServiceId->fields['DESCRIPTION'];
                            $PACKAGE_SERVICE_DATA['NUMBER_OF_SESSION'] = $quantity;
                            $PACKAGE_SERVICE_DATA['PRICE_PER_SESSION'] = ($quantity > 0) ? $getData[54] / $quantity : 0;
                            $PACKAGE_SERVICE_DATA['TOTAL'] = '';
                            $PACKAGE_SERVICE_DATA['DISCOUNT'] = '';
                            $PACKAGE_SERVICE_DATA['DISCOUNT_TYPE'] = '';
                            $PACKAGE_SERVICE_DATA['FINAL_AMOUNT'] = '';
                            $PACKAGE_SERVICE_DATA['ACTIVE'] = 1;
                            db_perform_account('DOA_PACKAGE_SERVICE', $PACKAGE_SERVICE_DATA, 'insert');
                        }

                        if ($getData[19] > 0) {
                            $service_code = 'NCLASS';
                            $quantity = $getData[19];
                            $doableServiceId = $db_account->Execute("SELECT PK_SERVICE_MASTER, PK_SERVICE_CODE, DESCRIPTION FROM DOA_SERVICE_CODE WHERE SERVICE_CODE ='$service_code' AND PK_LOCATION = '$PK_LOCATION'");
                            $PK_SERVICE_MASTER = $doableServiceId->fields['PK_SERVICE_MASTER'];
                            $PK_SERVICE_CODE = $doableServiceId->fields['PK_SERVICE_CODE'];
                            $PACKAGE_SERVICE_DATA['PK_PACKAGE'] = $PK_PACKAGE;
                            $PACKAGE_SERVICE_DATA['PK_SERVICE_MASTER'] = $PK_SERVICE_MASTER;
                            $PACKAGE_SERVICE_DATA['PK_SERVICE_CODE'] = $PK_SERVICE_CODE;
                            $PACKAGE_SERVICE_DATA['SERVICE_DETAILS'] = $doableServiceId->fields['DESCRIPTION'];
                            $PACKAGE_SERVICE_DATA['NUMBER_OF_SESSION'] = $quantity;
                            $PACKAGE_SERVICE_DATA['PRICE_PER_SESSION'] = ($quantity > 0) ? $getData[54] / $quantity : 0;
                            $PACKAGE_SERVICE_DATA['TOTAL'] = '';
                            $PACKAGE_SERVICE_DATA['DISCOUNT'] = '';
                            $PACKAGE_SERVICE_DATA['DISCOUNT_TYPE'] = '';
                            $PACKAGE_SERVICE_DATA['FINAL_AMOUNT'] = '';
                            $PACKAGE_SERVICE_DATA['ACTIVE'] = 1;
                            db_perform_account('DOA_PACKAGE_SERVICE', $PACKAGE_SERVICE_DATA, 'insert');
                        }
                    }
                    ############# Enrollment Master Entry #############
                    $ENROLLMENT_DATA['PK_PACKAGE'] = $PK_PACKAGE;
                    $enrollmentById = $db->Execute("SELECT PK_USER FROM DOA_USERS WHERE USER_ID = '" . $getData[21] . "'");
                    $ENROLLMENT_DATA['ENROLLMENT_BY_ID'] = ($enrollmentById->RecordCount() > 0) ? $enrollmentById->fields['PK_USER'] : 0;
                    $ENROLLMENT_BY_PERCENTAGE = 100;
                    $ENROLLMENT_DATA['ENROLLMENT_BY_PERCENTAGE'] = number_format($ENROLLMENT_BY_PERCENTAGE, 2);
                    $ENROLLMENT_DATA['ACTIVE'] = 1;
                    $ENROLLMENT_DATA['IS_SALE'] = $getData[6];
                    $ENROLLMENT_DATA['STATUS'] = "A";
                    $ENROLLMENT_DATA['ENROLLMENT_DATE'] = date('Y-m-d', strtotime($getData[3]));
                    $ENROLLMENT_DATA['CHARGE_TYPE'] = 0;
                    $ENROLLMENT_DATA['EXPIRY_DATE'] = date('Y-m-d', strtotime($getData[31]));
                    $ENROLLMENT_DATA['CREATED_BY'] = $_POST['PK_ACCOUNT_MASTER'];
                    $ENROLLMENT_DATA['CREATED_ON'] = date("Y-m-d H:i");
                    db_perform_account('DOA_ENROLLMENT_MASTER', $ENROLLMENT_DATA, 'insert');
                    $PK_ENROLLMENT_MASTER = $db_account->insert_ID();

                    $PK_ACCOUNT_MASTER = $_POST['PK_ACCOUNT_MASTER'];
                    if ($getData[22] != NULL && $getData[22] != '' && $getData[23] > 0) {
                        $ENROLLMENT_SERVICE_PROVIDER_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
                        $user_id = $getData[22];
                        $doableCustomerId = $db->Execute("SELECT DOA_USERS.PK_USER FROM DOA_USERS INNER JOIN DOA_USER_LOCATION ON DOA_USER_LOCATION.PK_USER = DOA_USERS.PK_USER WHERE DOA_USERS.USER_ID = '" . $user_id . "' AND DOA_USER_LOCATION.PK_LOCATION = '$PK_LOCATION' AND DOA_USERS.PK_ACCOUNT_MASTER = '$PK_ACCOUNT_MASTER'");
                        $ENROLLMENT_SERVICE_PROVIDER_DATA['SERVICE_PROVIDER_ID'] = ($doableCustomerId->RecordCount() > 0) ? $doableCustomerId->fields['PK_USER'] : 0;
                        $ENROLLMENT_SERVICE_PROVIDER_DATA['SERVICE_PROVIDER_PERCENTAGE'] = $getData[23];
                        $ENROLLMENT_SERVICE_PROVIDER_DATA['PERCENTAGE_AMOUNT'] = ($getData[4] * $getData[23]) / 100;
                        db_perform_account('DOA_ENROLLMENT_SERVICE_PROVIDER', $ENROLLMENT_SERVICE_PROVIDER_DATA, 'insert');
                    }
                    if ($getData[24] != NULL && $getData[24] != '' && $getData[25] > 0) {
                        $ENROLLMENT_SERVICE_PROVIDER_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
                        $user_id = $getData[24];
                        $doableCustomerId = $db->Execute("SELECT DOA_USERS.PK_USER FROM DOA_USERS INNER JOIN DOA_USER_LOCATION ON DOA_USER_LOCATION.PK_USER = DOA_USERS.PK_USER WHERE DOA_USERS.USER_ID = '" . $user_id . "' AND DOA_USER_LOCATION.PK_LOCATION = '$PK_LOCATION' AND DOA_USERS.PK_ACCOUNT_MASTER = '$PK_ACCOUNT_MASTER'");
                        $ENROLLMENT_SERVICE_PROVIDER_DATA['SERVICE_PROVIDER_ID'] = ($doableCustomerId->RecordCount() > 0) ? $doableCustomerId->fields['PK_USER'] : 0;
                        $ENROLLMENT_SERVICE_PROVIDER_DATA['SERVICE_PROVIDER_PERCENTAGE'] = $getData[25];
                        $ENROLLMENT_SERVICE_PROVIDER_DATA['PERCENTAGE_AMOUNT'] = ($getData[4] * $getData[25]) / 100;
                        db_perform_account('DOA_ENROLLMENT_SERVICE_PROVIDER', $ENROLLMENT_SERVICE_PROVIDER_DATA, 'insert');
                    }
                    if ($getData[26] != NULL && $getData[26] != '' && $getData[27] > 0) {
                        $ENROLLMENT_SERVICE_PROVIDER_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
                        $user_id = $getData[26];
                        $doableCustomerId = $db->Execute("SELECT DOA_USERS.PK_USER FROM DOA_USERS INNER JOIN DOA_USER_LOCATION ON DOA_USER_LOCATION.PK_USER = DOA_USERS.PK_USER WHERE DOA_USERS.USER_ID = '" . $user_id . "' AND DOA_USER_LOCATION.PK_LOCATION = '$PK_LOCATION' AND DOA_USERS.PK_ACCOUNT_MASTER = '$PK_ACCOUNT_MASTER'");
                        $ENROLLMENT_SERVICE_PROVIDER_DATA['SERVICE_PROVIDER_ID'] = ($doableCustomerId->RecordCount() > 0) ? $doableCustomerId->fields['PK_USER'] : 0;
                        $ENROLLMENT_SERVICE_PROVIDER_DATA['SERVICE_PROVIDER_PERCENTAGE'] = $getData[27];
                        $ENROLLMENT_SERVICE_PROVIDER_DATA['PERCENTAGE_AMOUNT'] = ($getData[4] * $getData[27]) / 100;
                        db_perform_account('DOA_ENROLLMENT_SERVICE_PROVIDER', $ENROLLMENT_SERVICE_PROVIDER_DATA, 'insert');
                    }

                    $ACTUAL_AMOUNT = $getData[4];
                    $DISCOUNT = $getData[40];
                    $TOTAL_AMOUNT = $getData[54];
                    $DOWN_PAYMENT = 0;
                    $BALANCE_PAYABLE = 0;
                    $PAYMENT_METHOD = 'One Time';
                    $PAYMENT_TERM = '';
                    $NUMBER_OF_PAYMENT = 0;
                    $FIRST_DUE_DATE = date('Y-m-d');
                    $INSTALLMENT_AMOUNT = 0;
                    if (strpos($getData[32], "C")  !== false) {
                        $info = str_replace('  ', ' ', $getData[32]);
                        $paymentInfo = explode(' ', $info);
                        $NUMBER_OF_PAYMENT = (is_array($paymentInfo) && isset($paymentInfo[2]) && is_int($paymentInfo[2])) ? $paymentInfo[2] : 0;
                        $INSTALLMENT_AMOUNT = (float)$paymentInfo[1];
                        $DOWN_PAYMENT = $TOTAL_AMOUNT - ($NUMBER_OF_PAYMENT * $INSTALLMENT_AMOUNT);
                        $BALANCE_PAYABLE = ($NUMBER_OF_PAYMENT * $INSTALLMENT_AMOUNT);
                        $PAYMENT_METHOD = 'Flexible Payments';
                        $PAYMENT_TERM = 'Monthly';
                        $FIRST_DUE_DATE = date('Y-m-d', strtotime($getData[57]));
                    }

                    $BILLING_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
                    $BILLING_DATA['BILLING_REF'] = '';
                    $BILLING_DATA['BILLING_DATE'] = date('Y-m-d', strtotime($getData[3]));
                    $BILLING_DATA['ACTUAL_AMOUNT'] = number_format($ACTUAL_AMOUNT, 2);
                    $BILLING_DATA['DISCOUNT'] = $DISCOUNT;
                    $BILLING_DATA['DOWN_PAYMENT'] = number_format($DOWN_PAYMENT, 2);
                    $BILLING_DATA['BALANCE_PAYABLE'] = number_format($BALANCE_PAYABLE, 2);
                    $BILLING_DATA['TOTAL_AMOUNT'] = number_format($TOTAL_AMOUNT, 2);
                    $BILLING_DATA['PAYMENT_METHOD'] = $PAYMENT_METHOD;
                    $BILLING_DATA['PAYMENT_TERM'] = $PAYMENT_TERM;
                    $BILLING_DATA['NUMBER_OF_PAYMENT'] = $NUMBER_OF_PAYMENT;
                    $BILLING_DATA['FIRST_DUE_DATE'] = $FIRST_DUE_DATE;
                    $BILLING_DATA['INSTALLMENT_AMOUNT'] = number_format($INSTALLMENT_AMOUNT, 2);
                    db_perform_account('DOA_ENROLLMENT_BILLING', $BILLING_DATA, 'insert');
                    $PK_ENROLLMENT_BILLING = $db_account->insert_ID();

                    if ($getData[9] > 0) {
                        $service_code = 'PRI1';
                        $quantity = $getData[9];
                        $doableServiceId = $db_account->Execute("SELECT PK_SERVICE_MASTER, PK_SERVICE_CODE, DESCRIPTION FROM DOA_SERVICE_CODE WHERE SERVICE_CODE ='$service_code' AND PK_LOCATION = '$PK_LOCATION'");
                        $PK_SERVICE_MASTER = $doableServiceId->fields['PK_SERVICE_MASTER'];
                        $PK_SERVICE_CODE = $doableServiceId->fields['PK_SERVICE_CODE'];
                        $SERVICE_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
                        $SERVICE_DATA['PK_SERVICE_MASTER'] = $PK_SERVICE_MASTER;
                        $SERVICE_DATA['PK_SERVICE_CODE'] = $PK_SERVICE_CODE;
                        $SERVICE_DATA['SERVICE_DETAILS'] = $doableServiceId->fields['DESCRIPTION'];
                        $SERVICE_DATA['NUMBER_OF_SESSION'] = $ENROLLMENT_SERVICE_DATA['ORIGINAL_SESSION_COUNT'] = $quantity;
                        $SERVICE_DATA['PRICE_PER_SESSION'] = ($quantity > 0) ? $TOTAL_AMOUNT / $quantity : 0;
                        $SERVICE_DATA['TOTAL'] = number_format($ACTUAL_AMOUNT, 2);
                        $SERVICE_DATA['DISCOUNT'] = $DISCOUNT;
                        $SERVICE_DATA['DISCOUNT_TYPE'] = ($DISCOUNT > 0) ? 1 : 0;
                        $SERVICE_DATA['FINAL_AMOUNT'] = $ENROLLMENT_SERVICE_DATA['ORIGINAL_AMOUNT'] = number_format($TOTAL_AMOUNT, 2);
                        db_perform_account('DOA_ENROLLMENT_SERVICE', $SERVICE_DATA, 'insert');
                    }

                    if ($getData[10] > 0) {
                        $service_code = 'PRI2';
                        $quantity = $getData[10];
                        $doableServiceId = $db_account->Execute("SELECT PK_SERVICE_MASTER, PK_SERVICE_CODE, DESCRIPTION FROM DOA_SERVICE_CODE WHERE SERVICE_CODE ='$service_code' AND PK_LOCATION = '$PK_LOCATION'");
                        $PK_SERVICE_MASTER = $doableServiceId->fields['PK_SERVICE_MASTER'];
                        $PK_SERVICE_CODE = $doableServiceId->fields['PK_SERVICE_CODE'];
                        $SERVICE_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
                        $SERVICE_DATA['PK_SERVICE_MASTER'] = $PK_SERVICE_MASTER;
                        $SERVICE_DATA['PK_SERVICE_CODE'] = $PK_SERVICE_CODE;
                        $SERVICE_DATA['SERVICE_DETAILS'] = $doableServiceId->fields['DESCRIPTION'];
                        $SERVICE_DATA['NUMBER_OF_SESSION'] = $ENROLLMENT_SERVICE_DATA['ORIGINAL_SESSION_COUNT'] = $quantity;
                        $SERVICE_DATA['PRICE_PER_SESSION'] = ($quantity > 0) ? $TOTAL_AMOUNT / $quantity : 0;
                        $SERVICE_DATA['TOTAL'] = number_format($ACTUAL_AMOUNT, 2);
                        $SERVICE_DATA['DISCOUNT'] = $DISCOUNT;
                        $SERVICE_DATA['DISCOUNT_TYPE'] = ($DISCOUNT > 0) ? 1 : 0;
                        $SERVICE_DATA['FINAL_AMOUNT'] = $ENROLLMENT_SERVICE_DATA['ORIGINAL_AMOUNT'] = number_format($TOTAL_AMOUNT, 2);
                        db_perform_account('DOA_ENROLLMENT_SERVICE', $SERVICE_DATA, 'insert');
                    }

                    if ($getData[11] > 0) {
                        $service_code = 'PRI3';
                        $quantity = $getData[11];
                        $doableServiceId = $db_account->Execute("SELECT PK_SERVICE_MASTER, PK_SERVICE_CODE, DESCRIPTION FROM DOA_SERVICE_CODE WHERE SERVICE_CODE ='$service_code' AND PK_LOCATION = '$PK_LOCATION'");
                        $PK_SERVICE_MASTER = $doableServiceId->fields['PK_SERVICE_MASTER'];
                        $PK_SERVICE_CODE = $doableServiceId->fields['PK_SERVICE_CODE'];
                        $SERVICE_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
                        $SERVICE_DATA['PK_SERVICE_MASTER'] = $PK_SERVICE_MASTER;
                        $SERVICE_DATA['PK_SERVICE_CODE'] = $PK_SERVICE_CODE;
                        $SERVICE_DATA['SERVICE_DETAILS'] = $doableServiceId->fields['DESCRIPTION'];
                        $SERVICE_DATA['NUMBER_OF_SESSION'] = $ENROLLMENT_SERVICE_DATA['ORIGINAL_SESSION_COUNT'] = $quantity;
                        $SERVICE_DATA['PRICE_PER_SESSION'] = ($quantity > 0) ? $TOTAL_AMOUNT / $quantity : 0;
                        $SERVICE_DATA['TOTAL'] = number_format($ACTUAL_AMOUNT, 2);
                        $SERVICE_DATA['DISCOUNT'] = $DISCOUNT;
                        $SERVICE_DATA['DISCOUNT_TYPE'] = ($DISCOUNT > 0) ? 1 : 0;
                        $SERVICE_DATA['FINAL_AMOUNT'] = $ENROLLMENT_SERVICE_DATA['ORIGINAL_AMOUNT'] = number_format($TOTAL_AMOUNT, 2);
                        db_perform_account('DOA_ENROLLMENT_SERVICE', $SERVICE_DATA, 'insert');
                    }

                    if ($getData[12] > 0) {
                        $service_code = 'PRI4';
                        $quantity = $getData[12];
                        $doableServiceId = $db_account->Execute("SELECT PK_SERVICE_MASTER, PK_SERVICE_CODE, DESCRIPTION FROM DOA_SERVICE_CODE WHERE SERVICE_CODE ='$service_code' AND PK_LOCATION = '$PK_LOCATION'");
                        $PK_SERVICE_MASTER = $doableServiceId->fields['PK_SERVICE_MASTER'];
                        $PK_SERVICE_CODE = $doableServiceId->fields['PK_SERVICE_CODE'];
                        $SERVICE_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
                        $SERVICE_DATA['PK_SERVICE_MASTER'] = $PK_SERVICE_MASTER;
                        $SERVICE_DATA['PK_SERVICE_CODE'] = $PK_SERVICE_CODE;
                        $SERVICE_DATA['SERVICE_DETAILS'] = $doableServiceId->fields['DESCRIPTION'];
                        $SERVICE_DATA['NUMBER_OF_SESSION'] = $ENROLLMENT_SERVICE_DATA['ORIGINAL_SESSION_COUNT'] = $quantity;
                        $SERVICE_DATA['PRICE_PER_SESSION'] = ($quantity > 0) ? $TOTAL_AMOUNT / $quantity : 0;
                        $SERVICE_DATA['TOTAL'] = number_format($ACTUAL_AMOUNT, 2);
                        $SERVICE_DATA['DISCOUNT'] = $DISCOUNT;
                        $SERVICE_DATA['DISCOUNT_TYPE'] = ($DISCOUNT > 0) ? 1 : 0;
                        $SERVICE_DATA['FINAL_AMOUNT'] = $ENROLLMENT_SERVICE_DATA['ORIGINAL_AMOUNT'] = number_format($TOTAL_AMOUNT, 2);
                        db_perform_account('DOA_ENROLLMENT_SERVICE', $SERVICE_DATA, 'insert');
                    }

                    if ($getData[14] > 0) {
                        $service_code = 'CMP';
                        $quantity = $getData[14];
                        $doableServiceId = $db_account->Execute("SELECT PK_SERVICE_MASTER, PK_SERVICE_CODE, DESCRIPTION FROM DOA_SERVICE_CODE WHERE SERVICE_CODE ='$service_code' AND PK_LOCATION = '$PK_LOCATION'");
                        $PK_SERVICE_MASTER = $doableServiceId->fields['PK_SERVICE_MASTER'];
                        $PK_SERVICE_CODE = $doableServiceId->fields['PK_SERVICE_CODE'];
                        $SERVICE_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
                        $SERVICE_DATA['PK_SERVICE_MASTER'] = $PK_SERVICE_MASTER;
                        $SERVICE_DATA['PK_SERVICE_CODE'] = $PK_SERVICE_CODE;
                        $SERVICE_DATA['SERVICE_DETAILS'] = $doableServiceId->fields['DESCRIPTION'];
                        $SERVICE_DATA['NUMBER_OF_SESSION'] = $ENROLLMENT_SERVICE_DATA['ORIGINAL_SESSION_COUNT'] = $quantity;
                        $SERVICE_DATA['PRICE_PER_SESSION'] = ($quantity > 0) ? $TOTAL_AMOUNT / $quantity : 0;
                        $SERVICE_DATA['TOTAL'] = number_format($ACTUAL_AMOUNT, 2);
                        $SERVICE_DATA['DISCOUNT'] = $DISCOUNT;
                        $SERVICE_DATA['DISCOUNT_TYPE'] = ($DISCOUNT > 0) ? 1 : 0;
                        $SERVICE_DATA['FINAL_AMOUNT'] = $ENROLLMENT_SERVICE_DATA['ORIGINAL_AMOUNT'] = number_format($TOTAL_AMOUNT, 2);
                        db_perform_account('DOA_ENROLLMENT_SERVICE', $SERVICE_DATA, 'insert');
                    }

                    if ($getData[15] > 0) {
                        $service_code = 'GRP1';
                        $quantity = $getData[15];
                        $doableServiceId = $db_account->Execute("SELECT PK_SERVICE_MASTER, PK_SERVICE_CODE, DESCRIPTION FROM DOA_SERVICE_CODE WHERE SERVICE_CODE ='$service_code' AND PK_LOCATION = '$PK_LOCATION'");
                        $PK_SERVICE_MASTER = $doableServiceId->fields['PK_SERVICE_MASTER'];
                        $PK_SERVICE_CODE = $doableServiceId->fields['PK_SERVICE_CODE'];
                        $SERVICE_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
                        $SERVICE_DATA['PK_SERVICE_MASTER'] = $PK_SERVICE_MASTER;
                        $SERVICE_DATA['PK_SERVICE_CODE'] = $PK_SERVICE_CODE;
                        $SERVICE_DATA['SERVICE_DETAILS'] = $doableServiceId->fields['DESCRIPTION'];
                        $SERVICE_DATA['NUMBER_OF_SESSION'] = $ENROLLMENT_SERVICE_DATA['ORIGINAL_SESSION_COUNT'] = $quantity;
                        $SERVICE_DATA['PRICE_PER_SESSION'] = ($quantity > 0) ? $TOTAL_AMOUNT / $quantity : 0;
                        $SERVICE_DATA['TOTAL'] = number_format($ACTUAL_AMOUNT, 2);
                        $SERVICE_DATA['DISCOUNT'] = $DISCOUNT;
                        $SERVICE_DATA['DISCOUNT_TYPE'] = ($DISCOUNT > 0) ? 1 : 0;
                        $SERVICE_DATA['FINAL_AMOUNT'] = $ENROLLMENT_SERVICE_DATA['ORIGINAL_AMOUNT'] = number_format($TOTAL_AMOUNT, 2);
                        db_perform_account('DOA_ENROLLMENT_SERVICE', $SERVICE_DATA, 'insert');
                    }

                    if ($getData[16] > 0) {
                        $service_code = 'GRP2';
                        $quantity = $getData[16];
                        $doableServiceId = $db_account->Execute("SELECT PK_SERVICE_MASTER, PK_SERVICE_CODE, DESCRIPTION FROM DOA_SERVICE_CODE WHERE SERVICE_CODE ='$service_code' AND PK_LOCATION = '$PK_LOCATION'");
                        $PK_SERVICE_MASTER = $doableServiceId->fields['PK_SERVICE_MASTER'];
                        $PK_SERVICE_CODE = $doableServiceId->fields['PK_SERVICE_CODE'];
                        $SERVICE_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
                        $SERVICE_DATA['PK_SERVICE_MASTER'] = $PK_SERVICE_MASTER;
                        $SERVICE_DATA['PK_SERVICE_CODE'] = $PK_SERVICE_CODE;
                        $SERVICE_DATA['SERVICE_DETAILS'] = $doableServiceId->fields['DESCRIPTION'];
                        $SERVICE_DATA['NUMBER_OF_SESSION'] = $ENROLLMENT_SERVICE_DATA['ORIGINAL_SESSION_COUNT'] = $quantity;
                        $SERVICE_DATA['PRICE_PER_SESSION'] = ($quantity > 0) ? $TOTAL_AMOUNT / $quantity : 0;
                        $SERVICE_DATA['TOTAL'] = number_format($ACTUAL_AMOUNT, 2);
                        $SERVICE_DATA['DISCOUNT'] = $DISCOUNT;
                        $SERVICE_DATA['DISCOUNT_TYPE'] = ($DISCOUNT > 0) ? 1 : 0;
                        $SERVICE_DATA['FINAL_AMOUNT'] = $ENROLLMENT_SERVICE_DATA['ORIGINAL_AMOUNT'] = number_format($TOTAL_AMOUNT, 2);
                        db_perform_account('DOA_ENROLLMENT_SERVICE', $SERVICE_DATA, 'insert');
                    }

                    if ($getData[17] > 0) {
                        $service_code = 'PRT';
                        $quantity = $getData[17];
                        $doableServiceId = $db_account->Execute("SELECT PK_SERVICE_MASTER, PK_SERVICE_CODE, DESCRIPTION FROM DOA_SERVICE_CODE WHERE SERVICE_CODE ='$service_code' AND PK_LOCATION = '$PK_LOCATION'");
                        $PK_SERVICE_MASTER = $doableServiceId->fields['PK_SERVICE_MASTER'];
                        $PK_SERVICE_CODE = $doableServiceId->fields['PK_SERVICE_CODE'];
                        $SERVICE_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
                        $SERVICE_DATA['PK_SERVICE_MASTER'] = $PK_SERVICE_MASTER;
                        $SERVICE_DATA['PK_SERVICE_CODE'] = $PK_SERVICE_CODE;
                        $SERVICE_DATA['SERVICE_DETAILS'] = $doableServiceId->fields['DESCRIPTION'];
                        $SERVICE_DATA['NUMBER_OF_SESSION'] = $ENROLLMENT_SERVICE_DATA['ORIGINAL_SESSION_COUNT'] = $quantity;
                        $SERVICE_DATA['PRICE_PER_SESSION'] = ($quantity > 0) ? $TOTAL_AMOUNT / $quantity : 0;
                        $SERVICE_DATA['TOTAL'] = number_format($ACTUAL_AMOUNT, 2);
                        $SERVICE_DATA['DISCOUNT'] = $DISCOUNT;
                        $SERVICE_DATA['DISCOUNT_TYPE'] = ($DISCOUNT > 0) ? 1 : 0;
                        $SERVICE_DATA['FINAL_AMOUNT'] = $ENROLLMENT_SERVICE_DATA['ORIGINAL_AMOUNT'] = number_format($TOTAL_AMOUNT, 2);
                        db_perform_account('DOA_ENROLLMENT_SERVICE', $SERVICE_DATA, 'insert');
                    }

                    if ($getData[18] > 0) {
                        $service_code = 'NPRI';
                        $quantity = $getData[18];
                        $doableServiceId = $db_account->Execute("SELECT PK_SERVICE_MASTER, PK_SERVICE_CODE, DESCRIPTION FROM DOA_SERVICE_CODE WHERE SERVICE_CODE ='$service_code' AND PK_LOCATION = '$PK_LOCATION'");
                        $PK_SERVICE_MASTER = $doableServiceId->fields['PK_SERVICE_MASTER'];
                        $PK_SERVICE_CODE = $doableServiceId->fields['PK_SERVICE_CODE'];
                        $SERVICE_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
                        $SERVICE_DATA['PK_SERVICE_MASTER'] = $PK_SERVICE_MASTER;
                        $SERVICE_DATA['PK_SERVICE_CODE'] = $PK_SERVICE_CODE;
                        $SERVICE_DATA['SERVICE_DETAILS'] = $doableServiceId->fields['DESCRIPTION'];
                        $SERVICE_DATA['NUMBER_OF_SESSION'] = $ENROLLMENT_SERVICE_DATA['ORIGINAL_SESSION_COUNT'] = $quantity;
                        $SERVICE_DATA['PRICE_PER_SESSION'] = ($quantity > 0) ? $TOTAL_AMOUNT / $quantity : 0;
                        $SERVICE_DATA['TOTAL'] = number_format($ACTUAL_AMOUNT, 2);
                        $SERVICE_DATA['DISCOUNT'] = $DISCOUNT;
                        $SERVICE_DATA['DISCOUNT_TYPE'] = ($DISCOUNT > 0) ? 1 : 0;
                        $SERVICE_DATA['FINAL_AMOUNT'] = $ENROLLMENT_SERVICE_DATA['ORIGINAL_AMOUNT'] = number_format($TOTAL_AMOUNT, 2);
                        db_perform_account('DOA_ENROLLMENT_SERVICE', $SERVICE_DATA, 'insert');
                    }

                    if ($getData[19] > 0) {
                        $service_code = 'NCLASS';
                        $quantity = $getData[19];
                        $doableServiceId = $db_account->Execute("SELECT PK_SERVICE_MASTER, PK_SERVICE_CODE, DESCRIPTION FROM DOA_SERVICE_CODE WHERE SERVICE_CODE ='$service_code' AND PK_LOCATION = '$PK_LOCATION'");
                        $PK_SERVICE_MASTER = $doableServiceId->fields['PK_SERVICE_MASTER'];
                        $PK_SERVICE_CODE = $doableServiceId->fields['PK_SERVICE_CODE'];
                        $SERVICE_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
                        $SERVICE_DATA['PK_SERVICE_MASTER'] = $PK_SERVICE_MASTER;
                        $SERVICE_DATA['PK_SERVICE_CODE'] = $PK_SERVICE_CODE;
                        $SERVICE_DATA['SERVICE_DETAILS'] = $doableServiceId->fields['DESCRIPTION'];
                        $SERVICE_DATA['NUMBER_OF_SESSION'] = $ENROLLMENT_SERVICE_DATA['ORIGINAL_SESSION_COUNT'] = $quantity;
                        $SERVICE_DATA['PRICE_PER_SESSION'] = ($quantity > 0) ? $TOTAL_AMOUNT / $quantity : 0;
                        $SERVICE_DATA['TOTAL'] = number_format($ACTUAL_AMOUNT, 2);
                        $SERVICE_DATA['DISCOUNT'] = $DISCOUNT;
                        $SERVICE_DATA['DISCOUNT_TYPE'] = ($DISCOUNT > 0) ? 1 : 0;
                        $SERVICE_DATA['FINAL_AMOUNT'] = $ENROLLMENT_SERVICE_DATA['ORIGINAL_AMOUNT'] = number_format($TOTAL_AMOUNT, 2);
                        db_perform_account('DOA_ENROLLMENT_SERVICE', $SERVICE_DATA, 'insert');
                    }


                    $BALANCE = 0;
                    $BILLED_AMOUNT = $getData[4];
                    $IS_DOWN_PAYMENT = (strpos($getData[33], 'down payment')  !== false) ? 1 : 0;
                    $BALANCE += $BILLED_AMOUNT;
                    if ($BILLED_AMOUNT == 0 && $IS_DOWN_PAYMENT == 1) {
                        $BILLED_AMOUNT = $getData[63];
                    }
                    $BILLING_LEDGER_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
                    $BILLING_LEDGER_DATA['PK_ENROLLMENT_BILLING '] = $PK_ENROLLMENT_BILLING;
                    $BILLING_LEDGER_DATA['TRANSACTION_TYPE'] = 'Billing';
                    $BILLING_LEDGER_DATA['ENROLLMENT_LEDGER_PARENT'] = 0;
                    $BILLING_LEDGER_DATA['DUE_DATE'] = date('Y-m-d', strtotime($getData[3]));
                    $BILLING_LEDGER_DATA['BILLED_AMOUNT'] = number_format($BILLED_AMOUNT, 2);
                    $BILLING_LEDGER_DATA['PAID_AMOUNT'] = 0;
                    $BILLING_LEDGER_DATA['BALANCE'] = number_format($BALANCE, 2);
                    $BILLING_LEDGER_DATA['IS_PAID'] = (!empty($getData[63])) ? 1 : 0;
                    $BILLING_LEDGER_DATA['STATUS'] = 'A';
                    $BILLING_LEDGER_DATA['IS_DOWN_PAYMENT'] = $IS_DOWN_PAYMENT;
                    db_perform_account('DOA_ENROLLMENT_LEDGER', $BILLING_LEDGER_DATA, 'insert');
                    $PK_ENROLLMENT_LEDGER = $db_account->insert_ID();

                    $TOTAL_PAID_AMOUNT = 0;
                    $orgDate = $getData[3];
                    $newDate = date("Y-m-d", strtotime($orgDate));

                    if ($enrollment_payment->fields['payment_method'] == 'Save Card' || $enrollment_payment->fields['payment_method'] == 'Charge') {
                        $payment_type = $enrollment_payment->fields['card_type'];
                    } else {
                        $payment_type = $enrollment_payment->fields['payment_method'];
                    }
                    $payment_type = $getData[81];
                    $PK_PAYMENT_TYPE = $db->Execute("SELECT PK_PAYMENT_TYPE FROM DOA_PAYMENT_TYPE WHERE PAYMENT_TYPE = '$payment_type'");

                    $ENROLLMENT_PAYMENT_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
                    $ENROLLMENT_PAYMENT_DATA['PK_ENROLLMENT_BILLING'] = $PK_ENROLLMENT_BILLING;
                    $ENROLLMENT_PAYMENT_DATA['PK_PAYMENT_TYPE'] = ($PK_PAYMENT_TYPE->RecordCount() > 0) ? $PK_PAYMENT_TYPE->fields['PK_PAYMENT_TYPE'] : 0;
                    $ENROLLMENT_PAYMENT_DATA['PK_ENROLLMENT_LEDGER'] = $PK_ENROLLMENT_LEDGER;
                    $ENROLLMENT_PAYMENT_DATA['TYPE'] = $enrollment_payment->fields['record_type'];
                    $ENROLLMENT_PAYMENT_DATA['PK_LOCATION'] = $PK_LOCATION;
                    $ENROLLMENT_PAYMENT_DATA['AMOUNT'] = number_format(abs($getData[63]), 2);
                    $ENROLLMENT_PAYMENT_DATA['NOTE'] = $getData[33];
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
                    $ENROLLMENT_PAYMENT_DATA['RECEIPT_NUMBER'] = $getData[80];

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

                    break;

                case "DOA_ENROLLMENT_SERVICE":
                    $enrollmentId = $getData[0];
                    $doableEnrollmentId = $db_account->Execute("SELECT PK_ENROLLMENT_MASTER, ENROLLMENT_NAME FROM DOA_ENROLLMENT_MASTER WHERE ENROLLMENT_ID = '$enrollmentId'");
                    $PK_ENROLLMENT_MASTER = $doableEnrollmentId->fields['PK_ENROLLMENT_MASTER'];
                    $ENROLLMENT_NAME = $doableEnrollmentId->fields['ENROLLMENT_NAME'];

                    $doableServiceId = $db_account->Execute("SELECT PK_SERVICE_MASTER, PK_SERVICE_CODE, DESCRIPTION FROM DOA_SERVICE_CODE WHERE SERVICE_CODE ='$getData[1]'");
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
                        $SERVICE_DATA['PK_SERVICE_CODE'] =  $PK_SERVICE_CODE;
                        $SERVICE_DATA['PK_SCHEDULING_CODE'] =  $PK_SCHEDULING_CODE;
                        $SERVICE_DATA['FREQUENCY'] =  0;
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
                    $table_data = $db_account->Execute("SELECT * FROM DOA_EVENT WHERE HEADER = '$getData[4]' AND START_DATE = '$getData[5]' AND START_TIME = '$getData[6]'");
                    if ($table_data->RecordCount() == 0) {
                        $INSERT_DATA['HEADER'] = $getData[4];
                        if ($getData[9] == "G") {
                            $pk_event_type = $db_account->Execute("SELECT PK_EVENT_TYPE FROM DOA_EVENT_TYPE WHERE EVENT_TYPE='General' AND PK_ACCOUNT_MASTER='$_POST[PK_ACCOUNT_MASTER]'");
                            $INSERT_DATA['PK_EVENT_TYPE'] = $pk_event_type->fields['PK_EVENT_TYPE'];
                        } else {
                            $INSERT_DATA['PK_EVENT_TYPE'] = 0;
                        }
                        $INSERT_DATA['PK_ACCOUNT_MASTER'] = $_POST['PK_ACCOUNT_MASTER'];
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
                        $PK_EVENT = $db_account->insert_ID();
                        $EVENT_LOCATION_DATA['PK_EVENT'] = $PK_EVENT;
                        $EVENT_LOCATION_DATA['PK_LOCATION'] = $PK_LOCATION;
                        db_perform_account('DOA_EVENT_LOCATION', $EVENT_LOCATION_DATA, 'insert');
                    }
                    break;

                case "DOA_APPOINTMENT_MASTER":
                    $customer_name = preg_split("/\s*[,&]|\s+and\s+\s*/", $getData[3]);
                    $first_name = isset($customer_name[1]) ? $customer_name[1] : '';
                    $$last_name = isset($customer_name[0]) ? $customer_name[0] : '';
                    $student = $db->Execute("SELECT * FROM DOA_USERS WHERE FIRST_NAME='$first_name' AND LAST_NAME='$last_name' AND PK_ACCOUNT_MASTER='$_POST[PK_ACCOUNT_MASTER]'");
                    $getEmail = $student->fields['EMAIL_ID'];
                    $PK_USER_MASTER = 0;
                    if ($getEmail !== 0) {
                        $doableUserId = $db->Execute("SELECT DOA_USER_MASTER.PK_USER_MASTER FROM DOA_USER_MASTER INNER JOIN DOA_USERS ON DOA_USER_MASTER.PK_USER=DOA_USERS.PK_USER WHERE DOA_USERS.EMAIL_ID='$getEmail' AND DOA_USER_MASTER.PK_ACCOUNT_MASTER = '$_POST[PK_ACCOUNT_MASTER]'");
                        $PK_USER_MASTER = ($doableUserId->RecordCount() > 0) ? $doableUserId->fields['PK_USER_MASTER'] : NULL;
                        $INSERT_DATA['CUSTOMER_ID'] = $PK_USER_MASTER;
                    } else {
                        $INSERT_DATA['CUSTOMER_ID'] = NULL;
                    }

                    $user_id = $getData[0];
                    $service_provider = $db->Execute("SELECT DOA_USERS.EMAIL_ID FROM DOA_USERS INNER JOIN DOA_USER_LOCATION ON DOA_USER_LOCATION.PK_USER = DOA_USERS.PK_USER WHERE DOA_USERS.USER_ID = '" . $user_id . "' AND DOA_USER_LOCATION.PK_LOCATION = '$PK_LOCATION' AND DOA_USERS.PK_ACCOUNT_MASTER = '$PK_ACCOUNT_MASTER'");
                    $getServiceProvider = $service_provider->fields['EMAIL_ID'];
                    if ($getServiceProvider !== 0) {
                        $SERVICE_PROVIDER_ID = $db->Execute("SELECT PK_USER FROM DOA_USERS WHERE EMAIL_ID = '$getServiceProvider'");
                        $INSERT_DATA['SERVICE_PROVIDER_ID'] = ($SERVICE_PROVIDER_ID->RecordCount() > 0) ? $SERVICE_PROVIDER_ID->fields['PK_USER'] : '';
                    } else {
                        $INSERT_DATA['SERVICE_PROVIDER_ID'] = NULL;
                    }

                    $service = $getData[8];
                    $doableServiceId = $db_account->Execute("SELECT PK_SERVICE_MASTER, PK_SERVICE_CODE, DESCRIPTION FROM DOA_SERVICE_CODE WHERE SERVICE_CODE ='$service_code' AND PK_LOCATION = '$PK_LOCATION'");
                    if ($doableServiceId->RecordCount() > 0) {
                        $PK_SERVICE_MASTER = $doableServiceId->fields['PK_SERVICE_MASTER'];
                        $PK_SERVICE_CODE = $doableServiceId->fields['PK_SERVICE_CODE'];
                    } else {
                        $SERVICE = [
                            'PK_LOCATION'      => $PK_LOCATION,
                            'SERVICE_NAME'     => $service,
                            'PK_SERVICE_CLASS' => (stripos($service, 'Miscellaneous') !== false ? 5 : 2),
                            'IS_SCHEDULE'      => 1,
                            'IS_SUNDRY'        => 0,
                            'DESCRIPTION'      => $service,
                            'ACTIVE'           => 1,
                            'IS_DELETED'       => 0,
                            'CREATED_BY'       => $_SESSION['PK_USER'],
                            'CREATED_ON'       => date("Y-m-d H:i")
                        ];
                        db_perform_account('DOA_SERVICE_MASTER', $SERVICE, 'insert');
                        $PK_SERVICE_MASTER = $db_account->insert_ID();

                        $SERVICE_CODE = [
                            'PK_SERVICE_MASTER' => $PK_SERVICE_MASTER,
                            'PK_LOCATION'       => $PK_LOCATION,
                            'SERVICE_CODE'      => $service_code,
                            'DESCRIPTION'       => $service_name_clean,
                            'IS_GROUP'          => (stripos($service_code, 'GRP') !== false ? 1 : 0),
                            'CAPACITY'          => (stripos($service_code, 'GRP') !== false ? 20 : 0),
                            'IS_CHARGEABLE'     => '',
                            'SORT_ORDER'        => '',
                            'ACTIVE'            => 1
                        ];
                        db_perform_account('DOA_SERVICE_CODE', $SERVICE_CODE, 'insert');
                        $PK_SERVICE_CODE = $db_account->insert_ID();
                    }

                    $getServiceCodeId = $db_account->Execute("SELECT PK_SCHEDULING_CODE FROM DOA_SCHEDULING_CODE WHERE SCHEDULING_CODE = '$getData[2]'");
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
                            $account_data = $db->Execute("SELECT ENROLLMENT_ID_CHAR, ENROLLMENT_ID_NUM FROM `DOA_ACCOUNT_MASTER` WHERE `PK_ACCOUNT_MASTER` = '$_POST[PK_ACCOUNT_MASTER]'");
                            if ($account_data->RecordCount() > 0) {
                                $enrollment_char = $account_data->fields['ENROLLMENT_ID_CHAR'];
                            } else {
                                $enrollment_char = 'ENR';
                            }
                            $enrollment_data = $db_account->Execute("SELECT ENROLLMENT_ID FROM `DOA_ENROLLMENT_MASTER` WHERE `PK_ACCOUNT_MASTER` = '$_POST[PK_ACCOUNT_MASTER]' ORDER BY PK_ENROLLMENT_MASTER DESC LIMIT 1");
                            if ($enrollment_data->RecordCount() > 0) {
                                $last_enrollment_id = str_replace($enrollment_char, '', $enrollment_data->fields['ENROLLMENT_ID']);
                                $ENROLLMENT_DATA['ENROLLMENT_ID'] = $enrollment_char . (intval($last_enrollment_id) + 1);
                            } else {
                                $ENROLLMENT_DATA['ENROLLMENT_ID'] = $enrollment_char . $account_data->fields['ENROLLMENT_ID_NUM'];
                            }

                            $ENROLLMENT_DATA['PK_ACCOUNT_MASTER'] = $_POST['PK_ACCOUNT_MASTER'];
                            $customerId = $getData[4];
                            $doableCustomerId = $db->Execute("SELECT DOA_USER_MASTER.PK_USER_MASTER FROM DOA_USER_MASTER INNER JOIN DOA_USERS ON DOA_USER_MASTER.PK_USER=DOA_USERS.PK_USER WHERE DOA_USERS.PK_USER = " . $student->fields['PK_USER'] . " AND DOA_USER_MASTER.PK_ACCOUNT_MASTER = '$_POST[PK_ACCOUNT_MASTER]'");
                            $ENROLLMENT_DATA['PK_USER_MASTER'] = $PK_USER_MASTER;
                            $ENROLLMENT_DATA['AGREEMENT_PDF_LINK'] = '';
                            $ENROLLMENT_DATA['ENROLLMENT_BY_ID'] = $_POST['PK_ACCOUNT_MASTER'];
                            $ENROLLMENT_DATA['ACTIVE'] = 1;
                            $ENROLLMENT_DATA['STATUS'] = "A";
                            $ENROLLMENT_DATA['CREATED_BY'] = $_POST['PK_ACCOUNT_MASTER'];
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

                    $APPOINTMENT_MASTER_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
                    $APPOINTMENT_MASTER_DATA['PK_ENROLLMENT_SERVICE'] = $PK_ENROLLMENT_SERVICE;
                    $APPOINTMENT_MASTER_DATA['STANDING_ID'] = $getData[13];
                    $APPOINTMENT_MASTER_DATA['PK_SERVICE_MASTER'] = $PK_SERVICE_MASTER;
                    $APPOINTMENT_MASTER_DATA['PK_SERVICE_CODE'] = $PK_SERVICE_CODE;
                    $APPOINTMENT_MASTER_DATA['PK_SCHEDULING_CODE'] = $PK_SCHEDULING_CODE;
                    $APPOINTMENT_MASTER_DATA['PK_LOCATION'] = $PK_LOCATION;
                    $APPOINTMENT_MASTER_DATA['DATE'] = date('Y-m-d', strtotime($getData[4]));
                    $APPOINTMENT_MASTER_DATA['START_TIME'] = date('H:i:s', strtotime($getData[5]));
                    $endTime = strtotime($getData[5]) + $getData[7] * 60;
                    $convertedTime = date('H:i:s', $endTime);
                    $APPOINTMENT_MASTER_DATA['END_TIME'] = $convertedTime;
                    $APPOINTMENT_MASTER_DATA['IS_CHARGED'] = $getData[16] == 'Y' ? 1 : 0;

                    $appt_status = $getData[11];
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
                    $APPOINTMENT_MASTER_DATA['INTERNAL_COMMENT'] = $getData[20];
                    if (strpos($getData[8], 'GRP') !== false) {
                        $APPOINTMENT_MASTER_DATA['GROUP_NAME'] = $getData[9];
                    }

                    if ($PK_ENROLLMENT_MASTER == 0 || $PK_ENROLLMENT_SERVICE == 0) {
                        $APPOINTMENT_MASTER_DATA['APPOINTMENT_TYPE'] = 'AD-HOC';
                    } elseif ($service_id == 'PRT' || $service_id == 'GRP1' ||  $service_id == 'GRP2' || $service_id == 'GRP3') {
                        $APPOINTMENT_MASTER_DATA['APPOINTMENT_TYPE'] = 'GROUP';
                    } else {
                        $APPOINTMENT_MASTER_DATA['APPOINTMENT_TYPE'] = 'NORMAL';
                    }

                    $APPOINTMENT_MASTER_DATA['SERIAL_NUMBER'] = getAppointmentSerialNumber($PK_USER_MASTER);
                    $APPOINTMENT_MASTER_DATA['ACTIVE'] = 1;
                    $APPOINTMENT_MASTER_DATA['IS_PAID'] = 0;

                    $APPOINTMENT_MASTER_DATA['CREATED_BY'] = $PK_ACCOUNT_MASTER;
                    $APPOINTMENT_MASTER_DATA['CREATED_ON'] = date("Y-m-d H:i");
                    //pre_r($APPOINTMENT_MASTER_DATA);
                    db_perform_account('DOA_APPOINTMENT_MASTER', $APPOINTMENT_MASTER_DATA, 'insert');
                    $PK_APPOINTMENT_MASTER = $db_account->insert_ID();

                    $INSERT_DATA_CUSTOMER['PK_APPOINTMENT_MASTER'] = $PK_APPOINTMENT_MASTER;
                    $INSERT_DATA_CUSTOMER['PK_USER_MASTER'] = $PK_USER_MASTER;
                    db_perform_account('DOA_APPOINTMENT_CUSTOMER', $INSERT_DATA_CUSTOMER, 'insert');


                    $user_id = $getData[0];
                    $doableServiceProviderId = $db->Execute("SELECT DOA_USERS.PK_USER FROM DOA_USERS INNER JOIN DOA_USER_LOCATION ON DOA_USER_LOCATION.PK_USER = DOA_USERS.PK_USER WHERE DOA_USERS.USER_ID = '" . $user_id . "' AND DOA_USER_LOCATION.PK_LOCATION = '$PK_LOCATION' AND DOA_USERS.PK_ACCOUNT_MASTER = '$PK_ACCOUNT_MASTER'");
                    $SERVICE_PROVIDER_ID = ($doableServiceProviderId->RecordCount() > 0) ? $doableServiceProviderId->fields['PK_USER'] : 0;
                    $INSERT_DATA_SERVICE_PROVIDER['PK_APPOINTMENT_MASTER'] = $PK_APPOINTMENT_MASTER;
                    $INSERT_DATA_SERVICE_PROVIDER['PK_USER'] = $SERVICE_PROVIDER_ID;
                    db_perform_account('DOA_APPOINTMENT_SERVICE_PROVIDER', $INSERT_DATA_SERVICE_PROVIDER, 'insert');
                    break;
                default:
                    break;
            }
            $lineNumber++;
        }
        // Close opened CSV file
        fclose($csvFile);
        header("Location: csv_uploader.php");
    } else {
        echo "Please select valid file";
    }
}

function checkSessionCount($SESSION_COUNT, $PK_ENROLLMENT_MASTER, $PK_ENROLLMENT_SERVICE, $PK_USER_MASTER, $PK_SERVICE_MASTER)
{
    global $db;
    global $db_account;
    $SESSION_CREATED = $db_account->Execute("SELECT COUNT(`PK_ENROLLMENT_MASTER`) AS SESSION_COUNT FROM `DOA_APPOINTMENT_MASTER` WHERE `PK_ENROLLMENT_MASTER` = " . $PK_ENROLLMENT_MASTER . " AND PK_ENROLLMENT_SERVICE = " . $PK_ENROLLMENT_SERVICE);
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
<?php require_once('../includes/header.php'); ?>

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">
        <?php require_once('../includes/top_menu.php'); ?>
        <div class="page-wrapper">
            <?php require_once('../includes/top_menu_bar.php') ?>
            <?php require_once('../includes/setup_menu_super_admin.php') ?>
            <div class="container-fluid body_content m-0">
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
                            <a type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" href="database_uploader.php"><i class="fa fa-plus-circle"></i> Upload From DB</a>
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
                                    $row = $db->Execute("SELECT DOA_ACCOUNT_MASTER.*, DOA_BUSINESS_TYPE.BUSINESS_TYPE FROM DOA_ACCOUNT_MASTER LEFT JOIN DOA_BUSINESS_TYPE ON DOA_BUSINESS_TYPE.PK_BUSINESS_TYPE = DOA_ACCOUNT_MASTER.PK_BUSINESS_TYPE ORDER BY CREATED_ON DESC");
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
                        <!-- <div class="col-md-2">
                            <div class="form-group">
                                <label class="form-label">Select Database Name</label>
                                <select class="form-control" name="DATABASE_NAME" id="DATABASE_NAME">
                                    <option value="">Select Database Name</option>
                                    <option value="AMTO">AMTO</option>
                                    <option value="AMWH">AMWH</option>
                                    <option value="AMLS">AMLS</option>
                                    <option value="AMWB">AMWB</option>
                                    <option value="AMLV">AMLV</option>
                                    <option value="JTLV">JTLV</option>
                                    <option value="AMMB">AMMB</option>
                                    <option value="AMMO">AMMO</option>
                                    <option value="AMNP">AMNP</option>
                                    <option value="AMSR">AMSR</option>
                                    <option value="AMPT">AMPT</option>
                                    <option value="AMLG">AMLG</option>
                                    <option value="AMSJ">AMSJ</option>
                                    <option value="AMTC">AMTC</option>
                                    <option value="AMSI">AMSI</option>
                                    <option value="AMBV">AMBV</option>
                                    <option value="AMFW">AMFW</option>
                                    <option value="AMSE">AMSE</option>
                                    <option value="AMEV">AMEV</option>
                                </select>
                            </div>
                        </div> -->
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label">Select Table Name</label>
                                <select class="form-control" name="TABLE_NAME" id="TABLE_NAME" onchange="viewCsvDownload(this)">
                                    <option value="">Select Table Name</option>
                                    <option value="DOA_INQUIRY_METHOD">DOA_INQUIRY_METHOD</option>
                                    <!--<option value="DOA_EVENT_TYPE">DOA_EVENT_TYPE</option>
                                <option value="DOA_HOLIDAY_LIST">DOA_HOLIDAY_LIST</option>-->
                                    <option value="DOA_USERS">DOA_USERS</option>
                                    <option value="DOA_STAFF">DOA_STAFF</option>
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
                        <div class="col-md-2">
                            <div class="form-group">
                                <label class="form-label">Select CSV</label>
                                <input type="file" class="form-control" name="file">
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