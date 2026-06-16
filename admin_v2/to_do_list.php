<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;
global $results_per_page;

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

$date_start = '';
$date_end = '';

$START_DATE = ' ';
$END_DATE = ' ';

if (empty($_GET['START_DATE']) && empty($_GET['END_DATE']) && empty($_GET['search_text'])) {
    $START_DATE = " AND DOA_SPECIAL_APPOINTMENT.DATE = '" . date('Y-m-d') . "'";
}

$appointment_status = empty($_GET['appointment_status']) ? '1, 2, 3, 5, 7, 8' : $_GET['appointment_status'];

if (!empty($_GET['START_DATE'])) {
    $date_start = $_GET['START_DATE'];
    $START_DATE = " AND DOA_SPECIAL_APPOINTMENT.DATE >= '" . date('Y-m-d', strtotime($_GET['START_DATE'])) . "'";
}
if (!empty($_GET['END_DATE'])) {
    $date_end = $_GET['END_DATE'];
    $END_DATE = " AND DOA_SPECIAL_APPOINTMENT.DATE <= '" . date('Y-m-d', strtotime($_GET['END_DATE'])) . "'";
}

$search_text = '';
$search = $START_DATE . $END_DATE . ' ';
if (!empty($_GET['search_text'])) {
    $search_text = $_GET['search_text'];
    $search = $START_DATE . $END_DATE . " AND (DOA_SPECIAL_APPOINTMENT.TITLE LIKE '%" . $search_text . "%') ";
}

$standing = 0;
$standing_cond = ' ';
if (isset($_GET['standing'])) {
    if ($_GET['standing'] == 1) {
        $standing = 1;
        $standing_cond = ' AND DOA_SPECIAL_APPOINTMENT.STANDING_ID > 0 ';
    } else {
        $standing_cond = '  ';
    }
}

if ($standing == 1) {
    $title = "All Standing To-Do";
} else {
    $title = "All To-Do";
}

// Different queries for standing and normal view
if ($standing == 1) {
    // STANDING VIEW - Group by STANDING_ID
    $SPECIAL_APPOINTMENT_QUERY = "SELECT
                                        DOA_SPECIAL_APPOINTMENT.*,
                                        DOA_APPOINTMENT_STATUS.APPOINTMENT_STATUS,
                                        DOA_APPOINTMENT_STATUS.STATUS_CODE,
                                        DOA_APPOINTMENT_STATUS.COLOR_CODE AS APPOINTMENT_COLOR,
                                        DOA_SCHEDULING_CODE.COLOR_CODE,
                                        DOA_SCHEDULING_CODE.DURATION,
                                        GROUP_CONCAT(DISTINCT(CONCAT(SERVICE_PROVIDER.FIRST_NAME, ' ', SERVICE_PROVIDER.LAST_NAME)) SEPARATOR ', ') AS SERVICE_PROVIDER_NAME,
                                        GROUP_CONCAT(DISTINCT(CONCAT(CUSTOMER.FIRST_NAME, ' ', CUSTOMER.LAST_NAME)) SEPARATOR ', ') AS CUSTOMER_NAME
                                    FROM
                                        `DOA_SPECIAL_APPOINTMENT`
                                    LEFT JOIN DOA_SPECIAL_APPOINTMENT_USER ON DOA_SPECIAL_APPOINTMENT.PK_SPECIAL_APPOINTMENT = DOA_SPECIAL_APPOINTMENT_USER.PK_SPECIAL_APPOINTMENT
                                    LEFT JOIN $master_database.DOA_USERS AS SERVICE_PROVIDER ON DOA_SPECIAL_APPOINTMENT_USER.PK_USER = SERVICE_PROVIDER.PK_USER
                                            
                                    LEFT JOIN DOA_SPECIAL_APPOINTMENT_CUSTOMER ON DOA_SPECIAL_APPOINTMENT.PK_SPECIAL_APPOINTMENT = DOA_SPECIAL_APPOINTMENT_CUSTOMER.PK_SPECIAL_APPOINTMENT
                                    LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_SPECIAL_APPOINTMENT_CUSTOMER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER
                                    LEFT JOIN $master_database.DOA_USERS AS CUSTOMER ON DOA_USER_MASTER.PK_USER = CUSTOMER.PK_USER
                                            
                                    LEFT JOIN DOA_MASTER.DOA_APPOINTMENT_STATUS AS DOA_APPOINTMENT_STATUS ON DOA_SPECIAL_APPOINTMENT.PK_APPOINTMENT_STATUS = DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS
                                    LEFT JOIN DOA_SCHEDULING_CODE ON DOA_SCHEDULING_CODE.PK_SCHEDULING_CODE = DOA_SPECIAL_APPOINTMENT.PK_SCHEDULING_CODE
                                    WHERE DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS IN ($appointment_status)
                                    AND DOA_SPECIAL_APPOINTMENT.PK_LOCATION IN ($DEFAULT_LOCATION_ID)
                                    " . $standing_cond . $search . "
                                    GROUP BY DOA_SPECIAL_APPOINTMENT.STANDING_ID
                                    ORDER BY DOA_SPECIAL_APPOINTMENT.DATE ASC, DOA_SPECIAL_APPOINTMENT.START_TIME ASC";
} else {
    // NORMAL VIEW - Group by PK_SPECIAL_APPOINTMENT (each appointment individually)
    $SPECIAL_APPOINTMENT_QUERY = "SELECT
                                        DOA_SPECIAL_APPOINTMENT.*,
                                        DOA_APPOINTMENT_STATUS.APPOINTMENT_STATUS,
                                        DOA_APPOINTMENT_STATUS.STATUS_CODE,
                                        DOA_APPOINTMENT_STATUS.COLOR_CODE AS APPOINTMENT_COLOR,
                                        DOA_SCHEDULING_CODE.COLOR_CODE,
                                        DOA_SCHEDULING_CODE.DURATION,
                                        GROUP_CONCAT(DISTINCT(CONCAT(SERVICE_PROVIDER.FIRST_NAME, ' ', SERVICE_PROVIDER.LAST_NAME)) SEPARATOR ', ') AS SERVICE_PROVIDER_NAME,
                                        GROUP_CONCAT(DISTINCT(CONCAT(CUSTOMER.FIRST_NAME, ' ', CUSTOMER.LAST_NAME)) SEPARATOR ', ') AS CUSTOMER_NAME
                                    FROM
                                        `DOA_SPECIAL_APPOINTMENT`
                                    LEFT JOIN DOA_SPECIAL_APPOINTMENT_USER ON DOA_SPECIAL_APPOINTMENT.PK_SPECIAL_APPOINTMENT = DOA_SPECIAL_APPOINTMENT_USER.PK_SPECIAL_APPOINTMENT
                                    LEFT JOIN $master_database.DOA_USERS AS SERVICE_PROVIDER ON DOA_SPECIAL_APPOINTMENT_USER.PK_USER = SERVICE_PROVIDER.PK_USER
                                            
                                    LEFT JOIN DOA_SPECIAL_APPOINTMENT_CUSTOMER ON DOA_SPECIAL_APPOINTMENT.PK_SPECIAL_APPOINTMENT = DOA_SPECIAL_APPOINTMENT_CUSTOMER.PK_SPECIAL_APPOINTMENT
                                    LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_SPECIAL_APPOINTMENT_CUSTOMER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER
                                    LEFT JOIN $master_database.DOA_USERS AS CUSTOMER ON DOA_USER_MASTER.PK_USER = CUSTOMER.PK_USER
                                            
                                    LEFT JOIN DOA_MASTER.DOA_APPOINTMENT_STATUS AS DOA_APPOINTMENT_STATUS ON DOA_SPECIAL_APPOINTMENT.PK_APPOINTMENT_STATUS = DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS
                                    LEFT JOIN DOA_SCHEDULING_CODE ON DOA_SCHEDULING_CODE.PK_SCHEDULING_CODE = DOA_SPECIAL_APPOINTMENT.PK_SCHEDULING_CODE
                                    WHERE DOA_APPOINTMENT_STATUS.PK_APPOINTMENT_STATUS IN ($appointment_status)
                                    AND DOA_SPECIAL_APPOINTMENT.PK_LOCATION IN ($DEFAULT_LOCATION_ID)
                                    " . $standing_cond . $search . "
                                    GROUP BY DOA_SPECIAL_APPOINTMENT.PK_SPECIAL_APPOINTMENT
                                    ORDER BY DOA_SPECIAL_APPOINTMENT.DATE ASC, DOA_SPECIAL_APPOINTMENT.START_TIME ASC";
}

$query = $db_account->Execute($SPECIAL_APPOINTMENT_QUERY);

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
<?php require_once('../includes/header.php'); ?>
<?php include 'layout/header.php'; ?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?> - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="//maxcdn.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Inter', sans-serif;
            color: #333;
        }

        .dashboard-container {
            background: #ffffff;
            border: 1px solid #e9ecef;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
            margin-top: 15px !important;
            margin-bottom: 2rem;
            overflow: hidden;
        }

        /* ===== UNIFIED STYLING - ALL INPUTS, SELECTS & BUTTONS MATCH ===== */
        .staff-dropdown,
        .filter-btn,
        .history-btn,
        .search-input,
        .date-input {
            border-radius: 10px !important;
            border: 1px solid #e2e8f0 !important;
            background: #ffffff !important;
            font-size: 14px !important;
            padding: 0.375rem 0.75rem !important;
            height: 40px !important;
            transition: all 0.3s ease !important;
            color: #333 !important;
        }

        .staff-dropdown:focus,
        .filter-btn:focus,
        .history-btn:focus,
        .search-input:focus,
        .date-input:focus {
            border-color: #39b54a !important;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1) !important;
            outline: none !important;
            background: #ffffff !important;
        }

        .staff-dropdown::placeholder,
        .search-input::placeholder,
        .date-input::placeholder {
            color: #a0aec0 !important;
        }

        /* Search wrapper */
        .search-wrapper {
            position: relative;
            flex: 1;
            min-width: 200px;
        }

        .search-wrapper .bi-search {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #a0aec0;
            z-index: 2;
        }

        .search-wrapper .search-input {
            padding-left: 2.5rem !important;
            width: 100%;
        }

        /* Date inputs */
        .date-input-group {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .date-input-group .date-input {
            width: 130px;
        }

        .date-input-group span {
            color: #a0aec0;
            font-weight: 500;
        }

        /* Filter buttons - same as dropdown */
        .filter-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            background: #ffffff !important;
        }

        .filter-btn:hover {
            background: #f8fafc !important;
            border-color: #cbd5e0 !important;
        }

        .staff-dropdown {
            cursor: pointer;
            background: #ffffff !important;
            min-width: 140px;
            appearance: auto;
        }

        /* Action buttons container */
        .filter-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .history-btn {
            border-radius: 50% !important;
            width: 40px;
            height: 40px;
            padding: 0 !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        /* ===== END UNIFIED STYLING ===== */

        .avatar-stack {
            display: flex;
            align-items: center;
        }

        .avatar-badge {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 600;
            margin-right: -6px;
            border: 2px solid #fff;
        }

        .bg-n {
            background-color: #ffdad9;
            color: #cc3a35;
        }

        .bg-s {
            background-color: #ffe8d6;
            color: #b76e00;
        }

        .bg-a {
            background-color: #e0f2fe;
            color: #0369a1;
        }

        .bg-e {
            background-color: #dbeafe;
            color: #1d4ed8;
        }

        .todo-table {
            width: 100%;
            border-collapse: collapse;
        }

        .todo-table th {
            background-color: #f8fafc;
            color: #718096;
            font-weight: 500;
            font-size: 13px;
            padding: 12px 16px;
            border-bottom: 1px solid #edf2f7;
        }

        .todo-table td {
            padding: 16px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
            font-size: 14px;
        }

        .date-sidebar {
            text-align: center;
            font-weight: bold;
            color: #718096;
            border-right: 1px solid #edf2f7;
            width: 80px;
            background-color: #fff;
        }

        .date-day {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #a0aec0;
            margin-bottom: 2px;
        }

        .date-number {
            font-size: 24px;
            color: #1a202c;
            line-height: 1;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            border: none;
            cursor: pointer;
        }

        .status-not-started {
            background-color: #e2e8f0;
            color: #4a5568;
        }

        .status-in-progress {
            background-color: #fef3c7;
            color: #d97706;
        }

        .status-complete {
            background-color: #dcfce7;
            color: #16a34a;
        }

        .status-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            border: 2px solid currentColor;
            background-color: transparent;
            position: relative;
        }

        .status-dot::after {
            content: "";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 4px;
            height: 4px;
            border-radius: 50%;
        }

        .dot-not-started::after {
            background-color: #4a5568;
        }

        .dot-in-progress::after {
            background-color: #d97706;
        }

        .dot-complete::after {
            background-color: #16a34a;
        }

        .provider-img {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 8px;
        }

        .task-desc {
            color: #4a5568;
            max-width: 350px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sort-icon {
            font-size: 11px;
            color: #a0aec0;
            margin-left: 4px;
            cursor: pointer;
        }

        .pagination ul {
            display: flex;
            justify-content: center;
            list-style: none;
            padding: 20px 0;
            gap: 5px;
        }

        .pagination ul li a {
            padding: 8px 12px;
            border: 1px solid #dee2e6;
            color: #007bff;
            text-decoration: none;
            border-radius: 4px;
        }

        .pagination ul li a.active {
            background-color: #007bff;
            color: white;
        }

        .sortable.asc::after {
            content: " ▲";
        }

        .sortable.desc::after {
            content: " ▼";
        }

        th {
            cursor: pointer;
        }

        .standing-row {
            cursor: pointer;
        }

        .standing-row:hover {
            background-color: #f8fafc;
        }

        .avatarname {
            display: inline-block;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            text-align: center;
            line-height: 28px;
            font-size: 11px;
            font-weight: 600;
            margin-right: 6px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .filter-actions {
                flex-direction: column;
                width: 100%;
            }

            .date-input-group {
                width: 100%;
            }

            .date-input-group .date-input {
                width: 100%;
            }

            .search-wrapper {
                width: 100%;
            }

            .staff-dropdown {
                width: 100%;
            }
        }
    </style>
</head>

<body class="skin-default-dark fixed-layout">

    <div id="main-wrapper">
        <div class="page-wrapper" style="padding-top: 1px !important;">

            <div class="container-fluid py-4 px-4 m-auto mx-auto dashboard-container">

                <!-- Header -->
                <div class="p-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn history-btn p-2 d-inline-flex align-items-center justify-content-center">
                                <i class="bi bi-clock-history fs-5 text-secondary"></i>
                            </button>
                            <div>
                                <h1 class="h4 mb-0 fw-bold"><?= $title ?></h1>
                                <p class="text-muted small mb-0">Manage your tasks and appointments</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <form class="form-material form-horizontal" id="search_form" action="" method="get">
                    <input type="hidden" name="standing" id="standing" value="<?= $standing ?>">
                    <input type="hidden" name="page" id="page" value="1">

                    <div class="px-4 pb-3">
                        <div class="d-flex align-items-center gap-2 flex-nowrap">

                            <!-- Search -->
                            <div class="search-wrapper" style="min-width: 250px;">
                                <i class="bi bi-search"></i>
                                <input type="text"
                                    class="form-control search-input"
                                    name="search_text"
                                    id="search_text"
                                    placeholder="Search by title..."
                                    value="<?= htmlspecialchars($search_text) ?>">
                            </div>

                            <!-- Date Range -->
                            <div class="date-input-group d-flex align-items-center gap-2">
                                <input type="text"
                                    id="START_DATE"
                                    name="START_DATE"
                                    class="form-control date-input datepicker-normal"
                                    placeholder="Start Date"
                                    style="width: 130px;"
                                    value="<?= !empty($_GET['START_DATE']) ? $_GET['START_DATE'] : '' ?>">

                                <span>to</span>

                                <input type="text"
                                    id="END_DATE"
                                    name="END_DATE"
                                    class="form-control date-input datepicker-normal"
                                    placeholder="End Date"
                                    style="width: 130px;"
                                    value="<?= !empty($_GET['END_DATE']) ? $_GET['END_DATE'] : '' ?>">
                            </div>

                            <!-- Status -->
                            <select class="form-select staff-dropdown"
                                name="appointment_status"
                                id="appointment_status"
                                style="width: 180px;"
                                onchange="$('#search_form').submit()">
                                <option value="">All Status</option>
                                <?php
                                $row = $db->Execute("SELECT * FROM DOA_APPOINTMENT_STATUS WHERE ACTIVE = 1");
                                while (!$row->EOF) { ?>
                                    <option value="<?= $row->fields['PK_APPOINTMENT_STATUS']; ?>"
                                        <?= ($row->fields['PK_APPOINTMENT_STATUS'] == $appointment_status) ? "selected" : "" ?>>
                                        <?= $row->fields['APPOINTMENT_STATUS'] ?>
                                    </option>
                                <?php $row->MoveNext();
                                } ?>
                            </select>

                            <!-- Buttons -->
                            <?php if ($standing == 0) { ?>
                                <button type="button" class="btn filter-btn"
                                    onclick="$('#standing').val(1); $('#search_form').submit();">
                                    Show Standing
                                </button>
                            <?php } else { ?>
                                <button type="button" class="btn filter-btn"
                                    onclick="$('#standing').val(''); $('#search_form').submit();">
                                    Show Normal
                                </button>
                            <?php } ?>

                            <button type="submit" class="btn filter-btn">
                                <i class="bi bi-sliders"></i> Filter
                            </button>

                            <a href="to_do_list.php" class="btn filter-btn">
                                <i class="bi bi-arrow-repeat"></i> Reset
                            </a>

                        </div>
                    </div>
                </form>

                <!-- Table -->
                <div class="table-responsive px-4 py-2">
                    <div class="border rounded-2">
                        <table class="todo-table" id="to_do_list">
                            <thead>
                                <tr>
                                    <th style="width: 80px; background: #fff; border-bottom: 0; border-right: 1px solid #f1f5f9;" class="sortable" data-type="date">Date</th>
                                    <th class="sortable" data-type="string">Title</th>
                                    <th class="sortable" data-type="string" style="text-align: center;">Time</th>
                                    <th class="sortable" data-type="string" style="text-align: center;">Status</th>
                                    <th class="sortable" data-type="string" style="text-align: center;">Service Provider</th>
                                    <th style="text-align: center;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $special_appointment_data = $db_account->Execute($SPECIAL_APPOINTMENT_QUERY);

                                if ($special_appointment_data->RecordCount() > 0) {
                                    $special_appointment_data->Move($page_first_result);
                                    $counter = 0;
                                    while (!$special_appointment_data->EOF && $counter < $results_per_page) {
                                        if ($standing == 1 && $special_appointment_data->fields['STANDING_ID'] > 0) {
                                            $standing_date = $db_account->Execute("SELECT MIN(DOA_SPECIAL_APPOINTMENT.DATE) AS BEGINNING_DATE, MAX(DOA_SPECIAL_APPOINTMENT.DATE) AS END_DATE FROM `DOA_SPECIAL_APPOINTMENT` WHERE STANDING_ID = " . $special_appointment_data->fields['STANDING_ID']);
                                        }

                                        if ($standing == 0) {
                                            // NORMAL VIEW - Display individual appointments
                                            $display_date = $special_appointment_data->fields['DATE'];
                                            $date_display = date('m/d/Y', strtotime($special_appointment_data->fields['DATE']));
                                            $day_name = date('l', strtotime($display_date));
                                            $day_number = date('j', strtotime($display_date));
                                            $month_name = date('M', strtotime($display_date));
                                            $status_text = $special_appointment_data->fields['APPOINTMENT_STATUS'];
                                            $status_class = 'status-not-started';
                                            $dot_class = 'dot-not-started';
                                            if (stripos($status_text, 'progress') !== false) {
                                                $status_class = 'status-in-progress';
                                                $dot_class = 'dot-in-progress';
                                            } elseif (stripos($status_text, 'complete') !== false || stripos($status_text, 'done') !== false) {
                                                $status_class = 'status-complete';
                                                $dot_class = 'dot-complete';
                                            }
                                ?>
                                            <tr>
                                                <td class="date-sidebar">
                                                    <div class="date-day"><?= substr($day_name, 0, 3) ?></div>
                                                    <div class="date-number"><?= $day_number ?></div>
                                                    <div class="small text-muted"><?= $month_name ?></div>
                                                </td>
                                                <td><?= htmlspecialchars($special_appointment_data->fields['TITLE']) ?>
                                                    <?php if ($special_appointment_data->fields['STANDING_ID'] > 0) { ?>
                                                        <span style="font-weight: bold; color: #1B72B8">(S)</span>
                                                    <?php } ?>
                                                </td>
                                                <td style="text-align: center;">
                                                    <?= date('h:i A', strtotime($special_appointment_data->fields['START_TIME'])) . " - " . date('h:i A', strtotime($special_appointment_data->fields['END_TIME'])) ?>
                                                </td>
                                                <td style="text-align: center;" class="justify-content-center">
                                                    <span class="status-badge <?= $status_class ?>">
                                                        <span class="status-dot <?= $dot_class ?>"></span> <?= htmlspecialchars($status_text) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php
                                                    $providers = explode(',', $special_appointment_data->fields['SERVICE_PROVIDER_NAME']);
                                                    foreach ($providers as $provider) {
                                                        if (trim($provider)) {
                                                            $customer = getProfileBadge($provider);
                                                            $customer_initial = $customer['initials'];
                                                            $customer_color = $customer['color'];
                                                            echo '<div style="text-align: center;"><span class="avatarname" style="color: #fff; background-color: ' . $customer_color . '">' . $customer_initial . '</span> ' . htmlspecialchars(trim($provider)) . '</div>';
                                                        }
                                                    }
                                                    ?>
                                                </td>
                                                <td style="text-align: center;">
                                                    <?php if (in_array("To-Do Edit", $PERMISSION_ARRAY)) { ?>
                                                        <a href="edit_to_do.php?id=<?= $special_appointment_data->fields['PK_SPECIAL_APPOINTMENT'] ?>" title="Edit" style="font-size:18px; margin-right: 10px;"><i class="fa fa-edit"></i></a>
                                                    <?php } ?>
                                                    <?php if (in_array("To-Do Delete", $PERMISSION_ARRAY)) { ?>
                                                        <a href="javascript:" onclick="ConfirmDelete(<?= $special_appointment_data->fields['PK_SPECIAL_APPOINTMENT'] ?>);" title="Delete" style="font-size:18px; color: #dc3545;"><i class="fa fa-trash"></i></a>
                                                    <?php } ?>
                                                </td>
                                            </tr>
                                        <?php
                                        } else {
                                            // STANDING VIEW - Display grouped standing appointments
                                            $display_date = $standing_date->fields['BEGINNING_DATE'];
                                            $day_name = date('l', strtotime($display_date));
                                            $day_number = date('j', strtotime($display_date));
                                            $month_name = date('M', strtotime($display_date));
                                        ?>
                                            <tr class="standing-row" style="cursor: pointer;" onclick="showStandingToDoDetails(this, <?= $special_appointment_data->fields['STANDING_ID'] ?>)">
                                                <td class="date-sidebar">
                                                    <div class="date-day"><?= substr($day_name, 0, 3) ?></div>
                                                    <div class="date-number"><?= $day_number ?></div>
                                                    <div class="small text-muted">Standing</div>
                                                </td>
                                                <td><?= htmlspecialchars($special_appointment_data->fields['TITLE']) ?> <span style="font-weight: bold; color: #1B72B8">(S)</span></td>
                                                <td style="text-align: center;"><?= date('m/d/Y', strtotime($standing_date->fields['BEGINNING_DATE'])) ?> - <?= date('m/d/Y', strtotime($standing_date->fields['END_DATE'])) ?></td>
                                                <td style="text-align: center;"><span class="status-badge status-in-progress"><span class="status-dot dot-in-progress"></span> Recurring</span></td>
                                                <td style="text-align: center;"><?= htmlspecialchars($special_appointment_data->fields['SERVICE_PROVIDER_NAME']) ?></td>
                                                <td style="text-align: center;">
                                                    <?php if (in_array("To-Do Edit", $PERMISSION_ARRAY)) { ?>
                                                        <a href="edit_to_do.php?id=<?= $special_appointment_data->fields['PK_SPECIAL_APPOINTMENT'] ?>&standing=1" title="Edit" style="font-size:18px; margin-right: 10px;"><i class="fa fa-edit"></i></a>
                                                    <?php } ?>
                                                    <?php if (in_array("To-Do Delete", $PERMISSION_ARRAY)) { ?>
                                                        <a href="javascript:" onclick="ConfirmDeleteStanding(<?= $special_appointment_data->fields['STANDING_ID'] ?>);" title="Delete All Standing" style="font-size:18px; color: #dc3545;"><i class="fa fa-trash"></i></a>
                                                    <?php } ?>
                                                </td>
                                            </tr>
                                    <?php
                                        }
                                        $special_appointment_data->MoveNext();
                                        $counter++;
                                    }
                                } else {
                                    ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4">No to-do items found</td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                <div class="center">
                    <div class="pagination outer">
                        <ul>
                            <?php if ($page > 1) { ?>
                                <li><a href="to_do_list.php?START_DATE=<?= urlencode($date_start) ?>&END_DATE=<?= urlencode($date_end) ?>&appointment_status=<?= urlencode($appointment_status) ?>&page=1&standing=<?= $standing ?>&search_text=<?= urlencode($search_text) ?>">&laquo;</a></li>
                                <li><a href="to_do_list.php?START_DATE=<?= urlencode($date_start) ?>&END_DATE=<?= urlencode($date_end) ?>&appointment_status=<?= urlencode($appointment_status) ?>&page=<?= ($page - 1) ?>&standing=<?= $standing ?>&search_text=<?= urlencode($search_text) ?>">&lsaquo;</a></li>
                            <?php }
                            for ($page_count = 1; $page_count <= $number_of_page; $page_count++) {
                                if ($page_count == $page || $page_count == ($page + 1) || $page_count == ($page - 1) || $page_count == $number_of_page || $page_count == 1) {
                                    echo '<li><a class="' . (($page_count == $page) ? "active" : "") . '" href="to_do_list.php?START_DATE=' . urlencode($date_start) . '&END_DATE=' . urlencode($date_end) . '&appointment_status=' . urlencode($appointment_status) . '&page=' . $page_count . '&standing=' . $standing . '&search_text=' . urlencode($search_text) . '">' . $page_count . ' </a></li>';
                                } elseif ($page_count == ($number_of_page - 1) && $number_of_page > 3) {
                                    echo '<li><a href="javascript:;" style="border: none; margin: 0; padding: 8px;">...</a></li>';
                                }
                            }
                            if ($page < $number_of_page) { ?>
                                <li><a href="to_do_list.php?START_DATE=<?= urlencode($date_start) ?>&END_DATE=<?= urlencode($date_end) ?>&appointment_status=<?= urlencode($appointment_status) ?>&page=<?= ($page + 1) ?>&standing=<?= $standing ?>&search_text=<?= urlencode($search_text) ?>">&rsaquo;</a></li>
                                <li><a href="to_do_list.php?START_DATE=<?= urlencode($date_start) ?>&END_DATE=<?= urlencode($date_end) ?>&appointment_status=<?= urlencode($appointment_status) ?>&page=<?= $number_of_page ?>&standing=<?= $standing ?>&search_text=<?= urlencode($search_text) ?>">&raquo;</a></li>
                            <?php } ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php require_once('../includes/footer.php'); ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <script>
        $(function() {
            $("#START_DATE, #END_DATE").datepicker({
                dateFormat: 'yy-mm-dd',
                changeMonth: true,
                changeYear: true,
                onSelect: function() {
                    $("#search_form").submit();
                }
            });

            $(".sortable").on("click", function() {
                var table = $(this).closest("table");
                var tbody = table.find("tbody");
                var rows = tbody.find("tr").toArray();
                var index = $(this).index();
                var asc = !$(this).hasClass("asc");
                var type = $(this).data("type");

                table.find(".sortable").removeClass("asc desc");
                $(this).addClass(asc ? "asc" : "desc");

                rows.sort(function(a, b) {
                    var A = $(a).children("td").eq(index).text().trim();
                    var B = $(b).children("td").eq(index).text().trim();

                    if (type === "date") {
                        var dateA = new Date(A);
                        var dateB = new Date(B);
                        A = isNaN(dateA) ? 0 : dateA;
                        B = isNaN(dateB) ? 0 : dateB;
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

                $.each(rows, function(i, row) {
                    tbody.append(row);
                });
            });

            $("#search_text").on("keypress", function(e) {
                if (e.which == 13) {
                    $("#search_form").submit();
                }
            });
        });

        function showStandingToDoDetails(param, STANDING_ID) {

            let $row = $(param);

            // If already expanded, close it
            if ($row.data('expanded')) {
                $row.nextUntil('.standing-row').remove();
                $row.data('expanded', false);
                return;
            }

            // Close all other expanded standing rows
            $('.standing-row').each(function() {
                $(this).data('expanded', false);
                $(this).nextUntil('.standing-row').remove();
            });

            $row.data('expanded', true);

            $.ajax({
                url: "pagination/get_standing_to_do.php",
                type: "GET",
                data: {
                    STANDING_ID: STANDING_ID
                },
                success: function(result) {
                    $(result).insertAfter($row);
                },
                error: function() {
                    alert('Error loading details');
                    $row.data('expanded', false);
                }
            });
        }

        function ConfirmDelete(PK_SPECIAL_APPOINTMENT) {
            Swal.fire({
                title: "Are you sure?",
                text: "You won't be able to revert this!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, delete it!"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "ajax/AjaxFunctions.php",
                        type: 'POST',
                        data: {
                            FUNCTION_NAME: 'deleteSpecialAppointment',
                            PK_SPECIAL_APPOINTMENT: PK_SPECIAL_APPOINTMENT,
                            IS_STANDING: 0
                        },
                        success: function(data) {
                            window.location.href = window.location.pathname;
                        }
                    });
                }
            });
        }

        function ConfirmDeleteStanding(STANDING_ID) {
            Swal.fire({
                title: "Are you sure?",
                text: "You want to delete all standing appointments?",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, delete it!"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "ajax/AjaxFunctions.php",
                        type: 'POST',
                        data: {
                            FUNCTION_NAME: 'deleteSpecialAppointment',
                            PK_SPECIAL_APPOINTMENT: STANDING_ID,
                            IS_STANDING: 1
                        },
                        success: function(data) {
                            window.location.href = window.location.pathname;
                        }
                    });
                }
            });
        }
    </script>
</body>

</html>