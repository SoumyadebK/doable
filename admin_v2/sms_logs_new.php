<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];

$title = "SMS Logs";

// Simple fix - convert to array if it's a string
if (!is_array($DEFAULT_LOCATION_ID)) {
    if (strpos($DEFAULT_LOCATION_ID, ',') !== false) {
        $DEFAULT_LOCATION_ID = array_map('trim', explode(',', $DEFAULT_LOCATION_ID));
    } else {
        $DEFAULT_LOCATION_ID = !empty($DEFAULT_LOCATION_ID) ? [$DEFAULT_LOCATION_ID] : [];
    }
}

$location_ids_for_sql = implode(',', $DEFAULT_LOCATION_ID);

// Pagination and filters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : 'all';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

$header_text = '';
$header_data = $db->Execute("SELECT * FROM `DOA_HEADER_TEXT` WHERE ACTIVE = 1 AND HEADER_TITLE = 'SMS Logs page'");
if ($header_data && $header_data->RecordCount() > 0) {
    $header_text = $header_data->fields['HEADER_TEXT'];
}

$offset = ($page - 1) * $per_page;

// Build search conditions
$where_conditions = [];
$where_conditions[] = "DOA_SMS_LOG.PK_LOCATION IN (" . $location_ids_for_sql . ")";

if (!empty($search)) {
    $where_conditions[] = "(DOA_USERS.FIRST_NAME LIKE '%" . addslashes($search) . "%' 
                           OR DOA_USERS.LAST_NAME LIKE '%" . addslashes($search) . "%' 
                           OR DOA_SMS_LOG.PHONE_NUMBER LIKE '%" . addslashes($search) . "%' 
                           OR DOA_SMS_LOG.MESSAGE LIKE '%" . addslashes($search) . "%')";
}

if ($status_filter == 'success') {
    $where_conditions[] = "DOA_SMS_LOG.IS_ERROR = 0";
} elseif ($status_filter == 'failed') {
    $where_conditions[] = "DOA_SMS_LOG.IS_ERROR = 1";
}

if (!empty($from_date)) {
    $where_conditions[] = "DATE(DOA_SMS_LOG.TRIGGER_TIME) >= '" . addslashes($from_date) . "'";
}

if (!empty($to_date)) {
    $where_conditions[] = "DATE(DOA_SMS_LOG.TRIGGER_TIME) <= '" . addslashes($to_date) . "'";
}

$where_clause = implode(" AND ", $where_conditions);

// Count total records
$count_query = "SELECT COUNT(*) as total 
                FROM DOA_SMS_LOG 
                INNER JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_SMS_LOG.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER 
                INNER JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER 
                LEFT JOIN $master_database.DOA_LOCATION AS DOA_LOCATION ON DOA_SMS_LOG.PK_LOCATION = DOA_LOCATION.PK_LOCATION 
                WHERE $where_clause";

$total_result = $db_account->Execute($count_query);
$total_records = $total_result->fields['total'];
$total_pages = ceil($total_records / $per_page);

// Get SMS logs for current page
$query = "SELECT DOA_SMS_LOG.*, 
          DOA_USERS.FIRST_NAME, 
          DOA_USERS.LAST_NAME, 
          DOA_LOCATION.LOCATION_NAME 
          FROM DOA_SMS_LOG 
          INNER JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_SMS_LOG.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER 
          INNER JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER 
          LEFT JOIN $master_database.DOA_LOCATION AS DOA_LOCATION ON DOA_SMS_LOG.PK_LOCATION = DOA_LOCATION.PK_LOCATION 
          WHERE $where_clause 
          ORDER BY DOA_SMS_LOG.TRIGGER_TIME DESC 
          LIMIT $offset, $per_page";

$sms_logs = $db_account->Execute($query);
?>

<!DOCTYPE html>
<html lang="en">
<?php include 'layout/header_script.php'; ?>
<?php require_once('../includes/header.php'); ?>
<?php include 'layout/header.php'; ?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> - Setup Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="assets/css/setup-styles.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <style>
        .date-filter-container {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .date-input {
            padding: 0.4rem 0.8rem;
            border-radius: 30px;
            border: 1px solid #e2e8f0;
            font-size: 0.85rem;
            background-color: #fff;
        }

        .date-input:focus {
            outline: none;
            border-color: #0d6efd;
            box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.1);
        }

        .status-filter-group {
            display: flex;
            gap: 0.5rem;
            background: #f1f5f9;
            padding: 0.25rem;
            border-radius: 40px;
        }

        .status-filter-btn {
            border: none;
            background: transparent;
            padding: 0.4rem 1rem;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 500;
            color: #475569;
            transition: all 0.2s;
        }

        .status-filter-btn.active {
            background: white;
            color: #0f172a;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .badge-status {
            padding: 4px 10px;
            border-radius: 30px;
            font-size: 0.7rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .badge-success {
            background: #dcfce7;
            color: #15803d;
        }

        .badge-failed {
            background: #fee2e2;
            color: #b91c1c;
        }

        .message-preview {
            max-width: 250px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            cursor: pointer;
        }

        .message-preview:hover {
            color: #0d6efd;
        }

        .avatar-circle {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.85rem;
            color: white;
            flex-shrink: 0;
        }

        .error-message {
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: #dc2626;
            font-size: 0.75rem;
        }

        .pagination .page-link {
            border-radius: 30px !important;
            margin: 0 2px;
            color: #334155;
            border: none;
            background: transparent;
        }

        .pagination .page-item.active .page-link {
            background-color: #0d6efd;
            color: white;
        }

        @media (max-width: 768px) {
            .search-container {
                width: 100%;
                margin-bottom: 0.5rem;
            }

            .date-filter-container {
                width: 100%;
            }
        }

        .header-note {
            background: #f0f9ff;
            border-left: 3px solid #0d6efd;
        }

        .refresh-btn {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            color: #475569;
            padding: 0.4rem 1rem;
            border-radius: 40px;
            transition: all 0.2s;
        }

        .refresh-btn:hover {
            background-color: #e9ecef;
        }

        /* jQuery UI Datepicker override for better visibility */
        .ui-datepicker {
            z-index: 1000 !important;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            padding: 10px;
        }

        .ui-datepicker table {
            font-size: 13px;
        }

        .ui-datepicker .ui-state-default {
            background: transparent;
            border: none;
            text-align: center;
        }

        .ui-datepicker .ui-state-active {
            background: #0d6efd !important;
            color: white !important;
            border-radius: 50%;
        }
    </style>
</head>

<body>

    <div class="container-fluid py-4 px-4 m-auto mx-auto dashboard-container">
        <div class="row g-4">
            <!-- Sidebar -->
            <div class="col-12 col-md-4 col-xl-3">
                <?php include 'layout/setup_sidebar.php'; ?>
            </div>

            <!-- Main Content -->
            <div class="col-12 col-md-8 col-xl-9">
                <div class="main-card">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
                        <div>
                            <h2 class="fw-semibold h4 mb-1">
                                <i class="bi bi-envelope-paper me-2 text-primary"></i><?= htmlspecialchars($title) ?>
                            </h2>
                            <p class="text-muted small mb-0">Track SMS communications, delivery status, and error logs</p>
                            <?php if (!empty($header_text)): ?>
                                <div class="mt-2 alert alert-light py-2 px-3 small bg-light rounded-3 header-note">
                                    <i class="bi bi-info-circle-fill me-1 text-primary"></i> <?= htmlspecialchars($header_text) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <button class="refresh-btn d-flex align-items-center gap-2" onclick="window.location.reload();">
                            <i class="bi bi-arrow-repeat"></i> Refresh
                        </button>
                    </div>

                    <!-- Filters -->
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <div class="search-container">
                            <i class="bi bi-search"></i>
                            <input type="text" class="form-control search-input" placeholder="Search by customer, phone, message..." id="searchInput" value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="status-filter-group">
                            <button class="status-filter-btn <?= $status_filter == 'all' ? 'active' : '' ?>" data-status="all">All</button>
                            <button class="status-filter-btn <?= $status_filter == 'success' ? 'active' : '' ?>" data-status="success">Success</button>
                            <button class="status-filter-btn <?= $status_filter == 'failed' ? 'active' : '' ?>" data-status="failed">Failed</button>
                        </div>
                    </div>

                    <!-- Date Range Filter -->
                    <div class="mb-3 d-flex flex-wrap gap-2 align-items-center">
                        <div class="date-filter-container">
                            <input type="text" id="from_date" class="date-input" placeholder="From Date" value="<?= htmlspecialchars($from_date) ?>" autocomplete="off">
                            <span>—</span>
                            <input type="text" id="to_date" class="date-input" placeholder="To Date" value="<?= htmlspecialchars($to_date) ?>" autocomplete="off">
                            <button class="btn btn-sm btn-outline-secondary rounded-pill" id="clearDatesBtn">
                                <i class="bi bi-x-circle"></i> Clear
                            </button>
                        </div>
                    </div>

                    <!-- Results count -->
                    <div class="text-muted small mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-envelope-paper"></i> <?= $total_records ?> <?= $total_records == 1 ? 'SMS record' : 'SMS records' ?>
                    </div>

                    <!-- SMS Logs Table -->
                    <div class="table-responsive">
                        <table class="table custom-table align-middle mb-4">
                            <thead>
                                <tr>
                                    <th>Send Date</th>
                                    <th>Location</th>
                                    <th>Customer</th>
                                    <th>Phone Number</th>
                                    <th>Message</th>
                                    <th>Status</th>
                                    <th>Error Message</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $counter = 0;
                                if ($sms_logs && !$sms_logs->EOF):
                                    while (!$sms_logs->EOF):
                                        $send_date = $sms_logs->fields['TRIGGER_TIME'];
                                        $location_name = $sms_logs->fields['LOCATION_NAME'] ?? '—';
                                        $customer_name = trim($sms_logs->fields['FIRST_NAME'] . ' ' . $sms_logs->fields['LAST_NAME']);
                                        $customer_name = !empty($customer_name) ? $customer_name : '—';
                                        $phone_number = $sms_logs->fields['PHONE_NUMBER'] ?? '—';
                                        $message = $sms_logs->fields['MESSAGE'] ?? '—';
                                        $is_error = $sms_logs->fields['IS_ERROR'] == 1;
                                        $error_message = $sms_logs->fields['ERROR_MESSAGE'] ?? '';

                                        $formatted_date = !empty($send_date) ? date('m/d/Y h:i A', strtotime($send_date)) : '—';
                                        $customer = getProfileBadge($customer_name);
                                        $customer_initial = $customer['initials'];
                                        $customer_color = $customer['color'];
                                ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <i class="bi bi-clock text-muted"></i>
                                                    <span class="small"><?= $formatted_date ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill small">
                                                    <i class="bi bi-geo-alt-fill me-1 text-secondary"></i> <?= htmlspecialchars($location_name) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span class="avatarname" style="color: #fff; background-color: <?= $customer_color ?>;"><?= $customer_initial; ?></span>
                                                    <span class="fw-medium"><?= htmlspecialchars($customer_name) ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center gap-1">
                                                    <i class="bi bi-telephone-fill text-muted small"></i>
                                                    <span><?= htmlspecialchars($phone_number) ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="message-preview" title="<?= htmlspecialchars($message) ?>" onclick='showFullMessage(`<?= htmlspecialchars(str_replace("'", "\\'", $message)) ?>`)'>
                                                    <i class="bi bi-chat-text me-1 text-muted"></i>
                                                    <?= htmlspecialchars(strlen($message) > 60 ? substr($message, 0, 60) . '...' : $message) ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if (!$is_error): ?>
                                                    <span class="badge-status badge-success">
                                                        <i class="bi bi-check-circle-fill"></i> Success
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge-status badge-failed">
                                                        <i class="bi bi-x-circle-fill"></i> Failed
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($error_message)): ?>
                                                    <div class="error-message" title="<?= htmlspecialchars($error_message) ?>">
                                                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                                        <?= htmlspecialchars(strlen($error_message) > 40 ? substr($error_message, 0, 40) . '...' : $error_message) ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted small">—</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php
                                        $sms_logs->MoveNext();
                                        $counter++;
                                    endwhile;
                                endif;
                                if ($total_records == 0):
                                    ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-5">
                                            <i class="bi bi-envelope-paper display-1 text-muted"></i>
                                            <p class="mt-3 text-muted">No SMS logs found for the selected filters</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 pt-2">
                            <div class="text-muted small">
                                Page <?= $page ?> of <?= $total_pages ?>
                            </div>
                            <nav aria-label="Page navigation">
                                <ul class="pagination pagination-sm mb-0 align-items-center">
                                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                        <a class="page-link border-0" href="?page=1&status_filter=<?= $status_filter ?>&search=<?= urlencode($search) ?>&from_date=<?= urlencode($from_date) ?>&to_date=<?= urlencode($to_date) ?>&per_page=<?= $per_page ?>" aria-label="First"><i class="bi bi-chevron-double-left"></i></a>
                                    </li>
                                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                        <a class="page-link border-0" href="?page=<?= $page - 1 ?>&status_filter=<?= $status_filter ?>&search=<?= urlencode($search) ?>&from_date=<?= urlencode($from_date) ?>&to_date=<?= urlencode($to_date) ?>&per_page=<?= $per_page ?>" aria-label="Previous"><i class="bi bi-chevron-left"></i></a>
                                    </li>
                                    <?php
                                    $start_page = max(1, $page - 2);
                                    $end_page = min($total_pages, $page + 2);
                                    if ($start_page > 1): ?>
                                        <li class="page-item"><a class="page-link" href="?page=1&status_filter=<?= $status_filter ?>&search=<?= urlencode($search) ?>&from_date=<?= urlencode($from_date) ?>&to_date=<?= urlencode($to_date) ?>&per_page=<?= $per_page ?>">1</a></li>
                                        <?php if ($start_page > 2): ?>
                                            <li class="page-item disabled"><span class="page-link border-0 bg-transparent">...</span></li>
                                        <?php endif; ?>
                                    <?php endif;
                                    for ($i = $start_page; $i <= $end_page; $i++): ?>
                                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                            <a class="page-link" href="?page=<?= $i ?>&status_filter=<?= $status_filter ?>&search=<?= urlencode($search) ?>&from_date=<?= urlencode($from_date) ?>&to_date=<?= urlencode($to_date) ?>&per_page=<?= $per_page ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor;
                                    if ($end_page < $total_pages): ?>
                                        <?php if ($end_page < $total_pages - 1): ?>
                                            <li class="page-item disabled"><span class="page-link border-0 bg-transparent">...</span></li>
                                        <?php endif; ?>
                                        <li class="page-item"><a class="page-link" href="?page=<?= $total_pages ?>&status_filter=<?= $status_filter ?>&search=<?= urlencode($search) ?>&from_date=<?= urlencode($from_date) ?>&to_date=<?= urlencode($to_date) ?>&per_page=<?= $per_page ?>"><?= $total_pages ?></a></li>
                                    <?php endif; ?>
                                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                        <a class="page-link border-0" href="?page=<?= $page + 1 ?>&status_filter=<?= $status_filter ?>&search=<?= urlencode($search) ?>&from_date=<?= urlencode($from_date) ?>&to_date=<?= urlencode($to_date) ?>&per_page=<?= $per_page ?>" aria-label="Next"><i class="bi bi-chevron-right"></i></a>
                                    </li>
                                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                        <a class="page-link border-0" href="?page=<?= $total_pages ?>&status_filter=<?= $status_filter ?>&search=<?= urlencode($search) ?>&from_date=<?= urlencode($from_date) ?>&to_date=<?= urlencode($to_date) ?>&per_page=<?= $per_page ?>" aria-label="Last"><i class="bi bi-chevron-double-right"></i></a>
                                    </li>
                                </ul>
                            </nav>
                            <div>
                                <select class="form-select form-select-sm page-select rounded-pill py-1 px-3" id="perPageSelect">
                                    <option value="10" <?= $per_page == 10 ? 'selected' : '' ?>>10 / page</option>
                                    <option value="25" <?= $per_page == 25 ? 'selected' : '' ?>>25 / page</option>
                                    <option value="50" <?= $per_page == 50 ? 'selected' : '' ?>>50 / page</option>
                                    <option value="100" <?= $per_page == 100 ? 'selected' : '' ?>>100 / page</option>
                                </select>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php require_once('../includes/footer.php'); ?>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            // Initialize datepickers
            $("#from_date, #to_date").datepicker({
                dateFormat: "yy-mm-dd",
                changeMonth: true,
                changeYear: true,
                showButtonPanel: true,
                yearRange: "c-5:c+5"
            });

            // Search with debounce
            let searchTimeout;
            $('#searchInput').on('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    let searchVal = encodeURIComponent($(this).val());
                    window.location.href = '?status_filter=<?= $status_filter ?>&search=' + searchVal + '&from_date=<?= urlencode($from_date) ?>&to_date=<?= urlencode($to_date) ?>&per_page=<?= $per_page ?>';
                }, 500);
            });

            // Per page change
            $('#perPageSelect').on('change', function() {
                window.location.href = '?status_filter=<?= $status_filter ?>&search=<?= urlencode($search) ?>&from_date=<?= urlencode($from_date) ?>&to_date=<?= urlencode($to_date) ?>&per_page=' + $(this).val();
            });

            // Status filter buttons
            $('.status-filter-btn').on('click', function() {
                let newStatus = $(this).data('status');
                window.location.href = '?status_filter=' + newStatus + '&search=<?= urlencode($search) ?>&from_date=<?= urlencode($from_date) ?>&to_date=<?= urlencode($to_date) ?>&per_page=<?= $per_page ?>';
            });

            // Date range filter
            $('#from_date, #to_date').on('change', function() {
                let fromDate = $('#from_date').val();
                let toDate = $('#to_date').val();
                window.location.href = '?status_filter=<?= $status_filter ?>&search=<?= urlencode($search) ?>&from_date=' + fromDate + '&to_date=' + toDate + '&per_page=<?= $per_page ?>';
            });

            // Clear dates
            $('#clearDatesBtn').on('click', function() {
                window.location.href = '?status_filter=<?= $status_filter ?>&search=<?= urlencode($search) ?>&per_page=<?= $per_page ?>';
            });
        });

        // Show full message in modal
        function showFullMessage(message) {
            Swal.fire({
                title: 'SMS Message',
                html: '<div style="text-align: left; padding: 10px; word-wrap: break-word;">' + message + '</div>',
                icon: 'info',
                confirmButtonText: 'Close',
                confirmButtonColor: '#0d6efd'
            });
        }
    </script>

    <?php
    // Helper functions
    function getInitials($name)
    {
        if ($name == '—' || empty($name)) {
            return '?';
        }
        $words = explode(' ', trim($name));
        $initials = '';
        foreach ($words as $word) {
            if (!empty($word)) {
                $initials .= strtoupper($word[0]);
            }
            if (strlen($initials) >= 2) break;
        }
        return $initials ?: '?';
    }

    function getAvatarColor($index)
    {
        $gradients = [
            ['start' => '#667eea', 'end' => '#764ba2'],
            ['start' => '#f093fb', 'end' => '#f5576c'],
            ['start' => '#4facfe', 'end' => '#00f2fe'],
            ['start' => '#43e97b', 'end' => '#38f9d7'],
            ['start' => '#fa709a', 'end' => '#fee140'],
            ['start' => '#a18cd1', 'end' => '#fbc2eb'],
            ['start' => '#ff9a9e', 'end' => '#fecfef'],
            ['start' => '#ffecd2', 'end' => '#fcb69f'],
            ['start' => '#a6c1ee', 'end' => '#fbc2eb'],
            ['start' => '#fbc2eb', 'end' => '#a6c1ee']
        ];
        return $gradients[$index % count($gradients)];
    }
    ?>
</body>

</html>