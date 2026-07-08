<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;
global $account_database;

if (empty($_GET['id'])) {
    $PK_USER_MASTER = '';
    $NAME = '';
    $BUSINESS_NAME = '';
    $BUSINESS_LOGO = '';
    $PK_GIFT_CERTIFICATE_SETUP = '';
    $DATE_OF_PURCHASE = '';
    $GIFT_NOTE = '';
    $AMOUNT = '';
    $ACTIVE = '';
    $GIFT_CERTIFICATE_NAME = '';
    $GIFT_CERTIFICATE_CODE = '';
} else {
    $query = "SELECT
                DOA_GIFT_CERTIFICATE_MASTER.*,
                DOA_ACCOUNT_MASTER.BUSINESS_NAME,
                DOA_ACCOUNT_MASTER.BUSINESS_LOGO,
                DOA_LOCATION.LOCATION_NAME,
                DOA_GIFT_CERTIFICATE_SETUP.GIFT_CERTIFICATE_NAME,
                DOA_GIFT_CERTIFICATE_SETUP.GIFT_CERTIFICATE_CODE
            FROM DOA_GIFT_CERTIFICATE_MASTER

            INNER JOIN DOA_GIFT_CERTIFICATE_SETUP
                ON DOA_GIFT_CERTIFICATE_MASTER.PK_GIFT_CERTIFICATE_SETUP =
                DOA_GIFT_CERTIFICATE_SETUP.PK_GIFT_CERTIFICATE_SETUP

            INNER JOIN {$master_database}.DOA_ACCOUNT_MASTER AS DOA_ACCOUNT_MASTER
                ON DOA_ACCOUNT_MASTER.PK_ACCOUNT_MASTER =
                DOA_GIFT_CERTIFICATE_MASTER.PK_ACCOUNT_MASTER

            LEFT JOIN {$master_database}.DOA_LOCATION AS DOA_LOCATION
                ON DOA_GIFT_CERTIFICATE_MASTER.PK_LOCATION =
                DOA_LOCATION.PK_LOCATION

            WHERE DOA_GIFT_CERTIFICATE_MASTER.PK_GIFT_CERTIFICATE_MASTER = " . intval($_GET['id']);

    $res = $db_account->Execute($query);
    $NAME = $res->fields['RECIPIENT'] . ' ' . $res->fields['LAST_NAME'];
    $BUSINESS_NAME = $res->fields['BUSINESS_NAME'];
    $BUSINESS_LOGO = $res->fields['BUSINESS_LOGO'];
    $PK_GIFT_CERTIFICATE_SETUP = $res->fields['PK_GIFT_CERTIFICATE_SETUP'];
    $DATE_OF_PURCHASE = $res->fields['DATE_OF_PURCHASE'];
    $GIFT_NOTE = $res->fields['GIFT_NOTE'];
    $AMOUNT = $res->fields['AMOUNT'];
    $ACTIVE = $res->fields['ACTIVE'];
    $GIFT_CERTIFICATE_NAME = $res->fields['GIFT_CERTIFICATE_NAME'];
    $GIFT_CERTIFICATE_CODE = $res->fields['UNIQUE_ID'];
    $LOCATION_NAME = $res->fields['LOCATION_NAME'];
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
<style>
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

    .h4,
    .h5,
    .h6,
    h4,
    h5,
    h6 {
        margin-top: 0px !important;
        margin-bottom: 0px !important;
    }
</style>

<body style="margin: auto; font-family: calibri,serif; width:50%;">

    <table style="width:100%;">
        <tr>
            <td style="width=100%">
                <img width="100%" src="<?= $http_path ?>assets/images/b1.jpg" alt="">
            </td>
        </tr>
    </table>


    <div style="margin:40px 60px; min-height:560px;">
        <table style="width:100%; border-collapse:collapse;">
            <tr style="vertical-align:middle;">
                <td style="width:80%; text-align:right; padding-right:20px;">
                    <h2 style="margin:0; font-size:30px; text-transform:uppercase;">
                        <?php echo $BUSINESS_NAME ?>
                    </h2>

                    <h3 style="margin:8px 0 0; font-weight:500;">
                        <?php echo $LOCATION_NAME ?>
                    </h3>
                </td>

                <td style="width:20%; text-align:right;">
                    <img src="<?php echo str_replace('../', $http_path, $BUSINESS_LOGO) ?>"
                        style="max-height:90px; max-width:140px;">
                </td>
            </tr>
        </table>
        <table style="width:100%; margin-top:45px;">
            <tr>
                <td style="text-align:left;">
                    <h1 style="margin:0; font-size:36px; text-transform:uppercase;">
                        <?php echo $GIFT_CERTIFICATE_NAME ?>
                    </h1>

                    <h3 style="margin-top:10px; color:#666;">
                        Certificate No :
                        <?php echo $GIFT_CERTIFICATE_CODE ?>
                    </h3>
                </td>
            </tr>
        </table>
        <table style="width:100%; margin:40px 0;">
            <tr>
                <td style="width:180px;">
                    <span style="font-size:22px; font-weight:600;">
                        Gift Amount
                    </span>
                </td>

                <td>
                    <span style="font-size:38px; font-weight:700;">
                        <?php echo $AMOUNT ?>
                    </span>
                </td>
            </tr>
        </table>
        <table style="width:100%; border-collapse:collapse; margin-top:35px;">
            <tr style="vertical-align:top;">

                <td style="width:30%; padding-right:20px;">
                    <div style="font-size:18px; font-weight:600; margin-bottom:10px;">
                        Recipient
                    </div>

                    <div style="font-size:22px;">
                        <?php echo $NAME ?>
                    </div>
                </td>

                <td style="width:40%; padding:0 20px; text-align:center;">
                    <div style="font-size:18px; font-weight:600; margin-bottom:10px;">
                        Notes
                    </div>

                    <div style="font-size:22px;">
                        <?php echo $GIFT_NOTE ?>
                    </div>
                </td>

                <td style="width:30%; text-align:right;">

                    <div style="font-size:18px; font-weight:600; margin-bottom:10px;">
                        Purchase Date
                    </div>

                    <div style="font-size:20px;">
                        <?php echo $DATE_OF_PURCHASE ?>
                    </div>

                </td>

            </tr>
        </table>
        <!-- <table style="width:100%;margin: 20px 0;">
            <td>
                ** See Terms on next page
            </td>
        </table> -->
    </div>
    <div style="height:50px;"></div>
    <table style="width:100%;">
        <tr>
            <td style="width=100%">
                <img width="100%" src="<?= $http_path ?>assets/images/b2.jpg" alt="">
            </td>
        </tr>
    </table>
</body>

</html>