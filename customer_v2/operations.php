<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;
global $results_per_page;

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 4) {
    header("location:../login.php");
    exit;
}

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];

$title = "Appointments";

$appointment_time = " AND DOA_APPOINTMENT_MASTER.DATE <= '" . date('Y-m-d') . "'";

if (isset($_GET['SERVICE_PROVIDER_ID']) && $_GET['SERVICE_PROVIDER_ID'] != '') {
    $SERVICE_PROVIDER_ID = $_GET['SERVICE_PROVIDER_ID'];
} else {
    $SERVICE_PROVIDER_ID = 0;
}

$search_text = '';
$search = '';
$SPECIFIC_DATE = '';
$FROM_DATE = '';
$END_DATE = '';

if (!empty($_GET['DATE_SELECTION'])) {
    if ($_GET['DATE_SELECTION'] == 1) {
        $SPECIFIC_DATE = date('Y-m-d');
        if ($SERVICE_PROVIDER_ID > 0) {
            $search_text = $_GET['SERVICE_PROVIDER_ID'];
            $search = " AND DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER = " . $SERVICE_PROVIDER_ID . " AND DOA_APPOINTMENT_MASTER.DATE = '$SPECIFIC_DATE'";
        } else {
            $search = " AND DOA_APPOINTMENT_MASTER.DATE = '$SPECIFIC_DATE'";
        }
    } else if ($_GET['DATE_SELECTION'] == 2) {
        $SPECIFIC_DATE = date('Y-m-d', strtotime("-1 days"));
        if ($SERVICE_PROVIDER_ID > 0) {
            $search_text = $SERVICE_PROVIDER_ID;
            $search = " AND DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER = " . $SERVICE_PROVIDER_ID . " AND DOA_APPOINTMENT_MASTER.DATE = '$SPECIFIC_DATE'";
        } else {
            $search = " AND DOA_APPOINTMENT_MASTER.DATE = '$SPECIFIC_DATE'";
        }
    } else if ($_GET['DATE_SELECTION'] == 3) {
        [$START_DATE, $END_DATE] = currentWeekRange(date('Y-m-d'));
        if ($SERVICE_PROVIDER_ID > 0) {
            $search_text = $SERVICE_PROVIDER_ID;
            $search = " DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER = " . $SERVICE_PROVIDER_ID . " AND DOA_APPOINTMENT_MASTER.DATE >= '$START_DATE' AND DOA_APPOINTMENT_MASTER.DATE <= '$END_DATE'";
        } else {
            $search = " AND DOA_APPOINTMENT_MASTER.DATE >= '$START_DATE' AND DOA_APPOINTMENT_MASTER.DATE <= '$END_DATE'";
        }
    } else if ($_GET['DATE_SELECTION'] == 4) {
        $SPECIFIC_DATE = date('Y-m-d', strtotime($_GET['SPECIFIC_DATE']));
        if ($SERVICE_PROVIDER_ID > 0) {
            $search_text = $SERVICE_PROVIDER_ID;
            $search = " AND DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER = " . $SERVICE_PROVIDER_ID . " AND DOA_APPOINTMENT_MASTER.DATE = '$SPECIFIC_DATE'";
        } else {
            $search = " AND DOA_APPOINTMENT_MASTER.DATE = '$SPECIFIC_DATE'";
        }
    } else if ($_GET['DATE_SELECTION'] == 5) {
        $FROM_DATE = date('Y-m-d', strtotime($_GET['FROM_DATE']));
        $END_DATE = date('Y-m-d', strtotime($_GET['END_DATE']));
        if ($SERVICE_PROVIDER_ID > 0) {
            $search_text = $SERVICE_PROVIDER_ID;
            $search = " AND DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER = " . $SERVICE_PROVIDER_ID . " AND DOA_APPOINTMENT_MASTER.DATE BETWEEN '$FROM_DATE' AND '$END_DATE'";
        } else {
            $search_text = '';
            $search = " AND DOA_APPOINTMENT_MASTER.DATE BETWEEN '$FROM_DATE' AND '$END_DATE'";
        }
    }
} elseif ($SERVICE_PROVIDER_ID > 0) {
    $search = " AND DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER = " . $SERVICE_PROVIDER_ID;
}

if (!empty($_GET['date'])) {
    if ($_GET['date'] == 'today') {
        $search = " AND DOA_APPOINTMENT_MASTER.DATE = '" . date('Y-m-d') . "'";
    } else if ($_GET['date'] == 'yesterday') {
        $search = " AND DOA_APPOINTMENT_MASTER.DATE = '" . date('Y-m-d', strtotime('-1 day')) . "'";
    } else if ($_GET['date'] == 'earlier') {
        $search = " AND DOA_APPOINTMENT_MASTER.DATE < '" . date('Y-m-d') . "' AND DOA_APPOINTMENT_MASTER.IS_PAID = 0";
    }
}

$ALL_APPOINTMENT_QUERY = "SELECT
                            DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER,
                            DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_SERVICE,
                            DOA_APPOINTMENT_MASTER.GROUP_NAME,
                            DOA_APPOINTMENT_MASTER.SERIAL_NUMBER,
                            DOA_APPOINTMENT_MASTER.DATE,
                            DOA_APPOINTMENT_MASTER.START_TIME,
                            DOA_APPOINTMENT_MASTER.END_TIME,
                            DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE,
                            DOA_APPOINTMENT_MASTER.IS_PAID,
                            DOA_ENROLLMENT_MASTER.ENROLLMENT_NAME,
                            DOA_ENROLLMENT_MASTER.ENROLLMENT_ID,
                            DOA_SERVICE_MASTER.SERVICE_NAME,
                            DOA_SERVICE_CODE.SERVICE_CODE,
                            DOA_APPOINTMENT_MASTER.IS_PAID,
                            DOA_APPOINTMENT_MASTER.NO_SHOW,
                            DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS,
                            DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE,
                            DOA_APPOINTMENT_STATUS.STATUS_CODE,
                            DOA_APPOINTMENT_STATUS.COLOR_CODE AS APPOINTMENT_COLOR,
                            DOA_SCHEDULING_CODE.COLOR_CODE,
                            GROUP_CONCAT(DISTINCT(CONCAT(SERVICE_PROVIDER.FIRST_NAME, ' ', SERVICE_PROVIDER.LAST_NAME)) SEPARATOR ',') AS SERVICE_PROVIDER_NAME,
                            GROUP_CONCAT(DISTINCT(CONCAT(CUSTOMER.FIRST_NAME, ' ', CUSTOMER.LAST_NAME)) SEPARATOR ',') AS CUSTOMER_NAME
                        FROM
                            DOA_APPOINTMENT_MASTER
                        LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_SERVICE_PROVIDER.PK_APPOINTMENT_MASTER
                        LEFT JOIN $master_database.DOA_USERS AS SERVICE_PROVIDER ON DOA_APPOINTMENT_SERVICE_PROVIDER.PK_USER = SERVICE_PROVIDER.PK_USER
                        
                        LEFT JOIN DOA_APPOINTMENT_CUSTOMER ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER = DOA_APPOINTMENT_CUSTOMER.PK_APPOINTMENT_MASTER
                        LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_APPOINTMENT_CUSTOMER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER
                        LEFT JOIN $master_database.DOA_USERS AS CUSTOMER ON DOA_USER_MASTER.PK_USER = CUSTOMER.PK_USER
                                
                        LEFT JOIN DOA_SCHEDULING_CODE ON DOA_APPOINTMENT_MASTER.PK_SCHEDULING_CODE = DOA_SCHEDULING_CODE.PK_SCHEDULING_CODE
                        LEFT JOIN DOA_SERVICE_MASTER ON DOA_APPOINTMENT_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_MASTER.PK_SERVICE_MASTER
                        LEFT JOIN $master_database.DOA_APPOINTMENT_STATUS AS DOA_APPOINTMENT_STATUS ON DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS = DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS 
                        LEFT JOIN DOA_ENROLLMENT_MASTER ON DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_MASTER = DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER
                        LEFT JOIN DOA_SERVICE_CODE ON DOA_APPOINTMENT_MASTER.PK_SERVICE_CODE = DOA_SERVICE_CODE.PK_SERVICE_CODE
                        WHERE (CUSTOMER.IS_DELETED = 0 OR CUSTOMER.IS_DELETED IS null) 
                        AND DOA_APPOINTMENT_MASTER.PK_LOCATION IN ($DEFAULT_LOCATION_ID)
                        AND DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS IN (1, 3, 4, 5, 7, 8)
                        AND DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE IN ('GROUP', 'NORMAL', 'AD-HOC')
                        AND DOA_APPOINTMENT_MASTER.STATUS = 'A'
                        AND DOA_APPOINTMENT_CUSTOMER.PK_USER_MASTER = '$_SESSION[PK_USER_MASTER]'
                        $appointment_time
                        $search
                        GROUP BY DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER
                        ORDER BY DOA_APPOINTMENT_MASTER.DATE DESC";

$query = $db_account->Execute($ALL_APPOINTMENT_QUERY);

$number_of_result =  $query->RecordCount();
$number_of_page = ceil($number_of_result / $results_per_page);
if (!isset($_GET['page'])) {
    $page = 1;
} else {
    $page = $_GET['page'];
}
$page_first_result = ($page - 1) * $results_per_page;

function currentWeekRange($date): array
{
    $ts = strtotime($date);
    $start = (date('w', $ts) == 0) ? $ts : strtotime('last sunday', $ts);
    return array(date('Y-m-d', $start), date('Y-m-d', strtotime('next saturday', $start)));
}

?>

<!DOCTYPE html>
<html lang="en">
<?php include 'layout/header_script.php'; ?>
<?php require_once('../includes/header.php'); ?>
<?php include 'layout/header.php'; ?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

<style>
    :root {
        --primary-color: #39B54A;
        --primary-light: #5DCB6E;
        --primary-dark: #2D8F3B;
        --primary-rgb: 57, 181, 74;
        --success-color: #39B54A;
        --warning-color: #F59E0B;
        --danger-color: #EF4444;
        --gray-50: #F9FAFB;
        --gray-100: #F3F4F6;
        --gray-200: #E5E7EB;
        --gray-300: #D1D5DB;
        --gray-400: #9CA3AF;
        --gray-500: #6B7280;
        --gray-600: #4B5563;
        --gray-700: #374151;
        --gray-800: #1F2937;
        --gray-900: #111827;
        --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        --radius: 12px;
        --radius-sm: 8px;
        --radius-lg: 16px;
        --radius-pill: 50px;
    }

    * {
        box-sizing: border-box;
    }

    body {
        background: var(--gray-50);
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }

    .page-wrapper {
        padding-top: 0px !important;
        background: var(--gray-50);
    }

    .breadcrumb-wrapper {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        flex-wrap: wrap;
        gap: 12px;
    }

    .breadcrumb-wrapper h4 {
        font-size: 24px;
        font-weight: 700;
        color: var(--gray-900);
        margin: 0;
        letter-spacing: -0.025em;
    }

    .breadcrumb-wrapper h4 i {
        color: var(--primary-color);
        margin-right: 10px;
    }

    .breadcrumb-nav {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        color: var(--gray-500);
    }

    .breadcrumb-nav .current {
        color: var(--gray-700);
        font-weight: 500;
    }

    /* Filter Bar */
    .filter-bar {
        background: #ffffff;
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--gray-200);
        padding: 20px 24px;
        margin-bottom: 24px;
    }

    .filter-bar .filter-row {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        align-items: center;
        margin-bottom: 12px;
    }

    .filter-bar .filter-row:last-child {
        margin-bottom: 0;
    }

    .filter-bar .filter-row .filter-item {
        flex: 1;
        min-width: 150px;
    }

    .quick-filter-buttons {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    @media (max-width: 768px) {
        .filter-bar .filter-row {
            flex-direction: column;
            align-items: stretch;
        }

        .filter-bar .filter-row .filter-item {
            min-width: 100%;
        }

        .quick-filter-buttons {
            width: 100%;
        }

        .quick-filter-buttons .btn-modern {
            flex: 1;
            justify-content: center;
        }
    }

    /* Form Controls */
    .form-control-modern {
        width: 100%;
        padding: 10px 14px;
        font-size: 14px;
        color: var(--gray-800);
        background: #fff;
        border: 1.5px solid var(--gray-200);
        border-radius: var(--radius-sm);
        transition: all 0.2s ease;
        outline: none;
        font-family: inherit;
    }

    .form-control-modern:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1);
    }

    .form-control-modern::placeholder {
        color: var(--gray-400);
        font-size: 13px;
    }

    select.form-control-modern {
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236B7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 12px center;
        padding-right: 36px;
    }

    /* Buttons */
    .btn-modern {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 22px;
        font-size: 14px;
        font-weight: 500;
        border: none;
        border-radius: var(--radius-pill);
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        font-family: inherit;
        line-height: 1.5;
        white-space: nowrap;
    }

    .btn-modern-primary {
        background: var(--primary-color);
        color: #fff;
    }

    .btn-modern-primary:hover {
        background: var(--primary-dark);
        box-shadow: var(--shadow-md);
        transform: translateY(-1px);
        color: #fff;
    }

    .btn-modern-secondary {
        background: var(--gray-100);
        color: var(--gray-700);
    }

    .btn-modern-secondary:hover {
        background: var(--gray-200);
        color: var(--gray-800);
    }

    .btn-modern-outline {
        background: #fff;
        color: var(--gray-600);
        border: 1.5px solid var(--gray-200);
    }

    .btn-modern-outline:hover {
        background: var(--gray-50);
        border-color: var(--primary-color);
        color: var(--primary-color);
    }

    .btn-modern-outline.active {
        background: var(--primary-color);
        border-color: var(--primary-color);
        color: #fff;
    }

    .btn-modern-sm {
        padding: 6px 16px;
        font-size: 13px;
    }

    /* Card / Table */
    .card-modern {
        background: #ffffff;
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--gray-200);
        overflow: hidden;
        transition: box-shadow 0.2s ease;
    }

    .card-modern:hover {
        box-shadow: var(--shadow-md);
    }

    .card-modern .card-header {
        padding: 20px 24px;
        background: var(--gray-50);
        border-bottom: 1px solid var(--gray-200);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
    }

    .card-modern .card-header h5 {
        font-size: 16px;
        font-weight: 600;
        color: var(--gray-800);
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .card-modern .card-header h5 i {
        color: var(--primary-color);
    }

    .card-modern .card-body {
        padding: 24px 28px;
    }

    /* Table */
    .table-wrapper {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .table-modern {
        width: 100% !important;
        border-collapse: collapse;
        font-size: 14px;
    }

    .table-modern thead th {
        background: var(--gray-50);
        padding: 12px 14px;
        text-align: left;
        font-weight: 600;
        color: var(--gray-600);
        border-bottom: 2px solid var(--gray-200);
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        white-space: nowrap;
        cursor: pointer;
        user-select: none;
        position: relative;
    }

    .table-modern thead th:hover {
        background: var(--gray-100);
        color: var(--gray-800);
    }

    .table-modern thead th.sortable::after {
        font-family: "Font Awesome 6 Free";
        font-weight: 900;
        content: "\f0dc";
        position: absolute;
        right: 8px;
        color: var(--gray-400);
        font-size: 11px;
    }

    .table-modern thead th.sortable.asc::after {
        content: "\f0d8";
        color: var(--primary-color);
    }

    .table-modern thead th.sortable.desc::after {
        content: "\f0d7";
        color: var(--primary-color);
    }

    .table-modern tbody td {
        padding: 12px 14px;
        border-bottom: 1px solid var(--gray-100);
        color: var(--gray-700);
        vertical-align: middle;
    }

    .table-modern tbody tr {
        transition: background 0.15s ease;
    }

    .table-modern tbody tr:hover {
        background: var(--gray-50);
    }

    .table-modern tbody tr.row-today {
        background: #F0FDF4;
    }

    .table-modern tbody tr.row-today:hover {
        background: #DCFCE7;
    }

    .table-modern tbody tr.row-noshow {
        background: #FEF9C3;
    }

    .table-modern tbody tr.row-noshow:hover {
        background: #FEF08A;
    }

    .table-modern tbody tr.row-cancelled {
        background: #FEE2E2;
    }

    .table-modern tbody tr.row-cancelled:hover {
        background: #FECACA;
    }

    /* Checkbox */
    .checkbox-modern {
        width: 18px;
        height: 18px;
        accent-color: var(--primary-color);
        cursor: pointer;
    }

    /* Paid Badge */
    .paid-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 12px;
        border-radius: 50px;
        font-size: 12px;
        font-weight: 500;
        white-space: nowrap;
    }

    .paid-badge.paid {
        background: #D1FAE5;
        color: #065F46;
    }

    .paid-badge.unpaid {
        background: #FEF3C7;
        color: #92400E;
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 48px 20px;
        color: var(--gray-400);
    }

    .empty-state i {
        font-size: 48px;
        margin-bottom: 16px;
        color: var(--gray-300);
    }

    .empty-state h5 {
        font-size: 18px;
        font-weight: 600;
        color: var(--gray-600);
        margin-bottom: 8px;
    }

    .empty-state p {
        font-size: 14px;
        color: var(--gray-400);
    }

    /* Pagination */
    .pagination-modern {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 4px;
        padding: 20px 24px;
        border-top: 1px solid var(--gray-200);
        flex-wrap: wrap;
    }

    .pagination-modern .page-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 36px;
        height: 36px;
        padding: 0 12px;
        border: 1px solid var(--gray-200);
        border-radius: var(--radius-sm);
        color: var(--gray-600);
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.2s ease;
        background: #fff;
    }

    .pagination-modern .page-link:hover {
        border-color: var(--primary-color);
        color: var(--primary-color);
        background: #F0FDF4;
    }

    .pagination-modern .page-link.active {
        background: var(--primary-color);
        border-color: var(--primary-color);
        color: #fff;
    }

    .pagination-modern .page-link.active:hover {
        background: var(--primary-dark);
        border-color: var(--primary-dark);
        color: #fff;
    }

    .pagination-modern .page-link.dots {
        border: none;
        cursor: default;
        background: transparent;
    }

    .pagination-modern .page-link.dots:hover {
        background: transparent;
        border-color: transparent;
        color: var(--gray-500);
    }

    .pagination-modern .page-link.hidden {
        display: none;
    }

    .form-helper {
        font-size: 12px;
        color: var(--gray-400);
        margin-top: 4px;
    }

    .date-filter-input {
        display: none;
    }

    .date-filter-input.active {
        display: block;
    }
</style>

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">
        <?php require_once('../includes/header.php'); ?>

        <div class="page-wrapper" style="padding-top: 0px !important;">
            <div class="container-fluid py-4 px-4 m-auto mx-auto dashboard-container">

                <!-- Filter Bar -->
                <div class="filter-bar">
                    <form class="form-horizontal" action="" method="get" id="filter_form">
                        <!-- Quick Filter Buttons -->
                        <div class="quick-filter-buttons" style="margin-bottom: 16px;">
                            <?php
                            $today_count = $db_account->Execute("SELECT COUNT(DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER) AS TODAY_COUNT FROM DOA_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_MASTER.PK_LOCATION IN ($DEFAULT_LOCATION_ID) AND DOA_APPOINTMENT_MASTER.STATUS = 'A' AND DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS != 2 AND DOA_APPOINTMENT_MASTER.DATE = '" . date('Y-m-d') . "'");
                            $yesterday_count = $db_account->Execute("SELECT COUNT(DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER) AS YESTERDAY_COUNT FROM DOA_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_MASTER.PK_LOCATION IN ($DEFAULT_LOCATION_ID) AND DOA_APPOINTMENT_MASTER.STATUS = 'A' AND DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS != 2 AND DOA_APPOINTMENT_MASTER.DATE = '" . date('Y-m-d', strtotime('-1 day')) . "'");
                            $earlier_count = $db_account->Execute("SELECT COUNT(DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER) AS EARLIER_COUNT FROM DOA_APPOINTMENT_MASTER WHERE DOA_APPOINTMENT_MASTER.PK_LOCATION IN ($DEFAULT_LOCATION_ID) AND DOA_APPOINTMENT_MASTER.STATUS = 'A' AND DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS != 2 AND DOA_APPOINTMENT_MASTER.DATE < '" . date('Y-m-d') . "' AND DOA_APPOINTMENT_MASTER.IS_PAID = 0");
                            ?>
                            <a href="operations.php?date=today" class="btn-modern btn-modern-outline btn-modern-sm <?= (isset($_GET['date']) && $_GET['date'] == 'today') ? 'active' : '' ?>">
                                <i class="fas fa-calendar-day"></i> Today (<?= ($today_count->RecordCount() > 0) ? $today_count->fields['TODAY_COUNT'] : 0 ?>)
                            </a>
                            <a href="operations.php?date=yesterday" class="btn-modern btn-modern-outline btn-modern-sm <?= (isset($_GET['date']) && $_GET['date'] == 'yesterday') ? 'active' : '' ?>">
                                <i class="fas fa-calendar-minus"></i> Yesterday (<?= ($yesterday_count->RecordCount() > 0) ? $yesterday_count->fields['YESTERDAY_COUNT'] : 0 ?>)
                            </a>
                            <a href="operations.php?date=earlier" class="btn-modern btn-modern-outline btn-modern-sm <?= (isset($_GET['date']) && $_GET['date'] == 'earlier') ? 'active' : '' ?>">
                                <i class="fas fa-calendar-times"></i> Earlier (<?= ($earlier_count->RecordCount() > 0) ? $earlier_count->fields['EARLIER_COUNT'] : 0 ?>)
                            </a>
                        </div>

                        <!-- Filter Row -->
                        <div class="filter-row">
                            <div class="filter-item">
                                <select class="form-control-modern" name="SERVICE_PROVIDER_ID" id="SERVICE_PROVIDER_ID">
                                    <option value="">Select <?= htmlspecialchars($service_provider_title ?? 'Service Provider') ?></option>
                                    <?php
                                    $row = $db->Execute("SELECT DISTINCT DOA_USERS.PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER INNER JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER=DOA_USER_LOCATION.PK_USER WHERE DOA_USER_ROLES.PK_ROLES = 5 AND DOA_USER_LOCATION.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND ACTIVE=1 AND DOA_USERS.PK_ACCOUNT_MASTER = " . $_SESSION['PK_ACCOUNT_MASTER'] . " ORDER BY NAME");
                                    while (!$row->EOF) { ?>
                                        <option value="<?= $row->fields['PK_USER'] ?>" <?= ($row->fields['PK_USER'] == $SERVICE_PROVIDER_ID) ? 'selected' : '' ?>><?= htmlspecialchars($row->fields['NAME']) ?></option>
                                    <?php $row->MoveNext();
                                    } ?>
                                </select>
                            </div>

                            <div class="filter-item">
                                <select class="form-control-modern" name="DATE_SELECTION" id="DATE_SELECTION" onchange="selectDate(this)">
                                    <option value="">Select Date Filter</option>
                                    <option value="1" <?= (isset($_GET['DATE_SELECTION']) && $_GET['DATE_SELECTION'] == 1) ? 'selected' : '' ?>>Today</option>
                                    <option value="2" <?= (isset($_GET['DATE_SELECTION']) && $_GET['DATE_SELECTION'] == 2) ? 'selected' : '' ?>>Yesterday</option>
                                    <option value="3" <?= (isset($_GET['DATE_SELECTION']) && $_GET['DATE_SELECTION'] == 3) ? 'selected' : '' ?>>This Week</option>
                                    <option value="4" <?= (isset($_GET['DATE_SELECTION']) && $_GET['DATE_SELECTION'] == 4) ? 'selected' : '' ?>>Specific Date</option>
                                    <option value="5" <?= (isset($_GET['DATE_SELECTION']) && $_GET['DATE_SELECTION'] == 5) ? 'selected' : '' ?>>Date Range</option>
                                </select>
                            </div>

                            <div class="filter-item specific_date <?= (isset($_GET['DATE_SELECTION']) && $_GET['DATE_SELECTION'] == 4) ? '' : 'date-filter-input' ?>">
                                <input type="text" id="SPECIFIC_DATE" name="SPECIFIC_DATE" placeholder="Specific Date" class="form-control-modern datepicker-past" value="<?= ($SPECIFIC_DATE == '' || $SPECIFIC_DATE == '0000-00-00') ? '' : date('m/d/Y', strtotime($SPECIFIC_DATE)) ?>">
                            </div>

                            <div class="filter-item from_date <?= (isset($_GET['DATE_SELECTION']) && $_GET['DATE_SELECTION'] == 5) ? '' : 'date-filter-input' ?>">
                                <input type="text" id="FROM_DATE" name="FROM_DATE" placeholder="From Date" class="form-control-modern datepicker-past" value="<?= ($FROM_DATE == '' || $FROM_DATE == '0000-00-00') ? '' : date('m/d/Y', strtotime($FROM_DATE)) ?>">
                            </div>

                            <div class="filter-item end_date <?= (isset($_GET['DATE_SELECTION']) && $_GET['DATE_SELECTION'] == 5) ? '' : 'date-filter-input' ?>">
                                <input type="text" id="END_DATE" name="END_DATE" placeholder="To Date" class="form-control-modern datepicker-normal" value="<?= ($END_DATE == '' || $END_DATE == '0000-00-00') ? '' : date('m/d/Y', strtotime($END_DATE)) ?>">
                            </div>

                            <div class="filter-item" style="flex: 0;">
                                <button type="submit" class="btn-modern btn-modern-primary">
                                    <i class="fas fa-search"></i> Search
                                </button>
                            </div>
                            <div class="filter-item" style="flex: 0;">
                                <a href="operations.php" class="btn-modern btn-modern-secondary">
                                    <i class="fas fa-times"></i> Clear
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Main Content -->
                <div class="row">
                    <div class="col-12">
                        <div class="card-modern">
                            <div class="card-header">
                                <h5>
                                    <i class="fas fa-list"></i>
                                    Appointments
                                    <span style="background: var(--gray-200); color: var(--gray-600); padding: 2px 12px; border-radius: 50px; font-size: 13px; font-weight: 500;"><?= $number_of_result ?> total</span>
                                </h5>
                                <div>
                                    <button type="button" class="btn-modern btn-modern-primary btn-modern-sm" onclick="markAllComplete()">
                                        <i class="fas fa-check-double"></i> Mark Completed
                                    </button>
                                </div>
                            </div>
                            <div class="card-body" style="padding: 0;">
                                <div class="table-wrapper">
                                    <?php if ($number_of_result > 0): ?>
                                        <table class="table-modern" id="appointment_table">
                                            <thead>
                                                <tr>
                                                    <th style="width: 50px;"><input type="checkbox" class="checkbox-modern" onClick="toggle(this)" /></th>
                                                    <th data-type="string" class="sortable">Customer</th>
                                                    <th data-type="string" class="sortable">Enrollment ID</th>
                                                    <th data-type="string" class="sortable"><?= htmlspecialchars($service_provider_title ?? 'Service Provider') ?></th>
                                                    <th data-type="string" class="sortable">Day</th>
                                                    <th data-type="datetime" class="sortable">Date</th>
                                                    <th data-type="time" class="sortable">Time</th>
                                                    <th>Paid</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $i = $page_first_result + 1;
                                                $appointment_data = $db_account->Execute($ALL_APPOINTMENT_QUERY, $page_first_result . ',' . $results_per_page);
                                                $current_date = date('Y-m-d');

                                                while (!$appointment_data->EOF) {
                                                    $date = $appointment_data->fields['DATE'];
                                                    $no_show = $appointment_data->fields['NO_SHOW'];
                                                    $pk_appointment_status = $appointment_data->fields['PK_APPOINTMENT_STATUS'];

                                                    $row_class = '';
                                                    if ($date == $current_date) {
                                                        $row_class = 'row-today';
                                                    } elseif ($no_show == 'Charge' || $no_show == 'No Charge') {
                                                        $row_class = 'row-noshow';
                                                    } elseif ($pk_appointment_status == 6) {
                                                        $row_class = 'row-cancelled';
                                                    }
                                                ?>
                                                    <tr class="<?= $row_class ?>">
                                                        <td>
                                                            <?php if ($appointment_data->fields['CUSTOMER_NAME']) { ?>
                                                                <label>
                                                                    <input type="checkbox" name="PK_APPOINTMENT_MASTER[]" class="PK_APPOINTMENT_MASTER checkbox-modern" value="<?= $appointment_data->fields['PK_APPOINTMENT_MASTER'] ?>">
                                                                </label>
                                                            <?php } else { ?>
                                                                <a href="javascript:" onclick="ConfirmDelete(<?= $appointment_data->fields['PK_APPOINTMENT_MASTER'] ?>)" style="color: var(--danger-color);"><i class="fas fa-trash"></i></a>
                                                            <?php } ?>
                                                        </td>
                                                        <td><?= htmlspecialchars($appointment_data->fields['CUSTOMER_NAME'] ?? '') ?></td>
                                                        <?php if (!empty($appointment_data->fields['ENROLLMENT_ID']) || !empty($appointment_data->fields['ENROLLMENT_NAME'])) { ?>
                                                            <td><?= (($appointment_data->fields['ENROLLMENT_NAME']) ? htmlspecialchars($appointment_data->fields['ENROLLMENT_NAME']) . ' - ' : '') . htmlspecialchars($appointment_data->fields['ENROLLMENT_ID']) . " || " . htmlspecialchars($appointment_data->fields['SERVICE_NAME']) . " || " . htmlspecialchars($appointment_data->fields['SERVICE_CODE']) ?></td>
                                                        <?php } elseif (empty($appointment_data->fields['SERVICE_NAME']) && empty($appointment_data->fields['SERVICE_CODE'])) { ?>
                                                            <td><?= htmlspecialchars($appointment_data->fields['SERVICE_NAME'] . "  " . $appointment_data->fields['SERVICE_CODE']) ?></td>
                                                        <?php } else { ?>
                                                            <td><?= htmlspecialchars($appointment_data->fields['SERVICE_NAME'] . " || " . $appointment_data->fields['SERVICE_CODE']) ?></td>
                                                        <?php } ?>
                                                        <td><?= htmlspecialchars($appointment_data->fields['SERVICE_PROVIDER_NAME'] ?? '') ?></td>
                                                        <td><?= date('l', strtotime($appointment_data->fields['DATE'])) ?></td>
                                                        <td><?= date('m/d/Y', strtotime($appointment_data->fields['DATE'])) ?></td>
                                                        <td><?= date('h:i A', strtotime($appointment_data->fields['START_TIME'])) . " - " . date('h:i A', strtotime($appointment_data->fields['END_TIME'])) ?></td>
                                                        <td>
                                                            <?php if ($appointment_data->fields['IS_PAID'] == 1) { ?>
                                                                <span class="paid-badge paid"><i class="fas fa-check-circle"></i> Paid</span>
                                                            <?php } else { ?>
                                                                <span class="paid-badge unpaid"><i class="fas fa-clock"></i> Unpaid</span>
                                                            <?php } ?>
                                                        </td>
                                                    </tr>
                                                <?php $appointment_data->MoveNext();
                                                    $i++;
                                                } ?>
                                            </tbody>
                                        </table>
                                    <?php else: ?>
                                        <div class="empty-state">
                                            <i class="fas fa-calendar-times"></i>
                                            <h5>No Appointments Found</h5>
                                            <p>There are no appointments matching your criteria.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Pagination -->
                                <?php if ($number_of_page > 1) { ?>
                                    <div class="pagination-modern">
                                        <?php if ($page > 1) { ?>
                                            <a class="page-link" href="operations.php?page=1">
                                                <i class="fas fa-angle-double-left"></i>
                                            </a>
                                            <a class="page-link" href="operations.php?page=<?= ($page - 1) ?>">
                                                <i class="fas fa-angle-left"></i>
                                            </a>
                                        <?php }
                                        for ($page_count = 1; $page_count <= $number_of_page; $page_count++) {
                                            if ($page_count == $page || $page_count == ($page + 1) || $page_count == ($page - 1) || $page_count == $number_of_page) {
                                                $active_class = ($page_count == $page) ? 'active' : '';
                                                echo '<a class="page-link ' . $active_class . '" href="operations.php?page=' . $page_count . (($search_text == '') ? '' : '&search_text=' . urlencode($search_text)) . '">' . $page_count . '</a>';
                                            } elseif ($page_count == ($number_of_page - 1)) {
                                                echo '<span class="page-link dots">...</span>';
                                            } else {
                                                echo '<a class="page-link hidden" href="operations.php?page=' . $page_count . (($search_text == '') ? '' : '&search_text=' . urlencode($search_text)) . '">' . $page_count . '</a>';
                                            }
                                        }
                                        if ($page < $number_of_page) { ?>
                                            <a class="page-link" href="operations.php?page=<?= ($page + 1) ?>">
                                                <i class="fas fa-angle-right"></i>
                                            </a>
                                            <a class="page-link" href="operations.php?page=<?= $number_of_page ?>">
                                                <i class="fas fa-angle-double-right"></i>
                                            </a>
                                        <?php } ?>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <?php require_once('../includes/footer.php'); ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        function selectDate(param) {
            let Date = parseInt($(param).val());

            $('.specific_date').hide();
            $('.from_date').hide();
            $('.end_date').hide();

            if (Date === 4) {
                $('.specific_date').show();
            } else if (Date === 5) {
                $('.from_date').show();
                $('.end_date').show();
            }
        }

        $(document).ready(function() {
            $("#SPECIFIC_DATE").datepicker({
                format: 'mm/dd/yyyy',
                maxDate: 0
            });
            $("#FROM_DATE").datepicker({
                numberOfMonths: 1,
                onSelect: function(selected) {
                    $("#END_DATE").datepicker("option", "minDate", selected);
                    $("#FROM_DATE, #END_DATE").trigger("change");
                }
            });
            $("#END_DATE").datepicker({
                numberOfMonths: 1,
                onSelect: function(selected) {
                    $("#FROM_DATE").datepicker("option", "maxDate", selected);
                }
            });
        });

        function ConfirmDelete(PK_APPOINTMENT_MASTER) {
            Swal.fire({
                title: "Are you sure?",
                text: "You won't be able to revert this!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#39B54A",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, delete it!"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "ajax/AjaxFunctions.php",
                        type: 'POST',
                        data: {
                            FUNCTION_NAME: 'deleteAppointment',
                            PK_APPOINTMENT_MASTER: PK_APPOINTMENT_MASTER
                        },
                        success: function(data) {
                            window.location.href = 'operations.php';
                        }
                    });
                }
            });
        }

        function toggle(source) {
            var checkboxes = document.querySelectorAll('input[name="PK_APPOINTMENT_MASTER[]"]');
            for (var i = 0; i < checkboxes.length; i++) {
                checkboxes[i].checked = source.checked;
            }
        }

        function markAllComplete() {
            let PK_APPOINTMENT_MASTER = [];
            $(".PK_APPOINTMENT_MASTER:checked").each(function() {
                PK_APPOINTMENT_MASTER.push($(this).val());
            });

            if (PK_APPOINTMENT_MASTER.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Selection',
                    text: 'Please select at least one appointment.'
                });
                return;
            }

            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                data: {
                    FUNCTION_NAME: 'markAllAppointmentCompleted',
                    PK_APPOINTMENT_MASTER: PK_APPOINTMENT_MASTER
                },
                success: function(data) {
                    window.location = "operations.php";
                }
            });
        }

        // Sorting
        $(function() {
            $('.sortable').on('click', function() {
                var table = $(this).closest('table');
                var tbody = table.find('tbody');
                var rows = tbody.find('tr').toArray();
                var index = $(this).index();
                var asc = !$(this).hasClass('asc');
                var type = $(this).data('type');

                table.find('.sortable').removeClass('asc desc');
                $(this).addClass(asc ? 'asc' : 'desc');

                rows.sort(function(a, b) {
                    var A = $(a).children('td').eq(index).text().trim();
                    var B = $(b).children('td').eq(index).text().trim();

                    if (type === 'number') {
                        A = parseFloat(A.replace(/[^0-9.\-]/g, '')) || 0;
                        B = parseFloat(B.replace(/[^0-9.\-]/g, '')) || 0;
                    } else if (type === 'datetime') {
                        A = new Date(A);
                        B = new Date(B);
                    } else {
                        A = A.toLowerCase();
                        B = B.toLowerCase();
                    }

                    if (A < B) return asc ? -1 : 1;
                    if (A > B) return asc ? 1 : -1;
                    return 0;
                });

                $.each(rows, function(i, row) {
                    tbody.append(row);
                });
            });
        });

        // Show hidden page numbers
        function showHiddenPageNumber(el) {
            $(el).closest('.pagination-modern').find('.hidden').show();
            $(el).hide();
        }
    </script>
</body>

</html>