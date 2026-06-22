<?php
require_once('../global/config.php');
global $db;
global $db_account;

$title = "Document Library";

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
$header_data = $db->Execute("SELECT * FROM `DOA_HEADER_TEXT` WHERE ACTIVE = 1 AND HEADER_TITLE = 'Document Library page'");
if ($header_data && $header_data->RecordCount() > 0) {
    $header_text = $header_data->fields['HEADER_TEXT'];
}

$offset = ($page - 1) * $per_page;

// Count total records
$count_query = "SELECT COUNT(DISTINCT PK_DOCUMENT_LIBRARY) as total 
                FROM DOA_DOCUMENT_LIBRARY 
                WHERE DOA_DOCUMENT_LIBRARY.ACTIVE = '$status'";

if (!empty($search)) {
    $count_query .= " AND DOA_DOCUMENT_LIBRARY.DOCUMENT_NAME LIKE '%" . addslashes($search) . "%'";
}

$total_result = $db_account->Execute($count_query);
$total_records = $total_result->fields['total'];
$total_pages = ceil($total_records / $per_page);

// Get documents for current page
$query = "SELECT * FROM DOA_DOCUMENT_LIBRARY 
          WHERE DOA_DOCUMENT_LIBRARY.ACTIVE = '$status'
          GROUP BY DOA_DOCUMENT_LIBRARY.DOCUMENT_NAME";

if (!empty($search)) {
    $query .= " AND DOA_DOCUMENT_LIBRARY.DOCUMENT_NAME LIKE '%" . addslashes($search) . "%'";
}

$query .= " ORDER BY DOA_DOCUMENT_LIBRARY.DOCUMENT_NAME ASC LIMIT $offset, $per_page";
$documents = $db_account->Execute($query);
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
            font-size: 1.1rem;
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

        .document-icon {
            font-size: 2rem;
            color: #64748b;
        }

        .document-name {
            font-weight: 600;
            font-size: 1rem;
            color: #1e293b;
        }

        .document-meta {
            font-size: 0.7rem;
            color: #94a3b8;
            margin-top: 4px;
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

        .action-icons .text-danger:hover {
            color: #dc2626 !important;
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
                                    <i class="bi bi-slash-circle me-2 text-muted"></i>Not Active Document Library
                                <?php } else { ?>
                                    <i class="bi bi-check-circle-fill me-2 text-success"></i>Active Document Library
                                <?php } ?>
                            </h2>
                            <p class="text-muted small mb-0">Manage document templates, forms, and library resources</p>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-success-custom rounded-pill d-flex align-items-center gap-2" onclick="createNewDocument()">
                                <i class="bi bi-plus-lg"></i> Create New Document
                            </button>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <div class="search-container">
                            <i class="bi bi-search"></i>
                            <input type="text" class="form-control search-input" placeholder="Search by document name..." id="searchInput" value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="status-toggle-group">
                            <button class="status-btn <?= $status_check == 'active' ? 'active' : '' ?>" data-status="active">Active</button>
                            <button class="status-btn <?= $status_check == 'inactive' ? 'active' : '' ?>" data-status="inactive">Not Active</button>
                        </div>
                    </div>

                    <!-- Results count -->
                    <div class="text-muted small mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-files"></i> <?= $total_records ?> <?= $total_records == 1 ? 'document' : 'documents' ?>
                    </div>

                    <!-- Document Grid View -->
                    <?php if ($total_records > 0): ?>
                        <div class="row g-3">
                            <?php
                            $counter = 0;
                            $row_number = $offset + 1;
                            if ($documents && !$documents->EOF):
                                while (!$documents->EOF):
                                    $PK_DOCUMENT_LIBRARY = $documents->fields['PK_DOCUMENT_LIBRARY'];
                                    $document_name = $documents->fields['DOCUMENT_NAME'];
                                    $is_active = $documents->fields['ACTIVE'] == 1;

                                    // Get file count for this document
                                    $fileCountQuery = $db_account->Execute("SELECT COUNT(*) as file_count FROM DOA_DOCUMENT_LIBRARY WHERE DOCUMENT_NAME = '" . addslashes($document_name) . "'");
                                    $file_count = ($fileCountQuery && !$fileCountQuery->EOF) ? $fileCountQuery->fields['file_count'] : 1;

                                    $initials = getDocumentInitials($document_name);
                                    $bg_color = getDocumentAvatarColor($counter);
                            ?>
                                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                        <div class="card border-0 shadow-sm h-100 document-card" style="border-radius: 20px; transition: all 0.2s; cursor: pointer;" onclick="editDocument(<?= $PK_DOCUMENT_LIBRARY ?>)">
                                            <div class="card-body text-center p-4">
                                                <div class="avatar-circle mx-auto mb-3" style="background: linear-gradient(135deg, <?= $bg_color['start'] ?>, <?= $bg_color['end'] ?>); width: 70px; height: 70px; border-radius: 70px;">
                                                    <i class="bi bi-file-text-fill" style="font-size: 2rem;"></i>
                                                </div>
                                                <h6 class="document-name mb-1"><?= htmlspecialchars($document_name) ?></h6>
                                                <div class="document-meta">
                                                    <span class="badge bg-light text-dark rounded-pill px-2 py-1">
                                                        <i class="bi bi-files me-1"></i> <?= $file_count ?> version<?= $file_count != 1 ? 's' : '' ?>
                                                    </span>
                                                </div>
                                                <div class="mt-3">
                                                    <?php if ($is_active): ?>
                                                        <span class="badge-status badge-active"><i class="bi bi-check-circle-fill"></i> Active</span>
                                                    <?php else: ?>
                                                        <span class="badge-status badge-inactive"><i class="bi bi-x-circle-fill"></i> Inactive</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="card-footer bg-transparent border-0 pb-3 pt-0 text-center">
                                                <div class="action-icons d-flex justify-content-center gap-3">
                                                    <a href="javascript:;" onclick="event.stopPropagation(); editDocument(<?= $PK_DOCUMENT_LIBRARY ?>);" title="Edit">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </a>
                                                    <a href="javascript:;" onclick="event.stopPropagation(); ConfirmDelete(<?= $PK_DOCUMENT_LIBRARY ?>);" title="Delete" class="text-danger">
                                                        <i class="bi bi-trash3"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                            <?php
                                    $documents->MoveNext();
                                    $counter++;
                                endwhile;
                            endif; ?>
                        </div>
                    <?php else: ?>
                        <!-- Empty State -->
                        <div class="empty-state">
                            <i class="bi bi-folder2-open"></i>
                            <p class="mt-3 text-muted">No documents found for the selected filters</p>
                        </div>
                    <?php endif; ?>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 pt-4 mt-2">
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
                                    <option value="12" <?= $per_page == 12 ? 'selected' : '' ?>>12 / page</option>
                                    <option value="24" <?= $per_page == 24 ? 'selected' : '' ?>>24 / page</option>
                                    <option value="48" <?= $per_page == 48 ? 'selected' : '' ?>>48 / page</option>
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

        // Delete document
        function ConfirmDelete(PK_DOCUMENT_LIBRARY) {
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
                            FUNCTION_NAME: 'deleteDocumentLibraryData',
                            PK_DOCUMENT_LIBRARY: PK_DOCUMENT_LIBRARY
                        },
                        success: function(data) {
                            Swal.fire('Deleted!', 'Document has been deleted.', 'success');
                            window.location.href = `all_document_library.php?status=<?= $status_check ?>&search=<?= urlencode($search) ?>&per_page=<?= $per_page ?>`;
                        },
                        error: function() {
                            Swal.fire('Error!', 'Something went wrong.', 'error');
                        }
                    });
                }
            });
        }

        // Edit document
        function editDocument(id) {
            window.location.href = "document_library.php?id=" + id;
        }

        // Create new document
        function createNewDocument() {
            window.location.href = 'document_library.php';
        }

        // Card hover effect
        $('.document-card').hover(
            function() {
                $(this).css('transform', 'translateY(-4px)');
                $(this).css('box-shadow', '0 12px 24px rgba(0,0,0,0.1)');
            },
            function() {
                $(this).css('transform', 'translateY(0)');
                $(this).css('box-shadow', '0 4px 12px rgba(0,0,0,0.05)');
            }
        );
    </script>

    <?php
    // Helper functions
    function getDocumentInitials($name)
    {
        $words = explode(' ', trim($name));
        $initials = '';
        foreach ($words as $word) {
            if (!empty($word)) {
                $initials .= strtoupper($word[0]);
            }
            if (strlen($initials) >= 2) break;
        }
        return $initials ?: 'DOC';
    }

    function getDocumentAvatarColor($index)
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
            ['start' => '#fbc2eb', 'end' => '#a6c1ee'],
            ['start' => '#84fab0', 'end' => '#8fd3f4'],
            ['start' => '#a8edea', 'end' => '#fed6e3']
        ];
        return $gradients[$index % count($gradients)];
    }
    ?>
</body>

</html>