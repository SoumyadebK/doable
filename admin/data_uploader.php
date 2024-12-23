<?php
error_reporting(0);
require_once('../global/config.php');
$title = "Data Uploader";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 2 ){
    header("location:../login.php");
    exit;
}

$PK_ACCOUNT_MASTER = $_SESSION['PK_ACCOUNT_MASTER'];

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
        //$_SESSION['MIGRATION_DB_NAME'] = $_POST['DATABASE_NAME'];
        //require_once('upload_functions.php');

        // Open uploaded CSV file with read-only mode
        $csvFile = fopen($_FILES['file']['tmp_name'], 'r');
        $lineNumber = 1;

        $PK_LOCATION = $_POST['PK_LOCATION'];

        // Parse data from CSV file line by line
        while (($getData = fgetcsv($csvFile, 10000, ",")) !== FALSE)
        {
            if ($lineNumber === 1) { $lineNumber++; continue; }
            //CUSTOMER SECTION
            $INSERT_DATA['PK_ACCOUNT_MASTER'] = $PK_ACCOUNT_MASTER;
            $INSERT_DATA['FIRST_NAME'] = trim($getData[0]);
            $INSERT_DATA['LAST_NAME'] = trim($getData[1]);
            $INSERT_DATA['EMAIL_ID'] = $getData[2];
            $INSERT_DATA['PHONE'] = $getData[3];
            $INSERT_DATA['ACTIVE'] = 1;
            $INSERT_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
            $INSERT_DATA['CREATED_ON'] = date("Y-m-d H:i");
            db_perform('DOA_USERS', $INSERT_DATA, 'insert');
            $PK_USER = $db->insert_ID();

            if ($PK_USER) {
                $USER_ROLE_DATA['PK_USER'] = $PK_USER;
                $USER_ROLE_DATA['PK_ROLES'] = 4;
                db_perform('DOA_USER_ROLES', $USER_ROLE_DATA, 'insert');

                $USER_DATA_ACCOUNT['PK_USER_MASTER_DB'] = $PK_USER;
                $USER_DATA_ACCOUNT['PK_ACCOUNT_MASTER'] = $PK_ACCOUNT_MASTER;
                $INSERT_DATA['FIRST_NAME'] = trim($getData[0]);
                $INSERT_DATA['LAST_NAME'] = trim($getData[1]);
                $INSERT_DATA['EMAIL_ID'] = $getData[2];
                $INSERT_DATA['PHONE'] = $getData[3];
                $USER_DATA_ACCOUNT['CREATED_BY'] = $_SESSION['PK_USER'];
                $USER_DATA_ACCOUNT['CREATED_ON'] = date("Y-m-d H:i");
                db_perform_account('DOA_USERS', $USER_DATA_ACCOUNT, 'insert');

                $USER_LOCATION_DATA['PK_USER'] = $PK_USER;
                $USER_LOCATION_DATA['PK_LOCATION'] = $PK_LOCATION;
                db_perform('DOA_USER_LOCATION', $USER_LOCATION_DATA, 'insert');

                $USER_MASTER_DATA['PK_USER'] = $PK_USER;
                $USER_MASTER_DATA['PK_ACCOUNT_MASTER'] = $PK_ACCOUNT_MASTER;
                $USER_MASTER_DATA['PRIMARY_LOCATION_ID'] = $PK_LOCATION;
                db_perform('DOA_USER_MASTER', $USER_MASTER_DATA, 'insert');
                $PK_USER_MASTER = $db->insert_ID();
            }

            if ($PK_USER_MASTER) {
                $CUSTOMER_DATA['PK_USER_MASTER'] = $PK_USER_MASTER;
                $CUSTOMER_DATA['FIRST_NAME'] = trim($getData[0]);
                $CUSTOMER_DATA['LAST_NAME'] = trim($getData[1]);
                $CUSTOMER_DATA['EMAIL_ID'] = $getData[2];
                $CUSTOMER_DATA['PHONE'] = $getData[3];
                db_perform_account('DOA_CUSTOMER_DETAILS', $CUSTOMER_DATA, 'insert');
                $PK_CUSTOMER_DETAILS = $db_account->insert_ID();

                if (!empty($getData[3]) && $getData[3] != "   -   -    *") {
                    $PHONE_DATA['PK_CUSTOMER_DETAILS'] = $PK_CUSTOMER_DETAILS;
                    $PHONE_DATA['PHONE'] = $getData[3];
                    db_perform_account('DOA_CUSTOMER_PHONE', $PHONE_DATA, 'insert');
                }
            }

            //ENROLLMENT SECTION

            //$ENROLLMENT_DATA['PK_ENROLLMENT_TYPE'] = 0;

            $account_data = $db->Execute("SELECT ENROLLMENT_ID_CHAR, ENROLLMENT_ID_NUM FROM `DOA_ACCOUNT_MASTER` WHERE `PK_ACCOUNT_MASTER` = '$_POST[PK_ACCOUNT_MASTER]'");
            if ($account_data->RecordCount() > 0){
                $enrollment_char = $account_data->fields['ENROLLMENT_ID_CHAR'];
            } else {
                $enrollment_char = 'ENR';
            }
            $enrollment_data = $db->Execute("SELECT ENROLLMENT_ID FROM `DOA_ENROLLMENT_MASTER` WHERE `PK_ACCOUNT_MASTER` = '$_POST[PK_ACCOUNT_MASTER]' ORDER BY PK_ENROLLMENT_MASTER DESC LIMIT 1");
            if ($enrollment_data->RecordCount() > 0){
                $last_enrollment_id = str_replace($enrollment_char, '', $enrollment_data->fields['ENROLLMENT_ID']) ;
                $ENROLLMENT_DATA['ENROLLMENT_ID'] = $enrollment_char.(intval($last_enrollment_id)+1);
            }else{
                $ENROLLMENT_DATA['ENROLLMENT_ID'] = $enrollment_char.$account_data->fields['ENROLLMENT_ID_NUM'];
            }

            $ENROLLMENT_DATA['PK_ACCOUNT_MASTER'] = $_POST['PK_ACCOUNT_MASTER'];
            $ENROLLMENT_DATA['PK_USER_MASTER'] = $PK_USER_MASTER;
            $ENROLLMENT_DATA['PK_LOCATION'] = $PK_LOCATION;
            $ENROLLMENT_DATA['ENROLLMENT_BY_ID'] = $PK_ACCOUNT_MASTER;
            $ENROLLMENT_DATA['ACTIVE'] = 1;
            $ENROLLMENT_DATA['STATUS'] = "A";
            //$ENROLLMENT_DATA['EXPIRY_DATE'] = $getData[22];
            $ENROLLMENT_DATA['CREATED_BY'] = $PK_ACCOUNT_MASTER;
            $ENROLLMENT_DATA['CREATED_ON'] = date("Y-m-d H:i");
            db_perform_account('DOA_ENROLLMENT_MASTER', $ENROLLMENT_DATA, 'insert');
            $PK_ENROLLMENT_MASTER = $db_account->insert_ID();

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

            $TOTAL_LESSONS = $getData[4] + $getData[5] + $getData[6];
            $TOTAL_COST = $getData[8];
            $PRICE_PER_SESSION = $TOTAL_COST/$TOTAL_LESSONS;
            if($getData[4] > 0) {
                $SERVICE_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
                $SERVICE_DATA['PK_SERVICE_MASTER'] = 12;
                $SERVICE_DATA['PK_SERVICE_CODE'] =  12;
                //$SERVICE_DATA['PK_SCHEDULING_CODE'] =  $PK_SCHEDULING_CODE;
                $SERVICE_DATA['FREQUENCY'] =  0;
                $service_details = $db_account->Execute("SELECT DESCRIPTION FROM DOA_SERVICE_MASTER WHERE PK_SERVICE_MASTER = 12");
                $SERVICE_DATA['SERVICE_DETAILS'] = $service_details->fields['DESCRIPTION'];
                $SERVICE_DATA['NUMBER_OF_SESSION'] = $getData[4];
                $SERVICE_DATA['PRICE_PER_SESSION'] = $PRICE_PER_SESSION;
                $SERVICE_DATA['TOTAL'] = $TOTAL_COST/$getData[4];
                $SERVICE_DATA['DISCOUNT'] = 0;
                $SERVICE_DATA['FINAL_AMOUNT'] = $TOTAL_COST/$getData[4];
                db_perform_account('DOA_ENROLLMENT_SERVICE', $SERVICE_DATA, 'insert');
            }

            if($getData[5] > 0) {
                $SERVICE_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
                $SERVICE_DATA['PK_SERVICE_MASTER'] = 4;
                $SERVICE_DATA['PK_SERVICE_CODE'] =  4;
                //$SERVICE_DATA['PK_SCHEDULING_CODE'] =  $PK_SCHEDULING_CODE;
                $SERVICE_DATA['FREQUENCY'] =  0;
                $service_details = $db_account->Execute("SELECT DESCRIPTION FROM DOA_SERVICE_MASTER WHERE PK_SERVICE_MASTER = 4");
                $SERVICE_DATA['SERVICE_DETAILS'] = $service_details->fields['DESCRIPTION'];
                $SERVICE_DATA['NUMBER_OF_SESSION'] = $getData[5];
                $SERVICE_DATA['PRICE_PER_SESSION'] = $PRICE_PER_SESSION;
                $SERVICE_DATA['TOTAL'] = $TOTAL_COST/$getData[5];
                $SERVICE_DATA['DISCOUNT'] = 0;
                $SERVICE_DATA['FINAL_AMOUNT'] = $TOTAL_COST/$getData[5];
                db_perform_account('DOA_ENROLLMENT_SERVICE', $SERVICE_DATA, 'insert');
            }

            if($getData[6] > 0) {
                $SERVICE_DATA['PK_ENROLLMENT_MASTER'] = $PK_ENROLLMENT_MASTER;
                $SERVICE_DATA['PK_SERVICE_MASTER'] = 16;
                $SERVICE_DATA['PK_SERVICE_CODE'] =  16;
                //$SERVICE_DATA['PK_SCHEDULING_CODE'] =  $PK_SCHEDULING_CODE;
                $SERVICE_DATA['FREQUENCY'] =  0;
                $service_details = $db_account->Execute("SELECT DESCRIPTION FROM DOA_SERVICE_MASTER WHERE PK_SERVICE_MASTER = 16");
                $SERVICE_DATA['SERVICE_DETAILS'] = $service_details->fields['DESCRIPTION'];
                $SERVICE_DATA['NUMBER_OF_SESSION'] = $getData[6];
                $SERVICE_DATA['PRICE_PER_SESSION'] = $PRICE_PER_SESSION;
                $SERVICE_DATA['TOTAL'] = $TOTAL_COST/$getData[6];
                $SERVICE_DATA['DISCOUNT'] = 0;
                $SERVICE_DATA['FINAL_AMOUNT'] = $TOTAL_COST/$getData[6];
                db_perform_account('DOA_ENROLLMENT_SERVICE', $SERVICE_DATA, 'insert');
            }

            $lineNumber++;
        }
        // Close opened CSV file
        fclose($csvFile);
        //header("Location: csv_uploader.php");
    }
    else
    {
        echo "Please select valid file";
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
        <?php require_once('../includes/setup_menu.php') ?>
        <div class="container-fluid body_content m-0">
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
                    <div class="col-md-2">
                        <div class="form-group">
                            <label class="form-label">Select Location</label>
                            <select class="form-control" name="PK_LOCATION" id="PK_LOCATION">
                                <option value="">Select Location</option>
                                <?php
                                $row = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME FROM DOA_LOCATION WHERE ACTIVE = 1 AND PK_ACCOUNT_MASTER = ".$_SESSION['PK_ACCOUNT_MASTER']);
                                while (!$row->EOF) { ?>
                                    <option value="<?php echo $row->fields['PK_LOCATION'];?>"><?=$row->fields['LOCATION_NAME']?></option>
                                    <?php $row->MoveNext(); } ?>
                            </select>
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
<?php require_once('../includes/footer.php');?>
</body>
<script>
    function viewCsvDownload(param) {
        let table_name = $(param).val();
        $('#view_download_div').html(`<a href="../uploads/csv_upload/${table_name}.csv" target="_blank">View Sample</a>`);
    }
</script>
</html>
