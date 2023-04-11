<?php
require_once('../global/config.php');
$title = "Upload CSV";
require_once('upload_functions.php');

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 1 ){
    header("location:../login.php");
    exit;
}

if(!empty($_POST))
{
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
    if (!empty($_FILES['file']['name']) && in_array($_FILES['file']['type'], $fileMimes))
    {
        // Open uploaded CSV file with read-only mode
        $csvFile = fopen($_FILES['file']['tmp_name'], 'r');

        // Skip the first line
        fgetcsv($csvFile);

        // Parse data from CSV file line by line
        while (($getData = fgetcsv($csvFile, 10000, ",")) !== FALSE)
        {
            switch ($_POST['TABLE_NAME']) {
                case 'DOA_INQUIRY_METHOD':
                    $INQUIRY_METHOD = $getData[0];
                    $table_data = $db->Execute("SELECT * FROM DOA_INQUIRY_METHOD WHERE INQUIRY_METHOD='$getData[0]' AND PK_ACCOUNT_MASTER='$_POST[PK_ACCOUNT_MASTER]'");
                    if ($table_data->RecordCount() == 0) {
                        $INSERT_DATA['PK_ACCOUNT_MASTER'] = $_POST['PK_ACCOUNT_MASTER'];
                        $INSERT_DATA['INQUIRY_METHOD'] = $INQUIRY_METHOD;
                        $INSERT_DATA['ACTIVE'] = 1;
                        $INSERT_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
                        $INSERT_DATA['CREATED_ON'] = date("Y-m-d H:i");
                        db_perform('DOA_INQUIRY_METHOD', $INSERT_DATA, 'insert');
                    }
                    break;

                case 'DOA_EVENT_TYPE':
                    $table_data = $db->Execute("SELECT * FROM DOA_EVENT_TYPE WHERE EVENT_TYPE='$getData[0]' AND PK_ACCOUNT_MASTER='$_POST[PK_ACCOUNT_MASTER]'");
                    if ($table_data->RecordCount() == 0) {
                        $INSERT_DATA['PK_ACCOUNT_MASTER'] = $_POST['PK_ACCOUNT_MASTER'];
                        $INSERT_DATA['EVENT_TYPE'] = $getData[0];
                        $INSERT_DATA['COLOR_CODE'] = $getData[1];
                        $INSERT_DATA['ACTIVE'] = 1;
                        $INSERT_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
                        $INSERT_DATA['CREATED_ON'] = date("Y-m-d H:i");
                        db_perform('DOA_EVENT_TYPE', $INSERT_DATA, 'insert');
                    }
                    break;

                case 'DOA_HOLIDAY_LIST':
                    $table_data = $db->Execute("SELECT * FROM DOA_HOLIDAY_LIST WHERE HOLIDAY_NAME='$getData[1]' AND PK_ACCOUNT_MASTER='$_POST[PK_ACCOUNT_MASTER]'");
                    if ($table_data->RecordCount() == 0) {
                        $INSERT_DATA['PK_ACCOUNT_MASTER'] = $_POST['PK_ACCOUNT_MASTER'];
                        $INSERT_DATA['HOLIDAY_DATE'] = date("Y-m-d", strtotime($getData[0]));
                        $INSERT_DATA['HOLIDAY_NAME'] = $getData[1];
                        db_perform('DOA_HOLIDAY_LIST', $INSERT_DATA, 'insert');
                    }
                    break;

                case 'DOA_USERS':
                    $table_data = $db->Execute("SELECT * FROM DOA_USERS WHERE USER_ID='$getData[18]' AND PK_ACCOUNT_MASTER='$_POST[PK_ACCOUNT_MASTER]'");
                    if ($table_data->RecordCount() == 0) {
                        $roleId = $getData[1];
                        $getRole = getRole($roleId);
                        $doableRoleId = $db->Execute("SELECT PK_ROLES FROM DOA_ROLES WHERE ROLES='$getRole'");
                        $INSERT_DATA['PK_ACCOUNT_MASTER'] = $_POST['PK_ACCOUNT_MASTER'];
                        $INSERT_DATA['PK_ROLES'] = $doableRoleId->fields['PK_ROLES'];
                        $INSERT_DATA['PK_LOCATION'] = $getData[25];
                        $INSERT_DATA['FIRST_NAME'] = $getData[3];
                        $INSERT_DATA['LAST_NAME'] = $getData[4];
                        $INSERT_DATA['USER_API_KEY'] = $getData[2];
                        $INSERT_DATA['USER_ID'] = $getData[18];
                        $INSERT_DATA['EMAIL_ID'] = $getData[13];
                        $INSERT_DATA['TAX_ID'] = $getData[14];
                        if (!empty($getData[12]) && $getData[12] != null) {
                            $INSERT_DATA['PHONE'] = $getData[12];
                        } elseif (!empty($getData[11]) && $getData[11] != null) {
                            $INSERT_DATA['PHONE'] = $getData[11];
                        }
                        $INSERT_DATA['PASSWORD'] = $getData[19];
                        $INSERT_DATA['USER_IMAGE'] = $getData[7];
                        $INSERT_DATA['ACTIVE'] = $getData[16];
                        $INSERT_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
                        $INSERT_DATA['CREATED_ON'] = date("Y-m-d H:i");
                        db_perform('DOA_USERS', $INSERT_DATA, 'insert');
                        $PK_USER = $db->insert_ID();

                        if ($PK_USER) {
                            $USER_DATA['PK_USER'] = $PK_USER;
                            $USER_DATA['GENDER'] = $getData[5];
                            $USER_DATA['DOB'] = date("Y-m-d", strtotime($getData[15]));
                            $USER_DATA['ADDRESS'] = $getData[6];
                            $USER_DATA['ADDRESS_1'] = $getData[7];
                            $USER_DATA['CITY'] = $getData[8];
                            $USER_DATA['PK_STATES'] = $getData[9];
                            $USER_DATA['ZIP'] = $getData[10];
                            $USER_DATA['NOTES'] = $getData[17];
                            $USER_DATA['ACTIVE'] = $getData[16];
                            $USER_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
                            $USER_DATA['CREATED_ON'] = date("Y-m-d H:i");
                            db_perform('DOA_USER_PROFILE', $USER_DATA, 'insert');
                        }
                    }
                    break;

                case 'DOA_CUSTOMER':
                    $table_data = $db->Execute("SELECT * FROM DOA_USERS WHERE USER_ID='$getData[1]' AND PK_ACCOUNT_MASTER='$_POST[PK_ACCOUNT_MASTER]'");
                    if ($table_data->RecordCount() == 0) {
                        $INSERT_DATA['PK_ACCOUNT_MASTER'] = $_POST['PK_ACCOUNT_MASTER'];
                        $INSERT_DATA['PK_ROLES'] = 4;
                        $INSERT_DATA['USER_ID'] = $getData[1];
                        $INSERT_DATA['FIRST_NAME'] = $getData[2];
                        $INSERT_DATA['LAST_NAME'] = $getData[3];
                        $INSERT_DATA['USER_API_KEY'] = $getData[0];
                        $INSERT_DATA['EMAIL_ID'] = $getData[25];
                        //$INSERT_DATA['HOME_PHONE'] = $getData[18];
                        if (!empty($getData[21]) && $getData[21] != null && $getData[21] != "   -   -    *") {
                            $INSERT_DATA['PHONE'] = $getData[21];
                        } elseif (!empty($getData[19]) && $getData[19] != null && $getData[19] != "   -   -    *") {
                            $INSERT_DATA['PHONE'] = $getData[19];
                        } elseif (!empty($getData[20]) && $getData[20] != null && $getData[20] != "   -   -    *") {
                            $INSERT_DATA['PHONE'] = $getData[20];
                        }
                        $INSERT_DATA['ACTIVE'] = 1;
                        $INSERT_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
                        $INSERT_DATA['CREATED_ON'] = date("Y-m-d H:i");
                        db_perform('DOA_USERS', $INSERT_DATA, 'insert');
                        $PK_USER = $db->insert_ID();

                        if ($PK_USER) {
                            $USER_DATA['PK_USER'] = $PK_USER;
                            if ($getData[8] == 0) {
                                $USER_DATA['GENDER'] = "Male";
                            } elseif ($getData[8] == 1) {
                                $USER_DATA['GENDER'] = "Female";
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
                            $state_code = $getData[17];
                            $doableState = $db->Execute("SELECT PK_STATES FROM DOA_STATES WHERE STATE_CODE='$state_code'");
                            $USER_DATA['PK_COUNTRY'] = 1;
                            $USER_DATA['PK_STATES'] = $doableState->fields['PK_STATES'];
                            $USER_DATA['ZIP'] = $getData[18];
                            $USER_DATA['NOTES'] = $getData[16];
                            $USER_DATA['ACTIVE'] = 1;
                            $USER_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
                            $USER_DATA['CREATED_ON'] = date("Y-m-d H:i");
                            db_perform('DOA_USER_PROFILE', $USER_DATA, 'insert');

                            $USER_MASTER_DATA['PK_USER'] = $PK_USER;
                            $USER_MASTER_DATA['PK_ACCOUNT_MASTER'] = $_POST['PK_ACCOUNT_MASTER'];
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
                                $CUSTOMER_DATA['PARTNER_FIRST_NAME'] = ($partner_name[0])?:'';
                                $CUSTOMER_DATA['PARTNER_LAST_NAME'] = ($partner_name[1])?:'';
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
                                db_perform('DOA_CUSTOMER_DETAILS', $CUSTOMER_DATA, 'insert');
                                $PK_CUSTOMER_DETAILS = $db->insert_ID();

                                if (!empty($getData[19]) && $getData[19] != "   -   -    *") {
                                    $PHONE_DATA['PK_CUSTOMER_DETAILS'] = $PK_CUSTOMER_DETAILS;
                                    $PHONE_DATA['PHONE'] = $getData[19];
                                    db_perform('DOA_CUSTOMER_PHONE', $PHONE_DATA, 'insert');
                                }

                                if (!empty($getData[20]) && $getData[20] != "   -   -    *") {
                                    $PHONE_DATA['PK_CUSTOMER_DETAILS'] = $PK_CUSTOMER_DETAILS;
                                    $PHONE_DATA['PHONE'] = $getData[20];
                                    db_perform('DOA_CUSTOMER_PHONE', $PHONE_DATA, 'insert');
                                }

                                if ($getData[10] != "0000-00-00 00:00:00" && $getData[10] > 0) {
                                    $SPECIAL_DATA['PK_CUSTOMER_DETAILS'] = $PK_CUSTOMER_DETAILS;
                                    $SPECIAL_DATA['SPECIAL_DATE'] = date("Y-m-d", strtotime($getData[10]));
                                    $SPECIAL_DATA['DATE_NAME'] = $getData[12];
                                    db_perform('DOA_SPECIAL_DATE', $SPECIAL_DATA, 'insert');
                                }
                                if ($getData[11] != "0000-00-00 00:00:00" && $getData[11] > 0) {
                                    $SPECIAL_DATA_1['PK_CUSTOMER_DETAILS'] = $PK_CUSTOMER_DETAILS;
                                    $SPECIAL_DATA_1['SPECIAL_DATE'] = date("Y-m-d", strtotime($getData[11]));
                                    $SPECIAL_DATA_1['DATE_NAME'] = $getData[13];
                                    db_perform('DOA_SPECIAL_DATE', $SPECIAL_DATA_1, 'insert');
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
                                    $doableInquiryId = $db->Execute("SELECT PK_INQUIRY_METHOD FROM DOA_INQUIRY_METHOD WHERE INQUIRY_METHOD='$getInquiry'");
                                    $INQUIRY_VALUE['PK_INQUIRY_METHOD'] = $doableInquiryId->fields['PK_INQUIRY_METHOD'];
                                }

                                if (!empty($getData[37])) {
                                    $takerId = $getData[37];
                                    $getTaker = getTaker($takerId);
                                    $doableTakerId = $db->Execute("SELECT PK_USER FROM DOA_USERS WHERE USER_ID='$getTaker'");
                                    $INQUIRY_VALUE['INQUIRY_TAKER_ID'] = $doableTakerId->fields['PK_USER'];
                                }
                                db_perform('DOA_USER_INTEREST_OTHER_DATA', $INQUIRY_VALUE, 'insert');
                            }
                        }
                    }
                    break;

                case 'DOA_SERVICE_MASTER':
                    $table_data = $db->Execute("SELECT * FROM DOA_SERVICE_MASTER WHERE SERVICE_NAME='$getData[1]' AND PK_ACCOUNT_MASTER='$_POST[PK_ACCOUNT_MASTER]'");
                    if ($table_data->RecordCount() == 0) {
                        $INSERT_DATA['PK_ACCOUNT_MASTER'] = $_POST['PK_ACCOUNT_MASTER'];
                        $INSERT_DATA['SERVICE_NAME'] = $getData[1];
                        $INSERT_DATA['PK_SERVICE_CLASS'] = 2;
                        $INSERT_DATA['IS_SCHEDULE'] = 1;
                        $INSERT_DATA['DESCRIPTION'] = $getData[1];
                        $INSERT_DATA['ACTIVE'] = 1;
                        db_perform('DOA_SERVICE_MASTER', $INSERT_DATA, 'insert');
                    }
                    break;

                case 'DOA_SERVICE_CODE':
                    $table_data = $db->Execute("SELECT * FROM DOA_SERVICE_CODE WHERE SERVICE_CODE='$getData[1]' AND PK_ACCOUNT_MASTER='$_POST[PK_ACCOUNT_MASTER]'");
                    if ($table_data->RecordCount() == 0) {
                        $serviceId = $getData[3];
                        [$getService, $isChargable] = getService($serviceId);
                        $doableServiceId = $db->Execute("SELECT PK_SERVICE_MASTER FROM DOA_SERVICE_MASTER WHERE SERVICE_NAME='$getService'");
                        $INSERT_DATA['PK_SERVICE_MASTER'] = $doableServiceId->fields['PK_SERVICE_MASTER'];
                        $INSERT_DATA['SERVICE_CODE'] = $getData[1];
                        $INSERT_DATA['PK_FREQUENCY'] = 0;
                        $INSERT_DATA['DESCRIPTION'] = $getData[1];
                        $INSERT_DATA['DURATION'] = $getData[4];
                        $serviceName = $getData[1];
                        if (strpos($serviceName, "Group")) {
                            $INSERT_DATA['IS_GROUP'] = 1;
                        } else {
                            $INSERT_DATA['IS_GROUP'] = 0;
                        }
                        $INSERT_DATA['CAPACITY'] = $getData[5];
                        if ($isChargable == "Y") {
                            $INSERT_DATA['IS_CHARGEABLE'] = 1;
                        } elseif ($isChargable == "N") {
                            $INSERT_DATA['IS_CHARGEABLE'] = 0;
                        }
                        if ($getData[13] == "Active") {
                            $INSERT_DATA['ACTIVE'] = 1;
                        } elseif ($getData[13] == "Not Active") {
                            $INSERT_DATA['ACTIVE'] = 0;
                        }
                        db_perform('DOA_SERVICE_CODE', $INSERT_DATA, 'insert');
                    }
                    break;

                case "DOA_ENROLLMENT_TYPE":
                    $table_data = $db->Execute("SELECT * FROM DOA_ENROLLMENT_TYPE WHERE ENROLLMENT_TYPE='$getData[1]' AND PK_ACCOUNT_MASTER='$_POST[PK_ACCOUNT_MASTER]'");
                    if ($table_data->RecordCount() == 0) {
                        $INSERT_DATA['ENROLLMENT_TYPE'] = $getData[1];
                        $INSERT_DATA['ACTIVE'] = 1;
                        db_perform('DOA_ENROLLMENT_TYPE', $INSERT_DATA, 'insert');
                    }
                    break;

                case "DOA_ENROLLMENT_MASTER":
                    $table_data = $db->Execute("SELECT * FROM DOA_ENROLLMENT_MASTER WHERE OLD_DATABASE_PK_ID='$getData[0]' AND PK_ACCOUNT_MASTER='$_POST[PK_ACCOUNT_MASTER]'");
                    if ($table_data->RecordCount() == 0) {
                        $INSERT_DATA['OLD_DATABASE_PK_ID'] = $getData[0];
                        $INSERT_DATA['ENROLLMENT_NAME'] = $getData[3];
                        $account_data = $db->Execute("SELECT ENROLLMENT_ID_CHAR, ENROLLMENT_ID_NUM FROM `DOA_ACCOUNT_MASTER` WHERE `PK_ACCOUNT_MASTER` = '$_POST[PK_ACCOUNT_MASTER]'");
                        if ($account_data->RecordCount() > 0){
                            $enrollment_char = $account_data->fields['ENROLLMENT_ID_CHAR'];
                        } else {
                            $enrollment_char = 'ENR';
                        }
                        $enrollment_data = $db->Execute("SELECT ENROLLMENT_ID FROM `DOA_ENROLLMENT_MASTER` WHERE `PK_ACCOUNT_MASTER` = '$_POST[PK_ACCOUNT_MASTER]' ORDER BY PK_ENROLLMENT_MASTER DESC LIMIT 1");
                        if ($enrollment_data->RecordCount() > 0){
                            $last_enrollment_id = str_replace($enrollment_char, '', $enrollment_data->fields['ENROLLMENT_ID']) ;
                            $INSERT_DATA['ENROLLMENT_ID'] = $enrollment_char.(intval($last_enrollment_id)+1);
                        }else{
                            $INSERT_DATA['ENROLLMENT_ID'] = $enrollment_char.$account_data->fields['ENROLLMENT_ID_NUM'];
                        }

                        $INSERT_DATA['PK_ACCOUNT_MASTER'] = $_POST['PK_ACCOUNT_MASTER'];
                        $customerId = $getData[4];
                        $doableCustomerId = $db->Execute("SELECT DOA_USER_MASTER.PK_USER_MASTER FROM DOA_USER_MASTER INNER JOIN DOA_USERS ON DOA_USER_MASTER.PK_USER=DOA_USERS.PK_USER WHERE DOA_USERS.USER_ID='$customerId' AND DOA_USER_MASTER.PK_ACCOUNT_MASTER = '$_POST[PK_ACCOUNT_MASTER]'");
                        $INSERT_DATA['PK_USER_MASTER'] = $doableCustomerId->fields['PK_USER_MASTER'];
                        $INSERT_DATA['AGREEMENT_PDF_LINK'] = $getData[41];
                        $INSERT_DATA['ENROLLMENT_BY_ID'] = $_POST['PK_ACCOUNT_MASTER'];
                        $INSERT_DATA['ACTIVE'] = 1;
                        $INSERT_DATA['STATUS'] = "A";
                        $INSERT_DATA['CREATED_BY'] = $_POST['PK_ACCOUNT_MASTER'];
                        db_perform('DOA_ENROLLMENT_MASTER', $INSERT_DATA, 'insert');
                        $PK_ENROLLMENT_MASTER = $db->insert_ID();

                        $INSERT_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
                        $INSERT_DATA['BILLING_REF'] = '';
                        $INSERT_DATA['BILLING_DATE'] = $getData[7];
                        $INSERT_DATA['DOWN_PAYMENT'] = '';
                        $INSERT_DATA['BALANCE_PAYABLE'] = $getData[33];
                        $INSERT_DATA['TOTAL_AMOUNT'] = $getData[38];
                        $INSERT_DATA['PAYMENT_METHOD'] = '';
                        $INSERT_DATA['PAYMENT_TERM'] = '';
                        $INSERT_DATA['NUMBER_OF_PAYMENT'] = '';
                        $INSERT_DATA['FIRST_DUE_DATE'] = '';
                        $INSERT_DATA['INSTALLMENT_AMOUNT'] = '';
                        db_perform('DOA_ENROLLMENT_BILLING', $INSERT_DATA, 'insert');
                    }
                    break;

                case "DOA_ENROLLMENT_PAYMENT":
                        $doableEnrollmentService = $db->Execute("SELECT DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER, DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_SERVICE FROM DOA_ENROLLMENT_SERVICE INNER JOIN DOA_SERVICE_MASTER ON DOA_SERVICE_MASTER.PK_SERVICE_MASTER=DOA_ENROLLMENT_SERVICE.PK_SERVICE_MASTER WHERE DOA_SERVICE_MASTER.SERVICE_NAME='General' AND PK_ACCOUNT_MASTER = '$_POST[PK_ACCOUNT_MASTER]'");
                        $INSERT_DATA['PK_ENROLLMENT_MASTER'] = $doableEnrollmentService->fields['PK_ENROLLMENT_MASTER'];
                        $PK_ENROLLMENT_MASTER = $doableEnrollmentService->fields['PK_ENROLLMENT_MASTER'];
                        $PK_ENROLLMENT_BILLING = $db->Execute("SELECT PK_ENROLLMENT_BILLING FROM DOA_ENROLLMENT_BILLING WHERE PK_ENROLLMENT_MASTER='$PK_ENROLLMENT_MASTER' ");
                        $INSERT_DATA['PK_ENROLLMENT_BILLING'] = $PK_ENROLLMENT_BILLING->fields['PK_ENROLLMENT_BILLING'];
                        $PK_PAYMENT_TYPE = $db->Execute("SELECT PK_PAYMENT_TYPE FROM DOA_PAYMENT_TYPE WHERE PAYMENT_TYPE='$getData[5]'");
                        $INSERT_DATA['PK_PAYMENT_TYPE'] = $PK_PAYMENT_TYPE->fields['PK_PAYMENT_TYPE'];
                        $INSERT_DATA['AMOUNT'] = $getData[8];;
                        $INSERT_DATA['REMAINING_AMOUNT'] = '';
                        $INSERT_DATA['PK_PAYMENT_TYPE_REMAINING'] = '';
                        $INSERT_DATA['NAME'] = $getData[19];
                        $INSERT_DATA['CARD_NUMBER'] = $getData[16];
                        $INSERT_DATA['SECURITY_CODE'] = '';
                        $INSERT_DATA['EXPIRATION_DATE'] = '';
                        $INSERT_DATA['CHECK_NUMBER'] = $getData[17];
                        $INSERT_DATA['CHECK_DATE'] = '';
                        $INSERT_DATA['NOTE'] = $getData[18];
                        $orgDate = $getData[7];
                        $newDate = date("Y-m-d", strtotime($orgDate));
                        $INSERT_DATA['PAYMENT_DATE'] = $newDate;
                        $INSERT_DATA['PAYMENT_INFO'] = $getData[20];
                        db_perform('DOA_ENROLLMENT_PAYMENT', $INSERT_DATA, 'insert');

                    break;

                case "DOA_ENROLLMENT_SERVICE":
                    $table_data = $db->Execute("SELECT * FROM DOA_ENROLLMENT_SERVICE WHERE  PK_ACCOUNT_MASTER='$_POST[PK_ACCOUNT_MASTER]'");
                    if ($table_data->RecordCount() == 0) {
                        $enrollmentId = $getData[4];
                        $doableEnrollmentId = $db->Execute("SELECT PK_ENROLLMENT_MASTER FROM DOA_ENROLLMENT_MASTER WHERE OLD_DATABASE_PK_ID = '$enrollmentId'");
                        $SERVICE_DATA['PK_ENROLLMENT_MASTER'] =  $doableEnrollmentId->fields['PK_ENROLLMENT_MASTER'];
                        $serviceId = $getData[2];
                        $getServiceMaster = getServiceMaster($serviceId);
                        $doableServiceId = $db->Execute("SELECT PK_SERVICE_MASTER, DESCRIPTION FROM DOA_SERVICE_MASTER WHERE SERVICE_NAME='$getServiceMaster'");
                        $SERVICE_DATA['PK_SERVICE_MASTER'] =  $doableServiceId->fields['PK_SERVICE_MASTER'];
                        $SERVICE_DATA['PK_SERVICE_CODE'] =  0;
                        $SERVICE_DATA['FREQUENCY'] =  0;
                        $SERVICE_DATA['SERVICE_DETAILS'] = $doableServiceId->fields['DESCRIPTION'];
                        [$getQuantity, $getCost] = getQuantity($enrollmentId);
                        $SERVICE_DATA['NUMBER_OF_SESSION'] = $getQuantity;
                        $SERVICE_DATA['PRICE_PER_SESSION'] = $getCost;
                        [$getTotal, $getDiscount, $getFinalAmount] = getEnrollmentDetails($enrollmentId);
                        $SERVICE_DATA['TOTAL'] = $getTotal;
                        $SERVICE_DATA['DISCOUNT'] = $getDiscount;
                        $SERVICE_DATA['FINAL_AMOUNT'] = $getFinalAmount;
                        db_perform('DOA_ENROLLMENT_SERVICE', $SERVICE_DATA, 'insert');
                    }
                    break;

                case "DOA_APPOINTMENT_MASTER":
                        $INSERT_DATA['PK_ACCOUNT_MASTER'] = $_POST['PK_ACCOUNT_MASTER'];
                        $studentId = $getData[3];
                        $getEmail = getUser($studentId);
                        $doableUserId = $db->Execute("SELECT DOA_USER_MASTER.PK_USER_MASTER, DOA_USER_MASTER.PK_USER FROM DOA_USER_MASTER INNER JOIN DOA_USERS ON DOA_USER_MASTER.PK_USER=DOA_USERS.PK_USER WHERE DOA_USERS.EMAIL_ID='$getEmail' AND DOA_USER_MASTER.PK_ACCOUNT_MASTER = '$_POST[PK_ACCOUNT_MASTER]'");
                        $INSERT_DATA['CUSTOMER_ID'] = $doableUserId->fields['PK_USER_MASTER'];
                        $INSERT_DATA['SERVICE_PROVIDER_ID'] = $doableUserId->fields['PK_USER'];

//                        $doableUserMaster = $db->Execute("SELECT PK_USER_MASTER FROM DOA_CUSTOMER_DETAILS WHERE FIRST_NAME='General' AND LAST_NAME = 'Customer'");
//                        $PK_USER_MASTER = $doableUserMaster->fields['PK_USER_MASTER'];
//                        $doableEnrollment = $db->Execute("SELECT PK_ENROLLMENT_MASTER FROM DOA_ENROLLMENT_MASTER WHERE PK_USER_MASTER='$PK_USER_MASTER' AND PK_ACCOUNT_MASTER = '$_POST[PK_ACCOUNT_MASTER]'");
//                        $doableUserMaster = $db->Execute("SELECT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER FROM DOA_CUSTOMER_DETAILS INNER JOIN DOA_ENROLLMENT_MASTER ON DOA_CUSTOMER_DETAILS.PK_USER_MASTER=DOA_ENROLLMENT_MASTER.PK_USER_MASTER WHERE DOA_CUSTOMER_DETAILS.FIRST_NAME='General' AND DOA_CUSTOMER_DETAILS.LAST_NAME = 'Customer';");
                        $doableEnrollmentService = $db->Execute("SELECT DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER, DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_SERVICE FROM DOA_ENROLLMENT_SERVICE INNER JOIN DOA_SERVICE_MASTER ON DOA_SERVICE_MASTER.PK_SERVICE_MASTER=DOA_ENROLLMENT_SERVICE.PK_SERVICE_MASTER WHERE DOA_SERVICE_MASTER.SERVICE_NAME='General' AND PK_ACCOUNT_MASTER = '$_POST[PK_ACCOUNT_MASTER]'");
                        $INSERT_DATA['PK_ENROLLMENT_MASTER'] = $doableEnrollmentService->fields['PK_ENROLLMENT_MASTER'];
                        $INSERT_DATA['PK_ENROLLMENT_SERVICE'] = $doableEnrollmentService->fields['PK_ENROLLMENT_SERVICE'];

                        //$serviceId = $getData[9];
                        //$getServiceMaster = getServiceMaster($serviceId);
                        $doableServiceId = $db->Execute("SELECT PK_SERVICE_MASTER FROM DOA_SERVICE_MASTER WHERE SERVICE_NAME='General'");
                        $INSERT_DATA['PK_SERVICE_MASTER'] =  $doableServiceId->fields['PK_SERVICE_MASTER'];
                        $INSERT_DATA['PK_SERVICE_CODE'] =  0;
                        $orgDate = $getData[5];
                        $newDate = date("Y-m-d", strtotime($orgDate));
                        $INSERT_DATA['DATE'] = $newDate;
                        $INSERT_DATA['START_TIME'] = $getData[6];
                        $endTime = strtotime($getData[6]) + $getData[8]*60;
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
                        $INSERT_DATA['CREATED_BY'] = $_POST['PK_ACCOUNT_MASTER'];
                        db_perform('DOA_APPOINTMENT_MASTER', $INSERT_DATA, 'insert');

                    break;

                case "DOA_EVENT":
                    $table_data = $db->Execute("SELECT * FROM DOA_EVENT WHERE ENROLLMENT_TYPE='$getData[1]' AND PK_ACCOUNT_MASTER='$_POST[PK_ACCOUNT_MASTER]'");
                    if ($table_data->RecordCount() == 0) {
                        $INSERT_DATA['PK_ACCOUNT_MASTER'] = $_POST['PK_ACCOUNT_MASTER'];
                        $INSERT_DATA['HEADER'] = $getData[4];
                        if ($getData[9] == "G") {
                            $pk_event_type = $db->Execute("SELECT PK_EVENT_TYPE FROM DOA_EVENT_TYPE WHERE EVENT_TYPE='General' AND PK_ACCOUNT_MASTER='$_POST[PK_ACCOUNT_MASTER]'");
                            $INSERT_DATA['PK_EVENT_TYPE'] = $pk_event_type->fields['PK_EVENT_TYPE'];
                        } else {
                            $INSERT_DATA['PK_EVENT_TYPE'] = 0;
                        }
                        $INSERT_DATA['START_DATE'] = $getData[5];
                        $INSERT_DATA['START_TIME'] = $getData[6];
                        $endTime = strtotime($getData[6]) + $getData[8] * 60;
                        $convertedTime = date('H:i:s', $endTime);
                        $INSERT_DATA['END_TIME'] = $convertedTime;
                        $INSERT_DATA['DESCRIPTION'] = $getData[15];
                        $INSERT_DATA['SHARE_WITH_CUSTOMERS'] = 0;
                        $INSERT_DATA['SHARE_WITH_SERVICE_PROVIDERS'] = 1;
                        $INSERT_DATA['SHARE_WITH_EMPLOYEES'] = 1;
                        if ($getData[10] == "A") {
                            $INSERT_DATA['ACTIVE'] = 1;
                        } else {
                            $INSERT_DATA['ACTIVE'] = 0;
                        }
                        $created_by = explode(" ", $getData[2]);
                        $firstName = ($created_by[0])?:'';
                        $lastName = ($created_by[1])?:'';
                        $doableNameId = $db->Execute("SELECT PK_USER FROM DOA_USERS WHERE FIRST_NAME='$firstName' AND LAST_NAME = '$lastName'");
                        $INSERT_DATA['CREATED_BY'] = $doableNameId->fields['PK_USER'];
                        db_perform('DOA_EVENT', $INSERT_DATA, 'insert');
                    }
                    break;
            }



            /*else if($_POST['TABLE_NAME'] == 'DOA_LOCATION') {
                $INSERT_DATA['PK_ACCOUNT_MASTER'] = $_POST['PK_ACCOUNT_MASTER'];
                $INSERT_DATA['LOCATION_NAME'] = $getData[0];
                $INSERT_DATA['LOCATION_CODE'] =$getData[1];
                $INSERT_DATA['ADDRESS'] = $getData[2];
                $INSERT_DATA['ADDRESS_1'] = $getData[3];
                $INSERT_DATA['CITY'] = $getData[4];
                $INSERT_DATA['PK_STATES'] = $getData[5];
                $INSERT_DATA['ZIP_CODE'] = $getData[6];
                $INSERT_DATA['PK_COUNTRY'] = $getData[7];
                $INSERT_DATA['PHONE'] = $getData[8];
                $INSERT_DATA['EMAIL'] = $getData[9];
                $INSERT_DATA['EMAIL'] = $getData[10];
                $INSERT_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
                $INSERT_DATA['CREATED_ON'] = date("Y-m-d H:i");
                db_perform('DOA_LOCATION', $INSERT_DATA, 'insert');
            }*/




//            // If user already exists in the database with the same email
//            $query = "SELECT id FROM users WHERE email = '" . $getData[1] . "'";
//
//            $check = mysqli_query($conn, $query);
//
//            if ($check->num_rows > 0)
//            {
//                mysqli_query($conn, "UPDATE DOA_INQUIRY_METHOD SET PK_INQUIRY_METHOD = '" . $name . "'");
//            }
//            else
//            {
//                mysqli_query($conn, "INSERT INTO users (PK_INQUIRY_METHOD, PK_ACCOUNT_MASTER, INQUIRY_METHOD, ACTIVE, CREATED_ON, CREATED_ON, EDITED_ON, EDITED_BY) VALUES ('" . $name . "', '" . $email . "', '" . $phone . "', NOW(), NOW(), '" . $status . "')");
//
//            }
        }

        // Close opened CSV file
        fclose($csvFile);

        header("Location: csv_uploader.php");
    }
    else
    {
        echo "Please select valid file";
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
                    <div class="col-md-4">
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
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Select Table Name</label>
                            <select class="form-control" name="TABLE_NAME" id="TABLE_NAME" onchange="viewCsvDownload(this)">
                                <option value="">Select Table Name</option>
                                <option value="DOA_INQUIRY_METHOD">DOA_INQUIRY_METHOD</option>
                                <option value="DOA_EVENT_TYPE">DOA_EVENT_TYPE</option>
                                <option value="DOA_HOLIDAY_LIST">DOA_HOLIDAY_LIST</option>
                                <option value="DOA_USERS">DOA_USERS</option>
                                <option value="DOA_CUSTOMER">DOA_CUSTOMER</option>
                                <option value="DOA_SERVICE_MASTER">DOA_SERVICE_MASTER</option>
                                <option value="DOA_SERVICE_CODE">DOA_SERVICE_CODE</option>
                                <option value="DOA_ENROLLMENT_MASTER">DOA_ENROLLMENT_MASTER</option>
                                <option value="DOA_ENROLLMENT_SERVICE">DOA_ENROLLMENT_SERVICE</option>
                                <option value="DOA_APPOINTMENT_MASTER">DOA_APPOINTMENT_MASTER</option>
                                <option value="DOA_EVENT">DOA_EVENT</option>
                                <option value="DOA_ENROLLMENT_PAYMENT">DOA_ENROLLMENT_PAYMENT</option>
                            </select>
                            <div id="view_download_div" class="m-10"></div>
                        </div>
                    </div>
                    <!--<div class="col-md-4">
                        <div class="form-group">
                            <select class="form-control" name="TABLE_NAME" id="TABLE_NAME">
                                <option value="">Select Table Name</option>
                                <?php
/*                                for($i=1; $i<=100; $i++){ */?>
                                <option value="<?/*=$i*/?>" <?/*=($i==5)?"selected":""*/?>><?/*=$i*/?></option>
                                <?php /*} */?>
                            </select>
                        </div>
                    </div>-->
                    <div class="col-md-4">
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
<?php require_once('../includes/footer.php');?>
</body>
<script>
    function viewCsvDownload(param) {
        let table_name = $(param).val();
        $('#view_download_div').html(`<a href="../uploads/csv_upload/${table_name}.csv" target="_blank">View Sample</a>`);
    }
</script>
</html>