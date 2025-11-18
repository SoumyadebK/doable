<?php
require_once('../global/config.php');
require_once("../global/stripe-php/init.php");

global $db;
global $db_account;
global $upload_path;
global $AMI_ENABLE;

use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

if (empty($_GET['id']))
    $title = "Add Location";
else
    $title = "Edit Location";

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])) {
    header("location:../login.php");
    exit;
}

$PK_ACCOUNT_MASTER = $_SESSION['PK_ACCOUNT_MASTER'];
$PK_USER = $_SESSION['PK_USER'];

$help_title = '';
$help_description = '';
$help = $db->Execute("SELECT * FROM DOA_HELP_PAGE WHERE PAGE_LINK = 'location'");
if ($help->RecordCount() > 0) {
    $help_title = $help->fields['TITLE'];
    $help_description = $help->fields['DESCRIPTION'];
}

$SA_PAYMENT_GATEWAY_TYPE = '';
$SA_GATEWAY_MODE = '';
$SA_SECRET_KEY = '';
$SA_PUBLISHABLE_KEY = '';
$SA_ACCESS_TOKEN = '';
$SA_SQUARE_APP_ID = '';
$SA_SQUARE_LOCATION_ID = '';

$payment_gateway_setting = $db->Execute("SELECT * FROM `DOA_PAYMENT_GATEWAY_SETTINGS`");
if ($payment_gateway_setting->RecordCount() > 0) {
    $SA_PAYMENT_GATEWAY_TYPE = $payment_gateway_setting->fields['PAYMENT_GATEWAY_TYPE'];
    $SA_GATEWAY_MODE = $payment_gateway_setting->fields['GATEWAY_MODE'];
    $SA_SECRET_KEY = $payment_gateway_setting->fields['SECRET_KEY'];
    $SA_PUBLISHABLE_KEY = $payment_gateway_setting->fields['PUBLISHABLE_KEY'];
    $SA_ACCESS_TOKEN = $payment_gateway_setting->fields['ACCESS_TOKEN'];
    $SA_SQUARE_APP_ID = $payment_gateway_setting->fields['APP_ID'];
    $SA_SQUARE_LOCATION_ID = $payment_gateway_setting->fields['LOCATION_ID'];
}

if (empty($_GET['id'])) {
    $PK_LOCATION = 0;
    $PK_CORPORATION = '';
    $PK_ACCOUNT_TYPE = '';
    $FRANCHISE = '';
    $LOCATION_NAME = '';
    $LOCATION_CODE = '';
    $ADDRESS = ''; //$res->fields['ADDRESS'];
    $ADDRESS_1 = ''; //$res->fields['ADDRESS_1'];
    $PK_COUNTRY = ''; //$res->fields['PK_COUNTRY'];
    $PK_STATES = ''; //$res->fields['PK_STATES'];
    $CITY = ''; //$res->fields['CITY'];
    $ZIP_CODE = ''; //$res->fields['ZIP'];
    $PHONE = ''; //$res->fields['PHONE'];
    $EMAIL = ''; //$res->fields['EMAIL'];
    $IMAGE_PATH = '';
    $PK_TIMEZONE = '';
    $TIME_SLOT_INTERVAL     = '';
    $SERVICE_PROVIDER_TITLE = '';
    $OPERATION_TAB_TITLE    = '';
    $ENROLLMENT_ID_CHAR     = '';
    $ENROLLMENT_ID_NUM      = '';
    $MISCELLANEOUS_ID_CHAR  = '';
    $MISCELLANEOUS_ID_NUM   = '';
    $APPOINTMENT_REMINDER   = '';
    $HOUR                   = '';
    $ROYALTY_PERCENTAGE = '';
    $ACTIVE = '';

    $PAYMENT_GATEWAY_TYPE = '';
    $GATEWAY_MODE = '';
    $SECRET_KEY = '';
    $PUBLISHABLE_KEY = '';
    $ACCESS_TOKEN = '';
    $SQUARE_APP_ID = '';
    $SQUARE_LOCATION_ID = '';
    $LOGIN_ID = '';
    $TRANSACTION_KEY = '';
    $AUTHORIZE_CLIENT_KEY = '';
    $MERCHANT_ID = '';
    $API_KEY = '';
    $PUBLIC_API_KEY = '';


    $AM_USER_NAME = '';
    $AM_PASSWORD = '';
    $AM_REFRESH_TOKEN = '';
    $SALES_TAX = '';
    $RECEIPT_CHARACTER = '';
    $TEXTING_FEATURE_ENABLED = '';
    $TWILIO_ACCOUNT_TYPE = '';
    $SID = '';
    $TOKEN = '';
    $TWILIO_PHONE_NO = '';
    $FOCUSBIZ_API_KEY = '';
    $USERNAME_PREFIX = '';

    $SMTP_HOST = '';
    $SMTP_PORT = '';
    $SMTP_USERNAME = '';
    $SMTP_PASSWORD = '';

    $START_DATE = '';
    $PAYMENT_FROM = '';
} else {
    $res = $db->Execute("SELECT * FROM `DOA_LOCATION` WHERE `PK_LOCATION` = '$_GET[id]'");
    if ($res->RecordCount() == 0) {
        header("location:all_locations.php");
        exit;
    }

    $PK_LOCATION = $_GET['id'];
    $PK_CORPORATION = $res->fields['PK_CORPORATION'];
    $PK_ACCOUNT_TYPE = $res->fields['PK_ACCOUNT_TYPE'];
    $FRANCHISE = $res->fields['FRANCHISE'];
    $LOCATION_NAME = $res->fields['LOCATION_NAME'];
    $LOCATION_CODE = $res->fields['LOCATION_CODE'];
    $ADDRESS = $res->fields['ADDRESS'];
    $ADDRESS_1 = $res->fields['ADDRESS_1'];
    $PK_COUNTRY = $res->fields['PK_COUNTRY'];
    $PK_STATES = $res->fields['PK_STATES'];
    $CITY = $res->fields['CITY'];
    $ZIP_CODE = $res->fields['ZIP_CODE'];
    $PHONE = $res->fields['PHONE'];
    $EMAIL = $res->fields['EMAIL'];
    $IMAGE_PATH = $res->fields['IMAGE_PATH'];
    $PK_TIMEZONE = $res->fields['PK_TIMEZONE'];
    $TIME_SLOT_INTERVAL     = $res->fields['TIME_SLOT_INTERVAL'];
    $SERVICE_PROVIDER_TITLE = $res->fields['SERVICE_PROVIDER_TITLE'];
    $OPERATION_TAB_TITLE    = $res->fields['OPERATION_TAB_TITLE'];
    $ENROLLMENT_ID_CHAR     = $res->fields['ENROLLMENT_ID_CHAR'];
    $ENROLLMENT_ID_NUM      = $res->fields['ENROLLMENT_ID_NUM'];
    $MISCELLANEOUS_ID_CHAR  = $res->fields['MISCELLANEOUS_ID_CHAR'];
    $MISCELLANEOUS_ID_NUM   = $res->fields['MISCELLANEOUS_ID_NUM'];
    $APPOINTMENT_REMINDER   = $res->fields['APPOINTMENT_REMINDER'];
    $HOUR                   = $res->fields['HOUR'];
    $ROYALTY_PERCENTAGE = $res->fields['ROYALTY_PERCENTAGE'];
    $ACTIVE = $res->fields['ACTIVE'];

    $PAYMENT_GATEWAY_TYPE   = $res->fields['PAYMENT_GATEWAY_TYPE'];
    $GATEWAY_MODE           = $res->fields['GATEWAY_MODE'];
    $SECRET_KEY             = $res->fields['SECRET_KEY'];
    $PUBLISHABLE_KEY        = $res->fields['PUBLISHABLE_KEY'];
    $ACCESS_TOKEN           = $res->fields['ACCESS_TOKEN'];
    $SQUARE_APP_ID          = $res->fields['APP_ID'];
    $SQUARE_LOCATION_ID     = $res->fields['LOCATION_ID'];
    $LOGIN_ID               = $res->fields['LOGIN_ID'];
    $TRANSACTION_KEY        = $res->fields['TRANSACTION_KEY'];
    $AUTHORIZE_CLIENT_KEY   = $res->fields['AUTHORIZE_CLIENT_KEY'];
    $MERCHANT_ID            = $res->fields['MERCHANT_ID'];
    $API_KEY                = $res->fields['API_KEY'];
    $PUBLIC_API_KEY         = $res->fields['PUBLIC_API_KEY'];

    $AM_USER_NAME           = $res->fields['AM_USER_NAME'];
    $AM_PASSWORD            = $res->fields['AM_PASSWORD'];
    $AM_REFRESH_TOKEN       = $res->fields['AM_REFRESH_TOKEN'];
    $SALES_TAX              = $res->fields['SALES_TAX'];
    $RECEIPT_CHARACTER      = $res->fields['RECEIPT_CHARACTER'];
    $TEXTING_FEATURE_ENABLED = $res->fields['TEXTING_FEATURE_ENABLED'];
    $TWILIO_ACCOUNT_TYPE    = $res->fields['TWILIO_ACCOUNT_TYPE'];
    $SID                    = $res->fields['SID'];
    $TOKEN                  = $res->fields['TOKEN'];
    $TWILIO_PHONE_NO        = $res->fields['TWILIO_PHONE_NO'];

    $FOCUSBIZ_API_KEY = $res->fields['FOCUSBIZ_API_KEY'];
    $USERNAME_PREFIX = $res->fields['USERNAME_PREFIX'];

    $SMTP_HOST = $res->fields['SMTP_HOST'];
    $SMTP_PORT = $res->fields['SMTP_PORT'];
    $SMTP_USERNAME = $res->fields['SMTP_USERNAME'];
    $SMTP_PASSWORD = $res->fields['SMTP_PASSWORD'];

    $START_DATE = $res->fields['CREATED_ON'];
    $PAYMENT_FROM = $res->fields['PAYMENT_FROM'];
}

$user_data = $db->Execute("SELECT DOA_USERS.ABLE_TO_EDIT_PAYMENT_GATEWAY FROM DOA_USERS WHERE PK_USER = '$_SESSION[PK_USER]'");
$ABLE_TO_EDIT_PAYMENT_GATEWAY = $user_data->fields['ABLE_TO_EDIT_PAYMENT_GATEWAY'];

$account_data = $db->Execute("SELECT * FROM `DOA_ACCOUNT_MASTER` WHERE `PK_ACCOUNT_MASTER`  = " . $PK_ACCOUNT_MASTER);

$RENEWAL_INTERVAL = $account_data->fields['RENEWAL_INTERVAL'];

$AM_AMOUNT = $account_data->fields['AM_AMOUNT'];
$NOT_AM_AMOUNT = $account_data->fields['NOT_AM_AMOUNT'];

if (($AM_AMOUNT == '' || $AM_AMOUNT == 0.00) && ($NOT_AM_AMOUNT == '' || $NOT_AM_AMOUNT == 0.00)) {
    $res = $db->Execute("SELECT * FROM `DOA_OTHER_SETTING`");
    if ($res->RecordCount() > 0) {
        $AM_AMOUNT       = $res->fields['AM_AMOUNT'];
        $NOT_AM_AMOUNT   = $res->fields['NOT_AM_AMOUNT'];
    }
}
$AMOUNT = ($FRANCHISE == 1) ? $AM_AMOUNT : $NOT_AM_AMOUNT;

if (!empty($_POST)) {
    if ($_POST['FUNCTION_NAME'] == 'saveLocationData') {
        unset($_POST['FUNCTION_NAME']);
        $LOCATION_DATA = $_POST;
        $LOCATION_DATA['PK_ACCOUNT_MASTER'] = $_SESSION['PK_ACCOUNT_MASTER'];

        if ($_FILES['IMAGE_PATH']['name'] != '') {
            if (!file_exists('../' . $upload_path . '/location_image/')) {
                mkdir('../' . $upload_path . '/location_image/', 0777, true);
                chmod('../' . $upload_path . '/location_image/', 0777);
            }

            $extn = explode(".", $_FILES['IMAGE_PATH']['name']);
            $iindex = count($extn) - 1;
            $rand_string = time() . "-" . rand(100000, 999999);
            $file11 = 'location_image_' . $_SESSION['PK_USER'] . $rand_string . "." . $extn[$iindex];
            $extension = strtolower($extn[$iindex]);

            if ($extension == "gif" || $extension == "jpeg" || $extension == "pjpeg" || $extension == "png" || $extension == "jpg") {
                $image_path = '../' . $upload_path . '/location_image/' . $file11;
                move_uploaded_file($_FILES['IMAGE_PATH']['tmp_name'], $image_path);
                $LOCATION_DATA['IMAGE_PATH'] = $image_path;
            }
        }

        $LOCATION_CODE = trim($LOCATION_DATA['LOCATION_CODE']);
        if (!file_exists('../' . $upload_path . '/enrollment_pdf/' . $LOCATION_CODE . '/')) {
            mkdir('../' . $upload_path . '/enrollment_pdf/' . $LOCATION_CODE . '/', 0777, true);
            chmod('../' . $upload_path . '/enrollment_pdf/' . $LOCATION_CODE . '/', 0777);
        }

        if (!empty($LOCATION_DATA['FOCUSBIZ_API_KEY'])) {
            if ($LOCATION_DATA['FOCUSBIZ_API_KEY'] != $LOCATION_DATA['FOCUSBIZ_API_KEY_OLD']) {
                $location = array();
                $location['FIRST_NAME'] = $LOCATION_DATA['LOCATION_NAME'];
                $location['LAST_NAME'] = '(' . $LOCATION_DATA['LOCATION_CODE'] . ')';
                $location['EMAIL_ID'] = $LOCATION_DATA['EMAIL'];
                $location['ACTIVE'] = 1;
                $location['USER_ID'] = $LOCATION_DATA['LOCATION_CODE'];

                $location['PASSWORD'] = 'Password@123'; // Default password, can be changed later

                $URL = "https://focusbiz.com/API/V1/user";

                $json = json_encode($location);
                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_SSL_VERIFYHOST => '0',
                    CURLOPT_SSL_VERIFYPEER => '0',
                    CURLOPT_URL => $URL,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_POST => 1,
                    CURLOPT_POSTFIELDS => $json,
                    CURLOPT_HTTPHEADER => array(
                        "APIKEY: " . $LOCATION_DATA['FOCUSBIZ_API_KEY']
                    ),
                ));

                $return_data = curl_exec($curl);
                $err = curl_error($curl);

                curl_close($curl);

                if ($err) {
                    echo "cURL Error #:" . $err;
                    exit;
                } else {
                    $response = json_decode($return_data);
                    $LOCATION_DATA['FOCUSBIZ_ACCESS_TOKEN'] = $_SESSION['FOCUSBIZ_ACCESS_TOKEN'] = $response->ACCESS_TOKEN;
                }
            }
        } else {
            $LOCATION_DATA['FOCUSBIZ_ACCESS_TOKEN'] = NULL;
        }
        unset($LOCATION_DATA['FOCUSBIZ_API_KEY_OLD']);

        if (empty($_GET['id'])) {
            $LOCATION_DATA['ACTIVE'] = 1;
            $LOCATION_DATA['CREATED_BY'] = $_SESSION['PK_USER'];
            $LOCATION_DATA['CREATED_ON'] = date("Y-m-d H:i");
            db_perform('DOA_LOCATION', $LOCATION_DATA, 'insert');
            $PK_LOCATION = $db->insert_ID();
            $LOCATION_ARRAY = explode(',', $_SESSION['DEFAULT_LOCATION_ID']);
            $LOCATION_ARRAY[] = $PK_LOCATION;
            $_SESSION['DEFAULT_LOCATION_ID'] = implode(',', $LOCATION_ARRAY);
        } else {
            $LOCATION_DATA['ACTIVE'] = $_POST['ACTIVE'];
            $LOCATION_DATA['EDITED_BY'] = $_SESSION['PK_USER'];
            $LOCATION_DATA['EDITED_ON'] = date("Y-m-d H:i");
            db_perform('DOA_LOCATION', $LOCATION_DATA, 'update', " PK_LOCATION =  '$_GET[id]'");
            $PK_LOCATION = $_GET['id'];
        }
    }

    if ($_POST['FUNCTION_NAME'] == 'saveOperationalHours') {
        $ALL_DAYS = isset($_POST['ALL_DAYS']) ? 1 : 0;
        $operational_hours = $db_account->Execute("SELECT * FROM DOA_OPERATIONAL_HOUR WHERE `PK_LOCATION` = '" . (int)$_GET['id'] . "'");

        if ($operational_hours->RecordCount() > 0) {
            for ($i = 0; $i < count($_POST['OPEN_TIME']); $i++) {
                $PK_LOCATION = (int)$_GET['id'];
                $DAY_NUMBER = (int)($i + 1);
                $OPERATIONAL_HOUR_DATA['PK_LOCATION'] = $PK_LOCATION;
                $OPERATIONAL_HOUR_DATA['DAY_NUMBER'] = $DAY_NUMBER;

                // Special handling for 12:00 AM
                $open_time = ($ALL_DAYS == 0) ? $_POST['OPEN_TIME'][$i] : $_POST['OPEN_TIME'][0];
                if (strtoupper($open_time) == '12:00 AM' || strtoupper($open_time) == '12:00:00 AM') {
                    $OPERATIONAL_HOUR_DATA['OPEN_TIME'] = '24:00:00';
                } else {
                    $OPERATIONAL_HOUR_DATA['OPEN_TIME'] = !empty($open_time) ? date('H:i:s', strtotime($open_time)) : '00:00:00';
                }

                $close_time = ($ALL_DAYS == 0) ? $_POST['CLOSE_TIME'][$i] : $_POST['CLOSE_TIME'][0];
                if (strtoupper($close_time) == '12:00 AM' || strtoupper($close_time) == '12:00:00 AM') {
                    $OPERATIONAL_HOUR_DATA['CLOSE_TIME'] = '24:00:00';
                } else {
                    $OPERATIONAL_HOUR_DATA['CLOSE_TIME'] = !empty($close_time) ? date('H:i:s', strtotime($close_time)) : '00:00:00';
                }

                $OPERATIONAL_HOUR_DATA['CLOSED'] = isset($_POST['CLOSED_' . $i]) ? 1 : 0;

                db_perform_account('DOA_OPERATIONAL_HOUR', $OPERATIONAL_HOUR_DATA, 'update', " PK_LOCATION = $PK_LOCATION AND DAY_NUMBER = $DAY_NUMBER");
            }
        } else {
            if (count($_POST['OPEN_TIME']) > 0) {
                for ($i = 0; $i < count($_POST['OPEN_TIME']); $i++) {
                    $OPERATIONAL_HOUR_DATA['PK_LOCATION'] = (int)$_GET['id'];
                    $OPERATIONAL_HOUR_DATA['DAY_NUMBER'] = $i + 1;

                    // Special handling for 12:00 AM
                    $open_time = ($ALL_DAYS == 0) ? $_POST['OPEN_TIME'][$i] : $_POST['OPEN_TIME'][0];
                    if (strtoupper($open_time) == '12:00 AM' || strtoupper($open_time) == '12:00:00 AM') {
                        $OPERATIONAL_HOUR_DATA['OPEN_TIME'] = '24:00:00';
                    } else {
                        $OPERATIONAL_HOUR_DATA['OPEN_TIME'] = !empty($open_time) ? date('H:i:s', strtotime($open_time)) : '00:00:00';
                    }

                    $close_time = ($ALL_DAYS == 0) ? $_POST['CLOSE_TIME'][$i] : $_POST['CLOSE_TIME'][0];
                    if (strtoupper($close_time) == '12:00 AM' || strtoupper($close_time) == '12:00:00 AM') {
                        $OPERATIONAL_HOUR_DATA['CLOSE_TIME'] = '24:00:00';
                    } else {
                        $OPERATIONAL_HOUR_DATA['CLOSE_TIME'] = !empty($close_time) ? date('H:i:s', strtotime($close_time)) : '00:00:00';
                    }

                    $OPERATIONAL_HOUR_DATA['CLOSED'] = isset($_POST['CLOSED_' . $i]) ? 1 : 0;

                    db_perform_account('DOA_OPERATIONAL_HOUR', $OPERATIONAL_HOUR_DATA, 'insert');
                }
            }
        }
    }

    if ($_POST['FUNCTION_NAME'] == 'saveHolidayData') {
        unset($_POST['FUNCTION_NAME']);
        $PK_LOCATION = (int)$_POST['PK_LOCATION'];
        $db->Execute("DELETE FROM `DOA_LOCATION_HOLIDAY_LIST` WHERE `PK_LOCATION` = " . $PK_LOCATION);
        for ($i = 0; $i < count($_POST['HOLIDAY_DATE']); $i++) {
            $HOLIDAY_LIST_DATA['PK_LOCATION'] = $PK_LOCATION;
            $HOLIDAY_LIST_DATA['HOLIDAY_DATE'] = date('Y-m-d', strtotime($_POST['HOLIDAY_DATE'][$i]));
            $HOLIDAY_LIST_DATA['HOLIDAY_NAME'] = $_POST['HOLIDAY_NAME'][$i];
            db_perform('DOA_LOCATION_HOLIDAY_LIST', $HOLIDAY_LIST_DATA, 'insert');
        }
    }

    if ($_POST['FUNCTION_NAME'] == 'savePermissionData') {
        $PK_LOCATION = (int)$_POST['PK_LOCATION'];

        // First delete all existing permissions for this location
        $db->Execute("DELETE FROM DOA_CUSTOMER_TAB WHERE PK_LOCATION = " . $PK_LOCATION);

        // Process each tab permission
        if (!empty($_POST['TAB_NAME']) && is_array($_POST['TAB_NAME'])) {
            foreach ($_POST['TAB_NAME'] as $i => $tab_name) {
                $permission = isset($_POST['PERMISSION'][$i]) ? 1 : 0;

                $PERMISSION_DATA = [
                    'PK_LOCATION' => $PK_LOCATION,
                    'TAB_NAME' => $tab_name,
                    'PERMISSION' => $permission
                ];

                // Insert new record
                db_perform('DOA_CUSTOMER_TAB', $PERMISSION_DATA, 'insert');
            }
        }
    }

    header("location:all_locations.php");
}

?>

<!DOCTYPE html>
<html lang="en">
<?php require_once('../includes/header.php'); ?>
<style>
    #advice-required-entry-ACCEPT_HANDLING {
        width: 150px;
        top: 20px;
        position: absolute;
    }

    .StripeElement {
        display: block;
        width: 100%;
        height: 34px;
        padding: 6px 12px;
        font-size: 14px;
        line-height: 1.42857143;
        color: #555;
        background-color: #fff;
        background-image: none;
        border: 1px solid #ccc;
        border-radius: 4px;
    }

    .StripeElement--focus {
        box-shadow: 0 1px 3px 0 #cfd7df;
    }

    .StripeElement--invalid {
        border-color: #fa755a;
    }

    .StripeElement--webkit-autofill {
        background-color: #fefde5 !important;
    }
</style>
<style>
    /* Compact toggle switch for font-size 14px */
    .switch {
        position: relative;
        display: inline-block;
        width: 42px;
        /* Reduced from 60px */
        height: 22px;
        /* Reduced from 30px */
        vertical-align: middle;
        /* Better alignment with text */
        margin: 0 5px;
        /* Add some spacing */
    }

    .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }


    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        transition: .4s;
        border-radius: 22px;
        /* Adjusted to match new height */
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 18px;
        /* Reduced from 26px */
        width: 18px;
        /* Reduced from 26px */
        left: 2px;
        /* Adjusted positioning */
        bottom: 2px;
        /* Adjusted positioning */
        background-color: white;
        transition: .4s;
        border-radius: 50%;
    }

    input:checked+.slider {
        background-color: #39B54A;
    }

    input:checked+.slider:before {
        transform: translateX(20px);
        /* Adjusted for new width */
    }

    /* Focus state for accessibility */
    input:focus+.slider {
        box-shadow: 0 0 0 2px rgba(57, 181, 74, 0.3);
    }

    /* Optional: Add transition for smooth toggle */
    .switch * {
        transition: all 0.3s ease;
    }
</style>

<body class="skin-default-dark fixed-layout">
    <?php require_once('../includes/loader.php'); ?>
    <div id="main-wrapper">
        <?php require_once('../includes/top_menu.php'); ?>
        <div class="page-wrapper">
            <?php require_once('../includes/top_menu_bar.php') ?>
            <div class="container-fluid body_content">
                <div class="row page-titles">
                    <div class="col-md-5 align-self-center">
                        <h4 class="text-themecolor"><?= $title ?></h4>
                    </div>
                    <div class="col-md-7 align-self-center text-end">
                        <div class="d-flex justify-content-end align-items-center">
                            <ol class="breadcrumb justify-content-end">
                                <li class="breadcrumb-item"><a href="setup.php">Setup</a></li>
                                <li class="breadcrumb-item"><a href="all_locations.php">All Locations</a></li>
                                <li class="breadcrumb-item active"><?= $title ?></li>
                            </ol>

                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-8">
                        <div class="card">
                            <div class="card-title" style="margin-top: 15px; margin-left: 15px;">
                                <?php
                                if (!empty($_GET['id'])) {
                                    echo $LOCATION_NAME;
                                }
                                ?>
                            </div>
                            <div class="card-body">
                                <!-- Nav tabs -->
                                <ul class="nav nav-tabs" role="tablist">
                                    <li> <a class="nav-link active" data-bs-toggle="tab" id="location_link" href="#location_div" role="tab"><span class="hidden-sm-up"><i class="ti-location-pin"></i></span> <span class="hidden-xs-down">Location</span></a> </li>
                                    <?php if (!empty($_GET['id'])) { ?>
                                        <li> <a class="nav-link" data-bs-toggle="tab" id="operational_hours_link" href="#operational_hours" role="tab"><span class="hidden-sm-up"><i class="ti-time"></i></span> <span class="hidden-xs-down">Operational Hours</span></a> </li>
                                        <li> <a class="nav-link" data-bs-toggle="tab" id="holiday_list_link" href="#holiday_list" role="tab"><span class="hidden-sm-up"><i class="ti-calendar"></i></span> <span class="hidden-xs-down">Holiday List</span></a> </li>
                                        <li> <a class="nav-link" data-bs-toggle="tab" id="billing_link" href="#billing" role="tab" onclick="getSavedCreditCardList();"><span class="hidden-sm-up"><i class="ti-receipt"></i></span> <span class="hidden-xs-down">Billing</span></a> </li>
                                        <li> <a class="nav-link" data-bs-toggle="tab" id="customer_tab_permissions_link" href="#customer_tab_permissions" role="tab"><span class="hidden-sm-up"><i class="ti-check-box"></i></span> <span class="hidden-xs-down">Customer Tab Permissions</span></a> </li>
                                        <!-- <li> <a class="nav-link" data-bs-toggle="tab" id="receipts_link" href="#receipts" role="tab"><span class="hidden-sm-up"><i class="ti-receipt"></i></span> <span class="hidden-xs-down">Receipts</span></a> </li> -->
                                    <?php } ?>
                                </ul>

                                <!-- Tab panes -->
                                <div class="tab-content tabcontent-border">
                                    <div class="tab-pane active" id="location_div" role="tabpanel">
                                        <form class="form-material form-horizontal" id="location_form" action="" method="post" enctype="multipart/form-data">
                                            <input type="hidden" name="FUNCTION_NAME" value="saveLocationData">
                                            <div class="p-20">
                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12" for="example-text">Corporation<span class="text-danger">*</span>
                                                            </label>
                                                            <div class="col-md-12">
                                                                <div class="col-sm-12">
                                                                    <select class="form-control" name="PK_CORPORATION" id="PK_CORPORATION" required>
                                                                        <option value="">Select Corporation</option>
                                                                        <?php
                                                                        $row = $db->Execute("SELECT PK_CORPORATION, CORPORATION_NAME FROM DOA_CORPORATION WHERE ACTIVE = 1 AND PK_ACCOUNT_MASTER = " . $_SESSION['PK_ACCOUNT_MASTER'] . " ORDER BY PK_CORPORATION");
                                                                        while (!$row->EOF) { ?>
                                                                            <option value="<?php echo $row->fields['PK_CORPORATION']; ?>" <?= ($row->fields['PK_CORPORATION'] == $PK_CORPORATION) ? "selected" : "" ?>><?= $row->fields['CORPORATION_NAME'] ?></option>
                                                                        <?php $row->MoveNext();
                                                                        } ?>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-3">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Account Type<span class="text-danger">*</span>
                                                            </label>
                                                            <div class="col-md-12">
                                                                <?php
                                                                $row = $db->Execute("SELECT PK_ACCOUNT_TYPE,ACCOUNT_TYPE FROM DOA_ACCOUNT_TYPE WHERE ACTIVE='1' ORDER BY PK_ACCOUNT_TYPE");
                                                                while (!$row->EOF) { ?>
                                                                    <input type="radio" name="PK_ACCOUNT_TYPE" id="<?= $row->fields['PK_ACCOUNT_TYPE']; ?>" value="<?= $row->fields['PK_ACCOUNT_TYPE']; ?>" <?php if ($row->fields['PK_ACCOUNT_TYPE'] == $PK_ACCOUNT_TYPE) echo 'checked'; ?> required>
                                                                    <label for="<?= $row->fields['PK_ACCOUNT_TYPE']; ?>"><?= $row->fields['ACCOUNT_TYPE'] ?></label>
                                                                <?php $row->MoveNext();
                                                                } ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <?php if ($AMI_ENABLE == 1) { ?>
                                                        <div class="col-3">
                                                            <div class="form-group">
                                                                <label class="col-md-12">Arthur Murray Franchise ?</label>
                                                                <div class="col-md-12">
                                                                    <label><input type="radio" name="FRANCHISE" id="FRANCHISE" value="1" <?php if ($FRANCHISE == 1) echo 'checked="checked"'; ?> onclick="showArthurMurraySetup(this);" />&nbsp;Yes</label>&nbsp;&nbsp;
                                                                    <label><input type="radio" name="FRANCHISE" id="FRANCHISE" value="0" <?php if ($FRANCHISE == 0) echo 'checked="checked"'; ?> onclick="showArthurMurraySetup(this);" />&nbsp;No</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php } ?>
                                                </div>
                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12" for="example-text">Location<span class="text-danger">*</span>
                                                            </label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="LOCATION_NAME" name="LOCATION_NAME" class="form-control" placeholder="Enter Location Name" required value="<?php echo $LOCATION_NAME ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12" for="example-text">Location Code<span class="text-danger">*</span>
                                                            </label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="LOCATION_CODE" name="LOCATION_CODE" class="form-control" placeholder="Enter Location Code" required value="<?php echo $LOCATION_CODE ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12" for="example-text">Address</label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="ADDRESS" name="ADDRESS" class="form-control" placeholder="Enter Address" value="<?php echo $ADDRESS ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12" for="example-text">Apt/Ste</label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="ADDRESS_1" name="ADDRESS_1" class="form-control" placeholder="Enter Apartment OR Street" value="<?php echo $ADDRESS_1 ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12" for="example-text">Country<span class="text-danger">*</span>
                                                            </label>
                                                            <div class="col-md-12">
                                                                <div class="col-sm-12">
                                                                    <select class="form-control" name="PK_COUNTRY" id="PK_COUNTRY" onChange="fetch_state(this.value)" required>
                                                                        <option value="">Select Country</option>
                                                                        <?php
                                                                        $row = $db->Execute("SELECT PK_COUNTRY,COUNTRY_NAME FROM DOA_COUNTRY WHERE ACTIVE = 1 ORDER BY PK_COUNTRY");
                                                                        while (!$row->EOF) { ?>
                                                                            <option value="<?php echo $row->fields['PK_COUNTRY']; ?>" <?= ($row->fields['PK_COUNTRY'] == $PK_COUNTRY) ? "selected" : "" ?>><?= $row->fields['COUNTRY_NAME'] ?></option>
                                                                        <?php $row->MoveNext();
                                                                        } ?>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12" for="example-text">State<span class="text-danger">*</span>
                                                            </label>
                                                            <div class="col-md-12">
                                                                <div class="col-sm-12">
                                                                    <div id="State_div"></div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12" for="example-text">City</span>
                                                            </label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="CITY" name="CITY" class="form-control" placeholder="Enter City" value="<?php echo $CITY ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12" for="example-text">Postal / Zip Code</span>
                                                            </label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="ZIP_CODE" name="ZIP_CODE" class="form-control" placeholder="Enter Postal / Zip Code" value="<?php echo $ZIP_CODE ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12" for="example-text">Phone</label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="PHONE" name="PHONE" class="form-control" placeholder="Enter Phone No." value="<?php echo $PHONE ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12" for="example-text">Email</label>
                                                            <div class="col-md-12">
                                                                <input type="email" id="EMAIL" name="EMAIL" class="form-control" placeholder="enter Email Address" value="<?php echo $EMAIL ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12" for="example-text">Location Image</label>
                                                            <div class="col-md-12">
                                                                <input type="file" name="IMAGE_PATH" id="IMAGE_PATH" class="form-control">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12" for="example-text">Timezone<span class="text-danger">*</span></label>
                                                            <div class="col-md-12">
                                                                <select name="PK_TIMEZONE" id="PK_TIMEZONE" class="form-control required-entry" required>
                                                                    <option value="">Select</option>
                                                                    <? $res_type = $db->Execute("SELECT * FROM DOA_TIMEZONE WHERE ACTIVE = 1 ORDER BY NAME ASC");
                                                                    while (!$res_type->EOF) { ?>
                                                                        <option value="<?= $res_type->fields['PK_TIMEZONE'] ?>" <? if ($res_type->fields['PK_TIMEZONE'] == $PK_TIMEZONE) echo 'selected="selected"'; ?>><?= $res_type->fields['NAME'] ?></option>
                                                                    <? $res_type->MoveNext();
                                                                    } ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <?php if ($IMAGE_PATH != '') { ?><div style="width: 120px;height: 120px;margin-top: 25px;"><a class="fancybox" href="<?php echo $IMAGE_PATH; ?>" data-fancybox-group="gallery"><img src="<?php echo $IMAGE_PATH; ?>" style="width:120px; height:120px" /></a></div><?php } ?>
                                                </div>
                                                <div class="row">
                                                    <!-- <div class="col-6">
                                                    <div class="form-group">
                                                        <label class="col-md-12">Username Prefix</label>
                                                        <div class="col-md-12">
                                                            <input type="hidden" name="OLD_USERNAME_PREFIX" id="OLD_USERNAME_PREFIX" value="<?php echo $USERNAME_PREFIX ?>">
                                                            <input type="text" id="USERNAME_PREFIX" name="USERNAME_PREFIX" class="form-control" placeholder="Username Prefix" value="<?php echo $USERNAME_PREFIX ?>">
                                                        </div>
                                                    </div>
                                                </div> -->
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label for="example-text">Time Interval for Calendar Rows</label>
                                                            <div>
                                                                <select name="TIME_SLOT_INTERVAL" id="TIME_SLOT_INTERVAL" class="form-control required-entry" required>
                                                                    <option value="">Select</option>
                                                                    <?php for ($i = 5; $i <= 60; $i += 5) { ?>
                                                                        <option value="<?= '00:' . $i . ':00' ?>" <?= ($TIME_SLOT_INTERVAL == '00:' . $i . ':00') ? 'selected' : '' ?>><?= '00:' . $i . ':00' ?></option>
                                                                    <?php } ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <?php if (isset($_SESSION['error'])) { ?>
                                                        <div class="alert alert-danger">
                                                            <strong><?= $_SESSION['error']; ?></strong>
                                                        </div>
                                                    <?php } ?>
                                                </div>

                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Title for Service Provider</label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="SERVICE_PROVIDER_TITLE" name="SERVICE_PROVIDER_TITLE" class="form-control" placeholder="Title for Service Provider" value="<?php echo $SERVICE_PROVIDER_TITLE ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Name of the Tab for Charging Services</label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="OPERATION_TAB_TITLE" name="OPERATION_TAB_TITLE" class="form-control" placeholder="Name of the Tab for Charging Services" value="<?php echo $OPERATION_TAB_TITLE ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Enrollment Prefix</label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="ENROLLMENT_ID_CHAR" name="ENROLLMENT_ID_CHAR" class="form-control" placeholder="Enrollment Prefix" value="<?php echo $ENROLLMENT_ID_CHAR ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Starting Enrollment Number</label>
                                                            <div class="col-md-12">
                                                                <input type="number" id="ENROLLMENT_ID_NUM" name="ENROLLMENT_ID_NUM" class="form-control" placeholder="Starting Enrollment Number" value="<?php echo $ENROLLMENT_ID_NUM ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Misc Enrollment Prefix</label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="MISCELLANEOUS_ID_CHAR" name="MISCELLANEOUS_ID_CHAR" class="form-control" placeholder="Misc Enrollment Prefix" value="<?php echo $MISCELLANEOUS_ID_CHAR ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Starting Misc Enrollment Number</label>
                                                            <div class="col-md-12">
                                                                <input type="number" id="MISCELLANEOUS_ID_NUM" name="MISCELLANEOUS_ID_NUM" class="form-control" placeholder="Starting Misc Enrollment Number" value="<?php echo $MISCELLANEOUS_ID_NUM ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-3">
                                                        <div class="form-group">
                                                            <label class="col-md-12" for="example-text">Royalty Percentage</label>
                                                            <div class="input-group">
                                                                <input type="text" name="ROYALTY_PERCENTAGE" id="ROYALTY_PERCENTAGE" class="form-control" value="<?php echo $ROYALTY_PERCENTAGE ?>">
                                                                <span class="form-control input-group-text">%</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-3">
                                                        <div class="form-group">
                                                            <label class="col-md-12" for="example-text">Sales Tax</label>
                                                            <div class="input-group">
                                                                <input type="text" name="SALES_TAX" id="SALES_TAX" class="form-control" value="<?php echo $SALES_TAX ?>">
                                                                <span class="form-control input-group-text">%</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12" for="example-text">Receipt Prefix<span class="text-danger">*</span></label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="RECEIPT_CHARACTER" name="RECEIPT_CHARACTER" class="form-control" placeholder="Receipt Prefix" required value="<?= $RECEIPT_CHARACTER ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Focusbiz API Key</label>
                                                            <div class="col-md-12">
                                                                <input type="hidden" name="FOCUSBIZ_API_KEY_OLD" value="<?= $FOCUSBIZ_API_KEY ? $FOCUSBIZ_API_KEY : '' ?>">
                                                                <input type="text" id="FOCUSBIZ_API_KEY" name="FOCUSBIZ_API_KEY" class="form-control" placeholder="Enter Focusbiz API Key" value="<?php echo $FOCUSBIZ_API_KEY ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <!-- <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Username Prefix</label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="USERNAME_PREFIX" name="USERNAME_PREFIX" class="form-control" placeholder="Enter Username Prefix" value="<?php echo $USERNAME_PREFIX ?>">
                                                            </div>
                                                        </div>
                                                    </div> -->
                                                </div>

                                                <div class="row" style="margin-bottom: 15px; margin-top: 15px;">
                                                    <div class="col-md-4">
                                                        <label class="form-label">Texting Feature Enabled?</label>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label><input type="radio" name="TEXTING_FEATURE_ENABLED" id="TEXTING_FEATURE_ENABLED" value="1" <? if ($TEXTING_FEATURE_ENABLED == 1) echo 'checked="checked"'; ?> onclick="showTwilioAccountSetting(this);" />&nbsp;Yes</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                        <label><input type="radio" name="TEXTING_FEATURE_ENABLED" id="TEXTING_FEATURE_ENABLED" value="0" <? if ($TEXTING_FEATURE_ENABLED == 0) echo 'checked="checked"'; ?> onclick="showTwilioAccountSetting(this);" />&nbsp;No</label>
                                                    </div>
                                                </div>



                                                <div class="row twilio_account_type" id="twilio_account_type" style="display: <?= ($TEXTING_FEATURE_ENABLED == '1') ? '' : 'none' ?>; margin-bottom: 15px;">
                                                    <div class="row">
                                                        <div class="col-6">
                                                            <div class="form-group">
                                                                <label class="form-label" style="margin-bottom: 20px;">Send an Appointment Reminder Text message.</label><br>
                                                                <label style="margin-right: 70px;"><input type="radio" id="APPOINTMENT_REMINDER" name="APPOINTMENT_REMINDER" class="form-check-inline" value="1" <?= ($APPOINTMENT_REMINDER == '1') ? 'checked' : '' ?> onclick="showHourBox(this);">Yes</label>
                                                                <label style="margin-right: 70px;"><input type="radio" id="APPOINTMENT_REMINDER" name="APPOINTMENT_REMINDER" class="form-check-inline" value="0" <?= ($APPOINTMENT_REMINDER == '0') ? 'checked' : '' ?> onclick="showHourBox(this);">No</label>
                                                            </div>
                                                        </div>
                                                        <div class="col-6 hour_box" id="yes" style="display: <?= ($APPOINTMENT_REMINDER == '1') ? '' : 'none' ?>;">
                                                            <div class="form-group">
                                                                <label class="form-label">How many hours before the appointment ?</label>
                                                                <input type="text" class="form-control" name="HOUR" value="<?= $HOUR ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-12">
                                                        <label><input type="radio" name="TWILIO_ACCOUNT_TYPE" id="TWILIO_ACCOUNT_TYPE_0" value="0" <? if ($TWILIO_ACCOUNT_TYPE == 0) echo 'checked="checked"'; ?> onclick="showTwilioSetting(this);" />&nbsp;Using Doable's Twilio account</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                        <label><input type="radio" name="TWILIO_ACCOUNT_TYPE" id="TWILIO_ACCOUNT_TYPE_1" value="1" <? if ($TWILIO_ACCOUNT_TYPE == 1) echo 'checked="checked"'; ?> onclick="showTwilioSetting(this);" />&nbsp;Using Your own Twilio Account</label>
                                                    </div>
                                                </div>

                                                <div id="twilio_setting_div" class="row" style="display: <?= ($TEXTING_FEATURE_ENABLED == 1 && $TWILIO_ACCOUNT_TYPE == 1) ? '' : 'none' ?>; margin-top: 30px;">
                                                    <b class="btn btn-light" style="margin-bottom: 20px;">Twilio Setting</b>
                                                    <div class="col-4">
                                                        <div class="form-group">
                                                            <label class="col-md-12" for="example-text">SID</label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="SID" name="SID" class="form-control" placeholder="Enter SID" value="<?php echo $SID ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-4">
                                                        <div class="form-group">
                                                            <label class="col-md-12" for="example-text">Token</label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="TOKEN" name="TOKEN" class="form-control" placeholder="Enter TOKEN" value="<?php echo $TOKEN ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-4">
                                                        <div class="form-group">
                                                            <label class="col-md-12" for="example-text">Phone No.</label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="TWILIO_PHONE_NO" name="TWILIO_PHONE_NO" class="form-control" placeholder="Enter Phone No." value="<?php echo $TWILIO_PHONE_NO ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <?php if ($ABLE_TO_EDIT_PAYMENT_GATEWAY == 1) { ?>
                                                    <div class="row" style="margin-top: 30px;">
                                                        <b class="btn btn-light" style="margin-bottom: 20px;">Electronic Connection to Merchant Service</b>
                                                        <div class="row">
                                                            <div class="col-6">
                                                                <div class="form-group">
                                                                    <label class="form-label" style="margin-bottom: 20px;">Payment Gateway</label><br>
                                                                    <label style="margin-right: 30px;"><input type="radio" id="PAYMENT_GATEWAY_TYPE" name="PAYMENT_GATEWAY_TYPE" class="form-check-inline" value="Stripe" <?= ($PAYMENT_GATEWAY_TYPE == 'Stripe') ? 'checked' : '' ?> onclick="showPaymentGateway(this);">Stripe</label>
                                                                    <label style="margin-right: 30px;"><input type="radio" id="PAYMENT_GATEWAY_TYPE" name="PAYMENT_GATEWAY_TYPE" class="form-check-inline" value="Square" <?= ($PAYMENT_GATEWAY_TYPE == 'Square') ? 'checked' : '' ?> onclick="showPaymentGateway(this);">Square</label>
                                                                    <label style="margin-right: 30px;"><input type="radio" id="PAYMENT_GATEWAY_TYPE" name="PAYMENT_GATEWAY_TYPE" class="form-check-inline" value="Authorized.net" <?= ($PAYMENT_GATEWAY_TYPE == 'Authorized.net') ? 'checked' : '' ?> onclick="showPaymentGateway(this);">Authorized.net</label>
                                                                    <label style="margin-right: 30px;"><input type="radio" id="PAYMENT_GATEWAY_TYPE" name="PAYMENT_GATEWAY_TYPE" class="form-check-inline" value="Clover" <?= ($PAYMENT_GATEWAY_TYPE == 'Clover') ? 'checked' : '' ?> onclick="showPaymentGateway(this);">Clover</label>
                                                                </div>
                                                            </div>
                                                            <div class="col-6">
                                                                <div class="form-group">
                                                                    <label class="form-label" style="margin-bottom: 20px;">Gateway Mode</label><br>
                                                                    <label style="margin-right: 70px;"><input type="radio" id="GATEWAY_MODE" name="GATEWAY_MODE" class="form-check-inline" value="test" <?= ($GATEWAY_MODE == 'test' || $GATEWAY_MODE == null || $GATEWAY_MODE == '') ? 'checked' : '' ?>> Test</label>
                                                                    <label style="margin-right: 70px;"><input type="radio" id="GATEWAY_MODE" name="GATEWAY_MODE" class="form-check-inline" value="live" <?= ($GATEWAY_MODE == 'live') ? 'checked' : '' ?>> Live</label>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="row payment_gateway" id="stripe" style="display: <?= ($PAYMENT_GATEWAY_TYPE == 'Stripe') ? '' : 'none' ?>;">
                                                            <div class="col-12">
                                                                <div class="form-group">
                                                                    <label class="form-label">Secret Key</label>
                                                                    <input type="text" class="form-control" name="SECRET_KEY" value="<?= $SECRET_KEY ?>">
                                                                </div>
                                                                <div class="form-group">
                                                                    <label class="form-label">Publishable Key</label>
                                                                    <input type="text" class="form-control" name="PUBLISHABLE_KEY" value="<?= $PUBLISHABLE_KEY ?>">
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="row payment_gateway" id="square" style="display: <?= ($PAYMENT_GATEWAY_TYPE == 'Square') ? '' : 'none' ?>">
                                                            <div class="col-12">
                                                                <div class="form-group">
                                                                    <label class="form-label">Application ID</label>
                                                                    <input type="text" class="form-control" name="APP_ID" value="<?= $SQUARE_APP_ID ?>">
                                                                </div>
                                                                <div class="form-group">
                                                                    <label class="form-label">Location ID</label>
                                                                    <input type="text" class="form-control" name="LOCATION_ID" value="<?= $SQUARE_LOCATION_ID ?>">
                                                                </div>
                                                                <div class="form-group">
                                                                    <label class="form-label">Access Token</label>
                                                                    <input type="text" class="form-control" name="ACCESS_TOKEN" value="<?= $ACCESS_TOKEN ?>">
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="row payment_gateway" id="authorized" style="display: <?= ($PAYMENT_GATEWAY_TYPE == 'Authorized.net') ? '' : 'none' ?>">
                                                            <div class="col-12">
                                                                <div class="form-group">
                                                                    <label class="form-label">Login ID</label>
                                                                    <input type="text" class="form-control" name="LOGIN_ID" value="<?= $LOGIN_ID ?>">
                                                                </div>
                                                                <div class="form-group">
                                                                    <label class="form-label">Transaction Key</label>
                                                                    <input type="text" class="form-control" name="TRANSACTION_KEY" value="<?= $TRANSACTION_KEY ?>">
                                                                </div>
                                                                <div class="form-group">
                                                                    <label class="form-label">Authorize Client Key</label>
                                                                    <input type="text" class="form-control" name="AUTHORIZE_CLIENT_KEY" value="<?= $AUTHORIZE_CLIENT_KEY ?>">
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="row payment_gateway" id="Clover" style="display: <?= ($PAYMENT_GATEWAY_TYPE == 'Clover') ? '' : 'none' ?>;">
                                                            <div class="col-12">
                                                                <div class="form-group">
                                                                    <label class="form-label">Merchant ID</label>
                                                                    <input type="text" class="form-control" name="MERCHANT_ID" value="<?= $MERCHANT_ID ?>">
                                                                </div>
                                                                <div class="form-group">
                                                                    <label class="form-label">Private Token</label>
                                                                    <input type="text" class="form-control" name="API_KEY" value="<?= $API_KEY ?>">
                                                                </div>
                                                                <div class="form-group">
                                                                    <label class="form-label">Public Token</label>
                                                                    <input type="text" class="form-control" name="PUBLIC_API_KEY" value="<?= $PUBLIC_API_KEY ?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php } ?>

                                                <div class="row" style="margin-top: 30px;">
                                                    <b class="btn btn-light" style="margin-bottom: 20px;">Email Connection</b>
                                                    <div class="col-3">
                                                        <div class="form-group">
                                                            <label class="form-label">SMTP HOST</label>
                                                            <input type="text" class="form-control" name="SMTP_HOST" value="<?= $SMTP_HOST ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-3">
                                                        <div class="form-group">
                                                            <label class="form-label">SMTP PORT</label>
                                                            <input type="text" class="form-control" name="SMTP_PORT" value="<?= $SMTP_PORT ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-3">
                                                        <div class="form-group">
                                                            <label class="form-label">SMTP USERNAME</label>
                                                            <input type="text" class="form-control" name="SMTP_USERNAME" value="<?= $SMTP_USERNAME ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-3">
                                                        <div class="form-group">
                                                            <label class="form-label">SMTP PASSWORD</label>
                                                            <input type="text" class="form-control" name="SMTP_PASSWORD" value="<?= $SMTP_PASSWORD ?>">
                                                        </div>
                                                    </div>
                                                </div>

                                                <?php if ($AMI_ENABLE == 1) { ?>
                                                    <div class="row arthur_murray_setup" id="arthur_murray_setup" style="display: <?= ($FRANCHISE == '1') ? '' : 'none' ?>; margin-top: 30px;">
                                                        <b class="btn btn-light" style="margin-bottom: 20px;">Arthur Murray API Setup</b>
                                                        <div class="col-4">
                                                            <div class="form-group">
                                                                <label class="form-label">User Name</label>
                                                                <input type="text" class="form-control" name="AM_USER_NAME" value="<?= $AM_USER_NAME ?>">
                                                            </div>
                                                        </div>
                                                        <div class="col-4">
                                                            <div class="form-group">
                                                                <label class="form-label">Password</label>
                                                                <input type="text" class="form-control" name="AM_PASSWORD" value="<?= $AM_PASSWORD ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php } ?>

                                                <?php if (!empty($_GET['id'])) { ?>
                                                    <div class="row" style="margin-bottom: 15px;">
                                                        <div class="col-6">
                                                            <div class="form-group">
                                                                <label class="form-label" style="margin-bottom: 10px;">Active</label><br>
                                                                <label style="margin-right: 30px;"><input type="radio" name="ACTIVE" id="ACTIVE" value="1" <?php if ($ACTIVE == 1) echo 'checked="checked"'; ?> />&nbsp;Yes</label>&nbsp;&nbsp;
                                                                <label style="margin-right: 30px;"><input type="radio" name="ACTIVE" id="ACTIVE" value="0" <?php if ($ACTIVE == 0) echo 'checked="checked"'; ?> />&nbsp;No</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php } ?>

                                                <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">Save</button>
                                                <button type="button" class="btn btn-inverse waves-effect waves-light" onclick="window.location.href='all_locations.php'">Cancel</button>

                                                <!-- Hidden submit button for the form -->
                                                <button type="submit" id="realSubmit" style="display:none;"></button>
                                            </div>
                                        </form>
                                    </div>

                                    <div class="tab-pane" id="operational_hours" role="tabpanel">
                                        <form class="form-material form-horizontal" action="" method="post" enctype="multipart/form-data">
                                            <input type="hidden" name="FUNCTION_NAME" value="saveOperationalHours">
                                            <div class="p-20">
                                                <div class="row">
                                                    <div class="col-3">
                                                        <div class="form-group" style="text-align: center;">
                                                            <label class="form-label" for="example-text" style="font-weight: bold;">Day</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-3">
                                                        <div class="form-group" style="text-align: center;">
                                                            <label class="form-label" for="example-text" style="font-weight: bold;">Open Time</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-3">
                                                        <div class="form-group" style="text-align: center;">
                                                            <label class="form-label" for="example-text" style="font-weight: bold;">Close Time</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-3">
                                                        <div class="form-group">
                                                            <label><input type="checkbox" name="ALL_DAYS" class="form-check-inline" onclick="applyToAllDays(this)"> All Days</label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php
                                                $operational_hours = $db_account->Execute("SELECT * FROM DOA_OPERATIONAL_HOUR WHERE `PK_LOCATION` = '$PK_LOCATION'");
                                                if ($operational_hours->RecordCount() > 0) {
                                                    $i = 0;
                                                    while (!$operational_hours->EOF) { ?>
                                                        <div class="row">
                                                            <div class="col-3">
                                                                <div class="form-group">
                                                                    <div class="col-md-12">
                                                                        <select name="DAY_NUMBER[]" class="form-control required-entry" disabled>
                                                                            <option value="1" <?= ($operational_hours->fields['DAY_NUMBER'] == 1) ? 'selected' : '' ?>>Monday</option>
                                                                            <option value="2" <?= ($operational_hours->fields['DAY_NUMBER'] == 2) ? 'selected' : '' ?>>Tuesday</option>
                                                                            <option value="3" <?= ($operational_hours->fields['DAY_NUMBER'] == 3) ? 'selected' : '' ?>>Wednesday</option>
                                                                            <option value="4" <?= ($operational_hours->fields['DAY_NUMBER'] == 4) ? 'selected' : '' ?>>Thursday</option>
                                                                            <option value="5" <?= ($operational_hours->fields['DAY_NUMBER'] == 5) ? 'selected' : '' ?>>Friday</option>
                                                                            <option value="6" <?= ($operational_hours->fields['DAY_NUMBER'] == 6) ? 'selected' : '' ?>>Saturday</option>
                                                                            <option value="7" <?= ($operational_hours->fields['DAY_NUMBER'] == 7) ? 'selected' : '' ?>>Sunday</option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-3">
                                                                <div class="form-group">
                                                                    <div class="col-md-12">
                                                                        <input type="text" name="OPEN_TIME[]" class="form-control time-input time-picker OPEN_TIME" value="<?= ($operational_hours->fields['OPEN_TIME'] == '00:00:00') ? '' : date('h:i A', strtotime($operational_hours->fields['OPEN_TIME'])) ?>" style="pointer-events: <?= ($operational_hours->fields['CLOSED'] == 1) ? 'none' : '' ?>" readonly>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-3">
                                                                <div class="form-group">
                                                                    <div class="col-md-12">
                                                                        <input type="text" name="CLOSE_TIME[]" class="form-control time-input time-picker CLOSE_TIME" value="<?= ($operational_hours->fields['CLOSE_TIME'] == '00:00:00') ? '' : date('h:i A', strtotime($operational_hours->fields['CLOSE_TIME'])) ?>" style="pointer-events: <?= ($operational_hours->fields['CLOSED'] == 1) ? 'none' : '' ?>" readonly>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-3">
                                                                <div class="form-group">
                                                                    <div class="col-md-12" style="margin-top: 10px;">
                                                                        <label><input type="checkbox" name="CLOSED_<?= $i ?>" onchange="closeThisDay(this)" <?= ($operational_hours->fields['CLOSED'] == 1) ? 'checked' : '' ?>> Closed</label>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php $operational_hours->MoveNext();
                                                        $i++;
                                                    } ?>
                                                    <?php } else {
                                                    for ($i = 1; $i <= 7; $i++) { ?>
                                                        <div class="row">
                                                            <div class="col-3">
                                                                <div class="form-group">
                                                                    <div class="col-md-12">
                                                                        <select name="DAY_NUMBER[]" class="form-control required-entry" disabled>
                                                                            <option value="1" <?= ($i == 1) ? 'selected' : '' ?>>Monday</option>
                                                                            <option value="2" <?= ($i == 2) ? 'selected' : '' ?>>Tuesday</option>
                                                                            <option value="3" <?= ($i == 3) ? 'selected' : '' ?>>Wednesday</option>
                                                                            <option value="4" <?= ($i == 4) ? 'selected' : '' ?>>Thursday</option>
                                                                            <option value="5" <?= ($i == 5) ? 'selected' : '' ?>>Friday</option>
                                                                            <option value="6" <?= ($i == 6) ? 'selected' : '' ?>>Saturday</option>
                                                                            <option value="7" <?= ($i == 7) ? 'selected' : '' ?>>Sunday</option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-3">
                                                                <div class="form-group">
                                                                    <div class="col-md-12">
                                                                        <input type="text" name="OPEN_TIME[]" class="form-control time-input time-picker OPEN_TIME" readonly>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-3">
                                                                <div class="form-group">
                                                                    <div class="col-md-12">
                                                                        <input type="text" name="CLOSE_TIME[]" class="form-control time-input time-picker CLOSE_TIME" readonly>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-3">
                                                                <div class="form-group">
                                                                    <div class="col-md-12" style="margin-top: 10px;">
                                                                        <label><input type="checkbox" name="CLOSED_<?= $i - 1 ?>" onchange="closeThisDay(this)"> Closed</label>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                <?php }
                                                } ?>
                                            </div>
                                            <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">Save</button>
                                            <button type="button" class="btn btn-inverse waves-effect waves-light" onclick="window.location.href='all_locations.php'">Cancel</button>
                                        </form>
                                    </div>

                                    <div class="tab-pane" id="holiday_list" role="tabpanel">
                                        <form class="form-material form-horizontal" action="" method="post" enctype="multipart/form-data">
                                            <input type="hidden" name="FUNCTION_NAME" value="saveHolidayData">
                                            <input type="hidden" name="PK_LOCATION" value="<?= $PK_LOCATION ?>">
                                            <div class="p-20" id="holiday_list_section">
                                                <div class="row">
                                                    <div class="col-3">
                                                        <div class="form-group" style="text-align: center;">
                                                            <label class="form-label" style="font-weight: bold;">Holiday Date</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-3">
                                                        <div class="form-group" style="text-align: center;">
                                                            <label class="form-label" style="font-weight: bold;">Holiday Name</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-3" style="margin-top: -30px;">
                                                        <a href="javascript:;" class="btn btn-info waves-effect waves-light text-white" style="margin-top: 30px;" onclick="addMoreHoliday();">Add More</a>
                                                    </div>
                                                </div>
                                                <?php
                                                $holiday_list = $db->Execute("SELECT * FROM DOA_LOCATION_HOLIDAY_LIST WHERE PK_LOCATION = " . $PK_LOCATION);
                                                if ($holiday_list->RecordCount() > 0) {
                                                    while (!$holiday_list->EOF) { ?>
                                                        <div class="row">
                                                            <div class="col-3">
                                                                <div class="form-group">
                                                                    <div class="col-md-12">
                                                                        <input type="text" name="HOLIDAY_DATE[]" class="form-control datepicker-normal" value="<?= date('m/d/Y', strtotime($holiday_list->fields['HOLIDAY_DATE'])) ?>">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-3">
                                                                <div class="form-group">
                                                                    <div class="col-md-12">
                                                                        <input type="text" name="HOLIDAY_NAME[]" class="form-control" value="<?= $holiday_list->fields['HOLIDAY_NAME'] ?>">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-3" style="padding-top: 5px;">
                                                                <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                                            </div>
                                                        </div>
                                                    <?php $holiday_list->MoveNext();
                                                    } ?>
                                                <?php } else { ?>
                                                    <div class="row">
                                                        <div class="col-3">
                                                            <div class="form-group">
                                                                <div class="col-md-12">
                                                                    <input type="text" name="HOLIDAY_DATE[]" class="form-control datepicker-normal">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-3">
                                                            <div class="form-group">
                                                                <div class="col-md-12">
                                                                    <input type="text" name="HOLIDAY_NAME[]" class="form-control">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-3" style="padding-top: 5px;">
                                                            <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                                        </div>
                                                    </div>
                                                <?php } ?>
                                            </div>
                                            <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">Save</button>
                                            <button type="button" class="btn btn-inverse waves-effect waves-light" onclick="window.location.href='business_profile.php'">Cancel</button>
                                        </form>
                                    </div>

                                    <div class="tab-pane" id="customer_tab_permissions" role="tabpanel">
                                        <form class="form-material form-horizontal" action="" method="post">
                                            <input type="hidden" name="FUNCTION_NAME" value="savePermissionData">
                                            <input type="hidden" name="PK_LOCATION" value="<?= $PK_LOCATION ?>">

                                            <div class="p-20" id="permission_list_div">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group text-center">
                                                            <label class="form-label font-weight-bold">Customer Tabs</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group text-center">
                                                            <label class="form-label font-weight-bold">Visible in Customer Login</label>
                                                        </div>
                                                    </div>
                                                </div>

                                                <?php
                                                $tab_options = [
                                                    'Profile' => 'Profile',
                                                    'Family' => 'Family',
                                                    'Documents' => 'Documents',
                                                    'Active Enrollments' => 'Active Enrollments',
                                                    'Completed Enrollments' => 'Completed Enrollments',
                                                    'Payment Register' => 'Payment Register',
                                                    'Appointments' => 'Appointments',
                                                    'For Record Only' => 'For Record Only',
                                                    'Comments' => 'Comments',
                                                    'Credit Card' => 'Credit Card',
                                                    'Wallet' => 'Wallet',
                                                    'Delete' => 'Delete'
                                                ];

                                                $customer_tabs = $db->Execute("SELECT * FROM DOA_CUSTOMER_TAB WHERE PK_LOCATION = " . $PK_LOCATION);
                                                $existing_permissions = [];
                                                while (!$customer_tabs->EOF) {
                                                    $existing_permissions[$customer_tabs->fields['TAB_NAME']] = $customer_tabs->fields['PERMISSION'];
                                                    $customer_tabs->MoveNext();
                                                }

                                                $i = 0;
                                                foreach ($tab_options as $tab_key => $tab_label) {
                                                    // Modified this line to default to checked if no existing permission is found
                                                    $is_checked = isset($existing_permissions[$tab_key]) ? ($existing_permissions[$tab_key] == 1) : true;
                                                ?>
                                                    <div class="row mb-3">
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <input type="text" class="form-control" value="<?= htmlspecialchars($tab_label) ?>" readonly>
                                                                <input type="hidden" name="TAB_NAME[]" value="<?= $tab_key ?>">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="form-group text-center">
                                                                <label class="switch">
                                                                    <input type="checkbox" name="PERMISSION[<?= $i ?>]" value="1" <?= $is_checked ? 'checked' : '' ?>>
                                                                    <span class="slider"></span>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php
                                                    $i++;
                                                }
                                                ?>
                                            </div>

                                            <div class="form-group text-right">
                                                <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">Save</button>
                                                <button type="button" class="btn btn-inverse waves-effect waves-light" onclick="window.location.href='all_locations.php'">Cancel</button>
                                            </div>
                                        </form>
                                    </div>

                                    <div class="tab-pane" id="billing" role="tabpanel" style="margin-top: 20px;">
                                        <div class="row">
                                            <div class="col-6">
                                                <form class="form-material form-horizontal" id="location_payment_form" method="post" enctype="multipart/form-data">
                                                    <input type="hidden" class="PK_ACCOUNT_MASTER" name="PK_ACCOUNT_MASTER" value="<?= $PK_ACCOUNT_MASTER ?>">
                                                    <input type="hidden" class="PK_LOCATION" name="PK_LOCATION" value="<?= $PK_LOCATION ?>">
                                                    <div class="p-20">
                                                        <div class="row">
                                                            <div class="col-5">
                                                                <div class="form-group">
                                                                    <label class="col-md-12">Subscription Start Date</label>
                                                                    <div class="col-md-12">
                                                                        <p><?= ($START_DATE == '') ? '' : date('m/d/Y', strtotime($START_DATE)) ?></p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-5">
                                                                <div class="form-group">
                                                                    <label class="col-md-12">Next Renewal Date</label>
                                                                    <div class="col-md-12">
                                                                        <p><?= ($RENEWAL_INTERVAL == 'monthly') ? date('m/d/Y', strtotime('+1 month', strtotime($START_DATE))) : date('m/d/Y', strtotime('+1 year', strtotime($START_DATE))) ?></p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-2">
                                                                <div class="form-group">
                                                                    <label class="col-md-12">Status</label>
                                                                    <div class="col-md-12">
                                                                        <p><?= ($ACTIVE == 1) ? 'Active' : 'Inactive' ?></p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="row" style="margin-bottom: 15px;">
                                                            <div class="col-12">
                                                                <div class="form-group">
                                                                    <label class="form-label" style="margin-bottom: 10px;">Payment From</label><br>
                                                                    <label style="margin-right: 30px;"><input type="radio" name="PAYMENT_FROM" id="PAYMENT_FROM" value="location" <?= ($PAYMENT_FROM == 'location') ? 'checked' : '' ?> onclick="changePaymentFrom(this)" />&nbsp;Location</label>&nbsp;&nbsp;
                                                                    <label style="margin-right: 30px;"><input type="radio" name="PAYMENT_FROM" id="PAYMENT_FROM" value="corporation" <?= ($PAYMENT_FROM == 'corporation') ? 'checked' : '' ?> onclick="changePaymentFrom(this)" />&nbsp;Corporation</label>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div id="payment_details_div" style="display: <?= ($PAYMENT_FROM == 'location') ? '' : 'none' ?>;">
                                                            <div class="row">
                                                                <div class="col-12">
                                                                    <div class="form-group">
                                                                        <label class="col-md-12">Amount</label>
                                                                        <div class="col-md-12">
                                                                            <input type="text" class="form-control" value="<?= '$' . $AMOUNT ?>" disabled>
                                                                            <input type="hidden" name="AMOUNT" id="AMOUNT" value="<?= $AMOUNT ?>">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-12">
                                                                    <input type="hidden" name="PAYMENT_METHOD_ID" id="PAYMENT_METHOD_ID" value="">
                                                                    <?php if ($SA_PAYMENT_GATEWAY_TYPE == 'Stripe') { ?>
                                                                        <input type="hidden" name="stripe_token" id="stripe_token" value="">
                                                                        <div class="row">
                                                                            <div class="col-12">
                                                                                <div class="form-group" id="card_div">

                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="row" id="card_list_div">
                                                                        </div>
                                                                    <?php } elseif ($SA_PAYMENT_GATEWAY_TYPE == 'Square') { ?>
                                                                        <input type="hidden" name="square_token" id="square_token" value="">
                                                                        <div class="row">
                                                                            <div class="col-12">
                                                                                <div id="payment-card-container"></div>
                                                                                <div id="payment-status-container"></div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="row" id="card_list_div">
                                                                        </div>
                                                                    <?php } ?>
                                                                </div>
                                                            </div>
                                                            <div class="row" id="location_payment_status"></div>
                                                        </div>

                                                        <div class="form-group">
                                                            <button type="submit" id="location-payment-btn" class="btn btn-info waves-effect waves-light m-r-10 text-white">Process</button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>

                                            <div class="col-6">
                                                <h4 style="text-align: center; margin-bottom: 20px;">Payment History</h4>
                                                <table id="payment_table" class="table table-striped border">
                                                    <thead>
                                                        <tr>
                                                            <th style="text-align: center;">Date</th>
                                                            <th style="text-align: center;">Status</th>
                                                            <th style="text-align: center;">Amount</th>
                                                            <th style="text-align: center;">Info</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php
                                                        $location_payments = $db->Execute("SELECT * FROM DOA_PAYMENT_DETAILS WHERE CLASS = 'location' AND PK_VALUE = " . $PK_LOCATION . " ORDER BY DATE_TIME DESC");
                                                        if ($location_payments->RecordCount() > 0) {
                                                            while (!$location_payments->EOF) {
                                                                $payment_info = json_decode($location_payments->fields['PAYMENT_INFO']);
                                                                $payment_type = 'Credit Card' . " # " . ((isset($payment_info->LAST4)) ? $payment_info->LAST4 : ''); ?>
                                                                <tr>
                                                                    <td style="text-align: center;"><?= date('m/d/Y h:i A', strtotime($location_payments->fields['DATE_TIME'])) ?></td>
                                                                    <td style="text-align: center;"><?= $location_payments->fields['PAYMENT_STATUS'] ?></td>
                                                                    <td style="text-align: center;">$<?= number_format($location_payments->fields['AMOUNT'], 2) ?></td>
                                                                    <td style="text-align: center;"><?= $payment_type ?></td>
                                                                </tr>
                                                            <?php $location_payments->MoveNext();
                                                            } ?>
                                                        <?php } else { ?>
                                                            <tr>
                                                                <td colspan="4" style="text-align: center;">No payment records found.</td>
                                                            </tr>
                                                        <?php } ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-4">
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <h4 class="col-md-12" STYLE="text-align: center">
                                        <?= $help_title ?>
                                    </h4>
                                    <div class="col-md-12">
                                        <text class="required-entry rich" id="DESCRIPTION"><?= $help_description ?></text>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php require_once('../includes/footer.php'); ?>
</body>

<script>
    $('.datepicker-normal').datepicker({
        format: 'mm/dd/yyyy',
    });

    $('.time-picker').timepicker({
        timeFormat: 'hh:mm p',
        interval: 30,
        dynamic: false,
        dropdown: true,
        scrollbar: true
    });

    function closeThisDay(param) {
        if ($(param).is(':checked')) {
            $(param).closest('.row').find('.time-input').val('');
            $(param).closest('.row').find('.time-input').css('pointer-events', 'none');
        } else {
            $(param).closest('.row').find('.time-input').css('pointer-events', '');
        }
    }

    $(document).ready(function() {
        fetch_state(<?php echo $PK_COUNTRY; ?>);
    });

    function fetch_state(PK_COUNTRY) {
        jQuery(document).ready(function($) {
            var data = "PK_COUNTRY=" + PK_COUNTRY + "&PK_STATES=<?= $PK_STATES; ?>";
            var value = $.ajax({
                url: "ajax/state.php",
                type: "POST",
                data: data,
                async: false,
                cache: false,
                success: function(result) {
                    document.getElementById('State_div').innerHTML = result;
                }
            }).responseText;
        });
    }

    function showPaymentGateway(radio) {
        $('.payment_gateway').slideUp();
        if (radio.value == 'Stripe') {
            document.getElementById('stripe').style.display = 'block';
        } else if (radio.value == 'Square') {
            document.getElementById('square').style.display = 'block';
        } else if (radio.value == 'Authorized.net') {
            document.getElementById('authorized').style.display = 'block';
        } else if (radio.value == 'Clover') {
            document.getElementById('Clover').style.display = 'block';
        }
    }

    function applyToAllDays(param) {
        if ($(param).is(':checked')) {
            let OPEN_TIME = $(".OPEN_TIME");
            $('.OPEN_TIME').val($(OPEN_TIME[0]).val());

            let CLOSE_TIME = $(".CLOSE_TIME");
            $('.CLOSE_TIME').val($(CLOSE_TIME[0]).val());
        } else {
            let OPEN_TIME = $(".OPEN_TIME");
            for (let i = 1; i < 7; i++) {
                $(OPEN_TIME[i]).val('');
            }

            let CLOSE_TIME = $(".CLOSE_TIME");
            for (let i = 1; i < 7; i++) {
                $(CLOSE_TIME[i]).val('');
            }
        }
    }
</script>
<script>
    function showTwilioAccountSetting(param) {
        if ($(param).val() === '1') {
            $('#twilio_account_type').slideDown();
        } else {
            $('#twilio_account_type').slideUp();
            $('#TWILIO_ACCOUNT_TYPE_0').prop('checked', true);
            $('#twilio_setting_div').slideUp();
        }
    }

    function showTwilioSetting(param) {
        if ($(param).val() === '1') {
            $('#twilio_setting_div').slideDown();
        } else {
            $('#twilio_setting_div').slideUp();
        }
    }

    function showArthurMurraySetup(param) {
        if ($(param).val() === '1') {
            $('#arthur_murray_setup').slideDown();
        } else {
            $('#arthur_murray_setup').slideUp();
        }
    }

    function addMoreHoliday() {
        $('#holiday_list_section').append(`<div class="row">
                                            <div class="col-3">
                                                <div class="form-group">
                                                    <div class="col-md-12">
                                                        <input type="text" name="HOLIDAY_DATE[]" class="form-control datepicker-normal">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-3">
                                                <div class="form-group">
                                                    <div class="col-md-12">
                                                        <input type="text" name="HOLIDAY_NAME[]" class="form-control">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-3" style="padding-top: 5px;">
                                                <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                            </div>
                                        </div>`);

        $('.datepicker-normal').datepicker({
            format: 'mm/dd/yyyy',
        });
    }

    function removeThis(param) {
        $(param).closest('.row').remove();
    }

    function showHourBox(radio) {
        if (radio.value == 1) {
            document.getElementById("yes").style.display = "block";
        } else {
            document.getElementById("yes").style.display = "none";
        }
    }

    function changePaymentFrom(param) {
        if ($(param).val() == 'corporation') {
            $('#payment_details_div').slideUp();
        } else {
            $('#payment_details_div').slideDown();
        }
    }

    function getSavedCreditCardList() {
        let payment_gateway_type = '<?= $SA_PAYMENT_GATEWAY_TYPE ?>';
        if (payment_gateway_type == 'Square') {
            squarePaymentFunction();
        } else if (payment_gateway_type == 'Stripe') {
            stripePaymentFunction();
        }
        $.ajax({
            url: "ajax/get_credit_card_list_from_master.php",
            type: 'POST',
            data: {
                PK_VALUE: '<?= $PK_LOCATION ?>',
                class: 'location'
            },
            success: function(data) {
                $('#card_list_div').slideDown().html(data);
            }
        });
    }
</script>


<?php if ($SA_PAYMENT_GATEWAY_TYPE == 'Stripe') { ?>
    <script src="https://js.stripe.com/v3/"></script>
    <script type="text/javascript">
        var stripe = Stripe('<?= $SA_PUBLISHABLE_KEY ?>');
        var elements = stripe.elements();

        var style = {
            base: {
                height: '34px',
                padding: '6px 12px',
                fontSize: '14px',
                lineHeight: '1.42857143',
                color: '#555',
                backgroundColor: '#fff',
                border: '1px solid #ccc',
                borderRadius: '4px',
                '::placeholder': {
                    color: '#ddd'
                }
            },
            invalid: {
                color: '#fa755a',
                iconColor: '#fa755a'
            }
        };

        // Create an instance of the card Element.
        var stripe_card = elements.create('card', {
            style: style
        });
        var pay_type = '';

        function stripePaymentFunction() {
            if (($('#card-element')).length > 0) {
                stripe_card.mount('#card-element');
            }
            // Handle real-time validation errors from the card Element.
            stripe_card.addEventListener('change', function(event) {
                var displayError = document.getElementById('card-errors');
                if (event.error) {
                    displayError.textContent = event.error.message;
                } else {
                    displayError.textContent = '';
                    addStripeTokenOnForm();
                }
            });
        }

        function addStripeTokenOnForm() {
            //event.preventDefault();
            stripe.createToken(stripe_card).then(function(result) {
                if (result.error) {
                    // Inform the user if there was an error.
                    let errorElement = document.getElementById('card-errors');
                    errorElement.textContent = result.error.message;
                } else {
                    // Send the token to your server.
                    $('#stripe_token').val(result.token.id);
                }
            });
        }
    </script>
<?php } ?>

<?php if ($SA_PAYMENT_GATEWAY_TYPE == 'Square') {
    if ($SA_GATEWAY_MODE == 'live')
        $SQ_URL = "https://connect.squareup.com";
    else
        $SQ_URL = "https://connect.squareupsandbox.com";

    if ($SA_GATEWAY_MODE == 'live')
        $URL = "https://web.squarecdn.com/v1/square.js";
    else
        $URL = "https://sandbox.web.squarecdn.com/v1/square.js";
?>
    <script src="<?= $URL ?>"></script>
    <script type="text/javascript">
        let square_card;

        async function squarePaymentFunction() {
            let square_appId = '<?= $SA_SQUARE_APP_ID ?>';
            let square_locationId = '<?= $SA_SQUARE_LOCATION_ID ?>';
            const payments = Square.payments(square_appId, square_locationId);
            square_card = await payments.card();
            $('#payment-card-container').text('');
            await square_card.attach('#payment-card-container');
        }

        async function addSquareTokenOnForm() {
            const statusContainer = document.getElementById('payment-status-container');

            try {
                // Tokenize the card details
                const result = await square_card.tokenize();
                if (result.status === 'OK') {
                    // Add the token to the hidden input field
                    $('#square_token').val(result.token);
                } else {
                    // Handle tokenization errors
                    let errorMessage = `Tokenization failed with status: ${result.status}`;
                    if (result.errors) {
                        errorMessage += ` and errors: ${JSON.stringify(result.errors)}`;
                    }
                    throw new Error(errorMessage);
                }
            } catch (e) {
                console.error(e);
                statusContainer.innerHTML = `<p class="alert alert-danger">Payment Failed: ${e.message}</p>`;
            }
        }
    </script>
<?php } ?>


<script type="text/javascript">
    function sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    function getPaymentMethodId(param) {
        $('.credit-card-div').css("opacity", "1");
        $('#PAYMENT_METHOD_ID').val($(param).attr('id'));
        $(param).css("opacity", "0.6");
    }

    $(document).on('submit', '#location_payment_form', function(event) {
        event.preventDefault();
        $('#location-payment-btn').prop('disabled', true);
        let PAYMENT_GATEWAY = '<?= $SA_PAYMENT_GATEWAY_TYPE ?>';
        if (PAYMENT_GATEWAY == 'Square') {
            let PAYMENT_METHOD_ID = $('#PAYMENT_METHOD_ID').val();
            if (PAYMENT_METHOD_ID == '') {
                addSquareTokenOnForm();
                sleep(3000).then(() => {
                    submitLocationPaymentForm();
                });
            } else {
                submitLocationPaymentForm();
            }
        } else {
            submitLocationPaymentForm();
        }
    });

    function submitLocationPaymentForm() {
        let form_data = $('#location_payment_form').serialize();
        $.ajax({
            url: "includes/process_location_payment.php",
            type: 'POST',
            data: form_data,
            dataType: 'json',
            success: function(data) {
                if (data.STATUS === 'Failed') {
                    $('#location_payment_status').html(`<p class="alert alert-danger">${data.PAYMENT_INFO}</p>`);
                    $('#location-payment-btn').prop('disabled', false);
                } else {
                    $('#location_payment_status').html(`<p class="alert alert-success">Payment Successful, Page will refresh automatically.</p>`);

                    setTimeout(function() {
                        location.reload();
                    }, 3000);
                }
            }
        });
    }

    $('#payment_table').DataTable({
        order: [
            [0, 'desc']
        ],
        columnDefs: [{
            type: 'date',
            targets: 0
        }],
    });
</script>


<script type="text/javascript">
    function changeTabPermission(PK_LOCATION_CUSTOMER_TAB) {
        var checkbox = event.target;
        var countOnPermission = checkbox.checked ? 1 : 0;

        $.ajax({
            url: "ajax/AjaxFunctions.php",
            type: 'POST',
            data: {
                FUNCTION_NAME: 'updateTabPermission',
                PK_LOCATION_CUSTOMER_TAB: PK_LOCATION_CUSTOMER_TAB,
                COUNT_ON_PERMISSION: countOnPermission
            },
            success: function(data) {

            }
        });
    }
</script>

</html>