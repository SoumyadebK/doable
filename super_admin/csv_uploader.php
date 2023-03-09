<?php
$conn = require_once('../global/config.php');
$title = "Upload CSV";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 1 ){
    header("location:../login.php");
    exit;
}

if (isset($_POST['submit']))
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
        // Parse data from CSV file line by line
        while (($getData = fgetcsv($csvFile, 10000, ",")) !== FALSE)
        {
            if($_POST['TABLE_NAME'] == 'DOA_INQUIRY_METHOD') {
                // Get row data
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
            }
            else if($_POST['TABLE_NAME'] == 'DOA_EVENT_TYPE') {
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
            }
            else if($_POST['TABLE_NAME'] == 'DOA_HOLIDAY_LIST') {
                $table_data = $db->Execute("SELECT * FROM DOA_HOLIDAY_LIST WHERE HOLIDAY_NAME='$getData[1]' AND PK_ACCOUNT_MASTER='$_POST[PK_ACCOUNT_MASTER]'");
                if ($table_data->RecordCount() == 0) {
                    $INSERT_DATA['PK_ACCOUNT_MASTER'] = $_POST['PK_ACCOUNT_MASTER'];
                    $INSERT_DATA['HOLIDAY_DATE'] = $getData[0];
                    $INSERT_DATA['HOLIDAY_NAME'] = $getData[1];
                    db_perform('DOA_HOLIDAY_LIST', $INSERT_DATA, 'insert');
                }
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
                        <?php if($_SESSION['PK_ROLES'] == 1) { ?>
                            <button type="button" class="btn btn-info d-none d-lg-block m-l-15 text-white" onclick="window.location.href='add_user.php'" ><i class="fa fa-plus-circle"></i> Create New</button>
                        <?php } ?>
                    </div>
                </div>
            </div>
            <div class="row">
                <form class="row" action="" method="post" enctype="multipart/form-data">
                    <div class="col-md-4">
                        <div class="form-group">
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
                        <select class="form-control" name="TABLE_NAME" id="TABLE_NAME">
                            <option value="">Select Table Name</option>
                            <option value="DOA_INQUIRY_METHOD">DOA_INQUIRY_METHOD</option>
                            <option value="DOA_EVENT_TYPE">DOA_EVENT_TYPE</option>
                            <option value="DOA_HOLIDAY_LIST">DOA_HOLIDAY_LIST</option>
                        </select>
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
                    <div class="input-group">
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="customFileInput" aria-describedby="customFileInput" name="file">
<!--                            <label class="custom-file-label" for="customFileInput"><i class="ti-folder"></i> Select file</label>-->
                        </div>
                        <div class="input-group-append">
                            <button  type="submit" name="submit" value="Upload" class="btn btn-info d-none d-lg-block m-l-15 text-white"><i class="ti-export"></i> Upload</button>
                        </div>
                    </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require_once('../includes/footer.php');?>
<script>
    $(function () {
        $('#myTable').DataTable();
    });
    function ConfirmDelete(anchor)
    {
        var conf = confirm("Are you sure you want to delete?");
        if(conf)
            window.location=anchor.attr("href");
    }
    function editpage(PK_USER){
        //alert(i);
        window.location.href = "add_user.php?id="+PK_USER;
    }
</script>
</body>
</html>