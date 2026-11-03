<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;
global $results_per_page;

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];
$LOCATION_ARRAY = explode(',', $_SESSION['DEFAULT_LOCATION_ID']);
$PK_USER_MASTER = $_SESSION['PK_USER_MASTER'];

$service_provider_title = $service_provider_title ?? 'Service Provider';
$operation_tab_title = $operation_tab_title ?? 'Operations';

$status_check = empty($_GET['status']) ? '' : $_GET['status'];
$appointment_time = ' ';
$order_by = " ORDER BY DOA_APPOINTMENT_MASTER.DATE DESC, DOA_APPOINTMENT_MASTER.START_TIME DESC";
if ($status_check == 'previous') {
    $appointment_time = " AND DOA_APPOINTMENT_MASTER.DATE < '" . date('Y-m-d') . "'";
} elseif ($status_check == 'future') {
    $appointment_time = " AND DOA_APPOINTMENT_MASTER.DATE > '" . date('Y-m-d') . "'";
} elseif (empty($_GET['START_DATE']) && empty($_GET['END_DATE']) && empty($_GET['search_text'])) {
    $appointment_time = " AND DOA_APPOINTMENT_MASTER.DATE = '" . date('Y-m-d') . "'";
} elseif (empty($_GET['START_DATE']) && empty($_GET['END_DATE']) && !empty($_GET['search_text'])) {
    $appointment_time = " AND DOA_APPOINTMENT_MASTER.DATE >= '" . date('Y-m-d') . "'";
    $order_by = " ORDER BY DOA_APPOINTMENT_MASTER.DATE ASC, DOA_APPOINTMENT_MASTER.START_TIME ASC";
}

$appointment_status = empty($_GET['appointment_status']) ? '1, 2, 3, 5, 7, 8' : $_GET['appointment_status'];

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 4) {
    header("location:../login.php");
    exit;
}

$START_DATE = ' ';
$END_DATE = ' ';
if (!empty($_GET['START_DATE'])) {
    $START_DATE = " AND DOA_APPOINTMENT_MASTER.DATE >= '" . date('Y-m-d', strtotime($_GET['START_DATE'])) . "'";
}
if (!empty($_GET['END_DATE'])) {
    $END_DATE = " AND DOA_APPOINTMENT_MASTER.DATE <= '" . date('Y-m-d', strtotime($_GET['END_DATE'])) . "'";
}

$search_text = '';
$search = $START_DATE . $END_DATE . ' ';
if (!empty($_GET['search_text'])) {
    $search_text = $_GET['search_text'];
    $search = $START_DATE . $END_DATE . " AND (DOA_ENROLLMENT_MASTER.ENROLLMENT_ID LIKE '%" . $search_text . "%' OR CUSTOMER.FIRST_NAME LIKE '%" . $search_text . "%' OR SERVICE_PROVIDER.FIRST_NAME LIKE '%" . $search_text . "%' OR CUSTOMER.LAST_NAME LIKE '%" . $search_text . "%' OR SERVICE_PROVIDER.LAST_NAME LIKE '%" . $search_text . "%' OR CUSTOMER.EMAIL_ID LIKE '%" . $search_text . "%' OR CUSTOMER.PHONE LIKE '%" . $search_text . "%')";
}

$standing = 0;
$standing_select = ' ';
$standing_cond = ' ';
$standing_group = ' GROUP BY DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER ';
if (isset($_GET['standing'])) {
    if ($_GET['standing'] == 1) {
        $standing = 1;
        $standing_select = ' MIN(DOA_APPOINTMENT_MASTER.DATE) AS BEGINNING_DATE, MAX(DOA_APPOINTMENT_MASTER.DATE) AS END_DATE, ';
        $standing_cond = ' AND DOA_APPOINTMENT_MASTER.STANDING_ID > 0 ';
        $standing_group = " GROUP BY DOA_APPOINTMENT_MASTER.STANDING_ID ";
    } else {
        $standing_cond = ' AND DOA_APPOINTMENT_MASTER.STANDING_ID = 0 ';
    }
}

if ($standing == 1) {
    $title = "Standing Appointments";
    $appointment_time = ' ';
} else {
    if ($status_check == 'previous') {
        $title = "Previous Appointments";
    } elseif ($status_check == 'future') {
        $title = "Future Appointments";
    } else {
        $title = "Today's Appointments";
    }
}

$ALL_APPOINTMENT_QUERY = "SELECT
                            DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_MASTER,
                            $standing_select
                            DOA_APPOINTMENT_MASTER.STANDING_ID,
                            DOA_APPOINTMENT_MASTER.PK_ENROLLMENT_SERVICE,
                            DOA_APPOINTMENT_MASTER.GROUP_NAME,
                            DOA_APPOINTMENT_MASTER.SERIAL_NUMBER,
                            DOA_APPOINTMENT_MASTER.DATE,
                            DOA_APPOINTMENT_MASTER.START_TIME,
                            DOA_APPOINTMENT_MASTER.END_TIME,
                            DOA_APPOINTMENT_MASTER.COMMENT,
                            DOA_APPOINTMENT_MASTER.IMAGE,
                            DOA_APPOINTMENT_MASTER.VIDEO,
                            DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE,
                            DOA_APPOINTMENT_MASTER.IS_PAID,
                            DOA_ENROLLMENT_MASTER.ENROLLMENT_NAME,
                            DOA_ENROLLMENT_MASTER.ENROLLMENT_ID,
                            DOA_SERVICE_MASTER.SERVICE_NAME,
                            DOA_SERVICE_CODE.SERVICE_CODE,
                            DOA_APPOINTMENT_MASTER.IS_PAID,
                            DOA_APPOINTMENT_MASTER.IS_CHARGED,
                            DOA_APPOINTMENT_MASTER.APPOINTMENT_TYPE,
                            DOA_APPOINTMENT_MASTER.PK_APPOINTMENT_STATUS,
                            DOA_APPOINTMENT_STATUS.APPOINTMENT_STATUS,
                            DOA_APPOINTMENT_STATUS.STATUS_CODE,
                            DOA_APPOINTMENT_STATUS.COLOR_CODE AS APPOINTMENT_COLOR,
                            DOA_APPOINTMENT_STATUS.APPOINTMENT_STATUS,
                            DOA_SCHEDULING_CODE.COLOR_CODE,
                            GROUP_CONCAT(DISTINCT(CONCAT(SERVICE_PROVIDER.FIRST_NAME, ' ', SERVICE_PROVIDER.LAST_NAME)) SEPARATOR ', ') AS SERVICE_PROVIDER_NAME,
                            GROUP_CONCAT(DISTINCT(CONCAT(CUSTOMER.FIRST_NAME, ' ', CUSTOMER.LAST_NAME)) SEPARATOR ', ') AS CUSTOMER_NAME
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
                        AND DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS IN ($appointment_status)
                        AND DOA_APPOINTMENT_CUSTOMER.PK_USER_MASTER = '$PK_USER_MASTER'
                        $standing_cond
                        $appointment_time
                        $search
                        $standing_group
                        $order_by";

$query = $db_account->Execute($ALL_APPOINTMENT_QUERY);

$number_of_result =  $query->RecordCount();
$number_of_page = ceil($number_of_result / $results_per_page);

if (!isset($_GET['page'])) {
    $page = 1;
} else {
    $page = $_GET['page'];
}
$page_first_result = ($page - 1) * $results_per_page;

?>

<!DOCTYPE html>
<html lang="en">
<?php include 'layout/header_script.php'; ?>

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

    /* Breadcrumb / Page Title */
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

    .breadcrumb-nav a {
        color: var(--primary-color);
        text-decoration: none;
        font-weight: 500;
        transition: color 0.2s;
    }

    .breadcrumb-nav a:hover {
        color: var(--primary-dark);
    }

    .breadcrumb-nav .separator {
        color: var(--gray-300);
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
    }

    .filter-bar .filter-row .filter-item {
        flex: 1;
        min-width: 150px;
    }

    .filter-bar .filter-row .filter-item.search-item {
        flex: 2;
        min-width: 250px;
    }

    @media (max-width: 768px) {
        .filter-bar .filter-row {
            flex-direction: column;
            align-items: stretch;
        }

        .filter-bar .filter-row .filter-item {
            min-width: 100%;
        }
    }

    .quick-buttons {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-bottom: 16px;
    }

    @media (max-width: 768px) {
        .quick-buttons {
            width: 100%;
        }

        .quick-buttons .btn-modern {
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

    /* Active quick filter button */
    .btn-active {
        background: var(--primary-color);
        border: 1.5px solid var(--primary-color);
        color: #fff !important;
        box-shadow: var(--shadow-sm);
    }

    .btn-active:hover {
        background: var(--primary-dark);
        border-color: var(--primary-dark);
        color: #fff !important;
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
        padding: 0;
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

    .table-modern thead th.sortable.asc::after {
        content: " ▲";
        font-size: 10px;
        color: var(--primary-color);
    }

    .table-modern thead th.sortable.desc::after {
        content: " ▼";
        font-size: 10px;
        color: var(--primary-color);
    }

    .table-modern tbody td {
        padding: 12px 14px;
        border-bottom: 1px solid var(--gray-100);
        color: var(--gray-700);
        vertical-align: middle;
    }

    .table-modern tbody tr.main-row {
        cursor: pointer;
        transition: background 0.15s ease;
    }

    .table-modern tbody tr.main-row:hover {
        background: #F0FDF4;
    }

    .table-modern tbody tr.detail-row {
        background: var(--gray-50);
    }

    .table-modern tbody tr.detail-row td {
        padding: 16px 20px;
        border-bottom: 2px solid var(--gray-200);
    }

    .table-modern tbody tr.header {
        cursor: pointer;
        background: #ffffff;
    }

    .table-modern tbody tr.header:hover {
        background: #F0FDF4;
    }

    /* Status Badge */
    .status-badge-modern {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 12px;
        border-radius: 50px;
        font-size: 12px;
        font-weight: 500;
        white-space: nowrap;
    }

    .appointment-type-badge {
        display: inline-block;
        font-size: 11px;
        font-weight: 500;
        padding: 2px 8px;
        border-radius: 50px;
        background: var(--gray-100);
        color: var(--gray-600);
    }

    .appointment-type-badge.private {
        background: #E0F2FE;
        color: #0369A1;
    }

    .appointment-type-badge.group {
        background: #F3E8FF;
        color: #7E22CE;
    }

    .appointment-type-badge.adhoc {
        background: #FEF3C7;
        color: #92400E;
    }

    .standing-indicator {
        display: inline-block;
        font-size: 11px;
        font-weight: 700;
        color: var(--primary-color);
        background: #D1FAE5;
        padding: 1px 6px;
        border-radius: 4px;
        margin-left: 4px;
    }

    .paid-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 12px;
        font-weight: 500;
    }

    .paid-badge.paid {
        color: #065F46;
    }

    .paid-badge.unpaid {
        color: #92400E;
    }

    .view-btn {
        padding: 4px 12px;
        font-size: 12px;
        font-weight: 500;
        border: none;
        border-radius: 50px;
        background: var(--primary-color);
        color: #fff;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .view-btn:hover {
        background: var(--primary-dark);
        transform: translateY(-1px);
    }

    /* Comment detail section */
    .detail-section {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    @media (max-width: 768px) {
        .detail-section {
            grid-template-columns: 1fr;
        }
    }

    .detail-section .detail-item {
        background: #fff;
        border-radius: var(--radius-sm);
        border: 1px solid var(--gray-200);
        padding: 14px 16px;
    }

    .detail-section .detail-item label {
        font-size: 12px;
        font-weight: 600;
        color: var(--gray-500);
        text-transform: uppercase;
        letter-spacing: 0.03em;
        margin-bottom: 8px;
        display: block;
    }

    .detail-section .detail-item .comment-text {
        font-size: 14px;
        color: var(--gray-700);
        line-height: 1.6;
        white-space: pre-wrap;
        word-break: break-word;
    }

    .detail-section .detail-item .history-text {
        font-size: 12px;
        color: var(--gray-500);
        line-height: 1.8;
        margin-top: 8px;
    }

    .detail-section .detail-item .history-text span {
        display: block;
    }

    .media-preview {
        max-width: 150px;
        height: auto;
        border-radius: var(--radius-sm);
        border: 1px solid var(--gray-200);
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

    /* Floating Action Buttons */
    .floating-actions {
        position: fixed;
        bottom: 24px;
        left: 50%;
        transform: translateX(-50%);
        display: flex;
        gap: 10px;
        z-index: 100;
        flex-wrap: wrap;
        justify-content: center;
    }

    @media (max-width: 768px) {
        .floating-actions {
            bottom: 16px;
            right: 16px;
            left: 16px;
            justify-content: center;
        }

        .floating-actions .btn-modern {
            flex: 1;
            justify-content: center;
            padding: 10px 14px;
            font-size: 13px;
        }
    }

    /* Empty state */
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
</style>

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">


        <div class="page-wrapper" style="padding-top: 0px !important;">
            <div class="container-fluid py-4 px-4 m-auto mx-auto dashboard-container">

                <!-- Filter Bar -->
                <div class="filter-bar">
                    <!-- Quick Filter Buttons -->
                    <div class="quick-buttons">

                        <a href="appointment_list.php?status=previous"
                            class="btn-modern btn-modern-sm <?= ($status_check == 'previous') ? 'btn-active' : 'btn-modern-outline' ?>">
                            <i class="fas fa-history"></i> Previous
                        </a>

                        <a href="appointment_list.php?status=future"
                            class="btn-modern btn-modern-sm <?= ($status_check == 'future') ? 'btn-active' : 'btn-modern-outline' ?>">
                            <i class="fas fa-calendar-plus"></i> Future
                        </a>

                        <?php if ($standing == 0) { ?>
                            <a href="appointment_list.php?standing=1"
                                class="btn-modern btn-modern-sm <?= ($standing == 1) ? 'btn-active' : 'btn-modern-outline' ?>">
                                <i class="fas fa-redo"></i> Standing
                            </a>
                        <?php } else { ?>
                            <a href="appointment_list.php"
                                class="btn-modern btn-modern-sm btn-active">
                                <i class="fas fa-list"></i> Normal
                            </a>
                        <?php } ?>

                    </div>

                    <!-- Search Form -->
                    <form id="search_form" action="" method="get">
                        <div class="filter-row">
                            <div class="filter-item">
                                <select class="form-control-modern" name="appointment_status" id="appointment_status" onchange="$('#search_form').submit()">
                                    <option value="">All Statuses</option>
                                    <?php
                                    $row = $db->Execute("SELECT * FROM DOA_APPOINTMENT_STATUS WHERE ACTIVE = 1");
                                    while (!$row->EOF) { ?>
                                        <option value="<?php echo $row->fields['PK_APPOINTMENT_STATUS']; ?>" <?= ($row->fields['PK_APPOINTMENT_STATUS'] == $appointment_status) ? "selected" : "" ?>><?= htmlspecialchars($row->fields['APPOINTMENT_STATUS']) ?></option>
                                    <?php $row->MoveNext();
                                    } ?>
                                </select>
                            </div>
                            <div class="filter-item">
                                <input type="text" id="START_DATE" name="START_DATE" class="form-control-modern datepicker-normal" placeholder="Start Date" value="<?= !empty($_GET['START_DATE']) ? htmlspecialchars($_GET['START_DATE']) : '' ?>">
                            </div>
                            <div class="filter-item">
                                <input type="text" id="END_DATE" name="END_DATE" class="form-control-modern datepicker-normal" placeholder="End Date" value="<?= !empty($_GET['END_DATE']) ? htmlspecialchars($_GET['END_DATE']) : '' ?>">
                            </div>
                            <div class="filter-item search-item">
                                <input class="form-control-modern" type="text" id="search_text" name="search_text" placeholder="Search by name, email, phone, enrollment ID..." value="<?= htmlspecialchars($search_text) ?>">
                            </div>
                            <div class="filter-item" style="flex: 0;">
                                <button type="submit" class="btn-modern btn-modern-primary">
                                    <i class="fas fa-search"></i> Search
                                </button>
                            </div>
                            <?php if (!empty($search_text) || !empty($_GET['START_DATE']) || !empty($_GET['END_DATE'])): ?>
                                <div class="filter-item" style="flex: 0;">
                                    <a href="appointment_list.php" class="btn-modern btn-modern-secondary">
                                        <i class="fas fa-times"></i> Clear
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <!-- Appointments Table -->
                <div class="card-modern">
                    <div class="card-header">
                        <h5>
                            <i class="fas fa-list"></i>
                            <?= $title ?>
                            <span style="background: var(--gray-200); color: var(--gray-600); padding: 2px 12px; border-radius: 50px; font-size: 13px; font-weight: 500;"><?= $number_of_result ?> total</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-wrapper">
                            <?php if ($number_of_result > 0): ?>
                                <table class="table-modern">
                                    <thead>
                                        <tr>
                                            <th data-type="number" class="sortable" style="width: 50px;">No</th>
                                            <th data-type="string" class="sortable">Service Name</th>
                                            <th data-type="string" class="sortable">Class Name</th>
                                            <th data-type="string" class="sortable">Customer</th>
                                            <th data-type="string" class="sortable">Enrollment ID</th>
                                            <th data-type="string" class="sortable"><?= htmlspecialchars($service_provider_title) ?></th>
                                            <th data-type="string" class="sortable">Day</th>
                                            <th data-date class="sortable">Date</th>
                                            <th data-type="string" class="sortable">Time</th>
                                            <th data-type="string" class="sortable">Comment &amp; Uploads</th>
                                            <th>Paid</th>
                                            <th style="width: 10%;">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $i = $page_first_result + 1;
                                        $appointment_data = $db_account->Execute($ALL_APPOINTMENT_QUERY, $page_first_result . ',' . $results_per_page);
                                        while (!$appointment_data->EOF) {
                                            $IMAGE_LINK = $appointment_data->fields['IMAGE'];
                                            $VIDEO_LINK = $appointment_data->fields['VIDEO'];
                                            $CHANGED_BY = '';

                                            if ($standing == 0) {
                                                $status_data = $db_account->Execute("SELECT DOA_APPOINTMENT_STATUS.APPOINTMENT_STATUS, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_APPOINTMENT_STATUS_HISTORY.TIME_STAMP FROM DOA_APPOINTMENT_STATUS_HISTORY LEFT JOIN $master_database.DOA_APPOINTMENT_STATUS AS DOA_APPOINTMENT_STATUS ON DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS=DOA_APPOINTMENT_STATUS_HISTORY.PK_APPOINTMENT_STATUS LEFT JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USERS.PK_USER=DOA_APPOINTMENT_STATUS_HISTORY.PK_USER WHERE PK_APPOINTMENT_MASTER = " . $appointment_data->fields['PK_APPOINTMENT_MASTER']);
                                                while (!$status_data->EOF) {
                                                    $CHANGED_BY .= "(" . $status_data->fields['APPOINTMENT_STATUS'] . " by " . $status_data->fields['NAME'] . " at " . date('m-d-Y H:i:s A', strtotime($status_data->fields['TIME_STAMP'])) . ")<br>";
                                                    $status_data->MoveNext();
                                                }
                                            }

                                            // Determine appointment type badge
                                            $type_label = '';
                                            $type_class = '';
                                            if ($appointment_data->fields['APPOINTMENT_TYPE'] == 'NORMAL') {
                                                $type_label = 'Private Session';
                                                $type_class = 'private';
                                            } elseif ($appointment_data->fields['APPOINTMENT_TYPE'] == 'AD-HOC') {
                                                $type_label = 'Ad-Hoc';
                                                $type_class = 'adhoc';
                                            } else {
                                                $type_label = 'Group Class';
                                                $type_class = 'group';
                                            }
                                        ?>
                                            <tr class="main-row" onclick="$(this).next().slideToggle();">
                                                <td><?= $i; ?></td>
                                                <td>
                                                    <span class="appointment-type-badge <?= $type_class ?>"><?= $type_label ?></span>
                                                    <?php if ($appointment_data->fields['STANDING_ID'] > 0) { ?>
                                                        <span class="standing-indicator">S</span>
                                                    <?php } ?>
                                                </td>
                                                <td><?= htmlspecialchars($appointment_data->fields['GROUP_NAME'] ?? '') ?></td>
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
                                                <td style="text-align: center;">
                                                    <?php if ($appointment_data->fields['COMMENT'] != '' || $IMAGE_LINK != '' || $VIDEO_LINK != '' || $CHANGED_BY != '') { ?>
                                                        <button class="view-btn">View</button>
                                                    <?php } ?>
                                                </td>
                                                <td>
                                                    <?php if ($appointment_data->fields['IS_PAID'] == 1) { ?>
                                                        <span class="paid-badge paid"><i class="fas fa-check-circle"></i> Paid</span>
                                                    <?php } else { ?>
                                                        <span class="paid-badge unpaid"><i class="fas fa-clock"></i> Unpaid</span>
                                                    <?php } ?>
                                                </td>
                                                <td>
                                                    <span style="color: <?= $appointment_data->fields['APPOINTMENT_COLOR'] ?>; font-weight: 500;">
                                                        <?= htmlspecialchars($appointment_data->fields['APPOINTMENT_STATUS'] ?? '') ?>
                                                    </span>
                                                    <?php if ($appointment_data->fields['IS_CHARGED'] == 1) { ?>
                                                        <i class="fas fa-dollar-sign" style="color: var(--primary-color); margin-left: 4px;" title="Charged"></i>
                                                    <?php } ?>
                                                </td>
                                            </tr>

                                            <tr class="detail-row" style="display: none;">
                                                <td colspan="12">
                                                    <div class="detail-section">
                                                        <!-- Comment -->
                                                        <div class="detail-item">
                                                            <label><i class="fas fa-comment"></i> Comment</label>
                                                            <div class="comment-text"><?= !empty($appointment_data->fields['COMMENT']) ? nl2br(htmlspecialchars($appointment_data->fields['COMMENT'])) : '<span style="color: var(--gray-400);">No comment</span>' ?></div>
                                                            <?php if ($CHANGED_BY != '') { ?>
                                                                <div class="history-text">
                                                                    <strong style="display: block; margin-bottom: 4px; color: var(--gray-600);">Status History:</strong>
                                                                    <?= $CHANGED_BY ?>
                                                                </div>
                                                            <?php } ?>
                                                        </div>

                                                        <!-- Uploads -->
                                                        <div class="detail-item">
                                                            <label><i class="fas fa-paperclip"></i> Uploads</label>
                                                            <div style="display: flex; gap: 16px; flex-wrap: wrap;">
                                                                <?php if ($IMAGE_LINK != '' && $IMAGE_LINK != null) { ?>
                                                                    <a href="<?= htmlspecialchars($IMAGE_LINK) ?>" target="_blank">
                                                                        <img src="<?= htmlspecialchars($IMAGE_LINK) ?>" class="media-preview" alt="Appointment Image">
                                                                    </a>
                                                                <?php } ?>
                                                                <?php if ($VIDEO_LINK != '' && $VIDEO_LINK != null) { ?>
                                                                    <a href="<?= htmlspecialchars($VIDEO_LINK) ?>" target="_blank">
                                                                        <video width="240" height="135" controls style="border-radius: var(--radius-sm); border: 1px solid var(--gray-200);">
                                                                            <source src="<?= htmlspecialchars($VIDEO_LINK) ?>" type="video/mp4">
                                                                        </video>
                                                                    </a>
                                                                <?php } ?>
                                                                <?php if (($IMAGE_LINK == '' || $IMAGE_LINK == null) && ($VIDEO_LINK == '' || $VIDEO_LINK == null)) { ?>
                                                                    <span style="color: var(--gray-400);">No uploads</span>
                                                                <?php } ?>
                                                            </div>
                                                        </div>
                                                    </div>
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
                                    <a class="page-link" href="appointment_list.php?status=<?= $status_check ?>&appointment_status=<?= $appointment_status ?>&page=1">
                                        <i class="fas fa-angle-double-left"></i>
                                    </a>
                                    <a class="page-link" href="appointment_list.php?status=<?= $status_check ?>&appointment_status=<?= $appointment_status ?>&page=<?= ($page - 1) ?>">
                                        <i class="fas fa-angle-left"></i>
                                    </a>
                                <?php }
                                for ($page_count = 1; $page_count <= $number_of_page; $page_count++) {
                                    if ($page_count == $page || $page_count == ($page + 1) || $page_count == ($page - 1) || $page_count == $number_of_page) {
                                        $active_class = ($page_count == $page) ? 'active' : '';
                                        echo '<a class="page-link ' . $active_class . '" href="appointment_list.php?status=' . $status_check . '&appointment_status=' . $appointment_status . '&page=' . $page_count . (($search_text == '') ? '' : '&search_text=' . urlencode($search_text)) . '">' . $page_count . '</a>';
                                    } elseif ($page_count == ($number_of_page - 1)) {
                                        echo '<span class="page-link dots">...</span>';
                                    } else {
                                        echo '<a class="page-link hidden" href="appointment_list.php?status=' . $status_check . '&appointment_status=' . $appointment_status . '&page=' . $page_count . (($search_text == '') ? '' : '&search_text=' . urlencode($search_text)) . '">' . $page_count . '</a>';
                                    }
                                }
                                if ($page < $number_of_page) { ?>
                                    <a class="page-link" href="appointment_list.php?status=<?= $status_check ?>&appointment_status=<?= $appointment_status ?>&page=<?= ($page + 1) ?>">
                                        <i class="fas fa-angle-right"></i>
                                    </a>
                                    <a class="page-link" href="appointment_list.php?status=<?= $status_check ?>&appointment_status=<?= $appointment_status ?>&page=<?= $number_of_page ?>">
                                        <i class="fas fa-angle-double-right"></i>
                                    </a>
                                <?php } ?>
                            </div>
                        <?php } ?>
                    </div>
                </div>

            </div>
        </div>

        <!-- Floating Action Buttons -->
        <div class="floating-actions">
            <button type="button" id="appointments" class="btn-modern btn-modern-primary" onclick="showMessage()">
                <i class="fas fa-plus-circle"></i> Appointments
            </button>
            <button type="button" id="operations" class="btn-modern btn-modern-secondary" onclick="window.location.href='operations.php'">
                <i class="fas fa-layer-group"></i> <?= htmlspecialchars($operation_tab_title) ?>
            </button>
        </div>
    </div>

    <?php require_once('../includes/footer.php'); ?>

    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>

    <script>
        // Date picker range
        $(function() {
            $("#START_DATE").datepicker({
                numberOfMonths: 1,
                onSelect: function(selected) {
                    $("#END_DATE").datepicker("option", "minDate", selected);
                    $("#START_DATE, #END_DATE").trigger("change");
                }
            });
            $("#END_DATE").datepicker({
                numberOfMonths: 1,
                onSelect: function(selected) {
                    $("#START_DATE").datepicker("option", "maxDate", selected);
                }
            });
        });

        // Sortable table
        $(document).ready(function() {
            $(".sortable").on("click", function() {
                var table = $(this).closest("table");
                var tbody = table.find("tbody");
                var rows = tbody.find("tr.main-row").toArray();
                var index = $(this).index();
                var asc = !$(this).hasClass("asc");
                var isDate = $(this).is("[data-date]");
                var type = $(this).data("type");

                // Remove old sorting indicators
                table.find(".sortable").removeClass("asc desc");
                $(this).addClass(asc ? "asc" : "desc");

                rows.sort(function(a, b) {
                    var A = $(a).children("td").eq(index).text().trim();
                    var B = $(b).children("td").eq(index).text().trim();

                    if (isDate) {
                        A = new Date(A);
                        B = new Date(B);
                    } else if (type === "number") {
                        A = parseFloat(A.replace(/[^0-9.\-]/g, "")) || 0;
                        B = parseFloat(B.replace(/[^0-9.\-]/g, "")) || 0;
                    } else {
                        A = A.toLowerCase();
                        B = B.toLowerCase();
                    }

                    if (A < B) return asc ? -1 : 1;
                    if (A > B) return asc ? 1 : -1;
                    return 0;
                });

                // Append sorted rows (also move their detail rows)
                $.each(rows, function(i, row) {
                    tbody.append(row);
                    tbody.append($(row).next('.detail-row'));
                });
            });
        });

        // Show message for multiple locations
        function showMessage() {
            if (<?= count($LOCATION_ARRAY) ?> === 1) {
                window.location.href = 'create_appointment.php';
            } else {
                swal("Select One Location!", "Only one location can be selected on top of the page in order to schedule an appointment.", "error");
            }
        }

        // Show standing appointment details
        function showStandingAppointmentDetails(param, STANDING_ID, PK_APPOINTMENT_MASTER) {
            let $nextRows = $(param).nextUntil('tr.header');

            if ($nextRows.length) {
                $nextRows.remove();
            } else {
                $.ajax({
                    url: "pagination/get_standing_appointment.php",
                    type: 'GET',
                    data: {
                        STANDING_ID: STANDING_ID,
                        PK_APPOINTMENT_MASTER: PK_APPOINTMENT_MASTER
                    },
                    success: function(result) {
                        $(result).insertAfter($(param).closest('tr'));
                    }
                });
            }
        }

        // Confirm delete
        function ConfirmDelete(PK_APPOINTMENT_MASTER, type) {
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
                            type: type,
                            PK_APPOINTMENT_MASTER: PK_APPOINTMENT_MASTER
                        },
                        success: function(data) {
                            let currentURL = window.location.href;
                            let extractedPart = currentURL.substring(currentURL.lastIndexOf("/") + 1);
                            window.location.href = extractedPart;
                        }
                    });
                }
            });
        }

        // Select status
        function selectStatus(param) {
            var status = $(param).val();
            window.location.href = "appointment_list.php?appointment_status=" + status;
        }

        // Show hidden page numbers
        function showHiddenPageNumber(el) {
            $(el).closest('.pagination-modern').find('.hidden').show();
            $(el).hide();
        }
    </script>
</body>

</html>