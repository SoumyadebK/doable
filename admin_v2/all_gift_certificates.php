<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;

$title = "All Gift Certificates";

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
$header_data = $db->Execute("SELECT * FROM `DOA_HEADER_TEXT` WHERE ACTIVE = 1 AND HEADER_TITLE = 'Gift Certificates page'");
if ($header_data && $header_data->RecordCount() > 0) {
    $header_text = $header_data->fields['HEADER_TEXT'];
}

$offset = ($page - 1) * $per_page;

// Build search condition
$search_condition = '';
if (!empty($search)) {
    $search_condition = " AND (CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) LIKE '%" . addslashes($search) . "%' 
                      OR DOA_GIFT_CERTIFICATE_SETUP.GIFT_CERTIFICATE_NAME LIKE '%" . addslashes($search) . "%' 
                      OR DOA_GIFT_CERTIFICATE_SETUP.GIFT_CERTIFICATE_CODE LIKE '%" . addslashes($search) . "%' 
                      OR DOA_GIFT_CERTIFICATE_MASTER.AMOUNT LIKE '%" . addslashes($search) . "%')";
}

// Count total records
$count_query = "SELECT COUNT(DISTINCT DOA_GIFT_CERTIFICATE_MASTER.PK_GIFT_CERTIFICATE_MASTER) as total 
                FROM DOA_GIFT_CERTIFICATE_MASTER 
                INNER JOIN DOA_GIFT_CERTIFICATE_SETUP ON DOA_GIFT_CERTIFICATE_MASTER.PK_GIFT_CERTIFICATE_SETUP = DOA_GIFT_CERTIFICATE_SETUP.PK_GIFT_CERTIFICATE_SETUP 
                LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_GIFT_CERTIFICATE_MASTER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER 
                LEFT JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USER_MASTER.PK_USER = DOA_USERS.PK_USER 
                WHERE DOA_GIFT_CERTIFICATE_MASTER.ACTIVE = '$status' 
                AND DOA_GIFT_CERTIFICATE_MASTER.PK_ACCOUNT_MASTER = " . intval($_SESSION['PK_ACCOUNT_MASTER']) . " 
                $search_condition";

$total_result = $db_account->Execute($count_query);
$total_records = $total_result->fields['total'];
$total_pages = ceil($total_records / $per_page);

// Get gift certificates for current page
$query = "SELECT DOA_GIFT_CERTIFICATE_MASTER.PK_GIFT_CERTIFICATE_MASTER, 
          CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS CUSTOMER_NAME,
          DOA_GIFT_CERTIFICATE_SETUP.GIFT_CERTIFICATE_CODE, 
          DOA_GIFT_CERTIFICATE_SETUP.GIFT_CERTIFICATE_NAME, 
          DOA_GIFT_CERTIFICATE_MASTER.DATE_OF_PURCHASE, 
          DOA_GIFT_CERTIFICATE_MASTER.AMOUNT, 
          DOA_GIFT_CERTIFICATE_MASTER.ACTIVE 
          FROM DOA_GIFT_CERTIFICATE_MASTER 
          INNER JOIN DOA_GIFT_CERTIFICATE_SETUP ON DOA_GIFT_CERTIFICATE_MASTER.PK_GIFT_CERTIFICATE_SETUP = DOA_GIFT_CERTIFICATE_SETUP.PK_GIFT_CERTIFICATE_SETUP 
          LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_GIFT_CERTIFICATE_MASTER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER 
          LEFT JOIN $master_database.DOA_USERS AS DOA_USERS ON DOA_USER_MASTER.PK_USER = DOA_USERS.PK_USER 
          WHERE DOA_GIFT_CERTIFICATE_MASTER.ACTIVE = '$status' 
          AND DOA_GIFT_CERTIFICATE_MASTER.PK_ACCOUNT_MASTER = " . intval($_SESSION['PK_ACCOUNT_MASTER']) . " 
          $search_condition 
          ORDER BY DOA_GIFT_CERTIFICATE_MASTER.DATE_OF_PURCHASE DESC 
          LIMIT $offset, $per_page";

$gift_certificates = $db_account->Execute($query);
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
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 48px;
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

        .amount-badge {
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

        .action-icons .text-danger:hover {
            color: #dc2626 !important;
        }

        .date-text {
            background: #f8f9fa;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: #475569;
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

        .gift-card-icon {
            font-size: 1.5rem;
            margin-right: 8px;
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
                                    <i class="bi bi-slash-circle me-2 text-muted"></i>Not Active Gift Certificates
                                <?php } else { ?>
                                    <i class="bi bi-check-circle-fill me-2 text-success"></i>Active Gift Certificates
                                <?php } ?>
                            </h2>
                            <p class="text-muted small mb-0">Manage gift certificates, track purchases, and generate PDFs</p>
                            <?php if (!empty($header_text)): ?>
                                <div class="mt-2 alert alert-light py-2 px-3 small bg-light rounded-3 header-note">
                                    <i class="bi bi-info-circle-fill me-1 text-primary"></i> <?= htmlspecialchars($header_text) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-success-custom rounded-pill d-flex align-items-center gap-2" onclick="createNewGiftCertificate()">
                                <i class="bi bi-plus-lg"></i> Create New
                            </button>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <div class="search-container">
                            <i class="bi bi-search"></i>
                            <input type="text" class="form-control search-input" placeholder="Search by customer, code, name, amount..." id="searchInput" value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="status-toggle-group">
                            <button class="status-btn <?= $status_check == 'active' ? 'active' : '' ?>" data-status="active">Active</button>
                            <button class="status-btn <?= $status_check == 'inactive' ? 'active' : '' ?>" data-status="inactive">Not Active</button>
                        </div>
                    </div>

                    <!-- Results count -->
                    <div class="text-muted small mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-gift"></i> <?= $total_records ?> <?= $total_records == 1 ? 'gift certificate' : 'gift certificates' ?>
                    </div>

                    <!-- Gift Certificates Table -->
                    <div class="table-responsive">
                        <table class="table custom-table align-middle mb-4">
                            <thead>
                                <tr>
                                    <th style="width: 40px;">#</th>
                                    <th>Customer</th>
                                    <th style="text-align: center;">Gift Certificate Name</th>
                                    <th style="text-align: center;">Gift Certificate Code</th>
                                    <th style="text-align: center;">Purchase Date</th>
                                    <th style="text-align: center;">Amount</th>
                                    <th style="text-align: center;">Status</th>
                                    <th style="width: 100px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $counter = 0;
                                $row_number = $offset + 1;
                                if ($gift_certificates && !$gift_certificates->EOF):
                                    while (!$gift_certificates->EOF):
                                        $PK_GIFT_CERTIFICATE_MASTER = $gift_certificates->fields['PK_GIFT_CERTIFICATE_MASTER'];
                                        $customer_name = !empty($gift_certificates->fields['CUSTOMER_NAME']) ? $gift_certificates->fields['CUSTOMER_NAME'] : '—';
                                        $gift_code = $gift_certificates->fields['GIFT_CERTIFICATE_CODE'];
                                        $gift_name = $gift_certificates->fields['GIFT_CERTIFICATE_NAME'];
                                        $purchase_date = $gift_certificates->fields['DATE_OF_PURCHASE'];
                                        $amount = $gift_certificates->fields['AMOUNT'];
                                        $is_active = $gift_certificates->fields['ACTIVE'] == 1;

                                        $customer = getProfileBadge($customer_name);
                                        $customer_initial = $customer['initials'];
                                        $customer_color = $customer['color'];

                                        // Format date
                                        $formatted_date = !empty($purchase_date) && $purchase_date != '0000-00-00' ? date('M d, Y', strtotime($purchase_date)) : '—';
                                ?>
                                        <tr>
                                            <td class="text-muted small fw-medium"><?= $row_number++ ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span class="avatarname" style="color: #fff; background-color: <?= $customer_color ?>;"><?= $customer_initial; ?></span>
                                                    <div>
                                                        <div class="fw-semibold"><?= htmlspecialchars($customer_name) ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td style="text-align: center;">
                                                <div>
                                                    <span class="fw-medium"><?= htmlspecialchars($gift_name) ?></span>
                                                </div>
                                            </td>
                                            <td style="text-align: center;">
                                                <div>
                                                    <span class="fw-medium"><?= htmlspecialchars($gift_code) ?></span>
                                                </div>
                                            </td>
                                            <td style="text-align: center; vertical-align: middle;">
                                                <div class="date-text">
                                                    <i class="bi bi-calendar3"></i> <?= $formatted_date ?>
                                                </div>
                                            </td>
                                            <td style="text-align: center;">
                                                <span class="amount-badge">
                                                    <i class="bi bi-currency-dollar"></i> <?= number_format($amount, 2) ?>
                                                </span>
                                            </td>
                                            <td style="text-align: center;">
                                                <?php if ($is_active): ?>
                                                    <span class="badge-status badge-active"><i class="bi bi-check-circle-fill"></i> Active</span>
                                                <?php else: ?>
                                                    <span class="badge-status badge-inactive"><i class="bi bi-x-circle-fill"></i> Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="action-icons">
                                                    <a href="javascript:;" onclick="editGiftCertificate(<?= $PK_GIFT_CERTIFICATE_MASTER ?>);" title="Edit">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </a>
                                                    <a href="javascript:;" onclick="downloadGiftCertificate(<?= $PK_GIFT_CERTIFICATE_MASTER ?>);" title="Download PDF">
                                                        <i class="bi bi-download"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php
                                        $gift_certificates->MoveNext();
                                        $counter++;
                                    endwhile;
                                endif;
                                if ($total_records == 0):
                                    ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-5">
                                            <i class="bi bi-gift display-1 text-muted"></i>
                                            <p class="mt-3 text-muted">No gift certificates found for the selected filters</p>
                                            <button class="btn btn-sm btn-outline-primary mt-2" onclick="createNewGiftCertificate()">Create your first gift certificate</button>
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

        // Edit gift certificate
        function editGiftCertificate(id) {
            window.location.href = "gift_certificate.php?id=" + id;
        }

        // Create new gift certificate
        function createNewGiftCertificate() {
            window.location.href = 'gift_certificate.php';
        }

        // Download gift certificate PDF
        function downloadGiftCertificate(PK_GIFT_CERTIFICATE_MASTER) {
            Swal.fire({
                title: 'Generating PDF...',
                text: 'Please wait while we prepare your gift certificate.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                data: {
                    FUNCTION_NAME: 'viewGiftCertificatePdf',
                    PK_GIFT_CERTIFICATE_MASTER: PK_GIFT_CERTIFICATE_MASTER
                },
                success: function(data) {
                    Swal.close();
                    window.open(data, '_blank');
                },
                error: function(error) {
                    Swal.fire('Error!', 'Failed to generate PDF. Please try again.', 'error');
                    console.log(JSON.stringify(error));
                }
            });
        }
    </script>

    <?php
    // Helper functions
    function getGiftInitials($name)
    {
        if ($name == '—' || empty($name)) {
            return 'GC';
        }
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

    function getGiftAvatarColor($index)
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