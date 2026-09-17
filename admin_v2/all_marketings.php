<!DOCTYPE html>
<html lang="en">
<?php
require_once('../global/config.php');
$title = "All Marketing Campaigns";

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

$header_text = '';
$header_data = $db->Execute("SELECT * FROM `DOA_HEADER_TEXT` WHERE ACTIVE = 1 AND HEADER_TITLE = 'Email Templates Page'");
if ($header_data->RecordCount() > 0) {
    $header_text = $header_data->fields['HEADER_TEXT'];
}

// Handle delete and toggle actions
if (isset($_POST['action']) && isset($_POST['campaign_id'])) {
    $campaign_id = intval($_POST['campaign_id']);
    $action = $_POST['action'];

    if ($action == 'delete') {
        // Look up the name before deleting so we can say which one in the confirmation
        $del_res = $db_account->Execute("SELECT CAMPAIGN_NAME FROM DOA_MARKET_CAMPAIGN 
                                         WHERE PK_MARKET_CAMPAIGN = " . $campaign_id . " 
                                         AND PK_ACCOUNT_MASTER = " . intval($_SESSION['PK_ACCOUNT_MASTER']));
        $deleted_name = ($del_res && $del_res->RecordCount() > 0) ? $del_res->fields['CAMPAIGN_NAME'] : '';

        $db_account->Execute("DELETE FROM DOA_MARKET_CAMPAIGN WHERE PK_MARKET_CAMPAIGN = " . $campaign_id . " 
                              AND PK_ACCOUNT_MASTER = " . intval($_SESSION['PK_ACCOUNT_MASTER']));
        $_SESSION['success_message'] = $deleted_name
            ? 'Marketing campaign "' . $deleted_name . '" was deleted.'
            : 'Marketing campaign deleted successfully.';
    } elseif ($action == 'toggle_status') {
        // Look up the name BEFORE toggling so the message can name the campaign
        $name_res = $db_account->Execute("SELECT CAMPAIGN_NAME, ACTIVE FROM DOA_MARKET_CAMPAIGN 
                                          WHERE PK_MARKET_CAMPAIGN = " . $campaign_id . " 
                                          AND PK_ACCOUNT_MASTER = " . intval($_SESSION['PK_ACCOUNT_MASTER']));
        $toggled_name = ($name_res && $name_res->RecordCount() > 0) ? $name_res->fields['CAMPAIGN_NAME'] : '';
        $was_active   = ($name_res && $name_res->RecordCount() > 0) ? ((int)$name_res->fields['ACTIVE'] === 1) : false;

        $db_account->Execute("UPDATE DOA_MARKET_CAMPAIGN SET ACTIVE = IF(ACTIVE = 1, 0, 1) 
                              WHERE PK_MARKET_CAMPAIGN = " . $campaign_id . " 
                              AND PK_ACCOUNT_MASTER = " . intval($_SESSION['PK_ACCOUNT_MASTER']));

        if ($toggled_name) {
            $_SESSION['success_message'] = $was_active
                ? 'Marketing campaign "' . $toggled_name . '" was deactivated.'
                : 'Marketing campaign "' . $toggled_name . '" was activated.';
        } else {
            $_SESSION['success_message'] = 'Marketing campaign status updated successfully.';
        }
    }

    header("Location: " . $_SERVER['PHP_SELF'] . "?" . http_build_query($_GET));
    exit;
}

// ---------------------------------------------------------------
// Handle post-save redirect (from marketing.php after create/update)
// The save page redirects here with ?saved=ID&saved_name=NAME
// ---------------------------------------------------------------
$highlight_id = 0;
$highlight_name = '';
if (!empty($_GET['saved'])) {
    $highlight_id = intval($_GET['saved']);
    if (!empty($_GET['saved_name'])) {
        $highlight_name = $_GET['saved_name'];
    }
}

// Get filter parameters
$status_check = isset($_GET['status']) ? $_GET['status'] : 'active';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 8;

// If we're returning from a save, force the filter to match the saved campaign's
// actual status and jump to the page containing it. This guarantees the user
// lands on the page that actually contains the campaign.
if ($highlight_id > 0) {
    // Look up the campaign's actual status + created timestamp
    $hc_res = $db_account->Execute("SELECT ACTIVE, CREATED_ON, CAMPAIGN_NAME 
                                    FROM DOA_MARKET_CAMPAIGN 
                                    WHERE PK_MARKET_CAMPAIGN = " . $highlight_id . " 
                                    AND PK_ACCOUNT_MASTER = " . intval($_SESSION['PK_ACCOUNT_MASTER']));
    if ($hc_res && $hc_res->RecordCount() > 0) {
        $hc_active = ((int)$hc_res->fields['ACTIVE'] === 1);
        $status_check = $hc_active ? 'active' : 'inactive';
        if (empty($highlight_name)) {
            $highlight_name = $hc_res->fields['CAMPAIGN_NAME'];
        }

        // Find this campaign's row position under the current sort (CREATED_ON DESC).
        // Count how many campaigns come strictly before it.
        $hc_status_clause = $hc_active ? "(DOA_MARKET_CAMPAIGN.ACTIVE = 1)"
            : "(DOA_MARKET_CAMPAIGN.ACTIVE = 0 OR DOA_MARKET_CAMPAIGN.ACTIVE IS NULL)";
        $created_on = $hc_res->fields['CREATED_ON'];

        $pos_res = $db_account->Execute(
            "SELECT COUNT(*) AS before_count FROM DOA_MARKET_CAMPAIGN
             WHERE DOA_MARKET_CAMPAIGN.PK_ACCOUNT_MASTER = " . intval($_SESSION['PK_ACCOUNT_MASTER']) . "
               AND DOA_MARKET_CAMPAIGN.PK_LOCATION IN (" . intval($_SESSION['DEFAULT_LOCATION_ID']) . ")
               AND $hc_status_clause
               AND (DOA_MARKET_CAMPAIGN.CREATED_ON > '" . addslashes($created_on) . "'
                    OR (DOA_MARKET_CAMPAIGN.CREATED_ON = '" . addslashes($created_on) . "'
                        AND DOA_MARKET_CAMPAIGN.PK_MARKET_CAMPAIGN > " . $highlight_id . "))"
        );
        $before_count = ($pos_res && $pos_res->RecordCount() > 0) ? (int)$pos_res->fields['before_count'] : 0;
        $page = (int)floor($before_count / $per_page) + 1;
    }
}

// Build active condition (qualified to DOA_MARKET_CAMPAIGN, handles NULL safely)
if ($status_check == 'active') {
    $active_condition = "(DOA_MARKET_CAMPAIGN.ACTIVE = 1)";
} else {
    $active_condition = "(DOA_MARKET_CAMPAIGN.ACTIVE = 0 OR DOA_MARKET_CAMPAIGN.ACTIVE IS NULL)";
}

$offset = ($page - 1) * $per_page;

// Count total records
$count_query = "SELECT COUNT(*) as total 
                FROM DOA_MARKET_CAMPAIGN 
                WHERE DOA_MARKET_CAMPAIGN.PK_ACCOUNT_MASTER = " . intval($_SESSION['PK_ACCOUNT_MASTER']) . " 
                AND DOA_MARKET_CAMPAIGN.PK_LOCATION IN (" . ($_SESSION['DEFAULT_LOCATION_ID']) . ")
                AND $active_condition";

if (!empty($search)) {
    $count_query .= " AND (DOA_MARKET_CAMPAIGN.CAMPAIGN_NAME LIKE '%" . addslashes($search) . "%' 
                       OR DOA_MARKET_CAMPAIGN.SUBJECT LIKE '%" . addslashes($search) . "%')";
}

$total_result = $db_account->Execute($count_query);
$total_records = (int)$total_result->fields['total'];
$total_pages = max(1, (int)ceil($total_records / $per_page));

// Clamp page to valid range
if ($page < 1) $page = 1;
if ($page > $total_pages) $page = $total_pages;
$offset = ($page - 1) * $per_page;

// Get campaigns for current page — newest first
$query = "SELECT DOA_MARKET_CAMPAIGN.*, DOA_LOCATION.LOCATION_NAME 
          FROM DOA_MARKET_CAMPAIGN 
          LEFT JOIN $master_database.DOA_LOCATION ON DOA_MARKET_CAMPAIGN.PK_LOCATION = DOA_LOCATION.PK_LOCATION
          WHERE DOA_MARKET_CAMPAIGN.PK_LOCATION IN (" . ($_SESSION['DEFAULT_LOCATION_ID']) . ") 
          AND DOA_MARKET_CAMPAIGN.PK_ACCOUNT_MASTER = " . intval($_SESSION['PK_ACCOUNT_MASTER']) . " 
          AND $active_condition";

if (!empty($search)) {
    $query .= " AND (DOA_MARKET_CAMPAIGN.CAMPAIGN_NAME LIKE '%" . addslashes($search) . "%' 
                 OR DOA_MARKET_CAMPAIGN.SUBJECT LIKE '%" . addslashes($search) . "%')";
}

// Newest first. PK_MARKET_CAMPAIGN DESC is a stable tiebreaker for rows created in the same second.
$query .= " ORDER BY DOA_MARKET_CAMPAIGN.CREATED_ON DESC, DOA_MARKET_CAMPAIGN.PK_MARKET_CAMPAIGN DESC LIMIT $offset, $per_page";
$marketing_campaigns = $db_account->Execute($query);

// How many rows are actually being shown on this page
$shown_from = $total_records === 0 ? 0 : $offset + 1;
$shown_to   = min($offset + $per_page, $total_records);
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
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <style>
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
            gap: 14px;
            align-items: center;
            justify-content: flex-start;
        }

        .action-icons a {
            color: #64748b;
            transition: color 0.2s;
            font-size: 1.1rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }

        .action-icons a:hover {
            color: #0d6efd;
        }

        .action-icons a.text-danger:hover {
            color: #dc3545 !important;
        }

        /* Toggle status button styling */
        .action-icons .toggle-status-btn i {
            font-size: 1.7rem;
            transition: color 0.2s ease;
        }

        /* ACTIVE campaign -> GREEN toggle */
        .action-icons .toggle-status-btn.is-active i {
            color: #16a34a;
        }

        .action-icons .toggle-status-btn.is-active:hover i {
            color: #dc3545;
        }

        /* INACTIVE campaign -> RED toggle */
        .action-icons .toggle-status-btn.is-inactive i {
            color: #dc3545;
        }

        .action-icons .toggle-status-btn.is-inactive:hover i {
            color: #16a34a;
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

        .empty-state {
            text-align: center;
            padding: 3rem;
        }

        .empty-state i {
            font-size: 4rem;
            color: #cbd5e1;
        }

        .status-icon {
            font-size: 1.2rem;
            margin-right: 8px;
        }

        .template-preview {
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Highlighted row after a save */
        @keyframes highlightFade {
            0% {
                background-color: #FEF3C7;
            }

            60% {
                background-color: #FEF3C7;
            }

            100% {
                background-color: transparent;
            }
        }

        tr.highlight-row>td {
            animation: highlightFade 3.5s ease-out;
            border-top: 1px solid #FCD34D !important;
            border-bottom: 1px solid #FCD34D !important;
        }

        tr.highlight-row>td:first-child {
            border-left: 1px solid #FCD34D !important;
            border-top-left-radius: 8px;
            border-bottom-left-radius: 8px;
        }

        tr.highlight-row>td:last-child {
            border-right: 1px solid #FCD34D !important;
            border-top-right-radius: 8px;
            border-bottom-right-radius: 8px;
        }

        .count-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--gray-100, #f3f4f6);
            color: var(--gray-600, #4b5563);
            padding: 3px 12px;
            border-radius: 20px;
            font-weight: 500;
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
                    <!-- Success message -->
                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="alert alert-success alert-dismissible fade show rounded-3" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <?= htmlspecialchars($_SESSION['success_message']) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php unset($_SESSION['success_message']); ?>
                    <?php elseif ($highlight_id > 0 && !empty($highlight_name)): ?>
                        <div class="alert alert-success alert-dismissible fade show rounded-3" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            Marketing campaign "<strong><?= htmlspecialchars($highlight_name) ?></strong>" was saved successfully.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
                        <div>
                            <h2 class="fw-semibold h4 mb-1">
                                <i class="bi bi-megaphone me-2" style="color: #39b54a;"></i>Marketing Campaigns
                            </h2>
                            <p class="text-muted small mb-0">Manage marketing campaigns and their configurations</p>
                        </div>
                        <button class="btn btn-success-custom rounded-pill d-flex align-items-center gap-2" onclick="window.location.href='marketing.php'">
                            <i class="bi bi-plus-lg"></i> Create New Marketing Campaign
                        </button>
                    </div>

                    <!-- Filters -->
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <div class="search-container">
                            <i class="bi bi-search"></i>
                            <input type="text" class="form-control search-input" placeholder="Search by campaign name or subject..." id="searchInput" value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="status-toggle-group">
                            <button class="status-btn <?= $status_check == 'active' ? 'active' : '' ?>" data-status="active">Active</button>
                            <button class="status-btn <?= $status_check == 'inactive' ? 'active' : '' ?>" data-status="inactive">Not Active</button>
                        </div>
                    </div>

                    <!-- Results count — shows "showing X-Y of Z" -->
                    <div class="text-muted small mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-megaphone"></i>
                        <?php if ($total_records === 0): ?>
                            <span class="count-badge">No marketing campaigns</span>
                        <?php else: ?>
                            <span class="count-badge">
                                Showing <?= $shown_from ?>–<?= $shown_to ?> of <?= $total_records ?>
                                <?= $total_records == 1 ? 'marketing campaign' : 'marketing campaigns' ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Marketing Campaigns Table -->
                    <div class="table-responsive">
                        <table class="table custom-table align-middle mb-4">
                            <thead>
                                <tr>
                                    <th style="width: 40px;">#</th>
                                    <th>Campaign Name</th>
                                    <th style="text-align: center;">Location</th>
                                    <th style="text-align: center;">Subject</th>
                                    <th style="text-align: center;">Status</th>
                                    <th style="width: 130px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $counter = 0;
                                $row_number = $offset + 1;
                                if ($marketing_campaigns && !$marketing_campaigns->EOF):
                                    while (!$marketing_campaigns->EOF):
                                        $PK_MARKET_CAMPAIGN = $marketing_campaigns->fields['PK_MARKET_CAMPAIGN'];
                                        $campaign_name = $marketing_campaigns->fields['CAMPAIGN_NAME'];
                                        $location_name = $marketing_campaigns->fields['LOCATION_NAME'];
                                        $subject = $marketing_campaigns->fields['SUBJECT'];
                                        $is_active = ($marketing_campaigns->fields['ACTIVE'] == 1);
                                        $is_highlight = ($highlight_id > 0 && (int)$PK_MARKET_CAMPAIGN === $highlight_id);
                                ?>
                                        <tr class="<?= $is_highlight ? 'highlight-row' : '' ?>" <?= $is_highlight ? 'id="highlightedCampaignRow"' : '' ?>>
                                            <td class="text-muted small fw-medium"><?= $row_number++ ?></td>
                                            <td>
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="fw-semibold">
                                                        <?= htmlspecialchars($campaign_name) ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center"><?= htmlspecialchars($location_name) ?></td>
                                            <td class="text-center"><?= htmlspecialchars($subject) ?></td>
                                            <td class="text-center">
                                                <?php if ($is_active): ?>
                                                    <span class="badge-status badge-active"><i class="bi bi-check-circle-fill"></i> Active</span>
                                                <?php else: ?>
                                                    <span class="badge-status badge-inactive"><i class="bi bi-x-circle-fill"></i> Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="action-icons">
                                                    <a href="marketing.php?id=<?= $PK_MARKET_CAMPAIGN ?>" title="Edit">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </a>
                                                    <a href="javascript:void(0);"
                                                        class="toggle-status-btn <?= $is_active ? 'is-active' : 'is-inactive' ?>"
                                                        data-id="<?= $PK_MARKET_CAMPAIGN ?>"
                                                        data-status="<?= $is_active ? 'active' : 'inactive' ?>"
                                                        title="<?= $is_active ? 'Deactivate' : 'Activate' ?>">
                                                        <i class="bi <?= $is_active ? 'bi-toggle-on' : 'bi-toggle-off' ?>"></i>
                                                    </a>
                                                    <a href="javascript:void(0);"
                                                        class="delete-btn text-danger"
                                                        data-id="<?= $PK_MARKET_CAMPAIGN ?>"
                                                        data-name="<?= htmlspecialchars($campaign_name) ?>"
                                                        title="Delete">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php
                                        $marketing_campaigns->MoveNext();
                                        $counter++;
                                    endwhile;
                                endif;
                                if ($total_records == 0):
                                    ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5">
                                            <i class="bi bi-megaphone display-1 text-muted"></i>
                                            <p class="mt-3 text-muted">No marketing campaigns found for the selected filters</p>
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

    <!-- Hidden form for actions -->
    <form id="actionForm" method="POST" style="display: none;">
        <input type="hidden" name="action" id="actionType">
        <input type="hidden" name="campaign_id" id="campaignId">
    </form>

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

        // Status toggle buttons (filter)
        $('.status-btn').on('click', function() {
            let newStatus = $(this).data('status');
            if (newStatus) {
                window.location.href = '?status=' + newStatus + '&search=<?= urlencode($search) ?>&per_page=<?= $per_page ?>';
            }
        });

        function editpage(id) {
            window.location.href = "marketing.php?id=" + id;
        }

        // Toggle active/inactive status
        $(document).on('click', '.toggle-status-btn', function() {
            let id = $(this).data('id');
            let status = $(this).data('status');
            let actionText = status === 'active' ? 'deactivate' : 'activate';

            if (confirm('Are you sure you want to ' + actionText + ' this campaign?')) {
                $('#actionType').val('toggle_status');
                $('#campaignId').val(id);
                $('#actionForm').submit();
            }
        });

        // Delete campaign
        $(document).on('click', '.delete-btn', function() {
            let id = $(this).data('id');
            let name = $(this).data('name');

            if (confirm('Are you sure you want to delete "' + name + '"? This action cannot be undone.')) {
                $('#actionType').val('delete');
                $('#campaignId').val(id);
                $('#actionForm').submit();
            }
        });

        // Auto-dismiss success alert
        setTimeout(function() {
            $('.alert-success').fadeOut('slow', function() {
                $(this).remove();
            });
        }, 4000);

        // If we highlighted a row, scroll it into view smoothly and clean up the URL
        // so a refresh doesn't keep re-highlighting it.
        $(function() {
            const highlighted = document.getElementById('highlightedCampaignRow');
            if (highlighted) {
                setTimeout(() => {
                    highlighted.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                }, 100);

                // Remove ?saved=... from the URL without reloading the page
                if (window.history && window.history.replaceState) {
                    const url = new URL(window.location.href);
                    url.searchParams.delete('saved');
                    url.searchParams.delete('saved_name');
                    window.history.replaceState({}, document.title, url.pathname + '?' + url.searchParams.toString());
                }
            }
        });
    </script>
</body>

</html>