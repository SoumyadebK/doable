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
    $res = $db->Execute("SELECT DOA_ACCOUNT_MASTER.BUSINESS_NAME, DOA_ACCOUNT_MASTER.BUSINESS_LOGO, DOA_LOCATION.LOCATION_NAME, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_GIFT_CERTIFICATE_MASTER.PK_USER_MASTER, DOA_GIFT_CERTIFICATE_MASTER.PK_GIFT_CERTIFICATE_SETUP, DOA_GIFT_CERTIFICATE_MASTER.DATE_OF_PURCHASE, DOA_GIFT_CERTIFICATE_MASTER.GIFT_NOTE, DOA_GIFT_CERTIFICATE_MASTER.AMOUNT, DOA_GIFT_CERTIFICATE_MASTER.ACTIVE, DOA_GIFT_CERTIFICATE_SETUP.GIFT_CERTIFICATE_NAME, DOA_GIFT_CERTIFICATE_SETUP.GIFT_CERTIFICATE_CODE FROM DOA_GIFT_CERTIFICATE_MASTER INNER JOIN DOA_GIFT_CERTIFICATE_SETUP ON DOA_GIFT_CERTIFICATE_MASTER.PK_GIFT_CERTIFICATE_SETUP=DOA_GIFT_CERTIFICATE_SETUP.PK_GIFT_CERTIFICATE_SETUP INNER JOIN DOA_USER_MASTER ON DOA_GIFT_CERTIFICATE_MASTER.PK_USER_MASTER=DOA_USER_MASTER.PK_USER_MASTER INNER JOIN DOA_USERS ON DOA_USERS.PK_USER=DOA_USER_MASTER.PK_USER INNER JOIN DOA_ACCOUNT_MASTER ON DOA_ACCOUNT_MASTER.PK_ACCOUNT_MASTER=DOA_GIFT_CERTIFICATE_MASTER.PK_ACCOUNT_MASTER LEFT JOIN DOA_LOCATION ON DOA_USERS.PK_LOCATION=DOA_LOCATION.PK_LOCATION WHERE DOA_GIFT_CERTIFICATE_MASTER.PK_GIFT_CERTIFICATE_MASTER = '$_GET[id]'");
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
    <link href="http://localhost/doable/assets/gift_certificate/style.css" rel="stylesheet">
    <link href="http://localhost/doable/assets/gift_certificate/bootstrap.min.css" rel="stylesheet">
    <title>Gift Certificate</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Comic+Neue:wght@700&display=swap" rel="stylesheet">
</head>
<body>
<div class="main-content ">
    <div class="container">
        <div class="top-row row justify-content-end" style="margin-bottom:3rem;">
            <div class="top1 text-right">
                <h2 class="text-uppercase"><?php echo $BUSINESS_NAME?></h2>
                <h6 class="text-capitalize font-normal">Thousand Oaks</h6>
            </div>
            <div class="top1 text-center">
                <img src="<?php echo $BUSINESS_LOGO?>" style="height: 75px; width: auto;"/>
            </div>
        </div>
        <div class="row" style="margin-bottom: 4rem;">
            <div class="col-lg-12">
                <h1 class="text-uppercase font-700 font-custom font44"><?php echo $GIFT_CERTIFICATE_NAME.'-'.$GIFT_CERTIFICATE_CODE?></h1>
            </div>
        </div>
        <div class="row" style="margin-bottom: 8rem;">
            <div class="col-lg-2 font20 font-normal text-uppercase">Amount</div>
            <div class="col-lg-4 text-left">
                <h4 class="font-700 font24"><?php echo $AMOUNT ?></h4>
            </div>
        </div>
        <div class="row" style="margin-bottom:4rem;">
            <div class="col-lg-4">
                <label class="d-block text-capitalize font-normal font20">Name</label>
                <label class="d-block text-capitalize font-normal font20"><?php echo $NAME?></label>
            </div>
            <div class="col-lg-4">
                <textarea class="form-control" placeholder="" id="floatingTextarea2" style="height: 100px"><?php echo $GIFT_NOTE ?></textarea>
            </div>
            <div class="col-lg-4 text-center">
                <label class="d-block text-uppercase font-normal font20">Purchase Date</label>
                <label class="d-block text-capitalize font-normal font20"><?php echo $DATE_OF_PURCHASE ?></label>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-12">
                <p>** See Terms on next page</p>
            </div>
        </div>
    </div>
</div>
</body>
</html>
