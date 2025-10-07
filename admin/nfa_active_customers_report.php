<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;

$title = "NFA ACTIVE CUSTOMERS REPORT";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

$today = date('Y-m-d');

$account_data = $db->Execute("SELECT * FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
$user_data = $db->Execute("SELECT * FROM DOA_USERS WHERE PK_USER = '$_SESSION[PK_USER]'");
$business_name = $account_data->RecordCount() > 0 ? $account_data->fields['BUSINESS_NAME'] : '';
if (preg_match("/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/", $business_name)) {
    $business_name = '';
} else {
    $business_name = '' . $business_name;
}

$location_name = '';
$results = $db->Execute("SELECT PK_LOCATION, LOCATION_NAME FROM DOA_LOCATION WHERE PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND ACTIVE = 1 AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
$resultsArray = [];
while (!$results->EOF) {
    $resultsArray[] = $results->fields['LOCATION_NAME'];
    $results->MoveNext();
}
$totalResults = count($resultsArray);
$concatenatedResults = "";
foreach ($resultsArray as $key => $result) {
    // Append the current result to the concatenated string
    $concatenatedResults .= $result;

    // If it's not the last result, append a comma
    if ($key < $totalResults - 1) {
        $concatenatedResults .= ", ";
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
            <div class="container-fluid body_content">
                <div class="row page-titles">
                    <div class="col-md-5 align-self-center">
                        <h4 class="text-themecolor"><?= $title ?></h4>
                    </div>
                    <div class="col-md-7 align-self-center text-end">
                        <div class="d-flex justify-content-end align-items-center">
                            <ol class="breadcrumb justify-content-end">
                                <li class="breadcrumb-item active"><a href="reports.php">Reports</a></li>
                                <li class="breadcrumb-item active"><a href="customer_summary_report.php"><?= $title ?></a></li>
                            </ol>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div>
                                    <img src="../assets/images/background/doable_logo.png" style="margin-bottom:-35px; height: 60px; width: auto;">
                                    <h3 class="card-title" style="padding-bottom:15px; text-align: center; font-weight: bold"><?= $title ?></h3>
                                </div>

                                <div class="table-responsive">
                                    <table id="myTable" class="table table-bordered" data-page-length='50'>
                                        <thead>
                                            <tr>
                                                <th style="width:50%; text-align: center; vertical-align:auto; font-weight: bold" colspan="6"><?= ($account_data->fields['FRANCHISE'] == 1) ? 'Franchisee: ' : '' ?><?= $business_name . " (" . $concatenatedResults . ")" ?></th>
                                            </tr>
                                            <tr>
                                                <th style="text-align: center;">Customer Name</th>
                                                <th style="text-align: center;">Enrollment Name / Number</th>
                                                <th style="text-align: center;">Total</th>
                                                <th style="text-align: center;">Session Left</th>
                                                <th style="text-align: center;">Service Provider</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $i = 1;

                                            $row = $db_account->Execute("SELECT 
                                                                            DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_SERVICE,
                                                                            DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION,
                                                                            CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS CUSTOMER_NAME,
                                                                            DOA_ENROLLMENT_MASTER.ENROLLMENT_NAME,
                                                                            DOA_ENROLLMENT_MASTER.ENROLLMENT_ID,
                                                                            GROUP_CONCAT(CONCAT(SP.FIRST_NAME, ' ', SP.LAST_NAME) SEPARATOR ', ') AS SERVICE_PROVIDER_NAMES
                                                                        FROM DOA_ENROLLMENT_SERVICE 
                                                                        LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER 
                                                                        JOIN DOA_SERVICE_CODE ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE 
                                                                        JOIN DOA_SERVICE_MASTER ON DOA_ENROLLMENT_SERVICE.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER 
                                                                        JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER
                                                                        JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USER_MASTER.PK_USER = DOA_USERS.PK_USER                                                                            
                                                                        LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ESP ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = ESP.PK_ENROLLMENT_MASTER
                                                                        LEFT JOIN DOA_USERS SP ON ESP.SERVICE_PROVIDER_ID = SP.PK_USER
                                                                        WHERE 
                                                                            DOA_ENROLLMENT_MASTER.STATUS = 'A'  AND DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ")
                                                                            AND DOA_SERVICE_CODE.IS_GROUP = 0  
                                                                            AND DOA_USERS.ACTIVE = 1 AND DOA_USERS.IS_DELETED = 0
                                                                            AND DOA_SERVICE_MASTER.PK_SERVICE_CLASS != 5 
                                                                        GROUP BY 
                                                                            DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER,
                                                                            CUSTOMER_NAME,
                                                                            DOA_ENROLLMENT_MASTER.ENROLLMENT_NAME
                                                                        ORDER BY CUSTOMER_NAME");
                                            while (!$row->EOF) {
                                                $NUMBER_OF_SESSION = getSessionCreatedCount($row->fields['PK_ENROLLMENT_SERVICE']);

                                                if ($row->fields['NUMBER_OF_SESSION'] > $NUMBER_OF_SESSION) {
                                            ?>
                                                    <tr>
                                                        <td style="text-align: center;"><?= $row->fields['CUSTOMER_NAME'] ?></td>
                                                        <td style="text-align: center;"><?= $row->fields['ENROLLMENT_NAME'] . " / " . $row->fields['ENROLLMENT_ID'] ?></td>
                                                        <td style="text-align: center;"><?= $row->fields['NUMBER_OF_SESSION'] ?></td>
                                                        <td style="text-align: center;"><?= $row->fields['NUMBER_OF_SESSION'] - $NUMBER_OF_SESSION ?></td>
                                                        <td style="text-align: center;"><?= $row->fields['SERVICE_PROVIDER_NAMES'] ?></td>
                                                    </tr>
                                            <?php
                                                }
                                                $row->MoveNext();
                                                $i++;
                                            } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php require_once('../includes/footer.php'); ?>
</body>

</html>