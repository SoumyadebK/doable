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
                    $table_data = $db->Execute("SELECT * FROM DOA_USERS WHERE USER_ID='$getData[0]' AND PK_ACCOUNT_MASTER='$_POST[PK_ACCOUNT_MASTER]'");
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
                        $INSERT_DATA['HOME_PHONE'] = $getData[11];
                        $INSERT_DATA['PHONE'] = $getData[12];
                        $INSERT_DATA['PASSWORD'] = $getData[19];
                        $INSERT_DATA['USER_IMAGE'] = $getData[7];
                        $INSERT_DATA['ACTIVE'] = 1;
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
                            $USER_DATA['NOTES'] = $getData[16];
                            $USER_DATA['ACTIVE'] = 1;
                            $USER_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
                            $USER_DATA['CREATED_ON'] = date("Y-m-d H:i");
                            db_perform('DOA_USER_PROFILE', $USER_DATA, 'insert');
                        }

                    }
                    break;

                case 'DOA_CUSTOMER':
                    $table_data = $db->Execute("SELECT * FROM DOA_USERS WHERE USER_ID='$getData[0]' AND PK_ACCOUNT_MASTER='$_POST[PK_ACCOUNT_MASTER]'");
                    if ($table_data->RecordCount() == 0) {
                        $INSERT_DATA['PK_ACCOUNT_MASTER'] = $_POST['PK_ACCOUNT_MASTER'];
                        $INSERT_DATA['PK_ROLES'] = 4;
                        $INSERT_DATA['FIRST_NAME'] = $getData[1];
                        $INSERT_DATA['LAST_NAME'] = $getData[2];
                        $INSERT_DATA['USER_API_KEY'] = $getData[0];
                        $INSERT_DATA['EMAIL_ID'] = $getData[24];
                        $INSERT_DATA['HOME_PHONE'] = $getData[18];
                        $INSERT_DATA['PHONE'] = $getData[20];
                        $INSERT_DATA['ACTIVE'] = 1;
                        $INSERT_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
                        $INSERT_DATA['CREATED_ON'] = date("Y-m-d H:i");
                        db_perform('DOA_USERS', $INSERT_DATA, 'insert');

                        $PK_USER = $db->insert_ID();
                        if ($PK_USER) {
                            $USER_DATA['PK_USER'] = $PK_USER;
                            $USER_DATA['GENDER'] = $getData[7];
                            $USER_DATA['DOB'] = date("Y-m-d", strtotime($getData[5]));
                            $USER_DATA['ADDRESS'] = $getData[13];
                            $USER_DATA['ADDRESS_1'] = $getData[14];
                            $USER_DATA['CITY'] = $getData[15];
                            $USER_DATA['PK_STATES'] = $getData[16];
                            $USER_DATA['ZIP'] = $getData[17];
                            $USER_DATA['NOTES'] = $getData[15];
                            $USER_DATA['ACTIVE'] = 1;
                            $USER_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
                            $USER_DATA['CREATED_ON'] = date("Y-m-d H:i");
                            db_perform('DOA_USER_PROFILE', $USER_DATA, 'insert');

                            $USER_MASTER_DATA['PK_USER'] = $PK_USER;
                            $USER_MASTER_DATA['PK_ACCOUNT_MASTER'] = $_POST['PK_ACCOUNT_MASTER'];
                            db_perform('DOA_USER_MASTER', $USER_MASTER_DATA, 'insert');

                            $PK_USER_MASTER = $db->insert_ID();
                            if($PK_USER_MASTER){
                                $CUSTOMER_DATA['PK_USER_MASTER'] = $PK_USER_MASTER;
                                $CUSTOMER_DATA['FIRST_NAME'] = $getData[1];
                                $CUSTOMER_DATA['LAST_NAME'] = $getData[2];
                                $CUSTOMER_DATA['EMAIL'] = $getData[24];
                                $CUSTOMER_DATA['PHONE'] = $getData[20];
                                $CUSTOMER_DATA['DOB'] = date("Y-m-d", strtotime($getData[5]));
                                $CUSTOMER_DATA['CALL_PREFERENCE'] = $getData[22];
                                $CUSTOMER_DATA['REMINDER_OPTION'] = $getData[23];
                                $CUSTOMER_DATA['PARTNER_FIRST_NAME'] = $getData[25];
                                $CUSTOMER_DATA['PARTNER_GENDER'] = $getData[26];
                                $CUSTOMER_DATA['PARTNER_DOB'] = date("Y-m-d", strtotime($getData[6]));
                                $CUSTOMER_DATA['IS_PRIMARY'] = 1;
                                db_perform('DOA_CUSTOMER_DETAILS', $CUSTOMER_DATA, 'insert');

                                $PK_CUSTOMER_DETAILS = $db->insert_ID();
                                $SPECIAL_DATA['PK_CUSTOMER_DETAILS'] = $PK_CUSTOMER_DETAILS;
                                $SPECIAL_DATA['SPECIAL_DATE'] =  date("Y-m-d", strtotime($getData[9]));
                                $SPECIAL_DATA['DATE_NAME'] = $getData[11];
                                db_perform('DOA_SPECIAL_DATE', $SPECIAL_DATA, 'insert');
                                $SPECIAL_DATA_1['PK_CUSTOMER_DETAILS'] = $PK_CUSTOMER_DETAILS;
                                $SPECIAL_DATA_1['SPECIAL_DATE'] =  date("Y-m-d", strtotime($getData[10]));
                                $SPECIAL_DATA_1['DATE_NAME'] = $getData[12];
                                db_perform('DOA_SPECIAL_DATE', $SPECIAL_DATA_1, 'insert');
                            }
                        }



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