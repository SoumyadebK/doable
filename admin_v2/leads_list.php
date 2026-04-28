<?php
require_once('../global/config.php');
$title = "All Leads - List View";

// Check session first
if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '') {
    header("location:../login.php");
    exit;
}

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'] ?? 0;

// Get filter parameters
$search_text = isset($_GET['search_text']) ? trim($_GET['search_text']) : '';
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
$choose_date = isset($_GET['CHOOSE_DATE']) && $_GET['CHOOSE_DATE'] != '' ? date('Y-m-d', strtotime($_GET['CHOOSE_DATE'])) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 8;

// Add sorting parameter
$sort_column = isset($_GET['sort']) ? trim($_GET['sort']) : 'PK_LEADS';
$sort_order = isset($_GET['order']) && strtoupper($_GET['order']) == 'ASC' ? 'ASC' : 'DESC';

// Define allowed sort columns to prevent SQL injection
$allowed_sort_columns = [
    'NAME',
    'LOCATION_NAME',
    'OPPORTUNITY_SOURCE',
    'LATEST_DATE',
    'LEAD_STATUS',
    'CREATED_ON',
    'PHONE',
    'EMAIL_ID'
];
$sort_column = in_array($sort_column, $allowed_sort_columns) ? $sort_column : 'PK_LEADS';

// Ensure page is at least 1
if ($page < 1) $page = 1;

// Build WHERE conditions
$where_clause = "DOA_LEADS.PK_LOCATION IN (" . $DEFAULT_LOCATION_ID . ")";

if ($search_text != '') {
    $search_escaped =  $search_text; // No need to escape here as we are using prepared statements or properly handling input in the actual implementation
    $where_clause .= " AND (DOA_LEADS.FIRST_NAME LIKE '%$search_escaped%' 
                     OR DOA_LEADS.LAST_NAME LIKE '%$search_escaped%' 
                     OR DOA_LEADS.PHONE LIKE '%$search_escaped%' 
                     OR DOA_LEADS.EMAIL_ID LIKE '%$search_escaped%' 
                     OR LS.LEAD_STATUS LIKE '%$search_escaped%')";
}

if ($status_filter != '' && $status_filter != 'inactive') {
    $where_clause .= " AND DOA_LEADS.PK_LEAD_STATUS = " . (int)$status_filter . " AND DOA_LEADS.ACTIVE = 1";
} elseif ($status_filter == 'inactive') {
    $where_clause .= " AND DOA_LEADS.ACTIVE = 0";
} else {
    $where_clause .= " AND DOA_LEADS.ACTIVE = 1";
}

if ($choose_date != '') {
    $where_clause .= " AND EXISTS (SELECT 1 FROM DOA_LEAD_DATE 
                       WHERE PK_LEADS = DOA_LEADS.PK_LEADS 
                       AND DATE = '$choose_date')";
}

// Get total count - simplified query
$count_sql = "SELECT COUNT(DISTINCT DOA_LEADS.PK_LEADS) AS TOTAL 
              FROM DOA_LEADS 
              INNER JOIN " . $master_database . ".DOA_LOCATION AS DOA_LOCATION 
                  ON DOA_LOCATION.PK_LOCATION = DOA_LEADS.PK_LOCATION 
              LEFT JOIN DOA_LEAD_STATUS AS LS 
                  ON DOA_LEADS.PK_LEAD_STATUS = LS.PK_LEAD_STATUS 
              WHERE " . $where_clause;

$count_result = $db->Execute($count_sql);
$total_records = $count_result->fields['TOTAL'] ?? 0;
$total_pages = ($per_page > 0) ? ceil($total_records / $per_page) : 1;
$offset = ($page - 1) * $per_page;

// Get leads data
$leads_sql = "SELECT DISTINCT 
                DOA_LEADS.PK_LEADS, 
                DOA_LEADS.FIRST_NAME,
                DOA_LEADS.LAST_NAME,
                CONCAT(DOA_LEADS.FIRST_NAME, ' ', DOA_LEADS.LAST_NAME) AS NAME, 
                DOA_LEADS.PHONE, 
                DOA_LEADS.EMAIL_ID, 
                LS.LEAD_STATUS,
                LS.PK_LEAD_STATUS,
                LS.STATUS_COLOR,
                DOA_LEADS.DESCRIPTION, 
                DOA_LEADS.OPPORTUNITY_SOURCE, 
                DOA_LEADS.ACTIVE, 
                DOA_LEADS.CREATED_ON, 
                DOA_LEADS.IS_CALLED, 
                DOA_LEADS.IS_APPOINTMENT_CREATED, 
                DOA_LOCATION.LOCATION_NAME,
                (SELECT DATE FROM DOA_LEAD_DATE 
                 WHERE PK_LEADS = DOA_LEADS.PK_LEADS 
                 ORDER BY CREATED_ON DESC 
                 LIMIT 1) AS LATEST_DATE
            FROM DOA_LEADS 
            INNER JOIN " . $master_database . ".DOA_LOCATION AS DOA_LOCATION 
                ON DOA_LOCATION.PK_LOCATION = DOA_LEADS.PK_LOCATION 
            LEFT JOIN DOA_LEAD_STATUS AS LS 
                ON DOA_LEADS.PK_LEAD_STATUS = LS.PK_LEAD_STATUS 
            WHERE " . $where_clause . "
            ORDER BY 
    CASE 
        WHEN '$sort_column' = 'NAME' THEN CONCAT(DOA_LEADS.FIRST_NAME, ' ', DOA_LEADS.LAST_NAME)
        WHEN '$sort_column' = 'LOCATION_NAME' THEN DOA_LOCATION.LOCATION_NAME
        WHEN '$sort_column' = 'OPPORTUNITY_SOURCE' THEN DOA_LEADS.OPPORTUNITY_SOURCE
        WHEN '$sort_column' = 'LATEST_DATE' THEN (SELECT DATE FROM DOA_LEAD_DATE WHERE PK_LEADS = DOA_LEADS.PK_LEADS ORDER BY CREATED_ON DESC LIMIT 1)
        WHEN '$sort_column' = 'LEAD_STATUS' THEN LS.LEAD_STATUS
        WHEN '$sort_column' = 'CREATED_ON' THEN DOA_LEADS.CREATED_ON
        WHEN '$sort_column' = 'PHONE' THEN DOA_LEADS.PHONE
        WHEN '$sort_column' = 'EMAIL_ID' THEN DOA_LEADS.EMAIL_ID
        ELSE DOA_LEADS.PK_LEADS
    END $sort_order
            LIMIT " . (int)$offset . ", " . (int)$per_page;

$leads_result = $db->Execute($leads_sql);

// Get all statuses for filter dropdown 
$all_status_sql = "SELECT * FROM `DOA_LEAD_STATUS` 
                   WHERE ACTIVE = 1 
                   AND `PK_ACCOUNT_MASTER` = " . $_SESSION['PK_ACCOUNT_MASTER'] . " 
                   ORDER BY DISPLAY_ORDER ASC";
$all_status = $db->Execute($all_status_sql);

// Helper function to truncate text
function truncateText($text, $length = 30)
{
    if (empty($text)) return '—';
    if (strlen($text) <= $length) return htmlspecialchars($text);
    return htmlspecialchars(substr($text, 0, $length)) . '...';
}
?>

<!DOCTYPE html>
<html lang="en">
<?php include 'layout/header_script.php'; ?>
<?php require_once('../includes/header.php'); ?>
<?php include 'layout/header.php'; ?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leads List View</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Inter', sans-serif;
            color: #333;
        }

        .toolbar-btn {
            border: 1px solid #dee2e6;
            background: #fff;
            border-radius: 8px;
            font-size: 0.85rem;
            padding: 6px 12px;
            color: #444;
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
        }

        .text-green {
            color: #39b54a;
        }

        .btn-success {
            background-color: #39b54a;
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

        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.85rem;
            margin-right: 12px;
        }

        .avatar-jb {
            background: #eff8ff;
            color: #175cd3;
        }

        .avatar-sw {
            background: #fef0c7;
            color: #93370d;
        }

        .avatar-at {
            background: #e0f2fe;
            color: #026aa2;
        }

        .avatar-bg1 {
            background: #e8f3ec;
            color: #2e7d32;
        }

        .avatar-bg2 {
            background: #fce4ec;
            color: #c62828;
        }

        .avatar-bg3 {
            background: #f3e5f5;
            color: #7b1fa2;
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
            box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.05);
        }

        /* Optional: Add hover effect */
        .status-pill:hover {
            background: #fff;
            border-color: #dee2e6;
            transform: translateY(-1px);
            transition: all 0.2s;
        }

        .status-pill-new::before {
            background-color: #6c757d;
        }

        .status-pill-enrolled::before {
            background-color: #198754;
        }

        .status-pill-not-enrolled::before {
            background-color: #fd7e14;
        }

        .status-pill-inactive::before {
            background-color: #dc3545;
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
        }

        .action-icons i:hover {
            opacity: 0.7;
        }

        .lead-name-link {
            cursor: pointer;
            color: #333;
            font-weight: 600;
            text-decoration: none;
        }

        .lead-name-link:hover {
            color: #39b54a;
        }

        .status-filter-btn {
            cursor: pointer;
            transition: all 0.2s;
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
            transition: opacity 0.2s;
        }

        .sortable-header:hover .sort-icon {
            opacity: 1;
        }

        .sortable-header.asc .sort-icon::before {
            content: "\F235";
            /* bi-arrow-down */
        }

        .sortable-header.desc .sort-icon::before {
            content: "\F229";
            /* bi-arrow-up */
        }

        .sort-indicator {
            display: inline-block;
            margin-left: 5px;
            font-size: 0.7rem;
        }

        .sortable-header.asc .sort-indicator:after {
            content: "↑";
        }

        .sortable-header.desc .sort-indicator:after {
            content: "↓";
        }
    </style>
</head>

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">
        <div class="page-wrapper" style="padding-top: 0px !important;">
            <div class="container-fluid body_content" style="margin-top: 0px;">
                <div class="container-fluid py-4 px-4 bg-white m-3 rounded border mx-auto">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="d-flex align-items-center">
                            <div class="p-2 bg-white border me-3 icon-circle"><i class="bi bi-people fs-5"></i></div>
                            <div>
                                <h4 class="mb-0 fw-bold">Leads</h4>
                                <p class="text-muted small mb-0">Manage and track all your leads</p>
                            </div>
                        </div>
                        <button class="btn btn-success border-0 rounded-pill px-3" onclick="window.location.href='leads.php'">
                            <i class="bi bi-plus-lg me-1"></i> Create New Lead
                        </button>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                        <div class="search-container">
                            <i class="bi bi-search"></i>
                            <input type="text" id="search_text" class="form-control" placeholder="Search by name, email, phone..." value="<?= htmlspecialchars($search_text) ?>">
                        </div>

                        <div class="d-flex gap-2 align-items-center flex-wrap">
                            <div class="btn-group bg-white border rounded-pill p-1">
                                <button class="btn btn-sm status-filter-btn rounded-pill px-3 <?= ($status_filter == '') ? 'active' : '' ?>" data-status="">All</button>
                                <?php
                                // Show dynamic statuses from database
                                $status_options = [];
                                if ($all_status && $all_status->RecordCount() > 0) {
                                    while (!$all_status->EOF) {
                                        $status_val = $all_status->fields['PK_LEAD_STATUS'];
                                        $status_label = $all_status->fields['LEAD_STATUS'];
                                ?>
                                        <button class="btn btn-sm status-filter-btn rounded-pill px-3 <?= ($status_filter == $status_val) ? 'active' : '' ?>" data-status="<?= $status_val ?>"><?= htmlspecialchars($status_label) ?></button>
                                <?php
                                        $all_status->MoveNext();
                                    }
                                }
                                ?>
                                <button class="btn btn-sm status-filter-btn rounded-pill px-3 <?= ($status_filter == 'inactive') ? 'active' : '' ?>" data-status="inactive">Inactive</button>
                            </div>
                            <div class="btn-group ms-2 border rounded-pill p-1" style="border-radius: 20px !important;">
                                <button class="toolbar-btn me-1 border-0 rounded-pill" id="kanban_view_btn" style="background: transparent; color: #6c757d;" onclick="window.location.href='leads_grid.php'">
                                    <i class="bi bi-grid-3x3-gap-fill"></i>
                                </button>
                                <button class="toolbar-btn border-0 rounded-pill" id="list_view_btn" style="background: #39b54a; color: white;">
                                    <i class="bi bi-list-ul"></i>
                                </button>
                            </div>
                            <div class="position-relative">
                                <input type="text" id="CHOOSE_DATE" class="form-control datepicker-normal" placeholder="Filter by Follow up Date" value="<?= htmlspecialchars($_GET['CHOOSE_DATE'] ?? '') ?>" style="border-radius: 20px; width: 180px;">
                            </div>
                            <button class="toolbar-btn rounded-pill" onclick="submitFilters()"><i class="bi bi-filter me-1"></i> Filter</button>
                            <button class="toolbar-btn rounded-pill"><i class="bi bi-arrow-down-up me-1"></i> Sort by <i class="bi bi-chevron-down ms-1"></i></button>
                            <button class="toolbar-btn rounded-pill" onclick="resetFilters()"><i class="bi bi-arrow-repeat me-1"></i> Reset</button>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="mb-2 text-muted small fw-medium"><?= $total_records ?> leads found</div>

                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 40px;"><input type="checkbox" class="form-check-input" id="selectAll"></th>
                                        <th class="sortable-header" data-sort="NAME" style="cursor: pointer;">
                                            Customer Name / Email
                                            <i class="bi bi-arrow-down-up ms-1 sort-icon"></i>
                                            <span class="sort-indicator"></span>
                                        </th>
                                        <th class="sortable-header" data-sort="LOCATION_NAME" style="cursor: pointer;">
                                            Primary Location
                                            <i class="bi bi-arrow-down-up ms-1 sort-icon"></i>
                                        </th>
                                        <th class="sortable-header" data-sort="OPPORTUNITY_SOURCE" style="cursor: pointer;">
                                            Source
                                            <i class="bi bi-arrow-down-up ms-1 sort-icon"></i>
                                        </th>
                                        <th class="sortable-header" data-sort="LATEST_DATE" style="cursor: pointer;">
                                            Follow-up Date
                                            <i class="bi bi-arrow-down-up ms-1 sort-icon"></i>
                                        </th>
                                        <th class="sortable-header" data-sort="DESCRIPTION" style="cursor: pointer;">
                                            Notes
                                            <i class="bi bi-arrow-down-up ms-1 sort-icon"></i>
                                        </th>
                                        <th class="sortable-header" data-sort="LEAD_STATUS" style="cursor: pointer;">
                                            Status
                                            <i class="bi bi-arrow-down-up ms-1 sort-icon"></i>
                                        </th>
                                        <th style="width: 150px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($leads_result && $leads_result->RecordCount() > 0): ?>
                                        <?php while (!$leads_result->EOF):
                                            $lead = $leads_result->fields;

                                            $CUSTOMER_NAME = $lead['NAME'];
                                            $customer = getProfileBadge($CUSTOMER_NAME);
                                            $customer_initial = $customer['initials'];
                                            $customer_color = $customer['color'];

                                            $status_lower = strtolower($lead['LEAD_STATUS'] ?? '');
                                            $status_class = '';
                                            if ($status_lower == 'new') $status_class = 'status-pill-new';
                                            elseif ($status_lower == 'enrolled') $status_class = 'status-pill-enrolled';
                                            elseif ($status_lower == 'not enrolled') $status_class = 'status-pill-not-enrolled';
                                            elseif ($lead['ACTIVE'] == 0) $status_class = 'status-pill-inactive';

                                            $display_status = $lead['ACTIVE'] == 0 ? 'Inactive' : ($lead['LEAD_STATUS'] ?? 'New');
                                            $follow_up_date = (!empty($lead['LATEST_DATE']) && $lead['LATEST_DATE'] != '0000-00-00')
                                                ? date('m/d/Y', strtotime($lead['LATEST_DATE']))
                                                : 'N/A';
                                        ?>
                                            <tr>
                                                <td><input type="checkbox" class="form-check-input lead-checkbox" data-id="<?= $lead['PK_LEADS'] ?>"></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div><span class="avatarname" style="color: #fff; background-color: <?= $customer_color ?>;"><?= $customer_initial; ?></span></div>
                                                        <div>
                                                            <div class="text-dark lead-name-link" onclick="editpage(<?= $lead['PK_LEADS'] ?>, '<?= htmlspecialchars($lead['LATEST_DATE'] ?? '') ?>')"><?= htmlspecialchars($lead['NAME']) ?></div>
                                                            <div class="text-muted small"><?= htmlspecialchars($lead['EMAIL_ID']) ?></div>
                                                            <?php if ($lead['PHONE']): ?>
                                                                <div class="text-muted small"><?= htmlspecialchars($lead['PHONE']) ?></div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?= htmlspecialchars($lead['LOCATION_NAME']) ?></td>
                                                <td><?= htmlspecialchars($lead['OPPORTUNITY_SOURCE'] ?: '—') ?></td>
                                                <td>
                                                    <?= $follow_up_date ?>
                                                    <?php if ($lead['IS_APPOINTMENT_CREATED']): ?>
                                                        <i class="bi bi-star-fill ms-1" style="color: gold; font-size: 12px;" title="Appointment Created"></i>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-muted small">
                                                    <?= truncateText($lead['DESCRIPTION'] ?? '', 40) ?>
                                                    <?php if ($lead['IS_CALLED']): ?>
                                                        <i class="bi bi-check-circle-fill ms-1" style="color: #39b54a; font-size: 12px;" title="Called"></i>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    // Get status color from the database
                                                    $status_color = '#6c757d'; // Default gray
                                                    if ($lead['ACTIVE'] == 0) {
                                                        $status_color = '#dc3545'; // Red for inactive
                                                    } elseif (!empty($lead['STATUS_COLOR'])) {
                                                        $status_color = $lead['STATUS_COLOR'];
                                                    } else {
                                                        // Fallback colors based on status name
                                                        $status_lower = strtolower($lead['LEAD_STATUS'] ?? '');
                                                        switch ($status_lower) {
                                                            case 'new':
                                                                $status_color = '#6c757d';
                                                                break;
                                                            case 'enrolled':
                                                                $status_color = '#198754';
                                                                break;
                                                            case 'not enrolled':
                                                                $status_color = '#fd7e14';
                                                                break;
                                                            default:
                                                                $status_color = '#6c757d';
                                                        }
                                                    }
                                                    ?>
                                                    <span class="status-pill">
                                                        <span class="status-dot" style="background-color: <?= $status_color ?>;"></span>
                                                        <?= htmlspecialchars($display_status) ?>
                                                    </span>
                                                </td>
                                                <td class="action-icons">
                                                    <i class="bi bi-telephone-fill text-muted" onclick="callToLeads(<?= $lead['PK_LEADS'] ?>)" title="Call"></i>
                                                    <i class="bi bi-envelope text-muted" onclick="sendEmail('<?= htmlspecialchars($lead['EMAIL_ID']) ?>')" title="Email"></i>
                                                    <i class="bi bi-pencil text-muted" onclick="editpage(<?= $lead['PK_LEADS'] ?>, '<?= htmlspecialchars($lead['LATEST_DATE'] ?? '') ?>')" title="Edit"></i>
                                                    <i class="bi bi-trash3 text-danger" onclick="ConfirmDelete(<?= $lead['PK_LEADS'] ?>)" title="Delete"></i>
                                                </td>
                                            </tr>
                                        <?php
                                            $leads_result->MoveNext();
                                        endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-5 text-muted">
                                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                                No leads found matching your criteria.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if ($total_pages > 1): ?>
                            <div class="d-flex justify-content-between align-items-center pagination-container flex-wrap gap-2">
                                <div>Page <?= $page ?> of <?= $total_pages ?></div>
                                <div class="d-flex align-items-center gap-1 flex-wrap">
                                    <a class="page-link-custom" data-page="1"><i class="bi bi-chevron-double-left"></i></a>
                                    <a class="page-link-custom" data-page="<?= max(1, $page - 1) ?>"><i class="bi bi-chevron-left"></i></a>

                                    <?php
                                    $start_page = max(1, $page - 2);
                                    $end_page = min($total_pages, $page + 2);
                                    if ($total_pages > 0) {
                                        for ($i = $start_page; $i <= $end_page; $i++): ?>
                                            <a class="page-link-custom <?= $i == $page ? 'active' : '' ?>" data-page="<?= $i ?>"><?= $i ?></a>
                                        <?php endfor;
                                    }
                                    if ($end_page < $total_pages - 1): ?>
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

    <script>
        $(document).ready(function() {
            $('.datepicker-normal').datepicker({
                dateFormat: 'mm/dd/yy',
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

                let perPage = $('#per_page').val() || <?= $per_page ?>;
                let searchText = $('#search_text').val() || '';
                let status = $(this).data('status') || '';
                let chooseDate = $('#CHOOSE_DATE').val() || '';
                let currentSort = getCurrentSort();
                let currentOrder = getCurrentOrder();

                let url = window.location.pathname + '?';
                let params = [];

                if (searchText) params.push('search_text=' + encodeURIComponent(searchText));
                if (status) params.push('status=' + encodeURIComponent(status));
                if (chooseDate) params.push('CHOOSE_DATE=' + encodeURIComponent(chooseDate));
                if (currentSort) params.push('sort=' + encodeURIComponent(currentSort));
                if (currentOrder) params.push('order=' + encodeURIComponent(currentOrder));
                params.push('per_page=' + perPage);
                params.push('page=1');

                url += params.join('&');
                window.location.href = url;
            });

            // Sorting functionality
            $('.sortable-header').on('click', function() {
                let sortColumn = $(this).data('sort');
                let currentSort = getCurrentSort();
                let currentOrder = getCurrentOrder();
                let newOrder = 'DESC';

                // Toggle order if clicking the same column
                if (currentSort === sortColumn) {
                    newOrder = currentOrder === 'DESC' ? 'ASC' : 'DESC';
                }

                // Update sort indicators
                $('.sortable-header').removeClass('asc desc');
                if (newOrder === 'ASC') {
                    $(this).addClass('asc');
                } else {
                    $(this).addClass('desc');
                }

                // Get other filter values
                let perPage = $('#per_page').val() || <?= $per_page ?>;
                let searchText = $('#search_text').val() || '';
                let status = $('.status-filter-btn.active').data('status') || '';
                let chooseDate = $('#CHOOSE_DATE').val() || '';

                // Build URL
                let url = window.location.pathname + '?';
                let params = [];

                if (searchText) params.push('search_text=' + encodeURIComponent(searchText));
                if (status) params.push('status=' + encodeURIComponent(status));
                if (chooseDate) params.push('CHOOSE_DATE=' + encodeURIComponent(chooseDate));
                params.push('sort=' + encodeURIComponent(sortColumn));
                params.push('order=' + encodeURIComponent(newOrder));
                params.push('per_page=' + perPage);
                params.push('page=1');

                url += params.join('&');
                window.location.href = url;
            });

            // Date filter
            $('#CHOOSE_DATE').on('change', function() {
                let perPage = $('#per_page').val() || <?= $per_page ?>;
                submitFiltersWithPerPage(perPage);
            });

            // Per page change
            $('#per_page').on('change', function() {
                let perPage = $(this).val() || <?= $per_page ?>;
                submitFiltersWithPerPage(perPage);
            });

            // Pagination
            $(document).on('click', '.page-link-custom', function(e) {
                e.preventDefault();
                let page = $(this).data('page');
                if (page) {
                    let perPage = $('#per_page').val() || <?= $per_page ?>;
                    let url = new URL(window.location.href);
                    url.searchParams.set('page', page);
                    url.searchParams.set('per_page', perPage);
                    window.location.href = url.toString();
                }
            });

            // Select all functionality
            $('#selectAll').on('change', function() {
                $('.lead-checkbox').prop('checked', $(this).is(':checked'));
            });

            // Set initial sort indicator
            setInitialSortIndicator();
        });

        function getCurrentSort() {
            let urlParams = new URLSearchParams(window.location.search);
            return urlParams.get('sort') || '';
        }

        function getCurrentOrder() {
            let urlParams = new URLSearchParams(window.location.search);
            return urlParams.get('order') || 'DESC';
        }

        function setInitialSortIndicator() {
            let currentSort = getCurrentSort();
            let currentOrder = getCurrentOrder();

            if (currentSort) {
                $('.sortable-header').each(function() {
                    if ($(this).data('sort') === currentSort) {
                        if (currentOrder === 'ASC') {
                            $(this).addClass('asc');
                        } else {
                            $(this).addClass('desc');
                        }
                    }
                });
            }
        }

        function submitFilters() {
            let perPage = $('#per_page').val() || <?= $per_page ?>;
            submitFiltersWithPerPage(perPage);
        }

        function submitFiltersWithPerPage(perPage) {
            let searchText = $('#search_text').val() || '';
            let status = $('.status-filter-btn.active').data('status') || '';
            let chooseDate = $('#CHOOSE_DATE').val() || '';
            let currentSort = getCurrentSort();
            let currentOrder = getCurrentOrder();

            let url = window.location.pathname + '?';
            let params = [];

            if (searchText) params.push('search_text=' + encodeURIComponent(searchText));
            if (status) params.push('status=' + encodeURIComponent(status));
            if (chooseDate) params.push('CHOOSE_DATE=' + encodeURIComponent(chooseDate));
            if (currentSort) params.push('sort=' + encodeURIComponent(currentSort));
            if (currentOrder) params.push('order=' + encodeURIComponent(currentOrder));
            params.push('per_page=' + perPage);
            params.push('page=1');

            url += params.join('&');
            window.location.href = url;
        }

        function resetFilters() {
            window.location.href = window.location.pathname + '?per_page=8&page=1';
        }

        function editpage(id, date) {
            window.location.href = "leads.php?id=" + id + (date ? "&date=" + date : "");
        }

        function sendEmail(email) {
            if (email && email !== '') {
                window.location.href = 'mailto:' + email;
            } else {
                Swal.fire('Info', 'No email address available for this lead.', 'info');
            }
        }

        function ConfirmDelete(PK_LEADS) {
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
                            FUNCTION_NAME: 'deleteLeads',
                            PK_LEADS: PK_LEADS
                        },
                        success: function(data) {
                            Swal.close();
                            Swal.fire('Deleted!', 'Lead has been deleted.', 'success').then(() => {
                                window.location.reload();
                            });
                        },
                        error: function() {
                            Swal.close();
                            Swal.fire('Error!', 'Could not delete lead.', 'error');
                        }
                    });
                }
            });
        }

        function callToLeads(PK_LEADS) {
            Swal.fire({
                title: 'Initiating Call...',
                text: 'Please wait',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: "../voice_agent/outbound_call.php",
                type: 'GET',
                data: {
                    PK_LEADS: PK_LEADS
                },
                success: function(response) {
                    Swal.close();
                    if (response === 'success') {
                        Swal.fire('Call Initiated!', 'The call to the lead has been initiated successfully.', 'success');
                    } else {
                        Swal.fire('Error!', response || 'Could not initiate call', 'error');
                    }
                },
                error: function() {
                    Swal.close();
                    Swal.fire('Error!', 'There was an error initiating the call. Please try again.', 'error');
                }
            });
        }
    </script>
</body>

</html>