<?php
ob_start();
require_once('../global/config.php');
global $db;
global $db_account;
global $upload_path;

$title = "Add Product";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

if (empty($_GET['id'])) {
    $PK_PRODUCT = '';
    $PRODUCT_ID = '';
    $PRODUCT_NAME = '';
    $PRODUCT_DESCRIPTION = '';
    $PRICE = '';
    $SHIPPING_INFORMATION = '';
    $PRODUCT_IMAGES = '';
    $BRAND = '';
    $CATEGORY = '';
    $SIZE = '';
    $COLOR = '';
    $WEIGHT = '';
    $ACTIVE = '';
} else {
    $res = $db_account->Execute("SELECT * FROM `DOA_PRODUCT` WHERE `PK_PRODUCT` = '$_GET[id]'");
    if ($res->RecordCount() == 0) {
        header("location:all_products.php");
        exit;
    }
    $PK_PRODUCT = $res->fields['PK_PRODUCT'];
    $PRODUCT_ID = $res->fields['PRODUCT_ID'];
    $PRODUCT_NAME = $res->fields['PRODUCT_NAME'];
    $PRODUCT_DESCRIPTION = $res->fields['PRODUCT_DESCRIPTION'];
    $PRICE = $res->fields['PRICE'];
    $SHIPPING_INFORMATION = $res->fields['SHIPPING_INFORMATION'];
    $PRODUCT_IMAGES = $res->fields['PRODUCT_IMAGES'];
    $BRAND = $res->fields['BRAND'];
    $CATEGORY = $res->fields['CATEGORY'];
    $SIZE = $res->fields['SIZE'];
    $COLOR = $res->fields['COLOR'];
    $WEIGHT = $res->fields['WEIGHT'];
    $ACTIVE = $res->fields['ACTIVE'];
}

if (!empty($_POST)) {
    $PRODUCT_SIZE = $_POST['PRODUCT_SIZE'] ?? [];
    unset($_POST['PRODUCT_SIZE']);
    $PRODUCT_COLOR = $_POST['PRODUCT_COLOR'] ?? [];
    unset($_POST['PRODUCT_COLOR']);
    $PRODUCT_DATA = $_POST;

    if ($_FILES['PRODUCT_IMAGES']['name'] != '') {
        if (!file_exists('../' . $upload_path . '/product_image/')) {
            mkdir('../' . $upload_path . '/product_image/', 0777, true);
            chmod('../' . $upload_path . '/product_image/', 0777);
        }
        $extn = explode(".", $_FILES['PRODUCT_IMAGES']['name']);
        $iindex = count($extn) - 1;
        $rand_string = time() . "-" . rand(100000, 999999);
        $file11 = 'product_image' . $_SESSION['PK_USER'] . $rand_string . "." . $extn[$iindex];
        $extension = strtolower($extn[$iindex]);

        if ($extension == "gif" || $extension == "jpeg" || $extension == "pjpeg" || $extension == "png" || $extension == "jpg") {
            $image_path = '../' . $upload_path . '/product_image/' . $file11;
            move_uploaded_file($_FILES['PRODUCT_IMAGES']['tmp_name'], $image_path);
            $PRODUCT_DATA['PRODUCT_IMAGES'] = $image_path;
        }
    }

    if (empty($_GET['id'])) {
        $PRODUCT_DATA['IS_DELETED'] = 0;
        $PRODUCT_DATA['ACTIVE'] = 1;
        $PRODUCT_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
        $PRODUCT_DATA['CREATED_ON'] = date("Y-m-d H:i");
        db_perform_account('DOA_PRODUCT', $PRODUCT_DATA, 'insert');
        $PK_PRODUCT = $db_account->insert_ID();
    } else {
        $PRODUCT_DATA['ACTIVE'] = $_POST['ACTIVE'];
        $PRODUCT_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
        $PRODUCT_DATA['EDITED_ON'] = date("Y-m-d H:i");
        db_perform_account('DOA_PRODUCT', $PRODUCT_DATA, 'update', "PK_PRODUCT = '$_GET[id]'");
        $PK_PRODUCT = $_GET['id'];
    }

    $db_account->Execute("DELETE FROM `DOA_PRODUCT_SIZE` WHERE `PK_PRODUCT` = '$PK_PRODUCT'");
    if (count($PRODUCT_SIZE) > 0) {
        for ($i = 0; $i < count($PRODUCT_SIZE); $i++) {
            if (!empty($PRODUCT_SIZE[$i])) {
                $PRODUCT_SIZE_DATA['PK_PRODUCT'] = $PK_PRODUCT;
                $PRODUCT_SIZE_DATA['SIZE'] = $PRODUCT_SIZE[$i];
                db_perform_account('DOA_PRODUCT_SIZE', $PRODUCT_SIZE_DATA, 'insert');
            }
        }
    }

    $db_account->Execute("DELETE FROM `DOA_PRODUCT_COLOR` WHERE `PK_PRODUCT` = '$PK_PRODUCT'");
    if (isset($PRODUCT_COLOR) && count($PRODUCT_COLOR) > 0) {
        for ($i = 0; $i < count($PRODUCT_COLOR); $i++) {
            if (!empty($PRODUCT_COLOR[$i])) {
                $PRODUCT_COLOR_DATA['PK_PRODUCT'] = $PK_PRODUCT;
                $PRODUCT_COLOR_DATA['COLOR'] = $PRODUCT_COLOR[$i];
                db_perform_account('DOA_PRODUCT_COLOR', $PRODUCT_COLOR_DATA, 'insert');
            }
        }
    }

    header("location:products_list.php");
    exit;
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
    <title><?= $title ?> | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Inter', sans-serif;
            color: #333;
        }

        .dashboard-container {
            max-width: 1400px;
        }

        .form-label {
            font-weight: 500;
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 0.25rem;
        }

        .form-control,
        .form-select {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 0.6rem 0.75rem;
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #00b633;
            box-shadow: 0 0 0 0.2rem rgba(0, 182, 51, 0.1);
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

        .btn-outline-secondary-custom {
            border: 1px solid #dee2e6;
            background-color: white;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }

        .btn-outline-secondary-custom:hover {
            border-color: #00b633;
            color: #00b633;
        }

        .image-preview {
            width: 120px;
            height: 120px;
            border-radius: 12px;
            object-fit: cover;
            border: 2px solid #dee2e6;
            transition: all 0.2s ease;
        }

        .image-preview:hover {
            border-color: #00b633;
            transform: scale(1.02);
        }

        .card-hover {
            transition: box-shadow 0.2s ease;
        }

        .card-hover:hover {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08);
        }

        .breadcrumb-item a {
            text-decoration: none;
            color: #6c757d;
        }

        .breadcrumb-item a:hover {
            color: #00b633;
        }

        .required-field::after {
            content: "*";
            color: #dc3545;
            margin-left: 4px;
        }

        .size-color-card {
            background-color: #f8f9fa;
            border-radius: 12px;
            padding: 1rem;
            margin-top: 0.5rem;
        }

        .remove-btn {
            color: #dc3545;
            cursor: pointer;
            transition: color 0.2s ease;
        }

        .remove-btn:hover {
            color: #bb2d3b;
        }

        hr {
            opacity: 0.5;
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
        <!-- Header Section -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center gap-3">
                <div class="icon-box bg-white border rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                    <i class="bi bi-box-seam text-secondary fs-5"></i>
                </div>
                <div>
                    <h1 class="h4 fw-bold mb-0 text-dark"><?= $title ?></h1>
                    <p class="text-muted small mb-0"><?= empty($_GET['id']) ? 'Create a new product' : 'Edit existing product details' ?></p>
                </div>
            </div>
            <a href="products_list.php" class="btn btn-success-custom rounded-pill">
                <i class="bi bi-arrow-left"></i> Back to Products
            </a>
        </div>

        <hr class="mb-4">

        <!-- Form Section -->
        <form action="" method="post" enctype="multipart/form-data">
            <div class="row">
                <!-- Left Column -->
                <div class="col-lg-8">
                    <!-- Basic Information Card -->
                    <div class="card border-0 shadow-sm mb-4 card-hover">
                        <div class="card-header bg-white border-bottom-0 pt-3 pb-0">
                            <h5 class="fw-semibold mb-0"><i class="bi bi-info-circle me-2 text-success"></i>Basic Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label required-field">Product ID / SKU</label>
                                    <input type="text" id="PRODUCT_ID" name="PRODUCT_ID" class="form-control" placeholder="Enter Product ID/SKU" value="<?= htmlspecialchars($PRODUCT_ID) ?>" required>
                                    <small class="text-muted">Unique identifier for inventory tracking</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label required-field">Product Name</label>
                                    <input type="text" id="PRODUCT_NAME" name="PRODUCT_NAME" class="form-control" placeholder="Enter Product Name" value="<?= htmlspecialchars($PRODUCT_NAME) ?>" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label required-field">Product Description</label>
                                    <textarea id="PRODUCT_DESCRIPTION" name="PRODUCT_DESCRIPTION" class="form-control" rows="3" placeholder="Enter Product Description" required><?= htmlspecialchars($PRODUCT_DESCRIPTION) ?></textarea>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label required-field">Price ($)</label>
                                    <input type="number" step="0.01" id="PRICE" name="PRICE" class="form-control" placeholder="0.00" value="<?= htmlspecialchars($PRICE) ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Brand</label>
                                    <input type="text" id="BRAND" name="BRAND" class="form-control" placeholder="Enter Brand" value="<?= htmlspecialchars($BRAND) ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Category</label>
                                    <input type="text" id="CATEGORY" name="CATEGORY" class="form-control" placeholder="Enter Category" value="<?= htmlspecialchars($CATEGORY) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Shipping Information</label>
                                    <textarea id="SHIPPING_INFORMATION" name="SHIPPING_INFORMATION" class="form-control" rows="2" placeholder="Enter Shipping Information"><?= htmlspecialchars($SHIPPING_INFORMATION) ?></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Weight</label>
                                    <input type="text" id="WEIGHT" name="WEIGHT" class="form-control" placeholder="e.g., 1.5 kg, 500g" value="<?= htmlspecialchars($WEIGHT) ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Size & Color Card -->
                    <div class="card border-0 shadow-sm mb-4 card-hover">
                        <div class="card-header bg-white border-bottom-0 pt-3 pb-0">
                            <h5 class="fw-semibold mb-0"><i class="bi bi-grid-3x3-gap-fill me-2 text-success"></i>Size & Color Variants</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold mb-2">Size / Dimensions</label>
                                    <div id="add_more_size">
                                        <?php if (!empty($_GET['id']) && isset($PK_PRODUCT)) {
                                            $product_size = $db_account->Execute("SELECT * FROM DOA_PRODUCT_SIZE WHERE PK_PRODUCT = '$PK_PRODUCT'");
                                            while (!$product_size->EOF) { ?>
                                                <div class="row mb-2">
                                                    <div class="col-10">
                                                        <input type="text" name="PRODUCT_SIZE[]" class="form-control" placeholder="Enter Size/Dimensions" value="<?= htmlspecialchars($product_size->fields['SIZE']) ?>">
                                                    </div>
                                                    <div class="col-2">
                                                        <i class="bi bi-trash3 remove-btn fs-5" onclick="removeThis(this);"></i>
                                                    </div>
                                                </div>
                                        <?php $product_size->MoveNext();
                                            }
                                        } ?>
                                    </div>
                                    <button type="button" class="btn btn-outline-secondary-custom rounded-pill btn-sm mt-2" onclick="addMoreSize();">
                                        <i class="bi bi-plus-lg"></i> Add Size
                                    </button>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold mb-2">Color</label>
                                    <div id="add_more_color">
                                        <?php if (!empty($_GET['id']) && isset($PK_PRODUCT)) {
                                            $product_color = $db_account->Execute("SELECT * FROM DOA_PRODUCT_COLOR WHERE PK_PRODUCT = '$PK_PRODUCT'");
                                            while (!$product_color->EOF) { ?>
                                                <div class="row mb-2">
                                                    <div class="col-10">
                                                        <input type="text" name="PRODUCT_COLOR[]" class="form-control" placeholder="Enter Color" value="<?= htmlspecialchars($product_color->fields['COLOR']) ?>">
                                                    </div>
                                                    <div class="col-2">
                                                        <i class="bi bi-trash3 remove-btn fs-5" onclick="removeThis(this);"></i>
                                                    </div>
                                                </div>
                                        <?php $product_color->MoveNext();
                                            }
                                        } ?>
                                    </div>
                                    <button type="button" class="btn btn-outline-secondary-custom rounded-pill btn-sm mt-2" onclick="addMoreColor();">
                                        <i class="bi bi-plus-lg"></i> Add Color
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Active Status (for edit mode) -->
                    <?php if (!empty($_GET['id'])) { ?>
                        <div class="card border-0 shadow-sm mb-4 card-hover">
                            <div class="card-body">
                                <label class="form-label fw-semibold">Product Status</label>
                                <div class="d-flex gap-4 mt-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="ACTIVE" id="active_yes" value="1" <?= ($ACTIVE == 1) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="active_yes">
                                            <i class="bi bi-check-circle-fill text-success"></i> Active
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="ACTIVE" id="active_no" value="0" <?= ($ACTIVE == 0) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="active_no">
                                            <i class="bi bi-x-circle-fill text-danger"></i> Inactive
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>

                <!-- Right Column - Product Image -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm card-hover" style="top: 20px;">
                        <div class="card-header bg-white border-bottom-0 pt-3 pb-0">
                            <h5 class="fw-semibold mb-0"><i class="bi bi-image me-2 text-success"></i>Product Image</h5>
                        </div>
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <?php if ($PRODUCT_IMAGES != '') { ?>
                                    <img id="profile-img" src="<?= htmlspecialchars($PRODUCT_IMAGES) ?>" class="image-preview mb-3" alt="Product Image">
                                <?php } else { ?>
                                    <img id="profile-img" src="https://placehold.co/400x400?text=No+Image" class="image-preview mb-3" alt="Product Image">
                                <?php } ?>
                            </div>
                            <div class="mb-2">
                                <label for="PRODUCT_IMAGES" class="btn btn-success-custom rounded-pill w-100">
                                    <i class="bi bi-cloud-upload"></i> Upload Image
                                </label>
                                <input type="file" name="PRODUCT_IMAGES" id="PRODUCT_IMAGES" class="d-none" onchange="previewFile(this)" <?= empty($PRODUCT_IMAGES) ? 'required' : '' ?>>
                            </div>
                            <small class="text-muted">Supported formats: JPG, PNG, GIF. Max size: 5MB</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="row mt-4">
                <div class="col-12">
                    <hr>
                    <div class="d-flex gap-3 justify-content-end">
                        <a href="all_products.php" class="btn btn-outline-secondary-custom rounded-pill">Cancel</a>
                        <button type="submit" class="btn btn-success-custom rounded-pill">
                            <i class="bi bi-check-lg"></i> <?= empty($_GET['id']) ? 'Create Product' : 'Update Product' ?>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function previewFile(input) {
            let file = input.files[0];
            if (file) {
                let reader = new FileReader();
                reader.onload = function() {
                    $("#profile-img").attr("src", reader.result);
                }
                reader.readAsDataURL(file);
            }
        }

        function removeThis(param) {
            $(param).closest('.row').remove();
        }

        function addMoreSize() {
            $('#add_more_size').append(`
            <div class="row mb-2">
                <div class="col-10">
                    <input type="text" name="PRODUCT_SIZE[]" class="form-control" placeholder="Enter Size/Dimensions">
                </div>
                <div class="col-2">
                    <i class="bi bi-trash3 remove-btn fs-5" onclick="removeThis(this);"></i>
                </div>
            </div>
        `);
        }

        function addMoreColor() {
            $('#add_more_color').append(`
            <div class="row mb-2">
                <div class="col-10">
                    <input type="text" name="PRODUCT_COLOR[]" class="form-control" placeholder="Enter Color">
                </div>
                <div class="col-2">
                    <i class="bi bi-trash3 remove-btn fs-5" onclick="removeThis(this);"></i>
                </div>
            </div>
        `);
        }
    </script>
</body>

</html>
<?php ob_end_flush(); ?>