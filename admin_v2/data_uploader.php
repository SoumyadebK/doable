<?php
error_reporting(0);
require_once('../global/config.php');
$title = "Data Uploader";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

$PK_ACCOUNT_MASTER = $_SESSION['PK_ACCOUNT_MASTER'];

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
        $account_data = $db->Execute("SELECT DB_NAME, ENROLLMENT_ID_CHAR, ENROLLMENT_ID_NUM FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER = " . $PK_ACCOUNT_MASTER);
        $DB_NAME = $account_data->fields['DB_NAME'];

        if ($account_data->fields['ENROLLMENT_ID_CHAR'] != null) {
            $enrollment_char = $account_data->fields['ENROLLMENT_ID_CHAR'];
        } else {
            $enrollment_char = 'ENR';
        }

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
        //$_SESSION['MIGRATION_DB_NAME'] = $_POST['DATABASE_NAME'];
        //require_once('upload_functions.php');

        // Open uploaded CSV file with read-only mode
        $csvFile = fopen($_FILES['file']['tmp_name'], 'r');
        $lineNumber = 1;

        $PK_LOCATION = $_POST['PK_LOCATION'];

        // Parse data from CSV file line by line
        while (($getData = fgetcsv($csvFile, 10000, ",")) !== FALSE) {
            if ($lineNumber === 1) {
                $lineNumber++;
                continue;
            }

            $customer_exist = $db->Execute("SELECT DOA_USERS.PK_USER, DOA_USER_MASTER.PK_USER_MASTER, DOA_USERS.USER_ID FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER WHERE DOA_USERS.EMAIL_ID LIKE '%" . $getData[2] . "%' AND DOA_USER_MASTER.PRIMARY_LOCATION_ID = '$PK_LOCATION' AND DOA_USERS.PK_ACCOUNT_MASTER = " . $PK_ACCOUNT_MASTER);
            if ($customer_exist->RecordCount() > 0) {
                $PK_USER = $customer_exist->fields['PK_USER'];
                $PK_USER_MASTER = $customer_exist->fields['PK_USER_MASTER'];
                $USER_ID = $customer_exist->fields['USER_ID'];
            } else {
                //CUSTOMER SECTION
                $USER_DATA['PK_ACCOUNT_MASTER'] = $PK_ACCOUNT_MASTER;
                $USER_DATA['FIRST_NAME'] = trim($getData[0]);
                $USER_DATA['LAST_NAME'] = trim($getData[1]);
                $USER_DATA['EMAIL_ID'] = $getData[2];
                $USER_DATA['PHONE'] = $getData[3];
                $USER_DATA['DOB'] = !empty($getData[4]) ? date("Y-m-d", strtotime($getData[4])) : null;

                $USER_DATA['ADDRESS'] = $getData[5];
                $USER_DATA['ADDRESS_1'] = null;
                $USER_DATA['CITY'] = $getData[7];
                $USER_DATA['PK_COUNTRY'] = 1;
                $state = $getData[8];
                $state_data = $db->Execute("SELECT PK_STATES FROM DOA_STATES WHERE STATE_NAME='$state' OR STATE_CODE='$state'");
                $USER_DATA['PK_STATES'] = ($state_data->RecordCount() > 0) ? $state_data->fields['PK_STATES'] : 0;
                $USER_DATA['ZIP'] = $getData[6];

                $USER_DATA['IS_DELETED'] = 0;
                $USER_DATA['ACTIVE'] = 1;
                $USER_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
                $USER_DATA['CREATED_ON'] = date("Y-m-d H:i");
                db_perform('DOA_USERS', $USER_DATA, 'insert');
                $PK_USER = $db->insert_ID();

                if ($PK_USER) {
                    $USER_ROLE_DATA['PK_USER'] = $PK_USER;
                    $USER_ROLE_DATA['PK_ROLES'] = 4;
                    db_perform('DOA_USER_ROLES', $USER_ROLE_DATA, 'insert');

                    $USER_LOCATION_DATA['PK_USER'] = $PK_USER;
                    $USER_LOCATION_DATA['PK_LOCATION'] = $PK_LOCATION;
                    db_perform('DOA_USER_LOCATION', $USER_LOCATION_DATA, 'insert');

                    $USER_MASTER_DATA['PK_USER'] = $PK_USER;
                    $USER_MASTER_DATA['PK_ACCOUNT_MASTER'] = $PK_ACCOUNT_MASTER;
                    $USER_MASTER_DATA['PRIMARY_LOCATION_ID'] = $PK_LOCATION;
                    $USER_MASTER_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
                    $USER_MASTER_DATA['CREATED_ON'] = date("Y-m-d H:i");
                    db_perform('DOA_USER_MASTER', $USER_MASTER_DATA, 'insert');
                    $PK_USER_MASTER = $db->insert_ID();
                }

                if ($PK_USER_MASTER) {
                    $USER_DATA_ACCOUNT['PK_USER_MASTER_DB'] = $PK_USER;
                    $USER_DATA_ACCOUNT['PK_ACCOUNT_MASTER'] = $PK_ACCOUNT_MASTER;
                    $USER_DATA_ACCOUNT['FIRST_NAME'] = trim($getData[0]);
                    $USER_DATA_ACCOUNT['LAST_NAME'] = trim($getData[1]);
                    $USER_DATA_ACCOUNT['EMAIL_ID'] = $getData[2];
                    $USER_DATA_ACCOUNT['PHONE'] = $getData[3];
                    $USER_DATA_ACCOUNT['CREATED_BY'] = $_SESSION['PK_USER'];
                    $USER_DATA_ACCOUNT['CREATED_ON'] = date("Y-m-d H:i");
                    db_perform_account('DOA_USERS', $USER_DATA_ACCOUNT, 'insert');

                    $CUSTOMER_DATA['PK_USER_MASTER'] = $PK_USER_MASTER;
                    $CUSTOMER_DATA['FIRST_NAME'] = trim($getData[0]);
                    $CUSTOMER_DATA['LAST_NAME'] = trim($getData[1]);
                    $CUSTOMER_DATA['EMAIL_ID'] = $getData[2];
                    $CUSTOMER_DATA['PHONE'] = $getData[3];
                    $USER_DATA_ACCOUNT['DOB'] = !empty($getData[4]) ? date("Y-m-d", strtotime($getData[4])) : null;
                    db_perform_account('DOA_CUSTOMER_DETAILS', $CUSTOMER_DATA, 'insert');
                    $PK_CUSTOMER_DETAILS = $db_account->insert_ID();
                }
            }


            //$TOTAL_LESSONS = $getData[4] + $getData[5] + $getData[6];
            $TOTAL_LESSONS = $getData[9] + $getData[10] + $getData[11] + $getData[12];
            if ($TOTAL_LESSONS > 0) {
                if ($getData[21] <= 0) {
                    $enrollment_data = $db_account->Execute("SELECT ENROLLMENT_ID FROM `DOA_ENROLLMENT_MASTER` WHERE `PK_USER_MASTER` = " . $PK_USER_MASTER . " ORDER BY PK_ENROLLMENT_MASTER DESC LIMIT 1");
                    if ($enrollment_data->RecordCount() > 0) {
                        $last_enrollment_id = str_replace($enrollment_char, '', $enrollment_data->fields['ENROLLMENT_ID']);
                        $ENROLLMENT_DATA['ENROLLMENT_ID'] = $enrollment_char . '-' . (intval($last_enrollment_id) + 1);
                    } else {
                        $ENROLLMENT_DATA['ENROLLMENT_ID'] = $enrollment_char . '-' . $account_data->fields['ENROLLMENT_ID_NUM'];
                    }
                } else {
                    $ENROLLMENT_DATA['ENROLLMENT_ID'] = $enrollment_char . '-' . $getData[21];
                }

                $customer_enrollment_number = $db_account->Execute("SELECT CUSTOMER_ENROLLMENT_NUMBER FROM `DOA_ENROLLMENT_MASTER` WHERE PK_USER_MASTER = " . $PK_USER_MASTER . " ORDER BY PK_ENROLLMENT_MASTER DESC LIMIT 1");
                if ($customer_enrollment_number->RecordCount() > 0) {
                    $ENROLLMENT_DATA['CUSTOMER_ENROLLMENT_NUMBER'] = $customer_enrollment_number->fields['CUSTOMER_ENROLLMENT_NUMBER'] + 1;
                } else {
                    $ENROLLMENT_DATA['CUSTOMER_ENROLLMENT_NUMBER'] = 1;
                }

                if ($getData[21] == 0) {
                    $ENROLLMENT_DATA['PK_ENROLLMENT_TYPE'] = 5;
                } else if ($getData[21] == 1) {
                    $ENROLLMENT_DATA['PK_ENROLLMENT_TYPE'] = 5;
                } else if ($getData[21] == 2) {
                    $ENROLLMENT_DATA['PK_ENROLLMENT_TYPE'] = 2;
                } else if ($getData[21] == 3) {
                    $ENROLLMENT_DATA['PK_ENROLLMENT_TYPE'] = 13;
                } else {
                    $ENROLLMENT_DATA['PK_ENROLLMENT_TYPE'] = 9;
                }

                $TOTAL_COST = $getData[13];
                $PRICE_PER_SESSION = $TOTAL_COST / $getData[9];

                $ENROLLMENT_DATA['PK_USER_MASTER'] = $PK_USER_MASTER;
                $ENROLLMENT_DATA['PK_LOCATION'] = $PK_LOCATION;
                $ENROLLMENT_DATA['CHARGE_TYPE'] = 'Session';
                $ENROLLMENT_DATA['ENROLLMENT_BY_ID'] = $_SESSION['PK_USER'];
                $ENROLLMENT_DATA['ACTIVE'] = 1;
                $ENROLLMENT_DATA['STATUS'] = "CO";
                $ENROLLMENT_DATA['ENROLLMENT_DATE'] = date("Y-m-d");
                //$ENROLLMENT_DATA['EXPIRY_DATE'] = $getData[22];
                $ENROLLMENT_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
                $ENROLLMENT_DATA['CREATED_ON'] = date("Y-m-d H:i");
                //pre_R($ENROLLMENT_DATA);
                db_perform_account('DOA_ENROLLMENT_MASTER', $ENROLLMENT_DATA, 'insert');
                $PK_ENROLLMENT_MASTER = $db_account->insert_ID();

                //COMPLETED ENROLLMENT BILLING SECTION
                $BILLING_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
                $BILLING_DATA['BILLING_REF'] = '';
                $BILLING_DATA['BILLING_DATE'] = date('Y-m-d');
                $BILLING_DATA['ACTUAL_AMOUNT'] = $TOTAL_COST;
                $BILLING_DATA['DISCOUNT'] = 0;
                $BILLING_DATA['DOWN_PAYMENT'] = 0;
                $BILLING_DATA['BALANCE_PAYABLE'] = 0;
                $BILLING_DATA['TOTAL_AMOUNT'] = $TOTAL_COST;
                $BILLING_DATA['PAYMENT_METHOD'] = 'One Time';
                $BILLING_DATA['PAYMENT_TERM'] = '';
                $BILLING_DATA['NUMBER_OF_PAYMENT'] = 0;
                $BILLING_DATA['FIRST_DUE_DATE'] = date('Y-m-d');;
                $BILLING_DATA['INSTALLMENT_AMOUNT'] = 0;
                //pre_r($BILLING_DATA);
                db_perform_account('DOA_ENROLLMENT_BILLING', $BILLING_DATA, 'insert');
                $PK_ENROLLMENT_BILLING = $db_account->insert_ID();


                if ($getData[9] > 0) {
                    $SERVICE_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
                    $service_details = $db_account->Execute("SELECT DOA_SERVICE_MASTER.PK_SERVICE_MASTER, DOA_SERVICE_MASTER.DESCRIPTION, DOA_SERVICE_CODE.PK_SERVICE_CODE FROM DOA_SERVICE_MASTER LEFT JOIN DOA_SERVICE_CODE ON DOA_SERVICE_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_CODE.PK_SERVICE_MASTER WHERE SERVICE_NAME LIKE '%Private%'");
                    $SERVICE_DATA['PK_SERVICE_MASTER'] = $service_details->fields['PK_SERVICE_MASTER'];
                    $SERVICE_DATA['PK_SERVICE_CODE'] = $service_details->fields['PK_SERVICE_CODE'];
                    //$SERVICE_DATA['PK_SCHEDULING_CODE'] =  $PK_SCHEDULING_CODE;
                    $SERVICE_DATA['SERVICE_DETAILS'] = $service_details->fields['DESCRIPTION'];
                    $SERVICE_DATA['NUMBER_OF_SESSION'] = $getData[9];
                    $SERVICE_DATA['PRICE_PER_SESSION'] = $PRICE_PER_SESSION;
                    $SERVICE_DATA['TOTAL'] = $TOTAL_COST;
                    $SERVICE_DATA['TOTAL_AMOUNT_PAID'] = $TOTAL_COST;
                    $SERVICE_DATA['DISCOUNT'] = 0;
                    $SERVICE_DATA['FINAL_AMOUNT'] = $TOTAL_COST;
                    $SERVICE_DATA['STATUS'] = 'CO';
                    db_perform_account('DOA_ENROLLMENT_SERVICE', $SERVICE_DATA, 'insert');
                }

                if ($getData[10] > 0) {
                    $SERVICE_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
                    $service_details = $db_account->Execute("SELECT DOA_SERVICE_MASTER.PK_SERVICE_MASTER, DOA_SERVICE_MASTER.DESCRIPTION, DOA_SERVICE_CODE.PK_SERVICE_CODE FROM DOA_SERVICE_MASTER LEFT JOIN DOA_SERVICE_CODE ON DOA_SERVICE_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_CODE.PK_SERVICE_MASTER WHERE SERVICE_NAME LIKE '%Group%'");
                    $SERVICE_DATA['PK_SERVICE_MASTER'] = $service_details->fields['PK_SERVICE_MASTER'];
                    $SERVICE_DATA['PK_SERVICE_CODE'] = $service_details->fields['PK_SERVICE_CODE'];
                    //$SERVICE_DATA['PK_SCHEDULING_CODE'] =  $PK_SCHEDULING_CODE;
                    $SERVICE_DATA['SERVICE_DETAILS'] = $service_details->fields['DESCRIPTION'];
                    $SERVICE_DATA['NUMBER_OF_SESSION'] = $getData[10];
                    $SERVICE_DATA['PRICE_PER_SESSION'] = 0;
                    $SERVICE_DATA['TOTAL'] = 0;
                    $SERVICE_DATA['TOTAL_AMOUNT_PAID'] = 0;
                    $SERVICE_DATA['DISCOUNT'] = 0;
                    $SERVICE_DATA['FINAL_AMOUNT'] = 0;
                    $SERVICE_DATA['STATUS'] = 'CO';
                    db_perform_account('DOA_ENROLLMENT_SERVICE', $SERVICE_DATA, 'insert');
                }

                if ($getData[11] > 0) {
                    $SERVICE_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
                    $service_details = $db_account->Execute("SELECT DOA_SERVICE_MASTER.PK_SERVICE_MASTER, DOA_SERVICE_MASTER.DESCRIPTION, DOA_SERVICE_CODE.PK_SERVICE_CODE FROM DOA_SERVICE_MASTER LEFT JOIN DOA_SERVICE_CODE ON DOA_SERVICE_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_CODE.PK_SERVICE_MASTER WHERE SERVICE_NAME LIKE '%Party%'");
                    $SERVICE_DATA['PK_SERVICE_MASTER'] = $service_details->fields['PK_SERVICE_MASTER'];
                    $SERVICE_DATA['PK_SERVICE_CODE'] = $service_details->fields['PK_SERVICE_CODE'];
                    //$SERVICE_DATA['PK_SCHEDULING_CODE'] =  $PK_SCHEDULING_CODE;
                    $SERVICE_DATA['SERVICE_DETAILS'] = $service_details->fields['DESCRIPTION'];
                    $SERVICE_DATA['NUMBER_OF_SESSION'] = $getData[11];
                    $SERVICE_DATA['PRICE_PER_SESSION'] = 0;
                    $SERVICE_DATA['TOTAL'] = 0;
                    $SERVICE_DATA['TOTAL_AMOUNT_PAID'] = 0;
                    $SERVICE_DATA['DISCOUNT'] = 0;
                    $SERVICE_DATA['FINAL_AMOUNT'] = 0;
                    $SERVICE_DATA['STATUS'] = 'CO';
                    db_perform_account('DOA_ENROLLMENT_SERVICE', $SERVICE_DATA, 'insert');
                }

                if ($getData[12] > 0) {
                    $SERVICE_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
                    $service_details = $db_account->Execute("SELECT DOA_SERVICE_MASTER.PK_SERVICE_MASTER, DOA_SERVICE_MASTER.DESCRIPTION, DOA_SERVICE_CODE.PK_SERVICE_CODE FROM DOA_SERVICE_MASTER LEFT JOIN DOA_SERVICE_CODE ON DOA_SERVICE_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_CODE.PK_SERVICE_MASTER WHERE SERVICE_NAME LIKE '%Coach%'");
                    $SERVICE_DATA['PK_SERVICE_MASTER'] = $service_details->fields['PK_SERVICE_MASTER'];
                    $SERVICE_DATA['PK_SERVICE_CODE'] = $service_details->fields['PK_SERVICE_CODE'];
                    //$SERVICE_DATA['PK_SCHEDULING_CODE'] =  $PK_SCHEDULING_CODE;
                    $SERVICE_DATA['SERVICE_DETAILS'] = $service_details->fields['DESCRIPTION'];
                    $SERVICE_DATA['NUMBER_OF_SESSION'] = $getData[12];
                    $SERVICE_DATA['PRICE_PER_SESSION'] = 0;
                    $SERVICE_DATA['TOTAL'] = 0;
                    $SERVICE_DATA['TOTAL_AMOUNT_PAID'] = 0;
                    $SERVICE_DATA['DISCOUNT'] = 0;
                    $SERVICE_DATA['FINAL_AMOUNT'] = 0;
                    $SERVICE_DATA['STATUS'] = 'CO';
                    db_perform_account('DOA_ENROLLMENT_SERVICE', $SERVICE_DATA, 'insert');
                }

                //COMPLETED ENROLLMENT LEDGER
                $BILLING_LEDGER_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
                $BILLING_LEDGER_DATA['PK_ENROLLMENT_BILLING '] = $PK_ENROLLMENT_BILLING;
                $BILLING_LEDGER_DATA['TRANSACTION_TYPE'] = 'Billing';
                $BILLING_LEDGER_DATA['ENROLLMENT_LEDGER_PARENT'] = 0;
                $BILLING_LEDGER_DATA['DUE_DATE'] = date('Y-m-d');
                $BILLING_LEDGER_DATA['BILLED_AMOUNT'] = $TOTAL_COST;
                $BILLING_LEDGER_DATA['PAID_AMOUNT'] = $TOTAL_COST;
                $BILLING_LEDGER_DATA['BALANCE'] = $TOTAL_COST;
                $BILLING_LEDGER_DATA['IS_PAID'] = 1;
                $BILLING_LEDGER_DATA['STATUS'] = 'CO';
                $BILLING_LEDGER_DATA['IS_DOWN_PAYMENT'] = 0;
                //pre_r($BILLING_LEDGER_DATA);
                db_perform_account('DOA_ENROLLMENT_LEDGER', $BILLING_LEDGER_DATA, 'insert');
                $PK_ENROLLMENT_LEDGER = $db_account->insert_ID();

                //COMPLETED ENROLLMENT PAYMENT
                $ENROLLMENT_PAYMENT_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
                $ENROLLMENT_PAYMENT_DATA['PK_ENROLLMENT_BILLING'] = $PK_ENROLLMENT_BILLING;
                $ENROLLMENT_PAYMENT_DATA['PK_PAYMENT_TYPE'] = 12;
                $ENROLLMENT_PAYMENT_DATA['PK_ENROLLMENT_LEDGER'] = $PK_ENROLLMENT_LEDGER;
                $ENROLLMENT_PAYMENT_DATA['TYPE'] = 'Payment';
                $ENROLLMENT_PAYMENT_DATA['AMOUNT'] = $TOTAL_COST;
                $ENROLLMENT_PAYMENT_DATA['NOTE'] = '';
                $ENROLLMENT_PAYMENT_DATA['PAYMENT_DATE'] = date('Y-m-d');
                $ENROLLMENT_PAYMENT_DATA['PAYMENT_INFO'] = '';
                $ENROLLMENT_PAYMENT_DATA['PAYMENT_STATUS'] = 'Success';
                db_perform_account('DOA_ENROLLMENT_PAYMENT', $ENROLLMENT_PAYMENT_DATA, 'insert');
            }



            $TOTAL_LESSONS = $getData[14] + $getData[15] + $getData[16] + $getData[17];
            if ($TOTAL_LESSONS > 0) {
                //ACTIVE ENROLLMENT SECTION
                $enrollment_data = $db_account->Execute("SELECT ENROLLMENT_ID FROM `DOA_ENROLLMENT_MASTER` WHERE `PK_USER_MASTER` = " . $PK_USER_MASTER . " ORDER BY PK_ENROLLMENT_MASTER DESC LIMIT 1");
                if ($enrollment_data->RecordCount() > 0) {
                    $last_enrollment_id = str_replace($enrollment_char, '', $enrollment_data->fields['ENROLLMENT_ID']);
                    $ENROLLMENT_DATA['ENROLLMENT_ID'] = $enrollment_char . '-' . (intval($last_enrollment_id) + 1);
                } else {
                    $ENROLLMENT_DATA['ENROLLMENT_ID'] = $enrollment_char . '-' . $account_data->fields['ENROLLMENT_ID_NUM'];
                }

                $customer_enrollment_number = $db_account->Execute("SELECT CUSTOMER_ENROLLMENT_NUMBER FROM `DOA_ENROLLMENT_MASTER` WHERE PK_USER_MASTER = " . $PK_USER_MASTER . " ORDER BY PK_ENROLLMENT_MASTER DESC LIMIT 1");
                if ($customer_enrollment_number->RecordCount() > 0) {
                    $ENROLLMENT_DATA['CUSTOMER_ENROLLMENT_NUMBER'] = $customer_enrollment_number->fields['CUSTOMER_ENROLLMENT_NUMBER'] + 1;
                } else {
                    $ENROLLMENT_DATA['CUSTOMER_ENROLLMENT_NUMBER'] = 1;
                }

                if ($ENROLLMENT_DATA['CUSTOMER_ENROLLMENT_NUMBER'] == 0) {
                    $ENROLLMENT_DATA['PK_ENROLLMENT_TYPE'] = 5;
                } else if ($ENROLLMENT_DATA['CUSTOMER_ENROLLMENT_NUMBER'] == 1) {
                    $ENROLLMENT_DATA['PK_ENROLLMENT_TYPE'] = 5;
                } else if ($ENROLLMENT_DATA['CUSTOMER_ENROLLMENT_NUMBER'] == 2) {
                    $ENROLLMENT_DATA['PK_ENROLLMENT_TYPE'] = 2;
                } else if ($ENROLLMENT_DATA['CUSTOMER_ENROLLMENT_NUMBER'] == 3) {
                    $ENROLLMENT_DATA['PK_ENROLLMENT_TYPE'] = 13;
                } else {
                    $ENROLLMENT_DATA['PK_ENROLLMENT_TYPE'] = 9;
                }

                $TOTAL_COST = $getData[18];
                $PRICE_PER_SESSION = $TOTAL_COST / $getData[14];
                $TOTAL_PAID = $getData[20];
                $TOTAL_REMAINING = $getData[19];

                $ENROLLMENT_DATA['PK_USER_MASTER'] = $PK_USER_MASTER;
                $ENROLLMENT_DATA['PK_LOCATION'] = $PK_LOCATION;
                $ENROLLMENT_DATA['CHARGE_TYPE'] = 'Session';
                $ENROLLMENT_DATA['ENROLLMENT_BY_ID'] = $_SESSION['PK_USER'];
                $ENROLLMENT_DATA['ACTIVE'] = 1;
                $ENROLLMENT_DATA['STATUS'] = "A";
                $ENROLLMENT_DATA['ENROLLMENT_DATE'] = date("Y-m-d");
                //$ENROLLMENT_DATA['EXPIRY_DATE'] = $getData[22];
                $ENROLLMENT_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
                $ENROLLMENT_DATA['CREATED_ON'] = date("Y-m-d H:i");
                db_perform_account('DOA_ENROLLMENT_MASTER', $ENROLLMENT_DATA, 'insert');
                $PK_ENROLLMENT_MASTER = $db_account->insert_ID();

                //ACTIVE ENROLLMENT BILLING SECTION
                $BILLING_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
                $BILLING_DATA['BILLING_REF'] = '';
                $BILLING_DATA['BILLING_DATE'] = date('Y-m-d');
                $BILLING_DATA['ACTUAL_AMOUNT'] = $TOTAL_COST;
                $BILLING_DATA['DISCOUNT'] = 0;
                $BILLING_DATA['DOWN_PAYMENT'] = 0;
                $BILLING_DATA['BALANCE_PAYABLE'] = $TOTAL_COST;
                $BILLING_DATA['TOTAL_AMOUNT'] = $TOTAL_COST;
                $BILLING_DATA['PAYMENT_METHOD'] = 'One Time';
                $BILLING_DATA['PAYMENT_TERM'] = '';
                $BILLING_DATA['NUMBER_OF_PAYMENT'] = 0;
                $BILLING_DATA['FIRST_DUE_DATE'] = date('Y-m-d');;
                $BILLING_DATA['INSTALLMENT_AMOUNT'] = 0;
                db_perform_account('DOA_ENROLLMENT_BILLING', $BILLING_DATA, 'insert');
                $PK_ENROLLMENT_BILLING = $db_account->insert_ID();


                if ($getData[14] > 0) {
                    $SERVICE_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
                    $service_details = $db_account->Execute("SELECT DOA_SERVICE_MASTER.PK_SERVICE_MASTER, DOA_SERVICE_MASTER.DESCRIPTION, DOA_SERVICE_CODE.PK_SERVICE_CODE FROM DOA_SERVICE_MASTER LEFT JOIN DOA_SERVICE_CODE ON DOA_SERVICE_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_CODE.PK_SERVICE_MASTER WHERE SERVICE_NAME LIKE '%Private%'");
                    $SERVICE_DATA['PK_SERVICE_MASTER'] = $service_details->fields['PK_SERVICE_MASTER'];
                    $SERVICE_DATA['PK_SERVICE_CODE'] = $service_details->fields['PK_SERVICE_CODE'];
                    //$SERVICE_DATA['PK_SCHEDULING_CODE'] =  $PK_SCHEDULING_CODE;
                    $SERVICE_DATA['SERVICE_DETAILS'] = $service_details->fields['DESCRIPTION'];
                    $SERVICE_DATA['NUMBER_OF_SESSION'] = $getData[14];
                    $SERVICE_DATA['PRICE_PER_SESSION'] = $PRICE_PER_SESSION;
                    $SERVICE_DATA['TOTAL'] = $TOTAL_COST;
                    $SERVICE_DATA['TOTAL_AMOUNT_PAID'] = $TOTAL_PAID;
                    $SERVICE_DATA['DISCOUNT'] = 0;
                    $SERVICE_DATA['FINAL_AMOUNT'] = $TOTAL_COST;
                    $SERVICE_DATA['STATUS'] = 'A';
                    db_perform_account('DOA_ENROLLMENT_SERVICE', $SERVICE_DATA, 'insert');
                }

                if ($getData[15] > 0) {
                    $SERVICE_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
                    $service_details = $db_account->Execute("SELECT DOA_SERVICE_MASTER.PK_SERVICE_MASTER, DOA_SERVICE_MASTER.DESCRIPTION, DOA_SERVICE_CODE.PK_SERVICE_CODE FROM DOA_SERVICE_MASTER LEFT JOIN DOA_SERVICE_CODE ON DOA_SERVICE_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_CODE.PK_SERVICE_MASTER WHERE SERVICE_NAME LIKE '%Group%'");
                    $SERVICE_DATA['PK_SERVICE_MASTER'] = $service_details->fields['PK_SERVICE_MASTER'];
                    $SERVICE_DATA['PK_SERVICE_CODE'] = $service_details->fields['PK_SERVICE_CODE'];
                    //$SERVICE_DATA['PK_SCHEDULING_CODE'] =  $PK_SCHEDULING_CODE;
                    $SERVICE_DATA['SERVICE_DETAILS'] = $service_details->fields['DESCRIPTION'];
                    $SERVICE_DATA['NUMBER_OF_SESSION'] = $getData[15];
                    $SERVICE_DATA['PRICE_PER_SESSION'] = 0;
                    $SERVICE_DATA['TOTAL'] = 0;
                    $SERVICE_DATA['TOTAL_AMOUNT_PAID'] = 0;
                    $SERVICE_DATA['DISCOUNT'] = 0;
                    $SERVICE_DATA['FINAL_AMOUNT'] = 0;
                    $SERVICE_DATA['STATUS'] = 'A';
                    db_perform_account('DOA_ENROLLMENT_SERVICE', $SERVICE_DATA, 'insert');
                }

                if ($getData[16] > 0) {
                    $SERVICE_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
                    $service_details = $db_account->Execute("SELECT DOA_SERVICE_MASTER.PK_SERVICE_MASTER, DOA_SERVICE_MASTER.DESCRIPTION, DOA_SERVICE_CODE.PK_SERVICE_CODE FROM DOA_SERVICE_MASTER LEFT JOIN DOA_SERVICE_CODE ON DOA_SERVICE_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_CODE.PK_SERVICE_MASTER WHERE SERVICE_NAME LIKE '%Party%'");
                    $SERVICE_DATA['PK_SERVICE_MASTER'] = $service_details->fields['PK_SERVICE_MASTER'];
                    $SERVICE_DATA['PK_SERVICE_CODE'] = $service_details->fields['PK_SERVICE_CODE'];
                    //$SERVICE_DATA['PK_SCHEDULING_CODE'] =  $PK_SCHEDULING_CODE;
                    $SERVICE_DATA['SERVICE_DETAILS'] = $service_details->fields['DESCRIPTION'];
                    $SERVICE_DATA['NUMBER_OF_SESSION'] = $getData[16];
                    $SERVICE_DATA['PRICE_PER_SESSION'] = 0;
                    $SERVICE_DATA['TOTAL'] = 0;
                    $SERVICE_DATA['TOTAL_AMOUNT_PAID'] = 0;
                    $SERVICE_DATA['DISCOUNT'] = 0;
                    $SERVICE_DATA['FINAL_AMOUNT'] = 0;
                    $SERVICE_DATA['STATUS'] = 'A';
                    db_perform_account('DOA_ENROLLMENT_SERVICE', $SERVICE_DATA, 'insert');
                }

                if ($getData[17] > 0) {
                    $SERVICE_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
                    $service_details = $db_account->Execute("SELECT DOA_SERVICE_MASTER.PK_SERVICE_MASTER, DOA_SERVICE_MASTER.DESCRIPTION, DOA_SERVICE_CODE.PK_SERVICE_CODE FROM DOA_SERVICE_MASTER LEFT JOIN DOA_SERVICE_CODE ON DOA_SERVICE_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_CODE.PK_SERVICE_MASTER WHERE SERVICE_NAME LIKE '%Coach%'");
                    $SERVICE_DATA['PK_SERVICE_MASTER'] = $service_details->fields['PK_SERVICE_MASTER'];
                    $SERVICE_DATA['PK_SERVICE_CODE'] = $service_details->fields['PK_SERVICE_CODE'];
                    //$SERVICE_DATA['PK_SCHEDULING_CODE'] =  $PK_SCHEDULING_CODE;
                    $SERVICE_DATA['SERVICE_DETAILS'] = $service_details->fields['DESCRIPTION'];
                    $SERVICE_DATA['NUMBER_OF_SESSION'] = $getData[17];
                    $SERVICE_DATA['PRICE_PER_SESSION'] = 0;
                    $SERVICE_DATA['TOTAL'] = 0;
                    $SERVICE_DATA['TOTAL_AMOUNT_PAID'] = 0;
                    $SERVICE_DATA['DISCOUNT'] = 0;
                    $SERVICE_DATA['FINAL_AMOUNT'] = 0;
                    $SERVICE_DATA['STATUS'] = 'A';
                    db_perform_account('DOA_ENROLLMENT_SERVICE', $SERVICE_DATA, 'insert');
                }

                //ACTIVE ENROLLMENT LEDGER
                $BILLING_LEDGER_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
                $BILLING_LEDGER_DATA['PK_ENROLLMENT_BILLING '] = $PK_ENROLLMENT_BILLING;
                $BILLING_LEDGER_DATA['TRANSACTION_TYPE'] = 'Billing';
                $BILLING_LEDGER_DATA['ENROLLMENT_LEDGER_PARENT'] = 0;
                $BILLING_LEDGER_DATA['DUE_DATE'] = date('Y-m-d');
                $BILLING_LEDGER_DATA['BILLED_AMOUNT'] = $TOTAL_PAID;
                $BILLING_LEDGER_DATA['PAID_AMOUNT'] = 0;
                $BILLING_LEDGER_DATA['BALANCE'] = $TOTAL_PAID;
                $BILLING_LEDGER_DATA['IS_PAID'] = 1;
                $BILLING_LEDGER_DATA['STATUS'] = 'A';
                $BILLING_LEDGER_DATA['IS_DOWN_PAYMENT'] = 0;
                db_perform_account('DOA_ENROLLMENT_LEDGER', $BILLING_LEDGER_DATA, 'insert');
                $PK_ENROLLMENT_LEDGER = $db_account->insert_ID();

                //ACTIVE ENROLLMENT PAYMENT
                $ENROLLMENT_PAYMENT_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
                $ENROLLMENT_PAYMENT_DATA['PK_ENROLLMENT_BILLING'] = $PK_ENROLLMENT_BILLING;
                $ENROLLMENT_PAYMENT_DATA['PK_PAYMENT_TYPE'] = 12;
                $ENROLLMENT_PAYMENT_DATA['PK_ENROLLMENT_LEDGER'] = $PK_ENROLLMENT_LEDGER;
                $ENROLLMENT_PAYMENT_DATA['TYPE'] = 'Payment';
                $ENROLLMENT_PAYMENT_DATA['AMOUNT'] = $TOTAL_PAID;
                $ENROLLMENT_PAYMENT_DATA['NOTE'] = '';
                $ENROLLMENT_PAYMENT_DATA['PAYMENT_DATE'] = date('Y-m-d');
                $ENROLLMENT_PAYMENT_DATA['PAYMENT_INFO'] = '';
                $ENROLLMENT_PAYMENT_DATA['PAYMENT_STATUS'] = 'Success';
                db_perform_account('DOA_ENROLLMENT_PAYMENT', $ENROLLMENT_PAYMENT_DATA, 'insert');

                if ($TOTAL_REMAINING > 0) {
                    //ACTIVE ENROLLMENT LEDGER
                    $BILLING_LEDGER_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
                    $BILLING_LEDGER_DATA['PK_ENROLLMENT_BILLING '] = $PK_ENROLLMENT_BILLING;
                    $BILLING_LEDGER_DATA['TRANSACTION_TYPE'] = 'Billing';
                    $BILLING_LEDGER_DATA['ENROLLMENT_LEDGER_PARENT'] = 0;
                    $BILLING_LEDGER_DATA['DUE_DATE'] = date('Y-m-d');
                    $BILLING_LEDGER_DATA['BILLED_AMOUNT'] = $TOTAL_REMAINING;
                    $BILLING_LEDGER_DATA['PAID_AMOUNT'] = 0;
                    $BILLING_LEDGER_DATA['BALANCE'] = $TOTAL_REMAINING;
                    $BILLING_LEDGER_DATA['IS_PAID'] = 0;
                    $BILLING_LEDGER_DATA['STATUS'] = 'A';
                    $BILLING_LEDGER_DATA['IS_DOWN_PAYMENT'] = 0;
                    db_perform_account('DOA_ENROLLMENT_LEDGER', $BILLING_LEDGER_DATA, 'insert');
                    $PK_ENROLLMENT_LEDGER = $db_account->insert_ID();
                }
            }

            $lineNumber++;
        }
        // Close opened CSV file
        fclose($csvFile);
        //header("Location: csv_uploader.php");
    } else {
        echo "Please select valid file";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<?php include 'layout/header_script.php'; ?>
<?php require_once('../includes/header.php'); ?>
<?php include 'layout/header.php'; ?>

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">

        <div class="page-wrapper" style="padding-top: 0px !important;">

            <?php require_once('layout/setup_menu.php') ?>
            <div class="container-fluid body_content m-0" style="margin-top: 0px !important;">
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
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">Select Location</label>
                                <select class="form-control" name="PK_LOCATION" id="PK_LOCATION">
                                    <option value="">Select Location</option>
                                    <?php
                                    $row = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME FROM DOA_LOCATION WHERE PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND ACTIVE = 1 AND PK_ACCOUNT_MASTER = " . $_SESSION['PK_ACCOUNT_MASTER']);
                                    while (!$row->EOF) { ?>
                                        <option value="<?php echo $row->fields['PK_LOCATION']; ?>"><?= $row->fields['LOCATION_NAME'] ?></option>
                                    <?php $row->MoveNext();
                                    } ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">Select CSV</label>
                                <input type="file" class="form-control" name="file">
                                <a href="../uploads/Demo Sheet.csv" target="_blank">Download Demo Sheet</a>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white" style="margin-top: 30px">Submit</button>
                        </div>
                    </div>

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
</script>

</html>