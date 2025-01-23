<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];

$title = "All Orders";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5]) ){
    header("location:../login.php");
    exit;
}

$header_text = '';
$header_data = $db->Execute("SELECT * FROM `DOA_HEADER_TEXT` WHERE ACTIVE = 1 AND HEADER_TITLE = 'Orders Page'");
if ($header_data->RecordCount() > 0) {
    $header_text = $header_data->fields['HEADER_TEXT'];
}

?>

<!DOCTYPE html>
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<html lang="en">
<?php require_once('../includes/header.php');?>
<style>
    th {
        text-align: center;
        vertical-align: middle;
    }
    td {
        text-align: center;
        vertical-align: middle;
    }
</style>
<body class="skin-default-dark fixed-layout">
<?php require_once('../includes/loader.php');?>
<div id="main-wrapper">
    <?php require_once('../includes/top_menu.php');?>
    <div class="page-wrapper">
        <?php require_once('../includes/top_menu_bar.php') ?>
        <?php require_once('../includes/setup_menu.php') ?>
        <div class="container-fluid body_content m-0">
            <div class="row page-titles">
                <div class="col-md-4 align-self-center">
                    <h4 class="text-themecolor"><?=$title?></h4>
                </div>
                <div class="col-md-3"></div>
                <div class="col-md-5 align-self-center text-end">
                    <div class="d-flex justify-content-end align-items-center">
                        <ol class="breadcrumb justify-content-end">
                            <li class="breadcrumb-item"><a href="setup.php">Setup</a></li>
                            <li class="breadcrumb-item active"><?=$title?></li>
                        </ol>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row" style="text-align: center;">
                                <h5 style="font-weight: bold;"><?=$header_text?></h5>
                            </div>
                            <div class="table-responsive">
                                <table id="myTable" class="table table-striped border" data-page-length="50">
                                    <thead>
                                        <tr>
                                            <th width="5%">Order ID</th>
                                            <th width="5%">Order Date</th>
                                            <th width="15%">Customer Name</th>
                                            <th width="5%">Order Type</th>
                                            <th width="20%">Address</th>
                                            <th width="15%">Payment Details</th>
                                            <th width="20%">Product</th>
                                            <th width="5%">Order Status</th>
                                            <th width="10%">Action</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                    <?php
                                    $i=1;
                                    $row = $db_account->Execute("SELECT DOA_ORDER.*, DOA_ORDER_STATUS.STATUS, DOA_ORDER_STATUS.COLOR_CODE, CONCAT(CUSTOMER.FIRST_NAME, ' ', CUSTOMER.LAST_NAME) AS CUSTOMER_NAME FROM `DOA_ORDER` LEFT JOIN $master_database.DOA_ORDER_STATUS AS DOA_ORDER_STATUS ON DOA_ORDER.PK_ORDER_STATUS = DOA_ORDER_STATUS.PK_ORDER_STATUS LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_ORDER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER LEFT JOIN $master_database.DOA_USERS AS CUSTOMER ON DOA_USER_MASTER.PK_USER = CUSTOMER.PK_USER");
                                    while (!$row->EOF) {
                                        $product_details = $db_account->Execute("SELECT DOA_ORDER_ITEM.*, DOA_PRODUCT.PRODUCT_NAME, DOA_PRODUCT_SIZE.SIZE, DOA_PRODUCT_COLOR.COLOR FROM `DOA_ORDER_ITEM` LEFT JOIN DOA_PRODUCT ON DOA_ORDER_ITEM.PK_PRODUCT = DOA_PRODUCT.PK_PRODUCT LEFT JOIN DOA_PRODUCT_SIZE ON DOA_ORDER_ITEM.PK_PRODUCT_SIZE = DOA_PRODUCT_SIZE.PK_PRODUCT_SIZE LEFT JOIN DOA_PRODUCT_COLOR ON DOA_ORDER_ITEM.PK_PRODUCT_COLOR = DOA_PRODUCT_COLOR.PK_PRODUCT_COLOR WHERE DOA_ORDER_ITEM.PK_ORDER = ".$row->fields['PK_ORDER']); ?>
                                        <tr>
                                            <td onclick="editpage(<?=$row->fields['PK_ORDER']?>);"><?=$row->fields['ORDER_ID']?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_ORDER']?>);"><?=date('m/d/Y h:i A', strtotime($row->fields['CREATED_ON']))?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_ORDER']?>);"><?=$row->fields['CUSTOMER_NAME']?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_ORDER']?>);"><?=$row->fields['ORDER_TYPE']?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_ORDER']?>);"><?=$row->fields['ADDRESS']?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_ORDER']?>);"><?=$row->fields['PAYMENT_DETAILS']?></td>
                                            <td onclick="editpage(<?=$row->fields['PK_ORDER']?>);">
                                                <?php
                                                while (!$product_details->EOF) {
                                                    echo $product_details->fields['PRODUCT_NAME'].'<br>('.$product_details->fields['COLOR'].', '.$product_details->fields['SIZE'].') X '.$product_details->fields['PRODUCT_QUANTITY'].'<br><br>';
                                                    $product_details->MoveNext();
                                                } ?>
                                            </td>
                                            <td><p class="btn" style="background-color: <?=$row->fields['COLOR_CODE']?>; font-weight: bold;"><?=$row->fields['STATUS']?></p></td>
                                            <td>
                                                <a href="order_details.php?id=<?=$row->fields['PK_ORDER']?>" style="font-size: 25px;"><i class="fa fa-info-circle" title="Get Detains"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                            </td>
                                        </tr>
                                        <?php $row->MoveNext();
                                        $i++; } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once('../includes/footer.php');?>
<script>
    $(function () {
        $('#myTable').DataTable();
    });
    function ConfirmDelete(PK_ORDER)
    {
        let conf = confirm("Are you sure you want to delete?");
        if(conf) {
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                data: {FUNCTION_NAME: 'deleteProductData', PK_ORDER: PK_ORDER},
                success: function (data) {
                    window.location.href = `all_products.php`;
                }
            });
        }
    }
    function editpage(id){
        window.location.href = "product.php?id="+id;
    }
</script>
</body>
</html>