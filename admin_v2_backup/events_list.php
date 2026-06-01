<?php
require_once('../global/config.php');
$title = "All Events - Modern List View";

// Check session first
if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

// Get filter parameters
$search_text = isset($_GET['search_text']) ? trim($_GET['search_text']) : '';
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : 'active'; // 'active' or 'past'
$from_date = isset($_GET['from_date']) && $_GET['from_date'] != '' ? date('Y-m-d', strtotime($_GET['from_date'])) : '';
$to_date = isset($_GET['to_date']) && $_GET['to_date'] != '' ? date('Y-m-d', strtotime($_GET['to_date'])) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 8;

// Add sorting parameter
$sort_column = isset($_GET['sort']) ? trim($_GET['sort']) : 'START_DATE';
$sort_order = isset($_GET['order']) && strtoupper($_GET['order']) == 'ASC' ? 'ASC' : 'DESC';

// Define allowed sort columns to prevent SQL injection
$allowed_sort_columns = ['HEADER', 'EVENT_TYPE', 'LOCATION_NAME', 'START_DATE', 'END_DATE'];
$sort_column = in_array($sort_column, $allowed_sort_columns) ? $sort_column : 'START_DATE';

// Ensure page is at least 1
if ($page < 1) $page = 1;

// Get today's date for past/active filtering
$today_date = date('Y-m-d');

// Build WHERE conditions
$where_clause = "DOA_EVENT_LOCATION.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ")";
$where_clause .= " AND DOA_EVENT.PK_ACCOUNT_MASTER = " . $_SESSION['PK_ACCOUNT_MASTER'];
$where_clause .= " AND DOA_EVENT.ACTIVE = 1"; // Only show active events (not soft-deleted)

// Status filter: Active (end_date >= today OR end_date is NULL and start_date >= today) 
// Past (end_date < today OR end_date is NULL and start_date < today)
if ($status_filter == 'active') {
    // Active: Events that haven't ended yet (end_date >= today OR if no end_date, start_date >= today)
    $where_clause .= " AND (DOA_EVENT.END_DATE >= '$today_date' OR (DOA_EVENT.END_DATE IS NULL AND DOA_EVENT.START_DATE >= '$today_date'))";
} elseif ($status_filter == 'past') {
    // Past: Events that have already ended (end_date < today OR if no end_date, start_date < today)
    $where_clause .= " AND (DOA_EVENT.END_DATE < '$today_date' OR (DOA_EVENT.END_DATE IS NULL AND DOA_EVENT.START_DATE < '$today_date'))";
}

// Search filter
if ($search_text != '') {
    //$search_escaped = $db_account->addq($search_text);
    $where_clause .= " AND (DOA_EVENT.HEADER LIKE '%$search_text%' 
                     OR DOA_EVENT_TYPE.EVENT_TYPE LIKE '%$search_text%' 
                     OR DOA_LOCATION.LOCATION_NAME LIKE '%$search_text%')";
}

// Date range filter
if ($from_date != '') {
    $where_clause .= " AND DOA_EVENT.START_DATE >= '$from_date'";
}
if ($to_date != '') {
    $where_clause .= " AND DOA_EVENT.START_DATE <= '$to_date'";
}

// Get total count
$count_sql = "SELECT COUNT(DISTINCT DOA_EVENT.PK_EVENT) AS TOTAL 
              FROM `DOA_EVENT` 
              JOIN DOA_EVENT_LOCATION ON DOA_EVENT.PK_EVENT = DOA_EVENT_LOCATION.PK_EVENT 
              LEFT JOIN DOA_EVENT_TYPE ON DOA_EVENT.PK_EVENT_TYPE = DOA_EVENT_TYPE.PK_EVENT_TYPE 
              LEFT JOIN " . $master_database . ".DOA_LOCATION AS DOA_LOCATION ON DOA_LOCATION.PK_LOCATION = DOA_EVENT_LOCATION.PK_LOCATION 
              WHERE " . $where_clause;

$count_result = $db_account->Execute($count_sql);
$total_records = $count_result->fields['TOTAL'] ?? 0;
$total_pages = ($per_page > 0) ? ceil($total_records / $per_page) : 1;
$offset = ($page - 1) * $per_page;

// Get events data
$events_sql = "SELECT DISTINCT 
                DOA_EVENT.PK_EVENT,
                DOA_EVENT.HEADER,
                DOA_EVENT.START_DATE,
                DOA_EVENT.START_TIME,
                DOA_EVENT.END_DATE,
                DOA_EVENT.END_TIME,
                DOA_EVENT.ACTIVE,
                DOA_EVENT_TYPE.EVENT_TYPE,
                DOA_LOCATION.LOCATION_NAME
            FROM `DOA_EVENT` 
            JOIN DOA_EVENT_LOCATION ON DOA_EVENT.PK_EVENT = DOA_EVENT_LOCATION.PK_EVENT 
            LEFT JOIN DOA_EVENT_TYPE ON DOA_EVENT.PK_EVENT_TYPE = DOA_EVENT_TYPE.PK_EVENT_TYPE 
            LEFT JOIN " . $master_database . ".DOA_LOCATION AS DOA_LOCATION ON DOA_LOCATION.PK_LOCATION = DOA_EVENT_LOCATION.PK_LOCATION 
            WHERE " . $where_clause . "
            ORDER BY " . $sort_column . " " . $sort_order . "
            LIMIT " . (int)$offset . ", " . (int)$per_page;

$events_result = $db_account->Execute($events_sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events - List View</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: #f8f9fa;
            font-family: 'Inter', sans-serif;
            color: #333;
        }

        a {
            color: #690C24;
            text-decoration: none;
            font-size: 14px;
        }

        .toolbar-btn {
            border: 1px solid #dee2e6;
            background: #fff;
            border-radius: 8px;
            font-size: 0.85rem;
            padding: 6px 12px;
            color: #444;
            transition: all 0.2s;
        }

        .toolbar-btn:hover {
            background-color: #f8f9fa;
        }

        .search-container {
            position: relative;
            width: 280px;
        }

        .search-container i {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #adb5bd;
            z-index: 1;
        }

        .search-container input {
            padding-left: 35px;
            border-radius: 8px;
            font-size: 0.85rem;
        }

        .icon-circle {
            width: 45px;
            height: 45px;
            text-align: center;
            border-radius: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .text-green {
            color: #39b54a;
        }

        .btn-success {
            background-color: #39b54a !important;
            border: none;
        }

        .btn-success:hover {
            background-color: #2e8e3c;
        }

        .table thead th {
            background-color: #f8f9fa;
            color: #6c757d;
            font-weight: 500;
            font-size: 0.85rem;
            padding: 12px 16px;
            border-bottom: 1px solid #dee2e6;
        }

        .table tbody td {
            padding: 16px;
            vertical-align: middle;
            font-size: 0.85rem;
            border-bottom: 1px solid #dee2e6;
        }

        .event-avatar {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #e8f3ec;
            color: #2e7d32;
            margin-right: 12px;
        }

        .status-pill {
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 500;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            color: #344054;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
        }

        .status-badge-active {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }

        .status-badge-active .status-dot {
            background-color: #28a745;
        }

        .status-badge-past {
            background-color: #e9ecef;
            color: #6c757d;
            border-color: #dee2e6;
        }

        .status-badge-past .status-dot {
            background-color: #adb5bd;
        }

        .status-filter-btn {
            cursor: pointer;
            transition: all 0.2s;
            border-radius: 20px;
            padding: 6px 18px;
            font-size: 0.85rem;
        }

        .status-filter-btn.active {
            background-color: #39b54a !important;
            color: white !important;
        }

        .sortable-header {
            cursor: pointer;
            user-select: none;
            transition: background-color 0.2s;
        }

        .sortable-header:hover {
            background-color: #e9ecef !important;
        }

        .sortable-header .sort-icon {
            font-size: 0.75rem;
            opacity: 0.5;
            margin-left: 5px;
        }

        .sortable-header.asc .sort-icon:before {
            content: "↑";
        }

        .sortable-header.desc .sort-icon:before {
            content: "↓";
        }

        .pagination-container {
            padding-top: 20px;
            font-size: 0.85rem;
            color: #6c757d;
        }

        .page-link-custom {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 20px;
            text-decoration: none;
            color: #6c757d;
            border: 1px solid #dee2e6;
            margin: 0 2px;
            cursor: pointer;
        }

        .page-link-custom:hover {
            background-color: #f8f9fa;
            color: #39b54a;
        }

        .page-link-custom.active {
            background: #39b54a;
            color: white;
            border-color: #39b54a;
        }

        .action-icons i {
            cursor: pointer;
            transition: opacity 0.2s;
            margin: 0 5px;
            font-size: 1.1rem;
        }

        .action-icons i:hover {
            opacity: 0.7;
        }

        .event-name-link {
            cursor: pointer;
            color: #333;
            font-weight: 600;
            text-decoration: none;
        }

        .event-name-link:hover {
            color: #39b54a;
        }

        .btn-group-soft {
            background: white;
            border-radius: 40px;
            padding: 4px;
            border: 1px solid #dee2e6;
        }

        .filter-date-input {
            border-radius: 20px;
            width: 170px;
            font-size: 0.85rem;
        }

        .text-muted-small {
            font-size: 0.75rem;
        }

        .badge-location {
            background-color: #f1f3f5;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 500;
        }

        .location-cell i {
            font-size: 0.7rem;
        }
    </style>
    <?php include 'layout/header_script.php'; ?>
    <?php require_once('../includes/header.php'); ?>
    <?php include 'layout/header.php'; ?>
</head>

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">
        <div class="page-wrapper" style="padding-top: 0px !important;">
            <div class="container-fluid body_content" style="margin-top: 0px;">
                <div class="container-fluid py-4 px-4 bg-white m-3 rounded border mx-auto">
                    <!-- Header Section -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="d-flex align-items-center">
                            <div class="p-2 bg-white border me-3 icon-circle"><i class="bi bi-calendar-event fs-4 text-green"></i></div>
                            <div>
                                <h4 class="mb-0 fw-bold">Events</h4>
                                <p class="text-muted small mb-0">Manage and track all your events</p>
                            </div>
                        </div>
                        <button class="btn btn-success border-0 rounded-pill px-3" onclick="window.location.href='event.php'">
                            <i class="bi bi-plus-lg me-1"></i> Create New Event
                        </button>
                    </div>

                    <!-- Filter Bar -->
                    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                        <div class="search-container">
                            <i class="bi bi-search"></i>
                            <input type="text" id="search_text" class="form-control" placeholder="Search by event name, type, location..." value="<?= htmlspecialchars($search_text) ?>">
                        </div>

                        <div class="d-flex gap-3 align-items-center flex-wrap">
                            <!-- Status filter: Active & Past only -->
                            <div class="btn-group-soft d-flex gap-2">
                                <button class="status-filter-btn btn btn-sm rounded-pill px-3 <?= $status_filter == 'active' ? 'active' : '' ?>" data-status="active">
                                    <i class="bi bi-calendar-check me-1"></i> Active
                                </button>
                                <button class="status-filter-btn btn btn-sm rounded-pill px-3 <?= $status_filter == 'past' ? 'active' : '' ?>" data-status="past">
                                    <i class="bi bi-calendar-x me-1"></i> Past
                                </button>
                            </div>

                            <!-- View Toggle -->
                            <!-- <div class="btn-group ms-2 border rounded-pill p-1" style="border-radius: 20px !important;">
                                <button class="toolbar-btn me-1 border-0 rounded-pill" style="background: transparent; color: #6c757d;" onclick="window.location.href='events_grid.php'">
                                    <i class="bi bi-grid-3x3-gap-fill"></i>
                                </button>
                                <button class="toolbar-btn border-0 rounded-pill" style="background: #39b54a; color: white;">
                                    <i class="bi bi-list-ul"></i>
                                </button>
                            </div> -->

                            <!-- Date Range Filter -->
                            <div class="d-flex gap-2 align-items-center">
                                <input type="text" id="from_date" class="form-control datepicker-normal filter-date-input" placeholder="From Date" value="<?= htmlspecialchars($_GET['from_date'] ?? '') ?>" autocomplete="off">
                                <span class="text-muted">—</span>
                                <input type="text" id="to_date" class="form-control datepicker-normal filter-date-input" placeholder="To Date" value="<?= htmlspecialchars($_GET['to_date'] ?? '') ?>" autocomplete="off">
                            </div>

                            <button class="toolbar-btn rounded-pill" onclick="resetFilters()">
                                <i class="bi bi-arrow-repeat me-1"></i> Reset
                            </button>
                        </div>
                    </div>

                    <!-- Results Count & Table -->
                    <div class="row g-3">
                        <div class="mb-2 text-muted small fw-medium"><?= $total_records ?> event<?= $total_records != 1 ? 's' : '' ?> found</div>

                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 40px;"><input type="checkbox" class="form-check-input" id="selectAll"></th>
                                        <th class="sortable-header" data-sort="HEADER">
                                            Event Name
                                            <i class="bi bi-arrow-down-up ms-1 sort-icon"></i>
                                        </th>
                                        <th class="sortable-header" data-sort="EVENT_TYPE">
                                            Type
                                            <i class="bi bi-arrow-down-up ms-1 sort-icon"></i>
                                        </th>
                                        <th class="sortable-header" data-sort="LOCATION_NAME">
                                            Location
                                            <i class="bi bi-arrow-down-up ms-1 sort-icon"></i>
                                        </th>
                                        <th class="sortable-header" data-sort="START_DATE">
                                            Start Date & Time
                                            <i class="bi bi-arrow-down-up ms-1 sort-icon"></i>
                                        </th>
                                        <th class="sortable-header" data-sort="END_DATE">
                                            End Date & Time
                                            <i class="bi bi-arrow-down-up ms-1 sort-icon"></i>
                                        </th>
                                        <th>Status</th>
                                        <th style="width: 100px;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($events_result && $events_result->RecordCount() > 0): ?>
                                        <?php while (!$events_result->EOF):
                                            $event = $events_result->fields;
                                            $today = new DateTime();
                                            $endDate = new DateTime($event['END_DATE']);
                                            $isPastEvent = $endDate < $today;
                                            $statusLabel = $isPastEvent ? 'Past' : 'Active';
                                            $statusColor = $isPastEvent ? '#adb5bd' : '#28a745';
                                            $statusClass = $isPastEvent ? 'status-badge-past' : 'status-badge-active';

                                            $startDateFormatted = date('m/d/Y', strtotime($event['START_DATE']));
                                            $startTimeFormatted = date('h:i A', strtotime($event['START_TIME']));
                                            $endDateFormatted = ($event['END_DATE'] && $event['END_DATE'] != '0000-00-00') ? date('m/d/Y', strtotime($event['END_DATE'])) : '—';
                                            $endTimeFormatted = ($event['END_TIME'] && $event['END_TIME'] != '00:00:00') ? date('h:i A', strtotime($event['END_TIME'])) : '—';
                                        ?>
                                            <tr>
                                                <td><input type="checkbox" class="form-check-input event-checkbox" data-id="<?= $event['PK_EVENT'] ?>"></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <!-- <div class="event-avatar">
                                                            <i class="bi bi-calendar-week"></i>
                                                        </div> -->
                                                        <div>
                                                            <div class="event-name-link fw-semibold" onclick="editEvent(<?= $event['PK_EVENT'] ?>)"><?= htmlspecialchars($event['HEADER']) ?></div>
                                                            <!-- <div class="text-muted text-muted-small">ID: EV-<?= $event['PK_EVENT'] ?></div> -->
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><span class="badge-location"><?= htmlspecialchars($event['EVENT_TYPE'] ?? 'General') ?></span></td>
                                                <td class="location-cell"><i class="bi bi-geo-alt-fill me-1 text-muted"></i> <?= htmlspecialchars($event['LOCATION_NAME'] ?? '—') ?></td>
                                                <td><?= $startDateFormatted ?> <span class="text-muted"><?= $startTimeFormatted ?></span></td>
                                                <td><?= $endDateFormatted ?> <span class="text-muted"><?= ($endTimeFormatted != '—') ? $endTimeFormatted : '' ?></span></td>
                                                <td>
                                                    <span class="status-pill <?= $statusClass ?>">
                                                        <span class="status-dot" style="background-color: <?= $statusColor ?>;"></span>
                                                        <?= $statusLabel ?>
                                                    </span>
                                                </td>
                                                <td class="action-icons">
                                                    <i class="bi bi-pencil-square text-muted" onclick="editEvent(<?= $event['PK_EVENT'] ?>)" title="Edit"></i>
                                                    <!-- <i class="bi bi-trash3 text-danger" onclick="confirmDeleteEvent(<?= $event['PK_EVENT'] ?>)" title="Delete"></i>
                                                    <i class="bi bi-three-dots-vertical text-muted" title="More"></i> -->
                                                </td>
                                            </tr>
                                        <?php
                                            $events_result->MoveNext();
                                        endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-5 text-muted">
                                                <i class="bi bi-calendar-x fs-1 d-block mb-2"></i>
                                                No events found matching your criteria.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div class="d-flex justify-content-between align-items-center pagination-container flex-wrap gap-2 mt-3">
                                <div>Page <?= $page ?> of <?= $total_pages ?></div>
                                <div class="d-flex align-items-center gap-1 flex-wrap">
                                    <a class="page-link-custom" data-page="1"><i class="bi bi-chevron-double-left"></i></a>
                                    <a class="page-link-custom" data-page="<?= max(1, $page - 1) ?>"><i class="bi bi-chevron-left"></i></a>

                                    <?php
                                    $start_page = max(1, $page - 2);
                                    $end_page = min($total_pages, $page + 2);
                                    for ($i = $start_page; $i <= $end_page; $i++): ?>
                                        <a class="page-link-custom <?= $i == $page ? 'active' : '' ?>" data-page="<?= $i ?>"><?= $i ?></a>
                                    <?php endfor; ?>
                                    <?php if ($end_page < $total_pages - 1): ?>
                                        <span>...</span>
                                        <a class="page-link-custom" data-page="<?= $total_pages ?>"><?= $total_pages ?></a>
                                    <?php elseif ($end_page < $total_pages): ?>
                                        <a class="page-link-custom" data-page="<?= $total_pages ?>"><?= $total_pages ?></a>
                                    <?php endif; ?>

                                    <a class="page-link-custom" data-page="<?= min($total_pages, $page + 1) ?>"><i class="bi bi-chevron-right"></i></a>
                                    <a class="page-link-custom" data-page="<?= $total_pages ?>"><i class="bi bi-chevron-double-right"></i></a>
                                </div>
                                <div>
                                    <select id="per_page" class="form-select form-select-sm d-inline-block w-auto">
                                        <option value="8" <?= $per_page == 8 ? 'selected' : '' ?>>8 / page</option>
                                        <option value="16" <?= $per_page == 16 ? 'selected' : '' ?>>16 / page</option>
                                        <option value="32" <?= $per_page == 32 ? 'selected' : '' ?>>32 / page</option>
                                        <option value="50" <?= $per_page == 50 ? 'selected' : '' ?>>50 / page</option>
                                    </select>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php require_once('../includes/footer.php'); ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            $('.datepicker-normal').datepicker({
                dateFormat: 'mm/dd/yy',
                changeMonth: true,
                changeYear: true
            });

            // Search with debounce
            let searchTimeout;
            $('#search_text').on('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    submitFilters();
                }, 500);
            });

            // Status filter buttons
            $('.status-filter-btn').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                $('.status-filter-btn').removeClass('active');
                $(this).addClass('active');
                submitFilters();
            });

            // Date filters
            $('#from_date, #to_date').on('change', function() {
                submitFilters();
            });

            // Per page change
            $('#per_page').on('change', function() {
                submitFilters();
            });

            // Pagination
            $(document).on('click', '.page-link-custom', function(e) {
                e.preventDefault();
                let page = $(this).data('page');
                if (page) {
                    let url = new URL(window.location.href);
                    url.searchParams.set('page', page);
                    window.location.href = url.toString();
                }
            });

            // Select all functionality
            $('#selectAll').on('change', function() {
                $('.event-checkbox').prop('checked', $(this).is(':checked'));
            });

            // Sorting functionality
            $('.sortable-header').on('click', function() {
                let sortColumn = $(this).data('sort');
                let url = new URL(window.location.href);
                let currentSort = url.searchParams.get('sort') || '';
                let currentOrder = url.searchParams.get('order') || 'DESC';
                let newOrder = 'DESC';

                if (currentSort === sortColumn) {
                    newOrder = currentOrder === 'DESC' ? 'ASC' : 'DESC';
                }

                url.searchParams.set('sort', sortColumn);
                url.searchParams.set('order', newOrder);
                url.searchParams.set('page', 1);
                window.location.href = url.toString();
            });

            // Set active sort indicator
            let currentSort = new URLSearchParams(window.location.search).get('sort') || 'START_DATE';
            let currentOrder = new URLSearchParams(window.location.search).get('order') || 'DESC';
            $(`.sortable-header[data-sort="${currentSort}"]`).addClass(currentOrder === 'ASC' ? 'asc' : 'desc');
        });

        function submitFilters() {
            let searchText = $('#search_text').val() || '';
            let status = $('.status-filter-btn.active').data('status') || 'active';
            let fromDate = $('#from_date').val() || '';
            let toDate = $('#to_date').val() || '';
            let perPage = $('#per_page').val() || '<?= $per_page ?>';
            let currentSort = new URLSearchParams(window.location.search).get('sort') || 'START_DATE';
            let currentOrder = new URLSearchParams(window.location.search).get('order') || 'DESC';

            let url = window.location.pathname + '?';
            let params = [];

            if (searchText) params.push('search_text=' + encodeURIComponent(searchText));
            params.push('status=' + encodeURIComponent(status));
            if (fromDate) params.push('from_date=' + encodeURIComponent(fromDate));
            if (toDate) params.push('to_date=' + encodeURIComponent(toDate));
            params.push('sort=' + encodeURIComponent(currentSort));
            params.push('order=' + encodeURIComponent(currentOrder));
            params.push('per_page=' + perPage);
            params.push('page=1');

            url += params.join('&');
            window.location.href = url;
        }

        function resetFilters() {
            window.location.href = window.location.pathname + '?status=active&per_page=8&page=1';
        }

        function editEvent(id) {
            window.location.href = "event.php?id=" + id;
        }

        function confirmDeleteEvent(PK_EVENT) {
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
                    Swal.fire({
                        title: 'Deleting...',
                        text: 'Please wait',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    $.ajax({
                        url: "ajax/AjaxFunctions.php",
                        type: 'POST',
                        data: {
                            FUNCTION_NAME: 'deleteEvent',
                            PK_EVENT: PK_EVENT
                        },
                        success: function(data) {
                            Swal.close();
                            Swal.fire('Deleted!', 'Event has been deleted.', 'success').then(() => {
                                window.location.reload();
                            });
                        },
                        error: function() {
                            Swal.close();
                            Swal.fire('Error!', 'Could not delete event.', 'error');
                        }
                    });
                }
            });
        }
    </script>
</body>

</html>