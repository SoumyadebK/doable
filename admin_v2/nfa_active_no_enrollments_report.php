<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;

$title = "NFA ACTIVE NO ENROLLMENTS REPORT";

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
                                                <th style="width:50%; text-align: center; vertical-align:auto; font-weight: bold" colspan="7"><?= ($account_data->fields['FRANCHISE'] == 1) ? 'Franchisee: ' : '' ?><?= " (" . $concatenatedResults . ")" ?></th>
                                            </tr>
                                            <tr>
                                                <th style="width:10%; text-align: left">Student</th>
                                                <th style="width:10%; text-align: center">Phone</th>
                                                <th style="width:10%; text-align: center">Email</th>
                                                <th style="width:10%; text-align: center">Address</th>
                                                <th style="width:10%; text-align: center">Date of the Last Private Appointment</th>
                                                <th style="width:10%; text-align: center">Total Days Since the Last One</th>
                                                <th style="width:10%; text-align: center">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $i = 1;
                                            $total_amount = 0;

                                            // Build the service provider filter condition
                                            $service_provider_filter = "";
                                            if (!empty($service_provider_id)) {
                                                $service_provider_filter = "AND DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER IN (
                                                        SELECT DISTINCT PK_ENROLLMENT_MASTER 
                                                        FROM DOA_ENROLLMENT_SERVICE_PROVIDER 
                                                        WHERE SERVICE_PROVIDER_ID IN (" . $service_provider_id . ")
                                                    )";
                                            }

                                            $row = $db_account->Execute("
                                                SELECT
                                                    CONCAT(
                                                        DOA_USERS.FIRST_NAME,
                                                        ' ',
                                                        DOA_USERS.LAST_NAME
                                                    ) AS CLIENT,
                                                    DOA_USERS.USER_ID,
                                                    DOA_USERS.PK_USER,
                                                    DOA_USERS.PHONE,
                                                    DOA_USERS.EMAIL_ID,
                                                    DOA_USERS.ADDRESS,
                                                    'No Active Enrollment / No Future Appointment' AS STATUS,
                                                    
                                                    (
                                                        SELECT am.DATE 
                                                        FROM DOA_APPOINTMENT_MASTER am
                                                        INNER JOIN DOA_ENROLLMENT_MASTER em ON am.PK_ENROLLMENT_MASTER = em.PK_ENROLLMENT_MASTER
                                                        INNER JOIN DOA_SERVICE_CODE sc ON am.PK_SERVICE_CODE = sc.PK_SERVICE_CODE
                                                        WHERE em.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER
                                                        AND am.DATE <= CURDATE()
                                                        AND sc.IS_GROUP = 0 
                                                        AND am.PK_APPOINTMENT_STATUS = 2  
                                                        ORDER BY am.DATE DESC
                                                        LIMIT 1
                                                    ) AS LAST_PRIVATE_APPOINTMENT_DATE
                                                FROM
                                                    $master_database.DOA_USERS AS DOA_USERS
                                                INNER JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER
                                                    ON DOA_USER_MASTER.PK_USER = DOA_USERS.PK_USER
                                                INNER JOIN $master_database.DOA_USER_LOCATION AS DOA_USER_LOCATION
                                                    ON DOA_USER_LOCATION.PK_USER = DOA_USERS.PK_USER    
                                                LEFT JOIN DOA_ENROLLMENT_MASTER 
                                                    ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER
                                                    AND DOA_ENROLLMENT_MASTER.STATUS = 'A'
                                                LEFT JOIN DOA_APPOINTMENT_MASTER 
                                                    ON DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER
                                                    AND DOA_APPOINTMENT_MASTER.DATE >= CURDATE()
                                                WHERE
                                                    DOA_USERS.IS_DELETED = 0 
                                                    AND DOA_USERS.ACTIVE = 1 
                                                    AND DOA_USER_LOCATION.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ")
                                                    AND DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER IS NULL 
                                                    AND DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER IS NULL
                                                ORDER BY CLIENT ASC
                                            ");

                                            while (!$row->EOF) {
                                                $last_appointment_date = $row->fields['LAST_PRIVATE_APPOINTMENT_DATE'];
                                                $days_since_last = '';

                                                if (!empty($last_appointment_date)) {
                                                    $last_date = new DateTime($last_appointment_date);
                                                    $today_date = new DateTime();
                                                    $interval = $today_date->diff($last_date);
                                                    $days_since_last = $interval->days;
                                                }

                                                // Format the date for display
                                                $formatted_date = !empty($last_appointment_date) ? date('m/d/Y', strtotime($last_appointment_date)) : 'No Previous Appointment';
                                            ?>
                                                <tr>
                                                    <td style="text-align: left"><?= $row->fields['CLIENT'] ?></td>
                                                    <td style="text-align: center"><?= $row->fields['PHONE'] ?></td>
                                                    <td style="text-align: center"><?= $row->fields['EMAIL_ID'] ?></td>
                                                    <td style="text-align: center"><?= $row->fields['ADDRESS'] ?></td>
                                                    <td style="text-align: center"><?= $formatted_date ?></td>
                                                    <td style="text-align: center"><?= !empty($days_since_last) ? $days_since_last . ' days' : 'N/A' ?></td>
                                                    <td style="text-align: center"><?= $row->fields['STATUS'] ?></td>
                                                </tr>
                                            <?php
                                                $row->MoveNext();
                                                $i++;
                                            }
                                            ?>
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