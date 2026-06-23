<!DOCTYPE html>
<html lang="en">
<?php
require_once('../global/config.php');
$title = "All Scheduling Codes";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];

// Get header text for Scheduling Codes page
$header_text = '';
$header_data = $db->Execute("SELECT * FROM `DOA_HEADER_TEXT` WHERE ACTIVE = 1 AND HEADER_TITLE = 'Scheduling Codes page'");
if ($header_data && $header_data->RecordCount() > 0) {
    $header_text = $header_data->fields['HEADER_TEXT'];
}

// --- Modern UI: search, pagination, status filter, same style as Users page ---
$status_check = isset($_GET['status']) ? $_GET['status'] : 'active';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 8;

$status = ($status_check == 'active') ? 1 : 0;

// Location IDs condition (from session, similar to original)
$location_ids = trim($DEFAULT_LOCATION_ID, ',');
$location_ids = empty($location_ids) ? '0' : preg_replace('/,+/', ',', $location_ids);

// Build active condition
$active_condition = ($status_check == 'active') ? "DOA_SCHEDULING_CODE.ACTIVE = 1" : "DOA_SCHEDULING_CODE.ACTIVE = 0";

$offset = ($page - 1) * $per_page;

// Count total records
$count_query = "SELECT COUNT(DISTINCT DOA_SCHEDULING_CODE.PK_SCHEDULING_CODE) as total 
                FROM DOA_SCHEDULING_CODE 
                LEFT JOIN $master_database.DOA_LOCATION AS DOA_LOCATION ON DOA_SCHEDULING_CODE.PK_LOCATION = DOA_LOCATION.PK_LOCATION 
                WHERE DOA_SCHEDULING_CODE.PK_LOCATION IN ($location_ids) 
                AND DOA_SCHEDULING_CODE.PK_ACCOUNT_MASTER = " . intval($_SESSION['PK_ACCOUNT_MASTER']) . " 
                AND $active_condition";

if (!empty($search)) {
    $count_query .= " AND (DOA_SCHEDULING_CODE.SCHEDULING_CODE LIKE '%" . addslashes($search) . "%' 
                      OR DOA_SCHEDULING_CODE.SCHEDULING_NAME LIKE '%" . addslashes($search) . "%' 
                      OR DOA_LOCATION.LOCATION_NAME LIKE '%" . addslashes($search) . "%')";
}

$total_result = $db_account->Execute($count_query);
$total_records = $total_result->fields['total'];
$total_pages = ceil($total_records / $per_page);

// Get scheduling codes for current page
$query = "SELECT DISTINCT DOA_SCHEDULING_CODE.PK_SCHEDULING_CODE, 
          DOA_SCHEDULING_CODE.SCHEDULING_CODE, 
          DOA_SCHEDULING_CODE.SCHEDULING_NAME, 
          DOA_SCHEDULING_CODE.COLOR_CODE, 
          DOA_SCHEDULING_CODE.DURATION, 
          DOA_SCHEDULING_CODE.UNIT, 
          DOA_SCHEDULING_CODE.SORT_ORDER,
          DOA_SCHEDULING_CODE.TO_DOS,
          DOA_SCHEDULING_CODE.ACTIVE,
          DOA_LOCATION.LOCATION_NAME
          FROM DOA_SCHEDULING_CODE 
          LEFT JOIN $master_database.DOA_LOCATION AS DOA_LOCATION ON DOA_SCHEDULING_CODE.PK_LOCATION = DOA_LOCATION.PK_LOCATION 
          WHERE DOA_SCHEDULING_CODE.PK_LOCATION IN ($location_ids) 
          AND DOA_SCHEDULING_CODE.PK_ACCOUNT_MASTER = " . intval($_SESSION['PK_ACCOUNT_MASTER']) . " 
          AND $active_condition";

if (!empty($search)) {
    $query .= " AND (DOA_SCHEDULING_CODE.SCHEDULING_CODE LIKE '%" . addslashes($search) . "%' 
                OR DOA_SCHEDULING_CODE.SCHEDULING_NAME LIKE '%" . addslashes($search) . "%' 
                OR DOA_LOCATION.LOCATION_NAME LIKE '%" . addslashes($search) . "%')";
}

$query .= " ORDER BY DOA_SCHEDULING_CODE.SORT_ORDER ASC, DOA_SCHEDULING_CODE.SCHEDULING_CODE ASC LIMIT $offset, $per_page";
$scheduling_codes = $db_account->Execute($query);
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="assets/css/setup-styles.css" rel="stylesheet">
    <style>
        /* Color swatch + name styles */
        .color-swatch {
            width: 5px;
            height: 5px;
            border-radius: 20px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.15);
            transition: transform 0.1s ease;
        }

        .color-name {
            font-size: 0.75rem;
            font-family: 'Courier New', monospace;
            font-weight: 500;
            background: #f8f9fa;
            padding: 2px 8px;
            border-radius: 30px;
            letter-spacing: 0.3px;
        }

        /* Code name in colored box */
        .code-color-box {
            display: inline-block;
            padding: 4px 14px;
            border-radius: 40px;
            font-weight: 700;
            font-size: 0.85rem;
            font-family: 'Inter', monospace;
            background-color: var(--code-bg, #eef2ff);
            color: var(--code-text, #1e293b);
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            transition: all 0.1s;
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

        .badge-todo {
            background: #e0e7ff;
            color: #3730a3;
            font-size: 0.7rem;
            padding: 2px 8px;
            border-radius: 30px;
        }

        .duration-chip {
            background: #f1f5f9;
            padding: 4px 10px;
            border-radius: 30px;
            font-size: 0.7rem;
            font-weight: 500;
            color: #334155;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .cursor-pointer {
            cursor: pointer;
        }

        .scheduling-code-badge {
            font-family: monospace;
            background: #f1f5f9;
            padding: 2px 10px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.8rem;
        }

        .dropdown-item i {
            width: 1.2rem;
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

            .custom-table {
                font-size: 0.8rem;
            }
        }

        .header-note {
            background: #f0f9ff;
            border-left: 3px solid #0d6efd;
        }

        .location-badge {
            background: #f8f9fa;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
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
                                <i class="bi bi-box-arrow-up-right me-2" style="color: #39b54a;"></i>Scheduling Codes
                            </h2>
                            <p class="text-muted small mb-0">Manage scheduling codes, durations, and visual settings</p>
                        </div>
                        <button class="btn btn-success-custom d-flex rounded-pill align-items-center gap-2" onclick="window.location.href='add_scheduling_codes.php'">
                            <i class="bi bi-plus-lg"></i> Create Scheduling Code
                        </button>
                    </div>

                    <!-- Filters (status & search) -->
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <div class="search-container">
                            <i class="bi bi-search"></i>
                            <input type="text" class="form-control search-input" placeholder="Search by code, name, location..." id="searchInput" value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="status-toggle-group">
                            <button class="status-btn <?= $status_check == 'active' ? 'active' : '' ?>" data-status="active">Active</button>
                            <button class="status-btn <?= $status_check == 'inactive' ? 'active' : '' ?>" data-status="inactive">Not Active</button>
                        </div>
                    </div>

                    <!-- Results count -->
                    <div class="text-muted small mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-tag-fill"></i> <?= $total_records ?> <?= $total_records == 1 ? (($status_check == 'active') ? 'active scheduling code' : 'inactive scheduling code') : (($status_check == 'active') ? 'active scheduling codes' : 'inactive scheduling codes') ?>
                    </div>

                    <!-- Scheduling Codes Table with enhanced color display -->
                    <div class="table-responsive">
                        <table class="table custom-table align-middle mb-4">
                            <thead>
                                <tr>
                                    <th style="width: 40px;">#</th>
                                    <th style="text-align: center;">Code</th>
                                    <th style="text-align: center;">Name</th>
                                    <th style="text-align: center;">Location</th>
                                    <th style="text-align: center;">Duration</th>
                                    <th style="text-align: center;">Color</th>
                                    <th style="text-align: center;">To Dos</th>
                                    <th style="text-align: center;">Sort Order</th>
                                    <th style="text-align: center;">Status</th>
                                    <th style="width: 60px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $counter = 0;
                                $row_number = $offset + 1;
                                if ($scheduling_codes && !$scheduling_codes->EOF):
                                    while (!$scheduling_codes->EOF):
                                        $PK_SCHEDULING_CODE = $scheduling_codes->fields['PK_SCHEDULING_CODE'];
                                        $code = $scheduling_codes->fields['SCHEDULING_CODE'];
                                        $name = $scheduling_codes->fields['SCHEDULING_NAME'];
                                        $location_name = $scheduling_codes->fields['LOCATION_NAME'] ?? '—';
                                        $color_code = $scheduling_codes->fields['COLOR_CODE'] ?? '#6c757d';
                                        $duration = $scheduling_codes->fields['DURATION'] ?? '—';
                                        $unit = $scheduling_codes->fields['UNIT'] ?? '';
                                        $sort_order = $scheduling_codes->fields['SORT_ORDER'] ?? '—';
                                        $to_dos = isset($scheduling_codes->fields['TO_DOS']) && $scheduling_codes->fields['TO_DOS'] == 1;
                                        $is_active = isset($scheduling_codes->fields['ACTIVE']) && $scheduling_codes->fields['ACTIVE'] == 1;

                                        $duration_display = ($duration != '—' && $duration != '') ? $duration . ' min'  : '—';
                                        $initials = getSchedulingInitials($code, $name);
                                        $bg_color = getSchedulingAvatarColor($counter);

                                        // Determine if color is light or dark to set text contrast
                                        $is_light_color = isLightColor($color_code);
                                        $text_color = $is_light_color ? '#1e293b' : '#ffffff';
                                ?>
                                        <tr>
                                            <td class="text-muted small fw-medium"><?= $row_number++ ?></td>
                                            <td class="text-center">
                                                <div class="d-flex justify-content-center align-items-center gap-3">
                                                    <div>
                                                        <div class="fw-semibold mb-1">
                                                            <!-- Code Name displayed inside a colored box matching the scheduling code color -->
                                                            <span class="code-color-box" style="--code-bg: <?= htmlspecialchars($color_code) ?>; --code-text: <?= $text_color ?>; background-color: <?= htmlspecialchars($color_code) ?>; color: <?= $text_color ?>;">
                                                                <?= htmlspecialchars($code) ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="fw-semibold"><?= htmlspecialchars($name) ?></div>
                                            </td>
                                            <td class="text-center">
                                                <span class="location-badge">
                                                    <i class="bi bi-geo-alt-fill text-secondary"></i> <?= htmlspecialchars($location_name) ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($duration_display != '—'): ?>
                                                    <span class="duration-chip"><i class="bi bi-clock"></i> <?= htmlspecialchars($duration_display) ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted small">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center align-middle">
                                                <div class="d-flex justify-content-center align-items-center gap-2">
                                                    <div class="color-swatch"
                                                        style="background-color: <?= htmlspecialchars($color_code) ?>; border: 1px solid rgba(0,0,0,0.1);">
                                                    </div>

                                                    <span class="color-name"
                                                        style="background: <?= htmlspecialchars($color_code) ?>20;
                     color: <?= $is_light_color ? '#1e293b' : '#1e293b'; ?>;
                     border: 1px solid <?= htmlspecialchars($color_code) ?>40;">
                                                        <?= strtoupper(htmlspecialchars($color_code)) ?>
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($to_dos): ?>
                                                    <span class="badge-todo"><i class="bi bi-check2-circle"></i></span>
                                                <?php else: ?>
                                                    <span class="text-muted small">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="text-muted small fw-medium"><?= htmlspecialchars($sort_order) ?></span>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($is_active): ?>
                                                    <span class="badge-status badge-active"><i class="bi bi-check-circle-fill"></i> Active</span>
                                                <?php else: ?>
                                                    <span class="badge-status badge-inactive"><i class="bi bi-x-circle-fill"></i> Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <div class="dropdown">
                                                    <i class="bi bi-three-dots-vertical text-muted cursor-pointer" data-bs-toggle="dropdown" style="cursor: pointer;"></i>
                                                    <ul class="dropdown-menu">
                                                        <li><a class="dropdown-item" href="add_scheduling_codes.php?id=<?= $PK_SCHEDULING_CODE ?>"><i class="bi bi-pencil me-2"></i> Edit</a></li>
                                                        <li><a class="dropdown-item" href="add_scheduling_codes.php?id=<?= $PK_SCHEDULING_CODE ?>&view=1"><i class="bi bi-eye me-2"></i> View</a></li>
                                                        <li>
                                                            <hr class="dropdown-divider">
                                                        </li>
                                                        <li><a class="dropdown-item text-danger" href="#" onclick="confirmDeleteSchedulingCode(<?= $PK_SCHEDULING_CODE ?>)"><i class="bi bi-trash me-2"></i> Delete</a></li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php
                                        $scheduling_codes->MoveNext();
                                        $counter++;
                                    endwhile;
                                endif;
                                if ($total_records == 0):
                                    ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-5">
                                            <i class="bi bi-code-square display-1 text-muted"></i>
                                            <p class="mt-3 text-muted">No scheduling codes found for the selected filters</p>
                                            <button class="btn btn-sm btn-outline-primary mt-2" onclick="window.location.href='add_scheduling_codes.php'">Create your first scheduling code</button>
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

        // Delete scheduling code via AJAX
        function confirmDeleteSchedulingCode(pk_scheduling_code) {
            if (confirm("Are you sure you want to delete this scheduling code? This action may affect scheduling templates and events.")) {
                $.ajax({
                    url: "ajax/AjaxFunctions.php",
                    type: 'POST',
                    data: {
                        FUNCTION_NAME: 'deleteSchedulingCodeData',
                        PK_SCHEDULING_CODE: pk_scheduling_code
                    },
                    success: function(data) {
                        window.location.href = `all_scheduling_codes.php?status=<?= $status_check ?>&search=<?= urlencode($search) ?>&per_page=<?= $per_page ?>`;
                    },
                    error: function() {
                        alert("Error deleting scheduling code. Please try again.");
                    }
                });
            }
        }

        function editpage(id) {
            window.location.href = "add_scheduling_codes.php?id=" + id;
        }
    </script>

    <?php
    // Helper functions
    function getSchedulingInitials($code, $name)
    {
        if (!empty($code)) {
            return strtoupper(substr($code, 0, 2));
        }
        if (!empty($name)) {
            $words = explode(' ', trim($name));
            $initials = '';
            foreach ($words as $word) {
                if (!empty($word)) {
                    $initials .= strtoupper($word[0]);
                }
                if (strlen($initials) >= 2) break;
            }
            return $initials ?: 'SC';
        }
        return 'SC';
    }

    function getSchedulingAvatarColor($index)
    {
        $colors = ['#fef08a', '#fed7aa', '#bfdbfe', '#ddd6fe', '#fbcfe8', '#bbf7d0', '#c4b5fd', '#fdba74', '#a7f3d0', '#fecaca', '#bae6fd', '#d9f99d'];
        return $colors[$index % count($colors)];
    }

    // Helper to determine if a color is light (for text contrast)
    function isLightColor($hex)
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) == 3) {
            $r = hexdec(str_repeat(substr($hex, 0, 1), 2));
            $g = hexdec(str_repeat(substr($hex, 1, 1), 2));
            $b = hexdec(str_repeat(substr($hex, 2, 1), 2));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }
        $brightness = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
        return $brightness > 155;
    }
    ?>
</body>

</html>