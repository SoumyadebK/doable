<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];

// Simple fix - convert to array if it's a string
if (!is_array($DEFAULT_LOCATION_ID)) {
    // If it's a comma-separated string like "13, 27"
    if (strpos($DEFAULT_LOCATION_ID, ',') !== false) {
        $DEFAULT_LOCATION_ID = array_map('trim', explode(',', $DEFAULT_LOCATION_ID));
    } else {
        // If it's a single value
        $DEFAULT_LOCATION_ID = !empty($DEFAULT_LOCATION_ID) ? [$DEFAULT_LOCATION_ID] : [];
    }
}

// Get location count
$location_count = count($DEFAULT_LOCATION_ID);
$multiple_locations = ($location_count > 1);

$title = "All Services / Service Codes";

$status_check = isset($_GET['status']) ? $_GET['status'] : 'active';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 8;

if ($status_check == 'active') {
    $status = 1;
} elseif ($status_check == 'inactive') {
    $status = 0;
}

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

$header_text = '';
$header_data = $db->Execute("SELECT * FROM `DOA_HEADER_TEXT` WHERE ACTIVE = 1 AND HEADER_TITLE = 'Service Codes page'");
if ($header_data && $header_data->RecordCount() > 0) {
    $header_text = $header_data->fields['HEADER_TEXT'];
}

// Convert array to string for SQL IN clause
$location_ids_for_sql = implode(',', $DEFAULT_LOCATION_ID);

$offset = ($page - 1) * $per_page;

// Count total records
$count_query = "SELECT COUNT(DISTINCT DOA_SERVICE_MASTER.PK_SERVICE_MASTER) as total 
                FROM DOA_SERVICE_MASTER 
                LEFT JOIN DOA_SERVICE_CODE ON DOA_SERVICE_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_CODE.PK_SERVICE_MASTER 
                LEFT JOIN $master_database.DOA_LOCATION AS DOA_LOCATION ON DOA_SERVICE_MASTER.PK_LOCATION = DOA_LOCATION.PK_LOCATION 
                WHERE DOA_SERVICE_MASTER.PK_LOCATION IN (" . $location_ids_for_sql . ") 
                AND DOA_SERVICE_MASTER.IS_DELETED = 0 
                AND DOA_SERVICE_MASTER.ACTIVE = '$status'";

if (!empty($search)) {
    $count_query .= " AND (DOA_SERVICE_MASTER.SERVICE_NAME LIKE '%" . addslashes($search) . "%' 
                      OR DOA_SERVICE_CODE.SERVICE_CODE LIKE '%" . addslashes($search) . "%' 
                      OR DOA_LOCATION.LOCATION_NAME LIKE '%" . addslashes($search) . "%')";
}

$total_result = $db_account->Execute($count_query);
$total_records = $total_result->fields['total'];
$total_pages = ceil($total_records / $per_page);

// Get services for current page
$query = "SELECT DISTINCT DOA_SERVICE_MASTER.PK_SERVICE_MASTER, 
          DOA_SERVICE_MASTER.SERVICE_NAME, 
          DOA_SERVICE_CODE.SERVICE_CODE, 
          DOA_SERVICE_MASTER.DESCRIPTION, 
          DOA_SERVICE_MASTER.ACTIVE, 
          DOA_SERVICE_CODE.COUNT_ON_CALENDAR, 
          DOA_SERVICE_CODE.SORT_ORDER, 
          DOA_LOCATION.LOCATION_NAME 
          FROM DOA_SERVICE_MASTER 
          LEFT JOIN DOA_SERVICE_CODE ON DOA_SERVICE_MASTER.PK_SERVICE_MASTER = DOA_SERVICE_CODE.PK_SERVICE_MASTER 
          LEFT JOIN $master_database.DOA_LOCATION AS DOA_LOCATION ON DOA_SERVICE_MASTER.PK_LOCATION = DOA_LOCATION.PK_LOCATION 
          WHERE DOA_SERVICE_MASTER.PK_LOCATION IN (" . $location_ids_for_sql . ") 
          AND DOA_SERVICE_MASTER.IS_DELETED = 0 
          AND DOA_SERVICE_MASTER.ACTIVE = '$status'";

if (!empty($search)) {
    $query .= " AND (DOA_SERVICE_MASTER.SERVICE_NAME LIKE '%" . addslashes($search) . "%' 
                OR DOA_SERVICE_CODE.SERVICE_CODE LIKE '%" . addslashes($search) . "%' 
                OR DOA_LOCATION.LOCATION_NAME LIKE '%" . addslashes($search) . "%')";
}

$query .= " ORDER BY DOA_SERVICE_CODE.SORT_ORDER ASC LIMIT $offset, $per_page";
$services = $db_account->Execute($query);
?>

<!DOCTYPE html>
<html lang="en">
<?php include 'layout/header_script.php'; ?>
<?php include 'layout/header.php'; ?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> - Setup Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="assets/css/setup-styles.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="assets/css/setup-styles.css" rel="stylesheet">
    <style>
        .avatar-circle {
            width: 44px;
            height: 44px;
            background-color: #eef2ff;
            border-radius: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1rem;
            color: #1e293b;
            flex-shrink: 0;
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

        .badge-active {
            background: #dcfce7;
            color: #15803d;
        }

        .badge-inactive {
            background: #fee2e2;
            color: #b91c1c;
        }

        .service-code-badge {
            font-family: monospace;
            background: #f1f5f9;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.75rem;
            display: inline-block;
        }

        .description-text {
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-size: 0.8rem;
            color: #475569;
        }

        .doc-count {
            background: #eef2ff;
            padding: 4px 10px;
            border-radius: 30px;
            font-size: 0.7rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        /* Toggle Switch */
        .switch {
            position: relative;
            display: inline-block;
            width: 44px;
            height: 24px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #cbd5e1;
            transition: 0.3s;
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: 0.3s;
            border-radius: 50%;
        }

        input:checked+.slider {
            background-color: #0d6efd;
        }

        input:checked+.slider:before {
            transform: translateX(20px);
        }

        .cursor-pointer {
            cursor: pointer;
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

        .dropdown-item i {
            width: 1.2rem;
        }

        .location-badge {
            background: #f8f9fa;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .sort-order-badge {
            background: #fef9c3;
            color: #854d0e;
            padding: 4px 10px;
            border-radius: 30px;
            font-size: 0.7rem;
            font-weight: 600;
            display: inline-block;
        }

        @media (max-width: 768px) {
            .search-container {
                width: 100%;
                margin-bottom: 0.5rem;
            }

            .d-flex.justify-content-between {
                flex-direction: column;
                align-items: stretch !important;
                gap: 0.75rem;
            }

            .status-toggle-group {
                align-self: flex-start;
            }
        }

        .header-note {
            background: #f0f9ff;
            border-left: 3px solid #0d6efd;
        }

        .action-icons {
            display: flex;
            gap: 12px;
            align-items: center;
            justify-content: center;
        }

        .action-icons a {
            color: #64748b;
            transition: color 0.2s;
        }

        .action-icons a:hover {
            color: #0d6efd;
        }

        .action-icons .text-danger:hover {
            color: #dc2626 !important;
        }
    </style>
</head>

<body>

    <div class="container-fluid py-4 px-4 m-auto mx-auto dashboard-container">
        <div class="row g-4">
            <!-- Sidebar -->
            <div class="col-12 col-md-4 col-xl-2">
                <?php include 'layout/setup_sidebar.php'; ?>
            </div>

            <!-- Main Content -->
            <div class="col-12 col-md-8 col-xl-10">
                <div class="main-card">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
                        <div>
                            <h2 class="fw-semibold h4 mb-1">
                                <?php if ($status_check == 'inactive') { ?>
                                    <i class="bi bi-slash-circle me-2 text-muted"></i>Not Active Services
                                <?php } else { ?>
                                    <i class="bi bi-check-circle-fill me-2 text-success"></i>Active Services
                                <?php } ?>
                            </h2>
                            <p class="text-muted small mb-0">Manage service codes, descriptions, and calendar settings</p>
                        </div>
                        <div class="d-flex gap-2">
                            <!-- <?php if ($status_check == 'inactive') { ?>
                                <button class="btn btn-outline-custom d-flex align-items-center gap-2" onclick="window.location.href='all_service_codes.php?status=active'">
                                    <i class="bi bi-check-circle"></i> Show Active
                                </button>
                            <?php } else { ?>
                                <button class="btn btn-outline-custom d-flex align-items-center gap-2" onclick="window.location.href='all_service_codes.php?status=inactive'">
                                    <i class="bi bi-slash-circle"></i> Show Not Active
                                </button>
                            <?php } ?> -->
                            <button class="btn btn-success-custom rounded-pill d-flex align-items-center gap-2" onclick="createNewService()">
                                <i class="bi bi-plus-lg"></i> Create New Service
                            </button>
                        </div>
                    </div>

                    <!-- Filters (status & search) -->
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <div class="search-container">
                            <i class="bi bi-search"></i>
                            <input type="text" class="form-control search-input" placeholder="Search by name, code, location..." id="searchInput" value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="status-toggle-group">
                            <button class="status-btn <?= $status_check == 'active' ? 'active' : '' ?>" data-status="active">Active</button>
                            <button class="status-btn <?= $status_check == 'inactive' ? 'active' : '' ?>" data-status="inactive">Not Active</button>
                        </div>
                    </div>

                    <!-- Results count -->
                    <div class="text-muted small mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-grid-3x3-gap-fill"></i> <?= $total_records ?> <?= $total_records == 1 ? 'service' : 'services' ?>
                    </div>

                    <!-- Services Table -->
                    <div class="table-responsive">
                        <table class="table custom-table align-middle mb-4">
                            <thead>
                                <tr>
                                    <th style="width: 40px;">#</th>
                                    <th>Service Name / Code</th>
                                    <th style="text-align: center;">Location</th>
                                    <th style="text-align: center;">Description</th>
                                    <th style="text-align: center;">Documents</th>
                                    <th style="text-align: center;">Count on Calendar</th>
                                    <th style="text-align: center;">Sort Order</th>
                                    <th style="text-align: center;">Status</th>
                                    <th style="width: 80px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $counter = 0;
                                $row_number = $offset + 1;
                                if ($services && !$services->EOF):
                                    while (!$services->EOF):
                                        $PK_SERVICE_MASTER = $services->fields['PK_SERVICE_MASTER'];
                                        $service_name = $services->fields['SERVICE_NAME'];
                                        $service_code = $services->fields['SERVICE_CODE'] ?? '—';
                                        $location_name = $services->fields['LOCATION_NAME'] ?? '—';
                                        $description = $services->fields['DESCRIPTION'] ?? '—';
                                        $count_on_calendar = isset($services->fields['COUNT_ON_CALENDAR']) && $services->fields['COUNT_ON_CALENDAR'] == 1;
                                        $sort_order = $services->fields['SORT_ORDER'] ?? '—';
                                        $is_active = $services->fields['ACTIVE'] == 1;

                                        // Get document count
                                        $doc_row = $db_account->Execute("SELECT COUNT(*) as doc_count FROM `DOA_SERVICE_DOCUMENTS` WHERE PK_SERVICE_MASTER = " . $PK_SERVICE_MASTER);
                                        $doc_count = ($doc_row && !$doc_row->EOF) ? $doc_row->fields['doc_count'] : 0;

                                        $initials = getServiceInitials($service_name);
                                        $bg_color = getServiceAvatarColor($counter);
                                ?>
                                        <tr>
                                            <td class="text-muted small fw-medium"><?= $row_number++ ?></td>
                                            <td>
                                                <div class="d-flex align-items-center gap-3">
                                                    <div>
                                                        <div class="fw-semibold"><?= htmlspecialchars($service_name) ?></div>
                                                        <div class="mt-1">
                                                            <span class="service-code-badge">
                                                                <i class="bi bi-upc-scan me-1"></i> <?= htmlspecialchars($service_code) ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="location-badge">
                                                    <i class="bi bi-geo-alt-fill text-secondary"></i> <?= htmlspecialchars($location_name) ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <div class="description-text" title="<?= htmlspecialchars($description) ?>">
                                                    <?= htmlspecialchars(strlen($description) > 60 ? substr($description, 0, 60) . '...' : $description) ?>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="doc-count">
                                                    <i class="bi bi-paperclip"></i> <?= $doc_count ?> <?= $doc_count == 1 ? 'file' : 'files' ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <label class="switch">
                                                    <input type="checkbox" class="calendar-switch" data-service-id="<?= $PK_SERVICE_MASTER ?>" <?= $count_on_calendar ? 'checked' : '' ?>>
                                                    <span class="slider"></span>
                                                </label>
                                            </td>
                                            <td class="text-center">
                                                <span class="sort-order-badge">
                                                    <i class="bi bi-sort-numeric-down-alt me-1"></i> <?= htmlspecialchars($sort_order) ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($is_active): ?>
                                                    <span class="badge-status badge-active"><i class="bi bi-check-circle-fill"></i> Active</span>
                                                <?php else: ?>
                                                    <span class="badge-status badge-inactive"><i class="bi bi-x-circle-fill"></i> Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <div class="action-icons">
                                                    <a href="javascript:;" onclick="editService(<?= $PK_SERVICE_MASTER ?>);" title="Edit" style="font-size:18px">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </a>
                                                    <a href="javascript:;" onclick="ConfirmDelete(<?= $PK_SERVICE_MASTER ?>);" title="Delete" style="font-size:18px" class="text-danger">
                                                        <i class="bi bi-trash3"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php
                                        $services->MoveNext();
                                        $counter++;
                                    endwhile;
                                endif;
                                if ($total_records == 0 && !empty($location_ids_for_sql)):
                                    ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-5">
                                            <i class="bi bi-building display-1 text-muted"></i>
                                            <p class="mt-3 text-muted">No services found for the selected filters</p>
                                            <button class="btn btn-sm btn-outline-primary mt-2" onclick="createNewService()">Create your first service</button>
                                        </td>
                                    </tr>
                                <?php elseif (empty($location_ids_for_sql)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-5">
                                            <i class="bi bi-geo-alt-exclamation display-1 text-muted"></i>
                                            <p class="mt-3 text-muted">No locations selected. Please update your profile settings.</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1 && !empty($location_ids_for_sql)): ?>
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 pt-2">
                            <div class="text-muted small">
                                Page <?= $page ?> of <?= $total_pages ?>
                            </div>
                            <nav aria-label="Page navigation">
                                <ul class="pagination pagination-sm mb-0 align-items-center">
                                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                        <a class="page-link border-0" href="?page=1&status=<?= $status_check ?>&search=<?= urlencode($search) ?>&per_page=<?= $per_page ?>" aria-label="First"><i class="bi bi-chevron-double-left"></i></a>
                                    </li>
                                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                        <a class="page-link border-0" href="?page=<?= $page - 1 ?>&status=<?= $status_check ?>&search=<?= urlencode($search) ?>&per_page=<?= $per_page ?>" aria-label="Previous"><i class="bi bi-chevron-left"></i></a>
                                    </li>
                                    <?php
                                    $start_page = max(1, $page - 2);
                                    $end_page = min($total_pages, $page + 2);
                                    if ($start_page > 1): ?>
                                        <li class="page-item"><a class="page-link" href="?page=1&status=<?= $status_check ?>&search=<?= urlencode($search) ?>&per_page=<?= $per_page ?>">1</a></li>
                                        <?php if ($start_page > 2): ?>
                                            <li class="page-item disabled"><span class="page-link border-0 bg-transparent">...</span></li>
                                        <?php endif; ?>
                                    <?php endif;
                                    for ($i = $start_page; $i <= $end_page; $i++): ?>
                                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                            <a class="page-link" href="?page=<?= $i ?>&status=<?= $status_check ?>&search=<?= urlencode($search) ?>&per_page=<?= $per_page ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor;
                                    if ($end_page < $total_pages): ?>
                                        <?php if ($end_page < $total_pages - 1): ?>
                                            <li class="page-item disabled"><span class="page-link border-0 bg-transparent">...</span></li>
                                        <?php endif; ?>
                                        <li class="page-item"><a class="page-link" href="?page=<?= $total_pages ?>&status=<?= $status_check ?>&search=<?= urlencode($search) ?>&per_page=<?= $per_page ?>"><?= $total_pages ?></a></li>
                                    <?php endif; ?>
                                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                        <a class="page-link border-0" href="?page=<?= $page + 1 ?>&status=<?= $status_check ?>&search=<?= urlencode($search) ?>&per_page=<?= $per_page ?>" aria-label="Next"><i class="bi bi-chevron-right"></i></a>
                                    </li>
                                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                        <a class="page-link border-0" href="?page=<?= $total_pages ?>&status=<?= $status_check ?>&search=<?= urlencode($search) ?>&per_page=<?= $per_page ?>" aria-label="Last"><i class="bi bi-chevron-double-right"></i></a>
                                    </li>
                                </ul>
                            </nav>
                            <div>
                                <select class="form-select form-select-sm page-select rounded-pill py-1 px-3" id="perPageSelect">
                                    <option value="8" <?= $per_page == 8 ? 'selected' : '' ?>>8 / page</option>
                                    <option value="10" <?= $per_page == 10 ? 'selected' : '' ?>>10 / page</option>
                                    <option value="25" <?= $per_page == 25 ? 'selected' : '' ?>>25 / page</option>
                                    <option value="50" <?= $per_page == 50 ? 'selected' : '' ?>>50 / page</option>
                                </select>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php require_once('../includes/footer.php'); ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        // Search with debounce
        let searchTimeout;
        $('#searchInput').on('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                let searchVal = encodeURIComponent($(this).val());
                window.location.href = '?status=<?= $status_check ?>&search=' + searchVal + '&per_page=<?= $per_page ?>';
            }, 500);
        });

        // Per page change
        $('#perPageSelect').on('change', function() {
            window.location.href = '?status=<?= $status_check ?>&search=<?= urlencode($search) ?>&per_page=' + $(this).val();
        });

        // Status toggle buttons
        $('.status-btn').on('click', function() {
            let newStatus = $(this).data('status');
            if (newStatus) {
                window.location.href = '?status=' + newStatus + '&search=<?= urlencode($search) ?>&per_page=<?= $per_page ?>';
            }
        });

        // Count on Calendar toggle
        $('.calendar-switch').on('change', function() {
            const serviceId = $(this).data('service-id');
            const countOnCalendar = $(this).prop('checked') ? 1 : 0;

            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                data: {
                    FUNCTION_NAME: 'updateCountOnCalendar',
                    PK_SERVICE_MASTER: serviceId,
                    COUNT_ON_CALENDAR: countOnCalendar
                },
                success: function(data) {
                    // Optional: Show toast notification
                },
                error: function() {
                    $(this).prop('checked', !$(this).prop('checked'));
                    alert('Error updating calendar setting');
                }
            });
        });

        // Delete service
        function ConfirmDelete(PK_SERVICE_MASTER) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "ajax/AjaxFunctions.php",
                        type: 'POST',
                        data: {
                            FUNCTION_NAME: 'deleteServiceData',
                            PK_SERVICE_MASTER: PK_SERVICE_MASTER
                        },
                        success: function(data) {
                            Swal.fire('Deleted!', 'Service has been deleted.', 'success');
                            window.location.href = `all_service_codes.php?status=<?= $status_check ?>&search=<?= urlencode($search) ?>&per_page=<?= $per_page ?>`;
                        },
                        error: function() {
                            Swal.fire('Error!', 'Something went wrong.', 'error');
                        }
                    });
                }
            });
        }

        // Edit service
        function editService(id) {
            <?php if ($multiple_locations) { ?>
                Swal.fire({
                    title: 'Multiple Locations Selected!',
                    html: 'You have selected <?= $location_count ?> locations.<br><br>Please select one location to edit Services / Service Codes.',
                    icon: 'warning',
                    confirmButtonText: 'OK'
                });
            <?php } else { ?>
                window.location.href = "service_codes.php?id=" + id;
            <?php } ?>
        }

        // Create new service
        function createNewService() {
            <?php if ($multiple_locations) { ?>
                Swal.fire({
                    title: 'Multiple Locations Selected!',
                    html: 'You have selected <strong><?= $location_count ?></strong> locations.<br><br>Please select one location to create a new Service / Service Code.',
                    icon: 'warning',
                    confirmButtonText: 'OK'
                });
            <?php } else { ?>
                window.location.href = 'service_codes.php';
            <?php } ?>
        }
    </script>

    <?php
    // Helper functions
    function getServiceInitials($name)
    {
        $words = explode(' ', trim($name));
        $initials = '';
        foreach ($words as $word) {
            if (!empty($word)) {
                $initials .= strtoupper($word[0]);
            }
            if (strlen($initials) >= 2) break;
        }
        return $initials ?: 'SR';
    }

    function getServiceAvatarColor($index)
    {
        $colors = ['#fef08a', '#fed7aa', '#bfdbfe', '#ddd6fe', '#fbcfe8', '#bbf7d0', '#c4b5fd', '#fdba74', '#a7f3d0', '#fecaca', '#bae6fd', '#d9f99d'];
        return $colors[$index % count($colors)];
    }
    ?>
</body>

</html>