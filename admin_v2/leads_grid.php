<?php
require_once('../global/config.php');
$title = "All Leads";

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];

// ==============================================
// Get filter parameters - DEFINE ALL VARIABLES
// ==============================================
$search_text = isset($_GET['search_text']) ? trim($_GET['search_text']) : '';
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
$choose_date = isset($_GET['CHOOSE_DATE']) && $_GET['CHOOSE_DATE'] != '' ? date('Y-m-d', strtotime($_GET['CHOOSE_DATE'])) : '';

// Build search condition
$search_condition = '';
if ($search_text != '') {
    $search_escaped = $search_text;
    $search_condition = " AND (DOA_LEADS.FIRST_NAME LIKE '%$search_escaped%' 
                     OR DOA_LEADS.LAST_NAME LIKE '%$search_escaped%' 
                     OR DOA_LEADS.PHONE LIKE '%$search_escaped%' 
                     OR DOA_LEADS.EMAIL_ID LIKE '%$search_escaped%' 
                     OR LS.LEAD_STATUS LIKE '%$search_escaped%')";
}

// Build status condition
$status_condition = '';
if ($status_filter != '' && $status_filter != 'inactive') {
    $status_condition = " AND DOA_LEADS.PK_LEAD_STATUS = " . (int)$status_filter . " AND DOA_LEADS.ACTIVE = 1";
} elseif ($status_filter == 'inactive') {
    $status_condition = " AND DOA_LEADS.ACTIVE = 0";
} else {
    $status_condition = " AND DOA_LEADS.ACTIVE = 1";
}

// Build date condition
$date_condition = '';
if ($choose_date != '') {
    $date_condition = " AND EXISTS (SELECT 1 FROM DOA_LEAD_DATE 
                       WHERE PK_LEADS = DOA_LEADS.PK_LEADS 
                       AND DATE = '$choose_date')";
}

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '') {
    header("location:../login.php");
    exit;
}

// Insert default statuses if not exist
$lead_status = ['New' => '#fffbb9', 'Enrolled' => '#96d35f', 'Not Enrolled' => '#ffa57d'];
$i = 1;
foreach ($lead_status as $key => $value) {
    $is_exist = $db->Execute("SELECT * FROM DOA_LEAD_STATUS WHERE LEAD_STATUS='" . $key . "' AND PK_ACCOUNT_MASTER='" . $_SESSION['PK_ACCOUNT_MASTER'] . "'");
    if ($is_exist->RecordCount() == 0) {
        $lead_status_data['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];
        $lead_status_data['LEAD_STATUS'] = $key;
        $lead_status_data['STATUS_COLOR'] = $value;
        $lead_status_data['DISPLAY_ORDER'] = $i;
        $lead_status_data['ACTIVE'] = 1;
        db_perform('DOA_LEAD_STATUS', $lead_status_data, 'insert');
    }
    $i++;
}

// Get all statuses for filter dropdown - store in array to avoid pointer issues
$all_status_sql = "SELECT * FROM `DOA_LEAD_STATUS` 
                   WHERE ACTIVE = 1 
                   AND `PK_ACCOUNT_MASTER` = " . $_SESSION['PK_ACCOUNT_MASTER'] . " 
                   ORDER BY DISPLAY_ORDER ASC";
$all_status_result = $db->Execute($all_status_sql);

// Store statuses in an array for reuse
$statuses_array = array();
if ($all_status_result && $all_status_result->RecordCount() > 0) {
    while (!$all_status_result->EOF) {
        $statuses_array[] = array(
            'PK_LEAD_STATUS' => $all_status_result->fields['PK_LEAD_STATUS'],
            'LEAD_STATUS' => $all_status_result->fields['LEAD_STATUS'],
            'STATUS_COLOR' => $all_status_result->fields['STATUS_COLOR']
        );
        $all_status_result->MoveNext();
    }
}

// Helper function to truncate text
function truncateText($text, $length = 100)
{
    if (empty($text)) return '—';
    if (strlen($text) <= $length) return htmlspecialchars($text);
    return htmlspecialchars(substr($text, 0, $length)) . '...';
}

// Determine if we should show only one status column
$show_single_column = false;
$selected_status_id = null;

if ($status_filter != '' && $status_filter != 'inactive') {
    $show_single_column = true;
    $selected_status_id = (int)$status_filter;
}
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'layout/header_script.php'; ?>

<?php include 'layout/header.php'; ?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leads Kanban Board</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <link href="//maxcdn.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
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
            left: 12px;
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

        .status-filter-btn {
            cursor: pointer;
            transition: all 0.2s;
        }

        .status-filter-btn.active {
            background-color: #39b54a !important;
            color: white !important;
        }

        .kanban-col {
            background-color: #f1f3f5;
            border-radius: 12px;
            padding: 12px;
            min-height: 80vh;
        }

        .col-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding: 0 5px;
        }

        .col-title {
            font-weight: 600;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 8px;
            display: inline-block;
        }

        .count-badge {
            font-size: 0.8rem;
            color: #6c757d;
            font-weight: 400;
        }

        .lead-card {
            background: #fff;
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 12px;
            border: 1px solid transparent;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
            transition: transform 0.1s ease;
            cursor: grab;
        }

        .lead-card:hover {
            border-color: #dee2e6;
            transform: translateY(-2px);
        }

        .avatar-lp {
            width: 36px;
            height: 36px;
            background-color: #fee2e2;
            color: #b91c1c;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.75rem;
            margin-right: 12px;
        }

        .lead-name {
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 0;
            cursor: pointer;
        }

        .lead-name:hover {
            color: #39b54a;
        }

        .lead-email {
            font-size: 0.75rem;
            color: #6c757d;
        }

        .lead-footer {
            font-size: 0.75rem;
            color: #6c757d;
            margin-top: 15px;
        }

        .icon-circle {
            width: 45px;
            height: 45px;
            text-align: center;
            border-radius: 25px;
        }

        .btn-success {
            background-color: #39b54a;
        }

        .kanban-col.list-group {
            padding: 0;
        }

        .sortable-ghost {
            opacity: 0.4;
            background-color: #f0f0f0;
        }

        .sortable-chosen {
            opacity: 1;
        }

        .lead-icons {
            display: flex;
            gap: 12px;
            margin-top: 12px;
            padding-top: 8px;
            border-top: 1px solid #f0f0f0;
        }

        .icon-with-pill {
            position: relative;
            display: flex;
            align-items: center;
        }

        .icon-with-pill i {
            color: #39b54a;
            font-size: 14px;
            cursor: pointer;
        }

        .pill {
            background-color: #eefdf0ff;
            color: #39b54a;
            font-size: 0.7rem;
            font-weight: 500;
            padding: 3px 8px;
            border-radius: 20px;
            white-space: nowrap;
            margin-left: 6px;
            display: none;
        }

        .pill.show {
            display: inline-block;
        }

        .card-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .kanban-board-wrapper {
            overflow-x: auto;
            overflow-y: hidden;
            width: 100%;
            position: relative;
        }

        .kanban-board-container {
            display: flex;
            flex-direction: row;
            flex-wrap: nowrap;
            gap: 16px;
            min-width: min-content;
            padding-bottom: 10px;
        }

        .kanban-board-container .kanban-col-wrapper {
            flex: 0 0 320px;
            min-width: 320px;
        }

        .kanban-col {
            background-color: #f1f3f5;
            border-radius: 12px;
            padding: 12px;
            height: calc(100vh - 280px);
            min-height: 500px;
            display: flex;
            flex-direction: column;
        }

        .kanban-col .list-group {
            overflow-y: auto;
            flex: 1;
            padding-right: 5px;
        }

        .kanban-board-wrapper::-webkit-scrollbar {
            height: 8px;
        }

        .kanban-board-wrapper::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .kanban-board-wrapper::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 10px;
        }

        .kanban-board-wrapper::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        .kanban-col .list-group::-webkit-scrollbar {
            width: 6px;
        }

        .kanban-col .list-group::-webkit-scrollbar-track {
            background: #e9ecef;
            border-radius: 10px;
        }

        .kanban-col .list-group::-webkit-scrollbar-thumb {
            background: #adb5bd;
            border-radius: 10px;
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
                        <button class="btn btn-success border-0 rounded-pill px-3" onclick="window.location.href='leads.php'"><i class="bi bi-plus-lg me-1"></i> Create New Lead</button>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                        <div class="search-container">
                            <i class="fa fa-search"></i>
                            <input type="text" id="search_text" class="form-control" placeholder="Search by name, email, phone..." value="<?= htmlspecialchars($search_text) ?>">
                        </div>

                        <div class="d-flex gap-2 align-items-center flex-wrap">
                            <div class="btn-group bg-white border rounded-pill p-1">
                                <button class="btn btn-sm status-filter-btn rounded-pill px-3 <?= ($status_filter == '') ? 'active' : '' ?>" data-status="">All</button>
                                <?php
                                // Show dynamic statuses from array
                                foreach ($statuses_array as $status) {
                                    $status_val = $status['PK_LEAD_STATUS'];
                                    $status_label = $status['LEAD_STATUS'];
                                ?>
                                    <button class="btn btn-sm status-filter-btn rounded-pill px-3 <?= ($status_filter == $status_val) ? 'active' : '' ?>" data-status="<?= $status_val ?>"><?= htmlspecialchars($status_label) ?></button>
                                <?php
                                }
                                ?>
                                <button class="btn btn-sm status-filter-btn rounded-pill px-3 <?= ($status_filter == 'inactive') ? 'active' : '' ?>" data-status="inactive">Inactive</button>
                            </div>
                            <div class="btn-group ms-2 border rounded-pill p-1" style="border-radius: 20px !important;">
                                <button class="toolbar-btn me-1 border-0 rounded-pill" id="kanban_view_btn" style="background: #39b54a; color: white;">
                                    <i class="bi bi-grid-3x3-gap-fill"></i>
                                </button>
                                <button class="toolbar-btn border-0 rounded-pill" id="list_view_btn" style="background: transparent; color: #6c757d;" onclick="window.location.href='leads_list.php'">
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

                    <div class="kanban-board-wrapper">
                        <div class="kanban-board-container" id="kanban_container">
                            <?php
                            // If filtering by a specific status (not 'All' and not 'inactive'), show only that column
                            if ($show_single_column && $selected_status_id) {
                                // Find the selected status from the array
                                $selected_status_data = null;
                                foreach ($statuses_array as $status_data) {
                                    if ($status_data['PK_LEAD_STATUS'] == $selected_status_id) {
                                        $selected_status_data = $status_data;
                                        break;
                                    }
                                }

                                if ($selected_status_data) {
                                    $status_id = $selected_status_data['PK_LEAD_STATUS'];
                                    $status_name = $selected_status_data['LEAD_STATUS'];
                                    $status_color = $selected_status_data['STATUS_COLOR'] ?: '#6c757d';

                                    // Build query with ALL filter conditions
                                    $leads_query = "
                                                    SELECT DISTINCT 
                                                        DOA_LEADS.PK_LEADS, 
                                                        DOA_LEADS.FIRST_NAME,
                                                        DOA_LEADS.LAST_NAME,
                                                        CONCAT(DOA_LEADS.FIRST_NAME, ' ', DOA_LEADS.LAST_NAME) AS NAME, 
                                                        DOA_LEADS.PHONE, 
                                                        DOA_LEADS.EMAIL_ID, 
                                                        LS.LEAD_STATUS, 
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
                                                    FROM `DOA_LEADS` 
                                                    INNER JOIN " . $master_database . ".DOA_LOCATION AS DOA_LOCATION 
                                                        ON DOA_LOCATION.PK_LOCATION = DOA_LEADS.PK_LOCATION 
                                                    LEFT JOIN DOA_LEAD_STATUS AS LS 
                                                        ON DOA_LEADS.PK_LEAD_STATUS = LS.PK_LEAD_STATUS 
                                                    WHERE DOA_LEADS.PK_LEAD_STATUS = " . $status_id . " 
                                                        AND DOA_LEADS.PK_LOCATION IN (" . $DEFAULT_LOCATION_ID . ")
                                                        " . $status_condition . " 
                                                        " . $search_condition . " 
                                                        " . $date_condition . "
                                                    ORDER BY DOA_LEADS.PK_LEADS DESC";

                                    $leads_result = $db->Execute($leads_query);
                                    $lead_count = $leads_result->RecordCount();
                            ?>
                                    <div class="kanban-col-wrapper" style="flex: 0 0 18%; min-width: auto;">
                                        <div class="kanban-col">
                                            <div class="col-header">
                                                <div class="col-title">
                                                    <span class="status-dot" style="background: <?= $status_color ?>;"></span>
                                                    <?= htmlspecialchars($status_name) ?>
                                                </div>
                                                <span class="count-badge"><?= $lead_count ?> leads</span>
                                            </div>
                                            <div id="col-<?= preg_replace('/[^a-z0-9]/i', '-', strtolower($status_name)) ?>" class="list-group" data-status-id="<?= $status_id ?>" style="overflow-y: auto; flex: 1;">
                                                <?php if ($leads_result && $leads_result->RecordCount() > 0): ?>
                                                    <?php while (!$leads_result->EOF):
                                                        $lead = $leads_result->fields;
                                                        $CUSTOMER_NAME = $lead['NAME'];
                                                        $customer = getProfileBadge($CUSTOMER_NAME);
                                                        $customer_initial = $customer['initials'];
                                                        $customer_color = $customer['color'];
                                                        $follow_up_date = (!empty($lead['LATEST_DATE']) && $lead['LATEST_DATE'] != '0000-00-00')
                                                            ? date('m/d/Y', strtotime($lead['LATEST_DATE']))
                                                            : 'N/A';
                                                    ?>
                                                        <div class="lead-card" data-id="<?= $lead['PK_LEADS'] ?>">
                                                            <div style="float: right;" class="card-actions">
                                                                <?php if ($lead['IS_APPOINTMENT_CREATED']) { ?>
                                                                    <i class="bi bi-star-fill" style="color: gold;"></i>
                                                                <?php } ?>
                                                                <?php if ($lead['IS_CALLED']) { ?>
                                                                    <i class="bi bi-check-square-fill" style="color: #39b54a;"></i>
                                                                <?php } ?>
                                                                <i class="bi bi-trash3" onclick="ConfirmDelete(<?= $lead['PK_LEADS'] ?>);" style="color: red; cursor: pointer;"></i>
                                                            </div>
                                                            <div class="d-flex align-items-center mb-2">
                                                                <div><span class="avatarname" style="color: #fff; background-color: <?= $customer_color ?>;"><?= $customer_initial; ?></span></div>
                                                                <div>
                                                                    <p class="lead-name" onclick="editpage(<?= $lead['PK_LEADS'] ?>, '<?= $lead['LATEST_DATE'] ?? '' ?>');"><?= htmlspecialchars($lead['NAME']) ?></p>
                                                                    <p class="lead-email mb-0"><?= htmlspecialchars($lead['EMAIL_ID']) ?></p>
                                                                </div>
                                                            </div>
                                                            <div class="lead-footer d-flex justify-content-between">
                                                                <span><?= htmlspecialchars($lead['LOCATION_NAME']) ?></span>
                                                                <span style="text-align: right;"><?= htmlspecialchars($lead['OPPORTUNITY_SOURCE'] ?: '—') ?></span>
                                                            </div>
                                                            <div class="lead-icons">
                                                                <div class="icon-with-pill">
                                                                    <i class="bi bi-telephone-fill toggle-pill" data-target="pill-phone-<?= $lead['PK_LEADS'] ?>"></i>
                                                                    <span class="pill pill-phone-<?= $lead['PK_LEADS'] ?>"><?= htmlspecialchars($lead['PHONE']) ?></span>
                                                                </div>
                                                                <div class="icon-with-pill">
                                                                    <i class="bi bi-envelope-fill toggle-pill" data-target="pill-email-<?= $lead['PK_LEADS'] ?>"></i>
                                                                    <span class="pill pill-email-<?= $lead['PK_LEADS'] ?>"><?= htmlspecialchars($lead['EMAIL_ID']) ?></span>
                                                                </div>
                                                                <div class="icon-with-pill">
                                                                    <i class="bi bi-chat-dots-fill toggle-pill" data-target="pill-chat-<?= $lead['PK_LEADS'] ?>"></i>
                                                                    <span class="pill pill-chat-<?= $lead['PK_LEADS'] ?>"><?= truncateText($lead['DESCRIPTION'], 30) ?></span>
                                                                </div>
                                                                <div class="icon-with-pill">
                                                                    <i class="bi bi-calendar-fill toggle-pill" data-target="pill-calendar-<?= $lead['PK_LEADS'] ?>"></i>
                                                                    <span class="pill pill-calendar-<?= $lead['PK_LEADS'] ?>"><?= date('m/d/Y - h:iA', strtotime($lead['CREATED_ON'])) ?></span>
                                                                </div>
                                                                <div class="icon-with-pill" style="font-size: 18px;">
                                                                    <i class="bi bi-telephone-plus-fill" onclick="callToLeads(<?= $lead['PK_LEADS'] ?>)" style="cursor: pointer;" title="AI Call"></i>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php
                                                        $leads_result->MoveNext();
                                                    endwhile; ?>
                                                <?php else: ?>
                                                    <div class="text-center text-muted py-3">No leads found</div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php
                                }
                            }
                            // If filtering by inactive, show only inactive column
                            elseif ($status_filter == 'inactive') {
                                // Inactive column query
                                $inactive_query = "
                                                    SELECT DISTINCT 
                                                        DOA_LEADS.PK_LEADS, 
                                                        DOA_LEADS.FIRST_NAME,
                                                        DOA_LEADS.LAST_NAME,
                                                        CONCAT(DOA_LEADS.FIRST_NAME, ' ', DOA_LEADS.LAST_NAME) AS NAME, 
                                                        DOA_LEADS.PHONE, 
                                                        DOA_LEADS.EMAIL_ID, 
                                                        LS.LEAD_STATUS, 
                                                        DOA_LEADS.DESCRIPTION, 
                                                        DOA_LEADS.OPPORTUNITY_SOURCE, 
                                                        DOA_LEADS.ACTIVE, 
                                                        DOA_LEADS.CREATED_ON, 
                                                        DOA_LOCATION.LOCATION_NAME 
                                                    FROM `DOA_LEADS` 
                                                    INNER JOIN " . $master_database . ".DOA_LOCATION AS DOA_LOCATION 
                                                        ON DOA_LOCATION.PK_LOCATION = DOA_LEADS.PK_LOCATION 
                                                    LEFT JOIN DOA_LEAD_STATUS AS LS 
                                                        ON DOA_LEADS.PK_LEAD_STATUS = LS.PK_LEAD_STATUS 
                                                    WHERE DOA_LEADS.PK_LOCATION IN (" . $DEFAULT_LOCATION_ID . ") 
                                                        AND DOA_LEADS.ACTIVE = 0 
                                                        " . $search_condition . " 
                                                        " . $date_condition . "
                                                    ORDER BY DOA_LEADS.PK_LEADS DESC";

                                $inactive_result = $db->Execute($inactive_query);
                                ?>
                                <div class="kanban-col-wrapper" style="flex: 0 0 18%; min-width: auto;">
                                    <div class="kanban-col">
                                        <div class="col-header">
                                            <div class="col-title">
                                                <span class="status-dot" style="background: #dc3545;"></span>
                                                Inactive
                                            </div>
                                            <span class="count-badge"><?= $inactive_result->RecordCount() ?> leads</span>
                                        </div>
                                        <div id="col-inactive" class="list-group" data-status-id="inactive" style="overflow-y: auto; flex: 1;">
                                            <?php if ($inactive_result && $inactive_result->RecordCount() > 0): ?>
                                                <?php while (!$inactive_result->EOF):
                                                    $lead = $inactive_result->fields;
                                                    $CUSTOMER_NAME = $lead['NAME'];
                                                    $customer = getProfileBadge($CUSTOMER_NAME);
                                                    $customer_initial = $customer['initials'];
                                                    $customer_color = $customer['color'];
                                                ?>
                                                    <div class="lead-card" data-id="<?= $lead['PK_LEADS'] ?>">
                                                        <div style="float: right;" class="card-actions">
                                                            <i class="bi bi-trash3" onclick="ConfirmDelete(<?= $lead['PK_LEADS'] ?>);" style="color: red; cursor: pointer;"></i>
                                                        </div>
                                                        <div class="d-flex align-items-center mb-2">
                                                            <div><span class="avatarname" style="color: #fff; background-color: <?= $customer_color ?>;"><?= $customer_initial; ?></span></div>
                                                            <div>
                                                                <p class="lead-name" onclick="editpage(<?= $lead['PK_LEADS'] ?>);"><?= htmlspecialchars($lead['NAME']) ?></p>
                                                                <p class="lead-email mb-0"><?= htmlspecialchars($lead['EMAIL_ID']) ?></p>
                                                            </div>
                                                        </div>
                                                        <div class="lead-footer d-flex justify-content-between">
                                                            <span><?= htmlspecialchars($lead['LOCATION_NAME']) ?></span>
                                                            <span><?= htmlspecialchars($lead['OPPORTUNITY_SOURCE'] ?: '—') ?></span>
                                                        </div>
                                                        <div class="lead-icons">
                                                            <div class="icon-with-pill">
                                                                <i class="bi bi-telephone-fill toggle-pill" data-target="pill-phone-<?= $lead['PK_LEADS'] ?>"></i>
                                                                <span class="pill pill-phone-<?= $lead['PK_LEADS'] ?>"><?= htmlspecialchars($lead['PHONE']) ?></span>
                                                            </div>
                                                            <div class="icon-with-pill">
                                                                <i class="bi bi-envelope-fill toggle-pill" data-target="pill-email-<?= $lead['PK_LEADS'] ?>"></i>
                                                                <span class="pill pill-email-<?= $lead['PK_LEADS'] ?>"><?= htmlspecialchars($lead['EMAIL_ID']) ?></span>
                                                            </div>
                                                            <div class="icon-with-pill">
                                                                <i class="bi bi-chat-dots-fill toggle-pill" data-target="pill-chat-<?= $lead['PK_LEADS'] ?>"></i>
                                                                <span class="pill pill-chat-<?= $lead['PK_LEADS'] ?>"><?= truncateText($lead['DESCRIPTION'], 30) ?></span>
                                                            </div>
                                                            <div class="icon-with-pill">
                                                                <i class="bi bi-calendar-fill toggle-pill" data-target="pill-calendar-<?= $lead['PK_LEADS'] ?>"></i>
                                                                <span class="pill pill-calendar-<?= $lead['PK_LEADS'] ?>"><?= date('m/d/Y - h:iA', strtotime($lead['CREATED_ON'])) ?></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php
                                                    $inactive_result->MoveNext();
                                                endwhile; ?>
                                            <?php else: ?>
                                                <div class="text-center text-muted py-3">No inactive leads</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php
                            } else {
                                // Show all status columns (original code)
                                // Loop through statuses array for columns
                                foreach ($statuses_array as $status_data) {
                                    $status_id = $status_data['PK_LEAD_STATUS'];
                                    $status_name = $status_data['LEAD_STATUS'];
                                    $status_color = $status_data['STATUS_COLOR'] ?: '#6c757d';

                                    // Build query with ALL filter conditions
                                    $leads_query = "
                                                    SELECT DISTINCT 
                                                        DOA_LEADS.PK_LEADS, 
                                                        DOA_LEADS.FIRST_NAME,
                                                        DOA_LEADS.LAST_NAME,
                                                        CONCAT(DOA_LEADS.FIRST_NAME, ' ', DOA_LEADS.LAST_NAME) AS NAME, 
                                                        DOA_LEADS.PHONE, 
                                                        DOA_LEADS.EMAIL_ID, 
                                                        LS.LEAD_STATUS, 
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
                                                    FROM `DOA_LEADS` 
                                                    INNER JOIN " . $master_database . ".DOA_LOCATION AS DOA_LOCATION 
                                                        ON DOA_LOCATION.PK_LOCATION = DOA_LEADS.PK_LOCATION 
                                                    LEFT JOIN DOA_LEAD_STATUS AS LS 
                                                        ON DOA_LEADS.PK_LEAD_STATUS = LS.PK_LEAD_STATUS 
                                                    WHERE DOA_LEADS.PK_LEAD_STATUS = " . $status_id . " 
                                                        AND DOA_LEADS.PK_LOCATION IN (" . $DEFAULT_LOCATION_ID . ")
                                                        " . $status_condition . " 
                                                        " . $search_condition . " 
                                                        " . $date_condition . "
                                                    ORDER BY DOA_LEADS.PK_LEADS DESC";

                                    $leads_result = $db->Execute($leads_query);
                                    $lead_count = $leads_result->RecordCount();
                                ?>
                                    <div class="kanban-col-wrapper">
                                        <div class="kanban-col">
                                            <div class="col-header">
                                                <div class="col-title">
                                                    <span class="status-dot" style="background: <?= $status_color ?>;"></span>
                                                    <?= htmlspecialchars($status_name) ?>
                                                </div>
                                                <span class="count-badge"><?= $lead_count ?> leads</span>
                                            </div>
                                            <div id="col-<?= preg_replace('/[^a-z0-9]/i', '-', strtolower($status_name)) ?>" class="list-group" data-status-id="<?= $status_id ?>" style="overflow-y: auto; flex: 1;">
                                                <?php if ($leads_result && $leads_result->RecordCount() > 0): ?>
                                                    <?php while (!$leads_result->EOF):
                                                        $lead = $leads_result->fields;
                                                        $CUSTOMER_NAME = $lead['NAME'];
                                                        $customer = getProfileBadge($CUSTOMER_NAME);
                                                        $customer_initial = $customer['initials'];
                                                        $customer_color = $customer['color'];
                                                        $follow_up_date = (!empty($lead['LATEST_DATE']) && $lead['LATEST_DATE'] != '0000-00-00')
                                                            ? date('m/d/Y', strtotime($lead['LATEST_DATE']))
                                                            : 'N/A';
                                                    ?>
                                                        <div class="lead-card" data-id="<?= $lead['PK_LEADS'] ?>">
                                                            <div style="float: right;" class="card-actions">
                                                                <?php if ($lead['IS_APPOINTMENT_CREATED']) { ?>
                                                                    <i class="bi bi-star-fill" style="color: gold;"></i>
                                                                <?php } ?>
                                                                <?php if ($lead['IS_CALLED']) { ?>
                                                                    <i class="bi bi-check-square-fill" style="color: #39b54a;"></i>
                                                                <?php } ?>
                                                                <i class="bi bi-trash3" onclick="ConfirmDelete(<?= $lead['PK_LEADS'] ?>);" style="color: red; cursor: pointer;"></i>
                                                            </div>
                                                            <div class="d-flex align-items-center mb-2">
                                                                <div><span class="avatarname" style="color: #fff; background-color: <?= $customer_color ?>;"><?= $customer_initial; ?></span></div>
                                                                <div>
                                                                    <p class="lead-name" onclick="editpage(<?= $lead['PK_LEADS'] ?>, '<?= $lead['LATEST_DATE'] ?? '' ?>');"><?= htmlspecialchars($lead['NAME']) ?></p>
                                                                    <p class="lead-email mb-0"><?= htmlspecialchars($lead['EMAIL_ID']) ?></p>
                                                                </div>
                                                            </div>
                                                            <div class="lead-footer d-flex justify-content-between">
                                                                <span><?= htmlspecialchars($lead['LOCATION_NAME']) ?></span>
                                                                <span style="text-align: right;"><?= htmlspecialchars($lead['OPPORTUNITY_SOURCE'] ?: '—') ?></span>
                                                            </div>
                                                            <div class="lead-icons">
                                                                <div class="icon-with-pill">
                                                                    <i class="bi bi-telephone-fill toggle-pill" data-target="pill-phone-<?= $lead['PK_LEADS'] ?>"></i>
                                                                    <span class="pill pill-phone-<?= $lead['PK_LEADS'] ?>"><?= htmlspecialchars($lead['PHONE']) ?></span>
                                                                </div>
                                                                <div class="icon-with-pill">
                                                                    <i class="bi bi-envelope-fill toggle-pill" data-target="pill-email-<?= $lead['PK_LEADS'] ?>"></i>
                                                                    <span class="pill pill-email-<?= $lead['PK_LEADS'] ?>"><?= htmlspecialchars($lead['EMAIL_ID']) ?></span>
                                                                </div>
                                                                <div class="icon-with-pill">
                                                                    <i class="bi bi-chat-dots-fill toggle-pill" data-target="pill-chat-<?= $lead['PK_LEADS'] ?>"></i>
                                                                    <span class="pill pill-chat-<?= $lead['PK_LEADS'] ?>"><?= truncateText($lead['DESCRIPTION'], 30) ?></span>
                                                                </div>
                                                                <div class="icon-with-pill">
                                                                    <i class="bi bi-calendar-fill toggle-pill" data-target="pill-calendar-<?= $lead['PK_LEADS'] ?>"></i>
                                                                    <span class="pill pill-calendar-<?= $lead['PK_LEADS'] ?>"><?= date('m/d/Y - h:iA', strtotime($lead['CREATED_ON'])) ?></span>
                                                                </div>
                                                                <div class="icon-with-pill" style="font-size: 18px;">
                                                                    <i class="bi bi-telephone-plus-fill" onclick="callToLeads(<?= $lead['PK_LEADS'] ?>)" style="cursor: pointer;" title="AI Call"></i>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php
                                                        $leads_result->MoveNext();
                                                    endwhile; ?>
                                                <?php else: ?>
                                                    <div class="text-center text-muted py-3">No leads</div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php
                                }
                                ?>

                                <?php
                                // Inactive column with ALL filters applied (only show when not filtering by a specific status)
                                $inactive_query = "
                                                    SELECT DISTINCT 
                                                        DOA_LEADS.PK_LEADS, 
                                                        DOA_LEADS.FIRST_NAME,
                                                        DOA_LEADS.LAST_NAME,
                                                        CONCAT(DOA_LEADS.FIRST_NAME, ' ', DOA_LEADS.LAST_NAME) AS NAME, 
                                                        DOA_LEADS.PHONE, 
                                                        DOA_LEADS.EMAIL_ID, 
                                                        LS.LEAD_STATUS, 
                                                        DOA_LEADS.DESCRIPTION, 
                                                        DOA_LEADS.OPPORTUNITY_SOURCE, 
                                                        DOA_LEADS.ACTIVE, 
                                                        DOA_LEADS.CREATED_ON, 
                                                        DOA_LOCATION.LOCATION_NAME 
                                                    FROM `DOA_LEADS` 
                                                    INNER JOIN " . $master_database . ".DOA_LOCATION AS DOA_LOCATION 
                                                        ON DOA_LOCATION.PK_LOCATION = DOA_LEADS.PK_LOCATION 
                                                    LEFT JOIN DOA_LEAD_STATUS AS LS 
                                                        ON DOA_LEADS.PK_LEAD_STATUS = LS.PK_LEAD_STATUS 
                                                    WHERE DOA_LEADS.PK_LOCATION IN (" . $DEFAULT_LOCATION_ID . ") 
                                                        AND DOA_LEADS.ACTIVE = 0 
                                                        " . $search_condition . " 
                                                        " . $date_condition . "
                                                    ORDER BY DOA_LEADS.PK_LEADS DESC";

                                $inactive_result = $db->Execute($inactive_query);
                                ?>
                                <div class="kanban-col-wrapper">
                                    <div class="kanban-col">
                                        <div class="col-header">
                                            <div class="col-title">
                                                <span class="status-dot" style="background: #dc3545;"></span>
                                                Inactive
                                            </div>
                                            <span class="count-badge"><?= $inactive_result->RecordCount() ?> leads</span>
                                        </div>
                                        <div id="col-inactive" class="list-group" data-status-id="inactive" style="overflow-y: auto; flex: 1;">
                                            <?php if ($inactive_result && $inactive_result->RecordCount() > 0): ?>
                                                <?php while (!$inactive_result->EOF):
                                                    $lead = $inactive_result->fields;
                                                    $CUSTOMER_NAME = $lead['NAME'];
                                                    $customer = getProfileBadge($CUSTOMER_NAME);
                                                    $customer_initial = $customer['initials'];
                                                    $customer_color = $customer['color'];
                                                ?>
                                                    <div class="lead-card" data-id="<?= $lead['PK_LEADS'] ?>">
                                                        <div style="float: right;" class="card-actions">
                                                            <i class="bi bi-trash3" onclick="ConfirmDelete(<?= $lead['PK_LEADS'] ?>);" style="color: red; cursor: pointer;"></i>
                                                        </div>
                                                        <div class="d-flex align-items-center mb-2">
                                                            <div><span class="avatarname" style="color: #fff; background-color: <?= $customer_color ?>;"><?= $customer_initial; ?></span></div>
                                                            <div>
                                                                <p class="lead-name" onclick="editpage(<?= $lead['PK_LEADS'] ?>);"><?= htmlspecialchars($lead['NAME']) ?></p>
                                                                <p class="lead-email mb-0"><?= htmlspecialchars($lead['EMAIL_ID']) ?></p>
                                                            </div>
                                                        </div>
                                                        <div class="lead-footer d-flex justify-content-between">
                                                            <span><?= htmlspecialchars($lead['LOCATION_NAME']) ?></span>
                                                            <span><?= htmlspecialchars($lead['OPPORTUNITY_SOURCE'] ?: '—') ?></span>
                                                        </div>
                                                        <div class="lead-icons">
                                                            <div class="icon-with-pill">
                                                                <i class="bi bi-telephone-fill toggle-pill" data-target="pill-phone-<?= $lead['PK_LEADS'] ?>"></i>
                                                                <span class="pill pill-phone-<?= $lead['PK_LEADS'] ?>"><?= htmlspecialchars($lead['PHONE']) ?></span>
                                                            </div>
                                                            <div class="icon-with-pill">
                                                                <i class="bi bi-envelope-fill toggle-pill" data-target="pill-email-<?= $lead['PK_LEADS'] ?>"></i>
                                                                <span class="pill pill-email-<?= $lead['PK_LEADS'] ?>"><?= htmlspecialchars($lead['EMAIL_ID']) ?></span>
                                                            </div>
                                                            <div class="icon-with-pill">
                                                                <i class="bi bi-chat-dots-fill toggle-pill" data-target="pill-chat-<?= $lead['PK_LEADS'] ?>"></i>
                                                                <span class="pill pill-chat-<?= $lead['PK_LEADS'] ?>"><?= truncateText($lead['DESCRIPTION'], 30) ?></span>
                                                            </div>
                                                            <div class="icon-with-pill">
                                                                <i class="bi bi-calendar-fill toggle-pill" data-target="pill-calendar-<?= $lead['PK_LEADS'] ?>"></i>
                                                                <span class="pill pill-calendar-<?= $lead['PK_LEADS'] ?>"><?= date('m/d/Y - h:iA', strtotime($lead['CREATED_ON'])) ?></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php
                                                    $inactive_result->MoveNext();
                                                endwhile; ?>
                                            <?php else: ?>
                                                <div class="text-center text-muted py-3">No inactive leads</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

    <script>
        $(document).ready(function() {
            $('.datepicker-normal').datepicker({
                dateFormat: 'mm/dd/yy',
            });

            // Toggle pill functionality
            $(document).on('click', '.toggle-pill', function(e) {
                e.stopPropagation();
                const target = $(this).data('target');
                const $card = $(this).closest('.lead-card');
                $card.find('.pill').not('.' + target).removeClass('show');
                $('.' + target).toggleClass('show');
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

                let searchText = $('#search_text').val() || '';
                let status = $(this).data('status') || '';
                let chooseDate = $('#CHOOSE_DATE').val() || '';

                let url = window.location.pathname + '?';
                let params = [];

                if (searchText) params.push('search_text=' + encodeURIComponent(searchText));
                if (status) params.push('status=' + encodeURIComponent(status));
                if (chooseDate) params.push('CHOOSE_DATE=' + encodeURIComponent(chooseDate));

                url += params.join('&');
                window.location.href = url;
            });

            // Date filter
            $('#CHOOSE_DATE').on('change', function() {
                submitFilters();
            });

            // Initialize Sortable with better configuration
            initializeSortable();
        });

        // Store sortable instances for re-initialization if needed
        let sortableInstances = [];

        function initializeSortable() {
            // Destroy existing sortable instances if any
            if (sortableInstances.length > 0) {
                sortableInstances.forEach(function(instance) {
                    if (instance && instance.destroy) {
                        instance.destroy();
                    }
                });
                sortableInstances = [];
            }

            // Initialize sortable for each column
            $('.kanban-col .list-group').each(function() {
                const columnId = $(this).attr('id');
                const $el = document.getElementById(columnId);

                if (columnId && $el) {
                    const sortable = new Sortable($el, {
                        group: {
                            name: 'kanban',
                            pull: true, // Allow dragging from other columns
                            revertClone: false,
                            put: true // Allow dropping into this column
                        },
                        animation: 200,
                        ghostClass: 'sortable-ghost',
                        chosenClass: 'sortable-chosen',
                        dragClass: 'sortable-drag',
                        handle: '.lead-card', // The element that triggers dragging
                        delay: 0, // No delay
                        touchStartThreshold: 2,
                        onEnd: function(evt) {
                            const item = evt.item;
                            const itemId = item.getAttribute('data-id');
                            const newStatusId = evt.to.getAttribute('data-status-id');
                            const oldStatusId = evt.from.getAttribute('data-status-id');
                            const oldContainerId = evt.from.id;
                            const newContainerId = evt.to.id;

                            // Check if status actually changed
                            if (newStatusId && oldStatusId && newStatusId !== oldStatusId) {
                                // Show loading indicator
                                Swal.fire({
                                    title: 'Updating status...',
                                    text: 'Moving lead to new column',
                                    allowOutsideClick: false,
                                    didOpen: () => {
                                        Swal.showLoading();
                                    }
                                });

                                // Send AJAX request to update status
                                $.ajax({
                                    url: "ajax/AjaxFunctions.php",
                                    type: 'POST',
                                    data: {
                                        FUNCTION_NAME: 'updateLeadStatus',
                                        PK_LEADS: itemId,
                                        STATUS_ID: newStatusId
                                    },
                                    dataType: 'json',
                                    success: function(response) {
                                        Swal.close();

                                        // Update column counts
                                        updateColumnCounts();

                                        // Show success message
                                        Swal.fire({
                                            title: 'Success!',
                                            text: 'Lead status updated successfully',
                                            icon: 'success',
                                            timer: 1500,
                                            showConfirmButton: false
                                        });

                                        // Optional: Update the status badge on the card
                                        updateCardStatusBadge(item, newStatusId);
                                    },
                                    error: function(xhr, status, error) {
                                        console.error('AJAX Error:', error);
                                        console.error('Response:', xhr.responseText);

                                        Swal.close();
                                        Swal.fire({
                                            title: 'Error!',
                                            text: 'Could not update lead status. Please try again.',
                                            icon: 'error',
                                            confirmButtonText: 'OK'
                                        }).then(() => {
                                            // Reload to revert the drag and reset state
                                            window.location.reload();
                                        });
                                    }
                                });
                            } else if (newStatusId === oldStatusId) {
                                // Same column - just reorder within same column
                                // You can save order preference here if needed
                                console.log('Reordered within same column');
                            }
                        }
                    });

                    sortableInstances.push(sortable);
                }
            });
        }

        function updateColumnCounts() {
            $('.kanban-col').each(function() {
                const $column = $(this);
                const $leadsList = $column.find('.list-group');
                const leadCount = $leadsList.find('.lead-card').length;
                $column.find('.count-badge').text(leadCount + ' leads');
            });
        }

        function updateCardStatusBadge(card, newStatusId) {
            // Optional: Update the status badge on the card
            // This would require knowledge of status names, so you might skip this
            // or implement an AJAX call to get the status name
            console.log('Card moved to status ID:', newStatusId);
        }

        function submitFilters() {
            let searchText = $('#search_text').val() || '';
            let status = $('.status-filter-btn.active').data('status') || '';
            let chooseDate = $('#CHOOSE_DATE').val() || '';

            let url = window.location.pathname + '?';
            let params = [];

            if (searchText) params.push('search_text=' + encodeURIComponent(searchText));
            if (status) params.push('status=' + encodeURIComponent(status));
            if (chooseDate) params.push('CHOOSE_DATE=' + encodeURIComponent(chooseDate));

            url += params.join('&');
            window.location.href = url;
        }

        function resetFilters() {
            window.location.href = window.location.pathname;
        }

        function editpage(id, date) {
            window.location.href = "leads.php?id=" + id + (date ? "&date=" + date : "");
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