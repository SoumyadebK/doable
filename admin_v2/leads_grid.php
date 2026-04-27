<?php
require_once('../global/config.php');
$title = "All Leads";

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];

if (isset($_GET['CHOOSE_DATE']) && $_GET['CHOOSE_DATE'] != '') {
    $CHOOSE_DATE = " AND DATE = '" . date('Y-m-d', strtotime($_GET['CHOOSE_DATE'])) . "'";
} else {
    $CHOOSE_DATE = "";
}

$status_check = empty($_GET['status']) ? '' : $_GET['status'];

$status_condition = ' ';
if ($status_check != '') {
    $status_condition = " AND DOA_LEADS.PK_LEAD_STATUS = " . $status_check;
}

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '') {
    header("location:../login.php");
    exit;
}

$results_per_page = 100;

if (isset($_GET['search_text'])) {
    $search_text = $_GET['search_text'];
    $search = " AND (DOA_LEADS.FIRST_NAME LIKE '%" . $search_text . "%' OR DOA_LEADS.LAST_NAME LIKE '%" . $search_text . "%' OR DOA_LEADS.PHONE LIKE '%" . $search_text . "%' OR DOA_LEADS.EMAIL_ID LIKE '%" . $search_text . "%' OR LS.LEAD_STATUS LIKE '%" . $search_text . "%')";
} else {
    $search_text = '';
    $search = ' ';
}

$query = $db->Execute("SELECT count(DOA_LEADS.PK_LEADS) AS TOTAL_RECORDS FROM DOA_LEADS");
$number_of_result =  $query->fields['TOTAL_RECORDS'];
$number_of_page = ceil($number_of_result / $results_per_page);

if (!isset($_GET['page'])) {
    $page = 1;
} else {
    $page = $_GET['page'];
}

$page_first_result = ($page - 1) * $results_per_page;

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
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'layout/header_script.php'; ?>
<?php require_once('../includes/header.php'); ?>
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

        /* Header Controls */
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
            font-size: 0.9rem;
        }

        /* Kanban Columns */
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
            font-size: 0.9rem;
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

        /* Lead Cards */
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
            color: #00B739;
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

        /* Status Colors */
        .bg-new {
            background-color: #6c757d;
        }

        .bg-enrolled {
            background-color: #198754;
        }

        .bg-not-enrolled {
            background-color: #fd7e14;
        }

        .bg-inactive {
            background-color: #dc3545;
        }

        .icon-circle {
            width: 45px;
            height: 45px;
            text-align: center;
            border-radius: 25px;
        }

        .text-green {
            color: green;
        }

        .btn-success {
            background-color: #00B739;
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
            color: #00B739;
            font-size: 14px;
            cursor: pointer;
        }

        .pill {
            background-color: #eefdf0ff;
            color: #00B739;
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

        .fa-star {
            color: gold;
        }

        .fa-check-square {
            color: #00B739;
        }

        .fa-trash {
            color: red;
            cursor: pointer;
        }

        .card-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        /* Kanban Board Container - Horizontal Scroll */
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

        /* Kanban Columns - Fixed width, no wrapping */
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

        /* Scrollable body inside each column */
        .kanban-col .list-group {
            overflow-y: auto;
            flex: 1;
            padding-right: 5px;
        }

        /* Custom scrollbar styling */
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
                                <p class="text-muted small mb-0">Optionally describe this</p>
                            </div>
                        </div>
                        <button class="btn btn-success border-0 rounded-pill px-3" onclick="window.location.href='leads.php'"><i class="bi bi-plus-lg me-1"></i> Create New Lead</button>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="search-container">
                            <i class="fa fa-search"></i>
                            <input type="text" id="search_text" class="form-control" placeholder="Search..." value="<?= htmlspecialchars($search_text) ?>">
                        </div>

                        <div class="d-flex gap-2 align-items-center">
                            <div class="bg-white border rounded-pill p-1">
                                <select id="status_filter" class="form-select form-select-sm rounded-pill border-0 bg-transparent" style="width: auto;">
                                    <option value="">All Status</option>
                                    <?php
                                    $all_status = $db->Execute("SELECT * FROM `DOA_LEAD_STATUS` WHERE ACTIVE = 1 AND `PK_ACCOUNT_MASTER` = " . $_SESSION['PK_ACCOUNT_MASTER'] . " ORDER BY DISPLAY_ORDER ASC");
                                    while (!$all_status->EOF) { ?>
                                        <option value="<?php echo $all_status->fields['PK_LEAD_STATUS']; ?>" <?= ($all_status->fields['PK_LEAD_STATUS'] == $status_check) ? 'selected' : '' ?>><?= $all_status->fields['LEAD_STATUS'] ?></option>
                                    <?php $all_status->MoveNext();
                                    } ?>
                                </select>
                            </div>
                            <div class="btn-group ms-2 border rounded-pill p-1" style="border-radius: 20px !important;">
                                <button class="toolbar-btn me-1 border-0 rounded-pill" id="kanban_view_btn" style="background: #00B739; color: white;">
                                    <i class="bi bi-grid-3x3-gap-fill"></i>
                                </button>
                                <button class="toolbar-btn border-0 rounded-pill" id="list_view_btn" style="background: transparent; color: #6c757d;" onclick="window.location.href='leads_list.php'">
                                    <i class="bi bi-list-ul"></i>
                                </button>
                            </div>
                            <div class="position-relative">
                                <input type="text" id="CHOOSE_DATE" class="form-control datepicker-normal" placeholder="Filter by Follow up Date" value="<?= ($_GET['CHOOSE_DATE']) ?? '' ?>" style="border-radius: 20px; width: 180px;">
                            </div>
                            <button class="toolbar-btn rounded-pill"><i class="bi bi-filter me-1"></i> Filter</button>
                            <button class="toolbar-btn rounded-pill"><i class="bi bi-arrow-down-up me-1"></i> Sort by <i class="bi bi-chevron-down ms-1"></i></button>
                        </div>
                    </div>

                    <div class="kanban-board-wrapper">
                        <div class="kanban-board-container" id="kanban_container">
                            <?php
                            $leads_status = $db->Execute("SELECT * FROM `DOA_LEAD_STATUS` WHERE ACTIVE = 1 AND (`PK_ACCOUNT_MASTER` = " . $_SESSION['PK_ACCOUNT_MASTER'] . ") ORDER BY DISPLAY_ORDER ASC");

                            while (!$leads_status->EOF) {
                                // Get leads with their latest follow-up date only
                                $leds_user = $db->Execute(
                                    "
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
                WHERE DOA_LEADS.PK_LEAD_STATUS = " . $leads_status->fields['PK_LEAD_STATUS'] . " 
                    AND DOA_LEADS.PK_LOCATION IN (" . $DEFAULT_LOCATION_ID . ") 
                    AND DOA_LEADS.ACTIVE = 1" . $search
                                );

                                // If date filter is applied, we need to filter the results
                                $filtered_leads = array();
                                if (!empty($_GET['CHOOSE_DATE'])) {
                                    $selected_date = date('Y-m-d', strtotime($_GET['CHOOSE_DATE']));
                                    while (!$leds_user->EOF) {
                                        if ($leds_user->fields['LATEST_DATE'] == $selected_date) {
                                            $filtered_leads[] = $leds_user->fields;
                                        }
                                        $leds_user->MoveNext();
                                    }
                                    $lead_count = count($filtered_leads);
                                } else {
                                    $lead_count = $leds_user->RecordCount();
                                }

                                // Map status to background color
                                $status_bg_color = '';
                                switch (strtolower($leads_status->fields['LEAD_STATUS'])) {
                                    case 'new':
                                        $status_bg_color = '#6c757d';
                                        break;
                                    case 'enrolled':
                                        $status_bg_color = '#198754';
                                        break;
                                    case 'not enrolled':
                                        $status_bg_color = '#fd7e14';
                                        break;
                                    default:
                                        $status_bg_color = $leads_status->fields['STATUS_COLOR'] ?: '#6c757d';
                                }
                            ?>
                                <div class="kanban-col-wrapper">
                                    <div class="kanban-col">
                                        <div class="col-header">
                                            <div class="col-title">
                                                <span class="status-dot" style="background: <?= $status_bg_color ?>;"></span>
                                                <?= htmlspecialchars($leads_status->fields['LEAD_STATUS']) ?>
                                            </div>
                                            <span class="count-badge"><?= $lead_count ?> leads</span>
                                        </div>
                                        <div id="col-<?= preg_replace('/[^a-z0-9]/i', '-', strtolower($leads_status->fields['LEAD_STATUS'])) ?>" class="list-group" data-status-id="<?= $leads_status->fields['PK_LEAD_STATUS'] ?>" style="overflow-y: auto; flex: 1;">
                                            <?php
                                            if (!empty($_GET['CHOOSE_DATE'])) {
                                                foreach ($filtered_leads as $lead) {
                                                    $initials = strtoupper(substr($lead['FIRST_NAME'], 0, 1) . substr($lead['LAST_NAME'], 0, 1));
                                            ?>
                                                    <div class="lead-card" data-id="<?= $lead['PK_LEADS'] ?>">
                                                        <div style="float: right;" class="card-actions">
                                                            <?php if ($lead['IS_APPOINTMENT_CREATED']) { ?>
                                                                <i class="bi bi-star-fill" style="color: gold;"></i>
                                                            <?php } ?>
                                                            <?php if ($lead['IS_CALLED']) { ?>
                                                                <i class="bi bi-check-square-fill" style="color: #00B739;"></i>
                                                            <?php } ?>
                                                            <i class="bi bi-trash3" onclick="ConfirmDelete(<?= $lead['PK_LEADS'] ?>);" style="color: red; cursor: pointer;"></i>
                                                        </div>
                                                        <div class="d-flex align-items-center mb-2">
                                                            <div class="avatar-lp"><?= $initials ?: 'LD' ?></div>
                                                            <div>
                                                                <p class="lead-name" onclick="editpage(<?= $lead['PK_LEADS'] ?>, '<?= $lead['LATEST_DATE'] ?? '' ?>');"><?= htmlspecialchars($lead['NAME']) ?></p>
                                                                <p class="lead-email mb-0"><?= htmlspecialchars($lead['EMAIL_ID']) ?></p>
                                                            </div>
                                                        </div>
                                                        <div class="lead-footer d-flex justify-content-between">
                                                            <span><?= htmlspecialchars($lead['LOCATION_NAME']) ?></span>
                                                            <span><?= htmlspecialchars($lead['OPPORTUNITY_SOURCE']) ?></span>
                                                        </div>
                                                        <div class="lead-footer">
                                                            <strong>Follow up:</strong>
                                                            <?php
                                                            if (!empty($lead['LATEST_DATE']) && $lead['LATEST_DATE'] != '0000-00-00') {
                                                                echo date('m/d/Y', strtotime($lead['LATEST_DATE']));
                                                            } else {
                                                                echo 'N/A';
                                                            }
                                                            ?>
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
                                                                <span class="pill pill-chat-<?= $lead['PK_LEADS'] ?>"><?= htmlspecialchars(substr($lead['DESCRIPTION'], 0, 30)) ?>...</span>
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
                                                }
                                            } else {
                                                while (!$leds_user->EOF) {
                                                    $initials = strtoupper(substr($leds_user->fields['FIRST_NAME'], 0, 1) . substr($leds_user->fields['LAST_NAME'], 0, 1));
                                                ?>
                                                    <div class="lead-card" data-id="<?= $leds_user->fields['PK_LEADS'] ?>">
                                                        <div style="float: right;" class="card-actions">
                                                            <?php if ($leds_user->fields['IS_APPOINTMENT_CREATED']) { ?>
                                                                <i class="bi bi-star-fill" style="color: gold;"></i>
                                                            <?php } ?>
                                                            <?php if ($leds_user->fields['IS_CALLED']) { ?>
                                                                <i class="bi bi-check-square-fill" style="color: #00B739;"></i>
                                                            <?php } ?>
                                                            <i class="bi bi-trash3" onclick="ConfirmDelete(<?= $leds_user->fields['PK_LEADS'] ?>);" style="color: red; cursor: pointer;"></i>
                                                        </div>
                                                        <div class="d-flex align-items-center mb-2">
                                                            <div class="avatar-lp"><?= $initials ?: 'LD' ?></div>
                                                            <div>
                                                                <p class="lead-name" onclick="editpage(<?= $leds_user->fields['PK_LEADS'] ?>, '<?= $leds_user->fields['LATEST_DATE'] ?? '' ?>');"><?= htmlspecialchars($leds_user->fields['NAME']) ?></p>
                                                                <p class="lead-email mb-0"><?= htmlspecialchars($leds_user->fields['EMAIL_ID']) ?></p>
                                                            </div>
                                                        </div>
                                                        <div class="lead-footer d-flex justify-content-between">
                                                            <span><?= htmlspecialchars($leds_user->fields['LOCATION_NAME']) ?></span>
                                                            <span><?= htmlspecialchars($leds_user->fields['OPPORTUNITY_SOURCE']) ?></span>
                                                        </div>
                                                        <div class="lead-footer">
                                                            <strong>Follow up:</strong>
                                                            <?php
                                                            if (!empty($leds_user->fields['LATEST_DATE']) && $leds_user->fields['LATEST_DATE'] != '0000-00-00') {
                                                                echo date('m/d/Y', strtotime($leds_user->fields['LATEST_DATE']));
                                                            } else {
                                                                echo 'N/A';
                                                            }
                                                            ?>
                                                        </div>
                                                        <div class="lead-icons">
                                                            <div class="icon-with-pill">
                                                                <i class="bi bi-telephone-fill toggle-pill" data-target="pill-phone-<?= $leds_user->fields['PK_LEADS'] ?>"></i>
                                                                <span class="pill pill-phone-<?= $leds_user->fields['PK_LEADS'] ?>"><?= htmlspecialchars($leds_user->fields['PHONE']) ?></span>
                                                            </div>
                                                            <div class="icon-with-pill">
                                                                <i class="bi bi-envelope-fill toggle-pill" data-target="pill-email-<?= $leds_user->fields['PK_LEADS'] ?>"></i>
                                                                <span class="pill pill-email-<?= $leds_user->fields['PK_LEADS'] ?>"><?= htmlspecialchars($leds_user->fields['EMAIL_ID']) ?></span>
                                                            </div>
                                                            <div class="icon-with-pill">
                                                                <i class="bi bi-chat-dots-fill toggle-pill" data-target="pill-chat-<?= $leds_user->fields['PK_LEADS'] ?>"></i>
                                                                <span class="pill pill-chat-<?= $leds_user->fields['PK_LEADS'] ?>"><?= htmlspecialchars(substr($leds_user->fields['DESCRIPTION'], 0, 30)) ?>...</span>
                                                            </div>
                                                            <div class="icon-with-pill">
                                                                <i class="bi bi-calendar-fill toggle-pill" data-target="pill-calendar-<?= $leds_user->fields['PK_LEADS'] ?>"></i>
                                                                <span class="pill pill-calendar-<?= $leds_user->fields['PK_LEADS'] ?>"><?= date('m/d/Y - h:iA', strtotime($leds_user->fields['CREATED_ON'])) ?></span>
                                                            </div>
                                                            <div class="icon-with-pill" style="font-size: 18px;">
                                                                <i class="bi bi-telephone-plus-fill" onclick="callToLeads(<?= $leds_user->fields['PK_LEADS'] ?>)" style="cursor: pointer;" title="AI Call"></i>
                                                            </div>
                                                        </div>
                                                    </div>
                                            <?php
                                                    $leds_user->MoveNext();
                                                }
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            <?php $leads_status->MoveNext();
                            }

                            // Inactive leads query
                            $leds_user = $db->Execute(
                                "
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
                AND DOA_LEADS.ACTIVE = 0" . $search
                            ); ?>
                            <div class="kanban-col-wrapper">
                                <div class="kanban-col">
                                    <div class="col-header">
                                        <div class="col-title">
                                            <span class="status-dot" style="background: #dc3545;"></span>
                                            Inactive
                                        </div>
                                        <span class="count-badge"><?= $leds_user->RecordCount() ?> leads</span>
                                    </div>
                                    <div id="col-inactive" class="list-group" data-status-id="inactive" style="overflow-y: auto; flex: 1;">
                                        <?php while (!$leds_user->EOF) {
                                            $initials = strtoupper(substr($leds_user->fields['FIRST_NAME'], 0, 1) . substr($leds_user->fields['LAST_NAME'], 0, 1));
                                        ?>
                                            <div class="lead-card" data-id="<?= $leds_user->fields['PK_LEADS'] ?>">
                                                <div style="float: right;" class="card-actions">
                                                    <i class="bi bi-trash3" onclick="ConfirmDelete(<?= $leds_user->fields['PK_LEADS'] ?>);" style="color: red; cursor: pointer;"></i>
                                                </div>
                                                <div class="d-flex align-items-center mb-2">
                                                    <div class="avatar-lp"><?= $initials ?: 'LD' ?></div>
                                                    <div>
                                                        <p class="lead-name" onclick="editpage(<?= $leds_user->fields['PK_LEADS'] ?>);"><?= htmlspecialchars($leds_user->fields['NAME']) ?></p>
                                                        <p class="lead-email mb-0"><?= htmlspecialchars($leds_user->fields['EMAIL_ID']) ?></p>
                                                    </div>
                                                </div>
                                                <div class="lead-footer d-flex justify-content-between">
                                                    <span><?= htmlspecialchars($leds_user->fields['LOCATION_NAME']) ?></span>
                                                    <span><?= htmlspecialchars($leds_user->fields['OPPORTUNITY_SOURCE']) ?></span>
                                                </div>
                                                <div class="lead-icons">
                                                    <div class="icon-with-pill">
                                                        <i class="bi bi-telephone-fill toggle-pill" data-target="pill-phone-<?= $leds_user->fields['PK_LEADS'] ?>"></i>
                                                        <span class="pill pill-phone-<?= $leds_user->fields['PK_LEADS'] ?>"><?= htmlspecialchars($leds_user->fields['PHONE']) ?></span>
                                                    </div>
                                                    <div class="icon-with-pill">
                                                        <i class="bi bi-envelope-fill toggle-pill" data-target="pill-email-<?= $leds_user->fields['PK_LEADS'] ?>"></i>
                                                        <span class="pill pill-email-<?= $leds_user->fields['PK_LEADS'] ?>"><?= htmlspecialchars($leds_user->fields['EMAIL_ID']) ?></span>
                                                    </div>
                                                    <div class="icon-with-pill">
                                                        <i class="bi bi-chat-dots-fill toggle-pill" data-target="pill-chat-<?= $leds_user->fields['PK_LEADS'] ?>"></i>
                                                        <span class="pill pill-chat-<?= $leds_user->fields['PK_LEADS'] ?>"><?= htmlspecialchars(substr($leds_user->fields['DESCRIPTION'], 0, 30)) ?>...</span>
                                                    </div>
                                                    <div class="icon-with-pill">
                                                        <i class="bi bi-calendar-fill toggle-pill" data-target="pill-calendar-<?= $leds_user->fields['PK_LEADS'] ?>"></i>
                                                        <span class="pill pill-calendar-<?= $leds_user->fields['PK_LEADS'] ?>"><?= date('m/d/Y - h:iA', strtotime($leds_user->fields['CREATED_ON'])) ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php $leds_user->MoveNext();
                                        } ?>
                                    </div>
                                </div>
                            </div>
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

    <script>
        $(document).ready(function() {
            $('.datepicker-normal').datepicker({
                format: 'mm/dd/yyyy',
            });

            // Toggle pill functionality
            $(document).on('click', '.toggle-pill', function(e) {
                e.stopPropagation();
                const target = $(this).data('target');
                const $card = $(this).closest('.lead-card');
                $card.find('.pill').not('.' + target).removeClass('show');
                $('.' + target).toggleClass('show');
            });

            // Search functionality
            let searchTimeout;
            $('#search_text').on('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    submitFilters();
                }, 500);
            });

            // Status filter
            $('#status_filter').on('change', function() {
                submitFilters();
            });

            // Date filter
            $('#CHOOSE_DATE').on('change', function() {
                submitFilters();
            });

            // Initialize Sortable for each column
            initializeSortable();
        });

        function initializeSortable() {
            $('.kanban-col .list-group').each(function() {
                const columnId = $(this).attr('id');
                if (columnId && document.getElementById(columnId)) {
                    new Sortable(document.getElementById(columnId), {
                        group: {
                            name: 'kanban',
                            pull: true,
                            revertClone: false
                        },
                        animation: 150,
                        ghostClass: 'sortable-ghost',
                        chosenClass: 'sortable-chosen',
                        onEnd: function(evt) {
                            const itemId = evt.item.getAttribute('data-id');
                            const newStatusId = evt.to.getAttribute('data-status-id');
                            const oldStatusId = evt.from.getAttribute('data-status-id');

                            if (newStatusId && oldStatusId && newStatusId !== oldStatusId) {
                                // Show loading indicator
                                Swal.fire({
                                    title: 'Updating...',
                                    text: 'Moving lead to new status',
                                    allowOutsideClick: false,
                                    didOpen: () => {
                                        Swal.showLoading();
                                    }
                                });

                                $.ajax({
                                    url: "ajax/AjaxFunctions.php",
                                    type: 'POST',
                                    data: {
                                        FUNCTION_NAME: 'updateLeadStatus',
                                        PK_LEADS: itemId,
                                        STATUS_ID: newStatusId
                                    },
                                    success: function(response) {
                                        Swal.close();
                                        // Update the count badges
                                        updateColumnCounts();

                                        // Show success message
                                        Swal.fire({
                                            title: 'Success!',
                                            text: 'Lead status updated successfully',
                                            icon: 'success',
                                            timer: 1500,
                                            showConfirmButton: false
                                        });
                                    },
                                    error: function(xhr, status, error) {
                                        Swal.close();
                                        Swal.fire({
                                            title: 'Error!',
                                            text: 'Could not update lead status. Please try again.',
                                            icon: 'error',
                                            confirmButtonText: 'OK'
                                        }).then(() => {
                                            // Reload to revert the drag
                                            window.location.reload();
                                        });
                                    }
                                });
                            }
                        }
                    });
                }
            });
        }

        // Function to update column counts after moving
        function updateColumnCounts() {
            $('.kanban-col').each(function() {
                const $column = $(this);
                const $leadsList = $column.find('.list-group');
                const leadCount = $leadsList.find('.lead-card').length;
                $column.find('.count-badge').text(leadCount + ' leads');
            });
        }

        function submitFilters() {
            const searchText = $('#search_text').val();
            const status = $('#status_filter').val();
            const chooseDate = $('#CHOOSE_DATE').val();

            let url = window.location.pathname + '?';
            if (searchText) url += 'search_text=' + encodeURIComponent(searchText) + '&';
            if (status) url += 'status=' + encodeURIComponent(status) + '&';
            if (chooseDate) url += 'CHOOSE_DATE=' + encodeURIComponent(chooseDate) + '&';

            window.location.href = url;
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
                    $.ajax({
                        url: "ajax/AjaxFunctions.php",
                        type: 'POST',
                        data: {
                            FUNCTION_NAME: 'deleteLeads',
                            PK_LEADS: PK_LEADS
                        },
                        success: function(data) {
                            window.location.href = 'all_leads.php';
                        }
                    });
                }
            });
        }

        function callToLeads(PK_LEADS) {
            $.ajax({
                url: "../voice_agent/outbound_call.php",
                type: 'GET',
                data: {
                    PK_LEADS: PK_LEADS
                },
                success: function(response) {
                    if (response === 'success') {
                        Swal.fire(
                            'Call Initiated!',
                            'The call to the lead has been initiated successfully.',
                            'success'
                        );
                    } else {
                        Swal.fire(
                            'Error!',
                            response,
                            'error'
                        );
                    }
                },
                error: function() {
                    Swal.fire(
                        'Error!',
                        'There was an error initiating the call. Please try again.',
                        'error'
                    );
                }
            });
        }
    </script>
</body>

</html>