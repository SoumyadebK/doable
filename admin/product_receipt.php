<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;
global $account_database;

$PK_ENROLLMENT_MASTER = $_GET['master_id'] ?: 0;
$RECEIPT_NUMBER = $_GET['receipt'] ?: 0;
$BILLING_REF = '';

//$RECEIPT_NUMBER_ARRAY = explode(',', $RECEIPT_NUMBER);
$business_details = $db->Execute("SELECT * FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER=".$_SESSION['PK_ACCOUNT_MASTER']); ?>

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
    </style>
</head>
<body>
<div>
    <button class="print-button" onclick="printPage()">Print</button>
</div>
<div id="printContent">
    <?php
    if ($PK_ENROLLMENT_MASTER == 0) {
        $wallet_data = $db_account->Execute("SELECT * FROM DOA_CUSTOMER_WALLET WHERE RECEIPT_NUMBER = '$RECEIPT_NUMBER' ORDER BY PK_CUSTOMER_WALLET DESC LIMIT 1");
        $user_data = $db->Execute("SELECT DOA_USERS.FIRST_NAME, DOA_USERS.LAST_NAME, DOA_USERS.PHONE, DOA_USERS.ADDRESS, DOA_USERS.CITY, DOA_STATES.STATE_NAME, DOA_USERS.ZIP, DOA_LOCATION.LOCATION_NAME FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER LEFT JOIN DOA_STATES ON DOA_STATES.PK_STATES=DOA_USERS.PK_STATES LEFT JOIN DOA_USER_LOCATION ON DOA_USER_LOCATION.PK_USER=DOA_USERS.PK_USER LEFT JOIN DOA_LOCATION ON DOA_LOCATION.PK_LOCATION=DOA_USER_LOCATION.PK_LOCATION WHERE DOA_USER_MASTER.PK_USER_MASTER = ".$wallet_data->fields['PK_USER_MASTER']);
    } else {
        $enrollment_billing_data = $db_account->Execute("SELECT DOA_ENROLLMENT_BILLING.BILLING_REF, DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_MASTER FROM DOA_ENROLLMENT_BILLING LEFT JOIN DOA_ENROLLMENT_PAYMENT ON DOA_ENROLLMENT_BILLING.PK_ENROLLMENT_BILLING = DOA_ENROLLMENT_PAYMENT.PK_ENROLLMENT_BILLING WHERE DOA_ENROLLMENT_PAYMENT.IS_ORIGINAL_RECEIPT = 1 AND DOA_ENROLLMENT_PAYMENT.RECEIPT_NUMBER = '$RECEIPT_NUMBER' LIMIT 1");
        $BILLING_REF = $enrollment_billing_data->fields['BILLING_REF'];

        $user_data = $db->Execute("SELECT DOA_USERS.FIRST_NAME, DOA_USERS.LAST_NAME, DOA_USERS.PHONE, DOA_USERS.ADDRESS, DOA_USERS.CITY, DOA_STATES.STATE_NAME, DOA_USERS.ZIP, DOA_LOCATION.LOCATION_NAME FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER LEFT JOIN DOA_STATES ON DOA_STATES.PK_STATES=DOA_USERS.PK_STATES LEFT JOIN DOA_USER_LOCATION ON DOA_USER_LOCATION.PK_USER=DOA_USERS.PK_USER LEFT JOIN DOA_LOCATION ON DOA_LOCATION.PK_LOCATION=DOA_USER_LOCATION.PK_LOCATION LEFT JOIN $account_database.DOA_ENROLLMENT_MASTER AS DOA_ENROLLMENT_MASTER ON DOA_ENROLLMENT_MASTER.PK_USER_MASTER = DOA_USER_MASTER.PK_USER_MASTER WHERE DOA_ENROLLMENT_MASTER.PK_ENROLLMENT_MASTER = " . $PK_ENROLLMENT_MASTER);
    }

    $enrollment_payment = $db_account->Execute("SELECT DOA_ENROLLMENT_PAYMENT.*, DOA_PAYMENT_TYPE.PAYMENT_TYPE FROM DOA_ENROLLMENT_PAYMENT LEFT JOIN $master_database.DOA_PAYMENT_TYPE AS DOA_PAYMENT_TYPE ON DOA_ENROLLMENT_PAYMENT.PK_PAYMENT_TYPE = DOA_PAYMENT_TYPE.PK_PAYMENT_TYPE WHERE DOA_ENROLLMENT_PAYMENT.IS_ORIGINAL_RECEIPT = 1 AND RECEIPT_NUMBER = '$RECEIPT_NUMBER'");

    $DETAILS_AMOUNT = '';
    $PAYMENT_DETAILS = '';
    $TOTAL_AMOUNT = 0;
    $PAYMENT_METHOD = '';
    $TYPE = '';
    while (!$enrollment_payment->EOF) {
        $TYPE = $enrollment_payment->fields['TYPE'];
        if ($enrollment_payment->fields['TYPE'] == 'Payment' || $enrollment_payment->fields['TYPE'] == 'Wallet') {
            if ($enrollment_payment->fields['PK_PAYMENT_TYPE'] == 2) {
                $PAYMENT_INFO = json_decode($enrollment_payment->fields['PAYMENT_INFO']);
                $PAYMENT_DETAILS = "<tr>
                                <td>" . $enrollment_payment->fields['PAYMENT_TYPE'] . "# : </td>
                                <td style='text-align:right'>" . $PAYMENT_INFO->CHECK_NUMBER . "</td>
                            </tr>";
            } elseif (in_array($enrollment_payment->fields['PK_PAYMENT_TYPE'], [1, 8, 9, 10, 11, 13, 14])) {
                $PAYMENT_INFO = json_decode($enrollment_payment->fields['PAYMENT_INFO']);
                $PAYMENT_DETAILS = "<tr>
                                <td>" . $enrollment_payment->fields['PAYMENT_TYPE'] . "# : </td>
                                <td style='text-align:right'>**** " . $PAYMENT_INFO->LAST4 . "</td>
                            </tr>";
            }
        }

        $PAYMENT_METHOD = $enrollment_payment->fields['PAYMENT_TYPE'];
        $DETAILS_AMOUNT .= '$'.number_format($enrollment_payment->fields['AMOUNT'], 2).'<br>';
        $TOTAL_AMOUNT += $enrollment_payment->fields['AMOUNT'];
        $enrollment_payment->MoveNext();
    }

    $BUSINESS_NAME = $business_details->fields['BUSINESS_NAME'];
    $FULL_NAME = $user_data->fields['FIRST_NAME'] . " " . $user_data->fields['LAST_NAME'];
    $LOCATION_NAME = $user_data->fields['LOCATION_NAME'];
    $STATE_NAME = $user_data->fields['STATE_NAME'];
    $ZIP = $user_data->fields['ZIP'];
    $PHONE = $user_data->fields['PHONE'];
    $AMOUNT = '$' . number_format($TOTAL_AMOUNT, 2);
    $TOTAL = '$' . number_format($TOTAL_AMOUNT, 2);
    $PAYMENT_DATE = date('m-d-Y', strtotime($enrollment_payment->fields['PAYMENT_DATE']));
    ?>

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
                                                <td align="left"> <img src="http://www.supah.it/dribbble/017/logo.png" width="32" height="32" alt="logo" border="0" /></td>
                                            </tr>
                                            <tr class="hiddenMobile">
                                                <td height="40"></td>
                                            </tr>
                                            <tr class="visibleMobile">
                                                <td height="20"></td>
                                            </tr>
                                            <tr>
                                                <td style="font-size: 12px; color: #5b5b5b; font-family: 'Open Sans', sans-serif; line-height: 18px; vertical-align: top; text-align: left;">
                                                    Hello, Philip Brooks.
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
                                                <td style="font-size: 21px; color: #ff0000; letter-spacing: -1px; font-family: 'Open Sans', sans-serif; line-height: 1; vertical-align: top; text-align: right;">
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
                                                    <small>ORDER</small> #800000025<br />
                                                    <small>MARCH 4TH 2016</small>
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
                                        <small>SKU</small>
                                    </th>
                                    <th style="font-size: 12px; font-family: 'Open Sans', sans-serif; color: #5b5b5b; font-weight: normal; line-height: 1; vertical-align: top; padding: 0 0 7px;" align="center">
                                        Quantity
                                    </th>
                                    <th style="font-size: 12px; font-family: 'Open Sans', sans-serif; color: #1e2b33; font-weight: normal; line-height: 1; vertical-align: top; padding: 0 0 7px;" align="right">
                                        Subtotal
                                    </th>
                                </tr>
                                <tr>
                                    <td height="1" style="background: #bebebe;" colspan="4"></td>
                                </tr>
                                <tr>
                                    <td height="10" colspan="4"></td>
                                </tr>
                                <tr>
                                    <td style="font-size: 12px; font-family: 'Open Sans', sans-serif; color: #ff0000;  line-height: 18px;  vertical-align: top; padding:10px 0;" class="article">
                                        Beats Studio Over-Ear Headphones
                                    </td>
                                    <td style="font-size: 12px; font-family: 'Open Sans', sans-serif; color: #646a6e;  line-height: 18px;  vertical-align: top; padding:10px 0;"><small>MH792AM/A</small></td>
                                    <td style="font-size: 12px; font-family: 'Open Sans', sans-serif; color: #646a6e;  line-height: 18px;  vertical-align: top; padding:10px 0;" align="center">1</td>
                                    <td style="font-size: 12px; font-family: 'Open Sans', sans-serif; color: #1e2b33;  line-height: 18px;  vertical-align: top; padding:10px 0;" align="right">$299.95</td>
                                </tr>
                                <tr>
                                    <td height="1" colspan="4" style="border-bottom:1px solid #e4e4e4"></td>
                                </tr>
                                <tr>
                                    <td style="font-size: 12px; font-family: 'Open Sans', sans-serif; color: #ff0000;  line-height: 18px;  vertical-align: top; padding:10px 0;" class="article">Beats RemoteTalk Cable</td>
                                    <td style="font-size: 12px; font-family: 'Open Sans', sans-serif; color: #646a6e;  line-height: 18px;  vertical-align: top; padding:10px 0;"><small>MHDV2G/A</small></td>
                                    <td style="font-size: 12px; font-family: 'Open Sans', sans-serif; color: #646a6e;  line-height: 18px;  vertical-align: top; padding:10px 0;" align="center">1</td>
                                    <td style="font-size: 12px; font-family: 'Open Sans', sans-serif; color: #1e2b33;  line-height: 18px;  vertical-align: top; padding:10px 0;" align="right">$29.95</td>
                                </tr>
                                <tr>
                                    <td height="1" colspan="4" style="border-bottom:1px solid #e4e4e4"></td>
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
                                        $329.90
                                    </td>
                                </tr>
                                <tr>
                                    <td style="font-size: 12px; font-family: 'Open Sans', sans-serif; color: #646a6e; line-height: 22px; vertical-align: top; text-align:right; ">
                                        Shipping &amp; Handling
                                    </td>
                                    <td style="font-size: 12px; font-family: 'Open Sans', sans-serif; color: #646a6e; line-height: 22px; vertical-align: top; text-align:right; ">
                                        $15.00
                                    </td>
                                </tr>
                                <tr>
                                    <td style="font-size: 12px; font-family: 'Open Sans', sans-serif; color: #000; line-height: 22px; vertical-align: top; text-align:right; ">
                                        <strong>Grand Total (Incl.Tax)</strong>
                                    </td>
                                    <td style="font-size: 12px; font-family: 'Open Sans', sans-serif; color: #000; line-height: 22px; vertical-align: top; text-align:right; ">
                                        <strong>$344.90</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="font-size: 12px; font-family: 'Open Sans', sans-serif; color: #b0b0b0; line-height: 22px; vertical-align: top; text-align:right; "><small>TAX</small></td>
                                    <td style="font-size: 12px; font-family: 'Open Sans', sans-serif; color: #b0b0b0; line-height: 22px; vertical-align: top; text-align:right; ">
                                        <small>$72.40</small>
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
                                                    Philip Brooks<br> Public Wales, Somewhere<br> New York NY<br> 4468, United States<br> T: 202-555-0133
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
                                                    Credit Card<br> Credit Card Type: Visa<br> Worldpay Transaction ID: <a href="#" style="color: #ff0000; text-decoration:underline;">4185939336</a><br>
                                                    <a href="#" style="color:#b0b0b0;">Right of Withdrawal</a>
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
                                                    <strong>SHIPPING INFORMATION</strong>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td width="100%" height="10"></td>
                                            </tr>
                                            <tr>
                                                <td style="font-size: 12px; font-family: 'Open Sans', sans-serif; color: #5b5b5b; line-height: 20px; vertical-align: top; ">
                                                    Sup Inc<br> Another Place, Somewhere<br> New York NY<br> 4468, United States<br> T: 202-555-0171
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
                                            <tr>
                                                <td style="font-size: 11px; font-family: 'Open Sans', sans-serif; color: #5b5b5b; line-height: 1; vertical-align: top; ">
                                                    <strong>SHIPPING METHOD</strong>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td width="100%" height="10"></td>
                                            </tr>
                                            <tr>
                                                <td style="font-size: 12px; font-family: 'Open Sans', sans-serif; color: #5b5b5b; line-height: 20px; vertical-align: top; ">
                                                    UPS: U.S. Shipping Services
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