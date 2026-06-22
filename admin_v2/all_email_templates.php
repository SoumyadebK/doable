<!DOCTYPE html>
<html lang="en">
<?php
require_once('../global/config.php');
$title = "All Email Templates";

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

// Get filter parameters
$status_check = isset($_GET['status']) ? $_GET['status'] : 'active';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 8;

if ($status_check == 'active') {
    $status = 1;
} elseif ($status_check == 'inactive') {
    $status = 0;
}

$offset = ($page - 1) * $per_page;

// Build active condition
if ($status_check == 'active') {
    $active_condition = "DOA_EMAIL_TEMPLATE.ACTIVE = 1";
} else {
    $active_condition = "DOA_EMAIL_TEMPLATE.ACTIVE = 0";
}

// Count total records
$count_query = "SELECT COUNT(*) as total 
                FROM DOA_EMAIL_TEMPLATE 
                WHERE DOA_EMAIL_TEMPLATE.PK_ACCOUNT_MASTER = " . intval($_SESSION['PK_ACCOUNT_MASTER']) . " 
                AND $active_condition";

if (!empty($search)) {
    $count_query .= " AND (DOA_EMAIL_TEMPLATE.TEMPLATE_NAME LIKE '%" . addslashes($search) . "%' 
                       OR DOA_EMAIL_TEMPLATE.SUBJECT LIKE '%" . addslashes($search) . "%')";
}

$total_result = $db_account->Execute($count_query);
$total_records = $total_result->fields['total'];
$total_pages = ceil($total_records / $per_page);

// Get email templates for current page
$query = "SELECT * FROM DOA_EMAIL_TEMPLATE 
          WHERE DOA_EMAIL_TEMPLATE.PK_ACCOUNT_MASTER = " . intval($_SESSION['PK_ACCOUNT_MASTER']) . " 
          AND $active_condition";

if (!empty($search)) {
    $query .= " AND (DOA_EMAIL_TEMPLATE.TEMPLATE_NAME LIKE '%" . addslashes($search) . "%' 
                 OR DOA_EMAIL_TEMPLATE.SUBJECT LIKE '%" . addslashes($search) . "%')";
}

$query .= " ORDER BY DOA_EMAIL_TEMPLATE.TEMPLATE_NAME ASC LIMIT $offset, $per_page";
$email_templates = $db_account->Execute($query);
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
                                    <i class="bi bi-slash-circle me-2 text-muted"></i>Not Active Email Templates
                                <?php } else { ?>
                                    <i class="bi bi-check-circle-fill me-2 text-success"></i>Active Email Templates
                                <?php } ?>
                            </h2>
                            <p class="text-muted small mb-0">Manage email templates and their configurations</p>
                        </div>
                        <button class="btn btn-success-custom rounded-pill d-flex align-items-center gap-2" onclick="window.location.href='email_template.php'">
                            <i class="bi bi-plus-lg"></i> Create New Email Template
                        </button>
                    </div>

                    <!-- Filters -->
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <div class="search-container">
                            <i class="bi bi-search"></i>
                            <input type="text" class="form-control search-input" placeholder="Search by template name or subject..." id="searchInput" value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="status-toggle-group">
                            <button class="status-btn <?= $status_check == 'active' ? 'active' : '' ?>" data-status="active">Active</button>
                            <button class="status-btn <?= $status_check == 'inactive' ? 'active' : '' ?>" data-status="inactive">Not Active</button>
                        </div>
                    </div>

                    <!-- Results count -->
                    <div class="text-muted small mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-envelope"></i> <?= $total_records ?> <?= $total_records == 1 ? 'email template' : 'email templates' ?>
                    </div>

                    <!-- Email Templates Table -->
                    <div class="table-responsive">
                        <table class="table custom-table align-middle mb-4">
                            <thead>
                                <tr>
                                    <th style="width: 40px;">#</th>
                                    <th>Template Name</th>
                                    <th>Subject</th>
                                    <th style="text-align: center;">Status</th>
                                    <th style="width: 60px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $counter = 0;
                                $row_number = $offset + 1;
                                if ($email_templates && !$email_templates->EOF):
                                    while (!$email_templates->EOF):
                                        $PK_EMAIL_TEMPLATE = $email_templates->fields['PK_EMAIL_TEMPLATE'];
                                        $template_name = $email_templates->fields['TEMPLATE_NAME'];
                                        $subject = $email_templates->fields['SUBJECT'];
                                        $is_active = $email_templates->fields['ACTIVE'] == 1;
                                ?>
                                        <tr>
                                            <td class="text-muted small fw-medium"><?= $row_number++ ?></td>
                                            <td>
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="fw-semibold">
                                                        <?= htmlspecialchars($template_name) ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?= htmlspecialchars($subject) ?></td>
                                            <td class="text-center">
                                                <?php if ($is_active): ?>
                                                    <span class="badge-status badge-active"><i class="bi bi-check-circle-fill"></i> Active</span>
                                                <?php else: ?>
                                                    <span class="badge-status badge-inactive"><i class="bi bi-x-circle-fill"></i> Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="action-icons">
                                                    <a href="email_template.php?id=<?= $PK_EMAIL_TEMPLATE ?>" title="Edit">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php
                                        $email_templates->MoveNext();
                                        $counter++;
                                    endwhile;
                                endif;
                                if ($total_records == 0):
                                    ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5">
                                            <i class="bi bi-envelope display-1 text-muted"></i>
                                            <p class="mt-3 text-muted">No email templates found for the selected filters</p>
                                            <button class="btn btn-sm btn-outline-primary mt-2" onclick="window.location.href='email_template.php'">Create your first email template</button>
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

        function editpage(id) {
            window.location.href = "email_template.php?id=" + id;
        }
    </script>
</body>

</html>