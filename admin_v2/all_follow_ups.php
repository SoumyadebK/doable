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
<style>
    body {
        background-color: #f8f9fa;
        font-family: 'Inter', sans-serif;
        color: #333;
    }


    .dashboard-container {
        /* max-width: 1400px; */
    }

    /* Sidebar Styles */
    .sidebar-card {
        background: #ffffff;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        min-height: 100%;
    }

    .sidebar-section {
        margin-bottom: 1.5rem;
    }

    .sidebar-section:last-child {
        margin-bottom: 0;
    }

    .section-title {
        font-size: 0.7rem;
        font-weight: 700;
        color: #a0aec0;
        letter-spacing: 0.05em;
        margin-bottom: 0.5rem;
        padding-left: 0.5rem;
    }

    .sidebar-card .nav-link {
        color: #4a5568;
        font-size: 0.85rem;
        font-weight: 500;
        padding: 0.5rem 0.75rem;
        border-radius: 6px;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        transition: all 0.2s ease;
    }

    .sidebar-card .nav-link i {
        font-size: 1rem;
        color: #718096;
    }

    .sidebar-card .nav-link .dot-icon {
        font-size: 1.2rem;
        line-height: 1;
        color: #718096;
        margin-left: 2px;
        margin-right: 2px;
    }

    .sidebar-card .nav-link:hover {
        background-color: #f8fafc;
        color: #1a202c;
    }

    /* Active State for 'Follow Ups' */
    .sidebar-card .nav-link.active {
        background-color: #f1f5f9;
        color: #10b981 !important;
        /* Green icon color as per UI */
        font-weight: 600;
    }

    .sidebar-card .nav-link.active i {
        color: #10b981;
    }

    /* Main Content Area */
    .main-card {
        background: #ffffff;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    }

    .extra-small {
        font-size: 0.65rem;
        letter-spacing: 0.03em;
        font-weight: 600;
    }

    /* Automation Card Component */
    .automation-card {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        background: #ffffff;
    }

    .icon-wrapper {
        width: 40px;
        height: 40px;
        background-color: #f1f5f9;
        border-radius: 8px;
        flex-shrink: 0;
    }

    .icon-wrapper i {
        font-size: 1.1rem;
    }

    /* Custom Bootstrap Form Switch to match UI exactly */
    .custom-switch {
        position: relative;
    }

    .custom-switch .form-check-input {
        width: 2.5em;
        height: 1.35em;
        background-color: #cbd5e1;
        border-color: transparent;
        cursor: pointer;
    }

    .custom-switch .form-check-input:checked {
        background-color: #10b981;
        /* Green color match */
        border-color: transparent;
    }

    .custom-switch .form-check-input:focus {
        box-shadow: none;
        border-color: transparent;
    }

    /* Add Follow Up Button styling */
    .btn-add-followup {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        /* Pillow oval shape like the image */
        color: #4a5568;
        font-size: 0.9rem;
        transition: all 0.2s ease;
    }

    .btn-add-followup:hover {
        background-color: #f8fafc;
        border-color: #cbd5e1;
        color: #1a202c;
    }
</style>

<body>

    <div class="container-fluid py-4 px-4 m-auto mx-auto dashboard-container">
        <div class="row g-4">
            <!-- Sidebar (same as users page) -->
            <div class="col-12 col-md-4 col-xl-3">
                <?php include 'layout/setup_sidebar.php'; ?>
            </div>

            <!-- Main Content -->
            <div class="col-12 col-md-8 col-xl-9">

                <div class="col-12 col-md-8 col-lg-12">
                    <div class="main-card p-4 h-100">

                        <div class="main-header border-bottom pb-3 mb-4">
                            <h2 class="h4 mb-1 fw-semibold text-dark">Automations</h2>
                            <p class="text-muted small mb-0">Enable automatic to-do's</p>
                        </div>

                        <div class="automation-card p-3 mb-3 d-flex align-items-start justify-content-between">
                            <div class="d-flex align-items-start gap-3">
                                <div class="icon-wrapper d-flex align-items-center justify-content-center">
                                    <i class="bi bi-lightning-charge-fill text-secondary"></i>
                                </div>
                                <div>
                                    <h3 class="h6 mb-1 fw-semibold text-dark">Trial Class Follow Up</h3>
                                    <p class="text-muted small mb-1">When a customer completes a class and has not purchased a contract</p>
                                    <span class="text-uppercase text-muted extra-small">EDITED 1 DAY AGO</span>
                                </div>
                            </div>

                            <div class="d-flex align-items-center gap-3 pt-1">
                                <div class="form-check form-switch custom-switch d-flex align-items-center gap-2 m-0 p-0">
                                    <input class="form-check-input m-0" type="checkbox" role="switch" id="switch1" checked>
                                    <label class="form-check-label text-dark small fw-medium" for="switch1">On</label>
                                </div>
                                <button class="btn btn-link text-muted p-0 border-0"><i class="bi bi-chevron-right fs-5"></i></button>
                            </div>
                        </div>

                        <button class="btn btn-add-followup w-100 py-2.5 fw-medium" onclick="window.location='add_follow_up.php'">
                            Add Follow Up
                        </button>

                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

</body>

</html>