<?php
require_once('../../global/config.php');
global $db;
global $db_account;
global $master_database;

// Handle JSON request for edit drawer
if (isset($_GET['get_details']) && $_GET['get_details'] == 1 && isset($_GET['PK_PRODUCT'])) {
    header('Content-Type: application/json');
    $PK_PRODUCT = intval($_GET['PK_PRODUCT']);

    // Fetch product with ALL fields including BRAND, WEIGHT, SHIPPING_INFORMATION
    $result = $db_account->Execute("SELECT PK_PRODUCT, PRODUCT_ID, PRODUCT_NAME, CATEGORY, BRAND, PRODUCT_DESCRIPTION, PRICE, WEIGHT, SHIPPING_INFORMATION, ACTIVE, PRODUCT_IMAGES FROM DOA_PRODUCT WHERE PK_PRODUCT = $PK_PRODUCT AND IS_DELETED = 0");

    if ($result && $result->RecordCount() > 0) {
        $product = $result->fields;

        // Fetch sizes for this product
        $sizes = $db_account->Execute("SELECT PK_PRODUCT_SIZE, SIZE FROM DOA_PRODUCT_SIZE WHERE PK_PRODUCT = $PK_PRODUCT");
        $product['sizes'] = [];
        if ($sizes && $sizes->RecordCount() > 0) {
            while (!$sizes->EOF) {
                $product['sizes'][] = $sizes->fields;
                $sizes->MoveNext();
            }
        }

        // Fetch colors for this product
        $colors = $db_account->Execute("SELECT PK_PRODUCT_COLOR, COLOR FROM DOA_PRODUCT_COLOR WHERE PK_PRODUCT = $PK_PRODUCT");
        $product['colors'] = [];
        if ($colors && $colors->RecordCount() > 0) {
            while (!$colors->EOF) {
                $product['colors'][] = $colors->fields;
                $colors->MoveNext();
            }
        }

        echo json_encode([
            'success' => true,
            'product' => $product
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
    }
    exit;
}

// Rest of your existing code for add to cart modal...
$PK_PRODUCT = isset($_GET['PK_PRODUCT']) ? intval($_GET['PK_PRODUCT']) : 0;

if ($PK_PRODUCT == 0) {
    echo '<div class="alert alert-danger">Invalid product</div>';
    exit;
}

$product_details = $db_account->Execute("SELECT * FROM DOA_PRODUCT WHERE PK_PRODUCT = '$PK_PRODUCT' AND IS_DELETED = 0");

if (!$product_details || $product_details->RecordCount() == 0) {
    echo '<div class="alert alert-danger">Product not found</div>';
    exit;
}
?>

<input type="hidden" id="PK_PRODUCT" name="PK_PRODUCT" value="<?= $PK_PRODUCT ?>">
<input type="hidden" id="PRODUCT_NAME" name="PRODUCT_NAME" value="<?= htmlspecialchars($product_details->fields['PRODUCT_NAME']) ?>">
<input type="hidden" id="PRODUCT_IMAGES" name="PRODUCT_IMAGES" value="<?= htmlspecialchars($product_details->fields['PRODUCT_IMAGES']) ?>">
<input type="hidden" id="PRODUCT_PRICE" name="PRODUCT_PRICE" value="<?= $product_details->fields['PRICE'] ?>">

<div class="col-5">
    <img id="profile-img" src="<?= htmlspecialchars($product_details->fields['PRODUCT_IMAGES']) ?>" alt="<?= htmlspecialchars($product_details->fields['PRODUCT_NAME']) ?>" style="width: 145px; height: auto;">
</div>
<div class="col-7">
    <b style="font-weight: bold;"><?= htmlspecialchars($product_details->fields['PRODUCT_NAME']) ?></b>
    <p class="m-t-10 m-b-10" style="font-weight: bold; font-size: 18px;">$<?= number_format($product_details->fields['PRICE'], 2) ?></p>
    <div class="row m-t-20">
        <?php
        $product_color = $db_account->Execute("SELECT * FROM DOA_PRODUCT_COLOR WHERE PK_PRODUCT = '$PK_PRODUCT'");
        if ($product_color && $product_color->RecordCount() > 0) { ?>
            <div class="col-6">
                <label class="form-label">Colour
                    <select class="form-control" name="PK_PRODUCT_COLOR" style="min-height: 30px; width: 85px; line-height: 1; margin-top: 3px;" required>
                        <option value="">Select Colour</option>
                        <?php while (!$product_color->EOF) { ?>
                            <option value="<?= $product_color->fields['PK_PRODUCT_COLOR'] ?>"><?= htmlspecialchars($product_color->fields['COLOR']) ?></option>
                        <?php $product_color->MoveNext();
                        } ?>
                    </select>
                </label>
            </div>
        <?php } ?>

        <?php
        $product_size = $db_account->Execute("SELECT * FROM DOA_PRODUCT_SIZE WHERE PK_PRODUCT = '$PK_PRODUCT'");
        if ($product_size && $product_size->RecordCount() > 0) { ?>
            <div class="col-6">
                <label class="form-label">Size
                    <select class="form-control" name="PK_PRODUCT_SIZE" style="min-height: 30px; width: 85px; line-height: 1; margin-top: 3px;" required>
                        <option value="">Select Size</option>
                        <?php while (!$product_size->EOF) { ?>
                            <option value="<?= $product_size->fields['PK_PRODUCT_SIZE'] ?>"><?= htmlspecialchars($product_size->fields['SIZE']) ?></option>
                        <?php $product_size->MoveNext();
                        } ?>
                    </select>
                </label>
            </div>
        <?php } ?>
    </div>
</div>