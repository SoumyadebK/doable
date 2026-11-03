<?php
require_once('../global/config.php');
$title = "Leads Report";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

// Initialize filter variables
$date_filter   = isset($_GET['date_filter'])   ? $_GET['date_filter']   : '';
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : '';
$lead_filter   = isset($_GET['lead_filter'])   ? $_GET['lead_filter']   : '';
$source_filter = isset($_GET['source_filter']) ? $_GET['source_filter'] : '';
$start_date    = isset($_GET['start_date'])    ? $_GET['start_date']    : '';
$end_date      = isset($_GET['end_date'])      ? $_GET['end_date']      : '';

// Pagination variables
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 50;
$offset = ($page - 1) * $records_per_page;

// ---------------------------------------------------------------
// Safe multi-location IN (...) — DEFAULT_LOCATION_ID can be "3,5,7"
// ---------------------------------------------------------------
$allowed_location_ids = array_filter(
    array_map('intval', explode(',', (string)$_SESSION['DEFAULT_LOCATION_ID'])),
    function ($id) {
        return $id > 0;
    }
);
$location_condition = empty($allowed_location_ids)
    ? "(1 = 0)"
    : "DOA_LEADS.PK_LOCATION IN (" . implode(',', $allowed_location_ids) . ")";

// Build WHERE conditions
$where_conditions = [];
$where_conditions[] = $location_condition;

// Add active status filter
if ($status_filter !== '' && $status_filter !== 'all') {
    $where_conditions[] = "DOA_LEADS.ACTIVE = " . intval($status_filter);
}

// Add lead status filter
if ($lead_filter !== '' && $lead_filter !== 'all') {
    $where_conditions[] = "DOA_LEADS.PK_LEAD_STATUS = " . intval($lead_filter);
}

// Add opportunity source filter
if ($source_filter !== '' && $source_filter !== 'all') {
    $where_conditions[] = "DOA_LEADS.OPPORTUNITY_SOURCE = '" . mysqli_real_escape_string($db->LinkID, $source_filter) . "'";
}

// Add date filter
if ($date_filter == 'range' && $start_date && $end_date) {
    $where_conditions[] = "DATE(DOA_LEADS.CREATED_ON) BETWEEN '" . mysqli_real_escape_string($db->LinkID, $start_date) . "' AND '" . mysqli_real_escape_string($db->LinkID, $end_date) . "'";
} elseif ($date_filter == 'today') {
    $where_conditions[] = "DATE(DOA_LEADS.CREATED_ON) = CURDATE()";
} elseif ($date_filter == 'week') {
    $where_conditions[] = "YEARWEEK(DOA_LEADS.CREATED_ON) = YEARWEEK(CURDATE())";
} elseif ($date_filter == 'month') {
    $where_conditions[] = "MONTH(DOA_LEADS.CREATED_ON) = MONTH(CURDATE()) AND YEAR(DOA_LEADS.CREATED_ON) = YEAR(CURDATE())";
} elseif ($date_filter == 'year') {
    $where_conditions[] = "YEAR(DOA_LEADS.CREATED_ON) = YEAR(CURDATE())";
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

// Get total records count for pagination
$count_query = "SELECT COUNT(*) as total 
    FROM DOA_LEADS 
    $where_clause";

$count_result = $db->Execute($count_query);
if ($count_result) {
    $total_records = (int)$count_result->fields['total'];
} else {
    $total_records = 0;
}
$total_pages = ($records_per_page > 0) ? ceil($total_records / $records_per_page) : 1;

// Get records for current page with LIMIT
$query = "SELECT 
        DOA_LEADS.PK_LEADS,
        DOA_LEADS.FIRST_NAME,
        DOA_LEADS.LAST_NAME,
        DOA_LEADS.PHONE,
        DOA_LEADS.EMAIL_ID,
        DOA_LEADS.PK_LEAD_STATUS,
        DOA_LEAD_STATUS.LEAD_STATUS,
        DOA_LEADS.DESCRIPTION,
        DOA_LEADS.OPPORTUNITY_SOURCE,
        DOA_LEADS.ACTIVE,
        DOA_LEADS.IS_CALLED,
        DOA_LEADS.IS_APPOINTMENT_CREATED,
        DOA_LEADS.CREATED_ON,
        DOA_LOCATION.LOCATION_NAME
    FROM DOA_LEADS AS DOA_LEADS
    LEFT JOIN DOA_LEAD_STATUS ON DOA_LEAD_STATUS.PK_LEAD_STATUS = DOA_LEADS.PK_LEAD_STATUS
    LEFT JOIN DOA_LOCATION AS DOA_LOCATION ON DOA_LOCATION.PK_LOCATION = DOA_LEADS.PK_LOCATION
    $where_clause 
    ORDER BY DOA_LEADS.CREATED_ON DESC, DOA_LEADS.PK_LEADS DESC
    LIMIT $offset, $records_per_page";

$result = $db->Execute($query);

// Get lead statuses for dropdown
$lead_statuses = [];
$lead_status_query = $db->Execute("SELECT PK_LEAD_STATUS, LEAD_STATUS 
                                   FROM DOA_LEAD_STATUS 
                                   WHERE PK_ACCOUNT_MASTER = " . intval($_SESSION['PK_ACCOUNT_MASTER']) . " 
                                   AND ACTIVE = 1 
                                   ORDER BY LEAD_STATUS");
if ($lead_status_query) {
    while (!$lead_status_query->EOF) {
        $lead_statuses[] = [
            'PK_LEAD_STATUS' => $lead_status_query->fields['PK_LEAD_STATUS'],
            'LEAD_STATUS'    => $lead_status_query->fields['LEAD_STATUS']
        ];
        $lead_status_query->MoveNext();
    }
}

// Get distinct opportunity sources for dropdown
$sources = [];
$sources_query = $db->Execute("SELECT DISTINCT OPPORTUNITY_SOURCE 
                               FROM DOA_LEADS 
                               WHERE OPPORTUNITY_SOURCE IS NOT NULL 
                                 AND OPPORTUNITY_SOURCE <> '' 
                                 AND PK_LOCATION IN (" . (empty($allowed_location_ids) ? "0" : implode(',', $allowed_location_ids)) . ")
                               ORDER BY OPPORTUNITY_SOURCE");
if ($sources_query) {
    while (!$sources_query->EOF) {
        $sources[] = $sources_query->fields['OPPORTUNITY_SOURCE'];
        $sources_query->MoveNext();
    }
}

// Build query string for pagination links
$query_params = [];
if ($date_filter) $query_params['date_filter'] = $date_filter;
if ($status_filter && $status_filter != 'all') $query_params['status_filter'] = $status_filter;
if ($lead_filter && $lead_filter != 'all') $query_params['lead_filter'] = $lead_filter;
if ($source_filter && $source_filter != 'all') $query_params['source_filter'] = $source_filter;
if ($start_date) $query_params['start_date'] = $start_date;
if ($end_date) $query_params['end_date'] = $end_date;
$query_string = http_build_query($query_params);

// Counters for the summary strip
$called_count       = 0;
$appointment_count  = 0;
$summary_result = $db->Execute("SELECT 
        SUM(CASE WHEN IS_CALLED = 1 THEN 1 ELSE 0 END) AS called_count,
        SUM(CASE WHEN IS_APPOINTMENT_CREATED = 1 THEN 1 ELSE 0 END) AS appointment_count
    FROM DOA_LEADS AS DOA_LEADS
    $where_clause");
if ($summary_result && $summary_result->RecordCount() > 0) {
    $called_count      = (int)$summary_result->fields['called_count'];
    $appointment_count = (int)$summary_result->fields['appointment_count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<?php include 'layout/header_script.php'; ?>
<?php require_once('../includes/header.php'); ?>
<?php include 'layout/header.php'; ?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<link href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />

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

    .filter-section {
        background: #f8f9fa;
        padding: 10px;
        border-radius: 8px;
        margin-bottom: 20px;
    }

    .select2-container {
        width: 100% !important;
        height: 38px !important;
    }

    .select2-container .select2-selection--single {
        box-sizing: border-box;
        cursor: pointer;
        display: block;
        height: 38px;
        user-select: none;
        -webkit-user-select: none;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #444;
        line-height: 33px;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 35px;
        position: absolute;
        top: 1px;
        right: 1px;
        width: 20px;
    }

    .filter-section .form-control,
    .filter-section .btn,
    .filter-section .select2-selection {
        border-radius: 50rem !important;
    }

    .btn-success {
        background-color: #39b54a;
    }

    .pagination {
        margin-bottom: 0;
        justify-content: flex-end;
    }

    .pagination .page-link {
        color: #690C24;
    }

    .pagination .page-item.active .page-link {
        background-color: #690C24;
        border-color: #690C24;
        color: white;
    }

    .record-info {
        padding-top: 10px;
        color: #6c757d;
        font-size: 14px;
    }

    .lead-summary {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        margin-bottom: 12px;
    }

    .lead-summary .stat-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 14px;
        border-radius: 50px;
        font-size: 13px;
        font-weight: 500;
        background: #eef2ff;
        color: #1e40af;
        border: 1px solid #c7d2fe;
    }

    .lead-summary .stat-chip.called {
        background: #dcfce7;
        color: #15803d;
        border-color: #bbf7d0;
    }

    .lead-summary .stat-chip.appointment {
        background: #fef3c7;
        color: #92400e;
        border-color: #fcd34d;
    }

    .badge-call {
        background: #dcfce7;
        color: #15803d;
    }

    .badge-no-call {
        background: #f1f5f9;
        color: #64748b;
    }

    .badge-appt {
        background: #fef3c7;
        color: #92400e;
    }

    .badge-no-appt {
        background: #f1f5f9;
        color: #64748b;
    }

    .desc-cell {
        max-width: 240px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
</style>

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">

        <div class="page-wrapper" style="padding-top: 1px !important;">

            <?php require_once('layout/report_menu.php') ?>
            <div class="container-fluid" style="padding: 10px 20px 0 20px; margin-top: 0px;">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-4">
                                        <img src="../assets/images/background/doable_logo.png" style="margin-bottom:15px; height: 60px; width: auto;">
                                    </div>
                                    <div class="col-4">
                                        <h3 class="card-title" style="padding-top:15px; text-align: center; font-weight: bold"><?= $title ?></h3>
                                    </div>
                                    <div class="btn col-4">
                                        <form action="excel_leads_report.php" method="post" id="excel-form">
                                            <input type="hidden" name="filtered_data" id="filtered_data">
                                            <button type="submit" id="export-to-excel" name="ExportType"
                                                value="Export to Excel" class="btn btn-info">Export to Excel</button>
                                        </form>
                                    </div>
                                </div>

                                <!-- Filter Section -->
                                <div class="filter-section justify-content-center align-items-center">
                                    <form method="GET" action="" id="filter-form">
                                        <div class="row">
                                            <!-- Date Filter -->
                                            <div class="col-md-1">
                                                <label class="form-label">Date Filter</label>
                                                <select name="date_filter" id="date_filter" class="form-control" onchange="toggleDateRange()">
                                                    <option value="">All Dates</option>
                                                    <option value="today" <?= $date_filter == 'today' ? 'selected' : '' ?>>Today</option>
                                                    <option value="week" <?= $date_filter == 'week' ? 'selected' : '' ?>>This Week</option>
                                                    <option value="month" <?= $date_filter == 'month' ? 'selected' : '' ?>>This Month</option>
                                                    <option value="year" <?= $date_filter == 'year' ? 'selected' : '' ?>>This Year</option>
                                                    <option value="range" <?= $date_filter == 'range' ? 'selected' : '' ?>>Date Range</option>
                                                </select>
                                            </div>

                                            <div class="col-md-3" id="date_range_div" style="display: <?= $date_filter == 'range' ? 'block' : 'none' ?>;">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <label class="form-label">Start Date</label>
                                                        <input type="date" name="start_date" id="start_date" class="form-control" value="<?= htmlspecialchars($start_date) ?>">
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label class="form-label">End Date</label>
                                                        <input type="date" name="end_date" id="end_date" class="form-control" value="<?= htmlspecialchars($end_date) ?>">
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Status Filter -->
                                            <div class="col-md-1">
                                                <label class="form-label">Status</label>
                                                <select name="status_filter" id="status_filter" class="form-control">
                                                    <option value="all" <?= $status_filter == 'all' || $status_filter == '' ? 'selected' : '' ?>>All</option>
                                                    <option value="1" <?= $status_filter == '1' ? 'selected' : '' ?>>Active</option>
                                                    <option value="0" <?= $status_filter == '0' ? 'selected' : '' ?>>Inactive</option>
                                                </select>
                                            </div>

                                            <!-- Lead Status Filter -->
                                            <div class="col-md-2">
                                                <label class="form-label">Lead Status</label>
                                                <select name="lead_filter" id="lead_filter" class="form-control select2-lead">
                                                    <option value="all">Select Lead Status</option>
                                                    <?php foreach ($lead_statuses as $ls): ?>
                                                        <option value="<?= $ls['PK_LEAD_STATUS'] ?>" <?= $lead_filter == $ls['PK_LEAD_STATUS'] ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($ls['LEAD_STATUS']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <!-- Opportunity Source Filter -->
                                            <div class="col-md-2">
                                                <label class="form-label">Opportunity Source</label>
                                                <select name="source_filter" id="source_filter" class="form-control select2-source">
                                                    <option value="all">Select Source</option>
                                                    <?php foreach ($sources as $src): ?>
                                                        <option value="<?= htmlspecialchars($src) ?>" <?= $source_filter == $src ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($src) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="col-md-1">
                                                <label class="form-label">&nbsp;</label>
                                                <button type="submit" class="btn btn-success form-control">Apply</button>
                                            </div>

                                            <div class="col-md-1">
                                                <label class="form-label">&nbsp;</label>
                                                <a href="?reset=1" class="btn btn-secondary form-control">Reset</a>
                                            </div>
                                        </div>
                                    </form>
                                </div>

                                <!-- Quick summary strip -->
                                <?php if ($total_records > 0): ?>
                                    <div class="lead-summary">
                                        <span class="stat-chip"><i class="bi bi-person-lines-fill"></i> <?= $total_records ?> Total Leads</span>
                                        <span class="stat-chip called"><i class="bi bi-telephone-fill"></i> <?= $called_count ?> Called</span>
                                        <span class="stat-chip appointment"><i class="bi bi-calendar-check-fill"></i> <?= $appointment_count ?> Appointment Created</span>
                                    </div>
                                <?php endif; ?>

                                <div class="table-responsive">
                                    <table id="myTable" class="table table-bordered" data-page-length='50'>
                                        <thead>
                                            <tr>
                                                <th style="width:4%; text-align: center; font-weight: bold">#</th>
                                                <th style="width:10%; text-align: center; font-weight: bold">First Name</th>
                                                <th style="width:10%; text-align: center; font-weight: bold">Last Name</th>
                                                <th style="width:11%; text-align: center; font-weight: bold">Email</th>
                                                <th style="width:10%; text-align: center; font-weight: bold">Phone</th>
                                                <th style="width:10%; text-align: center; font-weight: bold">Location</th>
                                                <th style="width:10%; text-align: center; font-weight: bold">Lead Status</th>
                                                <th style="width:10%; text-align: center; font-weight: bold">Source</th>
                                                <th style="width:6%; text-align: center; font-weight: bold">Called</th>
                                                <th style="width:7%; text-align: center; font-weight: bold">Appt.</th>
                                                <th style="width:10%; text-align: center; font-weight: bold">Created On</th>
                                                <th style="width:6%; text-align: center; font-weight: bold">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $row_number = $offset + 1;
                                            if (!$result || $result->RecordCount() == 0) {
                                                echo '<tr><td colspan="12" style="text-align: center;">No leads found</td></tr>';
                                            } else {
                                                while (!$result->EOF) {
                                                    $STATUS     = ($result->fields['ACTIVE'] == 1) ? "Active" : "Inactive";
                                                    $LEAD_ST    = $result->fields['LEAD_STATUS'] ?? '';
                                                    $IS_CALLED  = !empty($result->fields['IS_CALLED']);
                                                    $IS_APPT    = !empty($result->fields['IS_APPOINTMENT_CREATED']);
                                            ?>
                                                    <tr>
                                                        <td style="text-align: center" class="text-muted small"><?= $row_number++ ?></td>
                                                        <td style="text-align: center"><?= htmlspecialchars($result->fields['FIRST_NAME'] ?? '') ?></td>
                                                        <td style="text-align: center"><?= htmlspecialchars($result->fields['LAST_NAME'] ?? '') ?></td>
                                                        <td style="text-align: center"><?= htmlspecialchars($result->fields['EMAIL_ID'] ?? '') ?></td>
                                                        <td style="text-align: center"><?= htmlspecialchars($result->fields['PHONE'] ?? '') ?></td>
                                                        <td style="text-align: center"><?= htmlspecialchars($result->fields['LOCATION_NAME'] ?? '') ?></td>
                                                        <td style="text-align: center">
                                                            <?= $LEAD_ST ? htmlspecialchars($LEAD_ST) : '<span class="text-muted">—</span>' ?>
                                                        </td>
                                                        <td style="text-align: center"><?= htmlspecialchars($result->fields['OPPORTUNITY_SOURCE'] ?? '') ?></td>
                                                        <td style="text-align: center">
                                                            <?php if ($IS_CALLED): ?>
                                                                <span class="badge badge-call"><i class="bi bi-check2"></i> Yes</span>
                                                            <?php else: ?>
                                                                <span class="badge badge-no-call">No</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td style="text-align: center">
                                                            <?php if ($IS_APPT): ?>
                                                                <span class="badge badge-appt"><i class="bi bi-check2"></i> Yes</span>
                                                            <?php else: ?>
                                                                <span class="badge badge-no-appt">No</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td style="text-align: center">
                                                            <?= !empty($result->fields['CREATED_ON']) ? date('m-d-Y', strtotime($result->fields['CREATED_ON'])) : '' ?>
                                                        </td>
                                                        <td style="text-align: center">
                                                            <span class="badge <?= $STATUS == 'Active' ? 'bg-success' : 'bg-danger' ?>">
                                                                <?= $STATUS ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                            <?php
                                                    $result->MoveNext();
                                                }
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Pagination and Record Info -->
                                <?php if ($total_records > 0): ?>
                                    <div class="row mt-3">
                                        <div class="col-md-6 record-info">
                                            Showing <?= $offset + 1 ?> to <?= min($offset + $records_per_page, $total_records) ?> of <?= $total_records ?> leads
                                        </div>
                                        <div class="col-md-6">
                                            <nav>
                                                <ul class="pagination">
                                                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                                        <a class="page-link" href="?page=1<?= $query_string ? '&' . $query_string : '' ?>" aria-label="First">
                                                            <span aria-hidden="true">&laquo;&laquo;</span>
                                                        </a>
                                                    </li>

                                                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                                        <a class="page-link" href="?page=<?= $page - 1 ?><?= $query_string ? '&' . $query_string : '' ?>" aria-label="Previous">
                                                            <span aria-hidden="true">&laquo;</span>
                                                        </a>
                                                    </li>

                                                    <?php
                                                    $start_page = max(1, $page - 2);
                                                    $end_page = min($total_pages, $page + 2);

                                                    if ($start_page > 1) {
                                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                                    }

                                                    for ($i = $start_page; $i <= $end_page; $i++):
                                                    ?>
                                                        <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                                            <a class="page-link" href="?page=<?= $i ?><?= $query_string ? '&' . $query_string : '' ?>"><?= $i ?></a>
                                                        </li>
                                                    <?php
                                                    endfor;

                                                    if ($end_page < $total_pages) {
                                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                                    }
                                                    ?>

                                                    <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                                        <a class="page-link" href="?page=<?= $page + 1 ?><?= $query_string ? '&' . $query_string : '' ?>" aria-label="Next">
                                                            <span aria-hidden="true">&raquo;</span>
                                                        </a>
                                                    </li>

                                                    <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                                        <a class="page-link" href="?page=<?= $total_pages ?><?= $query_string ? '&' . $query_string : '' ?>" aria-label="Last">
                                                            <span aria-hidden="true">&raquo;&raquo;</span>
                                                        </a>
                                                    </li>
                                                </ul>
                                            </nav>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php require_once('../includes/footer.php'); ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

    <script>
        $(function() {
            $('.select2-lead').select2({
                placeholder: "Select lead status",
                allowClear: true
            });

            $('.select2-source').select2({
                placeholder: "Select source",
                allowClear: true
            });
        });

        function toggleDateRange() {
            var dateFilter = document.getElementById('date_filter').value;
            var dateRangeDiv = document.getElementById('date_range_div');
            if (dateFilter === 'range') {
                dateRangeDiv.style.display = 'block';
            } else {
                dateRangeDiv.style.display = 'none';
                document.getElementById('start_date').value = '';
                document.getElementById('end_date').value = '';
            }
        }

        document.getElementById('export-to-excel').addEventListener('click', function(e) {
            e.preventDefault();

            var filters = {
                date_filter: document.getElementById('date_filter').value,
                start_date: document.getElementById('start_date').value,
                end_date: document.getElementById('end_date').value,
                status_filter: document.getElementById('status_filter').value,
                lead_filter: document.getElementById('lead_filter').value,
                source_filter: document.getElementById('source_filter').value
            };

            var form = document.getElementById('excel-form');
            var hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'filter_data';
            hiddenInput.value = JSON.stringify(filters);
            form.appendChild(hiddenInput);
            form.submit();
        });

        <?php if (isset($_GET['reset'])): ?>
            window.location.href = window.location.pathname;
        <?php endif; ?>
    </script>

</body>

</html>