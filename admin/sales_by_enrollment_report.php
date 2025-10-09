<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;

$title = "SALES BY ENROLLMENT REPORT";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

$type = $_GET['type'];

$from_date = date('Y-m-d', strtotime($_GET['start_date']));
$to_date = date('Y-m-d', strtotime($_GET['end_date']));
$date_condition = "'" . date('Y-m-d', strtotime($from_date)) . "' AND '" . date('Y-m-d', strtotime($to_date)) . "'";

$service_provider_id = $_GET['service_provider_id'];

$payment_date = "AND DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID IN (" . $service_provider_id . ") GROUP BY SERVICE_PROVIDER_ID ORDER BY DOA_ENROLLMENT_MASTER.ENROLLMENT_DATE DESC";

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
                                        <div class="col-md-2 text-left">
                                            <img src="../assets/images/background/doable_logo.png" style="margin-bottom:-35px; height: 60px; width: auto;">
                                        </div>
                                        <div class="col-md-2 text-center">
                                            <h3 class="card-title" style="padding-bottom:15px; text-align: center; font-weight: bold"><?= $title ?></h3>
                                        </div>
                                        <div class="col-md-5 text-center">
                                            <h5 class="card-title" style="padding-bottom:15px; text-align: center; font-weight: bold"><?= $business_name . " (" . $concatenatedResults . ")" ?></h5>
                                        </div>
                                        <div class="col-md-3 text-center">
                                            <h6 class="card-title" style="padding-bottom:15px; text-align: center; font-weight: bold">(<?= date('m/d/Y', strtotime($from_date)) ?> - <?= date('m/d/Y', strtotime($to_date)) ?>)</h6>
                                        </div>
                                    </div>

                                    <?php
                                    $each_service_provider = $db_account->Execute("SELECT distinct DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID FROM DOA_ENROLLMENT_MASTER INNER JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER=DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") " . $payment_date);
                                    while (!$each_service_provider->EOF) {
                                        $name = $db->Execute("SELECT CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS TEACHER FROM DOA_USERS WHERE DOA_USERS.PK_USER = " . $each_service_provider->fields['SERVICE_PROVIDER_ID']);
                                        $service_provider_id_per_table = $each_service_provider->fields['SERVICE_PROVIDER_ID'];
                                    ?>

                                        <div class="table-responsive">
                                            <table id="myTable" class="table table-bordered" data-page-length='50'>
                                                <thead>
                                                    <tr>
                                                        <th style="width:50%; text-align: center; vertical-align:auto; font-weight: bold" colspan="3"><?= $name->fields['TEACHER'] ?></th>
                                                    </tr>
                                                    <tr>
                                                        <th style="width:8%; text-align: center">Enrollment Type</th>
                                                        <th style="width:8%; text-align: center">Total Enrollments</th>
                                                        <th style="width:8%; text-align: center">Total Units Sold</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    // Define the four enrollment types with their IDs and names
                                                    $enrollment_types = [
                                                        5 => 'Pre Original',
                                                        2 => 'Original',
                                                        9 => 'Extension',
                                                        13 => 'Renewal'
                                                    ];

                                                    // Pre Original
                                                    $pre_original_sold = $db_account->Execute("SELECT COUNT(DISTINCT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS SOLD FROM `DOA_ENROLLMENT_MASTER` LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID = $service_provider_id_per_table AND PK_ENROLLMENT_TYPE = 5 AND ENROLLMENT_DATE BETWEEN '$from_date' AND '$to_date'");
                                                    $pre_original_units = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION) AS UNITS FROM `DOA_ENROLLMENT_SERVICE` LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID = $service_provider_id_per_table AND PK_ENROLLMENT_TYPE = 5 AND ENROLLMENT_DATE BETWEEN '$from_date' AND '$to_date'");

                                                    // Original
                                                    $original_sold = $db_account->Execute("SELECT COUNT(DISTINCT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS SOLD FROM `DOA_ENROLLMENT_MASTER` LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID = $service_provider_id_per_table AND PK_ENROLLMENT_TYPE = 2 AND ENROLLMENT_DATE BETWEEN '$from_date' AND '$to_date'");
                                                    $original_units = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION) AS UNITS FROM `DOA_ENROLLMENT_SERVICE` LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID = $service_provider_id_per_table AND PK_ENROLLMENT_TYPE = 2 AND ENROLLMENT_DATE BETWEEN '$from_date' AND '$to_date'");

                                                    // Extension
                                                    $extension_sold = $db_account->Execute("SELECT COUNT(DISTINCT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS SOLD FROM `DOA_ENROLLMENT_MASTER` LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID = $service_provider_id_per_table AND PK_ENROLLMENT_TYPE = 9 AND ENROLLMENT_DATE BETWEEN '$from_date' AND '$to_date'");
                                                    $extension_units = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION) AS UNITS FROM `DOA_ENROLLMENT_SERVICE` LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID = $service_provider_id_per_table AND PK_ENROLLMENT_TYPE = 9 AND ENROLLMENT_DATE BETWEEN '$from_date' AND '$to_date'");

                                                    // Renewal
                                                    $renewal_sold = $db_account->Execute("SELECT COUNT(DISTINCT DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER) AS SOLD FROM `DOA_ENROLLMENT_MASTER` LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID = $service_provider_id_per_table AND PK_ENROLLMENT_TYPE = 13 AND ENROLLMENT_DATE BETWEEN '$from_date' AND '$to_date'");
                                                    $renewal_units = $db_account->Execute("SELECT SUM(DOA_ENROLLMENT_SERVICE.NUMBER_OF_SESSION) AS UNITS FROM `DOA_ENROLLMENT_SERVICE` LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_SERVICE.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER LEFT JOIN DOA_ENROLLMENT_SERVICE_PROVIDER ON DOA_ENROLLMENT_SERVICE_PROVIDER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER WHERE DOA_ENROLLMENT_SERVICE_PROVIDER.SERVICE_PROVIDER_ID = $service_provider_id_per_table AND PK_ENROLLMENT_TYPE = 13 AND ENROLLMENT_DATE BETWEEN '$from_date' AND '$to_date'");
                                                    ?>

                                                    <!-- Pre Original -->
                                                    <tr>
                                                        <td style="text-align: center">Pre Original</td>
                                                        <td style="text-align: center"><?= $pre_original_sold->fields['SOLD'] ?? 0 ?></td>
                                                        <td style="text-align: center"><?= $pre_original_units->fields['UNITS'] ?? 0 ?></td>
                                                    </tr>

                                                    <!-- Original -->
                                                    <tr>
                                                        <td style="text-align: center">Original</td>
                                                        <td style="text-align: center"><?= $original_sold->fields['SOLD'] ?? 0 ?></td>
                                                        <td style="text-align: center"><?= $original_units->fields['UNITS'] ?? 0 ?></td>
                                                    </tr>

                                                    <!-- Extension -->
                                                    <tr>
                                                        <td style="text-align: center">Extension</td>
                                                        <td style="text-align: center"><?= $extension_sold->fields['SOLD'] ?? 0 ?></td>
                                                        <td style="text-align: center"><?= $extension_units->fields['UNITS'] ?? 0 ?></td>
                                                    </tr>

                                                    <!-- Renewal -->
                                                    <tr>
                                                        <td style="text-align: center">Renewal</td>
                                                        <td style="text-align: center"><?= $renewal_sold->fields['SOLD'] ?? 0 ?></td>
                                                        <td style="text-align: center"><?= $renewal_units->fields['UNITS'] ?? 0 ?></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php
                                        $each_service_provider->MoveNext();
                                    } ?>
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