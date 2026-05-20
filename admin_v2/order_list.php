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
?>
<!DOCTYPE html>
<html lang="en">
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
            <button class="btn btn-success rounded-pill px-3" onclick="window.location.href='product.php';">
                <i class="bi bi-plus-lg"></i> New Products
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

    <script>
        $(document).ready(function() {
            var table = $('#ordersDataTable').DataTable({
                "pageLength": 50,
                "order": [],
                "language": {
                    "search": "",
                    "searchPlaceholder": "Search orders..."
                },
                "dom": 'lrtip'
            });

            $('#customSearchBox').on('keyup', function() {
                table.search(this.value).draw();
            });

            $('#selectAllCheck').on('change', function() {
                var isChecked = $(this).prop('checked');
                $('.order-checkbox').prop('checked', isChecked);
            });

            $('.clickable-row').on('click', function(e) {
                if ($(e.target).is('a') || $(e.target).is('i') || $(e.target).is('input')) {
                    return;
                }
                var orderId = $(this).data('order-id');
                if (orderId) {
                    window.location.href = "order_details.php?id=" + orderId;
                }
            });

            $('#filterBtn').on('click', function() {
                alert("Use the search box above to filter orders by ID, customer, or status.");
            });

            $('#exportBtn').on('click', function() {
                let csvRows = [];
                const headers = ['Order ID', 'Date', 'Status', 'Customer', 'Products', 'Revenue'];
                csvRows.push(headers.join(','));

                $('#ordersDataTable tbody tr:visible').each(function() {
                    var row = $(this);
                    var cols = [];
                    cols.push('"' + row.find('td:eq(1)').text().trim() + '"');
                    cols.push('"' + row.find('td:eq(2)').text().trim() + '"');
                    cols.push('"' + row.find('td:eq(3)').text().trim() + '"');
                    cols.push('"' + row.find('td:eq(4) .fw-medium').text().trim() + '"');
                    var productText = row.find('td:eq(5)').text().replace(/\n/g, ' ').replace(/\s+/g, ' ').trim();
                    cols.push('"' + productText.substring(0, 150) + '"');
                    cols.push('"' + row.find('td:eq(6)').text().trim() + '"');
                    csvRows.push(cols.join(','));
                });

                var csvString = csvRows.join('\n');
                var blob = new Blob(["\uFEFF" + csvString], {
                    type: 'text/csv;charset=utf-8;'
                });
                var link = document.createElement('a');
                var url = URL.createObjectURL(blob);
                link.setAttribute('href', url);
                link.setAttribute('download', 'orders_export.csv');
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(url);
            });
        });

        function editpage(id) {
            window.location.href = "order_details.php?id=" + id;
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
<?php
// Flush the output buffer at the end
ob_end_flush();
?>