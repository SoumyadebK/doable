<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;

$title = "LESSONS TAUGHT BY DEPARTMENT";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

$type = $_GET['type'];

$service_provider_id = $_GET['service_provider_id'];

$from_date = date('Y-m-d', strtotime($_GET['start_date']));
$to_date = date('Y-m-d', strtotime($_GET['end_date']));
$service_provider_id = $_GET['service_provider_id'];

$payment_date = "AND DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (" . $service_provider_id . ") AND DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE BETWEEN '" . date('Y-m-d', strtotime($from_date)) . "' AND '" . date('Y-m-d', strtotime($to_date)) . "' GROUP BY SERVICE_PROVIDER_ID ORDER BY DOA_ENROLLMENT_PAYMENT.PAYMENT_DATE DESC";

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
                                <li class="breadcrumb-item active"><a href="cash_report.php">Reports</a></li>
                                <li class="breadcrumb-item active"><?= $title ?></a></li>
                            </ol>
                        </div>
                    </div>
                </div>

                <?php
                if ($type === 'export') {
                    echo "<h3>Data export to Arthur Murray API Successfully</h3>";
                    /*$data = json_decode($post_data);
                if (isset($data->error)) {
                    echo '<div class="alert alert-danger alert-dismissible" role="alert">'.$data->error_description.'</div>';
                } elseif (isset($data->errors)) {
                    if (isset($data->errors->errors[0])) {
                        echo '<div class="alert alert-danger alert-dismissible" role="alert">' . $data->errors->errors[0] . '</div>';
                    } else {
                        echo '<div class="alert alert-danger alert-dismissible" role="alert">'.$data->message.'</div>';
                    }
                } else {
                    echo "<h3>Data export to Arthur Murray API Successfully</h3>";
                }*/
                } else { ?>
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row" style="margin-bottom: 20px;">
                                        <div class="col-md-3 text-left">
                                            <img src="../assets/images/background/doable_logo.png" style="margin-bottom:-35px; height: 60px; width: auto;">
                                        </div>
                                        <div class="col-md-6 text-center">
                                            <h3 class="card-title" style="padding-bottom:15px; text-align: center; font-weight: bold"><?= $title ?></h3>
                                        </div>
                                        <div class="col-md-3 text-center">
                                            <h6 class="card-title" style="padding-bottom:15px; text-align: center; font-weight: bold">(<?= date('m/d/Y', strtotime($from_date)) ?> - <?= date('m/d/Y', strtotime($to_date)) ?>)</h6>
                                        </div>
                                    </div>

                                    <div class="table-responsive">
                                        <table id="myTable" class="table table-bordered" data-page-length='50'>
                                            <thead>
                                                <tr>
                                                    <th style="width:50%; text-align: center; vertical-align:auto; font-weight: bold" colspan="7"><?= ($account_data->fields['FRANCHISE'] == 1) ? 'Franchisee: ' : '' ?><?= $business_name . " (" . $concatenatedResults . ")" ?></th>
                                                </tr>
                                                <tr>
                                                    <th style="text-align: center;">Service Provider</th>
                                                    <th style="text-align: center;">Total Private Services</th>
                                                    <th style="text-align: center;">Front End Services</th>
                                                    <th style="text-align: center;">Back End Services</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $i = 1;

                                                $row = $db_account->Execute("SELECT
                                                                                CONCAT(u.FIRST_NAME, ' ', u.LAST_NAME) AS SERVICE_PROVIDER_NAME,
                                                                                COUNT(r.PK_APPOINTMENT_MASTER) AS TOTAL_PRIVATE_SERVICES,
                                                                                SUM(CASE WHEN r.lesson_number <= 12 THEN 1 ELSE 0 END) AS FRONT_END_SERVICES,
                                                                                SUM(CASE WHEN r.lesson_number > 12 THEN 1 ELSE 0 END) AS BACK_END_SERVICES,
                                                                                SUM(r.UNIT) AS TOTAL_UNITS_TAUGHT,
                                                                                MIN(r.DATE) AS FIRST_SERVICE_DATE,
                                                                                MAX(r.DATE) AS LAST_SERVICE_DATE
                                                                            FROM (
                                                                                SELECT
                                                                                    t.PK_APPOINTMENT_MASTER,
                                                                                    t.PK_USER_MASTER,
                                                                                    t.PK_SCHEDULING_CODE,
                                                                                    t.DATE,
                                                                                    t.PK_APPOINTMENT_STATUS,
                                                                                    t.UNIT,
                                                                                    t.PK_USER,
                                                                                    
                                                                                    (@rn := IF(@prev_user = t.PK_USER_MASTER, @rn + 1, 1)) AS lesson_number,
                                                                                    (@prev_user := t.PK_USER_MASTER) AS prev_marker
                                                                                FROM (
                                                                                    
                                                                                    SELECT
                                                                                        apm.PK_APPOINTMENT_MASTER,
                                                                                        ac.PK_USER_MASTER,
                                                                                        apm.PK_SCHEDULING_CODE,
                                                                                        apm.DATE,
                                                                                        apm.PK_APPOINTMENT_STATUS,
                                                                                        sc.UNIT,
                                                                                        asp.PK_USER
                                                                                    FROM DOA_APPOINTMENT_MASTER apm
                                                                                    JOIN DOA_APPOINTMENT_CUSTOMER ac
                                                                                        ON apm.PK_APPOINTMENT_MASTER = ac.PK_APPOINTMENT_MASTER
                                                                                    JOIN DOA_APPOINTMENT_SERVICE_PROVIDER asp
                                                                                        ON apm.PK_APPOINTMENT_MASTER = asp.PK_APPOINTMENT_MASTER
                                                                                    JOIN DOA_SCHEDULING_CODE sc
                                                                                        ON apm.PK_SCHEDULING_CODE = sc.PK_SCHEDULING_CODE
                                                                                    JOIN DOA_SERVICE_CODE svc
                                                                                        ON apm.PK_SERVICE_CODE = svc.PK_SERVICE_CODE
                                                                                    WHERE
                                                                                        svc.IS_GROUP = 0
                                                                                        AND apm.PK_APPOINTMENT_STATUS = 2 
                                                                                        AND apm.STATUS = 'A'
                                                                                        AND apm.DATE BETWEEN '$from_date' AND '$to_date'
                                                                                        AND asp.PK_USER IN ($service_provider_id)
                                                                                        AND apm.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ")
                                                                                    ORDER BY ac.PK_USER_MASTER, apm.DATE
                                                                                ) AS t
                                                                                CROSS JOIN (SELECT @rn := 0, @prev_user := NULL) AS vars_init
                                                                            ) AS r
                                                                            LEFT JOIN DOA_MASTER.DOA_USERS u ON r.PK_USER = u.PK_USER
                                                                            GROUP BY r.PK_USER
                                                                            ORDER BY TOTAL_PRIVATE_SERVICES DESC");
                                                while (!$row->EOF) { ?>
                                                    <tr>
                                                        <td style="text-align: center;"><?= $row->fields['SERVICE_PROVIDER_NAME'] ?></td>
                                                        <td style="text-align: center;"><?= $row->fields['TOTAL_PRIVATE_SERVICES'] ?></td>
                                                        <td style="text-align: center;"><?= $row->fields['FRONT_END_SERVICES'] ?></td>
                                                        <td style="text-align: center;"><?= $row->fields['BACK_END_SERVICES'] ?></td>
                                                    </tr>
                                                <?php $row->MoveNext();
                                                    $i++;
                                                } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
    <?php require_once('../includes/footer.php'); ?>
</body>

</html>