<!DOCTYPE html>
<html lang="en">
<?php
require_once('../global/config.php');
$title = "All Locations";

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

$header_text = '';
$header_data = $db->Execute("SELECT * FROM `DOA_HEADER_TEXT` WHERE ACTIVE = 1 AND HEADER_TITLE = 'Locations page'");
if ($header_data->RecordCount() > 0) {
    $header_text = $header_data->fields['HEADER_TEXT'];
}

// --- NEW: same style as Users page (search, pagination, status filter, modern UI) ---
$status_check = isset($_GET['status']) ? $_GET['status'] : 'active';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 8;

$status = ($status_check == 'active') ? 1 : 0;

// Build location IDs condition (similar to original: PK_LOCATION IN ($DEFAULT_LOCATION_ID))
$location_ids = trim($DEFAULT_LOCATION_ID, ',');
$location_ids = empty($location_ids) ? '0' : preg_replace('/,+/', ',', $location_ids);

// Active condition
$active_condition = ($status_check == 'active') ? "DOA_LOCATION.ACTIVE = 1" : "DOA_LOCATION.ACTIVE = 0";

$offset = ($page - 1) * $per_page;

// Count total records
$count_query = "SELECT COUNT(DISTINCT DOA_LOCATION.PK_LOCATION) as total 
                FROM DOA_LOCATION 
                LEFT JOIN DOA_CORPORATION ON DOA_LOCATION.PK_CORPORATION = DOA_CORPORATION.PK_CORPORATION 
                WHERE DOA_LOCATION.PK_LOCATION IN ($location_ids) 
                AND DOA_LOCATION.PK_ACCOUNT_MASTER = " . intval($_SESSION['PK_ACCOUNT_MASTER']) . " 
                AND $active_condition";

if (!empty($search)) {
    $count_query .= " AND (DOA_LOCATION.LOCATION_NAME LIKE '%" . addslashes($search) . "%' 
                      OR DOA_LOCATION.CITY LIKE '%" . addslashes($search) . "%' 
                      OR DOA_LOCATION.PHONE LIKE '%" . addslashes($search) . "%' 
                      OR DOA_CORPORATION.CORPORATION_NAME LIKE '%" . addslashes($search) . "%')";
}

$total_result = $db->Execute($count_query);
$total_records = $total_result->fields['total'];
$total_pages = ceil($total_records / $per_page);

// Get locations for current page
$query = "SELECT DISTINCT DOA_LOCATION.PK_LOCATION, DOA_LOCATION.LOCATION_NAME, 
          DOA_LOCATION.CITY, DOA_LOCATION.PHONE, DOA_LOCATION.EMAIL, DOA_LOCATION.ACTIVE,
          DOA_CORPORATION.CORPORATION_NAME 
          FROM DOA_LOCATION 
          LEFT JOIN DOA_CORPORATION ON DOA_LOCATION.PK_CORPORATION = DOA_CORPORATION.PK_CORPORATION 
          WHERE DOA_LOCATION.PK_LOCATION IN ($location_ids) 
          AND DOA_LOCATION.PK_ACCOUNT_MASTER = " . intval($_SESSION['PK_ACCOUNT_MASTER']) . " 
          AND $active_condition";

if (!empty($search)) {
    $query .= " AND (DOA_LOCATION.LOCATION_NAME LIKE '%" . addslashes($search) . "%' 
                OR DOA_LOCATION.CITY LIKE '%" . addslashes($search) . "%' 
                OR DOA_LOCATION.PHONE LIKE '%" . addslashes($search) . "%' 
                OR DOA_CORPORATION.CORPORATION_NAME LIKE '%" . addslashes($search) . "%')";
}

$query .= " ORDER BY DOA_LOCATION.LOCATION_NAME ASC LIMIT $offset, $per_page";
$locations = $db->Execute($query);
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
            <div class="col-12 col-md-4 col-xl-2">
                <?php include 'layout/setup_sidebar.php'; ?>
            </div>

            <!-- Main Content -->
            <div class="col-12 col-md-8 col-xl-10">
                <div class="main-card">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
                        <div>
                            <h2 class="fw-semibold h4 mb-1"><?= htmlspecialchars($title) ?></h2>
                            <p class="text-muted small mb-0">Manage locations, view corporation association, and control visibility</p>
                        </div>
                        <button class="btn btn-success-custom rounded-pill d-flex align-items-center gap-2" onclick="window.location.href='location.php'">
                            <i class="bi bi-plus-lg"></i> Create New Location
                        </button>
                    </div>

                    <!-- Filters (status & search) -->
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <div class="search-container">
                            <i class="bi bi-search"></i>
                            <input type="text" class="form-control search-input" placeholder="Search by name, city, corporation..." id="searchInput" value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="status-toggle-group">
                            <button class="status-btn <?= $status_check == 'active' ? 'active' : '' ?>" data-status="active">Active</button>
                            <button class="status-btn <?= $status_check == 'inactive' ? 'active' : '' ?>" data-status="inactive">Not Active</button>
                        </div>
                    </div>

                    <!-- Results count -->
                    <div class="text-muted small mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-geo-alt-fill"></i> <?= $total_records ?> <?= $total_records == 1 ? 'location' : 'locations' ?>
                    </div>

                    <!-- Locations Table (modern design, similar to users + corporation style) -->
                    <div class="table-responsive">
                        <table class="table custom-table align-middle mb-4">
                            <thead>
                                <tr>
                                    <th style="width: 40px;">#</th>
                                    <th>Location</th>
                                    <th style="text-align: center;">Corporation</th>
                                    <th style="text-align: center;">City</th>
                                    <th style="text-align: center;">Contact</th>
                                    <th style="text-align: center;">Status</th>
                                    <th style="width: 60px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $counter = 0;
                                $row_number = $offset + 1;
                                while (!$locations->EOF):
                                    $PK_LOCATION = $locations->fields['PK_LOCATION'];
                                    $loc_name = $locations->fields['LOCATION_NAME'];
                                    $corp_name = $locations->fields['CORPORATION_NAME'] ?? '—';
                                    $city = $locations->fields['CITY'] ?? '—';
                                    $phone = $locations->fields['PHONE'] ?? '—';
                                    $email = $locations->fields['EMAIL'] ?? '—';
                                    $is_active = $locations->fields['ACTIVE'] == 1;
                                    $initials = getLocationInitials($loc_name);
                                    $bg_color = getLocationAvatarColor($counter);
                                ?>
                                    <tr>
                                        <td class="text-muted small fw-medium"><?= $row_number++ ?></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <div>
                                                    <div class="fw-semibold"><?= htmlspecialchars($loc_name) ?></div>
                                                    <div class="location-detail">
                                                        <i class="bi bi-telephone-fill me-1"></i> <?= htmlspecialchars($phone) ?>
                                                        <?php if ($email != '—'): ?>
                                                            <span class="mx-1">•</span> <i class="bi bi-envelope-fill me-1"></i> <?= htmlspecialchars($email) ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="info-chip">
                                                <i class="bi bi-building me-1"></i> <?= htmlspecialchars($corp_name) ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <i class="bi bi-pin-map-fill text-secondary me-1"></i> <?= htmlspecialchars($city) ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($phone != '—'): ?>
                                                <div><i class="bi bi-telephone"></i> <?= htmlspecialchars($phone) ?></div>
                                            <?php else: ?>
                                                <span class="text-muted small">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($is_active): ?>
                                                <span class="badge-status badge-active"><i class="bi bi-check-circle-fill"></i> Active</span>
                                            <?php else: ?>
                                                <span class="badge-status badge-inactive"><i class="bi bi-x-circle-fill"></i> Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <i class="bi bi-three-dots-vertical text-muted cursor-pointer" data-bs-toggle="dropdown" style="cursor: pointer;"></i>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item" href="location.php?id=<?= $PK_LOCATION ?>"><i class="bi bi-pencil me-2"></i> Edit</a></li>
                                                    <li><a class="dropdown-item" href="location.php?id=<?= $PK_LOCATION ?>&view=1"><i class="bi bi-eye me-2"></i> View</a></li>
                                                    <li>
                                                        <hr class="dropdown-divider">
                                                    </li>
                                                    <li><a class="dropdown-item text-danger" href="#" onclick="confirmDeleteLocation(<?= $PK_LOCATION ?>)"><i class="bi bi-trash me-2"></i> Delete</a></li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                <?php
                                    $locations->MoveNext();
                                    $counter++;
                                endwhile;
                                if ($total_records == 0):
                                ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-5">
                                            <i class="bi bi-geo-alt display-1 text-muted"></i>
                                            <p class="mt-3 text-muted">No locations found for the selected filters</p>
                                            <!-- <button class="btn btn-sm btn-outline-primary mt-2" onclick="window.location.href='location.php'">Create your first location</button> -->
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
                                <select class="form-select form-select-sm page-select py-2 px-3" id="perPageSelect">
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
        // Helper for avatar initials
        function getLocationInitials(name) {
            if (!name) return "LC";
            let parts = name.split(' ');
            let initials = '';
            for (let i = 0; i < parts.length && initials.length < 2; i++) {
                if (parts[i].length) initials += parts[i][0].toUpperCase();
            }
            return initials || "LOC";
        }

        // Search with debounce (as per users page)
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

        // Status toggle buttons (active/inactive)
        $('.status-btn').on('click', function() {
            let newStatus = $(this).data('status');
            if (newStatus) {
                window.location.href = '?status=' + newStatus + '&search=<?= urlencode($search) ?>&per_page=<?= $per_page ?>';
            }
        });

        // Delete location via AJAX (same pattern as original ConfirmDelete)
        function confirmDeleteLocation(pk_location) {
            if (confirm("Are you sure you want to delete this location? This may affect linked data.")) {
                $.ajax({
                    url: "ajax/AjaxFunctions.php",
                    type: 'POST',
                    data: {
                        FUNCTION_NAME: 'deleteLocationData',
                        PK_LOCATION: pk_location
                    },
                    success: function(data) {
                        // Reload with current filters
                        window.location.href = `all_locations.php?status=<?= $status_check ?>&search=<?= urlencode($search) ?>&per_page=<?= $per_page ?>`;
                    },
                    error: function() {
                        alert("Error deleting location. Please try again.");
                    }
                });
            }
        }

        // Edit function (compatibility)
        function editpage(id) {
            window.location.href = "location.php?id=" + id;
        }
    </script>

    <?php
    // Helper functions for avatar colors and initials (PHP side for initial render consistency)
    function getLocationInitials($name)
    {
        $words = explode(' ', trim($name));
        $initials = '';
        foreach ($words as $word) {
            if (!empty($word)) {
                $initials .= strtoupper($word[0]);
            }
            if (strlen($initials) >= 2) break;
        }
        return $initials ?: 'LC';
    }

    function getLocationAvatarColor($index)
    {
        $colors = ['#fef08a', '#fed7aa', '#bfdbfe', '#ddd6fe', '#fbcfe8', '#bbf7d0', '#c4b5fd', '#fdba74', '#a7f3d0', '#fecaca'];
        return $colors[$index % count($colors)];
    }
    ?>
</body>

</html>