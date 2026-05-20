<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];

$title = "My Products";

$status_check = empty($_GET['status']) ? 'active' : $_GET['status'];

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
$header_data = $db->Execute("SELECT * FROM `DOA_HEADER_TEXT` WHERE ACTIVE = 1 AND HEADER_TITLE = 'Products Page'");
if ($header_data->RecordCount() > 0) {
    $header_text = $header_data->fields['HEADER_TEXT'];
}

// Fetch products
$products = [];
$row = $db_account->Execute("SELECT * FROM DOA_PRODUCT WHERE IS_DELETED = 0 AND ACTIVE = '$status' ORDER BY PRODUCT_NAME ASC");
while (!$row->EOF) {
    $products[] = $row->fields;
    $row->MoveNext();
}

// Calculate stats
$total_products = 0;
$active_listings = 0;
$total_products_result = $db_account->Execute("SELECT COUNT(*) as total FROM DOA_PRODUCT WHERE IS_DELETED = 0");
if ($total_products_result && !$total_products_result->EOF) {
    $total_products = $total_products_result->fields['total'];
}

$active_listings_result = $db_account->Execute("SELECT COUNT(*) as active FROM DOA_PRODUCT WHERE IS_DELETED = 0 AND ACTIVE = 1");
if ($active_listings_result && !$active_listings_result->EOF) {
    $active_listings = $active_listings_result->fields['active'];
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
    <title><?= $title ?> Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Inter', sans-serif;
            color: #333;
        }

        /* Custom Green Button */
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

        /* Metric Stats Cards */
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

        .border-bottom-dashed .col-sm-6:last-child .metric-card:after {
            display: none;
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

        /* Form inputs & Filtering */
        .search-wrapper .form-control {
            border-color: #dee2e6;
            font-size: 0.95rem;
            padding-top: 0.6rem;
            padding-bottom: 0.6rem;
        }

        .search-wrapper .input-group-text {
            border-color: #dee2e6;
        }

        .btn-white {
            border-color: #dee2e6 !important;
            color: #555;
            font-size: 0.9rem;
            padding: 0.6rem 1rem;
            border-radius: 8px;
        }

        .custom-dropdown::after {
            margin-left: 1.5rem;
        }

        /* Product Cards */
        .product-img-container {
            aspect-ratio: 4 / 3;
            overflow: hidden;
            background-color: #ffffff;
            border-color: #eaeaea !important;
            border-radius: 8px !important;
            cursor: pointer;
        }

        .product-img {
            max-height: 100%;
            object-fit: contain;
        }

        /* Decorative Indicators matching UI elements in mockup */
        .status-dots .dot-dash {
            width: 12px;
            height: 4px;
            background-color: #a0a0a0;
            border-radius: 2px;
        }

        .status-dots .dot {
            width: 4px;
            height: 4px;
            background-color: #d0d0d0;
            border-radius: 50%;
        }

        .product-card .card-title {
            font-size: 0.95rem;
            letter-spacing: -0.1px;
        }

        .btn-success {
            background-color: #00B739;
        }

        .border-dashed {
            border: 1px dashed #ddd;
        }

        .product-card {
            cursor: pointer;
            transition: transform 0.2s ease;
        }

        .product-card:hover {
            transform: translateY(-4px);
        }

        .active-badge {
            position: absolute;
            bottom: 10px;
            right: 10px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }

        .active-badge.active {
            background-color: #00b633;
            box-shadow: 0 0 0 2px white;
        }

        .active-badge.inactive {
            background-color: #ee4444;
            box-shadow: 0 0 0 2px white;
        }

        .dropdown-menu-actions {
            min-width: 120px;
        }

        .dropdown-menu-actions a {
            font-size: 0.85rem;
            padding: 6px 12px;
        }

        /* Modal styles */
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
            <div>
                <div class="d-flex align-items-center gap-3">
                    <div class="icon-box bg-white border rounded-3 p-2 d-flex align-items-center justify-content-center">
                        <i class="bi bi-bag text-secondary fs-4"></i>
                    </div>
                    <div>
                        <h1 class="h4 fw-bold mb-0 text-dark"><?= $title ?></h1>
                        <p class="text-muted small mb-0">Manage and collaborate on your product listings.</p>
                    </div>
                </div>
            </div>
            <div>
                <?php if ($status_check == 'inactive') { ?>
                    <button type="button" class="btn btn-success border-0 rounded-pill px-3 me-2" onclick="window.location.href='products_list.php?status=active'">
                        <i class="bi bi-eye"></i> Show Active
                    </button>
                <?php } elseif ($status_check == 'active') { ?>
                    <button type="button" class="btn btn-danger border-0 rounded-pill px-3 me-2" onclick="window.location.href='products_list.php?status=inactive'">
                        <i class="bi bi-eye-slash"></i> Show Inactive
                    </button>
                <?php } ?>
                <button class="btn btn-success border-0 rounded-pill px-3" onclick="window.location.href='product.php'">
                    <i class="bi bi-plus-lg"></i> New Product
                </button>
            </div>
        </div>

        <hr class="border-dashed my-4">

        <div class="row g-3 mb-4 text-nowrap border-bottom-dashed">
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card metric-card p-3 h-100">
                    <p class="text-muted small mb-1">Total Products</p>
                    <div class="d-flex align-items-baseline gap-2">
                        <span class="fs-2 fw-semibold"><?= $total_products ?></span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card metric-card p-3 border-0 shadow-sm h-100">
                    <p class="text-muted small mb-1">Active Listings</p>
                    <div class="d-flex align-items-baseline gap-2">
                        <span class="fs-2 fw-semibold"><?= $active_listings ?></span>
                        <span class="badge text-success font-weight-normal"><?= round(($active_listings / max($total_products, 1)) * 100) ?>% <span class="text-muted fw-normal">of total</span></span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card metric-card p-3 border-0 shadow-sm h-100">
                    <p class="text-muted small mb-1">Current View</p>
                    <div class="d-flex align-items-baseline gap-2">
                        <span class="fs-2 fw-semibold"><?= count($products) ?></span>
                        <span class="badge <?= $status_check == 'active' ? 'text-success' : 'text-warning' ?> font-weight-normal">
                            <?= ucfirst($status_check) ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card metric-card p-3 border-0 shadow-sm h-100">
                    <p class="text-muted small mb-1">Total Revenue (All Time)</p>
                    <div class="d-flex align-items-baseline gap-2">
                        <span class="fs-2 fw-semibold">$--</span>
                        <span class="badge text-muted font-weight-normal">coming soon</span>
                    </div>
                </div>
            </div>
        </div>

        <hr class="border-dashed my-4">

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div class="search-wrapper flex-grow-1" style="max-width: 300px;">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" id="searchInput" class="form-control border-start-0 ps-0 text-muted shadow-none" placeholder="Search products...">
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <div class="btn-group border rounded-3 bg-white p-1 rounded-pill" role="group">
                    <button type="button" id="gridViewBtn" class="btn btn-sm btn-light border-0 text-success px-2 py-1"><i class="bi bi-grid-fill"></i></button>
                    <button type="button" id="listViewBtn" class="btn btn-sm btn-white border-0 text-muted px-2 py-1"><i class="bi bi-list"></i></button>
                </div>
                <div class="dropdown">
                    <button class="rounded-pill btn btn-white border dropdown-toggle text-dark d-flex align-items-center gap-4 custom-dropdown bg-white" type="button" data-bs-toggle="dropdown">
                        <span class="small">Filter by Status</span>
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="all_products.php?status=active">Active Only</a></li>
                        <li><a class="dropdown-item" href="all_products.php?status=inactive">Inactive Only</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Grid View -->
        <div id="gridView" class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-xl-4 g-4">
            <?php foreach ($products as $product): ?>
                <div class="col product-item" data-name="<?= strtolower(htmlspecialchars($product['PRODUCT_NAME'])) ?>" data-id="<?= $product['PK_PRODUCT'] ?>">
                    <div class="card product-card border-0 h-100 bg-transparent" onclick="editpage(<?= $product['PK_PRODUCT'] ?>);">
                        <div class="product-img-container p-4 border border-secondary-subtle rounded-3 bg-white position-relative d-flex align-items-center justify-content-center">
                            <div class="status-dots position-absolute top-0 start-0 m-3 d-flex gap-1 align-items-center">
                                <span class="dot-dash"></span>
                                <span class="dot"></span>
                                <span class="dot"></span>
                            </div>
                            <div class="position-absolute top-0 end-0 m-3" onclick="event.stopPropagation();">
                                <div class="dropdown">
                                    <button class="btn p-0 border-0 text-muted" type="button" data-bs-toggle="dropdown">
                                        <i class="bi bi-three-dots-vertical fs-5"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-actions">
                                        <li><a class="dropdown-item" href="javascript:" onclick="event.stopPropagation(); addToCart(<?= $product['PK_PRODUCT'] ?>);"><i class="bi bi-cart-plus me-2"></i> Add to Cart</a></li>
                                        <li><a class="dropdown-item" href="product.php?id=<?= $product['PK_PRODUCT'] ?>" onclick="event.stopPropagation();"><i class="bi bi-pencil me-2"></i> Edit</a></li>
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                        <li><a class="dropdown-item text-danger" href="javascript:" onclick="event.stopPropagation(); ConfirmDelete(<?= $product['PK_PRODUCT'] ?>);"><i class="bi bi-trash me-2"></i> Delete</a></li>
                                    </ul>
                                </div>
                            </div>
                            <?php if ($product['PRODUCT_IMAGES']): ?>
                                <img src="<?= htmlspecialchars($product['PRODUCT_IMAGES']) ?>" class="img-fluid product-img" alt="<?= htmlspecialchars($product['PRODUCT_NAME']) ?>">
                            <?php else: ?>
                                <i class="bi bi-image" style="font-size: 48px; color: #ccc;"></i>
                            <?php endif; ?>
                            <div class="active-badge <?= $product['ACTIVE'] == 1 ? 'active' : 'inactive' ?>"></div>
                        </div>
                        <div class="card-body px-0 pt-3">
                            <h5 class="card-title h6 fw-bold mb-1 text-dark"><?= htmlspecialchars($product['PRODUCT_NAME']) ?></h5>
                            <p class="card-text text-muted small mb-0">$<?= number_format($product['PRICE'], 2) ?></p>
                            <p class="card-text text-muted small"><?= htmlspecialchars(substr($product['PRODUCT_DESCRIPTION'], 0, 60)) ?>...</p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (count($products) == 0): ?>
                <div class="col-12 text-center py-5">
                    <i class="bi bi-box-seam" style="font-size: 64px; color: #ccc;"></i>
                    <h5 class="mt-3 text-muted">No products found</h5>
                    <button class="btn btn-success border-0 rounded-pill mt-2" onclick="window.location.href='product.php'">Create your first product</button>
                </div>
            <?php endif; ?>
        </div>

        <!-- List View (hidden by default) -->
        <div id="listView" class="table-responsive" style="display: none;">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Product ID</th>
                        <th>Image</th>
                        <th>Product Name</th>
                        <th>Description</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr class="product-item" data-name="<?= strtolower(htmlspecialchars($product['PRODUCT_NAME'])) ?>" data-id="<?= $product['PK_PRODUCT'] ?>">
                            <td><?= htmlspecialchars($product['PRODUCT_ID']) ?></td>
                            <td>
                                <?php if ($product['PRODUCT_IMAGES']): ?>
                                    <img src="<?= htmlspecialchars($product['PRODUCT_IMAGES']) ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;">
                                <?php else: ?>
                                    <i class="bi bi-image" style="font-size: 24px; color: #ccc;"></i>
                                <?php endif; ?>
                            </td>
                            <td class="fw-bold"><?= htmlspecialchars($product['PRODUCT_NAME']) ?></td>
                            <td><?= htmlspecialchars(substr($product['PRODUCT_DESCRIPTION'], 0, 80)) ?>...</td>
                            <td>$<?= number_format($product['PRICE'], 2) ?></td>
                            <td>
                                <?php if ($product['ACTIVE'] == 1): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="javascript:" onclick="addToCart(<?= $product['PK_PRODUCT'] ?>);" class="text-success me-3"><i class="bi bi-cart-plus"></i></a>
                                <a href="product.php?id=<?= $product['PK_PRODUCT'] ?>" class="text-primary me-3"><i class="bi bi-pencil"></i></a>
                                <a href="javascript:" onclick="ConfirmDelete(<?= $product['PK_PRODUCT'] ?>);" class="text-danger"><i class="bi bi-trash"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add To Cart Modal -->
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
                        <button type="submit" id="card-button" class="btn btn-success border-0 rounded-pill waves-effect waves-light m-r-10 text-white">Add to Cart</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Grid/List view toggle
        let currentView = 'grid';

        document.getElementById('gridViewBtn').addEventListener('click', function() {
            document.getElementById('gridView').style.display = '';
            document.getElementById('listView').style.display = 'none';
            this.classList.add('btn-light', 'text-success');
            this.classList.remove('btn-white', 'text-muted');
            document.getElementById('listViewBtn').classList.add('btn-white', 'text-muted');
            document.getElementById('listViewBtn').classList.remove('btn-light', 'text-success');
            currentView = 'grid';
        });

        document.getElementById('listViewBtn').addEventListener('click', function() {
            document.getElementById('gridView').style.display = 'none';
            document.getElementById('listView').style.display = '';
            this.classList.add('btn-light', 'text-success');
            this.classList.remove('btn-white', 'text-muted');
            document.getElementById('gridViewBtn').classList.add('btn-white', 'text-muted');
            document.getElementById('gridViewBtn').classList.remove('btn-light', 'text-success');
            currentView = 'list';
        });

        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            let searchValue = this.value.toLowerCase();
            let items = document.querySelectorAll('.product-item');

            items.forEach(function(item) {
                let productName = item.getAttribute('data-name') || '';
                if (productName.indexOf(searchValue) > -1) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });

        function ConfirmDelete(PK_PRODUCT) {
            let conf = confirm("Are you sure you want to delete this product?");
            if (conf) {
                $.ajax({
                    url: "ajax/AjaxFunctions.php",
                    type: 'POST',
                    data: {
                        FUNCTION_NAME: 'deleteProductData',
                        PK_PRODUCT: PK_PRODUCT
                    },
                    success: function(data) {
                        window.location.href = `all_products.php?status=<?= $status_check ?>`;
                    }
                });
            }
        }

        function editpage(id) {
            window.location.href = "product.php?id=" + id;
        }

        function addToCart(PK_PRODUCT) {
            $('#add_to_cart_form')[0].reset();
            $.ajax({
                url: "ajax/get_product_details.php",
                type: 'GET',
                data: {
                    PK_PRODUCT: PK_PRODUCT
                },
                success: function(data) {
                    $('#item_details').html(data);
                }
            });
            $('#add_to_cart').modal('show');
        }

        $(document).on('submit', '#add_to_cart_form', function(event) {
            event.preventDefault();
            let form_data = new FormData($('#add_to_cart_form')[0]);
            $.ajax({
                url: "ajax/AjaxFunctionProductPurchase.php",
                type: 'POST',
                data: form_data,
                processData: false,
                contentType: false,
                success: function(data) {
                    if (typeof updateCartCount === 'function') {
                        updateCartCount(data);
                    }
                    if (typeof $('#cart_count').text === 'function') {
                        $('#cart_count').text(data);
                    }
                    $('#add_to_cart').modal('hide');
                }
            });
        });

        // Quantity increment/decrement
        $(document).on('click', '.minus', function(e) {
            e.preventDefault();
            var $input = $(this).closest('.number').find('.counter_input');
            var currentVal = parseInt($input.val());
            if (!isNaN(currentVal) && currentVal > 1) {
                $input.val(currentVal - 1);
            } else {
                $input.val(1);
            }
        });

        $(document).on('click', '.plus', function(e) {
            e.preventDefault();
            var $input = $(this).closest('.number').find('.counter_input');
            var currentVal = parseInt($input.val());
            if (!isNaN(currentVal)) {
                $input.val(currentVal + 1);
            } else {
                $input.val(1);
            }
        });
    </script>
</body>

</html>