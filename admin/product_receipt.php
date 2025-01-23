<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $upload_path;
global $master_database;

$title = "Invoice";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5]) ){
    header("location:../login.php");
    exit;
}

if(!empty($_POST)) {
    $ORDER_DATA['ORDER_TYPE'] = $_POST['ORDER_TYPE'];
    $ORDER_DATA['PK_ORDER_STATUS'] = $_POST['PK_ORDER_STATUS'];
    db_perform_account('DOA_ORDER', $ORDER_DATA, 'update', ' PK_ORDER = ' . $_POST['PK_ORDER']);
}

$PK_ORDER = $_GET['id'];
$order_data = $db_account->Execute("SELECT DOA_ORDER.*, CONCAT(CUSTOMER.FIRST_NAME, ' ', CUSTOMER.LAST_NAME) AS CUSTOMER_NAME, DOA_COUNTRY.COUNTRY_NAME, DOA_STATES.STATE_NAME, CUSTOMER.PK_USER FROM `DOA_ORDER` LEFT JOIN $master_database.DOA_COUNTRY AS DOA_COUNTRY ON DOA_ORDER.PK_COUNTRY = DOA_COUNTRY.PK_COUNTRY LEFT JOIN $master_database.DOA_STATES AS DOA_STATES ON DOA_ORDER.PK_STATES = DOA_STATES.PK_STATES LEFT JOIN $master_database.DOA_USER_MASTER AS DOA_USER_MASTER ON DOA_ORDER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER LEFT JOIN $master_database.DOA_USERS AS CUSTOMER ON DOA_USER_MASTER.PK_USER = CUSTOMER.PK_USER WHERE DOA_ORDER.PK_ORDER = '$PK_ORDER'");
$customer_details = $db->Execute("SELECT DOA_USERS.*, DOA_COUNTRY.COUNTRY_NAME, DOA_STATES.STATE_NAME FROM DOA_USERS LEFT JOIN DOA_COUNTRY AS DOA_COUNTRY ON DOA_USERS.PK_COUNTRY = DOA_COUNTRY.PK_COUNTRY LEFT JOIN DOA_STATES AS DOA_STATES ON DOA_USERS.PK_STATES = DOA_STATES.PK_STATES WHERE PK_USER = ".$order_data->fields['PK_USER']);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title> Order confirmation </title>
    <meta name="robots" content="noindex,nofollow" />
    <meta name="viewport" content="width=device-width; initial-scale=1.0;" />
    <style type="text/css">
        @import url(https://fonts.googleapis.com/css?family=Open+Sans:400,700);
        body { margin: 0; padding: 0; background: #e1e1e1; }
        div, p, a, li, td { -webkit-text-size-adjust: none; }
        .ReadMsgBody { width: 100%; background-color: #ffffff; }
        .ExternalClass { width: 100%; background-color: #ffffff; }
        body { width: 100%; height: 100%; background-color: #e1e1e1; margin: 0; padding: 0; -webkit-font-smoothing: antialiased; }
        html { width: 100%; }
        p { padding: 0 !important; margin-top: 0 !important; margin-right: 0 !important; margin-bottom: 0 !important; margin-left: 0 !important; }
        .visibleMobile { display: none; }
        .hiddenMobile { display: block; }

        @media only screen and (max-width: 600px) {
            body { width: auto !important; }
            table[class=fullTable] { width: 96% !important; clear: both; }
            table[class=fullPadding] { width: 85% !important; clear: both; }
            table[class=col] { width: 45% !important; }
            .erase { display: none; }
        }

        @media only screen and (max-width: 420px) {
            table[class=fullTable] { width: 100% !important; clear: both; }
            table[class=fullPadding] { width: 85% !important; clear: both; }
            table[class=col] { width: 100% !important; clear: both; }
            table[class=col] td { text-align: left !important; }
            .erase { display: none; font-size: 0; max-height: 0; line-height: 0; padding: 0; }
            .visibleMobile { display: block !important; }
            .hiddenMobile { display: none !important; }
        }

        /* Optional: You can style your button */
        .print-button {
            background-color: #4CAF50; /* Green */
            border: none;
            color: white;
            padding: 10px 20px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 12px;
            margin: 4px 2px;
            cursor: pointer;
            float: right;
        }
    </style>
</head>
<body>
<div>
    <button class="print-button" onclick="printPage()">Print</button>
</div>
<div id="printContent">

    <!-- Header -->
    <table width="100%" border="0" cellpadding="0" cellspacing="0" align="center" class="fullTable" bgcolor="#e1e1e1">
        <tr>
            <td height="20"></td>
        </tr>
        <tr>
            <td>
                <table width="600" border="0" cellpadding="0" cellspacing="0" align="center" class="fullTable" bgcolor="#ffffff" style="border-radius: 10px 10px 0 0;">
                    <tr class="hiddenMobile">
                        <td height="40"></td>
                    </tr>
                    <tr class="visibleMobile">
                        <td height="30"></td>
                    </tr>

                    <tr>
                        <td>
                            <table width="480" border="0" cellpadding="0" cellspacing="0" align="center" class="fullPadding">
                                <tbody>
                                <tr>
                                    <td>
                                        <table width="220" border="0" cellpadding="0" cellspacing="0" align="left" class="col">
                                            <tbody>
                                            <tr>
                                                <td align="left"> <img src="../assets/images/doable_logo.png" width="70" height="35" alt="logo" border="0" /></td>
                                            </tr>
                                            <tr class="hiddenMobile">
                                                <td height="40"></td>
                                            </tr>
                                            <tr class="visibleMobile">
                                                <td height="20"></td>
                                            </tr>
                                            <tr>
                                                <td style="font-size: 12px; color: #5b5b5b; font-family: 'Open Sans', sans-serif; line-height: 18px; vertical-align: top; text-align: left;">
                                                    Hello, <?=$order_data->fields['CUSTOMER_NAME']?>.
                                                    <br> Thank you for shopping from our store and for your order.
                                                </td>
                                            </tr>
                                            </tbody>
                                        </table>
                                        <table width="220" border="0" cellpadding="0" cellspacing="0" align="right" class="col">
                                            <tbody>
                                            <tr class="visibleMobile">
                                                <td height="20"></td>
                                            </tr>
                                            <tr>
                                                <td height="5"></td>
                                            </tr>
                                            <tr>
                                                <td style="font-size: 21px; color: #39B54A; letter-spacing: -1px; font-family: 'Open Sans', sans-serif;  line-height: 1; vertical-align: top; text-align: right;">
                                                    Invoice
                                                </td>
                                            </tr>
                                            <tr>
                                            <tr class="hiddenMobile">
                                                <td height="50"></td>
                                            </tr>
                                            <tr class="visibleMobile">
                                                <td height="20"></td>
                                            </tr>
                                            <tr>
                                                <td style="font-size: 12px; color: #5b5b5b; font-family: 'Open Sans', sans-serif; line-height: 18px; vertical-align: top; text-align: right;">
                                                    <small>ORDER</small> #<?=$order_data->fields['ORDER_ID']?><br />
                                                    <small><?=$order_data->fields['CREATED_ON']?></small>
                                                </td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <!-- /Header -->
    <!-- Order Details -->
    <table width="100%" border="0" cellpadding="0" cellspacing="0" align="center" class="fullTable" bgcolor="#e1e1e1">
        <tbody>
        <tr>
            <td>
                <table width="600" border="0" cellpadding="0" cellspacing="0" align="center" class="fullTable" bgcolor="#ffffff">
                    <tbody>
                    <tr>
                    <tr class="hiddenMobile">
                        <td height="60"></td>
                    </tr>
                    <tr class="visibleMobile">
                        <td height="40"></td>
                    </tr>
                    <tr>
                        <td>
                            <table width="480" border="0" cellpadding="0" cellspacing="0" align="center" class="fullPadding">
                                <tbody>
                                <tr>
                                    <th style="font-size: 12px; font-family: 'Open Sans', sans-serif; color: #5b5b5b; font-weight: normal; line-height: 1; vertical-align: top; padding: 0 10px 7px 0;" width="52%" align="left">
                                        Item
                                    </th>
                                    <th style="font-size: 12px; font-family: 'Open Sans', sans-serif; color: #5b5b5b; font-weight: normal; line-height: 1; vertical-align: top; padding: 0 0 7px;" align="left">
                                        <small>Size</small>
                                    </th>
                                    <th style="font-size: 12px; font-family: 'Open Sans', sans-serif; color: #5b5b5b; font-weight: normal; line-height: 1; vertical-align: top; padding: 0 0 7px;" align="left">
                                        <small>Color</small>
                                    </th>
                                    <th style="font-size: 12px; font-family: 'Open Sans', sans-serif; color: #5b5b5b; font-weight: normal; line-height: 1; vertical-align: top; padding: 0 0 7px;" align="center">
                                        Quantity
                                    </th>
                                    <th style="font-size: 12px; font-family: 'Open Sans', sans-serif; color: #1e2b33; font-weight: normal; line-height: 1; vertical-align: top; padding: 0 0 7px;" align="right">
                                        Subtotal
                                    </th>
                                </tr>
                                <tr>
                                    <td height="1" colspan="6" style="border-bottom:1px solid #bebebe"></td>
                                </tr>
                                <tr>
                                    <td height="10" colspan="6"></td>
                                </tr>
                                <?php
                                $all_item_total = 0;
                                $product_details = $db_account->Execute("SELECT DOA_ORDER_ITEM.*, DOA_PRODUCT.PRODUCT_NAME, DOA_PRODUCT.PRODUCT_IMAGES, DOA_PRODUCT_SIZE.SIZE, DOA_PRODUCT_COLOR.COLOR FROM `DOA_ORDER_ITEM` LEFT JOIN DOA_PRODUCT ON DOA_ORDER_ITEM.PK_PRODUCT = DOA_PRODUCT.PK_PRODUCT LEFT JOIN DOA_PRODUCT_SIZE ON DOA_ORDER_ITEM.PK_PRODUCT_SIZE = DOA_PRODUCT_SIZE.PK_PRODUCT_SIZE LEFT JOIN DOA_PRODUCT_COLOR ON DOA_ORDER_ITEM.PK_PRODUCT_COLOR = DOA_PRODUCT_COLOR.PK_PRODUCT_COLOR WHERE DOA_ORDER_ITEM.PK_ORDER = ".$PK_ORDER);
                                while (!$product_details->EOF) {
                                $item_total = $product_details->fields['PRODUCT_QUANTITY'] * $product_details->fields['PRODUCT_PRICE'];
                                $all_item_total += $item_total; ?>
                                <tr>
                                    <td style="font-size: 12px; font-family: 'Open Sans', sans-serif; color: #39B54A;  line-height: 18px;  vertical-align: top; padding:10px 0;" class="article"><?=$product_details->fields['PRODUCT_NAME']?></td>
                                    <td style="font-size: 12px; font-family: 'Open Sans', sans-serif; color: #646a6e;  line-height: 18px;  vertical-align: top; padding:10px 0;"><small><?=$product_details->fields['SIZE']?></small></td>
                                    <td style="font-size: 12px; font-family: 'Open Sans', sans-serif; color: #646a6e;  line-height: 18px;  vertical-align: top; padding:10px 0;"><small><?=$product_details->fields['COLOR']?></small></td>
                                    <td style="font-size: 12px; font-family: 'Open Sans', sans-serif; color: #646a6e;  line-height: 18px;  vertical-align: top; padding:10px 0;" align="center"><?=$product_details->fields['PRODUCT_QUANTITY']?> X $<?=number_format($product_details->fields['PRODUCT_PRICE'], 2)?></td>
                                    <td style="font-size: 12px; font-family: 'Open Sans', sans-serif; color: #1e2b33;  line-height: 18px;  vertical-align: top; padding:10px 0;" align="right">$<?=number_format($item_total,2)?></td>
                                </tr>
                                    <?php $product_details->MoveNext();
                                } ?>
                                <tr>
                                    <td height="1" colspan="6" style="border-bottom:1px solid #e4e4e4"></td>
                                </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td height="20"></td>
                    </tr>
                    </tbody>
                </table>
            </td>
        </tr>
        </tbody>
    </table>
    <!-- /Order Details -->
    <!-- Total -->
    <table width="100%" border="0" cellpadding="0" cellspacing="0" align="center" class="fullTable" bgcolor="#e1e1e1">
        <tbody>
        <tr>
            <td>
                <table width="600" border="0" cellpadding="0" cellspacing="0" align="center" class="fullTable" bgcolor="#ffffff">
                    <tbody>
                    <tr>
                        <td>

                            <!-- Table Total -->
                            <table width="480" border="0" cellpadding="0" cellspacing="0" align="center" class="fullPadding">
                                <tbody>
                                    <tr>
                                        <td style="font-size: 12px; font-family: 'Open Sans', sans-serif; color: #646a6e; line-height: 22px; vertical-align: top; text-align:right; ">
                                            Subtotal
                                        </td>
                                        <td style="font-size: 12px; font-family: 'Open Sans', sans-serif; color: #646a6e; line-height: 22px; vertical-align: top; text-align:right; white-space:nowrap;" width="80">
                                            $<?=number_format($order_data->fields['ITEM_TOTAL'], 2)?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="font-size: 12px; font-family: 'Open Sans', sans-serif; color: #646a6e; line-height: 22px; vertical-align: top; text-align:right; ">
                                            Shipping &amp; Handling
                                        </td>
                                        <td style="font-size: 12px; font-family: 'Open Sans', sans-serif; color: #646a6e; line-height: 22px; vertical-align: top; text-align:right; ">
                                            <?=number_format($order_data->fields['SHIPPING_CHARGE'], 2)?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="font-size: 12px; font-family: 'Open Sans', sans-serif; color: #b0b0b0; line-height: 22px; vertical-align: top; text-align:right; "><small>TAX</small></td>
                                        <td style="font-size: 12px; font-family: 'Open Sans', sans-serif; color: #b0b0b0; line-height: 22px; vertical-align: top; text-align:right; ">
                                            <small><?=number_format($order_data->fields['SALES_TAX'], 2)?>% </small>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="font-size: 12px; font-family: 'Open Sans', sans-serif; color: #000; line-height: 22px; vertical-align: top; text-align:right; ">
                                            <strong>Grand Total (Incl.Tax)</strong>
                                        </td>
                                        <td style="font-size: 12px; font-family: 'Open Sans', sans-serif; color: #000; line-height: 22px; vertical-align: top; text-align:right; ">
                                            <strong> $<?=number_format($order_data->fields['ORDER_TOTAL'], 2)?> </strong>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <!-- /Table Total -->

                        </td>
                    </tr>
                    </tbody>
                </table>
            </td>
        </tr>
        </tbody>
    </table>
    <!-- /Total -->
    <!-- Information -->
    <table width="100%" border="0" cellpadding="0" cellspacing="0" align="center" class="fullTable" bgcolor="#e1e1e1">
        <tbody>
        <tr>
            <td>
                <table width="600" border="0" cellpadding="0" cellspacing="0" align="center" class="fullTable" bgcolor="#ffffff">
                    <tbody>
                    <tr>
                    <tr class="hiddenMobile">
                        <td height="60"></td>
                    </tr>
                    <tr class="visibleMobile">
                        <td height="40"></td>
                    </tr>
                    <tr>
                        <td>
                            <table width="480" border="0" cellpadding="0" cellspacing="0" align="center" class="fullPadding">
                                <tbody>
                                <tr>
                                    <td>
                                        <table width="220" border="0" cellpadding="0" cellspacing="0" align="left" class="col">

                                            <tbody>
                                            <tr>
                                                <td style="font-size: 11px; font-family: 'Open Sans', sans-serif; color: #5b5b5b; line-height: 1; vertical-align: top; ">
                                                    <strong>BILLING INFORMATION</strong>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td width="100%" height="10"></td>
                                            </tr>
                                            <tr>
                                                <td style="font-size: 12px; font-family: 'Open Sans', sans-serif; color: #5b5b5b; line-height: 20px; vertical-align: top; ">
                                                    <?=$customer_details->fields['ADDRESS']?><br>
                                                    <?php if(!empty($customer_details->fields['ADDRESS_1'])){
                                                        echo $customer_details->fields['ADDRESS_1']; ?>
                                                    <br>
                                                    <?php }?>
                                                    <?=$customer_details->fields['COUNTRY_NAME']?><br> <?=$customer_details->fields['STATE_NAME']?><br><?=$customer_details->fields['CITY']?><br> <?=$customer_details->fields['ZIP']?>
                                                </td>
                                            </tr>
                                            </tbody>
                                        </table>


                                        <table width="220" border="0" cellpadding="0" cellspacing="0" align="right" class="col">
                                            <tbody>
                                            <tr class="visibleMobile">
                                                <td height="20"></td>
                                            </tr>
                                            <tr>
                                                <td style="font-size: 11px; font-family: 'Open Sans', sans-serif; color: #5b5b5b; line-height: 1; vertical-align: top; ">
                                                    <strong>PAYMENT METHOD</strong>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td width="100%" height="10"></td>
                                            </tr>
                                            <tr>
                                                <td style="font-size: 12px; font-family: 'Open Sans', sans-serif; color: #5b5b5b; line-height: 20px; vertical-align: top; ">
                                                    <?=$order_data->fields['PAYMENT_DETAILS']?><!--<br> Credit Card Type: Visa<br> Worldpay Transaction ID: <a href="#" style="color: #ff0000; text-decoration:underline;">4185939336</a><br>
                                                    <a href="#" style="color:#b0b0b0;">Right of Withdrawal</a>-->
                                                </td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <table width="480" border="0" cellpadding="0" cellspacing="0" align="center" class="fullPadding">
                                <tbody>
                                <tr>
                                    <td>
                                        <table width="220" border="0" cellpadding="0" cellspacing="0" align="left" class="col">
                                            <tbody>
                                            <tr class="hiddenMobile">
                                                <td height="35"></td>
                                            </tr>
                                            <tr class="visibleMobile">
                                                <td height="20"></td>
                                            </tr>
                                            <tr>
                                                <td style="font-size: 11px; font-family: 'Open Sans', sans-serif; color: #5b5b5b; line-height: 1; vertical-align: top; ">
                                                    <strong>ORDER TYPE</strong>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td width="100%" height="10"></td>
                                            </tr>
                                            <tr>
                                                <td style="font-size: 12px; font-family: 'Open Sans', sans-serif; color: #5b5b5b; line-height: 20px; vertical-align: top; ">
                                                    <?=$order_data->fields['ORDER_TYPE']?>
                                                </td>
                                            </tr>
                                            </tbody>
                                        </table>

                                        <table width="220" border="0" cellpadding="0" cellspacing="0" align="right" class="col">
                                            <tbody>
                                            <tr class="hiddenMobile">
                                                <td height="35"></td>
                                            </tr>
                                            <tr class="visibleMobile">
                                                <td height="20"></td>
                                            </tr>
                                            <?php if($order_data->fields['ORDER_TYPE']!='PICK_UP'){?>
                                            <tr>
                                                <td style="font-size: 11px; font-family: 'Open Sans', sans-serif; color: #5b5b5b; line-height: 1; vertical-align: top; ">
                                                    <strong>SHIPPING INFORMATION</strong>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td width="100%" height="10"></td>
                                            </tr>
                                            <tr>
                                                <td style="font-size: 12px; font-family: 'Open Sans', sans-serif; color: #5b5b5b; line-height: 20px; vertical-align: top; ">
                                                    <?=$order_data->fields['ADDRESS']?><br>
                                                    <?php if(!empty($customer_details->fields['ADDRESS_1'])){
                                                        echo $customer_details->fields['ADDRESS_1']; ?>
                                                        <br>
                                                    <?php }?>
                                                    <?=$order_data->fields['COUNTRY_NAME']?><br> <?=$order_data->fields['STATE_NAME']?><br>  <?=$order_data->fields['CITY']?><br> <?=$order_data->fields['ZIP']?>
                                                </td>
                                            </tr>
                                            <?php } ?>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                    <tr class="hiddenMobile">
                        <td height="60"></td>
                    </tr>
                    <tr class="visibleMobile">
                        <td height="30"></td>
                    </tr>
                    </tbody>
                </table>
            </td>
        </tr>
        </tbody>
    </table>
    <!-- /Information -->
    <table width="100%" border="0" cellpadding="0" cellspacing="0" align="center" class="fullTable" bgcolor="#e1e1e1">

        <tr>
            <td>
                <table width="600" border="0" cellpadding="0" cellspacing="0" align="center" class="fullTable" bgcolor="#ffffff" style="border-radius: 0 0 10px 10px;">
                    <tr>
                        <td>
                            <table width="480" border="0" cellpadding="0" cellspacing="0" align="center" class="fullPadding">
                                <tbody>
                                <tr>
                                    <td style="font-size: 12px; color: #5b5b5b; font-family: 'Open Sans', sans-serif; line-height: 18px; vertical-align: top; text-align: left;">
                                        Have a nice day.
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                    <tr class="spacer">
                        <td height="50"></td>
                    </tr>

                </table>
            </td>
        </tr>
        <tr>
            <td height="20"></td>
        </tr>
    </table>

</div>
</body>
</html>

<script>
    function printPage() {
        window.print();
    }
</script>