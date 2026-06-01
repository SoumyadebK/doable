<?php
require_once('../global/config.php');
global $db;
global $db_account;

$title = "Gift Certificates Setup";

$status_check = isset($_GET['status']) ? $_GET['status'] : 'active';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 8;

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];

// Simple fix - convert to array if it's a string
if (!is_array($DEFAULT_LOCATION_ID)) {
    if (strpos($DEFAULT_LOCATION_ID, ',') !== false) {
        $DEFAULT_LOCATION_ID = array_map('trim', explode(',', $DEFAULT_LOCATION_ID));
    } else {
        $DEFAULT_LOCATION_ID = !empty($DEFAULT_LOCATION_ID) ? [$DEFAULT_LOCATION_ID] : [];
    }
}

$location_ids_for_sql = implode(',', $DEFAULT_LOCATION_ID);
$multiple_locations = (count($DEFAULT_LOCATION_ID) > 1);

$header_text = '';
$header_data = $db->Execute("SELECT * FROM `DOA_HEADER_TEXT` WHERE ACTIVE = 1 AND HEADER_TITLE = 'Gift Certificates Setup page'");
if ($header_data && $header_data->RecordCount() > 0) {
    $header_text = $header_data->fields['HEADER_TEXT'];
}

$offset = ($page - 1) * $per_page;

// Build active condition
if ($status_check == 'active') {
    $active_condition = "DOA_GIFT_CERTIFICATE_SETUP.ACTIVE = 1";
} else {
    $active_condition = "DOA_GIFT_CERTIFICATE_SETUP.ACTIVE = 0";
}

// Count total records
$count_query = "SELECT COUNT(DISTINCT DOA_GIFT_CERTIFICATE_SETUP.PK_GIFT_CERTIFICATE_SETUP) as total 
                FROM DOA_GIFT_CERTIFICATE_SETUP 
                JOIN DOA_GIFT_LOCATION ON DOA_GIFT_CERTIFICATE_SETUP.PK_GIFT_CERTIFICATE_SETUP = DOA_GIFT_LOCATION.PK_GIFT_CERTIFICATE_SETUP 
                WHERE DOA_GIFT_LOCATION.PK_LOCATION IN (" . $location_ids_for_sql . ") 
                AND DOA_GIFT_CERTIFICATE_SETUP.PK_ACCOUNT_MASTER = " . intval($_SESSION['PK_ACCOUNT_MASTER']) . " 
                AND $active_condition";

if (!empty($search)) {
    $count_query .= " AND (DOA_GIFT_CERTIFICATE_SETUP.GIFT_CERTIFICATE_NAME LIKE '%" . addslashes($search) . "%' 
                      OR DOA_GIFT_CERTIFICATE_SETUP.GIFT_CERTIFICATE_CODE LIKE '%" . addslashes($search) . "%')";
}

$total_result = $db_account->Execute($count_query);
$total_records = $total_result->fields['total'];
$total_pages = ceil($total_records / $per_page);

// Get gift certificate setups for current page
$query = "SELECT DISTINCT DOA_GIFT_CERTIFICATE_SETUP.PK_GIFT_CERTIFICATE_SETUP, 
          DOA_GIFT_CERTIFICATE_SETUP.GIFT_CERTIFICATE_CODE, 
          DOA_GIFT_CERTIFICATE_SETUP.GIFT_CERTIFICATE_NAME, 
          DOA_GIFT_CERTIFICATE_SETUP.EFFECTIVE_DATE, 
          DOA_GIFT_CERTIFICATE_SETUP.END_DATE, 
          DOA_GIFT_CERTIFICATE_SETUP.MINIMUM_AMOUNT, 
          DOA_GIFT_CERTIFICATE_SETUP.MAXIMUM_AMOUNT, 
          DOA_GIFT_CERTIFICATE_SETUP.ACTIVE 
          FROM DOA_GIFT_CERTIFICATE_SETUP 
          JOIN DOA_GIFT_LOCATION ON DOA_GIFT_CERTIFICATE_SETUP.PK_GIFT_CERTIFICATE_SETUP = DOA_GIFT_LOCATION.PK_GIFT_CERTIFICATE_SETUP 
          WHERE DOA_GIFT_LOCATION.PK_LOCATION IN (" . $location_ids_for_sql . ") 
          AND DOA_GIFT_CERTIFICATE_SETUP.PK_ACCOUNT_MASTER = " . intval($_SESSION['PK_ACCOUNT_MASTER']) . " 
          AND $active_condition";

if (!empty($search)) {
    $query .= " AND (DOA_GIFT_CERTIFICATE_SETUP.GIFT_CERTIFICATE_NAME LIKE '%" . addslashes($search) . "%' 
                OR DOA_GIFT_CERTIFICATE_SETUP.GIFT_CERTIFICATE_CODE LIKE '%" . addslashes($search) . "%')";
}

$query .= " ORDER BY DOA_GIFT_CERTIFICATE_SETUP.GIFT_CERTIFICATE_NAME ASC LIMIT $offset, $per_page";
$gift_setups = $db_account->Execute($query);
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="assets/css/setup-styles.css" rel="stylesheet">
    <style>
        .avatar-circle {
            width: 52px;
            height: 52px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 52px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1rem;
            color: white;
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

        .amount-range {
            background: #f1f5f9;
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: #1e293b;
        }

        .certificate-code {
            font-family: 'Courier New', monospace;
            background: #f1f5f9;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            display: inline-block;
        }

        .date-badge {
            background: #f8f9fa;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: #475569;
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

        .action-icons {
            display: flex;
            gap: 12px;
            align-items: center;
            justify-content: flex-start;
        }

        .action-icons a {
            color: #64748b;
            transition: color 0.2s;
            font-size: 1.1rem;
        }

        .action-icons a:hover {
            color: #0d6efd;
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

        .empty-state {
            text-align: center;
            padding: 3rem;
        }

        .empty-state i {
            font-size: 4rem;
            color: #cbd5e1;
        }

        .gift-icon {
            font-size: 1.2rem;
            margin-right: 6px;
            color: #0d6efd;
        }

        .warning-badge {
            background: #fef3c7;
            color: #92400e;
            padding: 4px 10px;
            border-radius: 30px;
            font-size: 0.65rem;
            display: inline-flex;
            align-items: center;
            gap: 4px;
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
                                <?php if ($status_check == 'inactive') { ?>
                                    <i class="bi bi-archive me-2 text-muted"></i>Not Active Gift Certificates Setup
                                <?php } else { ?>
                                    <i class="bi bi-gift-fill me-2 text-primary"></i>Active Gift Certificates Setup
                                <?php } ?>
                            </h2>
                            <p class="text-muted small mb-0">Configure gift certificate templates, amount ranges, and validity periods</p>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-success-custom rounded-pill d-flex align-items-center gap-2" onclick="createNewGiftSetup()">
                                <i class="bi bi-plus-lg"></i> Create New
                            </button>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <div class="search-container">
                            <i class="bi bi-search"></i>
                            <input type="text" class="form-control search-input" placeholder="Search by name or code..." id="searchInput" value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="status-toggle-group">
                            <button class="status-btn <?= $status_check == 'active' ? 'active' : '' ?>" data-status="active">Active</button>
                            <button class="status-btn <?= $status_check == 'inactive' ? 'active' : '' ?>" data-status="inactive">Not Active</button>
                        </div>
                    </div>

                    <!-- Results count -->
                    <div class="text-muted small mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-gift"></i> <?= $total_records ?> <?= $total_records == 1 ? 'gift certificate template' : 'gift certificate templates' ?>
                    </div>

                    <!-- Gift Certificates Setup Table -->
                    <div class="table-responsive">
                        <table class="table custom-table align-middle mb-4">
                            <thead>
                                <tr>
                                    <th style="width: 40px;">#</th>
                                    <th>Certificate Name / Code</th>
                                    <th>Amount Range</th>
                                    <th>Effective Date</th>
                                    <th>End Date</th>
                                    <th>Status</th>
                                    <th style="width: 60px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $counter = 0;
                                $row_number = $offset + 1;
                                if ($gift_setups && !$gift_setups->EOF):
                                    while (!$gift_setups->EOF):
                                        $PK_GIFT_CERTIFICATE_SETUP = $gift_setups->fields['PK_GIFT_CERTIFICATE_SETUP'];
                                        $gift_name = $gift_setups->fields['GIFT_CERTIFICATE_NAME'];
                                        $gift_code = $gift_setups->fields['GIFT_CERTIFICATE_CODE'];
                                        $min_amount = $gift_setups->fields['MINIMUM_AMOUNT'];
                                        $max_amount = $gift_setups->fields['MAXIMUM_AMOUNT'];
                                        $effective_date = $gift_setups->fields['EFFECTIVE_DATE'];
                                        $end_date = $gift_setups->fields['END_DATE'];
                                        $is_active = $gift_setups->fields['ACTIVE'] == 1;

                                        $initials = getGiftSetupInitials($gift_name);
                                        $bg_color = getGiftSetupAvatarColor($counter);

                                        // Format dates
                                        $formatted_effective = !empty($effective_date) && $effective_date != '0000-00-00' ? date('M d, Y', strtotime($effective_date)) : '—';
                                        $formatted_end = !empty($end_date) && $end_date != '0000-00-00' ? date('M d, Y', strtotime($end_date)) : '—';

                                        // Check if expired
                                        $is_expired = (!empty($end_date) && $end_date != '0000-00-00' && strtotime($end_date) < time());
                                ?>
                                        <tr>
                                            <td class="text-muted small fw-medium"><?= $row_number++ ?></td>
                                            <td>
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="avatar-circle" style="background: linear-gradient(135deg, <?= $bg_color['start'] ?>, <?= $bg_color['end'] ?>);">
                                                        <i class="bi bi-gift-fill"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-semibold"><?= htmlspecialchars($gift_name) ?></div>
                                                        <div class="mt-1">
                                                            <span class="certificate-code">
                                                                <i class="bi bi-upc-scan me-1"></i> <?= htmlspecialchars($gift_code) ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="amount-range">
                                                    <i class="bi bi-currency-dollar"></i> <?= number_format($min_amount, 2) ?> - <?= number_format($max_amount, 2) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="date-badge">
                                                    <i class="bi bi-calendar-check"></i> <?= $formatted_effective ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div>
                                                    <span class="date-badge">
                                                        <i class="bi bi-calendar-x"></i> <?= $formatted_end ?>
                                                    </span>
                                                    <?php if ($is_expired && $is_active): ?>
                                                        <span class="warning-badge ms-2">
                                                            <i class="bi bi-exclamation-triangle-fill"></i> Expired
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($is_active): ?>
                                                    <span class="badge-status badge-active"><i class="bi bi-check-circle-fill"></i> Active</span>
                                                <?php else: ?>
                                                    <span class="badge-status badge-inactive"><i class="bi bi-x-circle-fill"></i> Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="action-icons">
                                                    <a href="javascript:;" onclick="editGiftSetup(<?= $PK_GIFT_CERTIFICATE_SETUP ?>);" title="Edit">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php
                                        $gift_setups->MoveNext();
                                        $counter++;
                                    endwhile;
                                endif;
                                if ($total_records == 0 && !empty($location_ids_for_sql)):
                                    ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-5">
                                            <i class="bi bi-gift display-1 text-muted"></i>
                                            <p class="mt-3 text-muted">No gift certificate templates found for the selected filters</p>
                                            <button class="btn btn-sm btn-outline-primary mt-2" onclick="createNewGiftSetup()">Create your first gift certificate</button>
                                        </td>
                                    </tr>
                                <?php elseif (empty($location_ids_for_sql)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-5">
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

        // Edit gift certificate setup
        function editGiftSetup(id) {
            window.location.href = "gift_certificate_setup.php?id=" + id;
        }

        // Create new gift certificate setup
        function createNewGiftSetup() {
            window.location.href = 'gift_certificate_setup.php';
        }
    </script>

    <?php
    // Helper functions
    function getGiftSetupInitials($name)
    {
        $words = explode(' ', trim($name));
        $initials = '';
        foreach ($words as $word) {
            if (!empty($word)) {
                $initials .= strtoupper($word[0]);
            }
            if (strlen($initials) >= 2) break;
        }
        return $initials ?: 'GC';
    }

    function getGiftSetupAvatarColor($index)
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