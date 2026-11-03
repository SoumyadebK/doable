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

// Get the appointment type filter from GET parameters
$appointment_type = isset($_GET['appointment_type']) ? $_GET['appointment_type'] : 'all';
$type = isset($_GET['type']) ? $_GET['type'] : 'view';

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
    $concatenatedResults .= $result;
    if ($key < $totalResults - 1) {
        $concatenatedResults .= ", ";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<?php include 'layout/header_script.php'; ?>
<?php require_once('../includes/header.php'); ?>
<?php include 'layout/header.php'; ?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<link href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css" rel="stylesheet">

<style>
    a {
        color: #690C24;
        text-decoration: none;
        font-size: 14px;
    }

    .btn {
        border: 0;
        color: #fff;
        border-radius: 50rem;
        padding-left: 1.5rem;
        padding-right: 1.5rem;
    }

    input.form-control,
    select.form-control,
    textarea.form-control {
        border-radius: 0.375rem !important;
    }

    .filter-badge {
        background: #f8f9fa;
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 14px;
        color: #495057;
        margin-left: 10px;
    }

    .filter-badge strong {
        color: #690C24;
    }

    .filter-dropdown-container {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 15px;
        margin-bottom: 20px;
        padding: 10px;
        background: #f8f9fa;
        border-radius: 8px;
        flex-wrap: wrap;
    }

    .filter-dropdown-container label {
        font-weight: 600;
        color: #495057;
        margin: 0;
    }

    .filter-dropdown-container select {
        min-width: 200px;
        border-radius: 20px;
        padding: 5px 15px;
        border: 1px solid #ced4da;
    }

    .filter-dropdown-container .filter-count {
        background: #690C24;
        color: white;
        padding: 2px 12px;
        border-radius: 20px;
        font-size: 13px;
        text-align: center;
        min-width: 120px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
    }
</style>

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">
        <div class="page-wrapper" style="padding-top: 1px !important;">
            <div class="container-fluid body_content" style="margin-top: 0px;">
                <div class="row page-titles">
                    <div class="col-md-5 align-self-center">
                        <h4 class="text-themecolor"><?= $title ?></h4>
                    </div>
                    <div class="col-md-7 align-self-center text-end">
                        <div class="d-flex justify-content-end align-items-center">
                            <ol class="breadcrumb justify-content-end">
                                <li class="breadcrumb-item active"><a href="reports.php">Reports</a></li>
                                <li class="breadcrumb-item active"><a href="nfa_active_no_enrollments_report.php"><?= $title ?></a></li>
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

                                <!-- Filter Dropdown -->
                                <div class="filter-dropdown-container">
                                    <label for="filterSelect">
                                        <i class="bi bi-funnel"></i> Filter by Appointment History:
                                    </label>
                                    <select id="filterSelect" class="form-select" onchange="applyFilter(this.value)">
                                        <option value="all" <?= $appointment_type == 'all' ? 'selected' : '' ?>>All</option>
                                        <option value="with_previous" <?= $appointment_type == 'with_previous' ? 'selected' : '' ?>>With Previous Appointments</option>
                                        <option value="without_previous" <?= $appointment_type == 'without_previous' ? 'selected' : '' ?>>Without Previous Appointments</option>
                                    </select>
                                    <span class="filter-count" id="recordCount">
                                        <i class="bi bi-calendar-check"></i>
                                        <?php
                                        // Count records for display
                                        $count_sql = "
                                            SELECT COUNT(*) as total
                                            FROM $master_database.DOA_USERS AS DOA_USERS
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
                                        ";

                                        if ($appointment_type == 'with_previous') {
                                            $count_sql .= " AND EXISTS (
                                                SELECT 1 
                                                FROM DOA_APPOINTMENT_MASTER am
                                                INNER JOIN DOA_ENROLLMENT_MASTER em ON am.PK_ENROLLMENT_MASTER = em.PK_ENROLLMENT_MASTER
                                                INNER JOIN DOA_SERVICE_CODE sc ON am.PK_SERVICE_CODE = sc.PK_SERVICE_CODE
                                                WHERE em.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER
                                                AND am.DATE <= CURDATE()
                                                AND sc.IS_GROUP = 0 
                                                AND am.PK_APPOINTMENT_STATUS = 2
                                            )";
                                        } else if ($appointment_type == 'without_previous') {
                                            $count_sql .= " AND NOT EXISTS (
                                                SELECT 1 
                                                FROM DOA_APPOINTMENT_MASTER am
                                                INNER JOIN DOA_ENROLLMENT_MASTER em ON am.PK_ENROLLMENT_MASTER = em.PK_ENROLLMENT_MASTER
                                                INNER JOIN DOA_SERVICE_CODE sc ON am.PK_SERVICE_CODE = sc.PK_SERVICE_CODE
                                                WHERE em.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER
                                                AND am.DATE <= CURDATE()
                                                AND sc.IS_GROUP = 0 
                                                AND am.PK_APPOINTMENT_STATUS = 2
                                            )";
                                        }

                                        $count_result = $db_account->Execute($count_sql);
                                        $total_records = $count_result ? $count_result->fields['total'] : 0;
                                        echo $total_records . ' records';
                                        ?>
                                    </span>
                                    <?php if ($appointment_type != 'all'): ?>
                                        <a href="?appointment_type=all" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-x-circle"></i> Clear Filter
                                        </a>
                                    <?php endif; ?>
                                </div>

                                <div class="table-responsive">
                                    <table id="myTable" class="table table-bordered" data-page-length='50'>
                                        <thead>
                                            <tr>
                                                <th style="width:50%; text-align: center; vertical-align:auto; font-weight: bold" colspan="7">
                                                    <?= ($account_data->fields['FRANCHISE'] == 1) ? 'Franchisee: ' : '' ?>
                                                    <?= " (" . $concatenatedResults . ")" ?>
                                                </th>
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

                                            // Build the appointment type filter condition
                                            $appointment_filter = "";
                                            if ($appointment_type == 'with_previous') {
                                                $appointment_filter = "AND EXISTS (
                                                    SELECT 1 
                                                    FROM DOA_APPOINTMENT_MASTER am
                                                    INNER JOIN DOA_ENROLLMENT_MASTER em ON am.PK_ENROLLMENT_MASTER = em.PK_ENROLLMENT_MASTER
                                                    INNER JOIN DOA_SERVICE_CODE sc ON am.PK_SERVICE_CODE = sc.PK_SERVICE_CODE
                                                    WHERE em.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER
                                                    AND am.DATE <= CURDATE()
                                                    AND sc.IS_GROUP = 0 
                                                    AND am.PK_APPOINTMENT_STATUS = 2
                                                )";
                                            } else if ($appointment_type == 'without_previous') {
                                                $appointment_filter = "AND NOT EXISTS (
                                                    SELECT 1 
                                                    FROM DOA_APPOINTMENT_MASTER am
                                                    INNER JOIN DOA_ENROLLMENT_MASTER em ON am.PK_ENROLLMENT_MASTER = em.PK_ENROLLMENT_MASTER
                                                    INNER JOIN DOA_SERVICE_CODE sc ON am.PK_SERVICE_CODE = sc.PK_SERVICE_CODE
                                                    WHERE em.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER
                                                    AND am.DATE <= CURDATE()
                                                    AND sc.IS_GROUP = 0 
                                                    AND am.PK_APPOINTMENT_STATUS = 2
                                                )";
                                            }

                                            $sql = "
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
                                                    " . $appointment_filter . "
                                                ORDER BY CLIENT ASC
                                            ";

                                            $row = $db_account->Execute($sql);

                                            if ($row && $row->RecordCount() > 0) {
                                                while (!$row->EOF) {
                                                    $last_appointment_date = $row->fields['LAST_PRIVATE_APPOINTMENT_DATE'];
                                                    $days_since_last = '';

                                                    if (!empty($last_appointment_date)) {
                                                        $last_date = new DateTime($last_appointment_date);
                                                        $today_date = new DateTime();
                                                        $interval = $today_date->diff($last_date);
                                                        $days_since_last = $interval->days;
                                                    }

                                                    $formatted_date = !empty($last_appointment_date) ? date('m/d/Y', strtotime($last_appointment_date)) : 'No Previous Appointment';
                                            ?>
                                                    <tr>
                                                        <td style="text-align: left"><?= htmlspecialchars($row->fields['CLIENT']) ?></td>
                                                        <td style="text-align: center"><?= htmlspecialchars($row->fields['PHONE']) ?></td>
                                                        <td style="text-align: center"><?= htmlspecialchars($row->fields['EMAIL_ID']) ?></td>
                                                        <td style="text-align: center"><?= htmlspecialchars($row->fields['ADDRESS']) ?></td>
                                                        <td style="text-align: center"><?= $formatted_date ?></td>
                                                        <td style="text-align: center"><?= !empty($days_since_last) ? $days_since_last . ' days' : 'N/A' ?></td>
                                                        <td style="text-align: center"><?= $row->fields['STATUS'] ?></td>
                                                    </tr>
                                                <?php
                                                    $row->MoveNext();
                                                    $i++;
                                                }
                                            } else {
                                                ?>
                                                <tr>
                                                    <td colspan="7" style="text-align: center; padding: 30px;">
                                                        <i class="bi bi-info-circle" style="font-size: 24px; color: #6c757d;"></i>
                                                        <p style="margin-top: 10px; color: #6c757d;">No records found for the selected filter.</p>
                                                    </td>
                                                </tr>
                                            <?php
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

<script>
    function applyFilter(value) {
        // Get the current URL parameters
        const urlParams = new URLSearchParams(window.location.search);

        // Update or add the appointment_type parameter
        if (value && value !== 'all') {
            urlParams.set('appointment_type', value);
        } else {
            urlParams.delete('appointment_type');
        }

        // Preserve other parameters like 'type' if needed
        // urlParams.set('type', 'view');

        // Redirect to the new URL
        window.location.href = window.location.pathname + '?' + urlParams.toString();
    }

    // Add event listener for the dropdown to support Enter key
    document.addEventListener('DOMContentLoaded', function() {
        const filterSelect = document.getElementById('filterSelect');
        if (filterSelect) {
            filterSelect.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    applyFilter(this.value);
                }
            });
        }
    });
</script>