<!DOCTYPE html>
<html lang="en">
<?php
require_once('../global/config.php');
$title = "All Corporations";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

// Fetch header text (optional note)
$header_text = '';
$header_data = $db->Execute("SELECT * FROM `DOA_HEADER_TEXT` WHERE ACTIVE = 1 AND HEADER_TITLE = 'Corporations page'");
if ($header_data->RecordCount() > 0) {
    $header_text = $header_data->fields['HEADER_TEXT'];
}

// --- START: same style as Users page (search, pagination, status filter, modern UI) ---
$status_check = isset($_GET['status']) ? $_GET['status'] : 'active';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 8;

$status = ($status_check == 'active') ? 1 : 0;

// base conditions: account master, optionally active flag, soft delete check (if applicable, similar to users)
// Since original corporations table may not have IS_DELETED, we adapt: active = 1/0 based on status
$active_condition = "ACTIVE = '$status'";
if ($status_check == 'active') {
    $active_condition = "ACTIVE = 1";
} else {
    $active_condition = "ACTIVE = 0";
}

$offset = ($page - 1) * $per_page;

// count query
$count_query = "SELECT COUNT(*) as total FROM DOA_CORPORATION WHERE PK_ACCOUNT_MASTER = " . intval($_SESSION['PK_ACCOUNT_MASTER']) . " AND $active_condition";
if (!empty($search)) {
    $count_query .= " AND CORPORATION_NAME LIKE '%" . addslashes($search) . "%'";
}
$total_result = $db->Execute($count_query);
$total_records = $total_result->fields['total'];
$total_pages = ceil($total_records / $per_page);

// data query
$query = "SELECT PK_CORPORATION, CORPORATION_NAME, ACTIVE FROM DOA_CORPORATION WHERE PK_ACCOUNT_MASTER = " . intval($_SESSION['PK_ACCOUNT_MASTER']) . " AND $active_condition";
if (!empty($search)) {
    $query .= " AND CORPORATION_NAME LIKE '%" . addslashes($search) . "%'";
}
$query .= " ORDER BY CORPORATION_NAME ASC LIMIT $offset, $per_page";
$corporations = $db->Execute($query);
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
</head>

<body>

    <div class="container-fluid py-4 px-4 m-auto mx-auto dashboard-container">
        <div class="row g-4">
            <!-- Sidebar (same as users page) -->
            <div class="col-12 col-md-4 col-xl-3">
                <?php include 'layout/setup_sidebar.php'; ?>
            </div>

            <!-- Main Content -->
            <div class="col-12 col-md-8 col-xl-9">
                <div class="main-card">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
                        <div>
                            <h2 class="fw-semibold h4 mb-1"><?= htmlspecialchars($title) ?></h2>
                            <p class="text-muted small mb-0">Manage all corporations and their status</p>
                            <?php if (!empty($header_text)): ?>
                                <div class="mt-2 alert alert-light py-2 px-3 small bg-light rounded-3">
                                    <i class="bi bi-info-circle-fill me-1 text-info"></i> <?= htmlspecialchars($header_text) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <button class="btn btn-success-custom rounded-pill d-flex align-items-center gap-2" onclick="window.location.href='corporation.php'">
                            <i class="bi bi-plus-lg"></i> Create New Corporation
                        </button>
                    </div>

                    <!-- Filters (status & search) same as Users page -->
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <div class="search-container">
                            <i class="bi bi-search"></i>
                            <input type="text" class="form-control search-input" placeholder="Search corporations..." id="searchInput" value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="status-toggle-group">
                            <button class="status-btn <?= $status_check == 'active' ? 'active' : '' ?>" data-status="active">Active</button>
                            <button class="status-btn <?= $status_check == 'inactive' ? 'active' : '' ?>" data-status="inactive">Not Active</button>
                        </div>
                    </div>

                    <!-- Results count -->
                    <div class="text-muted small mb-3"><?= $total_records ?> <?= $total_records == 1 ? 'corporation' : 'corporations' ?></div>

                    <!-- Corporations Table (modern, similar to users) -->
                    <div class="table-responsive">
                        <table class="table custom-table align-middle mb-4">
                            <thead>
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th>Corporation Name</th>
                                    <th>Status</th>
                                    <th style="width: 80px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $counter = 0;
                                $row_number = $offset + 1;
                                while (!$corporations->EOF):
                                    $PK_CORPORATION = $corporations->fields['PK_CORPORATION'];
                                    $corp_name = $corporations->fields['CORPORATION_NAME'];
                                    $is_active = $corporations->fields['ACTIVE'] == 1;
                                    $initials = getInitialsCorp($corp_name);
                                    $bg_color = getAvatarColorCorp($counter);
                                ?>
                                    <tr>
                                        <td class="text-muted small fw-medium"><?= $row_number++ ?></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <div>
                                                    <div class="fw-semibold"><?= $corp_name ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($is_active): ?>
                                                <span class="badge-status badge-active"><i class="bi bi-check-circle-fill me-1"></i> Active</span>
                                            <?php else: ?>
                                                <span class="badge-status badge-inactive"><i class="bi bi-x-circle-fill me-1"></i> Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <i class="bi bi-three-dots-vertical text-muted cursor-pointer" data-bs-toggle="dropdown" style="cursor: pointer;"></i>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item" href="corporation.php?id=<?= $PK_CORPORATION ?>"><i class="bi bi-pencil me-2"></i> Edit</a></li>
                                                    <li><a class="dropdown-item" href="corporation.php?id=<?= $PK_CORPORATION ?>&view=1"><i class="bi bi-eye me-2"></i> View</a></li>
                                                    <li>
                                                        <hr class="dropdown-divider">
                                                    </li>
                                                    <li><a class="dropdown-item text-danger" href="#" onclick="confirmDeleteCorporation(<?= $PK_CORPORATION ?>)"><i class="bi bi-trash me-2"></i> Delete</a></li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                <?php
                                    $corporations->MoveNext();
                                    $counter++;
                                endwhile;
                                if ($total_records == 0):
                                ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-5">
                                            <i class="bi bi-building display-1 text-muted"></i>
                                            <p class="mt-3 text-muted">No corporations found</p>
                                            <!-- <button class="btn btn-sm btn-outline-primary mt-2" onclick="window.location.href='corporation.php'">Create your first corporation</button> -->
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination (exactly as Users page) -->
                    <?php if ($total_pages > 1): ?>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        // Helper functions for avatar
        function getInitialsCorp(name) {
            if (!name) return "C";
            let parts = name.split(' ');
            let initials = '';
            for (let i = 0; i < parts.length && initials.length < 2; i++) {
                if (parts[i].length) initials += parts[i][0].toUpperCase();
            }
            return initials || "CO";
        }

        // search, per page, status toggle: replicate users page behavior
        let searchTimeout;
        $('#searchInput').on('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                let searchVal = encodeURIComponent($(this).val());
                window.location.href = '?status=<?= $status_check ?>&search=' + searchVal + '&per_page=<?= $per_page ?>';
            }, 500);
        });

        $('#perPageSelect').on('change', function() {
            window.location.href = '?status=<?= $status_check ?>&search=<?= urlencode($search) ?>&per_page=' + $(this).val();
        });

        // status toggle buttons (active/inactive) - same style as users
        $('.status-btn').on('click', function() {
            let newStatus = $(this).data('status');
            if (newStatus) {
                window.location.href = '?status=' + newStatus + '&search=<?= urlencode($search) ?>&per_page=<?= $per_page ?>';
            }
        });

        // Delete via AJAX (similar to original ConfirmDelete but modern)
        function confirmDeleteCorporation(pk_corporation) {
            if (confirm("Are you sure you want to delete this corporation? This action may affect linked data.")) {
                $.ajax({
                    url: "ajax/AjaxFunctions.php",
                    type: 'POST',
                    data: {
                        FUNCTION_NAME: 'deleteCorporationData',
                        PK_CORPORATION: pk_corporation
                    },
                    success: function(data) {
                        // Refresh page or redirect to reflect deletion
                        window.location.href = `all_corporations.php?status=<?= $status_check ?>&search=<?= urlencode($search) ?>&per_page=<?= $per_page ?>`;
                    },
                    error: function() {
                        alert("Error deleting corporation. Please try again.");
                    }
                });
            }
        }

        // edit via inline click on row (optional double click or stay consistent with original)
        function editCorporation(id) {
            window.location.href = "corporation.php?id=" + id;
        }
        // keep original editpage if needed (compatibility)
        function editpage(id) {
            window.location.href = "corporation.php?id=" + id;
        }
    </script>

    <?php
    // helper functions for avatar palette
    function getInitialsCorp($name)
    {
        $words = explode(' ', trim($name));
        $initials = '';
        foreach ($words as $word) {
            if (!empty($word)) {
                $initials .= strtoupper($word[0]);
            }
            if (strlen($initials) >= 2) break;
        }
        return $initials ?: 'C';
    }

    function getAvatarColorCorp($index)
    {
        $colors = ['#fef08a', '#fed7aa', '#bfdbfe', '#ddd6fe', '#fbcfe8', '#bbf7d0', '#c4b5fd', '#fdba74'];
        return $colors[$index % count($colors)];
    }
    ?>
</body>

</html>