<?php
require_once('../global/config.php');
global $db;
global $db_account;
global $master_database;
global $upload_path;

$DEFAULT_LOCATION_ID = $_SESSION['DEFAULT_LOCATION_ID'];

if ($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4])) {
    header("location:../login.php");
    exit;
}

if (empty($_GET['id']))
    $title = "Add Enrollment";
else
    $title = "Edit Enrollment";

if (!empty($_GET['source']) && $_GET['source'] === 'customer') {
    $header = 'customer.php?id=' . $_GET['id_customer'] . '&master_id=' . $_GET['master_id_customer'] . '&tab=enrollment';
} else {
    $header = '';
}

$PK_ENROLLMENT_MASTER = 0;
$ENROLLMENT_NAME = '';
$ENROLLMENT_DATE = date('m/d/Y');
$PK_ENROLLMENT_TYPE = '';
$PK_LOCATION = '';
$PK_PACKAGE = '';
$TOTAL = '';
$FINAL_AMOUNT = '';
$PK_AGREEMENT_TYPE = '';
$PK_DOCUMENT_LIBRARY = 1;
$AGREEMENT_PDF_LINK = '';
$ENROLLMENT_BY_ID = $_SESSION['PK_USER'];
$ENROLLMENT_BY_PERCENTAGE = '';
$MEMO = '';
$ACTIVE = '';
$ACTIVE_AUTO_PAY = 0;

$PK_ENROLLMENT_BILLING = '';
$BILLING_REF = '';
$BILLING_DATE = '';
$DOWN_PAYMENT = 0.00;
$BALANCE_PAYABLE = 0.00;
$PAYMENT_METHOD = '';
$PAYMENT_TERM = '';
$NUMBER_OF_PAYMENT = '';
$FIRST_DUE_DATE = '';
$INSTALLMENT_AMOUNT = '';

$PK_ENROLLMENT_PAYMENT = '';
$PK_PAYMENT_TYPE = '';
$AMOUNT = '';
$NAME = '';
$CARD_NUMBER = '';
$SECURITY_CODE = '';
$EXPIRY_DATE = '';
$CHECK_NUMBER = '';
$CHECK_DATE = '';
$NOTE = '';
$CHARGE_TYPE = '';

$PK_USER_MASTER = '';
if (!empty($_GET['master_id_customer'])) {
    $PK_USER_MASTER = $_GET['master_id_customer'];
    $user_location = $db->Execute("SELECT `PK_LOCATION` FROM `DOA_USER_LOCATION` INNER JOIN DOA_USER_MASTER ON DOA_USER_MASTER.PK_USER = DOA_USER_LOCATION.PK_USER WHERE DOA_USER_MASTER.PK_USER_MASTER = " . $PK_USER_MASTER);
    if ($user_location->RecordCount() > 0) {
        $PK_LOCATION = $user_location->fields['PK_LOCATION'];
    } else {
        $PK_LOCATION = 0;
    }
}

$months = '';
$day = '';
if (!empty($_GET['id'])) {
    $res = $db_account->Execute("SELECT * FROM `DOA_ENROLLMENT_MASTER` WHERE `PK_ENROLLMENT_MASTER` = '$_GET[id]'");
    if ($res->RecordCount() == 0) {
        header("location:all_enrollments.php");
        exit;
    }
    $PK_ENROLLMENT_MASTER = $_GET['id'];
    $PK_USER_MASTER = $res->fields['PK_USER_MASTER'];
    $ENROLLMENT_NAME = $res->fields['ENROLLMENT_NAME'];
    $ENROLLMENT_DATE = date('m/d/Y', strtotime($res->fields['ENROLLMENT_DATE']));
    $PK_ENROLLMENT_TYPE = $res->fields['PK_ENROLLMENT_TYPE'];
    $PK_LOCATION = $res->fields['PK_LOCATION'];
    $PK_PACKAGE = $res->fields['PK_PACKAGE'];
    $CHARGE_TYPE = $res->fields['CHARGE_TYPE'];
    $EXPIRY_DATE = new DateTime($res->fields['EXPIRY_DATE']);
    $PK_AGREEMENT_TYPE = $res->fields['PK_AGREEMENT_TYPE'];
    $PK_DOCUMENT_LIBRARY = is_null($res->fields['PK_DOCUMENT_LIBRARY']) ? 1 : $res->fields['PK_DOCUMENT_LIBRARY'];
    $AGREEMENT_PDF_LINK = $res->fields['AGREEMENT_PDF_LINK'];
    $ENROLLMENT_BY_ID = $res->fields['ENROLLMENT_BY_ID'];
    $ENROLLMENT_BY_PERCENTAGE = $res->fields['ENROLLMENT_BY_PERCENTAGE'];
    $MEMO = $res->fields['MEMO'];
    $ACTIVE = $res->fields['ACTIVE'];
    $ACTIVE_AUTO_PAY = $res->fields['ACTIVE_AUTO_PAY'];

    $CREATED_ON = new DateTime($res->fields['CREATED_ON']);
    $interval = $EXPIRY_DATE->diff($CREATED_ON);
    $months = intval($interval->days / 30);

    $day = $EXPIRY_DATE->format('d');

    $billing_data = $db_account->Execute("SELECT * FROM `DOA_ENROLLMENT_BILLING` WHERE `PK_ENROLLMENT_MASTER` = '$_GET[id]'");
    if ($billing_data->RecordCount() > 0) {
        $PK_ENROLLMENT_BILLING = $billing_data->fields['PK_ENROLLMENT_BILLING'];
        $BILLING_REF = $billing_data->fields['BILLING_REF'];
        $BILLING_DATE = $billing_data->fields['BILLING_DATE'];
        $DOWN_PAYMENT = $billing_data->fields['DOWN_PAYMENT'];
        $BALANCE_PAYABLE = $billing_data->fields['BALANCE_PAYABLE'];
        $PAYMENT_METHOD = $billing_data->fields['PAYMENT_METHOD'];
        $PAYMENT_TERM = $billing_data->fields['PAYMENT_TERM'];
        $NUMBER_OF_PAYMENT = $billing_data->fields['NUMBER_OF_PAYMENT'];
        $FIRST_DUE_DATE = $billing_data->fields['FIRST_DUE_DATE'];
        $INSTALLMENT_AMOUNT = $billing_data->fields['INSTALLMENT_AMOUNT'];
    }

    $payment_data = $db_account->Execute("SELECT * FROM `DOA_ENROLLMENT_PAYMENT` WHERE `PK_ENROLLMENT_MASTER` = '$_GET[id]'");
    if ($payment_data->RecordCount() > 0) {
        $PK_ENROLLMENT_PAYMENT = $payment_data->fields['PK_ENROLLMENT_PAYMENT'];
        $PK_PAYMENT_TYPE = $payment_data->fields['PK_PAYMENT_TYPE'];
        $AMOUNT = $payment_data->fields['AMOUNT'];
        $NOTE = $payment_data->fields['NOTE'];
    }
}

$user_payment_gateway = $db->Execute("SELECT DOA_USER_MASTER.PK_USER_MASTER, DOA_LOCATION.PAYMENT_GATEWAY_TYPE, DOA_LOCATION.SECRET_KEY, DOA_LOCATION.PUBLISHABLE_KEY, DOA_LOCATION.ACCESS_TOKEN, DOA_LOCATION.APP_ID, DOA_LOCATION.LOCATION_ID, DOA_LOCATION.LOGIN_ID, DOA_LOCATION.TRANSACTION_KEY, DOA_LOCATION.AUTHORIZE_CLIENT_KEY FROM DOA_LOCATION INNER JOIN DOA_USER_MASTER ON DOA_LOCATION.PK_LOCATION = DOA_USER_MASTER.PRIMARY_LOCATION_ID WHERE DOA_USER_MASTER.PK_USER_MASTER = '$PK_USER_MASTER'");
if ($user_payment_gateway->RecordCount() > 0) {
    $PAYMENT_GATEWAY = $user_payment_gateway->fields['PAYMENT_GATEWAY_TYPE'];
    $SQUARE_APP_ID = $user_payment_gateway->fields['APP_ID'];
    $SQUARE_LOCATION_ID = $user_payment_gateway->fields['LOCATION_ID'];
    $ACCESS_TOKEN = $user_payment_gateway->fields['ACCESS_TOKEN'];
    $PUBLISHABLE_KEY = $user_payment_gateway->fields['PUBLISHABLE_KEY'];
    $SECRET_KEY = $user_payment_gateway->fields['SECRET_KEY'];
    $LOGIN_ID = $user_payment_gateway->fields['LOGIN_ID'];
    $TRANSACTION_KEY = $user_payment_gateway->fields['TRANSACTION_KEY'];
    $AUTHORIZE_CLIENT_KEY = $user_payment_gateway->fields['AUTHORIZE_CLIENT_KEY'];
} else {
    $account_data = $db->Execute("SELECT * FROM `DOA_ACCOUNT_MASTER` WHERE `PK_ACCOUNT_MASTER` = '$_SESSION[PK_ACCOUNT_MASTER]'");
    $PAYMENT_GATEWAY = $account_data->fields['PAYMENT_GATEWAY_TYPE'];
    $SQUARE_APP_ID             = $account_data->fields['APP_ID'];
    $SQUARE_LOCATION_ID     = $account_data->fields['LOCATION_ID'];
    $ACCESS_TOKEN             = $account_data->fields['ACCESS_TOKEN'];
    $PUBLISHABLE_KEY = $account_data->fields['PUBLISHABLE_KEY'];
    $SECRET_KEY = $account_data->fields['SECRET_KEY'];
    $LOGIN_ID = $account_data->fields['LOGIN_ID'];
    $TRANSACTION_KEY = $account_data->fields['TRANSACTION_KEY'];
    $AUTHORIZE_CLIENT_KEY = $account_data->fields['AUTHORIZE_CLIENT_KEY'];
}

$payment_gateway_data = getPaymentGatewayData();

$PAYMENT_GATEWAY = $payment_gateway_data->fields['PAYMENT_GATEWAY_TYPE'];
$GATEWAY_MODE  = $payment_gateway_data->fields['GATEWAY_MODE'];

$SECRET_KEY = $payment_gateway_data->fields['SECRET_KEY'];
$PUBLISHABLE_KEY = $payment_gateway_data->fields['PUBLISHABLE_KEY'];

$SQUARE_ACCESS_TOKEN = $payment_gateway_data->fields['ACCESS_TOKEN'];
$SQUARE_APP_ID = $payment_gateway_data->fields['APP_ID'];
$SQUARE_LOCATION_ID = $payment_gateway_data->fields['LOCATION_ID'];

$AUTHORIZE_LOGIN_ID         = $payment_gateway_data->fields['LOGIN_ID']; //"4Y5pCy8Qr";
$AUTHORIZE_TRANSACTION_KEY     = $payment_gateway_data->fields['TRANSACTION_KEY']; //"4ke43FW8z3287HV5";
$AUTHORIZE_CLIENT_KEY         = $payment_gateway_data->fields['AUTHORIZE_CLIENT_KEY']; //"8ZkyJnT87uFztUz56B4PfgCe7yffEZA4TR5dv8ALjqk5u9mr6d8Nmt8KHyp8s9Ay";

$MERCHANT_ID            = $payment_gateway_data->fields['MERCHANT_ID'];
$API_KEY                = $payment_gateway_data->fields['API_KEY'];
$PUBLIC_API_KEY         = $payment_gateway_data->fields['PUBLIC_API_KEY'];

?>

<!-- New Enrollment -->
<div class="overlay4"></div>
<div class="side-drawer" id="sideDrawer4">
    <div class="drawer-header text-end border-bottom px-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Create New Enrollment</h6>
        <span class="close-btn" id="closeDrawer4">&times;</span>
    </div>
    <div class="drawer-body p-3" style="overflow-y: auto; height: calc(100% - 100px);">
        <form class="mb-0" id="enrollmentForm">
            <input type="hidden" name="FUNCTION_NAME" value="saveEnrollmentData">
            <input type="hidden" name="PK_ENROLLMENT_MASTER" class="PK_ENROLLMENT_MASTER" value="<?= (empty($_GET['id'])) ? '' : $_GET['id'] ?>">
            <div class="row mb-2 align-items-center">
                <div class="col-5 col-md-5">
                    <div class="d-flex gap-2 align-items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 32 32" viewBox="0 0 32 32" width="24px" height="24px" fill="#ccc">
                            <path d="m14.545 16.872c3.665 0 6.647-2.982 6.647-6.647s-2.982-6.647-6.647-6.647-6.647 2.982-6.647 6.647 2.982 6.647 6.647 6.647zm0-11.294c2.563 0 4.647 2.084 4.647 4.647s-2.084 4.647-4.647 4.647-4.647-2.084-4.647-4.647 2.085-4.647 4.647-4.647z" />
                            <path d="m3.15 28.387c.089.024.178.036.266.036.439 0 .841-.292.964-.735 1.253-4.555 5.434-7.736 10.166-7.736 2.11 0 4.146.623 5.888 1.8.458.308 1.079.189 1.389-.269.309-.458.189-1.079-.269-1.389-2.074-1.402-4.497-2.143-7.008-2.143-5.629 0-10.602 3.785-12.094 9.205-.147.533.166 1.084.698 1.231z" />
                            <path d="m22.766 25.513h1.909v1.909c0 .552.448 1 1 1s1-.448 1-1v-1.909h1.909c.552 0 1-.448 1-1s-.448-1-1-1h-1.909v-1.909c0-.552-.448-1-1-1s-1 .448-1 1v1.909h-1.909c-.552 0-1 .448-1 1s.448 1 1 1z" />
                        </svg>
                        <label class="mb-0">Customer</label>
                    </div>
                </div>
                <div class="col-7 col-md-7">
                    <div class="form-group">
                        <select class="form-control form-select" required name="PK_USER_MASTER" id="PK_USER_MASTER" onchange="selectThisCustomer(this);">
                            <option>Select Customer</option>
                            <?php
                            $row = $db->Execute("SELECT DISTINCT DOA_USERS.PK_USER, CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.PHONE, DOA_USERS.ACTIVE, DOA_USER_MASTER.PK_USER_MASTER, DOA_USER_MASTER.PRIMARY_LOCATION_ID FROM DOA_USERS INNER JOIN DOA_USER_MASTER ON DOA_USERS.PK_USER = DOA_USER_MASTER.PK_USER LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER LEFT JOIN DOA_USER_LOCATION ON DOA_USER_LOCATION.PK_USER = DOA_USERS.PK_USER WHERE (DOA_USER_LOCATION.PK_LOCATION IN (" . $DEFAULT_LOCATION_ID . ") OR DOA_USER_MASTER.PRIMARY_LOCATION_ID IN (" . $DEFAULT_LOCATION_ID . ")) AND DOA_USER_ROLES.PK_ROLES = 4 AND DOA_USERS.ACTIVE = 1 AND DOA_USERS.IS_DELETED = 0 AND DOA_USER_MASTER.PK_ACCOUNT_MASTER = " . $_SESSION['PK_ACCOUNT_MASTER'] . " ORDER BY DOA_USERS.FIRST_NAME ASC");
                            while (!$row->EOF) { ?>
                                <option value="<?php echo $row->fields['PK_USER_MASTER']; ?>" data-customer_id="<?= $row->fields['PK_USER_MASTER'] ?>" data-pk_user="<?= $row->fields['PK_USER'] ?>" data-location_id="<?= ((empty($_GET['id'])) ? $row->fields['PRIMARY_LOCATION_ID'] : $PK_LOCATION) ?>" data-customer_name="<?= $row->fields['NAME'] ?>" <?= ($PK_USER_MASTER == $row->fields['PK_USER_MASTER']) ? 'selected' : '' ?>><?= $row->fields['NAME'] . ' (' . $row->fields['USER_NAME'] . ')' . ' (' . $row->fields['PHONE'] . ')' . ' (' . $row->fields['EMAIL_ID'] . ')' ?></option>
                            <?php $row->MoveNext();
                            } ?>
                        </select>
                    </div>
                </div>
            </div>
            <hr class="mb-3">
            <div class="row mb-2 align-items-center">
                <div class="col-5 col-md-5">
                    <div class="d-flex gap-2 align-items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" id="Line_copy" viewBox="0 0 256 256" width="24px" height="24px" fill="#ccc">
                            <path d="m195.6347 214.5626c-11.3591.7708-14.3591-7.7292-16.4248-11.7246-1.3408-2.8574-40.3667-96.8164-60.8149-146.1a3 3 0 0 0 -2.771-1.8506h-11.1817a3.0007 3.0007 0 0 0 -2.7339 1.7646l-66.5484 147.238c-1.8423 3.2354-3.3423 9.11-9.5278 9.3135-1.9761.2344-8.44 1.3594-8.44 1.3594a3 3 0 0 0 -3 3v8.9658a3 3 0 0 0 3 3h50.24a3 3 0 0 0 3-3v-9.832a2.9989 2.9989 0 0 0 -2.6455-2.9785c-1.8218-.2168-3.625-.44-5.3882-.6807-3.2568-.4375-3.9121-1.5664-4.0752-2.42 1.1416-3.0869 14.4219-34.1689 15.1626-35.9229h61.8613l13.373 32.2891c.0259.0635 1.5146 3.1858.556 4.2666-1.63 1.8375-6.1216 2.4016-7.7449 2.6611a50.6 50.6 0 0 1 -5.1528.541 3 3 0 0 0 -2.8271 2.9951v9.0811a3 3 0 0 0 3 3h59.7734a3 3 0 0 0 2.9976-2.8848l.3442-8.9658c.109-2.178-1.9691-3.3248-4.0319-3.1154zm-2.1978 8.9658h-53.8862v-3.31c4.0583-.7187 15.3916-1.3854 15.9463-8.793a11.7331 11.7331 0 0 0 -1.2715-6.8281l-14.103-34.0508a3 3 0 0 0 -2.7715-1.8525h-65.8691a3.0006 3.0006 0 0 0 -2.8091 1.9473c-.2061.55-16.4009 37.2471-16.4009 39.6777.0454 2.8057 1.0454 8.3057 12.16 9.0332v4.1758h-44.24v-3.3613c6.001-1.292 12.376-.542 16.4717-6.668a40.798 40.798 0 0 0 3.9546-7.1172l65.7601-145.4937h7.2422c7.24 17.4482 58.5117 140.9912 60.1567 144.4971 3.665 7.4485 7.3317 14.4485 19.7759 15.1182z" />
                            <path d="m107.7045 91.5695a3 3 0 0 0 -2.7573-1.85 2.9415 2.9415 0 0 0 -2.7734 1.8252l-28.6245 67.25a3 3 0 0 0 2.76 4.1748h56.5581a3 3 0 0 0 2.7705-4.15zm-26.8574 65.4 24.0529-56.5104 23.473 56.5109z" />
                            <path d="m233.0087 223.2169h-9.8213v-162.3291h9.8213a3 3 0 0 0 0-6h-25.6426a3 3 0 1 0 0 6h9.8213v162.3291h-9.8213a3 3 0 0 0 0 6h25.6426a3 3 0 0 0 0-6z" />
                            <path d="m17.1913 55.23a3 3 0 0 0 3-3v-9.8217h173.1358v9.8217a3 3 0 1 0 6 0v-25.642a3 3 0 0 0 -6 0v9.82h-173.1358v-9.82a3 3 0 1 0 -6 0v25.642a3 3 0 0 0 3 3z" />
                        </svg>
                        <label class="mb-0">Enrollment Name</label>
                    </div>
                </div>
                <div class="col-7 col-md-7">
                    <div class="form-group">
                        <input type="text" id="ENROLLMENT_NAME" name="ENROLLMENT_NAME" class="form-control" placeholder="Enter Enrollment Name" value="<?= $ENROLLMENT_NAME ?>">
                    </div>
                </div>
            </div>
            <div class="row mb-3 align-items-center">
                <div class="col-5 col-md-5">
                    <div class="d-flex gap-2 align-items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" id="Line_copy" viewBox="0 0 256 256" width="24px" height="24px" fill="transparent">
                            <path d="m195.6347 214.5626c-11.3591.7708-14.3591-7.7292-16.4248-11.7246-1.3408-2.8574-40.3667-96.8164-60.8149-146.1a3 3 0 0 0 -2.771-1.8506h-11.1817a3.0007 3.0007 0 0 0 -2.7339 1.7646l-66.5484 147.238c-1.8423 3.2354-3.3423 9.11-9.5278 9.3135-1.9761.2344-8.44 1.3594-8.44 1.3594a3 3 0 0 0 -3 3v8.9658a3 3 0 0 0 3 3h50.24a3 3 0 0 0 3-3v-9.832a2.9989 2.9989 0 0 0 -2.6455-2.9785c-1.8218-.2168-3.625-.44-5.3882-.6807-3.2568-.4375-3.9121-1.5664-4.0752-2.42 1.1416-3.0869 14.4219-34.1689 15.1626-35.9229h61.8613l13.373 32.2891c.0259.0635 1.5146 3.1858.556 4.2666-1.63 1.8375-6.1216 2.4016-7.7449 2.6611a50.6 50.6 0 0 1 -5.1528.541 3 3 0 0 0 -2.8271 2.9951v9.0811a3 3 0 0 0 3 3h59.7734a3 3 0 0 0 2.9976-2.8848l.3442-8.9658c.109-2.178-1.9691-3.3248-4.0319-3.1154zm-2.1978 8.9658h-53.8862v-3.31c4.0583-.7187 15.3916-1.3854 15.9463-8.793a11.7331 11.7331 0 0 0 -1.2715-6.8281l-14.103-34.0508a3 3 0 0 0 -2.7715-1.8525h-65.8691a3.0006 3.0006 0 0 0 -2.8091 1.9473c-.2061.55-16.4009 37.2471-16.4009 39.6777.0454 2.8057 1.0454 8.3057 12.16 9.0332v4.1758h-44.24v-3.3613c6.001-1.292 12.376-.542 16.4717-6.668a40.798 40.798 0 0 0 3.9546-7.1172l65.7601-145.4937h7.2422c7.24 17.4482 58.5117 140.9912 60.1567 144.4971 3.665 7.4485 7.3317 14.4485 19.7759 15.1182z" />
                            <path d="m107.7045 91.5695a3 3 0 0 0 -2.7573-1.85 2.9415 2.9415 0 0 0 -2.7734 1.8252l-28.6245 67.25a3 3 0 0 0 2.76 4.1748h56.5581a3 3 0 0 0 2.7705-4.15zm-26.8574 65.4 24.0529-56.5104 23.473 56.5109z" />
                            <path d="m233.0087 223.2169h-9.8213v-162.3291h9.8213a3 3 0 0 0 0-6h-25.6426a3 3 0 1 0 0 6h9.8213v162.3291h-9.8213a3 3 0 0 0 0 6h25.6426a3 3 0 0 0 0-6z" />
                            <path d="m17.1913 55.23a3 3 0 0 0 3-3v-9.8217h173.1358v9.8217a3 3 0 1 0 6 0v-25.642a3 3 0 0 0 -6 0v9.82h-173.1358v-9.82a3 3 0 1 0 -6 0v25.642a3 3 0 0 0 3 3z" />
                        </svg>
                        <label class="mb-0">Enrollment Type</label>
                    </div>
                </div>
                <div class="col-7 col-md-7">
                    <div class="form-group">
                        <select class="form-control form-select customerselect" name="PK_ENROLLMENT_TYPE" id="PK_ENROLLMENT_TYPE">
                            <option value="">Select Enrollment Type</option>
                            <option value="5" <?= ($PK_ENROLLMENT_TYPE == 5) ? 'selected' : '' ?>>PORI</option>
                            <option value="2" <?= ($PK_ENROLLMENT_TYPE == 2) ? 'selected' : '' ?>>ORI</option>
                            <option value="9" <?= ($PK_ENROLLMENT_TYPE == 9) ? 'selected' : '' ?>>EXT</option>
                            <option value="13" <?= ($PK_ENROLLMENT_TYPE == 13) ? 'selected' : '' ?>>REN</option>
                        </select>
                    </div>
                </div>
            </div>
            <hr class="mb-3">
            <div class="row mb-2">
                <div class="col-5 col-md-5">
                    <div class="d-flex gap-2 align-items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="19px" viewBox="0 0 25 25" fill="#ccc">
                            <path d="M11.3829 0L23.2617 1.698L24.9585 13.578L13.9281 24.6084C13.7031 24.8334 13.3979 24.9597 13.0797 24.9597C12.7615 24.9597 12.4564 24.8334 12.2313 24.6084L0.351344 12.7284C0.126379 12.5034 0 12.1982 0 11.88C0 11.5618 0.126379 11.2566 0.351344 11.0316L11.3829 0ZM12.2313 2.5464L2.89654 11.88L13.0797 22.062L22.4133 12.7284L21.1413 3.8184L12.2313 2.5464ZM14.7753 10.1832C14.3252 9.73286 14.0723 9.12214 14.0724 8.48538C14.0725 8.17008 14.1346 7.85789 14.2554 7.56662C14.3761 7.27535 14.553 7.01071 14.7759 6.7878C14.9989 6.56489 15.2636 6.38809 15.5549 6.26749C15.8463 6.14688 16.1585 6.08483 16.4738 6.08489C17.1105 6.085 17.7212 6.33806 18.1713 6.7884C18.6215 7.23874 18.8744 7.84946 18.8743 8.48623C18.8741 9.12299 18.6211 9.73362 18.1707 10.1838C17.7204 10.634 17.1097 10.8868 16.4729 10.8867C15.8362 10.8866 15.2255 10.6335 14.7753 10.1832Z" />
                        </svg>
                        <label class="mb-0">Packages</label>
                    </div>
                </div>
                <div class="col-7 col-md-7">
                    <div class="form-group mb-2">
                        <select class="form-control form-select PK_PACKAGE" name="PK_PACKAGE" id="PK_PACKAGE" onchange="selectThisPackage(this)">
                            <option value="">Select Package</option>
                            <?php
                            $row = $db_account->Execute("SELECT * FROM DOA_PACKAGE WHERE PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND ACTIVE = 1 AND IS_DELETED = 0 ORDER BY SORT_ORDER ASC");
                            while (!$row->EOF) { ?>
                                <option value="<?php echo $row->fields['PK_PACKAGE']; ?>" data-expiry_date="<?= $row->fields['EXPIRY_DATE'] ?>" <?= ($row->fields['PK_PACKAGE'] == $PK_PACKAGE) ? 'selected' : '' ?>><?= $row->fields['PACKAGE_NAME'] ?></option>
                            <?php $row->MoveNext();
                            } ?>
                        </select>
                    </div>
                    <div class="datetime-area f12 bg-light p-2 border rounded-2 mb-2" style="display: none;">
                        <div class="individual_service_div">
                            <div class="datetime-item d-flex ">
                                <div class="align-self-center">
                                    <p class="text-dark fw-semibold mb-0">Private Service <span class="badge border ms-auto" style="background-color: #ebf2ff; color: #6b82e2;">PRI</span></p>
                                    <span class="f10">Total: $90.00</span>
                                </div>
                                <div class="d-flex gap-2 ms-auto align-items-start">
                                    <button type="button" class="bg-white theme-text-light border-0 rounded-circle avatar-sm">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="-85 -19 617 617.33331" width="14px" height="14px" fill="#212529">
                                            <path d="m219.121094 319.375c-6.894532.019531-12.480469 5.605469-12.5 12.5v152.5c0 6.90625 5.601562 12.5 12.5 12.5 6.902344 0 12.5-5.59375 12.5-12.5v-152.5c-.019532-6.894531-5.601563-12.480469-12.5-12.5zm0 0"></path>
                                            <path d="m299.121094 319.375c-6.894532.019531-12.480469 5.605469-12.5 12.5v152.5c0 6.90625 5.601562 12.5 12.5 12.5 6.902344 0 12.5-5.59375 12.5-12.5v-152.5c-.019532-6.894531-5.601563-12.480469-12.5-12.5zm0 0"></path>
                                            <path d="m139.121094 319.375c-6.894532.019531-12.480469 5.605469-12.5 12.5v152.5c0 6.90625 5.601562 12.5 12.5 12.5 6.902344 0 12.5-5.59375 12.5-12.5v-152.5c-.019532-6.894531-5.601563-12.480469-12.5-12.5zm0 0"></path>
                                            <path d="m386.121094 64h-71.496094v-36.375c-.007812-15.257812-12.375-27.62109375-27.628906-27.625h-135.746094c-15.257812.00390625-27.621094 12.367188-27.628906 27.625v36.5h-71.496094c-27.515625.007812-51.003906 19.863281-55.582031 46.992188-4.582031 27.128906 11.09375 53.601562 37.078125 62.632812-.246094.894531-.371094 1.820312-.375 2.75v339.75c.015625 34.511719 27.988281 62.484375 62.5 62.5h246.875c34.511718-.015625 62.492187-27.988281 62.5-62.5v-339.75c.011718-.929688-.117188-1.855469-.375-2.75 26.019531-9.0625 41.6875-35.585938 37.078125-62.75s-28.152344-47.023438-55.703125-47zm-237.371094-36.375c.003906-1.449219 1.175781-2.617188 2.621094-2.625h135.753906c1.445312.007812 2.617188 1.175781 2.621094 2.625v36.5h-140.996094zm193.75 526.125h-246.753906c-20.683594-.058594-37.4375-16.816406-37.5-37.5v-339.375h321.875v339.375c-.117188 20.707031-16.914063 37.453125-37.621094 37.5zm43.621094-401.875h-333.996094c-17.332031 0-31.378906-14.046875-31.378906-31.375s14.046875-31.375 31.378906-31.375h333.996094c17.332031 0 31.378906 14.046875 31.378906 31.375s-14.046875 31.375-31.378906 31.375zm0 0"></path>
                                        </svg>
                                    </button>
                                    <button type="button" class="bg-white theme-text-light border-0 rounded-circle avatar-sm btncollapse" data-bs-toggle="collapse" href="#package1">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 28444 28444" width="14px" height="14px" fill="#212529">
                                            <path d="m26891 9213-12669 12669-12669-12669 1768-1767 10901 10901 10902-10901z" fill-rule="nonzero"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div class="collapse" id="package1">
                                <!-- Sessions -->
                                <div class="d-inline-flex gap-1">
                                    <div class="session-item">
                                        <label class="small text-muted">No. of sessions</label>
                                        <input type="number" class="form-control form-control-sm text-center" value="1">
                                    </div>
                                    <div class="session-item">
                                        <label class="small text-muted">Price / session</label>
                                        <div class="session-item position-relative">
                                            <input type="text" class="form-control form-control-sm" value="100.00" style="padding-left: 20px;">
                                            <span class="position-absolute" style="top: 7px; left: 10px;">$</span>
                                        </div>
                                    </div>

                                    <div class="session-item" style="min-width: 45px;">
                                        <label class="small text-muted">Total</label>
                                        <div class="f10 pt-2">$ 100.00</div>
                                    </div>
                                </div>
                                <hr class="my-2">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="f12 text-muted">Discount</label>
                                    <div class="form-check form-switch p-0 mb-0" style="min-height: auto;">
                                        <input class="form-check-input" type="checkbox" checked>
                                    </div>
                                </div>
                                <div class="d-inline-flex gap-1">
                                    <div class="session-item">
                                        <label class="small text-muted">Type</label>
                                        <select class="form-select form-select-sm" style="min-width: 90px;">
                                            <option>Percent</option>
                                            <option>Flat</option>
                                        </select>
                                    </div>
                                    <div class="session-item">
                                        <label class="small text-muted">Value</label>
                                        <div class="session-item position-relative">
                                            <input type="text" class="form-control form-control-sm" value="10" style="padding-left: 20px;">
                                            <span class="position-absolute" style="top: 7px; left: 10px;">$</span>
                                        </div>
                                    </div>
                                    <div class="session-item" style="min-width: 45px;">
                                        <label class="small text-muted">Total</label>
                                        <div class="f10 pt-2">$ 90.00</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="append_service_div">

                    </div>

                    <div class="totalamount p-2 border rounded-2 d-inline-flex align-items-center f12 justify-content-between w-100" <?= ($PK_ENROLLMENT_MASTER > 0) ? 'disabled_div' : '' ?>">
                        <span>Total Amount</span>
                        <span class="fw-semibold text-dark TOTAL_AMOUNT" value="<?= number_format((float)$total, 2, '.', ''); ?>" readonly></span>
                    </div>

                    <button type="button" class="btn-secondary w-100 f12 my-2 addpackage">Add More Service</button>
                    <?php
                    $payment_gateway_type = $db->Execute("SELECT PAYMENT_GATEWAY_TYPE FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER=" . $_SESSION['PK_ACCOUNT_MASTER']);
                    if ($payment_gateway_type->RecordCount() > 0) { ?>
                        <div class="d-flex gap-3 mt-1 <?= ($PK_ENROLLMENT_MASTER > 0) ? 'disabled_div' : '' ?>"">
                        <label class=" radio" for="Session">
                            <input type="radio" id="Session" name="CHARGE_TYPE" value="Session" <?= ($CHARGE_TYPE == 'Session') ? 'checked' : '' ?> onchange="chargeBySessions(this);">
                            <span></span>
                            Charge by sessions
                            </label>
                            <label class="radio" for="Membership">
                                <input type="radio" id="Membership" name="CHARGE_TYPE" class="charge_type" value="Membership" <?= ($CHARGE_TYPE == 'Membership') ? 'checked' : '' ?> onchange="chargeByMembership(this);">
                                <span></span>
                                Membership
                            </label>
                        </div>
                    <?php } ?>
                </div>
            </div>
            <hr class="mb-3">
            <div class="row mb-3 align-items-center">
                <div class="col-5 col-md-5">
                    <div class="d-flex gap-2 align-items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Capa_1" x="0px" y="0px" viewBox="0 0 55.668 55.668" xml:space="preserve" width="24px" height="19px" fill="#ccc">
                            <path d="M27.833,0C12.487,0,0,12.486,0,27.834s12.487,27.834,27.833,27.834 c15.349,0,27.834-12.486,27.834-27.834S43.182,0,27.833,0z M27.833,51.957c-13.301,0-24.122-10.821-24.122-24.123 S14.533,3.711,27.833,3.711c13.303,0,24.123,10.821,24.123,24.123S41.137,51.957,27.833,51.957z" />
                            <path d="M41.618,25.819H29.689V10.046c0-1.025-0.831-1.856-1.855-1.856c-1.023,0-1.854,0.832-1.854,1.856 v19.483h15.638c1.024,0,1.855-0.83,1.854-1.855C43.472,26.65,42.64,25.819,41.618,25.819z" />
                        </svg>
                        <label class="mb-0">Start Date & Expiration</label>
                    </div>
                </div>
                <div class="col-7 col-md-7">
                    <div class="form-group d-flex gap-2 align-items-center" id="datetime">
                        <input type="date" class="form-control" style="min-width: 110px;" id="ENROLLMENT_DATE" name="ENROLLMENT_DATE" value="<?= $ENROLLMENT_DATE ?>" required>
                        <select class="form-control form-select" name="EXPIRY_DATE" id="EXPIRY_DATE">
                            <option value="" selected disabled>-- Expire In --</option>
                            <option value="1" data-expiry_date="30" <?= ($months == 1) ? 'selected' : '' ?>>30 days</option>
                            <option value="2" data-expiry_date="60" <?= ($months == 2) ? 'selected' : '' ?>>60 days</option>
                            <option value="3" data-expiry_date="90" <?= ($months == 3) ? 'selected' : '' ?>>90 days</option>
                            <option value="6" data-expiry_date="180" <?= ($months == 6) ? 'selected' : '' ?>>180 days</option>
                            <option value="12" data-expiry_date="365" <?= ($months == 12) ? 'selected' : '' ?>>365 days</option>
                        </select>
                    </div>
                </div>
            </div>
            <hr class="mb-3">
            <div class="row mb-3 align-items-center">
                <div class="col-5 col-md-5">
                    <div class="d-flex gap-2 align-items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Capa_1" x="0px" y="0px" viewBox="0 0 55.668 55.668" xml:space="preserve" width="24px" height="19px" fill="#ccc">
                            <path d="M27.833,0C12.487,0,0,12.486,0,27.834s12.487,27.834,27.833,27.834 c15.349,0,27.834-12.486,27.834-27.834S43.182,0,27.833,0z M27.833,51.957c-13.301,0-24.122-10.821-24.122-24.123 S14.533,3.711,27.833,3.711c13.303,0,24.123,10.821,24.123,24.123S41.137,51.957,27.833,51.957z" />
                            <path d="M41.618,25.819H29.689V10.046c0-1.025-0.831-1.856-1.855-1.856c-1.023,0-1.854,0.832-1.854,1.856 v19.483h15.638c1.024,0,1.855-0.83,1.854-1.855C43.472,26.65,42.64,25.819,41.618,25.819z" />
                        </svg>
                        <label class="mb-0">Agreement</label>
                    </div>
                </div>
                <div class="col-7 col-md-7">
                    <div class="form-group">
                        <select class="form-control form-select" required name="PK_DOCUMENT_LIBRARY" id="PK_DOCUMENT_LIBRARY">
                            <option value="" selected disabled>-- Select --</option>
                            <?php
                            $row = $db_account->Execute("SELECT PK_DOCUMENT_LIBRARY, DOCUMENT_NAME FROM DOA_DOCUMENT_LIBRARY WHERE ACTIVE = 1 ORDER BY PK_DOCUMENT_LIBRARY");
                            while (!$row->EOF) { ?>
                                <option value="<?php echo $row->fields['PK_DOCUMENT_LIBRARY']; ?>" <?= ($PK_DOCUMENT_LIBRARY == $row->fields['PK_DOCUMENT_LIBRARY']) ? 'selected' : '' ?>><?= $row->fields['DOCUMENT_NAME'] ?></option>
                            <?php $row->MoveNext();
                            } ?>
                        </select>
                    </div>
                </div>
            </div>
            <hr class="mb-3">
            <div class="row mb-3">
                <div class="col-5 col-md-5">
                    <div class="d-flex gap-2 align-items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="19px" viewBox="0 0 24 24" fill="#ccc">
                            <path d="M20.4 24H3.6C2.64522 24 1.72955 23.6207 1.05442 22.9456C0.379285 22.2705 0 21.3548 0 20.4V1.2C0 0.88174 0.126428 0.576515 0.351472 0.351472C0.576515 0.126428 0.88174 0 1.2 0H18C18.3183 0 18.6235 0.126428 18.8485 0.351472C19.0736 0.576515 19.2 0.88174 19.2 1.2V15.6H24V20.4C24 21.3548 23.6207 22.2705 22.9456 22.9456C22.2705 23.6207 21.3548 24 20.4 24ZM19.2 18V20.4C19.2 20.7183 19.3264 21.0235 19.5515 21.2485C19.7765 21.4736 20.0817 21.6 20.4 21.6C20.7183 21.6 21.0235 21.4736 21.2485 21.2485C21.4736 21.0235 21.6 20.7183 21.6 20.4V18H19.2ZM16.8 21.6V2.4H2.4V20.4C2.4 20.7183 2.52643 21.0235 2.75147 21.2485C2.97652 21.4736 3.28174 21.6 3.6 21.6H16.8ZM4.8 6H14.4V8.4H4.8V6ZM4.8 10.8H14.4V13.2H4.8V10.8ZM4.8 15.6H10.8V18H4.8V15.6Z" />
                        </svg>
                        <label class="mb-0">Enrollment By</label>
                    </div>
                </div>
                <div class="col-7 col-md-7">
                    <div class="form-group d-flex gap-2 align-items-center" id="salesby">
                        <select class="form-control form-select" required name="ENROLLMENT_BY_ID" id="ENROLLMENT_BY_ID">
                            <option value="" selected disabled>-- Select --</option>
                        </select>
                        <div class="position-relative">
                            <input type="text" style="max-width: 120px;" class="form-control ENROLLMENT_BY_PERCENTAGE" name="ENROLLMENT_BY_PERCENTAGE" placeholder="Enter %" value="<?= $ENROLLMENT_BY_PERCENTAGE ?>">
                        </div>
                    </div>
                </div>
            </div>
            <hr class="mb-3">
            <div class="row mb-3">
                <div class="col-5 col-md-5">
                    <div class="d-flex gap-2 align-items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="19px" viewBox="0 0 24 24" fill="#ccc">
                            <path d="M20.4 24H3.6C2.64522 24 1.72955 23.6207 1.05442 22.9456C0.379285 22.2705 0 21.3548 0 20.4V1.2C0 0.88174 0.126428 0.576515 0.351472 0.351472C0.576515 0.126428 0.88174 0 1.2 0H18C18.3183 0 18.6235 0.126428 18.8485 0.351472C19.0736 0.576515 19.2 0.88174 19.2 1.2V15.6H24V20.4C24 21.3548 23.6207 22.2705 22.9456 22.9456C22.2705 23.6207 21.3548 24 20.4 24ZM19.2 18V20.4C19.2 20.7183 19.3264 21.0235 19.5515 21.2485C19.7765 21.4736 20.0817 21.6 20.4 21.6C20.7183 21.6 21.0235 21.4736 21.2485 21.2485C21.4736 21.0235 21.6 20.7183 21.6 20.4V18H19.2ZM16.8 21.6V2.4H2.4V20.4C2.4 20.7183 2.52643 21.0235 2.75147 21.2485C2.97652 21.4736 3.28174 21.6 3.6 21.6H16.8ZM4.8 6H14.4V8.4H4.8V6ZM4.8 10.8H14.4V13.2H4.8V10.8ZM4.8 15.6H10.8V18H4.8V15.6Z" />
                        </svg>
                        <label class="mb-0"><?= $service_provider_title ?></label>
                    </div>
                </div>
                <div class="col-7 col-md-7">
                    <?php
                    if (!empty($_GET['id'])) {
                        $enrollment_service_provider_data = $db_account->Execute("SELECT * FROM DOA_ENROLLMENT_SERVICE_PROVIDER WHERE PK_ENROLLMENT_MASTER = '$_GET[id]'");
                        while (!$enrollment_service_provider_data->EOF) { ?>

                            <div class="form-group d-flex gap-2 align-items-center" id="salesby">
                                <select class="form-control form-select SERVICE_PROVIDER_ID" name="SERVICE_PROVIDER_ID[]" id="SERVICE_PROVIDER_ID" disabled>
                                    <option value="" selected disabled>-- Select --</option>
                                    <?php
                                    $row = $db->Execute("SELECT DISTINCT(DOA_USERS.PK_USER), CONCAT(FIRST_NAME, ' ', LAST_NAME) AS NAME FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER LEFT JOIN DOA_USER_LOCATION ON DOA_USERS.PK_USER = DOA_USER_LOCATION.PK_USER WHERE DOA_USER_ROLES.PK_ROLES = 5 AND DOA_USER_LOCATION.PK_LOCATION IN (" . $_SESSION['DEFAULT_LOCATION_ID'] . ") AND PK_ACCOUNT_MASTER = '$_SESSION[PK_ACCOUNT_MASTER]' AND ACTIVE = 1 ORDER BY FIRST_NAME");
                                    while (!$row->EOF) { ?>
                                        <option value="<?php echo $row->fields['PK_USER']; ?>" <?= ($row->fields['PK_USER'] == $enrollment_service_provider_data->fields['SERVICE_PROVIDER_ID']) ? 'selected' : '' ?>><?= $row->fields['NAME'] ?></option>
                                    <?php $row->MoveNext();
                                    } ?>
                                </select>
                                <div class="position-relative">
                                    <input type="text" class="form-control SERVICE_PROVIDER_PERCENTAGE" placeholder="Enter %" style="max-width: 120px;" name="SERVICE_PROVIDER_PERCENTAGE[]" value="<?= number_format((float)$enrollment_service_provider_data->fields['SERVICE_PROVIDER_PERCENTAGE'], 2, '.', '') ?>">
                                </div>
                            </div>

                        <?php $enrollment_service_provider_data->MoveNext();
                        } ?>
                    <?php } else { ?>

                        <div class="form-group d-flex gap-2 align-items-center" id="salesby">
                            <select class="form-control form-select SERVICE_PROVIDER_ID" name="SERVICE_PROVIDER_ID[]" id="SERVICE_PROVIDER_ID">
                                <option value="" selected disabled>-- Select --</option>
                            </select>
                            <div class="position-relative">
                                <input type="text" class="form-control SERVICE_PROVIDER_PERCENTAGE" placeholder="Enter %" style="max-width: 120px;" name="SERVICE_PROVIDER_PERCENTAGE[]">
                            </div>
                        </div>


                    <?php } ?>
                    <div id="append_service_provider_div">

                    </div>
                    <button type="button" class="btn-secondary w-100 f12 mt-2" onclick="addMoreServiceProviders();">Add Service Provider</button>
                </div>
            </div>
            <hr class="mb-3">
            <div class="row">
                <div class="col-5 col-md-5">
                    <div class="d-flex gap-2 align-items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Capa_1" x="0px" y="0px" viewBox="0 0 512 512" style="enable-background:new 0 0 512 512;" xml:space="preserve" width="24px" height="19px" fill="#ccc">
                            <path d="M487.104,24.954c-33.274-33.269-87.129-33.273-120.407,0L51.948,339.665c-2.098,2.097-3.834,4.825-4.831,7.817 L1.057,485.647c-5.2,15.598,9.679,30.503,25.298,25.296l138.182-46.055c2.922-0.974,5.665-2.678,7.819-4.831l314.748-314.711 C520.299,112.154,520.299,58.146,487.104,24.954z M51.654,460.352l23.177-69.525l46.356,46.35L51.654,460.352z M158.214,417.634 l-63.837-63.829l267.272-267.24l63.837,63.83L158.214,417.634z M458.818,117.065l-5.049,5.049l-63.837-63.83l5.049-5.048 c17.602-17.597,46.239-17.597,63.837,0C476.419,70.833,476.419,99.467,458.818,117.065z" />
                        </svg>
                        <label class="mb-0">Internal Note</label>
                    </div>
                </div>
                <div class="col-7 col-md-7">
                    <div class="form-group">
                        <textarea class="form-control" name="MEMO"><?= $MEMO ?></textarea>
                    </div>
                </div>
            </div>
        </form>
    </div>
    <div class="modal-footer flex-nowrap p-2 border-top">
        <button type="button" class="btn-secondary w-100 m-1">Cancel</button>
        <button id="openDrawer5" type="button" class="btn-primary w-100 m-1">Continue to Billing</button>
    </div>
</div>

<!--Confirm Model-->
<div class="modal fade" id="confirm_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="card">
                    <div class="card-body">
                        <div>
                            <input type="hidden" id="is_confirm" value="0">
                            <label>Are you sure you want to proceed without selecting <?= $service_provider_title ?> ?</label>
                            <button type="button" class="btn btn-info waves-effect waves-light m-l-20 text-white" onclick="$('#is_confirm').val(1); $('#enrollment_form').submit();">Yes</button>
                            <button type="button" class="btn btn-danger waves-effect waves-light m-l-10 text-white" data-bs-dismiss="modal" aria-label="No">No</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Billing -->
<div class="overlay5"></div>
<div class="side-drawer" id="sideDrawer5">
    <div class="drawer-header text-end border-bottom px-3 d-flex justify-content-between align-items-center">
        <h6>
            <svg xmlns="http://www.w3.org/2000/svg" id="Layer_1" enable-background="new 0 0 100 100" viewBox="0 0 100 100" width="16px" height="16px" fill="CurrentColor">
                <path d="m44.93 76.47c.49.49 1.13.73 1.77.73s1.28-.24 1.77-.73c.98-.98.98-2.56 0-3.54l-21.43-21.43h51.96c1.38 0 2.5-1.12 2.5-2.5s-1.12-2.5-2.5-2.5h-51.96l21.43-21.43c.98-.98.98-2.56 0-3.54s-2.56-.98-3.54 0l-25.7 25.7c-.98.98-.98 2.56 0 3.54z"></path>
            </svg>
            <span class="mb-0">Create New Enrollment</span>
        </h6>
        <span class="close-btn" id="closeDrawer5">&times;</span>
    </div>
    <div class="drawer-body p-3" style="overflow-y: auto; height: calc(100% - 100px);">
        <h5 class="mb-4 text-dark">Billing</h5>
        <div class="booking-lesson">
            <div class="form-check border rounded-2 p-2 mb-2">
                <div class="d-flex">
                    <span class="checkicon d-inline-flex me-2 align-items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12px" height="12px" viewBox="0 0 15 15" fill="#00922E">
                            <path d="M7.5 6.75C8.49456 6.75 9.44839 7.14509 10.1517 7.84835C10.8549 8.55161 11.25 9.50544 11.25 10.5V15H3.75V10.5C3.75 9.50544 4.14509 8.55161 4.84835 7.84835C5.55161 7.14509 6.50544 6.75 7.5 6.75ZM2.466 9.0045C2.34664 9.40709 2.27614 9.82257 2.256 10.242L2.25 10.5V15H6.84877e-08V11.625C-0.000147605 10.9782 0.238521 10.3541 0.670226 9.87241C1.10193 9.39074 1.69627 9.08541 2.33925 9.015L2.46675 9.0045H2.466ZM12.534 9.0045C13.2014 9.04518 13.8282 9.33897 14.2864 9.82593C14.7447 10.3129 14.9999 10.9563 15 11.625V15H12.75V10.5C12.75 9.98025 12.675 9.4785 12.534 9.0045ZM2.625 4.5C3.12228 4.5 3.59919 4.69754 3.95083 5.04917C4.30246 5.40081 4.5 5.87772 4.5 6.375C4.5 6.87228 4.30246 7.34919 3.95083 7.70083C3.59919 8.05246 3.12228 8.25 2.625 8.25C2.12772 8.25 1.65081 8.05246 1.29917 7.70083C0.947544 7.34919 0.75 6.87228 0.75 6.375C0.75 5.87772 0.947544 5.40081 1.29917 5.04917C1.65081 4.69754 2.12772 4.5 2.625 4.5V4.5ZM12.375 4.5C12.8723 4.5 13.3492 4.69754 13.7008 5.04917C14.0525 5.40081 14.25 5.87772 14.25 6.375C14.25 6.87228 14.0525 7.34919 13.7008 7.70083C13.3492 8.05246 12.8723 8.25 12.375 8.25C11.8777 8.25 11.4008 8.05246 11.0492 7.70083C10.6975 7.34919 10.5 6.87228 10.5 6.375C10.5 5.87772 10.6975 5.40081 11.0492 5.04917C11.4008 4.69754 11.8777 4.5 12.375 4.5V4.5ZM7.5 0C8.29565 0 9.05871 0.316071 9.62132 0.87868C10.1839 1.44129 10.5 2.20435 10.5 3C10.5 3.79565 10.1839 4.55871 9.62132 5.12132C9.05871 5.68393 8.29565 6 7.5 6C6.70435 6 5.94129 5.68393 5.37868 5.12132C4.81607 4.55871 4.5 3.79565 4.5 3C4.5 2.20435 4.81607 1.44129 5.37868 0.87868C5.94129 0.316071 6.70435 0 7.5 0V0Z" />
                        </svg>
                    </span>
                    <label class="form-check-label text-dark">
                        Enrollment Number
                        <span class="statusarea ms-2 fw-normal"><span>-3</span></span>
                    </label>
                    <button type="button" class="bg-white boxshadow-sm p-0 border-0 rounded-4 ms-auto avatar-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="-14 0 511 512" width="14px" height="14px" fill="CurrentColor">
                            <path d="m.5 481.992188h300.078125v30.007812h-300.078125zm0 0"></path>
                            <path d="m330.585938 481.992188h120.03125v30.007812h-120.03125zm0 0"></path>
                            <path d="m483.464844 84.882812-84.867188-84.8710932-.011718-.0117188c-5.875 5.882812-313.644532 314.078125-313.75 314.183594l-57.59375 142.460937 142.46875-57.597656s181.703124-181.636719 313.753906-314.164063zm-42.421875.011719-35.917969 35.964844-42.375-42.371094 35.875-36.011719zm-99.46875 14.851563 42.34375 42.347656-21.199219 21.226562-42.320312-42.320312zm-238.554688 249.523437 31.597657 31.597657-53.042969 21.441406zm58.226563 15.789063-42.429688-42.433594 180.265625-180.503906 42.433594 42.433594zm0 0"></path>
                        </svg>
                    </button>
                </div>
                <div class="border rounded-2 p-2 mt-2">
                    <div class="d-flex mb-0">
                        <label class="form-check-label text-dark">
                            Private Service
                            <span class="badge ms-auto rounded-1" style="background-color: #ebf2ff; color: #6b82e2;">PRI</span>
                        </label>
                        <span class="f12 text-dark ms-auto">$90.00</span>
                    </div>
                    <div class="statusarea m-0">
                        <span>Session: 1</span>
                        <span>Price/Session: $100.00</span>
                        <span>Discount: 10%</span>
                    </div>
                </div>
                <div class="border rounded-2 p-2 mt-2">
                    <div class="d-flex mb-0">
                        <label class="form-check-label text-dark">
                            Group Class
                            <span class="badge ms-auto rounded-1" style="background-color: #feebf4; color: #ed85b7;">GRP</span>
                        </label>
                        <span class="f12 text-dark ms-auto">$100.00</span>
                    </div>
                    <div class="statusarea m-0">
                        <span>Session: 1</span>
                        <span>Price/Session: $100.00</span>
                    </div>
                </div>
                <div class="border rounded-2 p-2 mt-2">
                    <div class="d-flex mb-0">
                        <label class="form-check-label text-dark">
                            Private Service
                            <span class="badge ms-auto rounded-1" style="background-color: #E4FBF8; color: #22D3BB;">PTY</span>
                        </label>
                        <span class="f12 text-dark ms-auto">$100.00</span>
                    </div>
                    <div class="statusarea m-0">
                        <span>Session: 1</span>
                        <span>Price/Session: $100.00</span>
                    </div>
                </div>
                <div class="totalamount p-2 border rounded-2 d-inline-flex align-items-center f12 justify-content-between w-100 mt-2">
                    <span>Total Amount</span>
                    <span class="fw-semibold text-dark">$290.00</span>
                </div>
            </div>
        </div>
        <hr class="my-3">
        <h5 class="mb-4 text-dark">Payment Plans</h5>
        <form class="mb-0 appointmentform">
            <div class="row mb-2 align-items-center">
                <div class="col-5 col-md-5">
                    <div class="d-flex gap-2 align-items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="19px" viewBox="0 0 20 20" fill="#ccc">
                            <path d="M6 2L3 0L0 2V17C0 18.6569 1.34315 20 3 20H17C18.6569 20 20 18.6569 20 17V14H18V2L15 0L12 2L9 0L6 2ZM16 14H4V17C4 17.5523 3.55228 18 3 18C2.44772 18 2 17.5523 2 17V3.07037L3 2.4037L6 4.4037L9 2.4037L12 4.4037L15 2.4037L16 3.07037V14ZM17 18H5.82929C5.93985 17.6872 6 17.3506 6 17V16H18V17C18 17.5523 17.5523 18 17 18Z" />
                        </svg>
                        <label class="mb-0">Billing Ref #</label>
                    </div>
                </div>
                <div class="col-7 col-md-7">
                    <div class="form-group">
                        <input type="text" class="form-control" value="1234567890" />
                    </div>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-5 col-md-5">
                    <div class="d-flex gap-2 align-items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="19px" viewBox="0 0 24 24" fill="#ccc">
                            <path d="M12 24C5.3724 24 0 18.6276 0 12C0 5.3724 5.3724 0 12 0C18.6276 0 24 5.3724 24 12C24 18.6276 18.6276 24 12 24ZM12 21.6C14.5461 21.6 16.9879 20.5886 18.7882 18.7882C20.5886 16.9879 21.6 14.5461 21.6 12C21.6 9.45392 20.5886 7.01212 18.7882 5.21178C16.9879 3.41143 14.5461 2.4 12 2.4C9.45392 2.4 7.01212 3.41143 5.21178 5.21178C3.41143 7.01212 2.4 9.45392 2.4 12C2.4 14.5461 3.41143 16.9879 5.21178 18.7882C7.01212 20.5886 9.45392 21.6 12 21.6ZM7.8 14.4H14.4C14.5591 14.4 14.7117 14.3368 14.8243 14.2243C14.9368 14.1117 15 13.9591 15 13.8C15 13.6409 14.9368 13.4883 14.8243 13.3757C14.7117 13.2632 14.5591 13.2 14.4 13.2H9.6C8.80435 13.2 8.04129 12.8839 7.47868 12.3213C6.91607 11.7587 6.6 10.9957 6.6 10.2C6.6 9.40435 6.91607 8.64129 7.47868 8.07868C8.04129 7.51607 8.80435 7.2 9.6 7.2H10.8V4.8H13.2V7.2H16.2V9.6H9.6C9.44087 9.6 9.28826 9.66321 9.17574 9.77574C9.06321 9.88826 9 10.0409 9 10.2C9 10.3591 9.06321 10.5117 9.17574 10.6243C9.28826 10.7368 9.44087 10.8 9.6 10.8H14.4C15.1957 10.8 15.9587 11.1161 16.5213 11.6787C17.0839 12.2413 17.4 13.0044 17.4 13.8C17.4 14.5956 17.0839 15.3587 16.5213 15.9213C15.9587 16.4839 15.1957 16.8 14.4 16.8H13.2V19.2H10.8V16.8H7.8V14.4Z" />
                        </svg>
                        <label class="mb-0">Payment Method</label>
                    </div>
                </div>
                <div class="col-7 col-md-7">
                    <div class="form-group">
                        <div class="d-flex flex-column gap-2 paymentmethod">
                            <label class="radio">
                                <input type="radio" name="payment" checked>
                                <span></span>
                                One Time
                            </label>
                            <label class="radio">
                                <input type="radio" name="payment">
                                <span></span>
                                Payment Plans
                            </label>
                            <label class="radio">
                                <input type="radio" name="payment">
                                <span></span>
                                Flexible Payments
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div id="onetime" style="display: block;">
                <div class="row mb-2 align-items-center">
                    <div class="col-5 col-md-5">
                        <div class="d-flex gap-2 align-items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="19px" viewBox="0 0 20 20" fill="transparent">
                                <path d="M6 2L3 0L0 2V17C0 18.6569 1.34315 20 3 20H17C18.6569 20 20 18.6569 20 17V14H18V2L15 0L12 2L9 0L6 2ZM16 14H4V17C4 17.5523 3.55228 18 3 18C2.44772 18 2 17.5523 2 17V3.07037L3 2.4037L6 4.4037L9 2.4037L12 4.4037L15 2.4037L16 3.07037V14ZM17 18H5.82929C5.93985 17.6872 6 17.3506 6 17V16H18V17C18 17.5523 17.5523 18 17 18Z" />
                            </svg>
                            <label class="mb-0">Billing Date</label>
                        </div>
                    </div>
                    <div class="col-7 col-md-7">
                        <div class="form-group">
                            <input type="date" class="form-control" />
                        </div>
                    </div>
                </div>
                <hr class="mb-3">
                <div class="totalamount p-2 bg-light text-dark border rounded-2 d-inline-flex align-items-center f12 justify-content-between w-100">
                    <span>Balance Payable</span>
                    <span class="fw-semibold text-dark">$290.00</span>
                </div>
            </div>
            <div id="paymentplans" style="display: none;">
                <div class="row mb-2 align-items-center">
                    <div class="col-5 col-md-5"></div>
                    <div class="col-md-7 ms-auto pe-0 mb-2">
                        <div class="d-flex justify-content-between">
                            <label>Auto-Pay</label>
                            <div class="form-check form-switch p-0 mb-0" style="min-height: auto;">
                                <input class="form-check-input" type="checkbox" checked="">
                            </div>
                        </div>
                    </div>
                    <div class="col-5 col-md-5">
                        <div class="d-flex gap-2 align-items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="19px" viewBox="0 0 20 20" fill="transparent">
                                <path d="M6 2L3 0L0 2V17C0 18.6569 1.34315 20 3 20H17C18.6569 20 20 18.6569 20 17V14H18V2L15 0L12 2L9 0L6 2ZM16 14H4V17C4 17.5523 3.55228 18 3 18C2.44772 18 2 17.5523 2 17V3.07037L3 2.4037L6 4.4037L9 2.4037L12 4.4037L15 2.4037L16 3.07037V14ZM17 18H5.82929C5.93985 17.6872 6 17.3506 6 17V16H18V17C18 17.5523 17.5523 18 17 18Z" />
                            </svg>
                            <label class="mb-0">Billing Date</label>
                        </div>
                    </div>
                    <div class="col-7 col-md-7">
                        <div class="form-group">
                            <input type="date" class="form-control" />
                        </div>
                    </div>
                </div>
                <div class="row mb-2 align-items-center">
                    <div class="col-5 col-md-5">
                        <div class="d-flex gap-2 align-items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="19px" viewBox="0 0 20 20" fill="transparent">
                                <path d="M6 2L3 0L0 2V17C0 18.6569 1.34315 20 3 20H17C18.6569 20 20 18.6569 20 17V14H18V2L15 0L12 2L9 0L6 2ZM16 14H4V17C4 17.5523 3.55228 18 3 18C2.44772 18 2 17.5523 2 17V3.07037L3 2.4037L6 4.4037L9 2.4037L12 4.4037L15 2.4037L16 3.07037V14ZM17 18H5.82929C5.93985 17.6872 6 17.3506 6 17V16H18V17C18 17.5523 17.5523 18 17 18Z" />
                            </svg>
                            <label class="mb-0">Down Payment</label>
                        </div>
                    </div>
                    <div class="col-7 col-md-7">
                        <div class="form-group">
                            <div class="position-relative">
                                <input type="text" class="form-control" value="0" style="padding-left: 20px;">
                                <span class="position-absolute f12" style="top: 13px; left: 10px;">$</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mb-2 align-items-center">
                    <div class="col-5 col-md-5">
                        <div class="d-flex gap-2 align-items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="19px" viewBox="0 0 20 20" fill="transparent">
                                <path d="M6 2L3 0L0 2V17C0 18.6569 1.34315 20 3 20H17C18.6569 20 20 18.6569 20 17V14H18V2L15 0L12 2L9 0L6 2ZM16 14H4V17C4 17.5523 3.55228 18 3 18C2.44772 18 2 17.5523 2 17V3.07037L3 2.4037L6 4.4037L9 2.4037L12 4.4037L15 2.4037L16 3.07037V14ZM17 18H5.82929C5.93985 17.6872 6 17.3506 6 17V16H18V17C18 17.5523 17.5523 18 17 18Z" />
                            </svg>
                            <label class="mb-0">Payment Term</label>
                        </div>
                    </div>
                    <div class="col-7 col-md-7">
                        <div class="form-group d-flex gap-2">
                            <select class="form-control form-select">
                                <option>Monthly</option>
                                <option>Weekly</option>
                                <option>Yearly</option>
                            </select>
                            <input type="number" class="form-control" value="2">
                        </div>
                    </div>
                </div>
                <div class="row mb-2 align-items-center">
                    <div class="col-5 col-md-5">
                        <div class="d-flex gap-2 align-items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="19px" viewBox="0 0 20 20" fill="transparent">
                                <path d="M6 2L3 0L0 2V17C0 18.6569 1.34315 20 3 20H17C18.6569 20 20 18.6569 20 17V14H18V2L15 0L12 2L9 0L6 2ZM16 14H4V17C4 17.5523 3.55228 18 3 18C2.44772 18 2 17.5523 2 17V3.07037L3 2.4037L6 4.4037L9 2.4037L12 4.4037L15 2.4037L16 3.07037V14ZM17 18H5.82929C5.93985 17.6872 6 17.3506 6 17V16H18V17C18 17.5523 17.5523 18 17 18Z" />
                            </svg>
                            <label class="mb-0">1st Scheduled Payment Date</label>
                        </div>
                    </div>
                    <div class="col-7 col-md-7">
                        <div class="form-group d-flex gap-2">
                            <input type="date" class="form-control">
                        </div>
                    </div>
                </div>
                <hr class="mb-3">
                <div class="totalamount p-2 bg-light text-dark border rounded-2 d-inline-flex align-items-center f12 justify-content-between w-100">
                    <span>Installment Amount</span>
                    <span class="fw-semibold text-dark">$290.00</span>
                </div>
            </div>
            <div id="flexiblepayment" style="display: none;">
                <div class="row mb-2 align-items-center">
                    <div class="col-5 col-md-5"></div>
                    <div class="col-md-7 ms-auto pe-0 mb-2">
                        <div class="d-flex justify-content-between">
                            <label>Auto-Pay</label>
                            <div class="form-check form-switch p-0 mb-0" style="min-height: auto;">
                                <input class="form-check-input" type="checkbox" checked="">
                            </div>
                        </div>
                    </div>
                    <div class="col-5 col-md-5">
                        <div class="d-flex gap-2 align-items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="19px" viewBox="0 0 20 20" fill="transparent">
                                <path d="M6 2L3 0L0 2V17C0 18.6569 1.34315 20 3 20H17C18.6569 20 20 18.6569 20 17V14H18V2L15 0L12 2L9 0L6 2ZM16 14H4V17C4 17.5523 3.55228 18 3 18C2.44772 18 2 17.5523 2 17V3.07037L3 2.4037L6 4.4037L9 2.4037L12 4.4037L15 2.4037L16 3.07037V14ZM17 18H5.82929C5.93985 17.6872 6 17.3506 6 17V16H18V17C18 17.5523 17.5523 18 17 18Z" />
                            </svg>
                            <label class="mb-0">Billing Date</label>
                        </div>
                    </div>
                    <div class="col-7 col-md-7">
                        <div class="form-group">
                            <input type="date" class="form-control" />
                        </div>
                    </div>
                </div>
                <div class="row mb-2 align-items-center">
                    <div class="col-5 col-md-5">
                        <div class="d-flex gap-2 align-items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="19px" viewBox="0 0 20 20" fill="transparent">
                                <path d="M6 2L3 0L0 2V17C0 18.6569 1.34315 20 3 20H17C18.6569 20 20 18.6569 20 17V14H18V2L15 0L12 2L9 0L6 2ZM16 14H4V17C4 17.5523 3.55228 18 3 18C2.44772 18 2 17.5523 2 17V3.07037L3 2.4037L6 4.4037L9 2.4037L12 4.4037L15 2.4037L16 3.07037V14ZM17 18H5.82929C5.93985 17.6872 6 17.3506 6 17V16H18V17C18 17.5523 17.5523 18 17 18Z" />
                            </svg>
                            <label class="mb-0">Down Payment</label>
                        </div>
                    </div>
                    <div class="col-7 col-md-7">
                        <div class="form-group">
                            <div class="position-relative">
                                <input type="text" class="form-control" value="0" style="padding-left: 20px;">
                                <span class="position-absolute f12" style="top: 13px; left: 10px;">$</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mb-2 align-items-center">
                    <div class="col-5 col-md-5">
                        <div class="d-flex gap-2 align-items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="19px" viewBox="0 0 20 20" fill="transparent">
                                <path d="M6 2L3 0L0 2V17C0 18.6569 1.34315 20 3 20H17C18.6569 20 20 18.6569 20 17V14H18V2L15 0L12 2L9 0L6 2ZM16 14H4V17C4 17.5523 3.55228 18 3 18C2.44772 18 2 17.5523 2 17V3.07037L3 2.4037L6 4.4037L9 2.4037L12 4.4037L15 2.4037L16 3.07037V14ZM17 18H5.82929C5.93985 17.6872 6 17.3506 6 17V16H18V17C18 17.5523 17.5523 18 17 18Z" />
                            </svg>
                            <label class="mb-0">Next Payment Dates</label>
                        </div>
                    </div>
                    <div class="col-7 col-md-7">
                        <div class="form-group d-flex gap-2">
                            <input type="date" class="form-control">
                            <div class="position-relative">
                                <input type="text" class="form-control" value="390" style="padding-left: 20px;">
                                <span class="position-absolute f12" style="top: 13px; left: 10px;">$</span>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="mb-3">
                <div class="totalamount p-2 bg-light text-dark border rounded-2 d-inline-flex align-items-center f12 justify-content-between w-100">
                    <span>Installment Amount</span>
                    <span class="fw-semibold text-dark">$290.00</span>
                </div>
            </div>
        </form>
    </div>

    <div class="modal-footer flex-nowrap p-2 border-top">
        <button type="button" class="btn-secondary w-100 m-1">Cancel</button>
        <button type="button" class="btn-primary w-100 m-1">Continue to Payment</button>
    </div>
</div>
<!-- End Billing -->

<!-- Enrollment Billing -->
<div class="overlay6"></div>
<div class="side-drawer" id="sideDrawer6">
    <div class="drawer-header text-end border-bottom px-3 d-flex justify-content-between align-items-center">
        <h6>
            <svg xmlns="http://www.w3.org/2000/svg" id="Layer_1" enable-background="new 0 0 100 100" viewBox="0 0 100 100" width="16px" height="16px" fill="CurrentColor">
                <path d="m44.93 76.47c.49.49 1.13.73 1.77.73s1.28-.24 1.77-.73c.98-.98.98-2.56 0-3.54l-21.43-21.43h51.96c1.38 0 2.5-1.12 2.5-2.5s-1.12-2.5-2.5-2.5h-51.96l21.43-21.43c.98-.98.98-2.56 0-3.54s-2.56-.98-3.54 0l-25.7 25.7c-.98.98-.98 2.56 0 3.54z"></path>
            </svg>
            <span class="mb-0">Create New Enrollment / Billing</span>
        </h6>
        <span class="close-btn" id="closeDrawer6">&times;</span>
    </div>
    <div class="drawer-body p-3" style="overflow-y: auto; height: calc(100% - 100px);">
        <h5 class="mb-4 text-dark">Payment</h5>
        <form class="mb-0 appointmentform">
            <div class="totalamount p-2 text-dark border rounded-2 f12 d-flex flex-column gap-2">
                <div class="d-inline-flex align-items-center justify-content-between w-100">
                    <span>Total Amount</span>
                    <span class="fw-semibold text-dark">$290.00</span>
                </div>
                <div class="d-inline-flex align-items-center justify-content-between w-100">
                    <span class="fw-semibold">Amount to Pay</span>
                    <span class="fw-semibold text-dark">$290.00</span>
                </div>
            </div>
            <hr class="my-3">
            <div class="row mb-2 align-items-center">
                <div class="col-5 col-md-5">
                    <div class="d-flex gap-2 align-items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="19px" viewBox="0 0 24 22" fill="#ccc">
                            <path d="M1.2 0H22.8C23.1183 0 23.4235 0.126428 23.6485 0.351472C23.8736 0.576516 24 0.88174 24 1.2V20.4C24 20.7183 23.8736 21.0235 23.6485 21.2485C23.4235 21.4736 23.1183 21.6 22.8 21.6H1.2C0.88174 21.6 0.576515 21.4736 0.351472 21.2485C0.126428 21.0235 0 20.7183 0 20.4V1.2C0 0.88174 0.126428 0.576516 0.351472 0.351472C0.576515 0.126428 0.88174 0 1.2 0ZM21.6 10.8H2.4V19.2H21.6V10.8ZM21.6 6V2.4H2.4V6H21.6Z" />
                        </svg>
                        <label class="mb-0">Payment Type</label>
                    </div>
                </div>
                <div class="col-7 col-md-7">
                    <div class="form-group">
                        <select class="form-control form-select">
                            <option value="" selected disabled>-- Select --</option>
                            <option value="Cash">Cash</option>
                            <option value="">Credit Card</option>
                        </select>
                    </div>
                </div>
            </div>
            <hr class="my-3">
            <div class="row mb-2">
                <div class="col-5 col-md-5">
                    <div class="d-flex gap-2 align-items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Capa_1" x="0px" y="0px" viewBox="0 0 512 512" style="enable-background:new 0 0 512 512;" xml:space="preserve" width="24px" height="19px" fill="#ccc">
                            <path d="M487.104,24.954c-33.274-33.269-87.129-33.273-120.407,0L51.948,339.665c-2.098,2.097-3.834,4.825-4.831,7.817 L1.057,485.647c-5.2,15.598,9.679,30.503,25.298,25.296l138.182-46.055c2.922-0.974,5.665-2.678,7.819-4.831l314.748-314.711 C520.299,112.154,520.299,58.146,487.104,24.954z M51.654,460.352l23.177-69.525l46.356,46.35L51.654,460.352z M158.214,417.634 l-63.837-63.829l267.272-267.24l63.837,63.83L158.214,417.634z M458.818,117.065l-5.049,5.049l-63.837-63.83l5.049-5.048 c17.602-17.597,46.239-17.597,63.837,0C476.419,70.833,476.419,99.467,458.818,117.065z" />
                        </svg>
                        <label class="mb-0">Internal Note</label>
                    </div>
                </div>
                <div class="col-7 col-md-7">
                    <div class="form-group">
                        <textarea class="form-control"></textarea>
                    </div>
                </div>
            </div>
            <div class="totalamount p-2 bg-light text-dark border rounded-2 d-inline-flex align-items-center f12 justify-content-between w-100">
                <span>Amount to Pay</span>
                <span class="fw-semibold text-dark">$290.00</span>
            </div>
        </form>
    </div>

    <div class="modal-footer flex-nowrap p-2 border-top">
        <button type="button" class="btn-secondary w-100 m-1">Cancel</button>
        <button type="button" class="btn-primary w-100 m-1">Save</button>
    </div>
</div>
<!-- End Enrollment Billing -->

<script src='https://unpkg.com/popper.js/dist/umd/popper.min.js'></script>
<script src='https://unpkg.com/tooltip.js/dist/umd/tooltip.min.js'></script>
<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>

<script>
    $(document).ready(function() {
        $('#PK_USER_MASTER').trigger("change");
    });

    let ENROLLMENT_BY_ID = parseInt(<?= $ENROLLMENT_BY_ID ?>);

    const appId = '<?= $SQUARE_APP_ID ?>';
    const locationId = '<?= $SQUARE_LOCATION_ID ?>';

    async function initializeCard(payments) {
        if (document.getElementById("card-container") !== null) {
            const card = await payments.card();
            await card.attach('#card-container');
            return card;
        } else {
            return false;
        }
    }

    async function createPayment(token) {
        document.getElementById('sourceId').value = token;
        $('#payment_confirmation_form').submit();

        /*const body = JSON.stringify({
          locationId,
          sourceId: token,
        });

        const paymentResponse = await fetch('payment.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body,
        });

        if (paymentResponse.ok) {
          return paymentResponse.json();
        }

        const errorBody = await paymentResponse.text();
        throw new Error(errorBody);*/

    }

    /*async function tokenize(paymentMethod) {
        const tokenResult = await paymentMethod.tokenize();
        if (tokenResult.status === 'OK') {
            return tokenResult.token;
        } else {
            let errorMessage = `Tokenization failed with status: ${tokenResult.status}`;
            if (tokenResult.errors) {
                errorMessage += ` and errors: ${JSON.stringify(
                    tokenResult.errors
                )}`;
            }

            throw new Error(errorMessage);
        }
    }

    // status is either SUCCESS or FAILURE;
    function displayPaymentResults(status) {
        if (document.getElementById("payment-status-container") !== null) {
            const statusContainer = document.getElementById(
                'payment-status-container'
            );
        } else {
            return false;
        }
        if (status === 'SUCCESS') {
            statusContainer.classList.remove('is-failure');
            statusContainer.classList.add('is-success');
        } else {
            statusContainer.classList.remove('is-success');
            statusContainer.classList.add('is-failure');
        }

        statusContainer.style.visibility = 'visible';
    }

    document.addEventListener('DOMContentLoaded', async function () {
        if (!window.Square) {
            throw new Error('Square.js failed to load properly');
        }

        let payments;
        try {
            payments = window.Square.payments(appId, locationId);
        } catch {
            if (document.getElementById("payment-status-container") !== null) {
                const statusContainer = document.getElementById(
                    'payment-status-container'
                );
            } else {
                return false;
            }
            statusContainer.className = 'missing-credentials';
            statusContainer.style.visibility = 'visible';
            return;
        }

        let card;
        try {
            card = await initializeCard(payments);
        } catch (e) {
            console.error('Initializing Card failed', e);
            return;
        }

        // Checkpoint 2.
        async function handlePaymentMethodSubmission(event, paymentMethod) {
            event.preventDefault();

            try {
                // disable the submit button as we await tokenization and make a payment request.
                cardButton.disabled = true;
                const token = await tokenize(paymentMethod);
                const paymentResults = await createPayment(token);
                displayPaymentResults('SUCCESS');

                console.debug('Payment Success', paymentResults);
            } catch (e) {
                cardButton.disabled = false;
                displayPaymentResults('FAILURE');
                console.error(e.message);
            }
        }

        const cardButton = document.getElementById('card-button');
        cardButton.addEventListener('click', async function (event) {
            await handlePaymentMethodSubmission(event, card);
        });
    });*/
</script>

<script>
    let PK_ENROLLMENT_MASTER = parseInt(<?= empty($_GET['id']) ? 0 : $_GET['id'] ?>);

    $('#PK_USER_MASTER').SumoSelect({
        placeholder: 'Select Customer',
        search: true,
        searchText: 'Search...'
    });

    $('.datepicker-normal').datepicker({
        dateFormat: 'mm/dd/yy'
    });

    $('.datepicker-future').datepicker({
        dateFormat: 'mm/dd/yy',
        beforeShow: function(input, inst) {
            var selectedDate = $('#BILLING_DATE').datepicker('getDate');
            if (selectedDate) {
                var nextDay = new Date(selectedDate.getTime());
                nextDay.setDate(nextDay.getDate() + 1);
                $(this).datepicker('option', 'minDate', nextDay);
            } else {
                $(this).datepicker('option', 'minDate', 0);
            }
        }
    });


    function selectThisCustomer(param) {
        let location_id = $(param).find(':selected').data('location_id');
        let PK_USER = $(param).find(':selected').data('pk_user');
        $('#PK_LOCATION').val(location_id);
        $.ajax({
            url: "ajax/get_locations.php",
            type: "POST",
            data: {
                PK_USER: PK_USER,
                LOCATION_ID: location_id
            },
            async: false,
            cache: false,
            success: function(result) {
                $('#PK_LOCATION').empty().append(result);
                if (PK_ENROLLMENT_MASTER == 0) {
                    showEnrollmentInstructor();
                }
                showEnrollmentBy();
                getEnrollmentCount();
            }
        });
    }

    function showEnrollmentBy() {
        let location_id = $('#PK_LOCATION').val();
        $.ajax({
            url: "ajax/get_enrollment_by.php",
            type: "POST",
            data: {
                LOCATION_ID: location_id
            },
            async: false,
            cache: false,
            success: function(result) {
                $('#ENROLLMENT_BY_ID').empty().append(result);
                if (PK_ENROLLMENT_MASTER > 0) {
                    $('#ENROLLMENT_BY_ID').val(ENROLLMENT_BY_ID);
                }
            }
        });
    }

    function showEnrollmentInstructor() {
        let location_id = $('#PK_LOCATION').val();
        $.ajax({
            url: "ajax/get_instructor.php",
            type: "POST",
            data: {
                LOCATION_ID: location_id
            },
            async: false,
            cache: false,
            success: function(result) {
                $('.SERVICE_PROVIDER_ID').empty().append(result);
            }
        });
    }

    function getEnrollmentCount() {
        let PK_ENROLLMENT_MASTER = parseInt(<?= empty($_GET['id']) ? 0 : $_GET['id'] ?>);
        let PK_USER_MASTER = $('#PK_USER_MASTER').val();
        let PK_LOCATION = $('#PK_LOCATION').val();
        if (PK_USER_MASTER > 0 && PK_LOCATION > 0 && PK_ENROLLMENT_MASTER == 0) {
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: "POST",
                data: {
                    PK_USER_MASTER: PK_USER_MASTER,
                    PK_LOCATION: PK_LOCATION,
                    FUNCTION_NAME: 'getEnrollmentCount'
                },
                async: false,
                cache: false,
                success: function(result) {
                    switch (parseInt(result)) {
                        case 0:
                            $('#PK_ENROLLMENT_TYPE').val(5);
                            break;
                        case 1:
                            $('#PK_ENROLLMENT_TYPE').val(2);
                            break;
                        case 2:
                            $('#PK_ENROLLMENT_TYPE').val(9);
                            break;
                        default:
                            $('#PK_ENROLLMENT_TYPE').val(13);
                            break;
                    }
                }
            });
        }
    }

    function addMoreServices() {
        let charge_type = $('.charge_type:checked').val();
        if (charge_type === 'Membership') {
            var value = "XX";
            var type = "readonly";
            var total = "";
        } else {
            var value = "";
            var type = "";
            var total = "readonly";
        }


        $('#append_service_div').append(`<div class="row individual_service_div">
                                            <div class="col-2">
                                                <div class="form-group">
                                                    <select class="form-control PK_SERVICE_MASTER" name="PK_SERVICE_MASTER[]" onchange="selectThisService(this)" required>
                                                        <option>Select</option>
                                                        <?php
                                                        $row = $db_account->Execute("SELECT DISTINCT DOA_SERVICE_MASTER.PK_SERVICE_MASTER, DOA_SERVICE_MASTER.SERVICE_NAME, DOA_SERVICE_MASTER.DESCRIPTION, DOA_SERVICE_MASTER.ACTIVE FROM `DOA_SERVICE_MASTER` WHERE DOA_SERVICE_MASTER.PK_LOCATION IN (" . $DEFAULT_LOCATION_ID . ") AND IS_DELETED = 0");
                                                        while (!$row->EOF) { ?>
                                                            <option value="<?php echo $row->fields['PK_SERVICE_MASTER']; ?>"><?= $row->fields['SERVICE_NAME'] ?></option>
                                                        <?php $row->MoveNext();
                                                        } ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-1">
                                                <div class="form-group">
                                                    <select class="form-control PK_SERVICE_CODE" name="PK_SERVICE_CODE[]" onchange="selectThisServiceCode(this)" required>
                                                        <option value="">Select</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-2">
                                                <div class="form-group">
                                                    <input type="text" class="form-control SERVICE_DETAILS" name="SERVICE_DETAILS[]" >
                                                </div>
                                            </div>
                                            <div class="col-1">
                                                <div class="form-group">
                                                    <input type="text" class="form-control NUMBER_OF_SESSION" value="${value}" name="NUMBER_OF_SESSION[]" onkeyup="calculateServiceTotal(this)" ${type} required>
                                                </div>
                                            </div>
                                            <div class="col-1">
                                                <div class="form-group">
                                                    <input type="text" class="form-control PRICE_PER_SESSION" value="${value}" name="PRICE_PER_SESSION[]" onkeyup="calculateServiceTotal(this);" ${type} required>
                                                </div>
                                            </div>
                                            <div class="col-1">
                                                <div class="form-group">
                                                    <input type="text" class="form-control TOTAL" name="TOTAL[]" onkeyup="calculateServiceTotal(this)" ${total}>
                                                </div>
                                            </div>
                                            <div class="col-1">
                                                <div class="form-group">
                                                    <select class="form-control DISCOUNT_TYPE" name="DISCOUNT_TYPE[]" onchange="calculateServiceTotal(this)">
                                                        <option value="">Select</option>
                                                        <option value="1">Fixed</option>
                                                        <option value="2">Percent</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-1">
                                                <div class="form-group">
                                                    <input type="text" class="form-control DISCOUNT" name="DISCOUNT[]" onkeyup="calculateServiceTotal(this)">
                                                </div>
                                            </div>
                                            <div class="col-1">
                                                <div class="form-group">
                                                    <input type="text" class="form-control FINAL_AMOUNT" name="FINAL_AMOUNT[]" readonly>
                                                </div>
                                            </div>
                                            <div class="col-1">
                                                <div class="form-group">
                                                    <a href="javascript:;" onclick="removeThis(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                                </div>
                                            </div>
                                        </div>`);
    }

    function addMoreServiceProviders() {
        $('#append_service_provider_div').append(`<div class="form-group d-flex gap-2 align-items-center" id="salesby" style="margin-top: 1%;">
                            <select class="form-control form-select SERVICE_PROVIDER_ID" name="SERVICE_PROVIDER_ID[]" id="SERVICE_PROVIDER_ID">
                                <option value="" selected disabled>-- Select --</option>
                            </select>
                            <div class="position-relative">
                                <input type="text" class="form-control SERVICE_PROVIDER_PERCENTAGE" placeholder="Enter %" style="max-width: 120px;" name="SERVICE_PROVIDER_PERCENTAGE[]">
                            </div>
                        </div>`);
        showEnrollmentInstructor();
    }

    function removeThis(param) {
        $(param).closest('.row').remove();
    }

    function removeThisAmount(param) {
        $(param).closest('.row').remove();
        let total_bill = parseFloat(($('#total_bill').val()) ? $('#total_bill').val() : 0);
        let total_flexible_payment = 0;
        $('.FLEXIBLE_PAYMENT_AMOUNT').each(function() {
            total_flexible_payment += parseFloat($(this).val());
        });
        total_flexible_payment = isNaN(total_flexible_payment) ? 0 : total_flexible_payment;
        $('#BALANCE_PAYABLE').val(parseFloat(total_bill - total_flexible_payment).toFixed(2));
    }

    function selectThisServiceCode(param) {
        let service_details = $(param).find(':selected').data('details');
        let price = $(param).find(':selected').data('price');

        let charge_type = $('.charge_type:checked').val();
        if (charge_type === 'Membership') {
            $(param).closest('.row').find('.SERVICE_DETAILS').val(service_details);
            $(param).closest('.row').find('.PRICE_PER_SESSION').val("XX");
        } else {
            $(param).closest('.row').find('.SERVICE_DETAILS').val(service_details);
            $(param).closest('.row').find('.PRICE_PER_SESSION').val(price);
        }

        calculateServiceTotal(param);
    }

    function selectThisService(param) {
        let PK_SERVICE_MASTER = $(param).val();
        $.ajax({
            url: "ajax/get_service_codes.php",
            type: "POST",
            data: {
                PK_SERVICE_MASTER: PK_SERVICE_MASTER
            },
            async: false,
            cache: false,
            success: function(result) {
                $(param).closest('.row').find('.PK_SERVICE_CODE').empty();
                $(param).closest('.row').find('.PK_SERVICE_CODE').append(result);
            }
        });
    }

    function selectThisPackage(param) {
        let PK_PACKAGE = $(param).val();
        let EXPIRY_DATE = $(param).find(':selected').data('expiry_date');
        if (PK_PACKAGE) {
            $.ajax({
                url: "ajax/get_packages.php",
                type: "POST",
                data: {
                    PK_PACKAGE: PK_PACKAGE
                },
                async: false,
                cache: false,
                success: function(result) {
                    // Clear existing services
                    $('#append_service_div').empty();

                    // Append the package services
                    $('#append_service_div').html(result);

                    // Calculate total amount from FINAL_AMOUNT hidden inputs
                    let TOTAL_AMOUNT = 0;
                    $('#append_service_div .FINAL_AMOUNT').each(function() {
                        TOTAL_AMOUNT += parseFloat($(this).val()) || 0;
                    });
                    $('.TOTAL_AMOUNT').text('$' + TOTAL_AMOUNT.toFixed(2));
                    $('.TOTAL_AMOUNT').val(TOTAL_AMOUNT.toFixed(2));

                    // Set expiry date
                    $('select[name="EXPIRY_DATE"] option').each(function() {
                        if ($(this).data('expiry_date') == EXPIRY_DATE) {
                            $(this).prop('selected', true);
                        }
                    });
                }
            });
        } else {
            $('#append_service_div').empty();
            $('.TOTAL_AMOUNT').text('$0.00');
            $('.TOTAL_AMOUNT').val('0.00');
            addMoreServices();
        }
    }

    function chargeBySessions(param) {
        $('.NUMBER_OF_SESSION').prop('readonly', false);
        $('.NUMBER_OF_SESSION').val('').css('pointer-events', 'none').trigger('change');
        $('.PRICE_PER_SESSION').prop('readonly', false);
        $('.PRICE_PER_SESSION').val('').css('pointer-events', 'none').trigger('change');
        if ($(param).is(':checked') && ($(param).val() === 'Session' || $(param).val() === 'Membership')) {
            if ($(param).val() === 'Session') {
                $('#Membership').prop('checked', false);
                $('.TOTAL').prop('readonly', true);
                $('.add_more').hide();
                $('.session_base').show();
                $('.member_base').hide();
            } else {
                $('#Session').prop('checked', false);
                $('.NUMBER_OF_SESSION').prop('readonly', true);
                $('.NUMBER_OF_SESSION').val('XX').css('pointer-events', 'none').trigger('change');
                $('.PRICE_PER_SESSION').prop('readonly', true);
                $('.PRICE_PER_SESSION').val('XX').css('pointer-events', 'none').trigger('change');
                $('.TOTAL').prop('readonly', false);
                $('.add_more').show();
                $('.session_base').hide();
                $('.member_base').show();
            }
            $('#BILLING_DATE').prop('readonly', true).css("pointer-events", "none");
            $('.one_time').show();
            $('.payment_plans').hide();
            $('.flexible_payments').hide();
            document.querySelector("input[name='PAYMENT_METHOD'][value='One Time']").checked = true;
            $('#down_payment_div').slideUp();
            $('#AMOUNT_TO_PAY').prop('readonly', true);
            $('.partial_payment').hide();
            $('.ENROLLMENT_PAYMENT_TYPE').val(1).css('pointer-events', 'none').trigger('change');
            $('#save_card_on_file_div').show();
        } else {
            $('.session_base').show();
            $('.member_base').hide();

            $('.add_more').show();
            $('#BILLING_DATE').prop('readonly', false).css("pointer-events", "auto");
            $('.one_time').show();
            $('.payment_plans').show();
            $('.flexible_payments').show();
            document.querySelector("input[name='PAYMENT_METHOD'][value='One Time']").checked = false;
            $('#down_payment_div').slideDown();
            $('#AMOUNT_TO_PAY').prop('readonly', false);
            $('.ENROLLMENT_PAYMENT_TYPE').css('pointer-events', 'auto');
            $('.partial_payment').show();
            $('#save_card_on_file_div').hide();
        }
    }

    function calculateServiceTotal(param) {
        let charge_type = $('.charge_type:checked').val();
        let TOTAL = 0;

        if (charge_type === 'Membership') {
            TOTAL = ($(param).closest('.row').find('.TOTAL').val() == '') ? 0 : $(param).closest('.row').find('.TOTAL').val();
        } else {
            let number_of_session = ($(param).closest('.row').find('.NUMBER_OF_SESSION').val() == '') ? 0 : $(param).closest('.row').find('.NUMBER_OF_SESSION').val();
            let service_price = ($(param).closest('.row').find('.PRICE_PER_SESSION').val()) ?? 0;
            TOTAL = parseFloat(number_of_session) * parseFloat(service_price);
            $(param).closest('.row').find('.TOTAL').val(parseFloat(TOTAL).toFixed(2));
        }

        let DISCOUNT = ($(param).closest('.row').find('.DISCOUNT').val()) ?? 0;
        let DISCOUNT_TYPE = ($(param).closest('.row').find('.DISCOUNT_TYPE').val()) ?? 0;
        let FINAL_AMOUNT = parseFloat(TOTAL);
        if (DISCOUNT_TYPE == 1) {
            FINAL_AMOUNT = parseFloat(TOTAL - DISCOUNT);
        } else {
            if (DISCOUNT_TYPE == 2) {
                FINAL_AMOUNT = parseFloat(TOTAL - (TOTAL * (DISCOUNT / 100)));
            }
        }
        $(param).closest('.row').find('.FINAL_AMOUNT').val(FINAL_AMOUNT.toFixed(2));

        let TOTAL_AMOUNT = 0;
        $(param).closest('#enrollment_form').find('.FINAL_AMOUNT').each(function() {
            TOTAL_AMOUNT += parseFloat($(this).val());
        });
        $('.TOTAL_AMOUNT').val(TOTAL_AMOUNT.toFixed(2));
    }

    $(document).on('click', '#cancel_button', function() {
        window.location.href = 'all_enrollments.php'
    });

    function addMorePayments() {
        let total_bill = parseFloat(($('#total_bill').val()) ? $('#total_bill').val() : 0);
        let down_payment = parseFloat(($('#DOWN_PAYMENT').val()) ? $('#DOWN_PAYMENT').val() : 0);
        let total_flexible_payment = 0;
        $('.FLEXIBLE_PAYMENT_AMOUNT').each(function() {
            total_flexible_payment += parseFloat($(this).val());
        });
        if ((total_flexible_payment + down_payment) < total_bill) {
            $('#flexible_plans_div').append(`<div class="row">
                                            <div class="col-3">
                                                <div class="form-group">
                                                    <div class="col-md-12">
                                                        <input type="text" name="FLEXIBLE_PAYMENT_DATE[]" class="form-control datepicker-future" required>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-3">
                                                <div class="form-group">
                                                    <div class="col-md-12">
                                                        <input type="text" name="FLEXIBLE_PAYMENT_AMOUNT[]" class="form-control FLEXIBLE_PAYMENT_AMOUNT" onkeyup="calculateBalancePayable(this)" required>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-3" style="padding-top: 5px;">
                                                <a href="javascript:;" onclick="removeThisAmount(this);" style="color: red; font-size: 20px;"><i class="ti-trash"></i></a>
                                            </div>
                                        </div>`);
            $('.datepicker-future').datepicker({
                dateFormat: 'mm/dd/yy',
                beforeShow: function(input, inst) {
                    var selectedDate = $('#BILLING_DATE').datepicker('getDate');
                    if (selectedDate) {
                        var nextDay = new Date(selectedDate.getTime());
                        nextDay.setDate(nextDay.getDate() + 1);
                        $(this).datepicker('option', 'minDate', nextDay);
                    } else {
                        $(this).datepicker('option', 'minDate', 0);
                    }
                }
            });
        } else {
            alert('Total Bill Amount Exceed');
        }
    }

    $(document).on('submit', '#enrollment_form', function(event) {
        event.preventDefault();
        let service_provider = $('#SERVICE_PROVIDER_ID').val();
        let is_confirm = $('#is_confirm').val();
        if (service_provider == '' && is_confirm == 0) {
            $('#confirm_modal').modal('show');
        } else {
            $('#confirm_modal').modal('hide');
            let form_data = $('#enrollment_form').serialize();
            $.ajax({
                url: "ajax/AjaxFunctions.php",
                type: 'POST',
                data: form_data,
                dataType: 'json',
                success: function(data) {
                    if (PK_ENROLLMENT_MASTER > 0) {
                        window.location.reload();
                    } else {
                        $('.PK_ENROLLMENT_MASTER').val(data.PK_ENROLLMENT_MASTER);
                        $('#billing_link')[0].click();
                    }
                }
            });
        }
    });

    function goToPaymentTab() {
        let PK_ENROLLMENT_MASTER = $('.PK_ENROLLMENT_MASTER').val();
        if (PK_ENROLLMENT_MASTER) {
            $.ajax({
                url: "ajax/show_payment_tab.php",
                type: 'POST',
                data: {
                    PK_ENROLLMENT_MASTER: PK_ENROLLMENT_MASTER
                },
                success: function(data) {
                    $('#payment_tab_div').html(data);
                    $('#AMOUNT_SHOW').val($('.TOTAL_AMOUNT').val());
                    calculatePayment();
                }
            });
        } else {
            alert('Please fill up the enrollment form first');
            $('#enrollment_link')[0].click();
        }
    }

    function goToLedgerTab() {
        let PK_ENROLLMENT_MASTER = $('.PK_ENROLLMENT_MASTER').val();
        if (!PK_ENROLLMENT_MASTER) {
            alert('Please fill up the enrollment form first');
            $('#enrollment_link')[0].click();
        }
    }

    function calculateDiscount(param) {
        let DISCOUNT = $(param).closest('.row').find('.DISCOUNT').val();
        let DISCOUNT_TYPE = $(param).closest('.row').find('.DISCOUNT_TYPE').val();
        let TOTAL = $(param).closest('.row').find('.TOTAL').val();

        if (DISCOUNT_TYPE == 1) {
            let FINAL_AMOUNT = parseFloat(TOTAL - DISCOUNT);
            $(param).closest('.row').find('.FINAL_AMOUNT').val(FINAL_AMOUNT.toFixed(2));
        } else {
            if (DISCOUNT_TYPE == 2) {
                let FINAL_AMOUNT = parseFloat(TOTAL - (TOTAL * (DISCOUNT / 100)));
                $(param).closest('.row').find('.FINAL_AMOUNT').val(FINAL_AMOUNT.toFixed(2));
            }
        }
        let TOTAL_AMOUNT = 0;
        $(param).closest('#payment_tab_div').find('.FINAL_AMOUNT').each(function() {
            TOTAL_AMOUNT += parseFloat($(this).val());
        });
        $('#total_bill').val(parseFloat(TOTAL_AMOUNT).toFixed(2));
        $('#BALANCE_PAYABLE').val(parseFloat(TOTAL_AMOUNT).toFixed(2));
    }

    function calculatePayment() {
        let total_bill = parseFloat(($('#total_bill').val()) ? $('#total_bill').val() : 0);
        let down_payment = parseFloat(($('#DOWN_PAYMENT').val()) ? $('#DOWN_PAYMENT').val() : 0);
        let balance_payable = parseFloat(($('#BALANCE_PAYABLE').val()) ? $('#BALANCE_PAYABLE').val() : 0);
        $('#BALANCE_PAYABLE').val(parseFloat(total_bill - down_payment).toFixed(2));
        calculateBalancePayable();
        calculatePaymentPlans();
    }

    $(document).on('change', '.PAYMENT_METHOD', function() {
        $('.payment_method_div').slideUp();
        $('#down_payment_div').slideDown();
        $('#FIRST_DUE_DATE').prop('required', false);
        $('#auto-pay-div').slideUp();
        //$('#IS_ONE_TIME_PAY').val(0);
        if ($(this).val() == 'One Time') {
            let total_bill = parseFloat(($('#total_bill').val()) ? $('#total_bill').val() : 0);
            $('#DOWN_PAYMENT').val(0.00);
            $('#BALANCE_PAYABLE').val(total_bill.toFixed(2));
            $('#down_payment_div').slideUp();
            $('#ACTUAL_AMOUNT').val(total_bill.toFixed(2));
            $('#AMOUNT_TO_PAY').val(total_bill.toFixed(2));
            //$('#payment_confirmation_form_div').slideDown();
            //$('#IS_ONE_TIME_PAY').val(1);
            $('#PAYMENT_BILLING_REF').val($('#BILLING_REF').val());
            $('#PAYMENT_BILLING_DATE').val($('#BILLING_DATE').val());
            //$('#enrollment_payment_modal').modal('show');
        }
        if ($(this).val() == 'Payment Plans') {
            $('#FIRST_DUE_DATE').prop('required', true);
            $('#payment_plans_div').slideDown();
            $('#auto-pay-div').slideDown();
        }
        if ($(this).val() == 'Flexible Payments') {
            $('#flexible_plans_div').slideDown();
            let total_bill = parseFloat(($('#total_bill').val()) ? $('#total_bill').val() : 0);
            $('#DOWN_PAYMENT').val(0.00);
            $('#BALANCE_PAYABLE').val(total_bill.toFixed(2));
            $('#down_payment_div').slideDown();
            $('#ACTUAL_AMOUNT').val(total_bill.toFixed(2));
            $('#AMOUNT_TO_PAY').val(total_bill.toFixed(2));
            $('#auto-pay-div').slideDown();
            //$('#payment_confirmation_form_div').slideDown();
            //$('#enrollment_payment_modal').modal('show');
        }
    });

    $(document).on('click', '.ACTIVE_AUTO_PAY', function() {
        if ($(this).val() == '1') {
            $('#credit_card_modal').modal('show');
            getSavedCreditCardListAutoPay();
        } else {
            $('#TEMP_PAYMENT_METHOD_ID').val('');
            $('#TEMP_LAST4').val('');
            $('#AUTO_PAY_PAYMENT_METHOD_ID').val('');
            $('#selected_card_span').css('color', 'red').text('Auto Pay is not active');
        }
    });

    function getSavedCreditCardListAutoPay() {
        let PK_USER_MASTER = $('#PK_USER_MASTER').find(':selected').data('customer_id');
        $.ajax({
            url: "ajax/get_credit_card_list.php",
            type: 'POST',
            data: {
                PK_USER_MASTER: PK_USER_MASTER,
                call_from: 'enrollment_auto_pay'
            },
            success: function(data) {
                $('#saved_credit_card_list').slideDown().html(data);
                addCreditCardAutoPay();
            }
        });
    }

    function selectAutoPayCreditCard(param) {
        let payment_id = $(param).attr('id');
        let last4 = $(param).data('last4');

        $('.credit-card-div').css("opacity", "1");
        $(param).css("opacity", "0.6");

        $('#TEMP_PAYMENT_METHOD_ID').val(payment_id);
        $('#TEMP_LAST4').val(last4);
    }

    function addAutoPayCardDetails() {
        let payment_id = $('#TEMP_PAYMENT_METHOD_ID').val();
        let last4 = $('#TEMP_LAST4').val();

        $('#AUTO_PAY_PAYMENT_METHOD_ID').val(payment_id);
        $('#selected_card_span').css('color', 'green').html('Card ending in <b>' + last4 + '</b> selected for Auto Pay');
        $('#credit_card_modal').modal('hide');
    }

    function addCreditCardAutoPay() {
        let PK_USER = $('#PK_USER_MASTER').find(':selected').data('pk_user');
        let PK_USER_MASTER = $('#PK_USER_MASTER').find(':selected').data('customer_id');
        $.ajax({
            url: "includes/save_credit_card.php",
            type: 'POST',
            data: {
                PK_USER: PK_USER,
                PK_USER_MASTER: PK_USER_MASTER,
                call_from: 'enrollment_auto_pay'
            },
            success: function(data) {
                $('#add_credit_card_div').slideDown().html(data);
                addCreditCard();
            }
        });
    }

    function calculateBalancePayable() {
        let total_bill = parseFloat(($('#total_bill').val()) ? $('#total_bill').val() : 0);
        let total_flexible_payment = parseFloat(($('#DOWN_PAYMENT').val()) ? $('#DOWN_PAYMENT').val() : 0);
        $('.FLEXIBLE_PAYMENT_AMOUNT').each(function() {
            total_flexible_payment += parseFloat(($(this).val()) ? $(this).val() : 0);
        });
        total_flexible_payment = isNaN(total_flexible_payment) ? 0 : total_flexible_payment;
        $('#BALANCE_PAYABLE').val(parseFloat(total_bill - total_flexible_payment).toFixed(2));
    }

    function calculatePaymentPlans() {
        let balance_payable = parseFloat(($('#BALANCE_PAYABLE').val()) ? $('#BALANCE_PAYABLE').val() : 0);
        let NUMBER_OF_PAYMENT = parseInt(($('#NUMBER_OF_PAYMENT').val()) ? $('#NUMBER_OF_PAYMENT').val() : 1);
        $('#INSTALLMENT_AMOUNT').val(parseFloat(balance_payable / NUMBER_OF_PAYMENT).toFixed(2));
    }

    function calculateNumberOfPayment(param) {
        let balance_payable = parseFloat(($('#BALANCE_PAYABLE').val()) ? $('#BALANCE_PAYABLE').val() : 0);
        let entered_amount = $(param).val();
        let number_of_payment = balance_payable / entered_amount;
        $('#NUMBER_OF_PAYMENT').val(number_of_payment);
        if (Number.isInteger(number_of_payment)) {
            $('#number_of_payment_error').hide();
        } else {
            $('#number_of_payment_error').show();
        }
    }

    $(document).on('submit', '#billing_form', function(event) {
        event.preventDefault();
        calculateBalancePayable();
        calculatePaymentPlans();
        let total_bill = parseFloat(($('#total_bill').val()) ? $('#total_bill').val() : 0);
        let down_payment = parseFloat(($('#DOWN_PAYMENT').val()) ? $('#DOWN_PAYMENT').val() : 0);
        let total_flexible_payment = 0;
        $('.FLEXIBLE_PAYMENT_AMOUNT').each(function() {
            total_flexible_payment += parseFloat($(this).val());
        });
        total_flexible_payment = isNaN(total_flexible_payment) ? 0 : total_flexible_payment;
        if ((total_flexible_payment + down_payment) <= total_bill) {
            let balance_payable = parseFloat(($('#BALANCE_PAYABLE').val()) ? $('#BALANCE_PAYABLE').val() : 0);
            let payment_method = $('.PAYMENT_METHOD:checked').val();
            if (payment_method == 'Flexible Payments' && balance_payable > 0) {
                swal("Balance Payable!", "Remaining Balance Payable must be fully allocated between Next Payment Dates.", "error");
            } else {
                let number_of_payment = $('#NUMBER_OF_PAYMENT').val();
                if (Number.isInteger(Number(number_of_payment))) {
                    if ((payment_method === 'One Time') && (balance_payable <= 0)) {
                        Swal.fire({
                            title: "Are you sure?",
                            text: "The user want to create a $0.00 enrollment?",
                            icon: "warning",
                            showCancelButton: true,
                            confirmButtonColor: "#3085d6",
                            cancelButtonColor: "#d33",
                            confirmButtonText: "Yes, create it!"
                        }).then((result) => {
                            if (result.isConfirmed) {
                                submitBillingForm();
                            }
                        });
                    } else {
                        if (payment_method == 'Flexible Payments' || payment_method == 'Payment Plans') {
                            let ACTIVE_AUTO_PAY = $('.ACTIVE_AUTO_PAY:checked').val();
                            let AUTO_PAY_PAYMENT_METHOD_ID = $('#AUTO_PAY_PAYMENT_METHOD_ID').val();
                            if (ACTIVE_AUTO_PAY == '1' && AUTO_PAY_PAYMENT_METHOD_ID == '') {
                                Swal.fire({
                                    title: "Are you sure?",
                                    text: "You selected to active Auto Pay but no credit card selected. Do you want to proceed without Auto Pay?",
                                    icon: "warning",
                                    showCancelButton: true,
                                    confirmButtonColor: "#3085d6",
                                    cancelButtonColor: "#d33",
                                    confirmButtonText: "Yes, proceed!"
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        $('#ACTIVE_AUTO_PAY_NO').prop('checked', true);
                                        submitBillingForm();
                                    }
                                });
                            } else {
                                submitBillingForm();
                            }
                        } else {
                            submitBillingForm();
                        }
                    }
                } else {
                    $('#number_of_payment_error').slideUp();
                    $('#number_of_payment_error').slideDown();
                }
            }
        } else {
            alert('Total Bill Amount Exceed');
        }
    });

    function submitBillingForm() {
        let form_data = $('#billing_form').serialize();
        $.ajax({
            url: "ajax/AjaxFunctions.php",
            type: 'POST',
            data: form_data,
            dataType: 'json',
            success: function(data) {
                $('.PK_ENROLLMENT_BILLING').val(data.PK_ENROLLMENT_BILLING);
                $('.PK_ENROLLMENT_LEDGER').val(data.PK_ENROLLMENT_LEDGER);
                let payment_method = $('.PAYMENT_METHOD:checked').val();
                let down_payment = parseFloat($('#DOWN_PAYMENT').val());
                let today = new Date().getTime();
                let firstPaymentDate = new Date($('#FIRST_DUE_DATE').val()).getTime();
                let billingDate = new Date($('#BILLING_DATE').val()).getTime();
                let balance_payable = parseFloat(($('#BALANCE_PAYABLE').val()) ? $('#BALANCE_PAYABLE').val() : 0);

                //alert((today.getDate() + '/' + today.getMonth() + '/' + today.getFullYear() >= billingDate.getDate() + '/' + billingDate.getMonth() + '/' + billingDate.getFullYear()));

                //console.log($('.PAYMENT_METHOD:checked').val(), today.getDate() + '/' + today.getMonth() + '/' + today.getFullYear(), billingDate.getDate() + '/' + billingDate.getMonth() + '/' + billingDate.getFullYear());

                if (((down_payment > 0) && (today >= billingDate)) || ((payment_method === 'One Time') && (today >= billingDate) && (balance_payable > 0)) || ((payment_method === 'Payment Plans') && (today >= firstPaymentDate))) {
                    if (payment_method === 'One Time') {
                        $('#AMOUNT_TO_PAY').val(balance_payable.toFixed(2));
                        $('#ACTUAL_AMOUNT').val(balance_payable.toFixed(2));
                    } else {
                        if (down_payment > 0) {
                            $('#AMOUNT_TO_PAY').val(down_payment.toFixed(2));
                            $('#ACTUAL_AMOUNT').val(down_payment.toFixed(2));
                        } else {
                            if ((payment_method === 'Payment Plans') && (today >= firstPaymentDate)) {
                                let installment_amount = parseFloat(($('#INSTALLMENT_AMOUNT').val()) ? $('#INSTALLMENT_AMOUNT').val() : 0);
                                $('#AMOUNT_TO_PAY').val(installment_amount.toFixed(2));
                                $('#ACTUAL_AMOUNT').val(installment_amount.toFixed(2));
                            }
                        }
                    }
                    $('#enrollment_payment_modal').modal('show');
                } else {
                    let header = '<?= $header ?>';
                    if (header) {
                        window.location.href = header;
                    } else {
                        let PK_USER = $('#PK_USER_MASTER').find(':selected').data('pk_user');
                        let PK_USER_MASTER = $('#PK_USER_MASTER').find(':selected').data('customer_id');
                        window.location.href = 'customer.php?id=' + PK_USER + '&master_id=' + PK_USER_MASTER + '&tab=enrollment';
                    }
                }
            }
        });
    }

    function payNow(PK_ENROLLMENT_LEDGER, BILLED_AMOUNT) {
        $('.PK_ENROLLMENT_LEDGER').val(PK_ENROLLMENT_LEDGER);
        $('#AMOUNT_TO_PAY').val(BILLED_AMOUNT);
        $('#ACTUAL_AMOUNT').val(BILLED_AMOUNT);
        $('#payment_confirmation_form_div').slideDown();
        $('#PK_PAYMENT_TYPE').val('');
        $('.payment_type_div').slideUp();
        $('#wallet_balance_div').slideUp();
        $('#remaining_amount_div').slideUp();
        $('#PK_PAYMENT_TYPE_REMAINING').prop('required', false);
        $('#enrollment_payment_modal').modal('show');
    }

    function openReceipt(PK_ENROLLMENT_MASTER, RECEIPT_NUMBER) {
        let RECEIPT_NUMBER_ARRAY = RECEIPT_NUMBER.split(',');
        for (let i = 0; i < RECEIPT_NUMBER_ARRAY.length; i++) {
            window.open('generate_receipt_pdf.php?master_id=' + PK_ENROLLMENT_MASTER + '&receipt=' + RECEIPT_NUMBER_ARRAY[i], '_blank');
        }
    }
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const paymentMethods = document.querySelectorAll('.PAYMENT_METHOD');
        const paymentPlanFields = document.getElementById('payment_plans_div');
        const installmentInputs = document.querySelectorAll('.installment-input');

        paymentMethods.forEach(method => {
            method.addEventListener('change', function() {
                if (this.value === 'Payment Plans') {
                    paymentPlanFields.style.display = 'block';
                    installmentInputs.forEach(input => {
                        input.required = true;
                    });
                } else {
                    paymentPlanFields.style.display = 'none';
                    installmentInputs.forEach(input => {
                        input.required = false;
                        input.value = ''; // Clear values when not needed
                    });
                }
            });
        });

        // Initialize on page load
        const selectedMethod = document.querySelector('.PAYMENT_METHOD:checked');
        if (selectedMethod && selectedMethod.value === 'Payment Plans') {
            paymentPlanFields.style.display = 'block';
            installmentInputs.forEach(input => {
                input.required = true;
            });
        }
    });
</script>