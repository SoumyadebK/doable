<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

// Get appointment type parameter
$appointment_type = isset($_GET['appointment_type']) ? $_GET['appointment_type'] : 'all';

$today = date('Y-m-d');

$account_data = $db->Execute("SELECT * FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]'");
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

// Set filename based on appointment type
$filename = "NFA_Active_No_Enrollments_Report";
if ($appointment_type == 'with_previous') {
    $filename .= "_With_Previous_Appointments";
} elseif ($appointment_type == 'without_previous') {
    $filename .= "_Without_Previous_Appointments";
}
$filename .= "_" . date('Y-m-d') . ".xls";

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");

// Build the query (same as in the HTML report)
$base_query = "
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
";

$row = $db_account->Execute($base_query);
?>
<html>

<head>
    <meta charset="UTF-8">
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <table>
        <tr>
            <td colspan="7" style="text-align: center; font-weight: bold; font-size: 16px;">
                NFA ACTIVE NO ENROLLMENTS REPORT
                <?php if ($appointment_type == 'with_previous'): ?>
                    <br><small>(With Previous Appointments)</small>
                <?php elseif ($appointment_type == 'without_previous'): ?>
                    <br><small>(Without Previous Appointments)</small>
                <?php else: ?>
                    <br><small>(All)</small>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <td colspan="7" style="text-align: center;">
                <?= ($account_data->fields['FRANCHISE'] == 1) ? 'Franchisee: ' : '' ?><?= " (" . $concatenatedResults . ")" ?>
            </td>
        </tr>
        <tr>
            <th>Student</th>
            <th>Phone</th>
            <th>Email</th>
            <th>Address</th>
            <th>Date of the Last Private Appointment</th>
            <th>Total Days Since the Last One</th>
            <th>Status</th>
        </tr>
        <?php
        while (!$row->EOF) {
            $last_appointment_date = $row->fields['LAST_PRIVATE_APPOINTMENT_DATE'];
            $has_previous_appointment = !empty($last_appointment_date);

            // Filter based on appointment type
            $show_record = false;
            if ($appointment_type == 'all') {
                $show_record = true;
            } elseif ($appointment_type == 'with_previous' && $has_previous_appointment) {
                $show_record = true;
            } elseif ($appointment_type == 'without_previous' && !$has_previous_appointment) {
                $show_record = true;
            }

            if ($show_record) {
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
                    <td><?= $row->fields['CLIENT'] ?></td>
                    <td><?= $row->fields['PHONE'] ?></td>
                    <td><?= $row->fields['EMAIL_ID'] ?></td>
                    <td><?= $row->fields['ADDRESS'] ?></td>
                    <td><?= $formatted_date ?></td>
                    <td><?= !empty($days_since_last) ? $days_since_last . ' days' : 'N/A' ?></td>
                    <td><?= $row->fields['STATUS'] ?></td>
                </tr>
        <?php
            }
            $row->MoveNext();
        }
        ?>
    </table>
</body>

</html>