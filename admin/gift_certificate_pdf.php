<?php
require_once('../global/config.php');

if (empty($_GET['id'])) {
    $PK_USER_MASTER = '';
    $NAME = '';
    $BUSINESS_NAME = '';
    $BUSINESS_LOGO = '';
    $PK_GIFT_CERTIFICATE_SETUP ='';
    $DATE_OF_PURCHASE = '';
    $GIFT_NOTE = '';
    $AMOUNT = '';
    $ACTIVE = '';
    $GIFT_CERTIFICATE_NAME = '';
    $GIFT_CERTIFICATE_CODE = '';
} else {
    $res = $db->Execute("SELECT DOA_ACCOUNT_MASTER.BUSINESS_NAME, DOA_ACCOUNT_MASTER.BUSINESS_LOGO, DOA_LOCATION.LOCATION_NAME, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_GIFT_CERTIFICATE_MASTER.PK_USER_MASTER, DOA_GIFT_CERTIFICATE_MASTER.PK_GIFT_CERTIFICATE_SETUP, DOA_GIFT_CERTIFICATE_MASTER.DATE_OF_PURCHASE, DOA_GIFT_CERTIFICATE_MASTER.GIFT_NOTE, DOA_GIFT_CERTIFICATE_MASTER.AMOUNT, DOA_GIFT_CERTIFICATE_MASTER.ACTIVE, DOA_GIFT_CERTIFICATE_SETUP.GIFT_CERTIFICATE_NAME, DOA_GIFT_CERTIFICATE_SETUP.GIFT_CERTIFICATE_CODE FROM DOA_GIFT_CERTIFICATE_MASTER INNER JOIN DOA_GIFT_CERTIFICATE_SETUP ON DOA_GIFT_CERTIFICATE_MASTER.PK_GIFT_CERTIFICATE_SETUP=DOA_GIFT_CERTIFICATE_SETUP.PK_GIFT_CERTIFICATE_SETUP LEFT JOIN DOA_USER_MASTER ON DOA_GIFT_CERTIFICATE_MASTER.PK_USER_MASTER=DOA_USER_MASTER.PK_USER_MASTER LEFT JOIN DOA_USERS ON DOA_USERS.PK_USER=DOA_USER_MASTER.PK_USER INNER JOIN DOA_ACCOUNT_MASTER ON DOA_ACCOUNT_MASTER.PK_ACCOUNT_MASTER=DOA_GIFT_CERTIFICATE_MASTER.PK_ACCOUNT_MASTER LEFT JOIN DOA_LOCATION ON DOA_USERS.PK_LOCATION=DOA_LOCATION.PK_LOCATION WHERE DOA_GIFT_CERTIFICATE_MASTER.PK_GIFT_CERTIFICATE_MASTER = '$_GET[id]'");
    $PK_USER_MASTER = $res->fields['PK_USER_MASTER'];
    $NAME = $res->fields['NAME'];
    $BUSINESS_NAME = $res->fields['BUSINESS_NAME'];
    $BUSINESS_LOGO = $res->fields['BUSINESS_LOGO'];
    $PK_GIFT_CERTIFICATE_SETUP = $res->fields['PK_GIFT_CERTIFICATE_SETUP'];
    $DATE_OF_PURCHASE = $res->fields['DATE_OF_PURCHASE'];
    $GIFT_NOTE = $res->fields['GIFT_NOTE'];
    $AMOUNT = $res->fields['AMOUNT'];
    $ACTIVE = $res->fields['ACTIVE'];
    $GIFT_CERTIFICATE_NAME = $res->fields['GIFT_CERTIFICATE_NAME'];
    $GIFT_CERTIFICATE_CODE = $res->fields['GIFT_CERTIFICATE_CODE'];
}
?>

<!DOCTYPE html>
<html lang="">
<head>
    <!-- <link href="style.css" rel="stylesheet"> -->
    <!-- <link href="bootstrap.min.css" rel="stylesheet"> -->
    <title>Gift Certificate</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Comic+Neue:wght@700&display=swap" rel="stylesheet">
</head>
<style type="text/css">
    body {
        /*background-image: url('bg-body.png');
        height: 860px;
        background-position: center;
        background-size: cover;
        background-repeat: no-repeat;*/
    }
    .main-content {
        /*padding: 90px 0px 0;*/
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100%;
        flex-direction: column;
        position: relative;
        right: 0;
        left: 0;
        color: #626262;
    }
    .row {
        display: -webkit-box;
        display: -ms-flexbox;
        display: flex;
        -ms-flex-wrap: wrap;
        flex-wrap: wrap;
        margin-right: -15px;
        margin-left: -15px;
        width: 100%;
    }
    .container {
        max-width: 960px;
    }
    .font-custom {
        font-family: 'Comic Neue', cursive;
    }
    .top-row .top1 {
        min-width: 150px;
        flex: 0 0 0;
    }
    .d-block {
        display: block;
    }
    .justify-content-end {
        justify-content: end;
    }
    .container {
        width: 90% !important;
        max-width: 100%;
    }
    .mb-4 {
        margin-bottom: 1.5rem;
    }
    .font20 {
        font-size: 20px;
    }
    .font24 {
        font-size: 24px;
    }
    .font44 {
        font-size: 44px;
    }
    .font-normal {
        font-weight: 500;
    }
    .font-bold {
        font-weight: 600;
    }
    .font-700 {
        font-weight: 700;
    }
    .font-800 {
        font-weight: 800;
    }
    .h4, .h5, .h6, h4, h5, h6 {
        margin-top: 0px !important;
        margin-bottom: 0px !important;
    }
</style>
<body style="margin:0;">
<table style="width:100%;">
    <tr>
        <td style="width=100%">
            <img width="100%" src="<?=$http_path?>assets/images/b1.jpg" alt="">
        </td>
    </tr>
</table>


<div style="margin:50px 50px;height: 600px;font-family: calibri;">
    <table style="width:100%;">
        <tr>
            <td style="text-align:right;margin-right: 10px;width=100%">
                <h2 class="text-uppercase" style="margin-bottom:0px;margin-top:0px;margin-right: 10px;text-transform: uppercase;"><?php echo $BUSINESS_NAME?></h2>
                <h6 class="text-capitalize font-normal">(thousand oaks)</h6>
            </td>
            <td style="width=10%">
                <img src="<?=$http_path?>assets/images/logo.JPG"/>
            </td>
        </tr>
    </table>
    <table style="width:100%;">
        <tr>
            <td><h1 style="font-size: 44px; font-weight: 700; text-transform: capitalize;"><?php echo $GIFT_CERTIFICATE_NAME.'-'.$GIFT_CERTIFICATE_CODE?></h1></td>
        </tr>
    </table>
    <table style="width:100%;margin: 20px 0;">
        <tr>
            <td style="width=20%"><h1 style="font-size: 20px; display: block; text-transform: capitalize;">Amount : </h1></td>
            <td><h4 style="font-size: 24px; display: block; text-transform: capitalize;font-weight: 700;"><?php echo $AMOUNT?></h4></td>
        </tr>
    </table>
    <table style="width:100%;margin: 20px 0;">
        <tr style="vertical-align: top;">
            <td style="width=30%">
                <label style="font-size: 20px; display: block; text-transform: capitalize;margin: 0 0 10px;">Name : </label>
                <label style="font-size: 20px; display: block; text-transform: capitalize;"><?php echo $NAME?></label>
            </td>
            <td>
                <label>
                    <textarea style="width:100%;" rows="5"></textarea>
                </label>
            </td>
            <td style="text-align:center;">
                <label style="font-size: 20px; display: block; text-transform: uppercase;margin: 0 0 10px;">Purchase Date : </label>
                <label style="font-size: 20px; display: block; text-transform: capitalize;"><?php echo $DATE_OF_PURCHASE?></label>
            </td>
        </tr>
    </table>
    <table style="width:100%;margin: 20px 0;">
        <td>
            ** See Terms on next page
        </td>
    </table>
</div>
<table style="width:100%;">
    <tr>
        <td style="width=100%" >
            <img width="100%" src="<?=$http_path?>assets/images/b2.jpg" alt="">
        </td>
    </tr>
</table>
</body>
</html>
