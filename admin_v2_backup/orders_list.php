<!DOCTYPE html>
<html lang="en">
<?php
// Start output buffering to prevent header issues
ob_start();

// Ensure no whitespace or output before this point
require_once('../global/config.php');

global $db;
global $db_account;
global $master_database;

// Check if database connections are valid
if (!$db_account) {
    die("Database connection failed. Please check your configuration.");
}

$DEFAULT_LOCATION_ID = isset($_SESSION['DEFAULT_LOCATION_ID']) ? $_SESSION['DEFAULT_LOCATION_ID'] : '';

$title = "All Orders";

// Check user authentication
if (!isset($_SESSION['PK_USER']) || $_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

$header_text = '';
if ($db) {
    $header_data = $db->Execute("SELECT * FROM `DOA_HEADER_TEXT` WHERE ACTIVE = 1 AND HEADER_TITLE = 'Orders Page'");
    if ($header_data && $header_data->RecordCount() > 0) {
        $header_text = $header_data->fields['HEADER_TEXT'];
    }
}

// Fetch categories for dropdown (for product modal)
$categories = [];
$cat_result = $db_account->Execute("SELECT PK_CATEGORY, CATEGORY_NAME FROM DOA_CATEGORY WHERE IS_DELETED = 0 AND ACTIVE = 1 ORDER BY CATEGORY_NAME ASC");
if ($cat_result && $cat_result->RecordCount() > 0) {
    while (!$cat_result->EOF) {
        $categories[] = $cat_result->fields;
        $cat_result->MoveNext();
    }
}
?>

<?php include 'layout/header_script.php'; ?>
<?php require_once('../includes/header.php'); ?>
<?php include 'layout/header.php'; ?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Dashboard | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Inter', sans-serif;
            color: #333;
        }

        .btn-success-custom {
            background-color: #00b633;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: background-color 0.2s ease;
        }

        .btn-success-custom:hover {
            background-color: #00992b;
            color: #ffffff;
        }

        .metric-card {
            border: 0px !important;
            box-shadow: none !important;
            position: relative;
        }

        .metric-card::after {
            content: "";
            height: 56px;
            width: 2px;
            border: 0px;
            position: absolute;
            display: block;
            right: 0;
            top: 20px;
            background: #ddd;
        }

        .metric-card span.fs-2 {
            letter-spacing: -0.5px;
        }

        .bg-success-light {
            background-color: #e6f8ed !important;
            color: #00b633 !important;
            font-size: 0.8rem;
            padding: 4px 8px;
            border-radius: 4px;
        }

        .bg-danger-light {
            background-color: #fdebee !important;
            color: #ee4444 !important;
            font-size: 0.8rem;
            padding: 4px 8px;
            border-radius: 4px;
        }

        .search-wrapper .form-control {
            border-color: #dee2e6;
            font-size: 0.95rem;
        }

        .search-wrapper .input-group-text {
            border-color: #dee2e6;
        }

        .btn-white {
            border-color: #dee2e6 !important;
            color: #4a4a4a;
            font-size: 0.9rem;
            padding: 0.55rem 1rem;
            border-radius: 8px;
            background-color: white;
        }

        .custom-table {
            border: 1px solid #f0f0f0;
        }

        .custom-table thead th {
            background-color: #f4f6f8;
            color: #6c757d;
            font-weight: 500;
            font-size: 0.85rem;
            padding: 12px 16px;
            border-bottom: 1px solid #e9ecef;
        }

        .custom-table tbody td {
            padding: 14px 16px;
            font-size: 0.9rem;
            border-bottom: 1px solid #f1f3f5;
            vertical-align: middle;
        }

        .form-check-input {
            border-color: #ced4da;
            cursor: pointer;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            font-size: 0.8rem;
            font-weight: 500;
            padding: 6px 12px;
            border-radius: 30px;
            background-color: #f2f4f6;
            color: #2c3e50;
        }

        .status-badge i {
            margin-right: 5px;
        }

        .avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: bold;
            flex-shrink: 0;
        }

        .bg-orange-light {
            background-color: #fef3e6;
        }

        .text-orange {
            color: #f2994a;
        }

        .bg-pink-light {
            background-color: #fcebee;
        }

        .text-pink {
            color: #eb5757;
        }

        .bg-blue-light {
            background-color: #eaf2fe;
        }

        .text-blue {
            color: #2f80ed;
        }

        .bg-teal-light {
            background-color: #e6f7f4;
        }

        .text-teal {
            color: #27ae60;
        }

        .bg-purple-light {
            background-color: #f3eafb;
        }

        .text-purple {
            color: #9b51e0;
        }

        .bg-indigo-light {
            background-color: #ededfc;
        }

        .text-indigo {
            color: #56ccf2;
        }

        .btn-success {
            background-color: #00B739;
            border: none;
        }

        .product-list-sm {
            font-size: 0.8rem;
            line-height: 1.3;
            max-width: 220px;
        }

        .product-list-sm div {
            white-space: normal;
        }

        .action-icons a {
            color: #6c757d;
            margin: 0 4px;
            transition: 0.2s;
            font-size: 1.2rem;
        }

        .action-icons a:hover {
            color: #00b633;
        }

        .clickable-row {
            cursor: pointer;
        }

        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #dee2e6;
            border-radius: 30px;
            padding: 6px 12px;
        }

        .table-striped>tbody>tr:nth-of-type(odd)>* {
            background-color: #fafbfc;
        }

        .container {
            max-width: 1874px;
        }

        /* Product Modal Styles (copied from products page) */
        .custom-drawer {
            width: 460px !important;
            border-left: 1px solid #eaeaea;
            box-shadow: -4px 0 30px rgba(0, 0, 0, 0.05);
            border-radius: 20px 0 0 20px;
        }

        .drawer-header-content {
            padding: 24px 24px 16px 24px;
        }

        .drawer-title {
            font-size: 20px;
            font-weight: 600;
            color: #111111;
        }

        .drawer-subtitle {
            font-size: 14px;
            color: #6c757d;
        }

        .drawer-nav-tabs {
            border-bottom: 1px solid #f0f0f0;
            padding: 0 24px;
        }

        .drawer-nav-tabs .nav-link {
            border: none;
            color: #6c757d;
            font-size: 14px;
            font-weight: 500;
            padding: 12px 4px;
            margin-right: 20px;
            background: transparent;
            position: relative;
        }

        .drawer-nav-tabs .nav-link.active {
            color: #000000;
            font-weight: 600;
            background: transparent;
        }

        .drawer-nav-tabs .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 2px;
            background-color: #00b040;
        }

        .drawer-body-scroll {
            padding: 24px;
            overflow-y: auto;
            height: calc(100vh - 170px);
        }

        .form-label-custom {
            font-size: 14px;
            font-weight: 500;
            color: #111111;
            margin-bottom: 8px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .form-label-custom i {
            color: #ccc;
            font-size: 13px;
        }

        .form-control-custom {
            border: 1px solid #e2e2e2;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 14px;
            color: #333333;
            background-color: #ffffff;
        }

        .form-control-custom:focus {
            border-color: #00b040;
            box-shadow: 0 0 0 3px rgba(0, 176, 64, 0.1);
        }

        .form-info-text {
            font-size: 12px;
            color: #8a8a8a;
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .character-counter {
            font-size: 11px;
            color: #a0a0a0;
            text-align: right;
            margin-top: -22px;
            padding-right: 12px;
            position: relative;
            z-index: 5;
        }

        .section-divider-banner {
            background-color: #f8f9fa;
            font-size: 11px;
            font-weight: 600;
            color: #8a8a8a;
            letter-spacing: 0.5px;
            padding: 8px 24px;
            text-transform: uppercase;
            margin-left: -24px;
            margin-right: -24px;
        }

        .drawer-footer-actions {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 16px 24px;
            border-top: 1px solid #eaeaea;
            background-color: #ffffff;
            display: flex;
            gap: 12px;
        }

        .btn-cancel-custom {
            border: 1px solid #e2e2e2;
            background-color: #ffffff;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 500;
            color: #555555;
            padding: 10px 0;
            flex: 1;
        }

        .btn-cancel-custom:hover {
            background-color: #f8f9fa;
        }

        .btn-save-custom {
            background-color: #00b040;
            border: none;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 500;
            color: #ffffff;
            padding: 10px 0;
            flex: 1;
        }

        .btn-save-custom:hover {
            background-color: #009636;
        }

        .sub-menu {
            padding: 10px 10px;
            font-size: 14px;
        }

        .sub-menu a {
            padding: 5px;
            display: block;
            border-radius: 5px;
        }

        .sub-menu a:hover {
            color: #333;
            background-color: #ddd;
        }

        a {
            color: black;
            text-decoration: none;
        }

        /* Add to Cart Modal (reused) */
        .number {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 15px;
        }

        .number .btn {
            width: 30px;
            height: 30px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 15px;
        }

        .counter_input {
            width: 60px;
            text-align: center;
            border: 1px solid #dee2e6;
            border-radius: 15px;
            padding: 8px;
            font-size: 1rem;
        }
    </style>
</head>

<body>

    <div class="container py-4 px-4 bg-white m-3 rounded border mx-auto dashboard-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center gap-3">
                <div class="icon-box bg-white border rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                    <i class="bi bi-clock-history text-secondary fs-5"></i>
                </div>
                <div>
                    <h1 class="h4 fw-bold mb-0 text-dark">Orders</h1>
                    <p class="text-muted small mb-0">Manage and track your orders</p>
                </div>
            </div>
            <!-- Updated button to open the New Product drawer (offcanvas) -->
            <button class="btn btn-success rounded-pill px-3" data-bs-toggle="offcanvas" data-bs-target="#editProductDrawer" onclick="openNewProductDrawer();">
                <i class="bi bi-plus-lg"></i> New Product
            </button>
        </div>

        <?php
        // Initialize metrics with error checking
        $totalOrders = 0;
        $totalRevenue = 0;
        $pendingCount = 0;

        if ($db_account) {
            $metricsQuery = $db_account->Execute("SELECT PK_ORDER, ORDER_TOTAL_AMOUNT, PK_ORDER_STATUS FROM DOA_ORDER");
            if ($metricsQuery && $metricsQuery->RecordCount() > 0) {
                $totalOrders = $metricsQuery->RecordCount();
                while (!$metricsQuery->EOF) {
                    $amount = floatval($metricsQuery->fields['ORDER_TOTAL_AMOUNT'] ?? 0);
                    $totalRevenue += $amount;
                    $metricsQuery->MoveNext();
                }
            }

            $pendingQ = $db_account->Execute("SELECT COUNT(*) AS PENDING_CNT FROM DOA_ORDER O LEFT JOIN $master_database.DOA_ORDER_STATUS S ON O.PK_ORDER_STATUS = S.PK_ORDER_STATUS WHERE LOWER(S.STATUS) LIKE '%pending%' OR LOWER(S.STATUS) LIKE '%processing%'");
            $pendingCount = ($pendingQ && $pendingQ->RecordCount() > 0) ? intval($pendingQ->fields['PENDING_CNT']) : 0;
        }

        $avgOrderValue = ($totalOrders > 0) ? round($totalRevenue / $totalOrders, 2) : 0;
        ?>

        <div class="row g-3 mb-4 text-nowrap">
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card metric-card p-3 border-0 shadow-sm h-100">
                    <p class="text-muted small mb-1">Total Orders</p>
                    <div class="d-flex align-items-baseline gap-2">
                        <span class="fs-2 fw-semibold"><?= number_format($totalOrders) ?></span>
                        <span class="badge text-success font-weight-normal bg-success-light">+12% <span class="text-muted fw-normal">vs last week</span></span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card metric-card p-3 border-0 shadow-sm h-100">
                    <p class="text-muted small mb-1">Total Revenue</p>
                    <div class="d-flex align-items-baseline gap-2">
                        <span class="fs-2 fw-semibold">$<?= number_format($totalRevenue, 2) ?></span>
                        <span class="badge text-success font-weight-normal bg-success-light">+8% <span class="text-muted fw-normal">vs last week</span></span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card metric-card p-3 border-0 shadow-sm h-100">
                    <p class="text-muted small mb-1">Average Order Value</p>
                    <div class="d-flex align-items-baseline gap-2">
                        <span class="fs-2 fw-semibold">$<?= number_format($avgOrderValue, 2) ?></span>
                        <span class="badge text-danger font-weight-normal bg-danger-light">-2% <span class="text-muted fw-normal">this week</span></span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card metric-card p-3 border-0 shadow-sm h-100">
                    <p class="text-muted small mb-1">Pending Orders</p>
                    <div class="d-flex align-items-baseline gap-2">
                        <span class="fs-2 fw-semibold"><?= $pendingCount ?></span>
                        <span class="text-muted small fw-normal ms-1">Requires attention</span>
                    </div>
                </div>
            </div>
        </div>

        <hr class="border-secondary-subtle my-4">

        <?php if (!empty($header_text)): ?>
            <div class="row mb-3" style="text-align: center;">
                <div class="col-12">
                    <h5 style="font-weight: bold; background: #f1f9fe; padding: 8px; border-radius: 12px;"><?= htmlspecialchars($header_text) ?></h5>
                </div>
            </div>
        <?php endif; ?>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div class="d-flex flex-wrap align-items-center gap-2 flex-grow-1" style="max-width: 700px;">
                <div class="search-wrapper" style="width: 250px;">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted py-2"><i class="bi bi-search"></i></span>
                        <input type="text" id="customSearchBox" class="form-control border-start-0 ps-0 text-muted shadow-none py-2" placeholder="Search orders...">
                    </div>
                </div>
                <div class="border rounded-3 bg-white px-3 py-2 text-muted small d-flex align-items-center gap-2">
                    <i class="bi bi-calendar3"></i>
                    <span id="dateRangeLabel">All time orders</span>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-white border bg-white d-flex align-items-center gap-2 px-3" id="filterBtn"><i class="bi bi-sliders"></i> Filter</button>
                <button class="btn btn-white border bg-white d-flex align-items-center gap-2 px-3" id="exportBtn"><i class="bi bi-box-arrow-up"></i> Export</button>
            </div>
        </div>

        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="table-responsive">
                <table id="ordersDataTable" class="table table-hover align-middle mb-0 custom-table w-100">
                    <thead>
                        <tr>
                            <th style="width: 40px;"><input class="form-check-input ms-1 shadow-none" type="checkbox" id="selectAllCheck"></th>
                            <th>Order ID</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Customer</th>
                            <th>Order Type</th>
                            <th>Purchased (Products)</th>
                            <th class="text-end">Revenue</th>
                            <th style="width: 80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($db_account && $master_database) {
                            $ordersQuery = $db_account->Execute("SELECT DOA_ORDER.*, DOA_ORDER_STATUS.STATUS, DOA_ORDER_STATUS.COLOR_CODE, 
                        CONCAT(CUSTOMER.FIRST_NAME, ' ', CUSTOMER.LAST_NAME) AS CUSTOMER_NAME 
                        FROM `DOA_ORDER` 
                        LEFT JOIN $master_database.DOA_ORDER_STATUS AS DOA_ORDER_STATUS ON DOA_ORDER.PK_ORDER_STATUS = DOA_ORDER_STATUS.PK_ORDER_STATUS 
                        LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_ORDER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER 
                        LEFT JOIN $master_database.DOA_USERS AS CUSTOMER ON DOA_USER_MASTER.PK_USER = CUSTOMER.PK_USER 
                        ORDER BY DOA_ORDER.CREATED_ON DESC");

                            if ($ordersQuery && $ordersQuery->RecordCount() > 0) {
                                while (!$ordersQuery->EOF) {
                                    $pk_order = $ordersQuery->fields['PK_ORDER'];
                                    $orderIdDisplay = $ordersQuery->fields['ORDER_ID'];
                                    $createdOn = $ordersQuery->fields['CREATED_ON'];
                                    $orderDateFormatted = date('m/d/Y h:i A', strtotime($createdOn));
                                    $customerName = !empty($ordersQuery->fields['CUSTOMER_NAME']) ? $ordersQuery->fields['CUSTOMER_NAME'] : 'Guest';
                                    $orderType = ucwords(strtolower(str_replace('_', ' ', $ordersQuery->fields['ORDER_TYPE'])), ' ');
                                    $orderStatus = $ordersQuery->fields['STATUS'] ?? 'Unknown';
                                    $colorCode = $ordersQuery->fields['COLOR_CODE'] ?? '#6c757d';
                                    $totalAmount = floatval($ordersQuery->fields['ORDER_TOTAL_AMOUNT'] ?? 0);

                                    $product_details = $db_account->Execute("SELECT DOA_ORDER_ITEM.*, DOA_PRODUCT.PRODUCT_NAME, DOA_PRODUCT_SIZE.SIZE, DOA_PRODUCT_COLOR.COLOR 
                                FROM `DOA_ORDER_ITEM` 
                                LEFT JOIN DOA_PRODUCT ON DOA_ORDER_ITEM.PK_PRODUCT = DOA_PRODUCT.PK_PRODUCT 
                                LEFT JOIN DOA_PRODUCT_SIZE ON DOA_ORDER_ITEM.PK_PRODUCT_SIZE = DOA_PRODUCT_SIZE.PK_PRODUCT_SIZE 
                                LEFT JOIN DOA_PRODUCT_COLOR ON DOA_ORDER_ITEM.PK_PRODUCT_COLOR = DOA_PRODUCT_COLOR.PK_PRODUCT_COLOR 
                                WHERE DOA_ORDER_ITEM.PK_ORDER = " . intval($pk_order));

                                    $productHtml = '<div class="product-list-sm">';
                                    if ($product_details && $product_details->RecordCount() > 0) {
                                        while (!$product_details->EOF) {
                                            $prodName = $product_details->fields['PRODUCT_NAME'] ?? 'Product';
                                            $color = $product_details->fields['COLOR'] ?? '';
                                            $size = $product_details->fields['SIZE'] ?? '';
                                            $qty = $product_details->fields['PRODUCT_QUANTITY'] ?? 1;
                                            $productHtml .= htmlspecialchars($prodName) . ' <span class="text-muted">(' . htmlspecialchars($color) . ', ' . htmlspecialchars($size) . ') x' . $qty . '</span><br>';
                                            $product_details->MoveNext();
                                        }
                                    } else {
                                        $productHtml .= '<span class="text-muted">No items</span>';
                                    }
                                    $productHtml .= '</div>';

                                    $initials = '';
                                    $nameParts = explode(' ', trim($customerName));
                                    if (count($nameParts) >= 2) {
                                        $initials = strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1));
                                    } else {
                                        $initials = strtoupper(substr($customerName, 0, 2));
                                    }
                                    $bgClasses = ['bg-orange-light text-orange', 'bg-pink-light text-pink', 'bg-blue-light text-blue', 'bg-teal-light text-teal', 'bg-purple-light text-purple', 'bg-indigo-light text-indigo'];
                                    $avatarBgClass = $bgClasses[abs(crc32($customerName)) % 6];
                        ?>
                                    <tr class="clickable-row" data-order-id="<?= $pk_order ?>">
                                        <td><input class="form-check-input order-checkbox shadow-none" type="checkbox" data-orderid="<?= $pk_order ?>"></td>
                                        <td class="fw-medium text-dark"><?= htmlspecialchars($orderIdDisplay) ?></td>
                                        <td class="text-secondary"><?= $orderDateFormatted ?></td>
                                        <td><span class="status-badge" style="background-color: <?= htmlspecialchars($colorCode) ?>20; color: <?= htmlspecialchars($colorCode) ?>;"><i class="bi bi-circle-fill me-1" style="font-size: 0.6rem;"></i> <?= htmlspecialchars($orderStatus) ?></span></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="avatar <?= $avatarBgClass ?>"><?= htmlspecialchars($initials) ?></div>
                                                <span class="text-dark fw-medium"><?= htmlspecialchars($customerName) ?></span>
                                            </div>
                                        </td>
                                        <td><span class="text-muted"><?= htmlspecialchars($orderType) ?></span></td>
                                        <td><?= $productHtml ?></td>
                                        <td class="text-end fw-medium text-dark">$<?= number_format($totalAmount, 2) ?></td>
                                        <td class="action-icons">
                                            <a href="order_details.php?id=<?= $pk_order ?>" class="me-2" title="Details"><i class="fa fa-info-circle"></i></a>
                                            <a href="product_receipt.php?id=<?= $pk_order ?>" target="_blank" title="Receipt"><i class="fa fa-file-alt"></i></a>
                                        </td>
                                    </tr>
                        <?php
                                    $ordersQuery->MoveNext();
                                }
                            } else {
                                echo '<tr><td colspan="8" class="text-center">No orders found.</td></tr>';
                            }
                        } else {
                            echo '<tr><td colspan="8" class="text-center text-danger">Database connection error. Please check configuration.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- =============================================== -->
    <!-- ADD NEW PRODUCT MODAL (Offcanvas from products page) -->
    <!-- =============================================== -->
    <div class="offcanvas offcanvas-end custom-drawer" tabindex="-1" id="editProductDrawer" aria-labelledby="editProductDrawerLabel">
        <form id="productForm" method="post" enctype="multipart/form-data">
            <input type="hidden" name="FUNCTION_NAME" id="FUNCTION_NAME" value="saveProduct">
            <input type="hidden" name="PK_PRODUCT" id="PK_PRODUCT" value="">

            <div class="drawer-header-content position-relative">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h5 class="drawer-title" id="editProductDrawerLabel">New Product</h5>
                        <p class="drawer-subtitle mb-0">Manage your product details.</p>
                    </div>
                    <button type="button" class="btn btn-link p-0 text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-three-dots-vertical fs-5"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-menu-item dropdown-item fs-14 text-danger" href="javascript:" id="deleteProductBtn" onclick="deleteCurrentProduct();">Delete Product</a></li>
                        <li><a class="dropdown-menu-item dropdown-item fs-14" href="javascript:" onclick="duplicateProduct();">Duplicate</a></li>
                    </ul>
                </div>
            </div>

            <ul class="nav nav-tabs drawer-nav-tabs" id="productDrawerTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="details-tab" data-bs-toggle="tab" data-bs-target="#details-content" type="button" role="tab">General Details</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="images-tab" data-bs-toggle="tab" data-bs-target="#images-content" type="button" role="tab">Product Images</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="size-color-tab" data-bs-toggle="tab" data-bs-target="#size-color-content" type="button" role="tab">Size & Color</button>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="details-content" role="tabpanel">
                    <div class="drawer-body-scroll">
                        <div class="mb-4">
                            <label class="form-label-custom">Product Name <i class="bi bi-info-circle-fill"></i></label>
                            <input type="text" class="form-control form-control-custom" name="PRODUCT_NAME" id="PRODUCT_NAME" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label-custom">Product ID / SKU <i class="bi bi-info-circle-fill"></i></label>
                            <input type="text" class="form-control form-control-custom" name="PRODUCT_ID" id="PRODUCT_ID">
                        </div>
                        <div class="mb-4">
                            <label class="form-label-custom">Brand</label>
                            <input type="text" class="form-control form-control-custom" name="BRAND" id="BRAND">
                        </div>
                        <div class="mb-4">
                            <label class="form-label-custom">Category</label>
                            <input type="text" class="form-control form-control-custom" name="CATEGORY" id="CATEGORY">
                        </div>
                        <div class="mb-4">
                            <label class="form-label-custom">Description</label>
                            <textarea class="form-control form-control-custom" name="PRODUCT_DESCRIPTION" id="PRODUCT_DESCRIPTION" rows="4" maxlength="500"></textarea>
                            <div class="character-counter"><span id="charCount">0</span>/500</div>
                        </div>
                        <div class="section-divider-banner mb-4">Price & Stock</div>
                        <div class="mb-4">
                            <label class="form-label-custom">Price ($)</label>
                            <input type="number" step="0.01" class="form-control form-control-custom" name="PRICE" id="PRICE">
                        </div>
                        <div class="mb-4">
                            <label class="form-label-custom">Weight</label>
                            <input type="text" class="form-control form-control-custom" name="WEIGHT" id="WEIGHT" placeholder="e.g., 1.5 kg">
                        </div>
                        <div class="mb-4">
                            <label class="form-label-custom">Shipping Information</label>
                            <textarea class="form-control form-control-custom" name="SHIPPING_INFORMATION" id="SHIPPING_INFORMATION" rows="2"></textarea>
                        </div>
                        <div class="mb-4">
                            <label class="form-label-custom">Status</label>
                            <select class="form-select form-control-custom" name="ACTIVE" id="ACTIVE">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="images-content" role="tabpanel">
                    <div class="drawer-body-scroll">
                        <div class="mb-4">
                            <label class="form-label-custom">Product Image</label>
                            <input type="file" class="form-control form-control-custom" name="PRODUCT_IMAGES_FILE" id="PRODUCT_IMAGES_FILE" accept="image/*">
                            <div id="currentImagePreview" class="mt-3" style="display: none;">
                                <p class="small text-muted">Current Image:</p>
                                <img id="currentImage" src="" style="max-width: 100%; max-height: 150px; border-radius: 8px;">
                                <input type="hidden" name="EXISTING_IMAGE" id="EXISTING_IMAGE" value="">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="size-color-content" role="tabpanel">
                    <div class="drawer-body-scroll">
                        <div class="mb-4">
                            <label class="form-label-custom">Size / Dimensions</label>
                            <div id="add_more_size"></div>
                            <button type="button" class="btn btn-outline-secondary btn-sm mt-2" onclick="addMoreSize();">
                                <i class="bi bi-plus-lg"></i> Add Size
                            </button>
                        </div>
                        <div class="mb-4">
                            <label class="form-label-custom">Color</label>
                            <div id="add_more_color"></div>
                            <button type="button" class="btn btn-outline-secondary btn-sm mt-2" onclick="addMoreColor();">
                                <i class="bi bi-plus-lg"></i> Add Color
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="drawer-footer-actions">
                <button type="button" class="btn btn-cancel-custom" data-bs-dismiss="offcanvas">Cancel</button>
                <button type="submit" class="btn btn-save-custom">Save Changes</button>
            </div>
        </form>
    </div>

    <!-- Add To Cart Modal (Retained for consistency, though not primary) -->
    <div class="modal fade" id="add_to_cart" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog" style="width: 500px;">
            <form id="add_to_cart_form" method="post">
                <input type="hidden" name="FUNCTION_NAME" value="addToCart">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4><b>Add To Cart</b></h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row" id="item_details"></div>
                        <div class="number">
                            <span class="minus btn btn-success border-0 waves-effect waves-light text-white">-</span>
                            <input class="counter_input" inputmode="numeric" oninput="this.value = this.value.replace(/\D+/g, '')" id="PRODUCT_QUANTITY" name="PRODUCT_QUANTITY" value="1" />
                            <span class="plus btn btn-success border-0 waves-effect waves-light text-white">+</span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary border-0 rounded-pill" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-success border-0 rounded-pill">Add to Cart</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentProductId = null;
        let editProductOffcanvas;

        document.addEventListener('DOMContentLoaded', function() {
            const offcanvasElement = document.getElementById('editProductDrawer');
            if (offcanvasElement) {
                editProductOffcanvas = new bootstrap.Offcanvas(offcanvasElement);
            }
            const descTextarea = document.getElementById('PRODUCT_DESCRIPTION');
            if (descTextarea) {
                descTextarea.addEventListener('input', function() {
                    document.getElementById('charCount').innerText = this.value.length;
                });
            }
        });

        function openNewProductDrawer() {
            currentProductId = null;
            document.getElementById('FUNCTION_NAME').value = 'saveProduct';
            document.getElementById('PK_PRODUCT').value = '';
            document.getElementById('PRODUCT_NAME').value = '';
            document.getElementById('PRODUCT_ID').value = '';
            document.getElementById('BRAND').value = '';
            document.getElementById('CATEGORY').value = '';
            document.getElementById('PRODUCT_DESCRIPTION').value = '';
            document.getElementById('charCount').innerText = '0';
            document.getElementById('PRICE').value = '';
            document.getElementById('WEIGHT').value = '';
            document.getElementById('SHIPPING_INFORMATION').value = '';
            document.getElementById('ACTIVE').value = '1';
            document.getElementById('PRODUCT_IMAGES_FILE').value = '';
            document.getElementById('currentImagePreview').style.display = 'none';
            document.getElementById('EXISTING_IMAGE').value = '';
            document.getElementById('editProductDrawerLabel').innerText = 'New Product';
            $('#add_more_size').empty();
            $('#add_more_color').empty();
            const firstTab = document.querySelector('#details-tab');
            if (firstTab) new bootstrap.Tab(firstTab).show();
            if (editProductOffcanvas) editProductOffcanvas.show();
        }

        document.getElementById('productForm').addEventListener('submit', function(e) {
            e.preventDefault();
            let formData = new FormData(this);
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    try {
                        let result = JSON.parse(response);
                        //alert(result.message || 'Product saved successfully');
                        if (editProductOffcanvas) editProductOffcanvas.hide();
                        window.location.reload();
                    } catch (e) {
                        alert('Error saving product');
                    }
                },
                error: function() {
                    alert('Error saving product');
                }
            });
        });

        function deleteCurrentProduct() {
            if (!currentProductId && document.getElementById('PK_PRODUCT').value) currentProductId = document.getElementById('PK_PRODUCT').value;
            if (!currentProductId) {
                alert('No product selected');
                return;
            }
            if (confirm('Are you sure you want to delete this product?')) {
                $.ajax({
                    url: "ajax/AjaxFunctions.php",
                    type: 'POST',
                    data: {
                        FUNCTION_NAME: 'deleteProductData',
                        PK_PRODUCT: currentProductId
                    },
                    success: function() {
                        if (editProductOffcanvas) editProductOffcanvas.hide();
                        window.location.reload();
                    }
                });
            }
        }

        function duplicateProduct() {
            let pid = document.getElementById('PK_PRODUCT').value;
            if (!pid) return;
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                data: {
                    FUNCTION_NAME: 'duplicateProduct',
                    PK_PRODUCT: pid
                },
                success: function() {
                    if (editProductOffcanvas) editProductOffcanvas.hide();
                    window.location.reload();
                }
            });
        }

        function addMoreSize() {
            $('#add_more_size').append('<div class="row mb-2"><div class="col-10"><input type="text" name="PRODUCT_SIZE[]" class="form-control form-control-custom" placeholder="Enter Size/Dimensions"></div><div class="col-2"><i class="bi bi-trash3 text-danger fs-5" style="cursor: pointer;" onclick="removeThis(this);"></i></div></div>');
        }

        function addMoreColor() {
            $('#add_more_color').append('<div class="row mb-2"><div class="col-10"><input type="text" name="PRODUCT_COLOR[]" class="form-control form-control-custom" placeholder="Enter Color"></div><div class="col-2"><i class="bi bi-trash3 text-danger fs-5" style="cursor: pointer;" onclick="removeThis(this);"></i></div></div>');
        }

        function removeThis(el) {
            $(el).closest('.row').remove();
        }

        // DataTable initialization
        $(document).ready(function() {
            var table = $('#ordersDataTable').DataTable({
                pageLength: 50,
                order: [],
                dom: 'lrtip'
            });
            $('#customSearchBox').on('keyup', function() {
                table.search(this.value).draw();
            });
            $('#selectAllCheck').on('change', function() {
                $('.order-checkbox').prop('checked', $(this).prop('checked'));
            });
            $('.clickable-row').on('click', function(e) {
                if ($(e.target).is('a') || $(e.target).is('i') || $(e.target).is('input')) return;
                window.location.href = "order_details.php?id=" + $(this).data('order-id');
            });
            $('#filterBtn').on('click', function() {
                alert("Use the search box above to filter orders.");
            });
            $('#exportBtn').on('click', function() {
                let csvRows = [
                    ['Order ID', 'Date', 'Status', 'Customer', 'Products', 'Revenue']
                ];
                $('#ordersDataTable tbody tr:visible').each(function() {
                    let row = $(this);
                    csvRows.push([
                        '"' + row.find('td:eq(1)').text().trim() + '"',
                        '"' + row.find('td:eq(2)').text().trim() + '"',
                        '"' + row.find('td:eq(3)').text().trim() + '"',
                        '"' + row.find('td:eq(4) .fw-medium').text().trim() + '"',
                        '"' + row.find('td:eq(5)').text().replace(/\n/g, ' ').replace(/\s+/g, ' ').trim().substring(0, 150) + '"',
                        '"' + row.find('td:eq(6)').text().trim() + '"'
                    ]);
                });
                let blob = new Blob(["\uFEFF" + csvRows.map(row => row.join(',')).join('\n')], {
                    type: 'text/csv;charset=utf-8;'
                });
                let link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = 'orders_export.csv';
                link.click();
                URL.revokeObjectURL(link.href);
            });
        });

        function editpage(id) {
            window.location.href = "order_details.php?id=" + id;
        }
    </script>
</body>

</html>
<?php ob_end_flush(); ?>