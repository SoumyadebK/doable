<?php
require_once('../global/config.php');
//require_once('../includes/session_check.php');

$title = "Unsubscribed Customers";
$status_check = 'active';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 8;

// Set status filter
$status = ($status_check == 'active') ? 1 : 0;

// Get location IDs from session
$location_ids = trim($_SESSION['DEFAULT_LOCATION_ID'], ',');
$location_ids = empty($location_ids) ? '0' : preg_replace('/,+/', ',', $location_ids);

// Build query with search and pagination
$offset = ($page - 1) * $per_page;

// Count total records
$count_query = "SELECT COUNT(DISTINCT DOA_USERS.PK_USER) as total 
                FROM DOA_USERS 
                LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER
                WHERE DOA_USER_ROLES.PK_ROLES = 4
                AND IS_UNSUBSCRIBE_FROM_APPOINTMENT_REMINDER_EMAIL = 1
                AND (DOA_USERS.IS_DELETED = 0 || DOA_USERS.IS_DELETED IS NULL) 
                AND DOA_USERS.PK_ACCOUNT_MASTER = " . $_SESSION['PK_ACCOUNT_MASTER'];

if (!empty($search)) {
    $count_query .= " AND (CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) LIKE '%$search%' 
                      OR DOA_USERS.EMAIL_ID LIKE '%$search%')";
}

$total_result = $db->Execute($count_query);
$total_records = $total_result->fields['total'];
$total_pages = ceil($total_records / $per_page);

// Get users for current page
$query = "SELECT DISTINCT DOA_USERS.PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, 
          DOA_USERS.EMAIL_ID, DOA_USERS.PHONE, DOA_USERS.ACTIVE, DOA_USERS.IS_RECIPIENT, DOA_USERS.IS_UNSUBSCRIBE_FROM_APPOINTMENT_REMINDER_EMAIL,
          DOA_USER_MASTER.PRIMARY_LOCATION_ID
          FROM DOA_USERS 
          LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER
          LEFT JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER 
          WHERE DOA_USER_ROLES.PK_ROLES = 4
          AND IS_UNSUBSCRIBE_FROM_APPOINTMENT_REMINDER_EMAIL = 1
          AND (DOA_USERS.IS_DELETED = 0 || DOA_USERS.IS_DELETED IS NULL) 
          AND DOA_USERS.PK_ACCOUNT_MASTER = " . $_SESSION['PK_ACCOUNT_MASTER'];

if (!empty($search)) {
    $query .= " AND (CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) LIKE '%$search%' 
                OR DOA_USERS.EMAIL_ID LIKE '%$search%')";
}

$query .= " ORDER BY DOA_USERS.FIRST_NAME ASC LIMIT $offset, $per_page";

$users = $db->Execute($query);
?>

<!DOCTYPE html>
<html lang="en">
<?php include 'layout/header_script.php'; ?>
<?php include 'layout/header.php'; ?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?> - Setup Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="assets/css/setup-styles.css" rel="stylesheet">
</head>
<style>
    /* Custom green recipient switch */
    .recipient-switch:checked {
        background-color: #39b54a !important;
        border-color: #39b54a !important;
    }

    .recipient-switch:focus {
        border-color: #39b54a !important;
        box-shadow: 0 0 0 0.25rem rgba(57, 181, 74, 0.25) !important;
    }

    .recipient-switch.form-check-input {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3e%3ccircle r='3' fill='rgba%280,0,0,0.25%29'/%3e%3c/svg%3e") !important;
    }

    .recipient-switch.form-check-input:checked {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3e%3ccircle r='3' fill='%23fff'/%3e%3c/svg%3e") !important;
    }

    /* Make the switch track green */
    .recipient-switch.form-check-input {
        background-color: #e9ecef;
        border-color: #ced4da;
        width: 2.5em;
        height: 1.5em;
        margin-top: 0;
    }

    .recipient-switch.form-check-input:checked {
        background-color: #39b54a !important;
        border-color: #39b54a !important;
    }

    /* Optional: Add a subtle animation */
    .recipient-switch.form-check-input {
        transition: background-color 0.2s ease-in-out, border-color 0.2s ease-in-out;
    }
</style>

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
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <div>
                            <h2 class="fw-semibold h4 mb-1">
                                <i class="bi bi-people me-2" style="color: #39b54a;"></i>Unsubscribed Customers
                            </h2>
                            <p class="text-muted small mb-0">Manage your unsubscribed customers</p>
                        </div>
                        <!-- <button class="btn btn-success-custom rounded-pill d-flex align-items-center gap-2" onclick="window.location.href='user.php'">
                            <i class="bi bi-plus-lg"></i> Create New User
                        </button> -->
                    </div>

                    <!-- Filters -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="search-container">
                            <i class="bi bi-search"></i>
                            <input type="text" class="form-control search-input" placeholder="Search users..."
                                id="searchInput" value="<?= htmlspecialchars($search) ?>">
                        </div>

                        <!-- <div class="status-toggle-group">
                            <button class="status-btn <?= $status_check == 'active' ? 'active' : '' ?>"
                                onclick="window.location.href='?status=active&search=<?= urlencode($search) ?>'">
                                Active
                            </button>
                            <button class="status-btn <?= $status_check == 'inactive' ? 'active' : '' ?>"
                                onclick="window.location.href='?status=inactive&search=<?= urlencode($search) ?>'">
                                Not Active
                            </button>
                        </div> -->
                    </div>

                    <!-- Results count -->
                    <div class="text-muted small mb-3"><?= $total_records ?> <?= $total_records == 1 ? 'customer' : 'customers' ?></div>

                    <!-- Users Table -->
                    <div class="table-responsive">
                        <table class="table custom-table align-middle mb-4">
                            <thead>
                                <tr>
                                    <th style="text-align: center;">Name</th>
                                    <th style="text-align: center;">Email</th>
                                    <th style="text-align: center;">Phone</th>
                                    <th style="text-align: center;">Location</th>
                                    <th style="text-align: center;">Subscribe Again</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $counter = 0;
                                while (!$users->EOF):
                                    $PK_USER = $users->fields['PK_USER'];
                                    $customer = getProfileBadge($users->fields['NAME']);
                                    $customer_initial = $customer['initials'];
                                    $customer_color = $customer['color'];

                                    // Get locations
                                    $locations = [];
                                    $loc_query = $db->Execute("SELECT DOA_LOCATION.LOCATION_NAME FROM DOA_LOCATION WHERE PK_LOCATION = '{$users->fields['PRIMARY_LOCATION_ID']}'");
                                    while (!$loc_query->EOF) {
                                        $locations[] = $loc_query->fields['LOCATION_NAME'];
                                        $loc_query->MoveNext();
                                    }
                                ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <span class="avatarname" style="color: #fff; background-color: <?= $customer_color ?>;"><?= $customer_initial; ?></span>
                                                <div>
                                                    <div class="fw-semibold"><?= htmlspecialchars($users->fields['NAME']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center"><?= htmlspecialchars($users->fields['EMAIL_ID']) ?></td>
                                        <td class="text-center"><?= htmlspecialchars($users->fields['PHONE']) ?></td>
                                        <td class="text-center"><?= implode(', ', array_slice($locations, 0, 2)) ?><?= count($locations) > 2 ? '...' : '' ?></td>
                                        <td class="text-center">
                                            <div class="form-check form-switch d-flex justify-content-center align-items-center m-0">
                                                <input class="form-check-input recipient-switch" type="checkbox" role="switch" data-user-id="<?= $PK_USER ?>" <?= ($users->fields['IS_UNSUBSCRIBE_FROM_APPOINTMENT_REMINDER_EMAIL'] == 0) ? 'checked' : '' ?>>
                                            </div>
                                        </td>
                                    </tr>
                                <?php
                                    $users->MoveNext();
                                    $counter++;
                                endwhile;

                                if ($total_records == 0): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5">
                                            <i class="bi bi-inbox display-1 text-muted"></i>
                                            <p class="mt-3 text-muted">No unsubscribed customers found</p>
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
                                        <a class="page-link border-0" href="?page=1&status=<?= $status_check ?>&search=<?= urlencode($search) ?>" aria-label="First">
                                            <i class="bi bi-chevron-double-left"></i>
                                        </a>
                                    </li>
                                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                        <a class="page-link border-0" href="?page=<?= $page - 1 ?>&status=<?= $status_check ?>&search=<?= urlencode($search) ?>" aria-label="Previous">
                                            <i class="bi bi-chevron-left"></i>
                                        </a>
                                    </li>

                                    <?php
                                    $start_page = max(1, $page - 2);
                                    $end_page = min($total_pages, $page + 2);

                                    if ($start_page > 1): ?>
                                        <li class="page-item"><a class="page-link" href="?page=1&status=<?= $status_check ?>&search=<?= urlencode($search) ?>">1</a></li>
                                        <?php if ($start_page > 2): ?>
                                            <li class="page-item disabled"><span class="page-link border-0 bg-transparent">...</span></li>
                                        <?php endif; ?>
                                    <?php endif;

                                    for ($i = $start_page; $i <= $end_page; $i++): ?>
                                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                            <a class="page-link" href="?page=<?= $i ?>&status=<?= $status_check ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor;

                                    if ($end_page < $total_pages): ?>
                                        <?php if ($end_page < $total_pages - 1): ?>
                                            <li class="page-item disabled"><span class="page-link border-0 bg-transparent">...</span></li>
                                        <?php endif; ?>
                                        <li class="page-item"><a class="page-link" href="?page=<?= $total_pages ?>&status=<?= $status_check ?>&search=<?= urlencode($search) ?>"><?= $total_pages ?></a></li>
                                    <?php endif; ?>

                                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                        <a class="page-link border-0" href="?page=<?= $page + 1 ?>&status=<?= $status_check ?>&search=<?= urlencode($search) ?>" aria-label="Next">
                                            <i class="bi bi-chevron-right"></i>
                                        </a>
                                    </li>
                                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                        <a class="page-link border-0" href="?page=<?= $total_pages ?>&status=<?= $status_check ?>&search=<?= urlencode($search) ?>" aria-label="Last">
                                            <i class="bi bi-chevron-double-right"></i>
                                        </a>
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
        function editpage(id) {
            window.location.href = "user.php?id=" + id;
        }

        // Helper functions
        function getInitials(name) {
            return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
        }

        // Search functionality
        let searchTimeout;
        $('#searchInput').on('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                window.location.href = '?status=<?= $status_check ?>&search=' + encodeURIComponent($(this).val()) + '&per_page=<?= $per_page ?>';
            }, 500);
        });

        // Per page change
        $('#perPageSelect').on('change', function() {
            window.location.href = '?status=<?= $status_check ?>&search=<?= urlencode($search) ?>&per_page=' + $(this).val();
        });

        // Select all functionality
        $('#selectAll').on('change', function() {
            $('.user-checkbox').prop('checked', $(this).prop('checked'));
        });

        // Recipient toggle
        $('.recipient-switch').on('change', function() {
            const userId = $(this).data('user-id');
            const isSubscribe = $(this).prop('checked') ? 1 : 0;

            $.ajax({
                url: 'ajax/AjaxFunctions.php',
                type: 'POST',
                data: {
                    FUNCTION_NAME: 'subscribeToEmailReminder',
                    PK_USER: userId,
                    IS_SUBSCRIBED: isSubscribe
                },
                success: function(data) {
                    // Optional: Show success message
                },
                error: function() {
                    // Revert on error
                    $(this).prop('checked', !$(this).prop('checked'));
                    alert('Error updating recipient status');
                }
            });
        });

        // Delete user
        function deleteUser(userId) {
            if (confirm('Are you sure you want to delete this user?')) {
                window.location.href = 'delete.php?id=' + userId;
            }
        }
    </script>

</body>

</html>

<?php
function getInitials($name)
{
    $words = explode(' ', $name);
    $initials = '';
    foreach ($words as $word) {
        if (!empty($word)) {
            $initials .= strtoupper($word[0]);
        }
        if (strlen($initials) >= 2) break;
    }
    return $initials;
}

function getAvatarColor($index)
{
    $colors = ['#fef08a', '#fed7aa', '#bfdbfe', '#ddd6fe', '#fbcfe8', '#bbf7d0'];
    return $colors[$index % count($colors)];
}
?>