<?php
require_once('../global/config.php');
$title = "Accounts";

$user_master_data = $account = $db->Execute("SELECT * FROM DOA_USER_MASTER WHERE PK_USER = ".$_SESSION['PK_USER']);
$PK_USER_MASTER_ARRAY = [];
while (!$user_master_data->EOF){
    $PK_USER_MASTER_ARRAY[] = $user_master_data->fields['PK_USER_MASTER'];
    $user_master_data->MoveNext();
}
$PK_USER_MASTERS = implode(',', $PK_USER_MASTER_ARRAY);

$results_per_page = 100;

if (isset($_GET['search_text']) && $_GET['search_text'] != '') {
    $search_text = $_GET['search_text'];
    $search = " AND DOA_USERS.FIRST_NAME LIKE '%".$search_text."%' OR DOA_USERS.EMAIL_ID LIKE '%".$search_text."%' OR DOA_USERS.PHONE LIKE '%".$search_text."%'";
} else {
    $search_text = '';
    $search = ' ';
}

$query = $db->Execute("SELECT count(DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS TOTAL_RECORDS FROM `DOA_ENROLLMENT_MASTER` INNER JOIN DOA_LOCATION ON DOA_LOCATION.PK_LOCATION = DOA_ENROLLMENT_MASTER.PK_LOCATION  WHERE DOA_ENROLLMENT_MASTER.PK_USER_MASTER IN (".$PK_USER_MASTERS.")".$search);
$number_of_result =  $query->fields['TOTAL_RECORDS'];
$number_of_page = ceil ($number_of_result / $results_per_page);

if (!isset ($_GET['page']) ) {
    $page = 1;
} else {
    $page = $_GET['page'];
}
$page_first_result = ($page-1) * $results_per_page;

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 4){
    header("location:../login.php");
    exit;
}


?>

<!DOCTYPE html>
<html lang="en">
<?php require_once('../includes/header.php');?>
<style>
    .menu-list{
        list-style-type: none;
        margin-left: -30px;
    }

    .menu-list li{
        margin: 10px;
    }
</style>
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
                            <li class="breadcrumb-item active"><?=$title?></li>
                        </ol>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="row" style="padding: 15px 35px 35px 35px;">
                            <div class="col-md-3 col-sm-3 mt-3">
                                <!--<h4 class="card-title">General</h4>-->
                                <div>
                                    <ul class="menu-list">
                                        <li><a href="all_gift_certificates.php">Gift Certificate</a></li>
                                    </ul>
                                </div>
                            </div>
                            <!--<div class="col-md-3 col-sm-3 mt-3">
                                <h4 class="card-title">Communications</h4>
                                <div>
                                    <ul class="menu-list">
                                        <li><a href="all_email_accounts.php">Email Accounts</a></li>
                                    </ul>
                                </div>
                            </div>-->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once('../includes/footer.php');?>
</body>
</html>