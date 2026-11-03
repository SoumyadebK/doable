<?php
require_once('../global/config.php');
$title = "All Tags";

$status_check = empty($_GET['status']) ? 'active' : $_GET['status'];

if ($status_check == 'active') {
    $status = 1;
} elseif ($status_check == 'inactive') {
    $status = 0;
}

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '') {
    header("location:../login.php");
    exit;
}

$results_per_page = 10;

if (isset($_GET['search_text'])) {
    $search_text = $_GET['search_text'];
    $search = " AND (DOA_TAG.TAG_NAME LIKE '%" . $search_text . "%')";
} else {
    $search_text = '';
    $search = ' ';
}

$query = $db_account->Execute("SELECT count(DOA_TAG.PK_TAG) AS TOTAL_RECORDS FROM DOA_TAG WHERE DOA_TAG.ACTIVE=1 " . $search);
$number_of_result = $query->fields['TOTAL_RECORDS'];
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
    <title><?= htmlspecialchars($title) ?> - Setup Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/setup-styles.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --primary-color: #39B54A;
            --primary-dark: #2D8F3B;
            --primary-rgb: 57, 181, 74;
            --success-color: #39B54A;
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
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
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

        /* Main Card */
        .main-card {
            background: #ffffff;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-200);
            overflow: hidden;
            transition: box-shadow 0.2s ease;
        }

        .main-card:hover {
            box-shadow: var(--shadow-md);
        }

        /* Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 24px;
        }

        .page-header h2 {
            font-size: 24px;
            font-weight: 700;
            color: var(--gray-900);
            margin: 0;
            letter-spacing: -0.025em;
        }

        .page-header h2 i {
            color: var(--primary-color);
            margin-right: 10px;
        }

        .page-header .subtitle {
            color: var(--gray-500);
            font-size: 14px;
            margin-bottom: 0;
        }

        /* Search & Filters */
        .filters-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 20px;
        }

        .search-container {
            position: relative;
            display: flex;
            align-items: center;
            flex: 1;
            max-width: 380px;
        }

        .search-container .bi-search {
            position: absolute;
            left: 12px;
            color: var(--gray-400);
        }

        .search-container .search-input {
            padding: 10px 14px 10px 38px;
            border: 1.5px solid var(--gray-200);
            border-radius: var(--radius-pill);
            font-size: 14px;
            width: 100%;
            outline: none;
            transition: all 0.2s ease;
            background: #fff;
            color: var(--gray-800);
        }

        .search-container .search-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1);
        }

        .search-container .search-input::placeholder {
            color: var(--gray-400);
        }

        .status-toggle-group {
            display: flex;
            gap: 6px;
            background: var(--gray-100);
            padding: 4px;
            border-radius: var(--radius-pill);
        }

        .status-toggle-group .status-btn {
            padding: 6px 18px;
            border: none;
            border-radius: var(--radius-pill);
            font-size: 13px;
            font-weight: 500;
            color: var(--gray-600);
            background: transparent;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .status-toggle-group .status-btn:hover {
            color: var(--gray-800);
        }

        .status-toggle-group .status-btn.active {
            background: #fff;
            color: var(--primary-color);
            box-shadow: var(--shadow-sm);
        }

        .results-count {
            color: var(--gray-500);
            font-size: 14px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .results-count i {
            color: var(--primary-color);
        }

        /* Table */
        .custom-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }



        .custom-table tbody td {
            padding: 12px 16px;
            border-bottom: 1px solid var(--gray-100);
            color: var(--gray-700);
            vertical-align: middle;
        }

        .custom-table tbody tr {
            transition: background 0.15s ease;
        }

        .custom-table tbody tr:hover {
            background: var(--gray-50);
        }

        .custom-table tbody tr:last-child td {
            border-bottom: none;
        }

        .custom-table .status-badge {
            display: inline-block;
            padding: 4px 14px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 500;
            white-space: nowrap;
        }

        .custom-table .status-badge.active {
            background: #D1FAE5;
            color: #065F46;
        }

        .custom-table .status-badge.inactive {
            background: #FEE2E2;
            color: #991B1B;
        }

        .custom-table .action-icons {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .custom-table .action-icons a {
            color: var(--gray-500);
            transition: color 0.2s;
            text-decoration: none;
            font-size: 18px;
        }

        .custom-table .action-icons a:hover {
            color: var(--primary-color);
        }

        .custom-table .status-dot {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }

        .custom-table .status-dot.active {
            background: var(--success-color);
        }

        .custom-table .status-dot.inactive {
            background: var(--danger-color);
        }

        /* Empty State */
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

        /* Pagination */
        .pagination-modern {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid var(--gray-200);
        }

        .pagination-modern .pagination-info {
            color: var(--gray-500);
            font-size: 14px;
        }

        .pagination-modern .pagination {
            display: flex;
            gap: 4px;
            align-items: center;
            margin: 0;
        }

        .pagination-modern .pagination .page-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 36px;
            height: 36px;
            padding: 0 12px;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-pill) !important;
            color: var(--gray-600);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
            background: #fff;
        }

        .pagination-modern .pagination .page-link:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
            background: #F0FDF4;
        }

        .pagination-modern .pagination .page-item.active .page-link {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: #fff;
        }

        .pagination-modern .pagination .page-item.active .page-link:hover {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
        }

        .pagination-modern .pagination .page-item.disabled .page-link {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .pagination-modern .pagination .page-item .page-link.bg-transparent {
            background: transparent !important;
            border: none !important;
        }

        .page-select {
            border: 1.5px solid var(--gray-200);
            border-radius: var(--radius-pill);
            padding: 6px 16px;
            font-size: 14px;
            color: var(--gray-700);
            background: #fff;
            outline: none;
            transition: all 0.2s ease;
        }

        .page-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1);
        }

        /* Responsive */
        @media (max-width: 768px) {

            .page-header {
                flex-direction: column;
                align-items: stretch;
            }

            .filters-bar {
                flex-direction: column;
                align-items: stretch;
            }

            .search-container {
                max-width: 100%;
            }

            .status-toggle-group {
                align-self: flex-start;
            }

            .pagination-modern {
                flex-direction: column;
                align-items: center;
            }


        }

        @media (max-width: 992px) {
            .col-xl-2 {
                display: none;
            }

            .col-xl-10 {
                width: 100%;
            }
        }
    </style>
</head>

<body class="skin-default-dark fixed-layout">

    <div id="main-wrapper">
        <?php require_once('../includes/header.php'); ?>

        <div class="page-wrapper" style="padding-top: 0px !important;">
            <div class="container-fluid py-4 px-4 m-auto mx-auto dashboard-container">
                <div class="row g-4">
                    <!-- Sidebar -->
                    <div class="col-12 col-md-4 col-xl-2">
                        <?php include 'layout/setup_sidebar.php'; ?>
                    </div>

                    <!-- Main Content -->
                    <div class="col-12 col-md-8 col-xl-10">
                        <div class="main-card">
                            <div class="card-body">

                                <!-- Header -->
                                <div class="page-header">
                                    <div>
                                        <h2>
                                            <i class="bi bi-tags"></i> Tags
                                        </h2>
                                        <p class="subtitle">Manage tags for categorizing data</p>
                                    </div>
                                    <div>
                                        <button class="btn btn-success-custom rounded-pill d-flex align-items-center gap-2" onclick="window.location.href='add_tag.php'">
                                            <i class="bi bi-plus-lg"></i> Create New Tag
                                        </button>
                                    </div>
                                </div>

                                <!-- Filters -->
                                <div class="filters-bar">
                                    <div class="search-container">
                                        <i class="bi bi-search"></i>
                                        <input type="text" class="search-input" placeholder="Search tags..." id="searchInput" value="<?= htmlspecialchars($search_text) ?>">
                                    </div>
                                    <div class="status-toggle-group">
                                        <button class="status-btn <?= $status_check == 'active' ? 'active' : '' ?>" data-status="active">Active</button>
                                        <button class="status-btn <?= $status_check == 'inactive' ? 'active' : '' ?>" data-status="inactive">Not Active</button>
                                    </div>
                                </div>

                                <!-- Results count -->
                                <div class="results-count">
                                    <i class="bi bi-tags"></i> <?= $number_of_result ?> <?= $number_of_result == 1 ? 'tag' : 'tags' ?>
                                </div>

                                <!-- Table -->
                                <div class="table-responsive">
                                    <table class="custom-table align-middle mb-4">
                                        <thead>
                                            <tr>
                                                <th style="width: 50px;">#</th>
                                                <th>Tag Name</th>
                                                <th style="text-align: center; width: 140px;">Status</th>
                                                <th style="width: 80px;">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $row = $db_account->Execute("SELECT * FROM `DOA_TAG` WHERE DOA_TAG.ACTIVE=1 " . $search . " LIMIT " . $page_first_result . ',' . $results_per_page);
                                            $counter = $page_first_result + 1;

                                            if ($row->RecordCount() > 0):
                                                while (!$row->EOF):
                                                    $PK_TAG = $row->fields['PK_TAG'];
                                                    $TAG_NAME = $row->fields['TAG_NAME'];
                                                    $is_active = $row->fields['ACTIVE'] == 1;
                                            ?>
                                                    <tr>
                                                        <td class="text-muted small fw-medium"><?= $counter++ ?></td>
                                                        <td>
                                                            <div class="fw-semibold">
                                                                <?= htmlspecialchars($TAG_NAME) ?>
                                                            </div>
                                                        </td>
                                                        <td class="text-center">
                                                            <?php if ($is_active): ?>
                                                                <span class="status-badge active"><i class="bi bi-check-circle-fill"></i> Active</span>
                                                            <?php else: ?>
                                                                <span class="status-badge inactive"><i class="bi bi-x-circle-fill"></i> Inactive</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <div class="action-icons">
                                                                <a href="add_tag.php?id=<?= $PK_TAG ?>" title="Edit">
                                                                    <i class="bi bi-pencil-square"></i>
                                                                </a>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php
                                                    $row->MoveNext();
                                                endwhile;
                                            else: ?>
                                                <tr>
                                                    <td colspan="4" class="text-center py-5">
                                                        <div class="empty-state">
                                                            <i class="bi bi-tags"></i>
                                                            <h5>No Tags Found</h5>
                                                            <p>Start by creating your first tag.</p>
                                                            <button class="btn btn-success-custom rounded-pill d-flex align-items-center gap-2 mx-auto" onclick="window.location.href='add_tag.php'">
                                                                <i class="bi bi-plus-lg"></i> Create New Tag
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Pagination -->
                                <?php if ($number_of_page > 1): ?>
                                    <div class="pagination-modern">
                                        <div class="pagination-info">
                                            Page <?= $page ?> of <?= $number_of_page ?>
                                        </div>
                                        <nav>
                                            <ul class="pagination">
                                                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                                    <a class="page-link" href="?page=1&status=<?= $status_check ?>&search_text=<?= urlencode($search_text) ?>" aria-label="First">
                                                        <i class="bi bi-chevron-double-left"></i>
                                                    </a>
                                                </li>
                                                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                                    <a class="page-link" href="?page=<?= $page - 1 ?>&status=<?= $status_check ?>&search_text=<?= urlencode($search_text) ?>" aria-label="Previous">
                                                        <i class="bi bi-chevron-left"></i>
                                                    </a>
                                                </li>
                                                <?php
                                                $start_page = max(1, $page - 2);
                                                $end_page = min($number_of_page, $page + 2);
                                                if ($start_page > 1): ?>
                                                    <li class="page-item"><a class="page-link" href="?page=1&status=<?= $status_check ?>&search_text=<?= urlencode($search_text) ?>">1</a></li>
                                                    <?php if ($start_page > 2): ?>
                                                        <li class="page-item disabled"><span class="page-link bg-transparent">...</span></li>
                                                    <?php endif; ?>
                                                <?php endif;
                                                for ($i = $start_page; $i <= $end_page; $i++): ?>
                                                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                                        <a class="page-link" href="?page=<?= $i ?>&status=<?= $status_check ?>&search_text=<?= urlencode($search_text) ?>"><?= $i ?></a>
                                                    </li>
                                                <?php endfor;
                                                if ($end_page < $number_of_page): ?>
                                                    <?php if ($end_page < $number_of_page - 1): ?>
                                                        <li class="page-item disabled"><span class="page-link bg-transparent">...</span></li>
                                                    <?php endif; ?>
                                                    <li class="page-item"><a class="page-link" href="?page=<?= $number_of_page ?>&status=<?= $status_check ?>&search_text=<?= urlencode($search_text) ?>"><?= $number_of_page ?></a></li>
                                                <?php endif; ?>
                                                <li class="page-item <?= $page >= $number_of_page ? 'disabled' : '' ?>">
                                                    <a class="page-link" href="?page=<?= $page + 1 ?>&status=<?= $status_check ?>&search_text=<?= urlencode($search_text) ?>" aria-label="Next">
                                                        <i class="bi bi-chevron-right"></i>
                                                    </a>
                                                </li>
                                                <li class="page-item <?= $page >= $number_of_page ? 'disabled' : '' ?>">
                                                    <a class="page-link" href="?page=<?= $number_of_page ?>&status=<?= $status_check ?>&search_text=<?= urlencode($search_text) ?>" aria-label="Last">
                                                        <i class="bi bi-chevron-double-right"></i>
                                                    </a>
                                                </li>
                                            </ul>
                                        </nav>
                                        <div>
                                            <select class="page-select" id="perPageSelect">
                                                <option value="10" <?= $results_per_page == 10 ? 'selected' : '' ?>>10 / page</option>
                                                <option value="25" <?= $results_per_page == 25 ? 'selected' : '' ?>>25 / page</option>
                                                <option value="50" <?= $results_per_page == 50 ? 'selected' : '' ?>>50 / page</option>
                                                <option value="100" <?= $results_per_page == 100 ? 'selected' : '' ?>>100 / page</option>
                                            </select>
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

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        // Search with debounce
        let searchTimeout;
        $('#searchInput').on('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                let searchVal = encodeURIComponent($(this).val());
                window.location.href = '?status=<?= $status_check ?>&search_text=' + searchVal + '&per_page=<?= $results_per_page ?>';
            }, 500);
        });

        // Per page change
        $('#perPageSelect').on('change', function() {
            window.location.href = '?status=<?= $status_check ?>&search_text=<?= urlencode($search_text) ?>&per_page=' + $(this).val();
        });

        // Status toggle buttons
        $('.status-btn').on('click', function() {
            let newStatus = $(this).data('status');
            if (newStatus) {
                window.location.href = '?status=' + newStatus + '&search_text=<?= urlencode($search_text) ?>&per_page=<?= $results_per_page ?>';
            }
        });

        // Edit function
        function editpage(id) {
            window.location.href = "add_tag.php?id=" + id;
        }
    </script>
</body>

</html>